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
            'visual designer', 'freelance designer', 'ui designer', 'ui/ux designer', 'illustrator'
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

        'graphic designer' => [
            'visual designer', 'freelance designer', 'ui designer', 'ui/ux designer', 'illustrator', 'layout artist'
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
                $map[$term] = array_values(array_unique(array_merge($map[$term], array_slice($related, 0, 120))));
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
        $maxGeneratedEntries = 12000; // 10,000+ target while keeping runtime manageable.

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
            $generated[$t] = array_values(array_unique(array_merge($generated[$t], array_slice($cleanSkills, 0, 40))));
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

        // Result: 10,000+ generated job-semantic examples (capped by maxGeneratedEntries).
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
    'motorcycle taxi driver (habal-habal)' => ['driving', 'passenger service', 'navigation', 'safety', 'time management'],
    // --- Massive Additive Occupation Dataset (1050 New Entries) ---
    'healthcare operations coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'operations', 'coordination', 'planning', 'execution'],
    'healthcare support specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'support', 'problem solving', 'communication', 'service'],
    'healthcare service analyst' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'analysis', 'service quality', 'reporting', 'kpi'],
    'healthcare compliance assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'compliance', 'documentation', 'audit support', 'policy'],
    'healthcare quality officer' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'healthcare documentation officer' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'documentation', 'records', 'accuracy', 'filing'],
    'healthcare field technician' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'healthcare planning assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'planning', 'scheduling', 'coordination', 'organization'],
    'healthcare project associate' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'project support', 'tracking', 'coordination', 'reporting'],
    'healthcare training coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'training', 'facilitation', 'learning', 'assessment'],
    'healthcare customer care specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'customer service', 'communication', 'resolution', 'crm'],
    'healthcare data processing assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'data entry', 'processing', 'accuracy', 'reporting'],
    'healthcare process improvement analyst' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'healthcare risk control assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'healthcare resource coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'healthcare workflow specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'healthcare performance analyst' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'healthcare delivery coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'delivery', 'routing', 'coordination', 'tracking'],
    'healthcare logistics assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'healthcare administrative officer' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'administration', 'documentation', 'organization', 'coordination'],
    'healthcare reporting analyst' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'reporting', 'analysis', 'dashboards', 'data'],
    'healthcare inventory controller' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'healthcare maintenance coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'healthcare safety officer' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'healthcare site coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'site operations', 'coordination', 'safety', 'reporting'],
    'healthcare procurement assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'healthcare vendor coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'healthcare records officer' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'records management', 'documentation', 'filing', 'accuracy'],
    'healthcare client support specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'client support', 'communication', 'issue resolution', 'service'],
    'healthcare implementation assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'implementation', 'configuration', 'support', 'documentation'],
    'healthcare integration specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'integration', 'systems', 'testing', 'technical support'],
    'healthcare monitoring analyst' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'healthcare audit assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'audit support', 'documentation', 'compliance', 'verification'],
    'healthcare research assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'research', 'data collection', 'analysis', 'documentation'],
    'healthcare communications officer' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'healthcare product support specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'healthcare technical coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'technical coordination', 'planning', 'support', 'execution'],
    'healthcare service coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'healthcare operations supervisor' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'supervision', 'operations', 'team coordination', 'quality'],
    'healthcare analytics specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'analytics', 'reporting', 'data insights', 'kpi'],
    'healthcare engagement coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'engagement', 'communication', 'program support', 'coordination'],
    'healthcare program assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'program support', 'documentation', 'coordination', 'tracking'],
    'healthcare execution specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'execution', 'delivery', 'coordination', 'quality'],
    'healthcare solutions assistant' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'healthcare production coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'production', 'planning', 'quality', 'coordination'],
    'healthcare dispatch coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'dispatch', 'routing', 'tracking', 'coordination'],
    'healthcare onboarding specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'onboarding', 'training', 'documentation', 'support'],
    'healthcare account support specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'account support', 'client service', 'reporting', 'coordination'],
    'healthcare compliance coordinator' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'compliance', 'policy', 'audit support', 'documentation'],
    'healthcare quality assurance specialist' => ['patient safety', 'clinical support', 'health protocols', 'care coordination', 'quality assurance', 'testing', 'standards', 'reporting'],
    'finance operations coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'operations', 'coordination', 'planning', 'execution'],
    'finance support specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'support', 'problem solving', 'communication', 'service'],
    'finance service analyst' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'analysis', 'service quality', 'reporting', 'kpi'],
    'finance compliance assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'documentation', 'audit support', 'policy', 'teamwork'],
    'finance quality officer' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'finance documentation officer' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'documentation', 'records', 'accuracy', 'filing'],
    'finance field technician' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'finance planning assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'planning', 'scheduling', 'coordination', 'organization'],
    'finance project associate' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'project support', 'tracking', 'coordination', 'reporting'],
    'finance training coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'training', 'facilitation', 'learning', 'assessment'],
    'finance customer care specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'customer service', 'communication', 'resolution', 'crm'],
    'finance data processing assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'data entry', 'processing', 'accuracy', 'reporting'],
    'finance process improvement analyst' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'finance risk control assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'risk assessment', 'controls', 'monitoring', 'teamwork'],
    'finance resource coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'finance workflow specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'finance performance analyst' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'finance delivery coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'delivery', 'routing', 'coordination', 'tracking'],
    'finance logistics assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'finance administrative officer' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'administration', 'documentation', 'organization', 'coordination'],
    'finance reporting analyst' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'reporting', 'analysis', 'dashboards', 'data'],
    'finance inventory controller' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'finance maintenance coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'finance safety officer' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'safety', 'inspection', 'risk prevention', 'teamwork'],
    'finance site coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'site operations', 'coordination', 'safety', 'reporting'],
    'finance procurement assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'finance vendor coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'vendor relations', 'coordination', 'follow-up', 'teamwork'],
    'finance records officer' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'records management', 'documentation', 'filing', 'accuracy'],
    'finance client support specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'client support', 'communication', 'issue resolution', 'service'],
    'finance implementation assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'implementation', 'configuration', 'support', 'documentation'],
    'finance integration specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'integration', 'systems', 'testing', 'technical support'],
    'finance monitoring analyst' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'finance audit assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'audit support', 'documentation', 'verification', 'teamwork'],
    'finance research assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'research', 'data collection', 'analysis', 'documentation'],
    'finance communications officer' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'finance product support specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'finance technical coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'technical coordination', 'planning', 'support', 'execution'],
    'finance service coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'finance operations supervisor' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'supervision', 'operations', 'team coordination', 'quality'],
    'finance analytics specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'analytics', 'reporting', 'data insights', 'kpi'],
    'finance engagement coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'engagement', 'communication', 'program support', 'coordination'],
    'finance program assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'program support', 'documentation', 'coordination', 'tracking'],
    'finance execution specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'execution', 'delivery', 'coordination', 'quality'],
    'finance solutions assistant' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'finance production coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'production', 'planning', 'quality', 'coordination'],
    'finance dispatch coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'dispatch', 'routing', 'tracking', 'coordination'],
    'finance onboarding specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'onboarding', 'training', 'documentation', 'support'],
    'finance account support specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'account support', 'client service', 'reporting', 'coordination'],
    'finance compliance coordinator' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'policy', 'audit support', 'documentation', 'teamwork'],
    'finance quality assurance specialist' => ['financial reporting', 'compliance', 'reconciliation', 'risk checks', 'quality assurance', 'testing', 'standards', 'reporting'],
    'education operations coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'operations', 'coordination', 'planning', 'execution'],
    'education support specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'support', 'problem solving', 'communication', 'service'],
    'education service analyst' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'analysis', 'service quality', 'reporting', 'kpi'],
    'education compliance assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'compliance', 'documentation', 'audit support', 'policy'],
    'education quality officer' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'education documentation officer' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'documentation', 'records', 'accuracy', 'filing'],
    'education field technician' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'education planning assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'planning', 'scheduling', 'coordination', 'organization'],
    'education project associate' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'project support', 'tracking', 'coordination', 'reporting'],
    'education training coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'training', 'facilitation', 'learning', 'teamwork'],
    'education customer care specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'customer service', 'communication', 'resolution', 'crm'],
    'education data processing assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'data entry', 'processing', 'accuracy', 'reporting'],
    'education process improvement analyst' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'education risk control assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'education resource coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'education workflow specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'education performance analyst' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'education delivery coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'delivery', 'routing', 'coordination', 'tracking'],
    'education logistics assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'education administrative officer' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'administration', 'documentation', 'organization', 'coordination'],
    'education reporting analyst' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'reporting', 'analysis', 'dashboards', 'data'],
    'education inventory controller' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'education maintenance coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'education safety officer' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'education site coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'site operations', 'coordination', 'safety', 'reporting'],
    'education procurement assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'education vendor coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'education records officer' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'records management', 'documentation', 'filing', 'accuracy'],
    'education client support specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'client support', 'communication', 'issue resolution', 'service'],
    'education implementation assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'implementation', 'configuration', 'support', 'documentation'],
    'education integration specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'integration', 'systems', 'testing', 'technical support'],
    'education monitoring analyst' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'education audit assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'audit support', 'documentation', 'compliance', 'verification'],
    'education research assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'research', 'data collection', 'analysis', 'documentation'],
    'education communications officer' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'education product support specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'education technical coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'technical coordination', 'planning', 'support', 'execution'],
    'education service coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'education operations supervisor' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'supervision', 'operations', 'team coordination', 'quality'],
    'education analytics specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'analytics', 'reporting', 'data insights', 'kpi'],
    'education engagement coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'engagement', 'communication', 'program support', 'coordination'],
    'education program assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'program support', 'documentation', 'coordination', 'tracking'],
    'education execution specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'execution', 'delivery', 'coordination', 'quality'],
    'education solutions assistant' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'education production coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'production', 'planning', 'quality', 'coordination'],
    'education dispatch coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'dispatch', 'routing', 'tracking', 'coordination'],
    'education onboarding specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'onboarding', 'training', 'documentation', 'support'],
    'education account support specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'account support', 'client service', 'reporting', 'coordination'],
    'education compliance coordinator' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'compliance', 'policy', 'audit support', 'documentation'],
    'education quality assurance specialist' => ['curriculum support', 'student coordination', 'assessment', 'learning support', 'quality assurance', 'testing', 'standards', 'reporting'],
    'retail operations coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'operations', 'coordination', 'planning', 'execution'],
    'retail support specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'support', 'problem solving', 'communication', 'service'],
    'retail service analyst' => ['customer service', 'merchandising', 'sales support', 'inventory', 'analysis', 'service quality', 'reporting', 'kpi'],
    'retail compliance assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'compliance', 'documentation', 'audit support', 'policy'],
    'retail quality officer' => ['customer service', 'merchandising', 'sales support', 'inventory', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'retail documentation officer' => ['customer service', 'merchandising', 'sales support', 'inventory', 'documentation', 'records', 'accuracy', 'filing'],
    'retail field technician' => ['customer service', 'merchandising', 'sales support', 'inventory', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'retail planning assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'planning', 'scheduling', 'coordination', 'organization'],
    'retail project associate' => ['customer service', 'merchandising', 'sales support', 'inventory', 'project support', 'tracking', 'coordination', 'reporting'],
    'retail training coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'training', 'facilitation', 'learning', 'assessment'],
    'retail customer care specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'communication', 'resolution', 'crm', 'teamwork'],
    'retail data processing assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'data entry', 'processing', 'accuracy', 'reporting'],
    'retail process improvement analyst' => ['customer service', 'merchandising', 'sales support', 'inventory', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'retail risk control assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'retail resource coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'retail workflow specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'retail performance analyst' => ['customer service', 'merchandising', 'sales support', 'inventory', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'retail delivery coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'delivery', 'routing', 'coordination', 'tracking'],
    'retail logistics assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'logistics', 'dispatch', 'coordination', 'teamwork'],
    'retail administrative officer' => ['customer service', 'merchandising', 'sales support', 'inventory', 'administration', 'documentation', 'organization', 'coordination'],
    'retail reporting analyst' => ['customer service', 'merchandising', 'sales support', 'inventory', 'reporting', 'analysis', 'dashboards', 'data'],
    'retail inventory controller' => ['customer service', 'merchandising', 'sales support', 'inventory', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'retail maintenance coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'retail safety officer' => ['customer service', 'merchandising', 'sales support', 'inventory', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'retail site coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'site operations', 'coordination', 'safety', 'reporting'],
    'retail procurement assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'retail vendor coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'retail records officer' => ['customer service', 'merchandising', 'sales support', 'inventory', 'records management', 'documentation', 'filing', 'accuracy'],
    'retail client support specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'client support', 'communication', 'issue resolution', 'service'],
    'retail implementation assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'implementation', 'configuration', 'support', 'documentation'],
    'retail integration specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'integration', 'systems', 'testing', 'technical support'],
    'retail monitoring analyst' => ['customer service', 'merchandising', 'sales support', 'inventory', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'retail audit assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'audit support', 'documentation', 'compliance', 'verification'],
    'retail research assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'research', 'data collection', 'analysis', 'documentation'],
    'retail communications officer' => ['customer service', 'merchandising', 'sales support', 'inventory', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'retail product support specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'product support', 'troubleshooting', 'documentation', 'teamwork'],
    'retail technical coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'technical coordination', 'planning', 'support', 'execution'],
    'retail service coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'retail operations supervisor' => ['customer service', 'merchandising', 'sales support', 'inventory', 'supervision', 'operations', 'team coordination', 'quality'],
    'retail analytics specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'analytics', 'reporting', 'data insights', 'kpi'],
    'retail engagement coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'engagement', 'communication', 'program support', 'coordination'],
    'retail program assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'program support', 'documentation', 'coordination', 'tracking'],
    'retail execution specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'execution', 'delivery', 'coordination', 'quality'],
    'retail solutions assistant' => ['customer service', 'merchandising', 'sales support', 'inventory', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'retail production coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'production', 'planning', 'quality', 'coordination'],
    'retail dispatch coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'dispatch', 'routing', 'tracking', 'coordination'],
    'retail onboarding specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'onboarding', 'training', 'documentation', 'support'],
    'retail account support specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'account support', 'client service', 'reporting', 'coordination'],
    'retail compliance coordinator' => ['customer service', 'merchandising', 'sales support', 'inventory', 'compliance', 'policy', 'audit support', 'documentation'],
    'retail quality assurance specialist' => ['customer service', 'merchandising', 'sales support', 'inventory', 'quality assurance', 'testing', 'standards', 'reporting'],
    'manufacturing operations coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'operations', 'coordination', 'planning', 'execution'],
    'manufacturing support specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'support', 'problem solving', 'communication', 'service'],
    'manufacturing service analyst' => ['production', 'quality control', 'safety', 'process monitoring', 'analysis', 'service quality', 'reporting', 'kpi'],
    'manufacturing compliance assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'compliance', 'documentation', 'audit support', 'policy'],
    'manufacturing quality officer' => ['production', 'quality control', 'safety', 'process monitoring', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'manufacturing documentation officer' => ['production', 'quality control', 'safety', 'process monitoring', 'documentation', 'records', 'accuracy', 'filing'],
    'manufacturing field technician' => ['production', 'quality control', 'safety', 'process monitoring', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'manufacturing planning assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'planning', 'scheduling', 'coordination', 'organization'],
    'manufacturing project associate' => ['production', 'quality control', 'safety', 'process monitoring', 'project support', 'tracking', 'coordination', 'reporting'],
    'manufacturing training coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'training', 'facilitation', 'learning', 'assessment'],
    'manufacturing customer care specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'customer service', 'communication', 'resolution', 'crm'],
    'manufacturing data processing assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'data entry', 'processing', 'accuracy', 'reporting'],
    'manufacturing process improvement analyst' => ['production', 'quality control', 'safety', 'process monitoring', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'manufacturing risk control assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'manufacturing resource coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'manufacturing workflow specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'manufacturing performance analyst' => ['production', 'quality control', 'safety', 'process monitoring', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'manufacturing delivery coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'delivery', 'routing', 'coordination', 'tracking'],
    'manufacturing logistics assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'manufacturing administrative officer' => ['production', 'quality control', 'safety', 'process monitoring', 'administration', 'documentation', 'organization', 'coordination'],
    'manufacturing reporting analyst' => ['production', 'quality control', 'safety', 'process monitoring', 'reporting', 'analysis', 'dashboards', 'data'],
    'manufacturing inventory controller' => ['production', 'quality control', 'safety', 'process monitoring', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'manufacturing maintenance coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'manufacturing safety officer' => ['production', 'quality control', 'safety', 'process monitoring', 'compliance', 'inspection', 'risk prevention', 'teamwork'],
    'manufacturing site coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'site operations', 'coordination', 'reporting', 'teamwork'],
    'manufacturing procurement assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'manufacturing vendor coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'manufacturing records officer' => ['production', 'quality control', 'safety', 'process monitoring', 'records management', 'documentation', 'filing', 'accuracy'],
    'manufacturing client support specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'client support', 'communication', 'issue resolution', 'service'],
    'manufacturing implementation assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'implementation', 'configuration', 'support', 'documentation'],
    'manufacturing integration specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'integration', 'systems', 'testing', 'technical support'],
    'manufacturing monitoring analyst' => ['production', 'quality control', 'safety', 'process monitoring', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'manufacturing audit assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'audit support', 'documentation', 'compliance', 'verification'],
    'manufacturing research assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'research', 'data collection', 'analysis', 'documentation'],
    'manufacturing communications officer' => ['production', 'quality control', 'safety', 'process monitoring', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'manufacturing product support specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'manufacturing technical coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'technical coordination', 'planning', 'support', 'execution'],
    'manufacturing service coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'manufacturing operations supervisor' => ['production', 'quality control', 'safety', 'process monitoring', 'supervision', 'operations', 'team coordination', 'quality'],
    'manufacturing analytics specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'analytics', 'reporting', 'data insights', 'kpi'],
    'manufacturing engagement coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'engagement', 'communication', 'program support', 'coordination'],
    'manufacturing program assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'program support', 'documentation', 'coordination', 'tracking'],
    'manufacturing execution specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'execution', 'delivery', 'coordination', 'quality'],
    'manufacturing solutions assistant' => ['production', 'quality control', 'safety', 'process monitoring', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'manufacturing production coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'planning', 'quality', 'coordination', 'teamwork'],
    'manufacturing dispatch coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'dispatch', 'routing', 'tracking', 'coordination'],
    'manufacturing onboarding specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'onboarding', 'training', 'documentation', 'support'],
    'manufacturing account support specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'account support', 'client service', 'reporting', 'coordination'],
    'manufacturing compliance coordinator' => ['production', 'quality control', 'safety', 'process monitoring', 'compliance', 'policy', 'audit support', 'documentation'],
    'manufacturing quality assurance specialist' => ['production', 'quality control', 'safety', 'process monitoring', 'quality assurance', 'testing', 'standards', 'reporting'],
    'construction operations coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'operations', 'planning', 'execution', 'teamwork'],
    'construction support specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'support', 'problem solving', 'communication', 'service'],
    'construction service analyst' => ['site safety', 'construction support', 'coordination', 'tool handling', 'analysis', 'service quality', 'reporting', 'kpi'],
    'construction compliance assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'compliance', 'documentation', 'audit support', 'policy'],
    'construction quality officer' => ['site safety', 'construction support', 'coordination', 'tool handling', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'construction documentation officer' => ['site safety', 'construction support', 'coordination', 'tool handling', 'documentation', 'records', 'accuracy', 'filing'],
    'construction field technician' => ['site safety', 'construction support', 'coordination', 'tool handling', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'construction planning assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'planning', 'scheduling', 'organization', 'teamwork'],
    'construction project associate' => ['site safety', 'construction support', 'coordination', 'tool handling', 'project support', 'tracking', 'reporting', 'teamwork'],
    'construction training coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'training', 'facilitation', 'learning', 'assessment'],
    'construction customer care specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'customer service', 'communication', 'resolution', 'crm'],
    'construction data processing assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'data entry', 'processing', 'accuracy', 'reporting'],
    'construction process improvement analyst' => ['site safety', 'construction support', 'coordination', 'tool handling', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'construction risk control assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'construction resource coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'resource planning', 'allocation', 'tracking', 'teamwork'],
    'construction workflow specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'workflow', 'efficiency', 'optimization', 'teamwork'],
    'construction performance analyst' => ['site safety', 'construction support', 'coordination', 'tool handling', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'construction delivery coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'delivery', 'routing', 'tracking', 'teamwork'],
    'construction logistics assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'logistics', 'inventory', 'dispatch', 'teamwork'],
    'construction administrative officer' => ['site safety', 'construction support', 'coordination', 'tool handling', 'administration', 'documentation', 'organization', 'teamwork'],
    'construction reporting analyst' => ['site safety', 'construction support', 'coordination', 'tool handling', 'reporting', 'analysis', 'dashboards', 'data'],
    'construction inventory controller' => ['site safety', 'construction support', 'coordination', 'tool handling', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'construction maintenance coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'maintenance', 'scheduling', 'inspection', 'teamwork'],
    'construction safety officer' => ['site safety', 'construction support', 'coordination', 'tool handling', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'construction site coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'site operations', 'safety', 'reporting', 'teamwork'],
    'construction procurement assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'construction vendor coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'vendor relations', 'compliance', 'follow-up', 'teamwork'],
    'construction records officer' => ['site safety', 'construction support', 'coordination', 'tool handling', 'records management', 'documentation', 'filing', 'accuracy'],
    'construction client support specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'client support', 'communication', 'issue resolution', 'service'],
    'construction implementation assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'implementation', 'configuration', 'support', 'documentation'],
    'construction integration specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'integration', 'systems', 'testing', 'technical support'],
    'construction monitoring analyst' => ['site safety', 'construction support', 'coordination', 'tool handling', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'construction audit assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'audit support', 'documentation', 'compliance', 'verification'],
    'construction research assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'research', 'data collection', 'analysis', 'documentation'],
    'construction communications officer' => ['site safety', 'construction support', 'coordination', 'tool handling', 'communication', 'documentation', 'stakeholder support', 'teamwork'],
    'construction product support specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'construction technical coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'technical coordination', 'planning', 'support', 'execution'],
    'construction service coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'service delivery', 'customer support', 'scheduling', 'teamwork'],
    'construction operations supervisor' => ['site safety', 'construction support', 'coordination', 'tool handling', 'supervision', 'operations', 'team coordination', 'quality'],
    'construction analytics specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'analytics', 'reporting', 'data insights', 'kpi'],
    'construction engagement coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'engagement', 'communication', 'program support', 'teamwork'],
    'construction program assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'program support', 'documentation', 'tracking', 'teamwork'],
    'construction execution specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'execution', 'delivery', 'quality', 'teamwork'],
    'construction solutions assistant' => ['site safety', 'construction support', 'coordination', 'tool handling', 'solution support', 'problem solving', 'implementation', 'teamwork'],
    'construction production coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'production', 'planning', 'quality', 'teamwork'],
    'construction dispatch coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'dispatch', 'routing', 'tracking', 'teamwork'],
    'construction onboarding specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'onboarding', 'training', 'documentation', 'support'],
    'construction account support specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'account support', 'client service', 'reporting', 'teamwork'],
    'construction compliance coordinator' => ['site safety', 'construction support', 'coordination', 'tool handling', 'compliance', 'policy', 'audit support', 'documentation'],
    'construction quality assurance specialist' => ['site safety', 'construction support', 'coordination', 'tool handling', 'quality assurance', 'testing', 'standards', 'reporting'],
    'logistics operations coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'operations', 'coordination', 'planning', 'execution'],
    'logistics support specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'support', 'problem solving', 'communication', 'service'],
    'logistics service analyst' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'analysis', 'service quality', 'reporting', 'kpi'],
    'logistics compliance assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'compliance', 'documentation', 'audit support', 'policy'],
    'logistics quality officer' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'logistics documentation officer' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'documentation', 'records', 'accuracy', 'filing'],
    'logistics field technician' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'logistics planning assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'planning', 'scheduling', 'coordination', 'organization'],
    'logistics project associate' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'project support', 'tracking', 'coordination', 'reporting'],
    'logistics training coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'training', 'facilitation', 'learning', 'assessment'],
    'logistics customer care specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'customer service', 'communication', 'resolution', 'crm'],
    'logistics data processing assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'data entry', 'processing', 'accuracy', 'reporting'],
    'logistics process improvement analyst' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'logistics risk control assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'logistics resource coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'logistics workflow specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'logistics performance analyst' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'logistics delivery coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'delivery', 'coordination', 'tracking', 'teamwork'],
    'logistics logistics assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'logistics', 'inventory', 'coordination', 'teamwork'],
    'logistics administrative officer' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'administration', 'documentation', 'organization', 'coordination'],
    'logistics reporting analyst' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'reporting', 'analysis', 'dashboards', 'data'],
    'logistics inventory controller' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'logistics maintenance coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'logistics safety officer' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'logistics site coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'site operations', 'coordination', 'safety', 'reporting'],
    'logistics procurement assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'logistics vendor coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'logistics records officer' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'records management', 'documentation', 'filing', 'accuracy'],
    'logistics client support specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'client support', 'communication', 'issue resolution', 'service'],
    'logistics implementation assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'implementation', 'configuration', 'support', 'documentation'],
    'logistics integration specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'integration', 'systems', 'testing', 'technical support'],
    'logistics monitoring analyst' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'logistics audit assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'audit support', 'documentation', 'compliance', 'verification'],
    'logistics research assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'research', 'data collection', 'analysis', 'documentation'],
    'logistics communications officer' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'logistics product support specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'logistics technical coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'technical coordination', 'planning', 'support', 'execution'],
    'logistics service coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'logistics operations supervisor' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'supervision', 'operations', 'team coordination', 'quality'],
    'logistics analytics specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'analytics', 'reporting', 'data insights', 'kpi'],
    'logistics engagement coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'engagement', 'communication', 'program support', 'coordination'],
    'logistics program assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'program support', 'documentation', 'coordination', 'tracking'],
    'logistics execution specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'execution', 'delivery', 'coordination', 'quality'],
    'logistics solutions assistant' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'logistics production coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'production', 'planning', 'quality', 'coordination'],
    'logistics dispatch coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'tracking', 'coordination', 'teamwork', 'communication'],
    'logistics onboarding specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'onboarding', 'training', 'documentation', 'support'],
    'logistics account support specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'account support', 'client service', 'reporting', 'coordination'],
    'logistics compliance coordinator' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'compliance', 'policy', 'audit support', 'documentation'],
    'logistics quality assurance specialist' => ['dispatch', 'routing', 'supply chain', 'inventory tracking', 'quality assurance', 'testing', 'standards', 'reporting'],
    'hospitality operations coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'operations', 'planning', 'execution', 'teamwork'],
    'hospitality support specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'support', 'problem solving', 'communication', 'service'],
    'hospitality service analyst' => ['guest service', 'front office', 'service quality', 'coordination', 'analysis', 'reporting', 'kpi', 'teamwork'],
    'hospitality compliance assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'compliance', 'documentation', 'audit support', 'policy'],
    'hospitality quality officer' => ['guest service', 'front office', 'service quality', 'coordination', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'hospitality documentation officer' => ['guest service', 'front office', 'service quality', 'coordination', 'documentation', 'records', 'accuracy', 'filing'],
    'hospitality field technician' => ['guest service', 'front office', 'service quality', 'coordination', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'hospitality planning assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'planning', 'scheduling', 'organization', 'teamwork'],
    'hospitality project associate' => ['guest service', 'front office', 'service quality', 'coordination', 'project support', 'tracking', 'reporting', 'teamwork'],
    'hospitality training coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'training', 'facilitation', 'learning', 'assessment'],
    'hospitality customer care specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'customer service', 'communication', 'resolution', 'crm'],
    'hospitality data processing assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'data entry', 'processing', 'accuracy', 'reporting'],
    'hospitality process improvement analyst' => ['guest service', 'front office', 'service quality', 'coordination', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'hospitality risk control assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'hospitality resource coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'resource planning', 'allocation', 'tracking', 'teamwork'],
    'hospitality workflow specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'workflow', 'efficiency', 'optimization', 'teamwork'],
    'hospitality performance analyst' => ['guest service', 'front office', 'service quality', 'coordination', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'hospitality delivery coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'delivery', 'routing', 'tracking', 'teamwork'],
    'hospitality logistics assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'logistics', 'inventory', 'dispatch', 'teamwork'],
    'hospitality administrative officer' => ['guest service', 'front office', 'service quality', 'coordination', 'administration', 'documentation', 'organization', 'teamwork'],
    'hospitality reporting analyst' => ['guest service', 'front office', 'service quality', 'coordination', 'reporting', 'analysis', 'dashboards', 'data'],
    'hospitality inventory controller' => ['guest service', 'front office', 'service quality', 'coordination', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'hospitality maintenance coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'maintenance', 'scheduling', 'inspection', 'teamwork'],
    'hospitality safety officer' => ['guest service', 'front office', 'service quality', 'coordination', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'hospitality site coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'site operations', 'safety', 'reporting', 'teamwork'],
    'hospitality procurement assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'hospitality vendor coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'vendor relations', 'compliance', 'follow-up', 'teamwork'],
    'hospitality records officer' => ['guest service', 'front office', 'service quality', 'coordination', 'records management', 'documentation', 'filing', 'accuracy'],
    'hospitality client support specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'client support', 'communication', 'issue resolution', 'service'],
    'hospitality implementation assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'implementation', 'configuration', 'support', 'documentation'],
    'hospitality integration specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'integration', 'systems', 'testing', 'technical support'],
    'hospitality monitoring analyst' => ['guest service', 'front office', 'service quality', 'coordination', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'hospitality audit assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'audit support', 'documentation', 'compliance', 'verification'],
    'hospitality research assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'research', 'data collection', 'analysis', 'documentation'],
    'hospitality communications officer' => ['guest service', 'front office', 'service quality', 'coordination', 'communication', 'documentation', 'stakeholder support', 'teamwork'],
    'hospitality product support specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'hospitality technical coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'technical coordination', 'planning', 'support', 'execution'],
    'hospitality service coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'service delivery', 'customer support', 'scheduling', 'teamwork'],
    'hospitality operations supervisor' => ['guest service', 'front office', 'service quality', 'coordination', 'supervision', 'operations', 'team coordination', 'quality'],
    'hospitality analytics specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'analytics', 'reporting', 'data insights', 'kpi'],
    'hospitality engagement coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'engagement', 'communication', 'program support', 'teamwork'],
    'hospitality program assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'program support', 'documentation', 'tracking', 'teamwork'],
    'hospitality execution specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'execution', 'delivery', 'quality', 'teamwork'],
    'hospitality solutions assistant' => ['guest service', 'front office', 'service quality', 'coordination', 'solution support', 'problem solving', 'implementation', 'teamwork'],
    'hospitality production coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'production', 'planning', 'quality', 'teamwork'],
    'hospitality dispatch coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'dispatch', 'routing', 'tracking', 'teamwork'],
    'hospitality onboarding specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'onboarding', 'training', 'documentation', 'support'],
    'hospitality account support specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'account support', 'client service', 'reporting', 'teamwork'],
    'hospitality compliance coordinator' => ['guest service', 'front office', 'service quality', 'coordination', 'compliance', 'policy', 'audit support', 'documentation'],
    'hospitality quality assurance specialist' => ['guest service', 'front office', 'service quality', 'coordination', 'quality assurance', 'testing', 'standards', 'reporting'],
    'government operations coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'operations', 'coordination', 'planning', 'execution'],
    'government support specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'support', 'problem solving', 'communication', 'service'],
    'government service analyst' => ['public service', 'documentation', 'policy compliance', 'records management', 'analysis', 'service quality', 'reporting', 'kpi'],
    'government compliance assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'compliance', 'audit support', 'policy', 'teamwork'],
    'government quality officer' => ['public service', 'documentation', 'policy compliance', 'records management', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'government documentation officer' => ['public service', 'documentation', 'policy compliance', 'records management', 'records', 'accuracy', 'filing', 'teamwork'],
    'government field technician' => ['public service', 'documentation', 'policy compliance', 'records management', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'government planning assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'planning', 'scheduling', 'coordination', 'organization'],
    'government project associate' => ['public service', 'documentation', 'policy compliance', 'records management', 'project support', 'tracking', 'coordination', 'reporting'],
    'government training coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'training', 'facilitation', 'learning', 'assessment'],
    'government customer care specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'customer service', 'communication', 'resolution', 'crm'],
    'government data processing assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'data entry', 'processing', 'accuracy', 'reporting'],
    'government process improvement analyst' => ['public service', 'documentation', 'policy compliance', 'records management', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'government risk control assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'government resource coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'government workflow specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'government performance analyst' => ['public service', 'documentation', 'policy compliance', 'records management', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'government delivery coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'delivery', 'routing', 'coordination', 'tracking'],
    'government logistics assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'government administrative officer' => ['public service', 'documentation', 'policy compliance', 'records management', 'administration', 'organization', 'coordination', 'teamwork'],
    'government reporting analyst' => ['public service', 'documentation', 'policy compliance', 'records management', 'reporting', 'analysis', 'dashboards', 'data'],
    'government inventory controller' => ['public service', 'documentation', 'policy compliance', 'records management', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'government maintenance coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'government safety officer' => ['public service', 'documentation', 'policy compliance', 'records management', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'government site coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'site operations', 'coordination', 'safety', 'reporting'],
    'government procurement assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'procurement', 'vendor management', 'purchasing', 'teamwork'],
    'government vendor coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'government records officer' => ['public service', 'documentation', 'policy compliance', 'records management', 'filing', 'accuracy', 'teamwork', 'communication'],
    'government client support specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'client support', 'communication', 'issue resolution', 'service'],
    'government implementation assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'implementation', 'configuration', 'support', 'teamwork'],
    'government integration specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'integration', 'systems', 'testing', 'technical support'],
    'government monitoring analyst' => ['public service', 'documentation', 'policy compliance', 'records management', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'government audit assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'audit support', 'compliance', 'verification', 'teamwork'],
    'government research assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'research', 'data collection', 'analysis', 'teamwork'],
    'government communications officer' => ['public service', 'documentation', 'policy compliance', 'records management', 'communication', 'coordination', 'stakeholder support', 'teamwork'],
    'government product support specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'product support', 'troubleshooting', 'customer service', 'teamwork'],
    'government technical coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'technical coordination', 'planning', 'support', 'execution'],
    'government service coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'government operations supervisor' => ['public service', 'documentation', 'policy compliance', 'records management', 'supervision', 'operations', 'team coordination', 'quality'],
    'government analytics specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'analytics', 'reporting', 'data insights', 'kpi'],
    'government engagement coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'engagement', 'communication', 'program support', 'coordination'],
    'government program assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'program support', 'coordination', 'tracking', 'teamwork'],
    'government execution specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'execution', 'delivery', 'coordination', 'quality'],
    'government solutions assistant' => ['public service', 'documentation', 'policy compliance', 'records management', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'government production coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'production', 'planning', 'quality', 'coordination'],
    'government dispatch coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'dispatch', 'routing', 'tracking', 'coordination'],
    'government onboarding specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'onboarding', 'training', 'support', 'teamwork'],
    'government account support specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'account support', 'client service', 'reporting', 'coordination'],
    'government compliance coordinator' => ['public service', 'documentation', 'policy compliance', 'records management', 'compliance', 'policy', 'audit support', 'teamwork'],
    'government quality assurance specialist' => ['public service', 'documentation', 'policy compliance', 'records management', 'quality assurance', 'testing', 'standards', 'reporting'],
    'technology operations coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'operations', 'coordination', 'planning', 'execution'],
    'technology support specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'support', 'problem solving', 'communication', 'service'],
    'technology service analyst' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'analysis', 'service quality', 'reporting', 'kpi'],
    'technology compliance assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'compliance', 'documentation', 'audit support', 'policy'],
    'technology quality officer' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'technology documentation officer' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'documentation', 'records', 'accuracy', 'filing'],
    'technology field technician' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'fieldwork', 'maintenance', 'technical', 'teamwork'],
    'technology planning assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'planning', 'scheduling', 'coordination', 'organization'],
    'technology project associate' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'project support', 'tracking', 'coordination', 'reporting'],
    'technology training coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'training', 'facilitation', 'learning', 'assessment'],
    'technology customer care specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'customer service', 'communication', 'resolution', 'crm'],
    'technology data processing assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'data entry', 'processing', 'accuracy', 'reporting'],
    'technology process improvement analyst' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'technology risk control assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'technology resource coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'technology workflow specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'technology performance analyst' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'technology delivery coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'delivery', 'routing', 'coordination', 'tracking'],
    'technology logistics assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'technology administrative officer' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'administration', 'documentation', 'organization', 'coordination'],
    'technology reporting analyst' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'reporting', 'analysis', 'dashboards', 'data'],
    'technology inventory controller' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'technology maintenance coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'technology safety officer' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'technology site coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'site operations', 'coordination', 'safety', 'reporting'],
    'technology procurement assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'technology vendor coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'technology records officer' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'records management', 'documentation', 'filing', 'accuracy'],
    'technology client support specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'client support', 'communication', 'issue resolution', 'service'],
    'technology implementation assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'implementation', 'configuration', 'support', 'documentation'],
    'technology integration specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'integration', 'systems', 'testing', 'teamwork'],
    'technology monitoring analyst' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'technology audit assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'audit support', 'documentation', 'compliance', 'verification'],
    'technology research assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'research', 'data collection', 'analysis', 'documentation'],
    'technology communications officer' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'technology product support specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'product support', 'customer service', 'documentation', 'teamwork'],
    'technology technical coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'technical coordination', 'planning', 'support', 'execution'],
    'technology service coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'technology operations supervisor' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'supervision', 'operations', 'team coordination', 'quality'],
    'technology analytics specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'analytics', 'reporting', 'data insights', 'kpi'],
    'technology engagement coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'engagement', 'communication', 'program support', 'coordination'],
    'technology program assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'program support', 'documentation', 'coordination', 'tracking'],
    'technology execution specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'execution', 'delivery', 'coordination', 'quality'],
    'technology solutions assistant' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'technology production coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'production', 'planning', 'quality', 'coordination'],
    'technology dispatch coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'dispatch', 'routing', 'tracking', 'coordination'],
    'technology onboarding specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'onboarding', 'training', 'documentation', 'support'],
    'technology account support specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'account support', 'client service', 'reporting', 'coordination'],
    'technology compliance coordinator' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'compliance', 'policy', 'audit support', 'documentation'],
    'technology quality assurance specialist' => ['digital tools', 'technical support', 'automation', 'troubleshooting', 'quality assurance', 'testing', 'standards', 'reporting'],
    'telecommunications operations coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'operations', 'coordination', 'planning', 'execution'],
    'telecommunications support specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'support', 'problem solving', 'communication', 'service'],
    'telecommunications service analyst' => ['network support', 'service assurance', 'ticket handling', 'field support', 'analysis', 'service quality', 'reporting', 'kpi'],
    'telecommunications compliance assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'compliance', 'documentation', 'audit support', 'policy'],
    'telecommunications quality officer' => ['network support', 'service assurance', 'ticket handling', 'field support', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'telecommunications documentation officer' => ['network support', 'service assurance', 'ticket handling', 'field support', 'documentation', 'records', 'accuracy', 'filing'],
    'telecommunications field technician' => ['network support', 'service assurance', 'ticket handling', 'field support', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'telecommunications planning assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'planning', 'scheduling', 'coordination', 'organization'],
    'telecommunications project associate' => ['network support', 'service assurance', 'ticket handling', 'field support', 'project support', 'tracking', 'coordination', 'reporting'],
    'telecommunications training coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'training', 'facilitation', 'learning', 'assessment'],
    'telecommunications customer care specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'customer service', 'communication', 'resolution', 'crm'],
    'telecommunications data processing assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'data entry', 'processing', 'accuracy', 'reporting'],
    'telecommunications process improvement analyst' => ['network support', 'service assurance', 'ticket handling', 'field support', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'telecommunications risk control assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'telecommunications resource coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'telecommunications workflow specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'telecommunications performance analyst' => ['network support', 'service assurance', 'ticket handling', 'field support', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'telecommunications delivery coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'delivery', 'routing', 'coordination', 'tracking'],
    'telecommunications logistics assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'telecommunications administrative officer' => ['network support', 'service assurance', 'ticket handling', 'field support', 'administration', 'documentation', 'organization', 'coordination'],
    'telecommunications reporting analyst' => ['network support', 'service assurance', 'ticket handling', 'field support', 'reporting', 'analysis', 'dashboards', 'data'],
    'telecommunications inventory controller' => ['network support', 'service assurance', 'ticket handling', 'field support', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'telecommunications maintenance coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'telecommunications safety officer' => ['network support', 'service assurance', 'ticket handling', 'field support', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'telecommunications site coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'site operations', 'coordination', 'safety', 'reporting'],
    'telecommunications procurement assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'telecommunications vendor coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'telecommunications records officer' => ['network support', 'service assurance', 'ticket handling', 'field support', 'records management', 'documentation', 'filing', 'accuracy'],
    'telecommunications client support specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'client support', 'communication', 'issue resolution', 'service'],
    'telecommunications implementation assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'implementation', 'configuration', 'support', 'documentation'],
    'telecommunications integration specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'integration', 'systems', 'testing', 'technical support'],
    'telecommunications monitoring analyst' => ['network support', 'service assurance', 'ticket handling', 'field support', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'telecommunications audit assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'audit support', 'documentation', 'compliance', 'verification'],
    'telecommunications research assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'research', 'data collection', 'analysis', 'documentation'],
    'telecommunications communications officer' => ['network support', 'service assurance', 'ticket handling', 'field support', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'telecommunications product support specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'telecommunications technical coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'technical coordination', 'planning', 'support', 'execution'],
    'telecommunications service coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'telecommunications operations supervisor' => ['network support', 'service assurance', 'ticket handling', 'field support', 'supervision', 'operations', 'team coordination', 'quality'],
    'telecommunications analytics specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'analytics', 'reporting', 'data insights', 'kpi'],
    'telecommunications engagement coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'engagement', 'communication', 'program support', 'coordination'],
    'telecommunications program assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'program support', 'documentation', 'coordination', 'tracking'],
    'telecommunications execution specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'execution', 'delivery', 'coordination', 'quality'],
    'telecommunications solutions assistant' => ['network support', 'service assurance', 'ticket handling', 'field support', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'telecommunications production coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'production', 'planning', 'quality', 'coordination'],
    'telecommunications dispatch coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'dispatch', 'routing', 'tracking', 'coordination'],
    'telecommunications onboarding specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'onboarding', 'training', 'documentation', 'support'],
    'telecommunications account support specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'account support', 'client service', 'reporting', 'coordination'],
    'telecommunications compliance coordinator' => ['network support', 'service assurance', 'ticket handling', 'field support', 'compliance', 'policy', 'audit support', 'documentation'],
    'telecommunications quality assurance specialist' => ['network support', 'service assurance', 'ticket handling', 'field support', 'quality assurance', 'testing', 'standards', 'reporting'],
    'agriculture operations coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'operations', 'coordination', 'planning', 'execution'],
    'agriculture support specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'support', 'problem solving', 'communication', 'service'],
    'agriculture service analyst' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'analysis', 'service quality', 'reporting', 'kpi'],
    'agriculture compliance assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'compliance', 'documentation', 'audit support', 'policy'],
    'agriculture quality officer' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'agriculture documentation officer' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'documentation', 'records', 'accuracy', 'filing'],
    'agriculture field technician' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'troubleshooting', 'maintenance', 'technical', 'teamwork'],
    'agriculture planning assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'planning', 'scheduling', 'coordination', 'organization'],
    'agriculture project associate' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'project support', 'tracking', 'coordination', 'reporting'],
    'agriculture training coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'training', 'facilitation', 'learning', 'assessment'],
    'agriculture customer care specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'customer service', 'communication', 'resolution', 'crm'],
    'agriculture data processing assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'data entry', 'processing', 'accuracy', 'reporting'],
    'agriculture process improvement analyst' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'agriculture risk control assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'agriculture resource coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'agriculture workflow specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'agriculture performance analyst' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'agriculture delivery coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'delivery', 'routing', 'coordination', 'tracking'],
    'agriculture logistics assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'agriculture administrative officer' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'administration', 'documentation', 'organization', 'coordination'],
    'agriculture reporting analyst' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'reporting', 'analysis', 'dashboards', 'data'],
    'agriculture inventory controller' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'agriculture maintenance coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'agriculture safety officer' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'agriculture site coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'site operations', 'coordination', 'safety', 'reporting'],
    'agriculture procurement assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'agriculture vendor coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'agriculture records officer' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'records management', 'documentation', 'filing', 'accuracy'],
    'agriculture client support specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'client support', 'communication', 'issue resolution', 'service'],
    'agriculture implementation assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'implementation', 'configuration', 'support', 'documentation'],
    'agriculture integration specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'integration', 'systems', 'testing', 'technical support'],
    'agriculture monitoring analyst' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'agriculture audit assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'audit support', 'documentation', 'compliance', 'verification'],
    'agriculture research assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'research', 'data collection', 'analysis', 'documentation'],
    'agriculture communications officer' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'agriculture product support specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'agriculture technical coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'technical coordination', 'planning', 'support', 'execution'],
    'agriculture service coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'agriculture operations supervisor' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'supervision', 'operations', 'team coordination', 'quality'],
    'agriculture analytics specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'analytics', 'reporting', 'data insights', 'kpi'],
    'agriculture engagement coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'engagement', 'communication', 'program support', 'coordination'],
    'agriculture program assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'program support', 'documentation', 'coordination', 'tracking'],
    'agriculture execution specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'execution', 'delivery', 'coordination', 'quality'],
    'agriculture solutions assistant' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'agriculture production coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'production', 'planning', 'quality', 'coordination'],
    'agriculture dispatch coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'dispatch', 'routing', 'tracking', 'coordination'],
    'agriculture onboarding specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'onboarding', 'training', 'documentation', 'support'],
    'agriculture account support specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'account support', 'client service', 'reporting', 'coordination'],
    'agriculture compliance coordinator' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'compliance', 'policy', 'audit support', 'documentation'],
    'agriculture quality assurance specialist' => ['farm operations', 'resource management', 'quality checks', 'fieldwork', 'quality assurance', 'testing', 'standards', 'reporting'],
    'energy operations coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'operations', 'coordination', 'planning', 'execution'],
    'energy support specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'support', 'problem solving', 'communication', 'service'],
    'energy service analyst' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'analysis', 'service quality', 'reporting', 'kpi'],
    'energy compliance assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'compliance', 'documentation', 'audit support', 'policy'],
    'energy quality officer' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'energy documentation officer' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'documentation', 'records', 'accuracy', 'filing'],
    'energy field technician' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'fieldwork', 'troubleshooting', 'technical', 'teamwork'],
    'energy planning assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'planning', 'scheduling', 'coordination', 'organization'],
    'energy project associate' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'project support', 'tracking', 'coordination', 'reporting'],
    'energy training coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'training', 'facilitation', 'learning', 'assessment'],
    'energy customer care specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'customer service', 'communication', 'resolution', 'crm'],
    'energy data processing assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'data entry', 'processing', 'accuracy', 'reporting'],
    'energy process improvement analyst' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'energy risk control assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'risk assessment', 'controls', 'compliance', 'teamwork'],
    'energy resource coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'energy workflow specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'energy performance analyst' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'energy delivery coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'delivery', 'routing', 'coordination', 'tracking'],
    'energy logistics assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'energy administrative officer' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'administration', 'documentation', 'organization', 'coordination'],
    'energy reporting analyst' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'reporting', 'analysis', 'dashboards', 'data'],
    'energy inventory controller' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'energy maintenance coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'scheduling', 'inspection', 'coordination', 'teamwork'],
    'energy safety officer' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'energy site coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'site operations', 'coordination', 'safety', 'reporting'],
    'energy procurement assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'energy vendor coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'energy records officer' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'records management', 'documentation', 'filing', 'accuracy'],
    'energy client support specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'client support', 'communication', 'issue resolution', 'service'],
    'energy implementation assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'implementation', 'configuration', 'support', 'documentation'],
    'energy integration specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'integration', 'systems', 'testing', 'technical support'],
    'energy monitoring analyst' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'incident tracking', 'analysis', 'reporting', 'teamwork'],
    'energy audit assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'audit support', 'documentation', 'compliance', 'verification'],
    'energy research assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'research', 'data collection', 'analysis', 'documentation'],
    'energy communications officer' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'energy product support specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'energy technical coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'technical coordination', 'planning', 'support', 'execution'],
    'energy service coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'energy operations supervisor' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'supervision', 'operations', 'team coordination', 'quality'],
    'energy analytics specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'analytics', 'reporting', 'data insights', 'kpi'],
    'energy engagement coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'engagement', 'communication', 'program support', 'coordination'],
    'energy program assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'program support', 'documentation', 'coordination', 'tracking'],
    'energy execution specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'execution', 'delivery', 'coordination', 'quality'],
    'energy solutions assistant' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'energy production coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'production', 'planning', 'quality', 'coordination'],
    'energy dispatch coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'dispatch', 'routing', 'tracking', 'coordination'],
    'energy onboarding specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'onboarding', 'training', 'documentation', 'support'],
    'energy account support specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'account support', 'client service', 'reporting', 'coordination'],
    'energy compliance coordinator' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'compliance', 'policy', 'audit support', 'documentation'],
    'energy quality assurance specialist' => ['safety compliance', 'monitoring', 'maintenance', 'operational control', 'quality assurance', 'testing', 'standards', 'reporting'],
    'automotive operations coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'operations', 'coordination', 'planning', 'execution'],
    'automotive support specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'support', 'problem solving', 'communication', 'service'],
    'automotive service analyst' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'analysis', 'service quality', 'reporting', 'kpi'],
    'automotive compliance assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'compliance', 'documentation', 'audit support', 'policy'],
    'automotive quality officer' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'automotive documentation officer' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'documentation', 'records', 'accuracy', 'filing'],
    'automotive field technician' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'fieldwork', 'troubleshooting', 'technical', 'teamwork'],
    'automotive planning assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'planning', 'scheduling', 'coordination', 'organization'],
    'automotive project associate' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'project support', 'tracking', 'coordination', 'reporting'],
    'automotive training coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'training', 'facilitation', 'learning', 'assessment'],
    'automotive customer care specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'customer service', 'communication', 'resolution', 'crm'],
    'automotive data processing assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'data entry', 'processing', 'accuracy', 'reporting'],
    'automotive process improvement analyst' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'automotive risk control assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'automotive resource coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'automotive workflow specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'automotive performance analyst' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'automotive delivery coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'delivery', 'routing', 'coordination', 'tracking'],
    'automotive logistics assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'automotive administrative officer' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'administration', 'documentation', 'organization', 'coordination'],
    'automotive reporting analyst' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'reporting', 'analysis', 'dashboards', 'data'],
    'automotive inventory controller' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'automotive maintenance coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'scheduling', 'inspection', 'coordination', 'teamwork'],
    'automotive safety officer' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'compliance', 'inspection', 'risk prevention', 'teamwork'],
    'automotive site coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'site operations', 'coordination', 'reporting', 'teamwork'],
    'automotive procurement assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'automotive vendor coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'automotive records officer' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'records management', 'documentation', 'filing', 'accuracy'],
    'automotive client support specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'client support', 'communication', 'issue resolution', 'service'],
    'automotive implementation assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'implementation', 'configuration', 'support', 'documentation'],
    'automotive integration specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'integration', 'systems', 'testing', 'technical support'],
    'automotive monitoring analyst' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'automotive audit assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'audit support', 'documentation', 'compliance', 'verification'],
    'automotive research assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'research', 'data collection', 'analysis', 'documentation'],
    'automotive communications officer' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'automotive product support specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'automotive technical coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'technical coordination', 'planning', 'support', 'execution'],
    'automotive service coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'automotive operations supervisor' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'supervision', 'operations', 'team coordination', 'quality'],
    'automotive analytics specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'analytics', 'reporting', 'data insights', 'kpi'],
    'automotive engagement coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'engagement', 'communication', 'program support', 'coordination'],
    'automotive program assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'program support', 'documentation', 'coordination', 'tracking'],
    'automotive execution specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'execution', 'delivery', 'coordination', 'quality'],
    'automotive solutions assistant' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'automotive production coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'production', 'planning', 'quality', 'coordination'],
    'automotive dispatch coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'dispatch', 'routing', 'tracking', 'coordination'],
    'automotive onboarding specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'onboarding', 'training', 'documentation', 'support'],
    'automotive account support specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'account support', 'client service', 'reporting', 'coordination'],
    'automotive compliance coordinator' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'compliance', 'policy', 'audit support', 'documentation'],
    'automotive quality assurance specialist' => ['diagnostics', 'maintenance', 'repair coordination', 'safety', 'quality assurance', 'testing', 'standards', 'reporting'],
    'media operations coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'operations', 'coordination', 'planning', 'execution'],
    'media support specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'support', 'problem solving', 'communication', 'service'],
    'media service analyst' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'analysis', 'service quality', 'reporting', 'kpi'],
    'media compliance assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'compliance', 'documentation', 'audit support', 'policy'],
    'media quality officer' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'media documentation officer' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'documentation', 'records', 'accuracy', 'filing'],
    'media field technician' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'media planning assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'planning', 'scheduling', 'coordination', 'organization'],
    'media project associate' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'project support', 'tracking', 'coordination', 'reporting'],
    'media training coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'training', 'facilitation', 'learning', 'assessment'],
    'media customer care specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'customer service', 'communication', 'resolution', 'crm'],
    'media data processing assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'data entry', 'processing', 'accuracy', 'reporting'],
    'media process improvement analyst' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'media risk control assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'media resource coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'media workflow specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'media performance analyst' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'media delivery coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'delivery', 'routing', 'coordination', 'tracking'],
    'media logistics assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'media administrative officer' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'administration', 'documentation', 'organization', 'coordination'],
    'media reporting analyst' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'reporting', 'analysis', 'dashboards', 'data'],
    'media inventory controller' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'media maintenance coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'media safety officer' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'media site coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'site operations', 'coordination', 'safety', 'reporting'],
    'media procurement assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'media vendor coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'media records officer' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'records management', 'documentation', 'filing', 'accuracy'],
    'media client support specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'client support', 'communication', 'issue resolution', 'service'],
    'media implementation assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'implementation', 'configuration', 'support', 'documentation'],
    'media integration specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'integration', 'systems', 'testing', 'technical support'],
    'media monitoring analyst' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'media audit assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'audit support', 'documentation', 'compliance', 'verification'],
    'media research assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'research', 'data collection', 'analysis', 'documentation'],
    'media communications officer' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'media product support specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'media technical coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'technical coordination', 'planning', 'support', 'execution'],
    'media service coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'media operations supervisor' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'supervision', 'operations', 'team coordination', 'quality'],
    'media analytics specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'analytics', 'reporting', 'data insights', 'kpi'],
    'media engagement coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'engagement', 'communication', 'program support', 'coordination'],
    'media program assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'program support', 'documentation', 'coordination', 'tracking'],
    'media execution specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'execution', 'delivery', 'coordination', 'quality'],
    'media solutions assistant' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'media production coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'production', 'planning', 'quality', 'coordination'],
    'media dispatch coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'dispatch', 'routing', 'tracking', 'coordination'],
    'media onboarding specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'onboarding', 'training', 'documentation', 'support'],
    'media account support specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'account support', 'client service', 'reporting', 'coordination'],
    'media compliance coordinator' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'compliance', 'policy', 'audit support', 'documentation'],
    'media quality assurance specialist' => ['content workflow', 'editing support', 'publishing', 'creative coordination', 'quality assurance', 'testing', 'standards', 'reporting'],
    'ecommerce operations coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'operations', 'coordination', 'planning', 'execution'],
    'ecommerce support specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'support', 'problem solving', 'communication', 'service'],
    'ecommerce service analyst' => ['order management', 'customer support', 'online operations', 'analytics', 'analysis', 'service quality', 'reporting', 'kpi'],
    'ecommerce compliance assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'compliance', 'documentation', 'audit support', 'policy'],
    'ecommerce quality officer' => ['order management', 'customer support', 'online operations', 'analytics', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'ecommerce documentation officer' => ['order management', 'customer support', 'online operations', 'analytics', 'documentation', 'records', 'accuracy', 'filing'],
    'ecommerce field technician' => ['order management', 'customer support', 'online operations', 'analytics', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'ecommerce planning assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'planning', 'scheduling', 'coordination', 'organization'],
    'ecommerce project associate' => ['order management', 'customer support', 'online operations', 'analytics', 'project support', 'tracking', 'coordination', 'reporting'],
    'ecommerce training coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'training', 'facilitation', 'learning', 'assessment'],
    'ecommerce customer care specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'customer service', 'communication', 'resolution', 'crm'],
    'ecommerce data processing assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'data entry', 'processing', 'accuracy', 'reporting'],
    'ecommerce process improvement analyst' => ['order management', 'customer support', 'online operations', 'analytics', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'ecommerce risk control assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'ecommerce resource coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'ecommerce workflow specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'ecommerce performance analyst' => ['order management', 'customer support', 'online operations', 'analytics', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'ecommerce delivery coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'delivery', 'routing', 'coordination', 'tracking'],
    'ecommerce logistics assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'ecommerce administrative officer' => ['order management', 'customer support', 'online operations', 'analytics', 'administration', 'documentation', 'organization', 'coordination'],
    'ecommerce reporting analyst' => ['order management', 'customer support', 'online operations', 'analytics', 'reporting', 'analysis', 'dashboards', 'data'],
    'ecommerce inventory controller' => ['order management', 'customer support', 'online operations', 'analytics', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'ecommerce maintenance coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'ecommerce safety officer' => ['order management', 'customer support', 'online operations', 'analytics', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'ecommerce site coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'site operations', 'coordination', 'safety', 'reporting'],
    'ecommerce procurement assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'ecommerce vendor coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'ecommerce records officer' => ['order management', 'customer support', 'online operations', 'analytics', 'records management', 'documentation', 'filing', 'accuracy'],
    'ecommerce client support specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'client support', 'communication', 'issue resolution', 'service'],
    'ecommerce implementation assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'implementation', 'configuration', 'support', 'documentation'],
    'ecommerce integration specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'integration', 'systems', 'testing', 'technical support'],
    'ecommerce monitoring analyst' => ['order management', 'customer support', 'online operations', 'analytics', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'ecommerce audit assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'audit support', 'documentation', 'compliance', 'verification'],
    'ecommerce research assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'research', 'data collection', 'analysis', 'documentation'],
    'ecommerce communications officer' => ['order management', 'customer support', 'online operations', 'analytics', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'ecommerce product support specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'ecommerce technical coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'technical coordination', 'planning', 'support', 'execution'],
    'ecommerce service coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'service delivery', 'coordination', 'scheduling', 'teamwork'],
    'ecommerce operations supervisor' => ['order management', 'customer support', 'online operations', 'analytics', 'supervision', 'operations', 'team coordination', 'quality'],
    'ecommerce analytics specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'reporting', 'data insights', 'kpi', 'teamwork'],
    'ecommerce engagement coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'engagement', 'communication', 'program support', 'coordination'],
    'ecommerce program assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'program support', 'documentation', 'coordination', 'tracking'],
    'ecommerce execution specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'execution', 'delivery', 'coordination', 'quality'],
    'ecommerce solutions assistant' => ['order management', 'customer support', 'online operations', 'analytics', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'ecommerce production coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'production', 'planning', 'quality', 'coordination'],
    'ecommerce dispatch coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'dispatch', 'routing', 'tracking', 'coordination'],
    'ecommerce onboarding specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'onboarding', 'training', 'documentation', 'support'],
    'ecommerce account support specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'account support', 'client service', 'reporting', 'coordination'],
    'ecommerce compliance coordinator' => ['order management', 'customer support', 'online operations', 'analytics', 'compliance', 'policy', 'audit support', 'documentation'],
    'ecommerce quality assurance specialist' => ['order management', 'customer support', 'online operations', 'analytics', 'quality assurance', 'testing', 'standards', 'reporting'],
    'banking operations coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'operations', 'coordination', 'planning', 'execution'],
    'banking support specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'support', 'problem solving', 'communication', 'service'],
    'banking service analyst' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'analysis', 'service quality', 'reporting', 'kpi'],
    'banking compliance assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'audit support', 'policy', 'teamwork', 'communication'],
    'banking quality officer' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'banking documentation officer' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'records', 'accuracy', 'filing', 'teamwork'],
    'banking field technician' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'banking planning assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'planning', 'scheduling', 'coordination', 'organization'],
    'banking project associate' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'project support', 'tracking', 'coordination', 'reporting'],
    'banking training coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'training', 'facilitation', 'learning', 'assessment'],
    'banking customer care specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'customer service', 'communication', 'resolution', 'crm'],
    'banking data processing assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'data entry', 'processing', 'accuracy', 'reporting'],
    'banking process improvement analyst' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'banking risk control assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'risk assessment', 'controls', 'monitoring', 'teamwork'],
    'banking resource coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'banking workflow specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'banking performance analyst' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'banking delivery coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'delivery', 'routing', 'coordination', 'tracking'],
    'banking logistics assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'banking administrative officer' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'administration', 'organization', 'coordination', 'teamwork'],
    'banking reporting analyst' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'reporting', 'analysis', 'dashboards', 'data'],
    'banking inventory controller' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'banking maintenance coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'banking safety officer' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'safety', 'inspection', 'risk prevention', 'teamwork'],
    'banking site coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'site operations', 'coordination', 'safety', 'reporting'],
    'banking procurement assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'procurement', 'vendor management', 'purchasing', 'teamwork'],
    'banking vendor coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'vendor relations', 'coordination', 'follow-up', 'teamwork'],
    'banking records officer' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'records management', 'filing', 'accuracy', 'teamwork'],
    'banking client support specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'client support', 'communication', 'issue resolution', 'service'],
    'banking implementation assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'implementation', 'configuration', 'support', 'teamwork'],
    'banking integration specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'integration', 'systems', 'testing', 'technical support'],
    'banking monitoring analyst' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'banking audit assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'audit support', 'verification', 'teamwork', 'communication'],
    'banking research assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'research', 'data collection', 'analysis', 'teamwork'],
    'banking communications officer' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'communication', 'coordination', 'stakeholder support', 'teamwork'],
    'banking product support specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'product support', 'troubleshooting', 'customer service', 'teamwork'],
    'banking technical coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'technical coordination', 'planning', 'support', 'execution'],
    'banking service coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'banking operations supervisor' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'supervision', 'operations', 'team coordination', 'quality'],
    'banking analytics specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'analytics', 'reporting', 'data insights', 'kpi'],
    'banking engagement coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'engagement', 'communication', 'program support', 'coordination'],
    'banking program assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'program support', 'coordination', 'tracking', 'teamwork'],
    'banking execution specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'execution', 'delivery', 'coordination', 'quality'],
    'banking solutions assistant' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'banking production coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'production', 'planning', 'quality', 'coordination'],
    'banking dispatch coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'dispatch', 'routing', 'tracking', 'coordination'],
    'banking onboarding specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'onboarding', 'training', 'support', 'teamwork'],
    'banking account support specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'account support', 'client service', 'reporting', 'coordination'],
    'banking compliance coordinator' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'policy', 'audit support', 'teamwork', 'communication'],
    'banking quality assurance specialist' => ['customer onboarding', 'transaction monitoring', 'compliance', 'documentation', 'quality assurance', 'testing', 'standards', 'reporting'],
    'insurance operations coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'operations', 'coordination', 'planning', 'execution'],
    'insurance support specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'support', 'problem solving', 'communication', 'service'],
    'insurance service analyst' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'analysis', 'service quality', 'reporting', 'kpi'],
    'insurance compliance assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'compliance', 'documentation', 'audit support', 'policy'],
    'insurance quality officer' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'insurance documentation officer' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'documentation', 'records', 'accuracy', 'filing'],
    'insurance field technician' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'insurance planning assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'planning', 'scheduling', 'coordination', 'organization'],
    'insurance project associate' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'project support', 'tracking', 'coordination', 'reporting'],
    'insurance training coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'training', 'facilitation', 'learning', 'assessment'],
    'insurance customer care specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'customer service', 'communication', 'resolution', 'crm'],
    'insurance data processing assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'data entry', 'processing', 'accuracy', 'reporting'],
    'insurance process improvement analyst' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'insurance risk control assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'insurance resource coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'insurance workflow specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'insurance performance analyst' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'insurance delivery coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'delivery', 'routing', 'coordination', 'tracking'],
    'insurance logistics assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'insurance administrative officer' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'administration', 'documentation', 'organization', 'coordination'],
    'insurance reporting analyst' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'reporting', 'analysis', 'dashboards', 'data'],
    'insurance inventory controller' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'insurance maintenance coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'insurance safety officer' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'insurance site coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'site operations', 'coordination', 'safety', 'reporting'],
    'insurance procurement assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'insurance vendor coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'insurance records officer' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'records management', 'documentation', 'filing', 'accuracy'],
    'insurance client support specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'client support', 'communication', 'issue resolution', 'service'],
    'insurance implementation assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'implementation', 'configuration', 'support', 'documentation'],
    'insurance integration specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'integration', 'systems', 'testing', 'technical support'],
    'insurance monitoring analyst' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'insurance audit assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'audit support', 'documentation', 'compliance', 'verification'],
    'insurance research assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'research', 'data collection', 'analysis', 'documentation'],
    'insurance communications officer' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'insurance product support specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'insurance technical coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'technical coordination', 'planning', 'support', 'execution'],
    'insurance service coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'insurance operations supervisor' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'supervision', 'operations', 'team coordination', 'quality'],
    'insurance analytics specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'analytics', 'reporting', 'data insights', 'kpi'],
    'insurance engagement coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'engagement', 'communication', 'program support', 'coordination'],
    'insurance program assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'program support', 'documentation', 'coordination', 'tracking'],
    'insurance execution specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'execution', 'delivery', 'coordination', 'quality'],
    'insurance solutions assistant' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'insurance production coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'production', 'planning', 'quality', 'coordination'],
    'insurance dispatch coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'dispatch', 'routing', 'tracking', 'coordination'],
    'insurance onboarding specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'onboarding', 'training', 'documentation', 'support'],
    'insurance account support specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'account support', 'client service', 'reporting', 'coordination'],
    'insurance compliance coordinator' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'compliance', 'policy', 'audit support', 'documentation'],
    'insurance quality assurance specialist' => ['claims processing', 'policy support', 'risk review', 'customer assistance', 'quality assurance', 'testing', 'standards', 'reporting'],
    'public service operations coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'operations', 'planning', 'execution', 'teamwork'],
    'public service support specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'support', 'problem solving', 'communication', 'service'],
    'public service service analyst' => ['community support', 'case handling', 'coordination', 'documentation', 'analysis', 'service quality', 'reporting', 'kpi'],
    'public service compliance assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'compliance', 'audit support', 'policy', 'teamwork'],
    'public service quality officer' => ['community support', 'case handling', 'coordination', 'documentation', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'public service documentation officer' => ['community support', 'case handling', 'coordination', 'documentation', 'records', 'accuracy', 'filing', 'teamwork'],
    'public service field technician' => ['community support', 'case handling', 'coordination', 'documentation', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'public service planning assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'planning', 'scheduling', 'organization', 'teamwork'],
    'public service project associate' => ['community support', 'case handling', 'coordination', 'documentation', 'project support', 'tracking', 'reporting', 'teamwork'],
    'public service training coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'training', 'facilitation', 'learning', 'assessment'],
    'public service customer care specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'customer service', 'communication', 'resolution', 'crm'],
    'public service data processing assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'data entry', 'processing', 'accuracy', 'reporting'],
    'public service process improvement analyst' => ['community support', 'case handling', 'coordination', 'documentation', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'public service risk control assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'public service resource coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'resource planning', 'allocation', 'tracking', 'teamwork'],
    'public service workflow specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'workflow', 'efficiency', 'optimization', 'teamwork'],
    'public service performance analyst' => ['community support', 'case handling', 'coordination', 'documentation', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'public service delivery coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'delivery', 'routing', 'tracking', 'teamwork'],
    'public service logistics assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'logistics', 'inventory', 'dispatch', 'teamwork'],
    'public service administrative officer' => ['community support', 'case handling', 'coordination', 'documentation', 'administration', 'organization', 'teamwork', 'communication'],
    'public service reporting analyst' => ['community support', 'case handling', 'coordination', 'documentation', 'reporting', 'analysis', 'dashboards', 'data'],
    'public service inventory controller' => ['community support', 'case handling', 'coordination', 'documentation', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'public service maintenance coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'maintenance', 'scheduling', 'inspection', 'teamwork'],
    'public service safety officer' => ['community support', 'case handling', 'coordination', 'documentation', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'public service site coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'site operations', 'safety', 'reporting', 'teamwork'],
    'public service procurement assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'procurement', 'vendor management', 'purchasing', 'teamwork'],
    'public service vendor coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'vendor relations', 'compliance', 'follow-up', 'teamwork'],
    'public service records officer' => ['community support', 'case handling', 'coordination', 'documentation', 'records management', 'filing', 'accuracy', 'teamwork'],
    'public service client support specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'client support', 'communication', 'issue resolution', 'service'],
    'public service implementation assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'implementation', 'configuration', 'support', 'teamwork'],
    'public service integration specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'integration', 'systems', 'testing', 'technical support'],
    'public service monitoring analyst' => ['community support', 'case handling', 'coordination', 'documentation', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'public service audit assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'audit support', 'compliance', 'verification', 'teamwork'],
    'public service research assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'research', 'data collection', 'analysis', 'teamwork'],
    'public service communications officer' => ['community support', 'case handling', 'coordination', 'documentation', 'communication', 'stakeholder support', 'teamwork'],
    'public service product support specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'product support', 'troubleshooting', 'customer service', 'teamwork'],
    'public service technical coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'technical coordination', 'planning', 'support', 'execution'],
    'public service service coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'service delivery', 'customer support', 'scheduling', 'teamwork'],
    'public service operations supervisor' => ['community support', 'case handling', 'coordination', 'documentation', 'supervision', 'operations', 'team coordination', 'quality'],
    'public service analytics specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'analytics', 'reporting', 'data insights', 'kpi'],
    'public service engagement coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'engagement', 'communication', 'program support', 'teamwork'],
    'public service program assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'program support', 'tracking', 'teamwork', 'communication'],
    'public service execution specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'execution', 'delivery', 'quality', 'teamwork'],
    'public service solutions assistant' => ['community support', 'case handling', 'coordination', 'documentation', 'solution support', 'problem solving', 'implementation', 'teamwork'],
    'public service production coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'production', 'planning', 'quality', 'teamwork'],
    'public service dispatch coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'dispatch', 'routing', 'tracking', 'teamwork'],
    'public service onboarding specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'onboarding', 'training', 'support', 'teamwork'],
    'public service account support specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'account support', 'client service', 'reporting', 'teamwork'],
    'public service compliance coordinator' => ['community support', 'case handling', 'coordination', 'documentation', 'compliance', 'policy', 'audit support', 'teamwork'],
    'public service quality assurance specialist' => ['community support', 'case handling', 'coordination', 'documentation', 'quality assurance', 'testing', 'standards', 'reporting'],
    'non profit operations coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'operations', 'coordination', 'planning', 'execution'],
    'non profit support specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'support', 'problem solving', 'communication', 'service'],
    'non profit service analyst' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'analysis', 'service quality', 'kpi', 'teamwork'],
    'non profit compliance assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'compliance', 'documentation', 'audit support', 'policy'],
    'non profit quality officer' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'quality assurance', 'inspection', 'standards', 'teamwork'],
    'non profit documentation officer' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'documentation', 'records', 'accuracy', 'filing'],
    'non profit field technician' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'non profit planning assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'planning', 'scheduling', 'coordination', 'organization'],
    'non profit project associate' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'project support', 'tracking', 'coordination', 'teamwork'],
    'non profit training coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'training', 'facilitation', 'learning', 'assessment'],
    'non profit customer care specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'customer service', 'communication', 'resolution', 'crm'],
    'non profit data processing assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'data entry', 'processing', 'accuracy', 'teamwork'],
    'non profit process improvement analyst' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'process improvement', 'analysis', 'optimization', 'teamwork'],
    'non profit risk control assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'non profit resource coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'non profit workflow specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'non profit performance analyst' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'performance metrics', 'kpi', 'analysis', 'teamwork'],
    'non profit delivery coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'delivery', 'routing', 'coordination', 'tracking'],
    'non profit logistics assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'non profit administrative officer' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'administration', 'documentation', 'organization', 'coordination'],
    'non profit reporting analyst' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'analysis', 'dashboards', 'data', 'teamwork'],
    'non profit inventory controller' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'inventory control', 'stock monitoring', 'accuracy', 'teamwork'],
    'non profit maintenance coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'non profit safety officer' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'non profit site coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'site operations', 'coordination', 'safety', 'teamwork'],
    'non profit procurement assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'non profit vendor coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'non profit records officer' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'records management', 'documentation', 'filing', 'accuracy'],
    'non profit client support specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'client support', 'communication', 'issue resolution', 'service'],
    'non profit implementation assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'implementation', 'configuration', 'support', 'documentation'],
    'non profit integration specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'integration', 'systems', 'testing', 'technical support'],
    'non profit monitoring analyst' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'monitoring', 'incident tracking', 'analysis', 'teamwork'],
    'non profit audit assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'audit support', 'documentation', 'compliance', 'verification'],
    'non profit research assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'research', 'data collection', 'analysis', 'documentation'],
    'non profit communications officer' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'non profit product support specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'non profit technical coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'technical coordination', 'planning', 'support', 'execution'],
    'non profit service coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'non profit operations supervisor' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'supervision', 'operations', 'team coordination', 'quality'],
    'non profit analytics specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'analytics', 'data insights', 'kpi', 'teamwork'],
    'non profit engagement coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'engagement', 'communication', 'coordination', 'teamwork'],
    'non profit program assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'documentation', 'coordination', 'tracking', 'teamwork'],
    'non profit execution specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'execution', 'delivery', 'coordination', 'quality'],
    'non profit solutions assistant' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'non profit production coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'production', 'planning', 'quality', 'coordination'],
    'non profit dispatch coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'dispatch', 'routing', 'tracking', 'coordination'],
    'non profit onboarding specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'onboarding', 'training', 'documentation', 'support'],
    'non profit account support specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'account support', 'client service', 'coordination', 'teamwork'],
    'non profit compliance coordinator' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'compliance', 'policy', 'audit support', 'documentation'],
    'non profit quality assurance specialist' => ['program support', 'stakeholder engagement', 'reporting', 'community outreach', 'quality assurance', 'testing', 'standards', 'teamwork'],
    'aviation operations coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'operations', 'coordination', 'planning', 'execution'],
    'aviation support specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'support', 'problem solving', 'communication', 'service'],
    'aviation service analyst' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'analysis', 'service quality', 'reporting', 'kpi'],
    'aviation compliance assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'compliance', 'documentation', 'audit support', 'policy'],
    'aviation quality officer' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'quality assurance', 'inspection', 'standards', 'reporting'],
    'aviation documentation officer' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'documentation', 'records', 'accuracy', 'filing'],
    'aviation field technician' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'fieldwork', 'troubleshooting', 'maintenance', 'technical'],
    'aviation planning assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'planning', 'scheduling', 'coordination', 'organization'],
    'aviation project associate' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'project support', 'tracking', 'coordination', 'reporting'],
    'aviation training coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'training', 'facilitation', 'learning', 'assessment'],
    'aviation customer care specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'customer service', 'communication', 'resolution', 'crm'],
    'aviation data processing assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'data entry', 'processing', 'accuracy', 'reporting'],
    'aviation process improvement analyst' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'process improvement', 'analysis', 'optimization', 'reporting'],
    'aviation risk control assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'risk assessment', 'controls', 'compliance', 'monitoring'],
    'aviation resource coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'resource planning', 'allocation', 'coordination', 'tracking'],
    'aviation workflow specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'workflow', 'efficiency', 'coordination', 'optimization'],
    'aviation performance analyst' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'performance metrics', 'kpi', 'analysis', 'reporting'],
    'aviation delivery coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'delivery', 'routing', 'coordination', 'tracking'],
    'aviation logistics assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'logistics', 'inventory', 'dispatch', 'coordination'],
    'aviation administrative officer' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'administration', 'documentation', 'organization', 'coordination'],
    'aviation reporting analyst' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'reporting', 'analysis', 'dashboards', 'data'],
    'aviation inventory controller' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'inventory control', 'stock monitoring', 'accuracy', 'reporting'],
    'aviation maintenance coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'maintenance', 'scheduling', 'inspection', 'coordination'],
    'aviation safety officer' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'safety', 'compliance', 'inspection', 'risk prevention'],
    'aviation site coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'site operations', 'coordination', 'safety', 'reporting'],
    'aviation procurement assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'procurement', 'vendor management', 'purchasing', 'documentation'],
    'aviation vendor coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'vendor relations', 'coordination', 'compliance', 'follow-up'],
    'aviation records officer' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'records management', 'documentation', 'filing', 'accuracy'],
    'aviation client support specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'client support', 'communication', 'issue resolution', 'service'],
    'aviation implementation assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'implementation', 'configuration', 'support', 'documentation'],
    'aviation integration specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'integration', 'systems', 'testing', 'technical support'],
    'aviation monitoring analyst' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'monitoring', 'incident tracking', 'analysis', 'reporting'],
    'aviation audit assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'audit support', 'documentation', 'compliance', 'verification'],
    'aviation research assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'research', 'data collection', 'analysis', 'documentation'],
    'aviation communications officer' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'communication', 'coordination', 'documentation', 'stakeholder support'],
    'aviation product support specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'product support', 'troubleshooting', 'customer service', 'documentation'],
    'aviation technical coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'technical coordination', 'planning', 'support', 'execution'],
    'aviation service coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'service delivery', 'coordination', 'customer support', 'scheduling'],
    'aviation operations supervisor' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'supervision', 'operations', 'team coordination', 'quality'],
    'aviation analytics specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'analytics', 'reporting', 'data insights', 'kpi'],
    'aviation engagement coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'engagement', 'communication', 'program support', 'coordination'],
    'aviation program assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'program support', 'documentation', 'coordination', 'tracking'],
    'aviation execution specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'execution', 'delivery', 'coordination', 'quality'],
    'aviation solutions assistant' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'solution support', 'problem solving', 'coordination', 'implementation'],
    'aviation production coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'production', 'planning', 'quality', 'coordination'],
    'aviation dispatch coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'dispatch', 'routing', 'tracking', 'coordination'],
    'aviation onboarding specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'onboarding', 'training', 'documentation', 'support'],
    'aviation account support specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'account support', 'client service', 'reporting', 'coordination'],
    'aviation compliance coordinator' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'compliance', 'policy', 'audit support', 'documentation'],
    'aviation quality assurance specialist' => ['flight operations support', 'safety compliance', 'ground coordination', 'service assurance', 'quality assurance', 'testing', 'standards', 'reporting'],

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