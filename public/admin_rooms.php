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
    Booking::createRoom($_POST, $_FILES);
    $successMsg = 'บันทึกข้อมูลห้องประชุมพร้อมอุปกรณ์และรูปถ่ายสำเร็จ!';
}

$rooms = Booking::getAllRooms();
$features = Booking::getAllFeatures();

$currentLogo = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
$currentOrgName = $_SESSION['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องประชุม - Smart Room Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- Top Navbar Component -->
    <?php include __DIR__ . '/../app/components/navbar.php'; ?>

    <!-- Main Content Layout -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation Component -->
            <?php include __DIR__ . '/../app/components/sidebar.php'; ?>

            <!-- Main Workspace -->
            <div class="col-lg-10 p-5">
                
                <?php if ($successMsg): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center p-4 mb-4" style="border-radius: 16px; border: none; background-color: #dcfce7; color: #15803d;" role="alert">
                        <i class="fa-solid fa-circle-check fs-3 me-3"></i>
                        <div><strong class="fw-bold">สำเร็จ!</strong> <?= htmlspecialchars($successMsg) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">จัดการข้อมูลห้องประชุม (Room Management)</h4>
                        <p class="text-muted mb-0">เพิ่ม แก้ไข ปิดปรับปรุงห้องประชุม และตั้งค่าจำนวนที่นั่ง</p>
                    </div>
                    <button class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#newRoomModal">
                        <i class="fa-solid fa-plus me-2"></i>เพิ่มห้องประชุมใหม่
                    </button>
                </div>

                <!-- Rooms Table Card -->
                <div class="card main-card bg-white p-4 mb-5">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="py-3">ID</th>
                                    <th scope="col" class="py-3">ชื่อห้องประชุม (Room Name)</th>
                                    <th scope="col" class="py-3">รูปถ่าย (Photos)</th>
                                    <th scope="col" class="py-3">สถานที่ (Location)</th>
                                    <th scope="col" class="py-3">อุปกรณ์ (Equipments)</th>
                                    <th scope="col" class="py-3">ความจุ (Capacity)</th>
                                    <th scope="col" class="py-3">สถานะ (Status)</th>
                                    <th scope="col" class="py-3 text-end">จัดการ (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?= $room['id'] ?></td>
                                        <td class="fw-bold text-indigo"><?= htmlspecialchars($room['room_name']) ?></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php if (!empty($room['images'])): ?>
                                                    <?php foreach ($room['images'] as $img): ?>
                                                        <img src="<?= htmlspecialchars($img) ?>" class="rounded-3 object-fit-cover shadow-sm" width="50" height="40" style="border: 1px solid #e2e8f0;">
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted fs-7">ไม่มีรูปภาพ</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($room['location']) ?></td>
                                        <td>
                                            <div class="text-muted fs-7" style="max-width: 220px;">
                                                <?= !empty($room['equipment_summary']) ? htmlspecialchars($room['equipment_summary']) : 'ไม่มีอุปกรณ์ระบุ' ?>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-indigo-light px-3 py-2 fs-7"><?= $room['capacity'] ?> ที่นั่ง</span></td>
                                        <td><span class="badge bg-green-light px-3 py-2 fs-7">ใช้งาน (Active)</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-light btn-sm px-3 py-2 rounded-3 me-1"><i class="fa-solid fa-pen-to-square text-primary"></i> แก้ไข</button>
                                            <button class="btn btn-light btn-sm px-3 py-2 rounded-3" onclick="return confirm('คุณยืนยันที่จะลบห้องประชุมนี้ใช่หรือไม่?');"><i class="fa-solid fa-trash-can text-danger"></i> ลบ</button>
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

    <!-- 🏷️ MODAL: เพิ่มห้องประชุมใหม่ -->
    <div class="modal fade" id="newRoomModal" tabindex="-1" aria-labelledby="newRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-indigo-light p-4" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <h5 class="modal-title fw-bold" id="newRoomModalLabel"><i class="fa-solid fa-plus me-2"></i> เพิ่มห้องประชุมใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="admin_rooms.php" method="POST" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">ชื่อห้องประชุม <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" name="room_name" placeholder="ตัวอย่าง: Room G - Executive Lounge" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">สถานที่ / อาคาร <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" name="location" placeholder="ตัวอย่าง: อาคารสำนักงานใหญ่" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ความจุ (คน) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control p-3" name="capacity" placeholder="ตัวอย่าง: 30" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">อุปกรณ์ประจำห้อง (เลือกได้มากกว่า 1) <span class="text-primary">*</span></label>
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="row g-2">
                                    <?php foreach ($features as $feature): ?>
                                        <div class="col-md-6">
                                            <div class="form-check d-flex align-items-center mb-0">
                                                <input class="form-check-input me-2" type="checkbox" name="features[]" value="<?= $feature['id'] ?>" id="feat_<?= $feature['id'] ?>">
                                                <label class="form-check-label fs-7 fw-medium text-dark" for="feat_<?= $feature['id'] ?>">
                                                    <?= htmlspecialchars($feature['feature_name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">แนบรูปถ่ายห้องประชุม (ประมาณ 5 รูป) <span class="text-primary">*</span></label>
                            <input type="file" class="form-control p-3 bg-white" name="room_images[]" multiple accept="image/*">
                            <div class="form-text text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i> รองรับไฟล์รูปภาพ JPG, PNG, GIF (สามารถเลือกพร้อมกันได้ 5 รูป)</div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
