<?php include 'session_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Hide hamburger menu on desktop */
        .hamburger-menu {
            display: none;
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
        
        /* Fix chart container expansion */
        canvas {
            max-height: 300px !important;
            max-width: 100% !important;
        }
        
        #registrationChart {
            height: 300px !important;
            width: 100% !important;
        }
        
        #statusChart {
            height: 300px !important;
            width: 100% !important;
        }
        /* Desktop and Laptop Responsive Design */
        @media (min-width: 1200px) {
            .main-content {
                padding: 40px;
            }
            
            canvas {
                max-height: 400px !important;
            }
            
            #registrationChart {
                height: 400px !important;
            }
            
            #statusChart {
                height: 400px !important;
            }
        }
        
        @media (min-width: 992px) and (max-width: 1199px) {
            .main-content {
                padding: 32px;
            }
            
            canvas {
                max-height: 350px !important;
            }
            
            #registrationChart {
                height: 400px !important;
            }
            
            #statusChart {
                height: 350px !important;
            }
        }
        
        @media (min-width: 769px) and (max-width: 991px) {
            .main-content {
                padding: 24px;
            }
            
            .charts-container {
                grid-template-columns: 1fr 1fr !important;
                gap: 20px !important;
            }
            
            canvas {
                max-height: 300px !important;
            }
            
            #registrationChart {
                height: 300px !important;
            }
            
            #statusChart {
                height: 300px !important;
            }
        }
        
        @media (min-width: 481px) and (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .charts-container {
                display: flex !important;
                flex-direction: column !important;
                gap: 20px !important;
            }
            
            .registration-chart-container,
            .status-chart-container {
                width: 100% !important;
            }
            
            canvas {
                max-height: 320px !important;
            }
            
            #registrationChart {
                height: 320px !important;
            }
            
            #statusChart {
                height: 320px !important;
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
                display: block;
                width: 90%;
                text-align: left;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
                height: auto;
            }
            
            .main-content > div:first-child {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            
            .main-content > div:first-child > div:last-child {
                align-self: stretch;
            }
            
            .main-content > div:first-child h2 {
                font-size: 1.5rem;
            }
            
            .main-content > div:first-child p {
                font-size: 1rem;
            }
            
            /* Mobile: Stack charts vertically - Registration Trends first, then Application Status */
            .charts-container {
                display: flex !important;
                flex-direction: column !important;
                gap: 24px !important;
            }
            
            /* Registration Trends Chart - Full width on mobile */
            .registration-chart-container {
                width: 100% !important;
                order: 1; /* First on mobile */
            }
            
            /* Application Status Chart - Full width on mobile */
            .status-chart-container {
                width: 100% !important;
                order: 2; /* Second on mobile */
            }
            
            /* Mobile filter adjustments */
            .registration-chart-container > div:first-child {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
            }
            
            .registration-chart-container > div:first-child > div:last-child {
                width: 100% !important;
                flex-wrap: wrap !important;
            }
            
            .registration-chart-container select {
                min-width: 120px !important;
                font-size: 0.8rem !important;
            }
            
            /* Mobile chart container adjustments */
            .registration-chart-container {
                padding: 20px !important;
            }
            
            .status-chart-container {
                padding: 20px !important;
            }
            
            .main-content > div:nth-child(2) {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .main-content > div:nth-child(4) {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .main-content > div:nth-child(5) {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            canvas {
                max-height: 350px !important;
            }
            
            #registrationChart {
                height: 400px !important;
            }
            
            #statusChart {
                height: 400px !important;
            }
            
            /* Increase table/chart container size for mobile */
            .registration-chart-container,
            .status-chart-container {
                min-height: 450px !important;
            }
            
            /* Mobile-specific chart padding adjustments */
            .registration-chart-container canvas {
                padding: 10px !important;
            }
            
            /* Mobile chart options for smaller dots */
            .registration-chart-container {
                position: relative;
            }
            
            .registration-chart-container::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                pointer-events: none;
                z-index: 1;
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
            
            .main-content > div:first-child h2 {
                font-size: 1.3rem;
            }
            
            .main-content > div:first-child p {
                font-size: 0.9rem;
            }
            
            .main-content > div:nth-child(2) > div {
                padding: 20px;
            }
            
            .main-content > div:nth-child(2) > div > div:first-child {
                font-size: 2rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(3) {
                font-size: 2.2rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(4) {
                font-size: 1rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(5) {
                font-size: 0.8rem;
            }
            
            /* Small mobile chart adjustments */
            .charts-container {
                gap: 16px !important;
            }
            
            .registration-chart-container,
            .status-chart-container {
                padding: 16px !important;
            }
            
            .registration-chart-container h3,
            .status-chart-container h3 {
                font-size: 1.1rem !important;
            }
            
            /* Small mobile chart padding for better data point visibility */
            .registration-chart-container canvas {
                padding: 15px !important;
            }
            
            canvas {
                max-height: 320px !important;
            }
            
            #registrationChart {
                height: 320px !important;
            }
            
            #statusChart {
                height: 320px !important;
            }
            
            /* Increase small mobile table/chart container size */
            .registration-chart-container,
            .status-chart-container {
                min-height: 360px !important;
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
            }
            .main-content {
                margin-left: 0;
                padding: 16px;
                height: auto;
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
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
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
            <a href="job_postings.php">💼 JOB POSTINGS</a>
            <a href="job.php">👥 JOBSEEKERS</a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;">➕ ADD ACCOUNT</a>
            <a href="#" class="active">📊 Analytics</a>
            <a href="announcement.php">📢 ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
        <div class="main-content">
            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #e3f2fd;">
                <div>
                    <h2 style="color:#233a8b; font-size:1.8rem; font-weight:700; margin:0;">📊 Analytics Dashboard</h2>
                    <p style="color:#666; margin:8px 0 0 0; font-size:1.1rem;">Comprehensive insights and performance metrics</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="background: linear-gradient(135deg, #e3f2fd, #f0f4ff); padding: 12px 20px; border-radius: 12px; border-left: 4px solid #1976d2;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #1976d2;" id="totalUsers">0</div>
                        <div style="font-size: 0.9rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Total Users</div>
                    </div>
                </div>
            </div>

            <!-- Analytics Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px;">
                
                <!-- Jobseeker Statistics -->
                <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #4caf50, #45a049); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">👥</div>
                        <div>
                            <h3 style="margin: 0; color: #233a8b; font-size: 1.2rem; font-weight: 700;">Jobseeker Analytics</h3>
                            <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">Registration trends and demographics</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div style="text-align: center; padding: 16px; background: rgba(76,175,80,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #4caf50;" id="totalJobseekers">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Total Registered</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(255,152,0,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #ff9800;" id="pendingApplications">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Pending</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(76,175,80,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #4caf50;" id="acceptedApplications">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Accepted</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(244,67,54,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #f44336;" id="rejectedApplications">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Rejected</div>
                        </div>
                    </div>
                </div>

                <!-- Skills Registry Analytics -->
                <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #1976d2, #1565c0); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">🛠️</div>
                        <div>
                            <h3 style="margin: 0; color: #233a8b; font-size: 1.2rem; font-weight: 700;">Skills Registry</h3>
                            <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">Skill distribution and trends</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div style="text-align: center; padding: 16px; background: rgba(25,118,210,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #1976d2;" id="totalSkills">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Total Skills</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(25,118,210,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #1976d2;" id="barangayCount">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Barangays</div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">📈</div>
                        <div>
                            <h3 style="margin: 0; color: #233a8b; font-size: 1.2rem; font-weight: 700;">Monthly Trends</h3>
                            <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">Registration patterns</p>
                        </div>
                    </div>
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 3rem; font-weight: 700; color: #ff9800;" id="thisMonthRegistrations">0</div>
                        <div style="font-size: 0.9rem; color: #666; margin-top: 8px;">New registrations this month</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px;">
                
                 <!-- Registration Trends Chart -->
                 <div class="registration-chart-container" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                     <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                         <h3 style="margin: 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">📊 Registration Trends</h3>
                         <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                             <select id="trendFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; font-size: 0.9rem;">
                                 <option value="12months">Last 12 Months</option>
                                 <option value="yearly" id="yearlyOption">Yearly (2020-2025)</option>
                                 <option value="monthly">Specific Month</option>
                             </select>
                             <select id="monthFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; font-size: 0.9rem; display: none;">
                                 <option value="">Select Month</option>
                                 <option value="01">January</option>
                                 <option value="02">February</option>
                                 <option value="03">March</option>
                                 <option value="04">April</option>
                                 <option value="05">May</option>
                                 <option value="06">June</option>
                                 <option value="07">July</option>
                                 <option value="08">August</option>
                                 <option value="09">September</option>
                                 <option value="10">October</option>
                                 <option value="11">November</option>
                                 <option value="12">December</option>
                             </select>
                             <select id="yearFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; font-size: 0.9rem; display: none;">
                                 <option value="">Select Year</option>
                                 <!-- Years will be populated by JavaScript -->
                             </select>
                         </div>
                     </div>
                     <canvas id="registrationChart" width="400" height="200"></canvas>
                 </div>

                <!-- Application Status Pie Chart -->
                <div class="status-chart-container" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                    <h3 style="margin: 0 0 20px 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">🎯 Application Status</h3>
                    <canvas id="statusChart" width="300" height="300"></canvas>
                </div>
            </div>

            <!-- Skills Distribution -->
            <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1); margin-bottom: 32px;">
                <h3 style="margin: 0 0 20px 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">🛠️ Job Applicants' Most Common Skills</h3>
                <div id="skillsList" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <!-- Skills will be populated by JavaScript -->
                </div>
            </div>

            <!-- Performance Metrics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                
                <!-- Success Rate -->
                <div style="background: linear-gradient(135deg, #4caf50, #45a049); color: white; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 8px;">🎯</div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;" id="successRate">0%</div>
                    <div style="font-size: 1rem; opacity: 0.9;">Success Referral Rate</div>
                </div>

                <!-- Average Processing Time -->
                <div style="background: linear-gradient(135deg, #1976d2, #1565c0); color: white; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 8px;">⏱️</div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;" id="avgProcessingTime">0</div>
                    <div style="font-size: 1rem; opacity: 0.9;">Avg. Processing Days</div>
                </div>

                <!-- System Health -->
                <div style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 8px;">💚</div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;">99.9%</div>
                    <div style="font-size: 1rem; opacity: 0.9;">System Uptime</div>
                </div>
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
                adminSection.style.maxWidth = '100px';
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
                    adminUsername.style.maxWidth = '100px';
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
                    adminSection.style.maxWidth = '100px';
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
            header.style.padding = '12px 16px';
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
            headerTitle.style.fontSize = '1.2rem';
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
        
        // Apply immediately
        applyMobileStyles();
        removeWelcomeText();
        
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

    // Update username display
        fetch('session_check.php')
            .then(r => r.json())
            .then(data => {
                document.getElementById('adminUsername').textContent = data.username; // Removed 'Welcome, ' prefix
                if (data.isMainAdmin) {
                    document.getElementById('addAccountLink').style.display = 'block';
                } else {
                    document.getElementById('addAccountLink').style.display = 'none';
                }
            })
            .catch(() => {
                console.error('Session check failed');
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

    // Analytics Data and Charts
    let analyticsData = {
        totalJobseekers: 0,
        pendingApplications: 0,
        acceptedApplications: 0,
        rejectedApplications: 0,
        totalSkills: 0,
        barangayCount: 13,
        thisMonthRegistrations: 0,
        monthlyTrends: [],
        skillsDistribution: []
    };
    
     let chartsCreated = false;

     // Generate trends data based on filter
     function generateTrendsData(jobseekers, filterType, selectedMonth = null, selectedYear = null) {
         const trendsData = [];
         
         if (filterType === '12months') {
             // Last 12 months
             for (let i = 11; i >= 0; i--) {
                 const date = new Date();
                 date.setMonth(date.getMonth() - i);
                 const monthName = date.toLocaleDateString('en-US', { month: 'short' });
                 
                 const count = jobseekers.filter(j => {
                     if (j.submission_month && j.submission_year) {
                         return parseInt(j.submission_month) === (date.getMonth() + 1) && parseInt(j.submission_year) === date.getFullYear();
                     }
                     return false;
                 }).length;
                 
                 trendsData.push({ month: monthName, count: count });
             }
         } else if (filterType === 'yearly') {
             // Yearly data from 2020 to current year (automatically extends)
             const currentYear = new Date().getFullYear();
             for (let year = 2020; year <= currentYear; year++) {
                 const count = jobseekers.filter(j => {
                     if (j.submission_year) {
                         return parseInt(j.submission_year) === year;
                     }
                     return false;
                 }).length;
                 
                 trendsData.push({ month: year.toString(), count: count });
             }
         } else if (filterType === 'monthly' && selectedMonth && selectedYear) {
             // Specific month data - show daily breakdown within the selected month
             console.log('Filtering for month:', selectedMonth, 'year:', selectedYear);
             
             const selectedMonthInt = parseInt(selectedMonth);
             const selectedYearInt = parseInt(selectedYear);
             
             // Get the number of days in the selected month
             const daysInMonth = new Date(selectedYearInt, selectedMonthInt, 0).getDate();
             console.log('Days in month:', daysInMonth);
             
             // Create daily data for the entire month
             for (let day = 1; day <= daysInMonth; day++) {
                 const count = jobseekers.filter(j => {
                     if (j.submission_day && j.submission_month && j.submission_year) {
                         const jobseekerDay = parseInt(j.submission_day);
                         const jobseekerMonth = parseInt(j.submission_month);
                         const jobseekerYear = parseInt(j.submission_year);
                         
                         return jobseekerDay === day && 
                                jobseekerMonth === selectedMonthInt && 
                                jobseekerYear === selectedYearInt;
                     }
                     return false;
                 }).length;
                 
                 trendsData.push({ month: `Day ${day}`, count: count });
                 console.log(`Day ${day}: ${count} registrations`);
             }
             
             console.log('Found daily trends data:', trendsData);
             
             // If no daily data found, check if we have monthly data and distribute it
             const totalMonthlyCount = trendsData.reduce((sum, day) => sum + day.count, 0);
             console.log('Total monthly count from daily data:', totalMonthlyCount);
             
             if (totalMonthlyCount === 0) {
                 // Try to get monthly count from the original data
                 const monthlyCount = jobseekers.filter(j => {
                     if (j.submission_month && j.submission_year) {
                         const jobseekerMonth = parseInt(j.submission_month);
                         const jobseekerYear = parseInt(j.submission_year);
                         
                         return jobseekerMonth === selectedMonthInt && 
                                jobseekerYear === selectedYearInt;
                     }
                     return false;
                 }).length;
                 
                 console.log('Monthly count from monthly data:', monthlyCount);
                 
                 if (monthlyCount > 0) {
                     // Distribute the monthly count across days (put it all on day 15 for visualization)
                     const newTrendsData = [];
                     for (let day = 1; day <= daysInMonth; day++) {
                         const count = day === 15 ? monthlyCount : 0; // Put all registrations on day 15
                         newTrendsData.push({ month: `Day ${day}`, count: count });
                     }
                     console.log('Redistributed monthly data:', newTrendsData);
                     return newTrendsData;
                 }
             }
         }
         
         return trendsData;
     }

     // Fetch analytics data
    async function fetchAnalyticsData() {
        try {
            // Fetch jobseeker statistics from real data
            const jobseekerResponse = await fetch('jobseekers.php');
            const jobseekers = await jobseekerResponse.json();
            
            console.log('Fetched jobseekers:', jobseekers.length);
            console.log('Sample jobseeker data:', jobseekers.slice(0, 3)); // Show first 3 records
            
            // Real jobseeker data
            analyticsData.totalJobseekers = jobseekers.length;
            analyticsData.pendingApplications = jobseekers.filter(j => !j.application_status || j.application_status === 'Pending' || j.application_status === '').length;
            analyticsData.acceptedApplications = jobseekers.filter(j => j.application_status === 'Accepted').length;
            analyticsData.rejectedApplications = jobseekers.filter(j => j.application_status === 'Rejected').length;
            
            console.log('Status counts:', {
                pending: analyticsData.pendingApplications,
                accepted: analyticsData.acceptedApplications,
                rejected: analyticsData.rejectedApplications
            });
            
            // Calculate this month's registrations from real data
            const currentDate = new Date();
            const currentMonth = currentDate.getMonth();
            const currentYear = currentDate.getFullYear();
            
            analyticsData.thisMonthRegistrations = jobseekers.filter(j => {
                if (j.submission_month && j.submission_year) {
                    return parseInt(j.submission_month) === (currentMonth + 1) && parseInt(j.submission_year) === currentYear;
                }
                return false;
            }).length;
            
             // Generate trends data based on current filter
             analyticsData.monthlyTrends = generateTrendsData(jobseekers, '12months');
            
            console.log('Monthly trends data:', analyticsData.monthlyTrends);
            
            // Fetch real skills data from skill registry
            await fetchSkillsData();
            
            console.log('Analytics data:', analyticsData);
            
            // Update UI
            updateAnalyticsUI();
            
             // Wait a bit for DOM to be ready, then create charts
             if (!chartsCreated) {
                 setTimeout(() => {
                     console.log('Creating charts after data fetch...');
                     createCharts();
                     chartsCreated = true;
                 }, 100);
             } else {
                 console.log('Charts already created, updating registration chart...');
                 createRegistrationChart();
             }
            
        } catch (error) {
            console.error('Error fetching analytics data:', error);
            // If fetch fails, show zeros instead of sample data
            analyticsData.totalJobseekers = 0;
            analyticsData.pendingApplications = 0;
            analyticsData.acceptedApplications = 0;
            analyticsData.rejectedApplications = 0;
            analyticsData.thisMonthRegistrations = 0;
            analyticsData.monthlyTrends = [
                { month: 'Jul', count: 0 },
                { month: 'Aug', count: 0 },
                { month: 'Sep', count: 0 },
                { month: 'Oct', count: 0 },
                { month: 'Nov', count: 0 },
                { month: 'Dec', count: 0 }
            ];
            analyticsData.skillsDistribution = [];
            analyticsData.totalSkills = 0;
            
            updateAnalyticsUI();
            if (!chartsCreated) {
                setTimeout(() => {
                    createCharts();
                    chartsCreated = true;
                }, 100);
            }
        }
    }

    // Fetch real skills data from skill registry
    async function fetchSkillsData() {
        try {
            // Fetch skills data from skill registry
            const skillsResponse = await fetch('skill.php');
            const skillsText = await skillsResponse.text();
            
            // Parse skills data from the skill registry
            // This is a simplified approach - you might need to create a dedicated API endpoint
            const skillsData = [];
            
            // Count skills from jobseeker data
            const skillCounts = {};
            const jobseekerResponse = await fetch('jobseekers.php');
            const jobseekers = await jobseekerResponse.json();
            
            jobseekers.forEach(jobseeker => {
                // Count individual skills
                if (jobseeker.skill_auto_mechanic == 1) skillCounts['Auto Mechanic'] = (skillCounts['Auto Mechanic'] || 0) + 1;
                if (jobseeker.skill_electrician == 1) skillCounts['Electrician'] = (skillCounts['Electrician'] || 0) + 1;
                if (jobseeker.skill_photography == 1) skillCounts['Photography'] = (skillCounts['Photography'] || 0) + 1;
                if (jobseeker.skill_beautician == 1) skillCounts['Beautician'] = (skillCounts['Beautician'] || 0) + 1;
                if (jobseeker.skill_embroidery == 1) skillCounts['Embroidery'] = (skillCounts['Embroidery'] || 0) + 1;
                if (jobseeker.skill_plumbing == 1) skillCounts['Plumbing'] = (skillCounts['Plumbing'] || 0) + 1;
                if (jobseeker.skill_carpentry == 1) skillCounts['Carpentry'] = (skillCounts['Carpentry'] || 0) + 1;
                if (jobseeker.skill_gardening == 1) skillCounts['Gardening'] = (skillCounts['Gardening'] || 0) + 1;
                if (jobseeker.skill_sewing == 1) skillCounts['Sewing'] = (skillCounts['Sewing'] || 0) + 1;
                if (jobseeker.skill_computer == 1) skillCounts['Computer Literacy'] = (skillCounts['Computer Literacy'] || 0) + 1;
                if (jobseeker.skill_masonry == 1) skillCounts['Masonry'] = (skillCounts['Masonry'] || 0) + 1;
                if (jobseeker.skill_stenography == 1) skillCounts['Stenography'] = (skillCounts['Stenography'] || 0) + 1;
                if (jobseeker.skill_domestic == 1) skillCounts['Domestic Chores'] = (skillCounts['Domestic Chores'] || 0) + 1;
                if (jobseeker.skill_painter == 1) skillCounts['Painter/Artist'] = (skillCounts['Painter/Artist'] || 0) + 1;
                if (jobseeker.skill_tailoring == 1) skillCounts['Tailoring'] = (skillCounts['Tailoring'] || 0) + 1;
                if (jobseeker.skill_driver == 1) skillCounts['Driving'] = (skillCounts['Driving'] || 0) + 1;
                if (jobseeker.skill_painting == 1) skillCounts['Painting Job'] = (skillCounts['Painting Job'] || 0) + 1;
            });
            
            // Convert to array and sort by count
            analyticsData.skillsDistribution = Object.entries(skillCounts)
                .map(([skill, count]) => ({ skill, count }))
                .sort((a, b) => b.count - a.count)
                .slice(0, 6); // Top 6 skills
            
            analyticsData.totalSkills = Object.values(skillCounts).reduce((sum, count) => sum + count, 0);
            
        } catch (error) {
            console.error('Error fetching skills data:', error);
            analyticsData.skillsDistribution = [];
            analyticsData.totalSkills = 0;
        }
    }

    // Update analytics UI
    function updateAnalyticsUI() {
        document.getElementById('totalUsers').textContent = analyticsData.totalJobseekers;
        document.getElementById('totalJobseekers').textContent = analyticsData.totalJobseekers;
        document.getElementById('pendingApplications').textContent = analyticsData.pendingApplications;
        document.getElementById('acceptedApplications').textContent = analyticsData.acceptedApplications;
        document.getElementById('rejectedApplications').textContent = analyticsData.rejectedApplications;
        document.getElementById('totalSkills').textContent = analyticsData.totalSkills;
        document.getElementById('barangayCount').textContent = analyticsData.barangayCount;
        document.getElementById('thisMonthRegistrations').textContent = analyticsData.thisMonthRegistrations;
        
        // Calculate success rate
        const totalProcessed = analyticsData.acceptedApplications + analyticsData.rejectedApplications;
        const successRate = totalProcessed > 0 ? Math.round((analyticsData.acceptedApplications / totalProcessed) * 100) : 0;
        document.getElementById('successRate').textContent = successRate + '%';
        
        // Mock average processing time
        document.getElementById('avgProcessingTime').textContent = Math.floor(Math.random() * 5) + 2;
        
        // Update skills list
        updateSkillsList();
    }

    // Update skills distribution list
    function updateSkillsList() {
        const skillsList = document.getElementById('skillsList');
        skillsList.innerHTML = '';
        
        if (analyticsData.skillsDistribution.length === 0) {
            skillsList.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: linear-gradient(135deg, #f5f5f5, #fafafa); border-radius: 12px; border: 2px dashed #bdbdbd;">
                    <div style="font-size: 3rem; color: #999; margin-bottom: 16px;">🛠️</div>
                    <div style="font-weight: 600; color: #666; margin-bottom: 8px; font-size: 1.1rem;">No Skills Data Available</div>
                    <div style="color: #999; font-size: 0.9rem;">Skills will appear here once jobseekers register with their skills</div>
                </div>
            `;
            return;
        }
        
        analyticsData.skillsDistribution.forEach(skill => {
            const percentage = analyticsData.totalSkills > 0 ? Math.round((skill.count / analyticsData.totalSkills) * 100) : 0;
            const skillElement = document.createElement('div');
            skillElement.style.cssText = `
                background: linear-gradient(135deg, #e3f2fd, #f0f4ff);
                border-radius: 12px;
                padding: 16px;
                text-align: center;
                border: 1px solid #bbdefb;
                transition: transform 0.2s ease;
            `;
            skillElement.innerHTML = `
                <div style="font-size: 1.5rem; margin-bottom: 8px;">🛠️</div>
                <div style="font-weight: 600; color: #1976d2; margin-bottom: 4px;">${skill.skill}</div>
                <div style="font-size: 2rem; font-weight: 700; color: #1976d2; margin-bottom: 4px;">${skill.count}</div>
                <div style="font-size: 0.8rem; color: #666;">${percentage}% of total</div>
            `;
            skillElement.addEventListener('mouseenter', () => {
                skillElement.style.transform = 'translateY(-4px)';
            });
            skillElement.addEventListener('mouseleave', () => {
                skillElement.style.transform = 'translateY(0)';
            });
            skillsList.appendChild(skillElement);
        });
    }

     // Create registration chart only
     function createRegistrationChart() {
         console.log('Creating registration chart with data:', analyticsData.monthlyTrends);
         
         // Ensure we have data for chart
         const monthlyData = analyticsData.monthlyTrends.length > 0 ? analyticsData.monthlyTrends : [
             { month: 'Jul', count: 0 },
             { month: 'Aug', count: 0 },
             { month: 'Sep', count: 0 },
             { month: 'Oct', count: 0 },
             { month: 'Nov', count: 0 },
             { month: 'Dec', count: 0 }
         ];
         
         console.log('Monthly data for chart:', monthlyData);
         console.log('Chart labels:', monthlyData.map(trend => trend.month));
         console.log('Chart data:', monthlyData.map(trend => trend.count));
         console.log('Max value in data:', Math.max(...monthlyData.map(trend => trend.count)));
         console.log('Min value in data:', Math.min(...monthlyData.map(trend => trend.count)));
         
         // Registration Trends Chart
         const registrationCtx = document.getElementById('registrationChart');
         if (registrationCtx) {
             console.log('Registration chart canvas found');
             try {
                 // Destroy existing chart if it exists
                 if (window.registrationChart && typeof window.registrationChart.destroy === 'function') {
                     console.log('Destroying existing registration chart');
                     window.registrationChart.destroy();
                 }
                 
                 console.log('Creating new registration chart...');
                 
                 // Determine chart type based on data points and filter type
                 let chartType = 'line';
                 
                 // Only use bar chart for single data points
                 if (monthlyData.length === 1) {
                     chartType = 'bar';
                 }
                 // For all other cases (including daily data), use line chart
                 else {
                     chartType = 'line';
                 }
                 
                 // Check if mobile device for smaller dots
                 const isMobile = window.innerWidth <= 768;
                 const pointRadius = isMobile ? 4 : 8;
                 const pointHoverRadius = isMobile ? 6 : 10;
                 
                 console.log('Using chart type:', chartType, 'for', monthlyData.length, 'data points');
                 console.log('Mobile device:', isMobile, 'Point radius:', pointRadius);
                 
                 window.registrationChart = new Chart(registrationCtx.getContext('2d'), {
                     type: chartType,
                     data: {
                         labels: monthlyData.map(trend => trend.month),
                         datasets: [{
                             label: 'New Registrations',
                             data: monthlyData.map(trend => trend.count),
                             borderColor: '#1976d2',
                             backgroundColor: chartType === 'bar' ? '#1976d2' : 'rgba(25, 118, 210, 0.1)',
                             borderWidth: chartType === 'bar' ? 0 : 3,
                             fill: chartType === 'line',
                             tension: chartType === 'line' ? 0.4 : 0,
                             pointBackgroundColor: '#1976d2',
                             pointBorderColor: '#fff',
                             pointBorderWidth: isMobile ? 2 : 3,
                             pointRadius: chartType === 'line' ? pointRadius : 0,
                             pointHoverRadius: chartType === 'line' ? pointHoverRadius : 0,
                             pointHoverBackgroundColor: '#1565c0',
                             pointHoverBorderColor: '#fff',
                             pointHoverBorderWidth: isMobile ? 2 : 3
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         resizeDelay: 0,
                         layout: {
                             padding: {
                                 top: 20,
                                 bottom: 20,
                                 left: 10,
                                 right: 10
                             }
                         },
                         animation: {
                             duration: 0
                         },
                         plugins: {
                             legend: {
                                 display: false
                             },
                             tooltip: {
                                 enabled: true,
                                 callbacks: {
                                     title: function(context) {
                                         return context[0].label;
                                     },
                                     label: function(context) {
                                         return `${context.parsed.y} registrations`;
                                     }
                                 }
                             }
                         },
                         scales: {
                             y: {
                                 beginAtZero: true,
                                 min: -0.5,
                                 max: Math.max(5, Math.max(...monthlyData.map(trend => trend.count)) + 1),
                                 grid: {
                                     color: 'rgba(0,0,0,0.1)',
                                     drawBorder: false
                                 },
                                 ticks: {
                                     callback: function(value) {
                                         return Number.isInteger(value) && value >= 0 ? value : null;
                                     },
                                     padding: 15,
                                     stepSize: 1
                                 }
                             },
                             x: {
                                 grid: {
                                     display: false
                                 },
                                 ticks: {
                                     maxRotation: monthlyData.length > 15 ? 45 : 0,
                                     font: {
                                         size: monthlyData.length > 15 ? 9 : 11
                                     },
                                     padding: 15
                                 }
                             }
                         }
                     }
                 });
                 console.log('Registration chart created successfully');
             } catch (error) {
                 console.error('Error creating registration chart:', error);
             }
         } else {
             console.error('Registration chart canvas not found');
             // Try to find the canvas element
             const canvas = document.querySelector('#registrationChart');
             if (canvas) {
                 console.log('Canvas element found:', canvas);
             } else {
                 console.error('Canvas element with id "registrationChart" not found in DOM');
             }
         }
     }

     // Create charts
     function createCharts() {
         if (chartsCreated) {
             console.log('Charts already created, skipping...');
             return;
         }
         
         console.log('Creating charts with data:', analyticsData);
         
         // Destroy existing charts if they exist
         if (window.registrationChart && typeof window.registrationChart.destroy === 'function') {
             window.registrationChart.destroy();
         }
         if (window.statusChart && typeof window.statusChart.destroy === 'function') {
             window.statusChart.destroy();
         }
         
         // Create registration chart
         createRegistrationChart();
         
         // Create status chart
         const statusData = [
             analyticsData.acceptedApplications || 0,
             analyticsData.pendingApplications || 0,
             analyticsData.rejectedApplications || 0
         ];
         
         console.log('Status data for chart:', statusData);
         
         // Application Status Pie Chart
         const statusCtx = document.getElementById('statusChart');
         if (statusCtx) {
             try {
                 window.statusChart = new Chart(statusCtx.getContext('2d'), {
                     type: 'doughnut',
                     data: {
                         labels: ['Accepted', 'Pending', 'Rejected'],
                         datasets: [{
                             data: statusData,
                             backgroundColor: [
                                 '#4caf50',
                                 '#ff9800',
                                 '#f44336'
                             ],
                             borderWidth: 0
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         resizeDelay: 0,
                         animation: {
                             duration: 0
                         },
                         plugins: {
                             legend: {
                                 position: 'bottom',
                                 labels: {
                                     padding: 20,
                                     usePointStyle: true
                                 }
                             }
                         }
                     }
                 });
                 console.log('Status chart created successfully');
             } catch (error) {
                 console.error('Error creating status chart:', error);
             }
         } else {
             console.error('Status chart canvas not found');
         }
     }

     // Handle filter change and show/hide dropdowns
     function handleFilterChange() {
         const filterType = document.getElementById('trendFilter').value;
         const monthFilter = document.getElementById('monthFilter');
         const yearFilter = document.getElementById('yearFilter');
         
         if (filterType === 'monthly') {
             monthFilter.style.display = 'block';
             yearFilter.style.display = 'block';
         } else {
             monthFilter.style.display = 'none';
             yearFilter.style.display = 'none';
         }
         
         // Update chart if monthly filter is selected and both month and year are chosen
         if (filterType === 'monthly' && monthFilter.value && yearFilter.value) {
             updateTrendsChart();
         } else if (filterType !== 'monthly') {
             updateTrendsChart();
         }
     }
     
     // Populate year dropdown
     function populateYearDropdown() {
         const yearFilter = document.getElementById('yearFilter');
         const currentYear = new Date().getFullYear();
         
         // Clear existing options
         yearFilter.innerHTML = '<option value="">Select Year</option>';
         
         // Add years from current year down to 2020
         for (let year = currentYear; year >= 2020; year--) {
             const option = document.createElement('option');
             option.value = year;
             option.textContent = year;
             yearFilter.appendChild(option);
         }
     }
     
     // Update trends chart based on filter (automatic)
     async function updateTrendsChart() {
         const filterType = document.getElementById('trendFilter').value;
         const selectedMonth = document.getElementById('monthFilter').value;
         const selectedYear = document.getElementById('yearFilter').value;
         
         try {
             const jobseekerResponse = await fetch('jobseekers.php');
             const jobseekers = await jobseekerResponse.json();
             
             analyticsData.monthlyTrends = generateTrendsData(jobseekers, filterType, selectedMonth, selectedYear);
             
             console.log('Updated trends data:', analyticsData.monthlyTrends);
             
             // Update only the registration chart
             if (window.registrationChart) {
                 window.registrationChart.destroy();
             }
             
             // Create only the registration chart
             createRegistrationChart();
             
         } catch (error) {
             console.error('Error updating trends chart:', error);
         }
     }

     // Initialize analytics when page loads
     document.addEventListener('DOMContentLoaded', function() {
         if (isInitialized) return;
         isInitialized = true;
         
         console.log('DOM loaded, starting analytics...');
         console.log('Chart.js available:', typeof Chart !== 'undefined');
         
         // Update yearly option text to show current year
         const currentYear = new Date().getFullYear();
         const yearlyOption = document.getElementById('yearlyOption');
         if (yearlyOption) {
             yearlyOption.textContent = `Yearly (2020-${currentYear})`;
         }
         
         // Add event listeners for filter changes
         document.getElementById('trendFilter').addEventListener('change', handleFilterChange);
         document.getElementById('monthFilter').addEventListener('change', updateTrendsChart);
         document.getElementById('yearFilter').addEventListener('change', updateTrendsChart);
         
         // Populate year dropdown
         populateYearDropdown();
         
         // Check if Chart.js is loaded
         if (typeof Chart === 'undefined') {
             console.error('Chart.js not loaded!');
             // Try to load Chart.js dynamically
             const script = document.createElement('script');
             script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
             script.onload = function() {
                 console.log('Chart.js loaded dynamically');
                 fetchAnalyticsData();
             };
             script.onerror = function() {
                 console.error('Failed to load Chart.js');
             };
             document.head.appendChild(script);
         } else {
             console.log('Chart.js is available, fetching analytics data...');
             fetchAnalyticsData();
         }
     });

    // Also try to load when window is fully loaded
    window.addEventListener('load', function() {
        if (isInitialized) return;
        console.log('Window loaded, checking analytics...');
        if (analyticsData.totalJobseekers === 0 && !chartsCreated) {
            console.log('Retrying analytics fetch...');
            fetchAnalyticsData();
        }
    });

    // Prevent multiple initializations
    let isInitialized = false;
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
</body>
</html>
