<?php
/**
 * scripts/migrate_media_metadata.php
 * Medya Tablosuna EXIF ve İsimlendirme Metadata Sütunlarını Ekleme ve Doldurma
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/media_metadata.php';

$pdo = DB::getConnection();
$base_dir = dirname(__DIR__);

echo "--- Medya Metadata & EXIF Migrasyonu Başlatılıyor ---\n";

// 1. Mevcut sütunları kontrol et
$cols_stmt = $pdo->query("PRAGMA table_info(media)");
$existing_cols = [];
while ($row = $cols_stmt->fetch(PDO::FETCH_ASSOC)) {
    $existing_cols[] = $row['name'];
}

$new_cols = [
    'country_code' => 'VARCHAR(10) DEFAULT ""',
    'city_code'    => 'VARCHAR(10) DEFAULT ""',
    'item_type'    => 'VARCHAR(20) DEFAULT ""',
    'project_code' => 'VARCHAR(20) DEFAULT ""',
    'shot_date'    => 'VARCHAR(20) DEFAULT ""',
    'shot_year'    => 'VARCHAR(10) DEFAULT ""',
    'camera'       => 'VARCHAR(100) DEFAULT ""',
    'lens'         => 'VARCHAR(100) DEFAULT ""',
    'aperture'     => 'VARCHAR(20) DEFAULT ""',
    'aperture_num' => 'REAL DEFAULT 0',
    'shutter'      => 'VARCHAR(20) DEFAULT ""',
    'shutter_num'  => 'REAL DEFAULT 0',
    'iso'          => 'INT DEFAULT 0',
    'focal_length' => 'VARCHAR(20) DEFAULT ""'
];

foreach ($new_cols as $col => $type) {
    if (!in_array($col, $existing_cols)) {
        $pdo->exec("ALTER TABLE `media` ADD COLUMN `{$col}` {$type}");
        echo "Sütun eklendi: {$col}\n";
    }
}

// İndeksler
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_country ON media(country_code)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_city ON media(city_code)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_type ON media(item_type)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_project ON media(project_code)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_year ON media(shot_year)");

// 2. Tüm medya kayıtlarını tara ve güncelle
$stmt = $pdo->query("SELECT id, filename, file_path FROM `media`");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($rows);
echo "Toplam {$total} görsel işleniyor...\n";

$update_stmt = $pdo->prepare("
    UPDATE `media` SET
        country_code = ?,
        city_code    = ?,
        item_type    = ?,
        project_code = ?,
        shot_date    = ?,
        shot_year    = ?,
        camera       = ?,
        lens         = ?,
        aperture     = ?,
        aperture_num = ?,
        shutter      = ?,
        shutter_num  = ?,
        iso          = ?,
        focal_length = ?
    WHERE id = ?
");

$updated = 0;
foreach ($rows as $r) {
    $id = (int)$r['id'];
    $fn = $r['filename'];
    $fp = $base_dir . '/' . $r['file_path'];

    $parsed = parse_media_filename($fn);
    $exif = read_photo_exif($fp);

    $country_code = $parsed['country_code'] ?: '';
    $city_code    = $parsed['city_code'] ?: '';
    $item_type    = $parsed['item_type'] ?: '';
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

    $update_stmt->execute([
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
        $focal_length,
        $id
    ]);

    $updated++;
}

echo "Tamamlandı: {$updated} / {$total} görsel başarıyla güncellendi!\n";
