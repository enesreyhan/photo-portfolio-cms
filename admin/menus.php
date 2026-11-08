<?php
/**
 * admin/menus.php
 * Menü artık Sayfalar ekranından yönetilir (bağlantı/ayraç ekleme, sıralama, menüden çıkarma).
 * Alt bilgi bağlantıları Site Ayarları › Genel'de, sosyal ikonlar Temalar › Özelleştir › Menü'de.
 * Eski yer imleri ve bağlantılar kırılmasın diye bu dosya yönlendirme olarak kalır.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
header('Location: ' . url('admin/pages.php'), true, 301);
exit;
