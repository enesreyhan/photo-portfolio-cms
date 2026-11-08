<?php
/**
 * admin/templates.php
 * Şablonlar & Görünüm Merkezi (Design & Template Studio Hub)
 * Sitedeki tüm sayfaların, vitrinlerin, üst menünün ve alt bilginin şablon/mizanpaj
 * ayarlarına tek bir görsel merkezden ulaşılmasını ve tek tıkla stil paketlerinin uygulanmasını sağlar.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$admin_title     = __a('templates_hub_title', 'Temalar');
$breadcrumb_root = __a('bc_system', 'Sistem & Ayarlar');
$breadcrumb_page = __a('templates_hub_title', 'Temalar');

// Dinamik sayfa başlıkları
$homepage_title       = get_setting('homepage_title_' . $admin_lang) ?: __a('menu_homepage', 'Ana Sayfa');
$portfolio_nav_title  = get_setting('portfolio_title_' . $admin_lang) ?: __a('menu_portfolio', 'Portfolyo');
$portfolio_home_title = __a('menu_portfolio_home_settings', 'Portfolyo Ana Sayfa');
$portfolio_inner_title= __a('menu_inner_pages', 'Portfolyo İç Sayfalar');
$blog_title           = get_setting('blog_title_' . $admin_lang) ?: __a('menu_blog', 'Blog / Yazılar');
$about_title          = get_setting('about_title_' . $admin_lang) ?: __a('menu_about', 'Hakkımda');
$contact_title        = get_setting('contact_title_' . $admin_lang) ?: __a('menu_contact_page', 'İletişim');

// ── KÜRATORİYEL ANA STİL PAKETLERİ (ONE-CLICK MASTER PRESETS) ──
require_once __DIR__ . '/../includes/style_presets.php';
$MASTER_PRESETS = [
    'cinematic_dark' => [
        'key'        => 'cinematic_dark',
        'title'      => __a('Belgesel & Sinematik Siyah', 'Cinematic Dark'),
        'badge'      => __a('En Çok Tercih Edilen', 'Most Popular'),
        'badge_cls'  => 'badge-primary',
        'desc'       => __a('Karanlık oda derinliğinde siyah zemin (#000000), tam ekran fotoğraf ve akıcı yatay menü.', 'Deep black darkroom ambience (#000000), full-screen imagery, and fluid horizontal navigation.'),
        'surface'    => 'dark',
        'surface_lbl'=> __a('Karanlık Siyah (#000000)', 'Dark Black (#000000)'),
        'hp_tpl'     => 'cinema_fullbleed',
        'hp_lbl'     => __a('Tam Ekran Sinematik Slayt', 'Full-Bleed Cinematic Slide'),
        'inner_tpl'  => 'spread_right',
        'inner_lbl'  => __a('Sağda Görsel Forma', 'Right Visual Spread'),
        'header_lbl' => __a('Yatay Klasik Menü', 'Horizontal Classic Nav'),
        'bg_color'   => '#0a0a0c',
        'card_border'=> 'rgba(255,255,255,0.12)',
        'mockup'     => 'cinematic_dark',
        'settings'   => style_preset_settings()['cinematic_dark'],
    ],
    'pure_white_gallery' => [
        'key'        => 'pure_white_gallery',
        'title'      => __a('Saf Beyaz Galeri & Orijinal Kadraj', 'Pure White Cube Gallery'),
        'badge'      => __a('Düz Beyaz & Minimalist', 'Pure White & Minimal'),
        'badge_cls'  => 'badge-published',
        'desc'       => __a('Modern sanat galerisi sadeliğinde düz saf beyaz zemin (#FFFFFF), fotoğrafların orijinal kadrajını koruyan beyaz küp sergi düzeni ve siyah zarif tipografi.', 'Modern contemporary gallery aesthetic in stark pure white (#FFFFFF), preserving original aspect ratios in white cube spreads with dark typography.'),
        'surface'    => 'white',
        'surface_lbl'=> __a('Saf Beyaz (#FFFFFF)', 'Pure White (#FFFFFF)'),
        'hp_tpl'     => 'white_cube_gallery',
        'hp_lbl'     => __a('Beyaz Küp Galeri', 'White Cube Gallery'),
        'inner_tpl'  => 'white_cube',
        'inner_lbl'  => __a('Beyaz Küp Sergi', 'White Cube Exhibition'),
        'header_lbl' => __a('Yatay Sol Menü', 'Horizontal Left Menu'),
        'bg_color'   => '#ffffff',
        'card_border'=> '#e2e8f0',
        'mockup'     => 'pure_white_gallery',
        'settings'   => style_preset_settings()['pure_white_gallery'],
    ],
    'editorial_magazine' => [
        'key'        => 'editorial_magazine',
        'title'      => __a('Editoryal Dergi & Foto-Röportaj', 'Editorial Magazine & Essay'),
        'badge'      => __a('Editoryal', 'Editorial'),
        'badge_cls'  => 'badge-featured',
        'desc'       => __a('Tipografi hiyerarşisi, büyük manşetler, çift sütunlu dergi mizanpajı ve anlatı odaklı foto-hikaye ritmi.', 'Strong typographic hierarchy, large covers, two-column magazine spreads, and narrative photo-essay rhythm.'),
        'surface'    => 'dark',
        'surface_lbl'=> __a('Karanlık Zemin (#0A0C10)', 'Dark Charcoal (#0A0C10)'),
        'hp_tpl'     => 'editorial_magazine',
        'hp_lbl'     => __a('Editoryal Dergi Manşet', 'Editorial Magazine Cover'),
        'inner_tpl'  => 'title_photo_text',
        'inner_lbl'  => __a('Dergi Makalesi', 'Magazine Article Spread'),
        'header_lbl' => __a('Yatay Klasik Menü', 'Horizontal Classic Nav'),
        'bg_color'   => '#0f1115',
        'card_border'=> 'rgba(255,255,255,0.12)',
        'mockup'     => 'editorial_magazine',
        'settings'   => style_preset_settings()['editorial_magazine'],
    ],
    'museum_paper' => [
        'key'        => 'museum_paper',
        'title'      => __a('Müze Paspartu & Sıcak Kitap Kağıdı', 'Museum Paper & Passepartout'),
        'badge'      => __a('Sanat Kitabı Dokusu', 'Art Book Texture'),
        'badge_cls'  => 'badge-secondary',
        'desc'       => __a('Sıcak krem arşiv kağıdı (#F4F2ED), simetrik paspartu çerçeveler ve küratöryel sergi künyeleri.', 'Warm archival cream paper (#F4F2ED), symmetric passepartout borders, and curatorial exhibition labels.'),
        'surface'    => 'paper',
        'surface_lbl'=> __a('Sıcak Krem Kağıt (#F4F2ED)', 'Warm Cream Paper (#F4F2ED)'),
        'hp_tpl'     => 'museum_passepartout',
        'hp_lbl'     => __a('Müze Paspartu', 'Museum Passepartout'),
        'inner_tpl'  => 'centered_museum',
        'inner_lbl'  => __a('Müze Ortalanmış', 'Centered Museum Spread'),
        'header_lbl' => __a('Yatay Klasik Menü', 'Horizontal Classic Nav'),
        'bg_color'   => '#f4f2ed',
        'card_border'=> '#e2dcd2',
        'mockup'     => 'museum_paper',
        'settings'   => style_preset_settings()['museum_paper'],
    ],
    'vertical_monograph' => [
        'key'        => 'vertical_monograph',
        'title'      => __a('Çağdaş Dikey Monografi', 'Contemporary Vertical Monograph'),
        'badge'      => __a('Monografi', 'Monograph'),
        'badge_cls'  => 'badge-primary',
        'desc'       => __a('Solda sabit dikey menü, monografik bölüm yapısı ve sağda kesintisiz akan görsel sergi.', 'Fixed left vertical sidebar navigation, monograph chapters, and continuous visual exhibition wall.'),
        'surface'    => 'dark',
        'surface_lbl'=> __a('Karanlık Siyah (#000000)', 'Dark Black (#000000)'),
        'hp_tpl'     => 'vertical_chapters',
        'hp_lbl'     => __a('Dikey Hikaye Bölümleri', 'Vertical Story Chapters'),
        'inner_tpl'  => 'sticky_split',
        'inner_lbl'  => __a('Sabit İkili Düzen Detay', 'Sticky Split Monograph'),
        'header_lbl' => __a('Solda Dikey Menü', 'Left Vertical Menu'),
        'bg_color'   => '#000000',
        'card_border'=> 'rgba(255,255,255,0.14)',
        'mockup'     => 'vertical_monograph',
        'settings'   => style_preset_settings()['vertical_monograph'],
    ],
    'sidebar_list_look' => [
        'key'        => 'sidebar_list_look',
        'title'      => __a('Kenar Listesi', 'Sidebar List'),
        'badge'      => __a('Yeni · Sade', 'New · Minimal'),
        'badge_cls'  => 'badge-info',
        'desc'       => __a('Beyaz zeminde solda proje listesi, sağda kırpılmadan tek büyük fotoğraf; proje sayfalarında altyazılı sekans. Az sayıda güçlü proje için.', 'White background, project list on the left and one large uncropped photo on the right; captioned sequence on project pages. For a few strong projects.'),
        'surface'    => 'white',
        'surface_lbl'=> __a('Beyaz', 'White'),
        'hp_tpl'     => 'sidebar_list',
        'hp_lbl'     => __a('Kenar Listesi Sunumu', 'Sidebar List'),
        'inner_tpl'  => 'white_cube',
        'inner_lbl'  => __a('Altyazılı Sekans', 'Captioned Sequence'),
        'header_lbl' => __a('Sola Yaslı Yatay Menü', 'Left-Aligned Top Bar'),
        'bg_color'   => '#ffffff',
        'card_border'=> 'rgba(0,0,0,0.1)',
        'mockup'     => 'photo',
        'settings'   => style_preset_settings()['sidebar_list_look'],
    ],
    'map_atlas' => [
        'key'        => 'map_atlas',
        'title'      => __a('Harita Atlası', 'Map Atlas'),
        'badge'      => __a('Yeni · Belgesel', 'New · Documentary'),
        'badge_cls'  => 'badge-info',
        'desc'       => __a('Ana sayfada dünya haritası üzerinde projeler, portfolyoda yıllara göre arşiv, proje sayfalarında altyazılı sekans. Farklı coğrafyalarda çalışanlar için.', 'Projects on a world map on the homepage, archive by year in the portfolio, captioned sequence on project pages. For work across many places.'),
        'surface'    => 'dark',
        'surface_lbl'=> __a('Koyu', 'Dark'),
        'hp_tpl'     => 'project_map',
        'hp_lbl'     => __a('Proje Haritası', 'Project Map'),
        'inner_tpl'  => 'spread_right',
        'inner_lbl'  => __a('Altyazılı Sekans', 'Captioned Sequence'),
        'header_lbl' => __a('Klasik Yatay Menü', 'Classic Top Bar'),
        'bg_color'   => '#0b0d10',
        'card_border'=> 'rgba(255,255,255,0.12)',
        'mockup'     => 'photo',
        'settings'   => style_preset_settings()['map_atlas'],
    ],
    'archive_look' => [
        'key'        => 'archive_look',
        'title'      => __a('Arşiv', 'Archive'),
        'badge'      => __a('Yeni · Arşiv', 'New · Archive'),
        'badge_cls'  => 'badge-info',
        'desc'       => __a('Ana sayfada yıllara göre arşiv, portfolyoda kontak baskı, proje sayfalarında iki sütunlu galeri. Uzun yıllara yayılan çok projeli işler için.', 'Archive by year on the homepage, contact sheet in the portfolio, two-column gallery on project pages. For many projects over many years.'),
        'surface'    => 'white',
        'surface_lbl'=> __a('Beyaz', 'White'),
        'hp_tpl'     => 'year_archive',
        'hp_lbl'     => __a('Yıllara Göre Arşiv', 'Archive by Year'),
        'inner_tpl'  => 'title_photo_text',
        'inner_lbl'  => __a('İki Sütunlu Galeri', 'Two-Column Gallery'),
        'header_lbl' => __a('Klasik Yatay Menü', 'Classic Top Bar'),
        'bg_color'   => '#ffffff',
        'card_border'=> 'rgba(0,0,0,0.1)',
        'mockup'     => 'photo',
        'settings'   => style_preset_settings()['archive_look'],
    ],
    'photobook_look' => [
        'key'        => 'photobook_look',
        'title'      => __a('Fotokitap', 'Photobook'),
        'badge'      => __a('Yeni · Kitap', 'New · Book'),
        'badge_cls'  => 'badge-info',
        'desc'       => __a('Krem kâğıt zemin; ana sayfada fotoğraf ve kısa bildiri, portfolyoda serbest kolaj, proje sayfalarında kitap gibi açılımlar. Kitap ve sergi dili için.', 'Cream paper background; photo and short statement on the homepage, free collage in the portfolio, book-like spreads on project pages.'),
        'surface'    => 'paper',
        'surface_lbl'=> __a('Krem Kâğıt', 'Cream Paper'),
        'hp_tpl'     => 'photo_statement',
        'hp_lbl'     => __a('Fotoğraf + Bildiri', 'Photo + Statement'),
        'inner_tpl'  => 'centered_museum',
        'inner_lbl'  => __a('Fotokitap Açılımı', 'Photobook Spread'),
        'header_lbl' => __a('Sola Yaslı Yatay Menü', 'Left-Aligned Top Bar'),
        'bg_color'   => '#f4f2ed',
        'card_border'=> 'rgba(0,0,0,0.12)',
        'mockup'     => 'photo',
        'settings'   => style_preset_settings()['photobook_look'],
    ],
    'agency_showcase' => [
        'key'        => 'agency_showcase',
        'title'      => __a('Ajans Vitrini', 'Agency Showcase'),
        'badge'      => __a('Yeni · Vitrin', 'New · Showcase'),
        'badge_cls'  => 'badge-info',
        'desc'       => __a('Ana sayfada tam ekran kapak, seçili işler, son yazılar ve iletişim çağrısı; portfolyoda küratöryel ızgara. Editörlere ve müşterilere hızlı yön vermek için.', 'Full-screen cover, selected work, latest writing and a contact call on the homepage; curatorial grid in the portfolio. Gives editors and clients a quick overview.'),
        'surface'    => 'dark',
        'surface_lbl'=> __a('Koyu', 'Dark'),
        'hp_tpl'     => 'sectioned_showcase',
        'hp_lbl'     => __a('Bölümlü Vitrin', 'Sectioned Showcase'),
        'inner_tpl'  => 'cinema_hero',
        'inner_lbl'  => __a('Sinematik Kapak', 'Cinematic Cover'),
        'header_lbl' => __a('Saydam Sabit Yatay', 'Transparent Top Bar'),
        'bg_color'   => '#0a0a0c',
        'card_border'=> 'rgba(255,255,255,0.12)',
        'mockup'     => 'photo',
        'settings'   => style_preset_settings()['agency_showcase'],
    ],
];

// ── STİL PAKETİ: UYGULA (yedekli) · GERİ AL · ÖNİZLE ──
$master_preset_applied = null;

/** Paketlerin değiştirdiği ayarların şu anki değerleri (olmayanlar null) */
function style_current_snapshot(): array {
    $snap = [];
    foreach (style_preset_tracked_keys() as $k) {
        $snap[$k] = array_key_exists($k, $GLOBALS['site_settings_cache'] ?? []) ? get_setting($k) : null;
    }
    // Kategoriye özel tasarım ayarları ("alan@kategori") da geri alınabilsin
    foreach ($GLOBALS['site_settings_cache'] ?? [] as $k => $v) {
        if (str_contains((string)$k, '@') && design_scoped_key((string)$k)) $snap[$k] = (string)$v;
    }
    return $snap;
}
function style_history(): array {
    $h = json_decode((string)get_setting(STYLE_HISTORY_KEY, '[]'), true);
    return is_array($h) ? $h : [];
}
function style_history_save(array $h): void {
    set_setting(STYLE_HISTORY_KEY, json_encode(array_values($h), JSON_UNESCAPED_UNICODE));
}

