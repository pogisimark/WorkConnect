<?php
// Script to add profile fields to company_users table
require_once 'db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Update Company Profile Fields - WorkConnect</title>
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
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #f44336;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            color: #2196f3;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Update Company Profile Fields</h1>";

// Add columns to company_users table
$alterations = [
    "ALTER TABLE company_users ADD COLUMN IF NOT EXISTS logo VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE company_users ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL",
    "ALTER TABLE company_users ADD COLUMN IF NOT EXISTS website VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE company_users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL",
    "ALTER TABLE company_users ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL"
];

$success_count = 0;
$error_count = 0;

foreach ($alterations as $sql) {
    // MySQL doesn't support IF NOT EXISTS for ALTER TABLE, so we'll check first
    $column_name = '';
    if (strpos($sql, 'logo') !== false) $column_name = 'logo';
    elseif (strpos($sql, 'description') !== false) $column_name = 'description';
    elseif (strpos($sql, 'website') !== false) $column_name = 'website';
    elseif (strpos($sql, 'address') !== false) $column_name = 'address';
    elseif (strpos($sql, 'phone') !== false) $column_name = 'phone';
    
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM company_users LIKE '$column_name'");
    
    if ($check && $check->num_rows > 0) {
        echo "<div class='info'>ℹ️ Column '$column_name' already exists.</div>";
    } else {
        // Remove IF NOT EXISTS and execute
        $clean_sql = str_replace(' IF NOT EXISTS', '', $sql);
        if ($conn->query($clean_sql) === TRUE) {
            echo "<div class='success'>✅ Column '$column_name' added successfully!</div>";
            $success_count++;
        } else {
            echo "<div class='error'>❌ Error adding column '$column_name': " . $conn->error . "</div>";
            $error_count++;
        }
    }
}

// Create uploads directory if it doesn't exist
$upload_dir = '../assets/uploads/company_logos/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<div class='success'>✅ Upload directory created: $upload_dir</div>";
    } else {
        echo "<div class='error'>❌ Could not create upload directory: $upload_dir</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Upload directory already exists: $upload_dir</div>";
}

echo "<hr>";
echo "<p><strong>Summary:</strong> $success_count columns added, $error_count errors</p>";
echo "<p><a href='profile.php'>Go to Company Profile</a> | <a href='dashboard.php'>Go to Dashboard</a></p>";
echo "</div></body></html>";

$conn->close();
?>

