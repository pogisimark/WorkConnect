<?php
// Suppress warnings and errors for PDF generation
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

// Start output buffering to catch any accidental output
ob_start();

// Check session manually to avoid header conflicts
session_start();
if (!isset($_SESSION['username'])) {
    ob_end_clean();
    header('Location: login.html');
    exit;
}

// Clear any output
ob_end_clean();

require_once 'db.php';
require_once '../vendor/autoload.php';

$job_postings_company_sql = '';
$colCheck = @$conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
if ($colCheck && $colCheck->num_rows > 0) {
    $job_postings_company_sql = ' AND company_id IS NOT NULL';
}

use TCPDF as TCPDF;

// Helper function to calculate MultiCell height without drawing
function calculateMultiCellHeight($pdf, $width, $text, $lineHeight) {
    $lines = explode("\n", $text);
    $totalHeight = 0;
    foreach ($lines as $line) {
        $lineWidth = $pdf->GetStringWidth($line);
        $lineCount = max(1, ceil($lineWidth / ($width - 2)));
        $totalHeight += $lineCount * $lineHeight;
    }
    return $totalHeight;
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$industry = $_GET['industry'] ?? '';
$search = $_GET['search'] ?? '';

// Build query to get job postings
$query = "SELECT id, title, company, location, job_type, salary_range, status, industry, description, created_at 
          FROM job_postings 
          WHERE 1=1" . $job_postings_company_sql;
$params = [];
$types = '';

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($type)) {
    $query .= " AND job_type = ?";
    $params[] = $type;
    $types .= 's';
}

if (!empty($industry)) {
    $query .= " AND industry LIKE ?";
    $params[] = "%$industry%";
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR company LIKE ? OR location LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('WorkConnect');
$pdf->SetAuthor('WorkConnect System');
$pdf->SetTitle('Job Postings Report');
$pdf->SetSubject('Job Postings Export');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(35, 58, 139); // #233a8b

// Title
$pdf->Cell(0, 10, 'Job Postings Report', 0, 1, 'C');
$pdf->Ln(5);

// Report info
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'Generated on: ' . date('F d, Y h:i A'), 0, 1, 'C');
$pdf->Cell(0, 6, 'Total Jobs: ' . count($jobs), 0, 1, 'C');
$pdf->Ln(5);

// Table header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(35, 58, 139);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(200, 200, 200);

// Table headers - adjust widths to fit page (210mm - 30mm margins = 180mm available)
$header = ['Title', 'Company', 'Location', 'Type', 'Salary', 'Status'];
$w = [55, 25, 35, 20, 25, 20]; // Column widths in mm (total = 180mm)

// Header row
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Table data
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(245, 245, 245);

$fill = false;
$fixedRowHeight = 10; // Fixed height for all rows

