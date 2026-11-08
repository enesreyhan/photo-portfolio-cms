<?php
/**
 * admin/item-edit.php
 * Proje, Seri ve Tekil İçerik Düzenleme ve Ekleme Editörü
 * Modern, Temiz & Tamamen Görsel Editoryal Hikaye Stüdyosu (Normal Format / JSON Blokları)
 * - Markdown ve teknik kod karmaşası tamamen kaldırıldı.
 * - Çift Dilli (TR / EN)
 * - Editoryal Giriş Hikayesi (Sayfa Üstü)
 * - Sürükle-Bırak Hikaye Tuvali: Bölüm Kartları (Koyu/Beyaz), Ara Metinler, Fotoğraf Akışı, Alıntılar, Videolar
 * - Otomatik Klasör Bazlı Yükleme & AVIF/WebP Desteği
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$id = (int)($_GET['id'] ?? 0);
$site_languages = get_active_languages();
$all_categories = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
if (empty($all_categories)) {
    // Yeni kurulum: çalışma bir kategoriye bağlanmalı; önce kategori oluşturulsun
    if ($id === 0 && ($_GET['type'] ?? '') !== 'blog') {
        set_flash('info', __a('Çalışma eklemeden önce bir portfolyo kategorisi (ör. Projeler) oluşturun.', 'Create a portfolio category (e.g. Projects) before adding a work.'));
        header('Location: ' . url('admin/categories.php'));
        exit;
    }
    $all_categories = [
        ['key_slug' => 'portfolio', 'name_tr' => 'Portfolyo', 'name_en' => 'Portfolio'],
    ];
}
$default_type = $_GET['type'] ?? ($all_categories[0]['key_slug'] ?? 'portfolio');
// Bağımsız sayfalar fotoğraf editöründe değil, yazı editöründe düzenlenir
if ($default_type === 'page' && $id === 0) { header('Location: blog-edit.php?type=page'); exit; }

$item = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM `items` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        set_flash('error', 'İçerik bulunamadı.');
        header('Location: portfolio.php');
        exit;
    }
    if ($item['type'] === 'page' && $item['slug'] !== 'aboutme') {
        header('Location: blog-edit.php?id=' . $id);
        exit;
    }
}

// Blog yazıları özel blog-edit.php editöründe yönetilir
if (($id > 0 && isset($item) && ($item['type'] ?? '') === 'blog') || ($id === 0 && ($_GET['type'] ?? '') === 'blog')) {
    $blog_dest = ($id > 0) ? ('blog-edit.php?id=' . $id) : 'blog-edit.php';
    header('Location: ' . $blog_dest);
    exit;
}

// Projenin hedef klasörünü tespit et
$curr_type = $item['type'] ?? $default_type;
$curr_slug = $item['slug'] ?? '';
$project_folder = '';
if ($curr_slug !== '') {
    $type_plural = ($curr_type === 'project') ? 'projects' : (slugify($curr_type) ?: 'single');
    $project_folder = 'content/' . $type_plural . '/' . $curr_slug;
}

// Form Gönderimi (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'CSRF güvenlik hatası.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $type           = trim($_POST['type'] ?? 'project');
    $slug_en        = slugify($_POST['slug_en'] ?? $_POST['slug'] ?? '');
    $slug_tr        = slugify($_POST['slug_tr'] ?? '');
    $title_en       = trim($_POST['title_en'] ?? '');
    $title_tr       = trim($_POST['title_tr'] ?? '');
    $description_en = trim($_POST['description_en'] ?? '');
    $description_tr = trim($_POST['description_tr'] ?? '');
    $content_en     = trim($_POST['content_en'] ?? '');
    $content_tr     = trim($_POST['content_tr'] ?? '');
    $cover_image    = trim($_POST['cover_image'] ?? '');
    $location_tr    = trim($_POST['location_tr'] ?? '');
    $location_en    = trim($_POST['location_en'] ?? '');
    $location       = $location_tr !== '' ? $location_tr : $location_en;
    $year           = trim($_POST['year'] ?? '');
    $weight          = (int)($_POST['weight'] ?? 0);
    $date            = trim($_POST['date'] ?? date('Y-m-d'));
    $status          = in_array($_POST['status'] ?? '', ['published', 'unlisted', 'draft'], true) ? $_POST['status'] : 'published';
    $access_password = trim($_POST['access_password'] ?? '');
    $cover_focal     = trim($_POST['cover_focal'] ?? '50% 50%');
    if (!preg_match('/^\d{1,3}%\s+\d{1,3}%$/', $cover_focal)) {
        $cover_focal = '50% 50%';
    }
    $aliases         = trim($_POST['aliases'] ?? '');

    if ($slug_en === '') {
        $slug_en = slugify($title_en ?: $title_tr);
    }
    if ($slug_tr === '') {
        $slug_tr = slugify($title_tr ?: $title_en);
    }
    $slug = $slug_en ?: $slug_tr;

    // Fotoğraf sayısını hesapla
    $photo_count = 0;
    $tr_obj = json_decode($content_tr, true);
    $en_obj = json_decode($content_en, true);
    $count_tr = 0;
    $count_en = 0;

    if (!empty($tr_obj['blocks']) && is_array($tr_obj['blocks'])) {
        foreach ($tr_obj['blocks'] as $b) {
            if (($b['type'] ?? '') === 'photo') $count_tr++;
        }
    }
    if (!empty($en_obj['blocks']) && is_array($en_obj['blocks'])) {
        foreach ($en_obj['blocks'] as $b) {
            if (($b['type'] ?? '') === 'photo') $count_en++;
        }
    }
    $photo_count = max($count_tr, $count_en);

    // Eğer JSON değilse (eski format yedeği):
    if ($photo_count === 0) {
        if (preg_match_all('/\{\{\s*image\(path=/', $content_en, $matches)) {
            $photo_count = count($matches[0]);
        } elseif (preg_match_all('/\{\{\s*image\(path=/', $content_tr, $matches)) {
            $photo_count = count($matches[0]);
        }
    }

    if ($id > 0) {
        // Güncelle
        $stmt = $pdo->prepare("
            UPDATE `items` SET
                `type` = ?, `slug` = ?, `slug_en` = ?, `slug_tr` = ?,
                `title_en` = ?, `title_tr` = ?,
                `description_en` = ?, `description_tr` = ?,
                `content_en` = ?, `content_tr` = ?,
                `cover_image` = ?, `cover_focal` = ?, `location` = ?, `location_en` = ?, `location_tr` = ?, `year` = ?,
                `photo_count` = ?, `weight` = ?, `date` = ?,
                `status` = ?, `access_password` = ?, `aliases` = ?, `updated_at` = ?
            WHERE `id` = ?
        ");
        $stmt->execute([
            $type, $slug, $slug_en, $slug_tr,
            $title_en, $title_tr,
            $description_en, $description_tr,
            $content_en, $content_tr,
            $cover_image, $cover_focal, $location, $location_en, $location_tr, $year,
            $photo_count, $weight, $date,
            $status, $access_password, $aliases, date('Y-m-d H:i:s'),
            $id
        ]);
        set_flash('success', 'İçerik başarıyla güncellendi.');
    } else {
        // Yeni Ekle
        $stmt = $pdo->prepare("
            INSERT INTO `items` (
                `type`, `slug`, `slug_en`, `slug_tr`, `title_en`, `title_tr`,
                `description_en`, `description_tr`,
                `content_en`, `content_tr`,
                `cover_image`, `cover_focal`, `location`, `location_en`, `location_tr`, `year`,
                `photo_count`, `weight`, `date`,
                `status`, `access_password`, `aliases`, `created_at`, `updated_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            $type, $slug, $slug_en, $slug_tr,
            $title_en, $title_tr,
            $description_en, $description_tr,
            $content_en, $content_tr,
            $cover_image, $cover_focal, $location, $location_en, $location_tr, $year,
            $photo_count, $weight, $date,
            $status, $access_password, $aliases, $now, $now
        ]);
        $id = (int)$pdo->lastInsertId();
        set_flash('success', 'Yeni içerik başarıyla oluşturuldu.');
    }

    // Ekstra dillerin (hy, ku, fr, de vb.) sütunlarını kaydet
    foreach ($site_languages as $sl) {
        $lc = $sl['code'];
        if (in_array($lc, ['en', 'tr'], true)) continue;
        ensure_language_columns($lc);
        $l_title = trim($_POST['title_' . $lc] ?? '');
        $l_slug  = slugify($_POST['slug_' . $lc] ?? '');
        if ($l_slug === '' && $l_title !== '') {
            $l_slug = slugify($l_title);
        }
        $l_desc  = trim($_POST['description_' . $lc] ?? '');
        $l_cont  = trim($_POST['content_' . $lc] ?? '');
        $l_loc   = trim($_POST['location_' . $lc] ?? '');
        try {
            $st_extra = $pdo->prepare("UPDATE `items` SET `slug_{$lc}` = ?, `title_{$lc}` = ?, `description_{$lc}` = ?, `content_{$lc}` = ?, `location_{$lc}` = ? WHERE `id` = ?");
            $st_extra->execute([$l_slug, $l_title, $l_desc, $l_cont, $l_loc, $id]);
        } catch (\Throwable $e) {}
    }

    if (!empty($_POST['return']) && $_POST['return'] === 'homepage') {
        header('Location: homepage.php');
    } elseif (!empty($_POST['return']) && $_POST['return'] === 'pages' && $id > 0) {
        header('Location: pages.php?page=' . rawurlencode('item-' . $id));
    } elseif ($type === 'blog') {
        header('Location: blog.php');
    } else {
        header('Location: portfolio.php?cat=' . urlencode($type));
    }
    exit;
}

$is_new = ($id === 0);
$return_to = trim($_GET['return'] ?? ($_POST['return'] ?? ''));
$type_labels = [
    'blog' => __a('menu_blog', 'Blog / Yazılar'),
];
$type_single_names = [
    'blog' => __a('blog_post', 'Blog Yazısı'),
];
foreach ($all_categories as $cat_row) {
    $ck = $cat_row['key_slug'];
    $cat_name = get_category_localized_name($cat_row, $admin_lang);
    $type_labels[$ck] = $cat_name;
    $type_single_names[$ck] = $cat_name;
}

$title_in_lang = $item
    ? (item_field($item, 'title', $admin_lang) ?: ($admin_lang === 'tr' ? ($item['title_tr'] ?: $item['title_en']) : ($item['title_en'] ?: $item['title_tr'])))
    : '';
$category_label = $type_single_names[$curr_type] ?? ($type_labels[$curr_type] ?? __a('menu_portfolio', 'Portfolyo'));

$admin_title = $is_new ? (__a('add_new', 'Yeni Ekle') . ' (' . $category_label . ')') : (__a('edit', 'Düzenle') . ': ' . $title_in_lang);
$breadcrumb_root = ($return_to === 'homepage') ? __a('menu_homepage', 'Ana Sayfa') : ($type_labels[$curr_type] ?? __a('menu_portfolio', 'Portfolyo'));
$breadcrumb_page = $is_new ? __a('add_new', 'Yeni Ekle') : $title_in_lang;

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/image_modal.php';
?>

<style>
/* ── Normal Editoryal Stüdyo Stilleri (Tema Uyumlu) ── */
.studio-tools-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 500;
    padding: 7px 12px;
    border-radius: 6px;
    border: 1px solid var(--border-subtle);
    background: var(--bg-surface);
    color: var(--text-main);
    cursor: pointer;
    transition: all 0.15s;
}

.studio-tools-btn:hover {
    background: var(--bg-hover);
    border-color: var(--border-light);
}


/* Sürükle-Bırak Tuvali */
.story-canvas {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 14px;
    min-height: 80px;
}

/* Genel Blok Kartı (Stravia Temiz SaaS Kartları) */
.story-card {
    grid-column: 1 / -1;
    background: var(--bg-surface);
    border: 1px solid var(--border-card);
    border-radius: 10px;
    padding: 16px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

/* B5: Diptik (Yan Yana İkili Fotoğraf Eşleştirme) Stüdyo Görünümü */
.story-card.is-diptych-pair {
    grid-column: span 1;
    border-color: rgba(124, 58, 237, 0.45);
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.04) 0%, var(--bg-surface) 100%);
}
@media (max-width: 960px) {
    .story-card.is-diptych-pair {
        grid-column: 1 / -1;
    }
}
.diptych-badge {
    background: rgba(124, 58, 237, 0.14);
    color: var(--violet-text);
    border: 1px solid rgba(124, 58, 237, 0.32);
    font-size: 12px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
[data-theme="dark"] .diptych-badge {
    background: rgba(167, 139, 250, 0.18);
    color: #c4b5fd;
    border-color: rgba(167, 139, 250, 0.35);
}

/* B3: Akıllı Odak Noktası (Focal Point Picker) İşaretçisi */
.focal-crosshair-pin {
    position: absolute;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    background: rgba(37, 99, 235, 0.85);
    box-shadow: 0 0 0 2px rgba(0,0,0,0.45), 0 2px 8px rgba(0,0,0,0.4);
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 4;
    transition: left 0.12s ease, top 0.12s ease;
}
.focal-crosshair-pin::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #ffffff;
    transform: translate(-50%, -50%);
}
.story-card-thumb {
    cursor: crosshair;
}
.thumb-focal-dot {
    position: absolute;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1.5px solid #fff;
    background: rgba(253, 208, 0, 0.92);
    box-shadow: 0 1px 4px rgba(0,0,0,0.6);
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 3;
}

.story-card.is-cover-card {
    border-color: #2563eb;
    background: var(--accent-blue-subtle, rgba(37, 99, 235, 0.08));
    box-shadow: 0 0 0 1px #2563eb, 0 4px 12px rgba(37, 99, 235, 0.08);
}

.story-card:hover {
    border-color: var(--border-light);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}

.story-card.sortable-ghost {
    opacity: 0.35;
    border: 1.5px dashed var(--text-main);
    background: var(--bg-canvas-subtle);
}

.story-card.sortable-chosen {
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    border-color: var(--primary);
}

.drag-handle {
    cursor: grab;
    color: var(--text-sub);
    width: 24px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s ease;
    user-select: none;
}

.drag-handle:hover {
    background: var(--bg-hover);
    color: var(--text-main);
}

.drag-handle:active {
    cursor: grabbing;
}

.story-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}

.story-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 2px;
}

.story-badge {
    font-size: 12px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    letter-spacing: 0.02em;
    background: var(--bg-canvas-subtle);
    color: var(--text-muted);
    border: 1px solid var(--border-subtle);
}

.story-badge.badge-photo {
    background: var(--bg-surface-2);
    color: var(--text-main);
    border-color: var(--border-subtle);
}

.story-badge.badge-chapter {
    background: var(--primary);
    color: var(--primary-text);
    border-color: var(--primary);
}

.story-badge.badge-text {
    background: var(--bg-surface-2);
    color: var(--text-muted);
    border-color: var(--border-subtle);
}

.story-badge.badge-quote {
    background: var(--bg-surface-2);
    color: var(--text-muted);
    border-color: var(--border-subtle);
}

.story-badge.badge-youtube {
    background: rgba(239, 68, 68, 0.12);
    color: var(--danger-text);
    border-color: rgba(239, 68, 68, 0.3);
}

/* Fotoğraf Önizleme */
.story-card-thumb {
    width: 168px;
    height: 112px;
    background: var(--bg-surface-2);
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
    border: 1px solid var(--border-card);
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.story-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Diptik kartı yarım genişlikte: fotoğraf üstte tam genişlik, ayarlar altta. */
.story-card.is-diptych-pair { flex-wrap: wrap; }
.story-card.is-diptych-pair .story-card-thumb { width: calc(100% - 38px); height: auto; aspect-ratio: 3 / 2; }
.story-card.is-diptych-pair .story-card-body { flex-basis: 100%; }
.story-card-top { flex-wrap: wrap; }
.layout-pill { white-space: nowrap; }

.story-card-path {
    font-size: 12px;
    color: var(--text-muted);
    font-family: var(--font-mono);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 240px;
}

/* Hizalama Seçicileri (Modern Segmented Controls) */
.story-card-layout-pills,
.theme-pill-group {
    display: inline-flex;
    gap: 2px;
    align-items: center;
    background: var(--bg-canvas-subtle);
    padding: 2px;
    border-radius: 6px;
    border: 1px solid var(--border-subtle);
}

.layout-pill,
.theme-pill {
    font-size: 12px;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: 6px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.12s ease;
    line-height: 1.3;
}

.layout-pill:hover,
.theme-pill:hover {
    color: var(--text-main);
}

.layout-pill.active {
    background: var(--bg-surface);
    color: var(--text-main);
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    border-color: var(--border-subtle);
}

.theme-pill.active-dark {
    background: #0f172a;
    color: #ffffff;
    font-weight: 600;
    border-color: #0f172a;
}

.theme-pill.active-white {
    background: #ffffff;
    color: var(--text-main);
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    border-color: rgba(0,0,0,0.04);
}

/* Kart Aksiyon Butonları */
.btn-card-del {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--text-sub);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.btn-card-del:hover {
    color: var(--danger-text);
    background: var(--danger-soft);
    border-color: #fee2e2;
}

/* ── KAPSAMLI ZENGİN METİN EDİTÖRÜ (CMS RICH TEXT COMPONENT) ── */
.rich-editor-wrap {
    background: var(--bg-surface);
    border: 1px solid var(--border-light);
    border-radius: 6px;
    overflow: hidden;
    transition: border-color 0.15s, box-shadow 0.15s;
    display: flex;
    flex-direction: column;
}

.rich-editor-wrap:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}

.rich-toolbar {
    background: var(--bg-canvas-subtle);
    border-bottom: 1px solid var(--border-subtle);
    padding: 6px 8px;
    display: flex;
    align-items: center;
    gap: 3px;
    flex-wrap: wrap;
    user-select: none;
}

.rt-btn {
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    padding: 3px 6px;
    font-size: 12px;
    line-height: 1;
    color: var(--text-sub);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 26px;
    transition: all 0.12s;
    font-family: inherit;
    box-sizing: border-box;
}

.rt-btn:hover {
    background: var(--bg-hover);
    color: var(--text-main);
    border-color: var(--border-subtle);
}

.rt-btn:active {
    background: var(--bg-active);
}

.rt-btn svg {
    display: block;
    stroke: currentColor;
}

.rt-select {
    font-size: 12px;
    font-weight: 600;
    padding: 2px 6px;
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    background: var(--bg-surface);
    color: var(--text-main);
    cursor: pointer;
    outline: none;
    height: 26px;
    line-height: 1;
    box-sizing: border-box;
}

.rt-select:hover {
    border-color: #94a3b8;
}

.rt-select:focus {
    border-color: #0284c7;
}

.rt-sep {
    width: 1px;
    height: 16px;
    background: var(--border-subtle);
    margin: 0 3px;
}

.rt-bold { font-weight: 800; font-size: 13px; }
.rt-italic { font-style: italic; font-size: 13px; font-family: serif; }
.rt-underline { text-decoration: underline; font-size: 13px; }
.rt-strike { text-decoration: line-through; font-size: 13px; }

.rich-editable {
    padding: 12px 14px;
    min-height: 75px;
    max-height: 420px;
    overflow-y: auto;
    font-size: 14px;
    line-height: 1.7;
    color: var(--text-main);
    outline: none;
    background: var(--bg-surface);
    word-break: break-word;
}

.rich-editable:empty::before {
    content: attr(data-placeholder);
    color: var(--text-sub);
    pointer-events: none;
    display: block;
}

.rich-editable p {
    margin: 0 0 10px 0;
}

.rich-editable p:last-child {
    margin-bottom: 0;
}

.rich-editable h2,
.rich-editable h3,
.rich-editable h4 {
    margin: 14px 0 6px 0;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.3;
}
.rich-editable h2 { font-size: 18px; }
.rich-editable h3 { font-size: 16px; }
.rich-editable h4 { font-size: 14px; }

.rich-editable blockquote {
    margin: 10px 0;
    padding: 6px 12px;
    border-left: 3px solid #2563eb;
    background: var(--bg-canvas-subtle);
    border-radius: 6px;
    font-style: italic;
    color: var(--text-muted);
}

