<?php
/**
 * Ensures announcements support first-publish tracking and "closed" visibility state.
 */
if (!function_exists('ensure_announcements_publish_tracking')) {
    function ensure_announcements_publish_tracking($conn)
    {
        static $done = false;
        if ($done || !$conn) {
            return;
        }
        $done = true;
        $t = @$conn->query("SHOW TABLES LIKE 'announcements'");
        if (!$t || $t->num_rows === 0) {
            return;
        }
        $col = @$conn->query("SHOW COLUMNS FROM announcements LIKE 'first_published_at'");
        if ($col && $col->num_rows === 0) {
            @$conn->query("ALTER TABLE announcements ADD COLUMN first_published_at DATETIME NULL DEFAULT NULL AFTER date_posted");
        }
        $st = @$conn->query("SHOW COLUMNS FROM announcements WHERE Field = 'status'");
        if ($st && $st->num_rows > 0) {
            $type = strtolower((string) ($st->fetch_assoc()['Type'] ?? ''));
            if (strpos($type, 'closed') === false) {
                @$conn->query("ALTER TABLE announcements MODIFY COLUMN status ENUM('draft','published','archived','closed') DEFAULT 'draft'");
            }
        }
        // Rows already live (or were archived) should not trigger "first publish" emails again
        @$conn->query("UPDATE announcements SET first_published_at = COALESCE(first_published_at, date_posted, created_at) WHERE first_published_at IS NULL AND status IN ('published','archived','closed')");
    }
}
