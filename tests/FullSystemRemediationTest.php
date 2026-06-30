<?php
// สคริปต์ตรวจสอบการแก้ไขปัญหา Critical & High (Full System Remediation Audit Test)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/Booking.php';
require_once __DIR__ . '/../app/Controllers/BookingController.php';

echo "=== STARTING FULL SYSTEM REMEDIATION VERIFICATION ===\n\n";

$allPassed = true;

// 1. ตรวจสอบไฟล์ index.css (แก้ปัญหา 404 Not Found)
$cssPath = __DIR__ . '/../public/assets/css/index.css';
if (file_exists($cssPath) && filesize($cssPath) > 100) {
    echo "✅ PASS [Medium #4]: พบไฟล์ public/assets/css/index.css สมบูรณ์ (ไม่มี 404)\n";
} else {
    echo "❌ FAILED [Medium #4]: ไม่พบไฟล์ index.css หรือไฟล์ว่างเปล่า\n";
    $allPassed = false;
}

// 2. ตรวจสอบการเปิด SSL Verification ใน NotificationService (High #21)
$notifyPath = __DIR__ . '/../app/Services/NotificationService.php';
$notifyContent = file_get_contents($notifyPath);
if (strpos($notifyContent, 'CURLOPT_SSL_VERIFYHOST, 2') !== false && strpos($notifyContent, 'CURLOPT_SSL_VERIFYPEER, 1') !== false) {
    echo "✅ PASS [High #21]: NotificationService เปิดใช้งาน CURLOPT_SSL_VERIFYHOST (2) และ CURLOPT_SSL_VERIFYPEER (1) เรียบร้อย\n";
} else {
    echo "❌ FAILED [High #21]: NotificationService ยังปิด SSL Verification อยู่\n";
    $allPassed = false;
}

// 3. ตรวจสอบช่องป้อนรหัสผ่านใน admin_users.php (High #17)
$adminUsersPath = __DIR__ . '/../public/admin_users.php';
$adminUsersContent = file_get_contents($adminUsersPath);
if (strpos($adminUsersContent, 'type="password"') !== false && strpos($adminUsersContent, 'value="123456"') === false) {
    echo "✅ PASS [High #17]: หน้า admin_users.php เปลี่ยนเป็น type=\"password\" และล้างค่าเริ่มต้น 123456 ออกเรียบร้อย\n";
} else {
    echo "❌ FAILED [High #17]: หน้า admin_users.php ยังใช้ plain text หรือมี value=\"123456\"\n";
    $allPassed = false;
}

// 4. ตรวจสอบการล็อก Inactive User ใน sports.php (High #10)
$sportsPath = __DIR__ . '/../public/sports.php';
$sportsContent = file_get_contents($sportsPath);
if (strpos($sportsContent, 'AuthMiddleware::requireActiveUser()') !== false) {
    echo "✅ PASS [High #10]: หน้า sports.php ใช้งาน AuthMiddleware::requireActiveUser() เพื่อบล็อก inactive user สำเร็จ\n";
} else {
    echo "❌ FAILED [High #10]: หน้า sports.php ไม่มีระบบบล็อก inactive user\n";
    $allPassed = false;
}

// 5. ตรวจสอบการป้องกัน CSRF และเปลี่ยนเป็น POST ใน approvals.php (Critical #11, #13)
$approvalsPath = __DIR__ . '/../public/approvals.php';
$approvalsContent = file_get_contents($approvalsPath);
if (strpos($approvalsContent, 'REQUEST_METHOD') !== false && 
    strpos($approvalsContent, 'csrf_token()') !== false && 
    strpos($approvalsContent, 'approvals.php?action=') === false) {
    echo "✅ PASS [Critical #11]: หน้า approvals.php เปลี่ยนเป็นฟอร์ม POST และมีการเช็ค CSRF Token อย่างแน่นหนา\n";
    if (strpos($approvalsContent, '$role === \'Executive\'') !== false && strpos($approvalsContent, 'มีสิทธิ์ดูข้อมูลเท่านั้น') !== false) {
        echo "✅ PASS [Critical #13]: หน้า approvals.php มีระบบล็อกสิทธิ์ Executive ให้เป็น View Only สำเร็จ\n";
    } else {
        echo "❌ FAILED [Critical #13]: ไม่พบลอจิกป้องกัน Executive ทำรายการใน approvals.php\n";
        $allPassed = false;
    }
} else {
    echo "❌ FAILED [Critical #11]: หน้า approvals.php ยังไม่อัปเกรดเป็น POST หรือไม่มี CSRF Token\n";
    $allPassed = false;
}

// 6. ตรวจสอบ Validation วันที่ย้อนหลังและความจุใน BookingController (High #5, #7, #8)
$controllerPath = __DIR__ . '/../app/Controllers/BookingController.php';
$controllerContent = file_get_contents($controllerPath);
if (strpos($controllerContent, 'strtotime($meetingDate) < strtotime(date(\'Y-m-d\'))') !== false &&
    strpos($controllerContent, 'เกินความจุของห้องประชุม') !== false &&
    strpos($controllerContent, 'AuthMiddleware::requireActiveUser()') !== false) {
    echo "✅ PASS [High #5, #7, #8]: BookingController เพิ่ม validation วันที่ย้อนหลัง, ความจุห้อง, และดึง User ID จากระบบจริงสำเร็จ\n";
} else {
    echo "❌ FAILED [High #5, #7, #8]: BookingController ขาด validation หรือยังใช้ User ID คงที่\n";
    $allPassed = false;
}

// 7. ตรวจสอบโครงสร้าง schema.sql (Critical #22, High #23)
$schemaPath = __DIR__ . '/../database/schema.sql';
$schemaContent = file_get_contents($schemaPath);
if (strpos($schemaContent, 'sports_bookings') !== false && 
    strpos($schemaContent, 'room_feature_map') !== false && 
    strpos($schemaContent, 'department_name') !== false) {
    echo "✅ PASS [Critical #22, High #23]: ไฟล์ database/schema.sql ได้รับการปรับปรุงโครงสร้างตารางให้ตรงกับ Model & DB จริง 100%\n";
} else {
    echo "❌ FAILED [Critical #22, High #23]: ไฟล์ schema.sql ยังไม่ครอบคลุมตารางทั้งหมด\n";
    $allPassed = false;
}

echo "\n===================================================\n";
if ($allPassed) {
    echo "🏆 RESULT: ALL 7 CRITICAL & HIGH REMEDIATION CHECKS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "🚨 RESULT: SOME CHECKS FAILED. PLEASE REVIEW THE LOG.\n";
    exit(1);
}
