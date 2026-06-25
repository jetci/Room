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

// ตรวจสอบสิทธิ์การเข้าถึง (ต้องเป็น Admin เท่านั้น)
if ($role !== 'Admin') {
    $_SESSION['error_message'] = "ปฏิเสธการเข้าถึง: เมนูนี้สงวนสิทธิ์เฉพาะผู้ดูแลระบบส่วนกลาง (Admin) เท่านั้น";
    header("Location: login.php");
    exit;
}

// จัดการคำร้องขออนุมัติหรือยกเลิกการจองสนามกีฬา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_token']) || $_POST['_token'] !== csrf_token()) {
        $_SESSION['error_message'] = "คำขอไม่ถูกต้อง (CSRF Token Mismatch)";
        header("Location: admin_sports.php");
        exit;
    }

    $action = $_POST['action'] ?? '';
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($action === 'approve') {
        $_SESSION['success_message'] = "อนุมัติคำร้องขอเข้าใช้บริการสนามกีฬาและให้ยืมอุปกรณ์กีฬา (ID: #SP-{$bookingId}) เรียบร้อยแล้ว";
    } elseif ($action === 'reject') {
        $_SESSION['success_message'] = "ปฏิเสธคำร้องขอเข้าใช้บริการสนามกีฬา (ID: #SP-{$bookingId}) เรียบร้อยแล้ว";
    }

    header("Location: admin_sports.php");
    exit;
}

