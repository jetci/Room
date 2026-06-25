<?php
require_once __DIR__ . '/../routes/web.php';
use App\Models\Booking;

$currentUser = $_SESSION['user'] ?? [
    'full_name' => 'คุณสมชาย บริหารดี',
    'role_name' => 'Admin',
    'email' => 'admin@wiang.go.th',
    'status' => 'active'
];
$role = $currentUser['role_name'] ?? 'User';

// จัดการการส่งฟอร์มจองสนามกีฬา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_token']) || $_POST['_token'] !== csrf_token()) {
        $_SESSION['error_message'] = "คำขอไม่ถูกต้อง (CSRF Token Mismatch)";
        header("Location: sports.php");
        exit;
    }

    $title = $_POST['title'] ?? '';
    $facilityId = (int)($_POST['facility_id'] ?? 0);
    $sportsDate = $_POST['sports_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $borrowEquipments = $_POST['borrow_equipments'] ?? [];
    $userNotes = $_POST['user_notes'] ?? '';

    // แปลงอาร์เรย์อุปกรณ์เป็นข้อความ
    $borrowSummary = is_array($borrowEquipments) ? implode(', ', $borrowEquipments) : $borrowEquipments;

    // ตรวจสอบการจองย้อนหลัง
    $currentDate = date('Y-m-d');
    if ($sportsDate < $currentDate) {
        $_SESSION['error_message'] = "ไม่สามารถจองสนามกีฬาหรือยืมอุปกรณ์ย้อนหลังได้ กรุณาเลือกวันที่ปัจจุบันหรือล่วงหน้า";
        header("Location: sports.php");
        exit;
    }

    // สร้างการจอง
    $bookingData = [
        'facility_id' => $facilityId,
        'user_id' => $currentUser['id'] ?? 3,
        'title' => $title,
        'sports_date' => $sportsDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'borrow_equipments' => $borrowSummary,
        'user_notes' => $userNotes
    ];

    Booking::createSportsBooking($bookingData);

    // แจ้งเตือนความสำเร็จ
    $_SESSION['success_message'] = "ส่งคำร้องขอจองสนามกีฬาและยืมอุปกรณ์กีฬาเรียบร้อยแล้ว กรุณารอเจ้าหน้าที่พิจารณาอนุมัติ";
    header("Location: sports.php");
    exit;
}

$successMsg = $_SESSION['success_message'] ?? null;
$errorMsg = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// ดึงข้อมูลสนามกีฬา
$sportsFacilities = Booking::getAllSportsFacilities();

