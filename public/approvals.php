<?php
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
use App\Models\Booking;
use App\Helpers\EmailHelper;
use App\Middleware\AuthMiddleware;

// ตรวจสอบสิทธิ์การเข้าถึง (อนุญาต Admin, Approver, HeadAdmin, Executive)
$currentUser = AuthMiddleware::requireRole(['Admin', 'Approver', 'HeadAdmin', 'Executive']);
$role = $currentUser['role_name'] ?? 'Admin';
$userStatus = $currentUser['status'] ?? 'active';
$avatarName = urlencode($currentUser['full_name'] ?? 'Admin');

$rooms = Booking::getAllRooms();

// จัดการกดอนุมัติ/ปฏิเสธ (เปลี่ยนจาก GET เป็น POST พร้อมตรวจ CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    // ป้องกันสิทธิ์ Executive ทำรายการ (View Only Enforce)
    if ($role === 'Executive') {
        $_SESSION['error_message'] = "ผู้บริหาร (Executive) มีสิทธิ์ดูข้อมูลเท่านั้น ไม่สามารถทำรายการอนุมัติได้";
        header("Location: approvals.php");
        exit;
    }

    // ตรวจสอบ CSRF Token
    if (!isset($_POST['_token']) || $_POST['_token'] !== csrf_token()) {
        $_SESSION['error_message'] = "คำขอไม่ถูกต้อง (CSRF Token Mismatch)";
        header("Location: approvals.php");
        exit;
    }

    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $approverFullName = $currentUser['full_name'] ?? 'Approver';

    if ($action === 'approve_user') {
        Booking::toggleUserStatus($id, 'active');
        $_SESSION['approval_msg'] = "อนุมัติเปิดใช้งานบัญชีผู้ใช้ ID: $id สำเร็จเรียบร้อย";
    } elseif ($action === 'reject_user') {
        Booking::deleteUser($id);
        $_SESSION['approval_msg'] = "ปฏิเสธและลบคำขอสมัครสมาชิก ID: $id เรียบร้อย";
    } elseif ($action === 'verify_step1') {
        Booking::verifyBookingStep1($id, $approverFullName, 'room');
        $_SESSION['approval_msg'] = "ขั้นตอนที่ 1: เจ้าหน้าที่ผู้ดูแล ($approverFullName) ทำการตรวจสอบผ่านคำร้องจองห้องประชุม ID: #{$id} และส่งต่อให้หัวหน้าสำนักปลัดเรียบร้อย";
    } elseif ($action === 'approve_final') {
        Booking::approveBookingFinal($id, $approverFullName, 'room');
        $_SESSION['approval_msg'] = "ขั้นตอนที่ 2: หัวหน้าสำนักปลัด ($approverFullName) ทำการยืนยันขั้นสุดท้ายคำร้อง ID: #{$id} พร้อมประทับลายเซ็นอิเล็กทรอนิกส์ (e-Signature) สำเร็จ";
    } elseif ($action === 'verify_sports_step1') {
        Booking::verifyBookingStep1($id, $approverFullName, 'sports');
        $_SESSION['approval_msg'] = "ขั้นตอนที่ 1: เจ้าหน้าที่ผู้ดูแล ($approverFullName) ตรวจสอบผ่านคำร้องจองสนามกีฬา/อุปกรณ์ ID: #SP-{$id} และส่งต่อให้หัวหน้าสำนักปลัดเรียบร้อย";
    } elseif ($action === 'approve_sports_final') {
        Booking::approveBookingFinal($id, $approverFullName, 'sports');
        $_SESSION['approval_msg'] = "ขั้นตอนที่ 2: หัวหน้าสำนักปลัด ($approverFullName) ยืนยันขั้นสุดท้ายคำร้องสนามกีฬา ID: #SP-{$id} พร้อมประทับลายเซ็นอิเล็กทรอนิกส์ (e-Signature) สำเร็จ";
    } elseif (in_array($action, ['approve_sports', 'reject_sports'])) {
        $act = $action === 'approve_sports' ? 'อนุมัติ' : 'ปฏิเสธ';
        $_SESSION['approval_msg'] = "ทำรายการ $act คำร้องขอเข้าใช้บริการสนามกีฬาและเบิกยืมอุปกรณ์กีฬา (ID: #SP-{$id}) เรียบร้อยแล้ว";
    } elseif (in_array($action, ['approve', 'reject'])) {
        $act = $action === 'approve' ? 'อนุมัติ' : 'ปฏิเสธ';
        $statusKey = $action === 'approve' ? 'approved' : 'rejected';
        
        // อัปเดตสถานะลง Database จริง
        Booking::updateBookingStatus($id, $statusKey, $approverFullName);

        // ข้อมูลจำลองการจองสำหรับส่งอีเมล
        $mockBooking = [
            'title' => 'ประชุมจัดทำแผนงบประมาณประจำปี 2569 (อบต.เวียง)',
            'room_name' => 'Room A - Grand Auditorium',
            'meeting_date' => '28 มิถุนายน 2569',
            'start_time' => '09:00',
            'end_time' => '12:00'
        ];
        
        EmailHelper::sendBookingStatusEmail($mockBooking, $statusKey, 'user@wiang.go.th', 'คุณใจดี พนักงานทั่วไป', 'ห้องประชุมมีการใช้งานด่วนจากผู้บริหารในวันดังกล่าว');
        EmailHelper::sendLineNotify($mockBooking, $statusKey, 'คุณใจดี พนักงานทั่วไป', 'ห้องประชุมมีการใช้งานด่วนจากผู้บริหารในวันดังกล่าว');
        
        $_SESSION['approval_msg'] = "ทำรายการ $act การจอง ID: " . htmlspecialchars($id) . " สำเร็จ พร้อมบันทึกฐานข้อมูลและส่งแจ้งเตือนเรียบร้อยแล้ว";
    }
    header("Location: approvals.php");
    exit;
}

