<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Fetch all companies with their names and emails
    $query = "SELECT id, company_name, email FROM company_users ORDER BY company_name ASC";
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

