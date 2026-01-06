<?php
// Simple PHP script to add database columns safely
$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully.<br><br>";

// Try to add rejection_reason column
echo "Attempting to add rejection_reason column...<br>";
$sql1 = "ALTER TABLE jobseeker ADD COLUMN rejection_reason TEXT";
if ($conn->query($sql1) === TRUE) {
    echo "✅ rejection_reason column added successfully.<br>";
} else {
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "ℹ️ rejection_reason column already exists.<br>";
    } else {
        echo "❌ Error adding rejection_reason column: " . $conn->error . "<br>";
    }
}

// Try to create notifications table
echo "<br>Attempting to create notifications table...<br>";
$sql2 = "CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql2) === TRUE) {
    echo "✅ notifications table created successfully.<br>";
} else {
    if (strpos($conn->error, "already exists") !== false) {
        echo "ℹ️ notifications table already exists.<br>";
    } else {
        echo "❌ Error creating notifications table: " . $conn->error . "<br>";
    }
}

echo "<br>Database setup completed!<br>";
$conn->close();
?>
