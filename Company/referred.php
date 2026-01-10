<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_check.php';
require_once 'db.php';

// Get company information
$company_id = intval($_SESSION['company_id']); // Ensure it's an integer
$company_name = $_SESSION['company_name'];

// Debug: Log company_id being used for filtering
error_log("Company Referred Page - Company ID: $company_id (type: " . gettype($company_id) . "), Company Name: $company_name");

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

// Get all referred jobseekers (jobseekers with application_status = 'Referred' AND referred_to_company_id = this company)
$referred_jobseekers = [];

// Debug: Log company_id being used
error_log("Company Referred Page - Company ID: $company_id, Company Name: $company_name");

// Check if referred_to_company_id column exists
$check_column = $conn->query("SHOW COLUMNS FROM jobseeker LIKE 'referred_to_company_id'");
$has_referred_column = $check_column && $check_column->num_rows > 0;
error_log("Has referred_to_company_id column: " . ($has_referred_column ? 'YES' : 'NO'));

// Debug: Check all referred jobseekers regardless of company
$debug_all_referred = $conn->query("SELECT id, application_status, referred_to_company_id FROM jobseeker WHERE application_status = 'Referred'");
if ($debug_all_referred) {
    $all_referred = $debug_all_referred->fetch_all(MYSQLI_ASSOC);
    error_log("Total referred jobseekers in database: " . count($all_referred));
    foreach ($all_referred as $ref) {
        error_log("Jobseeker ID: {$ref['id']}, Status: {$ref['application_status']}, Referred to Company ID: " . ($ref['referred_to_company_id'] ?? 'NULL'));
    }
}

