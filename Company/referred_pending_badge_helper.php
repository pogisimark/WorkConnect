<?php
/**
 * Pending referrals for this company (awaiting Accept/Reject) — sidebar badge on "Referred".
 */
if (!function_exists('company_referred_pending_count_for_sidebar')) {
    function company_referred_pending_count_for_sidebar($conn, $company_id)
    {
        $company_id = (int) $company_id;
        if (!$conn || $company_id <= 0) {
            return 0;
        }
        $tbl = @$conn->query("SHOW TABLES LIKE 'jobseeker_company_referrals'");
        if ($tbl && $tbl->num_rows > 0) {
            $stmt = $conn->prepare(
                'SELECT COUNT(*) AS c FROM jobseeker_company_referrals WHERE company_id = ? AND status = ?'
            );
            if ($stmt) {
                $pending = 'pending';
                $stmt->bind_param('is', $company_id, $pending);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                return (int) ($row['c'] ?? 0);
            }
        }
        $col = @$conn->query("SHOW COLUMNS FROM jobseeker LIKE 'referred_to_company_id'");
        if ($col && $col->num_rows > 0) {
            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS c FROM jobseeker WHERE referred_to_company_id = ? AND application_status = 'Referred'"
            );
            if ($stmt) {
                $stmt->bind_param('i', $company_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                return (int) ($row['c'] ?? 0);
            }
        }

        return 0;
    }
}

if (!function_exists('company_referred_pending_badge_html')) {
    function company_referred_pending_badge_html($count)
    {
        $n = (int) $count;
        if ($n < 1) {
            return '';
        }
        $display = $n > 99 ? '99+' : (string) $n;
        $title = $n . ' referral' . ($n === 1 ? '' : 's') . ' pending your review';
        $style = 'display:inline-flex;align-items:center;justify-content:center;align-self:center;min-width:20px;height:20px;padding:0 7px;'
            . 'margin-left:8px;margin-top:0;vertical-align:middle;background:#f44336;color:#fff;font-size:0.68rem;font-weight:700;border-radius:999px;'
            . 'line-height:1;box-shadow:0 1px 3px rgba(0,0,0,0.22);flex-shrink:0;';

        return '<span class="company-referred-pending-badge" style="' . $style . '" title="' . htmlspecialchars($title) . '" aria-label="' . htmlspecialchars($title) . '">'
            . htmlspecialchars($display) . '</span>';
    }
}
