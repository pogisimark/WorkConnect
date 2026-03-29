<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');
include 'session_protect.php';
require_once __DIR__ . '/follow_up_pending_badge.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';
require_once __DIR__ . '/jobseeker_pending_badge.php';
require_once __DIR__ . '/db.php';
$follow_up_pending_count = fu_get_pending_follow_up_count($conn);
$acfu_unread_count = acfu_get_unread_response_count($conn);
$pending_jobseekers_count = js_get_pending_jobseekers_count($conn);

$skill_registry_counts = [];
if ($conn) {
    $cntRes = @$conn->query("SELECT barangay, COUNT(*) AS c FROM skill_registry GROUP BY barangay");
    if ($cntRes) {
        while ($cr = $cntRes->fetch_assoc()) {
            $key = trim((string) ($cr['barangay'] ?? ''));
            if ($key !== '') {
                $skill_registry_counts[$key] = (int) $cr['c'];
            }
        }
    }
}

$skill_registry_barangays = [
    ['Bangkal', 'bangkal logo.png'],
    ['Baraka', 'baraka logo.png'],
    ['Bigte', 'bigte logo.png'],
    ['Bitungol', 'bitungol logo.png'],
    ['Friendship Village Resources (FVR)', 'fvr logo.png'],
    ['Matictic', 'matictic logo.png'],
    ['Minuyan', 'minuyan logo.png'],
    ['Partida', 'partida logo.png'],
    ['Pinagtulayan', 'pinagtulayan logo.png'],
    ['Poblacion', 'poblacion logo.png'],
    ['San Lorenzo', 'san lorenzo logo.png'],
    ['San Mateo', 'san mateo logo.png'],
    ['Tigbe', 'tigbe logo.png'],
];

