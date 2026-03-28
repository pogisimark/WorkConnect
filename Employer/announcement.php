<?php
include 'session_protect.php';
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
    <title>WorkConnect Announcements</title>
    <!-- TinyMCE CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
        /* SweetAlert z-index fix - ensure validation/alerts appear above create announcement modal (z-index 2000) */
        .swal2-container {
            z-index: 99999 !important;
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
            }
            
            .main-content > div:first-child {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
                background: #fff;
                border-radius: 16px;
                padding: 20px;
                margin-bottom: 16px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .main-content > div:first-child > div:last-child {
                align-self: stretch;
            }
            
            .main-content > div:first-child h2 {
                font-size: 1.3rem;
                color: #233a8b;
            }
            
            .main-content > div:first-child p {
                font-size: 0.9rem;
                color: #666;
            }
            
            .main-content > div:nth-child(2) {
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
            
            /* Override any existing grid styles */
            .main-content > div[style*="grid-template-columns"] {
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
            }
            
            .main-content > div:nth-child(2) > div,
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
            }
            
            .main-content > div:first-child {
                padding: 16px;
                border-radius: 12px;
            }
            
            .main-content > div:first-child h2 {
                font-size: 1.2rem;
            }
            
            .main-content > div:first-child p {
                font-size: 0.85rem;
            }
            
            .main-content > div:nth-child(2) > div,
            .main-content > div:nth-child(3) > div,
            .main-content > div:nth-child(4) > div {
                padding: 14px;
                border-radius: 10px;
            }
            
            .main-content > div:nth-child(2) > div > div:first-child {
                font-size: 1.8rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(3) {
                font-size: 2rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(4) {
                font-size: 0.9rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(5) {
                font-size: 0.75rem;
            }
        }
        
        /* Spinner Loading Styles */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn-loading {
            opacity: 0.7;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* Enhanced Action Buttons */
        .action-btn {
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 80px;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .action-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-btn.edit {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
        }
        
        .action-btn.edit:hover {
            background: linear-gradient(135deg, #1976d2, #1565c0);
        }
        
        .action-btn.publish {
            background: linear-gradient(135deg, #4caf50, #388e3c);
            color: white;
        }
        
        .action-btn.publish:hover {
            background: linear-gradient(135deg, #388e3c, #2e7d32);
        }
        
        .action-btn.unpublish {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
        }
        
        .action-btn.unpublish:hover {
            background: linear-gradient(135deg, #f57c00, #ef6c00);
        }
        
        .action-btn.delete {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
        }
        
        .action-btn.delete:hover {
            background: linear-gradient(135deg, #d32f2f, #c62828);
        }
        
        .action-btn.btn-loading {
            opacity: 0.7;
            cursor: not-allowed;
            pointer-events: none;
            transform: none !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            min-width: 80px !important;
            width: auto !important;
            height: auto !important;
            padding: 8px 16px !important;
        }
        
        .action-btn.btn-loading .spinner {
            margin-right: 6px;
            margin-left: 0;
        }
        
        /* Search Input with Spinner */
        .search-container {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 30px 10px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #233a8b;
            box-shadow: 0 0 0 2px rgba(35, 58, 139, 0.1);
        }
        
        .search-input.loading {
            padding-right: 30px;
            background-color: #f8f9fa;
        }
        
        .search-spinner {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            border: 1.5px solid #e0e0e0;
            border-top: 1.5px solid #233a8b;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
            z-index: 10;
        }
        
        .search-spinner.active {
            display: block;
        }
        
        /* Enhanced Category and Status Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .badge.category {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
            border: 1px solid #90caf9;
        }
        
        .badge.category.hiring-alert {
            background: linear-gradient(135deg, #fff3e0, #ffcc02);
            color: #e65100;
            border: 1px solid #ffb74d;
        }
        
        .badge.category.job-fair {
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
            color: #2e7d32;
            border: 1px solid #81c784;
        }
        
        .badge.category.training {
            background: linear-gradient(135deg, #f3e5f5, #e1bee7);
            color: #7b1fa2;
            border: 1px solid #ba68c8;
        }
        
        .badge.category.update {
            background: linear-gradient(135deg, #e0f2f1, #b2dfdb);
            color: #00695c;
            border: 1px solid #4db6ac;
        }
        
        .badge.status {
            font-weight: 700;
        }
        
        .badge.status.published {
            background: linear-gradient(135deg, #4caf50, #388e3c);
            color: white;
            border: 1px solid #2e7d32;
        }
        
        .badge.status.draft {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            border: 1px solid #ef6c00;
        }
        
        .badge.status.archived {
            background: linear-gradient(135deg, #9e9e9e, #757575);
            color: white;
            border: 1px solid #616161;
        }
        
        /* Mobile responsive buttons */
        @media (max-width: 768px) {
            .action-btn {
                padding: 6px 12px;
                font-size: 11px;
                min-width: 70px;
                gap: 4px;
            }
            
            .action-btn span:first-child {
                font-size: 14px;
            }
            
            .search-input {
                padding: 8px 25px 8px 8px;
                font-size: 13px;
            }
            
            .search-spinner {
                right: 6px;
                width: 10px;
                height: 10px;
                border-width: 1px;
            }
        }
        
        @media (max-width: 480px) {
            .action-btn {
                padding: 5px 10px;
                font-size: 10px;
                min-width: 60px;
                gap: 3px;
            }
            
            .action-btn span:first-child {
                font-size: 12px;
            }
            
            .search-input {
                padding: 6px 20px 6px 6px;
                font-size: 12px;
            }
            
            .search-spinner {
                right: 4px;
                width: 8px;
                height: 8px;
                border-width: 1px;
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
            <a href="Dashboard.php"> DASHBOARD</a>
            <a href="job_postings.php"> JOB POSTINGS</a>
            <a href="job.php"> JOBSEEKERS<?php echo js_pending_jobseekers_badge_html($pending_jobseekers_count); ?></a>
            <a href="follow_up_requests.php"> FOLLOW-UP REQUESTS<?php echo fu_follow_up_badge_html($follow_up_pending_count); ?></a>
            <a href="request_follow_up.php"> REQUEST FOLLOW UP<span class="acfu-sidebar-badge"><?php echo acfu_unread_badge_html($acfu_unread_count); ?></span></a>
            <a href="skill.php"> SKILL REGISTRY</a>
            <a href="companies_list.php"> COMPANIES</a>
            <a href="btec.php"> BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
            <a href="analytics.php"> Analytics</a>
            <a href="announcement.php" class="active"> ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout"> Logout</a>
        </div>
        <div class="main-content">
            <!-- Header Section -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <div>
                    <h2 style="color:#233a8b; font-size:1.8rem; font-weight:bold; margin:0;">Announcements</h2>
                    <p style="color:#666; margin:8px 0 0 0; font-size:1.1rem;">Manage and create announcements for job seekers</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <button id="refreshBtn" style="background:#4caf50; color:#fff; border:none; padding:12px 20px; border-radius:8px; font-weight:600; cursor:pointer; font-size:0.9rem;">
                        🔄 Refresh
                    </button>
                    <button id="createAnnouncementBtn" style="background:#233a8b; color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:600; cursor:pointer; font-size:1rem;">
                        📢 Create New Announcement
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div id="statsOverview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px;">
                <!-- Stats will be loaded here -->
            </div>

            <!-- Filters and Search -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Search</label>
                        <div class="search-container">
                            <input type="text" id="searchInput" class="search-input" placeholder="Search announcements...">
                            <div class="search-spinner" id="searchSpinner"></div>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Status</label>
                        <select id="statusFilter" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                            <option value="">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Category</label>
                        <select id="categoryFilter" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                            <option value="">All Categories</option>
                            <option value="Job Fair">Job Fair</option>
                            <option value="Hiring Alert">Hiring Alert</option>
                            <option value="Training">Training</option>
                            <option value="Update">Update</option>
                        </select>
                    </div>
                    <div>
                        <button id="clearFiltersBtn" style="background: #f5f5f5; color: #666; border: 1px solid #ddd; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Announcements Table -->
            <div style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
                <div style="padding: 20px; border-bottom: 1px solid #eee;">
                    <h3 style="margin: 0; color: #333; font-size: 1.2rem;">All Announcements</h3>
                </div>
                <div id="announcementsTable" style="overflow-x: auto;">
                    <!-- Table will be loaded here -->
                </div>
                <div id="pagination" style="padding: 20px; border-top: 1px solid #eee; display: flex; justify-content: center; align-items: center; gap: 10px;">
                    <!-- Pagination will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Create/Edit Announcement Modal -->
        <div id="announcementModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; inset:0;width:100%;height:100%;min-height:100vh;min-height:100dvh;max-height:100dvh;box-sizing:border-box; background: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
            <div style="background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); padding: 32px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 id="modalTitle" style="margin: 0; color: #233a8b; font-size: 1.5rem;">Create New Announcement</h3>
                    <button id="closeModalBtn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
                </div>
                
                <form id="announcementForm">
                    <input type="hidden" id="announcementId" name="id">
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Title *</label>
                        <input type="text" id="announcementTitle" name="title" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Category *</label>
                        <select id="announcementCategory" name="category" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                            <option value="">Select Category</option>
                            <option value="Job Fair">Job Fair</option>
                            <option value="Hiring Alert">Hiring Alert</option>
                            <option value="Training">Training</option>
                            <option value="Update">Update</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Description *</label>
                        <textarea id="announcementDescription" name="description" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-height: 200px; resize: vertical;"></textarea>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Tags (comma-separated)</label>
                        <input type="text" id="announcementTags" name="tags" placeholder="e.g., IT Jobs, Manila, Full Time" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Expiration Date (optional)</label>
                        <input type="date" id="announcementExpiration" name="expiration_date" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Status</label>
                        <select id="announcementStatus" name="status" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Attachments</label>
                        <div id="fileUploadArea" style="border: 2px dashed #ddd; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 16px;">
                            <input type="file" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display: none;">
                            <p style="margin: 0; color: #666;">Click to upload files or drag and drop</p>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #999;">PDF, JPG, PNG, DOC, DOCX (max 5MB each)</p>
                        </div>
                        <div id="uploadedFiles" style="display: none;">
                            <h4 style="margin: 0 0 12px 0; color: #333; font-size: 14px;">Uploaded Files:</h4>
                            <div id="filesList"></div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" id="cancelBtn" style="background: #f5f5f5; color: #666; border: 1px solid #ddd; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            Cancel
                        </button>
                        <button type="button" id="previewBtn" style="background: #ff9800; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            Preview
                        </button>
                        <button type="submit" id="saveBtn" style="background: #233a8b; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            Save Draft
                        </button>
                        <button type="button" id="publishBtn" style="background: #4caf50; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            Publish
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Modal -->
        <div id="previewModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; inset:0;width:100%;height:100%;min-height:100vh;min-height:100dvh;max-height:100dvh;box-sizing:border-box; background: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
            <div style="background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); padding: 32px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 style="margin: 0; color: #233a8b; font-size: 1.5rem;">Preview</h3>
                    <button id="closePreviewBtn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
                </div>
                <div id="previewContent"></div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; inset:0;width:100%;height:100%;min-height:100vh;min-height:100dvh;max-height:100dvh;box-sizing:border-box; background: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
            <div style="background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); padding: 32px; max-width: 400px; width: 90%; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 16px;">⚠️</div>
                <h3 style="margin: 0 0 12px 0; color: #f44336; font-size: 1.3rem;">Confirm Delete</h3>
                <p style="color: #666; margin-bottom: 24px; font-size: 1rem;">Are you sure you want to delete this announcement? This action cannot be undone.</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button id="confirmDeleteBtn" style="background: #f44336; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        Yes, Delete
                    </button>
                    <button id="cancelDeleteBtn" style="background: #f5f5f5; color: #666; border: 1px solid #ddd; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        Cancel
                    </button>
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

// Announcement Management JavaScript
let currentPage = 1;
let currentFilters = {};
let currentAnnouncementId = null;
let uploadedFiles = [];

// Load stats overview
function loadStats() {
    fetch('announcement_stats.php?action=overview')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                document.getElementById('statsOverview').innerHTML = `
                    <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 2rem; color: #233a8b; font-weight: bold;">${stats.total_announcements}</div>
                        <div style="color: #666; font-size: 14px;">Total Announcements</div>
                    </div>
                    <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 2rem; color: #4caf50; font-weight: bold;">${stats.published}</div>
                        <div style="color: #666; font-size: 14px;">Published</div>
                    </div>
                    <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 2rem; color: #ff9800; font-weight: bold;">${stats.draft}</div>
                        <div style="color: #666; font-size: 14px;">Draft</div>
                    </div>
                    <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 2rem; color: #2196f3; font-weight: bold;">${stats.total_views}</div>
                        <div style="color: #666; font-size: 14px;">Total Views</div>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Load announcements table
function loadAnnouncements() {
    const params = new URLSearchParams({
        page: currentPage,
        limit: 10,
        ...currentFilters
    });
    
    fetch(`announcement_api.php?action=read&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAnnouncementsTable(data.announcements);
                renderPagination(data.pagination);
            } else {
                console.error('Error loading announcements:', data.error);
            }
        })
        .catch(error => console.error('Error loading announcements:', error))
        .finally(() => {
            // Hide search spinner when loading is complete
            const searchInput = document.getElementById('searchInput');
            const searchSpinner = document.getElementById('searchSpinner');
            searchInput.classList.remove('loading');
            searchSpinner.classList.remove('active');
        });
}

// Refresh announcements and stats
function refreshData() {
    const refreshBtn = document.getElementById('refreshBtn');
    const originalText = refreshBtn.innerHTML;
    
    // Show loading state
    refreshBtn.innerHTML = '<span class="spinner"></span>Refreshing...';
    refreshBtn.disabled = true;
    
    loadAnnouncements();
    loadStats();
    
    // Reset button after a short delay
    setTimeout(() => {
        refreshBtn.innerHTML = originalText;
        refreshBtn.disabled = false;
    }, 1000);
}

// Render announcements table
function renderAnnouncementsTable(announcements) {
    if (announcements.length === 0) {
        document.getElementById('announcementsTable').innerHTML = `
            <div style="padding: 40px; text-align: center; color: #666;">
                <div style="font-size: 3rem; margin-bottom: 16px;">📢</div>
                <h3 style="margin: 0 0 8px 0;">No announcements found</h3>
                <p style="margin: 0;">Create your first announcement to get started.</p>
            </div>
        `;
        return;
    }
    
    const tableHTML = `
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #eee; font-weight: 600;">Title</th>
                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #eee; font-weight: 600;">Category</th>
                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #eee; font-weight: 600;">Status</th>
                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #eee; font-weight: 600;">Date Posted</th>
                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #eee; font-weight: 600;">Views</th>
                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #eee; font-weight: 600;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${announcements.map(announcement => `
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 16px;">
                            <div style="font-weight: 600; color: #333; margin-bottom: 4px;">${announcement.title}</div>
                            <div style="font-size: 12px; color: #666;">${announcement.description.substring(0, 100)}${announcement.description.length > 100 ? '...' : ''}</div>
                        </td>
                        <td style="padding: 16px;">
                            <span class="badge category ${getCategoryClass(announcement.category)}">
                                ${announcement.category}
                            </span>
                        </td>
                        <td style="padding: 16px;">
                            <span class="badge status ${announcement.status}">
                                ${announcement.status}
                            </span>
                        </td>
                        <td style="padding: 16px; color: #666; font-size: 14px;">
                            ${new Date(announcement.date_posted).toLocaleDateString()}
                        </td>
                        <td style="padding: 16px; color: #666; font-size: 14px;">
                            ${announcement.view_count || 0}
                        </td>
                        <td style="padding: 16px;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button onclick="editAnnouncement(${announcement.id})" class="action-btn edit">
                                    
                                    <span>Edit</span>
                                </button>
                                <button onclick="changeAnnouncementStatus(${announcement.id}, '${announcement.status === 'published' ? 'draft' : 'published'}')" class="action-btn ${announcement.status === 'published' ? 'unpublish' : 'publish'}">
                                    
                                    <span>${announcement.status === 'published' ? 'Unpublish' : 'Publish'}</span>
                                </button>
                                <button onclick="deleteAnnouncement(${announcement.id})" class="action-btn delete">
                                    
                                    <span>Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    
    document.getElementById('announcementsTable').innerHTML = tableHTML;
}

// Get status color
function getStatusColor(status) {
    switch (status) {
        case 'published': return '#4caf50';
        case 'draft': return '#ff9800';
        case 'archived': return '#9e9e9e';
        default: return '#666';
    }
}

// Get category class for styling
function getCategoryClass(category) {
    switch (category.toLowerCase()) {
        case 'hiring alert': return 'hiring-alert';
        case 'job fair': return 'job-fair';
        case 'training': return 'training';
        case 'update': return 'update';
        default: return '';
    }
}

// Render pagination
function renderPagination(pagination) {
    if (pagination.pages <= 1) {
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    // Previous button
    if (pagination.page > 1) {
        paginationHTML += `<button onclick="goToPage(${pagination.page - 1})" style="background: #f5f5f5; color: #666; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Previous</button>`;
    }
    
    // Page numbers
    for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.pages, pagination.page + 2); i++) {
        paginationHTML += `<button onclick="goToPage(${i})" style="background: ${i === pagination.page ? '#233a8b' : '#f5f5f5'}; color: ${i === pagination.page ? '#fff' : '#666'}; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; cursor: pointer; margin: 0 4px;">${i}</button>`;
    }
    
    // Next button
    if (pagination.page < pagination.pages) {
        paginationHTML += `<button onclick="goToPage(${pagination.page + 1})" style="background: #f5f5f5; color: #666; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Next</button>`;
    }
    
    document.getElementById('pagination').innerHTML = paginationHTML;
}

// Go to page
function goToPage(page) {
    currentPage = page;
    loadAnnouncements();
}

// Apply filters
function applyFilters() {
    currentPage = 1;
    currentFilters = {
        search: document.getElementById('searchInput').value,
        status: document.getElementById('statusFilter').value,
        category: document.getElementById('categoryFilter').value
    };
    
    loadAnnouncements();
}

// Clear filters
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('categoryFilter').value = '';
    
    // Hide search spinner
    const searchInput = document.getElementById('searchInput');
    const searchSpinner = document.getElementById('searchSpinner');
    searchInput.classList.remove('loading');
    searchSpinner.classList.remove('active');
    
    currentFilters = {};
    currentPage = 1;
    loadAnnouncements();
}

// Create new announcement
function createAnnouncement() {
    currentAnnouncementId = null;
    document.getElementById('modalTitle').textContent = 'Create New Announcement';
    document.getElementById('announcementForm').reset();
    document.getElementById('announcementId').value = '';
    uploadedFiles = [];
    updateFilesList();
    document.getElementById('announcementModal').style.display = 'flex';
}

// Edit announcement
function editAnnouncement(id) {
    currentAnnouncementId = id;
    document.getElementById('modalTitle').textContent = 'Edit Announcement';
    
    // Find the edit button that was clicked and show loading state
    const editButton = event.target.closest('.action-btn');
    if (editButton) {
        const originalText = editButton.innerHTML;
        editButton.innerHTML = `<span class="spinner"></span><span>Loading...</span>`;
        editButton.classList.add('btn-loading');
        editButton.disabled = true;
        editButton.setAttribute('data-original-text', originalText);
        
        // Ensure button maintains its size
        editButton.style.minWidth = '80px';
        editButton.style.width = 'auto';
    }
    
    fetch(`announcement_api.php?action=get_single&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const announcement = data.announcement;
                document.getElementById('announcementId').value = announcement.id;
                document.getElementById('announcementTitle').value = announcement.title;
                document.getElementById('announcementCategory').value = announcement.category;
                document.getElementById('announcementDescription').value = announcement.description;
                document.getElementById('announcementTags').value = announcement.tags || '';
                document.getElementById('announcementExpiration').value = announcement.expiration_date || '';
                document.getElementById('announcementStatus').value = announcement.status;
                
                uploadedFiles = announcement.attachments || [];
                updateFilesList();
                
                document.getElementById('announcementModal').style.display = 'flex';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error loading announcement: ' + data.error
                });
            }
        })
        .catch(error => {
            console.error('Error loading announcement:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error loading announcement'
            });
        })
        .finally(() => {
            // Reset button state
            if (editButton) {
                const originalText = editButton.getAttribute('data-original-text');
                editButton.innerHTML = originalText;
                editButton.classList.remove('btn-loading');
                editButton.disabled = false;
                editButton.removeAttribute('data-original-text');
                editButton.style.minWidth = '';
                editButton.style.width = '';
            }
        });
}

// Save announcement
function saveAnnouncement(status = 'draft') {
    const formData = {
        id: document.getElementById('announcementId').value,
        title: document.getElementById('announcementTitle').value,
        category: document.getElementById('announcementCategory').value,
        description: document.getElementById('announcementDescription').value,
        tags: document.getElementById('announcementTags').value.split(',').map(tag => tag.trim()).filter(tag => tag),
        expiration_date: document.getElementById('announcementExpiration').value,
        status: status
    };
    
    const url = currentAnnouncementId ? 'announcement_api.php?action=update' : 'announcement_api.php?action=create';
    
    // Show loading state for appropriate button
    const saveBtn = document.getElementById('saveBtn');
    const publishBtn = document.getElementById('publishBtn');
    const originalSaveText = saveBtn.innerHTML;
    const originalPublishText = publishBtn.innerHTML;
    
    if (status === 'draft') {
        saveBtn.innerHTML = '<span class="spinner"></span>Saving...';
        saveBtn.classList.add('btn-loading');
        saveBtn.disabled = true;
    } else {
        publishBtn.innerHTML = '<span class="spinner"></span>Publishing...';
        publishBtn.classList.add('btn-loading');
        publishBtn.disabled = true;
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Announcement saved successfully!',
                timer: 2000,
                showConfirmButton: false
            });
            document.getElementById('announcementModal').style.display = 'none';
            loadAnnouncements();
            loadStats();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error saving announcement: ' + data.error
            });
        }
    })
    .catch(error => {
        console.error('Error saving announcement:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error saving announcement'
        });
    })
    .finally(() => {
        // Reset button states
        saveBtn.innerHTML = originalSaveText;
        saveBtn.classList.remove('btn-loading');
        saveBtn.disabled = false;
        publishBtn.innerHTML = originalPublishText;
        publishBtn.classList.remove('btn-loading');
        publishBtn.disabled = false;
    });
}

// Change announcement status
function changeAnnouncementStatus(id, newStatus) {
    // Find the button that was clicked using event target
    const targetButton = event.target.closest('.action-btn');
    
    if (targetButton) {
        const originalText = targetButton.innerHTML;
        const loadingText = newStatus === 'published' ? 'Publishing...' : 'Unpublishing...';
        targetButton.innerHTML = `<span class="spinner"></span><span>${loadingText}</span>`;
        targetButton.classList.add('btn-loading');
        targetButton.disabled = true;
        
        // Store original text for restoration
        targetButton.setAttribute('data-original-text', originalText);
        
        // Ensure button maintains its size
        targetButton.style.minWidth = '80px';
        targetButton.style.width = 'auto';
    }
    
    fetch('announcement_api.php?action=change_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id, status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Status updated successfully!',
                timer: 2000,
                showConfirmButton: false
            });
            loadAnnouncements();
            loadStats();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error updating status: ' + data.error
            });
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error updating status'
        });
    })
    .finally(() => {
        // Reset button state
        if (targetButton) {
            const originalText = targetButton.getAttribute('data-original-text');
            targetButton.innerHTML = originalText;
            targetButton.classList.remove('btn-loading');
            targetButton.disabled = false;
            targetButton.removeAttribute('data-original-text');
            targetButton.style.minWidth = '';
            targetButton.style.width = '';
        }
    });
}

// Delete announcement
function deleteAnnouncement(id) {
    currentAnnouncementId = id;
    
    // Find the delete button that was clicked and show loading state
    const deleteButton = event.target.closest('.action-btn');
    if (deleteButton) {
        const originalText = deleteButton.innerHTML;
        deleteButton.innerHTML = `<span class="spinner"></span><span>Deleting...</span>`;
        deleteButton.classList.add('btn-loading');
        deleteButton.disabled = true;
        deleteButton.setAttribute('data-original-text', originalText);
        
        // Ensure button maintains its size
        deleteButton.style.minWidth = '80px';
        deleteButton.style.width = 'auto';
    }
    
    // Show SweetAlert2 confirmation dialog
    Swal.fire({
        title: 'Confirm Delete',
        text: 'Are you sure you want to delete this announcement? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f44336',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            confirmDelete();
        } else {
            // Reset button state if cancelled
            if (deleteButton) {
                const originalText = deleteButton.getAttribute('data-original-text');
                deleteButton.innerHTML = originalText;
                deleteButton.classList.remove('btn-loading');
                deleteButton.disabled = false;
                deleteButton.removeAttribute('data-original-text');
                deleteButton.style.minWidth = '';
                deleteButton.style.width = '';
            }
        }
    });
}

// Confirm delete
function confirmDelete() {
    fetch('announcement_api.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: currentAnnouncementId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Announcement deleted successfully!',
                timer: 2000,
                showConfirmButton: false
            });
            loadAnnouncements();
            loadStats();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error deleting announcement: ' + data.error
            });
        }
    })
    .catch(error => {
        console.error('Error deleting announcement:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error deleting announcement'
        });
    })
    .finally(() => {
        // Reset the delete button in the table if it exists
        const deleteButtons = document.querySelectorAll('.action-btn.delete');
        deleteButtons.forEach(button => {
            if (button.getAttribute('data-original-text')) {
                const originalText = button.getAttribute('data-original-text');
                button.innerHTML = originalText;
                button.classList.remove('btn-loading');
                button.disabled = false;
                button.removeAttribute('data-original-text');
                button.style.minWidth = '';
                button.style.width = '';
            }
        });
    });
}

// File upload handling
function setupFileUpload() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('fileInput');
    
    fileUploadArea.addEventListener('click', () => fileInput.click());
    
    fileUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        fileUploadArea.style.borderColor = '#233a8b';
        fileUploadArea.style.backgroundColor = '#f0f4ff';
    });
    
    fileUploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        fileUploadArea.style.borderColor = '#ddd';
        fileUploadArea.style.backgroundColor = 'transparent';
    });
    
    fileUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileUploadArea.style.borderColor = '#ddd';
        fileUploadArea.style.backgroundColor = 'transparent';
        
        const files = e.dataTransfer.files;
        handleFileUpload(files);
    });
    
    fileInput.addEventListener('change', (e) => {
        handleFileUpload(e.target.files);
    });
}

// Handle file upload
function handleFileUpload(files) {
    // If no announcement ID yet, save as draft first
    if (!currentAnnouncementId) {
        // Auto-save as draft to get an ID
        const formData = {
            title: document.getElementById('announcementTitle').value || 'Untitled',
            category: document.getElementById('announcementCategory').value || 'Update',
            description: document.getElementById('announcementDescription').value || 'No description',
            tags: [],
            expiration_date: document.getElementById('announcementExpiration').value,
            status: 'draft'
        };
        
        fetch('announcement_api.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentAnnouncementId = data.announcement_id;
                document.getElementById('announcementId').value = currentAnnouncementId;
                // Now upload the files
                uploadFiles(files);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error creating announcement: ' + data.error
                });
            }
        })
        .catch(error => {
            console.error('Error creating announcement:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error creating announcement'
            });
        });
        return;
    }
    
    uploadFiles(files);
}

// Separate function to actually upload files
function uploadFiles(files) {
    Array.from(files).forEach(file => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('announcement_id', currentAnnouncementId);
        
        fetch('upload_announcement_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadedFiles.push(data.file);
                updateFilesList();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error uploading file: ' + data.error
                });
            }
        })
        .catch(error => {
            console.error('Error uploading file:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error uploading file'
            });
        });
    });
}

// Normalize file object (handles both upload response and API response)
function normalizeFile(file) {
    const path = file.path || file.file_path || '';
    const name = file.name || file.file_name || '';
    const type = (file.type || file.file_type || '').toLowerCase();
    const size = file.size || file.file_size || 0;
    let sizeFormatted = file.size_formatted;
    if (!sizeFormatted && size) {
        sizeFormatted = size >= 1024 * 1024 ? (size / (1024 * 1024)).toFixed(2) + ' MB' : (size / 1024).toFixed(2) + ' KB';
    }
    const ext = (name.split('.').pop() || '').toLowerCase();
    const isImage = /^(jpe?g|png|gif|webp)$/i.test(ext) || /^image\//.test(type);
    const isPdf = ext === 'pdf' || type === 'application/pdf';
    const previewUrl = path ? '../' + path : '';
    return { ...file, path, name, type, size, size_formatted: sizeFormatted, isImage, isPdf, previewUrl };
}

// Update files list with visual previews
function updateFilesList() {
    const filesList = document.getElementById('filesList');
    const uploadedFilesDiv = document.getElementById('uploadedFiles');
    
    if (uploadedFiles.length === 0) {
        uploadedFilesDiv.style.display = 'none';
        return;
    }
    
    uploadedFilesDiv.style.display = 'block';
    filesList.innerHTML = uploadedFiles.map(file => {
        const f = normalizeFile(file);
        let previewHtml = '';
        if (f.isImage && f.previewUrl) {
            previewHtml = `<img src="${f.previewUrl}" alt="${f.name}" style="width:100%;height:120px;object-fit:contain;background:#f0f0f0;border-radius:6px;" onerror="this.parentElement.innerHTML='<div style=\\'height:120px;display:flex;align-items:center;justify-content:center;background:#e0e0e0;border-radius:6px;color:#666;font-size:12px;\\'>No preview</div>'">`;
        } else if (f.isPdf && f.previewUrl) {
            previewHtml = `<object data="${f.previewUrl}#page=1" type="application/pdf" style="width:100%;height:120px;border-radius:6px;background:#f5f5f5;"><div style="height:120px;display:flex;align-items:center;justify-content:center;background:#e3f2fd;border-radius:6px;color:#1976d2;font-size:14px;font-weight:600;">PDF: ${f.name}</div></object>`;
        } else {
            previewHtml = `<div style="height:120px;display:flex;align-items:center;justify-content:center;background:#e3f2fd;border-radius:6px;color:#1976d2;font-size:14px;font-weight:600;"><span style="margin-right:8px;">📄</span>${f.name}</div>`;
        }
        return `
        <div style="flex:0 0 auto;width:140px;margin:0 12px 12px 0;background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06);">
            <div style="padding:6px;min-height:120px;max-height:120px;overflow:hidden;">${previewHtml}</div>
            <div style="padding:8px;border-top:1px solid #eee;">
                <div style="font-weight:600;color:#333;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${f.name}">${f.name}</div>
                <div style="font-size:11px;color:#666;margin-top:2px;">${f.size_formatted || ''}</div>
                <button onclick="removeFile(${file.id})" style="margin-top:6px;width:100%;background:#f44336;color:#fff;border:none;padding:6px;border-radius:4px;cursor:pointer;font-size:11px;font-weight:600;">Remove</button>
            </div>
        </div>
        `;
    }).join('');
    filesList.style.display = 'flex';
    filesList.style.flexWrap = 'wrap';
    filesList.style.gap = '12px';
}

// Remove file
function removeFile(fileId) {
    fetch('announcement_api.php?action=delete_file', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ file_id: fileId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            uploadedFiles = uploadedFiles.filter(file => file.id !== fileId);
            updateFilesList();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error removing file: ' + data.error
            });
        }
    })
    .catch(error => {
        console.error('Error removing file:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error removing file'
        });
    });
}

// Preview announcement
function previewAnnouncement() {
    const title = document.getElementById('announcementTitle').value;
    const category = document.getElementById('announcementCategory').value;
    const description = document.getElementById('announcementDescription').value;
    const tags = document.getElementById('announcementTags').value;
    
    if (!title || !category || !description) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please fill in all required fields before previewing.'
        });
        return;
    }
    
    const previewHTML = `
        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="margin: 0; color: #233a8b;">${title}</h2>
                <span style="background: #e3eaff; color: #233a8b; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                    ${category}
                </span>
            </div>
            <div style="margin-bottom: 16px; line-height: 1.6;">
                ${description.replace(/\n/g, '<br>')}
            </div>
            ${tags ? `
                <div style="margin-bottom: 16px;">
                    <strong>Tags:</strong> ${tags.split(',').map(tag => `<span style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 12px;">${tag.trim()}</span>`).join('')}
                </div>
            ` : ''}
            ${uploadedFiles.length > 0 ? `
                <div>
                    <strong>Attachments:</strong>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                        ${uploadedFiles.map(file => `<li>${file.name} (${file.size_formatted})</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('previewContent').innerHTML = previewHTML;
    document.getElementById('previewModal').style.display = 'flex';
}

// Initialize TinyMCE
function initTinyMCE() {
    tinymce.init({
        selector: '#announcementDescription',
        height: 300,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help',
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
        }
    });
}

// Initialize everything when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadAnnouncements();
    setupFileUpload();
    initTinyMCE();
    
    // Auto-refresh data every 30 seconds to update view counts
    setInterval(refreshData, 30000);
    
    // Event listeners
    document.getElementById('createAnnouncementBtn').addEventListener('click', createAnnouncement);
    document.getElementById('refreshBtn').addEventListener('click', refreshData);
    document.getElementById('closeModalBtn').addEventListener('click', () => document.getElementById('announcementModal').style.display = 'none');
    document.getElementById('cancelBtn').addEventListener('click', () => document.getElementById('announcementModal').style.display = 'none');
    document.getElementById('previewBtn').addEventListener('click', previewAnnouncement);
    document.getElementById('closePreviewBtn').addEventListener('click', () => document.getElementById('previewModal').style.display = 'none');
    document.getElementById('saveBtn').addEventListener('click', (e) => {
        e.preventDefault();
        saveAnnouncement('draft');
    });
    document.getElementById('publishBtn').addEventListener('click', (e) => {
        e.preventDefault();
        saveAnnouncement('published');
    });
    document.getElementById('announcementForm').addEventListener('submit', (e) => {
        e.preventDefault();
        saveAnnouncement('draft');
    });
    
    // Filter event listeners
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchInput = document.getElementById('searchInput');
        const searchSpinner = document.getElementById('searchSpinner');
        
        // Show spinner immediately when user types
        if (e.target.value.trim()) {
            searchInput.classList.add('loading');
            searchSpinner.classList.add('active');
        } else {
            searchInput.classList.remove('loading');
            searchSpinner.classList.remove('active');
        }
        
        // Apply debounced search
        debounce(applyFilters, 500)();
    });
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('categoryFilter').addEventListener('change', applyFilters);
    document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
    
    // Delete functionality is now handled by SweetAlert2
    
    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target.id === 'announcementModal') {
            document.getElementById('announcementModal').style.display = 'none';
        }
        if (e.target.id === 'previewModal') {
            document.getElementById('previewModal').style.display = 'none';
        }
        if (e.target.id === 'deleteModal') {
            document.getElementById('deleteModal').style.display = 'none';
        }
    });
});

// Debounce function for search
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
</body>
</html>