.rich-editable ul,
.rich-editable ol {
    margin: 6px 0 10px 20px;
    padding: 0;
}

.rich-editable li {
    margin-bottom: 3px;
}

.rich-editable hr {
    border: none;
    border-top: 1px solid var(--border-subtle);
    margin: 14px 0;
}

.rich-editable del,
.rich-editable s,
.rich-editable strike {
    text-decoration: line-through;
    opacity: 0.75;
}

.rich-editable a {
    color: #0284c7;
    text-decoration: underline;
}

.rich-editable strong, .rich-editable b {
    font-weight: 700;
}

/* ── 14 inç MacBook & Kompakt Ekranlarda Mükemmel Sığma ve Taşma Koruması ── */
.admin-content {
    padding: 20px 24px 48px 24px !important;
    max-width: 100% !important;
    box-sizing: border-box;
}

.item-edit-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 18px;
    align-items: start;
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
    transition: all 0.22s ease;
}

.item-main-column {
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
}

.item-right-column {
    min-width: 0;
    width: 300px;
    box-sizing: border-box;
}

.item-right-column .card {
    box-sizing: border-box;
    width: 100%;
}

@media (max-width: 1280px) {
    .item-edit-grid {
        grid-template-columns: minmax(0, 1fr) 280px;
        gap: 14px;
    }
    .item-right-column {
        width: 280px;
    }
}

@media (max-width: 1024px) {
    .item-edit-grid {
        grid-template-columns: 1fr;
    }
    .item-right-column {
        width: 100%;
    }
}

body.right-panel-collapsed .item-edit-grid {
    grid-template-columns: 1fr !important;
}

body.right-panel-collapsed .item-right-column {
    display: none !important;
}

.floating-right-panel-btn {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 999;
    display: none;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.22);
}

body.right-panel-collapsed .floating-right-panel-btn {
    display: inline-flex !important;
}

/* ── Tek Çatı Altında Çok Dilli Sekme ve Düğmeler (Bilingual Controls) ── */
.canvas-lang-switcher {
    display: inline-flex;
    align-items: center;
    background: var(--bg-canvas-subtle);
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    padding: 3px;
    gap: 2px;
}

