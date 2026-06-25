<?php

namespace App\Models;

use Config\Database;
use PDO;
use PDOException;

class Booking
{
    /**
     * ดึงข้อมูลห้องประชุมทั้งหมด
     */
    public static function getAllRooms(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM rooms WHERE status = 'active' ORDER BY id ASC");
            $rooms = $stmt->fetchAll();

            // ดึงรูปภาพประกอบและอุปกรณ์ของแต่ละห้อง
            foreach ($rooms as &$room) {
                $stmtImg = $db->prepare("SELECT image_path FROM room_images WHERE room_id = :id ORDER BY id ASC");
                $stmtImg->execute([':id' => $room['id']]);
                $room['images'] = $stmtImg->fetchAll(PDO::FETCH_COLUMN);

                // ดึงรายการอุปกรณ์
                $stmtFeat = $db->prepare("SELECT rf.feature_name FROM room_feature_map rfm 
                                          JOIN room_features rf ON rfm.feature_id = rf.id 
                                          WHERE rfm.room_id = :id");
                $stmtFeat->execute([':id' => $room['id']]);
                $features = $stmtFeat->fetchAll(PDO::FETCH_COLUMN);
                $room['features_list'] = $features;
                if (!empty($features)) {
                    $room['equipment_summary'] = implode(', ', $features);
                }
            }
            return $rooms;
        } catch (PDOException $e) {
            // ส่งข้อมูลจำลองกลับไปหากยังไม่ได้ทำการ Migrate หรือต่อ DB ไม่สำเร็จ (เพื่อความต่อเนื่องของ Mockup)
            return [
                ['id' => 1, 'room_name' => 'Room A - Grand Auditorium', 'capacity' => 50, 'location' => 'อาคารสำนักงานใหญ่ ชั้น 9', 'equipment_summary' => 'โปรเจกเตอร์ 4K, ระบบเสียง VDO Conference, กระดานอัจฉริยะ (Smartboard), ไมค์ไร้สาย', 'images' => ['https://images.unsplash.com/photo-1517502884422-41eaead166d4?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=600&q=80']],
                ['id' => 2, 'room_name' => 'Room B - Executive Boardroom', 'capacity' => 15, 'location' => 'อาคารสำนักงานใหญ่ ชั้น 9', 'equipment_summary' => 'ระบบเสียง VDO Conference, ไมค์ไร้สาย', 'images' => ['https://images.unsplash.com/photo-1416339442236-8ceb164046f8?auto=format&fit=crop&w=600&q=80']],
                ['id' => 3, 'room_name' => 'Room C - Creative Space', 'capacity' => 8, 'location' => 'อาคารทิศเหนือ ชั้น 3', 'equipment_summary' => 'กระดานอัจฉริยะ (Smartboard)', 'images' => ['https://images.unsplash.com/photo-1556761175-b413da4bafcf?auto=format&fit=crop&w=600&q=80']],
                ['id' => 4, 'room_name' => 'Room D - Smart Pod 1', 'capacity' => 20, 'location' => 'อาคารสำนักงานใหญ่ ชั้น 5', 'equipment_summary' => 'โปรเจกเตอร์ 4K, ไมค์ไร้สาย', 'images' => ['https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=600&q=80']],
                ['id' => 5, 'room_name' => 'Room E - Digital Lab', 'capacity' => 10, 'location' => 'อาคารทิศใต้ ชั้น 2', 'equipment_summary' => 'โปรเจกเตอร์ 4K', 'images' => ['https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=600&q=80']],
                ['id' => 6, 'room_name' => 'Room F - Mini Meeting', 'capacity' => 4, 'location' => 'อาคารทิศเหนือ ชั้น 3', 'equipment_summary' => 'กระดานอัจฉริยะ (Smartboard)', 'images' => ['https://images.unsplash.com/photo-1503423571247-220e6631b0fc?auto=format&fit=crop&w=600&q=80']],
            ];
        }
    }

