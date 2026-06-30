<?php

// Set default timezone as per Checklist
date_default_timezone_set('Asia/Bangkok');

// Start Session with Secure Cookie Settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
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

// ==========================================================================
// 🚀 GLOBAL ORG SETTINGS PERSISTENCE LAYER (Permanent File Storage)
// ==========================================================================
$settingsStorageDir = __DIR__ . '/../storage/';
$settingsStoragePath = $settingsStorageDir . 'settings.json';
if (file_exists($settingsStoragePath)) {
    $persistedSettings = @json_decode(file_get_contents($settingsStoragePath), true);
    if (is_array($persistedSettings)) {
        $_SESSION['org_logo'] = $persistedSettings['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
        $_SESSION['org_name'] = $persistedSettings['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
        $_SESSION['org_address'] = $persistedSettings['org_address'] ?? 'อำเภอเชียงคำ จังหวัดพะเยา 56110';
        $_SESSION['org_tax_id'] = $persistedSettings['org_tax_id'] ?? '0994000123456';
        $_SESSION['org_phone'] = $persistedSettings['org_phone'] ?? '054-456789';
    }
}
