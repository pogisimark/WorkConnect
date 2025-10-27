<?php
// Job Matching Algorithm for WorkConnect
// This file contains the core matching logic for recommending jobs to job seekers

class JobMatchingAlgorithm {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Calculate compatibility score between a job seeker and job posting
     * Returns a score from 0-100
     */
    public function calculateCompatibilityScore($userId, $jobPostingId) {
        // Get job seeker data
        $jobSeeker = $this->getJobSeekerData($userId);
        if (!$jobSeeker) return 0;
        
        // Get job posting data
        $jobPosting = $this->getJobPostingData($jobPostingId);
        if (!$jobPosting) return 0;
        
        // Calculate different matching factors
        $skillScore = $this->calculateSkillMatch($jobSeeker, $jobPosting);
        $locationScore = $this->calculateLocationMatch($userId, $jobPosting);
        $experienceScore = $this->calculateExperienceMatch($jobSeeker, $jobPosting);
        $salaryScore = $this->calculateSalaryMatch($userId, $jobPosting);
        $jobTypeScore = $this->calculateJobTypeMatch($userId, $jobPosting);
        
        // Weighted average of all scores
        $weights = [
            'skills' => 0.35,      // Most important factor
            'location' => 0.20,     // Important for practical reasons
            'experience' => 0.20,   // Important for qualification
            'salary' => 0.15,      // Important for satisfaction
            'job_type' => 0.10     // Nice to have match
        ];
        
        $totalScore = ($skillScore * $weights['skills']) +
                     ($locationScore * $weights['location']) +
                     ($experienceScore * $weights['experience']) +
                     ($salaryScore * $weights['salary']) +
                     ($jobTypeScore * $weights['job_type']);
        
        return round($totalScore, 2);
    }
    
    /**
     * Calculate skill matching score
     */
    private function calculateSkillMatch($jobSeeker, $jobPosting) {
        $jobSeekerSkills = json_decode($jobSeeker['skills_array'] ?? '[]', true);
        $jobRequirements = strtolower($jobPosting['requirements']);
        
        if (empty($jobSeekerSkills)) return 30; // Default score if no skills listed
        
        $matchedSkills = 0;
        $totalSkills = count($jobSeekerSkills);
        
        // Common skills to look for in job requirements
        $skillKeywords = [
            'php', 'mysql', 'javascript', 'html', 'css', 'python', 'java', 'c++',
            'communication', 'leadership', 'teamwork', 'problem solving',
            'marketing', 'sales', 'customer service', 'data analysis',
            'project management', 'time management', 'creativity',
            'microsoft office', 'excel', 'word', 'powerpoint',
            'photoshop', 'illustrator', 'adobe', 'design',
            'accounting', 'finance', 'budgeting', 'reporting'
        ];
        
        foreach ($jobSeekerSkills as $skill) {
            $skillLower = strtolower($skill);
            
            // Direct skill match
            if (strpos($jobRequirements, $skillLower) !== false) {
                $matchedSkills++;
                continue;
            }
            
            // Check for related keywords
            foreach ($skillKeywords as $keyword) {
                if (strpos($skillLower, $keyword) !== false && 
                    strpos($jobRequirements, $keyword) !== false) {
                    $matchedSkills++;
                    break;
                }
            }
        }
        
        // Calculate percentage and apply curve for better distribution
        $skillMatchPercentage = ($matchedSkills / $totalSkills) * 100;
        
        // Apply curve: 0-50% maps to 0-60, 50-100% maps to 60-100
        if ($skillMatchPercentage <= 50) {
            return ($skillMatchPercentage / 50) * 60;
        } else {
            return 60 + (($skillMatchPercentage - 50) / 50) * 40;
        }
    }
    
    /**
     * Calculate location matching score
     */
    private function calculateLocationMatch($userId, $jobPosting) {
        $preferences = $this->getUserPreferences($userId);
        $jobLocation = strtolower($jobPosting['location']);
        
        if (!$preferences || empty($preferences['preferred_locations'])) {
            return 70; // Default score if no location preferences
        }
        
        $preferredLocations = json_decode($preferences['preferred_locations'], true);
        
        foreach ($preferredLocations as $preferredLocation) {
            $preferredLower = strtolower($preferredLocation);
            
            // Exact match
            if ($preferredLower === $jobLocation) {
                return 100;
            }
            
            // Partial match (e.g., "Manila" matches "Metro Manila")
            if (strpos($jobLocation, $preferredLower) !== false || 
                strpos($preferredLower, $jobLocation) !== false) {
                return 85;
            }
        }
        
        return 40; // No location match
    }
    
