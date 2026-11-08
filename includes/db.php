<?php
/**
 * includes/db.php
 * PDO Veritabanı Bağlantı Yöneticisi (SQLite & MySQL)
 */

require_once __DIR__ . '/../config.php';

if (!class_exists('DB')) {
class DB {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                if (DB_DRIVER === 'sqlite') {
                    if (class_exists('\\Pdo\\Sqlite')) {
                        self::$instance = new \Pdo\Sqlite('sqlite:' . DB_SQLITE_PATH);
                    } else {
                        self::$instance = new PDO('sqlite:' . DB_SQLITE_PATH);
                    }
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec("PRAGMA foreign_keys = ON;");

                    $trLowerFn = function($str) {
                        $map = ['İ' => 'i', 'I' => 'ı', 'Ğ' => 'ğ', 'Ü' => 'ü', 'Ş' => 'ş', 'Ö' => 'ö', 'Ç' => 'ç'];
                        $s = strtr((string)$str, $map);
                        $s = mb_strtolower($s, 'UTF-8');
                        return str_replace(["\xcc\x87"], '', $s);
                    };

                    if (method_exists(self::$instance, 'createFunction')) {
                        self::$instance->createFunction('lower_utf8', $trLowerFn);
                    } elseif (method_exists(self::$instance, 'sqliteCreateFunction')) {
                        @self::$instance->sqliteCreateFunction('lower_utf8', $trLowerFn);
                    }
                } else {
                    $dsn = sprintf(
                        'mysql:host=%s;dbname=%s;charset=%s',
                        DB_HOST,
                        DB_NAME,
                        DB_CHARSET
                    );

                    $options = [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                    ];

                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo '<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">';
                echo '<h2>Veritabanı Bağlantı Hatası</h2>';
                echo '<p>Lütfen <code>config.php</code> dosyasındaki veritabanı ayarlarını kontrol edin.</p>';
                if (ini_get('display_errors')) {
                    echo '<p style="color: red; font-size: 13px;">Hata Detayı: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                echo '</div>';
                exit;
            }

            self::checkTables();
        }

        return self::$instance;
    }

    private static function checkTables(): void {
        $pdo = self::$instance;
        $is_sqlite = DB_DRIVER === 'sqlite';
        $auto_inc = $is_sqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
        $text_type = $is_sqlite ? "TEXT" : "MEDIUMTEXT";

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `items` (
                    `id` {$auto_inc},
                    `type` VARCHAR(30) NOT NULL,
                    `slug` VARCHAR(191) NOT NULL,
                    `slug_en` VARCHAR(191) NULL,
                    `slug_tr` VARCHAR(191) NULL,
                    `title_en` VARCHAR(255) NOT NULL,
                    `title_tr` VARCHAR(255) NOT NULL,
                    `description_en` TEXT NULL,
                    `description_tr` TEXT NULL,
                    `content_en` {$text_type} NULL,
                    `content_tr` {$text_type} NULL,
                    `cover_image` VARCHAR(255) NULL,
                    `location` VARCHAR(255) NULL,
                    `year` VARCHAR(100) NULL,
                    `photo_count` INT DEFAULT 0,
                    `weight` INT DEFAULT 0,
                    `date` VARCHAR(50) NULL,
                    `status` VARCHAR(20) DEFAULT 'published',
                    `aliases` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL
                );
            ");

            // items tablosunda slug_en ve slug_tr sütun kontrolü ve migrasyonu
            try {
                $cols = [];
                if ($is_sqlite) {
                    $c_query = $pdo->query("PRAGMA table_info(`items`)")->fetchAll();
                    foreach ($c_query as $c) {
                        $cols[] = $c['name'];
                    }
                } else {
                    $c_query = $pdo->query("SHOW COLUMNS FROM `items`")->fetchAll();
                    foreach ($c_query as $c) {
                        $cols[] = $c['Field'];
                    }
                }

                if (!in_array('slug_en', $cols, true)) {
                    $pdo->exec("ALTER TABLE `items` ADD COLUMN `slug_en` VARCHAR(191) NULL;");
                    $pdo->exec("UPDATE `items` SET `slug_en` = `slug` WHERE `slug_en` IS NULL OR `slug_en` = '';");
                }
                if (!in_array('slug_tr', $cols, true)) {
                    $pdo->exec("ALTER TABLE `items` ADD COLUMN `slug_tr` VARCHAR(191) NULL;");
                    $pdo->exec("UPDATE `items` SET `slug_tr` = `slug` WHERE `slug_tr` IS NULL OR `slug_tr` = '';");
                }
                if (!in_array('location_en', $cols, true)) {
                    $pdo->exec("ALTER TABLE `items` ADD COLUMN `location_en` VARCHAR(255) NULL;");
                    $pdo->exec("UPDATE `items` SET `location_en` = `location` WHERE `location_en` IS NULL OR `location_en` = '';");
                }
                if (!in_array('location_tr', $cols, true)) {
                    $pdo->exec("ALTER TABLE `items` ADD COLUMN `location_tr` VARCHAR(255) NULL;");
                    $pdo->exec("UPDATE `items` SET `location_tr` = `location` WHERE `location_tr` IS NULL OR `location_tr` = '';");
                }
                if (!in_array('access_password', $cols, true)) {
                    $pdo->exec("ALTER TABLE `items` ADD COLUMN `access_password` VARCHAR(255) NULL DEFAULT '';");
                }
                if (!in_array('cover_focal', $cols, true)) {
                    $pdo->exec("ALTER TABLE `items` ADD COLUMN `cover_focal` VARCHAR(32) NULL DEFAULT '50% 50%';");
                }
            } catch (\Throwable $e) {}

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `settings` (
                    `key_name` VARCHAR(100) PRIMARY KEY,
                    `key_value` TEXT NULL
                );
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` {$auto_inc},
                    `username` VARCHAR(50) NOT NULL UNIQUE,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `email` VARCHAR(100) NULL,
                    `created_at` DATETIME NOT NULL
                );
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `media` (
                    `id` {$auto_inc},
                    `filename` VARCHAR(255) NOT NULL,
                    `original_name` VARCHAR(255) NULL,
                    `file_path` VARCHAR(255) NOT NULL,
                    `avif_path` VARCHAR(255) NULL,
                    `webp_path` VARCHAR(255) NULL,
                    `mime_type` VARCHAR(50) NULL,
                    `file_size` INT DEFAULT 0,
                    `width` INT DEFAULT 0,
                    `height` INT DEFAULT 0,
                    `created_at` DATETIME NOT NULL
                );
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `homepage_slides` (
                    `id` {$auto_inc},
                    `image_path` VARCHAR(255) NOT NULL,
                    `title_tr` VARCHAR(255) NULL,
                    `title_en` VARCHAR(255) NULL,
                    `description_tr` {$text_type} NULL,
                    `description_en` {$text_type} NULL,
                    `location` VARCHAR(255) NULL,
                    `year` VARCHAR(50) NULL,
                    `link_url` VARCHAR(255) NULL,
                    `weight` INT NOT NULL DEFAULT 0,
                    `is_active` INT NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL
                );
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `categories` (
                    `id` {$auto_inc},
                    `key_slug` VARCHAR(64) NOT NULL UNIQUE,
                    `name_tr` VARCHAR(128) NOT NULL,
                    `name_en` VARCHAR(128) NOT NULL,
                    `slug_tr` VARCHAR(128) NOT NULL,
                    `slug_en` VARCHAR(128) NOT NULL,
                    `description_tr` {$text_type} NULL,
                    `description_en` {$text_type} NULL,
                    `show_in_menu` INT NOT NULL DEFAULT 1,
                    `menu_id` INT NULL,
                    `weight` INT NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP
                );
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `menus` (
                    `id` {$auto_inc},
                    `title_tr` VARCHAR(255) NOT NULL,
                    `title_en` VARCHAR(255) NOT NULL,
                    `url_tr` VARCHAR(255) NOT NULL,
                    `url_en` VARCHAR(255) NOT NULL,
                    `weight` INT NOT NULL DEFAULT 0,
                    `is_active` INT NOT NULL DEFAULT 1,
                    `separator_before` INT NOT NULL DEFAULT 0,
                    `is_separator` INT NOT NULL DEFAULT 0,
                    `is_system` INT NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL
                );
            ");

            try {
                $m_cols = [];
                if ($is_sqlite) {
                    $mc_query = $pdo->query("PRAGMA table_info(`menus`)")->fetchAll();
                    foreach ($mc_query as $c) {
                        $m_cols[] = $c['name'];
                    }
                } else {
                    $mc_query = $pdo->query("SHOW COLUMNS FROM `menus`")->fetchAll();
                    foreach ($mc_query as $c) {
                        $m_cols[] = $c['Field'];
                    }
                }
                if (!in_array('is_separator', $m_cols, true)) {
                    $pdo->exec("ALTER TABLE `menus` ADD COLUMN `is_separator` INT NOT NULL DEFAULT 0;");
                }
            } catch (\Throwable $e) {}

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `languages` (
                    `id` {$auto_inc},
                    `code` VARCHAR(16) NOT NULL UNIQUE,
                    `name_native` VARCHAR(100) NOT NULL,
                    `name_tr` VARCHAR(100) NOT NULL,
                    `flag` VARCHAR(32) NULL DEFAULT '🌐',
                    `direction` VARCHAR(8) NOT NULL DEFAULT 'ltr',
                    `locale` VARCHAR(32) NULL DEFAULT '',
                    `fallback_lang` VARCHAR(16) NOT NULL DEFAULT 'en',
                    `is_active` INT NOT NULL DEFAULT 1,
                    `is_default` INT NOT NULL DEFAULT 0,
                    `is_admin_enabled` INT NOT NULL DEFAULT 1,
                    `url_slugs_json` {$text_type} NULL,
                    `ui_strings_json` {$text_type} NULL,
                    `weight` INT NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL
                );
            ");

            try {
                $seeded_check = $pdo->query("SELECT `key_value` FROM `settings` WHERE `key_name` = 'languages_seeded_v1'")->fetchColumn();
                $lang_count = (int)$pdo->query("SELECT COUNT(*) FROM `languages`")->fetchColumn();
                if (!$seeded_check && $lang_count === 0) {
                    $now = date('Y-m-d H:i:s');
                    $seed_langs = [
                        [
                            'code' => 'en',
                            'name_native' => 'English',
                            'name_tr' => 'İngilizce',
                            'flag' => '🇬🇧',
                            'direction' => 'ltr',
                            'locale' => 'en_US',
                            'fallback_lang' => 'tr',
                            'is_active' => 1,
                            'is_default' => 1,
                            'is_admin_enabled' => 1,
                            'url_slugs_json' => json_encode([
                                'about' => 'about',
                                'field_work' => 'field-work',
                                'privacy_notice' => 'privacy-notice',
                                'explicit_consent' => 'explicit-consent',
                                'projects' => 'projects',
                                'series' => 'series',
                                'single' => 'single',
                            ], JSON_UNESCAPED_UNICODE),
                            'weight' => 1,
                        ],
                        [
                            'code' => 'tr',
                            'name_native' => 'Türkçe',
                            'name_tr' => 'Türkçe',
                            'flag' => '🇹🇷',
                            'direction' => 'ltr',
                            'locale' => 'tr_TR',
                            'fallback_lang' => 'en',
                            'is_active' => 1,
                            'is_default' => 0,
                            'is_admin_enabled' => 1,
                            'url_slugs_json' => json_encode([
                                'about' => 'hakkimda',
                                'field_work' => 'saha-rotalari',
                                'privacy_notice' => 'aydinlatma-metni',
                                'explicit_consent' => 'acik-riza-metni',
                                'projects' => 'projeler',
                                'series' => 'seriler',
                                'single' => 'tekil',
                            ], JSON_UNESCAPED_UNICODE),
                            'weight' => 2,
                        ],
                    ];
                    $ins = $pdo->prepare("
                        INSERT INTO `languages` (
                            `code`, `name_native`, `name_tr`, `flag`, `direction`, `locale`,
                            `fallback_lang`, `is_active`, `is_default`, `is_admin_enabled`,
                            `url_slugs_json`, `weight`, `created_at`, `updated_at`
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($seed_langs as $sl) {
                        $ins->execute([
                            $sl['code'], $sl['name_native'], $sl['name_tr'], $sl['flag'],
                            $sl['direction'], $sl['locale'], $sl['fallback_lang'],
                            $sl['is_active'], $sl['is_default'], $sl['is_admin_enabled'],
                            $sl['url_slugs_json'], $sl['weight'], $now, $now
                        ]);
                    }
                    if ($is_sqlite) {
                        $pdo->exec("INSERT OR IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('languages_seeded_v1', '1');");
                    } else {
                        $pdo->exec("INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('languages_seeded_v1', '1');");
                    }
                }
            } catch (\Throwable $e) {}

            // Sonradan eklenen sütunlar: eski ve yeni kurulumlarda eksikse ekle (harita, EXIF filtreleri, slayt→çalışma bağı)
            $ensure_cols = function (string $table, array $defs) use ($pdo, $is_sqlite) {
                $have = $is_sqlite
                    ? array_column($pdo->query("PRAGMA table_info(`{$table}`)")->fetchAll(), 'name')
                    : array_column($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(), 'Field');
                foreach ($defs as $col => $def) {
                    if (!in_array($col, $have, true)) $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
                }
            };
            try {
                $ensure_cols('media', [
                    'folder' => "VARCHAR(255) DEFAULT ''", 'country_code' => "VARCHAR(10) DEFAULT ''", 'city_code' => "VARCHAR(10) DEFAULT ''",
                    'item_type' => "VARCHAR(20) DEFAULT ''", 'project_code' => "VARCHAR(20) DEFAULT ''", 'shot_date' => "VARCHAR(20) DEFAULT ''",
                    'shot_year' => "VARCHAR(10) DEFAULT ''", 'camera' => "VARCHAR(100) DEFAULT ''", 'lens' => "VARCHAR(100) DEFAULT ''",
                    'aperture' => "VARCHAR(20) DEFAULT ''", 'aperture_num' => 'REAL DEFAULT 0', 'shutter' => "VARCHAR(20) DEFAULT ''",
                    'shutter_num' => 'REAL DEFAULT 0', 'iso' => 'INT DEFAULT 0', 'focal_length' => "VARCHAR(20) DEFAULT ''",
                ]);
                $ensure_cols('homepage_slides', ['item_id' => 'INT NULL']);
            } catch (\Throwable $e) {}

            // Tamamen boş yeni kurulum: Hakkımda ve İletişim menüsü, Hakkımda sayfası (mevcut sitelere asla dokunmaz)
            try {
                $defaults_done = $pdo->query("SELECT `key_value` FROM `settings` WHERE `key_name` = 'defaults_seeded_v1'")->fetchColumn();
                if (!$defaults_done) {
                    $is_empty = (int)$pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn() === 0
                             && (int)$pdo->query("SELECT COUNT(*) FROM `menus`")->fetchColumn() === 0
                             && (int)$pdo->query("SELECT COUNT(*) FROM `items`")->fetchColumn() === 0;
                    if ($is_empty) {
                        $now = date('Y-m-d H:i:s');
                        $ins_menu = $pdo->prepare("INSERT INTO `menus` (`title_tr`, `title_en`, `url_tr`, `url_en`, `weight`, `is_active`, `separator_before`, `is_separator`, `is_system`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, 1, 0, 0, 0, ?, ?)");
                        // Portfolyo kategorileri hazır gelmez; kullanıcı kendi kategorilerini Sayfalar ekranından ekler
                        $ins_menu->execute(['Hakkımda', 'About', 'tr/hakkimda/', 'en/about/', 10, $now, $now]);
                        $ins_menu->execute(['İletişim', 'Contact', 'tr/iletisim/', 'en/contact/', 20, $now, $now]);
                        $pdo->exec(($is_sqlite ? "INSERT OR IGNORE" : "INSERT IGNORE") . " INTO `settings` (`key_name`, `key_value`) VALUES ('contact_page_enabled', '1');");
                        $pdo->prepare("INSERT INTO `items` (`type`, `slug`, `slug_tr`, `slug_en`, `title_tr`, `title_en`, `content_tr`, `content_en`, `status`, `created_at`, `updated_at`) VALUES ('page', 'aboutme', 'hakkimda', 'about', 'Hakkımda', 'About Me', '', '', 'published', ?, ?)")
                            ->execute([$now, $now]);
                    }
                    $pdo->exec(($is_sqlite ? "INSERT OR IGNORE" : "INSERT IGNORE") . " INTO `settings` (`key_name`, `key_value`) VALUES ('defaults_seeded_v1', '1');");
                }
            } catch (\Throwable $e) {}
        } catch (\Throwable $e) {}
    }
}
}
