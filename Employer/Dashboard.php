<?php include 'session_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WorkConnect Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fafafa;
            max-height: 100vh;
            overflow: hidden;
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
            min-height: 100vh;
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
        }
        .main-content {
            flex: 1;
            padding: 32px;
            background: #fff;
            margin-left: 240px;
            height: calc(100vh - 64px);
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
        
        /* Mobile App UI - Completely Different Layout */
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
            
            .layout {
                flex-direction: column;
                padding-top: 60px;
                margin-bottom: 80px;
            }
            
            /* Mobile Bottom Navigation */
            .sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #fff;
                display: flex;
                justify-content: space-around;
                padding: 8px 0;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                z-index: 1000;
                height: auto;
                width: 100%;
                flex-direction: row;
            }
            
            .sidebar a {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 8px 4px;
                text-decoration: none;
                color: #666;
                font-size: 0.7rem;
                transition: color 0.3s;
                min-width: 50px;
                border-radius: 0;
                background: none;
                border: none;
                margin: 0;
                white-space: nowrap;
            }
            
            .sidebar a:before {
                content: '';
                font-size: 1.2rem;
                margin-bottom: 4px;
            }
            
            .sidebar a[href="Dashboard.php"]:before { content: '📊'; }
            .sidebar a[href="job.php"]:before { content: '👥'; }
            .sidebar a[href="skill.php"]:before { content: '🛠️'; }
            .sidebar a[href="btec.php"]:before { content: '📈'; }
            .sidebar a[href="add.php"]:before { content: '➕'; }
            .sidebar a[href="analytics.php"]:before { content: '📊'; }
            .sidebar a[href="logout.php"]:before { content: '🚪'; }
            
            .sidebar a.active {
                color: #233a8b;
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
                margin-bottom: 80px;
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
                font-size: 0.65rem;
                padding: 6px 2px;
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
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center;">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo">
            <span class="header-title">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="adminUsername" style="font-size: 1rem; font-weight: 500;">Welcome, Admin</span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="#" class="active">📊 DASHBOARD</a>
            <a href="job.php">👥 JOB APPLICANTS</a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;">➕ ADD ACCOUNT</a>
            <a href="analytics.php">📊 Analytics</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <div>
                    <h2 style="color:#233a8b; font-size:1.8rem; font-weight:bold; margin:0;">PESO Dashboard</h2>
                    <p style="color:#666; margin:8px 0 0 0; font-size:1.1rem;">Public Employment Service Office Management System</p>
                </div>
                <div style="background: linear-gradient(135deg, #233a8b, #1976d2); color: white; padding: 16px 24px; border-radius: 12px; text-align: center;">
                    <div id="currentDate" style="font-size: 1.1rem; font-weight: 600;"></div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Philippines Time</div>
                </div>
            </div>

            <!-- Key Statistics Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-bottom:32px;">
                <div style="background:linear-gradient(135deg,#e3eaff,#f0f4ff);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(35,58,139,0.08);border-left:4px solid #233a8b;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div style="font-size:2.5rem;">👥</div>
                        <div style="background:#233a8b;color:white;padding:6px 12px;border-radius:20px;font-size:0.85rem;font-weight:600;">REGISTERED</div>
                    </div>
                    <div id="jobseekersCount" style="font-size:2.8rem;font-weight:bold;color:#233a8b;margin-bottom:8px;">...</div>
                    <div style="font-size:1.1rem;color:#555;font-weight:500;">Total Job Applicants</div>
                    <div style="font-size:0.9rem;color:#888;margin-top:4px;">Active Job Applicants in the system</div>
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
                                <div class="quick-action-title" style="font-weight:600;color:#233a8b;transition:color 0.3s ease;">Review Job Applicants</div>
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
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
                <div style="background:linear-gradient(135deg,#e3eaff,#f0f4ff);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(35,58,139,0.08);">
                    <h3 style="color:#233a8b;margin-top:0;margin-bottom:20px;font-size:1.3rem;display:flex;align-items:center;gap:8px;">
                        🏛️ PESO Mission & Services
                    </h3>
                    <div style="font-size:1.05rem;color:#333;line-height:1.6;">
                        <p style="margin-bottom:16px;">The Public Employment Service Office (PESO) serves as the primary employment facilitation unit in your municipality, connecting job seekers with employment opportunities and providing essential labor market services.</p>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px;">
                           
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

                <div style="background:linear-gradient(135deg,#fff3e0,#fff8f0);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(255,152,0,0.08);">
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
// Check admin session and update UI
fetch('session_check.php')
    .then(r => r.json())
    .then(data => {
        // Update username display
        document.getElementById('adminUsername').textContent = 'Welcome, ' + data.username;
        
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
</body>
</html>
