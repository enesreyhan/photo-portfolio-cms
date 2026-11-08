<?php
/**
 * admin/about.php
 * Hakkımda / About Me Sayfası Yönetimi (Zengin Görsel Editör & Yapılandırılmış CMS)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$site_languages = get_active_languages();
$stmt = $pdo->prepare("SELECT * FROM `items` WHERE `slug` = 'aboutme' LIMIT 1");
$stmt->execute();
$item = $stmt->fetch();

if (!$item) {
    die("Hata: Hakkımda ('aboutme') kaydı veritabanında bulunamadı.");
}

/**
 * Mevcut HTML veya B64 JSON metadata bloğundan yapılandırılmış verileri ayrıştırır
 */
if (!function_exists('parse_about_content')) {
function parse_about_content($html) {
    if (empty($html)) {
        return [
            'narrative' => '',
            'portrait' => ['img' => '/assets/brand/portrait-placeholder.svg', 'name' => site_owner_name(), 'role' => ''],
            'contact' => ['kicker' => '', 'title' => '', 'desc' => '', 'email' => get_setting('contact_email'), 'loc' => ''],
            'chronology' => ['title' => '', 'sub' => '', 'items' => []]
        ];
    }

    // Modern B64 metadata kontrolü
    if (preg_match('#<!-- ABOUT_METADATA_B64:([a-zA-Z0-9+/=]+) -->#', $html, $m_meta)) {
        $meta_raw = base64_decode($m_meta[1]);
        $meta = json_decode($meta_raw, true);
        if ($meta && is_array($meta)) {
            preg_match('#<div class="about-narrative">(.*?)</div>\s*<aside#s', $html, $m_narr);
            $narrative = isset($m_narr[1]) ? trim($m_narr[1]) : '';
            return [
                'narrative' => $narrative,
                'portrait' => $meta['portrait'] ?? [],
                'contact' => $meta['contact'] ?? [],
                'chronology' => $meta['chronology'] ?? ['title' => '', 'sub' => '', 'items' => []]
            ];
        }
    }

    // Geriye dönük uyumluluk: Klasik HTML ayrıştırıcı (Regex fallback)
    preg_match('#<div class="about-narrative">(.*?)</div>\s*<aside#s', $html, $m_narr);
    $narrative = isset($m_narr[1]) ? trim($m_narr[1]) : '';

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
        'kicker' => $m_kicker[1] ?? '',
        'title' => $m_ctitle[1] ?? '',
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
        'title' => $m_ch_title[1] ?? '',
        'sub' => $m_ch_sub[1] ?? '',
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

/**
 * Yapılandırılmış verileri sitenin CSS sınıflarına ve mizanpajına uygun temiz HTML çıktısına derler
 */
function compile_about_html($narrative, $portrait, $contact, $chronology) {
    $img = !empty($portrait['img']) ? $portrait['img'] : '/assets/brand/portrait-placeholder.svg';
    $name = !empty($portrait['name']) ? $portrait['name'] : site_owner_name();
    $role = !empty($portrait['role']) ? $portrait['role'] : '';

    $kicker = !empty($contact['kicker']) ? $contact['kicker'] : '';
    $ctitle = !empty($contact['title']) ? $contact['title'] : '';
    $desc = !empty($contact['desc']) ? $contact['desc'] : '';
    $email = !empty($contact['email']) ? $contact['email'] : get_setting('contact_email');
    $loc = !empty($contact['loc']) ? $contact['loc'] : '';

    $ch_title = !empty($chronology['title']) ? $chronology['title'] : 'Sergiler, Yayınlar & Medya';
    $ch_sub = !empty($chronology['sub']) ? $chronology['sub'] : '';
    $items = !empty($chronology['items']) && is_array($chronology['items']) ? $chronology['items'] : [];

    $chrono_items_html = '';
    foreach ($items as $item) {
        $year = htmlspecialchars($item['year'] ?? '', ENT_QUOTES, 'UTF-8');
        $badge = htmlspecialchars($item['badge'] ?? 'badge-exhibition', ENT_QUOTES, 'UTF-8');
        $badge_label = htmlspecialchars($item['badge_label'] ?? 'Sergi', ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $url = trim($item['url'] ?? '');
        $venue = htmlspecialchars($item['venue'] ?? '', ENT_QUOTES, 'UTF-8');

        if ($url !== '') {
            $title_html = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="chrono-link">'
                . '<span>' . $title . '</span>'
                . '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>'
                . '</a>';
        } else {
            $title_html = '<h4 class="chrono-name">' . $title . '</h4>';
        }

        $chrono_items_html .= <<<ITEM
<div class="chronology-item">
<span class="chrono-year">{$year}</span>
<span class="chrono-badge {$badge}">{$badge_label}</span>
<div class="chrono-info">
{$title_html}
<span class="chrono-venue">{$venue}</span>
</div>
</div>\n
ITEM;
    }

    $email_esc = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $kicker_esc = htmlspecialchars($kicker, ENT_QUOTES, 'UTF-8');
    $ctitle_esc = htmlspecialchars($ctitle, ENT_QUOTES, 'UTF-8');
    $desc_esc = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
    $loc_esc = htmlspecialchars($loc, ENT_QUOTES, 'UTF-8');
    $img_esc = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
    $name_esc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $role_esc = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
    $ch_title_esc = htmlspecialchars($ch_title, ENT_QUOTES, 'UTF-8');
    $ch_sub_esc = htmlspecialchars($ch_sub, ENT_QUOTES, 'UTF-8');

    $narrative_trimmed = trim($narrative);

    // Kayıp olmadan yeniden yükleme için JSON yedeği
    $meta_json = base64_encode(json_encode([
        'portrait' => $portrait,
        'contact' => $contact,
        'chronology' => $chronology
    ], JSON_UNESCAPED_UNICODE));

    $html = <<<HTML
<div class="about-container">

<!-- 1. ANLATI & PORTRE MİZANPAJI -->
<div class="about-main-grid">
<div class="about-narrative">
{$narrative_trimmed}
</div>

<aside class="about-aside">
<figure class="about-portrait-card">
<img src="{$img_esc}" alt="{$name_esc}" class="about-portrait-img" />
<figcaption class="about-portrait-caption">
<span class="caption-name">{$name_esc}</span>
<span class="caption-role">{$role_esc}</span>
</figcaption>
</figure>
</aside>
</div>

<!-- 2. GÖREVLER & İLETİŞİM BANNERI -->
<section class="about-contact-banner">
<div class="contact-banner-content">
<span class="contact-banner-kicker">{$kicker_esc}</span>
<h3 class="contact-banner-title">{$ctitle_esc}</h3>
<p class="contact-banner-desc">{$desc_esc}</p>
</div>
<div class="contact-banner-action">
<a href="mailto:{$email_esc}" class="about-email-btn">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<rect x="2" y="4" width="20" height="16" rx="2"></rect>
<path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
</svg>
<span>{$email_esc}</span>
</a>
<span class="contact-location">{$loc_esc}</span>
</div>
</section>

<!-- 3. KÜRATÖRYAL KRONOLOJİ -->
<section class="about-chronology">
<div class="chronology-header">
<h2 class="chronology-title">{$ch_title_esc}</h2>
<span class="chronology-sub">{$ch_sub_esc}</span>
</div>

<div class="chronology-list">
{$chrono_items_html}</div>
</section>

</div>
<!-- ABOUT_METADATA_B64:{$meta_json} -->
HTML;

    return $html;
}

// POST İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'CSRF güvenlik hatası.');
        header('Location: about.php');
        exit;
    }

    $title_tr = trim($_POST['title_tr'] ?? 'Hakkımda');
    $title_en = trim($_POST['title_en'] ?? 'About Me');

    // Türkçe Veriler
    $narrative_tr = $_POST['narrative_tr'] ?? '';
    $portrait_tr = [
        'img'  => trim($_POST['portrait_img_tr'] ?? '/assets/brand/portrait-placeholder.svg'),
        'name' => trim($_POST['portrait_name_tr'] ?? site_owner_name()),
        'role' => trim($_POST['portrait_role_tr'] ?? '')
    ];
    $contact_tr = [
        'kicker' => trim($_POST['contact_kicker_tr'] ?? 'EDİTORYAL İŞ BİRLİĞİ & TEMSİL'),
        'title'  => trim($_POST['contact_title_tr'] ?? 'Saha Görevleri & İletişim'),
        'desc'   => trim($_POST['contact_desc_tr'] ?? ''),
        'email'  => trim($_POST['contact_email_tr'] ?? get_setting('contact_email')),
        'loc'    => trim($_POST['contact_loc_tr'] ?? '')
    ];
    $chrono_items_tr = json_decode($_POST['chrono_items_tr_json'] ?? '[]', true) ?: [];
    $chronology_tr = [
        'title' => trim($_POST['chrono_title_tr'] ?? 'Sergi, Yayın ve Programlar'),
        'sub'   => trim($_POST['chrono_sub_tr'] ?? 'Seçilmiş Monografiler, Katılımlar ve Basın Arşivi'),
        'items' => $chrono_items_tr
    ];

    // İngilizce Veriler
    $narrative_en = $_POST['narrative_en'] ?? '';
    $portrait_en = [
        'img'  => trim($_POST['portrait_img_en'] ?? $portrait_tr['img']),
        'name' => trim($_POST['portrait_name_en'] ?? site_owner_name()),
        'role' => trim($_POST['portrait_role_en'] ?? '')
    ];
    $contact_en = [
        'kicker' => trim($_POST['contact_kicker_en'] ?? 'EDITORIAL INQUIRIES & COMMISSIONS'),
        'title'  => trim($_POST['contact_title_en'] ?? 'Assignments & Contact'),
        'desc'   => trim($_POST['contact_desc_en'] ?? ''),
        'email'  => trim($_POST['contact_email_en'] ?? get_setting('contact_email')),
        'loc'    => trim($_POST['contact_loc_en'] ?? '')
    ];
    $chrono_items_en = json_decode($_POST['chrono_items_en_json'] ?? '[]', true) ?: [];
    $chronology_en = [
        'title' => trim($_POST['chrono_title_en'] ?? 'Exhibitions, Publications & Media'),
        'sub'   => trim($_POST['chrono_sub_en'] ?? 'Selected Monographs, Features and Press Archive'),
        'items' => $chrono_items_en
    ];

    $compiled_content_tr = compile_about_html($narrative_tr, $portrait_tr, $contact_tr, $chronology_tr);
    $compiled_content_en = compile_about_html($narrative_en, $portrait_en, $contact_en, $chronology_en);

    $up = $pdo->prepare("
        UPDATE `items` SET
            `title_tr` = ?, `title_en` = ?,
            `content_tr` = ?, `content_en` = ?,
            `cover_image` = ?,
            `updated_at` = ?
        WHERE `slug` = 'aboutme'
    ");
    $up->execute([
        $title_tr, $title_en,
        $compiled_content_tr, $compiled_content_en,
        $portrait_tr['img'],
        date('Y-m-d H:i:s')
    ]);

    // Ekstra dillerin (hy, ku, fr, de vb.) Hakkımda içeriklerini derle ve kaydet
    foreach ($site_languages as $sl) {
        $lc = $sl['code'];
        if (in_array($lc, ['tr', 'en'], true)) continue;
        ensure_language_columns($lc);
        $l_title = trim($_POST['title_' . $lc] ?? '');
        $l_narr  = $_POST['narrative_' . $lc] ?? '';
        $l_port  = [
            'img'  => trim($_POST['portrait_img_' . $lc] ?? $portrait_tr['img']),
            'name' => trim($_POST['portrait_name_' . $lc] ?? site_owner_name()),
            'role' => trim($_POST['portrait_role_' . $lc] ?? $portrait_en['role'])
        ];
        $l_cont  = [
            'kicker' => trim($_POST['contact_kicker_' . $lc] ?? $contact_en['kicker']),
            'title'  => trim($_POST['contact_title_' . $lc] ?? $contact_en['title']),
            'desc'   => trim($_POST['contact_desc_' . $lc] ?? $contact_en['desc']),
            'email'  => trim($_POST['contact_email_' . $lc] ?? $contact_en['email']),
            'loc'    => trim($_POST['contact_loc_' . $lc] ?? $contact_en['loc'])
        ];
        $l_ch_items = json_decode($_POST['chrono_items_' . $lc . '_json'] ?? '[]', true) ?: $chrono_items_en;
        $l_chrono = [
            'title' => trim($_POST['chrono_title_' . $lc] ?? $chronology_en['title']),
            'sub'   => trim($_POST['chrono_sub_' . $lc] ?? $chronology_en['sub']),
            'items' => $l_ch_items
        ];
        $l_compiled = (trim(strip_tags($l_narr)) !== '' || $l_title !== '')
            ? compile_about_html($l_narr, $l_port, $l_cont, $l_chrono)
            : '';
        try {
            $st_ex = $pdo->prepare("UPDATE `items` SET `title_{$lc}` = ?, `content_{$lc}` = ? WHERE `slug` = 'aboutme'");
            $st_ex->execute([$l_title, $l_compiled]);
        } catch (\Throwable $e) {}
    }

    set_flash('success', 'Hakkımda sayfası başarıyla güncellendi.');
    header('Location: about.php');
    exit;
}

// Verileri hazırla
$data_tr = parse_about_content($item['content_tr'] ?? '');
$data_en = parse_about_content($item['content_en'] ?? '');
$data_extra = [];
foreach ($site_languages as $sl) {
    $lc = $sl['code'];
    if (in_array($lc, ['tr', 'en'], true)) continue;
    $data_extra[$lc] = parse_about_content($item['content_' . $lc] ?? '');
}

$admin_title = __a('menu_about');
$breadcrumb_root = __a('bc_pages');
$breadcrumb_page = __a('menu_about');

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/image_modal.php';
?>

<style>
/* Zengin Editör ve Sayfa Yönetimi Stilleri */
.about-editor-section {
    margin-bottom: 24px;
}
.about-section-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}
.about-section-card h4 {
    margin: 0 0 14px 0;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid var(--border-subtle);
    padding-bottom: 10px;
}

/* Zengin Editör Araç Çubuğu */
.rich-editor-wrap {
    border: 1px solid var(--border-light);
    border-radius: 10px;
    background: var(--bg-surface);
    overflow: hidden;
    transition: all 0.15s ease;
}
.rich-editor-wrap:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}
.rich-toolbar {
    background: var(--bg-canvas-subtle);
    border-bottom: 1px solid var(--border-subtle);
    padding: 6px 10px;
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
    user-select: none;
}
.rt-btn {
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 13px;
    line-height: 1;
    color: var(--text-sub);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    transition: all 0.12s;
    font-family: inherit;
}
.rt-btn:hover {
    background: var(--bg-hover);
    color: var(--text-main);
    border-color: var(--border-subtle);
}
.rt-btn:active {
    background: var(--border-subtle);
}
.rt-select {
    font-size: 12px;
    font-weight: 600;
    padding: 4px 8px;
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    background: var(--bg-surface);
    color: var(--text-main);
    cursor: pointer;
    outline: none;
    height: 28px;
}
.rt-select:hover {
    border-color: #94a3b8;
}
.rt-sep {
    width: 1px;
    height: 18px;
    background: var(--border-subtle);
    margin: 0 4px;
}
.rt-btn svg {
    display: block;
    stroke: currentColor;
}
.rt-bold { font-weight: 800; font-size: 13px; }
.rt-italic { font-style: italic; font-size: 13px; font-family: serif; }
.rt-underline { text-decoration: underline; font-size: 13px; }
.rt-strike { text-decoration: line-through; font-size: 13px; }

