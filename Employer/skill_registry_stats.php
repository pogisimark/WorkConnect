<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    $total_skill_mentions = 0;
    $res = $conn->query("SELECT skills FROM skill_registry WHERE skills IS NOT NULL AND TRIM(skills) <> ''");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $parts = array_filter(array_map('trim', explode(',', $row['skills'])));
            $total_skill_mentions += count($parts);
        }
    }
    echo json_encode([
        'success' => true,
        'total_skill_mentions' => $total_skill_mentions
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'total_skill_mentions' => 0
    ]);
}

$conn->close();
