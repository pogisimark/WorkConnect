<?php
include 'session_protect.php';
require_once __DIR__ . '/follow_up_pending_badge.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';
require_once __DIR__ . '/jobseeker_pending_badge.php';
require_once __DIR__ . '/db.php';
$follow_up_pending_count = fu_get_pending_follow_up_count($conn);
$acfu_unread_count = acfu_get_unread_response_count($conn);
$pending_jobseekers_count = js_get_pending_jobseekers_count($conn);
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
    <title>WorkConnect BTEC Monthly Report</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fafafa;
            min-height: 100vh;
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
            min-height: calc(100vh - 64px);
            padding-top: 64px; /* offset for fixed header */
        }
        .sidebar {
            background: #e3eaff;
            width: 240px;
            height: calc(100vh - 64px);
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
            min-height: calc(100vh - 64px);
            overflow-y: auto;
            box-sizing: border-box;
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
            
            .info-row input {
                min-width: 80px;
                max-width: 120px;
                width: auto;
                box-sizing: border-box;
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
                height: calc(100vh - 56px) !important;
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
            
            .info-row input {
                min-width: 60px;
                max-width: 100px;
                width: auto;
                font-size: 0.9rem;
                padding: 6px 8px;
                box-sizing: border-box;
            }
            
            .header div {
                font-size: 0.8rem;
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
            }
            .main-content {
                margin-left: 0;
                padding: 16px;
                height: auto;
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
        
        /* BTEC Report Styles */
        .report-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .report-header {
            background: linear-gradient(135deg, #233a8b 0%, #1e2f73 100%);
            color: white;
            padding: 24px 32px;
            text-align: center;
        }
        
        .report-header h1 {
            margin: 0 0 20px 0;
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .report-info {
            display: flex;
            gap: 32px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .info-row label {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .info-row input {
            padding: 8px 12px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 1rem;
            min-width: 120px;
            max-width: 150px;
        }
        
        .info-row input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .info-row input:focus {
            outline: none;
            border-color: rgba(255,255,255,0.6);
            background: rgba(255,255,255,0.2);
        }
        
        .table-container {
            overflow-x: auto;
            padding: 24px;
        }
        
        .btec-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .btec-table th,
        .btec-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        
        .btec-table th {
            background: #233a8b;
            color: white;
            font-weight: bold;
            font-size: 0.85rem;
        }
        
        .activities-col {
            width: 25%;
            min-width: 200px;
            text-align: left !important;
        }
        
        .programs-col {
            width: 20%;
            min-width: 150px;
            text-align: left !important;
            font-size: 0.8rem;
        }
        
        .month-header {
            background: #1e2f73 !important;
            font-size: 0.8rem;
        }
        
        .data-header {
            background: #233a8b !important;
            font-size: 0.8rem;
        }
        
        .section-header td {
            background: #f8f9fa !important;
            color: #233a8b;
            font-weight: bold;
            text-align: left !important;
            padding: 12px 8px;
        }
        
        .subsection-header td {
            background: #e9ecef !important;
            color: #495057;
            font-weight: 600;
            text-align: left !important;
            padding: 10px 8px;
            font-size: 0.85rem;
        }
        
        .activity-name {
            text-align: left !important;
            font-weight: 500;
            background: #f8f9fa;
            padding: 10px 8px;
            font-size: 0.85rem;
        }
        
        .programs-cell {
            text-align: left !important;
            font-size: 0.75rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 8px;
            line-height: 1.3;
        }
        
        .data-input {
            width: 90%;
            border: 1px solid #ddd;
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            text-align: center;
            background: white;
        }
        
        .data-input:focus {
            outline: none;
            border-color: #233a8b;
            box-shadow: 0 0 0 2px rgba(35,58,139,0.2);
        }
        
        .month-input {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            width: 80px;
        }
        
        .month-input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .month-select {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            width: 100px;
            cursor: pointer;
        }
        
        .month-select option {
            background: #233a8b;
            color: white;
        }
        
        .month-select option:disabled {
            background: #f5f5f5 !important;
            color: #999999 !important;
            cursor: not-allowed;
        }
        
        .barangay-select {
            padding: 8px 12px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 1rem;
            min-width: 200px;
            cursor: pointer;
        }
        
        .barangay-select option {
            background: #233a8b;
            color: white;
        }
        
        .barangay-select:focus {
            outline: none;
            border-color: rgba(255,255,255,0.6);
            background: rgba(255,255,255,0.2);
        }
        
        .form-actions {
            padding: 24px 32px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #233a8b;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e2f73;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(35,58,139,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108,117,125,0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.3);
        }
        
        /* Responsive Design for Table */
        @media (max-width: 1200px) {
            .table-container {
                padding: 16px;
            }
            
            .btec-table {
                font-size: 0.8rem;
            }
            
            .activities-col {
                min-width: 180px;
            }
            
            .programs-col {
                min-width: 120px;
            }
        }
        
        @media (max-width: 768px) {
            .report-header {
                padding: 20px 16px;
            }
            
            .report-header h1 {
                font-size: 1.5rem;
            }
            
            .report-info {
                flex-direction: column;
                gap: 16px;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .info-row input {
                min-width: 100%;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            
            .table-container {
                padding: 12px;
            }
            
            .btec-table {
                font-size: 0.75rem;
            }
            
            .activities-col {
                min-width: 150px;
            }
            
            .programs-col {
                min-width: 100px;
                font-size: 0.7rem;
            }
            
            .data-input {
                padding: 4px 6px;
                font-size: 0.75rem;
            }
            
            .form-actions {
                padding: 16px;
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                padding: 14px 24px;
            }
        }
        
        @media (max-width: 480px) {
            .report-header {
                padding: 16px 12px;
            }
            
            .report-header h1 {
                font-size: 1.3rem;
            }
            
            .table-container {
                padding: 8px;
            }
            
            .btec-table {
                font-size: 0.7rem;
            }
            
            .activities-col {
                min-width: 120px;
            }
            
            .programs-col {
                min-width: 80px;
                font-size: 0.65rem;
            }
            
            .data-input {
                padding: 3px 4px;
                font-size: 0.7rem;
            }
            
            .form-actions {
                padding: 12px;
            }
        }
        
        
        /* Merged Table SRS Section Styling */
        .srs-total-input {
            border: 1px solid #ddd;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            background: white;
            text-align: center;
        }
        
        .srs-total-input:focus {
            outline: none;
            border-color: #233a8b;
            box-shadow: 0 0 0 2px rgba(35,58,139,0.2);
        }
        
        .srs-specify-input {
            border: 1px solid #ddd;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            background: white;
        }
        
        .srs-specify-input:focus {
            outline: none;
            border-color: #233a8b;
            box-shadow: 0 0 0 2px rgba(35,58,139,0.2);
        }
        
        .skills-examples {
            background: #e9ecef;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.8rem;
            color: #495057;
            line-height: 1.4;
        }
        
        .skills-input-container {
            margin-top: 16px;
            margin-bottom: 8px;
        }
        
        .skills-specify-input {
            width: 100%;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
        }
        
        .skills-specify-input:focus {
            outline: none;
            border-color: #233a8b;
            box-shadow: 0 0 0 2px rgba(35,58,139,0.2);
        }
        
        .filtered-skills-display {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 0.9rem;
            color: #495057;
            line-height: 1.5;
            border: 1px solid #dee2e6;
            min-height: 60px;
        }
        
        .filtered-skills-display strong {
            color: #233a8b;
        }
        
        #skills-list {
            color: #233a8b;
            font-weight: 500;
        }
        
        .skill-box {
            display: inline-block;
            background: #233a8b;
            color: white;
            padding: 8px 16px;
            margin: 4px 8px 4px 0;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 2px 6px rgba(35,58,139,0.3);
            transition: all 0.2s ease;
            white-space: nowrap;
            min-width: fit-content;
        }
        
        .skill-box:hover {
            background: #1e2f73;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(35,58,139,0.3);
        }
        
        .skill-count {
            background: rgba(255,255,255,0.25);
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .no-skills {
            color: #6c757d;
            font-style: italic;
        }
        
        /* Print Styles - 2-Page Layout */
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            body {
                font-size: 12px !important;
                line-height: 1.3 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .header, .sidebar, .form-actions {
                display: none !important;
            }
            
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            
            .report-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .report-header {
                padding: 8px 0 !important;
                background: none !important;
                color: black !important;
                text-align: left !important;
                page-break-after: avoid !important;
            }
            
            .report-header h1 {
                font-size: 18px !important;
                margin: 0 0 10px 0 !important;
                font-weight: bold !important;
            }
            
            .report-info {
                flex-direction: row !important;
                gap: 20px !important;
                margin-bottom: 8px !important;
            }
            
            .info-row {
                display: inline-block !important;
                margin: 0 !important;
            }
            
            .info-row label {
                font-size: 11px !important;
                font-weight: bold !important;
            }
            
            .info-row input, .info-row select {
                font-size: 11px !important;
                padding: 3px 5px !important;
                border: 1px solid #000 !important;
                background: white !important;
                color: black !important;
                width: 110px !important;
            }
            
            .table-container {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .btec-table {
                font-size: 10px !important;
                border-collapse: collapse !important;
                width: 100% !important;
                margin: 0 !important;
            }
            
            .btec-cell-input {
                font-size: 9px !important;
                padding: 3px 4px !important;
                border: 1px solid #333 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .btec-table th,
            .btec-table td {
                padding: 4px 5px !important;
                border: 1px solid #000 !important;
                font-size: 9px !important;
                line-height: 1.2 !important;
            }
            
            .btec-table th {
                background: #f0f0f0 !important;
                color: black !important;
                font-weight: bold !important;
                font-size: 9px !important;
            }
            
            .activities-col {
                width: 25% !important;
                min-width: 100px !important;
            }
            
            .programs-col {
                width: 20% !important;
                min-width: 80px !important;
                font-size: 7px !important;
            }
            
            .section-header td {
                background: #e0e0e0 !important;
                color: black !important;
                font-weight: bold !important;
                font-size: 10px !important;
                padding: 5px 4px !important;
            }
            
            .subsection-header td {
                background: #f5f5f5 !important;
                color: black !important;
                font-weight: bold !important;
                font-size: 9px !important;
                padding: 4px !important;
            }
            
            .activity-name {
                font-size: 9px !important;
                padding: 4px !important;
                background: #fafafa !important;
            }
            
            .programs-cell {
                font-size: 8px !important;
                padding: 4px !important;
                background: #fafafa !important;
            }
            
            /* Page 1: A. SUMMARY OF PROGRAMS/PROJECTS - Slightly Bigger */
            .section-header:first-of-type {
                page-break-before: avoid !important;
            }
            
            /* Make Page 1 content slightly bigger */
            .section-header:first-of-type td {
                font-size: 11px !important;
                padding: 6px 5px !important;
            }
            
            /* Page 1 subsection headers */
            .section-header:first-of-type ~ .subsection-header td {
                font-size: 10px !important;
                padding: 5px !important;
            }
            
            /* Page 1 activity names */
            .section-header:first-of-type ~ .activity-name {
                font-size: 10px !important;
                padding: 5px !important;
            }
            
            /* Page 1 programs cells */
            .section-header:first-of-type ~ .programs-cell {
                font-size: 9px !important;
                padding: 5px !important;
            }
            
            /* Force page break before B. SRS/PEIS section */
            .page-break-before {
                page-break-before: always !important;
            }
            
            /* Ensure proper spacing and avoid orphaned headers */
            .section-header {
                page-break-after: avoid !important;
            }
            
            .subsection-header {
                page-break-after: avoid !important;
            }
            
            .filtered-skills-display {
                background: #f8f8f8 !important;
                padding: 5px !important;
                margin: 4px 0 !important;
                font-size: 9px !important;
                border: 1px solid #ccc !important;
            }
            
            .skills-examples {
                font-size: 10px !important;
                padding: 6px 10px !important;
                margin-bottom: 5px !important;
            }
            
            .skill-box {
                font-size: 8px !important;
                padding: 3px 6px !important;
                margin: 2px 4px 2px 0 !important;
                border-radius: 12px !important;
            }
            
            .skill-count {
                font-size: 7px !important;
                padding: 2px 5px !important;
                margin-left: 5px !important;
            }
        }
        
        /* A. and B. Section Headers */
        .section-header td {
            background: #f8f9fa !important;
            color: #233a8b;
            font-weight: bold;
            text-align: left !important;
            padding: 12px 8px;
            font-size: 1rem;
        }
        
        /* Subsection Headers */
        .subsection-header td {
            background: #e9ecef !important;
            color: #495057;
            font-weight: 600;
            text-align: left !important;
            padding: 10px 8px;
            font-size: 0.85rem;
        }
        
        /* Part A — manual entry cells (not auto-filled like Part B) */
        .btec-data-cell.btec-part-a-input-wrap {
            padding: 4px 6px !important;
            vertical-align: middle;
            background: #fff !important;
        }
        .btec-cell-input {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
            padding: 6px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #233a8b;
            background: #fff;
        }
        .btec-cell-input:focus {
            outline: none;
            border-color: #233a8b;
            box-shadow: 0 0 0 2px rgba(35, 58, 139, 0.15);
        }
        
    </style>
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
            <a href="skill.php"> SKILL REGISTRY</a>
            <a href="companies_list.php"> COMPANIES</a>
            <a href="#" class="active"> BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
            <a href="analytics.php"> Analytics</a>
            <a href="announcement.php"> ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout"> Logout</a>
        </div>
        <div class="main-content">
            <div class="report-container">
                <div class="report-header">
                    <h1>BTEC MONTHLY REPORT</h1>
                    <div class="report-info">
                        <div class="info-row">
                            <label>City/Municipality:</label>
                            <input type="text" id="cityMunicipality" value="Norzagaray" readonly style="background: #e9ecef; cursor: not-allowed; color: #333;">
                        </div>
                        <div class="info-row">
                            <label>Barangay:</label>
                            <select id="barangay" class="barangay-select">
                                <option value="">Select Barangay</option>
                                <option value="Bangkal">Bangkal</option>
                                <option value="Baraka">Baraka</option>
                                <option value="Bigte">Bigte</option>
                                <option value="Bitungol">Bitungol</option>
                                <option value="Friendship Village Resources (FVR)">Friendship Village Resources (FVR)</option>
                                <option value="Matictic">Matictic</option>
                                <option value="Minuyan">Minuyan</option>
                                <option value="Partida">Partida</option>
                                <option value="Pinagtulayan">Pinagtulayan</option>
                                <option value="Poblacion">Poblacion</option>
                                <option value="San Lorenzo">San Lorenzo</option>
                                <option value="San Mateo">San Mateo</option>
                                <option value="Tigbe">Tigbe</option>
                            </select>
                        </div>
                        <div class="info-row">
                            <label>Year:</label>
                            <select id="year" class="barangay-select">
                                <option value="">Select Year</option>
                                <!-- Years will be populated by JavaScript -->
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="btec-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="activities-col">ACTIVITIES</th>
                                <th colspan="2" class="month-header">Month: <select class="month-select" id="month1">
                                    <option value="">Select Month</option>
                                    <option value="January">January</option>
                                    <option value="February">February</option>
                                    <option value="March">March</option>
                                    <option value="April">April</option>
                                    <option value="May">May</option>
                                    <option value="June">June</option>
                                    <option value="July">July</option>
                                    <option value="August">August</option>
                                    <option value="September">September</option>
                                    <option value="October">October</option>
                                    <option value="November">November</option>
                                    <option value="December">December</option>
                                </select></th>
                                <th colspan="2" class="month-header">Month: <select class="month-select" id="month2">
                                    <option value="">Select Month</option>
                                    <option value="January">January</option>
                                    <option value="February">February</option>
                                    <option value="March">March</option>
                                    <option value="April">April</option>
                                    <option value="May">May</option>
                                    <option value="June">June</option>
                                    <option value="July">July</option>
                                    <option value="August">August</option>
                                    <option value="September">September</option>
                                    <option value="October">October</option>
                                    <option value="November">November</option>
                                    <option value="December">December</option>
                                </select></th>
                                <th colspan="2" class="month-header">Month: <select class="month-select" id="month3">
                                    <option value="">Select Month</option>
                                    <option value="January">January</option>
                                    <option value="February">February</option>
                                    <option value="March">March</option>
                                    <option value="April">April</option>
                                    <option value="May">May</option>
                                    <option value="June">June</option>
                                    <option value="July">July</option>
                                    <option value="August">August</option>
                                    <option value="September">September</option>
                                    <option value="October">October</option>
                                    <option value="November">November</option>
                                    <option value="December">December</option>
                                </select></th>
                                <th rowspan="2" class="programs-col">PROGRAMS/PROJECTS</th>
                            </tr>
                            <tr>
                                <th class="data-header">Total</th>
                                <th class="data-header">Female</th>
                                <th class="data-header">Total</th>
                                <th class="data-header">Female</th>
                                <th class="data-header">Total</th>
                                <th class="data-header">Female</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- A. SUMMARY OF PROGRAMS/PROJECTS -->
                            <tr class="section-header">
                                <td colspan="8"><strong>A. SUMMARY OF PROGRAMS/PROJECTS</strong></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of applicants registered</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell">Examples: Daily Job Referral, Job Fair, LRA, SRA, SPES, WAP, WHIP, TUPAD, GIP (Please specify)</td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of applicants pre-screened</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of jobseekers referred to PESO</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of placed monitored</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Posting of Job Vacancies -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Posting of Job Vacancies</strong></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of job vacancies posted</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name">Date posted</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" placeholder="e.g. mm/dd/yyyy" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" placeholder="e.g. mm/dd/yyyy" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" placeholder="e.g. mm/dd/yyyy" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" placeholder="e.g. mm/dd/yyyy" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" placeholder="e.g. mm/dd/yyyy" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" placeholder="e.g. mm/dd/yyyy" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Skills Training Assisted -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Skills Training Assisted, specify Course:</strong></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of enrolled participants</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell">Skills Training Program</td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of participants graduated</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of employed, Please encircle: WE/SE</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Livelihood Program Beneficiaries -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Livelihood Program Beneficiaries</strong></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name">Specify:</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell">DOLE Livelihood Grants/ LGU Livelihood Projects/ DTI/ OWWA/ TESDA (TWSP, STEP)</td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of beneficiaries</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># or active/operational</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Career Guidance Seminar Monitored -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Career Guidance Seminar Monitored</strong></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of students enrolled</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell">Career Guidance Seminar</td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of students participated</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name">Name of school:</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Employment Coaching & Counseling Assisted -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Employment Coaching & Counseling Assisted</strong></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of batches</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell">PEOS, AIRTIP, LEGS</td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name"># of participants</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Other Programs Assisted -->
                            <tr>
                                <td class="activity-name">Other Programs Assisted: Passport on Wheels (POW)/ Hapinoy/ SSS</td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="btec-data-cell btec-part-a-input-wrap"><input type="text" class="btec-cell-input" autocomplete="off" /></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- B. SRS/PEIS Skills Profile Section -->
                            <tr class="section-header page-break-before">
                                <td colspan="8"><strong>B. SRS/PEIS</strong></td>
                            </tr>
                            
                            <tr>
                                <td class="activity-name">Skills Profile based on SRS # of labor force surveyed</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Age Section -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Age:</strong></td>
                            </tr>
                            <tr>
                                <td class="activity-name">15-25</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">26-35</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">36-45</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">46 & up</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Marital Status Section -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Marital Status:</strong></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Married</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Single</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Widowed</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Divorced</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Separated</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Others, Specify</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Educational Attainment Section -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Educational Attainment:</strong></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Elementary Graduate</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">High School Level</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">High School Graduate</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">College Level</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">College Graduate</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Elem Level</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">Others, Specify: </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Employment Status Section -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Employment Status:</strong></td>
                            </tr>
                            <tr>
                                <td class="activity-name">No of Wage Employed (WE)</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">No. of Self-Employed (SE)</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">No. of Unemployed (UE)</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Work Experience Section -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Length of Service/Work Experience:</strong></td>
                            </tr>
                            <tr>
                                <td class="activity-name">0 month - 1 year</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">2 years - 3 years</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">4 years - 5 years</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">6 years - above</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Job Seekers Section -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>Job Seekers:</strong></td>
                            </tr>
                            <tr>
                                <td class="activity-name">No. First Time Jobseekers</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            <tr>
                                <td class="activity-name">No. Displaced Workers due to COVID-19</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="programs-cell"></td>
                            </tr>
                            
                            <!-- Skills Section -->
                            <tr class="subsection-header">
                                <td colspan="8"><strong>No. of Skills Registered/Surveyed, Please Specify:</strong></td>
                            </tr>
                            <tr>
                                <td class="activity-name" colspan="8">
                                    <div class="skills-examples">
                                        <strong>Examples:</strong> Welding, Sewing, Carpentry, Caregiving, IT, Bookkeeping, Nursing, Teaching, Computer Programming, Housekeeping, Driving, etc.
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="activity-name" colspan="8">
                                    <div class="skills-input-container">
                                        <div id="filtered-skills-display" class="filtered-skills-display">
                                            <strong>Registered Skills:</strong> <span id="skills-list">No data found</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="printReport()">Print Report</button>
                    <button type="button" class="btn btn-success" onclick="exportReport()">📊 Export to Excel</button>
                </div>
            </div>
        </div>
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
            adminSection.style.fontSize = '0.75rem';
            adminSection.style.maxWidth = '100px';
            adminSection.style.overflow = 'hidden';
            adminSection.style.textOverflow = 'ellipsis';
            
            // Update admin text to show only username
            const adminUsername = document.getElementById('adminUsername');
            if (adminUsername) {
                const currentText = adminUsername.textContent;
                if (currentText.includes('Welcome, ')) {
                    adminUsername.textContent = currentText.replace('Welcome, ', '');
                }
            }
            
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
            
            // Reset admin section
            adminSection.style.marginRight = '20px';
            adminSection.style.gap = '8px';
            adminSection.style.fontSize = '1rem';
            
            // Remove "Welcome, " text for desktop too
            const adminUsername = document.getElementById('adminUsername');
            if (adminUsername && adminUsername.textContent.includes('Welcome, ')) {
                adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
            }
            
            // Reset logo
            const logo = header.querySelector('img');
            if (logo) {
                logo.style.height = '48px';
                logo.style.marginRight = '16px';
            }
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
                adminUsername.style.fontSize = '0.75rem';
                adminUsername.style.maxWidth = '100px';
                adminUsername.style.overflow = 'hidden';
                adminUsername.style.textOverflow = 'ellipsis';
                adminUsername.style.whiteSpace = 'nowrap';
                // Remove "Welcome, " text for mobile
                if (adminUsername.textContent.includes('Welcome, ')) {
                    adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
                }
            }
            
            if (adminSection) {
                adminSection.style.marginRight = '8px';
                adminSection.style.gap = '4px';
                adminSection.style.maxWidth = '100px';
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
    
    // Remove "Welcome, " text for both mobile and desktop
    function removeWelcomeText() {
        const adminUsername = document.getElementById('adminUsername');
        if (adminUsername && adminUsername.textContent.includes('Welcome, ')) {
            adminUsername.textContent = adminUsername.textContent.replace('Welcome, ', '');
        }
    }
    
    // Apply immediately
    applyMobileStyles();
    removeWelcomeText();
    
    // Initial check
    handleMobileHeader();
    
    // Check on resize
    window.addEventListener('resize', handleMobileHeader);

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

    // Function to fetch and populate Part B data automatically
    function fetchAndPopulatePartB() {
        const barangay = document.getElementById('barangay').value;
        const year = document.getElementById('year').value;
        const monthSelects = document.querySelectorAll('.month-select');
        const months = Array.from(monthSelects).map(select => select.value).filter(month => month);
        
        if (!barangay) {
            return;
        }
        
        if (!year) {
            return;
        }
        
        if (months.length === 0) {
            return;
        }
        
        // Clear existing data first
        clearPartBData();
        
        // Collect all skills data from all months
        let allSkillsData = [];
        let completedRequests = 0;
        const totalRequests = months.length;
        
        months.forEach((month, index) => {
            fetchSkillRegistryData(barangay, month, index, (monthSkillsData) => {
                if (monthSkillsData && monthSkillsData.length > 0) {
                    allSkillsData = allSkillsData.concat(monthSkillsData);
                }
                completedRequests++;
                if (completedRequests === totalRequests) {
                    // Update skills display with all collected data
                    updateSkillsDisplay(allSkillsData);
                }
            });
        });
    }
    
    // Function to clear Part B data
    function clearPartBData() {
        const allRows = document.querySelectorAll('.btec-table tbody tr');
        let isPartB = false;
        
        allRows.forEach(row => {
            const activityCell = row.querySelector('.activity-name');
            if (activityCell) {
                const text = activityCell.textContent.trim();
                
                // Check if we're entering Part B
                if (text.includes('Skills Profile based on SRS')) {
                    isPartB = true;
                }
                
                // Check if we're leaving Part B (next section header)
                if (isPartB && text.includes('A. SUMMARY OF PROGRAMS/PROJECTS')) {
                    isPartB = false;
                }
                
                // Clear all data cells in Part B rows
                if (isPartB) {
                    const cells = row.querySelectorAll('td');
                    // Skip the first cell (activity name) and the last cell (programs cell)
                    for (let i = 1; i < cells.length - 1; i++) {
                        cells[i].textContent = '';
                        cells[i].style.fontWeight = '';
                        cells[i].style.color = '';
                    }
                }
            }
        });
        
        // Clear skills display
        const skillsListElement = document.getElementById('skills-list');
        if (skillsListElement) {
            skillsListElement.innerHTML = '<span class="no-skills">No data found</span>';
        }
    }
    
    // Function to fetch skill registry data
    function fetchSkillRegistryData(barangay, month, monthIndex, callback) {
        // Convert month name to number for precise filtering
        const monthNumbers = {
            'January': '01', 'February': '02', 'March': '03', 'April': '04',
            'May': '05', 'June': '06', 'July': '07', 'August': '08',
            'September': '09', 'October': '10', 'November': '11', 'December': '12'
        };
        
        const monthNumber = monthNumbers[month];
        const selectedYear = document.getElementById('year').value || new Date().getFullYear();
        
        const url = `skill_registry.php?barangay=${encodeURIComponent(barangay)}&month=${monthNumber}&year=${selectedYear}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    console.log(`Found ${data.data.length} records for ${barangay} - ${month} ${selectedYear}`);
                    populatePartBData(data.data, monthIndex);
                    if (callback) callback(data.data);
                } else {
                    console.log(`No data found for ${barangay} - ${month} ${selectedYear}`);
                    // Clear the specific month columns if no data found
                    clearMonthData(monthIndex);
                    if (callback) callback([]);
                }
            })
            .catch(error => {
                console.error('Error fetching skill registry data:', error);
                if (callback) callback([]);
            });
    }
    
    // Function to clear data for a specific month
    function clearMonthData(monthIndex) {
        const allRows = document.querySelectorAll('.btec-table tbody tr');
        let isPartB = false;
        
        allRows.forEach(row => {
            const activityCell = row.querySelector('.activity-name');
            if (activityCell) {
                const text = activityCell.textContent.trim();
                
                // Check if we're entering Part B
                if (text.includes('Skills Profile based on SRS')) {
                    isPartB = true;
                }
                
                // Check if we're leaving Part B
                if (isPartB && text.includes('A. SUMMARY OF PROGRAMS/PROJECTS')) {
                    isPartB = false;
                }
                
                // Clear data cells for the specific month in Part B rows
                if (isPartB) {
                    const cells = row.querySelectorAll('td');
                    const totalCellIndex = monthIndex * 2 + 1; // +1 to skip activity name cell
                    const femaleCellIndex = monthIndex * 2 + 2; // +2 to skip activity name cell
                    
                    if (cells[totalCellIndex]) {
                        cells[totalCellIndex].textContent = '';
                        cells[totalCellIndex].style.fontWeight = '';
                        cells[totalCellIndex].style.color = '';
                    }
                    if (cells[femaleCellIndex]) {
                        cells[femaleCellIndex].textContent = '';
                        cells[femaleCellIndex].style.fontWeight = '';
                        cells[femaleCellIndex].style.color = '';
                    }
                }
            }
        });
    }
    
    // Function to populate Part B with skill registry data
    function populatePartBData(skillData, monthIndex) {
        // Calculate statistics
        const stats = calculateSkillRegistryStats(skillData);
        
        // Update the main SRS row
        updateDataInput('Skills Profile based on SRS # of labor force surveyed', monthIndex, stats.total, stats.female);
        
        // Update Age groups
        updateDataInput('15-25', monthIndex, stats.ageGroups['15-25'].total, stats.ageGroups['15-25'].female);
        updateDataInput('26-35', monthIndex, stats.ageGroups['26-35'].total, stats.ageGroups['26-35'].female);
        updateDataInput('36-45', monthIndex, stats.ageGroups['36-45'].total, stats.ageGroups['36-45'].female);
        updateDataInput('46 & up', monthIndex, stats.ageGroups['46+'].total, stats.ageGroups['46+'].female);
        
        // Update Marital Status
        updateDataInput('Married', monthIndex, stats.maritalStatus.Married.total, stats.maritalStatus.Married.female);
        updateDataInput('Single', monthIndex, stats.maritalStatus.Single.total, stats.maritalStatus.Single.female);
        updateDataInput('Widowed', monthIndex, stats.maritalStatus.Widowed.total, stats.maritalStatus.Widowed.female);
        updateDataInput('Divorced', monthIndex, stats.maritalStatus.Divorced.total, stats.maritalStatus.Divorced.female);
        updateDataInput('Separated', monthIndex, stats.maritalStatus.Separated.total, stats.maritalStatus.Separated.female);
        
        // Update Educational Attainment
        updateDataInput('Elementary Graduate', monthIndex, stats.education['Elementary Graduate'].total, stats.education['Elementary Graduate'].female);
        updateDataInput('High School Level', monthIndex, stats.education['High School Level'].total, stats.education['High School Level'].female);
        updateDataInput('High School Graduate', monthIndex, stats.education['High School Graduate'].total, stats.education['High School Graduate'].female);
        updateDataInput('College Level', monthIndex, stats.education['College Level'].total, stats.education['College Level'].female);
        updateDataInput('College Graduate', monthIndex, stats.education['College Graduate'].total, stats.education['College Graduate'].female);
        updateDataInput('Elem Level', monthIndex, stats.education['Elem Level'].total, stats.education['Elem Level'].female);
        
        // Update Employment Status
        updateDataInput('No of Wage Employed (WE)', monthIndex, stats.employment.WE.total, stats.employment.WE.female);
        updateDataInput('No. of Self-Employed (SE)', monthIndex, stats.employment.SE.total, stats.employment.SE.female);
        updateDataInput('No. of Unemployed (UE)', monthIndex, stats.employment.UE.total, stats.employment.UE.female);
        
        // Update Work Experience
        updateDataInput('0 month - 1 year', monthIndex, stats.workExperience['0-1'].total, stats.workExperience['0-1'].female);
        updateDataInput('2 years - 3 years', monthIndex, stats.workExperience['2-3'].total, stats.workExperience['2-3'].female);
        updateDataInput('4 years - 5 years', monthIndex, stats.workExperience['4-5'].total, stats.workExperience['4-5'].female);
        updateDataInput('6 years - above', monthIndex, stats.workExperience['6+'].total, stats.workExperience['6+'].female);
        
        // Update Job Seekers
        updateDataInput('No. First Time Jobseekers', monthIndex, stats.jobSeekers.FTJS.total, stats.jobSeekers.FTJS.female);
        updateDataInput('No. Displaced Workers due to COVID-19', monthIndex, stats.jobSeekers.COVID.total, stats.jobSeekers.COVID.female);
    }
    
    // Function to calculate statistics from skill registry data
    function calculateSkillRegistryStats(data) {
        const stats = {
            total: data.length,
            female: data.filter(person => person.sex === 'F').length,
            ageGroups: {
                '15-25': { total: 0, female: 0 },
                '26-35': { total: 0, female: 0 },
                '36-45': { total: 0, female: 0 },
                '46+': { total: 0, female: 0 }
            },
            maritalStatus: {
                Married: { total: 0, female: 0 },
                Single: { total: 0, female: 0 },
                Widowed: { total: 0, female: 0 },
                Divorced: { total: 0, female: 0 },
                Separated: { total: 0, female: 0 }
            },
            education: {
                'Elementary Graduate': { total: 0, female: 0 },
                'High School Level': { total: 0, female: 0 },
                'High School Graduate': { total: 0, female: 0 },
                'College Level': { total: 0, female: 0 },
                'College Graduate': { total: 0, female: 0 },
                'Elem Level': { total: 0, female: 0 }
            },
            employment: {
                WE: { total: 0, female: 0 },
                SE: { total: 0, female: 0 },
                UE: { total: 0, female: 0 }
            },
            workExperience: {
                '0-1': { total: 0, female: 0 },
                '2-3': { total: 0, female: 0 },
                '4-5': { total: 0, female: 0 },
                '6+': { total: 0, female: 0 }
            },
            jobSeekers: {
                FTJS: { total: 0, female: 0 },
                COVID: { total: 0, female: 0 }
            }
        };
        
        data.forEach(person => {
            const isFemale = person.sex === 'F';
            const age = parseInt(person.age) || 0;
            
            // Age groups
            if (age >= 15 && age <= 25) {
                stats.ageGroups['15-25'].total++;
                if (isFemale) stats.ageGroups['15-25'].female++;
            } else if (age >= 26 && age <= 35) {
                stats.ageGroups['26-35'].total++;
                if (isFemale) stats.ageGroups['26-35'].female++;
            } else if (age >= 36 && age <= 45) {
                stats.ageGroups['36-45'].total++;
                if (isFemale) stats.ageGroups['36-45'].female++;
            } else if (age >= 46) {
                stats.ageGroups['46+'].total++;
                if (isFemale) stats.ageGroups['46+'].female++;
            }
            
            // Marital Status
            if (stats.maritalStatus[person.marital]) {
                stats.maritalStatus[person.marital].total++;
                if (isFemale) stats.maritalStatus[person.marital].female++;
            }
            
            // Education
            if (stats.education[person.education]) {
                stats.education[person.education].total++;
                if (isFemale) stats.education[person.education].female++;
            }
            
            // Employment Status
            if (person.we_position && person.we_position.trim()) {
                stats.employment.WE.total++;
                if (isFemale) stats.employment.WE.female++;
            }
            if (person.se_business && person.se_business.trim()) {
                stats.employment.SE.total++;
                if (isFemale) stats.employment.SE.female++;
            }
            if (person.ue === 'yes') {
                stats.employment.UE.total++;
                if (isFemale) stats.employment.UE.female++;
            }
            
            // Work Experience (simplified mapping)
            const weMonths = person.we_months || '';
            if (weMonths.includes('0 month') || weMonths.includes('1 year')) {
                stats.workExperience['0-1'].total++;
                if (isFemale) stats.workExperience['0-1'].female++;
            } else if (weMonths.includes('2 years') || weMonths.includes('3 years')) {
                stats.workExperience['2-3'].total++;
                if (isFemale) stats.workExperience['2-3'].female++;
            } else if (weMonths.includes('4 years') || weMonths.includes('5 years')) {
                stats.workExperience['4-5'].total++;
                if (isFemale) stats.workExperience['4-5'].female++;
            } else if (weMonths.includes('6 years') || weMonths.includes('above')) {
                stats.workExperience['6+'].total++;
                if (isFemale) stats.workExperience['6+'].female++;
            }
            
            // Job Seekers
            if (person.ftjs === 'yes') {
                stats.jobSeekers.FTJS.total++;
                if (isFemale) stats.jobSeekers.FTJS.female++;
            }
            if (person.covid === 'yes') {
                stats.jobSeekers.COVID.total++;
                if (isFemale) stats.jobSeekers.COVID.female++;
            }
        });
        
        return stats;
    }
    
    // Function to update data in table cells
    function updateDataInput(activityName, monthIndex, total, female) {
        const rows = document.querySelectorAll('.btec-table tbody tr');
        rows.forEach(row => {
            const activityCell = row.querySelector('.activity-name');
            if (activityCell && activityCell.textContent.trim() === activityName) {
                const cells = row.querySelectorAll('td');
                // Skip the first cell (activity name) and the last cell (programs cell)
                // Update the data cells for the specific month
                const totalCellIndex = monthIndex * 2 + 1; // +1 to skip activity name cell
                const femaleCellIndex = monthIndex * 2 + 2; // +2 to skip activity name cell
                
                if (cells[totalCellIndex]) {
                    cells[totalCellIndex].textContent = total;
                    cells[totalCellIndex].style.fontWeight = 'bold';
                    cells[totalCellIndex].style.color = '#233a8b';
                }
                if (cells[femaleCellIndex]) {
                    cells[femaleCellIndex].textContent = female;
                    cells[femaleCellIndex].style.fontWeight = 'bold';
                    cells[femaleCellIndex].style.color = '#233a8b';
                }
            }
        });
    }
    
    // Function to update skills display with filtered skills
    function updateSkillsDisplay(skillData) {
        const skillsListElement = document.getElementById('skills-list');
        if (!skillsListElement) return;
        
        // Collect all skills from the data
        const allSkills = [];
        skillData.forEach(person => {
            if (person.skills && person.skills.trim()) {
                // Split skills by common separators and clean them
                const personSkills = person.skills
                    .split(/[,;|&]/) // Split by comma, semicolon, pipe, or ampersand
                    .map(skill => skill.trim())
                    .filter(skill => skill.length > 0);
                allSkills.push(...personSkills);
            }
        });
        
        // Count skills and remove duplicates
        const skillCounts = {};
        allSkills.forEach(skill => {
            const normalizedSkill = skill.toLowerCase().trim();
            if (normalizedSkill) {
                skillCounts[normalizedSkill] = (skillCounts[normalizedSkill] || 0) + 1;
            }
        });
        
        // Clear existing content
        skillsListElement.innerHTML = '';
        
        // Create display
        if (Object.keys(skillCounts).length === 0) {
            skillsListElement.innerHTML = '<span class="no-skills">No skills found</span>';
            return;
        }
        
        // Sort skills alphabetically and create skill boxes
        const sortedSkills = Object.keys(skillCounts).sort();
        
        // Clear any existing content first
        skillsListElement.innerHTML = '';
        
        // Add a container for better layout
        const skillsContainer = document.createElement('div');
        skillsContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;';
        
        sortedSkills.forEach(skill => {
            const count = skillCounts[skill];
            const capitalizedSkill = skill.charAt(0).toUpperCase() + skill.slice(1);
            
            // Create skill box element
            const skillBox = document.createElement('span');
            skillBox.className = 'skill-box';
            skillBox.innerHTML = `${capitalizedSkill}<span class="skill-count">${count}</span>`;
            
            skillsContainer.appendChild(skillBox);
        });
        
        skillsListElement.appendChild(skillsContainer);
    }
    
    // Add event listeners for barangay, year, and month changes
    document.getElementById('barangay').addEventListener('change', function() {
        // Clear all Part B data when barangay changes
        clearPartBData();
        // Then fetch new data
        fetchAndPopulatePartB();
    });
    
    document.getElementById('year').addEventListener('change', function() {
        // Clear all Part B data when year changes
        clearPartBData();
        // Then fetch new data
        fetchAndPopulatePartB();
    });
    
    // Function to update month dropdown options to prevent duplicates
    function updateMonthOptions() {
        const month1 = document.getElementById('month1');
        const month2 = document.getElementById('month2');
        const month3 = document.getElementById('month3');
        
        const allMonths = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
        
        // Get currently selected values
        const selected1 = month1.value;
        const selected2 = month2.value;
        const selected3 = month3.value;
        
        // Update month2 options
        updateDropdownOptions(month2, allMonths, [selected1, selected3]);
        
        // Update month3 options
        updateDropdownOptions(month3, allMonths, [selected1, selected2]);
        
        // Update month1 options
        updateDropdownOptions(month1, allMonths, [selected2, selected3]);
    }
    
    // Function to update dropdown options
    function updateDropdownOptions(dropdown, allMonths, excludeMonths) {
        const currentValue = dropdown.value;
        const options = dropdown.querySelectorAll('option');
        
        // Reset all options to enabled
        options.forEach(option => {
            if (option.value !== '') {
                option.disabled = false;
                option.style.color = '';
                option.style.backgroundColor = '';
            }
        });
        
        // Disable excluded months (show as gray)
        excludeMonths.forEach(excludedMonth => {
            if (excludedMonth && excludedMonth !== '') {
                const option = dropdown.querySelector(`option[value="${excludedMonth}"]`);
                if (option) {
                    option.disabled = true;
                    option.style.color = '#999999';
                    option.style.backgroundColor = '#f5f5f5';
                }
            }
        });
        
        // If current selection is now disabled, clear it
        if (currentValue && excludeMonths.includes(currentValue)) {
            dropdown.value = '';
        }
    }
    
    // Add event listeners for month dropdowns
    document.getElementById('month1').addEventListener('change', function() {
        updateMonthOptions();
        clearPartBData();
        fetchAndPopulatePartB();
    });
    
    document.getElementById('month2').addEventListener('change', function() {
        updateMonthOptions();
        clearPartBData();
        fetchAndPopulatePartB();
    });
    
    document.getElementById('month3').addEventListener('change', function() {
        updateMonthOptions();
        clearPartBData();
        fetchAndPopulatePartB();
    });
    
    // Function to populate year dropdown dynamically
    function populateYearDropdown() {
        const yearSelect = document.getElementById('year');
        
        // Check if element exists
        if (!yearSelect) {
            console.log('Year select element not found, retrying...');
            setTimeout(populateYearDropdown, 200);
            return;
        }
        
        // Clear all existing options including the default "Select Year"
        yearSelect.innerHTML = '';
        
        // Use current year as the default, list down to 2020
        const currentYear = new Date().getFullYear();
        const earliestYear = 2020;
        
        for (let year = currentYear; year >= earliestYear; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            
            // Set current year as selected by default
            if (year === currentYear) {
                option.selected = true;
            }
            
            yearSelect.appendChild(option);
        }
        
        console.log('Year dropdown populated successfully');
    }
    
    // Initialize month options and year dropdown on page load
    document.addEventListener('DOMContentLoaded', function() {
        populateYearDropdown();
        updateMonthOptions();
    });
    
    // Also try immediately and with a delay as fallback
    setTimeout(populateYearDropdown, 50);
    setTimeout(populateYearDropdown, 500);

    // BTEC Report Functions
    
    function printReport() {
        // Hide buttons and other non-printable elements
        const formActions = document.querySelector('.form-actions');
        const sidebar = document.querySelector('.sidebar');
        const header = document.querySelector('.header');
        const reportHeader = document.querySelector('.report-header');
        
        if (formActions) formActions.style.display = 'none';
        if (sidebar) sidebar.style.display = 'none';
        if (header) header.style.display = 'none';
        if (reportHeader) reportHeader.style.display = 'none'; // Hide the big header
        
        // Add compact print class to body
        document.body.classList.add('print-mode');
        
        // Ensure page break is applied to B. SRS/PEIS section
        const srsSection = document.querySelector('.page-break-before');
        if (srsSection) {
            srsSection.style.pageBreakBefore = 'always';
        }
        
        // Print the page
        window.print();
        
        // Restore elements
        if (formActions) formActions.style.display = 'flex';
        if (sidebar) sidebar.style.display = 'flex';
        if (header) header.style.display = 'flex';
        if (reportHeader) reportHeader.style.display = 'block';
        
        // Remove print class
        document.body.classList.remove('print-mode');
    }
    
    function exportReport() {
        // Enhanced Excel export with proper formatting
        const table = document.querySelector('.btec-table');
        const year = document.getElementById('year').value || 'Unknown';
        const month1 = document.getElementById('month1').value;
        const month2 = document.getElementById('month2').value;
        const month3 = document.getElementById('month3').value;
        const barangay = document.getElementById('barangay').value || 'Unknown';
        const cityMunicipality = document.getElementById('cityMunicipality').value || 'Norzagaray';
        
        // Build months string
        const selectedMonths = [month1, month2, month3].filter(month => month && month !== '');
        let monthsString = '';
        if (selectedMonths.length === 0) {
            monthsString = 'No_Months';
        } else if (selectedMonths.length === 1) {
            monthsString = selectedMonths[0];
        } else if (selectedMonths.length === 2) {
            monthsString = `${selectedMonths[0]}_${selectedMonths[1]}`;
        } else {
            monthsString = `${selectedMonths[0]}_${selectedMonths[1]}_${selectedMonths[2]}`;
        }
        
        // Create filename: BTEC_Report_Year_Months_Barangay.xlsx
        const filename = `BTEC_Report_${year}_${monthsString}_${barangay}.xlsx`;
        
        // Prepare data for export
        const exportData = prepareExportData();
        
        // Create Excel file with proper formatting
        createFormattedExcel(exportData, filename, year, selectedMonths, barangay, cityMunicipality);
    }
    
    function prepareExportData() {
        const table = document.querySelector('.btec-table');
        const rows = table.querySelectorAll('tr');
        const data = [];
        
        rows.forEach((row, rowIndex) => {
            const cells = row.querySelectorAll('th, td');
            const rowData = [];
            
            cells.forEach((cell, cellIndex) => {
                const select = cell.querySelector('select');
                let cellValue = '';
                
                if (select) {
                    cellValue = select.value || '';
                } else {
                    cellValue = cell.textContent.trim();
                }
                
                // Clean up cell value and handle special cases
                cellValue = cellValue.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
                
                // Handle empty cells and special characters
                if (cellValue === '' || cellValue === 'undefined' || cellValue === 'null') {
                    cellValue = '';
                }
                
                // Special handling for skills display - format it properly
                if (cellValue.includes('Registered Skills:') && cellValue !== 'Registered Skills: No data found') {
                    cellValue = formatSkillsForExcel(cellValue);
                }
                
                // Escape commas and quotes for proper CSV handling
                if (cellValue.includes(',') || cellValue.includes('"')) {
                    cellValue = '"' + cellValue.replace(/"/g, '""') + '"';
                }
                
                rowData.push(cellValue);
            });
            
            data.push(rowData);
        });
        
        return data;
    }
    
    function formatSkillsForExcel(skillsText) {
        // Extract skills from the text (e.g., "Registered Skills: Carpenting1Gaming1Gardening1Masonry3Singing1Welding1")
        const skillsMatch = skillsText.match(/Registered Skills:\s*(.+)/);
        if (!skillsMatch) return skillsText;
        
        const skillsString = skillsMatch[1];
        
        // Parse the skills string to extract individual skills with counts
        const skills = [];
        let currentSkill = '';
        let currentCount = '';
        let i = 0;
        
        while (i < skillsString.length) {
            const char = skillsString[i];
            
            if (isNaN(char)) {
                // It's a letter, add to current skill
                currentSkill += char;
            } else {
                // It's a number, add to current count
                currentCount += char;
                
                // Check if next character is also a number or if we're at the end
                if (i === skillsString.length - 1 || isNaN(skillsString[i + 1])) {
                    // We've reached the end of a skill-count pair
                    if (currentSkill && currentCount) {
                        const skillName = currentSkill.charAt(0).toUpperCase() + currentSkill.slice(1);
                        const count = parseInt(currentCount) || 1;
                        skills.push(`${skillName} (${count})`);
                    }
                    currentSkill = '';
                    currentCount = '';
                }
            }
            i++;
        }
        
        // Format the skills nicely
        if (skills.length === 0) {
            return 'Registered Skills: No skills found';
        }
        
        return `Registered Skills: ${skills.join(', ')}`;
    }
    
    function createFormattedExcel(data, filename, year, months, barangay, cityMunicipality) {
        // Create a new workbook
        const wb = XLSX.utils.book_new();
        
        // Prepare worksheet data with proper formatting
        const wsData = [];
        
        // Add report header
        wsData.push(['BTEC MONTHLY REPORT']);
        wsData.push([]); // Empty row
        wsData.push(['City/Municipality:', cityMunicipality]);
        wsData.push(['Barangay:', barangay]);
        wsData.push(['Year:', year]);
        wsData.push(['Months:', months.join(', ')]);
        wsData.push(['Generated on:', new Date().toLocaleDateString()]);
        wsData.push([]); // Empty row
        
        // Create proper table structure matching the web interface
        
        // Add table headers with proper structure
        const headerRow1 = ['ACTIVITIES'];
        const headerRow2 = ['']; // Empty for activities column
        
        // Add month headers with proper structure
        months.forEach(month => {
            headerRow1.push(`Month: ${month}`);
            headerRow1.push(''); // Empty cell for the second column of each month
            headerRow2.push('Total');
            headerRow2.push('Female');
        });
        
        // If no months selected, add default structure
        if (months.length === 0) {
            headerRow1.push('Month: [Select Month]');
            headerRow1.push('');
            headerRow2.push('Total');
            headerRow2.push('Female');
        }
        
        // Add programs/projects header
        headerRow1.push('PROGRAMS/PROJECTS');
        headerRow2.push(''); // Empty for programs column
        
        wsData.push(headerRow1);
        wsData.push(headerRow2);
        
        // Add table data with proper structure
        data.forEach((row, rowIndex) => {
            if (row.length > 0) {
                // Skip the original header rows from the web table
                if (rowIndex > 1) { // Skip the first two header rows from web table
                    wsData.push(row);
                }
            }
        });
        
        // Create worksheet
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        
        // Set column widths for better display and prevent cutoff
        const colWidths = [
            { wch: 40 }, // Activities column - wider for long text
        ];
        
        // Add widths for each month (2 columns per month)
        const monthCount = months.length > 0 ? months.length : 1; // Default to 1 if no months
        for (let i = 0; i < monthCount; i++) {
            colWidths.push({ wch: 15 }); // Total column
            colWidths.push({ wch: 15 }); // Female column
        }
        
        // Add width for programs/projects column
        colWidths.push({ wch: 50 }); // Programs/Projects column
        
        ws['!cols'] = colWidths;
        
        // Set row heights for better readability
        const rowHeights = [];
        for (let i = 0; i < wsData.length; i++) {
            if (i === 0) {
                rowHeights.push({ hpt: 30 }); // Header row
            } else if (i >= 7) { // Table data rows
                rowHeights.push({ hpt: 20 }); // Slightly taller for better readability
            } else {
                rowHeights.push({ hpt: 18 }); // Standard height
            }
        }
        ws['!rows'] = rowHeights;
        
        // Apply formatting to cells
        const range = XLSX.utils.decode_range(ws['!ref']);
        
        // Style the header row (row 0)
        for (let col = range.s.c; col <= range.e.c; col++) {
            const cellAddress = XLSX.utils.encode_cell({ r: 0, c: col });
            if (!ws[cellAddress]) ws[cellAddress] = { v: '' };
            ws[cellAddress].s = {
                font: { bold: true, size: 16, color: { rgb: "FFFFFF" } },
                fill: { fgColor: { rgb: "233A8B" } },
                alignment: { horizontal: "center", vertical: "center" },
                border: {
                    top: { style: "thin", color: { rgb: "000000" } },
                    bottom: { style: "thin", color: { rgb: "000000" } },
                    left: { style: "thin", color: { rgb: "000000" } },
                    right: { style: "thin", color: { rgb: "000000" } }
                }
            };
        }
        
        // Find table start row (after header info)
        const tableStartRow = wsData.length - data.length + 1; // After our custom headers
        
        // Style table headers and data
        for (let row = tableStartRow; row <= range.e.r; row++) {
            for (let col = range.s.c; col <= range.e.c; col++) {
                const cellAddress = XLSX.utils.encode_cell({ r: row, c: col });
                if (!ws[cellAddress]) continue;
                
                const cell = ws[cellAddress];
                const cellValue = cell.v || '';
                
                // Determine cell styling based on content
                let cellStyle = {
                    alignment: { horizontal: "left", vertical: "center", wrapText: true },
                    border: {
                        top: { style: "thin", color: { rgb: "000000" } },
                        bottom: { style: "thin", color: { rgb: "000000" } },
                        left: { style: "thin", color: { rgb: "000000" } },
                        right: { style: "thin", color: { rgb: "000000" } }
                    }
                };
                
                // Style section headers (A. SUMMARY, B. SRS/PEIS)
                if (cellValue.includes('A. SUMMARY') || cellValue.includes('B. SRS/PEIS')) {
                    cellStyle.font = { bold: true, size: 12, color: { rgb: "233A8B" } };
                    cellStyle.fill = { fgColor: { rgb: "F8F9FA" } };
                    cellStyle.alignment.horizontal = "left";
                }
                // Style subsection headers
                else if (cellValue.includes('Posting of Job Vacancies') || 
                         cellValue.includes('Skills Training Assisted') ||
                         cellValue.includes('Livelihood Program Beneficiaries') ||
                         cellValue.includes('Career Guidance Seminar') ||
                         cellValue.includes('Employment Coaching') ||
                         cellValue.includes('Age:') ||
                         cellValue.includes('Marital Status:') ||
                         cellValue.includes('Educational Attainment:') ||
                         cellValue.includes('Employment Status:') ||
                         cellValue.includes('Length of Service') ||
                         cellValue.includes('Job Seekers:') ||
                         cellValue.includes('No. of Skills Registered')) {
                    cellStyle.font = { bold: true, size: 11, color: { rgb: "495057" } };
                    cellStyle.fill = { fgColor: { rgb: "E9ECEF" } };
                    cellStyle.alignment.horizontal = "left";
                }
                // Style table headers (Month headers, Total, Female)
                else if (row === tableStartRow || row === tableStartRow + 1) {
                    cellStyle.font = { bold: true, size: 10, color: { rgb: "FFFFFF" } };
                    cellStyle.fill = { fgColor: { rgb: "233A8B" } };
                    cellStyle.alignment.horizontal = "center";
                }
                // Style activity names
                else if (col === 0 && cellValue && !cellValue.includes('A.') && !cellValue.includes('B.')) {
                    cellStyle.font = { bold: false, size: 10 };
                    cellStyle.fill = { fgColor: { rgb: "F8F9FA" } };
                    cellStyle.alignment.horizontal = "left";
                }
                // Style data cells (numeric values) - check if it's a data column (not activities or programs)
                else if (col > 0 && col < (1 + monthCount * 2) && cellValue && !isNaN(cellValue) && cellValue !== '') {
                    cellStyle.font = { bold: true, size: 11, color: { rgb: "233A8B" } };
                    cellStyle.alignment.horizontal = "center";
                    cellStyle.fill = { fgColor: { rgb: "F0F8FF" } }; // Light blue background for data
                }
                // Style programs/projects column (last column)
                else if (col === (1 + monthCount * 2)) {
                    cellStyle.font = { size: 9, color: { rgb: "6C757D" } };
                    cellStyle.fill = { fgColor: { rgb: "F8F9FA" } };
                    cellStyle.alignment.horizontal = "left";
                }
                // Default styling
                else {
                    cellStyle.font = { size: 10 };
                    cellStyle.alignment.horizontal = "left";
                }
                
                cell.s = cellStyle;
            }
        }
        
        // Add freeze panes to keep headers visible
        ws['!freeze'] = { xSplit: 0, ySplit: tableStartRow + 2 };
        
        // Add the worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'BTEC Report');
        
        // Generate and download the file
        XLSX.writeFile(wb, filename);
    }
    </script>

    <!-- Logout Modal -->
    <div id="logoutModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
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
