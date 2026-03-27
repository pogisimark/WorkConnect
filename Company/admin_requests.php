<?php
date_default_timezone_set('Asia/Manila');
require_once 'session_check.php';
require_once 'db.php';

$company_id = (int) $_SESSION['company_id'];
$company_name = $_SESSION['company_name'];

$company_logo = null;
$col_check = $conn->query("SHOW COLUMNS FROM company_users LIKE 'logo'");
if ($col_check && $col_check->num_rows > 0) {
    $st = $conn->prepare("SELECT logo FROM company_users WHERE id = ?");
    $st->bind_param("i", $company_id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row && !empty($row['logo'])) $company_logo = $row['logo'];
    $st->close();
}

$requests = [];
$check = $conn->query("SHOW TABLES LIKE 'admin_company_follow_up'");
if ($check && $check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id, message, status, company_response, responded_at, created_at FROM admin_company_follow_up WHERE company_id = ? AND (COALESCE(hidden_by_company, 0) = 0) ORDER BY FIELD(status, 'pending', 'answered'), created_at DESC");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $requests[] = $row;
    $stmt->close();
}
require_once __DIR__ . '/admin_requests_badge_helper.php';
$pending_admin_requests_count = 0;
foreach ($requests as $row) {
    if (($row['status'] ?? '') === 'pending') {
        $pending_admin_requests_count++;
    }
}
require_once __DIR__ . '/referred_pending_badge_helper.php';
$referred_pending_sidebar_count = company_referred_pending_count_for_sidebar($conn, $company_id);
require_once __DIR__ . '/view_applicants_badge_helper.php';
$pending_applicants_sidebar_count = company_pending_applicants_count_for_sidebar($conn, $company_id);
$conn->close();

