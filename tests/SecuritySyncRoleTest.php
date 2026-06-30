<?php
// สคริปต์ทดสอบการปิดช่องโหว่ sync_role และ cookie payload

// ตั้งค่าจำลอง HTTP Request
$_GET['sync_role'] = 'admin';
$_COOKIE['user_session_payload'] = json_encode(['id' => 1, 'role_name' => 'Admin']);

// โหลด config/app.php
require_once __DIR__ . '/../config/app.php';

$errors = [];

// 1. ตรวจสอบว่า $_SESSION['user'] ต้องไม่ถูกเซ็ตจาก $_GET['sync_role'] หรือ $_COOKIE
if (!empty($_SESSION['user'])) {
    $errors[] = "❌ ช่องโหว่ sync_role หรือ cookie payload ยังทำงานอยู่! (\$_SESSION['user'] ถูกเซ็ต)";
} else {
    echo "✅ PASS: \$_SESSION['user'] ไม่ถูกแทรกแซงจาก ?sync_role=admin หรือ Cookie\n";
}

// 2. ตรวจสอบตั้งค่าความปลอดภัยของ Session
if (ini_get('session.cookie_httponly') != '1') {
    $errors[] = "❌ session.cookie_httponly ไม่ได้ตั้งเป็น 1";
} else {
    echo "✅ PASS: session.cookie_httponly = 1 (ปลอดภัยจาก XSS Cookie Theft)\n";
}

if (ini_get('session.cookie_samesite') !== 'Lax' && ini_get('session.cookie_samesite') !== 'Strict') {
    $errors[] = "❌ session.cookie_samesite ไม่ได้ตั้งเป็น Lax หรือ Strict";
} else {
    echo "✅ PASS: session.cookie_samesite = " . ini_get('session.cookie_samesite') . " (ป้องกัน CSRF เบื้องต้น)\n";
}

if (!empty($errors)) {
    echo "\n=== TEST FAILED ===\n";
    foreach ($errors as $err) {
        echo $err . "\n";
    }
    exit(1);
} else {
    echo "\n=== ALL TESTS PASSED SUCCESSFULLY ===\n";
    exit(0);
}