.canvas-lang-pill {
    background: transparent;
    border: none;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    border-radius: 6px;
    cursor: pointer;
    line-height: 1.2;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.canvas-lang-pill:hover {
    color: var(--text-main);
}

.canvas-lang-pill.active {
    background: var(--bg-surface);
    color: var(--text-main);
    font-weight: 700;
    box-shadow: var(--card-shadow);
}

.lang-pill-group {
    display: inline-flex;
    align-items: center;
    background: var(--bg-canvas-subtle);
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    padding: 2px;
    gap: 2px;
}

.lang-pill {
    background: transparent;
    border: none;
    padding: 3px 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    border-radius: 6px;
    cursor: pointer;
    line-height: 1.2;
    transition: all 0.15s ease;
}

.lang-pill:hover {
    color: var(--text-main);
}

.lang-pill.active {
    background: var(--bg-surface);
    color: var(--text-main);
    font-weight: 700;
    box-shadow: var(--card-shadow);
}
</style>

<script>
if (localStorage.getItem('item_edit_right_collapsed') === 'true') {
    document.body.classList.add('right-panel-collapsed');
}
</script>

<?php
// Ortak düzenleyici üst çubuğu: geri bağlantısı düzenleyiciye nereden gelindiyse oraya döner
if ($return_to === 'pages') { $eb_back = ['pages.php' . ($id > 0 ? '?page=' . rawurlencode('item-' . $id) : ''), __a('Sayfalar', 'Pages')]; }
elseif ($return_to === 'homepage') { $eb_back = ['homepage.php', __a('Ana Sayfa Seçkisi', 'Homepage selection')]; }
elseif ($curr_type === 'blog') { $eb_back = [url('admin/blog.php'), __a('Blog', 'Blog')]; }
else { $eb_back = [url('admin/portfolio.php?cat=' . urlencode($curr_type)), __a('Portfolyo', 'Portfolio')]; }
ob_start(); ?>
        <button type="button" id="focusModeBtn" class="btn btn-secondary" onclick="toggleFocusMode()" title="<?= e(__a('Sol menüyü ve sağ paneli gizler', 'Hides the sidebar and the side panel')) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
            <span id="focusModeLabel"><?= __a('Odaklan', 'Focus') ?></span>
        </button>
        <button type="button" id="rightPanelToggleBtn" class="btn btn-secondary" onclick="toggleItemRightPanel()" title="<?= e(__a('Sağ paneli gizle / aç (Ctrl+])', 'Hide / show the side panel (Ctrl+])')) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/></svg>
            <span id="rightPanelToggleLabel"><?= __a('Sağ Panel', 'Side Panel') ?></span>
        </button>
<?php
editor_bar([
    'back_href'  => $eb_back[0],
    'back_label' => $eb_back[1],
    'status'     => $is_new ? null : ($item['status'] ?? 'draft'),
    'form'       => 'mainItemForm',
    'preview'    => $is_new ? '' : item_url($item, $admin_lang),
    'extra'      => ob_get_clean(),
    'title'      => $is_new ? (__a('add_new', 'Yeni Ekle') . ' - ' . $category_label) : $title_in_lang,
    'sub'        => $is_new ? __a('Yeni fotoğraf serisi, galeri ve editoryal hikaye anlatımı oluşturun.', 'Create a new photo series, gallery, and editorial narrative.') : implode(' • ', array_map(function ($sl) use ($item) { return '/' . $sl['code'] . '/' . item_slug($item, $sl['code']) . '/'; }, $site_languages)),
]);
?>

<?php
$lb_preview = [];
if (!$is_new) { foreach ($site_languages as $sl) { $lb_preview[$sl['code']] = item_url($item, $sl['code']); } }
lang_bar([
    'scope'        => '#mainItemForm',
    'click'        => '.canvas-lang-pill[data-lang="{lang}"]',
    'hide'         => '.canvas-lang-switcher, #btnAutoTranslateCanvas, .studio-tools-btn[onclick="copyFromSourceLang()"]',
    'copy_fn'      => 'copyFromSourceLang',
    'translate_fn' => 'autoTranslateCurrentLang',
    'preview'      => $lb_preview,
]);
?>
<form method="POST" id="mainItemForm" class="save-form item-edit-grid">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="return" value="<?= e($return_to) ?>">
    <!-- Gizli Yapılandırılmış İçerik Alanları (Tüm Diller) -->
    <?php foreach ($site_languages as $sl): ?>
    <input type="hidden" name="content_<?= e($sl['code']) ?>" id="input_content_<?= e($sl['code']) ?>" value="">
    <?php endforeach; ?>

    <!-- ── SOL SÜTUN: Başlık, Açıklama ve Editoryal Stüdyo ── -->
    <div class="item-main-column">
        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-sub);"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg>
                    <div>
                        <h3 class="card-title" id="canvasStudioTitle" style="margin:0; font-size:16px; font-weight:700;"><?= __a('Editoryal Stüdyo & Fotoğraf Tuvali', 'Editorial Studio & Photo Canvas') ?></h3>
                        <span id="canvasStudioSub" style="font-size:12px; color:var(--text-muted);"><?= __a('Fotoğraf sırası ve yerleşimleri tüm dillerde tek tuvalde ortaktır', 'Photo sequence and layouts are shared across all languages on a single canvas') ?></span>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <div class="canvas-lang-switcher" title="<?= __a('Tuvaldeki tüm metin alanlarının dil görünümünü değiştir', 'Change language view of all text fields on canvas') ?>">
                        <span style="font-size:12px; font-weight:600; color:var(--text-sub); margin-right:4px;"><?= __a('Dil:', 'Language:') ?></span>
                        <?php foreach ($site_languages as $idx_l => $sl):
                            $lc = $sl['code'];
                            $is_first_tr = ($lc === 'tr');
                        ?>
                        <button type="button" class="canvas-lang-pill <?= $is_first_tr ? 'active' : '' ?>" data-lang="<?= e($lc) ?>" onclick="setGlobalCanvasLang('<?= e($lc) ?>')">
                            <?= e($sl['flag'] ?? '🌐') ?> <?= e($sl['name_native'] ?: strtoupper($lc)) ?>
                            <?php if (empty($sl['is_active'])): ?><span style="font-size:12px; opacity:0.65;">(<?= __a('Pasif', 'Inactive') ?>)</span><?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="studio-tools-btn" onclick="copyFromSourceLang()" title="<?= __a('Aktif dildeki boş alanları ve fotoğraf alt yazılarını kaynak dilden (TR/EN) kopyala', 'Copy empty fields and photo captions from source language (TR/EN)') ?>" style="font-size:12px; padding:5px 10px; background:var(--bg-surface-2);">
                        <?= __a('⇄ Diğer Dilden Kopyala', '⇄ Copy from Other Lang') ?>
                    </button>
                    <button type="button" id="btnAutoTranslateCanvas" class="studio-tools-btn" onclick="autoTranslateCurrentLang()" title="<?= __a('Başlık, açıklama, giriş hikayesi, bölüm başlıkları ve tüm fotoğraf alt yazılarını seçili dile otomatik çevir', 'Auto-translate title, description, intro story, chapter titles, and captions to selected language') ?>" style="font-size:12px; padding:5px 11px; background:var(--info-soft); color:var(--accent-blue); border-color:var(--info-border); font-weight:600;">
                        <?= __a('Bu Dile Otomatik Çevir', 'Auto-Translate to This Lang') ?>
                    </button>
                    <button type="button" class="studio-tools-btn" onclick="clearAllFieldsCurrentLang()" title="<?= __a('Seçili dildeki başlık, açıklama ve blokları siler', 'Clears title, description and blocks in the active language') ?>" style="font-size:12px; padding:5px 10px;">
                        <?= __a('Bu dili boşalt', 'Clear this language') ?>
                    </button>
                </div>
            </div>

            <!-- Otomatik Uyarı Tespit & Tek Tıkla Temizleme Bildirim Barı -->
            <div id="bannerTranslationWarning" style="display:none; margin:14px 20px 0 20px; padding:10px 14px; background:var(--danger-soft); border:1px solid var(--danger-border); border-radius:6px; align-items:center; justify-content:space-between; gap:12px; font-size:13px; color:var(--danger-text);">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:18px;">⚠️</span>
                    <div>
                        <strong id="bannerWarningTitle"><?= __a('Hatalı Çeviri / Kota Uyarısı Tespit Edildi!', 'Translation Error / Quota Warning Detected!') ?></strong>
                        <div id="bannerWarningDesc" style="font-size:12px; color:var(--danger-text); margin-top:2px;">
                            <?= __a('Bu dilde MYMEMORY WARNING içeren alanlar bulundu. Tek tıkla temizleyebilirsiniz.', 'Fields containing MYMEMORY WARNING found in this language. You can clear them with one click.') ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
                    <button type="button" onclick="purgeTranslationWarnings(true)" style="background:#e11d48; color:#fff; border:none; padding:5px 12px; font-size:12px; font-weight:600; border-radius:6px; cursor:pointer;">
                        <?= __a('Hatalı Uyarıları Tek Tıkla Temizle', 'Clear Error Warnings Now') ?>
                    </button>
                    <button type="button" onclick="document.getElementById('bannerTranslationWarning').style.display='none'" style="background:transparent; border:none; color:var(--danger-text); cursor:pointer; font-size:16px; padding:2px 6px;">✕</button>
                </div>
            </div>

            <div class="card-body">
                <!-- 1. BAŞLIK -->
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:6px;">
                        <label class="form-label" id="labelFieldTitle" style="margin:0; font-weight:700;"><?= __a('Başlık', 'Title') ?></label>
                    </div>
                    <?php foreach ($site_languages as $sl): $lc = $sl['code']; ?>
                    <input type="text" name="title_<?= e($lc) ?>" id="input_title_<?= e($lc) ?>" class="form-control field-title-<?= e($lc) ?>" style="<?= $lc === 'tr' ? '' : 'display:none;' ?>" value="<?= e($item['title_' . $lc] ?? '') ?>" placeholder="<?= $lc === 'en' ? 'Enter title...' : ($lc === 'tr' ? 'Başlık girin...' : e($sl['name_native'] . '...')) ?>" onblur="autoSlug()">
                    <?php endforeach; ?>
                </div>

                <!-- 2. KISA AÇIKLAMA -->
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:6px;">
                        <label class="form-label" id="labelFieldDesc" style="margin:0; font-weight:700;"><?= __a('Kısa Açıklama', 'Short Description') ?></label>
                    </div>
                    <?php foreach ($site_languages as $sl): $lc = $sl['code']; ?>
                    <textarea name="description_<?= e($lc) ?>" id="input_description_<?= e($lc) ?>" class="form-control field-description-<?= e($lc) ?>" style="<?= $lc === 'tr' ? '' : 'display:none;' ?>" rows="2" placeholder="<?= $lc === 'en' ? 'Short description or summary...' : ($lc === 'tr' ? 'Kısa açıklama veya özet girin...' : e($sl['name_native'] . '...')) ?>"><?= e($item['description_' . $lc] ?? '') ?></textarea>
                    <?php endforeach; ?>
                </div>

                <!-- 2.1 KONUM / COĞRAFYA & YIL / DÖNEM -->
                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:16px; margin-bottom:18px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:6px;">
                            <label class="form-label" id="labelFieldLoc" style="margin:0; font-weight:700;"><?= __a('Konum / Coğrafya', 'Location / Geography') ?></label>
                        </div>
                        <?php foreach ($site_languages as $sl):
                            $lc = $sl['code'];
                            $loc_val = $item['location_' . $lc] ?? (($lc === 'tr' || $lc === 'en') ? ($item['location'] ?? '') : '');
                        ?>
                        <input type="text" name="location_<?= e($lc) ?>" id="input_location_<?= e($lc) ?>" class="form-control field-location-<?= e($lc) ?>" style="<?= $lc === 'tr' ? '' : 'display:none;' ?>" value="<?= e($loc_val) ?>" placeholder="<?= $lc === 'en' ? 'Location / Geography...' : ($lc === 'tr' ? 'Konum / Coğrafya girin...' : e($sl['name_native'] . '...')) ?>">
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" id="labelFieldYear" style="margin-bottom:6px; font-weight:700;"><?= __a('Yıl / Dönem', 'Year / Period') ?></label>
                        <input type="text" name="year" id="input_year" class="form-control" value="<?= e($item['year'] ?? '') ?>" placeholder="Örn: 2025 veya 2018–2024">
                    </div>
                </div>

                <!-- 3. ÜST EDİTORYAL GİRİŞ HİKAYESİ KARTI (LEAD NARRATIVE) -->
                <div class="form-group" style="background:var(--bg-surface); border:1px solid var(--border-card); border-radius:10px; padding:16px; margin-bottom:18px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:8px;">
                        <label class="form-label" style="margin:0; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
                            <span id="labelFieldIntro"><?= __a('Editoryal Giriş Hikayesi', 'Editorial Intro Story') ?></span>
                        </label>
                    </div>
                    <p id="descFieldIntro" style="font-size:12px; color:var(--text-muted); margin:0 0 10px 0; line-height:1.5;">
                        <?= __a('Fotoğraflar ve bölümler başlamadan önce, sayfa başlığının hemen altında yer alan ana hikaye ve editoryal giriş metni.', 'Main story and editorial intro text appearing directly beneath the title before photos and sections.') ?>
                    </p>
                    <div class="rich-editor-wrap">
                        <div class="rich-toolbar">
                            <select class="rt-select" onfocus="saveCurrentSelection()" onmousedown="saveCurrentSelection()" onchange="execRichBlockFormat(this)" title="<?= __a('Paragraf veya Başlık Formatı', 'Paragraph or Heading Format') ?>">
                                <option value="p"><?= __a('Paragraf', 'Paragraph') ?></option>
                                <option value="h2"><?= __a('Büyük Başlık (H2)', 'Heading 2 (H2)') ?></option>
                                <option value="h3"><?= __a('Bölüm Başlığı (H3)', 'Chapter Title (H3)') ?></option>
                                <option value="h4"><?= __a('Alt Başlık (H4)', 'Subheading (H4)') ?></option>
                                <option value="blockquote"><?= __a('Alıntı Bloğu', 'Quote Block') ?></option>
                            </select>
                            <select class="rt-select" onfocus="saveCurrentSelection()" onmousedown="saveCurrentSelection()" onchange="execRichFontSize(this)" title="<?= __a('Yazı Boyutu', 'Font Size') ?>">
                                <option value="" disabled selected><?= __a('Boyut', 'Size') ?></option>
                                <option value="12px"><?= __a('12px - Küçük / Not', '12px - Small / Note') ?></option>
                                <option value="14px"><?= __a('14px - Standart', '14px - Standard') ?></option>
                                <option value="16px"><?= __a('16px - Okuma', '16px - Reading') ?></option>
                                <option value="18px"><?= __a('18px - Vurgulu', '18px - Emphasis') ?></option>
                                <option value="22px"><?= __a('22px - Başlık', '22px - Subheading') ?></option>
                                <option value="28px"><?= __a('28px - Manşet', '28px - Main Heading') ?></option>
                                <option value="inherit"><?= __a('Sıfırla (Varsayılan)', 'Reset (Default)') ?></option>
                            </select>
                            <div class="rt-sep"></div>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'undo')" title="<?= __a('Geri Al (Ctrl+Z)', 'Undo (Ctrl+Z)') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'redo')" title="<?= __a('İleri Al (Ctrl+Y)', 'Redo (Ctrl+Y)') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
                            </button>
                            <div class="rt-sep"></div>
                            <button type="button" class="rt-btn rt-bold" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'bold')" title="<?= __a('Kalın (Ctrl+B)', 'Bold (Ctrl+B)') ?>"><strong>B</strong></button>
                            <button type="button" class="rt-btn rt-italic" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'italic')" title="<?= __a('İtalik (Ctrl+I)', 'Italic (Ctrl+I)') ?>"><em>I</em></button>
                            <button type="button" class="rt-btn rt-underline" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'underline')" title="<?= __a('Altı Çizili (Ctrl+U)', 'Underline (Ctrl+U)') ?>"><u>U</u></button>
                            <button type="button" class="rt-btn rt-strike" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'strikeThrough')" title="<?= __a('Üstü Çizili', 'Strikethrough') ?>"><s>S</s></button>
                            <div class="rt-sep"></div>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'justifyLeft')" title="<?= __a('Sola Yasla', 'Align Left') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'justifyCenter')" title="<?= __a('Ortala', 'Center') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'justifyRight')" title="<?= __a('Sağa Yasla', 'Align Right') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'justifyFull')" title="<?= __a('İki Yana Yasla', 'Justify') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                            </button>
                            <div class="rt-sep"></div>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'insertUnorderedList')" title="<?= __a('Madde İmleri Listesi', 'Bullet List') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1.5" fill="currentColor"/><circle cx="3.5" cy="12" r="1.5" fill="currentColor"/><circle cx="3.5" cy="18" r="1.5" fill="currentColor"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'insertOrderedList')" title="<?= __a('Numaralı Liste', 'Numbered List') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'insertHorizontalRule')" title="<?= __a('Ayırıcı Yatay Çizgi', 'Horizontal Rule') ?>">―</button>
                            <div class="rt-sep"></div>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichLink(this)" title="<?= __a('Bağlantı / Link Ekle', 'Insert Link') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'unlink')" title="<?= __a('Bağlantıyı Kaldır', 'Remove Link') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.16 11.75-1.72 1.71a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                            </button>
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, 'removeFormat')" title="<?= __a('Biçimlendirmeyi Temizle', 'Clear Formatting') ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/></svg>
                            </button>
                        </div>
                        <?php foreach ($site_languages as $sl): $lc = $sl['code']; ?>
                        <div class="rich-editable field-intro-<?= e($lc) ?>" id="introEditable_<?= e($lc) ?>" contenteditable="true" style="min-height:110px; <?= $lc === 'tr' ? '' : 'display:none;' ?>" data-placeholder="<?= ($lc === 'en' ? 'Editorial intro narrative and story paragraphs...' : 'Editoryal giriş anlatısı ve hikaye paragrafları...') ?>" onpaste="handleRichPaste(event)" oninput="updateRichIntro('<?= e($lc) ?>', this)"></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 4. HİKAYE AKIŞI ARAÇ ÇUBUĞU (YENİ BLOK EKLE & DROPZONE) -->
                <div class="story-toolbar" style="background:var(--bg-surface); border:1px solid var(--border-card); border-radius:10px; padding:14px; margin-bottom:14px; box-shadow:var(--card-shadow);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                        <div style="font-weight:700; font-size:13px; display:flex; align-items:center; gap:8px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg>
                            <span><?= __a('Editoryal Hikaye & Fotoğraf Akışı', 'Editorial Story & Photo Stream') ?></span>
                            <span class="badge" id="blockCountBadge" style="background:var(--bg-canvas-subtle); color:var(--text-muted); font-size:12px; font-weight:600;">0 <?= __a('Blok', 'Blocks') ?></span>
                        </div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <button type="button" class="studio-tools-btn" id="canvasGridToggle" aria-pressed="false" title="<?= e(__a('Serinin tamamını küçük resimlerle tek ekranda gösterir; sıralamak için sürükleyin', 'Shows the whole series as thumbnails on one screen; drag to reorder')) ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                                <span><?= __a('Izgara', 'Grid') ?></span>
                            </button>
                            <button type="button" class="studio-tools-btn" onclick="addTextBlock()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                <span><?= __a('+ Ara Metin', '+ Subtext') ?></span>
                            </button>
                            <button type="button" class="studio-tools-btn" onclick="addChapterBlock()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                <span><?= __a('+ Bölüm Kartı', '+ Chapter') ?></span>
                            </button>
                            <button type="button" class="studio-tools-btn" onclick="addQuoteBlock()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg>
                                <span><?= __a('+ Alıntı', '+ Quote') ?></span>
                            </button>
                            <button type="button" class="studio-tools-btn" onclick="addYoutubeBlock()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                <span><?= __a('+ Video', '+ Video') ?></span>
                            </button>
                            <button type="button" class="studio-tools-btn" onclick="openMediaModalForProject()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <span><?= __a('Kütüphaneden Seç', 'Select from Library') ?></span>
                            </button>
                            <label class="studio-tools-btn" style="cursor:pointer; margin:0; background:var(--primary); color:var(--primary-text); border-color:var(--primary); font-weight:600;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                                <span><?= __a('Projeye Yükle', 'Upload to Project') ?></span>
                                <input type="file" multiple accept="image/*" style="display:none;" onchange="uploadDirectToProject(this.files)">
                            </label>
                        </div>
                    </div>

                    <!-- Projeye Özel Hızlı Sürükle-Bırak Alanı -->
                    <div id="projectDropZone" style="border:2px dashed var(--border-light); border-radius:6px; padding:16px; text-align:center; background:var(--bg-canvas-subtle); cursor:pointer;">
                        <span style="font-size:12px; color:var(--text-muted);">
                            <?= __a('Fotoğrafları buraya sürükleyin', 'Drag photos here') ?> <strong>(<?= __a('Doğrudan bu projenin klasörüne yüklenir & AVIF/WebP yapılır', 'Directly uploaded to this project folder & converted to AVIF/WebP') ?>)</strong>
                        </span>
                    </div>
                </div>

                <!-- 5. SÜRÜKLE-BIRAK EDİTORYAL AKIŞ KARTLARI -->
                <p class="phone-only-note"><?= __a('Telefonda başlık, açıklama ve alt yazıları düzenleyebilirsiniz. Fotoğraf eklemek, sıralamak ve yerleşimi değiştirmek için bilgisayardan açın.', 'On a phone you can edit titles, descriptions and captions. To add, reorder or lay out photos, open this page on a computer.') ?></p>
                <div id="storyCanvas" class="story-canvas">
                    <!-- JS ile dinamik render edilir -->
                </div>
                <style>
                /* Fotoğraf kartı araçları (yerleşim, kapak, kaldır) yalnızca kartın üzerindeyken / odaktayken görünür */
                @media (hover: hover) {
                    .story-card.type-photo .card-tool { opacity: 0; transition: opacity 0.12s ease; }
                    .story-card.type-photo:hover .card-tool, .story-card.type-photo:focus-within .card-tool { opacity: 1; }
                }
                /* Izgara görünümü: bütün seri küçük resimlerle tek ekranda; sürükle-bırak aynen çalışır */
                .story-canvas.is-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
                .story-canvas.is-grid .story-card { grid-column: auto; flex-direction: column; padding: 6px; gap: 6px; }
                .story-canvas.is-grid .story-card .drag-handle { position: absolute; top: 10px; left: 10px; z-index: 3; width: 26px; height: 26px; background: var(--bg-surface); border: 1px solid var(--border-subtle); box-shadow: var(--card-shadow); }
                .story-canvas.is-grid .story-card.type-photo .story-card-thumb { width: 100%; height: auto; aspect-ratio: 3 / 2; }
                .story-canvas.is-grid .story-card.type-photo .story-card-body { display: none; }
                .story-canvas.is-grid .story-card:not(.type-photo) { max-height: 104px; overflow: hidden; font-size: var(--fs-xs); flex-direction: row; }
                .story-canvas.is-grid .story-card:not(.type-photo) .drag-handle { position: static; box-shadow: none; }
                .story-canvas.is-grid .story-card:not(.type-photo) .story-card-body > :not(.story-card-top) { display: none; }
                #canvasGridToggle[aria-pressed="true"] { background: var(--primary); color: var(--primary-text); border-color: var(--primary); }
                </style>
                <script>
                (function () {
                    var btn = document.getElementById('canvasGridToggle'), canvas = document.getElementById('storyCanvas');
                    function set(on) {
                        canvas.classList.toggle('is-grid', on);
                        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                        try { localStorage.setItem('item_canvas_grid', on ? '1' : '0'); } catch (e) {}
                    }
                    try { if (localStorage.getItem('item_canvas_grid') === '1') set(true); } catch (e) {}
                    btn.addEventListener('click', function () { set(!canvas.classList.contains('is-grid')); });
                })();
                </script>
            </div>
        </div>
    </div>

    <!-- ── SAĞ SÜTUN: Meta Bilgileri ve Ayarlar ── -->
    <div class="item-right-column" id="itemRightPanel">
        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title"><?= __a('Yayın & Özellikler', 'Publishing & Properties') ?></h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleItemRightPanel()" title="<?= __a('Sağ Paneli Gizle', 'Hide Side Panel') ?>" style="padding:4px 8px; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    <span><?= __a('Gizle', 'Hide') ?></span>
                </button>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><?= __a('İçerik Türü', 'Content Type') ?></label>
                    <select name="type" id="select_type" class="form-control" onchange="updateTargetFolderNotice()">
                        <?php 
                        $curr_type = $item['type'] ?? $default_type; 
                        foreach ($all_categories as $cat):
                        ?>
                            <option value="<?= e($cat['key_slug']) ?>" <?= $curr_type === $cat['key_slug'] ? 'selected' : '' ?>>
                                <?= e(get_category_localized_name($cat, $admin_lang)) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="blog" <?= $curr_type === 'blog' ? 'selected' : '' ?>>
                            <?= __a('Blog / Yazı', 'Blog / Article') ?>
                        </option>
                        <?php // Kategorisi silinmiş çalışma sessizce ilk kategoriye kaymasın: mevcut değeri koru
                        if ($curr_type !== 'blog' && !in_array($curr_type, array_column($all_categories, 'key_slug'), true)): ?>
                        <option value="<?= e($curr_type) ?>" selected><?= e(sprintf(__a('Silinmiş kategori: %s — yeni bir kategori seçin', 'Deleted category: %s — choose a new one'), $curr_type)) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Kalıcı Bağlantı (Slug / URL) EN -->
                <div class="form-group slug-lang-pane slug-lang-en" style="margin-bottom: 14px; display:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <label class="form-label" style="margin:0; font-size:12px; font-weight:700; display:flex; align-items:center; gap:5px;">
                            <span style="background:var(--info-soft); color:var(--accent-blue); padding:1px 5px; border-radius:6px; font-family:var(--font-mono); font-size:12px; border:1px solid var(--info-border);">EN</span>
                            <span><?= __a('Sayfa adresi (EN)', 'Page address (EN)') ?></span>
                        </label>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="slug_en" id="input_slug_en" class="form-control font-mono" value="<?= e($item['slug_en'] ?? $item['slug'] ?? '') ?>" oninput="onSlugEnChange()" placeholder="Örn: through-the-fire" style="font-size:12px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="generateSlugEn()" title="<?= __a('İngilizce Başlıktan Üret', 'Generate from English Title') ?>" style="white-space:nowrap; padding:4px 8px; font-size:12px;"><?= __a('Üret', 'Generate') ?></button>
                    </div>
                    <div class="form-help" id="slugEnPreview" style="margin-top:4px; color:var(--text-muted); font-family:var(--font-mono); font-size:12px;">
                        URL: /en/<strong><?= e($item['slug_en'] ?? $item['slug'] ?? '...') ?></strong>/
                    </div>
                </div>

                <!-- Kalıcı Bağlantı (Slug / URL) TR -->
                <div class="form-group slug-lang-pane slug-lang-tr" style="margin-bottom: 14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <label class="form-label" style="margin:0; font-size:12px; font-weight:700; display:flex; align-items:center; gap:5px;">
                            <span style="background:var(--success-soft); color:var(--success-text); padding:1px 5px; border-radius:6px; font-family:var(--font-mono); font-size:12px; border:1px solid var(--success-border);">TR</span>
                            <span><?= __a('Sayfa adresi (TR)', 'Page address (TR)') ?></span>
                        </label>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="slug_tr" id="input_slug_tr" class="form-control font-mono" value="<?= e($item['slug_tr'] ?? '') ?>" oninput="onSlugTrChange()" placeholder="Örn: atesin-icinden" style="font-size:12px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="generateSlugTr()" title="<?= __a('Türkçe Başlıktan Üret', 'Generate from Turkish Title') ?>" style="white-space:nowrap; padding:4px 8px; font-size:12px;"><?= __a('Üret', 'Generate') ?></button>
                    </div>
                    <div class="form-help" id="slugTrPreview" style="margin-top:4px; color:var(--text-muted); font-family:var(--font-mono); font-size:12px;">
                        URL: /tr/<strong><?= e($item['slug_tr'] ?? '...') ?></strong>/
                    </div>
                </div>

                <?php foreach ($site_languages as $sl):
                    $lc = $sl['code'];
                    if (in_array($lc, ['en', 'tr'], true)) continue;
                ?>
                <div class="form-group slug-lang-pane slug-lang-<?= e($lc) ?>" style="margin-bottom: 14px; display:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <label class="form-label" style="margin:0; font-size:12px; font-weight:700; display:flex; align-items:center; gap:5px;">
                            <span style="background:var(--bg-canvas-subtle); color:var(--text-main); padding:1px 5px; border-radius:6px; font-family:var(--font-mono); font-size:12px; border:1px solid var(--border-subtle);"><?= e($sl['flag'] ?? '🌐') ?> <?= e(strtoupper($lc)) ?></span>
                            <span><?= __a('Sayfa adresi', 'Page address') ?> <?= e(strtoupper($lc)) ?></span>
                        </label>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="slug_<?= e($lc) ?>" id="input_slug_<?= e($lc) ?>" class="form-control font-mono" value="<?= e($item['slug_' . $lc] ?? '') ?>" placeholder="<?= __a('Boş bırakılırsa EN/TR kullanılır', 'Falls back to EN/TR if blank') ?>" style="font-size:12px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="generateSlugForLang('<?= e($lc) ?>')" title="<?= __a('Başlıktan Üret', 'Generate from Title') ?>" style="white-space:nowrap; padding:4px 8px; font-size:12px;"><?= __a('Üret', 'Generate') ?></button>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Medya ve Dosya Sistemi Klasör Referansı (Arka Plan) -->
                <input type="hidden" name="slug" id="input_slug" value="<?= e($item['slug'] ?? '') ?>">
                <div class="form-help" id="targetFolderNotice" style="margin-top:-6px; margin-bottom:14px; color:var(--warning-text); font-family:var(--font-mono); font-size:12px; background:var(--warning-soft); padding:4px 8px; border-radius:6px; border:1px solid var(--warning-border);">
                    <?= __a('Klasör:', 'Folder:') ?> <?= e($project_folder ?: __a('Otomatik belirlenecek', 'Determined automatically')) ?>
                </div>

                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label class="form-label" style="margin:0;"><?= __a('Kapak Görseli', 'Cover Image') ?></label>
                        <button type="button" class="btn btn-sm" id="btn_remove_cover" onclick="removeCoverImage()" style="background:var(--danger-soft); color:var(--danger-text); border:1px solid var(--danger-border); font-size:12px; padding:2px 8px; border-radius:6px; <?= !empty($item['cover_image']) ? 'display:inline-flex;' : 'display:none;' ?> align-items:center; gap:4px; cursor:pointer;" title="<?= __a('Kapak görselini kaldır', 'Remove cover image') ?>">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <span><?= __a('Kapağı Kaldır', 'Remove Cover') ?></span>
                        </button>
                    </div>
                    <div style="display:flex; gap:8px; margin-bottom:8px;">
                        <input type="text" name="cover_image" id="input_cover_image" class="form-control" value="<?= e($item['cover_image'] ?? '') ?>" placeholder="Örn: content/projects/slug/cover.jpg" oninput="onCoverInputChange()">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openMediaModalForInput('input_cover_image')"><?= __a('Seç', 'Select') ?></button>
                    </div>
                    <input type="hidden" name="cover_focal" id="input_cover_focal" value="<?= e($item['cover_focal'] ?? '50% 50%') ?>">
                    <?php 
                        $preview_style = !empty($item['cover_image']) ? 'display:block;' : 'display:none;';
                        $preview_url = !empty($item['cover_image']) ? get_cover_image_url($item['cover_image']) : '';
                        $focal_val = $item['cover_focal'] ?? '50% 50%';
                        $focal_parts = explode(' ', $focal_val);
                        $focal_x = $focal_parts[0] ?? '50%';
                        $focal_y = $focal_parts[1] ?? '50%';
                    ?>
                    <div id="cover_preview_container" style="position:relative; <?= $preview_style ?>">
                        <div id="cover_focal_wrapper" style="position:relative; cursor:crosshair; border-radius:6px; overflow:hidden; border:1px solid var(--border-color);" onclick="pickCoverFocal(event)" title="<?= __a('Odak noktasını (focal point) belirlemek için görsel üzerine tıklayın', 'Click on image to set focal point') ?>">
                            <img id="input_cover_image_preview" src="<?= e($preview_url) ?>" style="width:100%; height:130px; object-fit:cover; object-position:<?= e($focal_val) ?>; display:block; pointer-events:none;" />
                            <div id="cover_focal_pin" class="focal-crosshair-pin" style="left:<?= e($focal_x) ?>; top:<?= e($focal_y) ?>;"></div>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:5px; font-size:12px; color:var(--text-muted);">
                            <span><?= __a('Odak noktası (resme tıklayın):', 'Focal point (click the image):') ?> <strong id="cover_focal_label" style="color:var(--text-main); font-family:var(--font-mono);"><?= e($focal_val) ?></strong></span>
                            <button type="button" class="btn btn-sm" onclick="resetCoverFocal()" style="font-size:12px; padding:1px 6px; background:transparent; border:1px solid var(--border-color); border-radius:6px; cursor:pointer; color:var(--text-muted);">
                                <?= __a('Merkeze Sıfırla', 'Reset to Center') ?>
                            </button>
                        </div>
                        <button type="button" onclick="removeCoverImage()" style="position:absolute; top:8px; right:8px; background:rgba(15,23,42,0.8); color:#ffffff; border:none; border-radius:6px; padding:4px 8px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:4px; backdrop-filter:blur(4px); box-shadow:0 2px 4px rgba(0,0,0,0.25); z-index:5;" title="<?= __a('Kapağı Kaldır', 'Remove Cover') ?>">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <span><?= __a('Kaldır', 'Remove') ?></span>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __a('Yayın Tarihi', 'Publish Date') ?></label>
                    <input type="date" name="date" class="form-control" value="<?= e($item['date'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __a('Sıra', 'Order') ?></label>
                    <input type="number" name="weight" class="form-control" value="<?= e($item['weight'] ?? 1) ?>" min="0">
                    <div class="form-help"><?= __a('Küçük numaralılar en üstte görünür.', 'Lower numbers appear first.') ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __a('Yayın Durumu', 'Publish Status') ?></label>
                    <select name="status" id="input_item_status" class="form-control" onchange="toggleItemStatusHelp()">
                        <option value="published" <?= ($item['status'] ?? 'published') === 'published' ? 'selected' : '' ?>><?= __a('Yayında (Herkese Açık)', 'Published (Public)') ?></option>
                        <option value="unlisted" <?= ($item['status'] ?? '') === 'unlisted' ? 'selected' : '' ?>><?= __a('Gizli Bağlantı (Listelenmez / Yalnızca Linkle)', 'Unlisted (Hidden / Direct Link Only)') ?></option>
                        <option value="draft" <?= ($item['status'] ?? '') === 'draft' ? 'selected' : '' ?>><?= __a('Taslak (Yalnızca Yönetici)', 'Draft (Admin Only)') ?></option>
                    </select>
                    <div class="form-help" id="unlisted_status_notice" style="<?= ($item['status'] ?? '') === 'unlisted' ? 'display:block;' : 'display:none;' ?> color:var(--warning-text); font-size:12px; margin-top:5px; background:rgba(217,119,6,0.08); padding:6px 8px; border-radius:6px; border:1px solid rgba(217,119,6,0.2);">
                        <?= __a('Bu çalışma ana sayfada, sitemap’te ve önceki/sonraki projelerde listelenmez. Sadece doğrudan bağlantıya sahip kişiler erişebilir.', 'This work will not appear in the homepage, sitemap, or next/prev navigation. Only people with the direct URL can view it.') ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span><?= __a('Erişim Parolası (Opsiyonel)', 'Access Password (Optional)') ?></span>
                        <?php if (!empty($item['access_password'])): ?>
                            <span style="font-size:12px; background:rgba(16,185,129,0.12); color:var(--success-text); padding:1px 6px; border-radius:6px; font-weight:700;"><?= __a('Aktif', 'Active') ?></span>
                        <?php endif; ?>
                    </label>
                    <input type="text" name="access_password" id="input_access_password" class="form-control" value="<?= e($item['access_password'] ?? '') ?>" placeholder="<?= __a('Boş bırakılırsa şifresiz açılır', 'Leave blank for public access') ?>" autocomplete="off" style="font-family:var(--font-mono); font-size:13px;">
                    <div class="form-help"><?= __a('Müşteriye özel veya gizli sunumlar için parola belirleyebilirsiniz. Ziyaretçi sayfayı açarken bu parolayı girmelidir.', 'Set a password for private client previews. Visitors must enter this password to view.') ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __a('Eski adresler (buraya yönlendirilir)', 'Old addresses (redirect here)') ?></label>
                    <input type="text" name="aliases" class="form-control" value="<?= e($item['aliases'] ?? '') ?>" placeholder="Örn: /single/turkiye-color/">
                </div>

            </div>
        </div>
    </div>
</form>

<!-- Sağ Panel Gizliyken Hızlı Açma Butonu -->
<button type="button" class="floating-right-panel-btn btn btn-primary" onclick="toggleItemRightPanel()" title="<?= __a('Yayın & Özellikler Ayarlarını Aç', 'Open Publishing & Properties Settings') ?>">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    <span><?= __a('Ayarlar Panelini Aç', 'Open Settings Panel') ?></span>
</button>

<script>
// ── Proje Klasörünün Dinamik Hesaplanması ──
function getActiveProjectFolder() {
    var slug = document.getElementById('input_slug').value.trim();
    var type = document.getElementById('select_type').value;
    if (!slug) return 'uploads';
    var typePlural = (type === 'project') ? 'projects' : (slugifyText(type) || 'single');
    return 'content/' + typePlural + '/' + slugifyText(slug);
}

