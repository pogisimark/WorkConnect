<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once __DIR__ . '/../jobseeker_placement_helper.php';
require_once 'session_init.php';
workconnect_ensure_jobseeker_placement_columns($conn);

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
        WHERE " . workconnect_jobseeker_sql_actively_placed_condition();
    
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
    
    // Sort by % of accepted (desc), then count (desc) — stable “top 10” rank
    uasort($acceptedSkillsWithPercentage, function ($a, $b) {
        if ($a['percentage'] != $b['percentage']) {
            return $b['percentage'] <=> $a['percentage'];
        }
        return $b['count'] <=> $a['count'];
    });
    
    // Top 10 skills among accepted jobseekers (same % rule: count / totalAccepted)
    $topAcceptedSkills = array_slice($acceptedSkillsWithPercentage, 0, 10, true);
    
    $userSkillsLower = array_map('strtolower', $userSkills);
    
    // How many of the current top-10 slots the user has (case-insensitive); each slot = 10% when 10 slots exist
    $matchedSkills = 0;
    foreach ($topAcceptedSkills as $skillName => $_data) {
        if (in_array(strtolower($skillName), $userSkillsLower, true)) {
            $matchedSkills++;
        }
    }
    
    $topCount = count($topAcceptedSkills);
    // Denominator: up to 10 — e.g. 6/10 => 60% (10% per matched top skill); if only 5 skills in list, 5/5 => 100% when fully matched
    $matchDenominator = $topCount > 0 ? min(10, $topCount) : 0;
    $matchScore = $matchDenominator > 0
        ? round(min(100, ($matchedSkills / $matchDenominator) * 100), 1)
        : 0;
    
    // Missing skills in top-10 rank order (do not re-sort — so gaps #1–5 first, then #6–10)
    $missingSkills = [];
    foreach ($topAcceptedSkills as $skill => $data) {
        if (!in_array(strtolower($skill), $userSkillsLower, true)) {
            $missingSkills[] = [
                'skill' => $skill,
                'percentage' => $data['percentage'],
                'count' => $data['count']
            ];
        }
    }
    
    // Top 5 missing by rank among the top 10; if user already has #1–5, next rows are #6–10; if none missing, empty (100% vs top 10)
    $recommendations = array_slice($missingSkills, 0, 5);
    
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

