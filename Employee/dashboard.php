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
        .mobile-nav-item[data-section="apply"]:before { content: '📝'; }
        .mobile-nav-item[data-section="profile"]:before { content: '👤'; }
        .mobile-nav-item[data-section="logout"]:before { content: '🚪'; }
        
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
            display: block;
            margin-bottom: 10px;
            padding: 8px 12px;
            font-size: 0.85rem;
            text-align: center;
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
        width: 100%;
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
                <iframe id="apply-iframe" src="apply.html" width="100%" frameborder="0" style="border-radius: 8px; border: none; height: auto;"></iframe>
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

    <!-- Mobile Bottom Navigation - Outside dashboard container -->
    <div class="mobile-bottom-nav">
        <div class="mobile-nav-item active" data-section="dashboard" onclick="showSection('dashboard')">Dashboard</div>
        <div class="mobile-nav-item" data-section="apply" onclick="showSection('apply')">Apply</div>
        <div class="mobile-nav-item" data-section="profile" onclick="showSection('profile')">Profile</div>
        <div class="mobile-nav-item" data-section="logout" onclick="showLogoutModal()">Logout</div>
    </div>

    <script>
        function showSection(section) {
            // Hide all sections
            document.getElementById('dashboard-section').style.display = 'none';
            document.getElementById('apply-section').style.display = 'none';
            document.getElementById('profile-section').style.display = 'none';
            
            // Remove active class from all nav items (desktop and mobile)
            document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
            document.querySelectorAll('.mobile-nav-item').forEach(item => item.classList.remove('active'));
            
            // Show selected section
            document.getElementById(section + '-section').style.display = 'block';
            
            // Add active class to clicked nav item
            if (event && event.target) {
                event.target.classList.add('active');
            }
            
            // Also update mobile nav active state
            document.querySelectorAll('.mobile-nav-item').forEach(item => {
                if (item.getAttribute('data-section') === section) {
                    item.classList.add('active');
                }
            });
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
    </script>
</body>
</html>
