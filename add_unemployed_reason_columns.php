<?php
require_once __DIR__ . '/Employee/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Unemployment Reason Columns</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        .ok { color: #1b5e20; }
        .warn { color: #ef6c00; }
        .err { color: #b71c1c; }
    </style>
</head>
<body>
<h2>Update jobseeker table (unemployment reason fields)</h2>
<?php
$columns = [
    'unemployed_type_terminated_abroad' => 'TINYINT(1) DEFAULT 0',
    'unemployed_type_others' => 'TINYINT(1) DEFAULT 0',
    'unemployed_other_specify' => 'VARCHAR(100)',
];

foreach ($columns as $name => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM jobseeker LIKE '{$name}'");
    if ($check && $check->num_rows > 0) {
        echo "<p class='warn'>Column <strong>{$name}</strong> already exists.</p>";
        continue;
    }

    $sql = "ALTER TABLE jobseeker ADD COLUMN {$name} {$definition}";
    if ($conn->query($sql) === TRUE) {
        echo "<p class='ok'>Added column <strong>{$name}</strong>.</p>";
    } else {
        echo "<p class='err'>Failed adding <strong>{$name}</strong>: " . htmlspecialchars($conn->error) . "</p>";
    }
}

$conn->close();
?>
</body>
</html>
