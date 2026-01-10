<?php
/**
 * AI-Powered Job Matcher
 * Uses free AI services and intelligent matching algorithms
 * No expiration, free tier with limited but sufficient features
 */

class AIJobMatcher {
    
    /**
     * Location proximity groups - cities that are considered nearby
     * This handles cases like Manila/Makati being close
     */
    private static $locationGroups = [
        'metro_manila' => [
            'manila', 'makati', 'quezon city', 'taguig', 'pasig', 'mandaluyong',
            'san juan', 'muntinlupa', 'las piñas', 'parañaque', 'valenzuela',
            'caloocan', 'malabon', 'navotas', 'marikina', 'pateros'
        ],
        'ncr_south' => ['muntinlupa', 'las piñas', 'parañaque', 'taguig'],
        'ncr_north' => ['caloocan', 'malabon', 'navotas', 'valenzuela'],
        'ncr_east' => ['marikina', 'pasig', 'san juan'],
        'ncr_west' => ['manila', 'makati', 'mandaluyong']
    ];
    
    /**
     * Occupation similarity groups - related job titles
     */
    private static $occupationGroups = [
        'software' => ['software developer', 'programmer', 'web developer', 'app developer', 'coder', 'software engineer'],
        'marketing' => ['marketing', 'digital marketing', 'social media', 'content creator', 'seo specialist'],
        'customer_service' => ['customer service', 'call center', 'support', 'csr', 'customer care'],
        'design' => ['graphic designer', 'ui designer', 'ux designer', 'web designer', 'artist'],
        'sales' => ['sales', 'sales representative', 'account executive', 'business development'],
        'admin' => ['administrative', 'admin assistant', 'secretary', 'office staff', 'clerk'],
        'accounting' => ['accountant', 'bookkeeper', 'auditor', 'financial analyst'],
        'hr' => ['hr', 'human resources', 'recruiter', 'talent acquisition'],
        'any' => [] // Special case - matches everything
    ];
    
