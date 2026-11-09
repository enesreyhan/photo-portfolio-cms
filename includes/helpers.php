<?php
/**
 * includes/helpers.php
 * Yardımcı Fonksiyonlar: Markdown, AVIF/WebP Görsel Dönüştürme, URL, SEO, Güvenlik
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Parsedown.php';
require_once __DIR__ . '/legal_defaults.php';

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function tr_lower(?string $str): string {
    if ($str === null) return '';
    $map = [
        'İ' => 'i', 'I' => 'ı',
        'Ğ' => 'ğ', 'Ü' => 'ü', 'Ş' => 'ş', 'Ö' => 'ö', 'Ç' => 'ç',
    ];
    $str = strtr($str, $map);
    $str = mb_strtolower($str, 'UTF-8');
    return str_replace(["\xcc\x87"], '', $str);
}

function url(string $path = ''): string {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    $clean_path = ltrim($path, '/');
    if ($clean_path === '') {
        return BASE_URL . '/';
    }
    return BASE_URL . '/' . $clean_path;
}

function asset_url(string $path): string {
    $full_path = dirname(__DIR__) . '/' . ltrim($path, '/');
    $ver = file_exists($full_path) ? filemtime($full_path) : 1;
    return url($path) . '?v=' . $ver;
}

function redirect(string $path): void {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

function flash(string $type, string $message): void {
    if (function_exists('set_flash')) {
        set_flash($type, $message);
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
    }
}

$GLOBALS['site_settings_cache'] = null;

function get_setting(string $key, string $default = ''): string {
    if ($GLOBALS['site_settings_cache'] === null) {
        try {
            $pdo = DB::getConnection();
            $stmt = $pdo->query("SELECT `key_name`, `key_value` FROM `settings`");
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $GLOBALS['site_settings_cache'] = $rows ?: [];
        } catch (\Throwable $e) {
            $GLOBALS['site_settings_cache'] = [];
        }
        // Yönetici stil paketi önizlemesi: yalnızca giriş yapmış yöneticinin tarayıcısında, kayıt yapmadan
        if (!empty($_COOKIE['er_style_preview'])) {
            require_once __DIR__ . '/style_presets.php';
            $GLOBALS['site_settings_cache'] = array_merge($GLOBALS['site_settings_cache'], style_preview_overrides());
        }
    }
    // Kategoriye özel tasarım (Temalar › Proje listeleri / Proje sayfası): "pf_template@series" gibi; boşsa ortak ayar
    // Sayfaya özel seçim (Sayfalar › Ayarlar › Sayfa şablonu): "inner_opening_layout@item-12"; listeninkinden önce gelir
    $item_scope = $GLOBALS['setting_item_scope'] ?? '';
    if ($item_scope !== '' && ($GLOBALS['site_settings_cache'][$key . '@' . $item_scope] ?? '') !== '') {
        return $GLOBALS['site_settings_cache'][$key . '@' . $item_scope];
    }
    $scope = $GLOBALS['setting_scope'] ?? '';
    if ($scope !== '' &&($GLOBALS['site_settings_cache'][$key . '@' . $scope] ?? '') !== '') {
        return $GLOBALS['site_settings_cache'][$key . '@' . $scope];
    }
    return $GLOBALS['site_settings_cache'][$key] ?? $default;
}

/** Saha Rotaları haritası siteye özel bir modüldür; yalnızca includes/private/field_map.php varsa açılır */
function field_map_enabled(): bool {
    return is_file(__DIR__ . '/private/field_map.php');
}

/** Site sahibinin adı (telif, Schema.org, yasal metin, panel): ayar → footer marka başlığı → logo metni → alan adı */
function site_owner_name(): string {
    foreach (['site_owner_name', 'footer_brand_title_en', 'footer_brand_title_tr', 'header_logo_text'] as $k) {
        $v = trim(get_setting($k));
        if ($v !== '') return $v;
    }
    return preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'Portfolio');
}

function set_setting(string $key, string $value): void {
    $pdo = DB::getConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $st = $pdo->prepare("INSERT INTO `settings` (`key_name`, `key_value`) VALUES (?, ?) ON CONFLICT(`key_name`) DO UPDATE SET `key_value` = excluded.`key_value`");
        $st->execute([$key, $value]);
    } else {
        $st = $pdo->prepare("INSERT INTO `settings` (`key_name`, `key_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `key_value` = ?");
        $st->execute([$key, $value, $value]);
    }
    if ($GLOBALS['site_settings_cache'] !== null) {
        $GLOBALS['site_settings_cache'][$key] = $value;
    }
}

function slugify(string $text): string {
    $text = trim($text);
    // Kesme işaretlerini kaldır (Tanzanya'da -> Tanzanyada)
    $text = str_replace(["'", "’", "`", "´"], '', $text);
    $tr_map = [
        'ı' => 'i', 'İ' => 'i', 'I' => 'i',
        'ş' => 's', 'Ş' => 's',
        'ğ' => 'g', 'Ğ' => 'g',
        'ü' => 'u', 'Ü' => 'u',
        'ö' => 'o', 'Ö' => 'o',
        'ç' => 'c', 'Ç' => 'c',
    ];
    $text = strtr($text, $tr_map);
    $text = mb_strtolower($text, 'UTF-8');
    // Latin dışı alfabeler okunur Latin harflerine (Ermenice, Kiril, Yunan, Gürcü); kalanlar intl ile
    $text = slug_transliterate($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Adres (slug) için harf çevirisi tablosu — küçük harfler. Panelin tarayıcı tarafı da aynı tabloyu kullanır
 * (admin/includes/lang_bar.php → window.erSlugify), böylece önerilen adres ile kaydedilen adres aynı olur.
 */
function slug_translit_map(): array {
    static $map = null;
    if ($map !== null) return $map;
    $map = [
        // Ermenice (önce iki harfli "ու")
        'ու' => 'u', 'և' => 'ev', 'ա' => 'a', 'բ' => 'b', 'գ' => 'g', 'դ' => 'd', 'ե' => 'e', 'զ' => 'z', 'է' => 'e', 'ը' => 'y', 'թ' => 't',
        'ժ' => 'zh', 'ի' => 'i', 'լ' => 'l', 'խ' => 'kh', 'ծ' => 'ts', 'կ' => 'k', 'հ' => 'h', 'ձ' => 'dz', 'ղ' => 'gh', 'ճ' => 'ch', 'մ' => 'm',
        'յ' => 'y', 'ն' => 'n', 'շ' => 'sh', 'ո' => 'o', 'չ' => 'ch', 'պ' => 'p', 'ջ' => 'j', 'ռ' => 'r', 'ս' => 's', 'վ' => 'v', 'տ' => 't',
        'ր' => 'r', 'ց' => 'ts', 'ւ' => 'v', 'փ' => 'p', 'ք' => 'k', 'օ' => 'o', 'ֆ' => 'f',
        // Kiril (Rusça, Ukraynaca, Bulgarca, Sırpça)
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y',
        'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
        'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'і' => 'i', 'ї' => 'yi', 'є' => 'ye', 'ґ' => 'g', 'ђ' => 'dj', 'ј' => 'j', 'љ' => 'lj', 'њ' => 'nj', 'ћ' => 'c', 'џ' => 'dz',
        // Yunanca
        'α' => 'a', 'ά' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'έ' => 'e', 'ζ' => 'z', 'η' => 'i', 'ή' => 'i', 'θ' => 'th',
        'ι' => 'i', 'ί' => 'i', 'ϊ' => 'i', 'ΐ' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'ό' => 'o',
        'π' => 'p', 'ρ' => 'r', 'σ' => 's', 'ς' => 's', 'τ' => 't', 'υ' => 'y', 'ύ' => 'y', 'ϋ' => 'y', 'ΰ' => 'y', 'φ' => 'f', 'χ' => 'ch',
        'ψ' => 'ps', 'ω' => 'o', 'ώ' => 'o',
        // Gürcüce
        'ა' => 'a', 'ბ' => 'b', 'გ' => 'g', 'დ' => 'd', 'ე' => 'e', 'ვ' => 'v', 'ზ' => 'z', 'თ' => 't', 'ი' => 'i', 'კ' => 'k', 'ლ' => 'l',
        'მ' => 'm', 'ნ' => 'n', 'ო' => 'o', 'პ' => 'p', 'ჟ' => 'zh', 'რ' => 'r', 'ს' => 's', 'ტ' => 't', 'უ' => 'u', 'ფ' => 'p', 'ქ' => 'k',
        'ღ' => 'gh', 'ყ' => 'q', 'შ' => 'sh', 'ჩ' => 'ch', 'ც' => 'ts', 'ძ' => 'dz', 'წ' => 'ts', 'ჭ' => 'ch', 'ხ' => 'kh', 'ჯ' => 'j', 'ჰ' => 'h',
        // Sık görülen Latin harfleri
        'ß' => 'ss', 'æ' => 'ae', 'œ' => 'oe', 'ø' => 'o', 'đ' => 'd', 'ł' => 'l', 'þ' => 'th', 'ð' => 'd',
    ];
    return $map;
}

/** Küçük harfe çevrilmiş metni ASCII'ye yaklaştırır (bilinmeyen harfler olduğu gibi kalır, sonra temizlenir) */
function slug_transliterate(string $text): string {
    if (!preg_match('/[^\x00-\x7F]/', $text)) return $text;
    $text = strtr($text, slug_translit_map());
    if (!preg_match('/[^\x00-\x7F]/', $text)) return $text;
    // Aksanlı Latin harfleri ve diğer alfabeler (Arapça, Çince, Hintçe…): sunucuda intl varsa
    if (function_exists('transliterator_transliterate')) {
        $t = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        if (is_string($t) && $t !== '') return $t;
    }
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($t) && $t !== '') return strtolower($t);
    }
    return $text;
}

/**
 * Sistemde tanımlı tüm dilleri veritabanından döndürür
 */
function get_all_languages(bool $refresh = false): array {
    static $cache = null;
    if ($cache !== null && !$refresh) {
        return $cache;
    }
    try {
        $pdo = DB::getConnection();
        $rows = $pdo->query("SELECT * FROM `languages` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $synced_flag = get_setting('lang_columns_synced_codes', '');
            $synced_list = array_filter(explode(',', $synced_flag));
            $needs_sync_update = false;
            foreach ($rows as &$r) {
                $r['code'] = strtolower(trim($r['code']));
                $r['url_slugs'] = !empty($r['url_slugs_json']) ? (json_decode($r['url_slugs_json'], true) ?: []) : [];
                $r['ui_strings'] = !empty($r['ui_strings_json']) ? (json_decode($r['ui_strings_json'], true) ?: []) : [];
                if (!in_array($r['code'], ['en', 'tr'], true) && !in_array($r['code'], $synced_list, true)) {
                    ensure_language_columns($r['code']);
                    $synced_list[] = $r['code'];
                    $needs_sync_update = true;
                }
            }
            unset($r);
            if ($needs_sync_update) {
                set_setting('lang_columns_synced_codes', implode(',', array_unique($synced_list)));
            }
            $cache = $rows;
            return $cache;
        }
    } catch (\Throwable $e) {}

    // Fallback (DB henüz hazır değilse)
    $cache = [
        [
            'id' => 1, 'code' => 'en', 'name_native' => 'English', 'name_tr' => 'İngilizce',
            'flag' => '🇬🇧', 'direction' => 'ltr', 'locale' => 'en_US', 'fallback_lang' => 'tr',
            'is_active' => 1, 'is_default' => 1, 'is_admin_enabled' => 1, 'weight' => 1,
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
            'ui_strings' => [],
        ],
        [
            'id' => 2, 'code' => 'tr', 'name_native' => 'Türkçe', 'name_tr' => 'Türkçe',
            'flag' => '🇹🇷', 'direction' => 'ltr', 'locale' => 'tr_TR', 'fallback_lang' => 'en',
            'is_active' => 1, 'is_default' => 0, 'is_admin_enabled' => 1, 'weight' => 2,
            'url_slugs' => ['about' => 'hakkimda', 'field_work' => 'saha-rotalari', 'privacy_notice' => 'aydinlatma-metni', 'explicit_consent' => 'acik-riza-metni', 'projects' => 'projeler', 'series' => 'seriler', 'single' => 'tekil'],
            'ui_strings' => [],
        ],
    ];
    return $cache;
}

/**
 * Ön yüzde (sitede) aktif olan dil satırlarını döndürür
 */
function get_active_languages(bool $refresh = false): array {
    $all = get_all_languages($refresh);
    $active = array_values(array_filter($all, fn($l) => !empty($l['is_active'])));
    if (empty($active) && !empty($all)) {
        return [$all[0]];
    }
    return $active;
}

/**
 * Sitede aktif olan dil kodları listesi (Örn: ['en', 'tr'])
 */
function get_supported_languages(bool $refresh = false): array {
    $active = get_active_languages($refresh);
    $codes = array_values(array_unique(array_map(fn($l) => $l['code'], $active)));
    return !empty($codes) ? $codes : ['en', 'tr'];
}

/**
 * Sistemde kayıtlı tüm (aktif + pasif) dil kodları
 */
function get_all_language_codes(bool $refresh = false): array {
    $all = get_all_languages($refresh);
    return array_values(array_unique(array_map(fn($l) => $l['code'], $all)));
}

/**
 * Sitenin varsayılan (kök) dil kodunu döndürür
 */
function get_default_language(): string {
    $active = get_active_languages();
    foreach ($active as $l) {
        if (!empty($l['is_default'])) {
            return $l['code'];
        }
    }
    $setting_def = get_setting('default_site_lang', '');
    $active_codes = array_column($active, 'code');
    if ($setting_def !== '' && in_array($setting_def, $active_codes, true)) {
        return $setting_def;
    }
    return $active[0]['code'] ?? 'en';
}

/**
 * Belirli bir dil kodunun detaylı bilgisini döndürür
 */
function get_language_info(string $code): ?array {
    $code = strtolower(trim($code));
    foreach (get_all_languages() as $l) {
        if ($l['code'] === $code) {
            return $l;
        }
    }
    return null;
}

/**
 * Sabit sayfalar için dile göre kanonik slug döndürür (about, contact, blog, field_work, privacy_notice, explicit_consent, projects, series, single)
 */