/* Editör İçerik Alanı */
.rich-editable {
    padding: 16px 20px;
    min-height: 280px;
    max-height: 550px;
    overflow-y: auto;
    font-size: 14px;
    line-height: 1.8;
    color: var(--text-main);
    outline: none;
    background: var(--bg-surface);
    word-break: break-word;
}
.rich-editable p {
    margin: 0 0 14px 0;
}
.rich-editable h2,
.rich-editable h3,
.rich-editable h4 {
    font-family: inherit;
    font-weight: 700;
    color: var(--text-main);
    margin: 20px 0 10px 0;
}
.rich-editable h2 { font-size: 18px; }
.rich-editable h3 { font-size: 16px; }
.rich-editable h4 { font-size: 14px; }
.rich-editable ul,
.rich-editable ol {
    margin: 0 0 14px 20px;
    padding: 0;
}
.rich-editable blockquote {
    margin: 14px 0;
    padding: 8px 14px;
    border-left: 3px solid var(--primary, #0284c7);
    background: var(--bg-canvas-subtle);
    color: var(--text-sub);
    font-style: italic;
}
.rich-editable hr {
    border: none;
    border-top: 1px solid var(--border-subtle);
    margin: 16px 0;
}
.rich-editable del,
.rich-editable s,
.rich-editable strike {
    text-decoration: line-through;
    opacity: 0.75;
}
.rich-editable img {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    margin: 14px 0;
}
.rich-editable:empty::before {
    content: attr(data-placeholder);
    color: var(--text-sub);
    pointer-events: none;
    display: block;
}

/* Portre Kartı */
.portrait-editor-box {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}
.portrait-preview-wrap {
    width: 130px;
    height: 155px;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid var(--border-subtle);
    background: var(--bg-surface-2);
    flex-shrink: 0;
    position: relative;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.portrait-preview-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Kronoloji Tablosu */
.chrono-table {
    width: 100%;
    border-collapse: collapse;
}
.chrono-table th {
    background: var(--bg-surface-2);
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 8px 12px;
    border-bottom: 1px solid var(--border-subtle);
}
.chrono-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border-subtle);
    color: var(--text-main);
    vertical-align: middle;
}
.chrono-table tr:hover td {
    background: var(--bg-hover) !important;
}
.chrono-input {
    width: 100%;
    padding: 6px 10px;
    font-size: 13px;
    border: 1px solid var(--border-light);
    border-radius: 6px;
    background: var(--bg-surface);
    color: var(--text-main);
    box-sizing: border-box;
}
.chrono-input:focus {
    border-color: #0284c7;
    outline: none;
}
.badge-pill-select {
    padding: 5px 8px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid var(--border-subtle);
    background: var(--bg-surface);
    color: var(--text-main);
    cursor: pointer;
}
[data-theme="dark"] #btnAutoTranslateAbout {
    background: rgba(37, 99, 235, 0.18) !important;
    color: #93c5fd !important;
    border-color: rgba(37, 99, 235, 0.35) !important;
}
[data-theme="dark"] .rt-html-btn {
    background: var(--bg-surface-2) !important;
    border-color: var(--border-subtle) !important;
    color: var(--text-main) !important;
}
</style>