    /**
     * Calculate experience matching score
     */
    private function calculateExperienceMatch($jobSeeker, $jobPosting) {
        $jobSeekerExperience = (int)($jobSeeker['years_experience'] ?? 0);
        $requirements = strtolower($jobPosting['requirements']);
        
        // Extract experience requirements from job posting
        $experienceRequired = $this->extractExperienceRequirement($requirements);
        
        if ($experienceRequired === null) {
            return 80; // No specific experience requirement
        }
        
        $difference = abs($jobSeekerExperience - $experienceRequired);
        
        // Score based on how close the experience is
        if ($difference === 0) {
            return 100; // Perfect match
        } elseif ($difference <= 1) {
            return 90; // Very close
        } elseif ($difference <= 2) {
            return 75; // Close
        } elseif ($difference <= 3) {
            return 60; // Somewhat close
        } else {
            return max(30, 60 - ($difference * 5)); // Decreasing score
        }
    }
    
    /**
     * Calculate salary matching score
     */
    private function calculateSalaryMatch($userId, $jobPosting) {
        $preferences = $this->getUserPreferences($userId);
        $salaryRange = $jobPosting['salary_range'];
        
        if (!$preferences || !$preferences['min_salary'] || !$salaryRange) {
            return 70; // Default score if no salary info
        }
        
        $minExpectedSalary = (float)$preferences['min_salary'];
        $jobSalaryRange = $this->parseSalaryRange($salaryRange);
        
        if (!$jobSalaryRange) {
            return 70; // Can't parse salary range
        }
        
        $jobMinSalary = $jobSalaryRange['min'];
        $jobMaxSalary = $jobSalaryRange['max'];
        
        // Perfect match if expected salary is within job range
        if ($minExpectedSalary >= $jobMinSalary && $minExpectedSalary <= $jobMaxSalary) {
            return 100;
        }
        
        // Calculate score based on how close it is
        if ($minExpectedSalary < $jobMinSalary) {
            $difference = $jobMinSalary - $minExpectedSalary;
            $percentage = ($difference / $jobMinSalary) * 100;
            
            if ($percentage <= 10) return 90;
            elseif ($percentage <= 20) return 80;
            elseif ($percentage <= 30) return 70;
            else return max(40, 70 - ($percentage - 30));
        } else {
            // Expected salary is higher than job max
            $difference = $minExpectedSalary - $jobMaxSalary;
            $percentage = ($difference / $jobMaxSalary) * 100;
            
            if ($percentage <= 10) return 85;
            elseif ($percentage <= 20) return 75;
            elseif ($percentage <= 30) return 65;
            else return max(30, 65 - ($percentage - 30));
        }
    }
    
    /**
     * Calculate job type matching score
     */
    private function calculateJobTypeMatch($userId, $jobPosting) {
        $preferences = $this->getUserPreferences($userId);
        $jobType = $jobPosting['job_type'];
        
        if (!$preferences || empty($preferences['preferred_job_types'])) {
            return 80; // Default score if no job type preferences
        }
        
        $preferredJobTypes = json_decode($preferences['preferred_job_types'], true);
        
        foreach ($preferredJobTypes as $preferredType) {
            if (strtolower($preferredType) === strtolower($jobType)) {
                return 100;
            }
        }
        
        return 50; // No job type match
    }
    
