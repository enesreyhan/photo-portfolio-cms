<?php
/**
 * admin/backup.php
 * Yedekleme işlevi Site Ayarları (settings.php) sayfasına taşınmıştır.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

if (isset($_GET['download']) && $_GET['download'] === 'sqlite') {
    header('Location: ' . url('admin/settings.php?download=sqlite'));
    exit;
}

header('Location: ' . url('admin/settings.php'));
exit;
