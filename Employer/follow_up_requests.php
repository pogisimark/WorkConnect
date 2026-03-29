<?php
date_default_timezone_set('Asia/Manila');
include 'session_protect.php';
require_once 'db.php';
require_once __DIR__ . '/follow_up_pending_badge.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';
require_once __DIR__ . '/jobseeker_pending_badge.php';
$follow_up_pending_count = fu_get_pending_follow_up_count($conn);

$requests = [];
if ($conn) {
    $sql = "SELECT f.id, f.jobseeker_id, f.message, f.status, f.admin_response, f.responded_at, f.created_at,
            j.firstname, j.surname, j.middlename, j.email, j.submission_date, j.created_at AS app_created
            FROM follow_up_requests f
            JOIN jobseeker j ON f.jobseeker_id = j.id
            WHERE (COALESCE(f.hidden_by_admin, 0) = 0)
            ORDER BY FIELD(f.status, 'pending', 'answered'), f.created_at DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
}
$acfu_unread_count = acfu_get_unread_response_count($conn);
$pending_jobseekers_count = js_get_pending_jobseekers_count($conn);
$conn->close();

function formatName($row) {
    $name = trim(($row['firstname'] ?? '') . ' ' . (($row['middlename'] ?? '') !== '' && ($row['middlename'] ?? '') !== 'n/a' ? ($row['middlename'] . ' ') : '') . ($row['surname'] ?? ''));
    return $name ?: 'Applicant';
}
// Format datetime in Philippines time (UTC+8); DB session timezone is set in db.php
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
    <title>Follow-up requests - WorkConnect</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #fafafa; min-height: 100vh; min-height: 100dvh; overflow-x: hidden; overflow-y: auto; }
        .header { background: #233a8b; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; height: 64px; position: fixed; top: 0; left: 0; width: 100%; max-width: 100vw; z-index: 1000; box-shadow: 0 2px 8px rgba(35,58,139,0.10); box-sizing: border-box; }
        .header img { height: 48px; margin-right: 16px; border-radius: 50%; background: none; border: none; }
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
            .header-title { font-size: 1.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; min-width: 0; }
            .hamburger-menu { display: block !important; background: none; border: none; cursor: pointer; padding: 8px; margin-right: 12px; z-index: 1001; }
            .hamburger-menu span { display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px; }
            .hamburger-menu.active span:nth-child(1) { transform: rotate(-45deg) translate(-5px, 6px); }
            .hamburger-menu.active span:nth-child(2) { opacity: 0; }
            .hamburger-menu.active span:nth-child(3) { transform: rotate(45deg) translate(-5px, -6px); }
            .layout { flex-direction: column; padding-top: 60px; }
            .sidebar { position: fixed !important; top: 56px !important; left: -240px !important; width: 240px !important; height: calc(100vh - 56px) !important; height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important; max-height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important; transition: left 0.3s ease !important; display: flex !important; flex-direction: column !important; padding: 20px 0 0 24px !important; box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important; }
            .sidebar.active { left: 0 !important; }
            .sidebar a { display: flex; align-items: center; padding: 12px 16px; margin-bottom: 8px; gap: 12px; }
            .main-content { margin-left: 0; padding: 16px; width: 100%; }
        }
        .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card.pending { border-left: 4px solid #ff9800; }
        .card.answered { border-left: 4px solid #4caf50; }
        .card h3 { margin: 0 0 8px 0; color: #233a8b; font-size: 1.1rem; }
        .card .meta { color: #666; font-size: 0.9rem; margin-bottom: 10px; }
        .card .message { background: #f5f5f5; padding: 12px; border-radius: 8px; margin-bottom: 10px; white-space: pre-wrap; }
        .card .response { background: #e8f5e9; padding: 12px; border-radius: 8px; margin-top: 10px; white-space: pre-wrap; }
        .btn { padding: 8px 16px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
        .btn-primary { background: #1976d2; color: #fff; }
        .btn-primary:hover:not(:disabled) { background: #1565c0; }
        #respondSubmitBtn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-width: 160px; }
        #respondSubmitBtn:disabled { opacity: 0.92; cursor: wait; }
        .respond-spin { display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff; border-radius: 50%; animation: respondSpin 0.65s linear infinite; flex-shrink: 0; }
        #respondSubmitBtn.is-loading .respond-spin { display: inline-block; }
        @keyframes respondSpin { to { transform: rotate(360deg); } }
        .modal { display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1100; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: #fff; border-radius: 12px; padding: 24px; max-width: 480px; width: 90%; }
        .modal-content textarea { width: 100%; min-height: 120px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .modal-actions { margin-top: 16px; display: flex; gap: 10px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-pending { background: #fff3e0; color: #e65100; }
        .badge-answered { background: #e8f5e9; color: #2e7d32; }
        .card-actions { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 12px; }
        .card-actions .left { display: flex; align-items: center; gap: 8px; }
        .btn-delete { background: #f44336; color: #fff; padding: 6px 12px; border-radius: 6px; border: none; font-size: 0.85rem; cursor: pointer; }
        .btn-delete:hover { background: #d32f2f; }
        .bulk-actions { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .bulk-actions input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .btn-delete-selected { background: #d32f2f; color: #fff; padding: 8px 16px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
        .btn-delete-selected:hover { background: #b71c1c; }
        .btn-delete-selected:disabled { opacity: 0.6; cursor: not-allowed; }
        .follow-up-checkbox:disabled { cursor: not-allowed; opacity: 0.55; }
        .delete-hint { font-size: 0.8rem; color: #888; max-width: 200px; text-align: right; line-height: 1.35; }
        .status-filter-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .status-filter-wrap label { font-weight: 600; color: #233a8b; font-size: 0.9rem; }
        #statusFilterFu { padding: 8px 14px; border: 2px solid #e3f2fd; border-radius: 8px; background: #fff; color: #233a8b; font-weight: 600; font-size: 0.9rem; cursor: pointer; min-width: 140px; }
        #statusFilterFu:hover, #statusFilterFu:focus { border-color: #1976d2; outline: none; }
    </style>
    <link rel="stylesheet" href="../assets/css/Employer-sidebar-neat.css?v=<?php echo time(); ?>">
    <script src="../assets/js/employer-page-loading.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
<div class="header" id="mainHeader">
        <div style="display: flex; align-items: center;">
            <button class="hamburger-menu" id="hamburgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title" id="headerTitle">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;" id="adminSection">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">👤</div>
            <span id="adminUsername" style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php"> DASHBOARD</a>
            <a href="job_postings.php"> JOB POSTINGS</a>
            <a href="job.php"> JOBSEEKERS<?php echo js_pending_jobseekers_badge_html($pending_jobseekers_count); ?></a>
            <a href="follow_up_requests.php" class="active"> FOLLOW-UP REQUESTS<?php echo fu_follow_up_badge_html($follow_up_pending_count); ?></a>
            <a href="request_follow_up.php"> REQUEST FOLLOW UP<span class="acfu-sidebar-badge"><?php echo acfu_unread_badge_html($acfu_unread_count); ?></span></a>
            <a href="skill.php"> SKILL REGISTRY</a>
            <a href="companies_list.php"> COMPANIES</a>
            <a href="btec.php"> BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
            <a href="analytics.php"> Analytics</a>
            <a href="announcement.php"> ANNOUNCEMENTS</a>
            <a href="#" class="logout"> Logout</a>
        </div>
        <div class="main-content">
            <h2 style="color: #233a8b; margin: 0 0 8px 0;">Follow-up requests</h2>
            <p style="color: #666; margin: 0 0 24px 0;">Jobseekers who requested a follow-up on their pending application. Respond below; they will be notified.</p>

            <?php if (empty($requests)): ?>
                <div class="card">
                    <p style="color: #666; margin: 0;">No follow-up requests yet.</p>
                </div>
            <?php else: ?>
                <div class="status-filter-wrap">
                    <label for="statusFilterFu">Filter by status:</label>
                    <select id="statusFilterFu" onchange="filterFollowUpByStatus(this.value)">
                        <option value="all">All</option>
                        <option value="pending">Pending</option>
                        <option value="answered">Answered</option>
                    </select>
                </div>
                <div class="bulk-actions">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" id="selectAllFollowUp"> Select all</label>
                    <button type="button" class="btn-delete-selected" id="deleteSelectedFollowUp" disabled>Delete selected</button>
                </div>
                <?php foreach ($requests as $r): ?>
                    <div class="card follow-up-card <?php echo $r['status'] === 'pending' ? 'pending' : 'answered'; ?>" data-request-id="<?php echo (int)$r['id']; ?>" data-status="<?php echo $r['status'] === 'pending' ? 'pending' : 'answered'; ?>">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                            <div style="flex: 1; min-width: 0;">
                                <h3><?php echo htmlspecialchars(formatName($r)); ?></h3>
                                <div class="meta">
                                    <?php echo htmlspecialchars($r['email'] ?? ''); ?> ·
                                    Application: <?php echo formatDate($r['submission_date'] ?? $r['app_created']); ?> ·
                                    Requested: <?php echo formatDate($r['created_at']); ?> <span style="color:#888;font-size:0.8rem;">(PH time)</span>
                                    <span class="badge badge-<?php echo $r['status'] === 'pending' ? 'pending' : 'answered'; ?>"><?php echo $r['status'] === 'pending' ? 'Pending' : 'Answered'; ?></span>
                                </div>
                                <?php if (!empty($r['message'])): ?>
                                    <div class="message"><?php echo htmlspecialchars($r['message']); ?></div>
                                <?php endif; ?>
                                <?php if ($r['status'] === 'answered' && !empty($r['admin_response'])): ?>
                                    <div class="response"><strong>Your response:</strong><br><?php echo htmlspecialchars($r['admin_response']); ?></div>
                                    <p style="font-size: 0.85rem; color: #666; margin-top: 8px;">Responded: <?php echo formatDate($r['responded_at']); ?> <span style="color:#888;font-size:0.8rem;">(PH time)</span></p>
                                <?php endif; ?>
                                <div class="card-actions">
                                    <div class="left">
                                        <label style="display: flex; align-items: center; gap: 6px; cursor: <?php echo $r['status'] === 'pending' ? 'not-allowed' : 'pointer'; ?>; font-size: 0.9rem;"><input type="checkbox" class="follow-up-checkbox" value="<?php echo (int)$r['id']; ?>"<?php echo $r['status'] === 'pending' ? ' disabled title="Respond to this request before it can be removed."' : ''; ?>> Select</label>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-primary" onclick="openRespondModal(<?php echo (int)$r['id']; ?>, '<?php echo htmlspecialchars(formatName($r), ENT_QUOTES); ?>')">Respond</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($r['status'] === 'answered'): ?>
                                        <button type="button" class="btn-delete" onclick="deleteOneFollowUp(<?php echo (int)$r['id']; ?>, '<?php echo htmlspecialchars(formatName($r), ENT_QUOTES); ?>')">Delete</button>
                                    <?php else: ?>
                                        <span class="delete-hint" title="Send a response first.">Respond first to remove</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logout Modal (same as Dashboard) -->
    <div id="logoutModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;inset:0;width:100%;height:100%;min-height:100vh;min-height:100dvh;max-height:100dvh;box-sizing:border-box;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
        <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(25,118,210,0.18);padding:32px 28px 24px 28px;max-width:400px;width:100%;margin:0 auto;text-align:center;">
            <div style="font-size:3rem;margin-bottom:16px;"></div>
            <h3 style="margin-top:0;color:#233a8b;font-size:1.3rem;font-weight:bold;margin-bottom:12px;">Confirm Logout</h3>
            <p style="color:#666;margin-bottom:24px;font-size:1rem;">Are you sure you want to logout from your account?</p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button id="confirmLogoutBtn" style="background:#f44336;color:#fff;border:none;border-radius:8px;padding:12px 24px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.2s ease;">Yes, Logout</button>
                <button id="cancelLogoutBtn" style="background:#bdbdbd;color:#1a3876;border:none;border-radius:8px;padding:12px 24px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.2s ease;">Cancel</button>
            </div>
        </div>
    </div>

    <div id="respondModal" class="modal">
        <div class="modal-content">
            <h3 style="margin: 0 0 12px 0;">Respond to follow-up request</h3>
            <p id="respondModalName" style="color: #666; margin: 0 0 12px 0;"></p>
            <textarea id="respondModalText" placeholder="Type your response. The jobseeker will see this in their notifications."></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" id="respondSubmitBtn">
                    <span class="respond-spin" id="respondSpinner" aria-hidden="true"></span>
                    <span id="respondBtnLabel">Send response</span>
                </button>
                <button type="button" class="btn" id="respondCancelBtn" style="background: #e0e0e0; color: #333;" onclick="closeRespondModal()">Cancel</button>
            </div>
        </div>
    </div>
    <input type="hidden" id="respondRequestId" value="">

    <script>
        function setRespondModalLoading(loading) {
            var btn = document.getElementById('respondSubmitBtn');
            var cancel = document.getElementById('respondCancelBtn');
            var label = document.getElementById('respondBtnLabel');
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
        function openRespondModal(requestId, name) {
            document.getElementById('respondRequestId').value = requestId;
            document.getElementById('respondModalName').textContent = name;
            document.getElementById('respondModalText').value = '';
            setRespondModalLoading(false);
            document.getElementById('respondModal').classList.add('show');
        }
        function closeRespondModal() {
            setRespondModalLoading(false);
            document.getElementById('respondModal').classList.remove('show');
        }
        document.getElementById('respondSubmitBtn').onclick = function() {
            var requestId = document.getElementById('respondRequestId').value;
            var text = document.getElementById('respondModalText').value.trim();
            if (!text) {
                Swal.fire({ title: 'Error', text: 'Please enter a response.', icon: 'warning' });
                return;
            }
            setRespondModalLoading(true);
            fetch('respond_follow_up.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId, response: text })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setRespondModalLoading(false);
                closeRespondModal();
                if (data.success) {
                    Swal.fire({ title: 'Sent', text: data.message, icon: 'success' }).then(function() { location.reload(); });
                } else {
                    Swal.fire({ title: 'Error', text: data.message || 'Failed to send.', icon: 'error' });
                }
            })
            .catch(function() {
                setRespondModalLoading(false);
                Swal.fire({ title: 'Error', text: 'Request failed. Please try again.', icon: 'error' });
            });
        };
        // Session check (same as Dashboard): username + show Add Account for main admin
        fetch('session_check.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var un = document.getElementById('adminUsername');
                if (un) un.textContent = data.username || 'Admin';
                var addLink = document.getElementById('addAccountLink');
                if (addLink) addLink.style.display = data.isMainAdmin ? 'block' : 'none';
            })
            .catch(function() {});

        // Hamburger menu (same as Dashboard)
        document.addEventListener('DOMContentLoaded', function() {
            var hamburgerMenu = document.getElementById('hamburgerMenu');
            var sidebar = document.querySelector('.sidebar');
            function checkScreenSize() {
                if (window.innerWidth <= 768) {
                    hamburgerMenu.style.display = 'block';
                } else {
                    hamburgerMenu.style.display = 'none';
                    sidebar.classList.remove('active');
                    hamburgerMenu.classList.remove('active');
                }
            }
            checkScreenSize();
            window.addEventListener('resize', checkScreenSize);
            hamburgerMenu.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                hamburgerMenu.classList.toggle('active');
            });
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768 && !sidebar.contains(event.target) && !hamburgerMenu.contains(event.target)) {
                    sidebar.classList.remove('active');
                    hamburgerMenu.classList.remove('active');
                }
            });

            // Logout: show modal instead of navigating
            document.querySelectorAll('.logout').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('logoutModal').style.display = 'flex';
                });
            });
        });

        // Logout modal: confirm and cancel
        document.getElementById('confirmLogoutBtn').onclick = function() {
            var confirmBtn = document.getElementById('confirmLogoutBtn');
            var cancelBtn = document.getElementById('cancelLogoutBtn');
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            confirmBtn.textContent = 'Logging out...';
            var style = document.createElement('style');
            style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            document.head.appendChild(style);
            setTimeout(function() { window.location.href = 'logout.php'; }, 1000);
        };
        document.getElementById('cancelLogoutBtn').onclick = function() {
            document.getElementById('logoutModal').style.display = 'none';
        };
        window.addEventListener('click', function(e) {
            if (e.target === document.getElementById('logoutModal')) {
                document.getElementById('logoutModal').style.display = 'none';
            }
        });

        // Pending / Answered filter
        function filterFollowUpByStatus(value) {
            document.querySelectorAll('.follow-up-card').forEach(function(card) {
                var status = card.getAttribute('data-status');
                if (value === 'all' || status === value) card.style.display = '';
                else card.style.display = 'none';
            });
            updateDeleteSelectedState();
        }

        // Select all / Delete selected (Select all only affects visible cards)
        var selectAllEl = document.getElementById('selectAllFollowUp');
        var deleteSelectedBtn = document.getElementById('deleteSelectedFollowUp');
        var checkboxes = document.querySelectorAll('.follow-up-checkbox');
        function getVisibleEligibleCheckboxes() {
            var eligible = [];
            document.querySelectorAll('.follow-up-card').forEach(function(card) {
                if (card.style.display === 'none') return;
                var cb = card.querySelector('.follow-up-checkbox');
                if (cb && !cb.disabled) eligible.push(cb);
            });
            return eligible;
        }
        function updateDeleteSelectedState() {
            var visibleCheckboxes = getVisibleEligibleCheckboxes();
            var anyChecked = document.querySelectorAll('.follow-up-checkbox:checked').length > 0;
            if (deleteSelectedBtn) deleteSelectedBtn.disabled = !anyChecked;
            var allVisibleChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(function(cb) { return cb.checked; });
            if (selectAllEl) selectAllEl.checked = allVisibleChecked;
        }
        if (selectAllEl) {
            selectAllEl.addEventListener('change', function() {
                var value = document.getElementById('statusFilterFu') ? document.getElementById('statusFilterFu').value : 'all';
                document.querySelectorAll('.follow-up-card').forEach(function(card) {
                    var status = card.getAttribute('data-status');
                    var visible = value === 'all' || status === value;
                    var cb = card.querySelector('.follow-up-checkbox');
                    if (cb && !cb.disabled) cb.checked = selectAllEl.checked && visible;
                });
                updateDeleteSelectedState();
            });
        }
        document.querySelectorAll('.follow-up-checkbox').forEach(function(cb) {
            cb.addEventListener('change', updateDeleteSelectedState);
        });
        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', function() {
                var ids = [];
                document.querySelectorAll('.follow-up-checkbox:checked').forEach(function(cb) { ids.push(parseInt(cb.value, 10)); });
                if (ids.length === 0) return;
                Swal.fire({ title: 'Delete requests?', text: 'Only answered follow-ups can be removed (' + ids.length + ' selected). Pending requests must be responded to first.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#666', confirmButtonText: 'Delete' })
                    .then(function(result) {
                        if (!result.isConfirmed) return;
                        deleteSelectedBtn.disabled = true;
                        fetch('delete_follow_up.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: ids }) })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.success) { Swal.fire({ title: 'Deleted', text: data.message, icon: 'success' }).then(function() { location.reload(); }); }
                                else { Swal.fire({ title: 'Error', text: data.message || 'Failed to delete.', icon: 'error' }); deleteSelectedBtn.disabled = false; }
                            })
                            .catch(function() { Swal.fire({ title: 'Error', text: 'Request failed.', icon: 'error' }); deleteSelectedBtn.disabled = false; });
                    });
            });
        }
        function deleteOneFollowUp(id, name) {
            Swal.fire({ title: 'Delete this request?', text: 'Remove this answered follow-up from your list (' + name + ').', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#666', confirmButtonText: 'Delete' })
                .then(function(result) {
                    if (!result.isConfirmed) return;
                    fetch('delete_follow_up.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: [id] }) })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) { Swal.fire({ title: 'Deleted', text: data.message, icon: 'success' }).then(function() { location.reload(); }); }
                            else { Swal.fire({ title: 'Error', text: data.message || 'Failed to delete.', icon: 'error' }); }
                        })
                        .catch(function() { Swal.fire({ title: 'Error', text: 'Request failed.', icon: 'error' }); });
                });
        }
    </script>
</body>
</html>
