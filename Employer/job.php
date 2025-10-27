<?php include 'session_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect Jobseekers</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        background: #fafafa;
        min-height: 100vh;
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
        min-height: calc(100vh - 64px);
        padding-top: 64px; /* offset for fixed header */
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
    .sidebar .logout {
        margin-top: auto;
        margin-bottom: 32px;
        color: #222;
        font-weight: bold;
        display: block;
        width: 90%;
        text-align: left;
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
    .main-content {
        flex: 1;
        padding: 32px;
        background: #fff;
        margin-left: 240px;
        min-height: calc(100vh - 64px);
        overflow-y: auto;
        box-sizing: border-box;
    }
    .jobseeker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 50px;
        margin-top: 24px;
        padding: 0 20px;
    }
    .jobseeker-card {
        background: linear-gradient(135deg, #ffffff, #f8fafc);
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 18px 14px 14px 14px;
        box-shadow: 0 4px 20px rgba(35,58,139,0.08);
        border: 1px solid rgba(35,58,139,0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        width: 100%;
        position: relative;
        overflow: hidden;
        min-height: 220px;
    }
    .jobseeker-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #233a8b, #1976d2);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .jobseeker-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 40px rgba(35,58,139,0.15);
        border-color: rgba(35,58,139,0.2);
    }
    .jobseeker-card:hover::before {
        opacity: 1;
    }
    .jobseeker-card .jobseeker-name {
        font-size: 1rem;
        font-weight: 700;
        color: #233a8b;
        text-align: center;
        margin-top: 10px;
        margin-bottom: 6px;
        line-height: 1.3;
        padding: 0 2px;
    }
    .jobseeker-card .jobseeker-info {
        font-size: 0.85rem;
        color: #555;
        margin-bottom: 10px;
        text-align: center;
        line-height: 1.4;
        padding: 5px 8px;
        background: rgba(35,58,139,0.05);
        border-radius: 6px;
        width: 100%;
        min-height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .jobseeker-card .view-details-btn {
        background: linear-gradient(135deg, #1976d2, #1565c0);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        margin-top: 14px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(25,118,210,0.2);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        width: 100%;
        max-width: 160px;
    }
    .jobseeker-card .view-details-btn:hover {
        background: linear-gradient(135deg, #1565c0, #0d47a1);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(25,118,210,0.3);
    }
    
    .status-pending {
        color: #ff9800;
        font-weight: 600;
        background: rgba(255,152,0,0.1);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-accepted {
        color: #4caf50;
        font-weight: 600;
        background: rgba(76,175,80,0.1);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-rejected {
        color: #f44336;
        font-weight: 600;
        background: rgba(244,67,54,0.1);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .accept-btn {
        background: linear-gradient(135deg, #4caf50, #45a049) !important;
        color: white !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 8px 16px !important;
        font-size: 0.9rem !important;
        cursor: pointer !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 2px 6px rgba(76,175,80,0.3) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .accept-btn:hover {
        background: linear-gradient(135deg, #45a049, #388e3c) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(76,175,80,0.4) !important;
    }
    
    .reject-btn {
        background: linear-gradient(135deg, #f44336, #d32f2f) !important;
        color: white !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 8px 16px !important;
        font-size: 0.9rem !important;
        cursor: pointer !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 2px 6px rgba(244,67,54,0.3) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .reject-btn:hover {
        background: linear-gradient(135deg, #d32f2f, #b71c1c) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(244,67,54,0.4) !important;
    }
    
    /* Hide hamburger menu on desktop */
    .hamburger-menu {
        display: none;
    }
    /* Enhanced responsive design */
    @media (max-width: 1400px) {
        .jobseeker-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 1200px) {
        .jobseeker-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }
    @media (max-width: 900px) {
        .jobseeker-grid {
            grid-template-columns: repeat(1, 1fr);
            gap: 20px;
        }
        .jobseeker-card {
            max-width: 400px;
            margin: 0 auto;
        }
    }
    @media (max-width: 768px) {
        .header {
            padding: 8px 16px;
            height: 56px;
        }
        
        .header img {
            height: 36px;
            margin-right: 12px;
        }
        
        .header-title {
            font-size: 1.4rem;
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
            padding-top: 56px;
            flex-direction: column;
        }
        
        /* Mobile Sidebar - Hidden by default */
        .sidebar {
            position: fixed !important;
            top: 56px !important;
            left: -240px !important;
            width: 240px !important;
            height: calc(100vh - 56px) !important;
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
            display: block;
            width: 90%;
            text-align: left;
        }
        
        .main-content {
            margin-left: 0;
            padding: 20px;
            height: auto;
        }
        
        .jobseeker-grid {
            grid-template-columns: 1fr;
            gap: 16px;
            padding: 0 10px;
        }
        
        .jobseeker-card {
            max-width: 100%;
            margin: 0;
            padding: 16px 12px 12px 12px;
            min-height: 200px;
        }
        
        .jobseeker-card .jobseeker-name {
            font-size: 0.95rem;
        }
        
        .jobseeker-card .jobseeker-info {
            font-size: 0.8rem;
            padding: 4px 6px;
        }
        
        .jobseeker-card .view-details-btn {
            padding: 6px 16px;
            font-size: 0.8rem;
            max-width: 140px;
        }
    }
    
    @media (max-width: 480px) {
        .header {
            padding: 6px 12px;
            height: 48px;
        }
        
        .header img {
            height: 28px;
            margin-right: 8px;
        }
        
        .header-title {
            font-size: 1.2rem;
        }
        
        .layout {
            padding-top: 48px;
        }
        
        .sidebar {
            padding: 12px;
            gap: 6px;
        }
        
        .sidebar a {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
        
        .main-content {
            padding: 16px;
        }
        
        .jobseeker-grid {
            gap: 12px;
            padding: 0 5px;
        }
        
        .jobseeker-card {
            padding: 14px 10px 10px 10px;
            min-height: 180px;
        }
        
        .jobseeker-card .jobseeker-name {
            font-size: 0.9rem;
            margin-top: 8px;
            margin-bottom: 4px;
        }
        
        .jobseeker-card .jobseeker-info {
            font-size: 0.75rem;
            padding: 3px 5px;
            margin-bottom: 8px;
        }
        
        .jobseeker-card .view-details-btn {
            padding: 5px 12px;
            font-size: 0.75rem;
            max-width: 120px;
        }
        
        .status-pending,
        .status-accepted,
        .status-rejected {
            font-size: 0.75rem;
            padding: 3px 8px;
        }
    }
    
    @media (max-width: 800px) {
        .layout {
            flex-direction: column;
        }
        .sidebar {
            width: 100%;
            height: auto;
            position: static;
            flex-direction: row;
            padding: 16px 0 0 0;
            overflow-x: auto;
        }
        .main-content {
            margin-left: 0;
            padding: 20px;
            height: auto;
        }
        .jobseeker-grid {
            grid-template-columns: repeat(1, 1fr);
            padding: 0;
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
    }

    /* Dropdown styling */
    #statusFilter {
        box-shadow: 0 2px 8px rgba(35,58,139,0.1);
    }

    #statusFilter:hover {
        border-color: #1976d2;
        box-shadow: 0 4px 12px rgba(35,58,139,0.15);
    }

    #statusFilter:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25,118,210,0.1);
    }

    /* Responsive dropdown */
    @media (max-width: 768px) {
        .main-content > div:first-child {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        
        .main-content > div:first-child > div:first-child {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        #statusFilter, #occupationFilter, #skillsFilter {
            min-width: 120px;
            font-size: 0.85rem;
        }
        
        /* Mobile Filter Stack - Vertical Layout */
        .main-content > div:first-child > div:first-child {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
            align-items: stretch !important;
        }
        
        .main-content > div:first-child > div:first-child > div {
            width: 100% !important;
            margin-bottom: 8px !important;
        }
        
        .main-content > div:first-child > div:first-child label {
            font-size: 0.9rem !important;
            margin-bottom: 4px !important;
            display: block !important;
        }
        
        .main-content > div:first-child > div:first-child select {
            width: 100% !important;
            padding: 8px 12px !important;
            font-size: 0.9rem !important;
            box-sizing: border-box !important;
        }
        
        /* Move total display to the right on tablet */
        .main-content > div:first-child > div:last-child {
            justify-content: flex-end !important;
        }
        
    }

    @media (max-width: 480px) {
        .main-content > div:first-child > div:first-child {
            gap: 8px;
        }
        
        #statusFilter, #occupationFilter, #skillsFilter {
            min-width: 100px;
            font-size: 0.8rem;
            padding: 6px 12px;
        }
        
        /* Move total display to the right on mobile */
        .main-content > div:first-child > div:last-child {
            justify-content: flex-end !important;
        }
    }

/* Enhanced Modal Styles */
.details-section {
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border-radius: 16px;
    padding: 24px 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(25,118,210,0.08);
    border: 1px solid rgba(35,58,139,0.1);
    position: relative;
    overflow: hidden;
}

.details-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #233a8b, #1976d2);
}

.section-title {
    color: #233a8b;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 16px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e3f2fd;
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 40px;
    height: 2px;
    background: linear-gradient(90deg, #1976d2, #42a5f5);
}

.section-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.field-item {
    padding: 8px 12px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    background: rgba(255,255,255,0.5);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.field-item:hover {
    background: rgba(35,58,139,0.02);
    transform: translateX(4px);
}

.field-item:last-child {
    border-bottom: none;
}

.field-item strong {
    color: #1976d2;
    font-weight: 600;
    min-width: 140px;
    display: inline-block;
    font-size: 0.95rem;
}

.employment-type {
    background: #e3f2fd;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 12px;
    font-size: 1.05rem;
    color: #1976d2;
}

.work-experience-item {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.company-name {
    font-size: 1.1rem;
    color: #1976d2;
    margin-bottom: 6px;
}

.position, .duration, .status, .address {
    font-size: 0.95rem;
    color: #666;
    margin-bottom: 4px;
}

.language-item {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 8px 12px;
    margin-bottom: 8px;
}

.language-item strong {
    color: #1976d2;
    font-weight: 600;
}

/* Skills Badge Styles */
.skills-category {
    margin-bottom: 16px;
}

.skills-label {
    margin-bottom: 8px;
    font-size: 0.95rem;
    color: #1976d2;
    font-weight: 600;
}

.skills-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.skill-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: capitalize;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.skill-badge.predefined {
    background: linear-gradient(135deg, #e3f2fd, #f0f4ff);
    color: #1976d2;
    border: 1px solid #bbdefb;
}

.skill-badge.other {
    background: linear-gradient(135deg, #f3e5f5, #fce4ec);
    color: #7b1fa2;
    border: 1px solid #ce93d8;
}

.skill-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.skill-badge.predefined:hover {
    background: linear-gradient(135deg, #bbdefb, #e3f2fd);
}

.skill-badge.other:hover {
    background: linear-gradient(135deg, #ce93d8, #f3e5f5);
}

/* Responsive improvements */
@media (max-width: 600px) {
    .details-section {
        padding: 12px;
        margin-bottom: 12px;
    }
    
    .section-title {
        font-size: 1rem;
    }
    
    .field-item strong {
        min-width: 100px;
        font-size: 0.9rem;
    }
    
    .work-experience-item {
        padding: 8px;
    }
    
    .skills-badges {
        gap: 6px;
    }
    
    .skill-badge {
        padding: 4px 8px;
        font-size: 0.8rem;
    }
    }

    /* Resume Modal Button Hover Effects */
    #resumeModal button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    
    #resumeModal a:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    
    #resumeCloseBtn:hover {
        background: #e0e0e0 !important;
        transform: scale(1.1);
    }

    /* Modal Enhancements */
    #acceptModal input:focus,
    #rejectModal textarea:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25,118,210,0.1);
    }

    #acceptModal input:hover,
    #rejectModal textarea:hover {
        border-color: #bbdefb;
    }

    /* Close button hover effects */
    #acceptCloseBtn:hover,
    #rejectCloseBtn:hover {
        background: #e0e0e0 !important;
        transform: scale(1.1);
    }

    /* Button hover effects */
    #confirmAcceptBtn:hover:not(:disabled) {
        background: linear-gradient(135deg, #45a049, #388e3c) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(76,175,80,0.4) !important;
    }

    #confirmRejectBtn:hover:not(:disabled) {
        background: linear-gradient(135deg, #d32f2f, #b71c1c) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(244,67,54,0.4) !important;
    }

    #cancelAcceptBtn:hover,
    #cancelRejectBtn:hover {
        background: linear-gradient(135deg, #9e9e9e, #757575) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    }

    /* Disabled button styles */
    #confirmAcceptBtn:disabled,
    #confirmRejectBtn:disabled {
        opacity: 0.8;
        cursor: not-allowed;
        transform: none !important;
    }

    /* Spinner animation */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Modal backdrop blur effect */
    #acceptModal,
    #rejectModal {
        backdrop-filter: blur(4px);
    }

    </style>
</head>
<body>
    <div class="header" id="mainHeader">
        <div style="display: flex; align-items: center;">
            <button class="hamburger-menu" id="hamburgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="Logo">
            <span class="header-title" id="headerTitle">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;" id="adminSection">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="adminUsername" style="font-size: 1rem; font-weight: 500;">Welcome, Admin</span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php">📊 DASHBOARD</a>
            <a href="job_postings.php">💼 JOB POSTINGS</a>
            <a href="#" class="active">👥 JOBSEEKERS</a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;">➕ ADD ACCOUNT</a>
            <a href="analytics.php">📊 Analytics</a>
            <a href="announcement.php">📢 ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #e3f2fd;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div>
                        <h2 id="pageTitle" style="color:#233a8b; font-size:1.8rem; font-weight:700; margin:0;">Pending Jobseekers</h2>
                        <p style="color:#666; margin:8px 0 0 0; font-size:1.1rem;">Review and manage jobseeker applications</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;" class="filter-container" id="filterContainer">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label for="statusFilter" style="font-weight: 600; color: #233a8b; font-size: 0.9rem;">Filter by Status:</label>
                            <select id="statusFilter" onchange="showTab(this.value)" style="padding: 8px 16px; border: 2px solid #e3f2fd; border-radius: 8px; background: #fff; color: #233a8b; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; min-width: 140px;">
                                <option value="all">📋 Pending</option>
                                <option value="accepted">✅ Accepted</option>
                                <option value="rejected">❌ Rejected</option>
                            </select>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label for="occupationFilter" style="font-weight: 600; color: #233a8b; font-size: 0.9rem;">Filter by Occupation:</label>
                            <select id="occupationFilter" onchange="filterByOccupation(this.value)" style="padding: 8px 16px; border: 2px solid #e3f2fd; border-radius: 8px; background: #fff; color: #233a8b; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; min-width: 180px;">
                                <option value="all">🔍 All Occupations</option>
                            </select>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label for="skillsFilter" style="font-weight: 600; color: #233a8b; font-size: 0.9rem;">Filter by Skills:</label>
                            <select id="skillsFilter" onchange="filterBySkills(this.value)" style="padding: 8px 16px; border: 2px solid #e3f2fd; border-radius: 8px; background: #fff; color: #233a8b; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; min-width: 180px;">
                                <option value="all">🛠️ All Skills</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="background: linear-gradient(135deg, #e3f2fd, #f0f4ff); padding: 12px 20px; border-radius: 12px; border-left: 4px solid #1976d2;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #1976d2;" id="applicantCount">0</div>
                        <div style="font-size: 0.9rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Total</div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons Section - Below the header -->
            <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px; padding: 0 20px;">
                <button id="multipleAcceptBtn" onclick="toggleMultipleAcceptMode()" style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255,152,0,0.3);">
                    📋 Multiple Accept
                </button>
                <button id="bulkAcceptBtn" onclick="showBulkAcceptModal()" style="background: linear-gradient(135deg, #4caf50, #45a049); color: white; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(76,175,80,0.3); display: none;">
                     Send & Accept All
                </button>
            </div>
            <div id="jobseekerCards" class="jobseeker-grid"></div>
        </div>
    </div>

        <!-- Resume Modal -->
        <div id="resumeModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.25);justify-content:center;align-items:center;backdrop-filter:blur(4px);">
            <div style="background:linear-gradient(135deg, #ffffff, #f8fafc);border-radius:16px;box-shadow:0 12px 40px rgba(25,118,210,0.2);padding:24px 20px 20px 20px;max-width:480px;width:100%;margin:0 auto;position:relative;border:1px solid rgba(35,58,139,0.1);">
                <!-- Close button -->
                <button id="resumeCloseBtn" style="position:absolute;top:12px;right:12px;background:#f5f5f5;color:#666;border:none;border-radius:50%;width:32px;height:32px;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;box-shadow:0 2px 6px rgba(0,0,0,0.1);">×</button>
                
                <!-- Header -->
                <div style="text-align:center;margin-bottom:20px;padding-bottom:16px;border-bottom:2px solid #e3f2fd;">
                    <div style="background:linear-gradient(135deg, #233a8b, #1976d2);color:white;padding:8px 16px;border-radius:8px;display:inline-block;margin-bottom:12px;box-shadow:0 3px 8px rgba(35,58,139,0.3);">
                        <h3 style="margin:0;font-size:1.1rem;font-weight:700;letter-spacing:0.3px;">📄 Resume Preview</h3>
                    </div>
                    <p style="color:#666;margin:0;font-size:0.85rem;">Review the jobseeker's resume</p>
                </div>
                
                <!-- Resume content -->
                <div id="resumePreview" style="margin-bottom:20px;min-height:150px;background:#fff;border-radius:8px;padding:16px;border:1px solid #e0e0e0;box-shadow:0 2px 6px rgba(0,0,0,0.05);"></div>
                
                <!-- Action buttons -->
                <div style="display:flex;gap:10px;justify-content:center;margin-top:16px;">
                    <button id="resumeNextBtn" style="background:linear-gradient(135deg, #1976d2, #1565c0);color:#fff;border:none;border-radius:8px;padding:10px 24px;font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.3s ease;box-shadow:0 3px 8px rgba(25,118,210,0.3);text-transform:uppercase;letter-spacing:0.3px;">Continue to Details</button>
                    <button id="resumeDownloadBtn" style="background:linear-gradient(135deg, #4caf50, #45a049);color:#fff;border:none;border-radius:8px;padding:10px 20px;font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.3s ease;box-shadow:0 3px 8px rgba(76,175,80,0.3);display:none;">📥 Download</button>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div id="detailsModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
            <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(25,118,210,0.18);padding:32px 28px 24px 28px;max-width:540px;width:100%;margin:0 auto;max-height:90vh;overflow-y:auto;">
                <div id="detailsContent"></div>
                <button id="detailsCloseBtn" style="background:#bdbdbd;color:#1a3876;border:none;border-radius:22px;padding:12px 44px;font-weight:bold;font-size:1.08rem;cursor:pointer;margin-top:18px;">Close</button>
            </div>
        </div>

        <!-- Accept Modal -->
        <div id="acceptModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.25);justify-content:center;align-items:center;backdrop-filter:blur(4px);">
            <div style="background:linear-gradient(135deg, #ffffff, #f8fafc);border-radius:20px;box-shadow:0 12px 40px rgba(25,118,210,0.25);padding:32px 28px 24px 28px;max-width:520px;width:100%;margin:0 auto;position:relative;border:1px solid rgba(35,58,139,0.1);">
                <!-- Close button -->
                <button id="acceptCloseBtn" style="position:absolute;top:16px;right:16px;background:#f5f5f5;color:#666;border:none;border-radius:50%;width:32px;height:32px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;box-shadow:0 2px 6px rgba(0,0,0,0.1);">×</button>
                
                <!-- Header -->
                <div style="text-align:center;margin-bottom:24px;padding-bottom:20px;border-bottom:2px solid #e3f2fd;">
                    <div style="background:linear-gradient(135deg, #4caf50, #45a049);color:white;padding:12px 20px;border-radius:12px;display:inline-block;margin-bottom:16px;box-shadow:0 4px 12px rgba(76,175,80,0.3);">
                        <h3 style="margin:0;font-size:1.2rem;font-weight:700;letter-spacing:0.3px;">✅ Accept Jobseeker</h3>
                    </div>
                    <p style="color:#666;margin:0;font-size:0.95rem;line-height:1.4;">Enter the email address where the jobseeker details should be sent.</p>
                </div>
                
                <!-- Form -->
                <div style="margin-bottom:24px;">
                    <label style="display:block;margin-bottom:10px;font-weight:600;color:#333;font-size:0.95rem;">Email Address:</label>
                    <input type="email" id="employerEmail" placeholder="Enter email address..." style="width:100%;padding:14px 16px;border:2px solid #e3f2fd;border-radius:10px;font-size:1rem;transition:all 0.3s ease;box-sizing:border-box;" required>
                </div>
                
                <!-- Action buttons -->
                <div style="display:flex;gap:12px;justify-content:center;">
                    <button id="confirmAcceptBtn" style="background:linear-gradient(135deg, #4caf50, #45a049);color:#fff;border:none;border-radius:10px;padding:12px 28px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.3s ease;box-shadow:0 4px 12px rgba(76,175,80,0.3);position:relative;display:flex;align-items:center;justify-content:center;min-height:48px;">
                        <span class="btn-text">Send & Accept</span>
                        <div class="spinner" id="acceptSpinner" style="display: none;align-items:center;justify-content:center;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">
                            <div class="spinner-inner" style="width:20px;height:20px;border:2px solid #ffffff;border-top:2px solid transparent;border-radius:50%;animation:spin 1s linear infinite;"></div>
                        </div>
                    </button>
                    <button id="cancelAcceptBtn" style="background:linear-gradient(135deg, #bdbdbd, #9e9e9e);color:#1a3876;border:none;border-radius:10px;padding:12px 28px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.3s ease;box-shadow:0 2px 6px rgba(0,0,0,0.1);">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div id="rejectModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.25);justify-content:center;align-items:center;backdrop-filter:blur(4px);">
            <div style="background:linear-gradient(135deg, #ffffff, #f8fafc);border-radius:20px;box-shadow:0 12px 40px rgba(244,67,54,0.25);padding:32px 28px 24px 28px;max-width:520px;width:100%;margin:0 auto;position:relative;border:1px solid rgba(244,67,54,0.1);">
                <!-- Close button -->
                <button id="rejectCloseBtn" style="position:absolute;top:16px;right:16px;background:#f5f5f5;color:#666;border:none;border-radius:50%;width:32px;height:32px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;box-shadow:0 2px 6px rgba(0,0,0,0.1);">×</button>
                
                <!-- Header -->
                <div style="text-align:center;margin-bottom:24px;padding-bottom:20px;border-bottom:2px solid #ffebee;">
                    <div style="background:linear-gradient(135deg, #f44336, #d32f2f);color:white;padding:12px 20px;border-radius:12px;display:inline-block;margin-bottom:16px;box-shadow:0 4px 12px rgba(244,67,54,0.3);">
                        <h3 style="margin:0;font-size:1.2rem;font-weight:700;letter-spacing:0.3px;">❌ Reject Jobseeker</h3>
                    </div>
                    <p style="color:#666;margin:0;font-size:0.95rem;line-height:1.4;">Please provide a reason for rejection. This will be sent to the jobseeker.</p>
                </div>
                
                <!-- Form -->
                <div style="margin-bottom:24px;">
                    <label style="display:block;margin-bottom:10px;font-weight:600;color:#333;font-size:0.95rem;">Reason for Rejection:</label>
                    <textarea id="rejectionReason" placeholder="Enter reason for rejection..." style="width:100%;height:120px;padding:14px 16px;border:2px solid #ffebee;border-radius:10px;resize:vertical;font-family:inherit;font-size:1rem;transition:all 0.3s ease;box-sizing:border-box;" required></textarea>
                </div>
                
                <!-- Action buttons -->
                <div style="display:flex;gap:12px;justify-content:center;">
                    <button id="confirmRejectBtn" style="background:linear-gradient(135deg, #f44336, #d32f2f);color:#fff;border:none;border-radius:10px;padding:12px 28px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.3s ease;box-shadow:0 4px 12px rgba(244,67,54,0.3);position:relative;display:flex;align-items:center;justify-content:center;min-height:48px;">
                        <span class="btn-text">Reject Application</span>
                        <div class="spinner" id="rejectSpinner" style="display: none;align-items:center;justify-content:center;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">
                            <div class="spinner-inner" style="width:20px;height:20px;border:2px solid #ffffff;border-top:2px solid transparent;border-radius:50%;animation:spin 1s linear infinite;"></div>
                        </div>
                    </button>
                    <button id="cancelRejectBtn" style="background:linear-gradient(135deg, #bdbdbd, #9e9e9e);color:#1a3876;border:none;border-radius:10px;padding:12px 28px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.3s ease;box-shadow:0 2px 6px rgba(0,0,0,0.1);">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Bulk Accept Modal -->
        <div id="bulkAcceptModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.25);justify-content:center;align-items:center;backdrop-filter:blur(4px);">
            <div style="background:linear-gradient(135deg, #ffffff, #f8fafc);border-radius:20px;box-shadow:0 12px 40px rgba(25,118,210,0.25);padding:32px 28px 24px 28px;max-width:600px;width:100%;margin:0 auto;position:relative;border:1px solid rgba(35,58,139,0.1);">
                <!-- Close button -->
                <button id="bulkAcceptCloseBtn" style="position:absolute;top:16px;right:16px;background:#f5f5f5;color:#666;border:none;border-radius:50%;width:32px;height:32px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;box-shadow:0 2px 6px rgba(0,0,0,0.1);">×</button>
                
                <!-- Header -->
                <div style="text-align:center;margin-bottom:24px;padding-bottom:20px;border-bottom:2px solid #e3f2fd;">
                    <div style="background:linear-gradient(135deg, #4caf50, #45a049);color:white;padding:12px 20px;border-radius:12px;display:inline-block;margin-bottom:16px;box-shadow:0 4px 12px rgba(76,175,80,0.3);">
                        <h3 style="margin:0;font-size:1.2rem;font-weight:700;letter-spacing:0.3px;">Send & Accept All Jobseekers</h3>
                    </div>
                    <p style="color:#666;margin:0;font-size:0.95rem;line-height:1.4;">Select multiple jobseekers and send their details to a single employer.</p>
                </div>
                
                <!-- Selected jobseekers list -->
                <div style="margin-bottom:24px;">
                    <label style="display:block;margin-bottom:10px;font-weight:600;color:#333;font-size:0.95rem;">Selected Jobseekers:</label>
                    <div id="selectedJobseekersList" style="max-height:200px;overflow-y:auto;border:2px solid #e3f2fd;border-radius:8px;padding:12px;background:#f8f9fa;">
                        <p style="color:#666;margin:0;font-style:italic;">No jobseekers selected</p>
                    </div>
                </div>
                
                <!-- Email input -->
                <div style="margin-bottom:24px;">
                    <label style="display:block;margin-bottom:10px;font-weight:600;color:#333;font-size:0.95rem;">Employer Email Address:</label>
                    <input type="email" id="bulkEmployerEmail" placeholder="Enter employer email address..." style="width:100%;padding:14px 16px;border:2px solid #e3f2fd;border-radius:10px;font-size:1rem;transition:all 0.3s ease;box-sizing:border-box;" required>
                </div>
                
                <!-- Action buttons -->
                <div style="display:flex;gap:12px;justify-content:center;">
                    <button id="confirmBulkAcceptBtn" style="background:linear-gradient(135deg, #4caf50, #45a049);color:#fff;border:none;border-radius:10px;padding:12px 28px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.3s ease;box-shadow:0 4px 12px rgba(76,175,80,0.3);position:relative;display:flex;align-items:center;justify-content:center;min-height:48px;">
                        <span class="btn-text">Send & Accept All</span>
                        <div class="spinner" id="bulkAcceptSpinner" style="display: none;align-items:center;justify-content:center;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">
                            <div class="spinner-inner" style="width:20px;height:20px;border:2px solid #ffffff;border-top:2px solid transparent;border-radius:50%;animation:spin 1s linear infinite;"></div>
                        </div>
                    </button>
                    <button id="cancelBulkAcceptBtn" style="background:linear-gradient(135deg, #bdbdbd, #9e9e9e);color:#1a3876;border:none;border-radius:10px;padding:12px 28px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.3s ease;box-shadow:0 2px 6px rgba(0,0,0,0.1);">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Logout Modal -->
        <div id="logoutModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
            <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(25,118,210,0.18);padding:32px 28px 24px 28px;max-width:400px;width:100%;margin:0 auto;text-align:center;">
                <div style="font-size:3rem;margin-bottom:16px;">🚪</div>
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
                
                // Mobile filter stacking
                function handleMobileFilters() {
                    const filterContainer = document.getElementById('filterContainer');
                    if (window.innerWidth <= 768) {
                        filterContainer.style.display = 'flex';
                        filterContainer.style.flexDirection = 'column';
                        filterContainer.style.alignItems = 'stretch';
                        filterContainer.style.gap = '12px';
                    } else {
                        filterContainer.style.display = 'flex';
                        filterContainer.style.flexDirection = 'row';
                        filterContainer.style.alignItems = 'center';
                        filterContainer.style.gap = '12px';
                    }
                }
                
                // Initial check
                handleMobileFilters();
                
                // Check on resize
                window.addEventListener('resize', handleMobileFilters);
                
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

            // Update username display and ADD ACCOUNT link visibility
            fetch('session_check.php')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('adminUsername').textContent = data.username; // Remove "Welcome, " prefix
                    if (data.isMainAdmin) {
                        document.getElementById('addAccountLink').style.display = 'block';
                    } else {
                        document.getElementById('addAccountLink').style.display = 'none';
                    }
                })
                .catch(() => {
                    console.error('Session check failed');
                });
                
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
                    adminSection.style.whiteSpace = 'nowrap';
                    
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
                    headerTitle.style.maxWidth = 'none';
                    
                    // Reset admin section
                    adminSection.style.marginRight = '20px';
                    adminSection.style.gap = '8px';
                    adminSection.style.fontSize = '1rem';
                    adminSection.style.maxWidth = 'none';
                    adminSection.style.overflow = 'visible';
                    adminSection.style.textOverflow = 'unset';
                    adminSection.style.whiteSpace = 'normal';
                    
                    // Reset logo
                    const logo = header.querySelector('img');
                    if (logo) {
                        logo.style.height = '48px';
                        logo.style.marginRight = '16px';
                    }
                }
            }
            
            // Remove "Welcome, " text for both mobile and desktop
            function removeWelcomeText() {
                const adminUsername = document.getElementById('adminUsername');
                if (adminUsername && adminUsername.textContent.includes('Welcome, ')) {
                    adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
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
                        // Remove "Welcome, " text
                        if (adminUsername.textContent.includes('Welcome, ')) {
                            adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
                        }
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
            removeWelcomeText();
            
            // Initial check
            handleMobileHeader();
            
            // Check on resize
            window.addEventListener('resize', handleMobileHeader);

        let allJobseekers = [];
        let currentTab = 'all';
        let currentOccupationFilter = 'all';
        let currentSkillsFilter = 'all';
        let selectedJobseekers = [];
        let multipleAcceptMode = false;
        
        // Tab switching functionality
        function showTab(tab) {
            currentTab = tab;
            
            // Update dropdown selection
            document.getElementById('statusFilter').value = tab;
            
            // Reset occupation and skills filters when switching tabs
            currentOccupationFilter = 'all';
            currentSkillsFilter = 'all';
            document.getElementById('occupationFilter').value = 'all';
            document.getElementById('skillsFilter').value = 'all';
            
            // Show/hide Multiple Accept button based on status
            const multipleAcceptBtn = document.getElementById('multipleAcceptBtn');
            if (tab === 'accepted' || tab === 'rejected') {
                multipleAcceptBtn.style.display = 'none';
                // Exit multiple accept mode if active
                if (multipleAcceptMode) {
                    multipleAcceptMode = false;
                    toggleMultipleAcceptMode();
                }
            } else {
                multipleAcceptBtn.style.display = 'block';
            }
            
            // Update page title
            const title = document.getElementById('pageTitle');
            switch(tab) {
                case 'all':
                    title.textContent = 'Pending Jobseekers';
                    break;
                case 'accepted':
                    title.textContent = 'Accepted Jobseekers';
                    break;
                case 'rejected':
                    title.textContent = 'Rejected Jobseekers';
                    break;
            }
            
            // Filter and display jobseekers
            filterAndDisplayJobseekers();
        }
        
        // Function to populate occupation filter dropdown
        function populateOccupationFilter() {
            const occupationFilter = document.getElementById('occupationFilter');
            
            // Get all unique occupations from all jobseekers
            const occupations = new Set();
            
            allJobseekers.forEach(jobseeker => {
                // Add occupation1, occupation2, occupation3 if they exist and are not empty/n/a
                if (jobseeker.occupation1 && jobseeker.occupation1 !== 'n/a' && jobseeker.occupation1.trim() !== '') {
                    occupations.add(jobseeker.occupation1.trim());
                }
                if (jobseeker.occupation2 && jobseeker.occupation2 !== 'n/a' && jobseeker.occupation2.trim() !== '') {
                    occupations.add(jobseeker.occupation2.trim());
                }
                if (jobseeker.occupation3 && jobseeker.occupation3 !== 'n/a' && jobseeker.occupation3.trim() !== '') {
                    occupations.add(jobseeker.occupation3.trim());
                }
            });
            
            // Clear existing options except the first one
            occupationFilter.innerHTML = '<option value="all">🔍 All Occupations</option>';
            
            // Add unique occupations to dropdown
            const sortedOccupations = Array.from(occupations).sort();
            sortedOccupations.forEach(occupation => {
                const option = document.createElement('option');
                option.value = occupation;
                option.textContent = occupation;
                occupationFilter.appendChild(option);
            });
        }
        
        // Function to filter by occupation
        function filterByOccupation(occupation) {
            currentOccupationFilter = occupation;
            filterAndDisplayJobseekers();
        }
        
        // Function to populate skills filter dropdown
        function populateSkillsFilter() {
            const skillsFilter = document.getElementById('skillsFilter');
            
            // Get all unique skills from all jobseekers with case-insensitive handling
            const skills = new Map(); // Use Map to store original case and check for duplicates
            
            console.log('Populating skills filter with', allJobseekers.length, 'jobseekers');
            
            // Debug: Log the first jobseeker's data structure to see what skill fields exist
            if (allJobseekers.length > 0) {
                console.log('First jobseeker data:', allJobseekers[0]);
                console.log('Skill fields in first jobseeker:', Object.keys(allJobseekers[0]).filter(key => key.startsWith('skill_')));
            }
            
            allJobseekers.forEach(jobseeker => {
                // Add predefined skills if they exist
                const predefinedSkills = [
                    { name: 'Auto mechanic', field: 'skill_auto_mechanic' },
                    { name: 'Electrician', field: 'skill_electrician' },
                    { name: 'Photography', field: 'skill_photography' },
                    { name: 'Beautician', field: 'skill_beautician' },
                    { name: 'Embroidery', field: 'skill_embroidery' },
                    { name: 'Plumbing', field: 'skill_plumbing' },
                    { name: 'Carpentry work', field: 'skill_carpentry' },
                    { name: 'Gardening', field: 'skill_gardening' },
                    { name: 'Sewing dresses', field: 'skill_sewing' },
                    { name: 'Computer literature', field: 'skill_computer' },
                    { name: 'Masonry', field: 'skill_masonry' },
                    { name: 'Stenography', field: 'skill_stenography' },
                    { name: 'Domestic chores', field: 'skill_domestic' },
                    { name: 'Painter/Artist', field: 'skill_painter' },
                    { name: 'Tailoring', field: 'skill_tailoring' },
                    { name: 'Driver', field: 'skill_driver' },
                    { name: 'Painting job', field: 'skill_painting' }
                ];
                
                predefinedSkills.forEach(skill => {
                    console.log(`Checking skill "${skill.name}" with field "${skill.field}" for jobseeker ${jobseeker.id}:`, jobseeker[skill.field]);
                    if (jobseeker[skill.field] && (jobseeker[skill.field] === 1 || jobseeker[skill.field] === '1')) {
                        const lowerSkill = skill.name.toLowerCase();
                        if (!skills.has(lowerSkill)) {
                            skills.set(lowerSkill, skill.name); // Store original case
                            console.log('Found predefined skill:', skill.name, 'for jobseeker', jobseeker.id);
                        }
                    }
                });
                
                // Add skills from the "others" field
                if (jobseeker.skill_others && jobseeker.skill_others !== 'n/a' && jobseeker.skill_others.trim() !== '') {
                    console.log('Processing skill_others for jobseeker', jobseeker.id, ':', jobseeker.skill_others);
                    const othersSkills = parseOthersSkills(jobseeker.skill_others);
                    console.log('Parsed others skills:', othersSkills);
                    othersSkills.forEach(skill => {
                        const lowerSkill = skill.toLowerCase();
                        if (!skills.has(lowerSkill)) {
                            skills.set(lowerSkill, skill); // Store original case
                            console.log('Found other skill:', skill, 'for jobseeker', jobseeker.id);
                        }
                    });
                }
            });
            
            // Clear existing options except the first one
            skillsFilter.innerHTML = '<option value="all">🛠️ All Skills</option>';
            
            // Add unique skills to dropdown (sorted by original case)
            const sortedSkills = Array.from(skills.values()).sort();
            console.log('Found skills:', sortedSkills);
            
            sortedSkills.forEach(skill => {
                const option = document.createElement('option');
                option.value = skill;
                option.textContent = skill;
                skillsFilter.appendChild(option);
            });
        }
        
        // Function to parse skills from the "others" field
        function parseOthersSkills(othersText) {
            if (!othersText || othersText === 'n/a' || othersText.trim() === '') {
                return [];
            }
            
            // Split by common separators: comma, semicolon, "and", "or", newline
            const separators = [',', ';', ' and ', ' or ', '\n', '\r\n'];
            let skills = [othersText.trim()];
            
            // Split by each separator
            separators.forEach(separator => {
                const newSkills = [];
                skills.forEach(skill => {
                    if (skill.includes(separator)) {
                        newSkills.push(...skill.split(separator).map(s => s.trim()).filter(s => s !== ''));
                    } else {
                        newSkills.push(skill);
                    }
                });
                skills = newSkills;
            });
            
            // Clean up and filter out empty strings
            return skills
                .map(skill => skill.trim())
                .filter(skill => skill !== '' && skill !== 'n/a')
                .filter(skill => skill.length > 1); // Filter out single characters
        }
        
        // Function to filter by skills
        function filterBySkills(skill) {
            currentSkillsFilter = skill;
            filterAndDisplayJobseekers();
        }
        
        function filterAndDisplayJobseekers() {
                const container = document.getElementById('jobseekerCards');
                const countElement = document.getElementById('applicantCount');
            let filteredData = allJobseekers;
            
            if (currentTab === 'all') {
                // Only show pending jobseekers in the main tab
                filteredData = allJobseekers.filter(j => !j.application_status || j.application_status === 'Pending' || j.application_status === '');
            } else if (currentTab === 'accepted') {
                filteredData = allJobseekers.filter(j => j.application_status === 'Accepted');
            } else if (currentTab === 'rejected') {
                filteredData = allJobseekers.filter(j => j.application_status === 'Rejected');
            }
            
            // Apply occupation filter
            if (currentOccupationFilter !== 'all') {
                filteredData = filteredData.filter(j => {
                    return (j.occupation1 && j.occupation1 === currentOccupationFilter) ||
                           (j.occupation2 && j.occupation2 === currentOccupationFilter) ||
                           (j.occupation3 && j.occupation3 === currentOccupationFilter);
                });
            }
            
            // Apply skills filter
            if (currentSkillsFilter !== 'all') {
                filteredData = filteredData.filter(j => {
                    // Check predefined skills using correct field names
                    const predefinedSkills = [
                        { name: 'Auto mechanic', field: 'skill_auto_mechanic' },
                        { name: 'Electrician', field: 'skill_electrician' },
                        { name: 'Photography', field: 'skill_photography' },
                        { name: 'Beautician', field: 'skill_beautician' },
                        { name: 'Embroidery', field: 'skill_embroidery' },
                        { name: 'Plumbing', field: 'skill_plumbing' },
                        { name: 'Carpentry work', field: 'skill_carpentry' },
                        { name: 'Gardening', field: 'skill_gardening' },
                        { name: 'Sewing dresses', field: 'skill_sewing' },
                        { name: 'Computer literature', field: 'skill_computer' },
                        { name: 'Masonry', field: 'skill_masonry' },
                        { name: 'Stenography', field: 'skill_stenography' },
                        { name: 'Domestic chores', field: 'skill_domestic' },
                        { name: 'Painter/Artist', field: 'skill_painter' },
                        { name: 'Tailoring', field: 'skill_tailoring' },
                        { name: 'Driver', field: 'skill_driver' },
                        { name: 'Painting job', field: 'skill_painting' }
                    ];
                    
                    // Check if the selected skill is a predefined skill (case-insensitive)
                    const matchingSkill = predefinedSkills.find(skill => 
                        skill.name.toLowerCase() === currentSkillsFilter.toLowerCase()
                    );
                    
                    if (matchingSkill && j[matchingSkill.field] && (j[matchingSkill.field] === 1 || j[matchingSkill.field] === '1')) {
                        return true;
                    }
                    
                    // Check if the selected skill is in the "others" field (case-insensitive)
                    if (j.skill_others && j.skill_others !== 'n/a' && j.skill_others.trim() !== '') {
                        const othersSkills = parseOthersSkills(j.skill_others);
                        return othersSkills.some(skill => skill.toLowerCase() === currentSkillsFilter.toLowerCase());
                    }
                    
                    return false;
                });
            }
            
            // Update the count display
            countElement.textContent = filteredData.length;
            
            if (!filteredData.length) {
                    container.innerHTML = `
                        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #f8fafc, #ffffff); border-radius: 16px; border: 2px dashed #e0e0e0;">
                            <div style="font-size: 4rem; margin-bottom: 16px;">📋</div>
                            <h3 style="color: #666; margin: 0 0 8px 0; font-size: 1.2rem;">No Jobseekers found</h3>
                            <p style="color: #999; margin: 0; font-size: 0.95rem;">There are no applicants matching the current filter.</p>
                        </div>
                    `;
                    return;
                }
            
            container.innerHTML = '';
            filteredData.forEach(j => {
                    const card = document.createElement('div');
                    card.className = 'jobseeker-card';
                    let imgHtml = '';
                    if (j.resume_file) {
                        const ext = j.resume_file.split('.').pop().toLowerCase();
                        if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
                            imgHtml = `<img src="../uploads/resumes/${j.resume_file}" alt="Resume Image" style="width:80px;height:80px;object-fit:cover;border-radius:12px;background:#fff;margin-bottom:10px;box-shadow:0 1px 4px rgba(35,58,139,0.10);">`;
                        } else {
                            // Show PDF or document icon for non-image files
                            imgHtml = `
                                <div style="width:80px;height:80px;background:linear-gradient(135deg, #e3f2fd, #f0f4ff);border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;margin-bottom:10px;box-shadow:0 1px 4px rgba(35,58,139,0.10);border:1px solid #bbdefb;">
                                    <div style="font-size:1.8rem;color:#1976d2;margin-bottom:4px;">📄</div>
                                    <div style="font-size:0.7rem;color:#1976d2;font-weight:600;text-transform:uppercase;letter-spacing:0.3px;">${ext}</div>
                                </div>
                            `;
                        }
                    } else {
                        // Show placeholder when no resume
                        imgHtml = `
                            <div style="width:80px;height:80px;background:linear-gradient(135deg, #f5f5f5, #fafafa);border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;margin-bottom:10px;box-shadow:0 1px 4px rgba(0,0,0,0.05);border:1px solid #e0e0e0;">
                                <div style="font-size:1.8rem;color:#999;margin-bottom:4px;">📄</div>
                                <div style="font-size:0.7rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:0.3px;">No Resume</div>
                            </div>
                        `;
                    }
                
                // Different button layout based on status
                let actionButtons = '';
                if (j.application_status === 'Accepted' || j.application_status === 'Rejected') {
                    actionButtons = `<div class="action-buttons" style="margin-top: 8px; display: flex; gap: 8px; justify-content: center;">
                        
                    </div>`;
                } else {
                    actionButtons = `<div class="action-buttons" style="margin-top: 8px; display: flex; gap: 8px; justify-content: center;">
                        <button class="accept-btn" onclick="showAcceptModal(${j.id})" style="background: #4caf50; color: white; border: none; border-radius: 6px; padding: 6px 12px; font-size: 0.9rem; cursor: pointer;">Accept</button>
                        <button class="reject-btn" onclick="rejectJobseeker(${j.id})" style="background: #f44336; color: white; border: none; border-radius: 6px; padding: 6px 12px; font-size: 0.9rem; cursor: pointer;">Reject</button>
                    </div>`;
                }
                
                    card.innerHTML = `
                        <div style="position: relative; margin-bottom: 16px;">
                            <div style="position: absolute; top: -8px; left: -8px; z-index: 10; display: none;" class="checkbox-container">
                                <input type="checkbox" class="jobseeker-checkbox" data-jobseeker-id="${j.id}" style="width: 18px; height: 18px; cursor: pointer; accent-color: #4caf50;">
                            </div>
                            ${imgHtml}
                            <div style="position: absolute; top: -8px; right: -8px; background: linear-gradient(135deg, #233a8b, #1976d2); color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">ID: ${j.id}</div>
                        </div>
                        <div class="jobseeker-name">${j.firstname} ${j.middlename && j.middlename !== 'n/a' ? j.middlename + ' ' : ''}${j.surname}${j.suffix && j.suffix !== 'n/a' ? ', ' + j.suffix : ''}</div>
                        <div class="jobseeker-info"><strong>Age:</strong> ${j.age} years</div>
                        <div class="jobseeker-info"><strong>Gender:</strong> ${j.sex}</div>
                        <div class="jobseeker-info"><strong>Status:</strong> <span class="status-${j.application_status ? j.application_status.toLowerCase() : 'pending'}">${j.application_status || 'Pending'}</span></div>
                        <button class="view-details-btn">📋 View Details</button>
                    ${actionButtons}
                    `;
                
                // Add click handler for view details button
                    card.querySelector('.view-details-btn').onclick = function() {
                        showResumeModal(j);
                    };
                    
                    // Add checkbox change handler
                    const checkbox = card.querySelector('.jobseeker-checkbox');
                    checkbox.addEventListener('change', function() {
                        handleCheckboxChange(this, j);
                    });
                    
                    container.appendChild(card);
                });
        }
        
        // Fetch jobseekers and render cards
        fetch('jobseekers.php')
            .then(r => r.json())
            .then(data => {
                allJobseekers = data;
                populateOccupationFilter();
                populateSkillsFilter();
                filterAndDisplayJobseekers();
            });

        // Modal logic
        let currentJobseeker = null;
        function showResumeModal(j) {
            currentJobseeker = j;
            document.getElementById('resumeModal').style.display = 'flex';
            // Resume preview
            const resumeDiv = document.getElementById('resumePreview');
            if (j.resume_file) {
                const ext = j.resume_file.split('.').pop().toLowerCase();
                const url = '../uploads/resumes/' + j.resume_file;
                const imageExts = ["jpg","jpeg","png","gif","bmp","webp"];
                const docExts = ["doc","docx"];
                
                if (imageExts.includes(ext)) {
                    resumeDiv.innerHTML = `
                        <div style="text-align:center;margin-bottom:16px;">
                            <div style="background:linear-gradient(135deg, #e3f2fd, #f0f4ff);padding:12px;border-radius:8px;margin-bottom:12px;border:1px solid #bbdefb;">
                                <div style="font-size:1.5rem;margin-bottom:6px;">🖼️</div>
                                <div style="font-weight:600;color:#1976d2;margin-bottom:2px;font-size:0.9rem;">Image Resume</div>
                                <div style="font-size:0.8rem;color:#666;">${ext.toUpperCase()} format</div>
                            </div>
                            <img src="${url}" alt="Resume Image" style="width:100%;max-width:300px;height:auto;border-radius:8px;border:1px solid #e0e0e0;box-shadow:0 2px 8px rgba(35,58,139,0.1);margin-bottom:12px;">
                            <div style="display:flex;gap:8px;justify-content:center;">
                                <a href="${url}" download style="background:linear-gradient(135deg, #4caf50, #45a049);color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:0.8rem;box-shadow:0 2px 6px rgba(76,175,80,0.3);transition:all 0.2s ease;">📥 Download</a>
                                <a href="${url}" style="background:linear-gradient(135deg, #1976d2, #1565c0);color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:0.8rem;box-shadow:0 2px 6px rgba(25,118,210,0.3);transition:all 0.2s ease;">👁️ View</a>
                            </div>
                        </div>
                    `;
                } else if (ext === 'pdf') {
                    resumeDiv.innerHTML = `
                        <div style="text-align:center;margin-bottom:16px;">
                            <div style="background:linear-gradient(135deg, #e3f2fd, #f0f4ff);padding:12px;border-radius:8px;margin-bottom:12px;border:1px solid #bbdefb;">
                                <div style="font-size:1.5rem;margin-bottom:6px;">📄</div>
                                <div style="font-weight:600;color:#1976d2;margin-bottom:2px;font-size:0.9rem;">PDF Resume</div>
                                <div style="font-size:0.8rem;color:#666;">Portable Document Format</div>
                            </div>
                            <iframe src="${url}" width="100%" height="250px" style="border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 8px rgba(35,58,139,0.1);margin-bottom:12px;"></iframe>
                            <div style="display:flex;gap:8px;justify-content:center;">
                                <a href="${url}" download style="background:linear-gradient(135deg, #4caf50, #45a049);color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:0.8rem;box-shadow:0 2px 6px rgba(76,175,80,0.3);transition:all 0.2s ease;">📥 Download</a>
                                <a href="${url}" style="background:linear-gradient(135deg, #1976d2, #1565c0);color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:0.8rem;box-shadow:0 2px 6px rgba(25,118,210,0.3);transition:all 0.2s ease;">🔗 Open</a>
                            </div>
                        </div>
                    `;
                } else if (docExts.includes(ext)) {
                    resumeDiv.innerHTML = `
                        <div style="text-align:center;">
                            <div style="background:linear-gradient(135deg, #e3f2fd, #f0f4ff);padding:16px;border-radius:8px;margin-bottom:16px;border:1px solid #bbdefb;">
                                <div style="font-size:2rem;color:#1976d2;margin-bottom:8px;">📄</div>
                                <div style="font-weight:600;color:#1976d2;margin-bottom:4px;font-size:0.9rem;">Document Resume</div>
                                <div style="color:#666;font-size:0.8rem;margin-bottom:2px;">${ext.toUpperCase()} format</div>
                                <div style="color:#999;font-size:0.7rem;">${j.resume_file}</div>
                            </div>
                            <div style="display:flex;gap:8px;justify-content:center;">
                                <a href="${url}" download style="background:linear-gradient(135deg, #4caf50, #45a049);color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:0.8rem;box-shadow:0 2px 6px rgba(76,175,80,0.3);transition:all 0.2s ease;">📥 Download</a>
                            </div>
                        </div>
                    `;
                } else {
                    resumeDiv.innerHTML = `
                        <div style="text-align:center;">
                            <div style="background:linear-gradient(135deg, #e3f2fd, #f0f4ff);padding:16px;border-radius:8px;margin-bottom:16px;border:1px solid #bbdefb;">
                                <div style="font-size:2rem;color:#1976d2;margin-bottom:8px;">📄</div>
                                <div style="font-weight:600;color:#1976d2;margin-bottom:4px;font-size:0.9rem;">Resume File</div>
                                <div style="color:#666;font-size:0.8rem;margin-bottom:2px;">${ext.toUpperCase()} format</div>
                                <div style="color:#999;font-size:0.7rem;">${j.resume_file}</div>
                            </div>
                            <div style="display:flex;gap:8px;justify-content:center;">
                                <a href="${url}" download style="background:linear-gradient(135deg, #4caf50, #45a049);color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:0.8rem;box-shadow:0 2px 6px rgba(76,175,80,0.3);transition:all 0.2s ease;">📥 Download</a>
                            </div>
                        </div>
                    `;
                }
            } else {
                resumeDiv.innerHTML = `
                    <div style="text-align:center;padding:20px;">
                        <div style="background:linear-gradient(135deg, #f5f5f5, #fafafa);border:2px dashed #bdbdbd;border-radius:8px;padding:24px;margin-bottom:16px;">
                            <div style="font-size:2rem;color:#999;margin-bottom:12px;">📄</div>
                            <div style="font-weight:600;color:#666;margin-bottom:6px;font-size:0.9rem;">No Resume Uploaded</div>
                            <div style="color:#999;font-size:0.8rem;">This jobseeker has not uploaded a resume yet</div>
                        </div>
                        <div style="color:#666;font-size:0.8rem;font-style:italic;">You can still review their profile details by clicking "Continue to Details"</div>
                    </div>
                `;
            }
        }
        document.getElementById('resumeNextBtn').onclick = function() {
            document.getElementById('resumeModal').style.display = 'none';
            showDetailsModal(currentJobseeker);
        };
        
        document.getElementById('resumeCloseBtn').onclick = function() {
            document.getElementById('resumeModal').style.display = 'none';
        };
        function showDetailsModal(j) {
            document.getElementById('detailsModal').style.display = 'flex';
            
            // Helper function to convert 1/0 to yes/no for display
            function boolToText(value) {
                if (value === 1 || value === '1' || value === true) return 'Yes';
                if (value === 0 || value === '0' || value === false) return 'No';
                return value;
            }
            
            // Helper functions for better data formatting
            function formatField(label, value, defaultValue = 'Not provided') {
                if (!value || value === 'n/a' || value === '' || value === null) {
                    return '';
                }
                return `<div class="field-item"><strong>${label}:</strong> ${value}</div>`;
            }
            
            function formatBooleanField(label, value) {
                if (!value || value === 0 || value === '0' || value === false) {
                    return '';
                }
                return `<div class="field-item"><strong>${label}:</strong> ${boolToText(value)}</div>`;
            }
            
            function formatListField(label, values) {
                const validValues = values.filter(v => v && v !== 'n/a' && v !== '');
                if (validValues.length === 0) return '';
                return `<div class="field-item"><strong>${label}:</strong> ${validValues.join(', ')}</div>`;
            }
            
            function formatWorkExperience(company, address, position, months, status) {
                if (!company || company === 'n/a' || company === '') return '';
                let exp = `<div class="work-experience-item">`;
                exp += `<div class="company-name"><strong>${company}</strong></div>`;
                if (position && position !== 'n/a') exp += `<div class="position">Position: ${position}</div>`;
                if (months && months !== 'n/a') exp += `<div class="duration">Duration: ${months} months</div>`;
                if (status && status !== 'n/a') exp += `<div class="status">Status: ${status}</div>`;
                if (address && address !== 'n/a') exp += `<div class="address">Address: ${address}</div>`;
                exp += `</div>`;
                return exp;
            }
            
            function formatSkills(skills) {
                if (!skills || skills === 'n/a' || skills === '') return '';
                return `<div class="field-item"><strong>Other Skills:</strong> ${skills}</div>`;
            }
            
            function formatLanguageProficiency(lang, read, write, speak, understand) {
                const skills = [];
                if (read && (read === 1 || read === '1' || read === true)) skills.push('Read');
                if (write && (write === 1 || write === '1' || write === true)) skills.push('Write');
                if (speak && (speak === 1 || speak === '1' || speak === true)) skills.push('Speak');
                if (understand && (understand === 1 || understand === '1' || understand === true)) skills.push('Understand');
                
                if (skills.length === 0) return '';
                return `<div class="language-item"><strong>${lang}:</strong> ${skills.join(', ')}</div>`;
            }
            
            function formatIndividualSkills(j) {
                const predefinedSkills = [];
                const otherSkills = [];
                
                // Collect predefined skills
                if (j.skill_auto_mechanic && (j.skill_auto_mechanic === 1 || j.skill_auto_mechanic === '1')) predefinedSkills.push('Auto mechanic');
                if (j.skill_electrician && (j.skill_electrician === 1 || j.skill_electrician === '1')) predefinedSkills.push('Electrician');
                if (j.skill_photography && (j.skill_photography === 1 || j.skill_photography === '1')) predefinedSkills.push('Photography');
                if (j.skill_beautician && (j.skill_beautician === 1 || j.skill_beautician === '1')) predefinedSkills.push('Beautician');
                if (j.skill_embroidery && (j.skill_embroidery === 1 || j.skill_embroidery === '1')) predefinedSkills.push('Embroidery');
                if (j.skill_plumbing && (j.skill_plumbing === 1 || j.skill_plumbing === '1')) predefinedSkills.push('Plumbing');
                if (j.skill_carpentry && (j.skill_carpentry === 1 || j.skill_carpentry === '1')) predefinedSkills.push('Carpentry work');
                if (j.skill_gardening && (j.skill_gardening === 1 || j.skill_gardening === '1')) predefinedSkills.push('Gardening');
                if (j.skill_sewing && (j.skill_sewing === 1 || j.skill_sewing === '1')) predefinedSkills.push('Sewing dresses');
                if (j.skill_computer && (j.skill_computer === 1 || j.skill_computer === '1')) predefinedSkills.push('Computer literature');
                if (j.skill_masonry && (j.skill_masonry === 1 || j.skill_masonry === '1')) predefinedSkills.push('Masonry');
                if (j.skill_stenography && (j.skill_stenography === 1 || j.skill_stenography === '1')) predefinedSkills.push('Stenography');
                if (j.skill_domestic && (j.skill_domestic === 1 || j.skill_domestic === '1')) predefinedSkills.push('Domestic chores');
                if (j.skill_painter && (j.skill_painter === 1 || j.skill_painter === '1')) predefinedSkills.push('Painter/Artist');
                if (j.skill_tailoring && (j.skill_tailoring === 1 || j.skill_tailoring === '1')) predefinedSkills.push('Tailoring');
                if (j.skill_driver && (j.skill_driver === 1 || j.skill_driver === '1')) predefinedSkills.push('Driver');
                if (j.skill_painting && (j.skill_painting === 1 || j.skill_painting === '1')) predefinedSkills.push('Painting job');
                
                // Parse and collect other skills
                if (j.skill_others && j.skill_others !== 'n/a' && j.skill_others !== '') {
                    const othersText = j.skill_others.trim();
                    // Split by common separators: comma, semicolon, "and", "or", newline
                    const separators = [',', ';', ' and ', ' or ', '\n', '\r\n'];
                    let skills = [othersText];
                    
                    // Split by each separator
                    separators.forEach(separator => {
                        const newSkills = [];
                        skills.forEach(skill => {
                            if (skill.includes(separator)) {
                                newSkills.push(...skill.split(separator).map(s => s.trim()).filter(s => s !== ''));
                            } else {
                                newSkills.push(skill);
                            }
                        });
                        skills = newSkills;
                    });
                    
                    // Clean up and filter out empty strings
                    otherSkills.push(...skills
                        .map(skill => skill.trim())
                        .filter(skill => skill !== '' && skill !== 'n/a')
                        .filter(skill => skill.length > 1)); // Filter out single characters
                }
                
                let result = '';
                
                // Display predefined skills as badges
                if (predefinedSkills.length > 0) {
                    result += '<div class="skills-category">';
                    result += '<div class="skills-label"><strong>Predefined Skills:</strong></div>';
                    result += '<div class="skills-badges">';
                    predefinedSkills.forEach(skill => {
                        result += `<span class="skill-badge predefined">${skill}</span>`;
                    });
                    result += '</div></div>';
                }
                
                // Display other skills as badges
                if (otherSkills.length > 0) {
                    result += '<div class="skills-category">';
                    result += '<div class="skills-label"><strong>Other Skills:</strong></div>';
                    result += '<div class="skills-badges">';
                    otherSkills.forEach(skill => {
                        result += `<span class="skill-badge other">${skill}</span>`;
                    });
                    result += '</div></div>';
                }
                
                if (predefinedSkills.length === 0 && otherSkills.length === 0) return '';
                return result;
            }
            
            function formatDisability(j) {
                if (!j.hasDisability || j.hasDisability === 0 || j.hasDisability === '0') return '';
                
                const disabilities = [];
                if (j.disability_speech && (j.disability_speech === 1 || j.disability_speech === '1')) disabilities.push('Speech');
                if (j.disability_hearing && (j.disability_hearing === 1 || j.disability_hearing === '1')) disabilities.push('Hearing');
                if (j.disability_visual && (j.disability_visual === 1 || j.disability_visual === '1')) disabilities.push('Visual');
                if (j.disability_mental && (j.disability_mental === 1 || j.disability_mental === '1')) disabilities.push('Mental');
                if (j.disability_others && (j.disability_others === 1 || j.disability_others === '1')) {
                    if (j.disability_other && j.disability_other !== 'n/a' && j.disability_other !== '') {
                        disabilities.push('Others: ' + j.disability_other);
                    } else {
                        disabilities.push('Others');
                    }
                }
                
                if (disabilities.length === 0) return '';
                return `<div class="field-item"><strong>Disability:</strong> ${disabilities.join(', ')}</div>`;
            }
            // Build the content using the new formatting functions
            let content = '';
            
            // Personal Information Section
            content += `<div class="details-section">
                <h3 class="section-title">I. PERSONAL INFORMATION</h3>
                <div class="section-content">
                    ${formatField('Name', `${j.firstname} ${j.middlename && j.middlename !== 'n/a' ? j.middlename + ' ' : ''}${j.surname}${j.suffix && j.suffix !== 'n/a' ? ', ' + j.suffix : ''}`)}
                    ${formatField('Age', j.age)}
                    ${formatField('Sex', j.sex)}
                    ${formatField('Date of Birth', j.dob)}
                    ${formatField('Civil Status', j.civilstatus)}
                    ${formatField('Religion', j.religion)}
                    ${formatField('Address', `${j.street || ''}, ${j.barangay || ''}, ${j.municipality || ''}, ${j.province || ''}`.replace(/^,\s*|,\s*$/g, ''))}
                    ${formatField('Contact Number', j.contact)}
                    ${formatField('Email', j.email)}
                    ${formatField('TIN', j.tin)}
                    ${formatField('Height', j.height ? j.height + ' ft.' : '')}
                    ${formatDisability(j)}
                </div>
            </div>`;
            
            // Employment Status Section
            content += `<div class="details-section">
                <h3 class="section-title">II. EMPLOYMENT STATUS</h3>
                <div class="section-content">`;
            
            // Display only the relevant employment status section based on actual data
            if (j.employed && (j.employed === 1 || j.employed === '1')) {
                content += `<div class="employment-type"><strong>Employed</strong></div>`;
                if (j.employment_type_wage && (j.employment_type_wage === 1 || j.employment_type_wage === '1')) {
                    content += formatBooleanField('Wage Employed', j.employment_type_wage);
                    // selfTypeFields (Voluntary, Vendor, etc.) are under wage employed
                    if (j.self_type_voluntary && (j.self_type_voluntary === 1 || j.self_type_voluntary === '1')) content += formatBooleanField('Voluntary/PhilHealth', j.self_type_voluntary);
                    if (j.self_type_vendor && (j.self_type_vendor === 1 || j.self_type_vendor === '1')) content += formatBooleanField('Vendor / Retailer', j.self_type_vendor);
                    if (j.self_type_homebased && (j.self_type_homebased === 1 || j.self_type_homebased === '1')) content += formatBooleanField('Home-based worker', j.self_type_homebased);
                    if (j.self_type_transport && (j.self_type_transport === 1 || j.self_type_transport === '1')) content += formatBooleanField('Transport', j.self_type_transport);
                    if (j.self_type_domestic && (j.self_type_domestic === 1 || j.self_type_domestic === '1')) content += formatBooleanField('Domestic Worker', j.self_type_domestic);
                    if (j.self_type_fisherfolk && (j.self_type_fisherfolk === 1 || j.self_type_fisherfolk === '1')) content += formatBooleanField('Fisherfolk', j.self_type_fisherfolk);
                    if (j.self_type_others && (j.self_type_others === 1 || j.self_type_others === '1') && j.other_jobs) content += formatField('Other Job/s', j.other_jobs);
                }
                if (j.employment_type_self && (j.employment_type_self === 1 || j.employment_type_self === '1')) {
                    content += formatBooleanField('Self-Employed', j.employment_type_self);
                    if (j.self_employed_specify && j.self_employed_specify !== 'n/a' && j.self_employed_specify !== '') {
                        content += formatField('Self-Employed Specify', j.self_employed_specify);
                    }
                }
            } else if (j.unemployed && (j.unemployed === 1 || j.unemployed === '1')) {
                content += `<div class="employment-type"><strong>Unemployed</strong></div>`;
                content += formatField('Duration Looking for Work', j.unemployed_months ? j.unemployed_months + ' months' : '');
                if (j.unemployed_type_first && (j.unemployed_type_first === 1 || j.unemployed_type_first === '1')) content += formatBooleanField('First-time Jobseeker/Graduate', j.unemployed_type_first);
                if (j.unemployed_type_local && (j.unemployed_type_local === 1 || j.unemployed_type_local === '1')) content += formatBooleanField('Local Contract', j.unemployed_type_local);
                if (j.unemployed_type_resigned && (j.unemployed_type_resigned === 1 || j.unemployed_type_resigned === '1')) content += formatBooleanField('Resigned', j.unemployed_type_resigned);
                if (j.unemployed_type_finished && (j.unemployed_type_finished === 1 || j.unemployed_type_finished === '1')) content += formatBooleanField('Finished Contract (OFW)', j.unemployed_type_finished);
                if (j.unemployed_type_public && (j.unemployed_type_public === 1 || j.unemployed_type_public === '1')) content += formatBooleanField('Public Contract', j.unemployed_type_public);
                if (j.unemployed_type_retired && (j.unemployed_type_retired === 1 || j.unemployed_type_retired === '1')) content += formatBooleanField('Retired', j.unemployed_type_retired);
                if (j.unemployed_type_terminated && (j.unemployed_type_terminated === 1 || j.unemployed_type_terminated === '1')) content += formatBooleanField('Terminated/Laid off (Local)', j.unemployed_type_terminated);
            } else {
                // If neither employed nor unemployed is clearly set, show a default message
                content += `<div class="field-item"><strong>Employment Status:</strong> Not specified</div>`;
            }
            
            content += formatField('OFW', j.ofw);
            content += formatField('OFW Country', j.ofw_country);
            content += formatField('OFW Returnee', j.returnee);
            content += formatField('Deployment Country', j.deployment_country);
            content += formatField('Month of Return', j.return_month);
            content += formatField('Year of Return', j.return_year);
            content += formatField('Employed Abroad in Philippines', j.abroad);
            content += formatField('Job Beneficiary', j.beneficiary);
            content += formatField('Household ID', j.household_id);
            
            content += `</div></div>`;
            
            // Job Preferences Section
            content += `<div class="details-section">
                <h3 class="section-title">II. JOB PREFERENCE</h3>
                <div class="section-content">
                    ${formatListField('Preferred Occupations', [j.occupation1, j.occupation2, j.occupation3])}
                    ${formatListField('Local Work Locations', [j.local1, j.local2, j.local3])}
                    ${formatListField('Overseas Work Locations', [j.overseas1, j.overseas2, j.overseas3])}
                    ${formatBooleanField('Full-time', j.fulltime)}
                    ${formatBooleanField('Part-time', j.parttime)}
                    </div>
            </div>`;
            
            // Language Proficiency Section
            content += `<div class="details-section">
                <h3 class="section-title">III. LANGUAGE / DIALECT PROFICIENCY</h3>
                <div class="section-content">
                    ${formatLanguageProficiency('English', j.english_read, j.english_write, j.english_speak, j.english_understand)}
                    ${formatLanguageProficiency('Filipino', j.filipino_read, j.filipino_write, j.filipino_speak, j.filipino_understand)}
                    ${formatLanguageProficiency('Mandarin', j.mandarin_read, j.mandarin_write, j.mandarin_speak, j.mandarin_understand)}
                    ${formatLanguageProficiency(j.other_language || 'Other Language', j.other_read, j.other_write, j.other_speak, j.other_understand)}
                </div>
            </div>`;
            
            // Educational Background Section
            content += `<div class="details-section">
                <h3 class="section-title">IV. EDUCATIONAL BACKGROUND</h3>
                <div class="section-content">
                    ${formatField('Currently in School', j.inschool)}
                    ${formatField('Education Level', j.level)}
                    ${formatField('Course', j.course)}
                    ${formatField('Year Graduated', j.year_graduated)}
                    ${formatField('Level Reached', j.level_reached)}
                    ${formatField('Last Attended', j.last_attended)}
                </div>
            </div>`;
            
            // Technical/Vocational Training Section
            content += `<div class="details-section">
                <h3 class="section-title">V. TECHNICAL/VOCATIONAL AND OTHER TRAINING</h3>
                <div class="section-content">
                    ${formatField('Training Course 1', j.training_course_1)}
                    ${formatField('Training Hours 1', j.training_hours_1)}
                    ${formatField('Training Institution 1', j.training_institution_1)}
                    ${formatField('Training Skills 1', j.training_skills_1)}
                    ${formatField('Training Certificate 1', j.training_cert_1)}
                    ${formatField('Training Course 2', j.training_course_2)}
                    ${formatField('Training Hours 2', j.training_hours_2)}
                    ${formatField('Training Institution 2', j.training_institution_2)}
                    ${formatField('Training Skills 2', j.training_skills_2)}
                    ${formatField('Training Certificate 2', j.training_cert_2)}
                    ${formatField('Training Course 3', j.training_course_3)}
                    ${formatField('Training Hours 3', j.training_hours_3)}
                    ${formatField('Training Institution 3', j.training_institution_3)}
                    ${formatField('Training Skills 3', j.training_skills_3)}
                    ${formatField('Training Certificate 3', j.training_cert_3)}
                </div>
            </div>`;
            
            // Eligibility/Professional License Section
            content += `<div class="details-section">
                <h3 class="section-title">VI. ELIGIBILITY/PROFESSIONAL LICENSE</h3>
                <div class="section-content">
                    ${formatField('Eligibility 1', j.eligibility_1)}
                    ${formatField('Eligibility Date 1', j.eligibility_date_1)}
                    ${formatField('Eligibility 2', j.eligibility_2)}
                    ${formatField('Eligibility Date 2', j.eligibility_date_2)}
                    ${formatField('PRC License 1', j.prc_1)}
                    ${formatField('PRC Valid Until 1', j.prc_valid_1)}
                    ${formatField('PRC License 2', j.prc_2)}
                    ${formatField('PRC Valid Until 2', j.prc_valid_2)}
                </div>
            </div>`;
            
            // Work Experience Section
            content += `<div class="details-section">
                <h3 class="section-title">VII. WORK EXPERIENCE</h3>
                <div class="section-content">
                    ${formatWorkExperience(j.company_name_1, j.company_address_1, j.position_1, j.months_1, j.status_1)}
                    ${formatWorkExperience(j.company_name_2, j.company_address_2, j.position_2, j.months_2, j.status_2)}
                    ${formatWorkExperience(j.company_name_3, j.company_address_3, j.position_3, j.months_3, j.status_3)}
                </div>
            </div>`;
            
            // Skills Section
            content += `<div class="details-section">
                <h3 class="section-title">VIII. OTHER SKILLS ACQUIRED</h3>
                <div class="section-content">
                    ${formatIndividualSkills(j)}
                </div>
            </div>`;
            
            document.getElementById('detailsContent').innerHTML = content;
        }
        document.getElementById('detailsCloseBtn').onclick = function() {
            document.getElementById('detailsModal').style.display = 'none';
        };
        
        // Accept/Reject functionality
        let currentJobseekerId = null;
        
        function showAcceptModal(jobseekerId) {
            currentJobseekerId = jobseekerId;
            document.getElementById('employerEmail').value = '';
            document.getElementById('acceptModal').style.display = 'flex';
        }
        
        function rejectJobseeker(jobseekerId) {
            currentJobseekerId = jobseekerId;
            document.getElementById('rejectModal').style.display = 'flex';
            document.getElementById('rejectionReason').value = '';
        }
        
        function updateJobseekerStatusWithCallback(jobseekerId, status, rejectionReason = null, employerEmail = null, callback = null) {
            const requestData = {
                jobseeker_id: jobseekerId,
                status: status
            };
            
            if (rejectionReason) {
                requestData.rejection_reason = rejectionReason;
            }
            
            if (employerEmail) {
                requestData.employer_email = employerEmail;
            }
            
            fetch('update_jobseeker_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the local data
                    const jobseeker = allJobseekers.find(j => j.id == jobseekerId);
                    if (jobseeker) {
                        jobseeker.application_status = status;
                    }
                    
                    if (status === 'Accepted') {
                        // Send email with jobseeker details
                        sendJobseekerEmail(jobseekerId, employerEmail);
                        
                        // Create notification for the jobseeker
                        createJobseekerNotification(jobseekerId, 'Application Accepted!', 'Congratulations! Your job application has been reffered to the employer. You will receive an email with further details.');
                        
                        // Show success SweetAlert for acceptance
                        Swal.fire({
                            icon: 'success',
                            title: 'Application Accepted!',
                            text: 'The jobseeker has been successfully accepted and email sent to employer.',
                            confirmButtonColor: '#4caf50',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Close modal after SweetAlert is dismissed
                            document.getElementById('acceptModal').style.display = 'none';
                            resetAcceptModal();
                        });
                    } else if (status === 'Rejected') {
                        // Create notification for the jobseeker
                        createJobseekerNotification(jobseekerId, 'Application Update', 'Your job application status has been updated. Please check your dashboard for details.');
                        
                        // Show success SweetAlert for rejection
                        Swal.fire({
                            icon: 'success',
                            title: 'Application Rejected!',
                            text: 'The jobseeker has been successfully rejected and notified.',
                            confirmButtonColor: '#f44336',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Close modal after SweetAlert is dismissed
                            document.getElementById('rejectModal').style.display = 'none';
                            resetRejectModal();
                        });
                    }
                    
                    // Refresh the display without page reload
                    filterAndDisplayJobseekers();
                    
                    // Call callback with success
                    if (callback) callback(true);
                } else {
                    // Show error SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error updating jobseeker status: ' + data.message,
                        confirmButtonColor: '#f44336'
                    });
                    
                    // Call callback with failure
                    if (callback) callback(false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error SweetAlert
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating jobseeker status',
                    confirmButtonColor: '#f44336'
                });
                
                // Call callback with failure
                if (callback) callback(false);
            });
        }

        function updateJobseekerStatus(jobseekerId, status, rejectionReason = null, employerEmail = null) {
            const requestData = {
                jobseeker_id: jobseekerId,
                status: status
            };
            
            if (rejectionReason) {
                requestData.rejection_reason = rejectionReason;
            }
            
            if (employerEmail) {
                requestData.employer_email = employerEmail;
            }
            
            fetch('update_jobseeker_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the local data
                    const jobseeker = allJobseekers.find(j => j.id == jobseekerId);
                    if (jobseeker) {
                        jobseeker.application_status = status;
                    }
                    
                    if (status === 'Accepted') {
                        // Send email with jobseeker details
                        sendJobseekerEmail(jobseekerId, employerEmail);
                        
                        // Create notification for the jobseeker
                        createJobseekerNotification(jobseekerId, 'Application Accepted!', 'Congratulations! Your job application has been reffered to the employer. You will receive an email with further details.');
                    } else if (status === 'Rejected') {
                        // Create notification for the jobseeker
                        createJobseekerNotification(jobseekerId, 'Application Update', 'Your job application status has been updated. Please check your dashboard for details.');
                        
                        // Show success SweetAlert for rejection
                        Swal.fire({
                            icon: 'success',
                            title: 'Application Rejected!',
                            text: 'The jobseeker has been successfully rejected and notified.',
                            confirmButtonColor: '#f44336',
                            confirmButtonText: 'OK'
                        });
                    }
                    
                    // Refresh the display without page reload
                    filterAndDisplayJobseekers();
                } else {
                    // Show error SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error updating jobseeker status: ' + data.message,
                        confirmButtonColor: '#f44336'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error SweetAlert
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating jobseeker status',
                    confirmButtonColor: '#f44336'
                });
            });
        }
        
        function createJobseekerNotification(jobseekerId, title, message) {
            // Get the jobseeker's user_id from the jobseeker data
            const jobseeker = allJobseekers.find(j => j.id == jobseekerId);
            if (!jobseeker || !jobseeker.user_id) {
                console.error('Jobseeker or user_id not found');
                return;
            }
            
            fetch('../Employee/create_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: jobseeker.user_id,
                    title: title,
                    message: message,
                    type: 'success'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Notification created successfully');
                } else {
                    console.error('Failed to create notification:', data.message);
                }
            })
            .catch(error => {
                console.error('Error creating notification:', error);
            });
        }
        
        function sendJobseekerEmail(jobseekerId, employerEmail) {
            fetch('send_email_with_phpmailer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    jobseeker_id: jobseekerId,
                    employer_email: employerEmail
                })
            })
            .then(response => {
                console.log('Email response status:', response.status);
                return response.text(); // Use text() instead of json() to handle HTML errors
            })
            .then(data => {
                console.log('Email response data:', data);
                try {
                    const jsonData = JSON.parse(data);
                    if (jsonData.success) {
                        console.log('Email sent successfully');
                    } else {
                        console.error('Email failed to send:', jsonData.message);
                    }
                } catch (e) {
                    console.log('Email sent successfully (generic response)');
                }
            })
            .catch(error => {
                console.error('Email error:', error);
                // Email error is logged but doesn't show SweetAlert to avoid duplication
            });
        }
        
        // Email validation function
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Accept modal event listeners
        document.getElementById('confirmAcceptBtn').onclick = function() {
            const email = document.getElementById('employerEmail').value.trim();
            const acceptBtn = document.getElementById('confirmAcceptBtn');
            const btnText = document.querySelector('#confirmAcceptBtn .btn-text');
            const spinner = document.getElementById('acceptSpinner');
            const cancelBtn = document.getElementById('cancelAcceptBtn');
            
            if (!email) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Field',
                    text: 'Please enter an email address.',
                    confirmButtonColor: '#4caf50'
                });
                return;
            }
            if (!isValidEmail(email)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address.',
                    confirmButtonColor: '#4caf50'
                });
                return;
            }
            
            // Show loading state
            acceptBtn.disabled = true;
            cancelBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'flex';
            
            if (currentJobseekerId) {
                // Call the update function and handle the response
                updateJobseekerStatusWithCallback(currentJobseekerId, 'Accepted', null, email, function(success) {
                    // Stop spinner and reset button state
                    acceptBtn.disabled = false;
                    cancelBtn.disabled = false;
                    btnText.style.display = 'inline';
                    spinner.style.display = 'none';
                    
                    // Modal will be closed by SweetAlert .then() callback
                    // No need to close it here
                });
            }
        };
        
        document.getElementById('cancelAcceptBtn').onclick = function() {
            document.getElementById('acceptModal').style.display = 'none';
            resetAcceptModal();
        };
        
        document.getElementById('acceptCloseBtn').onclick = function() {
            document.getElementById('acceptModal').style.display = 'none';
            resetAcceptModal();
        };
        
        function resetAcceptModal() {
            const acceptBtn = document.getElementById('confirmAcceptBtn');
            const btnText = document.querySelector('#confirmAcceptBtn .btn-text');
            const spinner = document.getElementById('acceptSpinner');
            const cancelBtn = document.getElementById('cancelAcceptBtn');
            
            acceptBtn.disabled = false;
            cancelBtn.disabled = false;
            btnText.style.display = 'inline';
            spinner.style.display = 'none';
            document.getElementById('employerEmail').value = '';
        }
        
        // Reject modal event listeners
        document.getElementById('confirmRejectBtn').onclick = function() {
            const rejectionReason = document.getElementById('rejectionReason').value.trim();
            const rejectBtn = document.getElementById('confirmRejectBtn');
            const btnText = document.querySelector('#confirmRejectBtn .btn-text');
            const spinner = document.getElementById('rejectSpinner');
            const cancelBtn = document.getElementById('cancelRejectBtn');
            
            if (!rejectionReason) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Field',
                    text: 'Please provide a reason for rejection.',
                    confirmButtonColor: '#f44336'
                });
                return;
            }
            
            // Show loading state
            rejectBtn.disabled = true;
            cancelBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'flex';
            
            if (currentJobseekerId) {
                // Call the update function and handle the response
                updateJobseekerStatusWithCallback(currentJobseekerId, 'Rejected', rejectionReason, function(success) {
                    // Stop spinner and reset button state
                    rejectBtn.disabled = false;
                    cancelBtn.disabled = false;
                    btnText.style.display = 'inline';
                    spinner.style.display = 'none';
                    
                    // Modal will be closed by SweetAlert .then() callback
                    // No need to close it here
                });
            }
        };
        
        document.getElementById('cancelRejectBtn').onclick = function() {
            document.getElementById('rejectModal').style.display = 'none';
            resetRejectModal();
        };
        
        document.getElementById('rejectCloseBtn').onclick = function() {
            document.getElementById('rejectModal').style.display = 'none';
            resetRejectModal();
        };
        
        function resetRejectModal() {
            const rejectBtn = document.getElementById('confirmRejectBtn');
            const btnText = document.querySelector('#confirmRejectBtn .btn-text');
            const spinner = document.getElementById('rejectSpinner');
            const cancelBtn = document.getElementById('cancelRejectBtn');
            
            rejectBtn.disabled = false;
            cancelBtn.disabled = false;
            btnText.style.display = 'inline';
            spinner.style.display = 'none';
            document.getElementById('rejectionReason').value = '';
        }
        
        // Close modals on outside click
        window.onclick = function(e) {
            if (e.target === document.getElementById('resumeModal')) document.getElementById('resumeModal').style.display = 'none';
            if (e.target === document.getElementById('detailsModal')) document.getElementById('detailsModal').style.display = 'none';
            if (e.target === document.getElementById('acceptModal')) document.getElementById('acceptModal').style.display = 'none';
            if (e.target === document.getElementById('rejectModal')) document.getElementById('rejectModal').style.display = 'none';
            if (e.target === document.getElementById('logoutModal')) document.getElementById('logoutModal').style.display = 'none';
        };
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

        // Multiple Accept Mode Toggle
        function toggleMultipleAcceptMode() {
            multipleAcceptMode = !multipleAcceptMode;
            const multipleAcceptBtn = document.getElementById('multipleAcceptBtn');
            const bulkAcceptBtn = document.getElementById('bulkAcceptBtn');
            const checkboxes = document.querySelectorAll('.checkbox-container');
            
            if (multipleAcceptMode) {
                // Enter multiple accept mode
                multipleAcceptBtn.textContent = '❌ Cancel';
                multipleAcceptBtn.style.background = 'linear-gradient(135deg, #f44336, #d32f2f)';
                multipleAcceptBtn.style.boxShadow = '0 2px 8px rgba(244,67,54,0.3)';
                
                // Show all checkboxes
                checkboxes.forEach(checkbox => {
                    checkbox.style.display = 'block';
                });
                
                // Hide individual action buttons
                document.querySelectorAll('.action-buttons').forEach(btn => {
                    btn.style.display = 'none';
                });
                
            } else {
                // Exit multiple accept mode
                multipleAcceptBtn.textContent = '📋 Multiple Accept';
                multipleAcceptBtn.style.background = 'linear-gradient(135deg, #ff9800, #f57c00)';
                multipleAcceptBtn.style.boxShadow = '0 2px 8px rgba(255,152,0,0.3)';
                
                // Hide all checkboxes
                checkboxes.forEach(checkbox => {
                    checkbox.style.display = 'none';
                });
                
                // Show individual action buttons
                document.querySelectorAll('.action-buttons').forEach(btn => {
                    btn.style.display = 'flex';
                });
                
                // Clear selections
                selectedJobseekers = [];
                document.querySelectorAll('.jobseeker-checkbox').forEach(cb => cb.checked = false);
                updateBulkAcceptButton();
            }
        }
        
        // Bulk Accept Functionality
        function handleCheckboxChange(checkbox, jobseeker) {
            const jobseekerId = jobseeker.id;
            
            if (checkbox.checked) {
                if (!selectedJobseekers.find(js => js.id === jobseekerId)) {
                    selectedJobseekers.push(jobseeker);
                }
            } else {
                selectedJobseekers = selectedJobseekers.filter(js => js.id !== jobseekerId);
            }
            
            updateBulkAcceptButton();
            updateSelectedJobseekersList();
        }
        
        function updateBulkAcceptButton() {
            const bulkAcceptBtn = document.getElementById('bulkAcceptBtn');
            if (multipleAcceptMode && selectedJobseekers.length > 0) {
                bulkAcceptBtn.style.display = 'block';
                bulkAcceptBtn.textContent = ` Send & Accept All (${selectedJobseekers.length})`;
            } else {
                bulkAcceptBtn.style.display = 'none';
            }
        }
        
        function updateSelectedJobseekersList() {
            const listContainer = document.getElementById('selectedJobseekersList');
            
            if (selectedJobseekers.length === 0) {
                listContainer.innerHTML = '<p style="color:#666;margin:0;font-style:italic;">No jobseekers selected</p>';
                return;
            }
            
            let listHTML = '';
            selectedJobseekers.forEach(js => {
                listHTML += `
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:#fff;border-radius:6px;margin-bottom:8px;border:1px solid #e0e0e0;">
                        <div>
                            <strong>${js.firstname} ${js.middlename && js.middlename !== 'n/a' ? js.middlename + ' ' : ''}${js.surname}${js.suffix && js.suffix !== 'n/a' ? ', ' + js.suffix : ''}</strong>
                            <div style="font-size:0.85rem;color:#666;">ID: ${js.id} | Age: ${js.age} | ${js.sex}</div>
                        </div>
                    </div>
                `;
            });
            
            listContainer.innerHTML = listHTML;
        }
        
        function removeFromSelection(jobseekerId) {
            selectedJobseekers = selectedJobseekers.filter(js => js.id !== jobseekerId);
            
            // Uncheck the checkbox
            const checkbox = document.querySelector(`input[data-jobseeker-id="${jobseekerId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
            
            updateBulkAcceptButton();
            updateSelectedJobseekersList();
        }
        
        function showBulkAcceptModal() {
            if (selectedJobseekers.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one jobseeker to proceed.',
                    confirmButtonColor: '#4caf50'
                });
                return;
            }
            
            document.getElementById('bulkEmployerEmail').value = '';
            document.getElementById('bulkAcceptModal').style.display = 'flex';
        }
        
        function bulkAcceptJobseekers() {
            const email = document.getElementById('bulkEmployerEmail').value.trim();
            const bulkAcceptBtn = document.getElementById('confirmBulkAcceptBtn');
            const btnText = document.querySelector('#confirmBulkAcceptBtn .btn-text');
            const spinner = document.getElementById('bulkAcceptSpinner');
            const cancelBtn = document.getElementById('cancelBulkAcceptBtn');
            
            if (!email) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Field',
                    text: 'Please enter an email address.',
                    confirmButtonColor: '#4caf50'
                });
                return;
            }
            
            if (!isValidEmail(email)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address.',
                    confirmButtonColor: '#4caf50'
                });
                return;
            }
            
            // Show loading state
            bulkAcceptBtn.disabled = true;
            cancelBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'flex';
            
            // Process each jobseeker
            let completed = 0;
            let total = selectedJobseekers.length;
            let successCount = 0;
            let errorCount = 0;
            
            selectedJobseekers.forEach((jobseeker, index) => {
                setTimeout(() => {
                    updateJobseekerStatusWithCallback(jobseeker.id, 'Accepted', null, email, function(success) {
                        completed++;
                        
                        if (success) {
                            successCount++;
                        } else {
                            errorCount++;
                        }
                        
                        // Check if all are completed
                        if (completed === total) {
                            // Reset button state
                            bulkAcceptBtn.disabled = false;
                            cancelBtn.disabled = false;
                            btnText.style.display = 'inline';
                            spinner.style.display = 'none';
                            
                            // Show final result
                            if (errorCount === 0) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sent Successful!',
                                    text: `All ${successCount} jobseekers have been accepted and details sent to ${email}.`,
                                    confirmButtonColor: '#4caf50',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Clear selections and close modal
                                    selectedJobseekers = [];
                                    document.querySelectorAll('.jobseeker-checkbox').forEach(cb => cb.checked = false);
                                    updateBulkAcceptButton();
                                    document.getElementById('bulkAcceptModal').style.display = 'none';
                                    // Exit multiple accept mode
                                    multipleAcceptMode = false;
                                    toggleMultipleAcceptMode();
                                    filterAndDisplayJobseekers();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Partial Success',
                                    text: `${successCount} jobseekers accepted successfully, ${errorCount} failed.`,
                                    confirmButtonColor: '#4caf50',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Clear selections and close modal
                                    selectedJobseekers = [];
                                    document.querySelectorAll('.jobseeker-checkbox').forEach(cb => cb.checked = false);
                                    updateBulkAcceptButton();
                                    document.getElementById('bulkAcceptModal').style.display = 'none';
                                    // Exit multiple accept mode
                                    multipleAcceptMode = false;
                                    toggleMultipleAcceptMode();
                                    filterAndDisplayJobseekers();
                                });
                            }
                        }
                    });
                }, index * 500); // Stagger requests by 500ms
            });
        }
        
        // Bulk Accept Modal Event Listeners
        document.getElementById('confirmBulkAcceptBtn').onclick = function() {
            bulkAcceptJobseekers();
        };
        
        document.getElementById('cancelBulkAcceptBtn').onclick = function() {
            document.getElementById('bulkAcceptModal').style.display = 'none';
        };
        
        document.getElementById('bulkAcceptCloseBtn').onclick = function() {
            document.getElementById('bulkAcceptModal').style.display = 'none';
        };
        
        // Close bulk accept modal on outside click
        window.onclick = function(e) {
            if (e.target === document.getElementById('resumeModal')) document.getElementById('resumeModal').style.display = 'none';
            if (e.target === document.getElementById('detailsModal')) document.getElementById('detailsModal').style.display = 'none';
            if (e.target === document.getElementById('acceptModal')) document.getElementById('acceptModal').style.display = 'none';
            if (e.target === document.getElementById('rejectModal')) document.getElementById('rejectModal').style.display = 'none';
            if (e.target === document.getElementById('bulkAcceptModal')) document.getElementById('bulkAcceptModal').style.display = 'none';
            if (e.target === document.getElementById('logoutModal')) document.getElementById('logoutModal').style.display = 'none';
        };
        </script>
</body>
</html>
