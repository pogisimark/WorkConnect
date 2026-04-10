<?php
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/session_protect.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../Company/company_peso_schema.php';
require_once __DIR__ . '/../Company/company_signup_mail.php';
require_once __DIR__ . '/admin_audit_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

ensureCompanyPesoSchema($conn);

$input = json_decode(file_get_contents('php://input'), true);
$company_id = isset($input['company_id']) ? (int) $input['company_id'] : 0;
if ($company_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid company.']);
    exit;
}

$stmt = $conn->prepare('SELECT id, company_name, email, COALESCE(peso_verified, 0) AS pv FROM company_users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $company_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Company not found.']);
    exit;
}
if ((int) $row['pv'] === 1) {
    admin_audit_log($conn, 'COMPANY_VERIFY_SKIPPED', 'company', $company_id, 'Verification skipped: company already verified.');
    echo json_encode(['success' => false, 'message' => 'This company is already verified.']);
    exit;
}

$upd = $conn->prepare('UPDATE company_users SET peso_verified = 1, email_verified = 1, email_verify_token = NULL, email_verify_expires = NULL WHERE id = ?');
$upd->bind_param('i', $company_id);
if (!$upd->execute()) {
    $upd->close();
    admin_audit_log($conn, 'COMPANY_VERIFY_FAILED', 'company', $company_id, 'Company verification failed at database update.');
    echo json_encode(['success' => false, 'message' => 'Could not update account.']);
    exit;
}
$upd->close();

$loginUrl = workconnect_public_company_login_url();

$send = sendCompanyPesoApprovedEmail($row['email'], $row['company_name'], $loginUrl);
admin_audit_log(
    $conn,
    'COMPANY_VERIFY',
    'company',
    $company_id,
    'Company verified by admin.',
    ['company_name' => $row['company_name'] ?? '', 'email_sent' => (bool)($send['success'] ?? false)]
);
$conn->close();

echo json_encode([
    'success' => true,
    'message' => $send['success']
        ? 'Company verified. A confirmation email was sent to the company.'
        : 'Company verified, but the approval email could not be sent. Ask the company to try logging in.',
    'email_sent' => $send['success'],
]);