function updateTargetFolderNotice() {
    var f = getActiveProjectFolder();
    var el = document.getElementById('targetFolderNotice');
    if (el) el.innerText = 'Klasör: ' + f;
    if (typeof updateSlugPreviews === 'function') {
        updateSlugPreviews();
    }
}

// ── Veritabanından Gelen İçeriklerin ve Dillerin Yüklenmesi ──
var SITE_LANGUAGES = <?= json_encode(array_map(fn($l) => [
    'code' => $l['code'],
    'flag' => $l['flag'] ?: '🌐',
    'name_native' => $l['name_native'],
    'name_tr' => $l['name_tr'],
    'fallback_lang' => $l['fallback_lang'] ?: 'en',
], $site_languages), JSON_UNESCAPED_UNICODE) ?>;

var RAW_CONTENTS = <?php
    $raw_map = [];
    foreach ($site_languages as $sl) {
        $lc = $sl['code'];
        $raw_map[$lc] = $item['content_' . $lc] ?? '';
    }
    echo json_encode($raw_map, JSON_UNESCAPED_UNICODE);
?>;
var rawContent_tr = RAW_CONTENTS.tr || '';
var rawContent_en = RAW_CONTENTS.en || '';

function normalizeBlockForUnified(b, lang) {
    if (!b) return null;
    var type = b.type || 'photo';
    if (type === 'photo') {
        return {
            type: 'photo',
            path: b.path || '',
            layout: b.layout || 'single',
            focal: b.focal || '50% 50%',
            alt_tr: lang === 'tr' ? (b.alt || b.alt_tr || '') : '',
            alt_en: lang === 'en' ? (b.alt || b.alt_en || '') : ''
        };
    } else if (type === 'chapter') {
        return {
            type: 'chapter',
            theme: b.theme || 'dark',
            title_tr: lang === 'tr' ? (b.title || b.title_tr || '') : '',
            title_en: lang === 'en' ? (b.title || b.title_en || '') : '',
            lead_tr: lang === 'tr' ? (b.lead || b.lead_tr || '') : '',
            lead_en: lang === 'en' ? (b.lead || b.lead_en || '') : ''
        };
    } else if (type === 'text') {
        return {
            type: 'text',
            text_tr: lang === 'tr' ? (b.text || b.text_tr || '') : '',
            text_en: lang === 'en' ? (b.text || b.text_en || '') : ''
        };
    } else if (type === 'quote') {
        return {
            type: 'quote',
            text_tr: lang === 'tr' ? (b.text || b.text_tr || '') : '',
            text_en: lang === 'en' ? (b.text || b.text_en || '') : ''
        };
    } else if (type === 'youtube') {
        return {
            type: 'youtube',
            id: b.id || ''
        };
    }
    return b;
}

function createMergedBlock(tb, eb) {
    var type = (tb && tb.type) || (eb && eb.type) || 'photo';
    if (type === 'photo') {
        return {
            type: 'photo',
            path: (tb && tb.path) || (eb && eb.path) || '',
            layout: (tb && tb.layout) || (eb && eb.layout) || 'single',
            focal: (tb && tb.focal) || (eb && eb.focal) || '50% 50%',
            alt_tr: (tb && (tb.alt || tb.alt_tr)) || '',
            alt_en: (eb && (eb.alt || eb.alt_en)) || (tb && tb.alt_en) || ''
        };
    } else if (type === 'chapter') {
        return {
            type: 'chapter',
            theme: (tb && tb.theme) || (eb && eb.theme) || 'dark',
            title_tr: (tb && (tb.title || tb.title_tr)) || '',
            title_en: (eb && (eb.title || eb.title_en)) || (tb && tb.title_en) || '',
            lead_tr: (tb && (tb.lead || tb.lead_tr)) || '',
            lead_en: (eb && (eb.lead || eb.lead_en)) || (tb && tb.lead_en) || ''
        };
    } else if (type === 'text') {
        return {
            type: 'text',
            text_tr: (tb && (tb.text || tb.text_tr)) || '',
            text_en: (eb && (eb.text || eb.text_en)) || (tb && tb.text_en) || ''
        };
    } else if (type === 'quote') {
        return {
            type: 'quote',
            text_tr: (tb && (tb.text || tb.text_tr)) || '',
            text_en: (eb && (eb.text || eb.text_en)) || (tb && tb.text_en) || ''
        };
    } else if (type === 'youtube') {
        return {
            type: 'youtube',
            id: (tb && tb.id) || (eb && eb.id) || ''
        };
    }
    return tb || eb;
}

function mergeLanguageBlocks(trBlocks, enBlocks) {
    trBlocks = trBlocks || [];
    enBlocks = enBlocks || [];

    if (!trBlocks.length && !enBlocks.length) return [];
    if (!trBlocks.length) return enBlocks.map(function(b) { return normalizeBlockForUnified(b, 'en'); });
    if (!enBlocks.length) return trBlocks.map(function(b) { return normalizeBlockForUnified(b, 'tr'); });

    // Uzunluklar ve blok tipleri birebir eşleşiyorsa doğrudan indeks eşlemesi yap
    var canDirectMatch = (trBlocks.length === enBlocks.length);
    if (canDirectMatch) {
        for (var i = 0; i < trBlocks.length; i++) {
            if ((trBlocks[i].type || 'photo') !== (enBlocks[i].type || 'photo')) {
                canDirectMatch = false;
                break;
            }
        }
    }

    if (canDirectMatch) {
        var merged = [];
        for (var i = 0; i < trBlocks.length; i++) {
            merged.push(createMergedBlock(trBlocks[i], enBlocks[i]));
        }
        return merged;
    }

    // Aksi halde TR akışını ana eksen al, EN bloklarını tip/path ile eşle
    var usedEnIndexes = {};
    var enPhotoMap = {};
    var enChapters = [];
    var enTexts = [];
    var enQuotes = [];

    enBlocks.forEach(function(b, idx) {
        var t = b.type || 'photo';
        if (t === 'photo' && b.path) {
            enPhotoMap[b.path] = { block: b, idx: idx };
        } else if (t === 'chapter') {
            enChapters.push({ block: b, idx: idx });
        } else if (t === 'text') {
            enTexts.push({ block: b, idx: idx });
        } else if (t === 'quote') {
            enQuotes.push({ block: b, idx: idx });
        }
    });

    var chPtr = 0, txtPtr = 0, qPtr = 0;
    var merged = [];

    trBlocks.forEach(function(tb) {
        var t = tb.type || 'photo';
        var matchedEb = null;

        if (t === 'photo' && tb.path && enPhotoMap[tb.path]) {
            matchedEb = enPhotoMap[tb.path].block;
            usedEnIndexes[enPhotoMap[tb.path].idx] = true;
        } else if (t === 'chapter' && chPtr < enChapters.length) {
            matchedEb = enChapters[chPtr].block;
            usedEnIndexes[enChapters[chPtr].idx] = true;
            chPtr++;
        } else if (t === 'text' && txtPtr < enTexts.length) {
            matchedEb = enTexts[txtPtr].block;
            usedEnIndexes[enTexts[txtPtr].idx] = true;
            txtPtr++;
        } else if (t === 'quote' && qPtr < enQuotes.length) {
            matchedEb = enQuotes[qPtr].block;
            usedEnIndexes[enQuotes[qPtr].idx] = true;
            qPtr++;
        }

        merged.push(createMergedBlock(tb, matchedEb));
    });

    // TR'de olmayan fazladan EN blokları varsa onları da ekle
    enBlocks.forEach(function(eb, idx) {
        if (!usedEnIndexes[idx]) {
            merged.push(normalizeBlockForUnified(eb, 'en'));
        }
    });

    return merged;
}

// ── Editoryal Veri Yapısı (Normal Format - JSON, Çok Dilli) ──
var parsedByLang = {};
var studioIntro = {};
SITE_LANGUAGES.forEach(function(l) {
    var parsed = parseToNormalStructure(RAW_CONTENTS[l.code] || '');
    parsedByLang[l.code] = parsed;
    studioIntro[l.code] = parsed.intro || '';
});

var studioBlocks = mergeLanguageBlocks(
    (parsedByLang.tr && parsedByLang.tr.blocks) || [],
    (parsedByLang.en && parsedByLang.en.blocks) || []
);

// Ekstra dillerdeki (hy, ku, fr, de vb.) blok metinlerini mevcut studioBlocks indekslerine entegre et
SITE_LANGUAGES.forEach(function(l) {
    var lc = l.code;
    if (lc === 'tr' || lc === 'en') return;
    var extraBlocks = (parsedByLang[lc] && parsedByLang[lc].blocks) || [];
    studioBlocks.forEach(function(b, idx) {
        var eb = extraBlocks[idx] || null;
        if (b.type === 'photo') {
            b['alt_' + lc] = (eb && (eb.alt || eb['alt_' + lc])) || '';
        } else if (b.type === 'chapter') {
            b['title_' + lc] = (eb && (eb.title || eb['title_' + lc])) || '';
            b['lead_' + lc] = (eb && (eb.lead || eb['lead_' + lc])) || '';
        } else if (b.type === 'text' || b.type === 'quote') {
            b['text_' + lc] = (eb && (eb.text || eb['text_' + lc])) || '';
        }
    });
});

var currentCanvasLang = 'tr';
var sortableInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    // 1. Giriş Metinlerini Zengin Editör Alanlarına Doldur (Tüm Diller)
    SITE_LANGUAGES.forEach(function(l) {
        var introEl = document.getElementById('introEditable_' + l.code);
        if (introEl) introEl.innerHTML = richTextToHtml(studioIntro[l.code] || '');
    });

    // 2. Görsel Tuvali Render Et
    renderStoryCanvas();

    // 3. Sürükle-Bırak SortableJS Başlat
    initSortable();

    // 4. Dropzone Dinleyicisi
    setupProjectDropZone();

    // 5. Form Kaydetme Öncesi JSON Paketleme
    document.getElementById('mainItemForm').addEventListener('submit', function() {
        packStudioData();
    });

    // Ctrl+S: footer.php genel kısayolu requestSubmit ile tetikler; submit dinleyicisi packStudioData çağırır
});

