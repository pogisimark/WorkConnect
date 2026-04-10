<?php
include 'session_protect.php';

$showEmployerAddAccountUnauthorized = false;
if (!empty($_SESSION['employer_unauthorized_add_account'])) {
    $showEmployerAddAccountUnauthorized = true;
    unset($_SESSION['employer_unauthorized_add_account']);
}

require_once __DIR__ . '/follow_up_pending_badge.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';
require_once __DIR__ . '/jobseeker_pending_badge.php';
require_once __DIR__ . '/db.php';
$follow_up_pending_count = fu_get_pending_follow_up_count($conn);
$acfu_unread_count = acfu_get_unread_response_count($conn);
$pending_jobseekers_count = js_get_pending_jobseekers_count($conn);
if ($conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fafafa;
            min-height: 100vh; min-height: 100dvh;
            overflow-x: hidden;
            overflow-y: auto;
        }
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
            padding-top: 64px; /* offset for fixed header */
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
            min-width: 0; /* flex child: allow shrink so inner grids don’t overflow viewport */
            padding: 32px;
            background: #fff;
            margin-left: 240px;
            min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
            overflow-y: auto;
            box-sizing: border-box;
        }
        .sidebar a:hover {
            color: #233a8b;
            background: #d1dbfa; 
            border-radius: 8px;   
            padding-left: 10px;   
        }
        .sidebar a.active {
            color: #fff;
            background: #233a8b;
            box-shadow: 0 2px 8px rgba(35,58,139,0.15);
        }
        
        /* Hide hamburger menu on desktop */
        .hamburger-menu {
            display: none;
        }
        
        /* Quick Action Hover Effects */
        .quick-action-link:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
            background: linear-gradient(135deg, #f8fafc, #ffffff) !important;
        }
        
        .quick-action-link:hover .quick-action-icon {
            transform: scale(1.1);
        }
        
        .quick-action-link:hover .quick-action-title {
            color: #1976d2 !important;
        }
        
        .quick-action-link:hover .quick-action-desc {
            color: #555 !important;
        }
        
        /* Laptop Responsive (1024px - 1366px) */
        @media (max-width: 1366px) and (min-width: 1024px) {
            .header {
                padding: 14px 24px;
                height: 60px;
            }
            .header img {
                height: 42px;
                margin-right: 14px;
            }
            .header-title {
                font-size: 1.6rem;
            }
            .main-content {
                padding: 28px;
            }
            .main-content > div:nth-child(2) {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            .main-content > div:nth-child(3) {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            .main-content > div:nth-child(4) {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }
        
        /* Tablet Responsive (768px - 1023px) */
        @media (max-width: 1023px) and (min-width: 768px) {
            .header {
                padding: 12px 20px;
                height: 58px;
            }
            .header img {
                height: 40px;
                margin-right: 12px;
            }
            .header-title {
                font-size: 1.5rem;
            }
            .main-content {
                padding: 24px;
            }
            .main-content > div:nth-child(2) {
                grid-template-columns: repeat(2, 1fr);
                gap: 18px;
            }
            .main-content > div:nth-child(3) {
                grid-template-columns: repeat(2, 1fr);
                gap: 18px;
            }
            .main-content > div:nth-child(4) {
                grid-template-columns: repeat(2, 1fr);
                gap: 18px;
            }
        }
        
        /* Mobile Hamburger Menu System */
        @media (max-width: 768px) {
            body {
                background: #f8fafc;
                padding: 0;
                margin: 0;
            }
            
            .header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                padding: 12px 16px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
                min-height: 56px;
            }
            
            .header img {
                height: 32px;
                margin-right: 8px;
                flex-shrink: 0;
            }
            
            .header-title {
                font-size: 1.2rem;
                color: #fff;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                flex: 1;
                min-width: 0;
            }
            
            .header div {
                display: flex;
                align-items: center;
                gap: 6px;
                margin-left: 0 !important;
                flex-shrink: 0;
            }
            
            .header div span {
                font-size: 0.75rem;
                color: #fff;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100px;
            }
            
            .header div a {
                background: #ffcb05;
                color: #233a8b;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 0.7rem;
                text-decoration: none;
                white-space: nowrap;
                flex-shrink: 0;
            }
            
            /* Hamburger Menu Button */
            .hamburger-menu {
                display: block !important;
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
            
            .layout {
                flex-direction: column;
                padding-top: 60px;
            }
            
            /* Mobile Sidebar - Hidden by default */
            .sidebar {
                position: fixed !important;
                top: 56px !important;
                left: -240px !important;
                width: 240px !important;
                height: calc(100vh - 56px) !important; height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important; max-height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important;
                background: #e3eaff !important;
                z-index: 999 !important;
                transition: left 0.3s ease !important;
                display: flex !important;
                flex-direction: column !important;
                padding: 20px 0 0 24px !important;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
            }
            
            .sidebar.active {
                left: 0 !important;
            }
            
            .sidebar a {
                display: flex;
                align-items: center;
                padding: 12px 16px;
                text-decoration: none;
                color: #222;
                font-size: 0.9rem;
                font-weight: bold;
                transition: all 0.2s;
                border-radius: 8px;
                margin-bottom: 8px;
                gap: 12px;
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
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: clip;
            }
            
            /* PESO + Quick Tips row: prevent right-edge clip on narrow screens */
            .dashboard-peso-tips-row {
                min-width: 0;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            .dashboard-peso-card,
            .dashboard-tips-card {
                box-sizing: border-box;
                min-width: 0;
                max-width: 100%;
                width: 100%;
                overflow-wrap: break-word;
                word-wrap: break-word;
                padding: 16px 14px !important;
            }
            .dashboard-peso-features {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
            
            .main-content > div.dashboard-page-header,
            .main-content > div:first-child {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
                background: #fff;
                border-radius: 16px;
                padding: 16px 14px;
                margin-bottom: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .main-content > div.dashboard-page-header > div:last-child,
            .main-content > div:first-child > div:last-child {
                align-self: stretch;
            }
            
            .main-content > div.dashboard-datetime-card,
            .main-content > div:first-child > div.dashboard-datetime-card {
                padding: 10px 12px !important;
                border-radius: 10px !important;
            }
            
            .main-content > div.dashboard-datetime-card #currentDate,
            .main-content > div:first-child > div.dashboard-datetime-card #currentDate {
                font-size: 0.88rem !important;
            }
            
            .main-content > div.dashboard-page-header h2,
            .main-content > div:first-child h2 {
                font-size: 1.25rem;
                color: #233a8b;
            }
            
            .main-content > div.dashboard-page-header p,
            .main-content > div:first-child p {
                font-size: 0.85rem;
                color: #666;
            }
            
            /* KPI grid: 2 columns, compact tiles (analytics-style) */
            .main-content > div.dashboard-stat-grid {
                display: grid !important;
                flex-direction: unset !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
                margin-bottom: 14px !important;
            }
            
            .main-content > div:nth-child(2):not(.dashboard-stat-grid) {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 16px;
            }
            
            .main-content > div:nth-child(3) {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 16px;
            }
            
            .main-content > div:nth-child(4) {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 16px;
            }
            
            /* Stack sections vertically on mobile - Quick Tips first, then PESO Mission */
            .main-content > div:last-child {
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
                grid-template-columns: none !important;
            }
            
            .main-content > div:last-child > div:first-child {
                order: 2 !important; /* PESO Mission goes to bottom */
                width: 100% !important;
                max-width: 100% !important;
                flex: none !important;
            }
            
            .main-content > div:last-child > div:last-child {
                order: 1 !important; /* Quick Tips goes to top */
                width: 100% !important;
                max-width: 100% !important;
                flex: none !important;
            }
            
            /* Match the width of other cards on mobile */
            .main-content > div:last-child {
                max-width: 100% !important;
                margin: 0 auto !important;
            }
            
            /* Override any existing grid styles (except KPI stat grid) */
            .main-content > div[style*="grid-template-columns"]:not(.dashboard-stat-grid) {
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
            }
            
            .main-content > div.dashboard-stat-grid > div {
                padding: 12px 10px !important;
                border-radius: 12px !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            }
            
            .main-content > div.dashboard-stat-grid > div > div:first-child {
                margin-bottom: 6px !important;
            }
            
            .main-content > div.dashboard-stat-grid > div > div:first-child > div:first-child {
                font-size: 1.35rem !important;
                line-height: 1 !important;
            }
            
            .main-content > div.dashboard-stat-grid > div > div:first-child > div:last-child {
                font-size: 0.62rem !important;
                padding: 3px 7px !important;
                border-radius: 999px !important;
            }
            
            .main-content > div.dashboard-stat-grid #jobseekersCount,
            .main-content > div.dashboard-stat-grid #skillsCount,
            .main-content > div.dashboard-stat-grid #newApplicantsCount,
            .main-content > div.dashboard-stat-grid #placedCount,
            .main-content > div.dashboard-stat-grid #companiesCount {
                font-size: 1.55rem !important;
                margin-bottom: 2px !important;
                line-height: 1.1 !important;
            }
            
            .main-content > div.dashboard-stat-grid > div > div:nth-child(3) {
                font-size: 0.78rem !important;
                font-weight: 600 !important;
                color: #444 !important;
                line-height: 1.25 !important;
            }
            
            .main-content > div.dashboard-stat-grid > div > div:nth-child(4) {
                display: none !important;
            }
            
            .main-content > div:nth-child(3) > div,
            .main-content > div:nth-child(4) > div {
                background: #fff;
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 8px 12px;
                min-height: 50px;
            }
            
            .header img {
                height: 28px;
                margin-right: 6px;
            }
            
            .header-title {
                font-size: 1rem;
            }
            
            .header div span {
                font-size: 0.7rem;
                max-width: 80px;
            }
            
            .header div a {
                padding: 3px 6px;
                font-size: 0.65rem;
            }
            
            .header div {
                gap: 4px;
            }
            
            .layout {
                padding-top: 50px;
                margin-bottom: 70px;
            }
            
            .sidebar a {
                font-size: 0.8rem;
                padding: 6px 10px;
            }
            
            .sidebar a:before {
                font-size: 1rem;
            }
            
            .main-content {
                padding: 12px;
                max-width: 100%;
                min-width: 0;
            }
            
            .dashboard-peso-card,
            .dashboard-tips-card {
                padding: 14px 12px !important;
            }
            
            .main-content > div.dashboard-page-header,
            .main-content > div:first-child {
                padding: 14px 12px;
                border-radius: 12px;
            }
            
            .main-content > div.dashboard-page-header h2,
            .main-content > div:first-child h2 {
                font-size: 1.15rem;
            }
            
            .main-content > div.dashboard-page-header p,
            .main-content > div:first-child p {
                font-size: 0.82rem;
            }
            
            .main-content > div:nth-child(3) > div,
            .main-content > div:nth-child(4) > div {
                padding: 14px;
                border-radius: 10px;
            }
            
            .main-content > div.dashboard-stat-grid {
                gap: 8px !important;
            }
            
            .main-content > div.dashboard-stat-grid > div {
                padding: 10px 8px !important;
            }
            
            .main-content > div.dashboard-stat-grid #jobseekersCount,
            .main-content > div.dashboard-stat-grid #skillsCount,
            .main-content > div.dashboard-stat-grid #newApplicantsCount,
            .main-content > div.dashboard-stat-grid #placedCount,
            .main-content > div.dashboard-stat-grid #companiesCount {
                font-size: 1.4rem !important;
            }
            
            .main-content > div.dashboard-stat-grid > div > div:nth-child(3) {
                font-size: 0.72rem !important;
            }
        }
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
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="adminUsername" style="font-size: 1rem; font-weight: 500;">Admin</span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="#" class="active"> DASHBOARD</a>
            <a href="job_postings.php"> JOB POSTINGS</a>
            <a href="job.php"> JOBSEEKERS<?php echo js_pending_jobseekers_badge_html($pending_jobseekers_count); ?></a>
            <a href="follow_up_requests.php"> FOLLOW-UP REQUESTS<?php echo fu_follow_up_badge_html($follow_up_pending_count); ?></a>
            <a href="request_follow_up.php"> REQUEST FOLLOW UP<span class="acfu-sidebar-badge"><?php echo acfu_unread_badge_html($acfu_unread_count); ?></span></a>
            <a href="skill.php"> SKILL REGISTRY</a>
            <a href="companies_list.php"> COMPANIES</a>
            <a href="btec.php"> BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
            <a href="analytics.php"> Analytics</a>
            <a href="announcement.php"> ANNOUNCEMENTS</a>
            <a href="audit_logs.php"> 🧾 AUDIT LOGS</a>
            <a href="logout.php" class="logout"> Logout</a>
        </div>
        <div class="main-content">
            <div class="dashboard-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <div>
                    <h2 style="color:#233a8b; font-size:1.8rem; font-weight:bold; margin:0;">PESO Dashboard</h2>
                    <p style="color:#666; margin:8px 0 0 0; font-size:1.1rem;">Public Employment Service Office Management System</p>
                </div>
                <div class="dashboard-datetime-card" style="background: linear-gradient(135deg, #233a8b, #1976d2); color: white; padding: 16px 24px; border-radius: 12px; text-align: center;">
                    <div id="currentDate" style="font-size: 1.1rem; font-weight: 600;"></div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Philippines Time</div>
                </div>
            </div>

            <!-- Key Statistics Cards -->
            <div class="dashboard-stat-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-bottom:32px;">
                <div style="background:linear-gradient(135deg,#e3eaff,#f0f4ff);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(35,58,139,0.08);border-left:4px solid #233a8b;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div style="font-size:2.5rem;">👥</div>
                        <div style="background:#233a8b;color:white;padding:6px 12px;border-radius:20px;font-size:0.85rem;font-weight:600;">REGISTERED</div>
                    </div>
                    <div id="jobseekersCount" style="font-size:2.8rem;font-weight:bold;color:#233a8b;margin-bottom:8px;">...</div>
                    <div style="font-size:1.1rem;color:#555;font-weight:500;">Total Job Seekers</div>
                    <div style="font-size:0.9rem;color:#888;margin-top:4px;">Active Job Seekers in the system</div>
                </div>

                <div style="background:linear-gradient(135deg,#e8f5e8,#f0f8f0);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(34,139,34,0.08);border-left:4px solid #22c55e;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div style="font-size:2.5rem;">🛠️</div>
                        <div style="background:#22c55e;color:white;padding:6px 12px;border-radius:20px;font-size:0.85rem;font-weight:600;">SKILLS</div>
                    </div>
                    <div id="skillsCount" style="font-size:2.8rem;font-weight:bold;color:#22c55e;margin-bottom:8px;">...</div>
                    <div style="font-size:1.1rem;color:#555;font-weight:500;">Skills Registered</div>
                    <div style="font-size:0.9rem;color:#888;margin-top:4px;">Unique skills in the registry</div>
                </div>

                <div style="background:linear-gradient(135deg,#fff3e0,#fff8f0);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(255,152,0,0.08);border-left:4px solid #ff9800;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div style="font-size:2.5rem;">📈</div>
                        <div style="background:#ff9800;color:white;padding:6px 12px;border-radius:20px;font-size:0.85rem;font-weight:600;">THIS WEEK</div>
                    </div>
                    <div id="newApplicantsCount" style="font-size:2.8rem;font-weight:bold;color:#ff9800;margin-bottom:8px;">...</div>
                    <div style="font-size:1.1rem;color:#555;font-weight:500;">New Applicants</div>
                    <div style="font-size:0.9rem;color:#888;margin-top:4px;">Applications received this week</div>
                </div>

                <div style="background:linear-gradient(135deg,#f3e5f5,#faf5ff);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(156,39,176,0.08);border-left:4px solid #9c27b0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div style="font-size:2.5rem;">✅</div>
                        <div style="background:#9c27b0;color:white;padding:6px 12px;border-radius:20px;font-size:0.85rem;font-weight:600;">PLACED</div>
                    </div>
                    <div id="placedCount" style="font-size:2.8rem;font-weight:bold;color:#9c27b0;margin-bottom:8px;">...</div>
                    <div style="font-size:1.1rem;color:#555;font-weight:500;">Successfully Placed</div>
                    <div style="font-size:0.9rem;color:#888;margin-top:4px;">Job seekers placed in employment</div>
                </div>

                <div style="background:linear-gradient(135deg,#e0f2fe,#f0f9ff);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(2,132,199,0.08);border-left:4px solid #0284c7;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div style="font-size:2.5rem;">🏢</div>
                        <div style="background:#0284c7;color:white;padding:6px 12px;border-radius:20px;font-size:0.85rem;font-weight:600;">VERIFIED</div>
                    </div>
                    <div id="companiesCount" style="font-size:2.8rem;font-weight:bold;color:#0284c7;margin-bottom:8px;">...</div>
                    <div style="font-size:1.1rem;color:#555;font-weight:500;">Verified Companies</div>
                    <div style="font-size:0.9rem;color:#888;margin-top:4px;">Email-verified employer accounts</div>
                </div>
            </div>

            <!-- Quick Actions and Recent Activity -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">
                <!-- Quick Actions -->
                <div style="background:#f8fafc;border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(25,118,210,0.08);">
                    <h3 style="color:#233a8b;margin-top:0;margin-bottom:20px;font-size:1.3rem;display:flex;align-items:center;gap:8px;">
                        ⚡ Quick Actions
                    </h3>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <a href="job.php" class="quick-action-link" style="display:flex;align-items:center;gap:12px;padding:16px;background:white;border-radius:12px;text-decoration:none;color:#333;box-shadow:0 2px 8px rgba(0,0,0,0.05);transition:all 0.3s ease;border-left:4px solid #233a8b;cursor:pointer;">
                            <div class="quick-action-icon" style="font-size:1.5rem;transition:transform 0.3s ease;">👥</div>
                            <div>
                                <div class="quick-action-title" style="font-weight:600;color:#233a8b;transition:color 0.3s ease;">Review Job Seekers</div>
                                <div class="quick-action-desc" style="font-size:0.9rem;color:#666;transition:color 0.3s ease;">Manage pending applications</div>
                            </div>
                        </a>
                        <a href="skill.php" class="quick-action-link" style="display:flex;align-items:center;gap:12px;padding:16px;background:white;border-radius:12px;text-decoration:none;color:#333;box-shadow:0 2px 8px rgba(0,0,0,0.05);transition:all 0.3s ease;border-left:4px solid #22c55e;cursor:pointer;">
                            <div class="quick-action-icon" style="font-size:1.5rem;transition:transform 0.3s ease;">🛠️</div>
                            <div>
                                <div class="quick-action-title" style="font-weight:600;color:#22c55e;transition:color 0.3s ease;">Skill Registry</div>
                                <div class="quick-action-desc" style="font-size:0.9rem;color:#666;transition:color 0.3s ease;">View and manage skills database</div>
                            </div>
                        </a>
                        <a href="btec.php" class="quick-action-link" style="display:flex;align-items:center;gap:12px;padding:16px;background:white;border-radius:12px;text-decoration:none;color:#333;box-shadow:0 2px 8px rgba(0,0,0,0.05);transition:all 0.3s ease;border-left:4px solid #ff9800;cursor:pointer;">
                            <div class="quick-action-icon" style="font-size:1.5rem;transition:transform 0.3s ease;">📊</div>
                            <div>
                                <div class="quick-action-title" style="font-weight:600;color:#ff9800;transition:color 0.3s ease;">Generate Reports</div>
                                <div class="quick-action-desc" style="font-size:0.9rem;color:#666;transition:color 0.3s ease;">Create BTEC monthly reports</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- System Status -->
                <div style="background:#f8fafc;border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(25,118,210,0.08);">
                    <h3 style="color:#233a8b;margin-top:0;margin-bottom:20px;font-size:1.3rem;display:flex;align-items:center;gap:8px;">
                        📊 System Overview
                    </h3>
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:white;border-radius:8px;border-left:4px solid #22c55e;">
                            <div>
                                <div style="font-weight:600;color:#333;">Database Status</div>
                                <div style="font-size:0.9rem;color:#666;">Connection active</div>
                            </div>
                            <div style="background:#22c55e;color:white;padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:600;">ONLINE</div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:white;border-radius:8px;border-left:4px solid #22c55e;">
                            <div>
                                <div style="font-weight:600;color:#333;">Email System</div>
                                <div style="font-size:0.9rem;color:#666;">Notifications enabled</div>
                            </div>
                            <div style="background:#22c55e;color:white;padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:600;">ACTIVE</div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:white;border-radius:8px;border-left:4px solid #233a8b;">
                            <div>
                                <div style="font-weight:600;color:#333;">Last Backup</div>
                                <div id="lastBackup" style="font-size:0.9rem;color:#666;">Loading...</div>
                            </div>
                            <div style="background:#233a8b;color:white;padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:600;">RECENT</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESO Information and Tips -->
            <div class="dashboard-peso-tips-row" style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
                <div class="dashboard-peso-card" style="background:linear-gradient(135deg,#e3eaff,#f0f4ff);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(35,58,139,0.08);">
                    <h3 style="color:#233a8b;margin-top:0;margin-bottom:20px;font-size:1.3rem;display:flex;align-items:center;gap:8px;">
                        🏛️ PESO Mission & Services
                    </h3>
                    <div style="font-size:1.05rem;color:#333;line-height:1.6;">
                        <p style="margin-bottom:16px;">The Public Employment Service Office (PESO) serves as the primary employment facilitation unit in your municipality, connecting job seekers with employment opportunities and providing essential labor market services.</p>
                        <div class="dashboard-peso-features" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px;">
                           
                            <div>
                                <h4 style="color:#233a8b;margin:0 0 8px 0;font-size:1.1rem;">Key Features:</h4>
                                <ul style="margin:0;padding-left:20px;font-size:0.95rem;color:#555;">
                                    <li>Digital job application system</li>
                                    <li>Skills registry management</li>
                                    <li>Automated notifications</li>
                                    <li>Comprehensive reporting</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-tips-card" style="background:linear-gradient(135deg,#fff3e0,#fff8f0);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(255,152,0,0.08);">
                    <h3 style="color:#ff9800;margin-top:0;margin-bottom:20px;font-size:1.3rem;display:flex;align-items:center;gap:8px;">
                        💡 Quick Tips
                    </h3>
                    <div style="font-size:0.95rem;color:#333;line-height:1.5;">
                        <div style="background:white;padding:16px;border-radius:12px;margin-bottom:12px;border-left:4px solid #ff9800;">
                            <strong style="color:#ff9800;">📋 Daily Tasks:</strong><br>
                            Review new applications and update applicant status regularly.
                        </div>
                        <div style="background:white;padding:16px;border-radius:12px;margin-bottom:12px;border-left:4px solid #22c55e;">
                            <strong style="color:#22c55e;">📊 Weekly Reports:</strong><br>
                            Generate BTEC reports every Monday for the previous week.
                        </div>
                        <div style="background:white;padding:16px;border-radius:12px;border-left:4px solid #233a8b;">
                            <strong style="color:#233a8b;">🔄 Monthly Review:</strong><br>
                            Analyze skill trends and update the skills registry.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
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
    
<script>
// Hamburger Menu Functionality
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const sidebar = document.querySelector('.sidebar');
    
    // Show hamburger menu on mobile
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            hamburgerMenu.style.display = 'block';
        } else {
            hamburgerMenu.style.display = 'none';
            sidebar.classList.remove('active');
            hamburgerMenu.classList.remove('active');
        }
    }
    
    // Initial check
    checkScreenSize();
    
    // Check on resize
    window.addEventListener('resize', checkScreenSize);
    
    // Mobile header display fix
    function handleMobileHeader() {
        const header = document.getElementById('mainHeader');
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const headerTitle = document.getElementById('headerTitle');
        const adminSection = document.getElementById('adminSection');
        
        if (window.innerWidth <= 768) {
            // Mobile: Ensure header is properly displayed
            header.style.position = 'fixed';
            header.style.top = '0';
            header.style.left = '0';
            header.style.width = '100%';
            header.style.zIndex = '1000';
            header.style.display = 'flex';
            header.style.alignItems = 'center';
            header.style.justifyContent = 'space-between';
            header.style.padding = '12px 20px';
            header.style.height = '64px';
            header.style.boxSizing = 'border-box';
            header.style.maxWidth = '100vw';
            header.style.overflow = 'hidden';
            
            // Show hamburger menu
            hamburgerMenu.style.display = 'block';
            hamburgerMenu.style.visibility = 'visible';
            
            // Adjust title size for mobile - make smaller
            headerTitle.style.fontSize = '0.9rem';
            headerTitle.style.whiteSpace = 'nowrap';
            headerTitle.style.overflow = 'hidden';
            headerTitle.style.textOverflow = 'ellipsis';
            headerTitle.style.maxWidth = '100px';
            
            // Adjust admin section for mobile - make smaller
            adminSection.style.marginRight = '8px';
            adminSection.style.gap = '4px';
            adminSection.style.fontSize = '0.8rem';
            adminSection.style.maxWidth = '120px';
            adminSection.style.overflow = 'hidden';
            adminSection.style.textOverflow = 'ellipsis';
            
            // Update admin text to show only username
            const adminUsername = document.getElementById('adminUsername');
            if (adminUsername) {
                // Username is already set without "Welcome, " prefix
            }
            
            // Ensure logo is visible - make smaller
            const logo = header.querySelector('img');
            if (logo) {
                logo.style.height = '32px';
                logo.style.marginRight = '8px';
            }
            
            // Adjust hamburger menu spacing
            hamburgerMenu.style.marginRight = '8px';
            
        } else {
            // Desktop: Reset to normal
            header.style.position = 'fixed';
            header.style.top = '0';
            header.style.left = '0';
            header.style.width = '100%';
            header.style.zIndex = '1000';
            header.style.display = 'flex';
            header.style.alignItems = 'center';
            header.style.justifyContent = 'space-between';
            header.style.padding = '12px 20px';
            header.style.height = '64px';
            header.style.boxSizing = 'border-box';
            header.style.maxWidth = '100vw';
            
            // Hide hamburger menu on desktop
            hamburgerMenu.style.display = 'none';
            
            // Reset title size
            headerTitle.style.fontSize = '1.7rem';
            headerTitle.style.whiteSpace = 'normal';
            headerTitle.style.overflow = 'visible';
            headerTitle.style.textOverflow = 'unset';
            
            // Reset admin section
            adminSection.style.marginRight = '20px';
            adminSection.style.gap = '8px';
            adminSection.style.fontSize = '1rem';
            
            // Username is already set without "Welcome, " prefix
            const adminUsername = document.getElementById('adminUsername');
            
            // Reset logo
            const logo = header.querySelector('img');
            if (logo) {
                logo.style.height = '48px';
                logo.style.marginRight = '16px';
            }
        }
    }
    
    // Apply mobile styles immediately
    function applyMobileStyles() {
        if (window.innerWidth <= 768) {
            const headerTitle = document.getElementById('headerTitle');
            const adminUsername = document.getElementById('adminUsername');
            const adminSection = document.getElementById('adminSection');
            const logo = document.querySelector('img');
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            
            // Apply inline styles immediately
            if (headerTitle) {
                headerTitle.style.fontSize = '0.9rem';
                headerTitle.style.maxWidth = '100px';
                headerTitle.style.overflow = 'hidden';
                headerTitle.style.textOverflow = 'ellipsis';
                headerTitle.style.whiteSpace = 'nowrap';
            }
            
            if (adminUsername) {
                adminUsername.style.fontSize = '0.8rem';
                adminUsername.style.maxWidth = '120px';
                adminUsername.style.overflow = 'hidden';
                adminUsername.style.textOverflow = 'ellipsis';
                adminUsername.style.whiteSpace = 'nowrap';
            }
            
            if (adminSection) {
                adminSection.style.marginRight = '8px';
                adminSection.style.gap = '4px';
                adminSection.style.maxWidth = '120px';
            }
            
            if (logo) {
                logo.style.height = '32px';
                logo.style.marginRight = '8px';
            }
            
            if (hamburgerMenu) {
                hamburgerMenu.style.display = 'block';
                hamburgerMenu.style.visibility = 'visible';
                hamburgerMenu.style.marginRight = '8px';
            }
        }
    }
    
    // Apply immediately
    applyMobileStyles();
    
    // Initial check
    handleMobileHeader();
    
    // Check on resize
    window.addEventListener('resize', handleMobileHeader);
    
    // Toggle sidebar
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
});

