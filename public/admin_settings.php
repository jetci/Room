<?php
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
use App\Models\Booking;
use App\Middleware\AuthMiddleware;

$currentUser = AuthMiddleware::requireAdmin();
$role = $currentUser['role_name'] ?? 'Admin';
$userStatus = $currentUser['status'] ?? 'active';

// จัดการการบันทึกข้อมูลองค์กรและอัปโหลดโลโก้
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_token']) || $_POST['_token'] !== csrf_token()) {
        $_SESSION['error_message'] = "คำขอไม่ถูกต้อง (CSRF Token Mismatch)";
        header("Location: admin_settings.php");
        exit;
    }

    $orgName = $_POST['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
    $orgAddress = $_POST['org_address'] ?? 'อำเภอเชียงคำ จังหวัดพะเยา 56110';
    $orgTaxId = $_POST['org_tax_id'] ?? '0994000123456';
    $orgPhone = $_POST['org_phone'] ?? '054-456789';

    // จัดการอัปโหลดไฟล์โลโก้ (Simulation & Real Upload handling)
    $logoUrl = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';

    // ตรวจสอบภาพที่ผ่านการตัดแต่งและย่อขยาย (Cropped Logo Data Base64)
    if (!empty($_POST['cropped_logo_data']) && strpos($_POST['cropped_logo_data'], 'data:image') === 0) {
        $base64Data = $_POST['cropped_logo_data'];
        
        // แยกส่วน data tag ออกเพื่อบันทึกไฟล์กายภาพสำรองไว้
        list($type, $data) = explode(';', $base64Data);
        list(, $data)      = explode(',', $data);
        $fileData = base64_decode($data);

        // หา extension จาก MIME type
        $ext = 'png';
        if (strpos($type, 'jpeg') !== false) $ext = 'jpg';
        elseif (strpos($type, 'svg') !== false) $ext = 'svg';

        // สร้างโฟลเดอร์ uploads หากยังไม่มี
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $newFileName = 'logo_cropped_' . time() . '.' . $ext;
        $destPath = $uploadDir . $newFileName;
        @file_put_contents($destPath, $fileData);

        // ใช้ Base64 string เต็มรูปแบบ (Data URI) ใน Session เพื่อการแสดงผลที่เสถียร 100% ในทุกๆ หน้าเว็บ ตัดปัญหา Path 404
        $logoUrl = $base64Data;
    } elseif (isset($_FILES['org_logo_file']) && $_FILES['org_logo_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['org_logo_file']['tmp_name'];
        $fileName = $_FILES['org_logo_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $newFileName = 'logo_' . time() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            
            if (@move_uploaded_file($fileTmpPath, $destPath)) {
                // แปลงไฟล์กายภาพที่อัปโหลดเป็น Base64 Data URI เพื่อความเสถียรเช่นกัน
                $fileContents = file_get_contents($destPath);
                $mimeType = mime_content_type($destPath);
                $logoUrl = 'data:' . $mimeType . ';base64,' . base64_encode($fileContents);
            } else {
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
            }
        }
    }

    $_SESSION['org_logo'] = $logoUrl;
    $_SESSION['org_name'] = $orgName;
    $_SESSION['org_address'] = $orgAddress;
    $_SESSION['org_tax_id'] = $orgTaxId;
    $_SESSION['org_phone'] = $orgPhone;

    // ==========================================================================
    // 🚀 PERMANENT FILE STORAGE SAVE LAYER (Survives Logout & Session Destroy)
    // ==========================================================================
    $storageDir = __DIR__ . '/../storage/';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0755, true);
    }
    $settingsData = [
        'org_logo' => $logoUrl,
        'org_name' => $orgName,
        'org_address' => $orgAddress,
        'org_tax_id' => $orgTaxId,
        'org_phone' => $orgPhone,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    @file_put_contents($storageDir . 'settings.json', json_encode($settingsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $_SESSION['success_message'] = "บันทึกข้อมูลองค์กรและอัปเดตตราสัญลักษณ์ (ปรับย่อขยายพอดีเฟรมวงกลม) ในระบบเรียบร้อยแล้ว";
    header("Location: admin_settings.php");
    exit;
}

$successMsg = $_SESSION['success_message'] ?? null;
$errorMsg = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// ดึงค่าการตั้งค่าปัจจุบัน
$currentLogo = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
$currentOrgName = $_SESSION['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
$currentOrgAddress = $_SESSION['org_address'] ?? 'อำเภอเชียงคำ จังหวัดพะเยา 56110';
$currentOrgTaxId = $_SESSION['org_tax_id'] ?? '0994000123456';
$currentOrgPhone = $_SESSION['org_phone'] ?? '054-456789';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าข้อมูลองค์กร & โลโก้ (Organization Settings) - อบต.เวียง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; }
        .sidebar { min-height: calc(100vh - 84px); background-color: #ffffff; border-right: 1px solid #e2e8f0; }
        .nav-link { font-weight: 600; color: #64748b; padding: 14px 24px; border-radius: 12px; margin-bottom: 6px; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background-color: #f1f5f9; color: #4338ca; }
        .nav-link.active { background-color: #e0e7ff; color: #4338ca; border-left: 5px solid #4338ca; }
        .card-preview { border-radius: 20px; border: 1px solid #cbd5e1; background: #ffffff; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); }
        .logo-preview-box { width: 160px; height: 160px; border-radius: 50%; border: 4px solid #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden; background-color: #f8fafc; margin: 0 auto; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        .logo-preview-box img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        
        /* Cropper Custom Styling for Circular Frame */
        .cropper-view-box, .cropper-face {
            border-radius: 50%;
        }
        /* The modal cropper container */
        .img-container {
            max-height: 400px;
            width: 100%;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 16px;
            border: 1px dashed #cbd5e1;
        }
        .img-container img {
            display: block;
            max-width: 100%;
        }
        .range-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: #e2e8f0;
            outline: none;
            padding: 0;
            margin: 10px 0;
        }
        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #4338ca;
            cursor: pointer;
            transition: background .15s ease-in-out;
            box-shadow: 0 2px 6px rgba(67, 56, 202, 0.4);
        }
        .range-slider::-webkit-slider-thumb:hover {
            background: #3730a3;
        }
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
                    <span class="fs-7 text-secondary">ระบบจองห้องประชุมออนไลน์ (Smart Room Booking)</span>
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
                    <li class="nav-item"><a class="nav-link" href="admin_sports.php"><i class="fa-solid fa-trophy me-3"></i> จัดการสนามกีฬา</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_equipments.php"><i class="fa-solid fa-couch me-3"></i> จัดการอุปกรณ์</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & Export</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_users.php"><i class="fa-solid fa-users-gear me-3"></i> จัดการผู้ใช้</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_announcements.php"><i class="fa-solid fa-bullhorn me-3"></i> ประกาศส่วนกลาง</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_settings.php"><i class="fa-solid fa-house-flag me-3"></i> ตั้งค่าข้อมูล & โลโก้</a></li>
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
                        <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-house-flag me-2 text-indigo"></i> ตั้งค่าข้อมูลองค์กร & ระบบอัปโหลดตราสัญลักษณ์ (Branding & Logo Settings)</h4>
                        <p class="text-muted mb-0">ปรับแต่งตราสัญลักษณ์ โลโก้องค์กร และชื่อหน่วยงานที่จะปรากฏบนหัวเอกสารใบยืนยันการจองห้องประชุม (e-Memo) และแถบ Navbar ของระบบ</p>
                    </div>
                    <a href="print_slip.php?id=1012" class="btn btn-outline-indigo px-4 py-3 rounded-3 fw-semibold"><i class="fa-solid fa-print me-2"></i> ทดสอบดูตัวอย่างใบยืนยันการจอง</a>
                </div>

                <div class="row g-5">
                    
                    <!-- FORM COLUMN -->
                    <div class="col-lg-8">
                        <div class="card card-preview p-5">
                            <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-building me-2 text-primary"></i> ฟอร์มจัดการข้อมูลองค์กรและโลโก้</h5>
                            
                            <form id="brandingForm" action="admin_settings.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="cropped_logo_data" id="cropped_logo_data">
                                
                                <div class="row g-4">
                                    <!-- ชื่อองค์กร -->
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-dark">ชื่อองค์กร / หน่วยงาน (Organization Name) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control p-3 rounded-3 bg-white" name="org_name" value="<?= htmlspecialchars($currentOrgName) ?>" required placeholder="ตัวอย่าง: องค์การบริหารส่วนตำบลเวียง">
                                    </div>

                                    <!-- ที่อยู่องค์กร -->
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-dark">สถานที่ตั้ง / ที่อยู่ (Address) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control p-3 rounded-3 bg-white" name="org_address" value="<?= htmlspecialchars($currentOrgAddress) ?>" required placeholder="ตัวอย่าง: อำเภอเชียงคำ จังหวัดพะเยา 56110">
                                    </div>

                                    <!-- เลขประจำตัวผู้เสียภาษี -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark">เลขประจำตัวผู้เสียภาษี / เลขหน่วยงาน</label>
                                        <input type="text" class="form-control p-3 rounded-3 bg-white" name="org_tax_id" value="<?= htmlspecialchars($currentOrgTaxId) ?>" placeholder="ตัวอย่าง: 0994000123456">
                                    </div>

                                    <!-- เบอร์โทรศัพท์ -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark">เบอร์โทรศัพท์ติดต่อ (Contact Phone)</label>
                                        <input type="text" class="form-control p-3 rounded-3 bg-white" name="org_phone" value="<?= htmlspecialchars($currentOrgPhone) ?>" placeholder="ตัวอย่าง: 054-456789">
                                    </div>

                                    <!-- อัปโหลดไฟล์โลโก้ -->
                                    <div class="col-md-12 mt-5 pt-4 border-top">
                                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-cloud-arrow-up me-2 text-indigo"></i> อัปโหลดและปรับขนาดตราสัญลักษณ์ (Upload & Auto-fit Frame)</h6>
                                        <p class="text-muted fs-7 mb-4">เมื่อเลือกไฟล์ภาพ ระบบจะแสดงหน้าต่างสำหรับปรับ ย่อ-ขยาย และจัดตำแหน่งภาพให้พอดีกับเฟรมวงกลมโดยอัตโนมัติ</p>
                                        
                                        <div class="mb-2 p-4 rounded-4 bg-light border">
                                            <label class="form-label fw-semibold text-dark">อัปโหลดไฟล์รูปภาพตราสัญลักษณ์ (PNG, JPG, SVG)</label>
                                            <input type="file" class="form-control p-3 rounded-3 bg-white shadow-sm" id="logoFileInput" name="org_logo_file" accept="image/*">
                                            <div class="d-flex align-items-center mt-2 text-secondary fs-8">
                                                <i class="fa-solid fa-circle-info me-2 text-indigo"></i> 
                                                <span>รองรับการปรับย่อ-ขยาย (Zoom in / out) และย้ายตำแหน่งเพื่อให้ภาพสอดคล้องกับกรอบวงกลมเป๊ะๆ</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end pt-4 border-top mt-5">
                                    <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-bold fs-6 shadow-sm"><i class="fa-solid fa-floppy-disk me-2"></i> บันทึกและอัปเดตข้อมูลองค์กร</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- PREVIEW COLUMN -->
                    <div class="col-lg-4">
                        <div class="card card-preview p-5 bg-slate-50 text-center">
                            <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-eye me-2 text-indigo"></i> ตัวอย่างโลโก้องค์กรปัจจุบัน</h5>
                            <p class="text-muted fs-7 mb-4">รูปภาพและชื่อองค์กรด้านล่างจะถูกนำไปแสดงผลที่หัวใบยืนยันการจองห้องประชุม (e-Memo) และแถบเมนูหลัก</p>
                            
                            <div class="logo-preview-box mb-4 shadow-sm bg-white p-3">
                                <img id="livePreviewImg" src="<?= $currentLogo ?>" alt="Organization Logo">
                            </div>

                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($currentOrgName) ?></h5>
                            <p class="text-muted fs-7 mb-3"><?= htmlspecialchars($currentOrgAddress) ?></p>

                            <div class="alert alert-indigo-subtle p-3 rounded-4 mt-4 text-start fs-7" style="border: 1px solid #c7d2fe; background-color: #e0e7ff; color: #3730a3;">
                                <i class="fa-solid fa-arrows-to-circle me-2 text-indigo fs-5 pull-left"></i> 
                                <strong>ฟังก์ชันปรับให้พอดีเฟรม:</strong> ภาพที่อัปโหลดใหม่จะถูกจัดทรงให้อยู่ในกรอบวงกลมอย่างงดงามทันที เพื่อรักษาความเป็นระเบียบและมาตรฐานของเอกสารราชการ
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- MODAL FOR CROPPER (ปรับย่อขยายภาพ) -->
    <div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom p-4">
                    <h5 class="modal-title fw-bold text-dark" id="cropModalLabel"><i class="fa-solid fa-crop-simple me-2 text-indigo"></i> ปรับตำแหน่งและย่อขยายภาพ (Adjust & Fit Circle Frame)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-4 fs-7 text-secondary">
                        <i class="fa-solid fa-circle-info me-2 text-primary"></i> <strong>คำแนะนำ:</strong> ใช้ตัวเลื่อน (Slider) ด้านล่างเพื่อ <strong>ย่อ-ขยายภาพ</strong> และใช้เมาส์ลากภาพเพื่อปรับตำแหน่งให้อยู่กึ่งกลางกรอบวงกลมตามต้องการ
                    </div>
                    
                    <!-- Cropper Stage -->
                    <div class="img-container mb-4">
                        <img id="cropperImage" src="" alt="Picture for cropping">
                    </div>

                    <!-- Zoom Slider Controls -->
                    <div class="px-4 py-3 bg-light rounded-4 border mb-3">
                        <label class="form-label fw-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-magnifying-glass-minus me-2"></i>ย่อภาพ (Zoom Out)</span>
                            <span>ขยายภาพ (Zoom In)<i class="fa-solid fa-magnifying-glass-plus ms-2"></i></span>
                        </label>
                        <input type="range" class="range-slider" id="zoomSlider" min="0.1" max="3" step="0.05" value="1">
                    </div>
                    
                    <!-- Additional Action Buttons -->
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold" id="btnRotateLeft"><i class="fa-solid fa-rotate-left me-2"></i>หมุนซ้าย</button>
                        <button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold" id="btnRotateRight"><i class="fa-solid fa-rotate-right me-2"></i>หมุนขวา</button>
                        <button type="button" class="btn btn-outline-danger px-4 py-2 rounded-3 fw-semibold" id="btnReset"><i class="fa-solid fa-arrows-rotate me-2"></i>รีเซ็ต</button>
                    </div>

                </div>
                <div class="modal-footer border-top p-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-light px-4 py-3 rounded-3 fw-semibold border" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-2"></i>ยกเลิก</button>
                    <button type="button" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-bold shadow-sm" id="btnApplyCrop"><i class="fa-solid fa-check me-2"></i>ตกลง (ยืนยันรูปทรงนี้)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let cropper;
        const logoFileInput = document.getElementById('logoFileInput');
        const cropperImage = document.getElementById('cropperImage');
        const zoomSlider = document.getElementById('zoomSlider');
        const cropModalElement = document.getElementById('cropModal');
        const cropModal = new bootstrap.Modal(cropModalElement);
        const livePreviewImg = document.getElementById('livePreviewImg');
        const croppedLogoDataInput = document.getElementById('cropped_logo_data');

        // เมื่อผู้ใช้เลือกไฟล์ภาพ
        logoFileInput.addEventListener('change', function(event) {
            const files = event.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                if (/^image\/\w+/.test(file.type)) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        cropperImage.src = e.target.result;
                        cropModal.show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
                }
            }
        });

        // เมื่อ Modal แสดงขึ้นมา เริ่มตั้งค่า Cropper
        cropModalElement.addEventListener('shown.bs.modal', function() {
            if (cropper) { cropper.destroy(); }
            cropper = new Cropper(cropperImage, {
                aspectRatio: 1, // บังคับอัตราส่วน 1:1 สำหรับกรอบวงกลม
                viewMode: 1,
                dragMode: 'move', // ใช้เลื่อนภาพไปมา
                autoCropArea: 0.9,
                restore: false,
                modal: true,
                guides: false,
                highlight: false,
                cropBoxMovable: false, // ล็อกกรอบวงกลมให้อยู่ตรงกลาง แล้วเลื่อนภาพเอา
                cropBoxResizable: false, // ล็อกขนาดกรอบ
                toggleDragModeOnDblclick: false,
                ready: function () {
                    // กำหนดค่าตั้งต้นให้ Slider สอดคล้องกับภาพ
                    const containerData = cropper.getContainerData();
                    const imageData = cropper.getImageData();
                    zoomSlider.value = imageData.scaleX || 1;
                }
            });
        });

        // เมื่อปิด Modal ให้ล้าง Cropper
        cropModalElement.addEventListener('hidden.bs.modal', function() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        });

        // Slider สำหรับปรับ ย่อ-ขยาย ภาพ
        zoomSlider.addEventListener('input', function() {
            if (cropper) {
                const ratio = parseFloat(this.value);
                cropper.zoomTo(ratio);
            }
        });

        // ปุ่มหมุนภาพ
        document.getElementById('btnRotateLeft').addEventListener('click', function() {
            if (cropper) cropper.rotate(-45);
        });
        document.getElementById('btnRotateRight').addEventListener('click', function() {
            if (cropper) cropper.rotate(45);
        });

        // ปุ่มรีเซ็ต
        document.getElementById('btnReset').addEventListener('click', function() {
            if (cropper) {
                cropper.reset();
                zoomSlider.value = 1;
            }
        });

        // ปุ่ม ยืนยันการตัดภาพ (ตกลง)
        document.getElementById('btnApplyCrop').addEventListener('click', function() {
            if (cropper) {
                // ดึง Canvas ออกมาในขนาดที่คมชัด
                const canvas = cropper.getCroppedCanvas({
                    width: 400,
                    height: 400,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                if (canvas) {
                    // แปลง Canvas เป็น Base64 PNG
                    const croppedBase64 = canvas.toDataURL('image/png');
                    // แสดงตัวอย่างสดที่กรอบ Preview ด้านขวา
                    livePreviewImg.src = croppedBase64;
                    // เก็บค่า Base64 ลงใน input hidden เพื่อบันทึกผ่านฟอร์ม
                    croppedLogoDataInput.value = croppedBase64;
                    // ปิด Modal
                    cropModal.hide();
                }
            }
        });
    </script>
</body>
</html>

