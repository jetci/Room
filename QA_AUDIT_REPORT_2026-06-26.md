# รายงานตรวจสอบเชิงลึกระบบ Smart Room Booking

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
วันที่ตรวจสอบ : 26 มิถุนายน 2569
สภาพแวดล้อมที่ตรวจ : Docker container `meeting_booking_web` ที่ `http://localhost:8080`, MySQL container `meeting_booking_db`, ตรวจโค้ดใน workspace และทดสอบ UI ผ่าน Browser

หมายเหตุการตรวจสอบ : เครื่อง host ไม่มี `php` และ `composer` ใน PATH จึงไม่สามารถรัน `php -l` หรือ `composer validate` จาก host ได้ แต่ Docker container ของระบบกำลังทำงานอยู่และตรวจ flow ผ่าน browser/DB ได้

## สรุปภาพรวม

ระบบยังอยู่ในสถานะกึ่ง mockup/กึ่ง production มีความเสี่ยงสูงด้านสิทธิ์การเข้าถึง, session, approval, DB schema, และข้อมูลที่แสดงว่า “สำเร็จ” ทั้งที่ไม่ได้บันทึกจริง จุดที่ต้องแก้ก่อนใช้งานจริงคือ auth/RBAC, ปิด backdoor `sync_role`, ทำ migration ให้ตรงกับโค้ด, และเปลี่ยน mock fallback ให้ fail แบบตรวจสอบได้

จำนวนประเด็นหลักที่พบ:

- Critical: 6 รายการ
- High: 14 รายการ
- Medium: 5 รายการ
- Low: 1 รายการ

## Data Architecture & Integration

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : แผนภาพ Data Architecture & Integration ที่แนบมา “ตรงบางส่วน” กับระบบจริง แต่ยังไม่ตรง 100% โดยเฉพาะส่วน Notification, AuditLogService, equipment check และ sports data flow ระบบจริงยังมีหลายจุดที่เป็น mock/simulation หรือมี method อยู่แต่ไม่ได้ถูกเรียกใน flow หลัก
วิธีแก้ไข : ปรับแผนภาพและโค้ดให้มี source of truth เดียวกัน โดยแยก flow ห้องประชุม, กีฬา/อุปกรณ์, notification, audit log และ DB migration ให้ชัดเจน พร้อมเพิ่ม integration test ทุกเส้นทางข้อมูล

### สถานะเทียบกับแผนภาพ

| จุดในแผนภาพ | สถานะจริง | หลักฐาน |
|---|---|---|
| หน้าเว็บ UI / Forms ส่งคำร้องจอง | ตรง | Forms หลายหน้า POST ไป `booking_store.php` เช่น `dashboard.php`, `search.php`, `calendar.php`, `approvals.php` |
| `booking_store.php` เข้า Model โดยตรง | ตรงบางส่วน | POST ถูก intercept ที่ `routes/web.php` แล้วส่งเข้า `BookingController::store()` ก่อนเรียก `Booking.php` |
| โมเดล `Booking.php` เป็นศูนย์กลาง booking data | ตรง | `BookingController::store()` เรียก `Booking::isTimeOverlapping()` และ `Booking::create()` |
| ตรวจคิวทับซ้อนด้วย `isTimeSlotAvailable` | ไม่ตรงสำหรับจองห้อง | Room booking จริงใช้ `Booking::isTimeOverlapping()` ไม่ใช่ `isTimeSlotAvailable()` |
| ตรวจอุปกรณ์ด้วย `checkEquipmentAvailability` | ไม่ตรงใน flow จริง | มี method ใน `Booking.php` แต่ `sports.php` ไม่ได้เรียกก่อน `createSportsBooking()` |
| `config/database.php` ต่อ DB กลาง | ตรง | พยายามต่อ MySQL ก่อน และ fallback เป็น SQLite |
| Hybrid fallback ไป SQLite | ตรงเชิงโค้ด แต่เสี่ยงสูง | ถ้า MySQL ล้มเหลวจะสร้าง `storage/database.sqlite` โดยอัตโนมัติ แต่ไม่มี migration ครบ |
| NotificationService ยิง LINE Notify / Email | ไม่ตรงกับ flow booking/approval จริง | `NotificationService.php` มีอยู่ แต่ booking flow ไม่เรียก และ approval ใช้ `EmailHelper` แบบ simulation/session |
| AuditLogService บันทึกประวัติ | ไม่ตรงกับ flow booking จริง | `Booking::create()` insert `audit_logs` โดยตรง ไม่ได้เรียก `AuditLogService::logAction()` |
| Audit log เก็บข้อมูลและ IP | ตรงบางส่วน | `Booking::create()` เก็บ `user_id`, `module`, `action`, `reference_id`, `ip_address`; แต่ `AuditLogService` ใช้ schema คนละชุด |
| MySQL Server เป็นฐานหลัก | ตรงใน container ปัจจุบัน | MySQL container `meeting_booking_db` กำลังทำงานและมีตารางหลัก |

