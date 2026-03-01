<?php
date_default_timezone_set('Asia/Manila');
require_once 'session_check.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$conn) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$company_id = (int) $_SESSION['company_id'];
$input = json_decode(file_get_contents('php://input'), true);
$request_id = isset($input['request_id']) ? (int) $input['request_id'] : 0;
$response_text = isset($input['response']) ? trim($conn->real_escape_string($input['response'] ?? '')) : '';

if ($request_id <= 0 || $response_text === '') {
    echo json_encode(['success' => false, 'message' => 'Missing request ID or response text']);
    exit;
}

$stmt = $conn->prepare("UPDATE admin_company_follow_up SET company_response = ?, status = 'answered', responded_at = NOW() WHERE id = ? AND company_id = ? AND status = 'pending'");
$stmt->bind_param("sii", $response_text, $request_id, $company_id);
$stmt->execute();
if ($stmt->affected_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Request not found or already answered']);
    exit;
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Response sent.']);
