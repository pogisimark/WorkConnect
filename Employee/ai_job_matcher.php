<?php
/**
 * AI-Powered Job Matcher
 * Uses free AI services and intelligent matching algorithms
 * No expiration, free tier with limited but sufficient features
 */

class AIJobMatcher {
    /**
     * Occupation-specific bridge terms for better title-to-title matching.
     * Additive map only: existing semantic map remains untouched.
     */
    private static $occupationAliasMap = [
        'programmer' => [
            'computer programmer', 'software developer', 'software engineer',
            'application developer', 'app developer', 'web developer', 'web programmer',
            'full stack developer', 'frontend developer', 'backend developer',
            'php developer', 'javascript developer', 'java developer', 'python developer',
            'c# developer', '.net developer', 'mobile app developer', 'game developer',
            'coding'
        ],
        'software developer' => [
            'programmer', 'software engineer', 'application developer', 'app developer',
            'full stack developer', 'frontend developer', 'backend developer', 'web developer'
        ],
        'web developer' => [
            'programmer', 'software developer', 'software engineer',
            'frontend developer', 'backend developer', 'full stack developer',
            'website developer', 'web programmer'
        ],
        'application developer' => [
            'programmer', 'software developer', 'app developer',
            'mobile app developer', 'web developer', 'software engineer'
        ],
        'content writer' => [
            'copywriter', 'seo content writer', 'technical writer',
            'article writer', 'blog writer', 'content creator'
        ],
        'graphic designer' => [
            'visual designer', 'freelance designer', 'ui designer', 'ui/ux designer', 'illustrator', 'layout artist'
        ],
        'it support specialist' => [
            'it support', 'technical support', 'helpdesk', 'system administrator', 'it specialist'
        ],
        'software engineer' => [
            'programmer', 'software developer', 'application developer', 'web developer',
            'full stack developer', 'backend developer', 'frontend developer'
        ],
        'full stack developer' => [
            'software developer', 'software engineer', 'web developer', 'programmer',
            'frontend developer', 'backend developer'
        ],
        'backend developer' => [
            'software developer', 'software engineer', 'programmer', 'api developer', 'server-side developer'
        ],
        'frontend developer' => [
            'software developer', 'web developer', 'programmer', 'ui developer', 'javascript developer'
        ],
        'mobile app developer' => [
            'application developer', 'app developer', 'programmer', 'software developer', 'android developer', 'ios developer'
        ],
        'devops engineer' => [
            'cloud engineer', 'site reliability engineer', 'system administrator', 'infrastructure engineer'
        ],
        'cloud engineer' => [
            'devops engineer', 'cloud architect', 'cloud security engineer', 'infrastructure engineer'
        ],
        'data analyst' => [
            'business analyst', 'bi analyst', 'data scientist', 'reporting analyst', 'excel analyst'
        ],
        'data scientist' => [
            'machine learning engineer', 'ai engineer', 'data analyst', 'data engineer'
        ],
        'machine learning engineer' => [
            'ai engineer', 'data scientist', 'nlp engineer', 'computer vision engineer'
        ],

        'it support' => [
            'it support specialist', 'technical support', 'helpdesk', 'service desk', 'desktop support', 'it specialist'
        ],
        'technical support' => [
            'it support', 'helpdesk', 'it support specialist', 'customer support'
        ],
        'helpdesk' => [
            'it support', 'technical support', 'service desk', 'it support specialist'
        ],

        'call center agent' => [
            'customer service representative', 'customer support', 'bpo specialist', 'csr', 'contact center agent'
        ],
        'customer service representative' => [
            'call center agent', 'customer support', 'bpo specialist', 'csr', 'support agent'
        ],
        'bpo specialist' => [
            'call center agent', 'customer service representative', 'customer support', 'csr'
        ],
        'virtual assistant' => [
            'remote customer support', 'administrative assistant', 'data entry freelancer', 'customer support'
        ],
        'data entry freelancer' => [
            'data encoder', 'data entry specialist', 'virtual assistant', 'administrative assistant'
        ],

        'accountant' => [
            'accounting clerk', 'bookkeeper', 'finance manager', 'financial analyst', 'auditor', 'cpa'
        ],
        'bookkeeper' => [
            'accountant', 'accounting clerk', 'finance assistant', 'accounts payable', 'accounts receivable'
        ],
        'accounting clerk' => [
            'accountant', 'bookkeeper', 'finance assistant', 'billing clerk'
        ],
        'financial analyst' => [
            'accountant', 'finance manager', 'budget analyst', 'risk analyst'
        ],

        'teacher' => [
            'tutor', 'online tutor', 'professor', 'instructor', 'education consultant'
        ],
        'tutor' => [
            'teacher', 'online tutor', 'instructor'
        ],

        'nurse' => [
            'registered nurse', 'staff nurse', 'clinical nurse', 'nursing assistant'
        ],
        'doctor' => [
            'physician', 'general practitioner', 'medical doctor', 'specialist'
        ],
        'pharmacist' => [
            'pharmacy assistant', 'clinical pharmacist', 'drugstore pharmacist'
        ],

        'ui/ux designer' => [
            'ui designer', 'ux designer', 'product designer', 'visual designer'
        ],
        'copywriter' => [
            'content writer', 'seo content writer', 'technical writer', 'content creator'
        ],
        'digital marketer' => [
            'marketing executive', 'social media manager', 'seo specialist', 'content strategist'
        ],
        'social media manager' => [
            'digital marketer', 'content creator', 'social media strategist', 'marketing executive'
        ],
        'marketing executive' => [
            'digital marketer', 'brand manager', 'social media manager'
        ],

        'driver' => [
            'truck driver', 'bus driver', 'delivery driver', 'motorcycle taxi driver', 'rider'
        ],
        'truck driver' => [
            'driver', 'delivery driver', 'logistics driver', 'heavy truck driver'
        ],
        'delivery rider' => [
            'driver', 'delivery driver', 'motorcycle taxi driver', 'rider'
        ],
        'warehouse supervisor' => [
            'logistics coordinator', 'warehouse staff', 'inventory coordinator'
        ],
        'logistics coordinator' => [
            'warehouse supervisor', 'logistics manager', 'dispatch coordinator'
        ],

        'electrician' => [
            'industrial electrician', 'master electrician', 'electrical technician'
        ],
        'mechanic' => [
            'auto mechanic', 'diesel mechanic', 'motorcycle mechanic', 'automotive technician'
        ],
        'plumber' => [
            'pipefitter', 'steamfitter', 'maintenance plumber'
        ],
        'carpenter' => [
            'cabinet maker', 'framing carpenter', 'woodworker'
        ],
        'construction worker' => [
            'construction laborer', 'site worker', 'helper', 'mason'
        ],

        'cashier' => [
            'retail staff', 'sales associate', 'store cashier', 'pos cashier'
        ],
        'retail staff' => [
            'sales associate', 'cashier', 'store crew', 'store staff'
        ],
        'sales executive' => [
            'account manager', 'business development manager', 'sales representative'
        ],
        'business development manager' => [
            'sales executive', 'account manager', 'partnership manager'
        ],
        'account manager' => [
            'sales executive', 'business development manager', 'key account manager'
        ]
    ];

    /**
     * Auto-generated occupation alias cache (built from semanticMap).
     * This produces 1000+ additive examples dynamically.
     */
    private static $generatedOccupationAliasMap = null;
    private static $generatedSemanticExpansionMap = null;
    private static $unifiedSemanticMapCache = null;

    private static $occupationVariantPrefixes = [
        'senior', 'junior', 'jr', 'lead', 'associate', 'assistant', 'remote', 'freelance'
    ];

    private static $occupationVariantSuffixes = [
        'specialist', 'officer', 'technician', 'coordinator', 'analyst', 'consultant', 'staff'
    ];

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
        'mabalacat' => ['lat' => 15.2150, 'lon' => 120.5750],
        'guagua' => ['lat' => 14.9658, 'lon' => 120.6306],
        'lubao' => ['lat' => 14.9403, 'lon' => 120.6017],

        // --- Cavite (Expanded) ---
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

