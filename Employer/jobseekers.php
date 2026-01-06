<?php
// jobseekers.php: Returns jobseeker data as JSON for job.html
header('Content-Type: application/json');
$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}
$sql = "SELECT *, 
    YEAR(CURDATE())-YEAR(dob) - (DATE_FORMAT(CURDATE(),'%m%d') < DATE_FORMAT(dob,'%m%d')) AS age,
    DAY(submission_date) AS submission_day,
    MONTH(submission_date) AS submission_month,
    YEAR(submission_date) AS submission_year
FROM jobseeker ORDER BY id DESC";
$res = $conn->query($sql);
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
$conn->close();
?>