    /**
     * ตรวจสอบเวลาซ้ำซ้อน (Overlapping Prevention)
     */
    public static function isTimeOverlapping(int $roomId, string $meetingDate, string $startTime, string $endTime): bool
    {
        try {
            $db = Database::getConnection();
            $sql = "SELECT COUNT(*) FROM bookings 
                    WHERE room_id = :room_id 
                    AND meeting_date = :meeting_date 
                    AND booking_status IN ('draft', 'confirmed') 
                    AND approval_status != 'rejected'
                    AND (
                        (start_time < :end_time AND end_time > :start_time)
                    )";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':room_id' => $roomId,
                ':meeting_date' => $meetingDate,
                ':start_time' => $startTime,
                ':end_time' => $endTime
            ]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // ในโหมด Mockup จำลองว่าไม่ซ้ำ
            return false;
        }
    }

    /**
     * สร้างการจองใหม่ (Create Booking)
     */
    public static function create(array $data): bool
    {
        try {
            $db = Database::getConnection();
            $sql = "INSERT INTO bookings (room_id, user_id, title, agenda, meeting_date, start_time, end_time, attendee_count, approval_status, booking_status) 
                    VALUES (:room_id, :user_id, :title, :agenda, :meeting_date, :start_time, :end_time, :attendee_count, :approval_status, :booking_status)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':room_id' => $data['room_id'],
                ':user_id' => $data['user_id'] ?? 3, // Default user_id = 3 (User)
                ':title' => $data['title'],
                ':agenda' => $data['agenda'] ?? '',
                ':meeting_date' => $data['meeting_date'],
                ':start_time' => $data['start_time'],
                ':end_time' => $data['end_time'],
                ':attendee_count' => $data['attendee_count'] ?? 0,
                ':approval_status' => 'pending',
                ':booking_status' => 'draft'
            ]);

            $bookingId = $db->lastInsertId();

            // เก็บ Audit Log
            $sqlAudit = "INSERT INTO audit_logs (user_id, module, action, reference_id, ip_address) VALUES (:user_id, 'Booking', 'create', :ref_id, :ip)";
            $stmtAudit = $db->prepare($sqlAudit);
            $stmtAudit->execute([
                ':user_id' => $data['user_id'] ?? 3,
                ':ref_id' => $bookingId,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            return true;
        } catch (PDOException $e) {
            // บันทึกลง Log หรือคืนค่า true จำลองความสำเร็จในโหมด Mockup
            return true;
        }
    }

    /**
     * สร้างห้องประชุมใหม่ พร้อมแนบรูปถ่าย 5 รูป (Create Room with 5 Images)
     */
    public static function createRoom(array $data, array $files = []): bool
    {
        try {
            $db = Database::getConnection();
            $sql = "INSERT INTO rooms (room_name, location, floor, capacity, status) 
                    VALUES (:room_name, :location, :floor, :capacity, 'active')";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':room_name' => $data['room_name'],
                ':location' => $data['location'] ?? '',
                ':floor' => $data['floor'] ?? '',
                ':capacity' => (int)($data['capacity'] ?? 0)
            ]);

            $roomId = $db->lastInsertId();

            // บันทึกอุปกรณ์ที่เลือก (room_feature_map) และสรุป equipment_summary
            if (!empty($data['features']) && is_array($data['features'])) {
                $featureNames = [];
                foreach ($data['features'] as $featureId) {
                    $stmtMap = $db->prepare("INSERT INTO room_feature_map (room_id, feature_id) VALUES (:room_id, :feature_id)");
                    $stmtMap->execute([':room_id' => $roomId, ':feature_id' => (int)$featureId]);

                    // ดึงชื่ออุปกรณ์เพื่อเอาไปใส่ equipment_summary
                    $stmtFn = $db->prepare("SELECT feature_name FROM room_features WHERE id = :id");
                    $stmtFn->execute([':id' => (int)$featureId]);
                    $name = $stmtFn->fetchColumn();
                    if ($name) {
                        $featureNames[] = $name;
                    }
                }
                if (!empty($featureNames)) {
                    $summary = implode(', ', $featureNames);
                    $stmtUpd = $db->prepare("UPDATE rooms SET equipment_summary = :summary WHERE id = :id");
                    $stmtUpd->execute([':summary' => $summary, ':id' => $roomId]);
                }
            }

            // จัดการอัปโหลดไฟล์รูปภาพ (รองรับ 5 รูป)
            if (!empty($files['room_images']['name'][0])) {
                $uploadDir = __DIR__ . '/../../public/uploads/rooms/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileCount = count($files['room_images']['name']);
                // จำกัดสูงสุด 5 รูปตามที่ผู้ใช้ร้องขอ
                $maxFiles = min($fileCount, 5);

                for ($i = 0; $i < $maxFiles; $i++) {
                    if ($files['room_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $files['room_images']['tmp_name'][$i];
                        $fileName = time() . '_' . basename($files['room_images']['name'][$i]);
                        $destination = $uploadDir . $fileName;

                        if (move_uploaded_file($tmpName, $destination)) {
                            // บันทึกลงตาราง room_images
                            $sqlImg = "INSERT INTO room_images (room_id, image_path) VALUES (:room_id, :image_path)";
                            $stmtImg = $db->prepare($sqlImg);
                            $stmtImg->execute([
                                ':room_id' => $roomId,
                                ':image_path' => 'uploads/rooms/' . $fileName
                            ]);
                        }
                    }
                }
            }

            return true;
        } catch (PDOException $e) {
            return true;
        }
    }

    /**
     * ดึงข้อมูลอุปกรณ์/สิ่งอำนวยความสะดวกทั้งหมด
     */
    public static function getAllFeatures(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM room_features ORDER BY id ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [
                ['id' => 1, 'feature_name' => 'โปรเจกเตอร์ 4K', 'total_qty' => 12, 'active_qty' => 10, 'maintenance_qty' => 2],
                ['id' => 2, 'feature_name' => 'ระบบเสียง VDO Conference', 'total_qty' => 8, 'active_qty' => 7, 'maintenance_qty' => 1],
                ['id' => 3, 'feature_name' => 'กระดานอัจฉริยะ (Smartboard)', 'total_qty' => 6, 'active_qty' => 6, 'maintenance_qty' => 0],
                ['id' => 4, 'feature_name' => 'ไมค์ไร้สาย', 'total_qty' => 20, 'active_qty' => 18, 'maintenance_qty' => 2]
            ];
        }
    }

    /**
     * เพิ่มอุปกรณ์ใหม่
     */
    public static function createFeature(string $featureName, int $totalQty = 1, int $activeQty = 1, int $maintenanceQty = 0): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO room_features (feature_name, total_qty, active_qty, maintenance_qty) 
                                  VALUES (:name, :total, :active, :maint)");
            $stmt->execute([
                ':name' => $featureName,
                ':total' => $totalQty,
                ':active' => $activeQty,
                ':maint' => $maintenanceQty
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * อัปเดตข้อมูลอุปกรณ์ (Edit Feature)
     */
    public static function updateFeature(int $id, string $featureName, int $totalQty, int $activeQty, int $maintenanceQty): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE room_features 
                                  SET feature_name = :name, total_qty = :total, active_qty = :active, maintenance_qty = :maint 
                                  WHERE id = :id");
            $stmt->execute([
                ':name' => $featureName,
                ':total' => $totalQty,
                ':active' => $activeQty,
                ':maint' => $maintenanceQty,
                ':id' => $id
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * ลบอุปกรณ์ (Delete Feature)
     */
    public static function deleteFeature(int $id): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM room_features WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * ดึงข้อมูลผู้ใช้ทั้งหมดพร้อมแผนกและบทบาท
     */
    public static function getAllUsers(): array
    {
        try {
            $db = Database::getConnection();
            $sql = "SELECT u.*, d.department_name, r.role_name 
                    FROM users u 
                    LEFT JOIN departments d ON u.department_id = d.id 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    ORDER BY u.id ASC";
            $stmt = $db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [
                ['id' => 1, 'full_name' => 'คุณสมชาย บริหารดี', 'email' => 'admin@company.com', 'phone' => '0812345678', 'department_name' => 'สำนักปลัด (อบต.เวียง)', 'role_name' => 'Admin', 'department_id' => 1, 'role_id' => 1, 'status' => 'active'],
                ['id' => 2, 'full_name' => 'คุณสมศรี อนุมัติการ', 'email' => 'approver@company.com', 'phone' => '0823456789', 'department_name' => 'กองคลัง (อบต.เวียง)', 'role_name' => 'Approver', 'department_id' => 2, 'role_id' => 2, 'status' => 'active'],
                ['id' => 3, 'full_name' => 'คุณใจดี พนักงานทั่วไป', 'email' => 'user@company.com', 'phone' => '0834567890', 'department_name' => 'กองช่าง (อบต.เวียง)', 'role_name' => 'User', 'department_id' => 3, 'role_id' => 3, 'status' => 'active'],
                ['id' => 4, 'full_name' => 'ท่านนายก ประเสริฐศักดิ์', 'email' => 'executive@company.com', 'phone' => '0845678901', 'department_name' => 'สำนักปลัด (อบต.เวียง)', 'role_name' => 'Executive', 'department_id' => 1, 'role_id' => 4, 'status' => 'active']
            ];
        }
    }

    /**
     * ดึงข้อมูลแผนกทั้งหมด (รองรับ อบต.เวียง และหน่วยงานภายนอก)
     */
    public static function getAllDepartments(): array
    {
        $targetDepts = [
            1 => 'สำนักปลัด (อบต.เวียง)',
            2 => 'กองคลัง (อบต.เวียง)',
            3 => 'กองช่าง (อบต.เวียง)',
            4 => 'กองการศึกษา ศาสนา และวัฒนธรรม (อบต.เวียง)',
            5 => 'กองสวัสดิการสังคม (อบต.เวียง)',
            6 => 'กองสาธารณสุขและสิ่งแวดล้อม (อบต.เวียง)',
            7 => 'หน่วยราชการภายนอก / ประชาชนทั่วไป'
        ];

        try {
            $db = Database::getConnection();
            
            // อัปเดตและเพิ่มข้อมูลแผนกของ อบต.เวียง ลงในฐานข้อมูลจริงทันทีโดยไม่กระทบ Foreign Key
            foreach ($targetDepts as $id => $name) {
                $chk = $db->prepare("SELECT id FROM departments WHERE id = ?");
                $chk->execute([$id]);
                if ($chk->fetch()) {
                    $up = $db->prepare("UPDATE departments SET department_name = ? WHERE id = ?");
                    $up->execute([$name, $id]);
                } else {
                    $ins = $db->prepare("INSERT INTO departments (id, department_name) VALUES (?, ?)");
                    $ins->execute([$id, $name]);
                }
            }

            $stmt = $db->query("SELECT * FROM departments ORDER BY id ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $fallback = [];
            foreach ($targetDepts as $id => $name) {
                $fallback[] = ['id' => $id, 'department_name' => $name];
            }
            return $fallback;
        }
    }

    /**
     * ดึงข้อมูลบทบาททั้งหมด
     */
    public static function getAllRoles(): array
    {
        $targetRoles = [
            1 => 'Admin',
            2 => 'Approver',
            3 => 'User',
            4 => 'Executive'
        ];

        try {
            $db = Database::getConnection();
            
            foreach ($targetRoles as $id => $name) {
                $chk = $db->prepare("SELECT id FROM roles WHERE id = ?");
                $chk->execute([$id]);
                if ($chk->fetch()) {
                    $up = $db->prepare("UPDATE roles SET role_name = ? WHERE id = ?");
                    $up->execute([$name, $id]);
                } else {
                    $ins = $db->prepare("INSERT INTO roles (id, role_name) VALUES (?, ?)");
                    $ins->execute([$id, $name]);
                }
            }

            $stmt = $db->query("SELECT * FROM roles ORDER BY id ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $fallback = [];
            foreach ($targetRoles as $id => $name) {
                $fallback[] = ['id' => $id, 'role_name' => $name];
            }
            return $fallback;
        }
    }

    /**
     * เพิ่มผู้ใช้ใหม่ (รองรับการแยก first_name และ last_name)
     */
    public static function createUser(array $data): bool
    {
        $fullName = !empty($data['first_name']) ? trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')) : ($data['full_name'] ?? '');
        try {
            $db = Database::getConnection();
            $sql = "INSERT INTO users (department_id, role_id, full_name, email, phone, password_hash, status) 
                    VALUES (:dept, :role, :name, :email, :phone, :pass, :status)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':dept' => (int)$data['department_id'],
                ':role' => (int)$data['role_id'],
                ':name' => $fullName,
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? '',
                ':pass' => password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT),
                ':status' => $data['status'] ?? 'active'
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * อัปเดตข้อมูลผู้ใช้
     */
    public static function updateUser(array $data): bool
    {
        $fullName = !empty($data['first_name']) ? trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')) : ($data['full_name'] ?? '');
        try {
            $db = Database::getConnection();
            $sql = "UPDATE users 
                    SET department_id = :dept, role_id = :role, full_name = :name, email = :email, phone = :phone, status = :status 
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':dept' => (int)$data['department_id'],
                ':role' => (int)$data['role_id'],
                ':name' => $fullName,
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? '',
                ':status' => $data['status'] ?? 'active',
                ':id' => (int)$data['user_id']
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * ลบผู้ใช้
     */
    public static function deleteUser(int $id): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * เปลี่ยนสถานะผู้ใช้ (Toggle Status)
     */
    public static function toggleUserStatus(int $id, string $status): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * ดึงข้อมูลสนามกีฬาและลานกีฬาอเนกประสงค์ทั้งหมด (Sports Facilities)
     */
    public static function getAllSportsFacilities(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM sports_facilities WHERE status = 'active' ORDER BY id ASC");
            $facilities = $stmt->fetchAll();
            if (!empty($facilities)) {
                return $facilities;
            }
        } catch (PDOException $e) {
            // Fallback mock data สำหรับสนามกีฬาของ อบต.เวียง
        }

        return [
            [
                'id' => 1, 
                'facility_name' => 'สนามฟุตบอลหญ้าเทียม อบต.เวียง (Wiang Football Turf)', 
                'category' => 'สนามฟุตบอล / ฟุตซอล',
                'location' => 'ศูนย์กีฬาชุมชน อบต.เวียง โซน A', 
                'capacity' => 22, 
                'available_equipments' => 'ลูกฟุตบอล 5 ลูก, เสื้อเอี๊ยมแบ่งทีม (2 สี), ไฟส่องสว่างสปอตไลท์ (กลางคืน), ตู้กดน้ำดื่ม', 
                'rules' => 'กรุณาสวมรองเท้าฟุตบอลหรือรองเท้าผ้าใบเท่านั้น, ไม่อนุญาตให้นำอาหารขึ้นสนาม',
                'images' => [
                    'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=800&q=80'
                ]
            ],
            [
                'id' => 2, 
                'facility_name' => 'ลานกีฬาอเนกประสงค์ / สนามบาสเกตบอล (Multipurpose Court)', 
                'category' => 'สนามบาสเกตบอล / วอลเลย์บอล',
                'location' => 'ศูนย์กีฬาชุมชน อบต.เวียง โซน B', 
                'capacity' => 20, 
                'available_equipments' => 'ลูกบาสเกตบอล 3 ลูก, ลูกวอลเลย์บอล 3 ลูก, ตาข่ายวอลเลย์บอล, เสาประดับ', 
                'rules' => 'จองใช้งานได้ไม่เกิน 2 ชั่วโมงต่อกลุ่ม/วัน เพื่อแบ่งปันให้ชุมชน',
                'images' => [
                    'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=800&q=80'
                ]
            ],
            [
                'id' => 3, 
                'facility_name' => 'คอร์ทแบดมินตัน มาตรฐาน 1-2 (Indoor Badminton Courts)', 
                'category' => 'สนามแบดมินตัน',
                'location' => 'อาคารศูนย์กีฬาในร่ม (Gymnasium) ชั้น 1', 
                'capacity' => 8, 
                'available_equipments' => 'ลูกแบดมินตัน (พลาสติก/ขนไก่), ไม้แบดมินตันสำรอง 4 อัน, พัดลมระบายอากาศ, สกอร์บอร์ด', 
                'rules' => 'โปรดแต่งกายสุภาพและสวมรองเท้าพื้นยาง (Non-marking) เท่านั้น',
                'images' => [
                    'https://images.unsplash.com/photo-1622279457486-62d751e39a3f?auto=format&fit=crop&w=800&q=80'
                ]
            ],
            [
                'id' => 4, 
                'facility_name' => 'ลานเปตองชุมชนสัมพันธ์ 1-4 (Petanque Courts)', 
                'category' => 'ลานเปตอง',
                'location' => 'บริเวณสวนสาธารณะริมน้ำ อบต.เวียง', 
                'capacity' => 16, 
                'available_equipments' => 'ชุดลูกเปตองมาตรฐาน 4 ชุด, คราดปรับพื้นทราย, ป้ายแสดงคะแนน', 
                'rules' => 'ผู้ยืมอุปกรณ์ต้องนำบัตรประชาชนมาแลกรับที่เจ้าหน้าที่ดูแลลาน',
                'images' => [
                    'https://images.unsplash.com/photo-1526848784260-fe5bcf41e8f2?auto=format&fit=crop&w=800&q=80'
                ]
            ],
            [
                'id' => 5, 
                'facility_name' => 'สนามตะกร้อ / ลานตะกร้อวง (Sepak Takraw Court)', 
                'category' => 'สนามตะกร้อ',
                'location' => 'ศูนย์กีฬาชุมชน อบต.เวียง โซน C', 
                'capacity' => 10, 
                'available_equipments' => 'ลูกตะกร้อ 5 ลูก, ตาข่ายตะกร้อ, อัฒจันทร์นั่งชมขนาดเล็ก', 
                'rules' => 'เปิดให้บริการตั้งแต่เวลา 08.00 น. - 21.00 น.',
                'images' => [
                    'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=800&q=80'
                ]
            ],
        ];
    }

    /**
     * สร้างการจองสนามกีฬาและยืมอุปกรณ์ (Create Sports Booking)
     */
    public static function createSportsBooking(array $data): bool
    {
        try {
            $db = Database::getConnection();
            $sql = "INSERT INTO sports_bookings (facility_id, user_id, title, sports_date, start_time, end_time, borrow_equipments, user_notes, approval_status, booking_status) 
                    VALUES (:facility_id, :user_id, :title, :sports_date, :start_time, :end_time, :borrow_equipments, :user_notes, 'pending', 'draft')";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':facility_id' => $data['facility_id'],
                ':user_id' => $data['user_id'] ?? 3,
                ':title' => $data['title'],
                ':sports_date' => $data['sports_date'],
                ':start_time' => $data['start_time'],
                ':end_time' => $data['end_time'],
                ':borrow_equipments' => $data['borrow_equipments'] ?? '',
                ':user_notes' => $data['user_notes'] ?? ''
            ]);
            return true;
        } catch (PDOException $e) {
            // ในโหมด Mockup จำลองความสำเร็จ
            return true;
        }
    }

    /**
     * ดึงข้อมูลการจองสนามกีฬาทั้งหมด
     */
    public static function getAllSportsBookings(): array
    {
        try {
            $db = Database::getConnection();
            $sql = "SELECT sb.*, sf.facility_name, sf.category, sf.location, u.full_name, u.email, u.phone, d.department_name 
                    FROM sports_bookings sb 
                    JOIN sports_facilities sf ON sb.facility_id = sf.id 
                    LEFT JOIN users u ON sb.user_id = u.id 
                    LEFT JOIN departments d ON u.department_id = d.id 
                    ORDER BY sb.sports_date DESC, sb.start_time DESC";
            $stmt = $db->query($sql);
            $bookings = $stmt->fetchAll();
            if (!empty($bookings)) {
                return $bookings;
            }
        } catch (PDOException $e) {
            // Fallback mock data
        }

        return [
            [
                'id' => 101,
                'facility_id' => 1,
                'facility_name' => 'สนามฟุตบอลหญ้าเทียม อบต.เวียง (Wiang Football Turf)',
                'category' => 'สนามฟุตบอล / ฟุตซอล',
                'location' => 'ศูนย์กีฬาชุมชน อบต.เวียง โซน A',
                'full_name' => 'คุณสมชาย บริหารดี',
                'department_name' => 'สำนักปลัด (อบต.เวียง)',
                'phone' => '0812345678',
                'title' => 'แข่งฟุตบอลกระชับมิตร บุคลากรและผู้นำชุมชน',
                'sports_date' => '2026-06-28',
                'start_time' => '17:00',
                'end_time' => '20:00',
                'borrow_equipments' => 'ลูกฟุตบอล 5 ลูก, เสื้อเอี๊ยม 20 ตัว, เปิดไฟสปอตไลท์',
                'user_notes' => 'ขอเจ้าหน้าที่ช่วยเตรียมคูลเลอร์น้ำดื่ม',
                'approval_status' => 'approved',
                'created_at' => '2026-06-24 10:30:00'
            ],
            [
                'id' => 102,
                'facility_id' => 3,
                'facility_name' => 'คอร์ทแบดมินตัน มาตรฐาน 1-2 (Indoor Badminton Courts)',
                'category' => 'สนามแบดมินตัน',
                'location' => 'อาคารศูนย์กีฬาในร่ม (Gymnasium) ชั้น 1',
                'full_name' => 'คุณสมศรี อนุมัติการ',
                'department_name' => 'กองคลัง (อบต.เวียง)',
                'phone' => '0823456789',
                'title' => 'ออกกำลังกายประจำสัปดาห์ กองคลัง',
                'sports_date' => '2026-06-29',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'borrow_equipments' => 'ไม้แบดมินตันสำรอง 4 อัน, ลูกแบด 2 หลอด',
                'user_notes' => '-',
                'approval_status' => 'pending',
                'created_at' => '2026-06-25 09:15:00'
            ],
            [
                'id' => 103,
                'facility_id' => 2,
                'facility_name' => 'ลานกีฬาอเนกประสงค์ / สนามบาสเกตบอล (Multipurpose Court)',
                'category' => 'สนามบาสเกตบอล / วอลเลย์บอล',
                'location' => 'ศูนย์กีฬาชุมชน อบต.เวียง โซน B',
                'full_name' => 'คุณใจดี พนักงานทั่วไป',
                'department_name' => 'กองช่าง (อบต.เวียง)',
                'phone' => '0834567890',
                'title' => 'ซ้อมวอลเลย์บอลทีม อบต.เวียง',
                'sports_date' => '2026-06-30',
                'start_time' => '16:30',
                'end_time' => '18:30',
                'borrow_equipments' => 'ลูกวอลเลย์บอล 3 ลูก, ตาข่ายวอลเลย์บอล',
                'user_notes' => 'ขอยืมกุญแจเปิดตู้เก็บอุปกรณ์',
                'approval_status' => 'pending',
                'created_at' => '2026-06-25 11:00:00'
            ]
        ];
    }
}
