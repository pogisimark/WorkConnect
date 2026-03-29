<?php
/**
 * Placement lifecycle: jobseeker.application_status = Accepted means "successfully placed";
 * placement_active = 1 means they are still in that placement (block new NSRP / new applies).
 * PESO can end placement → Pending + placement_active 0 so they can use the system again.
 */

if (!function_exists('workconnect_ensure_jobseeker_placement_columns')) {
    function workconnect_ensure_jobseeker_placement_columns(?mysqli $conn): void
    {
        static $done = false;
        if ($done || !$conn || $conn->connect_error) {
            return;
        }
        $done = true;

        $t = @$conn->query("SHOW COLUMNS FROM jobseeker LIKE 'placement_active'");
        if ($t && $t->num_rows === 0) {
            @$conn->query("ALTER TABLE jobseeker ADD COLUMN placement_active TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=currently placed (blocks new apply/NRSP)' AFTER application_status");
        }
        $t2 = @$conn->query("SHOW COLUMNS FROM jobseeker LIKE 'placement_ended_at'");
        if ($t2 && $t2->num_rows === 0) {
            @$conn->query("ALTER TABLE jobseeker ADD COLUMN placement_ended_at DATETIME NULL AFTER placement_active");
        }
        $t3 = @$conn->query("SHOW COLUMNS FROM jobseeker LIKE 'placement_end_reason'");
        if ($t3 && $t3->num_rows === 0) {
            @$conn->query("ALTER TABLE jobseeker ADD COLUMN placement_end_reason VARCHAR(255) NULL AFTER placement_ended_at");
        }

        // Existing Accepted rows → treat as actively placed until PESO ends placement
        @$conn->query("UPDATE jobseeker SET placement_active = 1 WHERE application_status = 'Accepted' AND placement_active = 0");
    }
}

if (!function_exists('workconnect_jobseeker_is_actively_placed')) {
    /**
     * True when NSRP row is Accepted and still in active placement (should block new applies / NRSP submit).
     *
     * @param array<string,mixed>|null $row jobseeker row (needs application_status; placement_active optional)
     */
    function workconnect_jobseeker_is_actively_placed(?array $row): bool
    {
        if (!$row || empty($row['application_status'])) {
            return false;
        }
        if (strcasecmp(trim((string) $row['application_status']), 'Accepted') !== 0) {
            return false;
        }
        if (!array_key_exists('placement_active', $row) || $row['placement_active'] === null) {
            // Pre-migration or SELECT omitted column: keep legacy behavior (Accepted = blocked)
            return true;
        }
        return (int) $row['placement_active'] === 1;
    }
}

if (!function_exists('workconnect_sync_jobseeker_placement_flags')) {
    /**
     * After application_status change via PESO/Employer API.
     */
    function workconnect_sync_jobseeker_placement_flags(mysqli $conn, int $jobseeker_id, string $status): void
    {
        workconnect_ensure_jobseeker_placement_columns($conn);
        $jobseeker_id = (int) $jobseeker_id;
        if ($jobseeker_id <= 0) {
            return;
        }
        $s = strtolower(trim($status));
        if ($s === 'accepted') {
            $st = $conn->prepare('UPDATE jobseeker SET placement_active = 1, placement_ended_at = NULL, placement_end_reason = NULL WHERE id = ?');
            if ($st) {
                $st->bind_param('i', $jobseeker_id);
                $st->execute();
                $st->close();
            }
        } elseif (in_array($s, ['pending', 'referred', 'rejected'], true)) {
            $st = $conn->prepare('UPDATE jobseeker SET placement_active = 0 WHERE id = ?');
            if ($st) {
                $st->bind_param('i', $jobseeker_id);
                $st->execute();
                $st->close();
            }
        }
    }
}

if (!function_exists('workconnect_jobseeker_sql_actively_placed_condition')) {
    /**
     * SQL fragment for "currently placed through NSRP Accepted" (for analytics).
     * Assumes columns exist (call ensure first).
     */
    function workconnect_jobseeker_sql_actively_placed_condition(): string
    {
        return "(application_status = 'Accepted' AND COALESCE(placement_active, 1) = 1)";
    }
}

if (!function_exists('workconnect_company_application_card_status')) {
    /**
     * Company / PESO applicant list: show "Closed" when placement ended but jae row was never updated.
     *
     * @param array<string,mixed> $applicant row with application_status, jobseeker_status, optional placement_active
     * @return array{label:string,css:string}
     */
    function workconnect_company_application_card_status(array $applicant): array
    {
        $raw = trim((string) ($applicant['application_status'] ?? 'Applied'));
        $lower = strtolower($raw);
        $js = [
            'application_status' => $applicant['jobseeker_status'] ?? '',
            'placement_active' => array_key_exists('placement_active', $applicant) ? $applicant['placement_active'] : null,
        ];
        if ($lower === 'closed') {
            return ['label' => 'Closed', 'css' => 'closed'];
        }
        if ($lower === 'accepted' && !workconnect_jobseeker_is_actively_placed($js)) {
            return ['label' => 'Closed', 'css' => 'closed'];
        }

        return ['label' => $raw !== '' ? $raw : 'Applied', 'css' => strtolower($raw !== '' ? $raw : 'applied')];
    }
}