    /**
     * Check if two locations are nearby using proximity groups
     */
    public static function areLocationsNearby($location1, $location2) {
        if (empty($location1) || empty($location2)) {
            return false;
        }
        
        $loc1 = strtolower(trim($location1));
        $loc2 = strtolower(trim($location2));
        
        // Exact match
        if ($loc1 === $loc2) {
            return true;
        }
        
        // Check if both are in the same proximity group
        foreach (self::$locationGroups as $group => $cities) {
            $inGroup1 = false;
            $inGroup2 = false;
            
            foreach ($cities as $city) {
                if (strpos($loc1, $city) !== false || strpos($city, $loc1) !== false) {
                    $inGroup1 = true;
                }
                if (strpos($loc2, $city) !== false || strpos($city, $loc2) !== false) {
                    $inGroup2 = true;
                }
            }
            
            if ($inGroup1 && $inGroup2) {
                return true;
            }
        }
        
        // Check for partial matches (e.g., "Metro Manila" contains "Manila")
        if (strpos($loc1, $loc2) !== false || strpos($loc2, $loc1) !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate location proximity score (0-100)
     */
    public static function calculateLocationProximityScore($preferredLocation, $jobLocation) {
        if (empty($preferredLocation) || empty($jobLocation)) {
            return 70; // Default score if no preference
        }
        
        $pref = strtolower(trim($preferredLocation));
        $job = strtolower(trim($jobLocation));
        
        // Exact match
        if ($pref === $job) {
            return 100;
        }
        
        // Check if nearby
        if (self::areLocationsNearby($preferredLocation, $jobLocation)) {
            return 85; // High score for nearby locations
        }
        
        // Partial match
        if (strpos($job, $pref) !== false || strpos($pref, $job) !== false) {
            return 75;
        }
        
        return 40; // Low score for no match
    }
    
    /**
     * Check if occupation matches using semantic similarity
     */
    public static function isOccupationSimilar($preferredOccupation, $jobTitle) {
        if (empty($preferredOccupation) || empty($jobTitle)) {
            return false;
        }
        
        $pref = strtolower(trim($preferredOccupation));
        $job = strtolower(trim($jobTitle));
        
        // Handle "any" - matches everything
        if ($pref === 'any' || $pref === 'n/a' || $pref === '') {
            return true;
        }
        
        // Exact match
        if ($pref === $job) {
            return true;
        }
        
        // Check if both are in the same occupation group
        foreach (self::$occupationGroups as $group => $occupations) {
            $inGroup1 = false;
            $inGroup2 = false;
            
            foreach ($occupations as $occ) {
                if (strpos($pref, $occ) !== false || strpos($occ, $pref) !== false) {
                    $inGroup1 = true;
                }
                if (strpos($job, $occ) !== false || strpos($occ, $job) !== false) {
                    $inGroup2 = true;
                }
            }
            
            if ($inGroup1 && $inGroup2) {
                return true;
            }
        }
        
        // Partial match (one contains the other)
        if (strpos($job, $pref) !== false || strpos($pref, $job) !== false) {
            return true;
        }
        
        // Word similarity (check if key words match)
        $prefWords = explode(' ', $pref);
        $jobWords = explode(' ', $job);
        $commonWords = array_intersect($prefWords, $jobWords);
        
        if (count($commonWords) > 0 && count($commonWords) >= min(count($prefWords), count($jobWords)) * 0.5) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate occupation similarity score (0-100)
     */
    public static function calculateOccupationSimilarityScore($preferredOccupations, $jobTitle) {
        if (empty($preferredOccupations) || !is_array($preferredOccupations)) {
            return 50; // Default score
        }
        
        $maxScore = 0;
        
        foreach ($preferredOccupations as $prefOcc) {
            $prefOcc = trim($prefOcc);
            
            // Handle "any" - gives high score
            if (strtolower($prefOcc) === 'any' || strtolower($prefOcc) === 'n/a' || $prefOcc === '') {
                $maxScore = max($maxScore, 80); // High score for "any"
                continue;
            }
            
            if (self::isOccupationSimilar($prefOcc, $jobTitle)) {
                // Exact match
                if (strtolower($prefOcc) === strtolower($jobTitle)) {
                    $maxScore = max($maxScore, 100);
                } else {
                    // Similar match
                    $maxScore = max($maxScore, 85);
                }
            }
        }
        
        return $maxScore > 0 ? $maxScore : 30;
    }
    
    /**
     * Use Hugging Face free API for semantic similarity (optional, fallback to local matching)
     * This is free and doesn't expire, but has rate limits
     */
    public static function getSemanticSimilarity($text1, $text2) {
        // For now, use local matching
        // If you want to use Hugging Face API, uncomment below:
        /*
        $url = "https://api-inference.huggingface.co/models/sentence-transformers/all-MiniLM-L6-v2";
        $data = [
            "inputs" => [
                "source_sentence" => $text1,
                "sentences" => [$text2]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if (isset($result[0])) {
                return $result[0] * 100; // Convert to 0-100 scale
            }
        }
        */
        
        // Fallback: Use local word-based similarity
        return self::calculateWordSimilarity($text1, $text2);
    }
    
    /**
     * Calculate word-based similarity (0-100)
     */
    private static function calculateWordSimilarity($text1, $text2) {
        $words1 = array_unique(explode(' ', strtolower($text1)));
        $words2 = array_unique(explode(' ', strtolower($text2)));
        
        $commonWords = array_intersect($words1, $words2);
        $totalWords = array_unique(array_merge($words1, $words2));
        
        if (count($totalWords) === 0) {
            return 0;
        }
        
        $similarity = (count($commonWords) / count($totalWords)) * 100;
        return round($similarity, 2);
    }
    
    /**
     * Enhanced location matching with proximity
     */
    public static function matchLocationWithProximity($preferredLocations, $jobLocation) {
        if (empty($preferredLocations) || !is_array($preferredLocations)) {
            return ['score' => 70, 'matched_locations' => []];
        }
        
        $matchedLocations = [];
        $maxScore = 0;
        $hasAny = false;
        
        foreach ($preferredLocations as $prefLoc) {
            $prefLoc = trim($prefLoc);
            
            // Handle "any" - matches everything with high score
            if (strtolower($prefLoc) === 'any') {
                $hasAny = true;
                $matchedLocations[] = $prefLoc;
                $maxScore = max($maxScore, 80); // High score for "any"
                continue;
            }
            
            if (empty($prefLoc) || strtolower($prefLoc) === 'n/a') {
                continue;
            }
            
            $score = self::calculateLocationProximityScore($prefLoc, $jobLocation);
            
            if ($score >= 75) {
                $matchedLocations[] = $prefLoc;
                $maxScore = max($maxScore, $score);
            }
        }
        
        // If "any" was found, return high score
        if ($hasAny) {
            return [
                'score' => 80,
                'matched_locations' => array_unique($matchedLocations)
            ];
        }
        
        if ($maxScore === 0) {
            return ['score' => 40, 'matched_locations' => []];
        }
        
        return [
            'score' => $maxScore,
            'matched_locations' => array_unique($matchedLocations)
        ];
    }
    
    /**
     * Enhanced occupation matching with "any" handling
     */
    public static function matchOccupationWithAI($preferredOccupations, $jobTitle) {
        if (empty($preferredOccupations) || !is_array($preferredOccupations)) {
            return ['score' => 50, 'matched_occupations' => []];
        }
        
        $matchedOccupations = [];
        $maxScore = 0;
        
        foreach ($preferredOccupations as $prefOcc) {
            $prefOcc = trim($prefOcc);
            
            if (empty($prefOcc) || strtolower($prefOcc) === 'n/a') {
                continue;
            }
            
            // Handle "any" - matches everything with high score
            if (strtolower($prefOcc) === 'any') {
                $matchedOccupations[] = $prefOcc;
                $maxScore = max($maxScore, 80);
                continue;
            }
            
            if (self::isOccupationSimilar($prefOcc, $jobTitle)) {
                $matchedOccupations[] = $prefOcc;
                
                // Calculate score based on similarity
                if (strtolower($prefOcc) === strtolower($jobTitle)) {
                    $maxScore = max($maxScore, 100);
                } else {
                    $maxScore = max($maxScore, 85);
                }
            }
        }
        
        if ($maxScore === 0) {
            return ['score' => 30, 'matched_occupations' => []];
        }
        
        return [
            'score' => $maxScore,
            'matched_occupations' => array_unique($matchedOccupations)
        ];
    }
}
?>
