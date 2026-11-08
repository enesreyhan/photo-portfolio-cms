<?php
/**
 * config.php
 * Veritabanı ve Genel Yapılandırma
 */

if (!ob_get_level()) {
    ob_start();
}

// Hata raporlama: Lokalde hataları göster, canlı sunucuda gizle ve logla
// Host başlığı istemciden gelir; buna güvenmek canlıda hata ayıklama çıktısını açtırabilirdi
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

// İzinli alan adları: ALLOWED_HOSTS ortam değişkeni (virgüllü) veya includes/private/hosts.php (dizi döner).
// Liste boşsa kısıtlama yok. Listede olmayan Host başlığı listenin ilk adıyla değiştirilir (cache/SEO zehirlenmesine karşı).
$allowed_hosts = array_filter(array_map('trim', explode(',', strtolower((string)getenv('ALLOWED_HOSTS')))));
if (!$allowed_hosts && is_file(__DIR__ . '/includes/private/hosts.php')) {
    $allowed_hosts = array_map('strtolower', (array)require __DIR__ . '/includes/private/hosts.php');
}
if ($allowed_hosts && isset($_SERVER['HTTP_HOST'])) {
    $req_host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']));
    if (!in_array($req_host, $allowed_hosts, true) && !in_array($req_host, ['localhost', '127.0.0.1'], true)) {
        $_SERVER['HTTP_HOST'] = reset($allowed_hosts);
    }
}

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