### Flow จริงของการจองห้องประชุม

```text
UI / Forms
  -> POST /booking_store.php
  -> routes/web.php
  -> BookingController::store()
  -> Booking::isTimeOverlapping()
  -> Booking::create()
  -> Config\Database::getConnection()
  -> MySQL หรือ SQLite fallback
  -> INSERT bookings
  -> INSERT audit_logs โดยตรง
```

ไฟล์อ้างอิง:

- `routes/web.php:16` intercept POST ไป `booking_store.php`
- `app/controllers/BookingController.php:12` method `store()`
- `app/controllers/BookingController.php:45` เรียก `Booking::isTimeOverlapping()`
- `app/controllers/BookingController.php:52` เรียก `Booking::create()`
- `app/models/Booking.php:55` method `isTimeOverlapping()`
- `app/models/Booking.php:118` insert `bookings`
- `app/models/Booking.php:137` insert `audit_logs` โดยตรง
- `config/database.php:10` method `getConnection()`

### Flow กีฬาและอุปกรณ์ที่ควรเป็น เทียบกับของจริง

Flow ที่ควรเป็นตามแผนภาพ:

```text
Sports UI / Forms
  -> sports.php
  -> Booking::isTimeSlotAvailable(type='sports')
  -> Booking::checkEquipmentAvailability()
  -> Booking::createSportsBooking()
  -> sports_bookings / equipments
  -> NotificationService
  -> AuditLogService
```

Flow จริงตอนนี้:

```text
Sports UI / Forms
  -> sports.php
  -> Booking::createSportsBooking()
  -> พยายาม INSERT sports_bookings
  -> ถ้า DB error จะ catch แล้ว return true
```

ผลกระทบ:

- ไม่มีการตรวจ slot ซ้ำของสนามกีฬาใน flow จริง
- ไม่มีการตรวจ stock อุปกรณ์ก่อนบันทึก
- MySQL จริงไม่มีตาราง `sports_bookings`, `sports_facilities`, `equipments`
- ผู้ใช้ได้รับข้อความสำเร็จได้ แม้ข้อมูลไม่ได้ถูกบันทึกจริง

### DB จริงที่ตรวจพบใน MySQL

ตารางที่มีจริง:

```text
audit_logs
booking_approvals
booking_attendees
bookings
departments
notifications
roles
room_feature_map
room_features
room_images
rooms
users
```

ตารางที่โค้ดเรียกใช้แต่ไม่พบใน MySQL จริง:

```text
sports_bookings
sports_facilities
equipments
```

### ข้อสรุป Data Architecture

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : แผนภาพเหมาะใช้เป็น “สถาปัตยกรรมเป้าหมาย” แต่ไม่ใช่สิ่งที่ระบบทำงานจริงในปัจจุบัน ระบบจริงยังผูก business logic จำนวนมากไว้ใน `Booking.php`, มี direct DB insert, มี mock fallback, และ integration services ไม่ถูกเรียกอย่างสม่ำเสมอ
วิธีแก้ไข : ปรับเป็น service architecture ชัดเจน เช่น `BookingService`, `SportsBookingService`, `EquipmentService`, `NotificationService`, `AuditLogService`; ให้ controller เรียก service กลาง, service ทำ transaction, แล้วค่อยเรียก model/repository/DB พร้อมบังคับ error handling แบบ fail closed

## บทบาท: ผู้ใช้ไม่ล็อกอิน / Public

