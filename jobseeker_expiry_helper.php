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
        $sql = "
            UPDATE jobseeker j
            INNER JOIN employee_users eu ON eu.id = j.user_id
            SET
                j.application_status = 'Rejected',
                j.rejection_reason = 'Application automatically expired after 30 days of account inactivity.',
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

