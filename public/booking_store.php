<?php
require_once __DIR__ . '/../routes/web.php';

$currentUser = $_SESSION['user'] ?? null;
$userStatus = $currentUser['status'] ?? 'active';

if ($userStatus === 'inactive') {
    $_SESSION['error_message'] = 'บัญชีของคุณอยู่ระหว่างรอการอนุมัติจาก Approver ไม่สามารถทำการจองห้องประชุมได้ในขณะนี้';
    header("Location: dashboard.php");
    exit;
}

$_SESSION['success_message'] = 'คำขอจองห้องประชุมของคุณถูกส่งเรียบร้อยแล้ว และอยู่ระหว่างรอพิจารณาจาก Approver';
header("Location: dashboard.php");
exit;