### 1. เปลี่ยนบทบาทเป็น Admin/Approver/User/Executive ได้จาก URL

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : `config/app.php:17-38` รับ `?sync_role=admin|approver|user|executive|user_inactive` แล้วเขียน `$_SESSION['user']` และ cookie `user_session_payload` โดยไม่มีการยืนยันตัวตน ไม่มี signature และไม่มี server-side session binding ทำให้ผู้ใช้เปิด `dashboard.php?sync_role=admin` เพื่อยกระดับสิทธิ์ได้ทันที
วิธีแก้ไข : ลบ `sync_role` และ demo role ออกจาก production, ใช้ระบบ login จริงเท่านั้น, เก็บ session server-side, ใช้ signed/encrypted cookie เฉพาะ session id, เพิ่ม middleware `requireAuth()` และ `requireRole()` ที่ทุกหน้า protected ต้องเรียกก่อน render/action

### 2. หลัง logout ยังเข้า `admin_users.php` ได้โดยตรง

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : ทดสอบผ่าน browser หลังออกจากระบบแล้วเปิด `http://localhost:8080/admin_users.php` ยังเห็นหน้าจัดการผู้ใช้พร้อมปุ่มระงับ/ลบ เพราะหลายหน้าใช้ fallback เป็น Admin เมื่อไม่มี session เช่น `public/admin_users.php:5-13`, `public/admin_rooms.php:5-13`, `public/admin_equipments.php:5-13`, `public/reports.php:5-13`, `public/audit_logs.php:5-13`
วิธีแก้ไข : ห้ามตั้ง default user เป็น Admin, ถ้าไม่มี `$_SESSION['user']` ต้อง redirect ไป login หรือคืน 401/403, รวม auth check ไว้ใน middleware กลาง และเขียน automated test ว่า unauthenticated user เข้า admin pages ไม่ได้

### 3. Cookie session ถูกแก้ไขเองได้

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : `config/app.php:33-38` เขียน/อ่าน `user_session_payload` เป็น JSON ตรงๆ จาก cookie แล้ว trust เป็น session user ไม่มี HMAC/signature ไม่มี `HttpOnly`, `Secure`, `SameSite` ใน `setcookie()` ผู้โจมตีสามารถ forge role/status/id ได้
วิธีแก้ไข : เก็บเฉพาะ session id ใน cookie, ตั้งค่า `session.cookie_httponly=1`, `session.cookie_secure=1` บน HTTPS, `SameSite=Lax/Strict`, regenerate session id หลัง login และห้าม restore user object จาก client-side JSON

### 4. ลิงก์/ไฟล์ที่อ้างถึงหาย ทำให้ผู้ใช้เจอ 404

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Medium
ปัญหาที่ตรวจพบ : `assets/css/index.css` ถูกอ้างใน `public/sports.php:80`, `public/admin_sports.php:63`, `public/admin_settings.php:137`, `public/admin_announcements.php:67` แต่ไฟล์ไม่มีจริงและ `http://localhost:8080/assets/css/index.css` คืน 404 นอกจากนี้ sidebar มีลิงก์ `register_ext.php` ที่ไม่มีไฟล์จริง (`app/components/sidebar.php:50`)
วิธีแก้ไข : เปลี่ยนไปใช้ `assets/css/style.css` หรือเพิ่มไฟล์ `index.css` จริง, ลบ/แก้ลิงก์ `register_ext.php`, เพิ่ม smoke test ตรวจ asset/link 404

## บทบาท: User ที่ active

### 5. การจองห้องใช้ user_id คงที่ ไม่ใช้ผู้ใช้ที่ล็อกอิน

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `app/controllers/BookingController.php:52-60` ส่ง `user_id => 3` เสมอ ทำให้การจองไม่ผูกกับผู้ใช้จริง และ audit trail ผิดคน หาก Approver/Admin/Executive ใช้ฟอร์มจองก็ยังถูกบันทึกเป็น user id 3
วิธีแก้ไข : ใช้ `$_SESSION['user']['id']` หลังผ่าน `requireAuth()`, validate ว่า user active, เก็บ created_by/updated_by ให้ถูกต้อง และเพิ่ม test ว่าการจองของแต่ละ role ได้ user_id ตรง session

### 6. Search Available Rooms ไม่ได้ตรวจเวลาว่างจริง

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `public/search.php:21-26` โหลดห้องทั้งหมดและ filter เฉพาะ capacity; `public/search.php:89-130` แสดงทุกห้องเป็น “ว่างพร้อมใช้” โดยไม่ตรวจ booking conflict ทั้งที่มี helper `Booking::isTimeSlotAvailable()` (`app/models/Booking.php:701-719`) แต่ไม่ได้ถูกเรียกในหน้า search
วิธีแก้ไข : เพิ่ม input ช่วงเวลาใน search, query booking overlap จาก DB, แสดงเฉพาะห้องที่ว่างจริง, ปิดปุ่มจอง/แจ้งเหตุผลเมื่อชนเวลา, เพิ่ม index `(room_id, meeting_date, start_time, end_time)` และ test double-booking