// ── İçeriği Normal Yapıya Çözümleme (JSON veya Legacy Markdown) ──
function parseToNormalStructure(raw) {
    if (!raw) return { intro: '', blocks: [] };
    raw = raw.trim();

    // 1. Zaten normal JSON ise doğrudan al
    if (raw.startsWith('{')) {
        try {
            var parsed = JSON.parse(raw);
            if (parsed && typeof parsed === 'object') {
                return {
                    intro: parsed.intro || '',
                    blocks: parsed.blocks || []
                };
            }
        } catch (e) {}
    }

    // 2. Eski Markdown formatı ise bloklara dönüştür
    var lines = raw.split('\n');
    var introLines = [];
    var blocks = [];
    var hitFirstBlock = false;
    var len = lines.length;
    var i = 0;
    var currentChapterTheme = 'dark';
    var inRow = false;

    while (i < len) {
        var line = lines[i];
        var trimmed = line.trim();

        // Bölüm Başlangıcı
        var chMatch = trimmed.match(/<div\s+class=["']project-chapter\s+(chapter-(dark|white))["']>/i);
        if (chMatch) {
            hitFirstBlock = true;
            currentChapterTheme = (chMatch[2] === 'white') ? 'white' : 'dark';

            var chTitle = '';
            var chLead = '';
            for (var k = i + 1; k < Math.min(len, i + 35); k++) {
                if (/<div\s+class=["']project-chapter-header["']>/i.test(lines[k].trim())) {
                    var headerLines = [];
                    k++;
                    while (k < len && !/<\/div>/i.test(lines[k].trim())) {
                        headerLines.push(lines[k].trim());
                        k++;
                    }
                    var headerHtml = headerLines.join(' ');
                    var tm = headerHtml.match(/<h2[^>]*class=["']project-chapter-title["'][^>]*>(.*?)<\/h2>/i);
                    var lm = headerHtml.match(/<p[^>]*class=["']project-chapter-lead["'][^>]*>(.*?)<\/p>/i);
                    if (tm) chTitle = tm[1].replace(/<[^>]+>/g, '').trim();
                    if (lm) chLead = lm[1].replace(/<[^>]+>/g, '').trim();
                    break;
                }
            }

            blocks.push({
                type: 'chapter',
                title: chTitle,
                lead: chLead,
                theme: currentChapterTheme
            });
            i++;
            continue;
        }

        if (/<div\s+class=["']project-chapter-header["']>/i.test(trimmed)) {
            i++;
            while (i < len && !/<\/div>/i.test(lines[i].trim())) { i++; }
            i++;
            continue;
        }

        if (/<div\s+class=["']photo-row["']>/i.test(trimmed)) {
            hitFirstBlock = true;
            inRow = true;
            i++;
            continue;
        }

        if (/^<\/div>$/i.test(trimmed)) {
            if (inRow) { inRow = false; }
            i++;
            continue;
        }

        var imgMatch = trimmed.match(/\{\{\s*image\((.*?)\)\s*\}\}/i);
        if (imgMatch) {
            hitFirstBlock = true;
            var attrs = imgMatch[1];
            var pm = attrs.match(/path=["']([^"']+)["']/);
            var am = attrs.match(/alt=["']([^"']*)["']/);
            var cm = attrs.match(/caption=["']([^"']*)["']/);

            blocks.push({
                type: 'photo',
                path: pm ? pm[1] : '',
                alt: am ? am[1] : '',
                caption: cm ? cm[1] : '',
                layout: inRow ? 'row' : 'single'
            });
            i++;
            continue;
        }

        var ytMatch = trimmed.match(/\{\{\s*youtube\(id=["']([^"']+)["']\)\s*\}\}/i);
        if (ytMatch) {
            hitFirstBlock = true;
            blocks.push({
                type: 'youtube',
                id: ytMatch[1]
            });
            i++;
            continue;
        }

        var qMatch = trimmed.match(/^>\s*(.*)$/);
        if (qMatch) {
            hitFirstBlock = true;
            blocks.push({
                type: 'quote',
                text: qMatch[1].trim()
            });
            i++;
            continue;
        }

        if (/<div\s+class=["']project-narrative-block["']>/i.test(trimmed)) {
            hitFirstBlock = true;
            var narLines = [];
            i++;
            while (i < len && !/<\/div>/i.test(lines[i].trim())) {
                narLines.push(lines[i].trim());
                i++;
            }
            var narText = narLines.join('\n').replace(/<p>/gi, '').replace(/<\/p>/gi, '\n').replace(/<[^>]+>/g, '').trim();
            blocks.push({
                type: 'text',
                text: narText
            });
            i++;
            continue;
        }

        if (trimmed !== '') {
            if (!hitFirstBlock) {
                introLines.push(line);
            } else {
                blocks.push({
                    type: 'text',
                    text: trimmed
                });
            }
        }
        i++;
    }

    return {
        intro: introLines.join('\n').trim(),
        blocks: blocks
    };
}

// ── Zengin Metin Araç Çubuğu HTML Şablonu ──
function renderRichToolbarHtml() {
    var isEn = (window.ADMIN_LANG === 'en');
    return '<div class="rich-toolbar">' +
        '<select class="rt-select" onfocus="saveCurrentSelection()" onmousedown="saveCurrentSelection()" onchange="execRichBlockFormat(this)" title="' + (isEn ? 'Format' : 'Format') + '">' +
            '<option value="p">' + (isEn ? 'Paragraph' : 'Paragraf') + '</option>' +
            '<option value="h2">' + (isEn ? 'Heading 2 (H2)' : 'Başlık 2 (H2)') + '</option>' +
            '<option value="h3">' + (isEn ? 'Chapter Title (H3)' : 'Bölüm Başlığı (H3)') + '</option>' +
            '<option value="h4">' + (isEn ? 'Subheading (H4)' : 'Alt Başlık (H4)') + '</option>' +
            '<option value="blockquote">' + (isEn ? 'Quote Block' : 'Alıntı Bloğu') + '</option>' +
        '</select>' +
        '<select class="rt-select" onfocus="saveCurrentSelection()" onmousedown="saveCurrentSelection()" onchange="execRichFontSize(this)" title="' + (isEn ? 'Font Size' : 'Yazı Boyutu') + '">' +
            '<option value="" disabled selected>' + (isEn ? 'Size' : 'Boyut') + '</option>' +
            '<option value="12px">' + (isEn ? '12px (Small Note)' : '12px (Küçük Not)') + '</option>' +
            '<option value="14px">' + (isEn ? '14px (Standard)' : '14px (Standart)') + '</option>' +
            '<option value="16px">' + (isEn ? '16px (Reading Text)' : '16px (Okuma Metni)') + '</option>' +
            '<option value="18px">' + (isEn ? '18px (Lead Emphasis)' : '18px (Vurgulu Lead)') + '</option>' +
            '<option value="22px">' + (isEn ? '22px (Subheading)' : '22px (Alt Başlık)') + '</option>' +
            '<option value="28px">' + (isEn ? '28px (Main Heading)' : '28px (Büyük Başlık)') + '</option>' +
            '<option value="inherit">' + (isEn ? 'Reset (Default)' : 'Sıfırla (Varsayılan)') + '</option>' +
        '</select>' +
        '<div class="rt-sep"></div>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'undo\')" title="' + (isEn ? 'Undo (Ctrl+Z)' : 'Geri Al (Ctrl+Z)') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>' +
        '</button>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'redo\')" title="' + (isEn ? 'Redo (Ctrl+Y)' : 'İleri Al (Ctrl+Y)') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>' +
        '</button>' +
        '<div class="rt-sep"></div>' +
        '<button type="button" class="rt-btn rt-bold" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'bold\')" title="' + (isEn ? 'Bold (Ctrl+B)' : 'Kalın (Ctrl+B)') + '"><strong>B</strong></button>' +
        '<button type="button" class="rt-btn rt-italic" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'italic\')" title="' + (isEn ? 'Italic (Ctrl+I)' : 'İtalik (Ctrl+I)') + '"><em>I</em></button>' +
        '<button type="button" class="rt-btn rt-underline" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'underline\')" title="' + (isEn ? 'Underline (Ctrl+U)' : 'Altı Çizili (Ctrl+U)') + '"><u>U</u></button>' +
        '<button type="button" class="rt-btn rt-strike" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'strikeThrough\')" title="' + (isEn ? 'Strikethrough' : 'Üstü Çizili') + '"><s>S</s></button>' +
        '<div class="rt-sep"></div>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'justifyLeft\')" title="' + (isEn ? 'Align Left' : 'Sola Yasla') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="6" x2="3" y2="6"/><line x1="15" y1="12" x2="3" y2="12"/><line x1="17" y1="18" x2="3" y2="18"/></svg>' +
        '</button>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'justifyCenter\')" title="' + (isEn ? 'Center' : 'Ortala') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="6" x2="3" y2="6"/><line x1="17" y1="12" x2="7" y2="12"/><line x1="19" y1="18" x2="5" y2="18"/></svg>' +
        '</button>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'justifyRight\')" title="' + (isEn ? 'Align Right' : 'Sağa Yasla') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="12" x2="9" y2="12"/><line x1="21" y1="18" x2="7" y2="18"/></svg>' +
        '</button>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'justifyFull\')" title="' + (isEn ? 'Justify' : 'İki Yana Yasla') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="12" x2="3" y2="12"/><line x1="21" y1="18" x2="3" y2="18"/></svg>' +
        '</button>' +
        '<div class="rt-sep"></div>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'insertUnorderedList\')" title="' + (isEn ? 'Bullet List' : 'Madde İmleri Listesi') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1.5" fill="currentColor"/><circle cx="3.5" cy="12" r="1.5" fill="currentColor"/><circle cx="3.5" cy="18" r="1.5" fill="currentColor"/></svg>' +
        '</button>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'insertOrderedList\')" title="' + (isEn ? 'Numbered List' : 'Numaralı Liste') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/></svg>' +
        '</button>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'insertHorizontalRule\')" title="' + (isEn ? 'Horizontal Rule' : 'Ayırıcı Yatay Çizgi') + '">―</button>' +
        '<div class="rt-sep"></div>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichLink(this)" title="' + (isEn ? 'Add Link' : 'Bağlantı / Link Ekle') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>' +
        '</button>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'unlink\')" title="' + (isEn ? 'Remove Link' : 'Bağlantıyı Kaldır') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.16 11.75-1.72 1.71a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>' +
        '</button>' +
        '<button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd(this, \'removeFormat\')" title="' + (isEn ? 'Clear Formatting' : 'Biçimlendirmeyi Temizle') + '">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/></svg>' +
        '</button>' +
    '</div>';
}

// ── Zengin Metin Dönüştürücü & Güvenlik Yardımcıları ──
function richTextToHtml(raw) {
    if (!raw) return '';
    var str = String(raw).trim();
    if (/<[a-z][\s\S]*>/i.test(str)) {
        return str;
    }
    var res = escapeHtml(str);
    res = res.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    res = res.replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>');
    res = res.replace(/\r?\n\s*\r?\n/g, '</p><p>');
    res = res.replace(/\r?\n/g, '<br>');
    return '<p>' + res + '</p>';
}

function getRichContent(el) {
    if (!el) return '';
    var html = el.innerHTML.trim();
    if (html === '<br>' || html === '<p><br></p>' || html === '<p></p>') {
        return '';
    }
    return html;
}

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

function getEditableFromTarget(target) {
    if (!target) return null;
    if (typeof target === 'string') {
        return document.getElementById(target) || document.getElementById('introEditable_' + target) || document.getElementById('editable_' + target);
    }
    if (target.nodeType) {
        if (target.classList && target.classList.contains('rich-editable')) return target;
        var wrap = target.closest('.rich-editor-wrap');
        if (wrap) return wrap.querySelector('.rich-editable');
    }
    return null;
}

function execRichCmd(target, cmd, val) {
    var editable = getEditableFromTarget(target);
    if (!editable) return;
    editable.focus();
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
    editable.dispatchEvent(new Event('input', { bubbles: true }));
}

function execRichBlockFormat(target, tagVal) {
    var tag = tagVal;
    if (!tag && target && target.value) {
        tag = target.value;
    }
    var editable = getEditableFromTarget(target);
    if (!editable || !tag) return;
    editable.focus();
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
    editable.dispatchEvent(new Event('input', { bubbles: true }));
}

function execRichFontSize(target, sizeVal) {
    var size = sizeVal;
    if (!size && target && target.value) {
        size = target.value;
    }
    var editable = getEditableFromTarget(target);
    if (!editable) return;
    editable.focus();

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
            if (block && editable.contains(block)) {
                block.style.fontSize = '';
            }
        }
        editable.dispatchEvent(new Event('input', { bubbles: true }));
        if (target && target.tagName === 'SELECT') target.value = '';
        return;
    }

    if (curSel && curSel.rangeCount > 0 && !curSel.isCollapsed) {
        document.execCommand('styleWithCSS', false, false);
        document.execCommand('fontSize', false, '7');
        var fontTags = editable.querySelectorAll('font[size="7"]');
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
        var webkitSpans = editable.querySelectorAll('span[style*="-webkit-xxx"]');
        webkitSpans.forEach(function(s) {
            s.style.fontSize = size;
        });
    } else if (curSel && curSel.rangeCount > 0) {
        var node = curSel.anchorNode;
        if (node && node.nodeType === 3) node = node.parentNode;
        var block = node ? node.closest('p, h2, h3, h4, blockquote, li, div') : null;
        if (block && editable.contains(block)) {
            block.style.fontSize = size;
        }
    }

    editable.dispatchEvent(new Event('input', { bubbles: true }));
    if (target && target.tagName === 'SELECT') target.value = '';
}

function execRichLink(target) {
    var editable = getEditableFromTarget(target);
    if (!editable) return;
    editable.focus();
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
    var selectedText = curSel ? curSel.toString().trim() : '';
    adminPromptLink().then(function (url) {
        if (!url) return;
        if (selectedText.length === 0) {
            document.execCommand('insertHTML', false, '<a href="' + escapeHtml(url) + '" target="_blank">' + escapeHtml(url) + '</a>');
        } else {
            document.execCommand('createLink', false, url);
        }
        editable.dispatchEvent(new Event('input', { bubbles: true }));
    });
}

function handleRichPaste(e) {
    e.preventDefault();
    var text = (e.clipboardData || window.clipboardData).getData('text/plain');
    document.execCommand('insertText', false, text);
}

function updateRichIntro(lang, el) {
    studioIntro[lang] = getRichContent(el);
}

function updateRichBlock(idx, field, el) {
    if (studioBlocks[idx]) {
        studioBlocks[idx][field] = getRichContent(el);
    }
}

function syncActiveInputsToBlocks() {
    var canvasEl = document.getElementById('storyCanvas');
    if (!canvasEl) return;

    canvasEl.querySelectorAll('.story-card').forEach(function(card) {
        var idx = parseInt(card.getAttribute('data-idx'), 10);
        if (isNaN(idx) || !studioBlocks[idx]) return;
        var b = studioBlocks[idx];

        if (b.type === 'photo') {
            SITE_LANGUAGES.forEach(function(l) {
                var altInp = card.querySelector('input.block-lang-' + l.code);
                if (altInp) b['alt_' + l.code] = altInp.value;
            });
        } else if (b.type === 'chapter') {
            SITE_LANGUAGES.forEach(function(l) {
                var tInp = card.querySelector('input.field-chapter-title-' + l.code);
                if (tInp) b['title_' + l.code] = tInp.value;
                var lInp = card.querySelector('.rich-editable[data-field="lead_' + l.code + '"]');
                if (lInp) b['lead_' + l.code] = getRichContent(lInp);
            });
        } else if (b.type === 'text' || b.type === 'quote') {
            SITE_LANGUAGES.forEach(function(l) {
                var txtInp = card.querySelector('.rich-editable[data-field="text_' + l.code + '"]');
                if (txtInp) b['text_' + l.code] = getRichContent(txtInp);
            });
        } else if (b.type === 'youtube') {
            var yInp = card.querySelector('input[data-field="youtube_id"]');
            if (yInp) {
                var vid = yInp.value.trim();
                var match = vid.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
                if (match) vid = match[1];
                b.id = vid;
            }
        }
    });
}

// ── Verileri JSON Olarak Paketleme (Kaydetme Öncesi - Tüm Diller) ──
function packStudioData() {
    SITE_LANGUAGES.forEach(function(l) {
        var introEl = document.getElementById('introEditable_' + l.code);
        studioIntro[l.code] = introEl ? getRichContent(introEl) : '';
    });

    syncActiveInputsToBlocks();

    SITE_LANGUAGES.forEach(function(l) {
        var lc = l.code;
        var langBlocks = [];
        studioBlocks.forEach(function(b) {
            if (b.type === 'photo') {
                langBlocks.push({
                    type: 'photo',
                    path: b.path || '',
                    layout: b.layout || 'single',
                    focal: b.focal || '50% 50%',
                    alt: b['alt_' + lc] || ''
                });
            } else if (b.type === 'chapter') {
                langBlocks.push({
                    type: 'chapter',
                    theme: b.theme || 'dark',
                    title: b['title_' + lc] || '',
                    lead: b['lead_' + lc] || ''
                });
            } else if (b.type === 'text') {
                langBlocks.push({
                    type: 'text',
                    text: b['text_' + lc] || ''
                });
            } else if (b.type === 'quote') {
                langBlocks.push({
                    type: 'quote',
                    text: b['text_' + lc] || ''
                });
            } else if (b.type === 'youtube') {
                langBlocks.push({
                    type: 'youtube',
                    id: b.id || ''
                });
            }
        });
        var hiddenEl = document.getElementById('input_content_' + lc);
        if (hiddenEl) {
            hiddenEl.value = JSON.stringify({
                intro: studioIntro[lc] || '',
                blocks: langBlocks
            }, null, 2);
        }
    });
}

var MIN_ICONS = {
    drag: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>',
    close: '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    star: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    chapter: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>',
    text: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
    quote: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg>',
    youtube: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
    photo: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>'
};

// ── Editoryal Tuvali Render Etme ──
var studioBlocksSnapshot = null;
function renderStoryCanvas() {
    var canvas = document.getElementById('storyCanvas');
    if (!canvas) return;
    // Tuval alanlarının name'i yok; yapısal değişiklikleri (ekle/sırala/sil) ilk durumla kıyaslayıp kaydedilmemiş uyarısına bildir
    var snap = JSON.stringify(studioBlocks);
    if (studioBlocksSnapshot === null) studioBlocksSnapshot = snap;
    else if (snap !== studioBlocksSnapshot && window.adminMarkDirty) window.adminMarkDirty(document.getElementById('mainItemForm'));

    var blocks = studioBlocks;
    var isEn = (window.ADMIN_LANG === 'en');

    // Sayaçları güncelle
    var photoCount = blocks.filter(function(b) { return b.type === 'photo'; }).length;
    var chapterCount = blocks.filter(function(b) { return b.type === 'chapter'; }).length;
    var textCount = blocks.filter(function(b) { return b.type === 'text'; }).length;
    
    var badgeText = blocks.length + (isEn ? ' Blocks (' : ' Blok (') + photoCount + (isEn ? ' Photos' : ' Fotoğraf');
    if (chapterCount > 0) badgeText += ', ' + chapterCount + (isEn ? ' Chapters' : ' Bölüm');
    if (textCount > 0) badgeText += ', ' + textCount + (isEn ? ' Texts' : ' Metin');
    badgeText += ')';
    var badgeEl = document.getElementById('blockCountBadge');
    if (badgeEl) badgeEl.innerText = badgeText;

    if (!blocks.length) {
        canvas.innerHTML = '<div style="text-align:center; padding:36px; color:var(--text-muted); border:2px dashed var(--border-light); border-radius:10px; background:var(--bg-surface);">' +
            '<div style="display:flex; justify-content:center; margin-bottom:10px; color:var(--text-sub);"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></div>' +
            '<div style="font-weight:600; font-size:14px; color:var(--text-main); margin-bottom:4px;">' + (isEn ? 'No blocks in the story flow yet' : 'Hikaye akışında henüz blok bulunmuyor') + '</div>' +
            '<div style="font-size:12px; color:var(--text-muted);">' + (isEn ? 'You can start creating your editorial narrative by adding subtext, chapter cards, or photos from the buttons above.' : 'Yukarıdaki butonlardan ara metin, bölüm kartı veya fotoğraf ekleyerek editoryal anlatınızı oluşturmaya başlayabilirsiniz.') + '</div>' +
        '</div>';
        return;
    }

    var photoIndex = 1;
    var activeLang = currentCanvasLang || 'tr';

    canvas.innerHTML = blocks.map(function(b, idx) {
        var cardHtml = '';

        if (b.type === 'chapter') {
            var chapterPanes = SITE_LANGUAGES.map(function(l) {
                var lc = l.code;
                var isL_en = (lc === 'en');
                var lblTitle = isL_en ? 'Chapter Title' : 'Bölüm Başlığı';
                var phTitle = isL_en ? 'Chapter title...' : 'Bölüm başlığı...';
                var lblIntro = isL_en ? 'Chapter Intro / Lead Paragraph' : 'Bölüm Giriş / Lead Paragrafı';
                var phIntro = isL_en ? 'Introductory narrative setting the chapter mood...' : 'Bölümün atmosferini anlatan giriş metni...';
                return '<div class="block-lang-pane block-lang-' + lc + '" style="' + (lc !== activeLang ? 'display:none;' : '') + '">' +
                    '<div style="margin-bottom:8px;">' +
                        '<label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:3px;">' + lblTitle + '</label>' +
                        '<input type="text" class="form-control field-chapter-title-' + lc + '" style="font-weight:700; font-size:13px;" value="' + escapeHtml(b['title_' + lc] || '') + '" placeholder="' + phTitle + '" oninput="updateChapterField(' + idx + ', \'title_' + lc + '\', this.value)">' +
                    '</div>' +
                    '<div>' +
                        '<label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:3px;">' + lblIntro + '</label>' +
                        '<div class="rich-editor-wrap">' +
                            renderRichToolbarHtml() +
                            '<div class="rich-editable" contenteditable="true" style="min-height:55px;" data-idx="' + idx + '" data-field="lead_' + lc + '" data-placeholder="' + phIntro + '" onpaste="handleRichPaste(event)" oninput="updateRichBlock(' + idx + ', \'lead_' + lc + '\', this)">' + richTextToHtml(b['lead_' + lc] || '') + '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }).join('');

            cardHtml = '<div class="story-card type-chapter" data-idx="' + idx + '" data-card-lang="' + activeLang + '">' +
                '<div class="drag-handle" title="' + (isEn ? 'Drag to reorder' : 'Sırasını değiştirmek için sürükleyin') + '">' + MIN_ICONS.drag + '</div>' +
                '<div class="story-card-body">' +
                    '<div class="story-card-top">' +
                        '<div style="display:flex; align-items:center; gap:8px;">' +
                            '<span class="story-badge badge-chapter">' + MIN_ICONS.chapter + ' ' + (isEn ? 'Chapter' : 'Bölüm') + '</span>' +
                        '</div>' +
                        '<div style="display:flex; align-items:center; gap:6px;">' +
                            '<button type="button" class="btn-card-del" onclick="removeBlockFromStory(' + idx + ')" title="' + (isEn ? 'Remove Chapter' : 'Bölümü Kaldır') + '">' + MIN_ICONS.close + '</button>' +
                        '</div>' +
                    '</div>' +
                    chapterPanes +
                '</div>' +
            '</div>';
        } else if (b.type === 'text') {
            var textPanes = SITE_LANGUAGES.map(function(l) {
                var lc = l.code;
                var phText = (lc === 'en') ? 'Editorial story text appearing between photos...' : 'Fotoğraflar arasında görünecek editoryal hikaye metni...';
                return '<div class="block-lang-pane block-lang-' + lc + '" style="' + (lc !== activeLang ? 'display:none;' : '') + '">' +
                    '<div class="rich-editor-wrap">' +
                        renderRichToolbarHtml() +
                        '<div class="rich-editable" contenteditable="true" style="min-height:85px;" data-idx="' + idx + '" data-field="text_' + lc + '" data-placeholder="' + phText + '" onpaste="handleRichPaste(event)" oninput="updateRichBlock(' + idx + ', \'text_' + lc + '\', this)">' + richTextToHtml(b['text_' + lc] || '') + '</div>' +
                    '</div>' +
                '</div>';
            }).join('');

            cardHtml = '<div class="story-card type-text" data-idx="' + idx + '" data-card-lang="' + activeLang + '">' +
                '<div class="drag-handle" title="' + (isEn ? 'Drag to reorder' : 'Sırasını değiştirmek için sürükleyin') + '">' + MIN_ICONS.drag + '</div>' +
                '<div class="story-card-body">' +
                    '<div class="story-card-top">' +
                        '<span class="story-badge badge-text">' + MIN_ICONS.text + ' ' + (isEn ? 'Subtext' : 'Ara Metin') + '</span>' +
                        '<div style="display:flex; align-items:center; gap:6px;">' +
                            '<button type="button" class="btn-card-del" onclick="removeBlockFromStory(' + idx + ')" title="' + (isEn ? 'Remove Text' : 'Metni Kaldır') + '">' + MIN_ICONS.close + '</button>' +
                        '</div>' +
                    '</div>' +
                    textPanes +
                '</div>' +
            '</div>';
        } else if (b.type === 'quote') {
            var quotePanes = SITE_LANGUAGES.map(function(l) {
                var lc = l.code;
                var phQuote = (lc === 'en') ? 'Editorial quote text...' : 'Editoryal alıntı metni...';
                return '<div class="block-lang-pane block-lang-' + lc + '" style="' + (lc !== activeLang ? 'display:none;' : '') + '">' +
                    '<div class="rich-editor-wrap">' +
                        renderRichToolbarHtml() +
                        '<div class="rich-editable" contenteditable="true" style="min-height:50px; font-style:italic;" data-idx="' + idx + '" data-field="text_' + lc + '" data-placeholder="' + phQuote + '" onpaste="handleRichPaste(event)" oninput="updateRichBlock(' + idx + ', \'text_' + lc + '\', this)">' + richTextToHtml(b['text_' + lc] || '') + '</div>' +
                    '</div>' +
                '</div>';
            }).join('');

            cardHtml = '<div class="story-card type-quote" data-idx="' + idx + '" data-card-lang="' + activeLang + '">' +
                '<div class="drag-handle" title="' + (isEn ? 'Drag to reorder' : 'Sırasını değiştirmek için sürükleyin') + '">' + MIN_ICONS.drag + '</div>' +
                '<div class="story-card-body">' +
                    '<div class="story-card-top">' +
                        '<span class="story-badge badge-quote">' + MIN_ICONS.quote + ' ' + (isEn ? 'Quote' : 'Alıntı') + '</span>' +
                        '<div style="display:flex; align-items:center; gap:6px;">' +
                            '<button type="button" class="btn-card-del" onclick="removeBlockFromStory(' + idx + ')" title="' + (isEn ? 'Remove Quote' : 'Alıntıyı Kaldır') + '">' + MIN_ICONS.close + '</button>' +
                        '</div>' +
                    '</div>' +
                    quotePanes +
                '</div>' +
            '</div>';
                    '<div class="story-card-top">' +
                        '<span class="story-badge badge-quote">' + MIN_ICONS.quote + ' ' + (isEn ? 'Quote' : 'Alıntı') + '</span>' +
                        '<div style="display:flex; align-items:center; gap:6px;">' +
                            '<button type="button" class="btn-card-del" onclick="removeBlockFromStory(' + idx + ')" title="' + (isEn ? 'Remove Quote' : 'Alıntıyı Kaldır') + '">' + MIN_ICONS.close + '</button>' +
                        '</div>' +
                    '</div>' +
                    quotePanes +
                '</div>' +
            '</div>';
        } else if (b.type === 'youtube') {
            // YouTube Video
            var ytThumb = b.id ? ('https://img.youtube.com/vi/' + b.id + '/hqdefault.jpg') : '';
            cardHtml = '<div class="story-card type-youtube" data-idx="' + idx + '">' +
                '<div class="drag-handle" title="' + (isEn ? 'Drag to reorder' : 'Sırasını değiştirmek için sürükleyin') + '">' + MIN_ICONS.drag + '</div>' +
                '<div class="story-card-thumb" style="background:#0f172a;">' +
                    (ytThumb ? '<img src="' + ytThumb + '" alt="YouTube" />' : '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-sub);font-size:12px;">' + (isEn ? 'No Video' : 'Video Yok') + '</div>') +
                '</div>' +
                '<div class="story-card-body">' +
                    '<div class="story-card-top">' +
                        '<span class="story-badge badge-youtube">' + MIN_ICONS.youtube + ' YouTube Video</span>' +
                        '<button type="button" class="btn-card-del" onclick="removeBlockFromStory(' + idx + ')" title="' + (isEn ? 'Remove Video' : 'Videoyu Kaldır') + '">' + MIN_ICONS.close + '</button>' +
                    '</div>' +
                    '<div style="display:flex; gap:8px; align-items:center;">' +
                        '<label style="font-size:12px; font-weight:600; color:var(--text-muted); margin:0; white-space:nowrap;">Video ID / Link:</label>' +
                        '<input type="text" data-field="youtube_id" class="form-control" style="font-size:12px; padding:5px 8px;" value="' + escapeHtml(b.id || '') + '" placeholder="Örn: 0SHfeXoVkF4" oninput="updateYoutubeField(' + idx + ', this.value)">' +
                    '</div>' +
                '</div>' +
            '</div>';
        } else {
            // Fotoğraf Kartı
            var cleanPath = (b.path || '').replace(/^\/+/, '');
            if (!cleanPath.startsWith('content/') && !cleanPath.startsWith('uploads/') && !cleanPath.startsWith('images/') && !cleanPath.startsWith('http')) {
                cleanPath = 'content/' + cleanPath;
            }
            var thumbUrl = cleanPath.startsWith('http') ? cleanPath : ('<?= url("") ?>' + cleanPath);
            var filename = (b.path || '').split('/').pop();
            var currPhotoIdx = photoIndex++;

            var currCoverVal = (document.getElementById('input_cover_image') ? document.getElementById('input_cover_image').value.trim() : '').replace(/^\/+/, '');
            var blockPathClean = (b.path || '').replace(/^\/+/, '');
            var isCover = currCoverVal && (currCoverVal === blockPathClean || currCoverVal.endsWith(blockPathClean) || blockPathClean.endsWith(currCoverVal));

            var photoAltInputs = SITE_LANGUAGES.map(function(l) {
                var lc = l.code;
                var phPhoto = (lc === 'en') ? 'Photo caption / description...' : 'Fotoğraf alt yazısı / açıklaması...';
                return '<input type="text" class="form-control block-lang-pane block-lang-' + lc + '" style="font-size:13px; padding:6px 10px; ' + (lc !== activeLang ? 'display:none;' : '') + '" value="' + escapeHtml(b['alt_' + lc] || '') + '" placeholder="' + phPhoto + '" oninput="updateCardAlt(' + idx + ', \'alt_' + lc + '\', this.value)">';
            }).join('');

            var focalPoint = b.focal || '50% 50%';
            var focalParts = focalPoint.split(' ');
            var focalX = focalParts[0] || '50%';
            var focalY = focalParts[1] || '50%';
            var isCustomFocal = (focalPoint !== '50% 50%');
            var isDiptych = (b.layout === 'row');

            cardHtml = '<div class="story-card type-photo ' + (isCover ? 'is-cover-card' : '') + ' ' + (isDiptych ? 'is-diptych-pair' : '') + '" data-idx="' + idx + '" data-card-lang="' + activeLang + '">' +
                '<div class="drag-handle" title="' + (isEn ? 'Drag to reorder' : 'Sırasını değiştirmek için sürükleyin') + '">' + MIN_ICONS.drag + '</div>' +
                '<div class="story-card-thumb" ondblclick="pickStoryBlockFocal(event, ' + idx + ')" title="' + (isEn ? 'Double-click to set focal point (Current: ' + focalPoint + ')' : 'Odak noktasını belirlemek için görsele çift tıklayın (Şu an: ' + focalPoint + ')') + '" style="position:relative; cursor:crosshair;">' +
                    '<img src="' + thumbUrl + '" alt="' + escapeHtml(b.alt_tr || b.alt_en || '') + '" loading="lazy" style="object-position:' + focalPoint + ';" />' +
                    '<span class="thumb-focal-dot" style="left:' + focalX + '; top:' + focalY + ';" title="' + focalPoint + '"></span>' +
                '</div>' +
                '<div class="story-card-body">' +
                    '<div class="story-card-top">' +
                        '<div style="display:flex; align-items:center; gap:8px; min-width:0; flex-wrap:wrap;">' +
                            '<span class="story-badge badge-photo">' + MIN_ICONS.photo + ' #' + currPhotoIdx + '</span>' +
                            (isDiptych ? '<span class="diptych-badge">' + (isEn ? 'Diptych' : 'Diptik') + '</span>' : '') +
                            (isCover ? '<span class="story-badge" style="background:var(--warning-soft); color:var(--warning-text); border:1px solid var(--warning-border); font-weight:700;">★ ' + (isEn ? 'Cover' : 'Kapak') + '</span>' : '') +
                            (isCustomFocal ? '<span style="font-size:12px; font-family:var(--font-mono); color:var(--text-muted); cursor:pointer; background:rgba(0,0,0,0.06); padding:1px 5px; border-radius:6px;" onclick="resetStoryBlockFocal(event, ' + idx + ')" title="' + (isEn ? 'Reset focal point' : 'Odak noktasını sıfırla') + '">' + focalPoint + ' ✕</span>' : '') +
                            '<span class="story-card-path" title="' + b.path + '">' + filename + '</span>' +
                        '</div>' +
                        '<div style="display:flex; align-items:center; gap:6px;">' +
                            '<div class="story-card-layout-pills card-tool">' +
                                '<button type="button" class="layout-pill ' + (b.layout === 'single' ? 'active' : '') + '" onclick="changeCardLayout(' + idx + ', \'single\')" title="' + (isEn ? 'Full Width' : 'Tam Genişlik') + '">' + (isEn ? 'Full' : 'Tam Boy') + '</button>' +
                                '<button type="button" class="layout-pill ' + (b.layout === 'row' ? 'active' : '') + '" onclick="changeCardLayout(' + idx + ', \'row\')" title="' + (isEn ? 'Diptych / Side-by-side Pair' : 'Diptik / Yan Yana İkili') + '">' + (isEn ? 'Diptych' : 'Diptik') + '</button>' +
                                '<button type="button" class="layout-pill ' + (b.layout === 'left' ? 'active' : '') + '" onclick="changeCardLayout(' + idx + ', \'left\')" title="' + (isEn ? 'Align Left' : 'Sola Yasla') + '">' + (isEn ? 'Left' : 'Sola') + '</button>' +
                                '<button type="button" class="layout-pill ' + (b.layout === 'right' ? 'active' : '') + '" onclick="changeCardLayout(' + idx + ', \'right\')" title="' + (isEn ? 'Align Right' : 'Sağa Yasla') + '">' + (isEn ? 'Right' : 'Sağa') + '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div style="display:flex; gap:8px; align-items:center;">' +
                        '<div style="flex:1;">' + photoAltInputs + '</div>' +
                        (isCover
                            ? '<button type="button" class="btn btn-primary btn-sm" style="font-size:12px; padding:5px 9px; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; border-radius:6px; background:#1e293b; color:#ffffff; border-color:#0f172a; font-weight:700;" onclick="toggleCoverFromStory(\'' + escapeHtml(b.path) + '\')" title="' + (isEn ? 'Remove Cover' : 'Kapağı Kaldır') + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="#60a5fa" stroke="#60a5fa" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> <span>' + (isEn ? 'Cover (Remove)' : 'Kapak (Kaldır)') + '</span></button>'
                            : '<button type="button" class="btn btn-secondary btn-sm card-tool" style="font-size:12px; padding:5px 9px; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; border-radius:6px;" onclick="toggleCoverFromStory(\'' + escapeHtml(b.path) + '\')" title="' + (isEn ? 'Set as Cover Photo' : 'Kapak Görseli Yap') + '">' + MIN_ICONS.star + ' <span>' + (isEn ? 'Make Cover' : 'Kapak Yap') + '</span></button>'
                        ) +
                        '<button type="button" class="btn-card-del card-tool" onclick="removeBlockFromStory(' + idx + ')" title="' + (isEn ? 'Remove from Stream' : 'Akıştan Kaldır') + '">' + MIN_ICONS.close + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        return cardHtml;
    }).join('');
}

// ── Kart ve Global Dil Geçiş Yöneticileri ──
function setCardLang(btn, lang) {
    var card = btn.closest('.story-card');
    if (!card) return;
    card.setAttribute('data-card-lang', lang);
    card.querySelectorAll('.lang-pill-group .lang-pill').forEach(function(p) {
        p.classList.toggle('active', p.getAttribute('data-lang') === lang);
    });
    var focused = false;
    card.querySelectorAll('.block-lang-pane').forEach(function(pane) {
        if (pane.classList.contains('block-lang-' + lang)) {
            pane.style.display = 'block';
            if (!focused) {
                var inp = pane.matches('input, textarea, [contenteditable="true"]') ? pane : pane.querySelector('input, textarea, [contenteditable="true"]');
                if (inp) {
                    try { inp.focus(); focused = true; } catch(e) {}
                }
            }
        } else {
            pane.style.display = 'none';
        }
    });
}

var CANVAS_I18N = {
    tr: {
        studioTitle: 'Editoryal Stüdyo & Fotoğraf Tuvali',
        studioSub: 'Fotoğraf sırası ve yerleşimleri tüm dillerde tek tuvalde ortaktır',
        title: 'Başlık',
        desc: 'Kısa Açıklama',
        loc: 'Konum / Coğrafya',
        year: 'Yıl / Dönem',
        intro: 'Editoryal Giriş Hikayesi',
        introDesc: 'Fotoğraflar ve bölümler başlamadan önce, sayfa başlığının hemen altında yer alan ana hikaye ve editoryal giriş metni.'
    },
    en: {
        studioTitle: 'Editorial Studio & Photo Canvas',
        studioSub: 'Photo sequence and layouts are shared across all languages on a single canvas',
        title: 'Title',
        desc: 'Short Description',
        loc: 'Location / Geography',
        year: 'Year / Period',
        intro: 'Editorial Intro Story',
        introDesc: 'Main story and editorial intro text appearing directly beneath the title before photos and sections.'
    }
};

function setGlobalCanvasLang(lang) {
    currentCanvasLang = lang;
    document.querySelectorAll('.canvas-lang-pill').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
    });

    var dict = CANVAS_I18N['<?= e($admin_lang === 'tr' ? 'tr' : 'en') ?>']; // etiketler panel dilinde kalır, içerik diline göre değişmez
    var elST = document.getElementById('canvasStudioTitle'); if (elST) elST.innerText = dict.studioTitle;
    var elSS = document.getElementById('canvasStudioSub'); if (elSS) elSS.innerText = dict.studioSub;
    var elLT = document.getElementById('labelFieldTitle'); if (elLT) elLT.innerText = dict.title;
    var elLD = document.getElementById('labelFieldDesc'); if (elLD) elLD.innerText = dict.desc;
    var elLL = document.getElementById('labelFieldLoc'); if (elLL) elLL.innerText = dict.loc;
    var elLY = document.getElementById('labelFieldYear'); if (elLY) elLY.innerText = dict.year;
    var elLI = document.getElementById('labelFieldIntro'); if (elLI) elLI.innerText = dict.intro;
    var elDI = document.getElementById('descFieldIntro'); if (elDI) elDI.innerText = dict.introDesc;

    switchFieldLang('title', lang);
    switchFieldLang('description', lang);
    switchFieldLang('location', lang);
    switchFieldLang('intro', lang);
    document.querySelectorAll('.slug-lang-pane').forEach(function(pane) {
        pane.style.display = pane.classList.contains('slug-lang-' + lang) ? 'block' : 'none';
    });
    document.querySelectorAll('.story-card').forEach(function(card) {
        card.setAttribute('data-card-lang', lang);
        card.querySelectorAll('.block-lang-pane').forEach(function(pane) {
            if (pane.classList.contains('block-lang-' + lang)) {
                pane.style.display = 'block';
            } else {
                pane.style.display = 'none';
            }
        });
    });
    checkLanguageForWarnings(lang);
}

function switchFieldLang(fieldName, lang) {
    var group = document.querySelector('.lang-pill-group[data-target="' + fieldName + '"]');
    if (group) {
        group.querySelectorAll('.lang-pill').forEach(function(p) {
            p.classList.toggle('active', p.getAttribute('data-lang') === lang);
        });
    }

    SITE_LANGUAGES.forEach(function(l) {
        var lc = l.code;
        var el = (fieldName === 'intro')
            ? document.getElementById('introEditable_' + lc)
            : (document.getElementById('input_' + fieldName + '_' + lc) || document.querySelector('.field-' + fieldName + '-' + lc));
        if (el) {
            el.style.display = (lc === lang) ? 'block' : 'none'; // focus yok: sayfa açılışında aşağı zıplatıyordu
        }
    });
}

function generateSlugForLang(lang) {
    var tEl = document.getElementById('input_title_' + lang);
    var tEn = document.getElementById('input_title_en');
    var tTr = document.getElementById('input_title_tr');
    var src = (tEl && tEl.value.trim()) || (tEn && tEn.value.trim()) || (tTr && tTr.value.trim()) || '';
    if (src) {
        var s = slugifyText(src);
        if (!s && tEn && tEn.value.trim()) {
            s = slugifyText(tEn.value.trim());
        }
        var sEl = document.getElementById('input_slug_' + lang);
        if (sEl) sEl.value = s;
    }
}

// ── Kaynak Dili Tespit Et (İngilizce daima birincil ana kaynaktır; yalnızca EN için TR kullanılır) ──
function detectSourceLang(targetLang) {
    if (targetLang === 'en') return 'tr';
    
    // Diğer tüm diller (hy, fr, de, tr vb.) için kesinlikle EN ana kaynaktır
    var tEn = document.getElementById('input_title_en');
    if (tEn && tEn.value.trim() !== '') return 'en';
    
    // Eğer İngilizce henüz doldurulmamışsa yedek olarak Türkçe'ye bak
    var tTr = document.getElementById('input_title_tr');
    if (targetLang !== 'tr' && tTr && tTr.value.trim() !== '') return 'tr';
    
    return 'en';
}

// ── Madde 9: Diğer Dilden Metinleri ve Fotoğraf Alt Yazılarını Senkronize Et / Kopyala ──
function copyFromSourceLang() {
    syncActiveInputsToBlocks();
    var targetLang = currentCanvasLang || 'en';
    var srcLang = detectSourceLang(targetLang);

    var fields = ['title', 'description', 'location'];
    var fieldNames = { title: 'Başlık', description: 'Kısa açıklama', location: 'Konum' };
    var filled = [];
    fields.forEach(function(f) {
        var srcEl = document.getElementById('input_' + f + '_' + srcLang);
        if ((!srcEl || !srcEl.value.trim()) && srcLang !== 'tr' && targetLang !== 'tr') {
            srcEl = document.getElementById('input_' + f + '_tr');
        }
        var dstEl = document.getElementById('input_' + f + '_' + targetLang);
        if (!srcEl || !dstEl) return;
        if (!dstEl.value.trim()) dstEl.value = srcEl.value;
        else if (dstEl.value !== srcEl.value) filled.push([fieldNames[f] || f, srcEl, dstEl]);
    });
    // Dolu alanlar tek seferde sorulur; boş alanlar ve bloklar beklemeden kopyalanır
    if (filled.length) {
        adminConfirm(filled.map(function (x) { return x[0]; }).join(', ') + ' (' + targetLang.toUpperCase() + ') zaten dolu. Kaynak dildeki metin üzerine yazılsın mı?', { ok: 'Üzerine yaz', danger: false }).then(function (ok) {
            if (ok) filled.forEach(function (x) { x[2].value = x[1].value; x[2].dispatchEvent(new Event('input', { bubbles: true })); });
        });
    }

    var srcIntroEl = document.getElementById('introEditable_' + srcLang);
    if ((!srcIntroEl || !getRichContent(srcIntroEl)) && srcLang !== 'tr' && targetLang !== 'tr') {
        srcIntroEl = document.getElementById('introEditable_tr');
    }
    var dstIntroEl = document.getElementById('introEditable_' + targetLang);
    if (srcIntroEl && dstIntroEl && !getRichContent(dstIntroEl)) {
        dstIntroEl.innerHTML = srcIntroEl.innerHTML;
        studioIntro[targetLang] = getRichContent(dstIntroEl);
    }

    studioBlocks.forEach(function(b) {
        if (b.type === 'photo') {
            var sAlt = b['alt_' + srcLang] || (srcLang !== 'tr' ? (b['alt_tr'] || '') : '');
            if (!b['alt_' + targetLang] && sAlt) {
                b['alt_' + targetLang] = sAlt;
            }
        } else if (b.type === 'chapter') {
            var sTitle = b['title_' + srcLang] || (srcLang !== 'tr' ? (b['title_tr'] || '') : '');
            var sLead = b['lead_' + srcLang] || (srcLang !== 'tr' ? (b['lead_tr'] || '') : '');
            if (!b['title_' + targetLang] && sTitle) b['title_' + targetLang] = sTitle;
            if (!b['lead_' + targetLang] && sLead) b['lead_' + targetLang] = sLead;
        } else if (b.type === 'text' || b.type === 'quote') {
            var sTxt = b['text_' + srcLang] || (srcLang !== 'tr' ? (b['text_tr'] || '') : '');
            if (!b['text_' + targetLang] && sTxt) b['text_' + targetLang] = sTxt;
        }
    });

    renderStoryCanvas();
}

// ── Temizleme & Kota Uyarısı Filtreleme Yardımcıları ──
function isTranslationWarningText(str) {
    if (!str) return false;
    var lower = String(str).toLowerCase().trim();
    if (lower.indexOf('mymemory warning') !== -1) return true;
    if (lower.indexOf('used all available free translations') !== -1) return true;
    if (lower.indexOf('translated.net/doc/usagelimits') !== -1) return true;
    if (lower.indexOf('next available in') !== -1) return true;
    if (lower.indexOf('quota finished') !== -1) return true;
    if (lower.indexOf('too many requests') !== -1) return true;
    if (lower.indexOf('warning:') === 0 && (lower.indexOf('limit') !== -1 || lower.indexOf('free') !== -1 || lower.indexOf('available') !== -1)) return true;
    return false;
}

// Hatalı / Limit Uyarılarını Temizle (MYMEMORY WARNING vb.)
function purgeTranslationWarnings(showToast) {
    syncActiveInputsToBlocks();
    var lang = currentCanvasLang || 'en';
    var count = 0;

    // 1. Standart Girişler
    var titleEl = document.getElementById('input_title_' + lang);
    if (titleEl && isTranslationWarningText(titleEl.value)) {
        titleEl.value = '';
        count++;
    }
    var descEl = document.getElementById('input_description_' + lang);
    if (descEl && isTranslationWarningText(descEl.value)) {
        descEl.value = '';
        count++;
    }
    var locEl = document.getElementById('input_location_' + lang);
    if (locEl && isTranslationWarningText(locEl.value)) {
        locEl.value = '';
        count++;
    }

    // 2. Giriş Hikayesi
    var introEl = document.getElementById('introEditable_' + lang);
    if (introEl && isTranslationWarningText(introEl.innerHTML)) {
        introEl.innerHTML = '';
        studioIntro[lang] = '';
        count++;
    }
    if (studioIntro[lang] && isTranslationWarningText(studioIntro[lang])) {
        studioIntro[lang] = '';
        count++;
    }

    // 3. Hikaye Blokları
    studioBlocks.forEach(function(b) {
        if (b.type === 'photo') {
            if (isTranslationWarningText(b['alt_' + lang])) {
                b['alt_' + lang] = '';
                count++;
            }
        } else if (b.type === 'chapter') {
            if (isTranslationWarningText(b['title_' + lang])) {
                b['title_' + lang] = '';
                count++;
            }
            if (isTranslationWarningText(b['lead_' + lang])) {
                b['lead_' + lang] = '';
                count++;
            }
        } else if (b.type === 'text' || b.type === 'quote') {
            if (isTranslationWarningText(b['text_' + lang])) {
                b['text_' + lang] = '';
                count++;
            }
        }
    });

    renderStoryCanvas();
    checkLanguageForWarnings(lang);

    if (showToast) {
        if (count > 0) {
            alert('✓ ' + count + ' adet hatalı MyMemory uyarı metni başarıyla temizlendi.');
        } else {
            alert('Aktif dilde (' + lang.toUpperCase() + ') herhangi bir hatalı uyarı metni tespit edilmedi.');
        }
    }
}

// Aktif Dilin Tüm Alanlarını Boşalt
function clearAllFieldsCurrentLang() {
    var lang = currentCanvasLang || 'en';
    var langUpper = lang.toUpperCase();
    adminConfirm(langUpper + ' dilindeki başlık, açıklama, giriş hikayesi, bölüm ve alt yazı metinleri boşaltılacak. Kaydetmeden çıkarsanız eski hali korunur.', { title: langUpper + ' dili boşaltılsın mı?', ok: 'Boşalt' }).then(function (ok) {
        if (ok) clearAllFieldsCurrentLangNow(lang, langUpper);
    });
}

function clearAllFieldsCurrentLangNow(lang, langUpper) {
    syncActiveInputsToBlocks();

    var titleEl = document.getElementById('input_title_' + lang);
    if (titleEl) titleEl.value = '';
    var descEl = document.getElementById('input_description_' + lang);
    if (descEl) descEl.value = '';
    var locEl = document.getElementById('input_location_' + lang);
    if (locEl) locEl.value = '';

    var introEl = document.getElementById('introEditable_' + lang);
    if (introEl) introEl.innerHTML = '';
    studioIntro[lang] = '';

    studioBlocks.forEach(function(b) {
        if (b.type === 'photo') {
            b['alt_' + lang] = '';
        } else if (b.type === 'chapter') {
            b['title_' + lang] = '';
            b['lead_' + lang] = '';
        } else if (b.type === 'text' || b.type === 'quote') {
            b['text_' + lang] = '';
        }
    });

    renderStoryCanvas();
    checkLanguageForWarnings(lang);
    alert('✓ ' + langUpper + ' diline ait tüm alanlar temizlendi.');
}

// Dil Değişiminde veya Sayfa Açılışında Hatalı Uyarı Denetimi
function checkLanguageForWarnings(lang) {
    var banner = document.getElementById('bannerTranslationWarning');
    if (!banner) return;
    var count = 0;

    var titleEl = document.getElementById('input_title_' + lang);
    if (titleEl && isTranslationWarningText(titleEl.value)) count++;
    var descEl = document.getElementById('input_description_' + lang);
    if (descEl && isTranslationWarningText(descEl.value)) count++;
    var locEl = document.getElementById('input_location_' + lang);
    if (locEl && isTranslationWarningText(locEl.value)) count++;

    var introEl = document.getElementById('introEditable_' + lang);
    if (introEl && isTranslationWarningText(introEl.innerHTML)) count++;
    else if (studioIntro[lang] && isTranslationWarningText(studioIntro[lang])) count++;

    studioBlocks.forEach(function(b) {
        if (b.type === 'photo' && isTranslationWarningText(b['alt_' + lang])) count++;
        else if (b.type === 'chapter') {
            if (isTranslationWarningText(b['title_' + lang])) count++;
            if (isTranslationWarningText(b['lead_' + lang])) count++;
        } else if ((b.type === 'text' || b.type === 'quote') && isTranslationWarningText(b['text_' + lang])) count++;
    });

    if (count > 0) {
        banner.style.display = 'flex';
        var desc = document.getElementById('bannerWarningDesc');
        if (desc) {
            desc.textContent = lang.toUpperCase() + ' dilinde MyMemory çeviri limiti uyarısı içeren ' + count + ' alan tespit edildi. Tek tıkla temizleyebilirsiniz.';
        }
    } else {
        banner.style.display = 'none';
    }
}

// ── Otomatik Çeviri İlerleme Modalı Yönetimi ──
var isAutoTranslateRunning = false;
var cancelTranslateRequested = false;

function openTranslateModal(srcLang, targetLang, tasks) {
    var modal = document.getElementById('modalAutoTranslateProgress');
    if (!modal) return;

    var sObj = (typeof SITE_LANGUAGES !== 'undefined' && SITE_LANGUAGES.find(function(l) { return l.code === srcLang; })) || { flag: '🌐', name_tr: srcLang.toUpperCase() };
    var tObj = (typeof SITE_LANGUAGES !== 'undefined' && SITE_LANGUAGES.find(function(l) { return l.code === targetLang; })) || { flag: '🌐', name_tr: targetLang.toUpperCase() };

    var langPairEl = document.getElementById('modalTranslateLangPair');
    if (langPairEl) {
        langPairEl.innerHTML = (sObj.flag || '🌐') + ' ' + (sObj.name_tr || srcLang.toUpperCase()) + ' ➔ ' + (tObj.flag || '🌐') + ' ' + (tObj.name_tr || targetLang.toUpperCase());
    }

    var listEl = document.getElementById('modalTranslateTasksList');
    if (listEl) {
        listEl.innerHTML = tasks.map(function(t, idx) {
            var snippet = escapeHtml(t.text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim());
            if (snippet.length > 55) snippet = snippet.substring(0, 52) + '...';
            return '<div id="task-row-' + idx + '" style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-bottom:1px solid var(--border-subtle); font-size:12px; transition:background 0.2s;">' +
                '<div style="display:flex; align-items:center; gap:8px; overflow:hidden; padding-right:10px;">' +
                    '<span style="font-size:14px; flex-shrink:0;">' + t.icon + '</span>' +
                    '<div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' +
                        '<strong style="color:var(--text-main);">' + escapeHtml(t.label) + ':</strong> ' +
                        '<span style="color:var(--text-muted);">"' + snippet + '"</span>' +
                    '</div>' +
                '</div>' +
                '<div id="task-status-' + idx + '" style="flex-shrink:0;">' +
                    '<span style="background:var(--bg-canvas-subtle); color:var(--text-muted); font-size:12px; padding:2px 8px; border-radius:10px; font-weight:600;">⏳ Bekliyor</span>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    var alertEl = document.getElementById('modalStatusAlert');
    if (alertEl) {
        alertEl.style.display = 'none';
        alertEl.innerHTML = '';
    }

    var btnCancel = document.getElementById('btnCancelTranslate');
    if (btnCancel) btnCancel.style.display = 'inline-block';
    var btnClose = document.getElementById('btnCloseTranslate');
    if (btnClose) btnClose.style.display = 'none';

    updateModalProgress(0, tasks.length, 0, 0, 'Çeviri başlatılıyor...');
    modal.style.display = 'flex';
}

function updateTaskRowUI(idx, t) {
    var row = document.getElementById('task-row-' + idx);
    var statusEl = document.getElementById('task-status-' + idx);
    if (!statusEl) return;

    if (t.status === 'translating') {
        if (row) {
            row.style.background = '#f0f9ff';
            try { row.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch(e) {}
        }
        statusEl.innerHTML = '<span style="background:#e0f2fe; color:#0284c7; font-size:12px; padding:2px 8px; border-radius:10px; font-weight:600; display:inline-flex; align-items:center; gap:4px;"><span style="width:9px; height:9px; border:2px solid currentColor; border-right-color:transparent; border-radius:50%; display:inline-block; animation:modalSpin 0.75s linear infinite;"></span> Çevriliyor...</span>';
    } else if (t.status === 'success') {
        if (row) row.style.background = '#f0fdf4';
        var preview = escapeHtml(t.resultText.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim());
        if (preview.length > 28) preview = preview.substring(0, 25) + '...';
        statusEl.innerHTML = '<span style="background:var(--success-soft); color:var(--success-text); font-size:12px; padding:2px 8px; border-radius:10px; font-weight:600;" title="' + escapeHtml(t.resultText) + '">✅ ' + (preview || 'Tamamlandı') + '</span>';
    } else if (t.status === 'failed') {
        if (row) row.style.background = '#fff1f2';
        statusEl.innerHTML = '<span style="background:var(--danger-soft); color:var(--danger-text); font-size:12px; padding:2px 8px; border-radius:10px; font-weight:600;" title="' + escapeHtml(t.errorMsg || 'Limit aşıldı') + '">⚠️ Atlandı (Korundu)</span>';
    }
}

function updateModalProgress(completed, total, success, failed, customLabel) {
    var pct = total > 0 ? Math.round((completed / total) * 100) : 0;
    var bar = document.getElementById('modalProgressBar');
    if (bar) bar.style.width = pct + '%';
    var pctEl = document.getElementById('modalProgressPercent');
    if (pctEl) pctEl.textContent = pct + '%';

    var textEl = document.getElementById('modalProgressText');
    if (textEl) {
        textEl.textContent = customLabel || (completed + ' / ' + total + ' alan tamamlandı');
    }

    var sTot = document.getElementById('statTotal');
    if (sTot) sTot.textContent = 'Toplam: ' + total + ' alan';
    var sSuc = document.getElementById('statSuccess');
    if (sSuc) sSuc.textContent = '✅ ' + success + ' Başarılı';
    var sFail = document.getElementById('statFailed');
    if (sFail) sFail.textContent = '⚠️ ' + failed + ' Atlanan / Korunan';
    var sRem = document.getElementById('statRemaining');
    if (sRem) sRem.textContent = '⏳ ' + Math.max(0, total - completed) + ' Kalan';
}

function finishAutoTranslate(success, failed, total, targetLang) {
    isAutoTranslateRunning = false;
    renderStoryCanvas();
    checkLanguageForWarnings(targetLang);

    var btnCancel = document.getElementById('btnCancelTranslate');
    if (btnCancel) btnCancel.style.display = 'none';
    var btnClose = document.getElementById('btnCloseTranslate');
    if (btnClose) btnClose.style.display = 'inline-block';

    updateModalProgress(total, total, success, failed, 'İşlem Tamamlandı');

    var alertEl = document.getElementById('modalStatusAlert');
    if (alertEl) {
        alertEl.style.display = 'block';
        if (failed > 0) {
            alertEl.style.background = '#fff7ed';
            alertEl.style.border = '1px solid #ffedd5';
            alertEl.style.color = '#c2410c';
            alertEl.innerHTML = '⚠️ <strong>' + success + ' alan başarıyla çevrildi.</strong><br>' + failed + ' alan ise çeviri servisi limiti nedeniyle atlandı ve hata mesajıyla kirlenmemesi için mevcut hali korundu.';
        } else {
            alertEl.style.background = '#f0fdf4';
            alertEl.style.border = '1px solid #bbf7d0';
            alertEl.style.color = '#15803d';
            alertEl.innerHTML = '✅ <strong>Mükemmel!</strong> ' + total + ' alanın tamamı seçili dile başarıyla çevrildi ve tuvale uygulandı.';
        }
    }
}

function cancelAutoTranslate() {
    if (isAutoTranslateRunning) {
        cancelTranslateRequested = true;
        var textEl = document.getElementById('modalProgressText');
        if (textEl) textEl.textContent = 'İşlem durduruluyor...';
    } else {
        closeTranslateModal();
    }
}

function closeTranslateModal() {
    var hide = function () { var modal = document.getElementById('modalAutoTranslateProgress'); if (modal) modal.style.display = 'none'; };
    if (!isAutoTranslateRunning) return hide();
    adminConfirm('Çeviri sürüyor. Durdurursanız şu ana kadar çevrilen alanlar kalır, kalanlar çevrilmez.', { title: 'Çeviri durdurulsun mu?', ok: 'Durdur ve kapat', danger: false }).then(function (ok) {
        if (!ok) return;
        cancelTranslateRequested = true;
        hide();
    });
}

// ── Canlı İlerlemeli Otomatik Çeviri Asistanı ──
function autoTranslateCurrentLang() {
    if (isAutoTranslateRunning) return;
    var isEn = (window.ADMIN_LANG === 'en');
    syncActiveInputsToBlocks();
    var targetLang = currentCanvasLang || 'en';
    var srcLang = detectSourceLang(targetLang);

    var tasks = [];
    function queueTask(label, icon, rawText, applyFn, fieldId) {
        if (rawText && String(rawText).trim() !== '') {
            tasks.push({
                label: label,
                icon: icon,
                text: String(rawText),
                apply: applyFn,
                fieldId: fieldId,
                status: 'pending',
                resultText: '',
                errorMsg: ''
            });
        }
    }

    var srcTitle = document.getElementById('input_title_' + srcLang);
    if ((!srcTitle || srcTitle.value.trim() === '') && srcLang !== 'tr' && targetLang !== 'tr') {
        srcTitle = document.getElementById('input_title_tr');
    }
    var dstTitle = document.getElementById('input_title_' + targetLang);
    if (srcTitle && dstTitle && srcTitle.value.trim() !== '') {
        queueTask((isEn ? 'Title' : 'Başlık'), '📝', srcTitle.value, function(res) {
            dstTitle.value = res;
            if (targetLang === 'en') generateSlugEn();
            else if (targetLang === 'tr') generateSlugTr();
            else generateSlugForLang(targetLang);
        }, 'title');
    }

    var srcDesc = document.getElementById('input_description_' + srcLang);
    if ((!srcDesc || srcDesc.value.trim() === '') && srcLang !== 'tr' && targetLang !== 'tr') {
        srcDesc = document.getElementById('input_description_tr');
    }
    var dstDesc = document.getElementById('input_description_' + targetLang);
    if (srcDesc && dstDesc && srcDesc.value.trim() !== '') {
        queueTask((isEn ? 'Short Description' : 'Kısa Açıklama'), '📄', srcDesc.value, function(res) { dstDesc.value = res; }, 'desc');
    }

    var srcLoc = document.getElementById('input_location_' + srcLang);
    if ((!srcLoc || srcLoc.value.trim() === '') && srcLang !== 'tr' && targetLang !== 'tr') {
        srcLoc = document.getElementById('input_location_tr');
    }
    var dstLoc = document.getElementById('input_location_' + targetLang);
    if (srcLoc && dstLoc && srcLoc.value.trim() !== '') {
        queueTask((isEn ? 'Location / Geography' : 'Konum / Coğrafya'), '📍', srcLoc.value, function(res) { dstLoc.value = res; }, 'loc');
    }

    var srcIntroEl = document.getElementById('introEditable_' + srcLang);
    if ((!srcIntroEl || !getRichContent(srcIntroEl)) && srcLang !== 'tr' && targetLang !== 'tr') {
        srcIntroEl = document.getElementById('introEditable_tr');
    }
    var dstIntroEl = document.getElementById('introEditable_' + targetLang);
    var srcIntroHtml = srcIntroEl ? getRichContent(srcIntroEl) : '';
    if (srcIntroHtml && dstIntroEl) {
        queueTask('Giriş Hikayesi (Intro Story)', '📖', srcIntroHtml, function(res) {
            dstIntroEl.innerHTML = res;
            studioIntro[targetLang] = res;
        }, 'intro');
    }

    var photoNum = 0;
    studioBlocks.forEach(function(b, bIdx) {
        if (b.type === 'photo') {
            photoNum++;
            var sAlt = b['alt_' + srcLang] || (srcLang !== 'tr' ? (b['alt_tr'] || '') : '');
            if (sAlt) {
                (function(pn) {
                    queueTask('Fotoğraf #' + pn + ' Alt Yazısı', '🖼️', sAlt, function(res) { b['alt_' + targetLang] = res; }, 'photo_' + bIdx);
                })(photoNum);
            }
        } else if (b.type === 'chapter') {
            var sTitle = b['title_' + srcLang] || (srcLang !== 'tr' ? (b['title_tr'] || '') : '');
            var sLead = b['lead_' + srcLang] || (srcLang !== 'tr' ? (b['lead_tr'] || '') : '');
            if (sTitle) queueTask('Bölüm Başlığı', '📑', sTitle, function(res) { b['title_' + targetLang] = res; }, 'ch_title_' + bIdx);
            if (sLead) queueTask('Bölüm Özeti', '📑', sLead, function(res) { b['lead_' + targetLang] = res; }, 'ch_lead_' + bIdx);
        } else if (b.type === 'text' || b.type === 'quote') {
            var sTxt = b['text_' + srcLang] || (srcLang !== 'tr' ? (b['text_tr'] || '') : '');
            var typeName = b.type === 'quote' ? 'Alıntı (Quote)' : 'Metin Bloğu (Text)';
            if (sTxt) queueTask(typeName, '💬', sTxt, function(res) { b['text_' + targetLang] = res; }, 'text_' + bIdx);
        }
    });

    if (!tasks.length) {
        alert('Kaynak dil olarak İngilizce (EN) içeriği bulunamadı. Lütfen önce İngilizce alanları doldurun.');
        return;
    }

    openTranslateModal(srcLang, targetLang, tasks);

    isAutoTranslateRunning = true;
    cancelTranslateRequested = false;

    var completedCount = 0;
    var successCount = 0;
    var failCount = 0;
    var batchSize = 2;
    var taskIndex = 0;

    function processNextBatch() {
        if (cancelTranslateRequested || taskIndex >= tasks.length) {
            finishAutoTranslate(successCount, failCount, tasks.length, targetLang);
            return;
        }

        var currentBatch = tasks.slice(taskIndex, taskIndex + batchSize);
        var currentIndices = [];
        for (var i = 0; i < currentBatch.length; i++) {
            var idx = taskIndex + i;
            currentIndices.push(idx);
            tasks[idx].status = 'translating';
            updateTaskRowUI(idx, tasks[idx]);
        }

        updateModalProgress(completedCount, tasks.length, successCount, failCount, 'Çevriliyor (' + (taskIndex + 1) + '-' + Math.min(taskIndex + batchSize, tasks.length) + ' / ' + tasks.length + ')...');

        fetch('<?= url("admin/api-translate.php") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: '<?= csrf_token() ?>',
                source_lang: srcLang,
                target_lang: targetLang,
                texts: currentBatch.map(function(t) { return t.text; })
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var translations = (data && data.ok && Array.isArray(data.translations)) ? data.translations : [];
            var results = (data && Array.isArray(data.results)) ? data.results : [];

            currentBatch.forEach(function(t, bIdx) {
                var globalIdx = currentIndices[bIdx];
                var trText = translations[bIdx] || '';
                var resObj = results[bIdx] || null;

                // GÜVENLİK FİLTRESİ: MYMEMORY WARNING VB. YAZILARI KESİNLİKLE KABUL ETME!
                if (trText && !isTranslationWarningText(trText) && (resObj ? resObj.success : true)) {
                    t.status = 'success';
                    t.resultText = trText;
                    try {
                        t.apply(trText);
                    } catch (e) {
                        console.error('Apply error:', e);
                    }
                    successCount++;
                } else {
                    t.status = 'failed';
                    t.errorMsg = (resObj && resObj.error) ? resObj.error : 'Kota limiti veya servis hatası (Alan korundu)';
                    failCount++;
                }
                completedCount++;
                updateTaskRowUI(globalIdx, t);
            });

            taskIndex += batchSize;
            updateModalProgress(completedCount, tasks.length, successCount, failCount);

            if (!cancelTranslateRequested && taskIndex < tasks.length) {
                setTimeout(processNextBatch, 120);
            } else {
                finishAutoTranslate(successCount, failCount, tasks.length, targetLang);
            }
        })
        .catch(function(err) {
            console.error('Fetch error:', err);
            currentBatch.forEach(function(t, bIdx) {
                var globalIdx = currentIndices[bIdx];
                t.status = 'failed';
                t.errorMsg = 'Bağlantı hatası (Alan korundu)';
                failCount++;
                completedCount++;
                updateTaskRowUI(globalIdx, t);
            });
            taskIndex += batchSize;
            updateModalProgress(completedCount, tasks.length, successCount, failCount);

            if (!cancelTranslateRequested && taskIndex < tasks.length) {
                setTimeout(processNextBatch, 250);
            } else {
                finishAutoTranslate(successCount, failCount, tasks.length, targetLang);
            }
        });
    }

    setTimeout(processNextBatch, 100);
}

// ── HTML Escape Yardımcısı ──
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ── Blok Ekleme Fonksiyonları ──
function addTextBlock() {
    syncActiveInputsToBlocks();
    studioBlocks.push({
        type: 'text',
        text_tr: '',
        text_en: ''
    });
    renderStoryCanvas();
}

function addChapterBlock() {
    syncActiveInputsToBlocks();
    studioBlocks.push({
        type: 'chapter',
        title_tr: '',
        title_en: '',
        lead_tr: '',
        lead_en: '',
        theme: 'dark'
    });
    renderStoryCanvas();
}

function addQuoteBlock() {
    syncActiveInputsToBlocks();
    studioBlocks.push({
        type: 'quote',
        text_tr: '',
        text_en: ''
    });
    renderStoryCanvas();
}

function addYoutubeBlock() {
    syncActiveInputsToBlocks();
    studioBlocks.push({
        type: 'youtube',
        id: ''
    });
    renderStoryCanvas();
}

// ── Kart Güncelleme İşleyicileri ──
function updateChapterField(idx, field, value) {
    if (studioBlocks[idx]) {
        studioBlocks[idx][field] = value;
    }
}

function changeChapterTheme(idx, theme) {
    if (studioBlocks[idx]) {
        syncActiveInputsToBlocks();
        studioBlocks[idx].theme = theme;
        renderStoryCanvas();
    }
}

function updateYoutubeField(idx, value) {
    if (studioBlocks[idx]) {
        var vid = value.trim();
        var match = vid.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
        if (match) vid = match[1];
        studioBlocks[idx].id = vid;
    }
}

function updateCardAlt(idx, field, value) {
    if (studioBlocks[idx]) {
        studioBlocks[idx][field] = value;
    }
}

function changeCardLayout(idx, newLayout) {
    if (studioBlocks[idx]) {
        syncActiveInputsToBlocks();
        studioBlocks[idx].layout = newLayout;
        renderStoryCanvas();
    }
}

function removeBlockFromStory(idx) {
    syncActiveInputsToBlocks();
    studioBlocks.splice(idx, 1);
    renderStoryCanvas();
}

// ── Kapak Görseli Yönetimi (Seçme / Kaldırma / Değiştirme) ──
function removeCoverImage() {
    var input = document.getElementById('input_cover_image');
    if (input) input.value = '';
    var preview = document.getElementById('input_cover_image_preview');
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }
    var container = document.getElementById('cover_preview_container');
    if (container) container.style.display = 'none';
    var btnRemove = document.getElementById('btn_remove_cover');
    if (btnRemove) btnRemove.style.display = 'none';
    renderStoryCanvas();
}

function setCoverImage(path) {
    if (!path) {
        removeCoverImage();
        return;
    }
    var input = document.getElementById('input_cover_image');
    if (input) input.value = path;
    var preview = document.getElementById('input_cover_image_preview');
    var container = document.getElementById('cover_preview_container');
    var btnRemove = document.getElementById('btn_remove_cover');

    var cleanPath = path.replace(/^\/+/, '');
    if (!cleanPath.startsWith('content/') && !cleanPath.startsWith('uploads/') && !cleanPath.startsWith('images/') && !cleanPath.startsWith('processed_images/') && !cleanPath.startsWith('http')) {
        cleanPath = 'content/' + cleanPath;
    }
    var fullUrl = cleanPath.startsWith('http') ? cleanPath : ('<?= url("") ?>' + cleanPath);
    if (preview) {
        preview.src = fullUrl;
        preview.style.display = 'block';
    }
    if (container) container.style.display = 'block';
    if (btnRemove) btnRemove.style.display = 'inline-flex';
    renderStoryCanvas();
}

function toggleCoverFromStory(path) {
    var input = document.getElementById('input_cover_image');
    var current = (input ? input.value.trim() : '').replace(/^\/+/, '');
    var target = (path || '').replace(/^\/+/, '');
    if (current && (current === target || current.endsWith(target) || target.endsWith(current))) {
        removeCoverImage();
    } else {
        setCoverImage(path);
    }
}

function onCoverInputChange() {
    var val = document.getElementById('input_cover_image').value.trim();
    if (val) {
        setCoverImage(val);
    } else {
        removeCoverImage();
    }
}

function setCoverFromStory(path) {
    toggleCoverFromStory(path);
}

// ── B3: Akıllı Odak Noktası (Focal Point Picker) ve Durum Yönetimi ──
function pickCoverFocal(e) {
    var wrapper = document.getElementById('cover_focal_wrapper');
    if (!wrapper) return;
    var rect = wrapper.getBoundingClientRect();
    var x = Math.round(Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100)));
    var y = Math.round(Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100)));
    var focalVal = x + '% ' + y + '%';
    
    var inp = document.getElementById('input_cover_focal');
    if (inp) inp.value = focalVal;
    var pin = document.getElementById('cover_focal_pin');
    if (pin) {
        pin.style.left = x + '%';
        pin.style.top = y + '%';
    }
    var lbl = document.getElementById('cover_focal_label');
    if (lbl) lbl.innerText = focalVal;
    var prev = document.getElementById('input_cover_image_preview');
    if (prev) prev.style.objectPosition = focalVal;
}

function resetCoverFocal() {
    var focalVal = '50% 50%';
    var inp = document.getElementById('input_cover_focal');
    if (inp) inp.value = focalVal;
    var pin = document.getElementById('cover_focal_pin');
    if (pin) {
        pin.style.left = '50%';
        pin.style.top = '50%';
    }
    var lbl = document.getElementById('cover_focal_label');
    if (lbl) lbl.innerText = focalVal;
    var prev = document.getElementById('input_cover_image_preview');
    if (prev) prev.style.objectPosition = focalVal;
}

function pickStoryBlockFocal(e, idx) {
    if (e.target.closest('.thumb-focal-dot')) return;
    var thumb = e.currentTarget;
    var rect = thumb.getBoundingClientRect();
    var x = Math.round(Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100)));
    var y = Math.round(Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100)));
    var focalVal = x + '% ' + y + '%';
    if (studioBlocks[idx]) {
        syncActiveInputsToBlocks();
        studioBlocks[idx].focal = focalVal;
        renderStoryCanvas();
    }
}

function resetStoryBlockFocal(e, idx) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    if (studioBlocks[idx]) {
        syncActiveInputsToBlocks();
        studioBlocks[idx].focal = '50% 50%';
        renderStoryCanvas();
    }
}

function toggleItemStatusHelp() {
    var sel = document.getElementById('input_item_status');
    var notice = document.getElementById('unlisted_status_notice');
    if (sel && notice) {
        notice.style.display = (sel.value === 'unlisted') ? 'block' : 'none';
    }
}

// ── SortableJS Sürükle-Bırak Başlatma ──
function initSortable() {
    var el = document.getElementById('storyCanvas');
    if (!el || typeof Sortable === 'undefined') return;

    if (sortableInstance) {
        try { sortableInstance.destroy(); } catch(e) {}
    }

    sortableInstance = new Sortable(el, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function(evt) {
            var oldIdx = evt.oldIndex;
            var newIdx = evt.newIndex;
            if (oldIdx !== newIdx) {
                syncActiveInputsToBlocks();
                var movedItem = studioBlocks.splice(oldIdx, 1)[0];
                studioBlocks.splice(newIdx, 0, movedItem);
                renderStoryCanvas();
            }
        }
    });
}

// ── Medya Kütüphanesinden Projeye Görsel Ekleme ──
function openMediaModalForProject() {
    var isNew = <?= $is_new ? 'true' : 'false' ?>;
    var slug = document.getElementById('input_slug') ? document.getElementById('input_slug').value.trim() : '';
    var defaultFolder = (!isNew && slug) ? getActiveProjectFolder() : 'all';
    openMediaModalForCallback(function(filePath, altText) {
        syncActiveInputsToBlocks();
        studioBlocks.push({
            type: 'photo',
            path: filePath,
            alt_tr: altText || '',
            alt_en: altText || '',
            caption: '',
            layout: 'single'
        });
        renderStoryCanvas();
    }, defaultFolder);
}

// ── Projeye Doğrudan Hızlı Yükleme (Klasör Hedefli & AVIF/WebP) ──
function uploadDirectToProject(files) {
    if (!files || !files.length) return;

    var targetFolder = getActiveProjectFolder();
    var queue = Array.from(files);

    function processUploadQueue() {
        if (!queue.length) return;
        var file = queue.shift();

        var formData = new FormData();
        formData.append('image', file);
        formData.append('csrf_token', '<?= csrf_token() ?>');
        formData.append('folder', targetFolder);
        formData.append('item_type', document.getElementById('select_type') ? document.getElementById('select_type').value : '');
        formData.append('item_slug', document.getElementById('input_slug') ? document.getElementById('input_slug').value.trim() : '');

        fetch('<?= url("admin/upload.php") ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                syncActiveInputsToBlocks();
                studioBlocks.push({
                    type: 'photo',
                    path: data.file_path,
                    alt_tr: data.filename || '',
                    alt_en: data.filename || '',
                    caption: '',
                    layout: 'single'
                });
                renderStoryCanvas();
            } else {
                alert('Yükleme hatası: ' + (data.error || 'Bilinmeyen hata'));
            }
            processUploadQueue();
        })
        .catch(function(err) {
            alert('Sunucu hatası oluştu.');
            processUploadQueue();
        });
    }

    processUploadQueue();
}

