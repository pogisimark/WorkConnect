<?php
/**
 * Ensures company_users has email verification columns.
 * On first migration, existing company accounts are marked verified.
 */
function ensureCompanyVerificationSchema(mysqli $conn): void {
    if (!$conn) {
        return;
    }
    $r = @$conn->query("SHOW COLUMNS FROM company_users LIKE 'email_verified'");
    if ($r && $r->num_rows > 0) {
        return;
    }
    $sql = "ALTER TABLE company_users
        ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0,
        ADD COLUMN email_verify_token VARCHAR(64) NULL DEFAULT NULL,
        ADD COLUMN email_verify_expires DATETIME NULL DEFAULT NULL";
    @$conn->query($sql);
    @$conn->query("CREATE INDEX idx_company_email_verify_token ON company_users (email_verify_token)");
    @$conn->query("UPDATE company_users SET email_verified = 1 WHERE email_verified = 0");
}