### 7. เมื่อ DB error ระบบยังแจ้งสำเร็จ

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `Booking::create()` catch `PDOException` แล้ว `return true` (`app/models/Booking.php:115-118`) และ `isTimeOverlapping()` catch แล้ว `return false` (`app/models/Booking.php:73-76`) ทำให้ระบบแสดงว่าจองสำเร็จแม้ insert/check overlap ล้มเหลว
วิธีแก้ไข : เปลี่ยนเป็น fail closed, log exception พร้อม correlation id, rollback transaction, แจ้งผู้ใช้ว่าไม่สามารถบันทึกได้, ห้าม return success จาก catch ใน production

### 8. ไม่ validate วันที่ย้อนหลัง/ความจุ/room existence ใน flow จองห้อง

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `BookingController::store()` ตรวจแค่ required fields และ start/end time (`app/controllers/BookingController.php:23-47`) แต่ไม่ตรวจวันที่ย้อนหลัง, room_id มีอยู่จริง, room active, attendee_count ไม่เกิน capacity, และไม่ได้ตรวจว่า user active เพราะ check ใน `public/booking_store.php:4-14` ไม่ถูกใช้เมื่อ POST ถูก intercept โดย `routes/web.php:16-20`
วิธีแก้ไข : รวม validation ใน service กลางก่อนบันทึก, ตรวจสิทธิ์/สถานะ user ใน controller, ตรวจ room capacity และ active status จาก DB, เพิ่ม validation errors ที่ชัดเจน

## บทบาท: User ที่ inactive / รออนุมัติ

### 9. Dashboard ยังมีฟอร์มจองให้ inactive user เห็น

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Medium
ปัญหาที่ตรวจพบ : ทดสอบ `dashboard.php?sync_role=user_inactive` แล้วระบบขึ้น banner ระงับสิทธิ์ แต่ DOM ยังมีฟอร์ม `booking_store.php` 1 ฟอร์ม และ sidebar ยังแสดงเมนูจอง เพียงแค่กดแล้ว redirect จากบางหน้า (`public/dashboard.php`, `app/components/sidebar.php:21-38`)
วิธีแก้ไข : ซ่อน/disable action ที่จองได้ทั้งหมดสำหรับ inactive user, ให้ sidebar แสดงสถานะรออนุมัติแทนเมนู action, ตรวจซ้ำฝั่ง server ทุก endpoint

### 10. กีฬาไม่ block inactive user

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `public/sports.php:5-12` อ่าน role/status แต่ไม่มี check `inactive` ก่อนแสดง/submit form ขณะที่ search/calendar มี check redirect (`public/search.php:15-18`, `public/calendar.php:15-18`) ทำให้ inactive user ยังเข้าหน้าจองสนามและเห็นฟอร์มได้
วิธีแก้ไข : เพิ่ม `requireActiveUser()` ให้ sports endpoint ทั้ง GET/POST, ซ่อนเมนู sports เมื่อ inactive หรือแสดง read-only พร้อมเหตุผล

## บทบาท: Approver

### 11. Approve/Reject ใช้ GET และไม่มี CSRF

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : `public/approvals.php:8-38` เปลี่ยนสถานะผู้ใช้/แสดงผลอนุมัติผ่าน query string เช่น `approvals.php?action=approve_user&id=...` และลิงก์ที่ `public/approvals.php:310-313`, `public/approvals.php:373-392` เป็น GET ไม่มี CSRF token จึงเสี่ยง CSRF และ accidental approval
วิธีแก้ไข : เปลี่ยนเป็น POST form พร้อม CSRF, ตรวจ `requireRole(['Admin','Approver'])`, ใช้ confirmation server-side, เพิ่ม audit log ทุก action, ป้องกัน replay ด้วย state transition validation

