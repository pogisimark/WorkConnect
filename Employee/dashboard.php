<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_check.php';
require_once 'db.php';

// Ensure session is properly started and user is authenticated
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Additional session validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Generate unique session token for iframe security
$session_token = hash('sha256', session_id() . $_SESSION['user_id'] . 'workconnect');
$_SESSION['iframe_token'] = $session_token;

// Get user applications
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
    /* Mobile-first responsive design */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 10px 15px;
            flex-direction: column;
            gap: 10px;
            height: auto;
            min-height: 80px;
        }
        
        .logo-brand {
            justify-content: center;
            width: 100%;
        }
        
        .logo {
            height: 30px;
            margin-right: 8px;
        }
        
        .brand {
            font-size: 1.2rem;
        }
        
        .user-info {
            width: 100%;
            justify-content: space-between;
            gap: 10px;
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
            max-width: 120px;
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
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            position: relative;
            overflow-y: auto; /* Enable vertical scrolling */
        }
        
        /* Mobile-friendly iframe for job application form */
        #apply-iframe {
            width: 100% !important;
            min-height: 100vh !important; /* Ensure enough height for content */
            height: auto !important; /* Auto height to show all content */
            border: none !important;
            border-radius: 8px !important;
        }
        
        /* Mobile progress bar improvements */
        #apply-iframe {
            /* Ensure iframe content is scrollable */
            position: relative;
            z-index: 1;
        }
        
        /* Mobile-specific progress bar styling */
        @media (max-width: 768px) {
            /* Target progress bars within the iframe content */
            #apply-iframe {
                /* Add mobile-specific styling for better progress bar display */
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
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
            padding: 6px 8px;
            font-size: 0.7rem;
            white-space: nowrap;
            border-radius: 8px;
            background: transparent;
            border: none;
            color: #666;
            text-decoration: none;
            min-height: 44px;
            width: 100%;
            max-width: 60px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .mobile-nav-item:before {
            content: '';
            font-size: 1.2rem;
            margin-bottom: 2px;
        }
        
        .mobile-nav-item[data-section="dashboard"]:before { content: '📊'; }
        .mobile-nav-item[data-section="jobs"]:before { content: '🎯'; }
        .mobile-nav-item[data-section="resume"]:before { content: '📄'; }
        .mobile-nav-item[data-section="analytics"]:before { content: '📈'; }
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
        
        /* Fix iframe for mobile */
        #apply-iframe {
            min-height: 100vh; /* Ensure enough height for content */
            height: auto; /* Auto height to show all content */
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
        
        /* Notification dropdown mobile positioning */
        .notification-dropdown {
            right: 10px;
            width: 300px;
            max-height: 300px;
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
            max-width: 100px;
        }
        
        .dashboard-container {
            padding-top: 70px;
            padding-bottom: 110px; /* Much more space for bottom navigation */
            min-height: calc(100vh - 180px); /* Account for header and bottom nav */
            overflow-y: auto; /* Enable vertical scrolling */
        }
        
        .main-content {
            padding: 10px 10px 110px 10px; /* Much more bottom padding for mobile nav */
            min-height: calc(10vh - 200px); /* Ensure enough height for scrolling */
        }
        
        .mobile-bottom-nav {
            height: 55px;
        }
        
        .mobile-nav-item {
            padding: 4px 6px;
            font-size: 0.65rem;
            max-width: 50px;
        }
        
        .mobile-nav-item:before {
            font-size: 1rem;
        }
        
        /* Mobile-friendly iframe for smaller screens */
        #apply-iframe {
            min-height: 100vh !important; /* Ensure enough height for content */
            height: auto !important; /* Auto height to show all content */
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
        
        #apply-iframe {
            height: 500px;
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
            height: 100vh !important;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }
        
        html {
            overflow-x: hidden !important;
            overflow-y: auto !important; /* Enable vertical scrolling */
            height: 100vh !important;
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
        min-height: 100vh; /* Use min-height instead of fixed height */
        padding-top: 100px; /* Increased to account for header height */
        overflow: visible; /* Allow content to expand */
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
    
    /* Hide desktop sidebar on mobile */
    @media (max-width: 768px) {
        .sidebar.desktop-nav {
            display: none !important;
        }
        
        /* Override any desktop styles that might affect mobile */
        .main-content {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            left: 0 !important;
            right: 0 !important;
                height: auto !important; /* Allow mobile content to flow naturally */
                overflow: visible !important; /* Allow content to expand naturally */
        }
        
        .dashboard-container {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
                height: auto !important; /* Allow mobile container to flow naturally */
                overflow: visible !important; /* Allow content to expand naturally */
            }
            
            /* Show mobile bottom navigation only on mobile */
            .mobile-bottom-nav {
                display: flex !important;
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
    
    #apply-iframe {
        border: none;
        display: block;
        width: 100%;
        max-width: 100%;
        min-height: 110vh; /* Ensure iframe has enough height */
        height: auto; /* Allow iframe to expand to its content */
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
        min-height: 200vh; /* Ensure iframe has enough height for content */
        height: auto; /* Fixed height to prevent scrollbar issues */
        
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
        min-height: 100vh; /* Ensure iframe has enough height for content */
        height: auto; /* Allow iframe to expand to its content */
        border-radius: 8px;
    }
    
    /* Mobile responsive adjustments for recommended jobs */
    @media (max-width: 768px) {
        #recommended-jobs-container {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        
        #recommended-jobs-iframe {
            min-height: 100vh;
            height: auto;
            border-radius: 0;
        }
        
        #resume-container {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        
        #resume-iframe {
            min-height: 100vh;
            height: auto;
            border-radius: 0;
        }
        
        #announcements-container {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        
        #announcements-iframe {
            min-height: 100vh;
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
    
    .success-rate-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
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
    
    @media (max-width: 768px) {
        .success-rate-grid,
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
        <!-- Desktop Sidebar -->
        <div class="sidebar desktop-nav">
            <ul class="sidebar-nav">
                <li><a href="#dashboard" class="active" onclick="showSection('dashboard')">Dashboard</a></li>
                <li><a href="#recommended_jobs" onclick="showSection('recommended_jobs')">Recommended Jobs <span class="badge" id="jobBadge" style="display:none;">New</span></a></li>
                <!--<li><a href="#resume" onclick="showSection('resume')">Resume Builder</a></li>-->
                <li><a href="#apply" onclick="showSection('apply')">NSRP Registration</a></li>
                <li><a href="#follow_up" onclick="showSection('follow_up')">Request follow-up</a></li>
                <li><a href="#announcements" onclick="showSection('announcements')">Announcements</a></li>
                <li><a href="#profile" onclick="showSection('profile')">Profile</a></li>
                <li><a href="#" onclick="showLogoutModal()">Logout</a></li>
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
                        <h3>Application Status</h3>
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
                        <h3>Recent Applications</h3>
                        <?php if (empty($applications)): ?>
                            <div class="no-applications">
                                <h3>No Applications Yet</h3>
                                <p>You haven't submitted any job applications yet.</p>
                                <button class="apply-now-btn" onclick="showSection('apply')">Apply Now</button>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($applications, 0, 3) as $app): ?>
                                <?php 
                                    $app_status = !empty($app['application_status']) ? $app['application_status'] : 'Pending';
                                    $status_class = strtolower($app_status);
                                    
                                    // Format submission date
                                    $submission_date = '';
                                    if (!empty($app['submission_date'])) {
                                        $submission_date = date('M Y', strtotime($app['submission_date']));
                                    } elseif (!empty($app['submission_month']) && !empty($app['submission_year'])) {
                                        $submission_date = date('M Y', mktime(0, 0, 0, $app['submission_month'], 1, $app['submission_year']));
                                    } else {
                                        $submission_date = 'Date not available';
                                    }
                                    
                                    $full_name = trim(($app['firstname'] ?? '') . ' ' . ($app['surname'] ?? ''));
                                    if (empty($full_name)) {
                                        $full_name = 'Application #' . $app['id'];
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
                            <h4>Total Applications</h4>
                            <p><?php echo count($applications); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Skills Ranking Section -->
                <div class="skills-ranking-section">
                    <h2 class="section-title">Top Skills Ranking</h2>
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
                    <div id="successRateContainer" class="success-rate-grid">
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
                        <a href="https://www.facebook.com/share/1GrpFP7Xqr/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer" class="facebook-link">
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
                    <iframe id="recommended-jobs-iframe" src="recommended_jobs.php?session_id=<?php echo session_id(); ?>&user_id=<?php echo $_SESSION['user_id']; ?>&token=<?php echo $session_token; ?>" width="100%" frameborder="0" scrolling="yes"></iframe>
                </div>
            </div>

            <!-- Resume Builder Section -->
            <div id="resume-section" class="content-section" style="display: none;"> 
                <h2 class="section-title">Resume Builder</h2>
                <div id="resume-container">
                    <div id="loading-indicator-resume" style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                        <div class="loading-spinner"></div>
                        <p style="margin: 10px 0 0 0; color: #666;">Loading resume builder...</p>
                    </div>
                    <iframe id="resume-iframe" src="resume_builder.php?session_id=<?php echo session_id(); ?>&user_id=<?php echo $_SESSION['user_id']; ?>&token=<?php echo $session_token; ?>" width="100%" frameborder="0" scrolling="yes"></iframe>
                </div>
            </div>

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
                    <iframe id="apply-iframe" src="apply.php?session_id=<?php echo session_id(); ?>&user_id=<?php echo $_SESSION['user_id']; ?>&token=<?php echo $session_token; ?>" width="100%" frameborder="0" style="border-radius: 8px; border: none; height: auto;"></iframe>
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
                    <h3 style="color: #1a3876; margin-bottom: 15px;">All Applications</h3>
                    <?php if (empty($applications)): ?>
                        <p style="color: #666;">No applications submitted yet.</p>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <?php 
                                $app_status = !empty($app['application_status']) ? $app['application_status'] : 'Pending';
                                $status_class = strtolower($app_status);
                                
                                // Format submission date
                                $submission_date = '';
                                if (!empty($app['submission_date'])) {
                                    $submission_date = date('M j, Y', strtotime($app['submission_date']));
                                } elseif (!empty($app['submission_month']) && !empty($app['submission_year'])) {
                                    $submission_date = date('M j, Y', mktime(0, 0, 0, $app['submission_month'], 1, $app['submission_year']));
                                } else {
                                    $submission_date = 'Date not available';
                                }
                                
                                $full_name = trim(($app['firstname'] ?? '') . ' ' . ($app['surname'] ?? ''));
                                if (empty($full_name)) {
                                    $full_name = 'Application #' . $app['id'];
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
                                                | Position: <?php echo htmlspecialchars($app['occupation1']); ?>
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
                    <iframe id="announcements-iframe" src="announcements.php?session_id=<?php echo session_id(); ?>&user_id=<?php echo $_SESSION['user_id']; ?>&token=<?php echo $session_token; ?>" width="100%" frameborder="0" scrolling="yes" style="border-radius: 8px; border: none; height: auto; min-height: 100vh;"></iframe>
                </div>
                <!-- Facebook Link -->
                <div class="facebook-link-container" style="margin-top: 20px;">
                    <a href="https://www.facebook.com/share/1GrpFP7Xqr/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer" class="facebook-link">
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
        <div class="mobile-nav-item" data-section="resume" onclick="showSection('resume')">Resume</div>
        <div class="mobile-nav-item" data-section="follow_up" onclick="showSection('follow_up')">Follow-up</div>
        <div class="mobile-nav-item" data-section="announcements" onclick="showSection('announcements')">📢</div>
        <div class="mobile-nav-item" data-section="profile" onclick="showSection('profile')">Profile</div>
    </div>

    <script>
        
        // Handle URL hash changes (for direct links)
        function handleHashChange() {
            const hash = window.location.hash.substring(1); // Remove the #
            if (hash && ['dashboard', 'recommended_jobs', 'resume', 'apply', 'follow_up', 'announcements', 'profile'].includes(hash)) {
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
        });
        
        // Check hash on page load
        document.addEventListener('DOMContentLoaded', function() {
            handleHashChange();
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
                }
                
                // If showing recommended jobs section, hide loading indicator after delay
                if (section === 'recommended_jobs') {
                    hideRecommendedJobsLoadingIndicator();
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
                        bodyEl.innerHTML = '<p style="color: #666;">' + (data.message || 'You can request a follow-up once your application has been pending for at least 7 days.') + '</p>';
                        return;
                    }
                    var requests = data.requests || [];
                    var html = '';
                    if (data.already_pending) {
                        html += '<p style="color: #856404; background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 20px;">You have a pending follow-up request. You will be notified when admin responds.</p>';
                    }
                    if (requests.length > 0) {
                        html += '<h3 style="color: #233a8b; margin: 0 0 12px 0; font-size: 1.1rem;">Past requests</h3>';
                        html += '<div class="bulk-actions-fu" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap;"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" id="fuSelectAll"> Select all</label><button type="button" class="btn-delete-selected-fu" id="fuDeleteSelected" disabled style="background:#d32f2f;color:#fff;padding:8px 16px;border-radius:8px;border:none;font-weight:600;cursor:pointer;font-size:0.9rem;">Delete selected</button></div>';
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
                            html += '<div style="margin-top:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;"><label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.9rem;"><input type="checkbox" class="fu-card-checkbox" value="' + req.id + '"> Select</label><button type="button" class="btn-delete-fu" data-id="' + req.id + '" style="background:#f44336;color:#fff;padding:6px 12px;border-radius:6px;border:none;font-size:0.85rem;cursor:pointer;">Delete</button></div>';
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    if (!data.already_pending) {
                        html += '<p style="color: #333; margin-bottom: 15px;">You have a pending application. You can request a follow-up below. Admin will be notified and may respond via your notifications.</p>';
                        html += '<textarea id="follow_up_message" placeholder="Optional: Add a short message for admin..." style="width:100%; min-height: 100px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; box-sizing: border-box;"></textarea>';
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
            if (btn) btn.disabled = true;
            const message = messageEl ? messageEl.value.trim() : '';
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
            iframe.setAttribute('scrolling', 'yes');
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
                    <div class="analytics-loading" style="grid-column: 1 / -1;">
                        <div style="font-size: 3rem; color: #999; margin-bottom: 16px;">📊</div>
                        <div style="font-weight: 600; color: #666; margin-bottom: 8px; font-size: 1.1rem;">No Applications Yet</div>
                        <div style="color: #999; font-size: 0.9rem;">Submit your first application to see your success rate</div>
                    </div>
                `;
                return;
            }
            
            let html = `
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
            `;
            
            if (Object.keys(data.top_rejection_reasons).length > 0) {
                html += `
                    <div class="success-rate-card" style="grid-column: 1 / -1; text-align: left;">
                        <h3 style="margin: 0 0 16px 0; color: #233a8b; font-size: 1.1rem;">Top Rejection Reasons</h3>
                        <div class="rejection-reasons">
                            ${Object.entries(data.top_rejection_reasons).map(([reason, count]) => `
                                <div class="rejection-reason-item">
                                    <span style="color: #666;">${reason}</span>
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