if ($conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkConnect Skill Registry</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .add-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        inset:0;width:100%;height:100%;min-height:100vh;min-height:100dvh;max-height:100dvh;box-sizing:border-box;
        background: rgba(0,0,0,0.18);
        justify-content: center;
        align-items: center;
    }
    .add-modal-content {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px 28px 20px 28px;
        max-width: 1000px;
        width: 90vw;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 8px 40px rgba(35,58,139,0.15);
        border: 1px solid #bbdefb;
        position: relative;
    }
    .add-modal-close {
        position: absolute;
        top: 20px;
        left: 20px;
        background: #90caf9;
        color: #1565c0;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
        cursor: pointer;
        font-weight: bold;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(35,58,139,0.2);
    }
    .add-modal-close:hover {
        background: #bbdefb;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(35,58,139,0.3);
    }
    .add-modal-form label {
        font-weight: bold;
        margin-bottom: 2px;
        display: block;
        font-size: 1rem;
    }
    .add-modal-form input[type="text"],
    .add-modal-form input[type="date"],
    .add-modal-form input[type="number"],
    .add-modal-form select {
        width: 95%;
        max-width: 300px;
        padding: 8px 12px;
        border-radius: 8px;
        border: 2px solid #e3f2fd;
        margin-bottom: 12px;
        font-size: 0.9rem;
        background: #fff;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(35,58,139,0.05);
    }
    .add-modal-form input[type="text"]:focus,
    .add-modal-form input[type="date"]:focus,
    .add-modal-form input[type="number"]:focus,
    .add-modal-form select:focus {
        border-color: #90caf9;
        background: rgba(227,242,253,0.3);
        box-shadow: 0 0 0 3px rgba(227,242,253,0.5);
        outline: none;
    }
    .add-modal-form .row {
        display: flex;
        gap: 16px;
        margin-bottom: 8px;
    }
    .add-modal-form .row > div {
        flex: 1;
    }
    .add-modal-form .section-title {
        font-weight: bold;
        font-size: 1rem;
        margin: 12px 0 6px 0;
    }
    .add-modal-form .radio-group {
        display: flex;
        gap: 12px;
        margin-bottom: 8px;
        align-items: center;
    }
    .add-modal-form .radio-group label {
        font-weight: normal;
        margin-bottom: 0;
        font-size: 1rem;
    }
    .add-modal-form .legend {
        font-size: 0.9rem;
        color: #333;
        margin-top: 12px;
    }
    
    /* Form Section Styling */
    .form-section {
        margin-bottom: 32px;
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e3f2fd;
        box-shadow: 0 2px 8px rgba(35,58,139,0.05);
    }
    
    .section-header {
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #90caf9;
    }
    
    .section-header h3 {
        margin: 0;
        color: #233a8b;
        font-size: 1.1rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-section .row {
        margin-bottom: 16px;
    }
    
    .form-section .row:last-child {
        margin-bottom: 0;
    }
    
    /* Specific field max-widths to reduce crowding */
    .add-modal-form input[name="city"],
    .add-modal-form input[name="barangay"] {
        max-width: 200px;
    }
    
    .add-modal-form input[name="printed_name"] {
        max-width: 400px;
    }
    
    .add-modal-form input[name="address"] {
        max-width: 350px;
    }
    
    .add-modal-form input[name="education"] {
        max-width: 400px;
    }
    
    .add-modal-form select[name="education"] {
        max-width: 400px;
    }
    
    .add-modal-form input[name="we_position"],
    .add-modal-form input[name="se_business"] {
        max-width: 250px;
    }
    
    .add-modal-form input[name="skills"] {
        max-width: 400px;
    }
    
    .add-modal-form input[name="contact"] {
        max-width: 180px;
    }
    
    .add-modal-form input[name="age"] {
        max-width: 80px;
    }
    
    .add-modal-form input[name="sex"] {
        max-width: 60px;
    }
    
    .add-modal-form input[name="marital"] {
        max-width: 120px;
    }
    
    .add-modal-form input[name="we_months"],
    .add-modal-form input[name="se_months"] {
        max-width: 100px;
    }
    
    .add-modal-form select[name="we_months"],
    .add-modal-form select[name="se_months"] {
        max-width: 200px;
    }
    .add-modal-form .submit-btn {
        background: #90caf9;
        color: #1565c0;
        border: none;
        border-radius: 12px;
        padding: 14px 40px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: 24px;
        float: right;
        box-shadow: 0 4px 12px rgba(35,58,139,0.2);
        transition: all 0.2s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .add-modal-form .submit-btn:hover {
        background: #bbdefb;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(35,58,139,0.3);
    }
    .modal-actions {
        position: absolute;
        top: 16px;
        right: 24px;
        display: flex;
        gap: 12px;
        z-index: 20;
    }
    .modal-actions button {
        background: #90caf9;
        color: #1565c0;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(35,58,139,0.2);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .modal-actions button:hover {
        background: #bbdefb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(35,58,139,0.3);
    }
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        inset:0;width:100%;height:100%;min-height:100vh;min-height:100dvh;max-height:100dvh;box-sizing:border-box;
        overflow: auto;
        background: rgba(0,0,0,0.18);
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background: #ffffff;
        border-radius: 20px;
        padding: 32px 28px 28px 28px;
        max-width: 98vw;
        width: 95vw;
        max-height: 90vh;
        overflow: auto;
        box-shadow: 0 8px 40px rgba(35,58,139,0.15);
        border: 1px solid #bbdefb;
        position: relative;
    }
    .modal-close {
        position: absolute;
        top: 20px;
        left: 20px;
        background: #90caf9;
        color: #1565c0;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
        cursor: pointer;
        font-weight: bold;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(35,58,139,0.2);
    }
    .modal-close:hover {
        background: #bbdefb;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(35,58,139,0.3);
    }
    .barangay-title-modal {
        color: #1565c0;
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 20px;
        margin-left: 32px;
        margin-top: 60px;
        text-align: center;
        background: #e3f2fd;
        padding: 16px 24px;
        border-radius: 12px;
        border-left: 4px solid #90caf9;
        box-shadow: 0 2px 8px rgba(35,58,139,0.1);
    }
    .barangay-table-form {
        border-collapse: collapse;
        width: 100%;
        min-width: 5000px;
        font-size: 0.85rem;
        background: #fff;
        table-layout: auto;
        border: 2px solid #e3f2fd;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(35,58,139,0.08);
    }
    
    /* Mobile table improvements */
    @media (max-width: 768px) {
        .barangay-table-form {
            min-width: 4000px;
            font-size: 0.75rem;
        }
        
        .barangay-table-form th,
        .barangay-table-form td {
            padding: 6px 4px;
            font-size: 0.75rem;
            min-width: 80px;
        }
        
        .barangay-table-form th {
            font-size: 0.7rem;
            line-height: 1.2;
            padding: 8px 4px;
        }
        
        .barangay-table-form input,
        .barangay-table-form select {
            font-size: 0.7rem;
            padding: 2px 4px;
        }
    }
    
    @media (max-width: 480px) {
        .barangay-table-form {
            min-width: 3500px;
            font-size: 0.65rem;
        }
        
        .barangay-table-form th,
        .barangay-table-form td {
            padding: 4px 2px;
            font-size: 0.65rem;
            min-width: 70px;
        }
        
        .barangay-table-form th {
            font-size: 0.6rem;
            padding: 6px 2px;
        }
        
        .barangay-table-form input,
        .barangay-table-form select {
            font-size: 0.65rem;
            padding: 1px 2px;
        }
    }
    .barangay-table-form td {
        min-width: 120px;
        border: 1px solid #e3f2fd;
        padding: 10px 8px;
        text-align: center;
        vertical-align: middle;
        word-break: break-word;
        background: #fff;
        transition: background 0.2s ease;
        white-space: normal;
        overflow: visible;
        width: auto;
    }
    .barangay-table-form tr:hover td {
        background: rgba(35,58,139,0.02);
    }
    .barangay-table-form th {
        white-space: normal;
        background: #e3f2fd;
        color: #233a8b;
        font-size: 0.8rem;
        font-weight: 700;
        border: 1px solid #bbdefb;
        text-align: center;
        vertical-align: middle;
        padding: 12px 8px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        min-width: 120px;
        overflow: visible;
        width: auto;
        line-height: 1.3;
    }
    .barangay-table-form input, .barangay-table-form select {
        min-width: 0;
        width: 100%;
        border: none;
        background: transparent;
        font-size: 0.85rem;
        text-align: center;
        outline: none;
        overflow: visible;
        white-space: nowrap;
        box-sizing: border-box;
        border-radius: 0;
        padding: 4px 6px;
        transition: all 0.2s ease;
    }
    .barangay-table-form input:focus {
        border-color: transparent;
        background: rgba(227,242,253,0.3);
        box-shadow: none;
    }
    .barangay-table-form input[readonly] {
        background: transparent;
        border-color: transparent;
        color: #333;
    }
    .barangay-table-form input[disabled] {
        background: transparent;
        border-color: transparent;
        cursor: not-allowed;
    }
    
    /* Hide placeholder text for date inputs to make table cleaner */
    .barangay-table-form input[type="date"]::-webkit-input-placeholder {
        color: transparent;
    }
    .barangay-table-form input[type="date"]::-moz-placeholder {
        color: transparent;
    }
    .barangay-table-form input[type="date"]:-ms-input-placeholder {
        color: transparent;
    }
    .barangay-table-form input[type="date"]::placeholder {
        color: transparent;
    }
    
    /* Additional styling to make empty date inputs look cleaner */
    .barangay-table-form input[type="date"]:invalid {
        color: transparent;
    }
    .barangay-table-form input[type="date"]:invalid::-webkit-datetime-edit {
        color: transparent;
    }
    .barangay-table-form input[type="date"]:invalid::-webkit-datetime-edit-fields-wrapper {
        color: transparent;
    }
    
    /* Clean date input styling - hide all placeholder text */
    .barangay-table-form .clean-date-input::-webkit-datetime-edit-text {
        color: transparent;
    }
    .barangay-table-form .clean-date-input::-webkit-datetime-edit-month-field {
        color: transparent;
    }
    .barangay-table-form .clean-date-input::-webkit-datetime-edit-day-field {
        color: transparent;
    }
    .barangay-table-form .clean-date-input::-webkit-datetime-edit-year-field {
        color: transparent;
    }
    .barangay-table-form .clean-date-input:invalid::-webkit-datetime-edit-text {
        color: transparent;
    }
    .barangay-table-form .clean-date-input:invalid::-webkit-datetime-edit-month-field {
        color: transparent;
    }
    .barangay-table-form .clean-date-input:invalid::-webkit-datetime-edit-day-field {
        color: transparent;
    }
    .barangay-table-form .clean-date-input:invalid::-webkit-datetime-edit-year-field {
        color: transparent;
    }
    
    /* For Firefox */
    .barangay-table-form .clean-date-input:invalid {
        color: transparent;
    }
    
    /* For Edge/IE */
    .barangay-table-form .clean-date-input::-ms-input-placeholder {
        color: transparent;
    }
    
    /* Specific column width adjustments */
    .barangay-table-form th:nth-child(1), .barangay-table-form td:nth-child(1) { min-width: 60px; } /* No. */
    .barangay-table-form th:nth-child(2), .barangay-table-form td:nth-child(2) { min-width: 120px; } /* Date of Survey */
    .barangay-table-form th:nth-child(3), .barangay-table-form td:nth-child(3) { min-width: 200px; } /* Printed Name */
    .barangay-table-form th:nth-child(4), .barangay-table-form td:nth-child(4) { min-width: 80px; } /* FTJS Yes */
    .barangay-table-form th:nth-child(5), .barangay-table-form td:nth-child(5) { min-width: 80px; } /* FTJS No */
    .barangay-table-form th:nth-child(6), .barangay-table-form td:nth-child(6) { min-width: 80px; } /* COVID Yes */
    .barangay-table-form th:nth-child(7), .barangay-table-form td:nth-child(7) { min-width: 80px; } /* COVID No */
    .barangay-table-form th:nth-child(8), .barangay-table-form td:nth-child(8) { min-width: 180px; } /* Address */
    .barangay-table-form th:nth-child(9), .barangay-table-form td:nth-child(9) { min-width: 120px; } /* DOB */
    .barangay-table-form th:nth-child(10), .barangay-table-form td:nth-child(10) { min-width: 120px; } /* Contact */
    .barangay-table-form th:nth-child(11), .barangay-table-form td:nth-child(11) { min-width: 60px; } /* Age */
    .barangay-table-form th:nth-child(12), .barangay-table-form td:nth-child(12) { min-width: 80px; } /* Sex */
    .barangay-table-form th:nth-child(13), .barangay-table-form td:nth-child(13) { min-width: 120px; } /* Marital Status */
    .barangay-table-form th:nth-child(14), .barangay-table-form td:nth-child(14) { min-width: 200px; } /* Education */
    .barangay-table-form th:nth-child(15), .barangay-table-form td:nth-child(15) { min-width: 150px; } /* WE Position */
    .barangay-table-form th:nth-child(16), .barangay-table-form td:nth-child(16) { min-width: 120px; } /* WE Duration */
    .barangay-table-form th:nth-child(17), .barangay-table-form td:nth-child(17) { min-width: 150px; } /* SE Business */
    .barangay-table-form th:nth-child(18), .barangay-table-form td:nth-child(18) { min-width: 120px; } /* SE Duration */
    .barangay-table-form th:nth-child(19), .barangay-table-form td:nth-child(19) { min-width: 80px; } /* UE */
    .barangay-table-form th:nth-child(20), .barangay-table-form td:nth-child(20) { min-width: 200px; } /* Skills */
    
    /* Mobile column width optimizations */
    @media (max-width: 768px) {
        .barangay-table-form th:nth-child(1), .barangay-table-form td:nth-child(1) { min-width: 40px; } /* No. */
        .barangay-table-form th:nth-child(2), .barangay-table-form td:nth-child(2) { min-width: 100px; } /* Date of Survey */
        .barangay-table-form th:nth-child(3), .barangay-table-form td:nth-child(3) { min-width: 150px; } /* Printed Name */
        .barangay-table-form th:nth-child(4), .barangay-table-form td:nth-child(4) { min-width: 60px; } /* FTJS Yes */
        .barangay-table-form th:nth-child(5), .barangay-table-form td:nth-child(5) { min-width: 60px; } /* FTJS No */
        .barangay-table-form th:nth-child(6), .barangay-table-form td:nth-child(6) { min-width: 60px; } /* COVID Yes */
        .barangay-table-form th:nth-child(7), .barangay-table-form td:nth-child(7) { min-width: 60px; } /* COVID No */
        .barangay-table-form th:nth-child(8), .barangay-table-form td:nth-child(8) { min-width: 140px; } /* Address */
        .barangay-table-form th:nth-child(9), .barangay-table-form td:nth-child(9) { min-width: 100px; } /* DOB */
        .barangay-table-form th:nth-child(10), .barangay-table-form td:nth-child(10) { min-width: 100px; } /* Contact */
        .barangay-table-form th:nth-child(11), .barangay-table-form td:nth-child(11) { min-width: 40px; } /* Age */
        .barangay-table-form th:nth-child(12), .barangay-table-form td:nth-child(12) { min-width: 60px; } /* Sex */
        .barangay-table-form th:nth-child(13), .barangay-table-form td:nth-child(13) { min-width: 100px; } /* Marital Status */
        .barangay-table-form th:nth-child(14), .barangay-table-form td:nth-child(14) { min-width: 150px; } /* Education */
        .barangay-table-form th:nth-child(15), .barangay-table-form td:nth-child(15) { min-width: 120px; } /* WE Position */
        .barangay-table-form th:nth-child(16), .barangay-table-form td:nth-child(16) { min-width: 100px; } /* WE Duration */
        .barangay-table-form th:nth-child(17), .barangay-table-form td:nth-child(17) { min-width: 120px; } /* SE Business */
        .barangay-table-form th:nth-child(18), .barangay-table-form td:nth-child(18) { min-width: 100px; } /* SE Duration */
        .barangay-table-form th:nth-child(19), .barangay-table-form td:nth-child(19) { min-width: 60px; } /* UE */
        .barangay-table-form th:nth-child(20), .barangay-table-form td:nth-child(20) { min-width: 150px; } /* Skills */
    }
    
    @media (max-width: 480px) {
        .barangay-table-form th:nth-child(1), .barangay-table-form td:nth-child(1) { min-width: 35px; } /* No. */
        .barangay-table-form th:nth-child(2), .barangay-table-form td:nth-child(2) { min-width: 80px; } /* Date of Survey */
        .barangay-table-form th:nth-child(3), .barangay-table-form td:nth-child(3) { min-width: 120px; } /* Printed Name */
        .barangay-table-form th:nth-child(4), .barangay-table-form td:nth-child(4) { min-width: 50px; } /* FTJS Yes */
        .barangay-table-form th:nth-child(5), .barangay-table-form td:nth-child(5) { min-width: 50px; } /* FTJS No */
        .barangay-table-form th:nth-child(6), .barangay-table-form td:nth-child(6) { min-width: 50px; } /* COVID Yes */
        .barangay-table-form th:nth-child(7), .barangay-table-form td:nth-child(7) { min-width: 50px; } /* COVID No */
        .barangay-table-form th:nth-child(8), .barangay-table-form td:nth-child(8) { min-width: 120px; } /* Address */
        .barangay-table-form th:nth-child(9), .barangay-table-form td:nth-child(9) { min-width: 80px; } /* DOB */
        .barangay-table-form th:nth-child(10), .barangay-table-form td:nth-child(10) { min-width: 80px; } /* Contact */
        .barangay-table-form th:nth-child(11), .barangay-table-form td:nth-child(11) { min-width: 35px; } /* Age */
        .barangay-table-form th:nth-child(12), .barangay-table-form td:nth-child(12) { min-width: 50px; } /* Sex */
        .barangay-table-form th:nth-child(13), .barangay-table-form td:nth-child(13) { min-width: 80px; } /* Marital Status */
        .barangay-table-form th:nth-child(14), .barangay-table-form td:nth-child(14) { min-width: 120px; } /* Education */
        .barangay-table-form th:nth-child(15), .barangay-table-form td:nth-child(15) { min-width: 100px; } /* WE Position */
        .barangay-table-form th:nth-child(16), .barangay-table-form td:nth-child(16) { min-width: 80px; } /* WE Duration */
        .barangay-table-form th:nth-child(17), .barangay-table-form td:nth-child(17) { min-width: 100px; } /* SE Business */
        .barangay-table-form th:nth-child(18), .barangay-table-form td:nth-child(18) { min-width: 80px; } /* SE Duration */
        .barangay-table-form th:nth-child(19), .barangay-table-form td:nth-child(19) { min-width: 50px; } /* UE */
        .barangay-table-form th:nth-child(20), .barangay-table-form td:nth-child(20) { min-width: 120px; } /* Skills */
    }
    /* Filter UI Design */
    .filter-container {
        background: linear-gradient(135deg, #e3f2fd, #f0f4ff);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid #bbdefb;
        box-shadow: 0 2px 8px rgba(35,58,139,0.1);
        margin-top: 50px;
    }
    .filter-row {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filter-label {
        font-weight: 600;
        color: #233a8b;
        font-size: 0.9rem;
        min-width: 60px;
    }
    .filter-select {
        padding: 8px 12px;
        border: 2px solid #bbdefb;
        border-radius: 8px;
        background: #fff;
        color: #233a8b;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 120px;
    }
    .filter-select:focus {
        outline: none;
        border-color: #90caf9;
        box-shadow: 0 0 0 3px rgba(144,202,249,0.2);
    }
    .filter-select:hover {
        border-color: #90caf9;
        background: rgba(144,202,249,0.05);
    }
.barangay-table-scroll {
    overflow-x: auto !important;
    width: 100%;
    padding-bottom: 8px;
    max-width: 100vw;
    white-space: nowrap;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(35,58,139,0.1);
    /* Enhanced mobile scrolling */
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

/* Mobile table scroll improvements */
@media (max-width: 768px) {
    .barangay-table-scroll {
        padding-bottom: 12px;
        margin: 0 -10px;
        padding-left: 10px;
        padding-right: 10px;
    }
    
    /* Add scroll indicators for mobile */
    .barangay-table-scroll::after {
        content: '← Swipe to see more columns →';
        display: block;
        text-align: center;
        font-size: 0.8rem;
        color: #666;
        margin-top: 8px;
        font-style: italic;
    }
}

@media (max-width: 480px) {
    .barangay-table-scroll {
        margin: 0 -5px;
        padding-left: 5px;
        padding-right: 5px;
    }
    
    .barangay-table-scroll::after {
        font-size: 0.7rem;
        margin-top: 6px;
    }
}
    @media (max-width: 900px) {
        .modal-content {
            padding: 12px 2vw 12px 2vw;
        }
        .barangay-title-modal {
            margin-left: 0;
        }
    }
    body {
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        background: #fafafa;
        min-height: 100vh; min-height: 100dvh;
        overflow-x: hidden;
        overflow-y: auto;
    }
    .header {
        background: #233a8b;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        height: 64px;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        max-width: 100vw;
        z-index: 1000;
        box-shadow: 0 2px 8px rgba(35,58,139,0.10);
        box-sizing: border-box;
    }
    .header img {
        height: 48px;
        margin-right: 16px;
        border-radius: 50%;
        background: none;
        border: none;
    }
    .header-title {
        font-size: 1.7rem;
        font-weight: bold;
        letter-spacing: 0.5px;
    }
    .layout {
    display: flex;
    min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
    padding-top: 64px; /* offset for fixed header */
}
    .sidebar {
    background: #e3eaff;
    width: 240px;
    height: calc(100vh - 64px); height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px)); max-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
    position: fixed;
    top: 64px;
    left: 0;
    z-index: 999;
    display: flex;
    flex-direction: column;
    padding: 32px 0 0 24px;
    box-sizing: border-box;
    overflow-y: auto;
}
    .sidebar a {
        font-weight: bold;
        color: #222;
        text-decoration: none;
        margin-bottom: 16px;
        font-size: 1rem;
        letter-spacing: 0.3px;
        transition: all 0.2s;
        padding: 12px 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 10%;
    }
    .sidebar a:hover {
        color: #233a8b;
        background: #d1dbfa;
    }
    .sidebar .logout {
        margin-top: auto;
        margin-bottom: 32px;
        color: #222;
        font-weight: bold;
        display: block;
        width: 90%;
        text-align: left;
    }
    .sidebar a:hover {
        color: #233a8b;
        background: #d1dbfa; 
        border-radius: 8px;   
        padding-left: 10px;   
    }
    .sidebar a.active {
        color: #fff;
        background: #233a8b;
        box-shadow: 0 2px 8px rgba(35,58,139,0.15);
    }
    
    /* Hide hamburger menu on desktop */
    .hamburger-menu {
        display: none;
    }
    .main-content {
    flex: 1;
    padding: 32px;
    background: #fff;
    margin-left: 240px;
    min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
    overflow-y: auto;
    box-sizing: border-box;
}
    .skill-registry-barangay-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px 16px;
        margin-top: 8px;
        margin-bottom: 4px;
        padding: 0 8px;
        width: 100%;
        box-sizing: border-box;
    }
    .skill-registry-barangay-toolbar label {
        font-weight: 600;
        color: #233a8b;
        font-size: 0.9rem;
        margin: 0;
    }
    #barangaySearch {
        flex: 1;
        min-width: 200px;
        max-width: min(100%, 480px);
        padding: 10px 14px;
        border: 2px solid #e3f2fd;
        border-radius: 10px;
        font-size: 0.95rem;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }
    #barangaySearch:focus {
        outline: none;
        border-color: #90caf9;
    }
    #barangaySearchHint {
        font-size: 0.85rem;
        color: #666;
        min-height: 1.2em;
    }
    .barangay-grid {
        display: grid;
        gap: 18px;
        margin-top: 16px;
        padding: 0 8px;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 150px), 1fr));
    }
    /* 13 barangays: 5 + 5 + 3; full content width on desktop so cards expand horizontally */
    @media (min-width: 900px) {
        .skill-registry-barangay-toolbar {
            padding: 0;
        }
        .barangay-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            width: 100%;
            max-width: none;
            margin-left: 0;
            margin-right: 0;
            padding: 0;
            gap: 20px 22px;
        }
    }
    @media (min-width: 1200px) {
        .barangay-grid {
            gap: 22px 28px;
        }
        .barangay-card {
            min-height: 176px;
            padding: 22px 16px 18px 16px;
        }
        .barangay-card img {
            width: 80px;
            height: 80px;
        }
        .barangay-name {
            font-size: 1.05rem;
        }
        .barangay-registry-count {
            font-size: 0.78rem;
        }
    }
    .barangay-card {
        background: #e3f2fd;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 18px 12px 14px 12px;
        box-shadow: 0 4px 20px rgba(35,58,139,0.08);
        border: 1px solid #bbdefb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
        min-height: 158px;
    }
    .barangay-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: #90caf9;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .barangay-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 40px rgba(35,58,139,0.15);
        border-color: #90caf9;
        background: #d1dbfa;
    }
    .barangay-card:hover::before {
        opacity: 1;
    }
    .barangay-card img {
        width: 70px;
        height: 70px;
        object-fit: contain;
        border-radius: 16px;
        background: #fff;
        margin-bottom: 12px;
        box-shadow: 0 4px 12px rgba(35,58,139,0.15);
        transition: all 0.3s ease;
    }
    .barangay-card:hover img {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(35,58,139,0.25);
    }
    .barangay-name {
        font-size: 1rem;
        font-weight: 700;
        color: #233a8b;
        text-align: center;
        margin-top: 8px;
        line-height: 1.3;
        padding: 0 6px;
    }
    .barangay-registry-count {
        font-size: 0.72rem;
        font-weight: 600;
        color: #1565c0;
        margin-top: 6px;
        text-align: center;
        line-height: 1.25;
        opacity: 0.95;
    }
    @media (max-width: 899px) {
        .barangay-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            max-width: none;
        }
    }
    @media (max-width: 600px) {
        .barangay-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
        @media (max-width: 768px) {
            .header {
                padding: 8px 16px;
                height: 56px;
            }
            
            .header img {
                height: 36px;
                margin-right: 12px;
            }
            
            .header-title {
                font-size: 1.4rem;
            }
            
        
        /* Hamburger Menu Button */
        .hamburger-menu {
            display: block !important;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            margin-right: 12px;
            z-index: 1001;
        }
        
        .hamburger-menu span {
            display: block;
            width: 25px;
            height: 3px;
            background: #fff;
            margin: 5px 0;
            transition: 0.3s;
            border-radius: 2px;
        }
        
        .hamburger-menu.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }
        
        .hamburger-menu.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger-menu.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }
        
        .layout {
            padding-top: 56px;
            flex-direction: column;
        }
        
        /* Mobile Sidebar - Hidden by default */
        .sidebar {
            position: fixed !important;
            top: 56px !important;
            left: -240px !important;
            width: 240px !important;
            height: calc(100vh - 56px) !important; height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important; max-height: calc(100dvh - 56px - env(safe-area-inset-bottom, 0px)) !important;
            background: #e3eaff !important;
            z-index: 999 !important;
            transition: left 0.3s ease !important;
            display: flex !important;
            flex-direction: column !important;
            padding: 20px 0 0 24px !important;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
        }
        
        .sidebar.active {
            left: 0 !important;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            text-decoration: none;
            color: #222;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.2s;
            border-radius: 8px;
            margin-bottom: 8px;
            gap: 12px;
        }
        
        .sidebar a:hover {
            color: #233a8b;
            background: #d1dbfa;
        }
        
        .sidebar a.active {
            color: #fff;
            background: #233a8b;
            box-shadow: 0 2px 8px rgba(35,58,139,0.15);
        }
        
        .sidebar .logout {
            margin-top: auto;
            margin-bottom: 32px;
            color: #222;
            font-weight: bold;
            display: block;
            width: 90%;
            text-align: left;
        }
        
        .main-content {
            margin-left: 0;
            padding: 20px;
            height: auto;
        }
        
        .barangay-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            padding: 0 10px;
        }
        
        .barangay-card {
            padding: 20px 12px 16px 12px;
            min-height: 160px;
        }
        
        .barangay-card img {
            width: 60px;
            height: 60px;
        }
        
        .barangay-name {
            font-size: 0.9rem;
        }
        
        .modal-content {
            padding: 16px 12px 12px 12px;
            max-width: 98vw;
            width: 98vw;
            margin: 0 1vw;
        }
        
        .barangay-title-modal {
            font-size: 1.1rem;
            margin-left: 0;
            margin-top: 20px;
            padding: 10px 12px;
            text-align: center;
        }
        
        .barangay-table-form {
            font-size: 0.75rem;
            min-width: 4000px;
        }
        
        .barangay-table-form th,
        .barangay-table-form td {
            padding: 6px 4px;
            font-size: 0.75rem;
            min-width: 80px;
        }
        
        /* Improve filter container for mobile */
        .filter-container {
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        
        .filter-row {
            flex-direction: column;
            gap: 12px;
        }
        
        .filter-group {
            justify-content: space-between;
            width: 100%;
        }
        
        .filter-select {
            min-width: 140px;
            flex: 1;
        }
        
        .add-modal-content {
            padding: 20px 16px 16px 16px;
            max-width: 95vw;
            width: 95vw;
            max-height: 85vh;
        }
        
        .add-modal-form .row {
            flex-direction: column;
            gap: 8px;
        }
        
        .form-section {
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .section-header h3 {
            font-size: 1rem;
        }
        
        .add-modal-form .submit-btn {
            padding: 12px 32px;
            font-size: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .header {
            padding: 6px 12px;
            height: 48px;
        }
        
        .header img {
            height: 28px;
            margin-right: 8px;
        }
        
        .header-title {
            font-size: 1.2rem;
        }
        
        .layout {
            padding-top: 48px;
        }
        
        .sidebar {
            padding: 12px;
            gap: 6px;
        }
        
        .sidebar a {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
        
        .main-content {
            padding: 16px;
        }
        
        .barangay-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 0 5px;
        }
        
        .barangay-card {
            padding: 16px 10px 12px 10px;
            min-height: 140px;
        }
        
        .barangay-card img {
            width: 50px;
            height: 50px;
        }
        
        .barangay-name {
            font-size: 0.85rem;
        }
        
        .modal-content {
            padding: 20px 16px 16px 16px;
            max-width: 98vw;
            width: 98vw;
        }
        
        .barangay-title-modal {
            font-size: 1.1rem;
            margin-top: 30px;
            padding: 10px 12px;
        }
        
        .barangay-table-form {
            font-size: 0.75rem;
            min-width: 4000px;
        }
        
        .barangay-table-form th,
        .barangay-table-form td {
            padding: 6px 4px;
            font-size: 0.75rem;
            min-width: 90px;
        }
        
        .add-modal-content {
            padding: 18px 14px 14px 14px;
            max-width: 98vw;
            width: 98vw;
            max-height: 90vh;
        }
        
        .add-modal-form .submit-btn {
            padding: 10px 24px;
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 800px) {
        .layout {
            flex-direction: column;
        }
        .sidebar {
            width: 100%;
            height: auto;
            position: static;
            flex-direction: row;
            padding: 16px 0 0 0;
            overflow-x: auto;
        }
        .main-content {
            margin-left: 0;
            padding: 20px;
            height: auto;
        }
        .barangay-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .sidebar .logout {
            margin-top: auto;
            margin-bottom: 32px;
            color: #222;
            font-weight: bold;
            display: block;
            width: 90%;
            text-align: left;
        }
    }
    .barangay-table-form td:last-child input[type="text"] {
    min-width: 120px;
    max-width: 320px;
}

    /* SweetAlert z-index fix - ensure validation/alerts appear above add modal (z-index 2000) */
    .swal2-container {
        z-index: 99999 !important;
    }
    .swal-high-zindex {
        z-index: 99999 !important;
    }
    
    /* Mobile touch improvements */
    @media (max-width: 768px) {
        /* Better touch targets for mobile */
        .barangay-table-form input,
        .barangay-table-form select {
            min-height: 32px;
            touch-action: manipulation;
        }
        
        /* Improve checkbox visibility on mobile */
        .barangay-table-form input[type="checkbox"] {
            transform: scale(1.2);
            margin: 4px;
        }
        
        /* Better modal actions for mobile */
        .modal-actions {
            position: static;
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 16px 0;
            flex-wrap: wrap;
        }
        
        .modal-actions button {
            flex: 1;
            min-width: 80px;
            padding: 10px 16px;
            font-size: 0.9rem;
        }
        
        /* Improve table header readability on mobile */
        .barangay-table-form th {
            background: #e3f2fd;
            color: #1565c0;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #90caf9;
        }
        
        /* Add visual separation for mobile table rows */
        .barangay-table-form tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .barangay-table-form tr:hover {
            background: #e3f2fd;
        }
    }
    
    @media (max-width: 480px) {
        /* Even smaller touch targets for very small screens */
        .barangay-table-form input,
        .barangay-table-form select {
            min-height: 28px;
        }
        
        .barangay-table-form input[type="checkbox"] {
            transform: scale(1.1);
        }
        
        .modal-actions button {
            padding: 8px 12px;
            font-size: 0.8rem;
        }
    }
</style>
<!-- ...existing code... -->
<!-- ...existing code... -->
    <link rel="stylesheet" href="../assets/css/Employer-sidebar-neat.css?v=<?php echo time(); ?>">
    <script src="../assets/js/employer-page-loading.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
<div class="header" id="mainHeader">
        <div style="display: flex; align-items: center;">
            <button class="hamburger-menu" id="hamburgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title" id="headerTitle">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;" id="adminSection">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="adminUsername" style="font-size: 1rem; font-weight: 500;">Welcome, Admin</span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php"> DASHBOARD</a>
            <a href="job_postings.php"> JOB POSTINGS</a>
            <a href="job.php"> JOBSEEKERS<?php echo js_pending_jobseekers_badge_html($pending_jobseekers_count); ?></a>
            <a href="follow_up_requests.php"> FOLLOW-UP REQUESTS<?php echo fu_follow_up_badge_html($follow_up_pending_count); ?></a>
            <a href="request_follow_up.php"> REQUEST FOLLOW UP<span class="acfu-sidebar-badge"><?php echo acfu_unread_badge_html($acfu_unread_count); ?></span></a>
            <a href="skill.php" class="active"> SKILL REGISTRY</a>
            <a href="companies_list.php"> COMPANIES</a>
            <a href="btec.php"> BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
            <a href="analytics.php"> Analytics</a>
            <a href="announcement.php"> ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout"> Logout</a>
        </div>
        <!-- ...existing code... -->
<div class="main-content">

    <!-- Add Modal for new entry -->
    <div class="add-modal" id="addModal">
        <div class="add-modal-content">
            <button class="add-modal-close" id="closeAddModalBtn">&#8592;</button>
            <form class="add-modal-form" id="addSkillForm">
                <div style="text-align:center; font-weight:700; font-size:1.2rem; margin-bottom:30px; margin-top:60px; color:#1565c0; background: #e3f2fd; padding: 16px 20px; border-radius: 12px; border-left: 4px solid #90caf9; box-shadow: 0 2px 8px rgba(35,58,139,0.1);">PUBLIC EMPLOYMENT SERVICE OFFICE<br>SKILLS REGISTRY SYSTEM</div>
                
                <!-- Survey Information Section -->
                <div class="form-section">
                    <div class="section-header">
                        <h3>📋 Survey Information</h3>
                    </div>
                    <div class="row">
                        <div>
                            <label>City/Municipality:</label>
                            <input type="text" name="city" id="addCity" value="Norzagaray" readonly style="background:#e9ecef;cursor:not-allowed;" />
                        </div>
                        <div>
                            <label>Barangay:</label>
                            <input type="text" name="barangay" id="addBarangay" readonly style="background:#e9ecef;cursor:not-allowed;" />
                        </div>
                        <div>
                            <label>Date of Survey:</label>
                            <input type="date" name="survey_date" />
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="section-header">
                        <h3>👤 Personal Information</h3>
                    </div>
                    <div class="row">
                        <div style="flex:2;">
                            <label>Full Name <span style="font-weight:normal;">(Surname, First Name, Middle Initial)</span>:</label>
                            <input type="text" name="printed_name" placeholder="Enter full name" />
                        </div>
                        <div>
                            <label>Date of Birth:</label>
                            <input type="date" name="dob" />
                        </div>
                        <div>
                            <label>Age:</label>
                            <input type="number" name="age" id="addAgeInput" min="0" max="120" placeholder="Auto-calculated" readonly style="background:#e9ecef;cursor:not-allowed;" />
                        </div>
                    </div>
                    <div class="row">
                        <div>
                            <label>Sex:</label>
                            <select name="sex">
                                <option value="">Select</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div>
                            <label>Marital Status:</label>
                            <select name="marital">
                                <option value="">Select</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </div>
                        <div>
                            <label>Contact Number:</label>
                            <input type="text" name="contact" placeholder="09XX-XXX-XXXX" />
                        </div>
                    </div>
                    <div class="row">
                        <div style="flex:1;">
                            <label>Address <span style="font-weight:normal;">(House #/ Sitio/ Purok/ Street)</span>:</label>
                            <input type="text" name="address" placeholder="Enter complete address" />
                        </div>
                    </div>
                </div>

                <!-- Education Section -->
                <div class="form-section">
                    <div class="section-header">
                        <h3>🎓 Educational Background</h3>
                    </div>
                    <div class="row">
                        <div style="flex:1;">
                            <label>Educational Attainment <span style="font-weight:normal;"></span>:</label>
                            <select name="education" id="educationSelect">
                                <option value="">Select Educational Attainment</option>
                                <option value="Elementary Graduate">Elementary Graduate</option>
                                <option value="High School Level">High School Level</option>
                                <option value="High School Graduate">High School Graduate</option>
                                <option value="College Level">College Level</option>
                                <option value="College Graduate">College Graduate</option>
                                <option value="Others">Others, Specify:</option>
                            </select>
                            <input type="text" name="education_other" id="educationOther" placeholder="Specify other educational attainment" style="margin-top: 8px; display: none;" />
                        </div>
                    </div>
                </div>

                <!-- Employment Status Section -->
                <div class="form-section">
                    <div class="section-header">
                        <h3>💼 Employment Status</h3>
                    </div>
                    <div class="row">
                        <div>
                            <label>First-Time Jobseeker:</label>
                            <div class="radio-group">
                                <input type="radio" name="ftjs" value="yes" id="ftjs_yes"><label for="ftjs_yes">Yes</label>
                                <input type="radio" name="ftjs" value="no" id="ftjs_no"><label for="ftjs_no">No</label>
                            </div>
                        </div>
                        <div>
                            <label>COVID-19 Displaced Worker:</label>
                            <div class="radio-group">
                                <input type="radio" name="covid" value="yes" id="covid_yes"><label for="covid_yes">Yes</label>
                                <input type="radio" name="covid" value="no" id="covid_no"><label for="covid_no">No</label>
                            </div>
                        </div>
                        <div>
                            <label>Currently Unemployed:</label>
                            <div class="radio-group">
                                <input type="checkbox" name="ue" id="ue_checkbox" value="yes">
                                <label for="ue_checkbox">Yes</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Experience Section -->
                <div class="form-section">
                    <div class="section-header">
                        <h3>💼 Employment Experience (Leave blank if not applicable)</h3>
                    </div>
                    <div class="row">
                        <div>
                            <label>Wage Employment (WE) Position:</label>
                            <input type="text" name="we_position" placeholder="e.g., Office Clerk, Sales Representative" />
                        </div>
                        <div>
                            <label>Duration (months/years):</label>
                            <select name="we_months">
                                <option value="">Select Duration</option>
                                <option value="0 month - 1 year">0 month - 1 year</option>
                                <option value="2 years - 3 years">2 years - 3 years</option>
                                <option value="4 years - 5 years">4 years - 5 years</option>
                                <option value="6 years - above">6 years - above</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div>
                            <label>Self-Employment (SE) Business:</label>
                            <input type="text" name="se_business" placeholder="e.g., Sari-sari Store, Food Stall" />
                        </div>
                        <div>
                            <label>Duration (months/years):</label>
                            <select name="se_months">
                                <option value="">Select Duration</option>
                                <option value="0 month - 1 year">0 month - 1 year</option>
                                <option value="2 years - 3 years">2 years - 3 years</option>
                                <option value="4 years - 5 years">4 years - 5 years</option>
                                <option value="6 years - above">6 years - above</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Skills Section -->
                <div class="form-section">
                    <div class="section-header">
                        <h3>🛠️ Skills & Competencies</h3>
                    </div>
                    <div class="row">
                        <div style="flex:1;">
                            <label>Skills <span style="font-weight:normal;">(List all relevant skills)</span>:</label>
                            <input type="text" name="skills" placeholder="e.g., Computer Literacy, Driving, Cooking, Carpentry, etc." />
                        </div>
                    </div>
                </div>

                <div class="legend">
                    <strong>Legend:</strong><br>
                    <strong>WE</strong> = Wage Employed, <strong>SE</strong> = Self Employed, <strong>UE</strong> = Unemployed
                </div>
                <button type="submit" class="submit-btn">SUBMIT ENTRY</button>
            </form>
        </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #e3f2fd;">
        <div>
            <h2 style="color:#233a8b; font-size:1.8rem; font-weight:700; margin:0;">Skill Registry</h2>
            <p style="color:#666; margin:8px 0 0 0; font-size:1.1rem;">Select a barangay to manage skills registry</p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="background: linear-gradient(135deg, #e3f2fd, #f0f4ff); padding: 12px 20px; border-radius: 12px; border-left: 4px solid #1976d2;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #1976d2;" id="barangayCount"><?php echo count($skill_registry_barangays); ?></div>
                <div style="font-size: 0.9rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Barangays</div>
            </div>
        </div>
    </div>
    <div class="skill-registry-barangay-toolbar">
        <label for="barangaySearch">Find barangay</label>
        <input type="search" id="barangaySearch" placeholder="Type to filter by name…" autocomplete="off" aria-label="Filter barangays by name">
        <span id="barangaySearchHint"></span>
    </div>
    <div class="barangay-grid">
        <?php foreach ($skill_registry_barangays as $br): ?>
            <?php
            $bName = $br[0];
            $bFile = $br[1];
            $regCount = $skill_registry_counts[$bName] ?? 0;
            ?>
            <div class="barangay-card" data-barangay="<?php echo htmlspecialchars($bName, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="../assets/image/<?php echo htmlspecialchars($bFile, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($bName, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="barangay-name"><?php echo htmlspecialchars($bName); ?></div>
                <div class="barangay-registry-count"><?php echo $regCount; ?> registry <?php echo $regCount === 1 ? 'entry' : 'entries'; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Modal for barangay form -->
    <div class="modal" id="barangayModal">
        <div class="modal-content">
            <div class="filter-container">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filterMonth" class="filter-label">📅 Month:</label>
                        <select id="filterMonth" class="filter-select">
                            <?php $skillFilterDefaultMonth = date('m'); ?>
                            <option value="01"<?php echo $skillFilterDefaultMonth === '01' ? ' selected' : ''; ?>>January</option>
                            <option value="02"<?php echo $skillFilterDefaultMonth === '02' ? ' selected' : ''; ?>>February</option>
                            <option value="03"<?php echo $skillFilterDefaultMonth === '03' ? ' selected' : ''; ?>>March</option>
                            <option value="04"<?php echo $skillFilterDefaultMonth === '04' ? ' selected' : ''; ?>>April</option>
                            <option value="05"<?php echo $skillFilterDefaultMonth === '05' ? ' selected' : ''; ?>>May</option>
                            <option value="06"<?php echo $skillFilterDefaultMonth === '06' ? ' selected' : ''; ?>>June</option>
                            <option value="07"<?php echo $skillFilterDefaultMonth === '07' ? ' selected' : ''; ?>>July</option>
                            <option value="08"<?php echo $skillFilterDefaultMonth === '08' ? ' selected' : ''; ?>>August</option>
                            <option value="09"<?php echo $skillFilterDefaultMonth === '09' ? ' selected' : ''; ?>>September</option>
                            <option value="10"<?php echo $skillFilterDefaultMonth === '10' ? ' selected' : ''; ?>>October</option>
                            <option value="11"<?php echo $skillFilterDefaultMonth === '11' ? ' selected' : ''; ?>>November</option>
                            <option value="12"<?php echo $skillFilterDefaultMonth === '12' ? ' selected' : ''; ?>>December</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filterYear" class="filter-label">📆 Year:</label>
                        <select id="filterYear" class="filter-select"></select>
                    </div>
                </div>
            </div>
            <button class="modal-close" id="closeModalBtn">&#8592;</button>
            <div class="modal-actions">
                <button id="editModalBtn">Edit</button>
                <button id="downloadModalBtn">Download</button>
                <button id="addModalBtn">Add</button>
            </div>
            <div class="barangay-title-modal" id="barangayModalTitle">Bangkal</div>
            <div class="barangay-table-scroll" style="overflow-x:auto;">
                <table class="barangay-table-form">
        <!-- colgroup removed for auto-sizing columns -->
        <tr>
            <th rowspan="2">No.</th>
            <th rowspan="2">Date of Survey</th>
            <th rowspan="2">Printed Name<br><span style="font-weight:normal;">(Surname, Firstname, Middle Initial)</span></th>
            <th colspan="2">First Time Job Seeker</th>
            <th colspan="2">COVID-19 Displaced Workers</th>
            <th rowspan="2">House #/ Sitio/ Purok/ Street</th>
            <th rowspan="2">Date of Birth<br><span style="font-weight:normal;">(mm/dd/yyyy)</span></th>
            <th rowspan="2">Contact #<br>Cell/Tel</th>
            <th rowspan="2">Age</th>
            <th rowspan="2">Sex<br>(M/F)</th>
            <th rowspan="2">Marital Status</th>
            <th rowspan="2">Educational Attainment<br><span style="font-weight:normal;">(Please specify level and course)</span></th>
            <th rowspan="2">Employment<br>WE (Position)</th>
            <th rowspan="2"># of mos./ years</th>
            <th rowspan="2">Employment<br> SE (Livelihood/Business)</th>
            <th rowspan="2"># of mos./ years</th>
            <th rowspan="2">UE</th>
            <th rowspan="2">Skills</th>
        </tr>
        <tr>
            <th>YES</th>
            <th>NO</th>
            <th>YES</th>
            <th>NO</th>
        </tr>
        <!-- 15 empty rows for input -->
        <!-- ROWS_START -->
        <!-- ROWS -->
        <!-- ROWS_END -->
    </table>
            </div>
        </div>
    </div>
</div>
<!-- ...existing code... -->
    </div>
<script>
// Hamburger Menu Functionality
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const sidebar = document.querySelector('.sidebar');
    
    // Show hamburger menu on mobile
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            hamburgerMenu.style.display = 'block';
        } else {
            hamburgerMenu.style.display = 'none';
            sidebar.classList.remove('active');
            hamburgerMenu.classList.remove('active');
        }
    }
    
    // Initial check
    checkScreenSize();
    
    // Check on resize
    window.addEventListener('resize', checkScreenSize);
    
    // Toggle sidebar
    hamburgerMenu.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        hamburgerMenu.classList.toggle('active');
    });
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !hamburgerMenu.contains(event.target)) {
                sidebar.classList.remove('active');
                hamburgerMenu.classList.remove('active');
            }
        }
    });
});

