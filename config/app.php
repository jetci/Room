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

// Vercel Serverless Persistence Layer (Restore Session from Cookie)
if (!empty($_GET['sync_role'])) {
    $r = $_GET['sync_role'];
    $uData = null;
    if ($r === 'admin') {
        $uData = ['id' => 1, 'full_name' => 'คุณสมชาย บริหารดี (Admin)', 'email' => 'admin@wiang.go.th', 'role_name' => 'Admin', 'role_id' => 1, 'status' => 'active'];
    } elseif ($r === 'approver') {
        $uData = ['id' => 2, 'full_name' => 'คุณสมศรี อนุมัติการ (Approver)', 'email' => 'approver@wiang.go.th', 'role_name' => 'Approver', 'role_id' => 2, 'status' => 'active'];
    } elseif ($r === 'user') {
        $uData = ['id' => 3, 'full_name' => 'คุณใจดี พนักงานทั่วไป (User - Active)', 'email' => 'user@wiang.go.th', 'role_name' => 'User', 'role_id' => 3, 'status' => 'active'];
    } elseif ($r === 'executive') {
        $uData = ['id' => 4, 'full_name' => 'ท่านนายก ประเสริฐศักดิ์ (Executive)', 'email' => 'executive@wiang.go.th', 'role_name' => 'Executive', 'role_id' => 4, 'status' => 'active'];
    } elseif ($r === 'user_inactive') {
        $uData = ['id' => 5, 'full_name' => 'คุณรอคอย สมาชิกใหม่ (User - รออนุมัติ)', 'email' => 'waiting@wiang.go.th', 'role_name' => 'User', 'role_id' => 3, 'status' => 'inactive'];
    }
    if ($uData) {
        $_SESSION['user'] = $uData;
        setcookie('user_session_payload', json_encode($uData), time() + 86400, '/');
    }
} elseif (empty($_SESSION['user']) && !empty($_COOKIE['user_session_payload'])) {
    $decodedUser = json_decode($_COOKIE['user_session_payload'], true);
    if (is_array($decodedUser)) {
        $_SESSION['user'] = $decodedUser;
    }
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
