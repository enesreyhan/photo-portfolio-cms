<?php
/**
 * scripts/convert_all_images.php
 * Portfolyodaki tüm orijinal fotoğrafları AVIF ve WebP formatlarına dönüştürür.
 * CLI veya cron üzerinden çalıştırılabilir:
 * php scripts/convert_all_images.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

echo "========================================================\n";
echo " Toplu AVIF ve WebP Görsel Dönüştürücü\n";
echo "========================================================\n";

$base_dir = dirname(__DIR__);
$content_dir = $base_dir . '/content';

if (!is_dir($content_dir)) {
    echo "HATA: content dizini bulunamadı!\n";
    exit(1);
}

// Tüm görselleri bul
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($content_dir));
$image_files = [];

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $ext = strtolower($file->getExtension());
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $image_files[] = $file->getPathname();
        }
    }
}

$total = count($image_files);
echo "Toplam {$total} fotoğraf bulundu. Dönüştürme başlıyor...\n\n";

$widths = [600, 920, 1800];
$formats = ['webp', 'avif'];

$processed = 0;
$skipped = 0;

$start_time = microtime(true);

foreach ($image_files as $idx => $file_path) {
    $current = $idx + 1;
    $filename = pathinfo($file_path, PATHINFO_FILENAME);
    $rel = str_replace($base_dir . '/', '', $file_path);

    echo "[{$current}/{$total}] {$filename}... ";

    $did_work = false;
    foreach ($widths as $w) {
        foreach ($formats as $fmt) {
            $target = $base_dir . '/processed_images/' . $filename . '_' . $w . 'w.' . $fmt;
            if (!file_exists($target) || filesize($target) === 0) {
                resize_and_convert_image($file_path, $w, $fmt, ($fmt === 'avif' ? 78 : 82));
                $did_work = true;
            }
        }
    }

    if ($did_work) {
        $processed++;
        echo "DÖNÜŞTÜRÜLDÜ (AVIF + WebP)\n";
    } else {
        $skipped++;
        echo "Zaten mevcut, atlandı.\n";
    }
}

$elapsed = round(microtime(true) - $start_time, 2);
echo "\n========================================================\n";
echo "İşlem Tamamlandı!\n";
echo "İşlenen Görsel: {$processed}\n";
echo "Atlanan (Önceden hazır): {$skipped}\n";
echo "Geçen Süre: {$elapsed} saniye\n";
echo "========================================================\n";