function setupProjectDropZone() {
    var dz = document.getElementById('projectDropZone');
    if (!dz) return;

    dz.addEventListener('dragover', function(e) {
        e.preventDefault();
        dz.style.borderColor = '#d97706';
        dz.style.backgroundColor = '#fffbeb';
    });

    dz.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dz.style.borderColor = '#cbd5e1';
        dz.style.backgroundColor = '#f8fafc';
    });

    dz.addEventListener('drop', function(e) {
        e.preventDefault();
        dz.style.borderColor = '#cbd5e1';
        dz.style.backgroundColor = '#f8fafc';
        uploadDirectToProject(e.dataTransfer.files);
    });

    dz.addEventListener('click', function() {
        var input = dz.parentElement.querySelector('input[type="file"]');
        if (input) input.click();
    });
}

function onSlugEnChange() {
    var val = document.getElementById('input_slug_en').value.trim();
    var clean = slugifyText(val);
    document.getElementById('input_slug').value = clean;
    updateSlugPreviews();
    var f = getActiveProjectFolder();
    var el = document.getElementById('targetFolderNotice');
    if (el) el.innerText = 'Klasör: ' + f;
}

function onSlugTrChange() {
    updateSlugPreviews();
}

function generateSlugEn() {
    var tEn = document.getElementById('input_title_en').value.trim();
    var tTr = document.getElementById('input_title_tr').value.trim();
    var src = tEn || tTr;
    if (src) {
        var s = slugifyText(src);
        document.getElementById('input_slug_en').value = s;
        document.getElementById('input_slug').value = s;
        updateSlugPreviews();
        updateTargetFolderNotice();
    }
}

