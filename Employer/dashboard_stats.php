<?php
// dashboard_stats.php
header('Content-Type: application/json');
require_once 'db.php';

// Total Jobseekers (count all in jobseeker table)
$res1 = $conn->query("SELECT COUNT(*) as total FROM jobseeker");
$total_jobseekers = $res1 ? intval($res1->fetch_assoc()['total']) : 0;

// Skills Registered (count distinct skills, ignoring empty/null)
$res2 = $conn->query("SELECT COUNT(DISTINCT skills) as total FROM skill_registry WHERE skills IS NOT NULL AND skills != ''");
$skills_registered = $res2 ? intval($res2->fetch_assoc()['total']) : 0;

// New Applicants (This Week) - from jobseeker table using submission_month and submission_year
$current_year = date('Y');
$current_month = date('n'); // n = month without leading zeros
$res3 = $conn->query("SELECT COUNT(*) as total FROM jobseeker WHERE submission_year = $current_year AND submission_month = $current_month");
$new_applicants = $res3 ? intval($res3->fetch_assoc()['total']) : 0;

// Successfully Placed Jobseekers (count accepted applications)
$res4 = $conn->query("SELECT COUNT(*) as total FROM jobseeker WHERE application_status = 'accepted'");
$placed_jobseekers = $res4 ? intval($res4->fetch_assoc()['total']) : 0;

// Verified employer companies only (email verified — same rule as referrals / get_companies)
$total_companies = 0;
$evCol = @$conn->query("SHOW COLUMNS FROM company_users LIKE 'email_verified'");
if ($evCol && $evCol->num_rows > 0) {
    $res5 = @$conn->query("SELECT COUNT(*) as total FROM company_users WHERE COALESCE(email_verified, 0) = 1");
} else {
    $res5 = @$conn->query("SELECT COUNT(*) as total FROM company_users");
}
if ($res5) {
    $total_companies = intval($res5->fetch_assoc()['total']);
}

// Output
$data = [
    'total_jobseekers' => $total_jobseekers,
    'skills_registered' => $skills_registered,
    'new_applicants' => $new_applicants,
    'placed_jobseekers' => $placed_jobseekers,
    'total_companies' => $total_companies
];
echo json_encode($data);