// Update username display
        fetch('session_check.php')
            .then(r => r.json())
            .then(data => {
                document.getElementById('adminUsername').textContent = data.username; // Remove "Welcome, " prefix
                if (data.isMainAdmin) {
                    document.getElementById('addAccountLink').style.display = 'block';
                } else {
                    document.getElementById('addAccountLink').style.display = 'none';
                }
            })
            .catch(() => {
                console.error('Session check failed');
            });
            
        // Mobile header display fix
        function handleMobileHeader() {
            const header = document.getElementById('mainHeader');
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const headerTitle = document.getElementById('headerTitle');
            const adminSection = document.getElementById('adminSection');
            
            if (window.innerWidth <= 768) {
                // Mobile: Ensure header is properly displayed
                header.style.position = 'fixed';
                header.style.top = '0';
                header.style.left = '0';
                header.style.width = '100%';
                header.style.zIndex = '1000';
                header.style.display = 'flex';
                header.style.alignItems = 'center';
                header.style.justifyContent = 'space-between';
                header.style.padding = '12px 20px';
                header.style.height = '64px';
                header.style.boxSizing = 'border-box';
                header.style.maxWidth = '100vw';
                header.style.overflow = 'hidden';
                
                // Show hamburger menu
                hamburgerMenu.style.display = 'block';
                hamburgerMenu.style.visibility = 'visible';
                
                // Adjust title size for mobile - make smaller
                headerTitle.style.fontSize = '0.9rem';
                headerTitle.style.whiteSpace = 'nowrap';
                headerTitle.style.overflow = 'hidden';
                headerTitle.style.textOverflow = 'ellipsis';
                headerTitle.style.maxWidth = '100px';
                
                // Adjust admin section for mobile - make smaller
                adminSection.style.marginRight = '8px';
                adminSection.style.gap = '4px';
                adminSection.style.fontSize = '0.8rem';
                adminSection.style.maxWidth = '120px';
                adminSection.style.overflow = 'hidden';
                adminSection.style.textOverflow = 'ellipsis';
                adminSection.style.whiteSpace = 'nowrap';
                
                // Ensure logo is visible - make smaller
                const logo = header.querySelector('img');
                if (logo) {
                    logo.style.height = '32px';
                    logo.style.marginRight = '8px';
                }
                
                // Adjust hamburger menu spacing
                hamburgerMenu.style.marginRight = '8px';
                
            } else {
                // Desktop: Reset to normal
                header.style.position = 'fixed';
                header.style.top = '0';
                header.style.left = '0';
                header.style.width = '100%';
                header.style.zIndex = '1000';
                header.style.display = 'flex';
                header.style.alignItems = 'center';
                header.style.justifyContent = 'space-between';
                header.style.padding = '12px 20px';
                header.style.height = '64px';
                header.style.boxSizing = 'border-box';
                header.style.maxWidth = '100vw';
                
                // Hide hamburger menu on desktop
                hamburgerMenu.style.display = 'none';
                
                // Reset title size
                headerTitle.style.fontSize = '1.7rem';
                headerTitle.style.whiteSpace = 'normal';
                headerTitle.style.overflow = 'visible';
                headerTitle.style.textOverflow = 'unset';
                headerTitle.style.maxWidth = 'none';
                
                // Reset admin section
                adminSection.style.marginRight = '20px';
                adminSection.style.gap = '8px';
                adminSection.style.fontSize = '1rem';
                adminSection.style.maxWidth = 'none';
                adminSection.style.overflow = 'visible';
                adminSection.style.textOverflow = 'unset';
                adminSection.style.whiteSpace = 'normal';
                
                // Reset logo
                const logo = header.querySelector('img');
                if (logo) {
                    logo.style.height = '48px';
                    logo.style.marginRight = '16px';
                }
            }
        }
        
        // Remove "Welcome, " text for both mobile and desktop
        function removeWelcomeText() {
            const adminUsername = document.getElementById('adminUsername');
            if (adminUsername && adminUsername.textContent.includes('Welcome, ')) {
                adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
            }
        }
        
        // Apply mobile styles immediately
        function applyMobileStyles() {
            if (window.innerWidth <= 768) {
                const headerTitle = document.getElementById('headerTitle');
                const adminUsername = document.getElementById('adminUsername');
                const adminSection = document.getElementById('adminSection');
                const logo = document.querySelector('img');
                const hamburgerMenu = document.getElementById('hamburgerMenu');
                
                // Apply inline styles immediately
                if (headerTitle) {
                    headerTitle.style.fontSize = '0.9rem';
                    headerTitle.style.maxWidth = '100px';
                    headerTitle.style.overflow = 'hidden';
                    headerTitle.style.textOverflow = 'ellipsis';
                    headerTitle.style.whiteSpace = 'nowrap';
                }
                
                if (adminUsername) {
                    adminUsername.style.fontSize = '0.8rem';
                    adminUsername.style.maxWidth = '120px';
                    adminUsername.style.overflow = 'hidden';
                    adminUsername.style.textOverflow = 'ellipsis';
                    adminUsername.style.whiteSpace = 'nowrap';
                    // Remove "Welcome, " text
                    if (adminUsername.textContent.includes('Welcome, ')) {
                        adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
                    }
                }
                
                if (adminSection) {
                    adminSection.style.marginRight = '8px';
                    adminSection.style.gap = '4px';
                    adminSection.style.maxWidth = '120px';
                }
                
                if (logo) {
                    logo.style.height = '32px';
                    logo.style.marginRight = '8px';
                }
                
                if (hamburgerMenu) {
                    hamburgerMenu.style.display = 'block';
                    hamburgerMenu.style.visibility = 'visible';
                    hamburgerMenu.style.marginRight = '8px';
                }
            }
        }
        
        // Apply immediately
        applyMobileStyles();
        removeWelcomeText();
        
        // Initial check
        handleMobileHeader();
        
        // Check on resize
        window.addEventListener('resize', handleMobileHeader);