### 12. อนุมัติการจองห้อง/กีฬาไม่ได้ update DB จริง

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `public/approvals.php:19-38` สำหรับ booking ใช้ `$mockBooking` เพื่อส่ง simulation email/LINE แล้วตั้ง session message แต่ไม่มี `UPDATE bookings SET approval_status...`; action กีฬา `approve_sports/reject_sports` ที่ `public/approvals.php:16-18` ตั้ง message อย่างเดียว ไม่ update `sports_bookings`
วิธีแก้ไข : สร้าง `BookingApprovalService`, update DB ใน transaction, เก็บ approver_id/reject_reason/approved_at, sync notification หลัง commit, แสดงข้อมูลจาก DB ไม่ใช่ mock rows

### 13. Executive เห็นหน้า approvals ทั้งที่ควร view-only แต่ endpoint ยังรับ action

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : UI บางส่วนซ่อนปุ่มเมื่อ role เป็น Executive (`public/approvals.php:367-385`) แต่ action handler อยู่ก่อน role check และรับ `$_GET['action']` ทันที (`public/approvals.php:8-38`) ถ้า Executive เรียก URL action โดยตรงยังมีโอกาสทำรายการได้ โดยเฉพาะเมื่อ role ถูก forge ได้จาก `sync_role`
วิธีแก้ไข : ตรวจ role ก่อน action handler ทุกครั้ง, แยก permission `approve_booking`, `approve_user`, `view_report`, ห้ามใช้ UI hide เป็น security control

## บทบาท: Executive

### 14. Dashboard/Reports เป็นข้อมูล static/mock ไม่ใช่รายงานจาก DB

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Medium
ปัญหาที่ตรวจพบ : `public/dashboard.php:415-449`, `public/dashboard.php:482-572`, `public/reports.php` แสดงตัวเลข เช่น 142 รายการ, 92%, 35,400 บาท, utilization 85% แบบ hardcoded ไม่คำนวณจาก `bookings/rooms/equipment` จริง ทำให้ผู้บริหารใช้ข้อมูลตัดสินใจผิด
วิธีแก้ไข : สร้าง reporting queries จาก DB, ระบุช่วงวันที่/filter, เพิ่ม timestamp “ข้อมูล ณ เวลาใด”, เพิ่ม empty/error state และ export จาก query จริง

### 15. Executive/Admin/Approver access แยกไม่ชัด

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Medium
ปัญหาที่ตรวจพบ : Sidebar ให้ Admin/Approver/Executive เข้าหน้า approvals (`app/components/sidebar.php:42-45`) แต่ permission จริงของ Approver/Executive ต่างกัน และหลายหน้าใช้ role fallback เป็น Admin ทำให้ boundary ระหว่างบทบาทไม่ชัด
วิธีแก้ไข : ทำ matrix permission ราย action, centralize permission check, เพิ่ม feature tests ราย role: Public, inactive User, active User, Approver, Executive, Admin

## บทบาท: Admin

### 16. หน้า Admin บางหน้าขาด role check และ CSRF

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : `public/admin_users.php:18-43`, `public/admin_rooms.php:16-18`, `public/admin_equipments.php:17-37` รับ POST เพื่อสร้าง/แก้/ลบข้อมูลโดยไม่มี CSRF และไม่มี role guard จริง อีกทั้ง fallback user เป็น Admin เมื่อไม่มี session
วิธีแก้ไข : เพิ่ม `requireAdmin()` ก่อนทุก action, เพิ่ม CSRF ทุก form, เปลี่ยน delete/toggle เป็น POST เท่านั้น, เพิ่ม audit log, validate input และแสดง error เมื่อ DB fail

### 17. รหัสผ่านเริ่มต้นแสดงเป็น plain text `123456`

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : หน้า admin users ใช้ input type text และค่า default `123456` สำหรับ password/confirm (`public/admin_users.php:308-314`) ทำให้เกิด weak credential และ shoulder-surfing risk
วิธีแก้ไข : ใช้ generated temporary password หรือ invite/reset link, force password change หลัง login, ใช้ input `type="password"`, บังคับ policy ความยาว/complexity และ rate limit login

### 18. Upload logo/room image ยังไม่ปลอดภัยพอ

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `public/admin_settings.php:38-80` รับ base64 data URI แบบไม่จำกัดขนาดและอนุญาต `svg/gif`; `app/models/Booking.php:164-186` upload room images ด้วย `basename()` และไม่มี MIME/size validation จริง เสี่ยง XSS ผ่าน SVG, storage bloat, และไฟล์ชนชื่อจาก `time()`
วิธีแก้ไข : จำกัดขนาดไฟล์, ใช้ `finfo_file()` ตรวจ MIME, ปิด SVG หรือ sanitize อย่างเข้มงวด, ใช้ random filename, เก็บ metadata ใน DB, scan/resize image server-side, ห้ามเก็บ logo เป็น data URI ขนาดใหญ่ใน session/settings

