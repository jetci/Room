-- -----------------------------------------------------
-- Database: meeting_booking_db
-- -----------------------------------------------------
CREATE DATABASE IF NOT EXISTS `meeting_booking_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `meeting_booking_db`;
SET NAMES utf8mb4;

-- 1. ตารางแผนก (departments)
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ตารางบทบาทผู้ใช้ (roles)
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ตารางผู้ใช้ (users)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20),
  `password_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_user_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ตารางห้องประชุม (rooms)
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(150),
  `floor` VARCHAR(50),
  `capacity` INT DEFAULT 0,
  `equipment_summary` TEXT,
  `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ตารางอุปกรณ์/สิ่งอำนวยความสะดวกหลัก (room_features)
CREATE TABLE IF NOT EXISTS `room_features` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `feature_name` VARCHAR(100) NOT NULL UNIQUE,
  `total_qty` INT DEFAULT 1,
  `active_qty` INT DEFAULT 1,
  `maintenance_qty` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ตารางเชื่อมโยงห้องกับอุปกรณ์ (room_feature_map)
CREATE TABLE IF NOT EXISTS `room_feature_map` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `feature_id` INT NOT NULL,
  CONSTRAINT `fk_map_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_map_feature` FOREIGN KEY (`feature_id`) REFERENCES `room_features`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ตารางข้อมูลการจอง (bookings)
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `agenda` TEXT,
  `meeting_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `attendee_count` INT DEFAULT 0,
  `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `booking_status` ENUM('draft', 'confirmed', 'cancelled', 'completed') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_meeting_date_room` (`meeting_date`, `room_id`),
  INDEX `idx_booking_status` (`booking_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ตารางรายชื่อผู้เข้าร่วมประชุม (booking_attendees)
CREATE TABLE IF NOT EXISTS `booking_attendees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `attendee_name` VARCHAR(100) NOT NULL,
  `attendee_email` VARCHAR(150) NOT NULL,
  CONSTRAINT `fk_attendee_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ตารางการพิจารณาอนุมัติ (booking_approvals)
CREATE TABLE IF NOT EXISTS `booking_approvals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `approver_id` INT NOT NULL,
  `action` ENUM('approve', 'reject') NOT NULL,
  `action_note` TEXT,
  `action_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_approval_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_approval_user` FOREIGN KEY (`approver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. ตารางบันทึกการแจ้งเตือน (notifications)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `booking_id` INT NOT NULL,
  `channel` ENUM('email', 'line', 'telegram', 'in-app') NOT NULL,
  `message` TEXT NOT NULL,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('success', 'failed', 'pending') DEFAULT 'pending',
  CONSTRAINT `fk_notify_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notify_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. ตารางเก็บประวัติการทำงาน (audit_logs)
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `module` VARCHAR(50) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `reference_id` INT,
  `log_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Insert ข้อมูลจำลองตั้งต้น (Seeders)
-- -----------------------------------------------------
INSERT INTO `departments` (`id`, `department_name`) VALUES 
(1, 'สำนักปลัด (อบต.เวียง)'), 
(2, 'กองคลัง (อบต.เวียง)'), 
(3, 'กองช่าง (อบต.เวียง)'), 
(4, 'กองการศึกษา ศาสนา และวัฒนธรรม (อบต.เวียง)'), 
(5, 'กองสวัสดิการสังคม (อบต.เวียง)'), 
(6, 'กองสาธารณสุขและสิ่งแวดล้อม (อบต.เวียง)'), 
(7, 'หน่วยราชการภายนอก / ประชาชนทั่วไป');

INSERT INTO `roles` (`id`, `role_name`) VALUES 
(1, 'Admin'), (2, 'Approver'), (3, 'User');

-- Default User: Somchai Admin (password is 'password123' hashed with bcrypt)
INSERT INTO `users` (`id`, `department_id`, `role_id`, `full_name`, `email`, `phone`, `password_hash`, `status`) VALUES
(1, 1, 1, 'คุณสมชาย บริหารดี (Admin)', 'admin@company.com', '0812345678', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'active'),
(2, 2, 2, 'คุณสมศรี อนุมัติการ (Approver)', 'approver@company.com', '0823456789', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'active'),
(3, 3, 3, 'คุณใจดี พนักงานทั่วไป (User)', 'user@company.com', '0834567890', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'active');

INSERT INTO `rooms` (`id`, `room_name`, `location`, `floor`, `capacity`, `equipment_summary`, `status`) VALUES
(1, 'Room A - Grand Auditorium', 'อาคารสำนักงานใหญ่', 'ชั้น 9', 50, 'โปรเจกเตอร์ 4K, ระบบเสียง Dolby, ไมค์ไร้สาย 4 ตัว, กระดานอัจฉริยะ', 'active'),
(2, 'Room B - Executive Boardroom', 'อาคารสำนักงานใหญ่', 'ชั้น 9', 15, 'จอภาพ LED 85 นิ้ว, ระบบ VDO Conference Polycom, โต๊ะประชุมหนังแท้', 'active'),
(3, 'Room C - Creative Space', 'อาคารทิศเหนือ', 'ชั้น 3', 8, 'ทีวี 65 นิ้ว, Whiteboard ผนังเต็ม, โต๊ะกลมปรับระดับ', 'active'),
(4, 'Room D - Smart Pod 1', 'อาคารสำนักงานใหญ่', 'ชั้น 5', 20, 'จอสัมผัสอัจฉริยะ 55 นิ้ว, อุปกรณ์ Conference ขนาดเล็ก', 'active'),
(5, 'Room E - Digital Lab', 'อาคารทิศใต้', 'ชั้น 2', 10, 'คอมพิวเตอร์ 10 เครื่อง, โปรเจกเตอร์, ไวท์บอร์ด', 'active'),
(6, 'Room F - Mini Meeting', 'อาคารทิศเหนือ', 'ชั้น 3', 4, 'ทีวี 50 นิ้ว, กระดานกระจก', 'active');

INSERT INTO `room_features` (`id`, `feature_name`, `total_qty`, `active_qty`, `maintenance_qty`) VALUES
(1, 'โปรเจกเตอร์ 4K', 12, 10, 2), 
(2, 'ระบบเสียง VDO Conference', 8, 7, 1), 
(3, 'กระดานอัจฉริยะ (Smartboard)', 6, 6, 0), 
(4, 'ไมค์ไร้สาย', 20, 18, 2);

INSERT INTO `room_feature_map` (`room_id`, `feature_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
(2, 2), (2, 4),
(3, 3);

-- Sample Bookings
INSERT INTO `bookings` (`id`, `room_id`, `user_id`, `title`, `agenda`, `meeting_date`, `start_time`, `end_time`, `attendee_count`, `approval_status`, `booking_status`) VALUES
(1, 1, 1, 'ประชุมงบประมาณประจำไตรมาส 3', 'ทบทวนงบประมาณและวางแผนค่าใช้จ่ายครึ่งปีหลัง', CURDATE(), '09:00:00', '11:30:00', 35, 'approved', 'confirmed'),
(2, 2, 3, 'สัมภาษณ์พนักงานใหม่ ตำแหน่ง Senior Developer', 'สัมภาษณ์รอบสุดท้ายโดยทีมผู้บริหาร', CURDATE(), '13:00:00', '15:00:00', 5, 'approved', 'confirmed'),
(3, 3, 2, 'อัปเดตงานทีม Design & UX/UI', 'หารือการปรับปรุงระบบแอปพลิเคชันภายใน', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '12:00:00', 6, 'pending', 'draft'),
(4, 1, 1, 'อบรมการป้องกันความปลอดภัยทางไซเบอร์', 'อบรมประจำปีสำหรับพนักงานทุกคนในองค์กร', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:30:00', '16:30:00', 45, 'approved', 'confirmed');

INSERT INTO `audit_logs` (`user_id`, `module`, `action`, `reference_id`, `ip_address`) VALUES
(1, 'Booking', 'create', 1, '127.0.0.1'),
(3, 'Booking', 'create', 2, '127.0.0.1'),
(2, 'Booking', 'create', 3, '127.0.0.1');

-- 12. ตารางเก็บรูปภาพห้องประชุม (room_images) รองรับประมาณ 5 รูปต่อห้อง
CREATE TABLE IF NOT EXISTS `room_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_room_images_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_images` (`room_id`, `image_path`) VALUES
(1, 'https://images.unsplash.com/photo-1517502884422-41eaead166d4?auto=format&fit=crop&w=600&q=80'),
(1, 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=600&q=80'),
(1, 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?auto=format&fit=crop&w=600&q=80'),
(2, 'https://images.unsplash.com/photo-1416339442236-8ceb164046f8?auto=format&fit=crop&w=600&q=80'),
(2, 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=600&q=80'),
(3, 'https://images.unsplash.com/photo-1556761175-b413da4bafcf?auto=format&fit=crop&w=600&q=80'),
(3, 'https://images.unsplash.com/photo-1531538606174-0f90ff5dce83?auto=format&fit=crop&w=600&q=80'),
(4, 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=600&q=80'),
(4, 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=600&q=80'),
(5, 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=600&q=80'),
(6, 'https://images.unsplash.com/photo-1503423571247-220e6631b0fc?auto=format&fit=crop&w=600&q=80');

