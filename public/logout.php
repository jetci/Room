<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
setcookie('user_session_payload', '', time() - 3600, '/');
header("Location: index.php");
exit;