### 19. Audit Logs ไม่ใช่ข้อมูลจริงครบถ้วน

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `AuditLogService` เขียน `audit_logs (user_id, action_type, details, ip_address, user_agent)` (`app/Services/AuditLogService.php:25`) แต่ DB จริงมีคอลัมน์ `module/action/reference_id/log_time/ip_address` ทำให้ service ใช้กับ DB จริงไม่ได้ ขณะเดียวกันหน้า `public/audit_logs.php` แสดงข้อมูล static บางส่วน ไม่ได้เรียก service จริง
วิธีแก้ไข : ปรับ schema/service ให้ตรงกัน, log ทุก create/update/delete/approve/login/logout, เพิ่ม actor/target/before/after/status, แสดงจาก DB จริงพร้อม pagination/filter

## API / Integration

### 20. Notification/Email ยังเป็น simulation และ config ไม่ตรงกัน

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `EmailHelper` เก็บ email/LINE ล่าสุดลง session เท่านั้น (`app/Helpers/EmailHelper.php:92-138`) ไม่ส่งจริง; `NotificationService` อ่าน `LINE_NOTIFY_TOKEN` แต่ `.env` มี `LINE_BOT_CHANNEL_ACCESS_TOKEN`; ถ้าไม่มี token จะ `return true` แบบ simulation (`app/Services/NotificationService.php:15-21`) ทำให้ผู้ใช้คิดว่าส่งแจ้งเตือนแล้ว
วิธีแก้ไข : เลือก provider จริงและ config env ให้ตรง, แยก simulation mode ด้วย `APP_ENV`, ให้ production fail เมื่อ config ไม่มี, บันทึก notification status ใน DB และ retry queue

### 21. ปิด SSL verify ตอนยิง LINE API

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `app/Services/NotificationService.php:24-25` ตั้ง `CURLOPT_SSL_VERIFYHOST = 0` และ `CURLOPT_SSL_VERIFYPEER = 0` ทำให้เสี่ยง MITM และไม่เหมาะกับ production
วิธีแก้ไข : เปิด SSL verify, ใช้ CA bundle ที่ถูกต้องใน container, ตั้ง timeout/error handling, log response code และไม่ return success เมื่อ API fail

## Database / Migration

### 22. `database/schema.sql` ไม่ตรงกับโค้ดและ DB จริง

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Critical
ปัญหาที่ตรวจพบ : `database/schema.sql` สร้าง `rooms(name, is_active)`, `bookings(booking_date, attendees, user_notes)`, `audit_logs(action_type, details, user_agent)` แต่โค้ดและ DB จริงใช้ `rooms(room_name,status)`, `bookings(meeting_date, agenda, attendee_count, booking_status)`, `audit_logs(module, action, reference_id)` และ schema หลักขาด `departments`, `roles`, `room_features`, `room_images`, `room_feature_map`, `sports_bookings`, `sports_facilities` ที่โค้ดใช้งาน
วิธีแก้ไข : ทำ migration versioned เป็น source of truth เดียว, rebuild DB จากศูนย์ใน CI ทุกครั้ง, รวมตารางทั้งหมดใน migration, ห้ามพึ่ง Docker volume เก่าที่มี schema คนละชุด

### 23. กีฬา/อุปกรณ์ไม่มีตารางใน DB จริง แต่ model บอกสำเร็จ

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : ตรวจ MySQL container แล้วไม่มี `sports_facilities`, `sports_bookings`, `equipments` แต่ `Booking::createSportsFacility()`, `deleteSportsFacility()`, `createSportsBooking()` และ `checkEquipmentAvailability()` ใช้ตารางเหล่านี้ (`app/models/Booking.php:467-496`, `588-610`, `734-745`) และ catch แล้ว return true/พร้อมใช้งาน
วิธีแก้ไข : เพิ่ม migration ตารางกีฬา/อุปกรณ์, เปลี่ยน catch เป็น error, ตรวจ stock/overlap จริงก่อน insert, เพิ่ม foreign keys และ transaction

