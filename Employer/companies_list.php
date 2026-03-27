<?php
date_default_timezone_set('Asia/Manila');
include 'session_protect.php';
require_once 'db.php';

$companies = [];
$verified_company_count = 0;
if ($conn) {
    $cols_check = $conn->query("SHOW COLUMNS FROM company_users");
    $has_created_at = false;
    $has_email_verified = false;
    if ($cols_check) {
        while ($col = $cols_check->fetch_assoc()) {
            if ($col['Field'] === 'created_at') { $has_created_at = true; }
            if ($col['Field'] === 'email_verified') { $has_email_verified = true; }
        }
    }
    $select = "id, company_name, email";
    if ($has_created_at) $select .= ", created_at";
    if ($has_email_verified) $select .= ", email_verified";
    $sql = "SELECT $select FROM company_users ORDER BY company_name ASC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($has_email_verified && isset($row['email_verified']) && (int)$row['email_verified'] === 1) {
                $verified_company_count++;
            }
            $companies[] = $row;
        }
    }
    if (!$has_email_verified) {
        $verified_company_count = count($companies);
    }
    require_once __DIR__ . '/follow_up_pending_badge.php';
    require_once __DIR__ . '/admin_company_follow_up_badge.php';
    $follow_up_pending_count = fu_get_pending_follow_up_count($conn);
    $acfu_unread_count = acfu_get_unread_response_count($conn);
    $conn->close();
} else {
    $follow_up_pending_count = 0;
    $acfu_unread_count = 0;
}

function formatDate($d) {
    if (empty($d) || $d === '0000-00-00 00:00:00') return '—';
    return date('M j, Y', strtotime($d));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="/assets/image/PESO Logo circle.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - WorkConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #fafafa; min-height: 100vh; overflow-x: hidden; }
        .header { background: #233a8b; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; height: 64px; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-shadow: 0 2px 8px rgba(35,58,139,0.1); box-sizing: border-box; }
        .header img { height: 48px; margin-right: 16px; border-radius: 50%; }
        .header-title { font-size: 1.7rem; font-weight: bold; letter-spacing: 0.5px; }
        .layout { display: flex; min-height: calc(100vh - 64px); padding-top: 64px; }
        .sidebar { background: #e3eaff; width: 240px; height: calc(100vh - 64px); position: fixed; top: 64px; left: 0; z-index: 999; display: flex; flex-direction: column; padding: 32px 0 0 24px; box-sizing: border-box; overflow-y: auto; }
        .sidebar a { font-weight: bold; color: #222; text-decoration: none; margin-bottom: 16px; font-size: 1rem; letter-spacing: 0.3px; transition: all 0.2s; padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; gap: 12px; margin-top: 10%; }
        .sidebar a:hover { color: #233a8b; background: #d1dbfa; border-radius: 8px; padding-left: 10px; }
        .sidebar .logout { margin-top: auto; margin-bottom: 32px; color: #222; font-weight: bold; display: block; width: 90%; text-align: left; }
        .sidebar a.active { color: #fff; background: #233a8b; box-shadow: 0 2px 8px rgba(35,58,139,0.15); }
        .main-content { flex: 1; padding: 32px; background: #fff; margin-left: 240px; min-height: calc(100vh - 64px); overflow-y: auto; box-sizing: border-box; }
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
            .sidebar { left: -240px !important; width: 240px !important; height: calc(100vh - 56px) !important; transition: left 0.3s ease !important; }
            .sidebar.active { left: 0 !important; }
            .main-content { margin-left: 0; padding: 16px; width: 100%; }
        }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-header h2 { color: #233a8b; margin: 0; font-size: 1.8rem; }
        .page-header p { color: #666; margin: 8px 0 0 0; font-size: 1rem; }
        .search-wrap { display: flex; align-items: center; gap: 12px; }
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
    </style>
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
            <a href="job.php"> JOBSEEKERS</a>
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
                    <p>Registered companies; count badge shows <strong>verified</strong> (email confirmed) accounts.</p>
                </div>
                <div class="search-wrap">
                    <input type="text" id="companySearch" placeholder="Search by name or email..." autocomplete="off">
                    <div class="count-badge">
                        <div class="num" id="companyCount"><?php echo (int) $verified_company_count; ?></div>
                        <div class="label">Verified</div>
                    </div>
                </div>
            </div>

            <?php if (empty($companies)): ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <p style="font-size: 1.1rem; margin: 0;">No companies registered yet.</p>
                </div>
            <?php else: ?>
                <table class="companies-table" id="companiesTable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Company Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Date Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $i => $c): ?>
                        <tr class="company-row" data-name="<?php echo htmlspecialchars(strtolower($c['company_name'] ?? '')); ?>" data-email="<?php echo htmlspecialchars(strtolower($c['email'] ?? '')); ?>" data-verified="<?php echo (isset($c['email_verified']) && (int)$c['email_verified'] === 1) ? '1' : '0'; ?>">
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($c['company_name'] ?? '—'); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($c['email'] ?? ''); ?>" style="color:#1976d2;text-decoration:none;"><?php echo htmlspecialchars($c['email'] ?? '—'); ?></a></td>
                            <td><?php
                                $ev = isset($c['email_verified']) ? (int)$c['email_verified'] : null;
                                if ($ev === 1) {
                                    echo '<span style="background:#e8f5e9;color:#2e7d32;padding:4px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;">Verified</span>';
                                } elseif ($ev === 0) {
                                    echo '<span style="background:#fff3e0;color:#e65100;padding:4px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;">Pending</span>';
                                } else {
                                    echo '<span style="color:#888;">—</span>';
                                }
                            ?></td>
                            <td><?php echo formatDate($c['created_at'] ?? null); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
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

        // Search filter
        var searchInput = document.getElementById('companySearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var term = this.value.toLowerCase().trim();
                var rows = document.querySelectorAll('.company-row');
                var visible = 0;
                rows.forEach(function(row) {
                    var name = row.getAttribute('data-name') || '';
                    var email = row.getAttribute('data-email') || '';
                    var match = !term || name.indexOf(term) >= 0 || email.indexOf(term) >= 0;
                    row.style.display = match ? '' : 'none';
                    if (match && row.getAttribute('data-verified') === '1') visible++;
                });
                var countEl = document.getElementById('companyCount');
                if (countEl) countEl.textContent = visible;
            });
        }

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
