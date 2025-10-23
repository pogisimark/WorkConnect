<?php
// Test page to verify forgot password functionality
require_once 'db.php';

echo "<h2>Testing Forgot Password Setup</h2>";

// Test database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>Database connection failed: " . $conn->connect_error . "</p>";
    exit();
} else {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
}

// Check if password_resets table exists
$result = $conn->query("SHOW TABLES LIKE 'password_resets'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ password_resets table exists</p>";
} else {
    echo "<p style='color: orange;'>⚠ password_resets table does not exist. Creating it now...</p>";
    
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id),
        INDEX idx_expires_at (expires_at)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ password_resets table created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating table: " . $conn->error . "</p>";
    }
}

// Check if employee_users table exists
$result = $conn->query("SHOW TABLES LIKE 'employee_users'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ employee_users table exists</p>";
    
    // Check if there are any users
    $result = $conn->query("SELECT COUNT(*) as count FROM employee_users");
    $row = $result->fetch_assoc();
    echo "<p style='color: blue;'>ℹ Found " . $row['count'] . " users in employee_users table</p>";
} else {
    echo "<p style='color: red;'>✗ employee_users table does not exist</p>";
}

// Test JSON response
echo "<h3>Testing JSON Response</h3>";
echo "<p>Testing forgot password handler...</p>";

// Simulate a test request
$test_data = json_encode(['email' => 'test@example.com']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/forgot_password_handler.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $test_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response) {
    $json_response = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✓ JSON response is valid</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Invalid JSON response</p>";
        echo "<p>Raw response: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ No response from handler</p>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #1a3876; }
p { margin: 5px 0; }
</style>
