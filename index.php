<?php
/**
 * index.php
 * Sitenin Ön Yüz Yönlendiricisi (Front Controller & Router)
 * 
 * Yeni Dil Tabanlı URL Mimarisi:
 * - Ana Sayfa: /en/ ve /tr/
 * - Projeler, Seriler ve Tekiller: /{lang}/{slug}/ (Örn: /tr/atesin-icinden/ ve /en/through-the-fire/)
 * - Bölüm İndeksleri: /en/projects/, /tr/projeler/, /en/series/, /tr/seriler/, /en/single/, /tr/tekil/
 * - Statik Sayfalar:
 *     Hakkımda: /en/about/ ve /tr/hakkimda/
 *     Aydınlatma Metni: /en/privacy-notice/ ve /tr/aydinlatma-metni/
 *     Açık Rıza Metni: /en/explicit-consent/ ve /tr/acik-riza-metni/
 * - Tüm eski URL yapıları için 301 Kalıcı Yönlendirme (Permanent Redirect).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// URI Ayrıştırma
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$parsed_url = parse_url($request_uri);
$raw_path = trim($parsed_url['path'] ?? '/', '/');

// Script yolunu temizle (subfolder kurulumları için)
$script_path = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($script_path !== '' && strpos($raw_path, $script_path) === 0) {
    $raw_path = trim(substr($raw_path, strlen($script_path)), '/');
}

// Feeds, Sitemap ve Robots doğrudan yönlendirmeleri
if ($raw_path === 'sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    exit;
}
if ($raw_path === 'atom.xml') {
    require __DIR__ . '/atom.php';
    exit;
}
if ($raw_path === 'rss.xml') {
    require __DIR__ . '/rss.php';
    exit;
}
if ($raw_path === 'robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    readfile(__DIR__ . '/robots.txt');
    exit;
}

// Trailing Slash Normalizasyonu (Statik dosya uzantısı olmayan sayfalar için)
$original_path = $parsed_url['path'] ?? '/';
if ($original_path !== '/' && !str_ends_with($original_path, '/') && !pathinfo($original_path, PATHINFO_EXTENSION)) {
    $qs = !empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '';
    header('Location: ' . $original_path . '/' . $qs, true, 301);
    exit;
}

$supported_languages = get_supported_languages(); // Aktif dil kodları (Örn: ['en', 'tr'])
$default_site_lang = get_default_language();
$pdo = DB::getConnection();

// ----------------------------------------------------
// Bakım / "Yenileniyor" Modu Kontrolü (E12)
// ----------------------------------------------------
$maintenance_active = (get_setting('maintenance_mode', '0') === '1');
if ($maintenance_active) {
    if (!is_logged_in()) {
        http_response_code(503);
        header('Retry-After: 3600');
        $site_name = get_setting('site_name', site_owner_name());
        $detected_lang = in_array($raw_path, $supported_languages, true) ? $raw_path : $default_site_lang;
        if (!empty($_COOKIE['site_lang']) && in_array($_COOKIE['site_lang'], $supported_languages, true)) {
            $detected_lang = $_COOKIE['site_lang'];
        }
        $m_title = setting_l('maintenance_title', $detected_lang, '');
        if ($m_title === '') {
            $m_title = site_t('Site Yenileniyor', 'Under Maintenance', $detected_lang);
        }
        $m_text = setting_l('maintenance_text', $detected_lang, '');
        if ($m_text === '') {
            $m_text = site_t('Web sitemiz şu anda güncellenmektedir. Çok yakında yeni çalışmalar ve projelerle tekrar yayında olacağız.', 'Our website is currently undergoing scheduled updates. We will be back shortly with new works and stories.', $detected_lang);
        }
        $sc = standalone_screen_style();
        ?>
        <!DOCTYPE html>
        <html lang="<?= e($detected_lang) ?>" style="color-scheme: <?= $sc['light'] ? 'light' : 'dark' ?>">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= e($m_title) ?> — <?= e($site_name) ?></title>
            <meta name="robots" content="noindex, nofollow">
            <link href="<?= e(asset_url('fonts.css')) ?>" rel="stylesheet">
            <style>
                <?= $sc['css'] ?>
                .m-wrap { max-width: 560px; width: 100%; }
                .m-brand { font-size: 12px; letter-spacing: .14em; text-transform: uppercase; color: <?= $sc['sub'] ?>; border-top: 1px solid <?= $sc['border'] ?>; padding-top: 20px; margin-bottom: 14px; }
            </style>
        </head>
        <body>
            <main class="m-wrap">
                <div class="sc-kicker"><?= site_t('Bakım & Hazırlık', 'Scheduled Maintenance', $detected_lang) ?></div>
                <h1 class="sc-title"><?= e($m_title) ?></h1>
                <p class="sc-text"><?= nl2br(e($m_text)) ?></p>
                <div class="m-brand"><?= e($site_name) ?></div>
                <a href="<?= url('admin/login.php') ?>" class="sc-link"><?= site_t('Yönetici Girişi', 'Admin Login', $detected_lang) ?></a>
            </main>
        </body>
        </html>
        <?php
        exit;
    }
}

// ----------------------------------------------------
// 0. Kök Dizin ( / ) İstekleri -> Tercih Edilen Dile 302 Yönlendirme
// ----------------------------------------------------
if ($raw_path === '') {
    $detected_lang = $default_site_lang;
    if (get_setting('auto_detect_browser_lang', '1') === '1') {
        if (!empty($_COOKIE['site_lang']) && in_array($_COOKIE['site_lang'], $supported_languages, true)) {
            $detected_lang = $_COOKIE['site_lang'];
        } elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $pref = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
            if (in_array($pref, $supported_languages, true)) {
                $detected_lang = $pref;
            }
        }
    }
    header('Location: ' . url($detected_lang . '/'), true, 302);
    exit;
}

// ----------------------------------------------------
// 0a. Pasif veya Çıkartılmış Dil Kökleri -> Varsayılan Dile Yönlendirme
// ----------------------------------------------------
$known_lang_codes = array_unique(array_merge(get_all_language_codes(), array_keys(get_preset_language_catalog())));
$req_segments = explode('/', $raw_path);
$req_first = $req_segments[0] ?? '';
if ($req_first !== '' && !in_array($req_first, $supported_languages, true) && in_array($req_first, $known_lang_codes, true)) {
    if (get_setting('disabled_lang_behavior', 'redirect_default') === 'redirect_default') {
        $req_sub = $req_segments[1] ?? '';
        if ($req_sub === '') {
            header('Location: ' . url($default_site_lang . '/'), true, 302);
            exit;
        }
        header('Location: ' . resolve_slide_url($raw_path, $default_site_lang), true, 302);
        exit;
    }
}

// ----------------------------------------------------
// 0b. Dil Kökleri (/en, /tr, /hy vb.)
// ----------------------------------------------------
if (in_array($raw_path, $supported_languages)) {
    $lang = $raw_path;
    $is_tr = ($lang === 'tr');
    // Cookie güncelle
    if (!headers_sent()) {
        setcookie('site_lang', $lang, ['expires' => time() + 86400 * 365, 'path' => '/', 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    }

    // Ana Sayfa Küratöryel Seçkisi
    // Bağlı proje gizli bağlantı / taslak ise (ya da silindiyse) slayt gösterilmez; proje yayına dönünce geri gelir
    $slides_stmt = $pdo->query("SELECT s.* FROM `homepage_slides` s LEFT JOIN `items` i ON i.`id` = s.`item_id` WHERE s.`is_active` = 1 AND (s.`item_id` IS NULL OR i.`status` = 'published') ORDER BY s.`weight` ASC, s.`id` DESC");
    $slides = $slides_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ana sayfada "bütün çalışmalar" seçiliyse (Şablonlar › ana şablon seçenekleri): seçki yerine
    // sitedeki tüm kategorilerin yayımlanmış çalışmaları, en yeniden eskiye
    if (get_setting('tpl_home_source', 'slides') === 'all') {
        $all_stmt = $pdo->query("SELECT i.* FROM `items` i WHERE i.`status` = 'published' AND i.`type` IN (SELECT `key_slug` FROM `categories`) AND i.`cover_image` IS NOT NULL AND i.`cover_image` <> '' ORDER BY i.`date` DESC, i.`weight` ASC, i.`id` DESC");
        $all_items = $all_stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($all_items) {
            $slides = [];
            foreach ($all_items as $p) {
                // items satırı slayt alanlarını (title_xx, description_xx, location_xx, year) zaten taşır
                $slides[] = $p + ['image_path' => $p['cover_image'], 'link_url' => item_url($p, $lang)];
            }
        }
    }

    if (empty($slides)) {
        $stmt = $pdo->query("SELECT * FROM `items` WHERE `type` = 'project' AND `status` = 'published' ORDER BY `weight` ASC, `date` DESC");
        $raw_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $slides = [];
        foreach ($raw_projects as $p) {
            $slides[] = [
                'id'             => $p['id'],
                'image_path'     => $p['cover_image'],
                'title_tr'       => $p['title_tr'],
                'title_en'       => $p['title_en'],
                'description_tr' => $p['description_tr'],
                'description_en' => $p['description_en'],
                'location'       => item_field($p, 'location', $lang),
                'year'           => $p['year'],
                'link_url'       => item_url($p, $lang),
            ];
        }
    }

    $alternate_en_url = url('en/');
    $alternate_tr_url = url('tr/');
    $alternate_urls = [];
    foreach ($supported_languages as $sl_code) {
        $alternate_urls[$sl_code] = url($sl_code . '/');
    }
    require __DIR__ . '/includes/views/home.php';
    exit;
}

// ----------------------------------------------------
// 1. Siteye özel eski URL yönlendirmeleri (varsa): includes/private/legacy_redirects.php
// ----------------------------------------------------
if (is_file(__DIR__ . '/includes/private/legacy_redirects.php')) {
    require __DIR__ . '/includes/private/legacy_redirects.php';
}

// ----------------------------------------------------
// 2. KANONİK DİL TABANLI YÖNLENDİRME (CANONICAL LANGUAGE ROUTER)
// Format: /{lang}/{sub_path}/
// ----------------------------------------------------
$segments = explode('/', $raw_path);
$lang = $segments[0];

if (!in_array($lang, $supported_languages)) {
    // Tanınmayan kök istek -> 404
    require __DIR__ . '/includes/views/404.php';
    exit;
}

// Tercih edilen dil çerezini güncelle
if (!headers_sent()) {
    setcookie('site_lang', $lang, ['expires' => time() + 86400 * 365, 'path' => '/', 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
}

$sub_path = $segments[1] ?? '';

// ----------------------------------------------------
// 2a. Kategori İndeksleri (Projeler, Seriler, Tekil ve Özel Kategoriler)
// ----------------------------------------------------
$cat_stmt = $pdo->prepare("SELECT * FROM `categories` WHERE `slug_en` = ? OR `slug_tr` = ? OR `key_slug` = ? LIMIT 1");
$cat_stmt->execute([$sub_path, $sub_path, $sub_path]);
$matched_cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);

if ($matched_cat) {
    // Çapraz dil kontrolü (Örn: /tr/books/ -> /tr/kitaplar/ 301 yönlendirme)
    $canonical_cat_slug = !empty($matched_cat['slug_' . $lang])
        ? $matched_cat['slug_' . $lang]
        : (($lang === 'tr') ? $matched_cat['slug_tr'] : $matched_cat['slug_en']);
    if ($sub_path !== $canonical_cat_slug) {
        header('Location: ' . url($lang . '/' . $canonical_cat_slug . '/'), true, 301);
        exit;
    }

    $c_type = $matched_cat['key_slug'];
    $GLOBALS['setting_scope'] = $c_type;
    $stmt = $pdo->prepare("SELECT * FROM `items` WHERE `type` = ? AND `status` = 'published' ORDER BY `weight` ASC, `date` DESC");
    $stmt->execute([$c_type]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $section_type = $c_type;
    $section_title = item_field($matched_cat, 'name', $lang);
    $alternate_en_url = url('en/' . $matched_cat['slug_en'] . '/');
    $alternate_tr_url = url('tr/' . $matched_cat['slug_tr'] . '/');
    $alternate_urls = [];
    foreach ($supported_languages as $sl_code) {
        $sl_cat_slug = !empty($matched_cat['slug_' . $sl_code]) ? $matched_cat['slug_' . $sl_code] : (($sl_code === 'tr') ? $matched_cat['slug_tr'] : $matched_cat['slug_en']);
        $alternate_urls[$sl_code] = url($sl_code . '/' . $sl_cat_slug . '/');
    }
    require __DIR__ . '/includes/views/monograph_cards.php';
    exit;
}

// ----------------------------------------------------
// 2b. Statik Sayfalar (Hakkımda, İletişim, Blog/Yazılar, Saha Rotaları, KVKK)
// ----------------------------------------------------
$lang_about_slug   = get_static_slug('about', $lang);
$lang_contact_slug = get_static_slug('contact', $lang);
$lang_blog_slug    = get_static_slug('blog', $lang);
$lang_field_slug   = get_static_slug('field_work', $lang);
$lang_privacy_slug = get_static_slug('privacy_notice', $lang);
$lang_consent_slug = get_static_slug('explicit_consent', $lang);

if ($sub_path === $lang_contact_slug || in_array($sub_path, ['contact', 'iletisim'], true)) {
    if (get_setting('contact_page_enabled', '0') === '1') {
        if ($sub_path !== $lang_contact_slug) {
            header('Location: ' . url($lang . '/' . $lang_contact_slug . '/'), true, 301);
            exit;
        }
        $alternate_en_url = url('en/' . get_static_slug('contact', 'en') . '/');
        $alternate_tr_url = url('tr/' . get_static_slug('contact', 'tr') . '/');
        $alternate_urls = [];
        foreach ($supported_languages as $sl_code) {
            $alternate_urls[$sl_code] = url($sl_code . '/' . get_static_slug('contact', $sl_code) . '/');
        }
        $url_tr = $alternate_tr_url;
        $url_en = $alternate_en_url;
        require __DIR__ . '/includes/views/contact.php';
        exit;
    } else {
        $anchor = ($lang === 'tr' ? '#iletisim' : '#contact');
        header('Location: ' . url($lang . '/' . $lang_about_slug . '/' . $anchor), true, 302);
        exit;
    }
}

if ($sub_path === $lang_blog_slug || in_array($sub_path, ['blog', 'yazilar', 'haberler', 'journal', 'news'], true)) {
    if (get_setting('blog_enabled', '0') === '1') {
        if ($sub_path !== $lang_blog_slug) {
            header('Location: ' . url($lang . '/' . $lang_blog_slug . '/'), true, 301);
            exit;
        }
        $stmt_b = $pdo->query("SELECT * FROM `items` WHERE `type` = 'blog' AND `status` = 'published' ORDER BY `weight` ASC, `date` DESC, `id` DESC");
        $blog_items = $stmt_b->fetchAll(PDO::FETCH_ASSOC);

        $alternate_en_url = url('en/' . get_static_slug('blog', 'en') . '/');
        $alternate_tr_url = url('tr/' . get_static_slug('blog', 'tr') . '/');
        $alternate_urls = [];
        foreach ($supported_languages as $sl_code) {
            $alternate_urls[$sl_code] = url($sl_code . '/' . get_static_slug('blog', $sl_code) . '/');
        }
        $url_tr = $alternate_tr_url;
        $url_en = $alternate_en_url;
        require __DIR__ . '/includes/views/blog_index.php';
        exit;
    }
}

if ($sub_path === $lang_about_slug || in_array($sub_path, ['about', 'hakkimda'], true)) {
    if ($sub_path !== $lang_about_slug) {
        header('Location: ' . url($lang . '/' . $lang_about_slug . '/'), true, 301);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM `items` WHERE `slug` = 'about' OR `slug_en` = 'about' OR `slug_en` = 'aboutme' OR `slug_tr` = 'hakkimda' LIMIT 1");
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $alternate_en_url = url('en/about/');
        $alternate_tr_url = url('tr/hakkimda/');
        $alternate_urls = [];
        foreach ($supported_languages as $sl_code) {
            $alternate_urls[$sl_code] = url($sl_code . '/' . get_static_slug('about', $sl_code) . '/');
        }
        require __DIR__ . '/includes/views/about.php';
        exit;
    }
}

if (field_map_enabled() && ($sub_path === $lang_field_slug || in_array($sub_path, ['field-work', 'saha-rotalari'], true))) {
    if ($sub_path !== $lang_field_slug) {
        header('Location: ' . url($lang . '/' . $lang_field_slug . '/'), true, 301);
        exit;
    }
    $alternate_en_url = url('en/field-work/');
    $alternate_tr_url = url('tr/saha-rotalari/');
    $alternate_urls = [];
    foreach ($supported_languages as $sl_code) {
        $alternate_urls[$sl_code] = url($sl_code . '/' . get_static_slug('field_work', $sl_code) . '/');
    }
    require __DIR__ . '/includes/private/field_map.php';
    exit;
}

if ($sub_path === $lang_privacy_slug || in_array($sub_path, ['privacy-notice', 'aydinlatma-metni'], true)) {
    if (get_setting('legal_pages_enabled', '1') != '1') {
        http_response_code(404);
        require __DIR__ . '/includes/views/404.php';
        exit;
    }
    if ($sub_path !== $lang_privacy_slug) {
        header('Location: ' . url($lang . '/' . $lang_privacy_slug . '/'), true, 301);
        exit;
    }
    $legal_type = 'aydinlatma';
    $alternate_en_url = url('en/privacy-notice/');
    $alternate_tr_url = url('tr/aydinlatma-metni/');
    $alternate_urls = [];
    foreach ($supported_languages as $sl_code) {
        $alternate_urls[$sl_code] = url($sl_code . '/' . get_static_slug('privacy_notice', $sl_code) . '/');
    }
    require __DIR__ . '/includes/views/legal.php';
    exit;
}

if ($sub_path === $lang_consent_slug || in_array($sub_path, ['explicit-consent', 'acik-riza-metni'], true)) {
    if (get_setting('legal_pages_enabled', '1') != '1') {
        http_response_code(404);
        require __DIR__ . '/includes/views/404.php';
        exit;
    }
    if ($sub_path !== $lang_consent_slug) {
        header('Location: ' . url($lang . '/' . $lang_consent_slug . '/'), true, 301);
        exit;
    }
    $legal_type = 'acik_riza';
    $alternate_en_url = url('en/explicit-consent/');
    $alternate_tr_url = url('tr/acik-riza-metni/');
    $alternate_urls = [];
    foreach ($supported_languages as $sl_code) {
        $alternate_urls[$sl_code] = url($sl_code . '/' . get_static_slug('explicit_consent', $sl_code) . '/');
    }
    require __DIR__ . '/includes/views/legal.php';
    exit;
}

// ----------------------------------------------------
// 2c. Saf Dil Tabanlı Öğe Detay Sayfası (/{lang}/{slug}/)
// ----------------------------------------------------
$slug = $sub_path;

if ($slug !== '') {
    $status_sql = is_logged_in() ? "`status` IN ('published', 'unlisted', 'draft')" : "`status` IN ('published', 'unlisted')";
    // 404 sistem sayfası (type 'page', slug '404') yalnızca bulunamayan adreslerde gösterilir
    $status_sql .= " AND NOT (`type` = 'page' AND `slug` = '404')";
    $stmt = $pdo->prepare("SELECT * FROM `items` WHERE (`slug_en` = ? OR `slug_tr` = ? OR `slug` = ?) AND {$status_sql} LIMIT 1");
    $stmt->execute([$slug, $slug, $slug]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    // Eğer doğrudan eşleşmediyse alias'lardan ara
    if (!$item) {
        $alias_needle = "%/" . $slug . "/%";
        $alias_stmt = $pdo->prepare("SELECT * FROM `items` WHERE `aliases` LIKE ? AND {$status_sql} LIMIT 1");
        $alias_stmt->execute([$alias_needle]);
        $item = $alias_stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($item) {
        $GLOBALS['setting_scope'] = (string)($item['type'] ?? '');
        $GLOBALS['setting_item_scope'] = 'item-' . (int)$item['id'];
        if (($item['type'] ?? '') === 'blog' && get_setting('blog_enabled', '0') !== '1' && !is_logged_in()) {
            http_response_code(404);
            require __DIR__ . '/includes/views/404.php';
            exit;
        }

        // C6: Parola Korumalı Projeler (Password-Protected Work Gate)
        if (!empty($item['access_password']) && !is_logged_in()) {
            init_session();
            $session_key = 'item_unlocked_' . (int)$item['id'];
            $password_error = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_password'])) {
                $submitted_pass = trim($_POST['item_password'] ?? '');
                // Admin girişiyle aynı IP kilidi: 15 dk'da 5 hatalı deneme
                if (($lock_min = login_lock_remaining_minutes()) > 0) {
                    $password_error = site_t("Çok fazla hatalı deneme. {$lock_min} dakika sonra tekrar deneyin.", "Too many failed attempts. Try again in {$lock_min} minutes.");
                } elseif (item_password_verify((string)$item['access_password'], $submitted_pass)) {
                    $_SESSION[$session_key] = true;
                    header('Location: ' . item_url($item, $lang));
                    exit;
                } else {
                    login_record_failure();
                    usleep(500000);
                    $password_error = site_t('Hatalı parola girdiniz. Lütfen tekrar deneyin.', 'Incorrect password. Please try again.');
                }
            }

            if (empty($_SESSION[$session_key])) {
                $page_title = item_field($item, 'title', $lang);
                $site_name = get_setting('site_name', site_owner_name());
                $cover_bg_url = !empty($item['cover_image']) ? get_cover_image_url($item['cover_image']) : '';
                $sc = standalone_screen_style();
                ?>
                <!DOCTYPE html>
                <html lang="<?= e($lang) ?>" style="color-scheme: <?= $sc['light'] ? 'light' : 'dark' ?>">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title><?= e($page_title) ?> — <?= site_t('Korumalı Proje', 'Protected Work') ?></title>
                    <meta name="robots" content="noindex, nofollow">
                    <link href="<?= e(asset_url('fonts.css')) ?>" rel="stylesheet">
                    <style>
                        <?= $sc['css'] ?>
                        body { position: relative; overflow: hidden; }
                        <?php if ($cover_bg_url !== ''): ?>
                        /* Kapak fotoğrafı: bulanık değil, karartılmış tam kadraj — fotoğraf okunur kalır */
                        .bg-backdrop { position: absolute; inset: 0; background: url('<?= e($cover_bg_url) ?>') center / cover no-repeat; opacity: <?= $sc['light'] ? '0.14' : '0.22' ?>; filter: grayscale(1); z-index: 0; }
                        <?php endif; ?>
                        .gate-card { position: relative; z-index: 1; max-width: 460px; width: 100%; padding: 44px 36px; background: <?= $sc['bg'] ?>; border: 1px solid <?= $sc['border'] ?>; }
                        .gate-input { width: 100%; padding: 14px 0; margin-bottom: 18px; border: 0; border-bottom: 1px solid <?= $sc['border'] ?>; background: transparent; color: inherit; font: inherit; font-size: 15px; letter-spacing: .08em; outline: none; }
                        .gate-input:focus { border-bottom-color: <?= $sc['accent'] ?>; }
                        .gate-input:focus-visible { outline: none; }
                        .gate-btn { width: 100%; padding: 14px; border: 1px solid <?= $sc['accent'] ?>; background: <?= $sc['accent'] ?>; color: <?= $sc['on_accent'] ?>; font: inherit; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; cursor: pointer; transition: opacity .15s ease; }
                        .gate-btn:hover { opacity: .88; }
                        .gate-error { font-size: 13px; color: #e5484d; border-left: 2px solid #e5484d; padding: 6px 12px; margin-bottom: 18px; }
                        .gate-back { display: inline-block; margin-top: 24px; }
                        @media (max-width: 480px) { .gate-card { padding: 32px 22px; } }
                    </style>
                </head>
                <body>
                    <?php if ($cover_bg_url !== ''): ?><div class="bg-backdrop" aria-hidden="true"></div><?php endif; ?>
                    <main class="gate-card">
                        <div class="sc-kicker"><?= site_t('Özel & Gizli Proje', 'Private & Protected Work') ?></div>
                        <h1 class="sc-title"><?= e($page_title) ?></h1>
                        <p class="sc-text"><?= site_t('Bu sergi ve içeriğe erişebilmek için lütfen tarafınıza iletilen parolayı giriniz.', 'Please enter the access password provided to you to view this curated story.') ?></p>
                        <?php if ($password_error): ?>
                            <div class="gate-error" role="alert"><?= e($password_error) ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <label for="gate-pw" class="sc-link" style="display:block;margin-bottom:2px"><?= site_t('Proje Parolası', 'Project Password') ?></label>
                            <input type="password" id="gate-pw" name="item_password" class="gate-input" autocomplete="current-password" autofocus required>
                            <button type="submit" class="gate-btn"><?= site_t('Sergiyi Aç', 'Unlock Project') ?></button>
                        </form>
                        <a href="<?= url($lang . '/') ?>" class="sc-link gate-back">&larr; <?= site_t('Ana Sayfaya Dön', 'Back to Home') ?></a>
                    </main>
                </body>
                </html>
                <?php
                exit;
            }
        }

        $canonical_slug = item_slug($item, $lang);

        // Eğer mevcut dildeki kanonik slug ile ziyaret edilen slug uyuşmuyorsa
        // (Örn: /tr/through-the-fire/ -> /tr/atesin-icinden/ veya bir alias) -> 301 Kalıcı Yönlendirme!
        if ($slug !== $canonical_slug) {
            header('Location: ' . item_url($item, $lang), true, 301);
            exit;
        }

        // Önceki ve Sonraki Öğeler (Aynı tipteki yayınlanmış öğeler arasında - C6: unlisted gizlenir)
        if (($item['status'] ?? '') === 'unlisted') {
            $prev_item = null;
            $next_item = null;
        } else {
            $type = $item['type'];
            $all_stmt = $pdo->prepare("SELECT id, type, slug, slug_en, slug_tr, title_en, title_tr, cover_image, location, location_en, location_tr, year, date FROM `items` WHERE `type` = ? AND `status` = 'published' ORDER BY `weight` ASC, `date` DESC");
            $all_stmt->execute([$type]);
            $all_type_items = $all_stmt->fetchAll(PDO::FETCH_ASSOC);

            $curr_idx = -1;
            foreach ($all_type_items as $idx => $it) {
                if ($it['id'] == $item['id']) {
                    $curr_idx = $idx;
                    break;
                }
            }
            $prev_item = ($curr_idx > 0) ? $all_type_items[$curr_idx - 1] : null;
            $next_item = ($curr_idx >= 0 && $curr_idx < count($all_type_items) - 1) ? $all_type_items[$curr_idx + 1] : null;
        }

        $alternate_en_url = item_url($item, 'en');
        $alternate_tr_url = item_url($item, 'tr');
        $alternate_urls = [];
        foreach ($supported_languages as $sl_code) {
            $alternate_urls[$sl_code] = item_url($item, $sl_code);
        }

        // Bağımsız sayfa ve blog yazısı: sade metin sayfası (ikisi de blog-edit.php ile yazılır).
        // Bağımsız sayfa şablon seçtiyse (page_view@item-<id>) Hakkımda / İletişim / proje görünümüyle açılır.
        $view = 'page.php';
        if (in_array($item['type'] ?? '', ['page', 'blog'], true)) {
            $pv = ($item['type'] === 'page' && $item['slug'] !== '404') ? get_setting('page_view', '') : '';
            $view = ['about' => 'about.php', 'contact' => 'contact.php', 'story' => 'page.php'][$pv] ?? 'standalone.php';
        }
        require __DIR__ . '/includes/views/' . $view;
        exit;
    }
}

// ----------------------------------------------------
// 3. Eşleşmeyen Rotalarda 404 Kadraj Dışı
// ----------------------------------------------------
require __DIR__ . '/includes/views/404.php';
