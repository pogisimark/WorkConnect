<?php
/**
 * Soft-delete uses status = 'Deleted'; ensure job_postings.status ENUM allows it.
 */
if (!function_exists('ensure_job_postings_status_allows_deleted')) {
    function ensure_job_postings_status_allows_deleted($conn)
    {
        static $checked = false;
        if ($checked || !$conn) {
            return;
        }
        $checked = true;
        $t = @$conn->query("SHOW TABLES LIKE 'job_postings'");
        if (!$t || $t->num_rows === 0) {
            return;
        }
        $r = @$conn->query("SHOW COLUMNS FROM job_postings WHERE Field = 'status'");
        if (!$r || $r->num_rows === 0) {
            return;
        }
        $type = (string) ($r->fetch_assoc()['Type'] ?? '');
        if (stripos($type, 'enum(') === false) {
            return;
        }
        if (preg_match("/'Deleted'/i", $type)) {
            return;
        }
        $alter = "ALTER TABLE job_postings MODIFY COLUMN status ENUM('Active', 'Closed', 'Draft', 'Deleted') DEFAULT 'Active'";
        @$conn->query($alter);
    }
}
