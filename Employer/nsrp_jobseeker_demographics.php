<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    $edu = "LOWER(CONCAT(IFNULL(`level`,''), ' ', IFNULL(`level_reached`,''), ' ', IFNULL(`course`,'')))";

    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(TRIM(COALESCE(sex,''))) IN ('male','m') THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN LOWER(TRIM(COALESCE(sex,''))) IN ('female','f') THEN 1 ELSE 0 END) as female,
        SUM(CASE 
            WHEN dob IS NOT NULL AND dob != '0000-00-00' 
            AND (YEAR(CURDATE()) - YEAR(dob) - (DATE_FORMAT(CURDATE(),'%m%d') < DATE_FORMAT(dob,'%m%d'))) BETWEEN 15 AND 25 
            THEN 1 ELSE 0 END) as age_15_25,
        SUM(CASE 
            WHEN dob IS NOT NULL AND dob != '0000-00-00' 
            AND (YEAR(CURDATE()) - YEAR(dob) - (DATE_FORMAT(CURDATE(),'%m%d') < DATE_FORMAT(dob,'%m%d'))) BETWEEN 26 AND 35 
            THEN 1 ELSE 0 END) as age_26_35,
        SUM(CASE 
            WHEN dob IS NOT NULL AND dob != '0000-00-00' 
            AND (YEAR(CURDATE()) - YEAR(dob) - (DATE_FORMAT(CURDATE(),'%m%d') < DATE_FORMAT(dob,'%m%d'))) BETWEEN 36 AND 45 
            THEN 1 ELSE 0 END) as age_36_45,
        SUM(CASE 
            WHEN dob IS NOT NULL AND dob != '0000-00-00' 
            AND (YEAR(CURDATE()) - YEAR(dob) - (DATE_FORMAT(CURDATE(),'%m%d') < DATE_FORMAT(dob,'%m%d'))) > 45 
            THEN 1 ELSE 0 END) as age_46_plus,
        SUM(CASE WHEN IFNULL(unemployed,0) = 1 THEN 1 ELSE 0 END) as unemployed,
        SUM(CASE WHEN IFNULL(employed,0) = 1 AND IFNULL(employment_type_wage,0) = 1 THEN 1 ELSE 0 END) as wage_employed,
        SUM(CASE WHEN IFNULL(employed,0) = 1 AND IFNULL(employment_type_self,0) = 1 THEN 1 ELSE 0 END) as self_employed,
        SUM(CASE WHEN $edu LIKE '%elementary%' THEN 1 ELSE 0 END) as elementary,
        SUM(CASE WHEN $edu LIKE '%secondary%' OR $edu LIKE '%k-12%' OR $edu LIKE '%high school%' THEN 1 ELSE 0 END) as high_school,
        SUM(CASE WHEN $edu LIKE '%tertiary%' OR $edu LIKE '%graduate%' OR $edu LIKE '%post-graduate%' OR $edu LIKE '%college%' THEN 1 ELSE 0 END) as college,
        SUM(CASE WHEN $edu LIKE '%vocational%' OR $edu LIKE '%tesda%' OR $edu LIKE '%tech-voc%' THEN 1 ELSE 0 END) as vocational
    FROM jobseeker";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();

        $total = (int) $data['total'];
        $data['male_percentage'] = $total > 0 ? round(($data['male'] / $total) * 100, 1) : 0;
        $data['female_percentage'] = $total > 0 ? round(($data['female'] / $total) * 100, 1) : 0;

        $data['age_15_25_percentage'] = $total > 0 ? round(($data['age_15_25'] / $total) * 100, 1) : 0;
        $data['age_26_35_percentage'] = $total > 0 ? round(($data['age_26_35'] / $total) * 100, 1) : 0;
        $data['age_36_45_percentage'] = $total > 0 ? round(($data['age_36_45'] / $total) * 100, 1) : 0;
        $data['age_46_plus_percentage'] = $total > 0 ? round(($data['age_46_plus'] / $total) * 100, 1) : 0;

        $data['unemployed_percentage'] = $total > 0 ? round(($data['unemployed'] / $total) * 100, 1) : 0;
        $data['wage_employed_percentage'] = $total > 0 ? round(($data['wage_employed'] / $total) * 100, 1) : 0;
        $data['self_employed_percentage'] = $total > 0 ? round(($data['self_employed'] / $total) * 100, 1) : 0;

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
