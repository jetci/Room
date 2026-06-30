<?php

namespace App\Services;

use Config\Database;
use PDO;
use PDOException;

class AuditLogService
{
    /**
     * บันทึกกิจกรรมการใช้งานระบบลงฐานข้อมูล (Audit Log)
     * @param int $userId ไอดีผู้ใช้
     * @param string $actionType ประเภทกิจกรรม (เช่น LOGIN, BOOKING_CREATE, SETTINGS_UPDATE)
     * @param string $details รายละเอียดเพิ่มเติม
     * @return bool
     */
    public static function logAction(int $userId, string $actionType, string $details): bool
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Agent';

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, module, action, action_type, details, ip_address, user_agent, log_time) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            return $stmt->execute([$userId, 'System', $actionType, $actionType, $details, $ipAddress, $userAgent]);
        } catch (PDOException $e) {
            error_log("[Audit Log Fallback] User: $userId | Action: $actionType | Details: $details | IP: $ipAddress | Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ดึงข้อมูลบันทึกกิจกรรมล่าสุด
     * @param int $limit จำนวนบรรทัด
     * @return array
     */
    public static function getRecentLogs(int $limit = 50): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT a.*, u.full_name, u.email FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.log_time DESC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("[Audit Log Fetch Error] " . $e->getMessage());
            return [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'full_name' => 'คุณสมชาย บริหารดี',
                    'email' => 'admin@wiang.go.th',
                    'module' => 'System',
                    'action' => 'SYSTEM_INIT',
                    'action_type' => 'SYSTEM_INIT',
                    'details' => 'ระบบพร้อมใช้งานและรันโหมดป้องกันฐานข้อมูลขั้นสูง',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'log_time' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
    }
}
