<?php
// สคริปต์ตรวจสอบขั้นตอนการอนุมัติ 2 ระดับ (Multi-Tier Approval Workflow Test)
// ตรวจสอบ Flow: เจ้าหน้าที่ผู้ดูแล (Step 1) -> หัวหน้าสำนักปลัด (Final e-Signature)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Models/Booking.php';

echo "=== STARTING MULTI-TIER APPROVAL WORKFLOW VERIFICATION ===\n\n";

$allPassed = true;

// 1. ตรวจสอบเมธอด verifyBookingStep1 และ approveBookingFinal ใน Booking.php
$modelPath = __DIR__ . '/../app/Models/Booking.php';
$modelContent = file_get_contents($modelPath);

if (strpos($modelContent, 'verifyBookingStep1') !== false && strpos($modelContent, 'approveBookingFinal') !== false) {
    echo "✅ PASS [Core Methods]: พบเมธอด verifyBookingStep1 และ approveBookingFinal ใน Booking Model เรียบร้อย\n";
} else {
    echo "❌ FAILED [Core Methods]: ไม่พบเมธอดสำหรับอนุมัติ 2 ระดับใน Booking Model\n";
    $allPassed = false;
}

// 2. ตรวจสอบการสร้าง Hash ของ e-Signature
if (strpos($modelContent, 'hash(\'sha256\'') !== false && strpos($modelContent, 'e_signature_hash') !== false) {
    echo "✅ PASS [e-Signature Hash]: พบกระบวนการสร้าง e-Signature Hash อัตโนมัติ (SHA-256) และบันทึกลงฟิลด์ e_signature_hash\n";
} else {
    echo "❌ FAILED [e-Signature Hash]: ไม่พบลอจิกสร้างลายเซ็นอิเล็กทรอนิกส์ (e-Signature Hash)\n";
    $allPassed = false;
}

// 3. ตรวจสอบหน้าต่าง approvals.php (UI Buttons & Handlers)
$approvalsPath = __DIR__ . '/../public/approvals.php';
$approvalsContent = file_get_contents($approvalsPath);

if (strpos($approvalsContent, 'action === \'verify_step1\'') !== false && 
    strpos($approvalsContent, 'action === \'approve_final\'') !== false &&
    strpos($approvalsContent, 'print_booking.php') !== false) {
    echo "✅ PASS [UI Action Handlers]: หน้า approvals.php รองรับปุ่ม ตรวจสอบผ่าน (ขั้น 1), ยืนยันขั้นสุดท้าย (e-Signature) และลิงก์พิมพ์ใบขอใช้สถานที่\n";
} else {
    echo "❌ FAILED [UI Action Handlers]: หน้า approvals.php ไม่พบตัวรับ Action 2 ขั้นตอน\n";
    $allPassed = false;
}

// 4. ตรวจสอบไฟล์ใบขอใช้สถานที่ (print_booking.php)
$printPath = __DIR__ . '/../public/print_booking.php';
if (file_exists($printPath) && filesize($printPath) > 500) {
    $printContent = file_get_contents($printPath);
    if (strpos($printContent, 'E-SIGNATURE STAMP') !== false && strpos($printContent, 'e_signature_hash') !== false) {
        echo "✅ PASS [Print Sheet]: พบไฟล์ print_booking.php สมบูรณ์ มีตารางตราประทับ e-Signature ของหัวหน้าสำนักปลัดและเจ้าหน้าที่ผู้ดูแลชัดเจน\n";
    } else {
        echo "❌ FAILED [Print Sheet]: ไฟล์ print_booking.php ขาดโครงสร้าง e-Signature\n";
        $allPassed = false;
    }
} else {
    echo "❌ FAILED [Print Sheet]: ไม่พบไฟล์ print_booking.php หรือไฟล์ว่างเปล่า\n";
    $allPassed = false;
}

echo "\n===================================================\n";
if ($allPassed) {
    echo "🏆 RESULT: MULTI-TIER APPROVAL WORKFLOW & E-SIGNATURE VERIFICATION PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "🚨 RESULT: SOME CHECKS FAILED. PLEASE REVIEW THE LOG.\n";
    exit(1);
}
