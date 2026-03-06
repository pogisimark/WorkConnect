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

// Check which profile columns exist
$columns_check = $conn->query("SHOW COLUMNS FROM company_users");
$existing_columns = [];
if ($columns_check) {
    while ($row = $columns_check->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST['description'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Handle logo upload
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/company_logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = 'company_' . $company_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                $logo_path = 'assets/uploads/company_logos/' . $file_name;
                
                // Delete old logo if exists
                $stmt = $conn->prepare("SELECT logo FROM company_users WHERE id = ?");
                $stmt->bind_param("i", $company_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $old_logo = $result->fetch_assoc()['logo'] ?? null;
                $stmt->close();
                
                if ($old_logo && file_exists('../' . $old_logo)) {
                    unlink('../' . $old_logo);
                }
            } else {
                $error_message = "Error uploading logo file.";
            }
        } else {
            $error_message = "Invalid file type. Please upload JPG, PNG, GIF, or WEBP.";
        }
    }
    
    // Update company profile
    if (empty($error_message)) {
        // Build UPDATE query based on available columns
        $update_fields = [];
        $update_values = [];
        $update_types = '';
        
        if (in_array('description', $existing_columns)) {
            $update_fields[] = 'description = ?';
            $update_values[] = $description;
            $update_types .= 's';
        }
        if (in_array('website', $existing_columns)) {
            $update_fields[] = 'website = ?';
            $update_values[] = $website;
            $update_types .= 's';
        }
        if (in_array('address', $existing_columns)) {
            $update_fields[] = 'address = ?';
            $update_values[] = $address;
            $update_types .= 's';
        }
        if (in_array('phone', $existing_columns)) {
            $update_fields[] = 'phone = ?';
            $update_values[] = $phone;
            $update_types .= 's';
        }
        if ($logo_path && in_array('logo', $existing_columns)) {
            $update_fields[] = 'logo = ?';
            $update_values[] = $logo_path;
            $update_types .= 's';
        }
        
        if (!empty($update_fields)) {
            $update_values[] = $company_id;
            $update_types .= 'i';
            
            $update_query = "UPDATE company_users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param($update_types, ...$update_values);
        } else {
            // No profile columns exist yet - show message to run update script
            $error_message = "Profile fields not available. Please run the database update script first: <a href='update_company_profile_fields.php'>Update Database</a>";
            $stmt = null;
        }
        
        if ($stmt) {
            if ($stmt->execute()) {
                $success_message = "Company profile updated successfully!";
                // Update session company_name if changed
                if (isset($_POST['company_name']) && !empty($_POST['company_name'])) {
                    $_SESSION['company_name'] = trim($_POST['company_name']);
                }
                // Refresh page to show updated data
                header("Location: profile.php?success=1");
                exit();
            } else {
                $error_message = "Error updating profile: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Check for success message in URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Company profile updated successfully!";
}

// Fetch current company profile data
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
$company_description = (in_array('description', $existing_columns) && isset($company_profile['description'])) ? $company_profile['description'] : '';
$company_website = (in_array('website', $existing_columns) && isset($company_profile['website'])) ? $company_profile['website'] : '';
$company_address = (in_array('address', $existing_columns) && isset($company_profile['address'])) ? $company_profile['address'] : '';
$company_phone = (in_array('phone', $existing_columns) && isset($company_profile['phone'])) ? $company_profile['phone'] : '';

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/Company-sidebar.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        
        .profile-page {
            padding: 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2rem;
            color: #1a3876;
            margin: 0;
        }
        
        .profile-form {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 1200px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group input[type="tel"],
        .form-group textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="url"]:focus,
        .form-group input[type="tel"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a3876;
            box-shadow: 0 0 0 3px rgba(26, 56, 118, 0.1);
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .logo-upload-section {
            margin-bottom: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .logo-upload-section label {
            display: block;
            margin-bottom: 15px;
            color: #333;
            font-weight: 500;
            font-size: 1rem;
        }
        
        .logo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #1a3876;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .logo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            border: 4px solid #1a3876;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .logo-placeholder i {
            font-size: 3rem;
            color: #999;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #1a3876;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2c5aa0;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: opacity 0.5s ease-out;
        }
        
        .alert.fade-out {
            opacity: 0;
            height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .profile-form {
                padding: 20px;
            }
            
            .logo-upload-section {
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="jobposting.php"><i class="fas fa-briefcase"></i> Job Posting</a></li>
                <li><a href="view_applicants.php"><i class="fas fa-users"></i> View Applicants</a></li>
                <li><a href="referred.php"><i class="fas fa-user-check"></i> Referred</a></li>
                <li><a href="admin_requests.php"><i class="fas fa-envelope"></i> Admin Requests</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-building"></i> Company Profile</a></li>
                <li><a href="#" class="logout" onclick="showLogoutModal(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="profile-page">
                <div class="page-header">
                    <h1 class="page-title">Company Profile</h1>
                </div>

                <?php if ($success_message): ?>
                    <div id="successAlert" class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div id="errorAlert" class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-form">
                    <form method="POST" enctype="multipart/form-data" id="profileForm">
                        <div class="logo-upload-section">
                            <label>Company Logo</label>
                            <div style="display: flex; align-items: flex-start; gap: 30px; flex-wrap: wrap;">
                                <?php if ($company_logo): ?>
                                    <img src="../<?php echo htmlspecialchars($company_logo); ?>" alt="Company Logo" class="logo-preview" id="logoPreview">
                                <?php else: ?>
                                    <div class="logo-placeholder" id="logoPreview">
                                        <i class="fas fa-building"></i>
                                    </div>
                                <?php endif; ?>
                                <div style="flex: 1; min-width: 300px;">
                                    <input type="file" name="logo" id="logoInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="previewLogo(this)" style="padding: 10px; width: 100%; border: 2px dashed #ddd; border-radius: 8px; background: white; cursor: pointer;">
                                    <small style="color: #666; display: block; margin-top: 10px; font-size: 0.9rem;">Recommended: Square image, max 2MB (JPG, PNG, GIF, WEBP)</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_name">Company Name</label>
                                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                                <small style="color: #666; font-size: 0.85rem;">Company name cannot be changed</small>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="text" id="email" value="<?php echo htmlspecialchars($email); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                                <small style="color: #666; font-size: 0.85rem;">Email cannot be changed</small>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Company Description</label>
                            <textarea id="description" name="description" placeholder="Tell us about your company, mission, values, and what makes you unique..."><?php echo htmlspecialchars($company_description); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($company_website); ?>" placeholder="https://www.example.com">
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($company_phone); ?>" placeholder="+63 XXX XXX XXXX">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" placeholder="Enter your complete company address..."><?php echo htmlspecialchars($company_address); ?></textarea>
                        </div>

                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
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

        // Hamburger menu & slide-out sidebar (mobile)
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const sidebar = document.querySelector('.sidebar.desktop-nav');
            if (!hamburgerMenu || !sidebar) return;
            // Create backdrop for mobile (reliable click-to-close)
            let backdrop = document.getElementById('sidebarBackdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'sidebarBackdrop';
                backdrop.className = 'sidebar-backdrop';
                backdrop.setAttribute('aria-hidden', 'true');
                document.body.appendChild(backdrop);
            }
            function closeSidebar() {
                sidebar.classList.remove('active');
                hamburgerMenu.classList.remove('active');
                backdrop.classList.remove('active');
            }
            function openSidebar() {
                sidebar.classList.add('active');
                hamburgerMenu.classList.add('active');
                if (window.innerWidth <= 768) backdrop.classList.add('active');
            }
            function toggleSidebar() {
                if (sidebar.classList.contains('active')) closeSidebar();
                else openSidebar();
            }
            hamburgerMenu.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
            hamburgerMenu.addEventListener('touchend', function(e) {
                e.preventDefault();
                toggleSidebar();
            }, { passive: false });
            backdrop.addEventListener('click', closeSidebar);
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(e.target) && !hamburgerMenu.contains(e.target) && e.target !== backdrop) {
                        closeSidebar();
                    }
                }
            });
        });

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

        // Logo preview
        function previewLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('logoPreview');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Replace placeholder with image
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'logo-preview';
                        img.id = 'logoPreview';
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Auto-hide alerts after 5 seconds
        function autoHideAlerts() {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            if (successAlert) {
                setTimeout(function() {
                    successAlert.classList.add('fade-out');
                    setTimeout(function() {
                        successAlert.style.display = 'none';
                    }, 500);
                }, 5000);
            }
            
            if (errorAlert) {
                setTimeout(function() {
                    errorAlert.classList.add('fade-out');
                    setTimeout(function() {
                        errorAlert.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            autoHideAlerts();
        });
    </script>
</body>
</html>

