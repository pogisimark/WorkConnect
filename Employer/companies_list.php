<?php
date_default_timezone_set('Asia/Manila');
include 'session_protect.php';
require_once 'db.php';
require_once __DIR__ . '/follow_up_pending_badge.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';
require_once __DIR__ . '/jobseeker_pending_badge.php';
require_once __DIR__ . '/../Company/company_peso_schema.php';

$companies_approved = [];
$companies_pending = [];
$verified_company_count = 0;
$pending_company_count = 0;

if ($conn) {
    ensureCompanyPesoSchema($conn);
    $cols_check = $conn->query('SHOW COLUMNS FROM company_users');
    $has_created_at = false;
    $has_email_verified = false;
    $has_peso_verified = false;
    $has_contact = false;
    $has_telephone = false;
    $has_phone = false;
    if ($cols_check) {
        while ($col = $cols_check->fetch_assoc()) {
            $f = $col['Field'];
            if ($f === 'created_at') {
                $has_created_at = true;
            }
            if ($f === 'email_verified') {
                $has_email_verified = true;
            }
            if ($f === 'peso_verified') {
                $has_peso_verified = true;
            }
            if ($f === 'contact_number') {
                $has_contact = true;
            }
            if ($f === 'telephone_number') {
                $has_telephone = true;
            }
        }
    }
    $select = 'id, company_name, email';
    if ($has_created_at) {
        $select .= ', created_at';
    }
    if ($has_email_verified) {
        $select .= ', email_verified';
    }
    if ($has_peso_verified) {
        $select .= ', peso_verified';
    }
    if ($has_contact) {
        $select .= ', contact_number';
    }
    if ($has_telephone) {
        $select .= ', telephone_number';
    }
    if ($has_phone) {
        $select .= ', phone';
    }
    $order = $has_peso_verified
        ? 'ORDER BY COALESCE(peso_verified, 0) DESC, company_name ASC'
        : 'ORDER BY company_name ASC';
    $sql = "SELECT $select FROM company_users $order";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (company_row_peso_approved($row, $has_peso_verified)) {
                $companies_approved[] = $row;
            } else {
                $companies_pending[] = $row;
            }
        }
    }
    $verified_company_count = count($companies_approved);
    $pending_company_count = count($companies_pending);
    $follow_up_pending_count = fu_get_pending_follow_up_count($conn);
    $acfu_unread_count = acfu_get_unread_response_count($conn);
    $pending_jobseekers_count = js_get_pending_jobseekers_count($conn);
    $conn->close();
} else {
    $follow_up_pending_count = 0;
    $acfu_unread_count = 0;
    $pending_jobseekers_count = 0;
}

function formatDate($d) {
    if (empty($d) || $d === '0000-00-00 00:00:00') {
        return '—';
    }
    return date('M j, Y', strtotime($d));
}

/** @param array<string,mixed> $row */
function company_row_peso_approved(array $row, bool $has_peso_column): bool {
    if ($has_peso_column) {
        return isset($row['peso_verified']) && (int) $row['peso_verified'] === 1;
    }
    if (array_key_exists('email_verified', $row)) {
        return (int) $row['email_verified'] === 1;
    }
    return true;
}

