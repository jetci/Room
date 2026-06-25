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
    $_SESSION['error_message'] = 'บัญชีของคุณอยู่ระหว่างรอการอนุมัติจาก Approver ไม่สามารถใช้งานเมนูค้นหาห้องว่างและจองห้องประชุมได้';
    header("Location: dashboard.php");
    exit;
}

$rooms = Booking::getAllRooms();

// จำลองการค้นหา
$searched = isset($_GET['search']);
$searchDate = $_GET['search_date'] ?? date('Y-m-d');
$searchCapacity = (int)($_GET['capacity'] ?? 0);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาห้องว่าง - Smart Room Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- Flatpickr CSS & JS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
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
                            <a class="nav-link" href="calendar.php"><i class="fa-solid fa-calendar-days me-3"></i> ปฏิทินการจอง</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <?php if ($userStatus === 'inactive'): ?>
                            <a class="nav-link text-muted" href="#" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถใช้งานเมนูจองได้ในขณะนี้'); return false;"><i class="fa-solid fa-magnifying-glass me-3 text-secondary"></i> ค้นหาห้องว่าง <i class="fa-solid fa-lock ms-2 text-warning"></i></a>
                        <?php else: ?>
                            <a class="nav-link active" href="search.php"><i class="fa-solid fa-magnifying-glass me-3"></i> ค้นหาห้องว่าง</a>
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
                        <h4 class="fw-bold mb-1">ค้นหาห้องประชุมว่าง (Search Available Rooms)</h4>
                        <p class="text-muted mb-0">ค้นหาห้องว่างตามวันที่ เวลา และจำนวนผู้เข้าร่วมประชุมที่ต้องการ</p>
                    </div>
                    <button class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#bookingModal">
                        <i class="fa-solid fa-circle-plus me-2"></i>จองห้องประชุมใหม่
                    </button>
                </div>

                <!-- Search Filter Card -->
                <div class="card main-card bg-white p-4 mb-4">
                    <form action="search.php" method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="search" value="1">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">วันที่ต้องการจอง</label>
                            <input type="text" class="form-control p-3 datepicker bg-white" name="search_date" value="<?= htmlspecialchars($searchDate) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">จำนวนผู้เข้าร่วมขั้นต่ำ (คน)</label>
                            <input type="number" class="form-control p-3" name="capacity" value="<?= $searchCapacity ?>" placeholder="ตัวอย่าง: 10">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-custom-primary w-100 py-3"><i class="fa-solid fa-magnifying-glass me-2"></i> ค้นหาห้องว่าง</button>
                        </div>
                    </form>
                </div>

                <!-- Search Results -->
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> ห้องประชุมที่ว่างพร้อมจอง ในวันที่ <?= htmlspecialchars($searchDate) ?></h6>
                <div class="row g-4 mb-5">
                    <?php foreach ($rooms as $room): ?>
                        <?php if ($searchCapacity > 0 && $room['capacity'] < $searchCapacity) continue; ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card main-card bg-white p-4 h-100 d-flex flex-column justify-content-between overflow-hidden">
                                <div>
                                    <!-- Room Image Banner & Gallery -->
                                    <?php if (!empty($room['images'])): ?>
                                        <div class="mb-3">
                                            <img src="<?= htmlspecialchars($room['images'][0]) ?>" class="w-100 object-fit-cover rounded-3 shadow-sm mb-2" style="height: 180px;">
                                            <?php if (count($room['images']) > 1): ?>
                                                <div class="d-flex gap-2 overflow-x-auto py-1">
                                                    <?php foreach (array_slice($room['images'], 1) as $thumb): ?>
                                                        <img src="<?= htmlspecialchars($thumb) ?>" class="rounded-3 object-fit-cover shadow-sm flex-shrink-0" width="60" height="45" style="border: 1px solid #e2e8f0;">
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="fw-bold text-indigo mb-0"><?= htmlspecialchars($room['room_name']) ?></h5>
                                        <span class="badge bg-green-light px-3 py-2 fs-7">ว่างพร้อมใช้</span>
                                    </div>
                                    <p class="text-muted fs-7 mb-3"><i class="fa-solid fa-location-dot me-2"></i> <?= htmlspecialchars($room['location']) ?></p>
                                    <div class="mb-4">
                                        <span class="badge bg-indigo-light me-2 mb-1"><i class="fa-solid fa-user-group me-1"></i> ความจุ <?= $room['capacity'] ?> คน</span>
                                        <?php 
                                            $cardFeatures = !empty($room['features_list']) ? $room['features_list'] : (!empty($room['equipment_summary']) ? explode(', ', $room['equipment_summary']) : []); 
                                            foreach ($cardFeatures as $feat): 
                                        ?>
                                            <span class="badge bg-purple-light me-2 mb-1"><i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($feat) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary w-100 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#bookingModal" onclick="document.querySelector('select[name=room_id]').value='<?= $room['id'] ?>'; document.querySelector('input[name=meeting_date]').value='<?= $searchDate ?>';">
                                    <i class="fa-solid fa-bookmark me-2"></i> จองห้องนี้ทันที
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['room_name']) ?> (<?= htmlspecialchars($r['location']) ?> - รองรับ <?= $r['capacity'] ?> คน)</option>
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
            if (typeof flatpickr !== 'undefined') {
                flatpickr(".datepicker", {
                    locale: "th",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d F Y",
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
