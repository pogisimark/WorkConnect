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
    
    // Server-side validation: required fields
    $printed_name = trim($data['printed_name'] ?? '');
    $survey_date = trim($data['survey_date'] ?? '');
    $education = trim($data['education'] ?? '');
    $ftjs = trim($data['ftjs'] ?? '');
    $covid = trim($data['covid'] ?? '');
    $skills = trim($data['skills'] ?? '');
    
    if (!$survey_date) { echo json_encode(['success'=>false, 'msg'=>'Date of Survey is required.']); exit; }
    if (!$printed_name) { echo json_encode(['success'=>false, 'msg'=>'Full Name is required.']); exit; }
    if (empty(trim($data['dob'] ?? ''))) { echo json_encode(['success'=>false, 'msg'=>'Date of Birth is required.']); exit; }
    if (empty(trim($data['sex'] ?? ''))) { echo json_encode(['success'=>false, 'msg'=>'Sex is required.']); exit; }
    if (empty(trim($data['marital'] ?? ''))) { echo json_encode(['success'=>false, 'msg'=>'Marital Status is required.']); exit; }
    if (empty(trim($data['contact'] ?? ''))) { echo json_encode(['success'=>false, 'msg'=>'Contact Number is required.']); exit; }
    if (empty(trim($data['address'] ?? ''))) { echo json_encode(['success'=>false, 'msg'=>'Address is required.']); exit; }
    if (!$education) { echo json_encode(['success'=>false, 'msg'=>'Educational Attainment is required.']); exit; }
    if (!$ftjs) { echo json_encode(['success'=>false, 'msg'=>'First-Time Jobseeker (Yes/No) is required.']); exit; }
    if (!$covid) { echo json_encode(['success'=>false, 'msg'=>'COVID-19 Displaced Worker (Yes/No) is required.']); exit; }
    $skillsArr = array_filter(array_map('trim', explode(',', $skills)));
    if (empty($skillsArr)) { echo json_encode(['success'=>false, 'msg'=>'At least one skill is required.']); exit; }
    
    // Duplicate full name check (same barangay, case-insensitive)
    $checkStmt = $conn->prepare("SELECT id FROM skill_registry WHERE barangay = ? AND LOWER(TRIM(printed_name)) = LOWER(?)");
    $checkStmt->bind_param('ss', $barangay, $printed_name);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success'=>false, 'msg'=>'A person with this full name already exists in this barangay. Please check for duplicate entry.']);
        exit;
    }
    
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