if ($has_referred_column) {
    // Filter by company_id - only show jobseekers specifically referred to this company
    // Also check for any referred jobseekers without company_id for debugging
    $debug_stmt = $conn->prepare("SELECT COUNT(*) as count FROM jobseeker WHERE application_status = 'Referred' AND referred_to_company_id IS NULL");
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result()->fetch_assoc();
    $debug_stmt->close();
    if ($debug_result && $debug_result['count'] > 0) {
        error_log("WARNING: Found {$debug_result['count']} referred jobseekers with NULL referred_to_company_id");
    }
    
    // Check how many are referred to this company
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM jobseeker WHERE application_status = 'Referred' AND referred_to_company_id = ?");
    $check_stmt->bind_param("i", $company_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    error_log("Found {$check_result['count']} jobseekers referred to company_id: $company_id");
    
    // Also check with NULL for debugging
    $check_null_stmt = $conn->query("SELECT COUNT(*) as count FROM jobseeker WHERE application_status = 'Referred' AND referred_to_company_id IS NULL");
    if ($check_null_stmt) {
        $null_result = $check_null_stmt->fetch_assoc();
        error_log("Found {$null_result['count']} jobseekers with NULL referred_to_company_id");
    }
    
    // Debug: Check all referred jobseekers and their company_ids
    $debug_all = $conn->query("SELECT id, application_status, referred_to_company_id, CAST(referred_to_company_id AS CHAR) as company_id_str FROM jobseeker WHERE application_status = 'Referred' LIMIT 10");
    if ($debug_all) {
        error_log("All referred jobseekers in database:");
        while ($row = $debug_all->fetch_assoc()) {
            error_log("  Jobseeker ID: {$row['id']}, Status: {$row['application_status']}, Company ID (int): " . ($row['referred_to_company_id'] ?? 'NULL') . ", Company ID (str): " . ($row['company_id_str'] ?? 'NULL'));
        }
    }
    
    $stmt = $conn->prepare("
        SELECT 
            id as jobseeker_id,
            firstname,
            middlename,
            surname,
            suffix,
            email,
            contact,
            dob,
            sex,
            barangay,
            municipality,
            province,
            resume_file,
            application_status,
            occupation1,
            occupation2,
            occupation3,
            local1,
            local2,
            local3,
            training_skills_1,
            training_skills_2,
            training_skills_3,
            skill_others,
            skill_auto_mechanic,
            skill_electrician,
            skill_photography,
            skill_beautician,
            skill_embroidery,
            skill_plumbing,
            skill_carpentry,
            skill_gardening,
            skill_sewing,
            skill_computer,
            skill_masonry,
            skill_stenography,
            skill_domestic,
            skill_painter,
            skill_tailoring,
            skill_driver,
            skill_painting,
            submission_date,
            referred_to_company_id
        FROM jobseeker 
        WHERE application_status = 'Referred' AND referred_to_company_id = ?
        ORDER BY submission_date DESC, id DESC
    ");
    $stmt->bind_param("i", $company_id);
} else {
    // Fallback: show all referred if column doesn't exist yet (for backward compatibility)
    $stmt = $conn->prepare("
        SELECT 
            id as jobseeker_id,
            firstname,
            middlename,
            surname,
            suffix,
            email,
            contact,
            dob,
            sex,
            barangay,
            municipality,
            province,
            resume_file,
            application_status,
            occupation1,
            occupation2,
            occupation3,
            local1,
            local2,
            local3,
            training_skills_1,
            training_skills_2,
            training_skills_3,
            skill_others,
            skill_auto_mechanic,
            skill_electrician,
            skill_photography,
            skill_beautician,
            skill_embroidery,
            skill_plumbing,
            skill_carpentry,
            skill_gardening,
            skill_sewing,
            skill_computer,
            skill_masonry,
            skill_stenography,
            skill_domestic,
            skill_painter,
            skill_tailoring,
            skill_driver,
            skill_painting,
            submission_date
        FROM jobseeker 
        WHERE application_status = 'Referred'
        ORDER BY submission_date DESC, id DESC
    ");
}
$stmt->execute();
$referred_jobseekers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get referral statistics for this company
$referral_stats = [
    'total_referred' => 0,
    'total_accepted' => 0,
    'total_rejected' => 0,
    'total_pending' => 0,
    'acceptance_rate' => 0
];

if ($has_referred_column) {
    // Total referred to this company (all time)
    $stats_stmt = $conn->prepare("SELECT COUNT(*) as count FROM jobseeker WHERE referred_to_company_id = ?");
    $stats_stmt->bind_param("i", $company_id);
    $stats_stmt->execute();
    $result = $stats_stmt->get_result();
    $referral_stats['total_referred'] = $result->fetch_assoc()['count'] ?? 0;
    $stats_stmt->close();
    
    // Total accepted (were referred to this company and now accepted)
    $stats_stmt = $conn->prepare("SELECT COUNT(*) as count FROM jobseeker WHERE referred_to_company_id = ? AND application_status = 'Accepted'");
    $stats_stmt->bind_param("i", $company_id);
    $stats_stmt->execute();
    $result = $stats_stmt->get_result();
    $referral_stats['total_accepted'] = $result->fetch_assoc()['count'] ?? 0;
    $stats_stmt->close();
    
    // Total rejected (were referred to this company and now rejected)
    $stats_stmt = $conn->prepare("SELECT COUNT(*) as count FROM jobseeker WHERE referred_to_company_id = ? AND application_status = 'Rejected'");
    $stats_stmt->bind_param("i", $company_id);
    $stats_stmt->execute();
    $result = $stats_stmt->get_result();
    $referral_stats['total_rejected'] = $result->fetch_assoc()['count'] ?? 0;
    $stats_stmt->close();
    
    // Total still pending/referred
    $stats_stmt = $conn->prepare("SELECT COUNT(*) as count FROM jobseeker WHERE referred_to_company_id = ? AND application_status = 'Referred'");
    $stats_stmt->bind_param("i", $company_id);
    $stats_stmt->execute();
    $result = $stats_stmt->get_result();
    $referral_stats['total_pending'] = $result->fetch_assoc()['count'] ?? 0;
    $stats_stmt->close();
    
    // Calculate acceptance rate
    $processed = $referral_stats['total_accepted'] + $referral_stats['total_rejected'];
    if ($processed > 0) {
        $referral_stats['acceptance_rate'] = ($referral_stats['total_accepted'] / $processed) * 100;
    }
} else {
    // Fallback: count all referred jobseekers
    $stats_stmt = $conn->query("SELECT COUNT(*) as count FROM jobseeker WHERE application_status = 'Referred'");
    if ($stats_stmt) {
        $referral_stats['total_referred'] = $stats_stmt->fetch_assoc()['count'] ?? 0;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referred Jobseekers - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
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
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100%;
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
        
        .referred-container {
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
        
        .jobseeker-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #2196f3;
        }
        
        .jobseeker-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .jobseeker-name {
            color: #1a3876;
            margin: 0 0 10px 0;
            font-size: 1.5rem;
        }
        
        .jobseeker-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .jobseeker-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-referred {
            background: #2196f3;
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .jobseeker-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.85rem;
        }
        
        .detail-value {
            color: #333;
            font-size: 0.95rem;
        }
        
        .jobseeker-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
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
        
        .btn-accept {
            background: #4caf50;
            color: white;
        }
        
        .btn-accept:hover {
            background: #45a049;
        }
        
        .btn-reject {
            background: #f44336;
            color: white;
        }
        
        .btn-reject:hover {
            background: #d32f2f;
        }
        
        .btn-resume {
            background: #ff9800;
            color: white;
        }
        
        .btn-resume:hover {
            background: #f57c00;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
        }
        
        /* Modal Styles */
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
            margin: 50px auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
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
        }
        
        .details-section {
            margin-bottom: 25px;
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
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .jobseeker-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .jobseeker-actions {
                flex-direction: column;
            }
            
            .jobseeker-details {
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
                <li><a href="view_applicants.php"><i class="fas fa-users"></i> View Applicants</a></li>
                <li><a href="referred.php" class="active"><i class="fas fa-user-check"></i> Referred</a></li>
                <li><a href="profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
                <li><a href="#" class="logout" onclick="showLogoutModal(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="referred-container">
                <div class="page-header">
                    <h1><i class="fas fa-user-check"></i> Referred Jobseekers</h1>
                    <p>Review and manage jobseekers referred by the admin</p>
                </div>

                <!-- Referral Statistics Section (Always shown) -->
                <div style="margin-bottom: 30px;">
                    <h2 style="color: #1a3876; margin-bottom: 20px; font-size: 1.5rem;">
                        <i class="fas fa-chart-bar"></i> Referral Statistics
                    </h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <!-- Total Referred -->
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #1976d2;">
                            <div style="font-size: 2rem; color: #1976d2; margin-bottom: 8px;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #1976d2; margin-bottom: 5px;">
                                <?php echo $referral_stats['total_referred']; ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Total Referred</div>
                        </div>
                        
                        <!-- Total Accepted -->
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #388e3c;">
                            <div style="font-size: 2rem; color: #388e3c; margin-bottom: 8px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #388e3c; margin-bottom: 5px;">
                                <?php echo $referral_stats['total_accepted']; ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Accepted</div>
                        </div>
                        
                        <!-- Total Rejected -->
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #c62828;">
                            <div style="font-size: 2rem; color: #c62828; margin-bottom: 8px;">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #c62828; margin-bottom: 5px;">
                                <?php echo $referral_stats['total_rejected']; ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Rejected</div>
                        </div>
                        
                        <!-- Pending/Referred -->
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #ff9800;">
                            <div style="font-size: 2rem; color: #ff9800; margin-bottom: 8px;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #ff9800; margin-bottom: 5px;">
                                <?php echo $referral_stats['total_pending']; ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Pending Review</div>
                        </div>
                        
                        <!-- Acceptance Rate -->
                        <?php if ($referral_stats['total_accepted'] + $referral_stats['total_rejected'] > 0): ?>
                        <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #1a3876;">
                            <div style="font-size: 2rem; color: #1a3876; margin-bottom: 8px;">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #1a3876; margin-bottom: 5px;">
                                <?php echo number_format($referral_stats['acceptance_rate'], 1); ?>%
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">Acceptance Rate</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($referred_jobseekers)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-check"></i>
                        <h3>No Referred Jobseekers</h3>
                        <p>There are no jobseekers referred to your company at this time.</p>
                        <p style="margin-top: 15px; font-size: 0.9rem; color: #999;">
                            <strong>Note:</strong> Jobseekers will appear here only after the admin refers them specifically to your company. 
                            Make sure the admin selects your company when referring jobseekers.
                        </p>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; color: #1976d2; font-weight: 600;">
                        <i class="fas fa-info-circle"></i> Currently Referred: <?php echo count($referred_jobseekers); ?>
                    </div>

                    <?php foreach ($referred_jobseekers as $jobseeker): ?>
                        <?php
                            $full_name = trim(($jobseeker['firstname'] ?? '') . ' ' . ($jobseeker['middlename'] && $jobseeker['middlename'] !== 'n/a' ? $jobseeker['middlename'] . ' ' : '') . ($jobseeker['surname'] ?? '') . ($jobseeker['suffix'] ? ' ' . $jobseeker['suffix'] : ''));
                            if (empty($full_name)) {
                                $full_name = 'Jobseeker #' . $jobseeker['jobseeker_id'];
                            }
                            
                            // Calculate age
                            $age = '';
                            if (!empty($jobseeker['dob'])) {
                                $dob = new DateTime($jobseeker['dob']);
                                $today = new DateTime();
                                $age = $today->diff($dob)->y;
                            }
                            
                            // Format submission date
                            $submission_date = '';
                            if (!empty($jobseeker['submission_date'])) {
                                $submission_date = date('M d, Y', strtotime($jobseeker['submission_date']));
                            }
                        ?>
                        <div class="jobseeker-card">
                            <div class="jobseeker-header">
                                <div>
                                    <h2 class="jobseeker-name"><?php echo htmlspecialchars($full_name); ?></h2>
                                    <div class="jobseeker-meta">
                                        <?php if ($age): ?>
                                            <div class="jobseeker-meta-item">
                                                <i class="fas fa-birthday-cake"></i>
                                                <span><?php echo $age; ?> years old</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($jobseeker['sex'])): ?>
                                            <div class="jobseeker-meta-item">
                                                <i class="fas fa-<?php echo strtolower($jobseeker['sex']) === 'male' ? 'mars' : 'venus'; ?>"></i>
                                                <span><?php echo htmlspecialchars($jobseeker['sex']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($jobseeker['municipality']) || !empty($jobseeker['province'])): ?>
                                            <div class="jobseeker-meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars(trim(($jobseeker['municipality'] ?? '') . ', ' . ($jobseeker['province'] ?? ''), ', ')); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($submission_date): ?>
                                            <div class="jobseeker-meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <span>Referred: <?php echo $submission_date; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="status-referred">REFERRED</span>
                            </div>

                            <div class="jobseeker-details">
                                <?php if (!empty($jobseeker['email'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($jobseeker['email']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($jobseeker['contact'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Contact</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($jobseeker['contact']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($jobseeker['occupation1']) || !empty($jobseeker['occupation2']) || !empty($jobseeker['occupation3'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Preferred Occupation</span>
                                        <span class="detail-value">
                                            <?php 
                                                $occupations = array_filter([$jobseeker['occupation1'], $jobseeker['occupation2'], $jobseeker['occupation3']]);
                                                echo htmlspecialchars(implode(', ', $occupations));
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($jobseeker['local1']) || !empty($jobseeker['local2']) || !empty($jobseeker['local3'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Preferred Location</span>
                                        <span class="detail-value">
                                            <?php 
                                                $locations = array_filter([$jobseeker['local1'], $jobseeker['local2'], $jobseeker['local3']]);
                                                echo htmlspecialchars(implode(', ', $locations));
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="jobseeker-actions">
                                <button class="btn btn-view" onclick="viewJobseekerDetails(<?php echo htmlspecialchars(json_encode($jobseeker)); ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <?php if ($jobseeker['resume_file']): ?>
                                    <button class="btn btn-resume" onclick="viewResume('<?php echo htmlspecialchars($jobseeker['resume_file']); ?>')">
                                        <i class="fas fa-file-alt"></i> View Resume
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-accept" onclick="acceptReferredJobseeker(<?php echo $jobseeker['jobseeker_id']; ?>, '<?php echo htmlspecialchars($full_name); ?>')">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button class="btn btn-reject" onclick="rejectReferredJobseeker(<?php echo $jobseeker['jobseeker_id']; ?>, '<?php echo htmlspecialchars($full_name); ?>')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Jobseeker Details Modal -->
    <div id="jobseekerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Jobseeker Details</h2>
                <button class="close-btn" onclick="closeModal('jobseekerModal')">&times;</button>
            </div>
            <div id="jobseekerModalContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div id="rejectionModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Reject Referred Jobseeker</h2>
                <button class="close-btn" onclick="closeModal('rejectionModal')">&times;</button>
            </div>
            <div style="padding: 20px;">
                <p style="margin-bottom: 15px; color: #666;">Please provide a reason for rejecting this referred jobseeker. This will be sent to the jobseeker via email.</p>
                <form id="rejectionForm">
                    <input type="hidden" id="rejectJobseekerId" value="">
                    <input type="hidden" id="rejectJobseekerName" value="">
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

        // View jobseeker details
        function viewJobseekerDetails(jobseeker) {
            const fullName = (jobseeker.firstname || '') + ' ' + 
                           (jobseeker.middlename && jobseeker.middlename !== 'n/a' ? jobseeker.middlename + ' ' : '') + 
                           (jobseeker.surname || '') + 
                           (jobseeker.suffix ? ' ' + jobseeker.suffix : '');
            
            // Calculate age
            let age = '';
            if (jobseeker.dob) {
                const dob = new Date(jobseeker.dob);
                const today = new Date();
                age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
            }
            
            let html = `
                <div class="details-section">
                    <h3>Personal Information</h3>
                    <div class="detail-row">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">${fullName.trim() || 'N/A'}</div>
                    </div>
                    ${age ? `<div class="detail-row">
                        <div class="detail-label">Age</div>
                        <div class="detail-value">${age} years old</div>
                    </div>` : ''}
                    ${jobseeker.sex ? `<div class="detail-row">
                        <div class="detail-label">Gender</div>
                        <div class="detail-value">${jobseeker.sex}</div>
                    </div>` : ''}
                    ${jobseeker.email ? `<div class="detail-row">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">${jobseeker.email}</div>
                    </div>` : ''}
                    ${jobseeker.contact ? `<div class="detail-row">
                        <div class="detail-label">Contact</div>
                        <div class="detail-value">${jobseeker.contact}</div>
                    </div>` : ''}
                    ${jobseeker.barangay || jobseeker.municipality || jobseeker.province ? `<div class="detail-row">
                        <div class="detail-label">Address</div>
                        <div class="detail-value">${[jobseeker.barangay, jobseeker.municipality, jobseeker.province].filter(Boolean).join(', ')}</div>
                    </div>` : ''}
                </div>
            `;
            
            // Preferred Occupations
            const occupations = [jobseeker.occupation1, jobseeker.occupation2, jobseeker.occupation3].filter(Boolean);
            if (occupations.length > 0) {
                html += `
                    <div class="details-section">
                        <h3>Preferred Occupations</h3>
                        <div class="detail-row">
                            <div class="detail-label">Occupations</div>
                            <div class="detail-value">${occupations.join(', ')}</div>
                        </div>
                    </div>
                `;
            }
            
            // Preferred Locations
            const locations = [jobseeker.local1, jobseeker.local2, jobseeker.local3].filter(Boolean);
            if (locations.length > 0) {
                html += `
                    <div class="details-section">
                        <h3>Preferred Locations</h3>
                        <div class="detail-row">
                            <div class="detail-label">Locations</div>
                            <div class="detail-value">${locations.join(', ')}</div>
                        </div>
                    </div>
                `;
            }
            
            // Skills - Check both training_skills and boolean skill columns
            const predefinedSkills = [];
            const trainingSkills = [];
            const otherSkills = [];
            
            // Collect predefined boolean skills
            const skillMap = {
                'skill_auto_mechanic': 'Auto Mechanic',
                'skill_electrician': 'Electrician',
                'skill_photography': 'Photography',
                'skill_beautician': 'Beautician',
                'skill_embroidery': 'Embroidery',
                'skill_plumbing': 'Plumbing',
                'skill_carpentry': 'Carpentry',
                'skill_gardening': 'Gardening',
                'skill_sewing': 'Sewing',
                'skill_computer': 'Computer Literacy',
                'skill_masonry': 'Masonry',
                'skill_stenography': 'Stenography',
                'skill_domestic': 'Domestic Chores',
                'skill_painter': 'Painter/Artist',
                'skill_tailoring': 'Tailoring',
                'skill_driver': 'Driving',
                'skill_painting': 'Painting'
            };
            
            for (const [key, label] of Object.entries(skillMap)) {
                if (jobseeker[key] && (jobseeker[key] === 1 || jobseeker[key] === '1' || jobseeker[key] === true)) {
                    predefinedSkills.push(label);
                }
            }
            
            // Collect training skills
            if (jobseeker.training_skills_1 && jobseeker.training_skills_1 !== 'n/a' && jobseeker.training_skills_1.trim() !== '') {
                trainingSkills.push(jobseeker.training_skills_1);
            }
            if (jobseeker.training_skills_2 && jobseeker.training_skills_2 !== 'n/a' && jobseeker.training_skills_2.trim() !== '') {
                trainingSkills.push(jobseeker.training_skills_2);
            }
            if (jobseeker.training_skills_3 && jobseeker.training_skills_3 !== 'n/a' && jobseeker.training_skills_3.trim() !== '') {
                trainingSkills.push(jobseeker.training_skills_3);
            }
            
            // Collect other skills
            if (jobseeker.skill_others && jobseeker.skill_others !== 'n/a' && jobseeker.skill_others.trim() !== '') {
                otherSkills.push(jobseeker.skill_others);
            }
            
            // Combine all skills
            const allSkills = [...predefinedSkills, ...trainingSkills, ...otherSkills];
            
            if (allSkills.length > 0) {
                html += `
                    <div class="details-section">
                        <h3>Skills</h3>
                        <div class="detail-row">
                            <div class="detail-label">Skills</div>
                            <div class="detail-value">${allSkills.join(', ')}</div>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('jobseekerModalContent').innerHTML = html;
            document.getElementById('jobseekerModal').style.display = 'block';
        }

        // View resume
        function viewResume(resumePath) {
            const url = '../uploads/resumes/' + resumePath;
            window.open(url, '_blank');
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Accept referred jobseeker
        function acceptReferredJobseeker(jobseekerId, jobseekerName) {
            Swal.fire({
                title: 'Accept Jobseeker?',
                html: `Are you sure you want to accept <strong>${jobseekerName}</strong>?<br><br>This will update their status to "Accepted" and notify them via email.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4caf50',
                cancelButtonColor: '#666',
                confirmButtonText: 'Yes, Accept',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('handle_referred.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'accept',
                            jobseeker_id: jobseekerId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                title: 'Jobseeker Accepted!',
                                text: data.message || 'The jobseeker has been accepted and notified via email.',
                                icon: 'success',
                                confirmButtonColor: '#1a3876'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Failed to accept jobseeker. Please try again.',
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

        // Reject referred jobseeker
        function rejectReferredJobseeker(jobseekerId, jobseekerName) {
            document.getElementById('rejectJobseekerId').value = jobseekerId;
            document.getElementById('rejectJobseekerName').value = jobseekerName;
            document.getElementById('rejectionReason').value = '';
            document.getElementById('rejectionModal').style.display = 'block';
        }

        // Handle rejection form submission
        document.getElementById('rejectionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const jobseekerId = document.getElementById('rejectJobseekerId').value;
            const jobseekerName = document.getElementById('rejectJobseekerName').value;
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

            document.getElementById('rejectionModal').style.display = 'none';

            Swal.fire({
                title: 'Reject Jobseeker?',
                html: `Are you sure you want to reject <strong>${jobseekerName}</strong>?<br><br>This will update their status to "Rejected" and notify them via email.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#666',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('handle_referred.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'reject',
                            jobseeker_id: jobseekerId,
                            rejection_reason: rejectionReason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                title: 'Jobseeker Rejected!',
                                text: data.message || 'The jobseeker has been rejected and notified via email.',
                                icon: 'success',
                                confirmButtonColor: '#1a3876'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Failed to reject jobseeker. Please try again.',
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
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const jobseekerModal = document.getElementById('jobseekerModal');
            const rejectionModal = document.getElementById('rejectionModal');
            
            if (event.target === jobseekerModal) {
                jobseekerModal.style.display = 'none';
            }
            if (event.target === rejectionModal) {
                rejectionModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
