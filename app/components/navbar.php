<?php
$currentUser = $_SESSION['user'] ?? null;
$isGuest = (!$currentUser);
if (!$currentUser) {
    $currentUser = [
        'full_name' => 'ผู้เยี่ยมชม (Guest)',
        'role_name' => 'Guest',
        'email' => 'guest@wiang.go.th',
        'status' => 'guest'
    ];
}
$avatarName = urlencode($currentUser['full_name'] ?? 'Guest');
$role = $currentUser['role_name'] ?? $currentUser['role'] ?? 'Guest';
$userStatus = $currentUser['status'] ?? 'active';

$currentLogo = $_SESSION['org_logo'] ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Garuda_of_Thailand_%28Government_Gazette%29.svg/180px-Garuda_of_Thailand_%28Government_Gazette%29.svg.png';
$currentOrgName = $_SESSION['org_name'] ?? 'องค์การบริหารส่วนตำบลเวียง';
?>
<!-- Top Navbar Component -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3 sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="<?= $currentLogo ?>" alt="Logo" class="me-3 rounded-circle shadow-sm" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #cbd5e1;"> 
            <span class="fw-bold">SMART FACILITY BOOKING (<?= htmlspecialchars($currentOrgName) ?>)</span>
        </a>
        <div class="d-flex align-items-center">
            <button class="btn btn-light position-relative me-3 border-0" style="background: #f1f5f9; border-radius: 12px; width: 44px; height: 44px;">
                <i class="fa-regular fa-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
            </button>
            <div class="d-flex align-items-center me-3">
                <img src="https://ui-avatars.com/api/?name=<?= $avatarName ?>&background=4338ca&color=fff" class="rounded-circle me-2" width="44" height="44">
                <div class="d-none d-md-block">
                    <div class="fw-semibold fs-6 lh-1"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                    <span class="badge bg-indigo-light mt-1"><?= htmlspecialchars($role) ?></span>
                </div>
            </div>
            <?php if ($isGuest): ?>
                <a href="login.php" class="btn btn-custom-primary btn-sm px-4 py-2 rounded-3 fw-semibold shadow-sm">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> เข้าสู่ระบบ
                </a>
            <?php else: ?>
                <a href="logout.php" class="btn btn-outline-danger btn-sm px-3 py-2 rounded-3 fw-semibold">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
