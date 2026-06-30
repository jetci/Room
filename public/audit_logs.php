<?php
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
use App\Models\Booking;
use App\Middleware\AuthMiddleware;

$currentUser = AuthMiddleware::requireAdmin();
$role = $currentUser['role_name'] ?? 'Admin';
$userStatus = $currentUser['status'] ?? 'active';
$avatarName = urlencode($currentUser['full_name'] ?? 'Admin');

$rooms = Booking::getAllRooms();

$currentLogo = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
$currentOrgName = $_SESSION['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Smart Room Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
                            <a class="nav-link" href="calendar.php"><i class="fa-solid fa-calendar-days me-3"></i> ปฏิทินการจอง</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <?php if ($userStatus === 'inactive'): ?>
                            <a class="nav-link text-muted" href="#" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ অন্ু ไม่สามารถใช้งานเมนูจองได้ในขณะนี้'); return false;"><i class="fa-solid fa-magnifying-glass me-3 text-secondary"></i> ค้นหาห้องว่าง <i class="fa-solid fa-lock ms-2 text-warning"></i></a>
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
                            <a class="nav-link active" href="audit_logs.php"><i class="fa-solid fa-shield-halved me-3"></i> Audit Logs</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Main Workspace -->
            <div class="col-lg-10 p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">บันทึกความปลอดภัยและการทำงาน (Audit Logs)</h4>
                        <p class="text-muted mb-0">ตรวจสอบกิจกรรมการใช้งานระบบ การจอง การอนุมัติ และบันทึก IP Address</p>
                    </div>
                    <button class="btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold">
                        <i class="fa-solid fa-rotate me-2"></i>รีเฟรชข้อมูล
                    </button>
                </div>

                <!-- Audit Logs Table Card -->
                <div class="card main-card bg-white p-4 mb-5">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="py-3">วัน-เวลา (Log Time)</th>
                                    <th scope="col" class="py-3">ผู้ดำเนินการ (User ID)</th>
                                    <th scope="col" class="py-3">โมดูล (Module)</th>
                                    <th scope="col" class="py-3">กิจกรรม (Action)</th>
                                    <th scope="col" class="py-3">เลขอ้างอิง (Ref ID)</th>
                                    <th scope="col" class="py-3">IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold text-muted"><?= date('d/m/') . (date('Y') + 543) . ' ' . date('H:i:s') ?></td>
                                    <td class="fw-bold text-indigo">User #1 (Somchai Admin)</td>
                                    <td><span class="badge bg-indigo-light px-3 py-2 fs-7">Booking</span></td>
                                    <td><span class="badge bg-green-light px-3 py-2 fs-7">create (สร้างการจอง)</span></td>
                                    <td class="fw-bold text-dark">Booking #1</td>
                                    <td class="text-muted"><i class="fa-solid fa-desktop me-2 text-secondary"></i> 127.0.0.1</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted"><?= date('d/m/', strtotime('-15 minutes')) . (date('Y', strtotime('-15 minutes')) + 543) . ' ' . date('H:i:s', strtotime('-15 minutes')) ?></td>
                                    <td class="fw-bold text-indigo">User #3 (Jaidee User)</td>
                                    <td><span class="badge bg-indigo-light px-3 py-2 fs-7">Booking</span></td>
                                    <td><span class="badge bg-green-light px-3 py-2 fs-7">create (สร้างการจอง)</span></td>
                                    <td class="fw-bold text-dark">Booking #2</td>
                                    <td class="text-muted"><i class="fa-solid fa-desktop me-2 text-secondary"></i> 127.0.0.1</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted"><?= date('d/m/', strtotime('-1 hour')) . (date('Y', strtotime('-1 hour')) + 543) . ' ' . date('H:i:s', strtotime('-1 hour')) ?></td>
                                    <td class="fw-bold text-indigo">User #2 (Somsri Approver)</td>
                                    <td><span class="badge bg-yellow-light px-3 py-2 fs-7">Approval</span></td>
                                    <td><span class="badge bg-green-light px-3 py-2 fs-7">approve (อนุมัติ)</span></td>
                                    <td class="fw-bold text-dark">Booking #3</td>
                                    <td class="text-muted"><i class="fa-solid fa-desktop me-2 text-secondary"></i> 127.0.0.1</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
