<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_check.php';
require_once 'db.php';

// Get company information with profile data
$company_id = $_SESSION['company_id'];
$company_name = $_SESSION['company_name'];
$email = $_SESSION['email'];

// Check which profile columns exist
$columns_check = $conn->query("SHOW COLUMNS FROM company_users");
$existing_columns = [];
if ($columns_check) {
    while ($row = $columns_check->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
}

// Build SELECT query based on available columns
$select_fields = ['id'];
$profile_fields = ['logo', 'description', 'website', 'address', 'phone'];
foreach ($profile_fields as $field) {
    if (in_array($field, $existing_columns)) {
        $select_fields[] = $field;
    }
}

$select_query = "SELECT " . implode(', ', $select_fields) . " FROM company_users WHERE id = ?";
$stmt = $conn->prepare($select_query);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company_profile = $result->fetch_assoc();
$stmt->close();

$company_logo = (in_array('logo', $existing_columns) && isset($company_profile['logo'])) ? $company_profile['logo'] : null;
$company_description = (in_array('description', $existing_columns) && isset($company_profile['description'])) ? $company_profile['description'] : null;
$company_website = (in_array('website', $existing_columns) && isset($company_profile['website'])) ? $company_profile['website'] : null;
$company_address = (in_array('address', $existing_columns) && isset($company_profile['address'])) ? $company_profile['address'] : null;
$company_phone = (in_array('phone', $existing_columns) && isset($company_profile['phone'])) ? $company_profile['phone'] : null;

// Get job postings count and analytics
$job_count = 0;
$active_jobs = 0;
$closed_jobs = 0;
$recent_jobs = [];
$total_applications = 0;
$applications_by_status = ['Applied' => 0, 'Accepted' => 0, 'Rejected' => 0];
$recent_applications = [];

// Additional statistics
$avg_compatibility_score = 0;
$acceptance_rate = 0;
$rejection_rate = 0;
$avg_applications_per_job = 0;
$jobs_with_no_applications = 0;
$most_popular_job = null;
$avg_response_time_days = 0;

// Check if job_postings table exists and get company's jobs
$table_check = $conn->query("SHOW TABLES LIKE 'job_postings'");
if ($table_check && $table_check->num_rows > 0) {
    // Check if job_postings has company_id column
    $columns = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
    if ($columns && $columns->num_rows > 0) {
        // Count total jobs
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_postings WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $job_count = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        // Count active and closed jobs
        $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM job_postings WHERE company_id = ? GROUP BY status");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] == 'Active') $active_jobs = $row['count'];
            if ($row['status'] == 'Closed') $closed_jobs = $row['count'];
        }
        $stmt->close();
        
        // Get recent jobs for this company only
        if ($job_count > 0) {
            $stmt = $conn->prepare("SELECT id, title, status, created_at FROM job_postings WHERE company_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $recent_jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        
        // Get applications analytics if job_applications_extended table exists
        $app_table_check = $conn->query("SHOW TABLES LIKE 'job_applications_extended'");
        if ($app_table_check && $app_table_check->num_rows > 0) {
            // Get total applications for company's jobs
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM job_applications_extended jae
                INNER JOIN job_postings jp ON jae.job_posting_id = jp.id
                WHERE jp.company_id = ?
            ");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $total_applications = $result->fetch_assoc()['count'] ?? 0;
            $stmt->close();
            
            // Get applications by status
            $stmt = $conn->prepare("
                SELECT jae.status, COUNT(*) as count 
                FROM job_applications_extended jae
                INNER JOIN job_postings jp ON jae.job_posting_id = jp.id
                WHERE jp.company_id = ?
                GROUP BY jae.status
            ");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $applications_by_status[$row['status']] = $row['count'];
            }
            $stmt->close();
            
            // Get average compatibility score
            $stmt = $conn->prepare("
                SELECT AVG(jae.compatibility_score) as avg_score
                FROM job_applications_extended jae
                INNER JOIN job_postings jp ON jae.job_posting_id = jp.id
                WHERE jp.company_id = ? AND jae.compatibility_score > 0
            ");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $avg_row = $result->fetch_assoc();
            $avg_compatibility_score = $avg_row['avg_score'] ?? 0;
            $stmt->close();
            
            // Calculate acceptance and rejection rates
            if ($total_applications > 0) {
                $acceptance_rate = ($applications_by_status['Accepted'] / $total_applications) * 100;
                $rejection_rate = ($applications_by_status['Rejected'] / $total_applications) * 100;
            }
            
            // Get average applications per job
            if ($job_count > 0) {
                $avg_applications_per_job = $total_applications / $job_count;
            }
            
            // Count jobs with no applications
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM job_postings jp
                LEFT JOIN job_applications_extended jae ON jp.id = jae.job_posting_id
                WHERE jp.company_id = ? AND jae.id IS NULL
            ");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $jobs_with_no_applications = $result->fetch_assoc()['count'] ?? 0;
            $stmt->close();
            
            // Get most popular job (by application count)
            $stmt = $conn->prepare("
                SELECT jp.id, jp.title, COUNT(jae.id) as app_count
                FROM job_postings jp
                LEFT JOIN job_applications_extended jae ON jp.id = jae.job_posting_id
                WHERE jp.company_id = ?
                GROUP BY jp.id, jp.title
                ORDER BY app_count DESC
                LIMIT 1
            ");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $most_popular_job = $result->fetch_assoc();
            $stmt->close();
            
            // Calculate average response time (days between applied_date and viewed_date for accepted/rejected)
            $stmt = $conn->prepare("
                SELECT AVG(DATEDIFF(jae.viewed_date, jae.applied_date)) as avg_days
                FROM job_applications_extended jae
                INNER JOIN job_postings jp ON jae.job_posting_id = jp.id
                WHERE jp.company_id = ? 
                    AND jae.status IN ('Accepted', 'Rejected')
                    AND jae.viewed_date IS NOT NULL
                    AND jae.applied_date IS NOT NULL
            ");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $avg_response = $result->fetch_assoc();
            $avg_response_time_days = $avg_response['avg_days'] ?? 0;
            $stmt->close();
            
            // Get recent applications grouped by job with counts and latest date
            $stmt = $conn->prepare("
                SELECT 
                    jp.id as job_posting_id,
                    jp.title as job_title,
                    COUNT(jae.id) as application_count,
                    MAX(jae.applied_date) as latest_applied_date,
                    MIN(jae.applied_date) as first_applied_date
                FROM job_postings jp
                INNER JOIN job_applications_extended jae ON jae.job_posting_id = jp.id
                WHERE jp.company_id = ? 
                    AND jae.applied_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY jp.id, jp.title
                ORDER BY latest_applied_date DESC, application_count DESC
                LIMIT 10
            ");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $recent_applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/Company-sidebar.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        
        .company-dashboard {
            padding: 20px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #1a3876 0%, #2c5aa0 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .welcome-card h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .welcome-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .stat-icon {
            font-size: 3rem;
            color: #1a3876;
            margin-bottom: 15px;
        }
        
        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1a3876;
            margin-bottom: 10px;
        }
        
        .stat-card .stat-label {
            color: #666;
            font-size: 1rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #1a3876;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .recent-jobs {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .job-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .job-item:last-child {
            border-bottom: none;
        }
        
        .job-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .job-meta {
            font-size: 0.9rem;
            color: #666;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #4caf50;
        }
        
        .status-draft {
            background: #fff3e0;
            color: #ff9800;
        }
        
        .status-closed {
            background: #ffebee;
            color: #f44336;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .profile-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .profile-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .profile-item h4 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .profile-item p {
            color: #333;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .welcome-card h1 {
                font-size: 1.5rem;
            }
            
            .welcome-card p {
                font-size: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .job-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        
        /* Header User Profile Styles */
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }
        
        .profile-icon {
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.3s;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }
        
        .profile-icon:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .profile-icon i {
            color: white;
        }
        
        .welcome-text {
            color: white;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Profile Dropdown Styles */
        .profile-dropdown {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 200px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            overflow: hidden;
        }
        
        .profile-dropdown-item {
            padding: 15px 20px;
            cursor: pointer;
            transition: background-color 0.2s;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .profile-dropdown-item i {
            font-size: 16px;
        }
        
        /* Header Fixed Position */
        .dashboard-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: auto;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: #f8f9fa;
            width: 250px;
            height: calc(100vh - 80px);
            position: fixed;
            left: 0;
            top: 80px;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin: 0;
        }
        
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
            color: white;
            border-left-color: #ffcb05;
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
        
        .sidebar {
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .sidebar-nav li:last-child {
            margin-top: auto;
            margin-bottom: 20px;
        }
        
        .dashboard-container {
            padding-top: 80px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: calc(100vh - 80px);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .company-dashboard {
                padding: 15px;
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
        @media (max-width: 768px) {
            .hamburger-menu {
                display: flex !important;
            }
            /* Mobile: slide-out sidebar */
            .sidebar.desktop-nav {
                position: fixed !important;
                top: 80px !important;
                left: -250px !important;
                bottom: 0 !important;
                right: auto !important;
                width: 250px !important;
                height: calc(100vh - 80px) !important;
                background: #f8f9fa !important;
                display: flex !important;
                flex-direction: column !important;
                padding: 20px 0 !important;
                box-shadow: 2px 0 10px rgba(0,0,0,0.15) !important;
                z-index: 998 !important;
                transition: left 0.3s ease !important;
            }
            .sidebar.desktop-nav.active {
                left: 0 !important;
            }
            /* Backdrop when sidebar is open */
            .dashboard-container:has(.sidebar.desktop-nav.active)::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.4);
                z-index: 997;
                pointer-events: auto;
            }
        }
        @media (min-width: 769px) {
            .hamburger-menu {
                display: none !important;
            }
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
                    <?php if ($company_logo): ?>
                        <img src="../<?php echo htmlspecialchars($company_logo); ?>" alt="Company Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <i class="fas fa-building"></i>
                    <?php endif; ?>
                </div>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($company_name); ?></span>
            </div>
        </div>
    </div>

    <!-- Profile Dropdown -->
    <div id="profileDropdown" class="profile-dropdown" style="display:none;">
        <div class="profile-dropdown-item logout" onclick="showLogoutModal()">
            <i class="fas fa-sign-out-alt"></i> Logout
        </div>
    </div>

    <div class="dashboard-container">
        <!-- Desktop Sidebar -->
        <div class="sidebar desktop-nav">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="jobposting.php"><i class="fas fa-briefcase"></i> Job Posting</a></li>
                <li><a href="view_applicants.php"><i class="fas fa-users"></i> View Applicants</a></li>
                <li><a href="referred.php"><i class="fas fa-user-check"></i> Referred</a></li>
                <li><a href="admin_requests.php"><i class="fas fa-envelope"></i> Admin Requests</a></li>
                <li><a href="profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
                <li><a href="#" class="logout" onclick="showLogoutModal(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="company-dashboard">
                <div class="welcome-card">
                    <h1>Welcome, <?php echo htmlspecialchars($company_name); ?>!</h1>
                    <p>Manage your job postings and connect with talented candidates</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="stat-value"><?php echo $job_count; ?></div>
                        <div class="stat-label">Total Job Postings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_applications; ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $active_jobs; ?></div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $closed_jobs; ?></div>
                        <div class="stat-label">Closed Jobs</div>
                    </div>
                </div>
                
                <?php if ($total_applications > 0): ?>
                <div class="recent-jobs" style="margin-bottom: 30px;">
                    <h2 class="section-title">Application Status Breakdown</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #1976d2;"><?php echo $applications_by_status['Applied']; ?></div>
                            <div style="color: #666; font-size: 0.9rem;">Applied</div>
                        </div>
                        <div style="padding: 15px; background: #e8f5e9; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #388e3c;"><?php echo $applications_by_status['Accepted']; ?></div>
                            <div style="color: #666; font-size: 0.9rem;">Accepted</div>
                        </div>
                        <div style="padding: 15px; background: #ffebee; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #c62828;"><?php echo $applications_by_status['Rejected']; ?></div>
                            <div style="color: #666; font-size: 0.9rem;">Rejected</div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Statistics Section -->
                <div class="recent-jobs" style="margin-bottom: 30px;">
                    <h2 class="section-title">Performance Metrics</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <?php if ($avg_compatibility_score > 0): ?>
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                            <div style="font-size: 2rem; color: #1a3876; margin-bottom: 8px;">
                                <i class="fas fa-star"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #1a3876; margin-bottom: 5px;">
                                <?php echo number_format($avg_compatibility_score, 1); ?>%
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Avg. Match Score</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($total_applications > 0): ?>
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                            <div style="font-size: 2rem; color: #388e3c; margin-bottom: 8px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #388e3c; margin-bottom: 5px;">
                                <?php echo number_format($acceptance_rate, 1); ?>%
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Acceptance Rate</div>
                        </div>
                        
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                            <div style="font-size: 2rem; color: #c62828; margin-bottom: 8px;">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #c62828; margin-bottom: 5px;">
                                <?php echo number_format($rejection_rate, 1); ?>%
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Rejection Rate</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($job_count > 0): ?>
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                            <div style="font-size: 2rem; color: #1976d2; margin-bottom: 8px;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #1976d2; margin-bottom: 5px;">
                                <?php echo number_format($avg_applications_per_job, 1); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Avg. Applications/Job</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($avg_response_time_days > 0): ?>
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                            <div style="font-size: 2rem; color: #f57c00; margin-bottom: 8px;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #f57c00; margin-bottom: 5px;">
                                <?php echo number_format($avg_response_time_days, 1); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Avg. Response Time (Days)</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Job Insights Section -->
                <div class="recent-jobs" style="margin-bottom: 30px;">
                    <h2 class="section-title">Job Insights</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <?php if ($most_popular_job && isset($most_popular_job['app_count']) && $most_popular_job['app_count'] > 0): ?>
                        <div style="padding: 20px; background: linear-gradient(135deg, #1a3876 0%, #2c5aa0 100%); border-radius: 12px; color: white;">
                            <div style="font-size: 1.2rem; margin-bottom: 10px; opacity: 0.9;">
                                <i class="fas fa-trophy"></i> Most Popular Job
                            </div>
                            <div style="font-size: 1.3rem; font-weight: bold; margin-bottom: 8px;">
                                <?php echo htmlspecialchars($most_popular_job['title']); ?>
                            </div>
                            <div style="font-size: 1.1rem; opacity: 0.9;">
                                <?php echo $most_popular_job['app_count']; ?> <?php echo $most_popular_job['app_count'] == 1 ? 'application' : 'applications'; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($jobs_with_no_applications > 0): ?>
                        <div style="padding: 20px; background: #fff3e0; border-radius: 12px; border-left: 4px solid #ff9800;">
                            <div style="font-size: 1.2rem; color: #f57c00; margin-bottom: 10px;">
                                <i class="fas fa-exclamation-triangle"></i> Attention Needed
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #f57c00; margin-bottom: 5px;">
                                <?php echo $jobs_with_no_applications; ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo $jobs_with_no_applications == 1 ? 'Job' : 'Jobs'; ?> with no applications
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (count($recent_applications) > 0): ?>
                <div class="recent-jobs" style="margin-bottom: 30px;">
                    <h2 class="section-title">Recent Applications (Last 30 Days)</h2>
                    <?php foreach ($recent_applications as $app): ?>
                        <div class="job-item" style="padding: 20px; border-bottom: 1px solid #e0e0e0;">
                            <div style="flex: 1;">
                                <div class="job-title" style="font-size: 1.1rem; margin-bottom: 10px;">
                                    <i class="fas fa-briefcase" style="color: #1a3876; margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($app['job_title']); ?>
                                </div>
                                <div class="job-meta" style="display: flex; flex-wrap: wrap; gap: 20px; font-size: 0.9rem;">
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-users" style="color: #1976d2;"></i>
                                        <strong style="color: #1976d2;"><?php echo $app['application_count']; ?></strong>
                                        <span style="color: #666;"><?php echo $app['application_count'] == 1 ? 'applicant' : 'applicants'; ?></span>
                                    </div>
                                    <?php if ($app['latest_applied_date']): ?>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-clock" style="color: #f57c00;"></i>
                                            <span style="color: #666;">Latest: <?php echo date('M d, Y g:i A', strtotime($app['latest_applied_date'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($app['first_applied_date'] && $app['first_applied_date'] != $app['latest_applied_date']): ?>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-calendar-alt" style="color: #666;"></i>
                                            <span style="color: #666;">First: <?php echo date('M d, Y', strtotime($app['first_applied_date'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                <span class="status-badge" style="background: #e3f2fd; color: #1976d2; padding: 8px 16px; border-radius: 20px; font-weight: 600;">
                                    <?php echo $app['application_count']; ?> <?php echo $app['application_count'] == 1 ? 'Application' : 'Applications'; ?>
                                </span>
                                <?php if ($app['latest_applied_date']): ?>
                                    <span style="font-size: 0.8rem; color: #999;">
                                        <?php 
                                            $latest_date = strtotime($app['latest_applied_date']);
                                            $now = time();
                                            $diff = $now - $latest_date;
                                            $days = floor($diff / (60 * 60 * 24));
                                            if ($days == 0) {
                                                echo 'Today';
                                            } elseif ($days == 1) {
                                                echo 'Yesterday';
                                            } else {
                                                echo $days . ' days ago';
                                            }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="recent-jobs" style="margin-bottom: 30px;">
                    <h2 class="section-title">Recent Applications</h2>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <p>No applications received in the last 30 days.</p>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <div class="recent-jobs">
                    <h2 class="section-title">Recent Job Postings</h2>
                    <?php if (count($recent_jobs) > 0): ?>
                        <?php foreach ($recent_jobs as $job): ?>
                            <div class="job-item">
                                <div>
                                    <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                    <div class="job-meta">
                                        Posted on <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?php echo strtolower($job['status'] ?? 'draft'); ?>">
                                    <?php echo htmlspecialchars($job['status'] ?? 'Draft'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <p>No job postings yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-section">
                    <h2 class="section-title">Company Profile</h2>
                    <div class="profile-item">
                        <h4>Company Name</h4>
                        <p><?php echo htmlspecialchars($company_name); ?></p>
                    </div>
                    <div class="profile-item">
                        <h4>Email Address</h4>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hamburger menu & slide-out sidebar (mobile)
        document.addEventListener('DOMContentLoaded', function() {
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
        });

        // Profile dropdown toggle
        function toggleProfileMenu() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!event.target.matches('.profile-icon') && !event.target.closest('.profile-icon')) {
                if (dropdown && dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                }
            }
        }

        // Logout modal
        function showLogoutModal() {
            // Close dropdown first
            document.getElementById('profileDropdown').style.display = 'none';
            
            // Show confirmation modal
            Swal.fire({
                title: 'Logout?',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1a3876',
                cancelButtonColor: '#666',
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
</body>
</html>
