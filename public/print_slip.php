<?php
require_once __DIR__ . '/../routes/web.php';
use App\Models\Booking;

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 1012;
$rooms = Booking::getAllRooms();
// เลือกห้องจำลองตาม ID
$selectedRoom = $rooms[($bookingId % count($rooms))] ?? $rooms[0];

$currentUser = $_SESSION['user'] ?? [
    'full_name' => 'คุณใจดี พนักงานทั่วไป',
    'department_name' => 'กองช่าง (อบต.เวียง)',
    'email' => 'user@wiang.go.th',
    'phone' => '0834567890'
];

$meetingTitle = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : 'ประชุมทบทวนแผนปฏิบัติการและงบประมาณประจำปี 2569';
$meetingDate = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '28 มิถุนายน 2569';
$meetingTime = isset($_GET['time']) ? htmlspecialchars($_GET['time']) : '09:00 - 12:00';
$attendees = isset($_GET['attendees']) ? (int)$_GET['attendees'] : 15;
$bookingStatus = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : 'confirmed';

$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode("https://github.com/jetci/Room/booking/" . $bookingId);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบยืนยันการจองห้องประชุม - #<?= $bookingId ?> (อบต.เวียง)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
        }
        .slip-container {
            max-width: 820px;
            margin: 40px auto;
            background: #ffffff;
            padding: 56px 64px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .header-box {
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 28px;
            margin-bottom: 36px;
        }
        .garuda-logo {
            width: 80px;
            height: auto;
            margin-bottom: 16px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 110px;
            color: #f1f5f9;
            opacity: 0.4;
            z-index: 0;
            pointer-events: none;
            font-weight: 800;
            white-space: nowrap;
        }
        .content-box {
            position: relative;
            z-index: 1;
        }
        .table-custom td {
            padding: 12px 16px;
            font-size: 16px;
            border-color: #e2e8f0;
        }
        .table-custom td:first-child {
            width: 35%;
            font-weight: 600;
            color: #64748b;
            background-color: #f8fafc;
        }
        .signature-box {
            margin-top: 64px;
            page-break-inside: avoid;
        }
        @media print {
            body {
                background-color: #ffffff !important;
                margin: 0;
                padding: 0;
            }
            .slip-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 auto;
                padding: 20px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="slip-container position-relative">
            <!-- Watermark -->
            <div class="watermark">CONFIRMED</div>

            <div class="content-box">
                <!-- Action Buttons (No Print) -->
                <div class="d-flex justify-content-between align-items-center mb-4 no-print bg-light p-3 rounded-4 border">
                    <a href="javascript:history.back()" class="btn btn-secondary px-4 py-2 rounded-3 fw-semibold"><i class="fa-solid fa-arrow-left me-2"></i> ย้อนกลับ</a>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm" style="background-color: #4338ca; border: none;">
                            <i class="fa-solid fa-print me-2"></i> สั่งพิมพ์ใบยืนยันการจอง (Print Slip)
                        </button>
                    </div>
                </div>

                <!-- Slip Header -->
                <div class="header-box text-center">
                    <!-- ตราครุฑจำลอง / ตรา อบต. -->
                    <div class="mb-3">
                        <i class="fa-solid fa-building-flag fa-4x text-indigo" style="color: #4338ca;"></i>
                    </div>
                    <h2 class="fw-bold mb-1" style="color: #1e293b;">องค์การบริหารส่วนตำบลเวียง</h2>
                    <h5 class="fw-semibold text-muted mb-3">ใบยืนยันการจองห้องประชุม (Smart Room Booking e-Memo)</h5>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
                        <div class="text-start">
                            <span class="text-muted fw-semibold fs-7">เลขที่อ้างอิงการจอง:</span>
                            <h4 class="fw-bold text-indigo mb-0">MEMO-2569-<?= $bookingId ?></h4>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fw-semibold fs-7">สถานะการจอง:</span>
                            <div class="mt-1">
                                <span class="badge bg-success px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-circle-check me-2"></i> ได้รับการอนุมัติ (Approved)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slip Details -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-calendar-day me-2 text-indigo"></i> ข้อมูลและรายละเอียดการประชุม</h5>
                    <table class="table table-bordered table-custom mb-4">
                        <tbody>
                            <tr>
                                <td>หัวข้อ / วาระการประชุม</td>
                                <td class="fw-bold text-dark"><?= $meetingTitle ?></td>
                            </tr>
                            <tr>
                                <td>ห้องประชุมที่จอง</td>
                                <td class="fw-bold text-indigo"><?= htmlspecialchars($selectedRoom['room_name']) ?></td>
                            </tr>
                            <tr>
                                <td>สถานที่ตั้ง</td>
                                <td><?= htmlspecialchars($selectedRoom['location']) ?></td>
                            </tr>
                            <tr>
                                <td>วันที่ประชุม</td>
                                <td class="fw-bold text-dark"><?= $meetingDate ?></td>
                            </tr>
                            <tr>
                                <td>เวลาที่ใช้ห้อง</td>
                                <td class="fw-bold text-primary"><?= $meetingTime ?> น.</td>
                            </tr>
                            <tr>
                                <td>จำนวนผู้เข้าร่วม</td>
                                <td><?= $attendees ?> ท่าน (ความจุห้อง <?= htmlspecialchars($selectedRoom['capacity']) ?> ท่าน)</td>
                            </tr>
                            <tr>
                                <td>อุปกรณ์ที่จัดเตรียม</td>
                                <td><?= htmlspecialchars($selectedRoom['equipment_summary'] ?? 'โปรเจกเตอร์, ไมค์ไร้สาย, ระบบเสียง') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-user-tie me-2 text-indigo"></i> ข้อมูลผู้ขอจอง</h5>
                    <table class="table table-bordered table-custom mb-0">
                        <tbody>
                            <tr>
                                <td>ผู้จอง / สมาชิก</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($currentUser['full_name'] ?? 'คุณใจดี พนักงานทั่วไป') ?></td>
                            </tr>
                            <tr>
                                <td>สังกัด / แผนก</td>
                                <td><?= htmlspecialchars($currentUser['department_name'] ?? 'กองช่าง (อบต.เวียง)') ?></td>
                            </tr>
                            <tr>
                                <td>ข้อมูลติดต่อ</td>
                                <td>อีเมล: <?= htmlspecialchars($currentUser['email'] ?? 'user@wiang.go.th') ?> | โทร: <?= htmlspecialchars($currentUser['phone'] ?? '0834567890') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer & Signatures -->
                <div class="row signature-box pt-4 border-top">
                    <div class="col-6 text-center">
                        <p class="mb-5 text-muted fs-7">ผู้ขอจองห้องประชุม</p>
                        <p class="fw-bold mb-1">( <?= htmlspecialchars($currentUser['full_name'] ?? 'คุณใจดี พนักงานทั่วไป') ?> )</p>
                        <p class="text-muted fs-7 mb-0"><?= htmlspecialchars($currentUser['department_name'] ?? 'กองช่าง (อบต.เวียง)') ?></p>
                    </div>
                    <div class="col-6 text-center">
                        <p class="mb-5 text-muted fs-7">เจ้าหน้าที่ผู้พิจารณาอนุมัติ</p>
                        <p class="fw-bold mb-1">( คุณสมศรี อนุมัติการ )</p>
                        <p class="text-muted fs-7 mb-0">ผู้อำนวยการกองบริหารส่วนสถานที่ (Approver)</p>
                    </div>
                </div>

                <!-- QR Code Verification -->
                <div class="d-flex align-items-center justify-content-center mt-5 p-4 bg-light rounded-4 border">
                    <img src="<?= $qrUrl ?>" alt="QR Code" class="me-4 rounded-3 border bg-white p-1" style="width: 100px; height: 100px;">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">ตรวจสอบยืนยันเอกสาร (e-Verification)</h6>
                        <p class="text-muted fs-7 mb-0">สแกน QR Code เพื่อตรวจสอบความถูกต้องของสลิปและสถานะการจองล่าสุดบนระบบ Smart Room Booking ขององค์การบริหารส่วนตำบลเวียง</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
