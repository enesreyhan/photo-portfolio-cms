<?php
/**
 * config.php
 * Veritabanı ve Genel Yapılandırma
 */

if (!ob_get_level()) {
    ob_start();
}

// Hata raporlama: Lokalde hataları göster, canlı sunucuda gizle ve logla
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
            (isset($_SERVER['HTTP_HOST']) && (str_contains($_SERVER['HTTP_HOST'], 'localhost') || str_contains($_SERVER['HTTP_HOST'], '127.0.0.1')));

if ($is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Veritabanı Sürücüsü: 'sqlite' (varsayılan) veya 'mysql'
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite');

// MySQL Bilgileri (DB_DRIVER=mysql iken ortam değişkenlerinden okunur)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'portfolio');
define('DB_USER', getenv('DB_USER') ?: 'portfolio_user');
define('DB_PASS', getenv('DB_PASS') ?: 'DB_PAROLANIZ');
define('DB_CHARSET', 'utf8mb4');

// SQLite Dosya Yolu (Lokal geliştirme ve test için)
define('DB_SQLITE_PATH', __DIR__ . '/site.db');

// Site URL Yapılandırması (Otomatik Algılama)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = ($script_dir === '/' || $script_dir === '.') ? '' : rtrim($script_dir, '/');
$base_path = preg_replace('#/(admin|api|scripts|includes)$#', '', $base_path);
define('BASE_URL', $protocol . $host . $base_path);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');
