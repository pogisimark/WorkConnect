<?php
/**
 * Pending admin→company follow-ups (status pending, not hidden by company).
 */
if (!function_exists('company_admin_pending_request_count')) {
    function company_admin_pending_request_count($conn, $company_id)
    {
        $company_id = (int) $company_id;
        if (!$conn || $company_id <= 0) {
            return 0;
        }
        $chk = @$conn->query("SHOW TABLES LIKE 'admin_company_follow_up'");
        if (!$chk || $chk->num_rows === 0) {
            return 0;
        }
        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS c FROM admin_company_follow_up WHERE company_id = ? AND status = ? AND COALESCE(hidden_by_company, 0) = 0'
        );
        if (!$stmt) {
            return 0;
        }
        $pending = 'pending';
        $stmt->bind_param('is', $company_id, $pending);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['c'] ?? 0);
    }
}

if (!function_exists('company_admin_requests_badge_html')) {
    function company_admin_requests_badge_html($count)
    {
        $n = (int) $count;
        if ($n < 1) {
            return '';
        }
        $display = $n > 99 ? '99+' : (string) $n;
        $title = $n . ' admin request' . ($n === 1 ? '' : 's') . ' awaiting your response';
        $style = 'display:inline-flex;align-items:center;justify-content:center;align-self:center;min-width:20px;height:20px;padding:0 7px;'
            . 'margin-left:8px;margin-top:0;vertical-align:middle;background:#f44336;color:#fff;font-size:0.68rem;font-weight:700;border-radius:999px;'
            . 'line-height:1;box-shadow:0 1px 3px rgba(0,0,0,0.22);flex-shrink:0;';

        return '<span class="company-admin-req-badge" style="' . $style . '" title="' . htmlspecialchars($title) . '" aria-label="' . htmlspecialchars($title) . '">'
            . htmlspecialchars($display) . '</span>';
    }
}
