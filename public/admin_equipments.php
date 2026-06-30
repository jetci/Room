<?php
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
use App\Models\Booking;
use App\Middleware\AuthMiddleware;

$currentUser = AuthMiddleware::requireAdmin();
$role = $currentUser['role_name'] ?? 'Admin';
$userStatus = $currentUser['status'] ?? 'active';
$avatarName = urlencode($currentUser['full_name'] ?? 'Admin');

$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    
    if ($action === 'create' && !empty($_POST['feature_name'])) {
        $totalQty = (int)($_POST['total_qty'] ?? 1);
        $activeQty = (int)($_POST['active_qty'] ?? 1);
        $maintenanceQty = max(0, $totalQty - $activeQty);
        Booking::createFeature($_POST['feature_name'], $totalQty, $activeQty, $maintenanceQty);
        $successMsg = 'เพิ่มอุปกรณ์ใหม่เข้าสู่ฐานข้อมูลสำเร็จ!';
    } elseif ($action === 'edit' && !empty($_POST['feature_id']) && !empty($_POST['feature_name'])) {
        $id = (int)$_POST['feature_id'];
        $totalQty = (int)($_POST['total_qty'] ?? 1);
        $activeQty = (int)($_POST['active_qty'] ?? 1);
        $maintenanceQty = max(0, $totalQty - $activeQty);
        Booking::updateFeature($id, $_POST['feature_name'], $totalQty, $activeQty, $maintenanceQty);
        $successMsg = 'แก้ไขข้อมูลอุปกรณ์สำเร็จ!';
    } elseif ($action === 'delete' && !empty($_POST['feature_id'])) {
        $id = (int)$_POST['feature_id'];
        Booking::deleteFeature($id);
        $successMsg = 'ลบข้อมูลอุปกรณ์ออกจากฐานข้อมูลสำเร็จ!';
    }
}

$features = Booking::getAllFeatures();

