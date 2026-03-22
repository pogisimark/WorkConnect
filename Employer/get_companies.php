<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Only companies that verified their email (for admin referrals)
    $col = $conn->query("SHOW COLUMNS FROM company_users LIKE 'email_verified'");
    $hasVerified = $col && $col->num_rows > 0;
    if ($hasVerified) {
        $query = "SELECT id, company_name, email FROM company_users WHERE COALESCE(email_verified, 0) = 1 ORDER BY company_name ASC";
    } else {
        $query = "SELECT id, company_name, email FROM company_users ORDER BY company_name ASC";
    }
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Error fetching companies: " . $conn->error);
    }
    
    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = [
            'id' => $row['id'],
            'company_name' => $row['company_name'],
            'email' => $row['email']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'companies' => $companies
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>