    private static function normalizeOccupationText($text) {
        $text = strtolower(trim((string)$text));
        $text = preg_replace('/[^a-z0-9\+\#\.\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Build large additive alias examples from semanticMap and existing aliases.
     * The resulting map is intentionally broad to cover real-world title variants.
     */
    private static function getGeneratedOccupationAliasMap() {
        if (self::$generatedOccupationAliasMap !== null) {
            return self::$generatedOccupationAliasMap;
        }

        $map = [];

        // Seed from semantic map categories + synonyms
        foreach (self::$semanticMap as $key => $synonyms) {
            $cluster = array_merge([$key], is_array($synonyms) ? $synonyms : []);
            $expanded = [];

            foreach ($cluster as $term) {
                $base = self::normalizeOccupationText($term);
                if ($base === '' || strlen($base) < 3) {
                    continue;
                }

                $expanded[] = $base;

                // Slash form normalization, e.g. "ui/ux designer" => "ui ux designer"
                if (strpos($base, '/') !== false) {
                    $expanded[] = self::normalizeOccupationText(str_replace('/', ' ', $base));
                }

                // Prefixed variants
                foreach (self::$occupationVariantPrefixes as $prefix) {
                    $expanded[] = self::normalizeOccupationText($prefix . ' ' . $base);
                }

                // Suffixed variants
                foreach (self::$occupationVariantSuffixes as $suffix) {
                    if (!preg_match('/\b' . preg_quote($suffix, '/') . '\b/', $base)) {
                        $expanded[] = self::normalizeOccupationText($base . ' ' . $suffix);
                    }
                }

                // Common freelancing/role variants
                if (!preg_match('/\bfreelancer\b/', $base)) {
                    $expanded[] = self::normalizeOccupationText($base . ' freelancer');
                }
                if (!preg_match('/\bmanager\b/', $base)) {
                    $expanded[] = self::normalizeOccupationText($base . ' manager');
                }
                if (!preg_match('/\bengineer\b/', $base) && preg_match('/\bdeveloper\b|\bprogrammer\b|\bsoftware\b/', $base)) {
                    $expanded[] = self::normalizeOccupationText($base . ' engineer');
                }
            }

            $expanded = array_values(array_unique(array_filter($expanded)));
            if (count($expanded) < 2) {
                continue;
            }

            // For each generated term, store related terms from same semantic cluster.
            foreach ($expanded as $term) {
                $related = array_values(array_diff($expanded, [$term]));
                if (!isset($map[$term])) {
                    $map[$term] = [];
                }
                // Cap per-term related list for performance.
                $map[$term] = array_values(array_unique(array_merge($map[$term], array_slice($related, 0, 45))));
            }
        }

        // Also seed from explicit occupationAliasMap (existing hand-tuned aliases)
        foreach (self::$occupationAliasMap as $key => $aliases) {
            $k = self::normalizeOccupationText($key);
            if ($k === '') continue;
            if (!isset($map[$k])) $map[$k] = [];

            foreach ($aliases as $a) {
                $na = self::normalizeOccupationText($a);
                if ($na === '') continue;
                $map[$k][] = $na;
                if (!isset($map[$na])) $map[$na] = [];
                $map[$na][] = $k;
            }
            $map[$k] = array_values(array_unique($map[$k]));
        }

        self::$generatedOccupationAliasMap = $map;
        return self::$generatedOccupationAliasMap;
    }

    /**
     * Build 500+ additive job semantic examples (job title => aligned skills/synonyms).
     * This does NOT remove/replace existing semanticMap data.
     */
    private static function getGeneratedSemanticExpansionMap() {
        if (self::$generatedSemanticExpansionMap !== null) {
            return self::$generatedSemanticExpansionMap;
        }

        $levels = ['junior', 'senior', 'lead', 'assistant', 'associate', 'principal', 'remote', 'freelance'];

        $baseRoleSkills = [
            // IT / Software / Data
            'software developer' => ['programming', 'software', 'developer', 'coding', 'debugging', 'api', 'git', 'technical', 'agile'],
            'web developer' => ['html', 'css', 'javascript', 'frontend', 'backend', 'responsive', 'web app', 'api', 'programming'],
            'frontend developer' => ['html', 'css', 'javascript', 'react', 'vue', 'ui', 'ux', 'responsive', 'web design'],
            'backend developer' => ['php', 'node js', 'python', 'java', 'sql', 'api', 'server', 'database', 'microservices'],
            'full stack developer' => ['frontend', 'backend', 'api', 'database', 'javascript', 'php', 'python', 'deployment', 'testing'],
            'mobile app developer' => ['android', 'ios', 'flutter', 'react native', 'kotlin', 'swift', 'mobile ui', 'api integration'],
            'qa engineer' => ['testing', 'automation', 'selenium', 'cypress', 'quality assurance', 'bug tracking', 'test cases'],
            'devops engineer' => ['docker', 'kubernetes', 'ci cd', 'jenkins', 'automation', 'infrastructure', 'monitoring', 'cloud'],
            'cloud engineer' => ['aws', 'azure', 'gcp', 'cloud architecture', 'security', 'scalability', 'networking', 'virtualization'],
            'site reliability engineer' => ['sre', 'uptime', 'monitoring', 'automation', 'incident response', 'ci cd', 'infrastructure'],
            'data analyst' => ['data', 'analytics', 'excel', 'sql', 'reporting', 'dashboard', 'statistics', 'business intelligence'],
            'business intelligence analyst' => ['power bi', 'tableau', 'data modeling', 'dashboard', 'reporting', 'kpi', 'sql'],
            'data scientist' => ['python', 'machine learning', 'statistics', 'modeling', 'data analysis', 'tensorflow', 'pytorch'],
            'machine learning engineer' => ['ml', 'python', 'tensorflow', 'pytorch', 'algorithms', 'feature engineering', 'deployment'],
            'ai engineer' => ['ai', 'machine learning', 'nlp', 'computer vision', 'deep learning', 'model optimization'],
            'cybersecurity analyst' => ['security', 'threat analysis', 'vulnerability', 'siem', 'incident response', 'firewall'],
            'it support specialist' => ['helpdesk', 'troubleshooting', 'hardware', 'software', 'network', 'technical support', 'ticketing'],
            'system administrator' => ['linux', 'windows server', 'network', 'backup', 'monitoring', 'security', 'infrastructure'],
            'database administrator' => ['sql', 'database', 'backup', 'performance tuning', 'replication', 'dba', 'query optimization'],
            'ui ux designer' => ['wireframe', 'prototype', 'figma', 'adobe xd', 'usability', 'interaction design', 'user research'],
            'graphic designer' => ['photoshop', 'illustrator', 'branding', 'layout', 'visual', 'creative', 'typography'],

            // BPO / Support / Admin
            'call center agent' => ['customer service', 'communication', 'inbound', 'outbound', 'crm', 'problem solving', 'english'],
            'customer service representative' => ['customer support', 'communication', 'crm', 'ticketing', 'escalation', 'service'],
            'bpo specialist' => ['process outsourcing', 'customer handling', 'call handling', 'email support', 'multitasking'],
            'technical support representative' => ['troubleshooting', 'technical support', 'remote support', 'ticketing', 'customer service'],
            'virtual assistant' => ['calendar management', 'emails', 'scheduling', 'research', 'data entry', 'administrative'],
            'data encoder' => ['typing', 'accuracy', 'data entry', 'excel', 'documentation', 'records management'],
            'administrative assistant' => ['administrative', 'documentation', 'scheduling', 'office', 'communication', 'filing'],
            'office clerk' => ['filing', 'data entry', 'documentation', 'office support', 'records', 'coordination'],
            'receptionist' => ['front desk', 'customer service', 'phone handling', 'scheduling', 'communication', 'office support'],

            // Finance / Accounting
            'accountant' => ['accounting', 'bookkeeping', 'finance', 'tax', 'ledger', 'financial reporting', 'cpa'],
            'bookkeeper' => ['bookkeeping', 'ledger', 'invoicing', 'accounts payable', 'accounts receivable', 'quickbooks'],
            'accounting clerk' => ['billing', 'bookkeeping', 'data entry', 'financial records', 'invoice', 'reconciliation'],
            'financial analyst' => ['financial modeling', 'forecasting', 'budgeting', 'analysis', 'reporting', 'excel'],
            'auditor' => ['audit', 'compliance', 'internal control', 'risk assessment', 'financial review'],
            'tax consultant' => ['tax', 'compliance', 'filing', 'corporate tax', 'advisory'],
            'finance manager' => ['budgeting', 'forecasting', 'financial planning', 'team management', 'reporting'],
            'payroll specialist' => ['payroll', 'compensation', 'timekeeping', 'tax deductions', 'hris', 'compliance'],

            // Sales / Marketing
            'sales representative' => ['sales', 'prospecting', 'negotiation', 'client management', 'closing', 'crm'],
            'sales executive' => ['business development', 'account management', 'pipeline', 'sales strategy', 'crm'],
            'account manager' => ['client retention', 'relationship management', 'upselling', 'sales', 'negotiation'],
            'business development manager' => ['lead generation', 'partnership', 'strategy', 'market research', 'sales'],
            'digital marketer' => ['seo', 'sem', 'social media', 'campaigns', 'analytics', 'content marketing'],
            'social media manager' => ['content creation', 'engagement', 'analytics', 'social strategy', 'campaigns'],
            'seo specialist' => ['seo', 'keyword research', 'on page', 'off page', 'analytics', 'google search console'],
            'content writer' => ['writing', 'seo', 'blogging', 'copywriting', 'editing', 'research'],
            'copywriter' => ['copywriting', 'content', 'branding', 'campaign messaging', 'writing', 'editing'],
            'brand manager' => ['branding', 'positioning', 'campaign strategy', 'market research', 'communications'],

            // HR / Legal / Operations
            'hr specialist' => ['recruitment', 'talent acquisition', 'employee relations', 'onboarding', 'hr policies'],
            'recruiter' => ['sourcing', 'screening', 'interviewing', 'talent acquisition', 'job posting'],
            'training specialist' => ['training', 'facilitation', 'curriculum', 'coaching', 'learning and development'],
            'operations manager' => ['operations', 'process improvement', 'kpi', 'team management', 'strategy'],
            'project manager' => ['project planning', 'execution', 'stakeholder', 'budget', 'risk', 'agile'],
            'procurement officer' => ['procurement', 'vendor management', 'purchasing', 'negotiation', 'supply chain'],
            'legal assistant' => ['legal research', 'documentation', 'contracts', 'case preparation', 'compliance'],
            'compliance officer' => ['compliance', 'risk', 'regulatory', 'audit', 'policy', 'reporting'],

            // Education
            'teacher' => ['teaching', 'lesson planning', 'classroom management', 'assessment', 'curriculum'],
            'online tutor' => ['online teaching', 'lesson planning', 'student support', 'assessment', 'subject mastery'],
            'professor' => ['lectures', 'research', 'curriculum development', 'academic writing', 'student advising'],
            'guidance counselor' => ['counseling', 'student support', 'assessment', 'communication', 'wellbeing'],
            'instructional designer' => ['curriculum', 'elearning', 'learning outcomes', 'content design', 'assessment'],

            // Healthcare
            'nurse' => ['patient care', 'clinical', 'hospital', 'charting', 'medication', 'healthcare'],
            'registered nurse' => ['rn', 'patient care', 'clinical', 'monitoring', 'hospital', 'nursing'],
            'doctor' => ['diagnosis', 'treatment', 'patient care', 'clinical', 'medical consultation'],
            'medical technologist' => ['laboratory', 'sample analysis', 'diagnostics', 'lab equipment', 'reporting'],
            'pharmacist' => ['medication', 'dispensing', 'drug interaction', 'pharmacy', 'patient counseling'],
            'physical therapist' => ['rehabilitation', 'therapy', 'mobility', 'exercise plans', 'patient care'],
            'caregiver' => ['elderly care', 'patient assistance', 'daily living support', 'health monitoring'],
            'medical assistant' => ['patient intake', 'vitals', 'clinical support', 'documentation', 'scheduling'],

            // Engineering / Construction / Trades
            'civil engineer' => ['civil engineering', 'construction', 'autocad', 'site planning', 'project management'],
            'mechanical engineer' => ['mechanical design', 'cad', 'maintenance', 'thermodynamics', 'manufacturing'],
            'electrical engineer' => ['electrical design', 'power systems', 'circuit', 'troubleshooting', 'automation'],
            'architect' => ['architectural design', 'autocad', '3d modeling', 'planning', 'construction'],
            'quantity surveyor' => ['cost estimation', 'boq', 'construction', 'procurement', 'project costing'],
            'electrician' => ['wiring', 'electrical maintenance', 'circuit', 'installation', 'safety'],
            'mechanic' => ['automotive repair', 'diagnostics', 'maintenance', 'engine', 'troubleshooting'],
            'plumber' => ['pipe installation', 'repair', 'water systems', 'maintenance', 'tools'],
            'welder' => ['welding', 'fabrication', 'metalwork', 'safety', 'assembly'],
            'carpenter' => ['woodwork', 'construction', 'furniture', 'measurement', 'tools'],
            'construction worker' => ['construction', 'manual labor', 'safety', 'tools', 'teamwork'],
            'heavy equipment operator' => ['equipment operation', 'excavator', 'bulldozer', 'safety', 'site work'],

            // Logistics / Transport / Warehouse
            'driver' => ['driving', 'delivery', 'transportation', 'navigation', 'vehicle maintenance'],
            'delivery rider' => ['delivery', 'navigation', 'motorcycle', 'customer service', 'time management'],
            'truck driver' => ['truck driving', 'logistics', 'route planning', 'cargo handling', 'safety'],
            'warehouse staff' => ['inventory', 'picking', 'packing', 'stocking', 'warehouse operations'],
            'warehouse supervisor' => ['inventory control', 'team supervision', 'warehouse operations', 'kpi', 'safety'],
            'logistics coordinator' => ['logistics', 'shipping', 'dispatch', 'inventory', 'supply chain'],
            'supply chain analyst' => ['supply chain', 'forecasting', 'inventory analysis', 'procurement', 'reporting'],
            'purchasing assistant' => ['purchasing', 'vendor coordination', 'procurement', 'documentation', 'inventory'],

            // Hospitality / Food / Retail
            'hotel receptionist' => ['hospitality', 'front desk', 'booking', 'customer service', 'communication'],
            'hotel manager' => ['hotel operations', 'hospitality', 'staff management', 'guest relations', 'budgeting'],
            'restaurant manager' => ['restaurant operations', 'staff scheduling', 'inventory', 'customer service', 'food safety'],
            'chef' => ['culinary', 'menu planning', 'food preparation', 'kitchen operations', 'food safety'],
            'cook' => ['cooking', 'food prep', 'kitchen', 'food safety', 'time management'],
            'barista' => ['coffee preparation', 'customer service', 'cash handling', 'cleanliness', 'inventory'],
            'cashier' => ['pos', 'cash handling', 'customer service', 'transactions', 'accuracy'],
            'sales associate' => ['retail sales', 'customer service', 'merchandising', 'inventory', 'upselling'],
            'store supervisor' => ['store operations', 'team supervision', 'sales monitoring', 'inventory control'],

            // Government / Community / Misc
            'barangay staff' => ['community service', 'documentation', 'administrative support', 'coordination', 'public service'],
            'utility worker' => ['maintenance', 'cleaning', 'facility support', 'repair', 'manual labor'],
            'security guard' => ['security', 'surveillance', 'patrol', 'incident reporting', 'safety'],
            'janitor' => ['cleaning', 'sanitation', 'facility maintenance', 'housekeeping', 'safety'],
            'messenger' => ['document delivery', 'routing', 'coordination', 'time management', 'communication']
        ];

        $generated = [];
        $maxGeneratedEntries = 3500; // Cap runtime memory; semanticMap + baseRoleSkills still seed matching.

        $domainTags = [
            'healthcare', 'finance', 'education', 'retail', 'manufacturing', 'construction',
            'logistics', 'hospitality', 'government', 'technology', 'telecommunications',
            'agriculture', 'energy', 'automotive', 'media', 'ecommerce', 'banking',
            'insurance', 'public service', 'non profit'
        ];
        $domainSkillMap = [
            'healthcare' => ['patient care', 'clinical', 'medical records', 'health safety'],
            'finance' => ['reporting', 'compliance', 'financial analysis', 'accuracy'],
            'education' => ['lesson planning', 'assessment', 'curriculum', 'communication'],
            'retail' => ['customer service', 'sales', 'inventory', 'pos'],
            'manufacturing' => ['quality control', 'production', 'safety', 'maintenance'],
            'construction' => ['site safety', 'tools', 'planning', 'coordination'],
            'logistics' => ['dispatch', 'routing', 'inventory', 'supply chain'],
            'hospitality' => ['guest service', 'operations', 'teamwork', 'communication'],
            'government' => ['documentation', 'public service', 'compliance', 'coordination'],
            'technology' => ['technical', 'problem solving', 'automation', 'digital tools'],
            'telecommunications' => ['network', 'support', 'field operations', 'service quality'],
            'agriculture' => ['field work', 'safety', 'operations', 'resource management'],
            'energy' => ['safety', 'maintenance', 'monitoring', 'compliance'],
            'automotive' => ['diagnostics', 'repair', 'maintenance', 'safety'],
            'media' => ['content', 'editing', 'creative', 'publishing'],
            'ecommerce' => ['online operations', 'orders', 'customer support', 'analytics'],
            'banking' => ['customer onboarding', 'compliance', 'risk checks', 'records'],
            'insurance' => ['claims', 'policy', 'risk assessment', 'documentation'],
            'public service' => ['community support', 'records', 'coordination', 'accountability'],
            'non profit' => ['program support', 'community outreach', 'documentation', 'stakeholder engagement']
        ];
        $roleSuffixes = [
            'specialist', 'coordinator', 'associate', 'assistant', 'officer',
            'analyst', 'technician', 'manager', 'supervisor', 'consultant'
        ];
        $workModes = ['remote', 'onsite', 'hybrid', 'field', 'contract', 'freelance'];

        // Merge seed roles from explicit base map + existing semanticMap keys.
        $seedRoles = array_values(array_unique(array_merge(array_keys($baseRoleSkills), array_keys(self::$semanticMap))));

        $addGeneratedEntry = function($title, $skills) use (&$generated, $maxGeneratedEntries) {
            if (count($generated) >= $maxGeneratedEntries) {
                return;
            }
            $t = self::normalizeOccupationText($title);
            if ($t === '' || strlen($t) < 3) {
                return;
            }
            $cleanSkills = [];
            foreach ($skills as $s) {
                $ns = self::normalizeOccupationText($s);
                if ($ns !== '' && strlen($ns) >= 2) {
                    $cleanSkills[] = $ns;
                }
            }
            if (empty($cleanSkills)) {
                return;
            }
            if (!isset($generated[$t])) {
                $generated[$t] = [];
            }
            $generated[$t] = array_values(array_unique(array_merge($generated[$t], array_slice($cleanSkills, 0, 28))));
        };

        foreach ($seedRoles as $role) {
            if (count($generated) >= $maxGeneratedEntries) {
                break;
            }
            $roleNorm = self::normalizeOccupationText($role);
            if ($roleNorm === '' || strlen($roleNorm) < 4) continue;

            $roleSkills = array_merge(
                [$roleNorm],
                $baseRoleSkills[$roleNorm] ?? [],
                self::$semanticMap[$roleNorm] ?? []
            );
            $roleSkills = array_values(array_unique(array_filter($roleSkills)));
            if (count($roleSkills) < 3) {
                // Skip very weak/non-role terms.
                continue;
            }

            // Base role
            $addGeneratedEntry($roleNorm, $roleSkills);

            // Level variants
            foreach ($levels as $lvl) {
                $addGeneratedEntry($lvl . ' ' . $roleNorm, array_merge($roleSkills, [$lvl]));
            }

            // Deterministically choose subsets to prevent combinatorial explosion.
            $h = abs(crc32($roleNorm));
            $domainStart = $h % count($domainTags);
            $suffixStart = $h % count($roleSuffixes);
            $modeStart = $h % count($workModes);

            $pickedDomains = [];
            for ($i = 0; $i < 6; $i++) {
                $pickedDomains[] = $domainTags[($domainStart + $i) % count($domainTags)];
            }
            $pickedSuffixes = [];
            for ($i = 0; $i < 5; $i++) {
                $pickedSuffixes[] = $roleSuffixes[($suffixStart + $i) % count($roleSuffixes)];
            }
            $pickedModes = [];
            for ($i = 0; $i < 3; $i++) {
                $pickedModes[] = $workModes[($modeStart + $i) % count($workModes)];
            }

            // Domain + suffix + mode variants
            foreach ($pickedDomains as $domain) {
                $domainSkills = $domainSkillMap[$domain] ?? [];
                foreach ($pickedSuffixes as $suffix) {
                    $addGeneratedEntry(
                        $domain . ' ' . $roleNorm . ' ' . $suffix,
                        array_merge($roleSkills, $domainSkills, [$domain, $suffix])
                    );
                    $addGeneratedEntry(
                        $roleNorm . ' ' . $suffix . ' ' . $domain,
                        array_merge($roleSkills, $domainSkills, [$domain, $suffix])
                    );
                }
                foreach ($pickedModes as $mode) {
                    $addGeneratedEntry(
                        $mode . ' ' . $domain . ' ' . $roleNorm,
                        array_merge($roleSkills, $domainSkills, [$mode, $domain])
                    );
                }
            }
        }

        // Generated title→skill expansions (capped by maxGeneratedEntries).
        self::$generatedSemanticExpansionMap = $generated;
        return self::$generatedSemanticExpansionMap;
    }

    private static function getUnifiedSemanticMap() {
        if (self::$unifiedSemanticMapCache !== null) {
            return self::$unifiedSemanticMapCache;
        }

        $merged = self::$semanticMap;
        $expanded = self::getGeneratedSemanticExpansionMap();

        foreach ($expanded as $key => $synonyms) {
            if (!isset($merged[$key])) {
                $merged[$key] = [];
            }
            $merged[$key] = array_values(array_unique(array_merge($merged[$key], $synonyms)));
        }

        self::$unifiedSemanticMapCache = $merged;
        return self::$unifiedSemanticMapCache;
    }

    private static function getOccupationTerms($occupation) {
        $base = self::normalizeOccupationText($occupation);
        if ($base === '') return [];

        $terms = [$base];

        // Existing semantic map (kept intact)
        $terms = array_merge($terms, self::getSemanticTerms($base));

        // Direct alias expansion (new additive bridge)
        if (isset(self::$occupationAliasMap[$base])) {
            $terms = array_merge($terms, self::$occupationAliasMap[$base]);
        }

        // Reverse alias lookup: if term is listed under a key, include that key and its aliases
        foreach (self::$occupationAliasMap as $key => $aliases) {
            if (in_array($base, $aliases, true)) {
                $terms[] = $key;
                $terms = array_merge($terms, $aliases);
            }
        }

        // Generated 1000+ semantic examples from existing semantic map.
        $generatedMap = self::getGeneratedOccupationAliasMap();
        if (isset($generatedMap[$base])) {
            $terms = array_merge($terms, $generatedMap[$base]);
        }

        $cleanTerms = [];
        foreach ($terms as $t) {
            $nt = self::normalizeOccupationText($t);
            if ($nt !== '' && $nt !== 'n a') {
                $cleanTerms[] = $nt;
            }
        }
        return array_values(array_unique($cleanTerms));
    }

    private static function calculateSemanticOccupationScore($preferredOccupation, $jobTitle) {
        $prefTerms = self::getOccupationTerms($preferredOccupation);
        $job = self::normalizeOccupationText($jobTitle);
        if (empty($prefTerms) || $job === '') return 0;

        // Strong signal: a semantic term appears as full phrase in the job title
        foreach ($prefTerms as $term) {
            if ($term === '') continue;
            if (preg_match('/\b' . preg_quote($term, '/') . '\b/i', $job)) {
                return 95;
            }
        }

        // Fallback: token overlap across semantic terms
        $jobTokens = array_values(array_filter(explode(' ', $job)));
        if (empty($jobTokens)) return 0;

        $prefTokenSet = [];
        foreach ($prefTerms as $term) {
            $parts = array_values(array_filter(explode(' ', $term)));
            foreach ($parts as $p) {
                if (strlen($p) >= 3) {
                    $prefTokenSet[$p] = true;
                }
            }
        }
        if (empty($prefTokenSet)) return 0;

        $matches = 0;
        foreach ($jobTokens as $jt) {
            if (isset($prefTokenSet[$jt])) {
                $matches++;
            }
        }
        if ($matches === 0) return 0;

        $coverage = $matches / count($jobTokens);
        return min(90, max(35, round($coverage * 100)));
    }

    public static function matchLocationWithProximity($preferredLocations, $jobLocation, $userCurrentLocation = null) {
        $matchedLocations = [];
        $hasAny = false;

        // Registered home (NSRP province + municipality)
        $currentRaw = $userCurrentLocation ? trim((string) $userCurrentLocation) : '';
        $currentData = ['score' => 0, 'distance_km' => null];
        if ($currentRaw !== '') {
            $currentData = self::calculateDistanceBasedLocationScore($currentRaw, $jobLocation);
        }
        $currentScoreBoosted = $currentData['score'] > 0 ? min(100, $currentData['score'] + 5) : 0;

        $bestPrefScore = 0;
        $minPrefDistanceKm = null;
        $prefLabelAtMinDistance = null;

        if (!empty($preferredLocations) && is_array($preferredLocations)) {
            foreach ($preferredLocations as $prefLoc) {
                $prefLoc = trim((string) $prefLoc);
                if ($prefLoc === '' || strtolower($prefLoc) === 'n/a') {
                    continue;
                }

                if (strtolower($prefLoc) === 'any') {
                    $hasAny = true;
                    $matchedLocations[] = 'Any';
                    continue;
                }

                $distanceData = self::calculateDistanceBasedLocationScore($prefLoc, $jobLocation);
                $score = $distanceData['score'];
                $distanceKm = $distanceData['distance_km'];

                if ($score > $bestPrefScore) {
                    $bestPrefScore = $score;
                }

                if ($distanceKm !== null && $distanceKm >= 0) {
                    if ($minPrefDistanceKm === null || $distanceKm < $minPrefDistanceKm) {
                        $minPrefDistanceKm = $distanceKm;
                        $prefLabelAtMinDistance = $prefLoc;
                    }
                }

                if ($score >= 40) {
                    $matchedLocations[] = $prefLoc;
                }
            }
        }

        $anyScore = $hasAny ? 80 : 0;
        // Overall location factor (for weighting) — best of home, preferred work, or "any"
        $finalScore = max($currentScoreBoosted, $bestPrefScore, $anyScore);

        $dc = $currentData['distance_km'];
        $basis = 'none';
        $displayDistanceKm = null;
        $isNearbyCurrent = false;

        // UI basis: whichever anchor is nearer (km). Tie → home. If only one has coordinates, use that.
        if ($dc !== null && $minPrefDistanceKm !== null) {
            if ($dc <= $minPrefDistanceKm) {
                $basis = 'current';
                $displayDistanceKm = $dc;
                $isNearbyCurrent = true;
            } else {
                $basis = 'preferred';
                $displayDistanceKm = $minPrefDistanceKm;
            }
        } elseif ($dc !== null) {
            $basis = 'current';
            $displayDistanceKm = $dc;
            $isNearbyCurrent = true;
        } elseif ($minPrefDistanceKm !== null) {
            $basis = 'preferred';
            $displayDistanceKm = $minPrefDistanceKm;
        } elseif ($hasAny && $finalScore > 0) {
            $basis = 'any';
            $displayDistanceKm = null;
        }

        return [
            'score' => $finalScore,
            'matched_locations' => array_values(array_unique($matchedLocations)),
            'distance_km' => $displayDistanceKm,
            'is_nearby_current' => $isNearbyCurrent,
            'location_basis' => $basis,
            'nearest_preferred_label' => $prefLabelAtMinDistance,
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

            $fuzzyScore = self::calculateFuzzyOccupationScore($prefOcc, $jobTitle);
            $semanticScore = self::calculateSemanticOccupationScore($prefOcc, $jobTitle);
            $score = max($fuzzyScore, $semanticScore);

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
    'nutritionist' => ['diet', 'nutrition', 'meal planning', 'healthcare', 'wellness', 'consulting', 'assessment', 'patient education'],

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
    'motorcycle taxi driver (habal-habal)' => ['driving', 'passenger service', 'navigation', 'safety', 'time management'],
    // --- Requested Additions (User-Specified Roles) ---
    'utility worker / janitor' => ['cleaning', 'sanitation', 'facility maintenance', 'waste management', 'safety', 'housekeeping'],
    'store helper / stock clerk' => ['inventory', 'stocking', 'merchandising', 'organization', 'customer service', 'retail operations'],
    'fast food crew' => ['food preparation', 'customer service', 'cash handling', 'cleaning', 'teamwork', 'order processing'],
    'sales associate / promodiser' => ['sales', 'product promotion', 'customer engagement', 'retail', 'merchandising', 'communication'],
    'construction laborer' => ['construction', 'manual labor', 'tool handling', 'site safety', 'teamwork', 'material handling'],
    'laundry staff' => ['washing', 'drying', 'folding', 'fabric care', 'housekeeping', 'customer service'],
    'house helper (kasambahay)' => ['housekeeping', 'cleaning', 'childcare', 'cooking', 'time management', 'trustworthiness'],
    'car wash attendant' => ['vehicle cleaning', 'detailing', 'customer service', 'time management', 'quality service', 'manual work'],
    'delivery rider' => ['delivery', 'navigation', 'time management', 'customer service', 'motorcycle', 'route planning'],
    'farm worker' => ['planting', 'harvesting', 'crop maintenance', 'manual labor', 'irrigation', 'teamwork'],
    'fisherman' => ['fishing', 'boat handling', 'net repair', 'catch handling', 'safety', 'endurance'],
    'parking attendant' => ['traffic guidance', 'vehicle assistance', 'customer service', 'monitoring', 'organization', 'safety'],
    'traffic aide / enforcer assistant' => ['traffic management', 'public safety', 'road coordination', 'communication', 'incident reporting', 'discipline'],
    'cook / kitchen staff' => ['food prep', 'cooking', 'kitchen safety', 'cleanliness', 'time management', 'teamwork'],
    'hairdresser / barber' => ['hair cutting', 'styling', 'grooming', 'customer service', 'hygiene', 'communication'],
    'aircon technician' => ['air conditioning', 'hvac maintenance', 'troubleshooting', 'installation', 'repair', 'safety'],
    'auto mechanic' => ['automotive diagnostics', 'engine repair', 'vehicle maintenance', 'troubleshooting', 'tools', 'safety'],
    'tailor / seamstress' => ['sewing', 'alteration', 'pattern making', 'garment construction', 'fabric handling', 'attention to detail'],
    'computer technician' => ['hardware repair', 'software installation', 'troubleshooting', 'network setup', 'technical support', 'maintenance'],
    'baker' => ['baking', 'food prep', 'oven operation', 'recipe adherence', 'quality control', 'sanitation'],
    'driver (professional)' => ['driving', 'route planning', 'vehicle care', 'safety compliance', 'customer service', 'logistics'],
    'call center agent (bpo)' => ['customer service', 'call handling', 'crm', 'problem solving', 'communication', 'process adherence'],
    'office staff / clerk' => ['documentation', 'filing', 'data entry', 'office support', 'communication', 'organization'],
    'admin assistant' => ['scheduling', 'documentation', 'coordination', 'office administration', 'communication', 'reporting'],
    'bank teller' => ['cash handling', 'customer service', 'transaction processing', 'compliance', 'accuracy', 'banking operations'],
    'hotel front desk staff' => ['front desk', 'guest service', 'booking', 'hospitality', 'communication', 'problem resolution'],
    'retail supervisor' => ['store operations', 'team supervision', 'inventory control', 'sales monitoring', 'customer service', 'reporting'],
    'warehouse coordinator' => ['inventory coordination', 'dispatch', 'logistics', 'warehouse operations', 'tracking', 'team coordination'],
    'sales representative' => ['sales', 'lead generation', 'negotiation', 'client relations', 'crm', 'closing'],
    'it support specialist' => ['technical support', 'helpdesk', 'troubleshooting', 'hardware', 'software', 'ticketing'],
    'web developer' => ['html', 'css', 'javascript', 'frontend', 'backend', 'responsive design'],
    'graphic designer' => ['design', 'photoshop', 'illustrator', 'branding', 'layout', 'visual communication'],
    'draftsman' => ['autocad', 'technical drawing', 'blueprints', 'drafting', 'design detailing', 'construction docs'],
    'laboratory technician' => ['laboratory procedures', 'sample handling', 'testing', 'documentation', 'equipment operation', 'quality control'],
    'engineering technician' => ['technical support', 'equipment maintenance', 'calibration', 'testing', 'troubleshooting', 'documentation'],
    'network technician' => ['network setup', 'cabling', 'router configuration', 'troubleshooting', 'network maintenance', 'it support'],
    'video editor' => ['video editing', 'premiere', 'after effects', 'storytelling', 'color correction', 'audio sync'],
    'hr officer' => ['recruitment', 'employee relations', 'documentation', 'hr compliance', 'onboarding', 'coordination'],
    'marketing officer' => ['campaign planning', 'branding', 'digital marketing', 'market research', 'analytics', 'communications'],
    'accountant (non-cpa)' => ['bookkeeping', 'financial records', 'tax prep', 'reconciliation', 'reporting', 'accounting software'],
    'registered nurse' => ['patient care', 'clinical monitoring', 'medication administration', 'charting', 'healthcare', 'compassion'],
    'medical doctor' => ['diagnosis', 'treatment', 'patient consultation', 'clinical care', 'medical decision making', 'documentation'],
    'lawyer' => ['legal research', 'litigation', 'contracts', 'advisory', 'compliance', 'case management'],
    'licensed teacher' => ['teaching', 'lesson planning', 'classroom management', 'assessment', 'student engagement', 'curriculum'],
    'engineer' => ['engineering principles', 'problem solving', 'design', 'analysis', 'project execution', 'technical documentation'],
    'architect' => ['architectural design', 'space planning', 'autocad', '3d modeling', 'construction documents', 'client presentation'],
    'pharmacist' => ['dispensing', 'drug interaction checks', 'patient counseling', 'inventory control', 'pharmacy operations', 'compliance'],
    'dentist' => ['oral diagnosis', 'dental procedures', 'patient care', 'clinic operations', 'sterilization', 'record keeping'],
    'criminologist' => ['criminal investigation', 'law enforcement support', 'forensic basics', 'report writing', 'analysis', 'public safety'],
    'licensed psychologist' => ['psychological assessment', 'therapy', 'counseling', 'mental health', 'case notes', 'ethics'],
    'pilot' => ['aviation', 'flight operations', 'navigation', 'safety protocols', 'aircraft systems', 'decision making'],
    'ship captain / marine engineer' => ['marine navigation', 'vessel operations', 'engine maintenance', 'maritime safety', 'crew management', 'logistics'],
    'medical specialist (surgeon etc)' => ['specialized diagnosis', 'surgical procedures', 'patient management', 'hospital practice', 'clinical leadership', 'evidence-based care'],
    'software engineer' => ['software development', 'coding', 'system design', 'debugging', 'version control', 'testing'],
    'ai engineer' => ['machine learning', 'deep learning', 'nlp', 'model training', 'python', 'data pipelines'],
    'data scientist' => ['data analysis', 'machine learning', 'statistics', 'feature engineering', 'modeling', 'visualization'],
    'petroleum engineer' => ['reservoir analysis', 'drilling operations', 'production optimization', 'safety compliance', 'energy systems', 'engineering'],
    'automation engineer' => ['automation', 'plc', 'industrial control', 'robotics', 'process optimization', 'troubleshooting'],
    'aerospace engineer' => ['aerodynamics', 'aircraft systems', 'simulation', 'design analysis', 'safety', 'testing'],
    'corporate executive' => ['strategic planning', 'leadership', 'operations oversight', 'financial management', 'stakeholder management', 'decision making'],
    'investment banker' => ['financial modeling', 'valuation', 'capital markets', 'deal structuring', 'client advisory', 'analysis'],
    'government official' => ['public administration', 'policy implementation', 'governance', 'compliance', 'stakeholder coordination', 'public service'],
    'senior military officer' => ['leadership', 'operations planning', 'discipline', 'security strategy', 'resource management', 'mission execution'],
    'ceo / company president' => ['executive leadership', 'corporate strategy', 'business growth', 'stakeholder relations', 'financial oversight', 'decision making'],
    'university professor' => ['higher education', 'research', 'lecturing', 'curriculum development', 'academic advising', 'publication'],
    'judge / justice' => ['judicial review', 'legal reasoning', 'court procedure', 'case adjudication', 'ethics', 'constitutional law'],
    'international organization staff (un adb etc)' => ['international development', 'policy analysis', 'program implementation', 'stakeholder engagement', 'reporting', 'multilateral coordination'],

    // --- Additional PH In-Demand Jobs (Curated Additions) ---
    'cashier (supermarket)' => ['point of sale', 'cash handling', 'barcode scanning', 'customer service', 'transaction accuracy', 'retail operations'],
    'cashier (convenience store)' => ['point of sale', 'cash handling', 'stocking', 'customer service', 'inventory awareness', 'store operations'],
    'grocery merchandiser' => ['merchandising', 'planogram', 'stock replenishment', 'retail display', 'inventory', 'coordination'],
    'sari-sari store assistant' => ['cash handling', 'inventory', 'customer service', 'pricing', 'stocking', 'record keeping'],
    'pharmacy cashier' => ['cash handling', 'customer service', 'point of sale', 'receipt processing', 'retail pharmacy', 'accuracy'],
    'production operator' => ['machine operation', 'production line', 'quality checks', 'safety compliance', 'troubleshooting', 'shift work'],
    'machine operator' => ['machine setup', 'equipment operation', 'quality control', 'maintenance basics', 'safety', 'production targets'],
    'factory worker' => ['assembly', 'packaging', 'quality checks', 'manual labor', 'safety', 'teamwork'],
    'qa inspector' => ['quality inspection', 'sampling', 'defect detection', 'documentation', 'standards compliance', 'reporting'],
    'qa supervisor' => ['quality assurance', 'team supervision', 'inspection planning', 'root cause analysis', 'compliance', 'reporting'],
    'production planner' => ['production scheduling', 'capacity planning', 'inventory coordination', 'forecasting', 'process optimization', 'reporting'],
    'procurement officer' => ['vendor sourcing', 'purchase orders', 'price negotiation', 'supplier management', 'procurement', 'documentation'],
    'purchasing officer' => ['purchase requests', 'vendor coordination', 'quotation analysis', 'procurement', 'inventory support', 'compliance'],
    'supply chain coordinator' => ['supply chain', 'inventory flow', 'dispatch planning', 'vendor coordination', 'logistics', 'reporting'],
    'inventory clerk' => ['stock counting', 'inventory records', 'receiving', 'issuance', 'warehouse support', 'accuracy'],
    'stock controller' => ['inventory control', 'reorder levels', 'stock audit', 'reporting', 'warehouse operations', 'coordination'],
    'warehouse checker' => ['receiving', 'item verification', 'count accuracy', 'documentation', 'warehouse operations', 'quality checks'],
    'warehouse picker' => ['order picking', 'packing', 'inventory location', 'warehouse safety', 'speed', 'accuracy'],
    'forklift operator' => ['forklift driving', 'material handling', 'warehouse safety', 'loading', 'unloading', 'equipment checks'],
    'dispatcher' => ['dispatch scheduling', 'route coordination', 'driver communication', 'tracking', 'logistics', 'incident handling'],
    'freight forwarding staff' => ['shipment documentation', 'customs coordination', 'cargo tracking', 'logistics', 'client updates', 'compliance'],
    'import export coordinator' => ['import documentation', 'export permits', 'customs process', 'shipping coordination', 'compliance', 'reporting'],
    'customs broker assistant' => ['customs documentation', 'tariff classification', 'broker support', 'compliance', 'shipment coordination', 'record keeping'],
    'bookkeeping assistant' => ['bookkeeping', 'voucher preparation', 'ledger posting', 'reconciliation', 'documentation', 'accuracy'],
    'accounts payable staff' => ['invoice validation', 'payment processing', 'vendor reconciliation', 'ap', 'documentation', 'reporting'],
    'accounts receivable staff' => ['billing', 'collection monitoring', 'customer ledger', 'ar reconciliation', 'documentation', 'reporting'],
    'billing staff' => ['billing preparation', 'invoice issuance', 'account validation', 'customer support', 'documentation', 'accuracy'],
    'credit and collection staff' => ['collections', 'aging report', 'payment follow up', 'customer communication', 'documentation', 'negotiation'],
    'treasury assistant' => ['cash management', 'bank transactions', 'fund monitoring', 'treasury support', 'reconciliation', 'reporting'],
    'payroll assistant' => ['timesheet processing', 'salary computation', 'statutory deductions', 'payroll documentation', 'hr coordination', 'accuracy'],
    'tax accountant' => ['tax filing', 'bir compliance', 'withholding tax', 'vat', 'documentation', 'audit support'],
    'internal control officer' => ['control testing', 'risk mitigation', 'process review', 'compliance', 'audit coordination', 'reporting'],
    'csr (voice)' => ['voice support', 'customer service', 'call handling', 'crm', 'problem resolution', 'communication'],
    'csr (non voice)' => ['chat support', 'email support', 'ticket handling', 'customer service', 'documentation', 'response management'],
    'chat support agent' => ['chat handling', 'customer support', 'ticketing', 'typing speed', 'problem solving', 'service quality'],
    'email support specialist' => ['email handling', 'customer support', 'case documentation', 'ticketing', 'sla adherence', 'communication'],
    'technical support engineer' => ['technical troubleshooting', 'incident resolution', 'system diagnostics', 'customer support', 'ticketing', 'documentation'],
    'service desk analyst' => ['ticket management', 'itil basics', 'incident triage', 'technical support', 'sla', 'documentation'],
    'workforce analyst' => ['forecasting', 'scheduling', 'wfm', 'real-time monitoring', 'reporting', 'analytics'],
    'real time analyst' => ['queue monitoring', 'staffing adjustments', 'sla monitoring', 'wfm tools', 'reporting', 'coordination'],
    'quality analyst (bpo)' => ['call evaluation', 'quality scoring', 'coaching feedback', 'process compliance', 'reporting', 'analytics'],
    'trainer (bpo)' => ['new hire training', 'process training', 'facilitation', 'assessment', 'coaching', 'performance tracking'],
    'team leader (bpo)' => ['team supervision', 'performance coaching', 'queue management', 'customer service', 'reporting', 'people management'],
    'operations manager (bpo)' => ['operations oversight', 'kpi management', 'client management', 'process improvement', 'team leadership', 'reporting'],
    'virtual receptionist' => ['inbound calls', 'appointment scheduling', 'customer communication', 'email handling', 'crm', 'administrative support'],
    'ecommerce customer support' => ['order inquiries', 'returns handling', 'chat support', 'email support', 'platform tools', 'customer service'],
    'amazon va' => ['product listing', 'order management', 'customer service', 'inventory monitoring', 'marketplace tools', 'reporting'],
    'shopify va' => ['shopify admin', 'order fulfillment', 'product upload', 'customer support', 'inventory', 'basic analytics'],
    'social media va' => ['social posting', 'content scheduling', 'community management', 'basic design', 'engagement', 'reporting'],
    'medical va' => ['appointment scheduling', 'patient communication', 'ehr updates', 'insurance verification', 'documentation', 'hipaa awareness'],
    'real estate va' => ['lead management', 'listing updates', 'appointment setting', 'crm', 'email management', 'documentation'],
    'appointment setter' => ['lead calling', 'schedule management', 'crm updates', 'communication', 'follow ups', 'target achievement'],
    'telemarketer' => ['outbound calls', 'lead generation', 'product pitching', 'objection handling', 'crm', 'sales targets'],
    'inside sales agent' => ['sales calls', 'lead qualification', 'crm', 'proposal follow up', 'closing support', 'communication'],
    'field sales agent' => ['field selling', 'client visits', 'territory management', 'product demo', 'collection support', 'reporting'],
    'medical representative' => ['product presentation', 'doctor engagement', 'territory coverage', 'sales reporting', 'compliance', 'relationship management'],
    'promotions staff' => ['product sampling', 'brand promotion', 'customer engagement', 'retail coordination', 'sales support', 'communication'],
    'trade marketing officer' => ['trade campaigns', 'retail execution', 'merchandising', 'channel coordination', 'reporting', 'brand support'],
    'category buyer' => ['category planning', 'vendor negotiation', 'margin analysis', 'assortment planning', 'procurement', 'reporting'],
    'branch operations officer' => ['branch operations', 'customer service', 'cash control', 'compliance', 'team coordination', 'reporting'],
    'branch manager' => ['branch leadership', 'sales management', 'operations', 'compliance', 'team development', 'kpi monitoring'],
    'loan processor' => ['loan documentation', 'application verification', 'credit file review', 'compliance', 'coordination', 'accuracy'],
    'credit investigator' => ['background verification', 'credit assessment', 'field investigation', 'report writing', 'risk evaluation', 'compliance'],
    'claims processor' => ['claims review', 'documentation validation', 'policy checks', 'customer updates', 'turnaround management', 'reporting'],
    'underwriting assistant' => ['policy screening', 'risk data gathering', 'documentation', 'underwriting support', 'accuracy', 'compliance'],
    'hr generalist' => ['recruitment', 'employee relations', 'policy implementation', 'hr documentation', 'compensation support', 'compliance'],
    'talent acquisition specialist' => ['sourcing', 'screening', 'interview coordination', 'employer branding', 'hiring process', 'stakeholder management'],
    'compensation and benefits officer' => ['benefits administration', 'salary structuring', 'government remittances', 'policy communication', 'data accuracy', 'compliance'],
    'training officer' => ['training needs analysis', 'module development', 'facilitation', 'evaluation', 'learning programs', 'coordination'],
    'employee engagement officer' => ['engagement programs', 'event coordination', 'internal communication', 'culture initiatives', 'feedback analysis', 'reporting'],
    'safety officer (osh)' => ['occupational safety', 'hazard assessment', 'incident investigation', 'safety training', 'compliance', 'reporting'],
    'pollution control officer' => ['environmental compliance', 'waste monitoring', 'permit management', 'inspection', 'documentation', 'reporting'],
    'property custodian' => ['asset inventory', 'property records', 'issuance and return', 'documentation', 'audit support', 'coordination'],
    'records management officer' => ['records classification', 'archiving', 'retrieval', 'documentation control', 'compliance', 'accuracy'],
    'document controller' => ['document versioning', 'filing systems', 'approval workflows', 'compliance', 'audit trail', 'coordination'],
    'executive assistant' => ['calendar management', 'travel coordination', 'meeting support', 'confidential documentation', 'communication', 'administrative excellence'],
    'legal secretary' => ['legal documentation', 'calendar docketing', 'client communication', 'filing', 'case support', 'confidentiality'],
    'paralegal assistant' => ['legal research', 'document drafting', 'case file organization', 'court filing support', 'compliance', 'detail orientation'],
    'court stenographer' => ['stenography', 'transcription', 'court proceedings', 'accuracy', 'legal terminology', 'documentation'],
    'notarial staff' => ['document verification', 'identity checks', 'record keeping', 'client coordination', 'compliance', 'accuracy'],
    'construction foreman' => ['site supervision', 'crew management', 'work scheduling', 'quality checks', 'safety compliance', 'coordination'],
    'site engineer' => ['site execution', 'plan interpretation', 'quantity monitoring', 'coordination', 'quality control', 'reporting'],
    'project engineer' => ['project coordination', 'technical planning', 'cost awareness', 'quality assurance', 'schedule tracking', 'reporting'],
    'estimator' => ['cost estimation', 'quantity takeoff', 'boq preparation', 'pricing analysis', 'documentation', 'coordination'],
    'autocad operator' => ['autocad', 'technical drafting', 'layout preparation', 'revision control', 'attention to detail', 'documentation'],
    'bim modeler' => ['bim software', '3d modeling', 'clash detection', 'construction documentation', 'coordination', 'technical drafting'],
    'mep engineer' => ['mechanical electrical plumbing', 'design coordination', 'system calculations', 'construction support', 'compliance', 'testing'],
    'qa qc engineer' => ['qa qc', 'inspection test plans', 'quality documentation', 'nonconformance tracking', 'compliance', 'reporting'],
    'quantity surveyor assistant' => ['measurement', 'cost tracking', 'variation monitoring', 'documentation', 'coordination', 'boq support'],
    'survey aide' => ['land survey support', 'field measurements', 'equipment handling', 'data recording', 'coordination', 'accuracy'],
    'rebar detailer' => ['rebar detailing', 'structural drawing interpretation', 'quantity estimation', 'drafting', 'coordination', 'accuracy'],
    'steel fabricator' => ['metal fabrication', 'cutting', 'welding', 'blueprint reading', 'quality checks', 'safety'],
    'tile setter' => ['tile installation', 'surface preparation', 'measurement', 'finishing', 'quality workmanship', 'safety'],
    'painter' => ['surface preparation', 'painting', 'coating application', 'finishing', 'color matching', 'safety'],
    'masonry worker' => ['bricklaying', 'concrete mixing', 'plastering', 'site safety', 'manual skills', 'teamwork'],
    'equipment maintenance technician' => ['preventive maintenance', 'repair', 'troubleshooting', 'equipment checks', 'safety', 'documentation'],
    'motorpool coordinator' => ['vehicle scheduling', 'maintenance tracking', 'fuel monitoring', 'documentation', 'coordination', 'compliance'],
    'fleet supervisor' => ['fleet operations', 'driver management', 'route optimization', 'maintenance planning', 'safety compliance', 'reporting'],
    'shipping coordinator' => ['shipment scheduling', 'carrier coordination', 'documentation', 'tracking', 'logistics', 'communication'],
    'cold storage staff' => ['cold chain handling', 'inventory', 'temperature monitoring', 'warehouse safety', 'documentation', 'coordination'],
    'food safety officer' => ['food safety compliance', 'haccp', 'sanitation monitoring', 'inspection', 'training', 'documentation'],
    'quality assurance analyst (food)' => ['food quality checks', 'sampling', 'compliance', 'reporting', 'root cause analysis', 'documentation'],
    'commissary staff' => ['food preparation', 'portioning', 'inventory handling', 'sanitation', 'teamwork', 'production support'],
    'pastry chef' => ['pastry production', 'baking', 'recipe development', 'food presentation', 'quality control', 'sanitation'],
    'kitchen steward' => ['kitchen cleaning', 'dishwashing', 'sanitation', 'waste handling', 'kitchen support', 'teamwork'],
    'restaurant cashier' => ['pos operations', 'cash handling', 'customer service', 'order coordination', 'accuracy', 'teamwork'],
    'dining staff' => ['table service', 'customer assistance', 'order accuracy', 'hospitality', 'communication', 'teamwork'],
    'guest relations officer' => ['guest assistance', 'issue resolution', 'service recovery', 'hospitality', 'communication', 'coordination'],
    'reservation agent' => ['booking management', 'customer communication', 'system encoding', 'rate handling', 'hospitality', 'accuracy'],
    'housekeeping supervisor' => ['housekeeping operations', 'room inspection', 'staff scheduling', 'quality standards', 'inventory', 'coordination'],
    'room attendant' => ['room cleaning', 'linen replacement', 'guest support', 'housekeeping standards', 'time management', 'attention to detail'],
    'bellman' => ['luggage handling', 'guest assistance', 'hotel operations support', 'communication', 'hospitality', 'coordination'],
    'concierge staff' => ['guest requests', 'local information', 'reservation support', 'hospitality', 'communication', 'problem solving'],
    'spa therapist' => ['spa treatments', 'client care', 'wellness', 'hygiene', 'service quality', 'communication'],
    'gym instructor' => ['fitness coaching', 'exercise guidance', 'member engagement', 'safety', 'program planning', 'communication'],
    'lifeguard' => ['water safety', 'rescue response', 'first aid', 'vigilance', 'public safety', 'communication'],
    'cctv operator' => ['cctv monitoring', 'incident detection', 'reporting', 'security protocols', 'attention to detail', 'communication'],
    'loss prevention officer' => ['loss prevention', 'inventory shrinkage control', 'surveillance', 'incident reporting', 'retail security', 'coordination'],
    'fire safety officer' => ['fire safety inspection', 'emergency drills', 'incident response', 'compliance', 'training', 'reporting'],
    'barangay health worker' => ['community health support', 'patient monitoring', 'health education', 'home visits', 'documentation', 'public service'],
    'public school clerk' => ['school records', 'documentation', 'student file handling', 'office support', 'coordination', 'communication'],
    'registrar staff' => ['records processing', 'enrollment support', 'documentation', 'database encoding', 'coordination', 'accuracy'],
    'guidance office staff' => ['student support', 'appointment coordination', 'documentation', 'communication', 'confidentiality', 'office administration'],
    'lgu administrative aide' => ['public records', 'documentation', 'office support', 'citizen assistance', 'coordination', 'compliance'],
    'social welfare aide' => ['community assistance', 'case documentation', 'beneficiary coordination', 'public service', 'communication', 'reporting'],
    'drrm staff' => ['disaster preparedness', 'emergency coordination', 'relief logistics', 'incident reporting', 'community outreach', 'public safety'],
    'election support staff' => ['voter assistance', 'poll operations', 'documentation', 'queue management', 'public service', 'coordination'],
    'permit processor' => ['application review', 'permit encoding', 'compliance checks', 'documentation', 'customer assistance', 'coordination'],
    'utility maintenance staff' => ['facility upkeep', 'minor repairs', 'cleanliness', 'equipment checks', 'safety', 'manual work'],
    'maintenance electrician' => ['electrical maintenance', 'troubleshooting', 'preventive checks', 'repair', 'safety', 'documentation'],
    'maintenance plumber' => ['plumbing maintenance', 'leak repair', 'pipe checks', 'facility support', 'tools', 'safety'],
    'airline ground staff' => ['passenger assistance', 'ground operations', 'flight support coordination', 'communication', 'safety compliance', 'service quality'],
    'airport check in agent' => ['check in processing', 'passenger service', 'document verification', 'reservation systems', 'communication', 'accuracy'],
    'seafarer deck crew' => ['deck operations', 'maritime safety', 'navigation support', 'maintenance', 'teamwork', 'discipline'],
    'engine cadet' => ['engine room support', 'marine systems', 'preventive maintenance', 'safety', 'technical learning', 'discipline'],
    'marine fitter' => ['marine equipment fitting', 'welding', 'fabrication', 'repair', 'safety', 'technical skills'],
    'ship electrician' => ['marine electrical systems', 'troubleshooting', 'maintenance', 'safety', 'technical documentation', 'repair'],
    'port operations staff' => ['cargo coordination', 'port safety', 'documentation', 'logistics', 'communication', 'operations support'],
    'freight handler' => ['cargo handling', 'loading and unloading', 'warehouse coordination', 'safety', 'inventory checks', 'teamwork'],
    'ofw documentation staff' => ['document processing', 'visa support', 'compliance checks', 'client coordination', 'record keeping', 'accuracy'],
    'visa processing officer' => ['visa documentation', 'application review', 'compliance', 'client communication', 'tracking', 'reporting'],
    'recruitment coordinator (ofw)' => ['candidate processing', 'interview scheduling', 'document compliance', 'agency coordination', 'communication', 'reporting'],
    'event assistant' => ['event support', 'setup coordination', 'guest assistance', 'vendor follow up', 'documentation', 'teamwork'],
    'photobooth attendant' => ['equipment setup', 'customer service', 'event support', 'basic troubleshooting', 'coordination', 'time management'],
    'studio assistant' => ['equipment handling', 'schedule support', 'client assistance', 'documentation', 'production support', 'organization'],
    'content moderator' => ['content review', 'policy enforcement', 'quality checks', 'documentation', 'attention to detail', 'compliance'],
    'community manager' => ['online community management', 'engagement', 'content coordination', 'customer support', 'analytics', 'communication'],
    'seo outreach specialist' => ['link building', 'outreach communication', 'seo', 'content coordination', 'reporting', 'relationship management'],
    'ads specialist' => ['paid ads', 'campaign setup', 'audience targeting', 'performance analysis', 'budget optimization', 'reporting'],
    'performance marketer' => ['campaign optimization', 'conversion tracking', 'analytics', 'a b testing', 'budget management', 'reporting'],
    'ux designer' => ['user research', 'wireframing', 'prototype testing', 'information architecture', 'usability', 'design thinking'],
    'wordpress developer' => ['wordpress', 'php', 'theme customization', 'plugin integration', 'responsive design', 'debugging'],
    'php developer' => ['php', 'mysql', 'backend development', 'api integration', 'debugging', 'version control'],
    'laravel developer' => ['laravel', 'php', 'mvc', 'rest api', 'database design', 'testing'],
    'react developer' => ['react', 'javascript', 'state management', 'component design', 'api integration', 'frontend development'],
    'node js developer' => ['node js', 'express', 'api development', 'database integration', 'backend', 'testing'],
    'android developer' => ['android', 'kotlin', 'java', 'mobile ui', 'api integration', 'debugging'],
    'ios developer' => ['ios', 'swift', 'mobile development', 'api integration', 'ui kit', 'testing'],
    'qa tester' => ['manual testing', 'test case execution', 'bug reporting', 'regression testing', 'quality assurance', 'documentation'],
    'automation tester' => ['test automation', 'selenium', 'cypress', 'script maintenance', 'qa', 'reporting'],
    'business intelligence developer' => ['power bi', 'tableau', 'sql', 'data modeling', 'dashboard creation', 'reporting'],
    'database developer' => ['sql development', 'query optimization', 'database schema', 'stored procedures', 'performance tuning', 'documentation'],
    'network engineer' => ['network design', 'routing switching', 'firewall management', 'troubleshooting', 'monitoring', 'security'],
    'systems analyst' => ['requirements analysis', 'process mapping', 'solution design', 'documentation', 'stakeholder coordination', 'testing support'],
    'application support analyst' => ['incident handling', 'application troubleshooting', 'ticket resolution', 'sql basics', 'communication', 'documentation'],
    'erp support specialist' => ['erp troubleshooting', 'user support', 'process training', 'configuration assistance', 'documentation', 'coordination'],
    'sap encoder' => ['sap data entry', 'transaction encoding', 'accuracy', 'documentation', 'process compliance', 'reporting'],
    'government accountant' => ['government accounting', 'budget utilization', 'financial reporting', 'audit support', 'compliance', 'documentation'],
    'budget officer' => ['budget preparation', 'expense monitoring', 'financial analysis', 'reporting', 'coordination', 'compliance'],
    'planning officer' => ['program planning', 'data analysis', 'project monitoring', 'reporting', 'coordination', 'documentation'],
    'monitoring and evaluation officer' => ['m and e', 'indicator tracking', 'data collection', 'impact reporting', 'analysis', 'stakeholder communication'],
    'public information officer' => ['public communication', 'media coordination', 'content writing', 'documentation', 'stakeholder engagement', 'branding'],
    'customer success specialist' => ['customer onboarding', 'retention', 'product adoption', 'relationship management', 'issue escalation', 'reporting'],
    'account manager (saas)' => ['client onboarding', 'renewals', 'upselling', 'product support coordination', 'relationship management', 'reporting'],
    'field service technician' => ['onsite troubleshooting', 'equipment repair', 'preventive maintenance', 'customer communication', 'documentation', 'safety'],
    'biomedical technician' => ['medical equipment maintenance', 'calibration', 'troubleshooting', 'documentation', 'hospital support', 'compliance'],
    'dialysis technician' => ['dialysis machine operation', 'patient support', 'clinical protocols', 'monitoring', 'documentation', 'safety'],
    'care coordinator' => ['patient coordination', 'appointment management', 'care planning', 'communication', 'documentation', 'healthcare support'],
    'clinic secretary' => ['appointment scheduling', 'patient records', 'front desk support', 'documentation', 'communication', 'confidentiality'],
    'dental receptionist' => ['clinic scheduling', 'patient assistance', 'record management', 'billing support', 'communication', 'customer service'],
    'ambulance driver' => ['emergency driving', 'patient transport', 'route navigation', 'vehicle readiness', 'safety', 'coordination'],
    'emergency medical technician' => ['emergency response', 'basic life support', 'patient stabilization', 'documentation', 'team coordination', 'safety'],
    'bi analyst' => ['business intelligence', 'dashboarding', 'sql', 'data visualization', 'reporting', 'stakeholder support']
];

    /**
     * Gets all related semantic terms for a given term.
     * Focused expansion to avoid irrelevant cross-matches.
     */
    private static function getSemanticTerms($term) {
        $term = strtolower(trim($term));
        $terms = [$term];
        $semantic = self::getUnifiedSemanticMap();
        
        // 1. If it's a primary category (key), get its direct synonyms
        if (isset($semantic[$term])) {
            $terms = array_merge($terms, $semantic[$term]);
        }
        
        // 2. If it's a synonym, get the primary category (key) it belongs to
        // We do NOT add all other synonyms of that key to prevent "transitive" mismatching
        foreach ($semantic as $key => $synonyms) {
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