// Önizleme çerezi seçenekleri (yalnızca yönetici, bir saat)
function style_preview_cookie_set(string $value): void {
    setcookie(STYLE_PREVIEW_COOKIE, $value, [
        'expires' => time() + 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
}
function style_preview_cookie_clear(): void {
    setcookie(STYLE_PREVIEW_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
}

// Önizlemeyi başlat: yalnızca bu tarayıcıda, kayıt yapmadan siteyi paketle göster
if (isset($_GET['preview']) && isset(style_preset_settings()[(string)$_GET['preview']])) {
    style_preview_cookie_set((string)$_GET['preview']);
    header('Location: ' . url(get_default_language() . '/'));
    exit;
}
// Taslağı yeni sekmede önizle
if (isset($_GET['preview_draft']) && design_draft_get()) {
    style_preview_cookie_set(STYLE_PREVIEW_DRAFT);
    header('Location: ' . url($admin_lang . '/'));
    exit;
}
// Önizlemeyi kapat (taslak silinmez; Temalar sayfasından devam edilebilir)
if (isset($_GET['preview_end'])) {
    style_preview_cookie_clear();
    if (isset($_GET['ajax'])) { http_response_code(204); exit; }
    header('Location: templates.php');
    exit;
}

/** JSON yanıt + çıkış */
function design_json(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── TASARIM DÜZENLEYİCİ: TASLAĞI KAYDET (canlı önizleme; ziyaretçiler görmez) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'design_draft_save') {
    $tid = (string)($_POST['theme'] ?? '');
    if (!verify_csrf($_POST['csrf_token'] ?? '') || !isset(site_templates()[$tid])) design_json(['ok' => false], 400);
    $opts   = json_decode((string)($_POST['opts'] ?? '{}'), true);
    $values = json_decode((string)($_POST['values'] ?? '{}'), true);
    $draft  = design_draft_clean($tid, is_array($opts) ? $opts : [], is_array($values) ? $values : []);
    design_draft_save($draft);
    style_preview_cookie_set(STYLE_PREVIEW_DRAFT);
    design_json(['ok' => true]);
}

// ── TASARIM DÜZENLEYİCİ: TASLAĞI SİL ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'design_draft_discard') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) design_json(['ok' => false], 400);
    design_draft_clear();
    style_preview_cookie_clear();
    if (!empty($_POST['ajax'])) design_json(['ok' => true]);
    set_flash('success', __a('Yayınlanmamış değişiklikler silindi.', 'Unpublished changes discarded.'));
    header('Location: templates.php');
    exit;
}

// ── TASARIM DÜZENLEYİCİ: LOGO / SİMGE YÜKLE (dosya kaydedilir, değer yalnızca taslağa girer) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'design_upload') {
    $field = (string)($_POST['field'] ?? '');
    $def   = design_editor_fields()[$field] ?? null;
    if (!verify_csrf($_POST['csrf_token'] ?? '') || !$def || $def['type'] !== 'image' || empty($_FILES['file'])) design_json(['ok' => false], 400);
    $res = design_upload_brand_file($_FILES['file'], str_replace('_', '-', preg_replace('/^brand_/', '', $field)));
    if (is_array($res)) design_json(['ok' => false, 'error' => $res['error']], 422);
    design_json(['ok' => true, 'path' => $res, 'url' => url($res)]);
}

// ── TASARIM DÜZENLEYİCİ: YAYINLA (taslak siteye yazılır; önceki görünüm geri alınabilir) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'design_publish') {
    $draft = design_draft_get();
    if (!verify_csrf($_POST['csrf_token'] ?? '') || !$draft) {
        set_flash('error', __a('Yayınlanacak değişiklik bulunamadı.', 'No changes to publish.'));
        header('Location: templates.php');
        exit;
    }
    $t     = site_templates()[$draft['theme']];
    $title = __a($t['tr'], $t['en']);
    get_setting('__warm_cache__');
    $hist   = style_history();
    $snap   = style_current_snapshot();
    $writes = design_draft_writes($draft);
    foreach ($writes as $k => $v) { if (design_scoped_key((string)$k) && !array_key_exists($k, $snap)) $snap[$k] = null; }
    $hist[] = ['at' => date('d.m.Y H:i'), 'preset' => $title, 'values' => $snap];
    style_history_save(array_slice($hist, -STYLE_HISTORY_MAX));
    foreach ($writes as $k => $v) {
        set_setting($k, (string)$v);
    }
    design_draft_clear();
    style_preview_cookie_clear();
    $only_scoped = $writes && !array_filter(array_keys($writes), fn($k) => !design_scoped_key((string)$k));
    set_flash('success', $only_scoped
        ? __a('Sayfaya özel tasarım yayınlandı; sitenin geri kalanı değişmedi. Beğenmezseniz “Önceki görünüme dön” ile geri alabilirsiniz.', 'Page design published; the rest of the site is unchanged. Use “Revert to previous look” to undo.')
        : sprintf(__a('“%s” teması yayınlandı. Beğenmezseniz “Önceki görünüme dön” ile geri alabilirsiniz.', '“%s” theme published. Use “Revert to previous look” to undo.'), $title));
    header('Location: templates.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array($_POST['action'] ?? '', ['apply_master_preset', 'undo_master_preset'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', __a('Güvenlik doğrulaması başarısız (CSRF).', 'Security check failed (CSRF).'));
        header('Location: templates.php');
        exit;
    }
    // Uygulama ya da geri alma sonrası önizleme çerezi kalmasın
    setcookie(STYLE_PREVIEW_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);

    if ($_POST['action'] === 'apply_master_preset') {
        $preset_key = $_POST['preset_key'] ?? '';
        // Eski görünüm paketleri ($MASTER_PRESETS) ya da ana şablonlar (tpl:<id>, includes/site_templates.php)
        $pkg_settings = $MASTER_PRESETS[$preset_key]['settings'] ?? (style_preset_settings()[$preset_key] ?? null);
        $pkg_title    = $MASTER_PRESETS[$preset_key]['title'] ?? (isset(style_preset_titles()[$preset_key]) ? __a(style_preset_titles()[$preset_key]['tr'], style_preset_titles()[$preset_key]['en']) : '');
        if (is_array($pkg_settings)) {
            get_setting('__warm_cache__'); // önbelleği doldur
            $hist = style_history();
            $hist[] = [
                'at'     => date('d.m.Y H:i'),
                'before' => __a('“%s” uygulanmadan önceki görünüm', 'Look before applying “%s”'),
                'preset' => $pkg_title,
                'values' => style_current_snapshot(),
            ];
            style_history_save(array_slice($hist, -STYLE_HISTORY_MAX));
            foreach ($pkg_settings as $k => $v) {
                set_setting($k, $v);
            }
            set_flash('success', sprintf(__a('“%s” görünümü uygulandı. Beğenmezseniz “Önceki görünüme dön” ile geri alabilirsiniz.', '“%s” applied. Use “Revert to previous look” to undo.'), $pkg_title));
        }
    } else {
        $hist = style_history();
        $last = array_pop($hist);
        if ($last && is_array($last['values'] ?? null)) {
            $pdo = DB::getConnection();
            $del = $pdo->prepare("DELETE FROM `settings` WHERE `key_name` = ?");
            foreach ($last['values'] as $k => $v) {
                if (!in_array($k, style_preset_tracked_keys(), true) && !design_scoped_key((string)$k)) continue;
                if ($v === null) { $del->execute([$k]); } else { set_setting($k, (string)$v); }
            }
            style_history_save($hist);
            set_flash('success', __a('Önceki görünüme dönüldü.', 'Reverted to the previous look.'));
        }
    }
    // Ana şablon uygulandıysa doğrudan o şablonun seçeneklerine dön
    header('Location: templates.php' . (str_starts_with((string)($_POST['preset_key'] ?? ''), 'tpl:') ? '#secenekler' : ''));
    exit;
}

// ── ANA ŞABLON SEÇENEKLERİ: KAYDET (yalnızca etkin şablonun seçenekleri) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save_template_options') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', __a('Güvenlik doğrulaması başarısız (CSRF).', 'Security check failed (CSRF).'));
        header('Location: templates.php#secenekler');
        exit;
    }
    $active_id = site_template_active();
    $opt_defs  = site_template_options();
    $posted    = is_array($_POST['opt'] ?? null) ? $_POST['opt'] : [];
    foreach (site_templates()[$active_id]['options'] as $opt) {
        if (!isset($opt_defs[$opt])) continue;
        $val = $opt_defs[$opt]['type'] === 'toggle' ? (isset($posted[$opt]) ? '1' : '0') : (string)($posted[$opt] ?? '');
        foreach (site_template_option_writes($opt, $val) as $k => $v) {
            set_setting($k, $v);
        }
    }
    set_flash('success', __a('Şablon seçenekleri kaydedildi.', 'Template options saved.'));
    header('Location: templates.php#secenekler');
    exit;
}
$style_hist = style_history();
// Yeni görünüm kartlarının önizlemesi için ana sayfa seçkisinden küçük fotoğraflar
$mock_imgs = [];
try {
    $mock_rows = DB::getConnection()->query("SELECT `image_path` FROM `homepage_slides` WHERE `is_active` = 1 ORDER BY `weight` ASC, `id` ASC LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($mock_rows as $mp) {
        $mp = ltrim((string)$mp, '/');
        $full = function_exists('resolve_image_file_path') ? resolve_image_file_path($mp) : null;
        $small = ($full && function_exists('resize_and_convert_image')) ? resize_and_convert_image($full, 800, 'webp', 80) : null;
        $mock_imgs[] = url($small ?: $mp);
    }
} catch (\Throwable $e) {}
while ($mock_imgs && count($mock_imgs) < 4) { $mock_imgs[] = $mock_imgs[count($mock_imgs) % max(1, count($mock_imgs))]; }
$style_last = $style_hist ? end($style_hist) : null;

// Ana şablon kartları için: etkin şablon ve küçük taslaklarda kullanılacak çalışma kapakları
$site_tpls     = site_templates();
$tpl_opt_defs  = site_template_options();
$active_tpl_id = site_template_active();
$sketch_imgs   = [];
$sketch_titles = [];
try {
    $cov = DB::getConnection()->query("SELECT * FROM `items` WHERE `status` = 'published' AND `type` IN (SELECT `key_slug` FROM `categories`) AND `cover_image` IS NOT NULL AND `cover_image` <> '' ORDER BY `date` DESC, `id` DESC LIMIT 14")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cov as $c) {
        $full  = resolve_image_file_path(ltrim((string)$c['cover_image'], '/'));
        $small = $full ? resize_and_convert_image($full, 800, 'webp', 80) : null;
        if (!$small && !$full) continue;
        $sketch_imgs[]   = url($small ?: ltrim((string)$c['cover_image'], '/'));
        $sketch_titles[] = item_field($c, 'title', $admin_lang);
    }
} catch (\Throwable $e) {}
if (!$sketch_imgs) { $sketch_imgs = $mock_imgs; }
$sketch_name = trim((string)get_setting('header_logo_text', get_setting('site_title', '')));
$preview_active_key = $_COOKIE[STYLE_PREVIEW_COOKIE] ?? '';

require __DIR__ . '/includes/header.php';
?>

<style>
/* ── KÜRATORİYEL STİL PAKETLERİ (MASTER PRESETS) ── */
.master-presets-wrap {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 28px;
    margin-bottom: 40px;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}
.master-presets-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}
.master-presets-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-main);
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.master-presets-sub {
    font-size: 13.5px;
    color: var(--text-muted);
    margin: 0;
    max-width: 720px;
    line-height: 1.5;
}
.master-presets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 20px;
}
.master-preset-card {
    background: var(--bg-body);
    border: 1.5px solid var(--border-color);
    border-radius: 10px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.22s ease;
    position: relative;
}
.master-preset-card:hover {
    border-color: #3b82f6;
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.08);
}
.master-preset-card.is-active-preset {
    border-color: #10b981;
    background: color-mix(in srgb, #10b981 4%, var(--bg-body));
    box-shadow: 0 0 0 1px #10b981, 0 8px 24px rgba(16, 185, 129, 0.12);
}
.preset-active-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #10b981;
    color: #ffffff;
    font-size: 10.5px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
}
.preset-mockup-frame {
    width: 100%;
    height: 130px;
    border-radius: 6px;
    margin-bottom: 16px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(0, 0, 0, 0.1);
}
.preset-mockup-frame svg {
    width: 100%;
    height: 100%;
    display: block;
}
.preset-card-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-main);
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.preset-card-desc {
    font-size: 12.5px;
    color: var(--text-muted);
    line-height: 1.5;
    margin: 0 0 16px 0;
    min-height: 48px;
}
.preset-spec-list {
    list-style: none;
    padding: 0;
    margin: 0 0 18px 0;
    font-size: 11.5px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    border-top: 1px dashed var(--border-color);
    padding-top: 12px;
}
.preset-spec-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: var(--text-muted);
}
.preset-spec-item strong {
    color: var(--text-main);
    font-weight: 600;
}
.preset-apply-btn {
    width: 100%;
    padding: 9px 14px;
    font-size: 12.5px;
    font-weight: 600;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.18s ease;
    border: none;
}

</style>

<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('Temalar', 'Themes')) ?></h1>
        <p class="page-header-sub">
            <?= e(__a('Bir temaya tıklayın: neler sunduğunu görün, sitenizin kendi fotoğraflarıyla önizleyin, renklerini, logosunu ve menüsünü özelleştirip yayınlayın.', 'Click a theme to see what it offers, preview it with your own photographs, customise its colours, logo and menu, and publish.')) ?>
        </p>
    </div>
</div>

