<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_protect.php';
require_once 'db.php';

// Get job_id from query parameter if provided
$job_id_filter = isset($_GET['job_id']) ? intval($_GET['job_id']) : null;

// Get all job postings (or specific job if job_id is provided)
$jobs = [];
$job_applicants = [];

$table_check = $conn->query("SHOW TABLES LIKE 'job_postings'");
if ($table_check && $table_check->num_rows > 0) {
    // Get jobs - all jobs for employer, or specific job if job_id is provided
    if ($job_id_filter) {
        $stmt = $conn->prepare("SELECT id, title, description, requirements, salary_range, location, job_type, industry, status, created_at, company FROM job_postings WHERE id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $job_id_filter);
    } else {
        $stmt = $conn->prepare("SELECT id, title, description, requirements, salary_range, location, job_type, industry, status, created_at, company FROM job_postings ORDER BY created_at DESC");
    }
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

require_once __DIR__ . '/follow_up_pending_badge.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';
require_once __DIR__ . '/jobseeker_pending_badge.php';
$follow_up_pending_count = fu_get_pending_follow_up_count($conn);
$acfu_unread_count = acfu_get_unread_response_count($conn);
$pending_jobseekers_count = js_get_pending_jobseekers_count($conn);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applicants - WorkConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fafafa;
            min-height: 100vh; min-height: 100dvh;
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
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(35,58,139,0.10);
        }
        
        .header img {
            height: 48px;
            margin-right: 16px;
            border-radius: 50%;
        }
        
        .header-title {
            font-size: 1.7rem;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        
        .layout {
            display: flex;
            min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
            padding-top: 64px;
        }
        
        .sidebar {
            background: #e3eaff;
            width: 240px;
            height: calc(100vh - 64px); height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px)); max-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
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
            min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
            overflow-y: auto;
            box-sizing: border-box;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #233a8b;
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
            color: #233a8b;
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
            border-left: 4px solid #233a8b;
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
            color: #233a8b;
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
        }
        
        .btn-view {
            background: #233a8b;
            color: white;
        }
        
        .btn-view:hover {
            background: #1a2d6b;
        }
        
        .btn-resume {
            background: #1976d2;
            color: white;
        }
        
        .btn-resume:hover {
            background: #1565c0;
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
            color: #233a8b;
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
            color: #233a8b;
            margin: 0 0 15px 0;
            font-size: 1.2rem;
            border-bottom: 2px solid #233a8b;
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
            .job-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .applicant-header {
                flex-direction: column;
                gap: 10px;
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
    <link rel="stylesheet" href="../assets/css/Employer-sidebar-neat.css?v=<?php echo time(); ?>">
    <script src="../assets/js/employer-page-loading.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
<div class="header">
        <div style="display: flex; align-items: center;">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
        </div>
    </div>

    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php">📊 DASHBOARD</a>
            <a href="job_postings.php">💼 JOB POSTINGS</a>
            <a href="job.php">👥 JOBSEEKERS<?php echo js_pending_jobseekers_badge_html($pending_jobseekers_count); ?></a>
            <a href="follow_up_requests.php">📩 FOLLOW-UP REQUESTS<?php echo fu_follow_up_badge_html($follow_up_pending_count); ?></a>
            <a href="request_follow_up.php"> REQUEST FOLLOW UP<span class="acfu-sidebar-badge"><?php echo acfu_unread_badge_html($acfu_unread_count); ?></span></a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="companies_list.php">🏢 COMPANIES</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;">➕ ADD ACCOUNT</a>
            <a href="analytics.php">📊 Analytics</a>
            <a href="announcement.php">📢 ANNOUNCEMENTS</a>
            <a href="logout.php">🚪 Logout</a>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Job Applicants</h1>
                <p>Review applications and jobseeker profiles for job postings</p>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Job Postings Found</h3>
                    <p>There are no job postings available at this time.</p>
                    <a href="job_postings.php" class="btn btn-view" style="margin-top: 20px; display: inline-block; text-decoration: none;">View Job Postings</a>
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
                                        <i class="fas fa-building"></i>
                                        <span><?php echo htmlspecialchars($job['company'] ?? 'N/A'); ?></span>
                                    </div>
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

    <script>
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
            if (event.target == detailsModal) {
                detailsModal.style.display = 'none';
            }
            if (event.target == resumeModal) {
                resumeModal.style.display = 'none';
            }
        };

        fetch('session_check.php').then(function(r) { return r.json(); }).then(function(d) {
            var a = document.getElementById('addAccountLink');
            if (a) { a.style.display = d.isMainAdmin ? 'block' : 'none'; }
        }).catch(function() {});
    </script>
</body>
</html>
