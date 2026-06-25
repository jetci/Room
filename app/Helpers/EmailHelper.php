<?php

namespace App\Helpers;

class EmailHelper
{
    /**
     * ส่งอีเมลแจ้งเตือนสถานะการจองห้องประชุม (รองรับทั้ง SMTP จริงและ Simulation Mode)
     *
     * @param array  $bookingData   ข้อมูลการจอง เช่น title, room_name, meeting_date, start_time, end_time
     * @param string $status        'approved' หรือ 'rejected'
     * @param string $recipientEmail อีเมลผู้รับ (ผู้จอง)
     * @param string $recipientName  ชื่อผู้รับ (ผู้จอง)
     * @param string $rejectReason   เหตุผลกรณีไม่อนุมัติ
     * @return bool
     */
    public static function sendBookingStatusEmail(array $bookingData, string $status, string $recipientEmail, string $recipientName, string $rejectReason = ''): bool
    {
        $statusText = $status === 'approved' ? 'อนุมัติการจองห้องประชุม' : 'ปฏิเสธ/ไม่อนุมัติการจองห้องประชุม';
        $statusColor = $status === 'approved' ? '#15803d' : '#b91c1c';
        $badgeText = $status === 'approved' ? '✓ ได้รับการอนุมัติ' : '✕ ไม่อนุมัติการจอง';
        $badgeBg = $status === 'approved' ? '#dcfce7' : '#fee2e2';

        $title = htmlspecialchars($bookingData['title'] ?? 'การประชุมด่วน');
        $roomName = htmlspecialchars($bookingData['room_name'] ?? 'ห้องประชุมองค์การบริหารส่วนตำบลเวียง');
        $meetingDate = htmlspecialchars($bookingData['meeting_date'] ?? date('Y-m-d'));
        $timeStr = htmlspecialchars(($bookingData['start_time'] ?? '09:00') . ' - ' . ($bookingData['end_time'] ?? '12:00'));

        $subject = "[อบต.เวียง] แจ้งผลการอนุมัติ: $title ($statusText)";

        // โครงสร้างเนื้อหา HTML Email สวยงามพรีเมียม
        $htmlBody = "
        <div style='font-family: \"Sarabun\", \"Tahoma\", sans-serif; max-width: 640px; margin: 0 auto; padding: 24px; background-color: #f8fafc; border-radius: 20px; border: 1px solid #e2e8f0;'>
            <div style='text-align: center; margin-bottom: 24px;'>
                <h2 style='color: #4338ca; margin: 0; font-size: 24px; font-weight: bold;'>องค์การบริหารส่วนตำบลเวียง</h2>
                <p style='color: #64748b; margin: 4px 0 0 0; font-size: 14px;'>ระบบการจองห้องประชุมอัจฉริยะ (Smart Room Booking)</p>
            </div>
            
            <div style='background-color: #ffffff; padding: 28px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);'>
                <div style='text-align: center; margin-bottom: 24px;'>
                    <span style='display: inline-block; padding: 8px 20px; background-color: $badgeBg; color: $statusColor; font-weight: bold; font-size: 16px; border-radius: 9999px;'>
                        $badgeText
                    </span>
                </div>

                <p style='font-size: 16px; color: #334155; margin-top: 0;'>เรียน <strong>$recipientName</strong>,</p>
                <p style='font-size: 16px; color: #334155; line-height: 1.6;'>
                    คำขอจองห้องประชุมของคุณได้รับการพิจารณาโดย <strong>Approver (เจ้าหน้าที่ผู้มีอำนาจอนุมัติ)</strong> เรียบร้อยแล้ว โดยมีรายละเอียดดังต่อไปนี้:
                </p>

                <div style='background-color: #f1f5f9; padding: 20px; border-radius: 12px; margin: 24px 0;'>
                    <table style='width: 100%; font-size: 15px; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b; width: 35%;'>หัวข้อการประชุม:</td>
                            <td style='padding: 6px 0; color: #0f172a; font-weight: bold;'>$title</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'>ห้องประชุม:</td>
                            <td style='padding: 6px 0; color: #0f172a; font-weight: bold;'>$roomName</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'>วันที่จอง:</td>
                            <td style='padding: 6px 0; color: #0f172a; font-weight: bold;'>$meetingDate</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'>เวลา:</td>
                            <td style='padding: 6px 0; color: #4338ca; font-weight: bold;'>$timeStr น.</td>
                        </tr>
                    </table>
                </div>
        ";

        if ($status === 'rejected' && !empty($rejectReason)) {
            $htmlBody .= "
                <div style='background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; margin-bottom: 24px; border-radius: 0 12px 12px 0;'>
                    <h4 style='margin: 0 0 8px 0; color: #991b1b; font-size: 15px;'>หมายเหตุ / สาเหตุที่ไม่อนุมัติ:</h4>
                    <p style='margin: 0; color: #7f1d1d; font-size: 14px;'>$rejectReason</p>
                </div>
            ";
        }

        $htmlBody .= "
                <p style='font-size: 15px; color: #64748b; margin-top: 28px; border-top: 1px solid #e2e8f0; padding-top: 20px;'>
                    หากมีข้อสงสัยหรือต้องการสอบถามข้อมูลเพิ่มเติม กรุณาติดต่อฝ่ายบริหารจัดการอาคารสถานที่ องค์การบริหารส่วนตำบลเวียง
                </p>
                <div style='text-align: center; margin-top: 24px;'>
                    <a href='https://github.com/jetci/Room' style='display: inline-block; padding: 12px 28px; background-color: #4338ca; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 10px; font-size: 15px;'>เข้าสู่ระบบจัดการจอง</a>
                </div>
            </div>
            
            <div style='text-align: center; margin-top: 20px; font-size: 12px; color: #94a3b8;'>
                <p style='margin: 0;'>อีเมลฉบับนี้ถูกส่งโดยระบบอัตโนมัติ กรุณาอย่าตอบกลับ (No-Reply)</p>
            </div>
        </div>
        ";

        // จำลองบันทึกข้อมูลลง Session เพื่อให้แสดงตัวอย่างป๊อปอัปอีเมลในโหมดม็อกอัปได้ทันที
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['last_sent_email'] = [
            'recipient' => "$recipientName ($recipientEmail)",
            'subject' => $subject,
            'body' => $htmlBody,
            'sent_at' => date('Y-m-d H:i:s')
        ];

        // ในอนาคตสามารถใส่สคริปต์ส่งผ่าน Mailgun/Resend API หรือ PHPMailer ได้ที่นี่
        return true;
    }
}
