<?php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$conn) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$company_id = isset($input['company_id']) ? (int) $input['company_id'] : 0;
$message = isset($input['message']) ? trim($conn->real_escape_string($input['message'] ?? '')) : '';
$message = $message === '' ? null : $message;

if ($company_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a company.']);
    exit;
}

$check = $conn->prepare("SELECT id FROM company_users WHERE id = ?");
$check->bind_param("i", $company_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    $check->close();
    echo json_encode(['success' => false, 'message' => 'Invalid company.']);
    exit;
}
$check->close();

$stmt = $conn->prepare("INSERT INTO admin_company_follow_up (company_id, message, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("is", $company_id, $message);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Your follow-up request has been sent to the company. They will be able to respond from their portal.']);
} else {
    $stmt->close();
    $conn->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit. Please try again.']);
}
