<?php
/**
 * admin/logout.php
 * Yönetici Çıkış İşlemi
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Yalnızca CSRF token'lı POST ile çıkış (başka siteden <img> ile çıkış yaptırılamasın)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    admin_logout();
}
header('Location: ' . url('admin/login.php'));
exit;