function formatDate($d) {
    if (empty($d)) return '—';
    return date('M j, Y g:i A', strtotime($d));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Requests - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/Company-sidebar.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/company-logout.js?v=1"></script>
    <style>
        body { margin: 0; padding: 0; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-profile { display: flex; align-items: center; gap: 10px; position: relative; }
        .profile-icon { font-size: 24px; cursor: pointer; padding: 8px; border-radius: 50%; transition: background-color 0.3s; background-color: rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; overflow: hidden; }
        .profile-icon:hover { background-color: rgba(255, 255, 255, 0.2); }
        .profile-icon i { color: white; }
        .profile-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .welcome-text { color: white; font-size: 1rem; font-weight: 500; }
        .profile-dropdown { position: fixed; top: 80px; right: 20px; width: 200px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); z-index: 1001; overflow: hidden; }
        .profile-dropdown-item { padding: 15px 20px; cursor: pointer; transition: background-color 0.2s; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 10px; }
        .profile-dropdown-item:hover { background-color: #f8f9fa; }
        .profile-dropdown-item.logout { color: #f44336; }
        .profile-dropdown-item.logout:hover { background-color: #ffebee; }
        .dashboard-header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; height: auto; }
        .sidebar { background: #f8f9fa; width: 250px; height: calc(100vh - 80px); position: fixed; left: 0; top: 80px; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.1); overflow-y: auto; overflow-x: hidden; display: flex; flex-direction: column; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; height: 100%; }
        .sidebar-nav li { margin: 0; }
        .sidebar-nav a { display: flex; align-items: center; flex-wrap: nowrap; gap: 10px; padding: 15px 25px; color: #333; text-decoration: none; font-weight: 500; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar-nav a i { font-size: 18px; width: 20px; text-align: center; }
        .sidebar-nav a:hover { background: #e9ecef; border-left-color: #1a3876; }
        .sidebar-nav a.active { background: #1a3876; color: white; border-left-color: #ffcb05; }
        .sidebar-nav a.logout { color: #f44336; margin-top: auto; }
        .sidebar-nav a.logout:hover { background: #ffebee; border-left-color: #f44336; }
        .sidebar-nav a.logout i { color: #f44336; }
        .sidebar-nav li:last-child { margin-top: auto; margin-bottom: 20px; }
        .dashboard-container { padding-top: 80px; }
        .main-content { margin-left: 250px; padding: 20px; min-height: calc(100vh - 80px); }
        .card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-left: 4px solid #ddd; }
        .card.pending { border-left-color: #ff9800; }
        .card.answered { border-left-color: #4caf50; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-pending { background: #fff3e0; color: #e65100; }
        .badge-answered { background: #e8f5e9; color: #2e7d32; }
        .card-actions-ar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 12px; }
        .card-actions-ar .left { display: flex; align-items: center; gap: 8px; }
        .btn { padding: 8px 16px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
        .btn-primary { background: #1976d2; color: #fff; }
        .btn-primary:hover:not(:disabled) { background: #1565c0; }
        #respondSubmitBtn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-width: 150px; }
        #respondSubmitBtn:disabled { opacity: 0.92; cursor: wait; }
        .ar-respond-spin { display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff; border-radius: 50%; animation: arRespondSpin 0.65s linear infinite; flex-shrink: 0; }
        #respondSubmitBtn.is-loading .ar-respond-spin { display: inline-block; }
        @keyframes arRespondSpin { to { transform: rotate(360deg); } }
        .btn-delete { background: #f44336; color: #fff; padding: 6px 12px; border-radius: 6px; border: none; font-size: 0.85rem; cursor: pointer; }
        .btn-delete:hover { background: #d32f2f; }
        .modal { display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1100; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: #fff; border-radius: 12px; padding: 24px; max-width: 480px; width: 90%; }
        .modal-content textarea { width: 100%; min-height: 120px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .bulk-actions { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .status-filter-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
        #statusFilterAr { padding: 8px 14px; border: 2px solid #e3f2fd; border-radius: 8px; font-size: 0.9rem; cursor: pointer; min-width: 140px; }
        .admin-req-pending-pill { display: inline-flex; align-items: center; margin-left: 12px; font-size: 0.85rem; font-weight: 700; background: #fff3e0; color: #e65100; padding: 6px 14px; border-radius: 999px; vertical-align: middle; border: 1px solid #ffcc80; }
        /* Keep pending-count badge visible on active Admin Requests link (same row as text) */
        .sidebar-nav a.active .company-admin-req-badge {
            display: inline-flex !important;
            align-self: center !important;
            visibility: visible !important;
            opacity: 1 !important;
            background: #f44336 !important;
            color: #fff !important;
            margin-left: 8px !important;
            margin-top: 0 !important;
        }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>
<div class="dashboard-header">
        <div class="logo-brand">
            <button class="hamburger-menu" id="hamburgerMenu" aria-label="Menu" type="button">
                <span></span><span></span><span></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="brand">WorkConnect</span>
        </div>
        <div class="user-info">
            <div class="user-profile">
                <div class="profile-icon" onclick="toggleProfileMenu()">
                    <?php if ($company_logo): ?>
                        <img src="../<?php echo htmlspecialchars($company_logo); ?>" alt="Company Logo">
                    <?php else: ?>
                        <i class="fas fa-building"></i>
                    <?php endif; ?>
                </div>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($company_name); ?></span>
            </div>
        </div>
    </div>

    <div id="profileDropdown" class="profile-dropdown" style="display:none;">
        <div class="profile-dropdown-item logout" onclick="showLogoutModal()">
            <i class="fas fa-sign-out-alt"></i> Logout
        </div>
    </div>

    <div class="dashboard-container">
        <div class="sidebar desktop-nav">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="jobposting.php"><i class="fas fa-briefcase"></i> Job Posting</a></li>
                <li><a href="view_applicants.php"><i class="fas fa-users"></i> View Applicants<?php echo company_pending_applicants_badge_html($pending_applicants_sidebar_count); ?></a></li>
                <li><a href="referred.php"><i class="fas fa-user-check"></i> Referred<?php echo company_referred_pending_badge_html($referred_pending_sidebar_count); ?></a></li>
                <li><a href="admin_requests.php" class="active"><i class="fas fa-envelope"></i> Admin Requests<?php echo company_admin_requests_badge_html($pending_admin_requests_count); ?></a></li>
                <li><a href="profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
                <li><a href="#" class="logout" onclick="showLogoutModal(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
        <h1 style="color: #1a3876; margin: 0 0 8px 0; display: flex; align-items: center; flex-wrap: wrap; gap: 8px;"><i class="fas fa-envelope"></i> Requests from Admin
            <?php if ($pending_admin_requests_count > 0): ?>
                <span class="admin-req-pending-pill" title="You have not responded to these yet"><?php echo (int) $pending_admin_requests_count; ?> not responded</span>
            <?php endif; ?>
        </h1>
        <p style="color: #666; margin: 0 0 24px 0;">Follow-up requests sent by the admin. You can respond below.</p>

        <?php if (empty($requests)): ?>
            <div class="card">
                <p style="color: #666; margin: 0;">No requests from admin yet.</p>
            </div>
        <?php else: ?>
            <div class="status-filter-wrap">
                <label for="statusFilterAr">Filter:</label>
                <select id="statusFilterAr" onchange="filterByStatus(this.value)">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="answered">Answered</option>
                </select>
            </div>
            <div class="bulk-actions">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" id="selectAllAr"> Select all</label>
                <button type="button" class="btn-delete" id="deleteSelectedAr" disabled>Remove selected</button>
            </div>
            <?php foreach ($requests as $r): ?>
                <div class="card ar-card <?php echo $r['status'] === 'pending' ? 'pending' : 'answered'; ?>" data-id="<?php echo (int)$r['id']; ?>" data-status="<?php echo $r['status']; ?>">
                    <p style="font-size: 0.85rem; color: #666; margin: 0 0 10px 0;">Requested: <?php echo formatDate($r['created_at']); ?> <span style="color:#888;">(PH time)</span> <span class="badge badge-<?php echo $r['status']; ?>"><?php echo $r['status'] === 'pending' ? 'Pending' : 'Answered'; ?></span></p>
                    <?php if (!empty($r['message'])): ?>
                        <div style="background: #f5f5f5; padding: 12px; border-radius: 8px; margin-bottom: 10px;"><?php echo nl2br(htmlspecialchars($r['message'])); ?></div>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'answered' && !empty($r['company_response'])): ?>
                        <p style="font-weight: 600; margin-bottom: 6px;">Your response:</p>
                        <div style="background: #e8f5e9; padding: 12px; border-radius: 8px; margin-bottom: 8px;"><?php echo nl2br(htmlspecialchars($r['company_response'])); ?></div>
                        <p style="font-size: 0.85rem; color: #666;">Responded: <?php echo formatDate($r['responded_at']); ?> (PH time)</p>
                    <?php endif; ?>
                    <div class="card-actions-ar">
                        <div class="left">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;"><input type="checkbox" class="ar-checkbox" value="<?php echo (int)$r['id']; ?>"> Select</label>
                            <?php if ($r['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-primary" onclick="openRespondModal(<?php echo (int)$r['id']; ?>)">Respond</button>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-delete" onclick="deleteOne(<?php echo (int)$r['id']; ?>)">Remove</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>

    <div id="respondModal" class="modal">
        <div class="modal-content">
            <h3 style="margin: 0 0 12px 0;">Respond to admin request</h3>
            <textarea id="respondText" placeholder="Type your response..."></textarea>
            <div style="margin-top: 16px; display: flex; gap: 10px;">
                <button type="button" class="btn btn-primary" id="respondSubmitBtn">
                    <span class="ar-respond-spin" id="arRespondSpinner" aria-hidden="true"></span>
                    <span id="arRespondBtnLabel">Send response</span>
                </button>
                <button type="button" class="btn" id="arRespondCancelBtn" style="background: #e0e0e0; color: #333;" onclick="closeRespondModal()">Cancel</button>
            </div>
        </div>
    </div>
    <input type="hidden" id="respondRequestId" value="">

    <script>
        function toggleProfileMenu() {
            var dropdown = document.getElementById('profileDropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
        window.onclick = function(event) {
            var dropdown = document.getElementById('profileDropdown');
            if (!event.target.matches('.profile-icon') && !event.target.closest('.profile-icon')) {
                if (dropdown && dropdown.style.display === 'block') dropdown.style.display = 'none';
            }
        };
        document.addEventListener('DOMContentLoaded', function() {
            var hamburgerMenu = document.getElementById('hamburgerMenu');
            var sidebar = document.querySelector('.sidebar.desktop-nav');
            if (hamburgerMenu && sidebar) {
                hamburgerMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                    hamburgerMenu.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                        if (!sidebar.contains(e.target) && !hamburgerMenu.contains(e.target)) {
                            sidebar.classList.remove('active');
                            hamburgerMenu.classList.remove('active');
                        }
                    }
                });
            }
        });
        function filterByStatus(v) {
            document.querySelectorAll('.ar-card').forEach(function(c) { c.style.display = (v === 'all' || c.getAttribute('data-status') === v) ? '' : 'none'; });
            updateArState();
        }
        function updateArState() {
            var vis = []; document.querySelectorAll('.ar-card').forEach(function(c) { if (c.style.display !== 'none') { var x = c.querySelector('.ar-checkbox'); if (x) vis.push(x); } });
            document.getElementById('deleteSelectedAr').disabled = document.querySelectorAll('.ar-checkbox:checked').length === 0;
            document.getElementById('selectAllAr').checked = vis.length > 0 && vis.every(function(cb) { return cb.checked; });
        }
        document.getElementById('selectAllAr').addEventListener('change', function() {
            var v = document.getElementById('statusFilterAr').value;
            document.querySelectorAll('.ar-card').forEach(function(c) {
                var cb = c.querySelector('.ar-checkbox');
                if (cb) cb.checked = this.checked && (v === 'all' || c.getAttribute('data-status') === v);
            }.bind(this));
            updateArState();
        });
        document.querySelectorAll('.ar-checkbox').forEach(function(cb) { cb.addEventListener('change', updateArState); });
        function showArDeleteLoading(count) {
            Swal.fire({
                title: 'Removing...',
                text: count > 1 ? ('Removing ' + count + ' requests…') : 'Removing request…',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: function () { Swal.showLoading(); }
            });
        }
        function runDeleteAdminRequests(ids) {
            showArDeleteLoading(ids.length);
            fetch('delete_admin_request.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: ids }) })
                .then(function (res) { return res.json(); })
                .then(function (d) {
                    Swal.close();
                    if (d.success) {
                        Swal.fire({ title: 'Done', text: d.message, icon: 'success', confirmButtonColor: '#1a3876' }).then(function () { location.reload(); });
                    } else {
                        Swal.fire({ title: 'Error', text: d.message || 'Could not remove.', icon: 'error', confirmButtonColor: '#1a3876' });
                    }
                })
                .catch(function () {
                    Swal.close();
                    Swal.fire({ title: 'Error', text: 'Request failed. Please try again.', icon: 'error', confirmButtonColor: '#1a3876' });
                });
        }
        document.getElementById('deleteSelectedAr').addEventListener('click', function() {
            var ids = []; document.querySelectorAll('.ar-checkbox:checked').forEach(function(cb) { ids.push(parseInt(cb.value, 10)); });
            if (ids.length === 0) return;
            Swal.fire({ title: 'Remove from list?', text: 'Remove ' + ids.length + ' request(s)?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Remove' }).then(function(r) {
                if (!r.isConfirmed) return;
                runDeleteAdminRequests(ids);
            });
        });
        function deleteOne(id) {
            Swal.fire({ title: 'Remove this request?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Remove' }).then(function(r) {
                if (!r.isConfirmed) return;
                runDeleteAdminRequests([id]);
            });
        }
        function setArRespondLoading(loading) {
            var btn = document.getElementById('respondSubmitBtn');
            var cancel = document.getElementById('arRespondCancelBtn');
            var label = document.getElementById('arRespondBtnLabel');
            if (!btn) return;
            if (loading) {
                btn.classList.add('is-loading');
                btn.disabled = true;
                if (label) label.textContent = 'Sending...';
                if (cancel) cancel.disabled = true;
            } else {
                btn.classList.remove('is-loading');
                btn.disabled = false;
                if (label) label.textContent = 'Send response';
                if (cancel) cancel.disabled = false;
            }
        }
        function openRespondModal(id) {
            document.getElementById('respondRequestId').value = id;
            document.getElementById('respondText').value = '';
            setArRespondLoading(false);
            document.getElementById('respondModal').classList.add('show');
        }
        function closeRespondModal() {
            setArRespondLoading(false);
            document.getElementById('respondModal').classList.remove('show');
        }
        document.getElementById('respondSubmitBtn').onclick = function() {
            var id = document.getElementById('respondRequestId').value;
            var text = document.getElementById('respondText').value.trim();
            if (!text) { Swal.fire({ title: 'Error', text: 'Please enter a response.', icon: 'warning' }); return; }
            setArRespondLoading(true);
            fetch('respond_to_admin_request.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ request_id: id, response: text }) })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    setArRespondLoading(false);
                    closeRespondModal();
                    if (d.success) { Swal.fire({ title: 'Sent', text: d.message, icon: 'success' }).then(function() { location.reload(); }); }
                    else Swal.fire({ title: 'Error', text: d.message || 'Failed.', icon: 'error' });
                })
                .catch(function() {
                    setArRespondLoading(false);
                    Swal.fire({ title: 'Error', text: 'Request failed. Please try again.', icon: 'error' });
                });
        };
    </script>
</body>
</html>
