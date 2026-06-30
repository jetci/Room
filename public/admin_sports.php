<?php
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
use App\Models\Booking;
use App\Middleware\AuthMiddleware;

$currentUser = AuthMiddleware::requireAdmin();
$role = $currentUser['role_name'] ?? 'Admin';

// จัดการคำร้องขอเพิ่มหรือลบสนามกีฬา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_token']) || $_POST['_token'] !== csrf_token()) {
        $_SESSION['error_message'] = "คำขอไม่ถูกต้อง (CSRF Token Mismatch)";
        header("Location: admin_sports.php");
        exit;
    }

    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        Booking::createSportsFacility($_POST);
        $_SESSION['success_message'] = "บันทึกข้อมูลสนามกีฬาและลานกีฬาอเนกประสงค์ใหม่เรียบร้อยแล้ว!";
    } elseif ($action === 'delete') {
        $facilityId = (int)($_POST['facility_id'] ?? 0);
        Booking::deleteSportsFacility($facilityId);
        $_SESSION['success_message'] = "ลบข้อมูลสนามกีฬา (ID: #SF-{$facilityId}) ออกจากระบบเรียบร้อยแล้ว";
    }

    header("Location: admin_sports.php");
    exit;
}

$successMsg = $_SESSION['success_message'] ?? null;
$errorMsg = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// ดึงข้อมูลรายการสนามกีฬาทั้งหมด
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
    <title>จัดการสนามกีฬา & สถานที่ (Sports Admin) - <?= htmlspecialchars($currentOrgName) ?></title>
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
        .table-custom th { background-color: #f1f5f9; color: #334155; font-weight: 700; border-bottom: 2px solid #e2e8f0; padding: 16px; font-size: 0.95rem; }
        .table-custom td { padding: 20px 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        .table-custom tr:hover td { background-color: #f8fafc; }
        
        .badge-custom { padding: 8px 16px; border-radius: 12px; font-weight: 600; font-size: 0.85rem; }
    </style>
</head>
<body>

    <!-- Top Navbar Component -->
    <?php include __DIR__ . '/../app/components/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            
            <!-- Sidebar Navigation Component -->
            <?php include __DIR__ . '/../app/components/sidebar.php'; ?>

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
                        <h3 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-trophy me-2 text-indigo"></i> จัดการสนามกีฬา & สถานที่ (Sports Facility Management)</h3>
                        <p class="text-muted mb-0">เพิ่ม แก้ไข ปิดปรับปรุงลานกีฬาอเนกประสงค์ และจัดการข้อมูลสถานที่ยืมอุปกรณ์ขององค์กร</p>
                    </div>
                    <div class="d-flex gap-3">
                        <a href="sports.php" class="btn btn-outline-indigo px-4 py-3 rounded-3 fw-semibold"><i class="fa-solid fa-eye me-2"></i> ดูหน้ามุมมองผู้ใช้ทั่วไป</a>
                        <button class="btn btn-custom-primary px-4 py-3 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#newFacilityModal">
                            <i class="fa-solid fa-plus me-2"></i> เพิ่มสนามกีฬาใหม่
                        </button>
                    </div>
                </div>

                <!-- FACILITIES TABLE CARD -->
                <div class="card card-custom p-4 mb-5">
                    <h5 class="fw-bold mb-4 text-dark px-2"><i class="fa-solid fa-layer-group me-2 text-primary"></i> รายชื่อสนามกีฬาและสถานที่ให้บริการในระบบ</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อสถานที่ (Facility Name)</th>
                                    <th>หมวดหมู่ (Category)</th>
                                    <th>สถานที่ตั้ง (Location)</th>
                                    <th>อุปกรณ์ให้บริการ (Available Equipments)</th>
                                    <th>ความจุ (Capacity)</th>
                                    <th>สถานะ</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sportsFacilities as $facility): ?>
                                    <tr>
                                        <td class="fw-bold text-indigo">#SF-<?= htmlspecialchars($facility['id']) ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($facility['facility_name']) ?></div>
                                            <?php if (!empty($facility['rules'])): ?>
                                                <div class="text-secondary fs-8 mt-1"><i class="fa-solid fa-circle-info me-1 text-warning"></i> <strong>กฎระเบียบ:</strong> <?= htmlspecialchars($facility['rules']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border badge-custom"><?= htmlspecialchars($facility['category']) ?></span></td>
                                        <td class="text-secondary"><?= htmlspecialchars($facility['location']) ?></td>
                                        <td>
                                            <div class="text-muted fs-8" style="max-width: 250px;">
                                                <?= !empty($facility['available_equipments']) ? htmlspecialchars($facility['available_equipments']) : 'ไม่มีอุปกรณ์ระบุ' ?>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-indigo-subtle text-indigo badge-custom"><?= htmlspecialchars($facility['capacity']) ?> คน</span></td>
                                        <td><span class="badge bg-success badge-custom"><i class="fa-solid fa-circle-check me-1"></i> ใช้งาน (Active)</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light px-3 py-2 rounded-3 me-1 fw-semibold border"><i class="fa-solid fa-pen-to-square text-primary me-1"></i> แก้ไข</button>
                                            <form action="admin_sports.php" method="POST" class="d-inline" onsubmit="return confirm('คุณยืนยันที่จะลบสนามกีฬา/สถานที่นี้ออกจากระบบใช่หรือไม่?');">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="facility_id" value="<?= htmlspecialchars($facility['id']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-light px-3 py-2 rounded-3 fw-semibold border text-danger"><i class="fa-solid fa-trash-can me-1"></i> ลบ</button>
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

    <!-- 🏷️ MODAL: เพิ่มสนามกีฬาใหม่ -->
    <div class="modal fade" id="newFacilityModal" tabindex="-1" aria-labelledby="newFacilityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 28px; border: none; overflow: hidden;">
                <div class="modal-header bg-indigo p-4 text-white">
                    <h5 class="modal-title fw-bold" id="newFacilityModalLabel"><i class="fa-solid fa-plus me-2"></i> เพิ่มสนามกีฬา / สถานที่ใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-5">
                    <form action="admin_sports.php" method="POST" class="row g-4">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark">ชื่อสนามกีฬา / สถานที่ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3.5 rounded-3 border" name="facility_name" placeholder="ตัวอย่าง: สนามฟุตบอลหญ้าเทียม โซน D" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">หมวดหมู่กีฬา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3.5 rounded-3 border" name="category" placeholder="ตัวอย่าง: สนามฟุตบอล / ฟุตซอล" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">สถานที่ตั้ง / โซน <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3.5 rounded-3 border" name="location" placeholder="ตัวอย่าง: ศูนย์กีฬาชุมชน โซน D" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">ความจุรองรับ (คน) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control p-3.5 rounded-3 border" name="capacity" placeholder="ตัวอย่าง: 22" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">URL รูปภาพประจำสนาม</label>
                            <input type="text" class="form-control p-3.5 rounded-3 border" name="image_url" placeholder="https://images.unsplash.com/...">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark">อุปกรณ์กีฬาที่มีให้บริการ ณ สนาม (คำอธิบาย)</label>
                            <input type="text" class="form-control p-3.5 rounded-3 border" name="available_equipments" placeholder="ตัวอย่าง: ลูกฟุตบอล 5 ลูก, เสื้อเอี๊ยม 20 ตัว, ไฟสปอตไลท์">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark">กฎระเบียบการใช้สถานที่ (Rules)</label>
                            <textarea class="form-control p-3.5 rounded-3 border" name="rules" rows="2" placeholder="ตัวอย่าง: กรุณาสวมรองเท้ากีฬาเท่านั้น ไม่อนุญาตให้นำอาหารขึ้นสนาม"></textarea>
                        </div>
                        <div class="col-12 text-end mt-5">
                            <button type="button" class="btn btn-light px-4 py-3 rounded-3 me-2 fw-semibold border" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-bold shadow-sm"><i class="fa-solid fa-floppy-disk me-2"></i> บันทึกข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
