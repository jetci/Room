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

    if (isset($_FILES['org_logo_file']) && $_FILES['org_logo_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['org_logo_file']['tmp_name'];
        $fileName = $_FILES['org_logo_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            // สร้างโฟลเดอร์ uploads หากยังไม่มี
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $newFileName = 'logo_' . time() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            
            if (@move_uploaded_file($fileTmpPath, $destPath)) {
                $logoUrl = 'uploads/' . $newFileName;
            } else {
                // Simulation Fallback หากติด Permission หรืออยู่บน Vercel Serverless
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
            }
        }
    } elseif (!empty($_POST['org_logo_url'])) {
        $logoUrl = $_POST['org_logo_url'];
    }

    $_SESSION['org_logo'] = $logoUrl;
    $_SESSION['org_name'] = $orgName;
    $_SESSION['org_address'] = $orgAddress;
    $_SESSION['org_tax_id'] = $orgTaxId;
    $_SESSION['org_phone'] = $orgPhone;

    $_SESSION['success_message'] = "บันทึกข้อมูลองค์กรและอัปเดตตราสัญลักษณ์/โลโก้ (Organization Logo) ในระบบเรียบร้อยแล้ว";
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
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; }
        .sidebar { min-height: calc(100vh - 84px); background-color: #ffffff; border-right: 1px solid #e2e8f0; }
        .nav-link { font-weight: 600; color: #64748b; padding: 14px 24px; border-radius: 12px; margin-bottom: 6px; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background-color: #f1f5f9; color: #4338ca; }
        .nav-link.active { background-color: #e0e7ff; color: #4338ca; border-left: 5px solid #4338ca; }
        .card-preview { border-radius: 20px; border: 1px solid #cbd5e1; background: #ffffff; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); }
        .logo-preview-box { width: 150px; height: 150px; border-radius: 16px; border: 2px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden; background-color: #f8fafc; margin: 0 auto; }
        .logo-preview-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom py-3 sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="<?= $currentLogo ?>" alt="Logo" class="me-3" style="max-height: 44px; width: auto;">
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
                            
                            <form action="admin_settings.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                                
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
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-cloud-arrow-up me-2 text-indigo"></i> อัปโหลดตราสัญลักษณ์ / โลโก้ใหม่ (Logo Upload)</h6>
                                        <p class="text-muted fs-7 mb-3">เลือกระหว่างการอัปโหลดไฟล์รูปภาพจากเครื่องคอมพิวเตอร์ของคุณ หรือระบุที่อยู่ลิงก์รูปภาพ (URL) โดยตรง</p>
                                        
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold text-dark">1. อัปโหลดไฟล์รูปภาพ (PNG, JPG, SVG)</label>
                                            <input type="file" class="form-control p-3 rounded-3 bg-white" name="org_logo_file" accept="image/*" onchange="previewImage(event)">
                                            <span class="text-muted fs-8 mt-1 d-block">ขนาดไฟล์แนะนำไม่เกิน 2MB (อัตราส่วน 1:1 หรือพื้นหลังโปร่งใส)</span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-dark">2. หรือ ระบุที่อยู่ URL ของรูปภาพโลโก้</label>
                                            <input type="url" class="form-control p-3 rounded-3 bg-white" name="org_logo_url" value="<?= htmlspecialchars($currentLogo) ?>" placeholder="https://example.com/logo.png">
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
                                <i class="fa-solid fa-lightbulb me-2 text-indigo"></i> <strong>ระบบ White-labeling:</strong> รองรับการปรับเปลี่ยนชื่อและโลโก้เพื่อนำไปใช้งานกับหน่วยงานราชการหรือเทศบาลอื่นๆ ได้อย่างไร้รอยต่อ
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('livePreviewImg').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
