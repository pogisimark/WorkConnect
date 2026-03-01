<?php
date_default_timezone_set('Asia/Manila');
include 'session_protect.php';
require_once 'db.php';
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Follow Up - WorkConnect</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #fafafa; min-height: 100vh; overflow-x: hidden; overflow-y: auto; }
        .header { background: #233a8b; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; height: 64px; position: fixed; top: 0; left: 0; width: 100%; max-width: 100vw; z-index: 1000; box-shadow: 0 2px 8px rgba(35,58,139,0.10); box-sizing: border-box; }
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
            .header-title { font-size: 1.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; min-width: 0; }
            .hamburger-menu { display: block !important; background: none; border: none; cursor: pointer; padding: 8px; margin-right: 12px; z-index: 1001; }
            .hamburger-menu span { display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px; }
            .hamburger-menu.active span:nth-child(1) { transform: rotate(-45deg) translate(-5px, 6px); }
            .hamburger-menu.active span:nth-child(2) { opacity: 0; }
            .hamburger-menu.active span:nth-child(3) { transform: rotate(45deg) translate(-5px, -6px); }
            .layout { flex-direction: column; padding-top: 60px; }
            .sidebar { position: fixed !important; top: 56px !important; left: -240px !important; width: 240px !important; height: calc(100vh - 56px) !important; transition: left 0.3s ease !important; display: flex !important; flex-direction: column !important; padding: 20px 0 0 24px !important; box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important; }
            .sidebar.active { left: 0 !important; }
            .main-content { margin-left: 0; padding: 16px; width: 100%; }
        }
        .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card.pending { border-left: 4px solid #ff9800; }
        .card.answered { border-left: 4px solid #4caf50; }
        .form-section { background: #f8fafc; border: 1px solid #e3f2fd; border-radius: 12px; padding: 24px; margin-bottom: 28px; }
        .form-section label { display: block; font-weight: 600; color: #233a8b; margin-bottom: 8px; font-size: 0.95rem; }
        .form-section select, .form-section textarea { width: 100%; max-width: 400px; padding: 10px 14px; border: 2px solid #e3f2fd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; }
        .form-section textarea { min-height: 100px; resize: vertical; }
        .btn-submit { background: #1976d2; color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: 0.95rem; cursor: pointer; margin-top: 12px; }
        .btn-submit:hover { background: #1565c0; }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-pending { background: #fff3e0; color: #e65100; }
        .badge-answered { background: #e8f5e9; color: #2e7d32; }
        .card-actions-ac { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 12px; }
        .card-actions-ac .left { display: flex; align-items: center; gap: 8px; }
        .btn-delete { background: #f44336; color: #fff; padding: 6px 12px; border-radius: 6px; border: none; font-size: 0.85rem; cursor: pointer; }
        .btn-delete:hover { background: #d32f2f; }
        .bulk-actions { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .bulk-actions input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .btn-delete-selected { background: #d32f2f; color: #fff; padding: 8px 16px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
        .btn-delete-selected:hover { background: #b71c1c; }
        .btn-delete-selected:disabled { opacity: 0.6; cursor: not-allowed; }
        .status-filter-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        #statusFilterAc { padding: 8px 14px; border: 2px solid #e3f2fd; border-radius: 8px; background: #fff; color: #233a8b; font-weight: 600; font-size: 0.9rem; cursor: pointer; min-width: 140px; }
    </style>
</head>
<body>
    <div class="header" id="mainHeader">
        <div style="display: flex; align-items: center;">
            <button class="hamburger-menu" id="hamburgerMenu"><span></span><span></span><span></span></button>
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">👤</div>
            <span id="adminUsername" style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php"> DASHBOARD</a>
            <a href="job_postings.php"> JOB POSTINGS</a>
            <a href="job.php"> JOBSEEKERS</a>
            <a href="follow_up_requests.php"> FOLLOW-UP REQUESTS</a>
            <a href="request_follow_up.php" class="active"> REQUEST FOLLOW UP</a>
            <a href="skill.php"> SKILL REGISTRY</a>
            <a href="btec.php"> BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
            <a href="analytics.php"> Analytics</a>
            <a href="announcement.php"> ANNOUNCEMENTS</a>
            <a href="#" class="logout"> Logout</a>
        </div>
        <div class="main-content">
            <h2 style="color: #233a8b; margin: 0 0 8px 0;">Request follow-up from company</h2>
            <p style="color: #666; margin: 0 0 24px 0;">Send a follow-up request to a company. They can respond from their portal. You will see their reply below.</p>

            <div class="form-section">
                <label for="companySelect">Select company</label>
                <select id="companySelect" style="max-width: 100%;">
                    <option value="">-- Select a company --</option>
                </select>
                <label for="messageText" style="margin-top: 16px;">Message:</label>
                <textarea id="messageText" placeholder="Add a message for the company..." style = "max-width: 100%;"></textarea>
                <br>
                <button type="button" class="btn-submit" id="submitRequestBtn">Send follow-up request</button>
            </div>

            <h3 style="color: #233a8b; margin: 0 0 12px 0; font-size: 1.1rem;">Past requests</h3>
            <div id="pastRequestsLoading" style="color: #666;">Loading...</div>
            <div id="pastRequestsBody" style="display: none;">
                <div class="status-filter-wrap">
                    <label for="statusFilterAc">Filter:</label>
                    <select id="statusFilterAc" onchange="filterAcByStatus(this.value)">
                        <option value="all">All</option>
                        <option value="pending">Pending</option>
                        <option value="answered">Answered</option>
                    </select>
                </div>
                <div class="bulk-actions">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" id="selectAllAc"> Select all</label>
                    <button type="button" class="btn-delete-selected" id="deleteSelectedAc" disabled>Delete selected</button>
                </div>
                <div id="pastRequestsList"></div>
            </div>
        </div>
    </div>

    <div id="logoutModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
        <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(25,118,210,0.18);padding:32px 28px;max-width:400px;width:100%;margin:0 auto;text-align:center;">
            <h3 style="margin-top:0;color:#233a8b;font-size:1.3rem;font-weight:bold;margin-bottom:12px;">Confirm Logout</h3>
            <p style="color:#666;margin-bottom:24px;font-size:1rem;">Are you sure you want to logout?</p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button id="confirmLogoutBtn" style="background:#f44336;color:#fff;border:none;border-radius:8px;padding:12px 24px;font-weight:600;cursor:pointer;">Yes, Logout</button>
                <button id="cancelLogoutBtn" style="background:#bdbdbd;color:#1a3876;border:none;border-radius:8px;padding:12px 24px;font-weight:600;cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let allCompanies = [];
        let allRequests = [];

        function formatPhTime(isoStr) {
            if (!isoStr) return '';
            var d = new Date(isoStr);
            return d.toLocaleString('en-PH', { timeZone: 'Asia/Manila', dateStyle: 'medium', timeStyle: 'short' });
        }
        function escapeHtml(t) { if (!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

        function loadCompanies() {
            fetch('get_companies.php').then(function(r) { return r.json(); }).then(function(data) {
                if (data.success && data.companies) allCompanies = data.companies;
                var sel = document.getElementById('companySelect');
                sel.innerHTML = '<option value="">-- Select a company --</option>';
                allCompanies.forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.company_name;
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }
        function loadPastRequests() {
            document.getElementById('pastRequestsLoading').style.display = 'block';
            document.getElementById('pastRequestsBody').style.display = 'none';
            fetch('get_admin_follow_up_requests.php').then(function(r) { return r.json(); }).then(function(data) {
                document.getElementById('pastRequestsLoading').style.display = 'none';
                if (!data.success) { document.getElementById('pastRequestsLoading').textContent = 'Unable to load.'; return; }
                allRequests = data.requests || [];
                if (allRequests.length === 0) {
                    document.getElementById('pastRequestsBody').style.display = 'block';
                    document.getElementById('pastRequestsList').innerHTML = '<div class="card"><p style="color:#666;margin:0;">No requests yet.</p></div>';
                    return;
                }
                document.getElementById('pastRequestsBody').style.display = 'block';
                renderPastRequests();
            }).catch(function() {
                document.getElementById('pastRequestsLoading').style.display = 'none';
                document.getElementById('pastRequestsLoading').textContent = 'Unable to load.';
            });
        }
        function renderPastRequests() {
            var filter = (document.getElementById('statusFilterAc') || {}).value || 'all';
            var fullHtml = '';
            allRequests.forEach(function(r) {
                if (filter !== 'all' && r.status !== filter) return;
                var isPending = r.status === 'pending';
                var dateStr = formatPhTime(r.created_at);
                var safeName = (r.company_name || '').replace(/'/g, "\\'").replace(/\\/g, '\\\\');
                fullHtml += '<div class="card follow-up-card-ac ' + (isPending ? 'pending' : 'answered') + '" data-id="' + r.id + '" data-status="' + r.status + '">';
                fullHtml += '<p style="font-size:0.85rem;color:#666;margin:0 0 10px 0;">To: <strong>' + escapeHtml(r.company_name) + '</strong> · ' + dateStr + ' (PH time) <span class="badge badge-' + r.status + '">' + (isPending ? 'Pending' : 'Answered') + '</span></p>';
                fullHtml += '<p style="color:#666;margin-bottom:6px;font-size:0.9rem;">Your message:</p><div style="background:#f5f5f5;padding:12px;border-radius:6px;margin-bottom:10px;font-size:0.9rem;">' + (r.message && r.message.trim() ? escapeHtml(r.message) : '<em style="color:#999;">No message</em>') + '</div>';
                if (!isPending && r.company_response) {
                    fullHtml += '<p style="color:#666;margin-bottom:6px;font-size:0.9rem;">Company response:</p><div style="background:#e8f5e9;padding:12px;border-radius:6px;margin-bottom:8px;font-size:0.9rem;">' + escapeHtml(r.company_response) + '</div>';
                    if (r.responded_at) fullHtml += '<p style="font-size:0.8rem;color:#666;margin:0;">Responded: ' + formatPhTime(r.responded_at) + ' (PH time)</p>';
                } else if (isPending) fullHtml += '<p style="color:#856404;font-size:0.9rem;margin:0;">Awaiting company response.</p>';
                fullHtml += '<div class="card-actions-ac"><div class="left"><label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.9rem;"><input type="checkbox" class="ac-checkbox" value="' + r.id + '"> Select</label></div><button type="button" class="btn-delete" onclick="deleteOneAc(' + r.id + ',\'' + safeName + '\')">Delete</button></div>';
                fullHtml += '</div>';
            });
            document.getElementById('pastRequestsList').innerHTML = fullHtml || '<div class="card"><p style="color:#666;margin:0;">No requests match the filter.</p></div>';
            document.querySelectorAll('.ac-checkbox').forEach(function(cb) { cb.addEventListener('change', updateAcDeleteState); });
        }
        function filterAcByStatus(value) {
            document.querySelectorAll('.follow-up-card-ac').forEach(function(card) {
                card.style.display = (value === 'all' || card.getAttribute('data-status') === value) ? '' : 'none';
            });
            updateAcDeleteState();
        }
        function updateAcDeleteState() {
            var visible = [];
            document.querySelectorAll('.follow-up-card-ac').forEach(function(card) {
                if (card.style.display !== 'none') { var cb = card.querySelector('.ac-checkbox'); if (cb) visible.push(cb); }
            });
            var any = document.querySelectorAll('.ac-checkbox:checked').length > 0;
            document.getElementById('deleteSelectedAc').disabled = !any;
            document.getElementById('selectAllAc').checked = visible.length > 0 && visible.every(function(cb) { return cb.checked; });
        }
        document.getElementById('selectAllAc').addEventListener('change', function() {
            var filter = (document.getElementById('statusFilterAc') || {}).value || 'all';
            document.querySelectorAll('.follow-up-card-ac').forEach(function(card) {
                var cb = card.querySelector('.ac-checkbox');
                if (cb) cb.checked = this.checked && (filter === 'all' || card.getAttribute('data-status') === filter);
            }.bind(this));
            updateAcDeleteState();
        });
        document.getElementById('deleteSelectedAc').addEventListener('click', function() {
            var ids = []; document.querySelectorAll('.ac-checkbox:checked').forEach(function(cb) { ids.push(parseInt(cb.value, 10)); });
            if (ids.length === 0) return;
            Swal.fire({ title: 'Remove from list?', text: 'Remove ' + ids.length + ' request(s) from your view?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#666', confirmButtonText: 'Remove' }).then(function(r) {
                if (!r.isConfirmed) return;
                this.disabled = true;
                fetch('delete_admin_follow_up.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: ids }) }).then(function(res) { return res.json(); }).then(function(data) {
                    if (data.success) { Swal.fire({ title: 'Done', text: data.message, icon: 'success' }); loadPastRequests(); }
                    else Swal.fire({ title: 'Error', text: data.message || 'Failed.', icon: 'error' });
                    document.getElementById('deleteSelectedAc').disabled = false;
                }).catch(function() { document.getElementById('deleteSelectedAc').disabled = false; });
            });
        });
        function deleteOneAc(id, name) {
            Swal.fire({ title: 'Remove this request?', text: 'Request to ' + name + ' will be removed from your list.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#666', confirmButtonText: 'Remove' }).then(function(r) {
                if (!r.isConfirmed) return;
                fetch('delete_admin_follow_up.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: [id] }) }).then(function(res) { return res.json(); }).then(function(data) {
                    if (data.success) { Swal.fire({ title: 'Done', text: data.message, icon: 'success' }); loadPastRequests(); }
                    else Swal.fire({ title: 'Error', text: data.message || 'Failed.', icon: 'error' });
                });
            });
        }
        document.getElementById('submitRequestBtn').addEventListener('click', function() {
            var companyId = document.getElementById('companySelect').value;
            var message = (document.getElementById('messageText').value || '').trim();
            if (!companyId) { Swal.fire({ title: 'Select company', text: 'Please select a company.', icon: 'warning' }); return; }
            var btn = this;
            btn.disabled = true;
            fetch('submit_admin_follow_up.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ company_id: parseInt(companyId, 10), message: message || '' }) })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    if (data.success) { Swal.fire({ title: 'Sent', text: data.message, icon: 'success' }); document.getElementById('messageText').value = ''; loadPastRequests(); }
                    else Swal.fire({ title: 'Error', text: data.message || 'Failed.', icon: 'error' });
                })
                .catch(function() { btn.disabled = false; Swal.fire({ title: 'Error', text: 'Request failed.', icon: 'error' }); });
        });
        loadCompanies();
        loadPastRequests();
        fetch('session_check.php').then(function(r) { return r.json(); }).then(function(d) {
            var u = document.getElementById('adminUsername'); if (u) u.textContent = d.username || 'Admin';
            var a = document.getElementById('addAccountLink'); if (a) a.style.display = d.isMainAdmin ? 'block' : 'none';
        }).catch(function() {});
        document.addEventListener('DOMContentLoaded', function() {
            var hamburger = document.getElementById('hamburgerMenu');
            var sidebar = document.querySelector('.sidebar');
            if (hamburger) hamburger.addEventListener('click', function() { sidebar.classList.toggle('active'); hamburger.classList.toggle('active'); });
            document.querySelectorAll('.logout').forEach(function(btn) { btn.addEventListener('click', function(e) { e.preventDefault(); document.getElementById('logoutModal').style.display = 'flex'; }); });
        });
        document.getElementById('confirmLogoutBtn').onclick = function() { this.disabled = true; this.textContent = 'Logging out...'; setTimeout(function() { window.location.href = 'logout.php'; }, 800); };
        document.getElementById('cancelLogoutBtn').onclick = function() { document.getElementById('logoutModal').style.display = 'none'; };
        window.addEventListener('click', function(e) { if (e.target === document.getElementById('logoutModal')) document.getElementById('logoutModal').style.display = 'none'; });
    </script>
</body>
</html>
