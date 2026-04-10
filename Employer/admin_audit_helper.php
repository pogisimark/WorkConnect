<?php
date_default_timezone_set('Asia/Manila');

if (!function_exists('admin_audit_ensure_schema')) {
    function admin_audit_ensure_schema(mysqli $conn): void
    {
        if (!$conn || $conn->connect_error) {
            return;
        }
        $sql = "CREATE TABLE IF NOT EXISTS admin_audit_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NULL,
            admin_username VARCHAR(100) NOT NULL,
            action VARCHAR(120) NOT NULL,
            entity_type VARCHAR(80) NULL,
            entity_id BIGINT NULL,
            description TEXT NULL,
            meta_json LONGTEXT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_id (admin_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at),
            INDEX idx_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @$conn->query($sql);
    }
}

if (!function_exists('admin_audit_client_ip')) {
    function admin_audit_client_ip(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $val = (string)$_SERVER[$k];
                if ($k === 'HTTP_X_FORWARDED_FOR') {
                    $parts = explode(',', $val);
                    return trim((string)$parts[0]);
                }
                return trim($val);
            }
        }
        return '';
    }
}

if (!function_exists('admin_audit_log')) {
    function admin_audit_log(mysqli $conn, string $action, ?string $entityType = null, $entityId = null, ?string $description = null, array $meta = []): void
    {
        if (!$conn || $conn->connect_error) {
            return;
        }
        admin_audit_ensure_schema($conn);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $username = isset($_SESSION['username']) ? trim((string)$_SESSION['username']) : '';
        if ($username === '') {
            $username = 'UnknownAdmin';
        }

        $adminId = null;
        $s = @$conn->prepare("SELECT id FROM admin_accounts WHERE username = ? LIMIT 1");
        if ($s) {
            $s->bind_param('s', $username);
            $s->execute();
            $r = $s->get_result();
            if ($r && $r->num_rows > 0) {
                $row = $r->fetch_assoc();
                $adminId = (int)$row['id'];
            }
            $s->close();
        } elseif (strcasecmp($username, 'Admin') === 0) {
            $adminId = 1;
        }

        $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        if ($metaJson !== null && strlen($metaJson) > 20000) {
            $metaJson = substr($metaJson, 0, 20000);
        }
        $ip = admin_audit_client_ip();
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $entityIdVal = is_numeric($entityId) ? (int)$entityId : null;
        $entityTypeVal = $entityType !== null ? trim($entityType) : null;
        $desc = $description !== null ? $description : null;

        $ins = @$conn->prepare("INSERT INTO admin_audit_logs
            (admin_id, admin_username, action, entity_type, entity_id, description, meta_json, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$ins) {
            return;
        }
        $ins->bind_param(
            'isssissss',
            $adminId,
            $username,
            $action,
            $entityTypeVal,
            $entityIdVal,
            $desc,
            $metaJson,
            $ip,
            $ua
        );
        $ins->execute();
        $ins->close();
    }
}

