<?php
/**
 * Sidebar badge: count of pending jobseekers.
 */
if (!function_exists('js_get_pending_jobseekers_count')) {
    function js_get_pending_jobseekers_count($conn)
    {
        if (!$conn || !($conn instanceof mysqli)) {
            return 0;
        }

        $tableCheck = @$conn->query("SHOW TABLES LIKE 'jobseeker'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return 0;
        }

        // Treat NULL/empty as Pending to match existing app behavior.
        $sql = "SELECT COUNT(*) AS c
                FROM jobseeker
                WHERE COALESCE(NULLIF(TRIM(application_status), ''), 'Pending') = 'Pending'";
        $res = @$conn->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            return (int) $row['c'];
        }
        return 0;
    }
}

if (!function_exists('js_pending_jobseekers_badge_html')) {
    function js_pending_jobseekers_badge_html($count)
    {
        if (function_exists('fu_follow_up_badge_html')) {
            return fu_follow_up_badge_html($count);
        }

        $n = (int) $count;
        if ($n < 1) {
            return '';
        }
        $display = $n > 99 ? '99+' : (string) $n;
        $title = $n . ' pending jobseeker' . ($n === 1 ? '' : 's');
        $style = 'display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 7px;'
            . 'margin-left:8px;background:#f44336;color:#fff;font-size:0.68rem;font-weight:700;border-radius:999px;'
            . 'line-height:1;box-shadow:0 1px 3px rgba(0,0,0,0.22);flex-shrink:0;';
        return '<span class="js-nav-badge" style="' . $style . '" title="' . htmlspecialchars($title) . '" aria-label="' . htmlspecialchars($title) . '">'
            . htmlspecialchars($display) . '</span>';
    }
}

