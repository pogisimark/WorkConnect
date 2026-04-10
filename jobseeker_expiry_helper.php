<?php
date_default_timezone_set('Asia/Manila');

if (!function_exists('workconnect_ensure_jobseeker_expiry_schema')) {
    function workconnect_ensure_jobseeker_expiry_schema(mysqli $conn): void
    {
        if (!$conn || $conn->connect_error) {
            return;
        }

        $lastActiveCol = @$conn->query("SHOW COLUMNS FROM employee_users LIKE 'last_active_at'");
        if ($lastActiveCol && $lastActiveCol->num_rows === 0) {
            @$conn->query("ALTER TABLE employee_users ADD COLUMN last_active_at DATETIME NULL AFTER updated_at");
            @$conn->query("UPDATE employee_users SET last_active_at = NOW() WHERE last_active_at IS NULL");
        }

        $autoExpiredFlag = @$conn->query("SHOW COLUMNS FROM jobseeker LIKE 'auto_expired'");
        if ($autoExpiredFlag && $autoExpiredFlag->num_rows === 0) {
            @$conn->query("ALTER TABLE jobseeker ADD COLUMN auto_expired TINYINT(1) NOT NULL DEFAULT 0 AFTER rejection_reason");
        }
        $autoExpiredAt = @$conn->query("SHOW COLUMNS FROM jobseeker LIKE 'auto_expired_at'");
        if ($autoExpiredAt && $autoExpiredAt->num_rows === 0) {
            @$conn->query("ALTER TABLE jobseeker ADD COLUMN auto_expired_at DATETIME NULL AFTER auto_expired");
        }
        $warnSentAt = @$conn->query("SHOW COLUMNS FROM jobseeker LIKE 'inactivity_warned_at'");
        if ($warnSentAt && $warnSentAt->num_rows === 0) {
            @$conn->query("ALTER TABLE jobseeker ADD COLUMN inactivity_warned_at DATETIME NULL AFTER auto_expired_at");
        }
    }
}

if (!function_exists('workconnect_touch_employee_activity')) {
    function workconnect_touch_employee_activity(mysqli $conn, int $userId): void
    {
        if (!$conn || $conn->connect_error || $userId <= 0) {
            return;
        }
        $stmt = @$conn->prepare("UPDATE employee_users SET last_active_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (!function_exists('workconnect_expire_inactive_jobseekers')) {
    function workconnect_expire_inactive_jobseekers(mysqli $conn, int $days = 30): int
    {
        if (!$conn || $conn->connect_error) {
            return 0;
        }
        workconnect_ensure_jobseeker_expiry_schema($conn);

        $days = max(1, (int)$days);
        $reason = "Automatic Rejection: Inactive for {$days} days.";
        $reasonEsc = $conn->real_escape_string($reason);
        $sql = "
            UPDATE jobseeker j
            INNER JOIN employee_users eu ON eu.id = j.user_id
            SET
                j.application_status = 'Rejected',
                j.rejection_reason = '{$reasonEsc}',
                j.auto_expired = 1,
                j.auto_expired_at = NOW(),
                j.updated_at = NOW()
            WHERE
                COALESCE(j.placement_active, 0) = 0
                AND COALESCE(j.auto_expired, 0) = 0
                AND j.application_status IN ('Pending', 'Referred')
                AND COALESCE(eu.last_active_at, eu.updated_at, eu.created_at) < (NOW() - INTERVAL {$days} DAY)
                AND COALESCE(j.updated_at, j.created_at) < (NOW() - INTERVAL {$days} DAY)
        ";
        @$conn->query($sql);
        return (int)($conn->affected_rows ?? 0);
    }
}

if (!function_exists('workconnect_send_inactivity_warning_emails')) {
    function workconnect_send_inactivity_warning_emails(mysqli $conn, int $warnDays = 25, int $expireDays = 30): int
    {
        if (!$conn || $conn->connect_error) {
            return 0;
        }
        workconnect_ensure_jobseeker_expiry_schema($conn);

        $warnDays = max(1, (int)$warnDays);
        $expireDays = max($warnDays + 1, (int)$expireDays);
        $subject = 'WorkConnect NSRP Warning: Inactivity Detected';

        $sql = "
            SELECT
                j.id,
                COALESCE(NULLIF(j.firstname, ''), '') AS firstname,
                COALESCE(NULLIF(j.surname, ''), '') AS surname,
                COALESCE(NULLIF(j.email, ''), NULLIF(eu.email, '')) AS target_email
            FROM jobseeker j
            INNER JOIN employee_users eu ON eu.id = j.user_id
            WHERE
                COALESCE(j.placement_active, 0) = 0
                AND COALESCE(j.auto_expired, 0) = 0
                AND j.application_status IN ('Pending', 'Referred')
                AND j.inactivity_warned_at IS NULL
                AND COALESCE(eu.last_active_at, eu.updated_at, eu.created_at) <= (NOW() - INTERVAL {$warnDays} DAY)
                AND COALESCE(eu.last_active_at, eu.updated_at, eu.created_at) > (NOW() - INTERVAL {$expireDays} DAY)
        ";
        $res = @$conn->query($sql);
        if (!$res) {
            return 0;
        }

        $sentCount = 0;
        while ($row = $res->fetch_assoc()) {
            $jid = (int)($row['id'] ?? 0);
            $email = trim((string)($row['target_email'] ?? ''));
            if ($jid <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $name = trim(($row['firstname'] ?? '') . ' ' . ($row['surname'] ?? ''));
            if ($name === '') {
                $name = 'Jobseeker';
            }

            $message = "
            <!DOCTYPE html>
            <html lang='en'>
            <head><meta charset='UTF-8'><title>NSRP Inactivity Warning</title></head>
            <body style='font-family:Arial,sans-serif;background:#f6f8fb;padding:20px;color:#1f2d3d;'>
                <div style='max-width:620px;margin:0 auto;background:#fff;border:1px solid #e3e8f3;border-radius:10px;overflow:hidden;'>
                    <div style='background:#233a8b;color:#fff;padding:16px 20px;font-size:18px;font-weight:700;'>WorkConnect NSRP Inactivity Warning</div>
                    <div style='padding:20px;line-height:1.6;'>
                        <p>Dear " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</p>
                        <p>Our records show that your account has been inactive for almost {$warnDays} days while your NSRP application is still <strong>Pending/Referred</strong>.</p>
                        <p>If you remain inactive for {$expireDays} days, your NSRP status will be automatically rejected by the system with this reason:</p>
                        <p style='background:#fff3cd;border-left:4px solid #ffb300;padding:10px 12px;'><strong>Automatic Rejection: Inactive for {$expireDays} days.</strong></p>
                        <p>Please log in to your WorkConnect account to keep your application active.</p>
                        <p>Thank you,<br><strong>WorkConnect Team</strong></p>
                    </div>
                </div>
            </body>
            </html>";

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: WorkConnect <noreply@workconnect.com>\r\n";
            $headers .= "Reply-To: noreply@workconnect.com\r\n";

            if (@mail($email, $subject, $message, $headers)) {
                $upd = @$conn->prepare("UPDATE jobseeker SET inactivity_warned_at = NOW() WHERE id = ?");
                if ($upd) {
                    $upd->bind_param("i", $jid);
                    $upd->execute();
                    $upd->close();
                }
                $sentCount++;
            }
        }
        return $sentCount;
    }
}

