<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'db.php';
require_once 'company_verification_schema.php';
require_once 'send_company_verification_email.php';

header('Content-Type: application/json');

function companyResendJson($success, $message) {
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

ensureCompanyVerificationSchema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    companyResendJson(false, 'Invalid request.');
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    companyResendJson(false, 'Please enter a valid email address.');
}

$stmt = $conn->prepare("SELECT id, company_name, email_verified FROM company_users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$r = $stmt->get_result();
if (!$r->num_rows) {
    companyResendJson(true, 'If an unverified account exists for this email, a verification link has been sent.');
}
$user = $r->fetch_assoc();
$stmt->close();

if ((int)$user['email_verified'] === 1) {
    companyResendJson(true, 'If an unverified account exists for this email, a verification link has been sent.');
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+48 hours'));
$uid = (int)$user['id'];
$up = $conn->prepare("UPDATE company_users SET email_verify_token = ?, email_verify_expires = ? WHERE id = ?");
$up->bind_param("ssi", $token, $expires, $uid);
if (!$up->execute()) {
    companyResendJson(false, 'Could not process request. Try again later.');
}
$up->close();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
$verifyLink = $scheme . '://' . $host . $dir . '/verify_email.php?token=' . urlencode($token);

$result = sendCompanyVerificationEmail($email, $user['company_name'] ?? 'Company', $verifyLink);
companyResendJson($result['success'], $result['success']
    ? 'Verification email sent. Please check your inbox.'
    : $result['message']);
