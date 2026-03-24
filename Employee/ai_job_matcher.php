<?php
/**
 * AI-Powered Job Matcher
 * Uses free AI services and intelligent matching algorithms
 * No expiration, free tier with limited but sufficient features
 */

class AIJobMatcher {
    private static $predefinedCoordinates = [
        // --- Major Cities & Regions ---
        'manila' => ['lat' => 14.5995, 'lon' => 120.9842],
        'quezon city' => ['lat' => 14.6760, 'lon' => 121.0437],
        'makati' => ['lat' => 14.5547, 'lon' => 121.0244],
        'taguig' => ['lat' => 14.5176, 'lon' => 121.0509],
        'pasig' => ['lat' => 14.5764, 'lon' => 121.0851],
        'mandaluyong' => ['lat' => 14.5794, 'lon' => 121.0359],
        'pasay' => ['lat' => 14.5378, 'lon' => 121.0014],
        'parañaque' => ['lat' => 14.4793, 'lon' => 121.0198],
        'las piñas' => ['lat' => 14.4445, 'lon' => 120.9939],
        'muntinlupa' => ['lat' => 14.4081, 'lon' => 121.0415],
        'marikina' => ['lat' => 14.6507, 'lon' => 121.1029],
        'valenzuela' => ['lat' => 14.6812, 'lon' => 120.9762],
        'caloocan' => ['lat' => 14.6416, 'lon' => 120.9762],
        'malabon' => ['lat' => 14.6681, 'lon' => 120.9461],
        'navotas' => ['lat' => 14.6732, 'lon' => 120.9350],
        'san juan' => ['lat' => 14.6019, 'lon' => 121.0355],
        'pateros' => ['lat' => 14.5454, 'lon' => 121.0687],

        // --- Bulacan (Expanded) ---
        'malolos' => ['lat' => 14.8423, 'lon' => 120.8121],
        'meycauayan' => ['lat' => 14.7342, 'lon' => 120.9571],
        'san jose del monte' => ['lat' => 14.8139, 'lon' => 121.0433],
        'baliuag' => ['lat' => 14.9547, 'lon' => 120.9014],
        'marilao' => ['lat' => 14.7578, 'lon' => 120.9583],
        'santa maria' => ['lat' => 14.8183, 'lon' => 120.9503],
        'bocaue' => ['lat' => 14.7950, 'lon' => 120.9258],
        'guiguinto' => ['lat' => 14.8361, 'lon' => 120.8803],
        'hagonoy' => ['lat' => 14.8361, 'lon' => 120.7317],
        'plaridel' => ['lat' => 14.8872, 'lon' => 120.8572],
        'pulilan' => ['lat' => 14.9011, 'lon' => 120.8522],
        'calumpit' => ['lat' => 14.9142, 'lon' => 120.7628],
        'bulacan' => ['lat' => 14.8781, 'lon' => 120.8834],

        // --- Other Major Cities ---
        'davao' => ['lat' => 7.1907, 'lon' => 125.4553],
        'cebu' => ['lat' => 10.3157, 'lon' => 123.8854],
        'zamboanga' => ['lat' => 6.9214, 'lon' => 122.0797],
        'antipolo' => ['lat' => 14.5845, 'lon' => 121.1754],
        'bacoor' => ['lat' => 14.4615, 'lon' => 120.9622],
        'dasmarinas' => ['lat' => 14.3294, 'lon' => 120.9367],
        'imus' => ['lat' => 14.4297, 'lon' => 120.9367],
        'iloilo' => ['lat' => 10.7202, 'lon' => 122.5621],
        'bacolod' => ['lat' => 10.6765, 'lon' => 122.9509],
        'cagayan de oro' => ['lat' => 8.4542, 'lon' => 124.6319],
        'lapu-lapu' => ['lat' => 10.3111, 'lon' => 123.9493],
        'angeles' => ['lat' => 15.1441, 'lon' => 120.5887],
        'general santos' => ['lat' => 6.1164, 'lon' => 125.1716],
        'baguio' => ['lat' => 16.4023, 'lon' => 120.5960],
        'butuan' => ['lat' => 8.9475, 'lon' => 125.5406],
        'iligan' => ['lat' => 8.2280, 'lon' => 124.2452],
        'tacloban' => ['lat' => 11.2433, 'lon' => 125.0042],
        'cotabato' => ['lat' => 7.2232, 'lon' => 124.2455],
        'batangas' => ['lat' => 13.7565, 'lon' => 121.0583],
        'san fernando' => ['lat' => 15.0286, 'lon' => 120.6898],
        'lucena' => ['lat' => 13.9413, 'lon' => 121.6150],
        'cabanatuan' => ['lat' => 15.4864, 'lon' => 120.9734],

        // --- Pampanga (Expanded) ---
        'san fernando' => ['lat' => 15.0286, 'lon' => 120.6898],
        'angeles' => ['lat' => 15.1441, 'lon' => 120.5887],
        'mabalacat' => ['lat' => 15.2150, 'lon' => 120.5750],
        'guagua' => ['lat' => 14.9658, 'lon' => 120.6306],
        'lubao' => ['lat' => 14.9403, 'lon' => 120.6017],

        // --- Cavite (Expanded) ---
        'bacoor' => ['lat' => 14.4615, 'lon' => 120.9622],
        'dasmarinas' => ['lat' => 14.3294, 'lon' => 120.9367],
        'imus' => ['lat' => 14.4297, 'lon' => 120.9367],
        'cavite city' => ['lat' => 14.4831, 'lon' => 120.9100],
        'tagaytay' => ['lat' => 14.1153, 'lon' => 120.9621],
        'general trias' => ['lat' => 14.3850, 'lon' => 120.8842],

        // --- Laguna (Expanded) ---
        'calamba' => ['lat' => 14.2123, 'lon' => 121.1633],
        'binan' => ['lat' => 14.3370, 'lon' => 121.0820],
        'santa rosa' => ['lat' => 14.3122, 'lon' => 121.1114],
        'san pedro' => ['lat' => 14.3528, 'lon' => 121.0536],
        'cabuyao' => ['lat' => 14.2783, 'lon' => 121.1256],
        'los banos' => ['lat' => 14.1683, 'lon' => 121.2433],

        // --- Provinces ---
        'pampanga' => ['lat' => 15.0833, 'lon' => 120.6667],
        'cavite' => ['lat' => 14.2467, 'lon' => 120.8804],
        'laguna' => ['lat' => 14.1446, 'lon' => 121.4792],
        'rizal' => ['lat' => 14.6231, 'lon' => 121.2597],
        'ilocos norte' => ['lat' => 18.1667, 'lon' => 120.6667],
        'ilocos sur' => ['lat' => 17.5667, 'lon' => 120.3833],
        'isabela' => ['lat' => 17.1475, 'lon' => 121.7742],
        'cagayan' => ['lat' => 17.9167, 'lon' => 121.6667],
        'zambales' => ['lat' => 15.3333, 'lon' => 120.0833],
        'tarlac' => ['lat' => 15.4833, 'lon' => 120.6000],
        'nueva ecija' => ['lat' => 15.4833, 'lon' => 120.9667],
        'aurora' => ['lat' => 15.7667, 'lon' => 121.5500],
        'quezon' => ['lat' => 13.9333, 'lon' => 121.6167],
        'camarines norte' => ['lat' => 14.1667, 'lon' => 122.7500],
        'camarines sur' => ['lat' => 13.6667, 'lon' => 123.1667],
        'albay' => ['lat' => 13.1333, 'lon' => 123.7333],
        'sorsogon' => ['lat' => 12.9667, 'lon' => 124.0000],
        'masbate' => ['lat' => 12.3667, 'lon' => 123.6167],
        'catanduanes' => ['lat' => 13.5833, 'lon' => 124.2333],
        'romblon' => ['lat' => 12.5833, 'lon' => 122.2667],
        'palawan' => ['lat' => 9.7500, 'lon' => 118.7500],
        'mindoro occidental' => ['lat' => 13.0000, 'lon' => 120.8333],
        'mindoro oriental' => ['lat' => 13.0000, 'lon' => 121.3333],
        'marinduque' => ['lat' => 13.4667, 'lon' => 121.8833],
        'aklan' => ['lat' => 11.6667, 'lon' => 122.3333],
        'antique' => ['lat' => 11.7500, 'lon' => 121.9500],
        'capiz' => ['lat' => 11.5833, 'lon' => 122.7500],
        'negros occidental' => ['lat' => 10.6667, 'lon' => 122.9500],
        'negros oriental' => ['lat' => 9.3000, 'lon' => 123.3000],
        'siquijor' => ['lat' => 9.2167, 'lon' => 123.5167],
        'bohol' => ['lat' => 9.6500, 'lon' => 123.8500],
        'leyte' => ['lat' => 11.2500, 'lon' => 124.5000],
        'southern leyte' => ['lat' => 10.3833, 'lon' => 124.9833],
        'biliran' => ['lat' => 11.5667, 'lon' => 124.4333],
        'samar' => ['lat' => 12.0000, 'lon' => 125.0000],
        'eastern samar' => ['lat' => 11.6667, 'lon' => 125.4333],
        'northern samar' => ['lat' => 12.5000, 'lon' => 124.5000],
        'zamboanga del norte' => ['lat' => 8.5000, 'lon' => 123.0000],
        'zamboanga del sur' => ['lat' => 7.8333, 'lon' => 123.2500],
        'zamboanga sibugay' => ['lat' => 7.7500, 'lon' => 122.7500],
        'bukidnon' => ['lat' => 8.1500, 'lon' => 125.1167],
        'camiguin' => ['lat' => 9.1667, 'lon' => 124.7167],
        'lanao del norte' => ['lat' => 8.2167, 'lon' => 124.2500],
        'lanao del sur' => ['lat' => 8.0000, 'lon' => 124.2500],
        'misamis occidental' => ['lat' => 8.4167, 'lon' => 123.7500],
        'misamis oriental' => ['lat' => 8.4833, 'lon' => 124.6500],
        'davao de oro' => ['lat' => 7.5000, 'lon' => 126.0000],
        'davao del norte' => ['lat' => 7.4500, 'lon' => 125.8000],
        'davao del sur' => ['lat' => 6.7500, 'lon' => 125.3333],
        'davao oriental' => ['lat' => 7.0833, 'lon' => 126.1667],
        'south cotabato' => ['lat' => 6.5000, 'lon' => 124.8333],
        'sultan kudarat' => ['lat' => 6.5833, 'lon' => 124.5833],
        'sarangani' => ['lat' => 6.1667, 'lon' => 125.2833],
        'agusan del norte' => ['lat' => 9.0000, 'lon' => 125.5000],
        'agusan del sur' => ['lat' => 8.9667, 'lon' => 125.7500],
        'dinagat islands' => ['lat' => 10.0000, 'lon' => 125.5833],
        'surigao del norte' => ['lat' => 9.7500, 'lon' => 125.5000],
        'surigao del sur' => ['lat' => 8.5000, 'lon' => 126.1667],
        'maguindanao' => ['lat' => 7.0000, 'lon' => 124.5000],
        'basilan' => ['lat' => 6.5833, 'lon' => 122.0000],
        'sulu' => ['lat' => 6.0000, 'lon' => 121.0000],
        'tawi-tawi' => ['lat' => 5.0833, 'lon' => 119.7500],
    ];

