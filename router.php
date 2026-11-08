<?php
/**
 * router.php
 * PHP Dahili Geliştirme Sunucusu (Built-in Development Server) Yönlendiricisi
 * 
 * Kullanım:
 * php -S localhost:8000 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Feed ve sitemap yönlendirmeleri
if ($uri === '/atom.xml') {
    require __DIR__ . '/atom.php';
    exit;
}
if ($uri === '/rss.xml') {
    require __DIR__ . '/rss.php';
    exit;
}
if ($uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    exit;
}
if ($uri === '/robots.txt') {
    require __DIR__ . '/robots.php';
    exit;
}

// Mevcut statik dosya veya klasör kontrolü (css, js, resimler, yazı tipleri, uploads)
$file_path = __DIR__ . $uri;
if ($uri !== '/' && file_exists($file_path)) {
    if (is_file($file_path)) {
        // Eğer PHP dosyası ise (örneğin /admin/items.php) çalıştır
        if (substr($file_path, -4) === '.php') {
            require $file_path;
            exit;
        }
        // Statik dosyaları doğrudan PHP web server'a sunması için bırak
        return false;
    }
}

// content/ altındaki statik dosyaların doğrudan sunulması (/projects/... veya /series/... istekleri için)
if (preg_match('/\.(jpg|jpeg|png|webp|avif|gif|svg)$/i', $uri)) {
    $content_file = __DIR__ . '/content' . $uri;
    if (file_exists($content_file) && is_file($content_file)) {
        $ext = strtolower(pathinfo($content_file, PATHINFO_EXTENSION));
        $mimes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'webp' => 'image/webp',
            'avif' => 'image/avif', 'gif' => 'image/gif',
            'svg' => 'image/svg+xml'
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($content_file));
        readfile($content_file);
        exit;
    }
}

// Admin dizinindeki istekler
if ($uri === '/admin' || $uri === '/admin/') {
    require __DIR__ . '/admin/index.php';
    exit;
}

// Diğer tüm rotalar index.php ön yüz denetleyicisine
require __DIR__ . '/index.php';
