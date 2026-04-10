<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/session_protect.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../Company/company_peso_schema.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
if ($companyId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid company ID']);
    exit;
}

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

ensureCompanyPesoSchema($conn);
$sql = "SELECT id, company_name, email, contact_number, telephone_number, created_at, peso_verified,
        business_permit_path, certificates_json, privacy_consent, privacy_consent_at
        FROM company_users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $companyId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();
$conn->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Company not found']);
    exit;
}

$certificates = [];
if (!empty($row['certificates_json'])) {
    $parsed = json_decode((string)$row['certificates_json'], true);
    if (is_array($parsed)) {
        foreach ($parsed as $p) {
            if (is_string($p) && $p !== '') {
                $certificates[] = $p;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'company' => [
        'id' => (int)$row['id'],
        'company_name' => (string)$row['company_name'],
        'email' => (string)$row['email'],
        'contact_number' => (string)($row['contact_number'] ?? ''),
        'telephone_number' => (string)($row['telephone_number'] ?? ''),
        'created_at' => (string)($row['created_at'] ?? ''),
        'status' => ((int)($row['peso_verified'] ?? 0) === 1) ? 'Verified' : 'Pending',
        'business_permit_path' => (string)($row['business_permit_path'] ?? ''),
        'certificates' => $certificates,
        'privacy_consent' => ((int)($row['privacy_consent'] ?? 0) === 1),
        'privacy_consent_at' => (string)($row['privacy_consent_at'] ?? '')
    ]
]);
?>
