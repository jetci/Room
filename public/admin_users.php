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

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    
    if ($action === 'create' && !empty($_POST['first_name']) && !empty($_POST['email'])) {
        $pass = $_POST['password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        
        if ($pass !== $confirmPass) {
            $errorMsg = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง';
        } else {
            Booking::createUser($_POST);
            $successMsg = 'สร้างบัญชีผู้ใช้ใหม่สำเร็จเรียบร้อย!';
        }
    } elseif ($action === 'edit' && !empty($_POST['user_id']) && !empty($_POST['first_name'])) {
        Booking::updateUser($_POST);
        $successMsg = 'อัปเดตข้อมูลผู้ใช้สำเร็จ!';
    } elseif ($action === 'toggle_status' && !empty($_POST['user_id'])) {
        $id = (int)$_POST['user_id'];
        $newStatus = $_POST['new_status'] ?? 'inactive';
        Booking::toggleUserStatus($id, $newStatus);
        $successMsg = 'อัปเดตสถานะบัญชีผู้ใช้สำเร็จ!';
    } elseif ($action === 'delete' && !empty($_POST['user_id'])) {
        $id = (int)$_POST['user_id'];
        Booking::deleteUser($id);
        $successMsg = 'ลบบัญชีผู้ใช้ออกจากระบบสำเร็จ!';
    }
}

