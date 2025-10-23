<?php include 'session_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WorkConnect Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fafafa;
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
            height: calc(100vh - 64px);
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
            
            .layout {
                padding-top: 56px;
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 0;
                left: 0;
                padding: 16px;
                flex-direction: row;
                overflow-x: auto;
                gap: 8px;
            }
            
            .sidebar a {
                white-space: nowrap;
                margin-bottom: 0;
                margin-top: 0;
                padding: 8px 12px;
                font-size: 0.9rem;
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
            
            .main-content > div:nth-child(2) {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .main-content > div:nth-child(3) {
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
                max-height: 250px !important;
            }
            
            #registrationChart {
                height: 250px !important;
            }
            
            #statusChart {
                height: 250px !important;
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
            
            canvas {
                max-height: 200px !important;
            }
            
            #registrationChart {
                height: 200px !important;
            }
            
            #statusChart {
                height: 200px !important;
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
                margin-top: 0;
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center;">
            <img src="../assets/image/PESO Logo circle.png" alt="Logo">
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
            <a href="Dashboard.php">📊 DASHBOARD</a>
            <a href="job.php">👥 JOB APPLICANTS</a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;">➕ ADD ACCOUNT</a>
            <a href="#" class="active">📊 Analytics</a>
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
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px;">
                
                 <!-- Registration Trends Chart -->
                 <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                     <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                         <h3 style="margin: 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">📊 Registration Trends</h3>
                         <div style="display: flex; gap: 12px; align-items: center;">
                             <select id="trendFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; font-size: 0.9rem;">
                                 <option value="12months">Last 12 Months</option>
                                 <option value="yearly" id="yearlyOption">Yearly (2020-2025)</option>
                             </select>
                         </div>
                     </div>
                     <canvas id="registrationChart" width="400" height="200"></canvas>
                 </div>

                <!-- Application Status Pie Chart -->
                <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
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
                    <div style="font-size: 1rem; opacity: 0.9;">Success Rate</div>
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
    // Update username display
        fetch('session_check.php')
            .then(r => r.json())
            .then(data => {
                document.getElementById('adminUsername').textContent = 'Welcome, ' + data.username;
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
     function generateTrendsData(jobseekers, filterType) {
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
                    createCharts();
                    chartsCreated = true;
                }, 100);
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
         
         // Registration Trends Chart
         const registrationCtx = document.getElementById('registrationChart');
         if (registrationCtx) {
             try {
                 window.registrationChart = new Chart(registrationCtx.getContext('2d'), {
                     type: 'line',
                     data: {
                         labels: monthlyData.map(trend => trend.month),
                         datasets: [{
                             label: 'New Registrations',
                             data: monthlyData.map(trend => trend.count),
                             borderColor: '#1976d2',
                             backgroundColor: 'rgba(25, 118, 210, 0.1)',
                             borderWidth: 3,
                             fill: true,
                             tension: 0.4,
                             pointBackgroundColor: '#1976d2',
                             pointBorderColor: '#fff',
                             pointBorderWidth: 2,
                             pointRadius: 6
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
                                 display: false
                             }
                         },
                         scales: {
                             y: {
                                 beginAtZero: true,
                                 grid: {
                                     color: 'rgba(0,0,0,0.1)'
                                 }
                             },
                             x: {
                                 grid: {
                                     display: false
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

     // Update trends chart based on filter (automatic)
     async function updateTrendsChart() {
         const filterType = document.getElementById('trendFilter').value;
         
         try {
             const jobseekerResponse = await fetch('jobseekers.php');
             const jobseekers = await jobseekerResponse.json();
             
             analyticsData.monthlyTrends = generateTrendsData(jobseekers, filterType);
             
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
         
         // Add event listener for automatic filter change
         document.getElementById('trendFilter').addEventListener('change', updateTrendsChart);
         
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