<?php editor_bar([
    'back_href'  => 'pages.php?page=static-about',
    'back_label' => __a('Sayfalar', 'Pages'),
    'status'     => 'published',
    'form'       => 'aboutForm',
    'preview'    => url($admin_lang . '/' . get_static_slug('about', $admin_lang) . '/'),
    'title'      => __a('Hakkımda', 'About'),
    'sub'        => __a('about_page_sub'),
]); ?>

<form method="POST" id="aboutForm" class="save-form" onsubmit="prepareAboutSubmit()">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <!-- Gizli Veri Alanları -->
    <?php foreach ($site_languages as $sl): $lc = $sl['code']; ?>
    <input type="hidden" name="narrative_<?= e($lc) ?>" id="hidden_narrative_<?= e($lc) ?>">
    <input type="hidden" name="chrono_items_<?= e($lc) ?>_json" id="hidden_chrono_items_<?= e($lc) ?>">
    <?php endforeach; ?>

    <?php 
    $available_tab_codes = array_merge(['tr', 'en'], array_column($site_languages, 'code'));
    $active_tab = in_array($admin_lang, $available_tab_codes, true) ? ('tab-' . $admin_lang) : 'tab-tr';
    ?>
    <?php
    $lb_preview = [];
    foreach (lang_bar_languages() as $l) { $lb_preview[$l['code']] = url($l['code'] . '/' . get_static_slug('about', $l['code']) . '/'); }
    lang_bar([
        'scope'        => '#aboutLangCard',
        'click'        => '#aboutLangTabs .tab-btn[data-tab="tab-{lang}"]',
        'hide'         => '#aboutLangTabs, #btnAutoTranslateAbout, .about-legacy-preview',
        'translate_fn' => 'autoTranslateAboutActiveTab',
        'preview'      => $lb_preview,
    ]);
    ?>
    <div class="card" id="aboutLangCard">
        <div class="card-header" style="flex-wrap:wrap; gap:10px;">
            <div class="admin-tabs" id="aboutLangTabs" style="border:none; margin:0; flex-wrap:wrap;">
                <button type="button" class="tab-btn <?= $active_tab === 'tab-tr' ? 'active' : '' ?>" data-tab="tab-tr">🇹🇷 <?= e(get_localized_language_name('tr', $admin_lang) ?: 'Türkçe') ?></button>
                <button type="button" class="tab-btn <?= $active_tab === 'tab-en' ? 'active' : '' ?>" data-tab="tab-en">🇬🇧 <?= e(get_localized_language_name('en', $admin_lang) ?: 'English') ?></button>
                <?php foreach ($site_languages as $sl):
                    $lc = $sl['code'];
                    if (in_array($lc, ['tr', 'en'], true)) continue;
                ?>
                <button type="button" class="tab-btn <?= $active_tab === ('tab-' . $lc) ? 'active' : '' ?>" data-tab="tab-<?= e($lc) ?>">
                    <?= e($sl['flag'] ?? '🌐') ?> <?= e($sl['name_native'] ?: strtoupper($lc)) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <button type="button" id="btnAutoTranslateAbout" class="btn btn-secondary btn-sm" onclick="autoTranslateAboutActiveTab()" style="background:var(--info-soft); color:var(--accent-blue); border-color:var(--info-border); font-weight:600;">
                    <?= __a('Aktif Sekmeyi Otomatik Çevir', 'Auto-Translate Active Tab') ?>
                </button>
                <a href="<?= url('tr/hakkimda/') ?>" target="_blank" class="btn btn-secondary btn-sm about-legacy-preview">🇹🇷 <?= __a('Türkçe Sayfayı Gör', 'View Turkish Page') ?></a>
                <a href="<?= url('en/about/') ?>" target="_blank" class="btn btn-secondary btn-sm about-legacy-preview">🇬🇧 <?= __a('İngilizce Sayfayı Gör', 'View English Page') ?></a>
            </div>
        </div>

        <div class="card-body">

            <!-- ══════════════════════════════════════════ -->
            <!-- ── TÜRKÇE SEKMESİ ── -->
            <!-- ══════════════════════════════════════════ -->
            <div id="tab-tr" class="tab-content <?= $active_tab === 'tab-tr' ? 'active' : '' ?>">
                
                <!-- 1. Sayfa Başlığı -->
                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label" style="font-weight:700;"><?= __a('Sayfa Ana Başlığı', 'Page Title (Turkish)') ?></label>
                    <input type="text" name="title_tr" class="form-control" value="<?= e($item['title_tr'] ?? 'Hakkımda') ?>" placeholder="Örn: Hakkımda">
                </div>

                <!-- 2. Biyografi ve Anlatı (Zengin Görsel Editör) -->
                <div class="about-section-card">
                    <h4 style="display:flex; align-items:center; gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <span><?= __a('Biyografi ve Editoryal Anlatı Metni', 'Biography & Narrative Text') ?></span>
                        <span style="font-size:12px; font-weight:normal; color:var(--text-muted); margin-left:auto;">
                            <?= __a('HTML kodları yerine doğrudan biçimlendirilmiş metin olarak düzenleyin.', 'Edit directly as formatted rich text without seeing raw HTML tags.') ?>
                        </span>
                    </h4>

                    <div class="rich-editor-wrap">
                        <div class="rich-toolbar">
                            <!-- Paragraf / Başlık Formatı -->
                            <select class="rt-select" onfocus="saveCurrentSelection()" onmousedown="saveCurrentSelection()" onchange="execRichBlockFormat(this, 'tr')" title="Paragraf veya Başlık Formatı">
                                <option value="p">Normal Paragraf</option>
                                <option value="h2">Büyük Başlık (H2)</option>
                                <option value="h3">Bölüm Başlığı (H3)</option>
                                <option value="h4">Alt Başlık (H4)</option>
                                <option value="blockquote">Alıntı Bloğu (Quote)</option>
                            </select>

                            <!-- Yazı Boyutu Seçici -->
                            <select class="rt-select" onfocus="saveCurrentSelection()" onmousedown="saveCurrentSelection()" onchange="execRichFontSize(this, 'tr')" title="Yazı Boyutu (Font Size)">
                                <option value="" disabled selected>Boyut</option>
                                <option value="12px">12px - Küçük / Not</option>
                                <option value="14px">14px - Standart</option>
                                <option value="16px">16px - Okuma</option>
                                <option value="18px">18px - Vurgulu</option>
                                <option value="22px">22px - Başlık</option>
                                <option value="28px">28px - Manşet</option>
                                <option value="inherit">Sıfırla (Varsayılan)</option>
                            </select>

                            <div class="rt-sep"></div>

                            <!-- Geri / İleri Al -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'undo')" title="Geri Al (Ctrl+Z)">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'redo')" title="İleri Al (Ctrl+Y)">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Kalın / İtalik / Altı Çizili / Üstü Çizili -->
                            <button type="button" class="rt-btn rt-bold" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'bold')" title="Kalın (Ctrl+B)"><strong>B</strong></button>
                            <button type="button" class="rt-btn rt-italic" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'italic')" title="İtalik (Ctrl+I)"><em>I</em></button>
                            <button type="button" class="rt-btn rt-underline" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'underline')" title="Altı Çizili (Ctrl+U)"><u>U</u></button>
                            <button type="button" class="rt-btn rt-strike" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'strikeThrough')" title="Üstü Çizili (Strikethrough)"><s>S</s></button>

                            <div class="rt-sep"></div>

                            <!-- Hizalama: Sol, Orta, Sağ, İki Yana -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'justifyLeft')" title="Sola Yasla">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="15" y2="12"></line><line x1="3" y1="18" x2="18" y2="18"></line></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'justifyCenter')" title="Ortala">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="6" y1="12" x2="18" y2="12"></line><line x1="4" y1="18" x2="20" y2="18"></line></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'justifyRight')" title="Sağa Yasla">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="9" y1="12" x2="21" y2="12"></line><line x1="6" y1="18" x2="21" y2="18"></line></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'justifyFull')" title="İki Yana Yasla">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="15" y2="12"></line><line x1="3" y1="18" x2="18" y2="18"></line></svg>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Listeler & Çizgi -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'insertUnorderedList')" title="Madde İmleri Listesi">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1.5" fill="currentColor"/><circle cx="3.5" cy="12" r="1.5" fill="currentColor"/><circle cx="3.5" cy="18" r="1.5" fill="currentColor"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'insertOrderedList')" title="Numaralı Liste">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'insertHorizontalRule')" title="Ayırıcı Yatay Çizgi">―</button>

                            <div class="rt-sep"></div>

                            <!-- Bağlantı / Link -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichLink('tr')" title="Bağlantı / Link Ekle">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'unlink')" title="Bağlantıyı Kaldır">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.16 11.75-1.72 1.71a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Görsel Ekle -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="insertImageToNarrative('tr')" title="Medya Kütüphanesinden Görsel Ekle" style="display:inline-flex; align-items:center; gap:5px; color:#0284c7; font-weight:600;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <span>Görsel Ekle</span>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Biçimlendirmeyi Temizle -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('tr', 'removeFormat')" title="Biçimlendirmeyi Temizle">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/></svg>
                            </button>

                            <!-- Kaynak Kod Modu Geçişi -->
                            <button type="button" class="rt-btn rt-html-btn" onclick="toggleHtmlMode('tr')" style="margin-left:auto; font-size:12px; display:inline-flex; align-items:center; gap:5px;" title="HTML Kaynak Kodu ile Zengin Editör Arasında Geçiş Yap">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                <span id="toggleText_tr">Kaynak Kodu</span>
                            </button>
                        </div>

                        <!-- Görsel Zengin Editör -->
                        <div class="rich-editable" id="editable_tr" contenteditable="true" data-placeholder="Biyografi, belgesel fotoğrafçılık yaklaşımınız ve editoryal hikaye paragraflarınızı buraya yazın..."><!--noi18n--><?= $data_tr['narrative'] ?><!--/noi18n--></div>

                        <!-- İsteğe Bağlı HTML Kaynak Alanı (Varsayılan olarak gizli) -->
                        <textarea id="raw_textarea_tr" class="form-control" style="display:none; min-height:280px; font-family:monospace; font-size:12px; border-radius:0; border:none;"></textarea>
                    </div>
                </div>

                <!-- 3. Portre ve Profil Kimlik Kartı -->
                <div class="about-section-card">
                    <h4 style="display:flex; align-items:center; gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>Portre Fotoğrafı ve Künye Bilgileri</span>
                    </h4>
                    <div class="portrait-editor-box">
                        <div>
                            <div class="portrait-preview-wrap">
                                <img id="portrait_preview_tr" src="<?= url(ltrim($data_tr['portrait']['img'] ?? '/assets/brand/portrait-placeholder.svg', '/')) ?>" alt="Portre">
                            </div>
                            <input type="hidden" name="portrait_img_tr" id="portrait_img_tr" value="<?= e($data_tr['portrait']['img'] ?? '/assets/brand/portrait-placeholder.svg') ?>">
                            <div style="margin-top:8px;">
                                <button type="button" class="btn btn-secondary btn-sm" style="width:130px; font-size:12px; padding:6px 4px; display:inline-flex; align-items:center; justify-content:center; gap:5px;" onclick="choosePortraitImage('tr')">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    <span>Fotoğraf Seç</span>
                                </button>
                            </div>
                        </div>

                        <div style="flex:1;">
                            <div class="form-group" style="margin-bottom:12px;">
                                <label class="form-label">İsim & Soyisim</label>
                                <input type="text" name="portrait_name_tr" class="form-control" value="<?= e($data_tr['portrait']['name'] ?? site_owner_name()) ?>" placeholder="Örn: Ayşe Yılmaz">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Ünvan ve Rol (Künye Açıklaması)</label>
                                <input type="text" name="portrait_role_tr" class="form-control" value="<?= e($data_tr['portrait']['role'] ?? '') ?>" placeholder="Örn: Belgesel Fotoğrafçı • İstanbul">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Saha Görevleri & İletişim Alanı -->
                <div class="about-section-card">
                    <h4 style="display:flex; align-items:center; gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>Saha Görevleri & İletişim Kutusu</span>
                    </h4>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Üst Etiket (Kicker)</label>
                            <input type="text" name="contact_kicker_tr" class="form-control" value="<?= e($data_tr['contact']['kicker'] ?? 'EDİTORYAL İŞ BİRLİĞİ & TEMSİL') ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Kutu Başlığı</label>
                            <input type="text" name="contact_title_tr" class="form-control" value="<?= e($data_tr['contact']['title'] ?? 'Saha Görevleri & İletişim') ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:12px;">
                        <label class="form-label">Açıklama Metni</label>
                        <textarea name="contact_desc_tr" class="form-control" rows="2"><?= e($data_tr['contact']['desc'] ?? 'Ulusal ve uluslararası editoryal saha çalışmaları, uzun soluklu belgesel araştırmaları ve monografi projeleri için uygundur.') ?></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">E-Posta Adresi</label>
                            <input type="email" name="contact_email_tr" class="form-control" readonly value="<?= e(get_setting('contact_email')) ?>"><small class="form-hint"><a href="settings.php#genel"><?= e(__a('Site Ayarları › Genel', 'Site Settings › General')) ?></a><?= e(__a('’den değiştirilir', ' to change')) ?></small>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Konum / Şehir</label>
                            <input type="text" name="contact_loc_tr" class="form-control" value="<?= e($data_tr['contact']['loc'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- 5. Küratoryal Kronoloji (Sergiler, Yayınlar, Medya) -->
                <div class="about-section-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                        <h4 style="margin:0; border:none; padding:0; display:flex; align-items:center; gap:8px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                            <span>Sergiler, Yayınlar ve Medya Kronolojisi</span>
                        </h4>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addChronoRow('tr')" style="border-color:#0284c7; color:#0284c7; font-weight:600;">
                            <span>+ Yeni Madde Ekle</span>
                        </button>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Bölüm Başlığı</label>
                            <input type="text" name="chrono_title_tr" class="form-control" value="<?= e($data_tr['chronology']['title'] ?? 'Sergi, Yayın ve Programlar') ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Alt Başlık</label>
                            <input type="text" name="chrono_sub_tr" class="form-control" value="<?= e($data_tr['chronology']['sub'] ?? 'Seçilmiş Monografiler, Katılımlar ve Basın Arşivi') ?>">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="chrono-table">
                            <thead>
                                <tr>
                                    <th style="width:70px;">Sıra</th>
                                    <th style="width:90px;">Yıl</th>
                                    <th style="width:130px;">Kategori</th>
                                    <th>Etkinlik / Eser / Başlık</th>
                                    <th style="width:220px;">Bağlantı Linki (URL)</th>
                                    <th>Mekan / Yayın Detayı</th>
                                    <th style="width:60px; text-align:center;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="chronoTableBody_tr">
                                <!-- JS ile doldurulacak -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- ══════════════════════════════════════════ -->
            <!-- ── İNGİLİZCE SEKMESİ ── -->
            <!-- ══════════════════════════════════════════ -->
            <div id="tab-en" class="tab-content <?= $active_tab === 'tab-en' ? 'active' : '' ?>">
                
                <!-- 1. Sayfa Başlığı -->
                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label" style="font-weight:700;">Page Title (English)</label>
                    <input type="text" name="title_en" class="form-control" value="<?= e($item['title_en'] ?? 'About Me') ?>" placeholder="e.g. About Me">
                </div>

                <!-- 2. Biyografi ve Anlatı (Zengin Görsel Editör) -->
                <div class="about-section-card">
                    <h4 style="display:flex; align-items:center; gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <span>Biography & Editorial Narrative Text</span>
                        <span style="font-size:12px; font-weight:normal; color:var(--text-muted); margin-left:auto;">
                            Edit directly as formatted rich text without seeing raw HTML tags.
                        </span>
                    </h4>

                    <div class="rich-editor-wrap">
                        <div class="rich-toolbar">
                            <!-- Paragraf / Başlık Formatı -->
                            <select class="rt-select" onfocus="saveCurrentSelection()" onmousedown="saveCurrentSelection()" onchange="execRichBlockFormat(this, 'en')" title="Paragraph or Heading Format">
                                <option value="p">Normal Paragraph</option>
                                <option value="h2">Heading 2 (H2)</option>
                                <option value="h3">Section Heading (H3)</option>
                                <option value="h4">Subheading (H4)</option>
                                <option value="blockquote">Quote Block</option>
                            </select>

                            <!-- Yazı Boyutu Seçici -->
                            <select class="rt-select" onfocus="saveCurrentSelection()" onmousedown="saveCurrentSelection()" onchange="execRichFontSize(this, 'en')" title="Font Size">
                                <option value="" disabled selected>Size</option>
                                <option value="12px">12px - Small / Note</option>
                                <option value="14px">14px - Regular</option>
                                <option value="16px">16px - Reading</option>
                                <option value="18px">18px - Lead</option>
                                <option value="22px">22px - Heading</option>
                                <option value="28px">28px - Banner</option>
                                <option value="inherit">Reset (Default)</option>
                            </select>

                            <div class="rt-sep"></div>

                            <!-- Undo / Redo -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'undo')" title="Undo (Ctrl+Z)">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'redo')" title="Redo (Ctrl+Y)">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Bold / Italic / Underline / Strikethrough -->
                            <button type="button" class="rt-btn rt-bold" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'bold')" title="Bold (Ctrl+B)"><strong>B</strong></button>
                            <button type="button" class="rt-btn rt-italic" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'italic')" title="Italic (Ctrl+I)"><em>I</em></button>
                            <button type="button" class="rt-btn rt-underline" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'underline')" title="Underline (Ctrl+U)"><u>U</u></button>
                            <button type="button" class="rt-btn rt-strike" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'strikeThrough')" title="Strikethrough"><s>S</s></button>

                            <div class="rt-sep"></div>

                            <!-- Alignments -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'justifyLeft')" title="Align Left">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="15" y2="12"></line><line x1="3" y1="18" x2="18" y2="18"></line></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'justifyCenter')" title="Align Center">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="6" y1="12" x2="18" y2="12"></line><line x1="4" y1="18" x2="20" y2="18"></line></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'justifyRight')" title="Align Right">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="9" y1="12" x2="21" y2="12"></line><line x1="6" y1="18" x2="21" y2="18"></line></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'justifyFull')" title="Justify">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="15" y2="12"></line><line x1="3" y1="18" x2="18" y2="18"></line></svg>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Lists & Divider -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'insertUnorderedList')" title="Bullet List">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1.5" fill="currentColor"/><circle cx="3.5" cy="12" r="1.5" fill="currentColor"/><circle cx="3.5" cy="18" r="1.5" fill="currentColor"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'insertOrderedList')" title="Numbered List">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'insertHorizontalRule')" title="Horizontal Line">―</button>

                            <div class="rt-sep"></div>

                            <!-- Link -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichLink('en')" title="Insert Link">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'unlink')" title="Remove Link">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.16 11.75-1.72 1.71a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Insert Image -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="insertImageToNarrative('en')" title="Insert Image from Library" style="display:inline-flex; align-items:center; gap:5px; color:#0284c7; font-weight:600;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <span>Insert Image</span>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Clear Formatting -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('en', 'removeFormat')" title="Clear Formatting">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/></svg>
                            </button>

                            <!-- HTML Mode Toggle -->
                            <button type="button" class="rt-btn rt-html-btn" onclick="toggleHtmlMode('en')" style="margin-left:auto; font-size:12px; display:inline-flex; align-items:center; gap:5px;" title="Toggle HTML Source Mode">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                <span id="toggleText_en">HTML Code</span>
                            </button>
                        </div>

                        <!-- Görsel Zengin Editör -->
                        <div class="rich-editable" id="editable_en" contenteditable="true" data-placeholder="Write your biography, documentary philosophy, and story paragraphs here..."><!--noi18n--><?= $data_en['narrative'] ?><!--/noi18n--></div>

                        <!-- İsteğe Bağlı HTML Kaynak Alanı -->
                        <textarea id="raw_textarea_en" class="form-control" style="display:none; min-height:280px; font-family:monospace; font-size:12px; border-radius:0; border:none;"></textarea>
                    </div>
                </div>

                <!-- 3. Portre ve Profil Kimlik Kartı -->
                <div class="about-section-card">
                    <h4 style="display:flex; align-items:center; gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>Portrait Photo & Identity Card</span>
                    </h4>
                    <div class="portrait-editor-box">
                        <div>
                            <div class="portrait-preview-wrap">
                                <img id="portrait_preview_en" src="<?= url(ltrim($data_en['portrait']['img'] ?? '/assets/brand/portrait-placeholder.svg', '/')) ?>" alt="Portrait">
                            </div>
                            <input type="hidden" name="portrait_img_en" id="portrait_img_en" value="<?= e($data_en['portrait']['img'] ?? '/assets/brand/portrait-placeholder.svg') ?>">
                            <div style="margin-top:8px;">
                                <button type="button" class="btn btn-secondary btn-sm" style="width:130px; font-size:12px; padding:6px 4px; display:inline-flex; align-items:center; justify-content:center; gap:5px;" onclick="choosePortraitImage('en')">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    <span>Select Photo</span>
                                </button>
                            </div>
                        </div>

                        <div style="flex:1;">
                            <div class="form-group" style="margin-bottom:12px;">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="portrait_name_en" class="form-control" value="<?= e($data_en['portrait']['name'] ?? site_owner_name()) ?>" placeholder="e.g. Jane Doe">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Role & Caption</label>
                                <input type="text" name="portrait_role_en" class="form-control" value="<?= e($data_en['portrait']['role'] ?? '') ?>" placeholder="e.g. Documentary Photographer • Istanbul">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Saha Görevleri & İletişim Alanı -->
                <div class="about-section-card">
                    <h4 style="display:flex; align-items:center; gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>Assignments & Contact Box</span>
                    </h4>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Kicker Title</label>
                            <input type="text" name="contact_kicker_en" class="form-control" value="<?= e($data_en['contact']['kicker'] ?? 'EDITORIAL INQUIRIES & COMMISSIONS') ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Box Title</label>
                            <input type="text" name="contact_title_en" class="form-control" value="<?= e($data_en['contact']['title'] ?? 'Assignments & Contact') ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:12px;">
                        <label class="form-label">Description Text</label>
                        <textarea name="contact_desc_en" class="form-control" rows="2"><?= e($data_en['contact']['desc'] ?? 'Available for national and international editorial field assignments, long-term documentary research, and monograph commissions.') ?></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="contact_email_en" class="form-control" readonly value="<?= e(get_setting('contact_email')) ?>"><small class="form-hint"><a href="settings.php#genel"><?= e(__a('Site Ayarları › Genel', 'Site Settings › General')) ?></a><?= e(__a('’den değiştirilir', ' to change')) ?></small>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Location</label>
                            <input type="text" name="contact_loc_en" class="form-control" value="<?= e($data_en['contact']['loc'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- 5. Küratoryal Kronoloji (Sergiler, Yayınlar, Medya) -->
                <div class="about-section-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                        <h4 style="margin:0; border:none; padding:0; display:flex; align-items:center; gap:8px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                            <span>Exhibitions, Publications & Media Chronology</span>
                        </h4>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addChronoRow('en')" style="border-color:#0284c7; color:#0284c7; font-weight:600;">
                            <span>+ Add New Entry</span>
                        </button>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Section Title</label>
                            <input type="text" name="chrono_title_en" class="form-control" value="<?= e($data_en['chronology']['title'] ?? 'Exhibitions, Publications & Media') ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Subtitle</label>
                            <input type="text" name="chrono_sub_en" class="form-control" value="<?= e($data_en['chronology']['sub'] ?? 'Selected Monographs, Features and Press Archive') ?>">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="chrono-table">
                            <thead>
                                <tr>
                                    <th style="width:70px;">Order</th>
                                    <th style="width:90px;">Year</th>
                                    <th style="width:130px;">Category</th>
                                    <th>Event / Title</th>
                                    <th style="width:220px;">Link (URL)</th>
                                    <th>Venue / Publication</th>
                                    <th style="width:60px; text-align:center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="chronoTableBody_en">
                                <!-- JS ile doldurulacak -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <?php foreach ($site_languages as $sl):
                $lc = $sl['code'];
                if (in_array($lc, ['tr', 'en'], true)) continue;
                $d_lc = $data_extra[$lc] ?? ['narrative' => '', 'portrait' => [], 'contact' => [], 'chronology' => ['title' => '', 'sub' => '', 'items' => []]];
            ?>
            <div id="tab-<?= e($lc) ?>" class="tab-content <?= $active_tab === ('tab-' . $lc) ? 'active' : '' ?>">
                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label" style="font-weight:700;"><?= e($sl['flag'] ?? '🌐') ?> <?= __a('Sayfa Ana Başlığı', 'Page Title') ?> (<?= e(get_localized_language_name($sl['code'], $admin_lang)) ?>)</label>
                    <input type="text" name="title_<?= e($lc) ?>" class="form-control" value="<?= e($item['title_' . $lc] ?? '') ?>" placeholder="<?= e($sl['name_native'] ?: strtoupper($lc)) ?>...">
                </div>

                <div class="about-section-card">
                    <h4 style="display:flex; align-items:center; gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <span><?= __a('Biyografi ve Editoryal Anlatı Metni', 'Biography & Narrative Text') ?> (<?= e($sl['name_native']) ?>)</span>
                    </h4>
                    <div class="rich-editor-wrap">
                        <div class="rich-toolbar">
                            <button type="button" class="rt-btn rt-bold" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= e($lc) ?>', 'bold')"><strong>B</strong></button>
                            <button type="button" class="rt-btn rt-italic" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= e($lc) ?>', 'italic')"><em>I</em></button>
                            <button type="button" class="rt-btn rt-underline" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= e($lc) ?>', 'underline')"><u>U</u></button>
                            <button type="button" class="rt-btn rt-html-btn" onclick="toggleHtmlMode('<?= e($lc) ?>')" style="margin-left:auto; font-size:12px; display:inline-flex; align-items:center; gap:5px;">
                                <span id="toggleText_<?= e($lc) ?>"><?= __a('HTML Kaynak', 'HTML Source') ?></span>
                            </button>
                        </div>
                        <div class="rich-editable" id="editable_<?= e($lc) ?>" contenteditable="true" data-placeholder="<?= e($sl['name_native']) ?>..."><!--noi18n--><?= $d_lc['narrative'] ?? '' ?><!--/noi18n--></div>
                        <textarea id="raw_textarea_<?= e($lc) ?>" class="form-control" style="display:none; min-height:280px; font-family:monospace; font-size:12px; border-radius:0; border:none;"></textarea>
                    </div>
                </div>

                <div class="about-section-card">
                    <h4><?= __a('Portre & İletişim Kutusu', 'Portrait & Contact Box') ?> (<?= e($sl['name_native']) ?>)</h4>
                    <input type="hidden" name="portrait_img_<?= e($lc) ?>" value="<?= e($d_lc['portrait']['img'] ?? ($data_tr['portrait']['img'] ?? '/assets/brand/portrait-placeholder.svg')) ?>">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label"><?= __a('Tam İsim', 'Full Name') ?></label>
                            <input type="text" name="portrait_name_<?= e($lc) ?>" class="form-control" value="<?= e($d_lc['portrait']['name'] ?? site_owner_name()) ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label"><?= __a('Unvan & Alt Yazı', 'Role & Subtitle') ?></label>
                            <input type="text" name="portrait_role_<?= e($lc) ?>" class="form-control" value="<?= e($d_lc['portrait']['role'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label"><?= __a('İletişim Kicker Başlığı', 'Contact Kicker Title') ?></label>
                            <input type="text" name="contact_kicker_<?= e($lc) ?>" class="form-control" value="<?= e($d_lc['contact']['kicker'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label"><?= __a('İletişim Ana Başlığı', 'Contact Main Title') ?></label>
                            <input type="text" name="contact_title_<?= e($lc) ?>" class="form-control" value="<?= e($d_lc['contact']['title'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label class="form-label"><?= __a('İletişim Açıklaması', 'Contact Description') ?></label>
                        <textarea name="contact_desc_<?= e($lc) ?>" class="form-control" rows="2"><?= e($d_lc['contact']['desc'] ?? '') ?></textarea>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label"><?= __a('E-Posta', 'Email') ?></label>
                            <input type="email" name="contact_email_<?= e($lc) ?>" class="form-control" readonly value="<?= e(get_setting('contact_email')) ?>"><small class="form-hint"><a href="settings.php#genel"><?= e(__a('Site Ayarları › Genel', 'Site Settings › General')) ?></a><?= e(__a('’den değiştirilir', ' to change')) ?></small>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label"><?= __a('Konum', 'Location') ?></label>
                            <input type="text" name="contact_loc_<?= e($lc) ?>" class="form-control" value="<?= e($d_lc['contact']['loc'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label"><?= __a('Kronoloji Başlığı', 'Chronology Title') ?></label>
                            <input type="text" name="chrono_title_<?= e($lc) ?>" class="form-control" value="<?= e($d_lc['chronology']['title'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label"><?= __a('Kronoloji Alt Başlığı', 'Chronology Subtitle') ?></label>
                            <input type="text" name="chrono_sub_<?= e($lc) ?>" class="form-control" value="<?= e($d_lc['chronology']['sub'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>


        </div>
    </div>
