<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Get all accepted jobseekers with their skills
    $sql = "SELECT 
        skill_auto_mechanic, skill_electrician, skill_photography, skill_beautician, 
        skill_embroidery, skill_plumbing, skill_carpentry, skill_gardening, skill_sewing, 
        skill_computer, skill_masonry, skill_stenography, skill_domestic, skill_painter, 
        skill_tailoring, skill_driver, skill_painting, skill_others
        FROM jobseeker
        WHERE application_status = 'Accepted'";
    
    $result = $conn->query($sql);
    
    $skillCounts = [];
    $totalAccepted = 0;
    
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
            $totalAccepted++;
            $hasSkills = false;
            
            // Count predefined skills
            foreach ($skillMap as $field => $skillName) {
                if ($row[$field] == 1) {
                    $hasSkills = true;
                    if (!isset($skillCounts[$skillName])) {
                        $skillCounts[$skillName] = 0;
                    }
                    $skillCounts[$skillName]++;
                }
            }
            
            // Parse skill_others if exists
            if (!empty($row['skill_others']) && $row['skill_others'] !== 'n/a') {
                $others = explode(',', $row['skill_others']);
                foreach ($others as $other) {
                    $other = trim($other);
                    if (!empty($other)) {
                        $hasSkills = true;
                        if (!isset($skillCounts[$other])) {
                            $skillCounts[$other] = 0;
                        }
                        $skillCounts[$other]++;
                    }
                }
            }
        }
    }
    
    // Convert to array and sort by count (descending)
    $skillsArray = [];
    foreach ($skillCounts as $skill => $count) {
        $percentage = $totalAccepted > 0 ? round(($count / $totalAccepted) * 100, 1) : 0;
        $skillsArray[] = [
            'skill' => $skill,
            'count' => $count,
            'percentage' => $percentage
        ];
    }
    
    // Same priority as skills gap: % of accepted (desc), then count (desc)
    usort($skillsArray, function ($a, $b) {
        if ($a['percentage'] != $b['percentage']) {
            return $b['percentage'] <=> $a['percentage'];
        }
        return $b['count'] - $a['count'];
    });
    
    $topSkills = array_slice($skillsArray, 0, 10);
    
    echo json_encode([
        'success' => true,
        'total_accepted' => $totalAccepted,
        'skills' => $topSkills
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching most accepted skills: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

