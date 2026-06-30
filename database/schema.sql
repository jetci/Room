-- โครงสร้างตารางสำหรับระบบ Smart Facility & Sports Booking (อบต.เวียง)
-- ปรับปรุงโครงสร้างรองรับ Multi-Tier Approval Workflow & e-Signature (QA Audit Alignment)

-- ตารางผู้ใช้งานระบบ (Users)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    department_name VARCHAR(150) DEFAULT 'พนักงานทั่วไป',
    role_name VARCHAR(50) DEFAULT 'User', -- Admin, User, Approver (เจ้าหน้าที่), HeadAdmin (หัวหน้าสำนักปลัด), Executive
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางแผนก/สังกัด (Departments)
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(150) NOT NULL,
    is_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางสิทธิ์การใช้งาน (Roles)
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางห้องประชุม (Rooms)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(150) NOT NULL,
    location VARCHAR(150) DEFAULT '',
    floor VARCHAR(50) DEFAULT '',
    capacity INT DEFAULT 10,
    color_code VARCHAR(20) DEFAULT '#4338ca',
    image_url VARCHAR(255) DEFAULT 'assets/img/meeting_default.jpg',
    description TEXT,
    equipment_summary TEXT DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางรายการอุปกรณ์ในห้องประชุม (Room Features)
CREATE TABLE IF NOT EXISTS room_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feature_name VARCHAR(150) NOT NULL,
    icon_class VARCHAR(100) DEFAULT 'fa-solid fa-circle-check',
    is_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางเชื่อมโยงห้องประชุมกับอุปกรณ์ (Room Feature Map)
CREATE TABLE IF NOT EXISTS room_feature_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    feature_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางเก็บรูปภาพห้องประชุม (Room Images)
CREATE TABLE IF NOT EXISTS room_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางสนามกีฬาและสถานที่ (Sports Facilities)
CREATE TABLE IF NOT EXISTS sports_facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    capacity INT DEFAULT 20,
    image_url VARCHAR(255) DEFAULT 'assets/img/sports_default.jpg',
    description TEXT,
    is_active INT DEFAULT 1,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางอุปกรณ์ส่วนกลาง (Equipments)
CREATE TABLE IF NOT EXISTS equipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL, -- meeting, sports
    total_stock INT DEFAULT 10,
    available_stock INT DEFAULT 10,
    is_active INT DEFAULT 1,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางการจองสถานที่ (Bookings)
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT DEFAULT NULL,
    sports_facility_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    agenda TEXT,
    meeting_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    attendee_count INT DEFAULT 1,
    user_notes TEXT,
    approval_status VARCHAR(50) DEFAULT 'pending', -- pending, verified (ผ่านขั้น 1), approved (ผ่านขั้น 2), rejected
    booking_status VARCHAR(50) DEFAULT 'draft', -- draft, confirmed, cancelled
    step1_approver_name VARCHAR(150) DEFAULT NULL,
    step1_approved_at TIMESTAMP DEFAULT NULL,
    final_approver_name VARCHAR(150) DEFAULT NULL,
    final_approved_at TIMESTAMP DEFAULT NULL,
    e_signature_hash VARCHAR(255) DEFAULT NULL,
    e_signature_path VARCHAR(255) DEFAULT NULL,
    approver_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางการจองสนามกีฬา (Sports Bookings)
CREATE TABLE IF NOT EXISTS sports_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sports_facility_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    sports_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    borrow_summary TEXT,
    user_notes TEXT,
    approval_status VARCHAR(50) DEFAULT 'pending',
    booking_status VARCHAR(50) DEFAULT 'draft',
    step1_approver_name VARCHAR(150) DEFAULT NULL,
    step1_approved_at TIMESTAMP DEFAULT NULL,
    final_approver_name VARCHAR(150) DEFAULT NULL,
    final_approved_at TIMESTAMP DEFAULT NULL,
    e_signature_hash VARCHAR(255) DEFAULT NULL,
    e_signature_path VARCHAR(255) DEFAULT NULL,
    approver_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางความสัมพันธ์การยืมอุปกรณ์ในการจอง (Booking Equipments)
CREATE TABLE IF NOT EXISTS booking_equipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT DEFAULT NULL,
    sports_booking_id INT DEFAULT NULL,
    equipment_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางประกาศส่วนกลาง (Announcements)
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    is_pinned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางเก็บบันทึกประวัติการใช้งาน (Audit Logs)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module VARCHAR(100) DEFAULT 'System',
    action VARCHAR(100) NOT NULL,
    reference_id INT DEFAULT NULL,
    action_type VARCHAR(100) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(50) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางการตั้งค่าระบบองค์กร (System Settings)
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
