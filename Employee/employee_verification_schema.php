<?php
/**
 * Ensures employee_users has email verification columns.
 * On first run, existing users are marked verified so they are not locked out.
 */
function ensureEmployeeVerificationSchema(mysqli $conn): void {
    if (!$conn) {
        return;
    }
    $r = @$conn->query("SHOW COLUMNS FROM employee_users LIKE 'email_verified'");
    if ($r && $r->num_rows > 0) {
        return;
    }
    $sql = "ALTER TABLE employee_users
        ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0,
        ADD COLUMN email_verify_token VARCHAR(64) NULL DEFAULT NULL,
        ADD COLUMN email_verify_expires DATETIME NULL DEFAULT NULL";
    @$conn->query($sql);
    @$conn->query("CREATE INDEX idx_email_verify_token ON employee_users (email_verify_token)");
    // Grandfather existing accounts (created before this feature)
    @$conn->query("UPDATE employee_users SET email_verified = 1 WHERE email_verified = 0");
}
