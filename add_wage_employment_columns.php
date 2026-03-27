<?php
// Adds new wage-employed subtype columns to existing jobseeker table.
require_once __DIR__ . '/Employee/db.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Add Wage Employment Columns</title></head><body>";
echo "<h2>Add Wage Employment Columns</h2>";

$columns = [
    'self_type_freelancer' => 'TINYINT(1) DEFAULT 0',
    'self_type_artisan' => 'TINYINT(1) DEFAULT 0',
];

foreach ($columns as $name => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM jobseeker LIKE '{$name}'");
    if ($check && $check->num_rows > 0) {
        echo "<p>ℹ️ Column <strong>{$name}</strong> already exists.</p>";
        continue;
    }

    $sql = "ALTER TABLE jobseeker ADD COLUMN {$name} {$definition}";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Added column <strong>{$name}</strong>.</p>";
    } else {
        echo "<p>❌ Failed adding <strong>{$name}</strong>: " . htmlspecialchars($conn->error) . "</p>";
    }
}

$conn->close();
echo "<p>Done.</p></body></html>";
