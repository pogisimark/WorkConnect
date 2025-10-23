<?php
// skill_registry.php
header('Content-Type: application/json');
require_once 'db.php'; // adjust path as needed

$method = $_SERVER['REQUEST_METHOD'];

function sanitize($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

if ($method === 'GET') {
    // Get all skills for a barangay
    $barangay = isset($_GET['barangay']) ? sanitize($_GET['barangay']) : '';
    $month = isset($_GET['month']) && $_GET['month'] !== '' ? intval($_GET['month']) : null;
    $year = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
    $sql = "SELECT * FROM skill_registry WHERE barangay = ?";
    $params = [$barangay];
    $types = 's';
    if ($month && $year) {
        $sql .= " AND MONTH(survey_date) = ? AND YEAR(survey_date) = ?";
        $params[] = $month;
        $params[] = $year;
        $types .= 'ii';
    } elseif ($month) {
        $sql .= " AND MONTH(survey_date) = ?";
        $params[] = $month;
        $types .= 'i';
    } elseif ($year) {
        $sql .= " AND YEAR(survey_date) = ?";
        $params[] = $year;
        $types .= 'i';
    }
    $sql .= " ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode(['success'=>true, 'data'=>$rows]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { echo json_encode(['success'=>false, 'msg'=>'No data']); exit; }
    $barangay = sanitize($data['barangay'] ?? '');
    $city = 'Norzagaray';
    $fields = [
        'survey_date','printed_name','dob','ftjs','covid','marital','address','contact','education','age','sex',
        'we_position','we_months','se_business','se_months','ue','skills'
    ];
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $sql = "INSERT INTO skill_registry (barangay, city, ".implode(',', $fields).") VALUES (?, ?, $placeholders)";
    $stmt = $conn->prepare($sql);
    $params = [$barangay, $city];
    foreach ($fields as $f) $params[] = $data[$f] ?? '';
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $ok = $stmt->execute();
    if (!$ok) error_log($stmt->error);
    echo json_encode(['success'=>$ok]);
    exit;
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['id'])) { echo json_encode(['success'=>false, 'msg'=>'No id']); exit; }
    $id = intval($data['id']);
    $fields = [
        'survey_date','printed_name','dob','ftjs','covid','marital','address','contact','education','age','sex',
        'we_position','we_months','se_business','se_months','ue','skills'
    ];
    $sets = implode(',', array_map(fn($f)=>"$f=?", $fields));
    $sql = "UPDATE skill_registry SET $sets WHERE id=?";
    $stmt = $conn->prepare($sql);
    $params = [];
    foreach ($fields as $f) $params[] = $data[$f] ?? '';
    $params[] = $id;
    $stmt->bind_param(str_repeat('s', count($fields)).'i', ...$params);
    $ok = $stmt->execute();
    echo json_encode(['success'=>$ok]);
    exit;
}

echo json_encode(['success'=>false, 'msg'=>'Invalid request']);
