<?php
date_default_timezone_set('Asia/Manila');
include 'session_protect.php';
require_once 'db.php';

$requests = [];
if ($conn) {
    $sql = "SELECT f.id, f.jobseeker_id, f.message, f.status, f.admin_response, f.responded_at, f.created_at,
            j.firstname, j.surname, j.middlename, j.email, j.submission_date, j.created_at AS app_created
            FROM follow_up_requests f
            JOIN jobseeker j ON f.jobseeker_id = j.id
            ORDER BY FIELD(f.status, 'pending', 'answered'), f.created_at DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
}
$conn->close();

function formatName($row) {
    $name = trim(($row['firstname'] ?? '') . ' ' . (($row['middlename'] ?? '') !== '' && ($row['middlename'] ?? '') !== 'n/a' ? ($row['middlename'] . ' ') : '') . ($row['surname'] ?? ''));
    return $name ?: 'Applicant';
}
function formatDate($d) {
    if (empty($d)) return '—';
    return date('M j, Y g:i A', strtotime($d));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow-up requests - WorkConnect</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #fafafa; min-height: 100vh; }
        .header { background: #233a8b; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; height: 64px; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-sizing: border-box; }
        .header img { height: 48px; margin-right: 16px; border-radius: 50%; }
        .header-title { font-size: 1.7rem; font-weight: bold; }
        .layout { display: flex; min-height: calc(100vh - 64px); padding-top: 64px; }
        .sidebar { background: #e3eaff; width: 240px; position: fixed; top: 64px; left: 0; height: calc(100vh - 64px); z-index: 999; padding: 32px 0 0 24px; box-sizing: border-box; overflow-y: auto; }
        .sidebar a { font-weight: bold; color: #222; text-decoration: none; margin-bottom: 16px; font-size: 1rem; padding: 12px 16px; border-radius: 8px; display: block; margin-top: 10%; }
        .sidebar a:hover { color: #233a8b; background: #d1dbfa; }
        .sidebar a.active { color: #fff; background: #233a8b; }
        .sidebar .logout { margin-top: auto; margin-bottom: 32px; }
        .main-content { flex: 1; padding: 32px; background: #fff; margin-left: 240px; min-height: calc(100vh - 64px); overflow-y: auto; box-sizing: border-box; }
        .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card.pending { border-left: 4px solid #ff9800; }
        .card.answered { border-left: 4px solid #4caf50; }
        .card h3 { margin: 0 0 8px 0; color: #233a8b; font-size: 1.1rem; }
        .card .meta { color: #666; font-size: 0.9rem; margin-bottom: 10px; }
        .card .message { background: #f5f5f5; padding: 12px; border-radius: 8px; margin-bottom: 10px; white-space: pre-wrap; }
        .card .response { background: #e8f5e9; padding: 12px; border-radius: 8px; margin-top: 10px; white-space: pre-wrap; }
        .btn { padding: 8px 16px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
        .btn-primary { background: #1976d2; color: #fff; }
        .btn-primary:hover { background: #1565c0; }
        .modal { display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1100; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: #fff; border-radius: 12px; padding: 24px; max-width: 480px; width: 90%; }
        .modal-content textarea { width: 100%; min-height: 120px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .modal-actions { margin-top: 16px; display: flex; gap: 10px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-pending { background: #fff3e0; color: #e65100; }
        .badge-answered { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center;">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 1rem; font-weight: 500;">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php">📊 DASHBOARD</a>
            <a href="job_postings.php">💼 JOB POSTINGS</a>
            <a href="job.php">👥 JOBSEEKERS</a>
            <a href="follow_up_requests.php" class="active">📩 FOLLOW-UP REQUESTS</a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;">➕ ADD ACCOUNT</a>
            <a href="analytics.php">📊 Analytics</a>
            <a href="announcement.php">📢 ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
        <div class="main-content">
            <h2 style="color: #233a8b; margin: 0 0 8px 0;">Follow-up requests</h2>
            <p style="color: #666; margin: 0 0 24px 0;">Jobseekers who requested a follow-up on their pending application. Respond below; they will be notified.</p>

            <?php if (empty($requests)): ?>
                <div class="card">
                    <p style="color: #666; margin: 0;">No follow-up requests yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $r): ?>
                    <div class="card <?php echo $r['status'] === 'pending' ? 'pending' : 'answered'; ?>">
                        <h3><?php echo htmlspecialchars(formatName($r)); ?></h3>
                        <div class="meta">
                            <?php echo htmlspecialchars($r['email'] ?? ''); ?> ·
                            Application: <?php echo formatDate($r['submission_date'] ?? $r['app_created']); ?> ·
                            Requested: <?php echo formatDate($r['created_at']); ?>
                            <span class="badge badge-<?php echo $r['status'] === 'pending' ? 'pending' : 'answered'; ?>"><?php echo $r['status'] === 'pending' ? 'Pending' : 'Answered'; ?></span>
                        </div>
                        <?php if (!empty($r['message'])): ?>
                            <div class="message"><?php echo htmlspecialchars($r['message']); ?></div>
                        <?php endif; ?>
                        <?php if ($r['status'] === 'answered' && !empty($r['admin_response'])): ?>
                            <div class="response"><strong>Your response:</strong><br><?php echo htmlspecialchars($r['admin_response']); ?></div>
                            <p style="font-size: 0.85rem; color: #666; margin-top: 8px;">Responded: <?php echo formatDate($r['responded_at']); ?></p>
                        <?php endif; ?>
                        <?php if ($r['status'] === 'pending'): ?>
                            <button type="button" class="btn btn-primary" onclick="openRespondModal(<?php echo (int)$r['id']; ?>, '<?php echo htmlspecialchars(formatName($r), ENT_QUOTES); ?>')">Respond</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="respondModal" class="modal">
        <div class="modal-content">
            <h3 style="margin: 0 0 12px 0;">Respond to follow-up request</h3>
            <p id="respondModalName" style="color: #666; margin: 0 0 12px 0;"></p>
            <textarea id="respondModalText" placeholder="Type your response. The jobseeker will see this in their notifications."></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" id="respondSubmitBtn">Send response</button>
                <button type="button" class="btn" style="background: #e0e0e0; color: #333;" onclick="closeRespondModal()">Cancel</button>
            </div>
        </div>
    </div>
    <input type="hidden" id="respondRequestId" value="">

    <script>
        function openRespondModal(requestId, name) {
            document.getElementById('respondRequestId').value = requestId;
            document.getElementById('respondModalName').textContent = name;
            document.getElementById('respondModalText').value = '';
            document.getElementById('respondModal').classList.add('show');
        }
        function closeRespondModal() {
            document.getElementById('respondModal').classList.remove('show');
        }
        document.getElementById('respondSubmitBtn').onclick = function() {
            var requestId = document.getElementById('respondRequestId').value;
            var text = document.getElementById('respondModalText').value.trim();
            if (!text) {
                Swal.fire({ title: 'Error', text: 'Please enter a response.', icon: 'warning' });
                return;
            }
            this.disabled = true;
            fetch('respond_follow_up.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId, response: text })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('respondSubmitBtn').disabled = false;
                closeRespondModal();
                if (data.success) {
                    Swal.fire({ title: 'Sent', text: data.message, icon: 'success' }).then(function() { location.reload(); });
                } else {
                    Swal.fire({ title: 'Error', text: data.message || 'Failed to send.', icon: 'error' });
                }
            })
            .catch(function() {
                document.getElementById('respondSubmitBtn').disabled = false;
                Swal.fire({ title: 'Error', text: 'Request failed. Please try again.', icon: 'error' });
            });
        };
    </script>
</body>
</html>
