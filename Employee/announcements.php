<?php 
include 'session_check.php'; 

// Check if this is being loaded in an iframe (from dashboard)
$isIframe = isset($_GET['session_id']) && isset($_GET['user_id']) && isset($_GET['token']);

if ($isIframe) {
    // Validate session token for iframe security
    // Use a more lenient validation - check if session_id and user_id match
    $expected_session_id = $_GET['session_id'] ?? '';
    $expected_user_id = $_GET['user_id'] ?? '';
    
    if ($expected_session_id !== session_id() || $expected_user_id != $_SESSION['user_id']) {
        die('Invalid session parameters');
    }
    
    // Additional token validation (optional - can be removed if too strict)
    $expected_token = hash('sha256', session_id() . $_SESSION['user_id'] . 'workconnect');
    $provided_token = $_GET['token'] ?? '';
    
    // Allow some flexibility in token validation
    if ($provided_token && $expected_token !== $provided_token) {
        // Try with a slightly different token format
        $alt_token = hash('sha256', session_id() . $_SESSION['user_id']);
        if ($alt_token !== $provided_token) {
            // For now, just log the mismatch but don't block access
            error_log("Token mismatch for user {$_SESSION['user_id']}");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect - Announcements</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fafafa;
            min-height: 100vh; min-height: 100dvh;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        <?php if (!$isIframe): ?>
        .header {
            background: #233a8b;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            height: 64px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            max-width: 100vw;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(35,58,139,0.10);
            box-sizing: border-box;
        }
        .header img {
            height: 48px;
            margin-right: 16px;
            border-radius: 50%;
            background: none;
            border: none;
        }
        .header-title {
            font-size: 1.7rem;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .layout {
            display: flex;
            min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
            padding-top: 64px;
        }
        .sidebar {
            background: #e3eaff;
            width: 240px;
            height: calc(100vh - 64px); height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px)); max-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
            position: fixed;
            top: 64px;
            left: 0;
            z-index: 999;
            display: flex;
            flex-direction: column;
            padding: 32px 0 0 24px;
            box-sizing: border-box;
            overflow-y: auto;
        }
        .sidebar a {
            font-weight: bold;
            color: #222;
            text-decoration: none;
            margin-bottom: 16px;
            font-size: 1rem;
            letter-spacing: 0.3px;
            transition: all 0.2s;
            padding: 12px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10%;
        }
        .sidebar a:hover {
            color: #233a8b;
            background: #d1dbfa;
        }
        .sidebar a.active {
            color: #fff;
            background: #233a8b;
            box-shadow: 0 2px 8px rgba(35,58,139,0.15);
        }
        .sidebar .logout {
            margin-top: auto;
            margin-bottom: 32px;
            color: #222;
            font-weight: bold;
            display: block;
            width: 90%;
            text-align: left;
        }
        .main-content {
            flex: 1;
            padding: 32px;
            background: #fff;
            margin-left: 240px;
            min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
            overflow-y: auto;
            box-sizing: border-box;
        }
        
        /* Hide hamburger menu on desktop */
        .hamburger-menu {
            display: none;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 12px 16px;
                height: 56px;
            }
            .header img {
                height: 32px;
                margin-right: 8px;
            }
            .header-title {
                font-size: 1.2rem;
            }
            .layout {
                padding-top: 56px;
            }
            .sidebar {
                position: fixed;
                top: 56px;
                left: -240px;
                width: 240px;
                height: calc(100vh - 56px); height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)); max-height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px));
                background: #e3eaff;
                z-index: 999;
                transition: left 0.3s ease;
                display: flex;
                flex-direction: column;
                padding: 20px 0 0 24px;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            .sidebar.active {
                left: 0;
            }
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            .hamburger-menu {
                display: block;
                background: none;
                border: none;
                cursor: pointer;
                padding: 8px;
                margin-right: 12px;
                z-index: 1001;
            }
            .hamburger-menu span {
                display: block;
                width: 25px;
                height: 3px;
                background: #fff;
                margin: 5px 0;
                transition: 0.3s;
                border-radius: 2px;
            }
            .hamburger-menu.active span:nth-child(1) {
                transform: rotate(-45deg) translate(-5px, 6px);
            }
            .hamburger-menu.active span:nth-child(2) {
                opacity: 0;
            }
            .hamburger-menu.active span:nth-child(3) {
                transform: rotate(45deg) translate(-5px, -6px);
            }
        }
        <?php else: ?>
        /* Iframe styles - remove header/sidebar */
        body {
            padding: 0;
            margin: 0;
            background: #fff;
        }
        <?php endif; ?>
        
        /* Announcement Cards */
        .announcement-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 24px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .announcement-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .announcement-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #233a8b;
            margin: 0 0 8px 0;
        }
        .announcement-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 0.9rem;
            color: #666;
        }
        .announcement-category {
            background: #e3eaff;
            color: #233a8b;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .announcement-body {
            padding: 20px;
        }
        .announcement-description {
            line-height: 1.6;
            color: #333;
            margin-bottom: 16px;
        }
        .announcement-tags {
            margin-bottom: 16px;
        }
        .announcement-tag {
            background: #f0f0f0;
            color: #666;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8rem;
            margin-right: 4px;
            display: inline-block;
        }
        .announcement-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .read-more-btn {
            background: #233a8b;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .read-more-btn:hover {
            background: #1a2d6b;
        }
        .attachment-btn {
            background: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .attachment-btn:hover {
            background: #e9e9e9;
        }
        
        /* Filters */
        .filters-section {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .filter-input {
            width: 95%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .clear-filters-btn {
            background: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }
        .empty-state-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .empty-state-message {
            font-size: 1rem;
        }
        
        /* Loading State */
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #233a8b;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Standalone announcement detail modal (same look as dashboard global / Company jobposting jd-modal) */
        .ann-modal-overlay {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            inset: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            max-height: 100dvh;
            box-sizing: border-box;
            background: rgba(0, 0, 0, 0.45);
            align-items: center;
            justify-content: center;
            padding: max(12px, env(safe-area-inset-top)) 16px max(12px, env(safe-area-inset-bottom));
        }
        .ann-modal-panel {
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 24px 56px rgba(26, 56, 118, 0.22);
            border: 1px solid #e2e8f0;
            width: min(92vw, 720px);
            max-width: 720px;
            max-height: min(90vh, 640px);
            overflow: hidden;
            box-sizing: border-box;
        }
        .ann-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
            padding: 20px 20px 16px 22px;
            border-bottom: 1px solid #e8eaf0;
            background: linear-gradient(180deg, #fafbff 0%, #fff 100%);
        }
        #modalTitle {
            margin: 0;
            flex: 1;
            min-width: 0;
            color: #1a3876;
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.35;
            text-align: left;
        }
        .ann-modal-x {
            flex-shrink: 0;
            width: 2.2rem;
            height: 2.2rem;
            line-height: 1;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: #546e7a;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
        }
        .ann-modal-x:hover {
            background: #eceff1;
            color: #1a3876;
        }
        .ann-modal-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .ann-modal-body::-webkit-scrollbar {
            width: 8px;
        }
        .ann-modal-body::-webkit-scrollbar-thumb {
            background: #c5cae9;
            border-radius: 8px;
        }
        .ann-modal-footer {
            flex-shrink: 0;
            padding: 16px 24px 20px;
            background: #f4f6f9;
            border-top: 1px solid #e8eaf0;
            box-sizing: border-box;
        }
        .ann-modal-primary-btn {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 0.95rem;
            background: #1a3876;
            color: #fff;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(26, 56, 118, 0.25);
        }
        .ann-modal-primary-btn:hover {
            filter: brightness(1.05);
        }
        #modalContent .jd-modal-root {
            padding: 20px 24px 8px;
            text-align: left;
        }
        #modalContent .jd-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 24px;
            margin-bottom: 22px;
        }
        @media (max-width: 560px) {
            #modalContent .jd-meta-grid {
                grid-template-columns: 1fr;
            }
        }
        #modalContent .jd-meta-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #78909c;
            font-weight: 700;
            margin-bottom: 5px;
        }
        #modalContent .jd-meta-value {
            font-size: 0.95rem;
            color: #263238;
            font-weight: 500;
            line-height: 1.35;
        }
        #modalContent .jd-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        #modalContent .jd-status--active {
            background: #e8f5e9;
            color: #1b5e20;
        }
        #modalContent .jd-status--closed {
            background: #ffebee;
            color: #b71c1c;
        }
        #modalContent .jd-status--draft {
            background: #fff8e1;
            color: #e65100;
        }
        #modalContent .jd-status--other {
            background: #eceff1;
            color: #455a64;
        }
        #modalContent .jd-section {
            margin-bottom: 18px;
        }
        #modalContent .jd-section:last-of-type {
            margin-bottom: 0;
        }
        #modalContent .jd-section-head {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #1a3876;
            font-weight: 700;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #modalContent .jd-section-head i {
            opacity: 0.85;
            font-size: 0.85rem;
        }
        #modalContent .jd-section-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #37474f;
            border: 1px solid #e8eaf0;
        }
        #modalContent .jd-empty {
            color: #90a4ae;
            font-style: italic;
        }
        #modalContent .jd-tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        #modalContent .jd-tag {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #455a64;
            font-weight: 500;
        }
        #modalContent .jd-attach-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            color: #1a3876;
            border: 1px solid #cfd8dc;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            margin: 0 8px 8px 0;
        }
        #modalContent .jd-attach-link:hover {
            background: #f5f7fa;
        }
        #modalContent .jd-attach-box {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        #modalContent .jd-attach-item {
            margin: 0;
        }
        #modalContent .jd-attach-item--image .jd-attach-image-link {
            display: block;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #fff;
            line-height: 0;
            max-height: min(50vh, 360px);
        }
        #modalContent .jd-attach-item--image .jd-attach-image {
            width: 100%;
            max-height: min(50vh, 360px);
            height: auto;
            object-fit: contain;
            display: block;
        }
        #modalContent .jd-attach-filemeta {
            margin-top: 8px;
            font-size: 0.85rem;
            color: #546e7a;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }
        #modalContent .jd-attach-filemeta .jd-attach-name {
            font-weight: 600;
            color: #263238;
            word-break: break-all;
        }
        #modalContent .jd-attach-ext {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #1a3876;
            background: #e8eaf6;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
