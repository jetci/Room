<?php
require_once __DIR__ . '/../routes/web.php';
use App\Models\Booking;

$currentUser = $_SESSION['user'] ?? [
    'full_name' => 'คุณสมชาย บริหารดี',
    'role_name' => 'Admin',
    'email' => 'admin@wiang.go.th',
    'status' => 'active'
];
$role = $currentUser['role_name'] ?? $currentUser['role'] ?? 'Admin';
$userStatus = $currentUser['status'] ?? 'active';
$avatarName = urlencode($currentUser['full_name'] ?? 'Admin');

if ($userStatus === 'inactive') {
    $_SESSION['error_message'] = 'บัญชีของคุณอยู่ระหว่างรอการอนุมัติจาก Approver ไม่สามารถใช้งานเมนูจองห้องประชุมและปฏิทินได้';
    header("Location: dashboard.php");
    exit;
}

$rooms = Booking::getAllRooms();

$currentLogo = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
$currentOrgName = $_SESSION['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทินการจองห้องประชุม - Smart Room Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>
    <!-- Flatpickr CSS & JS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <style>
        /* ==========================================================================
           ✨ PREMIUM MINIMALIST & SOFT FULLCALENDAR (Direct Override)
           ========================================================================== */
        .fc { font-family: 'Noto Sans Thai', sans-serif !important; }
        .fc .fc-toolbar-title { font-size: 1.5rem !important; font-weight: 700 !important; color: #1e293b !important; letter-spacing: -0.4px !important; }
        
        /* ปุ่ม Navigation (เดือน, สัปดาห์, วัน, วันนี้) */
        .fc .fc-button-primary { 
            background-color: #f8fafc !important; 
            color: #475569 !important; 
            border: 1px solid #e2e8f0 !important; 
            border-radius: 14px !important; 
            padding: 10px 20px !important; 
            font-weight: 600 !important; 
            font-size: 0.95rem !important;
            text-transform: capitalize !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important;
            transition: all 0.2s ease-in-out !important;
        }
        .fc .fc-button-primary:hover { background-color: #f1f5f9 !important; color: #0f172a !important; border-color: #cbd5e1 !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important; }
        .fc .fc-button-primary:not(:disabled):active,
        .fc .fc-button-primary:not(:disabled).fc-button-active { 
            background-color: #4338ca !important; 
            color: #ffffff !important; 
            border-color: #4338ca !important; 
            box-shadow: 0 6px 20px rgba(67, 56, 202, 0.3) !important;
        }
        .fc .fc-button-primary:focus { box-shadow: none !important; }

        /* ลบขอบตารางแข็งกระด้าง และเปลี่ยนสีเส้นตารางให้นุ่มนวลสบายตา */
        .fc-theme-standard td, .fc-theme-standard th { border-color: #f1f5f9 !important; }
        .fc-theme-standard .fc-scrollgrid { border: 1px solid #f1f5f9 !important; border-radius: 24px !important; overflow: hidden !important; box-shadow: 0 4px 20px rgba(0,0,0,0.03) !important; }
        
        /* ส่วนหัวตารางวัน (จันทร์-อาทิตย์) */
        .fc .fc-col-header-cell { padding: 16px 0 !important; background-color: #f8fafc !important; font-weight: 600 !important; color: #64748b !important; font-size: 0.95rem !important; border-bottom: 1px solid #e2e8f0 !important; }
        .fc .fc-col-header-cell-cushion { color: #475569 !important; font-weight: 600 !important; text-decoration: none !important; }
        
        /* ตัวเลขวันที่ในช่องตาราง */
        .fc .fc-daygrid-day-number { color: #64748b !important; font-weight: 500 !important; font-size: 0.9rem !important; padding: 12px !important; text-decoration: none !important; }
        .fc .fc-daygrid-day:hover .fc-daygrid-day-number { color: #4338ca !important; font-weight: 600 !important; }

        /* ไฮไลท์วันปัจจุบัน (Today View) ให้นุ่มนวลด้วยสีเขียวมิ้นต์พาสเทล */
        .fc .fc-day-today { background-color: #f0fdf4 !important; }
        .fc .fc-day-today .fc-daygrid-day-number { color: #16a34a !important; font-weight: 700 !important; font-size: 1rem !important; }

        /* สไตล์อีเวนต์ในปฏิทิน (Events) */
        .fc-event { 
            border-radius: 10px !important; 
            border: none !important; 
            padding: 6px 12px !important; 
            font-size: 0.85rem !important; 
            font-weight: 600 !important; 
            margin: 2px 6px 4px 6px !important; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important; 
            transition: transform 0.2s ease, box-shadow 0.2s ease !important; 
        }
        .fc-event:hover { transform: translateY(-2px) !important; box-shadow: 0 6px 16px rgba(0,0,0,0.15) !important; cursor: pointer; }
        .fc-event .fc-event-main { color: #ffffff !important; font-weight: 600 !important; letter-spacing: -0.1px; }

        /* ปรับพื้นหลังตารางให้ขาวสะอาด */
        .fc .fc-view-harness { background-color: #ffffff !important; border-radius: 24px !important; }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3 sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="<?= $currentLogo ?>" alt="Logo" class="me-3 rounded-circle shadow-sm" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #cbd5e1;"> 
                <span class="fw-bold">SMART ROOM BOOKING (<?= htmlspecialchars($currentOrgName) ?>)</span>
            </a>
            <div class="d-flex align-items-center">
                <button class="btn btn-light position-relative me-3 border-0" style="background: #f1f5f9; border-radius: 12px; width: 44px; height: 44px;">
                    <i class="fa-regular fa-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
                </button>
                <div class="d-flex align-items-center me-3">
                    <img src="https://ui-avatars.com/api/?name=<?= $avatarName ?>&background=4338ca&color=fff" class="rounded-circle me-2" width="44" height="44">
                    <div class="d-none d-md-block">
                        <div class="fw-semibold fs-6 lh-1"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                        <span class="badge bg-indigo-light mt-1"><?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm px-3 py-2 rounded-3 fw-semibold">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Layout -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-lg-2 d-none d-lg-block sidebar py-4 px-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fa-solid fa-chart-pie me-3"></i> แดชบอร์ด</a>
                    </li>
                    <li class="nav-item">
                        <?php if ($userStatus === 'inactive'): ?>
                            <a class="nav-link text-muted" href="#" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถใช้งานเมนูจองได้ในขณะนี้'); return false;"><i class="fa-solid fa-calendar-days me-3 text-secondary"></i> ปฏิทินการจอง <i class="fa-solid fa-lock ms-2 text-warning"></i></a>
                        <?php else: ?>
                            <a class="nav-link active" href="calendar.php"><i class="fa-solid fa-calendar-days me-3"></i> ปฏิทินการจอง</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <?php if ($userStatus === 'inactive'): ?>
                            <a class="nav-link text-muted" href="#" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถใช้งานเมนูจองได้ในขณะนี้'); return false;"><i class="fa-solid fa-magnifying-glass me-3 text-secondary"></i> ค้นหาห้องว่าง <i class="fa-solid fa-lock ms-2 text-warning"></i></a>
                        <?php else: ?>
                            <a class="nav-link" href="search.php"><i class="fa-solid fa-magnifying-glass me-3"></i> ค้นหาห้องว่าง</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <?php if ($userStatus === 'inactive'): ?>
                            <a class="nav-link text-muted" href="#" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถใช้งานเมนูจองได้ในขณะนี้'); return false;"><i class="fa-solid fa-futbol me-3 text-secondary"></i> จองสนามกีฬา & อุปกรณ์ <i class="fa-solid fa-lock ms-2 text-warning"></i></a>
                        <?php else: ?>
                            <a class="nav-link" href="sports.php"><i class="fa-solid fa-futbol me-3"></i> จองสนามกีฬา & อุปกรณ์</a>
                        <?php endif; ?>
                    </li>

                    <?php if ($role === 'Admin' || $role === 'Approver' || $role === 'Executive'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="approvals.php"><i class="fa-solid fa-user-clock me-3"></i> คิวรออนุมัติ <span class="badge bg-warning text-dark ms-2">7</span></a>
                        </li>
                    <?php endif; ?>

                    <?php if ($role === 'Executive'): ?>
                        <li class="nav-item mt-4 mb-2"><span class="text-muted fs-7 fw-bold px-3">สำหรับผู้บริหาร (EXECUTIVE)</span></li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & สถิติภาพรวม</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($role === 'Admin'): ?>
                        <li class="nav-item mt-4 mb-2"><span class="text-muted fs-7 fw-bold px-3">จัดการระบบ (ADMIN)</span></li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_rooms.php"><i class="fa-solid fa-door-open me-3"></i> จัดการห้องประชุม</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_sports.php"><i class="fa-solid fa-trophy me-3"></i> จัดการสนามกีฬา</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_equipments.php"><i class="fa-solid fa-couch me-3"></i> จัดการอุปกรณ์</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & Export</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_users.php"><i class="fa-solid fa-users-gear me-3"></i> จัดการผู้ใช้</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_announcements.php"><i class="fa-solid fa-bullhorn me-3"></i> ประกาศส่วนกลาง</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_settings.php"><i class="fa-solid fa-house-flag me-3"></i> ตั้งค่าข้อมูล & โลโก้</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="audit_logs.php"><i class="fa-solid fa-shield-halved me-3"></i> Audit Logs</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Main Workspace -->
            <div class="col-lg-10 p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">ปฏิทินการจองห้องประชุม (Calendar View)</h4>
                        <p class="text-muted mb-0">มุมมองตารางการใช้ห้องประชุมแบบเต็มจอ (รายสัปดาห์ / รายเดือน)</p>
                    </div>
                    <button class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#bookingModal">
                        <i class="fa-solid fa-circle-plus me-2"></i>จองห้องประชุมใหม่
                    </button>
                </div>

                <!-- FullCalendar Section -->
                <div class="card main-card bg-white p-4 mb-5">
                    <div id="fullpage-calendar"></div>
                </div>

            </div>
        </div>
    </div>

    <!-- 🏷️ MODAL: Form จองห้องประชุม -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-indigo-light p-4" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <h5 class="modal-title fw-bold" id="bookingModalLabel"><i class="fa-solid fa-calendar-plus me-2"></i> เพิ่มรายการจองห้องประชุม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="booking_store.php" method="POST" class="row g-3">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">เลือกห้องประชุม <span class="text-danger">*</span></label>
                            <select class="form-select p-3" name="room_id" required>
                                <option value="">-- กรุณาเลือกห้องประชุม --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['room_name']) ?> (<?= htmlspecialchars($room['location']) ?> - รองรับ <?= $room['capacity'] ?> คน)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">วันที่จอง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3 datepicker bg-white" name="meeting_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">เวลาเริ่มต้น <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3 timepicker bg-white" name="start_time" value="09:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">เวลาสิ้นสุด <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3 timepicker bg-white" name="end_time" value="11:30" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">หัวข้อการประชุม / Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" name="title" placeholder="ระบุหัวข้อการประชุม" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">จำนวนผู้เข้าร่วม (คน)</label>
                            <input type="number" class="form-control p-3" name="attendee_count" placeholder="ตัวอย่าง: 12">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">วาระการประชุม / รายละเอียดเพิ่มเติม (Agenda)</label>
                            <textarea class="form-control p-3" name="agenda" rows="3" placeholder="ระบุวาระหรือความต้องการพิเศษ"></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light px-4 py-3 me-2 rounded-3 fw-semibold" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3"><i class="fa-solid fa-floppy-disk me-2"></i> บันทึกการจอง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- EVENT DETAILS MODAL (แสดงรายละเอียดทั้งหมดให้เจ้าหน้าที่รับจองเห็น) -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e1b4b, #312e81); color: white; border-radius: 24px 24px 0 0; padding: 24px 32px;">
                    <div>
                        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold fs-8 mb-2"><i class="fa-solid fa-address-card me-2"></i>ข้อมูลการจองฉบับเต็มสำหรับเจ้าหน้าที่</span>
                        <h4 class="modal-title fw-bold" id="detailTitle">หัวข้อการประชุม</h4>
                        <p class="mb-0 fs-7 text-indigo-200" id="detailFacility"><i class="fa-solid fa-location-dot me-2 text-warning"></i> ห้องประชุม / สนามกีฬา</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-5">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-4 bg-slate-50 rounded-4 border h-100">
                                <div class="text-muted fs-8 fw-bold mb-1"><i class="fa-solid fa-building me-2 text-indigo"></i>หน่วยงานที่จอง (DEPARTMENT)</div>
                                <div class="fs-5 fw-bold text-dark" id="detailDepartment">กองคลัง</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 bg-slate-50 rounded-4 border h-100">
                                <div class="text-muted fs-8 fw-bold mb-1"><i class="fa-solid fa-user-check me-2 text-indigo"></i>ผู้จอง / เบอร์ติดต่อ (BOOKED BY)</div>
                                <div class="fs-5 fw-bold text-dark" id="detailBooker">คุณสมชาย บริหารดี</div>
                                <div class="fs-7 text-muted mt-1" id="detailPhone"><i class="fa-solid fa-phone me-1 text-success"></i> 081-2345678</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 bg-slate-50 rounded-4 border h-100">
                                <div class="text-muted fs-8 fw-bold mb-1"><i class="fa-regular fa-calendar-check me-2 text-indigo"></i>วัน-เวลาที่เข้าใช้ (TIME)</div>
                                <div class="fs-6 fw-bold text-dark" id="detailDateTime">25 มิถุนายน 2569 | 09:00 - 11:30 น.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 bg-slate-50 rounded-4 border h-100">
                                <div class="text-muted fs-8 fw-bold mb-1"><i class="fa-solid fa-circle-check me-2 text-indigo"></i>สถานะการจอง (STATUS)</div>
                                <div class="fs-6 fw-bold text-success" id="detailStatus"><i class="fa-solid fa-circle-check me-2"></i>ได้รับการอนุมัติแล้ว</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="p-4 bg-indigo-subtle text-indigo rounded-4 border">
                                <div class="fw-bold fs-7 mb-1"><i class="fa-solid fa-box-open me-2"></i>อุปกรณ์และสิ่งอำนวยความสะดวกที่ขอยืม:</div>
                                <p class="text-dark fw-semibold mb-0 fs-7" id="detailEquipments">โปรเจกเตอร์, ไมโครโฟน 2 ตัว</p>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="p-4 bg-light rounded-4 border">
                                <div class="fw-bold fs-7 text-secondary mb-1"><i class="fa-solid fa-file-lines me-2 text-indigo"></i>วาระการประชุม / หมายเหตุเพิ่มเติม:</div>
                                <p class="text-muted mb-0 fs-7" id="detailAgenda">ไม่มีหมายเหตุเพิ่มเติม</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top p-4 d-flex justify-content-end">
                    <button type="button" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-bold shadow-sm" data-bs-dismiss="modal"><i class="fa-solid fa-circle-check me-2"></i> รับทราบข้อมูล</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eventDetailsModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
            const calendarEl = document.getElementById('fullpage-calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'th',
                timeZone: 'Asia/Bangkok',
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์', day: 'วัน' },
                datesSet: function (info) {
                    const titleEl = document.querySelector('.fc-toolbar-title');
                    if (titleEl && info.view && info.view.title) {
                        let text = info.view.title;
                        text = text.replace(/\b(20\d{2})\b/g, match => parseInt(match) + 543);
                        titleEl.textContent = text;
                    }
                },
                events: [
                    { 
                        title: '[ห้องประชุมใหญ่] กองคลัง (คุณสมชาย) - ประชุมงบประมาณไตรมาส 3', 
                        start: '2026-06-25T09:00:00', 
                        end: '2026-06-25T11:30:00', 
                        backgroundColor: '#4338ca', 
                        borderColor: '#4338ca',
                        extendedProps: {
                            facility: 'ห้องประชุมใหญ่ (ชั้น 2 อาคารสำนักงาน)',
                            department: 'กองคลัง',
                            booker: 'คุณสมชาย บริหารดี',
                            phone: '081-2345678',
                            titleName: 'ประชุมงบประมาณไตรมาส 3',
                            displayTime: '25 มิถุนายน 2569 | 09:00 - 11:30 น.',
                            status: 'อนุมัติแล้ว (Approved)',
                            equipments: 'โปรเจกเตอร์ 4K, ไมโครโฟนไร้สาย 4 ตัว, ชา/กาแฟ 15 ชุด',
                            agenda: 'พิจารณาอนุมัติกรอบงบประมาณประจำไตรมาสที่ 3 และวางแผนการจัดซื้อจัดจ้าง'
                        }
                    },
                    { 
                        title: '[ห้องประชุมสภา] กองช่าง (คุณวิชัย) - สัมภาษณ์พนักงานใหม่', 
                        start: '2026-06-25T13:00:00', 
                        end: '2026-06-25T15:00:00', 
                        backgroundColor: '#10b981', 
                        borderColor: '#10b981',
                        extendedProps: {
                            facility: 'ห้องประชุมสภา อบต. (ชั้น 3)',
                            department: 'กองช่าง',
                            booker: 'คุณวิชัย สถาปัตย์',
                            phone: '082-9876543',
                            titleName: 'สัมภาษณ์พนักงานใหม่และผู้รับเหมา',
                            displayTime: '25 มิถุนายน 2569 | 13:00 - 15:00 น.',
                            status: 'อนุมัติแล้ว (Approved)',
                            equipments: 'จอ LED Touchscreen, กระดานไวท์บอร์ด',
                            agenda: 'สัมภาษณ์คัดเลือกวิศวกรโยธาและสถาปนิกประจำโครงการพัฒนาถนนชุมชน'
                        }
                    },
                    { 
                        title: '[ห้องประชุมสภา] กองสวัสดิการสังคม (คุณอนงค์) - อัปเดตงานชุมชน', 
                        start: '2026-06-26T10:00:00', 
                        end: '2026-06-26T12:00:00', 
                        backgroundColor: '#f59e0b', 
                        borderColor: '#f59e0b',
                        extendedProps: {
                            facility: 'ห้องประชุมสภา อบต. (ชั้น 3)',
                            department: 'กองสวัสดิการสังคม',
                            booker: 'คุณอนงค์ เมตตาจิต',
                            phone: '083-4567890',
                            titleName: 'อัปเดตโครงการสงเคราะห์ผู้สูงอายุและผู้ยากไร้',
                            displayTime: '26 มิถุนายน 2569 | 10:00 - 12:00 น.',
                            status: 'อนุมัติแล้ว (Approved)',
                            equipments: 'ชุดไมโครโฟน 10 ตัว, โพเดียมบรรยาย',
                            agenda: 'ประเมินผลการมอบเบี้ยยังชีพผู้สูงอายุและหารือแนวทางปรับปรุงเบี้ยช่วยเหลือ'
                        }
                    },
                    { 
                        title: '[ห้องประชุมใหญ่] กองการศึกษา (คุณสุเมธ) - อบรมไซเบอร์ซีเคียวริตี้', 
                        start: '2026-06-27T13:30:00', 
                        end: '2026-06-27T16:30:00', 
                        backgroundColor: '#4338ca', 
                        borderColor: '#4338ca',
                        extendedProps: {
                            facility: 'ห้องประชุมใหญ่ (ชั้น 2 อาคารสำนักงาน)',
                            department: 'กองการศึกษา ศาสนาและวัฒนธรรม',
                            booker: 'คุณสุเมธ ปัญญาเลิศ',
                            phone: '089-1122334',
                            titleName: 'อบรมการป้องกันความปลอดภัยทางไซเบอร์',
                            displayTime: '27 มิถุนายน 2569 | 13:30 - 16:30 น.',
                            status: 'อนุมัติแล้ว (Approved)',
                            equipments: 'ระบบบันทึกเสียงและภาพวิดีโอ (Recording System), โปรเจกเตอร์หลัก',
                            agenda: 'สร้างความตระหนักรู้เรื่อง Phishing, Ransomware และการคุ้มครองข้อมูลส่วนบุคคล (PDPA)'
                        }
                    },
                    { 
                        title: '[สนามฟุตบอลหญ้าเทียม] กองสาธารณสุข (คุณพงษ์) - แข่งกระชับมิตร', 
                        start: '2026-06-28T17:00:00', 
                        end: '2026-06-28T19:30:00', 
                        backgroundColor: '#ec4899', 
                        borderColor: '#ec4899',
                        extendedProps: {
                            facility: 'สนามฟุตบอลหญ้าเทียม (ลานกีฬาสวนสาธารณะเวียง)',
                            department: 'กองสาธารณสุขและสิ่งแวดล้อม',
                            booker: 'คุณพงษ์ศักดิ์ แข็งขัน',
                            phone: '085-5556666',
                            titleName: 'แข่งฟุตบอลกระชับมิตรส่งเสริมสุขภาพ',
                            displayTime: '28 มิถุนายน 2569 | 17:00 - 19:30 น.',
                            status: 'อนุมัติแล้ว (Approved)',
                            equipments: 'ลูกฟุตบอล 2 ลูก, เสื้อเอี๊ยม 20 ตัว, บริการเปิดไฟสปอตไลท์สนาม',
                            agenda: 'กิจกรรมสันทนาการกระชับความสัมพันธ์ระหว่างบุคลากรและเจ้าหน้าที่ป้องกันสาธารณภัย'
                        }
                    }
                ],
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    document.getElementById('detailTitle').innerText = props.titleName || info.event.title;
                    document.getElementById('detailFacility').innerHTML = `<i class="fa-solid fa-location-dot me-2 text-warning"></i> ${props.facility || 'ไม่ระบุสถานที่'}`;
                    document.getElementById('detailDepartment').innerText = props.department || 'ไม่ระบุหน่วยงาน';
                    document.getElementById('detailBooker').innerText = props.booker || 'ไม่ระบุชื่อผู้จอง';
                    document.getElementById('detailPhone').innerHTML = `<i class="fa-solid fa-phone me-1 text-success"></i> ${props.phone || 'ไม่ระบุเบอร์ติดต่อ'}`;
                    document.getElementById('detailDateTime').innerText = props.displayTime || 'ไม่ระบุเวลา';
                    document.getElementById('detailEquipments').innerText = props.equipments || 'ไม่มีอุปกรณ์ที่ขอยืม';
                    document.getElementById('detailAgenda').innerText = props.agenda || 'ไม่มีหมายเหตุเพิ่มเติม';
                    
                    eventDetailsModal.show();
                }
            });
            calendar.render();

            // Flatpickr: วันที่ (พ.ศ.) และ เวลา (24 ชั่วโมง)
            if (typeof flatpickr !== 'undefined') {
                flatpickr(".datepicker", {
                    locale: "th",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d F Y",
                    minDate: "today",
                    onReady: function(selectedDates, dateStr, instance) {
                        function updateBuddhistYear() {
                            if (instance.currentYearElement) {
                                instance.currentYearElement.value = instance.currentYear + 543;
                            }
                            if (instance.yearElements && instance.yearElements[0]) {
                                instance.yearElements[0].value = instance.currentYear + 543;
                            }
                            if (instance.altInput && instance.selectedDates[0]) {
                                const yearEl = instance.altInput;
                                const d = instance.selectedDates[0];
                                const thMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                                yearEl.value = d.getDate() + ' ' + thMonths[d.getMonth()] + ' ' + (d.getFullYear() + 543);
                            }
                        }
                        updateBuddhistYear();
                        if (instance.calendarContainer) {
                            instance.calendarContainer.addEventListener('click', function() {
                                setTimeout(updateBuddhistYear, 10);
                            });
                        }
                    },
                    onOpen: function(selectedDates, dateStr, instance) {
                        setTimeout(function() {
                            if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                            if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                        }, 10);
                    },
                    onMonthChange: function(selectedDates, dateStr, instance) {
                        setTimeout(function() {
                            if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                            if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                        }, 10);
                    },
                    onYearChange: function(selectedDates, dateStr, instance) {
                        setTimeout(function() {
                            if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                            if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                        }, 10);
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        setTimeout(function() {
                            if (instance.altInput && instance.selectedDates[0]) {
                                const yearEl = instance.altInput;
                                const d = instance.selectedDates[0];
                                const thMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                                yearEl.value = d.getDate() + ' ' + thMonths[d.getMonth()] + ' ' + (d.getFullYear() + 543);
                            }
                            if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                            if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                        }, 10);
                    }
                });

                flatpickr(".timepicker", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true
                });
            }
        });
    </script>
</body>
</html>