$users = Booking::getAllUsers();
$departments = Booking::getAllDepartments();
$roles = Booking::getAllRoles();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Smart Room Booking (อบต.เวียง)</title>
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
                <i class="fa-solid fa-building-flag me-2 fs-3 text-indigo"></i> 
                <span class="fw-bold">SMART ROOM BOOKING (อบต.เวียง)</span>
            </a>
            <div class="d-flex align-items-center">
                <a href="register.php" class="btn btn-light rounded-3 px-3 py-2 me-3 fw-semibold text-indigo border-0" style="background: #e0e7ff;">
                    <i class="fa-solid fa-user-plus me-1"></i> สมัครสมาชิก (หน้าลงทะเบียน)
                </a>
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
                            <a class="nav-link active" href="admin_users.php"><i class="fa-solid fa-users-gear me-3"></i> จัดการผู้ใช้</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="audit_logs.php"><i class="fa-solid fa-shield-halved me-3"></i> Audit Logs</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Main Workspace -->
            <div class="col-lg-10 p-4">
                
                <?php if (!empty($successMsg)): ?>
                    <div class="alert alert-success alert-dismissible fade show p-4 mb-4 rounded-4 d-flex align-items-center shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check fs-3 me-3 text-success"></i>
                        <div class="fw-semibold fs-5"><?= $successMsg ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show p-4 mb-4 rounded-4 d-flex align-items-center shadow-sm" role="alert">
                        <i class="fa-solid fa-triangle-exclamation fs-3 me-3 text-danger"></i>
                        <div class="fw-semibold fs-5"><?= $errorMsg ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">จัดการบัญชีผู้ใช้งาน (User Management)</h4>
                        <p class="text-muted mb-0">โครงสร้างหน่วยงานภายใน **อบต.เวียง** และหน่วยราชการภายนอก / กำหนดสิทธิ์ Admin, Approver, User</p>
                    </div>
                    <button class="btn btn-custom-primary px-4 py-3 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#newUserModal">
                        <i class="fa-solid fa-user-plus me-2"></i> เพิ่มผู้ใช้ใหม่ (อบต.เวียง)
                    </button>
                </div>

                <!-- Users Table Card -->
                <div class="card main-card bg-white p-4 mb-5 shadow-sm" style="border-radius: 24px;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="py-3 px-3">โปรไฟล์</th>
                                    <th scope="col" class="py-3">ชื่อ-นามสกุล (Full Name)</th>
                                    <th scope="col" class="py-3">หน่วยงาน / สังกัด (Department)</th>
                                    <th scope="col" class="py-3">บทบาท (Role)</th>
                                    <th scope="col" class="py-3">สถานะ (Status)</th>
                                    <th scope="col" class="py-3 text-end px-3">จัดการ (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <?php
                                        $nameEncoded = urlencode($u['full_name']);
                                        $statusClass = $u['status'] === 'active' ? 'bg-green-light text-success' : ($u['status'] === 'inactive' ? 'bg-yellow-light text-warning' : 'bg-red-light text-danger');
                                        $statusLabel = $u['status'] === 'active' ? 'Active (ปกติ)' : ($u['status'] === 'inactive' ? 'Inactive (รออนุมัติ/ระงับ)' : 'Suspended');
                                        $roleBadge = $u['role_id'] == 1 ? 'bg-indigo-light text-indigo' : ($u['role_id'] == 2 ? 'bg-green-light text-success' : ($u['role_id'] == 4 ? 'bg-info-light text-primary' : 'bg-yellow-light text-warning'));
                                    ?>
                                    <tr>
                                        <td class="px-3">
                                            <img src="https://ui-avatars.com/api/?name=<?= $nameEncoded ?>&background=random&color=fff" class="rounded-circle shadow-sm" width="48" height="48">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-indigo fs-6"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <span class="text-muted fs-7"><i class="fa-regular fa-envelope me-1"></i> <?= htmlspecialchars($u['email']) ?></span>
                                            <?php if (!empty($u['phone'])): ?>
                                                <span class="text-muted fs-7 ms-2"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($u['phone']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($u['department_name'] ?? 'ไม่มีสังกัด') ?></td>
                                        <td>
                                            <span class="badge <?= $roleBadge ?> px-3 py-2 fs-7 fw-bold"><?= htmlspecialchars($u['role_name'] ?? 'User') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $statusClass ?> px-3 py-2 fs-7 fw-bold"><?= $statusLabel ?></span>
                                        </td>
                                        <td class="text-end px-3">
                                            <button class="btn btn-light btn-sm px-3 py-2 rounded-3 me-1 fw-semibold shadow-sm" onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= htmlspecialchars(addslashes($u['phone'] ?? '')) ?>', <?= $u['department_id'] ?>, <?= $u['role_id'] ?>, '<?= $u['status'] ?>')">
                                                <i class="fa-solid fa-pen-to-square text-primary me-1"></i> แก้ไข
                                            </button>
                                            
                                            <form action="admin_users.php" method="POST" class="d-inline" onsubmit="return confirm('คุณต้องการเปลี่ยนสถานะผู้ใช้รายนี้ใช่หรือไม่?');">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                <button type="submit" class="btn btn-light btn-sm px-3 py-2 rounded-3 me-1 fw-semibold shadow-sm">
                                                    <?php if ($u['status'] === 'active'): ?>
                                                        <i class="fa-solid fa-lock text-warning me-1"></i> ระงับ
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-unlock text-success me-1"></i> ปลดล็อก
                                                    <?php endif; ?>
                                                </button>
                                            </form>

                                            <form action="admin_users.php" method="POST" class="d-inline" onsubmit="return confirm('คำเตือน: คุณต้องการลบบัญชีผู้ใช้งานนี้ออกจากระบบถาวรใช่หรือไม่?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-light btn-sm px-3 py-2 rounded-3 fw-semibold shadow-sm">
                                                    <i class="fa-solid fa-trash-can text-danger me-1"></i> ลบ
                                                </button>
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

    <!-- 🏷️ MODAL: เพิ่มผู้ใช้ใหม่ (อบต.เวียง) -->
    <div class="modal fade" id="newUserModal" tabindex="-1" aria-labelledby="newUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg" style="border-radius: 24px; border: none;">
                <div class="modal-header bg-indigo-light p-4" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <h5 class="modal-title fw-bold text-indigo" id="newUserModalLabel"><i class="fa-solid fa-user-plus me-2"></i> เพิ่มบัญชีผู้ใช้งานใหม่ (อบต.เวียง & ภายนอก)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="admin_users.php" method="POST" class="row g-3" onsubmit="return checkAdminPasswordMatch();">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ชื่อ (First Name) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" name="first_name" placeholder="ตัวอย่าง: สมชาย" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">นามสกุล (Last Name) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" name="last_name" placeholder="ตัวอย่าง: บริหารดี" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">อีเมล (Email) <span class="text-danger">*</span></label>
                            <input type="email" class="form-control p-3" name="email" placeholder="ตัวอย่าง: somchai@wiang.go.th" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">เบอร์โทรศัพท์ (Phone)</label>
                            <input type="text" class="form-control p-3" name="phone" placeholder="ตัวอย่าง: 0812345678">
                        </div>

                        <!-- 🔑 รหัสผ่าน & ยืนยันรหัสผ่าน -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">ตั้งรหัสผ่าน (Password) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3 bg-light text-indigo fw-bold" id="admin_reg_password" name="password" value="123456" required>
                            <div class="form-text text-muted fs-7">ระบบจะตั้งรหัสผ่านเริ่มต้นเป็น '123456'</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">ยืนยันรหัสผ่าน (Confirm Password) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3 bg-light text-indigo fw-bold" id="admin_reg_confirm_password" name="confirm_password" value="123456" required>
                            <div class="form-text text-muted fs-7">ยืนยันรหัสผ่านอีกครั้งเพื่อป้องกันการพิมพ์ผิดพลาด</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">หน่วยงาน / สังกัด (Department) <span class="text-danger">*</span></label>
                            <select class="form-select p-3" name="department_id" required>
                                <option value="" selected disabled>-- กรุณาเลือกสังกัด / แผนก --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">บทบาท (Role) <span class="text-danger">*</span></label>
                            <select class="form-select p-3" name="role_id" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?> <?= $r['id'] == 1 ? '(ผู้ดูแลระบบ)' : ($r['id'] == 2 ? '(ผู้อนุมัติ)' : ($r['id'] == 4 ? '(ผู้บริหาร)' : '(พนักงาน/สมาชิกทั่วไป)')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">สถานะบัญชี (Status) <span class="text-danger">*</span></label>
                            <select class="form-select p-3" name="status" required>
                                <option value="active">Active (ใช้งานปกติ)</option>
                                <option value="inactive">Inactive (ระงับชั่วคราว / รออนุมัติ)</option>
                            </select>
                        </div>

                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light px-4 py-3 me-2 rounded-3 fw-semibold" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-semibold"><i class="fa-solid fa-user-check me-2"></i> บันทึกข้อมูลผู้ใช้</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 🏷️ MODAL: แก้ไขผู้ใช้ -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg" style="border-radius: 24px; border: none;">
                <div class="modal-header bg-indigo-light p-4" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <h5 class="modal-title fw-bold text-indigo" id="editUserModalLabel"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> แก้ไขข้อมูลบัญชีผู้ใช้งาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="admin_users.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ชื่อ (First Name) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">นามสกุล (Last Name) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" id="edit_last_name" name="last_name" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">อีเมล (Email) <span class="text-danger">*</span></label>
                            <input type="email" class="form-control p-3" id="edit_email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">เบอร์โทรศัพท์ (Phone)</label>
                            <input type="text" class="form-control p-3" id="edit_phone" name="phone">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">หน่วยงาน / สังกัด (Department) <span class="text-danger">*</span></label>
                            <select class="form-select p-3" id="edit_department_id" name="department_id" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">บทบาท (Role) <span class="text-danger">*</span></label>
                            <select class="form-select p-3" id="edit_role_id" name="role_id" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?> <?= $r['id'] == 1 ? '(ผู้ดูแลระบบ)' : ($r['id'] == 2 ? '(ผู้อนุมัติ)' : ($r['id'] == 4 ? '(ผู้บริหาร)' : '(พนักงาน/สมาชิกทั่วไป)')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">สถานะบัญชี (Status) <span class="text-danger">*</span></label>
                            <select class="form-select p-3" id="edit_status" name="status" required>
                                <option value="active">Active (ใช้งานปกติ)</option>
                                <option value="inactive">Inactive (ระงับชั่วคราว / รออนุมัติ)</option>
                                <option value="suspended">Suspended (ระงับถาวร)</option>
                            </select>
                        </div>

                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light px-4 py-3 me-2 rounded-3 fw-semibold" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-semibold"><i class="fa-solid fa-floppy-disk me-2"></i> บันทึกการเปลี่ยนแปลง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkAdminPasswordMatch() {
            let pass = document.getElementById('admin_reg_password').value;
            let confirm = document.getElementById('admin_reg_confirm_password').value;
            if (pass !== confirm) {
                alert('⚠️ รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง!');
                document.getElementById('admin_reg_confirm_password').focus();
                return false;
            }
            return true;
        }

        function openEditUserModal(id, fullName, email, phone, deptId, roleId, status) {
            document.getElementById('edit_user_id').value = id;
            
            // แยกชื่อและนามสกุลออกจากกัน
            let parts = fullName.split(' ');
            document.getElementById('edit_first_name').value = parts[0] || '';
            document.getElementById('edit_last_name').value = parts.slice(1).join(' ') || '';
            
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_department_id').value = deptId;
            document.getElementById('edit_role_id').value = roleId;
            document.getElementById('edit_status').value = status;
            
            let modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
    </script>
</body>
</html>
