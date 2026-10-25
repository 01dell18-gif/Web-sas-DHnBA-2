<?php
/**
 * Backward compatibility redirect: password.php -> profil.php
 */
require_once __DIR__ . '/../config/functions.php';
require_admin();

header('Location: /admin/profil.php');
exit;