// Populate year filter (from 2022 to current year, progressive)
const filterYear = document.getElementById('filterYear');
function populateYearFilter() {
    const nowYear = new Date().getFullYear();
    filterYear.innerHTML = '';
    for (let y = nowYear; y >= 2022; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        filterYear.appendChild(opt);
    }
    filterYear.value = nowYear;
}
populateYearFilter();

let filterMonth = document.getElementById('filterMonth');
let filterMonthVal =
    filterMonth && filterMonth.value
        ? filterMonth.value
        : String(new Date().getMonth() + 1).padStart(2, '0');
let filterYearVal = new Date().getFullYear();
filterMonth.addEventListener('change', function() {
    filterMonthVal = this.value;
    fetchBarangayTable(currentBarangay);
});
filterYear.addEventListener('change', function() {
    filterYearVal = this.value;
    fetchBarangayTable(currentBarangay);
});
// Add Modal logic (per barangay)
const addModal = document.getElementById('addModal');
const addModalBtn = document.getElementById('addModalBtn');
const closeAddModalBtn = document.getElementById('closeAddModalBtn');
const addBarangayInput = document.getElementById('addBarangay');
const addCityInput = document.getElementById('addCity');
let currentBarangay = '';
addModalBtn.addEventListener('click', function() {
    // Set barangay field to currentBarangay
    addBarangayInput.value = currentBarangay;
    addCityInput.value = 'Norzagaray';
    addModal.style.display = 'flex';
});
closeAddModalBtn.addEventListener('click', function() {
    addModal.style.display = 'none';
});
window.addEventListener('click', (e) => {
    if (e.target === addModal) {
        addModal.style.display = 'none';
    }
});
// Download button functionality
document.getElementById('downloadModalBtn').addEventListener('click', function() {
    downloadCurrentTableData();
});