// Check admin session and update UI
fetch('session_check.php')
    .then(r => r.json())
    .then(data => {
        // Update username display
        document.getElementById('adminUsername').textContent = data.username;
        
        // Show/hide ADD ACCOUNT link based on admin type
        if (data.isMainAdmin) {
            document.getElementById('addAccountLink').style.display = 'block';
        } else {
            document.getElementById('addAccountLink').style.display = 'none';
        }
    })
    .catch(() => {
        console.error('Session check failed');
    });

// Update current date and time
function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-PH', options);
}
updateDateTime();
setInterval(updateDateTime, 60000); // Update every minute

// Fetch dashboard stats and update cards
fetch('dashboard_stats.php')
    .then(r => r.json())
    .then(data => {
        document.getElementById('jobseekersCount').textContent = data.total_jobseekers || '0';
        document.getElementById('skillsCount').textContent = data.skills_registered || '0';
        document.getElementById('newApplicantsCount').textContent = data.new_applicants || '0';
        document.getElementById('placedCount').textContent = data.placed_jobseekers || '0';
        const cc = document.getElementById('companiesCount');
        if (cc) cc.textContent = data.total_companies != null ? data.total_companies : '0';
        
        // Update last backup time
        const lastBackup = new Date();
        document.getElementById('lastBackup').textContent = lastBackup.toLocaleDateString('en-PH', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    })
    .catch(() => {
        document.getElementById('jobseekersCount').textContent = '0';
        document.getElementById('skillsCount').textContent = '0';
        document.getElementById('newApplicantsCount').textContent = '0';
        document.getElementById('placedCount').textContent = '0';
        const ccErr = document.getElementById('companiesCount');
        if (ccErr) ccErr.textContent = '0';
        document.getElementById('lastBackup').textContent = 'N/A';
    });

document.querySelectorAll('.logout').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('logoutModal').style.display = 'flex';
  });
});

// Logout modal functionality
document.getElementById('confirmLogoutBtn').onclick = function() {
    // Show loading state
    const confirmBtn = document.getElementById('confirmLogoutBtn');
    const cancelBtn = document.getElementById('cancelLogoutBtn');
    const originalText = confirmBtn.textContent;
    
    // Disable buttons and show loading
    confirmBtn.disabled = true;
    cancelBtn.disabled = true;
    confirmBtn.innerHTML = '<div style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px;"></div>Logging out...';
    
    // Add spinner animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    
    // Small delay to show loading state, then redirect
    setTimeout(() => {
        window.location.href = 'logout.php';
    }, 1000);
};

document.getElementById('cancelLogoutBtn').onclick = function() {
    document.getElementById('logoutModal').style.display = 'none';
};

// Close modal on outside click
window.onclick = function(e) {
    if (e.target === document.getElementById('logoutModal')) {
        document.getElementById('logoutModal').style.display = 'none';
    }
};
</script>
<?php if (!empty($showEmployerAddAccountUnauthorized)): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Not authorized',
            text: 'Only the super admin (main Admin account) can open Add Account. Sub-admin accounts cannot manage admin accounts.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#233a8b'
        });
    }
});
</script>
<?php endif; ?>
</body>
</html>


