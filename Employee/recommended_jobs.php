<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Recommended Jobs page for Employee Dashboard
require_once 'session_check.php';
require_once 'db.php';
require_once 'job_matching_algorithm.php';
require_once __DIR__ . '/../Employer/job_applications_withdraw_helper.php';
require_once __DIR__ . '/../jobseeker_placement_helper.php';

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
workconnect_ensure_jobseeker_placement_columns($conn);
$matching = new JobMatchingAlgorithm($conn);

/** Show NSRP preference in UI; keep raw value in DB (hide placeholders like n/a). */
function workconnect_nrsp_show_preference($value): bool {
    if ($value === null) {
        return false;
    }
    $t = strtolower(trim((string) $value));
    return $t !== '' && $t !== 'n/a' && $t !== 'na';
}

// Get minimum score from query parameter or use default 50%
$minScore = isset($_GET['min_score']) ? (int)$_GET['min_score'] : 50;

$success_message = null;
$error_message = null;

// Handle job application (before heavy page queries; supports AJAX for SweetAlert flow)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'apply_job') {
    $isAjaxApply = !empty($_POST['ajax_apply']);
    $jobId = (int)$_POST['job_id'];

    $stmt = $conn->prepare("SELECT id FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $jobseeker = $result->fetch_assoc();
    $stmt->close();

    if ($jobseeker) {
        $jobseekerId = $jobseeker['id'];

        $stJs = $conn->prepare("SELECT application_status, placement_active FROM jobseeker WHERE id = ?");
        $stJs->bind_param("i", $jobseekerId);
        $stJs->execute();
        $jsStatus = $stJs->get_result()->fetch_assoc();
        $stJs->close();
        if ($jsStatus && workconnect_jobseeker_is_actively_placed($jsStatus)) {
            if ($isAjaxApply) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'You have already been accepted for employment. Other job applications are no longer active.']);
                $conn->close();
                exit;
            }
            $error_message = 'You have already been accepted for employment. Other job applications are no longer active.';
        }

        if (!$error_message) {
            $stmt = $conn->prepare("SELECT id, status FROM job_applications_extended WHERE jobseeker_id = ? AND job_posting_id = ? ORDER BY applied_date DESC, id DESC LIMIT 1");
            $stmt->bind_param("ii", $jobseekerId, $jobId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $compatibility_score = $matching->calculateCompatibilityScore($userId, $jobId);

            if (!$existing) {
                $stmt = $conn->prepare("INSERT INTO job_applications_extended (jobseeker_id, job_posting_id, status, compatibility_score) VALUES (?, ?, 'Applied', ?)");
                $stmt->bind_param("iid", $jobseekerId, $jobId, $compatibility_score);
                if ($stmt->execute()) {
                    $success_message = "Application submitted successfully!";
                } else {
                    $error_message = "Error submitting application: " . $conn->error;
                }
                $stmt->close();
            } elseif (isset($existing['status']) && strcasecmp($existing['status'], 'Rejected') === 0) {
                $stmt = $conn->prepare("UPDATE job_applications_extended SET status = 'Applied', compatibility_score = ?, applied_date = NOW(), notes = NULL, viewed_date = NULL WHERE id = ?");
                $stmt->bind_param("di", $compatibility_score, $existing['id']);
                if ($stmt->execute()) {
                    $success_message = "Application submitted successfully!";
                } else {
                    $error_message = "Error submitting application: " . $conn->error;
                }
                $stmt->close();
            } elseif (isset($existing['status']) && strcasecmp($existing['status'], 'Closed') === 0) {
                $error_message = 'This application is closed (placement ended). You cannot apply again to this job on this record.';
            } elseif (
                isset($existing['status'])
                && !workconnect_jobseeker_is_actively_placed($jsStatus)
                && (
                    strcasecmp($existing['status'], 'Withdrawn') === 0
                    || strcasecmp($existing['status'], 'Accepted') === 0
                )
            ) {
                $stmt = $conn->prepare("UPDATE job_applications_extended SET status = 'Applied', compatibility_score = ?, applied_date = NOW(), notes = NULL, viewed_date = NULL WHERE id = ?");
                $stmt->bind_param("di", $compatibility_score, $existing['id']);
                if ($stmt->execute()) {
                    $success_message = "Application submitted successfully!";
                } else {
                    $error_message = "Error submitting application: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error_message = "You have already applied for this job.";
            }
        }
    } else {
        $error_message = "We couldn't find your profile. Please complete your NSRP registration first.";
    }

    if ($isAjaxApply) {
        header('Content-Type: application/json; charset=utf-8');
        if ($success_message) {
            echo json_encode(['success' => true, 'message' => $success_message]);
        } else {
            echo json_encode(['success' => false, 'message' => $error_message ?: 'Unable to submit application.']);
        }
        $conn->close();
        exit;
    }
}

