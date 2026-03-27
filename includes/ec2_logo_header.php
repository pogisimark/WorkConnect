<?php
function wc_should_render_ec2_logo_header(): bool
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '' || $host === 'localhost' || str_starts_with($host, '127.') || $host === '::1') {
        return false;
    }

    if (str_contains($host, 'local') || str_contains($host, 'xampp')) {
        return false;
    }

    return true;
}

function wc_render_ec2_logo_header(): void
{
    if (!wc_should_render_ec2_logo_header()) {
        return;
    }
    ?>
    <style>
        body {
            padding-top: 68px !important;
        }
        .wc-ec2-logo-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 68px;
            background: #1a3876;
            z-index: 2147483000;
            display: flex;
            align-items: center;
            padding: 10px 18px;
            box-sizing: border-box;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .wc-ec2-logo-header img {
            height: 46px;
            width: auto;
            display: block;
        }
    </style>
    <div class="wc-ec2-logo-header">
        <img src="/assets/image/PESO Logo circle.png" alt="WorkConnect Logo">
    </div>
    <?php
}
