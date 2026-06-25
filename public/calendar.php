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
                <i class="fa-solid fa-building-flag me-2 fs-3 text-indigo"></i> 
                <span class="fw-bold">SMART ROOM BOOKING (อบต.เวียง)</span>
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
                            <a class="nav-link" href="admin_equipments.php"><i class="fa-solid fa-couch me-3"></i> จัดการอุปกรณ์</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & Export</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_users.php"><i class="fa-solid fa-users-gear me-3"></i> จัดการผู้ใช้</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
                    { title: 'Room A: ประชุมงบประมาณไตรมาส 3', start: '2026-06-25T09:00:00', end: '2026-06-25T11:30:00', backgroundColor: '#4338ca', borderColor: '#4338ca' },
                    { title: 'Room B: สัมภาษณ์พนักงานใหม่', start: '2026-06-25T13:00:00', end: '2026-06-25T15:00:00', backgroundColor: '#10b981', borderColor: '#10b981' },
                    { title: 'Room C: อัปเดตงานทีม Design', start: '2026-06-26T10:00:00', end: '2026-06-26T12:00:00', backgroundColor: '#f59e0b', borderColor: '#f59e0b' },
                    { title: 'Room A: อบรมการป้องกันความปลอดภัยทางไซเบอร์', start: '2026-06-27T13:30:00', end: '2026-06-27T16:30:00', backgroundColor: '#4338ca', borderColor: '#4338ca' }
                ]
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
