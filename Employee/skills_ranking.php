<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Get all jobseekers with their skills
    $sql = "SELECT 
        skill_auto_mechanic, skill_electrician, skill_photography, skill_beautician, 
        skill_embroidery, skill_plumbing, skill_carpentry, skill_gardening, skill_sewing, 
        skill_computer, skill_masonry, skill_stenography, skill_domestic, skill_painter, 
        skill_tailoring, skill_driver, skill_painting, skill_others
        FROM jobseeker";
    
    $result = $conn->query($sql);
    
    $skillCounts = [];
    $totalSkills = 0;
    
    // Map of skill fields to display names
    $skillMap = [
        'skill_auto_mechanic' => 'Auto Mechanic',
        'skill_electrician' => 'Electrician',
        'skill_photography' => 'Photography',
        'skill_beautician' => 'Beautician',
        'skill_embroidery' => 'Embroidery',
        'skill_plumbing' => 'Plumbing',
        'skill_carpentry' => 'Carpentry',
        'skill_gardening' => 'Gardening',
        'skill_sewing' => 'Sewing',
        'skill_computer' => 'Computer Literacy',
        'skill_masonry' => 'Masonry',
        'skill_stenography' => 'Stenography',
        'skill_domestic' => 'Domestic Chores',
        'skill_painter' => 'Painter/Artist',
        'skill_tailoring' => 'Tailoring',
        'skill_driver' => 'Driving',
        'skill_painting' => 'Painting Job'
    ];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Count predefined skills
            foreach ($skillMap as $field => $skillName) {
                if ($row[$field] == 1) {
                    if (!isset($skillCounts[$skillName])) {
                        $skillCounts[$skillName] = 0;
                    }
                    $skillCounts[$skillName]++;
                    $totalSkills++;
                }
            }
            
            // Parse skill_others if exists
            if (!empty($row['skill_others']) && $row['skill_others'] !== 'n/a') {
                $others = explode(',', $row['skill_others']);
                foreach ($others as $other) {
                    $other = trim($other);
                    if (!empty($other)) {
                        if (!isset($skillCounts[$other])) {
                            $skillCounts[$other] = 0;
                        }
                        $skillCounts[$other]++;
                        $totalSkills++;
                    }
                }
            }
        }
    }
    
    // Convert to array and sort by count (descending)
    $skillsArray = [];
    foreach ($skillCounts as $skill => $count) {
        $skillsArray[] = [
            'skill' => $skill,
            'count' => $count
        ];
    }
    
    // Sort by count descending
    usort($skillsArray, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Get top 6 skills
    $topSkills = array_slice($skillsArray, 0, 6);
    
    echo json_encode([
        'success' => true,
        'skills' => $topSkills,
        'totalSkills' => $totalSkills
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching skills ranking: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

