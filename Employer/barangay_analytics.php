<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Get barangay comparison data
    $query = "SELECT 
        barangay,
        COUNT(*) as total_registrations,
        SUM(CASE WHEN sex = 'M' THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN sex = 'F' THEN 1 ELSE 0 END) as female,
        SUM(CASE WHEN age BETWEEN 15 AND 25 THEN 1 ELSE 0 END) as age_15_25,
        SUM(CASE WHEN age BETWEEN 26 AND 35 THEN 1 ELSE 0 END) as age_26_35,
        SUM(CASE WHEN age BETWEEN 36 AND 45 THEN 1 ELSE 0 END) as age_36_45,
        SUM(CASE WHEN age > 45 THEN 1 ELSE 0 END) as age_46_plus,
        SUM(CASE WHEN ue = 'yes' THEN 1 ELSE 0 END) as unemployed,
        SUM(CASE WHEN we_position IS NOT NULL AND we_position != '' THEN 1 ELSE 0 END) as wage_employed,
        SUM(CASE WHEN se_business IS NOT NULL AND se_business != '' THEN 1 ELSE 0 END) as self_employed,
        SUM(CASE WHEN ftjs = 'yes' THEN 1 ELSE 0 END) as first_time_jobseekers,
        SUM(CASE WHEN covid = 'yes' THEN 1 ELSE 0 END) as covid_displaced,
        SUM(CASE WHEN skills LIKE '%Auto Mechanic%' THEN 1 ELSE 0 END) as auto_mechanic,
        SUM(CASE WHEN skills LIKE '%Electrician%' THEN 1 ELSE 0 END) as electrician,
        SUM(CASE WHEN skills LIKE '%Photography%' THEN 1 ELSE 0 END) as photography,
        SUM(CASE WHEN skills LIKE '%Beautician%' THEN 1 ELSE 0 END) as beautician,
        SUM(CASE WHEN skills LIKE '%Embroidery%' THEN 1 ELSE 0 END) as embroidery,
        SUM(CASE WHEN skills LIKE '%Plumbing%' THEN 1 ELSE 0 END) as plumbing,
        SUM(CASE WHEN skills LIKE '%Carpentry%' THEN 1 ELSE 0 END) as carpentry,
        SUM(CASE WHEN skills LIKE '%Gardening%' THEN 1 ELSE 0 END) as gardening,
        SUM(CASE WHEN skills LIKE '%Sewing%' THEN 1 ELSE 0 END) as sewing,
        SUM(CASE WHEN skills LIKE '%Computer%' THEN 1 ELSE 0 END) as computer,
        SUM(CASE WHEN skills LIKE '%Masonry%' THEN 1 ELSE 0 END) as masonry,
        SUM(CASE WHEN skills LIKE '%Stenography%' THEN 1 ELSE 0 END) as stenography,
        SUM(CASE WHEN skills LIKE '%Domestic%' THEN 1 ELSE 0 END) as domestic,
        SUM(CASE WHEN skills LIKE '%Painter%' THEN 1 ELSE 0 END) as painter,
        SUM(CASE WHEN skills LIKE '%Tailoring%' THEN 1 ELSE 0 END) as tailoring,
        SUM(CASE WHEN skills LIKE '%Driving%' THEN 1 ELSE 0 END) as driver,
        SUM(CASE WHEN skills LIKE '%Painting Job%' THEN 1 ELSE 0 END) as painting_job
    FROM skill_registry 
    GROUP BY barangay 
    ORDER BY total_registrations DESC";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $barangays = [];
        $totalRegistrations = 0;
        
        while ($row = $result->fetch_assoc()) {
            $barangays[] = $row;
            $totalRegistrations += $row['total_registrations'];
        }
        
        // Count actual skill tokens from skill_registry.skills (comma-separated), per barangay — not NSRP checkbox patterns
        $skillCountsByBarangay = [];
        $skillsTextRes = $conn->query("SELECT barangay, skills FROM skill_registry WHERE skills IS NOT NULL AND TRIM(skills) <> ''");
        if ($skillsTextRes) {
            while ($srow = $skillsTextRes->fetch_assoc()) {
                $bg = trim((string) $srow['barangay']);
                if ($bg === '') {
                    continue;
                }
                $parts = array_filter(array_map('trim', explode(',', (string) $srow['skills'])));
                if (!isset($skillCountsByBarangay[$bg])) {
                    $skillCountsByBarangay[$bg] = [];
                }
                foreach ($parts as $p) {
                    if ($p === '') {
                        continue;
                    }
                    $norm = mb_strtolower($p, 'UTF-8');
                    if (!isset($skillCountsByBarangay[$bg][$norm])) {
                        $skillCountsByBarangay[$bg][$norm] = ['label' => $p, 'count' => 0];
                    }
                    $skillCountsByBarangay[$bg][$norm]['count']++;
                }
            }
        }

        // Calculate percentages and rankings
        foreach ($barangays as &$barangay) {
            $barangay['percentage_of_total'] = $totalRegistrations > 0 ?
                round(($barangay['total_registrations'] / $totalRegistrations) * 100, 1) : 0;

            $bg = trim((string) $barangay['barangay']);
            $top = [];
            if (isset($skillCountsByBarangay[$bg]) && count($skillCountsByBarangay[$bg]) > 0) {
                $entries = $skillCountsByBarangay[$bg];
                uasort($entries, function ($a, $b) {
                    return $b['count'] <=> $a['count'];
                });
                $slice = array_slice($entries, 0, 10, true);
                foreach ($slice as $entry) {
                    if ($entry['count'] > 0) {
                        $top[$entry['label']] = $entry['count'];
                    }
                }
            }
            $barangay['top_skills'] = $top;
        }
        unset($barangay);
        
        // Get overall statistics
        $overallStats = [
            'total_barangays' => count($barangays),
            'total_registrations' => $totalRegistrations,
            'average_per_barangay' => count($barangays) > 0 ? round($totalRegistrations / count($barangays), 1) : 0,
            'most_active' => $barangays[0] ?? null,
            'least_active' => end($barangays) ?? null
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'barangays' => $barangays,
                'overall_stats' => $overallStats
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No barangay data found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
