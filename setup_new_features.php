<?php
// Database setup script for all three new features
$host = "workconnect.cz2woayyket3.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully.<br><br>";

// Execute all three SQL files
$sqlFiles = [
    'database_updates_matching.sql',
    'database_updates_resume.sql', 
    'database_updates_analytics.sql'
];

foreach ($sqlFiles as $file) {
    echo "Executing $file...<br>";
    $sql = file_get_contents($file);
    
    // Remove comments and split by semicolon, but be careful with CSS in INSERT statements
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove comment lines
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove block comments
    
    // Split by semicolon, but handle multi-line INSERT statements properly
    $statements = [];
    $currentStatement = '';
    $inString = false;
    $stringChar = '';
    
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];
        
        if (!$inString && ($char === '"' || $char === "'")) {
            $inString = true;
            $stringChar = $char;
        } elseif ($inString && $char === $stringChar) {
            // Check for escaped quotes
            if ($i > 0 && $sql[$i-1] !== '\\') {
                $inString = false;
            }
        } elseif (!$inString && $char === ';') {
            $statements[] = trim($currentStatement);
            $currentStatement = '';
            continue;
        }
        
        $currentStatement .= $char;
    }
    
    // Add the last statement if it doesn't end with semicolon
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if ($conn->query($statement) === TRUE) {
                echo "✅ Statement executed successfully.<br>";
            } else {
                if (strpos($conn->error, "already exists") !== false || 
                    strpos($conn->error, "Duplicate column") !== false ||
                    strpos($conn->error, "Duplicate entry") !== false) {
                    echo "ℹ️ " . $conn->error . "<br>";
                } else {
                    echo "❌ Error: " . $conn->error . "<br>";
                }
            }
        }
    }
    echo "<br>";
}

echo "Database setup completed for all three features!<br>";
$conn->close();
?>