</form>

<script>
// ── Başlangıç Verileri ──
var chronoData = {
    tr: <?= json_encode($data_tr['chronology']['items'] ?? [], JSON_UNESCAPED_UNICODE) ?>,
    en: <?= json_encode($data_en['chronology']['items'] ?? [], JSON_UNESCAPED_UNICODE) ?>
};

var htmlMode = {
    tr: false,
    en: false
};

var badgeCategories = [
    { value: 'badge-exhibition', label_tr: 'Sergi', label_en: 'Exhibition' },
    { value: 'badge-magazine',   label_tr: 'Dergi', label_en: 'Magazine' },
    { value: 'badge-book',       label_tr: 'Kitap', label_en: 'Book' },
    { value: 'badge-media',      label_tr: 'Yayın & Basın', label_en: 'Press' },
    { value: 'badge-tv',         label_tr: 'TV', label_en: 'TV' },
    { value: 'badge-award',      label_tr: 'Ödül', label_en: 'Award' }
];

// ── Zengin Metin Komutları & Seçim İzleme ──
var savedSelectionRange = null;

function saveCurrentSelection() {
    var sel = window.getSelection();
    if (sel && sel.rangeCount > 0) {
        savedSelectionRange = sel.getRangeAt(0).cloneRange();
    }
}

