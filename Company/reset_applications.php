<?php
// Script to reset/truncate job_applications_extended table
// This will clear all application records to verify that auto-applications are fixed
require_once 'db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reset Applications - WorkConnect</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a3876;
        }
        .success {
            color: #4caf50;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #f44336;
            padding: 15px;
            background: #ffebee;
            border-radius: 4px;
            margin: 10px 0;
        }
        .warning {
            color: #ff9800;
            padding: 15px;
            background: #fff3e0;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            color: #2196f3;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 4px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #1a3876;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px 0 0;
        }
        .btn:hover {
            background: #2c5aa0;
        }
        .btn-danger {
            background: #f44336;
        }
        .btn-danger:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Reset Job Applications Table</h1>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'job_applications_extended'");
if (!$table_check || $table_check->num_rows == 0) {
    echo "<div class='error'>❌ job_applications_extended table does not exist.</div>";
} else {
    // Get current count
    $count_result = $conn->query("SELECT COUNT(*) as count FROM job_applications_extended");
    $current_count = $count_result->fetch_assoc()['count'] ?? 0;
    
    echo "<div class='info'>ℹ️ Current number of application records: <strong>$current_count</strong></div>";
    
    if ($current_count > 0) {
        echo "<div class='warning'>⚠️ This will delete ALL application records from the job_applications_extended table.</div>";
    }
    
    // Handle truncate action
    if (isset($_GET['action']) && $_GET['action'] === 'truncate') {
        // Truncate the table
        if ($conn->query("TRUNCATE TABLE job_applications_extended") === TRUE) {
            echo "<div class='success'>✅ job_applications_extended table has been reset successfully!</div>";
            echo "<div class='info'>ℹ️ All application records have been deleted. The table is now empty.</div>";
            echo "<div class='info'>ℹ️ <strong>Next Steps:</strong></div>";
            echo "<ul>";
            echo "<li>Login as an Employee user</li>";
            echo "<li>View Recommended Jobs (this should NOT create any applications)</li>";
            echo "<li>Click 'Apply' on a job (this SHOULD create one application)</li>";
            echo "<li>Check Company Dashboard to verify only explicit applications are recorded</li>";
            echo "</ul>";
        } else {
            echo "<div class='error'>❌ Error truncating table: " . $conn->error . "</div>";
        }
    } else {
        if ($current_count > 0) {
            echo "<p><strong>Are you sure you want to delete all application records?</strong></p>";
            echo "<a href='?action=truncate' class='btn btn-danger' onclick=\"return confirm('Are you sure you want to delete ALL application records? This cannot be undone!');\">Yes, Reset All Applications</a>";
        } else {
            echo "<div class='info'>ℹ️ The table is already empty. No action needed.</div>";
        }
    }
}

echo "<hr>";
echo "<p><a href='dashboard.php' class='btn'>Go to Dashboard</a></p>";
echo "</div></body></html>";

$conn->close();
?>