### 24. SQLite fallback อาจสร้างฐานแยกโดยไม่ migrate

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : `config/database.php:30-43` ถ้า MySQL ต่อไม่ได้จะสร้าง `storage/database.sqlite` ทันที แต่ไม่ได้รัน migration และโค้ดจะเข้าสู่ mock fallback จำนวนมาก เสี่ยงข้อมูลถูกบันทึกคนละฐานหรือไม่บันทึกจริง
วิธีแก้ไข : Production ควร fail fast เมื่อ DB ต่อไม่ได้, ถ้าต้องรองรับ SQLite ให้มี migration แยกและ health check ชัดเจน, แสดง maintenance mode แทน mock success

## UX/UI

### 25. Mobile layout ล้นจอและไม่มี hamburger/toggler

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Medium
ปัญหาที่ตรวจพบ : ทดสอบ viewport 390x844 พบ `docScroll` กว้างกว่า viewport ทุกหน้าที่ตรวจ เช่น dashboard/search/sports/admin_users/approvals; สาเหตุหลักคือ `.navbar-brand` ยาว 500-580px และ tables กว้าง 423-733px แม้บาง table มี `.table-responsive`; `navButtonCount=0` ไม่มี navbar toggler บนมือถือ
วิธีแก้ไข : เพิ่ม responsive navbar/toggler, truncate หรือซ่อนชื่อองค์กรยาวบน mobile, บังคับ `.navbar-brand span { max-width; overflow; text-overflow }`, ใช้ `table-responsive` พร้อม card layout บนจอเล็ก, ตรวจด้วย viewport 375/390/768 ใน CI

### 26. Palette/visual consistency ไม่สม่ำเสมอและมี CSS กระจัดกระจาย

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : Low
ปัญหาที่ตรวจพบ : หลายหน้าใช้ inline CSS และ font ต่างกัน (`Noto Sans Thai`, `Sarabun`), บางหน้าเรียก `style.css`, บางหน้าเรียก `index.css` ที่ไม่มีจริง, ทำให้หน้าตา/spacing/behavior ไม่เสถียร
วิธีแก้ไข : รวม design tokens และ component CSS กลาง, ลด inline styles, ใช้ navbar/sidebar component ให้ครบทุกหน้า, เพิ่ม visual regression smoke test

## สิ่งที่ยังไม่แล้วเสร็จ / Gap สำคัญ

เอกสารจัดเก็บที่ : `D:\room\QA_AUDIT_REPORT_2026-06-26.md`
ความร้ายแรง : High
ปัญหาที่ตรวจพบ : ระบบยังขาด production-ready modules หลายส่วน ได้แก่ RBAC middleware, migration ที่เชื่อถือได้, approval service, notification gateway จริง, sports/equipment persistence, audit log จริง, report query จริง, automated tests, rate limit/login hardening, และ responsive navigation
วิธีแก้ไข : แนะนำแผนแก้ 4 ระยะ:

1. ระยะเร่งด่วน: ปิด `sync_role`, ปิด demo login ใน production, เพิ่ม middleware auth/RBAC/CSRF, เปลี่ยน mock success เป็น error
2. ระยะฐานข้อมูล: เขียน migration ใหม่ให้ตรงโค้ด, rebuild DB ใน dev/CI, เพิ่ม FK/index/seed แยกจาก mock
3. ระยะ business flow: ทำ booking/approval/sports/equipment services พร้อม transaction และ audit log
4. ระยะคุณภาพ: เพิ่ม PHPUnit/feature tests, smoke tests ผ่าน browser, responsive checks, และ logging/monitoring

## หลักฐานการตรวจแบบย่อ

- Browser: หลัง logout เข้า `http://localhost:8080/admin_users.php` ได้และเห็น badge `Admin`, form ระงับ/ลบผู้ใช้ 8 forms
- Browser: `dashboard.php?sync_role=user|approver|executive|user_inactive` เปลี่ยนบทบาทบนหน้าได้จริง
- Browser mobile: viewport 390x844 พบ horizontal overflow ทุกหน้าที่สุ่มตรวจ และไม่มี navbar toggler
- DB: MySQL container มี bookings 4 รายการ, users 3 รายการ, audit_logs 6 รายการ แต่ไม่มีตาราง `sports_*` และ `equipments`
- Host runtime: `php` และ `composer` ไม่อยู่ใน PATH จึงต้องใช้ Docker/browser/DB inspection แทน syntax test จาก host