document.addEventListener('selectionchange', function() {
    var sel = window.getSelection();
    if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
        var node = sel.anchorNode;
        if (node && node.nodeType === 3) node = node.parentNode;
        if (node && (node.closest('.rich-editable') || (node.classList && node.classList.contains('rich-editable')))) {
            savedSelectionRange = sel.getRangeAt(0).cloneRange();
        }
    }
});

function execRichCmd(lang, cmd, val) {
    if (htmlMode[lang]) return;
    var el = document.getElementById('editable_' + lang);
    if (!el) return;
    el.focus();
    if (savedSelectionRange && (cmd === 'bold' || cmd === 'italic' || cmd === 'underline' || cmd === 'strikeThrough')) {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
            try {
                sel.removeAllRanges();
                sel.addRange(savedSelectionRange);
            } catch (e) {}
        }
    }
    document.execCommand(cmd, false, val || null);
}

function execRichBlockFormat(selectEl, lang) {
    if (htmlMode[lang]) return;
    var tag = selectEl.value;
    var el = document.getElementById('editable_' + lang);
    if (!el || !tag) return;
    el.focus();
    if (savedSelectionRange) {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
            try {
                sel.removeAllRanges();
                sel.addRange(savedSelectionRange);
            } catch (e) {}
        }
    }
    try {
        document.execCommand('formatBlock', false, tag);
    } catch (e) {
        document.execCommand('formatBlock', false, '<' + tag + '>');
    }
}

