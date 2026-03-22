<?php
/**
 * AI-Powered Job Matcher
 * Uses free AI services and intelligent matching algorithms
 * No expiration, free tier with limited but sufficient features
 */

class AIJobMatcher {
    private static $geoCache = [];
    
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
     * Occupation-to-skill hints based on preferred occupation dropdown options.
     * Used to infer expected skills for more accurate matching.
     */
    private static $occupationSkillHints = [
        'driver' => ['driving', 'driver', 'transport', 'delivery', 'vehicle', 'rider'],
        'truck driver' => ['driving', 'truck', 'delivery', 'logistics', 'vehicle'],
        'delivery rider' => ['driving', 'rider', 'delivery', 'navigation'],
        'cashier' => ['cashier', 'sales', 'customer service', 'pos', 'computer'],
        'sales representative' => ['sales', 'communication', 'customer service', 'negotiation'],
        'service crew' => ['customer service', 'food preparation', 'communication', 'cleaning'],
        'janitor' => ['cleaning', 'housekeeping', 'maintenance'],
        'security guard' => ['security', 'patrol', 'surveillance', 'communication'],
        'electrician' => ['electrical', 'wiring', 'troubleshooting'],
        'plumber' => ['plumbing', 'pipefitting', 'maintenance'],
        'carpenter' => ['carpentry', 'woodworking', 'construction'],
        'auto mechanic' => ['mechanic', 'automotive', 'vehicle repair', 'maintenance'],
        'cook' => ['cooking', 'food preparation', 'kitchen'],
        'chef' => ['cooking', 'kitchen', 'food safety'],
        'computer technician' => ['computer', 'technical support', 'troubleshooting'],
        'it support specialist' => ['computer', 'technical support', 'network', 'it'],
        'web developer' => ['programming', 'web development', 'javascript', 'html', 'css'],
        'software developer' => ['programming', 'software', 'debugging', 'database'],
        'accountant' => ['accounting', 'bookkeeping', 'excel', 'finance'],
        'teacher' => ['teaching', 'communication', 'lesson planning'],
        'warehouse staff' => ['warehouse', 'inventory', 'logistics', 'forklift']
    ];