function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** PESO contact_number or legacy profile phone (for display / search). */
function company_list_contact_display(array $c): string {
    $cn = trim((string) ($c['contact_number'] ?? ''));
    if ($cn !== '') {
        return $cn;
    }
    return trim((string) ($c['phone'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - WorkConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #fafafa; min-height: 100vh; min-height: 100dvh; overflow-x: hidden; }
        .header { background: #233a8b; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; height: 64px; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-shadow: 0 2px 8px rgba(35,58,139,0.1); box-sizing: border-box; }
        .header img { height: 48px; margin-right: 16px; border-radius: 50%; }
        .header-title { font-size: 1.7rem; font-weight: bold; letter-spacing: 0.5px; }
        .layout { display: flex; min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px)); padding-top: 64px; }
        .sidebar { background: #e3eaff; width: 240px; height: calc(100vh - 64px); height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px)); max-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px)); position: fixed; top: 64px; left: 0; z-index: 999; display: flex; flex-direction: column; padding: 32px 0 0 24px; box-sizing: border-box; overflow-y: auto; }
        .sidebar a { font-weight: bold; color: #222; text-decoration: none; margin-bottom: 16px; font-size: 1rem; letter-spacing: 0.3px; transition: all 0.2s; padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; gap: 12px; margin-top: 10%; }
        .sidebar a:hover { color: #233a8b; background: #d1dbfa; border-radius: 8px; padding-left: 10px; }
        .sidebar .logout { margin-top: auto; margin-bottom: 32px; color: #222; font-weight: bold; display: block; width: 90%; text-align: left; }
        .sidebar a.active { color: #fff; background: #233a8b; box-shadow: 0 2px 8px rgba(35,58,139,0.15); }
        .main-content { flex: 1; padding: 32px; background: #fff; margin-left: 240px; min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px)); overflow-y: auto; box-sizing: border-box; }
        .hamburger-menu { display: none; }
        @media (max-width: 768px) {
            .header { padding: 12px 16px; min-height: 56px; }
            .header img { height: 32px; margin-right: 8px; }
            .header-title { font-size: 1.2rem; }
            .hamburger-menu { display: block !important; background: none; border: none; cursor: pointer; padding: 8px; margin-right: 12px; z-index: 1001; }
            .hamburger-menu span { display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px; }
            .hamburger-menu.active span:nth-child(1) { transform: rotate(-45deg) translate(-5px, 6px); }
            .hamburger-menu.active span:nth-child(2) { opacity: 0; }
            .hamburger-menu.active span:nth-child(3) { transform: rotate(45deg) translate(-5px, -6px); }
            .layout { flex-direction: column; padding-top: 60px; }
            .sidebar { left: -240px !important; width: 240px !important; height: calc(100vh - 56px) !important; height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important; max-height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important; transition: left 0.3s ease !important; }
            .sidebar.active { left: 0 !important; }
            .main-content { margin-left: 0; padding: 16px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; overflow-x: clip; }
            .page-header { flex-direction: column; align-items: stretch; gap: 10px; margin-bottom: 16px; }
            .page-header h2 { font-size: 1.35rem; }
            .page-header p { font-size: 0.82rem; margin-top: 4px !important; line-height: 1.35; }
            .search-wrap { flex-direction: column; align-items: stretch; width: 100%; gap: 8px; }
            .search-wrap input { min-width: 0 !important; width: 100%; box-sizing: border-box; padding: 8px 12px; font-size: 0.9rem; }
            .count-badge {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: 10px;
                padding: 8px 14px;
                border-radius: 10px;
                align-self: center;
                max-width: 100%;
            }
            .count-badge .num { font-size: 1.25rem; line-height: 1; }
            .count-badge .label { font-size: 0.72rem; }
            .companies-table-wrap { width: 100%; min-width: 0; }
            .companies-table-wrap.companies-table-scroll {
                max-height: min(70vh, 40rem);
            }
            .companies-table-wrap.companies-table-scroll .companies-table thead th {
                position: static;
                box-shadow: none;
            }
            .companies-table { display: block; width: 100%; box-shadow: none; background: transparent; border-radius: 0; }
            .companies-table thead { display: none; }
            .companies-table tbody { display: block; width: 100%; }
            .companies-table tr.company-row {
                display: block;
                width: 100%;
                box-sizing: border-box;
                margin-bottom: 10px;
                padding: 0;
                background: #fff;
                border-radius: 10px;
                border: 1px solid #e8eaf0;
                box-shadow: 0 1px 6px rgba(35,58,139,0.06);
                overflow: hidden;
            }
            .companies-table tr.company-row:hover { background: #fff; }
            .companies-table td {
                display: grid;
                grid-template-columns: minmax(76px, 32%) 1fr;
                gap: 8px 10px;
                align-items: start;
                padding: 8px 12px;
                font-size: 0.82rem;
                border-bottom: 1px solid #f0f2f5;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            .companies-table tr.company-row td:last-child { border-bottom: none; }
            .companies-table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #233a8b;
                font-size: 0.68rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                line-height: 1.3;
                padding-top: 2px;
            }
            .companies-table td a { word-break: break-all; }
        }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-header h2 { color: #233a8b; margin: 0; font-size: 1.8rem; }
        .page-header p { color: #666; margin: 8px 0 0 0; font-size: 1rem; }
        .search-wrap { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .search-wrap input { padding: 10px 16px; border: 2px solid #e3f2fd; border-radius: 8px; font-size: 1rem; min-width: 220px; }
        .search-wrap input:focus { outline: none; border-color: #1976d2; }
        .companies-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .companies-table th { background: #e3f2fd; color: #233a8b; font-weight: 700; padding: 14px 16px; text-align: left; font-size: 0.9rem; }
        .companies-table td { padding: 14px 16px; border-bottom: 1px solid #eee; font-size: 0.95rem; }
        .companies-table tr:hover { background: #f8f9fa; }
        .companies-table tr:last-child td { border-bottom: none; }
        .count-badge { background: linear-gradient(135deg, #e3f2fd, #f0f4ff); padding: 12px 20px; border-radius: 12px; border-left: 4px solid #1976d2; }
        .count-badge .num { font-size: 1.5rem; font-weight: 700; color: #1976d2; }
        .count-badge .label { font-size: 0.9rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .empty-state { text-align: center; padding: 48px 24px; color: #666; background: #f8f9fa; border-radius: 12px; }
        .empty-state i { font-size: 3rem; color: #90caf9; margin-bottom: 16px; }
        .companies-section-title { color: #233a8b; font-size: 1.15rem; font-weight: 700; margin: 28px 0 12px 0; padding-bottom: 8px; border-bottom: 2px solid #e3f2fd; }
        .companies-section-title:first-of-type { margin-top: 0; }
        .count-badge-pending { border-left-color: #e65100; background: linear-gradient(135deg, #fff3e0, #fff8f0); }
        .count-badge-pending .num { color: #e65100; }
        .btn-verify { background: #233a8b; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-weight: 600; cursor: pointer; font-size: 0.85rem; }
        .btn-verify:hover { filter: brightness(1.08); }
        .btn-verify:disabled { opacity: 0.6; cursor: not-allowed; }
        /* ~10 data rows + header, then scroll (verified & pending lists) */
        .companies-table-wrap.companies-table-scroll {
            max-height: 34rem;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            background: #fff;
        }
        .companies-table-wrap.companies-table-scroll .companies-table {
            box-shadow: none;
            margin: 0;
        }
        .companies-table-wrap.companies-table-scroll .companies-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            box-shadow: 0 1px 0 rgba(35,58,139,0.12);
        }
    </style>
    <link rel="stylesheet" href="../assets/css/Employer-sidebar-neat.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/employer-page-loading.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
<div class="header" id="mainHeader">
        <div style="display: flex; align-items: center;">
            <button class="hamburger-menu" id="hamburgerMenu">
                <span></span><span></span><span></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">👤</div>
            <span style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php"> DASHBOARD</a>
            <a href="job_postings.php"> JOB POSTINGS</a>
            <a href="job.php"> JOBSEEKERS<?php echo js_pending_jobseekers_badge_html($pending_jobseekers_count); ?></a>
            <a href="follow_up_requests.php"> FOLLOW-UP REQUESTS<?php echo fu_follow_up_badge_html($follow_up_pending_count); ?></a>
            <a href="request_follow_up.php"> REQUEST FOLLOW UP<span class="acfu-sidebar-badge"><?php echo acfu_unread_badge_html($acfu_unread_count); ?></span></a>
            <a href="skill.php"> SKILL REGISTRY</a>
            <a href="companies_list.php" class="active"> COMPANIES</a>
            <a href="btec.php"> BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
            <a href="analytics.php"> Analytics</a>
            <a href="announcement.php"> ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout"> Logout</a>
        </div>
        <div class="main-content">
            <div class="page-header">
                <div>
                    <h2>Companies</h2>
                    <p>PESO-verified companies are listed first. <strong>Pending</strong> registrations appear below until you verify them; companies cannot log in until verified.</p>
                </div>
                <div class="search-wrap">
                    <input type="text" id="companySearch" placeholder="Search name, email, or phone..." autocomplete="off">
                    <div class="count-badge">
                        <div class="num" id="companyCount"><?php echo (int) $verified_company_count; ?></div>
                        <div class="label">PESO verified</div>
                    </div>
                    <div class="count-badge count-badge-pending">
                        <div class="num" id="pendingCountDisplay"><?php echo (int) $pending_company_count; ?></div>
                        <div class="label">Pending</div>
                    </div>
                </div>
            </div>

            <?php if (empty($companies_approved) && empty($companies_pending)): ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <p style="font-size: 1.1rem; margin: 0;">No companies registered yet.</p>
                </div>
            <?php else: ?>
                <?php if (!empty($companies_approved)): ?>
                <h3 class="companies-section-title">PESO verified</h3>
                <div class="companies-table-wrap companies-table-scroll">
                <table class="companies-table" id="companiesTableApproved">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Company Name</th>
                            <th>Email</th>
                            <th>Contact number</th>
                            <th>Telephone number</th>
                            <th>Status</th>
                            <th>Date Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies_approved as $i => $c):
                            $dn = strtolower((string) ($c['company_name'] ?? ''));
                            $de = strtolower((string) ($c['email'] ?? ''));
                            $contactShown = company_list_contact_display($c);
                            $dc = strtolower($contactShown);
                            $dt = strtolower((string) ($c['telephone_number'] ?? ''));
                            ?>
                        <tr class="company-row company-row-approved" data-name="<?php echo h($dn); ?>" data-email="<?php echo h($de); ?>" data-contact="<?php echo h($dc); ?>" data-tel="<?php echo h($dt); ?>">
                            <td data-label="No."><?php echo $i + 1; ?></td>
                            <td data-label="Company"><?php echo h($c['company_name'] ?? '—'); ?></td>
                            <td data-label="Email"><a href="mailto:<?php echo h($c['email'] ?? ''); ?>" style="color:#1976d2;text-decoration:none;"><?php echo h($c['email'] ?? '—'); ?></a></td>
                            <td data-label="Contact number"><?php echo h($contactShown !== '' ? $contactShown : '—'); ?></td>
                            <td data-label="Telephone number"><?php echo h(trim((string) ($c['telephone_number'] ?? '')) !== '' ? $c['telephone_number'] : '—'); ?></td>
                            <td data-label="Status"><span style="background:#e8f5e9;color:#2e7d32;padding:4px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;">Verified</span></td>
                            <td data-label="Registered"><?php echo formatDate($c['created_at'] ?? null); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>

                <?php if (!empty($companies_pending)): ?>
                <h3 class="companies-section-title">Pending PESO verification</h3>
                <div class="companies-table-wrap companies-table-scroll">
                <table class="companies-table" id="companiesTablePending">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Company Name</th>
                            <th>Email</th>
                            <th>Contact number</th>
                            <th>Telephone number</th>
                            <th>Status</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies_pending as $i => $c):
                            $dn = strtolower((string) ($c['company_name'] ?? ''));
                            $de = strtolower((string) ($c['email'] ?? ''));
                            $contactShown = company_list_contact_display($c);
                            $dc = strtolower($contactShown);
                            $dt = strtolower((string) ($c['telephone_number'] ?? ''));
                            $cid = (int) ($c['id'] ?? 0);
                            ?>
                        <tr class="company-row company-row-pending" data-name="<?php echo h($dn); ?>" data-email="<?php echo h($de); ?>" data-contact="<?php echo h($dc); ?>" data-tel="<?php echo h($dt); ?>" data-company-id="<?php echo $cid; ?>">
                            <td data-label="No."><?php echo $i + 1; ?></td>
                            <td data-label="Company"><?php echo h($c['company_name'] ?? '—'); ?></td>
                            <td data-label="Email"><a href="mailto:<?php echo h($c['email'] ?? ''); ?>" style="color:#1976d2;text-decoration:none;"><?php echo h($c['email'] ?? '—'); ?></a></td>
                            <td data-label="Contact number"><?php echo h($contactShown !== '' ? $contactShown : '—'); ?></td>
                            <td data-label="Telephone number"><?php echo h(trim((string) ($c['telephone_number'] ?? '')) !== '' ? $c['telephone_number'] : '—'); ?></td>
                            <td data-label="Status"><span style="background:#fff3e0;color:#e65100;padding:4px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;">Pending</span></td>
                            <td data-label="Registered"><?php echo formatDate($c['created_at'] ?? null); ?></td>
                            <td data-label="Actions"><button type="button" class="btn-verify" data-company-id="<?php echo $cid; ?>" data-company-name="<?php echo h($c['company_name'] ?? ''); ?>">Verify</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;inset:0;width:100%;height:100%;min-height:100vh;min-height:100dvh;max-height:100dvh;box-sizing:border-box;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
        <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(25,118,210,0.18);padding:32px 28px 24px 28px;max-width:400px;width:100%;margin:0 auto;text-align:center;">
            <h3 style="margin-top:0;color:#233a8b;font-size:1.3rem;font-weight:bold;margin-bottom:12px;">Confirm Logout</h3>
            <p style="color:#666;margin-bottom:24px;font-size:1rem;">Are you sure you want to logout?</p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button id="confirmLogoutBtn" style="background:#f44336;color:#fff;border:none;border-radius:8px;padding:12px 24px;font-weight:600;cursor:pointer;">Yes, Logout</button>
                <button id="cancelLogoutBtn" style="background:#bdbdbd;color:#1a3876;border:none;border-radius:8px;padding:12px 24px;font-weight:600;cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.logout').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('logoutModal').style.display = 'flex';
            });
        });
        document.getElementById('cancelLogoutBtn').onclick = function() {
            document.getElementById('logoutModal').style.display = 'none';
        };
        document.getElementById('confirmLogoutBtn').onclick = function() {
            window.location.href = 'logout.php';
        };

        function applyCompanySearch() {
            var searchInput = document.getElementById('companySearch');
            var term = searchInput ? searchInput.value.toLowerCase().trim() : '';
            var rows = document.querySelectorAll('.company-row');
            var visApproved = 0;
            var visPending = 0;
            rows.forEach(function(row) {
                var name = row.getAttribute('data-name') || '';
                var email = row.getAttribute('data-email') || '';
                var contact = row.getAttribute('data-contact') || '';
                var tel = row.getAttribute('data-tel') || '';
                var match = !term || name.indexOf(term) >= 0 || email.indexOf(term) >= 0 || contact.indexOf(term) >= 0 || tel.indexOf(term) >= 0;
                row.style.display = match ? '' : 'none';
                if (match) {
                    if (row.classList.contains('company-row-approved')) visApproved++;
                    if (row.classList.contains('company-row-pending')) visPending++;
                }
            });
            var countEl = document.getElementById('companyCount');
            if (countEl) countEl.textContent = visApproved;
            var pendEl = document.getElementById('pendingCountDisplay');
            if (pendEl) pendEl.textContent = visPending;
        }

        var searchInput = document.getElementById('companySearch');
        if (searchInput) {
            searchInput.addEventListener('input', applyCompanySearch);
        }

        document.querySelectorAll('.btn-verify').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = parseInt(btn.getAttribute('data-company-id'), 10);
                var cname = btn.getAttribute('data-company-name') || 'this company';
                if (!id) return;
                Swal.fire({
                    title: 'Verify company?',
                    text: 'Approve "' + cname + '" so they can log in. A confirmation email will be sent to their address.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Verify',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#233a8b'
                }).then(function(res) {
                    if (!res.isConfirmed) return;
                    btn.disabled = true;
                    Swal.fire({ title: 'Verifying…', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                    fetch('verify_company_account.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ company_id: id }),
                        credentials: 'same-origin'
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: 'Done', text: data.message || 'Verified.', icon: 'success', confirmButtonColor: '#233a8b' }).then(function() { location.reload(); });
                        } else {
                            btn.disabled = false;
                            Swal.fire({ title: 'Error', text: data.message || 'Could not verify.', icon: 'error', confirmButtonColor: '#233a8b' });
                        }
                    })
                    .catch(function() {
                        btn.disabled = false;
                        Swal.fire({ title: 'Error', text: 'Network error.', icon: 'error', confirmButtonColor: '#233a8b' });
                    });
                });
            });
        });

        // Hamburger menu (mobile)
        var hamburger = document.getElementById('hamburgerMenu');
        var sidebar = document.querySelector('.sidebar');
        if (hamburger && sidebar) {
            hamburger.onclick = function() {
                hamburger.classList.toggle('active');
                sidebar.classList.toggle('active');
            };
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                    sidebar.classList.remove('active');
                    hamburger.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>
