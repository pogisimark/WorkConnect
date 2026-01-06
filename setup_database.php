<?php
// Database setup script for rejection reason and notifications
$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully.<br>";

// Add rejection_reason column to jobseeker table if it doesn't exist
$check_column = "SHOW COLUMNS FROM jobseeker LIKE 'rejection_reason'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE jobseeker ADD COLUMN rejection_reason TEXT";
    if ($conn->query($sql) === TRUE) {
        echo "Added rejection_reason column to jobseeker table.<br>";
    } else {
        echo "Error adding rejection_reason column: " . $conn->error . "<br>";
    }
} else {
    echo "rejection_reason column already exists.<br>";
}

// Create notifications table if it doesn't exist
$check_table = "SHOW TABLES LIKE 'notifications'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Created notifications table.<br>";
    } else {
        echo "Error creating notifications table: " . $conn->error . "<br>";
    }
} else {
    echo "notifications table already exists.<br>";
}

$conn->close();
echo "Database setup completed!";
?>
