<?php
/**
 * includes/views/layout_header.php
 * Sitenin Üst Başlık ve Gezinti Alanı (Header & Site Navigation)
 * Zola Apollo temasının orijinal HTML yapısıyla %100 birebirdir.
 */

require_once __DIR__ . '/../helpers.php';

$lang = $lang ?? 'en';
$is_tr = ($lang === 'tr');
$curr_lang_info = get_language_info($lang);
$fb_lang = $curr_lang_info['fallback_lang'] ?? (($is_tr ? 'en' : 'tr'));

$site_title = get_setting('site_title_' . $lang, '');
if ($site_title === '') {
    $site_title = get_setting('site_title_' . $fb_lang, setting_l('site_title', null, site_owner_name()));
}
$site_desc = get_setting('site_desc_' . $lang, '');
if ($site_desc === '') {
    $site_desc = get_setting('site_desc_' . $fb_lang, setting_l('site_desc', null, ''));
}

$doc_title = !empty($page_title) ? ($page_title . ' - ' . $site_title) : $site_title;
$doc_desc = !empty($page_desc) ? $page_desc : $site_desc;
// Sayfaya özel SEO başlığı ve paylaşım görseli (Sayfalar › Ayarlar); yalnızca öğe sayfalarında (setting_item_scope)
if (!empty($GLOBALS['setting_item_scope'])) {
    $seo_t = trim(get_setting('item_seo_title_' . ($lang ?? 'en'), ''));
    if ($seo_t !== '') $doc_title = $seo_t;
    $seo_og = trim(get_setting('item_og_image', ''));
    if ($seo_og !== '') $og_image = url($seo_og);
}

$current_path = $current_path ?? ($_SERVER['REQUEST_URI'] ?? '/');
$parsed_curr = parse_url($current_path, PHP_URL_PATH);
$clean_curr = '/' . trim($parsed_curr, '/') . ($parsed_curr === '/' || $parsed_curr === '' ? '' : '/');

$canonical_url = url($clean_curr);

$html_class = $html_class ?? 'dark editorial-page';
$ga_id = get_setting('google_analytics', '');
$instagram_url = get_setting('instagram_url', '');

// Dil rotaları ve aktif diller
$active_site_langs = get_active_languages();
$curr_lang_info = get_language_info($lang);
$lang_dir = $curr_lang_info['direction'] ?? 'ltr';

if (!empty($alternate_en_url)) {
    $en_path = $alternate_en_url;
} else {
    $en_path = $is_tr ? preg_replace('#^/tr(/|$)#', '/en$1', $clean_curr) : $clean_curr;
    if ($en_path === '/' || $en_path === '') $en_path = '/en/';
}

if (!empty($alternate_tr_url)) {
    $tr_path = $alternate_tr_url;
} else {
    $tr_path = !$is_tr ? preg_replace('#^/en(/|$)#', '/tr$1', $clean_curr) : $clean_curr;
    if ($tr_path === '/' || $tr_path === '') $tr_path = '/tr/';
}

