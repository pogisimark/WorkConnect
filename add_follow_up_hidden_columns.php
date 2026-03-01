<?php
/**
 * One-time migration: add hidden_by_jobseeker and hidden_by_admin to follow_up_requests
 * so jobseeker "delete" only hides for jobseeker and admin "delete" only hides for admin.
 * Run once from browser or CLI, then you can delete this file or leave it (safe to run multiple times).
 */
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/Employer/db.php';

if (!$conn) {
    die('Database connection failed.');
}

$cols = [];
$res = $conn->query("SHOW COLUMNS FROM follow_up_requests LIKE 'hidden_by_%'");
while ($row = $res->fetch_assoc()) $cols[] = $row['Field'];
if (in_array('hidden_by_jobseeker', $cols) && in_array('hidden_by_admin', $cols)) {
    echo "Columns already exist. Nothing to do.";
    exit;
}
if (!in_array('hidden_by_jobseeker', $cols)) {
    $conn->query("ALTER TABLE follow_up_requests ADD COLUMN hidden_by_jobseeker TINYINT(1) DEFAULT 0");
    echo "Added hidden_by_jobseeker.<br>";
}
if (!in_array('hidden_by_admin', $cols)) {
    $conn->query("ALTER TABLE follow_up_requests ADD COLUMN hidden_by_admin TINYINT(1) DEFAULT 0");
    echo "Added hidden_by_admin.<br>";
}
echo "Done. Follow-up requests can now be hidden per side (jobseeker vs admin) without affecting the other.";
$conn->close();
