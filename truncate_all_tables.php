<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Truncate All Tables Script for WorkConnect
// WARNING: This will delete ALL data from ALL tables!
// Use this only for testing/development purposes.
//
// Expected tables (28) - must match setup_complete_database.php:
//   Core (5): employee_users, admin_accounts, jobseeker, company_users, skill_registry
//   Utility (3): notifications, password_resets, company_password_resets
//   Feature (11): job_postings, user_preferences, job_applications_extended, follow_up_requests,
//                 admin_company_follow_up, resume_templates, resumes, application_analytics,
//                 application_timeline, analytics_insights, monthly_analytics
//   Announcement (5): announcements, announcement_attachments, announcement_tags, announcement_views, announcement_clicks
//   Resume new (4): resumes_new, resume_work_experience, resume_education, resume_certifications

$host = "workconnect.cp28esmqk7aq.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db_name = "WorkConnect";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Truncate All Tables - WorkConnect</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #dc3545; 
            border-bottom: 3px solid #dc3545;
            padding-bottom: 10px;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
        }
        .success { 
            color: #28a745; 
            padding: 10px;
            background: #d4edda;
            border-radius: 4px;
            margin: 5px 0;
        }
        .error { 
            color: #dc3545; 
            padding: 10px;
            background: #f8d7da;
            border-radius: 4px;
            margin: 5px 0;
        }
        .info { 
            color: #17a2b8; 
            padding: 10px;
            background: #d1ecf1;
            border-radius: 4px;
            margin: 5px 0;
        }
        .step { 
            margin: 20px 0; 
            padding: 15px; 
            background: #f8f9fa; 
            border-left: 4px solid #233a8b; 
        }
        .table-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .table-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
            columns: 3;
            column-gap: 20px;
        }
        .table-list li {
            padding: 5px 0;
            break-inside: avoid;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
<?php wc_render_ec2_logo_header(); ?>
<div class='container'>
<h1>🗑️ Truncate All Tables - WorkConnect</h1>";

// Check if confirmation is provided
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$confirmed) {
    echo "<div class='warning'>
        <h2>⚠️ WARNING: This will delete ALL data from ALL tables!</h2>
        <p>This action cannot be undone. All data in the database will be permanently deleted.</p>
        <p><strong>Use this only for testing/development purposes!</strong></p>
    </div>";
    
    // Connect to get table list
    $conn = new mysqli($host, $user, $pass, $db_name);
    if ($conn->connect_error) {
        die("<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p></div></div></body></html>");
    }
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    
    // Expected tables from setup_complete_database.php (28 tables)
    $expected_tables = [
        'employee_users', 'admin_accounts', 'jobseeker', 'company_users', 'skill_registry',
        'notifications', 'password_resets', 'company_password_resets',
        'job_postings', 'user_preferences', 'job_applications_extended', 'follow_up_requests', 'admin_company_follow_up', 'resume_templates',
        'resumes', 'application_analytics', 'application_timeline', 'analytics_insights', 'monthly_analytics',
        'announcements', 'announcement_attachments', 'announcement_tags', 'announcement_views', 'announcement_clicks',
        'resumes_new', 'resume_work_experience', 'resume_education', 'resume_certifications'
    ];
    
    $conn->close();
    
    echo "<div class='step'>
        <h2>Tables that will be truncated (" . count($tables) . " tables found):</h2>";
    
    // Check if all expected tables are present
    $missing_tables = array_diff($expected_tables, $tables);
    $extra_tables = array_diff($tables, $expected_tables);
    
    if (count($missing_tables) > 0) {
        echo "<div class='info'><strong>Note:</strong> " . count($missing_tables) . " expected table(s) not found (may not exist yet):<br>";
        foreach ($missing_tables as $missing) {
            echo "• $missing<br>";
        }
        echo "</div>";
    }
    
    if (count($extra_tables) > 0) {
        echo "<div class='info'><strong>Note:</strong> " . count($extra_tables) . " additional table(s) found (will also be truncated):<br>";
        foreach ($extra_tables as $extra) {
            echo "• $extra<br>";
        }
        echo "</div>";
    }
    
    echo "<div class='table-list'>
            <ul>";
    foreach ($tables as $table) {
        $is_expected = in_array($table, $expected_tables) ? '✓' : '•';
        echo "<li>$is_expected $table</li>";
    }
    echo "      </ul>
        </div>
    </div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>
        <a href='?confirm=yes' class='btn' onclick='return confirm(\"Are you absolutely sure? This will delete ALL data!\");'>Yes, Truncate All Tables</a>
        <a href='setup_complete_database.php' class='btn btn-secondary'>Cancel - Go to Setup</a>
    </div>";
    
} else {
    // Proceed with truncation
    echo "<div class='step'><h2>Step 1: Connecting to Database...</h2>";
    $conn = new mysqli($host, $user, $pass, $db_name);
    if ($conn->connect_error) {
        die("<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p></div></div></body></html>");
    }
    echo "<p class='success'>✅ Connected to database '$db_name' successfully</p></div>";
    
    // Disable foreign key checks temporarily
    echo "<div class='step'><h2>Step 2: Disabling Foreign Key Checks...</h2>";
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    echo "<p class='success'>✅ Foreign key checks disabled</p></div>";
    
    // Get all tables
    echo "<div class='step'><h2>Step 3: Getting List of Tables...</h2>";
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    echo "<p class='success'>✅ Found " . count($tables) . " tables</p></div>";
    
    // Truncate each table
    echo "<div class='step'><h2>Step 4: Truncating Tables...</h2>";
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    foreach ($tables as $table) {
        $sql = "TRUNCATE TABLE `$table`";
        if ($conn->query($sql) === TRUE) {
            echo "<p class='success'>✅ Truncated: $table</p>";
            $success_count++;
        } else {
            echo "<p class='error'>❌ Error truncating $table: " . $conn->error . "</p>";
            $error_count++;
            $errors[] = "$table: " . $conn->error;
        }
    }
    
    // Re-enable foreign key checks
    echo "<div class='step'><h2>Step 5: Re-enabling Foreign Key Checks...</h2>";
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "<p class='success'>✅ Foreign key checks re-enabled</p></div>";
    
    $conn->close();
    
    // Summary
    echo "<div class='step' style='background: #d4edda; border-left-color: #28a745;'>
        <h2 style='color: #28a745;'>✅ Truncation Complete!</h2>
        <p><strong>Summary:</strong></p>
        <ul>
            <li>✅ Successfully truncated: <strong>$success_count</strong> tables</li>";
    
    if ($error_count > 0) {
        echo "            <li>❌ Errors: <strong>$error_count</strong> tables</li>";
        echo "            <li>Error details:</li><ul>";
        foreach ($errors as $error) {
            echo "                <li style='color: #dc3545;'>$error</li>";
        }
        echo "            </ul>";
    }
    
    echo "        </ul>
        <p><strong>All tables are now empty and ready for fresh data testing.</strong></p>
        <p style='margin-top: 20px;'>
            <a href='setup_complete_database.php' class='btn btn-secondary'>Run Database Setup</a>
            <a href='truncate_all_tables.php' class='btn btn-secondary'>Back to Truncate Page</a>
        </p>
    </div>";
}

echo "</div></body></html>";
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/ec2_logo_header.php'; ?>

