<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_check.php';
require_once 'db.php';

// Get company information
$company_id = $_SESSION['company_id'];
$company_name = $_SESSION['company_name'];
$email = $_SESSION['email'];

// Check if logo column exists and fetch company logo
$company_logo = null;
$columns_check = $conn->query("SHOW COLUMNS FROM company_users LIKE 'logo'");
if ($columns_check && $columns_check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT logo FROM company_users WHERE id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $company_profile = $result->fetch_assoc();
    $company_logo = $company_profile['logo'] ?? null;
    $stmt->close();
}

$success_message = '';
$error_message = '';

/** True when request expects JSON (fetch + X-Requested-With). */
function jobposting_is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strcasecmp(trim($_SERVER['HTTP_X_REQUESTED_WITH']), 'XMLHttpRequest') === 0;
}

function jobposting_json_response($success, $message, array $extra = []) {
    header('Content-Type: application/json; charset=utf-8');
    $payload = array_merge(['success' => (bool) $success, 'message' => (string) $message], $extra);
    echo json_encode($payload);
    exit;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $isAjax = jobposting_is_ajax_request();
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_job':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $requirements = trim($_POST['requirements'] ?? '');
                $salary_min = preg_replace('/[^0-9]/', '', $_POST['salary_min'] ?? '');
                $salary_max = preg_replace('/[^0-9]/', '', $_POST['salary_max'] ?? '');
                $salary_range = $salary_min && $salary_max ? $salary_min . '-' . $salary_max : trim($_POST['salary_range'] ?? '');
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
                    $stmt->close();
                    if ($isAjax) {
                        jobposting_json_response(true, 'Job posting created successfully!');
                    }
                    $success_message = "Job posting created successfully!";
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
                $salary_min = preg_replace('/[^0-9]/', '', $_POST['salary_min'] ?? '');
                $salary_max = preg_replace('/[^0-9]/', '', $_POST['salary_max'] ?? '');
                $salary_range = $salary_min && $salary_max ? $salary_min . '-' . $salary_max : trim($_POST['salary_range'] ?? '');
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
                    $stmt->close();
                    if ($isAjax) {
                        $job_payload = [
                            'id' => (int) $job_id,
                            'title' => $title,
                            'description' => $description,
                            'requirements' => $requirements,
                            'salary_range' => $salary_range,
                            'location' => $location,
                            'job_type' => $job_type,
                            'industry' => $industry,
                            'status' => $status,
                            'company' => $company_name,
                        ];
                        jobposting_json_response(true, 'Job posting updated successfully!', ['job' => $job_payload]);
                    }
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
                    if ($stmt->affected_rows < 1) {
                        $stmt->close();
                        $error_message = 'Job not found or already deleted.';
                        break;
                    }
                    $stmt->close();
                    if ($isAjax) {
                        jobposting_json_response(true, 'Job posting deleted successfully!');
                    }
                    $success_message = "Job posting deleted successfully!";
                    header("Location: jobposting.php?success=1");
                    exit();
                } else {
                    $error_message = "Error deleting job posting: " . $conn->error;
                }
                $stmt->close();
                break;

            case 'set_job_status':
                $job_id = (int) ($_POST['job_id'] ?? 0);
                $new_status = trim((string) ($_POST['new_status'] ?? ''));
                if ($job_id <= 0 || !in_array($new_status, ['Closed', 'Active'], true)) {
                    $error_message = 'Invalid job or status.';
                    break;
                }
                $check_column = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
                if ($check_column && $check_column->num_rows > 0) {
                    $stmt = $conn->prepare('UPDATE job_postings SET status = ? WHERE id = ? AND company_id = ?');
                    $stmt->bind_param('sii', $new_status, $job_id, $company_id);
                } else {
                    $stmt = $conn->prepare('UPDATE job_postings SET status = ? WHERE id = ? AND company = ?');
                    $stmt->bind_param('sis', $new_status, $job_id, $company_name);
                }
                if ($stmt && $stmt->execute()) {
                    if ($stmt->affected_rows < 1) {
                        $error_message = 'Job not found or you do not have permission to update it.';
                        $stmt->close();
                        break;
                    }
                    $stmt->close();
                    $status_msg = ($new_status === 'Closed')
                        ? 'Job posting closed successfully.'
                        : 'Job posting reopened successfully.';
                    if ($isAjax) {
                        jobposting_json_response(true, $status_msg);
                    }
                    header('Location: jobposting.php?success=1');
                    exit();
                }
                $error_message = 'Could not update job status. ' . ($conn->error ?: '');
                if ($stmt) {
                    $stmt->close();
                }
                break;
        }
        if ($isAjax && $error_message !== '') {
            jobposting_json_response(false, $error_message);
        }
    }
}

// Legacy success (e.g. non-AJAX delete) — show SweetAlert on load, not green banner
$flash_success_swal = (isset($_GET['success']) && $_GET['success'] == '1');

// Get company's job postings with analytics
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

// Get analytics for each job (applications count)
$job_analytics = [];
$app_table_check = $conn->query("SHOW TABLES LIKE 'job_applications_extended'");
if ($app_table_check && $app_table_check->num_rows > 0) {
    foreach ($job_postings as $job) {
        $job_id = $job['id'];
        // Count total applications
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM job_applications_extended WHERE job_posting_id = ?");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_apps = $result->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        
        // Count by status
        $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM job_applications_extended WHERE job_posting_id = ? GROUP BY status");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $status_counts = [];
        while ($row = $result->fetch_assoc()) {
            $status_counts[$row['status']] = $row['count'];
        }
        $stmt->close();
        
        $job_analytics[$job_id] = [
            'total_applications' => $total_apps,
            'status_counts' => $status_counts
        ];
    }
}

