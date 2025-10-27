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
        SUM(CASE WHEN we = 'yes' THEN 1 ELSE 0 END) as wage_employed,
        SUM(CASE WHEN se = 'yes' THEN 1 ELSE 0 END) as self_employed,
        SUM(CASE WHEN ftjs = 'yes' THEN 1 ELSE 0 END) as first_time_jobseekers,
        SUM(CASE WHEN covid = 'yes' THEN 1 ELSE 0 END) as covid_displaced,
        SUM(CASE WHEN skill_auto_mechanic = 1 THEN 1 ELSE 0 END) as auto_mechanic,
        SUM(CASE WHEN skill_electrician = 1 THEN 1 ELSE 0 END) as electrician,
        SUM(CASE WHEN skill_photography = 1 THEN 1 ELSE 0 END) as photography,
        SUM(CASE WHEN skill_beautician = 1 THEN 1 ELSE 0 END) as beautician,
        SUM(CASE WHEN skill_embroidery = 1 THEN 1 ELSE 0 END) as embroidery,
        SUM(CASE WHEN skill_plumbing = 1 THEN 1 ELSE 0 END) as plumbing,
        SUM(CASE WHEN skill_carpentry = 1 THEN 1 ELSE 0 END) as carpentry,
        SUM(CASE WHEN skill_gardening = 1 THEN 1 ELSE 0 END) as gardening,
        SUM(CASE WHEN skill_sewing = 1 THEN 1 ELSE 0 END) as sewing,
        SUM(CASE WHEN skill_computer = 1 THEN 1 ELSE 0 END) as computer,
        SUM(CASE WHEN skill_masonry = 1 THEN 1 ELSE 0 END) as masonry,
        SUM(CASE WHEN skill_stenography = 1 THEN 1 ELSE 0 END) as stenography,
        SUM(CASE WHEN skill_domestic = 1 THEN 1 ELSE 0 END) as domestic,
        SUM(CASE WHEN skill_painter = 1 THEN 1 ELSE 0 END) as painter,
        SUM(CASE WHEN skill_tailoring = 1 THEN 1 ELSE 0 END) as tailoring,
        SUM(CASE WHEN skill_driver = 1 THEN 1 ELSE 0 END) as driver,
        SUM(CASE WHEN skill_painting = 1 THEN 1 ELSE 0 END) as painting_job
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
        
        // Calculate percentages and rankings
        foreach ($barangays as &$barangay) {
            $barangay['percentage_of_total'] = $totalRegistrations > 0 ? 
                round(($barangay['total_registrations'] / $totalRegistrations) * 100, 1) : 0;
            
            // Calculate top skills for this barangay
            $skills = [
                'Auto Mechanic' => $barangay['auto_mechanic'],
                'Electrician' => $barangay['electrician'],
                'Photography' => $barangay['photography'],
                'Beautician' => $barangay['beautician'],
                'Embroidery' => $barangay['embroidery'],
                'Plumbing' => $barangay['plumbing'],
                'Carpentry' => $barangay['carpentry'],
                'Gardening' => $barangay['gardening'],
                'Sewing' => $barangay['sewing'],
                'Computer Literacy' => $barangay['computer'],
                'Masonry' => $barangay['masonry'],
                'Stenography' => $barangay['stenography'],
                'Domestic Chores' => $barangay['domestic'],
                'Painter/Artist' => $barangay['painter'],
                'Tailoring' => $barangay['tailoring'],
                'Driving' => $barangay['driver'],
                'Painting Job' => $barangay['painting_job']
            ];
            
            // Sort skills by count and get top 3
            arsort($skills);
            $barangay['top_skills'] = array_slice($skills, 0, 3, true);
        }
        
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
