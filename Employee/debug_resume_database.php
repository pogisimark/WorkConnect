<?php
require_once 'db.php';

echo "<h2>Recent Resumes in Database</h2>";

$result = $conn->query("SELECT id, user_id, resume_name, personal_info, work_experience, education, skills, certifications, created_at FROM resumes ORDER BY created_at DESC LIMIT 5");

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Resume Name</th><th>Personal Info</th><th>Work Exp</th><th>Education</th><th>Skills</th><th>Certifications</th><th>Created</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['resume_name']) . "</td>";
        echo "<td style='max-width: 200px; word-wrap: break-word;'>" . htmlspecialchars(substr($row['personal_info'], 0, 100)) . "...</td>";
        echo "<td style='max-width: 200px; word-wrap: break-word;'>" . htmlspecialchars(substr($row['work_experience'], 0, 100)) . "...</td>";
        echo "<td style='max-width: 200px; word-wrap: break-word;'>" . htmlspecialchars(substr($row['education'], 0, 100)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['skills']) . "</td>";
        echo "<td style='max-width: 200px; word-wrap: break-word;'>" . htmlspecialchars(substr($row['certifications'], 0, 100)) . "...</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No resumes found in database.</p>";
}

echo "<h3>Database Connection Test</h3>";
if ($conn) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

echo "<h3>Resumes Table Structure</h3>";
$result = $conn->query("DESCRIBE resumes");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
