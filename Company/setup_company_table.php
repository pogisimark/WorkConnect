<?php
// Setup script to create company_users table
require_once 'db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Company Table - WorkConnect</title>
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
        <h1>Company Users Table Setup</h1>";

// Create company_users table
$sql = "CREATE TABLE IF NOT EXISTS company_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<div class='success'>✅ company_users table created successfully!</div>";
} else {
    if (strpos($conn->error, "already exists") !== false) {
        echo "<div class='info'>ℹ️ company_users table already exists.</div>";
    } else {
        echo "<div class='error'>❌ Error creating company_users table: " . $conn->error . "</div>";
    }
}

echo "<p><a href='signup.php'>Go to Company Signup</a> | <a href='login.php'>Go to Company Login</a></p>";
echo "</div></body></html>";

$conn->close();
?>