function generateSlugTr() {
    var tTr = document.getElementById('input_title_tr').value.trim();
    var tEn = document.getElementById('input_title_en').value.trim();
    var src = tTr || tEn;
    if (src) {
        var s = slugifyText(src);
        document.getElementById('input_slug_tr').value = s;
        updateSlugPreviews();
    }
}

function updateSlugPreviews() {
    var slugEn = document.getElementById('input_slug_en') ? document.getElementById('input_slug_en').value.trim() : '';
    var slugTr = document.getElementById('input_slug_tr') ? document.getElementById('input_slug_tr').value.trim() : '';
    
    var elEn = document.getElementById('slugEnPreview');
    if (elEn) elEn.innerHTML = 'URL: /en/<strong>' + (slugEn || '...') + '</strong>/';

    var elTr = document.getElementById('slugTrPreview');
    if (elTr) elTr.innerHTML = 'URL: /tr/<strong>' + (slugTr || '...') + '</strong>/';
}

function autoSlug() {
    var slugEnInput = document.getElementById('input_slug_en');
    var slugTrInput = document.getElementById('input_slug_tr');
    if (slugEnInput && !slugEnInput.value) {
        generateSlugEn();
    }
    if (slugTrInput && !slugTrInput.value) {
        generateSlugTr();
    }
}