function get_static_slug(string $page_key, string $lang = 'en'): string {
    $lang = strtolower(trim($lang));
    // 1. Önce settings tablosundaki özel slug ayarına bak
    $custom_setting = trim(get_setting("static_slug_{$page_key}_{$lang}", ''));
    if ($custom_setting !== '') {
        return trim($custom_setting, '/');
    }

    // 2. Portfolyo kategorilerinden biriyse categories tablosuna bak
    if (in_array($page_key, ['projects', 'project', 'series', 'single'], true)) {
        try {
            $pdo = DB::getConnection();
            $cat_keys = ($page_key === 'projects' || $page_key === 'project') ? ['project', 'projects', 'portfolio'] : [$page_key];
            $in_placeholders = implode(',', array_fill(0, count($cat_keys), '?'));
            $st = $pdo->prepare("SELECT * FROM `categories` WHERE `key_slug` IN ({$in_placeholders}) ORDER BY `weight` ASC LIMIT 1");
            $st->execute($cat_keys);
            $cat = $st->fetch(PDO::FETCH_ASSOC);
            if ($cat) {
                $col = 'slug_' . $lang;
                if (!empty($cat[$col])) {
                    return trim($cat[$col], '/');
                }
                if ($lang === 'tr' && !empty($cat['slug_tr'])) return trim($cat['slug_tr'], '/');
                if (!empty($cat['slug_en'])) return trim($cat['slug_en'], '/');
            }
        } catch (\Throwable $e) {}
    }

    $defaults_en = [
        'about' => 'about',
        'contact' => 'contact',
        'blog' => 'blog',
        'field_work' => 'field-work',
        'privacy_notice' => 'privacy-notice',
        'explicit_consent' => 'explicit-consent',
        'projects' => 'projects',
        'series' => 'series',
        'single' => 'single',
    ];
    $defaults_tr = [
        'about' => 'hakkimda',
        'contact' => 'iletisim',
        'blog' => 'blog',
        'field_work' => 'saha-rotalari',
        'privacy_notice' => 'aydinlatma-metni',
        'explicit_consent' => 'acik-riza-metni',
        'projects' => 'projeler',
        'series' => 'seriler',
        'single' => 'tekil',
    ];
    $info = get_language_info($lang);
    if ($info && !empty($info['url_slugs'][$page_key])) {
        return $info['url_slugs'][$page_key];
    }
    if ($lang === 'tr') {
        return $defaults_tr[$page_key] ?? ($defaults_en[$page_key] ?? $page_key);
    }
    return $defaults_en[$page_key] ?? $page_key;
}

/**
 * Sabit sayfalar ve bölümler için dile göre özelleştirilmiş sayfa başlığını döndürür
 */
function get_page_custom_title(string $page_key, string $lang = 'en', string $fallback = ''): string {
    $lang = strtolower(trim($lang));
    $custom = trim(get_setting("{$page_key}_title_{$lang}", ''));
    if ($custom !== '') {
        return $custom;
    }
    $custom2 = trim(get_setting("page_title_{$page_key}_{$lang}", ''));
    if ($custom2 !== '') {
        return $custom2;
    }
    $defaults_tr = [
        'about' => 'Hakkımda',
        'contact' => 'İletişim',
        'blog' => 'Blog',
        'field_work' => 'Saha Rotaları',
        'privacy_notice' => 'Aydınlatma Metni',
        'explicit_consent' => 'Açık Rıza Metni',
        'projects' => 'Portfolyo',
        'series' => 'Seriler',
        'single' => 'Tekil',
    ];
    $defaults_en = [
        'about' => 'About Me',
        'contact' => 'Contact',
        'blog' => 'Blog',
        'field_work' => 'Field Work',
        'privacy_notice' => 'Privacy Notice',
        'explicit_consent' => 'Explicit Consent',
        'projects' => 'Portfolio',
        'series' => 'Series',
        'single' => 'Single',
    ];
    if ($fallback !== '') {
        return $fallback;
    }
    return ($lang === 'tr')
        ? ($defaults_tr[$page_key] ?? ucfirst($page_key))
        : ($defaults_en[$page_key] ?? ucfirst($page_key));
}

/**
 * Yeni bir dil eklendiğinde içerik tablolarında ilgili dil sütunlarını güvenli şekilde açar
 */
