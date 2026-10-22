<?php
require_once __DIR__ . '/../config/functions.php';

if (is_admin_logged_in()) {
    log_activity('LOGOUT', "Admin {$_SESSION['admin_user']} keluar.");
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_user']);
    unset($_SESSION['admin_name']);
}

set_flash('info', 'Anda telah berhasil keluar dari panel administrator.');
header('Location: /admin/login.php');
exit;