function slugifyText(text) {
    if (window.erSlugify) return window.erSlugify(text); // ortak harf tablosu (Ermenice, Kiril, Yunan…)
    var trMap = {
        'ı': 'i', 'İ': 'i', 'I': 'i',
        'ş': 's', 'Ş': 's',
        'ğ': 'g', 'Ğ': 'g',
        'ü': 'u', 'Ü': 'u',
        'ö': 'o', 'Ö': 'o',
        'ç': 'c', 'Ç': 'c'
    };
    return text.toString()
        .replace(/['’`´]/g, '')
        .toLowerCase()
        .replace(/[ıİIşŞğĞüÜöÖçÇ]/g, function(m) { return trMap[m] || m; })
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}

// ── Sağ Panel Aç / Kapat & Odaklanma Modu (Focus Mode) ──
function toggleItemRightPanel() {
    var isCollapsed = document.body.classList.toggle('right-panel-collapsed');
    localStorage.setItem('item_edit_right_collapsed', isCollapsed ? 'true' : 'false');
    updateRightPanelUI();
    updateFocusModeBtn();
}

function updateRightPanelUI() {
    var isCollapsed = document.body.classList.contains('right-panel-collapsed');
    var isEn = (window.ADMIN_LANG === 'en');
    var btn = document.getElementById('rightPanelToggleBtn');
    var label = document.getElementById('rightPanelToggleLabel');
    var tabLabel = document.getElementById('tabRightPanelLabel');
    var tabBtn = document.getElementById('tabRightPanelToggleBtn');

    if (isCollapsed) {
        if (btn) {
            btn.classList.add('btn-primary');
            btn.classList.remove('btn-secondary');
            if (label) label.textContent = isEn ? 'Open Settings' : 'Ayarları Aç';
            btn.title = isEn ? 'Open Side Settings Panel (Ctrl+])' : 'Sağ Ayarlar Panelini Aç (Ctrl+])';
        }
        if (tabLabel) tabLabel.textContent = isEn ? 'Open Settings' : 'Ayarları Aç';
        if (tabBtn) {
            tabBtn.classList.add('btn-primary');
            tabBtn.classList.remove('btn-secondary');
        }
    } else {
        if (btn) {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
            if (label) label.textContent = isEn ? 'Side Panel' : 'Sağ Panel';
            btn.title = isEn ? 'Hide Side Settings Panel (Ctrl+])' : 'Sağ Ayarlar Panelini Gizle (Ctrl+])';
        }
        if (tabLabel) tabLabel.textContent = isEn ? 'Hide Settings' : 'Ayarları Gizle';
        if (tabBtn) {
            tabBtn.classList.remove('btn-primary');
            tabBtn.classList.add('btn-secondary');
        }
    }
}

function toggleFocusMode() {
    var isFocused = document.body.classList.contains('sidebar-collapsed') && document.body.classList.contains('right-panel-collapsed');
    if (isFocused) {
        // Her iki paneli de aç (Normal mod)
        document.body.classList.remove('sidebar-collapsed');
        document.documentElement.classList.remove('sidebar-collapsed');
        document.documentElement.classList.remove('sidebar-collapsed-init');
        document.body.classList.remove('right-panel-collapsed');
        localStorage.setItem('admin_sidebar_collapsed', 'false');
        localStorage.setItem('item_edit_right_collapsed', 'false');
    } else {
        // Her iki paneli de gizle (Maksimum geniş çalışma alanı)
        document.body.classList.add('sidebar-collapsed');
        document.documentElement.classList.add('sidebar-collapsed');
        document.body.classList.add('right-panel-collapsed');
        localStorage.setItem('admin_sidebar_collapsed', 'true');
        localStorage.setItem('item_edit_right_collapsed', 'true');
    }
    if (typeof updateSidebarToggleBtn === 'function') updateSidebarToggleBtn();
    updateRightPanelUI();
    updateFocusModeBtn();
}

function updateFocusModeBtn() {
    var btn = document.getElementById('focusModeBtn');
    var label = document.getElementById('focusModeLabel');
    if (!btn) return;
    var isEn = (window.ADMIN_LANG === 'en');
    var isFocused = document.body.classList.contains('sidebar-collapsed') && document.body.classList.contains('right-panel-collapsed');
    if (isFocused) {
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-secondary');
        if (label) label.textContent = isEn ? 'Normal Mode' : 'Normal Mod';
        btn.title = isEn ? 'Return to Normal View (Show Panels)' : 'Normal Görünüme Dön (Panelleri Göster)';
    } else {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
        if (label) label.textContent = isEn ? 'Focus' : 'Odaklan';
        btn.title = isEn ? 'Fullscreen Focus Mode (Hides Left and Right Panels)' : 'Tam Ekran Odaklanma Modu (Sol ve Sağ Panelleri Gizler)';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateRightPanelUI();
    updateFocusModeBtn();
    checkLanguageForWarnings(currentCanvasLang || 'tr');
});
</script>

<!-- Otomatik Çeviri İlerleme ve Süreç Modalı -->
<div id="modalAutoTranslateProgress" class="studio-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); backdrop-filter:blur(4px); z-index:10000; align-items:center; justify-content:center; padding:16px;">
    <div class="studio-modal-box" style="background:var(--bg-surface); border-radius:10px; width:100%; max-width:620px; box-shadow:var(--card-shadow); display:flex; flex-direction:column; max-height:85vh; overflow:hidden; border:1px solid var(--border-subtle); animation:modalPopIn 0.2s cubic-bezier(0.16, 1, 0.3, 1); color:var(--text-main);">
        <!-- Modal Header -->
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; background:var(--bg-surface-2);">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:10px; background:var(--accent-blue-subtle); display:flex; align-items:center; justify-content:center; font-size:18px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg></div>
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text-main);"><?= __a('Otomatik Çeviri Asistanı', 'Auto-Translation Assistant') ?></h3>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:2px;" id="modalTranslateLangPair"></div>
                </div>
            </div>
            <button type="button" onclick="closeTranslateModal()" style="background:transparent; border:none; font-size:20px; color:var(--text-muted); cursor:pointer; padding:4px 8px; border-radius:6px; line-height:1;" title="<?= __a('Kapat', 'Close') ?>">✕</button>
        </div>

        <!-- Modal Body -->
        <div style="padding:18px 20px; flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:14px;">
            <!-- Progress Bar & Summary Stats -->
            <div style="background:var(--bg-canvas-subtle); border:1px solid var(--border-subtle); border-radius:10px; padding:12px 14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <div style="font-size:13px; font-weight:600; color:var(--text-main);" id="modalProgressText">
                        <?= __a('Çeviriye hazırlanıyor...', 'Preparing translation...') ?>
                    </div>
                    <div style="font-size:12px; font-weight:700; color:var(--accent-blue);" id="modalProgressPercent">
                        0%
                    </div>
                </div>
                <!-- Bar -->
                <div style="height:8px; background:var(--border-subtle); border-radius:999px; overflow:hidden; position:relative;">
                    <div id="modalProgressBar" style="width:0%; height:100%; background:linear-gradient(90deg, #3b82f6, #6366f1); border-radius:999px; transition:width 0.25s ease;"></div>
                </div>
                <!-- Mini Stats Badges -->
                <div style="display:flex; gap:10px; margin-top:10px; font-size:12px; flex-wrap:wrap;">
                    <span style="background:var(--bg-surface); border:1px solid var(--border-subtle); padding:2px 8px; border-radius:10px; color:var(--text-muted);" id="statTotal">Toplam: 0 alan</span>
                    <span style="background:rgba(16,185,129,0.15); padding:2px 8px; border-radius:10px; color:var(--success-text); font-weight:600;" id="statSuccess">✅ 0 Başarılı</span>
                    <span style="background:rgba(225,29,72,0.15); padding:2px 8px; border-radius:10px; color:var(--danger-text); font-weight:600;" id="statFailed">⚠️ 0 Atlanan / Korunan</span>
                    <span style="background:var(--accent-blue-subtle); padding:2px 8px; border-radius:10px; color:var(--accent-blue); font-weight:600;" id="statRemaining">⏳ 0 Bekleyen</span>
                </div>
            </div>

            <!-- Task Log Items List -->
            <div>
                <div style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:6px; display:flex; justify-content:space-between; align-items:center;">
                    <span><?= __a('İşlem Aşamaları ve Alan Durumları', 'Process Stages & Field Statuses') ?></span>
                    <span style="font-size:12px; font-weight:400; color:var(--text-sub);" id="modalEngineInfo"><?= __a('Google Translate & Koruma Kalkanı Aktif', 'Google Translate & Protection Shield Active') ?></span>
                </div>
                <div id="modalTranslateTasksList" style="max-height:220px; overflow-y:auto; border:1px solid var(--border-subtle); border-radius:6px; background:var(--bg-surface); display:flex; flex-direction:column;">
                </div>
            </div>

            <!-- Live Status / Warning Message Area -->
            <div id="modalStatusAlert" style="display:none; padding:10px 12px; border-radius:6px; font-size:12px;"></div>
        </div>

        <!-- Modal Footer -->
        <div style="padding:12px 20px; border-top:1px solid var(--border-subtle); background:var(--bg-surface-2); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div style="font-size:12px; color:var(--text-muted);" id="modalFooterHint">
                <?= __a('Kota aşımları durumunda alanlar hata yazısıyla kirletilmez, orijinal metinler korunur.', 'Fields will never be polluted with error messages upon quota limit; existing content is preserved.') ?>
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" id="btnCancelTranslate" class="btn btn-secondary btn-sm" onclick="cancelAutoTranslate()" style="display:inline-block; font-size:12px;">
                    <?= __a('Durdur', 'Stop') ?>
                </button>
                <button type="button" id="btnCloseTranslate" class="btn btn-primary btn-sm" onclick="closeTranslateModal()" style="display:none; font-size:12px;">
                    <?= __a('Tamamlandı', 'Done') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes modalSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@keyframes modalPopIn { 0% { opacity: 0; transform: scale(0.96); } 100% { opacity: 1; transform: scale(1); } }
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
