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
$userStatus = $currentUser['status'] ?? 'active';

// ตรวจสอบสิทธิ์การเข้าถึง (ต้องเป็น Admin เท่านั้น)
if ($role !== 'Admin') {
    $_SESSION['error_message'] = "ปฏิเสธการเข้าถึง: เมนูนี้สงวนสิทธิ์เฉพาะผู้ดูแลระบบส่วนกลาง (Admin) เท่านั้น";
    header("Location: login.php");
    exit;
}

// จัดการการบันทึกฟอร์มประกาศส่วนกลาง
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_token']) || $_POST['_token'] !== csrf_token()) {
        $_SESSION['error_msg'] = "คำขอไม่ถูกต้อง (CSRF Token Mismatch)";
        header("Location: admin_announcements.php");
        exit;
    }

    $_SESSION['announcement'] = [
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'level' => $_POST['level'] ?? 'warning',
        'title' => $_POST['title'] ?? '📢 ประกาศส่วนกลาง: งานซ่อมบำรุงระบบไฟฟ้าและระบบปรับอากาศ',
        'message' => $_POST['message'] ?? 'ในวันเสาร์ที่ 4 กรกฎาคม 2569 ห้องประชุมสภาใหญ่ (Room A) และห้องประชุมเล็ก (Room B) จะงดให้บริการชั่วคราวเพื่อซ่อมบำรุงประจำปี ขออภัยในความไม่สะดวกมา ณ ที่นี้',
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $_SESSION['success_msg'] = "บันทึกและอัปเดตข้อมูลป้ายประกาศส่วนกลาง (System Announcement Banner) บนหน้า Dashboard เรียบร้อยแล้ว";
    header("Location: admin_announcements.php");
    exit;
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// ดึงค่าประกาศปัจจุบัน
$announcement = $_SESSION['announcement'] ?? [
    'is_active' => 1,
    'level' => 'warning',
    'title' => '📢 ประกาศส่วนกลาง: งานซ่อมบำรุงระบบไฟฟ้าและระบบปรับอากาศ',
    'message' => 'ในวันเสาร์ที่ 4 กรกฎาคม 2569 ห้องประชุมสภาใหญ่ (Room A) และห้องประชุมเล็ก (Room B) จะงดให้บริการชั่วคราวเพื่อซ่อมบำรุงประจำปี ขออภัยในความไม่สะดวกมา ณ ที่นี้',
    'updated_at' => date('Y-m-d H:i:s')
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการประกาศส่วนกลาง (System Announcements) - อบต.เวียง</title>
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
        .card-preview { border-radius: 20px; border: 1px solid #cbd5e1; background: #ffffff; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom py-3 sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="fa-solid fa-building-flag fa-2x me-3 text-indigo"></i>
                <div>
                    <span class="fw-bold fs-5 text-indigo">ระบบจองห้องประชุมออนไลน์</span><br>
                    <span class="fs-7 text-secondary">องค์การบริหารส่วนตำบลเวียง (Smart Room Booking)</span>
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
                    <li class="nav-item"><a class="nav-link" href="calendar.php"><i class="fa-solid fa-calendar-view-month me-3"></i> ปฏิทินการจอง</a></li>
                    <li class="nav-item"><a class="nav-link" href="approvals.php"><i class="fa-solid fa-inbox me-3"></i> คิวรออนุมัติ</a></li>
                    <li class="nav-item"><a class="nav-link" href="register_ext.php"><i class="fa-solid fa-user-plus me-3"></i> คำขอลงทะเบียน</a></li>
                </ul>

                <div class="text-muted fs-8 fw-bold text-uppercase mb-3 px-3">สำหรับแอดมิน (Admin)</div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="admin_rooms.php"><i class="fa-solid fa-door-open me-3"></i> จัดการห้องประชุม</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_equipments.php"><i class="fa-solid fa-couch me-3"></i> จัดการอุปกรณ์</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & Export</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_users.php"><i class="fa-solid fa-users-gear me-3"></i> จัดการผู้ใช้</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_announcements.php"><i class="fa-solid fa-bullhorn me-3"></i> ประกาศส่วนกลาง</a></li>
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

                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-bullhorn me-2 text-indigo"></i> จัดการระบบป้ายประกาศข่าวสารส่วนกลาง (Global Announcement Banner)</h4>
                        <p class="text-muted mb-0">บริหารจัดการป้ายแจ้งเตือนส่วนกลางที่จะปรากฏบนส่วนบนสุดของหน้าแดชบอร์ดหลักสำหรับพนักงานและผู้ใช้ทุกบทบาท</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-indigo px-4 py-3 rounded-3 fw-semibold"><i class="fa-solid fa-chart-pie me-2"></i> ดูหน้าแดชบอร์ดหลัก</a>
                </div>

                <div class="row g-5">
                    
                    <!-- FORM COLUMN -->
                    <div class="col-lg-7">
                        <div class="card card-preview p-5">
                            <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> ตั้งค่าและแก้ไขข้อความป้ายประกาศ</h5>
                            
                            <form action="admin_announcements.php" method="POST">
                                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                                
                                <!-- สถานะป้ายประกาศ -->
                                <div class="mb-4 p-4 rounded-4 border bg-light d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-check-label fw-bold text-dark fs-6" for="is_active">สถานะการแสดงผลป้ายประกาศ</label>
                                        <p class="text-muted fs-7 mb-0">หากปิดสวิตช์ ป้ายประกาศจะถูกซ่อนจากหน้าแดชบอร์ดของทุกคนทันที</p>
                                    </div>
                                    <div class="form-check form-switch fs-3 mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" <?= $announcement['is_active'] ? 'checked' : '' ?>>
                                    </div>
                                </div>

                                <!-- ระดับความสำคัญ (สีป้าย) -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark">ระดับความสำคัญ / ประเภทข่าวสาร <span class="text-danger">*</span></label>
                                    <select class="form-select p-3 rounded-3 bg-white" name="level" required>
                                        <option value="warning" <?= $announcement['level'] === 'warning' ? 'selected' : '' ?>>🟡 ป้ายเตือนสีส้ม/เหลือง (งานซ่อมบำรุง, ประกาศทั่วไป)</option>
                                        <option value="danger" <?= $announcement['level'] === 'danger' ? 'selected' : '' ?>>🔴 ป้ายฉุกเฉินสีแดง (ปิดอาคาร, ระบบมีปัญหา, เหตุเร่งด่วน)</option>
                                        <option value="info" <?= $announcement['level'] === 'info' ? 'selected' : '' ?>>🔵 ป้ายแจ้งข่าวสารสีฟ้า (กิจกรรม, ต้อนรับผู้เยี่ยมชม)</option>
                                        <option value="success" <?= $announcement['level'] === 'success' ? 'selected' : '' ?>>🟢 ป้ายชื่นชมสีเขียว (สรุปงานสำเร็จ, กิจกรรมผ่านพ้น)</option>
                                    </select>
                                </div>

                                <!-- หัวข้อประกาศ -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark">หัวข้อประกาศ (Title) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control p-3 rounded-3 bg-white" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" required placeholder="ตัวอย่าง: 📢 ประกาศส่วนกลาง: งานซ่อมบำรุงระบบไฟฟ้า">
                                </div>

                                <!-- เนื้อหาประกาศ -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark">รายละเอียดข้อความ (Message Description) <span class="text-danger">*</span></label>
                                    <textarea class="form-control p-3 rounded-3 bg-white" name="message" rows="4" required placeholder="ระบุรายละเอียด วันที่ และข้อมูลที่จำเป็น"><?= htmlspecialchars($announcement['message']) ?></textarea>
                                </div>

                                <div class="d-flex justify-content-end pt-3 border-top mt-4">
                                    <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-bold fs-6 shadow-sm"><i class="fa-solid fa-floppy-disk me-2"></i> บันทึกและอัปเดตป้ายประกาศ</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- PREVIEW COLUMN -->
                    <div class="col-lg-5">
                        <div class="card card-preview p-5 bg-slate-50">
                            <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-eye me-2 text-indigo"></i> ตัวอย่างการแสดงผลบน Dashboard</h5>
                            <p class="text-muted fs-7 mb-4">ด้านล่างคือภาพจำลองของป้ายประกาศที่จะปรากฏให้ผู้ใช้ทุกคนเห็นบนหน้า Dashboard หลัก</p>
                            
                            <?php if ($announcement['is_active']): ?>
                                <?php
                                    $bgStyle = 'border: 1px solid #fed7aa; background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: #9a3412;';
                                    $iconColor = 'text-warning';
                                    if ($announcement['level'] === 'danger') {
                                        $bgStyle = 'border: 1px solid #fecaca; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); color: #991b1b;';
                                        $iconColor = 'text-danger';
                                    } elseif ($announcement['level'] === 'info') {
                                        $bgStyle = 'border: 1px solid #bfdbfe; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1e40af;';
                                        $iconColor = 'text-primary';
                                    } elseif ($announcement['level'] === 'success') {
                                        $bgStyle = 'border: 1px solid #bbf7d0; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: #166534;';
                                        $iconColor = 'text-success';
                                    }
                                ?>
                                <div class="alert d-flex align-items-center p-4 mb-0 shadow-sm" style="border-radius: 20px; <?= $bgStyle ?>" role="alert">
                                    <i class="fa-solid fa-bullhorn fa-2x me-4 <?= $iconColor ?>"></i>
                                    <div>
                                        <strong class="fw-bold fs-6"><?= htmlspecialchars($announcement['title']) ?></strong><br>
                                        <span class="fs-7"><?= htmlspecialchars($announcement['message']) ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary p-4 text-center rounded-4 mb-0">
                                    <i class="fa-solid fa-eye-slash fa-3x text-muted mb-3 d-block"></i>
                                    <h6 class="fw-bold text-secondary mb-1">ป้ายประกาศส่วนกลางปิดการแสดงผลอยู่ (Inactive)</h6>
                                    <p class="text-muted fs-7 mb-0">ผู้ใช้งานจะไม่เห็นป้ายประกาศใดๆ บนหน้า Dashboard ในขณะนี้</p>
                                </div>
                            <?php endif; ?>

                            <div class="mt-5 pt-4 border-top">
                                <h6 class="fw-bold text-muted fs-7 mb-3 text-uppercase">ประวัติการอัปเดตล่าสุด</h6>
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-clock-rotate-left fa-2x text-secondary me-3"></i>
                                    <div>
                                        <span class="fw-bold text-dark fs-7">อัปเดตโดย: <?= htmlspecialchars($currentUser['full_name']) ?> (Admin)</span><br>
                                        <span class="text-muted fs-8">เวลา: <?= htmlspecialchars($announcement['updated_at']) ?></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