// Function to download current table data as Excel file with proper formatting
function downloadCurrentTableData() {
    const table = document.querySelector('.barangay-table-form');
    if (!table) {
        alert('No table data found!');
        return;
    }
    
    // Get current filter values
    const currentBarangay = document.getElementById('barangayModalTitle').textContent;
    const currentMonth = document.getElementById('filterMonth').value;
    const currentYear = document.getElementById('filterYear').value;
    
    // Create Excel file using SheetJS library
    createExcelFile(currentBarangay, currentMonth, currentYear, table);
}

// Function to create Excel file with proper formatting
function createExcelFile(barangay, month, year, table) {
    // Check if SheetJS is loaded, if not, load it dynamically
    if (typeof XLSX === 'undefined') {
        loadSheetJS().then(() => {
            createExcelFile(barangay, month, year, table);
        });
        return;
    }
    
    // Create a new workbook
    const wb = XLSX.utils.book_new();
    
    // Prepare data for Excel
    const excelData = prepareExcelData(barangay, month, year, table);
    
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(excelData);
    
    // Set column widths for better display
    const colWidths = [
        { wch: 5 },   // No.
        { wch: 15 },  // Date of Survey
        { wch: 25 },  // Full Name
        { wch: 20 },  // First Time Job Seeker
        { wch: 25 },  // COVID-19 Displaced Worker
        { wch: 30 },  // Complete Address
        { wch: 15 },  // Date of Birth
        { wch: 18 },  // Contact Number
        { wch: 8 },   // Age
        { wch: 10 },  // Gender
        { wch: 15 },  // Marital Status
        { wch: 20 },  // Education Level
        { wch: 25 },  // Work Experience Position
        { wch: 15 },  // WE Duration
        { wch: 25 },  // Self-Employment Business
        { wch: 15 },  // SE Duration
        { wch: 15 },  // Unemployed Status
        { wch: 35 }   // Skills & Competencies
    ];
    
    ws['!cols'] = colWidths;
    
    // Add the worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'Skill Registry');
    
    // Generate filename
    const monthStr = month ? getMonthName(month) : 'AllMonths';
    const yearStr = year || 'AllYears';
    const dateStr = new Date().toISOString().split('T')[0];
    const filename = `SkillRegistry_${barangay.replace(/\s+/g, '')}_${monthStr}_${yearStr}_${dateStr}.xlsx`;
    
    // Write and download the file
    XLSX.writeFile(wb, filename);
}

