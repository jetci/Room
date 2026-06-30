# QA Re-check Report หลังฝ่ายพัฒนาแก้ไข

วันที่ตรวจสอบ: 2026-06-26  
เอกสารอ้างอิงเดิม: `D:\room\QA_AUDIT_REPORT_2026-06-26.md`  
ผลรวม: ยังไม่ผ่านสำหรับ production เพราะแก้ได้บางส่วน แต่ยังมี Critical runtime failures ใน Auth, DB schema, Approval workflow, Sports booking และ Audit integration

## สิ่งที่ตรวจพบว่าแก้แล้วบางส่วน

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : Info
ปัญหาที่ตรวจพบ : ทีมพัฒนาเพิ่ม `AuthMiddleware`, เพิ่ม CSRF/validation ใน `BookingController`, ป้องกันหน้า admin หลายหน้า, เปลี่ยน approvals เป็น POST + CSRF, เปิด SSL verify ใน `NotificationService`, เพิ่ม `public/assets/css/index.css`, และแก้ password field บางส่วนแล้ว
วิธีแก้ไข : ถือเป็น partial pass แต่ต้องทำ integration/runtime test เพิ่ม เพราะ test ที่ผ่านยังตรวจจาก string ในไฟล์มากกว่ายิง flow จริงเข้า DB

## 1. Guest ยังเห็น Dashboard/Search เป็น Admin ได้

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : หลัง logout แล้วเปิด `http://localhost:8080/dashboard.php?sync_role=admin` ยังเห็น Dashboard Admin ได้จริง และเปิด `http://localhost:8080/search.php` ยังเห็น navbar/sidebar เป็น `คุณสมชาย บริหารดี / Admin` โดยไม่ต้อง login สาเหตุคือ `public/dashboard.php:9`, `public/search.php:5`, `public/calendar.php:5`, `app/components/navbar.php:2`, `app/components/sidebar.php:2` fallback เป็น Admin เมื่อไม่มี `$_SESSION['user']`
วิธีแก้ไข : ใส่ `AuthMiddleware::requireAuth()` หรือ `requireActiveUser()` ในหน้า dashboard/search/calendar ที่ต้อง login, ลบ fallback Admin ออกจาก component กลาง, ถ้าหน้า public ต้องเปิดได้ให้แสดง guest state เท่านั้นและห้ามแสดง admin menu

## 2. ฐานข้อมูลจริงยังเป็น schema เก่า ไม่ตรงกับ `schema.sql` ใหม่

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : MySQL container จริงมีตาราง `audit_logs`, `booking_approvals`, `booking_attendees`, `bookings`, `departments`, `notifications`, `roles`, `room_feature_map`, `room_features`, `room_images`, `rooms`, `users` แต่ไม่มี `sports_bookings`, `sports_facilities`, `equipments`, `booking_equipments`; `bookings` จริงไม่มี `step1_approver_name`, `final_approver_name`, `e_signature_hash`; `users` จริงใช้ `department_id/role_id` ไม่ใช่ `department_name/role_name`
วิธีแก้ไข : ทำ versioned migration แบบ `ALTER TABLE` สำหรับ DB เดิม ไม่ใช่แค่แก้ `schema.sql`; เพิ่ม migration verification ใน CI; rebuild dev DB หรือรัน migration ให้ container volume ปัจจุบัน; เพิ่ม health check ที่ fail หากตาราง/คอลัมน์สำคัญหาย

## 3. Approval workflow/e-Signature ผ่าน test แต่ใช้งานจริงไม่ได้

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : `tests/MultiTierApprovalWorkflowTest.php` ผ่าน เพราะตรวจว่ามี method/string อยู่เท่านั้น แต่ runtime check คืน `false` ทั้ง `Booking::verifyBookingStep1(1, 'QA Approver', 'room')` และ `Booking::approveBookingFinal(1, 'QA Head', 'room')` เนื่องจาก DB จริงไม่มีคอลัมน์ workflow/e-signature ที่ model update (`app/models/Booking.php:191`, `app/models/Booking.php:222`)
วิธีแก้ไข : เพิ่มคอลัมน์ workflow/e-signature ให้ DB จริง, แยก approval service พร้อม error propagation, ให้ UI แสดง error จริงเมื่อ update fail, เพิ่ม integration test ที่ seed booking แล้วกด verify/final approve และตรวจ DB หลัง action

