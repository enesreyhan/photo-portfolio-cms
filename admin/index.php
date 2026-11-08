<?php
/**
 * admin/index.php
 * Yönetim Paneli Giriş Yönlendirmesi -> Ana Sayfa Seçkisi (homepage.php)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
redirect('admin/dashboard.php');
