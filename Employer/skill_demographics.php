<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Get demographic data from skill_registry table
    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN sex = 'M' THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN sex = 'F' THEN 1 ELSE 0 END) as female,
        SUM(CASE WHEN age BETWEEN 15 AND 25 THEN 1 ELSE 0 END) as age_15_25,
        SUM(CASE WHEN age BETWEEN 26 AND 35 THEN 1 ELSE 0 END) as age_26_35,
        SUM(CASE WHEN age BETWEEN 36 AND 45 THEN 1 ELSE 0 END) as age_36_45,
        SUM(CASE WHEN age > 45 THEN 1 ELSE 0 END) as age_46_plus,
        SUM(CASE WHEN ftjs = 'yes' THEN 1 ELSE 0 END) as first_time_jobseekers,
        SUM(CASE WHEN covid = 'yes' THEN 1 ELSE 0 END) as covid_displaced,
        SUM(CASE WHEN ue = 'yes' THEN 1 ELSE 0 END) as unemployed,
        SUM(CASE WHEN we_position IS NOT NULL AND we_position != '' THEN 1 ELSE 0 END) as wage_employed,
        SUM(CASE WHEN se_business IS NOT NULL AND se_business != '' THEN 1 ELSE 0 END) as self_employed,
        SUM(CASE WHEN education LIKE '%Elementary%' THEN 1 ELSE 0 END) as elementary,
        SUM(CASE WHEN education LIKE '%High School%' THEN 1 ELSE 0 END) as high_school,
        SUM(CASE WHEN education LIKE '%College%' THEN 1 ELSE 0 END) as college,
        SUM(CASE WHEN education LIKE '%Vocational%' THEN 1 ELSE 0 END) as vocational
    FROM skill_registry";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // Calculate percentages
        $total = $data['total'];
        $data['male_percentage'] = $total > 0 ? round(($data['male'] / $total) * 100, 1) : 0;
        $data['female_percentage'] = $total > 0 ? round(($data['female'] / $total) * 100, 1) : 0;
        
        // Age distribution percentages
        $data['age_15_25_percentage'] = $total > 0 ? round(($data['age_15_25'] / $total) * 100, 1) : 0;
        $data['age_26_35_percentage'] = $total > 0 ? round(($data['age_26_35'] / $total) * 100, 1) : 0;
        $data['age_36_45_percentage'] = $total > 0 ? round(($data['age_36_45'] / $total) * 100, 1) : 0;
        $data['age_46_plus_percentage'] = $total > 0 ? round(($data['age_46_plus'] / $total) * 100, 1) : 0;
        
        // Employment status percentages
        $data['unemployed_percentage'] = $total > 0 ? round(($data['unemployed'] / $total) * 100, 1) : 0;
        $data['wage_employed_percentage'] = $total > 0 ? round(($data['wage_employed'] / $total) * 100, 1) : 0;
        $data['self_employed_percentage'] = $total > 0 ? round(($data['self_employed'] / $total) * 100, 1) : 0;
        
        // Education percentages
        $data['elementary_percentage'] = $total > 0 ? round(($data['elementary'] / $total) * 100, 1) : 0;
        $data['high_school_percentage'] = $total > 0 ? round(($data['high_school'] / $total) * 100, 1) : 0;
        $data['college_percentage'] = $total > 0 ? round(($data['college'] / $total) * 100, 1) : 0;
        $data['vocational_percentage'] = $total > 0 ? round(($data['vocational'] / $total) * 100, 1) : 0;
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No demographic data found'
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