## 4. Sports booking แจ้งสำเร็จแม้ไม่มีตารางใน DB

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : `Booking::createSportsBooking(...)` คืนค่า `true` ใน runtime แม้ MySQL ไม่มี `sports_bookings`; `Booking::isTimeSlotAvailable(..., 'sports')` คืน `true` เมื่อ query ล้มเหลว; `Booking::checkEquipmentAvailability(...)` คืน `status=true` แม้ไม่มีตาราง `equipments` (`app/models/Booking.php:781`, `app/models/Booking.php:902`, `app/models/Booking.php:954`)
วิธีแก้ไข : เปลี่ยน catch เป็น fail-closed, ห้าม return success เมื่อ DB exception, สร้าง sports/equipment tables ใน DB จริง, ใช้ transaction สำหรับ booking + equipment stock, เพิ่ม test ที่ตรวจว่าหลังจองต้องมี row ใน DB จริงและ stock ถูกหัก

## 5. `schema.sql` ใหม่ยังไม่ตรงกับ Model แม้เริ่ม DB ใหม่

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `database/schema.sql:75` สร้าง `sports_facilities(name, category, capacity, image_url, description...)` แต่ model/admin ใช้ `facility_name`, `location`, `available_equipments`, `rules`; `database/schema.sql:125` ใช้ `sports_facility_id` และ `borrow_summary` แต่ `app/models/Booking.php:794` insert `facility_id` และ `borrow_equipments`; `app/models/Booking.php:933` ใช้ `booking_date` สำหรับ room availability ทั้งที่ schema ใช้ `meeting_date`
วิธีแก้ไข : เลือกชื่อคอลัมน์มาตรฐานชุดเดียว แล้วแก้ทั้ง schema/model/forms/report พร้อมกัน; เพิ่ม migration test ด้วย DB fresh และ DB upgrade จาก volume เก่า; ห้ามใช้ mock fallback กลบ PDO exception

## 6. AuditLogService ยังไม่ตรงกับ DB จริง

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : Runtime check `AuditLogService::logAction(...)` คืน `false` และ fallback ไป error_log เพราะ service insert `action_type/details/user_agent/created_at` แต่ DB จริงมี `module/action/reference_id/log_time/ip_address` (`app/Services/AuditLogService.php:25`)
วิธีแก้ไข : ปรับ schema หรือ service ให้ตรงกัน, ใช้ audit service จุดเดียวแทนการ insert ตรงใน model, เพิ่ม actor/action/target/before/after/result, และเพิ่ม test ที่ assert row ใน `audit_logs`

## 7. Demo login และ unsigned cookie payload ยังอยู่

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `public/login.php:10-36` ยังเปิด demo role login เป็น Admin/Approver/User/Executive ได้ และยัง set `user_session_payload` cookie แบบ JSON ไม่ signed (`public/login.php:15`, `public/login.php:20`, `public/login.php:25`, `public/login.php:30`, `public/login.php:35`, `public/login.php:59`) แม้ `config/app.php` จะเลิก consume `sync_role` แล้ว
วิธีแก้ไข : ปิด demo login ใน production ด้วย `APP_ENV`, ลบ `user_session_payload` หรือ signed/encrypted + httponly/samesite/secure, ให้ session server-side เป็นแหล่งสิทธิ์เดียว, เพิ่ม test ว่า production ไม่มี demo role buttons และไม่ set cookie ดังกล่าว

