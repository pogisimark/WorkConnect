<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_check.php';
require_once 'db.php';

// Get user applications
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM jobseeker WHERE user_id = ? ORDER BY submission_year DESC, submission_month DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get application counts by status
$stmt = $conn->prepare("SELECT application_status, COUNT(*) as count FROM jobseeker WHERE user_id = ? GROUP BY application_status");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$status_counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$status_counts_assoc = [];
foreach ($status_counts as $status) {
    $status_counts_assoc[$status['application_status']] = $status['count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-right: 20px;
    }
    
    .profile-icon {
        font-size: 24px;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: background-color 0.3s;
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .profile-icon:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .profile-dropdown {
        position: absolute;
        top: 60px;
        right: 200px;
        width: 200px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        overflow: hidden;
    }
    
    .profile-dropdown-item {
        padding: 15px 20px;
        cursor: pointer;
        transition: background-color 0.2s;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .profile-dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .profile-dropdown-item:last-child {
        border-bottom: none;
    }
    
    .profile-dropdown-item.logout {
        color: #f44336;
    }
    
    .profile-dropdown-item.logout:hover {
        background-color: #ffebee;
    }
    
    .notification-container {
        position: relative;
        margin-right: 20px;
    }
    
    .notification-icon {
        font-size: 24px;
        cursor: pointer;
        position: relative;
        padding: 8px;
        border-radius: 50%;
        transition: background-color 0.3s;
    }
    
    .notification-icon:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: #f44336;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .notification-dropdown {
        position: absolute;
        top: 60px;
        right: 20px;
        width: 350px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .notification-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notification-header h3 {
        margin: 0;
        color: #333;
    }
    
    .mark-all-read {
        background: #1976d2;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .notification-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .notification-item {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    
    .notification-item.unread {
        background-color: #e3f2fd;
        border-left: 4px solid #1976d2;
    }
    
    .notification-title {
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }
    
    .notification-message {
        color: #666;
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .notification-time {
        color: #999;
        font-size: 12px;
    }
    
    .no-notifications {
        padding: 20px;
        text-align: center;
        color: #666;
    }
    
    /* Fixed layout styles */
    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        overflow: auto;
    }
    
    html {
        overflow: auto;
    }
    
    .dashboard-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
    }
    
    .dashboard-container {
        display: flex;
        height: 100vh;
        padding-top: 100px; /* Increased to account for header height */
    }
    
    .sidebar {
        position: fixed;
        left: 0;
        top: 8.5%; /* Increased to account for header height */
        width: 250px;
        height: calc(100vh - 100px);
        z-index: 999;
        background: #f8f9fa;
        border-right: 1px solid #e0e0e0;
    }
    
    .main-content {
        margin-left: 250px;
        flex: 1;
        overflow: visible;
        height: auto;
        min-height: calc(100vh - 100px);
        padding: 20px;
        position: relative;
        padding-bottom: 50px;
    }
    
    #apply-section {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 100%;
    }
    
    #apply-iframe {
        border: none;
        display: block;
        height: 1200px;
        width: 100%;
        max-width: 100%;
    }
    
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="logo-brand">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="brand">WorkConnect</span>
        </div>
        <div class="user-info">
            <div class="user-profile">
                <div class="profile-icon" onclick="toggleProfileMenu()">
                    👤
                </div>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['firstname']); ?> </span>
            </div>
            <div class="notification-container">
                <div class="notification-icon" onclick="toggleNotifications()">
                    🔔
                    <span id="notificationBadge" class="notification-badge" style="display:none;">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Dropdown -->
    <div id="profileDropdown" class="profile-dropdown" style="display:none;">
        <div class="profile-dropdown-item" onclick="showSection('profile')">
            👤 Profile
        </div>
        <div class="profile-dropdown-item logout" onclick="showLogoutModal()">
            🚪 Logout
        </div>
    </div>

    <!-- Notification Dropdown -->
    <div id="notificationDropdown" class="notification-dropdown" style="display:none;">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button onclick="markAllAsRead()" class="mark-all-read">Mark all as read</button>
        </div>
        <div id="notificationList" class="notification-list">
            <!-- Notifications will be loaded here -->
        </div>
    </div>

    <div class="dashboard-container">
        <div class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="#dashboard" class="active" onclick="showSection('dashboard')">Dashboard</a></li>
                <li><a href="#apply" onclick="showSection('apply')">Apply for Job</a></li>
                <li><a href="#profile" onclick="showSection('profile')">Profile</a></li>
                <li><a href="#" onclick="showLogoutModal()">Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section"> 
                <div class="welcome-card">
                    <h1>Welcome to WorkConnect, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</h1>
                    <p>Track your job applications and manage your profile</p>
                </div>

                <div class="application-status">
                    <div class="status-card">
                        <h3>Application Status</h3>
                        <div style="margin-bottom: 15px;">
                            <span class="status-badge status-pending">Pending: <?php echo $status_counts_assoc['Pending'] ?? 0; ?></span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <span class="status-badge status-accepted">Accepted: <?php echo $status_counts_assoc['Accepted'] ?? 0; ?></span>
                        </div>
                        <div>
                            <span class="status-badge status-rejected">Rejected: <?php echo $status_counts_assoc['Rejected'] ?? 0; ?></span>
                        </div>
                    </div>

                    <div class="status-card">
                        <h3>Recent Applications</h3>
                        <?php if (empty($applications)): ?>
                            <div class="no-applications">
                                <h3>No Applications Yet</h3>
                                <p>You haven't submitted any job applications yet.</p>
                                <button class="apply-now-btn" onclick="showSection('apply')">Apply Now</button>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($applications, 0, 3) as $app): ?>
                                <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?></strong>
                                            
                                            <small style="color: #666;">Submitted: <?php echo date('M Y', mktime(0, 0, 0, $app['submission_month'], 1, $app['submission_year'])); ?></small>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($app['application_status']); ?>">
                                            <?php echo htmlspecialchars($app['application_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-section">
                    <h2 class="section-title">Profile Summary</h2>
                    <div class="profile-summary">
                        <div class="profile-item">
                            <h4>Name</h4>
                            <p><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></p>
                        </div>
                        <div class="profile-item">
                            <h4>Email</h4>
                            <p><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                        </div>
                        <div class="profile-item">
                            <h4>Total Applications</h4>
                            <p><?php echo count($applications); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Apply Section -->
            <div id="apply-section"  style="display: none;"> 
                <h2 class="section-title">Job Application Form</h2>
                <iframe id="apply-iframe" src="apply.html" width="100%" height="1200px" frameborder="0" style="border-radius: 8px; border: none;"></iframe>
            </div>

            <!-- Profile Section -->
            <div id="profile-section" class="content-section" style="display: none;">
                <h2 class="section-title">Profile Information</h2>
                <div class="profile-summary">
                    <div class="profile-item">
                        <h4>First Name</h4>
                        <p><?php echo htmlspecialchars($_SESSION['firstname']); ?></p>
                    </div>
                    <div class="profile-item">
                        <h4>Last Name</h4>
                        <p><?php echo htmlspecialchars($_SESSION['lastname']); ?></p>
                    </div>
                    <div class="profile-item">
                        <h4>Email Address</h4>
                        <p><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    </div>
                    <div class="profile-item">
                        <h4>Account Created</h4>
                        <p><?php echo date('F j, Y'); ?></p>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h3 style="color: #1a3876; margin-bottom: 15px;">All Applications</h3>
                    <?php if (empty($applications)): ?>
                        <p style="color: #666;">No applications submitted yet.</p>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <div style="margin-bottom: 15px; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #1a3876;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?></strong>
                                       
                                        <small style="color: #666;">
                                            Submitted: <?php echo date('M j, Y', mktime(0, 0, 0, $app['submission_month'], 1, $app['submission_year'])); ?>
                                            <?php if ($app['occupation1']): ?>
                                                | Position: <?php echo htmlspecialchars($app['occupation1']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="status-badge status-<?php echo strtolower($app['application_status']); ?>">
                                        <?php echo htmlspecialchars($app['application_status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSection(section) {
            // Hide all sections
            document.getElementById('dashboard-section').style.display = 'none';
            document.getElementById('apply-section').style.display = 'none';
            document.getElementById('profile-section').style.display = 'none';
            
            // Remove active class from all nav items
            document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
            
            // Show selected section
            document.getElementById(section + '-section').style.display = 'block';
            
            // Add active class to clicked nav item
            event.target.classList.add('active');
        }
        
        function toggleProfileMenu() {
            const dropdown = document.getElementById('profileDropdown');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            // Close notification dropdown if open
            notificationDropdown.style.display = 'none';
            
            if (dropdown.style.display === 'none' || dropdown.style.display === '') {
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
        }
        
        function showLogoutModal() {
            // Close any open dropdowns
            document.getElementById('profileDropdown').style.display = 'none';
            document.getElementById('notificationDropdown').style.display = 'none';
            
            Swal.fire({
                title: 'Logout Confirmation',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading spinner during logout
                    Swal.fire({
                        title: 'Logging out...',
                        text: 'Please wait while we log you out.',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Small delay to show the loading state, then redirect
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 1000);
                }
            });
        }
        
        // Notification functions
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown.style.display === 'none' || dropdown.style.display === '') {
                dropdown.style.display = 'block';
                loadNotifications();
            } else {
                dropdown.style.display = 'none';
            }
        }
        
        function loadNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notificationList = document.getElementById('notificationList');
                    const badge = document.getElementById('notificationBadge');
                    
                    if (data.notifications && data.notifications.length > 0) {
                        let unreadCount = 0;
                        notificationList.innerHTML = '';
                        
                        data.notifications.forEach(notification => {
                            if (!notification.is_read) unreadCount++;
                            
                            const notificationItem = document.createElement('div');
                            notificationItem.className = `notification-item ${!notification.is_read ? 'unread' : ''}`;
                            notificationItem.innerHTML = `
                                <div class="notification-title">${notification.title}</div>
                                <div class="notification-message">${notification.message}</div>
                                <div class="notification-time">${notification.created_at}</div>
                            `;
                            notificationItem.onclick = () => markAsRead(notification.id);
                            notificationList.appendChild(notificationItem);
                        });
                        
                        if (unreadCount > 0) {
                            badge.textContent = unreadCount;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    } else {
                        notificationList.innerHTML = '<div class="no-notifications">No notifications</div>';
                        badge.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                });
        }
        
        function markAsRead(notificationId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            });
        }
        
        function markAllAsRead() {
            fetch('mark_all_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            });
        }
        
        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            // Check for new notifications every 30 seconds
            setInterval(loadNotifications, 30000);
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationIcon = document.querySelector('.notification-icon');
            const profileDropdown = document.getElementById('profileDropdown');
            const profileIcon = document.querySelector('.profile-icon');
            
            if (!notificationDropdown.contains(event.target) && !notificationIcon.contains(event.target)) {
                notificationDropdown.style.display = 'none';
            }
            
            if (!profileDropdown.contains(event.target) && !profileIcon.contains(event.target)) {
                profileDropdown.style.display = 'none';
            }
        });
        
        // Handle iframe communication for SweetAlert and scroll
        window.addEventListener('message', function(event) {
            if (event.data.type === 'showAlert') {
                const { alertType, title, message } = event.data;
                
                const config = {
                    title: title,
                    text: message,
                    confirmButtonColor: '#1976d2'
                };

                switch(alertType) {
                    case 'success':
                        Swal.fire({
                            ...config,
                            icon: 'success',
                            confirmButtonColor: '#4caf50'
                        });
                        break;
                    case 'error':
                        Swal.fire({
                            ...config,
                            icon: 'error',
                            confirmButtonColor: '#f44336'
                        });
                        break;
                    case 'warning':
                        Swal.fire({
                            ...config,
                            icon: 'warning',
                            confirmButtonColor: '#ff9800'   
                        });
                        break;
                    default:
                        Swal.fire(config);
                }
            }
            
            // Handle scroll to top request from iframe (disabled to prevent multiple scrollbars)
            if (event.data.type === 'scrollToTop') {
                // Scroll handling disabled to prevent multiple scrollable areas
                // The iframe handles its own scrolling internally
            }
            
            // Handle scroll to apply section request from iframe
            if (event.data.type === 'scrollToApplySection') {
                // Scroll to the top of the apply section
                const applySection = document.getElementById('apply-section');
                if (applySection) {
                    applySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    </script>
</body>
</html>