<style>
/* ── Ana şablon seçici (includes/site_templates.php) ── */
.mt-step { display:flex; align-items:center; gap:10px; margin:0 0 6px; font-size:18px; font-weight:700; color:var(--text-main); }
.mt-step-num { width:26px; height:26px; border-radius:50%; background:var(--text-main); color:var(--bg-surface); display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
.mt-sub { font-size:13px; color:var(--text-muted); margin:0 0 18px; max-width:760px; line-height:1.55; }
.mt-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap:18px; margin-bottom:36px; }
.mt-card { background:var(--bg-surface); border:1px solid var(--border-card); border-radius:10px; overflow:hidden; display:flex; flex-direction:column; }
.mt-card.is-active { border-color:#10b981; box-shadow:0 0 0 2px rgba(16,185,129,.35); }
.mt-mock { position:relative; aspect-ratio:16/10; overflow:hidden; background:#fff; color:#111; border-bottom:1px solid var(--border-card); }
.mt-mock .bar { position:absolute; left:0; right:0; top:0; height:12px; background:#e9e7e2; display:flex; align-items:center; gap:4px; padding:0 7px; z-index:5; }
.mt-mock .bar i { width:4px; height:4px; border-radius:50%; background:#c9c6bf; }
.mt-mock .screen { position:absolute; inset:12px 0 0 0; overflow:hidden; }
.mt-mock .img { background-size:cover; background-position:center; background-color:#333; }
.mt-mock .img.fit { background-size:contain; background-repeat:no-repeat; background-color:transparent; }
.mt-mock .t-name { font-weight:800; letter-spacing:.06em; font-size:8px; text-transform:uppercase; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
.mt-mock .t-menu { font-size:7px; line-height:1.9; opacity:.75; }
.mt-mock .t-small { font-size:6.5px; opacity:.7; }
.mt-mock .t-mono { font:600 6px/1.2 ui-monospace, Menlo, monospace; }
.mt-mock .dark { background:#0b0b0c; color:#f2f2f2; } .mt-mock .paper { background:#f1ede4; color:#1b1a17; }
.mt-body { padding:13px 15px 15px; display:flex; flex-direction:column; gap:7px; flex:1; }
.mt-name { display:flex; align-items:baseline; gap:8px; margin:0; font-size:15px; font-weight:700; color:var(--text-main); }
.mt-name .n { font:600 11px/1 var(--font-mono, monospace); color:var(--text-muted); }
.mt-badge { margin-left:auto; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(16,185,129,.14); color:#059669; white-space:nowrap; }
.mt-desc { margin:0; font-size:12.5px; color:var(--text-muted); line-height:1.5; }
.mt-actions { margin-top:auto; padding-top:6px; display:flex; gap:8px; }
.mt-actions form { flex:1; margin:0; }
.mt-actions .btn { width:100%; justify-content:center; }
.mt-opts .setting-row { flex-wrap:wrap; }
.mt-seg { display:inline-flex; flex-wrap:wrap; gap:4px; background:var(--bg-surface-2, var(--bg-body)); border:1px solid var(--border-subtle); border-radius:10px; padding:3px; }
.mt-seg label { position:relative; cursor:pointer; }
.mt-seg input { position:absolute; opacity:0; pointer-events:none; }
.mt-seg span { display:block; padding:6px 11px; border-radius:6px; font-size:12.5px; font-weight:600; color:var(--text-muted); }
.mt-seg input:checked + span { background:var(--bg-surface); color:var(--text-main); box-shadow:0 1px 3px rgba(0,0,0,.12); }
.mt-seg input:focus-visible + span { outline:2px solid var(--accent-blue); outline-offset:1px; }
.mt-toggle { width:18px; height:18px; accent-color:#10b981; cursor:pointer; }
</style>

<?php if ($preview_active_key !== '' && isset(style_preset_titles()[$preview_active_key])): ?>
<div class="settings-hint-card" style="border-style:solid; border-color:var(--accent-blue);">
    <span style="flex:1;"><?= e(sprintf(__a('“%s” şu an yalnızca sizin tarayıcınızda önizleniyor; site ziyaretçiler için değişmedi.', '“%s” is being previewed in your browser only; the site has not changed for visitors.'), __a(style_preset_titles()[$preview_active_key]['tr'], style_preset_titles()[$preview_active_key]['en']))) ?></span>
    <a href="templates.php?preview_end=1" class="btn btn-secondary btn-sm"><?= e(__a('Önizlemeyi Kapat', 'End Preview')) ?></a>
</div>
<?php endif; ?>

<?php if ($style_last): ?>
<div class="settings-hint-card" style="border-style:solid; align-items:center;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
    <span style="flex:1;"><?= e(sprintf(__a('“%s” %s tarihinde uygulandı. Beğenmediyseniz önceki görünüme tek tıkla dönebilirsiniz.', '“%s” was applied on %s. If you don’t like it, revert to the previous look in one click.'), (string)($style_last['preset'] ?? ''), (string)($style_last['at'] ?? ''))) ?></span>
    <form method="POST" style="margin:0;" data-confirm="<?= e(__a('Site, bu şablon uygulanmadan önceki görünümüne dönsün mü?', 'Revert the site to the look it had before this template?')) ?>" data-confirm-ok="<?= e(__a('Önceki görünüme dön', 'Revert')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="undo_master_preset">
        <button type="submit" class="btn btn-secondary btn-sm"><?= e(__a('Önceki görünüme dön', 'Revert to previous look')) ?></button>
    </form>
</div>
<?php endif; ?>

<!-- ═════════════════════════════════════════════════════════════════════
     TEMALAR: galeri → tema penceresi (bilgi) → özelleştirme (canlı önizleme)
     Kayıt: includes/site_templates.php + includes/design_editor.php
     Taslak: design_draft_save · Yayınla: design_publish · Görsel: design_upload
     ═════════════════════════════════════════════════════════════════════ -->
<?php
$th_groups = site_template_option_groups();
$th_data   = [];
foreach ($site_tpls as $tid => $t) {
    $vals = [];
    foreach ($t['options'] as $opt) {
        if (isset($tpl_opt_defs[$opt])) $vals[$opt] = site_template_option_value_for($tid, $opt);
    }
    $th_data[$tid] = [
        'name'    => __a($t['tr'], $t['en']),
        'desc'    => __a($t['desc_tr'], $t['desc_en']),
        'info'    => array_map(fn($r) => [__a($r[0], $r[1]), __a($r[2], $r[3])], site_template_info($tid)),
        'options' => array_values(array_filter($t['options'], fn($o) => isset($tpl_opt_defs[$o]))),
        'values'  => $vals,
        'active'  => $tid === $active_tpl_id,
    ];
}
$th_defs = [];
foreach ($tpl_opt_defs as $opt => $d) {
    $choices = [];
    foreach ($d['choices'] ?? [] as $cv => $c) {
        $choices[] = ['v' => (string)$cv, 'label' => __a($c['tr'], $c['en']), 'swatch' => $c['swatch'] ?? null];
    }
    $th_defs[$opt] = ['label' => __a($d['tr'], $d['en']), 'type' => $d['type'], 'ui' => $d['ui'] ?? 'seg', 'group' => $d['group'] ?? 'home', 'choices' => $choices];
}
$th_group_data = [];
foreach (design_editor_sections() as $gk => $g) {
    // Sabit sayfa bölümleri önizlemede kendi sayfasında açılsın (yoksa ana sayfa)
    $g_url = in_array($gk, ['about', 'contact', 'blog'], true) ? url($admin_lang . '/' . get_static_slug($gk, $admin_lang) . '/') : '';
    // Proje sayfası ve listeler: önizleme ilk yayındaki projede / ilk liste sayfasında açılsın
    if ($gk === 'project' && ($pi = DB::getConnection()->query("SELECT * FROM `items` WHERE `status` = 'published' AND `type` IN (SELECT `key_slug` FROM `categories`) ORDER BY `weight` ASC, `date` DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC))) $g_url = item_url($pi, $admin_lang);
    if ($gk === 'lists' && ($pc = DB::getConnection()->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC))) $g_url = url($admin_lang . '/' . ($pc['slug_' . $admin_lang] ?? '' ?: $pc['slug_tr']) . '/');
    $th_group_data[$gk] = ['label' => __a($g['tr'], $g['en']), 'desc' => __a($g['desc_tr'], $g['desc_en']), 'collect' => $g['collect'] ?? '', 'part' => $g['part'] ?? 'general', 'url' => $g_url];
}
$th_active = $site_tpls[$active_tpl_id];

// Tasarım düzenleyici alanları (includes/design_editor.php) ekrana uygun biçimde
$de_fields = [];
foreach (design_editor_fields() as $fk => $f) {
    $row = [
        'section' => $f['section'], 'sub' => $f['sub'], 'type' => $f['type'], 'ui' => $f['ui'] ?? '', 'pics' => $f['pics'] ?? '',
        'label' => __a($f['tr'], $f['en']), 'help' => isset($f['help_tr']) ? __a($f['help_tr'], $f['help_en']) : '',
        'default' => (string)$f['default'], 'show_if' => $f['show_if'] ?? (object)[],
        'auto' => isset($f['auto_tr']) ? __a($f['auto_tr'], $f['auto_en']) : '', 'light' => !empty($f['light_only']), 'noAuto' => !empty($f['no_auto']),
    ];
    if ($f['type'] === 'choice') {
        $row['choices'] = [];
        foreach ($f['choices'] as $cv => $c) $row['choices'][] = ['v' => (string)$cv, 'label' => __a($c['tr'], $c['en'])];
    } elseif ($f['type'] === 'font') {
        $row['choices'] = [];
        if (isset($f['inherit_tr'])) $row['choices'][] = ['v' => '', 'label' => __a($f['inherit_tr'], $f['inherit_en']), 'stack' => design_font_stack((string)get_setting('site_heading_font', 'display'))];
        foreach (design_font_choices() as $cv => $c) $row['choices'][] = ['v' => $cv, 'label' => __a($c['tr'], $c['en']), 'stack' => $c['stack']];
    } elseif ($f['type'] === 'slider') {
        $row += ['min' => $f['min'], 'max' => $f['max'], 'step' => $f['step'], 'unit' => $f['unit']];
    } elseif ($f['type'] === 'text' || $f['type'] === 'url') {
        $row['max'] = (int)($f['max'] ?? 200);
    } elseif ($f['type'] === 'image') {
        $row += ['bg' => $f['bg'] ?? '#f4f4f2', 'fallback' => url($f['fallback'] ?? '')];
    }
    $de_fields[$fk] = $row;
}
foreach ($site_tpls as $tid => $t) {
    $fv = [];
    foreach (array_keys($de_fields) as $fk) $fv[$fk] = design_field_value_for($tid, $fk);
    $th_data[$tid]['fvalues'] = $fv;
}
$de_subs = [];
foreach (design_editor_subs() as $sk => $sv) $de_subs[$sk] = __a($sv['tr'], $sv['en']);
$de_draft = design_draft_get();
// Siteyle aynı olan (yazılacak hiçbir değeri kalmamış) eski taslak "yayınlanmamış değişiklik" diye gösterilmesin
if ($de_draft && !design_draft_writes($de_draft)) { design_draft_clear(); $de_draft = null; }
// Kategoriye özel ayarlar: sayfa seçici (liste ve ilk projenin adresi, önizleme için) ve kayıtlı özel değerler
$de_cats = [];
foreach (design_scope_categories() as $ck => $c) {
    $first = null;
    try {
        $st = DB::getConnection()->prepare("SELECT * FROM `items` WHERE `type` = ? AND `status` = 'published' ORDER BY `weight` ASC, `date` DESC LIMIT 1");
        $st->execute([$ck]);
        $first = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {}
    $c_items = [];
    foreach (design_scope_items() as $iid => $it) {
        if ((string)$it['type'] === (string)$ck) $c_items[] = ['k' => 'item-' . $iid, 'label' => item_field($it, 'title', $admin_lang) ?: ('#' . $iid), 'url' => item_url($it, $admin_lang)];
    }
    $de_cats[] = ['k' => $ck, 'label' => item_field($c, 'name', $admin_lang), 'list' => url($admin_lang . '/' . ($c['slug_' . $admin_lang] ?? $c['slug_en']) . '/'), 'item' => $first ? item_url($first, $admin_lang) : '', 'items' => $c_items];
}
$de_scoped = [];
foreach ($GLOBALS['site_settings_cache'] ?? [] as $sk => $sv) {
    if (str_contains((string)$sk, '@') && design_scoped_key((string)$sk) && ($v = design_field_value((string)$sk)) !== '') $de_scoped[$sk] = $v;
}
?>
<style>
/* ── Tema galerisi ── */
.th-current { display:flex; align-items:center; gap:14px; flex-wrap:wrap; padding:16px 18px; margin:0 0 26px; background:var(--bg-surface); border:1px solid var(--border-card); border-radius:10px; }
.th-current-text { flex:1; min-width:220px; font-size:13px; color:var(--text-muted); }
.th-current-text b { display:block; font-size:16px; color:var(--text-main); margin-top:2px; }
.th-filter { display:flex; flex-wrap:wrap; gap:6px; margin:0 0 18px; }
.th-filter-btn { all:unset; cursor:pointer; padding:7px 13px; border:1px solid var(--border-card); border-radius:999px; font-size:13px; font-weight:600; color:var(--text-muted); background:var(--bg-surface); }
.th-filter-btn span { font-weight:500; opacity:.7; margin-left:3px; }
.th-filter-btn[aria-pressed="true"] { background:var(--text-main); color:var(--bg-surface); border-color:var(--text-main); }
.th-filter-btn:focus-visible { outline:2px solid var(--accent-blue); outline-offset:2px; }
.th-card[hidden] { display:none; }
.th-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap:28px 22px; margin-bottom:40px; }
.th-card { all:unset; box-sizing:border-box; display:flex; flex-direction:column; gap:10px; cursor:pointer; }
.th-card .mt-mock { border:1px solid var(--border-card); border-radius:10px; transition:transform .2s ease, box-shadow .2s ease; }
.th-card:hover .mt-mock, .th-card:focus-visible .mt-mock { transform:translateY(-3px); box-shadow:0 12px 28px rgba(0,0,0,.14); }
.th-card:focus-visible { outline:2px solid var(--accent-blue); outline-offset:4px; border-radius:10px; }
.th-card.is-active .mt-mock { border-color:#10b981; box-shadow:0 0 0 2px rgba(16,185,129,.35); }
.th-card-name { display:flex; align-items:baseline; gap:8px; font-size:15px; font-weight:600; color:var(--text-main); }
.th-card-name .mt-badge { margin-left:auto; }
.th-card-hint { position:absolute; right:10px; bottom:10px; z-index:6; background:rgba(0,0,0,.72); color:#fff; font-size:11.5px; font-weight:600; padding:5px 10px; border-radius:999px; opacity:0; transition:opacity .2s; }
.th-card:hover .th-card-hint, .th-card:focus-visible .th-card-hint { opacity:1; }

/* ── Tema penceresi ── */
.th-modal { position:fixed; inset:0; z-index:10000; background:var(--bg-body, #f3f3f1); display:flex; flex-direction:column; }
.th-modal[hidden] { display:none; }
.th-top { height:58px; flex-shrink:0; display:flex; align-items:center; gap:12px; padding:0 16px; background:var(--bg-surface); border-bottom:1px solid var(--border-card); }
.th-top-left { flex:1; display:flex; align-items:center; gap:10px; min-width:0; }
.th-top-title { font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.th-top-right { flex:1; display:flex; justify-content:flex-end; align-items:center; gap:8px; }
.th-icon-btn { all:unset; box-sizing:border-box; width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; color:var(--text-main); cursor:pointer; }
.th-icon-btn:hover { background:var(--bg-surface-2, rgba(0,0,0,.05)); }
.th-icon-btn:focus-visible { outline:2px solid var(--accent-blue); }
.th-devices { display:inline-flex; border:1px solid var(--border-card); border-radius:10px; padding:2px; gap:2px; }
.th-devices .th-icon-btn { width:40px; height:32px; color:var(--text-muted); }
.th-devices .th-icon-btn[aria-pressed="true"] { color:var(--accent-blue, #2563eb); background:var(--bg-surface-2, rgba(37,99,235,.08)); }
.th-body { flex:1; display:flex; min-height:0; }
.th-side { width:360px; flex-shrink:0; background:var(--bg-surface); border-right:1px solid var(--border-card); overflow-y:auto; }
.th-modal.is-full .th-side { display:none; }
.th-pane { padding:22px 22px 30px; }
.th-pane[hidden] { display:none; }
.th-pane h2 { margin:0 0 6px; font-size:22px; font-weight:700; color:var(--text-main); letter-spacing:-.01em; }
.th-pane-desc { margin:0 0 18px; font-size:13.5px; color:var(--text-muted); line-height:1.55; }
.th-info { margin:0 0 20px; display:grid; grid-template-columns:max-content 1fr; gap:9px 14px; font-size:13px; }
.th-info dt { color:var(--text-muted); font-weight:600; }
.th-info dd { margin:0; color:var(--text-main); line-height:1.45; }
.th-sub { margin:0 0 8px; font-size:11.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--text-muted); }
.th-swatches-row { display:flex; gap:12px; margin:0 0 22px; flex-wrap:wrap; }
.th-swatch-dot { display:flex; flex-direction:column; align-items:center; gap:5px; font-size:11.5px; color:var(--text-muted); }
.th-swatch-dot i { width:34px; height:34px; border-radius:50%; border:1px solid rgba(0,0,0,.15); }
.th-actions { display:flex; flex-direction:column; gap:8px; }
.th-actions .btn { justify-content:center; width:100%; }
.th-editor-label { margin:0 0 6px; font:600 11.5px/1 var(--font-mono, monospace); letter-spacing:.08em; text-transform:uppercase; color:var(--text-muted); }
.th-row { all:unset; box-sizing:border-box; width:100%; display:flex; align-items:center; gap:14px; padding:16px 4px; border-bottom:1px solid var(--border-card); cursor:pointer; color:var(--text-main); }
.th-row:hover .th-row-label { text-decoration:underline; text-underline-offset:3px; }
.th-row:focus-visible { outline:2px solid var(--accent-blue); }
.th-row svg { flex-shrink:0; color:var(--text-muted); }
.th-row-text { flex:1; min-width:0; }
.th-row-label { display:block; font-size:15px; font-weight:500; }
.th-row-desc { display:block; font-size:12.5px; color:var(--text-muted); margin-top:2px; }
.th-back { all:unset; cursor:pointer; display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--text-muted); margin:0 0 16px; }
.th-back:hover { color:var(--text-main); }
.th-back:focus-visible { outline:2px solid var(--accent-blue); }
.th-field { padding:14px 0; border-bottom:1px solid var(--border-card); }
.th-field:last-child { border-bottom:0; }
.th-field-label { font-size:13.5px; font-weight:600; color:var(--text-main); margin:0 0 10px; }
.th-field-inline { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.th-field-inline .th-field-label { margin:0; }
.th-choices { display:grid; grid-template-columns:repeat(2, 1fr); gap:8px; }
.th-choices.is-swatch { grid-template-columns:repeat(4, 1fr); }
.th-choice { position:relative; cursor:pointer; }
.th-choice input { position:absolute; opacity:0; pointer-events:none; }
.th-choice-box { display:flex; flex-direction:column; align-items:center; gap:7px; padding:9px 6px; border:1px solid var(--border-card); border-radius:10px; font-size:12px; font-weight:600; color:var(--text-muted); text-align:center; transition:border-color .15s; }
.th-choice:hover .th-choice-box { border-color:var(--text-muted); }
.th-choice input:checked + .th-choice-box { border-color:var(--accent-blue, #2563eb); box-shadow:0 0 0 1px var(--accent-blue, #2563eb); color:var(--text-main); }
.th-choice input:focus-visible + .th-choice-box { outline:2px solid var(--accent-blue); outline-offset:2px; }
.th-swatch { width:100%; aspect-ratio:1/1; max-width:46px; border-radius:50%; border:1px solid rgba(0,0,0,.15); }
.th-pic { display:block; width:100%; height:46px; border-radius:6px; background:#f4f4f2; border:1px solid rgba(0,0,0,.06); position:relative; overflow:hidden; }
.th-pic i { position:absolute; display:block; background:#1a1a1a; border-radius:1px; }
.th-seg { display:flex; flex-wrap:wrap; gap:4px; background:var(--bg-surface-2, var(--bg-body)); border:1px solid var(--border-subtle, var(--border-card)); border-radius:10px; padding:3px; }
.th-seg label { position:relative; cursor:pointer; flex:1; }
.th-seg input { position:absolute; opacity:0; pointer-events:none; }
.th-seg span { display:block; text-align:center; padding:7px 10px; border-radius:6px; font-size:12.5px; font-weight:600; color:var(--text-muted); }
.th-seg input:checked + span { background:var(--bg-surface); color:var(--text-main); box-shadow:0 1px 3px rgba(0,0,0,.12); }
.th-seg input:focus-visible + span { outline:2px solid var(--accent-blue); }
.th-switch { position:relative; width:42px; height:24px; flex-shrink:0; }
.th-switch input { position:absolute; inset:0; opacity:0; margin:0; cursor:pointer; z-index:1; }
.th-switch span { position:absolute; inset:0; border-radius:999px; background:#c9c9c6; transition:background .2s; }
.th-switch span::after { content:''; position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; transition:transform .2s; box-shadow:0 1px 2px rgba(0,0,0,.25); }
.th-switch input:checked + span { background:#10b981; }
.th-switch input:checked + span::after { transform:translateX(18px); }
.th-switch input:focus-visible + span { outline:2px solid var(--accent-blue); outline-offset:2px; }
.th-stage { flex:1; min-width:0; display:flex; align-items:stretch; justify-content:center; padding:18px; overflow:auto; }
.th-frame-wrap { position:relative; width:100%; max-width:100%; background:#fff; border:1px solid var(--border-card); box-shadow:0 10px 30px rgba(0,0,0,.08); transition:max-width .25s ease; }
.th-modal.is-phone .th-frame-wrap { max-width:390px; border-radius:10px; overflow:hidden; }
.th-modal.is-full .th-stage { padding:0; }
.th-modal.is-full .th-frame-wrap { border:0; box-shadow:none; }
.th-frame-wrap iframe { display:block; width:100%; height:100%; border:0; }
.th-loading { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,.55); font-size:13px; font-weight:600; color:#333; opacity:0; pointer-events:none; transition:opacity .2s; }
.th-loading.is-on { opacity:1; }
.th-saved { font-size:12.5px; color:var(--text-muted); white-space:nowrap; }
.th-top-right [hidden] { display:none !important; }
/* ── Taslak uyarısı ── */
.th-draft { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:14px 16px; margin:0 0 16px; border:1px solid #f59e0b; background:rgba(245,158,11,.08); border-radius:10px; color:var(--text-main); }
.th-draft svg { color:#d97706; flex-shrink:0; }
.th-draft-text { flex:1; min-width:220px; font-size:12.5px; color:var(--text-muted); }
.th-draft-text b { display:block; font-size:14px; color:var(--text-main); }

/* ── Tasarım düzenleyici alanları ── */
.de-sub { margin:22px -22px 0; padding:10px 22px; background:var(--bg-surface-2, rgba(0,0,0,.035)); border-top:1px solid var(--border-card); border-bottom:1px solid var(--border-card); font:600 11px/1.2 var(--font-mono, monospace); letter-spacing:.09em; text-transform:uppercase; color:var(--text-muted); }
.de-sub:first-child { margin-top:0; }
.de-row { padding:13px 0; border-bottom:1px solid var(--border-card); }
.de-row:last-child { border-bottom:0; }
.de-row[hidden] { display:none; }
.de-head { display:flex; align-items:center; gap:6px; margin:0 0 9px; font-size:13.5px; font-weight:600; color:var(--text-main); }
.de-inline { display:flex; align-items:center; gap:12px; }
.de-inline .de-head { margin:0; flex:1; min-width:0; }
.de-help-btn { all:unset; box-sizing:border-box; width:17px; height:17px; border:1px solid var(--border-card); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:10.5px; font-weight:700; color:var(--text-muted); cursor:pointer; flex-shrink:0; }
.de-help-btn:hover, .de-help-btn[aria-expanded="true"] { color:var(--text-main); border-color:var(--text-muted); }
.de-help-btn:focus-visible { outline:2px solid var(--accent-blue); }
.de-help { margin:-3px 0 10px; font-size:12px; line-height:1.5; color:var(--text-muted); }
.de-help[hidden] { display:none; }
.de-input { width:100%; box-sizing:border-box; padding:9px 11px; border:1px solid var(--border-card); border-radius:6px; background:var(--bg-surface); color:var(--text-main); font:inherit; font-size:13.5px; }
.de-input:focus { outline:2px solid var(--accent-blue); outline-offset:0; border-color:transparent; }
.de-slider { display:flex; align-items:center; gap:12px; }
.de-slider input[type=range] { flex:1; accent-color:var(--accent-blue, #2563eb); min-width:0; }
.de-slider output { min-width:46px; text-align:right; font-size:12.5px; font-variant-numeric:tabular-nums; color:var(--text-muted); }
.de-color { display:flex; align-items:center; gap:8px; }
.de-color input[type=color] { width:40px; height:34px; padding:2px; border:1px solid var(--border-card); border-radius:6px; background:var(--bg-surface); cursor:pointer; }
.de-color.is-auto input[type=color] { opacity:.35; }
.de-auto { all:unset; cursor:pointer; font-size:12px; font-weight:600; padding:6px 10px; border:1px solid var(--border-card); border-radius:6px; color:var(--text-muted); }
.de-auto[aria-pressed="true"] { color:var(--text-main); border-color:var(--accent-blue, #2563eb); box-shadow:0 0 0 1px var(--accent-blue, #2563eb); }
.de-auto:focus-visible { outline:2px solid var(--accent-blue); }
.de-font-sample { margin-top:8px; padding:10px 12px; border:1px dashed var(--border-card); border-radius:6px; font-size:18px; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.de-image { display:flex; align-items:center; gap:12px; }
.de-image-prev { width:86px; height:56px; flex-shrink:0; border-radius:6px; border:1px solid var(--border-card); display:flex; align-items:center; justify-content:center; overflow:hidden; }
.de-image-prev img { max-width:80%; max-height:76%; object-fit:contain; }
.de-image-actions { display:flex; flex-direction:column; gap:6px; align-items:flex-start; }
.de-image-actions .btn { padding:5px 10px; font-size:12px; }
.de-image-state { font-size:11.5px; color:var(--text-muted); }
.de-error { margin-top:6px; font-size:12px; color:#dc2626; }
.th-choices.is-six { grid-template-columns:repeat(3, 1fr); }
.th-editor-part { margin:22px 0 2px; font:600 11px/1 var(--font-mono, monospace); letter-spacing:.09em; text-transform:uppercase; color:var(--text-muted); }
.de-tabs { display:flex; margin:6px -22px 16px; border-bottom:1px solid var(--border-card); }
.de-tab { all:unset; box-sizing:border-box; flex:1; text-align:center; padding:13px 8px; font:600 11.5px/1 var(--font-mono, monospace); letter-spacing:.08em; text-transform:uppercase; color:var(--text-muted); cursor:pointer; border-bottom:2px solid transparent; }
.de-tab[aria-selected="true"] { color:var(--accent-blue, #2563eb); border-bottom-color:var(--accent-blue, #2563eb); }
.de-tab:focus-visible { outline:2px solid var(--accent-blue); outline-offset:-2px; }
.de-intro { margin:0 0 6px; font-size:12.5px; line-height:1.5; color:var(--text-muted); }
.de-sub-link { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.de-viewall { all:unset; cursor:pointer; font:600 11.5px/1 -apple-system, BlinkMacSystemFont, sans-serif; letter-spacing:0; text-transform:none; color:var(--accent-blue, #2563eb); }
.de-viewall:hover { text-decoration:underline; }
.de-viewall:focus-visible { outline:2px solid var(--accent-blue); }
@media (max-width: 900px) {
    .th-body { flex-direction:column; }
    .th-side { width:auto; max-height:46vh; border-right:0; border-bottom:1px solid var(--border-card); }
    .th-stage { padding:10px; min-height:0; }
    .th-devices, .th-top-title, .th-saved { display:none; }
}
@media (prefers-reduced-motion: reduce) { .th-card .mt-mock, .th-frame-wrap, .th-switch span::after { transition:none; } }
</style>

<section id="temalar">
    <?php if ($de_draft): $dt = $site_tpls[$de_draft['theme']]; ?>
    <div class="th-draft" role="status">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        <span class="th-draft-text"><b><?= e(__a('Yayınlanmamış değişiklikleriniz var', 'You have unpublished changes')) ?></b><?= e(sprintf(__a('“%s” teması · son düzenleme %s · ziyaretçiler henüz görmüyor', '“%s” theme · last edited %s · not visible to visitors yet'), __a($dt['tr'], $dt['en']), $de_draft['at'])) ?></span>
        <button type="button" class="btn btn-primary btn-sm" data-th-open="<?= e($de_draft['theme']) ?>" data-th-step="edit"><?= e(__a('Düzenlemeye devam et', 'Continue editing')) ?></button>
        <form method="POST" style="margin:0;" data-confirm="<?= e(__a('Yayınlanmamış değişiklikler silinsin mi? Bu geri alınamaz.', 'Discard the unpublished changes? This cannot be undone.')) ?>" data-confirm-ok="<?= e(__a('Değişiklikleri sil', 'Discard changes')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="design_draft_discard">
            <button type="submit" class="btn btn-secondary btn-sm"><?= e(__a('Değişiklikleri sil', 'Discard changes')) ?></button>
        </form>
    </div>
    <?php endif; ?>
    <div class="th-current">
        <div class="th-current-text"><?= e(__a('Kullandığınız tema', 'Your current theme')) ?><b><?= e(__a($th_active['tr'], $th_active['en'])) ?></b></div>
        <button type="button" class="btn btn-primary" data-th-open="<?= e($active_tpl_id) ?>" data-th-step="edit"><?= e(__a('Özelleştir', 'Customise')) ?></button>
    </div>

    <?php
    $th_families = ['all' => __a('Tümü', 'All'), 'grid' => __a('Izgara', 'Grid'), 'single' => __a('Tek fotoğraf', 'Single photo'), 'horizontal' => __a('Yatay', 'Horizontal'), 'story' => __a('Hikâye ve liste', 'Story and list')];
    $th_counts = array_count_values(array_map(fn($t) => $t['family'] ?? 'grid', $site_tpls));
    ?>
    <div class="th-filter" role="group" aria-label="<?= e(__a('Temaları süz', 'Filter themes')) ?>">
        <?php foreach ($th_families as $fk => $fl): $cnt = $fk === 'all' ? count($site_tpls) : ($th_counts[$fk] ?? 0); if (!$cnt) continue; ?>
        <button type="button" class="th-filter-btn" data-th-filter="<?= e($fk) ?>" aria-pressed="<?= $fk === 'all' ? 'true' : 'false' ?>"><?= e($fl) ?> <span><?= $cnt ?></span></button>
        <?php endforeach; ?>
    </div>
    <div class="th-grid">
        <?php foreach ($site_tpls as $tid => $t): $is_active = ($tid === $active_tpl_id); ?>
        <button type="button" class="th-card<?= $is_active ? ' is-active' : '' ?>" data-family="<?= e($t['family'] ?? 'grid') ?>" data-th-open="<?= e($tid) ?>" aria-label="<?= e(sprintf(__a('%s temasının ayrıntılarını aç', 'Open details of the %s theme'), __a($t['tr'], $t['en']))) ?>">
            <span class="mt-mock" data-sketch="<?= e($tid) ?>" style="display:block;"><span class="bar"><i></i><i></i><i></i></span><span class="th-card-hint"><?= e(__a('Ayrıntılar', 'Details')) ?></span></span>
            <span class="th-card-name"><?= e(__a($t['tr'], $t['en'])) ?><?php if ($is_active): ?><span class="mt-badge"><?= e(__a('Kullanımda', 'In use')) ?></span><?php endif; ?></span>
        </button>
        <?php endforeach; ?>
    </div>
</section>

<div class="th-modal" id="thModal" hidden role="dialog" aria-modal="true" aria-labelledby="thTitle">
    <div class="th-top">
        <div class="th-top-left">
            <button type="button" class="th-icon-btn" data-th-close aria-label="<?= e(__a('Kapat', 'Close')) ?>" title="<?= e(__a('Kapat', 'Close')) ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <span class="th-top-title" id="thTopTitle"></span>
        </div>
        <div class="th-devices" role="group" aria-label="<?= e(__a('Önizleme boyutu', 'Preview size')) ?>">
            <button type="button" class="th-icon-btn" data-th-device="desktop" aria-pressed="true" title="<?= e(__a('Masaüstü', 'Desktop')) ?>" aria-label="<?= e(__a('Masaüstü', 'Desktop')) ?>"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></button>
            <button type="button" class="th-icon-btn" data-th-device="phone" aria-pressed="false" title="<?= e(__a('Telefon', 'Phone')) ?>" aria-label="<?= e(__a('Telefon', 'Phone')) ?>"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2" width="12" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></svg></button>
            <button type="button" class="th-icon-btn" data-th-device="full" aria-pressed="false" title="<?= e(__a('Tam ekran', 'Fullscreen')) ?>" aria-label="<?= e(__a('Tam ekran', 'Fullscreen')) ?>"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg></button>
        </div>
        <div class="th-top-right">
            <span class="th-saved" id="thDirty" hidden><?= e(__a('Yayınlanmamış değişiklikler var', 'Unpublished changes')) ?></span>
            <button type="button" class="btn btn-secondary btn-sm" id="thReset" hidden><?= e(__a('Değişiklikleri temizle', 'Clear changes')) ?></button>
            <button type="button" class="btn btn-primary btn-sm" id="thPublish" hidden><?= e(__a('Yayınla', 'Publish')) ?></button>
        </div>
    </div>
    <div class="th-body">
        <aside class="th-side">
            <!-- 1. Tema bilgisi -->
            <div class="th-pane" data-pane="info">
                <h2 id="thTitle"></h2>
                <p class="th-pane-desc" id="thDesc"></p>
                <dl class="th-info" id="thInfo"></dl>
                <p class="th-sub"><?= e(__a('Zemin seçenekleri', 'Background options')) ?></p>
                <div class="th-swatches-row" id="thInfoSwatches"></div>
                <div class="th-actions">
                    <button type="button" class="btn btn-primary" data-th-go="edit"><?= e(__a('Özelleştir', 'Customise')) ?></button>
                    <a class="btn btn-secondary" id="thNewTab" href="#" target="_blank" rel="noopener"><?= e(__a('Önizlemeyi yeni sekmede aç', 'Open preview in a new tab')) ?></a>
                </div>
                <p class="th-pane-desc" style="margin-top:14px;font-size:12.5px;"><?= e(__a('Sağdaki önizleme sitenizin kendi fotoğraflarıyla, yalnızca size gösterilir. “Yayınla”ya basana kadar ziyaretçiler hiçbir değişiklik görmez.', 'The preview on the right uses your own photographs and is shown only to you. Visitors see no change until you press “Publish”.')) ?></p>
            </div>
            <!-- 2. Tasarım düzenleyici: bölümler -->
            <div class="th-pane" data-pane="edit" hidden>
                <p class="th-editor-label"><?= e(__a('Tasarım düzenleyici', 'Design editor')) ?></p>
                <button type="button" class="th-row" data-th-go="info">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="9" x2="9" y2="21"/></svg>
                    <span class="th-row-text"><span class="th-row-label"><?= e(__a('Tema', 'Theme')) ?></span><span class="th-row-desc" id="thEditTheme"></span></span>
                </button>
                <div id="thGroups"></div>
            </div>
            <!-- 3. Tek bölümün ayarları -->
            <div class="th-pane" data-pane="group" hidden>
                <button type="button" class="th-back" data-th-go="edit">← <?= e(__a('Tasarım düzenleyici', 'Design editor')) ?></button>
                <h2 id="thGroupTitle" style="font-size:19px;"></h2>
                <div id="thGroupFields"></div>
            </div>
        </aside>
        <div class="th-stage">
            <div class="th-frame-wrap">
                <iframe id="thFrame" title="<?= e(__a('Tema önizlemesi', 'Theme preview')) ?>" src="about:blank"></iframe>
                <div class="th-loading" id="thLoading"><?= e(__a('Önizleme yükleniyor…', 'Loading preview…')) ?></div>
            </div>
        </div>
    </div>
    <form method="POST" id="thApplyForm" hidden>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="design_publish">
    </form>
</div>

<script>
(function () {
    var THEMES = <?= json_encode($th_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var DEFS   = <?= json_encode($th_defs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var GROUPS = <?= json_encode($th_group_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var FIELDS = <?= json_encode($de_fields, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var SUBS   = <?= json_encode($de_subs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var DRAFT  = <?= json_encode($de_draft, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var CATS   = <?= json_encode($de_cats, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var SCOPED = <?= json_encode((object)$de_scoped, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var SCOPE_GROUPS = <?= json_encode(DESIGN_SCOPED_SECTIONS) ?>;
    var CSRF   = <?= json_encode(csrf_token()) ?>;
    var SITE   = <?= json_encode(url($admin_lang . '/')) ?>;
    var BASE   = <?= json_encode(url('')) ?>;
    var T = {
        publishScoped: <?= json_encode(__a('Değişiklikler yalnızca şu sayfalara uygulansın mı: %s? Sitenin geri kalanı değişmez. Önceki görünüme tek tıkla dönebilirsiniz.', 'Apply the changes only to: %s? The rest of the site stays the same. You can revert in one click.'), JSON_UNESCAPED_UNICODE) ?>,
        publishMixed: <?= json_encode(__a('Değişiklikler yayınlansın mı? Ortak ayarlar tüm sitede, sayfaya özel ayarlar yalnızca şu sayfalarda geçerli olur: %s. Önceki görünüme tek tıkla dönebilirsiniz.', 'Publish the changes? Shared settings apply site-wide; page settings apply only to: %s. You can revert in one click.'), JSON_UNESCAPED_UNICODE) ?>,
        publishConfirm: <?= json_encode(__a('“%s” teması bu ayarlarla tüm siteye uygulansın mı? Önceki görünüme tek tıkla dönebilirsiniz.', 'Publish the “%s” theme with these settings to the whole site? You can revert in one click.'), JSON_UNESCAPED_UNICODE) ?>,
        resetConfirm: <?= json_encode(__a('Yayınlanmamış tüm değişiklikler silinsin mi?', 'Discard all unpublished changes?'), JSON_UNESCAPED_UNICODE) ?>,
        active: <?= json_encode(__a('Kullanımda', 'In use'), JSON_UNESCAPED_UNICODE) ?>,
        back: <?= json_encode(__a('bilgilere dön', 'back to details'), JSON_UNESCAPED_UNICODE) ?>,
        auto: <?= json_encode(__a('Otomatik', 'Automatic'), JSON_UNESCAPED_UNICODE) ?>,
        upload: <?= json_encode(__a('Görsel yükle', 'Upload image'), JSON_UNESCAPED_UNICODE) ?>,
        uploading: <?= json_encode(__a('Yükleniyor…', 'Uploading…'), JSON_UNESCAPED_UNICODE) ?>,
        useDefault: <?= json_encode(__a('Varsayılana dön', 'Use default'), JSON_UNESCAPED_UNICODE) ?>,
        isDefault: <?= json_encode(__a('Varsayılan görsel', 'Default image'), JSON_UNESCAPED_UNICODE) ?>,
        isCustom: <?= json_encode(__a('Yüklediğiniz görsel', 'Your image'), JSON_UNESCAPED_UNICODE) ?>,
        help: <?= json_encode(__a('Açıklama', 'Help'), JSON_UNESCAPED_UNICODE) ?>,
        basic: <?= json_encode(__a('Temel', 'Basic'), JSON_UNESCAPED_UNICODE) ?>,
        partGeneral: <?= json_encode(__a('Genel', 'General'), JSON_UNESCAPED_UNICODE) ?>,
        partPages: <?= json_encode(__a('Sayfalar', 'Pages'), JSON_UNESCAPED_UNICODE) ?>,
        advanced: <?= json_encode(__a('Gelişmiş', 'Advanced'), JSON_UNESCAPED_UNICODE) ?>,
        viewAll: <?= json_encode(__a('Tümünü gör', 'View all'), JSON_UNESCAPED_UNICODE) ?>,
        advColor: <?= json_encode(__a('Sitedeki bütün renkler, bölüm bölüm. Buradaki bir renk değişikliği temel renklerin önüne geçer.', 'Every colour on your site, section by section. A colour set here overrides the basic colours.'), JSON_UNESCAPED_UNICODE) ?>,
        advType: <?= json_encode(__a('Sitedeki bütün yazı ayarları, bölüm bölüm.', 'Every text setting on your site, section by section.'), JSON_UNESCAPED_UNICODE) ?>,
        basicColor: <?= json_encode(__a('Sitenin ana renklerini tek yerden hızlıca değiştirin.', 'Change the main colours of your site quickly, in one place.'), JSON_UNESCAPED_UNICODE) ?>,
        basicType: <?= json_encode(__a('Sitedeki bütün yazı tiplerini tek yerden hızlıca değiştirin.', 'Change all the fonts on your site quickly, in one place.'), JSON_UNESCAPED_UNICODE) ?>,
        badUrl: <?= json_encode(__a('Geçerli bir bağlantı yazın: https://…, /sayfa/ ya da mailto:…', 'Enter a valid link: https://…, /page/ or mailto:…'), JSON_UNESCAPED_UNICODE) ?>,
        tooDark: <?= json_encode(__a('Bu ton çok koyu. Açık bir ton seçin ya da koyu zemin için “Siyah”ı kullanın.', 'This tone is too dark. Pick a light tone, or use “Black” for a dark site.'), JSON_UNESCAPED_UNICODE) ?>,
        sample: <?= json_encode((string)get_setting('header_logo_text', site_owner_name()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        scopeLabel: <?= json_encode(__a('Hangi sayfa', 'Which page'), JSON_UNESCAPED_UNICODE) ?>,
        scopeAllLists: <?= json_encode(__a('Tüm listeler (ortak ayar)', 'All lists (shared)'), JSON_UNESCAPED_UNICODE) ?>,
        scopeAllProject: <?= json_encode(__a('Tüm proje sayfaları (ortak ayar)', 'All project pages (shared)'), JSON_UNESCAPED_UNICODE) ?>,
        scopeOwn: <?= json_encode(__a('kendi ayarı', 'own settings'), JSON_UNESCAPED_UNICODE) ?>,
        scopeAllNote: <?= json_encode(__a('Burada yaptığınız ayar, kendi ayarı olmayan bütün sayfalara uygulanır.', 'Settings here apply to every page that has no settings of its own.'), JSON_UNESCAPED_UNICODE) ?>,
        scopeInherit: <?= json_encode(__a('Bu sayfa şu an ortak ayarları kullanıyor. Ona ayrı bir düzen ve ayarlar vermek için:', 'This page uses the shared settings. To give it its own layout and settings:'), JSON_UNESCAPED_UNICODE) ?>,
        scopeOn: <?= json_encode(__a('Bu sayfaya özel ayar yap', 'Give this page its own settings'), JSON_UNESCAPED_UNICODE) ?>,
        scopeOwnNote: <?= json_encode(__a('Bu sayfanın kendi ayarları var; ortak ayarlar değişse de etkilenmez.', 'This page has its own settings; changes to the shared settings do not affect it.'), JSON_UNESCAPED_UNICODE) ?>,
        scopeOff: <?= json_encode(__a('Ortak ayarlara dön', 'Use shared settings'), JSON_UNESCAPED_UNICODE) ?>,
        scopeAllInList: <?= json_encode(__a('%s: listedeki tüm projeler', '%s: every project in the list'), JSON_UNESCAPED_UNICODE) ?>,
        focusAll: <?= json_encode(__a('Tüm tasarım ayarlarını göster', 'Show all design settings'), JSON_UNESCAPED_UNICODE) ?>,
        focusAllDesc: <?= json_encode(__a('Renkler, yazı tipleri, menü ve diğer sayfalar', 'Colours, fonts, menu and other pages'), JSON_UNESCAPED_UNICODE) ?>,
        saveError: <?= json_encode(__a('Değişiklik kaydedilemedi. Bağlantınızı kontrol edip tekrar deneyin.', 'The change could not be saved. Check your connection and try again.'), JSON_UNESCAPED_UNICODE) ?>
    };
    var ICONS = {
        lists:   '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="7" height="7"/><rect x="14" y="4" width="7" height="7"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="3" y1="19" x2="15" y2="19"/></svg>',
        project: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="12"/><line x1="3" y1="19" x2="21" y2="19"/><line x1="3" y1="22" x2="13" y2="22"/></svg>',
        about:   '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>',
        contact: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 7l9 6 9-6"/></svg>',
        blog:    '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 3h10l4 4v14H5z"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="16" y2="15"/></svg>',
        color:  '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3a9 9 0 1 0 0 18c1 0 1.5-.8 1.5-1.6 0-.9-.6-1.3-.6-2.1 0-.9.7-1.6 1.6-1.6H17a4 4 0 0 0 4-4C21 6.6 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7.5" r="1"/></svg>',
        logo:   '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7V4h16v3"/><line x1="12" y1="4" x2="12" y2="20"/><line x1="9" y1="20" x2="15" y2="20"/></svg>',
        type:   '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M8 8h8M12 8v9"/></svg>',
        menu:   '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="14" y2="17"/></svg>',
        home:   '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="8" height="8"/><rect x="13" y="3" width="8" height="8"/><rect x="3" y="13" width="8" height="8"/><rect x="13" y="13" width="8" height="8"/></svg>',
        footer: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="16" x2="21" y2="16"/></svg>'
    };
    // Seçeneklerin küçük çizimleri: [sol, üst, genişlik, yükseklik, ek stil] (yüzde)
    function pic(parts) { return '<span class="th-pic">' + parts.map(function (p) { return '<i style="left:' + p[0] + '%;top:' + p[1] + '%;width:' + p[2] + '%;height:' + p[3] + '%;' + (p[4] || '') + '"></i>'; }).join('') + '</span>'; }
    // P: fotoğraf, M: soluk yazı satırı; G: ızgara hücreleri (sütun, satır, sol, üst, genişlik, yükseklik)
    var P = 'opacity:.3', M = 'opacity:.45';
    function G(c, r, x, y, w, h, st) {
        var out = [], cw = (w - 3 * (c - 1)) / c, rh = (h - 4 * (r - 1)) / r;
        for (var j = 0; j < r; j++) for (var i = 0; i < c; i++) out.push([x + i * (cw + 3), y + j * (rh + 4), cw, rh, st || P]);
        return out;
    }
    function lines(x, y, w, n) { var o = []; for (var i = 0; i < n; i++) o.push([x, y + i * 8, i === n - 1 ? w * .7 : w, 3, M]); return o; }
    var LIST = {
        cinema_fullbleed:    pic([[0,0,100,100,P],[8,68,30,8],[8,82,20,4,M]]),
        white_cube_gallery:  pic([[26,14,48,62,P],[40,84,20,4,M]]),
        monograph_split:     pic([[0,0,55,100,P],[62,40,28,7],[62,54,20,4,M]]),
        museum_passepartout: pic([[8,8,84,84,'opacity:.08'],[24,18,52,54,P],[40,80,20,4,M]]),
        editorial_magazine:  pic([[0,0,100,100,P],[10,18,60,16],[10,42,30,4,M]]),
        filmstrip_stage:     pic([[10,6,80,62,P]].concat(G(5,1,10,74,80,18))),
        vertical_chapters:   pic([[0,0,90,48,P],[6,32,24,7],[0,52,90,48,P],[94,20,2,60,M]]),
        story_stream:        pic([[25,4,50,40,P],[30,48,40,4,M],[25,56,50,40,P]]),
        horizontal_panorama: pic(G(4,1,0,18,128,64)),
        curatorial_grid:     pic([[8,6,40,34,P],[52,6,40,34,P],[8,43,20,4],[52,43,20,4],[8,54,40,34,P],[52,54,40,34,P],[8,91,20,4],[52,91,20,4]]),
        thumb_grid:          pic(G(5,3,8,10,84,80)),
        mosaic_wall:         pic([[8,8,40,52,P],[51,8,20,24,P],[74,8,18,24,P],[51,36,41,24,P],[8,64,25,28,P],[36,64,56,28,P]]),
        photo_wall:          pic([[8,10,20,34,P],[31,10,30,34,P],[64,10,28,34,P],[8,50,34,40,P],[45,50,22,40,P],[70,50,22,40,P]]),
        staggered_gallery:   pic([[8,6,26,40,P],[37,20,26,40,P],[66,6,26,40,P],[8,52,26,40,P],[37,66,26,30,P],[66,52,26,40,P]]),
        split_grid:          pic([[8,10,54,80,P],[65,10,27,38,P],[65,52,27,38,P]]),
        featured_grid:       pic([[8,8,84,50,P]].concat(G(4,1,8,62,84,28))),
        zigzag_stream:       pic([[8,6,50,26,P],[64,16,26,5],[42,36,50,26,P],[10,46,26,5],[8,66,50,26,P],[64,76,26,5]]),
        split_screens:       pic([[0,0,50,100,P],[50,0,50,100,'opacity:.1'],[58,42,30,7],[58,56,22,4,M]]),
        triptych:            pic(G(3,1,6,12,88,76)),
        section_banners:     pic([[8,6,84,26,P],[14,16,24,6],[8,36,84,26,P],[14,46,24,6],[8,66,84,26,P],[14,76,24,6]]),
        light_table:         pic([[0,0,100,100,'opacity:.06']].concat(G(4,2,12,16,76,68))),
        framed_masonry:      pic([[8,8,26,40,P],[37,8,26,26,P],[66,8,26,46,P],[8,52,26,40,P],[37,38,26,54,P],[66,58,26,34,P]].map(function (r) { r[4] = 'opacity:.3;outline:2px solid #fff;outline-offset:-4px'; return r; })),
        minimal_index:       pic([[8,14,50,6],[80,15,12,4,M],[8,30,40,6],[80,31,12,4,M],[8,46,56,6],[80,47,12,4,M],[8,62,34,6],[80,63,12,4,M],[8,78,46,6],[80,79,12,4,M]]),
        year_archive:        pic([[8,8,16,7],[8,20,84,1,M]].concat(G(4,1,8,26,84,22),[[8,56,16,7],[8,68,84,1,M]],G(4,1,8,74,84,20))),
        sidebar_list:        pic([[8,12,22,5],[8,24,18,4,M],[8,34,20,4,M],[8,44,16,4,M],[8,54,19,4,M],[36,8,56,84,P]]),
        contact_sheet:       pic(G(6,4,6,8,88,84,'opacity:.5')),
        photo_statement:     pic([[8,10,40,80,P],[56,28,36,8],[56,42,30,8],[56,56,24,8]]),
        sectioned_showcase:  pic([[8,6,30,5]].concat(G(3,1,8,15,84,28),[[8,50,30,5]],G(3,1,8,59,84,28))),
        book_spreads:        pic([[8,16,42,64,P],[50,16,42,64,P],[49.5,16,1,64,M]]),
        free_collage:        pic([[10,12,30,40,P + ';transform:rotate(-6deg)'],[44,8,24,32,P + ';transform:rotate(4deg)'],[66,30,26,40,P + ';transform:rotate(-3deg)'],[20,56,34,32,P + ';transform:rotate(3deg)']]),
        project_map:         pic([[0,0,100,100,'opacity:.1'],[30,30,5,10,'border-radius:50%'],[60,50,5,10,'border-radius:50%'],[44,72,5,10,'border-radius:50%'],[74,22,5,10,'border-radius:50%']])
    };
    var BG = [0,0,100,100,'opacity:.25'];
    var PICS = {
        logo_type: {
            text_only:   pic([[10,30,46,14],[10,54,30,7,'opacity:.45']]),
            symbol_text: pic([[10,28,14,30,'border-radius:2px'],[28,34,40,12]]),
            full:        pic([[10,26,54,32,'border-radius:2px']]),
            full_text:   pic([[10,22,40,24,'border-radius:2px'],[10,56,46,9,'opacity:.6']]),
            symbol:      pic([[10,24,16,38,'border-radius:2px']]),
            none:        pic([[10,44,80,1,'opacity:.25']])
        },
        menu_style: {
            horizontal:        pic([[8,22,22,10],[54,25,9,5],[66,25,9,5],[78,25,9,5],[8,46,84,40,'opacity:.12']]),
            horizontal_center: pic([[39,14,22,10],[24,36,10,5],[38,36,10,5],[52,36,10,5],[66,36,10,5],[8,52,84,36,'opacity:.12']]),
            horizontal_left:   pic([[8,22,22,10],[36,25,9,5],[48,25,9,5],[60,25,9,5],[8,46,84,40,'opacity:.12']]),
            stacked_center:    pic([[39,12,22,9],[28,30,9,4],[40,30,9,4],[52,30,9,4],[64,30,9,4],[8,44,84,44,'opacity:.12']]),
            hamburger:         pic([[8,22,22,10],[80,20,10,3],[80,26,10,3],[80,32,10,3],[8,46,84,40,'opacity:.12']]),
            vertical_left:     pic([[8,14,20,8],[8,34,14,5],[8,46,14,5],[8,58,14,5],[34,12,58,76,'opacity:.12']])
        },
        listing: LIST,
        content_position: {
            bottom_left:    pic([BG,[8,68,30,8],[8,82,20,4,M]]),
            bottom_center:  pic([BG,[35,68,30,8],[40,82,20,4,M]]),
            center_overlay: pic([BG,[35,40,30,8],[40,54,20,4,M]]),
            top_left:       pic([BG,[8,12,30,8],[8,26,20,4,M]]),
            bottom_split:   pic([BG,[8,76,30,8],[70,78,22,4,M]])
        },
        inner_opening: {
            spread_right:      pic([[8,16,34,8]].concat(lines(8,32,32,4),[[48,10,44,80,P]])),
            spread_left:       pic([[8,10,44,80,P],[58,16,34,8]].concat(lines(58,32,32,4))),
            title_photo_text:  pic([[8,6,50,8],[8,20,84,48,P]].concat(lines(8,74,70,2))),
            editorial_stacked: pic([[8,6,84,40,P],[8,52,60,10]].concat(lines(8,68,84,3))),
            sticky_split:      pic([[0,0,50,100,P],[56,12,34,8]].concat(lines(56,28,34,6))),
            cinema_hero:       pic([[0,0,100,100,P],[30,40,40,10],[36,56,28,4,M]]),
            centered_museum:   pic([[30,6,40,50,P],[35,62,30,7]].concat(lines(30,76,40,2))),
            photo_first:       pic([[0,0,100,62,P],[8,70,40,8]].concat(lines(8,84,70,1))),
            framed_box:        pic([[8,8,84,84,'opacity:.08'],[16,16,40,68,P],[62,28,24,7]].concat(lines(62,42,24,3))),
            white_cube:        pic([[22,12,56,60,P],[40,80,20,4,M]]),
            story_header:      pic([[8,8,60,12],[8,26,40,4,M],[8,38,84,54,P]])
        },
        inner_stream: {
            editorial_single:    pic([[15,6,70,40,P],[15,52,70,40,P]]),
            captioned_sequence:  pic([[15,4,70,34,P],[15,41,30,4,M],[15,52,70,34,P],[15,89,30,4,M]]),
            grid_2col:           pic(G(2,2,8,8,84,84)),
            grid_3col:           pic(G(3,3,8,8,84,84)),
            alternating_rhythm:  pic([[8,6,52,26,P],[40,37,52,26,P],[8,68,52,26,P]]),
            magazine_offset:     pic([[8,6,58,42,P],[48,30,44,34,'opacity:.45'],[8,68,40,26,P]]),
            horizontal_panorama: LIST.horizontal_panorama,
            framed_masonry:      LIST.framed_masonry,
            white_cube_gallery:  LIST.white_cube_gallery,
            story_stream:        LIST.story_stream,
            photobook_spread:    LIST.book_spreads,
            essay_rhythm:        pic([[8,6,84,34,P],[8,44,26,18,P],[37,44,26,18,P],[66,44,26,18,P],[8,66,84,30,P]])
        },
        about_template: {
            monograph_classic:     pic([[8,10,50,8]].concat(lines(8,26,50,4),[[64,10,28,50,P]])),
            cinema_hero:           pic([[0,0,100,60,P],[8,66,40,8]].concat(lines(8,80,70,2))),
            sticky_split:          pic([[0,0,46,100,P],[52,12,36,8]].concat(lines(52,28,40,6))),
            magazine_profile:      pic([[8,8,40,84,P],[54,10,38,14]].concat(lines(54,32,38,3))),
            museum_curatorial:     pic([[30,6,40,44,P],[30,56,40,1,M],[30,62,22,5]].concat(lines(30,72,40,2))),
            minimalist_typography: pic([[8,12,70,12]].concat(lines(8,34,84,4))),
            modular_bento:         pic([[8,8,40,50,P],[51,8,41,24,'opacity:.15'],[51,35,41,23,'opacity:.15'],[8,62,26,30,'opacity:.15'],[37,62,55,30,'opacity:.15']]),
            press_dossier:         pic([[8,8,30,7],[8,20,84,1,M]].concat(lines(8,28,38,3),lines(54,28,38,3),[[8,56,84,1,M]],lines(8,64,38,3),lines(54,64,38,3))),
            format_portrait_title: pic([[8,10,40,58,P],[54,12,38,10]].concat(lines(54,30,38,5),[[8,76,84,1,M]],lines(8,82,26,2),lines(37,82,26,2),lines(66,82,26,2))),
            format_landscape:      pic([[0,0,100,40,P],[32,48,36,8]].concat(lines(22,62,56,3),[[8,80,26,14,P],[37,80,26,14,P],[66,80,26,14,P]])),
            format_centered:       pic([[34,6,32,7],[28,18,44,34,P]].concat(lines(28,58,44,6))),
            format_labels:         pic([[8,10,20,5]].concat(lines(36,10,56,3),[[8,40,20,5]],lines(36,40,56,3),[[8,70,20,5]],lines(36,70,56,3)))
        },
        contact_layout: {
            split_editorial:   pic([[8,8,50,8],[8,22,84,1,M]].concat(lines(8,30,26,4),[[40,30,52,8,'opacity:.2'],[40,42,52,8,'opacity:.2'],[40,54,52,26,'opacity:.2'],[40,84,18,6]])),
            centered_minimal:  pic([[25,8,50,8]].concat(lines(25,22,50,2),[[25,36,50,8,'opacity:.2'],[25,48,50,26,'opacity:.2'],[25,80,18,6]])),
            dossier_grid:      pic([[8,8,50,8],[8,24,26,14,'opacity:.2'],[37,24,26,14,'opacity:.2'],[66,24,26,14,'opacity:.2'],[8,44,84,8,'opacity:.2'],[8,56,84,24,'opacity:.2'],[8,84,18,6]]),
            format_info_form:  pic([[8,8,40,12]].concat(lines(8,30,24,4),[[40,30,52,8,'opacity:.2'],[40,42,52,8,'opacity:.2'],[40,54,52,26,'opacity:.2'],[40,84,18,6]])),
            format_image_form: pic([[34,6,32,8],[8,22,40,70,P],[54,22,38,8,'opacity:.2'],[54,34,38,8,'opacity:.2'],[54,46,38,34,'opacity:.2'],[54,84,16,6]]),
            format_hero_form:  pic([[8,6,84,30,P],[34,42,32,7]].concat(lines(20,54,60,2),[[20,64,60,8,'opacity:.2'],[20,74,60,14,'opacity:.2'],[20,91,14,5]])),
            format_quote_form: pic([[34,6,32,8]].concat(lines(20,20,60,2),[[26,34,48,4],[30,41,40,4]],[[20,54,60,8,'opacity:.2'],[20,64,60,22,'opacity:.2'],[20,90,14,5]]))
        },
        about_portrait: {
            right:    pic([[8,14,40,8]].concat(lines(8,30,46,6),[[62,12,30,76,P]])),
            left:     pic([[8,12,30,76,P],[46,14,40,8]].concat(lines(46,30,46,6))),
            top_hero: pic([[0,0,100,44,P],[8,52,40,8]].concat(lines(8,66,84,3))),
            floating: pic([[8,10,40,8],[60,14,32,40,P]].concat(lines(8,26,46,3),lines(8,58,84,4))),
            none:     pic([[8,14,40,8]].concat(lines(8,30,84,7)))
        },
        blog_template: {
            editorial_grid:       pic([[8,14,26,30,'opacity:.3'],[37,14,26,30,'opacity:.3'],[66,14,26,30,'opacity:.3'],[8,48,20,5],[37,48,20,5],[66,48,20,5],[8,60,26,30,'opacity:.3'],[37,60,26,30,'opacity:.3'],[66,60,26,30,'opacity:.3']]),
            magazine_featured:    pic([[8,12,84,40,'opacity:.3'],[8,56,40,6],[8,72,26,18,'opacity:.3'],[37,72,26,18,'opacity:.3'],[66,72,26,18,'opacity:.3']]),
            minimal_journal:      pic([[8,14,50,6],[8,24,22,3,'opacity:.45'],[8,38,60,6],[8,48,22,3,'opacity:.45'],[8,62,44,6],[8,72,22,3,'opacity:.45'],[8,84,56,6]]),
            chronological_stream: pic([[12,10,1,82,'opacity:.35'],[10,14,5,6,'border-radius:50%'],[22,12,70,20,'opacity:.3'],[22,34,40,5],[10,52,5,6,'border-radius:50%'],[22,50,70,20,'opacity:.3'],[22,72,40,5]]),
            split_showcase:       pic([[8,12,38,34,'opacity:.3'],[52,18,36,6],[52,30,28,4,'opacity:.45'],[8,54,38,34,'opacity:.3'],[52,60,36,6],[52,72,28,4,'opacity:.45']]),
            bento_news:           pic([[8,12,55,44,'opacity:.3'],[66,12,26,20,'opacity:.3'],[66,36,26,20,'opacity:.3'],[8,60,26,28,'opacity:.3'],[37,60,26,28,'opacity:.3'],[66,60,26,28,'opacity:.3']])
        }
    };

    var modal = document.getElementById('thModal'), frame = document.getElementById('thFrame'), loading = document.getElementById('thLoading');
    var state = { id: null, opts: {}, values: {}, initOpts: {}, initValues: {}, pane: 'info', group: null, scope: '', timer: null, seq: 0, saving: null, lastFocus: null };

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function clone(o) { return JSON.parse(JSON.stringify(o)); }
    function assign(a, b) { Object.keys(b || {}).forEach(function (k) { if (k in a) a[k] = String(b[k]); }); return a; }
    function dirty() { return JSON.stringify(state.opts) !== JSON.stringify(state.initOpts) || JSON.stringify(state.values) !== JSON.stringify(state.initValues); }
    // Kategoriye özel ayar: seçili sayfa varken liste/proje alanları "alan@kategori" anahtarına yazılır
    function vk(k) { return (state.scope && SCOPE_GROUPS.indexOf(state.group) >= 0 && FIELDS[k] && FIELDS[k].section === state.group) ? k + '@' + state.scope : k; }
    // Proje kapsamı ("item-12") → bağlı olduğu liste; liste kapsamı → null
    function scopeInfo(k) {
        for (var i = 0; i < CATS.length; i++) {
            if (CATS[i].k === k) return { cat: CATS[i], item: null };
            var it = (CATS[i].items || []).filter(function (x) { return x.k === k; })[0];
            if (it) return { cat: CATS[i], item: it };
        }
        return null;
    }
    function hasOwn(g, cat) { return Object.keys(FIELDS).some(function (k) { return FIELDS[k].section === g && (state.values[k + '@' + cat] || '') !== ''; }); }
    // Geçerli değer: proje → liste → ortak ayar (sitedeki get_setting sırası)
    function current(key) {
        var sk = vk(key);
        if (sk !== key) {
            if ((state.values[sk] || '') !== '') return state.values[sk];
            var si = scopeInfo(state.scope);
            if (si && si.item && (state.values[key + '@' + si.cat.k] || '') !== '') return state.values[key + '@' + si.cat.k];
        }
        return (key in state.values) ? state.values[key] : state.opts[key];
    }

    function updateTop() {
        var t = THEMES[state.id], d = dirty();
        // Etkin olmayan temada “Yayınla” her zaman; etkin temada yalnızca değişiklik varsa
        document.getElementById('thPublish').hidden = t.active ? !d : false;
        document.getElementById('thReset').hidden = !d;
        document.getElementById('thDirty').hidden = !d;
    }

    function showPane(name) {
        state.pane = name;
        modal.querySelectorAll('.th-pane').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== name; });
        updateTop();
        modal.querySelector('.th-side').scrollTop = 0;
    }

    function sectionHas(t, g) {
        if (t.options.some(function (o) { return DEFS[o].group === g; })) return true;
        return Object.keys(FIELDS).some(function (k) { return FIELDS[k].section === g; });
    }

    function renderInfo() {
        var t = THEMES[state.id];
        document.getElementById('thTitle').textContent = t.name;
        document.getElementById('thTopTitle').textContent = t.name + (t.active ? ' · ' + T.active : '');
        document.getElementById('thDesc').textContent = t.desc;
        document.getElementById('thInfo').innerHTML = t.info.map(function (r) { return '<dt>' + esc(r[0]) + '</dt><dd>' + esc(r[1]) + '</dd>'; }).join('');
        document.getElementById('thInfoSwatches').innerHTML = (DEFS.surface ? DEFS.surface.choices : []).map(function (c) { return '<span class="th-swatch-dot"><i style="background:' + esc(c.swatch) + '"></i>' + esc(c.label) + '</span>'; }).join('');
        document.getElementById('thEditTheme').textContent = t.name + ' · ' + T.back;
        var rows = '', lastPart = null;
        Object.keys(GROUPS).forEach(function (g) {
            // Sayfalar ekranından bir sayfa için açıldıysa yalnızca o sayfanın bölümü listelenir
            if (!sectionHas(t, g) || (state.focus && g !== state.focus)) return;
            if (GROUPS[g].part !== lastPart) { lastPart = GROUPS[g].part; rows += '<p class="th-editor-part">' + esc(lastPart === 'pages' ? T.partPages : T.partGeneral) + '</p>'; }
            rows += '<button type="button" class="th-row" data-th-group="' + g + '">' + (ICONS[g] || '') + '<span class="th-row-text"><span class="th-row-label">' + esc(GROUPS[g].label) + '</span><span class="th-row-desc">' + esc(GROUPS[g].desc) + '</span></span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>';
        });
        if (state.focus) rows += '<button type="button" class="th-row" data-th-unfocus>' + ICONS.home + '<span class="th-row-text"><span class="th-row-label">' + esc(T.focusAll) + '</span><span class="th-row-desc">' + esc(T.focusAllDesc) + '</span></span></button>';
        document.getElementById('thGroups').innerHTML = rows;
    }

    // ── Alan çizimleri ──
    function helpParts(id, text) {
        if (!text) return { btn: '', box: '' };
        return {
            btn: '<button type="button" class="de-help-btn" aria-expanded="false" aria-controls="' + id + '-help" aria-label="' + esc(T.help) + '">?</button>',
            box: '<p class="de-help" id="' + id + '-help" hidden>' + esc(text) + '</p>'
        };
    }
    function choiceGrid(name, choices, v, attr, picsKey, isSwatch) {
        var many = choices.length > 4 ? ' is-six' : '';
        return '<div class="th-choices' + (isSwatch ? ' is-swatch' : many) + '">' + choices.map(function (c) {
            var art = isSwatch ? '<span class="th-swatch" style="background:' + esc(c.swatch) + '"></span>' : ((PICS[picsKey] && PICS[picsKey][c.v]) || '');
            return '<label class="th-choice"><input type="radio" name="' + name + '" value="' + esc(c.v) + '" ' + attr + (c.v === v ? ' checked' : '') + '><span class="th-choice-box">' + art + esc(c.label) + '</span></label>';
        }).join('') + '</div>';
    }
    function segGroup(name, choices, v, attr) {
        return '<div class="th-seg">' + choices.map(function (c) {
            return '<label><input type="radio" name="' + name + '" value="' + esc(c.v) + '" ' + attr + (c.v === v ? ' checked' : '') + '><span>' + esc(c.label) + '</span></label>';
        }).join('') + '</div>';
    }

    // Temanın kendi seçeneği (zemin, menü, ana sayfa…)
    function optionHtml(o) {
        var d = DEFS[o], v = state.opts[o], name = 'th_' + o, attr = 'data-opt="' + o + '"';
        if (d.type === 'toggle') {
            return '<div class="de-row de-inline"><label class="de-head" for="' + name + '">' + esc(d.label) + '</label><span class="th-switch"><input type="checkbox" id="' + name + '" ' + attr + (v === '1' ? ' checked' : '') + '><span></span></span></div>';
        }
        var body = (d.ui === 'swatch' || d.ui === 'visual') ? choiceGrid(name, d.choices, v, attr, o, d.ui === 'swatch') : segGroup(name, d.choices, v, attr);
        return '<div class="de-row" role="radiogroup" aria-label="' + esc(d.label) + '"><p class="de-head">' + esc(d.label) + '</p>' + body + '</div>';
    }

    // Düzenleyici alanı (logo, yazı tipi, renk…)
    function fieldHtml(k) {
        var f = FIELDS[k], v = current(k), id = 'de_' + k, attr = 'data-field="' + k + '"', h = helpParts(id, f.help);
        var head = '<span class="de-head"><label for="' + id + '">' + esc(f.label) + '</label>' + h.btn + '</span>';
        var row = function (inner) { return '<div class="de-row" data-row="' + k + '">' + inner + '</div>'; };
        switch (f.type) {
            case 'toggle':
                return row('<div class="de-inline">' + head + '<span class="th-switch"><input type="checkbox" id="' + id + '" ' + attr + (v === '1' ? ' checked' : '') + '><span></span></span></div>' + h.box);
            case 'text':
            case 'url':
                return row(head + h.box + '<input type="text" class="de-input" id="' + id + '" maxlength="' + f.max + '" value="' + esc(v) + '" ' + attr + (f.type === 'url' ? ' inputmode="url" spellcheck="false" placeholder="https://"' : '') + '>' + (f.type === 'url' ? '<div class="de-error" data-error="' + k + '" hidden></div>' : ''));
            case 'choice':
                if (f.ui === 'select') {
                    return row(head + h.box + '<select class="de-input" id="' + id + '" ' + attr + '>' + f.choices.map(function (c) { return '<option value="' + esc(c.v) + '"' + (c.v === v ? ' selected' : '') + '>' + esc(c.label) + '</option>'; }).join('') + '</select>');
                }
                var hd = '<p class="de-head">' + esc(f.label) + h.btn + '</p>';
                var body = f.ui === 'visual' ? choiceGrid(id, f.choices, v, attr, f.pics, false) : segGroup(id, f.choices, v, attr);
                return row('<div role="radiogroup" aria-label="' + esc(f.label) + '">' + hd + h.box + body + '</div>');
            case 'slider':
                return row(head + h.box + '<div class="de-slider"><input type="range" id="' + id + '" min="' + f.min + '" max="' + f.max + '" step="' + f.step + '" value="' + esc(v) + '" ' + attr + '><output for="' + id + '">' + esc(v) + f.unit + '</output></div>');
            case 'color':
                if (f.noAuto) return row('<div class="de-inline">' + head + '<span class="de-color"><input type="color" id="' + id + '" value="' + esc(v || '#000000') + '" ' + attr + '></span></div>' + h.box);
                var auto = v === '';
                return row('<div class="de-inline">' + head + '<span class="de-color' + (auto ? ' is-auto' : '') + '"><input type="color" id="' + id + '" value="' + esc(auto ? '#111111' : v) + '" ' + attr + '><button type="button" class="de-auto" data-auto="' + k + '" aria-pressed="' + (auto ? 'true' : 'false') + '">' + esc(f.auto || T.auto) + '</button></span></div>' + h.box + '<div class="de-error" data-error="' + k + '" hidden></div>');
            case 'font':
                var stack = (f.choices.filter(function (c) { return c.v === v; })[0] || f.choices[0]).stack;
                return row(head + h.box + '<select class="de-input" id="' + id + '" ' + attr + '>' + f.choices.map(function (c) { return '<option value="' + esc(c.v) + '"' + (c.v === v ? ' selected' : '') + '>' + esc(c.label) + '</option>'; }).join('') + '</select><div class="de-font-sample" data-sample="' + k + '" style="font-family:' + esc(stack) + '">' + esc(T.sample) + '</div>');
            case 'image':
                var src = v ? BASE + v : f.fallback, custom = !!v && src !== f.fallback;
                return row('<p class="de-head">' + esc(f.label) + h.btn + '</p>' + h.box + '<div class="de-image"><span class="de-image-prev" style="background:' + esc(f.bg) + '"><img src="' + esc(src) + '" alt=""></span><span class="de-image-actions"><span class="de-image-state">' + esc(custom ? T.isCustom : T.isDefault) + '</span><label class="btn btn-secondary btn-sm">' + esc(T.upload) + '<input type="file" accept=".svg,.png,.webp,.jpg,.jpeg,.gif,.ico" hidden data-upload="' + k + '"></label>' + (custom ? '<button type="button" class="btn btn-secondary btn-sm" data-clear="' + k + '">' + esc(T.useDefault) + '</button>' : '') + '</span></div><div class="de-error" data-error="' + k + '" hidden></div>');
        }
        return '';
    }

    function sectionBody(g, opts) {
        var html = '', bySub = {};
        Object.keys(FIELDS).forEach(function (k) { if (FIELDS[k].section === g) (bySub[FIELDS[k].sub] = bySub[FIELDS[k].sub] || []).push(k); });
        // Temanın kendi seçenekleri “Temel” varsa oraya, yoksa “Seçenekler”e yerleşir
        var optSub = bySub.basic ? 'basic' : 'options';
        var subs = Object.keys(SUBS).filter(function (s) { return bySub[s] || (s === optSub && opts.length); });
        var headings = subs.length > 1;
        subs.forEach(function (s) {
            if (headings) html += '<p class="de-sub">' + esc(SUBS[s]) + '</p>';
            if (s === optSub) html += opts.map(optionHtml).join('');
            html += (bySub[s] || []).map(fieldHtml).join('');
        });
        if (!subs.length) html = opts.map(optionHtml).join('');
        return html;
    }

    // Gelişmiş sekmesi: diğer bölümlerdeki bütün renkler (ya da yazı ayarları), bölüm bölüm
    function collectBody(kind) {
        var html = '';
        Object.keys(GROUPS).forEach(function (g) {
            if (GROUPS[g].collect) return;
            var keys = Object.keys(FIELDS).filter(function (k) { var f = FIELDS[k]; return f.section === g && (kind === 'color' ? f.type === 'color' : f.sub === 'type'); });
            if (!keys.length) return;
            html += '<p class="de-sub de-sub-link"><span>' + esc(GROUPS[g].label) + '</span><button type="button" class="de-viewall" data-th-group="' + g + '">' + esc(T.viewAll) + ' ›</button></p>';
            html += keys.map(fieldHtml).join('');
        });
        return html;
    }

    function renderGroup(g, tab) {
        state.group = g;
        document.getElementById('thGroupTitle').textContent = GROUPS[g].label;
        var t = THEMES[state.id], html = '';
        var opts = t.options.filter(function (o) { return DEFS[o].group === g; });
        var kind = GROUPS[g].collect;
        if (kind) {
            if (tab) state.tab = tab;
            var adv = state.tab === 'advanced';
            html += '<div class="de-tabs" role="tablist">'
                + '<button type="button" role="tab" class="de-tab" data-de-tab="basic" aria-selected="' + (!adv) + '">' + esc(T.basic) + '</button>'
                + '<button type="button" role="tab" class="de-tab" data-de-tab="advanced" aria-selected="' + adv + '">' + esc(T.advanced) + '</button></div>';
            html += '<p class="de-intro">' + esc(adv ? (kind === 'color' ? T.advColor : T.advType) : (kind === 'color' ? T.basicColor : T.basicType)) + '</p>';
            html += adv ? collectBody(kind) : sectionBody(g, opts);
        } else if (SCOPE_GROUPS.indexOf(g) >= 0 && CATS.length) {
            html += '<div class="de-row"><label class="de-head" for="de_scope">' + esc(T.scopeLabel) + '</label><select class="de-input" id="de_scope" data-scope>'
                + '<option value="">' + esc(g === 'lists' ? T.scopeAllLists : T.scopeAllProject) + '</option>'
                + CATS.map(function (c) {
                    var opt = function (k, label) { return '<option value="' + esc(k) + '"' + (k === state.scope ? ' selected' : '') + '>' + esc(label + (hasOwn(g, k) ? ' · ' + T.scopeOwn : '')) + '</option>'; };
                    // Proje sayfası: önce listenin tüm projeleri, sonra tek tek projeler
                    if (g !== 'project' || !(c.items || []).length) return opt(c.k, c.label);
                    return '<optgroup label="' + esc(c.label) + '">' + opt(c.k, T.scopeAllInList.replace('%s', c.label)) + c.items.map(function (it) { return opt(it.k, it.label); }).join('') + '</optgroup>';
                }).join('')
                + '</select></div>';
            if (!state.scope) html += '<p class="de-intro">' + esc(T.scopeAllNote) + '</p>' + sectionBody(g, opts);
            else if (!hasOwn(g, state.scope)) html += '<p class="de-intro">' + esc(T.scopeInherit) + '</p><button type="button" class="btn btn-primary btn-sm" data-scope-on>' + esc(T.scopeOn) + '</button>';
            else html += '<p class="de-intro">' + esc(T.scopeOwnNote) + ' <button type="button" class="de-viewall" data-scope-off>' + esc(T.scopeOff) + '</button></p>' + sectionBody(g, opts);
        } else {
            html = sectionBody(g, opts);
        }
        document.getElementById('thGroupFields').innerHTML = html;
        applyShowIf();
    }

    // Bir alan yalnızca koşulları sağlanınca görünür (ör. “Yalnızca simge” seçiliyken yazı tipi gizlenir)
    function applyShowIf() {
        var box = document.getElementById('thGroupFields');
        box.querySelectorAll('[data-row]').forEach(function (r) {
            var cond = FIELDS[r.getAttribute('data-row')].show_if || {};
            r.hidden = !Object.keys(cond).every(function (ck) { return cond[ck].indexOf(current(ck)) >= 0; });
        });
        // Bir alt başlığın altında görünen alan kalmadıysa başlığı da gizle
        box.querySelectorAll('.de-sub').forEach(function (h) {
            var n = h.nextElementSibling, any = false;
            while (n && !n.classList.contains('de-sub')) { if (!n.hidden) any = true; n = n.nextElementSibling; }
            h.hidden = !any;
        });
    }

    // ── Taslak: kaydet ve önizlemeyi yenile ──
    function saveDraft() {
        var fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('action', 'design_draft_save');
        fd.append('theme', state.id);
        fd.append('opts', JSON.stringify(state.opts));
        fd.append('values', JSON.stringify(state.values));
        state.saving = fetch('templates.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
            if (!r.ok) throw new Error('save');
            return r.json();
        });
        return state.saving;
    }

    function framePath() {
        try {
            var l = frame.contentWindow.location;
            if (l && l.origin === window.location.origin && l.pathname.indexOf('/admin/') < 0) return l.pathname;
        } catch (e) {}
        return null;
    }

    function refresh() {
        var seq = ++state.seq;
        loading.classList.add('is-on');
        return saveDraft().then(function () {
            if (seq !== state.seq) return;
            var path = framePath();
            var base = path ? window.location.origin + path : (state.start || SITE);
            // Hakkımda/İletişim/Blog: sayfa kapalıyken yönlendirilmiş olabilir; açılınca kendi sayfasına dönsün
            var gu = ['about', 'contact', 'blog'].indexOf(state.group) >= 0 && GROUPS[state.group] ? GROUPS[state.group].url : '';
            if (gu) base = gu;
            state.start = '';
            frame.src = base + (base.indexOf('?') < 0 ? '?' : '&') + '_tp=' + Date.now();
        }).catch(function () {
            loading.classList.remove('is-on');
            alert(T.saveError);
        });
    }
    frame.addEventListener('load', function () { if (frame.src !== 'about:blank') loading.classList.remove('is-on'); });
    function scheduleRefresh(ms) { clearTimeout(state.timer); state.timer = setTimeout(refresh, ms || 300); }

    function setDevice(dv) {
        modal.classList.toggle('is-phone', dv === 'phone');
        modal.classList.toggle('is-full', dv === 'full');
        modal.querySelectorAll('[data-th-device]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-th-device') === dv ? 'true' : 'false'); });
    }

    function open(id, step) {
        if (!THEMES[id]) return;
        var t = THEMES[id];
        state.lastFocus = document.activeElement;
        state.id = id;
        state.initOpts = clone(t.values);
        state.initValues = clone(t.fvalues);
        state.opts = clone(t.values);
        state.values = clone(t.fvalues);
        // Bu temanın yayınlanmamış taslağı varsa oradan devam et
        Object.assign(state.initValues, clone(SCOPED)); Object.assign(state.values, clone(SCOPED));
        state.scope = ''; state.focus = '';
        if (DRAFT && DRAFT.theme === id) {
            assign(state.opts, DRAFT.opts); assign(state.values, DRAFT.values);
            Object.keys(DRAFT.values).forEach(function (k) { if (k.indexOf('@') > 0) state.values[k] = String(DRAFT.values[k]); });
        }
        renderInfo();
        document.getElementById('thNewTab').href = 'templates.php?preview_draft=1';
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        setDevice('desktop');
        frame.src = 'about:blank';
        showPane(step === 'edit' ? 'edit' : 'info');
        refresh();
        setTimeout(function () { var b = modal.querySelector('[data-th-close]'); if (b) b.focus(); }, 30);
    }

    function discard() {
        var fd = new FormData();
        fd.append('csrf_token', CSRF); fd.append('action', 'design_draft_discard'); fd.append('ajax', '1');
        return fetch('templates.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    }

    function close() {
        clearTimeout(state.timer);
        state.seq++;
        var wasDirty = dirty();
        modal.hidden = true;
        document.body.style.overflow = '';
        frame.src = 'about:blank';
        // Değişiklik yoksa taslağı sil; varsa sakla ve sayfada “devam et” uyarısını göster
        var wait = state.saving || Promise.resolve();
        wait.catch(function () {}).then(function () {
            return wasDirty ? fetch('templates.php?preview_end=1&ajax=1', { credentials: 'same-origin' }) : discard();
        }).then(function () { if (wasDirty || DRAFT) location.reload(); });
        if (state.lastFocus && state.lastFocus.focus) state.lastFocus.focus();
    }

    document.addEventListener('click', function (e) {
        var o = e.target.closest('[data-th-open]');
        if (o) { open(o.getAttribute('data-th-open'), o.getAttribute('data-th-step')); return; }
        if (modal.hidden) return;
        if (e.target.closest('[data-th-close]')) { close(); return; }
        var go = e.target.closest('[data-th-go]');
        if (go) { showPane(go.getAttribute('data-th-go')); return; }
        if (e.target.closest('[data-th-unfocus]')) { state.focus = ''; renderInfo(); return; }
        var tb = e.target.closest('[data-de-tab]');
        if (tb) { renderGroup(state.group, tb.getAttribute('data-de-tab')); return; }
        var g = e.target.closest('[data-th-group]');
        if (g) {
            var gk = g.getAttribute('data-th-group'), gu = GROUPS[gk].url;
            state.tab = 'basic'; state.scope = ''; renderGroup(gk); showPane('group');
            if (gu) { loading.classList.add('is-on'); frame.src = gu + '?_tp=' + Date.now(); }
            return;
        }
        var dv = e.target.closest('[data-th-device]');
        if (dv) { setDevice(dv.getAttribute('data-th-device')); return; }
        var hb = e.target.closest('.de-help-btn');
        if (hb) {
            var box = document.getElementById(hb.getAttribute('aria-controls')), show = hb.getAttribute('aria-expanded') !== 'true';
            hb.setAttribute('aria-expanded', show ? 'true' : 'false');
            if (box) box.hidden = !show;
            return;
        }
        var au = e.target.closest('[data-auto]');
        if (au) {
            var k = au.getAttribute('data-auto'), wasAuto = au.getAttribute('aria-pressed') === 'true';
            var inp = modal.querySelector('input[data-field="' + k + '"]');
            if (wasAuto && FIELDS[k].light && luminance(inp.value) < 0.6) return;
            var er0 = modal.querySelector('[data-error="' + k + '"]'); if (er0) er0.hidden = true;
            state.values[k] = wasAuto ? inp.value : '';
            au.setAttribute('aria-pressed', wasAuto ? 'false' : 'true');
            au.parentNode.classList.toggle('is-auto', !wasAuto);
            updateTop(); scheduleRefresh();
            return;
        }
        var cl = e.target.closest('[data-clear]');
        if (cl) { state.values[cl.getAttribute('data-clear')] = ''; renderGroup(state.group); updateTop(); scheduleRefresh(); }
    });

    function luminance(hex) {
        var c = [1, 3, 5].map(function (i) { var v = parseInt(hex.substr(i, 2), 16) / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
        return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
    }
    function onFieldChange(inp, live) {
        var k = inp.getAttribute('data-field'), f = FIELDS[k];
        if (f.type === 'url') {
            var ue = modal.querySelector('[data-error="' + k + '"]'), okUrl = inp.value === '' || /^(https?:\/\/\S+|\/\S*|mailto:\S+|tel:\+?[0-9 ()-]+)$/i.test(inp.value);
            if (ue) { ue.textContent = T.badUrl; ue.hidden = okUrl; }
            if (!okUrl) return;
        }
        if (f.type === 'color' && f.light) {
            var er = modal.querySelector('[data-error="' + k + '"]'), dark = luminance(inp.value) < 0.6;
            if (er) { er.textContent = T.tooDark; er.hidden = !dark; }
            if (dark) return;
        }
        state.values[vk(k)] = f.type === 'toggle' ? (inp.checked ? '1' : '0') : inp.value;
        if (f.type === 'slider') { var out = inp.parentNode.querySelector('output'); if (out) out.textContent = inp.value + f.unit; }
        if (f.type === 'color') { var a = modal.querySelector('[data-auto="' + k + '"]'); if (a) { a.setAttribute('aria-pressed', 'false'); a.parentNode.classList.remove('is-auto'); } }
        if (f.type === 'font') { var smp = modal.querySelector('[data-sample="' + k + '"]'), c = f.choices.filter(function (x) { return x.v === inp.value; })[0]; if (smp && c) smp.style.fontFamily = c.stack; }
        applyShowIf(); updateTop();
        scheduleRefresh(live ? 500 : 250);
    }
    function isLive(inp) { return inp.type === 'range' || inp.type === 'text' || inp.type === 'color'; }

    modal.addEventListener('input', function (e) {
        var inp = e.target.closest('[data-field]');
        if (inp && isLive(inp)) onFieldChange(inp, true);
    });
    // Seçili sayfaya özel ayar aç (ortak ayarlar kopyalanır) / kapat (özel değerler silinir, ortak ayara döner)
    modal.addEventListener('click', function (e) {
        var on = e.target.closest('[data-scope-on]'), off = e.target.closest('[data-scope-off]');
        if (!on && !off) return;
        Object.keys(FIELDS).forEach(function (k) {
            if (FIELDS[k].section !== state.group) return;
            var sk = k + '@' + state.scope;
            if (on) state.values[sk] = current(k);
            else if (sk in state.initValues) state.values[sk] = '';
            else delete state.values[sk];
        });
        renderGroup(state.group); updateTop(); scheduleRefresh();
    });
    // Zemin rengine tıklanınca (zaten seçili olana bile) özel zemin rengi bırakılır: seçilen ton görünsün
    modal.addEventListener('click', function (e) {
        var sw = e.target.closest('input[data-opt="surface"]');
        if (!sw || !('site_surface_custom' in state.values) || state.values.site_surface_custom === '') return;
        state.values.site_surface_custom = '';
        if (sw.value === state.opts.surface) { updateTop(); scheduleRefresh(); }
        if (state.group === 'color') { setTimeout(function () { renderGroup(state.group); }, 0); }
    });
    modal.addEventListener('change', function (e) {
        var up = e.target.closest('[data-upload]');
        if (up) { uploadImage(up); return; }
        var sc = e.target.closest('[data-scope]');
        if (sc) {
            state.scope = sc.value;
            renderGroup(state.group);
            var si = scopeInfo(state.scope), c = si && si.cat;
            var to = si && si.item ? si.item.url : (c ? (state.group === 'lists' ? c.list : (c.item || c.list)) : '');
            if (to) { loading.classList.add('is-on'); frame.src = to + (to.indexOf('?') < 0 ? '?' : '&') + '_tp=' + Date.now(); }
            return;
        }
        var inp = e.target.closest('[data-field]');
        if (inp) { if (!isLive(inp)) onFieldChange(inp, false); return; }
        var op = e.target.closest('[data-opt]');
        if (!op) return;
        var o = op.getAttribute('data-opt');
        state.opts[o] = op.type === 'checkbox' ? (op.checked ? '1' : '0') : op.value;
        applyShowIf(); updateTop(); scheduleRefresh();
    });

    function uploadImage(inp) {
        var k = inp.getAttribute('data-upload'), file = inp.files && inp.files[0];
        if (!file) return;
        var err = modal.querySelector('[data-error="' + k + '"]'), lbl = inp.parentNode, txt = lbl.firstChild;
        var label = txt.nodeValue;
        err.hidden = true;
        txt.nodeValue = T.uploading;
        var fd = new FormData();
        fd.append('csrf_token', CSRF); fd.append('action', 'design_upload'); fd.append('field', k); fd.append('file', file);
        fetch('templates.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (res) {
            if (!res.ok) { err.textContent = res.error || T.saveError; err.hidden = false; txt.nodeValue = label; return; }
            state.values[k] = res.path;
            renderGroup(state.group); updateTop(); refresh();
        }).catch(function () { err.textContent = T.saveError; err.hidden = false; txt.nodeValue = label; });
    }

    document.getElementById('thReset').addEventListener('click', function () {
        adminConfirm(T.resetConfirm, { ok: window.ADMIN_LANG === 'en' ? 'Undo changes' : 'Değişiklikleri geri al' }).then(function (ok) { if (ok) resetEdits(); });
    });
    function resetEdits() {
        state.opts = clone(state.initOpts);
        state.values = clone(state.initValues);
        if (state.pane === 'group' && state.group) renderGroup(state.group);
        updateTop();
        refresh();
    }

    document.getElementById('thPublish').addEventListener('click', function () {
        var t = THEMES[state.id];
        // Etkin temada yalnızca sayfaya özel ayarlar değiştiyse “tüm site” denmesin: hangi sayfalar etkileniyor, o söylensin
        var msg = T.publishConfirm.replace('%s', t.name);
        if (t.active) {
            var keys = Object.keys(state.values).filter(function (k) { return (state.values[k] || '') !== (state.initValues[k] || ''); });
            var global = keys.some(function (k) { return k.indexOf('@') < 0; }) || JSON.stringify(state.opts) !== JSON.stringify(state.initOpts);
            var names = [];
            keys.forEach(function (k) {
                if (k.indexOf('@') < 0) return;
                var si = scopeInfo(k.split('@')[1]), n = si ? (si.item ? si.item.label : T.scopeAllInList.replace('%s', si.cat.label)) : k.split('@')[1];
                if (names.indexOf(n) < 0) names.push(n);
            });
            if (names.length) msg = (global ? T.publishMixed : T.publishScoped).replace('%s', '“' + names.join('”, “') + '”');
        }
        adminConfirm(msg, { ok: window.ADMIN_LANG === 'en' ? 'Publish' : 'Yayınla', danger: false }).then(function (ok) {
            if (!ok) return;
            clearTimeout(state.timer);
            saveDraft().then(function () { document.getElementById('thApplyForm').submit(); }).catch(function () { adminToast(T.saveError, 'error'); });
        });
    });

    document.addEventListener('keydown', function (e) {
        // Onay penceresi açıkken Esc yalnızca o pencereyi kapatsın
        if (modal.hidden || (document.getElementById('adminDialog') || {}).open) return;
        if (e.key === 'Escape') {
            if (state.pane === 'group') showPane('edit'); else close();
            return;
        }
        if (e.key === 'Tab') {
            var f = Array.prototype.filter.call(modal.querySelectorAll('button, a[href], input:not([hidden]), select, iframe'), function (n) { return n.offsetParent !== null; });
            if (!f.length) return;
            if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
            else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
        }
    });
    // Sayfalar ekranından “Tasarımı düzenle”: templates.php?design=bölüm → etkin temanın düzenleyicisi o bölümde açılır
    (function () {
        var g = new URLSearchParams(location.search).get('design');
        if (!g || !GROUPS[g]) return;
        var active = Object.keys(THEMES).filter(function (k) { return THEMES[k].active; })[0];
        if (!active) return;
        // view=: Sayfalar ekranında seçilen sayfa (yalnızca bu sitenin adresi kabul edilir)
        var qs = new URLSearchParams(location.search), view = qs.get('view'), cat = qs.get('cat'), vu = null;
        try { vu = view ? new URL(view, location.origin) : null; } catch (e) {}
        state.start = (vu && vu.origin === location.origin && vu.pathname.indexOf('/admin/') < 0) ? vu.origin + vu.pathname : GROUPS[g].url;
        history.replaceState(null, '', 'templates.php');
        open(DRAFT && DRAFT.theme ? DRAFT.theme : active, 'edit');
        state.focus = g; renderInfo();
        // Bir projeden gelindiyse o projenin listesi seçili gelsin (ayarlar liste bazında tutulur)
        if (SCOPE_GROUPS.indexOf(g) >= 0 && scopeInfo(cat) && (g === 'project' || !scopeInfo(cat).item)) state.scope = cat;
        state.tab = 'basic';
        renderGroup(g);
        showPane('group');
    })();
    // Sayfadan ayrılırken önizleme çerezi kalmasın (taslak sunucuda kalır)
    window.addEventListener('pagehide', function () {
        if (!modal.hidden) { try { navigator.sendBeacon('templates.php?preview_end=1&ajax=1'); } catch (e) {} }
    });
})();
</script>

<script>
(function () {
    // Tema kartı taslakları: sitenin kendi çalışma kapaklarıyla çizilen küçük önizlemeler
    var IMG = <?= json_encode(array_values($sketch_imgs), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var TITLES = <?= json_encode(array_values($sketch_titles), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var NAME = <?= json_encode($sketch_name, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    if (!IMG.length) return;
    function esc(s) { return String(s || '').replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
    function t(i) { return esc(TITLES.length ? TITLES[i % TITLES.length] : ''); }
    function im(i, st, cls) { return '<div class="img ' + (cls || '') + '" style="background-image:url(\'' + encodeURI(IMG[i % IMG.length]) + '\');' + st + '"></div>'; }
    function abs(l, tp, w, h) { return 'position:absolute;left:' + l + '%;top:' + tp + '%;width:' + w + '%;height:' + h + '%;'; }
    function menuCol() { return '<div class="t-menu">' + ['—','—','—','—'].map(function () { return '<div style="width:34px;height:3px;background:currentColor;opacity:.35;margin:5px 0;"></div>'; }).join('') + '</div>'; }
    function topbar(c) { return '<div style="position:absolute;left:4%;right:4%;top:6%;display:flex;justify-content:space-between;align-items:center;z-index:3;color:' + (c || 'inherit') + ';"><span class="t-name">' + esc(NAME) + '</span><span style="display:flex;gap:7px;">' + [1,2,3,4].map(function () { return '<i style="display:block;width:16px;height:2px;background:currentColor;opacity:.6;"></i>'; }).join('') + '</span></div>'; }
    var D = {
        sidebar_grid: function () { var c = ''; for (var i = 0; i < 16; i++) c += im(i, 'aspect-ratio:1/1;'); return '<div class="screen"><div style="' + abs(0,0,22,100) + 'padding:9% 6%;border-right:1px solid #eee;"><div class="t-name">' + esc(NAME) + '</div><div class="t-small" style="margin:3px 0 8px;">—</div>' + menuCol() + '</div><div style="position:absolute;left:26%;right:3%;top:6%;display:grid;grid-template-columns:repeat(4,1fr);gap:4px;">' + c + '</div></div>'; },
        sidebar_single: function () { return '<div class="screen"><div style="' + abs(0,0,24,100) + 'padding:9% 6%;"><div class="t-name">' + esc(NAME) + '</div><div class="t-small" style="margin:3px 0 10px;">—</div>' + menuCol() + '</div>' + im(0, abs(28,8,68,76), 'fit') + '<div class="t-small" style="position:absolute;left:28%;bottom:5%;">' + t(0) + '</div></div>'; },
        top_slideshow: function () { return '<div class="screen dark">' + im(1, abs(0,0,100,100)) + '<div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7),transparent 55%);"></div>' + topbar('#fff') + '<div style="position:absolute;left:5%;bottom:9%;color:#fff;font-weight:800;font-size:13px;">' + t(1) + '</div></div>'; },
        tiled_grid: function () { var c = ''; for (var i = 0; i < 6; i++) c += im(i + 2, 'aspect-ratio:4/3;'); return '<div class="screen">' + topbar() + '<div style="position:absolute;left:4%;right:4%;top:19%;display:grid;grid-template-columns:repeat(3,1fr);gap:5px;">' + c + '</div></div>'; },
        horizontal_strip: function () { var r = '', w = [30,18,34,22,30]; for (var i = 0; i < 5; i++) r += '<div style="flex:0 0 ' + w[i] + '%;">' + im(i + 4, 'height:100%;') + '</div>'; return '<div class="screen">' + topbar() + '<div style="position:absolute;left:4%;top:22%;height:60%;display:flex;gap:6px;width:160%;">' + r + '</div></div>'; },
        vertical_stream: function () { return '<div class="screen dark">' + topbar('#fff') + im(3, abs(8,17,84,52)) + '<div style="position:absolute;left:8%;top:73%;color:#fff;font-weight:700;font-size:10px;">' + t(3) + '</div>' + im(5, abs(8,90,84,40)) + '</div>'; },
        text_index: function () { var r = ''; for (var i = 0; i < 6; i++) r += '<div style="border-bottom:1px solid #e5e5e5;padding:3px 0;font-size:8px;font-weight:' + (i === 2 ? 800 : 500) + ';white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + t(i) + '</div>'; return '<div class="screen">' + topbar() + '<div style="position:absolute;left:6%;right:42%;top:22%;">' + r + '</div>' + im(2, abs(62,22,32,60)) + '</div>'; },
        mosaic_wall: function () { var sp = ['grid-column:span 2;grid-row:span 2;','','','','','grid-column:span 2;','','grid-row:span 2;','','']; var c = ''; for (var i = 0; i < 10; i++) c += im(i, sp[i] + 'min-height:0;'); return '<div class="screen dark">' + topbar('#fff') + '<div style="position:absolute;left:3%;right:3%;top:17%;bottom:0;display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:34%;grid-auto-flow:dense;gap:3px;">' + c + '</div></div>'; },
        archive: function () { var o = '<div class="screen">' + topbar(), top = 19, ys = [[0],[1,2],[3,4]], yr = new Date().getFullYear(); ys.forEach(function (g, gi) { o += '<div style="position:absolute;left:5%;right:5%;top:' + top + '%;border-top:1px solid #e5e5e5;padding-top:3px;display:flex;gap:8px;"><div style="font-weight:300;font-size:13px;width:18%;">' + (yr - gi) + '</div><div style="flex:1;">'; g.forEach(function (i) { o += '<div style="display:flex;align-items:center;gap:5px;margin-bottom:3px;">' + im(i, 'width:30px;height:20px;') + '<span style="font-size:7.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + t(i) + '</span></div>'; }); o += '</div></div>'; top += 8 + g.length * 12; }); return o + '</div>'; },
        top_square_grid: function () { var c = ''; for (var i = 0; i < 12; i++) c += im(i, 'aspect-ratio:1/1;'); return '<div class="screen">' + topbar() + '<div style="position:absolute;left:12%;right:12%;top:18%;display:grid;grid-template-columns:repeat(4,1fr);gap:4px;">' + c + '</div></div>'; },
        sidebar_strip_captioned: function () { var r = '', w = [44, 30, 40]; for (var i = 0; i < 3; i++) r += '<div style="flex:0 0 ' + w[i] + '%;">' + im(i + 2, 'height:78%;') + '<div class="t-small" style="margin-top:4px;font-weight:700;">' + t(i + 2) + '</div></div>'; return '<div class="screen"><div style="' + abs(0,0,22,100) + 'padding:9% 6%;border-right:1px solid #eee;"><div class="t-name">' + esc(NAME) + '</div><div class="t-small" style="margin:3px 0 8px;">—</div>' + menuCol() + '</div><div style="position:absolute;left:26%;top:12%;height:68%;display:flex;gap:8px;width:130%;">' + r + '</div></div>'; },
        sidebar_strip_dark: function () { var r = '', w = [56, 40, 50]; for (var i = 0; i < 3; i++) r += '<div style="flex:0 0 ' + w[i] + '%;">' + im(i + 5, 'height:100%;') + '</div>'; return '<div class="screen dark"><div style="' + abs(0,0,22,100) + 'padding:9% 6%;background:#0b0b0c;z-index:2;"><div class="t-name">' + esc(NAME) + '</div><div class="t-small" style="margin:3px 0 8px;">—</div>' + menuCol() + '</div><div style="position:absolute;left:22%;top:0;height:100%;display:flex;gap:2px;width:140%;">' + r + '</div></div>'; },
        sidebar_strip: function () { var r = '', w = [40, 28, 44]; for (var i = 0; i < 3; i++) r += '<div style="flex:0 0 ' + w[i] + '%;">' + im(i + 8, 'height:100%;') + '</div>'; return '<div class="screen"><div style="' + abs(0,0,22,100) + 'padding:9% 6%;"><div class="t-name">' + esc(NAME) + '</div>' + menuCol() + '</div><div style="position:absolute;left:26%;top:16%;height:62%;display:flex;gap:8px;width:130%;">' + r + '</div></div>'; },
        sidebar_wall: function () { var hs = [[42, 30], [28, 46], [36, 34]], cols = ''; for (var c = 0; c < 3; c++) { cols += '<div style="flex:1;display:flex;flex-direction:column;gap:4px;">' + im(c, 'height:' + hs[c][0] + '%;') + im(c + 3, 'height:' + hs[c][1] + '%;') + im(c + 6, 'height:28%;') + '</div>'; } return '<div class="screen"><div style="' + abs(0,0,22,100) + 'padding:9% 6%;border-right:1px solid #eee;"><div class="t-name">' + esc(NAME) + '</div><div class="t-small" style="margin:3px 0 8px;">—</div>' + menuCol() + '</div><div style="position:absolute;left:26%;right:3%;top:6%;bottom:0;display:flex;gap:4px;">' + cols + '</div></div>'; },
        staggered: function () { var pos = [[6, 30, 40, 34], [56, 38, 26, 30], [18, 74, 28, 26], [52, 76, 40, 30]]; return '<div class="screen"><div style="position:absolute;left:0;right:0;top:6%;text-align:center;"><span class="t-name">' + esc(NAME) + '</span><div style="display:flex;gap:6px;justify-content:center;margin-top:4px;">' + [1,2,3,4].map(function () { return '<i style="display:block;width:14px;height:2px;background:currentColor;opacity:.5;"></i>'; }).join('') + '</div></div>' + pos.map(function (p, i) { return im(i, abs(p[0], p[1], p[2], p[3])); }).join('') + '</div>'; },
        one_two: function () { return '<div class="screen"><div style="position:absolute;left:4%;right:4%;top:6%;display:flex;justify-content:space-between;align-items:center;"><span class="t-name">' + esc(NAME) + '</span><span class="t-small" style="border:1px solid currentColor;border-radius:10px;padding:1px 6px;">Menü</span></div><div style="position:absolute;left:4%;right:4%;top:18%;bottom:4%;display:grid;grid-template-columns:2fr 1fr;grid-template-rows:1fr 1fr;gap:4px;">' + im(0, 'grid-row:span 2;') + im(1, '') + im(2, '') + '</div></div>'; },
        sidebar_featured: function () { var c = ''; for (var i = 1; i < 9; i++) c += im(i, 'aspect-ratio:1/1;'); return '<div class="screen"><div style="' + abs(0,0,22,100) + 'padding:9% 6%;border-right:1px solid #eee;"><div class="t-name">' + esc(NAME) + '</div><div class="t-small" style="margin:3px 0 8px;">—</div>' + menuCol() + '</div>' + im(0, abs(26,6,70,42)) + '<div style="position:absolute;left:26%;right:4%;top:52%;display:grid;grid-template-columns:repeat(4,1fr);gap:4px;">' + c + '</div></div>'; },
        statement_wall: function () { return '<div class="screen"><div style="position:absolute;left:5%;right:5%;top:6%;display:flex;justify-content:space-between;align-items:center;"><span class="t-name">' + esc(NAME) + '</span><span class="t-small">Menü</span></div><div style="position:absolute;left:5%;right:30%;top:17%;font-size:8px;line-height:1.35;">Ben bir <b>belgesel fotoğrafçısıyım</b>; insanların gündelik hayatını, kalıcı kılmak için fotoğraflıyorum.</div>' + im(0, abs(5,36,56,62)) + im(1, abs(64,36,31,30)) + im(2, abs(64,70,31,28)) + '</div>'; },
        zigzag: function () { return '<div class="screen">' + '<div style="position:absolute;left:5%;right:5%;top:6%;display:flex;justify-content:space-between;align-items:center;z-index:2;' + '' + '"><span class="t-name">' + esc(NAME) + '</span>' + '<span style="display:flex;flex-direction:column;gap:2px;"><i style="display:block;width:11px;height:1.5px;background:currentColor;"></i><i style="display:block;width:11px;height:1.5px;background:currentColor;"></i></span>' + '</div>' + im(0, abs(6,18,52,36)) + im(1, abs(42,60,52,36)) + im(2, abs(6,102,52,36)) + '</div>'; },
        split_screen: function () { return '<div class="screen">' + '<div style="position:absolute;left:5%;right:5%;top:6%;display:flex;justify-content:space-between;align-items:center;z-index:2;' + '' + '"><span class="t-name">' + esc(NAME) + '</span>' + '<span class="t-small" style="border:1px solid currentColor;border-radius:10px;padding:1px 6px;">Menü</span>' + '</div>' + im(3, abs(0,0,50,100)) + '<div style="position:absolute;left:56%;right:6%;top:36%;"><div style="font-weight:800;font-size:14px;line-height:1.05;">' + t(3) + '</div><div class="t-small" style="margin-top:6px;opacity:.6;">Görüntüle →</div></div></div>'; },
        magazine_stories: function () { return '<div class="screen" style="background:#f3efe6;">' + '<div style="position:absolute;left:5%;right:5%;top:6%;display:flex;justify-content:space-between;align-items:center;z-index:2;' + '' + '"><span class="t-name">' + esc(NAME) + '</span>' + '<span style="display:flex;flex-direction:column;gap:2px;"><i style="display:block;width:11px;height:1.5px;background:currentColor;"></i><i style="display:block;width:11px;height:1.5px;background:currentColor;"></i></span>' + '</div>' + '<div style="' + abs(0,16,52,84) + 'background:rgba(0,0,0,.06);"></div>' + im(4, abs(10,30,32,52)) + '<div style="position:absolute;left:58%;right:6%;top:40%;"><div style="font-weight:400;font-size:13px;line-height:1.1;color:#8a6d00;">' + t(4) + '</div><div style="margin-top:6px;height:3px;width:80%;background:rgba(0,0,0,.15);"></div><div style="margin-top:3px;height:3px;width:60%;background:rgba(0,0,0,.15);"></div></div></div>'; },
        three_panels: function () { var c = ''; for (var i = 0; i < 3; i++) c += '<div style="position:relative;">' + im(i + 6, 'position:absolute;inset:0;filter:brightness(.7);') + '<div class="t-small" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;letter-spacing:.12em;font-weight:700;text-transform:uppercase;text-align:center;padding:4px;">' + t(i + 6) + '</div></div>'; return '<div class="screen dark"><div style="position:absolute;inset:0;display:grid;grid-template-columns:repeat(3,1fr);">' + c + '</div>' + '<div style="position:absolute;left:5%;right:5%;top:6%;display:flex;justify-content:space-between;align-items:center;z-index:2;' + 'color:#fff;' + '"><span class="t-name">' + esc(NAME) + '</span>' + '<span style="display:flex;flex-direction:column;gap:2px;"><i style="display:block;width:11px;height:1.5px;background:currentColor;"></i><i style="display:block;width:11px;height:1.5px;background:currentColor;"></i></span>' + '</div>' + '</div>'; },
        section_banners: function () { var b = ''; for (var i = 0; i < 3; i++) b += '<div style="position:relative;height:30%;margin-bottom:2px;">' + im(i + 1, 'position:absolute;inset:0;filter:brightness(.68);') + '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;gap:6px;color:#fff;font-family:Georgia,serif;font-size:13px;">' + t(i + 1) + '<span style="display:inline-flex;width:12px;height:12px;border:1.5px solid #fff;border-radius:50%;font-size:7px;align-items:center;justify-content:center;">→</span></div></div>'; return '<div class="screen">' + '<div style="position:absolute;left:5%;right:5%;top:6%;display:flex;justify-content:space-between;align-items:center;z-index:2;' + '' + '"><span class="t-name">' + esc(NAME) + '</span>' + '<span class="t-small" style="border:1px solid currentColor;border-radius:10px;padding:1px 6px;">Menü</span>' + '</div>' + '<div style="position:absolute;left:0;right:0;top:15%;bottom:0;">' + b + '</div></div>'; },
        light_table: function () { var th = ''; for (var i = 0; i < 5; i++) th += im(i + 1, 'aspect-ratio:3/2;' + (i ? 'opacity:.45;' : 'outline:1px solid #fff;')); return '<div class="screen dark">' + topbar('#fff') + im(1, abs(6,20,70,66) + 'background-size:contain;background-repeat:no-repeat;background-position:center;') + '<div style="position:absolute;right:4%;top:20%;bottom:6%;width:15%;display:flex;flex-direction:column;gap:4px;overflow:hidden;">' + th + '</div></div>'; },
        bottom_bar: function () { return '<div class="screen dark">' + im(2, abs(0,0,100,100)) + '<div style="position:absolute;left:0;right:0;bottom:0;height:22%;background:linear-gradient(to top,rgba(0,0,0,.65),transparent);"></div><div style="position:absolute;left:5%;right:5%;bottom:6%;display:flex;justify-content:space-between;align-items:center;color:#fff;"><span class="t-name">' + esc(NAME) + '</span><span style="display:flex;gap:6px;">' + [1,2,3].map(function () { return '<i style="display:block;width:14px;height:2px;background:#fff;opacity:.85;"></i>'; }).join('') + '</span></div></div>'; },
        photobook: function () { return '<div class="screen paper">' + topbar() + '<div style="' + abs(8,19,42,70) + 'background:#faf7f0;box-shadow:inset -12px 0 14px -12px rgba(0,0,0,.45);display:flex;flex-direction:column;justify-content:flex-end;padding:5%;"><div style="font-weight:300;font-size:11px;">' + esc(NAME) + '</div></div><div style="' + abs(50,19,42,70) + 'background:#faf7f0;box-shadow:inset 12px 0 14px -12px rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;">' + im(0, 'width:70%;height:55%;') + '</div></div>'; }
    };
    // Galeri filtresi: Tümü / Izgara / Tek fotoğraf / Yatay / Hikâye ve liste
    document.querySelectorAll('[data-th-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var f = btn.getAttribute('data-th-filter');
            document.querySelectorAll('[data-th-filter]').forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
            document.querySelectorAll('.th-card[data-family]').forEach(function (c) { c.hidden = f !== 'all' && c.getAttribute('data-family') !== f; });
        });
    });
    document.querySelectorAll('.mt-mock[data-sketch]').forEach(function (el) {
        var f = D[el.getAttribute('data-sketch')];
        if (f) el.insertAdjacentHTML('beforeend', f());
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>