// Function to load SheetJS library dynamically
function loadSheetJS() {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load SheetJS library'));
        document.head.appendChild(script);
    });
}

// Function to prepare data for Excel export
function prepareExcelData(barangay, month, year, table) {
    const data = [];
    
    // Add header information
    data.push(['SKILL REGISTRY DATA EXPORT']);
    data.push(['=====================================']);
    data.push([`Barangay: ${barangay}`]);
    data.push([`Month: ${month ? getMonthName(month) : 'All Months'}`]);
    data.push([`Year: ${year || 'All Years'}`]);
    data.push([`Export Date: ${new Date().toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    })}`]);
    data.push([`Generated by: WorkConnect PESO System`]);
    data.push(['=====================================']);
    data.push([]); // Empty row
    
    // Add table headers
    const headers = [
        'No.',
        'Date of Survey',
        'Full Name',
        'First Time Job Seeker',
        'COVID-19 Displaced Worker',
        'Complete Address',
        'Date of Birth',
        'Contact Number',
        'Age',
        'Gender',
        'Marital Status',
        'Education Level',
        'WE (Position)',
        'WE Duration',
        'SE (Livelihood/Business)',
        'SE Duration',
        'Unemployed Status',
        'Skills & Competencies'
    ];
    data.push(headers);
    
    // Get all data rows (skip header rows)
    const rows = table.querySelectorAll('tr');
    let dataRowCount = 0;
    
    rows.forEach((row, index) => {
        // Skip the first two header rows
        if (index < 2) return;
        
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return;
        
        // Check if this row has actual data (not empty)
        const nameInput = cells[2]?.querySelector('input');
        if (!nameInput || !nameInput.value.trim()) return;
        
        dataRowCount++;
        let rowData = [];
        
        // Process each cell with special handling for checkbox pairs
        let ftjsValue = '';
        let covidValue = '';
        let processedCells = 0;
        
        cells.forEach((cell, cellIndex) => {
            const input = cell.querySelector('input');
            if (input) {
                if (input.type === 'checkbox') {
                    // Handle checkbox pairs for FTJS and COVID-19
                    if (cellIndex === 3) { // FTJS Yes checkbox
                        if (input.checked) ftjsValue = 'Yes';
                    } else if (cellIndex === 4) { // FTJS No checkbox
                        if (input.checked) ftjsValue = 'No';
                    } else if (cellIndex === 5) { // COVID-19 Yes checkbox
                        if (input.checked) covidValue = 'Yes';
                    } else if (cellIndex === 6) { // COVID-19 No checkbox
                        if (input.checked) covidValue = 'No';
                    } else if (cellIndex === 18) { // Unemployed checkbox
                        rowData.push(input.checked ? 'Yes' : 'No');
                        processedCells++;
                    }
                } else {
                    let value = input.value || '';
                    
                    // Format specific fields for better readability
                    if (cellIndex === 1 && value) { // Date of Survey
                        value = formatDate(value);
                    } else if (cellIndex === 8 && value) { // Date of Birth
                        value = formatDate(value);
                    } else if (cellIndex === 9 && value) { // Contact Number
                        value = formatContactNumber(value);
                    } else if (cellIndex === 19 && value) { // Skills
                        value = formatSkills(value);
                    }
                    
                    // Add data based on cell index, skipping checkbox columns
                    if (cellIndex === 0) { // No.
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 1) { // Date of Survey
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 2) { // Full Name
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 7) { // Address
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 8) { // Date of Birth
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 9) { // Contact Number
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 10) { // Age
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 11) { // Gender
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 12) { // Marital Status
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 13) { // Education Level
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 14) { // Work Experience Position
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 15) { // WE Duration
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 16) { // Self-Employment Business
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 17) { // SE Duration
                        rowData.push(value);
                        processedCells++;
                    } else if (cellIndex === 19) { // Skills
                        rowData.push(value);
                        processedCells++;
                    }
                }
            } else {
                // Handle non-input cells
                if (cellIndex === 0) { // No.
                    rowData.push(cell.textContent.trim());
                    processedCells++;
                }
            }
        });
        
        // Add consolidated checkbox values at the correct positions
        // Insert FTJS value at position 3 (after No., Date of Survey, Full Name)
        rowData.splice(3, 0, ftjsValue || 'Not specified');
        // Insert COVID-19 value at position 4 (after FTJS)
        rowData.splice(4, 0, covidValue || 'Not specified');
        
        data.push(rowData);
    });
    
    // Add summary section
    data.push([]); // Empty row
    data.push(['SUMMARY REPORT']);
    data.push(['=====================================']);
    data.push([`Total Records Exported: ${dataRowCount}`]);
    data.push([`Barangay: ${barangay}`]);
    data.push([`Filter Applied: ${month ? getMonthName(month) : 'All months'} ${year || 'All years'}`]);
    data.push([`Export Timestamp: ${new Date().toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    })}`]);
    data.push(['=====================================']);
    
    return data;
}

