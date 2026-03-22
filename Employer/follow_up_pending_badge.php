<?php
/**
 * Sidebar badge: count of follow-up requests still pending admin response.
 * Requires mysqli $conn to follow_up_requests table.
 */
if (!function_exists('fu_get_pending_follow_up_count')) {
    function fu_get_pending_follow_up_count($conn)
    {
        if (!$conn || !($conn instanceof mysqli)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) AS c FROM follow_up_requests WHERE COALESCE(hidden_by_admin, 0) = 0 AND status = 'pending'";
        $res = @$conn->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            return (int) $row['c'];
        }
        return 0;
    }
}

if (!function_exists('fu_follow_up_badge_html')) {
    function fu_follow_up_badge_html($count)
    {
        $n = (int) $count;
        if ($n < 1) {
            return '';
        }
        $display = $n > 99 ? '99+' : (string) $n;
        $title = $n . ' pending follow-up request' . ($n === 1 ? '' : 's');
        $style = 'display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 7px;'
            . 'margin-left:8px;background:#f44336;color:#fff;font-size:0.68rem;font-weight:700;border-radius:999px;'
            . 'line-height:1;box-shadow:0 1px 3px rgba(0,0,0,0.22);flex-shrink:0;';
        return '<span class="fu-nav-badge" style="' . $style . '" title="' . htmlspecialchars($title) . '" aria-label="' . htmlspecialchars($title) . '">'
            . htmlspecialchars($display) . '</span>';
    }
}