function execRichFontSize(selectEl, lang) {
    if (htmlMode[lang]) return;
    var size = selectEl.value;
    var el = document.getElementById('editable_' + lang);
    if (!el) return;
    el.focus();

    if (savedSelectionRange) {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
            try {
                sel.removeAllRanges();
                sel.addRange(savedSelectionRange);
            } catch (e) {}
        }
    }

    var curSel = window.getSelection();
    if (!size || size === 'inherit' || size === 'default') {
        if (curSel && !curSel.isCollapsed) {
            document.execCommand('removeFormat', false, null);
        } else if (curSel && curSel.rangeCount > 0) {
            var node = curSel.anchorNode;
            if (node && node.nodeType === 3) node = node.parentNode;
            var block = node ? node.closest('p, h2, h3, h4, blockquote, li, div') : null;
            if (block && el.contains(block)) {
                block.style.fontSize = '';
            }
        }
        if (selectEl) selectEl.value = '';
        return;
    }

    if (curSel && curSel.rangeCount > 0 && !curSel.isCollapsed) {
        document.execCommand('styleWithCSS', false, false);
        document.execCommand('fontSize', false, '7');
        var fontTags = el.querySelectorAll('font[size="7"]');
        fontTags.forEach(function(font) {
            var span = document.createElement('span');
            span.style.fontSize = size;
            while (font.firstChild) {
                span.appendChild(font.firstChild);
            }
            if (font.parentNode) {
                font.parentNode.replaceChild(span, font);
            }
        });
        var webkitSpans = el.querySelectorAll('span[style*="-webkit-xxx"]');
        webkitSpans.forEach(function(s) {
            s.style.fontSize = size;
        });
    } else if (curSel && curSel.rangeCount > 0) {
        var node = curSel.anchorNode;
        if (node && node.nodeType === 3) node = node.parentNode;
        var block = node ? node.closest('p, h2, h3, h4, blockquote, li, div') : null;
        if (block && el.contains(block)) {
            block.style.fontSize = size;
        }
    }

    if (selectEl) selectEl.value = '';
}

