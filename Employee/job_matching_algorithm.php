<?php
// Job Matching Algorithm for WorkConnect
// This file contains the core matching logic for recommending jobs to job seekers
// ai_job_matcher.php holds multi‑MB static maps; load it only after ensuring headroom.
@ini_set('memory_limit', '384M');
require_once 'ai_job_matcher.php';

class JobMatchingAlgorithm {
    private $conn;

    /**
     * Maximum active postings to score per recommended-jobs request (newest first).
     * Stops production timeouts when the job table is large; does not affect apply/compatibility for a single job.
     */
    private const RECOMMENDED_JOBS_CANDIDATE_LIMIT = 600;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Calculate compatibility score between a job seeker and job posting
     * Returns a score from 0-100
     */
    public function calculateCompatibilityScore($userId, $jobPostingId) {
        $breakdown = $this->calculateDetailedCompatibility($userId, $jobPostingId, null, null);
        return $breakdown['total_score'];
    }
    
    /**
     * Calculate detailed compatibility breakdown with individual factor scores
     * Returns array with total_score and individual factor scores
     *
     * @param array|null $jobSeekerPrefetched Row from jobseeker (skips DB when set — use in batch loops)
     * @param array|null $jobPostingPrefetched Row from job_postings (skips DB when set — must include same fields as getJobPostingData)
     */
    public function calculateDetailedCompatibility($userId, $jobPostingId, $jobSeekerPrefetched = null, $jobPostingPrefetched = null) {
        // Get job seeker data
        if (is_array($jobSeekerPrefetched)) {
            $jobSeeker = $jobSeekerPrefetched;
        } else {
            $jobSeeker = $this->getJobSeekerData($userId);
        }
        if (!$jobSeeker) {
            return [
                'total_score' => 0,
                'skill_score' => 0,
                'location_score' => 0,
                'occupation_score' => 0,
                'experience_score' => 0,
                'salary_score' => 0,
                'job_type_score' => 0,
                'matched_skills' => [],
                'matched_locations' => [],
                'matched_occupations' => [],
                'location_distance_km' => null,
                'is_nearby_current' => false,
                'location_basis' => 'none',
                'nearest_preferred_label' => null,
            ];
        }
        
        // Get job posting data
        if (is_array($jobPostingPrefetched) && (int)($jobPostingPrefetched['id'] ?? 0) === (int)$jobPostingId) {
            $jobPosting = $jobPostingPrefetched;
        } else {
            $jobPosting = $this->getJobPostingData($jobPostingId);
        }
        if (!$jobPosting) {
            return [
                'total_score' => 0,
                'skill_score' => 0,
                'location_score' => 0,
                'occupation_score' => 0,
                'experience_score' => 0,
                'salary_score' => 0,
                'job_type_score' => 0,
                'matched_skills' => [],
                'matched_locations' => [],
                'matched_occupations' => [],
                'location_distance_km' => null,
                'is_nearby_current' => false,
                'location_basis' => 'none',
                'nearest_preferred_label' => null,
            ];
        }
        
        // Calculate different matching factors with detailed info
        $skillResult = $this->calculateSkillMatch($jobSeeker, $jobPosting);
        $locationResult = $this->calculateLocationMatch($jobSeeker, $jobPosting);
        $occupationResult = $this->calculateOccupationMatch($jobSeeker, $jobPosting);
        $jobTypeScore = $this->calculateJobTypeMatch($jobSeeker, $jobPosting);
        
        // Extract scores and details
        $skillScore = is_array($skillResult) ? $skillResult['score'] : $skillResult;
        $locationScore = is_array($locationResult) ? $locationResult['score'] : $locationResult;
        $occupationScore = is_array($occupationResult) ? $occupationResult['score'] : $occupationResult;
        $matchedSkills = is_array($skillResult) ? ($skillResult['matched_skills'] ?? []) : [];
        $matchedLocations = is_array($locationResult) ? ($locationResult['matched_locations'] ?? []) : [];
        $matchedOccupations = is_array($occupationResult) ? ($occupationResult['matched_occupations'] ?? []) : [];
        $locationDistanceKm = is_array($locationResult) ? ($locationResult['distance_km'] ?? null) : null;
        $locationBasis = is_array($locationResult) ? ($locationResult['location_basis'] ?? null) : null;
        $isNearbyCurrentAddr = is_array($locationResult) && !empty($locationResult['is_nearby_current']);
        $nearestPreferredLabel = is_array($locationResult) ? ($locationResult['nearest_preferred_label'] ?? null) : null;
        
        // Skill match metadata (used by UI)
        $totalSkills = is_array($skillResult) ? (int)($skillResult['total_skills'] ?? 0) : 0;
        $matchedSkillCount = is_array($skillResult) ? (int)($skillResult['matched_count'] ?? count($matchedSkills)) : 0;

        // Final score rule:
        // weighted sum of 4 criteria only:
        // skills 40%, occupation 30%, location 20%, job type 10%
        $weights = [
            'skills' => 0.40,
            'occupation' => 0.30,
            'location' => 0.20,
            'job_type' => 0.10
        ];
        $totalScore = ($skillScore * $weights['skills']) +
                     ($occupationScore * $weights['occupation']) +
                     ($locationScore * $weights['location']) +
                     ($jobTypeScore * $weights['job_type']);
        
        return [
            'total_score' => round($totalScore, 2),
            'skill_score' => round($skillScore, 2),
            'location_score' => round($locationScore, 2),
            'occupation_score' => round($occupationScore, 2),
            // Kept for backward compatibility with any existing UI expectations.
            'experience_score' => 0,
            'salary_score' => 0,
            'job_type_score' => round($jobTypeScore, 2),
            'matched_skills' => $matchedSkills,
            'total_skills' => $totalSkills,
            'matched_count' => $matchedSkillCount,
            'matched_locations' => $matchedLocations,
            'matched_occupations' => $matchedOccupations,
            'location_distance_km' => $locationDistanceKm,
            'is_nearby_current' => $isNearbyCurrentAddr,
            'location_basis' => $locationBasis,
            'nearest_preferred_label' => $nearestPreferredLabel,
            'weights' => $weights
        ];
    }
    
