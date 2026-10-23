<?php
require_once __DIR__ . '/../config/functions.php';

if (is_admin_logged_in()) {
    $username = $_SESSION['admin_user'] ?? 'Pengguna';
    log_activity('LOGOUT', "Pengguna {$username} keluar.");
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
session_start();

set_flash('info', 'Anda telah berhasil keluar dari panel administrator.');
header('Location: /admin/login.php');
exit;

