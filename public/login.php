<?php
require_once __DIR__ . '/../routes/web.php';
use App\Models\Booking;

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $demoRole = $_POST['demo_role'] ?? '';

    // กรณีเลือก Demo Login
    if ($demoRole === 'admin') {
        $_SESSION['user'] = ['id' => 1, 'full_name' => 'คุณสมชาย บริหารดี (Admin)', 'email' => 'admin@wiang.go.th', 'role_name' => 'Admin', 'role_id' => 1, 'status' => 'active'];
        setcookie('user_session_payload', json_encode($_SESSION['user']), time() + 86400, '/');
        header("Location: dashboard.php");
        exit;
    } elseif ($demoRole === 'approver') {
        $_SESSION['user'] = ['id' => 2, 'full_name' => 'คุณสมศรี อนุมัติการ (Approver)', 'email' => 'approver@wiang.go.th', 'role_name' => 'Approver', 'role_id' => 2, 'status' => 'active'];
        setcookie('user_session_payload', json_encode($_SESSION['user']), time() + 86400, '/');
        header("Location: dashboard.php");
        exit;
    } elseif ($demoRole === 'user') {
        $_SESSION['user'] = ['id' => 3, 'full_name' => 'คุณใจดี พนักงานทั่วไป (User - Active)', 'email' => 'user@wiang.go.th', 'role_name' => 'User', 'role_id' => 3, 'status' => 'active'];
        setcookie('user_session_payload', json_encode($_SESSION['user']), time() + 86400, '/');
        header("Location: dashboard.php");
        exit;
    } elseif ($demoRole === 'executive') {
        $_SESSION['user'] = ['id' => 4, 'full_name' => 'ท่านนายก ประเสริฐศักดิ์ (Executive)', 'email' => 'executive@wiang.go.th', 'role_name' => 'Executive', 'role_id' => 4, 'status' => 'active'];
        setcookie('user_session_payload', json_encode($_SESSION['user']), time() + 86400, '/');
        header("Location: dashboard.php");
        exit;
    } elseif ($demoRole === 'user_inactive') {
        $_SESSION['user'] = ['id' => 5, 'full_name' => 'คุณรอคอย สมาชิกใหม่ (User - รออนุมัติ)', 'email' => 'waiting@wiang.go.th', 'role_name' => 'User', 'role_id' => 3, 'status' => 'inactive'];
        setcookie('user_session_payload', json_encode($_SESSION['user']), time() + 86400, '/');
        header("Location: dashboard.php");
        exit;
    }

    // กรณีล็อกอินด้วยอีเมลและรหัสผ่านจริง
    $users = Booking::getAllUsers();
    $found = false;
    foreach ($users as $u) {
        if ($u['email'] === $email) {
            $found = true;
            if ($u['status'] === 'inactive') {
                $errorMsg = 'บัญชีของคุณอยู่ระหว่าง "รอการอนุมัติจาก Approver" (ยังไม่เปิดใช้งาน)';
            } elseif ($u['status'] === 'suspended') {
                $errorMsg = 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
            } else {
                // สมมติว่าผ่าน (หรือเช็ค hash)
                $_SESSION['user'] = [
                    'id' => $u['id'], 
                    'full_name' => $u['full_name'], 
                    'email' => $u['email'], 
                    'role_name' => $u['role_name'] ?? 'User',
                    'role_id' => $u['role_id'] ?? 3
                ];
                setcookie('user_session_payload', json_encode($_SESSION['user']), time() + 86400, '/');
                header("Location: dashboard.php");
                exit;
            }
            break;
        }
    }

    if (!$found && empty($errorMsg)) {
        $errorMsg = 'ไม่พบข้อมูลบัญชีอีเมลนี้ในระบบ กรุณาตรวจสอบอีกครั้ง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Smart Room Booking (อบต.เวียง)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            border-radius: 32px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            background: #ffffff;
            overflow: hidden;
            max-width: 950px;
            width: 100%;
        }
        .left-box {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .right-box {
            padding: 48px;
        }
        .demo-btn {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px 16px;
            background: #f8fafc;
            color: #1e293b;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 12px;
        }
        .demo-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 d-flex justify-content-center">
                <div class="login-card row g-0">
                    <!-- Left Presentation Box -->
                    <div class="col-md-5 left-box d-none d-md-flex">
                        <i class="fa-solid fa-flag-checkered fa-4x mb-4 text-white"></i>
                        <h2 class="fw-bold mb-2">อบต.เวียง</h2>
                        <h5 class="fw-semibold mb-3 opacity-90">SMART ROOM BOOKING</h5>
                        <p class="fs-6 opacity-75 mb-4">ระบบบริหารจัดการและจองห้องประชุมอัจฉริยะ สำหรับบุคลากรภายในและประชาชนผู้มาติดต่อ</p>
                        
                        <div class="p-4 rounded-4 mt-auto" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa-solid fa-users-gear fs-4 me-2"></i>
                                <span class="fw-bold fs-6">ทดสอบเข้าใช้งานทันที (Demo)</span>
                            </div>
                            <p class="fs-7 mb-0 opacity-75">เพื่อความสะดวกในการทดสอบ คุณสามารถเลือกกดปุ่มเข้าสู่ระบบอัตโนมัติตามสิทธิ์ต่างๆ ทางขวามือได้ทันที</p>
                        </div>
                    </div>

                    <!-- Right Form Box -->
                    <div class="col-md-7 right-box">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3 class="fw-bold text-indigo mb-1">เข้าสู่ระบบ (Login)</h3>
                                <p class="text-muted fs-7 mb-0">กรุณากรอกอีเมลและรหัสผ่านของคุณ</p>
                            </div>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 fw-semibold"><i class="fa-solid fa-house me-1"></i> หน้าหลัก</a>
                        </div>

                        <?php if (!empty($errorMsg)): ?>
                            <div class="alert alert-danger p-3 rounded-3 fs-7 mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($errorMsg) ?></div>
                        <?php endif; ?>

                        <!-- Standard Form Login -->
                        <form action="login.php" method="POST" class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-7">อีเมล (Email)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control p-3 border-start-0 bg-light" name="email" placeholder="admin@wiang.go.th" required>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold fs-7">รหัสผ่าน (Password)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control p-3 border-start-0 bg-light" name="password" placeholder="••••••••" required>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-custom-primary w-100 py-3 rounded-3 fw-semibold fs-6 shadow-sm">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> เข้าสู่ระบบ
                                </button>
                            </div>
                        </form>

                        <div class="position-relative my-4 text-center">
                            <hr class="text-muted opacity-25">
                            <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted fs-7 fw-semibold">หรือเลือกเข้าสู่ระบบด่วน (Demo Login)</span>
                        </div>

                        <!-- Demo Login Buttons -->
                        <form action="login.php" method="POST">
                            <input type="hidden" name="demo_role" value="admin">
                            <button type="submit" class="demo-btn">
                                <span class="d-flex align-items-center"><i class="fa-solid fa-user-shield me-3 text-indigo fs-5"></i> คุณสมชาย (ผู้ดูแลระบบ - Admin)</span>
                                <i class="fa-solid fa-arrow-right text-muted"></i>
                            </button>
                        </form>

                        <form action="login.php" method="POST">
                            <input type="hidden" name="demo_role" value="approver">
                            <button type="submit" class="demo-btn">
                                <span class="d-flex align-items-center"><i class="fa-solid fa-user-check me-3 text-success fs-5"></i> คุณสมศรี (ผู้อนุมัติ - Approver)</span>
                                <i class="fa-solid fa-arrow-right text-muted"></i>
                            </button>
                        </form>

                        <form action="login.php" method="POST">
                            <input type="hidden" name="demo_role" value="user">
                            <button type="submit" class="demo-btn">
                                <span class="d-flex align-items-center"><i class="fa-solid fa-user me-3 text-warning fs-5"></i> คุณใจดี (พนักงาน/สมาชิกทั่วไป - Active)</span>
                                <i class="fa-solid fa-arrow-right text-muted"></i>
                            </button>
                        </form>

                        <form action="login.php" method="POST">
                            <input type="hidden" name="demo_role" value="executive">
                            <button type="submit" class="demo-btn" style="border-left: 4px solid #0dcaf0;">
                                <span class="d-flex align-items-center"><i class="fa-solid fa-user-tie me-3 text-info fs-5"></i> ท่านนายก ประเสริฐศักดิ์ (ผู้บริหาร - Executive)</span>
                                <i class="fa-solid fa-arrow-right text-muted"></i>
                            </button>
                        </form>

                        <form action="login.php" method="POST">
                            <input type="hidden" name="demo_role" value="user_inactive">
                            <button type="submit" class="demo-btn" style="border-left: 4px solid #f59e0b;">
                                <span class="d-flex align-items-center"><i class="fa-solid fa-user-lock me-3 text-secondary fs-5"></i> คุณรอคอย (สมาชิกใหม่ - รออนุมัติ / Inactive)</span>
                                <i class="fa-solid fa-arrow-right text-muted"></i>
                            </button>
                        </form>

                        <div class="col-12 text-center mt-4">
                            <span class="text-muted fs-7">ยังไม่มีบัญชีผู้ใช้งาน? <a href="register.php" class="fw-bold text-indigo text-decoration-none">สมัครสมาชิกที่นี่</a></span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
