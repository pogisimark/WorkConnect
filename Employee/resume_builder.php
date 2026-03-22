<?php
// Resume Builder for Employee Dashboard - NEW VERSION WITH SPECIFIC COLUMNS
// DISABLED: Resume builder feature is no longer active. Redirect to dashboard.
require_once 'session_check.php';
require_once 'db.php';

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Resume builder disabled - redirect to dashboard
header("Location: dashboard.php");
exit();

/* RESUME BUILDER CODE BELOW - COMMENTED OUT / INACTIVE
$userId = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug: Log all POST requests
    error_log("=== FORM SUBMISSION DEBUG ===");
    error_log("POST method detected");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Also output to browser for immediate debugging
    echo "<!-- DEBUG: POST request received -->";
    echo "<!-- POST data: " . htmlspecialchars(print_r($_POST, true)) . " -->";
    echo "<!-- FILES data: " . htmlspecialchars(print_r($_FILES, true)) . " -->";
    
    if (isset($_POST['action'])) {
        error_log("Action found: " . $_POST['action']);
        echo "<!-- DEBUG: Action found: " . htmlspecialchars($_POST['action']) . " -->";
        switch ($_POST['action']) {
            case 'save_resume':
                try {
                    // Debug: Log received data
                    error_log("=== SAVE RESUME DEBUG ===");
                    error_log("POST data: " . print_r($_POST, true));
                    error_log("FILES data: " . print_r($_FILES, true));
                    
                    // Start transaction
                    $conn->begin_transaction();
                    
                    // Get form data
                $templateId = (int)$_POST['template_id'];
                $resumeName = trim($_POST['resume_name']);
                $isDefault = isset($_POST['is_default']) ? 1 : 0;
                    
                    // Personal Information - Direct access to form fields
                    $firstname = trim($_POST['personal_info']['firstname'] ?? '');
                    $lastname = trim($_POST['personal_info']['lastname'] ?? '');
                    $email = trim($_POST['personal_info']['email'] ?? '');
                    $phone = trim($_POST['personal_info']['phone'] ?? '');
                    $location = trim($_POST['personal_info']['location'] ?? '');
                    $linkedin = trim($_POST['personal_info']['linkedin'] ?? '');
                    $summary = trim($_POST['personal_info']['summary'] ?? '');
                    
                    // Skills
                    $skills = trim($_POST['skills'] ?? '');
                    $languages = trim($_POST['languages'] ?? '');
                    
                    // Handle image upload
                    $profileImagePath = '';
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/profile_images/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                        $filePath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
                            $profileImagePath = $filePath;
                        }
                    }
                
                // If this is set as default, unset other defaults
                if ($isDefault) {
                        $stmt = $conn->prepare("UPDATE resumes_new SET is_default = 0 WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
                }
                
                    // Insert main resume record
                    $stmt = $conn->prepare("INSERT INTO resumes_new (user_id, template_id, resume_name, firstname, lastname, email, phone, location, linkedin, summary, skills, languages, profile_image, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisssssssssssi", $userId, $templateId, $resumeName, $firstname, $lastname, $email, $phone, $location, $linkedin, $summary, $skills, $languages, $profileImagePath, $isDefault);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error saving resume: " . $conn->error);
                    }
                    
                    $resumeId = $conn->insert_id;
                $stmt->close();
                    
                    // Insert work experience
                    if (isset($_POST['work_experience']) && is_array($_POST['work_experience'])) {
                        foreach ($_POST['work_experience'] as $index => $exp) {
                            if (!empty($exp['job_title']) && !empty($exp['company'])) {
                                $stmt = $conn->prepare("INSERT INTO resume_work_experience (resume_id, job_title, company, start_date, end_date, location, description, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("issssssi", $resumeId, $exp['job_title'], $exp['company'], $exp['start_date'], $exp['end_date'], $exp['location'], $exp['description'], $index);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                    
                    // Insert education
                    if (isset($_POST['education']) && is_array($_POST['education'])) {
                        foreach ($_POST['education'] as $index => $edu) {
                            if (!empty($edu['degree']) && !empty($edu['field']) && !empty($edu['school'])) {
                                $stmt = $conn->prepare("INSERT INTO resume_education (resume_id, degree, field, school, graduation_year, gpa, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("isssssi", $resumeId, $edu['degree'], $edu['field'], $edu['school'], $edu['graduation_year'], $edu['gpa'], $index);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                    
                    // Insert certifications
                    if (isset($_POST['certifications']) && is_array($_POST['certifications'])) {
                        foreach ($_POST['certifications'] as $index => $cert) {
                            if (!empty($cert['name'])) {
                                $stmt = $conn->prepare("INSERT INTO resume_certifications (resume_id, name, organization, issue_date, expiry_date, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("issssi", $resumeId, $cert['name'], $cert['organization'], $cert['issue_date'], $cert['expiry_date'], $index);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $success_message = "Resume saved successfully!";
                    error_log("Resume saved successfully with ID: " . $resumeId);
                    
                } catch (Exception $e) {
                    // Rollback transaction
                    $conn->rollback();
                    $error_message = "Error saving resume: " . $e->getMessage();
                    error_log("Resume save error: " . $e->getMessage());
                }
                break;
                
            case 'update_resume':
                try {
                    // Start transaction
                    $conn->begin_transaction();
                    
                $resumeId = (int)$_POST['resume_id'];
                $templateId = (int)$_POST['template_id'];
                $resumeName = trim($_POST['resume_name']);
                $isDefault = isset($_POST['is_default']) ? 1 : 0;
                    
                    // Personal Information
                    $firstname = trim($_POST['personal_info']['firstname'] ?? '');
                    $lastname = trim($_POST['personal_info']['lastname'] ?? '');
                    $email = trim($_POST['personal_info']['email'] ?? '');
                    $phone = trim($_POST['personal_info']['phone'] ?? '');
                    $location = trim($_POST['personal_info']['location'] ?? '');
                    $linkedin = trim($_POST['personal_info']['linkedin'] ?? '');
                    $summary = trim($_POST['personal_info']['summary'] ?? '');
                    $skills = trim($_POST['skills'] ?? '');
                    $languages = trim($_POST['languages'] ?? '');
                    
                    // Handle image upload
                    $profileImagePath = '';
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        // Delete old image if exists
                        $oldStmt = $conn->prepare("SELECT profile_image FROM resumes_new WHERE id = ? AND user_id = ?");
                        $oldStmt->bind_param("ii", $resumeId, $userId);
                        $oldStmt->execute();
                        $oldResult = $oldStmt->get_result();
                        if ($oldResult->num_rows > 0) {
                            $oldResume = $oldResult->fetch_assoc();
                            if ($oldResume['profile_image'] && file_exists($oldResume['profile_image'])) {
                                unlink($oldResume['profile_image']);
                            }
                        }
                        $oldStmt->close();
                        
                        $uploadDir = 'uploads/profile_images/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                        $filePath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
                            $profileImagePath = $filePath;
                        }
                    }
                
                // If this is set as default, unset other defaults
                if ($isDefault) {
                        $stmt = $conn->prepare("UPDATE resumes_new SET is_default = 0 WHERE user_id = ? AND id != ?");
                    $stmt->bind_param("ii", $userId, $resumeId);
                    $stmt->execute();
                    $stmt->close();
                }
                
                    // Update main resume record
                    if (!empty($profileImagePath)) {
                        $stmt = $conn->prepare("UPDATE resumes_new SET template_id=?, resume_name=?, firstname=?, lastname=?, email=?, phone=?, location=?, linkedin=?, summary=?, skills=?, languages=?, profile_image=?, is_default=? WHERE id=? AND user_id=?");
                        $stmt->bind_param("isssssssssssiii", $templateId, $resumeName, $firstname, $lastname, $email, $phone, $location, $linkedin, $summary, $skills, $languages, $profileImagePath, $isDefault, $resumeId, $userId);
                } else {
                        $stmt = $conn->prepare("UPDATE resumes_new SET template_id=?, resume_name=?, firstname=?, lastname=?, email=?, phone=?, location=?, linkedin=?, summary=?, skills=?, languages=?, is_default=? WHERE id=? AND user_id=?");
                        $stmt->bind_param("issssssssssiii", $templateId, $resumeName, $firstname, $lastname, $email, $phone, $location, $linkedin, $summary, $skills, $languages, $isDefault, $resumeId, $userId);
                    }
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error updating resume: " . $conn->error);
                }
                $stmt->close();
                    
                    // Delete existing related records
                    $conn->query("DELETE FROM resume_work_experience WHERE resume_id = $resumeId");
                    $conn->query("DELETE FROM resume_education WHERE resume_id = $resumeId");
                    $conn->query("DELETE FROM resume_certifications WHERE resume_id = $resumeId");
                    
                    // Insert updated work experience
                    if (isset($_POST['work_experience']) && is_array($_POST['work_experience'])) {
                        foreach ($_POST['work_experience'] as $index => $exp) {
                            if (!empty($exp['job_title']) && !empty($exp['company'])) {
                                $stmt = $conn->prepare("INSERT INTO resume_work_experience (resume_id, job_title, company, start_date, end_date, location, description, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("issssssi", $resumeId, $exp['job_title'], $exp['company'], $exp['start_date'], $exp['end_date'], $exp['location'], $exp['description'], $index);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                    
                    // Insert updated education
                    if (isset($_POST['education']) && is_array($_POST['education'])) {
                        foreach ($_POST['education'] as $index => $edu) {
                            if (!empty($edu['degree']) && !empty($edu['field']) && !empty($edu['school'])) {
                                $stmt = $conn->prepare("INSERT INTO resume_education (resume_id, degree, field, school, graduation_year, gpa, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("isssssi", $resumeId, $edu['degree'], $edu['field'], $edu['school'], $edu['graduation_year'], $edu['gpa'], $index);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                    
                    // Insert updated certifications
                    if (isset($_POST['certifications']) && is_array($_POST['certifications'])) {
                        foreach ($_POST['certifications'] as $index => $cert) {
                            if (!empty($cert['name'])) {
                                $stmt = $conn->prepare("INSERT INTO resume_certifications (resume_id, name, organization, issue_date, expiry_date, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("issssi", $resumeId, $cert['name'], $cert['organization'], $cert['issue_date'], $cert['expiry_date'], $index);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $success_message = "Resume updated successfully!";
                    
                } catch (Exception $e) {
                    // Rollback transaction
                    $conn->rollback();
                    $error_message = "Error updating resume: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get user's existing resumes from new table
$resumes = [];
try {
    $stmt = $conn->prepare("SELECT r.*, rt.name as template_name FROM resumes_new r JOIN resume_templates rt ON r.template_id = rt.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
$stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Get related data
    $workExpStmt = $conn->prepare("SELECT * FROM resume_work_experience WHERE resume_id = ? ORDER BY sort_order");
    $workExpStmt->bind_param("i", $row['id']);
    $workExpStmt->execute();
    $workExpResult = $workExpStmt->get_result();
    $workExperience = [];
    while ($exp = $workExpResult->fetch_assoc()) {
        $workExperience[] = $exp;
    }
    $workExpStmt->close();
    
    $eduStmt = $conn->prepare("SELECT * FROM resume_education WHERE resume_id = ? ORDER BY sort_order");
    $eduStmt->bind_param("i", $row['id']);
    $eduStmt->execute();
    $eduResult = $eduStmt->get_result();
    $education = [];
    while ($edu = $eduResult->fetch_assoc()) {
        $education[] = $edu;
    }
    $eduStmt->close();
    
    $certStmt = $conn->prepare("SELECT * FROM resume_certifications WHERE resume_id = ? ORDER BY sort_order");
    $certStmt->bind_param("i", $row['id']);
    $certStmt->execute();
    $certResult = $certStmt->get_result();
    $certifications = [];
    while ($cert = $certResult->fetch_assoc()) {
        $certifications[] = $cert;
    }
    $certStmt->close();
    
    $row['work_experience'] = $workExperience;
    $row['education'] = $education;
    $row['certifications'] = $certifications;
    
    $resumes[] = $row;
}
$stmt->close();

} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("Resume builder database error: " . $e->getMessage());
}

// Get available templates
try {
$stmt = $conn->prepare("SELECT * FROM resume_templates WHERE is_active = 1 ORDER BY name");
    if (!$stmt) {
        throw new Exception("Templates prepare failed: " . $conn->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Templates execute failed: " . $stmt->error);
    }
$templates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
} catch (Exception $e) {
    $templates = [];
    $error_message = "Templates error: " . $e->getMessage();
    error_log("Resume builder templates error: " . $e->getMessage());
}

// Get user's jobseeker data for auto-population
try {
$stmt = $conn->prepare("SELECT * FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    if (!$stmt) {
        throw new Exception("Jobseeker prepare failed: " . $conn->error);
    }
$stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        throw new Exception("Jobseeker execute failed: " . $stmt->error);
    }
$jobseekerData = $stmt->get_result()->fetch_assoc();
$stmt->close();
} catch (Exception $e) {
    $jobseekerData = [];
    error_log("Resume builder jobseeker error: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Builder - WorkConnect</title>
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
        })();
    </script>
    <style>
        
        .existing-resumes {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .resume-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .resume-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .resume-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .resume-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .default-badge {
            background: #ffc107;
            color: #333;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .builder-tabs {
            display: flex;
            background: white;
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 0;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px 20px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-weight: bold;
            color: #666;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab-button.active {
            background: white;
            color: #233a8b;
            border-bottom-color: #233a8b;
        }
        
        .tab-button:hover {
            background: #e9ecef;
        }
        
        .tab-content {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 500px;
        }
        
        .tab-panel {
            display: none;
        }
        
        .tab-panel.active {
            display: block;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .form-section h3 {
            margin-top: 0;
            color: #233a8b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .dynamic-list {
            margin-top: 15px;
        }
        
        .list-item {
            background: white;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            position: relative;
        }
        
        .list-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .list-item-title {
            font-weight: bold;
            color: #233a8b;
        }
        
        .remove-item {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .add-item {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .add-item:hover {
            background: #218838;
        }
        
        .template-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .template-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .template-card:hover {
            border-color: #233a8b;
            transform: translateY(-2px);
        }
        
        .template-card.selected {
            border-color: #233a8b;
            background: #f0f4ff;
        }
        
        .template-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 15px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-style: italic;
            color: #666;
        }
        
        .template-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .template-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .preview-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .preview-content {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            min-height: 400px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        
        /* Standalone main content styling */
        .main-content {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
            
        }
        
        .content-section {
            width: 100%;
            max-width: 100%;
        }
        
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-card h1 {
            margin: 0 0 10px 0;
            color: #233a8b;
            font-size: 2rem;
        }
        
        .welcome-card p {
            margin: 0;
            color: #666;
            font-size: 1.1rem;
        }
        
        /* Alert styles removed - using SweetAlert instead */
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #233a8b;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a2d6b;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Image Upload Styling */
        .image-upload-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        
        .image-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            position: relative;
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .no-image-placeholder {
            text-align: center;
            color: #666;
        }
        
        .no-image-placeholder i {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .image-upload-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .upload-btn {
            background: skyblue;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }
        
        .upload-btn:hover {
            background: skyblue;
        }
        
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }
        
        .remove-btn:hover {
            background: #c82333;
        }
        
        .image-help {
            color: #666;
            font-size: 0.8rem;
            text-align: center;
        }
        
        #profile_image {
            display: none;
        }
        
        /* Professional Resume Template Styling */
        .professional-resume {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .resume-header {
            background: linear-gradient(135deg, #233a8b 0%, #1a2d6b 100%);
            color: white;
            padding: 40px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .resume-header-content {
            flex: 1;
        }
        
        .resume-name {
            font-size: 2.8rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            line-height: 1.1;
        }
        
        .resume-title {
            font-size: 1.3rem;
            font-weight: 300;
            margin: 0 0 20px 0;
            opacity: 0.9;
        }
        
        .contact-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 0.95rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .contact-item i {
            width: 16px;
            text-align: center;
        }
        
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .resume-body {
            padding: 40px;
        }
        
        .resume-section {
            margin-bottom: 35px;
        }
        
        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #233a8b;
            margin: 0 0 20px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #233a8b;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background: #ffc107;
        }
        
        .summary-text {
            font-size: 1rem;
            line-height: 1.6;
            color: #444;
            margin: 0;
        }
        
        .experience-item, .education-item, .certification-item {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .experience-item:last-child, .education-item:last-child, .certification-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .item-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .item-company, .item-school, .item-organization {
            font-size: 1rem;
            font-weight: 500;
            color: #233a8b;
            margin: 0 0 5px 0;
        }
        
        .item-dates {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        .item-location {
            font-size: 0.9rem;
            color: #888;
            margin-left: 10px;
        }
        
        .item-description {
            font-size: 0.95rem;
            line-height: 1.5;
            color: #555;
            margin: 10px 0 0 0;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .skill-tag {
            background: #f8f9fa;
            color: #233a8b;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid #e9ecef;
        }
        
        /* SweetAlert customization */
        .swal-wide {
            width: 600px !important;
        }
        
        .swal-wide .swal2-html-container {
            font-size: 14px;
            line-height: 1.5;
        }
        
        .certification-dates {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        /* Modern Template Styles */
        .modern-template {
            background: #f8f9fa;
        }
        
        .modern-header {
            background: white;
            padding: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .modern-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #233a8b;
        }
        
        .modern-name {
            font-size: 2.5rem;
            font-weight: 700;
            color: #233a8b;
            margin: 0 0 5px 0;
        }
        
        .modern-title {
            font-size: 1.2rem;
            color: #666;
            font-weight: 300;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .contact-item-modern {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #555;
        }
        
        .contact-item-modern i {
            color: #233a8b;
            width: 16px;
        }
        
        .modern-body {
            padding: 40px;
        }
        
        .modern-section {
            background: white;
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .modern-section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #233a8b;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #233a8b;
        }
        
        .modern-summary {
            font-size: 1rem;
            line-height: 1.6;
            color: #444;
            margin: 0;
        }
        
        .modern-experience, .modern-education, .modern-certification {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .modern-experience:last-child, .modern-education:last-child, .modern-certification:last-child {
            border-bottom: none;
        }
        
        .modern-exp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .modern-job-title, .modern-degree, .modern-cert-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .modern-company, .modern-school, .modern-cert-org {
            font-size: 1rem;
            color: #233a8b;
            font-weight: 500;
        }
        
        .modern-exp-dates, .modern-edu-dates, .modern-cert-dates {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .modern-description {
            font-size: 0.95rem;
            line-height: 1.5;
            color: #555;
            margin: 0;
        }
        
        .modern-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .modern-skill {
            background: #233a8b;
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Creative Template Styles */
        .creative-template {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .two-column-template {
            background: white;
            color: #333;
            display: flex;
            min-height: auto;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .two-column-left {
            width: 35%;
            background: #ffd700;
            padding: 0;
            position: relative;
        }
        
        .two-column-left-top {
            background: #ffd700;
            padding: 20px;
            text-align: center;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .two-column-left-bottom {
            background: white;
            padding: 15px;
        }
        
        .two-column-right {
            width: 65%;
            padding: 30px 40px;
            background: white;
        }
        
        .two-column-profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 3px solid white;
            display: block;
        }
        
        .two-column-contact {
            color: #333;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .two-column-contact h3 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .two-column-contact p {
            margin: 3px 0;
        }
        
        .two-column-section {
            margin-bottom: 25px;
        }
        
        .two-column-section h3 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #333;
        }
        
        .two-column-section ul {
            margin: 0;
            padding-left: 15px;
        }
        
        .two-column-section li {
            font-size: 12px;
            margin-bottom: 3px;
            line-height: 1.3;
        }
        
        .two-column-name {
            font-size: 32px;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #333;
        }
        
        .two-column-title-line {
            width: 60px;
            height: 3px;
            background: #ffd700;
            margin-bottom: 20px;
        }
        
        .two-column-main-section {
            margin-bottom: 30px;
        }
        
        .two-column-main-section h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #333;
        }
        
        .two-column-main-section p {
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            color: #555;
        }
        
        .two-column-work-item {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .two-column-work-item:last-child {
            border-bottom: none;
        }
        
        .two-column-work-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
        }
        
        .two-column-work-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .two-column-work-company {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin: 0 0 3px 0;
        }
        
        .two-column-work-dates {
            font-size: 11px;
            color: #666;
            font-weight: normal;
        }
        
        .two-column-work-description {
            font-size: 12px;
            line-height: 1.4;
            color: #555;
            margin: 8px 0 0 0;
        }
        
        .two-column-work-description ul {
            margin: 5px 0 0 0;
            padding-left: 15px;
        }
        
        .two-column-work-description li {
            margin-bottom: 2px;
        }
        
        .creative-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .creative-left {
            flex: 1;
        }
        
        .creative-name {
            font-size: 2.8rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .creative-title {
            font-size: 1.3rem;
            font-weight: 300;
            margin: 0 0 20px 0;
            opacity: 0.9;
        }
        
        .creative-contact {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .creative-contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .creative-contact-item i {
            width: 16px;
        }
        
        .creative-right {
            flex-shrink: 0;
        }
        
        .creative-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            object-fit: cover;
        }
        
        .creative-body {
            padding: 40px;
        }
        
        .creative-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .creative-section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255,255,255,0.3);
        }
        
        .creative-summary {
            font-size: 1rem;
            line-height: 1.6;
            margin: 0;
            opacity: 0.9;
        }
        
        .creative-experience, .creative-education, .creative-certification {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .creative-experience:last-child, .creative-education:last-child, .creative-certification:last-child {
            border-bottom: none;
        }
        
        .creative-exp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .creative-job-title, .creative-degree, .creative-cert-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }
        
        .creative-company, .creative-school, .creative-cert-org {
            font-size: 1rem;
            opacity: 0.8;
            font-weight: 500;
        }
        
        .creative-exp-dates, .creative-edu-dates, .creative-cert-dates {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-bottom: 10px;
        }
        
        .creative-description {
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
            opacity: 0.9;
        }
        
        .creative-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .creative-skill {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .welcome-card {
                padding: 20px;
            }
            
            .welcome-card h1 {
                font-size: 1.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .template-selection {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .resume-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .resume-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
        <div class="main-content">
            <div class="content-section">
                <div class="welcome-card">
                    <h1><i class="fas fa-file-alt"></i> Resume Builder</h1>
                    <p>Create professional resumes with our easy-to-use builder</p>
                </div>
                
                <!-- Success/Error messages will be handled by SweetAlert -->
                
                <?php if (!empty($resumes)): ?>
                    <div class="existing-resumes">
                        <h3><i class="fas fa-folder-open"></i> Your Existing Resumes</h3>
                        <?php foreach ($resumes as $resume): ?>
                            <div class="resume-item">
                                <div class="resume-info">
                                    <h4>
                                        <?php echo htmlspecialchars($resume['resume_name']); ?>
                                        <?php if ($resume['is_default']): ?>
                                            <span class="default-badge">DEFAULT</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p>
                                        Template: <?php echo htmlspecialchars($resume['template_name']); ?> | 
                                        Created: <?php echo date('M j, Y', strtotime($resume['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="resume-actions">
                                    <button class="btn btn-primary btn-sm" onclick="editResume(<?php echo $resume['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="generatePDF(<?php echo $resume['id']; ?>, '<?php echo addslashes($resume['resume_name']); ?>')">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="deleteResume(<?php echo $resume['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="builder-tabs">
                    <button class="tab-button active" onclick="showTab('personal')">
                        <i class="fas fa-user"></i> Personal Info
                    </button>
                    <button class="tab-button" onclick="showTab('experience')">
                        <i class="fas fa-briefcase"></i> Experience
                    </button>
                    <button class="tab-button" onclick="showTab('education')">
                        <i class="fas fa-graduation-cap"></i> Education
                    </button>
                    <button class="tab-button" onclick="showTab('skills')">
                        <i class="fas fa-cogs"></i> Skills
                    </button>
                    <button class="tab-button" onclick="showTab('template')">
                        <i class="fas fa-palette"></i> Template
                    </button>
                    <button class="tab-button" onclick="showTab('preview')">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                </div>
                
                <div class="tab-content">
                    <form id="resumeForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_resume">
                        <input type="hidden" name="resume_id" id="resumeId" value="">
                        
                        <!-- Personal Information Tab -->
                        <div id="personal" class="tab-panel active">
                            <div class="form-section">
                                <h3><i class="fas fa-user"></i> Personal Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="firstname">First Name *</label>
                                        <input type="text" id="firstname" name="personal_info[firstname]" required 
                                               value="<?php echo htmlspecialchars($jobseekerData['firstname'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="lastname">Last Name *</label>
                                        <input type="text" id="lastname" name="personal_info[lastname]" required 
                                               value="<?php echo htmlspecialchars($jobseekerData['surname'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" id="email" name="personal_info[email]" required 
                                               value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="personal_info[phone]" 
                                               value="<?php echo htmlspecialchars($jobseekerData['contact_number'] ?? ''); ?>">
                                    </div>
                                </div>
                            <br>
                            <!-- Profile Image Upload -->
                            <div class="form-group">
                                <label for="profile_image" style="display: flex; justify-content: center; align-items: center;">Profile Photo</label>
                                <div class="image-upload-container">
                                    <div class="image-preview" id="imagePreview">
                                        <img id="previewImg" src="" alt="Profile Preview" style="display: none;">
                                        <div class="no-image-placeholder">
                                            <i class="fas fa-user-circle"></i>
                                            <span>No image selected</span>
                                        </div>
                                    </div>
                                    <div class="image-upload-controls">
                                        <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(this)">
                                        <label for="profile_image" class="upload-btn">
                                            <i class="fas fa-upload"></i> Choose Image
                                        </label>
                                        <button type="button" class="remove-btn" onclick="removeImage()" style="display: none;">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                    <small class="image-help">Recommended: Square image, max 2MB, JPG/PNG format</small>
                                </div>
                            </div>
                            
                                <div class="form-group">
                                    <label for="summary">Professional Summary</label>
                                    <textarea id="summary" name="personal_info[summary]" 
                                              placeholder="Write a brief summary of your professional background and career objectives..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Work Experience Tab -->
                        <div id="experience" class="tab-panel">
                            <div class="form-section">
                                <h3><i class="fas fa-briefcase"></i> Work Experience</h3>
                                <div id="experienceList" class="dynamic-list">
                                    <div class="list-item">
                                        <div class="list-item-header">
                                            <span class="list-item-title">Work Experience #1</span>
                                        </div>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Job Title *</label>
                                                <input type="text" name="work_experience[0][job_title]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Company *</label>
                                                <input type="text" name="work_experience[0][company]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Start Date</label>
                                                <input type="month" name="work_experience[0][start_date]">
                                            </div>
                                            <div class="form-group">
                                                <label>End Date</label>
                                                <input type="month" name="work_experience[0][end_date]">
                                            </div>
                                            <div class="form-group">
                                                <label>Location</label>
                                                <input type="text" name="work_experience[0][location]">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="work_experience[0][description]" 
                                                      placeholder="Describe your responsibilities and achievements..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="add-item" onclick="addExperience()">
                                    <i class="fas fa-plus"></i> Add Another Experience
                                </button>
                            </div>
                        </div>
                        
                        <!-- Education Tab -->
                        <div id="education" class="tab-panel">
                            <div class="form-section">
                                <h3><i class="fas fa-graduation-cap"></i> Education</h3>
                                <div id="educationList" class="dynamic-list">
                                    <div class="list-item">
                                        <div class="list-item-header">
                                            <span class="list-item-title">Education #1</span>
                                        </div>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Degree *</label>
                                                <input type="text" name="education[0][degree]" required 
                                                       placeholder="e.g., Bachelor of Science">
                                            </div>
                                            <div class="form-group">
                                                <label>Field of Study *</label>
                                                <input type="text" name="education[0][field]" required 
                                                       placeholder="e.g., Computer Science">
                                            </div>
                                            <div class="form-group">
                                                <label>School/University *</label>
                                                <input type="text" name="education[0][school]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Graduation Year</label>
                                                <input type="number" name="education[0][graduation_year]" 
                                                       min="1950" max="2030">
                                            </div>
                                            <div class="form-group">
                                                <label>GPA (Optional)</label>
                                                <input type="text" name="education[0][gpa]" 
                                                       placeholder="e.g., 3.5/4.0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="add-item" onclick="addEducation()">
                                    <i class="fas fa-plus"></i> Add Another Education
                                </button>
                            </div>
                        </div>
                        
                        <!-- Skills Tab -->
                        <div id="skills-tab" class="tab-panel">
                            <div class="form-section">
                                <h3><i class="fas fa-cogs"></i> Skills & Certifications</h3>
                                
                                <div class="form-group">
                                    <label for="skills">Skills (comma-separated)</label>
                                    <textarea id="skills" name="skills" 
                                              placeholder="e.g., PHP, MySQL, JavaScript, Project Management, Communication"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="languages">Languages (comma-separated) </label>
                                    <textarea id="languages" name="languages" 
                                              placeholder="e.g., English (Native), Spanish (Advanced), French (Intermediate)"></textarea>
                                </div>
                                
                                <div id="certificationsList" class="dynamic-list">
                                    <div class="list-item">
                                        <div class="list-item-header">
                                            <span class="list-item-title">Certification #1</span>
                                        </div>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Certification Name *</label>
                                                <input type="text" name="certifications[0][name]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Issuing Organization</label>
                                                <input type="text" name="certifications[0][organization]">
                                            </div>
                                            <div class="form-group">
                                                <label>Issue Date</label>
                                                <input type="month" name="certifications[0][issue_date]">
                                            </div>
                                            <div class="form-group">
                                                <label>Expiry Date</label>
                                                <input type="month" name="certifications[0][expiry_date]">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="add-item" onclick="addCertification()">
                                    <i class="fas fa-plus"></i> Add Another Certification
                                </button>
                            </div>
                        </div>
                        
                        <!-- Template Selection Tab -->
                        <div id="template" class="tab-panel">
                            <div class="form-section">
                                <h3><i class="fas fa-palette"></i> Choose Template</h3>
                                <div class="template-selection">
                                    <?php foreach ($templates as $template): ?>
                                        <div class="template-card" onclick="selectTemplate(<?php echo $template['id']; ?>)">
                                            <div class="template-preview">
                                                <i class="fas fa-file-alt" style="font-size: 3rem; color: #233a8b;"></i>
                                            </div>
                                            <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                                            <div class="template-description"><?php echo htmlspecialchars($template['description']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="template_id" id="selectedTemplate" value="<?php echo $templates[0]['id'] ?? '1'; ?>">
                                
                                <div class="form-group">
                                    <label for="resume_name">Resume Name *</label>
                                    <input type="text" id="resume_name" name="resume_name" required 
                                           placeholder="e.g., Software Developer Resume">
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="is_default" value="1"> 
                                        Set as default resume
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview Tab -->
                        <div id="preview" class="tab-panel">
                            <div class="preview-section">
                                <div class="preview-header">
                                    <h3><i class="fas fa-eye"></i> Resume Preview</h3>
                                    <div class="preview-controls">
                                    <button type="button" class="btn btn-primary" onclick="updatePreview()">
                                        <i class="fas fa-sync"></i> Refresh Preview
                                    </button>
                                        <button type="button" class="btn btn-secondary" onclick="debugFormData()">
                                            <i class="fas fa-bug"></i> Debug Data
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="checkFormStructure()">
                                            <i class="fas fa-search"></i> Check Form
                                    </button>
                                    </div>
                                </div>
                                <div class="preview-content" id="previewContent">
                                    <p style="text-align: center; color: #666; margin-top: 100px;">
                                        <i class="fas fa-file-alt" style="font-size: 3rem; margin-bottom: 20px;"></i><br>
                                        Fill in your information in the previous tabs to see a preview of your resume.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                           
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Save Resume
                            </button>
                        </div>
                    </form>
            </div>
        </div>
    </div>

    <script>
        let experienceCount = 1;
        let educationCount = 1;
        let certificationCount = 1;
        
        function showTab(tabName, event = null) {
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Handle the skills tab with the new ID
            const tabId = tabName === 'skills' ? 'skills-tab' : tabName;
            document.getElementById(tabId).classList.add('active');
            
            // Find the correct button to activate
            const targetButton = event ? event.target : document.querySelector(`button[onclick*="showTab('${tabName}')"]`);
            if (targetButton) {
                targetButton.classList.add('active');
            }
        }
        
        function selectTemplate(templateId) {
            document.querySelectorAll('.template-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            event.target.closest('.template-card').classList.add('selected');
            document.getElementById('selectedTemplate').value = templateId;
            
            console.log('Template selected:', templateId);
            console.log('Selected template value set to:', document.getElementById('selectedTemplate').value);
            
            // Update preview if we're on the preview tab
            const previewTab = document.getElementById('preview');
            if (previewTab.classList.contains('active')) {
                updatePreview();
            }
        }
        
        function addExperience() {
            const container = document.getElementById('experienceList');
            const newItem = document.createElement('div');
            newItem.className = 'list-item';
            newItem.innerHTML = `
                <div class="list-item-header">
                    <span class="list-item-title">Work Experience #${experienceCount + 1}</span>
                    <button type="button" class="remove-item" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Job Title *</label>
                        <input type="text" name="work_experience[${experienceCount}][job_title]" required>
                    </div>
                    <div class="form-group">
                        <label>Company *</label>
                        <input type="text" name="work_experience[${experienceCount}][company]" required>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="month" name="work_experience[${experienceCount}][start_date]">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="month" name="work_experience[${experienceCount}][end_date]">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="work_experience[${experienceCount}][location]">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="work_experience[${experienceCount}][description]" 
                              placeholder="Describe your responsibilities and achievements..."></textarea>
                </div>
            `;
            container.appendChild(newItem);
            experienceCount++;
        }
        
        function addEducation() {
            const container = document.getElementById('educationList');
            const newItem = document.createElement('div');
            newItem.className = 'list-item';
            newItem.innerHTML = `
                <div class="list-item-header">
                    <span class="list-item-title">Education #${educationCount + 1}</span>
                    <button type="button" class="remove-item" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Degree *</label>
                        <input type="text" name="education[${educationCount}][degree]" required 
                               placeholder="e.g., Bachelor of Science">
                    </div>
                    <div class="form-group">
                        <label>Field of Study *</label>
                        <input type="text" name="education[${educationCount}][field]" required 
                               placeholder="e.g., Computer Science">
                    </div>
                    <div class="form-group">
                        <label>School/University *</label>
                        <input type="text" name="education[${educationCount}][school]" required>
                    </div>
                    <div class="form-group">
                        <label>Graduation Year</label>
                        <input type="number" name="education[${educationCount}][graduation_year]" 
                               min="1950" max="2030">
                    </div>
                    <div class="form-group">
                        <label>GPA (Optional)</label>
                        <input type="text" name="education[${educationCount}][gpa]" 
                               placeholder="e.g., 3.5/4.0">
                    </div>
                </div>
            `;
            container.appendChild(newItem);
            educationCount++;
        }
        
        function addCertification() {
            const container = document.getElementById('certificationsList');
            const newItem = document.createElement('div');
            newItem.className = 'list-item';
            newItem.innerHTML = `
                <div class="list-item-header">
                    <span class="list-item-title">Certification #${certificationCount + 1}</span>
                    <button type="button" class="remove-item" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Certification Name *</label>
                        <input type="text" name="certifications[${certificationCount}][name]" required>
                    </div>
                    <div class="form-group">
                        <label>Issuing Organization</label>
                        <input type="text" name="certifications[${certificationCount}][organization]">
                    </div>
                    <div class="form-group">
                        <label>Issue Date</label>
                        <input type="month" name="certifications[${certificationCount}][issue_date]">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="month" name="certifications[${certificationCount}][expiry_date]">
                    </div>
                </div>
            `;
            container.appendChild(newItem);
            certificationCount++;
        }
        
        function removeItem(button) {
            button.closest('.list-item').remove();
        }
        
        // Image handling functions
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                        title: 'File Too Large',
                        text: 'Please select an image smaller than 2MB.',
                        icon: 'error',
                confirmButtonText: 'OK'
            });
                    input.value = '';
                    return;
        }
        
                // Validate file type
                if (!file.type.startsWith('image/')) {
            Swal.fire({
                        title: 'Invalid File Type',
                        text: 'Please select a valid image file (JPG, PNG, etc.).',
                        icon: 'error',
                confirmButtonText: 'OK'
            });
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImg = document.getElementById('previewImg');
                    const placeholder = document.querySelector('.no-image-placeholder');
                    const removeBtn = document.querySelector('.remove-btn');
                    
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeBtn.style.display = 'inline-flex';
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removeImage() {
            const input = document.getElementById('profile_image');
            const previewImg = document.getElementById('previewImg');
            const placeholder = document.querySelector('.no-image-placeholder');
            const removeBtn = document.querySelector('.remove-btn');
            
            input.value = '';
            previewImg.src = '';
            previewImg.style.display = 'none';
            placeholder.style.display = 'block';
            removeBtn.style.display = 'none';
        }
        
        function updatePreview() {
            const previewContent = document.getElementById('previewContent');
            
            // Collect form data
            const formData = collectFormData();
            
            // Check if we have any data to show
            const hasData = formData.personal_info.firstname || 
                           formData.personal_info.lastname || 
                           formData.personal_info.email ||
                           formData.personal_info.phone ||
                           formData.personal_info.summary ||
                           formData.work_experience.length > 0 || 
                           formData.education.length > 0 || 
                           formData.skills || 
                           formData.certifications.length > 0;
            
            if (!hasData) {
                previewContent.innerHTML = `
                    <div style="text-align: center; padding: 50px; color: #666;">
                        <i class="fas fa-file-alt" style="font-size: 3rem; margin-bottom: 20px; color: #233a8b;"></i>
                        <h3>No Data to Preview</h3>
                        <p>Please fill in your information in the previous tabs to see a preview of your resume.</p>
                        <p><strong>Debug Info:</strong> Check browser console for collected data.</p>
                    </div>
                `;
                return;
            }
            
            // Generate preview HTML
            const previewHTML = generatePreviewHTML(formData);
            previewContent.innerHTML = previewHTML;
        }
        
        function collectFormData() {
            console.log('=== COLLECTING FORM DATA ===');
            
            // Create FormData object to match backend expectations
            const formData = new FormData();
            
            // Collect personal info
            const personalInputs = document.querySelectorAll('input[name^="personal_info"], textarea[name^="personal_info"]');
            personalInputs.forEach(input => {
                const name = input.name;
                if (name.includes('[') && name.includes(']')) {
                    const key = name.match(/\[([^\]]+)\]/)[1];
                    formData.append(`personal_info[${key}]`, input.value);
                }
            });
            
            // Collect work experience - parse the nested array format correctly
            const workExperienceInputs = document.querySelectorAll('input[name^="work_experience"], textarea[name^="work_experience"]');
            console.log('Found work experience inputs:', workExperienceInputs.length);
            workExperienceInputs.forEach(input => {
                console.log('Work experience input:', input.name, '=', input.value);
                formData.append(input.name, input.value);
            });
            
            // Collect education - parse the nested array format correctly
            const educationInputs = document.querySelectorAll('input[name^="education"]');
            console.log('Found education inputs:', educationInputs.length);
            educationInputs.forEach(input => {
                console.log('Education input:', input.name, '=', input.value);
                formData.append(input.name, input.value);
            });
            
            // Collect skills
            const skillsTextarea = document.getElementById('skills');
            if (skillsTextarea) {
                console.log('Skills value:', skillsTextarea.value);
                formData.append('skills', skillsTextarea.value);
            }
            
            // Collect languages
            const languagesTextarea = document.getElementById('languages');
            if (languagesTextarea) {
                console.log('Languages value:', languagesTextarea.value);
                formData.append('languages', languagesTextarea.value);
            }
            
            // Collect certifications - parse the nested array format correctly
            const certificationInputs = document.querySelectorAll('input[name^="certifications"]');
            console.log('Found certification inputs:', certificationInputs.length);
            certificationInputs.forEach(input => {
                console.log('Certification input:', input.name, '=', input.value);
                formData.append(input.name, input.value);
            });
            
            // For preview purposes, also create a structured object
            const previewData = {
                personal_info: {},
                work_experience: [],
                education: [],
                skills: '',
                certifications: []
            };
            
            // Build preview data structure
            personalInputs.forEach(input => {
                const name = input.name;
                if (name.includes('[') && name.includes(']')) {
                    const key = name.match(/\[([^\]]+)\]/)[1];
                    previewData.personal_info[key] = input.value;
                }
            });
            
            // Parse work experience for preview
            const workExpMap = new Map();
            workExperienceInputs.forEach(input => {
                const name = input.name;
                const match = name.match(/work_experience\[(\d+)\]\[([^\]]+)\]/);
                if (match) {
                    const index = parseInt(match[1]);
                    const field = match[2];
                    if (!workExpMap.has(index)) {
                        workExpMap.set(index, {});
                    }
                    workExpMap.get(index)[field] = input.value;
                }
            });
            previewData.work_experience = Array.from(workExpMap.values()).filter(exp => 
                exp.job_title || exp.company || exp.description || exp.start_date || exp.end_date || exp.location
            );
            
            // Parse education for preview
            const eduMap = new Map();
            educationInputs.forEach(input => {
                const name = input.name;
                const match = name.match(/education\[(\d+)\]\[([^\]]+)\]/);
                if (match) {
                    const index = parseInt(match[1]);
                    const field = match[2];
                    if (!eduMap.has(index)) {
                        eduMap.set(index, {});
                    }
                    eduMap.get(index)[field] = input.value;
                }
            });
            previewData.education = Array.from(eduMap.values()).filter(edu => 
                edu.degree || edu.field || edu.school || edu.graduation_year || edu.gpa
            );
            
            // Parse certifications for preview
            const certMap = new Map();
            certificationInputs.forEach(input => {
                const name = input.name;
                const match = name.match(/certifications\[(\d+)\]\[([^\]]+)\]/);
                if (match) {
                    const index = parseInt(match[1]);
                    const field = match[2];
                    if (!certMap.has(index)) {
                        certMap.set(index, {});
                    }
                    certMap.get(index)[field] = input.value;
                }
            });
            previewData.certifications = Array.from(certMap.values()).filter(cert => 
                cert.name || cert.organization || cert.issue_date || cert.expiry_date
            );
            
            previewData.skills = skillsTextarea ? skillsTextarea.value : '';
            previewData.languages = languagesTextarea ? languagesTextarea.value : '';
            
            console.log('FormData entries:');
            for (let [key, value] of formData.entries()) {
                console.log(key, '=', value);
            }
            
            console.log('Preview data structure:', previewData);
            console.log('Work experience items:', previewData.work_experience.length);
            console.log('Education items:', previewData.education.length);
            console.log('Certification items:', previewData.certifications.length);
            
            return previewData;
        }
        
        function debugFormData() {
            const formData = collectFormData();
            console.log('=== DEBUG FORM DATA ===');
            console.log('Personal Info:', formData.personal_info);
            console.log('Work Experience:', formData.work_experience);
            console.log('Education:', formData.education);
            console.log('Skills:', formData.skills);
            console.log('Certifications:', formData.certifications);
            
            // Show in alert for easy viewing
            let debugInfo = '=== FORM DATA DEBUG ===\n\n';
            debugInfo += 'Personal Info:\n';
            Object.keys(formData.personal_info).forEach(key => {
                debugInfo += `  ${key}: "${formData.personal_info[key]}"\n`;
            });
            
            debugInfo += '\nWork Experience (' + formData.work_experience.length + ' items):\n';
            formData.work_experience.forEach((exp, index) => {
                debugInfo += `  Experience ${index + 1}:\n`;
                Object.keys(exp).forEach(key => {
                    debugInfo += `    ${key}: "${exp[key]}"\n`;
                });
            });
            
            debugInfo += '\nEducation (' + formData.education.length + ' items):\n';
            formData.education.forEach((edu, index) => {
                debugInfo += `  Education ${index + 1}:\n`;
                Object.keys(edu).forEach(key => {
                    debugInfo += `    ${key}: "${edu[key]}"\n`;
                });
            });
            
            debugInfo += `\nSkills: "${formData.skills}"\n`;
            
            debugInfo += '\nCertifications (' + formData.certifications.length + ' items):\n';
            formData.certifications.forEach((cert, index) => {
                debugInfo += `  Certification ${index + 1}:\n`;
                Object.keys(cert).forEach(key => {
                    debugInfo += `    ${key}: "${cert[key]}"\n`;
                });
            });
            
            alert(debugInfo);
        }
        
        function checkFormStructure() {
            console.log('=== FORM STRUCTURE CHECK ===');
            
            // Check if containers exist
            const experienceList = document.getElementById('experienceList');
            const educationList = document.getElementById('educationList');
            const certificationsList = document.getElementById('certificationsList');
            const skillsElement = document.getElementById('skills');
            
            console.log('Experience List exists:', !!experienceList);
            console.log('Education List exists:', !!educationList);
            console.log('Certifications List exists:', !!certificationsList);
            console.log('Skills element exists:', !!skillsElement);
            
            if (experienceList) {
                const experienceItems = experienceList.querySelectorAll('.list-item');
                console.log('Experience items found:', experienceItems.length);
                experienceItems.forEach((item, index) => {
                    const inputs = item.querySelectorAll('input, textarea');
                    console.log(`Experience ${index + 1} has ${inputs.length} inputs`);
                    inputs.forEach(input => {
                        console.log(`  - ${input.name}: "${input.value}"`);
                    });
                });
            }
            
            if (educationList) {
                const educationItems = educationList.querySelectorAll('.list-item');
                console.log('Education items found:', educationItems.length);
                educationItems.forEach((item, index) => {
                    const inputs = item.querySelectorAll('input');
                    console.log(`Education ${index + 1} has ${inputs.length} inputs`);
                    inputs.forEach(input => {
                        console.log(`  - ${input.name}: "${input.value}"`);
                    });
                });
            }
            
            if (certificationsList) {
                const certificationItems = certificationsList.querySelectorAll('.list-item');
                console.log('Certification items found:', certificationItems.length);
                certificationItems.forEach((item, index) => {
                    const inputs = item.querySelectorAll('input');
                    console.log(`Certification ${index + 1} has ${inputs.length} inputs`);
                    inputs.forEach(input => {
                        console.log(`  - ${input.name}: "${input.value}"`);
                    });
                });
            }
            
            if (skillsElement) {
                console.log('Skills value:', skillsElement.value);
            }
            
            alert('Form structure check completed. Check browser console for details.');
        }
        
        function generatePreviewHTML(data) {
            console.log('=== GENERATING PREVIEW HTML ===');
            console.log('Input data:', data);
            
            // Get profile image
            const profileImage = document.getElementById('previewImg');
            const imageSrc = profileImage && profileImage.style.display !== 'none' ? profileImage.src : '';
            console.log('Profile image src:', imageSrc);
            
            // Get selected template
            const selectedTemplate = document.getElementById('selectedTemplate').value;
            console.log('Selected template:', selectedTemplate);
            
            // Generate HTML based on selected template
            let result;
            switch(selectedTemplate) {
                case '1':
                    console.log('Using Classic template');
                    result = generateClassicTemplate(data, imageSrc);
                    break;
                case '2':
                    console.log('Using Modern template');
                    result = generateModernTemplate(data, imageSrc);
                    break;
                case '3':
                    console.log('Using Creative template');
                    result = generateCreativeTemplate(data, imageSrc);
                    break;
                case '4':
                    console.log('Using Two-Column template');
                    result = generateTwoColumnTemplate(data, imageSrc);
                    break;
                default:
                    console.log('Using default Classic template');
                    result = generateClassicTemplate(data, imageSrc);
            }
            
            console.log('Generated HTML length:', result.length);
            return result;
        }
        
        function generateClassicTemplate(data, imageSrc) {
            console.log('=== GENERATING CLASSIC TEMPLATE ===');
            console.log('Data received:', data);
            console.log('Work experience length:', data.work_experience.length);
            console.log('Education length:', data.education.length);
            console.log('Skills:', data.skills);
            console.log('Certifications length:', data.certifications.length);
            
            let html = `
                <div class="professional-resume classic-template">
                    <!-- Header Section -->
                    <div class="resume-header">
                        <div class="resume-header-content">
                            <h1 class="resume-name">${data.personal_info.firstname || ''} ${data.personal_info.lastname || ''}</h1>
                            <div class="resume-title">Professional Resume</div>
                            <div class="contact-info">
                                ${data.personal_info.email ? `<div class="contact-item"><i class="fas fa-envelope"></i> ${data.personal_info.email}</div>` : ''}
                                ${data.personal_info.phone ? `<div class="contact-item"><i class="fas fa-phone"></i> ${data.personal_info.phone}</div>` : ''}
                                ${data.personal_info.location ? `<div class="contact-item"><i class="fas fa-map-marker-alt"></i> ${data.personal_info.location}</div>` : ''}
                                ${data.personal_info.linkedin ? `<div class="contact-item"><i class="fab fa-linkedin"></i> <a href="${data.personal_info.linkedin}" style="color: white; text-decoration: none;">LinkedIn Profile</a></div>` : ''}
                                </div>
                        </div>
                        ${imageSrc ? `<img src="${imageSrc}" alt="Profile Photo" class="profile-photo">` : ''}
                    </div>
                    
                    <!-- Body Section -->
                    <div class="resume-body">
            `;
            
            // Professional Summary
            if (data.personal_info.summary) {
                console.log('Adding Professional Summary section');
                html += `
                    <div class="resume-section">
                        <h2 class="section-title">Professional Summary</h2>
                        <p class="summary-text">${data.personal_info.summary}</p>
                    </div>
                `;
            }
            
            // Work Experience
            if (data.work_experience.length > 0) {
                console.log('Adding Work Experience section with', data.work_experience.length, 'items');
                html += `
                    <div class="resume-section">
                        <h2 class="section-title">Work Experience</h2>
                `;
                
                data.work_experience.forEach((exp, index) => {
                    console.log(`Processing work experience ${index + 1}:`, exp);
                    if (exp.job_title || exp.company) {
                        html += `
                            <div class="experience-item">
                                <div class="item-header">
                                    <div>
                                        <h3 class="item-title">${exp.job_title || ''}</h3>
                                        <h4 class="item-company">${exp.company || ''}</h4>
                                    </div>
                                    <div class="item-dates">
                                        ${exp.start_date ? exp.start_date : ''} ${exp.end_date ? `- ${exp.end_date}` : (exp.start_date ? '- Present' : '')}
                                        ${exp.location ? `<span class="item-location">${exp.location}</span>` : ''}
                                    </div>
                                </div>
                                ${exp.description ? `<p class="item-description">${exp.description}</p>` : ''}
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
                        } else {
                console.log('No work experience data to display');
            }
            
            // Education
            if (data.education.length > 0) {
                console.log('Adding Education section with', data.education.length, 'items');
                html += `
                    <div class="resume-section">
                        <h2 class="section-title">Education</h2>
                `;
                
                data.education.forEach((edu, index) => {
                    console.log(`Processing education ${index + 1}:`, edu);
                    if (edu.degree || edu.field || edu.school) {
                        html += `
                            <div class="education-item">
                                <div class="item-header">
                                    <div>
                                        <h3 class="item-title">${edu.degree || ''} in ${edu.field || ''}</h3>
                                        <h4 class="item-school">${edu.school || ''}</h4>
                                    </div>
                                    <div class="item-dates">
                                        ${edu.graduation_year ? `Graduated: ${edu.graduation_year}` : ''}
                                        ${edu.gpa ? ` | GPA: ${edu.gpa}` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
                    } else {
                console.log('No education data to display');
            }
            
            // Skills
            if (data.skills) {
                console.log('Adding Skills section:', data.skills);
                const skillsArray = data.skills.split(',').map(skill => skill.trim()).filter(skill => skill);
                html += `
                    <div class="resume-section">
                        <h2 class="section-title">Skills</h2>
                        <div class="skills-list">
                            ${skillsArray.map(skill => `<span class="skill-tag">${skill}</span>`).join('')}
                        </div>
                    </div>
                `;
            } else {
                console.log('No skills data to display');
            }
            
            // Certifications
            if (data.certifications.length > 0) {
                console.log('Adding Certifications section with', data.certifications.length, 'items');
                html += `
                    <div class="resume-section">
                        <h2 class="section-title">Certifications</h2>
                `;
                
                data.certifications.forEach((cert, index) => {
                    console.log(`Processing certification ${index + 1}:`, cert);
                    if (cert.name) {
                        html += `
                            <div class="certification-item">
                                <div class="item-header">
                                    <div>
                                        <h3 class="item-title">${cert.name}</h3>
                                        ${cert.organization ? `<h4 class="item-organization">${cert.organization}</h4>` : ''}
                                    </div>
                                    <div class="certification-dates">
                                        ${cert.issue_date ? `Issued: ${cert.issue_date}` : ''}
                                        ${cert.expiry_date ? ` | Expires: ${cert.expiry_date}` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
            } else {
                console.log('No certifications data to display');
            }
            
            html += `
                    </div>
                </div>
            `;
            
            console.log('Final HTML generated, length:', html.length);
            return html;
        }
        
        function generateModernTemplate(data, imageSrc) {
            let html = `
                <div class="professional-resume modern-template">
                    <!-- Modern Header -->
                    <div class="modern-header">
                        <div class="header-left">
                            ${imageSrc ? `<img src="${imageSrc}" alt="Profile Photo" class="modern-photo">` : ''}
                            <div class="header-info">
                                <h1 class="modern-name">${data.personal_info.firstname || ''} ${data.personal_info.lastname || ''}</h1>
                                <div class="modern-title">Professional Resume</div>
                            </div>
                        </div>
                        <div class="header-right">
                            <div class="contact-grid">
                                ${data.personal_info.email ? `<div class="contact-item-modern"><i class="fas fa-envelope"></i><span>${data.personal_info.email}</span></div>` : ''}
                                ${data.personal_info.phone ? `<div class="contact-item-modern"><i class="fas fa-phone"></i><span>${data.personal_info.phone}</span></div>` : ''}
                                ${data.personal_info.location ? `<div class="contact-item-modern"><i class="fas fa-map-marker-alt"></i><span>${data.personal_info.location}</span></div>` : ''}
                                ${data.personal_info.linkedin ? `<div class="contact-item-modern"><i class="fab fa-linkedin"></i><span><a href="${data.personal_info.linkedin}" style="color: inherit;">LinkedIn</a></span></div>` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modern Body -->
                    <div class="modern-body">
            `;
            
            // Professional Summary
            if (data.personal_info.summary) {
                html += `
                    <div class="modern-section">
                        <h2 class="modern-section-title">Professional Summary</h2>
                        <p class="modern-summary">${data.personal_info.summary}</p>
                    </div>
                `;
            }
            
            // Work Experience
            if (data.work_experience.length > 0) {
                html += `
                    <div class="modern-section">
                        <h2 class="modern-section-title">Work Experience</h2>
                `;
                
                data.work_experience.forEach(exp => {
                    if (exp.job_title && exp.company) {
                        html += `
                            <div class="modern-experience">
                                <div class="modern-exp-header">
                                    <h3 class="modern-job-title">${exp.job_title}</h3>
                                    <span class="modern-company">${exp.company}</span>
                                </div>
                                <div class="modern-exp-dates">
                                    ${exp.start_date ? exp.start_date : ''} ${exp.end_date ? `- ${exp.end_date}` : (exp.start_date ? '- Present' : '')}
                                    ${exp.location ? ` | ${exp.location}` : ''}
                                </div>
                                ${exp.description ? `<p class="modern-description">${exp.description}</p>` : ''}
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
            }
            
            // Education
            if (data.education.length > 0) {
                html += `
                    <div class="modern-section">
                        <h2 class="modern-section-title">Education</h2>
                `;
                
                data.education.forEach(edu => {
                    if (edu.degree && edu.field && edu.school) {
                        html += `
                            <div class="modern-education">
                                <h3 class="modern-degree">${edu.degree} in ${edu.field}</h3>
                                <span class="modern-school">${edu.school}</span>
                                <div class="modern-edu-dates">
                                    ${edu.graduation_year ? `Graduated: ${edu.graduation_year}` : ''}
                                    ${edu.gpa ? ` | GPA: ${edu.gpa}` : ''}
                                </div>
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
            }
            
            // Skills
            if (data.skills) {
                const skillsArray = data.skills.split(',').map(skill => skill.trim()).filter(skill => skill);
                html += `
                    <div class="modern-section">
                        <h2 class="modern-section-title">Skills</h2>
                        <div class="modern-skills">
                            ${skillsArray.map(skill => `<span class="modern-skill">${skill}</span>`).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Certifications
            if (data.certifications.length > 0) {
                html += `
                    <div class="modern-section">
                        <h2 class="modern-section-title">Certifications</h2>
                `;
                
                data.certifications.forEach(cert => {
                    if (cert.name) {
                        html += `
                            <div class="modern-certification">
                                <h3 class="modern-cert-name">${cert.name}</h3>
                                ${cert.organization ? `<span class="modern-cert-org">${cert.organization}</span>` : ''}
                                <div class="modern-cert-dates">
                                    ${cert.issue_date ? `Issued: ${cert.issue_date}` : ''}
                                    ${cert.expiry_date ? ` | Expires: ${cert.expiry_date}` : ''}
                                </div>
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
            }
            
            html += `
                    </div>
                </div>
            `;
            
            return html;
        }
        
        function generateCreativeTemplate(data, imageSrc) {
            let html = `
                <div class="professional-resume creative-template">
                    <!-- Creative Header -->
                    <div class="creative-header">
                        <div class="creative-left">
                            <div class="creative-info">
                                <h1 class="creative-name">${data.personal_info.firstname || ''} ${data.personal_info.lastname || ''}</h1>
                                <div class="creative-title">Professional Resume</div>
                                <div class="creative-contact">
                                    ${data.personal_info.email ? `<div class="creative-contact-item"><i class="fas fa-envelope"></i> ${data.personal_info.email}</div>` : ''}
                                    ${data.personal_info.phone ? `<div class="creative-contact-item"><i class="fas fa-phone"></i> ${data.personal_info.phone}</div>` : ''}
                                    ${data.personal_info.location ? `<div class="creative-contact-item"><i class="fas fa-map-marker-alt"></i> ${data.personal_info.location}</div>` : ''}
                                    ${data.personal_info.linkedin ? `<div class="creative-contact-item"><i class="fab fa-linkedin"></i> <a href="${data.personal_info.linkedin}" style="color: inherit;">LinkedIn</a></div>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="creative-right">
                            ${imageSrc ? `<img src="${imageSrc}" alt="Profile Photo" class="creative-photo">` : ''}
                        </div>
                    </div>
                    
                    <!-- Creative Body -->
                    <div class="creative-body">
            `;
            
            // Professional Summary
            if (data.personal_info.summary) {
                html += `
                    <div class="creative-section">
                        <h2 class="creative-section-title">Professional Summary</h2>
                        <p class="creative-summary">${data.personal_info.summary}</p>
                    </div>
                `;
            }
            
            // Work Experience
            if (data.work_experience.length > 0) {
                html += `
                    <div class="creative-section">
                        <h2 class="creative-section-title">Work Experience</h2>
                `;
                
                data.work_experience.forEach(exp => {
                    if (exp.job_title && exp.company) {
                        html += `
                            <div class="creative-experience">
                                <div class="creative-exp-header">
                                    <h3 class="creative-job-title">${exp.job_title}</h3>
                                    <span class="creative-company">${exp.company}</span>
                                </div>
                                <div class="creative-exp-dates">
                                    ${exp.start_date ? exp.start_date : ''} ${exp.end_date ? `- ${exp.end_date}` : (exp.start_date ? '- Present' : '')}
                                    ${exp.location ? ` | ${exp.location}` : ''}
                                </div>
                                ${exp.description ? `<p class="creative-description">${exp.description}</p>` : ''}
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
            }
            
            // Education
            if (data.education.length > 0) {
                html += `
                    <div class="creative-section">
                        <h2 class="creative-section-title">Education</h2>
                `;
                
                data.education.forEach(edu => {
                    if (edu.degree && edu.field && edu.school) {
                        html += `
                            <div class="creative-education">
                                <h3 class="creative-degree">${edu.degree} in ${edu.field}</h3>
                                <span class="creative-school">${edu.school}</span>
                                <div class="creative-edu-dates">
                                    ${edu.graduation_year ? `Graduated: ${edu.graduation_year}` : ''}
                                    ${edu.gpa ? ` | GPA: ${edu.gpa}` : ''}
                                </div>
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
            }
            
            // Skills
            if (data.skills) {
                const skillsArray = data.skills.split(',').map(skill => skill.trim()).filter(skill => skill);
                html += `
                    <div class="creative-section">
                        <h2 class="creative-section-title">Skills</h2>
                        <div class="creative-skills">
                            ${skillsArray.map(skill => `<span class="creative-skill">${skill}</span>`).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Certifications
            if (data.certifications.length > 0) {
                html += `
                    <div class="creative-section">
                        <h2 class="creative-section-title">Certifications</h2>
                `;
                
                data.certifications.forEach(cert => {
                    if (cert.name) {
                        html += `
                            <div class="creative-certification">
                                <h3 class="creative-cert-name">${cert.name}</h3>
                                ${cert.organization ? `<span class="creative-cert-org">${cert.organization}</span>` : ''}
                                <div class="creative-cert-dates">
                                    ${cert.issue_date ? `Issued: ${cert.issue_date}` : ''}
                                    ${cert.expiry_date ? ` | Expires: ${cert.expiry_date}` : ''}
                                </div>
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
            }
            
            html += `
                    </div>
                </div>
            `;
            
            return html;
        }
        
        function generateTwoColumnTemplate(data, imageSrc) {
            let html = `
                <div class="professional-resume two-column-template">
                    <!-- Left Column -->
                    <div class="two-column-left">
                        <!-- Top Section with Photo Only -->
                        <div class="two-column-left-top">
                            ${imageSrc ? `<img src="${imageSrc}" alt="Profile Photo" class="two-column-profile-photo">` : ''}
                        </div>
                        
                        <!-- Bottom Section with Contact, Skills, Education, Languages -->
                        <div class="two-column-left-bottom">
                            <!-- Contact -->
                            <div class="two-column-section">
                                <h3>CONTACT</h3>
                                ${data.personal_info.location ? `<p>${data.personal_info.location}</p>` : ''}
                                ${data.personal_info.phone ? `<p>Mobile: ${data.personal_info.phone}</p>` : ''}
                                ${data.personal_info.email ? `<p>${data.personal_info.email}</p>` : ''}
                            </div>
                            
                            <!-- Skills -->
                            ${data.skills ? `
                                <div class="two-column-section">
                                    <h3>SKILLS</h3>
                                    <ul>
                                        ${data.skills.split(',').map(skill => `<li>${skill.trim()}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                            
                            <!-- Education -->
                            ${data.education.length > 0 ? `
                                <div class="two-column-section">
                                    <h3>EDUCATION</h3>
                                    ${data.education.map(edu => `
                                        <div style="margin-bottom: 15px;">
                                            ${edu.graduation_year ? `<div style="font-size: 11px; color: #666; margin-bottom: 3px;">${edu.graduation_year}</div>` : ''}
                                            ${edu.degree && edu.field ? `<div style="font-size: 12px; font-weight: bold; margin-bottom: 2px;">${edu.degree}: ${edu.field}</div>` : ''}
                                            ${edu.school ? `<div style="font-size: 11px; color: #555;">${edu.school}${edu.location ? ', ' + edu.location : ''}</div>` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ''}
                            
                            <!-- Languages -->
                            ${data.languages ? `
                                <div class="two-column-section">
                                    <h3>LANGUAGES</h3>
                                    <ul>
                                        ${data.languages.split(',').map(lang => `<li>${lang.trim()}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="two-column-right">
                        <!-- Name and Title -->
                        <h1 class="two-column-name">${data.personal_info.firstname || ''} ${data.personal_info.lastname || ''}</h1>
                        <div class="two-column-title-line"></div>
                        
                        <!-- Professional Summary -->
                        ${data.personal_info.summary ? `
                            <div class="two-column-main-section">
                                <h2>PROFESSIONAL SUMMARY</h2>
                                <p>${data.personal_info.summary}</p>
                            </div>
                        ` : ''}
                        
                        <!-- Work History -->
                        ${data.work_experience.length > 0 ? `
                            <div class="two-column-main-section">
                                <h2>WORK HISTORY</h2>
                                ${data.work_experience.map(exp => `
                                    <div class="two-column-work-item">
                                        <div class="two-column-work-header">
                                            <div>
                                                <div class="two-column-work-title">${exp.job_title || ''}</div>
                                                <div class="two-column-work-company">${exp.company || ''}${exp.location ? ', ' + exp.location : ''}</div>
                                            </div>
                                            <div class="two-column-work-dates">
                                                ${exp.start_date || ''}${exp.end_date ? ' - ' + exp.end_date : (exp.start_date ? ' - Current' : '')}
                                            </div>
                                        </div>
                                        ${exp.description ? `
                                            <div class="two-column-work-description">
                                                ${exp.description.split('\n').map(line => `<div>${line}</div>`).join('')}
                                            </div>
                                        ` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            return html;
        }
        
        function editResume(resumeId) {
            // Show loading
            Swal.fire({
                title: 'Loading Resume...',
                text: 'Please wait while we load your resume data.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Fetch resume data
            fetch('get_resume_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ resume_id: resumeId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Debug: Log the resume data
                    console.log('Resume data received:', data.resume);
                    console.log('Skills field:', data.resume.skills);
                    console.log('Languages field:', data.resume.languages);
                    
                    // Populate form with resume data
                    populateResumeForm(data.resume);
                    
                    // Switch to first tab without error
                    showTab('personal');
                    
                    // Update form action
                    document.querySelector('input[name="action"]').value = 'update_resume';
                    document.getElementById('resumeId').value = resumeId;
                    
                    // Close loading and show success silently
                    Swal.close();
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to load resume data.',
                        icon: 'error',
                confirmButtonText: 'OK'
            });
                }
            })
            .catch(error => {
                console.error('Error loading resume:', error);
            Swal.fire({
                    title: 'Error',
                    text: 'Failed to load resume data. Please try again.',
                    icon: 'error',
                confirmButtonText: 'OK'
                });
            });
        }
        
        function populateResumeForm(resume) {
            // Populate personal info - direct field access
            const personalFields = ['firstname', 'lastname', 'email', 'phone', 'location', 'linkedin', 'summary'];
            personalFields.forEach(field => {
                const input = document.querySelector(`input[name="personal_info[${field}]"], textarea[name="personal_info[${field}]"]`);
                if (input && resume[field]) {
                    input.value = resume[field];
                }
            });
            
            // Populate profile image if exists
            if (resume.profile_image) {
                const previewImg = document.getElementById('previewImg');
                const placeholder = document.querySelector('.no-image-placeholder');
                const removeBtn = document.querySelector('.remove-btn');
                
                previewImg.src = resume.profile_image;
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
                removeBtn.style.display = 'inline-flex';
            }
        
            // Populate work experience
            if (resume.work_experience && resume.work_experience.length > 0) {
                const container = document.getElementById('experienceList');
                container.innerHTML = '';
                
                resume.work_experience.forEach((exp, index) => {
                    addExperienceItem(exp, index);
                });
                experienceCount = resume.work_experience.length;
            }
            
            // Populate education
            if (resume.education && resume.education.length > 0) {
                const container = document.getElementById('educationList');
                container.innerHTML = '';
                
                resume.education.forEach((edu, index) => {
                    addEducationItem(edu, index);
                });
                educationCount = resume.education.length;
            }
            
            // Populate skills - target the textarea specifically to avoid ID conflicts
            if (resume.skills) {
                const skillsTextarea = document.querySelector('textarea#skills');
                if (skillsTextarea) {
                    skillsTextarea.value = resume.skills;
                    console.log('Skills populated successfully:', resume.skills);
                }
            }
            
            // Populate languages
            if (resume.languages) {
                const languagesTextarea = document.getElementById('languages');
                if (languagesTextarea) {
                    languagesTextarea.value = resume.languages;
                }
            }
            
            // Populate certifications
            if (resume.certifications && resume.certifications.length > 0) {
                const container = document.getElementById('certificationsList');
                container.innerHTML = '';
                
                resume.certifications.forEach((cert, index) => {
                    addCertificationItem(cert, index);
                });
                certificationCount = resume.certifications.length;
            }
            
            // Set template
            if (resume.template_id) {
                selectTemplateById(resume.template_id);
            }
            
            // Set resume name
            const resumeNameInput = document.getElementById('resume_name');
            if (resumeNameInput) {
                resumeNameInput.value = resume.resume_name || '';
            }
            
            // Set default checkbox
            const defaultCheckbox = document.querySelector('input[name="is_default"]');
            if (defaultCheckbox) {
                defaultCheckbox.checked = resume.is_default == 1;
            }
        }
        
        function addExperienceItem(exp, index) {
            const container = document.getElementById('experienceList');
            const newItem = document.createElement('div');
            newItem.className = 'list-item';
            newItem.innerHTML = `
                <div class="list-item-header">
                    <span class="list-item-title">Work Experience #${index + 1}</span>
                    <button type="button" class="remove-item" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Job Title *</label>
                        <input type="text" name="work_experience[${index}][job_title]" required value="${exp.job_title || ''}">
                    </div>
                    <div class="form-group">
                        <label>Company *</label>
                        <input type="text" name="work_experience[${index}][company]" required value="${exp.company || ''}">
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="month" name="work_experience[${index}][start_date]" value="${exp.start_date || ''}">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="month" name="work_experience[${index}][end_date]" value="${exp.end_date || ''}">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="work_experience[${index}][location]" value="${exp.location || ''}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="work_experience[${index}][description]" placeholder="Describe your responsibilities and achievements...">${exp.description || ''}</textarea>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        function addEducationItem(edu, index) {
            const container = document.getElementById('educationList');
            const newItem = document.createElement('div');
            newItem.className = 'list-item';
            newItem.innerHTML = `
                <div class="list-item-header">
                    <span class="list-item-title">Education #${index + 1}</span>
                    <button type="button" class="remove-item" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Degree *</label>
                        <input type="text" name="education[${index}][degree]" required placeholder="e.g., Bachelor of Science" value="${edu.degree || ''}">
                    </div>
                    <div class="form-group">
                        <label>Field of Study *</label>
                        <input type="text" name="education[${index}][field]" required placeholder="e.g., Computer Science" value="${edu.field || ''}">
                    </div>
                    <div class="form-group">
                        <label>School/University *</label>
                        <input type="text" name="education[${index}][school]" required value="${edu.school || ''}">
                    </div>
                    <div class="form-group">
                        <label>Graduation Year</label>
                        <input type="number" name="education[${index}][graduation_year]" min="1950" max="2030" value="${edu.graduation_year || ''}">
                    </div>
                    <div class="form-group">
                        <label>GPA (Optional)</label>
                        <input type="text" name="education[${index}][gpa]" placeholder="e.g., 3.5/4.0" value="${edu.gpa || ''}">
                    </div>
                                </div>
                            `;
            container.appendChild(newItem);
        }
        
        function addCertificationItem(cert, index) {
            const container = document.getElementById('certificationsList');
            const newItem = document.createElement('div');
            newItem.className = 'list-item';
            newItem.innerHTML = `
                <div class="list-item-header">
                    <span class="list-item-title">Certification #${index + 1}</span>
                    <button type="button" class="remove-item" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Certification Name *</label>
                        <input type="text" name="certifications[${index}][name]" required value="${cert.name || ''}">
                    </div>
                    <div class="form-group">
                        <label>Issuing Organization</label>
                        <input type="text" name="certifications[${index}][organization]" value="${cert.organization || ''}">
                    </div>
                    <div class="form-group">
                        <label>Issue Date</label>
                        <input type="month" name="certifications[${index}][issue_date]" value="${cert.issue_date || ''}">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="month" name="certifications[${index}][expiry_date]" value="${cert.expiry_date || ''}">
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        function selectTemplateById(templateId) {
            document.querySelectorAll('.template-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            const targetCard = document.querySelector(`.template-card[onclick*="${templateId}"]`);
            if (targetCard) {
                targetCard.classList.add('selected');
                document.getElementById('selectedTemplate').value = templateId;
            }
        }
        
        function generatePDF(resumeId, resumeName = 'Resume') {
            // Use the resume name passed as parameter
            console.log('Generating PDF for resume:', resumeId, 'with name:', resumeName);
            
            // Show loading
            Swal.fire({
                title: 'Generating PDF...',
                text: 'Please wait while we generate your resume PDF.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Generate PDF using form submission approach
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'generate_resume_pdf.php';
            form.target = '_blank';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'resume_id';
            input.value = resumeId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            // Close the loading indicator
            Swal.close();
        }
        
        function deleteResume(resumeId) {
            Swal.fire({
                title: 'Delete Resume',
                text: "Are you sure you want to delete this resume? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting Resume...',
                        text: 'Please wait while we delete your resume.',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Delete resume
                    fetch('delete_resume.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ resume_id: resumeId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'The resume has been deleted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload the page to refresh the resume list
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Failed to delete resume.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                }
            })
            .catch(error => {
                        console.error('Error deleting resume:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to delete resume. Please try again.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        }
        
        // Form validation
        function validateForm() {
            const errors = [];
            const missingSections = [];
            
            // Check required personal info
            const firstName = document.querySelector('input[name="personal_info[firstname]"]');
            const lastName = document.querySelector('input[name="personal_info[lastname]"]');
            const email = document.querySelector('input[name="personal_info[email]"]');
            
            if (!firstName || !firstName.value.trim()) {
                errors.push('First name is required');
                missingSections.push('Personal Information');
            }
            if (!lastName || !lastName.value.trim()) {
                errors.push('Last name is required');
                missingSections.push('Personal Information');
            }
            if (!email || !email.value.trim()) {
                errors.push('Email is required');
                missingSections.push('Personal Information');
            }
            
            // Check resume name
            const resumeName = document.getElementById('resume_name');
            if (!resumeName || !resumeName.value.trim()) {
                errors.push('Resume name is required');
                missingSections.push('Resume Name');
            }
            
            // Check template selection
            const selectedTemplate = document.getElementById('selectedTemplate');
            if (!selectedTemplate || !selectedTemplate.value) {
                errors.push('Please select a template');
                missingSections.push('Template Selection');
            }
            
            // Check work experience - more comprehensive validation
            const experienceItems = document.querySelectorAll('#experienceList .list-item');
            let hasValidWorkExp = false;
            let workExpErrors = [];
            
            experienceItems.forEach((item, index) => {
                const jobTitle = item.querySelector('input[name*="[job_title]"]');
                const company = item.querySelector('input[name*="[company]"]');
                
                if (jobTitle && company) {
                    if (!jobTitle.value.trim()) workExpErrors.push(`Job title is required for work experience #${index + 1}`);
                    if (!company.value.trim()) workExpErrors.push(`Company is required for work experience #${index + 1}`);
                    
                    if (jobTitle.value.trim() && company.value.trim()) {
                        hasValidWorkExp = true;
                    }
                }
            });
            
            if (!hasValidWorkExp) {
                errors.push(...workExpErrors);
                if (workExpErrors.length === 0) {
                    errors.push('At least one work experience entry is required');
                }
                missingSections.push('Work Experience');
            }
            
            // Check education - more comprehensive validation
            const educationItems = document.querySelectorAll('#educationList .list-item');
            let hasValidEducation = false;
            let educationErrors = [];
            
            educationItems.forEach((item, index) => {
                const degree = item.querySelector('input[name*="[degree]"]');
                const field = item.querySelector('input[name*="[field]"]');
                const school = item.querySelector('input[name*="[school]"]');
                
                if (degree && field && school) {
                    if (!degree.value.trim()) educationErrors.push(`Degree is required for education #${index + 1}`);
                    if (!field.value.trim()) educationErrors.push(`Field of study is required for education #${index + 1}`);
                    if (!school.value.trim()) educationErrors.push(`School/University is required for education #${index + 1}`);
                    
                    if (degree.value.trim() && field.value.trim() && school.value.trim()) {
                        hasValidEducation = true;
                    }
                }
            });
            
            if (!hasValidEducation) {
                errors.push(...educationErrors);
                if (educationErrors.length === 0) {
                    errors.push('At least one education entry is required');
                }
                missingSections.push('Education');
            }
            
            // Check certifications - more comprehensive validation
            const certificationItems = document.querySelectorAll('#certificationsList .list-item');
            let hasValidCert = false;
            let certErrors = [];
            
            certificationItems.forEach((item, index) => {
                const name = item.querySelector('input[name*="[name]"]');
                
                if (name) {
                    if (!name.value.trim()) {
                        certErrors.push(`Certification name is required for certification #${index + 1}`);
                    } else {
                        hasValidCert = true;
                    }
                }
            });
            
            if (!hasValidCert) {
                errors.push(...certErrors);
                if (certErrors.length === 0) {
                    errors.push('At least one certification entry is required');
                }
                missingSections.push('Certifications');
            }
            
            return { errors, missingSections: [...new Set(missingSections)] };
        }
        
        // Enhanced form submission
        function submitForm(event) {
            event.preventDefault();
            
            // Validate form
            const validation = validateForm();
            if (validation.errors.length > 0) {
                // Create a more user-friendly error message
                let errorMessage = '<div style="text-align: left;">';
                errorMessage += '<h4 style="color: #e74c3c; margin-bottom: 15px;">⚠️ Please complete the following required fields:</h4>';
                
                if (validation.missingSections.length > 0) {
                    errorMessage += '<div style="margin-bottom: 15px;">';
                    errorMessage += '<strong style="color: #2c3e50;">Missing Sections:</strong><br>';
                    errorMessage += validation.missingSections.map(section => `• ${section}`).join('<br>');
                    errorMessage += '</div>';
                }
                
                if (validation.errors.length > 0) {
                    errorMessage += '<div>';
                    errorMessage += '<strong style="color: #2c3e50;">Specific Issues:</strong><br>';
                    errorMessage += validation.errors.map(error => `• ${error}`).join('<br>');
                    errorMessage += '</div>';
                }
                
                errorMessage += '<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-left: 4px solid #3498db; border-radius: 4px;">';
                errorMessage += '<strong style="color: #3498db;">💡 Tip:</strong> Click on the tab names above to navigate to the sections that need completion.';
                errorMessage += '</div>';
                errorMessage += '</div>';
                
                Swal.fire({
                    title: 'Incomplete Resume',
                    html: errorMessage,
                    icon: 'warning',
                    confirmButtonText: 'I\'ll Fix These Issues',
                    confirmButtonColor: '#3498db',
                    width: '600px',
                    customClass: {
                        popup: 'swal-wide'
                    }
                }).then(() => {
                    // Focus on the first missing section
                    if (validation.missingSections.includes('Personal Information')) {
                        showTab('personal');
                    } else if (validation.missingSections.includes('Work Experience')) {
                        showTab('experience');
                    } else if (validation.missingSections.includes('Education')) {
                        showTab('education');
                    } else if (validation.missingSections.includes('Certifications')) {
                        showTab('skills');
                    }
                });
                return;
            }
            
            // Show loading
            Swal.fire({
                title: 'Saving Resume...',
                text: 'Please wait while we save your resume.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit form
            const form = document.getElementById('resumeForm');
            const formData = new FormData(form);
            
            console.log('Submitting form data...');
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            
            // Log form data for debugging
            for (let [key, value] of formData.entries()) {
                console.log(key, '=', value);
            }
            
            fetch('resume_builder.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(data => {
                console.log('Response data:', data);
                
                // Check if there's a success message in the response
                if (data.includes('Resume saved successfully') || data.includes('Resume updated successfully')) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Your resume has been saved successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload the page to show updated resume list
                        window.location.reload();
                    });
                } else if (data.includes('Error saving resume') || data.includes('Error updating resume')) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to save resume. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    console.log('Unexpected response, reloading page');
                    // If we get here, the page was reloaded with a message
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error saving resume:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to save resume. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
        
        // Auto-update preview when form changes
        function setupAutoPreview() {
            const form = document.getElementById('resumeForm');
            const inputs = form.querySelectorAll('input, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    // Only update if we're on the preview tab
                    const previewTab = document.getElementById('preview');
                    if (previewTab.classList.contains('active')) {
                        updatePreview();
                    }
                });
            });
        }
        
        // AI Generator function
        function openAIGenerator() {
            // Open AI generator in a new window/tab
            window.open('ai_resume_generator.html', '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Resume builder DOM loaded');
            
            // Select the first template by default
            const firstTemplate = document.querySelector('.template-card');
            if (firstTemplate) {
                firstTemplate.classList.add('selected');
                const templateId = firstTemplate.getAttribute('onclick').match(/selectTemplate\((\d+)\)/)[1];
                document.getElementById('selectedTemplate').value = templateId;
                console.log('Default template selected:', templateId);
            }
            
            // Show SweetAlert for success/error messages
            <?php if (isset($success_message)): ?>
                setTimeout(() => {
                    Swal.fire({
                        title: 'Success!',
                        text: '<?php echo addslashes($success_message); ?>',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        timer: 3000,
                        timerProgressBar: true
                    });
                }, 100);
            <?php endif; ?>
            
            <?php if (isset($error_message) && !empty($error_message)): ?>
                console.log('Error message found:', '<?php echo addslashes($error_message); ?>');
                setTimeout(() => {
                    Swal.fire({
                        title: 'Error!',
                        text: '<?php echo addslashes($error_message); ?>',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }, 100);
            <?php else: ?>
                console.log('No error message set');
            <?php endif; ?>
            
            // Setup form submission
            const form = document.getElementById('resumeForm');
            if (form) {
                form.addEventListener('submit', submitForm);
            }
            
            // Setup auto-preview
            setupAutoPreview();
            
            // Update preview when switching to preview tab
            const previewButton = document.querySelector('button[onclick="showTab(\'preview\')"]');
            if (previewButton) {
                previewButton.addEventListener('click', () => {
                    setTimeout(updatePreview, 100); // Small delay to ensure tab is switched
                });
            }
            
            // Setup refresh preview button
            const refreshPreviewButton = document.querySelector('button[onclick="updatePreview()"]');
            if (refreshPreviewButton) {
                refreshPreviewButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    updatePreview();
                });
            }
        });
    </script>
</body>
</html>
*/
*/