$GLOBALS['er_alternate_urls'] = $alternate_urls ?? [];
$GLOBALS['er_alternate_urls']['en'] = $GLOBALS['er_alternate_urls']['en'] ?? url($en_path);
$GLOBALS['er_alternate_urls']['tr'] = $GLOBALS['er_alternate_urls']['tr'] ?? url($tr_path);
foreach ($active_site_langs as $asl) {
    $ac = $asl['code'];
    if (empty($GLOBALS['er_alternate_urls'][$ac])) {
        $GLOBALS['er_alternate_urls'][$ac] = resolve_slide_url($clean_curr, $ac) ?: url($ac . '/');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($lang_dir) ?>" class="<?= e($html_class) ?>">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="base" content="<?= e(BASE_URL) ?>" />
    <meta name="color-scheme" content="light dark" />
    <?php if (get_setting('seo_hreflang_enabled', '1') === '1' && count($active_site_langs) > 1): ?>
        <?php foreach ($active_site_langs as $asl):
            $ac = $asl['code'];
            $alt_href = $GLOBALS['er_alternate_urls'][$ac] ?? url($ac . '/');
        ?>
    <link rel="alternate" hreflang="<?= e($ac) ?>" href="<?= e($alt_href) ?>" />
        <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e($GLOBALS['er_alternate_urls'][get_default_language()] ?? url(get_default_language() . '/')) ?>" />
    <?php endif; ?>
    <script>
    (function() {
        try {
            var root = document.documentElement;
            var isHomepage = root.classList.contains('hp-page');
            var isFieldMap = root.classList.contains('field-map-page');
            var saved = localStorage.getItem('er_surface_theme');
            if (saved === 'paper') {
                saved = 'white';
                try { localStorage.setItem('er_surface_theme', 'white'); } catch(e){}
            }
            var defaultSurface = <?= json_encode(
                (isset($html_class) && strpos($html_class, 'monograph-cards-page') !== false)
                    ? (in_array(get_setting('pf_default_surface', 'dark'), ['white', 'paper'], true) ? get_setting('pf_default_surface') : 'dark')
                    : ((isset($html_class) && strpos($html_class, 'hp-page') !== false)
                        ? (in_array(get_setting('hp_default_surface', 'dark'), ['white', 'paper'], true) ? get_setting('hp_default_surface') : 'dark')
                        : ((isset($html_class) && strpos($html_class, 'blog-page') !== false)
                            ? (in_array(get_setting('blog_default_surface', 'dark'), ['white', 'paper'], true) ? get_setting('blog_default_surface') : 'dark')
                            : ((isset($html_class) && strpos($html_class, 'contact-page') !== false)
                                ? (in_array(get_setting('contact_page_surface', 'dark'), ['white', 'paper'], true) ? get_setting('contact_page_surface') : 'dark')
                                : ((isset($html_class) && strpos($html_class, 'aboutme-page') !== false)
                                    ? (in_array(get_setting('about_default_surface', 'dark'), ['white', 'paper'], true) ? get_setting('about_default_surface') : 'dark')
                                    : (in_array(get_setting('inner_default_surface', 'dark'), ['white', 'paper'], true) ? get_setting('inner_default_surface') : 'dark')))))
            ) ?>;
            // Ziyaretçinin düğmeyle seçtiği zemin, yalnızca seçim anındaki site zemini hâlâ aynıysa geçerlidir;
            // panelde zemin değiştirilince eski tercih yok sayılır.
            var siteBase = (defaultSurface === 'paper' || defaultSurface === 'white') ? 'white' : 'dark';
            window.erSiteSurfaceBase = siteBase;
            if (saved && localStorage.getItem('er_surface_theme_base') !== siteBase) {
                saved = null;
                try { localStorage.removeItem('er_surface_theme'); localStorage.removeItem('er_surface_theme_base'); } catch(e){}
            }
            // Yönetici önizlemesinde (tema özelleştirme) ziyaretçi tercihi yok sayılır: seçilen zemin görünsün
            if (<?= (function_exists('style_preview_key') && style_preview_key()) ? 'true' : 'false' ?>) saved = null;
            var mode = (saved === 'white' || saved === 'dark') ? saved : siteBase;
            if (isFieldMap) mode = 'dark';
            root.setAttribute('data-surface-theme', mode);
            // Açık zeminin tonu: kâğıt ya da gri (extra-templates.css → html.er-tone-*[data-surface-theme="white"])
            var tone = <?= json_encode(in_array(get_setting('site_surface_tone', ''), ['paper', 'gray'], true) ? get_setting('site_surface_tone') : '') ?>;
            if (!tone && defaultSurface === 'paper') tone = 'paper';
            // Temalar › Renkler › Özel zemin rengi (yalnızca açık zeminde; koyu tonlar kayıtta reddedilir)
            var customBg = <?= json_encode(preg_match('/^#[0-9a-f]{6}$/i', (string)get_setting('site_surface_custom', '')) ? get_setting('site_surface_custom') : '') ?>;
            if (customBg && siteBase === 'white') { tone = 'custom'; root.style.setProperty('--er-tone-bg', customBg); }
            if (tone) root.classList.add('er-tone-' + tone);
            // Ziyaretçi açık/koyu düğmesiyle zemini değiştirmediyse sitenin kendi renkleri (yazı rengi vb.) geçerli
            if (mode === siteBase) root.classList.add('er-site-surface');
            var meta = document.querySelector('meta[name="color-scheme"]');
            if (isHomepage) {
                if (mode === 'white') {
                    root.classList.remove('dark', 'hp-surface-dark', 'gallery-paper', 'hp-surface-paper');
                    root.classList.add('light', 'gallery-white', 'hp-surface-white');
                    if (meta) meta.content = 'light';
                } else {
                    root.classList.remove('light', 'gallery-paper', 'hp-surface-paper', 'gallery-white', 'hp-surface-white');
                    if (!root.classList.contains('dark')) root.classList.add('dark');
                    root.classList.add('hp-surface-dark');
                    if (meta) meta.content = 'dark';
                }
            } else if (!isFieldMap && mode === 'white') {
                root.classList.remove('dark', 'about-surface-dark', 'gallery-paper', 'about-surface-paper');
                root.classList.add('light', 'gallery-white', 'about-surface-white');
                if (meta) meta.content = 'light';
            } else {
                root.classList.remove('light', 'gallery-paper', 'about-surface-paper', 'gallery-white', 'about-surface-white');
                if (!root.classList.contains('dark')) root.classList.add('dark');
                root.classList.add('about-surface-dark');
                if (meta) meta.content = 'dark';
            }
        } catch (e) {}
    })();
    </script>

    <title><?= e($doc_title) ?></title>
    <meta property="og:title" content="<?= e($doc_title) ?>" />
    <meta property="og:description" content="<?= e($doc_desc) ?>" />
    <meta name="description" content="<?= e($doc_desc) ?>" />
    <meta property="og:url" content="<?= e($canonical_url) ?>" />
    <meta property="og:site_name" content="<?= e($site_title) ?>" />
    <meta property="og:type" content="<?= !empty($is_single_item) ? 'article' : 'website' ?>" />

    <?php 
    $custom_og_def = trim((string)get_setting('default_og_image', ''));
    if ($custom_og_def === '') { // varsayılan paylaşım görseli seçilmemişse ana sayfanın ilk slaytı
        try { $custom_og_def = ltrim((string)DB::getConnection()->query("SELECT `image_path` FROM `homepage_slides` WHERE `is_active` = 1 ORDER BY `weight` ASC, `id` DESC LIMIT 1")->fetchColumn(), '/'); } catch (\Throwable $e) {}
    }
    $default_share_image = $custom_og_def !== '' ? url($custom_og_def) : '';
    $final_og_image = !empty($og_image) ? $og_image : $default_share_image;
    ?>
    <?php if ($final_og_image !== ''): ?><meta property="og:image" content="<?= e($final_og_image) ?>" /><?php endif; ?>
    <meta name="twitter:image" content="<?= e($final_og_image) ?>" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= e($doc_title) ?>" />
    <meta name="twitter:description" content="<?= e($doc_desc) ?>" />

    <?php
    $favicon_enabled = (int)get_setting('brand_favicon_enabled', 1);
    $custom_favicon  = trim((string)get_setting('brand_favicon', ''));
    if ($favicon_enabled):
        $favicon_href = $custom_favicon !== '' ? url($custom_favicon) : url('assets/brand/favicon.png');
        $fav_ext = strtolower(pathinfo($favicon_href, PATHINFO_EXTENSION));
        $fav_mime = match ($fav_ext) {
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };
    ?>
    <link rel="icon" type="<?= e($fav_mime) ?>" href="<?= e($favicon_href) ?>" />
    <link rel="apple-touch-icon" href="<?= e($favicon_href) ?>" />
    <?php else: ?>
    <link rel="icon" href="data:," />
    <?php endif; ?>

    <!-- Fonts -->
    <link rel="preload" href="<?= e(url('fonts/zed-fonts/ZedTextL-Regular.woff2')) ?>" as="font" type="font/woff2" crossorigin />
    <link rel="preload" href="<?= e(url('fonts/zed-fonts/ZedTextL-Bold.woff2')) ?>" as="font" type="font/woff2" crossorigin />
    <link rel="preload" href="<?= e(url('fonts/zed-fonts/ZedDisplayL-Heavy.woff2')) ?>" as="font" type="font/woff2" crossorigin />
    <link href="<?= e(asset_url('fonts.css')) ?>" rel="stylesheet" />

    <!-- Google Analytics + KVKK Çerez Onayı (analitik yalnızca onay varsa yüklenir) -->
    <?php
    require_once __DIR__ . '/../cookie_consent.php';
    render_cookie_consent_head($lang, (string)$ga_id);
    ?>

    <!-- Feeds -->
    <link rel="alternate" type="application/atom+xml" title="<?= e($site_title) ?>" href="<?= e(url('atom.xml')) ?>" />
    <link rel="alternate" type="application/rss+xml" title="<?= e($site_title) ?>" href="<?= e(url('rss.xml')) ?>" />

    <!-- Stylesheets -->
    <link rel="stylesheet" type="text/css" href="<?= e(asset_url('theme/light.css')) ?>" />
    <link rel="stylesheet" type="text/css" href="<?= e(asset_url('theme/dark.css')) ?>" />
    <link rel="stylesheet" type="text/css" media="screen" href="<?= e(asset_url('main.css')) ?>" />
    <link rel="stylesheet" type="text/css" href="<?= e(asset_url('custom.css')) ?>" />
    <link rel="stylesheet" type="text/css" href="<?= e(asset_url('extra-templates.css')) ?>" />
<?php
    // Temalar › Renkler ve Yazı tipleri (includes/design_editor.php). Ayarlanmamışsa hiçbir şey eklenmez.
    require_once __DIR__ . '/../design_editor.php';
    $dz_accent = preg_match('/^#[0-9a-f]{6}$/i', (string)get_setting('site_accent_color', '')) ? strtolower(get_setting('site_accent_color')) : '';
    $dz_text   = preg_match('/^#[0-9a-f]{6}$/i', (string)get_setting('site_text_color', '')) ? strtolower(get_setting('site_text_color')) : '';
    $dz_hfont  = (string)get_setting('site_heading_font', '');
    $dz_bfont  = (string)get_setting('site_body_font', '');
    $dz_scale  = max(85, min(120, (int)get_setting('site_font_scale', '100')));
    $dz_css    = [];
    $dz_root   = [];
    if ($dz_accent !== '') {
        $dz_root[] = '--er-accent:' . $dz_accent;
        $dz_root[] = '--er-accent-deep:color-mix(in srgb, ' . $dz_accent . ' 78%, #000)';
        $dz_root[] = '--er-on-accent:' . design_on_color($dz_accent);
    }
    if ($dz_hfont !== '' && isset(design_font_choices()[$dz_hfont]) && $dz_hfont !== 'display') $dz_root[] = '--header-font:' . design_font_stack($dz_hfont);
    if ($dz_bfont !== '' && isset(design_font_choices()[$dz_bfont]) && $dz_bfont !== 'sans') $dz_root[] = '--text-font:' . design_font_stack($dz_bfont, 'sans');
    if ($dz_scale !== 100) {
        $dz_root[] = '--font-size-base:' . round(16 * $dz_scale / 100, 2) . 'px';
        $dz_css[]  = 'html{font-size:var(--font-size-base)}';
        $dz_css[]  = '@media (max-width:992px){html{font-size:calc(var(--font-size-base)*.97)}}';
        $dz_css[]  = '@media (max-width:768px){html{font-size:calc(var(--font-size-base)*.95)}}';
        $dz_css[]  = '@media (max-width:576px){html{font-size:calc(var(--font-size-base)*.92)}}';
    }
    // Temalar › Düğmeler ve formlar: "theme" (varsayılan) seçildiyse temanın görünümüne dokunulmaz
    $dz_btn   = ':is(.not-found-btn,.contact-submit-btn,.gate-btn,.about-contact-btn,.about-email-btn)';
    $dz_field = ':is(.contact-input,.contact-textarea,.gate-input)';
    $dz_rad   = ['square' => '0', 'rounded' => '6px', 'pill' => '999px'][get_setting('site_button_shape', 'theme')] ?? null;
    if ($dz_rad !== null) $dz_css[] = $dz_btn . '{border-radius:' . $dz_rad . ' !important}';
    $dz_bstyle = get_setting('site_button_style', 'theme');
    if ($dz_bstyle === 'filled')  $dz_css[] = $dz_btn . '{background:var(--er-accent,#fdd000) !important;color:var(--er-on-accent,#111) !important;border:1px solid var(--er-accent,#fdd000) !important}';
    if ($dz_bstyle === 'outline') $dz_css[] = $dz_btn . '{background:transparent !important;color:inherit !important;border:1px solid currentColor !important}' . $dz_btn . ':hover{background:var(--er-accent,#fdd000) !important;color:var(--er-on-accent,#111) !important;border-color:var(--er-accent,#fdd000) !important}';
    $dz_case = get_setting('site_button_case', 'theme');
    if ($dz_case === 'uppercase') $dz_css[] = $dz_btn . '{text-transform:uppercase !important;letter-spacing:.06em !important}';
    if ($dz_case === 'normal')    $dz_css[] = $dz_btn . '{text-transform:none !important;letter-spacing:normal !important}';
    $dz_form = get_setting('site_form_style', 'theme');
    if ($dz_form === 'boxed')     $dz_css[] = $dz_field . '{border:1px solid color-mix(in srgb,currentColor 30%,transparent) !important;border-radius:' . ($dz_rad === '999px' ? '22px' : ($dz_rad ?? '6px')) . ' !important;background:transparent !important;padding:.75em .9em !important}';
    if ($dz_form === 'underline') $dz_css[] = $dz_field . '{border:0 !important;border-bottom:1px solid color-mix(in srgb,currentColor 35%,transparent) !important;border-radius:0 !important;background:transparent !important;padding-left:0 !important;padding-right:0 !important}';
    if ($dz_root) array_unshift($dz_css, ':root,:root.light,:root.dark{' . implode(';', $dz_root) . '}');
    if ($dz_text !== '') {
        // Fotoğraf üzerindeki yazılar, menü ve alt bilgi hariç; yalnızca sitenin kendi zemininde
        $dz_scope = 'html.er-site-surface:not(#er-text) :is(h1,h2,h3,h4,h5,h6,p,li,dt,dd,blockquote,figcaption,td,th,label,small):not(:is(header,nav,footer,#om-site-header,.hp-chapter-slide,.mono-chapter,.hp-slide-visual-area,.xt-tg-item,.contact-modal-dialog,[class*="overlay"],[class*="hero"],[class*="caption-over"]) *)';
        $dz_css[] = $dz_scope . '{color:' . $dz_text . ' !important}';
    }
    if ($dz_css):
?>
    <style id="er-design-tokens"><?= implode("\n", $dz_css) ?></style>
<?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.3.0/css/glightbox.min.css" />
    <?php if (!empty($html_class) && strpos($html_class, 'field-map') !== false): ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <?php endif; ?>
    <?php if (in_array($lang, ['hy', 'hyw', 'ar', 'ru', 'el', 'ja'], true) || $lang_dir === 'rtl'): ?>
    <!-- Çoklu Alfabe (Multi-Script) ve RTL Tipografi Desteği -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <?php if ($lang === 'hy' || $lang === 'hyw'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Armenian:wght@400;500;600;700;800&family=Noto+Serif+Armenian:wght@400;600;700&display=swap" rel="stylesheet" />
    <?php elseif ($lang === 'ar' || $lang_dir === 'rtl'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700;800&family=Noto+Naskh+Arabic:wght@400;600;700&display=swap" rel="stylesheet" />
    <?php elseif ($lang === 'ja'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet" />
    <?php endif; ?>
    <style>
        html[lang|="hy"] body, html[lang|="hy"] h1, html[lang|="hy"] h2, html[lang|="hy"] h3, html[lang|="hy"] p, html[lang="hyw"] body, html[lang="hyw"] h1, html[lang="hyw"] h2, html[lang="hyw"] h3, html[lang="hyw"] p {
            font-family: "ZedTextL", "Noto Sans Armenian", -apple-system, BlinkMacSystemFont, sans-serif;
        }
        html[lang="ar"] body, html[dir="rtl"] body, html[dir="rtl"] h1, html[dir="rtl"] h2, html[dir="rtl"] h3, html[dir="rtl"] p {
            font-family: "ZedTextL", "Noto Sans Arabic", -apple-system, BlinkMacSystemFont, sans-serif;
        }
        html[lang="ja"] body, html[lang="ja"] h1, html[lang="ja"] h2, html[lang="ja"] h3, html[lang="ja"] p {
            font-family: "ZedTextL", "Noto Sans JP", -apple-system, BlinkMacSystemFont, sans-serif;
        }
        html[dir="rtl"] .editorial-story-body,
        html[dir="rtl"] .project-split-left,
        html[dir="rtl"] .aboutme-content,
        html[dir="rtl"] .mono-card-content {
            direction: rtl;
            text-align: right;
        }
    </style>
    <?php endif; ?>

    <!-- Google & Schema.org JSON-LD (E11) -->
    <?php
    $site_name = get_setting('site_name', site_owner_name());
    $site_url = url('');
    $schema_graph = [];

    // 1. Photographer (Person)
    $schema_graph[] = [
        '@type' => 'Person',
        '@id' => $site_url . '#photographer',
        'name' => site_owner_name(),
        'url' => $site_url,
        'jobTitle' => setting_l('header_logo_subtext', null, ''),
        'sameAs' => array_values(array_filter([
            get_setting('instagram_url', ''),
            get_setting('x_url', ''),
            get_setting('youtube_url', ''),
            get_setting('facebook_url', ''),
            get_setting('linkedin_url', ''),
            get_setting('behance_url', '')
        ]))
    ];

    // 2. WebSite
    $schema_graph[] = [
        '@type' => 'WebSite',
        '@id' => $site_url . '#website',
        'url' => $site_url,
        'name' => $site_name,
        'inLanguage' => $lang
    ];

    // 3. Proje / Görsel Sanat Eseri Şeması (VisualArtwork & ImageGallery veya BlogPosting)
    if (!empty($item)) {
        $cur_title = item_field($item, 'title', $lang);
        $cur_desc = item_field($item, 'description', $lang);
        $cur_url = item_url($item, $lang);
        $cur_cover = !empty($item['cover_image']) ? get_cover_image_url($item['cover_image']) : '';

        if (($item['type'] ?? '') === 'blog') {
            $schema_graph[] = [
                '@type' => 'BlogPosting',
                '@id' => $cur_url . '#article',
                'headline' => $cur_title,
                'description' => $cur_desc ?: $cur_title,
                'image' => $cur_cover ? [$cur_cover] : [],
                'datePublished' => !empty($item['date']) ? date('c', strtotime($item['date'])) : null,
                'dateModified' => !empty($item['updated_at']) ? date('c', strtotime($item['updated_at'])) : null,
                'author' => ['@id' => $site_url . '#photographer'],
                'inLanguage' => $lang,
                'mainEntityOfPage' => $cur_url
            ];
        } else {
            $item_images = [];
            if ($cur_cover) $item_images[] = $cur_cover;
            
            $raw_c = $item['content_' . $lang] ?? ($item['content_tr'] ?? '');
            if (!empty($raw_c) && str_starts_with(trim($raw_c), '{')) {
                $p_obj = json_decode($raw_c, true);
                if (!empty($p_obj['blocks']) && is_array($p_obj['blocks'])) {
                    foreach ($p_obj['blocks'] as $bk) {
                        if (($bk['type'] ?? '') === 'photo' && !empty($bk['path'])) {
                            $item_images[] = get_cover_image_url($bk['path']);
                        }
                    }
                }
            }

            $schema_graph[] = [
                '@type' => 'VisualArtwork',
                '@id' => $cur_url . '#artwork',
                'name' => $cur_title,
                'description' => $cur_desc ?: $cur_title,
                'artform' => 'Photography',
                'artMedium' => 'Color & Monochrome Photography',
                'image' => array_values(array_slice(array_unique($item_images), 0, 12)),
                'creator' => ['@id' => $site_url . '#photographer'],
                'dateCreated' => $item['year'] ?? (!empty($item['date']) ? date('Y', strtotime($item['date'])) : null),
                'contentLocation' => item_field($item, 'location', $lang) ?: null,
                'inLanguage' => $lang,
                'url' => $cur_url
            ];

            if (count($item_images) > 1) {
                $schema_graph[] = [
                    '@type' => 'ImageGallery',
                    '@id' => $cur_url . '#gallery',
                    'name' => $cur_title,
                    'url' => $cur_url,
                    'creator' => ['@id' => $site_url . '#photographer'],
                    'image' => array_values(array_slice(array_unique($item_images), 0, 12))
                ];
            }
        }
    }

    $json_ld = [
        '@context' => 'https://schema.org',
        '@graph' => $schema_graph
    ];
    ?>
    <script type="application/ld+json">
    <?= json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
</head>
<?php
$body_classes = [];
if (!empty($body_class)) {
    $body_classes[] = $body_class;
}
if (!empty($html_class)) {
    if (strpos($html_class, 'hp-page') !== false) {
        $body_classes[] = 'homepage-body hp-body mono-body';
    } elseif (strpos($html_class, 'mono-page') !== false) {
        $body_classes[] = 'mono-body';
    } elseif (strpos($html_class, 'project-split-page') !== false) {
        $body_classes[] = 'project-split-body';
    } elseif (strpos($html_class, 'series-page') !== false) {
        $body_classes[] = 'series-body';
    } elseif (strpos($html_class, 'aboutme-page') !== false) {
        $body_classes[] = 'aboutme-body';
    } elseif (strpos($html_class, 'field-map-page') !== false) {
        $body_classes[] = 'field-map-body dark';
    } elseif (strpos($html_class, 'not-found-page') !== false) {
        $body_classes[] = 'not-found-body editorial-body';
    } elseif (strpos($html_class, 'editorial-page') !== false) {
        $body_classes[] = 'editorial-body';
    }
}

$h_style_early = get_setting('header_style', 'hamburger');
if (!empty($GLOBALS['is_homepage'])) {
    $hp_header_nav = get_setting('hp_header_nav_style', 'inherit');
    if ($hp_header_nav && $hp_header_nav !== 'inherit') {
        $h_style_early = $hp_header_nav;
    }
}
if ($h_style_early === 'vertical_left') {
    $body_classes[] = 'om-has-vertical-left';
} elseif ($h_style_early === 'vertical_right') {
    $body_classes[] = 'om-has-vertical-right';
}

$body_class_str = trim(implode(' ', array_unique(explode(' ', implode(' ', $body_classes)))));
?>
<body<?= $body_class_str !== '' ? ' class="' . e($body_class_str) . '"' : '' ?>>
<?php
// ── Yönetici stil paketi önizleme çubuğu (yalnızca önizleme açıkken, yalnızca yöneticiye) ──
if (!empty($_COOKIE['er_style_preview'])):
    require_once __DIR__ . '/../style_presets.php';
    $sp_key = style_preview_key();
    if ($sp_key):
        $sp_titles = style_preset_titles()[$sp_key] ?? ['tr' => $sp_key, 'en' => $sp_key];
        $sp_title  = ($lang ?? 'tr') === 'tr' ? $sp_titles['tr'] : $sp_titles['en'];
?>
<div id="er-style-preview-bar" style="position:fixed; left:50%; bottom:18px; transform:translateX(-50%); z-index:2147483000; display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:center; max-width:calc(100vw - 24px); padding:10px 12px 10px 16px; border-radius:12px; background:#0f172a; color:#fff; box-shadow:0 10px 30px rgba(0,0,0,.35); font:600 13px/1.35 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <span><?= e(site_t('Önizleme', 'Preview', $lang ?? 'tr')) ?>: <?= e($sp_title) ?> · <span style="opacity:.7; font-weight:500;"><?= e(site_t('yalnızca siz görüyorsunuz', 'only you can see this', $lang ?? 'tr')) ?></span></span>
    <form method="POST" action="<?= e(url('admin/templates.php')) ?>" style="margin:0;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="apply_master_preset">
        <input type="hidden" name="preset_key" value="<?= e($sp_key) ?>">
        <button type="submit" style="background:#fff; color:#0f172a; border:0; border-radius:8px; padding:7px 12px; font:inherit; cursor:pointer;"><?= e(site_t('Siteye Uygula', 'Apply to Site', $lang ?? 'tr')) ?></button>
    </form>
    <a href="<?= e(url('admin/templates.php?preview_end=1')) ?>" style="color:#fff; opacity:.85; text-decoration:underline; padding:7px 4px;"><?= e(site_t('Önizlemeyi Kapat', 'End Preview', $lang ?? 'tr')) ?></a>
</div>
<script>
// Paneldeki tema düzenleyicisinin önizleme çerçevesinde bu şerit gösterilmez (yayınlama orada yapılır)
try { if (window.self !== window.top) { var erPb = document.getElementById('er-style-preview-bar'); if (erPb) erPb.remove(); } } catch (e) {}
</script>
<?php endif; endif; ?>
<?php
// ── Duyuru bandı (Site Ayarları › Genel) ──
$ann_text = trim((string)get_setting('announce_text_' . ($lang ?? 'tr'), ''));
$ann_until = (string)get_setting('announce_until', '');
if (get_setting('announce_enabled', '0') === '1' && $ann_text !== '' && ($ann_until === '' || $ann_until >= date('Y-m-d'))):
    $ann_link = (string)get_setting('announce_link', '');
    $ann_id = substr(md5($ann_text . $ann_link . $ann_until), 0, 10);
?>
<div class="xt-announce" id="xt-announce" data-id="<?= e($ann_id) ?>" hidden>
    <?php if ($ann_link !== ''): ?><a href="<?= e($ann_link) ?>" class="xt-announce-text"><?= e($ann_text) ?> <span aria-hidden="true">→</span></a>
    <?php else: ?><span class="xt-announce-text"><?= e($ann_text) ?></span><?php endif; ?>
    <button type="button" class="xt-announce-close" aria-label="<?= e(site_t('Kapat', 'Close', $lang ?? 'tr')) ?>">×</button>
</div>
<script>
(function () {
    var b = document.getElementById('xt-announce'); if (!b) return;
    var key = 'xt_announce_closed_' + b.getAttribute('data-id');
    try { if (localStorage.getItem(key) === '1') { b.remove(); return; } } catch (e) {}
    b.hidden = false;
    b.querySelector('.xt-announce-close').addEventListener('click', function () {
        try { localStorage.setItem(key, '1'); } catch (e) {}
        b.remove();
    });
})();
</script>
<?php endif; ?>
<?php if (get_setting('maintenance_mode', '0') === '1' && is_logged_in()): ?>
<aside style="position:fixed; bottom:16px; left:16px; background:#0f172a; color:#f8fafc; border:1px solid rgba(234,179,8,0.5); font-size:11.5px; font-weight:600; padding:6px 14px; border-radius:999px; box-shadow:0 6px 20px rgba(0,0,0,0.4); z-index:99999999; backdrop-filter:blur(8px); display:flex; align-items:center; gap:8px; pointer-events:none;">
    <span style="width:7px; height:7px; border-radius:50%; background:#eab308; display:inline-block;"></span>
    <span><?= site_t('Bakım Modu Aktif (Yalnızca Yönetici Görür)', 'Maintenance Mode Active (Admin Preview)') ?></span>
</aside>
<?php endif; ?>
<?php
function render_site_navigation(string $lang, string $en_path, string $tr_path, string $instagram_url): void {
    $is_tr = ($lang === 'tr');
    $lang_info = get_language_info($lang);
    $fb = $lang_info['fallback_lang'] ?? (($is_tr ? 'en' : 'tr'));
    $site_title = get_setting('site_title_' . $lang, get_setting('site_title_' . $fb, setting_l('site_title', null, site_owner_name())));
    $menu = [];

    try {
        $pdo = DB::getConnection();
        $dbMenus = $pdo->query("SELECT * FROM `menus` WHERE `is_active` = 1 ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($dbMenus)) {
            foreach ($dbMenus as $m) {
                if (!empty($m['is_separator'])) {
                    $menu[] = [
                        'type' => 'separator',
                    ];
                    continue;
                }
                $name = get_menu_localized_title($m, $lang);
                $rawUrl = !empty($m['url_' . $lang])
                    ? $m['url_' . $lang]
                    : (!empty($m['url_' . $fb]) ? resolve_slide_url($m['url_' . $fb], $lang) : ($is_tr ? ($m['url_tr'] ?: $m['url_en']) : ($m['url_en'] ?: $m['url_tr'])));
                if (!in_array($lang, ['en', 'tr'], true) && empty($m['url_' . $lang])) {
                    $rawUrl = resolve_slide_url($m['url_en'] ?: $m['url_tr'], $lang);
                }
                $finalUrl = (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://')) ? $rawUrl : url($rawUrl);
                $menu[] = [
                    'type'      => 'link',
                    'name'      => $name,
                    'url'       => $finalUrl,
                    'separator' => !empty($m['separator_before'])
                ];
            }
        }
    } catch (Throwable $t) {
        $menu = [];
    }

    if (empty($menu)) {
        try {
            $pdo = DB::getConnection();
            $catMenus = $pdo->query("SELECT * FROM `categories` WHERE `show_in_menu` = 1 ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($catMenus as $cm) {
                $cName = get_category_localized_name($cm, $lang);
                $cSlug = !empty($cm['slug_' . $lang]) ? $cm['slug_' . $lang] : ($is_tr ? ($cm['slug_tr'] ?: $cm['slug_en']) : ($cm['slug_en'] ?: $cm['slug_tr']));
                $menu[] = [
                    'type' => 'link',
                    'name' => $cName,
                    'url'  => url($lang . '/' . $cSlug . '/')
                ];
            }
        } catch (Throwable $t) {}
    }
    $home_url = url($lang . '/');

    $show_instagram = (int)get_setting('header_show_instagram', 1);
    $show_twitter   = (int)get_setting('header_show_twitter', 0);
    $show_facebook  = (int)get_setting('header_show_facebook', 0);
    $show_youtube   = (int)get_setting('header_show_youtube', 0);
    $show_linkedin  = (int)get_setting('header_show_linkedin', 0);
    $show_email     = (int)get_setting('header_show_email', 0);

    $instagram_url = get_setting('instagram_url', $instagram_url);
    $twitter_url   = get_setting('x_url', '');
    $facebook_url  = get_setting('facebook_url', '');
    $youtube_url   = get_setting('youtube_url', '');
    $linkedin_url  = get_setting('linkedin_url', '');
    $contact_email = get_setting('contact_email', '');

    // Gelişmiş Header Özelleştirici Ayarları
    $h_style            = get_setting('header_style', 'hamburger'); // hamburger | hamburger_left | horizontal | horizontal_left | horizontal_left_logo_right | horizontal_center | stacked_center | hybrid | minimal_pill
    if (!empty($GLOBALS['is_homepage'])) {
        $hp_header_nav = get_setting('hp_header_nav_style', 'inherit');
        if ($hp_header_nav && $hp_header_nav !== 'inherit') {
            $h_style = $hp_header_nav;
        }
    }
    $h_pos_cover        = get_setting('header_pos_cover', 'fixed'); // fixed | absolute
    $h_pos_inner        = get_setting('header_pos_inner', 'absolute'); // absolute | fixed | smart_sticky | static
    $h_scroll_effect    = get_setting('header_scroll_effect', 'none'); // none | glass_on_scroll | solid_on_scroll
    $h_scroll_shrink    = (int)get_setting('header_scroll_shrink', 0);
    $h_container_width  = get_setting('header_container_width', 'full'); // full | contained | narrow
    $h_pad_y            = max(10, min(64, (int)get_setting('header_padding_y', 34)));
    $h_pad_x            = max(14, min(96, (int)get_setting('header_padding_x', 44)));

    $h_bg_style         = get_setting('header_bg_style', 'transparent'); // transparent | gradient | glass | solid_dark | solid_light | custom
    $h_bg_color         = get_setting('header_bg_color', '#0c0e12');
    $h_bg_opacity       = max(0, min(100, (int)get_setting('header_bg_opacity', 85))) / 100;
    $h_blur_px          = max(0, min(40, (int)get_setting('header_blur_px', 14)));
    $h_border_bottom    = (int)get_setting('header_border_bottom', 0);

    // Logo, Amblem & Marka Kimliği Ayarları
    $h_logo_type        = get_setting('header_logo_type', 'symbol'); // symbol | full | symbol_text | full_text | text_only | none
    $h_logo_size        = max(20, min(120, (int)get_setting('header_logo_size', 52)));
    $h_logo_text        = get_setting('header_logo_text', mb_strtoupper(site_owner_name()));
    $h_logo_subtext     = setting_l('header_logo_subtext', null, site_t('BELGESEL FOTOĞRAFÇI', 'DOCUMENTARY PHOTOGRAPHER'));
    $h_logo_show_sub    = (int)get_setting('header_logo_show_subtext', 0);
    $h_logo_sub_size    = get_setting('header_logo_subtext_size', 'small') === 'normal' ? '11.5px' : '9.5px'; // Şablonlar › Alt başlık boyutu
    // Temalar › Özelleştir › Site adı ve logo (includes/design_editor.php)
    require_once __DIR__ . '/../design_editor.php';
    // Logo yazı tipi seçilmemişse başlık yazı tipini izler (Temalar › Yazı tipleri)
    $h_logo_font        = get_setting('header_logo_font', '') !== '' ? design_font_stack((string)get_setting('header_logo_font')) : "var(--header-font, 'ZedDisplayFtl', sans-serif)";
    $h_logo_text_px     = (int)(get_setting('header_logo_text_size', '') !== '' ? get_setting('header_logo_text_size') : max(13, min(28, round($h_logo_size * 0.36))));
    $h_logo_text_px     = max(12, min(44, $h_logo_text_px));
    $h_logo_weight      = in_array(get_setting('header_logo_text_weight', '800'), ['400', '600', '800'], true) ? get_setting('header_logo_text_weight', '800') : '800';
    $h_logo_ls          = ['tight' => '-0.01em', 'normal' => '0.03em', 'wide' => '0.14em'][get_setting('header_logo_letter_spacing', 'normal')] ?? '0.03em';
    $h_logo_color       = preg_match('/^#[0-9a-f]{6}$/i', (string)get_setting('header_logo_text_color', '')) ? get_setting('header_logo_text_color') : '';
    $h_logo_sub_color   = preg_match('/^#[0-9a-f]{6}$/i', (string)get_setting('header_logo_subtext_color', '')) ? get_setting('header_logo_subtext_color') : '';
    $h_logo_mobile      = max(60, min(100, (int)get_setting('header_logo_mobile_scale', '100'))) / 100;
    $h_adaptive_lum     = (int)get_setting('header_adaptive_luminance', 1);

    $symbol_enabled     = (int)get_setting('brand_symbol_enabled', 1);
    $logo_enabled       = (int)get_setting('brand_logo_enabled', 1);
    $custom_sym_dark    = trim((string)get_setting('brand_symbol_dark', ''));
    $custom_sym_light   = trim((string)get_setting('brand_symbol_light', ''));
    $custom_logo_dark   = trim((string)get_setting('brand_logo_dark', ''));
    $custom_logo_light  = trim((string)get_setting('brand_logo_light', ''));

    $sym_dark_url       = $custom_sym_dark !== '' ? url($custom_sym_dark) : url('assets/brand/symbol-dark.svg');
    $sym_light_url      = $custom_sym_light !== '' ? url($custom_sym_light) : ($custom_sym_dark !== '' ? url($custom_sym_dark) : url('assets/brand/symbol-light.svg'));
    $logo_dark_url      = $custom_logo_dark !== '' ? url($custom_logo_dark) : url('assets/brand/symbol-dark.svg');
    $logo_light_url     = $custom_logo_light !== '' ? url($custom_logo_light) : ($custom_logo_dark !== '' ? url($custom_logo_dark) : url('assets/brand/symbol-light.svg'));

    $h_nav_fs           = max(10, min(28, (int)get_setting('header_nav_font_size', 14)));
    $h_nav_fw           = (int)get_setting('header_nav_font_weight', 600);
    if (!in_array($h_nav_fw, [300, 400, 500, 600, 700], true)) {
        $h_nav_fw = 600;
    }

    $h_nav_upper        = (int)get_setting('header_nav_uppercase', 0);
    $h_nav_trans_opt    = get_setting('header_nav_transform', '');
    if ($h_nav_trans_opt === '') {
        $h_nav_trans = ($h_nav_upper === 1) ? 'uppercase' : 'none';
    } else {
        $h_nav_trans = in_array($h_nav_trans_opt, ['none', 'uppercase', 'lowercase', 'capitalize'], true) ? $h_nav_trans_opt : 'none';
    }

    $h_nav_ls_opt       = get_setting('header_nav_letter_spacing', 'normal');
    $ls_map = [
        'tight'      => '-0.025em',
        'normal'     => ($h_nav_trans === 'uppercase' ? '0.06em' : '0em'),
        'wide'       => '0.08em',
        'wider'      => '0.14em',
        'ultra_wide' => '0.22em',
    ];
    $h_nav_ls = $ls_map[$h_nav_ls_opt] ?? ($h_nav_trans === 'uppercase' ? '0.06em' : '0em');

    // Menü yazı tipi (Temalar › Menü › Yazı tipi); sitenin yüklediği yazı tipleri (includes/design_editor.php)
    require_once __DIR__ . '/../design_editor.php';
    $h_nav_ff = design_font_stack((string)get_setting('header_nav_font_family', 'sans'), 'sans');

    $h_drawer_fs_opt    = get_setting('header_drawer_font_size', 'normal');
    $drawer_fs_map = [
        'compact'     => ['bar' => '20px', 'full' => '28px'],
        'normal'      => ['bar' => '25px', 'full' => '36px'],
        'large'       => ['bar' => '32px', 'full' => '44px'],
        'monumental'  => ['bar' => '42px', 'full' => '54px'],
    ];
    $drawer_fs_arr    = $drawer_fs_map[$h_drawer_fs_opt] ?? $drawer_fs_map['normal'];
    $h_drawer_fs      = $drawer_fs_arr['bar'];
    $h_drawer_fs_full = $drawer_fs_arr['full'];

    $h_nav_gap          = max(8, min(64, (int)get_setting('header_nav_gap', 24)));
    $h_nav_hover        = get_setting('header_nav_hover_style', 'underline'); // underline | accent | pill | opacity

    $h_bar_show_lang    = (int)get_setting('header_bar_show_lang', 1);
    $h_bar_show_surface = (int)get_setting('header_bar_show_surface', 1);
    $h_bar_show_socials = (int)get_setting('header_bar_show_socials', 1);
    $h_cta_enabled      = (int)get_setting('header_cta_enabled', 0);
    $h_cta_text         = setting_l('header_cta_text', null, site_t('İletişim', 'Contact'));
    $h_cta_url          = get_setting('header_cta_url', get_setting('contact_email') !== '' ? 'mailto:' . get_setting('contact_email') : '');
    if ($h_cta_url !== '' && !str_starts_with($h_cta_url, 'http') && !str_starts_with($h_cta_url, 'mailto:') && !str_starts_with($h_cta_url, 'tel:') && !str_starts_with($h_cta_url, '#')) {
        $h_cta_url = url($h_cta_url);
    }

    $h_hb_style         = get_setting('header_hamburger_style', 'yellow_circle'); // yellow_circle | dark_circle | glass_circle | minimal_lines | pill_label
    $h_accent_color     = get_setting('header_accent_color', '#fdd000');
    $h_drawer_style     = get_setting('header_drawer_style', 'card_top_right'); // card_top_right | drawer_right | fullscreen
    $h_drawer_bg        = get_setting('header_drawer_bg', '#fdd000');
    $h_drawer_text      = get_setting('header_drawer_text', '#0f0f0e');
    $h_drawer_surface   = (int)get_setting('header_drawer_show_surface', 1);
    $h_drawer_lang      = (int)get_setting('header_drawer_show_lang', 1);
    $h_drawer_socials   = (int)get_setting('header_drawer_show_socials', 1);

    // Hex -> RGBA ve Parlaklık (Luminance) Hesaplamaları
    $hex_clean = ltrim($h_bg_color ?: '#0c0e12', '#');
    if (strlen($hex_clean) === 3) {
        $hex_clean = $hex_clean[0].$hex_clean[0].$hex_clean[1].$hex_clean[1].$hex_clean[2].$hex_clean[2];
    }
    $r_val = hexdec(substr($hex_clean, 0, 2) ?: '0c');
    $g_val = hexdec(substr($hex_clean, 2, 2) ?: '0e');
    $b_val = hexdec(substr($hex_clean, 4, 2) ?: '12');
    $custom_rgba = sprintf('rgba(%d, %d, %d, %.2f)', $r_val, $g_val, $b_val, $h_bg_opacity);
    $custom_bg_lum = (0.299 * $r_val + 0.587 * $g_val + 0.114 * $b_val);
    $is_custom_bg_light = ($custom_bg_lum > 145);

    // Vurgu rengi kontrast metin rengi
    $acc_hex = ltrim($h_accent_color ?: '#fdd000', '#');
    if (strlen($acc_hex) === 3) {
        $acc_hex = $acc_hex[0].$acc_hex[0].$acc_hex[1].$acc_hex[1].$acc_hex[2].$acc_hex[2];
    }
    $acc_lum = (0.299 * hexdec(substr($acc_hex, 0, 2) ?: 'fd') + 0.587 * hexdec(substr($acc_hex, 2, 2) ?: 'd0') + 0.114 * hexdec(substr($acc_hex, 4, 2) ?: '00'));
    $accent_fg_color = ($acc_lum > 145) ? '#111111' : '#ffffff';

    $is_horiz_mode = in_array($h_style, [
        'horizontal',
        'horizontal_left',
        'horizontal_left_logo_right',
        'horizontal_center',
        'stacked_center',
        'vertical_left',
        'vertical_right',
        'hybrid',
        'minimal_pill'
    ], true);
    $header_classes = [
        'om-header-bar',
        // Horizontal bars collapse into the hamburger on narrow screens
        ($is_horiz_mode && !in_array($h_style, ['vertical_left', 'vertical_right'], true)) ? 'om-collapses' : '',
        'om-style-' . $h_style,
        'om-bg-' . $h_bg_style,
        'om-hb-' . $h_hb_style,
        'om-hover-' . $h_nav_hover,
    ];
    if ($h_bg_style === 'custom') {
        $header_classes[] = $is_custom_bg_light ? 'om-bg-custom-light' : 'om-bg-custom-dark';
    }
    if ($h_scroll_shrink) $header_classes[] = 'om-shrink-enabled';
    $header_classes[] = 'om-scroll-' . ($h_scroll_effect ?: 'none');
    if ($h_border_bottom) $header_classes[] = 'om-has-border';
    // Alt çubuk: yalnız ana sayfada, yatay menülerle; iç sayfalarda menü üstte kalır
    if (!empty($GLOBALS['is_homepage']) && get_setting('header_home_position', 'top') === 'bottom'
        && in_array($h_style, ['horizontal', 'horizontal_left', 'horizontal_left_logo_right', 'hamburger', 'hamburger_left'], true)) {
        $header_classes[] = 'om-pos-bottom';
    }

    $show_sym_part  = ($symbol_enabled && in_array($h_logo_type, ['symbol', 'symbol_text'], true));
    $show_full_part = ($logo_enabled && in_array($h_logo_type, ['full', 'full_text'], true));
    $show_text_part = in_array($h_logo_type, ['symbol_text', 'full_text', 'text_only'], true);
    $has_any_brand  = ($h_logo_type !== 'none') && ($show_sym_part || $show_full_part || $show_text_part);
?>
    <header id="om-site-header" class="<?= e(implode(' ', $header_classes)) ?>" data-pos-cover="<?= e($h_pos_cover) ?>" data-pos-inner="<?= e($h_pos_inner) ?>" data-adaptive-lum="<?= $h_adaptive_lum ?>">
        <nav class="site-nav om-nav">
            <!-- 1. SOL: Logo & Marka Alanı -->
            <div class="left-nav om-left-nav">
                <?php if ($has_any_brand): ?>
                <a href="<?= e($home_url) ?>" class="site-logo-link" id="site-logo-link" aria-label="<?= e($site_title) ?>">
                    <?php if ($show_sym_part): ?>
                        <span class="logo-symbol-wrap">
                            <img src="<?= e($sym_light_url) ?>" alt="<?= e($site_title) ?>" class="logo-light logo-symbol" width="<?= $h_logo_size ?>" height="<?= $h_logo_size ?>" />
                            <img src="<?= e($sym_dark_url) ?>" alt="<?= e($site_title) ?>" class="logo-dark logo-symbol" width="<?= $h_logo_size ?>" height="<?= $h_logo_size ?>" />
                        </span>
                    <?php elseif ($show_full_part): ?>
                        <span class="logo-symbol-wrap logo-full-svg-wrap">
                            <img src="<?= e($logo_light_url) ?>" alt="<?= e($site_title) ?>" class="logo-light logo-full-img" />
                            <img src="<?= e($logo_dark_url) ?>" alt="<?= e($site_title) ?>" class="logo-dark logo-full-img" />
                        </span>
                    <?php endif; ?>

                    <?php if ($show_text_part): ?>
                        <span class="om-brand-text-wrap">
                            <span class="om-brand-title"><?= e($h_logo_text ?: mb_strtoupper(site_owner_name())) ?></span>
                            <?php if ($h_logo_show_sub && !empty($h_logo_subtext)): ?>
                                <span class="om-brand-subtext"><?= e($h_logo_subtext) ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- 2. ORTA / SAĞ: Yatay Menü Linkleri (Yatay / Hibrit / Kapsül Modlarında Aktif) -->
            <?php if ($is_horiz_mode): ?>
                <div class="om-horiz-nav" role="navigation" aria-label="<?= site_t('Üst Gezinti', 'Top Navigation') ?>">
                    <?php 
                    $last_was_sep = false;
                    foreach ($menu as $item): 
                        $is_sep = (($item['type'] ?? '') === 'separator');
                        $has_sep_before = !empty($item['separator']);
                        if (($is_sep || $has_sep_before) && !$last_was_sep):
                            $last_was_sep = true;
                    ?>
                            <span class="om-horiz-sep" aria-hidden="true"></span>
                    <?php 
                        endif;
                        if (!$is_sep && !empty($item['name'])):
                            $last_was_sep = false;
                    ?>
                            <a href="<?= e($item['url']) ?>" class="om-horiz-link"><?= e($item['name']) ?></a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            <?php endif; ?>

            <!-- 3. SAĞ: Yardımcı Araçlar + Hamburger Butonu -->
            <div class="action-nav om-right-nav">
                <?php if ($is_horiz_mode || $h_bar_show_surface): ?>
                    <div class="om-horiz-tools">
                        <?php if ($h_bar_show_surface): ?>
                            <button type="button" class="om-bar-surface-btn" onclick="window.toggleSurfaceTheme && window.toggleSurfaceTheme(event)" title="<?= site_t('Karanlık / Aydınlık Tema', 'Dark / Light Theme') ?>" aria-label="<?= site_t('Karanlık / Aydınlık Tema', 'Dark / Light Theme') ?>">
                                <svg class="om-icon-moon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                                <svg class="om-icon-sun" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                            </button>
                        <?php endif; ?>

                        <?php
                        $nav_active_langs = get_active_languages();
                        $hide_if_single = (get_setting('hide_switcher_if_single_lang', '1') === '1');
                        $sw_format = get_setting('lang_switcher_format', 'code');
                        $can_show_lang_sw = !(count($nav_active_langs) <= 1 && $hide_if_single);
                        if ($is_horiz_mode && $h_bar_show_lang && $can_show_lang_sw): ?>
                            <div class="om-bar-lang-switch">
                                <?php foreach ($nav_active_langs as $l_idx => $nl):
                                    $nl_code = $nl['code'];
                                    $nl_url = $GLOBALS['er_alternate_urls'][$nl_code] ?? ($nl_code === 'tr' ? url($tr_path) : ($nl_code === 'en' ? url($en_path) : url($nl_code . '/')));
                                    $nl_label = strtoupper($nl_code);
                                    if ($sw_format === 'flag_code') {
                                        $nl_label = trim(($nl['flag'] ?? '') . ' ' . strtoupper($nl_code));
                                    } elseif ($sw_format === 'native') {
                                        $nl_label = $nl['name_native'] ?: strtoupper($nl_code);
                                    }
                                ?>
                                    <?php if ($l_idx > 0): ?><span class="om-bar-lang-sep">/</span><?php endif; ?>
                                    <a href="<?= e($nl_url) ?>" class="om-bar-lang-link <?= ($lang === $nl_code) ? 'is-active' : '' ?>" onclick="sessionStorage.setItem('lang-chosen',<?= e(json_encode($nl_code)) ?>)"><?= e($nl_label) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_horiz_mode && $h_bar_show_socials): ?>
                            <div class="om-bar-socials">
                                <?php if ($show_instagram && !empty($instagram_url)): ?>
                                    <a href="<?= e($instagram_url) ?>" target="_blank" rel="noopener noreferrer" class="om-bar-social-link" aria-label="Instagram" title="Instagram">
                                        <svg viewBox="0 0 448 512" fill="currentColor" width="15" height="15" aria-hidden="true"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($show_twitter && !empty($twitter_url)): ?>
                                    <a href="<?= e($twitter_url) ?>" target="_blank" rel="noopener noreferrer" class="om-bar-social-link" aria-label="X" title="X (Twitter)">
                                        <svg viewBox="0 0 512 512" fill="currentColor" width="14" height="14" aria-hidden="true"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($show_youtube && !empty($youtube_url)): ?>
                                    <a href="<?= e($youtube_url) ?>" target="_blank" rel="noopener noreferrer" class="om-bar-social-link" aria-label="YouTube" title="YouTube">
                                        <svg viewBox="0 0 576 512" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($show_facebook && !empty($facebook_url)): ?>
                                    <a href="<?= e($facebook_url) ?>" target="_blank" rel="noopener noreferrer" class="om-bar-social-link" aria-label="Facebook" title="Facebook">
                                        <svg viewBox="0 0 512 512" fill="currentColor" width="14" height="14" aria-hidden="true"><path d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($show_linkedin && !empty($linkedin_url)): ?>
                                    <a href="<?= e($linkedin_url) ?>" target="_blank" rel="noopener noreferrer" class="om-bar-social-link" aria-label="LinkedIn" title="LinkedIn">
                                        <svg viewBox="0 0 448 512" fill="currentColor" width="14" height="14" aria-hidden="true"><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($show_email && !empty($contact_email)): ?>
                                    <a href="mailto:<?= e($contact_email) ?>" class="om-bar-social-link om-bar-email-link" aria-label="Email" title="E-Posta">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($h_cta_enabled && !empty($h_cta_text)): ?>
                            <a href="<?= e($h_cta_url) ?>" class="om-bar-cta-btn"><?= e($h_cta_text) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <button type="button" id="om-menu-toggle" class="om-hamburger-btn" onclick="window.toggleHidokmehMenu && window.toggleHidokmehMenu(event)" aria-label="<?= site_t('Menüyü Aç', 'Open Menu') ?>" aria-expanded="false" aria-controls="hidokmeh-menu-drawer">
                    <?php if ($h_hb_style === 'pill_label'): ?>
                        <span class="om-hamburger-label"><?= site_t('MENÜ', 'MENU') ?></span>
                    <?php endif; ?>
                    <span class="om-hamburger-box" aria-hidden="true">
                        <span class="om-line om-line-top"></span>
                        <span class="om-line om-line-bottom"></span>
                    </span>
                </button>
            </div>
        </nav>
    </header>

    <!-- HEADER & MENÜ DİNAMİK STİLLERİ -->
    <style id="hidokmeh-menu-overrides">
        :root {
            --om-accent: <?= e($h_accent_color) ?>;
            --om-accent-fg: <?= e($accent_fg_color) ?>;
            --om-drawer-bg: <?= e($h_drawer_bg) ?>;
            --om-drawer-text: <?= e($h_drawer_text) ?>;
            --om-pad-y: <?= $h_pad_y ?>px;
            --om-pad-x: <?= $h_pad_x ?>px;
            --om-logo-size: <?= $h_logo_size ?>px;
            --om-nav-fs: <?= $h_nav_fs ?>px;
            --om-nav-fw: <?= $h_nav_fw ?>;
            --om-nav-transform: <?= $h_nav_trans ?>;
            --om-nav-ls: <?= $h_nav_ls ?>;
            --om-nav-ff: <?= $h_nav_ff ?>;
            --om-nav-gap: <?= $h_nav_gap ?>px;
            --om-drawer-fs: <?= $h_drawer_fs ?>;
            --om-drawer-fs-full: <?= $h_drawer_fs_full ?>;
            --om-blur: <?= $h_blur_px ?>px;
            --om-custom-bg: <?= e($custom_rgba) ?>;
        }

        /* =========================================================================
           1. ANA HEADER BAR & TEMA KONTRAST DEĞİŞKENLERİ (--om-fg)
           ========================================================================= */
        #om-site-header.om-header-bar {
            --om-fg: #ffffff;
            --om-border-col: rgba(255, 255, 255, 0.24);
            --om-pill-hover: rgba(255, 255, 255, 0.14);
            position: <?= ($h_pos_inner === 'fixed' || $h_pos_inner === 'smart_sticky') ? 'fixed' : 'absolute' ?> !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            padding: var(--om-pad-y) var(--om-pad-x) !important;
            margin: 0 !important;
            background: transparent !important;
            border: none !important;
            border-bottom: <?= $h_border_bottom ? '1px solid rgba(255,255,255,0.12)' : 'none' ?> !important;
            box-shadow: none !important;
            pointer-events: <?= ($h_bg_style === 'transparent' && !$is_horiz_mode && $h_pos_inner !== 'static') ? 'none' : 'auto' ?> !important;
            z-index: 1000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            box-sizing: border-box !important;
            overflow-anchor: none !important;
            color: var(--om-fg) !important;
            transition: padding 0.28s cubic-bezier(0.16, 1, 0.3, 1),
                        background-color 0.28s ease,
                        backdrop-filter 0.28s ease,
                        transform 0.32s cubic-bezier(0.16, 1, 0.3, 1),
                        border-color 0.25s ease !important;
        }

        html.hp-page #om-site-header.om-header-bar,
        html.mono-page #om-site-header.om-header-bar {
            position: <?= $h_pos_cover === 'absolute' ? 'absolute' : 'fixed' ?> !important;
        }

        /* Akıllı Sticky (Aşağı kaydırırken gizle) */
        #om-site-header.om-header-bar.is-scroll-hidden {
            transform: translateY(-110%) !important;
        }

        /* Kaydırınca Daralma (Shrink on Scroll) */
        #om-site-header.om-header-bar.om-shrink-enabled.is-scrolled {
            padding-top: <?= max(12, round($h_pad_y * 0.55)) ?>px !important;
            padding-bottom: <?= max(12, round($h_pad_y * 0.55)) ?>px !important;
        }
        #om-site-header.om-header-bar.om-shrink-enabled.is-scrolled .site-logo-link img.logo-symbol {
            width: <?= max(32, round($h_logo_size * 0.82)) ?>px !important;
            height: <?= max(32, round($h_logo_size * 0.82)) ?>px !important;
            min-width: <?= max(32, round($h_logo_size * 0.82)) ?>px !important;
            min-height: <?= max(32, round($h_logo_size * 0.82)) ?>px !important;
        }

        /* Arka Plan Modları (Karanlık Zemin / Fotoğraf Üstü Varsayılan) */
        #om-site-header.om-header-bar.om-bg-gradient {
            background: linear-gradient(180deg, rgba(0,0,0,0.68) 0%, rgba(0,0,0,0.28) 65%, transparent 100%) !important;
        }
        /* Aydınlık / Kitap Kağıdı İç Sayfalarda Gradyan Modu */
        html.light:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-bg-gradient:not(.is-scrolled),
        html.gallery-paper:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-bg-gradient:not(.is-scrolled) {
            background: linear-gradient(180deg, rgba(242,240,235,0.96) 0%, rgba(242,240,235,0.62) 68%, transparent 100%) !important;
        }

        #om-site-header.om-header-bar.om-bg-glass,
        #om-site-header.om-header-bar.om-scroll-glass_on_scroll.is-scrolled {
            background: rgba(12, 14, 18, 0.74) !important;
            backdrop-filter: blur(var(--om-blur)) !important;
            -webkit-backdrop-filter: blur(var(--om-blur)) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            pointer-events: auto !important;
        }
        html.light:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-bg-glass,
        html.gallery-paper:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-bg-glass,
        html.light:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-scroll-glass_on_scroll.is-scrolled,
        html.gallery-paper:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-scroll-glass_on_scroll.is-scrolled {
            background: rgba(242, 240, 235, 0.88) !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.09) !important;
        }

        #om-site-header.om-header-bar.om-bg-solid_dark,
        #om-site-header.om-header-bar.om-scroll-solid_on_scroll.is-scrolled {
            background: #0c0e12 !important;
            pointer-events: auto !important;
        }
        html.light:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-scroll-solid_on_scroll.is-scrolled,
        html.gallery-paper:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-scroll-solid_on_scroll.is-scrolled,
        #om-site-header.om-header-bar.om-bg-solid_light {
            background: #f4f2ed !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
            pointer-events: auto !important;
        }
        #om-site-header.om-header-bar.om-bg-custom {
            background: var(--om-custom-bg) !important;
            <?php if ($h_blur_px > 0): ?>
            backdrop-filter: blur(var(--om-blur)) !important;
            -webkit-backdrop-filter: blur(var(--om-blur)) !important;
            <?php endif; ?>
            pointer-events: auto !important;
        }

        /* Yüzen Kapsül Modu (Minimal Pill) */
        #om-site-header.om-header-bar.om-style-minimal_pill {
            padding: 16px var(--om-pad-x) !important;
            background: transparent !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            border: none !important;
            pointer-events: none !important;
        }
        #om-site-header.om-header-bar.om-style-minimal_pill nav.site-nav {
            background: rgba(14, 16, 22, 0.82) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(255, 255, 255, 0.16) !important;
            border-radius: 999px !important;
            padding: 10px 24px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.32) !important;
            pointer-events: auto !important;
        }
        html.light:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-style-minimal_pill nav.site-nav,
        html.gallery-paper:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar.om-style-minimal_pill nav.site-nav {
            background: rgba(255, 255, 255, 0.92) !important;
            border-color: rgba(0, 0, 0, 0.12) !important;
            box-shadow: 0 8px 26px rgba(0, 0, 0, 0.08) !important;
        }

        /* =========================================================================
           KONTRAST DURUM MAKİNESİ: NE ZAMAN SİYAH (#111111) / NE ZAMAN BEYAZ (#ffffff)
           ========================================================================= */
        /* Varsayılan: Koyu Zemin -> Beyaz Metin & Beyaz Logo (logo-dark) */
        #om-site-header.om-header-bar .logo-symbol-wrap img.logo-dark {
            display: block !important;
            filter: none !important;
        }
        #om-site-header.om-header-bar .logo-symbol-wrap img.logo-light {
            display: none !important;
        }

        /* Durum A: Aydınlık İç Sayfalar (Düz Koyu Bar seçili değilse) */
        html.light:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark),
        html.gallery-paper:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark),
        /* Durum B: Düz Açık Renk Bar veya Açık Özel Renk Seçiliyse (Tüm Sayfalarda) */
        #om-site-header.om-header-bar.om-bg-solid_light,
        #om-site-header.om-header-bar.om-bg-custom-light,
        /* Durum C: Kapak/Ana Sayfalarda Aydınlık Zemin veya Aydınlık Fotoğraf Üstü */
        html.hp-page.hp-surface-paper:has(#hp-container:is(.hp-tpl-monograph_split, .hp-tpl-museum_passepartout, .hp-tpl-curatorial_grid, .hp-tpl-minimal_index, .hp-fit-contain, .hp-fit-framed)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark),
        html.hp-page.hp-surface-white:not(:has(#hp-container.hp-fit-cover .mono-chapter)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark),
        html[data-surface-theme="white"]:not(:has(#hp-container.hp-fit-cover .mono-chapter)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark),
        html.hp-page:not(.dark):not(.hp-surface-dark):not([data-surface-theme="dark"]):has(#hp-container.hp-tpl-white_cube_gallery) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark),
        #om-site-header.om-header-bar.om-bg-transparent.is-on-light-bg:not(.om-style-minimal_pill):not(.is-scrolled),
        #om-site-header.om-header-bar.om-bg-transparent.om-scroll-none.is-on-light-bg:not(.om-style-minimal_pill) {
            --om-fg: #111111;
            --om-border-col: rgba(0, 0, 0, 0.22);
            --om-pill-hover: rgba(0, 0, 0, 0.08);
            color: #111111 !important;
        }

        html.light:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-light,
        html.gallery-paper:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-light,
        #om-site-header.om-header-bar.om-bg-solid_light .logo-symbol-wrap img.logo-light,
        #om-site-header.om-header-bar.om-bg-custom-light .logo-symbol-wrap img.logo-light,
        html.hp-page.hp-surface-paper:has(#hp-container:is(.hp-tpl-monograph_split, .hp-tpl-museum_passepartout, .hp-tpl-curatorial_grid, .hp-tpl-minimal_index, .hp-fit-contain, .hp-fit-framed)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-light,
        html.hp-page.hp-surface-white:not(:has(#hp-container.hp-fit-cover .mono-chapter)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-light,
        html[data-surface-theme="white"]:not(:has(#hp-container.hp-fit-cover .mono-chapter)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-light,
        html.hp-page:not(.dark):not(.hp-surface-dark):not([data-surface-theme="dark"]):has(#hp-container.hp-tpl-white_cube_gallery) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-light,
        #om-site-header.om-header-bar.om-bg-transparent.is-on-light-bg:not(.om-style-minimal_pill):not(.is-scrolled) .logo-symbol-wrap img.logo-light,
        #om-site-header.om-header-bar.om-bg-transparent.om-scroll-none.is-on-light-bg:not(.om-style-minimal_pill) .logo-symbol-wrap img.logo-light {
            display: block !important;
            filter: none !important;
        }

        html.light:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-dark,
        html.gallery-paper:not(.hp-page):not(.mono-page):not(.field-map-page) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-dark,
        #om-site-header.om-header-bar.om-bg-solid_light .logo-symbol-wrap img.logo-dark,
        #om-site-header.om-header-bar.om-bg-custom-light .logo-symbol-wrap img.logo-dark,
        html.hp-page.hp-surface-paper:has(#hp-container:is(.hp-tpl-monograph_split, .hp-tpl-museum_passepartout, .hp-tpl-curatorial_grid, .hp-tpl-minimal_index, .hp-fit-contain, .hp-fit-framed)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-dark,
        html.hp-page.hp-surface-white:not(:has(#hp-container.hp-fit-cover .mono-chapter)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-dark,
        html[data-surface-theme="white"]:not(:has(#hp-container.hp-fit-cover .mono-chapter)) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-dark,
        html.hp-page:not(.dark):not(.hp-surface-dark):not([data-surface-theme="dark"]):has(#hp-container.hp-tpl-white_cube_gallery) #om-site-header.om-header-bar:not(.om-bg-solid_dark):not(.om-bg-custom-dark) .logo-symbol-wrap img.logo-dark,
        #om-site-header.om-header-bar.om-bg-transparent.is-on-light-bg:not(.om-style-minimal_pill):not(.is-scrolled) .logo-symbol-wrap img.logo-dark,
        #om-site-header.om-header-bar.om-bg-transparent.om-scroll-none.is-on-light-bg:not(.om-style-minimal_pill) .logo-symbol-wrap img.logo-dark {
            display: none !important;
        }

        /* Durum D: Karanlık Temaya Geçildiğinde Üst Bar ve Logo (Beyaz Küp Galeri Dahil) */
        html:is(.dark, .hp-surface-dark, [data-surface-theme="dark"]) #om-site-header.om-header-bar:not(.om-bg-solid_light):not(.om-bg-custom-light) {
            --om-fg: #ffffff !important;
            --om-border-col: rgba(255, 255, 255, 0.22) !important;
            --om-pill-hover: rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
        }
        html:is(.dark, .hp-surface-dark, [data-surface-theme="dark"]) #om-site-header.om-header-bar:not(.om-bg-solid_light):not(.om-bg-custom-light) .logo-symbol-wrap img.logo-light {
            display: none !important;
        }
        html:is(.dark, .hp-surface-dark, [data-surface-theme="dark"]) #om-site-header.om-header-bar:not(.om-bg-solid_light):not(.om-bg-custom-light) .logo-symbol-wrap img.logo-dark {
            display: block !important;
        }

        /* =========================================================================
           NAV KONTEYNER, LOGO VE YATAY MENÜ ÖĞELERİ
           ========================================================================= */
        #om-site-header.om-header-bar nav.site-nav {
            width: 100% !important;
            max-width: <?= $h_container_width === 'contained' ? '1440px' : ($h_container_width === 'narrow' ? '1200px' : '100%') ?> !important;
            margin: 0 auto !important;
            padding: 0 !important;
            border: none !important;
            border-bottom: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            pointer-events: none !important;
            box-sizing: border-box !important;
            gap: 20px !important;
        }

        #om-site-header .om-left-nav,
        #om-site-header .om-right-nav,
        #om-site-header .site-logo-link {
            pointer-events: auto !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 14px !important;
            flex-shrink: 0 !important;
        }

        <?php if ($h_style === 'horizontal_center'): ?>
        @media (min-width: 961px) {
            #om-site-header .om-left-nav {
                flex: 1 1 0 !important;
                justify-content: flex-start !important;
            }
            #om-site-header .om-right-nav {
                flex: 1 1 0 !important;
                justify-content: flex-end !important;
            }
        }
        <?php elseif ($h_style === 'horizontal_left'): ?>
        @media (min-width: 961px) {
            #om-site-header .om-horiz-nav {
                margin-left: clamp(24px, 3.5vw, 44px) !important;
                margin-right: auto !important;
            }
            #om-site-header .om-right-nav {
                margin-left: auto !important;
            }
        }
        <?php elseif ($h_style === 'horizontal_left_logo_right'): ?>
        @media (min-width: 961px) {
            #om-site-header .om-horiz-nav {
                order: 1 !important;
                margin-left: 0 !important;
                margin-right: auto !important;
            }
            #om-site-header .om-left-nav {
                order: 2 !important;
                margin-left: auto !important;
                margin-right: 0 !important;
            }
            #om-site-header .om-right-nav {
                order: 3 !important;
                margin-left: 18px !important;
            }
        }
        <?php elseif ($h_style === 'stacked_center'): ?>
        @media (min-width: 961px) {
            #om-site-header .om-nav {
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 12px !important;
                position: relative !important;
            }
            #om-site-header .om-left-nav {
                order: 1 !important;
                margin: 0 auto !important;
                justify-content: center !important;
            }
            #om-site-header .om-horiz-nav {
                order: 2 !important;
                margin: 0 auto !important;
                justify-content: center !important;
            }
            #om-site-header .om-right-nav {
                position: absolute !important;
                right: 0 !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }
        }
        <?php elseif ($h_style === 'hamburger_left'): ?>
        #om-site-header .om-nav {
            flex-direction: row-reverse !important;
            justify-content: space-between !important;
        }
        #om-site-header .om-left-nav {
            order: 2 !important;
        }
        #om-site-header .om-right-nav {
            order: 1 !important;
        }
        <?php elseif ($h_style === 'vertical_left'): ?>
        @media (min-width: 768px) {
            #om-site-header.om-style-vertical_left {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                bottom: 0 !important;
                right: auto !important;
                width: 240px !important;
                height: 100vh !important;
                height: 100dvh !important;
                padding: 40px 24px 30px 24px !important;
                margin: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                z-index: 10000 !important;
                box-sizing: border-box !important;
                border-right: 1px solid var(--om-border-col) !important;
                border-left: none !important;
                border-bottom: none !important;
                border-top: none !important;
                pointer-events: auto !important;
                background: <?= ($h_bg_style === 'transparent') ? 'rgba(10, 12, 16, 0.94)' : 'transparent' ?> !important;
                backdrop-filter: blur(14px) !important;
                -webkit-backdrop-filter: blur(14px) !important;
            }
            html.light #om-site-header.om-style-vertical_left,
            html.gallery-paper #om-site-header.om-style-vertical_left {
                background: rgba(244, 242, 237, 0.96) !important;
                border-color: rgba(0, 0, 0, 0.12) !important;
                color: #111111 !important;
                --om-fg: #111111;
            }
            #om-site-header.om-style-vertical_left nav.site-nav {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                height: 100% !important;
                min-height: 100% !important;
                width: 100% !important;
                max-width: 100% !important;
                gap: 20px !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                pointer-events: auto !important;
            }
            #om-site-header.om-style-vertical_left nav.site-nav .om-left-nav {
                order: 1 !important;
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
                margin: 0 !important;
                flex-shrink: 0 !important;
            }
            #om-site-header.om-style-vertical_left nav.site-nav .om-horiz-nav {
                order: 2 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 6px 0 !important;
                flex: 1 1 auto !important;
                overflow-y: auto !important;
            }
            #om-site-header.om-style-vertical_left nav.site-nav a.om-horiz-link {
                font-family: var(--om-nav-ff) !important;
                font-size: var(--om-nav-fs) !important;
                font-weight: var(--om-nav-fw) !important;
                letter-spacing: var(--om-nav-ls) !important;
                text-transform: var(--om-nav-transform) !important;
                padding: 4px 0 !important;
                display: block !important;
                width: 100% !important;
                text-align: left !important;
                opacity: 0.88 !important;
                white-space: normal !important;
            }
            #om-site-header.om-style-vertical_left nav.site-nav a.om-horiz-link:hover {
                opacity: 1 !important;
            }
            #om-site-header.om-style-vertical_left nav.site-nav .om-horiz-sep {
                width: 100% !important;
                height: 1px !important;
                background: var(--om-fg) !important;
                opacity: 0.15 !important;
                margin: 6px 0 !important;
                display: block !important;
            }
            #om-site-header.om-style-vertical_left nav.site-nav .om-right-nav {
                order: 3 !important;
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
                margin-top: auto !important;
                flex-shrink: 0 !important;
            }
            #om-site-header.om-style-vertical_left nav.site-nav .om-horiz-tools {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                gap: 10px !important;
                width: 100% !important;
            }
            #om-site-header.om-style-vertical_left #om-menu-toggle {
                display: none !important;
            }

            /* İç sayfalar için gövde kaydırma */
            body.om-has-vertical-left:not(.hp-body),
            body:has(#om-site-header.om-style-vertical_left):not(.hp-body) {
                padding-left: 240px !important;
                box-sizing: border-box !important;
            }

            /* Ana Sayfa: Gövdede fazladan padding olmasın, fotoğraf ve sahne doğrudan menünün bittiği yerden başlasın */
            body.hp-body.om-has-vertical-left,
            body.hp-body:has(#om-site-header.om-style-vertical_left) {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            body.om-has-vertical-left .hp-mono-container,
            body.om-has-vertical-left .mono-container,
            html:has(#om-site-header.om-style-vertical_left) .hp-mono-container,
            html:has(#om-site-header.om-style-vertical_left) .mono-container {
                left: 240px !important;
                width: calc(100vw - 240px) !important;
                max-width: calc(100vw - 240px) !important;
            }
            body.om-has-vertical-left .hp-chapter-slide,
            html:has(#om-site-header.om-style-vertical_left) .hp-chapter-slide {
                width: 100% !important;
                max-width: 100% !important;
            }
        }
        @media (max-width: 767px) {
            #om-site-header.om-style-vertical_left {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: auto !important;
                width: 100% !important;
                height: auto !important;
                padding: 16px 18px !important;
                flex-direction: row !important;
                border: none !important;
                background: transparent !important;
            }
            #om-site-header.om-style-vertical_left nav.site-nav {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                height: auto !important;
                width: 100% !important;
            }
            #om-site-header.om-style-vertical_left #om-menu-toggle {
                display: inline-flex !important;
            }
            #om-site-header.om-style-vertical_left .om-horiz-nav,
            #om-site-header.om-style-vertical_left .om-horiz-tools {
                display: none !important;
            }
            body.om-has-vertical-left {
                padding-left: 0 !important;
            }
        }
        <?php elseif ($h_style === 'vertical_right'): ?>
        @media (min-width: 768px) {
            #om-site-header.om-style-vertical_right {
                position: fixed !important;
                top: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                left: auto !important;
                width: 240px !important;
                height: 100vh !important;
                height: 100dvh !important;
                padding: 40px 24px 30px 24px !important;
                margin: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                z-index: 10000 !important;
                box-sizing: border-box !important;
                border-left: 1px solid var(--om-border-col) !important;
                border-right: none !important;
                border-bottom: none !important;
                border-top: none !important;
                pointer-events: auto !important;
                background: <?= ($h_bg_style === 'transparent') ? 'rgba(10, 12, 16, 0.94)' : 'transparent' ?> !important;
                backdrop-filter: blur(14px) !important;
                -webkit-backdrop-filter: blur(14px) !important;
            }
            html.light #om-site-header.om-style-vertical_right,
            html.gallery-paper #om-site-header.om-style-vertical_right {
                background: rgba(244, 242, 237, 0.96) !important;
                border-color: rgba(0, 0, 0, 0.12) !important;
                color: #111111 !important;
                --om-fg: #111111;
            }
            #om-site-header.om-style-vertical_right nav.site-nav {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                height: 100% !important;
                min-height: 100% !important;
                width: 100% !important;
                max-width: 100% !important;
                gap: 20px !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                pointer-events: auto !important;
            }
            #om-site-header.om-style-vertical_right nav.site-nav .om-left-nav {
                order: 1 !important;
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
                margin: 0 !important;
                flex-shrink: 0 !important;
            }
            #om-site-header.om-style-vertical_right nav.site-nav .om-horiz-nav {
                order: 2 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 6px 0 !important;
                flex: 1 1 auto !important;
                overflow-y: auto !important;
            }
            #om-site-header.om-style-vertical_right nav.site-nav a.om-horiz-link {
                font-family: var(--om-nav-ff) !important;
                font-size: var(--om-nav-fs) !important;
                font-weight: var(--om-nav-fw) !important;
                letter-spacing: var(--om-nav-ls) !important;
                text-transform: var(--om-nav-transform) !important;
                padding: 4px 0 !important;
                display: block !important;
                width: 100% !important;
                text-align: left !important;
                opacity: 0.88 !important;
                white-space: normal !important;
            }
            #om-site-header.om-style-vertical_right nav.site-nav a.om-horiz-link:hover {
                opacity: 1 !important;
            }
            #om-site-header.om-style-vertical_right nav.site-nav .om-horiz-sep {
                width: 100% !important;
                height: 1px !important;
                background: var(--om-fg) !important;
                opacity: 0.15 !important;
                margin: 6px 0 !important;
                display: block !important;
            }
            #om-site-header.om-style-vertical_right nav.site-nav .om-right-nav {
                order: 3 !important;
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
                margin-top: auto !important;
                flex-shrink: 0 !important;
            }
            #om-site-header.om-style-vertical_right nav.site-nav .om-horiz-tools {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                gap: 10px !important;
                width: 100% !important;
            }
            #om-site-header.om-style-vertical_right #om-menu-toggle {
                display: none !important;
            }

            /* İç sayfalar için gövde kaydırma */
            body.om-has-vertical-right:not(.hp-body),
            body:has(#om-site-header.om-style-vertical_right):not(.hp-body) {
                padding-right: 240px !important;
                box-sizing: border-box !important;
            }

            /* Ana Sayfa: Gövdede fazladan padding olmasın, konteyner sağda menü boşluğu bıraksın */
            body.hp-body.om-has-vertical-right,
            body.hp-body:has(#om-site-header.om-style-vertical_right) {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            body.om-has-vertical-right .hp-mono-container,
            body.om-has-vertical-right .mono-container,
            html:has(#om-site-header.om-style-vertical_right) .hp-mono-container,
            html:has(#om-site-header.om-style-vertical_right) .mono-container {
                left: 0 !important;
                right: 240px !important;
                width: calc(100vw - 240px) !important;
                max-width: calc(100vw - 240px) !important;
            }
            body.om-has-vertical-right .hp-chapter-slide,
            html:has(#om-site-header.om-style-vertical_right) .hp-chapter-slide {
                width: 100% !important;
                max-width: 100% !important;
            }
        }
        @media (max-width: 767px) {
            #om-site-header.om-style-vertical_right {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: auto !important;
                width: 100% !important;
                height: auto !important;
                padding: 16px 18px !important;
                flex-direction: row !important;
                border: none !important;
                background: transparent !important;
            }
            #om-site-header.om-style-vertical_right nav.site-nav {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                height: auto !important;
                width: 100% !important;
            }
            #om-site-header.om-style-vertical_right #om-menu-toggle {
                display: inline-flex !important;
            }
            #om-site-header.om-style-vertical_right .om-horiz-nav,
            #om-site-header.om-style-vertical_right .om-horiz-tools {
                display: none !important;
            }
            body.om-has-vertical-right {
                padding-right: 0 !important;
            }
        }
        <?php endif; ?>

        #om-site-header .logo-symbol-wrap {
            display: inline-flex !important;
            align-items: center !important;
        }

        #om-site-header .site-logo-link {
            gap: 12px !important;
            text-decoration: none !important;
            border: none !important;
            background: transparent !important;
            color: var(--om-fg) !important;
        }

        #om-site-header .site-logo-link img.logo-symbol {
            width: var(--om-logo-size) !important;
            height: var(--om-logo-size) !important;
            min-width: var(--om-logo-size) !important;
            min-height: var(--om-logo-size) !important;
            object-fit: contain !important;
            transition: width 0.25s ease, height 0.25s ease, transform 0.25s ease !important;
        }

        #om-site-header .site-logo-link .logo-full-svg-wrap {
            display: inline-flex !important;
            align-items: center !important;
            line-height: 1 !important;
            height: auto !important;
            width: auto !important;
        }

        #om-site-header .site-logo-link img.logo-full-img,
        .site-logo-link img.logo-full-img,
        .site-logo-link img:not(.logo-symbol) {
            height: calc(var(--om-logo-size, 52px) * 0.46) !important;
            width: auto !important;
            max-width: min(55vw, 560px) !important;
            max-height: calc(var(--om-logo-size, 52px) * 0.6) !important;
            object-fit: contain !important;
            display: block !important;
            border-radius: 0 !important;
            transition: height 0.25s ease, width 0.25s ease !important;
        }

        #om-site-header.om-style-vertical_left .site-logo-link img.logo-full-img,
        #om-site-header.om-style-vertical_right .site-logo-link img.logo-full-img {
            max-width: 170px !important;
            width: auto !important;
            height: calc(var(--om-logo-size, 52px) * 0.42) !important;
        }

        #om-site-header .om-brand-text-wrap {
            display: inline-flex !important;
            flex-direction: column !important;
            line-height: 1.15 !important;
        }
        #om-site-header .om-brand-title {
            font-family: <?= $h_logo_font ?> !important;
            font-size: <?= $h_logo_text_px ?>px !important;
            font-weight: <?= $h_logo_weight ?> !important;
            letter-spacing: <?= $h_logo_ls ?> !important;
            color: <?= $h_logo_color !== '' ? $h_logo_color : 'var(--om-fg)' ?> !important;
        }
        #om-site-header .om-brand-subtext {
            font-family: var(--font-mono, monospace) !important;
            font-size: <?= $h_logo_sub_size ?> !important;
            letter-spacing: 0.12em !important;
            opacity: <?= $h_logo_sub_color !== '' ? '1' : '0.72' ?> !important;
            color: <?= $h_logo_sub_color !== '' ? $h_logo_sub_color : ($h_logo_color !== '' ? $h_logo_color : 'var(--om-fg)') ?> !important;
            margin-top: 2px !important;
        }
        <?php if ($h_logo_mobile < 1): ?>
        @media (max-width: 767px) {
            #om-site-header .om-brand-title { font-size: <?= round($h_logo_text_px * $h_logo_mobile, 1) ?>px !important; }
            #om-site-header .site-logo-link img { transform: scale(<?= $h_logo_mobile ?>); transform-origin: left center; }
        }
        <?php endif; ?>

        /* Yatay Menü Linkleri */
        #om-site-header .om-horiz-nav {
            display: inline-flex !important;
            align-items: center !important;
            gap: var(--om-nav-gap) !important;
            pointer-events: auto !important;
            <?php if ($h_style === 'horizontal'): ?>
            margin-left: auto !important;
            margin-right: 12px !important;
            <?php elseif ($h_style === 'horizontal_left'): ?>
            margin-left: clamp(24px, 3.5vw, 44px) !important;
            margin-right: auto !important;
            <?php elseif ($h_style === 'horizontal_left_logo_right'): ?>
            margin-left: 0 !important;
            margin-right: auto !important;
            <?php elseif ($h_style === 'horizontal_center' || $h_style === 'minimal_pill' || $h_style === 'hybrid' || $h_style === 'stacked_center'): ?>
            margin: 0 auto !important;
            <?php endif; ?>
        }

        #om-site-header a.om-horiz-link,
        #om-site-header a.om-horiz-link:link,
        #om-site-header a.om-horiz-link:visited {
            font-family: var(--om-nav-ff) !important;
            font-size: var(--om-nav-fs) !important;
            font-weight: var(--om-nav-fw) !important;
            letter-spacing: var(--om-nav-ls) !important;
            text-transform: var(--om-nav-transform) !important;
            color: var(--om-fg) !important;
            text-decoration: none !important;
            position: relative !important;
            padding: 5px 2px !important;
            border: none !important;
            border-bottom: none !important;
            background: transparent !important;
            transition: color 0.2s ease, opacity 0.2s ease, background-color 0.2s ease !important;
            white-space: nowrap !important;
        }

        /* Yatay Menü Hover Efektleri */
        #om-site-header.om-hover-underline a.om-horiz-link:hover {
            color: var(--om-fg) !important;
            background: transparent !important;
        }
        #om-site-header.om-hover-underline a.om-horiz-link::after {
            content: '' !important;
            position: absolute !important;
            left: 0 !important;
            bottom: 0 !important;
            width: 0 !important;
            height: 2px !important;
            background-color: var(--om-accent) !important;
            transition: width 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        #om-site-header.om-hover-underline a.om-horiz-link:hover::after {
            width: 100% !important;
        }
        #om-site-header.om-hover-accent a.om-horiz-link:hover {
            color: var(--om-accent) !important;
            background: transparent !important;
        }
        #om-site-header.om-hover-pill a.om-horiz-link {
            padding: 5px 11px !important;
            border-radius: 999px !important;
        }
        #om-site-header.om-hover-pill a.om-horiz-link:hover {
            color: var(--om-fg) !important;
            background: var(--om-pill-hover) !important;
        }
        #om-site-header.om-hover-opacity a.om-horiz-link:hover {
            color: var(--om-fg) !important;
            opacity: 0.62 !important;
            background: transparent !important;
        }

        #om-site-header .om-horiz-sep {
            width: 1px !important;
            height: 14px !important;
            background: var(--om-fg) !important;
            opacity: 0.28 !important;
            display: inline-block !important;
        }

        /* Yatay Bar Yardımcı Araçlar (Zemin, Dil, Sosyal Medya, CTA) */
        #om-site-header .om-horiz-tools {
            display: inline-flex !important;
            align-items: center !important;
            gap: 14px !important;
            pointer-events: auto !important;
            color: var(--om-fg) !important;
        }
        #om-site-header .om-bar-surface-btn {
            width: 32px !important;
            height: 32px !important;
            border-radius: 50% !important;
            border: 1px solid var(--om-border-col) !important;
            background: transparent !important;
            color: var(--om-fg) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            padding: 0 !important;
            transition: all 0.2s ease !important;
        }
        #om-site-header .om-bar-surface-btn svg {
            width: 14px !important;
            height: 14px !important;
            display: block !important;
            stroke: currentColor !important;
        }
        #om-site-header .om-bar-surface-btn .om-icon-sun {
            display: none !important;
        }
        #om-site-header .om-bar-surface-btn .om-icon-moon {
            display: block !important;
        }
        html:is(.dark, .hp-surface-dark, [data-surface-theme="dark"]) #om-site-header .om-bar-surface-btn .om-icon-moon {
            display: none !important;
        }
        html:is(.dark, .hp-surface-dark, [data-surface-theme="dark"]) #om-site-header .om-bar-surface-btn .om-icon-sun {
            display: block !important;
        }
        #om-site-header .om-bar-surface-btn:hover {
            border-color: var(--om-accent) !important;
            color: var(--om-accent) !important;
        }
        #om-site-header .om-bar-lang-switch {
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
            font-family: var(--font-mono, monospace) !important;
            font-size: 11.5px !important;
            font-weight: 600 !important;
            color: var(--om-fg) !important;
        }
        #om-site-header a.om-bar-lang-link,
        #om-site-header a.om-bar-lang-link:link,
        #om-site-header a.om-bar-lang-link:visited {
            color: var(--om-fg) !important;
            opacity: 0.68 !important;
            text-decoration: none !important;
            padding: 2px 5px !important;
            border-radius: 3px !important;
            border: none !important;
            background: transparent !important;
            transition: opacity 0.2s ease, background-color 0.2s ease !important;
        }
        #om-site-header a.om-bar-lang-link:not(.is-active):hover {
            opacity: 1 !important;
            color: var(--om-fg) !important;
            background: var(--om-pill-hover) !important;
        }
        #om-site-header a.om-bar-lang-link.is-active,
        #om-site-header a.om-bar-lang-link.is-active:hover {
            opacity: 1 !important;
            font-weight: 800 !important;
            background: var(--om-accent) !important;
            color: var(--om-accent-fg) !important;
        }
        #om-site-header .om-bar-lang-sep {
            color: var(--om-fg) !important;
            opacity: 0.35 !important;
            font-size: 10px !important;
        }
        #om-site-header .om-bar-socials {
            display: inline-flex !important;
            align-items: center !important;
            gap: 10px !important;
        }
        #om-site-header a.om-bar-social-link,
        #om-site-header a.om-bar-social-link:link,
        #om-site-header a.om-bar-social-link:visited {
            color: var(--om-fg) !important;
            opacity: 0.88 !important;
            width: 26px !important;
            height: 26px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
            border: none !important;
            border-bottom: none !important;
            background: transparent !important;
            transition: opacity 0.2s ease, color 0.2s ease, transform 0.2s ease !important;
        }
        #om-site-header a.om-bar-social-link svg {
            width: 15px !important;
            height: 15px !important;
            min-width: 15px !important;
            min-height: 15px !important;
            display: block !important;
            color: currentColor !important;
            fill: currentColor !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        #om-site-header a.om-bar-social-link.om-bar-email-link svg {
            fill: none !important;
            stroke: currentColor !important;
        }
        #om-site-header a.om-bar-social-link:hover {
            opacity: 1 !important;
            color: var(--om-accent) !important;
            background: transparent !important;
            transform: translateY(-1px) !important;
        }
        #om-site-header a.om-bar-cta-btn,
        #om-site-header a.om-bar-cta-btn:link,
        #om-site-header a.om-bar-cta-btn:visited {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 7px 15px !important;
            border-radius: 999px !important;
            background: var(--om-accent) !important;
            color: var(--om-accent-fg) !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            border: none !important;
            transition: transform 0.2s ease !important;
        }
        #om-site-header a.om-bar-cta-btn:hover {
            transform: scale(1.04) !important;
            background: var(--om-accent) !important;
            color: var(--om-accent-fg) !important;
        }

        /* Masaüstünde Sadece Yatay/Dikey Menü Seçiliyse Hamburger Butonunu Gizle, Mobilde Göster */
        <?php if (in_array($h_style, ['horizontal', 'horizontal_left', 'horizontal_left_logo_right', 'horizontal_center', 'stacked_center', 'minimal_pill', 'vertical_left', 'vertical_right'], true)): ?>
        @media (min-width: 961px) {
            #om-site-header #om-menu-toggle.om-hamburger-btn {
                display: none !important;
            }
        }
        <?php endif; ?>

        /* =========================================================================
           2. HAMBURGER BUTONU STİLLERİ
           ========================================================================= */
        #om-site-header .om-hamburger-btn,
        #om-site-header #om-menu-toggle {
            position: relative !important;
            top: auto !important;
            right: auto !important;
            width: <?= $h_hb_style === 'pill_label' ? 'auto' : '48px' ?> !important;
            height: 48px !important;
            padding: <?= $h_hb_style === 'pill_label' ? '0 18px' : '0' ?> !important;
            border-radius: <?= $h_hb_style === 'pill_label' ? '999px' : '50%' ?> !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 9px !important;
            cursor: pointer !important;
            margin: 0 !important;
            z-index: 1005 !important;
            pointer-events: auto !important;
            <?php if ($h_hb_style === 'dark_circle'): ?>
            background: #111111 !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.28) !important;
            <?php elseif ($h_hb_style === 'glass_circle'): ?>
            background: var(--om-pill-hover) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border: 1px solid var(--om-border-col) !important;
            color: var(--om-fg) !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.14) !important;
            <?php elseif ($h_hb_style === 'minimal_lines'): ?>
            background: transparent !important;
            border: none !important;
            color: var(--om-fg) !important;
            box-shadow: none !important;
            <?php else: ?>
            background: var(--om-accent) !important;
            background-color: var(--om-accent) !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
            color: var(--om-accent-fg) !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.22) !important;
            <?php endif; ?>
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), 
                        background-color 0.2s ease, 
                        box-shadow 0.25s ease,
                        opacity 0.2s ease !important;
        }

        #om-site-header .om-hamburger-label {
            font-family: var(--font-mono, monospace) !important;
            font-size: 11.5px !important;
            font-weight: 700 !important;
            letter-spacing: 0.08em !important;
            color: currentColor !important;
        }

        #om-site-header .om-hamburger-btn .om-line {
            background-color: currentColor !important;
        }

        #om-site-header .om-hamburger-btn:hover,
        #om-site-header #om-menu-toggle:hover {
            transform: scale(1.06) !important;
            <?php if ($h_hb_style === 'dark_circle'): ?>
            background: #222222 !important;
            color: #ffffff !important;
            <?php elseif ($h_hb_style === 'glass_circle'): ?>
            background: var(--om-pill-hover) !important;
            color: var(--om-fg) !important;
            <?php elseif ($h_hb_style === 'minimal_lines'): ?>
            background: transparent !important;
            color: var(--om-fg) !important;
            box-shadow: none !important;
            <?php else: ?>
            background: var(--om-accent) !important;
            color: var(--om-accent-fg) !important;
            <?php endif; ?>
        }

        /* Menü Açıldığında Yuvarlak X Butonu Gizlenir */
        #om-site-header .om-hamburger-btn.is-active,
        #om-site-header #om-menu-toggle.is-active,
        body.hidokmeh-menu-open #om-site-header .om-hamburger-btn,
        body.hidokmeh-menu-open #om-site-header #om-menu-toggle {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        @media (max-width: 960px) {
            #om-site-header:not(.om-style-vertical_left):not(.om-style-vertical_right) .om-horiz-nav {
                display: none !important;
            }
            #om-site-header:not(.om-style-vertical_left):not(.om-style-vertical_right) .om-horiz-tools .om-bar-lang-switch,
            #om-site-header:not(.om-style-vertical_left):not(.om-style-vertical_right) .om-horiz-tools .om-bar-socials,
            #om-site-header:not(.om-style-vertical_left):not(.om-style-vertical_right) .om-horiz-tools .om-bar-cta-btn {
                display: none !important;
            }
            #om-site-header .om-bar-surface-btn {
                display: inline-flex !important;
            }
            /* Once the hamburger takes over, the theme switch lives in the menu only */
            #om-site-header.om-collapses .om-bar-surface-btn {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            #om-site-header.om-header-bar {
                padding: 16px 18px !important;
            }
            #om-site-header .site-logo-link img.logo-symbol {
                width: clamp(26px, calc(var(--om-logo-size) * 0.75), 44px) !important;
                height: clamp(26px, calc(var(--om-logo-size) * 0.75), 44px) !important;
                min-width: clamp(26px, calc(var(--om-logo-size) * 0.75), 44px) !important;
                min-height: clamp(26px, calc(var(--om-logo-size) * 0.75), 44px) !important;
            }
            #om-site-header .om-hamburger-btn,
            #om-site-header #om-menu-toggle {
                width: <?= $h_hb_style === 'pill_label' ? 'auto' : '40px' ?> !important;
                height: 40px !important;
            }
        }

        /* =========================================================================
           3. AÇILIR MENÜ KARTI / ÇEKMECESİ (DRAWER STYLES)
           ========================================================================= */
        #hidokmeh-menu-drawer {
            background-color: var(--om-drawer-bg) !important;
            color: var(--om-drawer-text) !important;
            box-shadow: 0 16px 40px rgba(0,0,0,0.35) !important;
            <?php if ($h_drawer_style === 'fullscreen'): ?>
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            max-width: 100vw !important;
            height: 100dvh !important;
            border-radius: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            padding: 24px 36px !important;
            <?php elseif ($h_drawer_style === 'drawer_right'): ?>
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: auto !important;
            width: 360px !important;
            max-width: 88vw !important;
            height: 100dvh !important;
            border-radius: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            padding: 20px 8px !important;
            transform: translateX(100%) !important;
            <?php else: ?>
            top: 28px !important;
            right: 36px !important;
            width: 300px !important;
            max-width: calc(100vw - 28px) !important;
            border-radius: 4px !important;
            overflow: hidden !important;
            <?php endif; ?>
        }
        <?php if ($h_drawer_style === 'drawer_right'): ?>
        #hidokmeh-menu-drawer.is-open {
            transform: translateX(0) !important;
        }
        <?php endif; ?>
        <?php if ($h_drawer_style === 'fullscreen'): ?>
        #hidokmeh-menu-drawer .hidokmeh-body {
            max-width: 680px !important;
            width: 100% !important;
            margin: auto !important;
            text-align: center !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-nav-list {
            align-items: center !important;
            gap: 10px !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-item-wrap {
            height: 48px !important;
        }
        #hidokmeh-menu-drawer a.hidokmeh-menu-item {
            font-size: var(--om-drawer-fs-full) !important;
            line-height: 1.25 !important;
        }
        <?php else: ?>
        #hidokmeh-menu-drawer .hidokmeh-body {
            padding: 0 28px 20px !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-nav-list {
            margin-top: 26px !important;
            gap: 3px !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-item-wrap {
            height: auto !important;
            min-height: 36px !important;
            overflow: hidden !important;
        }
        #hidokmeh-menu-drawer a.hidokmeh-menu-item,
        a.hidokmeh-menu-item {
            font-size: var(--om-drawer-fs) !important;
            line-height: 1.35 !important;
        }
        <?php endif; ?>

        #hidokmeh-menu-drawer .hidokmeh-bar-close-btn {
            background: var(--om-drawer-text) !important;
            color: var(--om-drawer-bg) !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-close-label {
            color: var(--om-drawer-bg) !important;
        }

        #hidokmeh-menu-drawer a.hidokmeh-menu-item,
        a.hidokmeh-menu-item,
        #hidokmeh-menu-drawer a.hidokmeh-menu-item:hover,
        #hidokmeh-menu-drawer a.hidokmeh-menu-item:focus,
        #hidokmeh-menu-drawer a.hidokmeh-menu-item:active {
            font-family: var(--om-nav-ff) !important;
            font-weight: 700 !important;
            text-transform: var(--om-nav-transform) !important;
            letter-spacing: var(--om-nav-ls) !important;
            color: var(--om-drawer-text) !important;
            background: transparent !important;
            text-decoration: none !important;
            display: inline-block !important;
            border: none !important;
        }
        #hidokmeh-menu-drawer a.hidokmeh-menu-item::after {
            content: '' !important;
            position: absolute !important;
            left: 0 !important;
            bottom: 2px !important;
            width: 0 !important;
            height: 2px !important;
            background-color: var(--om-drawer-text) !important;
            transition: width 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        #hidokmeh-menu-drawer.om-hover-underline a.hidokmeh-menu-item:hover::after {
            width: 100% !important;
        }
        /* Çekmece bağlantıları da "Bağlantının üzerine gelince" ayarını izler */
        #hidokmeh-menu-drawer.om-hover-accent a.hidokmeh-menu-item:hover {
            color: var(--om-accent) !important;
        }
        #hidokmeh-menu-drawer.om-hover-pill a.hidokmeh-menu-item {
            padding: 0 12px !important;
            margin-left: -12px !important;
            border-radius: 999px !important;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1) var(--item-delay, 0ms), background-color 0.2s ease !important;
        }
        #hidokmeh-menu-drawer.om-hover-pill a.hidokmeh-menu-item:hover {
            background: color-mix(in srgb, var(--om-drawer-text) 12%, transparent) !important;
        }
        #hidokmeh-menu-drawer.om-hover-opacity a.hidokmeh-menu-item {
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1) var(--item-delay, 0ms), opacity 0.2s ease !important;
        }
        #hidokmeh-menu-drawer.om-hover-opacity a.hidokmeh-menu-item:hover {
            opacity: 0.55 !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-nav-sep,
        #hidokmeh-menu-drawer .hidokmeh-divider {
            background: var(--om-drawer-text) !important;
            opacity: 0.2 !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-theme-row {
            border-bottom-color: color-mix(in srgb, var(--om-drawer-text) 18%, transparent) !important;
            color: var(--om-drawer-text) !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-lang-group,
        #hidokmeh-menu-drawer .hidokmeh-lang-label,
        #hidokmeh-menu-drawer .hidokmeh-theme-pill,
        #hidokmeh-menu-drawer a.hidokmeh-lang-link,
        #hidokmeh-menu-drawer a.hidokmeh-social-icon,
        #hidokmeh-menu-drawer a.hidokmeh-social-icon svg {
            color: var(--om-drawer-text) !important;
            fill: currentColor !important;
        }
        #hidokmeh-menu-drawer a.hidokmeh-social-icon[href^="mailto:"] svg {
            fill: none !important;
            stroke: currentColor !important;
        }
        #hidokmeh-menu-drawer a.hidokmeh-social-icon:hover svg {
            color: var(--om-drawer-text) !important;
            fill: currentColor !important;
            opacity: 0.65 !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-lang-pipe {
            color: var(--om-drawer-text) !important;
            opacity: 0.38 !important;
        }
        #hidokmeh-menu-drawer a.hidokmeh-lang-link.is-active,
        #hidokmeh-menu-drawer button.hidokmeh-theme-pill.is-active {
            background: var(--om-drawer-text) !important;
            color: var(--om-drawer-bg) !important;
            font-weight: 700 !important;
        }
        #hidokmeh-menu-drawer a.hidokmeh-lang-link:not(.is-active):hover,
        #hidokmeh-menu-drawer button.hidokmeh-theme-pill:not(.is-active):hover {
            background: color-mix(in srgb, var(--om-drawer-text) 12%, transparent) !important;
            color: var(--om-drawer-text) !important;
        }
        #hidokmeh-menu-drawer .hidokmeh-top-bar {
            justify-content: flex-end !important;
        }
        <?php if ($h_drawer_style === 'card_top_right'): ?>
        @media (max-width: 768px) {
            #hidokmeh-menu-drawer {
                top: 16px !important;
                right: 18px !important;
                left: auto !important;
                width: 300px !important;
                max-width: calc(100vw - 36px) !important;
            }
        }
        <?php if (in_array('om-pos-bottom', $header_classes, true)): ?>
        /* Menü çubuğu alttayken köşe kartı da altta, düğmenin üstünde açılsın */
        #hidokmeh-menu-drawer { top: auto !important; bottom: 16px !important; transform-origin: bottom right; }
        <?php endif; ?>
        <?php endif; ?>
    </style>

    <!-- AÇILIR MENÜ & OVERLAY -->
    <div class="hidokmeh-menu-overlay" id="hidokmeh-overlay" onclick="window.closeHidokmehMenu && window.closeHidokmehMenu(event)" aria-hidden="true"></div>

    <?php // Vurgu rengi çekmece zeminiyle aynıysa görünmez olur; o durumda alt çizgiye düşer
    $drawer_hover = ($h_nav_hover === 'accent' && strtolower($h_accent_color) === strtolower($h_drawer_bg)) ? 'underline' : $h_nav_hover; ?>
    <div class="hidokmeh-menu-drawer om-hover-<?= e($drawer_hover) ?>" id="hidokmeh-menu-drawer" role="dialog" aria-modal="true" aria-label="<?= site_t('Site Menüsü', 'Site Navigation') ?>" aria-hidden="true">
        <div class="hidokmeh-top-bar">
            <button type="button" class="hidokmeh-bar-close-btn" id="hidokmeh-close-btn" onclick="window.closeHidokmehMenu && window.closeHidokmehMenu(event)" aria-label="<?= site_t('Menüyü Kapat', 'Close Menu') ?>">
                <span class="hidokmeh-close-label"><?= site_t('Kapat', 'Close') ?></span>
            </button>
        </div>

        <div class="hidokmeh-body">
            <nav class="hidokmeh-nav-list" aria-label="<?= site_t('Ana Gezinti', 'Main Navigation') ?>">
                <?php 
                $anim_delay = 50;
                foreach ($menu as $item): 
                    $is_sep = (($item['type'] ?? '') === 'separator');
                    if ($is_sep): ?>
                        <div class="hidokmeh-nav-sep" role="separator"></div>
                    <?php elseif (!empty($item['name'])): 
                        $anim_delay += 30;
                    ?>
                        <div class="hidokmeh-item-wrap">
                            <a href="<?= e($item['url']) ?>" class="hidokmeh-menu-item" style="--item-delay: <?= $anim_delay ?>ms;">
                                <?= e($item['name']) ?>
                            </a>
                        </div>
                    <?php endif; 
                endforeach; ?>
            </nav>

            <?php if ($h_drawer_surface || $h_drawer_lang || $h_drawer_socials): ?>
                <div class="hidokmeh-divider" aria-hidden="true"></div>
            <?php endif; ?>

            <?php if ($h_drawer_surface): ?>
            <div class="hidokmeh-theme-row">
                <span class="hidokmeh-lang-label"><?= site_t('Tema', 'Theme') ?></span>
                <div class="hidokmeh-theme-pills" role="group" aria-label="<?= site_t('Tema Modu', 'Theme Mode') ?>">
                    <button type="button" class="hidokmeh-theme-pill" data-surface-btn="dark" onclick="window.setSurfaceTheme && window.setSurfaceTheme('dark')" title="<?= site_t('Karanlık Mod', 'Dark Mode') ?>" aria-label="<?= site_t('Karanlık Mod', 'Dark Mode') ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    </button>
                    <span class="hidokmeh-lang-pipe">/</span>
                    <button type="button" class="hidokmeh-theme-pill" data-surface-btn="white" onclick="window.setSurfaceTheme && window.setSurfaceTheme('white')" title="<?= site_t('Saf Beyaz Mod', 'Pure White Mode') ?>" aria-label="<?= site_t('Saf Beyaz Mod', 'Pure White Mode') ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <?php
            $drawer_active_langs = $nav_active_langs ?? get_active_languages();
            $drawer_hide_single = (get_setting('hide_switcher_if_single_lang', '1') === '1');
            $drawer_sw_fmt = get_setting('lang_switcher_format', 'code');
            $drawer_can_show_lang = $h_drawer_lang && !(count($drawer_active_langs) <= 1 && $drawer_hide_single);
            if ($drawer_can_show_lang || $h_drawer_socials): ?>
            <div class="hidokmeh-bottom-row">
                <?php if ($drawer_can_show_lang): ?>
                <div class="hidokmeh-lang-group" style="flex-wrap:wrap; row-gap:4px;">
                    <span class="hidokmeh-lang-label"><?= site_t('Dil', 'Lang') ?></span>
                    <?php foreach ($drawer_active_langs as $d_idx => $dl):
                        $dl_code = $dl['code'];
                        $dl_url = $GLOBALS['er_alternate_urls'][$dl_code] ?? ($dl_code === 'tr' ? url($tr_path) : ($dl_code === 'en' ? url($en_path) : url($dl_code . '/')));
                        $dl_label = strtoupper($dl_code);
                        if ($drawer_sw_fmt === 'flag_code') {
                            $dl_label = trim(($dl['flag'] ?? '') . ' ' . strtoupper($dl_code));
                        } elseif ($drawer_sw_fmt === 'native') {
                            $dl_label = $dl['name_native'] ?: strtoupper($dl_code);
                        }
                    ?>
                        <?php if ($d_idx > 0): ?><span class="hidokmeh-lang-pipe">/</span><?php endif; ?>
                        <a href="<?= e($dl_url) ?>" class="hidokmeh-lang-link <?= ($lang === $dl_code) ? 'is-active' : '' ?>" onclick="sessionStorage.setItem('lang-chosen',<?= e(json_encode($dl_code)) ?>)"><?= e($dl_label) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div></div>
                <?php endif; ?>

                <?php if ($h_drawer_socials): ?>
                <div class="hidokmeh-social-group">
                    <?php if ($show_instagram && !empty($instagram_url)): ?>
                        <a href="<?= e($instagram_url) ?>" target="_blank" rel="noopener noreferrer" class="hidokmeh-social-icon" aria-label="Instagram">
                            <svg viewBox="0 0 448 512" fill="currentColor" width="15" height="15"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($show_twitter && !empty($twitter_url)): ?>
                        <a href="<?= e($twitter_url) ?>" target="_blank" rel="noopener noreferrer" class="hidokmeh-social-icon" aria-label="X (Twitter)">
                            <svg viewBox="0 0 512 512" fill="currentColor" width="14" height="14"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($show_facebook && !empty($facebook_url)): ?>
                        <a href="<?= e($facebook_url) ?>" target="_blank" rel="noopener noreferrer" class="hidokmeh-social-icon" aria-label="Facebook">
                            <svg viewBox="0 0 512 512" fill="currentColor" width="14" height="14"><path d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($show_youtube && !empty($youtube_url)): ?>
                        <a href="<?= e($youtube_url) ?>" target="_blank" rel="noopener noreferrer" class="hidokmeh-social-icon" aria-label="YouTube">
                            <svg viewBox="0 0 576 512" fill="currentColor" width="15" height="15"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($show_linkedin && !empty($linkedin_url)): ?>
                        <a href="<?= e($linkedin_url) ?>" target="_blank" rel="noopener noreferrer" class="hidokmeh-social-icon" aria-label="LinkedIn">
                            <svg viewBox="0 0 448 512" fill="currentColor" width="14" height="14"><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($show_email && !empty($contact_email)): ?>
                        <a href="mailto:<?= e($contact_email) ?>" class="hidokmeh-social-icon" aria-label="Email">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- GLOBAL MENU & ADAPTIVE LUMINANCE SCRIPT -->
    <script>
    (function() {
        var toggleBtn = document.getElementById('om-menu-toggle');
        var closeBtn = document.getElementById('hidokmeh-close-btn');
        var drawer = document.getElementById('hidokmeh-menu-drawer');
        var overlay = document.getElementById('hidokmeh-overlay');
        var headerBar = document.querySelector('.om-header-bar');

        window.openHidokmehMenu = function() {
            var d = document.getElementById('hidokmeh-menu-drawer');
            var o = document.getElementById('hidokmeh-overlay');
            var b = document.getElementById('om-menu-toggle');
            if (d) {
                d.classList.add('is-open');
                d.setAttribute('aria-hidden', 'false');
            }
            if (o) o.classList.add('is-open');
            if (b) {
                b.classList.add('is-active');
                b.setAttribute('aria-expanded', 'true');
            }
            document.body.classList.add('hidokmeh-menu-open');
            var c = document.getElementById('hidokmeh-close-btn');
            if (c) c.focus({ preventScroll: true });
        };

        window.closeHidokmehMenu = function(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            var d = document.getElementById('hidokmeh-menu-drawer');
            var o = document.getElementById('hidokmeh-overlay');
            var b = document.getElementById('om-menu-toggle');
            var hadFocus = !!d && d.contains(document.activeElement);
            if (d) {
                d.classList.remove('is-open');
                d.setAttribute('aria-hidden', 'true');
            }
            if (o) o.classList.remove('is-open');
            if (b) {
                b.classList.remove('is-active');
                b.setAttribute('aria-expanded', 'false');
            }
            document.body.classList.remove('hidokmeh-menu-open');
            if (hadFocus) {
                if (b && b.offsetParent !== null) b.focus({ preventScroll: true });
                else document.activeElement.blur();
            }
        };

        window.toggleHidokmehMenu = function(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            if (e && e.preventDefault) e.preventDefault();
            var d = document.getElementById('hidokmeh-menu-drawer');
            if (d && d.classList.contains('is-open')) {
                window.closeHidokmehMenu();
            } else {
                window.openHidokmehMenu();
            }
        };

        if (drawer) {
            drawer.querySelectorAll('.hidokmeh-menu-item, .hidokmeh-lang-link').forEach(function(link) {
                link.addEventListener('click', function() {
                    window.closeHidokmehMenu();
                });
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.closeHidokmehMenu();
            }
            // Açık çekmecede Tab arka plana kaçmasın (aria-modal)
            var dr = document.getElementById('hidokmeh-menu-drawer');
            if (e.key === 'Tab' && dr && dr.classList.contains('is-open')) {
                var f = Array.prototype.filter.call(dr.querySelectorAll('a[href], button:not([disabled]), input, select, [tabindex]:not([tabindex="-1"])'), function(el) { return el.offsetParent !== null; });
                if (!f.length) return;
                var first = f[0], last = f[f.length - 1];
                if (!dr.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
                else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });

        // ── KÜRATÖRYEL ZEMİN MODU: KARANLIK ODA (dark) vs. SAF BEYAZ (white) ──
        window.syncSurfaceThemeUI = function() {
            var root = document.documentElement;
            var currentAttr = root.getAttribute('data-surface-theme');
            var isWhite = (currentAttr === 'white') || root.classList.contains('gallery-white') || root.classList.contains('hp-surface-white') || root.classList.contains('about-surface-white') || root.classList.contains('light');
            var mode = isWhite ? 'white' : 'dark';

            // Menü içi ve sayfa başlığı altındaki zemin butonlarını güncelle
            document.querySelectorAll('[data-surface-btn]').forEach(function(btn) {
                var targetMode = btn.getAttribute('data-surface-btn');
                var active = (targetMode === mode) || (targetMode === 'white' && isWhite) || (targetMode === 'paper' && isWhite);
                btn.classList.toggle('is-active', active);
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        };

        window.setSurfaceTheme = function(mode) {
            // Aydınlık mod veya paper seçildiğinde kusursuz saf beyaz (white) aktifleştirilir
            var nextMode = (mode === 'dark') ? 'dark' : 'white';
            var root = document.documentElement;
            var isHomepage = root.classList.contains('hp-page');
            var isFieldMap = root.classList.contains('field-map-page');
            if (isFieldMap) nextMode = 'dark';
            root.setAttribute('data-surface-theme', nextMode);
            // Sitenin kendi renkleri (yazı rengi vb.) yalnızca sitenin zemininde geçerli
            root.classList.toggle('er-site-surface', nextMode === window.erSiteSurfaceBase);

            try {
                localStorage.setItem('er_surface_theme', nextMode);
                if (window.erSiteSurfaceBase) localStorage.setItem('er_surface_theme_base', window.erSiteSurfaceBase);
            } catch (e) {}

            var meta = document.querySelector('meta[name="color-scheme"]');
            if (isHomepage) {
                if (nextMode === 'white') {
                    root.classList.remove('dark', 'hp-surface-dark', 'gallery-paper', 'hp-surface-paper');
                    root.classList.add('light', 'gallery-white', 'hp-surface-white');
                    if (meta) meta.content = 'light';
                } else {
                    root.classList.remove('light', 'gallery-paper', 'hp-surface-paper', 'gallery-white', 'hp-surface-white');
                    if (!root.classList.contains('dark')) root.classList.add('dark');
                    root.classList.add('hp-surface-dark');
                    if (meta) meta.content = 'dark';
                }
            } else if (!isFieldMap && nextMode === 'white') {
                root.classList.remove('dark', 'about-surface-dark', 'gallery-paper', 'about-surface-paper');
                root.classList.add('light', 'gallery-white', 'about-surface-white');
                if (meta) meta.content = 'light';
            } else {
                root.classList.remove('light', 'gallery-paper', 'about-surface-paper', 'gallery-white', 'about-surface-white');
                if (!root.classList.contains('dark')) root.classList.add('dark');
                root.classList.add('about-surface-dark');
                if (meta) meta.content = 'dark';
            }

            window.syncSurfaceThemeUI();
            if (typeof window.checkHeaderLuminance === 'function') {
                window.checkHeaderLuminance();
            }
        };

        window.toggleSurfaceTheme = function(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            var current = document.documentElement.getAttribute('data-surface-theme');
            var isLight = current === 'white' || current === 'paper' || document.documentElement.classList.contains('light');
            window.setSurfaceTheme(isLight ? 'dark' : 'white');
        };

        window.addEventListener('storage', function(e) {
            if (e.key === 'er_surface_theme') {
                window.setSurfaceTheme(e.newValue === 'dark' ? 'dark' : 'white');
            }
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', window.syncSurfaceThemeUI);
        } else {
            window.syncSurfaceThemeUI();
        }

        // ── SCROLL DAVRANIŞI: SHRINK ON SCROLL, GLASS ON SCROLL & SMART STICKY ──
        (function() {
            var bar = document.querySelector('.om-header-bar');
            if (!bar) return;
            var posInner = bar.getAttribute('data-pos-inner') || 'absolute';
            var isCover = document.documentElement.classList.contains('hp-page') || document.documentElement.classList.contains('mono-page');
            var lastScrollY = window.scrollY || 0;

            window.addEventListener('scroll', function() {
                var currY = window.scrollY || 0;
                bar.classList.toggle('is-scrolled', currY > 36);

                if (!isCover && posInner === 'smart_sticky') {
                    if (currY > 120 && currY > lastScrollY + 6) {
                        bar.classList.add('is-scroll-hidden');
                    } else if (currY < lastScrollY - 4 || currY <= 80) {
                        bar.classList.remove('is-scroll-hidden');
                    }
                }
                lastScrollY = currY;
            }, { passive: true });
        })();

        // ── ARKA PLAN PARLAKLIĞINA GÖRE LOGO & HAMBURGER RENGİ (Siyah/Beyaz) ──
        window.checkHeaderLuminance = function() {
            var bar = document.querySelector('.om-header-bar');
            if (!bar) return;

            var root = document.documentElement;
            var hpContainer = document.getElementById('hp-container');

            // 1. Ana sayfada sol üst logonun altında fotoğraf değil düz zemin (surface) bulunan şablonlar:
            //    monograph_split (sol panel düz zemin), museum_passepartout (çerçeve dışı düz zemin),
            //    curatorial_grid (sayfa düz zemin), minimal_index (sol liste düz zemin)
            //    veya fotoğrafın tam ekran kaplamadığı (contain / framed) modlar:
            if (hpContainer) {
                var isSurfaceUnderLogo =
                    hpContainer.classList.contains('hp-tpl-monograph_split') ||
                    hpContainer.classList.contains('hp-tpl-museum_passepartout') ||
                    hpContainer.classList.contains('hp-tpl-curatorial_grid') ||
                    hpContainer.classList.contains('hp-tpl-minimal_index') ||
                    hpContainer.classList.contains('hp-fit-contain') ||
                    hpContainer.classList.contains('hp-fit-framed');

                if (isSurfaceUnderLogo) {
                    var isLightSurface = root.classList.contains('light') || root.classList.contains('hp-surface-white') || root.classList.contains('hp-surface-paper') || root.classList.contains('gallery-paper') || ['white', 'paper'].indexOf(root.getAttribute('data-surface-theme')) !== -1;
                    bar.classList.toggle('is-on-light-bg', isLightSurface);
                    return;
                }
            }

            if (bar.getAttribute('data-adaptive-lum') === '0') {
                bar.classList.remove('is-on-light-bg');
                return;
            }

            // Header bar kendi koyu fonuna sahipse (gradient, glass, solid_dark) logo daima beyaz (logo-dark) kalmalı
            if (bar.classList.contains('om-bg-gradient') || bar.classList.contains('om-bg-glass') || bar.classList.contains('om-bg-solid_dark') || bar.classList.contains('om-bg-custom-dark')) {
                bar.classList.remove('is-on-light-bg');
                return;
            }

            var activeSlide = document.querySelector('.hp-chapter-slide.active img.mono-chapter-img, .mono-inner-hero img.mono-chapter-img');
            if (!activeSlide) {
                var isLightPage = root.classList.contains('hp-surface-paper') || root.classList.contains('gallery-paper') || root.classList.contains('light');
                bar.classList.toggle('is-on-light-bg', isLightPage);
                return;
            }

            if (!activeSlide.complete || activeSlide.naturalWidth === 0) {
                activeSlide.addEventListener('load', window.checkHeaderLuminance, { once: true });
                return;
            }

            try {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d', { willReadFrequently: true });
                if (!ctx) return;
                canvas.width = 32;
                canvas.height = 24;

                // Sadece sol üstteki logonun tam altına denk gelen görünür fotoğraf bölgesini analiz et
                var rect = activeSlide.getBoundingClientRect();
                var vw = rect.width || window.innerWidth;
                var vh = rect.height || window.innerHeight;
                var nw = activeSlide.naturalWidth;
                var nh = activeSlide.naturalHeight;
                var scale = Math.max(vw / nw, vh / nh);
                var renderedW = nw * scale;
                var renderedH = nh * scale;
                var offsetX = Math.max(0, (renderedW - vw) / 2);
                var offsetY = Math.max(0, (renderedH - vh) / 2);

                var sx = Math.max(0, Math.min(nw - 20, Math.round((offsetX + 20) / scale)));
                var sy = Math.max(0, Math.min(nh - 20, Math.round((offsetY + 16) / scale)));
                var sw = Math.max(20, Math.min(nw - sx, Math.round(90 / scale)));
                var sh = Math.max(20, Math.min(nh - sy, Math.round(65 / scale)));

                ctx.drawImage(activeSlide, sx, sy, sw, sh, 0, 0, 32, 24);
                var imgData = ctx.getImageData(0, 0, 32, 24).data;
                var totalBrightness = 0;
                var count = 0;
                for (var i = 0; i < imgData.length; i += 4) {
                    var r = imgData[i], g = imgData[i+1], b = imgData[i+2];
                    totalBrightness += (0.299 * r + 0.587 * g + 0.114 * b);
                    count++;
                }
                var avgLum = totalBrightness / count;

                // Üst karartma (full_vignette / soft_scrim) varsa efektif parlaklığı düşür
                if (hpContainer && (hpContainer.classList.contains('hp-overlay-full_vignette') || hpContainer.classList.contains('hp-overlay-soft_scrim'))) {
                    var overlayAlpha = parseFloat(getComputedStyle(hpContainer).getPropertyValue('--hp-overlay-alpha')) || 0.75;
                    avgLum = avgLum * (1 - overlayAlpha * 0.62);
                }

                // Sadece gerçekten çok açık/beyaz zeminlerde (190 üzeri) siyah logoya geç; karanlık ve orta tonlarda beyaz logo (logo-dark) göster
                if (avgLum > 190) {
                    bar.classList.add('is-on-light-bg');
                } else {
                    bar.classList.remove('is-on-light-bg');
                }
            } catch (err) {
                bar.classList.remove('is-on-light-bg');
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', window.checkHeaderLuminance);
        } else {
            window.checkHeaderLuminance();
        }
        window.addEventListener('load', window.checkHeaderLuminance);
        var hpContainer = document.getElementById('hp-container');
        if (hpContainer) {
            var observer = new MutationObserver(function() {
                setTimeout(window.checkHeaderLuminance, 50);
            });
            observer.observe(hpContainer, { attributes: true, subtree: true, attributeFilter: ['class'] });
        }
    })();
    </script>
<?php
}
?>
