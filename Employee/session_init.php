<?php
// Centralized session bootstrap for Employee pages.
// Prevents warnings when default PHP session path is not writable.

if (session_status() === PHP_SESSION_NONE) {
    $customSessionPath = __DIR__ . '/../tmp/sessions';

    if (!is_dir($customSessionPath)) {
        @mkdir($customSessionPath, 0777, true);
    }

    if (is_dir($customSessionPath) && is_writable($customSessionPath)) {
        session_save_path($customSessionPath);
    }

    @session_start();
}