    private static function getCoordinatesForLocation($location) {
        if (empty($location) || strtolower($location) === 'n/a') return null;
        
        $location = strtolower(trim($location));
        
        // 1. Try exact match on full string (e.g., "Malolos, Bulacan")
        if (isset(self::$predefinedCoordinates[$location])) {
            return self::$predefinedCoordinates[$location];
        }

        // 2. Handle "City, Province" format
        if (strpos($location, ',') !== false) {
            $parts = array_map('trim', explode(',', $location));
            $city = $parts[0];
            $province = $parts[count($parts) - 1];
            
            // Try matching city specifically first for highest accuracy
            if (isset(self::$predefinedCoordinates[$city])) {
                return self::$predefinedCoordinates[$city];
            }
            
            // Fallback to matching province
            if (isset(self::$predefinedCoordinates[$province])) {
                return self::$predefinedCoordinates[$province];
            }
        }

        // 3. Fallback for partial matches (word search)
        foreach (self::$predefinedCoordinates as $key => $coords) {
            if (strpos($location, $key) !== false || strpos($key, $location) !== false) {
                return $coords;
            }
        }

        return null;
    }

    private static function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
               cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
               sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public static function calculateDistanceBasedLocationScore($preferredLocation, $jobLocation) {
        $prefCoords = self::getCoordinatesForLocation($preferredLocation);
        $jobCoords = self::getCoordinatesForLocation($jobLocation);

        if ($prefCoords && $jobCoords) {
            $distance = self::calculateHaversineDistance($prefCoords['lat'], $prefCoords['lon'], $jobCoords['lat'], $jobCoords['lon']);

            // Scoring based on distance
            if ($distance <= 10) return ['score' => 100, 'distance_km' => $distance]; // Very close
            if ($distance <= 25) return ['score' => 90, 'distance_km' => $distance]; // Nearby
            if ($distance <= 50) return ['score' => 80, 'distance_km' => $distance]; // Commutable
            if ($distance <= 100) return ['score' => 60, 'distance_km' => $distance]; // Far but possible
            if ($distance <= 200) return ['score' => 40, 'distance_km' => $distance]; // Very far
        }

        return ['score' => 0, 'distance_km' => null];
    }

    public static function calculateFuzzyOccupationScore($preferredOccupation, $jobTitle) {
        $pref = strtolower(trim($preferredOccupation));
        $job = strtolower(trim($jobTitle));

        if ($pref === $job) return 100;

        // Levenshtein distance for typo tolerance
        $distance = levenshtein($pref, $job);
        $len = max(strlen($pref), strlen($job));
        $similarity = $len > 0 ? (1 - $distance / $len) * 100 : 0;

        if ($similarity >= 80) return $similarity; // High similarity

        // Check for common words
        $prefWords = explode(' ', $pref);
        $jobWords = explode(' ', $job);
        $commonWords = array_intersect($prefWords, $jobWords);
        if (count($commonWords) > 0) {
            $wordSimilarity = (count($commonWords) / count(array_unique(array_merge($prefWords, $jobWords)))) * 100;
            return max($similarity, $wordSimilarity);
        }

        return $similarity;
    }

    public static function matchLocationWithProximity($preferredLocations, $jobLocation, $userCurrentLocation = null) {
        $matchedLocations = [];
        $maxScore = 0;
        $bestDistanceKm = null;
        $isNearbyCurrent = false;

        // 1. HIGHEST PRIORITY: Check proximity to User's Current Address (if provided)
        if ($userCurrentLocation && !empty($userCurrentLocation)) {
            $currentLocData = self::calculateDistanceBasedLocationScore($userCurrentLocation, $jobLocation);
            if ($currentLocData['score'] >= 80) { // If job is nearby current address
                $maxScore = $currentLocData['score'];
                $bestDistanceKm = $currentLocData['distance_km'];
                $isNearbyCurrent = true;
                // Boost score slightly for current address proximity
                $maxScore = min(100, $maxScore + 5); 
            }
        }

        // 2. SECOND PRIORITY: Check Preferred Locations (even if far)
        if (!empty($preferredLocations) && is_array($preferredLocations)) {
            foreach ($preferredLocations as $prefLoc) {
                $prefLoc = trim($prefLoc);
                if (empty($prefLoc) || strtolower($prefLoc) === 'n/a') continue;

                if (strtolower($prefLoc) === 'any') {
                    $maxScore = max($maxScore, 80);
                    $matchedLocations[] = 'Any';
                    continue;
                }

                $distanceData = self::calculateDistanceBasedLocationScore($prefLoc, $jobLocation);
                $score = $distanceData['score'];
                $distanceKm = $distanceData['distance_km'];

                // If it's a preferred location, we give it a high score even if distance is far
                // but if we already found a nearby current address, we keep the better score
                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestDistanceKm = $distanceKm;
                }

                if ($score >= 40) { // Relaxed threshold for preferred locations
                    $matchedLocations[] = $prefLoc;
                }
            }
        }