    /**
     * Skill similarity groups for semantic matching
     * This supports non-exact wording (e.g., driving -> driver -> delivery rider)
     */
    private static $skillGroups = [
        'driving' => ['driving', 'driver', 'drive', 'delivery driver', 'truck driver', 'van driver', 'rider', 'chauffeur', 'transport'],
        'computer' => ['computer', 'computer skills', 'computer literacy', 'it', 'technical support', 'data entry', 'ms office', 'excel'],
        'communication' => ['communication', 'communication skills', 'customer service', 'client handling', 'interpersonal'],
        'sales' => ['sales', 'selling', 'seller', 'vendor', 'retail', 'cashier', 'merchandising', 'upselling', 'store', 'promodiser', 'promoter'],
        'painting' => ['painting', 'painter', 'paint', 'coating', 'finishing'],
        'mechanic' => ['mechanic', 'auto mechanic', 'automotive', 'vehicle repair', 'maintenance'],
        'electrical' => ['electrician', 'electrical', 'wiring', 'circuit', 'panel'],
        'plumbing' => ['plumbing', 'plumber', 'pipefitting', 'drainage'],
        'carpentry' => ['carpentry', 'carpenter', 'woodworking', 'joinery'],
        'beauty' => ['beautician', 'cosmetology', 'hair styling', 'makeup', 'salon'],
        'sewing' => ['sewing', 'tailoring', 'dressmaking', 'alteration', 'embroidery'],
        'cleaning' => ['housekeeping', 'cleaning', 'janitorial', 'domestic helper', 'custodial'],
        'security' => ['security', 'security guard', 'patrol', 'surveillance'],
        'food' => ['cooking', 'kitchen', 'food preparation', 'chef', 'cook', 'service crew']
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
        
        return 0; // No textual/proximity match
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
            return ['score' => 70, 'matched_locations' => [], 'distance_km' => null];
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
                'matched_locations' => array_unique($matchedLocations),
                'distance_km' => null
            ];
        }
        
        // If no textual/proximity match, estimate by real map distance (API-based geocoding).
        if ($maxScore === 0) {
            $bestDistanceScore = 0;
            $bestDistanceKm = null;
            foreach ($preferredLocations as $prefLoc) {
                $distanceData = self::calculateDistanceBasedLocationScore($prefLoc, $jobLocation);
                $distanceScore = (float)($distanceData['score'] ?? 0);
                $distanceKm = $distanceData['distance_km'] ?? null;
                $bestDistanceScore = max($bestDistanceScore, $distanceScore);
                if ($bestDistanceKm === null || ($distanceKm !== null && $distanceKm < $bestDistanceKm)) {
                    $bestDistanceKm = $distanceKm;
                }
            }
            if ($bestDistanceScore > 0) {
                return ['score' => $bestDistanceScore, 'matched_locations' => [], 'distance_km' => $bestDistanceKm];
            }
            return ['score' => 0, 'matched_locations' => [], 'distance_km' => null];
        }
        
        return [
            'score' => $maxScore,
            'matched_locations' => array_unique($matchedLocations),
            'distance_km' => null
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
                    $maxScore = max($maxScore, 90); // Connected occupation
                }
            } else {
                // Considerable relationship (not direct/connected)
                $sim = self::getSemanticSimilarity($prefOcc, $jobTitle);
                if ($sim >= 20) {
                    $maxScore = max($maxScore, 60);
                }
            }
        }
        
        if ($maxScore === 0) {
            return ['score' => 0, 'matched_occupations' => []];
        }
        
        return [
            'score' => $maxScore,
            'matched_occupations' => array_unique($matchedOccupations)
        ];
    }

    /**
     * Enhanced skill matching using semantic/group similarity with optional API fallback
     * Returns:
     * - score (0-100)
     * - matched_skills (user skill values that matched)
     * - total_skills
     * - matched_count
     */
    public static function matchSkillsWithAI($userSkills, $jobTitle, $jobDescription, $jobRequirements, $jobIndustry = '') {
        if (empty($userSkills) || !is_array($userSkills)) {
            return ['score' => 0, 'matched_skills' => [], 'total_skills' => 0, 'matched_count' => 0];
        }

        $jobText = strtolower(trim(($jobTitle ?? '') . ' ' . ($jobDescription ?? '') . ' ' . ($jobRequirements ?? '') . ' ' . ($jobIndustry ?? '')));
        $jobExpectedSkills = self::inferExpectedSkillsFromJob($jobTitle, $jobDescription, $jobRequirements, $jobIndustry);
        $totalSkills = count($userSkills);
        $matchedSkills = [];
        $considerableSkills = [];

        foreach ($userSkills as $skillRaw) {
            $skill = strtolower(trim((string)$skillRaw));
            if ($skill === '' || $skill === 'n/a') {
                continue;
            }

            $isMatched = false;
            $isConsiderable = false;

            // 1) Direct or partial literal
            if (strpos($jobText, $skill) !== false) {
                $isMatched = true;
            }

            // 2) Skill-group semantic match
            if (!$isMatched) {
                foreach (self::$skillGroups as $group => $variants) {
                    $skillInGroup = false;
                    foreach ($variants as $variant) {
                        $v = strtolower($variant);
                        if (strpos($skill, $v) !== false || strpos($v, $skill) !== false) {
                            $skillInGroup = true;
                            break;
                        }
                    }

                    if ($skillInGroup) {
                        foreach ($variants as $variant) {
                            if (strpos($jobText, strtolower($variant)) !== false) {
                                $isMatched = true;
                                break;
                            }
                        }
                        // If the job is expected to need this skill group, treat as considerable
                        if (!$isMatched) {
                            foreach ($jobExpectedSkills as $expectedSkill) {
                                $exp = strtolower((string)$expectedSkill);
                                if (in_array($exp, array_map('strtolower', $variants), true) ||
                                    strpos($exp, $group) !== false || strpos($group, $exp) !== false) {
                                    $isConsiderable = true;
                                    break;
                                }
                            }
                        }
                    }

                    if ($isMatched) {
                        break;
                    }
                }
            }

            // 3) Token-level similarity fallback (lightweight local semantic)
            if (!$isMatched) {
                $similarity = self::getSemanticSimilarity($skill, $jobText);
                if ($similarity >= 28) {
                    $isMatched = true; // strong enough semantic match
                } elseif ($similarity >= 18) {
                    $isConsiderable = true;
                }
            }

            if ($isMatched) {
                $matchedSkills[] = (string)$skillRaw;
            } elseif ($isConsiderable) {
                $considerableSkills[] = (string)$skillRaw;
            }
        }

        $matchedSkills = array_values(array_unique($matchedSkills));
        $matchedCount = count($matchedSkills);

        // Requested behavior:
        // - If at least one strong skill match exists => 100%
        // - If only considerable matches exist => lower score
        // - Else => 0%
        if ($matchedCount > 0) {
            $score = 100;
        } elseif (!empty($considerableSkills)) {
            $score = 55;
        } else {
            $score = 0;
        }

        return [
            'score' => $score,
            'matched_skills' => $matchedSkills,
            'total_skills' => $totalSkills,
            'matched_count' => $matchedCount
        ];
    }

    private static function inferExpectedSkillsFromJob($jobTitle, $jobDescription, $jobRequirements, $jobIndustry = '') {
        $text = strtolower(trim((string)$jobTitle . ' ' . (string)$jobDescription . ' ' . (string)$jobRequirements . ' ' . (string)$jobIndustry));
        $expected = [];

        $jobSkillSignals = [
            'sales' => ['sales', 'seller', 'vendor', 'retail', 'cashier', 'merchandiser', 'store', 'sales clerk'],
            'driving' => ['driver', 'driving', 'rider', 'delivery', 'truck', 'van', 'transport'],
            'computer' => ['office staff', 'computer', 'it', 'technical support', 'encoder', 'data entry', 'excel'],
            'communication' => ['customer service', 'call center', 'csr', 'receptionist', 'front desk'],
            'cleaning' => ['janitor', 'housekeeping', 'cleaner', 'custodian'],
            'food' => ['cook', 'chef', 'kitchen', 'service crew', 'food'],
            'security' => ['security', 'guard', 'patrol'],
            'construction' => ['carpenter', 'mason', 'welder', 'construction', 'plumber', 'electrician']
        ];

        foreach ($jobSkillSignals as $skillGroup => $signals) {
            foreach ($signals as $signal) {
                if (strpos($text, $signal) !== false) {
                    if (isset(self::$skillGroups[$skillGroup])) {
                        $expected = array_merge($expected, self::$skillGroups[$skillGroup]);
                    } else {
                        $expected[] = $skillGroup;
                    }
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $expected))));
    }

    public static function inferSkillsFromPreferredOccupations($preferredOccupations) {
        if (empty($preferredOccupations) || !is_array($preferredOccupations)) {
            return [];
        }
        $inferred = [];
        foreach ($preferredOccupations as $occ) {
            $o = strtolower(trim((string)$occ));
            if ($o === '' || $o === 'n/a' || $o === 'any') {
                continue;
            }
            foreach (self::$occupationSkillHints as $key => $skills) {
                if (strpos($o, $key) !== false || strpos($key, $o) !== false) {
                    $inferred = array_merge($inferred, $skills);
                }
            }
        }
        return array_values(array_unique(array_filter(array_map('trim', $inferred))));
    }

    private static function calculateDistanceBasedLocationScore($preferredLocation, $jobLocation) {
        $coord1 = self::geocodeLocation($preferredLocation);
        $coord2 = self::geocodeLocation($jobLocation);
        if (!$coord1 || !$coord2) {
            return ['score' => 0, 'distance_km' => null];
        }
        $distanceKm = self::haversineKm($coord1['lat'], $coord1['lon'], $coord2['lat'], $coord2['lon']);

        if ($distanceKm <= 5) return ['score' => 95, 'distance_km' => round($distanceKm, 1)];
        if ($distanceKm <= 15) return ['score' => 85, 'distance_km' => round($distanceKm, 1)];
        if ($distanceKm <= 30) return ['score' => 75, 'distance_km' => round($distanceKm, 1)];
        if ($distanceKm <= 60) return ['score' => 60, 'distance_km' => round($distanceKm, 1)];
        if ($distanceKm <= 120) return ['score' => 45, 'distance_km' => round($distanceKm, 1)];
        if ($distanceKm <= 250) return ['score' => 30, 'distance_km' => round($distanceKm, 1)];
        return ['score' => 0, 'distance_km' => round($distanceKm, 1)];
    }

    private static function geocodeLocation($location) {
        $q = strtolower(trim((string)$location));
        if ($q === '' || $q === 'n/a') {
            return null;
        }
        if (isset(self::$geoCache[$q])) {
            return self::$geoCache[$q];
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($location . ', Philippines');
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 1.2,
                'header' => "User-Agent: WorkConnectMatcher/1.0\r\n"
            ]
        ]);
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            self::$geoCache[$q] = null;
            return null;
        }
        $data = json_decode($response, true);
        if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            self::$geoCache[$q] = null;
            return null;
        }
        $coords = ['lat' => (float)$data[0]['lat'], 'lon' => (float)$data[0]['lon']];
        self::$geoCache[$q] = $coords;
        return $coords;
    }

    private static function haversineKm($lat1, $lon1, $lat2, $lon2) {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $r * $c;
    }
}
?>
