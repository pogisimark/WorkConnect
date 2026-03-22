<?php
/**
 * Unread company replies to admin-initiated follow-ups (REQUEST FOLLOW UP sidebar).
 */
require_once __DIR__ . '/follow_up_pending_badge.php';

if (!function_exists('acfu_ensure_admin_response_read_column')) {
    function acfu_ensure_admin_response_read_column($conn)
    {
        static $done = false;
        if ($done || !$conn) {
            return;
        }
        $done = true;
        $r = @$conn->query("SHOW COLUMNS FROM admin_company_follow_up LIKE 'admin_response_read_at'");
        if ($r && $r->num_rows === 0) {
            @$conn->query("ALTER TABLE admin_company_follow_up ADD COLUMN admin_response_read_at DATETIME NULL DEFAULT NULL AFTER responded_at");
            // Existing answered threads: treat as already read so badge doesn't spike
            @$conn->query("UPDATE admin_company_follow_up SET admin_response_read_at = COALESCE(responded_at, NOW()) WHERE status = 'answered' AND company_response IS NOT NULL AND TRIM(company_response) <> ''");
        }
    }
}

if (!function_exists('acfu_get_unread_response_count')) {
    function acfu_get_unread_response_count($conn)
    {
        acfu_ensure_admin_response_read_column($conn);
        if (!$conn) {
            return 0;
        }
        $sql = "SELECT COUNT(*) AS c FROM admin_company_follow_up 
            WHERE COALESCE(hidden_by_admin, 0) = 0 
            AND status = 'answered' 
            AND company_response IS NOT NULL AND TRIM(company_response) <> '' 
            AND admin_response_read_at IS NULL";
        $res = @$conn->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            return (int) $row['c'];
        }
        return 0;
    }
}

if (!function_exists('acfu_unread_badge_html')) {
    function acfu_unread_badge_html($count)
    {
        return fu_follow_up_badge_html($count);
    }
}
