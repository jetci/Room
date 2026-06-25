<?php
require_once __DIR__ . '/../routes/web.php';
// หากล็อกอินอยู่แล้ว ให้พากระโดดไปหน้า Dashboard ทันที
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Room Booking - องค์การบริหารส่วนตำบลเวียง (อบต.เวียง)</title>
    <!-- Bootstrap 5 & Google Fonts & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>
    <style>
        /* ==========================================================================
           ✨ PREMIUM MINIMALIST & SOFT FULLCALENDAR (Index Live View)
           ========================================================================== */
        .fc { font-family: 'Noto Sans Thai', sans-serif !important; }
        .fc .fc-toolbar-title { font-size: 1.35rem !important; font-weight: 700 !important; color: #1e293b !important; letter-spacing: -0.3px !important; }
        
        /* ปุ่ม Navigation (เดือน, สัปดาห์, วัน, วันนี้) */
        .fc .fc-button-primary { 
            background-color: #f8fafc !important; 
            color: #475569 !important; 
            border: 1px solid #e2e8f0 !important; 
            border-radius: 12px !important; 
            padding: 8px 16px !important; 
            font-weight: 600 !important; 
            font-size: 0.9rem !important;
            text-transform: capitalize !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03) !important;
            transition: all 0.2s ease-in-out !important;
        }
        .fc .fc-button-primary:hover { background-color: #f1f5f9 !important; color: #0f172a !important; border-color: #cbd5e1 !important; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05) !important; }
        .fc .fc-button-primary:not(:disabled):active,
        .fc .fc-button-primary:not(:disabled).fc-button-active { 
            background-color: #4338ca !important; 
            color: #ffffff !important; 
            border-color: #4338ca !important; 
            box-shadow: 0 6px 18px rgba(67, 56, 202, 0.25) !important;
        }
        .fc .fc-button-primary:focus { box-shadow: none !important; }

        /* ลบขอบตารางแข็งกระด้าง และเปลี่ยนสีเส้นตารางให้นุ่มนวลสบายตา */
        .fc-theme-standard td, .fc-theme-standard th { border-color: #f1f5f9 !important; }
        .fc-theme-standard .fc-scrollgrid { border: 1px solid #f1f5f9 !important; border-radius: 20px !important; overflow: hidden !important; box-shadow: 0 4px 15px rgba(0,0,0,0.02) !important; }
        
        /* ส่วนหัวตารางวัน (จันทร์-อาทิตย์) */
        .fc .fc-col-header-cell { padding: 14px 0 !important; background-color: #f8fafc !important; font-weight: 600 !important; color: #64748b !important; font-size: 0.95rem !important; border-bottom: 1px solid #e2e8f0 !important; }
        .fc .fc-col-header-cell-cushion { color: #475569 !important; font-weight: 600 !important; text-decoration: none !important; }
        
        /* ตัวเลขวันที่ในช่องตาราง */
        .fc .fc-daygrid-day-number { color: #64748b !important; font-weight: 500 !important; font-size: 0.9rem !important; padding: 10px !important; text-decoration: none !important; }
        .fc .fc-daygrid-day:hover .fc-daygrid-day-number { color: #4338ca !important; font-weight: 600 !important; }

        /* ไฮไลท์วันปัจจุบัน (Today View) ให้นุ่มนวลด้วยสีเขียวมิ้นต์พาสเทล */
        .fc .fc-day-today { background-color: #f0fdf4 !important; }
        .fc .fc-day-today .fc-daygrid-day-number { color: #16a34a !important; font-weight: 700 !important; font-size: 1rem !important; }

        /* สไตล์อีเวนต์ในปฏิทิน (Events) */
        .fc-event { 
            border-radius: 8px !important; 
            border: none !important; 
            padding: 5px 10px !important; 
            font-size: 0.82rem !important; 
            font-weight: 600 !important; 
            margin: 1px 4px 3px 4px !important; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.04) !important; 
            transition: transform 0.2s ease, box-shadow 0.2s ease !important; 
        }
        .fc-event:hover { transform: translateY(-1px) !important; box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important; cursor: pointer; }
        .fc-event .fc-event-main { color: #ffffff !important; font-weight: 600 !important; letter-spacing: -0.1px; }

        /* ปรับพื้นหลังตารางให้ขาวสะอาด */
        .fc .fc-view-harness { background-color: #ffffff !important; border-radius: 20px !important; }

        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            color: #ffffff;
            display: flex;
            flex-direction: column;
        }
        /* Navbar */
        .navbar-custom {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* Hero Section */
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }
        .bg-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, rgba(0,0,0,0) 70%);
            top: -100px;
            right: -100px;
            z-index: 0;
            pointer-events: none;
        }
        .hero-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            backdrop-filter: blur(16px);
            padding: 64px 48px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.4);
            position: relative;
            z-index: 1;
        }
        .btn-custom-glow {
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            color: white;
            border: none;
            padding: 16px 36px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }
        .btn-custom-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.6);
            color: white;
        }
        .btn-custom-outline {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 16px 36px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-custom-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            color: white;
        }
        /* Features */
        .feature-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 36px 28px;
            height: 100%;
            transition: all 0.3s ease;
        }
        .feature-box:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3 sticky-top">
        <div class="container px-4">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php">
                <span class="badge bg-indigo-light text-indigo px-4 py-2 rounded-pill fs-7 fw-bold" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc;">
                    <i class="fa-solid fa-spa me-2"></i> องค์การบริหารส่วนตำบลเวียง (อบต.เวียง)
                </span>
            </a>
            <div class="d-flex align-items-center">
                <a href="login.php" class="btn btn-sm btn-custom-outline px-4 py-2 me-3 fs-7">เข้าสู่ระบบ</a>
                <a href="register.php" class="btn btn-sm btn-custom-glow px-4 py-2 fs-7"><i class="fa-solid fa-user-plus me-2"></i>สมัครสมาชิก</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="bg-glow"></div>
        <div class="container px-4">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-10 text-center">
                    <div class="hero-card">
                        
                        <h1 class="fw-bold mb-2 lh-base" style="font-size: 3rem; letter-spacing: -0.5px;">
                            ระบบบริหารจัดการและจองห้องประชุม อบต.เวียง
                        </h1>
                        <div class="mb-3" style="font-size: 1.35rem; font-weight: 600; letter-spacing: 1.5px; background: linear-gradient(135deg, #a5b4fc 0%, #818cf8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            SMART ROOM BOOKING SYSTEM
                        </div>

                        <!-- Live Calendar Section (Minimalist Premium Design) -->
                        <div class="live-calendar-wrapper mt-4 p-4 p-md-5 text-start bg-white" style="border-radius: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                                <div>
                                    <span class="badge bg-green-light text-success px-3 py-2 rounded-pill fs-7 mb-2 fw-semibold" style="background: #dcfce7; color: #15803d;">
                                        <i class="fa-solid fa-circle-dot fa-beat me-2"></i> Real-time Live Calendar
                                    </span>
                                    <h3 class="fw-bold mb-1 text-dark" style="color: #1e293b !important;">ตารางการใช้ห้องประชุม (Live Calendar)</h3>
                                    <p class="text-muted fs-7 mb-0">ตรวจสอบสถานะการจองห้องประชุมของ อบต.เวียง ได้ทันทีตลอด 24 ชั่วโมง</p>
                                </div>
                                <div class="mt-3 mt-md-0">
                                    <a href="login.php" class="btn btn-custom-glow btn-sm px-4 py-2"><i class="fa-solid fa-circle-plus me-2"></i>จองห้องประชุม</a>
                                </div>
                            </div>
                            <div id="live-index-calendar"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted border-top" style="border-color: rgba(255,255,255,0.05) !important;">
        <div class="container">
            <p class="fs-7 mb-0">© <?= date('Y') ?> องค์การบริหารส่วนตำบลเวียง (อบต.เวียง). All rights reserved. | Smart Meeting Room Platform</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('live-index-calendar');
            if (calendarEl) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'th',
                    initialView: 'dayGridMonth',
                    contentHeight: 'auto',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์', day: 'วัน' },
                    datesSet: function (info) {
                        const titleEl = document.querySelector('.fc-toolbar-title');
                        if (titleEl && info.view && info.view.title) {
                            let text = info.view.title;
                            text = text.replace(/\b(20\d{2})\b/g, match => parseInt(match) + 543);
                            titleEl.textContent = text;
                        }
                    },
                    events: [
                        { title: 'ห้องสภา: ประชุมสภา อบต.เวียง ประจำเดือน', start: '2026-06-25T09:00:00', end: '2026-06-25T12:00:00', backgroundColor: '#4338ca', borderColor: '#4338ca' },
                        { title: 'ห้องเล็ก: กองคลัง สรุปงบประจำปี', start: '2026-06-25T13:30:00', end: '2026-06-25T16:30:00', backgroundColor: '#10b981', borderColor: '#10b981' },
                        { title: 'ห้องสภา: ประชุมประชาคมหมู่บ้าน', start: '2026-06-28T09:00:00', end: '2026-06-28T12:00:00', backgroundColor: '#f59e0b', borderColor: '#f59e0b' },
                        { title: 'ห้องประชุม 2: อบรมส่งเสริมอาชีพชุมชน', start: '2026-06-29T13:00:00', end: '2026-06-29T16:00:00', backgroundColor: '#4338ca', borderColor: '#4338ca' }
                    ]
                });
                calendar.render();
            }
        });
    </script>
</body>
</html>
