<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = DB::getConnection();

$out = "-- Veritabanı Dökümü (MySQL Uyumlu)\n";
$out .= "-- Oluşturulma Tarihi: " . date('Y-m-d H:i:s') . "\n";
$out .= "SET NAMES utf8mb4;\n";
$out .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

$tables = ['users', 'settings', 'items', 'media'];

foreach ($tables as $tbl) {
    $out .= "-- -----------------------------------------------------\n";
    $out .= "-- Tablo: `{$tbl}`\n";
    $out .= "-- -----------------------------------------------------\n";
    $out .= "DROP TABLE IF EXISTS `{$tbl}`;\n";

    if ($tbl === 'users') {
        $out .= "CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
    } elseif ($tbl === 'settings') {
        $out .= "CREATE TABLE `settings` (
  `key` varchar(64) NOT NULL PRIMARY KEY,
  `value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
    } elseif ($tbl === 'items') {
        $out .= "CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL,
  `slug` varchar(128) NOT NULL UNIQUE,
  `title_en` varchar(255) NOT NULL DEFAULT '',
  `title_tr` varchar(255) NOT NULL DEFAULT '',
  `description_en` text DEFAULT NULL,
  `description_tr` text DEFAULT NULL,
  `content_en` longtext DEFAULT NULL,
  `content_tr` longtext DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `location` varchar(128) DEFAULT NULL,
  `year` varchar(32) DEFAULT NULL,
  `photo_count` int(11) NOT NULL DEFAULT 0,
  `weight` int(11) NOT NULL DEFAULT 0,
  `date` date NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'published',
  `aliases` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
    } elseif ($tbl === 'media') {
        $out .= "CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL UNIQUE,
  `folder` varchar(128) NOT NULL DEFAULT 'uploads',
  `mime_type` varchar(64) NOT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `alt_text_tr` varchar(255) DEFAULT NULL,
  `alt_text_en` varchar(255) DEFAULT NULL,
  `caption_tr` text DEFAULT NULL,
  `caption_en` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_folder` (`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
    }

    $rows = $pdo->query("SELECT * FROM `{$tbl}`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $colNames = implode('`, `', $cols);
            $vals = [];
            foreach ($row as $v) {
                if ($v === null) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = "'" . addcslashes($v, "\0\n\r\t\\'\"") . "'";
                }
            }
            $valStr = implode(', ', $vals);
            $out .= "INSERT INTO `{$tbl}` (`{$colNames}`) VALUES ({$valStr});\n";
        }
        $out .= "\n";
    }
}

$out .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/../database.sql', $out);
echo "database.sql updated successfully (" . strlen($out) . " bytes).\n";