    /**
     * Get recommended jobs for a user
     */
    public function getRecommendedJobs($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT jp.*, 
                   COALESCE(jae.compatibility_score, 0) as compatibility_score,
                   jae.applied_date,
                   CASE WHEN jae.id IS NOT NULL THEN 1 ELSE 0 END as already_applied
            FROM job_postings jp
            LEFT JOIN job_applications_extended jae ON jp.id = jae.job_posting_id AND jae.jobseeker_id = (
                SELECT id FROM jobseeker WHERE user_id = ?
            )
            WHERE jp.status = 'Active'
            ORDER BY compatibility_score DESC, jp.created_at DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $jobs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Calculate compatibility scores for jobs without scores
        foreach ($jobs as &$job) {
            if ($job['compatibility_score'] == 0) {
                $job['compatibility_score'] = $this->calculateCompatibilityScore($userId, $job['id']);
                
                // Store the calculated score
                $this->storeCompatibilityScore($userId, $job['id'], $job['compatibility_score']);
            }
        }
        
        // Sort by compatibility score again after calculation
        usort($jobs, function($a, $b) {
            return $b['compatibility_score'] <=> $a['compatibility_score'];
        });
        
        return $jobs;
    }
    
    /**
     * Update compatibility scores for all active jobs for a user
     */
    public function updateAllCompatibilityScores($userId) {
        $stmt = $this->conn->prepare("SELECT id FROM job_postings WHERE status = 'Active'");
        $stmt->execute();
        $result = $stmt->get_result();
        $jobPostings = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($jobPostings as $job) {
            $score = $this->calculateCompatibilityScore($userId, $job['id']);
            $this->storeCompatibilityScore($userId, $job['id'], $score);
        }
    }
    
    // Helper methods
    
    private function getJobSeekerData($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    private function getJobPostingData($jobPostingId) {
        $stmt = $this->conn->prepare("SELECT * FROM job_postings WHERE id = ?");
        $stmt->bind_param("i", $jobPostingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    private function getUserPreferences($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    private function extractExperienceRequirement($requirements) {
        // Look for patterns like "2+ years", "3-5 years", "minimum 2 years"
        if (preg_match('/(\d+)\+?\s*years?/', $requirements, $matches)) {
            return (int)$matches[1];
        }
        if (preg_match('/minimum\s+(\d+)\s*years?/', $requirements, $matches)) {
            return (int)$matches[1];
        }
        if (preg_match('/(\d+)-(\d+)\s*years?/', $requirements, $matches)) {
            return (int)$matches[1]; // Return minimum
        }
        return null;
    }
    
    private function parseSalaryRange($salaryRange) {
        // Parse formats like "25000-35000", "25,000 - 35,000", "25k-35k"
        $salaryRange = str_replace(',', '', $salaryRange);
        $salaryRange = str_replace('k', '000', $salaryRange);
        
        if (preg_match('/(\d+)-(\d+)/', $salaryRange, $matches)) {
            return [
                'min' => (float)$matches[1],
                'max' => (float)$matches[2]
            ];
        }
        
        return null;
    }
    
    private function storeCompatibilityScore($userId, $jobPostingId, $score) {
        // Get jobseeker ID
        $stmt = $this->conn->prepare("SELECT id FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $jobseeker = $result->fetch_assoc();
        $stmt->close();
        
        if (!$jobseeker) return;
        
        $jobseekerId = $jobseeker['id'];
        
        // Insert or update compatibility score
        $stmt = $this->conn->prepare("
            INSERT INTO job_applications_extended (jobseeker_id, job_posting_id, compatibility_score) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE compatibility_score = VALUES(compatibility_score)
        ");
        $stmt->bind_param("iid", $jobseekerId, $jobPostingId, $score);
        $stmt->execute();
        $stmt->close();
    }
}

// Usage example and API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    require_once 'db.php';
    $matching = new JobMatchingAlgorithm($conn);
    
    switch ($_GET['action']) {
        case 'get_recommendations':
            if (isset($_GET['user_id'])) {
                $userId = (int)$_GET['user_id'];
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                
                $recommendations = $matching->getRecommendedJobs($userId, $limit);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'recommendations' => $recommendations
                ]);
            }
            break;
            
        case 'calculate_score':
            if (isset($_GET['user_id']) && isset($_GET['job_id'])) {
                $userId = (int)$_GET['user_id'];
                $jobId = (int)$_GET['job_id'];
                
                $score = $matching->calculateCompatibilityScore($userId, $jobId);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'score' => $score
                ]);
            }
            break;
            
        case 'update_scores':
            if (isset($_GET['user_id'])) {
                $userId = (int)$_GET['user_id'];
                $matching->updateAllCompatibilityScores($userId);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Compatibility scores updated successfully'
                ]);
            }
            break;
    }
}
?>
