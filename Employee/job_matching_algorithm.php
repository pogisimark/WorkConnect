<?php
// Job Matching Algorithm for WorkConnect
// This file contains the core matching logic for recommending jobs to job seekers
require_once 'ai_job_matcher.php';

class JobMatchingAlgorithm {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Calculate compatibility score between a job seeker and job posting
     * Returns a score from 0-100
     */
    public function calculateCompatibilityScore($userId, $jobPostingId) {
        $breakdown = $this->calculateDetailedCompatibility($userId, $jobPostingId);
        return $breakdown['total_score'];
    }
    
    /**
     * Calculate detailed compatibility breakdown with individual factor scores
     * Returns array with total_score and individual factor scores
     */
    public function calculateDetailedCompatibility($userId, $jobPostingId) {
        // Get job seeker data
        $jobSeeker = $this->getJobSeekerData($userId);
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
                'matched_occupations' => []
            ];
        }
        
        // Get job posting data
        $jobPosting = $this->getJobPostingData($jobPostingId);
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
                'matched_occupations' => []
            ];
        }
        
        // Calculate different matching factors with detailed info
        $skillResult = $this->calculateSkillMatch($jobSeeker, $jobPosting);
        $locationResult = $this->calculateLocationMatch($jobSeeker, $jobPosting);
        $occupationResult = $this->calculateOccupationMatch($jobSeeker, $jobPosting);
        $experienceScore = $this->calculateExperienceMatch($jobSeeker, $jobPosting);
        $salaryScore = $this->calculateSalaryMatch($jobSeeker, $jobPosting);
        $jobTypeScore = $this->calculateJobTypeMatch($jobSeeker, $jobPosting);
        
        // Extract scores and details
        $skillScore = is_array($skillResult) ? $skillResult['score'] : $skillResult;
        $locationScore = is_array($locationResult) ? $locationResult['score'] : $locationResult;
        $occupationScore = is_array($occupationResult) ? $occupationResult['score'] : $occupationResult;
        $matchedSkills = is_array($skillResult) ? ($skillResult['matched_skills'] ?? []) : [];
        $matchedLocations = is_array($locationResult) ? ($locationResult['matched_locations'] ?? []) : [];
        $matchedOccupations = is_array($occupationResult) ? ($occupationResult['matched_occupations'] ?? []) : [];
        
        // Check if skills are missing (n/a case)
        $hasSkills = is_array($skillResult) && !empty($skillResult['total_skills']) && $skillResult['total_skills'] > 0;
        
        // Weighted average of all scores
        // Adjust weights if skills are missing to prevent low total scores
        if (!$hasSkills) {
            // If no skills, redistribute weights to other factors
            $weights = [
                'skills' => 0.00,        // No weight if no skills
                'occupation' => 0.35,   // Increased importance
                'location' => 0.30,      // Increased importance
                'experience' => 0.20,   // Increased importance
                'salary' => 0.10,       // Increased importance
                'job_type' => 0.05      // Same
            ];
        } else {
            // Normal weights when skills are available
            $weights = [
                'skills' => 0.30,        // Most important factor
                'occupation' => 0.25,   // Important - preferred job match
                'location' => 0.20,      // Important for practical reasons
                'experience' => 0.15,   // Important for qualification
                'salary' => 0.05,       // Nice to have
                'job_type' => 0.05      // Nice to have match
            ];
        }
        
        $totalScore = ($skillScore * $weights['skills']) +
                     ($occupationScore * $weights['occupation']) +
                     ($locationScore * $weights['location']) +
                     ($experienceScore * $weights['experience']) +
                     ($salaryScore * $weights['salary']) +
                     ($jobTypeScore * $weights['job_type']);
        
        return [
            'total_score' => round($totalScore, 2),
            'skill_score' => round($skillScore, 2),
            'location_score' => round($locationScore, 2),
            'occupation_score' => round($occupationScore, 2),
            'experience_score' => round($experienceScore, 2),
            'salary_score' => round($salaryScore, 2),
            'job_type_score' => round($jobTypeScore, 2),
            'matched_skills' => $matchedSkills,
            'matched_locations' => $matchedLocations,
            'matched_occupations' => $matchedOccupations,
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
        if (!empty($jobSeeker['training_skills_1']) && strtolower(trim($jobSeeker['training_skills_1'])) !== 'n/a') {
            $skills1 = array_map('trim', explode(',', $jobSeeker['training_skills_1']));
            $jobSeekerSkills = array_merge($jobSeekerSkills, $skills1);
        }
        if (!empty($jobSeeker['training_skills_2']) && strtolower(trim($jobSeeker['training_skills_2'])) !== 'n/a') {
            $skills2 = array_map('trim', explode(',', $jobSeeker['training_skills_2']));
            $jobSeekerSkills = array_merge($jobSeekerSkills, $skills2);
        }
        if (!empty($jobSeeker['training_skills_3']) && strtolower(trim($jobSeeker['training_skills_3'])) !== 'n/a') {
            $skills3 = array_map('trim', explode(',', $jobSeeker['training_skills_3']));
            $jobSeekerSkills = array_merge($jobSeekerSkills, $skills3);
        }
        
        // Add skill_others (comma-separated)
        if (!empty($jobSeeker['skill_others']) && strtolower(trim($jobSeeker['skill_others'])) !== 'n/a') {
            $others = array_map('trim', explode(',', $jobSeeker['skill_others']));
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
            $skill = trim($skill);
            return !empty($skill) && strtolower($skill) !== 'n/a';
        });
        $jobSeekerSkills = array_unique(array_map('trim', $jobSeekerSkills));
        
        $jobRequirements = strtolower($jobPosting['requirements'] . ' ' . $jobPosting['description']);
        
        // If no skills or only "n/a", return 0% score
        if (empty($jobSeekerSkills)) {
            return ['score' => 0, 'matched_skills' => [], 'total_skills' => 0, 'matched_count' => 0];
        }
        
        $matchedSkills = [];
        $totalSkills = count($jobSeekerSkills);
        
        // Common skills to look for in job requirements
        $skillKeywords = [
            'php', 'mysql', 'javascript', 'html', 'css', 'python', 'java', 'c++',
            'communication', 'leadership', 'teamwork', 'problem solving',
            'marketing', 'sales', 'customer service', 'data analysis',
            'project management', 'time management', 'creativity',
            'microsoft office', 'excel', 'word', 'powerpoint',
            'photoshop', 'illustrator', 'adobe', 'design',
            'accounting', 'finance', 'budgeting', 'reporting'
        ];
        
        foreach ($jobSeekerSkills as $skill) {
            $skillLower = strtolower(trim($skill));
            if (empty($skillLower)) continue;
            
            // Direct skill match
            if (strpos($jobRequirements, $skillLower) !== false) {
                $matchedSkills[] = $skill;
                continue;
            }
            
            // Check for related keywords
            foreach ($skillKeywords as $keyword) {
                if (strpos($skillLower, $keyword) !== false && 
                    strpos($jobRequirements, $keyword) !== false) {
                    $matchedSkills[] = $skill;
                    break;
                }
            }
        }
        
        // Calculate percentage and apply curve for better distribution
        $matchedCount = count($matchedSkills);
        $skillMatchPercentage = ($matchedCount / $totalSkills) * 100;
        
        // Apply curve: 0-50% maps to 0-60, 50-100% maps to 60-100
        if ($skillMatchPercentage <= 50) {
            $score = ($skillMatchPercentage / 50) * 60;
        } else {
            $score = 60 + (($skillMatchPercentage - 50) / 50) * 40;
        }
        
        return [
            'score' => round($score, 2),
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
        
        if (!empty($jobSeeker['local1'])) {
            $preferredLocations[] = trim($jobSeeker['local1']);
        }
        if (!empty($jobSeeker['local2'])) {
            $preferredLocations[] = trim($jobSeeker['local2']);
        }
        if (!empty($jobSeeker['local3'])) {
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
        
        $jobLocation = $jobPosting['location'];
        
        if (empty($preferredLocations)) {
            return ['score' => 70, 'matched_locations' => []]; // Default score if no location preferences
        }
        
        // Use AI-powered location matching with proximity
        return AIJobMatcher::matchLocationWithProximity($preferredLocations, $jobLocation);
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
        
        if (!empty($jobSeeker['occupation1'])) {
            $preferredOccupations[] = trim($jobSeeker['occupation1']);
        }
        if (!empty($jobSeeker['occupation2'])) {
            $preferredOccupations[] = trim($jobSeeker['occupation2']);
        }
        if (!empty($jobSeeker['occupation3'])) {
            $preferredOccupations[] = trim($jobSeeker['occupation3']);
        }
        
        $jobTitle = $jobPosting['title'];
        $jobDescription = strtolower($jobPosting['description'] . ' ' . $jobPosting['requirements']);
        
        if (empty($preferredOccupations)) {
            return ['score' => 50, 'matched_occupations' => []]; // Default score if no occupation preferences
        }
        
        // Use AI-powered occupation matching (handles "any" and semantic similarity)
        $result = AIJobMatcher::matchOccupationWithAI($preferredOccupations, $jobTitle);
        
        // Also check job description for additional matches
        foreach ($preferredOccupations as $prefOcc) {
            $prefOcc = trim($prefOcc);
            if (empty($prefOcc) || strtolower($prefOcc) === 'n/a') {
                continue;
            }
            
            // If not already matched, check description
            if (!in_array($prefOcc, $result['matched_occupations']) && 
                strpos($jobDescription, strtolower($prefOcc)) !== false) {
                $result['matched_occupations'][] = $prefOcc;
                if ($result['score'] < 70) {
                    $result['score'] = 70; // Boost score if found in description
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
            return 80; // Default score if no job type preferences
        }
        
        // Check for exact match
        foreach ($preferredJobTypes as $preferredType) {
            if (strtolower($preferredType) === strtolower($jobType)) {
                return 100; // Perfect match
            }
        }
        
        // If user selected both fulltime and parttime, give partial score for any job type
        if (count($preferredJobTypes) >= 2) {
            return 75; // User is flexible, give good score
        }
        
        return 50; // No job type match
    }
    
    /**
     * Get recommended jobs for a user
     * Only returns jobs that meet minimum compatibility threshold (default 50%)
     */
    public function getRecommendedJobs($userId, $limit = 20, $minScore = 50) {
        // Get all active jobs
        $stmt = $this->conn->prepare("
            SELECT jp.*, 
                   COALESCE(jae.compatibility_score, 0) as compatibility_score,
                   jae.applied_date,
                   CASE WHEN jae.id IS NOT NULL THEN 1 ELSE 0 END as already_applied
            FROM job_postings jp
            LEFT JOIN job_applications_extended jae ON jp.id = jae.job_posting_id AND jae.jobseeker_id = (
                SELECT id FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1
            )
            WHERE jp.status = 'Active'
            ORDER BY jp.created_at DESC
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $allJobs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Calculate compatibility scores and detailed breakdown for all jobs
        $jobsWithScores = [];
        foreach ($allJobs as $job) {
            // Calculate detailed compatibility
            $breakdown = $this->calculateDetailedCompatibility($userId, $job['id']);
            
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
