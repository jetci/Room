# 📅 Meeting Room Booking System (PHP 8.2 + MySQL + Docker)

ระบบจองห้องประชุมอัจฉริยะ (Smart Meeting Room Booking System) ออกแบบโครงสร้างตามมาตรฐาน MVC Architecture, Service Layer, Middleware และยึดหลัก Best Practices ด้าน Security / Performance

## 🚀 วิธีการติดตั้งและเปิดใช้งาน (Installation & Quick Start)

1. ตรวจสอบว่าในเครื่องติดตั้ง **Docker** และ **Docker Compose** เรียบร้อยแล้ว
2. เปิด Terminal หรือ Command Prompt ในโฟลเดอร์โปรเจกต์ (`d:\room`)
3. รันคำสั่งต่อไปนี้เพื่อเริ่มต้นระบบ:
   ```bash
   docker-compose up -d
   ```
4. ระบบจะทำการสร้าง Container และนำเข้าฐานข้อมูล (`database/schema.sql`) โดยอัตโนมัติ

## 🌐 ลิงก์เข้าใช้งานระบบ (Access URLs)
- **Web Application (ระบบจองห้องประชุม):** [http://localhost:8080](http://localhost:8080)
- **phpMyAdmin (จัดการฐานข้อมูล MySQL):** [http://localhost:8081](http://localhost:8081)
  - **Username:** `root`
  - **Password:** `rootpassword`

## 📁 โครงสร้างโปรเจกต์ (Folder Structure)
- `app/` - จัดเก็บ Controllers, Models, Views
- `services/` - จัดเก็บ Business Logic และ Integration Services
- `middleware/` - จัดเก็บระบบการคัดกรองสิทธิ์ Auth & RBAC
- `config/` - ตั้งค่าระบบและฐานข้อมูล
- `routes/` - ระบบกำหนดเส้นทาง Web & API
- `database/` - สคริปต์สคีมาฐานข้อมูลและตั้งต้นข้อมูล
- `public/` - Web Root (index.php, CSS, JS, Images)
- `storage/` - จัดเก็บไฟล์อัปโหลดและ Logs