    /**
     * Calculate skill matching score using NRSP form data
     * Returns array with score and matched skills list
     */
    private function calculateSkillMatch($jobSeeker, $jobPosting) {
        // Get skills from NRSP form: training_skills_1, training_skills_2, training_skills_3, skill_others, and checkbox skills
        $jobSeekerSkills = [];
        
        // Add training skills from text fields
        if (!$this->isNullLike($jobSeeker['training_skills_1'] ?? '')) {
            $skills1 = $this->splitPreferenceValues($jobSeeker['training_skills_1']);
            $jobSeekerSkills = array_merge($jobSeekerSkills, $skills1);
        }
        if (!$this->isNullLike($jobSeeker['training_skills_2'] ?? '')) {
            $skills2 = $this->splitPreferenceValues($jobSeeker['training_skills_2']);
            $jobSeekerSkills = array_merge($jobSeekerSkills, $skills2);
        }
        if (!$this->isNullLike($jobSeeker['training_skills_3'] ?? '')) {
            $skills3 = $this->splitPreferenceValues($jobSeeker['training_skills_3']);
            $jobSeekerSkills = array_merge($jobSeekerSkills, $skills3);
        }
        
        // Add skill_others (comma-separated)
        if (!$this->isNullLike($jobSeeker['skill_others'] ?? '')) {
            $others = $this->splitPreferenceValues($jobSeeker['skill_others']);
            $jobSeekerSkills = array_merge($jobSeekerSkills, $others);
        }
        
        // Add checkbox skills (boolean fields)
        $checkboxSkills = [
            'skill_auto_mechanic' => 'Auto Mechanic',
            'skill_electrician' => 'Electrician',
            'skill_photography' => 'Photography',
            'skill_beautician' => 'Beautician',
            'skill_embroidery' => 'Embroidery',
            'skill_plumbing' => 'Plumbing',
            'skill_carpentry' => 'Carpentry',
            'skill_gardening' => 'Gardening',
            'skill_sewing' => 'Sewing',
            'skill_computer' => 'Computer',
            'skill_masonry' => 'Masonry',
            'skill_stenography' => 'Stenography',
            'skill_domestic' => 'Domestic',
            'skill_painter' => 'Painter',
            'skill_tailoring' => 'Tailoring',
            'skill_driver' => 'Driver',
            'skill_painting' => 'Painting'
        ];
        
        foreach ($checkboxSkills as $field => $label) {
            if (!empty($jobSeeker[$field]) && $jobSeeker[$field] == 1) {
                $jobSeekerSkills[] = $label;
            }
        }
        
        // Also check skills_array if available (for backward compatibility)
        if (!empty($jobSeeker['skills_array'])) {
            $arraySkills = json_decode($jobSeeker['skills_array'], true);
            if (is_array($arraySkills)) {
                $jobSeekerSkills = array_merge($jobSeekerSkills, $arraySkills);
            }
        }
        
        // Remove empty values and normalize
        $jobSeekerSkills = array_filter($jobSeekerSkills, function($skill) {
            $skill = trim((string)$skill);
            return !$this->isNullLike($skill);
        });
        $jobSeekerSkills = array_values(array_unique(array_map('trim', $jobSeekerSkills)));

        $jobRequirements = strtolower($jobPosting['requirements'] . ' ' . $jobPosting['description'] . ' ' . $jobPosting['title']);
        
        // If no skills or only "n/a", return 0% score
        if (empty($jobSeekerSkills)) {
            return ['score' => 0, 'matched_skills' => [], 'total_skills' => 0, 'matched_count' => 0];
        }
        
        $matchedSkills = [];
        $totalSkills = count($jobSeekerSkills);

        // Shared keyword lists (one copy in memory per distinct list)
        $kwCommunication = ['communication', 'communicate', 'verbal', 'written', 'speaking', 'presentation', 'customer service', 'client relations', 'interpersonal', 'people skills', 'social', 'public speaking', 'negotiation', 'persuasion', 'explaining', 'listening', 'correspondence', 'email', 'phone', 'telephone', 'bilingual', 'multilingual', 'translation', 'interpretation', 'reporting', 'documentation', 'report writing', 'meeting', 'collaboration', 'teamwork'];
        $kwPainting = ['painter', 'painting', 'paint', 'decorative', 'interior', 'exterior', 'brush', 'roller', 'color', 'coating', 'finishing', 'renovation', 'construction', 'artistic', 'mural', 'wall painting', 'spray painting', 'automotive painting', 'industrial painting', 'residential painting', 'commercial painting'];
        $kwSewingTailoring = ['sewing', 'tailor', 'tailoring', 'dressmaking', 'garment', 'fabric', 'textile', 'clothing', 'apparel', 'alteration', 'pattern', 'stitching', 'embroidery', 'seamstress', 'dressmaker', 'alterations specialist', 'fashion design', 'costume', 'uniform'];
        
        // Comprehensive skill-to-keyword mapping for intelligent matching
        // Maps user skills to related keywords that should appear in job requirements
        $skillToKeywordsMap = [
            // Computer/IT Skills (expanded)
            'computer' => ['computer', 'it', 'information technology', 'software', 'programming', 'coding', 'developer', 'programmer', 'web development', 'web developer', 'technical', 'technology', 'tech', 'system', 'application', 'database', 'network', 'hardware', 'software development', 'software engineer', 'it support', 'technical support', 'computer science', 'cs', 'information systems', 'is', 'data entry', 'computer literacy', 'ms office', 'microsoft office', 'office suite', 'computer skills', 'basic computer', 'pc', 'desktop', 'laptop', 'excel', 'word', 'powerpoint', 'spreadsheet', 'data analysis', 'cybersecurity', 'cloud computing', 'devops', 'frontend', 'backend', 'fullstack', 'api', 'sql', 'javascript', 'python', 'java', 'php', 'html', 'css'],
            
            // Communication Skills (expanded)
            'communication' => $kwCommunication,
            'communication skills' => $kwCommunication,
            
            // Photography (expanded)
            'photography' => ['photography', 'photographer', 'photo', 'camera', 'photographic', 'imaging', 'visual', 'graphic design', 'adobe photoshop', 'photoshop', 'lightroom', 'editing', 'retouching', 'portrait', 'event photography', 'wedding photography', 'commercial photography', 'product photography', 'fashion photography', 'video', 'videography', 'cinematography', 'drone', 'aerial photography'],
            
            // Driver/Transportation (expanded)
            'driver' => ['driver', 'driving', 'delivery', 'transportation', 'logistics', 'vehicle', 'motorcycle', 'car', 'truck', 'van', 'fleet', 'chauffeur', 'courier', 'rider', 'delivery rider', 'motorcycle rider', 'transport', 'shipping', 'driver\'s license', 'drivers license', 'valid license', 'cdl', 'commercial driver', 'truck driver', 'van driver', 'taxi', 'uber', 'grab', 'food delivery', 'package delivery'],
            
            // Painter/Painting (expanded)
            'painter' => $kwPainting,
            'painting' => $kwPainting,
            
            // Sales/Retail (expanded)
            'sales' => ['sales', 'selling', 'retail', 'merchandise', 'customer', 'clerk', 'cashier', 'store', 'shop', 'outlet', 'point of sale', 'pos', 'transaction', 'inventory', 'product knowledge', 'upselling', 'cross-selling', 'sales representative', 'account executive', 'business development', 'client acquisition', 'revenue', 'quota', 'territory', 'cold calling', 'prospecting'],
            
            // Auto Mechanic (expanded)
            'auto mechanic' => ['mechanic', 'automotive', 'auto', 'vehicle repair', 'car repair', 'engine', 'transmission', 'brake', 'diagnostic', 'troubleshooting', 'maintenance', 'servicing', 'garage', 'workshop', 'automotive technician', 'auto technician', 'diesel mechanic', 'motorcycle mechanic', 'auto body', 'collision repair', 'tire service', 'oil change', 'tune-up'],
            
            // Electrician (expanded)
            'electrician' => ['electrician', 'electrical', 'wiring', 'circuit', 'electrical installation', 'electrical repair', 'electrical maintenance', 'power', 'voltage', 'electrical system', 'electrical work', 'residential electrician', 'commercial electrician', 'industrial electrician', 'electrical contractor', 'panel', 'breaker', 'outlet', 'switch', 'electrical code'],
            
            // Plumbing (expanded)
            'plumbing' => ['plumber', 'plumbing', 'pipe', 'water system', 'drainage', 'sewer', 'faucet', 'installation', 'repair', 'maintenance', 'waterworks', 'pipefitting', 'water heater', 'toilet', 'sink', 'shower', 'bathtub', 'drain', 'sewer line', 'water line'],
            
            // Carpentry (expanded)
            'carpentry' => ['carpenter', 'carpentry', 'woodwork', 'woodworking', 'furniture', 'cabinet', 'construction', 'framing', 'joinery', 'wood', 'lumber', 'saw', 'drill', 'cabinetmaker', 'finish carpenter', 'rough carpenter', 'trim', 'molding', 'millwork', 'custom furniture'],
            
            // Beautician (expanded)
            'beautician' => ['beautician', 'beauty', 'cosmetology', 'hair', 'makeup', 'salon', 'spa', 'styling', 'manicure', 'pedicure', 'facial', 'skincare', 'aesthetic', 'hairstylist', 'barber', 'nail technician', 'esthetician', 'massage therapist', 'waxing', 'eyebrow', 'eyelash'],
            
            // Sewing/Tailoring (expanded)
            'sewing' => $kwSewingTailoring,
            'tailoring' => $kwSewingTailoring,
            
            // Embroidery (expanded)
            'embroidery' => ['embroidery', 'embroid', 'needlework', 'stitching', 'decorative', 'textile', 'fabric', 'handicraft', 'craft', 'monogram', 'custom embroidery', 'machine embroidery', 'hand embroidery'],
            
            // Gardening (expanded)
            'gardening' => ['gardening', 'gardener', 'landscaping', 'landscape', 'horticulture', 'plant', 'lawn', 'garden', 'nursery', 'agriculture', 'farming', 'cultivation', 'landscape design', 'lawn care', 'tree care', 'irrigation', 'greenhouse', 'nursery worker', 'groundskeeper'],
            
            // Masonry (expanded)
            'masonry' => ['mason', 'masonry', 'brick', 'stone', 'concrete', 'construction', 'building', 'wall', 'foundation', 'cement', 'block', 'bricklayer', 'stonemason', 'concrete finisher', 'tile setter', 'tile installer', 'marble', 'granite'],
            
            // Stenography (expanded)
            'stenography' => ['stenography', 'stenographer', 'typing', 'transcription', 'shorthand', 'court reporter', 'legal transcription', 'secretary', 'administrative', 'court stenographer', 'closed captioning', 'captioning', 'realtime reporting'],
            
            // Domestic (expanded)
            'domestic' => ['domestic', 'housekeeping', 'housekeeper', 'cleaning', 'maid', 'household', 'home care', 'domestic helper', 'house help', 'house cleaning', 'residential cleaning', 'commercial cleaning', 'janitorial', 'custodial'],
            
            // Additional skills for comprehensive coverage
            'welding' => ['welder', 'welding', 'weld', 'arc welding', 'mig', 'tig', 'stick welding', 'fabrication', 'metal work', 'steel'],
            'cooking' => ['cook', 'chef', 'cooking', 'culinary', 'kitchen', 'food preparation', 'sous chef', 'line cook', 'pastry', 'baking', 'grill', 'sauté', 'prep cook'],
            'teaching' => ['teacher', 'teaching', 'instructor', 'educator', 'tutor', 'professor', 'lecturer', 'curriculum development', 'classroom management'],
            'nursing' => ['nurse', 'nursing', 'patient care', 'medical', 'healthcare', 'rn', 'lpn', 'cna', 'patient', 'clinical', 'hospital', 'clinic'],
            'accounting' => ['accountant', 'accounting', 'bookkeeping', 'financial', 'audit', 'tax', 'cpa', 'payroll', 'billing', 'accounts payable', 'accounts receivable', 'general ledger'],
            'marketing' => ['marketing', 'advertising', 'promotion', 'brand', 'social media', 'digital marketing', 'seo', 'sem', 'ppc', 'content marketing', 'email marketing', 'campaign'],
            'engineering' => ['engineer', 'engineering', 'mechanical', 'electrical', 'civil', 'chemical', 'industrial', 'design', 'cad', 'drafting', 'project management', 'technical drawing'],
            'legal' => ['lawyer', 'attorney', 'legal', 'paralegal', 'law', 'litigation', 'contract', 'legal research', 'compliance', 'legal writing'],
            'real estate' => ['real estate', 'realtor', 'realty', 'property', 'broker', 'agent', 'appraisal', 'property management', 'leasing', 'sales'],
            'farming' => ['farmer', 'farming', 'agriculture', 'agricultural', 'livestock', 'poultry', 'crop', 'harvest', 'irrigation', 'ranch', 'ranching'],
            'fitness' => ['fitness', 'trainer', 'personal trainer', 'gym', 'exercise', 'workout', 'strength training', 'cardio', 'yoga', 'pilates', 'coaching'],
            'security' => ['security', 'guard', 'security officer', 'surveillance', 'patrol', 'access control', 'cctv', 'alarm', 'safety', 'protection'],
            'hr' => ['human resources', 'hr', 'recruiter', 'talent acquisition', 'hiring', 'onboarding', 'employee relations', 'benefits', 'compensation', 'payroll'],
            'research' => ['research', 'researcher', 'laboratory', 'lab', 'data analysis', 'scientific', 'experiment', 'study', 'analysis', 'data collection'],
            'quality control' => ['quality control', 'qc', 'quality assurance', 'qa', 'inspection', 'testing', 'quality check', 'defect', 'standards', 'compliance'],
            'logistics' => ['logistics', 'supply chain', 'warehouse', 'inventory', 'distribution', 'shipping', 'receiving', 'forklift', 'order fulfillment', 'stock'],
            'energy' => ['energy', 'power', 'electric', 'utility', 'solar', 'wind', 'renewable', 'power plant', 'electrical utility', 'lineman'],
            'telecommunications' => ['telecommunications', 'telecom', 'telephone', 'internet', 'isp', 'network', 'fiber', 'cable', 'satellite', 'wireless'],
            'aviation' => ['aviation', 'airline', 'pilot', 'flight', 'aircraft', 'airport', 'air traffic', 'flight attendant', 'ground crew', 'aeronautical'],
            'maritime' => ['maritime', 'shipping', 'seafarer', 'seaman', 'sailor', 'captain', 'deck', 'engine', 'port', 'harbor', 'vessel'],
            'entertainment' => ['entertainment', 'performer', 'actor', 'actress', 'singer', 'musician', 'dancer', 'theater', 'stage', 'production'],
            'environmental' => ['environmental', 'sustainability', 'conservation', 'ecology', 'waste management', 'recycling', 'green', 'climate', 'environmental science']
        ];
        
        // Also check job title for skill relevance
        $jobTitle = strtolower($jobPosting['title']);
        $jobIndustry = strtolower($jobPosting['industry'] ?? '');
        
        // Comprehensive job categories for context-aware matching
        // NOTE: If a job doesn't match any category, the system will use flexible matching (less strict)
        // This ensures new job types still work correctly
        $jobCategories = [
            // IT & Technology
            'it' => ['developer', 'programmer', 'software', 'web', 'it support', 'technical support', 'system', 'network', 'database', 'programming', 'coding', 'computer science', 'information technology', 'tech', 'it', 'software engineer', 'web developer', 'app developer', 'mobile developer', 'frontend', 'backend', 'fullstack', 'devops', 'cybersecurity', 'data scientist', 'data analyst', 'ai', 'machine learning', 'cloud', 'system administrator', 'network engineer', 'database administrator', 'qa', 'quality assurance', 'tester', 'ui', 'ux', 'user interface', 'user experience'],
            
            // Manual Labor & Manufacturing
            'manual_labor' => ['factory', 'worker', 'warehouse', 'production', 'assembly', 'manufacturing', 'operator', 'machine operator', 'laborer', 'production worker', 'factory worker', 'packer', 'packaging', 'quality control', 'qc', 'machine', 'equipment operator', 'forklift', 'warehouse worker', 'stock', 'inventory'],
            
            // Delivery & Transportation
            'delivery' => ['delivery', 'rider', 'courier', 'driver', 'transport', 'logistics', 'shipping', 'dispatch', 'messenger', 'truck driver', 'van driver', 'motorcycle', 'bike', 'delivery driver', 'delivery rider', 'logistics coordinator', 'fleet', 'transportation'],
            
            // Sales & Retail
            'sales' => ['sales', 'retail', 'clerk', 'cashier', 'store', 'shop', 'salesperson', 'sales representative', 'merchandiser', 'sales associate', 'sales executive', 'account manager', 'business development', 'bd', 'sales manager', 'retail associate', 'store manager', 'supervisor', 'sales consultant', 'telemarketer', 'call center', 'telesales'],
            
            // Construction & Trades
            'construction' => ['construction', 'carpenter', 'mason', 'plumber', 'electrician', 'painter', 'welder', 'builder', 'contractor', 'foreman', 'construction worker', 'roofer', 'tiler', 'concrete', 'steel', 'ironworker', 'scaffolder', 'heavy equipment', 'excavator', 'crane operator', 'construction manager', 'site supervisor', 'architect', 'civil engineer'],
            
            // Service Industry
            'service' => ['service', 'customer service', 'receptionist', 'waiter', 'waitress', 'housekeeping', 'cleaning', 'server', 'host', 'bartender', 'housekeeper', 'janitor', 'custodian', 'maintenance', 'security guard', 'guard', 'concierge', 'bellhop', 'valet', 'porter', 'cleaner', 'maid', 'domestic helper'],
            
            // Creative & Design
            'creative' => ['designer', 'photographer', 'artist', 'creative', 'graphic', 'multimedia', 'illustrator', 'animator', 'videographer', 'video editor', 'editor', 'copywriter', 'content creator', 'ui designer', 'ux designer', 'web designer', 'interior designer', 'fashion designer', 'industrial designer', 'game designer', 'motion graphics', '3d artist', 'visual effects', 'vfx'],
            
            // Administrative & Office
            'administrative' => ['admin', 'administrative', 'secretary', 'clerk', 'office', 'receptionist', 'data entry', 'administrator', 'executive assistant', 'office assistant', 'office manager', 'administrative assistant', 'personal assistant', 'pa', 'virtual assistant', 'va', 'office clerk', 'file clerk', 'records', 'documentation'],
            
            // Healthcare & Medical
            'healthcare' => ['nurse', 'doctor', 'medical', 'healthcare', 'health', 'caregiver', 'therapist', 'dentist', 'pharmacist', 'veterinary', 'physician', 'surgeon', 'paramedic', 'emt', 'medical assistant', 'dental assistant', 'pharmacy', 'lab technician', 'medical technician', 'radiologist', 'physiotherapist', 'occupational therapist', 'psychologist', 'psychiatrist', 'veterinarian', 'vet', 'medical receptionist', 'hospital', 'clinic', 'health center'],
            
            // Education & Training
            'education' => ['teacher', 'instructor', 'educator', 'professor', 'tutor', 'trainer', 'faculty', 'education', 'teaching', 'lecturer', 'academic', 'principal', 'vice principal', 'school', 'university', 'college', 'training', 'coach', 'mentor', 'curriculum', 'librarian', 'research', 'academic researcher'],
            
            // Hospitality & Food Service
            'hospitality' => ['hotel', 'restaurant', 'cafe', 'resort', 'hospitality', 'chef', 'cook', 'kitchen', 'baker', 'pastry chef', 'sous chef', 'line cook', 'dishwasher', 'barista', 'food service', 'catering', 'banquet', 'event coordinator', 'hotel manager', 'front desk', 'concierge', 'housekeeping', 'laundry', 'spa', 'wellness'],
            
            // Finance & Accounting
            'finance' => ['accountant', 'finance', 'banking', 'auditor', 'bookkeeper', 'financial', 'accounting', 'cpa', 'tax', 'financial analyst', 'investment', 'banker', 'loan officer', 'credit analyst', 'treasurer', 'controller', 'payroll', 'billing', 'collections', 'insurance', 'actuary', 'financial advisor', 'wealth management'],
            
            // Marketing & Advertising
            'marketing' => ['marketing', 'advertising', 'promotion', 'brand', 'social media', 'digital marketing', 'marketer', 'marketing manager', 'brand manager', 'product manager', 'seo', 'sem', 'ppc', 'content marketing', 'email marketing', 'public relations', 'pr', 'event marketing', 'trade show', 'market research', 'analyst'],
            
            // Engineering
            'engineering' => ['engineer', 'engineering', 'mechanical', 'electrical', 'civil', 'chemical', 'industrial', 'aerospace', 'automotive', 'biomedical', 'environmental', 'materials', 'nuclear', 'petroleum', 'project engineer', 'design engineer', 'quality engineer', 'process engineer', 'maintenance engineer'],
            
            // Legal & Law
            'legal' => ['lawyer', 'attorney', 'legal', 'paralegal', 'law', 'judge', 'court', 'legal assistant', 'legal secretary', 'compliance', 'notary', 'mediator', 'arbitrator', 'legal advisor', 'corporate lawyer', 'criminal lawyer', 'family lawyer'],
            
            // Real Estate
            'real_estate' => ['real estate', 'realtor', 'realty', 'property', 'broker', 'agent', 'appraiser', 'property manager', 'leasing', 'real estate agent', 'real estate broker', 'property developer', 'land surveyor'],
            
            // Agriculture & Farming
            'agriculture' => ['farmer', 'farming', 'agriculture', 'agricultural', 'fisherman', 'fishing', 'livestock', 'poultry', 'crop', 'harvest', 'irrigation', 'agronomist', 'veterinarian', 'farm worker', 'ranch', 'ranching'],
            
            // Automotive
            'automotive' => ['automotive', 'auto', 'mechanic', 'car', 'vehicle', 'automobile', 'auto repair', 'auto technician', 'car mechanic', 'auto body', 'collision', 'auto parts', 'automotive technician', 'service advisor'],
            
            // Beauty & Cosmetics
            'beauty' => ['beauty', 'cosmetics', 'salon', 'spa', 'hairdresser', 'hairstylist', 'barber', 'makeup artist', 'esthetician', 'nail technician', 'massage therapist', 'cosmetologist', 'beautician', 'facialist'],
            
            // Fitness & Sports
            'fitness' => ['fitness', 'trainer', 'gym', 'personal trainer', 'coach', 'athletic', 'sports', 'instructor', 'yoga', 'pilates', 'fitness instructor', 'sports coach', 'athlete', 'physical education', 'pe'],
            
            // Media & Communications
            'media' => ['media', 'journalist', 'reporter', 'news', 'broadcast', 'radio', 'television', 'tv', 'anchor', 'producer', 'director', 'cameraman', 'sound engineer', 'broadcast engineer', 'news anchor', 'correspondent', 'writer', 'author', 'blogger'],
            
            // Non-Profit & Social Services
            'nonprofit' => ['nonprofit', 'non-profit', 'ngo', 'charity', 'social worker', 'counselor', 'case worker', 'community', 'outreach', 'volunteer coordinator', 'program coordinator', 'fundraiser', 'development officer'],
            
            // Government & Public Service
            'government' => ['government', 'public service', 'civil service', 'municipal', 'city', 'provincial', 'national', 'public administration', 'policy', 'public officer', 'government employee'],
            
            // Energy & Utilities
            'energy' => ['energy', 'power', 'electric', 'utility', 'solar', 'wind', 'renewable', 'oil', 'gas', 'petroleum', 'power plant', 'electrical utility', 'lineman', 'power line'],
            
            // Telecommunications
            'telecommunications' => ['telecommunications', 'telecom', 'telephone', 'internet', 'isp', 'network', 'fiber', 'cable', 'satellite', 'wireless', 'mobile network', 'telecom engineer', 'network technician'],
            
            // Aviation & Aerospace
            'aviation' => ['aviation', 'airline', 'pilot', 'flight', 'aircraft', 'airport', 'air traffic', 'flight attendant', 'ground crew', 'aviation mechanic', 'aeronautical'],
            
            // Maritime & Shipping
            'maritime' => ['maritime', 'shipping', 'seafarer', 'seaman', 'sailor', 'captain', 'deck', 'engine', 'port', 'harbor', 'maritime engineer', 'ship', 'vessel'],
            
            // Security & Law Enforcement
            'security' => ['security', 'guard', 'police', 'officer', 'law enforcement', 'detective', 'investigator', 'private investigator', 'security officer', 'bouncer', 'bodyguard', 'surveillance'],
            
            // Human Resources
            'hr' => ['human resources', 'hr', 'recruiter', 'talent acquisition', 'hr manager', 'hr assistant', 'payroll', 'benefits', 'compensation', 'employee relations', 'training and development'],
            
            // Supply Chain & Procurement
            'supply_chain' => ['supply chain', 'procurement', 'purchasing', 'buyer', 'sourcing', 'logistics coordinator', 'supply chain manager', 'inventory', 'warehouse manager', 'distribution'],
            
            // Research & Development
            'research' => ['research', 'researcher', 'scientist', 'laboratory', 'lab', 'r&d', 'research and development', 'analyst', 'data analyst', 'research assistant', 'scientific', 'biologist', 'chemist', 'physicist'],
            
            // Entertainment & Performing Arts
            'entertainment' => ['entertainment', 'performer', 'actor', 'actress', 'singer', 'musician', 'dancer', 'theater', 'theatre', 'stage', 'production', 'director', 'choreographer', 'composer', 'dj', 'disc jockey'],
            
            // Retail Management
            'retail_management' => ['store manager', 'retail manager', 'department manager', 'assistant manager', 'supervisor', 'team leader', 'shift manager', 'operations manager'],
            
            // Quality Assurance & Testing
            'qa' => ['quality assurance', 'qa', 'quality control', 'qc', 'tester', 'testing', 'test engineer', 'qa engineer', 'quality inspector', 'quality analyst'],
            
            // Environmental & Sustainability
            'environmental' => ['environmental', 'sustainability', 'conservation', 'ecologist', 'environmental engineer', 'waste management', 'recycling', 'green', 'climate', 'environmental scientist']
        ];
        
        // Determine job category from title and industry
        $detectedCategory = null;
        foreach ($jobCategories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($jobTitle, $keyword) !== false || strpos($jobIndustry, $keyword) !== false) {
                    $detectedCategory = $category;
                    break 2;
                }
            }
        }
        
        // Comprehensive skill-to-job-category relevance mapping
        // NOTE: If a skill is not in this map, it will use flexible matching (matches if keyword found)
        // This allows new skills to work automatically with new job types
        $skillRelevanceMap = [
            // Computer & IT Skills
            'computer' => ['it', 'administrative', 'qa', 'research', 'engineering', 'media', 'finance', 'marketing'],
            'computer skills' => ['it', 'administrative', 'qa', 'research', 'engineering', 'media', 'finance', 'marketing'],
            
            // Driver & Transportation
            'driver' => ['delivery', 'automotive', 'aviation', 'maritime', 'transport'],
            'driving' => ['delivery', 'automotive', 'aviation', 'maritime', 'transport'],
            
            // Communication Skills (relevant for customer-facing and collaborative roles)
            'communication' => ['sales', 'service', 'administrative', 'healthcare', 'education', 'hospitality', 'marketing', 'media', 'legal', 'hr', 'nonprofit', 'government', 'real_estate', 'finance'],
            'communication skills' => ['sales', 'service', 'administrative', 'healthcare', 'education', 'hospitality', 'marketing', 'media', 'legal', 'hr', 'nonprofit', 'government', 'real_estate', 'finance'],
            
            // Photography & Visual Arts
            'photography' => ['creative', 'marketing', 'media', 'entertainment', 'real_estate'],
            'photographer' => ['creative', 'marketing', 'media', 'entertainment', 'real_estate'],
            
            // Painting & Construction
            'painter' => ['construction', 'creative', 'automotive'],
            'painting' => ['construction', 'creative', 'automotive'],
            
            // Sales Skills
            'sales' => ['sales', 'marketing', 'real_estate', 'retail_management', 'finance'],
            
            // Auto Mechanic
            'auto mechanic' => ['automotive', 'manual_labor', 'delivery'],
            'mechanic' => ['automotive', 'manual_labor', 'delivery', 'aviation', 'maritime'],
            
            // Electrical & Technical
            'electrician' => ['construction', 'engineering', 'energy', 'telecommunications', 'it'],
            'electrical' => ['construction', 'engineering', 'energy', 'telecommunications', 'it'],
            
            // Plumbing
            'plumbing' => ['construction', 'service'],
            'plumber' => ['construction', 'service'],
            
            // Carpentry & Woodwork
            'carpentry' => ['construction', 'creative', 'manual_labor'],
            'carpenter' => ['construction', 'creative', 'manual_labor'],
            
            // Gardening & Landscaping
            'gardening' => ['manual_labor', 'service', 'agriculture', 'hospitality', 'real_estate'],
            'gardener' => ['manual_labor', 'service', 'agriculture', 'hospitality', 'real_estate'],
            
            // Masonry
            'masonry' => ['construction'],
            'mason' => ['construction'],
            
            // Sewing & Textiles
            'sewing' => ['manual_labor', 'creative', 'service'],
            'tailoring' => ['manual_labor', 'creative', 'service'],
            'embroidery' => ['manual_labor', 'creative', 'service'],
            
            // Beauty & Personal Care
            'beautician' => ['beauty', 'service', 'hospitality'],
            'beauty' => ['beauty', 'service', 'hospitality'],
            
            // Domestic & Housekeeping
            'domestic' => ['service', 'healthcare', 'hospitality'],
            'housekeeping' => ['service', 'healthcare', 'hospitality'],
            
            // Stenography & Administrative
            'stenography' => ['administrative', 'legal', 'government', 'media'],
            'typing' => ['administrative', 'legal', 'government', 'media', 'it'],
            
            // Auto Mechanic (specific)
            'automotive' => ['automotive', 'manual_labor', 'delivery'],
            
            // Construction Trades
            'welder' => ['construction', 'engineering', 'manufacturing'],
            'welding' => ['construction', 'engineering', 'manufacturing'],
            
            // Service Industry
            'cooking' => ['hospitality', 'service'],
            'chef' => ['hospitality', 'service'],
            'baker' => ['hospitality', 'service', 'retail_management'],
            
            // Healthcare Related
            'caregiving' => ['healthcare', 'service', 'nonprofit'],
            'nursing' => ['healthcare'],
            'medical' => ['healthcare'],
            
            // Education
            'teaching' => ['education'],
            'tutoring' => ['education'],
            
            // Finance & Accounting
            'accounting' => ['finance', 'administrative'],
            'bookkeeping' => ['finance', 'administrative'],
            
            // Marketing & Advertising
            'marketing' => ['marketing', 'sales', 'media', 'creative'],
            'advertising' => ['marketing', 'media', 'creative'],
            
            // Engineering
            'engineering' => ['engineering', 'construction', 'it', 'energy', 'telecommunications', 'aviation', 'maritime'],
            
            // Legal
            'legal' => ['legal', 'government', 'administrative'],
            'paralegal' => ['legal', 'government', 'administrative'],
            
            // Real Estate
            'real estate' => ['real_estate', 'sales', 'finance'],
            
            // Agriculture
            'farming' => ['agriculture', 'manual_labor'],
            'agricultural' => ['agriculture', 'manual_labor'],
            
            // Fitness & Sports
            'fitness' => ['fitness', 'education', 'healthcare', 'hospitality'],
            'coaching' => ['fitness', 'education', 'sports', 'entertainment'],
            
            // Media & Communications
            'journalism' => ['media', 'entertainment', 'marketing'],
            'writing' => ['media', 'entertainment', 'marketing', 'creative', 'administrative'],
            
            // Security
            'security' => ['security', 'government', 'service'],
            
            // Human Resources
            'hr' => ['hr', 'administrative', 'government'],
            'recruiting' => ['hr', 'administrative'],
            
            // Research
            'research' => ['research', 'education', 'healthcare', 'engineering', 'it'],
            'laboratory' => ['research', 'healthcare', 'engineering', 'qa'],
            
            // Quality Assurance
            'quality control' => ['qa', 'manual_labor', 'manufacturing', 'engineering'],
            'testing' => ['qa', 'it', 'engineering', 'research'],
            
            // Supply Chain
            'logistics' => ['supply_chain', 'delivery', 'manual_labor'],
            'warehouse' => ['supply_chain', 'manual_labor', 'delivery'],
            
            // Energy & Utilities
            'power' => ['energy', 'engineering', 'construction'],
            'electrical utility' => ['energy', 'engineering', 'telecommunications'],
            
            // Telecommunications
            'telecom' => ['telecommunications', 'it', 'engineering'],
            'network' => ['telecommunications', 'it', 'engineering'],
            
            // Aviation
            'aviation' => ['aviation', 'engineering', 'transport'],
            'pilot' => ['aviation', 'transport'],
            
            // Maritime
            'maritime' => ['maritime', 'engineering', 'transport'],
            'seafarer' => ['maritime', 'transport'],
            
            // Entertainment
            'performing' => ['entertainment', 'media', 'creative'],
            'acting' => ['entertainment', 'media', 'creative'],
            'music' => ['entertainment', 'media', 'creative', 'education'],
            
            // Environmental
            'environmental' => ['environmental', 'engineering', 'research', 'government'],
            'sustainability' => ['environmental', 'engineering', 'research', 'government']
        ];
        
        foreach ($jobSeekerSkills as $skill) {
            $skillLower = strtolower(trim($skill));
            if (empty($skillLower)) continue;
            $skillVariants = $this->expandSkillVariants($skillLower);
            
            $isMatched = false;

            // 0. Semantic-first fallback:
            // Always allow exact/variant hits in title/requirements before category gating.
            // This prevents false negatives like "driving" vs "driver".
            foreach ($skillVariants as $variant) {
                $variantPattern = '/\b' . preg_quote($variant, '/') . '\b/i';
                if (preg_match($variantPattern, $jobRequirements) || preg_match($variantPattern, $jobTitle)) {
                    $matchedSkills[] = $skill;
                    $isMatched = true;
                    break;
                }
            }
            if ($isMatched) {
                continue;
            }
            
            // Check if skill is relevant to the job category
            $skillIsRelevant = false;
            if ($detectedCategory) {
                // Check if this skill is relevant to the detected job category
                foreach ($skillRelevanceMap as $relevantSkill => $relevantCategories) {
                    foreach ($skillVariants as $variant) {
                        if (strpos($variant, $relevantSkill) !== false || strpos($relevantSkill, $variant) !== false) {
                            if (in_array($detectedCategory, $relevantCategories)) {
                                $skillIsRelevant = true;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            // 1. Direct skill match in requirements/description/title (only if relevant or no category detected)
            if (!$detectedCategory || $skillIsRelevant) {
                // Use word boundary matching for more precise matching
                $skillPattern = '/\b' . preg_quote($skillLower, '/') . '\b/i';
                if (preg_match($skillPattern, $jobRequirements) || preg_match($skillPattern, $jobTitle)) {
                $matchedSkills[] = $skill;
                    $isMatched = true;
                continue;
                }
            }
            
            // 2. Check skill-to-keyword mapping for intelligent matching (only if relevant)
            if (!$detectedCategory || $skillIsRelevant) {
                foreach ($skillToKeywordsMap as $mappedSkill => $keywords) {
                    // Check if user skill matches the mapped skill (exact match, contains, or is contained)
                    $skillMatches = false;
                    foreach ($skillVariants as $variant) {
                        if (($variant === $mappedSkill) ||
                            strpos($variant, $mappedSkill) !== false ||
                            strpos($mappedSkill, $variant) !== false) {
                            $skillMatches = true;
                            break;
                        }
                    }
                    
                    if ($skillMatches) {
                        // Check if any related keyword appears in job requirements or title
                        // Use word boundary matching for more precise keyword matching
                        foreach ($keywords as $keyword) {
                            $keywordPattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
                            if (preg_match($keywordPattern, $jobRequirements) || preg_match($keywordPattern, $jobTitle)) {
                                // Additional context check: ensure keyword is in a relevant context
                                // For example, "computer" in "computer skills" is relevant, but "computer" in "computer-controlled machine" for factory worker is less relevant
                                if ($this->isKeywordInRelevantContext($keyword, $jobRequirements, $jobTitle, $detectedCategory)) {
                    $matchedSkills[] = $skill;
                                    $isMatched = true;
                                    break 2; // Break out of both loops
                                }
                            }
                        }
                    }
                }
            }
            
            // 3. Partial word matching for compound skills (only if relevant and no direct match found)
            if (!$isMatched && (!$detectedCategory || $skillIsRelevant)) {
                $skillWords = explode(' ', $skillLower);
                foreach ($skillWords as $word) {
                    if (strlen($word) > 4) { // Only check words longer than 4 characters (more strict)
                        $wordPattern = '/\b' . preg_quote($word, '/') . '\b/i';
                        if (preg_match($wordPattern, $jobRequirements) || preg_match($wordPattern, $jobTitle)) {
                            if ($this->isKeywordInRelevantContext($word, $jobRequirements, $jobTitle, $detectedCategory)) {
                                $matchedSkills[] = $skill;
                                $isMatched = true;
                    break;
                            }
                        }
                    }
                }
            }
        }
        
        // AI semantic pass for all skills (non-exact matching support)
        $aiSkillResult = AIJobMatcher::matchSkillsWithAI(
            $jobSeekerSkills,
            (string)($jobPosting['title'] ?? ''),
            (string)($jobPosting['description'] ?? ''),
            (string)($jobPosting['requirements'] ?? ''),
            (string)($jobPosting['industry'] ?? '')
        );
        if (!empty($aiSkillResult['matched_skills'])) {
            $matchedSkills = array_values(array_unique(array_merge($matchedSkills, $aiSkillResult['matched_skills'])));
        }

        // Calculate percentage
        $matchedCount = count($matchedSkills);
        $totalSkills = max(1, $totalSkills);
        
        // Base score is percentage of matched skills
        $baseScore = ($matchedCount / $totalSkills) * 100;
        
        // Final score calculation:
        if ($matchedCount > 0) {
            // Any relevant skill match is a strong indicator of fit.
            // We give 100% if there is at least one solid match, as requested.
            $finalScore = 100;
        } else {
            // AI might have found a weak semantic match
            $finalScore = (float)($aiSkillResult['score'] ?? 0);
        }
        
        return [
            'score' => round($finalScore, 2),
            'matched_skills' => array_unique($matchedSkills),
            'total_skills' => $totalSkills,
            'matched_count' => $matchedCount
        ];
    }
    
    /**
     * Calculate location matching score using NRSP form data with AI proximity matching
     * Returns array with score and matched locations list
     */
    private function calculateLocationMatch($jobSeeker, $jobPosting) {
        // Get preferred locations from NRSP form: local1, local2, local3
        $preferredLocations = [];
        
        if (!$this->isNullLike($jobSeeker['local1'] ?? '')) {
            $preferredLocations[] = trim($jobSeeker['local1']);
        }
        if (!$this->isNullLike($jobSeeker['local2'] ?? '')) {
            $preferredLocations[] = trim($jobSeeker['local2']);
        }
        if (!$this->isNullLike($jobSeeker['local3'] ?? '')) {
            $preferredLocations[] = trim($jobSeeker['local3']);
        }
        
        // Also check user_preferences for backward compatibility
        if (empty($preferredLocations)) {
            $preferences = $this->getUserPreferences($jobSeeker['user_id'] ?? 0);
            if ($preferences && !empty($preferences['preferred_locations'])) {
                $preferredLocations = json_decode($preferences['preferred_locations'], true);
                if (!is_array($preferredLocations)) {
                    $preferredLocations = [];
                }
            }
        }
        
        // Jobseeker's CURRENT ADDRESS (highest priority)
        $currentProvince = $jobSeeker['province'] ?? '';
        $currentMunicipality = $jobSeeker['municipality'] ?? '';
        $userCurrentLocation = null;
        if (!empty($currentProvince) && !empty($currentMunicipality)) {
            $userCurrentLocation = $currentMunicipality . ', ' . $currentProvince;
        }
        
        $jobLocation = trim((string)($jobPosting['location'] ?? ''));
        
        if (empty($preferredLocations) && empty($userCurrentLocation)) {
            return [
                'score' => 70,
                'matched_locations' => [],
                'distance_km' => null,
                'is_nearby_current' => false,
                'location_basis' => 'none',
                'nearest_preferred_label' => null,
            ];
        }

        // Expand "City, Province" into multiple matchable variants
        $expandedLocations = [];
        foreach ($preferredLocations as $location) {
            $location = trim((string)$location);
            if ($this->isNullLike($location)) continue;
            $expandedLocations[] = $location;
            $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
            if (count($parts) >= 2) {
                $expandedLocations[] = $parts[0];
                $expandedLocations[] = $parts[count($parts) - 1];
            }
        }
        $preferredLocations = array_values(array_unique(array_filter($expandedLocations)));
        
        // Use AI-powered location matching with prioritized logic
        return AIJobMatcher::matchLocationWithProximity($preferredLocations, $jobLocation, $userCurrentLocation);
    }
    
    /**
     * Calculate occupation matching score using NRSP form data with AI semantic matching
     * Matches job title to preferred occupations (occupation1, occupation2, occupation3)
     * Handles "any" values intelligently
     * Returns array with score and matched occupations list
     */
    private function calculateOccupationMatch($jobSeeker, $jobPosting) {
        // Get preferred occupations from NRSP form
        $preferredOccupations = [];
        
        if (!$this->isNullLike($jobSeeker['occupation1'] ?? '')) {
            $preferredOccupations[] = trim($jobSeeker['occupation1']);
        }
        if (!$this->isNullLike($jobSeeker['occupation2'] ?? '')) {
            $preferredOccupations[] = trim($jobSeeker['occupation2']);
        }
        if (!$this->isNullLike($jobSeeker['occupation3'] ?? '')) {
            $preferredOccupations[] = trim($jobSeeker['occupation3']);
        }
        
        $jobTitle = $jobPosting['title'];
        $jobDescription = strtolower($jobPosting['description'] . ' ' . $jobPosting['requirements']);
        
        if (empty($preferredOccupations)) {
            return ['score' => 50, 'matched_occupations' => []]; // Default score if no occupation preferences
        }
        
        // Expand equivalent/synonym occupation variants before AI matching.
        $expandedOccupations = [];
        foreach ($preferredOccupations as $occupation) {
            $expandedOccupations = array_merge($expandedOccupations, $this->expandOccupationVariants($occupation));
        }
        $expandedOccupations = array_values(array_unique(array_filter($expandedOccupations)));

        // Use AI-powered occupation matching (handles "any" and semantic similarity)
        $result = AIJobMatcher::matchOccupationWithAI($expandedOccupations, $jobTitle);
        
        // Also check job description for additional consideration
        foreach ($expandedOccupations as $prefOcc) {
            $prefOcc = trim($prefOcc);
            if ($this->isNullLike($prefOcc)) {
                continue;
            }
            
            // If not already matched, check description
            if (!in_array($prefOcc, $result['matched_occupations']) && 
                strpos($jobDescription, strtolower($prefOcc)) !== false) {
                $result['matched_occupations'][] = $prefOcc;
                if ($result['score'] < 60) {
                    $result['score'] = 60; // considerable relationship
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Calculate experience matching score
     */
    private function calculateExperienceMatch($jobSeeker, $jobPosting) {
        $jobSeekerExperience = (int)($jobSeeker['years_experience'] ?? 0);
        $requirements = strtolower($jobPosting['requirements']);
        
        // Extract experience requirements from job posting
        $experienceRequired = $this->extractExperienceRequirement($requirements);
        
        if ($experienceRequired === null) {
            return 80; // No specific experience requirement
        }
        
        $difference = abs($jobSeekerExperience - $experienceRequired);
        
        // Score based on how close the experience is
        if ($difference === 0) {
            return 100; // Perfect match
        } elseif ($difference <= 1) {
            return 90; // Very close
        } elseif ($difference <= 2) {
            return 75; // Close
        } elseif ($difference <= 3) {
            return 60; // Somewhat close
        } else {
            return max(30, 60 - ($difference * 5)); // Decreasing score
        }
    }
    
    /**
     * Calculate salary matching score
     */
    private function calculateSalaryMatch($jobSeeker, $jobPosting) {
        $salaryRange = $jobPosting['salary_range'];
        
        // Try to get salary preference from user_preferences
        $preferences = $this->getUserPreferences($jobSeeker['user_id'] ?? 0);
        $minExpectedSalary = null;
        
        if ($preferences && !empty($preferences['min_salary'])) {
            $minExpectedSalary = (float)$preferences['min_salary'];
        }
        
        if (!$minExpectedSalary || !$salaryRange) {
            return 70; // Default score if no salary info
        }
        
        $jobSalaryRange = $this->parseSalaryRange($salaryRange);
        
        if (!$jobSalaryRange) {
            return 70; // Can't parse salary range
        }
        
        $jobMinSalary = $jobSalaryRange['min'];
        $jobMaxSalary = $jobSalaryRange['max'];
        
        // Perfect match if expected salary is within job range
        if ($minExpectedSalary >= $jobMinSalary && $minExpectedSalary <= $jobMaxSalary) {
            return 100;
        }
        
        // Calculate score based on how close it is
        if ($minExpectedSalary < $jobMinSalary) {
            $difference = $jobMinSalary - $minExpectedSalary;
            $percentage = ($difference / $jobMinSalary) * 100;
            
            if ($percentage <= 10) return 90;
            elseif ($percentage <= 20) return 80;
            elseif ($percentage <= 30) return 70;
            else return max(40, 70 - ($percentage - 30));
        } else {
            // Expected salary is higher than job max
            $difference = $minExpectedSalary - $jobMaxSalary;
            $percentage = ($difference / $jobMaxSalary) * 100;
            
            if ($percentage <= 10) return 85;
            elseif ($percentage <= 20) return 75;
            elseif ($percentage <= 30) return 65;
            else return max(30, 65 - ($percentage - 30));
        }
    }
    
    /**
     * Calculate job type matching score using NRSP form data
     */
    private function calculateJobTypeMatch($jobSeeker, $jobPosting) {
        // Get job type preferences from NRSP form: fulltime, parttime
        $preferredJobTypes = [];
        
        if (!empty($jobSeeker['fulltime']) && $jobSeeker['fulltime'] == 1) {
            $preferredJobTypes[] = 'Full-time';
        }
        if (!empty($jobSeeker['parttime']) && $jobSeeker['parttime'] == 1) {
            $preferredJobTypes[] = 'Part-time';
        }
        
        // Also check user_preferences for backward compatibility
        if (empty($preferredJobTypes)) {
            $preferences = $this->getUserPreferences($jobSeeker['user_id'] ?? 0);
            if ($preferences && !empty($preferences['preferred_job_types'])) {
                $preferredJobTypes = json_decode($preferences['preferred_job_types'], true);
                if (!is_array($preferredJobTypes)) {
                    $preferredJobTypes = [];
                }
            }
        }
        
        $jobType = $jobPosting['job_type'];
        
        if (empty($preferredJobTypes)) {
            return 0; // No selected preference => no match score
        }
        
        // Check for exact match
        foreach ($preferredJobTypes as $preferredType) {
            if (strtolower($preferredType) === strtolower($jobType)) {
                return 100; // exact preference match
            }
        }

        return 0; // requested: either 0 or 100
    }
    
    /**
     * Get recommended jobs for a user
     * Only returns jobs that meet minimum compatibility threshold (default 50%)
     */
    public function getRecommendedJobs($userId, $limit = 20, $minScore = 50) {
        // Only jobs tied to a real company account (same rule as Employer/job_postings.php).
        // Rows with company_id NULL are legacy seed/demo and must not appear in recommendations.
        $companyFilter = '';
        $colCheck = @$this->conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $companyFilter = ' AND jp.company_id IS NOT NULL';
        }

        $stmt = $this->conn->prepare("
            SELECT jp.*, 
                   COALESCE(jae.compatibility_score, 0) as compatibility_score,
                   jae.applied_date,
                   jae.status AS my_application_status,
                   CASE WHEN jae.id IS NOT NULL THEN 1 ELSE 0 END as already_applied
            FROM job_postings jp
            LEFT JOIN job_applications_extended jae ON jp.id = jae.job_posting_id AND jae.jobseeker_id = (
                SELECT id FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1
            )
            WHERE jp.status = 'Active'" . $companyFilter . "
            ORDER BY jp.created_at DESC
            LIMIT " . (int) self::RECOMMENDED_JOBS_CANDIDATE_LIMIT . "
        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $allJobs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $jobSeeker = $this->getJobSeekerData($userId);
        if (!$jobSeeker) {
            return [];
        }
        
        // Calculate compatibility scores and detailed breakdown for all jobs
        $jobsWithScores = [];
        foreach ($allJobs as $job) {
            // Calculate detailed compatibility
            $breakdown = $this->calculateDetailedCompatibility($userId, (int) $job['id'], $jobSeeker, $job);
            
            // Use stored score if already applied, otherwise use calculated score
            if ($job['already_applied'] == 1 && $job['compatibility_score'] > 0) {
                $breakdown['total_score'] = $job['compatibility_score'];
            }
            
            // Only include jobs that meet minimum threshold
            if ($breakdown['total_score'] >= $minScore) {
                $job['compatibility_score'] = $breakdown['total_score'];
                $job['match_breakdown'] = $breakdown;
                $jobsWithScores[] = $job;
            }
        }
        
        // Sort by compatibility score (highest first)
        usort($jobsWithScores, function($a, $b) {
            return $b['compatibility_score'] <=> $a['compatibility_score'];
        });
        
        // Limit results
        return array_slice($jobsWithScores, 0, $limit);
    }
    
    /**
     * Update compatibility scores for all active jobs for a user
     * NOTE: This function is deprecated - we don't store scores to avoid creating false applications
     * Applications should only be created when user explicitly applies
     */
    public function updateAllCompatibilityScores($userId) {
        // This function is kept for backward compatibility but does nothing
        // to prevent auto-creating application records
        // Scores are calculated on-the-fly when needed
        return;
    }
    
    // Helper methods
    
    private function getJobSeekerData($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    private function getJobPostingData($jobPostingId) {
        $stmt = $this->conn->prepare("SELECT * FROM job_postings WHERE id = ?");
        $stmt->bind_param("i", $jobPostingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    private function getUserPreferences($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    private function extractExperienceRequirement($requirements) {
        // Look for patterns like "2+ years", "3-5 years", "minimum 2 years"
        if (preg_match('/(\d+)\+?\s*years?/', $requirements, $matches)) {
            return (int)$matches[1];
        }
        if (preg_match('/minimum\s+(\d+)\s*years?/', $requirements, $matches)) {
            return (int)$matches[1];
        }
        if (preg_match('/(\d+)-(\d+)\s*years?/', $requirements, $matches)) {
            return (int)$matches[1]; // Return minimum
        }
        return null;
    }
    
    private function parseSalaryRange($salaryRange) {
        // Parse formats like "25000-35000", "25,000 - 35,000", "25k-35k"
        $salaryRange = str_replace(',', '', $salaryRange);
        $salaryRange = str_replace('k', '000', $salaryRange);
        
        if (preg_match('/(\d+)-(\d+)/', $salaryRange, $matches)) {
            return [
                'min' => (float)$matches[1],
                'max' => (float)$matches[2]
            ];
        }
        
        return null;
    }
    
    /**
     * Check if a keyword appears in a relevant context for the job
     * This prevents false matches like "computer" in "computer-controlled machine" for factory workers
     */
    private function isKeywordInRelevantContext($keyword, $jobRequirements, $jobTitle, $jobCategory) {
        // If no category detected, allow the match (less strict)
        if (!$jobCategory) {
            return true;
        }
        
        $keywordLower = strtolower($keyword);
        $text = strtolower($jobRequirements . ' ' . $jobTitle);
        
        // Context exclusion patterns - if keyword appears in these contexts, it's likely not relevant
        $exclusionPatterns = [
            'computer' => [
                '/computer[-\s]?controlled/i', // "computer-controlled"
                '/computer[-\s]?based/i', // "computer-based system"
                '/computer[-\s]?assisted/i', // "computer-assisted"
                '/computer[-\s]?generated/i', // "computer-generated"
            ],
            'driver' => [
                '/driving[-\s]?(results|innovation|growth|success|change|improvement)/i', // "driving results" etc.
                '/driver[-\s]?(seat|license|program|software)/i', // "driver seat", "driver license" (but not "driver's license" which is valid)
            ],
        ];
        
        // Check exclusion patterns
        if (isset($exclusionPatterns[$keywordLower])) {
            foreach ($exclusionPatterns[$keywordLower] as $pattern) {
                if (preg_match($pattern, $text)) {
                    return false; // Keyword in irrelevant context
                }
            }
        }
        
        // For IT jobs, "computer" should be in contexts like "computer skills", "computer science", "computer programming"
        if ($keywordLower === 'computer' && $jobCategory === 'it') {
            $relevantContexts = [
                '/computer[-\s]?(skills|science|programming|knowledge|literacy|experience|expertise)/i',
                '/basic[-\s]?computer/i',
                '/computer[-\s]?proficient/i',
                '/it[-\s]?skills/i',
                '/information[-\s]?technology/i'
            ];
            foreach ($relevantContexts as $pattern) {
                if (preg_match($pattern, $text)) {
                    return true; // Found in relevant context
                }
            }
            // If "computer" appears but not in relevant IT context, it might be less relevant
            // But still allow it if it's in the title or requirements explicitly
            return strpos($jobTitle, 'computer') !== false || strpos($jobTitle, 'it') !== false || strpos($jobTitle, 'software') !== false;
        }
        
        // For delivery jobs, "driver" should be in contexts like "driver's license", "delivery driver", "motorcycle driver"
        if ($keywordLower === 'driver' && $jobCategory === 'delivery') {
            $relevantContexts = [
                '/driver[\'s]?[-\s]?license/i',
                '/delivery[-\s]?driver/i',
                '/motorcycle[-\s]?driver/i',
                '/vehicle[-\s]?driver/i',
                '/valid[-\s]?driver/i'
            ];
            foreach ($relevantContexts as $pattern) {
                if (preg_match($pattern, $text)) {
                    return true; // Found in relevant context
                }
            }
            // If "driver" appears in delivery job title, it's relevant
            return strpos($jobTitle, 'driver') !== false || strpos($jobTitle, 'rider') !== false || strpos($jobTitle, 'delivery') !== false;
        }
        
        // Default: allow the match if it passed exclusion checks
        return true;
    }

    private function isNullLike($value) {
        $v = strtolower(trim((string)$value));
        return $v === '' || $v === 'n/a' || $v === 'na' || $v === 'null' || $v === 'none';
    }

    private function splitPreferenceValues($text) {
        $parts = preg_split('/[,\/\|;]+/', (string)$text);
        if (!is_array($parts)) return [];
        return array_values(array_filter(array_map('trim', $parts), function($item) {
            return !$this->isNullLike($item);
        }));
    }

    private function expandOccupationVariants($occupation) {
        $raw = trim((string)$occupation);
        if ($this->isNullLike($raw)) return [];
        $variants = [$raw];
        $lower = strtolower($raw);

        if ($lower === 'any') {
            return ['any'];
        }

        $map = [
            'web developer' => ['web developer', 'website developer', 'frontend developer', 'back-end developer'],
            'software developer' => ['software developer', 'software engineer', 'programmer'],
            'service crew' => ['service crew', 'food service crew', 'crew member'],
            'driver' => ['driver', 'delivery driver', 'truck driver', 'van driver'],
            'cashier' => ['cashier', 'retail cashier', 'store cashier']
        ];
        foreach ($map as $key => $equivalents) {
            if (strpos($lower, $key) !== false) {
                $variants = array_merge($variants, $equivalents);
            }
        }

        return array_values(array_unique($variants));
    }

    private function expandSkillVariants($skill) {
        $raw = strtolower(trim((string)$skill));
        if ($this->isNullLike($raw)) return [];

        $variants = [$raw];

        // Normalize punctuation variants
        $compact = preg_replace('/[^a-z0-9\s]/', ' ', $raw);
        $compact = preg_replace('/\s+/', ' ', trim((string)$compact));
        if (!$this->isNullLike($compact)) {
            $variants[] = $compact;
        }

        // Common verb/noun/role variants for better skill matching
        $map = [
            'driving' => ['driver', 'drive', 'delivery driver', 'truck driver', 'van driver', 'rider'],
            'driver' => ['driving', 'drive', 'delivery driver', 'truck driver', 'van driver', 'rider'],
            'painting job' => ['painting', 'painter', 'paint'],
            'painter' => ['painting', 'paint'],
            'computer literacy' => ['computer', 'computer skills', 'it'],
            'communication skills' => ['communication', 'customer service']
        ];

        foreach ($map as $key => $synonyms) {
            if (strpos($raw, $key) !== false) {
                $variants = array_merge($variants, $synonyms);
            }
        }

        // Lightweight stemming forms
        if (preg_match('/ing$/', $raw)) {
            $variants[] = preg_replace('/ing$/', '', $raw);
            $variants[] = preg_replace('/ing$/', 'er', $raw); // driving -> driver
        } elseif (preg_match('/er$/', $raw)) {
            $variants[] = preg_replace('/er$/', 'ing', $raw); // driver -> driving
        }

        return array_values(array_unique(array_filter(array_map('trim', $variants), function($v) {
            return $v !== '';
        })));
    }
    
    /**
     * Store compatibility score when user applies
     * This should ONLY be called when user explicitly applies for a job
     * DO NOT call this during recommendation calculation to avoid creating false applications
     */
    private function storeCompatibilityScore($userId, $jobPostingId, $score) {
        // DEPRECATED: This function should not be used for storing scores during recommendations
        // Applications should only be created when user explicitly clicks "Apply"
        // This function is kept for backward compatibility but should not be called
        // during recommendation calculation
        
        // If you need to store compatibility scores, do it only when creating the application
        // in recommended_jobs.php when user clicks "Apply"
        return;
    }
}

// Usage example and API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    require_once 'db.php';
    $matching = new JobMatchingAlgorithm($conn);
    
    switch ($_GET['action']) {
        case 'get_recommendations':
            if (isset($_GET['user_id'])) {
                $userId = (int)$_GET['user_id'];
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                
                $recommendations = $matching->getRecommendedJobs($userId, $limit);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'recommendations' => $recommendations
                ]);
            }
            break;
            
        case 'calculate_score':
            if (isset($_GET['user_id']) && isset($_GET['job_id'])) {
                $userId = (int)$_GET['user_id'];
                $jobId = (int)$_GET['job_id'];
                
                $score = $matching->calculateCompatibilityScore($userId, $jobId);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'score' => $score
                ]);
            }
            break;
            
        case 'update_scores':
            if (isset($_GET['user_id'])) {
                $userId = (int)$_GET['user_id'];
                $matching->updateAllCompatibilityScores($userId);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Compatibility scores updated successfully'
                ]);
            }
            break;
    }
}
?>