// Helper functions for better data formatting
function formatDate(dateString) {
    if (!dateString || dateString === '0000-00-00') return 'Not specified';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

function formatContactNumber(contact) {
    if (!contact) return 'Not provided';
    // Remove any non-digit characters and format
    const cleaned = contact.replace(/\D/g, '');
    if (cleaned.length === 11 && cleaned.startsWith('09')) {
        return `+63${cleaned.substring(1)}`;
    } else if (cleaned.length === 10) {
        return `+63${cleaned}`;
    }
    return contact;
}

function formatSkills(skills) {
    if (!skills) return 'None specified';
    // Clean up skills formatting
    return skills.split(',').map(skill => skill.trim()).filter(skill => skill).join(', ');
}

// Helper function to get month name
function getMonthName(monthNumber) {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[parseInt(monthNumber) - 1] || 'Unknown';
}
// Generate 15 empty rows for the modal table
const table = document.querySelector('.barangay-table-form');
if (table) {
    let rows = '';
    for (let i = 1; i <= 15; i++) {
        rows += `<tr>
            <td>${i}</td>
            <td><input type="date" name="survey_date${i}" class="clean-date-input"></td>
            <td><input type="text" name="printed_name${i}"></td>
            <td><input type="checkbox" name="ftjs_yes${i}"></td>
            <td><input type="checkbox" name="ftjs_no${i}"></td>
            <td><input type="checkbox" name="covid_yes${i}"></td>
            <td><input type="checkbox" name="covid_no${i}"></td>
            <td><input type="text" name="address${i}"></td>
            <td><input type="date" name="dob${i}"></td>
            <td><input type="text" name="contact${i}"></td>
            <td><input type="number" name="age${i}"></td>
            <td><input type="text" name="sex${i}"></td>
            <td><input type="text" name="marital${i}"></td>
            <td><input type="text" name="education${i}"></td>
            <td><input type="text" name="we_position${i}"></td>
            <td><input type="text" name="we_months${i}"></td>
            <td><input type="text" name="se_business${i}"></td>
            <td><input type="text" name="se_months${i}"></td>
            <td><input type="checkbox" name="ue${i}"></td>
            <td><input type="text" name="skills${i}"></td>
        </tr>`;
    }
    // Insert rows between ROWS_START and ROWS_END comments
    table.innerHTML = table.innerHTML.replace(
        /<!-- ROWS_START -->([\s\S]*?)<!-- ROWS_END -->/,
        `<!-- ROWS_START -->\n${rows}\n<!-- ROWS_END -->`
    );
}
// Modal logic for barangay cards
const modal = document.getElementById('barangayModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const barangayTitle = document.getElementById('barangayModalTitle');
(function initBarangaySearch() {
    const search = document.getElementById('barangaySearch');
    if (!search) return;
    function applyBarangayFilter() {
        const q = (search.value || '').trim().toLowerCase();
        let n = 0;
        document.querySelectorAll('.barangay-card').forEach(function (card) {
            const name = (card.getAttribute('data-barangay') || '').toLowerCase();
            const show = !q || name.indexOf(q) !== -1;
            card.style.display = show ? '' : 'none';
            if (show) n++;
        });
        const hint = document.getElementById('barangaySearchHint');
        if (hint) {
            hint.textContent = q ? (n + ' match' + (n === 1 ? '' : 'es')) : '';
        }
    }
    search.addEventListener('input', applyBarangayFilter);
    search.addEventListener('search', applyBarangayFilter);
})();
const cards = document.querySelectorAll('.barangay-card');
let barangayTable = document.querySelector('.barangay-table-form');
let editMode = false;
cards.forEach(card => {
    card.addEventListener('click', () => {
        const name = card.getAttribute('data-barangay');
        barangayTitle.textContent = name;
        currentBarangay = name;
        // Set barangay field for add modal as well
        addBarangayInput.value = name;
        // Fetch and render table for this barangay
        fetchBarangayTable(name);
        modal.style.display = 'flex';
    });
});
closeModalBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});
window.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});

// Fetch barangay table data and render (always read-only by default)
// Updated: Added SE columns - v2.0
function fetchBarangayTable(barangay) {
    let url = 'skill_registry.php?barangay=' + encodeURIComponent(barangay);
    if (filterMonth && filterMonth.value) url += '&month=' + filterMonth.value;
    if (filterYear && filterYear.value) url += '&year=' + filterYear.value;
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!barangayTable) barangayTable = document.querySelector('.barangay-table-form');
            let html = '';
            // Table header (Skills last, no extra NO column)
            html += `<tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">Date of Survey</th>
                <th rowspan="2">Printed Name<br><span style="font-weight:normal;">(Surname, Firstname, Middle Initial)</span></th>
                <th colspan="2">First Time Job Seeker</th>
                <th colspan="2">COVID-19 Displaced Workers</th>
                <th rowspan="2">House #/ Sitio/ Purok/ Street</th>
                <th rowspan="2">Date of Birth<br><span style="font-weight:normal;">(mm/dd/yyyy)</span></th>
                <th rowspan="2">Contact #<br>Cell/Tel</th>
                <th rowspan="2">Age</th>
                <th rowspan="2">Sex<br>(M/F)</th>
                <th rowspan="2">Marital Status</th>
                <th rowspan="2">Educational Attainment<br><span style="font-weight:normal;">(Please specify level and course)</span></th>
                <th rowspan="2">Employment<br>WE (Position)</th>
                <th rowspan="2"># of mos./ years</th>
                <th rowspan="2">Employment<br> SE (Livelihood/Business)</th>
                <th rowspan="2"># of mos./ years</th>
                <th rowspan="2">UE</th>
                <th rowspan="2">Skills</th>
            </tr>`;
            html += `<tr>
                <th>YES</th><th>NO</th><th>YES</th><th>NO</th>
            </tr>`;
            // Always render 15 rows: fill with data, then empty rows if needed
            let count = 0;
            if (data.success && data.data.length) {
                data.data.forEach((row, idx) => {
                    // Display survey_date if it exists and is not null
                    let surveyDateVal = (row.survey_date && row.survey_date !== '0000-00-00' && row.survey_date !== null) ? row.survey_date : '';
                    html += `<tr data-id="${row.id}">
                        <td>${idx+1}</td>
                        <td><input type="date" value="${surveyDateVal}" readonly /></td>
                        <td><input type="text" value="${row.printed_name||''}" readonly /></td>
                        <td><input type="checkbox" ${row.ftjs==='yes'?'checked':''} disabled /></td>
                        <td><input type="checkbox" ${row.ftjs==='no'?'checked':''} disabled /></td>
                        <td><input type="checkbox" ${row.covid==='yes'?'checked':''} disabled /></td>
                        <td><input type="checkbox" ${row.covid==='no'?'checked':''} disabled /></td>
                        <td><input type="text" value="${row.address||''}" readonly /></td>
                        <td><input type="text" value="${row.dob||''}" readonly /></td>
                        <td><input type="text" value="${row.contact||''}" readonly /></td>
                        <td><input type="text" value="${row.age||''}" readonly /></td>
                        <td><input type="text" value="${row.sex||''}" readonly /></td>
                        <td><input type="text" value="${row.marital||''}" readonly /></td>
                        <td><input type="text" value="${row.education||''}" readonly /></td>
                        <td><input type="text" value="${row.we_position||''}" readonly /></td>
                        <td><input type="text" value="${row.we_months||''}" readonly /></td>
                        <td><input type="text" value="${row.se_business||''}" readonly /></td>
                        <td><input type="text" value="${row.se_months||''}" readonly /></td>
                        <td><input type="checkbox" ${row.ue==='yes'?'checked':''} disabled /></td>
                        <td><input type="text" value="${row.skills||''}" readonly /></td>
                    </tr>`;
                    count++;
                });
            }
            for (let i = count + 1; i <= 15; i++) {
                html += `<tr><td>${i}</td>
                    <td><input type="date" readonly class="clean-date-input" /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="checkbox" disabled /></td>
                    <td><input type="checkbox" disabled /></td>
                    <td><input type="checkbox" disabled /></td>
                    <td><input type="checkbox" disabled /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="text" readonly /></td>
                    <td><input type="checkbox" disabled /></td>
                    <td><input type="text" readonly /></td>
                </tr>`;
            }
            // Store count for quota check
            window.currentBarangayMonthCount = count;
            barangayTable.innerHTML = html;
            // Enforce readOnly/disabled on all inputs after rendering
            barangayTable.querySelectorAll('input[type="text"]').forEach(inp => inp.readOnly = true);
            barangayTable.querySelectorAll('input[type="checkbox"]').forEach(inp => inp.disabled = true);
            barangayTable.querySelectorAll('input[type="date"]').forEach(inp => inp.readOnly = true);
        });
    // Always reset edit mode and button
    editMode = false;
    document.getElementById('editModalBtn').textContent = 'Edit';
    // Remove editable state on all inputs
    if (barangayTable) {
        barangayTable.querySelectorAll('input[type="text"]').forEach(inp => inp.readOnly = true);
        barangayTable.querySelectorAll('input[type="checkbox"]').forEach(inp => inp.disabled = true);
        barangayTable.querySelectorAll('input[type="date"]').forEach(inp => inp.readOnly = true);
    }
}

