<?php
// Script to add company_id column to job_postings table
require_once 'db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Add Company ID Column</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #4caf50; padding: 10px; background: #e8f5e9; border-radius: 4px; margin: 10px 0; }
        .error { color: #f44336; padding: 10px; background: #ffebee; border-radius: 4px; margin: 10px 0; }
        .info { color: #2196f3; padding: 10px; background: #e3f2fd; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <?php wc_render_ec2_logo_header(); ?>
<h1>Add Company ID Column to Job Postings</h1>";

// Check if column already exists
$check = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
if ($check && $check->num_rows > 0) {
    echo "<div class='info'>ℹ️ company_id column already exists in job_postings table.</div>";
} else {
    // Add company_id column
    $sql = "ALTER TABLE job_postings 
            ADD COLUMN company_id INT NULL,
            ADD INDEX idx_company_id (company_id)";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ company_id column added successfully!</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
}

// Optionally add foreign key constraint
$fk_check = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'job_postings' 
                          AND COLUMN_NAME = 'company_id' 
                          AND REFERENCED_TABLE_NAME = 'company_users'");
if ($fk_check && $fk_check->num_rows == 0) {
    $fk_sql = "ALTER TABLE job_postings 
               ADD CONSTRAINT fk_job_company 
               FOREIGN KEY (company_id) REFERENCES company_users(id) ON DELETE CASCADE";
    
    if ($conn->query($fk_sql) === TRUE) {
        echo "<div class='success'>✅ Foreign key constraint added successfully!</div>";
    } else {
        echo "<div class='info'>ℹ️ Foreign key constraint: " . $conn->error . "</div>";
    }
}

echo "<p><a href='dashboard.php'>Go to Dashboard</a> | <a href='jobposting.php'>Go to Job Postings</a></p>";
echo "</body></html>";

$conn->close();
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/ec2_logo_header.php'; ?>


