<?php include 'session_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect Add Account</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #f4f7fb 60%, #e3eaff 100%);
            min-height: 100vh;
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
        
        /* Hide hamburger menu on desktop */
        .hamburger-menu {
            display: none;
        }
        .main-content {
            flex: 1;
            padding: 40px 0 32px 0;
            background: transparent;
            margin-left: 240px;
            min-height: calc(100vh - 64px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            box-sizing: border-box;
        }
        .admin-form-container, .admin-table-container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(35,58,139,0.10);
            width: 100%;
            max-width: 480px;
            margin-bottom: 32px;
            padding: 36px 32px 28px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .admin-form-container h2 {
            text-align: center;
            color: #233a8b;
            margin-bottom: 18px;
        }
        .form-group label {
            font-weight: 600;
            color: #233a8b;
        }
        .form-group input {
            width: 100%;
            padding: 0.7rem;
            border-radius: 8px;
            border: 1px solid #b3c6e0;
            font-size: 1rem;
            margin-top: 0.3rem;
            margin-bottom: 1.2rem;
            background: #f4f7fb;
        }
        .login-btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(90deg, #233a8b 60%, #4f7cf7 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 1rem;
            transition: background 0.2s;
        }
        .login-btn:hover {
            background: linear-gradient(90deg, #4f7cf7 60%, #233a8b 100%);
        }
        .admin-table-container {
            max-width: 700px;
            padding: 36px 24px 28px 24px;
        }
        .admin-table-container h3 {
            color: #233a8b;
            margin-bottom: 18px;
            text-align: center;
        }
        table.admin-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        table.admin-table th, table.admin-table td {
            border: 1px solid #b3c6e0;
            padding: 10px 8px;
            text-align: center;
        }
        table.admin-table th {
            background: #e3eaff;
            color: #233a8b;
            font-weight: bold;
        }
        table.admin-table td {
            background: #f8fafc;
        }
        .admin-action-btn {
            padding: 6px 16px;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            margin: 0 4px;
            cursor: pointer;
            transition: background 0.18s;
        }
        .admin-action-btn.edit {
            background: #1976d2;
            color: #fff;
        }
        .admin-action-btn.edit:hover {
            background: #1251a3;
        }
        .admin-action-btn.delete {
            background: #d32f2f;
            color: #fff;
        }
        .admin-action-btn.delete:hover {
            background: #a31515;
        }
        .edit-row input {
            width: 90%;
            padding: 4px 6px;
            border-radius: 4px;
            border: 1px solid #b3c6e0;
            font-size: 1rem;
        }
        #adminCreateMsg {
            text-align: center;
        }
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
            
            
            .header div {
                margin-left: auto !important;
                flex-direction: column;
                gap: 8px;
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
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
                height: auto;
            }
            
            .admin-form-container,
            .admin-table-container {
                padding: 24px 20px 20px 20px;
                margin-bottom: 24px;
            }
            
            .admin-form-container h2 {
                font-size: 1.3rem;
            }
            
            .form-group input {
                padding: 0.6rem;
                font-size: 0.95rem;
            }
            
            .login-btn {
                padding: 0.8rem;
                font-size: 1rem;
            }
            
            .admin-table-container h3 {
                font-size: 1.2rem;
            }
            
            table.admin-table th,
            table.admin-table td {
                padding: 8px 6px;
                font-size: 0.9rem;
            }
            
            .admin-action-btn {
                padding: 4px 12px;
                font-size: 0.9rem;
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
            
            .header div {
                font-size: 0.8rem;
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
            
            .admin-form-container,
            .admin-table-container {
                padding: 20px 16px 16px 16px;
                margin-bottom: 20px;
            }
            
            .admin-form-container h2 {
                font-size: 1.2rem;
            }
            
            .form-group input {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
            
            .login-btn {
                padding: 0.7rem;
                font-size: 0.95rem;
            }
            
            .admin-table-container h3 {
                font-size: 1.1rem;
            }
            
            table.admin-table th,
            table.admin-table td {
                padding: 6px 4px;
                font-size: 0.8rem;
            }
            
            .admin-action-btn {
                padding: 3px 8px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 900px) {
            .main-content, .admin-form-container, .admin-table-container {
                margin-left: 0;
                padding: 12px 2vw 12px 2vw;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                flex-direction: row;
                padding: 16px 0 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="header" id="mainHeader" style="position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background: #233a8b; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; height: 64px; box-sizing: border-box; max-width: 100vw; overflow: hidden;">
        <div style="display: flex; align-items: center; flex: 1; min-width: 0;">
            <button class="hamburger-menu" id="hamburgerMenu" style="display: none; background: none; border: none; cursor: pointer; padding: 8px; margin-right: 8px; z-index: 1001; flex-shrink: 0;">
                <span style="display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px;"></span>
                <span style="display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px;"></span>
                <span style="display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px;"></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="Logo" style="height: 32px; margin-right: 8px; border-radius: 50%; background: none; border: none; flex-shrink: 0;">
            <span class="header-title" id="headerTitle" style="font-size: 1rem; font-weight: bold; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; min-width: 0; max-width: 150px;">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; margin-left: 8px; flex-shrink: 0;" id="adminSection">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="adminUsername" style="font-size: 0.75rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px;">Welcome, Admin</span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php">📊 DASHBOARD</a>
            <a href="job.php">👥 JOB APPLICANTS</a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="#" class="active">➕ ADD ACCOUNT</a>
            <a href="analytics.php">📊 Analytics</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
        <div class="main-content">
            <div class="admin-form-container">
                <h2>Create New Admin Account</h2>
                <form id="createAdminForm" autocomplete="off">
                    <div class="form-group">
                        <label for="newUsername">Username</label>
                        <input id="newUsername" name="username" type="text" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">Password</label>
                        <input id="newPassword" name="password" type="text" required>
                    </div>
                    <button type="submit" class="login-btn">Create Admin</button>
                    <div id="adminCreateMsg" style="margin-top:1rem;font-weight:bold;"></div>
                </form>
            </div>
            <div class="admin-table-container">
                <h3>Admin Accounts</h3>
                <table class="admin-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Admin rows will be inserted here -->
                    </tbody>
                </table>
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
                    headerTitle.style.fontSize = '1rem';
                    headerTitle.style.whiteSpace = 'nowrap';
                    headerTitle.style.overflow = 'hidden';
                    headerTitle.style.textOverflow = 'ellipsis';
                    headerTitle.style.maxWidth = '150px';
                    
                    // Adjust admin section for mobile - make smaller
                    adminSection.style.marginRight = '8px';
                    adminSection.style.gap = '4px';
                    adminSection.style.fontSize = '0.75rem';
                    adminSection.style.maxWidth = '120px';
                    adminSection.style.overflow = 'hidden';
                    adminSection.style.textOverflow = 'ellipsis';
                    
                    // Update admin text to show only username
                    const adminUsername = document.getElementById('adminUsername');
                    if (adminUsername) {
                        const currentText = adminUsername.textContent;
                        if (currentText.includes('Welcome, ')) {
                            adminUsername.textContent = currentText.replace('Welcome, ', '');
                        }
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
                    
                    // Remove "Welcome, " text for desktop too
                    const adminUsername = document.getElementById('adminUsername');
                    if (adminUsername && adminUsername.textContent.includes('Welcome, ')) {
                        adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
                    }
                    
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
                        headerTitle.style.fontSize = '1rem';
                        headerTitle.style.maxWidth = '150px';
                        headerTitle.style.overflow = 'hidden';
                        headerTitle.style.textOverflow = 'ellipsis';
                        headerTitle.style.whiteSpace = 'nowrap';
                    }
                    
                    if (adminUsername) {
                        adminUsername.style.fontSize = '0.75rem';
                        adminUsername.style.maxWidth = '120px';
                        adminUsername.style.overflow = 'hidden';
                        adminUsername.style.textOverflow = 'ellipsis';
                        adminUsername.style.whiteSpace = 'nowrap';
                        // Remove "Welcome, " text for mobile
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
            
            // Remove "Welcome, " text for both mobile and desktop
            function removeWelcomeText() {
                const adminUsername = document.getElementById('adminUsername');
                if (adminUsername && adminUsername.textContent.includes('Welcome, ')) {
                    adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
                }
            }
            
            // Apply immediately
            applyMobileStyles();
            removeWelcomeText();
            
            // Force mobile styles immediately
            if (window.innerWidth <= 768) {
                const header = document.getElementById('mainHeader');
                const hamburgerMenu = document.getElementById('hamburgerMenu');
                const headerTitle = document.getElementById('headerTitle');
                const logo = document.querySelector('img');
                const leftSection = header.querySelector('div:first-child');
                const adminSection = document.getElementById('adminSection');
                
                // Force mobile header layout
                header.style.display = 'flex';
                header.style.flexDirection = 'row';
                header.style.justifyContent = 'space-between';
                header.style.alignItems = 'center';
                header.style.padding = '12px 20px';
                header.style.height = '64px';
                header.style.overflow = 'hidden';
                
                // Force left section layout
                if (leftSection) {
                    leftSection.style.display = 'flex';
                    leftSection.style.flexDirection = 'row';
                    leftSection.style.alignItems = 'center';
                    leftSection.style.flex = '1';
                    leftSection.style.minWidth = '0';
                }
                
                // Force hamburger menu to show
                hamburgerMenu.style.display = 'block';
                hamburgerMenu.style.visibility = 'visible';
                hamburgerMenu.style.marginRight = '8px';
                hamburgerMenu.style.flexShrink = '0';
                
                // Force logo styles
                if (logo) {
                    logo.style.height = '32px';
                    logo.style.marginRight = '8px';
                    logo.style.flexShrink = '0';
                }
                
                // Force title styles
                headerTitle.style.fontSize = '1rem';
                headerTitle.style.flex = '1';
                headerTitle.style.minWidth = '0';
                headerTitle.style.overflow = 'hidden';
                headerTitle.style.textOverflow = 'ellipsis';
                headerTitle.style.whiteSpace = 'nowrap';
                
                // Force admin section layout
                if (adminSection) {
                    adminSection.style.display = 'flex';
                    adminSection.style.flexDirection = 'row';
                    adminSection.style.alignItems = 'center';
                    adminSection.style.gap = '6px';
                    adminSection.style.marginLeft = '8px';
                    adminSection.style.flexShrink = '0';
                }
                
                // Force admin username styles
                const adminUsername = document.getElementById('adminUsername');
                if (adminUsername) {
                    adminUsername.style.fontSize = '0.75rem';
                    adminUsername.style.maxWidth = '100px';
                    adminUsername.style.overflow = 'hidden';
                    adminUsername.style.textOverflow = 'ellipsis';
                    adminUsername.style.whiteSpace = 'nowrap';
                }
            }
            
            // Initial check
            handleMobileHeader();
            
            // Check on resize
            window.addEventListener('resize', handleMobileHeader);

            // Session check and update UI
            fetch('session_check.php')
                .then(r => r.json())
                .then(d => {
                    // Update username display
                    document.getElementById('adminUsername').textContent = d.username; // Remove "Welcome, " prefix
                })
                .catch(() => {
                    console.error('Session check failed');
                });
            // Create admin
            document.getElementById('createAdminForm').addEventListener('submit', function(e) {
                e.preventDefault();
                var msg = document.getElementById('adminCreateMsg');
                msg.textContent = '';
                fetch('add_admin.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        username: document.getElementById('newUsername').value.trim(),
                        password: document.getElementById('newPassword').value.trim()
                    })
                })
                .then(r => r.json())
                .then(d => {
                    msg.textContent = d.message;
                    msg.style.color = d.success ? '#388e3c' : '#d32f2f';
                    if (d.success) {
                        document.getElementById('createAdminForm').reset();
                        loadAdmins();
                    }
                })
                .catch(() => {
                    msg.textContent = 'Server error.';
                    msg.style.color = '#d32f2f';
                });
            });
            // Load admin accounts
            function loadAdmins() {
                fetch('admin_accounts.php')
                    .then(r => r.json())
                    .then(admins => {
                        var tbody = document.querySelector('#adminTable tbody');
                        tbody.innerHTML = '';
                        admins.forEach(function(admin) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + admin.id + '</td>' +
                                '<td>' + (admin.username === 'Admin' ? '<b>' + admin.username + '</b>' : '<span class="admin-username">' + admin.username + '</span>') + '</td>' +
                                '<td>' +
                                (admin.username === 'Admin' ? '' :
                                    '<button class="admin-action-btn edit" data-id="' + admin.id + '" data-username="' + admin.username + '">Edit</button>' +
                                    '<button class="admin-action-btn delete" data-id="' + admin.id + '">Delete</button>'
                                ) +
                                '</td>';
                            tbody.appendChild(tr);
                        });
                    });
            }
            loadAdmins();
            // Delete admin
            document.getElementById('adminTable').addEventListener('click', function(e) {
                // Handle delete button (only if it has 'delete' class but NOT 'cancel')
                if (e.target.classList.contains('delete') && !e.target.classList.contains('cancel')) {
                    var id = e.target.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this admin?')) {
                        fetch('delete_admin.php', {
                            method: 'POST',
                            headers: {'Content-Type':'application/json'},
                            body: JSON.stringify({id: id})
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) loadAdmins();
                            else alert(d.message || 'Delete failed.');
                        });
                    }
                }
                // Handle edit button (only if it has 'edit' class but NOT 'save')
                else if (e.target.classList.contains('edit') && !e.target.classList.contains('save')) {
                    var tr = e.target.closest('tr');
                    var id = e.target.getAttribute('data-id');
                    var username = e.target.getAttribute('data-username');
                    tr.classList.add('edit-row');
                    tr.innerHTML =
                        '<td>' + id + '</td>' +
                        '<td><input type="text" value="' + username + '" class="edit-username"></td>' +
                        '<td>' +
                        '<input type="text" placeholder="New Password" class="edit-password"> ' +
                        '<button class="admin-action-btn edit save" data-id="' + id + '">Save</button>' +
                        '<button class="admin-action-btn delete cancel">Cancel</button>' +
                        '</td>';
                }
                // Handle save button
                else if (e.target.classList.contains('save')) {
                    var tr = e.target.closest('tr');
                    var id = e.target.getAttribute('data-id');
                    var username = tr.querySelector('.edit-username').value.trim();
                    var password = tr.querySelector('.edit-password').value.trim();
                    if (!username || !password) {
                        alert('Username and password required.');
                        return;
                    }
                    fetch('edit_admin.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({id: id, username: username, password: password})
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) loadAdmins();
                        else alert(d.message || 'Update failed.');
                    });
                }
                // Handle cancel button
                else if (e.target.classList.contains('cancel')) {
                    loadAdmins();
                }
            });
            document.querySelectorAll('.logout').forEach(function(btn) {
              btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('logoutModal').style.display = 'flex';
              });
            });

            // Logout modal functionality - wait for DOM to be ready
            document.addEventListener('DOMContentLoaded', function() {
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
            });
            </script>

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
        </div>
    </div>
</body>
</html>
