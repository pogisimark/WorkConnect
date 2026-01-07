<?php
// Recommended Jobs page for Employee Dashboard
require_once 'session_check.php';
require_once 'db.php';
require_once 'job_matching_algorithm.php';

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$matching = new JobMatchingAlgorithm($conn);

// Get recommended jobs
$recommendations = $matching->getRecommendedJobs($userId, 20);

// Get user preferences for display
$stmt = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$preferences = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle job application
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'apply_job') {
    $jobId = (int)$_POST['job_id'];
    
    // Get jobseeker ID
    $stmt = $conn->prepare("SELECT id FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $jobseeker = $result->fetch_assoc();
    $stmt->close();
    
    if ($jobseeker) {
        $jobseekerId = $jobseeker['id'];
        
        // Check if already applied
        $stmt = $conn->prepare("SELECT id FROM job_applications_extended WHERE jobseeker_id = ? AND job_posting_id = ?");
        $stmt->bind_param("ii", $jobseekerId, $jobId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$existing) {
            // Calculate compatibility score before creating application
            $compatibility_score = $matching->calculateCompatibilityScore($userId, $jobId);
            
            // Insert new application with compatibility score
            // This is the ONLY place where applications should be created
            $stmt = $conn->prepare("INSERT INTO job_applications_extended (jobseeker_id, job_posting_id, status, compatibility_score) VALUES (?, ?, 'Applied', ?)");
            $stmt->bind_param("iid", $jobseekerId, $jobId, $compatibility_score);
            
            if ($stmt->execute()) {
                $success_message = "Application submitted successfully!";
                // Refresh recommendations
                $recommendations = $matching->getRecommendedJobs($userId, 20);
            } else {
                $error_message = "Error submitting application: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_message = "You have already applied for this job.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommended Jobs - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-dashboard.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .stats-summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #233a8b;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .job-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .job-card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            position: relative;
        }
        
        .job-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #233a8b;
            margin: 0 0 10px 0;
            line-height: 1.3;
        }
        
        .company-name {
            font-size: 1.1rem;
            color: #666;
            margin: 0 0 15px 0;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .compatibility-score {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .compatibility-score.medium {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .compatibility-score.low {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        
        .job-card-body {
            padding: 20px;
        }
        
        .job-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .job-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .job-requirements h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 0.9rem;
        }
        
        .job-requirements p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
            line-height: 1.4;
        }
        
        .job-actions {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-apply {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            flex: 1;
            transition: all 0.3s;
        }
        
        .btn-apply:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-2px);
        }
        
        .btn-view {
            background: #233a8b;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-view:hover {
            background: #1a2d6b;
        }
        
        .applied-badge {
            background: #6c757d;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .no-jobs {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-jobs i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-jobs h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .no-jobs p {
            color: #999;
            margin-bottom: 20px;
        }
        
        
        @media (max-width: 768px) {
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .job-meta {
                flex-direction: column;
                gap: 8px;
            }
            
            .job-actions {
                flex-direction: column;
            }
            
            .btn-apply,
            .btn-view {
                width: 100%;
            }
            
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="content-section">
                <div class="welcome-card">
                    <h1><i class="fas fa-bullseye"></i> Recommended Jobs</h1>
                    <p>Jobs matched to your skills, experience, and preferences</p>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="stats-summary">
                    <h3>Your Job Recommendations</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($recommendations); ?></div>
                            <div class="stat-label">Total Recommendations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count(array_filter($recommendations, function($job) { return $job['compatibility_score'] >= 80; })); ?></div>
                            <div class="stat-label">High Match (80%+)</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count(array_filter($recommendations, function($job) { return $job['already_applied'] == 1; })); ?></div>
                            <div class="stat-label">Already Applied</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count(array_filter($recommendations, function($job) { return $job['already_applied'] == 0; })); ?></div>
                            <div class="stat-label">Available to Apply</div>
                        </div>
                    </div>
                </div>
                
                <div class="filters-section">
                    <h3>Filter Jobs</h3>
                    <form method="GET" id="filterForm">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label for="location">Location</label>
                                <select name="location" id="location">
                                    <option value="">All Locations</option>
                                    <option value="Manila">Manila</option>
                                    <option value="Quezon City">Quezon City</option>
                                    <option value="Makati">Makati</option>
                                    <option value="Taguig">Taguig</option>
                                    <option value="Pasig">Pasig</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="job_type">Job Type</label>
                                <select name="job_type" id="job_type">
                                    <option value="">All Types</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="min_score">Minimum Match Score</label>
                                <select name="min_score" id="min_score">
                                    <option value="0">Any Score</option>
                                    <option value="70">70%+</option>
                                    <option value="80">80%+</option>
                                    <option value="90">90%+</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="industry">Industry</label>
                                <select name="industry" id="industry">
                                    <option value="">All Industries</option>
                                    <option value="Technology">Technology</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Customer Service">Customer Service</option>
                                    <option value="Analytics">Analytics</option>
                                    <option value="Design">Design</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($recommendations)): ?>
                    <div class="no-jobs">
                        <i class="fas fa-search"></i>
                        <h3>No Job Recommendations Found</h3>
                        <p>We couldn't find any jobs that match your current profile. Try updating your skills and preferences.</p>
                        <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">
                            <i class="fas fa-user-edit"></i> Update Profile
                        </button>
                    </div>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($recommendations as $job): ?>
                            <div class="job-card">
                                <div class="job-card-header">
                                    <div class="compatibility-score <?php 
                                        echo $job['compatibility_score'] >= 80 ? '' : 
                                            ($job['compatibility_score'] >= 60 ? 'medium' : 'low'); 
                                    ?>">
                                        <?php echo round($job['compatibility_score']); ?>% Match
                                    </div>
                                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <p class="company-name"><?php echo htmlspecialchars($job['company']); ?></p>
                                    <div class="job-meta">
                                        <div class="job-meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($job['location']); ?>
                                        </div>
                                        <div class="job-meta-item">
                                            <i class="fas fa-briefcase"></i>
                                            <?php echo htmlspecialchars($job['job_type']); ?>
                                        </div>
                                        <?php if ($job['salary_range']): ?>
                                        <div class="job-meta-item">
                                            <i class="fas fa-money-bill-wave"></i>
                                            ₱ <?php echo htmlspecialchars($job['salary_range']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <div class="job-description">
                                        <?php echo htmlspecialchars(substr($job['description'], 0, 200)) . '...'; ?>
                                    </div>
                                    
                                    <div class="job-requirements">
                                        <h4>Key Requirements:</h4>
                                        <p><?php echo htmlspecialchars(substr($job['requirements'], 0, 150)) . '...'; ?></p>
                                    </div>
                                    
                                    <div class="job-actions">
                                        <?php if ($job['already_applied']): ?>
                                            <div class="applied-badge">
                                                <i class="fas fa-check"></i> Applied
                                            </div>
                                        <?php else: ?>
                                            <form method="POST" style="flex: 1;">
                                                <input type="hidden" name="action" value="apply_job">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" class="btn-apply" onclick="return confirmApply()">
                                                    <i class="fas fa-paper-plane"></i> Apply Now
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button class="btn-view" onclick="viewJobDetails(<?php echo $job['id']; ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <script>
        function confirmApply() {
            return confirm('Are you sure you want to apply for this job?');
        }
        
        function viewJobDetails(jobId) {
            Swal.fire({
                title: 'Job Details',
                text: 'This would show detailed job information, requirements, and company details.',
                icon: 'info',
                confirmButtonText: 'OK'
            });
        }
        
        function clearFilters() {
            document.getElementById('location').value = '';
            document.getElementById('job_type').value = '';
            document.getElementById('min_score').value = '0';
            document.getElementById('industry').value = '';
            document.getElementById('filterForm').submit();
        }
        
    </script>
</body>
</html>