        return [
            'score' => $maxScore,
            'matched_locations' => array_unique($matchedLocations),
            'distance_km' => $bestDistanceKm,
            'is_nearby_current' => $isNearbyCurrent
        ];
    }


    public static function matchOccupationWithAI($preferredOccupations, $jobTitle) {
        if (empty($preferredOccupations) || !is_array($preferredOccupations)) {
            return ['score' => 50, 'matched_occupations' => []];
        }

        $matchedOccupations = [];
        $maxScore = 0;

        foreach ($preferredOccupations as $prefOcc) {
            $prefOcc = trim($prefOcc);
            if (empty($prefOcc) || strtolower($prefOcc) === 'n/a') continue;

            if (strtolower($prefOcc) === 'any') {
                $maxScore = max($maxScore, 80);
                $matchedOccupations[] = 'Any';
                continue;
            }

            $score = self::calculateFuzzyOccupationScore($prefOcc, $jobTitle);

            if ($score > $maxScore) {
                $maxScore = $score;
            }

            if ($score >= 70) { // Threshold for an occupation to be considered a match
                $matchedOccupations[] = $prefOcc;
            }
        }

        return [
            'score' => $maxScore,
            'matched_occupations' => array_unique($matchedOccupations)
        ];
    }

    /**
     * Semantic Map for AI matching.
     * Maps terms to related synonyms and broader categories.
     * Expanded to include Mid-Level and Senior-Level professional roles and skills.
     */
    private static $semanticMap = [
    // --- Information Technology & Software Engineering ---
    'programming' => ['software', 'developer', 'coder', 'coding', 'web developer', 'app developer', 'software engineer', 'it', 'technical', 'programmer', 'development', 'scripting', 'full stack', 'frontend', 'backend', 'devops', 'cloud architect', 'solutions architect', 'system administrator', 'cybersecurity analyst', 'database administrator', 'dba'],
    'software developer' => ['programming', 'coding', 'software engineer', 'web developer', 'it', 'technical', 'app developer', 'programmer', 'lead developer', 'senior engineer', 'cto', 'technical lead', 'software architect', 'scrum master', 'product manager'],
    'data analyst' => ['data', 'analytics', 'statistics', 'research', 'database', 'excel', 'reporting', 'data scientist', 'machine learning engineer', 'big data', 'business intelligence', 'bi analyst', 'data engineer', 'quantitative analyst'],
    'it' => ['computer', 'technical', 'software', 'network', 'system', 'information technology', 'support', 'it manager', 'cio', 'it director', 'network architect', 'security engineer', 'infrastructure lead'],
    'frontend developer' => ['html', 'css', 'javascript', 'react', 'vue', 'angular', 'ui', 'ux', 'responsive', 'bootstrap', 'web design', 'mobile app', 'web app'],
    'backend developer' => ['node.js', 'python', 'java', 'c#', 'php', 'sql', 'api', 'server', 'database', 'spring', 'django', 'express', 'architecture', 'microservices'],
    'devops engineer' => ['docker', 'kubernetes', 'ci/cd', 'aws', 'gcp', 'azure', 'terraform', 'ansible', 'jenkins', 'automation', 'infrastructure', 'monitoring'],
    'cloud engineer' => ['aws', 'azure', 'gcp', 'cloud architecture', 'virtualization', 'iaas', 'paas', 'saas', 'networking', 'security', 'scalability'],
    'cybersecurity analyst' => ['network security', 'firewall', 'penetration testing', 'risk management', 'incident response', 'ethical hacking', 'siem', 'encryption', 'threat analysis'],
    'data scientist' => ['python', 'r', 'statistics', 'machine learning', 'deep learning', 'data analysis', 'tensorflow', 'pytorch', 'ai', 'modeling', 'visualization'],
    'ai engineer' => ['machine learning', 'deep learning', 'tensorflow', 'pytorch', 'nlp', 'computer vision', 'ai models', 'algorithms', 'python', 'data engineering'],
    'mobile app developer' => ['android', 'ios', 'flutter', 'react native', 'swift', 'kotlin', 'app design', 'backend', 'api integration'],
    'game developer' => ['unity', 'unreal engine', 'c#', 'c++', 'game design', 'programming', 'art integration', 'debugging'],
    'ui designer' => ['figma', 'adobe xd', 'wireframes', 'prototyping', 'user experience', 'usability', 'interaction design'],
    'ux researcher' => ['usability testing', 'interviews', 'user research', 'wireframes', 'analytics', 'feedback', 'prototyping'],
    'software architect' => ['system design', 'scalability', 'microservices', 'software engineering', 'cloud', 'devops', 'design patterns'],
    'technical lead' => ['team management', 'code review', 'architecture', 'project planning', 'agile', 'scrum', 'mentoring'],
    'qa engineer' => ['testing', 'automation', 'selenium', 'cypress', 'manual testing', 'quality assurance', 'bug tracking'],

    // --- Finance, Accounting & Management ---
    'accountant' => ['accounting', 'bookkeeper', 'finance', 'financial', 'audit', 'tax', 'cpa', 'ledger', 'payroll', 'billing', 'financial controller', 'finance manager', 'cfo', 'treasurer', 'internal auditor', 'budget analyst'],
    'finance' => ['financial', 'investment banker', 'wealth manager', 'portfolio manager', 'actuary', 'risk manager', 'compliance officer', 'asset management', 'equity analyst'],
    'financial analyst' => ['budgeting', 'forecasting', 'investment', 'reporting', 'analysis', 'financial modeling', 'valuation', 'data', 'excel'],
    'auditor' => ['audit', 'compliance', 'internal control', 'risk assessment', 'financial reporting', 'sox', 'fraud detection'],
    'tax consultant' => ['tax', 'compliance', 'corporate tax', 'personal tax', 'irs', 'filing', 'advisory'],
    'management' => ['manager', 'director', 'vp', 'ceo', 'executive', 'operations manager', 'general manager', 'strategy', 'project manager', 'pmp', 'program manager', 'business analyst'],
    'project manager' => ['planning', 'execution', 'budgeting', 'risk management', 'stakeholder management', 'agile', 'scrum', 'leadership'],
    'operations manager' => ['process improvement', 'efficiency', 'team management', 'logistics', 'strategy', 'resource allocation'],
    'business analyst' => ['requirements gathering', 'data analysis', 'process improvement', 'solution design', 'documentation', 'stakeholder management'],

    // --- Healthcare & Medical Professional ---
    'nurse' => ['healthcare', 'medical', 'patient care', 'clinical', 'hospital', 'clinic', 'registered nurse', 'rn', 'nurse practitioner', 'head nurse', 'clinical supervisor', 'nursing director'],
    'doctor' => ['physician', 'surgeon', 'specialist', 'medical director', 'resident', 'consultant', 'pediatrician', 'cardiologist', 'neurologist', 'general practitioner', 'gp'],
    'dentist' => ['oral health', 'extraction', 'root canal', 'cosmetic dentistry', 'preventive care', 'diagnosis', 'patient education'],
    'pharmacist' => ['medications', 'dispensing', 'drug interactions', 'counseling', 'pharmacy', 'clinical trials', 'prescriptions', 'healthcare'],
    'physical therapist' => ['rehabilitation', 'mobility', 'exercises', 'patient care', 'therapy', 'injury recovery', 'assessment', 'treatment plan'],
    'radiologist' => ['x-ray', 'mri', 'ct', 'diagnostic imaging', 'patient care', 'medical imaging', 'interpretation', 'radiation safety'],
    'psychologist' => ['counseling', 'therapy', 'assessment', 'mental health', 'behavioral analysis', 'patient care', 'clinical'],
    'surgeon' => ['surgery', 'patient care', 'operating room', 'diagnosis', 'specialist', 'medical procedures', 'anesthesia'],
    'nutritionist' => ['diet', 'nutrition', 'meal planning', 'healthcare', 'wellness', 'consulting', 'assessment'],

    // --- Creative, Design & Marketing ---
    'graphic designer' => ['design', 'artist', 'creative', 'photoshop', 'illustrator', 'visual', 'multimedia', 'layout', 'art director', 'creative director', 'ui designer', 'ux designer', 'product designer', 'motion graphics', 'brand strategist'],
    'motion graphics designer' => ['after effects', 'premiere', 'animation', 'storytelling', 'editing', 'visual effects', '3d', '2d', 'composition'],
    'photographer' => ['shooting', 'lighting', 'editing', 'photoshop', 'portrait', 'composition', 'landscape', 'studio', 'camera settings'],
    'copywriter' => ['content creation', 'seo', 'advertising', 'marketing', 'branding', 'social media', 'editing', 'writing', 'campaigns'],
    'digital marketer' => ['seo', 'sem', 'ads', 'social media', 'analytics', 'content marketing', 'email campaigns', 'strategy', 'growth hacking'],
    'brand manager' => ['strategy', 'marketing', 'positioning', 'branding', 'campaigns', 'market research', 'pr', 'communications'],

    // --- Skilled Trades & Technical ---
    'electrician' => ['electrical', 'wiring', 'circuit', 'power', 'maintenance', 'technical', 'master electrician', 'electrical engineer', 'grid operator', 'instrumentation technician'],
    'mechanic' => ['automotive', 'auto', 'vehicle', 'repair', 'maintenance', 'engine', 'aircraft mechanic', 'heavy equipment mechanic', 'diesel technician', 'service manager'],
    'plumber' => ['plumbing', 'pipe', 'water', 'maintenance', 'repair', 'master plumber', 'pipefitter', 'hvac technician', 'steamfitter'],
    'carpenter' => ['carpentry', 'wood', 'construction', 'furniture', 'building', 'master carpenter', 'cabinet maker', 'framing lead', 'site foreman'],
    'driver' => ['driving', 'delivery', 'transport', 'logistics', 'vehicle', 'chauffeur', 'truck', 'van', 'rider', 'logistics manager', 'fleet manager', 'supply chain specialist', 'heavy truck driver'],

    // --- Emerging & Modern IT Roles ---
    'ai product manager' => ['machine learning', 'ai models', 'strategy', 'roadmap', 'stakeholders', 'product development', 'analytics'],
    'data engineer' => ['etl', 'sql', 'big data', 'python', 'hadoop', 'spark', 'data pipelines', 'etl jobs', 'data warehouses'],
    'blockchain developer' => ['solidity', 'smart contracts', 'ethereum', 'web3', 'dapps', 'cryptography', 'tokens', 'decentralized apps'],
    'esports coach' => ['gaming strategy', 'team management', 'analytics', 'communication', 'training', 'tournament', 'performance analysis'],
    'game designer' => ['level design', 'game mechanics', 'storytelling', 'unity', 'unreal', 'playtesting', 'prototyping', 'design documentation'],
    'devops architect' => ['ci/cd', 'docker', 'kubernetes', 'cloud', 'automation', 'infrastructure design', 'monitoring', 'scalability'],
    'cloud security engineer' => ['aws', 'azure', 'gcp', 'security', 'compliance', 'network', 'firewall', 'vulnerability assessment', 'penetration testing'],
    'machine learning engineer' => ['python', 'tensorflow', 'pytorch', 'nlp', 'computer vision', 'data preprocessing', 'model deployment', 'algorithms'],

    // --- Education & Research ---
    'teacher' => ['curriculum', 'lesson planning', 'teaching', 'training', 'assessment', 'classroom', 'education', 'student engagement', 'evaluation'],
    'professor' => ['lectures', 'research', 'thesis', 'academic', 'publishing', 'mentoring', 'curriculum development', 'student advising'],
    'tutor' => ['subject knowledge', 'teaching', 'exams', 'assessment', 'lesson planning', 'student guidance', 'online teaching'],
    'researcher' => ['experiments', 'lab', 'analysis', 'scientific', 'data collection', 'reporting', 'publication', 'statistics', 'evaluation'],
    'academic advisor' => ['mentoring', 'curriculum', 'guidance', 'student support', 'counseling', 'planning', 'education'],
    'curriculum developer' => ['lesson planning', 'learning outcomes', 'education', 'training', 'assessment', 'content creation', 'instructional design'],
    'education consultant' => ['strategy', 'training', 'assessment', 'curriculum', 'advisory', 'planning', 'school improvement'],

    // --- Sales & Business Development ---
    'sales executive' => ['selling', 'prospecting', 'crm', 'business development', 'account management', 'negotiation', 'strategy', 'retail'],
    'account manager' => ['client management', 'relationship', 'retention', 'sales', 'negotiation', 'crm', 'strategy', 'upselling'],
    'business development manager' => ['market research', 'lead generation', 'strategy', 'sales', 'pitching', 'crm', 'networking', 'partnerships'],
    'retail manager' => ['store operations', 'customer service', 'inventory', 'sales', 'staff management', 'visual merchandising'],
    'customer service representative' => ['customer support', 'communication', 'problem solving', 'crm', 'tickets', 'client satisfaction'],
    'sales director' => ['strategy', 'sales growth', 'team leadership', 'business development', 'crm', 'market analysis'],
    'key account manager' => ['client relations', 'retention', 'negotiation', 'strategy', 'sales', 'business development'],

    // --- Finance & Management Specialized Roles ---
    'cfo' => ['financial strategy', 'budgeting', 'investment', 'risk management', 'reporting', 'executive decision making'],
    'finance manager' => ['budgeting', 'financial planning', 'reporting', 'team management', 'forecasting', 'risk management'],
    'internal auditor' => ['audit', 'compliance', 'risk management', 'reporting', 'internal control', 'sox'],
    'treasurer' => ['cash management', 'risk management', 'investments', 'financial reporting', 'budgeting'],
    'budget analyst' => ['budget planning', 'forecasting', 'financial analysis', 'reporting', 'cost management'],
    'compliance officer' => ['regulations', 'risk assessment', 'auditing', 'policy enforcement', 'reporting'],
    'risk manager' => ['risk analysis', 'mitigation', 'compliance', 'strategy', 'financial modeling', 'assessment'],
    'portfolio manager' => ['investment', 'asset allocation', 'risk management', 'financial analysis', 'client reporting'],

    // --- Human Resources & Legal ---
    'hr manager' => ['human resources', 'recruitment', 'talent acquisition', 'employee relations', 'performance management', 'training'],
    'hr director' => ['strategy', 'policy', 'recruitment', 'team management', 'talent development', 'employee engagement'],
    'recruiter' => ['talent acquisition', 'interviewing', 'sourcing', 'screening', 'job postings', 'candidate management'],
    'legal counsel' => ['law', 'contracts', 'compliance', 'litigation', 'advisory', 'legal research'],
    'paralegal' => ['legal research', 'documentation', 'contracts', 'case preparation', 'client support'],
    'compliance analyst' => ['regulations', 'policy', 'auditing', 'risk management', 'reporting'],
    'contract manager' => ['contracts', 'negotiation', 'compliance', 'legal', 'risk management', 'review'],

    // --- Creative, Design & Marketing Specialties ---
    'ui/ux designer' => ['wireframes', 'prototyping', 'usability', 'interface design', 'user experience', 'figma', 'adobe xd', 'interaction design'],
    'visual designer' => ['branding', 'graphic design', 'photoshop', 'illustrator', 'creative', 'layout', 'composition', 'typography'],
    'motion designer' => ['after effects', 'animation', 'premiere', '2d', '3d', 'storytelling', 'visual effects', 'video editing'],
    'brand strategist' => ['branding', 'market research', 'strategy', 'positioning', 'campaigns', 'creative direction', 'advertising'],
    'content creator' => ['video', 'blogging', 'social media', 'photography', 'editing', 'copywriting', 'creative', 'digital media'],
    'graphic illustrator' => ['illustration', 'drawing', 'photoshop', 'adobe illustrator', 'visual storytelling', 'digital art'],
    'advertising specialist' => ['marketing', 'campaigns', 'media', 'ads', 'seo', 'branding', 'strategy', 'digital marketing'],
    'seo specialist' => ['search engine optimization', 'analytics', 'content strategy', 'keywords', 'google', 'ranking', 'seo audits'],
    'social media manager' => ['content creation', 'social media', 'analytics', 'engagement', 'marketing', 'strategy', 'campaigns'],

    // --- Healthcare & Medical Specialties ---
    'cardiologist' => ['heart', 'cardiology', 'diagnosis', 'patient care', 'treatment', 'specialist', 'hospital'],
    'neurologist' => ['nervous system', 'diagnosis', 'treatment', 'patient care', 'hospital', 'specialist', 'consulting'],
    'pediatrician' => ['children', 'healthcare', 'patient care', 'diagnosis', 'treatment', 'clinic', 'consultation'],
    'anesthesiologist' => ['anesthesia', 'surgery', 'patient monitoring', 'pain management', 'hospital', 'critical care'],
    'radiologic technologist' => ['x-ray', 'mri', 'ct', 'imaging', 'diagnostic', 'hospital', 'patient care'],
    'lab technician' => ['laboratory', 'samples', 'analysis', 'microscope', 'testing', 'results', 'reporting'],
    'therapist' => ['physical therapy', 'mental health', 'counseling', 'rehabilitation', 'patient care', 'exercise plans'],
    'occupational therapist' => ['rehabilitation', 'patient care', 'therapy', 'activities', 'assessment', 'mobility', 'wellness'],
    'speech therapist' => ['communication', 'therapy', 'rehabilitation', 'patient care', 'assessment', 'speech', 'language'],
    'nutritionist' => ['diet', 'meal planning', 'healthcare', 'consulting', 'wellness', 'patient education', 'assessment'],

    // --- Skilled Trades & Technical (Advanced) ---
    'electrician' => ['electrical', 'wiring', 'circuit', 'power', 'maintenance', 'installation', 'troubleshooting', 'safety', 'automation'],
    'master electrician' => ['electrical', 'installation', 'maintenance', 'wiring', 'inspection', 'circuit', 'troubleshooting', 'safety'],
    'mechanic' => ['automotive', 'repair', 'maintenance', 'engine', 'diagnostics', 'diesel', 'aircraft', 'heavy equipment', 'tools'],
    'auto body technician' => ['repair', 'paint', 'panel', 'dent removal', 'vehicle', 'collision', 'maintenance', 'spray'],
    'plumber' => ['plumbing', 'pipe', 'water', 'maintenance', 'repair', 'installation', 'hvac', 'drainage', 'pipefitter'],
    'hvac technician' => ['heating', 'cooling', 'ventilation', 'installation', 'repair', 'maintenance', 'air conditioning', 'refrigeration'],
    'carpenter' => ['woodworking', 'furniture', 'construction', 'framing', 'joinery', 'tool usage', 'cabinet making', 'site management'],
    'welder' => ['welding', 'mig', 'tig', 'fabrication', 'metalwork', 'blueprints', 'cutting', 'assembly', 'safety'],
    'driver' => ['driving', 'delivery', 'logistics', 'vehicle', 'truck', 'van', 'fleet management', 'route planning'],
    'heavy truck driver' => ['driving', 'logistics', 'transport', 'fleet', 'delivery', 'routes', 'cargo', 'safety'],
    'construction supervisor' => ['project management', 'site supervision', 'construction', 'team management', 'safety', 'planning'],
    'industrial technician' => ['machinery', 'maintenance', 'repair', 'automation', 'factory', 'equipment', 'troubleshooting'],
    'instrumentation technician' => ['sensors', 'measurement', 'automation', 'maintenance', 'calibration', 'electrical', 'technical'],
    
    // --- Niche IT & Tech Roles ---
    'blockchain architect' => ['smart contracts', 'ethereum', 'decentralized', 'architecture', 'tokens', 'web3', 'scalability', 'design'],
    'devops consultant' => ['ci/cd', 'docker', 'kubernetes', 'automation', 'cloud', 'monitoring', 'infrastructure', 'strategy'],
    'site reliability engineer' => ['sre', 'monitoring', 'automation', 'ci/cd', 'scalability', 'python', 'infrastructure', 'uptime'],
    'cloud solutions architect' => ['aws', 'azure', 'gcp', 'infrastructure', 'cloud design', 'devops', 'security', 'solutioning'],
    'data visualization specialist' => ['tableau', 'power bi', 'visualization', 'data', 'analytics', 'dashboard', 'insights', 'reporting'],
    'nlp engineer' => ['python', 'tensorflow', 'pytorch', 'nlp', 'text analytics', 'language models', 'machine learning'],
    'computer vision engineer' => ['opencv', 'python', 'tensorflow', 'pytorch', 'image recognition', 'deep learning', 'cv models'],
    'qa automation engineer' => ['selenium', 'cypress', 'automation', 'testing', 'ci/cd', 'bug tracking', 'scripts'],
    'mobile ui/ux designer' => ['prototyping', 'wireframes', 'adobe xd', 'figma', 'user experience', 'app design', 'interface'],

    // --- Niche Finance & Business Roles ---
    'equity analyst' => ['stocks', 'financial modeling', 'valuation', 'research', 'investment', 'portfolio', 'analysis', 'reporting'],
    'wealth manager' => ['portfolio management', 'investment', 'client advisory', 'financial planning', 'risk management'],
    'actuary' => ['statistics', 'risk assessment', 'insurance', 'mathematics', 'modeling', 'financial analysis'],
    'risk analyst' => ['risk assessment', 'financial', 'data analysis', 'compliance', 'mitigation', 'reporting'],
    'portfolio analyst' => ['portfolio', 'analysis', 'investment', 'valuation', 'reporting', 'finance', 'excel'],
    'venture capitalist' => ['investment', 'startups', 'funding', 'valuation', 'portfolio', 'strategy', 'deal sourcing'],
    'business consultant' => ['strategy', 'process improvement', 'market research', 'stakeholder management', 'analytics'],
    'strategy analyst' => ['market research', 'data analysis', 'business strategy', 'recommendations', 'reporting'],
    'project coordinator' => ['planning', 'execution', 'communication', 'schedule', 'stakeholder', 'tracking'],

    // --- Niche Sales & Marketing Roles ---
    'growth hacker' => ['digital marketing', 'seo', 'analytics', 'social media', 'strategy', 'campaigns', 'user acquisition'],
    'ecommerce manager' => ['shopify', 'woocommerce', 'online sales', 'marketing', 'analytics', 'campaigns', 'strategy'],
    'seo content writer' => ['seo', 'content creation', 'keywords', 'digital marketing', 'blog', 'analytics', 'writing'],
    'social media strategist' => ['social media', 'campaigns', 'content planning', 'analytics', 'branding', 'engagement'],
    'brand ambassador' => ['marketing', 'promotion', 'events', 'social media', 'product knowledge', 'communication'],
    'advertising account executive' => ['campaigns', 'client relations', 'strategy', 'media', 'digital marketing', 'ads'],
    'public relations specialist' => ['communication', 'media', 'press', 'events', 'branding', 'strategy', 'writing'],
    'copy editor' => ['proofreading', 'content', 'grammar', 'editing', 'writing', 'publishing', 'style guide'],
    'creative director' => ['leadership', 'design', 'branding', 'visuals', 'strategy', 'marketing', 'team management'],

    // --- Emerging & Niche Education Roles ---
    'online tutor' => ['virtual', 'teaching', 'subject knowledge', 'exams', 'lesson planning', 'student engagement'],
    'instructional designer' => ['curriculum', 'elearning', 'lesson plans', 'training', 'education', 'content creation', 'assessment'],
    'academic researcher' => ['research', 'data analysis', 'publication', 'laboratory', 'scientific', 'statistics', 'grant writing'],
    'lab researcher' => ['experiments', 'data collection', 'scientific', 'analysis', 'reporting', 'lab safety'],
    'education analyst' => ['data', 'assessment', 'curriculum', 'evaluation', 'policy', 'recommendations', 'reporting'],

    // --- Miscellaneous Skilled Trades & Technical Roles ---
    'industrial electrician' => ['industrial', 'electrical', 'wiring', 'power', 'automation', 'maintenance', 'safety'],
    'heavy equipment operator' => ['crane', 'bulldozer', 'excavator', 'construction', 'safety', 'operation', 'transport'],
    'diesel mechanic' => ['diesel', 'vehicles', 'repair', 'maintenance', 'troubleshooting', 'engines'],
    'pipefitter' => ['pipes', 'plumbing', 'welding', 'installation', 'maintenance', 'water systems'],
    'steamfitter' => ['steam', 'pipes', 'installation', 'maintenance', 'safety', 'repair'],
    'cabinet maker' => ['woodworking', 'furniture', 'design', 'joinery', 'tools', 'precision', 'craftsmanship'],
    'framing carpenter' => ['construction', 'wood', 'frames', 'blueprints', 'tools', 'site management'],
    'fleet manager' => ['vehicles', 'logistics', 'routes', 'drivers', 'planning', 'maintenance', 'fleet tracking'],
    'logistics coordinator' => ['shipping', 'inventory', 'tracking', 'supply chain', 'warehouse', 'planning', 'coordination'],

    // --- Creative & Arts ---
    'animator' => ['2d', '3d', 'animation', 'after effects', 'maya', 'blender', 'storyboarding', 'motion graphics', 'visual storytelling'],
    'illustrator' => ['drawing', 'sketching', 'digital art', 'photoshop', 'adobe illustrator', 'storytelling', 'creative'],
    'fashion designer' => ['clothing', 'design', 'textiles', 'patterns', 'sketching', 'fashion trends', 'production'],
    'interior designer' => ['spaces', 'furniture', 'aesthetics', 'layout', 'decor', 'color theory', '3d modeling', 'client consultation'],
    'photographer' => ['lighting', 'composition', 'camera', 'editing', 'photoshop', 'portrait', 'event photography', 'studio'],
    'video editor' => ['premiere', 'after effects', 'storytelling', 'cutting', 'color grading', 'motion graphics', 'sound editing'],
    'sound designer' => ['audio', 'editing', 'mixing', 'sound effects', 'pro tools', 'composition', 'recording', 'post-production'],
    'voice actor' => ['voice', 'recording', 'studio', 'character', 'dialogue', 'narration', 'editing', 'performance'],

    // --- Healthcare & Medical Specialties (continued) ---
    'oncologist' => ['cancer', 'treatment', 'patient care', 'diagnosis', 'radiation', 'chemotherapy', 'hospital', 'specialist'],
    'dermatologist' => ['skin', 'treatment', 'patient care', 'diagnosis', 'clinic', 'cosmetic', 'healthcare'],
    'orthopedic surgeon' => ['surgery', 'bones', 'patient care', 'diagnosis', 'rehabilitation', 'hospital', 'specialist'],
    'urologist' => ['urinary', 'kidneys', 'patient care', 'diagnosis', 'treatment', 'clinic', 'hospital'],
    'gynecologist' => ['women health', 'reproductive', 'patient care', 'diagnosis', 'clinic', 'treatment', 'hospital'],
    'anesthetist' => ['anesthesia', 'surgery', 'patient monitoring', 'critical care', 'hospital', 'pain management'],

    // --- Hospitality & Tourism ---
    'hotel manager' => ['operations', 'staff management', 'customer service', 'booking', 'hospitality', 'budgeting', 'strategy'],
    'chef' => ['cooking', 'kitchen', 'recipes', 'menu planning', 'food safety', 'culinary', 'team management'],
    'restaurant manager' => ['staff', 'operations', 'inventory', 'customer service', 'budgeting', 'hospitality'],
    'bartender' => ['mixology', 'customer service', 'drinks', 'bar operations', 'hospitality', 'speed', 'techniques'],
    'tour guide' => ['tourism', 'history', 'communication', 'customer service', 'navigation', 'languages', 'local knowledge'],
    'travel agent' => ['booking', 'itinerary', 'customer service', 'flights', 'hotels', 'tourism', 'planning'],

    // --- Transportation & Logistics ---
    'truck driver' => ['driving', 'routes', 'logistics', 'transportation', 'delivery', 'fleet management', 'safety'],
    'bus driver' => ['driving', 'passengers', 'safety', 'routes', 'transportation', 'schedule', 'vehicle maintenance'],
    'logistics manager' => ['shipping', 'inventory', 'transportation', 'planning', 'coordination', 'warehouse', 'supply chain'],
    'warehouse supervisor' => ['inventory', 'logistics', 'team management', 'operations', 'safety', 'planning', 'tracking'],
    'freight coordinator' => ['shipping', 'tracking', 'logistics', 'transportation', 'documentation', 'planning', 'coordination'],

    // --- Emerging & Remote Work Roles ---
    'remote customer support' => ['communication', 'crm', 'tickets', 'problem solving', 'email', 'chat', 'virtual assistance'],
    'virtual assistant' => ['calendar', 'emails', 'scheduling', 'customer support', 'research', 'documentation', 'task management'],
    'esports player' => ['gaming', 'teamplay', 'strategy', 'tournaments', 'communication', 'competition', 'skill'],
    'game streamer' => ['streaming', 'twitch', 'youtube', 'gaming', 'social media', 'engagement', 'entertainment'],
    'content strategist' => ['planning', 'content', 'social media', 'analytics', 'marketing', 'campaigns', 'digital'],
    'podcast producer' => ['audio', 'editing', 'recording', 'storytelling', 'content', 'distribution', 'marketing'],
    'ai trainer' => ['machine learning', 'data labeling', 'training', 'model improvement', 'ai', 'annotation', 'analysis'],
    'chatbot developer' => ['nlp', 'python', 'dialogflow', 'machine learning', 'conversation design', 'ai', 'integration'],

    // --- Miscellaneous Technical & Skilled Roles ---
    'surveying technician' => ['land', 'mapping', 'gps', 'measurements', 'construction', 'surveying', 'blueprints'],
    'civil drafter' => ['cad', 'autocad', 'blueprints', 'civil engineering', 'drafting', 'construction', 'technical drawings'],
    'safety officer' => ['occupational health', 'safety regulations', 'risk assessment', 'compliance', 'training', 'inspection'],
    'quality control inspector' => ['inspection', 'standards', 'production', 'testing', 'quality', 'reporting', 'compliance'],
    'environmental engineer' => ['sustainability', 'pollution', 'environment', 'engineering', 'compliance', 'analysis', 'reporting'],
    'geologist' => ['earth', 'rocks', 'soil', 'surveying', 'research', 'mapping', 'analysis'],

    // --- Popular Jobs in the Philippines ---
'call center agent' => ['customer service', 'communication', 'crm', 'inbound', 'outbound', 'problem solving', 'english proficiency', 'ticketing'],
'bpo specialist' => ['customer support', 'process outsourcing', 'crm', 'communication', 'call handling', 'multitasking', 'email support'],
'virtual assistant' => ['calendar management', 'emails', 'scheduling', 'data entry', 'customer service', 'research', 'administrative tasks'],
'content writer' => ['writing', 'seo', 'blog', 'copywriting', 'social media', 'editing', 'research'],
'freelance designer' => ['photoshop', 'illustrator', 'graphic design', 'branding', 'logo design', 'creative', 'adobe suite'],
'teacher' => ['lesson planning', 'teaching', 'education', 'student engagement', 'classroom management', 'assessment'],
'nurse' => ['patient care', 'hospital', 'clinical', 'healthcare', 'rn', 'medical', 'medication', 'monitoring'],
'doctor' => ['diagnosis', 'patient care', 'hospital', 'treatment', 'specialist', 'consultation', 'clinical'],
'engineer' => ['engineering', 'design', 'technical', 'construction', 'project management', 'problem solving'],
'construction worker' => ['building', 'manual labor', 'tools', 'safety', 'construction site', 'teamwork'],
'electrician' => ['wiring', 'circuit', 'power', 'installation', 'maintenance', 'troubleshooting'],
'mechanic' => ['repair', 'automotive', 'engine', 'maintenance', 'diagnostics', 'vehicles'],
'plumber' => ['pipes', 'installation', 'repair', 'water systems', 'maintenance'],
'security guard' => ['surveillance', 'patrol', 'safety', 'reporting', 'emergency response'],
'driver' => ['driving', 'transport', 'delivery', 'vehicle handling', 'logistics'],
'food service staff' => ['cooking', 'customer service', 'kitchen', 'cleaning', 'food preparation'],
'receptionist' => ['customer service', 'communication', 'front desk', 'phone handling', 'administrative tasks'],
'accounting clerk' => ['bookkeeping', 'ledger', 'billing', 'finance', 'accounting software', 'data entry'],
'cashier' => ['point of sale', 'customer service', 'transactions', 'money handling', 'accuracy', 'billing'],
'retail staff' => ['sales', 'customer service', 'inventory', 'merchandising', 'store operations'],
'call center team lead' => ['supervision', 'customer service', 'team management', 'communication', 'process monitoring'],
'it support specialist' => ['technical support', 'troubleshooting', 'hardware', 'software', 'networking', 'helpdesk'],
'graphic designer' => ['photoshop', 'illustrator', 'layout', 'visual communication', 'branding', 'creative'],
'social media manager' => ['content creation', 'social media', 'analytics', 'strategy', 'marketing'],
'marketing executive' => ['digital marketing', 'campaigns', 'branding', 'communication', 'strategy'],
'virtual call center trainer' => ['training', 'coaching', 'communication', 'customer service', 'process knowledge'],
'freelance web developer' => ['html', 'css', 'javascript', 'wordpress', 'react', 'php', 'backend', 'frontend'],
'accountant' => ['finance', 'bookkeeping', 'ledger', 'tax', 'financial reporting', 'cpa'],
'sales representative' => ['prospecting', 'client relations', 'negotiation', 'sales', 'crm', 'marketing'],
'photographer' => ['camera', 'lighting', 'editing', 'photoshop', 'shooting', 'composition'],
'event coordinator' => ['planning', 'organization', 'communication', 'budgeting', 'logistics'],
'call center trainer' => ['training', 'coaching', 'communication', 'customer service', 'call handling'],
'hr specialist' => ['recruitment', 'talent acquisition', 'employee relations', 'hr policies', 'onboarding'],
'logistics officer' => ['inventory', 'shipping', 'coordination', 'warehouse', 'planning', 'tracking'],

// --- Additional Popular Jobs in the Philippines ---
'medical technologist' => ['laboratory', 'testing', 'analysis', 'samples', 'diagnosis', 'reporting', 'hospital'],
'dental assistant' => ['dental', 'patient care', 'clinical', 'assistance', 'hygiene', 'equipment', 'support'],
'caregiver' => ['elderly care', 'patient assistance', 'medical support', 'home care', 'mobility assistance', 'feeding', 'companionship'],
'pharmacy assistant' => ['medications', 'dispensing', 'inventory', 'customer service', 'pharmacy', 'healthcare'],
'research assistant' => ['data collection', 'research', 'analysis', 'reporting', 'laboratory', 'documentation'],
'civil servant' => ['government', 'administration', 'policy', 'compliance', 'public service', 'documentation'],
'barangay secretary' => ['community', 'documentation', 'government', 'coordination', 'public service'],
'police officer' => ['law enforcement', 'patrolling', 'investigation', 'security', 'public safety', 'reporting'],
'firefighter' => ['emergency response', 'fire suppression', 'rescue', 'safety', 'public service', 'teamwork'],
'military personnel' => ['defense', 'training', 'strategy', 'discipline', 'teamwork', 'security'],
'fast food crew' => ['customer service', 'food preparation', 'cleaning', 'teamwork', 'speed', 'order handling'],
'barista' => ['coffee', 'customer service', 'beverages', 'preparation', 'barista machine', 'cleaning', 'presentation'],
'receptionist' => ['customer service', 'phone handling', 'communication', 'front desk', 'scheduling', 'administration'],
'store clerk' => ['sales', 'inventory', 'customer service', 'merchandising', 'checkout', 'stocking'],
'sales promoter' => ['marketing', 'promotion', 'customer interaction', 'product knowledge', 'sales', 'engagement'],
'call center quality analyst' => ['call monitoring', 'quality assurance', 'customer service', 'analytics', 'reporting', 'feedback'],
'team lead bpo' => ['supervision', 'team management', 'customer service', 'call monitoring', 'process knowledge', 'coaching'],
'finance officer' => ['accounting', 'budgeting', 'financial reporting', 'ledger', 'auditing', 'cash management'],
'treasury staff' => ['cash flow', 'banking', 'payments', 'accounting', 'financial reporting', 'risk assessment'],
'business process analyst' => ['workflow', 'process improvement', 'documentation', 'analytics', 'optimization', 'bpo'],
'marketing assistant' => ['campaigns', 'social media', 'branding', 'digital marketing', 'content creation', 'analytics'],
'seo analyst' => ['search engine optimization', 'keywords', 'analytics', 'content strategy', 'ranking', 'digital marketing'],
'graphic artist' => ['illustration', 'photoshop', 'branding', 'visuals', 'creative', 'layout', 'digital media'],
'web designer' => ['html', 'css', 'javascript', 'ux', 'ui', 'responsive design', 'wordpress', 'figma'],
'software tester' => ['testing', 'quality assurance', 'manual testing', 'automation', 'bug reporting', 'scripts'],
'it technician' => ['hardware', 'software', 'networking', 'troubleshooting', 'support', 'technical', 'installation'],
'helpdesk support' => ['customer support', 'technical support', 'troubleshooting', 'ticketing', 'communication', 'remote assistance'],
'project coordinator' => ['planning', 'communication', 'documentation', 'schedule', 'stakeholder management', 'coordination'],
'construction laborer' => ['manual labor', 'tools', 'building', 'safety', 'teamwork', 'construction site'],
'mason' => ['construction', 'cement', 'bricklaying', 'manual labor', 'teamwork', 'structure building'],
'welder' => ['metalwork', 'welding', 'tools', 'fabrication', 'construction', 'safety', 'precision'],
'heavy equipment operator' => ['crane', 'excavator', 'bulldozer', 'construction', 'operation', 'safety', 'transport'],
'farmer' => ['agriculture', 'crops', 'planting', 'harvesting', 'irrigation', 'farm equipment'],
'fisherman' => ['fishing', 'boats', 'nets', 'aquaculture', 'harvesting', 'marine knowledge'],
'delivery rider' => ['logistics', 'motorbike', 'delivery', 'route planning', 'customer service', 'time management'],
'courier' => ['delivery', 'logistics', 'package handling', 'time management', 'tracking', 'customer service'],
'package handler' => ['sorting', 'delivery', 'logistics', 'inventory', 'warehouse', 'teamwork'],
'warehouse staff' => ['inventory', 'packing', 'logistics', 'organization', 'loading', 'tracking'],
'hotel staff' => ['customer service', 'front desk', 'housekeeping', 'hospitality', 'guest relations'],
'front desk officer' => ['reception', 'customer service', 'communication', 'scheduling', 'administration'],
'event staff' => ['organization', 'setup', 'guest management', 'logistics', 'coordination'],
'driver/van operator' => ['driving', 'transport', 'routes', 'customer service', 'delivery', 'fleet management'],
'ride-hailing driver' => ['driving', 'gps', 'passenger service', 'navigation', 'communication'],
'cleaner/janitor' => ['cleaning', 'maintenance', 'sanitation', 'teamwork', 'organization'],
'security officer' => ['patrol', 'surveillance', 'public safety', 'reporting', 'emergency response', 'law enforcement'],

        // --- Very Common & Basic Jobs in the Philippines ---
'traffic enforcer' => ['traffic control', 'road safety', 'communication', 'patrol', 'coordination', 'law enforcement'],
'barangay tanod' => ['community safety', 'patrolling', 'coordination', 'reporting', 'local enforcement', 'public service'],
'scooter rider/delivery rider' => ['delivery', 'navigation', 'time management', 'motorbike', 'customer service'],
'street vendor' => ['sales', 'customer service', 'merchandising', 'pricing', 'inventory', 'cash handling'],
'market vendor' => ['sales', 'merchandising', 'customer service', 'product handling', 'pricing', 'stock management'],
'barangay clerk' => ['documentation', 'administration', 'public service', 'records', 'filing', 'communication'],
'construction helper' => ['manual labor', 'tools', 'construction site', 'teamwork', 'cleaning', 'material handling'],
'janitor' => ['cleaning', 'maintenance', 'sanitation', 'organization', 'safety', 'teamwork'],
'housekeeper' => ['cleaning', 'organization', 'maintenance', 'laundry', 'hospitality', 'attention to detail'],
'messenger/courier' => ['delivery', 'communication', 'time management', 'tracking', 'documentation'],
'security guard (basic)' => ['patrol', 'surveillance', 'reporting', 'safety', 'access control', 'emergency response'],
'cashier (basic)' => ['point of sale', 'transactions', 'customer service', 'accuracy', 'money handling'],
'restaurant crew' => ['customer service', 'food preparation', 'cleaning', 'teamwork', 'order handling'],
'fast food cashier' => ['point of sale', 'order taking', 'customer service', 'speed', 'accuracy', 'cash handling'],
'delivery boy' => ['delivery', 'navigation', 'route planning', 'customer service', 'timeliness'],
'bakery assistant' => ['baking', 'customer service', 'cleaning', 'food preparation', 'inventory'],
'fish vendor' => ['sales', 'inventory', 'customer service', 'market knowledge', 'pricing', 'handling'],
'vegetable vendor' => ['sales', 'market knowledge', 'inventory', 'customer service', 'pricing', 'stocking'],
'tricycle driver' => ['driving', 'vehicle maintenance', 'navigation', 'passenger safety', 'time management'],
'jeepney driver' => ['driving', 'navigation', 'passenger safety', 'vehicle maintenance', 'routes'],
'pedicab driver' => ['driving', 'passenger service', 'navigation', 'safety', 'local knowledge'],
'bus conductor' => ['ticketing', 'customer service', 'passenger coordination', 'communication', 'organization'],
'porter' => ['carrying', 'assisting', 'customer service', 'physical strength', 'coordination', 'organization'],
'lab assistant' => ['testing', 'samples', 'analysis', 'documentation', 'assistance', 'lab equipment'],
'fisherman assistant' => ['boat handling', 'nets', 'harvesting', 'teamwork', 'manual labor', 'aquaculture'],
'agricultural laborer' => ['planting', 'harvesting', 'irrigation', 'manual labor', 'farm tools', 'teamwork'],
'petrol station attendant' => ['customer service', 'fuel handling', 'cash handling', 'safety', 'maintenance'],
'driver/helper' => ['driving', 'loading/unloading', 'navigation', 'logistics', 'vehicle maintenance'],
'motorcycle mechanic' => ['repair', 'maintenance', 'diagnostics', 'vehicles', 'tools', 'manual skills'],
'street cleaner' => ['cleaning', 'maintenance', 'sweeping', 'sanitation', 'teamwork'],
'water delivery staff' => ['delivery', 'heavy lifting', 'time management', 'customer service'],
'ice cream vendor' => ['sales', 'customer service', 'merchandising', 'pricing', 'stock handling'],
'bagger/cashier assistant' => ['customer service', 'checkout assistance', 'bagging', 'teamwork', 'speed'],
'tourist guide assistant' => ['communication', 'coordination', 'guest service', 'local knowledge', 'tour assistance'],
'swimming instructor assistant' => ['water safety', 'training', 'supervision', 'communication', 'first aid'],
'sports coach assistant' => ['training', 'coordination', 'physical fitness', 'teamwork', 'motivation'],
'massage therapist assistant' => ['customer service', 'massage', 'hygiene', 'sanitation', 'client assistance'],
'retail shelf stocker' => ['inventory', 'merchandising', 'stocking', 'organization', 'accuracy'],
'cash in transit staff' => ['security', 'cash handling', 'transportation', 'reporting', 'coordination'],
// --- Additional Philippine Job Types (Not Previously Listed) ---
'elections officer' => ['voter registration', 'polling', 'coordination', 'public service', 'documentation', 'communication'],
'court clerk' => ['documentation', 'filing', 'case management', 'administration', 'record keeping', 'legal support'],
'social welfare officer' => ['community service', 'case management', 'public assistance', 'documentation', 'reporting', 'counseling'],
'disaster response coordinator' => ['emergency response', 'coordination', 'planning', 'rescue', 'team management', 'logistics'],
'airport ground staff' => ['check-in', 'baggage handling', 'customer service', 'coordination', 'safety', 'operations'],
'cargo loader' => ['loading', 'unloading', 'inventory', 'logistics', 'heavy lifting', 'coordination', 'safety'],
'ship crew / seafarer' => ['navigation', 'vessel maintenance', 'teamwork', 'safety', 'emergency response', 'operations'],
'ticketing officer' => ['booking', 'customer service', 'cash handling', 'coordination', 'documentation'],
'port worker / dock laborer' => ['loading', 'unloading', 'inventory', 'coordination', 'heavy lifting', 'safety'],
'resort lifeguard' => ['swimming', 'rescue', 'first aid', 'supervision', 'safety', 'teamwork'],
'tour operator' => ['itinerary planning', 'customer service', 'coordination', 'booking', 'communication', 'tour management'],
'bellboy / bellhop' => ['luggage handling', 'guest service', 'coordination', 'hospitality', 'teamwork'],
'waiter / waitress' => ['customer service', 'food serving', 'communication', 'teamwork', 'order taking', 'hospitality'],
'room service staff' => ['customer service', 'food delivery', 'cleaning', 'hospitality', 'coordination', 'time management'],
'theme park attendant' => ['guest service', 'safety', 'operations', 'coordination', 'communication', 'teamwork'],
'mall promoter' => ['sales', 'customer engagement', 'marketing', 'product knowledge', 'communication', 'promotion'],
'sidewalk seller / peddler' => ['sales', 'merchandising', 'pricing', 'customer service', 'inventory', 'street marketing'],
'grocery bagger / packer' => ['packing', 'customer service', 'teamwork', 'organization', 'speed', 'accuracy'],
'roofing worker' => ['construction', 'manual labor', 'safety', 'teamwork', 'tools', 'installation'],
'scaffold builder' => ['construction', 'manual labor', 'safety', 'teamwork', 'installation', 'height work'],
'air conditioning technician' => ['installation', 'maintenance', 'troubleshooting', 'cooling systems', 'repair', 'tools'],
'bicycle repairman' => ['repair', 'maintenance', 'tools', 'mechanical', 'customer service', 'manual skills'],
'shoe repair / cobbler' => ['repair', 'tools', 'manual skills', 'leatherwork', 'customer service', 'craftsmanship'],
'tailor / seamstress' => ['sewing', 'garment making', 'alteration', 'patterns', 'fabric knowledge', 'manual skills'],
'midwife / maternity aide' => ['patient care', 'delivery assistance', 'monitoring', 'newborn care', 'healthcare', 'documentation'],
'nursing aide / caregiver' => ['patient care', 'assistance', 'mobility support', 'feeding', 'healthcare', 'monitoring'],
'dental hygienist' => ['dental care', 'cleaning', 'assistance', 'patient care', 'clinic', 'tools'],
'tutorial center assistant' => ['teaching support', 'lesson preparation', 'student coordination', 'documentation', 'administration'],
'librarian assistant' => ['cataloging', 'documentation', 'organization', 'customer service', 'research assistance'],
'daycare aide / preschool assistant' => ['childcare', 'supervision', 'lesson support', 'play activities', 'safety', 'education'],
'poultry farm worker' => ['feeding', 'cleaning', 'egg collection', 'manual labor', 'animal care', 'inventory'],
'rice / corn farm laborer' => ['planting', 'harvesting', 'manual labor', 'irrigation', 'crop maintenance', 'teamwork'],
'vegetable picker / packer' => ['harvesting', 'packing', 'manual labor', 'sorting', 'quality checking', 'teamwork'],
'fish pond caretaker' => ['feeding', 'harvesting', 'maintenance', 'manual labor', 'aquaculture', 'monitoring'],
'food processing plant worker' => ['packaging', 'cleaning', 'inspection', 'manual labor', 'production line', 'safety'],
'online English tutor' => ['teaching', 'communication', 'grammar', 'lesson planning', 'student engagement', 'virtual'],
'data entry freelancer' => ['typing', 'accuracy', 'computer', 'excel', 'documentation', 'speed'],
'online seller / reselling business' => ['ecommerce', 'sales', 'customer service', 'marketing', 'logistics', 'inventory'],
'content creator TikTok / YouTube' => ['video creation', 'editing', 'social media', 'storytelling', 'engagement', 'digital marketing'],
'sign painter / billboard installer' => ['painting', 'design', 'installation', 'tools', 'manual skills', 'coordination'],
'pet groomer / dog walker' => ['animal care', 'grooming', 'walking', 'customer service', 'time management'],
'motorcycle taxi driver (habal-habal)' => ['driving', 'passenger service', 'navigation', 'time management', 'safety'],
'recycling collector / junkshop worker' => ['collection', 'sorting', 'transport', 'manual labor', 'teamwork'],

// --- Government / Public Service ---
    'census enumerator' => ['data collection', 'survey', 'fieldwork', 'community', 'documentation', 'reporting'],
    'licensing officer' => ['documentation', 'compliance', 'processing', 'coordination', 'government', 'records'],
    'building inspector' => ['construction', 'inspection', 'safety', 'compliance', 'reporting', 'documentation'],
    'sanitation inspector' => ['health', 'safety', 'inspection', 'compliance', 'reporting', 'documentation'],
    'community development officer' => ['coordination', 'community service', 'planning', 'reporting', 'documentation'],
    'public health officer' => ['health', 'inspection', 'community service', 'awareness', 'reporting', 'coordination'],
    'disaster relief worker' => ['emergency response', 'coordination', 'logistics', 'teamwork', 'rescue', 'public service'],
    'registry officer' => ['documentation', 'records', 'administration', 'coordination', 'data entry', 'compliance'],
    'government clerk' => ['documentation', 'administration', 'record keeping', 'coordination', 'filing', 'office support'],
    'election poll worker' => ['coordination', 'voter assistance', 'documentation', 'communication', 'organization', 'public service'],
    
    // --- Education / Tutoring / Arts ---
    'math tutor' => ['teaching', 'math', 'lesson planning', 'problem solving', 'student engagement', 'assessment'],
    'english tutor' => ['teaching', 'grammar', 'lesson planning', 'communication', 'student engagement', 'writing'],
    'science tutor' => ['teaching', 'experiment', 'lesson planning', 'research', 'student engagement', 'problem solving'],
    'music teacher' => ['instrument', 'teaching', 'practice', 'composition', 'student engagement', 'performance'],
    'art instructor' => ['drawing', 'painting', 'creative', 'teaching', 'techniques', 'student engagement'],
    'sports coach' => ['training', 'teamwork', 'physical fitness', 'motivation', 'coordination', 'discipline'],
    'daycare teacher' => ['childcare', 'supervision', 'play activities', 'lesson planning', 'safety', 'student engagement'],
    'preschool assistant' => ['childcare', 'supervision', 'play activities', 'lesson support', 'safety', 'teamwork'],
    'library assistant' => ['cataloging', 'documentation', 'organization', 'customer service', 'research support', 'filing'],
    'lab assistant (school)' => ['experiments', 'documentation', 'equipment', 'safety', 'coordination', 'supervision'],
    
    // --- Healthcare / Personal Care ---
    'midwife' => ['delivery assistance', 'patient care', 'newborn care', 'monitoring', 'healthcare', 'documentation'],
    'nursing aide' => ['patient care', 'feeding', 'mobility assistance', 'monitoring', 'healthcare', 'assistance'],
    'physiotherapist assistant' => ['therapy', 'patient support', 'exercise', 'mobility', 'rehabilitation', 'healthcare'],
    'nutritionist assistant' => ['diet', 'planning', 'consultation', 'healthcare', 'documentation', 'analysis'],
    'wellness coach' => ['fitness', 'exercise', 'motivation', 'planning', 'health', 'diet'],
    'massage therapist' => ['massage', 'client care', 'wellness', 'manual therapy', 'hygiene', 'relaxation'],
    'pharmacy aide' => ['medication', 'inventory', 'dispensing', 'customer service', 'healthcare', 'documentation'],
    'lab technician' => ['testing', 'analysis', 'samples', 'documentation', 'reporting', 'equipment'],
    'dental assistant' => ['patient care', 'dental', 'equipment', 'clinic', 'hygiene', 'support'],
    'medical transcriptionist' => ['documentation', 'typing', 'accuracy', 'medical terms', 'record keeping', 'attention to detail'],

    // --- BPO / IT / Online / Remote ---
    'call center inbound agent' => ['customer service', 'communication', 'call handling', 'crm', 'problem solving', 'tickets'],
    'call center outbound agent' => ['sales', 'communication', 'phone calls', 'crm', 'customer acquisition', 'targets'],
    'call center QA analyst' => ['call monitoring', 'quality assurance', 'reporting', 'analytics', 'coaching', 'documentation'],
    'call center trainer' => ['training', 'coaching', 'communication', 'process knowledge', 'customer service', 'assessment'],
    'team lead BPO' => ['supervision', 'team management', 'customer service', 'call monitoring', 'process knowledge', 'coaching'],
    'virtual assistant' => ['calendar management', 'emails', 'scheduling', 'data entry', 'customer service', 'research'],
    'social media manager' => ['content creation', 'analytics', 'marketing', 'campaigns', 'branding', 'strategy'],
    'content writer' => ['writing', 'seo', 'editing', 'blog', 'research', 'social media'],
    'web designer' => ['html', 'css', 'javascript', 'wordpress', 'responsive design', 'ux/ui'],
    'graphic designer' => ['photoshop', 'illustrator', 'branding', 'layout', 'visual communication', 'creative'],
    'software tester' => ['testing', 'manual testing', 'automation', 'bug reporting', 'scripts', 'qa'],
    'data entry freelancer' => ['typing', 'accuracy', 'excel', 'documentation', 'speed', 'computer skills'],
    'AI trainer' => ['data labeling', 'model training', 'analysis', 'machine learning', 'annotation', 'accuracy'],
    'online English tutor' => ['teaching', 'grammar', 'student engagement', 'lesson planning', 'communication'],
    'content creator TikTok/YouTube' => ['video editing', 'storytelling', 'social media', 'engagement', 'digital marketing'],
    
    // --- Skilled Trades / Construction / Technical ---
    'roofing worker' => ['installation', 'manual labor', 'tools', 'safety', 'construction', 'teamwork'],
    'scaffold builder' => ['manual labor', 'safety', 'construction', 'installation', 'height work', 'teamwork'],
    'heavy equipment operator' => ['crane', 'excavator', 'bulldozer', 'operation', 'safety', 'coordination'],
    'welder' => ['welding', 'metalwork', 'fabrication', 'tools', 'construction', 'safety'],
    'carpenter' => ['woodwork', 'construction', 'furniture', 'tools', 'measurement', 'manual skills'],
    'mason' => ['bricklaying', 'cement', 'construction', 'tools', 'manual labor', 'teamwork'],
    'electrician' => ['wiring', 'installation', 'maintenance', 'safety', 'troubleshooting', 'tools'],
    'plumber' => ['pipes', 'installation', 'repair', 'maintenance', 'tools', 'manual skills'],
    'hvac technician' => ['installation', 'maintenance', 'cooling systems', 'troubleshooting', 'tools', 'repair'],
    'bicycle repairman' => ['repair', 'maintenance', 'tools', 'mechanical', 'manual skills', 'customer service'],
    'shoe repair/cobbler' => ['repair', 'leatherwork', 'tools', 'manual skills', 'craftsmanship', 'customer service'],
    'tailor/seamstress' => ['sewing', 'garment making', 'alteration', 'patterns', 'fabric knowledge', 'manual skills'],
    'motorcycle mechanic' => ['repair', 'maintenance', 'diagnostics', 'vehicles', 'tools', 'manual skills'],
    
    // --- Transportation / Logistics ---
    'tricycle driver' => ['driving', 'navigation', 'passenger service', 'time management', 'safety'],
    'jeepney driver' => ['driving', 'passenger safety', 'navigation', 'vehicle maintenance', 'routes'],
    'pedicab driver' => ['driving', 'passenger service', 'navigation', 'safety', 'local knowledge'],
    'bus conductor' => ['ticketing', 'passenger service', 'coordination', 'organization', 'communication'],
    'delivery rider' => ['navigation', 'timeliness', 'customer service', 'route planning', 'motorbike'],
    'courier' => ['delivery', 'time management', 'tracking', 'customer service', 'organization'],
    'porter' => ['carrying', 'assisting', 'coordination', 'physical strength', 'organization', 'customer service'],
    'warehouse staff' => ['inventory', 'packing', 'logistics', 'organization', 'tracking', 'coordination'],
    'cargo loader' => ['loading', 'unloading', 'heavy lifting', 'coordination', 'safety', 'logistics'],
    'ship crew/seafarer' => ['navigation', 'vessel maintenance', 'teamwork', 'operations', 'safety', 'coordination'],
    
    // --- Hospitality / Retail / Food Service ---
    'resort lifeguard' => ['swimming', 'rescue', 'first aid', 'safety', 'supervision', 'teamwork'],
    'tour operator' => ['itinerary', 'coordination', 'customer service', 'booking', 'communication'],
    'bellboy/bellhop' => ['luggage handling', 'guest service', 'coordination', 'teamwork', 'hospitality'],
    'waiter/waitress' => ['customer service', 'food serving', 'communication', 'teamwork', 'order taking'],
    'room service staff' => ['food delivery', 'coordination', 'customer service', 'hospitality', 'time management'],
    'theme park attendant' => ['guest service', 'operations', 'safety', 'coordination', 'teamwork'],
    'mall promoter' => ['sales', 'marketing', 'customer engagement', 'communication', 'promotion'],
    'street vendor' => ['sales', 'customer service', 'pricing', 'inventory', 'merchandising', 'street marketing'],
    'market vendor' => ['sales', 'inventory', 'pricing', 'customer service', 'stocking', 'merchandising'],
    'fast food crew' => ['customer service', 'food preparation', 'cleaning', 'teamwork', 'order handling'],
    'cashier (basic)' => ['point of sale', 'transactions', 'accuracy', 'money handling', 'customer service'],
    'grocery bagger/packer' => ['packing', 'organization', 'teamwork', 'speed', 'accuracy', 'customer service'],
    
    // --- Agriculture / Fishing / Environment ---
    'poultry farm worker' => ['feeding', 'cleaning', 'egg collection', 'manual labor', 'animal care', 'inventory'],
    'rice/corn farm laborer' => ['planting', 'harvesting', 'manual labor', 'irrigation', 'crop maintenance', 'teamwork'],
    'vegetable picker/packer' => ['harvesting', 'packing', 'manual labor', 'sorting', 'quality checking', 'teamwork'],
    'fish pond caretaker' => ['feeding', 'harvesting', 'maintenance', 'manual labor', 'aquaculture', 'monitoring'],
    'food processing plant worker' => ['packaging', 'inspection', 'manual labor', 'cleaning', 'production line', 'safety'],
    
    // --- Miscellaneous / Gig Economy ---
    'pet groomer/dog walker' => ['animal care', 'grooming', 'walking', 'customer service', 'time management'],
    'ice cream vendor' => ['sales', 'customer service', 'stock handling', 'merchandising', 'pricing'],
    'street cleaner' => ['cleaning', 'maintenance', 'sweeping', 'sanitation', 'teamwork'],
    'recycling collector/junkshop worker' => ['collection', 'sorting', 'transport', 'manual labor', 'teamwork'],
    'sign painter/billboard installer' => ['painting', 'installation', 'tools', 'manual skills', 'coordination'],
    'motorcycle taxi driver (habal-habal)' => ['driving', 'passenger service', 'navigation', 'safety', 'time management']
];

    /**
     * Gets all related semantic terms for a given term.
     * Focused expansion to avoid irrelevant cross-matches.
     */
    private static function getSemanticTerms($term) {
        $term = strtolower(trim($term));
        $terms = [$term];
        
        // 1. If it's a primary category (key), get its direct synonyms
        if (isset(self::$semanticMap[$term])) {
            $terms = array_merge($terms, self::$semanticMap[$term]);
        }
        
        // 2. If it's a synonym, get the primary category (key) it belongs to
        // We do NOT add all other synonyms of that key to prevent "transitive" mismatching
        foreach (self::$semanticMap as $key => $synonyms) {
            if (in_array($term, $synonyms)) {
                $terms[] = $key;
            }
        }
        
        return array_unique($terms);
    }

    public static function matchSkillsWithAI($userSkills, $jobTitle, $jobDescription, $jobRequirements, $jobIndustry = '') {
        if (empty($userSkills) || !is_array($userSkills)) {
            return ['score' => 0, 'matched_skills' => [], 'total_skills' => 0, 'matched_count' => 0];
        }

        $jobText = strtolower(trim(($jobTitle ?? '') . ' ' . ($jobDescription ?? '') . ' ' . ($jobRequirements ?? '') . ' ' . ($jobIndustry ?? '')));
        $totalSkills = count($userSkills);
        $matchedSkills = [];

        // Clean job text and break into words
        $jobKeywords = array_unique(explode(' ', preg_replace('/[^a-z0-9\s]/', ' ', $jobText)));

        foreach ($userSkills as $skillRaw) {
            $skill = strtolower(trim((string)$skillRaw));
            if ($skill === '' || $skill === 'n/a') continue;

            // Get semantic variants for this skill
            $skillVariants = self::getSemanticTerms($skill);
            $isMatched = false;

            foreach ($skillVariants as $variant) {
                // Direct match of variant in job text (use word boundaries for accuracy)
                $variantPattern = '/\b' . preg_quote($variant, '/') . '\b/i';
                if (preg_match($variantPattern, $jobText)) {
                    $isMatched = true;
                    break;
                }

                // Keyword-based match for multi-word variants
                $variantWords = explode(' ', $variant);
                if (count($variantWords) > 1) {
                    $matchCount = 0;
                    foreach ($variantWords as $word) {
                        if (in_array($word, $jobKeywords)) {
                            $matchCount++;
                        }
                    }
                    // Require higher threshold for multi-word phrases (e.g., 75%)
                    // This prevents "lesson planning" from matching just "planning"
                    if (($matchCount / count($variantWords)) >= 0.75) {
                        $isMatched = true;
                        break;
                    }
                }
            }

            if ($isMatched) {
                $matchedSkills[] = $skillRaw;
            }
        }

        $matchedCount = count($matchedSkills);
        $score = ($totalSkills > 0) ? ($matchedCount / $totalSkills) * 100 : 0;
        
        return [
            'score' => round($score, 2),
            'matched_skills' => $matchedSkills,
            'total_skills' => $totalSkills,
            'matched_count' => $matchedCount
        ];
    }

}
?>