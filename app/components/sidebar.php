<?php
$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    $currentUser = [
        'full_name' => 'ผู้เยี่ยมชม (Guest)',
        'role_name' => 'Guest',
        'email' => 'guest@wiang.go.th',
        'status' => 'guest'
    ];
}
$role = $currentUser['role_name'] ?? $currentUser['role'] ?? 'Guest';
$userStatus = $currentUser['status'] ?? 'active';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Navigation Component -->
<div class="col-lg-2 d-none d-lg-block sidebar py-4 px-3">
    <ul class="nav flex-column mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <i class="fa-solid fa-chart-pie me-3"></i> หน้าหลัก (Dashboard)
            </a>
        </li>
        <li class="nav-item">
            <?php if ($userStatus === 'inactive'): ?>
                <a class="nav-link text-muted" href="#" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถใช้งานเมนูจองได้ในขณะนี้'); return false;"><i class="fa-solid fa-calendar-days me-3 text-secondary"></i> ค้นหา & จองห้อง <i class="fa-solid fa-lock ms-2 text-warning"></i></a>
            <?php else: ?>
                <a class="nav-link <?= $currentPage === 'search.php' ? 'active' : '' ?>" href="search.php"><i class="fa-solid fa-calendar-days me-3"></i> ค้นหา & จองห้อง</a>
            <?php endif; ?>
        </li>
        <li class="nav-item">
            <?php if ($userStatus === 'inactive'): ?>
                <a class="nav-link text-muted" href="#" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถใช้งานเมนูจองได้ในขณะนี้'); return false;"><i class="fa-solid fa-futbol me-3 text-secondary"></i> จองสนามกีฬา & อุปกรณ์ <i class="fa-solid fa-lock ms-2 text-warning"></i></a>
            <?php else: ?>
                <a class="nav-link <?= $currentPage === 'sports.php' ? 'active' : '' ?>" href="sports.php"><i class="fa-solid fa-futbol me-3"></i> จองสนามกีฬา & อุปกรณ์</a>
            <?php endif; ?>
        </li>
        <li class="nav-item">
            <?php if ($userStatus === 'inactive'): ?>
                <a class="nav-link text-muted" href="#" onclick="alert('บัญชีของคุณอยู่ระหว่างรอการอนุมัติ ไม่สามารถใช้งานเมนูจองได้ในขณะนี้'); return false;"><i class="fa-solid fa-calendar-view-month me-3 text-secondary"></i> ปฏิทินการจอง <i class="fa-solid fa-lock ms-2 text-warning"></i></a>
            <?php else: ?>
                <a class="nav-link <?= $currentPage === 'calendar.php' ? 'active' : '' ?>" href="calendar.php"><i class="fa-solid fa-calendar-view-month me-3"></i> ปฏิทินการจอง</a>
            <?php endif; ?>
        </li>

        <?php if ($role === 'Admin' || $role === 'Approver' || $role === 'Executive'): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'approvals.php' ? 'active' : '' ?>" href="approvals.php"><i class="fa-solid fa-inbox me-3"></i> คิวรออนุมัติ <span class="badge bg-warning text-dark ms-2">7</span></a>
            </li>
        <?php endif; ?>
        
        <?php if ($role === 'Admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'register_ext.php' ? 'active' : '' ?>" href="register_ext.php"><i class="fa-solid fa-user-plus me-3"></i> คำขอลงทะเบียน</a>
            </li>
        <?php endif; ?>
    </ul>

    <?php if ($role === 'Executive'): ?>
        <div class="text-muted fs-8 fw-bold text-uppercase mb-3 px-3">สำหรับผู้บริหาร (EXECUTIVE)</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & สถิติภาพรวม</a>
            </li>
        </ul>
    <?php endif; ?>

    <?php if ($role === 'Admin'): ?>
        <div class="text-muted fs-8 fw-bold text-uppercase mb-3 px-3">สำหรับแอดมิน (Admin)</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_rooms.php' ? 'active' : '' ?>" href="admin_rooms.php"><i class="fa-solid fa-door-open me-3"></i> จัดการห้องประชุม</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_sports.php' ? 'active' : '' ?>" href="admin_sports.php"><i class="fa-solid fa-trophy me-3"></i> จัดการสนามกีฬา</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_equipments.php' ? 'active' : '' ?>" href="admin_equipments.php"><i class="fa-solid fa-couch me-3"></i> จัดการอุปกรณ์</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-3"></i> รายงาน & Export</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_users.php' ? 'active' : '' ?>" href="admin_users.php"><i class="fa-solid fa-users-gear me-3"></i> จัดการผู้ใช้</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_announcements.php' ? 'active' : '' ?>" href="admin_announcements.php"><i class="fa-solid fa-bullhorn me-3"></i> ประกาศส่วนกลาง</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_settings.php' ? 'active' : '' ?>" href="admin_settings.php"><i class="fa-solid fa-house-flag me-3"></i> ตั้งค่าข้อมูล & โลโก้</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'audit_logs.php' ? 'active' : '' ?>" href="audit_logs.php"><i class="fa-solid fa-shield-halved me-3"></i> Audit Logs</a>
            </li>
        </ul>
    <?php endif; ?>
</div>
