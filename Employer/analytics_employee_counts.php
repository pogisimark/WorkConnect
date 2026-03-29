<?php
/**
 * Employee account vs NSRP submission counts for Analytics.
 * - total_employee_accounts: rows in employee_users (registered logins)
 * - nsrp_submitted_users: distinct employee users with at least one jobseeker (NSRP) row
 * - accounts_pending_nsrp: registered employees with no jobseeker row yet
 * - email_verified_users: employee_users with email_verified = 1 (or all accounts if column missing)
 * - email_unverified_users: signed up, email not verified yet
 * - verified_email_with_nsrp_users: email verified AND at least one NSRP (jobseeker) row
 * - employee_accounts: list of all employee_users (id, name, email, verification, nsrp_count)
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db.php';

try {
    $totalEmployeeAccounts = 0;
    $nsrpSubmittedUsers = 0;
    $accountsPendingNsrp = 0;
    $emailVerifiedUsers = 0;
    $emailUnverifiedUsers = 0;
    $verifiedEmailWithNsrpUsers = 0;

    $hasEu = $conn->query("SHOW TABLES LIKE 'employee_users'");
    $hasJs = $conn->query("SHOW TABLES LIKE 'jobseeker'");
    $hasEmailVerifiedCol = false;
    if ($hasEu && $hasEu->num_rows > 0) {
        $evCol = $conn->query("SHOW COLUMNS FROM employee_users LIKE 'email_verified'");
        $hasEmailVerifiedCol = $evCol && $evCol->num_rows > 0;
    }

    if ($hasEu && $hasEu->num_rows > 0) {
        $r = $conn->query('SELECT COUNT(*) AS c FROM employee_users');
        if ($r) {
            $row = $r->fetch_assoc();
            $totalEmployeeAccounts = (int) ($row['c'] ?? 0);
        }
        if ($hasEmailVerifiedCol) {
            $rEv = $conn->query(
                'SELECT SUM(CASE WHEN COALESCE(email_verified, 0) = 1 THEN 1 ELSE 0 END) AS v,' .
                ' SUM(CASE WHEN COALESCE(email_verified, 0) = 0 THEN 1 ELSE 0 END) AS u FROM employee_users'
            );
            if ($rEv) {
                $evRow = $rEv->fetch_assoc();
                $emailVerifiedUsers = (int) ($evRow['v'] ?? 0);
                $emailUnverifiedUsers = (int) ($evRow['u'] ?? 0);
            }
        } else {
            $emailVerifiedUsers = $totalEmployeeAccounts;
            $emailUnverifiedUsers = 0;
        }
    }

    if ($hasEu && $hasEu->num_rows > 0 && $hasJs && $hasJs->num_rows > 0) {
        $qNsrp = "
            SELECT COUNT(DISTINCT eu.id) AS c
            FROM employee_users eu
            INNER JOIN jobseeker j ON j.user_id = eu.id
        ";
        $r2 = $conn->query($qNsrp);
        if ($r2) {
            $row2 = $r2->fetch_assoc();
            $nsrpSubmittedUsers = (int) ($row2['c'] ?? 0);
        }

        $qPending = "
            SELECT COUNT(*) AS c
            FROM employee_users eu
            WHERE NOT EXISTS (
                SELECT 1 FROM jobseeker j WHERE j.user_id = eu.id LIMIT 1
            )
        ";
        $r3 = $conn->query($qPending);
        if ($r3) {
            $row3 = $r3->fetch_assoc();
            $accountsPendingNsrp = (int) ($row3['c'] ?? 0);
        }

        if ($hasEmailVerifiedCol) {
            $qVerifiedNsrp = "
                SELECT COUNT(DISTINCT eu.id) AS c
                FROM employee_users eu
                INNER JOIN jobseeker j ON j.user_id = eu.id
                WHERE COALESCE(eu.email_verified, 0) = 1
            ";
            $rVn = $conn->query($qVerifiedNsrp);
            if ($rVn) {
                $rowVn = $rVn->fetch_assoc();
                $verifiedEmailWithNsrpUsers = (int) ($rowVn['c'] ?? 0);
            }
        } else {
            $verifiedEmailWithNsrpUsers = $nsrpSubmittedUsers;
        }
    } elseif ($hasJs && $hasJs->num_rows > 0) {
        $r4 = $conn->query('SELECT COUNT(DISTINCT user_id) AS c FROM jobseeker WHERE user_id IS NOT NULL AND user_id > 0');
        if ($r4) {
            $row4 = $r4->fetch_assoc();
            $nsrpSubmittedUsers = (int) ($row4['c'] ?? 0);
        }
    }

    if ($hasEu && $hasEu->num_rows > 0) {
        $evExpr = $hasEmailVerifiedCol ? 'COALESCE(eu.email_verified, 0)' : '1';
        if ($hasJs && $hasJs->num_rows > 0) {
            $qList = "
                SELECT eu.id, eu.firstname, eu.lastname, eu.email,
                    ($evExpr) AS email_verified,
                    IFNULL(jc.cnt, 0) AS nsrp_count
                FROM employee_users eu
                LEFT JOIN (
                    SELECT user_id, COUNT(*) AS cnt
                    FROM jobseeker
                    WHERE user_id IS NOT NULL AND user_id > 0
                    GROUP BY user_id
                ) jc ON jc.user_id = eu.id
                ORDER BY eu.id ASC
            ";
        } else {
            $qList = "
                SELECT eu.id, eu.firstname, eu.lastname, eu.email,
                    ($evExpr) AS email_verified,
                    0 AS nsrp_count
                FROM employee_users eu
                ORDER BY eu.id ASC
            ";
        }
        $resList = $conn->query($qList);
        if ($resList) {
            while ($ar = $resList->fetch_assoc()) {
                $ev = (int) ($ar['email_verified'] ?? 0);
                $nsrp = (int) ($ar['nsrp_count'] ?? 0);
                $employeeAccounts[] = [
                    'id' => (int) ($ar['id'] ?? 0),
                    'firstname' => (string) ($ar['firstname'] ?? ''),
                    'lastname' => (string) ($ar['lastname'] ?? ''),
                    'email' => (string) ($ar['email'] ?? ''),
                    'email_verified' => $ev,
                    'email_verified_tracked' => $hasEmailVerifiedCol,
                    'nsrp_count' => $nsrp,
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'total_employee_accounts' => $totalEmployeeAccounts,
        'nsrp_submitted_users' => $nsrpSubmittedUsers,
        'accounts_pending_nsrp' => $accountsPendingNsrp,
        'email_verified_users' => $emailVerifiedUsers,
        'email_unverified_users' => $emailUnverifiedUsers,
        'verified_email_with_nsrp_users' => $verifiedEmailWithNsrpUsers,
        'has_email_verified_column' => $hasEmailVerifiedCol,
        'employee_accounts' => $employeeAccounts,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

$conn->close();
