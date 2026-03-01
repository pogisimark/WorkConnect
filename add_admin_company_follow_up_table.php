<?php
/**
 * One-time migration: create admin_company_follow_up table
 * (Admin requests follow-up from company; company can respond.)
 * Run once from browser, then safe to delete or leave.
 */
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/Employer/db.php';

if (!$conn) {
    die('Database connection failed.');
}

$check = $conn->query("SHOW TABLES LIKE 'admin_company_follow_up'");
if ($check && $check->num_rows > 0) {
    echo "Table admin_company_follow_up already exists. Nothing to do.";
    $conn->close();
    exit;
}

$sql = "CREATE TABLE admin_company_follow_up (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    message TEXT,
    status ENUM('pending','answered') DEFAULT 'pending',
    company_response TEXT,
    responded_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hidden_by_admin TINYINT(1) DEFAULT 0,
    hidden_by_company TINYINT(1) DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES company_users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_company_id (company_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table admin_company_follow_up created successfully.";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