// ดึงข้อมูลแบรนดิ้งองค์กร
$currentLogo = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
$currentOrgName = $_SESSION['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจองสนามกีฬา & ยืมอุปกรณ์กีฬา - <?= htmlspecialchars($currentOrgName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; }
        .sidebar { min-height: calc(100vh - 84px); background-color: #ffffff; border-right: 1px solid #e2e8f0; }
        .nav-link { font-weight: 600; color: #64748b; padding: 14px 24px; border-radius: 12px; margin-bottom: 6px; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background-color: #f1f5f9; color: #4338ca; }
        .nav-link.active { background-color: #e0e7ff; color: #4338ca; border-left: 5px solid #4338ca; }
        
        /* Premium Custom Sports Card Styling */
        .facility-card { border-radius: 24px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .facility-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(67, 56, 202, 0.12); border-color: #cbd5e1; }
        .facility-img-container { position: relative; height: 260px; overflow: hidden; }
        .facility-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .facility-card:hover .facility-img { transform: scale(1.05); }
        .category-badge { position: absolute; top: 16px; left: 16px; background: rgba(15, 23, 42, 0.85); color: #ffffff; padding: 8px 18px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; backdrop-filter: blur(8px); }
        .capacity-badge { position: absolute; bottom: 16px; right: 16px; background: rgba(255, 255, 255, 0.95); color: #0f172a; padding: 6px 16px; border-radius: 14px; font-weight: 700; font-size: 0.85rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        /* Modal Customization */
        .sports-modal-header { background: linear-gradient(135deg, #1e1b4b, #312e81); color: white; border-radius: 24px 24px 0 0; padding: 30px; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom py-3 sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="<?= $currentLogo ?>" alt="Logo" class="me-3 rounded-circle shadow-sm" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #cbd5e1;">
                <div>
                    <span class="fw-bold fs-5 text-indigo"><?= htmlspecialchars($currentOrgName) ?></span><br>
                    <span class="fs-7 text-secondary">ระบบจองห้องประชุมและสนามกีฬาออนไลน์ (Smart Facility Booking)</span>
                </div>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-indigo-subtle text-indigo px-3 py-2 fs-7 rounded-pill"><i class="fa-solid fa-flag me-2"></i>ระบบจองสนามกีฬา & ลานกีฬา</span>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle px-3 py-2 rounded-3 fw-semibold text-dark border" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-circle-user me-2 text-indigo"></i> <?= htmlspecialchars($currentUser['full_name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 mt-2">
                        <li><a class="dropdown-item py-2 fw-semibold text-muted" href="#"><i class="fa-solid fa-id-badge me-2 text-indigo"></i> บทบาท: <?= htmlspecialchars($role) ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 fw-semibold text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            
            <!-- SIDEBAR -->
            <div class="col-lg-2 sidebar p-4">
                <div class="text-muted fs-8 fw-bold text-uppercase mb-3 px-3">เมนูระบบส่วนกลาง</div>
                <ul class="nav flex-column mb-4">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fa-solid fa-chart-pie me-3"></i> หน้าหลัก (Dashboard)</a></li>
                    <li class="nav-item"><a class="nav-link" href="search.php"><i class="fa-solid fa-calendar-days me-3"></i> ค้นหา & จองห้อง</a></li>
                    <li class="nav-item"><a class="nav-link active" href="sports.php"><i class="fa-solid fa-futbol me-3"></i> จองสนามกีฬา & อุปกรณ์</a></li>
                    <li class="nav-item"><a class="nav-link" href="calendar.php"><i class="fa-solid fa-calendar-view-month me-3"></i> ปฏิทินการจอง</a></li>
                    <li class="nav-item"><a class="nav-link" href="approvals.php"><i class="fa-solid fa-inbox me-3"></i> คิวรออนุมัติ</a></li>
                    <li class="nav-item"><a class="nav-link" href="register_ext.php"><i class="fa-solid fa-user-plus me-3"></i> คำขอลงทะเบียน</a></li>
                </ul>

                <?php if ($role === 'Admin'): ?>
                <div class="text-muted fs-8 fw-bold text-uppercase mb-3 px-3">สำหรับแอดมิน (Admin)</div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="admin_rooms.php"><i class="fa-solid fa-door-open me-3"></i> จัดการห้องประชุม</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_sports.php"><i class="fa-solid fa-trophy me-3"></i> จัดการสนามกีฬา</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_equipments.php"><i class="fa-solid fa-couch me-3"></i> จัดการอุปกรณ์</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & Export</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_users.php"><i class="fa-solid fa-users-gear me-3"></i> จัดการผู้ใช้</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_announcements.php"><i class="fa-solid fa-bullhorn me-3"></i> ประกาศส่วนกลาง</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_settings.php"><i class="fa-solid fa-house-flag me-3"></i> ตั้งค่าข้อมูล & โลโก้</a></li>
                    <li class="nav-item"><a class="nav-link" href="audit_logs.php"><i class="fa-solid fa-shield-halved me-3"></i> Audit Logs</a></li>
                </ul>
                <?php endif; ?>
            </div>

            <!-- MAIN WORKSPACE -->
            <div class="col-lg-10 p-5">
                
                <?php if ($successMsg): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center p-4 mb-4 shadow-sm" style="border-radius: 16px; border: none; background-color: #dcfce7; color: #15803d;" role="alert">
                        <i class="fa-solid fa-circle-check fs-3 me-3"></i>
                        <div><strong class="fw-bold">สำเร็จ!</strong> <?= htmlspecialchars($successMsg) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center p-4 mb-4 shadow-sm" style="border-radius: 16px; border: none; background-color: #fee2e2; color: #b91c1c;" role="alert">
                        <i class="fa-solid fa-circle-exclamation fs-3 me-3"></i>
                        <div><strong class="fw-bold">ข้อผิดพลาด:</strong> <?= htmlspecialchars($errorMsg) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- PAGE HEADER -->
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h3 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-futbol me-2 text-indigo"></i> ค้นหา & จองสนามกีฬาและยืมอุปกรณ์กีฬา</h3>
                        <p class="text-muted mb-0">เปิดให้บริการจองสนามกีฬา ลานกีฬาอเนกประสงค์ และขอยืมอุปกรณ์กีฬาเพื่อส่งเสริมสุขภาพชุมชนและบุคลากร</p>
                    </div>
                    <?php if ($role === 'Admin'): ?>
                        <a href="admin_sports.php" class="btn btn-custom-primary px-4 py-3 rounded-3 fw-semibold shadow-sm"><i class="fa-solid fa-gear me-2"></i> แอดมินจัดการสนามกีฬา</a>
                    <?php endif; ?>
                </div>

                <!-- ALERT GUIDELINES -->
                <div class="alert alert-indigo-subtle p-4 rounded-4 mb-5 shadow-sm d-flex align-items-center" style="border: 1px solid #c7d2fe; background-color: #e0e7ff; color: #3730a3;">
                    <i class="fa-solid fa-trophy fs-1 me-4 text-indigo"></i>
                    <div>
                        <h6 class="fw-bold mb-1">กติกาการจองสนามกีฬาและขอยืมอุปกรณ์:</h6>
                        <p class="mb-0 fs-7">ผู้จองสามารถเลือกยืมอุปกรณ์กีฬา (อาทิ ลูกฟุตบอล, ลูกบาส, เสื้อเอี๊ยม, ขอเปิดไฟสปอตไลท์) ได้พร้อมกับการจองสนามทันที กรุณาจองล่วงหน้าอย่างน้อย 1 วันทำการ และนำบัตรประชาชนมาแสดงกับเจ้าหน้าที่สนามในวันเข้าใช้งาน</p>
                    </div>
                </div>

                <!-- SPORTS FACILITIES GRID -->
                <div class="row g-4">
                    <?php foreach ($sportsFacilities as $facility): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="facility-card h-100 d-flex flex-column">
                                <div class="facility-img-container">
                                    <img src="<?= htmlspecialchars($facility['images'][0] ?? 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=800&q=80') ?>" alt="Facility Image" class="facility-img">
                                    <span class="category-badge"><i class="fa-solid fa-volleyball me-2 text-warning"></i> <?= htmlspecialchars($facility['category']) ?></span>
                                    <span class="capacity-badge"><i class="fa-solid fa-user-group me-1 text-indigo"></i> รองรับ <?= htmlspecialchars($facility['capacity']) ?> คน</span>
                                </div>
                                <div class="p-4 d-flex flex-column flex-grow-1">
                                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($facility['facility_name']) ?></h5>
                                    <p class="text-secondary fs-7 mb-3"><i class="fa-solid fa-location-dot me-2 text-danger"></i> <?= htmlspecialchars($facility['location']) ?></p>
                                    
                                    <!-- อุปกรณ์ที่ให้ยืม -->
                                    <div class="bg-light p-3 rounded-4 mb-3 border flex-grow-1">
                                        <div class="fw-bold fs-7 text-indigo mb-1"><i class="fa-solid fa-box-open me-2"></i> อุปกรณ์และสิ่งอำนวยความสะดวกที่มีให้ยืม:</div>
                                        <p class="text-muted fs-7 mb-0"><?= htmlspecialchars($facility['available_equipments']) ?></p>
                                    </div>

                                    <!-- ระเบียบการใช้งาน -->
                                    <div class="fs-8 text-secondary mb-4">
                                        <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> <strong>ข้อปฏิบัติ:</strong> <?= htmlspecialchars($facility['rules']) ?>
                                    </div>

                                    <button type="button" class="btn btn-custom-primary w-100 py-3 rounded-3 fw-bold shadow-sm mt-auto" onclick="openBookingModal(<?= htmlspecialchars(json_encode($facility)) ?>)">
                                        <i class="fa-solid fa-circle-check me-2"></i> จองสนาม & ขอยืมอุปกรณ์นี้
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- BOOKING MODAL -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="sports-modal-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold fs-7"><i class="fa-solid fa-calendar-check me-2"></i>แบบฟอร์มขอเข้าใช้บริการสนามกีฬา</span>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <h4 class="modal-title fw-bold" id="modalFacilityName">ชื่อสนามกีฬา</h4>
                    <p class="mb-0 fs-7 text-indigo-200" id="modalFacilityLocation"><i class="fa-solid fa-location-dot me-2 text-warning"></i> สถานที่ตั้ง</p>
                </div>
                
                <form action="sports.php" method="POST">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="facility_id" id="modalFacilityId">

                    <div class="modal-body p-5">
                        <div class="row g-4">
                            <!-- หัวข้อกิจกรรม -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark">หัวข้อกิจกรรม / วัตถุประสงค์ (Activity Purpose) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control p-3 rounded-3" name="title" required placeholder="ตัวอย่าง: แข่งฟุตบอลกระชับมิตร, ซ้อมกีฬาประจำสัปดาห์, แข่งเปตองชุมชน">
                            </div>

                            <!-- วันที่จอง -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">วันที่ต้องการจอง (Date) <span class="text-danger">*</span></label>
                                <input type="date" class="form-control p-3 rounded-3" name="sports_date" min="<?= date('Y-m-d') ?>" required>
                                <span class="text-muted fs-8 mt-1 d-block">ระบบแสดงผลตามปี พ.ศ. ของเอกสารราชการ</span>
                            </div>

                            <!-- เวลาเริ่ม -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">เวลาเริ่มต้น (Start Time) <span class="text-danger">*</span></label>
                                <select class="form-select p-3 rounded-3" name="start_time" required>
                                    <option value="">เลือกเวลาเริ่ม...</option>
                                    <option value="08:00">08:00 น.</option>
                                    <option value="09:00">09:00 น.</option>
                                    <option value="10:00">10:00 น.</option>
                                    <option value="11:00">11:00 น.</option>
                                    <option value="12:00">12:00 น.</option>
                                    <option value="13:00">13:00 น.</option>
                                    <option value="14:00">14:00 น.</option>
                                    <option value="15:00">15:00 น.</option>
                                    <option value="16:00">16:00 น.</option>
                                    <option value="17:00">17:00 น.</option>
                                    <option value="18:00">18:00 น.</option>
                                    <option value="19:00">19:00 น.</option>
                                    <option value="20:00">20:00 น.</option>
                                </select>
                            </div>

                            <!-- เวลาสิ้นสุด -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">เวลาสิ้นสุด (End Time) <span class="text-danger">*</span></label>
                                <select class="form-select p-3 rounded-3" name="end_time" required>
                                    <option value="">เลือกเวลาสิ้นสุด...</option>
                                    <option value="09:00">09:00 น.</option>
                                    <option value="10:00">10:00 น.</option>
                                    <option value="11:00">11:00 น.</option>
                                    <option value="12:00">12:00 น.</option>
                                    <option value="13:00">13:00 น.</option>
                                    <option value="14:00">14:00 น.</option>
                                    <option value="15:00">15:00 น.</option>
                                    <option value="16:00">16:00 น.</option>
                                    <option value="17:00">17:00 น.</option>
                                    <option value="18:00">18:00 น.</option>
                                    <option value="19:00">19:00 น.</option>
                                    <option value="20:00">20:00 น.</option>
                                    <option value="21:00">21:00 น.</option>
                                </select>
                            </div>

                            <!-- เลือกอุปกรณ์ที่ต้องการยืม -->
                            <div class="col-md-12 mt-4 pt-3 border-top">
                                <label class="form-label fw-bold text-dark mb-3"><i class="fa-solid fa-box-open me-2 text-indigo"></i> อุปกรณ์กีฬาและสิ่งอำนวยความสะดวกที่ต้องการยืม (เลือกได้มากกว่า 1)</label>
                                <div class="row g-3" id="equipmentCheckboxes">
                                    <!-- Dynamic Checkboxes based on facility -->
                                </div>
                            </div>

                            <!-- หมายเหตุ -->
                            <div class="col-md-12 mt-4">
                                <label class="form-label fw-bold text-dark">รายละเอียดเพิ่มเติม / ข้อความถึงเจ้าหน้าที่สนาม (Optional)</label>
                                <textarea class="form-control p-3 rounded-3" name="user_notes" rows="3" placeholder="ตัวอย่าง: ขอยืมกุญแจห้องเปลี่ยนเครื่องแต่งกาย, ขอเจ้าหน้าที่เปิดสปอตไลท์สนาม..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-top p-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-light px-4 py-3 rounded-3 fw-semibold border" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-2"></i> ยกเลิก</button>
                        <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-bold shadow-sm"><i class="fa-solid fa-paper-plane me-2"></i> ส่งคำร้องขอจองสนามกีฬา</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));

        function openBookingModal(facility) {
            document.getElementById('modalFacilityId').value = facility.id;
            document.getElementById('modalFacilityName').innerText = facility.facility_name;
            document.getElementById('modalFacilityLocation').innerHTML = `<i class="fa-solid fa-location-dot me-2 text-warning"></i> ${facility.location} (ประเภท: ${facility.category})`;

            // สร้าง Checkbox อุปกรณ์จากข้อมูล available_equipments
            const container = document.getElementById('equipmentCheckboxes');
            container.innerHTML = '';
            
            if (facility.available_equipments) {
                const items = facility.available_equipments.split(', ');
                items.forEach((item, index) => {
                    const col = document.createElement('div');
                    col.className = 'col-md-6';
                    col.innerHTML = `
                        <div class="form-check p-3 bg-light rounded-3 border d-flex align-items-center">
                            <input class="form-check-input ms-1 me-3" type="checkbox" name="borrow_equipments[]" value="${item}" id="eq_${index}">
                            <label class="form-check-label fw-semibold text-dark flex-grow-1" for="eq_${index}">
                                ${item}
                            </label>
                        </div>
                    `;
                    container.appendChild(col);
                });
            } else {
                container.innerHTML = '<div class="col-12 text-muted fs-7">ไม่มีอุปกรณ์ระบุไว้สำหรับสนามนี้</div>';
            }

            bookingModal.show();
        }
    </script>
</body>
</html>
