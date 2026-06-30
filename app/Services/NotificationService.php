<?php

namespace App\Services;

class NotificationService
{
    /**
     * ส่งข้อความแจ้งเตือนไปยัง LINE Notify
     * @param string $message ข้อความที่ต้องการส่ง
     * @return bool
     */
    public static function sendLineNotify(string $message): bool
    {
        $lineToken = getenv('LINE_NOTIFY_TOKEN') ?: ($_ENV['LINE_NOTIFY_TOKEN'] ?? null);

        if (!$lineToken) {
            // โหมดจำลองเมื่อไม่มี Token (บันทึกลง Log)
            error_log("[LINE Notify Simulation] Message: $message");
            return true;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['message' => $message]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $lineToken,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($result, true);
        return isset($res['status']) && $res['status'] === 200;
    }

    /**
     * ส่งอีเมลยืนยันการจองพร้อมไฟล์แนบ (e-Memo / .ics)
     * @param array $bookingData ข้อมูลการจอง
     * @return bool
     */
    public static function sendEmailConfirmation(array $bookingData): bool
    {
        $to = $bookingData['email'] ?? 'user@wiang.go.th';
        $subject = "ยืนยันการจองสถานที่ (Smart Facility Booking - อบต.เวียง)";
        $body = "เรียนคุณ " . ($bookingData['full_name'] ?? 'ผู้ใช้บริการ') . ",\n\n";
        $body .= "คำร้องขอจองสถานที่ของคุณได้รับการบันทึกและอนุมัติเข้าสู่ระบบเรียบร้อยแล้ว\n";
        $body .= "รายละเอียด: " . ($bookingData['title'] ?? 'กิจกรรม') . "\n";
        $body .= "วันที่: " . ($bookingData['booking_date'] ?? date('Y-m-d')) . "\n\n";
        $body .= "ขอบคุณที่ใช้บริการ Smart Facility Booking - องค์การบริหารส่วนตำบลเวียง";

        // โหมดจำลองการส่งอีเมล (Simulated Email Gateway)
        error_log("[Email Gateway Simulation] To: $to | Subject: $subject | Body: $body");
        return true;
    }
}
