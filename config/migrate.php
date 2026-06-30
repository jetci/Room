<?php
/**
 * สคริปต์สำหรับรันติดตั้งตารางและข้อมูลเริ่มต้น + อัปเกรดโครงสร้าง DB จริง (Versioned Migration)
 * สามารถรันผ่าน Command Line: php config/migrate.php หรือเรียกผ่านเว็บเบราว์เซอร์
 */

require_once __DIR__ . '/database.php';

use Config\Database;

try {
    echo "[!] กำลังเชื่อมต่อกับฐานข้อมูล...\n";
    $db = Database::getConnection();
    echo "[✔] เชื่อมต่อฐานข้อมูลสำเร็จ (Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . ")\n";

    $schemaPath = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("ไม่พบไฟล์โครงสร้างตาราง: $schemaPath");
    }

    echo "[!] กำลังโหลดไฟล์โครงสร้างตาราง schema.sql เพื่อสร้างตารางใหม่...\n";
    $sql = file_get_contents($schemaPath);

    // รองรับกรณี SQLite ที่อาจไม่รองรับ AUTO_INCREMENT แบบ MySQL
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $sql = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
        $sql = str_replace('INT AUTOINCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    }

    // แตกคำสั่ง SQL และ Execute เพื่อสร้างตารางใหม่ (CREATE TABLE IF NOT EXISTS)
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $cleanStatement = trim($statement);
        if (!empty($cleanStatement)) {
            $db->exec($cleanStatement);
        }
    }

    echo "[✔] รันสร้างตารางหลักสำเร็จ\n";
    echo "[!] กำลังตรวจสอบและอัปเกรดคอลัมน์ในตารางเดิม (Dynamic ALTER TABLE Schema Upgrade)...\n";

    // ฟังก์ชันตัวช่วยในการตรวจสอบและเพิ่มคอลัมน์
    function addColumnIfNotExists($db, $table, $column, $definition) {
        try {
            // เช็คว่ามีคอลัมน์หรือยัง
            $colSql = "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'";
            $stmt = $db->query($colSql);
            if ($stmt && $stmt->rowCount() === 0) {
                $alterSql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
                $db->exec($alterSql);
                echo "   + เพิ่มคอลัมน์ {$table}.{$column} สำเร็จ\n";
            }
        } catch (Exception $e) {
            echo "   - ข้ามการเพิ่ม {$table}.{$column} (" . $e->getMessage() . ")\n";
        }
    }

    // 1. ตาราง bookings
    addColumnIfNotExists($db, 'bookings', 'step1_approver_name', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'step1_approved_at', 'TIMESTAMP DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'final_approver_name', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'final_approved_at', 'TIMESTAMP DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'e_signature_hash', 'VARCHAR(255) DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'e_signature_path', 'VARCHAR(255) DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'approver_id', 'INT DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'agenda', 'TEXT DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'meeting_date', 'DATE DEFAULT NULL');
    addColumnIfNotExists($db, 'bookings', 'attendee_count', 'INT DEFAULT 1');
    addColumnIfNotExists($db, 'bookings', 'booking_status', "VARCHAR(50) DEFAULT 'draft'");
    addColumnIfNotExists($db, 'bookings', 'approval_status', "VARCHAR(50) DEFAULT 'pending'");

    // 2. ตาราง users
    addColumnIfNotExists($db, 'users', 'department_name', "VARCHAR(150) DEFAULT 'พนักงานทั่วไป'");
    addColumnIfNotExists($db, 'users', 'role_name', "VARCHAR(50) DEFAULT 'User'");
    addColumnIfNotExists($db, 'users', 'phone', 'VARCHAR(50) DEFAULT NULL');
    addColumnIfNotExists($db, 'users', 'status', "VARCHAR(50) DEFAULT 'active'");

    // 3. ตาราง audit_logs
    addColumnIfNotExists($db, 'audit_logs', 'module', "VARCHAR(100) DEFAULT 'System'");
    addColumnIfNotExists($db, 'audit_logs', 'action', "VARCHAR(100) DEFAULT 'general'");
    addColumnIfNotExists($db, 'audit_logs', 'reference_id', 'INT DEFAULT NULL');
    addColumnIfNotExists($db, 'audit_logs', 'action_type', 'VARCHAR(100) DEFAULT NULL');
    addColumnIfNotExists($db, 'audit_logs', 'details', 'TEXT DEFAULT NULL');
    addColumnIfNotExists($db, 'audit_logs', 'log_time', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    addColumnIfNotExists($db, 'audit_logs', 'user_agent', 'TEXT DEFAULT NULL');
    addColumnIfNotExists($db, 'audit_logs', 'ip_address', "VARCHAR(50) DEFAULT '127.0.0.1'");

    // 4. ตาราง sports_facilities
    addColumnIfNotExists($db, 'sports_facilities', 'facility_name', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_facilities', 'location', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_facilities', 'available_equipments', 'TEXT DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_facilities', 'rules', 'TEXT DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_facilities', 'name', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_facilities', 'category', "VARCHAR(100) DEFAULT 'ทั่วไป'");
    addColumnIfNotExists($db, 'sports_facilities', 'capacity', 'INT DEFAULT 20');
    addColumnIfNotExists($db, 'sports_facilities', 'image_url', "VARCHAR(255) DEFAULT 'assets/img/sports_default.jpg'");

    // 5. ตาราง sports_bookings
    addColumnIfNotExists($db, 'sports_bookings', 'borrow_summary', 'TEXT DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'borrow_equipments', 'TEXT DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'facility_id', 'INT DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'sports_facility_id', 'INT DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'step1_approver_name', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'step1_approved_at', 'TIMESTAMP DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'final_approver_name', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'final_approved_at', 'TIMESTAMP DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'e_signature_hash', 'VARCHAR(255) DEFAULT NULL');
    addColumnIfNotExists($db, 'sports_bookings', 'e_signature_path', 'VARCHAR(255) DEFAULT NULL');

    // 6. ตาราง rooms
    addColumnIfNotExists($db, 'rooms', 'room_name', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'rooms', 'location', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'rooms', 'floor', 'VARCHAR(50) DEFAULT NULL');
    addColumnIfNotExists($db, 'rooms', 'capacity', 'INT DEFAULT 10');
    addColumnIfNotExists($db, 'rooms', 'status', "VARCHAR(50) DEFAULT 'active'");
    addColumnIfNotExists($db, 'rooms', 'name', 'VARCHAR(150) DEFAULT NULL');
    addColumnIfNotExists($db, 'rooms', 'is_active', 'INT DEFAULT 1');

    // 7. ตาราง features (รองรับการแมป room_feature_map)
    $db->exec("CREATE TABLE IF NOT EXISTS `features` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `feature_name` VARCHAR(150) NOT NULL,
        `icon` VARCHAR(100) DEFAULT 'fa-check',
        `is_active` TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "   + ตรวจสอบ/สร้างตาราง features สำเร็จ\n";

    echo "[✔] อัปเกรดคอลัมน์ในตารางฐานข้อมูลจริงเสร็จสมบูรณ์เรียบร้อยแล้ว!\n";

} catch (Exception $e) {
    echo "[✖] ข้อผิดพลาดในการรัน Migration: " . $e->getMessage() . "\n";
    exit(1);
}