$successMsg = $_SESSION['success_message'] ?? null;
$errorMsg = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// ดึงข้อมูลการจองสนามกีฬาและรายการสนาม
$sportsBookings = Booking::getAllSportsBookings();
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
    <title>จัดการสนามกีฬา & อนุมัติการยืมอุปกรณ์ (Admin Sports) - <?= htmlspecialchars($currentOrgName) ?></title>
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
        
        .card-custom { border-radius: 24px; border: 1px solid #e2e8f0; background: #ffffff; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); }
        .table-custom th { background-color: #f1f5f9; color: #334155; font-weight: 700; border-bottom: 2px solid #e2e8f0; padding: 16px; font-size: 0.9rem; }
        .table-custom td { padding: 20px 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        .table-custom tr:hover td { background-color: #f8fafc; }
        
        .facility-list-item { border-left: 4px solid #4338ca; background-color: #f8fafc; border-radius: 0 16px 16px 0; margin-bottom: 12px; padding: 16px 20px; }
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
                <span class="badge bg-danger px-3 py-2 fs-7 rounded-pill"><i class="fa-solid fa-user-shield me-2"></i>ผู้ดูแลระบบส่วนกลาง (Admin)</span>
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
                    <li class="nav-item"><a class="nav-link" href="sports.php"><i class="fa-solid fa-futbol me-3"></i> จองสนามกีฬา & อุปกรณ์</a></li>
                    <li class="nav-item"><a class="nav-link" href="calendar.php"><i class="fa-solid fa-calendar-view-month me-3"></i> ปฏิทินการจอง</a></li>
                    <li class="nav-item"><a class="nav-link" href="approvals.php"><i class="fa-solid fa-inbox me-3"></i> คิวรออนุมัติ</a></li>
                    <li class="nav-item"><a class="nav-link" href="register_ext.php"><i class="fa-solid fa-user-plus me-3"></i> คำขอลงทะเบียน</a></li>
                </ul>

                <div class="text-muted fs-8 fw-bold text-uppercase mb-3 px-3">สำหรับแอดมิน (Admin)</div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="admin_rooms.php"><i class="fa-solid fa-door-open me-3"></i> จัดการห้องประชุม</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_sports.php"><i class="fa-solid fa-trophy me-3"></i> จัดการสนามกีฬา</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_equipments.php"><i class="fa-solid fa-couch me-3"></i> จัดการอุปกรณ์</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & Export</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_users.php"><i class="fa-solid fa-users-gear me-3"></i> จัดการผู้ใช้</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_announcements.php"><i class="fa-solid fa-bullhorn me-3"></i> ประกาศส่วนกลาง</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_settings.php"><i class="fa-solid fa-house-flag me-3"></i> ตั้งค่าข้อมูล & โลโก้</a></li>
                    <li class="nav-item"><a class="nav-link" href="audit_logs.php"><i class="fa-solid fa-shield-halved me-3"></i> Audit Logs</a></li>
                </ul>
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
                        <h3 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-trophy me-2 text-indigo"></i> จัดการสนามกีฬา & อนุมัติการยืมอุปกรณ์กีฬา (Sports Admin)</h3>
                        <p class="text-muted mb-0">ดูแลระบบลานกีฬาอเนกประสงค์ ตรวจสอบคำร้องขอเข้าใช้งาน และอนุมัติการให้ยืมอุปกรณ์กีฬาขององค์กร</p>
                    </div>
                    <a href="sports.php" class="btn btn-outline-indigo px-4 py-3 rounded-3 fw-semibold"><i class="fa-solid fa-eye me-2"></i> ดูหน้ามุมมองผู้ใช้ทั่วไป</a>
                </div>

                <div class="row g-5">
                    
                    <!-- LEFT COLUMN: BOOKINGS TABLE -->
                    <div class="col-lg-8">
                        <div class="card card-custom p-4">
                            <h5 class="fw-bold mb-4 text-dark px-2"><i class="fa-solid fa-list-check me-2 text-primary"></i> รายการคำร้องขอจองสนามกีฬาและขอยืมอุปกรณ์</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>รหัส</th>
                                            <th>สนามกีฬา / กิจกรรม</th>
                                            <th>ผู้จอง & เบอร์ติดต่อ</th>
                                            <th>วัน-เวลาที่จอง</th>
                                            <th>สถานะ</th>
                                            <th class="text-end">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sportsBookings as $booking): ?>
                                            <?php 
                                                // แปลงปี พ.ศ.
                                                $dateObj = new DateTime($booking['sports_date']);
                                                $yearBE = (int)$dateObj->format('Y') + 543;
                                                $displayDate = $dateObj->format('d/m/') . $yearBE;
                                            ?>
                                            <tr>
                                                <td class="fw-bold text-indigo">#SP-<?= htmlspecialchars($booking['id']) ?></td>
                                                <div>
                                                    <td class="fw-bold text-dark"><?= htmlspecialchars($booking['facility_name']) ?><br>
                                                    <span class="text-muted fs-8 fw-normal"><i class="fa-solid fa-bookmark me-1 text-warning"></i> <?= htmlspecialchars($booking['title']) ?></span>
                                                    <?php if (!empty($booking['borrow_equipments'])): ?>
                                                        <div class="mt-2 py-1 px-3 bg-indigo-subtle text-indigo rounded-3 fs-8 fw-semibold">
                                                            <i class="fa-solid fa-box-open me-1"></i> ยืม: <?= htmlspecialchars($booking['borrow_equipments']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($booking['user_notes'])): ?>
                                                        <div class="mt-1 text-secondary fs-8"><strong>หมายเหตุ:</strong> <?= htmlspecialchars($booking['user_notes']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                </div>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($booking['full_name']) ?></div>
                                                    <div class="text-secondary fs-8"><?= htmlspecialchars($booking['department_name']) ?></div>
                                                    <div class="text-muted fs-8"><i class="fa-solid fa-phone me-1 text-success"></i> <?= htmlspecialchars($booking['phone']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border px-3 py-2 fs-7 mb-1"><?= $displayDate ?></span><br>
                                                    <span class="text-secondary fs-8 fw-semibold"><i class="fa-regular fa-clock me-1 text-indigo"></i> <?= htmlspecialchars($booking['start_time']) ?> - <?= htmlspecialchars($booking['end_time']) ?> น.</span>
                                                </td>
                                                <td>
                                                    <?php if ($booking['approval_status'] === 'approved'): ?>
                                                        <span class="badge bg-success px-3 py-2 fs-8 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> อนุมัติแล้ว</span>
                                                    <?php elseif ($booking['approval_status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark px-3 py-2 fs-8 rounded-pill"><i class="fa-solid fa-clock me-1"></i> รอพิจารณา</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger px-3 py-2 fs-8 rounded-pill"><i class="fa-solid fa-circle-xmark me-1"></i> ปฏิเสธ</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($booking['approval_status'] === 'pending'): ?>
                                                        <form action="admin_sports.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-sm btn-success px-3 py-2 rounded-3 fw-bold mb-1 w-100 shadow-sm"><i class="fa-solid fa-check me-1"></i> อนุมัติ</button>
                                                        </form>
                                                        <form action="admin_sports.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn btn-sm btn-danger px-3 py-2 rounded-3 fw-bold w-100 shadow-sm"><i class="fa-solid fa-xmark me-1"></i> ปฏิเสธ</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-light px-3 py-2 rounded-3 border text-muted w-100" disabled><i class="fa-solid fa-lock me-1"></i> ปิดคิว</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT COLUMN: FACILITIES LIST -->
                    <div class="col-lg-4">
                        <div class="card card-custom p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-cubes me-2 text-indigo"></i> ฐานข้อมูลสนามกีฬา</h5>
                                <button class="btn btn-sm btn-custom-primary px-3 py-2 rounded-3 fw-semibold" onclick="alert('ฟังก์ชันจำลอง: แอดมินสามารถกดเพิ่มสนามกีฬาใหม่เข้าสู่ระบบได้ทันที')"><i class="fa-solid fa-plus me-1"></i> เพิ่มสนามกีฬา</button>
                            </div>
                            <p class="text-muted fs-7 mb-4">สนามกีฬา ลานกีฬา และอุปกรณ์อเนกประสงค์ที่ลงทะเบียนเปิดให้บริการยืม-จองในระบบ</p>

                            <?php foreach ($sportsFacilities as $fac): ?>
                                <div class="facility-list-item shadow-sm">
                                    <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($fac['facility_name']) ?></h6>
                                    <p class="text-secondary fs-8 mb-2"><i class="fa-solid fa-location-dot me-2 text-danger"></i> <?= htmlspecialchars($fac['location']) ?></p>
                                    <div class="fs-8 text-muted">
                                        <strong>อุปกรณ์ที่มี:</strong> <?= htmlspecialchars($fac['available_equipments']) ?>
                                    </div>
                                    <div class="mt-3 d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-light border px-3 py-1 fs-8 fw-semibold text-indigo" onclick="alert('ฟังก์ชันจำลอง: กำลังเปิดฟอร์มแก้ไขข้อมูลสนาม ID #<?= $fac['id'] ?>')"><i class="fa-solid fa-pen-to-square me-1"></i> แก้ไข</button>
                                        <button class="btn btn-sm btn-light border px-3 py-1 fs-8 fw-semibold text-danger" onclick="alert('ฟังก์ชันจำลอง: ปิดการใช้งานสนาม ID #<?= $fac['id'] ?> ชั่วคราว')"><i class="fa-solid fa-trash me-1"></i> ระงับ</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
