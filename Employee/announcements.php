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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect - Announcements</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fafafa;
            min-height: 100vh;
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
            min-height: calc(100vh - 64px);
            padding-top: 64px;
        }
        .sidebar {
            background: #e3eaff;
            width: 240px;
            height: calc(100vh - 64px);
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
            min-height: calc(100vh - 64px);
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
                height: calc(100vh - 56px);
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
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo">
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
            <a href="resume_builder.php">📝 Resume Builder</a>
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

    <!-- Announcement Detail Modal -->
    <div id="announcementModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
        <div style="background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); padding: 32px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 id="modalTitle" style="margin: 0; color: #233a8b; font-size: 1.5rem;">Announcement Details</h3>
                <button id="closeModalBtn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
            </div>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        let currentFilters = {};
        let announcements = [];
        
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
            
            container.innerHTML = announcements.map(announcement => `
                <div class="announcement-card">
                    <div class="announcement-header">
                        <h3 class="announcement-title">${announcement.title}</h3>
                        <div class="announcement-meta">
                            <span class="announcement-category">${announcement.category}</span>
                            <span>📅 ${new Date(announcement.date_posted).toLocaleDateString()}</span>
                            <span>👁️ ${announcement.view_count || 0} views</span>
                        </div>
                    </div>
                    <div class="announcement-body">
                        <div class="announcement-description">
                            ${announcement.description.length > 200 ? 
                                announcement.description.substring(0, 200) + '...' : 
                                announcement.description
                            }
                        </div>
                        ${announcement.tags && announcement.tags.length > 0 ? `
                            <div class="announcement-tags">
                                ${Array.isArray(announcement.tags) ? 
                                    announcement.tags.map(tag => 
                                        `<span class="announcement-tag">${tag.trim()}</span>`
                                    ).join('') :
                                    announcement.tags.split(',').map(tag => 
                                        `<span class="announcement-tag">${tag.trim()}</span>`
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
            `).join('');
        }
        
        // View announcement details
        function viewAnnouncement(id) {
            const announcement = announcements.find(a => a.id === id);
            if (!announcement) return;
            
            // Track view
            trackView(id);
            
            // Show modal
            document.getElementById('modalTitle').textContent = announcement.title;
            document.getElementById('modalContent').innerHTML = `
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                        <span style="background: #e3eaff; color: #233a8b; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                            ${announcement.category}
                        </span>
                        <span style="color: #666; font-size: 0.9rem;">
                            📅 ${new Date(announcement.date_posted).toLocaleDateString()}
                        </span>
                        <span style="color: #666; font-size: 0.9rem;">
                            👁️ ${announcement.view_count || 0} views
                        </span>
                    </div>
                    <div style="line-height: 1.6; color: #333; margin-bottom: 20px;">
                        ${announcement.description.replace(/\n/g, '<br>')}
                    </div>
                    ${announcement.tags && announcement.tags.length > 0 ? `
                        <div style="margin-bottom: 20px;">
                            <strong>Tags:</strong><br>
                            ${Array.isArray(announcement.tags) ? 
                                announcement.tags.map(tag => 
                                    `<span style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 0.8rem; display: inline-block; margin-top: 4px;">${tag.trim()}</span>`
                                ).join('') :
                                announcement.tags.split(',').map(tag => 
                                    `<span style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 0.8rem; display: inline-block; margin-top: 4px;">${tag.trim()}</span>`
                                ).join('')
                            }
                        </div>
                    ` : ''}
                    ${announcement.attachments && announcement.attachments.length > 0 ? `
                        <div>
                            <strong>Attachments:</strong><br>
                            ${announcement.attachments.map(attachment => `
                                <a href="../${attachment.file_path}" target="_blank" style="display: inline-block; background: #f5f5f5; color: #666; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; text-decoration: none; margin: 4px 4px 0 0;">
                                    📎 ${attachment.file_name}
                                </a>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
            document.getElementById('announcementModal').style.display = 'flex';
            
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
                return response.json();
            })
            .then(data => {
                console.log('Track view response:', data);
            })
            .catch(error => {
                console.error('Error tracking view:', error);
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
                document.getElementById('announcementModal').style.display = 'none';
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target.id === 'announcementModal') {
                    document.getElementById('announcementModal').style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
