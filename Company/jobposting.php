<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_check.php';
require_once 'db.php';

// Get company information
$company_id = $_SESSION['company_id'];
$company_name = $_SESSION['company_name'];
$email = $_SESSION['email'];

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_job':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $requirements = trim($_POST['requirements'] ?? '');
                $salary_range = trim($_POST['salary_range'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $job_type = $_POST['job_type'] ?? 'Full-time';
                $industry = trim($_POST['industry'] ?? '');
                $status = $_POST['status'] ?? 'Active';
                
                // Check if company_id column exists
                $check_column = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
                if ($check_column && $check_column->num_rows > 0) {
                    // Insert with company_id
                    $stmt = $conn->prepare("INSERT INTO job_postings (title, company, description, requirements, salary_range, location, job_type, industry, status, company_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssssi", $title, $company_name, $description, $requirements, $salary_range, $location, $job_type, $industry, $status, $company_id);
                } else {
                    // Insert without company_id (backward compatibility)
                    $stmt = $conn->prepare("INSERT INTO job_postings (title, company, description, requirements, salary_range, location, job_type, industry, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssss", $title, $company_name, $description, $requirements, $salary_range, $location, $job_type, $industry, $status);
                }
                
                if ($stmt->execute()) {
                    $success_message = "Job posting created successfully!";
                    // Redirect to prevent form resubmission
                    header("Location: jobposting.php?success=1");
                    exit();
                } else {
                    $error_message = "Error creating job posting: " . $conn->error;
                }
                $stmt->close();
                break;
                
            case 'update_job':
                $job_id = $_POST['job_id'] ?? 0;
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $requirements = trim($_POST['requirements'] ?? '');
                $salary_range = trim($_POST['salary_range'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $job_type = $_POST['job_type'] ?? 'Full-time';
                $industry = trim($_POST['industry'] ?? '');
                $status = $_POST['status'] ?? 'Active';
                
                // Verify job belongs to this company
                $check_column = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
                if ($check_column && $check_column->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE job_postings SET title=?, company=?, description=?, requirements=?, salary_range=?, location=?, job_type=?, industry=?, status=? WHERE id=? AND company_id=?");
                    $stmt->bind_param("sssssssssii", $title, $company_name, $description, $requirements, $salary_range, $location, $job_type, $industry, $status, $job_id, $company_id);
                } else {
                    $stmt = $conn->prepare("UPDATE job_postings SET title=?, company=?, description=?, requirements=?, salary_range=?, location=?, job_type=?, industry=?, status=? WHERE id=? AND company=?");
                    $stmt->bind_param("ssssssssss", $title, $company_name, $description, $requirements, $salary_range, $location, $job_type, $industry, $status, $job_id, $company_name);
                }
                
                if ($stmt->execute()) {
                    $success_message = "Job posting updated successfully!";
                    header("Location: jobposting.php?success=1");
                    exit();
                } else {
                    $error_message = "Error updating job posting: " . $conn->error;
                }
                $stmt->close();
                break;
                
            case 'delete_job':
                $job_id = $_POST['job_id'] ?? 0;
                
                // Verify job belongs to this company
                $check_column = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
                if ($check_column && $check_column->num_rows > 0) {
                    $stmt = $conn->prepare("DELETE FROM job_postings WHERE id=? AND company_id=?");
                    $stmt->bind_param("ii", $job_id, $company_id);
                } else {
                    $stmt = $conn->prepare("DELETE FROM job_postings WHERE id=? AND company=?");
                    $stmt->bind_param("is", $job_id, $company_name);
                }
                
                if ($stmt->execute()) {
                    $success_message = "Job posting deleted successfully!";
                    header("Location: jobposting.php?success=1");
                    exit();
                } else {
                    $error_message = "Error deleting job posting: " . $conn->error;
                }
                $stmt->close();
                break;
        }
    }
}

// Check for success message in URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Operation completed successfully!";
}