// Align job_applications_extended with jobseeker Accepted (fixes stale "Applied" after referral accept)
$jsRecon = $conn->prepare("SELECT id FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$jsRecon->bind_param("i", $userId);
$jsRecon->execute();
$jsRow = $jsRecon->get_result()->fetch_assoc();
$jsRecon->close();
if ($jsRow && !empty($jsRow['id'])) {
    reconcile_stale_open_applications_for_accepted_jobseeker($conn, (int) $jsRow['id']);
}

// Hosting often uses 30s; recommendation scoring is CPU-heavy at scale.
@set_time_limit(120);

// Get recommended jobs (only jobs with compatibility >= minScore)
$recommendations = $matching->getRecommendedJobs($userId, 20, $minScore);

// Get user preferences for display
$stmt = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$preferences = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get NRSP form data from jobseeker table (including all skill fields) + application_status (blocks Apply on new jobs when already Accepted)
$stmt = $conn->prepare("SELECT occupation1, occupation2, occupation3, fulltime, parttime, local1, local2, local3, 
    training_skills_1, training_skills_2, training_skills_3, skill_others,
    skill_auto_mechanic, skill_electrician, skill_photography, skill_beautician, skill_embroidery, 
    skill_plumbing, skill_carpentry, skill_gardening, skill_sewing, skill_computer, skill_masonry, 
    skill_stenography, skill_domestic, skill_painter, skill_tailoring, skill_driver, skill_painting,
    application_status, placement_active
    FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$nrspData = $stmt->get_result()->fetch_assoc();
$stmt->close();

/** When actively placed (Accepted + placement), user cannot apply — stats must not count blank rows as "open". */
$globallyAcceptedForStats = is_array($nrspData) && workconnect_jobseeker_is_actively_placed($nrspData);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommended Jobs - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Render alerts on the parent dashboard when loaded in iframe.
        (function () {
            if (window.self === window.top) return;
            const localFire = Swal.fire.bind(Swal);
            Swal.fire = function () {
                try {
                    if (window.top && typeof window.top.showGlobalSwal === 'function') {
                        return window.top.showGlobalSwal.apply(window.top, arguments);
                    }
                } catch (e) {
                    // Fall back to local modal when parent access is unavailable.
                }
                return localFire.apply(Swal, arguments);
            };
            // showGlobalSwal opens the modal on the parent, but didOpen callbacks still called
            // from the iframe must use the parent's Swal for loading/close or the spinner
            // appears trapped inside the iframe.
            const topSwal = function () {
                try {
                    if (window.top && window.top.Swal) return window.top.Swal;
                } catch (e) { /* cross-origin */ }
                return null;
            };
            const localShowLoading = Swal.showLoading.bind(Swal);
            Swal.showLoading = function () {
                const T = topSwal();
                if (T && typeof T.showLoading === 'function') {
                    return T.showLoading.apply(T, arguments);
                }
                return localShowLoading.apply(Swal, arguments);
            };
            const localHideLoading = Swal.hideLoading && Swal.hideLoading.bind(Swal);
            Swal.hideLoading = function () {
                const T = topSwal();
                if (T && typeof T.hideLoading === 'function') {
                    return T.hideLoading.apply(T, arguments);
                }
                return localHideLoading ? localHideLoading.apply(Swal, arguments) : undefined;
            };
            const localClose = Swal.close.bind(Swal);
            Swal.close = function () {
                const T = topSwal();
                if (T && typeof T.close === 'function') {
                    return T.close.apply(T, arguments);
                }
                return localClose.apply(Swal, arguments);
            };
        })();
    </script>
    <style>
        .stats-summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #233a8b;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .job-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            /* Fill grid row height so actions can pin to bottom */
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .job-card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            position: relative;
        }
        
        .job-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #233a8b;
            margin: 0 0 10px 0;
            line-height: 1.3;
        }
        
        .company-name {
            font-size: 1.1rem;
            color: #666;
            margin: 0 0 15px 0;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .compatibility-score {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .compatibility-score.medium {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .compatibility-score.low {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        
        .job-card-body {
            padding: 20px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .job-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .job-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .job-requirements h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 0.9rem;
        }
        
        .job-requirements p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
            line-height: 1.4;
        }
        
        .job-actions {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            flex-shrink: 0;
            padding-top: 4px;
        }
        
        /* Status pills stay compact (Apply form uses flex:1; badges must not stretch full width) */
        .job-actions > .applied-badge,
        .job-actions > .rejected-badge,
        .job-actions > .accepted-badge,
        .job-actions > .closed-badge,
        .job-actions > .withdrawn-badge,
        .job-actions > .not-eligible-badge {
            flex: 0 0 auto;
            width: fit-content;
            max-width: 100%;
        }
        
        .btn-apply {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            flex: 1;
            transition: all 0.3s;
        }
        
        .btn-apply:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-2px);
        }
        
        .btn-view {
            background: #233a8b;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-view:hover {
            background: #1a2d6b;
        }
        
        .applied-badge {
            background: #6c757d;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .rejected-badge {
            background: linear-gradient(135deg, #c62828 0%, #b71c1c 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .accepted-badge {
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .closed-badge {
            background: linear-gradient(135deg, #546e7a 0%, #37474f 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .withdrawn-badge {
            background: #eceff1;
            color: #455a64;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            border: 1px solid #cfd8dc;
        }
        
        .btn-apply-disabled,
        .not-eligible-badge {
            background: #9e9e9e !important;
            color: #fff !important;
            cursor: not-allowed !important;
            opacity: 0.92;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: bold;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }
        
        .no-jobs {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-jobs i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-jobs h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .no-jobs p {
            color: #999;
            margin-bottom: 20px;
        }
        
        .nrsp-info-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 4px solid #233a8b;
            box-sizing: border-box;
            max-width: 100%;
            min-width: 0;
            overflow-x: hidden;
        }
        
        .nrsp-info-card h3 {
            margin: 0 0 10px 0;
            color: #233a8b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nrsp-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr));
            gap: 20px;
            margin-top: 20px;
            min-width: 0;
        }
        
        .nrsp-info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .nrsp-info-section h4 {
            margin: 0 0 12px 0;
            color: #333;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nrsp-info-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 0;
            align-content: flex-start;
        }
        
        .nrsp-badge {
            background: #233a8b;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            max-width: 100%;
            box-sizing: border-box;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            text-align: center;
            line-height: 1.35;
        }
        
        .nrsp-badge.skill {
            background: #28a745;
        }
        
        .nrsp-badge.empty {
            background: #6c757d;
            font-style: italic;
        }
        
        .nrsp-badge.ai-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            position: relative;
        }
        
        .nrsp-badge.ai-badge i {
            margin-left: 5px;
            font-size: 0.8rem;
        }
        
        .btn-update-nrsp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #233a8b;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-update-nrsp:hover {
            background: #1a2d6b;
            transform: translateY(-2px);
        }
        
        .match-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #233a8b;
        }
        
        .match-breakdown h4 {
            margin: 0 0 15px 0;
            color: #233a8b;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .breakdown-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }
        
        .breakdown-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .breakdown-score {
            font-size: 1.2rem;
            font-weight: bold;
            color: #233a8b;
            margin-bottom: 5px;
        }
        
        .breakdown-details {
            font-size: 0.75rem;
            color: #888;
            line-height: 1.4;
        }
        
        .breakdown-details small {
            display: block;
            margin-top: 3px;
            color: #28a745;
            font-weight: 500;
        }
        
        .job-details-modal .swal2-popup {
            max-width: 800px !important;
            padding: 0 !important;
        }
        
        .job-details-content {
            text-align: left !important;
            padding: 20px !important;
        }
        
        /* Apply Confirmation Modal Styles */
        .apply-confirm-modal .swal2-popup {
            border-radius: 15px !important;
            padding: 30px !important;
        }
        
        .apply-confirm-modal .swal2-title {
            font-size: 24px !important;
            font-weight: 600 !important;
            color: #233a8b !important;
            margin-bottom: 10px !important;
        }
        
        .apply-confirm-modal .swal2-html-container {
            margin: 20px 0 !important;
            padding: 0 !important;
        }
        
        .swal2-confirm-apply {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            border: none !important;
            padding: 12px 30px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            transition: all 0.3s !important;
        }
        
        .swal2-confirm-apply:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4) !important;
        }
        
        .swal2-cancel-apply {
            background: #6c757d !important;
            border: none !important;
            padding: 12px 30px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            transition: all 0.3s !important;
        }
        
        .swal2-cancel-apply:hover {
            background: #5a6268 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4) !important;
        }
        
        /* In dashboard iframe: do not tie body min-height to iframe viewport (causes scrollHeight to collapse on mobile Chrome after resize). */
        html.wc-in-iframe,
        body.wc-in-iframe {
            min-height: 0 !important;
            height: auto !important;
            overflow-x: hidden;
        }

        @media (max-width: 768px) {
            /* Two job cards per row; breakdown stays stacked so all fields stay readable */
            .jobs-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 20px;
            }
            .job-card {
                min-width: 0;
            }
            .job-card-header {
                padding: 10px 10px 12px;
            }
            .compatibility-score {
                position: static;
                display: inline-flex;
                align-items: center;
                margin: 0 0 8px 0;
                font-size: 0.72rem;
                padding: 5px 8px;
            }
            .job-title {
                font-size: 0.82rem;
                margin: 0 0 6px 0;
                line-height: 1.25;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            .company-name {
                font-size: 0.72rem;
                margin: 0 0 8px 0;
                line-height: 1.3;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            .job-meta {
                flex-direction: column;
                gap: 5px;
                font-size: 0.68rem;
            }
            .job-meta-item {
                gap: 4px;
                line-height: 1.3;
                word-break: break-word;
            }
            .job-card-body {
                padding: 10px;
            }
            .job-description {
                font-size: 0.72rem;
                line-height: 1.45;
                margin-bottom: 10px;
                -webkit-line-clamp: 8;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            .match-breakdown {
                padding: 10px 8px;
                margin-bottom: 12px;
            }
            .match-breakdown h4 {
                font-size: 0.78rem;
                margin-bottom: 8px;
                line-height: 1.3;
                flex-wrap: wrap;
                gap: 4px;
            }
            .breakdown-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            .breakdown-item {
                padding: 8px 6px;
                min-width: 0;
            }
            .breakdown-label {
                font-size: 0.68rem;
                flex-wrap: wrap;
                gap: 4px;
            }
            .breakdown-score {
                font-size: 0.95rem;
            }
            .breakdown-details {
                font-size: 0.65rem;
                line-height: 1.35;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            .job-actions {
                flex-direction: column;
                gap: 6px;
            }
            .btn-apply,
            .btn-view {
                width: 100%;
                padding: 8px 10px;
                font-size: 0.76rem;
            }
            .btn-apply-disabled,
            .not-eligible-badge {
                padding: 8px 10px !important;
                font-size: 0.72rem !important;
            }
            .applied-badge,
            .rejected-badge,
            .accepted-badge,
            .closed-badge,
            .withdrawn-badge {
                font-size: 0.68rem;
                padding: 6px 8px;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .job-details-modal .swal2-popup {
                max-width: 95% !important;
                margin: 10px !important;
            }
            
            .apply-confirm-modal .swal2-popup {
                max-width: 90% !important;
                margin: 10px !important;
                padding: 20px !important;
            }
            
            .apply-confirm-modal .swal2-title {
                font-size: 20px !important;
            }
            
            .swal2-confirm-apply,
            .swal2-cancel-apply {
                padding: 10px 20px !important;
                font-size: 14px !important;
                width: 100% !important;
                margin: 5px 0 !important;
            }

            /* NSRP preferences card: avoid tags hugging the right / horizontal overflow in dashboard iframe */
            .main-content {
                padding: 12px !important;
                min-width: 0 !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                overflow-x: hidden;
            }
            .content-section {
                padding: 14px !important;
                min-width: 0 !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                overflow-x: hidden;
            }
            .welcome-card {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            .nrsp-info-card {
                padding: 16px 12px !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            .nrsp-info-card > p {
                max-width: 100%;
            }
            .nrsp-info-grid {
                grid-template-columns: 1fr !important;
                gap: 14px !important;
            }
            .nrsp-info-section {
                padding: 12px 10px !important;
            }
            .nrsp-info-section h4 {
                flex-wrap: wrap;
                gap: 6px;
            }
            
            /* Your Job Recommendations — compact 2-column stats */
            .stats-summary {
                padding: 14px 12px !important;
                margin-bottom: 18px !important;
            }
            .stats-summary > h3 {
                font-size: 1.1rem !important;
                margin: 0 0 8px 0 !important;
            }
            .stats-summary > p {
                font-size: 0.78rem !important;
                line-height: 1.35 !important;
                margin-bottom: 10px !important;
            }
            .stats-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 8px !important;
            }
            .stats-grid .stat-item:last-child:nth-child(odd) {
                grid-column: 1 / -1;
            }
            .stat-item {
                padding: 10px 6px !important;
                border-radius: 8px !important;
                min-width: 0;
            }
            .stat-number {
                font-size: 1.35rem !important;
                margin-bottom: 2px !important;
                line-height: 1.1 !important;
            }
            .stat-label {
                font-size: 0.62rem !important;
                line-height: 1.25 !important;
            }
        }

        @media (max-width: 480px) {
            .jobs-grid {
                gap: 8px !important;
            }
            .main-content {
                padding: 10px !important;
            }
            .content-section {
                padding: 12px !important;
            }
            .nrsp-info-card {
                padding: 14px 10px !important;
            }
            .nrsp-badge {
                font-size: 0.8rem !important;
                padding: 6px 10px !important;
            }
            .stats-summary {
                padding: 12px 10px !important;
            }
            .stats-grid {
                gap: 6px !important;
            }
            .stat-item {
                padding: 8px 4px !important;
            }
            .stat-number {
                font-size: 1.2rem !important;
            }
            .stat-label {
                font-size: 0.58rem !important;
            }
        }
    </style>
</head>
<body>
<script>
(function () {
    if (window.self === window.top) return;
    document.documentElement.classList.add('wc-in-iframe');
    document.body.classList.add('wc-in-iframe');
})();
</script>
<div class="main-content">
        <div class="content-section">
                <div class="welcome-card">
                    <h1><i class="fas fa-bullseye"></i> Recommended Jobs</h1>
                    <p>Jobs matched to your NSRP form: skills, preferred occupation, location, and job type preference</p>
                </div>
                
                <?php if ($nrspData): ?>
                <div class="nrsp-info-card">
                    <h3><i class="fas fa-file-alt"></i> Your NSRP Form Preferences</h3>
                    <p style="margin-bottom: 20px; color: #666; font-size: 0.9rem;">
                        These are the preferences you provided in your NSRP form. 
                        <strong><i class="fas fa-robot"></i> AI-Powered Matching:</strong> The system intelligently matches jobs even if locations are nearby (e.g., Manila ↔ Makati) 
                        and handles "any" preferences to show all relevant jobs.
                    </p>
                    <div class="nrsp-info-grid">
                        <div class="nrsp-info-section">
                            <h4><i class="fas fa-briefcase"></i> Preferred Occupations</h4>
                            <div class="nrsp-info-items">
                                <?php if (workconnect_nrsp_show_preference($nrspData['occupation1'] ?? null)): ?>
                                    <span class="nrsp-badge <?php echo strtolower(trim($nrspData['occupation1'])) === 'any' ? 'ai-badge' : ''; ?>">
                                        <?php echo htmlspecialchars($nrspData['occupation1']); ?>
                                        <?php if (strtolower(trim($nrspData['occupation1'])) === 'any'): ?>
                                            <i class="fas fa-robot" title="AI will match all relevant jobs"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (workconnect_nrsp_show_preference($nrspData['occupation2'] ?? null)): ?>
                                    <span class="nrsp-badge <?php echo strtolower(trim($nrspData['occupation2'])) === 'any' ? 'ai-badge' : ''; ?>">
                                        <?php echo htmlspecialchars($nrspData['occupation2']); ?>
                                        <?php if (strtolower(trim($nrspData['occupation2'])) === 'any'): ?>
                                            <i class="fas fa-robot" title="AI will match all relevant jobs"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (workconnect_nrsp_show_preference($nrspData['occupation3'] ?? null)): ?>
                                    <span class="nrsp-badge <?php echo strtolower(trim($nrspData['occupation3'])) === 'any' ? 'ai-badge' : ''; ?>">
                                        <?php echo htmlspecialchars($nrspData['occupation3']); ?>
                                        <?php if (strtolower(trim($nrspData['occupation3'])) === 'any'): ?>
                                            <i class="fas fa-robot" title="AI will match all relevant jobs"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!workconnect_nrsp_show_preference($nrspData['occupation1'] ?? null) && !workconnect_nrsp_show_preference($nrspData['occupation2'] ?? null) && !workconnect_nrsp_show_preference($nrspData['occupation3'] ?? null)): ?>
                                    <span class="nrsp-badge empty">Not specified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="nrsp-info-section">
                            <h4><i class="fas fa-map-marker-alt"></i> Preferred Locations</h4>
                            <div class="nrsp-info-items">
                                <?php if (workconnect_nrsp_show_preference($nrspData['local1'] ?? null)): ?>
                                    <span class="nrsp-badge"><?php echo htmlspecialchars($nrspData['local1']); ?></span>
                                <?php endif; ?>
                                <?php if (workconnect_nrsp_show_preference($nrspData['local2'] ?? null)): ?>
                                    <span class="nrsp-badge"><?php echo htmlspecialchars($nrspData['local2']); ?></span>
                                <?php endif; ?>
                                <?php if (workconnect_nrsp_show_preference($nrspData['local3'] ?? null)): ?>
                                    <span class="nrsp-badge"><?php echo htmlspecialchars($nrspData['local3']); ?></span>
                                <?php endif; ?>
                                <?php if (!workconnect_nrsp_show_preference($nrspData['local1'] ?? null) && !workconnect_nrsp_show_preference($nrspData['local2'] ?? null) && !workconnect_nrsp_show_preference($nrspData['local3'] ?? null)): ?>
                                    <span class="nrsp-badge empty">Not specified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="nrsp-info-section">
                            <h4><i class="fas fa-tools"></i> Your Skills</h4>
                            <div class="nrsp-info-items">
                                <?php 
                                $allSkills = [];
                                
                                // Add training skills from text fields
                                if (!empty($nrspData['training_skills_1']) && strtolower(trim($nrspData['training_skills_1'])) !== 'n/a') {
                                    $skills1 = array_map('trim', explode(',', $nrspData['training_skills_1']));
                                    $allSkills = array_merge($allSkills, $skills1);
                                }
                                if (!empty($nrspData['training_skills_2']) && strtolower(trim($nrspData['training_skills_2'])) !== 'n/a') {
                                    $skills2 = array_map('trim', explode(',', $nrspData['training_skills_2']));
                                    $allSkills = array_merge($allSkills, $skills2);
                                }
                                if (!empty($nrspData['training_skills_3']) && strtolower(trim($nrspData['training_skills_3'])) !== 'n/a') {
                                    $skills3 = array_map('trim', explode(',', $nrspData['training_skills_3']));
                                    $allSkills = array_merge($allSkills, $skills3);
                                }
                                
                                // Add skill_others
                                if (!empty($nrspData['skill_others']) && strtolower(trim($nrspData['skill_others'])) !== 'n/a') {
                                    $others = array_map('trim', explode(',', $nrspData['skill_others']));
                                    $allSkills = array_merge($allSkills, $others);
                                }
                                
                                // Add checkbox skills (boolean fields)
                                $checkboxSkills = [
                                    'skill_auto_mechanic' => 'Auto Mechanic',
                                    'skill_electrician' => 'Electrician',
                                    'skill_photography' => 'Photography',
                                    'skill_beautician' => 'Beautician',
                                    'skill_embroidery' => 'Embroidery',
                                    'skill_plumbing' => 'Plumbing',
                                    'skill_carpentry' => 'Carpentry',
                                    'skill_gardening' => 'Gardening',
                                    'skill_sewing' => 'Sewing',
                                    'skill_computer' => 'Computer',
                                    'skill_masonry' => 'Masonry',
                                    'skill_stenography' => 'Stenography',
                                    'skill_domestic' => 'Domestic',
                                    'skill_painter' => 'Painter',
                                    'skill_tailoring' => 'Tailoring',
                                    'skill_driver' => 'Driver',
                                    'skill_painting' => 'Painting'
                                ];
                                
                                foreach ($checkboxSkills as $field => $label) {
                                    if (!empty($nrspData[$field]) && $nrspData[$field] == 1) {
                                        $allSkills[] = $label;
                                    }
                                }
                                
                                // Filter out empty values, "n/a", and duplicates
                                $allSkills = array_filter($allSkills, function($skill) {
                                    $skill = trim($skill);
                                    return !empty($skill) && strtolower($skill) !== 'n/a';
                                });
                                $allSkills = array_unique(array_map('trim', $allSkills));
                                ?>
                                <?php if (!empty($allSkills)): ?>
                                    <?php foreach ($allSkills as $skill): ?>
                                        <span class="nrsp-badge skill"><?php echo htmlspecialchars($skill); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="nrsp-badge empty">No skills specified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="nrsp-info-section">
                            <h4><i class="fas fa-clock"></i> Job Type Preferences</h4>
                            <div class="nrsp-info-items">
                                <?php if (!empty($nrspData['fulltime']) && $nrspData['fulltime'] == 1): ?>
                                    <span class="nrsp-badge">Full-time</span>
                                <?php endif; ?>
                                <?php if (!empty($nrspData['parttime']) && $nrspData['parttime'] == 1): ?>
                                    <span class="nrsp-badge">Part-time</span>
                                <?php endif; ?>
                                <?php if (empty($nrspData['fulltime']) && empty($nrspData['parttime'])): ?>
                                    <span class="nrsp-badge empty">Not specified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                   
                </div>
                <?php else: ?>
                <div class="nrsp-info-card" style="border-left-color: #ffc107;">
                    <h3><i class="fas fa-exclamation-triangle"></i> NSRP Form Not Found</h3>
                    <p style="margin-bottom: 15px; color: #666;">
                        We couldn't find your NSRP form data. Please complete the NSRP form to get personalized job recommendations.
                    </p>
                    <a href="#" class="btn-update-nrsp" onclick="navigateToNRSPForm(event)">
                        <i class="fas fa-file-alt"></i> Complete NSRP Form
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="stats-summary">
                    <h3>Your Job Recommendations</h3>
                    <p style="margin-bottom: 15px; color: #666; font-size: 0.9rem;">
                        <i class="fas fa-robot"></i> 
                        <strong>AI-Powered Matching:</strong> Jobs are intelligently matched using your NRSP form data. 
                        The system understands location proximity (e.g., Manila ↔ Makati) and handles "any" preferences. 
                        Only jobs with <?php echo $minScore; ?>%+ compatibility are shown.
                    </p>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($recommendations); ?></div>
                            <div class="stat-label">Total Recommendations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count(array_filter($recommendations, function($job) { return $job['compatibility_score'] >= 80; })); ?></div>
                            <div class="stat-label">High Match (80%+)</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count(array_filter($recommendations, function($job) { return $job['compatibility_score'] >= 60 && $job['compatibility_score'] < 80; })); ?></div>
                            <div class="stat-label">Good Match (60-79%)</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count(array_filter($recommendations, function ($job) use ($globallyAcceptedForStats) {
                                $st = isset($job['my_application_status']) ? trim((string) $job['my_application_status']) : '';
                                if ($st === '' || strcasecmp($st, 'Rejected') === 0) {
                                    return false;
                                }
                                if (!$globallyAcceptedForStats && strcasecmp($st, 'Withdrawn') === 0) {
                                    return false;
                                }
                                if (!$globallyAcceptedForStats && strcasecmp($st, 'Accepted') === 0) {
                                    return false;
                                }
                                if (!$globallyAcceptedForStats && strcasecmp($st, 'Closed') === 0) {
                                    return false;
                                }
                                return true;
                            })); ?></div>
                            <div class="stat-label">Submitted / In progress</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count(array_filter($recommendations, function ($job) use ($globallyAcceptedForStats) {
                                if ($globallyAcceptedForStats) {
                                    return false;
                                }
                                $st = isset($job['my_application_status']) ? trim((string) $job['my_application_status']) : '';

                                return $st === '' || strcasecmp($st, 'Rejected') === 0
                                    || strcasecmp($st, 'Withdrawn') === 0
                                    || strcasecmp($st, 'Accepted') === 0;
                            })); ?></div>
                            <div class="stat-label">Open to apply</div>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($recommendations)): ?>
                    <div class="no-jobs">
                        <i class="fas fa-search"></i>
                        <h3>No Job Recommendations Found</h3>
                        <p>We couldn't find any jobs that match your NRSP form preferences (skills, preferred occupation, and location). Please complete your NRSP form or update your preferences.</p>
                        
                    </div>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($recommendations as $job): 
                            $breakdown = $job['match_breakdown'] ?? null;
                        ?>
                            <div class="job-card" 
                                 data-job-id="<?php echo $job['id']; ?>"
                                 data-job-title="<?php echo htmlspecialchars($job['title']); ?>"
                                 data-job-company="<?php echo htmlspecialchars($job['company']); ?>"
                                 data-job-location="<?php echo htmlspecialchars($job['location']); ?>"
                                 data-job-type="<?php echo htmlspecialchars($job['job_type']); ?>"
                                 data-job-salary="<?php echo htmlspecialchars($job['salary_range'] ?? 'Not specified'); ?>"
                                 data-job-description="<?php echo htmlspecialchars($job['description']); ?>"
                                 data-job-requirements="<?php echo htmlspecialchars($job['requirements']); ?>"
                                 data-job-industry="<?php echo htmlspecialchars($job['industry'] ?? 'Not specified'); ?>"
                                 data-job-posted="<?php echo date('M d, Y', strtotime($job['created_at'])); ?>"
                                 data-job-score="<?php echo round($job['compatibility_score']); ?>">
                                <div class="job-card-header">
                                    <div class="compatibility-score <?php 
                                        echo $job['compatibility_score'] >= 80 ? '' : 
                                            ($job['compatibility_score'] >= 60 ? 'medium' : 'low'); 
                                    ?>">
                                        <?php echo round($job['compatibility_score']); ?>% Match
                                    </div>
                                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <p class="company-name"><?php echo htmlspecialchars($job['company']); ?></p>
                                    <div class="job-meta">
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
                                            ₱ <?php echo htmlspecialchars($job['salary_range']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <div class="job-description">
                                        <?php echo htmlspecialchars(substr($job['description'], 0, 200)) . '...'; ?>
                                    </div>
                                    
                                    <?php if ($breakdown): ?>
                                    <div class="match-breakdown">
                                        <h4><i class="fas fa-chart-pie"></i> Why This Job Matches Your NRSP Form:</h4>
                                        <div class="breakdown-grid">
                                            <div class="breakdown-item">
                                                <div class="breakdown-label">
                                                    <i class="fas fa-briefcase"></i> Preferred Occupation
                                                </div>
                                                <div class="breakdown-score"><?php echo round($breakdown['occupation_score']); ?>%</div>
                                                <?php if (!empty($breakdown['matched_occupations'])): ?>
                                                    <div class="breakdown-details">
                                                        <strong>Your preference:</strong> 
                                                        <?php 
                                                        $userOccupations = array_values(array_filter([
                                                            $nrspData['occupation1'] ?? '',
                                                            $nrspData['occupation2'] ?? '',
                                                            $nrspData['occupation3'] ?? ''
                                                        ], 'workconnect_nrsp_show_preference'));
                                                        $displayOccupations = array_map(function($occ) {
                                                            $occ = trim($occ);
                                                            if (strtolower($occ) === 'any') {
                                                                return '<span style="color: #28a745; font-weight: bold;">Any (AI matched)</span>';
                                                            }
                                                            return htmlspecialchars($occ);
                                                        }, $userOccupations);
                                                        echo implode(', ', $displayOccupations);
                                                        ?>
                                                        <br>
                                                        <strong>Matches:</strong> 
                                                        <?php 
                                                        $matchedDisplay = array_map(function($occ) {
                                                            if (strtolower($occ) === 'any') {
                                                                return '<span style="color: #28a745;">Any occupation (AI matched)</span>';
                                                            }
                                                            return htmlspecialchars($occ);
                                                        }, $breakdown['matched_occupations']);
                                                        echo implode(', ', $matchedDisplay);
                                                        ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="breakdown-details">
                                                        <strong>Your preference:</strong> 
                                                        <?php 
                                                        $userOccupations = array_values(array_filter([
                                                            $nrspData['occupation1'] ?? '',
                                                            $nrspData['occupation2'] ?? '',
                                                            $nrspData['occupation3'] ?? ''
                                                        ], 'workconnect_nrsp_show_preference'));
                                                        echo !empty($userOccupations) ? htmlspecialchars(implode(', ', $userOccupations)) : 'Not specified';
                                                        ?>
                                                        <br>
                                                        <span style="color: #dc3545;">No occupation match</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="breakdown-item">
                                                <div class="breakdown-label">
                                                    <i class="fas fa-tools"></i> Skills Match
                                                </div>
                                                <?php 
                                                $totalSkills = $breakdown['total_skills'] ?? 0;
                                                $matchedCount = count($breakdown['matched_skills'] ?? []);
                                                $skillScoreDisplay = ($totalSkills > 0 && $matchedCount > 0) ? round($breakdown['skill_score']) : 0;
                                                ?>
                                                <div class="breakdown-score"><?php echo $skillScoreDisplay; ?>%</div>
                                                <?php if ($totalSkills > 0 && !empty($breakdown['matched_skills'])): ?>
                                                    <div class="breakdown-details">
                                                        <?php 
                                                        echo "<strong>{$matchedCount} of {$totalSkills} skills match</strong>";
                                                        ?>
                                                        <br>
                                                        <strong>Matched skills:</strong> <?php echo htmlspecialchars(implode(', ', array_slice($breakdown['matched_skills'], 0, 3))); ?><?php echo count($breakdown['matched_skills']) > 3 ? '...' : ''; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="breakdown-details">
                                                        <strong>Your skills:</strong> 
                                                        <?php 
                                                        $allSkills = [];
                                                        
                                                        // Add training skills from text fields
                                                        if (!empty($nrspData['training_skills_1']) && strtolower(trim($nrspData['training_skills_1'])) !== 'n/a') {
                                                            $skills1 = array_map('trim', explode(',', $nrspData['training_skills_1']));
                                                            $allSkills = array_merge($allSkills, $skills1);
                                                        }
                                                        if (!empty($nrspData['training_skills_2']) && strtolower(trim($nrspData['training_skills_2'])) !== 'n/a') {
                                                            $skills2 = array_map('trim', explode(',', $nrspData['training_skills_2']));
                                                            $allSkills = array_merge($allSkills, $skills2);
                                                        }
                                                        if (!empty($nrspData['training_skills_3']) && strtolower(trim($nrspData['training_skills_3'])) !== 'n/a') {
                                                            $skills3 = array_map('trim', explode(',', $nrspData['training_skills_3']));
                                                            $allSkills = array_merge($allSkills, $skills3);
                                                        }
                                                        
                                                        // Add skill_others
                                                        if (!empty($nrspData['skill_others']) && strtolower(trim($nrspData['skill_others'])) !== 'n/a') {
                                                            $others = array_map('trim', explode(',', $nrspData['skill_others']));
                                                            $allSkills = array_merge($allSkills, $others);
                                                        }
                                                        
                                                        // Add checkbox skills (boolean fields)
                                                        $checkboxSkills = [
                                                            'skill_auto_mechanic' => 'Auto Mechanic',
                                                            'skill_electrician' => 'Electrician',
                                                            'skill_photography' => 'Photography',
                                                            'skill_beautician' => 'Beautician',
                                                            'skill_embroidery' => 'Embroidery',
                                                            'skill_plumbing' => 'Plumbing',
                                                            'skill_carpentry' => 'Carpentry',
                                                            'skill_gardening' => 'Gardening',
                                                            'skill_sewing' => 'Sewing',
                                                            'skill_computer' => 'Computer',
                                                            'skill_masonry' => 'Masonry',
                                                            'skill_stenography' => 'Stenography',
                                                            'skill_domestic' => 'Domestic',
                                                            'skill_painter' => 'Painter',
                                                            'skill_tailoring' => 'Tailoring',
                                                            'skill_driver' => 'Driver',
                                                            'skill_painting' => 'Painting'
                                                        ];
                                                        
                                                        foreach ($checkboxSkills as $field => $label) {
                                                            if (!empty($nrspData[$field]) && $nrspData[$field] == 1) {
                                                                $allSkills[] = $label;
                                                            }
                                                        }
                                                        
                                                        // Filter out empty values, "n/a", and duplicates
                                                        $allSkills = array_filter($allSkills, function($skill) {
                                                            $skill = trim($skill);
                                                            return !empty($skill) && strtolower($skill) !== 'n/a';
                                                        });
                                                        $allSkills = array_unique(array_map('trim', $allSkills));
                                                        echo !empty($allSkills) ? htmlspecialchars(implode(', ', array_slice($allSkills, 0, 5))) . (count($allSkills) > 5 ? '...' : '') : 'Not specified';
                                                        ?>
                                                        <br>
                                                        <span style="color: #dc3545;">No skills match (0%)</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="breakdown-item">
                                                <div class="breakdown-label">
                                                    <i class="fas fa-map-marker-alt"></i> Location Match
                                                </div>
                                                <div class="breakdown-score"><?php echo round($breakdown['location_score']); ?>%</div>
                                                <div class="breakdown-details">
                                                    <?php
                                                    $locBasis = isset($breakdown['location_basis']) ? (string) $breakdown['location_basis'] : '';
                                                    $locKm = (isset($breakdown['location_distance_km']) && $breakdown['location_distance_km'] !== null && is_numeric($breakdown['location_distance_km']))
                                                        ? round((float) $breakdown['location_distance_km'], 1)
                                                        : null;
                                                    $nearestPref = isset($breakdown['nearest_preferred_label']) ? trim((string) $breakdown['nearest_preferred_label']) : '';
                                                    ?>
                                                    <?php if ($locBasis === 'current' || ($locBasis === '' && !empty($breakdown['is_nearby_current']))): ?>
                                                        <span style="color: #28a745; font-weight: 600;">
                                                            <i class="fas fa-home"></i> Based on your location (NSRP address)
                                                        </span>
                                                        <br>This uses the distance from your registered <strong>city/municipality and province</strong> on your NSRP form to the job location<?php echo $locKm !== null ? ' (approx. <strong>' . htmlspecialchars((string) $locKm) . ' km</strong>)' : ''; ?>.
                                                    <?php elseif ($locBasis === 'preferred'): ?>
                                                        <span style="color: #233a8b; font-weight: 600;">
                                                            <i class="fas fa-map-marker-alt"></i> Based on your preferred work location
                                                        </span>
                                                        <br>This uses how close the job is to your <strong>preferred work location(s)</strong><?php echo $nearestPref !== '' ? ' (nearest match: ' . htmlspecialchars($nearestPref) . ')' : ''; ?><?php echo $locKm !== null ? ', approx. <strong>' . htmlspecialchars((string) $locKm) . ' km</strong> away' : ''; ?>.
                                                    <?php elseif ($locBasis === 'any'): ?>
                                                        <span style="color: #233a8b; font-weight: 600;">
                                                            <i class="fas fa-globe"></i> Open location preference
                                                        </span>
                                                        <br>Your NSRP lists <strong>any</strong> work location, so distance is not used for this part of the match.
                                                    <?php elseif ($breakdown['location_score'] > 0): ?>
                                                        <span style="color: #233a8b; font-weight: 600;">
                                                            <i class="fas fa-map-marker-alt"></i> Location match
                                                        </span>
                                                        <br>Location scoring applied<?php echo $locKm !== null ? ' (approx. ' . htmlspecialchars((string) $locKm) . ' km)' : ''; ?>.
                                                    <?php else: ?>
                                                        <span style="color: #dc3545; font-weight: 600;">
                                                            <i class="fas fa-times-circle"></i> No Location Match
                                                        </span>
                                                        <br>The job location is outside your preferred areas and NSRP address range we could measure.
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="breakdown-item">
                                                <div class="breakdown-label">
                                                    <i class="fas fa-clock"></i> Job Type Match
                                                </div>
                                                <div class="breakdown-score"><?php echo round($breakdown['job_type_score']); ?>%</div>
                                                <div class="breakdown-details">
                                                    <strong>Your preference:</strong> 
                                                    <?php 
                                                    $userJobTypes = [];
                                                    if (!empty($nrspData['fulltime']) && $nrspData['fulltime'] == 1) {
                                                        $userJobTypes[] = 'Full-time';
                                                    }
                                                    if (!empty($nrspData['parttime']) && $nrspData['parttime'] == 1) {
                                                        $userJobTypes[] = 'Part-time';
                                                    }
                                                    echo !empty($userJobTypes) ? htmlspecialchars(implode(', ', $userJobTypes)) : 'Not specified';
                                                    ?>
                                                    <br>
                                                    <strong>Job type:</strong> <?php echo htmlspecialchars($job['job_type']); ?>
                                                    <?php if ($breakdown['job_type_score'] == 100): ?>
                                                        <br><small style="color: #28a745;"><i class="fas fa-check"></i> Perfect match</small>
                                                    <?php elseif ($breakdown['job_type_score'] >= 50 && $breakdown['job_type_score'] < 100): ?>
                                                        <br><small style="color: #ffc107;"><i class="fas fa-info-circle"></i> Partial match</small>
                                                    <?php else: ?>
                                                        <br><small style="color: #dc3545;"><i class="fas fa-times"></i> No match</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="job-requirements">
                                        <h4>Key Requirements:</h4>
                                        <p><?php echo htmlspecialchars(substr($job['requirements'], 0, 150)) . '...'; ?></p>
                                    </div>
                                    
                                    <div class="job-actions">
                                        <?php
                                        $appSt = isset($job['my_application_status']) ? trim((string)$job['my_application_status']) : '';
                                        $globallyAccepted = workconnect_jobseeker_is_actively_placed(is_array($nrspData) ? $nrspData : null);
                                        $isRejected = $appSt !== '' && strcasecmp($appSt, 'Rejected') === 0;
                                        $isAccepted = $appSt !== '' && strcasecmp($appSt, 'Accepted') === 0;
                                        $isDbClosed = $appSt !== '' && strcasecmp($appSt, 'Closed') === 0;
                                        $isWithdrawn = $appSt !== '' && strcasecmp($appSt, 'Withdrawn') === 0;
                                        $isPastClosedPlacement = $isDbClosed || ($isAccepted && !$globallyAccepted);
                                        $withdrawnWhilePlaced = $isWithdrawn && $globallyAccepted;
                                        $showApplied = $appSt !== '' && !$isRejected && !($isAccepted && $globallyAccepted) && !$withdrawnWhilePlaced && !($isWithdrawn && !$globallyAccepted) && !$isPastClosedPlacement;
                                        ?>
                                        <?php if ($globallyAccepted && $appSt === ''): ?>
                                            <div class="not-eligible-badge" title="You are already accepted for employment. You cannot apply to additional jobs.">
                                                <i class="fas fa-ban"></i> Not eligible to apply
                                            </div>
                                        <?php elseif ($isRejected): ?>
                                            <div class="rejected-badge">
                                                <i class="fas fa-times-circle"></i> Rejected
                                            </div>
                                        <?php elseif ($isAccepted && $globallyAccepted): ?>
                                            <div class="accepted-badge">
                                                <i class="fas fa-check-circle"></i> Accepted
                                            </div>
                                        <?php elseif ($isPastClosedPlacement): ?>
                                            <div class="closed-badge" title="<?php echo $isDbClosed ? 'This application is closed (placement ended). Not an open application.' : 'Placement ended; you may apply again if the role is open.'; ?>">
                                                <i class="fas fa-door-closed"></i> Closed
                                            </div>
                                            <?php if (!$isDbClosed): ?>
                                            <form method="POST" style="flex: 1;" id="applyForm_<?php echo $job['id']; ?>">
                                                <input type="hidden" name="action" value="apply_job">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="button" class="btn-apply" onclick="confirmApply(<?php echo $job['id']; ?>)">
                                                    <i class="fas fa-paper-plane"></i> Apply Now
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        <?php elseif ($withdrawnWhilePlaced): ?>
                                            <div class="withdrawn-badge" title="Closed because you were accepted for employment elsewhere.">
                                                <i class="fas fa-ban"></i> Withdrawn
                                            </div>
                                        <?php elseif ($showApplied): ?>
                                            <div class="applied-badge">
                                                <i class="fas fa-check"></i> Applied
                                            </div>
                                        <?php else: ?>
                                            <form method="POST" style="flex: 1;" id="applyForm_<?php echo $job['id']; ?>">
                                                <input type="hidden" name="action" value="apply_job">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="button" class="btn-apply" onclick="confirmApply(<?php echo $job['id']; ?>)">
                                                    <i class="fas fa-paper-plane"></i> Apply Now
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button class="btn-view" onclick="viewJobDetails(<?php echo $job['id']; ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <script>
        // Function to navigate to NRSP form using parent window's navigation
        function navigateToNRSPForm(event) {
            if (event) {
                event.preventDefault();
            }
            
            // Check if we're in an iframe (loaded from dashboard)
            if (window.parent && window.parent !== window) {
                // We're in an iframe, communicate with parent
                try {
                    // Call parent's showSection function
                    if (typeof window.parent.showSection === 'function') {
                        window.parent.showSection('apply');
                    } else {
                        // Fallback: change parent's hash
                        window.parent.location.hash = 'apply';
                    }
                } catch (e) {
                    // If cross-origin or other error, fallback to direct link
                    console.error('Error navigating:', e);
                    window.location.href = 'apply.php';
                }
            } else {
                // Not in iframe, use hash navigation
                if (window.location.hash) {
                    window.location.hash = 'apply';
                } else {
                    // Direct navigation
                    window.location.href = 'apply.php';
                }
            }
        }
        
        /** Swap Apply Now → Applied as soon as the server succeeds (avoids waiting for a full iframe reload). */
        function setJobCardApplied(jobId) {
            const form = document.getElementById('applyForm_' + jobId);
            if (!form) return;
            const badge = document.createElement('div');
            badge.className = 'applied-badge';
            badge.setAttribute('role', 'status');
            badge.innerHTML = '<i class="fas fa-check"></i> Applied';
            form.replaceWith(badge);
        }

        async function confirmApply(jobId) {
            const result = await Swal.fire({
                title: 'Confirm Application',
                html: '<div style="text-align: center;"><i class="fas fa-briefcase" style="font-size: 48px; color: #28a745; margin-bottom: 20px;"></i><p style="font-size: 16px; color: #333;">Are you sure you want to apply for this job?</p></div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Yes, Apply Now',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                customClass: {
                    popup: 'apply-confirm-modal',
                    confirmButton: 'swal2-confirm-apply',
                    cancelButton: 'swal2-cancel-apply'
                },
                buttonsStyling: true,
                width: (typeof window !== 'undefined' && window.innerWidth <= 520) ? 'min(92vw, 450px)' : '450px'
            });
            
            if (!result.isConfirmed) {
                return;
            }

            const applyUrl = window.location.href.split('#')[0];

            Swal.fire({
                title: 'Submitting your application...',
                html: '<p style="margin-top:12px;color:#555;font-size:15px;">Please wait a moment.</p>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('action', 'apply_job');
            formData.append('job_id', String(jobId));
            formData.append('ajax_apply', '1');

            try {
                const resp = await fetch(applyUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                let data = {};
                try {
                    data = await resp.json();
                } catch (parseErr) {
                    data = {};
                }

                if (resp.ok && data.success) {
                    setJobCardApplied(jobId);
                    await Swal.fire({
                        icon: 'success',
                        title: 'Application submitted!',
                        text: data.message || 'Your application was submitted successfully.',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK'
                    });
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Could not apply',
                        text: (data && data.message) ? data.message : 'Something went wrong. Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            } catch (err) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Connection error',
                    text: 'Please check your network and try again.',
                    confirmButtonColor: '#dc3545'
                });
            }
        }
        
        function viewJobDetails(jobId) {
            // Find the job data from the current recommendations
            const jobCard = document.querySelector(`[data-job-id="${jobId}"]`);
            if (!jobCard) {
                Swal.fire({
                    title: 'Error',
                    text: 'Job details not found.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Get job data from data attributes or fetch from server
            const jobTitle = jobCard.getAttribute('data-job-title') || 'Job Title';
            const company = jobCard.getAttribute('data-job-company') || 'Company';
            const location = jobCard.getAttribute('data-job-location') || 'Location';
            const jobType = jobCard.getAttribute('data-job-type') || 'Job Type';
            const salaryRange = jobCard.getAttribute('data-job-salary') || 'Not specified';
            const description = normalizeJobBodyText(jobCard.getAttribute('data-job-description') || 'No description available.');
            const requirements = normalizeJobBodyText(jobCard.getAttribute('data-job-requirements') || 'No requirements specified.');
            const industry = jobCard.getAttribute('data-job-industry') || 'Not specified';
            const postedDate = jobCard.getAttribute('data-job-posted') || 'Unknown';
            const compatibilityScore = jobCard.getAttribute('data-job-score') || '0';
            
            // Create detailed HTML content
            const jobDetailsHTML = `
                <div style="text-align: left; max-width: 100%;">
                    <div style="background: linear-gradient(135deg, #233a8b 0%, #1a2d6b 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0; margin: -20px -3% 20px -20px;">
                        <h2 style="margin: 0 0 10px 0; font-size: 1.5rem;">${escapeHtml(jobTitle)}</h2>
                        <p style="margin: 0; font-size: 1.1rem; opacity: 0.9;">${escapeHtml(company)}</p>
                        <div style="margin-top: 15px; display: flex; gap: 15px; flex-wrap: wrap; font-size: 0.9rem;">
                            <span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(location)}</span>
                            <span><i class="fas fa-briefcase"></i> ${escapeHtml(jobType)}</span>
                            <span><i class="fas fa-money-bill-wave"></i> ₱ ${escapeHtml(salaryRange)}</span>
                            <span><i class="fas fa-industry"></i> ${escapeHtml(industry)}</span>
                        </div>
                        <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.2); border-radius: 5px; display: inline-block;">
                            <strong>Compatibility Score: ${escapeHtml(compatibilityScore)}%</strong>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h3 style="color: #233a8b; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas fa-info-circle"></i> Job Description
                        </h3>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #233a8b; white-space: pre-line; line-height: 1.6; text-align: center;">${escapeHtml(description)}</div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h3 style="color: #233a8b; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas fa-clipboard-list"></i> Requirements
                        </h3>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; white-space: pre-line; line-height: 1.6; text-align: center;">${escapeHtml(requirements)}</div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Location</strong>
                            <span style="color: #233a8b; font-size: 1.1rem;">${escapeHtml(location)}</span>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Job Type</strong>
                            <span style="color: #233a8b; font-size: 1.1rem;">${escapeHtml(jobType)}</span>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Salary Range</strong>
                            <span style="color: #233a8b; font-size: 1.1rem;">₱ ${escapeHtml(salaryRange)}</span>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Industry</strong>
                            <span style="color: #233a8b; font-size: 1.1rem;">${escapeHtml(industry)}</span>
                        </div>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 20px;">
                        <strong style="color: #856404;"><i class="fas fa-calendar"></i> Posted:</strong> 
                        <span style="color: #856404;">${escapeHtml(postedDate)}</span>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: '',
                html: jobDetailsHTML,
                width: (typeof window !== 'undefined' && window.innerWidth <= 600) ? 'min(96vw, 800px)' : '800px',
                showCloseButton: true,
                showConfirmButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: '#233a8b',
                customClass: {
                    popup: 'job-details-modal',
                    htmlContainer: 'job-details-content'
                }
            });
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        /** Tell parent dashboard the real document height (avoids iframe scrollHeight collapse on mobile Chrome when URL bar resizes). */
        (function wcRecommendedJobsIframeHeight() {
            if (window.self === window.top) return;
            var t = null;
            function measure() {
                var mc = document.querySelector('.main-content');
                var b = document.body;
                var e = document.documentElement;
                var h = Math.ceil(Math.max(
                    mc ? mc.scrollHeight : 0,
                    b ? b.scrollHeight : 0,
                    b ? b.offsetHeight : 0,
                    e ? e.scrollHeight : 0,
                    e ? e.offsetHeight : 0
                ));
                if (h < 200) h = 200;
                try {
                    window.parent.postMessage({ type: 'workconnect-resize-iframe', source: 'recommended_jobs', height: h }, '*');
                } catch (err) { /* ignore */ }
            }
            function schedule() {
                if (t) clearTimeout(t);
                t = setTimeout(measure, 50);
            }
            window.addEventListener('load', schedule);
            document.addEventListener('DOMContentLoaded', schedule);
            window.addEventListener('resize', schedule);
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', schedule);
                window.visualViewport.addEventListener('scroll', schedule);
            }
            if (typeof ResizeObserver !== 'undefined') {
                var ro = new ResizeObserver(schedule);
                if (document.body) ro.observe(document.body);
                ro.observe(document.documentElement);
            }
            schedule();
        })();

        /** Trim DB text and each line so modal body has no fake “first-line indent”. */
        function normalizeJobBodyText(str) {
            if (str == null || str === '') return '';
            return String(str)
                .trim()
                .split(/\r?\n/)
                .map(function (line) { return line.trim(); })
                .filter(function (line) { return line.length > 0; })
                .join('\n');
        }
        
    </script>
</body>
</html>