foreach ($jobs as $index => $job) {
    // Check if we need a new page
    if ($pdf->GetY() + $fixedRowHeight > 270) {
        $pdf->AddPage();
        // Redraw header on new page
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(35, 58, 139);
        $pdf->SetTextColor(255, 255, 255);
        for ($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(0, 0, 0);
    }
    
    // Get starting position
    $startX = $pdf->GetX();
    $startY = $pdf->GetY();
    
    // Prepare data - truncate to fit cells
    $title = trim($job['title']);
    $company = trim($job['company'] ?? 'N/A');
    $location = trim($job['location']);
    $jobType = trim($job['job_type']);
    
    // Format salary with peso sign and commas
    if (!empty($job['salary_range'])) {
        $salaryRange = trim($job['salary_range']);
        // Remove any existing peso sign or question mark
        $salaryRange = str_replace(['₱', '?', 'P', 'p', 'PHP', 'php'], '', $salaryRange);
        $salaryRange = trim($salaryRange);
        
        // Check if it's a range (contains dash or hyphen)
        if (preg_match('/(\d+)\s*[-–—]\s*(\d+)/', $salaryRange, $matches)) {
            // Format both numbers with commas
            $min = number_format((int)$matches[1], 0, '.', ',');
            $max = number_format((int)$matches[2], 0, '.', ',');
            // Use peso sign (₱) - UTF-8 character
            $salary = '₱' . $min . '-' . $max;
        } else {
            // Single number, format with commas
            $salary = '₱' . number_format((int)$salaryRange, 0, '.', ',');
        }
    } else {
        $salary = 'N/A';
    }
    
    $status = strtoupper(trim($job['status']));
    
    // Build title with industry
    $titleText = $title;
    if (!empty($job['industry'])) {
        $titleText .= "\n(" . trim($job['industry']) . ")";
    }
    
    // Calculate height needed for each cell
    $lineHeight = 4;
    $titleHeight = calculateMultiCellHeight($pdf, $w[0], $titleText, $lineHeight);
    $companyHeight = calculateMultiCellHeight($pdf, $w[1], $company, $lineHeight);
    $locationHeight = calculateMultiCellHeight($pdf, $w[2], $location, $lineHeight);
    $typeHeight = calculateMultiCellHeight($pdf, $w[3], $jobType, $lineHeight);
    $salaryHeight = calculateMultiCellHeight($pdf, $w[4], $salary, $lineHeight);
    $statusHeight = $lineHeight;
    
    // Get maximum height for this row
    $rowHeight = max($titleHeight, $companyHeight, $locationHeight, $typeHeight, $salaryHeight, $statusHeight, 8);
    
    // Draw cell borders first
    $xPos = $startX;
    for ($i = 0; $i < count($w); $i++) {
        $pdf->Rect($xPos, $startY, $w[$i], $rowHeight);
        if ($fill) {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Rect($xPos, $startY, $w[$i], $rowHeight, 'F');
        }
        $xPos += $w[$i];
    }
    
    // Now write text in each cell - save Y position before each MultiCell
    // Title cell
    $pdf->SetXY($startX + 1, $startY + 1);
    $savedY = $pdf->GetY();
    $pdf->MultiCell($w[0] - 2, $lineHeight, $titleText, 0, 'L', false, 0);
    $pdf->SetY($savedY);
    
    // Company cell
    $pdf->SetXY($startX + $w[0] + 1, $startY + 1);
    $savedY = $pdf->GetY();
    $pdf->MultiCell($w[1] - 2, $lineHeight, $company, 0, 'L', false, 0);
    $pdf->SetY($savedY);
    
    // Location cell
    $pdf->SetXY($startX + $w[0] + $w[1] + 1, $startY + 1);
    $savedY = $pdf->GetY();
    $pdf->MultiCell($w[2] - 2, $lineHeight, $location, 0, 'L', false, 0);
    $pdf->SetY($savedY);
    
    // Type cell
    $pdf->SetXY($startX + $w[0] + $w[1] + $w[2] + 1, $startY + 1);
    $savedY = $pdf->GetY();
    $pdf->MultiCell($w[3] - 2, $lineHeight, $jobType, 0, 'C', false, 0);
    $pdf->SetY($savedY);
    
    // Salary cell - use dejavusans font for better UTF-8 support
    $pdf->SetXY($startX + $w[0] + $w[1] + $w[2] + $w[3] + 1, $startY + 1);
    $savedY = $pdf->GetY();
    $pdf->SetFont('dejavusans', '', 7); // Use dejavusans for peso sign support
    $pdf->MultiCell($w[4] - 2, $lineHeight, $salary, 0, 'R', false, 0);
    $pdf->SetY($savedY);
    $pdf->SetFont('helvetica', '', 7); // Reset to helvetica
    
    // Status cell
    $pdf->SetXY($startX + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + 1, $startY + 1);
    $savedY = $pdf->GetY();
    $pdf->SetFont('helvetica', 'B', 7);
    $statusColor = ($job['status'] === 'Active') ? [76, 175, 80] : [158, 158, 158];
    $pdf->SetTextColor($statusColor[0], $statusColor[1], $statusColor[2]);
    $pdf->MultiCell($w[5] - 2, $lineHeight, $status, 0, 'C', false, 0);
    $pdf->SetY($savedY);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 7);
    
    // Move to next row
    $pdf->SetXY($startX, $startY + $rowHeight);
    
    $fill = !$fill;
}

// Footer
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'C');

// Generate filename
$filename = 'job_postings_' . date('Y-m-d') . '.pdf';

// Clean any output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Output PDF
$pdf->Output($filename, 'D'); // 'D' for download

$stmt->close();
$conn->close();
exit; // Ensure no further output
?>
