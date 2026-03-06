<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_check.php';
require_once 'db.php';

// Get company information
$company_id = $_SESSION['company_id'];
$company_name = $_SESSION['company_name'];

// Get company logo
$company_logo = null;
$columns_check = $conn->query("SHOW COLUMNS FROM company_users");
$existing_columns = [];
if ($columns_check) {
    while ($row = $columns_check->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
}

if (in_array('logo', $existing_columns)) {
    $stmt = $conn->prepare("SELECT logo FROM company_users WHERE id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $company_profile = $result->fetch_assoc();
    if ($company_profile && isset($company_profile['logo'])) {
        $company_logo = $company_profile['logo'];
    }
    $stmt->close();
}

// Get all job postings for this company
$jobs = [];
$job_applicants = [];

$table_check = $conn->query("SHOW TABLES LIKE 'job_postings'");
if ($table_check && $table_check->num_rows > 0) {
    $columns = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
    if ($columns && $columns->num_rows > 0) {
        // Get all jobs for this company
        $stmt = $conn->prepare("SELECT id, title, description, requirements, salary_range, location, job_type, industry, status, created_at FROM job_postings WHERE company_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get applicants for each job
        $app_table_check = $conn->query("SHOW TABLES LIKE 'job_applications_extended'");
        if ($app_table_check && $app_table_check->num_rows > 0) {
            foreach ($jobs as $job) {
                $job_id = $job['id'];
                $stmt = $conn->prepare("
                    SELECT 
                        jae.id as application_id,
                        jae.status as application_status,
                        jae.applied_date,
                        jae.viewed_date,
                        jae.compatibility_score,
                        jae.notes,
                        j.id as jobseeker_id,
                        j.firstname,
                        j.middlename,
                        j.surname,
                        j.suffix,
                        j.email,
                        j.contact,
                        j.dob,
                        j.sex,
                        j.barangay,
                        j.municipality,
                        j.province,
                        j.resume_file,
                        j.application_status as jobseeker_status
                    FROM job_applications_extended jae
                    INNER JOIN jobseeker j ON jae.jobseeker_id = j.id
                    WHERE jae.job_posting_id = ?
                    ORDER BY jae.applied_date DESC
                ");
                $stmt->bind_param("i", $job_id);
                $stmt->execute();
                $applicants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $job_applicants[$job_id] = $applicants;
                $stmt->close();
            }
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
    <title>View Applicants - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/Company-sidebar.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
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
            overflow: hidden;
        }
        
        .profile-icon:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .profile-icon i {
            color: white;
        }
        
        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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
        
        .applicants-container {
            padding: 0;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #1a3876;
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .page-header p {
            color: #666;
            margin: 0;
        }
        
        .job-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .job-title-section h2 {
            color: #1a3876;
            margin: 0 0 10px 0;
            font-size: 1.5rem;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .job-status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #4caf50;
        }
        
        .status-closed {
            background: #ffebee;
            color: #f44336;
        }
        
        .status-draft {
            background: #fff3e0;
            color: #ff9800;
        }
        
        .applicants-count {
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .applicants-list {
            margin-top: 20px;
        }
        
        .applicant-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #1a3876;
            transition: all 0.3s ease;
        }
        
        .applicant-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(4px);
        }
        
        .applicant-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .applicant-info h3 {
            color: #1a3876;
            margin: 0 0 8px 0;
            font-size: 1.1rem;
        }
        
        .applicant-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .applicant-detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .application-status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-applied {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-viewed {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-interview {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .status-accepted {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .status-rejected {
            background: #ffebee;
            color: #c62828;
        }
        
        .applicant-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-view {
            background: #1a3876;
            color: white;
        }
        
        .btn-view:hover {
            background: #2c5aa0;
        }
        
        .btn-resume {
            background: #1976d2;
            color: white;
        }
        
        .btn-resume:hover {
            background: #1565c0;
        }
        
        .btn-accept {
            background: #4caf50;
            color: white;
        }
        
        .btn-accept:hover {
            background: #388e3c;
        }
        
        .btn-reject {
            background: #f44336;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c62828;
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 20px auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .modal-header h2 {
            color: #1a3876;
            margin: 0;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 2rem;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .close-btn:hover {
            background: #f0f0f0;
            color: #333;
        }
        
        .details-section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .details-section h3 {
            color: #1a3876;
            margin: 0 0 15px 0;
            font-size: 1.2rem;
            border-bottom: 2px solid #1a3876;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        
        .detail-value {
            color: #333;
        }
        
        .resume-preview {
            text-align: center;
            margin: 20px 0;
        }
        
        .resume-preview img {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .job-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .applicant-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .applicant-actions {
                flex-direction: column;
            }
            
            .applicant-actions .btn {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 10px 20px;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
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
                        <img src="../<?php echo htmlspecialchars($company_logo); ?>" alt="Company Logo">
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="jobposting.php"><i class="fas fa-briefcase"></i> Job Posting</a></li>
                <li><a href="view_applicants.php" class="active"><i class="fas fa-users"></i> View Applicants</a></li>
                <li><a href="referred.php"><i class="fas fa-user-check"></i> Referred</a></li>
                <li><a href="admin_requests.php"><i class="fas fa-envelope"></i> Admin Requests</a></li>
                <li><a href="profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
                <li><a href="#" class="logout" onclick="showLogoutModal(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
                            
        <div class="main-content">
            <div class="applicants-container">
                <div class="page-header">
                    <h1><i class="fas fa-users"></i> Job Applicants</h1>
                    <p>Review applications and jobseeker profiles for your job postings</p>
                </div>

                <?php if (empty($jobs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <h3>No Job Postings Yet</h3>
                        <p>You haven't posted any jobs yet. Create your first job posting to start receiving applications.</p>
                        <a href="jobposting.php" class="btn btn-view" style="margin-top: 20px; display: inline-block; text-decoration: none;">Post a Job</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <?php 
                            $job_id = $job['id'];
                            $applicants = $job_applicants[$job_id] ?? [];
                            $applicant_count = count($applicants);
                        ?>
                        <div class="job-card">
                            <div class="job-header">
                                <div class="job-title-section">
                                    <h2><?php echo htmlspecialchars($job['title']); ?></h2>
                                    <div class="job-meta">
                                        <div class="job-meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($job['location']); ?></span>
                                        </div>
                                        <div class="job-meta-item">
                                            <i class="fas fa-briefcase"></i>
                                            <span><?php echo htmlspecialchars($job['job_type']); ?></span>
                                        </div>
                                        <?php if ($job['salary_range']): ?>
                                        <div class="job-meta-item">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>₱<?php echo htmlspecialchars($job['salary_range']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="job-meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span>Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                                    <span class="job-status-badge status-<?php echo strtolower($job['status']); ?>">
                                        <?php echo htmlspecialchars($job['status']); ?>
                                    </span>
                                    <span class="applicants-count">
                                        <i class="fas fa-users"></i> <?php echo $applicant_count; ?> <?php echo $applicant_count == 1 ? 'Applicant' : 'Applicants'; ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($applicant_count > 0): ?>
                                <div class="applicants-list">
                                    <?php foreach ($applicants as $applicant): ?>
                                        <?php
                                            $full_name = trim(($applicant['firstname'] ?? '') . ' ' . 
                                                ($applicant['middlename'] && $applicant['middlename'] !== 'n/a' ? $applicant['middlename'] . ' ' : '') . 
                                                ($applicant['surname'] ?? '') . 
                                                ($applicant['suffix'] && $applicant['suffix'] !== 'n/a' ? ', ' . $applicant['suffix'] : ''));
                                            if (empty($full_name)) {
                                                $full_name = 'Applicant #' . $applicant['jobseeker_id'];
                                            }
                                            
                                            // Calculate age
                                            $age = '';
                                            if (!empty($applicant['dob'])) {
                                                $birthDate = new DateTime($applicant['dob']);
                                                $today = new DateTime();
                                                $age = $today->diff($birthDate)->y;
                                            }
                                            
                                            $address = trim(($applicant['barangay'] ?? '') . ', ' . 
                                                ($applicant['municipality'] ?? '') . ', ' . 
                                                ($applicant['province'] ?? ''));
                                            $address = trim($address, ', ');
                                        ?>
                                        <div class="applicant-card">
                                            <div class="applicant-header">
                                                <div class="applicant-info">
                                                    <h3><?php echo htmlspecialchars($full_name); ?></h3>
                                                    <div class="applicant-details">
                                                        <?php if ($applicant['email']): ?>
                                                        <div class="applicant-detail-item">
                                                            <i class="fas fa-envelope"></i>
                                                            <span><?php echo htmlspecialchars($applicant['email']); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if ($applicant['contact']): ?>
                                                        <div class="applicant-detail-item">
                                                            <i class="fas fa-phone"></i>
                                                            <span><?php echo htmlspecialchars($applicant['contact']); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if ($age): ?>
                                                        <div class="applicant-detail-item">
                                                            <i class="fas fa-birthday-cake"></i>
                                                            <span><?php echo $age; ?> years old</span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if ($applicant['sex']): ?>
                                                        <div class="applicant-detail-item">
                                                            <i class="fas fa-<?php echo strtolower($applicant['sex']) == 'f' ? 'venus' : 'mars'; ?>"></i>
                                                            <span><?php echo htmlspecialchars($applicant['sex']); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if ($address): ?>
                                                        <div class="applicant-detail-item">
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <span><?php echo htmlspecialchars($address); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="applicant-detail-item">
                                                            <i class="fas fa-calendar-check"></i>
                                                            <span>Applied: <?php echo date('M d, Y g:i A', strtotime($applicant['applied_date'])); ?></span>
                                                        </div>
                                                        <?php if ($applicant['compatibility_score']): ?>
                                                        <div class="applicant-detail-item">
                                                            <i class="fas fa-star"></i>
                                                            <span>Match: <?php echo number_format($applicant['compatibility_score'], 1); ?>%</span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <span class="application-status-badge status-<?php echo strtolower($applicant['application_status'] ?? 'applied'); ?>">
                                                    <?php echo htmlspecialchars($applicant['application_status'] ?? 'Applied'); ?>
                                                </span>
                                            </div>
                                            <div class="applicant-actions">
                                                <button class="btn btn-view" onclick="viewApplicantDetails(<?php echo htmlspecialchars(json_encode($applicant)); ?>)">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                                <?php if ($applicant['resume_file']): ?>
                                                <button class="btn btn-resume" onclick="viewResume('<?php echo htmlspecialchars($applicant['resume_file']); ?>')">
                                                    <i class="fas fa-file-alt"></i> View Resume
                                                </button>
                                                <?php endif; ?>
                                                <?php if (strtolower($applicant['application_status']) !== 'accepted' && strtolower($applicant['application_status']) !== 'rejected'): ?>
                                                <button class="btn btn-accept" onclick="acceptApplicant(<?php echo $applicant['application_id']; ?>, <?php echo $applicant['jobseeker_id']; ?>, <?php echo $job_id; ?>, '<?php echo htmlspecialchars($job['title']); ?>')">
                                                    <i class="fas fa-check"></i> Accept
                                                </button>
                                                <button class="btn btn-reject" onclick="rejectApplicant(<?php echo $applicant['application_id']; ?>, <?php echo $applicant['jobseeker_id']; ?>, <?php echo $job_id; ?>, '<?php echo htmlspecialchars($job['title']); ?>')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state" style="padding: 30px;">
                                    <i class="fas fa-user-slash"></i>
                                    <p>No applicants yet for this job posting.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Applicant Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Applicant Details</h2>
                <button class="close-btn" onclick="closeModal('detailsModal')">&times;</button>
            </div>
            <div id="detailsContent"></div>
        </div>
    </div>

    <!-- Resume Modal -->
    <div id="resumeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Resume Preview</h2>
                <button class="close-btn" onclick="closeModal('resumeModal')">&times;</button>
            </div>
            <div id="resumeContent"></div>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div id="rejectionModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Reject Application</h2>
                <button class="close-btn" onclick="closeModal('rejectionModal')">&times;</button>
            </div>
            <div style="padding: 20px;">
                <p style="margin-bottom: 15px; color: #666;">Please provide a reason for rejecting this application. This will be sent to the jobseeker via email.</p>
                <form id="rejectionForm">
                    <input type="hidden" id="rejectApplicationId" value="">
                    <input type="hidden" id="rejectJobseekerId" value="">
                    <input type="hidden" id="rejectJobId" value="">
                    <input type="hidden" id="rejectJobTitle" value="">
                    <div style="margin-bottom: 20px;">
                        <label for="rejectionReason" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Rejection Reason *</label>
                        <textarea id="rejectionReason" name="rejectionReason" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-height: 120px; resize: vertical; font-family: inherit;" placeholder="Enter the reason for rejection..."></textarea>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn" style="background: #6c757d; color: white;" onclick="closeModal('rejectionModal')">Cancel</button>
                        <button type="submit" class="btn btn-reject">Submit Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleProfileMenu() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        window.onclick = function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!event.target.matches('.profile-icon') && !event.target.closest('.profile-icon')) {
                if (dropdown && dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                }
            }
        }

        function showLogoutModal() {
            document.getElementById('profileDropdown').style.display = 'none';
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

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function viewResume(resumeFile) {
            const modal = document.getElementById('resumeModal');
            const content = document.getElementById('resumeContent');
            
            const ext = resumeFile.split('.').pop().toLowerCase();
            const url = '../uploads/resumes/' + resumeFile;
            const imageExts = ["jpg","jpeg","png","gif","bmp","webp"];
            
            if (imageExts.includes(ext)) {
                content.innerHTML = `
                    <div class="resume-preview">
                        <img src="${url}" alt="Resume" style="max-width: 100%; border-radius: 8px;">
                        <div style="margin-top: 20px;">
                            <a href="${url}" target="_blank" class="btn btn-resume" style="text-decoration: none; display: inline-block;">
                                <i class="fas fa-download"></i> Download Resume
                            </a>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="resume-preview">
                        <div style="background: #f8f9fa; padding: 40px; border-radius: 8px; text-align: center;">
                            <i class="fas fa-file-pdf" style="font-size: 4rem; color: #1976d2; margin-bottom: 20px;"></i>
                            <h3 style="color: #333; margin-bottom: 10px;">Resume File</h3>
                            <p style="color: #666; margin-bottom: 20px;">${ext.toUpperCase()} format</p>
                            <a href="${url}" target="_blank" class="btn btn-resume" style="text-decoration: none; display: inline-block;">
                                <i class="fas fa-download"></i> Download Resume
                            </a>
                        </div>
                    </div>
                `;
            }
            
            modal.style.display = 'block';
        }

        function viewApplicantDetails(applicant) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            
            const fullName = `${applicant.firstname || ''} ${applicant.middlename && applicant.middlename !== 'n/a' ? applicant.middlename + ' ' : ''}${applicant.surname || ''}${applicant.suffix && applicant.suffix !== 'n/a' ? ', ' + applicant.suffix : ''}`.trim();
            
            let html = `
                <div class="details-section">
                    <h3>Personal Information</h3>
                    <div class="detail-row">
                        <div class="detail-label">Full Name:</div>
                        <div class="detail-value">${fullName || 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value">${applicant.email || 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Contact:</div>
                        <div class="detail-value">${applicant.contact || 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Date of Birth:</div>
                        <div class="detail-value">${applicant.dob ? new Date(applicant.dob).toLocaleDateString() : 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Gender:</div>
                        <div class="detail-value">${applicant.sex || 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Address:</div>
                        <div class="detail-value">${applicant.barangay || ''}${applicant.municipality ? ', ' + applicant.municipality : ''}${applicant.province ? ', ' + applicant.province : ''}</div>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>Application Information</h3>
                    <div class="detail-row">
                        <div class="detail-label">Application Status:</div>
                        <div class="detail-value">
                            <span class="application-status-badge status-${(applicant.application_status || 'applied').toLowerCase()}">
                                ${applicant.application_status || 'Applied'}
                            </span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Applied Date:</div>
                        <div class="detail-value">${new Date(applicant.applied_date).toLocaleString()}</div>
                    </div>
                    ${applicant.viewed_date ? `
                    <div class="detail-row">
                        <div class="detail-label">Viewed Date:</div>
                        <div class="detail-value">${new Date(applicant.viewed_date).toLocaleString()}</div>
                    </div>
                    ` : ''}
                    ${applicant.compatibility_score ? `
                    <div class="detail-row">
                        <div class="detail-label">Compatibility Score:</div>
                        <div class="detail-value">${parseFloat(applicant.compatibility_score).toFixed(1)}%</div>
                    </div>
                    ` : ''}
                    ${applicant.notes ? `
                    <div class="detail-row">
                        <div class="detail-label">Notes:</div>
                        <div class="detail-value">${applicant.notes}</div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const detailsModal = document.getElementById('detailsModal');
            const resumeModal = document.getElementById('resumeModal');
            const rejectionModal = document.getElementById('rejectionModal');
            if (event.target == detailsModal) {
                detailsModal.style.display = 'none';
            }
            if (event.target == resumeModal) {
                resumeModal.style.display = 'none';
            }
            if (event.target == rejectionModal) {
                rejectionModal.style.display = 'none';
            }
        }

        function acceptApplicant(applicationId, jobseekerId, jobId, jobTitle) {
            Swal.fire({
                title: 'Accept Application?',
                text: 'Are you sure you want to accept this applicant? An email will be sent to notify them.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4caf50',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Accept',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Processing...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('handle_application.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'accept',
                            application_id: applicationId,
                            jobseeker_id: jobseekerId,
                            job_id: jobId,
                            job_title: jobTitle
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                title: 'Application Accepted!',
                                text: data.message || 'The applicant has been notified via email.',
                                icon: 'success',
                                confirmButtonColor: '#1a3876'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Failed to accept application. Please try again.',
                                icon: 'error',
                                confirmButtonColor: '#1a3876'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#1a3876'
                        });
                        console.error('Error:', error);
                    });
                }
            });
        }

        function rejectApplicant(applicationId, jobseekerId, jobId, jobTitle) {
            document.getElementById('rejectApplicationId').value = applicationId;
            document.getElementById('rejectJobseekerId').value = jobseekerId;
            document.getElementById('rejectJobId').value = jobId;
            document.getElementById('rejectJobTitle').value = jobTitle;
            document.getElementById('rejectionReason').value = '';
            document.getElementById('rejectionModal').style.display = 'block';
        }

        // Handle rejection form submission
        document.getElementById('rejectionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const applicationId = document.getElementById('rejectApplicationId').value;
            const jobseekerId = document.getElementById('rejectJobseekerId').value;
            const jobId = document.getElementById('rejectJobId').value;
            const jobTitle = document.getElementById('rejectJobTitle').value;
            const rejectionReason = document.getElementById('rejectionReason').value.trim();

            if (!rejectionReason) {
                Swal.fire({
                    title: 'Reason Required',
                    text: 'Please provide a reason for rejection.',
                    icon: 'warning',
                    confirmButtonColor: '#1a3876'
                });
                return;
            }

            // Close modal
            document.getElementById('rejectionModal').style.display = 'none';

            // Show loading
            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('handle_application.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reject',
                    application_id: applicationId,
                    jobseeker_id: jobseekerId,
                    job_id: jobId,
                    job_title: jobTitle,
                    rejection_reason: rejectionReason
                })
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        title: 'Application Rejected',
                        text: data.message || 'The applicant has been notified via email.',
                        icon: 'success',
                        confirmButtonColor: '#1a3876'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to reject application. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#1a3876'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#1a3876'
                });
                console.error('Error:', error);
            });
        });
        
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
    </script>
</body>
</html>

