<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'session_init.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get user's skills
    $userSkills = [];
    $userSkillsQuery = "SELECT 
        skill_auto_mechanic, skill_electrician, skill_photography, skill_beautician, 
        skill_embroidery, skill_plumbing, skill_carpentry, skill_gardening, skill_sewing, 
        skill_computer, skill_masonry, skill_stenography, skill_domestic, skill_painter, 
        skill_tailoring, skill_driver, skill_painting, skill_others
        FROM jobseeker 
        WHERE user_id = ? 
        ORDER BY id DESC 
        LIMIT 1";
    
    $stmt = $conn->prepare($userSkillsQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $predefinedSkills = [
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
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        foreach ($predefinedSkills as $field => $skillName) {
            if ($row[$field] == 1) {
                $userSkills[] = $skillName;
            }
        }
        
        // Parse skill_others if exists
        if (!empty($row['skill_others']) && $row['skill_others'] !== 'n/a') {
            $others = explode(',', $row['skill_others']);
            foreach ($others as $other) {
                $other = trim($other);
                if (!empty($other)) {
                    $userSkills[] = $other;
                }
            }
        }
    }
    $stmt->close();
    
    // Get most accepted skills
    $acceptedSkillsQuery = "SELECT 
        skill_auto_mechanic, skill_electrician, skill_photography, skill_beautician, 
        skill_embroidery, skill_plumbing, skill_carpentry, skill_gardening, skill_sewing, 
        skill_computer, skill_masonry, skill_stenography, skill_domestic, skill_painter, 
        skill_tailoring, skill_driver, skill_painting, skill_others
        FROM jobseeker
        WHERE application_status = 'Accepted'";
    
    $result = $conn->query($acceptedSkillsQuery);
    $acceptedSkillCounts = [];
    $totalAccepted = 0;
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $totalAccepted++;
            
            // Count predefined skills
            foreach ($predefinedSkills as $field => $skillName) {
                if ($row[$field] == 1) {
                    if (!isset($acceptedSkillCounts[$skillName])) {
                        $acceptedSkillCounts[$skillName] = 0;
                    }
                    $acceptedSkillCounts[$skillName]++;
                }
            }
            
            // Parse skill_others
            if (!empty($row['skill_others']) && $row['skill_others'] !== 'n/a') {
                $others = explode(',', $row['skill_others']);
                foreach ($others as $other) {
                    $other = trim($other);
                    if (!empty($other)) {
                        if (!isset($acceptedSkillCounts[$other])) {
                            $acceptedSkillCounts[$other] = 0;
                        }
                        $acceptedSkillCounts[$other]++;
                    }
                }
            }
        }
    }
    
    // Calculate percentage for each accepted skill
    $acceptedSkillsWithPercentage = [];
    foreach ($acceptedSkillCounts as $skill => $count) {
        $percentage = $totalAccepted > 0 ? round(($count / $totalAccepted) * 100, 1) : 0;
        $acceptedSkillsWithPercentage[$skill] = [
            'count' => $count,
            'percentage' => $percentage
        ];
    }
    
    // Sort by percentage descending
    uasort($acceptedSkillsWithPercentage, function($a, $b) {
        return $b['percentage'] - $a['percentage'];
    });
    
    // Get top accepted skills
    $topAcceptedSkills = array_slice($acceptedSkillsWithPercentage, 0, 10, true);
    
    // Find missing skills (skills that accepted jobseekers have but user doesn't)
    $missingSkills = [];
    $userSkillsLower = array_map('strtolower', $userSkills);
    
    foreach ($topAcceptedSkills as $skill => $data) {
        if (!in_array(strtolower($skill), $userSkillsLower)) {
            $missingSkills[] = [
                'skill' => $skill,
                'percentage' => $data['percentage'],
                'count' => $data['count']
            ];
        }
    }
    
    // Sort missing skills by percentage
    usort($missingSkills, function($a, $b) {
        return $b['percentage'] - $a['percentage'];
    });
    
    // Get top 3-5 missing skills as recommendations
    $recommendations = array_slice($missingSkills, 0, 5);
    
    // Calculate match score
    $matchedSkills = 0;
    foreach ($userSkills as $userSkill) {
        if (isset($topAcceptedSkills[$userSkill])) {
            $matchedSkills++;
        }
    }
    
    $matchScore = count($topAcceptedSkills) > 0 
        ? round(($matchedSkills / min(count($topAcceptedSkills), 10)) * 100, 1) 
        : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user_skills_count' => count($userSkills),
            'user_skills' => $userSkills,
            'top_accepted_skills' => array_map(function($skill, $data) {
                return [
                    'skill' => $skill,
                    'percentage' => $data['percentage'],
                    'count' => $data['count']
                ];
            }, array_keys($topAcceptedSkills), $topAcceptedSkills),
            'missing_skills' => $missingSkills,
            'recommendations' => $recommendations,
            'match_score' => $matchScore,
            'total_accepted_jobseekers' => $totalAccepted
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching skills gap analysis: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

