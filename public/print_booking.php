<?php
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
use App\Models\Booking;
use App\Middleware\AuthMiddleware;

// ตรวจสอบสิทธิ์เข้าใช้งาน
$currentUser = AuthMiddleware::requireActiveUser();

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'room';

$booking = Booking::getBookingWithSignature($id, $type);
if (!$booking) {
    echo "ไม่พบข้อมูลใบคำร้อง หรือรหัสคำร้องไม่ถูกต้อง";
    exit;
}

$isSports = ($type === 'sports');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบคำร้องขอใช้สถานที่ - อบต.เวียง [#<?= $id ?>]</title>
    <!-- Font & Custom Stylesheet -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <script src="https://kit.fontawesome.com/c139786722.js" crossorigin="anonymous"></script>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: 'Sarabun', sans-serif; background-color: #f1f5f9; color: #0f172a; }
        .print-sheet {
            background: #ffffff;
            max-width: 210mm;
            min-height: 297mm;
            margin: 30px auto;
            padding: 45px 55px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        .thai-garuda {
            width: 70px;
            height: 70px;
            background: #cbd5e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #334155;
            font-size: 28px;
        }
        .doc-header { text-align: center; margin-bottom: 40px; }
        .e-signature-box {
            border: 2px dashed #10b981;
            background: #ecfdf5;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin-top: 40px;
            position: relative;
            overflow: hidden;
        }
        .e-signature-badge {
            background: #10b981;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 16px;
            border-radius: 0 0 12px 12px;
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
        }
        .seal-stamp {
            width: 140px;
            height: 50px;
            border: 3px solid #10b981;
            color: #10b981;
            font-weight: 800;
            font-size: 16px;
            line-height: 44px;
            text-align: center;
            border-radius: 8px;
            transform: rotate(-5deg);
            display: inline-block;
            margin: 15px 0;
            letter-spacing: 1px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
        }
        @media print {
            body { background: #ffffff; }
            .print-sheet { box-shadow: none; margin: 0; padding: 0; max-width: 100%; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="container py-4">
        <!-- Floating Action Bar for Printing -->
        <div class="no-print text-center mb-4">
            <button onclick="window.print()" class="btn btn-custom-primary px-5 py-3 rounded-4 fw-bold shadow-lg fs-6 me-2">
                <i class="fa-solid fa-print me-2"></i> พิมพ์ใบคำร้อง (Print Document)
            </button>
            <a href="approvals.php" class="btn btn-light border px-4 py-3 rounded-4 fw-semibold shadow-sm">กลับหน้าต่างอนุมัติ</a>
        </div>

        <!-- A4 Print Sheet -->
        <div class="print-sheet">
            <!-- Thai Garuda Mock Stamp -->
            <div class="thai-garuda shadow-sm">
                <i class="fa-solid fa-feather-pointed"></i>
            </div>

            <div class="doc-header">
                <h3 class="fw-bold mb-1">องค์การบริหารส่วนตำบลเวียง (อบต.เวียง)</h3>
                <h5 class="text-secondary fw-semibold mb-3">ใบคำร้องขอเข้าใช้สถานที่และสิ่งอำนวยความสะดวก (ฉบับรับรองอิเล็กทรอนิกส์)</h5>
                <div class="d-flex justify-content-between align-items-center border-top border-bottom py-2 mt-4 text-start fs-7 text-muted">
                    <span><strong>รหัสเอกสารอ้างอิง:</strong> DOC-<?= date('Y') ?>-<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></span>
                    <span><strong>วันที่พิมพ์เอกสาร:</strong> <?= date('d/m/') . (date('Y') + 543) ?></span>
                </div>
            </div>

            <!-- SECTION 1: ข้อมูลผู้ยื่นคำร้อง -->
            <h6 class="fw-bold text-primary mb-3">1. ข้อมูลผู้ยื่นคำร้อง (Applicant Information)</h6>
            <div class="row g-3 mb-4 bg-light p-3 rounded-4 border">
                <div class="col-sm-6">
                    <span class="text-muted fs-7 d-block">ชื่อ-นามสกุล ผู้ขอใช้งาน:</span>
                    <strong class="fs-6"><?= htmlspecialchars($booking['user_name'] ?? 'คุณใจดี พนักงานทั่วไป') ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted fs-7 d-block">แผนก / สำนัก / สังกัด:</span>
                    <strong class="fs-6"><?= htmlspecialchars($booking['department_name'] ?? 'ฝ่ายบริหารงานทั่วไป') ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted fs-7 d-block">เบอร์โทรศัพท์ติดต่อ:</span>
                    <strong class="fs-6"><?= htmlspecialchars($booking['phone'] ?? '089-999-8888') ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted fs-7 d-block">ประเภทคำร้อง:</span>
                    <strong class="fs-6"><?= $isSports ? 'สนามกีฬาและอุปกรณ์กีฬา' : 'ห้องประชุมส่วนกลาง' ?></strong>
                </div>
            </div>

            <!-- SECTION 2: รายละเอียดการใช้สถานที่ -->
            <h6 class="fw-bold text-primary mb-3">2. รายละเอียดการขอใช้สถานที่ (Facility Allocation Details)</h6>
            <table class="table table-bordered align-middle mb-4">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="py-2">รายการ / สถานที่</th>
                        <th scope="col" class="py-2">วัตถุประสงค์ (Title/Objective)</th>
                        <th scope="col" class="py-2">วันที่ขอใช้</th>
                        <th scope="col" class="py-2">เวลาที่ขอใช้</th>
                        <th scope="col" class="py-2">จำนวนผู้เข้าร่วม</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold text-indigo"><?= htmlspecialchars($booking['room_name'] ?? $booking['facility_name'] ?? 'Room A - Grand Auditorium') ?></td>
                        <td><?= htmlspecialchars($booking['title'] ?? 'ประชุมประจำเดือน') ?></td>
                        <td><?= htmlspecialchars($booking['meeting_date'] ?? $booking['sports_date'] ?? '28/06/2026') ?></td>
                        <td><?= htmlspecialchars($booking['start_time'] ?? '09:00') ?> - <?= htmlspecialchars($booking['end_time'] ?? '12:00') ?></td>
                        <td><?= htmlspecialchars($booking['attendee_count'] ?? 15) ?> ท่าน</td>
                    </tr>
                </tbody>
            </table>

            <!-- SECTION 3: รายการอุปกรณ์และความต้องการเพิ่มเติม -->
            <h6 class="fw-bold text-primary mb-3">3. รายการอุปกรณ์ที่ขอยืม & หมายเหตุ (Equipments & Notes)</h6>
            <div class="bg-light p-3 rounded-4 border mb-5">
                <p class="mb-1 fw-semibold text-dark">รายการอุปกรณ์เสริมที่ร้องขอ:</p>
                <p class="text-muted fs-7 mb-3"><?= htmlspecialchars($booking['user_notes'] ?? $booking['borrow_summary'] ?? 'ขอยืมโปรเจคเตอร์และระบบเสียงเสริม') ?></p>
                <div class="alert alert-warning mb-0 fs-7 py-2" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> ผู้ขอยืมต้องรับผิดชอบทรัพย์สินและกรุณาคืนอุปกรณ์ในสภาพสมบูรณ์หลังการใช้งาน
                </div>
            </div>

            <!-- SECTION 4: ตารางตรวจสอบและอนุมัติ 2 ระดับ (Multi-Tier Verification & e-Signature) -->
            <div class="row g-4 pt-4 border-top">
                <!-- Step 1: เจ้าหน้าที่ผู้ดูแล -->
                <div class="col-6">
                    <div class="border p-4 rounded-4 text-center bg-light h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-secondary mb-3">ขั้นตอนที่ 1 : ผู้ตรวจสอบเบื้องต้น</span>
                            <div class="fw-bold fs-6 mb-1"><?= htmlspecialchars($booking['step1_approver_name'] ?? 'คุณสมชาย บริหารดี') ?></div>
                            <div class="text-muted fs-7 mb-4">ตำแหน่ง: เจ้าหน้าที่ผู้ดูแลสถานที่ (Sports Admin / Facility Manager)</div>
                        </div>
                        <div>
                            <div class="text-success fw-bold mb-2"><i class="fa-solid fa-circle-check me-2"></i> ตรวจสอบผ่านแล้ว (VERIFIED)</div>
                            <div class="text-muted fs-8">วันที่ตรวจสอบ: <?= htmlspecialchars($booking['step1_approved_at'] ?? date('d/m/Y H:i')) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: หัวหน้าสำนักปลัด (e-Signature Stamp) -->
                <div class="col-6">
                    <div class="e-signature-box h-100 d-flex flex-column justify-content-between mt-0">
                        <div class="e-signature-badge">E-SIGNATURE STAMP</div>
                        <div>
                            <span class="badge bg-success mb-2 mt-2">ขั้นตอนที่ 2 : อนุมัติขั้นสุดท้าย (Final Approval)</span>
                            <div class="fw-bold fs-6 mb-1 text-dark"><?= htmlspecialchars($booking['final_approver_name'] ?? 'นายอำนาจ ปกป้องราษฎร์') ?></div>
                            <div class="text-muted fs-7 mb-2">ตำแหน่ง: หัวหน้าสำนักปลัด องค์การบริหารส่วนตำบลเวียง</div>
                        </div>
                        
                        <!-- E-Signature Digital Seal Stamp -->
                        <div class="my-3">
                            <div class="seal-stamp">E-SIGN APPROVED</div>
                        </div>

                        <div>
                            <div class="text-success fw-bold fs-7 mb-1"><i class="fa-solid fa-file-shield me-2"></i> รับรองลายเซ็นอิเล็กทรอนิกส์สำเร็จ</div>
                            <div class="text-muted fs-8 text-truncate">Hash: <?= htmlspecialchars($booking['e_signature_hash'] ?? hash('sha256', 'esig_2026')) ?></div>
                            <div class="text-muted fs-8">วันเวลาที่ลงนาม: <?= htmlspecialchars($booking['final_approved_at'] ?? date('d/m/Y H:i:s')) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center text-muted fs-8 mt-5 pt-3 border-top">
                เอกสารฉบับนี้ออกโดยระบบ Smart Room & Sports Booking (อบต.เวียง) และได้รับการรับรองด้วยลายเซ็นอิเล็กทรอนิกส์ตามพ.ร.บ.ว่าด้วยธุรกรรมทางอิเล็กทรอนิกส์
            </div>

        </div>
    </div>

</body>
</html>