// Get company's job postings
$check_column = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
if ($check_column && $check_column->num_rows > 0) {
    $stmt = $conn->prepare("SELECT * FROM job_postings WHERE company_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $company_id);
} else {
    // Fallback: filter by company name
    $stmt = $conn->prepare("SELECT * FROM job_postings WHERE company = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $company_name);
}
$stmt->execute();
$job_postings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        
        .job-posting-page {
            padding: 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2rem;
            color: #1a3876;
            margin: 0 0 20px 0;
        }
        
        .job-form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .form-section-title {
            font-size: 1.5rem;
            color: #1a3876;
            font-weight: 600;
            margin: 0;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #1a3876;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .jobs-section {
            margin-top: 30px;
        }
        
        .form-toggle-btn {
            background: #6c757d;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .form-toggle-btn:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .job-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .job-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .job-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a3876;
            margin: 0 0 5px 0;
        }
        
        .job-card-company {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .job-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.85rem;
        }
        
        .job-card-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .job-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .job-card-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #007bff;
            color: white;
        }
        
        .btn-edit:hover {
            background: #0056b3;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-closed {
            background: #f8d7da;
            color: #721c24;
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
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a3876;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-primary {
            background: #1a3876;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #2c5aa0;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .form-collapsed {
            display: none;
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
            position: absolute;
            top: 70px;
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
            
            .job-posting-page {
                padding: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .add-job-btn {
                width: 100%;
                justify-content: center;
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
                    <i class="fas fa-building"></i>
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
                <li><a href="jobposting.php" class="active"><i class="fas fa-briefcase"></i> Job Posting</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="job-posting-page">
                <div class="page-header">
                    <h1 class="page-title">Job Postings</h1>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Job Posting Form -->
                <div class="job-form-section" id="jobFormSection">
                    <div class="form-section-header">
                        <h2 class="form-section-title" id="formTitle">Add New Job Posting</h2>
                        <button class="form-toggle-btn" onclick="toggleForm()" id="toggleFormBtn" style="display: none;">
                            <i class="fas fa-chevron-up"></i> Collapse
                        </button>
                    </div>
                    <form id="jobForm" method="POST">
                        <input type="hidden" name="action" id="formAction" value="add_job">
                        <input type="hidden" name="job_id" id="jobId" value="">
                        
                        <div class="form-group">
                            <label for="title">Job Title *</label>
                            <input type="text" id="title" name="title" required placeholder="e.g., Software Developer">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="location">Location *</label>
                                <input type="text" id="location" name="location" required placeholder="e.g., Manila, Quezon City">
                            </div>
                            <div class="form-group">
                                <label for="job_type">Job Type *</label>
                                <select id="job_type" name="job_type" required>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="salary_range">Salary Range</label>
                                <input type="text" id="salary_range" name="salary_range" placeholder="e.g., 25000-35000">
                            </div>
                            <div class="form-group">
                                <label for="industry">Industry</label>
                                <input type="text" id="industry" name="industry" placeholder="e.g., Technology, Healthcare">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Job Description *</label>
                            <textarea id="description" name="description" required placeholder="Describe the role, responsibilities, and what makes this opportunity special..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="requirements">Requirements *</label>
                            <textarea id="requirements" name="requirements" required placeholder="List the required qualifications, skills, and experience..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="Active">Active</option>
                                <option value="Draft">Draft</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="resetForm()" id="cancelBtn" style="display: none;">Cancel</button>
                            <button type="submit" class="btn-primary">Save Job Posting</button>
                        </div>
                    </form>
                </div>

                <!-- Job Listings Section -->
                <div class="jobs-section">
                    <h2 class="section-title">My Job Postings (<?php echo count($job_postings); ?>)</h2>
                    
                    <?php if (empty($job_postings)): ?>
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <h3>No Job Postings Yet</h3>
                            <p>Fill out the form above to create your first job posting.</p>
                        </div>
                    <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($job_postings as $job): ?>
                            <div class="job-card">
                                <div class="job-card-header">
                                    <div>
                                        <h3 class="job-card-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                        <p class="job-card-company"><?php echo htmlspecialchars($job['company']); ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo strtolower($job['status']); ?>">
                                        <?php echo htmlspecialchars($job['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="job-card-meta">
                                    <div class="job-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($job['location']); ?>
                                    </div>
                                    <div class="job-meta-item">
                                        <i class="fas fa-briefcase"></i>
                                        <?php echo htmlspecialchars($job['job_type']); ?>
                                    </div>
                                    <?php if ($job['salary_range']): ?>
                                        <div class="job-meta-item">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <?php echo htmlspecialchars($job['salary_range']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="job-card-description">
                                    <?php echo htmlspecialchars(substr($job['description'], 0, 150)); ?>...
                                </div>
                                
                                <div class="job-card-footer">
                                    <small style="color: #666;">
                                        <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                    </small>
                                    <div class="job-card-actions">
                                        <button class="btn-small btn-edit" onclick="editJob(<?php echo $job['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-small btn-delete" onclick="deleteJob(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allJobs = <?php echo json_encode($job_postings); ?>;
        
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

        function toggleForm() {
            const formSection = document.getElementById('jobFormSection');
            const formContent = formSection.querySelector('form');
            const toggleBtn = document.getElementById('toggleFormBtn');
            
            if (formContent.style.display === 'none') {
                formContent.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Collapse';
            } else {
                formContent.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Expand';
            }
        }

        function resetForm() {
            document.getElementById('formTitle').textContent = 'Add New Job Posting';
            document.getElementById('formAction').value = 'add_job';
            document.getElementById('jobId').value = '';
            document.getElementById('jobForm').reset();
            document.getElementById('status').value = 'Active';
            document.getElementById('cancelBtn').style.display = 'none';
            
            // Scroll to top of form
            document.getElementById('jobFormSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function editJob(jobId) {
            const job = allJobs.find(j => j.id == jobId);
            if (!job) return;
            
            document.getElementById('formTitle').textContent = 'Edit Job Posting';
            document.getElementById('formAction').value = 'update_job';
            document.getElementById('jobId').value = jobId;
            document.getElementById('cancelBtn').style.display = 'inline-block';
            
            // Populate form fields
            document.getElementById('title').value = job.title;
            document.getElementById('description').value = job.description;
            document.getElementById('requirements').value = job.requirements;
            document.getElementById('salary_range').value = job.salary_range || '';
            document.getElementById('location').value = job.location;
            document.getElementById('job_type').value = job.job_type;
            document.getElementById('industry').value = job.industry || '';
            document.getElementById('status').value = job.status;
            
            // Show form if collapsed
            const formContent = document.getElementById('jobFormSection').querySelector('form');
            formContent.style.display = 'block';
            document.getElementById('toggleFormBtn').innerHTML = '<i class="fas fa-chevron-up"></i> Collapse';
            
            // Scroll to form
            document.getElementById('jobFormSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function deleteJob(jobId, jobTitle) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete "${jobTitle}". This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_job">
                        <input type="hidden" name="job_id" value="${jobId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Handle form submission
        document.getElementById('jobForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const requirements = document.getElementById('requirements').value.trim();
            const location = document.getElementById('location').value.trim();
            
            if (!title || !description || !requirements || !location) {
                e.preventDefault();
                Swal.fire({
                    title: 'Missing Information',
                    text: 'Please fill in all required fields.',
                    icon: 'warning',
                    confirmButtonColor: '#1a3876'
                });
                return false;
            }
        });
    </script>
</body>
</html>

