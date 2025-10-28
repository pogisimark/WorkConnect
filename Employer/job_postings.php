<?php include 'session_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect Job Postings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fafafa;
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
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
            max-width: 100vw;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(35,58,139,0.10);
            box-sizing: border-box;
        }
        .header img {
            height: 48px;
            margin-right: 16px;
            border-radius: 50%;
            background: none;
            border: none;
        }
        .header-title {
            font-size: 1.7rem;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        
        .hamburger-menu {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            margin-right: 12px;
        }
        
        .hamburger-menu span {
            width: 20px;
            height: 2px;
            background: #fff;
            margin: 2px 0;
            transition: 0.3s;
        }
        .layout {
            display: flex;
            min-height: calc(100vh - 64px);
            padding-top: 64px;
        }
        .sidebar {
            background: #e3eaff;
            width: 240px;
            height: calc(100vh - 64px);
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
        .sidebar .logout {
            margin-top: auto;
            margin-bottom: 32px;
            color: #222;
            font-weight: bold;
            display: block;
            width: 90%;
            text-align: left;
        }
        .main-content {
            flex: 1;
            padding: 32px;
            background: #fff;
            margin-left: 240px;
            min-height: calc(100vh - 64px);
            overflow-y: auto;
            box-sizing: border-box;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .page-title {
            color: #233a8b;
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }
        
        .page-subtitle {
            color: #666;
            margin: 8px 0 0 0;
            font-size: 1.1rem;
        }
        
        .add-job-btn {
            background: linear-gradient(135deg, #233a8b, #1976d2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .add-job-btn:hover {
            background: linear-gradient(135deg, #1a2d6b, #1565c0);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(35,58,139,0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #233a8b;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #233a8b;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .jobs-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .jobs-table th {
            background: #f8f9fa;
            padding: 16px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        
        .jobs-table td {
            padding: 16px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .jobs-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-view {
            background: #28a745;
            color: white;
        }
        
        .btn-view:hover {
            background: #218838;
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
        
        .btn-applications {
            background: #17a2b8;
            color: white;
        }
        
        .btn-applications:hover {
            background: #138496;
        }
        
        .btn-analytics {
            background: #6f42c1;
            color: white;
        }
        
        .btn-analytics:hover {
            background: #5a32a3;
        }
        
        /* Export Dropdown */
        .export-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .export-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .export-btn:hover {
            background: #5a6268;
        }
        
        .export-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            min-width: 180px;
        }
        
        .export-menu a {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .export-menu a:hover {
            background: #f8f9fa;
        }
        
        /* Search and Filter Section */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-bar {
            position: relative;
            margin-bottom: 15px;
        }
        
        .search-bar i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .search-bar input {
            width: 100%;
            padding: 12px 40px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #233a8b;
        }
        
        .clear-search {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .clear-search:hover {
            background: #f8f9fa;
            color: #333;
        }
        
        .filter-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-select,
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .filter-input {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-btn,
        .reset-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-btn {
            background: #233a8b;
            color: white;
        }
        
        .filter-btn:hover {
            background: #1a2d6b;
        }
        
        .reset-btn {
            background: #6c757d;
            color: white;
        }
        
        .reset-btn:hover {
            background: #5a6268;
        }
        
        /* Bulk Actions */
        .bulk-actions {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .bulk-info {
            font-weight: bold;
            color: #1976d2;
        }
        
        .bulk-buttons {
            display: flex;
            gap: 8px;
        }
        
        .bulk-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .bulk-activate {
            background: #28a745;
            color: white;
        }
        
        .bulk-activate:hover {
            background: #218838;
        }
        
        .bulk-close {
            background: #ffc107;
            color: #212529;
        }
        
        .bulk-close:hover {
            background: #e0a800;
        }
        
        .bulk-duplicate {
            background: #17a2b8;
            color: white;
        }
        
        .bulk-duplicate:hover {
            background: #138496;
        }
        
        .bulk-delete {
            background: #dc3545;
            color: white;
        }
        
        .bulk-delete:hover {
            background: #c82333;
        }
        
        /* Table Enhancements */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .table-title h3 {
            margin: 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .job-count {
            color: #666;
            font-size: 0.9rem;
            margin-left: 8px;
        }
        
        .select-all-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            color: #333;
            cursor: pointer;
        }
        
        .job-title-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .job-industry {
            color: #666;
            font-size: 0.8rem;
            font-style: italic;
        }
        
        .location-cell {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #666;
        }
        
        .location-cell i {
            color: #dc3545;
        }
        
        .job-type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .job-type-fulltime {
            background: #d4edda;
            color: #155724;
        }
        
        .job-type-parttime {
            background: #fff3cd;
            color: #856404;
        }
        
        .job-type-contract {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .job-type-internship {
            background: #f8d7da;
            color: #721c24;
        }
        
        .date-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .date-cell small {
            color: #666;
            font-size: 0.8rem;
        }
        
        .no-jobs {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-jobs i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: #233a8b;
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #ffcb05;
        }
        
        .modal-body {
            padding: 24px;
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
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #233a8b;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .btn-primary {
            background: #233a8b;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #1a2d6b;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Analytics Modal Styles */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .analytics-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #233a8b;
        }
        
        .analytics-icon {
            width: 50px;
            height: 50px;
            background: #233a8b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .analytics-content h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
            color: #233a8b;
        }
        
        .analytics-content p {
            margin: 5px 0;
            color: #666;
            font-weight: bold;
        }
        
        .analytics-content small {
            color: #28a745;
            font-size: 0.8rem;
        }
        
        .analytics-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }
        
        .chart-container h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.1rem;
        }
        
        .analytics-insights {
            background: #e3f2fd;
            border-radius: 12px;
            padding: 20px;
        }
        
        .analytics-insights h4 {
            margin: 0 0 15px 0;
            color: #1976d2;
        }
        
        .analytics-insights ul {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }
        
        .analytics-insights li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hamburger-menu {
                display: flex;
            }
            
            .layout {
                flex-direction: column;
            }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .filter-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-select,
            .filter-input {
                min-width: auto;
            }
            
            .analytics-charts {
                grid-template-columns: 1fr;
            }
            
            .analytics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .bulk-buttons {
                flex-wrap: wrap;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .page-header > div:last-child {
                display: flex;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .jobs-table {
                font-size: 0.9rem;
            }
            
            .jobs-table th,
            .jobs-table td {
                padding: 12px 8px;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .bulk-buttons {
                flex-direction: column;
            }
            
            .bulk-btn {
                width: 100%;
                justify-content: center;
            }
            
            .table-header {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .action-buttons {
                gap: 4px;
            }
            
            .btn {
                padding: 6px 8px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <div class="header" id="mainHeader">
        <div style="display: flex; align-items: center;">
            <button class="hamburger-menu" id="hamburgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title" id="headerTitle">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;" id="adminSection">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="adminUsername" style="font-size: 1rem; font-weight: 500;">Admin</span>
        </div>
    </div>

    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php">📊 DASHBOARD</a>
            <a href="job_postings.php" class="active">💼 JOB POSTINGS</a>
            <a href="job.php">👥 JOBSEEKERS</a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;">➕ ADD ACCOUNT</a>
            <a href="analytics.php">📊 Analytics</a>
            <a href="announcement.php">📢 ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
            
        </div>

        <div class="main-content">
            <?php
            // Handle form submissions
            require_once 'db.php';
            $success_message = '';
            $error_message = '';

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if (isset($_POST['action'])) {
                    switch ($_POST['action']) {
                        case 'add_job':
                            $title = trim($_POST['title']);
                            $company = trim($_POST['company']);
                            $description = trim($_POST['description']);
                            $requirements = trim($_POST['requirements']);
                            $salary_range = trim($_POST['salary_range']);
                            $location = trim($_POST['location']);
                            $job_type = $_POST['job_type'];
                            $industry = trim($_POST['industry']);
                            
                            $stmt = $conn->prepare("INSERT INTO job_postings (title, company, description, requirements, salary_range, location, job_type, industry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("ssssssss", $title, $company, $description, $requirements, $salary_range, $location, $job_type, $industry);
                            
                            if ($stmt->execute()) {
                                $success_message = "Job posting created successfully!";
                            } else {
                                $error_message = "Error creating job posting: " . $conn->error;
                            }
                            $stmt->close();
                            break;
                            
                        case 'update_job':
                            $id = $_POST['job_id'];
                            $title = trim($_POST['title']);
                            $company = trim($_POST['company']);
                            $description = trim($_POST['description']);
                            $requirements = trim($_POST['requirements']);
                            $salary_range = trim($_POST['salary_range']);
                            $location = trim($_POST['location']);
                            $job_type = $_POST['job_type'];
                            $industry = trim($_POST['industry']);
                            $status = $_POST['status'];
                            
                            $stmt = $conn->prepare("UPDATE job_postings SET title=?, company=?, description=?, requirements=?, salary_range=?, location=?, job_type=?, industry=?, status=? WHERE id=?");
                            $stmt->bind_param("sssssssssi", $title, $company, $description, $requirements, $salary_range, $location, $job_type, $industry, $status, $id);
                            
                            if ($stmt->execute()) {
                                $success_message = "Job posting updated successfully!";
                            } else {
                                $error_message = "Error updating job posting: " . $conn->error;
                            }
                            $stmt->close();
                            break;
                            
                        case 'delete_job':
                            $id = $_POST['job_id'];
                            $stmt = $conn->prepare("DELETE FROM job_postings WHERE id=?");
                            $stmt->bind_param("i", $id);
                            
                            if ($stmt->execute()) {
                                $success_message = "Job posting deleted successfully!";
                            } else {
                                $error_message = "Error deleting job posting: " . $conn->error;
                            }
                            $stmt->close();
                            break;
                            
                        case 'bulk_update_status':
                            $data = json_decode($_POST['data'], true);
                            $job_ids = $data['job_ids'];
                            $status = $data['status'];
                            
                            $placeholders = str_repeat('?,', count($job_ids) - 1) . '?';
                            $stmt = $conn->prepare("UPDATE job_postings SET status=? WHERE id IN ($placeholders)");
                            $params = array_merge([$status], $job_ids);
                            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                            
                            if ($stmt->execute()) {
                                $success_message = count($job_ids) . " job(s) updated to $status successfully!";
                            } else {
                                $error_message = "Error updating job status: " . $conn->error;
                            }
                            $stmt->close();
                            break;
                            
                        case 'bulk_delete':
                            $data = json_decode($_POST['data'], true);
                            $job_ids = $data['job_ids'];
                            
                            $placeholders = str_repeat('?,', count($job_ids) - 1) . '?';
                            $stmt = $conn->prepare("DELETE FROM job_postings WHERE id IN ($placeholders)");
                            $stmt->bind_param(str_repeat('i', count($job_ids)), ...$job_ids);
                            
                            if ($stmt->execute()) {
                                $success_message = count($job_ids) . " job(s) deleted successfully!";
                            } else {
                                $error_message = "Error deleting jobs: " . $conn->error;
                            }
                            $stmt->close();
                            break;
                            
                        case 'bulk_duplicate':
                            $data = json_decode($_POST['data'], true);
                            $job_ids = $data['job_ids'];
                            
                            $duplicated_count = 0;
                            foreach ($job_ids as $job_id) {
                                // Get original job data
                                $stmt = $conn->prepare("SELECT * FROM job_postings WHERE id=?");
                                $stmt->bind_param("i", $job_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $job = $result->fetch_assoc();
                                $stmt->close();
                                
                                if ($job) {
                                    // Insert duplicate with "Copy of" prefix
                                    $new_title = "Copy of " . $job['title'];
                                    $stmt = $conn->prepare("INSERT INTO job_postings (title, company, description, requirements, salary_range, location, job_type, industry, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                    $stmt->bind_param("sssssssss", $new_title, $job['company'], $job['description'], $job['requirements'], $job['salary_range'], $job['location'], $job['job_type'], $job['industry'], 'Draft');
                                    
                                    if ($stmt->execute()) {
                                        $duplicated_count++;
                                    }
                                    $stmt->close();
                                }
                            }
                            
                            if ($duplicated_count > 0) {
                                $success_message = "$duplicated_count job(s) duplicated successfully!";
                            } else {
                                $error_message = "No jobs were duplicated.";
                            }
                            break;
                    }
                }
            }

            // Get all job postings
            $stmt = $conn->prepare("SELECT * FROM job_postings ORDER BY created_at DESC");
            $stmt->execute();
            $job_postings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Get job posting statistics
            $stats_query = "SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_jobs,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_jobs
                FROM job_postings";
            $stats_result = $conn->query($stats_query);
            $stats = $stats_result->fetch_assoc();

            $conn->close();
            ?>

            <div class="page-header">
                <div>
                    <h1 class="page-title">Job Postings Management</h1>
                    <p class="page-subtitle">Manage and track your job postings</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div class="export-dropdown">
                        <button class="export-btn" onclick="toggleExportDropdown()">
                            <i class="fas fa-download"></i> Export
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="export-menu" id="exportMenu">
                            <a href="#" onclick="exportJobs('csv')">
                                <i class="fas fa-file-csv"></i> Export as CSV
                            </a>
                            <a href="#" onclick="exportJobs('pdf')">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </a>
                        </div>
                    </div>
                    <button class="add-job-btn" onclick="openAddJobModal()">
                        <i class="fas fa-plus"></i> Add New Job
                    </button>
                </div>
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

            <!-- Search and Filter Section -->
            <div class="filters-section">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search jobs by title, company, location, or description...">
                    <button class="clear-search" onclick="clearSearch()" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="filter-controls">
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Closed">Closed</option>
                        <option value="Draft">Draft</option>
                    </select>
                    
                    <select id="typeFilter" class="filter-select">
                        <option value="">All Types</option>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Internship">Internship</option>
                    </select>
                    
                    <input type="text" id="industryFilter" class="filter-input" placeholder="Filter by industry...">
                    
                    <button class="filter-btn" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    
                    <button class="reset-btn" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div class="bulk-actions" id="bulkActions" style="display: none;">
                <div class="bulk-info">
                    <span id="selectedCount">0</span> job(s) selected
                </div>
                <div class="bulk-buttons">
                    <button class="bulk-btn bulk-activate" onclick="bulkAction('activate')">
                        <i class="fas fa-check"></i> Activate
                    </button>
                    <button class="bulk-btn bulk-close" onclick="bulkAction('close')">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button class="bulk-btn bulk-duplicate" onclick="bulkAction('duplicate')">
                        <i class="fas fa-copy"></i> Duplicate
                    </button>
                    <button class="bulk-btn bulk-delete" onclick="bulkAction('delete')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_jobs']; ?></div>
                    <div class="stat-label">Total Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_jobs']; ?></div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['closed_jobs']; ?></div>
                    <div class="stat-label">Closed Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_jobs'] > 0 ? round(($stats['active_jobs'] / $stats['total_jobs']) * 100) : 0; ?>%</div>
                    <div class="stat-label">Active Rate</div>
                </div>
            </div>

            <div class="content-card">
                <?php if (empty($job_postings)): ?>
                    <div class="no-jobs">
                        <i class="fas fa-briefcase"></i>
                        <h3>No Job Postings Yet</h3>
                        <p>Start by creating your first job posting to attract qualified candidates.</p>
                        <button class="add-job-btn" onclick="openAddJobModal()">
                            <i class="fas fa-plus"></i> Create First Job Posting
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-header">
                        <div class="table-title">
                            <h3>Job Postings</h3>
                            <span class="job-count"><?php echo count($job_postings); ?> jobs</span>
                        </div>
                        <div class="table-actions">
                            <label class="select-all-label">
                                <input type="checkbox" id="selectAllJobs" onchange="toggleSelectAll()">
                                Select All
                            </label>
                        </div>
                    </div>
                    
                    <table class="jobs-table">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="selectAllHeader" onchange="toggleSelectAll()">
                                </th>
                                <th>Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Salary</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($job_postings as $job): ?>
                                <tr data-job-id="<?php echo $job['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="job-checkbox" value="<?php echo $job['id']; ?>" onchange="toggleJobSelection(<?php echo $job['id']; ?>)">
                                    </td>
                                    <td>
                                        <div class="job-title-cell">
                                            <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                            <small class="job-industry"><?php echo htmlspecialchars($job['industry']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($job['company']); ?></td>
                                    <td>
                                        <div class="location-cell">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($job['location']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="job-type-badge job-type-<?php echo strtolower(str_replace('-', '', $job['job_type'])); ?>">
                                            <?php echo htmlspecialchars($job['job_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($job['salary_range']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($job['status']); ?>">
                                            <?php echo htmlspecialchars($job['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                            <small><?php echo date('g:i A', strtotime($job['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-view" onclick="viewJob(<?php echo $job['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-edit" onclick="editJob(<?php echo $job['id']; ?>)" title="Edit Job">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-applications" onclick="viewApplications(<?php echo $job['id']; ?>)" title="View Applications">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <button class="btn btn-analytics" onclick="viewJobAnalytics(<?php echo $job['id']; ?>)" title="Job Analytics">
                                                <i class="fas fa-chart-line"></i>
                                            </button>
                                            <button class="btn btn-delete" onclick="deleteJob(<?php echo $job['id']; ?>)" title="Delete Job">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Job Modal -->
    <div id="jobModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Job Posting</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="jobForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add_job">
                    <input type="hidden" name="job_id" id="jobId" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Job Title *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="company">Company *</label>
                            <input type="text" id="company" name="company" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location *</label>
                            <input type="text" id="location" name="location" required>
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
                    
                    <div class="form-group" id="statusGroup" style="display: none;">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Closed">Closed</option>
                            <option value="Draft">Draft</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-primary" onclick="submitForm()">Save Job Posting</button>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics Modal -->
    <div id="analyticsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Job Analytics Dashboard</h2>
                <span class="close" onclick="closeAnalyticsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="analytics-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="analytics-content">
                            <h3>156</h3>
                            <p>Total Views</p>
                            <small>+12% from last week</small>
                        </div>
                    </div>
                    
                    <div class="analytics-card">
                        <div class="analytics-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="analytics-content">
                            <h3>12</h3>
                            <p>Applications</p>
                            <small>+3 this week</small>
                        </div>
                    </div>
                    
                    <div class="analytics-card">
                        <div class="analytics-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="analytics-content">
                            <h3>7.7%</h3>
                            <p>Conversion Rate</p>
                            <small>Above average</small>
                        </div>
                    </div>
                    
                    <div class="analytics-card">
                        <div class="analytics-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="analytics-content">
                            <h3>2.3</h3>
                            <p>Avg. Response Time</p>
                            <small>Days</small>
                        </div>
                    </div>
                </div>
                
                <div class="analytics-charts">
                    <div class="chart-container">
                        <h4>Applications Over Time</h4>
                        <canvas id="applicationsChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Application Sources</h4>
                        <canvas id="sourcesChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <div class="analytics-insights">
                    <h4>Key Insights</h4>
                    <ul>
                        <li><i class="fas fa-check-circle" style="color: #28a745;"></i> Your job posting performs 15% better than similar positions</li>
                        <li><i class="fas fa-info-circle" style="color: #17a2b8;"></i> Most applications come from LinkedIn (45%)</li>
                        <li><i class="fas fa-lightbulb" style="color: #ffc107;"></i> Consider adding more specific requirements to improve quality</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let allJobs = <?php echo json_encode($job_postings); ?>;
        let filteredJobs = [...allJobs];
        let selectedJobs = new Set();
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilters();
            updateJobCounts();
            initializeBulkActions();
        });
        
        // Search and Filter Functions
        function initializeFilters() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            const industryFilter = document.getElementById('industryFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', applyFilters);
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', applyFilters);
            }
            if (typeFilter) {
                typeFilter.addEventListener('change', applyFilters);
            }
            if (industryFilter) {
                industryFilter.addEventListener('change', applyFilters);
            }
        }
        
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const industryFilter = document.getElementById('industryFilter').value;
            
            filteredJobs = allJobs.filter(job => {
                const matchesSearch = !searchTerm || 
                    job.title.toLowerCase().includes(searchTerm) ||
                    job.company.toLowerCase().includes(searchTerm) ||
                    job.location.toLowerCase().includes(searchTerm) ||
                    job.description.toLowerCase().includes(searchTerm);
                
                const matchesStatus = !statusFilter || job.status === statusFilter;
                const matchesType = !typeFilter || job.job_type === typeFilter;
                const matchesIndustry = !industryFilter || job.industry.toLowerCase().includes(industryFilter.toLowerCase());
                
                return matchesSearch && matchesStatus && matchesType && matchesIndustry;
            });
            
            renderJobsTable();
            updateJobCounts();
        }
        
        function renderJobsTable() {
            const tbody = document.querySelector('.jobs-table tbody');
            if (!tbody) return;
            
            if (filteredJobs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            No jobs found matching your criteria
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = filteredJobs.map(job => `
                <tr data-job-id="${job.id}">
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" class="job-checkbox" value="${job.id}" onchange="toggleJobSelection(${job.id})">
                            <strong>${escapeHtml(job.title)}</strong>
                        </div>
                    </td>
                    <td>${escapeHtml(job.company)}</td>
                    <td>${escapeHtml(job.location)}</td>
                    <td>${escapeHtml(job.job_type)}</td>
                    <td>${escapeHtml(job.salary_range)}</td>
                    <td>
                        <span class="status-badge status-${job.status.toLowerCase()}">
                            ${job.status}
                        </span>
                    </td>
                    <td>${formatDate(job.created_at)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-view" onclick="viewJob(${job.id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-edit" onclick="editJob(${job.id})" title="Edit Job">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-applications" onclick="viewApplications(${job.id})" title="View Applications">
                                <i class="fas fa-users"></i>
                            </button>
                            <button class="btn btn-analytics" onclick="viewJobAnalytics(${job.id})" title="Job Analytics">
                                <i class="fas fa-chart-line"></i>
                            </button>
                            <button class="btn btn-delete" onclick="deleteJob(${job.id})" title="Delete Job">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function updateJobCounts() {
            const totalElement = document.querySelector('.stat-card:nth-child(1) .stat-number');
            const activeElement = document.querySelector('.stat-card:nth-child(2) .stat-number');
            const closedElement = document.querySelector('.stat-card:nth-child(3) .stat-number');
            
            if (totalElement) totalElement.textContent = filteredJobs.length;
            if (activeElement) activeElement.textContent = filteredJobs.filter(job => job.status === 'Active').length;
            if (closedElement) closedElement.textContent = filteredJobs.filter(job => job.status === 'Closed').length;
        }
        
        // Bulk Operations
        function initializeBulkActions() {
            const selectAllCheckbox = document.getElementById('selectAllJobs');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.job-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                        if (this.checked) {
                            selectedJobs.add(parseInt(checkbox.value));
                        } else {
                            selectedJobs.delete(parseInt(checkbox.value));
                        }
                    });
                    updateBulkActionsVisibility();
                });
            }
        }
        
        function toggleJobSelection(jobId) {
            if (selectedJobs.has(jobId)) {
                selectedJobs.delete(jobId);
            } else {
                selectedJobs.add(jobId);
            }
            updateBulkActionsVisibility();
            updateSelectAllCheckbox();
        }
        
        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('selectAllJobs');
            const checkboxes = document.querySelectorAll('.job-checkbox');
            
            if (selectAllCheckbox && checkboxes.length > 0) {
                const checkedCount = document.querySelectorAll('.job-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === checkboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            }
        }
        
        function updateBulkActionsVisibility() {
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            
            if (bulkActions) {
                bulkActions.style.display = selectedJobs.size > 0 ? 'flex' : 'none';
            }
            
            if (selectedCount) {
                selectedCount.textContent = selectedJobs.size;
            }
        }
        
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllJobs') || document.getElementById('selectAllHeader');
            const checkboxes = document.querySelectorAll('.job-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
                if (selectAllCheckbox.checked) {
                    selectedJobs.add(parseInt(checkbox.value));
                } else {
                    selectedJobs.delete(parseInt(checkbox.value));
                }
            });
            
            updateBulkActionsVisibility();
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            applyFilters();
        }
        
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('typeFilter').value = '';
            document.getElementById('industryFilter').value = '';
            applyFilters();
        }
        
        function toggleExportDropdown() {
            const menu = document.getElementById('exportMenu');
            if (menu) {
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            }
        }
        
        // Close export dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const exportDropdown = document.querySelector('.export-dropdown');
            const exportMenu = document.getElementById('exportMenu');
            
            if (exportDropdown && exportMenu && !exportDropdown.contains(event.target)) {
                exportMenu.style.display = 'none';
            }
        });
        
        function bulkAction(action) {
            if (selectedJobs.size === 0) return;
            
            const jobIds = Array.from(selectedJobs);
            
            switch(action) {
                case 'activate':
                    bulkUpdateStatus(jobIds, 'Active');
                    break;
                case 'close':
                    bulkUpdateStatus(jobIds, 'Closed');
                    break;
                case 'delete':
                    bulkDelete(jobIds);
                    break;
                case 'duplicate':
                    bulkDuplicate(jobIds);
                    break;
            }
        }
        
        function bulkUpdateStatus(jobIds, status) {
            Swal.fire({
                title: `Update ${jobIds.length} job(s) to ${status}?`,
                text: "This action will be applied to all selected jobs.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#233a8b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, make ${status}!`
            }).then((result) => {
                if (result.isConfirmed) {
                    submitBulkAction('bulk_update_status', { job_ids: jobIds, status: status });
                }
            });
        }
        
        function bulkDelete(jobIds) {
            Swal.fire({
                title: `Delete ${jobIds.length} job(s)?`,
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitBulkAction('bulk_delete', { job_ids: jobIds });
                }
            });
        }
        
        function bulkDuplicate(jobIds) {
            Swal.fire({
                title: `Duplicate ${jobIds.length} job(s)?`,
                text: "This will create copies of the selected jobs.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, duplicate them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitBulkAction('bulk_duplicate', { job_ids: jobIds });
                }
            });
        }
        
        function submitBulkAction(action, data) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="data" value="${JSON.stringify(data)}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Job Management Functions
        function openAddJobModal() {
            document.getElementById('modalTitle').textContent = 'Add New Job Posting';
            document.getElementById('formAction').value = 'add_job';
            document.getElementById('jobId').value = '';
            document.getElementById('statusGroup').style.display = 'none';
            document.getElementById('jobForm').reset();
            document.getElementById('jobModal').style.display = 'block';
        }
        
        function editJob(jobId) {
            const job = allJobs.find(j => j.id == jobId);
            if (!job) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Job Posting';
            document.getElementById('formAction').value = 'update_job';
            document.getElementById('jobId').value = jobId;
            document.getElementById('statusGroup').style.display = 'block';
            
            // Populate form fields
            document.getElementById('title').value = job.title;
            document.getElementById('company').value = job.company;
            document.getElementById('description').value = job.description;
            document.getElementById('requirements').value = job.requirements;
            document.getElementById('salary_range').value = job.salary_range;
            document.getElementById('location').value = job.location;
            document.getElementById('job_type').value = job.job_type;
            document.getElementById('industry').value = job.industry;
            document.getElementById('status').value = job.status;
            
            document.getElementById('jobModal').style.display = 'block';
        }
        
        function viewJob(jobId) {
            const job = allJobs.find(j => j.id == jobId);
            if (!job) return;
            
            const content = `
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #233a8b; margin-bottom: 10px;">${escapeHtml(job.title)}</h3>
                    <p style="color: #666; margin-bottom: 15px;"><strong>Company:</strong> ${escapeHtml(job.company)}</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div><strong>Location:</strong> ${escapeHtml(job.location)}</div>
                        <div><strong>Type:</strong> ${escapeHtml(job.job_type)}</div>
                        <div><strong>Salary:</strong> ${escapeHtml(job.salary_range)}</div>
                        <div><strong>Industry:</strong> ${escapeHtml(job.industry)}</div>
                        <div><strong>Status:</strong> <span class="status-badge status-${job.status.toLowerCase()}">${job.status}</span></div>
                        <div><strong>Posted:</strong> ${formatDate(job.created_at)}</div>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #233a8b; margin-bottom: 10px;">Job Description</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; white-space: pre-wrap;">${escapeHtml(job.description)}</div>
                </div>
                
                <div>
                    <h4 style="color: #233a8b; margin-bottom: 10px;">Requirements</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; white-space: pre-wrap;">${escapeHtml(job.requirements)}</div>
                </div>
            `;
            
            document.getElementById('viewContent').innerHTML = content;
            document.getElementById('viewModal').style.display = 'block';
        }
        
        function viewApplications(jobId) {
            // This would typically fetch applications via AJAX
            Swal.fire({
                title: 'Job Applications',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Total Applications:</strong> 12</p>
                        <p><strong>New Applications:</strong> 3</p>
                        <p><strong>Interview Scheduled:</strong> 2</p>
                        <p><strong>Hired:</strong> 1</p>
                        <hr>
                        <p style="color: #666; font-size: 0.9rem;">Click "View Details" to see individual applications and manage candidates.</p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'View Details',
                cancelButtonText: 'Close',
                confirmButtonColor: '#233a8b'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to applications management page
                    window.location.href = `job_applications.php?job_id=${jobId}`;
                }
            });
        }
        
        function viewJobAnalytics(jobId) {
            const job = allJobs.find(j => j.id == jobId);
            if (!job) return;
            
            Swal.fire({
                title: `Analytics: ${job.title}`,
                html: `
                    <div style="text-align: left;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; color: #1976d2;">Views</h4>
                                <div style="font-size: 2rem; font-weight: bold; color: #1976d2;">156</div>
                            </div>
                            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; color: #2e7d32;">Applications</h4>
                                <div style="font-size: 2rem; font-weight: bold; color: #2e7d32;">12</div>
                            </div>
                        </div>
                        <div style="background: #fff3e0; padding: 15px; border-radius: 8px;">
                            <h4 style="margin: 0 0 10px 0; color: #f57c00;">Conversion Rate</h4>
                            <div style="font-size: 1.5rem; font-weight: bold; color: #f57c00;">7.7%</div>
                            <small style="color: #666;">Applications per view</small>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Detailed Report',
                cancelButtonText: 'Close',
                confirmButtonColor: '#233a8b'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Open detailed analytics modal or page
                    openDetailedAnalytics(jobId);
                }
            });
        }
        
        function openDetailedAnalytics(jobId) {
            // This would open a more detailed analytics modal
            const analyticsModal = document.getElementById('analyticsModal');
            if (analyticsModal) {
                analyticsModal.style.display = 'block';
            }
        }
        
        function deleteJob(jobId) {
            const job = allJobs.find(j => j.id == jobId);
            const jobTitle = job ? job.title : 'this job';
            
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
        
        function closeModal() {
            document.getElementById('jobModal').style.display = 'none';
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        function closeAnalyticsModal() {
            document.getElementById('analyticsModal').style.display = 'none';
        }
        
        function submitForm() {
            const form = document.getElementById('jobForm');
            const title = document.getElementById('title').value.trim();
            const company = document.getElementById('company').value.trim();
            const description = document.getElementById('description').value.trim();
            const requirements = document.getElementById('requirements').value.trim();
            
            if (!title || !company || !description || !requirements) {
                Swal.fire({
                    title: 'Missing Information',
                    text: 'Please fill in all required fields.',
                    icon: 'warning',
                    confirmButtonColor: '#233a8b'
                });
                return;
            }
            
            form.submit();
        }
        
        // Utility Functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        // Export Functions
        function exportJobs(format) {
            if (filteredJobs.length === 0) {
                Swal.fire({
                    title: 'No Data',
                    text: 'There are no jobs to export.',
                    icon: 'info',
                    confirmButtonColor: '#233a8b'
                });
                return;
            }
            
            if (format === 'csv') {
                exportToCSV();
            } else if (format === 'pdf') {
                exportToPDF();
            }
        }
        
        function exportToCSV() {
            const headers = ['Title', 'Company', 'Location', 'Type', 'Salary', 'Status', 'Created'];
            const csvContent = [
                headers.join(','),
                ...filteredJobs.map(job => [
                    `"${job.title}"`,
                    `"${job.company}"`,
                    `"${job.location}"`,
                    `"${job.job_type}"`,
                    `"${job.salary_range}"`,
                    `"${job.status}"`,
                    `"${formatDate(job.created_at)}"`
                ].join(','))
            ].join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `job_postings_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function exportToPDF() {
            // This would typically make an AJAX call to generate PDF
            Swal.fire({
                title: 'Exporting PDF',
                text: 'Generating PDF report...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            // Simulate PDF generation
            setTimeout(() => {
                Swal.fire({
                    title: 'PDF Ready',
                    text: 'Your job postings report has been generated.',
                    icon: 'success',
                    confirmButtonColor: '#233a8b'
                });
            }, 2000);
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const jobModal = document.getElementById('jobModal');
            const viewModal = document.getElementById('viewModal');
            const analyticsModal = document.getElementById('analyticsModal');
            
            if (event.target === jobModal) {
                closeModal();
            }
            if (event.target === viewModal) {
                closeViewModal();
            }
            if (event.target === analyticsModal) {
                closeAnalyticsModal();
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'n':
                        e.preventDefault();
                        openAddJobModal();
                        break;
                    case 'f':
                        e.preventDefault();
                        document.getElementById('searchInput').focus();
                        break;
                    case 'e':
                        e.preventDefault();
                        if (selectedJobs.size === 1) {
                            const jobId = Array.from(selectedJobs)[0];
                            editJob(jobId);
                        }
                        break;
                }
            }
        });
        
        // Check admin session and update UI
        fetch('session_check.php')
            .then(r => r.json())
            .then(data => {
                // Update username display
                document.getElementById('adminUsername').textContent = data.username;
                
                // Show/hide ADD ACCOUNT link based on admin type
                if (data.isMainAdmin) {
                    document.getElementById('addAccountLink').style.display = 'block';
                } else {
                    document.getElementById('addAccountLink').style.display = 'none';
                }
            })
            .catch(() => {
                console.error('Session check failed');
            });
    </script>
</body>
</html>