<?php
/**
 * admin/logout.php
 * Yönetici Çıkış İşlemi
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

admin_logout();
header('Location: ' . url('admin/login.php'));
exit;