$approvalMsg = $_SESSION['approval_msg'] ?? null;
unset($_SESSION['approval_msg']);
$lastSentEmail = $_SESSION['last_sent_email'] ?? null;
$lastSentLine = $_SESSION['last_sent_line'] ?? null;

$allUsers = Booking::getAllUsers();
$pendingUsers = array_filter($allUsers, fn($u) => $u['status'] === 'inactive');

$currentLogo = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
$currentOrgName = $_SESSION['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คิวรออนุมัติ (Approver Queue) - <?= htmlspecialchars($currentOrgName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <!-- Flatpickr CSS & JS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; }
        .sidebar { min-height: calc(100vh - 84px); background-color: #ffffff; border-right: 1px solid #e2e8f0; }
        .nav-link { font-weight: 600; color: #64748b; padding: 14px 24px; border-radius: 12px; margin-bottom: 6px; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background-color: #f1f5f9; color: #4338ca; }
        .nav-link.active { background-color: #e0e7ff; color: #4338ca; border-left: 5px solid #4338ca; }
    </style>
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
                
                <?php if ($approvalMsg): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center justify-content-between p-4 mb-4 shadow-sm" style="border-radius: 16px; border: none; background-color: #dcfce7; color: #15803d;" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-circle-check fs-3 me-3"></i>
                            <div><strong class="fw-bold">สำเร็จ!</strong> <?= htmlspecialchars($approvalMsg) ?></div>
                        </div>
                        <div class="d-flex gap-2 me-4">
                            <?php if ($lastSentEmail): ?>
                                <button type="button" class="btn btn-sm btn-custom-primary px-3 py-2 fw-semibold rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#emailSimulationModal">
                                    <i class="fa-solid fa-envelope-open-text me-2"></i> ดูตัวอย่างอีเมลที่ส่ง
                                </button>
                            <?php endif; ?>
                            <?php if ($lastSentLine): ?>
                                <button type="button" class="btn btn-sm px-3 py-2 fw-semibold rounded-3 shadow-sm text-white" style="background-color: #06c755; border: none;" data-bs-toggle="modal" data-bs-target="#lineSimulationModal">
                                    <i class="fa-brands fa-line me-2 fs-6"></i> ดูข้อความ LINE Notify
                                </button>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Email Simulation Modal -->
                <?php if ($lastSentEmail): ?>
                    <div class="modal fade" id="emailSimulationModal" tabindex="-1" aria-labelledby="emailSimulationModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow-lg">
                                <div class="modal-header bg-light border-bottom-0 py-3 px-4">
                                    <h5 class="modal-title fw-bold text-indigo" id="emailSimulationModalLabel">
                                        <i class="fa-solid fa-satellite-dish me-2 text-primary"></i> กล่องจำลองการส่งอีเมล (Mock Email Gateway)
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="mb-3 pb-3 border-bottom fs-7">
                                        <div class="mb-1"><span class="fw-bold text-muted me-2">ผู้รับ (To):</span> <span class="text-dark fw-semibold"><?= htmlspecialchars($lastSentEmail['recipient']) ?></span></div>
                                        <div class="mb-1"><span class="fw-bold text-muted me-2">หัวข้อ (Subject):</span> <span class="text-indigo fw-bold"><?= htmlspecialchars($lastSentEmail['subject']) ?></span></div>
                                        <div><span class="fw-bold text-muted me-2">วันที่ส่ง (Sent At):</span> <span class="text-secondary"><?= htmlspecialchars($lastSentEmail['sent_at']) ?></span></div>
                                    </div>
                                    <div class="bg-white p-2">
                                        <?= $lastSentEmail['body'] ?>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light border-top-0 py-3 px-4">
                                    <span class="text-muted fs-7 me-auto"><i class="fa-solid fa-circle-info me-1"></i> รองรับการเชื่อมต่อผ่าน API ของ Resend/Mailgun ในระบบโปรดักชัน</span>
                                    <button type="button" class="btn btn-secondary px-4 py-2 rounded-3 fw-semibold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- LINE Notify Simulation Modal -->
                <?php if ($lastSentLine): ?>
                    <div class="modal fade" id="lineSimulationModal" tabindex="-1" aria-labelledby="lineSimulationModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow-lg" style="background-color: #849eb2;">
                                <div class="modal-header border-bottom-0 py-3 px-4 bg-white rounded-top-4">
                                    <h5 class="modal-title fw-bold" id="lineSimulationModalLabel" style="color: #06c755;">
                                        <i class="fa-brands fa-line me-2 fs-4"></i> LINE Notify Gateway Simulation
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="text-center mb-3">
                                        <span class="badge bg-dark-subtle text-dark px-3 py-1 fs-8 rounded-pill"><?= htmlspecialchars($lastSentLine['group_name']) ?></span>
                                    </div>
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 44px; height: 44px; border: 2px solid #06c755;">
                                            <i class="fa-brands fa-line fs-3 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1 bg-white p-4 rounded-4 shadow-sm position-relative" style="max-width: 85%;">
                                            <div class="fw-bold mb-2 text-dark fs-7">LINE Notify <span class="text-muted fw-normal fs-8 ms-2"><?= htmlspecialchars($lastSentLine['sent_at']) ?></span></div>
                                            <div class="text-dark fs-6" style="white-space: pre-wrap; line-height: 1.6; font-family: 'Sarabun', sans-serif;"><?= htmlspecialchars($lastSentLine['message']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-white border-top-0 py-3 px-4 rounded-bottom-4">
                                    <span class="text-muted fs-7 me-auto"><i class="fa-solid fa-circle-info me-1"></i> ยิงผ่าน cURL ไปยัง Notify API อัตโนมัติ</span>
                                    <button type="button" class="btn btn-secondary px-4 py-2 rounded-3 fw-semibold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- SECTION 1: คิวรออนุมัติบัญชีสมาชิกใหม่ -->
                <?php if ($role !== 'Executive'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold mb-1 text-indigo"><i class="fa-solid fa-user-shield me-2"></i> คิวรออนุมัติบัญชีสมาชิกใหม่ (Member Registrations)</h4>
                            <p class="text-muted mb-0">หน้าต่างสำหรับ **Approver (ผู้อนุมัติ)** ในการพิจารณาเปิดใช้งานบัญชีผู้ใช้ภายนอกและสมาชิกทั่วไป</p>
                        </div>
                        <span class="badge bg-indigo-light text-indigo px-4 py-2 fs-6 fw-bold">รออนุมัติ <?= count($pendingUsers) ?> บัญชี</span>
                    </div>

                    <div class="card main-card bg-white p-4 mb-5 shadow-sm" style="border-radius: 24px;">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="py-3 px-3">ผู้สมัคร</th>
                                        <th scope="col" class="py-3">อีเมล (Email)</th>
                                        <th scope="col" class="py-3">แผนก / สังกัด</th>
                                        <th scope="col" class="py-3">วันที่สมัคร</th>
                                        <th scope="col" class="py-3">สถานะ</th>
                                        <th scope="col" class="py-3 text-end px-3">การตัดสินใจ (Approver Action)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($pendingUsers) > 0): ?>
                                        <?php foreach ($pendingUsers as $u): ?>
                                            <?php $nameEncoded = urlencode($u['full_name']); ?>
                                            <tr>
                                                <td class="px-3">
                                                    <div class="d-flex align-items-center">
                                                        <img src="https://ui-avatars.com/api/?name=<?= $nameEncoded ?>&background=random&color=fff" class="rounded-circle me-3 shadow-sm" width="44" height="44">
                                                        <div>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></div>
                                                            <span class="text-muted fs-7"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($u['phone'] ?? 'ไม่ระบุ') ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-muted fw-semibold"><?= htmlspecialchars($u['email']) ?></td>
                                                <td class="fw-semibold text-indigo"><?= htmlspecialchars($u['department_name'] ?? 'สมาชิกทั่วไป') ?></td>
                                                <td class="text-muted fs-7"><?= date('d/m/') . (date('Y') + 543) ?></td>
                                                <td><span class="badge bg-yellow-light text-warning px-3 py-2 fs-7 fw-bold"><i class="fa-solid fa-hourglass-half me-1"></i> รออนุมัติ (Pending)</span></td>
                                                <td class="text-end px-3">
                                                    <form method="POST" action="approvals.php" class="d-inline">
                                                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="action" value="approve_user">
                                                        <button type="submit" class="btn btn-success btn-sm px-4 py-2 rounded-3 me-1 fw-semibold shadow-sm" onclick="return confirm('คุณยืนยันที่จะอนุมัติเปิดใช้งานบัญชีนี้ใช่หรือไม่?');">
                                                            <i class="fa-solid fa-user-check me-1"></i> อนุมัติ (Approve)
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="approvals.php" class="d-inline">
                                                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="action" value="reject_user">
                                                        <button type="submit" class="btn btn-danger btn-sm px-4 py-2 rounded-3 fw-semibold shadow-sm" onclick="return confirm('คุณต้องการปฏิเสธและลบคำขอสมัครนี้ใช่หรือไม่?');">
                                                            <i class="fa-solid fa-user-xmark me-1"></i> ปฏิเสธ (Reject)
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fa-solid fa-user-check fa-3x mb-3 text-success opacity-50"></i>
                                                <div class="fw-bold fs-5">ไม่มีบัญชีสมาชิกใหม่รออนุมัติในขณะนี้</div>
                                                <p class="fs-7 mb-0">บัญชีทั้งหมดได้รับการอนุมัติเรียบร้อยแล้ว</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- SECTION 2: คิวรออนุมัติการจองห้องประชุม -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1"><i class="fa-solid fa-calendar-day me-2 text-primary"></i> คิวรออนุมัติการจองห้องประชุม (Room Booking Queue)</h4>
                        <p class="text-muted mb-0">หน้าต่างสำหรับพิจารณาคำขอจองห้องประชุมจากพนักงานในองค์กร</p>
                    </div>
                    <button class="btn btn-custom-primary px-4 py-3 rounded-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#bookingModal">
                        <i class="fa-solid fa-circle-plus me-2"></i> จองห้องประชุมใหม่
                    </button>
                </div>

                <!-- Approval Queue Table Card -->
                <div class="card main-card bg-white p-4 mb-5 shadow-sm" style="border-radius: 24px;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="py-3 px-3">ผู้จอง</th>
                                    <th scope="col" class="py-3">หัวข้อ / การประชุม</th>
                                    <th scope="col" class="py-3">ห้องประชุม</th>
                                    <th scope="col" class="py-3">วัน-เวลาที่จอง</th>
                                    <th scope="col" class="py-3">สถานะ</th>
                                    <th scope="col" class="py-3 text-end px-3">จัดการ (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="px-3">
                                        <div class="fw-bold">คุณสมศรี อนุมัติการ</div>
                                        <span class="text-muted fs-7">ฝ่าย HR</span>
                                    </td>
                                    <td class="fw-semibold">อัปเดตงานทีม Design & UX/UI</td>
                                    <td>Room C - Creative Space</td>
                                    <td><?= date('d/m/', strtotime('+1 day')) . (date('Y', strtotime('+1 day')) + 543) ?> (10:00 - 12:00)</td>
                                    <td><span class="badge bg-yellow-light px-3 py-2 fw-bold text-warning">รอตรวจสอบขั้นต้น (Pending Step 1)</span></td>
                                    <td class="text-end px-3">
                                        <?php if ($role === 'Executive'): ?>
                                            <span class="badge bg-light text-secondary px-3 py-2 fs-7 fw-semibold"><i class="fa-solid fa-eye me-1"></i> สิทธิ์ดูข้อมูล (View Only)</span>
                                        <?php else: ?>
                                            <form method="POST" action="approvals.php" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="3">
                                                <input type="hidden" name="action" value="verify_step1">
                                                <button type="submit" class="btn btn-warning btn-sm px-4 py-2 rounded-3 me-1 fw-semibold shadow-sm text-dark"><i class="fa-solid fa-user-check me-1"></i> ตรวจสอบผ่าน (ขั้น 1)</button>
                                            </form>
                                            <form method="POST" action="approvals.php" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="3">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm px-4 py-2 rounded-3 fw-semibold shadow-sm" onclick="return confirm('คุณยืนยันที่จะปฏิเสธคำขอจองห้องประชุมนี้ใช่หรือไม่?');"><i class="fa-solid fa-xmark me-1"></i> ปฏิเสธ</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3">
                                        <div class="fw-bold">คุณใจดี พนักงานทั่วไป</div>
                                        <span class="text-muted fs-7">ฝ่าย IT</span>
                                    </td>
                                    <td class="fw-semibold">ทดสอบระบบงานโปรแกรมเมอร์</td>
                                    <td>Room D - Smart Pod 1</td>
                                    <td><?= date('d/m/', strtotime('+2 days')) . (date('Y', strtotime('+2 days')) + 543) ?> (14:00 - 16:00)</td>
                                    <td><span class="badge bg-info text-white px-3 py-2 fw-bold"><i class="fa-solid fa-user-shield me-1"></i> ตรวจสอบผ่านแล้ว (รอหัวหน้าสำนักปลัด)</span></td>
                                    <td class="text-end px-3">
                                        <?php if ($role === 'Executive'): ?>
                                            <span class="badge bg-light text-secondary px-3 py-2 fs-7 fw-semibold"><i class="fa-solid fa-eye me-1"></i> สิทธิ์ดูข้อมูล (View Only)</span>
                                        <?php else: ?>
                                            <form method="POST" action="approvals.php" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="5">
                                                <input type="hidden" name="action" value="approve_final">
                                                <button type="submit" class="btn btn-success btn-sm px-4 py-2 rounded-3 me-1 fw-semibold shadow-sm"><i class="fa-solid fa-signature me-1"></i> ยืนยันขั้นสุดท้าย (e-Signature)</button>
                                            </form>
                                            <form method="POST" action="approvals.php" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="5">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm px-4 py-2 rounded-3 fw-semibold shadow-sm" onclick="return confirm('คุณยืนยันที่จะปฏิเสธคำขอจองห้องประชุมนี้ใช่หรือไม่?');"><i class="fa-solid fa-xmark me-1"></i> ปฏิเสธ</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3">
                                        <div class="fw-bold">คุณวิชุดา งานดี</div>
                                        <span class="text-muted fs-7">ฝ่ายนโยบายและแผน</span>
                                    </td>
                                    <td class="fw-semibold">ประชุมผู้เชี่ยวชาญชุมชน</td>
                                    <td>Room B - Boardroom VIP</td>
                                    <td><?= date('d/m/', strtotime('+4 days')) . (date('Y', strtotime('+4 days')) + 543) ?> (09:00 - 12:00)</td>
                                    <td><span class="badge bg-success text-white px-3 py-2 fw-bold"><i class="fa-solid fa-circle-check me-1"></i> อนุมัติสำเร็จ (e-Signed)</span></td>
                                    <td class="text-end px-3">
                                        <a href="print_booking.php?id=7&type=room" target="_blank" class="btn btn-outline-primary btn-sm px-4 py-2 rounded-3 fw-semibold shadow-sm">
                                            <i class="fa-solid fa-print me-1"></i> พิมพ์ใบขอใช้สถานที่ (e-Signature)
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECTION 3: คิวรออนุมัติการจองสนามกีฬา & ขอยืมอุปกรณ์กีฬา -->
                <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
                    <div>
                        <h4 class="fw-bold mb-1 text-success"><i class="fa-solid fa-futbol me-2"></i> คิวรออนุมัติการจองสนามกีฬา & ขอยืมอุปกรณ์กีฬา (Sports Approvals)</h4>
                        <p class="text-muted mb-0">หน้าต่างสำหรับพิจารณาอนุมัติการใช้บริการสนามกีฬา ลานกีฬาอเนกประสงค์ และเบิกยืมอุปกรณ์กีฬา</p>
                    </div>
                    <a href="sports.php" class="btn btn-success px-4 py-3 rounded-3 fw-semibold shadow-sm">
                        <i class="fa-solid fa-plus me-2"></i> จองสนามกีฬาใหม่
                    </a>
                </div>

                <!-- Sports Approval Queue Table Card -->
                <div class="card main-card bg-white p-4 mb-5 shadow-sm" style="border-radius: 24px;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="py-3 px-3">ผู้จอง / เบิกยืม</th>
                                    <th scope="col" class="py-3">สนามกีฬา / สถานที่</th>
                                    <th scope="col" class="py-3">รายการอุปกรณ์ที่ยืม (Equipments)</th>
                                    <th scope="col" class="py-3">วัน-เวลาที่ขอใช้</th>
                                    <th scope="col" class="py-3">สถานะ</th>
                                    <th scope="col" class="py-3 text-end px-3">จัดการ (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="px-3">
                                        <div class="fw-bold">คุณวิชาญ นักกีฬาดี</div>
                                        <span class="text-muted fs-7">ฝ่ายพัฒนาชุมชน</span>
                                    </td>
                                    <td class="fw-semibold text-dark">สนามฟุตบอลหญ้าเทียม อบต.เวียง</td>
                                    <td><span class="badge bg-light text-secondary border px-3 py-2 fs-7">ลูกฟุตบอล 2 ลูก, เสื้อเอี๊ยม 10 ตัว</span></td>
                                    <td><?= date('d/m/', strtotime('+1 day')) . (date('Y', strtotime('+1 day')) + 543) ?> (17:00 - 19:00)</td>
                                    <td><span class="badge bg-yellow-light px-3 py-2 fw-bold text-warning">รอตรวจสอบขั้นต้น (Pending Step 1)</span></td>
                                    <td class="text-end px-3">
                                        <?php if ($role === 'Executive'): ?>
                                            <span class="badge bg-light text-secondary px-3 py-2 fs-7 fw-semibold"><i class="fa-solid fa-eye me-1"></i> สิทธิ์ดูข้อมูล (View Only)</span>
                                        <?php else: ?>
                                            <form method="POST" action="approvals.php" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="101">
                                                <input type="hidden" name="action" value="verify_sports_step1">
                                                <button type="submit" class="btn btn-warning btn-sm px-4 py-2 rounded-3 me-1 fw-semibold shadow-sm text-dark"><i class="fa-solid fa-user-check me-1"></i> ตรวจสอบผ่าน (ขั้น 1)</button>
                                            </form>
                                            <form method="POST" action="approvals.php" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="101">
                                                <input type="hidden" name="action" value="reject_sports">
                                                <button type="submit" class="btn btn-danger btn-sm px-4 py-2 rounded-3 fw-semibold shadow-sm" onclick="return confirm('คุณยืนยันที่จะปฏิเสธคำร้องขอจองสนามกีฬาและเบิกยืมอุปกรณ์นี้ใช่หรือไม่?');"><i class="fa-solid fa-xmark me-1"></i> ปฏิเสธ</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3">
                                        <div class="fw-bold">คุณนารี สุขสันต์</div>
                                        <span class="text-muted fs-7">ฝ่ายสวัสดิการสังคม</span>
                                    </td>
                                    <td class="fw-semibold text-dark">คอร์ทแบดมินตัน มาตรฐาน 1-2</td>
                                    <td><span class="badge bg-light text-secondary border px-3 py-2 fs-7">ไม่ยืมอุปกรณ์ (นำมาเอง)</span></td>
                                    <td><?= date('d/m/', strtotime('+3 days')) . (date('Y', strtotime('+3 days')) + 543) ?> (18:00 - 20:00)</td>
                                    <td><span class="badge bg-info text-white px-3 py-2 fw-bold"><i class="fa-solid fa-user-shield me-1"></i> ตรวจสอบผ่านแล้ว (รอหัวหน้าสำนักปลัด)</span></td>
                                    <td class="text-end px-3">
                                        <?php if ($role === 'Executive'): ?>
                                            <span class="badge bg-light text-secondary px-3 py-2 fs-7 fw-semibold"><i class="fa-solid fa-eye me-1"></i> สิทธิ์ดูข้อมูล (View Only)</span>
                                        <?php else: ?>
                                            <form method="POST" action="approvals.php" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="102">
                                                <input type="hidden" name="action" value="approve_sports_final">
                                                <button type="submit" class="btn btn-success btn-sm px-4 py-2 rounded-3 me-1 fw-semibold shadow-sm"><i class="fa-solid fa-signature me-1"></i> ยืนยันขั้นสุดท้าย (e-Signature)</button>
                                            </form>
                                            <form method="POST" action="approvals.php" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="102">
                                                <input type="hidden" name="action" value="reject_sports">
                                                <button type="submit" class="btn btn-danger btn-sm px-4 py-2 rounded-3 fw-semibold shadow-sm" onclick="return confirm('คุณยืนยันที่จะปฏิเสธคำร้องขอจองสนามกีฬาและเบิกยืมอุปกรณ์นี้ใช่หรือไม่?');"><i class="fa-solid fa-xmark me-1"></i> ปฏิเสธ</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3">
                                        <div class="fw-bold">คุณกนก บริการรวดเร็ว</div>
                                        <span class="text-muted fs-7">ฝ่ายบริหารงานทั่วไป</span>
                                    </td>
                                    <td class="fw-semibold text-dark">สนามฟุตซอลอเนกประสงค์ ในร่ม</td>
                                    <td><span class="badge bg-light text-secondary border px-3 py-2 fs-7">ลูกฟุตซอล 2 ลูก, นกหวีด 1 อัน</span></td>
                                    <td><?= date('d/m/', strtotime('+5 days')) . (date('Y', strtotime('+5 days')) + 543) ?> (16:00 - 18:00)</td>
                                    <td><span class="badge bg-success text-white px-3 py-2 fw-bold"><i class="fa-solid fa-circle-check me-1"></i> อนุมัติสำเร็จ (e-Signed)</span></td>
                                    <td class="text-end px-3">
                                        <a href="print_booking.php?id=103&type=sports" target="_blank" class="btn btn-outline-primary btn-sm px-4 py-2 rounded-3 fw-semibold shadow-sm">
                                            <i class="fa-solid fa-print me-1"></i> พิมพ์ใบขอใช้สถานที่ (e-Signature)
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- 🏷️ MODAL: Form จองห้องประชุม -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg" style="border-radius: 24px; border: none;">
                <div class="modal-header bg-indigo-light p-4" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <h5 class="modal-title fw-bold text-indigo" id="bookingModalLabel"><i class="fa-solid fa-calendar-plus me-2"></i> เพิ่มรายการจองห้องประชุม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="booking_store.php" method="POST" class="row g-3">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">เลือกห้องประชุม <span class="text-danger">*</span></label>
                            <select class="form-select p-3" name="room_id" required>
                                <option value="">-- กรุณาเลือกห้องประชุม --</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['room_name']) ?> (<?= htmlspecialchars($r['location']) ?> - รองรับ <?= $r['capacity'] ?> คน)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">วันที่จอง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3 datepicker bg-white" name="meeting_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">เวลาเริ่มต้น <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3 timepicker bg-white" name="start_time" value="09:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">เวลาสิ้นสุด <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3 timepicker bg-white" name="end_time" value="11:30" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">หัวข้อการประชุม / Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control p-3" name="title" placeholder="ระบุหัวข้อการประชุม" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">จำนวนผู้เข้าร่วม (คน)</label>
                            <input type="number" class="form-control p-3" name="attendee_count" placeholder="ตัวอย่าง: 12">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">วาระการประชุม / รายละเอียดเพิ่มเติม (Agenda)</label>
                            <textarea class="form-control p-3" name="agenda" rows="3" placeholder="ระบุวาระหรือความต้องการพิเศษ"></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light px-4 py-3 me-2 rounded-3 fw-semibold" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-custom-primary px-5 py-3 rounded-3 fw-semibold"><i class="fa-solid fa-floppy-disk me-2"></i> บันทึกการจอง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(".datepicker", {
                    locale: "th",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d F Y",
                    minDate: "today",
                    onReady: function(selectedDates, dateStr, instance) {
                        function updateBuddhistYear() {
                            if (instance.currentYearElement) {
                                instance.currentYearElement.value = instance.currentYear + 543;
                            }
                            if (instance.yearElements && instance.yearElements[0]) {
                                instance.yearElements[0].value = instance.currentYear + 543;
                            }
                            if (instance.altInput && instance.selectedDates[0]) {
                                const yearEl = instance.altInput;
                                const d = instance.selectedDates[0];
                                const thMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                                yearEl.value = d.getDate() + ' ' + thMonths[d.getMonth()] + ' ' + (d.getFullYear() + 543);
                            }
                        }
                        updateBuddhistYear();
                        if (instance.calendarContainer) {
                            instance.calendarContainer.addEventListener('click', function() {
                                setTimeout(updateBuddhistYear, 10);
                            });
                        }
                    },
                    onOpen: function(selectedDates, dateStr, instance) {
                        setTimeout(function() {
                            if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                            if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                        }, 10);
                    },
                    onMonthChange: function(selectedDates, dateStr, instance) {
                        setTimeout(function() {
                            if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                            if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                        }, 10);
                    },
                    onYearChange: function(selectedDates, dateStr, instance) {
                        setTimeout(function() {
                            if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                            if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                        }, 10);
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        setTimeout(function() {
                            if (instance.altInput && instance.selectedDates[0]) {
                                const yearEl = instance.altInput;
                                const d = instance.selectedDates[0];
                                const thMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                                yearEl.value = d.getDate() + ' ' + thMonths[d.getMonth()] + ' ' + (d.getFullYear() + 543);
                            }
                            if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                            if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                        }, 10);
                    }
                });

                flatpickr(".timepicker", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true
                });
            }
        });
    </script>
</body>
</html>
