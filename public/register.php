<?php
require_once __DIR__ . '/../routes/web.php';
use App\Models\Booking;

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if ($pass !== $confirmPass) {
        $errorMsg = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง';
    } else {
        $data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'password' => $pass,
            'department_id' => (int)($_POST['department_id'] ?? 1),
            'role_id' => 3, // 3 = User (พนักงาน / สมาชิกทั่วไป)
            'status' => 'inactive' // inactive = รอการอนุมัติจาก Approver
        ];

        if (!empty($data['first_name']) && !empty($data['email']) && !empty($data['password'])) {
            $created = Booking::createUser($data);
            if ($created) {
                $successMsg = 'สมัครสมาชิกสำเร็จ! บัญชีของคุณอยู่ในสถานะ "รอการอนุมัติจาก Approver (ผู้อนุมัติ)" เรียบร้อยแล้ว';
            } else {
                $errorMsg = 'ไม่สามารถสมัครสมาชิกได้ อีเมลนี้อาจมีอยู่ในระบบแล้ว';
            }
        } else {
            $errorMsg = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
        }
    }
}

$departments = Booking::getAllDepartments();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Smart Room Booking (อบต.เวียง)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* ลดความเข้มสี Placeholder และทำให้ดูนุ่มนวลขึ้น */
        .form-control::placeholder {
            color: #94a3b8 !important; /* สีเทาอ่อน */
            opacity: 0.65 !important;
            font-style: italic; /* ตัวเอียงเล็กน้อยให้รู้ว่าเป็นคำอธิบาย */
        }
        body {
            background: linear-gradient(135deg, #eef2f3 0%, #8e9eab 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            border-radius: 28px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            background: #ffffff;
            overflow: hidden;
            max-width: 950px;
            width: 100%;
        }
        .left-box {
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            color: white;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .right-box {
            padding: 48px;
        }
    </style>
</head>
<body>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-12 d-flex justify-content-center">
                <div class="register-card row g-0">
                    <!-- Left Presentation Box -->
                    <div class="col-md-5 left-box d-none d-md-flex">
                        <i class="fa-solid fa-building-flag fa-4x mb-4 text-white"></i>
                        <h2 class="fw-bold mb-2">อบต.เวียง</h2>
                        <h5 class="fw-semibold mb-3 opacity-90">SMART ROOM BOOKING</h5>
                        <p class="fs-6 opacity-75 mb-4">ระบบลงทะเบียนสมาชิกสำหรับบุคลากรภายในองค์การบริหารส่วนตำบลเวียง และหน่วยงานราชการภายนอก เพื่อเข้าถึงบริการจองห้องประชุม</p>
                    </div>

                    <!-- Right Form Box -->
                    <div class="col-md-7 right-box">
                        <h3 class="fw-bold text-indigo mb-1">สมัครสมาชิกใหม่</h3>
                        <p class="text-muted fs-7 mb-4">กรุณากรอกข้อมูลของคุณเพื่อส่งคำขอเปิดบัญชีผู้ใช้งาน</p>

                        <?php if (!empty($successMsg)): ?>
                            <div class="alert alert-success p-4 rounded-4 shadow-sm mb-4" role="alert">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fa-solid fa-circle-check fs-3 me-2 text-success"></i>
                                    <span class="fw-bold fs-5 text-success">ลงทะเบียนสำเร็จ!</span>
                                </div>
                                <p class="mb-3 fs-7"><?= htmlspecialchars($successMsg) ?></p>
                                <a href="index.php" class="btn btn-success w-100 py-3 rounded-3 fw-semibold">กลับสู่หน้าหลัก / เข้าสู่ระบบ</a>
                            </div>
                        <?php else: ?>

                            <?php if (!empty($errorMsg)): ?>
                                <div class="alert alert-danger p-3 rounded-3 fs-7 mb-4"><?= htmlspecialchars($errorMsg) ?></div>
                            <?php endif; ?>

                            <form action="register.php" method="POST" class="row g-3" onsubmit="return checkPasswordMatch();">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold fs-7">ชื่อ (First Name) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                                        <input type="text" class="form-control p-3 border-start-0 bg-light" name="first_name" placeholder="ตัวอย่าง: สมชาย" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold fs-7">นามสกุล (Last Name) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control p-3 bg-light" name="last_name" placeholder="ตัวอย่าง: บริหารดี" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold fs-7">อีเมล (Email) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                                        <input type="email" class="form-control p-3 border-start-0 bg-light" name="email" placeholder="ตัวอย่าง: somchai@wiang.go.th" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold fs-7">เบอร์โทรศัพท์ (Phone)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                                        <input type="text" class="form-control p-3 border-start-0 bg-light" name="phone" placeholder="ตัวอย่าง: 0812345678">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold fs-7">ตั้งรหัสผ่าน (Password) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                                        <input type="password" class="form-control p-3 border-start-0 bg-light" id="reg_password" name="password" placeholder="ตัวอย่าง: ••••••••" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold fs-7">ยืนยันรหัสผ่าน (Confirm Password) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                                        <input type="password" class="form-control p-3 border-start-0 bg-light" id="reg_confirm_password" name="confirm_password" placeholder="ตัวอย่าง: ••••••••" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold fs-7">สังกัด / แผนก (Department) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-building text-muted"></i></span>
                                        <select class="form-select p-3 border-start-0 bg-light" name="department_id" required>
                                            <option value="" selected disabled>-- กรุณาเลือกสังกัด / แผนก --</option>
                                            <?php foreach ($departments as $d): ?>
                                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-custom-primary w-100 py-3 rounded-3 fw-semibold fs-6 shadow-sm">
                                        <i class="fa-solid fa-user-plus me-2"></i> ยืนยันสมัครสมาชิก
                                    </button>
                                </div>

                                <div class="col-12 text-center mt-4">
                                    <span class="text-muted fs-7">มีบัญชีผู้ใช้งานอยู่แล้ว? <a href="login.php" class="fw-bold text-indigo text-decoration-none">เข้าสู่ระบบที่นี่</a></span>
                                </div>
                            </form>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPasswordMatch() {
            let pass = document.getElementById('reg_password').value;
            let confirm = document.getElementById('reg_confirm_password').value;
            if (pass !== confirm) {
                alert('⚠️ รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง!');
                document.getElementById('reg_confirm_password').focus();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