## 8. Tests ที่เพิ่มยังไม่พอสำหรับยืนยัน remediation

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `FullSystemRemediationTest`, `MultiTierApprovalWorkflowTest`, `SecuritySyncRoleTest`, `AdminAccessMiddlewareTest` ผ่าน แต่ส่วนใหญ่ตรวจ string/method หรือ simulate session ไม่ได้ตรวจ HTTP flow + DB state จริง จึงพลาดปัญหา DB missing table, approval fail, guest dashboard Admin fallback และ sports fail-open
วิธีแก้ไข : เพิ่ม PHPUnit/feature tests ที่รันกับ MySQL test DB จริง, ใช้ browser smoke tests สำหรับ guest/login/role, assert status code/redirect/DB row หลัง action, และให้ CI rebuild DB จาก migration ทุกครั้ง

## 9. Migration/deploy process ยังไม่ทำให้ DB ปัจจุบันอัปเดต

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : Medium
ปัญหาที่ตรวจพบ : `docker-compose.yml:36` mount `schema.sql` ไป `/docker-entrypoint-initdb.d/schema.sql` ซึ่ง MySQL จะรันเฉพาะตอนสร้าง volume ครั้งแรก; `config/migrate.php` ใช้ `CREATE TABLE IF NOT EXISTS` จึงไม่แก้คอลัมน์ของตารางเดิมที่มีอยู่แล้ว
วิธีแก้ไข : ใช้ migration versioning เช่น `001_initial.sql`, `002_add_sports.sql`, `003_add_approval_workflow.sql`, มีตาราง `schema_migrations`, และบังคับรัน migration ใน deploy/startup หรือ CI ก่อนเปิดระบบ

## 10. Data Architecture & Integration ตามไดอะแกรมยังไม่ตรง runtime

เอกสารจัดเก็บที่ : `D:\room\QA_RECHECK_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : เส้นทาง room POST ไป `BookingController::store()` จริงผ่าน `routes/web.php:16` และใช้ `isTimeOverlapping()` ไม่ใช่ `isTimeSlotAvailable()` ตามไดอะแกรม; sports flow ไม่เรียก `checkEquipmentAvailability()`; `NotificationService` ยังไม่ถูกผูกกับ booking/approval flow หลัก; `AuditLogService` มีแต่ใช้งานจริงไม่ได้กับ schema ปัจจุบัน; MySQL จริงไม่มีตาราง sports/equipment ที่ไดอะแกรมระบุ
วิธีแก้ไข : ปรับไดอะแกรมและโค้ดให้ตรงกัน หรือปรับ implementation ให้ตรงไดอะแกรม: UI/Form -> Controller/Service -> Availability/Equipment/Audit/Notification -> DB/API โดยทุก step ต้อง fail หาก dependency สำคัญล้มเหลว

## หลักฐานการตรวจซ้ำ

- Browser: หลัง logout เปิด `admin_users.php` ถูก redirect ไป `login.php` แล้ว ถือว่า admin page guard ดีขึ้น
- Browser: หลัง logout เปิด `dashboard.php?sync_role=admin` ยังเห็น Dashboard Admin
- Browser: หลัง logout เปิด `search.php` ยังเห็น Admin navbar/sidebar
- Browser: หลัง logout เปิด `sports.php` ถูก redirect ไป `login.php` แล้ว
- Docker/PHP lint: `BookingController.php`, `Booking.php`, `sports.php`, `approvals.php` ไม่มี syntax error
- Dev tests: `FullSystemRemediationTest.php`, `SecuritySyncRoleTest.php`, `AdminAccessMiddlewareTest.php`, `MultiTierApprovalWorkflowTest.php` ผ่าน แต่เป็น partial/weak tests
- MySQL runtime: `sports_bookings` ไม่มีจริง, `bookings` ไม่มี workflow/e-signature columns, `audit_logs` schema ไม่ตรงกับ `AuditLogService`
- Runtime methods: `createSportsBooking()` คืน `true` ทั้งที่ไม่มี table, `verifyBookingStep1()` และ `approveBookingFinal()` คืน `false`, `AuditLogService::logAction()` คืน `false`