function ensure_language_columns(string $code): void {
    $code = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($code)));
    if ($code === '' || in_array($code, ['en', 'tr'], true)) {
        return;
    }
    try {
        $pdo = DB::getConnection();
        $is_sqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        $text_type = $is_sqlite ? "TEXT" : "MEDIUMTEXT";

        $tables_spec = [
            'items' => [
                "slug_{$code}"        => "VARCHAR(191) NULL",
                "title_{$code}"       => "VARCHAR(255) NULL",
                "description_{$code}" => "TEXT NULL",
                "content_{$code}"     => "{$text_type} NULL",
                "location_{$code}"    => "VARCHAR(255) NULL",
            ],
            'categories' => [
                "name_{$code}"        => "VARCHAR(128) NULL",
                "slug_{$code}"        => "VARCHAR(128) NULL",
                "description_{$code}" => "{$text_type} NULL",
            ],
            'menus' => [
                "title_{$code}"       => "VARCHAR(255) NULL",
                "url_{$code}"         => "VARCHAR(255) NULL",
            ],
            'homepage_slides' => [
                "title_{$code}"       => "VARCHAR(255) NULL",
                "description_{$code}" => "{$text_type} NULL",
            ],
        ];

        foreach ($tables_spec as $table => $cols_to_add) {
            $existing = [];
            if ($is_sqlite) {
                $q = $pdo->query("PRAGMA table_info(`{$table}`)")->fetchAll();
                foreach ($q as $row) $existing[] = $row['name'];
            } else {
                $q = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
                foreach ($q as $row) $existing[] = $row['Field'];
            }
            foreach ($cols_to_add as $col_name => $col_def) {
                if (!in_array($col_name, $existing, true)) {
                    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col_name}` {$col_def};");
                }
            }
        }
        sync_standard_categories_and_menus($code);
    } catch (\Throwable $e) {}
}

/**
 * Hazır Dil Şablonları Kataloğu (Tek tıkla ekleme veya sıfırlama için)
 */
function get_preset_language_catalog(): array {
    return [
        'tr' => [
            'code' => 'tr', 'name_native' => 'Türkçe', 'name_tr' => 'Türkçe',
            'flag' => '🇹🇷', 'direction' => 'ltr', 'locale' => 'tr_TR', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'hakkimda', 'field_work' => 'saha-rotalari', 'privacy_notice' => 'aydinlatma-metni', 'explicit_consent' => 'acik-riza-metni', 'projects' => 'projeler', 'series' => 'seriler', 'single' => 'tekil'],
        ],
        'en' => [
            'code' => 'en', 'name_native' => 'English', 'name_tr' => 'İngilizce',
            'flag' => '🇬🇧', 'direction' => 'ltr', 'locale' => 'en_US', 'fallback_lang' => 'tr',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'hy' => [
            'code' => 'hy', 'name_native' => 'Հայերեն', 'name_tr' => 'Ermenice',
            'flag' => '🇦🇲', 'direction' => 'ltr', 'locale' => 'hy_AM', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'zh' => [
            'code' => 'zh', 'name_native' => '简体中文', 'name_tr' => 'Çince (Basitleştirilmiş - Standart)',
            'flag' => '🇨🇳', 'direction' => 'ltr', 'locale' => 'zh_CN', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'fr' => [
            'code' => 'fr', 'name_native' => 'Français', 'name_tr' => 'Fransızca',
            'flag' => '🇫🇷', 'direction' => 'ltr', 'locale' => 'fr_FR', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'a-propos', 'field_work' => 'terrain', 'privacy_notice' => 'confidentialite', 'explicit_consent' => 'consentement', 'projects' => 'projets', 'series' => 'series', 'single' => 'unique'],
        ],
        'de' => [
            'code' => 'de', 'name_native' => 'Deutsch', 'name_tr' => 'Almanca',
            'flag' => '🇩🇪', 'direction' => 'ltr', 'locale' => 'de_DE', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'ueber-mich', 'field_work' => 'feldarbeit', 'privacy_notice' => 'datenschutz', 'explicit_consent' => 'einwilligung', 'projects' => 'projekte', 'series' => 'serien', 'single' => 'einzel'],
        ],
        'es' => [
            'code' => 'es', 'name_native' => 'Español', 'name_tr' => 'İspanyolca',
            'flag' => '🇪🇸', 'direction' => 'ltr', 'locale' => 'es_ES', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'sobre-mi', 'field_work' => 'trabajo-de-campo', 'privacy_notice' => 'privacidad', 'explicit_consent' => 'consentimiento', 'projects' => 'proyectos', 'series' => 'series', 'single' => 'individual'],
        ],
        'ar' => [
            'code' => 'ar', 'name_native' => 'العربية', 'name_tr' => 'Arapça',
            'flag' => '🇸🇦', 'direction' => 'rtl', 'locale' => 'ar_SA', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'ru' => [
            'code' => 'ru', 'name_native' => 'Русский', 'name_tr' => 'Rusça',
            'flag' => '🇷🇺', 'direction' => 'ltr', 'locale' => 'ru_RU', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'el' => [
            'code' => 'el', 'name_native' => 'Ελληνικά', 'name_tr' => 'Yunanca',
            'flag' => '🇬🇷', 'direction' => 'ltr', 'locale' => 'el_GR', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'it' => [
            'code' => 'it', 'name_native' => 'Italiano', 'name_tr' => 'İtalyanca',
            'flag' => '🇮🇹', 'direction' => 'ltr', 'locale' => 'it_IT', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'chi-sono', 'field_work' => 'sul-campo', 'privacy_notice' => 'privacy', 'explicit_consent' => 'consenso', 'projects' => 'progetti', 'series' => 'serie', 'single' => 'singole'],
        ],
        'ja' => [
            'code' => 'ja', 'name_native' => '日本語', 'name_tr' => 'Japonca',
            'flag' => '🇯🇵', 'direction' => 'ltr', 'locale' => 'ja_JP', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'pt' => [
            'code' => 'pt', 'name_native' => 'Português', 'name_tr' => 'Portekizce',
            'flag' => '🇵🇹', 'direction' => 'ltr', 'locale' => 'pt_PT', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'sobre-mim', 'field_work' => 'trabalho-de-campo', 'privacy_notice' => 'aviso-de-privacidade', 'explicit_consent' => 'consentimento-explicito', 'projects' => 'projetos', 'series' => 'series', 'single' => 'individual'],
        ],
        'ko' => [
            'code' => 'ko', 'name_native' => '한국어', 'name_tr' => 'Korece',
            'flag' => '🇰🇷', 'direction' => 'ltr', 'locale' => 'ko_KR', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'hi' => [
            'code' => 'hi', 'name_native' => 'हिन्दी', 'name_tr' => 'Hintçe',
            'flag' => '🇮🇳', 'direction' => 'ltr', 'locale' => 'hi_IN', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'ur' => [
            'code' => 'ur', 'name_native' => 'اردو', 'name_tr' => 'Urduca',
            'flag' => '🇵🇰', 'direction' => 'rtl', 'locale' => 'ur_PK', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'about', 'field_work' => 'field-work', 'privacy_notice' => 'privacy-notice', 'explicit_consent' => 'explicit-consent', 'projects' => 'projects', 'series' => 'series', 'single' => 'single'],
        ],
        'ms' => [
            'code' => 'ms', 'name_native' => 'Bahasa Melayu', 'name_tr' => 'Malayca',
            'flag' => '🇲🇾', 'direction' => 'ltr', 'locale' => 'ms_MY', 'fallback_lang' => 'en',
            'url_slugs' => ['about' => 'tentang-saya', 'field_work' => 'kerja-lapangan', 'privacy_notice' => 'notis-privasi', 'explicit_consent' => 'persetujuan-nyata', 'projects' => 'projek', 'series' => 'siri', 'single' => 'tunggal'],
        ],
    ];
}
/**
 * Herhangi bir dil kodunun (ISO) hedef dildeki yerelleştirilmiş adını döndürür.
 * Örneğin: get_localized_language_name('pt', 'en') -> 'Portuguese'
 *         get_localized_language_name('pt', 'tr') -> 'Portekizce'
 *         get_localized_language_name('en', 'ko') -> '영어'
 */
function get_localized_language_name(string $code, ?string $display_lang = null): string {
    global $admin_lang;
    $code = strtolower(trim($code));
    $display = strtolower(trim($display_lang ?: ($admin_lang ?? 'tr')));
    if ($display === '') {
        $display = 'tr';
    }

    static $matrix = [
        'tr' => [
            'tr' => 'Türkçe', 'en' => 'İngilizce', 'pt' => 'Portekizce', 'ko' => 'Korece', 'hi' => 'Hintçe',
            'ur' => 'Urduca', 'ms' => 'Malayca', 'fr' => 'Fransızca', 'de' => 'Almanca', 'es' => 'İspanyolca',
            'it' => 'İtalyanca', 'ru' => 'Rusça', 'zh' => 'Çince', 'ja' => 'Japonca', 'ar' => 'Arapça',
            'el' => 'Yunanca', 'hy' => 'Ermenice'
        ],
        'en' => [
            'tr' => 'Turkish', 'en' => 'English', 'pt' => 'Portuguese', 'ko' => 'Korean', 'hi' => 'Hindi',
            'ur' => 'Urdu', 'ms' => 'Malay', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish',
            'it' => 'Italian', 'ru' => 'Russian', 'zh' => 'Chinese', 'ja' => 'Japanese', 'ar' => 'Arabic',
            'el' => 'Greek', 'hy' => 'Armenian'
        ],
        'pt' => [
            'tr' => 'Turco', 'en' => 'Inglês', 'pt' => 'Português', 'ko' => 'Coreano', 'hi' => 'Hindi',
            'ur' => 'Urdu', 'ms' => 'Malaio', 'fr' => 'Francês', 'de' => 'Alemão', 'es' => 'Espanhol',
            'it' => 'Italiano', 'ru' => 'Russo', 'zh' => 'Chinês', 'ja' => 'Japonês', 'ar' => 'Árabe',
            'el' => 'Grego', 'hy' => 'Armênio'
        ],
        'ko' => [
            'tr' => '튀르키예어', 'en' => '영어', 'pt' => '포르투갈어', 'ko' => '한국어', 'hi' => '힌디어',
            'ur' => '우르두어', 'ms' => '말레이어', 'fr' => '프랑스어', 'de' => '독일어', 'es' => '스페인어',
            'it' => '이탈리아어', 'ru' => '러시아어', 'zh' => '중국어', 'ja' => '일본어', 'ar' => '아랍어',
            'el' => '그리스어', 'hy' => '아르메니아어'
        ],
        'hi' => [
            'tr' => 'तुर्की', 'en' => 'अंग्रेज़ी', 'pt' => 'पुर्तगाली', 'ko' => 'कोरियाई', 'hi' => 'हिन्दी',
            'ur' => 'उर्दू', 'ms' => 'मलय', 'fr' => 'फ़्रेंच', 'de' => 'जर्मन', 'es' => 'स्पैनिश',
            'it' => 'इतालवी', 'ru' => 'रूसी', 'zh' => 'चीनी', 'ja' => 'जापानी', 'ar' => 'अरबी',
            'el' => 'ग्रीक', 'hy' => 'अर्मेनियाई'
        ],
        'ur' => [
            'tr' => 'ترکی', 'en' => 'انگریزی', 'pt' => 'پرتگالی', 'ko' => 'کوریائی', 'hi' => 'ہندی',
            'ur' => 'اردو', 'ms' => 'مالائی', 'fr' => 'فرانسیسی', 'de' => 'جرمن', 'es' => 'ہسپانوی',
            'it' => 'اطالوی', 'ru' => 'روسی', 'zh' => 'چینی', 'ja' => 'جاپانی', 'ar' => 'عربی',
            'el' => 'یونانی', 'hy' => 'آرمینیائی'
        ],
        'ms' => [
            'tr' => 'Bahasa Turki', 'en' => 'Bahasa Inggeris', 'pt' => 'Bahasa Portugis', 'ko' => 'Bahasa Korea', 'hi' => 'Bahasa Hindi',
            'ur' => 'Bahasa Urdu', 'ms' => 'Bahasa Melayu', 'fr' => 'Bahasa Perancis', 'de' => 'Bahasa Jerman', 'es' => 'Bahasa Sepanyol',
            'it' => 'Bahasa Itali', 'ru' => 'Bahasa Rusia', 'zh' => 'Bahasa Cina', 'ja' => 'Bahasa Jepun', 'ar' => 'Bahasa Arab',
            'el' => 'Bahasa Greek', 'hy' => 'Bahasa Armenia'
        ],
        'fr' => [
            'tr' => 'Turc', 'en' => 'Anglais', 'pt' => 'Portugais', 'ko' => 'Coréen', 'hi' => 'Hindi',
            'ur' => 'Ourdou', 'ms' => 'Malais', 'fr' => 'Français', 'de' => 'Allemand', 'es' => 'Espagnol',
            'it' => 'Italien', 'ru' => 'Russe', 'zh' => 'Chinois', 'ja' => 'Japonais', 'ar' => 'Arabe',
            'el' => 'Grec', 'hy' => 'Arménien'
        ],
        'de' => [
            'tr' => 'Türkisch', 'en' => 'Englisch', 'pt' => 'Portugiesisch', 'ko' => 'Koreanisch', 'hi' => 'Hindi',
            'ur' => 'Urdu', 'ms' => 'Malaiisch', 'fr' => 'Französisch', 'de' => 'Deutsch', 'es' => 'Spanisch',
            'it' => 'Italienisch', 'ru' => 'Russisch', 'zh' => 'Chinesisch', 'ja' => 'Japanisch', 'ar' => 'Arabisch',
            'el' => 'Griechisch', 'hy' => 'Armenisch'
        ],
        'es' => [
            'tr' => 'Turco', 'en' => 'Inglés', 'pt' => 'Portugués', 'ko' => 'Coreano', 'hi' => 'Hindi',
            'ur' => 'Urdu', 'ms' => 'Malayo', 'fr' => 'Francés', 'de' => 'Alemán', 'es' => 'Español',
            'it' => 'Italiano', 'ru' => 'Ruso', 'zh' => 'Chino', 'ja' => 'Japonés', 'ar' => 'Árabe',
            'el' => 'Griego', 'hy' => 'Armenio'
        ],
        'it' => [
            'tr' => 'Turco', 'en' => 'Inglese', 'pt' => 'Portoghese', 'ko' => 'Coreano', 'hi' => 'Hindi',
            'ur' => 'Urdu', 'ms' => 'Malese', 'fr' => 'Francese', 'de' => 'Tedesco', 'es' => 'Spagnolo',
            'it' => 'Italiano', 'ru' => 'Russo', 'zh' => 'Cinese', 'ja' => 'Giapponese', 'ar' => 'Arabo',
            'el' => 'Greco', 'hy' => 'Armeno'
        ],
        'ru' => [
            'tr' => 'Турецкий', 'en' => 'Английский', 'pt' => 'Португальский', 'ko' => 'Корейский', 'hi' => 'Хинди',
            'ur' => 'Урду', 'ms' => 'Малайский', 'fr' => 'Французский', 'de' => 'Немецкий', 'es' => 'Испанский',
            'it' => 'Итальянский', 'ru' => 'Русский', 'zh' => 'Китайский', 'ja' => 'Японский', 'ar' => 'Арабский',
            'el' => 'Греческий', 'hy' => 'Армянский'
        ],
        'zh' => [
            'tr' => '土耳其语', 'en' => '英语', 'pt' => '葡萄牙语', 'ko' => '韩语', 'hi' => '印地语',
            'ur' => '乌尔都语', 'ms' => '马来语', 'fr' => '法语', 'de' => '德语', 'es' => '西班牙语',
            'it' => '意大利语', 'ru' => '俄语', 'zh' => '简体中文', 'ja' => '日语', 'ar' => '阿拉伯语',
            'el' => '希腊语', 'hy' => '亚美尼亚语'
        ],
        'ja' => [
            'tr' => 'トルコ語', 'en' => '英語', 'pt' => 'ポルトガル語', 'ko' => '韓国語', 'hi' => 'ヒンディー語',
            'ur' => 'ウルドゥー語', 'ms' => 'マレー語', 'fr' => 'フランス語', 'de' => 'ドイツ語', 'es' => 'スペイン語',
            'it' => 'イタリア語', 'ru' => 'ロシア語', 'zh' => '中国語', 'ja' => '日本語', 'ar' => 'アラビア語',
            'el' => 'ギリシャ語', 'hy' => 'アルメニア語'
        ],
        'ar' => [
            'tr' => 'التركية', 'en' => 'الإنجليزية', 'pt' => 'البرتغالية', 'ko' => 'الكورية', 'hi' => 'الهندية',
            'ur' => 'الأردية', 'ms' => 'الملايو', 'fr' => 'الفرنسية', 'de' => 'الألمانية', 'es' => 'الإسبانية',
            'it' => 'الإيطالية', 'ru' => 'الروسية', 'zh' => 'الصينية', 'ja' => 'اليابانية', 'ar' => 'العربية',
            'el' => 'اليونانية', 'hy' => 'الأرمنية'
        ],
        'el' => [
            'tr' => 'Τουρκικά', 'en' => 'Αγγλικά', 'pt' => 'Πορτογαλικά', 'ko' => 'Κορεατικά', 'hi' => 'Χίντι',
            'ur' => 'Ούρντου', 'ms' => 'Μαλαϊκά', 'fr' => 'Γαλλικά', 'de' => 'Γερμανικά', 'es' => 'Ισπανικά',
            'it' => 'Ιταλικά', 'ru' => 'Ρωσικά', 'zh' => 'Κινεζικά', 'ja' => 'Ιαπωνικά', 'ar' => 'Αραβικά',
            'el' => 'Ελληνικά', 'hy' => 'Αρμενικά'
        ],
        'hy' => [
            'tr' => 'Թուրքերեն', 'en' => 'Անգլերեն', 'pt' => 'Պորտուգալերեն', 'ko' => 'Կորեերեն', 'hi' => 'Հինդի',
            'ur' => 'Ուրդու', 'ms' => 'Մալայերեն', 'fr' => 'Ֆրանսերեն', 'de' => 'Գերմաներեն', 'es' => 'Իսպաներեն',
            'it' => 'Իտալերեն', 'ru' => 'Ռուսերեն', 'zh' => 'Չինարեն', 'ja' => 'Ճապոներեն', 'ar' => 'Արաբերեն',
            'el' => 'Հունարեն', 'hy' => 'Հայերեն'
        ]
    ];

    if (isset($matrix[$display][$code])) {
        return $matrix[$display][$code];
    }
    if (isset($matrix['en'][$code])) {
        return $matrix['en'][$code];
    }
    if (isset($matrix['tr'][$code])) {
        return $matrix['tr'][$code];
    }
    $info = get_language_info($code);
    return $info['name_native'] ?? $info['name_tr'] ?? strtoupper($code);
}

/**
 * Öğe için geçerli dile göre kalıcı bağlantıyı (slug) döndürür
 */
function item_slug(array $item, string $lang = 'en'): string {
    $custom_key = 'slug_' . $lang;
    if (!empty($item[$custom_key])) {
        return $item[$custom_key];
    }
    $info = get_language_info($lang);
    $fb = $info['fallback_lang'] ?? 'en';
    if (!empty($item['slug_' . $fb])) {
        return $item['slug_' . $fb];
    }
    if (!empty($item['slug_en'])) {
        return $item['slug_en'];
    }
    if (!empty($item['slug_tr'])) {
        return $item['slug_tr'];
    }
    return $item['slug'] ?? '';
}

/**
 * Öğe için geçerli dile göre herhangi bir alanı (title, description, content, location) yedek dil (fallback) desteğiyle döndürür
 */
function item_field(array $item, string $field, string $lang = 'en'): string {
    $key = $field . '_' . $lang;
    if (isset($item[$key]) && trim((string)$item[$key]) !== '') {
        return (string)$item[$key];
    }
    $info = get_language_info($lang);
    $fb = $info['fallback_lang'] ?? 'en';
    if (isset($item[$field . '_' . $fb]) && trim((string)$item[$field . '_' . $fb]) !== '') {
        return (string)$item[$field . '_' . $fb];
    }
    if (isset($item[$field . '_en']) && trim((string)$item[$field . '_en']) !== '') {
        return (string)$item[$field . '_en'];
    }
    if (isset($item[$field . '_tr']) && trim((string)$item[$field . '_tr']) !== '') {
        return (string)$item[$field . '_tr'];
    }
    return (string)($item[$field] ?? '');
}

/**
 * Öğe için geçerli dile göre saf dil tabanlı kalıcı URL'yi üretir (/en/slug/ veya /tr/slug/)
 */
function item_url(array $item, string $lang = 'en'): string {
    $slug = item_slug($item, $lang);
    return url($lang . '/' . $slug . '/');
}

/**
 * Slayt veya harici bağlantı URL'sini geçerli dile göre çözer
 */
function resolve_slide_url(?string $raw_url, string $lang = 'en'): string {
    if (empty($raw_url)) return '';
    $raw_url = trim($raw_url);
    if (str_starts_with($raw_url, 'http://') || str_starts_with($raw_url, 'https://')) {
        return $raw_url;
    }
    $clean = trim($raw_url, '/');
    $parts = explode('/', $clean);
    $last_slug = end($parts);

    // Statik sayfalar
    if (in_array($last_slug, ['about', 'aboutme', 'hakkimda'])) {
        return url($lang . '/' . get_static_slug('about', $lang) . '/');
    }
    if (in_array($last_slug, ['map', 'field-work', 'field-map', 'saha-rotalari', 'rotalar'])) {
        return url($lang . '/' . get_static_slug('field_work', $lang) . '/');
    }
    if (in_array($last_slug, ['projects', 'projeler'])) {
        return url($lang . '/' . get_static_slug('projects', $lang) . '/');
    }
    if (in_array($last_slug, ['series', 'seriler'])) {
        return url($lang . '/' . get_static_slug('series', $lang) . '/');
    }
    if (in_array($last_slug, ['single', 'tekil'])) {
        return url($lang . '/' . get_static_slug('single', $lang) . '/');
    }

    // DB'den kategori veya öğe ara
    try {
        $pdo = DB::getConnection();
        $cat_st = $pdo->prepare("SELECT * FROM `categories` WHERE `slug_en` = ? OR `slug_tr` = ? OR `key_slug` = ? LIMIT 1");
        $cat_st->execute([$last_slug, $last_slug, $last_slug]);
        $cat_row = $cat_st->fetch(PDO::FETCH_ASSOC);
        if ($cat_row) {
            $c_slug = !empty($cat_row['slug_' . $lang]) ? $cat_row['slug_' . $lang] : (($lang === 'tr') ? ($cat_row['slug_tr'] ?: $cat_row['slug_en']) : ($cat_row['slug_en'] ?: $cat_row['slug_tr']));
            return url($lang . '/' . $c_slug . '/');
        }

        $st = $pdo->prepare("SELECT * FROM `items` WHERE `slug` = ? OR `slug_en` = ? OR `slug_tr` = ? LIMIT 1");
        $st->execute([$last_slug, $last_slug, $last_slug]);
        $it = $st->fetch(PDO::FETCH_ASSOC);
        if ($it) {
            return item_url($it, $lang);
        }
    } catch (\Throwable $e) {}

    // Dil öneki varsa koru veya değiştir
    $first = $parts[0] ?? '';
    if (in_array($first, get_all_language_codes())) {
        $parts[0] = $lang;
        return url(implode('/', $parts) . '/');
    }

    return url($lang . '/' . $clean . '/');
}

function format_date(?string $date_str, string $lang = 'en'): string {
    if (!$date_str) return '';
    $timestamp = strtotime($date_str);
    if (!$timestamp) return $date_str;

    $lang = strtolower(trim($lang));
    $month_maps = [
        'tr' => ['01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan', '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos', '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'],
        'hy' => ['01' => 'Հունվար', '02' => 'Փետրվար', '03' => 'Մարտ', '04' => 'Ապրիլ', '05' => 'Մայիս', '06' => 'Հունիս', '07' => 'Հուլիս', '08' => 'Օգոստոս', '09' => 'Սեպտեմբեր', '10' => 'Հոկտեմբեր', '11' => 'Նոյեմբեր', '12' => 'Դեկտեմբեր'],
        'ku' => ['01' => 'Rêbendan', '02' => 'Reşemî', '03' => 'Adar', '04' => 'Avrêl', '05' => 'Gulan', '06' => 'Pûşper', '07' => 'Tîrmeh', '08' => 'Gelawêj', '09' => 'Rezber', '10' => 'Kewçêr', '11' => 'Sermawez', '12' => 'Berfanbar'],
        'fr' => ['01' => 'janvier', '02' => 'février', '03' => 'mars', '04' => 'avril', '05' => 'mai', '06' => 'juin', '07' => 'juillet', '08' => 'août', '09' => 'septembre', '10' => 'octobre', '11' => 'novembre', '12' => 'décembre'],
        'de' => ['01' => 'Januar', '02' => 'Februar', '03' => 'März', '04' => 'April', '05' => 'Mai', '06' => 'Juni', '07' => 'Juli', '08' => 'August', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Dezember'],
        'es' => ['01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril', '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto', '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'],
        'it' => ['01' => 'gennaio', '02' => 'febbraio', '03' => 'marzo', '04' => 'aprile', '05' => 'maggio', '06' => 'giugno', '07' => 'luglio', '08' => 'agosto', '09' => 'settembre', '10' => 'ottobre', '11' => 'novembre', '12' => 'dicembre'],
        'ru' => ['01' => 'января', '02' => 'февраля', '03' => 'марта', '04' => 'апреля', '05' => 'мая', '06' => 'июня', '07' => 'июля', '08' => 'августа', '09' => 'сентября', '10' => 'октября', '11' => 'ноября', '12' => 'декабря'],
        'ar' => ['01' => 'يناير', '02' => 'فبراير', '03' => 'مارس', '04' => 'أبريل', '05' => 'مايو', '06' => 'يونيو', '07' => 'يوليو', '08' => 'أغسطس', '09' => 'سبتمبر', '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'],
        'el' => ['01' => 'Ιανουαρίου', '02' => 'Φεβρουαρίου', '03' => 'Μαρτίου', '04' => 'Απριλίου', '05' => 'Μαΐου', '06' => 'Ιουνίου', '07' => 'Ιουλίου', '08' => 'Αυγούστου', '09' => 'Σεπτεμβρίου', '10' => 'Οκτωβρίου', '11' => 'Νοεμβρίου', '12' => 'Δεκεμβρίου'],
        'pt' => ['01' => 'janeiro', '02' => 'fevereiro', '03' => 'março', '04' => 'abril', '05' => 'maio', '06' => 'junho', '07' => 'julho', '08' => 'agosto', '09' => 'setembro', '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'],
        'hi' => ['01' => 'जनवरी', '02' => 'फ़रवरी', '03' => 'मार्च', '04' => 'अप्रैल', '05' => 'मई', '06' => 'जून', '07' => 'जुलाई', '08' => 'अगस्त', '09' => 'सितंबर', '10' => 'अक्टूबर', '11' => 'नवंबर', '12' => 'दिसंबर'],
        'ur' => ['01' => 'جنوری', '02' => 'فروری', '03' => 'مارچ', '04' => 'اپریل', '05' => 'مئی', '06' => 'جون', '07' => 'جولائی', '08' => 'اگست', '09' => 'ستمبر', '10' => 'اکتوبر', '11' => 'نومبر', '12' => 'دسمبر'],
        'ms' => ['01' => 'Januari', '02' => 'Februari', '03' => 'Mac', '04' => 'April', '05' => 'Mei', '06' => 'Jun', '07' => 'Julai', '08' => 'Ogos', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Disember'],
    ];

    if ($lang === 'ja' || $lang === 'zh') {
        return date('Y年n月j日', $timestamp);
    }
    if ($lang === 'ko') {
        return date('Y년 n월 j일', $timestamp);
    }

    if (isset($month_maps[$lang])) {
        $d = (int)date('d', $timestamp);
        $m = $month_maps[$lang][date('m', $timestamp)] ?? '';
        $y = date('Y', $timestamp);
        if ($lang === 'de') {
            return "{$d}. {$m} {$y}";
        }
        if ($lang === 'pt') {
            return "{$d} de {$m} de {$y}";
        }
        return "{$d} {$m} {$y}";
    }

    return date('F j, Y', $timestamp);
}

/**
 * Hazır dil arayüz kataloğunu yükler (includes/i18n_ui/{lang}.php).
 * Anahtar: İngilizce kaynak metin → Değer: hedef dildeki çeviri.
 * Hem site ön yüzü (site_t) hem de yönetim paneli (__a, çıktı çevirici) bu kataloğu paylaşır.
 */
function ui_catalog(string $lang): array {
    static $cache = [];
    $lang = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($lang)));
    if ($lang === '') {
        return [];
    }
    if (!isset($cache[$lang])) {
        $file = __DIR__ . '/i18n_ui/' . $lang . '.php';
        $data = is_file($file) ? require $file : [];
        $cache[$lang] = is_array($data) ? $data : [];
    }
    return $cache[$lang];
}

/**
 * İngilizce kaynak metni verilen dile çevirir (katalogda yoksa İngilizce döner).
 */
function ui_translate(string $en, string $lang): string {
    $lang = strtolower(trim($lang));
    if ($lang === 'en' || $lang === '') {
        return $en;
    }
    $cat = ui_catalog($lang);
    if (isset($cat[$en])) {
        return $cat[$en];
    }
    $trimmed = trim($en);
    if ($trimmed !== $en && isset($cat[$trimmed])) {
        return str_replace($trimmed, $cat[$trimmed], $en);
    }
    return $en;
}

/**
 * Site ön yüzü için dile duyarlı sabit metin: Türkçe / İngilizce metni verip
 * diğer tüm hazır dillerde katalogdan (İngilizce kaynak anahtarıyla) çeviri döndürür.
 */
function site_t(string $tr, string $en, ?string $lang = null): string {
    $lang = strtolower(trim($lang ?? ($GLOBALS['lang'] ?? 'en')));
    if ($lang === 'tr') {
        return $tr;
    }
    if ($lang === 'en' || $lang === '') {
        return $en;
    }
    return ui_translate($en, $lang);
}

/**
 * Bir dil için bakılacak diller sırası: kendisi → yedek dili → İngilizce → Türkçe.
 */
function lang_chain(string $lang): array {
    $lang = strtolower(trim($lang));
    $chain = [$lang];
    $info = get_language_info($lang);
    if (!empty($info['fallback_lang'])) $chain[] = (string)$info['fallback_lang'];
    $chain[] = 'en';
    $chain[] = 'tr';
    return array_values(array_unique(array_filter($chain, 'strlen')));
}

/**
 * Dile göre saklanan bir ayar ({taban}_{dil}): o dilde boşsa yedek dil, sonra İngilizce, sonra Türkçe.
 * Örn: setting_l('footer_tagline', 'hy') → footer_tagline_hy, yoksa footer_tagline_en …
 */
function setting_l(string $base, ?string $lang = null, string $default = ''): string {
    $lang = strtolower(trim($lang ?? ($GLOBALS['lang'] ?? 'en')));
    get_setting('__warm__'); // önbelleği doldur
    $cache = $GLOBALS['site_settings_cache'] ?? [];
    // Türkçe ve İngilizce: kayıtlı değer (bilerek boş bırakılmışsa boş) ya da varsayılan — eski davranış
    if (in_array($lang, ['tr', 'en'], true)) {
        return array_key_exists($base . '_' . $lang, $cache) ? (string)$cache[$base . '_' . $lang] : $default;
    }
    // Diğer diller: boşsa henüz çevrilmemiş sayılır; yedek dile, sonra İngilizce ve Türkçeye bakılır
    foreach (lang_chain($lang) as $c) {
        $v = (string)($cache[$base . '_' . $c] ?? '');
        if (trim($v) !== '') return $v;
    }
    return $default;
}

/**
 * Dile göre ayarları kaydeder: $_POST'ta {taban}_{dil} olarak gelen her etkin dili yazar.
 * $bases: ['footer_tagline', …]  ·  $sanitize: isteğe bağlı fn(string $value, string $base): string
 */
function save_settings_l(array $bases, array $post, ?callable $sanitize = null): int {
    $n = 0;
    foreach (array_column(get_active_languages(), 'code') as $c) {
        foreach ($bases as $b) {
            $k = $b . '_' . $c;
            if (!array_key_exists($k, $post)) continue;
            $v = trim((string)$post[$k]);
            set_setting($k, $sanitize ? $sanitize($v, $b) : $v);
            $n++;
        }
    }
    return $n;
}

/**
 * Standart Portfolyo Kategorileri Çeviri Matrisi (Tüm 17 dil)
 */
function get_standard_category_matrix(): array {
    return [
        'project' => [
            'tr' => ['name' => 'Projeler', 'slug' => 'projeler'],
            'en' => ['name' => 'Projects', 'slug' => 'projects'],
            'fr' => ['name' => 'Projets', 'slug' => 'projets'],
            'zh' => ['name' => '专题项目', 'slug' => 'projects'],
            'de' => ['name' => 'Projekte', 'slug' => 'projekte'],
            'es' => ['name' => 'Proyectos', 'slug' => 'proyectos'],
            'it' => ['name' => 'Progetti', 'slug' => 'progetti'],
            'pt' => ['name' => 'Projetos', 'slug' => 'projetos'],
            'ru' => ['name' => 'Проекты', 'slug' => 'projects'],
            'ja' => ['name' => 'プロジェクト', 'slug' => 'projects'],
            'ko' => ['name' => '프로젝트', 'slug' => 'projects'],
            'ar' => ['name' => 'المشاريع', 'slug' => 'projects'],
            'hi' => ['name' => 'परियोजनाएं', 'slug' => 'projects'],
            'ur' => ['name' => 'منصوبے', 'slug' => 'projects'],
            'ms' => ['name' => 'Projek', 'slug' => 'projek'],
            'el' => ['name' => 'Έργα', 'slug' => 'projects'],
            'hy' => ['name' => 'Նախագծեր', 'slug' => 'projects'],
        ],
        'series' => [
            'tr' => ['name' => 'Seriler', 'slug' => 'seriler'],
            'en' => ['name' => 'Series', 'slug' => 'series'],
            'fr' => ['name' => 'Séries', 'slug' => 'series'],
            'zh' => ['name' => '系列作品', 'slug' => 'series'],
            'de' => ['name' => 'Serien', 'slug' => 'serien'],
            'es' => ['name' => 'Series', 'slug' => 'series'],
            'it' => ['name' => 'Serie', 'slug' => 'serie'],
            'pt' => ['name' => 'Séries', 'slug' => 'series'],
            'ru' => ['name' => 'Серии', 'slug' => 'series'],
            'ja' => ['name' => 'シリーズ', 'slug' => 'series'],
            'ko' => ['name' => '시리즈', 'slug' => 'series'],
            'ar' => ['name' => 'السلاسل', 'slug' => 'series'],
            'hi' => ['name' => 'श्रृंखला', 'slug' => 'series'],
            'ur' => ['name' => 'سلسلے', 'slug' => 'series'],
            'ms' => ['name' => 'Siri', 'slug' => 'siri'],
            'el' => ['name' => 'Σειρές', 'slug' => 'series'],
            'hy' => ['name' => 'Սերիա', 'slug' => 'series'],
        ],
        'single' => [
            'tr' => ['name' => 'Tekil', 'slug' => 'tekil'],
            'en' => ['name' => 'Single', 'slug' => 'single'],
            'fr' => ['name' => 'Photos Uniques', 'slug' => 'unique'],
            'zh' => ['name' => '单幅精选', 'slug' => 'single'],
            'de' => ['name' => 'Einzelbilder', 'slug' => 'einzel'],
            'es' => ['name' => 'Fotos Individuales', 'slug' => 'individual'],
            'it' => ['name' => 'Foto Singole', 'slug' => 'singole'],
            'pt' => ['name' => 'Fotografias Individuais', 'slug' => 'individual'],
            'ru' => ['name' => 'Одиночные кадры', 'slug' => 'single'],
            'ja' => ['name' => '単写真', 'slug' => 'single'],
            'ko' => ['name' => '싱글 포토', 'slug' => 'single'],
            'ar' => ['name' => 'صور فردية', 'slug' => 'single'],
            'hi' => ['name' => 'एकल चित्र', 'slug' => 'single'],
            'ur' => ['name' => 'انفرادی تصاویر', 'slug' => 'single'],
            'ms' => ['name' => 'Foto Tunggal', 'slug' => 'tunggal'],
            'el' => ['name' => 'Μεμονωμένες Φωτογραφίες', 'slug' => 'single'],
            'hy' => ['name' => 'Եզակի', 'slug' => 'single'],
        ],
        'selected' => [
            'tr' => ['name' => 'Seçki', 'slug' => 'secki'],
            'en' => ['name' => 'Selected', 'slug' => 'selected'],
            'fr' => ['name' => 'Sélection', 'slug' => 'selection'],
            'zh' => ['name' => '策展精选', 'slug' => 'selected'],
            'de' => ['name' => 'Kuration', 'slug' => 'auswahl'],
            'es' => ['name' => 'Selección', 'slug' => 'seleccion'],
            'it' => ['name' => 'Selezione', 'slug' => 'selezione'],
            'pt' => ['name' => 'Seleção', 'slug' => 'selecao'],
            'ru' => ['name' => 'Избранное', 'slug' => 'selected'],
            'ja' => ['name' => 'セレクション', 'slug' => 'selected'],
            'ko' => ['name' => '큐레이션 선별', 'slug' => 'selected'],
            'ar' => ['name' => 'مختارات', 'slug' => 'selected'],
            'hi' => ['name' => 'चयनित कार्य', 'slug' => 'selected'],
            'ur' => ['name' => 'منتخب کام', 'slug' => 'selected'],
            'ms' => ['name' => 'Pilihan', 'slug' => 'pilihan'],
            'el' => ['name' => 'Επιλογή', 'slug' => 'selected'],
            'hy' => ['name' => 'ընտրություն', 'slug' => 'selected'],
        ],
    ];
}

/**
 * Standart Başlık / Menü Öğeleri Çeviri Matrisi
 */
function get_standard_menu_matrix(): array {
    return [
        'about' => [
            'tr' => ['title' => 'Hakkımda', 'url' => 'tr/hakkimda/'],
            'en' => ['title' => 'About Me', 'url' => 'en/about/'],
            'fr' => ['title' => 'À Propos', 'url' => 'fr/a-propos/'],
            'zh' => ['title' => '关于我', 'url' => 'zh/about/'],
            'de' => ['title' => 'Über mich', 'url' => 'de/ueber-mich/'],
            'es' => ['title' => 'Sobre mí', 'url' => 'es/sobre-mi/'],
            'it' => ['title' => 'Chi sono', 'url' => 'it/chi-sono/'],
            'pt' => ['title' => 'Sobre mim', 'url' => 'pt/sobre-mim/'],
            'ru' => ['title' => 'Обо мне', 'url' => 'ru/about/'],
            'ja' => ['title' => 'プロフィール', 'url' => 'ja/about/'],
            'ko' => ['title' => '소개', 'url' => 'ko/about/'],
            'ar' => ['title' => 'عني', 'url' => 'ar/about/'],
            'hi' => ['title' => 'मेरे बारे में', 'url' => 'hi/about/'],
            'ur' => ['title' => 'میرے بارے میں', 'url' => 'ur/about/'],
            'ms' => ['title' => 'Tentang Saya', 'url' => 'ms/tentang-saya/'],
            'el' => ['title' => 'Σχετικά', 'url' => 'el/about/'],
            'hy' => ['title' => 'Իմ մասին', 'url' => 'hy/about/'],
        ],
        'separator' => [
            'tr' => ['title' => '| Dikey Ayraç', 'url' => ''],
            'en' => ['title' => '| Separator', 'url' => ''],
            'fr' => ['title' => '| Séparateur', 'url' => ''],
            'zh' => ['title' => '| 分隔符', 'url' => ''],
            'de' => ['title' => '| Trennzeichen', 'url' => ''],
            'es' => ['title' => '| Separador', 'url' => ''],
            'it' => ['title' => '| Separatore', 'url' => ''],
            'pt' => ['title' => '| Separador', 'url' => ''],
            'ru' => ['title' => '| Разделитель', 'url' => ''],
            'ja' => ['title' => '| 区切り線', 'url' => ''],
            'ko' => ['title' => '| 구분선', 'url' => ''],
            'ar' => ['title' => '| فاصل', 'url' => ''],
            'hi' => ['title' => '| विभाजक', 'url' => ''],
            'ur' => ['title' => '| جداکار', 'url' => ''],
            'ms' => ['title' => '| Pemisah', 'url' => ''],
            'el' => ['title' => '| Διαχωριστικό', 'url' => ''],
            'hy' => ['title' => '| Բաժանիչ', 'url' => ''],
        ],
    ];
}

/**
 * Kategori nesnesi veya anahtarı için aktif/belirtilen dildeki yerelleştirilmiş adı döndürür.
 * Veritabanında dil sütunu boş olsa dahi 17 dilli matristen veya sözlükten kusursuz çevirir.
 */
function get_category_localized_name($cat, ?string $lang = null): string {
    $lang = strtolower(trim($lang ?? ($GLOBALS['admin_lang'] ?? ($GLOBALS['lang'] ?? 'en'))));
    $matrix = get_standard_category_matrix();

    if (is_string($cat)) {
        $key = strtolower(trim($cat));
        if (isset($matrix[$key][$lang]['name'])) {
            return $matrix[$key][$lang]['name'];
        }
        foreach ($matrix as $k => $by_lang) {
            foreach ($by_lang as $l => $data) {
                if (strcasecmp($data['name'], $key) === 0 || strcasecmp($data['slug'], $key) === 0) {
                    return $by_lang[$lang]['name'] ?? ($by_lang['en']['name'] ?? $cat);
                }
            }
        }
        return ui_translate($cat, $lang);
    }

    if (!is_array($cat)) {
        return '';
    }

    if (!empty($cat['name_' . $lang])) {
        return (string)$cat['name_' . $lang];
    }

    $key = strtolower(trim($cat['key_slug'] ?? ''));
    if ($key !== '' && isset($matrix[$key][$lang]['name'])) {
        return $matrix[$key][$lang]['name'];
    }

    $en_name = trim($cat['name_en'] ?? '');
    $tr_name = trim($cat['name_tr'] ?? '');
    foreach ($matrix as $k => $by_lang) {
        if (($en_name !== '' && strcasecmp($by_lang['en']['name'], $en_name) === 0) ||
            ($tr_name !== '' && strcasecmp($by_lang['tr']['name'], $tr_name) === 0)) {
            return $by_lang[$lang]['name'] ?? ($by_lang['en']['name'] ?? $tr_name);
        }
    }

    if ($en_name !== '') {
        $translated = ui_translate($en_name, $lang);
        if ($translated !== $en_name) {
            return $translated;
        }
    }

    if ($lang === 'tr') {
        return $tr_name ?: $en_name;
    }
    return $en_name ?: $tr_name;
}

/**
 * Menü öğesi veya dizesi için aktif/belirtilen dildeki yerelleştirilmiş başlığı döndürür.
 */
function get_menu_localized_title($menu, ?string $lang = null): string {
    $lang = strtolower(trim($lang ?? ($GLOBALS['admin_lang'] ?? ($GLOBALS['lang'] ?? 'en'))));
    $menu_matrix = get_standard_menu_matrix();
    $cat_matrix = get_standard_category_matrix();

    if (is_string($menu)) {
        $str = trim($menu);
        if (strpos($str, '|') !== false || stripos($str, 'ayraç') !== false || stripos($str, 'separator') !== false) {
            return $menu_matrix['separator'][$lang]['title'] ?? $str;
        }
        if (isset($menu_matrix[$str][$lang]['title'])) {
            return $menu_matrix[$str][$lang]['title'];
        }
        if (isset($cat_matrix[$str][$lang]['name'])) {
            return $cat_matrix[$str][$lang]['name'];
        }
        return ui_translate($str, $lang);
    }

    if (!is_array($menu)) {
        return '';
    }

    if (!empty($menu['is_separator'])) {
        return $menu_matrix['separator'][$lang]['title'] ?? '| Separator';
    }

    if (!empty($menu['title_' . $lang])) {
        return (string)$menu['title_' . $lang];
    }

    $url_tr = strtolower(trim($menu['url_tr'] ?? ''));
    $url_en = strtolower(trim($menu['url_en'] ?? ''));
    $title_en = trim($menu['title_en'] ?? '');
    $title_tr = trim($menu['title_tr'] ?? '');

    if (strpos($url_tr, 'hakkimda') !== false || strpos($url_en, 'about') !== false ||
        strcasecmp($title_en, 'About Me') === 0 || strcasecmp($title_tr, 'Hakkımda') === 0) {
        return $menu_matrix['about'][$lang]['title'] ?? ($menu_matrix['about']['en']['title'] ?? $title_tr);
    }

    foreach ($cat_matrix as $slug => $by_lang) {
        if (strpos($url_tr, '/' . $slug . '/') !== false || strpos($url_en, '/' . $slug . '/') !== false ||
            strpos($url_tr, '/' . $by_lang['tr']['slug'] . '/') !== false || strpos($url_en, '/' . $by_lang['en']['slug'] . '/') !== false ||
            strcasecmp($title_en, $by_lang['en']['name']) === 0 || strcasecmp($title_tr, $by_lang['tr']['name']) === 0) {
            return $by_lang[$lang]['name'] ?? ($by_lang['en']['name'] ?? $title_tr);
        }
    }

    if ($title_en !== '') {
        $translated = ui_translate($title_en, $lang);
        if ($translated !== $title_en) {
            return $translated;
        }
    }

    if ($lang === 'tr') {
        return $title_tr ?: $title_en;
    }
    return $title_en ?: $title_tr;
}

/**
 * Belirtilen dil (veya tüm diller) için standart kategorileri ve menüleri veritabanında senkronize eder.
 */
function sync_standard_categories_and_menus(?string $specific_lang = null): void {
    try {
        $pdo = DB::getConnection();
        $is_sqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

        $langs = [];
        if ($specific_lang !== null) {
            $code = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($specific_lang)));
            if ($code !== '') $langs[] = $code;
        } else {
            $stmt = $pdo->query("SELECT `code` FROM `languages`");
            $langs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $cat_matrix = get_standard_category_matrix();
        $menu_matrix = get_standard_menu_matrix();

        foreach ($langs as $code) {
            if ($code === '' || $code === 'tr' || $code === 'en') {
                continue;
            }

            if ($is_sqlite) {
                $c_cols = [];
                foreach ($pdo->query("PRAGMA table_info(`categories`)")->fetchAll() as $r) $c_cols[] = $r['name'];
                if (!in_array("name_{$code}", $c_cols, true)) {
                    $pdo->exec("ALTER TABLE `categories` ADD COLUMN `name_{$code}` VARCHAR(128) NULL;");
                }
                if (!in_array("slug_{$code}", $c_cols, true)) {
                    $pdo->exec("ALTER TABLE `categories` ADD COLUMN `slug_{$code}` VARCHAR(128) NULL;");
                }

                $m_cols = [];
                foreach ($pdo->query("PRAGMA table_info(`menus`)")->fetchAll() as $r) $m_cols[] = $r['name'];
                if (!in_array("title_{$code}", $m_cols, true)) {
                    $pdo->exec("ALTER TABLE `menus` ADD COLUMN `title_{$code}` VARCHAR(255) NULL;");
                }
                if (!in_array("url_{$code}", $m_cols, true)) {
                    $pdo->exec("ALTER TABLE `menus` ADD COLUMN `url_{$code}` VARCHAR(255) NULL;");
                }
            }

            foreach ($cat_matrix as $key_slug => $trans) {
                if (isset($trans[$code])) {
                    $name = $trans[$code]['name'];
                    $slug = $trans[$code]['slug'];
                    $sql = "UPDATE `categories` SET `name_{$code}` = ?, `slug_{$code}` = ? 
                            WHERE `key_slug` = ? AND (`name_{$code}` IS NULL OR `name_{$code}` = '')";
                    $pdo->prepare($sql)->execute([$name, $slug, $key_slug]);
                }
            }

            foreach ($cat_matrix as $key_slug => $trans) {
                if (isset($trans[$code])) {
                    $name = $trans[$code]['name'];
                    $slug = $trans[$code]['slug'];
                    $url = $code . '/' . $slug . '/';
                    $sql = "UPDATE `menus` SET `title_{$code}` = ?, `url_{$code}` = ? 
                            WHERE (`url_tr` LIKE ? OR `url_en` LIKE ? OR `url_tr` LIKE ? OR `url_en` LIKE ?)
                            AND (`title_{$code}` IS NULL OR `title_{$code}` = '')";
                    $pdo->prepare($sql)->execute([
                        $name, $url,
                        "%/{$key_slug}/%", "%/{$key_slug}/%",
                        "%/{$trans['tr']['slug']}/%", "%/{$trans['en']['slug']}/%"
                    ]);
                }
            }

            if (isset($menu_matrix['about'][$code])) {
                $ab_title = $menu_matrix['about'][$code]['title'];
                $ab_url = $menu_matrix['about'][$code]['url'];
                $sql = "UPDATE `menus` SET `title_{$code}` = ?, `url_{$code}` = ? 
                        WHERE (`url_tr` LIKE '%hakkimda%' OR `url_en` LIKE '%about%')
                        AND (`title_{$code}` IS NULL OR `title_{$code}` = '')";
                $pdo->prepare($sql)->execute([$ab_title, $ab_url]);
            }

            if (isset($menu_matrix['separator'][$code])) {
                $sep_title = $menu_matrix['separator'][$code]['title'];
                $sql = "UPDATE `menus` SET `title_{$code}` = ? 
                        WHERE `is_separator` = 1 AND (`title_{$code}` IS NULL OR `title_{$code}` = '')";
                $pdo->prepare($sql)->execute([$sep_title]);
            }
        }
    } catch (\Throwable $e) {}
}

/**
 * Ön yüz (frontend) sabit metinlerini dilin özel ui_strings ayarından veya çok dilli sözlükten döndürür
 */
function ui_text(string $key, string $lang = 'en', string $default_en = '', string $default_tr = ''): string {
    $lang = strtolower(trim($lang));
    $info = get_language_info($lang);
    if ($info && !empty($info['ui_strings'][$key])) {
        return (string)$info['ui_strings'][$key];
    }

    $builtin = [
        'read_story' => [
            'tr' => 'Hikayeyi Oku', 'en' => 'Read Story', 'hy' => 'Կարդալ պատմությունը',
            'ku' => 'Çîrokê Bixwîne', 'fr' => 'Lire le récit', 'de' => 'Geschichte lesen',
            'es' => 'Leer historia', 'it' => 'Leggi la storia', 'ru' => 'Читать историю',
            'ar' => 'اقرأ القصة', 'el' => 'Διαβάστε την ιστορία', 'ja' => 'ストーリーを読む',
            'pt' => 'Ler História', 'ko' => '스토리 읽기', 'hi' => 'कहानी पढ़ें', 'ur' => 'کہانی پڑھیں', 'ms' => 'Baca Kisah', 'zh' => '阅读故事',
        ],
        'back_to_list' => [
            'tr' => 'Listeye Dön', 'en' => 'Back to List', 'hy' => 'Վերադառնալ ցանկին',
            'ku' => 'Vegere Lîsteyê', 'fr' => 'Retour à la liste', 'de' => 'Zurück zur Liste',
            'es' => 'Volver a la lista', 'it' => 'Torna alla lista', 'ru' => 'Назад к списку',
            'ar' => 'العودة إلى القائمة', 'el' => 'Επιστροφή στη λίστα', 'ja' => '一覧に戻る',
            'pt' => 'Voltar à Lista', 'ko' => '목록으로 돌아가기', 'hi' => 'सूची पर वापस जाएँ', 'ur' => 'فہرست پر واپس جائیں', 'ms' => 'Kembali ke Senarai', 'zh' => '返回列表',
        ],
        'share' => [
            'tr' => 'Paylaş', 'en' => 'Share', 'hy' => 'Կիսվել',
            'ku' => 'Parve bike', 'fr' => 'Partager', 'de' => 'Teilen',
            'es' => 'Compartir', 'it' => 'Condividi', 'ru' => 'Поделиться',
            'ar' => 'مشاركة', 'el' => 'Κοινοποίηση', 'ja' => '共有',
            'pt' => 'Compartilhar', 'ko' => '공유하기', 'hi' => 'साझा करें', 'ur' => 'اشتراک کریں', 'ms' => 'Kongsi', 'zh' => '分享',
        ],
        'minutes_read' => [
            'tr' => 'dk okuma', 'en' => 'min read', 'hy' => 'րոպե ընթերցում',
            'ku' => 'dq xwendin', 'fr' => 'min de lecture', 'de' => 'Min. Lesezeit',
            'es' => 'min de lectura', 'it' => 'min di lettura', 'ru' => 'мин чтения',
            'ar' => 'دقائق قراءة', 'el' => 'λεπτά ανάγνωσης', 'ja' => '分で読めます',
            'pt' => 'min de leitura', 'ko' => '분 소요', 'hi' => 'मिनट का पाठ', 'ur' => 'منٹ کا مطالعہ', 'ms' => 'min bacaan', 'zh' => '分钟阅读',
        ],
        'all_works' => [
            'tr' => 'Tümü', 'en' => 'All Works', 'hy' => 'Բոլոր աշխատանքները',
            'ku' => 'Hemû Berhem', 'fr' => 'Toutes les œuvres', 'de' => 'Alle Arbeiten',
            'es' => 'Todos los trabajos', 'it' => 'Tutti i lavori', 'ru' => 'Все работы',
            'ar' => 'جميع الأعمال', 'el' => 'Όλα τα έργα', 'ja' => 'すべての作品',
            'pt' => 'Todos', 'ko' => '전체', 'hi' => 'सभी', 'ur' => 'تمام', 'ms' => 'Semua', 'zh' => '全部',
        ],
        'view_project' => [
            'tr' => 'Çalışmayı İncele', 'en' => 'View Story', 'hy' => 'Դիտել նախագիծը',
            'ku' => 'Projeyê Bibîne', 'fr' => 'Voir le projet', 'de' => 'Projekt ansehen',
            'es' => 'Ver proyecto', 'it' => 'Vedi progetto', 'ru' => 'Смотреть проект',
            'ar' => 'عرض المشروع', 'el' => 'Προβολή έργου', 'ja' => 'プロジェクトを見る',
            'pt' => 'Ver História', 'ko' => '스토리 보기', 'hi' => 'कहानी देखें', 'ur' => 'کہانی دیکھیں', 'ms' => 'Lihat Kisah', 'zh' => '查看作品',
        ],
        'location' => [
            'tr' => 'Konum', 'en' => 'Location', 'hy' => 'Վայր',
            'ku' => 'Cih', 'fr' => 'Lieu', 'de' => 'Ort',
            'es' => 'Ubicación', 'it' => 'Luogo', 'ru' => 'Место',
            'ar' => 'الموقع', 'el' => 'Τοποθεσία', 'ja' => '場所',
            'pt' => 'Localização', 'ko' => '위치', 'hi' => 'स्थान', 'ur' => 'مقام', 'ms' => 'Lokasi', 'zh' => '地点',
        ],
        'date' => [
            'tr' => 'Tarih', 'en' => 'Date', 'hy' => 'Ամսաթիվ',
            'ku' => 'Dîrok', 'fr' => 'Date', 'de' => 'Datum',
            'es' => 'Fecha', 'it' => 'Data', 'ru' => 'Дата',
            'ar' => 'التاريخ', 'el' => 'Ημερομηνία', 'ja' => '日付',
            'pt' => 'Data', 'ko' => '날짜', 'hi' => 'तारीख', 'ur' => 'تاریخ', 'ms' => 'Tarikh', 'zh' => '日期',
        ],
        'photos_count' => [
            'tr' => 'Kare', 'en' => 'Frames', 'hy' => 'Կադր',
            'ku' => 'Wêne', 'fr' => 'Photographies', 'de' => 'Aufnahmen',
            'es' => 'Fotografías', 'it' => 'Fotografie', 'ru' => 'Кадров',
            'ar' => 'صور', 'el' => 'Φωτογραφίες', 'ja' => '枚',
            'pt' => 'Fotografias', 'ko' => '장', 'hi' => 'फ़ोटो', 'ur' => 'تصاویر', 'ms' => 'Keping', 'zh' => '张',
        ],
        'contact' => [
            'tr' => 'İletişim', 'en' => 'Contact', 'hy' => 'Կապ',
            'ku' => 'Têkilî', 'fr' => 'Contact', 'de' => 'Kontakt',
            'es' => 'Contacto', 'it' => 'Contatto', 'ru' => 'Контакты',
            'ar' => 'اتصل بنا', 'el' => 'Επικοινωνία', 'ja' => '連絡先',
            'pt' => 'Contato', 'ko' => '연락처', 'hi' => 'संपर्क', 'ur' => 'رابطہ', 'ms' => 'Hubungi', 'zh' => '联系',
        ],
    ];

    if (isset($builtin[$key][$lang])) {
        return $builtin[$key][$lang];
    }
    if ($lang === 'tr' && $default_tr !== '') {
        return $default_tr;
    }
    $fb = $info['fallback_lang'] ?? 'en';
    if ($fb === 'tr' && $default_tr !== '') {
        return $default_tr;
    }
    if (isset($builtin[$key][$fb])) {
        return $builtin[$key][$fb];
    }
    return $default_en !== '' ? $default_en : ($default_tr !== '' ? $default_tr : $key);
}

function calculate_reading_time(?string $text): int {
    if (!$text) return 1;
    $clean = strip_tags($text);
    $words = count(preg_split('/\s+/', trim($clean)));
    return max(1, (int)ceil($words / 200));
}

/**
 * Dosya yolu çözümleme
 * Verilen relatif veya mutlak dosya yolunu sunucu köküne göre tam yola çevirir.
 */
function resolve_image_file_path(string $path): ?string {
    $clean = ltrim($path, '/');
    $doc_root = dirname(__DIR__);

    $candidates = [
        $doc_root . '/' . $clean,
        $doc_root . '/content/' . $clean,
        $doc_root . '/uploads/' . $clean,
        $doc_root . '/static/' . $clean,
    ];

    foreach ($candidates as $cand) {
        if (file_exists($cand) && is_file($cand)) {
            return $cand;
        }
    }
    return null;
}

/**
 * Kapak veya içerik resmi için her zaman çalışan geçerli web URL'si üretir.
 */
function get_cover_image_url(?string $path): string {
    if (!$path) {
        return url('assets/brand/favicon.png');
    }
    $path = trim($path);
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    $full = resolve_image_file_path($path);
    if ($full) {
        $base_root = dirname(__DIR__);
        $rel = ltrim(str_replace($base_root, '', $full), '/\\');
        return url(str_replace('\\', '/', $rel));
    }
    $clean = ltrim($path, '/');
    return url($clean);
}

/**
 * Yönetim paneli ve küçük önizlemeler için hafifletilmiş thumbnail URL'si üretir.
 * Orijinal tam boy fotoğraf yerine 320px/480px WebP veya AVIF küçük görseli döndürür.
 */
function get_thumbnail_url(?string $path, int $target_width = 320): string {
    if (!$path) {
        return url('assets/brand/favicon.png');
    }
    $path = trim($path);
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    $full = resolve_image_file_path($path);
    if ($full) {
        $base_root = dirname(__DIR__);
        $filename = pathinfo($full, PATHINFO_FILENAME);

        // 1. Önce processed_images altında hazır _thumb.webp veya hedef genişlikte webp/avif var mı kontrol et
        $proc_dir = $base_root . '/processed_images';
        $thumb_webp = $proc_dir . '/' . $filename . '_thumb.webp';
        if (file_exists($thumb_webp)) {
            return url('processed_images/' . $filename . '_thumb.webp');
        }
        $sized_webp = $proc_dir . '/' . $filename . '_' . $target_width . 'w.webp';
        if (file_exists($sized_webp)) {
            return url('processed_images/' . $filename . '_' . $target_width . 'w.webp');
        }
        $sized_600_webp = $proc_dir . '/' . $filename . '_600w.webp';
        if (file_exists($sized_600_webp)) {
            return url('processed_images/' . $filename . '_600w.webp');
        }

        // 2. Mevcut Zola AVIF küçük varyantı varsa kullan (480w / 600w)
        $zola_avifs = find_existing_zola_avif($filename);
        if (!empty($zola_avifs)) {
            return url($zola_avifs[0]);
        }

        // 3. Yoksa anında küçük WebP veya AVIF thumbnail üret
        $generated = resize_and_convert_image($full, $target_width, 'webp', 78);
        if (!$generated) {
            $generated = resize_and_convert_image($full, $target_width, 'avif', 75);
        }
        if ($generated) {
            return url($generated);
        }
    }
    return get_cover_image_url($path);
}

/**
 * Resim boyutlandırma ve AVIF / WebP dönüştürücü
 * Verilen resmi belirtilen genişliğe ölçekler ve AVIF veya WebP formatında kaydeder.
 */
function resize_and_convert_image(string $src_path, int $target_width, string $format = 'avif', int $quality = 80): ?string {
    $base_dir = dirname(__DIR__);
    $proc_dir = $base_dir . '/processed_images';
    if (!is_dir($proc_dir)) {
        @mkdir($proc_dir, 0755, true);
    }

    $filename = pathinfo($src_path, PATHINFO_FILENAME);
    $target_name = $filename . '_' . $target_width . 'w.' . $format;
    $target_file = $proc_dir . '/' . $target_name;

    if (file_exists($target_file) && filesize($target_file) > 0) {
        return 'processed_images/' . $target_name;
    }

    if (!extension_loaded('gd')) {
        return null;
    }

    // Kaynak resmi oku
    $img_info = @getimagesize($src_path);
    if (!$img_info) return null;

    $orig_w = $img_info[0];
    $orig_h = $img_info[1];
    $mime = $img_info['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $src_img = @imagecreatefromjpeg($src_path);
            break;
        case 'image/png':
            $src_img = @imagecreatefrompng($src_path);
            break;
        case 'image/webp':
            $src_img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src_path) : null;
            break;
        case 'image/avif':
            $src_img = function_exists('imagecreatefromavif') ? @imagecreatefromavif($src_path) : null;
            break;
        default:
            $src_img = null;
    }

    if (!$src_img) return null;

    // EXIF orientation
    if (function_exists('exif_read_data') && ($mime === 'image/jpeg')) {
        $exif = @exif_read_data($src_path);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $rotated = imagerotate($src_img, 180, 0);
                    if ($rotated) { $src_img = $rotated; }
                    break;
                case 6:
                    $rotated = imagerotate($src_img, -90, 0);
                    if ($rotated) { $src_img = $rotated; }
                    break;
                case 8:
                    $rotated = imagerotate($src_img, 90, 0);
                    if ($rotated) { $src_img = $rotated; }
                    break;
            }
        }
    }

    $actual_orig_w = imagesx($src_img);
    $actual_orig_h = imagesy($src_img);

    $new_w = min($target_width, $actual_orig_w);
    $new_h = (int)round(($actual_orig_h * $new_w) / max(1, $actual_orig_w));

    $dst_img = imagecreatetruecolor($new_w, $new_h);
    imagealphablending($dst_img, false);
    imagesavealpha($dst_img, true);
    $trans = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
    imagefilledrectangle($dst_img, 0, 0, $new_w, $new_h, $trans);

    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $actual_orig_w, $actual_orig_h);

    $saved = false;
    if ($format === 'avif' && function_exists('imageavif')) {
        $saved = @imageavif($dst_img, $target_file, $quality);
    } elseif ($format === 'webp' && function_exists('imagewebp')) {
        $saved = @imagewebp($dst_img, $target_file, $quality);
    } elseif ($format === 'jpg' || $format === 'jpeg') {
        $saved = @imagejpeg($dst_img, $target_file, $quality);
    }

    return $saved ? ('processed_images/' . $target_name) : null;
}

/**
 * Zola tarafından önceden oluşturulmuş AVIF dosyalarını arar
 */
function find_existing_zola_avif(string $filename): array {
    static $cache = null;
    $base_dir = dirname(__DIR__);
    $proc_dir = $base_dir . '/processed_images';

    if ($cache === null) {
        $cache = [];
        if (is_dir($proc_dir)) {
            $files = scandir($proc_dir);
            foreach ($files as $f) {
                if (preg_match('/^(.+)\.[0-9a-f]{16}\.avif$/', $f, $m)) {
                    // Örnek: AB_20180824_NPKTH_P-XYZ_01.83e816ecfad354ca.avif
                    $prefix = $m[1];
                    $full_f = $proc_dir . '/' . $f;
                    $cache[$prefix][] = [
                        'rel' => 'processed_images/' . $f,
                        'size' => filesize($full_f)
                    ];
                }
            }
            foreach ($cache as $k => &$arr) {
                usort($arr, function($a, $b) {
                    return $a['size'] <=> $b['size'];
                });
                $arr = array_column($arr, 'rel');
            }
            unset($arr);
        }
    }

    return $cache[$filename] ?? [];
}

/**
 * Fotoğraf EXIF Kamera & Çekim Özeti Üretici (B4 Lightbox EXIF Gösterimi İçin)
 */
function get_photo_exif_summary(?string $full_path): string {
    if (!$full_path || !file_exists($full_path) || !function_exists('exif_read_data')) {
        return '';
    }
    $ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'tif', 'tiff'], true)) {
        return '';
    }
    try {
        $exif = @exif_read_data($full_path, 'EXIF,IFD0', false);
        if (!$exif || !is_array($exif)) return '';

        $parts = [];
        $make = trim((string)($exif['Make'] ?? ''));
        $model = trim((string)($exif['Model'] ?? ''));
        if ($model !== '') {
            if ($make !== '' && stripos($model, $make) === false) {
                $parts[] = $make . ' ' . $model;
            } else {
                $parts[] = $model;
            }
        }

        // Odak uzaklığı (FocalLength)
        if (!empty($exif['FocalLength'])) {
            $fl = $exif['FocalLength'];
            if (is_string($fl) && strpos($fl, '/') !== false) {
                [$n, $d] = array_map('floatval', explode('/', $fl, 2));
                if ($d > 0) $parts[] = round($n / $d) . 'mm';
            } elseif (is_numeric($fl)) {
                $parts[] = round((float)$fl) . 'mm';
            }
        }

        // Diyafram (FNumber)
        if (!empty($exif['FNumber'])) {
            $fn = $exif['FNumber'];
            if (is_string($fn) && strpos($fn, '/') !== false) {
                [$n, $d] = array_map('floatval', explode('/', $fn, 2));
                if ($d > 0) $parts[] = 'f/' . round($n / $d, 1);
            } elseif (is_numeric($fn)) {
                $parts[] = 'f/' . round((float)$fn, 1);
            }
        } elseif (!empty($exif['COMPUTED']['ApertureFNumber'])) {
            $parts[] = trim((string)$exif['COMPUTED']['ApertureFNumber']);
        }

        // Enstantane (ExposureTime)
        if (!empty($exif['ExposureTime'])) {
            $et = (string)$exif['ExposureTime'];
            if (strpos($et, '/') !== false) {
                [$n, $d] = array_map('floatval', explode('/', $et, 2));
                if ($n > 0 && $d > 0) {
                    if ($n >= $d) {
                        $parts[] = round($n / $d, 1) . 's';
                    } else {
                        $parts[] = '1/' . round($d / $n) . 's';
                    }
                }
            } else {
                $parts[] = $et . 's';
            }
        }

        // ISO
        if (!empty($exif['ISOSpeedRatings'])) {
            $iso = is_array($exif['ISOSpeedRatings']) ? reset($exif['ISOSpeedRatings']) : $exif['ISOSpeedRatings'];
            if ($iso) $parts[] = 'ISO ' . $iso;
        }

        return implode(' • ', array_filter($parts));
    } catch (\Throwable $e) {
        return '';
    }
}

/**
 * Duyarlı ve modern resim kartı üretici (<picture>, AVIF, WebP, responsive srcset)
 */
function get_responsive_image_html(string $img_path, string $alt = '', string $caption = '', bool $link = true, string $focal_point = ''): string {
    $full_path = resolve_image_file_path($img_path);
    $filename = pathinfo($img_path, PATHINFO_FILENAME);

    $orig_w = 1600;
    $orig_h = 1067;
    $is_portrait = false;

    if ($full_path && file_exists($full_path)) {
        $size = @getimagesize($full_path);
        if ($size) {
            $orig_w = $size[0];
            $orig_h = $size[1];
            $is_portrait = ($orig_h > $orig_w);
        }
    }

    $aspect_ratio = round($orig_w / max(1, $orig_h), 4);

    // AVIF ve WebP varyantlarını oluştur / bul
    $zola_avifs = find_existing_zola_avif($filename);

    $sm_avif = null; $md_avif = null; $lg_avif = null; $xl_avif = null;
    $sm_webp = null; $md_webp = null; $lg_webp = null;

    if (!empty($zola_avifs) && count($zola_avifs) >= 3) {
        // Zola AVIF dosyaları mevcut:
        // Normal galeri görsellerinde 3 adet (600w, 920w, 1800w),
        // Kapak görsellerinde ise 7 adet (480w, 600w, 800w, 920w, 1600w, 1800w, 2560w) bulunur.
        $cnt = count($zola_avifs);
        if ($cnt >= 7) {
            $sm_avif = $zola_avifs[1]; // 600w
            $md_avif = $zola_avifs[3]; // 920w
            $lg_avif = $zola_avifs[5]; // 1800w (diğer fotoğraflarla birebir aynı standart tam boy)
            $xl_avif = $zola_avifs[$cnt - 1]; // 2560w (büyüteç / zoom için yüksek çözünürlüklü)
        } elseif ($cnt >= 4) {
            $sm_avif = $zola_avifs[1];
            $md_avif = $zola_avifs[2];
            $lg_avif = $zola_avifs[$cnt - 1];
            $xl_avif = $lg_avif;
        } else {
            $sm_avif = $zola_avifs[0]; // 600w
            $md_avif = $zola_avifs[1]; // 920w
            $lg_avif = $zola_avifs[2]; // 1800w
            $xl_avif = $lg_avif;
        }
    } elseif ($full_path) {
        $sm_avif = resize_and_convert_image($full_path, 600, 'avif', 75);
        $md_avif = resize_and_convert_image($full_path, 920, 'avif', 78);
        $lg_avif = resize_and_convert_image($full_path, 1800, 'avif', 80);
        $xl_avif = $lg_avif;
    }

    if ($full_path) {
        $sm_webp = resize_and_convert_image($full_path, 600, 'webp', 80);
        $md_webp = resize_and_convert_image($full_path, 920, 'webp', 82);
        $lg_webp = resize_and_convert_image($full_path, 1800, 'webp', 85);
    }

    // Fallback URL (varsayılan resim veya orijinal)
    $clean_rel = ltrim($img_path, '/');
    $fallback_url = url($clean_rel);
    $large_view_url = $xl_avif ? url($xl_avif) : ($lg_avif ? url($lg_avif) : ($lg_webp ? url($lg_webp) : $fallback_url));
    $medium_url = $md_webp ? url($md_webp) : ($md_avif ? url($md_avif) : $fallback_url);

    // Srcset dizeleri
    $avif_srcset = [];
    if ($sm_avif) $avif_srcset[] = url($sm_avif) . ' 600w';
    if ($md_avif) $avif_srcset[] = url($md_avif) . ' 920w';
    if ($lg_avif) $avif_srcset[] = url($lg_avif) . ' 1800w';

    $webp_srcset = [];
    if ($sm_webp) $webp_srcset[] = url($sm_webp) . ' 600w';
    if ($md_webp) $webp_srcset[] = url($md_webp) . ' 920w';
    if ($lg_webp) $webp_srcset[] = url($lg_webp) . ' 1800w';

    $portrait_class = $is_portrait ? ' is-portrait' : '';
    $focal_clean = preg_match('/^\d{1,3}%\s+\d{1,3}%$/', trim($focal_point)) ? trim($focal_point) : '50% 50%';
    $style_vars = "--img-w:{$orig_w};--img-h:{$orig_h};--img-ratio:{$aspect_ratio};--focal:{$focal_clean};";

    $html = "<figure class=\"photo-figure{$portrait_class}\" style=\"{$style_vars}\">";
    if ($link) {
        $desc_attr = '';
        $effective_caption = trim($caption !== '' ? $caption : $alt);
        if ($effective_caption !== '') {
            $desc_attr = ' data-description="' . e($effective_caption) . '"';
        }
        $exif_attr = '';
        if (get_setting('lightbox_show_exif', '1') === '1' && $full_path) {
            $exif_summary = get_photo_exif_summary($full_path);
            if ($exif_summary !== '') {
                $exif_attr = ' data-exif="' . e($exif_summary) . '"';
            }
        }
        $html .= "<a href=\"" . e($large_view_url) . "\" class=\"glightbox\" data-gallery=\"project-gallery\"{$desc_attr}{$exif_attr}>";
    }

    $html .= "<picture style=\"display:contents;\">";
    if (!empty($avif_srcset)) {
        $html .= "<source type=\"image/avif\" srcset=\"" . e(implode(', ', $avif_srcset)) . "\" sizes=\"(max-width: 600px) 100vw, 100%\">";
    }
    if (!empty($webp_srcset)) {
        $html .= "<source type=\"image/webp\" srcset=\"" . e(implode(', ', $webp_srcset)) . "\" sizes=\"(max-width: 600px) 100vw, 100%\">";
    }
    $img_focal_style = ($focal_clean !== '50% 50%') ? "object-position:{$focal_clean};" : "";
    $html .= "<img src=\"" . e($medium_url) . "\" alt=\"" . e($alt) . "\" style=\"width:100%;height:auto;display:block;{$img_focal_style}\" loading=\"lazy\" decoding=\"async\" class=\"photo-figure-img\" />";
    $html .= "</picture>";

    if ($link) {
        $html .= "</a>";
    }
    if ($caption !== '') {
        $html .= "<figcaption class=\"photo-caption\">" . e($caption) . "</figcaption>";
    }
    $html .= "</figure>";

    return $html;
}

/**
 * Resim Yükleme ve AVIF / WebP Optimizasyonu
 * Admin paneline yüklenen tüm görselleri AVIF, WebP ve optimize JPEG olarak kaydeder.
 */
function optimize_and_save_uploaded_photo(string $source_tmp, string $target_dir, string $base_name, string $ext): array {
    $base_root = dirname(__DIR__);
    $target_dir = rtrim($target_dir, '/\\');
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0755, true);
    }

    $rel_folder = ltrim(str_replace($base_root, '', $target_dir), '/\\');
    $rel_folder = str_replace('\\', '/', $rel_folder);
    if ($rel_folder === '') $rel_folder = 'uploads';

    $ext = strtolower($ext);

    if ($ext === 'svg' || $ext === 'gif') {
        $final_name = $base_name . '.' . $ext;
        $dest = $target_dir . '/' . $final_name;
        if (@move_uploaded_file($source_tmp, $dest)) {
            $rel_path = $rel_folder . '/' . $final_name;
            return [
                'success'   => true,
                'filename'  => $final_name,
                'file_path' => $rel_path,
                'folder'    => $rel_folder,
                'url'       => url($rel_path),
                'avif_url'  => null,
                'webp_url'  => null,
            ];
        }
        return ['success' => false, 'error' => 'Dosya kaydedilemedi.'];
    }

    if (!extension_loaded('gd')) {
        $final_name = $base_name . '.' . $ext;
        $dest = $target_dir . '/' . $final_name;
        @move_uploaded_file($source_tmp, $dest);
        $rel_path = $rel_folder . '/' . $final_name;
        return [
            'success'   => true,
            'filename'  => $final_name,
            'file_path' => $rel_path,
            'folder'    => $rel_folder,
            'url'       => url($rel_path),
            'avif_url'  => null,
            'webp_url'  => null,
        ];
    }

    $raw = @file_get_contents($source_tmp);
    if (!$raw) {
        return ['success' => false, 'error' => 'Dosya okunamadı.'];
    }

    $src_img = @imagecreatefromstring($raw);
    if (!$src_img) {
        return ['success' => false, 'error' => 'Görsel işlenemedi.'];
    }

    // EXIF orientation düzeltmesi
    if (function_exists('exif_read_data') && ($ext === 'jpg' || $ext === 'jpeg')) {
        $exif = @exif_read_data($source_tmp);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $src_img = imagerotate($src_img, 180, 0);
                    break;
                case 6:
                    $src_img = imagerotate($src_img, -90, 0);
                    break;
                case 8:
                    $src_img = imagerotate($src_img, 90, 0);
                    break;
            }
        }
    }

    $orig_w = imagesx($src_img);
    $orig_h = imagesy($src_img);

    // 1. Ana Görsel (max 2400px genişlik)
    $max_w = 2400;
    $main_w = min($orig_w, $max_w);
    $main_h = (int)round(($orig_h * $main_w) / max(1, $orig_w));

    $main_dst = imagecreatetruecolor($main_w, $main_h);
    imagealphablending($main_dst, false);
    imagesavealpha($main_dst, true);
    $trans = imagecolorallocatealpha($main_dst, 255, 255, 255, 127);
    imagefilledrectangle($main_dst, 0, 0, $main_w, $main_h, $trans);
    imagecopyresampled($main_dst, $src_img, 0, 0, 0, 0, $main_w, $main_h, $orig_w, $orig_h);

    // WebP kaydet
    $webp_name = $base_name . '.webp';
    $webp_path = $target_dir . '/' . $webp_name;
    $has_webp = function_exists('imagewebp') && @imagewebp($main_dst, $webp_path, 84);

    // AVIF kaydet
    $avif_name = $base_name . '.avif';
    $avif_path = $target_dir . '/' . $avif_name;
    $has_avif = function_exists('imageavif') && @imageavif($main_dst, $avif_path, 78);

    // JPEG fallback kaydet
    $jpg_name = $base_name . '.jpg';
    $jpg_path = $target_dir . '/' . $jpg_name;
    @imagejpeg($main_dst, $jpg_path, 86);

    // Küçük Önizleme Thumbnail (400px)
    $thumb_w = 400;
    $thumb_h = (int)round(($orig_h * $thumb_w) / max(1, $orig_w));
    $thumb_dst = imagecreatetruecolor($thumb_w, $thumb_h);
    imagecopyresampled($thumb_dst, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $orig_w, $orig_h);
    @imagewebp($thumb_dst, $target_dir . '/' . $base_name . '_thumb.webp', 75);

    $rel_jpg = $rel_folder . '/' . $jpg_name;
    $rel_webp = $has_webp ? ($rel_folder . '/' . $webp_name) : null;
    $rel_avif = $has_avif ? ($rel_folder . '/' . $avif_name) : null;

    return [
        'success'   => true,
        'filename'  => $jpg_name,
        'file_path' => $rel_jpg,
        'folder'    => $rel_folder,
        'url'       => url($rel_jpg),
        'webp_url'  => $rel_webp ? url($rel_webp) : null,
        'avif_url'  => $rel_avif ? url($rel_avif) : null,
        'webp_path' => $rel_webp,
        'avif_path' => $rel_avif,
        'width'     => $orig_w,
        'height'    => $orig_h,
    ];
}

/**
 * Yönetici içeriği için izin listesi tabanlı HTML temizleyici (DOM ile; regex değil).
 * Bilinmeyen etiketlerin yalnız kendisi kalkar (içindeki metin kalır); tehlikeli etiketler içerikleriyle silinir.
 * on* nitelikleri, izin listesi dışı nitelikler ve javascript:/data: gibi adresler atılır.
 */
function sanitize_html(string $html): string {
    if (trim($html) === '' || strpos($html, '<') === false) return $html;

    static $drop = ['script', 'style', 'object', 'embed', 'applet', 'form', 'input', 'button', 'textarea', 'select',
        'option', 'link', 'meta', 'base', 'frame', 'frameset', 'template', 'noscript', 'math', 'foreignobject', 'use', 'animate', 'set'];
    static $tags = ['a', 'abbr', 'article', 'aside', 'audio', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup',
        'dd', 'del', 'details', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'header', 'hr', 'i', 'iframe', 'img', 'ins', 'kbd', 'li', 'mark', 'nav', 'ol', 'p', 'picture', 'pre', 'q', 's', 'section',
        'small', 'source', 'span', 'strike', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead',
        'time', 'tr', 'track', 'u', 'ul', 'video',
        'svg', 'g', 'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect', 'title', 'desc', 'defs', 'lineargradient', 'stop'];
    static $attrs = ['class', 'id', 'style', 'title', 'lang', 'dir', 'role', 'alt', 'width', 'height', 'loading', 'decoding',
        'src', 'srcset', 'sizes', 'href', 'target', 'rel', 'type', 'media', 'poster', 'preload', 'controls', 'autoplay', 'muted',
        'loop', 'playsinline', 'allow', 'allowfullscreen', 'frameborder', 'referrerpolicy', 'colspan', 'rowspan', 'scope',
        'datetime', 'cite', 'start', 'reversed', 'open', 'kind', 'srclang', 'label', 'default',
        'viewbox', 'xmlns', 'fill', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'stroke-dasharray', 'stroke-opacity', 'fill-opacity', 'opacity', 'd', 'points', 'cx', 'cy', 'r', 'rx', 'ry',
        'x', 'y', 'x1', 'x2', 'y1', 'y2', 'transform', 'offset', 'stop-color', 'gradientunits', 'preserveaspectratio'];
    static $url_attrs = ['href', 'src', 'poster', 'cite'];
    static $iframe_hosts = ['www.youtube-nocookie.com', 'youtube-nocookie.com', 'www.youtube.com', 'youtube.com', 'player.vimeo.com'];

    // Göreli adres, #çapa veya http(s)/mailto/tel; kontrol karakterleri ve boşluklar şemayı gizleyemesin
    $safe_url = function (string $u): bool {
        $u = preg_replace('/[\x00-\x20\x7f]+/', '', $u);
        return !preg_match('/^[a-z][a-z0-9+.\-]*:/i', $u) || preg_match('/^(https?|mailto|tel):/i', $u);
    };

    $wrapped = '<div id="__san_root">' . $html . '</div>';
    if (class_exists('Dom\HTMLDocument')) { // PHP 8.4+: tarayıcıyla aynı HTML5 ayrıştırıcısı
        $doc = \Dom\HTMLDocument::createFromString('<!doctype html><html><body>' . $wrapped . '</body></html>', LIBXML_NOERROR, 'UTF-8');
    } else {
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
    }
    $root = $doc->getElementById('__san_root');
    if (!$root) return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');

    $walk = function ($node) use (&$walk, $drop, $tags, $attrs, $url_attrs, $iframe_hosts, $safe_url) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) { $node->removeChild($child); continue; }
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            $name = strtolower($child->localName);
            if (in_array($name, $drop, true)) { $node->removeChild($child); continue; }
            if ($name === 'iframe' && !in_array(strtolower((string)parse_url($child->getAttribute('src'), PHP_URL_HOST)), $iframe_hosts, true)) {
                $node->removeChild($child); continue;
            }
            if (!in_array($name, $tags, true)) { // etiketi aç, içeriği koru
                $walk($child);
                while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $an = strtolower($attr->name);
                $ok = in_array($an, $attrs, true) || str_starts_with($an, 'aria-') || str_starts_with($an, 'data-');
                if ($ok && in_array($an, $url_attrs, true)) $ok = $safe_url($attr->value);
                if ($ok && $an === 'srcset') {
                    foreach (explode(',', $attr->value) as $cand) { if (!$safe_url(trim(strtok(trim($cand), ' ') ?: ''))) { $ok = false; break; } }
                }
                if ($ok && $an === 'style') $ok = !preg_match('/expression\s*\(|javascript\s*:|@import|behavior\s*:|-moz-binding/i', $attr->value);
                if (!$ok) $child->removeAttribute($attr->name);
            }
            if ($name === 'a' && strtolower($child->getAttribute('target')) === '_blank' && !preg_match('/\bnoopener\b/i', $child->getAttribute('rel'))) {
                $child->setAttribute('rel', trim($child->getAttribute('rel') . ' noopener noreferrer'));
            }
            $walk($child);
        }
    };
    $walk($root);

    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return $out;
}

/**
 * Markdown Çözümleme ve Shortcode İşleme
 */
function render_markdown(?string $content): string {
    if (!$content) return '';

    // 1. YouTube Shortcode: {{ youtube(id="xyz") }}
    $content = preg_replace_callback('/\{\{\s*youtube\(\s*id=[\'"]([^\'"]+)[\'"]\s*\)\s*\}\}/i', function($m) {
        $id = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        return '<div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; margin: 0 0 3.5rem 0; border-radius: 0; background-color: #000;">' .
               '<iframe src="https://www.youtube-nocookie.com/embed/' . $id . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border:0;" allowfullscreen title="YouTube Video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>' .
               '</div>';
    }, $content);

    // 2. Image Shortcode: {{ image(path="...", alt="...", caption="...") }}
    $content = preg_replace_callback('/\{\{\s*image\((.*?)\)\s*\}\}/is', function($m) {
        $attrs_str = $m[1];
        $path = ''; $alt = ''; $caption = '';
        if (preg_match('/path=[\'"]([^\'"]+)[\'"]/', $attrs_str, $pm)) {
            $path = $pm[1];
        }
        if (preg_match('/alt=[\'"]([^\'"]*)[\'"]/', $attrs_str, $am)) {
            $alt = $am[1];
        }
        if (preg_match('/caption=[\'"]([^\'"]*)[\'"]/', $attrs_str, $cm)) {
            $caption = $cm[1];
        }
        if (!$path) return '';
        return get_responsive_image_html($path, $alt, $caption);
    }, $content);

    $parsedown = new Parsedown();
    $parsedown->setSafeMode(false); // ham HTML'e izin verilir; çıktı sanitize_html'den geçer
    return sanitize_html($parsedown->text($content));
}

/**
 * Editoryal Zengin Metin Biçimlendirici ve XSS Temizleyici
 * Hem zengin HTML etiketlerini (strong, b, em, i, u, a, p, br) destekler hem de geriye dönük Markdown sözdizimini çözer.
 */
function format_editorial_rich_text(?string $text): string {
    if (!$text) return '';
    $text = trim($text);

    // 1. Geriye dönük Markdown sözdizimi varsa zengin HTML'e çevir
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/s', '<em>$1</em>', $text);

    // 2. İzin verilen güvenli anlamsal etiketleri koru (XSS önleme)
    $allowed = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'strike',
        'a', 'span', 'blockquote', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'hr', 'div'
    ];
    $clean = strip_tags($text, $allowed);

    // 3. Güvenli bağlantılar (yalnızca http, https, mailto, tel ve site içi göreli linkler)
    $clean = preg_replace_callback('/<a\s+([^>]*?)>/i', function($m) {
        if (preg_match('/href=[\x27"]([^\x27"]*)[\x27"]/i', $m[1], $hm)) {
            $href = trim($hm[1]);
            if (preg_match('/^(https?:\/\/|\/|mailto:|tel:)/i', $href)) {
                $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
                $isExternal = preg_match('/^https?:\/\//i', $href);
                $target = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
                return '<a href="' . $safeHref . '"' . $target . '>';
            }
        }
        return '<a>';
    }, $clean);

    // 3b. Nitelik ve adres temizliği DOM tabanlı izin listesiyle (XSS koruması)
    $clean = sanitize_html($clean);

    // 4. Eğer zaten <p>, <blockquote>, başlık veya liste içeriyorsa temiz HTML olarak döndür
    if (preg_match('/<\s*(p|blockquote|h2|h3|h4|h5|h6|ul|ol|div)\b/i', $clean)) {
        return $clean;
    }

    // 5. Değilse çift satır sonlarını <p> paragraflarına böl
    $paras = preg_split('/\r?\n\s*\r?\n/', $clean);
    $out = [];
    foreach ($paras as $p) {
        $p = trim($p);
        if ($p !== '') {
            $out[] = '<p>' . nl2br($p) . '</p>';
        }
    }
    return implode("\n", $out);
}

/**
 * Normal Editoryal Blokları (Structured JSON) Ön Yüz HTML Çıktısına Dönüştürür
 */
function render_structured_blocks(array $data): string {
    $html = '';

    // 1. Giriş Anlatısı Paragrafları (Editoryal Giriş Hikayesi)
    $intro_text = trim($data['intro'] ?? '');
    if ($intro_text !== '') {
        $html .= format_editorial_rich_text($intro_text) . "\n";
    }

    // 2. Editoryal Blok Akışı
    $blocks = $data['blocks'] ?? [];
    $i = 0;
    $len = count($blocks);
    $inChapter = false;

    while ($i < $len) {
        $b = $blocks[$i];
        $type = $b['type'] ?? 'photo';

        if ($type === 'chapter') {
            if ($inChapter) {
                $html .= '</div>' . "\n";
            }
            $html .= '<div class="project-chapter chapter-dark">' . "\n";
            $html .= '<div class="project-chapter-header">' . "\n";
            $html .= '<h2 class="project-chapter-title">' . htmlspecialchars($b['title'] ?? '') . '</h2>' . "\n";
            if (!empty($b['lead'])) {
                $leadText = format_editorial_rich_text($b['lead']);
                $html .= '<div class="project-chapter-lead">' . $leadText . '</div>' . "\n";
            }
            $html .= '</div>' . "\n";
            $inChapter = true;
            $i++;
            continue;
        }

        if ($type === 'photo') {
            $layout = $b['layout'] ?? 'single';
            if ($layout === 'row') {
                $rowItems = [$b];
                while ($i + 1 < $len && ($blocks[$i + 1]['type'] ?? '') === 'photo' && ($blocks[$i + 1]['layout'] ?? '') === 'row' && count($rowItems) < 2) {
                    $i++;
                    $rowItems[] = $blocks[$i];
                }
                $html .= '<div class="photo-row diptych-row">' . "\n";
                foreach ($rowItems as $item) {
                    $html .= get_responsive_image_html($item['path'] ?? '', $item['alt'] ?? '', $item['caption'] ?? '', true, $item['focal'] ?? '') . "\n";
                }
                $html .= '</div>' . "\n";
            } else {
                $align_class = '';
                if ($layout === 'left') $align_class = ' photo-align-left';
                elseif ($layout === 'right') $align_class = ' photo-align-right';
                $fig_html = get_responsive_image_html($b['path'] ?? '', $b['alt'] ?? '', $b['caption'] ?? '', true, $b['focal'] ?? '');
                if ($align_class !== '') {
                    $fig_html = preg_replace('/class="photo-figure/', 'class="photo-figure' . $align_class, $fig_html, 1);
                }
                $html .= $fig_html . "\n";
            }
            $i++;
            continue;
        }

        if ($type === 'text') {
            $text = trim($b['text'] ?? ($b['content'] ?? ''));
            $title = trim($b['title'] ?? '');
            $html .= '<div class="project-narrative-block">' . "\n";
            $html .= '<div class="project-narrative-block-inner">' . "\n";
            if ($title !== '') {
                $html .= '<p><strong>' . htmlspecialchars($title) . '</strong></p>' . "\n";
            }
            $html .= format_editorial_rich_text($text) . "\n";
            $html .= '</div>' . "\n";
            $html .= '</div>' . "\n";
            $i++;
            continue;
        }

        if ($type === 'quote') {
            $text = trim($b['text'] ?? ($b['content'] ?? ''));
            $html .= '<blockquote>' . format_editorial_rich_text($text) . '</blockquote>' . "\n";
            $i++;
            continue;
        }

        if ($type === 'youtube') {
            $id = htmlspecialchars($b['id'] ?? '');
            $html .= '<div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; margin: 0 0 3.5rem 0; border-radius: 0; background-color: #000;">' .
                   '<iframe src="https://www.youtube-nocookie.com/embed/' . $id . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border:0;" allowfullscreen title="YouTube Video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>' .
                   '</div>' . "\n";
            $i++;
            continue;
        }

        $i++;
    }

    if ($inChapter) {
        $html .= '</div>' . "\n";
    }

    return $html;
}

/**
 * İçerik Çözümleyici (Hem Normal Format / Structured JSON hem de Legacy Markdown Destekli)
 */
function render_item_content(?string $raw): string {
    if (!$raw) return '';
    $raw = trim($raw);

    // 1. JSON Yapısı mı kontrol et
    if (strpos($raw, '{') === 0) {
        $data = json_decode($raw, true);
        if (is_array($data) && (isset($data['blocks']) || isset($data['intro']))) {
            return render_structured_blocks($data);
        }
    }

    // 2. Değilse eski Markdown / HTML olarak render et (Geriye Dönük %100 Uyumluluk)
    return render_markdown($raw);
}

/**
 * Hakkımda / About Me Sayfası İçerik Çözümleyicisi
 * HTML veya B64 JSON metadata bloğundan anlatı, portre, iletişim ve kronoloji bileşenlerini ayrıştırır.
 */
if (!function_exists('parse_about_content')) {
    function parse_about_content(?string $html): array {
        if (empty($html)) {
            return [
                'narrative' => '',
                'portrait' => ['img' => '/assets/brand/portrait-placeholder.svg', 'name' => site_owner_name(), 'role' => ''],
                'contact' => ['kicker' => '', 'title' => '', 'desc' => '', 'email' => get_setting('contact_email'), 'loc' => ''],
                'chronology' => ['title' => '', 'sub' => '', 'items' => []]
            ];
        }

        // 1. Modern B64 metadata kontrolü
        if (preg_match('#<!-- ABOUT_METADATA_B64:([a-zA-Z0-9+/=]+) -->#', $html, $m_meta)) {
            $meta_raw = base64_decode($m_meta[1]);
            $meta = json_decode($meta_raw, true);
            if ($meta && is_array($meta)) {
                preg_match('#<div class="about-narrative">(.*?)</div>\s*<aside#s', $html, $m_narr);
                $narrative = isset($m_narr[1]) ? trim($m_narr[1]) : '';
                if ($narrative === '') {
                    if (preg_match('#^(.*?)(?=<aside|<section class="about-contact-banner"|<section class="about-chronology"|<!-- ABOUT_METADATA_B64)#s', $html, $m_alt)) {
                        $narrative = trim($m_alt[1]);
                    }
                }
                return [
                    'narrative' => $narrative,
                    'portrait' => $meta['portrait'] ?? ['img' => '/assets/brand/portrait-placeholder.svg', 'name' => site_owner_name(), 'role' => ''],
                    'contact' => $meta['contact'] ?? ['kicker' => '', 'title' => '', 'desc' => '', 'email' => get_setting('contact_email'), 'loc' => ''],
                    'chronology' => $meta['chronology'] ?? ['title' => '', 'sub' => '', 'items' => []]
                ];
            }
        }

        // 2. Geriye dönük uyumluluk: Klasik HTML ayrıştırıcı (Regex fallback)
        preg_match('#<div class="about-narrative">(.*?)</div>\s*<aside#s', $html, $m_narr);
        $narrative = isset($m_narr[1]) ? trim($m_narr[1]) : '';
        if ($narrative === '') {
            if (preg_match('#<div class="about-narrative">(.*?)</div>#s', $html, $m_narr2)) {
                $narrative = trim($m_narr2[1]);
            } elseif (preg_match('#^(.*?)(?=<aside|<section class="about-contact-banner"|<section class="about-chronology")#s', $html, $m_alt)) {
                $narrative = trim($m_alt[1]);
            } else {
                $narrative = trim($html);
            }
        }

        preg_match('#<img[^>]+src=["\']([^"\']+)["\']#', $html, $m_img);
        preg_match('#<span class="caption-name">([^<]+)</span>#', $html, $m_name);
        preg_match('#<span class="caption-role">([^<]+)</span>#', $html, $m_role);
        $portrait = [
            'img' => $m_img[1] ?? '/assets/brand/portrait-placeholder.svg',
            'name' => $m_name[1] ?? site_owner_name(),
            'role' => $m_role[1] ?? ''
        ];

        preg_match('#<span class="contact-banner-kicker">([^<]+)</span>#', $html, $m_kicker);
        preg_match('#<h3 class="contact-banner-title">([^<]+)</h3>#', $html, $m_ctitle);
        preg_match('#<p class="contact-banner-desc">([^<]+)</p>#', $html, $m_desc);
        preg_match('#<a href=["\']mailto:([^"\']+)["\']#', $html, $m_email);
        preg_match('#<span class="contact-location">([^<]+)</span>#', $html, $m_loc);
        $contact = [
            'kicker' => $m_kicker[1] ?? 'EDİTORYAL İŞ BİRLİĞİ & TEMSİL',
            'title' => $m_ctitle[1] ?? 'Saha Görevleri & İletişim',
            'desc' => $m_desc[1] ?? '',
            'email' => $m_email[1] ?? get_setting('contact_email'),
            'loc' => $m_loc[1] ?? ''
        ];

        preg_match('#<h2 class="chronology-title">([^<]+)</h2>#', $html, $m_ch_title);
        preg_match('#<span class="chronology-sub">([^<]+)</span>#', $html, $m_ch_sub);

        $items = [];
        if (preg_match_all('#<div class="chronology-item">(.*?)</div>\s*(?=<div class="chronology-item"|</div\s*>\s*</section)#s', $html, $item_matches)) {
            foreach ($item_matches[1] as $im) {
                preg_match('#<span class="chrono-year">([^<]+)</span>#', $im, $y);
                preg_match('#<span class="chrono-badge\s+([^"]+)">([^<]+)</span>#', $im, $b);
                preg_match('#<a href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#s', $im, $lnk);
                if ($lnk) {
                    $url = $lnk[1];
                    preg_match('#<span>([^<]+)</span>#', $lnk[2], $it);
                    $title = $it[1] ?? $lnk[2];
                } else {
                    $url = '';
                    preg_match('#<h4 class="chrono-name">([^<]+)</h4>#', $im, $t);
                    $title = $t[1] ?? '';
                }
                preg_match('#<span class="chrono-venue">([^<]+)</span>#', $im, $v);
                $items[] = [
                    'year' => isset($y[1]) ? trim($y[1]) : '',
                    'badge' => isset($b[1]) ? trim($b[1]) : 'badge-exhibition',
                    'badge_label' => isset($b[2]) ? trim($b[2]) : 'Sergi',
                    'title' => trim($title),
                    'url' => trim($url),
                    'venue' => isset($v[1]) ? trim($v[1]) : ''
                ];
            }
        }

        $chronology = [
            'title' => $m_ch_title[1] ?? 'Sergi, Yayın ve Programlar',
            'sub' => $m_ch_sub[1] ?? 'Seçilmiş Monografiler, Katılımlar ve Basın Arşivi',
            'items' => $items
        ];

        return [
            'narrative' => $narrative,
            'portrait' => $portrait,
            'contact' => $contact,
            'chronology' => $chronology
        ];
    }
}


/** 404 sayfasının varsayılan metni (Sayfalar › 404 sayfası düzenlenmemişse ya da taslaktaysa) */
function not_found_default_html(string $lang): string {
    return '<p>' . site_t('Bu sayfa kadrajın dışına çıktı.<br>Belki hiç var olmadı, belki taşındı — tıpkı ışık gibi.', 'This page wandered out of the frame.<br>Maybe it never existed, or maybe it moved — like light.', $lang) . '</p>';
}

/**
 * Bakım ve parolalı proje ekranları layout_header'ı yüklemez; site zemini, vurgu rengi
 * ve Zed fontlarıyla aynı görünsünler diye ortak palet + temel CSS.
 */
function standalone_screen_style(): array {
    require_once __DIR__ . '/design_editor.php';
    $surface = get_setting('inner_default_surface', 'dark');
    $bg = ['white' => '#ffffff', 'paper' => '#f4f2ed'][$surface] ?? '#0a0a0a';
    $light = $bg !== '#0a0a0a';
    $accent = preg_match('/^#[0-9a-f]{6}$/i', (string)get_setting('site_accent_color', '')) ? strtolower(get_setting('site_accent_color')) : '#fdd000';
    $p = [
        'light'  => $light,
        'bg'     => $bg,
        'text'   => $light ? '#111111' : '#f2f2f2',
        'sub'    => $light ? '#5c5c5c' : '#9a9a9a',
        'border' => $light ? 'rgba(0,0,0,0.14)' : 'rgba(255,255,255,0.16)',
        'accent' => $accent,
        'on_accent' => design_on_color($accent),
    ];
    $p['css'] = '*{box-sizing:border-box;margin:0;padding:0;border-radius:0}'
        . 'body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;'
        . 'background:' . $p['bg'] . ';color:' . $p['text'] . ';font-family:"ZedTextFtl",-apple-system,BlinkMacSystemFont,sans-serif;line-height:1.6}'
        . '.sc-kicker{font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:' . $p['sub'] . ';margin-bottom:18px;display:flex;align-items:center;gap:10px}'
        . '.sc-kicker::before{content:"";width:28px;height:2px;background:' . $p['accent'] . '}'
        . '.sc-title{font-family:"ZedDisplayFtl","ZedTextFtl",sans-serif;font-weight:400;font-size:clamp(28px,6vw,40px);line-height:1.1;letter-spacing:-.01em;margin-bottom:16px}'
        . '.sc-text{font-size:15px;color:' . $p['sub'] . ';margin-bottom:28px}'
        . '.sc-link{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:' . $p['sub'] . ';text-decoration:none;border-bottom:1px solid transparent}'
        . '.sc-link:hover,.sc-link:focus-visible{color:' . $p['text'] . ';border-bottom-color:' . $p['accent'] . '}'
        . ':focus-visible{outline:2px solid ' . $p['accent'] . ';outline-offset:2px}';
    return $p;
}
