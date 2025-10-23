<?php
require_once 'db.php';
header('Content-Type: application/json');

$result = $conn->query('SELECT id, username FROM admin_accounts ORDER BY id ASC');
$admins = [];
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}
echo json_encode($admins);
$conn->close();
