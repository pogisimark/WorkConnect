<?php
/**
 * Adds PESO approval workflow: contact_number, telephone_number, peso_verified.
 * Existing companies that were email-verified are treated as PESO-approved.
 */
function ensureCompanyPesoSchema(mysqli $conn): void {
    if (!$conn) {
        return;
    }
    $columns = [
        'contact_number' => 'VARCHAR(40) NULL DEFAULT NULL',
        'telephone_number' => 'VARCHAR(40) NULL DEFAULT NULL',
        'peso_verified' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'business_permit_path' => 'VARCHAR(255) NULL DEFAULT NULL',
        'certificates_json' => 'TEXT NULL',
        'privacy_consent' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'privacy_consent_at' => 'DATETIME NULL DEFAULT NULL',
    ];
    foreach ($columns as $field => $def) {
        $r = @$conn->query("SHOW COLUMNS FROM company_users LIKE '" . $conn->real_escape_string($field) . "'");
        if ($r && $r->num_rows === 0) {
            @$conn->query("ALTER TABLE company_users ADD COLUMN `$field` $def");
        }
    }
    // Legacy: anyone marked email-verified can still log in (PESO had effectively approved them before this feature).
    @$conn->query('UPDATE company_users SET peso_verified = 1 WHERE COALESCE(email_verified, 0) = 1 AND COALESCE(peso_verified, 0) = 0');
}

function companyHasPesoColumn(mysqli $conn): bool {
    if (!$conn) {
        return false;
    }
    $r = @$conn->query("SHOW COLUMNS FROM company_users LIKE 'peso_verified'");
    return $r && $r->num_rows > 0;
}