// Auto-calculate age from Date of Birth
document.querySelector('#addSkillForm input[name="dob"]').addEventListener('change', function() {
    const dobInput = this;
    const ageInput = document.getElementById('addAgeInput');
    if (!dobInput.value || !ageInput) return;
    const dob = new Date(dobInput.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    ageInput.value = age >= 0 && age <= 120 ? age : '';
});

// Handle education dropdown change
document.getElementById('educationSelect').addEventListener('change', function() {
    const otherInput = document.getElementById('educationOther');
    if (this.value === 'Others') {
        otherInput.style.display = 'block';
        otherInput.required = true;
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
});

// Add Skill Form submit (AJAX)
document.getElementById('addSkillForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    // Convert UE checkbox to yes/no
    formData.set('ue', formData.get('ue') ? 'yes' : '');
    
    // Handle education field - if "Others" is selected, use the other input value
    const educationSelect = document.getElementById('educationSelect');
    const educationOther = document.getElementById('educationOther');
    if (educationSelect.value === 'Others' && educationOther.value.trim()) {
        formData.set('education', educationOther.value.trim());
    }
    
    const data = Object.fromEntries(formData.entries());
    
    // Client-side validation: required fields
    const surveyDate = (data.survey_date || '').trim();
    const printedName = (data.printed_name || '').trim();
    const dob = (data.dob || '').trim();
    const sex = (data.sex || '').trim();
    const marital = (data.marital || '').trim();
    const contact = (data.contact || '').trim();
    const address = (data.address || '').trim();
    const education = (data.education || '').trim();
    const ftjs = (data.ftjs || '').trim();
    const covid = (data.covid || '').trim();
    const skills = (data.skills || '').trim();
    
    if (!surveyDate) {
        Swal.fire({ title: 'Validation Error', text: 'Please enter the Date of Survey.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!printedName) {
        Swal.fire({ title: 'Validation Error', text: 'Please enter the Full Name.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!dob) {
        Swal.fire({ title: 'Validation Error', text: 'Please select the Date of Birth.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!sex) {
        Swal.fire({ title: 'Validation Error', text: 'Please select Sex.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!marital) {
        Swal.fire({ title: 'Validation Error', text: 'Please select Marital Status.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!contact) {
        Swal.fire({ title: 'Validation Error', text: 'Please enter Contact Number.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!address) {
        Swal.fire({ title: 'Validation Error', text: 'Please enter Address.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!education) {
        Swal.fire({ title: 'Validation Error', text: 'Please select Educational Attainment.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!ftjs) {
        Swal.fire({ title: 'Validation Error', text: 'Please select First-Time Jobseeker (Yes/No).', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    if (!covid) {
        Swal.fire({ title: 'Validation Error', text: 'Please select COVID-19 Displaced Worker (Yes/No).', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    const skillsList = skills.split(',').map(s => s.trim()).filter(s => s);
    if (skillsList.length === 0) {
        Swal.fire({ title: 'Validation Error', text: 'Please enter at least one skill.', icon: 'warning', confirmButtonColor: '#233a8b', customClass: { popup: 'swal-high-zindex' } });
        return;
    }
    
    // Check quota: only 15 per barangay per month
    if (window.currentBarangayMonthCount >= 15) {
        Swal.fire({
            title: 'Quota Reached!',
            text: 'Quota of this month has been reached for this barangay.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800',
            customClass: {
                popup: 'swal-high-zindex'
            }
        });
        return;
    }
    // Show loading state
    Swal.fire({
        title: 'Adding Entry...',
        text: 'Please wait while we save your data.',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: {
            popup: 'swal-high-zindex'
        },
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('skill_registry.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(data)
    })
    .then(r=>r.json())
    .then(resp=>{
        if (resp.success) {
            // Clear all form fields after successful add
            form.reset();
            document.getElementById('educationOther').style.display = 'none';
            document.getElementById('addAgeInput').value = '';
            // Set city and barangay fields back to fixed values
            addCityInput.value = 'Norzagaray';
            addBarangayInput.value = currentBarangay;
            fetchBarangayTable(currentBarangay);
            
            // Close modal immediately
            addModal.style.display = 'none';
            
            // Show success SweetAlert
            Swal.fire({
                title: 'Success!',
                text: 'New skill registry entry has been added successfully.',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#233a8b',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: true,
                customClass: {
                    popup: 'swal-high-zindex'
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: resp.msg || 'Failed to save the entry. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545',
                customClass: {
                    popup: 'swal-high-zindex'
                }
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Network Error!',
            text: 'Unable to connect to the server. Please check your connection and try again.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545',
            customClass: {
                popup: 'swal-high-zindex'
            }
        });
    });
});

document.getElementById('editModalBtn').addEventListener('click', function() {
    if (!barangayTable) barangayTable = document.querySelector('.barangay-table-form');
    editMode = !editMode;
    // Toggle all inputs except No. column
    barangayTable.querySelectorAll('tr[data-id]').forEach(tr => {
        let inputs = tr.querySelectorAll('input');
        inputs.forEach((inp, idx) => {
            if (idx === 0) return; // skip No.
            if (inp.type === 'checkbox') {
                inp.disabled = !editMode ? true : false;
            } else {
                inp.readOnly = !editMode ? true : false;
            }
        });
    });
    if (editMode) {
        this.textContent = 'Save';
    } else {
        // Save all edited rows
        let updatePromises = [];
        barangayTable.querySelectorAll('tr[data-id]').forEach(tr => {
            const id = tr.getAttribute('data-id');
            const tds = tr.querySelectorAll('td');
            let surveyDateInput = tds[1].querySelector('input');
            let surveyDateVal = surveyDateInput.value;
            if (!surveyDateVal) {
                surveyDateVal = surveyDateInput.getAttribute('data-prev') || '';
            } else {
                surveyDateInput.setAttribute('data-prev', surveyDateVal);
            }
            const data = {
                id,
                survey_date: surveyDateVal,
                printed_name: tds[2].querySelector('input').value,
                ftjs: tds[3].querySelector('input').checked ? 'yes' : (tds[4].querySelector('input').checked ? 'no' : ''),
                covid: tds[5].querySelector('input').checked ? 'yes' : (tds[6].querySelector('input').checked ? 'no' : ''),
                address: tds[7].querySelector('input').value,
                dob: tds[8].querySelector('input').value,
                contact: tds[9].querySelector('input').value,
                age: tds[10].querySelector('input').value,
                sex: tds[11].querySelector('input').value,
                marital: tds[12].querySelector('input').value,
                education: tds[13].querySelector('input').value,
                we_position: tds[14].querySelector('input').value,
                we_months: tds[15].querySelector('input').value,
                se_business: tds[16].querySelector('input').value,
                se_months: tds[17].querySelector('input').value,
                ue: tds[18].querySelector('input').checked ? 'yes' : '',
                skills: tds[19].querySelector('input').value
            };
            updatePromises.push(
                fetch('skill_registry.php', {
                    method: 'PUT',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(data)
                })
                .then(r=>r.json())
                .then(resp=>{
                    if (!resp.success) alert('Failed to update row');
                })
            );
        });
        Promise.all(updatePromises).then(() => {
            this.textContent = 'Edit';
            fetchBarangayTable(currentBarangay);
        });
    }
});

document.querySelectorAll('.logout').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('logoutModal').style.display = 'flex';
  });
});

// Logout modal functionality - wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('confirmLogoutBtn').onclick = function() {
        // Show loading state
        const confirmBtn = document.getElementById('confirmLogoutBtn');
        const cancelBtn = document.getElementById('cancelLogoutBtn');
        const originalText = confirmBtn.textContent;
        
        // Disable buttons and show loading
        confirmBtn.disabled = true;
        cancelBtn.disabled = true;
        confirmBtn.innerHTML = '<div style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px;"></div>Logging out...';
        
        // Add spinner animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        
        // Small delay to show loading state, then redirect
        setTimeout(() => {
            window.location.href = 'logout.php';
        }, 1000);
    };

    document.getElementById('cancelLogoutBtn').onclick = function() {
        document.getElementById('logoutModal').style.display = 'none';
    };

    // Close modal on outside click
    window.onclick = function(e) {
        if (e.target === document.getElementById('logoutModal')) {
            document.getElementById('logoutModal').style.display = 'none';
        }
    };
});
</script>

        <!-- Logout Modal -->
        <div id="logoutModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;inset:0;width:100%;height:100%;min-height:100vh;min-height:100dvh;max-height:100dvh;box-sizing:border-box;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
            <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(25,118,210,0.18);padding:32px 28px 24px 28px;max-width:400px;width:100%;margin:0 auto;text-align:center;">
                <div style="font-size:3rem;margin-bottom:16px;"></div>
                <h3 style="margin-top:0;color:#233a8b;font-size:1.3rem;font-weight:bold;margin-bottom:12px;">Confirm Logout</h3>
                <p style="color:#666;margin-bottom:24px;font-size:1rem;">Are you sure you want to logout from your account?</p>
                <div style="display:flex;gap:12px;justify-content:center;">
                    <button id="confirmLogoutBtn" style="background:#f44336;color:#fff;border:none;border-radius:8px;padding:12px 24px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.2s ease;">Yes, Logout</button>
                    <button id="cancelLogoutBtn" style="background:#bdbdbd;color:#1a3876;border:none;border-radius:8px;padding:12px 24px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.2s ease;">Cancel</button>
                </div>
            </div>
        </div>
</body>
</html>
</html>