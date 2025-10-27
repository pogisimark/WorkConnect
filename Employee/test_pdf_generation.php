<?php
// Test PDF Generation - Debug Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PDF Generation Test ===\n";

// Test 1: Check if TCPDF is available
echo "1. Testing TCPDF availability...\n";
try {
    require_once '../vendor/autoload.php';
    echo "   ✓ Vendor autoload loaded\n";
    
    if (class_exists('TCPDF')) {
        echo "   ✓ TCPDF class exists\n";
    } else {
        echo "   ✗ TCPDF class NOT found\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ✗ Error loading TCPDF: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Define constants
echo "2. Testing TCPDF constants...\n";
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
    echo "   ✓ PDF_PAGE_ORIENTATION defined\n";
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
    echo "   ✓ PDF_UNIT defined\n";
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
    echo "   ✓ PDF_PAGE_FORMAT defined\n";
}

// Test 3: Create TCPDF object
echo "3. Testing TCPDF object creation...\n";
try {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    echo "   ✓ TCPDF object created successfully\n";
} catch (Exception $e) {
    echo "   ✗ TCPDF creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Basic PDF operations
echo "4. Testing basic PDF operations...\n";
try {
    $pdf->SetCreator('Test');
    $pdf->SetAuthor('Test Author');
    $pdf->SetTitle('Test PDF');
    echo "   ✓ PDF metadata set\n";
    
    $pdf->SetMargins(15, 15, 15);
    echo "   ✓ PDF margins set\n";
    
    $pdf->AddPage();
    echo "   ✓ PDF page added\n";
    
    $pdf->writeHTML('<h1>Test PDF</h1><p>This is a test PDF generation.</p>', true, false, true, false, '');
    echo "   ✓ HTML written to PDF\n";
    
} catch (Exception $e) {
    echo "   ✗ PDF operations failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Generate PDF content
echo "5. Testing PDF output generation...\n";
try {
    $pdfContent = $pdf->Output('test.pdf', 'S');
    echo "   ✓ PDF content generated (size: " . strlen($pdfContent) . " bytes)\n";
} catch (Exception $e) {
    echo "   ✗ PDF output failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== All tests passed! PDF generation should work. ===\n";
echo "If this test passes but the actual PDF generation fails,\n";
echo "the issue is likely in the database queries or HTML generation.\n";
?>