function execRichLink(lang) {
    if (htmlMode[lang]) return;
    var el = document.getElementById('editable_' + lang);
    if (!el) return;
    el.focus();

    if (savedSelectionRange) {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
            try {
                sel.removeAllRanges();
                sel.addRange(savedSelectionRange);
            } catch (e) {}
        }
    }

    var sel = window.getSelection();
    var selectedText = sel ? sel.toString().trim() : '';
    adminPromptLink().then(function (url) {
        if (!url) return;
        if (selectedText.length === 0) {
            document.execCommand('insertHTML', false, '<a href="' + escapeHtml(url) + '" target="_blank">' + escapeHtml(url) + '</a>');
        } else {
            document.execCommand('createLink', false, url);
        }
    });
}

function insertImageToNarrative(lang) {
    if (htmlMode[lang]) return;
    var el = document.getElementById('editable_' + lang);
    if (!el) return;

    openMediaModalForCallback(function(filePath, altText) {
        el.focus();
        var fullUrl = '<?= BASE_URL ?>/' + filePath.replace(/^\//, '');
        var imgHtml = '<p><img src="' + escapeHtml(filePath) + '" alt="' + escapeHtml(altText || 'Fotoğraf') + '" style="max-width:100%; height:auto; border-radius:6px; margin:16px 0; display:block;" /></p>';
        document.execCommand('insertHTML', false, imgHtml);
    });
}

function toggleHtmlMode(lang) {
    var editable = document.getElementById('editable_' + lang);
    var textarea = document.getElementById('raw_textarea_' + lang);
    var btnText = document.getElementById('toggleText_' + lang);

    if (!htmlMode[lang]) {
        // Zengin Moddan HTML Moduna geçiş
        textarea.value = editable.innerHTML.trim();
        editable.style.display = 'none';
        textarea.style.display = 'block';
        btnText.textContent = 'Görsel Editör';
        htmlMode[lang] = true;
    } else {
        // HTML Modundan Zengin Moda dönüş
        editable.innerHTML = textarea.value;
        textarea.style.display = 'none';
        editable.style.display = 'block';
        btnText.textContent = lang === 'tr' ? 'Kaynak Kodu' : 'HTML Code';
        htmlMode[lang] = false;
    }
}

// ── Portre Görseli Seçimi ──
function choosePortraitImage(lang) {
    openMediaModalForCallback(function(filePath, altText) {
        document.getElementById('portrait_img_' + lang).value = filePath;
        var fullUrl = '<?= BASE_URL ?>/' + filePath.replace(/^\//, '');
        document.getElementById('portrait_preview_' + lang).src = fullUrl;
    });
}

// ── Kronoloji Yönetimi ──
function renderChronoTable(lang) {
    var tbody = document.getElementById('chronoTableBody_' + lang);
    if (!tbody) return;
    var items = chronoData[lang] || [];
    tbody.innerHTML = '';

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--text-sub); padding:30px;">Henüz kronoloji maddesi eklenmemiş. "+ Yeni Madde Ekle" butonu ile ekleyebilirsiniz.</td></tr>';
        return;
    }

    items.forEach(function(item, idx) {
        var tr = document.createElement('tr');

        // Rozet seçenekleri
        var optHtml = '';
        badgeCategories.forEach(function(cat) {
            var selected = (item.badge === cat.value) ? 'selected' : '';
            var label = (lang === 'tr') ? cat.label_tr : cat.label_en;
            optHtml += '<option value="' + cat.value + '" ' + selected + '>' + label + '</option>';
        });

        tr.innerHTML = 
            '<td style="text-align:center; white-space:nowrap;">' +
                '<button type="button" class="rt-btn" onclick="moveChronoRow(\'' + lang + '\', ' + idx + ', -1)" ' + (idx === 0 ? 'disabled style="opacity:0.3;cursor:default;"' : '') + ' title="Yukarı Taşı">▲</button>' +
                '<button type="button" class="rt-btn" onclick="moveChronoRow(\'' + lang + '\', ' + idx + ', 1)" ' + (idx === items.length - 1 ? 'disabled style="opacity:0.3;cursor:default;"' : '') + ' title="Aşağı Taşı">▼</button>' +
            '</td>' +
            '<td><input type="text" class="chrono-input" value="' + escapeHtml(item.year || '') + '" oninput="updateChronoItem(\'' + lang + '\', ' + idx + ', \'year\', this.value)" placeholder="Örn: 2024"></td>' +
            '<td><select class="chrono-input badge-pill-select" onchange="updateChronoBadge(\'' + lang + '\', ' + idx + ', this)">' + optHtml + '</select></td>' +
            '<td><input type="text" class="chrono-input" value="' + escapeHtml(item.title || '') + '" oninput="updateChronoItem(\'' + lang + '\', ' + idx + ', \'title\', this.value)" placeholder="Etkinlik / Eser Adı"></td>' +
            '<td><input type="text" class="chrono-input" value="' + escapeHtml(item.url || '') + '" oninput="updateChronoItem(\'' + lang + '\', ' + idx + ', \'url\', this.value)" placeholder="https:// (Opsiyonel)"></td>' +
            '<td><input type="text" class="chrono-input" value="' + escapeHtml(item.venue || '') + '" oninput="updateChronoItem(\'' + lang + '\', ' + idx + ', \'venue\', this.value)" placeholder="Mekan / Detay"></td>' +
            '<td style="text-align:center;">' +
                '<button type="button" class="rt-btn" onclick="deleteChronoRow(\'' + lang + '\', ' + idx + ')" title="Sil" style="color:var(--danger-text); display:inline-flex; align-items:center; justify-content:center;">' +
                    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
                '</button>' +
            '</td>';

        tbody.appendChild(tr);
    });
}

