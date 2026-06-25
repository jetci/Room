<?php
require_once __DIR__ . '/../routes/web.php';
use App\Models\Booking;
$rooms = Booking::getAllRooms();
$successMsg = $_SESSION['success_message'] ?? null;
$errorMsg = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$currentUser = $_SESSION['user'] ?? [
    'full_name' => 'คุณสมชาย บริหารดี',
    'role_name' => 'Admin',
    'email' => 'admin@wiang.go.th',
    'status' => 'active'
];
$avatarName = urlencode($currentUser['full_name']);
$role = $currentUser['role_name'] ?? 'User';
$userStatus = $currentUser['status'] ?? 'active';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard (<?= htmlspecialchars($role) ?>) - Smart Room Booking (อบต.เวียง)</title>
    <!-- Bootstrap 5 & Google Fonts & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Chart.js & FullCalendar CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>
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
            <!-- Sidebar Navigation (Role-based Filter) -->
            <div class="col-lg-2 d-none d-lg-block sidebar py-4 px-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="fa-solid fa-chart-pie me-3"></i> แดชบอร์ด</a>
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
                
                <!-- Alert Messages -->
                <?php if ($successMsg): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center p-4 mb-4" style="border-radius: 16px; border: none; background-color: #dcfce7; color: #15803d;" role="alert">
                        <i class="fa-solid fa-circle-check fs-3 me-3"></i>
                        <div>
                            <strong class="fw-bold">สำเร็จ!</strong> <?= htmlspecialchars($successMsg) ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center p-4 mb-4" style="border-radius: 16px; border: none; background-color: #fee2e2; color: #b91c1c;" role="alert">
                        <i class="fa-solid fa-circle-exclamation fs-3 me-3"></i>
                        <div>
                            <strong class="fw-bold">ข้อผิดพลาด:</strong> <?= htmlspecialchars($errorMsg) ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- System Emergency Announcement Banner -->
                <div class="alert alert-dismissible fade show d-flex align-items-center p-4 mb-4 shadow-sm" style="border-radius: 20px; border: 1px solid #fed7aa; background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: #9a3412;" role="alert">
                    <i class="fa-solid fa-bullhorn fa-2x me-4 text-warning"></i>
                    <div>
                        <strong class="fw-bold fs-6">📢 ประกาศส่วนกลาง: งานซ่อมบำรุงระบบไฟฟ้าและระบบปรับอากาศ</strong><br>
                        <span class="fs-7">ในวันเสาร์ที่ 4 กรกฎาคม 2569 ห้องประชุมสภาใหญ่ (Room A) และห้องประชุมเล็ก (Room B) จะงดให้บริการชั่วคราวเพื่อซ่อมบำรุงประจำปี ขออภัยในความไม่สะดวกมา ณ ที่นี้</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <!-- Inactive User Alert Banner -->
                <?php if ($userStatus === 'inactive'): ?>
                    <div class="alert alert-warning d-flex align-items-center p-4 mb-4 shadow-sm" style="border-radius: 20px; border: 1px solid #f59e0b; background-color: #fffbeb; color: #b45309;" role="alert">
                        <i class="fa-solid fa-user-lock fa-2x me-3 text-warning"></i>
                        <div>
                            <strong class="fw-bold fs-6">🔒 บัญชีของคุณอยู่ระหว่างรอการอนุมัติจาก Approver (ผู้อนุมัติ)</strong><br>
                            <span class="fs-7">ระบบการจองห้องประชุม ปฏิทิน และการค้นหาห้องว่าง จะถูกระงับการใช้งานชั่วคราว จนกว่าคำขอของคุณจะได้รับการอนุมัติเข้าสู่ระบบอย่างเป็นทางการ</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Page Header & Action -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">ภาพรวมระบบจองห้องประชุม (<?= htmlspecialchars($role) ?> Dashboard)</h4>
                        <p class="text-muted mb-0">ยินดีต้อนรับ <?= htmlspecialchars($currentUser['full_name']) ?> | องค์การบริหารส่วนตำบลเวียง</p>
                    </div>
                    <?php if ($userStatus === 'inactive'): ?>
                        <button class="btn btn-secondary px-4 py-3 rounded-3 fw-semibold" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถจองห้องประชุมได้ในขณะนี้');">
                            <i class="fa-solid fa-lock me-2"></i>จองห้องประชุมใหม่ (ถูกระงับ)
                        </button>
                    <?php else: ?>
                        <button class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#bookingModal">
                            <i class="fa-solid fa-circle-plus me-2"></i>จองห้องประชุมใหม่
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ============================================== -->
                <!-- 1. DASHBOARD VIEW: ADMIN (ผู้ดูแลระบบ)          -->
                <!-- ============================================== -->
                <?php if ($role === 'Admin'): ?>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-stat bg-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1">18 <span class="fs-6 text-muted fw-normal">ห้อง</span></h3>
                                        <span class="text-muted fs-7">ห้องทั้งหมด (Active พร้อมใช้)</span>
                                    </div>
                                    <div class="icon-box bg-indigo-light"><i class="fa-solid fa-door-open"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-stat bg-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1">24 <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                                        <span class="text-success fw-bold fs-7"><i class="fa-solid fa-arrow-trend-up me-1"></i> +20%</span> <span class="text-muted fs-7">การจองวันนี้</span>
                                    </div>
                                    <div class="icon-box bg-green-light"><i class="fa-solid fa-calendar-check"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-stat bg-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1">7 <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                                        <span class="text-warning fw-bold fs-7"><i class="fa-solid fa-hourglass-half me-1"></i> รออนุมัติ</span> <span class="text-muted fs-7">ต้องดำเนินการ</span>
                                    </div>
                                    <div class="icon-box bg-yellow-light"><i class="fa-solid fa-bell"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-stat bg-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1">76%</h3>
                                        <span class="text-muted fs-7">อัตราการใช้งาน (เฉลี่ย 7 วันล่าสุด)</span>
                                    </div>
                                    <div class="icon-box bg-purple-light"><i class="fa-solid fa-chart-simple"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-8">
                            <div class="card main-card bg-white p-4 h-100">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-column text-indigo me-2"></i>จำนวนการจองแยกตามห้องประชุม (สัปดาห์นี้)</h6>
                                <div style="height: 280px;"><canvas id="bookingByRoomChart"></canvas></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card main-card bg-white p-4 h-100">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-donut text-indigo me-2"></i>สัดส่วนสถานะการจอง (120 รายการ)</h6>
                                <div style="height: 250px; display:flex; justify-content:center;"><canvas id="statusChart"></canvas></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- 2. DASHBOARD VIEW: APPROVER (ผู้อนุมัติ)       -->
                <!-- ============================================== -->
                <?php if ($role === 'Approver'): ?>
                    <div class="alert alert-info p-4 mb-4 d-flex align-items-center" style="border-radius: 20px; border: none; background-color: #eff6ff; color: #1e40af;">
                        <i class="fa-solid fa-user-shield fa-2x me-4 text-primary"></i>
                        <div>
                            <h5 class="fw-bold mb-1">ยินดีต้อนรับคุณผู้อนุมัติ (Approver)</h5>
                            <p class="mb-0 fs-7 text-muted">คุณมีหน้าที่สำคัญในการตรวจสอบและอนุมัติการจองห้องประชุม รวมถึงคำขอลงทะเบียนจากหน่วยงานภายนอก/ประชาชนทั่วไป</p>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card card-stat bg-white p-4 border-start border-warning border-5">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1 text-warning">7 <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                                        <span class="text-muted fs-7">คำขอจองห้องประชุม (รอการอนุมัติ)</span>
                                    </div>
                                    <div class="icon-box bg-yellow-light"><i class="fa-solid fa-user-clock"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat bg-white p-4 border-start border-success border-5">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1 text-success">15 <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                                        <span class="text-muted fs-7">อนุมัติเรียบร้อยแล้ว (วันนี้)</span>
                                    </div>
                                    <div class="icon-box bg-green-light"><i class="fa-solid fa-circle-check"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat bg-white p-4 border-start border-danger border-5">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1 text-danger">2 <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                                        <span class="text-muted fs-7">ปฏิเสธ / ไม่อนุมัติ (สัปดาห์นี้)</span>
                                    </div>
                                    <div class="icon-box bg-red-light" style="background: #fee2e2; color: #b91c1c;"><i class="fa-solid fa-ban"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Approval Panel -->
                    <div class="card main-card bg-white p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-indigo me-2"></i>คิวคำขอจองห้องประชุมด่วน (Quick Approval Queue)</h6>
                            <a href="approvals.php" class="btn btn-outline-primary btn-sm fw-semibold">ดูคิวทั้งหมด</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ผู้ขอจอง</th>
                                        <th>หัวข้อการประชุม</th>
                                        <th>ห้องประชุม</th>
                                        <th>วันที่และเวลา</th>
                                        <th>สถานะ</th>
                                        <th class="text-end">ดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">คุณมานพ หน่วยงานภายนอก</div>
                                            <span class="text-muted fs-7">หน่วยราชการภายนอก / ประชาชนทั่วไป</span>
                                        </td>
                                        <td class="fw-semibold">ประชุมความร่วมมือพัฒนาชุมชน</td>
                                        <td>ห้องประชุมใหญ่ (เวียง 1)</td>
                                        <td>28 มิ.ย. 2569 (09:00 - 12:00)</td>
                                        <td><span class="badge bg-warning text-dark px-3 py-2 rounded-pill">รออนุมัติ</span></td>
                                        <td class="text-end">
                                            <a href="approvals.php" class="btn btn-success btn-sm px-3 rounded-3 fw-semibold"><i class="fa-solid fa-check me-1"></i> อนุมัติ</a>
                                            <a href="approvals.php" class="btn btn-danger btn-sm px-3 rounded-3 fw-semibold" onclick="return confirm('คุณยืนยันที่จะปฏิเสธคำขอนี้ใช่หรือไม่?');"><i class="fa-solid fa-xmark me-1"></i> ปฏิเสธ</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">คุณวิภาดา กองช่าง</div>
                                            <span class="text-muted fs-7">กองช่าง (อบต.เวียง)</span>
                                        </td>
                                        <td class="fw-semibold">หารือแบบก่อสร้างถนนประจำตำบล</td>
                                        <td>ห้องประชุมสภา (เวียง 2)</td>
                                        <td>29 มิ.ย. 2569 (13:30 - 16:30)</td>
                                        <td><span class="badge bg-warning text-dark px-3 py-2 rounded-pill">รออนุมัติ</span></td>
                                        <td class="text-end">
                                            <a href="approvals.php" class="btn btn-success btn-sm px-3 rounded-3 fw-semibold"><i class="fa-solid fa-check me-1"></i> อนุมัติ</a>
                                            <a href="approvals.php" class="btn btn-danger btn-sm px-3 rounded-3 fw-semibold" onclick="return confirm('คุณยืนยันที่จะปฏิเสธคำขอนี้ใช่หรือไม่?');"><i class="fa-solid fa-xmark me-1"></i> ปฏิเสธ</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- 3. DASHBOARD VIEW: USER (พนักงาน/สมาชิกทั่วไป) -->
                <!-- ============================================== -->
                <?php if ($role === 'User'): ?>
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card card-stat bg-white p-4 border-start border-primary border-5">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1 text-primary">5 <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                                        <span class="text-muted fs-7">ประวัติการจองทั้งหมดของฉัน</span>
                                    </div>
                                    <div class="icon-box bg-indigo-light"><i class="fa-solid fa-calendar-user"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat bg-white p-4 border-start border-warning border-5">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1 text-warning">1 <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                                        <span class="text-muted fs-7">รอการพิจารณาจาก Approver</span>
                                    </div>
                                    <div class="icon-box bg-yellow-light"><i class="fa-solid fa-hourglass-half"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat bg-white p-4 border-start border-success border-5">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1 text-success">15 <span class="fs-6 text-muted fw-normal">ห้อง</span></h3>
                                        <span class="text-muted fs-7">ห้องประชุมว่างพร้อมจองวันนี้</span>
                                    </div>
                                    <div class="icon-box bg-green-light"><i class="fa-solid fa-door-open"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My Bookings History Table -->
                    <div class="card main-card bg-white p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-indigo me-2"></i>ประวัติการจองห้องประชุมของฉัน (My Booking History)</h6>
                            <?php if ($userStatus === 'inactive'): ?>
                                <button class="btn btn-sm btn-secondary fw-semibold" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถจองห้องประชุมได้ในขณะนี้');"><i class="fa-solid fa-lock me-1"></i> จองห้องใหม่ (ถูกระงับ)</button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-custom-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#bookingModal"><i class="fa-solid fa-plus me-1"></i> จองห้องใหม่</button>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>หัวข้อการประชุม</th>
                                        <th>ห้องประชุม</th>
                                        <th>วันที่และเวลา</th>
                                        <th>ผู้เข้าร่วม</th>
                                        <th>สถานะการจอง</th>
                                        <th class="text-end">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold">ประชุมทีมงานประจำเดือน</td>
                                        <td>ห้องประชุมสภา (เวียง 2)</td>
                                        <td>30 มิ.ย. 2569 (09:00 - 11:30)</td>
                                        <td>12 คน</td>
                                        <td><span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i> รออนุมัติ</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-outline-danger btn-sm px-3 rounded-3 fw-semibold" onclick="return confirm('คุณยืนยันที่จะยกเลิกการจองนี้ใช่หรือไม่?');"><i class="fa-solid fa-trash me-1"></i> ยกเลิก</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">หารืองานงบประมาณประจำปี</td>
                                        <td>ห้องประชุมเล็ก (เวียง 3)</td>
                                        <td>15 มิ.ย. 2569 (13:00 - 15:00)</td>
                                        <td>8 คน</td>
                                        <td><span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i> อนุมัติแล้ว</span></td>
                                        <td class="text-end">
                                            <a href="print_slip.php?id=1012&title=<?= urlencode('หารืองานงบประมาณประจำปี') ?>&date=<?= urlencode('15 มิ.ย. 2569') ?>&time=<?= urlencode('13:00 - 15:00') ?>&attendees=8" class="btn btn-outline-indigo btn-sm px-3 rounded-3 fw-semibold me-1"><i class="fa-solid fa-print me-1"></i> พิมพ์ใบจอง</a>
                                            <button class="btn btn-outline-secondary btn-sm px-3 rounded-3 fw-semibold" disabled>เสร็จสิ้น</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- 4. DASHBOARD VIEW: EXECUTIVE (ผู้บริหาร)         -->
                <!-- ============================================== -->
                <?php if ($role === 'Executive'): ?>
                    <div class="alert alert-info p-4 mb-4 d-flex align-items-center shadow-sm" style="border-radius: 20px; border: none; background: linear-gradient(135deg, #0dcaf0 0%, #0891b2 100%); color: white;">
                        <i class="fa-solid fa-user-tie fa-2x me-4 text-white"></i>
                        <div>
                            <h5 class="fw-bold mb-1">ยินดีต้อนรับคณะผู้บริหาร (Executive Decision Support Dashboard)</h5>
                            <p class="mb-0 fs-7 opacity-90">ระบบรายงานสถิติภาพรวมอัจฉริยะ สำหรับประกอบการประเมินประสิทธิภาพการใช้ทรัพยากรและตัดสินใจบริหารจัดการองค์กร</p>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-stat bg-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1">142 <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                                        <span class="text-success fw-bold fs-7"><i class="fa-solid fa-arrow-trend-up me-1"></i> +15%</span> <span class="text-muted fs-7">การจองเดือนนี้</span>
                                    </div>
                                    <div class="icon-box bg-indigo-light"><i class="fa-solid fa-handshake"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-stat bg-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1">82.5%</h3>
                                        <span class="text-success fw-bold fs-7">ระดับสูง</span> <span class="text-muted fs-7">ประสิทธิภาพการใช้ห้อง</span>
                                    </div>
                                    <div class="icon-box bg-green-light"><i class="fa-solid fa-chart-pie"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-stat bg-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1 text-indigo">Room A</h3>
                                        <span class="text-muted fs-7">ห้องประชุมยอดนิยม (68 ชม.)</span>
                                    </div>
                                    <div class="icon-box bg-yellow-light"><i class="fa-solid fa-star"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-stat bg-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bold mb-1">35,400 <span class="fs-6 text-muted fw-normal">บาท</span></h3>
                                        <span class="text-muted fs-7">งบประมาณจัดประชุมสะสม</span>
                                    </div>
                                    <div class="icon-box bg-purple-light"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts & Reporting Section for Executive -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-8">
                            <div class="card main-card bg-white p-4 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-chart-column text-indigo me-2"></i>รายงานสถิติการใช้งานแยกตามห้องประชุม (ประจำเดือน)</h6>
                                    <a href="reports.php" class="btn btn-outline-primary btn-sm fw-semibold"><i class="fa-solid fa-file-invoice-dollar me-1"></i> ดูรายงานทั้งหมด</a>
                                </div>
                                <div style="height: 280px;"><canvas id="bookingByRoomChart"></canvas></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card main-card bg-white p-4 h-100">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-donut text-indigo me-2"></i>สัดส่วนสถานะคำขออนุมัติภาพรวม</h6>
                                <div style="height: 250px; display:flex; justify-content:center;"><canvas id="statusChart"></canvas></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Summary Reports Table -->
                    <div class="card main-card bg-white p-4 mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fa-solid fa-file-lines text-indigo me-2"></i>รายงานข้อมูลเชิงลึกและการบริหารจัดการ (Recent Executive Insights)</h6>
                            <a href="reports.php" class="btn btn-custom-primary btn-sm fw-semibold"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> เปิดหน้ารายงาน & Export</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">ชื่อห้องประชุม</th>
                                        <th>จำนวนครั้งที่จอง</th>
                                        <th>รวมชั่วโมงใช้งาน</th>
                                        <th>ผู้เข้าร่วมรวม</th>
                                        <th>การประเมินประสิทธิภาพ (Occupancy Rate)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start fw-bold text-indigo">Room A - Grand Auditorium</td>
                                        <td>52 ครั้ง</td>
                                        <td>130 ชม.</td>
                                        <td>2,450 คน</td>
                                        <td><span class="badge bg-green-light px-3 py-2 fs-7">85% (ระดับการใช้พื้นที่สูงมาก)</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold text-indigo">Room B - Executive Boardroom</td>
                                        <td>38 ครั้ง</td>
                                        <td>85 ชม.</td>
                                        <td>570 คน</td>
                                        <td><span class="badge bg-green-light px-3 py-2 fs-7">78% (ระดับการใช้พื้นที่สูง)</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold text-indigo">Room C - Smart Setup Room</td>
                                        <td>29 ครั้ง</td>
                                        <td>64 ชม.</td>
                                        <td>410 คน</td>
                                        <td><span class="badge bg-green-light px-3 py-2 fs-7">65% (ระดับการใช้พื้นที่ปานกลาง)</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Executive Equipment Summary Report Table -->
                    <div class="card main-card bg-white p-4 mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fa-solid fa-boxes-stacked text-indigo me-2"></i>รายงานสถิติการใช้งานและสถานะอุปกรณ์ห้องประชุม (Equipment Utilization & Status)</h6>
                            <span class="badge bg-indigo-light text-indigo px-3 py-2 fs-7 fw-bold">อัปเดตสถานะแบบ Real-time</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">ชื่ออุปกรณ์ (Equipment Name)</th>
                                        <th>จำนวนทั้งหมด</th>
                                        <th>พร้อมใช้งาน</th>
                                        <th>ใช้งานไม่ได้ / ชำรุด</th>
                                        <th>ความถี่ในการใช้งาน</th>
                                        <th>ข้อเสนอแนะเชิงบริหาร (Executive Recommendation)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start fw-bold text-indigo"><i class="fa-solid fa-microphone-lines me-2 text-secondary"></i>ไมโครโฟนไร้สาย (Wireless Microphone)</td>
                                        <td class="fw-semibold">20 ตัว</td>
                                        <td><span class="badge bg-green-light text-success px-3 py-2 fs-7">18 ตัว</span></td>
                                        <td><span class="badge bg-red-light text-danger px-3 py-2 fs-7 fw-bold">2 ตัว</span></td>
                                        <td>112 ครั้ง / เดือน</td>
                                        <td class="text-muted fs-7">มีการใช้งานสูงมาก ควรพิจารณาตั้งงบจัดซื้อแบตเตอรี่สำรองและทดแทนตัวที่ชำรุด</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold text-indigo"><i class="fa-solid fa-video me-2 text-secondary"></i>กล้องประชุมทางไกล (4K PTZ Video Conference)</td>
                                        <td class="fw-semibold">4 ชุด</td>
                                        <td><span class="badge bg-green-light text-success px-3 py-2 fs-7">4 ชุด</span></td>
                                        <td><span class="badge bg-light text-secondary px-3 py-2 fs-7">0 ชุด</span></td>
                                        <td>45 ครั้ง / เดือน</td>
                                        <td class="text-muted fs-7">อุปกรณ์ทำงานสมบูรณ์ 100% เพียงพอต่อความต้องการในปัจจุบัน</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold text-indigo"><i class="fa-solid fa-print me-2 text-secondary"></i>โปรเจกเตอร์ความละเอียดสูง (HD Projector)</td>
                                        <td class="fw-semibold">6 เครื่อง</td>
                                        <td><span class="badge bg-green-light text-success px-3 py-2 fs-7">5 เครื่อง</span></td>
                                        <td><span class="badge bg-red-light text-danger px-3 py-2 fs-7 fw-bold">1 เครื่อง</span></td>
                                        <td>58 ครั้ง / เดือน</td>
                                        <td class="text-muted fs-7">เครื่องที่ใช้งานไม่ได้ส่งซ่อมระบบหลอดภาพ ควรแจ้งฝ่ายพัสดุติดตามสถานะ</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold text-indigo"><i class="fa-solid fa-chalkboard me-2 text-secondary"></i>สมาร์ทบอร์ดอัจฉริยะ (Interactive Whiteboard)</td>
                                        <td class="fw-semibold">2 เครื่อง</td>
                                        <td><span class="badge bg-green-light text-success px-3 py-2 fs-7">2 เครื่อง</span></td>
                                        <td><span class="badge bg-light text-secondary px-3 py-2 fs-7">0 เครื่อง</span></td>
                                        <td>35 ครั้ง / เดือน</td>
                                        <td class="text-muted fs-7">แนวโน้มการใช้งานเติบโต 30% ควรวางแผนขยายไปยังห้องประชุมย่อย</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- FullCalendar Section (แชร์ร่วมกันทุกสิทธิ์เพื่อดูคิวจอง) -->
                <div class="card main-card bg-white p-4 mb-5">
                    <h6 class="fw-bold mb-4"><i class="fa-solid fa-calendar-days text-indigo me-2"></i>ปฏิทินแสดงตารางการใช้ห้องประชุม (Live Calendar)</h6>
                    <div id="calendar"></div>
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
                                    <option value="<?= $room['id'] ?>">
                                        <?= htmlspecialchars($room['room_name']) ?> 
                                        (<?= htmlspecialchars($room['location']) ?> - รองรับ <?= $room['capacity'] ?> คน)
                                    </option>
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
                            <textarea class="form-control p-3" name="agenda" rows="3" placeholder="ระบุวาระหรือความต้องการพิเศษ เช่น ขอไมค์เพิ่ม 2 ตัว"></textarea>
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom App JS -->
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
