<?php
date_default_timezone_set('Asia/Manila');
include 'session_protect.php';
require_once 'db.php';
require_once __DIR__ . '/admin_audit_helper.php';

admin_audit_ensure_schema($conn);

$q = trim((string)($_GET['q'] ?? ''));
$action = trim((string)($_GET['action'] ?? ''));
$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = "(admin_username LIKE ? OR description LIKE ? OR entity_type LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($action !== '') {
    $where[] = "action = ?";
    $params[] = $action;
    $types .= 's';
}
if ($from !== '') {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $from;
    $types .= 's';
}
if ($to !== '') {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $to;
    $types .= 's';
}

$whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) AS c FROM admin_audit_logs $whereSql";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = (int)($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
$countStmt->close();

$sql = "SELECT id, admin_username, action, entity_type, entity_id, description, meta_json, ip_address, created_at
        FROM admin_audit_logs
        $whereSql
        ORDER BY id DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$bindTypes = $types . 'ii';
$bindParams = $params;
$bindParams[] = $perPage;
$bindParams[] = $offset;
$stmt->bind_param($bindTypes, ...$bindParams);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$actions = [];
$actRes = $conn->query("SELECT DISTINCT action FROM admin_audit_logs ORDER BY action ASC");
if ($actRes) {
    while ($r = $actRes->fetch_assoc()) {
        $actions[] = (string)$r['action'];
    }
}
$conn->close();

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function format_meta_html($metaJson): string {
    $metaJson = (string)$metaJson;
    if (trim($metaJson) === '') {
        return '<span class="meta-empty">—</span>';
    }
    $decoded = json_decode($metaJson, true);
    if (!is_array($decoded)) {
        return '<span class="meta-raw">' . h($metaJson) . '</span>';
    }
    $parts = [];
    foreach ($decoded as $k => $v) {
        if (is_array($v)) {
            $v = implode(', ', array_map(static function($x){ return (string)$x; }, $v));
        } elseif (is_bool($v)) {
            $v = $v ? 'true' : 'false';
        } elseif ($v === null) {
            $v = 'null';
        }
        $parts[] = '<span class="meta-pill"><span class="mk">' . h($k) . '</span><span class="mv">' . h((string)$v) . '</span></span>';
    }
    if (empty($parts)) {
        return '<span class="meta-empty">—</span>';
    }
    return '<div class="meta-wrap">' . implode('', $parts) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Audit Logs - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employer-sidebar-neat.css?v=<?php echo time(); ?>">
    <style>
        body{margin:0;font-family:Arial,sans-serif;background:#f7f9fc}
        .header{background:#233a8b;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:12px 20px;height:64px;position:fixed;top:0;left:0;width:100%;z-index:1000;box-sizing:border-box}
        .header .logo{height:40px;border-radius:50%;margin-right:10px}
        .header-title{font-size:1.05rem;font-weight:700}
        .layout{display:flex;min-height:calc(100vh - 64px);padding-top:64px}
        .sidebar{background:#e3eaff;width:240px;position:fixed;top:64px;left:0;height:calc(100vh - 64px);padding:16px 0 0 24px;box-sizing:border-box;overflow:auto;display:flex;flex-direction:column}
        .sidebar a{font-weight:700;color:#222;text-decoration:none;margin-bottom:12px;font-size:1rem;padding:10px 14px;border-radius:8px;display:flex;align-items:center}
        .sidebar a.active{background:#233a8b;color:#fff}
        .sidebar .logout{margin-top:auto;margin-bottom:24px}
        .main{margin-left:240px;flex:1;padding:22px}
        .card{background:#fff;border:1px solid #e4eaf5;border-radius:12px;padding:16px}
        .filters{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;margin-bottom:14px}
        .filters input,.filters select{padding:9px;border:1px solid #d9e2f1;border-radius:8px}
        .btn{background:#233a8b;color:#fff;border:none;border-radius:8px;padding:9px 14px;cursor:pointer;font-weight:700}
        table{width:100%;border-collapse:collapse}
        th,td{padding:10px;border-bottom:1px solid #edf1f8;font-size:.9rem;vertical-align:top}
        th{background:#f4f8ff;color:#1f3d79;text-align:left}
        .meta{max-width:420px;font-size:.82rem;color:#495b7a}
        .meta-wrap{display:flex;flex-wrap:wrap;gap:6px}
        .meta-pill{display:inline-flex;align-items:center;border:1px solid #dfe7f6;border-radius:999px;background:#f7faff;overflow:hidden}
        .meta-pill .mk{background:#edf3ff;color:#1f4a93;font-weight:700;padding:3px 7px}
        .meta-pill .mv{padding:3px 8px;color:#2b3a58}
        .meta-empty{color:#95a1ba}
        .meta-raw{white-space:pre-wrap;word-break:break-word}
        .pager{display:flex;justify-content:space-between;align-items:center;margin-top:12px}
        .muted{color:#6a7893;font-size:.85rem}
        .hamburger-menu{display:none;background:none;border:none;cursor:pointer;padding:8px;margin-right:8px}
        .hamburger-menu span{display:block;width:24px;height:3px;background:#fff;margin:4px 0;border-radius:2px}
        @media (max-width: 768px){
            .hamburger-menu{display:block}
            .sidebar{left:-240px;transition:left .25s ease}
            .sidebar.active{left:0}
            .main{margin-left:0;padding:14px}
            .filters{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
<div class="header" id="mainHeader">
    <div style="display:flex;align-items:center;">
        <button class="hamburger-menu" id="hamburgerMenu"><span></span><span></span><span></span></button>
        <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
        <span class="header-title" id="headerTitle">WorkConnect</span>
    </div>
    <div style="display:flex;align-items:center;gap:8px;margin-right:20px;" id="adminSection">
        <div style="width:28px;height:28px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;color:#233a8b;font-weight:bold;">👤</div>
        <span id="adminUsername" style="font-size:1rem;font-weight:500;"><?php echo h($_SESSION['username'] ?? 'Admin'); ?></span>
    </div>
</div>
<div class="layout">
    <div class="sidebar">
        <a href="Dashboard.php"> DASHBOARD</a>
        <a href="job_postings.php"> JOB POSTINGS</a>
        <a href="job.php"> JOBSEEKERS</a>
        <a href="follow_up_requests.php"> FOLLOW-UP REQUESTS</a>
        <a href="request_follow_up.php"> REQUEST FOLLOW UP</a>
        <a href="skill.php"> SKILL REGISTRY</a>
        <a href="companies_list.php"> COMPANIES</a>
        <a href="btec.php"> BTEC MONTHLY REPORT</a>
        <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
        <a href="analytics.php"> Analytics</a>
        <a href="announcement.php"> ANNOUNCEMENTS</a>
        <a href="audit_logs.php" class="active"> 🧾 AUDIT LOGS</a>
        <a href="logout.php" class="logout"> Logout</a>
    </div>
    <main class="main">
        <h2 style="margin-top:0;color:#233a8b;">Admin Audit Logs</h2>
        <div class="card">
            <form method="get" class="filters">
                <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Search admin, entity, description">
                <select name="action">
                    <option value="">All actions</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?php echo h($a); ?>" <?php echo $action === $a ? 'selected' : ''; ?>><?php echo h($a); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="from" value="<?php echo h($from); ?>">
                <input type="date" name="to" value="<?php echo h($to); ?>">
                <button class="btn" type="submit">Filter</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>When</th><th>Admin</th><th>Action</th><th>Entity</th><th>Description</th><th>Meta</th><th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="muted">No audit logs found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo h($r['created_at']); ?></td>
                        <td><?php echo h($r['admin_username']); ?></td>
                        <td><?php echo h($r['action']); ?></td>
                        <td><?php echo h(($r['entity_type'] ?? '')) . (($r['entity_id'] !== null && $r['entity_id'] !== '') ? (' #' . h($r['entity_id'])) : ''); ?></td>
                        <td><?php echo h($r['description'] ?? ''); ?></td>
                        <td class="meta"><?php echo format_meta_html($r['meta_json'] ?? ''); ?></td>
                        <td><?php echo h($r['ip_address'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div class="pager">
                <div class="muted">Total logs: <?php echo (int)$total; ?></div>
                <div>
                    <?php
                    $prev = max(1, $page - 1);
                    $next = $page + 1;
                    $qsBase = $_GET; $qsBase['page'] = $prev;
                    ?>
                    <a class="btn" href="?<?php echo h(http_build_query($qsBase)); ?>" style="text-decoration:none;">Prev</a>
                    <?php $qsBase['page'] = $next; ?>
                    <a class="btn" href="?<?php echo h(http_build_query($qsBase)); ?>" style="text-decoration:none;">Next</a>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    var hamburger = document.getElementById('hamburgerMenu');
    var sidebar = document.querySelector('.sidebar');
    if (hamburger && sidebar) {
        hamburger.onclick = function() { sidebar.classList.toggle('active'); };
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
    fetch('session_check.php').then(function(r){return r.json();}).then(function(d){
        var un = document.getElementById('adminUsername');
        if (un && d && d.username) un.textContent = d.username;
        var addLink = document.getElementById('addAccountLink');
        if (addLink) addLink.style.display = d && d.isMainAdmin ? 'block' : 'none';
    }).catch(function(){});
</script>
</body>
</html>


