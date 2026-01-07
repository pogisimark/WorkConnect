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
$applications_by_status = ['Applied' => 0, 'Viewed' => 0, 'Interview' => 0, 'Accepted' => 0, 'Rejected' => 0];
$recent_applications = [];

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
            
            // Get recent applications (last 7 days)
            $stmt = $conn->prepare("
                SELECT jae.id, jae.status, jae.applied_date, jp.title as job_title
                FROM job_applications_extended jae
                INNER JOIN job_postings jp ON jae.job_posting_id = jp.id
                WHERE jp.company_id = ? AND jae.applied_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY jae.applied_date DESC
                LIMIT 5
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
        
        .dashboard-container {
            padding-top: 80px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: calc(100vh - 80px);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .company-dashboard {
                padding: 15px;
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
                    <?php if ($company_logo): ?>
                        <img src="<?php echo htmlspecialchars($company_logo); ?>" alt="Company Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
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
                <li><a href="profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
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
                        <div style="padding: 15px; background: #fff3e0; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #f57c00;"><?php echo $applications_by_status['Viewed']; ?></div>
                            <div style="color: #666; font-size: 0.9rem;">Viewed</div>
                        </div>
                        <div style="padding: 15px; background: #f3e5f5; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #7b1fa2;"><?php echo $applications_by_status['Interview']; ?></div>
                            <div style="color: #666; font-size: 0.9rem;">Interview</div>
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
                
                <?php if (count($recent_applications) > 0): ?>
                <div class="recent-jobs" style="margin-bottom: 30px;">
                    <h2 class="section-title">Recent Applications (Last 7 Days)</h2>
                    <?php foreach ($recent_applications as $app): ?>
                        <div class="job-item">
                            <div>
                                <div class="job-title"><?php echo htmlspecialchars($app['job_title']); ?></div>
                                <div class="job-meta">
                                    Applied on <?php echo date('M d, Y g:i A', strtotime($app['applied_date'])); ?>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($app['status'] ?? 'applied'); ?>">
                                <?php echo htmlspecialchars($app['status'] ?? 'Applied'); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
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
