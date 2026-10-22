<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

if (is_admin()) {
    header('Location: /admin/users.php');
} else {
    header('Location: /admin/index.php');
}
exit;
