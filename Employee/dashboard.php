<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_check.php';
require_once 'db.php';

// Additional session validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Generate unique session token for iframe security
$session_token = hash('sha256', session_id() . $_SESSION['user_id'] . 'workconnect');
$_SESSION['iframe_token'] = $session_token;

// NSRP form submissions (jobseeker table)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM jobseeker WHERE user_id = ? ORDER BY submission_year DESC, submission_month DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get application counts by status
$stmt = $conn->prepare("SELECT COALESCE(application_status, 'Pending') as application_status, COUNT(*) as count FROM jobseeker WHERE user_id = ? GROUP BY application_status");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$status_counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$status_counts_assoc = [
    'Pending' => 0,
    'Referred' => 0,
    'Accepted' => 0,
    'Rejected' => 0
];
foreach ($status_counts as $status) {
    $status_key = $status['application_status'] ?: 'Pending';
    $status_counts_assoc[$status_key] = $status['count'];
}

// Count job applications from Recommended Jobs (job_applications_extended)
$recommended_job_application_count = 0;
$jae_table = @$conn->query("SHOW TABLES LIKE 'job_applications_extended'");
if ($jae_table && $jae_table->num_rows > 0) {
    $stmt_jae = $conn->prepare(
        'SELECT COUNT(*) AS c FROM job_applications_extended jae
         INNER JOIN jobseeker j ON j.id = jae.jobseeker_id
         WHERE j.user_id = ?'
    );
    if ($stmt_jae) {
        $stmt_jae->bind_param('i', $user_id);
        $stmt_jae->execute();
        $row_jae = $stmt_jae->get_result()->fetch_assoc();
        $recommended_job_application_count = (int)($row_jae['c'] ?? 0);
        $stmt_jae->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global SweetAlert entry point for iframe children.
        window.showGlobalSwal = function() {
            const scrollContainer = document.querySelector('.dashboard-container') || document.scrollingElement || document.documentElement;
            const savedWindowY = window.scrollY || window.pageYOffset || 0;
            const savedContainerY = scrollContainer ? scrollContainer.scrollTop : 0;

            // Most calls use object syntax; inject scroll-safe defaults there.
            if (arguments.length > 0 && typeof arguments[0] === 'object' && arguments[0] !== null) {
                const inputConfig = arguments[0];
                const userDidOpen = inputConfig.didOpen;
                const userWillClose = inputConfig.willClose;
                const config = Object.assign({}, inputConfig, {
                    heightAuto: false,
                    returnFocus: false,
                    didOpen: function(popup) {
                        if (typeof userDidOpen === 'function') userDidOpen(popup);
                    },
                    willClose: function(popup) {
                        if (typeof userWillClose === 'function') userWillClose(popup);
                        if (scrollContainer) scrollContainer.scrollTop = savedContainerY;
                        window.scrollTo(0, savedWindowY);
                    }
                });
                return Swal.fire(config);
            }

            return Swal.fire.apply(Swal, arguments);
        };
    </script>
    <style>
        /* Spinner Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Loading indicator styles */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1a3876;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .loading-spinner {
                width: 18px;
                height: 18px;
                border-width: 2px;
            }
        }
        
        /* Hamburger menu - hidden on desktop, shown on mobile */
        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            margin-right: 12px;
            z-index: 1001;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .hamburger-menu span {
            display: block;
            width: 24px;
            height: 3px;
            background: #fff;
            margin: 3px 0;
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
        @media (min-width: 769px) {
            .hamburger-menu {
                display: none !important;
            }
        }
        
    /* Mobile-first responsive design */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 10px 15px;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            height: auto;
            min-height: 56px;
        }
        
        .logo-brand {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }
        
        /* Mobile: hide logo + brand text; keep hamburger */
        .logo-brand .logo,
        .logo-brand .brand {
            display: none !important;
        }
        
        .hamburger-menu {
            display: flex !important;
            flex-shrink: 0;
        }
        
        .logo {
            height: 30px;
            margin-right: 8px;
        }
        
        .brand {
            font-size: 1.2rem;
        }
        
        .user-info {
            flex-shrink: 0;
            justify-content: flex-end;
            gap: 8px;
        }
        
        .user-profile {
            margin-right: 0;
            gap: 5px;
        }
        
        .welcome-text {
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px; /* wider now that logo/brand are hidden on mobile */
        }
        
        .notification-container {
            margin-right: 0;
        }
        
        .dashboard-container {
            flex-direction: column !important;
            padding-top: 80px;
            padding-bottom: 120px; /* Much more space for bottom navigation */
            height: auto;
            min-height: calc(100vh - 200px); /* Account for header and bottom nav */
            min-height: calc(100dvh - 200px - env(safe-area-inset-bottom, 0px));
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            position: relative;
            overflow-y: auto; /* Enable vertical scrolling */
        }
        
        /* NSRP iframe: height is set by JS to full content — no min-height (avoids double scrollbars) */
        #apply-iframe {
            width: 100% !important;
            min-height: 0 !important;
            height: auto !important;
            border: none !important;
            border-radius: 8px !important;
            position: relative;
            z-index: 1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Mobile-specific progress bar styling */
        @media (max-width: 768px) {
            /* Mobile progress bar improvements - these styles will be injected into iframe */
            .mobile-progress-container {
                position: sticky;
                top: 0;
                z-index: 100;
                background: white;
                padding: 10px;
                border-bottom: 1px solid #e0e0e0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .mobile-progress-bar {
                width: 100%;
                height: 8px;
                background: #e0e0e0;
                border-radius: 4px;
                overflow: hidden;
                margin-bottom: 10px;
            }
            
            .mobile-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #1a3876, #1976d2);
                border-radius: 4px;
                transition: width 0.3s ease;
            }
            
            .mobile-progress-text {
                font-size: 0.8rem;
                color: #666;
                text-align: center;
                font-weight: 500;
            }
            
            /* Mobile horizontal progress steps */
            .progress-steps {
                display: flex !important;
                flex-direction: row !important;
                overflow-x: auto !important;
                padding: 10px 0 !important;
                gap: 8px !important;
            -webkit-overflow-scrolling: touch !important;
            }
            
            .progress-step {
                flex-shrink: 0 !important;
                min-width: 60px !important;
                text-align: center !important;
                padding: 8px 4px !important;
            }
            
            .progress-step-circle {
                width: 30px !important;
                height: 30px !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto 4px auto !important;
                font-size: 0.8rem !important;
                font-weight: bold !important;
            }
            
            .progress-step-text {
                font-size: 0.7rem !important;
                line-height: 1.2 !important;
                word-wrap: break-word !important;
            }
            
            /* Active step styling for mobile */
            .progress-step.active .progress-step-circle {
                background: #1a3876 !important;
                color: white !important;
            }
            
            .progress-step.active .progress-step-text {
                color: #1a3876 !important;
                font-weight: bold !important;
            }
            
            /* Inactive step styling for mobile */
            .progress-step:not(.active) .progress-step-circle {
                background: #e0e0e0 !important;
                color: #666 !important;
            }
            
            .progress-step:not(.active) .progress-step-text {
                color: #666 !important;
            }
            
            /* Fix form field alignment on mobile */
            .form-group,
            .form-field,
            .field-group,
            .input-group {
                text-align: left !important;
                align-items: flex-start !important;
                justify-content: flex-start !important;
            }
            
            /* Hide returnee fields by default on mobile */
            #returneeFields,
            #returneeReturnFields {
                display: none !important;
            }
            
            /* Show returnee fields only when returnee is Yes */
            body:has(#returneeYes:checked) #returneeFields,
            body:has(#returneeYes:checked) #returneeReturnFields {
                display: block !important;
            }
            
            
            /* Reorganize disability layout for mobile */
            .disability-section,
            .disability-field,
            .disability-container {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Main disability checkbox at top */
            .disability-main,
            .disability-primary,
            .disability-checkbox:first-child,
            .disability-section > div:first-child,
            input[type="checkbox"][name*="disability"]:not([name*="speech"]):not([name*="hearing"]):not([name*="visual"]):not([name*="mental"]):not([name*="others"]),
            input[type="checkbox"][name*="Disability"]:not([name*="speech"]):not([name*="hearing"]):not([name*="visual"]):not([name*="mental"]):not([name*="others"]) {
                order: 1 !important;
                display: flex !important;
                align-items: center !important;
                margin-bottom: 15px !important;
                width: 100% !important;
                text-align: left !important;
            }
            
            /* Sub-checkboxes horizontal layout */
            .disability-options,
            .disability-list,
            .disability-checkboxes,
            .disability-section > div:not(:first-child),
            .disability-field > div:not(:first-child) {
                order: 2 !important;
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 10px !important;
                width: 100% !important;
                justify-content: flex-start !important;
                align-items: flex-start !important;
            }
            
            /* Individual sub-checkbox styling */
            .disability-options > div,
            .disability-list > div,
            .disability-checkboxes > div,
            .disability-section > div:not(:first-child) > div,
            .disability-field > div:not(:first-child) > div {
                display: flex !important;
                align-items: center !important;
                margin: 0 !important;
                padding: 5px !important;
                min-width: 60px !important;
                flex-shrink: 0 !important;
            }
            
            /* Sub-checkbox labels */
            .disability-options label,
            .disability-list label,
            .disability-checkboxes label,
            .disability-section > div:not(:first-child) label,
            .disability-field > div:not(:first-child) label {
                font-size: 0.8rem !important;
                margin-left: 5px !important;
                margin-right: 0 !important;
                white-space: nowrap !important;
            }
        }
        
        /* Extra small mobile screens (less than 400px) */
        @media (max-width: 400px) {
            /* ULTRA-AGGRESSIVE: Force main disability checkbox to left */
            body .form-row:has(#hasDisability),
            body label:has(#hasDisability),
            body .disability-section,
            body .disability-field,
            body .disability-container,
            body [class*="disability"],
            body [id*="disability"] {
                text-align: left !important;
                align-items: flex-start !important;
                justify-content: flex-start !important;
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* OVERRIDE: Force form-row containing disability to column layout */
            body .form-row:has(#hasDisability) {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                justify-content: flex-start !important;
                text-align: left !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* OVERRIDE: Force main disability label to left alignment */
            body .form-row:has(#hasDisability) label:has(#hasDisability) {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                text-align: left !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                gap: 8px !important;
            }
            
            /* ULTRA-AGGRESSIVE: Main disability checkbox at top - force left alignment */
            body #hasDisability,
            body input[type="checkbox"][name="hasDisability"],
            body .disability-main,
            body .disability-primary,
            body .disability-checkbox:first-child,
            body .disability-section > div:first-child,
            body input[type="checkbox"][name*="disability"]:not([name*="speech"]):not([name*="hearing"]):not([name*="visual"]):not([name*="mental"]):not([name*="others"]),
            body input[type="checkbox"][name*="Disability"]:not([name*="speech"]):not([name*="hearing"]):not([name*="visual"]):not([name*="mental"]):not([name*="others"]),
            body input[type="checkbox"][name="disability"],
            body input[type="checkbox"][name="Disability"],
            body input[type="checkbox"][name*="disability"],
            body input[type="checkbox"][name*="Disability"] {
                order: 1 !important;
                display: flex !important;
                align-items: center !important;
                margin-bottom: 10px !important;
                width: 100% !important;
                text-align: left !important;
                float: none !important;
                margin-left: 0 !important;
                margin-right: auto !important;
                justify-content: flex-start !important;
                position: static !important;
                left: 0 !important;
                right: auto !important;
                transform: none !important;
            }
            
            /* ULTRA-AGGRESSIVE: Sub-checkboxes horizontal layout */
            body .checkbox-group#disabilityFields,
            body #disabilityFields,
            body .disability-options,
            body .disability-list,
            body .disability-checkboxes,
            body .disability-section > div:not(:first-child),
            body .disability-field > div:not(:first-child),
            body [class*="disability"] > div:not(:first-child),
            body [id*="disability"] > div:not(:first-child) {
                order: 2 !important;
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 5px !important;
                width: 100% !important;
                justify-content: flex-start !important;
                align-items: flex-start !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }
            
            /* OVERRIDE: Force checkbox-group to horizontal layout */
            body .form-row:has(#hasDisability) .checkbox-group#disabilityFields {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
                width: 100% !important;
                justify-content: flex-start !important;
                align-items: flex-start !important;
                margin-top: 10px !important;
                margin-bottom: 0 !important;
                padding: 0 !important;
            }
            
            /* ULTRA-AGGRESSIVE: Individual sub-checkbox styling */
            body #disabilityFields label,
            body .disability-options > div,
            body .disability-list > div,
            body .disability-checkboxes > div,
            body .disability-section > div:not(:first-child) > div,
            body .disability-field > div:not(:first-child) > div,
            body [class*="disability"] > div:not(:first-child) > div,
            body [id*="disability"] > div:not(:first-child) > div {
                display: flex !important;
                align-items: center !important;
                margin: 0 !important;
                padding: 3px !important;
                min-width: 50px !important;
                flex-shrink: 0 !important;
                white-space: nowrap !important;
                float: none !important;
                text-align: left !important;
            }
            
            /* OVERRIDE: Force individual checkbox labels to horizontal layout */
            body .form-row:has(#hasDisability) .checkbox-group#disabilityFields label {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                margin: 0 !important;
                padding: 5px 8px !important;
                min-width: 60px !important;
                flex-shrink: 0 !important;
                white-space: nowrap !important;
                text-align: left !important;
                gap: 5px !important;
            }
            
            /* ULTRA-AGGRESSIVE: Sub-checkbox labels */
            body .disability-options label,
            body .disability-list label,
            body .disability-checkboxes label,
            body .disability-section > div:not(:first-child) label,
            body .disability-field > div:not(:first-child) label,
            body [class*="disability"] > div:not(:first-child) label,
            body [id*="disability"] > div:not(:first-child) label {
                font-size: 0.7rem !important;
                margin-left: 3px !important;
                margin-right: 0 !important;
                white-space: nowrap !important;
                text-align: left !important;
                float: none !important;
            }
            
            /* ULTRA-AGGRESSIVE: Force all disability elements to left alignment */
            body .disability-section *,
            body .disability-field *,
            body .disability-container *,
            body [class*="disability"] *,
            body [id*="disability"] * {
                text-align: left !important;
                float: none !important;
                margin-left: 0 !important;
                margin-right: auto !important;
                position: static !important;
                left: 0 !important;
                right: auto !important;
            }
            
            /* ULTRA-AGGRESSIVE: Override any existing styles */
            body input[type="checkbox"],
            body .checkbox,
            body .form-checkbox {
                margin-left: 0 !important;
                margin-right: auto !important;
                float: none !important;
                text-align: left !important;
                position: static !important;
                left: 0 !important;
                right: auto !important;
            }
        }
        
        /* Mobile Bottom Navigation - Completely separate from desktop */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 60px;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 0;
        }
        
        /* Hide mobile bottom nav on desktop */
        @media (min-width: 769px) {
            .mobile-bottom-nav {
                display: none !important;
            }
        }
        
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 6px 4px;
            font-size: 0.7rem;
            white-space: nowrap;
            border-radius: 8px;
            background: transparent;
            border: none;
            color: #666;
            text-decoration: none;
            min-height: 44px;
            flex: 1;
            min-width: 0;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .mobile-nav-item:before {
            content: '';
            font-size: 1.2rem;
            margin-bottom: 2px;
        }
        
        .mobile-nav-item[data-section="dashboard"]:before { content: '📊'; }
        .mobile-nav-item[data-section="recommended_jobs"]:before { content: '💼'; }
        .mobile-nav-item[data-section="apply"]:before { content: '📋'; }
        .mobile-nav-item[data-section="follow_up"]:before { content: '💬'; }
        .mobile-nav-item[data-section="announcements"]:before { content: '📢'; }
        .mobile-nav-item[data-section="profile"]:before { content: '👤'; }
        
        .mobile-nav-item.active {
            background: #1a3876;
            color: white;
        }
        
        .mobile-nav-item:hover {
            background: #f8f9fa;
            color: #1a3876;
        }
        
        .main-content {
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding: 15px 15px 120px 15px; /* Much more bottom padding for mobile nav */
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden;
            overflow-y: visible; /* Allow content to extend */
            flex: 1;
            position: relative;
            left: 0;
            right: 0;
            min-height: calc(100vh - 220px); /* Ensure enough height for scrolling */
            min-height: calc(100dvh - 220px - env(safe-area-inset-bottom, 0px));
        }
        
        .welcome-card {
            padding: 20px 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .welcome-card h1 {
            font-size: 1.3rem;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .application-status {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .status-card {
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .status-card h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 20px;
            text-align: center;
            white-space: nowrap;
        }
        
        .status-badge.status-pending {
            background: #ffc107;
            color: #333;
        }
        
        .status-badge.status-referred {
            background: #2196f3;
            color: white;
        }
        
        .status-badge.status-accepted {
            background: #4CAF50;
            color: white;
        }
        
        .status-badge.status-rejected {
            background: #f44336;
            color: white;
        }
        
        /* Fallback for any other status values or empty status */
        .status-badge:not(.status-pending):not(.status-referred):not(.status-accepted):not(.status-rejected) {
            background: #9e9e9e;
            color: white;
        }
        
        .profile-summary {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .profile-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .profile-item h4 {
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #666;
        }
        
        .profile-item p {
            font-size: 1rem;
            margin: 0;
        }
        
        /* New Features Section Styles */
        .new-features-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #233a8b;
        }
        
        .feature-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .feature-card p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .feature-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffc107;
            color: #333;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .badge {
            background: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 5px;
        }
        
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        
        .no-applications {
            text-align: center;
            padding: 20px;
        }
        
        .no-applications h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        
        .no-applications p {
            font-size: 0.9rem;
            margin-bottom: 15px;
            color: #666;
        }
        
        .apply-now-btn {
            padding: 10px 20px;
            font-size: 0.9rem;
        }
        
        /* Fix iframe for mobile — JS sets exact height to content */
        #apply-iframe {
            min-height: 0;
            height: auto;
            width: 100%;
            border: none;
            max-width: 100%;
        }
        
        /* Fix main content display */
        .main-content {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            padding: 15px;
            margin: 0;
        }
        
        /* Fix content sections */
        .content-section {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden;
            margin: 0 !important;
            padding: 0 !important;
            display: none; /* Ensure all sections are hidden by default */
        }
        
        /* Profile summary section within dashboard */
        .profile-summary-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* Fix welcome card */
        .welcome-card {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden;
            word-wrap: break-word;
            margin: 0 0 20px 0 !important;
        }
        
        /* Fix application status */
        .application-status {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .status-card {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden;
            margin: 0 0 15px 0 !important;
        }
        
        /* Fix profile summary */
        .profile-summary {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden;
        }
        
        .profile-item {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden;
        }
        
        /* Profile dropdown mobile positioning */
        .profile-dropdown {
            right: 10px;
            width: 180px;
        }
        
        /* Notification dropdown mobile - full width, no overflow */
        .notification-dropdown {
            position: fixed !important;
            left: 12px !important;
            right: 12px !important;
            top: 56px !important;
            width: auto !important;
            max-width: none !important;
            max-height: 70vh !important;
        }
        
        /* Hide desktop-specific elements */
        .dashboard-container > .sidebar {
            display: block;
        }
    }
    
    @media (max-width: 480px) {
        .dashboard-header {
            padding: 8px 10px;
            min-height: 70px;
        }
        
        .logo {
            height: 25px;
            margin-right: 6px;
        }
        
        .brand {
            font-size: 1rem;
        }
        
        .welcome-text {
            font-size: 0.8rem;
            max-width: 160px;
        }
        
        .dashboard-container {
            padding-top: 70px;
            padding-bottom: 110px; /* Much more space for bottom navigation */
            min-height: calc(100vh - 180px); /* Account for header and bottom nav */
            min-height: calc(100dvh - 180px - env(safe-area-inset-bottom, 0px));
            overflow-y: auto; /* Enable vertical scrolling */
        }
        
        .main-content {
            padding: 10px 10px 110px 10px; /* Much more bottom padding for mobile nav */
            min-height: calc(100vh - 200px);
            min-height: calc(100dvh - 200px - env(safe-area-inset-bottom, 0px));
        }
        
        .mobile-bottom-nav {
            height: 55px;
        }
        
        .mobile-nav-item {
            padding: 4px 2px;
            font-size: 0.65rem;
        }
        
        .mobile-nav-item:before {
            font-size: 1rem;
        }
        
        /* Mobile-friendly iframe for smaller screens */
        #apply-iframe {
            min-height: 0 !important;
            height: auto !important;
        }
        
        .main-content {
            padding: 10px;
        }
        
        .welcome-card {
            padding: 15px 10px;
        }
        
        .welcome-card h1 {
            font-size: 1.1rem;
        }
        
        .welcome-card p {
            font-size: 0.85rem;
        }
        
        .status-card {
            padding: 12px;
        }
        
        .status-badge {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
        
        .profile-item {
            padding: 12px;
        }
        
        .sidebar-nav a {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
        
        /* Ultra-mobile content fixes */
        .main-content {
            padding: 10px;
        }
        
        .welcome-card {
            padding: 15px 10px;
        }
        
        .welcome-card h1 {
            font-size: 1.1rem;
            line-height: 1.3;
        }
        
        .welcome-card p {
            font-size: 0.85rem;
            line-height: 1.3;
        }
        
        .status-card {
            padding: 12px;
        }
        
        .status-badge {
            padding: 6px 10px;
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
        
        .profile-item {
            padding: 12px;
        }
        
        .profile-item h4 {
            font-size: 0.85rem;
        }
        
        .profile-item p {
            font-size: 0.9rem;
        }
        
        .notification-dropdown {
            width: 280px;
        }
    }
    
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
    
    .notification-bell {
        color: inherit;
        flex-shrink: 0;
    }
    
    .notification-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #f44336;
        color: white;
        border-radius: 10px;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .notification-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: min(350px, calc(100vw - 24px));
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        z-index: 1001;
        max-height: 400px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
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
        background: #1a3876;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: background 0.2s;
    }
    
    .mark-all-read:hover {
        background: #152d5c;
    }
    
    .mark-all-read:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    .notification-list {
        max-height: 300px;
        overflow-y: auto;
        min-height: 60px;
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
        background-color: #e8f0fe;
        border-left: 4px solid #1a3876;
        padding-left: 19px;
    }
    
    .notification-title {
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }

    .notification-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
    }

    .notification-type {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #eef3ff;
        color: #1a3876;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .notification-message strong {
        font-weight: 700;
        color: #1a237e;
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
        padding: 24px 20px;
        text-align: center;
        color: #888;
        font-size: 14px;
    }
    
    .notification-loading {
        padding: 24px;
        text-align: center;
        color: #888;
        font-size: 14px;
    }
    
    /* Fixed layout styles */
    body {
        margin: 0;
        padding: 0;
        height: 80vh;
        overflow: auto;
    }
    
    html {
        overflow: auto;
    }
    
    /* Mobile body improvements */
    @media (max-width: 768px) {
        body {
            overflow-x: hidden !important;
            overflow-y: auto !important; /* Enable vertical scrolling */
            -webkit-text-size-adjust: 100% !important;
            -webkit-tap-highlight-color: transparent !important;
            height: 100vh !important; height: 100dvh !important; max-height: 100dvh !important;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }
        
        html {
            overflow-x: hidden !important;
            overflow-y: auto !important; /* Enable vertical scrolling */
            height: 100vh !important; height: 100dvh !important; max-height: 100dvh !important;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }
        
        * {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* Prevent horizontal scroll */
        .main-content {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        /* Fix all content containers */
        .dashboard-container {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Fix text overflow */
        .welcome-card h1,
        .welcome-card p,
        .section-title,
        .status-card h3 {
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }
        
        /* Fix status badges */
        .status-badge {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
        
        /* Fix profile items */
        .profile-item h4,
        .profile-item p {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        /* Touch-friendly improvements */
        .sidebar-nav a, .apply-now-btn, .profile-icon, .notification-icon {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
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
        min-height: 100vh; min-height: 100dvh; /* Use min-height instead of fixed height */
        padding-top: 100px; /* Increased to account for header height */
        overflow: visible; /* Allow content to expand */
    }
    
    .sidebar {
        position: fixed;
        left: 0;
        top: 8.5%; /* Increased to account for header height */
        width: 250px;
        height: calc(100vh - 100px); height: calc(100dvh - 100px - env(safe-area-inset-bottom, 0px)); max-height: calc(100dvh - 100px - env(safe-area-inset-bottom, 0px));
        z-index: 999;
        background: #f8f9fa;
        border-right: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
    }

    /* Match Company sidebar visual style */
    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px 25px;
        color: #333;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }
    .sidebar-nav a i {
        font-size: 18px;
        width: 20px;
        text-align: center;
    }
    .sidebar-nav a:hover {
        background: #e9ecef;
        border-left-color: #1a3876;
    }
    .sidebar-nav a.active {
        background: #1a3876;
        color: #fff;
        border-left-color: #ffcb05;
        font-weight: 600;
    }
    .sidebar-nav a.logout {
        color: #f44336;
        margin-top: auto;
    }
    .sidebar-nav a.logout:hover {
        background: #ffebee;
        border-left-color: #f44336;
    }
    .sidebar-nav a.logout i {
        color: #f44336;
    }
    
    /* Hide mobile sidebar on desktop */
    @media (min-width: 769px) {
        .sidebar.mobile-nav {
            display: none !important;
        }
        
        /* Hide mobile bottom navigation on desktop */
        .mobile-bottom-nav {
            display: none !important;
        }
        
        /* Ensure desktop layout is clean */
        .dashboard-container {
            overflow: hidden;
        }
        
        .main-content {
            overflow-y: auto;
            overflow-x: hidden;
        }
    }
    
    /* Mobile: slide-out sidebar (like Employer) - hide bottom nav */
    @media (max-width: 768px) {
        /* Hide bottom nav - use slide-out sidebar instead */
        .mobile-bottom-nav {
            display: none !important;
        }
        
        /* Slide-out sidebar: hidden by default, slides in when .active */
        .sidebar.desktop-nav {
            display: flex !important;
            flex-direction: column !important;
            position: fixed !important;
            top: 56px !important;
            left: -200px !important;
            width: 200px !important;
            height: calc(100vh - 56px) !important; height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important; max-height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important;
            background: #e3eaff !important;
            z-index: 999 !important;
            transition: left 0.3s ease !important;
            padding: 20px 0 0 16px !important;
            box-shadow: 2px 0 12px rgba(0,0,0,0.15) !important;
            overflow-y: auto !important;
            border-radius: 0 !important;
        }
        
        .sidebar.desktop-nav.active {
            left: 0 !important;
        }
        
        .sidebar-nav {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 !important;
            padding: 0 !important;
        }
        
        .sidebar-nav li {
            margin: 0 0 4px 0 !important;
        }
        
        .sidebar-nav li:last-child {
            margin-top: auto !important;
            margin-bottom: 20px !important;
        }
        
        .sidebar-nav a {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            text-align: left !important;
            padding: 12px 16px !important;
            margin: 0 !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
            border-left: 3px solid transparent !important;
        }
        
        .sidebar-nav a:hover {
            background: #d1dbfa !important;
            color: #1a3876 !important;
            border-left-color: #1a3876 !important;
            padding-left: 20px !important;
            transform: translateX(2px) !important;
            box-shadow: 0 2px 8px rgba(26, 56, 118, 0.15) !important;
        }
        
        .sidebar-nav a:before {
            display: none !important;
        }
        
        /* Notification dropdown: full-width on mobile to prevent overlap */
        .notification-dropdown {
            position: fixed !important;
            left: 12px !important;
            right: 12px !important;
            top: 56px !important;
            width: auto !important;
            max-width: none !important;
            max-height: 70vh !important;
        }
        
        /* Override any desktop styles that might affect mobile */
        .main-content {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            left: 0 !important;
            right: 0 !important;
            height: auto !important;
            overflow: visible !important;
        }
        
        .dashboard-container {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            overflow: visible !important;
            padding-bottom: 24px !important;
        }
    }
    
    .main-content {
        margin-left: 250px;
        flex: 1;
        overflow: visible; /* Allow content to expand naturally */
        height: auto; /* Auto height to accommodate content */
        padding: 20px;
        position: relative;
        padding-bottom: 50px;
    }
    
    #apply-section {
        position: relative;
        z-index: 1;
        width: 96%;
        max-width: 100%;
    }

    #apply-container {
        width: 100%;
        max-width: 100%;
    }
    
    #apply-iframe {
        border: none;
        display: block;
        width: 100%;
        max-width: 100%;
        min-height: 0;
        height: auto;
    }
    
    /* Recommended Jobs Container Styling */
    #recommended-jobs-container {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    #recommended-jobs-iframe {
        border: none;
        display: block;
        width: 100%;
        max-width: 100%;
        min-height: 0;
        height: auto;
        
        border-radius: 8px;
    }
    
    /* Resume Builder Container Styling */
    #resume-container {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    #resume-iframe {
        border: none;
        display: block;
        width: 150%;
        max-width: 100%;
        min-height: 150vh; /* Ensure iframe has enough height for content */
        height: auto; /* Allow iframe to expand to its content */
        border-radius: 8px;
    }
    
    /* Announcements Container Styling */
    #announcements-container {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    #announcements-iframe {
        border: none;
        display: block;
        width: 100%;
        max-width: 100%;
        min-height: 100vh; min-height: 100dvh; /* Ensure iframe has enough height for content */
        height: auto; /* Allow iframe to expand to its content */
        border-radius: 8px;
    }
    
    /* Mobile responsive adjustments for recommended jobs */
    @media (max-width: 768px) {
        #apply-section {
            width: 100%;
            max-width: 100%;
        }

        #apply-container {
            height: calc(100vh - 140px);
            height: calc(100dvh - 140px - env(safe-area-inset-bottom, 0px));
            max-height: calc(100dvh - 140px - env(safe-area-inset-bottom, 0px));
            overflow: hidden;
            border-radius: 8px;
        }

        #apply-iframe {
            min-height: 100% !important;
            height: 100% !important;
            -webkit-overflow-scrolling: touch;
        }

        #recommended-jobs-container {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        
        #recommended-jobs-iframe {
            min-height: 0;
            height: auto;
            border-radius: 0;
        }
        
        #resume-container {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        
        #resume-iframe {
            min-height: 100vh; min-height: 100dvh;
            height: auto;
            border-radius: 0;
        }
        
        #announcements-container {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        
        #announcements-iframe {
            min-height: 100vh; min-height: 100dvh;
            height: auto;
            border-radius: 0;
        }
    }
    
    /* Facebook Link Styling */
    .facebook-link-container {
        margin-top: 20px;
        padding: 16px;
        background: linear-gradient(135deg, #1877f2 0%, #0d5fbf 100%);
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(24, 119, 242, 0.2);
        transition: all 0.3s ease;
    }
    
    .facebook-link-container:hover {
        box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
        transform: translateY(-2px);
    }
    
    .facebook-link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #ffffff;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    
    .facebook-link:hover {
        color: #ffffff;
        text-decoration: none;
        opacity: 0.9;
    }
    
    .facebook-link-icon {
        font-size: 1.2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
    }
    
    .facebook-link-text {
        letter-spacing: 0.3px;
    }
    
    @media (max-width: 768px) {
        .facebook-link-container {
            padding: 12px;
            margin-top: 16px;
        }
        
        .facebook-link {
            font-size: 0.85rem;
        }
        
        .facebook-link-icon {
            width: 24px;
            height: 24px;
            font-size: 1rem;
        }
    }
    
    /* Skills Ranking Styling */
    .skills-ranking-section {
        margin-top: 30px;
        margin-bottom: 30px;
    }
    
    .skills-ranking-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    
    .skill-card {
        background: linear-gradient(135deg, #e3f2fd, #f0f4ff);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 1px solid #bbdefb;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .skill-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.15);
    }
    
    .skill-card-icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    
    .skill-card-name {
        font-weight: 600;
        color: #1976d2;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    
    .skill-card-count {
        font-size: 2rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 4px;
    }
    
    .skill-card-percentage {
        font-size: 0.8rem;
        color: #666;
    }
    
    .skills-ranking-loading {
        text-align: center;
        padding: 40px;
        background: #f8f9fa;
        border-radius: 8px;
        color: #666;
    }
    
    @media (max-width: 768px) {
        .skills-ranking-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .skill-card {
            padding: 16px;
        }
        
        .skill-card-count {
            font-size: 1.5rem;
        }
    }
    
    /* Application Success Rate Styling */
    .success-rate-section {
        margin-top: 30px;
        margin-bottom: 30px;
    }
    
    /* Stack: top row = stat cards (Accepted / Rejected / Pending / Withdrawn); below = optional rejection panel */
    .success-rate-stack {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 20px;
    }
    
    .success-rate-cards-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 20px;
    }
    
    .success-rate-rejection-panel {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        text-align: left;
    }
    
    .success-rate-rejection-panel h3 {
        margin: 0 0 16px 0;
        color: #233a8b;
        font-size: 1.1rem;
    }
    
    .success-rate-rejection-panel .rejection-reasons {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }
    
    .success-rate-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .success-rate-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    
    .success-rate-card.accepted {
        border-top: 4px solid #4CAF50;
    }
    
    .success-rate-card.rejected {
        border-top: 4px solid #f44336;
    }
    
    .success-rate-card.pending {
        border-top: 4px solid #ff9800;
    }
    
    .success-rate-card.withdrawn {
        border-top: 4px solid #78909c;
    }
    
    .success-rate-number {
        font-size: 3rem;
        font-weight: 700;
        margin: 10px 0;
    }
    
    .success-rate-card.accepted .success-rate-number {
        color: #4CAF50;
    }
    
    .success-rate-card.rejected .success-rate-number {
        color: #f44336;
    }
    
    .success-rate-card.pending .success-rate-number {
        color: #ff9800;
    }
    
    .success-rate-card.withdrawn .success-rate-number {
        color: #546e7a;
    }
    
    .success-rate-label {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .success-rate-percentage {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1976d2;
    }
    
    .success-rate-card.withdrawn .success-rate-percentage {
        color: #546e7a;
    }
    
    .rejection-reasons {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #e3f2fd;
    }
    
    .rejection-reason-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .rejection-reason-item:last-child {
        border-bottom: none;
    }
    
    /* Skills Gap Analysis Styling */
    .skills-gap-section {
        margin-top: 30px;
        margin-bottom: 30px;
    }
    
    .gap-analysis-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .gap-analysis-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .gap-analysis-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    
    .match-score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        font-weight: 700;
        position: relative;
    }
    
    .match-score-circle.high {
        background: linear-gradient(135deg, #4CAF50, #81C784);
        color: white;
    }
    
    .match-score-circle.medium {
        background: linear-gradient(135deg, #ff9800, #ffb74d);
        color: white;
    }
    
    .match-score-circle.low {
        background: linear-gradient(135deg, #f44336, #e57373);
        color: white;
    }
    
    .recommendations-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .recommendation-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        margin-bottom: 8px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #1976d2;
    }
    
    .recommendation-item:hover {
        background: #e3f2fd;
    }
    
    .recommendation-percentage {
        font-size: 0.85rem;
        color: #666;
        background: #e3f2fd;
        padding: 4px 8px;
        border-radius: 12px;
    }
    
    /* Most Accepted Skills Styling */
    .most-accepted-skills-section {
        margin-top: 30px;
        margin-bottom: 30px;
    }
    
    .most-accepted-skills-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    
    .accepted-skill-card {
        background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 2px solid #4CAF50;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 2px 8px rgba(76, 175, 80, 0.1);
    }
    
    .accepted-skill-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
    }
    
    .accepted-skill-icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    
    .accepted-skill-name {
        font-weight: 600;
        color: #2e7d32;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    
    .accepted-skill-count {
        font-size: 2rem;
        font-weight: 700;
        color: #2e7d32;
        margin-bottom: 4px;
    }
    
    .accepted-skill-percentage {
        font-size: 0.8rem;
        color: #4CAF50;
        font-weight: 600;
    }
    
    .analytics-loading {
        text-align: center;
        padding: 40px;
        background: #f8f9fa;
        border-radius: 8px;
        color: #666;
    }
    
    @media (max-width: 992px) {
        .success-rate-cards-row {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .gap-analysis-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .most-accepted-skills-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .success-rate-card,
        .gap-analysis-card {
            padding: 20px;
        }
        
        .success-rate-number {
            font-size: 2.5rem;
        }
        
        .match-score-circle {
            width: 100px;
            height: 100px;
            font-size: 1.5rem;
        }
    }

    /* Parent-level modal host for iframe pages */
    #global-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 5000;
        background: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    }
    #global-modal-panel {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        max-width: 720px;
        width: min(92vw, 720px);
        max-height: 90vh;
        overflow-y: auto;
        padding: 24px;
    }
    #global-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    #global-modal-title {
        margin: 0;
        color: #233a8b;
        font-size: 1.4rem;
        line-height: 1.2;
    }
    #global-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #666;
        cursor: pointer;
        padding: 0 6px;
    }

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
                    👤
                </div>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['firstname']); ?> </span>
            </div>
            <div class="notification-container">
                <div class="notification-icon" onclick="toggleNotifications()">
                    <svg class="notification-bell" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span id="notificationBadge" class="notification-badge" style="display:none;">0</span>
                </div>
                <!-- Notification Dropdown (inside container for proper positioning) -->
                <div id="notificationDropdown" class="notification-dropdown" style="display:none;">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button type="button" onclick="markAllAsRead()" class="mark-all-read">Mark all as read</button>
                    </div>
                    <div id="notificationList" class="notification-list">
                        <div class="notification-loading">Loading...</div>
                    </div>
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

    <div class="dashboard-container">
        <!-- Desktop Sidebar -->
        <div class="sidebar desktop-nav">
            <ul class="sidebar-nav">
                <li><a href="#dashboard" class="active" onclick="showSection('dashboard')"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#recommended_jobs" onclick="showSection('recommended_jobs')"><i class="fas fa-briefcase"></i> Recommended Jobs <span class="badge" id="jobBadge" style="display:none;">New</span></a></li>
                <!--<li><a href="#resume" onclick="showSection('resume')">Resume Builder</a></li>-->
                <li><a href="#apply" onclick="showSection('apply')"><i class="fas fa-file-alt"></i> NSRP Registration</a></li>
                <li><a href="#follow_up" onclick="showSection('follow_up')"><i class="fas fa-comment-dots"></i> Request follow-up</a></li>
                <li><a href="#announcements" onclick="showSection('announcements')"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="#profile" onclick="showSection('profile')"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="#" class="logout" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section" style="display: block;"> 
                <div class="welcome-card">
                    <h1>Welcome to WorkConnect, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</h1>
                    <p>Track your job applications and manage your profile</p>
                </div>

                <div class="application-status">
                    <div class="status-card">
                        <h3>NSRP Form Status</h3>
                        <div style="margin-bottom: 15px;">
                            <span class="status-badge status-pending">Pending: <?php echo $status_counts_assoc['Pending'] ?? 0; ?></span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <span class="status-badge status-referred">Referred: <?php echo $status_counts_assoc['Referred'] ?? 0; ?></span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <span class="status-badge status-accepted">Accepted: <?php echo $status_counts_assoc['Accepted'] ?? 0; ?></span>
                        </div>
                        <div>
                            <span class="status-badge status-rejected">Rejected: <?php echo $status_counts_assoc['Rejected'] ?? 0; ?></span>
                        </div>
                    </div>

                    <div class="status-card">
                        <h3>Recent NSRP Form</h3>
                        <?php if (empty($applications)): ?>
                            <div class="no-applications">
                                <h3>No NSRP Form Yet</h3>
                                <p>Complete your NSRP registration to appear here.</p>
                                <button class="apply-now-btn" onclick="showSection('apply')">Go to NSRP Registration</button>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($applications, 0, 3) as $app): ?>
                                <?php 
                                    $app_status = !empty($app['application_status']) ? trim($app['application_status']) : 'Pending';
                                    $status_class = strtolower($app_status);
                                    
                                    // NSRP submission: month and year only
                                    $submission_date = '';
                                    if (!empty($app['submission_date']) && $app['submission_date'] !== '0000-00-00') {
                                        $ts = strtotime($app['submission_date']);
                                        $submission_date = $ts ? date('M Y', $ts) : '';
                                    }
                                    if ($submission_date === '' && !empty($app['submission_month']) && !empty($app['submission_year'])) {
                                        $submission_date = date('M Y', mktime(0, 0, 0, (int)$app['submission_month'], 1, (int)$app['submission_year']));
                                    }
                                    if ($submission_date === '') {
                                        $submission_date = 'Date not available';
                                    }
                                    
                                    $mn = trim((string)($app['middlename'] ?? ''));
                                    if ($mn === '' || strtolower($mn) === 'n/a') {
                                        $mn = '';
                                    }
                                    $full_name = trim(($app['firstname'] ?? '') . ($mn !== '' ? ' ' . $mn : '') . ' ' . ($app['surname'] ?? ''));
                                    if (empty($full_name)) {
                                        $full_name = 'NSRP form #' . $app['id'];
                                    }
                                ?>
                                <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($full_name); ?></strong>
                                            <br>
                                            <small style="color: #666;">Submitted: <?php echo htmlspecialchars($submission_date); ?></small>
                                        </div>
                                        <span class="status-badge status-<?php echo htmlspecialchars($status_class); ?>">
                                            <?php echo htmlspecialchars($app_status); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- New Features Summary -->
                

                <div class="profile-summary-section">
                    <h2 class="section-title">Profile Summary</h2>
                    <div class="profile-summary">
                        <div class="profile-item">
                            <h4>Name</h4>
                            <p><?php 
                                $firstname = $_SESSION['firstname'] ?? '';
                                $lastname = $_SESSION['lastname'] ?? $_SESSION['surname'] ?? '';
                                $full_name = trim($firstname . ' ' . $lastname);
                                echo htmlspecialchars($full_name ?: 'Not set');
                            ?></p>
                        </div>
                        <div class="profile-item">
                            <h4>Email</h4>
                            <p><?php echo htmlspecialchars($_SESSION['email'] ?? 'Not set'); ?></p>
                        </div>
                        <div class="profile-item">
                            <h4 title="Jobs you applied to from Recommended Jobs">Total Applications</h4>
                            <p><?php echo (int)$recommended_job_application_count; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Skills Ranking Section -->
                <div class="skills-ranking-section">
                    <h2 class="section-title">Most Common Skills</h2>
                    <div id="skillsRankingContainer" class="skills-ranking-grid">
                        <div class="skills-ranking-loading">
                            <div class="loading-spinner" style="margin: 0 auto 10px;"></div>
                            <p>Loading skills ranking...</p>
                        </div>
                    </div>
                </div>

                <!-- Application Success Rate Section -->
                <div class="success-rate-section">
                    <h2 class="section-title">Application Success Rate</h2>
                    <p style="color:#666;font-size:0.9rem;margin:-8px 0 16px 0;">Based on applications you submitted from <strong>Recommended Jobs</strong>. <strong>Withdrawn</strong> means the application was closed (e.g. you were accepted elsewhere).</p>
                    <div id="successRateContainer" class="success-rate-stack">
                        <div class="analytics-loading">
                            <div class="loading-spinner" style="margin: 0 auto 10px;"></div>
                            <p>Loading success rate data...</p>
                        </div>
                    </div>
                </div>

                <!-- Most Accepted Skills Section -->
                <div class="most-accepted-skills-section">
                    <h2 class="section-title">Most Accepted Skills</h2>
                    <p style="color: #666; margin-bottom: 10px; font-size: 0.9rem;">Skills that accepted jobseekers have - what employers value most</p>
                    <div id="mostAcceptedSkillsContainer" class="most-accepted-skills-grid">
                        <div class="analytics-loading">
                            <div class="loading-spinner" style="margin: 0 auto 10px;"></div>
                            <p>Loading most accepted skills...</p>
                        </div>
                    </div>
                </div>

                <!-- Skills Gap Analysis Section -->
                <div class="skills-gap-section">
                    <h2 class="section-title">Skills Gap Analysis</h2>
                    <div id="skillsGapContainer" class="gap-analysis-grid">
                        <div class="analytics-loading">
                            <div class="loading-spinner" style="margin: 0 auto 10px;"></div>
                            <p>Loading skills gap analysis...</p>
                        </div>
                    </div>
                </div>

                <!-- Latest Announcements Section -->
                <div class="announcements-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 class="section-title">Latest Announcements</h2>
                        <a href="#" onclick="showSection('announcements')" style="color: #233a8b; text-decoration: none; font-weight: 600;">View All →</a>
                    </div>
                    <div id="latestAnnouncements" style="display: grid; gap: 16px;">
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; color: #666;">
                            <div class="loading-spinner" style="margin: 0 auto 10px;"></div>
                            Loading announcements...
                        </div>
                    </div>
                    <!-- Facebook Link -->
                    <div class="facebook-link-container">
                        <a href="https://www.facebook.com/NorzagarayPESO2021?rdid=NI8HgiwxTPYigG4o&share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F1862uYXDHX%2F#" target="_blank" rel="noopener noreferrer" class="facebook-link">
                            <span class="facebook-link-icon">📘</span>
                            <span class="facebook-link-text">Follow Us on Facebook</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recommended Jobs Section -->
            <div id="recommended_jobs-section" class="content-section" style="display: none;"> 
                <h2 class="section-title">Recommended Jobs</h2>
                <div id="recommended-jobs-container">
                    <div id="loading-indicator-jobs" style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                        <div class="loading-spinner"></div>
                        <p style="margin: 10px 0 0 0; color: #666;">Loading recommended jobs...</p>
                    </div>
                    <iframe id="recommended-jobs-iframe" src="recommended_jobs.php?session_id=<?php echo session_id(); ?>&user_id=<?php echo $_SESSION['user_id']; ?>&token=<?php echo $session_token; ?>" width="100%" frameborder="0" scrolling="no"></iframe>
                </div>
            </div>

            <!-- Resume Builder Section - DISABLED
            <div id="resume-section" class="content-section" style="display: none;"> 
                <h2 class="section-title">Resume Builder</h2>
                <div id="resume-container">
                    <div id="loading-indicator-resume" style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                        <div class="loading-spinner"></div>
                        <p style="margin: 10px 0 0 0; color: #666;">Loading resume builder...</p>
                    </div>
                    <iframe id="resume-iframe" src="resume_builder.php?session_id=<?php echo session_id(); ?>&user_id=<?php echo $_SESSION['user_id']; ?>&token=<?php echo $session_token; ?>" width="100%" frameborder="0" scrolling="no"></iframe>
                </div>
            </div>
            -->

            <!-- Follow-up Request Section -->
            <div id="follow_up-section" class="content-section" style="display: none;">
                <h2 class="section-title">Request follow-up</h2>
                <div id="follow_up_content" style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div id="follow_up_loading" style="text-align: center;">
                        <div class="loading-spinner"></div>
                        <p style="margin: 10px 0 0 0; color: #666;">Checking eligibility...</p>
                    </div>
                    <div id="follow_up_body" style="display: none;"></div>
                </div>
            </div>

            <!-- Apply Section -->
            <div id="apply-section" class="content-section" style="display: none;"> 
                <h2 class="section-title">Jobseeker Registration Form</h2>
                <div id="apply-container">
                    <div id="loading-indicator" style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                        <div class="loading-spinner"></div>
                        <p style="margin: 10px 0 0 0; color: #666;">Loading application form for your session...</p>
                    </div>
                    <iframe id="apply-iframe" src="apply.php?session_id=<?php echo session_id(); ?>&user_id=<?php echo $_SESSION['user_id']; ?>&token=<?php echo $session_token; ?>" width="100%" frameborder="0" scrolling="yes" style="border-radius: 8px; border: none; height: auto;"></iframe>
                </div>
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
                    <h3 style="color: #1a3876; margin-bottom: 15px;">NSRP Form</h3>
                    <?php if (empty($applications)): ?>
                        <p style="color: #666;">No NSRP form submitted yet.</p>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <?php 
                                $app_status = !empty($app['application_status']) ? trim($app['application_status']) : 'Pending';
                                $status_class = strtolower($app_status);
                                
                                $submission_date = '';
                                if (!empty($app['submission_date']) && $app['submission_date'] !== '0000-00-00') {
                                    $ts = strtotime($app['submission_date']);
                                    $submission_date = $ts ? date('M j, Y', $ts) : '';
                                }
                                if ($submission_date === '' && !empty($app['submission_month']) && !empty($app['submission_year'])) {
                                    $submission_date = date('M j, Y', mktime(0, 0, 0, (int)$app['submission_month'], 1, (int)$app['submission_year']));
                                }
                                if ($submission_date === '') {
                                    $submission_date = 'Date not available';
                                }
                                
                                $mn = trim((string)($app['middlename'] ?? ''));
                                if ($mn === '' || strtolower($mn) === 'n/a') {
                                    $mn = '';
                                }
                                $full_name = trim(($app['firstname'] ?? '') . ($mn !== '' ? ' ' . $mn : '') . ' ' . ($app['surname'] ?? ''));
                                if (empty($full_name)) {
                                    $full_name = 'NSRP form #' . $app['id'];
                                }
                            ?>
                            <div style="margin-bottom: 15px; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #1a3876;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($full_name); ?></strong>
                                        <br>
                                        <small style="color: #666;">
                                            Submitted: <?php echo htmlspecialchars($submission_date); ?>
                                            <?php if (!empty($app['occupation1'])): ?>
                                                | Preferred occupation: <?php echo htmlspecialchars($app['occupation1']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="status-badge status-<?php echo htmlspecialchars($status_class); ?>">
                                        <?php echo htmlspecialchars($app_status); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Announcements Section -->
            <div id="announcements-section" class="content-section" style="display: none;">
                <h2 class="section-title">Announcements</h2>
                <div id="announcements-container">
                    <div id="loading-indicator-announcements" style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                        <div class="loading-spinner"></div>
                        <p style="margin: 10px 0 0 0; color: #666;">Loading announcements...</p>
                    </div>
                    <iframe id="announcements-iframe" src="announcements.php?session_id=<?php echo session_id(); ?>&user_id=<?php echo $_SESSION['user_id']; ?>&token=<?php echo $session_token; ?>" width="100%" frameborder="0" scrolling="no" style="border-radius: 8px; border: none; height: auto; min-height: 100vh; min-height: 100dvh;"></iframe>
                </div>
                <!-- Facebook Link -->
                <div class="facebook-link-container" style="margin-top: 20px;">
                    <a href="https://www.facebook.com/NorzagarayPESO2021?rdid=NI8HgiwxTPYigG4o&share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F1862uYXDHX%2F#" target="_blank" rel="noopener noreferrer" class="facebook-link">
                        <span class="facebook-link-icon">📘</span>
                        <span class="facebook-link-text">Follow Us on Facebook</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation - Outside dashboard container -->
    <div class="mobile-bottom-nav">
        <div class="mobile-nav-item active" data-section="dashboard" onclick="showSection('dashboard')">Dashboard</div>
        <div class="mobile-nav-item" data-section="recommended_jobs" onclick="showSection('recommended_jobs')">Jobs</div>
        <div class="mobile-nav-item" data-section="apply" onclick="showSection('apply')">NSRP</div>
        <div class="mobile-nav-item" data-section="follow_up" onclick="showSection('follow_up')">Follow-up</div>
        <div class="mobile-nav-item" data-section="announcements" onclick="showSection('announcements')">News</div>
        <div class="mobile-nav-item" data-section="profile" onclick="showSection('profile')">Profile</div>
    </div>

    <div id="global-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="global-modal-title">
        <div id="global-modal-panel">
            <div id="global-modal-header">
                <h3 id="global-modal-title">Details</h3>
                <button id="global-modal-close" type="button" aria-label="Close modal">&times;</button>
            </div>
            <div id="global-modal-content"></div>
        </div>
    </div>

    <script>
        window.showGlobalModal = function (payload) {
            const overlay = document.getElementById('global-modal-overlay');
            const title = document.getElementById('global-modal-title');
            const content = document.getElementById('global-modal-content');
            if (!overlay || !title || !content) return;
            title.textContent = payload && payload.title ? payload.title : 'Details';
            content.innerHTML = payload && payload.html ? payload.html : '';
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        };

        window.closeGlobalModal = function () {
            const overlay = document.getElementById('global-modal-overlay');
            if (!overlay) return;
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        };

        // Handle URL hash changes (for direct links)
        function handleHashChange() {
            const hash = window.location.hash.substring(1); // Remove the #
            if (hash && ['dashboard', 'recommended_jobs', 'apply', 'follow_up', 'announcements', 'profile'].includes(hash)) {
                showSection(hash);
            }
        }
        
        // Listen for hash changes
        window.addEventListener('hashchange', handleHashChange);
        
        // Reload dashboard when NRSP form is submitted in iframe so status / recommended jobs reflect the submission
        window.addEventListener('message', function(e) {
            if (e.data && e.data.type === 'nrsp_submitted') {
                location.reload();
            }
            if (e.data && e.data.type === 'showModal') {
                window.showGlobalModal(e.data.payload || {});
            }
            if (e.data && e.data.type === 'hideModal') {
                window.closeGlobalModal();
            }
        });
        
        // Check hash on page load
        document.addEventListener('DOMContentLoaded', function() {
            handleHashChange();
            const closeBtn = document.getElementById('global-modal-close');
            const overlay = document.getElementById('global-modal-overlay');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    window.closeGlobalModal();
                });
            }
            if (overlay) {
                overlay.addEventListener('click', function (event) {
                    if (event.target === overlay) {
                        window.closeGlobalModal();
                    }
                });
            }
        });
        
        // Function to hide loading indicator with timer
        function hideLoadingIndicator() {
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) {
                // Hide after 2 seconds to ensure iframe content is loaded
                setTimeout(() => {
                    loadingIndicator.style.display = 'none';
                }, 2000);
            }
        }
        
        // Function to hide recommended jobs loading indicator with timer
        function hideRecommendedJobsLoadingIndicator() {
            const loadingIndicator = document.getElementById('loading-indicator-jobs');
            if (loadingIndicator) {
                // Hide after 2 seconds to ensure iframe content is loaded
                setTimeout(() => {
                    loadingIndicator.style.display = 'none';
                }, 2000);
            }
        }
        
        // Function to hide resume loading indicator with timer
        function hideResumeLoadingIndicator() {
            const loadingIndicator = document.getElementById('loading-indicator-resume');
            if (loadingIndicator) {
                // Hide after 2 seconds to ensure iframe content is loaded
                setTimeout(() => {
                    loadingIndicator.style.display = 'none';
                }, 2000);
            }
        }
        
        // Function to hide announcements loading indicator with timer
        function hideAnnouncementsLoadingIndicator() {
            const loadingIndicator = document.getElementById('loading-indicator-announcements');
            if (loadingIndicator) {
                // Hide after 2 seconds to ensure iframe content is loaded
                setTimeout(() => {
                    loadingIndicator.style.display = 'none';
                }, 2000);
            }
        }

        /**
         * NSRP apply iframe: height = full document inside iframe so ONLY the dashboard scrolls
         * (no second scrollbar inside the iframe). Uses ResizeObserver + delayed remeasures for mobile WebKit.
         */
        function setupApplyIframeAutoResize() {
            const iframe = document.getElementById('apply-iframe');
            if (!iframe) return;

            const minH = 120;
            let intervalId = null;
            let lastAppliedHeight = 0;
            let resizeObserver = null;
            const isMobileView = function () {
                return window.matchMedia('(max-width: 768px)').matches;
            };
            const applyMobileHeight = function () {
                const container = document.getElementById('apply-container');
                const mobileH = Math.max(420, window.innerHeight - 140);
                if (container) {
                    container.style.height = mobileH + 'px';
                    container.style.overflow = 'hidden';
                }
                iframe.style.height = mobileH + 'px';
                iframe.style.minHeight = mobileH + 'px';
            };

            const applyHeight = function (heightPx, force) {
                const h = Math.max(minH, Math.round(Number(heightPx) || 0));
                if (!force && Math.abs(h - lastAppliedHeight) < 2) return;
                iframe.style.height = h + 'px';
                iframe.style.maxHeight = 'none';
                lastAppliedHeight = h;
            };

            const measureHeight = function (force) {
                if (isMobileView()) {
                    applyMobileHeight();
                    return;
                }
                if (force) lastAppliedHeight = 0;
                try {
                    const doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
                    if (!doc) return;
                    const b = doc.body;
                    const e = doc.documentElement;
                    /* Do not use html.clientHeight — it tracks iframe viewport and breaks tall content */
                    const contentHeight = Math.max(
                        b ? b.scrollHeight : 0,
                        b ? b.offsetHeight : 0,
                        e ? e.scrollHeight : 0,
                        e ? e.offsetHeight : 0
                    );
                    applyHeight(contentHeight, !!force);
                } catch (err) {
                    applyHeight(minH, true);
                }
            };

            const bindResizeObserver = function () {
                try {
                    if (resizeObserver) resizeObserver.disconnect();
                    const doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
                    if (!doc || !doc.body) return;
                    resizeObserver = new ResizeObserver(function () {
                        measureHeight(true);
                    });
                    resizeObserver.observe(doc.body);
                    resizeObserver.observe(doc.documentElement);
                } catch (e) { /* ResizeObserver unsupported */ }
            };

            if (isMobileView()) {
                iframe.setAttribute('scrolling', 'yes');
                iframe.style.overflow = 'auto';
                applyMobileHeight();
            } else {
                iframe.setAttribute('scrolling', 'no');
                iframe.style.overflow = 'hidden';
            }

            iframe.addEventListener('load', function () {
                measureHeight(true);
                bindResizeObserver();
                if (intervalId) clearInterval(intervalId);
                intervalId = setInterval(function () { measureHeight(false); }, 700);
                [40, 120, 350, 900, 1800].forEach(function (ms) {
                    setTimeout(function () { measureHeight(true); }, ms);
                });
            });

            const onViewportChange = function () {
                const container = document.getElementById('apply-container');
                if (isMobileView()) {
                    iframe.setAttribute('scrolling', 'yes');
                    iframe.style.overflow = 'auto';
                    applyMobileHeight();
                } else {
                    if (container) {
                        container.style.height = '';
                        container.style.overflow = '';
                    }
                    iframe.setAttribute('scrolling', 'no');
                    iframe.style.overflow = 'hidden';
                    iframe.style.minHeight = '';
                }
                measureHeight(true);
                [80, 280, 600].forEach(function (ms) {
                    setTimeout(function () { measureHeight(true); }, ms);
                });
            };
            window.addEventListener('resize', onViewportChange);
            window.addEventListener('orientationchange', onViewportChange);

            window.addEventListener('message', function (ev) {
                if (!ev.data || ev.data.type !== 'workconnect-resize-apply' || ev.data.source !== 'apply') return;
                measureHeight(true);
                setTimeout(function () { measureHeight(true); }, 120);
            });

            window._wcResizeApplyIframe = function () { measureHeight(true); };

            measureHeight(true);
        }

        function setupIframeAutoResize(iframeId, minHeight = 700, postMessageSource = null) {
            const iframe = document.getElementById(iframeId);
            if (!iframe) return;

            let intervalId = null;
            let lastAppliedHeight = 0;
            const applyHeight = (height) => {
                const safeHeight = Math.max(minHeight, Number(height) || 0);
                // Prevent resize feedback loops: only apply meaningful height changes.
                if (Math.abs(safeHeight - lastAppliedHeight) < 4) return;
                iframe.style.height = safeHeight + 'px';
                lastAppliedHeight = safeHeight;
            };

            const measureHeight = () => {
                try {
                    const doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
                    if (!doc) return;
                    const body = doc.body;
                    const html = doc.documentElement;
                    const mc = doc.querySelector('.main-content');
                    let contentHeight = Math.max(
                        body ? body.scrollHeight : 0,
                        body ? body.offsetHeight : 0,
                        html ? html.scrollHeight : 0,
                        html ? html.offsetHeight : 0,
                        mc ? mc.scrollHeight : 0
                    );
                    const posted = parseInt(iframe.getAttribute('data-wc-posted-height') || '0', 10);
                    if (posted > 0) {
                        contentHeight = Math.max(contentHeight, posted);
                    }
                    applyHeight(contentHeight);
                } catch (e) {
                    // Keep min height when iframe document is not accessible.
                    applyHeight(minHeight);
                }
            };

            if (postMessageSource) {
                window.addEventListener('message', function wcIframeHeightMsg(ev) {
                    if (!ev.data || ev.data.type !== 'workconnect-resize-iframe') return;
                    if (ev.data.source !== postMessageSource) return;
                    if (iframe.contentWindow !== ev.source) return;
                    const h = Math.max(minHeight, parseInt(ev.data.height, 10) || 0);
                    iframe.setAttribute('data-wc-posted-height', String(h));
                    measureHeight();
                });
            }

            iframe.setAttribute('scrolling', 'no');
            iframe.style.overflow = 'hidden';
            iframe.addEventListener('load', function() {
                iframe.removeAttribute('data-wc-posted-height');
                measureHeight();
                if (intervalId) clearInterval(intervalId);
                intervalId = setInterval(measureHeight, 800);
            });
            const onViewportChange = function () {
                /* Do not clear iframe height here: on mobile Chrome, clearing + remeasure makes the
                   iframe document report a collapsed scrollHeight (matches short iframe viewport). */
                iframe.setAttribute('scrolling', 'no');
                iframe.style.overflow = 'hidden';
                measureHeight();
                [80, 280, 600].forEach(function (ms) {
                    setTimeout(measureHeight, ms);
                });
            };
            window.addEventListener('resize', onViewportChange);
            window.addEventListener('orientationchange', onViewportChange);
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', onViewportChange);
            }
            measureHeight();
        }

        function forceResizeIframe(iframeId, minHeight) {
            const iframe = document.getElementById(iframeId);
            if (!iframe) return;
            try {
                const doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
                if (!doc) return;
                const body = doc.body;
                const html = doc.documentElement;
                const mc = doc.querySelector('.main-content');
                let contentHeight = Math.max(
                    body ? body.scrollHeight : 0,
                    body ? body.offsetHeight : 0,
                    html ? html.scrollHeight : 0,
                    html ? html.offsetHeight : 0,
                    mc ? mc.scrollHeight : 0
                );
                const posted = parseInt(iframe.getAttribute('data-wc-posted-height') || '0', 10);
                if (posted > 0) {
                    contentHeight = Math.max(contentHeight, posted);
                }
                const safeHeight = Math.max(minHeight || 200, Number(contentHeight) || 0);
                iframe.style.height = safeHeight + 'px';
            } catch (e) {
                // Ignore cross-document measurement failures.
            }
        }
        
        // Function to handle recommended jobs iframe loading
        function handleRecommendedJobsIframeLoad() {
            const iframe = document.getElementById('recommended-jobs-iframe');
            const loadingIndicator = document.getElementById('loading-indicator-jobs');
            
            if (iframe && loadingIndicator) {
                iframe.onload = function() {
                    // Hide loading indicator when iframe loads
                    setTimeout(() => {
                        loadingIndicator.style.display = 'none';
                    }, 500);
                };
                
                // Handle iframe load error
                iframe.onerror = function() {
                    loadingIndicator.innerHTML = '<p style="color: #f44336;">Error loading recommended jobs. Please try again.</p>';
                };
            }
        }
        
        // Function to handle resume builder iframe loading
        function handleResumeIframeLoad() {
            const iframe = document.getElementById('resume-iframe');
            const loadingIndicator = document.getElementById('loading-indicator-resume');
            
            if (iframe && loadingIndicator) {
                iframe.onload = function() {
                    // Hide loading indicator when iframe loads
                    setTimeout(() => {
                        loadingIndicator.style.display = 'none';
                    }, 500);
                };
                
                // Handle iframe load error
                iframe.onerror = function() {
                    loadingIndicator.innerHTML = '<p style="color: #f44336;">Error loading resume builder. Please try again.</p>';
                };
            }
        }
        
        // Auto-hide loading indicator when apply section is shown
        function showSection(section) {
            // Close mobile slide-out sidebar when navigating
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar.desktop-nav');
                const hamburger = document.getElementById('hamburgerMenu');
                if (sidebar) sidebar.classList.remove('active');
                if (hamburger) hamburger.classList.remove('active');
            }
            // Update URL hash
            window.location.hash = section;
            
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(sec => {
                sec.style.display = 'none';
            });
            
            // Show selected section
            const targetSection = document.getElementById(section + '-section');
            if (targetSection) {
                targetSection.style.display = 'block';
                
                // If showing apply section, hide loading indicator after delay
                if (section === 'apply') {
                    hideLoadingIndicator();
                    /* Iframe was display:none — remeasure height so parent scroll matches full form */
                    setTimeout(function () {
                        if (typeof window._wcResizeApplyIframe === 'function') window._wcResizeApplyIframe();
                        forceResizeIframe('apply-iframe', 120);
                    }, 50);
                    setTimeout(function () {
                        if (typeof window._wcResizeApplyIframe === 'function') window._wcResizeApplyIframe();
                        forceResizeIframe('apply-iframe', 120);
                    }, 400);
                }
                
                // If showing recommended jobs section, hide loading indicator after delay
                if (section === 'recommended_jobs') {
                    hideRecommendedJobsLoadingIndicator();
                    setTimeout(function () { forceResizeIframe('recommended-jobs-iframe', 200); }, 60);
                    setTimeout(function () { forceResizeIframe('recommended-jobs-iframe', 200); }, 350);
                }
                
                // If showing resume section, hide loading indicator after delay
                if (section === 'resume') {
                    hideResumeLoadingIndicator();
                }
                
                // If showing announcements section, hide loading indicator after delay
                if (section === 'announcements') {
                    hideAnnouncementsLoadingIndicator();
                }
                
                // If showing follow-up section, load eligibility and render content
                if (section === 'follow_up') {
                    loadFollowUpSection();
                }
            }
            
            // Update active nav item
            document.querySelectorAll('.sidebar-nav a').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('href') === '#' + section) {
                    item.classList.add('active');
                }
            });
            
            // Update mobile nav
            document.querySelectorAll('.mobile-nav-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('data-section') === section) {
                    item.classList.add('active');
                }
            });
        }
        
        function loadFollowUpSection() {
            const loadingEl = document.getElementById('follow_up_loading');
            const bodyEl = document.getElementById('follow_up_body');
            if (!loadingEl || !bodyEl) return;
            loadingEl.style.display = 'block';
            bodyEl.style.display = 'none';
            bodyEl.innerHTML = '';
            fetch('check_follow_up_eligibility.php')
                .then(r => r.json())
                .then(data => {
                    loadingEl.style.display = 'none';
                    bodyEl.style.display = 'block';
                    if (!data.success) {
                        bodyEl.innerHTML = '<p style="color: #666;">Unable to check eligibility. Please try again.</p>';
                        return;
                    }
                    if (!data.eligible) {
                        bodyEl.innerHTML = '<p style="color: #666;">' + (data.message || 'You have no pending or referred application. Follow-up requests are only available when your application status is Pending or Referred.') + '</p>';
                        return;
                    }
                    var requests = data.requests || [];
                    var html = '';
                    if (data.already_pending) {
                        html += '<p style="color: #856404; background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 20px;">You have a pending follow-up request. You will be notified when admin responds.</p>';
                    }
                    if (requests.length > 0) {
                        var answeredCount = requests.filter(function(r) { return r.status !== 'pending'; }).length;
                        html += '<h3 style="color: #233a8b; margin: 0 0 12px 0; font-size: 1.1rem;">Past requests</h3>';
                        if (answeredCount > 0) {
                            html += '<div class="bulk-actions-fu" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap;"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" id="fuSelectAll"> Select all</label><button type="button" class="btn-delete-selected-fu" id="fuDeleteSelected" disabled style="background:#d32f2f;color:#fff;padding:8px 16px;border-radius:8px;border:none;font-weight:600;cursor:pointer;font-size:0.9rem;">Delete selected</button></div>';
                        }
                        html += '<div style="margin-bottom: 24px;" id="follow_up_cards_container">';
                        function formatPhTime(isoStr) {
                            if (!isoStr) return '';
                            var d = new Date(isoStr);
                            return d.toLocaleString('en-PH', { timeZone: 'Asia/Manila', dateStyle: 'medium', timeStyle: 'short' });
                        }
                        requests.forEach(function(req, idx) {
                            var dateStr = formatPhTime(req.created_at);
                            var isPending = req.status === 'pending';
                            html += '<div class="follow-up-card" data-request-id="' + req.id + '" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 16px; margin-bottom: 12px; background: ' + (isPending ? '#fffbf0' : '#fff') + '; border-left: 4px solid ' + (isPending ? '#ffc107' : '#4caf50') + ';">';
                            html += '<p style="font-size: 0.85rem; color: #666; margin: 0 0 10px 0;">Requested: ' + dateStr + ' <span style="color:#888;font-size:0.8rem;">(PH time)</span>' + (isPending ? ' <span style="background:#ffc107;color:#333;padding:2px 8px;border-radius:4px;font-weight:600;">Pending</span>' : '') + '</p>';
                            var msg = (req.message && req.message.trim() !== '') ? escapeHtml(req.message) : '<em style="color:#999;">No message</em>';
                            html += '<p style="color: #666; margin-bottom: 6px; font-size: 0.9rem;">Your message:</p><div style="background: #f5f5f5; padding: 12px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;">' + msg + '</div>';
                            if (isPending) {
                                html += '<p style="color: #856404; font-size: 0.9rem; margin: 0;">Awaiting admin response.</p>';
                            } else {
                                var resp = (req.admin_response && req.admin_response.trim() !== '') ? escapeHtml(req.admin_response) : '<em style="color:#999;">No response text</em>';
                                html += '<p style="color: #666; margin-bottom: 6px; font-size: 0.9rem;">Admin response:</p><div style="background: #e8f5e9; padding: 12px; border-radius: 6px; margin-bottom: 8px; font-size: 0.9rem;">' + resp + '</div>';
                                if (req.responded_at) html += '<p style="font-size: 0.8rem; color: #666; margin: 0;">Responded: ' + formatPhTime(req.responded_at) + ' <span style="color:#888;font-size:0.75rem;">(PH time)</span></p>';
                            }
                            html += '<div style="margin-top:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';
                            if (isPending) {
                                html += '<span style="font-size:0.85rem;color:#999;">Delete available after admin responds.</span>';
                            } else {
                                html += '<label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.9rem;"><input type="checkbox" class="fu-card-checkbox" value="' + req.id + '" data-answered="1"> Select</label><button type="button" class="btn-delete-fu" data-id="' + req.id + '" style="background:#f44336;color:#fff;padding:6px 12px;border-radius:6px;border:none;font-size:0.85rem;cursor:pointer;">Delete</button>';
                            }
                            html += '</div>';
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    if (!data.already_pending) {
                        html += '<p style="color: #333; margin-bottom: 15px;">You have a pending or referred application. You can request a follow-up below. Admin will be notified and may respond via your notifications.</p>';
                        html += '<textarea id="follow_up_message" placeholder="Add a message for admin (required)..." style="width:100%; min-height: 100px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; box-sizing: border-box;"></textarea>';
                        html += '<button type="button" class="apply-now-btn" id="submit_follow_up_btn">Submit follow-up request</button>';
                    }
                    bodyEl.innerHTML = html;
                    if (!data.already_pending) {
                        var btn = document.getElementById('submit_follow_up_btn');
                        if (btn) btn.onclick = submitFollowUpRequest;
                    }
                    if (requests.length > 0) {
                        var fuSelectAll = bodyEl.querySelector('#fuSelectAll');
                        var fuDeleteSelectedBtn = bodyEl.querySelector('#fuDeleteSelected');
                        var fuCheckboxes = bodyEl.querySelectorAll('.fu-card-checkbox');
                        function updateFuDeleteBtn() {
                            var n = bodyEl.querySelectorAll('.fu-card-checkbox:checked').length;
                            if (fuDeleteSelectedBtn) fuDeleteSelectedBtn.disabled = n === 0;
                            if (fuSelectAll) fuSelectAll.checked = n > 0 && n === fuCheckboxes.length;
                        }
                        if (fuSelectAll) fuSelectAll.addEventListener('change', function() { fuCheckboxes.forEach(function(cb) { cb.checked = fuSelectAll.checked; }); updateFuDeleteBtn(); });
                        fuCheckboxes.forEach(function(cb) { cb.addEventListener('change', updateFuDeleteBtn); });
                        if (fuDeleteSelectedBtn) fuDeleteSelectedBtn.addEventListener('click', function() {
                            var ids = []; bodyEl.querySelectorAll('.fu-card-checkbox:checked').forEach(function(cb) { ids.push(parseInt(cb.value, 10)); });
                            if (ids.length === 0) return;
                            Swal.fire({ title: 'Delete requests?', text: 'Permanently delete ' + ids.length + ' request(s)?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#666', confirmButtonText: 'Delete' }).then(function(r) {
                                if (!r.isConfirmed) return;
                                fuDeleteSelectedBtn.disabled = true;
                                fetch('delete_follow_up_request.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: ids }) })
                                    .then(function(res) { return res.json(); })
                                    .then(function(data) { if (data.success) { Swal.fire({ title: 'Deleted', text: data.message, icon: 'success' }); loadFollowUpSection(); } else { Swal.fire({ title: 'Error', text: data.message || 'Failed.', icon: 'error' }); fuDeleteSelectedBtn.disabled = false; } })
                                    .catch(function() { Swal.fire({ title: 'Error', text: 'Request failed.', icon: 'error' }); fuDeleteSelectedBtn.disabled = false; });
                            });
                        });
                        bodyEl.querySelectorAll('.btn-delete-fu').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                var id = parseInt(this.getAttribute('data-id'), 10);
                                Swal.fire({ title: 'Delete this request?', text: 'This conversation will be permanently deleted.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#666', confirmButtonText: 'Delete' }).then(function(r) {
                                    if (!r.isConfirmed) return;
                                    fetch('delete_follow_up_request.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: [id] }) })
                                        .then(function(res) { return res.json(); })
                                        .then(function(data) { if (data.success) { Swal.fire({ title: 'Deleted', text: data.message, icon: 'success' }); loadFollowUpSection(); } else { Swal.fire({ title: 'Error', text: data.message || 'Failed.', icon: 'error' }); } })
                                        .catch(function() { Swal.fire({ title: 'Error', text: 'Request failed.', icon: 'error' }); });
                                });
                            });
                        });
                    }
                })
                .catch(() => {
                    loadingEl.style.display = 'none';
                    bodyEl.style.display = 'block';
                    bodyEl.innerHTML = '<p style="color: #666;">Unable to load. Please try again.</p>';
                });
        }
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        function submitFollowUpRequest() {
            const btn = document.getElementById('submit_follow_up_btn');
            const messageEl = document.getElementById('follow_up_message');
            const message = messageEl ? messageEl.value.trim() : '';
            if (!message) {
                Swal.fire({ title: 'Message Required', text: 'Please enter a message before submitting your follow-up request.', icon: 'warning' });
                return;
            }
            if (btn) btn.disabled = true;
            fetch('submit_follow_up_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message || '' })
            })
                .then(r => r.json())
                .then(data => {
                    if (btn) btn.disabled = false;
                    if (data.success) {
                        Swal.fire({ title: 'Submitted', text: data.message, icon: 'success' });
                        loadFollowUpSection();
                    } else {
                        Swal.fire({ title: 'Error', text: data.message || 'Request failed.', icon: 'error' });
                    }
                })
                .catch(() => {
                    if (btn) btn.disabled = false;
                    Swal.fire({ title: 'Error', text: 'Request failed. Please try again.', icon: 'error' });
                });
        }
        
        // Function to load apply form with proper session isolation
        function loadApplyFormWithSession() {
            const iframe = document.getElementById('apply-iframe');
            if (iframe) {
                // Generate unique timestamp to prevent caching
                const timestamp = new Date().getTime();
                const sessionId = '<?php echo session_id(); ?>';
                const userId = '<?php echo $_SESSION['user_id']; ?>';
                const sessionToken = '<?php echo $session_token; ?>';
                
                // Show loading indicator
                const loadingIndicator = document.getElementById('loading-indicator');
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'block';
                }
                
                // Reload iframe with fresh session parameters
                iframe.src = `apply.php?session_id=${sessionId}&user_id=${userId}&token=${sessionToken}&t=${timestamp}`;
            }
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
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /** Bold the text after "Reason:" and add line breaks; safe HTML for notification body only. */
        function formatNotificationMessage(raw) {
            if (raw == null || raw === '') return '';
            const t = String(raw);
            const marker = 'Reason:';
            const idx = t.indexOf(marker);
            if (idx === -1) {
                return escapeHtml(t).replace(/\n/g, '<br>');
            }

            const before = t.slice(0, idx);
            const after = t.slice(idx + marker.length).trimStart();

            let reasonPart;
            let tailHtml = '';

            const withBreak = /^([\s\S]+?)\.\s*\n\n(Other\s+referred[\s\S]*)$/i.exec(after);
            const inlineOther = /^([\s\S]+?)(\.\s+Other\s+referred[\s\S]*)$/i.exec(after);

            if (withBreak) {
                reasonPart = withBreak[1].trim();
                tailHtml = '.<br><br>' + escapeHtml(withBreak[2]);
            } else if (inlineOther) {
                reasonPart = inlineOther[1].trim();
                const rest = inlineOther[2].replace(/^\.\s*/, '');
                tailHtml = '.<br><br>' + escapeHtml(rest);
            } else {
                reasonPart = after.trim();
            }

            return escapeHtml(before).replace(/\n/g, '<br>') + marker + ' <strong>' + escapeHtml(reasonPart) + '</strong>' + tailHtml;
        }

        function getNotificationMeta(notification) {
            const title = (notification.title || '').toLowerCase();
            const type = (notification.type || '').toLowerCase();

            if (type === 'announcement' || title.includes('announcement')) return { icon: '📢', label: 'Announcement' };
            if (type === 'follow_up' || title.includes('follow-up')) return { icon: '💬', label: 'Follow-up' };
            if (type === 'nrsp' || title.includes('nrsp')) return { icon: '📝', label: 'NSRP' };
            if (type === 'application' || title.includes('application') || title.includes('accepted') || title.includes('rejected') || title.includes('referred')) {
                return { icon: '📄', label: 'Application' };
            }
            return { icon: '🔔', label: 'General' };
        }

        function formatNotificationTime(isoTimestamp, fallback) {
            if (!isoTimestamp) return fallback || '';
            const date = new Date(isoTimestamp);
            if (Number.isNaN(date.getTime())) return fallback || '';
            const now = new Date();
            const diffMs = now - date;
            const diffMin = Math.floor(diffMs / 60000);
            if (diffMin < 1) return 'Just now';
            if (diffMin < 60) return `${diffMin}m ago`;
            const diffHr = Math.floor(diffMin / 60);
            if (diffHr < 24) return `${diffHr}h ago`;
            const diffDay = Math.floor(diffHr / 24);
            if (diffDay < 7) return `${diffDay}d ago`;
            return fallback || date.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        }

        function normalizeNSRPText(text) {
            if (text == null) return '';
            return String(text).replace(/\bNRSP\b/gi, 'NSRP');
        }
        
        function loadNotifications() {
            const notificationList = document.getElementById('notificationList');
            const badge = document.getElementById('notificationBadge');
            const markAllBtn = document.querySelector('.mark-all-read');
            
            notificationList.innerHTML = '<div class="notification-loading">Loading...</div>';
            
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.notifications && data.notifications.length > 0) {
                        let unreadCount = 0;
                        notificationList.innerHTML = '';
                        
                        data.notifications.forEach(notification => {
                            if (!notification.is_read) unreadCount++;
                            const meta = getNotificationMeta(notification);
                            const displayTime = formatNotificationTime(notification.created_at_iso, notification.created_at);
                            const normalizedTitle = normalizeNSRPText(notification.title || '');
                            const normalizedMessage = normalizeNSRPText(notification.message || '');
                            
                            const notificationItem = document.createElement('div');
                            notificationItem.className = `notification-item ${!notification.is_read ? 'unread' : ''}`;
                            notificationItem.innerHTML = `
                                <div class="notification-meta">
                                    <span class="notification-type">${meta.icon} ${escapeHtml(meta.label)}</span>
                                    <span class="notification-time">${escapeHtml(displayTime || '')}</span>
                                </div>
                                <div class="notification-title">${escapeHtml(normalizedTitle)}</div>
                                <div class="notification-message">${formatNotificationMessage(normalizedMessage)}</div>
                                <div class="notification-time">${escapeHtml(notification.created_at || '')}</div>
                            `;
                            notificationItem.onclick = () => markAsRead(notification.id);
                            notificationList.appendChild(notificationItem);
                        });
                        
                        if (unreadCount > 0) {
                            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                            badge.style.display = 'flex';
                            if (markAllBtn) { markAllBtn.disabled = false; markAllBtn.style.display = ''; }
                        } else {
                            badge.style.display = 'none';
                            if (markAllBtn) { markAllBtn.disabled = true; markAllBtn.style.display = 'none'; }
                        }
                    } else {
                        notificationList.innerHTML = '<div class="no-notifications">No notifications yet</div>';
                        badge.style.display = 'none';
                        if (markAllBtn) { markAllBtn.disabled = true; markAllBtn.style.display = 'none'; }
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    notificationList.innerHTML = '<div class="no-notifications" style="color:#c62828;">Failed to load. Try again.</div>';
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

            // NSRP: iframe height = full form content; parent dashboard scrolls only (no double scrollbar).
            setupApplyIframeAutoResize();
            setupIframeAutoResize('recommended-jobs-iframe', 200, 'recommended_jobs');
            setupIframeAutoResize('resume-iframe', 300);
            setupIframeAutoResize('announcements-iframe', 300);

            // Hamburger menu & slide-out sidebar (mobile)
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const sidebar = document.querySelector('.sidebar.desktop-nav');
            if (hamburgerMenu && sidebar) {
                hamburgerMenu.addEventListener('click', function() {
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

            // Enhance mobile iframe when page loads
            enhanceMobileIframe();
            
            // Initialize recommended jobs iframe handling
            handleRecommendedJobsIframeLoad();
            
            // Initialize resume builder iframe handling
            handleResumeIframeLoad();
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
            
            // Handle mobile progress bar updates from iframe
            if (event.data.type === 'updateProgress') {
                const { progress, step, totalSteps } = event.data;
                updateMobileProgressBar(progress, step, totalSteps);
            }
        });
        
        // Function to update mobile progress bar
        function updateMobileProgressBar(progress, step, totalSteps) {
            const iframe = document.getElementById('apply-iframe');
            if (!iframe) return;
            
            try {
                // Try to access iframe content and update progress
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                if (iframeDoc) {
                    // Look for existing progress elements or create them
                    let progressContainer = iframeDoc.querySelector('.mobile-progress-container');
                    if (!progressContainer) {
                        progressContainer = iframeDoc.createElement('div');
                        progressContainer.className = 'mobile-progress-container';
                        progressContainer.innerHTML = `
                            <div class="mobile-progress-bar">
                                <div class="mobile-progress-fill" style="width: ${progress}%"></div>
                            </div>
                            <div class="mobile-progress-text">Step ${step} of ${totalSteps} (${Math.round(progress)}%)</div>
                        `;
                        iframeDoc.body.insertBefore(progressContainer, iframeDoc.body.firstChild);
                    } else {
                        // Update existing progress
                        const progressFill = progressContainer.querySelector('.mobile-progress-fill');
                        const progressText = progressContainer.querySelector('.mobile-progress-text');
                        if (progressFill) progressFill.style.width = progress + '%';
                        if (progressText) progressText.textContent = `Step ${step} of ${totalSteps} (${Math.round(progress)}%)`;
                    }
                }
            } catch (e) {
                // Cross-origin restrictions - handle gracefully
                console.log('Cannot access iframe content due to cross-origin restrictions');
            }
        }
        
        // Mobile-specific iframe improvements
        function enhanceMobileIframe() {
            const iframe = document.getElementById('apply-iframe');
            if (!iframe) return;
            
            // Add mobile-specific attributes
            iframe.setAttribute('scrolling', 'no');
            iframe.style.webkitOverflowScrolling = 'touch';
            
            // Handle iframe load
            iframe.onload = function() {
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    if (iframeDoc) {
                        // Add mobile-specific styles to iframe content
                        const style = iframeDoc.createElement('style');
                        style.textContent = `
                            @media (max-width: 768px) {
                                body {
                                    padding-top: 60px;
                                    overflow-x: hidden;
                                    overflow-y: visible !important;
                                }
                                .mobile-progress-container {
                                    position: fixed;
                                    top: 0;
                                    left: 0;
                                    right: 0;
                                    z-index: 1000;
                                    background: white;
                                    padding: 10px;
                                    border-bottom: 1px solid #e0e0e0;
                                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                }
                                .mobile-progress-bar {
                                    width: 100%;
                                    height: 8px;
                                    background: #e0e0e0;
                                    border-radius: 4px;
                                    overflow: hidden;
                                    margin-bottom: 10px;
                                }
                                .mobile-progress-fill {
                                    height: 100%;
                                    background: linear-gradient(90deg, #1a3876, #1976d2);
                                    border-radius: 4px;
                                    transition: width 0.3s ease;
                                }
                                .mobile-progress-text {
                                    font-size: 0.8rem;
                                    color: #666;
                                    text-align: center;
                                    font-weight: 500;
                                }
                                
                                /* Mobile horizontal progress steps */
                                .progress-steps {
                                    display: flex !important;
                                    flex-direction: row !important;
                                    overflow-x: auto !important;
                                    padding: 10px 0 !important;
                                    gap: 8px !important;
                                    -webkit-overflow-scrolling: touch !important;
                                }
                                
                                .progress-step {
                                    flex-shrink: 0 !important;
                                    min-width: 60px !important;
                                    text-align: center !important;
                                    padding: 8px 4px !important;
                                }
                                
                                .progress-step-circle {
                                    width: 30px !important;
                                    height: 30px !important;
                                    border-radius: 50% !important;
                                    display: flex !important;
                                    align-items: center !important;
                                    justify-content: center !important;
                                    margin: 0 auto 4px auto !important;
                                    font-size: 0.8rem !important;
                                    font-weight: bold !important;
                                }
                                
                                .progress-step-text {
                                    font-size: 0.7rem !important;
                                    line-height: 1.2 !important;
                                    word-wrap: break-word !important;
                                }
                                
                                /* Active step styling for mobile */
                                .progress-step.active .progress-step-circle {
                                    background: #1a3876 !important;
                                    color: white !important;
                                }
                                
                                .progress-step.active .progress-step-text {
                                    color: #1a3876 !important;
                                    font-weight: bold !important;
                                }
                                
                                /* Inactive step styling for mobile */
                                .progress-step:not(.active) .progress-step-circle {
                                    background: #e0e0e0 !important;
                                    color: #666 !important;
                                }
                                
                                .progress-step:not(.active) .progress-step-text {
                                    color: #666 !important;
                                }
                                
                                /* Fix form field alignment on mobile */
                                .form-group,
                                .form-field,
                                .field-group,
                                .input-group {
                                    text-align: left !important;
                                    align-items: flex-start !important;
                                    justify-content: flex-start !important;
                                }
                                
                                
                                /* Reorganize disability layout for mobile in iframe */
                                .disability-section,
                                .disability-field,
                                .disability-container {
                                    display: flex !important;
                                    flex-direction: column !important;
                                    align-items: flex-start !important;
                                    width: 100% !important;
                                    margin: 0 !important;
                                    padding: 0 !important;
                                }
                                
                                /* Main disability checkbox at top in iframe */
                                .disability-main,
                                .disability-primary,
                                .disability-checkbox:first-child,
                                .disability-section > div:first-child,
                                input[type="checkbox"][name*="disability"]:not([name*="speech"]):not([name*="hearing"]):not([name*="visual"]):not([name*="mental"]):not([name*="others"]),
                                input[type="checkbox"][name*="Disability"]:not([name*="speech"]):not([name*="hearing"]):not([name*="visual"]):not([name*="mental"]):not([name*="others"]) {
                                    order: 1 !important;
                                    display: flex !important;
                                    align-items: center !important;
                                    margin-bottom: 15px !important;
                                    width: 100% !important;
                                    text-align: left !important;
                                }
                                
                                /* Sub-checkboxes horizontal layout in iframe */
                                .disability-options,
                                .disability-list,
                                .disability-checkboxes,
                                .disability-section > div:not(:first-child),
                                .disability-field > div:not(:first-child) {
                                    order: 2 !important;
                                    display: flex !important;
                                    flex-direction: row !important;
                                    flex-wrap: wrap !important;
                                    gap: 10px !important;
                                    width: 100% !important;
                                    justify-content: flex-start !important;
                                    align-items: flex-start !important;
                                }
                                
                                /* Individual sub-checkbox styling in iframe */
                                .disability-options > div,
                                .disability-list > div,
                                .disability-checkboxes > div,
                                .disability-section > div:not(:first-child) > div,
                                .disability-field > div:not(:first-child) > div {
                                    display: flex !important;
                                    align-items: center !important;
                                    margin: 0 !important;
                                    padding: 5px !important;
                                    min-width: 60px !important;
                                    flex-shrink: 0 !important;
                                }
                                
                                /* Sub-checkbox labels in iframe */
                                .disability-options label,
                                .disability-list label,
                                .disability-checkboxes label,
                                .disability-section > div:not(:first-child) label,
                                .disability-field > div:not(:first-child) label {
                                    font-size: 0.8rem !important;
                                    margin-left: 5px !important;
                                    margin-right: 0 !important;
                                    white-space: nowrap !important;
                                }
                                
                                /* Extra small mobile screens (less than 400px) in iframe */
                                @media (max-width: 400px) {
                                    /* ULTRA-AGGRESSIVE: Force main disability checkbox to left */
                                    body .form-row:has(#hasDisability),
                                    body label:has(#hasDisability),
                                    body .disability-section,
                                    body .disability-field,
                                    body .disability-container,
                                    body [class*="disability"],
                                    body [id*="disability"] {
                                        text-align: left !important;
                                        align-items: flex-start !important;
                                        justify-content: flex-start !important;
                                        display: flex !important;
                                        flex-direction: column !important;
                                        width: 100% !important;
                                        margin: 0 !important;
                                        padding: 0 !important;
                                    }
                                    
                                    /* OVERRIDE: Force form-row containing disability to column layout */
                                    body .form-row:has(#hasDisability) {
                                        display: flex !important;
                                        flex-direction: column !important;
                                        align-items: flex-start !important;
                                        justify-content: flex-start !important;
                                        text-align: left !important;
                                        width: 100% !important;
                                        margin: 0 !important;
                                        padding: 0 !important;
                                    }
                                    
                                    /* OVERRIDE: Force main disability label to left alignment */
                                    body .form-row:has(#hasDisability) label:has(#hasDisability) {
                                        display: flex !important;
                                        align-items: center !important;
                                        justify-content: flex-start !important;
                                        text-align: left !important;
                                        width: 100% !important;
                                        margin: 0 !important;
                                        padding: 0 !important;
                                        gap: 8px !important;
                                    }
                                    
                                    /* ULTRA-AGGRESSIVE: Main disability checkbox at top - force left alignment */
                                    body #hasDisability,
                                    body input[type="checkbox"][name="hasDisability"],
                                    body .disability-main,
                                    body .disability-primary,
                                    body .disability-checkbox:first-child,
                                    body .disability-section > div:first-child,
                                    body input[type="checkbox"][name*="disability"]:not([name*="speech"]):not([name*="hearing"]):not([name*="visual"]):not([name*="mental"]):not([name*="others"]),
                                    body input[type="checkbox"][name*="Disability"]:not([name*="speech"]):not([name*="hearing"]):not([name*="visual"]):not([name*="mental"]):not([name*="others"]),
                                    body input[type="checkbox"][name="disability"],
                                    body input[type="checkbox"][name="Disability"],
                                    body input[type="checkbox"][name*="disability"],
                                    body input[type="checkbox"][name*="Disability"] {
                                        order: 1 !important;
                                        display: flex !important;
                                        align-items: center !important;
                                        margin-bottom: 10px !important;
                                        width: 100% !important;
                                        text-align: left !important;
                                        float: none !important;
                                        margin-left: 0 !important;
                                        margin-right: auto !important;
                                        justify-content: flex-start !important;
                                        position: static !important;
                                        left: 0 !important;
                                        right: auto !important;
                                        transform: none !important;
                                    }
                                    
                                    /* ULTRA-AGGRESSIVE: Sub-checkboxes horizontal layout */
                                    body .checkbox-group#disabilityFields,
                                    body #disabilityFields,
                                    body .disability-options,
                                    body .disability-list,
                                    body .disability-checkboxes,
                                    body .disability-section > div:not(:first-child),
                                    body .disability-field > div:not(:first-child),
                                    body [class*="disability"] > div:not(:first-child),
                                    body [id*="disability"] > div:not(:first-child) {
                                        order: 2 !important;
                                        display: flex !important;
                                        flex-direction: row !important;
                                        flex-wrap: wrap !important;
                                        gap: 5px !important;
                                        width: 100% !important;
                                        justify-content: flex-start !important;
                                        align-items: flex-start !important;
                                        overflow-x: auto !important;
                                        -webkit-overflow-scrolling: touch !important;
                                    }
                                    
                                    /* OVERRIDE: Force checkbox-group to horizontal layout */
                                    body .form-row:has(#hasDisability) .checkbox-group#disabilityFields {
                                        display: flex !important;
                                        flex-direction: row !important;
                                        flex-wrap: wrap !important;
                                        gap: 8px !important;
                                        width: 100% !important;
                                        justify-content: flex-start !important;
                                        align-items: flex-start !important;
                                        margin-top: 10px !important;
                                        margin-bottom: 0 !important;
                                        padding: 0 !important;
                                    }
                                    
                                    /* ULTRA-AGGRESSIVE: Individual sub-checkbox styling */
                                    body #disabilityFields label,
                                    body .disability-options > div,
                                    body .disability-list > div,
                                    body .disability-checkboxes > div,
                                    body .disability-section > div:not(:first-child) > div,
                                    body .disability-field > div:not(:first-child) > div,
                                    body [class*="disability"] > div:not(:first-child) > div,
                                    body [id*="disability"] > div:not(:first-child) > div {
                                        display: flex !important;
                                        align-items: center !important;
                                        margin: 0 !important;
                                        padding: 3px !important;
                                        min-width: 50px !important;
                                        flex-shrink: 0 !important;
                                        white-space: nowrap !important;
                                        float: none !important;
                                        text-align: left !important;
                                    }
                                    
                                    /* OVERRIDE: Force individual checkbox labels to horizontal layout */
                                    body .form-row:has(#hasDisability) .checkbox-group#disabilityFields label {
                                        display: flex !important;
                                        align-items: center !important;
                                        justify-content: flex-start !important;
                                        margin: 0 !important;
                                        padding: 5px 8px !important;
                                        min-width: 60px !important;
                                        flex-shrink: 0 !important;
                                        white-space: nowrap !important;
                                        text-align: left !important;
                                        gap: 5px !important;
                                    }
                                    
                                    /* ULTRA-AGGRESSIVE: Sub-checkbox labels */
                                    body .disability-options label,
                                    body .disability-list label,
                                    body .disability-checkboxes label,
                                    body .disability-section > div:not(:first-child) label,
                                    body .disability-field > div:not(:first-child) label,
                                    body [class*="disability"] > div:not(:first-child) label,
                                    body [id*="disability"] > div:not(:first-child) label {
                                        font-size: 0.7rem !important;
                                        margin-left: 3px !important;
                                        margin-right: 0 !important;
                                        white-space: nowrap !important;
                                        text-align: left !important;
                                        float: none !important;
                                    }
                                    
                                    /* ULTRA-AGGRESSIVE: Force all disability elements to left alignment */
                                    body .disability-section *,
                                    body .disability-field *,
                                    body .disability-container *,
                                    body [class*="disability"] *,
                                    body [id*="disability"] * {
                                        text-align: left !important;
                                        float: none !important;
                                        margin-left: 0 !important;
                                        margin-right: auto !important;
                                        position: static !important;
                                        left: 0 !important;
                                        right: auto !important;
                                    }
                                    
                                    /* ULTRA-AGGRESSIVE: Override any existing styles */
                                    body input[type="checkbox"],
                                    body .checkbox,
                                    body .form-checkbox {
                                        margin-left: 0 !important;
                                        margin-right: auto !important;
                                        float: none !important;
                                        text-align: left !important;
                                        position: static !important;
                                        left: 0 !important;
                                        right: auto !important;
                                    }
                                }
                                
                                /* Fix form container alignment */
                                .form-container,
                                .form-wrapper,
                                .form-content {
                                    text-align: left !important;
                                }
                                
                                /* Ensure all form elements align left */
                                input[type="checkbox"],
                                input[type="radio"],
                                .checkbox,
                                .radio {
                                    margin-left: 0 !important;
                                    margin-right: auto !important;
                                    float: none !important;
                                    display: inline-block !important;
                                }
                                
                                /* Hide returnee fields by default on mobile */
                                #returneeFields,
                                #returneeReturnFields {
                                    display: none !important;
                                }
                                
                                /* Show returnee fields only when returnee is Yes */
                                body:has(#returneeYes:checked) #returneeFields,
                                body:has(#returneeYes:checked) #returneeReturnFields {
                                    display: block !important;
                                }
                            }
                        `;
                        iframeDoc.head.appendChild(style);
                        
                        // Ensure returnee fields are hidden by default
                        const returneeFields = iframeDoc.getElementById('returneeFields');
                        const returneeReturnFields = iframeDoc.getElementById('returneeReturnFields');
                        if (returneeFields) returneeFields.style.display = 'none';
                        if (returneeReturnFields) returneeReturnFields.style.display = 'none';
                    }
                } catch (e) {
                    console.log('Cannot access iframe content due to cross-origin restrictions');
                }
            };
        }

        // Load latest announcements
        function loadLatestAnnouncements() {
            fetch('announcement_api.php?action=read&status=published&limit=3')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const announcements = data.announcements.filter(announcement => {
                            // Filter out expired announcements
                            if (announcement.expiration_date) {
                                const expirationDate = new Date(announcement.expiration_date);
                                const today = new Date();
                                if (expirationDate < today) {
                                    return false;
                                }
                            }
                            return true;
                        }).slice(0, 3); // Show only 3 latest

                        const container = document.getElementById('latestAnnouncements');
                        
                        if (announcements.length === 0) {
                            container.innerHTML = `
                                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; color: #666;">
                                    <div style="font-size: 2rem; margin-bottom: 8px;">📢</div>
                                    <p style="margin: 0;">No announcements available</p>
                                </div>
                            `;
                            return;
                        }

                        container.innerHTML = announcements.map(announcement => `
                            <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 16px; border-left: 4px solid #233a8b;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <h4 style="margin: 0; color: #233a8b; font-size: 1rem;">${announcement.title}</h4>
                                    <span style="background: #e3eaff; color: #233a8b; padding: 2px 6px; border-radius: 3px; font-size: 0.7rem; font-weight: 600;">
                                        ${announcement.category}
                                    </span>
                                </div>
                                <p style="margin: 0 0 8px 0; color: #666; font-size: 0.9rem; line-height: 1.4;">
                                    ${announcement.description.length > 100 ? 
                                        announcement.description.substring(0, 100) + '...' : 
                                        announcement.description
                                    }
                                </p>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <small style="color: #999;">${new Date(announcement.date_posted).toLocaleDateString()}</small>
                                    <a href="#" onclick="showSection('announcements')" style="color: #233a8b; text-decoration: none; font-size: 0.8rem; font-weight: 600;">Read More →</a>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        document.getElementById('latestAnnouncements').innerHTML = `
                            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; color: #666;">
                                <p style="margin: 0;">Unable to load announcements</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading announcements:', error);
                    document.getElementById('latestAnnouncements').innerHTML = `
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; color: #666;">
                            <p style="margin: 0;">Unable to load announcements</p>
                        </div>
                    `;
                });
        }

        // Load Skills Ranking
        function loadSkillsRanking() {
            fetch('skills_ranking.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderSkillsRanking(data.skills, data.totalSkills);
                    } else {
                        document.getElementById('skillsRankingContainer').innerHTML = `
                            <div class="skills-ranking-loading">
                                <p>Unable to load skills ranking</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading skills ranking:', error);
                    document.getElementById('skillsRankingContainer').innerHTML = `
                        <div class="skills-ranking-loading">
                            <p>Error loading skills ranking</p>
                        </div>
                    `;
                });
        }

        function renderSkillsRanking(skills, totalSkills) {
            const container = document.getElementById('skillsRankingContainer');
            
            if (skills.length === 0) {
                container.innerHTML = `
                    <div class="skills-ranking-loading" style="grid-column: 1 / -1;">
                        <div style="font-size: 3rem; color: #999; margin-bottom: 16px;">🛠️</div>
                        <div style="font-weight: 600; color: #666; margin-bottom: 8px; font-size: 1.1rem;">No Skills Data Available</div>
                        <div style="color: #999; font-size: 0.9rem;">Skills will appear here once jobseekers register with their skills</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = skills.map(skill => {
                const percentage = totalSkills > 0 ? Math.round((skill.count / totalSkills) * 100) : 0;
                return `
                    <div class="skill-card">
                        <div class="skill-card-icon">🛠️</div>
                        <div class="skill-card-name">${skill.skill}</div>
                        <div class="skill-card-count">${skill.count}</div>
                        <div class="skill-card-percentage">${percentage}% of total</div>
                    </div>
                `;
            }).join('');
        }

        // Load Application Success Rate
        function loadApplicationSuccessRate() {
            fetch('application_success_rate.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderApplicationSuccessRate(data.data);
                    } else {
                        document.getElementById('successRateContainer').innerHTML = `
                            <div class="analytics-loading">
                                <p>Unable to load success rate data</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading application success rate:', error);
                    document.getElementById('successRateContainer').innerHTML = `
                        <div class="analytics-loading">
                            <p>Error loading success rate data</p>
                        </div>
                    `;
                });
        }

        function renderApplicationSuccessRate(data) {
            const container = document.getElementById('successRateContainer');
            
            if (data.total_applications === 0) {
                container.innerHTML = `
                    <div class="analytics-loading" style="width:100%;">
                        <div style="font-size: 3rem; color: #999; margin-bottom: 16px;">📊</div>
                        <div style="font-weight: 600; color: #666; margin-bottom: 8px; font-size: 1.1rem;">No job applications yet</div>
                        <div style="color: #999; font-size: 0.9rem;">Apply to a job from <strong>Recommended Jobs</strong> to see your success rate here.</div>
                    </div>
                `;
                return;
            }
            
            const wCount = typeof data.withdrawn_count === 'number' ? data.withdrawn_count : 0;
            const wRate = typeof data.withdrawn_rate === 'number' ? data.withdrawn_rate : 0;
            let html = `
                <div class="success-rate-cards-row">
                    <div class="success-rate-card accepted">
                        <div class="success-rate-label">Accepted Applications</div>
                        <div class="success-rate-number">${data.accepted_count}</div>
                        <div class="success-rate-percentage">${data.success_rate}%</div>
                    </div>
                    <div class="success-rate-card rejected">
                        <div class="success-rate-label">Rejected Applications</div>
                        <div class="success-rate-number">${data.rejected_count}</div>
                        <div class="success-rate-percentage">${data.rejection_rate}%</div>
                    </div>
                    <div class="success-rate-card pending">
                        <div class="success-rate-label">Pending Applications</div>
                        <div class="success-rate-number">${data.pending_count}</div>
                        <div class="success-rate-percentage">${data.pending_rate}%</div>
                    </div>
                    <div class="success-rate-card withdrawn">
                        <div class="success-rate-label">Withdrawn Applications</div>
                        <div class="success-rate-number">${wCount}</div>
                        <div class="success-rate-percentage">${wRate}%</div>
                    </div>
                </div>
            `;
            
            if (Object.keys(data.top_rejection_reasons).length > 0) {
                html += `
                    <div class="success-rate-rejection-panel">
                        <h3>Rejection Reasons</h3>
                        <div class="rejection-reasons">
                            ${Object.entries(data.top_rejection_reasons).map(([reason, count]) => `
                                <div class="rejection-reason-item">
                                    <span style="color: #666;">${escapeHtml(String(reason))}</span>
                                    <span style="color: #f44336; font-weight: 600;">${count}x</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        }

        // Load Most Accepted Skills
        function loadMostAcceptedSkills() {
            fetch('most_accepted_skills.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderMostAcceptedSkills(data.skills, data.total_accepted);
                    } else {
                        document.getElementById('mostAcceptedSkillsContainer').innerHTML = `
                            <div class="analytics-loading">
                                <p>Unable to load most accepted skills</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading most accepted skills:', error);
                    document.getElementById('mostAcceptedSkillsContainer').innerHTML = `
                        <div class="analytics-loading">
                            <p>Error loading most accepted skills</p>
                        </div>
                    `;
                });
        }

        function renderMostAcceptedSkills(skills, totalAccepted) {
            const container = document.getElementById('mostAcceptedSkillsContainer');
            
            if (skills.length === 0) {
                container.innerHTML = `
                    <div class="analytics-loading" style="grid-column: 1 / -1;">
                        <div style="font-size: 3rem; color: #999; margin-bottom: 16px;">✅</div>
                        <div style="font-weight: 600; color: #666; margin-bottom: 8px; font-size: 1.1rem;">No Accepted Jobseekers Yet</div>
                        <div style="color: #999; font-size: 0.9rem;">Skills will appear here once jobseekers are accepted</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = skills.map(skill => `
                <div class="accepted-skill-card">
                    <div class="accepted-skill-icon">✅</div>
                    <div class="accepted-skill-name">${skill.skill}</div>
                    <div class="accepted-skill-count">${skill.count}</div>
                    <div class="accepted-skill-percentage">${skill.percentage}% of accepted</div>
                </div>
            `).join('');
        }

        // Load Skills Gap Analysis
        function loadSkillsGapAnalysis() {
            fetch('skills_gap_analysis.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderSkillsGapAnalysis(data.data);
                    } else {
                        document.getElementById('skillsGapContainer').innerHTML = `
                            <div class="analytics-loading">
                                <p>Unable to load skills gap analysis</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading skills gap analysis:', error);
                    document.getElementById('skillsGapContainer').innerHTML = `
                        <div class="analytics-loading">
                            <p>Error loading skills gap analysis</p>
                        </div>
                    `;
                });
        }

        function renderSkillsGapAnalysis(data) {
            const container = document.getElementById('skillsGapContainer');
            
            // Determine match score class
            let scoreClass = 'low';
            let scoreLabel = 'Low Match';
            if (data.match_score >= 70) {
                scoreClass = 'high';
                scoreLabel = 'Excellent Match';
            } else if (data.match_score >= 40) {
                scoreClass = 'medium';
                scoreLabel = 'Good Match';
            }
            
            let html = `
                <div class="gap-analysis-card">
                    <h3 style="margin: 0 0 20px 0; color: #233a8b; font-size: 1.1rem; text-align: center;">Your Skills Match Score</h3>
                    <div class="match-score-circle ${scoreClass}">
                        <div style="text-align: center;">
                            <div style="font-size: 2.5rem;">${data.match_score}%</div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">${scoreLabel}</div>
                        </div>
                    </div>
                    <p style="text-align: center; color: #666; margin: 0; font-size: 0.9rem;">
                        You have <strong>${data.user_skills_count}</strong> skills registered
                    </p>
                </div>
            `;
            
            if (data.recommendations.length > 0) {
                html += `
                    <div class="gap-analysis-card">
                        <h3 style="margin: 0 0 16px 0; color: #233a8b; font-size: 1.1rem;">Recommended Skills to Add</h3>
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 16px;">These skills are highly valued by employers and could improve your chances:</p>
                        <ul class="recommendations-list">
                            ${data.recommendations.map(rec => `
                                <li class="recommendation-item">
                                    <span style="font-weight: 500; color: #333;">${rec.skill}</span>
                                    <span class="recommendation-percentage">${rec.percentage}% of accepted</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                `;
            } else {
                html += `
                    <div class="gap-analysis-card">
                        <h3 style="margin: 0 0 16px 0; color: #233a8b; font-size: 1.1rem;">Great Job! 🎉</h3>
                        <p style="color: #666; font-size: 0.9rem; margin: 0;">
                            You already have the most valued skills! Keep up the excellent work.
                        </p>
                    </div>
                `;
            }
            
            if (data.top_accepted_skills.length > 0) {
                html += `
                    <div class="gap-analysis-card">
                        <h3 style="margin: 0 0 16px 0; color: #233a8b; font-size: 1.1rem;">Top Accepted Skills Overview</h3>
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 16px;">Skills that ${data.total_accepted_jobseekers} accepted jobseekers have:</p>
                        <div style="max-height: 300px; overflow-y: auto;">
                            ${data.top_accepted_skills.slice(0, 8).map(skill => `
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                    <span style="color: #333;">${skill.skill}</span>
                                    <span style="color: #4CAF50; font-weight: 600; font-size: 0.9rem;">${skill.percentage}%</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        }

        // Poll for new announcements (admin-created) and refresh if detected
        var announcementCheck = { count: 0, latest_id: 0 };
        function pollNewAnnouncements() {
            fetch('announcement_api.php?action=check')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) return;
                    var count = data.count || 0;
                    var latestId = data.latest_id || 0;
                    if (announcementCheck.count !== count || announcementCheck.latest_id !== latestId) {
                        if (announcementCheck.count > 0 || announcementCheck.latest_id > 0) {
                            loadLatestAnnouncements();
                            var iframe = document.getElementById('announcements-iframe');
                            if (iframe && iframe.src) {
                                var sep = iframe.src.indexOf('?') >= 0 ? '&' : '?';
                                iframe.src = iframe.src.split('&t=')[0].split('?t=')[0] + sep + 't=' + Date.now();
                            }
                        }
                        announcementCheck.count = count;
                        announcementCheck.latest_id = latestId;
                    }
                })
                .catch(function() {});
        }

        // Load announcements when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadLatestAnnouncements();
            loadSkillsRanking();
            loadApplicationSuccessRate();
            loadMostAcceptedSkills();
            loadSkillsGapAnalysis();
            fetch('announcement_api.php?action=check')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        announcementCheck.count = data.count || 0;
                        announcementCheck.latest_id = data.latest_id || 0;
                    }
                })
                .catch(function() {});
            setInterval(pollNewAnnouncements, 25000);
        });
    </script>
</body>
</html>
