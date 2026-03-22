<?php
/**
 * Resend employee email verification (JSON API for login page).
 */
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'db.php';
require_once 'employee_verification_schema.php';
require_once 'send_employee_verification_email.php';

header('Content-Type: application/json');

function jsonOut($success, $message) {
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

ensureEmployeeVerificationSchema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Invalid request.');
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonOut(false, 'Please enter a valid email address.');
}

$stmt = $conn->prepare("SELECT id, firstname, email_verified FROM employee_users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$r = $stmt->get_result();
if (!$r->num_rows) {
    jsonOut(true, 'If an unverified account exists for this email, a verification link has been sent.');
}
$user = $r->fetch_assoc();
$stmt->close();

if ((int)$user['email_verified'] === 1) {
    jsonOut(true, 'If an unverified account exists for this email, a verification link has been sent.');
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+48 hours'));
$uid = (int)$user['id'];
$up = $conn->prepare("UPDATE employee_users SET email_verify_token = ?, email_verify_expires = ? WHERE id = ?");
$up->bind_param("ssi", $token, $expires, $uid);
if (!$up->execute()) {
    jsonOut(false, 'Could not process request. Try again later.');
}
$up->close();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
$verifyLink = $scheme . '://' . $host . $dir . '/verify_email.php?token=' . urlencode($token);

$result = sendEmployeeVerificationEmail($email, $user['firstname'] ?? 'User', $verifyLink);
jsonOut($result['success'], $result['success']
    ? 'Verification email sent. Please check your inbox.'
    : $result['message']);