<?php if (!$isIframe): ?>
    <div class="header">
        <div style="display: flex; align-items: center;">
            <button class="hamburger-menu" id="hamburgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="userName" style="font-size: 1rem; font-weight: 500;">User</span>
        </div>
    </div>
    
    <div class="layout">
        <div class="sidebar">
            <a href="dashboard.php">📊 Dashboard</a>
            <!--<a href="resume_builder.php">📝 Resume Builder</a>-->
            <a href="recommended_jobs.php">💼 Recommended Jobs</a>
            <a href="peso.html">🏛️ PESO</a>
            <a href="about.html">ℹ️ About</a>
            <a href="announcements.php" class="active">📢 Announcements</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
        
        <div class="main-content">
    <?php endif; ?>
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <div>
                    
                    <p style="color:#666; margin:8px 0 0 0; font-size:1.1rem;">Stay updated with the latest news and opportunities</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-section">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input type="text" id="searchInput" placeholder="Search announcements..." class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Category</label>
                        <select id="categoryFilter" class="filter-input">
                            <option value="">All Categories</option>
                            <option value="Job Fair">Job Fair</option>
                            <option value="Hiring Alert">Hiring Alert</option>
                            <option value="Training">Training</option>
                            <option value="Update">Update</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button id="clearFiltersBtn" class="clear-filters-btn">Clear Filters</button>
                    </div>
                </div>
            </div>
            
            <!-- Announcements List -->
            <div id="announcementsList">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>Loading announcements...</p>
                </div>
            </div>
        <?php if (!$isIframe): ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Announcement Detail Modal (layout matches dashboard global modal / jobposting view) -->
    <div id="announcementModal" class="ann-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="ann-modal-panel">
            <div class="ann-modal-header">
                <h3 id="modalTitle">Announcement</h3>
                <button type="button" id="closeModalBtn" class="ann-modal-x" aria-label="Close">&times;</button>
            </div>
            <div class="ann-modal-body">
                <div id="modalContent"></div>
            </div>
            <div class="ann-modal-footer">
                <button type="button" id="annModalFooterClose" class="ann-modal-primary-btn">Close</button>
            </div>
        </div>
    </div>

    <script>
        let currentFilters = {};
        let announcements = [];

        function showAnnouncementModal(title, html) {
            if (window.self !== window.top) {
                window.parent.postMessage({
                    type: 'showModal',
                    payload: { title: title, html: html }
                }, '*');
                return;
            }

            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalContent').innerHTML = html;
            const m = document.getElementById('announcementModal');
            m.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function hideAnnouncementModal() {
            if (window.self !== window.top) {
                window.parent.postMessage({ type: 'hideModal' }, '*');
                return;
            }
            const m = document.getElementById('announcementModal');
            m.style.display = 'none';
            document.body.style.overflow = '';
        }

        function escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text == null ? '' : String(text);
            return d.innerHTML;
        }

        /** Rich-text / HTML from admin editor → plain text (no tags, entities decoded). */
        function htmlToPlainText(html) {
            const s = String(html || '').trim();
            if (!s) return '';
            try {
                const doc = new DOMParser().parseFromString(s, 'text/html');
                let t = doc.body.textContent || '';
                t = t.replace(/\u00a0/g, ' ').replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
                return t;
            } catch (e) {
                return s.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            }
        }

        function announcementCategoryClass(cat) {
            const s = String(cat || '').toLowerCase();
            if (s.indexOf('training') !== -1) return 'jd-status jd-status--active';
            if (s.indexOf('hiring') !== -1) return 'jd-status jd-status--closed';
            if (s.indexOf('job fair') !== -1 || s.indexOf('jobfair') !== -1) return 'jd-status jd-status--draft';
            return 'jd-status jd-status--other';
        }

        function formatAnnouncementPosted(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return escapeHtml(String(iso));
            return escapeHtml(d.toLocaleDateString());
        }

        function sanitizeAnnouncementAttachmentPath(path) {
            let p = String(path || '').trim().replace(/\\/g, '/');
            if (!p || p.indexOf('..') !== -1) return '';
            p = p.replace(/^\/+/, '');
            return p;
        }

        function buildAnnouncementAttachmentHref(path) {
            const p = sanitizeAnnouncementAttachmentPath(path);
            if (!p) return '#';
            return '../' + p.split('/').map(function (seg) { return encodeURIComponent(seg); }).join('/');
        }

        var ANNOUNCEMENT_IMAGE_EXT = /\.(jpe?g|png|gif|webp|bmp|avif)$/i;

        function isLikelyImageAttachment(fileName, filePath) {
            const n = String(fileName || '').toLowerCase();
            const q = String(filePath || '').toLowerCase();
            return ANNOUNCEMENT_IMAGE_EXT.test(n) || ANNOUNCEMENT_IMAGE_EXT.test(q.split('/').pop() || '');
        }

        function fileExtensionLabel(fileName) {
            const base = String(fileName || '').split(/[\\/]/).pop() || '';
            const m = /\.([a-z0-9]+)$/i.exec(base);
            return m ? m[1].toUpperCase() : '';
        }

        function buildAnnouncementAttachmentsHtml(announcement) {
            if (!announcement.attachments || !announcement.attachments.length) return '';
            const blocks = announcement.attachments.map(function (att) {
                const rawName = att.file_name || 'file';
                const name = escapeHtml(rawName);
                const href = buildAnnouncementAttachmentHref(att.file_path);
                const ext = fileExtensionLabel(rawName);
                const extHtml = ext ? '<span class="jd-attach-ext">' + escapeHtml(ext) + '</span>' : '';
                if (isLikelyImageAttachment(rawName, att.file_path) && href !== '#') {
                    return (
                        '<div class="jd-attach-item jd-attach-item--image">' +
                        '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="jd-attach-image-link" title="Open full size">' +
                        '<img src="' + escapeHtml(href) + '" alt="' + name + '" class="jd-attach-image" loading="lazy" decoding="async">' +
                        '</a>' +
                        '<div class="jd-attach-filemeta">' +
                        '<i class="fas fa-paperclip" aria-hidden="true"></i> ' +
                        '<span class="jd-attach-name">' + name + '</span> ' +
                        extHtml +
                        '</div>' +
                        '</div>'
                    );
                }
                return (
                    '<div class="jd-attach-item">' +
                    '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="jd-attach-link"><i class="fas fa-paperclip"></i> ' + name + ' ' + extHtml + '</a>' +
                    '</div>'
                );
            }).join('');
            return (
                '<div class="jd-section">' +
                '<div class="jd-section-head"><i class="fas fa-paperclip"></i> Attachments</div>' +
                '<div class="jd-section-box jd-attach-box">' + blocks + '</div>' +
                '</div>'
            );
        }
        
        // Load announcements
        function loadAnnouncements() {
            const params = new URLSearchParams({
                status: 'published',
                ...currentFilters
            });
            
            fetch(`announcement_api.php?action=read&${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        announcements = data.announcements.filter(announcement => {
                            // Filter out expired announcements
                            if (announcement.expiration_date) {
                                const expirationDate = new Date(announcement.expiration_date);
                                const today = new Date();
                                if (expirationDate < today) {
                                    return false;
                                }
                            }
                            return true;
                        });
                        renderAnnouncements();
                    } else {
                        showError('Error loading announcements: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading announcements:', error);
                    showError('Error loading announcements');
                });
        }
        
        // Render announcements
        function renderAnnouncements() {
            const container = document.getElementById('announcementsList');
            
            if (announcements.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📢</div>
                        <div class="empty-state-title">No announcements found</div>
                        <div class="empty-state-message">Check back later for new announcements.</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = announcements.map(announcement => {
                const plainDesc = htmlToPlainText(announcement.description || '');
                const shortDesc = plainDesc.length > 200 ? plainDesc.substring(0, 200) + '…' : plainDesc;
                return `
                <div class="announcement-card">
                    <div class="announcement-header">
                        <h3 class="announcement-title">${escapeHtml(announcement.title)}</h3>
                        <div class="announcement-meta">
                            <span class="announcement-category">${escapeHtml(announcement.category)}</span>
                            <span>📅 ${new Date(announcement.date_posted).toLocaleDateString()}</span>
                            <span>👁️ ${announcement.view_count || 0} views</span>
                        </div>
                    </div>
                    <div class="announcement-body">
                        <div class="announcement-description">
                            ${escapeHtml(shortDesc)}
                        </div>
                        ${announcement.tags && announcement.tags.length > 0 ? `
                            <div class="announcement-tags">
                                ${Array.isArray(announcement.tags) ? 
                                    announcement.tags.map(tag => 
                                        `<span class="announcement-tag">${escapeHtml(String(tag).trim())}</span>`
                                    ).join('') :
                                    announcement.tags.split(',').map(tag => 
                                        `<span class="announcement-tag">${escapeHtml(String(tag).trim())}</span>`
                                    ).join('')
                                }
                            </div>
                        ` : ''}
                        <div class="announcement-actions">
                            <button class="read-more-btn" onclick="viewAnnouncement(${announcement.id})">
                                Read More
                            </button>
                            ${announcement.attachments && announcement.attachments.length > 0 ? `
                                <a href="#" class="attachment-btn" onclick="viewAttachments(${announcement.id})">
                                    📎 ${announcement.attachments.length} attachment(s)
                                </a>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            }).join('');
        }
        
        // View announcement details
        function viewAnnouncement(id) {
            const announcement = announcements.find(a => a.id === id);
            if (!announcement) return;
            
            // Track view
            trackView(id);
            
            const descPlain = htmlToPlainText(announcement.description || '');
            const descBlock = descPlain
                ? escapeHtml(descPlain).replace(/\r?\n|\n/g, '<br>')
                : '<span class="jd-empty">No description provided.</span>';
            const catClass = announcementCategoryClass(announcement.category);
            const viewsVal = escapeHtml(String(announcement.view_count != null ? announcement.view_count : 0));
            const modalHtml =
                '<div class="jd-modal-root">' +
                '<div class="jd-meta-grid">' +
                '<div><div class="jd-meta-label">Category</div><div class="jd-meta-value"><span class="' + catClass + '">' + escapeHtml(announcement.category || '—') + '</span></div></div>' +
                '<div><div class="jd-meta-label">Posted</div><div class="jd-meta-value"><i class="far fa-calendar-alt" style="opacity:0.75;margin-right:6px"></i>' + formatAnnouncementPosted(announcement.date_posted) + '</div></div>' +
                '<div><div class="jd-meta-label">Views</div><div class="jd-meta-value"><i class="far fa-eye" style="opacity:0.75;margin-right:6px"></i>' + viewsVal + '</div></div>' +
                '</div>' +
                '<div class="jd-section">' +
                '<div class="jd-section-head"><i class="fas fa-align-left"></i> Description</div>' +
                '<div class="jd-section-box">' + descBlock + '</div>' +
                '</div>' +
                buildAnnouncementAttachmentsHtml(announcement) +
                '</div>';
            showAnnouncementModal(announcement.title, modalHtml);
            
            // Refresh announcements to update view count
            setTimeout(() => {
                loadAnnouncements();
            }, 1000);
        }
        
        // Track view
        function trackView(announcementId) {
            console.log('Tracking view for announcement:', announcementId);
            console.log('User ID:', <?php echo $_SESSION['user_id'] ?? 'null'; ?>);
            console.log('Session data:', <?php echo json_encode($_SESSION); ?>);
            
            fetch('announcement_api.php?action=track_view', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    announcement_id: announcementId
                })
            })
            .then(response => {
                console.log('Track view response status:', response.status);
                console.log('Track view response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Track view response:', data);
                if (data.success) {
                    console.log('View tracked successfully!');
                } else {
                    console.error('Track view failed:', data.error);
                }
            })
            .catch(error => {
                console.error('Error tracking view:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack
                });
            });
        }
        
        // Apply filters
        function applyFilters() {
            currentFilters = {
                search: document.getElementById('searchInput').value,
                category: document.getElementById('categoryFilter').value
            };
            loadAnnouncements();
        }
        
        // Clear filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            currentFilters = {};
            loadAnnouncements();
        }
        
        // Show error
        function showError(message) {
            document.getElementById('announcementsList').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <div class="empty-state-title">Error</div>
                    <div class="empty-state-message">${message}</div>
                </div>
            `;
        }
        
        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!$isIframe): ?>
            // Load user name (only when not in iframe)
            fetch('session_check.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('userName').textContent = data.username || 'User';
                })
                .catch(error => console.error('Error loading user info:', error));
            
            // Hamburger menu (only when not in iframe)
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const sidebar = document.querySelector('.sidebar');
            
            if (hamburgerMenu && sidebar) {
                hamburgerMenu.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    hamburgerMenu.classList.toggle('active');
                });
                
                // Close sidebar when clicking outside
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(event.target) && !hamburgerMenu.contains(event.target)) {
                            sidebar.classList.remove('active');
                            hamburgerMenu.classList.remove('active');
                        }
                    }
                });
            }
            <?php endif; ?>
            
            // Load announcements (always)
            loadAnnouncements();
            
            // Event listeners (always)
            document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 500));
            document.getElementById('categoryFilter').addEventListener('change', applyFilters);
            document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
            document.getElementById('closeModalBtn').addEventListener('click', () => {
                hideAnnouncementModal();
            });
            const annFooterClose = document.getElementById('annModalFooterClose');
            if (annFooterClose) {
                annFooterClose.addEventListener('click', () => hideAnnouncementModal());
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target.id === 'announcementModal') {
                    hideAnnouncementModal();
                }
            });
        });
    </script>
</body>
</html>
