<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Middleware\AuthMiddleware;

class BookingController
{
    /**
     * จัดการคำขอจองห้องประชุม (Store Booking Request)
     */
    public function store()
    {
        // 1. Authentication Check & CSRF Token Validation
        $currentUser = AuthMiddleware::requireActiveUser();
        
        $token = $_POST['_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดด้านความปลอดภัย (Invalid CSRF Token)";
            header("Location: index.php");
            exit;
        }

        // 2. Input Sanitize & Validation
        $roomId = (int)($_POST['room_id'] ?? 0);
        $meetingDate = trim($_POST['meeting_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $title = trim(htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8'));
        $attendeeCount = (int)($_POST['attendee_count'] ?? 0);
        $agenda = trim(htmlspecialchars($_POST['agenda'] ?? '', ENT_QUOTES, 'UTF-8'));

        if ($roomId <= 0 || empty($meetingDate) || empty($startTime) || empty($endTime) || empty($title)) {
            $_SESSION['error_message'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
            header("Location: index.php");
            exit;
        }

        // 3. ตรวจสอบวันที่ย้อนหลัง (Past Date Validation)
        if (strtotime($meetingDate) < strtotime(date('Y-m-d'))) {
            $_SESSION['error_message'] = "ไม่อนุญาตให้จองห้องประชุมย้อนหลัง";
            header("Location: index.php");
            exit;
        }

        // 4. ตรวจสอบเวลาเริ่มต้น-สิ้นสุด
        if (strtotime($startTime) >= strtotime($endTime)) {
            $_SESSION['error_message'] = "เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น";
            header("Location: index.php");
            exit;
        }

        // 5. ตรวจสอบการมีอยู่ของห้องและความจุ (Room Existence & Capacity Validation)
        $rooms = Booking::getAllRooms();
        $selectedRoom = null;
        foreach ($rooms as $r) {
            if ((int)$r['id'] === $roomId) {
                $selectedRoom = $r;
                break;
            }
        }

        if (!$selectedRoom || ($selectedRoom['status'] ?? 'active') !== 'active') {
            $_SESSION['error_message'] = "ห้องประชุมที่เลือกไม่มีอยู่ หรือถูกระงับการใช้งานชั่วคราว";
            header("Location: index.php");
            exit;
        }

        if ($attendeeCount > (int)($selectedRoom['capacity'] ?? 0)) {
            $_SESSION['error_message'] = "จำนวนผู้เข้าร่วม (" . $attendeeCount . " คน) เกินความจุของห้องประชุม (" . (int)$selectedRoom['capacity'] . " คน)";
            header("Location: index.php");
            exit;
        }

        // 6. ตรวจสอบเวลาซ้ำซ้อน (Overlapping Prevention)
        try {
            if (Booking::isTimeOverlapping($roomId, $meetingDate, $startTime, $endTime)) {
                $_SESSION['error_message'] = "ห้องประชุมที่เลือกมีการจองในช่วงเวลานี้แล้ว กรุณาเลือกช่วงเวลาหรือห้องอื่น";
                header("Location: index.php");
                exit;
            }

            // 7. บันทึกข้อมูล (Save to DB)
            $success = Booking::create([
                'room_id' => $roomId,
                'user_id' => $currentUser['id'] ?? 3,
                'title' => $title,
                'agenda' => $agenda,
                'meeting_date' => $meetingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'attendee_count' => $attendeeCount
            ]);

            if ($success) {
                $_SESSION['success_message'] = "ส่งคำขอจองห้องประชุมสำเร็จ! คำขอของคุณอยู่ในสถานะ 'รออนุมัติ (Pending)'";
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง";
            }
        } catch (\Exception $e) {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดของระบบฐานข้อมูล: " . $e->getMessage();
        }

        header("Location: index.php");
        exit;
    }
}