function updateChronoItem(lang, idx, field, val) {
    if (chronoData[lang] && chronoData[lang][idx]) {
        chronoData[lang][idx][field] = val;
    }
}

function updateChronoBadge(lang, idx, selectEl) {
    if (chronoData[lang] && chronoData[lang][idx]) {
        var val = selectEl.value;
        chronoData[lang][idx]['badge'] = val;
        var selectedOpt = selectEl.options[selectEl.selectedIndex];
        chronoData[lang][idx]['badge_label'] = selectedOpt ? selectedOpt.text : 'Sergi';
    }
}

function addChronoRow(lang) {
    if (!chronoData[lang]) chronoData[lang] = [];
    var defaultBadge = 'badge-exhibition';
    var defaultLabel = (lang === 'tr') ? 'Sergi' : 'Exhibition';
    chronoData[lang].unshift({
        year: new Date().getFullYear().toString(),
        badge: defaultBadge,
        badge_label: defaultLabel,
        title: '',
        url: '',
        venue: ''
    });
    renderChronoTable(lang);
}

function moveChronoRow(lang, idx, dir) {
    var items = chronoData[lang];
    var target = idx + dir;
    if (target < 0 || target >= items.length) return;
    var temp = items[idx];
    items[idx] = items[target];
    items[target] = temp;
    renderChronoTable(lang);
}

function deleteChronoRow(lang, idx) {
    adminConfirm(window.ADMIN_LANG === 'en' ? 'This entry will be removed from the list.' : 'Bu madde listeden kaldırılacak.', { ok: window.ADMIN_LANG === 'en' ? 'Remove' : 'Kaldır' }).then(function (ok) {
        if (!ok) return;
        chronoData[lang].splice(idx, 1);
        renderChronoTable(lang);
    });
}

var ABOUT_LANGUAGES = <?= json_encode(array_column($site_languages, 'code')) ?>;

// ── Form Göndermeden Önce Hazırlık ──
function prepareAboutSubmit() {
    ABOUT_LANGUAGES.forEach(function(lang) {
        var narrativeVal = '';
        var rawTa = document.getElementById('raw_textarea_' + lang);
        var edEl = document.getElementById('editable_' + lang);
        if (htmlMode[lang] && rawTa) {
            narrativeVal = rawTa.value;
        } else if (edEl) {
            narrativeVal = edEl.innerHTML;
        }
        var hNarr = document.getElementById('hidden_narrative_' + lang);
        if (hNarr) hNarr.value = narrativeVal;
        var hChrono = document.getElementById('hidden_chrono_items_' + lang);
        if (hChrono) hChrono.value = JSON.stringify(chronoData[lang] || chronoData.en || []);
    });
}

function autoTranslateAboutActiveTab() {
    var activeBtn = document.querySelector('.admin-tabs .tab-btn.active');
    var tabId = activeBtn ? activeBtn.getAttribute('data-tab') : 'tab-en';
    var targetLang = tabId.replace(/^tab-/, '');
    // Daima İngilizce (EN) birincil ana kaynak, sadece EN için TR kullanılır
    var srcLang = (targetLang === 'en') ? 'tr' : 'en';

    var tasks = [];
    function queue(val, apply) {
        if (val && String(val).trim() !== '') {
            tasks.push({ text: String(val), apply: apply });
        }
    }

    var fieldNames = ['title', 'portrait_role', 'contact_kicker', 'contact_title', 'contact_desc', 'contact_loc', 'chrono_title', 'chrono_sub'];
    fieldNames.forEach(function(fn) {
        var sEl = document.querySelector('[name="' + fn + '_' + srcLang + '"]');
        if ((!sEl || sEl.value.trim() === '') && srcLang !== 'tr' && targetLang !== 'tr') {
            sEl = document.querySelector('[name="' + fn + '_tr"]');
        }
        var dEl = document.querySelector('[name="' + fn + '_' + targetLang + '"]');
        if (sEl && dEl && sEl.value.trim() !== '') {
            queue(sEl.value, function(res) { dEl.value = res; });
        }
    });

    var sNarr = document.getElementById('editable_' + srcLang);
    if ((!sNarr || sNarr.innerHTML.trim() === '') && srcLang !== 'tr' && targetLang !== 'tr') {
        sNarr = document.getElementById('editable_tr');
    }
    var dNarr = document.getElementById('editable_' + targetLang);
    if (sNarr && dNarr && sNarr.innerHTML.trim() !== '') {
        queue(sNarr.innerHTML, function(res) { dNarr.innerHTML = res; });
    }

    if (!tasks.length) return;
    var btn = document.getElementById('btnAutoTranslateAbout');
    var orig = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Çevriliyor...'; }

    fetch('<?= url("admin/api-translate.php") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?= csrf_token() ?>',
            source_lang: srcLang,
            target_lang: targetLang,
            texts: tasks.map(function(t) { return t.text; })
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        if (data && data.ok && Array.isArray(data.translations)) {
            var applied = 0;
            var skipped = 0;
            data.translations.forEach(function(trTxt, i) {
                if (tasks[i] && trTxt && !isTranslationWarningText(trTxt)) {
                    tasks[i].apply(trTxt);
                    applied++;
                } else if (tasks[i]) {
                    skipped++;
                }
            });
            if (skipped > 0) {
                alert('✓ ' + applied + ' alan başarıyla çevrildi.\n⚠️ ' + skipped + ' alan kota/hata uyarısı nedeniyle atlandı ve orijinal hali korundu.');
            }
        } else {
            alert('Çeviri sırasında bir hata oluştu: ' + ((data && data.error) || 'Bilinmeyen hata'));
        }
    })
    .catch(function() {
        if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        alert('Çeviri servisine bağlanılamadı.');
    });
}

function isTranslationWarningText(str) {
    if (!str) return false;
    var lower = String(str).toLowerCase().trim();
    return lower.indexOf('mymemory warning') !== -1 ||
           lower.indexOf('used all available free translations') !== -1 ||
           lower.indexOf('translated.net/doc/usagelimits') !== -1 ||
           lower.indexOf('next available in') !== -1 ||
           lower.indexOf('quota finished') !== -1 ||
           lower.indexOf('too many requests') !== -1 ||
           (lower.indexOf('warning:') === 0 && (lower.indexOf('limit') !== -1 || lower.indexOf('free') !== -1 || lower.indexOf('available') !== -1));
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Sayfa Yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    renderChronoTable('tr');
    renderChronoTable('en');
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
