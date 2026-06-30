<?php
namespace App\Middleware;

class AuthMiddleware
{
    /**
     * ตรวจสอบว่าเข้าสู่ระบบหรือยัง (Require Authenticated User)
     */
    public static function requireAuth(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $_SESSION['error_message'] = 'กรุณาเข้าสู่ระบบก่อนเข้าถึงหน้านี้';
            header("Location: login.php");
            exit;
        }

        return $_SESSION['user'];
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีสถานะ Active หรือไม่ (Require Active User)
     */
    public static function requireActiveUser(): array
    {
        $user = self::requireAuth();

        if (isset($user['status']) && $user['status'] === 'inactive') {
            $_SESSION['error_message'] = 'บัญชีของคุณอยู่ระหว่างรอการอนุมัติจากเจ้าหน้าที่ ไม่สามารถทำรายการนี้ได้';
            header("Location: dashboard.php");
            exit;
        }

        return $user;
    }

    /**
     * ตรวจสอบสิทธิ์การเข้าถึงตามบทบาท (Require Specific Roles)
     */
    public static function requireRole(array $allowedRoles): array
    {
        $user = self::requireAuth();
        $userRole = $user['role_name'] ?? 'User';

        if (!in_array($userRole, $allowedRoles, true)) {
            $_SESSION['error_message'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (Restricted Access)';
            header("Location: dashboard.php");
            exit;
        }

        return $user;
    }

    /**
     * ตรวจสอบว่าเป็น Admin เท่านั้น (Require Admin)
     */
    public static function requireAdmin(): array
    {
        return self::requireRole(['Admin']);
    }
}