require_once __DIR__ . '/view_applicants_badge_helper.php';
$pending_applicants_sidebar_count = company_pending_applicants_count_for_sidebar($conn, $company_id);
require_once __DIR__ . '/referred_pending_badge_helper.php';
$referred_pending_sidebar_count = company_referred_pending_count_for_sidebar($conn, $company_id);
require_once __DIR__ . '/admin_requests_badge_helper.php';
$pending_admin_requests_count = company_admin_pending_request_count($conn, $company_id);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/Company-sidebar.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/company-logout.js?v=1"></script>
    <script src="../assets/js/employer-page-loading.js?v=<?php echo time(); ?>" defer></script>
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
        
        /* Wider cards: max 3 columns on large screens (avoids cramped 4-up layout) */
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;
            margin-bottom: 36px;
        }
        
        @media (max-width: 1200px) {
            .jobs-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 22px;
            }
        }
        
        @media (max-width: 700px) {
            .jobs-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .job-card {
            background: white;
            padding: 0;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(26, 56, 118, 0.08);
            border: 1px solid #e8eaf0;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            overflow: hidden;
        }
        
        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(26, 56, 118, 0.12);
        }
        
        .job-card--closed {
            opacity: 0.96;
            background: #fafbfc;
        }
        
        .job-card-inner {
            padding: 22px 24px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .job-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
        }
        
        .job-card-header > div:first-child {
            min-width: 0;
        }
        
        .job-card-title {
            font-size: 1.28rem;
            font-weight: 700;
            color: #1a3876;
            margin: 0 0 6px 0;
            line-height: 1.3;
            letter-spacing: -0.02em;
        }
        
        .job-card-company {
            color: #546e7a;
            font-size: 0.95rem;
            margin: 0;
            font-weight: 500;
        }
        
        .job-card-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 18px;
            padding: 14px 0;
            border-top: 1px solid #eef1f5;
            border-bottom: 1px solid #eef1f5;
        }
        
        .job-meta-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: #455a64;
            font-size: 0.92rem;
            line-height: 1.4;
        }
        
        .job-meta-item i {
            color: #5c6bc0;
            margin-top: 3px;
            width: 1rem;
            flex-shrink: 0;
        }
        
        .job-card-description {
            color: #37474f;
            font-size: 0.93rem;
            line-height: 1.58;
            margin: 0;
            flex: 1;
            min-height: 4.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .job-card-description.job-card-description--empty {
            color: #90a4ae;
            font-style: italic;
        }
        
        .job-card-footer {
            margin-top: auto;
            padding: 18px 22px 20px;
            border-top: 1px solid #e8eaf0;
            background: linear-gradient(180deg, #f9fafc 0%, #f4f6f9 100%);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .job-card-footer-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px 18px;
            font-size: 0.88rem;
            color: #455a64;
        }
        
        .job-card-footer-meta .job-stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #37474f;
            font-weight: 500;
        }
        
        .job-card-footer-meta .job-stat-pill i {
            color: #5c6bc0;
            opacity: 0.9;
        }
        
        .job-card-footer-meta .job-stat-pill--hired {
            color: #2e7d32;
            font-weight: 600;
        }
        
        .job-card-footer-meta .job-stat-pill--hired i {
            color: #43a047;
        }
        
        /* 2×2 action grid — equal width, no cramped single row */
        .job-card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 12px;
            width: 100%;
        }
        
        .btn-small {
            padding: 11px 12px;
            border: none;
            border-radius: 10px;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, transform 0.15s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            white-space: nowrap;
        }
        
        .btn-small:active {
            transform: scale(0.98);
        }
        
        .btn-view-details {
            background: #e3f2fd;
            color: #0d47a1;
            border: 1px solid #90caf9;
        }
        
        .btn-view-details:hover {
            background: #bbdefb;
        }
        
        .btn-edit {
            background: #1a3876;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2c4a9e;
        }
        
        .btn-close-job {
            background: #fff8e1;
            color: #e65100;
            border: 1px solid #ffcc80;
        }
        
        .btn-close-job:hover {
            background: #ffecb3;
        }
        
        .btn-reopen-job {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #a5d6a7;
        }
        
        .btn-reopen-job:hover {
            background: #c8e6c9;
        }
        
        .btn-delete {
            background: #fff;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .btn-delete:hover {
            background: #ffebee;
        }
        
        .status-badge {
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            flex-shrink: 0;
            line-height: 1;
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
        
        /* —— Job “View details” modal (SweetAlert2) —— */
        .jd-modal-popup {
            border-radius: 16px !important;
            padding: 0 !important;
            overflow: hidden;
            box-shadow: 0 24px 56px rgba(26, 56, 118, 0.22) !important;
            border: 1px solid #e2e8f0 !important;
        }
        .jd-modal-title {
            margin: 0 !important;
            padding: 22px 52px 18px 24px !important;
            font-size: 1.28rem !important;
            font-weight: 700 !important;
            color: #1a3876 !important;
            text-align: left !important;
            line-height: 1.35 !important;
            border-bottom: 1px solid #e8eaf0;
            background: linear-gradient(180deg, #fafbff 0%, #fff 100%);
        }
        .jd-modal-html {
            margin: 0 !important;
            padding: 0 !important;
            max-height: min(62vh, 480px);
            overflow-y: auto;
        }
        .jd-modal-html::-webkit-scrollbar {
            width: 8px;
        }
        .jd-modal-html::-webkit-scrollbar-thumb {
            background: #c5cae9;
            border-radius: 8px;
        }
        .swal2-popup.jd-modal-popup .swal2-close {
            width: 2.2rem !important;
            height: 2.2rem !important;
            border-radius: 10px !important;
            color: #546e7a !important;
            transition: background 0.2s, color 0.2s !important;
        }
        .swal2-popup.jd-modal-popup .swal2-close:hover {
            background: #eceff1 !important;
            color: #1a3876 !important;
        }
        .jd-modal-actions-wrap {
            margin: 0 !important;
            padding: 16px 24px 20px !important;
            background: #f4f6f9;
            border-top: 1px solid #e8eaf0;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .jd-modal-actions-wrap .swal2-confirm {
            border-radius: 10px !important;
            padding: 10px 32px !important;
            font-weight: 600 !important;
            font-size: 0.9rem !important;
            box-shadow: 0 2px 8px rgba(26, 56, 118, 0.25) !important;
        }
        .jd-modal-root {
            padding: 20px 24px 8px;
            text-align: left;
        }
        .jd-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 24px;
            margin-bottom: 22px;
        }
        @media (max-width: 560px) {
            .jd-meta-grid {
                grid-template-columns: 1fr;
            }
        }
        .jd-meta-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #78909c;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .jd-meta-value {
            font-size: 0.95rem;
            color: #263238;
            font-weight: 500;
            line-height: 1.35;
        }
        .jd-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .jd-status--active {
            background: #e8f5e9;
            color: #1b5e20;
        }
        .jd-status--closed {
            background: #ffebee;
            color: #b71c1c;
        }
        .jd-status--draft {
            background: #fff8e1;
            color: #e65100;
        }
        .jd-status--other {
            background: #eceff1;
            color: #455a64;
        }
        .jd-section {
            margin-bottom: 18px;
        }
        .jd-section:last-of-type {
            margin-bottom: 0;
        }
        .jd-section-head {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #1a3876;
            font-weight: 700;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .jd-section-head i {
            opacity: 0.85;
            font-size: 0.85rem;
        }
        .jd-section-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #37474f;
            border: 1px solid #e8eaf0;
        }
        .jd-req-list {
            margin: 0;
            padding-left: 1.15rem;
        }
        .jd-req-list li {
            margin-bottom: 8px;
            padding-left: 2px;
        }
        .jd-req-list li:last-child {
            margin-bottom: 0;
        }
        .jd-req-empty {
            list-style: none;
            margin-left: -1.15rem;
            color: #90a4ae;
            font-style: italic;
        }
        .jd-footer-date {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px dashed #cfd8dc;
            font-size: 0.82rem;
            color: #78909c;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .jd-footer-date i {
            color: #5c6bc0;
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

        /* TomSelect Custom Styles to match form */
        .ts-wrapper.form-control, .ts-control {
            border: 1px solid #ddd !important;
            border-radius: 6px !important;
            padding: 12px !important;
            font-size: 14px !important;
            box-shadow: none !important;
            transition: border-color 0.3s !important;
            height: auto !important;
            min-height: 45px !important;
            background-color: #fff !important;
        }
        
        .ts-wrapper.focus .ts-control {
            border-color: #1a3876 !important;
            outline: none !important;
        }
        
        .ts-dropdown {
            border-radius: 6px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
            z-index: 1000 !important;
        }

        .ts-control input {
            font-size: 14px !important;
        }
        
        /* COMPLETELY hide the original select elements */
        select.ts-hidden-accessible {
            display: none !important;
            visibility: hidden !important;
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            z-index: -1 !important;
        }

        /* Prevent double borders and fix alignment */
        .form-group select {
            display: none;
        }
        .form-group .ts-wrapper {
            display: block;
            width: 100%;
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
            height: calc(100vh - 80px); height: calc(100dvh - 80px - env(safe-area-inset-bottom, 0px)); max-height: calc(100dvh - 80px - env(safe-area-inset-bottom, 0px));
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
            min-height: calc(100vh - 80px); min-height: calc(100dvh - 80px - env(safe-area-inset-bottom, 0px));
        }
        
        @media (max-width: 768px) {
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
                <li><a href="jobposting.php" class="active"><i class="fas fa-briefcase"></i> Job Posting</a></li>
                <li><a href="view_applicants.php"><i class="fas fa-users"></i> View Applicants<?php echo company_pending_applicants_badge_html($pending_applicants_sidebar_count); ?></a></li>
                <li><a href="referred.php"><i class="fas fa-user-check"></i> Referred<?php echo company_referred_pending_badge_html($referred_pending_sidebar_count); ?></a></li>
                <li><a href="admin_requests.php"><i class="fas fa-envelope"></i> Admin Requests<?php echo company_admin_requests_badge_html($pending_admin_requests_count); ?></a></li>
                <li><a href="profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
                <li><a href="#" class="logout" onclick="showLogoutModal(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="job-posting-page">
                <div class="page-header">
                    <h1 class="page-title">Job Postings</h1>
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

                <?php if (!empty($flash_success_swal)): ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Operation completed successfully!',
                        confirmButtonColor: '#1a3876'
                    }).then(function() {
                        history.replaceState(null, '', 'jobposting.php');
                    });
                });
                </script>
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
                                <label for="job_province">Province *</label>
                                <select id="job_province" name="job_province" required>
                                    <option value="" selected disabled hidden>Select province</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="job_city">Municipality/City *</label>
                                <select id="job_city" name="job_city" required>
                                    <option value="" selected disabled hidden>Select municipality/city</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" id="location" name="location">
                        
                        <div class="form-group">
                            <label for="job_type">Job Type *</label>
                            <select id="job_type" name="job_type" required>
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Contract">Contract</option>
                                <option value="Internship">Internship</option>
                            </select>
                        </div>
                        
                        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Common configuration for TomSelect to match NSRP style
                                const commonConfig = {
                                    create: false,
                                    allowEmptyOption: true,
                                    closeAfterSelect: true,
                                    openOnFocus: true,
                                    maxOptions: 1000,
                                    onItemAdd: function() {
                                        this.close();
                                        this.setTextboxValue('');
                                        this.blur();
                                    },
                                    render: {
                                        option: function(data, escape) {
                                            return '<div>' + escape(data.name || data.text) + '</div>';
                                        },
                                        item: function(data, escape) {
                                            return '<div>' + escape(data.name || data.text) + '</div>';
                                        }
                                    }
                                };

                                // Initialize TomSelect for Province and City
                                const provinceSelect = new TomSelect('#job_province', {
                                    ...commonConfig,
                                    valueField: 'name',
                                    labelField: 'name',
                                    searchField: ['name'],
                                    placeholder: 'Select province',
                                    onChange: function(value) {
                                        updateCityDropdown(value);
                                        updateLocationHidden();
                                    }
                                });

                                const citySelect = new TomSelect('#job_city', {
                                    ...commonConfig,
                                    valueField: 'name',
                                    labelField: 'name',
                                    searchField: ['name'],
                                    placeholder: 'Select municipality/city',
                                    onChange: function(value) {
                                        updateLocationHidden();
                                    }
                                });

                                // Initialize TomSelect for Job Type and Status for consistency
                                new TomSelect('#job_type', { ...commonConfig, placeholder: 'Select job type' });
                                new TomSelect('#status', { ...commonConfig, placeholder: 'Select status' });

                                // PH Provinces list matching NSRP form
                                const PH_PROVINCES = [
                                    { code: '012800000', name: 'Ilocos Norte' },
                                    { code: '012900000', name: 'Ilocos Sur' },
                                    { code: '013300000', name: 'La Union' },
                                    { code: '015500000', name: 'Pangasinan' },
                                    { code: '020900000', name: 'Batanes' },
                                    { code: '021500000', name: 'Cagayan' },
                                    { code: '023100000', name: 'Isabela' },
                                    { code: '025000000', name: 'Nueva Vizcaya' },
                                    { code: '025700000', name: 'Quirino' },
                                    { code: '030800000', name: 'Bataan' },
                                    { code: '031400000', name: 'Bulacan' },
                                    { code: '034900000', name: 'Nueva Ecija' },
                                    { code: '035400000', name: 'Pampanga' },
                                    { code: '036900000', name: 'Tarlac' },
                                    { code: '037100000', name: 'Zambales' },
                                    { code: '037700000', name: 'Aurora' },
                                    { code: '041000000', name: 'Batangas' },
                                    { code: '042100000', name: 'Cavite' },
                                    { code: '043400000', name: 'Laguna' },
                                    { code: '045600000', name: 'Quezon' },
                                    { code: '045800000', name: 'Rizal' },
                                    { code: '174000000', name: 'Marinduque' },
                                    { code: '175100000', name: 'Occidental Mindoro' },
                                    { code: '175200000', name: 'Oriental Mindoro' },
                                    { code: '175300000', name: 'Palawan' },
                                    { code: '175900000', name: 'Romblon' },
                                    { code: '050500000', name: 'Albay' },
                                    { code: '051600000', name: 'Camarines Norte' },
                                    { code: '051700000', name: 'Camarines Sur' },
                                    { code: '052000000', name: 'Catanduanes' },
                                    { code: '054100000', name: 'Masbate' },
                                    { code: '056200000', name: 'Sorsogon' },
                                    { code: '060400000', name: 'Aklan' },
                                    { code: '060600000', name: 'Antique' },
                                    { code: '061900000', name: 'Capiz' },
                                    { code: '063000000', name: 'Iloilo' },
                                    { code: '064500000', name: 'Negros Occidental' },
                                    { code: '067900000', name: 'Guimaras' },
                                    { code: '071200000', name: 'Bohol' },
                                    { code: '072200000', name: 'Cebu' },
                                    { code: '074600000', name: 'Negros Oriental' },
                                    { code: '076100000', name: 'Siquijor' },
                                    { code: '082600000', name: 'Eastern Samar' },
                                    { code: '083700000', name: 'Leyte' },
                                    { code: '084800000', name: 'Northern Samar' },
                                    { code: '086000000', name: 'Samar' },
                                    { code: '086400000', name: 'Southern Leyte' },
                                    { code: '087800000', name: 'Biliran' },
                                    { code: '097200000', name: 'Zamboanga Del Norte' },
                                    { code: '097300000', name: 'Zamboanga Del Sur' },
                                    { code: '098300000', name: 'Zamboanga Sibugay' },
                                    { code: '101300000', name: 'Bukidnon' },
                                    { code: '101800000', name: 'Camiguin' },
                                    { code: '103500000', name: 'Lanao Del Norte' },
                                    { code: '104200000', name: 'Misamis Occidental' },
                                    { code: '104300000', name: 'Misamis Oriental' },
                                    { code: '112300000', name: 'Davao Del Norte' },
                                    { code: '112400000', name: 'Davao Del Sur' },
                                    { code: '112500000', name: 'Davao Oriental' },
                                    { code: '118200000', name: 'Davao De Oro' },
                                    { code: '118600000', name: 'Davao Occidental' },
                                    { code: '124700000', name: 'Cotabato' },
                                    { code: '126300000', name: 'South Cotabato' },
                                    { code: '126500000', name: 'Sultan Kudarat' },
                                    { code: '128000000', name: 'Sarangani' },
                                    { code: '140100000', name: 'Abra' },
                                    { code: '141100000', name: 'Benguet' },
                                    { code: '142700000', name: 'Ifugao' },
                                    { code: '143200000', name: 'Kalinga' },
                                    { code: '144400000', name: 'Mountain Province' },
                                    { code: '148100000', name: 'Apayao' },
                                    { code: '160200000', name: 'Agusan Del Norte' },
                                    { code: '160300000', name: 'Agusan Del Sur' },
                                    { code: '166700000', name: 'Surigao Del Norte' },
                                    { code: '166800000', name: 'Surigao Del Sur' },
                                    { code: '168500000', name: 'Dinagat Islands' },
                                    { code: '150700000', name: 'Basilan' },
                                    { code: '153600000', name: 'Lanao Del Sur' },
                                    { code: '153800000', name: 'Maguindanao' },
                                    { code: '156600000', name: 'Sulu' },
                                    { code: '157000000', name: 'Tawi-Tawi' },
                                    { code: '130000000', name: 'Metro Manila (NCR)' }
                                ];

                                // Load Provinces from local list instead of multiple API calls
                                const options = PH_PROVINCES.sort((a, b) => a.name.localeCompare(b.name))
                                    .map(p => ({ name: p.name, code: p.code }));
                                provinceSelect.addOptions(options);

                                function updateCityDropdown(provinceName) {
                                    citySelect.clear();
                                    citySelect.clearOptions();
                                    if (!provinceName) return;

                                    // Find province code from local list
                                    const province = PH_PROVINCES.find(p => p.name === provinceName);
                                    if (!province) return;

                                    const provinceCode = province.code;
                                    let url = '';
                                    if (provinceCode === '130000000') {
                                        // Special case for Metro Manila
                                        url = 'https://psgc.gitlab.io/api/regions/130000000/cities-municipalities/';
                                    } else {
                                        url = `https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`;
                                    }

                                    fetch(url)
                                        .then(res => res.json())
                                        .then(cities => {
                                            citySelect.addOptions(cities.sort((a, b) => a.name.localeCompare(b.name))
                                                .map(c => ({ name: c.name })));
                                        })
                                        .catch(err => {
                                            console.error('Error fetching cities:', err);
                                        });
                                }

                                function updateLocationHidden() {
                                    const province = provinceSelect.getValue();
                                    const city = citySelect.getValue();
                                    if (province && city) {
                                        document.getElementById('location').value = `${city}, ${province}`;
                                    } else {
                                        document.getElementById('location').value = '';
                                    }
                                }
                            });
                        </script>
                        
                        <div class="form-row">
                            <div class="form-group" style="display: flex; gap: 10px; align-items: flex-end;">
                                <div style="flex: 1;">
                                    <label for="salary_min">Minimum Salary (PHP) *</label>
                                    <input type="text" id="salary_min" name="salary_min" placeholder="e.g., 25000" required>
                                </div>
                                <div style="flex: 1;">
                                    <label for="salary_max">Maximum Salary (PHP) *</label>
                                    <input type="text" id="salary_max" name="salary_max" placeholder="e.g., 35000" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="industry">Industry</label>
                                <input type="text" id="industry" name="industry" placeholder="e.g., Technology, Healthcare">
                            </div>
                        </div>
                        <input type="hidden" id="salary_range" name="salary_range">
                        
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
                        <?php foreach ($job_postings as $job):
                            $job_status_key = strtolower((string) ($job['status'] ?? 'active'));
                            $raw_desc = trim((string) ($job['description'] ?? ''));
                            if ($raw_desc === '') {
                                $desc_class = 'job-card-description job-card-description--empty';
                                $desc_html = 'No description provided.';
                            } else {
                                $desc_class = 'job-card-description';
                                $desc_html = htmlspecialchars(strlen($raw_desc) > 150 ? substr($raw_desc, 0, 150) . '…' : $raw_desc);
                            }
                            $can_close = in_array($job_status_key, ['active', 'draft'], true);
                            $is_closed = ($job_status_key === 'closed');
                            ?>
                            <div class="job-card<?php echo $is_closed ? ' job-card--closed' : ''; ?>" data-job-id="<?php echo (int) $job['id']; ?>">
                                <div class="job-card-inner">
                                    <div class="job-card-header">
                                        <div>
                                            <h3 class="job-card-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                            <p class="job-card-company"><?php echo htmlspecialchars($job['company']); ?></p>
                                        </div>
                                        <span class="status-badge status-<?php echo htmlspecialchars($job_status_key); ?>">
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
                                        <?php if (!empty($job['salary_range'])): ?>
                                            <div class="job-meta-item">
                                                <i class="fas fa-money-bill-wave"></i>
                                                ₱<?php echo htmlspecialchars($job['salary_range']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="<?php echo $desc_class; ?>"><?php echo $desc_html; ?></p>
                                </div>
                                
                                <div class="job-card-footer">
                                    <div class="job-card-footer-meta">
                                        <span class="job-stat-pill">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                        </span>
                                        <?php if (isset($job_analytics[$job['id']])):
                                            $analytics = $job_analytics[$job['id']];
                                            if ($analytics['total_applications'] > 0): ?>
                                                <span class="job-stat-pill">
                                                    <i class="fas fa-users"></i>
                                                    <?php echo (int) $analytics['total_applications']; ?> application<?php echo $analytics['total_applications'] > 1 ? 's' : ''; ?>
                                                </span>
                                                <?php if (!empty($analytics['status_counts']['Accepted'])): ?>
                                                    <span class="job-stat-pill job-stat-pill--hired">
                                                        <i class="fas fa-check-circle"></i>
                                                        <?php echo (int) $analytics['status_counts']['Accepted']; ?> hired
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="job-stat-pill" style="color:#90a4ae;">
                                                    <i class="fas fa-inbox"></i> No applications yet
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="job-card-actions">
                                        <button type="button" class="btn-small btn-view-details" onclick="viewJobDetails(<?php echo (int) $job['id']; ?>)">
                                            <i class="fas fa-eye"></i> View details
                                        </button>
                                        <button type="button" class="btn-small btn-edit" onclick="editJob(<?php echo (int) $job['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($can_close): ?>
                                            <button type="button" class="btn-small btn-close-job" onclick="closeJobPosting(<?php echo (int) $job['id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-lock"></i> Close
                                            </button>
                                        <?php elseif ($is_closed): ?>
                                            <button type="button" class="btn-small btn-reopen-job" onclick="reopenJobPosting(<?php echo (int) $job['id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-redo-alt"></i> Reopen
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn-small btn-delete" onclick="deleteJob(<?php echo (int) $job['id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>')">
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

        function resetForm(options) {
            options = options || {};
            document.getElementById('formTitle').textContent = 'Add New Job Posting';
            document.getElementById('formAction').value = 'add_job';
            document.getElementById('jobId').value = '';
            document.getElementById('jobForm').reset();
            document.getElementById('status').value = 'Active';
            document.getElementById('cancelBtn').style.display = 'none';
            // Clear salary fields
            document.getElementById('salary_min').value = '';
            document.getElementById('salary_max').value = '';
            // Re-initialize formatting
            setTimeout(initializeSalaryFormatting, 100);
            
            if (!options.skipScroll) {
                document.getElementById('jobFormSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
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
            // Parse salary range and populate min/max fields
            if (job.salary_range) {
                const salaryParts = job.salary_range.split('-');
                if (salaryParts.length === 2) {
                    document.getElementById('salary_min').value = formatNumberWithCommas(salaryParts[0].trim());
                    document.getElementById('salary_max').value = formatNumberWithCommas(salaryParts[1].trim());
                } else {
                    document.getElementById('salary_min').value = '';
                    document.getElementById('salary_max').value = '';
                }
            } else {
                document.getElementById('salary_min').value = '';
                document.getElementById('salary_max').value = '';
            }
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
            // Re-initialize formatting
            setTimeout(initializeSalaryFormatting, 100);
        }

        async function submitDeleteFetch(jobId) {
            const fd = new FormData();
            fd.append('action', 'delete_job');
            fd.append('job_id', String(jobId));
            const res = await fetch('jobposting.php', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseErr) {
                throw new Error('Unexpected response from server. Please try again.');
            }
            if (!data.success) {
                throw new Error(data.message || 'Could not delete job posting.');
            }
            return data;
        }

        function removeJobFromPage(jobId) {
            const card = document.querySelector('.job-card[data-job-id="' + jobId + '"]');
            if (card) {
                card.remove();
            }
            const idx = allJobs.findIndex(function(j) {
                return Number(j.id) === Number(jobId);
            });
            if (idx !== -1) {
                allJobs.splice(idx, 1);
            }
            const titleEl = document.querySelector('.jobs-section .section-title');
            if (titleEl) {
                titleEl.textContent = 'My Job Postings (' + allJobs.length + ')';
            }
            const grid = document.querySelector('.jobs-grid');
            if (grid && grid.children.length === 0) {
                grid.remove();
                const section = document.querySelector('.jobs-section');
                if (section && !section.querySelector('.empty-state')) {
                    const empty = document.createElement('div');
                    empty.className = 'empty-state';
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-briefcase';
                    const h3 = document.createElement('h3');
                    h3.textContent = 'No Job Postings Yet';
                    const p = document.createElement('p');
                    p.textContent = 'Fill out the form above to create your first job posting.';
                    empty.appendChild(icon);
                    empty.appendChild(h3);
                    empty.appendChild(p);
                    section.appendChild(empty);
                }
            }
            const editingId = document.getElementById('jobId').value;
            if (editingId && Number(editingId) === Number(jobId)) {
                resetForm({ skipScroll: true });
            }
        }

        function deleteJob(jobId, jobTitle) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You are about to delete "' + jobTitle + '". This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: function() {
                    return submitDeleteFetch(jobId);
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) {
                    removeJobFromPage(jobId);
                    showJobStatusToast(result.value.message);
                }
            });
        }

        /** Fetch close/reopen; used inside Swal preConfirm so loader runs until the server responds. */
        async function submitJobStatusFetch(jobId, newStatus) {
            const fd = new FormData();
            fd.append('action', 'set_job_status');
            fd.append('job_id', String(jobId));
            fd.append('new_status', newStatus);
            const res = await fetch('jobposting.php', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseErr) {
                throw new Error('Unexpected response from server. Please try again.');
            }
            if (!data.success) {
                throw new Error(data.message || 'Could not update job status.');
            }
            return data;
        }

        /** Update card + allJobs after close/reopen — no full reload (avoids delay after success). */
        function updateJobCardInPlace(jobId, newStatus) {
            const card = document.querySelector('.job-card[data-job-id="' + jobId + '"]');
            if (!card) {
                return;
            }
            const titleEl = card.querySelector('.job-card-title');
            const title = titleEl ? titleEl.textContent.trim() : '';

            const badge = card.querySelector('.status-badge');
            if (badge) {
                const key = String(newStatus).toLowerCase();
                badge.className = 'status-badge status-' + key;
                badge.textContent = String(newStatus || '');
            }

            if (newStatus === 'Closed') {
                card.classList.add('job-card--closed');
            } else {
                card.classList.remove('job-card--closed');
            }

            const actions = card.querySelector('.job-card-actions');
            if (!actions) {
                return;
            }
            const delBtn = actions.querySelector('.btn-delete');
            const oldClose = actions.querySelector('.btn-close-job');
            const oldReopen = actions.querySelector('.btn-reopen-job');

            if (newStatus === 'Closed') {
                if (oldClose) {
                    oldClose.remove();
                }
                if (!actions.querySelector('.btn-reopen-job')) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn-small btn-reopen-job';
                    btn.innerHTML = '<i class="fas fa-redo-alt"></i> Reopen';
                    btn.addEventListener('click', function() {
                        reopenJobPosting(jobId, title);
                    });
                    actions.insertBefore(btn, delBtn);
                }
            } else {
                if (oldReopen) {
                    oldReopen.remove();
                }
                if (!actions.querySelector('.btn-close-job')) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn-small btn-close-job';
                    btn.innerHTML = '<i class="fas fa-lock"></i> Close';
                    btn.addEventListener('click', function() {
                        closeJobPosting(jobId, title);
                    });
                    actions.insertBefore(btn, delBtn);
                }
            }

            const j = allJobs.find(function(x) {
                return Number(x.id) === Number(jobId);
            });
            if (j) {
                j.status = newStatus;
            }
        }

        /** Append a meta row (icon + text) without innerHTML on user text. */
        function appendJobMetaRow(metaEl, iconClass, text) {
            const row = document.createElement('div');
            row.className = 'job-meta-item';
            const icon = document.createElement('i');
            icon.className = iconClass;
            row.appendChild(icon);
            row.appendChild(document.createTextNode(' ' + (text == null ? '' : String(text))));
            metaEl.appendChild(row);
        }

        /** After saving edit via AJAX: refresh card + allJobs, then status buttons. */
        function applyJobEditToCard(job) {
            if (!job || job.id == null) {
                return;
            }
            const id = Number(job.id);
            const card = document.querySelector('.job-card[data-job-id="' + id + '"]');
            if (!card) {
                return;
            }

            const titleEl = card.querySelector('.job-card-title');
            if (titleEl) {
                titleEl.textContent = job.title || '';
            }
            const compEl = card.querySelector('.job-card-company');
            if (compEl && job.company) {
                compEl.textContent = job.company;
            }

            const meta = card.querySelector('.job-card-meta');
            if (meta) {
                meta.textContent = '';
                appendJobMetaRow(meta, 'fas fa-map-marker-alt', job.location || '');
                appendJobMetaRow(meta, 'fas fa-briefcase', job.job_type || '');
                if (job.salary_range) {
                    const row = document.createElement('div');
                    row.className = 'job-meta-item';
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-money-bill-wave';
                    row.appendChild(icon);
                    row.appendChild(document.createTextNode(' ₱' + String(job.salary_range)));
                    meta.appendChild(row);
                }
            }

            const descP = card.querySelector('p.job-card-description');
            if (descP) {
                const raw = String(job.description || '').trim();
                if (!raw) {
                    descP.className = 'job-card-description job-card-description--empty';
                    descP.textContent = 'No description provided.';
                } else {
                    descP.className = 'job-card-description';
                    const snippet = raw.length > 150 ? raw.slice(0, 150) + '…' : raw;
                    descP.textContent = snippet;
                }
            }

            const j = allJobs.find(function(x) {
                return Number(x.id) === id;
            });
            if (j) {
                j.title = job.title;
                j.description = job.description;
                j.requirements = job.requirements;
                j.salary_range = job.salary_range;
                j.location = job.location;
                j.job_type = job.job_type;
                j.industry = job.industry;
                j.status = job.status;
                if (job.company) {
                    j.company = job.company;
                }
            }

            updateJobCardInPlace(id, job.status || 'Active');
        }

        function showJobStatusToast(message) {
            Swal.fire({
                icon: 'success',
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2800,
                timerProgressBar: true
            });
        }

        function closeJobPosting(jobId, jobTitle) {
            Swal.fire({
                title: 'Close this posting?',
                html: `“${escapeHtml(jobTitle)}” will no longer appear to jobseekers. You can reopen it later.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#e65100',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, close it',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: () => submitJobStatusFetch(jobId, 'Closed')
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    updateJobCardInPlace(jobId, 'Closed');
                    showJobStatusToast(result.value.message);
                }
            });
        }

        function reopenJobPosting(jobId, jobTitle) {
            Swal.fire({
                title: 'Reopen posting?',
                html: `“${escapeHtml(jobTitle)}” will be visible again as an active job.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2e7d32',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reopen',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: () => submitJobStatusFetch(jobId, 'Active')
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    updateJobCardInPlace(jobId, 'Active');
                    showJobStatusToast(result.value.message);
                }
            });
        }

        function escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text == null ? '' : String(text);
            return d.innerHTML;
        }

        function viewJobDetails(jobId) {
            const job = allJobs.find(j => Number(j.id) === Number(jobId));
            if (!job) {
                return;
            }

            function formatPosted(iso) {
                if (!iso) {
                    return '—';
                }
                const d = new Date(String(iso).replace(' ', 'T'));
                if (isNaN(d.getTime())) {
                    return escapeHtml(String(iso).replace('T', ' ').slice(0, 19));
                }
                return escapeHtml(d.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }));
            }

            function statusClass(st) {
                const s = String(st || '').toLowerCase();
                if (s === 'active') {
                    return 'jd-status jd-status--active';
                }
                if (s === 'closed') {
                    return 'jd-status jd-status--closed';
                }
                if (s === 'draft') {
                    return 'jd-status jd-status--draft';
                }
                return 'jd-status jd-status--other';
            }

            function requirementsHtml(text) {
                const raw = String(text || '').trim();
                if (!raw) {
                    return '<ul class="jd-req-list"><li class="jd-req-empty">No requirements listed.</li></ul>';
                }
                const lines = raw.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
                const items = lines.map(function (line) {
                    const t = line.replace(/^[\-\*•]\s*/, '').replace(/^\d+[\.)]\s*/, '');
                    return '<li>' + escapeHtml(t) + '</li>';
                }).join('');
                return '<ul class="jd-req-list">' + items + '</ul>';
            }

            const salaryVal = job.salary_range
                ? '₱' + escapeHtml(String(job.salary_range))
                : '<span style="color:#90a4ae;">—</span>';
            const industryVal = job.industry
                ? escapeHtml(String(job.industry))
                : '<span style="color:#90a4ae;">—</span>';
            const descRaw = (job.description || '').trim();
            const descBlock = descRaw
                ? escapeHtml(descRaw).replace(/\r?\n/g, '<br>')
                : '<span style="color:#90a4ae;font-style:italic;">No description provided.</span>';

            const html = ''
                + '<div class="jd-modal-root">'
                + '<div class="jd-meta-grid">'
                + '<div><div class="jd-meta-label">Company</div><div class="jd-meta-value">' + escapeHtml(job.company || '') + '</div></div>'
                + '<div><div class="jd-meta-label">Status</div><div class="jd-meta-value"><span class="' + statusClass(job.status) + '">' + escapeHtml(job.status || '—') + '</span></div></div>'
                + '<div><div class="jd-meta-label">Location</div><div class="jd-meta-value">' + escapeHtml(job.location || '') + '</div></div>'
                + '<div><div class="jd-meta-label">Job type</div><div class="jd-meta-value">' + escapeHtml(job.job_type || '') + '</div></div>'
                + '<div><div class="jd-meta-label">Salary</div><div class="jd-meta-value">' + salaryVal + '</div></div>'
                + '<div><div class="jd-meta-label">Industry</div><div class="jd-meta-value">' + industryVal + '</div></div>'
                + '</div>'
                + '<div class="jd-section">'
                + '<div class="jd-section-head"><i class="fas fa-align-left"></i> Description</div>'
                + '<div class="jd-section-box">' + descBlock + '</div>'
                + '</div>'
                + '<div class="jd-section">'
                + '<div class="jd-section-head"><i class="fas fa-clipboard-list"></i> Requirements</div>'
                + '<div class="jd-section-box">' + requirementsHtml(job.requirements) + '</div>'
                + '</div>'
                + '<div class="jd-footer-date"><i class="far fa-clock"></i><span>Posted <strong>' + formatPosted(job.created_at) + '</strong></span></div>'
                + '</div>';

            Swal.fire({
                title: job.title || 'Job posting',
                html: html,
                showCloseButton: true,
                width: 'min(92vw, 720px)',
                padding: 0,
                focusConfirm: false,
                customClass: {
                    popup: 'jd-modal-popup',
                    title: 'jd-modal-title',
                    htmlContainer: 'jd-modal-html',
                    actions: 'jd-modal-actions-wrap',
                    confirmButton: 'jd-modal-confirm'
                },
                confirmButtonText: 'Close',
                confirmButtonColor: '#1a3876'
            });
        }

        // Format number with commas (e.g., 1000 -> 1,000)
        function formatNumberWithCommas(value) {
            if (!value) return '';
            // Remove all non-numeric characters
            const numbers = value.replace(/[^0-9]/g, '');
            if (!numbers) return '';
            // Add commas every 3 digits
            return numbers.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        // Remove commas from number string
        function removeCommas(value) {
            return value.replace(/,/g, '');
        }
        
        // Initialize salary input formatting
        function initializeSalaryFormatting() {
            const salaryMinInput = document.getElementById('salary_min');
            const salaryMaxInput = document.getElementById('salary_max');
            
            if (salaryMinInput && !salaryMinInput.hasAttribute('data-initialized')) {
                salaryMinInput.setAttribute('data-initialized', 'true');
                
                // Format on input
                salaryMinInput.addEventListener('input', function(e) {
                    const cursorPos = this.selectionStart;
                    const oldValue = this.value;
                    const newValue = formatNumberWithCommas(this.value);
                    
                    if (oldValue !== newValue) {
                        this.value = newValue;
                        // Adjust cursor position after formatting
                        const diff = newValue.length - oldValue.length;
                        this.setSelectionRange(cursorPos + diff, cursorPos + diff);
                    }
                });
                
                // Prevent non-numeric characters on paste
                salaryMinInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    const numbers = pastedText.replace(/[^0-9]/g, '');
                    this.value = formatNumberWithCommas(numbers);
                });
            }
            
            if (salaryMaxInput && !salaryMaxInput.hasAttribute('data-initialized')) {
                salaryMaxInput.setAttribute('data-initialized', 'true');
                
                // Format on input
                salaryMaxInput.addEventListener('input', function(e) {
                    const cursorPos = this.selectionStart;
                    const oldValue = this.value;
                    const newValue = formatNumberWithCommas(this.value);
                    
                    if (oldValue !== newValue) {
                        this.value = newValue;
                        // Adjust cursor position after formatting
                        const diff = newValue.length - oldValue.length;
                        this.setSelectionRange(cursorPos + diff, cursorPos + diff);
                    }
                });
                
                // Prevent non-numeric characters on paste
                salaryMaxInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    const numbers = pastedText.replace(/[^0-9]/g, '');
                    this.value = formatNumberWithCommas(numbers);
                });
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
            // After close/reopen reload: restore scroll position (avoid jump to top)
            try {
                const saved = sessionStorage.getItem('wc_jobposting_scroll');
                if (saved !== null) {
                    sessionStorage.removeItem('wc_jobposting_scroll');
                    const y = parseInt(saved, 10);
                    if (!Number.isNaN(y) && y >= 0) {
                        const applyScroll = function() {
                            window.scrollTo(0, y);
                        };
                        applyScroll();
                        requestAnimationFrame(function() {
                            applyScroll();
                            requestAnimationFrame(applyScroll);
                        });
                        window.addEventListener('load', applyScroll, { once: true });
                    }
                }
            } catch (e) { /* ignore */ }

            initializeSalaryFormatting();
            autoHideAlerts();
            
            // Hamburger menu & slide-out sidebar (mobile)
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
        
        // Handle form submission (AJAX + loading + SweetAlert success)
        document.getElementById('jobForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const requirements = document.getElementById('requirements').value.trim();
            const salaryMin = document.getElementById('salary_min').value.trim();
            const salaryMax = document.getElementById('salary_max').value.trim();
            const location = document.getElementById('location').value.trim();
            
            if (!title || !description || !requirements || !location) {
                Swal.fire({
                    title: 'Missing Information',
                    text: 'Please fill in all required fields.',
                    icon: 'warning',
                    confirmButtonColor: '#1a3876'
                });
                return;
            }
            
            if (!salaryMin || !salaryMax) {
                Swal.fire({
                    title: 'Missing Salary Information',
                    text: 'Please fill in both minimum and maximum salary.',
                    icon: 'warning',
                    confirmButtonColor: '#1a3876'
                });
                return;
            }
            
            const minValue = removeCommas(salaryMin);
            const maxValue = removeCommas(salaryMax);
            
            if (parseInt(minValue, 10) > parseInt(maxValue, 10)) {
                Swal.fire({
                    title: 'Invalid Salary Range',
                    text: 'Minimum salary cannot be greater than maximum salary.',
                    icon: 'warning',
                    confirmButtonColor: '#1a3876'
                });
                return;
            }
            
            document.getElementById('salary_range').value = minValue + '-' + maxValue;
            
            const form = this;
            const fd = new FormData(form);
            const isEdit = document.getElementById('formAction').value === 'update_job';
            Swal.fire({
                title: isEdit ? 'Saving changes…' : 'Creating posting…',
                text: 'Please wait…',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            try {
                const res = await fetch('jobposting.php', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Unexpected response from server. Please try again.',
                        confirmButtonColor: '#1a3876'
                    });
                    return;
                }
                Swal.close();
                if (data.success) {
                    if (isEdit && data.job) {
                        applyJobEditToCard(data.job);
                        showJobStatusToast(data.message);
                        resetForm({ skipScroll: true });
                    } else {
                        showJobStatusToast(data.message);
                        window.setTimeout(function() {
                            window.location.href = 'jobposting.php';
                        }, 2200);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Something went wrong.',
                        confirmButtonColor: '#1a3876'
                    });
                }
            } catch (err) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not reach the server. Please try again.',
                    confirmButtonColor: '#1a3876'
                });
            }
        });
    </script>
</body>
</html>

