<?php

// Set default timezone as per Checklist
date_default_timezone_set('Asia/Bangkok');

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Global helper for CSRF token
if (!function_exists('csrf_token')) {
    function csrf_token() {
        return $_SESSION['csrf_token'] ?? '';
    }
}
