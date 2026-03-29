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
    <title>WorkConnect Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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
            min-width: 0; /* flex child: allow shrink so grids/charts don’t overflow viewport */
            max-width: 100%;
            padding: 32px;
            background: #fff;
            margin-left: 240px;
            min-height: calc(100vh - 64px); min-height: calc(100dvh - 64px - env(safe-area-inset-bottom, 0px));
            overflow-y: auto;
            overflow-x: clip;
            box-sizing: border-box;
        }
        
        /* Chart.js: sized wrapper + responsive canvas (avoid padding on canvas — it adds to width) */
        .analytics-line-chart-wrap,
        .analytics-doughnut-chart-wrap {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            position: relative;
        }
        .analytics-line-chart-wrap {
            height: 300px;
        }
        .analytics-doughnut-chart-wrap {
            height: 300px;
        }
        .charts-container {
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }
        .registration-chart-container,
        .status-chart-container {
            box-sizing: border-box;
            min-width: 0;
            max-width: 100%;
        }
        
        /* Demographic chart area — fixed height so Chart.js fills predictably */
        .analytics-demographic-chart-wrap {
            position: relative;
            width: 100%;
            height: 280px;
            min-height: 0;
        }

        /* Barangay leaderboard: flex so few cards stretch full width; many wrap (up to ~13) */
        .analytics-barangay-leaderboard {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }
        .analytics-barangay-leaderboard .analytics-barangay-lb-card {
            flex: 1 1 160px;
            min-width: 0;
            box-sizing: border-box;
            max-width: 100%;
        }
        @media (min-width: 768px) {
            .analytics-barangay-leaderboard .analytics-barangay-lb-card {
                flex: 1 1 200px;
            }
        }
        /* Bar chart: full-width canvas like other analytics charts */
        .analytics-barangay-chart-wrap {
            position: relative;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            height: 300px;
            box-sizing: border-box;
        }
        .analytics-barangay-chart-wrap canvas {
            display: block;
            max-width: 100% !important;
        }
        @media (min-width: 1200px) {
            .analytics-barangay-chart-wrap {
                height: 360px;
            }
        }
        
        .analytics-line-chart-wrap canvas,
        .analytics-doughnut-chart-wrap canvas {
            display: block;
            max-width: 100% !important;
        }
        /* Desktop and Laptop Responsive Design */
        @media (min-width: 1200px) {
            .main-content {
                padding: 40px;
            }
            
            canvas {
                max-height: 400px !important;
            }
            
            .analytics-line-chart-wrap {
                height: 400px;
            }
            
            .analytics-doughnut-chart-wrap {
                height: 400px;
            }
        }
        
        @media (min-width: 992px) and (max-width: 1199px) {
            .main-content {
                padding: 32px;
            }
            
            canvas {
                max-height: 350px !important;
            }
            
            .analytics-line-chart-wrap {
                height: 400px;
            }
            
            .analytics-doughnut-chart-wrap {
                height: 350px;
            }
        }
        
        @media (min-width: 769px) and (max-width: 991px) {
            .main-content {
                padding: 24px;
            }
            
            .charts-container {
                grid-template-columns: 1fr 1fr !important;
                gap: 20px !important;
            }
            
            canvas {
                max-height: 300px !important;
            }
            
            .analytics-line-chart-wrap {
                height: 300px;
            }
            
            .analytics-doughnut-chart-wrap {
                height: 300px;
            }
        }
        
        @media (min-width: 481px) and (max-width: 768px) {
            .main-content {
                padding: 20px;
                max-width: 100%;
                min-width: 0;
            }
            
            .charts-container {
                display: flex !important;
                flex-direction: column !important;
                gap: 20px !important;
                min-width: 0 !important;
                max-width: 100% !important;
            }
            
            .registration-chart-container,
            .status-chart-container {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }
            
            canvas {
                max-height: 320px !important;
            }
            
            .analytics-line-chart-wrap {
                height: 280px;
            }
            
            .analytics-doughnut-chart-wrap {
                height: 280px;
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
            
            .header div {
                margin-left: auto !important;
                flex-direction: column;
                gap: 8px;
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
                max-width: 100%;
                min-width: 0;
                overflow-x: clip;
            }
            
            .analytics-dashboard-header > div:first-child {
                display: flex;
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }
            .analytics-dashboard-header > div:first-child > div:last-child {
                margin-left: 0;
                justify-content: center;
                align-self: stretch;
            }
            .analytics-dashboard-actions {
                justify-content: center;
                width: 100%;
            }
            .analytics-dashboard-header h2 {
                font-size: 1.5rem;
            }
            .analytics-dashboard-header > div:first-child p {
                font-size: 1rem;
            }
            
            /* Mobile: Stack charts vertically - Registration Trends first, then Application Status */
            .charts-container {
                display: flex !important;
                flex-direction: column !important;
                gap: 24px !important;
                min-width: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            
            /* Registration Trends Chart - Full width on mobile */
            .registration-chart-container {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                order: 1; /* First on mobile */
            }
            
            /* Application Status Chart - Full width on mobile */
            .status-chart-container {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                order: 2; /* Second on mobile */
            }
            
            /* Mobile filter adjustments */
            .registration-chart-container > div:first-child {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
            }
            
            .registration-chart-container > div:first-child > div:last-child {
                width: 100% !important;
                flex-wrap: wrap !important;
            }
            
            .registration-chart-container select {
                min-width: 120px !important;
                font-size: 0.8rem !important;
            }
            
            /* Mobile chart container adjustments */
            .registration-chart-container {
                padding: 20px !important;
            }
            
            .status-chart-container {
                padding: 20px !important;
            }
            
            .main-content > div:nth-child(2) {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .main-content > div:nth-child(4) {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .main-content > div:nth-child(5) {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            canvas {
                max-height: 350px !important;
            }
            
            .analytics-line-chart-wrap {
                height: 260px;
            }
            
            .analytics-doughnut-chart-wrap {
                height: 280px;
            }
            
            .registration-chart-container,
            .status-chart-container {
                min-height: 0 !important;
            }
            
            .registration-chart-container {
                position: relative;
            }
            
            /* Job Applicants' Most Common Skills — 2 columns, compact tiles */
            .analytics-skills-block {
                padding: 16px !important;
                margin-bottom: 20px !important;
            }
            .analytics-skills-block > h3 {
                font-size: 1.05rem !important;
                margin-bottom: 12px !important;
            }
            .analytics-skills-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 8px !important;
            }
            .analytics-skills-grid .analytics-skill-tile {
                padding: 10px 6px !important;
                border-radius: 10px !important;
            }
            .analytics-skills-grid .analytics-skill-tile > div:nth-child(1) {
                font-size: 1.1rem !important;
                margin-bottom: 4px !important;
            }
            .analytics-skills-grid .analytics-skill-tile > div:nth-child(2) {
                font-size: 0.68rem !important;
                font-weight: 600 !important;
                line-height: 1.25 !important;
                margin-bottom: 2px !important;
            }
            .analytics-skills-grid .analytics-skill-tile > div:nth-child(3) {
                font-size: 1.35rem !important;
                margin-bottom: 2px !important;
            }
            .analytics-skills-grid .analytics-skill-tile > div:nth-child(4) {
                font-size: 0.65rem !important;
            }
            
            /* Demographic Analytics — 2×2 grid, shorter charts */
            .analytics-demographic-block {
                padding: 16px !important;
                margin-bottom: 20px !important;
            }
            .analytics-demographic-block .analytics-demographic-head h3 {
                font-size: 1.05rem !important;
            }
            .analytics-demographic-block .analytics-demographic-head p {
                font-size: 0.8rem !important;
            }
            .analytics-demographic-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
            }
            .analytics-demographic-grid > .analytics-demo-chart-card {
                padding: 10px !important;
                border-radius: 10px !important;
            }
            .analytics-demographic-grid > .analytics-demo-chart-card > h4 {
                font-size: 0.8rem !important;
                margin: 0 0 8px 0 !important;
            }
            .analytics-demographic-chart-wrap {
                position: relative;
                width: 100%;
                height: 150px;
                min-height: 0;
            }
            
            /* Success rate / processing / uptime — 3 in one row */
            .analytics-kpi-row {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 6px !important;
                margin-bottom: 0 !important;
            }
            .analytics-kpi-row > div {
                padding: 10px 4px !important;
                border-radius: 10px !important;
                box-sizing: border-box;
                min-width: 0;
            }
            .analytics-kpi-row > div > div:nth-child(1) {
                font-size: 1.15rem !important;
                margin-bottom: 4px !important;
            }
            .analytics-kpi-row > div > div:nth-child(2) {
                font-size: clamp(0.8rem, 4.2vw, 1.05rem) !important;
                font-weight: 700 !important;
                margin-bottom: 4px !important;
                line-height: 1.15 !important;
            }
            .analytics-kpi-row > div > div:nth-child(3) {
                font-size: 0.55rem !important;
                line-height: 1.25 !important;
                opacity: 0.95 !important;
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
            
            .analytics-dashboard-header h2 {
                font-size: 1.3rem;
            }
            .analytics-dashboard-header > div:first-child p {
                font-size: 0.9rem;
            }
            
            .main-content > div:nth-child(2) > div {
                padding: 20px;
            }
            
            .main-content > div:nth-child(2) > div > div:first-child {
                font-size: 2rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(3) {
                font-size: 2.2rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(4) {
                font-size: 1rem;
            }
            
            .main-content > div:nth-child(2) > div > div:nth-child(5) {
                font-size: 0.8rem;
            }
            
            /* Small mobile chart adjustments */
            .charts-container {
                gap: 16px !important;
            }
            
            .registration-chart-container,
            .status-chart-container {
                padding: 16px !important;
            }
            
            .registration-chart-container h3,
            .status-chart-container h3 {
                font-size: 1.1rem !important;
            }
            
            canvas {
                max-height: 320px !important;
            }
            
            .analytics-line-chart-wrap {
                height: 240px;
            }
            
            .analytics-doughnut-chart-wrap {
                height: 260px;
            }
            
            .registration-chart-container,
            .status-chart-container {
                min-height: 0 !important;
            }
            
            .analytics-demographic-chart-wrap {
                height: 125px;
            }
            .analytics-kpi-row > div > div:nth-child(2) {
                font-size: clamp(0.72rem, 3.8vw, 0.95rem) !important;
            }
            .analytics-kpi-row > div > div:nth-child(3) {
                font-size: 0.5rem !important;
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
                max-width: 100%;
                min-width: 0;
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

        /* Key Insights — insight rows (all viewports) */
        .analytics-insight-row {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .analytics-insight-icon {
            font-size: 1.5rem;
            line-height: 1;
            flex-shrink: 0;
        }
        .analytics-insight-text {
            flex: 1;
            min-width: 0;
            color: #333;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.4;
            overflow-wrap: break-word;
        }

        /* Key Insights — narrow screens: balanced insets, wrap text, single column */
        @media (max-width: 768px) {
            .analytics-key-insights-card {
                padding-left: 18px !important;
                padding-right: 22px !important;
            }
            .analytics-insights-grid {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
                min-width: 0 !important;
            }
            .analytics-insight-item {
                min-width: 0 !important;
                max-width: 100%;
                box-sizing: border-box;
            }
            .analytics-insight-row {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                min-width: 0;
            }
            .analytics-insight-icon {
                font-size: 1.35rem !important;
                line-height: 1.2;
                flex-shrink: 0;
                margin-top: 2px;
            }
            .analytics-insight-text {
                flex: 1;
                min-width: 0;
                color: #333;
                font-size: 0.9rem !important;
                font-weight: 500;
                line-height: 1.45;
                overflow-wrap: break-word;
                word-break: break-word;
                padding-right: 6px;
            }
        }
        @media (max-width: 480px) {
            .analytics-key-insights-card {
                padding-left: 14px !important;
                padding-right: 20px !important;
            }
        }

        /* Top skills per barangay — responsive grid for all barangays (e.g. 13), up to 10 skills each */
        .analytics-barangay-top-skills {
            background: rgba(76, 175, 80, 0.06);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(76, 175, 80, 0.14);
            margin-top: 24px;
            box-shadow: 0 2px 12px rgba(27, 94, 32, 0.06);
        }
        .analytics-bts-title {
            margin: 0 0 8px 0;
            color: #2e7d32;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .analytics-bts-sub {
            margin: 0 0 18px 0;
            color: #555;
            font-size: 0.875rem;
            line-height: 1.45;
            max-width: 72ch;
        }
        .analytics-barangay-top-skills-grid {
            display: grid;
            gap: 14px;
            min-width: 0;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 200px), 1fr));
        }
        /* Desktop/tablet: auto-fit stretches few cards across full width; adds columns as more barangays appear */
        @media (min-width: 640px) {
            .analytics-barangay-top-skills-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }
        @media (min-width: 992px) {
            .analytics-barangay-top-skills-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
        }
        .analytics-bts-card {
            background: linear-gradient(180deg, #ffffff 0%, #f9fff9 100%);
            border-radius: 12px;
            border: 1px solid #c8e6c9;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.08);
            min-width: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .analytics-bts-card-head {
            background: linear-gradient(135deg, #43a047, #2e7d32);
            color: #fff;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 0.95rem;
            text-align: center;
            letter-spacing: 0.02em;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .analytics-bts-card-body {
            padding: 10px 12px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .analytics-bts-empty {
            margin: 0;
            color: #888;
            font-size: 0.82rem;
            text-align: center;
            padding: 8px 4px;
        }
        .analytics-bts-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 8px;
            background: rgba(76, 175, 80, 0.06);
            border: 1px solid rgba(76, 175, 80, 0.1);
            min-width: 0;
        }

        /* Employee email verification + verified NSRP (below top skills per barangay) */
        .analytics-email-verification-row {
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid rgba(76, 175, 80, 0.18);
        }
        .analytics-ev-title {
            margin: 0 0 8px 0;
            color: #2e7d32;
            font-size: 1.05rem;
            font-weight: 600;
        }
        .analytics-ev-sub {
            margin-bottom: 16px !important;
        }
        .analytics-ev-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
        }
        .analytics-ev-card {
            background: linear-gradient(180deg, #ffffff 0%, #f9fff9 100%);
            border-radius: 12px;
            border: 1px solid #c8e6c9;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.08);
            padding: 16px 18px;
            text-align: center;
        }
        .analytics-ev-card-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #2e7d32;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 8px;
        }
        .analytics-ev-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1b5e20;
            line-height: 1.15;
        }
        .analytics-ev-card-desc {
            margin: 8px 0 0 0;
            font-size: 0.8rem;
            color: #555;
            line-height: 1.4;
        }
        .analytics-ev-footnote {
            margin: 14px 0 0 0;
            font-size: 0.8rem;
            color: #777;
            line-height: 1.45;
            max-width: 72ch;
        }
        .analytics-ev-table-wrap {
            margin-top: 20px;
            border-radius: 12px;
            border: 1px solid rgba(76, 175, 80, 0.2);
            background: #fff;
            overflow: hidden;
        }
        .analytics-ev-table-scroll {
            max-height: 420px;
            overflow: auto;
        }
        .analytics-ev-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .analytics-ev-table caption {
            caption-side: top;
            text-align: left;
            padding: 12px 14px 10px;
            font-weight: 600;
            color: #2e7d32;
            background: rgba(76, 175, 80, 0.08);
            border-bottom: 1px solid rgba(76, 175, 80, 0.15);
        }
        .analytics-ev-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #e8f5e9;
            color: #1b5e20;
            font-weight: 600;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #c8e6c9;
            white-space: nowrap;
        }
        .analytics-ev-table tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid #eee;
            color: #333;
            vertical-align: top;
        }
        .analytics-ev-table tbody td .analytics-ev-email-text {
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .analytics-ev-table tbody tr:nth-child(even) {
            background: rgba(76, 175, 80, 0.04);
        }
        .analytics-ev-table tbody tr:hover {
            background: rgba(76, 175, 80, 0.1);
        }
        .analytics-ev-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .analytics-ev-badge-yes {
            background: #c8e6c9;
            color: #1b5e20;
        }
        .analytics-ev-badge-no {
            background: #ffebee;
            color: #c62828;
        }
        .analytics-ev-badge-na {
            background: #eceff1;
            color: #546e7a;
        }

        /* All User Accounts — compact on small screens (card rows, less padding) */
        @media (max-width: 640px) {
            .analytics-email-verification-row {
                margin-top: 16px;
                padding-top: 14px;
            }
            .analytics-ev-title {
                font-size: 0.95rem;
            }
            .analytics-ev-sub.analytics-ev-sub {
                font-size: 0.8rem !important;
                margin-bottom: 10px !important;
                line-height: 1.35 !important;
            }
            .analytics-ev-grid {
                gap: 8px;
            }
            .analytics-ev-card {
                padding: 10px 12px;
                border-radius: 10px;
            }
            .analytics-ev-card-label {
                font-size: 0.65rem;
                margin-bottom: 4px;
                letter-spacing: 0.03em;
            }
            .analytics-ev-card-value {
                font-size: 1.35rem;
            }
            .analytics-ev-card-desc {
                font-size: 0.7rem;
                margin-top: 4px;
                line-height: 1.3;
            }
            .analytics-ev-footnote {
                font-size: 0.74rem;
                margin-top: 10px;
            }
            .analytics-ev-table-wrap {
                margin-top: 12px;
                border-radius: 10px;
            }
            .analytics-ev-table-scroll {
                max-height: min(52vh, 380px);
                overflow-x: hidden;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .analytics-ev-table {
                font-size: 0.78rem;
            }
            .analytics-ev-table caption {
                padding: 8px 10px 6px;
                font-size: 0.88rem;
            }
            .analytics-ev-table thead {
                display: none;
            }
            .analytics-ev-table tbody tr {
                display: block;
                margin-bottom: 8px;
                padding: 8px 10px;
                background: #fff;
                border: 1px solid rgba(76, 175, 80, 0.2);
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            }
            .analytics-ev-table tbody tr:nth-child(even) {
                background: #fafefa;
            }
            .analytics-ev-table tbody td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                padding: 4px 0 !important;
                border-bottom: none;
                border-top: 1px solid rgba(0, 0, 0, 0.05);
                font-size: 0.76rem;
                line-height: 1.3;
                vertical-align: middle;
            }
            .analytics-ev-table tbody tr > td:first-child {
                border-top: none;
                padding-top: 0 !important;
            }
            .analytics-ev-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #2e7d32;
                font-size: 0.65rem;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                flex-shrink: 0;
                max-width: 42%;
            }
            .analytics-ev-table tbody td > span.analytics-ev-badge {
                flex-shrink: 0;
            }
            .analytics-ev-table tbody td.analytics-ev-td-plain .analytics-ev-cell-val {
                text-align: right;
                flex: 1;
                min-width: 0;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            .analytics-ev-table tbody td.analytics-ev-td-email {
                align-items: flex-start;
            }
            .analytics-ev-table tbody td.analytics-ev-td-email::before {
                margin-top: 2px;
            }
            .analytics-ev-table tbody td.analytics-ev-td-email .analytics-ev-email-text {
                text-align: right;
                word-break: break-all;
                overflow-wrap: anywhere;
                line-height: 1.25;
                font-size: 0.72rem;
                color: #444;
            }
            .analytics-ev-badge {
                padding: 1px 6px;
                font-size: 0.62rem;
            }
            .analytics-ev-table tbody tr.analytics-ev-tr-fullmsg {
                padding: 10px 12px;
            }
            .analytics-ev-table tbody tr.analytics-ev-tr-fullmsg td {
                display: block;
                border: none;
                padding: 0 !important;
                text-align: center;
                font-size: 0.8rem;
            }
            .analytics-ev-table tbody tr.analytics-ev-tr-fullmsg td::before {
                content: none;
            }
        }

        .analytics-bts-row:nth-child(even) {
            background: rgba(76, 175, 80, 0.03);
        }
        .analytics-bts-rank {
            flex-shrink: 0;
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: #e8f5e9;
            color: #1b5e20;
            font-size: 0.7rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .analytics-bts-name {
            flex: 1;
            min-width: 0;
            font-size: 0.82rem;
            color: #333;
            font-weight: 500;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .analytics-bts-count {
            flex-shrink: 0;
            font-size: 0.75rem;
            font-weight: 700;
            color: #1b5e20;
            background: #fff;
            border: 1px solid #a5d6a7;
            padding: 2px 8px;
            border-radius: 999px;
        }

        /* Print: only analytics dashboard (no top bar, sidebar, or export/print controls) */
        @media print {
            @page {
                margin: 12mm;
                size: auto;
            }
            html, body {
                background: #fff !important;
                overflow: visible !important;
                height: auto !important;
            }
            .header,
            .sidebar,
            .hamburger-menu {
                display: none !important;
            }
            .layout {
                display: block !important;
                padding-top: 0 !important;
                min-height: 0 !important;
            }
            .main-content {
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                min-height: 0 !important;
                overflow: visible !important;
                background: #fff !important;
                box-shadow: none !important;
            }
            .no-print {
                display: none !important;
            }
            /* Avoid only registration/status chart areas; demographic cards handle their own breaks */
            .analytics-line-chart-wrap,
            .analytics-doughnut-chart-wrap {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            canvas {
                max-width: 100% !important;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/Employer-sidebar-neat.css?v=<?php echo time(); ?>">
    <style id="analytics-print-overrides">
        /* After Employer-sidebar-neat.css */
        @media print {
            body .main-content .analytics-barangay-print-section {
                page-break-before: always !important;
                break-before: page !important;
                margin-top: 0 !important;
            }
            /* Demographics: no clipping (was max-height+overflow:hidden cutting charts).
               Stack one chart per row; keep each card on a single page. */
            body .main-content .analytics-demographic-block {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            body .main-content .analytics-demographic-grid {
                display: block !important;
            }
            body .main-content .analytics-demographic-grid > .analytics-demo-chart-card {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                margin-bottom: 18px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            body .main-content .analytics-demographic-chart-wrap {
                overflow: visible !important;
                max-height: none !important;
                height: 300px !important;
                min-height: 280px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            body .main-content .analytics-barangay-chart-wrap {
                height: 280px !important;
                max-height: none !important;
                overflow: visible !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            body .main-content .analytics-bts-card {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            body .main-content .analytics-barangay-top-skills-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
    </style>
    <script src="../assets/js/employer-page-loading.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
<div class="header" id="mainHeader" style="position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background: #233a8b; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; height: 64px; box-sizing: border-box; max-width: 100vw; overflow: hidden;">
        <div style="display: flex; align-items: center; flex: 1; min-width: 0;">
            <button class="hamburger-menu" id="hamburgerMenu" style="display: none; background: none; border: none; cursor: pointer; padding: 8px; margin-right: 8px; z-index: 1001; flex-shrink: 0;">
                <span style="display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px;"></span>
                <span style="display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px;"></span>
                <span style="display: block; width: 25px; height: 3px; background: #fff; margin: 5px 0; transition: 0.3s; border-radius: 2px;"></span>
            </button>
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <span class="header-title" id="headerTitle" style="font-size: 1rem; font-weight: bold; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; min-width: 0; max-width: 150px;">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; margin-left: 8px; flex-shrink: 0;" id="adminSection">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="adminUsername" style="font-size: 0.75rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px;">Welcome, Admin</span>
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
            <a href="btec.php"> BTEC MONTHLY REPORT</a>
            <a href="add.php" id="addAccountLink" style="display: none;"> ADD ACCOUNT</a>
            <a href="#" class="active"> Analytics</a>
            <a href="announcement.php"> ANNOUNCEMENTS</a>
            <a href="logout.php" class="logout"> Logout</a>
        </div>
        <div class="main-content">
            <!-- Page Header: title left, stat cards right; blue line; actions below -->
            <div class="analytics-dashboard-header" style="margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap; padding-bottom: 20px; border-bottom: 2px solid #e3f2fd;">
                    <div style="flex: 1; min-width: 200px;">
                        <h2 style="color:#233a8b; font-size:1.8rem; font-weight:700; margin:0;">📊 Analytics Dashboard</h2>
                        <p style="color:#666; margin:8px 0 0 0; font-size:1.1rem;">Comprehensive insights and performance metrics</p>
                    </div>
                    <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: stretch; margin-left: auto; justify-content: flex-end;">
                        <div style="background: linear-gradient(135deg, #e3f2fd, #f0f4ff); padding: 12px 20px; border-radius: 12px; border-left: 4px solid #1976d2; min-width: 148px; box-sizing: border-box;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1976d2;" id="totalUsers">0</div>
                            <div style="font-size: 0.85rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px;">Total users</div>
                            <div style="font-size: 0.68rem; color: #777; line-height: 1.35; margin-top: 4px;">All registered employee accounts</div>
                            <div style="font-size: 0.68rem; color: #555; line-height: 1.35; margin-top: 8px; min-height: 2.2em;" id="headerAccountOnlyNote"></div>
                        </div>
                        <div style="background: linear-gradient(135deg, #e8f5e9, #f1f8e9); padding: 12px 20px; border-radius: 12px; border-left: 4px solid #43a047; min-width: 148px; box-sizing: border-box;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #2e7d32;" id="headerNsrpJobseekersCount">0</div>
                            <div style="font-size: 0.85rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px;">Jobseekers</div>
                            <div style="font-size: 0.68rem; color: #777; line-height: 1.35; margin-top: 4px;">Completed NSRP form (same as JOBSEEKERS list)</div>
                        </div>
                    </div>
                </div>
                <div class="no-print analytics-dashboard-actions" style="display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap; margin-top: 16px;">
                    <button type="button" onclick="exportToExcel()" style="background: linear-gradient(135deg, #4caf50, #45a049); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        📊 Export Excel
                    </button>
                    <button type="button" onclick="printReport()" style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        🖨️ Print Report
                    </button>
                </div>
            </div>

            <!-- Quick Insights Widget -->
            <div class="analytics-key-insights-card" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1); margin-bottom: 32px; box-sizing: border-box;">
                <div class="analytics-key-insights-head" style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem; flex-shrink: 0;">💡</div>
                    <div style="min-width: 0;">
                        <h3 style="margin: 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">Key Insights</h3>
                        <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">Auto-generated insights from your data</p>
                    </div>
                </div>
                <div id="insightsContainer" class="analytics-insights-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; min-width: 0;">
                    <!-- Insights will be populated by JavaScript -->
                </div>
            </div>

            <!-- Analytics Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px;">
                
                <!-- Jobseeker Statistics -->
                <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #4caf50, #45a049); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">👥</div>
                        <div>
                            <h3 style="margin: 0; color: #233a8b; font-size: 1.2rem; font-weight: 700;">Jobseeker Analytics</h3>
                            <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">Registration trends and demographics</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div style="text-align: center; padding: 16px; background: rgba(76,175,80,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #4caf50;" id="totalJobseekers">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">NSRP submitted</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(144, 202, 249, 0.25); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #1565c0;" id="accountsPendingNsrp">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Account only (no NSRP yet)</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(255,152,0,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #ff9800;" id="pendingApplications">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Pending review</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(76,175,80,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #4caf50;" id="acceptedApplications">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Accepted</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(244,67,54,0.1); border-radius: 8px; grid-column: 1 / -1;">
                            <div style="font-size: 2rem; font-weight: 700; color: #f44336;" id="rejectedApplications">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Rejected</div>
                        </div>
                    </div>
                </div>

                <!-- Skills Registry Analytics -->
                <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #1976d2, #1565c0); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">🛠️</div>
                        <div>
                            <h3 style="margin: 0; color: #233a8b; font-size: 1.2rem; font-weight: 700;">Skills Registry</h3>
                            <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">PESO skill registry; total counts skill entries stored there</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div style="text-align: center; padding: 16px; background: rgba(25,118,210,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #1976d2;" id="totalSkills">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Total Skills</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: rgba(25,118,210,0.1); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: 700; color: #1976d2;" id="barangayCount">0</div>
                            <div style="font-size: 0.8rem; color: #666; text-transform: uppercase;">Barangays</div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">📈</div>
                        <div>
                            <h3 style="margin: 0; color: #233a8b; font-size: 1.2rem; font-weight: 700;">Monthly Trends</h3>
                            <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">Registration patterns</p>
                        </div>
                    </div>
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 3rem; font-weight: 700; color: #ff9800;" id="thisMonthRegistrations">0</div>
                        <div style="font-size: 0.9rem; color: #666; margin-top: 8px;">New registrations this month</div>
                    </div>
                    
                    <!-- Month-over-Month Comparison -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px;">
                        <div style="background: rgba(255,152,0,0.1); border-radius: 8px; padding: 16px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #ff9800;" id="lastMonthRegistrations">0</div>
                            <div style="font-size: 0.8rem; color: #666; margin-top: 4px;">Last Month</div>
                        </div>
                        <div style="background: rgba(255,152,0,0.1); border-radius: 8px; padding: 16px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #ff9800;" id="monthOverMonthChange">0%</div>
                            <div style="font-size: 0.8rem; color: #666; margin-top: 4px;">Change</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px;">
                
                 <!-- Registration Trends Chart -->
                 <div class="registration-chart-container" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                     <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                         <h3 style="margin: 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">📊 Registration Trends</h3>
                         <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                             <select id="trendFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; font-size: 0.9rem;">
                                 <option value="12months">Last 12 Months</option>
                                 <option value="yearly" id="yearlyOption">Yearly (2020-2025)</option>
                                 <option value="monthly">Specific Month</option>
                             </select>
                             <select id="monthFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; font-size: 0.9rem; display: none;">
                                 <option value="">Select Month</option>
                                 <option value="01">January</option>
                                 <option value="02">February</option>
                                 <option value="03">March</option>
                                 <option value="04">April</option>
                                 <option value="05">May</option>
                                 <option value="06">June</option>
                                 <option value="07">July</option>
                                 <option value="08">August</option>
                                 <option value="09">September</option>
                                 <option value="10">October</option>
                                 <option value="11">November</option>
                                 <option value="12">December</option>
                             </select>
                             <select id="yearFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; font-size: 0.9rem; display: none;">
                                 <option value="">Select Year</option>
                                 <!-- Years will be populated by JavaScript -->
                             </select>
                         </div>
                     </div>
                     <div class="analytics-line-chart-wrap">
                        <canvas id="registrationChart"></canvas>
                     </div>
                 </div>

                <!-- Application Status Pie Chart -->
                <div class="status-chart-container" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1);">
                    <h3 style="margin: 0 0 20px 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">🎯 Application Status</h3>
                    <div class="analytics-doughnut-chart-wrap">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Skills Distribution -->
            <div class="analytics-skills-block" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1); margin-bottom: 32px;">
                <h3 style="margin: 0 0 20px 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">🛠️ Job Applicants' Most Common Skills</h3>
                <div id="skillsList" class="analytics-skills-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <!-- Skills will be populated by JavaScript -->
                </div>
            </div>

            <!-- Demographic Analytics Section -->
            <div class="analytics-demographic-block" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1); margin-bottom: 32px;">
                <div class="analytics-demographic-head" style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #9c27b0, #7b1fa2); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">👥</div>
                    <div>
                        <h3 style="margin: 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">Demographic Analytics (Skill Registry)</h3>
                        <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">From PESO skill registry records: age, gender, education, and employment</p>
                    </div>
                </div>
                <div class="analytics-demographic-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                    <!-- Age Distribution Chart -->
                    <div class="analytics-demo-chart-card" style="background: rgba(156,39,176,0.05); border-radius: 12px; padding: 20px; border: 1px solid rgba(156,39,176,0.1);">
                        <h4 style="margin: 0 0 16px 0; color: #7b1fa2; font-size: 1.1rem; font-weight: 600;">Age Distribution</h4>
                        <div class="analytics-demographic-chart-wrap"><canvas id="ageChart"></canvas></div>
                    </div>
                    
                    <!-- Gender Distribution Chart -->
                    <div class="analytics-demo-chart-card" style="background: rgba(156,39,176,0.05); border-radius: 12px; padding: 20px; border: 1px solid rgba(156,39,176,0.1);">
                        <h4 style="margin: 0 0 16px 0; color: #7b1fa2; font-size: 1.1rem; font-weight: 600;">Gender Distribution</h4>
                        <div class="analytics-demographic-chart-wrap"><canvas id="genderChart"></canvas></div>
                    </div>
                    
                    <!-- Education Distribution Chart -->
                    <div class="analytics-demo-chart-card" style="background: rgba(156,39,176,0.05); border-radius: 12px; padding: 20px; border: 1px solid rgba(156,39,176,0.1);">
                        <h4 style="margin: 0 0 16px 0; color: #7b1fa2; font-size: 1.1rem; font-weight: 600;">Education Level</h4>
                        <div class="analytics-demographic-chart-wrap"><canvas id="educationChart"></canvas></div>
                    </div>
                    
                    <!-- Employment Status Chart -->
                    <div class="analytics-demo-chart-card" style="background: rgba(156,39,176,0.05); border-radius: 12px; padding: 20px; border: 1px solid rgba(156,39,176,0.1);">
                        <h4 style="margin: 0 0 16px 0; color: #7b1fa2; font-size: 1.1rem; font-weight: 600;">Employment Status</h4>
                        <div class="analytics-demographic-chart-wrap"><canvas id="employmentChart"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- NSRP jobseeker demographics (submitted NSRP forms) -->
            <div class="analytics-demographic-block" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1); margin-bottom: 32px;">
                <div class="analytics-demographic-head" style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #1976d2, #1565c0); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">📋</div>
                    <div>
                        <h3 style="margin: 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">Demographic Analytics (NSRP jobseekers)</h3>
                        <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">From submitted National Skills Registration Program (NSRP) forms—all applicants; updates as new forms are submitted</p>
                    </div>
                </div>
                <div class="analytics-demographic-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                    <div class="analytics-demo-chart-card" style="background: rgba(25,118,210,0.06); border-radius: 12px; padding: 20px; border: 1px solid rgba(25,118,210,0.12);">
                        <h4 style="margin: 0 0 16px 0; color: #1565c0; font-size: 1.1rem; font-weight: 600;">Age Distribution</h4>
                        <div class="analytics-demographic-chart-wrap"><canvas id="nsrpAgeChart"></canvas></div>
                    </div>
                    <div class="analytics-demo-chart-card" style="background: rgba(25,118,210,0.06); border-radius: 12px; padding: 20px; border: 1px solid rgba(25,118,210,0.12);">
                        <h4 style="margin: 0 0 16px 0; color: #1565c0; font-size: 1.1rem; font-weight: 600;">Gender Distribution</h4>
                        <div class="analytics-demographic-chart-wrap"><canvas id="nsrpGenderChart"></canvas></div>
                    </div>
                    <div class="analytics-demo-chart-card" style="background: rgba(25,118,210,0.06); border-radius: 12px; padding: 20px; border: 1px solid rgba(25,118,210,0.12);">
                        <h4 style="margin: 0 0 16px 0; color: #1565c0; font-size: 1.1rem; font-weight: 600;">Education Level</h4>
                        <div class="analytics-demographic-chart-wrap"><canvas id="nsrpEducationChart"></canvas></div>
                    </div>
                    <div class="analytics-demo-chart-card" style="background: rgba(25,118,210,0.06); border-radius: 12px; padding: 20px; border: 1px solid rgba(25,118,210,0.12);">
                        <h4 style="margin: 0 0 16px 0; color: #1565c0; font-size: 1.1rem; font-weight: 600;">Employment Status</h4>
                        <div class="analytics-demographic-chart-wrap"><canvas id="nsrpEmploymentChart"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- Barangay Comparison Section (print: always starts on a new page) -->
            <div class="analytics-barangay-print-section" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(25,118,210,0.08); border: 1px solid rgba(35,58,139,0.1); margin-bottom: 32px;">
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #4caf50, #45a049); color: white; padding: 12px; border-radius: 12px; margin-right: 16px; font-size: 1.5rem;">🏘️</div>
                    <div>
                        <h3 style="margin: 0; color: #233a8b; font-size: 1.3rem; font-weight: 700;">Barangay Comparison</h3>
                        <p style="margin: 4px 0 0 0; color: #666; font-size: 0.9rem;">Compare registration patterns across barangays</p>
                    </div>
                </div>
                
                <!-- Barangay Leaderboard -->
                <div style="background: rgba(76,175,80,0.05); border-radius: 12px; padding: 20px; border: 1px solid rgba(76,175,80,0.1); margin-bottom: 24px;">
                    <h4 style="margin: 0 0 16px 0; color: #45a049; font-size: 1.1rem; font-weight: 600;">Registration Leaderboard</h4>
                    <div id="barangayLeaderboard" class="analytics-barangay-leaderboard">
                        <!-- Leaderboard will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Barangay Comparison Chart -->
                <div style="background: rgba(76,175,80,0.05); border-radius: 12px; padding: 20px; border: 1px solid rgba(76,175,80,0.1);">
                    <h4 style="margin: 0 0 16px 0; color: #45a049; font-size: 1.1rem; font-weight: 600;">Registrations by Barangay</h4>
                    <div class="analytics-barangay-chart-wrap">
                        <canvas id="barangayChart"></canvas>
                    </div>
                </div>

                <!-- Top skills per barangay (skill_registry.skills — same as Excel export) -->
                <div class="analytics-barangay-top-skills">
                    <h4 class="analytics-bts-title">Top skills per barangay</h4>
                    <p class="analytics-bts-sub">Top 10 Skills Per barangay from skill registry</p>
                    <div id="barangayTopSkillsGrid" class="analytics-barangay-top-skills-grid"></div>
                </div>

                <div class="analytics-email-verification-row">
                    <h4 class="analytics-ev-title">Employee accounts — email verification &amp; NSRP</h4>
                    <p class="analytics-bts-sub analytics-ev-sub">Counts for registered employee logins: verified email after signup, still pending verification, and verified users who submitted an NSRP (job seeker) form.</p>
                    <div class="analytics-ev-grid">
                        <div class="analytics-ev-card">
                            <div class="analytics-ev-card-label">Verified (email)</div>
                            <div class="analytics-ev-card-value" id="analyticsEmailVerifiedCount">—</div>
                            <p class="analytics-ev-card-desc">Confirmed signup via email link</p>
                        </div>
                        <div class="analytics-ev-card">
                            <div class="analytics-ev-card-label">Unverified</div>
                            <div class="analytics-ev-card-value" id="analyticsEmailUnverifiedCount">—</div>
                            <p class="analytics-ev-card-desc">Signed up, email not verified yet</p>
                        </div>
                        <div class="analytics-ev-card">
                            <div class="analytics-ev-card-label">Verified job seekers</div>
                            <div class="analytics-ev-card-value" id="analyticsVerifiedNsrpCount">—</div>
                            <p class="analytics-ev-card-desc">Email verified and NSRP form submitted</p>
                        </div>
                    </div>
                    <p class="analytics-ev-footnote" id="analyticsEmailVerificationNote" style="display: none;"></p>
                    <div class="analytics-ev-table-wrap" id="analyticsEmployeeAccountsTableWrap">
                        <div class="analytics-ev-table-scroll">
                            <table class="analytics-ev-table" id="analyticsEmployeeAccountsTable">
                                <caption>All User Accounts</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Email verified</th>
                                        <th scope="col">NSRP submitted</th>
                                        <th scope="col">Verified job seeker</th>
                                    </tr>
                                </thead>
                                <tbody id="analyticsEmployeeAccountsTbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="analytics-kpi-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                
                <!-- Success Rate -->
                <div style="background: linear-gradient(135deg, #4caf50, #45a049); color: white; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 8px;">🎯</div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;" id="successRate">0%</div>
                    <div style="font-size: 1rem; opacity: 0.9;">Success Referral Rate</div>
                </div>

                <!-- Average Processing Time -->
                <div style="background: linear-gradient(135deg, #1976d2, #1565c0); color: white; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 8px;">⏱️</div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;" id="avgProcessingTime">0</div>
                    <div style="font-size: 1rem; opacity: 0.9;">Avg. Processing Days</div>
                </div>

                <!-- System Health -->
                <div style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 8px;">💚</div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;">99.9%</div>
                    <div style="font-size: 1rem; opacity: 0.9;">System Uptime</div>
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
                headerTitle.style.fontSize = '1rem';
                headerTitle.style.whiteSpace = 'nowrap';
                headerTitle.style.overflow = 'hidden';
                headerTitle.style.textOverflow = 'ellipsis';
                headerTitle.style.maxWidth = '150px';
                
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
                    headerTitle.style.fontSize = '1rem';
                    headerTitle.style.maxWidth = '150px';
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
        
        // Force mobile styles immediately
        if (window.innerWidth <= 768) {
            const header = document.getElementById('mainHeader');
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const headerTitle = document.getElementById('headerTitle');
            const logo = document.querySelector('img');
            const leftSection = header.querySelector('div:first-child');
            const adminSection = document.getElementById('adminSection');
            
            // Force mobile header layout
            header.style.display = 'flex';
            header.style.flexDirection = 'row';
            header.style.justifyContent = 'space-between';
            header.style.alignItems = 'center';
            header.style.padding = '12px 16px';
            header.style.height = '64px';
            header.style.overflow = 'hidden';
            
            // Force left section layout
            if (leftSection) {
                leftSection.style.display = 'flex';
                leftSection.style.flexDirection = 'row';
                leftSection.style.alignItems = 'center';
                leftSection.style.flex = '1';
                leftSection.style.minWidth = '0';
            }
            
            // Force hamburger menu to show
            hamburgerMenu.style.display = 'block';
            hamburgerMenu.style.visibility = 'visible';
            hamburgerMenu.style.marginRight = '8px';
            hamburgerMenu.style.flexShrink = '0';
            
            // Force logo styles
            if (logo) {
                logo.style.height = '32px';
                logo.style.marginRight = '8px';
                logo.style.flexShrink = '0';
            }
            
            // Force title styles
            headerTitle.style.fontSize = '1.2rem';
            headerTitle.style.flex = '1';
            headerTitle.style.minWidth = '0';
            headerTitle.style.overflow = 'hidden';
            headerTitle.style.textOverflow = 'ellipsis';
            headerTitle.style.whiteSpace = 'nowrap';
            
            // Force admin section layout
            if (adminSection) {
                adminSection.style.display = 'flex';
                adminSection.style.flexDirection = 'row';
                adminSection.style.alignItems = 'center';
                adminSection.style.gap = '6px';
                adminSection.style.marginLeft = '8px';
                adminSection.style.flexShrink = '0';
            }
            
            // Force admin username styles
            const adminUsername = document.getElementById('adminUsername');
            if (adminUsername) {
                adminUsername.style.fontSize = '0.75rem';
                adminUsername.style.maxWidth = '100px';
                adminUsername.style.overflow = 'hidden';
                adminUsername.style.textOverflow = 'ellipsis';
                adminUsername.style.whiteSpace = 'nowrap';
            }
        }
        
        // Apply immediately
        applyMobileStyles();
        removeWelcomeText();
        
        // Initial check
        handleMobileHeader();
        
        // Check on resize
        window.addEventListener('resize', handleMobileHeader);
        
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
                document.getElementById('adminUsername').textContent = data.username; // Removed 'Welcome, ' prefix
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

    // Analytics Data and Charts
    let analyticsData = {
        totalJobseekers: 0,
        totalEmployeeAccounts: 0,
        nsrpSubmittedUsers: 0,
        accountsPendingNsrp: 0,
        emailVerifiedUsers: 0,
        emailUnverifiedUsers: 0,
        verifiedEmailWithNsrpUsers: 0,
        hasEmailVerifiedColumn: false,
        employeeAccounts: [],
        employeeCountsOk: false,
        pendingApplications: 0,
        acceptedApplications: 0,
        rejectedApplications: 0,
        totalSkills: 0,
        jobseekerSkillsTotal: 0,
        barangayCount: 13,
        thisMonthRegistrations: 0,
        lastMonthRegistrations: 0,
        monthlyTrends: [],
        skillsDistribution: [],
        demographicData: null,
        nsrpDemographicData: null,
        barangayData: null
    };
    
     let chartsCreated = false;

     // Generate trends data based on filter
     function generateTrendsData(jobseekers, filterType, selectedMonth = null, selectedYear = null) {
         const trendsData = [];
         
         if (filterType === '12months') {
             // Last 12 calendar months (use day 1 so Feb is not skipped when "today" is Mar 29–31)
             const end = new Date();
             const endYear = end.getFullYear();
             const endMonth = end.getMonth();
             for (let i = 11; i >= 0; i--) {
                 const date = new Date(endYear, endMonth - i, 1);
                 const monthName = date.toLocaleDateString('en-US', { month: 'short' });
                 const y = date.getFullYear();
                 const m = date.getMonth() + 1;
                 const count = jobseekers.filter(j => {
                     if (j.submission_month && j.submission_year) {
                         return parseInt(j.submission_month) === m && parseInt(j.submission_year) === y;
                     }
                     return false;
                 }).length;
                 trendsData.push({ month: monthName, count: count });
             }
         } else if (filterType === 'yearly') {
             // Yearly data from 2020 to current year (automatically extends)
             const currentYear = new Date().getFullYear();
             for (let year = 2020; year <= currentYear; year++) {
                 const count = jobseekers.filter(j => {
                     if (j.submission_year) {
                         return parseInt(j.submission_year) === year;
                     }
                     return false;
                 }).length;
                 
                 trendsData.push({ month: year.toString(), count: count });
             }
         } else if (filterType === 'monthly' && selectedMonth && selectedYear) {
             // Specific month data - show daily breakdown within the selected month
             console.log('Filtering for month:', selectedMonth, 'year:', selectedYear);
             
             const selectedMonthInt = parseInt(selectedMonth);
             const selectedYearInt = parseInt(selectedYear);
             
             // Get the number of days in the selected month
             const daysInMonth = new Date(selectedYearInt, selectedMonthInt, 0).getDate();
             console.log('Days in month:', daysInMonth);
             
             // Create daily data for the entire month
             for (let day = 1; day <= daysInMonth; day++) {
                 const count = jobseekers.filter(j => {
                     if (j.submission_day && j.submission_month && j.submission_year) {
                         const jobseekerDay = parseInt(j.submission_day);
                         const jobseekerMonth = parseInt(j.submission_month);
                         const jobseekerYear = parseInt(j.submission_year);
                         
                         return jobseekerDay === day && 
                                jobseekerMonth === selectedMonthInt && 
                                jobseekerYear === selectedYearInt;
                     }
                     return false;
                 }).length;
                 
                 trendsData.push({ month: `Day ${day}`, count: count });
                 console.log(`Day ${day}: ${count} registrations`);
             }
             
             console.log('Found daily trends data:', trendsData);
             
            // Log the daily data for debugging
            const totalDailyCount = trendsData.reduce((sum, day) => sum + day.count, 0);
            console.log('Total daily count from daily data:', totalDailyCount);
         }
         
         return trendsData;
     }

     // Fetch analytics data
    async function fetchAnalyticsData() {
        try {
            const [jobseekerResponse, empCountsResponse] = await Promise.all([
                fetch('jobseekers.php'),
                fetch('analytics_employee_counts.php')
            ]);
            const jobseekers = await jobseekerResponse.json();

            let empJson = { success: false };
            try {
                if (empCountsResponse.ok) {
                    empJson = await empCountsResponse.json();
                }
            } catch (e) {
                console.error('Error parsing employee counts:', e);
            }
            if (empJson.success) {
                analyticsData.employeeCountsOk = true;
                analyticsData.totalEmployeeAccounts = parseInt(empJson.total_employee_accounts, 10) || 0;
                analyticsData.nsrpSubmittedUsers = parseInt(empJson.nsrp_submitted_users, 10) || 0;
                analyticsData.accountsPendingNsrp = parseInt(empJson.accounts_pending_nsrp, 10) || 0;
                analyticsData.emailVerifiedUsers = parseInt(empJson.email_verified_users, 10) || 0;
                analyticsData.emailUnverifiedUsers = parseInt(empJson.email_unverified_users, 10) || 0;
                analyticsData.verifiedEmailWithNsrpUsers = parseInt(empJson.verified_email_with_nsrp_users, 10) || 0;
                analyticsData.hasEmailVerifiedColumn = !!empJson.has_email_verified_column;
                analyticsData.employeeAccounts = Array.isArray(empJson.employee_accounts) ? empJson.employee_accounts : [];
            } else {
                analyticsData.employeeCountsOk = false;
                analyticsData.totalEmployeeAccounts = 0;
                analyticsData.nsrpSubmittedUsers = 0;
                analyticsData.accountsPendingNsrp = 0;
                analyticsData.emailVerifiedUsers = 0;
                analyticsData.emailUnverifiedUsers = 0;
                analyticsData.verifiedEmailWithNsrpUsers = 0;
                analyticsData.hasEmailVerifiedColumn = false;
                analyticsData.employeeAccounts = [];
            }

            console.log('Fetched jobseekers:', jobseekers.length);

            analyticsData.totalJobseekers = jobseekers.length;
            if (analyticsData.employeeCountsOk) {
                if (analyticsData.nsrpSubmittedUsers !== jobseekers.length) {
                    console.warn('NSRP count mismatch: API distinct users', analyticsData.nsrpSubmittedUsers, 'vs jobseeker rows', jobseekers.length);
                }
            } else {
                analyticsData.nsrpSubmittedUsers = jobseekers.length;
                analyticsData.totalEmployeeAccounts = jobseekers.length;
                analyticsData.accountsPendingNsrp = 0;
            }
            analyticsData.pendingApplications = jobseekers.filter(j => !j.application_status || j.application_status === 'Pending' || j.application_status === '').length;
            analyticsData.acceptedApplications = jobseekers.filter(j => j.application_status === 'Accepted').length;
            analyticsData.rejectedApplications = jobseekers.filter(j => j.application_status === 'Rejected').length;
            
            console.log('Status counts:', {
                pending: analyticsData.pendingApplications,
                accepted: analyticsData.acceptedApplications,
                rejected: analyticsData.rejectedApplications
            });
            
            // Calculate this month's and last month's registrations from real data
            const currentDate = new Date();
            const currentMonth = currentDate.getMonth();
            const currentYear = currentDate.getFullYear();
            const lastMonth = currentMonth === 0 ? 11 : currentMonth - 1;
            const lastMonthYear = currentMonth === 0 ? currentYear - 1 : currentYear;
            
            analyticsData.thisMonthRegistrations = jobseekers.filter(j => {
                if (j.submission_month && j.submission_year) {
                    return parseInt(j.submission_month) === (currentMonth + 1) && parseInt(j.submission_year) === currentYear;
                }
                return false;
            }).length;
            
            analyticsData.lastMonthRegistrations = jobseekers.filter(j => {
                if (j.submission_month && j.submission_year) {
                    return parseInt(j.submission_month) === (lastMonth + 1) && parseInt(j.submission_year) === lastMonthYear;
                }
                return false;
            }).length;
            
             // Generate trends data based on current filter
             analyticsData.monthlyTrends = generateTrendsData(jobseekers, '12months');
            
            console.log('Monthly trends data:', analyticsData.monthlyTrends);
            
            // Fetch additional data
            await Promise.all([
                fetchSkillsData(),
                fetchDemographicData(),
                fetchNsrpDemographicData(),
                fetchBarangayData()
            ]);
            
            console.log('Analytics data:', analyticsData);
            
            // Update UI
            updateAnalyticsUI();
            generateInsights();
            
             // Wait a bit for DOM to be ready, then create charts
             if (!chartsCreated) {
                 setTimeout(() => {
                     console.log('Creating charts after data fetch...');
                     createCharts();
                     chartsCreated = true;
                 }, 100);
             } else {
                 console.log('Charts already created, updating registration chart...');
                 createRegistrationChart();
             }
            
        } catch (error) {
            console.error('Error fetching analytics data:', error);
            analyticsData.totalJobseekers = 0;
            analyticsData.totalEmployeeAccounts = 0;
            analyticsData.nsrpSubmittedUsers = 0;
            analyticsData.accountsPendingNsrp = 0;
            analyticsData.emailVerifiedUsers = 0;
            analyticsData.emailUnverifiedUsers = 0;
            analyticsData.verifiedEmailWithNsrpUsers = 0;
            analyticsData.hasEmailVerifiedColumn = false;
            analyticsData.employeeAccounts = [];
            analyticsData.employeeCountsOk = false;
            analyticsData.pendingApplications = 0;
            analyticsData.acceptedApplications = 0;
            analyticsData.rejectedApplications = 0;
            analyticsData.thisMonthRegistrations = 0;
            analyticsData.lastMonthRegistrations = 0;
            analyticsData.monthlyTrends = [
                { month: 'Jul', count: 0 },
                { month: 'Aug', count: 0 },
                { month: 'Sep', count: 0 },
                { month: 'Oct', count: 0 },
                { month: 'Nov', count: 0 },
                { month: 'Dec', count: 0 }
            ];
            analyticsData.skillsDistribution = [];
            analyticsData.totalSkills = 0;
            analyticsData.jobseekerSkillsTotal = 0;
            
            updateAnalyticsUI();
            if (!chartsCreated) {
                setTimeout(() => {
                    createCharts();
                    chartsCreated = true;
                }, 100);
            }
        }
    }

    // Helper function to parse custom skills from skill_others field
    function parseOthersSkills(othersText) {
        if (!othersText || othersText === 'n/a' || othersText.trim() === '') {
            return [];
        }
        
        // Split by common separators: comma, semicolon, "and", "or", newline
        const separators = [',', ';', ' and ', ' or ', '\n', '\r\n'];
        let skills = [othersText.trim()];
        
        // Split by each separator
        separators.forEach(separator => {
            const newSkills = [];
            skills.forEach(skill => {
                if (skill.includes(separator)) {
                    newSkills.push(...skill.split(separator).map(s => s.trim()).filter(s => s !== ''));
                } else {
                    newSkills.push(skill);
                }
            });
            skills = newSkills;
        });
        
        // Clean up and filter out empty strings
        return skills
            .map(skill => skill.trim())
            .filter(skill => skill !== '' && skill !== 'n/a')
            .filter(skill => skill.length > 1); // Filter out single characters
    }

    async function fetchSkillsData() {
        try {
            let registrySkillTotal = 0;
            try {
                const regRes = await fetch('skill_registry_stats.php');
                const regJson = await regRes.json();
                if (regJson.success) {
                    registrySkillTotal = parseInt(regJson.total_skill_mentions, 10) || 0;
                }
            } catch (e) {
                console.error('Error fetching skill registry stats:', e);
            }

            const skillCounts = {};
            const jobseekerResponse = await fetch('jobseekers.php');
            const jobseekers = await jobseekerResponse.json();
            
            jobseekers.forEach(jobseeker => {
                // Count individual predefined skills
                if (jobseeker.skill_auto_mechanic == 1) skillCounts['Auto Mechanic'] = (skillCounts['Auto Mechanic'] || 0) + 1;
                if (jobseeker.skill_electrician == 1) skillCounts['Electrician'] = (skillCounts['Electrician'] || 0) + 1;
                if (jobseeker.skill_photography == 1) skillCounts['Photography'] = (skillCounts['Photography'] || 0) + 1;
                if (jobseeker.skill_beautician == 1) skillCounts['Beautician'] = (skillCounts['Beautician'] || 0) + 1;
                if (jobseeker.skill_embroidery == 1) skillCounts['Embroidery'] = (skillCounts['Embroidery'] || 0) + 1;
                if (jobseeker.skill_plumbing == 1) skillCounts['Plumbing'] = (skillCounts['Plumbing'] || 0) + 1;
                if (jobseeker.skill_carpentry == 1) skillCounts['Carpentry'] = (skillCounts['Carpentry'] || 0) + 1;
                if (jobseeker.skill_gardening == 1) skillCounts['Gardening'] = (skillCounts['Gardening'] || 0) + 1;
                if (jobseeker.skill_sewing == 1) skillCounts['Sewing'] = (skillCounts['Sewing'] || 0) + 1;
                if (jobseeker.skill_computer == 1) skillCounts['Computer Literacy'] = (skillCounts['Computer Literacy'] || 0) + 1;
                if (jobseeker.skill_masonry == 1) skillCounts['Masonry'] = (skillCounts['Masonry'] || 0) + 1;
                if (jobseeker.skill_stenography == 1) skillCounts['Stenography'] = (skillCounts['Stenography'] || 0) + 1;
                if (jobseeker.skill_domestic == 1) skillCounts['Domestic Chores'] = (skillCounts['Domestic Chores'] || 0) + 1;
                if (jobseeker.skill_painter == 1) skillCounts['Painter/Artist'] = (skillCounts['Painter/Artist'] || 0) + 1;
                if (jobseeker.skill_tailoring == 1) skillCounts['Tailoring'] = (skillCounts['Tailoring'] || 0) + 1;
                if (jobseeker.skill_driver == 1) skillCounts['Driving'] = (skillCounts['Driving'] || 0) + 1;
                if (jobseeker.skill_painting == 1) skillCounts['Painting Job'] = (skillCounts['Painting Job'] || 0) + 1;
                
                // Count custom skills from skill_others field
                if (jobseeker.skill_others && jobseeker.skill_others !== 'n/a' && jobseeker.skill_others.trim() !== '') {
                    const othersSkills = parseOthersSkills(jobseeker.skill_others);
                    othersSkills.forEach(skill => {
                        // Use original case for display, but normalize for counting
                        const skillKey = skill; // Keep original case
                        skillCounts[skillKey] = (skillCounts[skillKey] || 0) + 1;
                    });
                }
            });
            
            // Convert to array and sort by count
            analyticsData.skillsDistribution = Object.entries(skillCounts)
                .map(([skill, count]) => ({ skill, count }))
                .sort((a, b) => b.count - a.count)
                .slice(0, 6); // Top 6 skills

            analyticsData.jobseekerSkillsTotal = Object.values(skillCounts).reduce((sum, count) => sum + count, 0);
            analyticsData.totalSkills = registrySkillTotal;
            
        } catch (error) {
            console.error('Error fetching skills data:', error);
            analyticsData.skillsDistribution = [];
            analyticsData.totalSkills = 0;
            analyticsData.jobseekerSkillsTotal = 0;
        }
    }

    // Fetch demographic data
    async function fetchDemographicData() {
        try {
            const response = await fetch('skill_demographics.php');
            const result = await response.json();
            if (result.success) {
                analyticsData.demographicData = result.data;
                createDemographicCharts();
            }
        } catch (error) {
            console.error('Error fetching demographic data:', error);
        }
    }

    async function fetchNsrpDemographicData() {
        try {
            const response = await fetch('nsrp_jobseeker_demographics.php');
            const result = await response.json();
            if (result.success) {
                analyticsData.nsrpDemographicData = result.data;
                createNsrpDemographicCharts();
            }
        } catch (error) {
            console.error('Error fetching NSRP demographic data:', error);
        }
    }

    // Fetch barangay data
    async function fetchBarangayData() {
        try {
            const response = await fetch('barangay_analytics.php');
            const result = await response.json();
            if (result.success) {
                analyticsData.barangayData = result.data;
                createBarangayCharts();
                updateBarangayLeaderboard();
                updateBarangayTopSkills();
            }
        } catch (error) {
            console.error('Error fetching barangay data:', error);
        }
    }

    /** Insight items for dashboard and Excel export (same logic). */
    function buildAnalyticsInsightItems() {
        const insights = [];
        if (analyticsData.lastMonthRegistrations > 0) {
            const change = ((analyticsData.thisMonthRegistrations - analyticsData.lastMonthRegistrations) / analyticsData.lastMonthRegistrations) * 100;
            const changeText = change > 0 ? `up ${Math.round(change)}%` : `down ${Math.round(Math.abs(change))}%`;
            insights.push({
                icon: change > 0 ? '📈' : '📉',
                text: `Registrations ${changeText} compared to last month`,
                color: change > 0 ? '#4caf50' : '#f44336',
                category: 'Registration trend'
            });
        }
        if (analyticsData.skillsDistribution.length > 0) {
            const topSkill = analyticsData.skillsDistribution[0];
            insights.push({
                icon: '🛠️',
                text: `${topSkill.skill} is the most in-demand skill with ${topSkill.count} registrations`,
                color: '#1976d2',
                category: 'NSRP jobseeker skills'
            });
        }
        if (analyticsData.barangayData && analyticsData.barangayData.overall_stats.most_active) {
            const mostActive = analyticsData.barangayData.overall_stats.most_active;
            insights.push({
                icon: '🏆',
                text: `${mostActive.barangay} leads with ${mostActive.total_registrations} registrations`,
                color: '#ff9800',
                category: 'Skill registry by barangay'
            });
        }
        if (analyticsData.employeeCountsOk && analyticsData.accountsPendingNsrp > 0) {
            insights.push({
                icon: '👤',
                text: `${analyticsData.accountsPendingNsrp} registered account(s) have not submitted the NSRP form yet`,
                color: '#1565c0',
                category: 'Employee accounts'
            });
        }
        const totalProcessed = analyticsData.acceptedApplications + analyticsData.rejectedApplications;
        if (totalProcessed > 0) {
            const successRate = Math.round((analyticsData.acceptedApplications / totalProcessed) * 100);
            insights.push({
                icon: '🎯',
                text: `${successRate}% success rate in job referrals`,
                color: '#4caf50',
                category: 'Referral outcomes'
            });
        }
        return insights;
    }

    // Generate insights
    function generateInsights() {
        const insights = buildAnalyticsInsightItems();

        // Update insights container
        const container = document.getElementById('insightsContainer');
        container.innerHTML = '';
        
        insights.forEach(insight => {
            const insightElement = document.createElement('div');
            insightElement.className = 'analytics-insight-item';
            insightElement.style.cssText = `
                background: linear-gradient(135deg, ${insight.color}15, ${insight.color}25);
                border-radius: 12px;
                padding: 16px;
                border-left: 4px solid ${insight.color};
                transition: transform 0.2s ease;
            `;
            insightElement.innerHTML = `
                <div class="analytics-insight-row">
                    <div class="analytics-insight-icon">${insight.icon}</div>
                    <div class="analytics-insight-text">${insight.text}</div>
                </div>
            `;
            insightElement.addEventListener('mouseenter', () => {
                insightElement.style.transform = 'translateY(-2px)';
            });
            insightElement.addEventListener('mouseleave', () => {
                insightElement.style.transform = 'translateY(0)';
            });
            container.appendChild(insightElement);
        });
    }

    // Create demographic charts
    function createDemographicCharts() {
        if (!analyticsData.demographicData) return;
        
        const data = analyticsData.demographicData;
        const narrow = window.innerWidth <= 768;
        const demoLegend = {
            position: 'bottom',
            labels: {
                padding: narrow ? 4 : 15,
                usePointStyle: true,
                boxWidth: narrow ? 6 : 12,
                font: { size: narrow ? 8 : 12 }
            }
        };
        const barAxisMobile = narrow ? {
            x: { grid: { display: false }, ticks: { font: { size: 8 }, maxRotation: 45 } },
            y: { beginAtZero: true, grid: { display: false }, ticks: { font: { size: 8 } } }
        } : {
            y: { beginAtZero: true, grid: { display: false } },
            x: { grid: { display: false } }
        };
        
        // Age Distribution Chart
        const ageCtx = document.getElementById('ageChart');
        if (ageCtx) {
            new Chart(ageCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['15-25', '26-35', '36-45', '46+'],
                    datasets: [{
                        data: [data.age_15_25, data.age_26_35, data.age_36_45, data.age_46_plus],
                        backgroundColor: ['#ff9800', '#4caf50', '#2196f3', '#9c27b0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: demoLegend
                    }
                }
            });
        }
        
        // Gender Distribution Chart
        const genderCtx = document.getElementById('genderChart');
        if (genderCtx) {
            new Chart(genderCtx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Male', 'Female'],
                    datasets: [{
                        data: [data.male, data.female],
                        backgroundColor: ['#2196f3', '#e91e63'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: demoLegend
                    }
                }
            });
        }
        
        // Education Chart
        const educationCtx = document.getElementById('educationChart');
        if (educationCtx) {
            new Chart(educationCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Elementary', 'High School', 'College', 'Vocational'],
                    datasets: [{
                        data: [data.elementary, data.high_school, data.college, data.vocational],
                        backgroundColor: ['#ff5722', '#ff9800', '#4caf50', '#2196f3'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: barAxisMobile
                }
            });
        }
        
        // Employment Chart
        const employmentCtx = document.getElementById('employmentChart');
        if (employmentCtx) {
            new Chart(employmentCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Unemployed', 'Wage Employed', 'Self-Employed'],
                    datasets: [{
                        data: [data.unemployed, data.wage_employed, data.self_employed],
                        backgroundColor: ['#f44336', '#4caf50', '#ff9800'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: demoLegend
                    }
                }
            });
        }
    }

    function createNsrpDemographicCharts() {
        if (!analyticsData.nsrpDemographicData) return;

        const data = analyticsData.nsrpDemographicData;
        const narrow = window.innerWidth <= 768;
        const demoLegend = {
            position: 'bottom',
            labels: {
                padding: narrow ? 4 : 15,
                usePointStyle: true,
                boxWidth: narrow ? 6 : 12,
                font: { size: narrow ? 8 : 12 }
            }
        };
        const barAxisMobile = narrow ? {
            x: { grid: { display: false }, ticks: { font: { size: 8 }, maxRotation: 45 } },
            y: { beginAtZero: true, grid: { display: false }, ticks: { font: { size: 8 } } }
        } : {
            y: { beginAtZero: true, grid: { display: false } },
            x: { grid: { display: false } }
        };

        const ageCtx = document.getElementById('nsrpAgeChart');
        if (ageCtx) {
            new Chart(ageCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['15-25', '26-35', '36-45', '46+'],
                    datasets: [{
                        data: [data.age_15_25, data.age_26_35, data.age_36_45, data.age_46_plus],
                        backgroundColor: ['#ff9800', '#4caf50', '#2196f3', '#9c27b0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: demoLegend }
                }
            });
        }

        const genderCtx = document.getElementById('nsrpGenderChart');
        if (genderCtx) {
            new Chart(genderCtx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Male', 'Female'],
                    datasets: [{
                        data: [data.male, data.female],
                        backgroundColor: ['#2196f3', '#e91e63'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: demoLegend }
                }
            });
        }

        const educationCtx = document.getElementById('nsrpEducationChart');
        if (educationCtx) {
            new Chart(educationCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Elementary', 'High School', 'College', 'Vocational'],
                    datasets: [{
                        data: [data.elementary, data.high_school, data.college, data.vocational],
                        backgroundColor: ['#ff5722', '#ff9800', '#4caf50', '#2196f3'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: barAxisMobile
                }
            });
        }

        const employmentCtx = document.getElementById('nsrpEmploymentChart');
        if (employmentCtx) {
            new Chart(employmentCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Unemployed', 'Wage Employed', 'Self-Employed'],
                    datasets: [{
                        data: [data.unemployed, data.wage_employed, data.self_employed],
                        backgroundColor: ['#f44336', '#4caf50', '#ff9800'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: demoLegend }
                }
            });
        }
    }

    // Create barangay charts (all barangays with registry data — municipality has up to 13)
    function createBarangayCharts() {
        if (!analyticsData.barangayData) return;

        const barangays = analyticsData.barangayData.barangays || [];
        const barangayCtx = document.getElementById('barangayChart');
        if (!barangayCtx) return;

        if (typeof Chart !== 'undefined' && typeof Chart.getChart === 'function') {
            const existing = Chart.getChart(barangayCtx);
            if (existing) {
                existing.destroy();
            }
        }

        const n = barangays.length;
        const maxBarThickness = n > 10 ? 32 : n > 6 ? 40 : undefined;

        new Chart(barangayCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: barangays.map(function (b) { return b.barangay; }),
                datasets: [{
                    label: 'Registrations',
                    data: barangays.map(function (b) { return b.total_registrations; }),
                    backgroundColor: 'rgba(76,175,80,0.8)',
                    borderColor: '#4caf50',
                    borderWidth: 1,
                    maxBarThickness: maxBarThickness,
                    categoryPercentage: n <= 4 ? 0.75 : n <= 8 ? 0.85 : 0.9,
                    barPercentage: n <= 4 ? 0.85 : 0.9
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 4, right: 8, bottom: 4, left: 4 }
                },
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    },
                    x: {
                        ticks: {
                            maxRotation: n > 8 ? 55 : 45,
                            minRotation: n > 8 ? 35 : 0,
                            autoSkip: false,
                            font: { size: n > 10 ? 10 : 12 }
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Update barangay leaderboard (all barangays returned by API, ordered by registrations)
    function updateBarangayLeaderboard() {
        if (!analyticsData.barangayData) return;

        const container = document.getElementById('barangayLeaderboard');
        if (!container) return;
        const barangays = analyticsData.barangayData.barangays || [];

        container.innerHTML = '';

        barangays.forEach(function (barangay, index) {
            const element = document.createElement('div');
            element.className = 'analytics-barangay-lb-card';
            element.style.cssText =
                'background: linear-gradient(135deg, #e8f5e8, #f1f8e9); border-radius: 8px; padding: 12px; ' +
                'text-align: center; border: 1px solid #c8e6c9;';

            const rankIcon = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '🏅';

            const iconEl = document.createElement('div');
            iconEl.style.cssText = 'font-size: 1.2rem; margin-bottom: 4px;';
            iconEl.textContent = rankIcon;

            const nameEl = document.createElement('div');
            nameEl.style.cssText = 'font-weight: 600; color: #2e7d32; font-size: 0.9rem;';
            nameEl.textContent = barangay.barangay || '—';

            const countEl = document.createElement('div');
            countEl.style.cssText = 'font-size: 1.1rem; font-weight: 700; color: #4caf50;';
            countEl.textContent = String(barangay.total_registrations != null ? barangay.total_registrations : '');

            const subEl = document.createElement('div');
            subEl.style.cssText = 'font-size: 0.7rem; color: #666;';
            subEl.textContent = 'registrations';

            element.appendChild(iconEl);
            element.appendChild(nameEl);
            element.appendChild(countEl);
            element.appendChild(subEl);

            container.appendChild(element);
        });
    }

    function updateBarangayTopSkills() {
        const grid = document.getElementById('barangayTopSkillsGrid');
        if (!grid) return;
        grid.innerHTML = '';
        if (!analyticsData.barangayData || !analyticsData.barangayData.barangays) {
            const p = document.createElement('p');
            p.className = 'analytics-bts-empty';
            p.style.textAlign = 'left';
            p.textContent = 'No barangay data loaded.';
            grid.appendChild(p);
            return;
        }
        const barangays = analyticsData.barangayData.barangays;
        barangays.forEach(function (b) {
            const card = document.createElement('div');
            card.className = 'analytics-bts-card';

            const head = document.createElement('div');
            head.className = 'analytics-bts-card-head';
            head.textContent = b.barangay || '—';
            card.appendChild(head);

            const body = document.createElement('div');
            body.className = 'analytics-bts-card-body';

            const ts = b.top_skills;
            const entries = ts && typeof ts === 'object' ? Object.entries(ts) : [];
            if (entries.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'analytics-bts-empty';
                empty.textContent = 'No skills listed in the registry for this barangay.';
                body.appendChild(empty);
            } else {
                entries.forEach(function (pair, idx) {
                    const row = document.createElement('div');
                    row.className = 'analytics-bts-row';
                    const rank = document.createElement('span');
                    rank.className = 'analytics-bts-rank';
                    rank.textContent = String(idx + 1);
                    const name = document.createElement('span');
                    name.className = 'analytics-bts-name';
                    name.textContent = pair[0];
                    const count = document.createElement('span');
                    count.className = 'analytics-bts-count';
                    count.textContent = String(pair[1]);
                    row.appendChild(rank);
                    row.appendChild(name);
                    row.appendChild(count);
                    body.appendChild(row);
                });
            }
            card.appendChild(body);
            grid.appendChild(card);
        });
    }

    function xmlEscape(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function demoVal(count, pct) {
        if (count == null && (pct == null || pct === '')) return '';
        const c = count != null ? String(count) : '';
        if (pct != null && pct !== '') {
            const p = typeof pct === 'number' ? pct : parseFloat(String(pct), 10);
            const ps = !isNaN(p) ? (Math.round(p * 10) / 10) + '%' : String(pct);
            return c ? (c + ' (' + ps + ')') : ps;
        }
        return c;
    }

    function appendDemographicRows(push, prefix, d) {
        if (!d) return;
        push(prefix, 'Total records', d.total);
        push(prefix, 'Male', demoVal(d.male, d.male_percentage));
        push(prefix, 'Female', demoVal(d.female, d.female_percentage));
        push(prefix, 'Age 15–25', demoVal(d.age_15_25, d.age_15_25_percentage));
        push(prefix, 'Age 26–35', demoVal(d.age_26_35, d.age_26_35_percentage));
        push(prefix, 'Age 36–45', demoVal(d.age_36_45, d.age_36_45_percentage));
        push(prefix, 'Age 46+', demoVal(d.age_46_plus, d.age_46_plus_percentage));
        push(prefix, 'Education — Elementary', demoVal(d.elementary, d.elementary_percentage));
        push(prefix, 'Education — High school', demoVal(d.high_school, d.high_school_percentage));
        push(prefix, 'Education — College', demoVal(d.college, d.college_percentage));
        push(prefix, 'Education — Vocational', demoVal(d.vocational, d.vocational_percentage));
        push(prefix, 'Employment — Unemployed', demoVal(d.unemployed, d.unemployed_percentage));
        push(prefix, 'Employment — Wage employed', demoVal(d.wage_employed, d.wage_employed_percentage));
        push(prefix, 'Employment — Self-employed', demoVal(d.self_employed, d.self_employed_percentage));
        if (d.first_time_jobseekers != null) {
            push(prefix, 'First-time jobseekers', d.first_time_jobseekers);
        }
        if (d.covid_displaced != null) {
            push(prefix, 'COVID displaced workers', d.covid_displaced);
        }
    }

    /** Excel 2003 XML — 3 columns; plain title rows; styled section bands + data grid only. */
    function buildAnalyticsExcelXml(rows) {
        function cell(styleId, val, preferNumber) {
            const s = val == null ? '' : String(val);
            const trimmed = s.trim();
            const isPct = /%/.test(s);
            const n = preferNumber && trimmed !== '' && !isPct && /^-?\d+(\.\d+)?$/.test(trimmed) ? parseFloat(trimmed) : null;
            if (n !== null && !isNaN(n)) {
                return '<Cell ss:StyleID="' + styleId + '"><Data ss:Type="Number">' + n + '</Data></Cell>';
            }
            return '<Cell ss:StyleID="' + styleId + '"><Data ss:Type="String">' + xmlEscape(s) + '</Data></Cell>';
        }
        const rowXml = rows.map(function (r) {
            if (r.k === 'blank') {
                return '<Row ss:AutoFitHeight="0" ss:Height="6"></Row>';
            }
            let sA, sB, sC;
            if (r.k === 'plain') {
                sA = sB = sC = 'sPlain';
            } else if (r.k === 'section') {
                sA = sB = sC = 'sSec';
            } else if (r.k === 'head') {
                sA = sB = sC = 'sHead';
            } else if (r.k === 'dataEm') {
                sA = sB = 'sData';
                sC = 'sDataEm';
            } else {
                sA = sB = sC = 'sData';
            }
            return '<Row ss:AutoFitHeight="1">' +
                cell(sA, r.a, false) +
                cell(sB, r.b, false) +
                cell(sC, r.c, true) +
                '</Row>';
        }).join('');
        return '<?xml version="1.0" encoding="UTF-8"?>\n' +
            '<?mso-application progid="Excel.Sheet"?>\n' +
            '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ' +
            'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">\n' +
            '<Styles>\n' +
            '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' +
            '<Font ss:FontName="Calibri" ss:Size="11"/></Style>\n' +
            '  <Style ss:ID="sPlain"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' +
            '<Font ss:FontName="Calibri" ss:Size="11"/></Style>\n' +
            '  <Style ss:ID="sSec"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' +
            '<Font ss:Bold="1" ss:Size="11"/><Interior ss:Color="#E3F2FD" ss:Pattern="Solid"/>' +
            '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BBDEFB"/></Borders></Style>\n' +
            '  <Style ss:ID="sHead"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' +
            '<Font ss:Bold="1" ss:Size="11"/><Interior ss:Color="#BBDEFB" ss:Pattern="Solid"/>' +
            '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>\n' +
            '  <Style ss:ID="sData"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' +
            '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#ECEFF1"/></Borders></Style>\n' +
            '  <Style ss:ID="sDataEm"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' +
            '<Font ss:Bold="1"/><Interior ss:Color="#FAFAFA" ss:Pattern="Solid"/>' +
            '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#ECEFF1"/></Borders></Style>\n' +
            '</Styles>\n' +
            '<Worksheet ss:Name="Analytics Report">\n' +
            '<Table ss:DefaultRowHeight="16">\n' +
            '  <Column ss:AutoFitWidth="0" ss:Width="240"/>\n' +
            '  <Column ss:AutoFitWidth="0" ss:Width="360"/>\n' +
            '  <Column ss:AutoFitWidth="0" ss:Width="160"/>\n' +
            rowXml +
            '</Table>\n</Worksheet>\n</Workbook>';
    }

    // Export — values only (no formulas / long source notes); header rows unstyled
    function exportToExcel() {
        const rows = [];
        const push = function (section, item, value, kind) {
            rows.push({
                a: section == null ? '' : section,
                b: item == null ? '' : item,
                c: value == null ? '' : value,
                k: kind || 'data'
            });
        };
        const blank = function () {
            rows.push({ a: '', b: '', c: '', k: 'blank' });
        };

        const now = new Date();
        const dateStr = now.toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' });

        push('WorkConnect — Analytics Report', 'Generated', dateStr, 'plain');
        blank();
        push('Section', 'Item', 'Value', 'head');
        blank();

        push('Jobseeker analytics', '', '', 'section');
        push('', 'Registered employee accounts (total)', analyticsData.totalEmployeeAccounts);
        push('', 'NSRP form submitted (distinct users)', analyticsData.nsrpSubmittedUsers);
        push('', 'Account only — NSRP not submitted yet', analyticsData.accountsPendingNsrp);
        push('', 'Email verified (employee accounts)', analyticsData.employeeCountsOk ? analyticsData.emailVerifiedUsers : '—');
        push('', 'Email unverified (signed up, not verified)', analyticsData.employeeCountsOk ? analyticsData.emailUnverifiedUsers : '—');
        push('', 'Verified email + NSRP submitted (distinct users)', analyticsData.employeeCountsOk ? analyticsData.verifiedEmailWithNsrpUsers : '—');
        push('', 'NSRP records (rows in jobseeker list)', analyticsData.totalJobseekers);
        push('', 'Pending review (referral)', analyticsData.pendingApplications);
        push('', 'Accepted', analyticsData.acceptedApplications);
        push('', 'Rejected', analyticsData.rejectedApplications);
        const totalDecided = analyticsData.acceptedApplications + analyticsData.rejectedApplications;
        const successPct = totalDecided > 0 ? Math.round((analyticsData.acceptedApplications / totalDecided) * 100) : null;
        push('', 'Success referral rate', totalDecided > 0 ? successPct + '%' : 'N/A');
        blank();

        push('Skills registry (KPI)', '', '', 'section');
        push('', 'Total skill entries (PESO registry)', analyticsData.totalSkills);
        push('', 'Barangays (dashboard figure)', analyticsData.barangayCount);
        blank();

        push('NSRP applicants — skills', '', '', 'section');
        push('', 'Total skill selections', analyticsData.jobseekerSkillsTotal);
        if (analyticsData.skillsDistribution.length > 0) {
            analyticsData.skillsDistribution.forEach((s) => {
                const pct = analyticsData.jobseekerSkillsTotal > 0
                    ? Math.round((s.count / analyticsData.jobseekerSkillsTotal) * 100)
                    : 0;
                push('', s.skill, demoVal(s.count, pct));
            });
        } else {
            push('', 'Applicant skills', '—');
        }
        blank();

        push('Monthly trends (NSRP)', '', '', 'section');
        const mom = analyticsData.lastMonthRegistrations > 0
            ? Math.round(((analyticsData.thisMonthRegistrations - analyticsData.lastMonthRegistrations) / analyticsData.lastMonthRegistrations) * 100)
            : 0;
        push('', 'New registrations this month', analyticsData.thisMonthRegistrations);
        push('', 'Last month registrations', analyticsData.lastMonthRegistrations);
        push('', 'Month-over-month change', analyticsData.lastMonthRegistrations > 0 ? ((mom >= 0 ? '+' : '') + mom + '%') : 'N/A');
        (analyticsData.monthlyTrends || []).forEach(function (m) {
            push('', m.month, m.count);
        });
        blank();

        push('Application status', '', '', 'section');
        push('', 'Accepted', analyticsData.acceptedApplications);
        push('', 'Pending', analyticsData.pendingApplications);
        push('', 'Rejected', analyticsData.rejectedApplications);
        blank();

        push('Key insights', '', '', 'section');
        const insightItems = buildAnalyticsInsightItems();
        if (insightItems.length === 0) {
            push('', '—', '—');
        } else {
            insightItems.forEach(function (ins) {
                push('', ins.category || 'Insight', ins.text);
            });
        }
        blank();

        push('Demographics — skill registry', '', '', 'section');
        if (analyticsData.demographicData) {
            appendDemographicRows(push, '', analyticsData.demographicData);
        } else {
            push('', '—', '—');
        }
        blank();

        push('Demographics — NSRP jobseekers', '', '', 'section');
        if (analyticsData.nsrpDemographicData) {
            appendDemographicRows(push, '', analyticsData.nsrpDemographicData);
        } else {
            push('', '—', '—');
        }
        blank();

        push('Barangay comparison (skill registry)', '', '', 'section');
        if (analyticsData.barangayData && analyticsData.barangayData.overall_stats) {
            const os = analyticsData.barangayData.overall_stats;
            push('', 'Number of barangays', os.total_barangays);
            push('', 'Total registrations', os.total_registrations);
            push('', 'Average per barangay', os.average_per_barangay);
            if (os.most_active) {
                push('', 'Most active — name', os.most_active.barangay);
                push('', 'Most active — count', os.most_active.total_registrations);
            }
            if (os.least_active) {
                push('', 'Least active — name', os.least_active.barangay);
                push('', 'Least active — count', os.least_active.total_registrations);
            }
            blank();
            (analyticsData.barangayData.barangays || []).forEach(function (b) {
                let topStr = '';
                if (b.top_skills && typeof b.top_skills === 'object') {
                    topStr = Object.keys(b.top_skills).map(function (k) {
                        return k + ': ' + b.top_skills[k];
                    }).join('; ');
                }
                push('', b.barangay + ' — registrations', b.total_registrations, 'dataEm');
                push('', b.barangay + ' — % of total', b.percentage_of_total != null ? b.percentage_of_total + '%' : '');
                push('', b.barangay + ' — male / female', (b.male != null ? b.male : '') + ' / ' + (b.female != null ? b.female : ''));
                if (topStr) {
                    push('', b.barangay + ' — top skills (up to 10)', topStr);
                }
            });
        } else {
            push('', '—', '—');
        }
        blank();

        push('Dashboard KPIs (bottom cards)', '', '', 'section');
        push('', 'Success referral rate', totalDecided > 0 ? successPct + '%' : 'N/A');
        const avgEl = document.getElementById('avgProcessingTime');
        push('', 'Avg. processing days (shown on dashboard)', avgEl ? avgEl.textContent : '—');
        push('', 'System uptime (shown on dashboard)', '99.9%');

        const xmlContent = '\uFEFF' + buildAnalyticsExcelXml(rows);
        const blob = new Blob([xmlContent], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'WorkConnect_Analytics_' + now.toISOString().slice(0, 10) + '.xls';
        a.click();
        window.URL.revokeObjectURL(url);
    }

    function resizeAllChartsForPrint() {
        try {
            if (typeof Chart !== 'undefined' && typeof Chart.getChart === 'function') {
                document.querySelectorAll('canvas').forEach(function (cv) {
                    var ch = Chart.getChart(cv);
                    if (ch && typeof ch.resize === 'function') {
                        ch.resize();
                    }
                    if (ch && typeof ch.update === 'function') {
                        ch.update('none');
                    }
                });
            }
        } catch (e) { /* ignore */ }
    }

    window.addEventListener('beforeprint', function () {
        window.dispatchEvent(new Event('resize'));
        resizeAllChartsForPrint();
    });

    function printReport() {
        window.dispatchEvent(new Event('resize'));
        resizeAllChartsForPrint();
        requestAnimationFrame(function () {
            resizeAllChartsForPrint();
            setTimeout(function () {
                window.print();
            }, 300);
        });
    }

    function analyticsEvMakeBadge(text, type) {
        const span = document.createElement('span');
        span.className = 'analytics-ev-badge analytics-ev-badge-' + (type || 'na');
        span.textContent = text;
        return span;
    }

    function renderAnalyticsEmployeeAccountsTable() {
        const tbody = document.getElementById('analyticsEmployeeAccountsTbody');
        const wrap = document.getElementById('analyticsEmployeeAccountsTableWrap');
        if (!tbody || !wrap) {
            return;
        }
        tbody.textContent = '';
        if (!analyticsData.employeeCountsOk) {
            const tr = document.createElement('tr');
            tr.className = 'analytics-ev-tr-fullmsg';
            const td = document.createElement('td');
            td.colSpan = 6;
            td.style.padding = '16px 12px';
            td.style.color = '#666';
            td.textContent = 'Account list could not be loaded. Refresh the page or check that you are signed in.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }
        const list = analyticsData.employeeAccounts || [];
        if (list.length === 0) {
            const tr = document.createElement('tr');
            tr.className = 'analytics-ev-tr-fullmsg';
            const td = document.createElement('td');
            td.colSpan = 6;
            td.style.padding = '16px 12px';
            td.style.color = '#666';
            td.textContent = 'No user accounts registered yet.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }
        list.forEach(function (acc) {
            const tr = document.createElement('tr');
            const name = [acc.firstname, acc.lastname].filter(Boolean).join(' ').trim() || '—';
            const evTracked = !!acc.email_verified_tracked;
            const evOn = parseInt(acc.email_verified, 10) === 1;
            const nsrp = parseInt(acc.nsrp_count, 10) || 0;
            const verifiedJobSeeker = evTracked ? (evOn && nsrp > 0) : (nsrp > 0);

            const tdId = document.createElement('td');
            tdId.setAttribute('data-label', 'ID');
            tdId.className = 'analytics-ev-td-plain';
            const spanId = document.createElement('span');
            spanId.className = 'analytics-ev-cell-val';
            spanId.textContent = String(acc.id);
            tdId.appendChild(spanId);
            tr.appendChild(tdId);

            const tdName = document.createElement('td');
            tdName.setAttribute('data-label', 'Name');
            tdName.className = 'analytics-ev-td-plain';
            const spanName = document.createElement('span');
            spanName.className = 'analytics-ev-cell-val';
            spanName.textContent = name;
            tdName.appendChild(spanName);
            tr.appendChild(tdName);

            const tdEmail = document.createElement('td');
            tdEmail.setAttribute('data-label', 'Email');
            tdEmail.className = 'analytics-ev-td-plain analytics-ev-td-email';
            const spanEmail = document.createElement('span');
            spanEmail.className = 'analytics-ev-email-text analytics-ev-cell-val';
            const em = acc.email || '—';
            spanEmail.textContent = em;
            if (em && em !== '—') {
                tdEmail.title = em;
                spanEmail.title = em;
            }
            tdEmail.appendChild(spanEmail);
            tr.appendChild(tdEmail);

            const tdEv = document.createElement('td');
            tdEv.setAttribute('data-label', 'Verified');
            if (evTracked) {
                tdEv.appendChild(analyticsEvMakeBadge(evOn ? 'Yes' : 'No', evOn ? 'yes' : 'no'));
            } else {
                tdEv.appendChild(analyticsEvMakeBadge('N/A', 'na'));
            }
            tr.appendChild(tdEv);

            const tdNsrp = document.createElement('td');
            tdNsrp.setAttribute('data-label', 'NSRP');
            const hasNsrp = nsrp > 0;
            tdNsrp.appendChild(analyticsEvMakeBadge(hasNsrp ? 'True' : 'False', hasNsrp ? 'yes' : 'no'));
            tr.appendChild(tdNsrp);

            const tdVjs = document.createElement('td');
            tdVjs.setAttribute('data-label', 'Job seeker');
            tdVjs.appendChild(analyticsEvMakeBadge(verifiedJobSeeker ? 'Yes' : 'No', verifiedJobSeeker ? 'yes' : 'no'));
            tr.appendChild(tdVjs);

            tbody.appendChild(tr);
        });
    }

    // Update analytics UI
    function updateAnalyticsUI() {
        const totalAcc = analyticsData.employeeCountsOk ? analyticsData.totalEmployeeAccounts : analyticsData.totalJobseekers;
        const totalUsersEl = document.getElementById('totalUsers');
        if (totalUsersEl) {
            totalUsersEl.textContent = totalAcc;
        }
        const nsrpHeaderEl = document.getElementById('headerNsrpJobseekersCount');
        if (nsrpHeaderEl) {
            nsrpHeaderEl.textContent = analyticsData.employeeCountsOk
                ? analyticsData.nsrpSubmittedUsers
                : analyticsData.totalJobseekers;
        }
        const acctNoteEl = document.getElementById('headerAccountOnlyNote');
        if (acctNoteEl) {
            if (analyticsData.employeeCountsOk) {
                const p = analyticsData.accountsPendingNsrp;
                acctNoteEl.textContent = p > 0
                    ? p + ' user(s) signed up but have not submitted the NSRP form yet.'
                    : '';
            } else {
                acctNoteEl.textContent = 'Full account totals unavailable; total above matches NSRP records only.';
            }
        }
        document.getElementById('totalJobseekers').textContent = analyticsData.employeeCountsOk
            ? analyticsData.nsrpSubmittedUsers
            : analyticsData.totalJobseekers;
        const pendNsrpEl = document.getElementById('accountsPendingNsrp');
        if (pendNsrpEl) {
            pendNsrpEl.textContent = analyticsData.employeeCountsOk ? analyticsData.accountsPendingNsrp : '—';
        }
        const evEl = document.getElementById('analyticsEmailVerifiedCount');
        const unEl = document.getElementById('analyticsEmailUnverifiedCount');
        const vnEl = document.getElementById('analyticsVerifiedNsrpCount');
        const evNote = document.getElementById('analyticsEmailVerificationNote');
        if (evEl && unEl && vnEl) {
            if (analyticsData.employeeCountsOk) {
                evEl.textContent = analyticsData.emailVerifiedUsers;
                unEl.textContent = analyticsData.emailUnverifiedUsers;
                vnEl.textContent = analyticsData.verifiedEmailWithNsrpUsers;
            } else {
                evEl.textContent = '—';
                unEl.textContent = '—';
                vnEl.textContent = '—';
            }
        }
        if (evNote) {
            if (analyticsData.employeeCountsOk && !analyticsData.hasEmailVerifiedColumn) {
                evNote.style.display = 'block';
                evNote.textContent = 'Note: The database has no email_verified column on employee accounts; everyone is counted as verified and unverified is shown as 0. Verified job seekers still require a linked NSRP record.';
            } else if (!analyticsData.employeeCountsOk) {
                evNote.style.display = 'block';
                evNote.textContent = 'Employee account totals could not be loaded; counts above are unavailable.';
            } else {
                evNote.style.display = 'none';
                evNote.textContent = '';
            }
        }
        renderAnalyticsEmployeeAccountsTable();
        document.getElementById('pendingApplications').textContent = analyticsData.pendingApplications;
        document.getElementById('acceptedApplications').textContent = analyticsData.acceptedApplications;
        document.getElementById('rejectedApplications').textContent = analyticsData.rejectedApplications;
        document.getElementById('totalSkills').textContent = analyticsData.totalSkills;
        document.getElementById('barangayCount').textContent = analyticsData.barangayCount;
        document.getElementById('thisMonthRegistrations').textContent = analyticsData.thisMonthRegistrations;
        document.getElementById('lastMonthRegistrations').textContent = analyticsData.lastMonthRegistrations;
        
        // Calculate month-over-month change
        const change = analyticsData.lastMonthRegistrations > 0 ? 
            Math.round(((analyticsData.thisMonthRegistrations - analyticsData.lastMonthRegistrations) / analyticsData.lastMonthRegistrations) * 100) : 0;
        const changeElement = document.getElementById('monthOverMonthChange');
        changeElement.textContent = (change > 0 ? '+' : '') + change + '%';
        changeElement.style.color = change > 0 ? '#4caf50' : change < 0 ? '#f44336' : '#666';
        
        // Calculate success rate
        const totalProcessed = analyticsData.acceptedApplications + analyticsData.rejectedApplications;
        const successRate = totalProcessed > 0 ? Math.round((analyticsData.acceptedApplications / totalProcessed) * 100) : 0;
        document.getElementById('successRate').textContent = successRate + '%';
        
        // Mock average processing time
        document.getElementById('avgProcessingTime').textContent = Math.floor(Math.random() * 5) + 2;
        
        // Update skills list
        updateSkillsList();
    }

    // Update skills distribution list
    function updateSkillsList() {
        const skillsList = document.getElementById('skillsList');
        skillsList.innerHTML = '';
        
        if (analyticsData.skillsDistribution.length === 0) {
            skillsList.innerHTML = `
                <div class="analytics-skills-empty" style="grid-column: 1 / -1; text-align: center; padding: 40px; background: linear-gradient(135deg, #f5f5f5, #fafafa); border-radius: 12px; border: 2px dashed #bdbdbd;">
                    <div style="font-size: 3rem; color: #999; margin-bottom: 16px;">🛠️</div>
                    <div style="font-weight: 600; color: #666; margin-bottom: 8px; font-size: 1.1rem;">No Skills Data Available</div>
                    <div style="color: #999; font-size: 0.9rem;">Skills will appear here once jobseekers register with their skills</div>
                </div>
            `;
            return;
        }
        
        analyticsData.skillsDistribution.forEach(skill => {
            const pctBase = analyticsData.jobseekerSkillsTotal;
            const percentage = pctBase > 0 ? Math.round((skill.count / pctBase) * 100) : 0;
            const skillElement = document.createElement('div');
            skillElement.className = 'analytics-skill-tile';
            skillElement.style.cssText = `
                background: linear-gradient(135deg, #e3f2fd, #f0f4ff);
                border-radius: 12px;
                padding: 16px;
                text-align: center;
                border: 1px solid #bbdefb;
                transition: transform 0.2s ease;
            `;
            skillElement.innerHTML = `
                <div style="font-size: 1.5rem; margin-bottom: 8px;">🛠️</div>
                <div style="font-weight: 600; color: #1976d2; margin-bottom: 4px;">${skill.skill}</div>
                <div style="font-size: 2rem; font-weight: 700; color: #1976d2; margin-bottom: 4px;">${skill.count}</div>
                <div style="font-size: 0.8rem; color: #666;">${percentage}% of total</div>
            `;
            skillElement.addEventListener('mouseenter', () => {
                skillElement.style.transform = 'translateY(-4px)';
            });
            skillElement.addEventListener('mouseleave', () => {
                skillElement.style.transform = 'translateY(0)';
            });
            skillsList.appendChild(skillElement);
        });
    }

     // Create registration chart only
     function createRegistrationChart() {
         console.log('Creating registration chart with data:', analyticsData.monthlyTrends);
         
         // Ensure we have data for chart
         const monthlyData = analyticsData.monthlyTrends.length > 0 ? analyticsData.monthlyTrends : [
             { month: 'Jul', count: 0 },
             { month: 'Aug', count: 0 },
             { month: 'Sep', count: 0 },
             { month: 'Oct', count: 0 },
             { month: 'Nov', count: 0 },
             { month: 'Dec', count: 0 }
         ];
         
         console.log('Monthly data for chart:', monthlyData);
         console.log('Chart labels:', monthlyData.map(trend => trend.month));
         console.log('Chart data:', monthlyData.map(trend => trend.count));
         console.log('Max value in data:', Math.max(...monthlyData.map(trend => trend.count)));
         console.log('Min value in data:', Math.min(...monthlyData.map(trend => trend.count)));
         
         // Registration Trends Chart
         const registrationCtx = document.getElementById('registrationChart');
         if (registrationCtx) {
             console.log('Registration chart canvas found');
             try {
                 // Destroy existing chart if it exists
                 if (window.registrationChart && typeof window.registrationChart.destroy === 'function') {
                     console.log('Destroying existing registration chart');
                     window.registrationChart.destroy();
                 }
                 
                 console.log('Creating new registration chart...');
                 
                 // Determine chart type based on data points and filter type
                 let chartType = 'line';
                 
                 // Only use bar chart for single data points
                 if (monthlyData.length === 1) {
                     chartType = 'bar';
                 }
                 // For all other cases (including daily data), use line chart
                 else {
                     chartType = 'line';
                 }
                 
                 // Check if mobile device for smaller dots
                 const isMobile = window.innerWidth <= 768;
                 const pointRadius = isMobile ? 4 : 8;
                 const pointHoverRadius = isMobile ? 6 : 10;
                 
                 console.log('Using chart type:', chartType, 'for', monthlyData.length, 'data points');
                 console.log('Mobile device:', isMobile, 'Point radius:', pointRadius);
                 
                 window.registrationChart = new Chart(registrationCtx.getContext('2d'), {
                     type: chartType,
                     data: {
                         labels: monthlyData.map(trend => trend.month),
                         datasets: [{
                             label: 'New Registrations',
                             data: monthlyData.map(trend => trend.count),
                             borderColor: '#1976d2',
                             backgroundColor: chartType === 'bar' ? '#1976d2' : 'rgba(25, 118, 210, 0.1)',
                             borderWidth: chartType === 'bar' ? 0 : 3,
                             fill: chartType === 'line',
                             tension: chartType === 'line' ? 0.4 : 0,
                             pointBackgroundColor: '#1976d2',
                             pointBorderColor: '#fff',
                             pointBorderWidth: isMobile ? 2 : 3,
                             pointRadius: chartType === 'line' ? pointRadius : 0,
                             pointHoverRadius: chartType === 'line' ? pointHoverRadius : 0,
                             pointHoverBackgroundColor: '#1565c0',
                             pointHoverBorderColor: '#fff',
                             pointHoverBorderWidth: isMobile ? 2 : 3
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         resizeDelay: 0,
                         layout: {
                             padding: {
                                 top: 20,
                                 bottom: 20,
                                 left: 10,
                                 right: 10
                             }
                         },
                         animation: {
                             duration: 0
                         },
                         plugins: {
                             legend: {
                                 display: false
                             },
                             tooltip: {
                                 enabled: true,
                                 callbacks: {
                                     title: function(context) {
                                         return context[0].label;
                                     },
                                     label: function(context) {
                                         return `${context.parsed.y} registrations`;
                                     }
                                 }
                             }
                         },
                         scales: {
                             y: {
                                 beginAtZero: true,
                                 min: -0.5,
                                 max: Math.max(5, Math.max(...monthlyData.map(trend => trend.count)) + 1),
                                 grid: {
                                     color: 'rgba(0,0,0,0.1)',
                                     drawBorder: false
                                 },
                                 ticks: {
                                     callback: function(value) {
                                         return Number.isInteger(value) && value >= 0 ? value : null;
                                     },
                                     padding: 15,
                                     stepSize: 1
                                 }
                             },
                             x: {
                                 grid: {
                                     display: false
                                 },
                                 ticks: {
                                     maxRotation: monthlyData.length > 15 ? 45 : 0,
                                     font: {
                                         size: monthlyData.length > 15 ? 9 : 11
                                     },
                                     padding: 15
                                 }
                             }
                         }
                     }
                 });
                 console.log('Registration chart created successfully');
                 requestAnimationFrame(function() {
                     if (window.registrationChart && typeof window.registrationChart.resize === 'function') {
                         window.registrationChart.resize();
                     }
                 });
             } catch (error) {
                 console.error('Error creating registration chart:', error);
             }
         } else {
             console.error('Registration chart canvas not found');
             // Try to find the canvas element
             const canvas = document.querySelector('#registrationChart');
             if (canvas) {
                 console.log('Canvas element found:', canvas);
             } else {
                 console.error('Canvas element with id "registrationChart" not found in DOM');
             }
         }
     }

     // Create charts
     function createCharts() {
         if (chartsCreated) {
             console.log('Charts already created, skipping...');
             return;
         }
         
         console.log('Creating charts with data:', analyticsData);
         
         // Destroy existing charts if they exist
         if (window.registrationChart && typeof window.registrationChart.destroy === 'function') {
             window.registrationChart.destroy();
         }
         if (window.statusChart && typeof window.statusChart.destroy === 'function') {
             window.statusChart.destroy();
         }
         
         // Create registration chart
         createRegistrationChart();
         
         // Create status chart
         const statusData = [
             analyticsData.acceptedApplications || 0,
             analyticsData.pendingApplications || 0,
             analyticsData.rejectedApplications || 0
         ];
         
         console.log('Status data for chart:', statusData);
         
         // Application Status Pie Chart
         const statusCtx = document.getElementById('statusChart');
         if (statusCtx) {
             try {
                 window.statusChart = new Chart(statusCtx.getContext('2d'), {
                     type: 'doughnut',
                     data: {
                         labels: ['Accepted', 'Pending', 'Rejected'],
                         datasets: [{
                             data: statusData,
                             backgroundColor: [
                                 '#4caf50',
                                 '#ff9800',
                                 '#f44336'
                             ],
                             borderWidth: 0
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         resizeDelay: 0,
                         animation: {
                             duration: 0
                         },
                         plugins: {
                             legend: {
                                 position: 'bottom',
                                 labels: {
                                     padding: window.innerWidth <= 480 ? 10 : 20,
                                     boxWidth: window.innerWidth <= 480 ? 10 : 12,
                                     font: { size: window.innerWidth <= 480 ? 10 : 12 },
                                     usePointStyle: true
                                 }
                             }
                         }
                     }
                 });
                 console.log('Status chart created successfully');
                 requestAnimationFrame(function() {
                     if (window.statusChart && typeof window.statusChart.resize === 'function') {
                         window.statusChart.resize();
                     }
                 });
             } catch (error) {
                 console.error('Error creating status chart:', error);
             }
         } else {
             console.error('Status chart canvas not found');
         }
     }

     // Handle filter change and show/hide dropdowns
     function handleFilterChange() {
         const filterType = document.getElementById('trendFilter').value;
         const monthFilter = document.getElementById('monthFilter');
         const yearFilter = document.getElementById('yearFilter');
         
         if (filterType === 'monthly') {
             monthFilter.style.display = 'block';
             yearFilter.style.display = 'block';
         } else {
             monthFilter.style.display = 'none';
             yearFilter.style.display = 'none';
         }
         
         // Update chart if monthly filter is selected and both month and year are chosen
         if (filterType === 'monthly' && monthFilter.value && yearFilter.value) {
             updateTrendsChart();
         } else if (filterType !== 'monthly') {
             updateTrendsChart();
         }
     }
     
     // Populate year dropdown
     function populateYearDropdown() {
         const yearFilter = document.getElementById('yearFilter');
         const currentYear = new Date().getFullYear();
         
         // Clear existing options
         yearFilter.innerHTML = '<option value="">Select Year</option>';
         
         // Add years from current year down to 2020
         for (let year = currentYear; year >= 2020; year--) {
             const option = document.createElement('option');
             option.value = year;
             option.textContent = year;
             yearFilter.appendChild(option);
         }
     }
     
     // Update trends chart based on filter (automatic)
     async function updateTrendsChart() {
         const filterType = document.getElementById('trendFilter').value;
         const selectedMonth = document.getElementById('monthFilter').value;
         const selectedYear = document.getElementById('yearFilter').value;
         
         try {
             const jobseekerResponse = await fetch('jobseekers.php');
             const jobseekers = await jobseekerResponse.json();
             
             analyticsData.monthlyTrends = generateTrendsData(jobseekers, filterType, selectedMonth, selectedYear);
             
             console.log('Updated trends data:', analyticsData.monthlyTrends);
             
             // Update only the registration chart
             if (window.registrationChart) {
                 window.registrationChart.destroy();
             }
             
             // Create only the registration chart
             createRegistrationChart();
             
         } catch (error) {
             console.error('Error updating trends chart:', error);
         }
     }

     // Initialize analytics when page loads
     document.addEventListener('DOMContentLoaded', function() {
         if (isInitialized) return;
         isInitialized = true;
         
         console.log('DOM loaded, starting analytics...');
         console.log('Chart.js available:', typeof Chart !== 'undefined');
         
         // Update yearly option text to show current year
         const currentYear = new Date().getFullYear();
         const yearlyOption = document.getElementById('yearlyOption');
         if (yearlyOption) {
             yearlyOption.textContent = `Yearly (2020-${currentYear})`;
         }
         
         // Add event listeners for filter changes
         document.getElementById('trendFilter').addEventListener('change', handleFilterChange);
         document.getElementById('monthFilter').addEventListener('change', updateTrendsChart);
         document.getElementById('yearFilter').addEventListener('change', updateTrendsChart);
         
         // Populate year dropdown
         populateYearDropdown();
         
         // Check if Chart.js is loaded
         if (typeof Chart === 'undefined') {
             console.error('Chart.js not loaded!');
             // Try to load Chart.js dynamically
             const script = document.createElement('script');
             script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
             script.onload = function() {
                 console.log('Chart.js loaded dynamically');
                 fetchAnalyticsData();
             };
             script.onerror = function() {
                 console.error('Failed to load Chart.js');
             };
             document.head.appendChild(script);
         } else {
             console.log('Chart.js is available, fetching analytics data...');
             fetchAnalyticsData();
         }
     });

    // Also try to load when window is fully loaded
    window.addEventListener('load', function() {
        if (isInitialized) return;
        console.log('Window loaded, checking analytics...');
        if (analyticsData.totalJobseekers === 0 && !chartsCreated) {
            console.log('Retrying analytics fetch...');
            fetchAnalyticsData();
        }
    });

    // Prevent multiple initializations
    let isInitialized = false;
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
