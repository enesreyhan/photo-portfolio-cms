<?php
/**
 * scripts/sync_media.php
 * content/ ve uploads/ altındaki tüm fotoğrafları media tablosuna senkronize eder.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = DB::getConnection();
$base_dir = dirname(__DIR__);
$content_dir = $base_dir . '/content';

echo "Görseller taranıyor...\n";

$allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
$count = 0;

$stmt_check = $pdo->prepare("SELECT id FROM `media` WHERE `file_path` = ? LIMIT 1");
$stmt_insert = $pdo->prepare("
    INSERT INTO `media` (
        `filename`, `original_name`, `file_path`, `folder`,
        `avif_path`, `webp_path`, `mime_type`,
        `file_size`, `width`, `height`, `created_at`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$dirs_to_scan = [
    $base_dir . '/content',
    $base_dir . '/uploads',
];

foreach ($dirs_to_scan as $scan_dir) {
    if (!is_dir($scan_dir)) continue;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scan_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $allowed_exts)) continue;

        $full_path = $file->getPathname();
        $rel_path = ltrim(str_replace($base_dir, '', $full_path), '/\\');
        $rel_path = str_replace('\\', '/', $rel_path);

        // Zola .avif veya processed dosyalarını ana liste olarak değil varyant olarak ele al
        if (strpos($rel_path, 'processed_images/') !== false) continue;

        $stmt_check->execute([$rel_path]);
        if ($stmt_check->fetch()) {
            continue; // Zaten ekli
        }

        $filename = $file->getFilename();
        $folder = str_replace('\\', '/', ltrim(str_replace($base_dir, '', dirname($full_path)), '/\\'));
        $size = $file->getSize();

        $width = 0;
        $height = 0;
        $img_info = @getimagesize($full_path);
        if ($img_info) {
            $width = $img_info[0];
            $height = $img_info[1];
        }

        // AVIF ve WebP yollarını tespit et
        $avif_path = null;
        $webp_path = null;

        // 1. Zola processed_images kontrolü
        $zola_avifs = find_existing_zola_avif($filename);
        if (!empty($zola_avifs)) {
            $avif_path = $zola_avifs[count($zola_avifs) - 1]; // en yüksek çözünürlüklü
        }

        // 2. Aynı klasörde .avif / .webp var mı?
        $dir_path = dirname($full_path);
        $base_no_ext = pathinfo($filename, PATHINFO_FILENAME);
        if (file_exists($dir_path . '/' . $base_no_ext . '.avif')) {
            $avif_path = $folder . '/' . $base_no_ext . '.avif';
        }
        if (file_exists($dir_path . '/' . $base_no_ext . '.webp')) {
            $webp_path = $folder . '/' . $base_no_ext . '.webp';
        }

        $now = date('Y-m-d H:i:s', $file->getMTime());
        $mime = $img_info['mime'] ?? ('image/' . $ext);

        $stmt_insert->execute([
            $filename,
            $filename,
            $rel_path,
            $folder,
            $avif_path,
            $webp_path,
            $mime,
            $size,
            $width,
            $height,
            $now
        ]);
        $count++;
    }
}

echo "Senkronizasyon tamamlandı! Toplam {$count} görsel eklendi.\n";
