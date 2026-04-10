<?php
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../Employee/db.php';
require_once __DIR__ . '/../jobseeker_expiry_helper.php';

if (!isset($conn) || !$conn || $conn->connect_error) {
    http_response_code(500);
    echo "DB connection failed\n";
    exit(1);
}

workconnect_ensure_jobseeker_expiry_schema($conn);
$expired = workconnect_expire_inactive_jobseekers($conn, 30);
echo "Expired records: " . $expired . PHP_EOL;

$conn->close();
exit(0);

