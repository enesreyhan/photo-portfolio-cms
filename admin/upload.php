<?php
/**
 * admin/upload.php
 * Klasör Destekli Güvenli Resim Yükleme ve Otomatik AVIF/WebP Dönüştürme Servisi
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek veya dosya seçilmedi.']);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'CSRF güvenlik doğrulaması başarısız.']);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Dosya yükleme hatası kodu: ' . $file['error']]);
    exit;
}

// Belgesel fotoğrafçılar için 40MB'a kadar yüksek çözünürlüklü destek
if ($file['size'] > 40 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Dosya boyutu 40MB sınırını aşıyor.']);
    exit;
}

$allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif', 'tif', 'tiff'];
$orig_name = $file['name'];
$ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed_exts)) {
    echo json_encode(['success' => false, 'error' => 'Yalnızca görsel dosyaları yüklenebilir (JPG, PNG, WEBP, AVIF, SVG).']);
    exit;
}

// ── Klasör Belirleme ──
// 1. Doğrudan belirtilen klasör
$target_folder = trim($_POST['folder'] ?? $_POST['target_dir'] ?? '');

// 2. Proje/İçerik bazlı otomatik klasör
if ($target_folder === '') {
    $item_type = trim($_POST['item_type'] ?? '');
    $item_slug = trim($_POST['item_slug'] ?? '');
    if ($item_type && $item_slug) {
        $type_map = [
            'project' => 'content/projects',
            'series'  => 'content/series',
            'single'  => 'content/single',
        ];
        $base_type_dir = $type_map[$item_type] ?? ('content/' . slugify($item_type));
        $target_folder = $base_type_dir . '/' . slugify($item_slug);
    } else {
        $target_folder = 'uploads';
    }
}

// Güvenlik: dizin aşma temizliği (directory traversal prevention)
// Önce NUL silinir: tersi sırada ".\0." girdisi ".." olarak geri oluşuyordu
$target_folder = str_replace(["\0", '..'], '', $target_folder);
$target_folder = trim($target_folder, '/\\');
if ($target_folder === '') {
    $target_folder = 'uploads';
}
if (!preg_match('#^(uploads|content)(/|$)#', $target_folder)) {
    echo json_encode(['success' => false, 'error' => 'Hedef klasör uploads/ veya content/ altında olmalı.']);
    exit;
}

$base_dir = dirname(__DIR__);
$upload_dir = $base_dir . '/' . $target_folder;
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

// Temiz ve benzersiz dosya adı
$clean_name = slugify(pathinfo($orig_name, PATHINFO_FILENAME));
if ($clean_name === '') $clean_name = 'photo';
$base_name = date('Ymd_His') . '_' . $clean_name . '_' . bin2hex(random_bytes(3));

$res = optimize_and_save_uploaded_photo($file['tmp_name'], $upload_dir, $base_name, $ext);

if ($res['success']) {
    $file_path = $res['file_path'];
    $avif_path = $res['avif_path'] ?? null;
    $webp_path = $res['webp_path'] ?? null;
    $folder    = $res['folder'];

    require_once __DIR__ . '/../includes/media_metadata.php';

    $parsed = parse_media_filename($orig_name);
    $exif = read_photo_exif($base_dir . '/' . $file_path);

    $req_item_type = slugify($_POST['item_type'] ?? '');
    $country_code = $parsed['country_code'] ?: '';
    $city_code    = $parsed['city_code'] ?: '';
    $item_type    = $parsed['item_type'] ?: $req_item_type;
    $project_code = $parsed['project_code'] ?: '';
    $shot_date    = $parsed['date'] ?: ($exif['shot_datetime'] ? substr($exif['shot_datetime'], 0, 10) : '');
    $shot_year    = $parsed['year'] ?: ($shot_date ? substr($shot_date, 0, 4) : '');

    $camera       = $exif['camera_clean'] ?: $exif['camera'] ?: '';
    $lens         = $exif['lens'] ?: '';
    $aperture     = $exif['aperture'] ?: '';
    $aperture_num = (float)($exif['aperture_num'] ?? 0);
    $shutter      = $exif['shutter'] ?: '';
    $shutter_num  = (float)($exif['shutter_num'] ?? 0);
    $iso          = (int)($exif['iso'] ?? 0);
    $focal_length = $exif['focal_length'] ?: '';

    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("
        INSERT INTO `media` (
            `filename`, `original_name`, `file_path`, `folder`,
            `avif_path`, `webp_path`, `mime_type`,
            `file_size`, `width`, `height`, `created_at`,
            `country_code`, `city_code`, `item_type`, `project_code`,
            `shot_date`, `shot_year`, `camera`, `lens`,
            `aperture`, `aperture_num`, `shutter`, `shutter_num`,
            `iso`, `focal_length`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $res['filename'],
        $orig_name,
        $file_path,
        $folder,
        $avif_path,
        $webp_path,
        'image/' . $ext,
        $file['size'],
        $res['width'] ?? 0,
        $res['height'] ?? 0,
        date('Y-m-d H:i:s'),
        $country_code,
        $city_code,
        $item_type,
        $project_code,
        $shot_date,
        $shot_year,
        $camera,
        $lens,
        $aperture,
        $aperture_num,
        $shutter,
        $shutter_num,
        $iso,
        $focal_length
    ]);

    $shortcode = '{{ image(path="' . $file_path . '", alt="' . htmlspecialchars(pathinfo($orig_name, PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8') . '") }}';

    echo json_encode([
        'success'   => true,
        'url'       => $res['url'],
        'avif_url'  => $res['avif_url'],
        'webp_url'  => $res['webp_url'],
        'file_path' => $file_path,
        'folder'    => $folder,
        'filename'  => $res['filename'],
        'shortcode' => $shortcode,
        'width'     => $res['width'] ?? 0,
        'height'    => $res['height'] ?? 0,
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Dosya sunucuya kaydedilemedi.']);
}