$currentLogo = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
$currentOrgName = $_SESSION['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการอุปกรณ์ - Smart Room Booking</title>
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
                            <a class="nav-link active" href="admin_equipments.php"><i class="fa-solid fa-couch me-3"></i> จัดการอุปกรณ์</a>
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
                
                <?php if ($successMsg): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center p-4 mb-4" style="border-radius: 16px; border: none; background-color: #dcfce7; color: #15803d;" role="alert">
                        <i class="fa-solid fa-circle-check fs-3 me-3"></i>
                        <div><strong class="fw-bold">สำเร็จ!</strong> <?= htmlspecialchars($successMsg) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">จัดการอุปกรณ์ & สิ่งอำนวยความสะดวก (Equipments)</h4>
                        <p class="text-muted mb-0">รายการสิ่งอำนวยความสะดวกและอุปกรณ์อิเล็กทรอนิกส์ในห้องประชุม</p>
                    </div>
                    <button class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#newEquipmentModal">
                        <i class="fa-solid fa-plus me-2"></i>เพิ่มอุปกรณ์ใหม่
                    </button>
                </div>

                <!-- Equipment Table Card -->
                <div class="card main-card bg-white p-4 mb-5">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="py-3">ID</th>
                                    <th scope="col" class="py-3">ชื่ออุปกรณ์ / สิ่งอำนวยความสะดวก</th>
                                    <th scope="col" class="py-3 text-center">จำนวนทั้งหมด (Total)</th>
                                    <th scope="col" class="py-3 text-center">พร้อมใช้งาน (Active)</th>
                                    <th scope="col" class="py-3 text-center">ไม่พร้อมใช้งาน (Maintenance)</th>
                                    <th scope="col" class="py-3 text-center">สถานะ</th>
                                    <th scope="col" class="py-3 text-end">จัดการ (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($features as $feature): ?>
                                    <?php 
                                        $tot = (int)($feature['total_qty'] ?? 1);
                                        $act = (int)($feature['active_qty'] ?? 1);
                                        $maint = (int)($feature['maintenance_qty'] ?? 0);
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?= $feature['id'] ?></td>
                                        <td class="fw-bold text-indigo"><i class="fa-solid fa-microchip me-2 text-primary"></i> <?= htmlspecialchars($feature['feature_name']) ?></td>
                                        <td class="text-center fw-bold fs-6"><?= $tot ?></td>
                                        <td class="text-center fw-bold text-success fs-6"><?= $act ?></td>
                                        <td class="text-center fw-bold text-danger fs-6"><?= $maint ?></td>
                                        <td class="text-center">
                                            <?php if ($act > 0): ?>
                                                <span class="badge bg-green-light px-3 py-2 fs-7">พร้อมใช้งาน (Active)</span>
                                            <?php else: ?>
                                                <span class="badge bg-red-light px-3 py-2 fs-7 text-danger">ส่งซ่อม / ปิดใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-light btn-sm px-3 py-2 rounded-3 me-1" onclick="openEditModal(<?= $feature['id'] ?>, '<?= htmlspecialchars(addslashes($feature['feature_name'])) ?>', <?= $tot ?>, <?= $act ?>)"><i class="fa-solid fa-pen-to-square text-primary"></i> แก้ไข</button>
                                            <form action="admin_equipments.php" method="POST" class="d-inline" onsubmit="return confirm('คุณต้องการลบอุปกรณ์นี้ใช่หรือไม่?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="feature_id" value="<?= $feature['id'] ?>">
                                                <button type="submit" class="btn btn-light btn-sm px-3 py-2 rounded-3"><i class="fa-solid fa-trash-can text-danger"></i> ลบ</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- 🏷️ MODAL: เพิ่มอุปกรณ์ใหม่ -->
    <div class="modal fade" id="newEquipmentModal" tabindex="-1" aria-labelledby="newEquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-indigo-light p-4" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <h5 class="modal-title fw-bold" id="newEquipmentModalLabel"><i class="fa-solid fa-plus me-2"></i> เพิ่มอุปกรณ์ใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="admin_equipments.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="create">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">ชื่ออุปกรณ์ / สิ่งอำนวยความสะดวก <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" name="feature_name" placeholder="ตัวอย่าง: กล้อง AI Tracking / ลำโพง Bluetooth" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">จำนวนทั้งหมด <span class="text-danger">*</span></label>
                            <input type="number" class="form-control p-3" id="total_qty" name="total_qty" value="1" min="1" oninput="calculateMaintenance()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">พร้อมใช้งาน <span class="text-success">*</span></label>
                            <input type="number" class="form-control p-3" id="active_qty" name="active_qty" value="1" min="0" oninput="calculateMaintenance()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">ไม่พร้อมใช้งาน <span class="text-danger">*</span></label>
                            <input type="number" class="form-control p-3 bg-light text-danger fw-bold" id="maintenance_qty" name="maintenance_qty" value="0" readonly>
                        </div>
                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light px-4 py-3 me-2 rounded-3 fw-semibold" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3"><i class="fa-solid fa-floppy-disk me-2"></i> บันทึกข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 🏷️ MODAL: แก้ไขอุปกรณ์ -->
    <div class="modal fade" id="editEquipmentModal" tabindex="-1" aria-labelledby="editEquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-indigo-light p-4" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <h5 class="modal-title fw-bold" id="editEquipmentModalLabel"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> แก้ไขข้อมูลอุปกรณ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="admin_equipments.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="feature_id" id="edit_feature_id">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">ชื่ออุปกรณ์ / สิ่งอำนวยความสะดวก <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" id="edit_feature_name" name="feature_name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">จำนวนทั้งหมด <span class="text-danger">*</span></label>
                            <input type="number" class="form-control p-3" id="edit_total_qty" name="total_qty" min="1" oninput="calculateEditMaintenance()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">พร้อมใช้งาน <span class="text-success">*</span></label>
                            <input type="number" class="form-control p-3" id="edit_active_qty" name="active_qty" min="0" oninput="calculateEditMaintenance()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">ไม่พร้อมใช้งาน <span class="text-danger">*</span></label>
                            <input type="number" class="form-control p-3 bg-light text-danger fw-bold" id="edit_maintenance_qty" name="maintenance_qty" readonly>
                        </div>
                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light px-4 py-3 me-2 rounded-3 fw-semibold" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3"><i class="fa-solid fa-floppy-disk me-2"></i> บันทึกการเปลี่ยนแปลง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateMaintenance() {
            let total = parseInt(document.getElementById('total_qty').value) || 0;
            let active = parseInt(document.getElementById('active_qty').value) || 0;
            
            if (active > total) {
                active = total;
                document.getElementById('active_qty').value = active;
            }
            
            let maintenance = total - active;
            document.getElementById('maintenance_qty').value = maintenance < 0 ? 0 : maintenance;
        }

        function calculateEditMaintenance() {
            let total = parseInt(document.getElementById('edit_total_qty').value) || 0;
            let active = parseInt(document.getElementById('edit_active_qty').value) || 0;
            
            if (active > total) {
                active = total;
                document.getElementById('edit_active_qty').value = active;
            }
            
            let maintenance = total - active;
            document.getElementById('edit_maintenance_qty').value = maintenance < 0 ? 0 : maintenance;
        }

        function openEditModal(id, name, total, active) {
            document.getElementById('edit_feature_id').value = id;
            document.getElementById('edit_feature_name').value = name;
            document.getElementById('edit_total_qty').value = total;
            document.getElementById('edit_active_qty').value = active;
            calculateEditMaintenance();
            
            let modal = new bootstrap.Modal(document.getElementById('editEquipmentModal'));
            modal.show();
        }
    </script>
</body>
</html>
