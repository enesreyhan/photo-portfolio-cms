<?php
/**
 * admin/blog-edit.php
 * Blog / Yazılar & Makaleler Düzenleme ve Ekleme Editörü
 * Portfolyo/Sergi editöründen tamamen bağımsız, blog ve saha günlüklerine özel editoryal yazı stüdyosu.
 * Bağımsız sayfalar (type 'page', Sayfalar › Yeni › Boş sayfa) da bu editörle yazılır: ?type=page
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/../includes/design_editor.php';

require_login();

$pdo = DB::getConnection();
$id = (int)($_GET['id'] ?? 0);
$site_languages = get_active_languages();

// 404 sayfası: type 'page' + slug '404' sistem kaydı (includes/views/404.php okur; kendi adresinden açılmaz)
if (($_GET['system'] ?? '') === '404') {
    $sid = (int)$pdo->query("SELECT `id` FROM `items` WHERE `type` = 'page' AND `slug` = '404' LIMIT 1")->fetchColumn();
    if (!$sid) {
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("INSERT INTO `items` (`type`, `slug`, `slug_tr`, `slug_en`, `title_tr`, `title_en`, `content_tr`, `content_en`, `status`, `date`, `created_at`, `updated_at`) VALUES ('page', '404', '404', '404', ?, ?, ?, ?, 'published', ?, ?, ?)")
            ->execute(['Kadraj Dışı', 'Out of Frame', not_found_default_html('tr'), not_found_default_html('en'), date('Y-m-d'), $now, $now]);
        $sid = (int)$pdo->lastInsertId();
    }
    header('Location: blog-edit.php?id=' . $sid);
    exit;
}

$is_404 = false;
$post = null;
$post_type = (($_GET['type'] ?? '') === 'page') ? 'page' : 'blog';
if ($id > 0) {
    // 'aboutme' kaydı Hakkımda'nın içeriğidir; about.php'de düzenlenir
    $stmt = $pdo->prepare("SELECT * FROM `items` WHERE `id` = ? AND (`type` = 'blog' OR (`type` = 'page' AND `slug` <> 'aboutme')) LIMIT 1");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($post) $post_type = $post['type'];
    $is_404 = $post && $post['type'] === 'page' && $post['slug'] === '404';
    if (!$post) {
        set_flash('error', __a('Blog yazısı bulunamadı veya silinmiş.', 'Blog post not found or has been deleted.'));
        header('Location: blog.php');
        exit;
    }
}

// ── Form Gönderimi (POST - Kaydet / Güncelle) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', __a('CSRF güvenlik doğrulaması başarısız oldu.', 'CSRF security verification failed.'));
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $status       = in_array($_POST['status'] ?? '', ['published', 'unlisted', 'draft'], true) ? $_POST['status'] : 'draft';
    $date         = !empty($_POST['date']) ? trim($_POST['date']) : date('Y-m-d');
    $year         = date('Y', strtotime($date));
    $cover_image  = trim($_POST['cover_image'] ?? '');
    $cover_focal  = trim($_POST['cover_focal'] ?? '50% 50%');
    if (!preg_match('/^\d{1,3}%\s+\d{1,3}%$/', $cover_focal)) {
        $cover_focal = '50% 50%';
    }
    $weight       = (int)($_POST['weight'] ?? 0);

    // TR & EN Temel Alanlar
    $title_tr       = trim($_POST['title_tr'] ?? '');
    $title_en       = trim($_POST['title_en'] ?? '');
    $description_tr = trim($_POST['description_tr'] ?? '');
    $description_en = trim($_POST['description_en'] ?? '');
    $content_tr     = trim($_POST['content_tr'] ?? '');
    $content_en     = trim($_POST['content_en'] ?? '');
    $location_tr    = trim($_POST['location_tr'] ?? '');
    $location_en    = trim($_POST['location_en'] ?? '');

    // Slug yönetimi
    $slug_tr = slugify($_POST['slug_tr'] ?? '');
    if ($slug_tr === '' && $title_tr !== '') {
        $slug_tr = slugify($title_tr);
    }
    $slug_en = slugify($_POST['slug_en'] ?? '');
    if ($slug_en === '' && $title_en !== '') {
        $slug_en = slugify($title_en);
    }

    if ($is_404) $slug_tr = $slug_en = '404';
    $canonical_slug = $slug_tr ?: ($slug_en ?: ('post-' . time()));
    $location       = $location_tr ?: $location_en;

    $now = date('Y-m-d H:i:s');

    if ($id > 0) {
        // Güncelle
        $stmt = $pdo->prepare("
            UPDATE `items` SET
                `slug` = ?, `slug_tr` = ?, `slug_en` = ?,
                `title_tr` = ?, `title_en` = ?,
                `description_tr` = ?, `description_en` = ?,
                `content_tr` = ?, `content_en` = ?,
                `location` = ?, `location_tr` = ?, `location_en` = ?,
                `cover_image` = ?, `cover_focal` = ?, `date` = ?, `year` = ?,
                `weight` = ?, `status` = ?, `updated_at` = ?
            WHERE `id` = ? AND `type` = ?
        ");
        $stmt->execute([
            $canonical_slug, $slug_tr, $slug_en,
            $title_tr, $title_en,
            $description_tr, $description_en,
            $content_tr, $content_en,
            $location, $location_tr, $location_en,
            $cover_image, $cover_focal, $date, $year,
            $weight, $status, $now,
            $id, $post_type
        ]);
        set_flash('success', $post_type === 'page' ? __a('Sayfa güncellendi.', 'Page updated.') : __a('Blog yazısı başarıyla güncellendi.', 'Blog post updated successfully.'));
    } else {
        // Yeni Ekle
        $stmt = $pdo->prepare("
            INSERT INTO `items` (
                `type`, `slug`, `slug_tr`, `slug_en`,
                `title_tr`, `title_en`,
                `description_tr`, `description_en`,
                `content_tr`, `content_en`,
                `location`, `location_tr`, `location_en`,
                `cover_image`, `cover_focal`, `date`, `year`,
                `weight`, `status`, `created_at`, `updated_at`
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?,
                ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?
            )
        ");
        $stmt->execute([
            $post_type, $canonical_slug, $slug_tr, $slug_en,
            $title_tr, $title_en,
            $description_tr, $description_en,
            $content_tr, $content_en,
            $location, $location_tr, $location_en,
            $cover_image, $cover_focal, $date, $year,
            $weight, $status, $now, $now
        ]);
        $id = (int)$pdo->lastInsertId();
        set_flash('success', $post_type === 'page' ? __a('Sayfa oluşturuldu.', 'Page created.') : __a('Yeni blog yazısı başarıyla oluşturuldu.', 'New blog post created successfully.'));
    }

    // Ekstra Diller (hy, de, fr vb.)
    foreach ($site_languages as $sl) {
        $lc = $sl['code'];
        if (in_array($lc, ['en', 'tr'], true)) continue;
        ensure_language_columns($lc);

        $l_title = trim($_POST['title_' . $lc] ?? '');
        $l_slug  = $is_404 ? '404' : slugify($_POST['slug_' . $lc] ?? '');
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

    // Bağımsız sayfanın şablonu: "about:format_centered" gibi; listede olmayan değer sade metne düşer
    if ($post_type === 'page' && !$is_404) {
        [$pv, $pk] = array_pad(explode(':', (string)($_POST['page_template'] ?? ''), 2), 2, '');
        if (!isset(page_template_groups()[$pv]['choices'][$pk])) $pv = $pk = '';
        set_setting('page_view@item-' . $id, $pv);
        if ($pv !== '') set_setting(PAGE_VIEW_FIELDS[$pv] . '@item-' . $id, $pk);
    }

    // Kaydet her zaman düzenleme sayfasında bırakır (tüm düzenleyicilerde aynı davranış)
    header('Location: blog-edit.php?id=' . $id);
    exit;
}

$is_new = ($id === 0);
$is_page = ($post_type === 'page');
$page_title_val = $is_new
    ? ($is_page ? __a('Yeni sayfa', 'New page') : __a('Yeni Yazı Ekle', 'Add New Article'))
    : (($admin_lang === 'en' && !empty($post['title_en'])) ? $post['title_en'] : ($post['title_tr'] ?: $post['title_en']));

if ($is_404) $page_title_val = __a('404 sayfası', '404 page');
$admin_title     = $is_new ? ($is_page ? __a('Yeni sayfa', 'New page') : __a('Yeni Blog Yazısı Ekle', 'Add New Blog Post')) : (($is_page ? __a('Sayfayı düzenle: ', 'Edit page: ') : __a('Yazıyı Düzenle: ', 'Edit Article: ')) . e($page_title_val));
$breadcrumb_root = __a('Sayfalar', 'Pages');
$breadcrumb_page = $is_new ? ($is_page ? __a('Yeni sayfa', 'New page') : __a('Yeni Blog Yazısı', 'New Blog Post')) : e($page_title_val);

$post_date = $post['date'] ?? date('Y-m-d');
if (!empty($post_date)) {
    $post_date = substr($post_date, 0, 10);
}
$post_status = $post['status'] ?? 'published';
$post_weight = (int)($post['weight'] ?? 0);
$post_cover  = $post['cover_image'] ?? '';
$post_focal  = $post['cover_focal'] ?? '50% 50%';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/image_modal.php';
?>

<style>
/* ── Blog Editör Stüdyo Stilleri ── */
.blog-editor-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1024px) {
    .blog-editor-layout {
        grid-template-columns: 1fr;
    }
}

/* Dil Sekmeleri */
.blog-lang-tabs {
    display: flex;
    gap: 6px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 20px;
    padding-bottom: 0;
    overflow-x: auto;
}
.blog-lang-tab {
    padding: 10px 18px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.blog-lang-tab:hover {
    color: var(--text-main);
}
.blog-lang-tab.active {
    color: var(--text-main);
    border-bottom-color: #0284c7;
}

/* Zengin Editör Araç Çubuğu */
.rt-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    border-bottom: none;
    border-radius: 8px 8px 0 0;
}
.rt-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.15s ease;
    padding: 0;
}
.rt-btn:hover {
    background: var(--bg-surface);
    color: var(--text-main);
    border-color: var(--border-color);
}
.rt-btn.active {
    background: var(--bg-surface);
    color: #0284c7;
    border-color: #0284c7;
}
.rt-sep {
    width: 1px;
    height: 20px;
    background: var(--border-color);
    margin: 0 4px;
}

/* İçerik Düzenleme Alanı */
.blog-rich-editable {
    min-height: 480px;
    padding: 22px;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: 0 0 8px 8px;
    font-size: 16px;
    line-height: 1.8;
    color: var(--text-main);
    outline: none;
    overflow-y: auto;
}
.blog-rich-editable:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}
.blog-rich-editable:empty:before {
    content: attr(data-placeholder);
    color: var(--text-muted);
    opacity: 0.6;
    pointer-events: none;
    display: block;
}
.blog-rich-editable p {
    margin: 0 0 1.25rem 0;
}
.blog-rich-editable h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 2rem 0 1rem 0;
    color: var(--text-main);
}
.blog-rich-editable h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 1.6rem 0 0.8rem 0;
    color: var(--text-main);
}
.blog-rich-editable blockquote {
    border-left: 3px solid #0284c7;
    padding-left: 16px;
    margin: 1.5rem 0;
    font-style: italic;
    color: var(--text-muted);
}
.blog-rich-editable figure {
    margin: 2rem 0;
    text-align: center;
}
.blog-rich-editable figure img {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}
.blog-rich-editable figure figcaption {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 6px;
}
.blog-rich-editable ul,
.blog-rich-editable ol {
    margin: 0 0 1.25rem 24px;
}
.blog-rich-editable hr {
    border: none;
    border-top: 1px solid var(--border-color);
    margin: 2rem 0;
}

/* Editör Alt Bilgi Çubuğu */
.blog-editor-footer-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    font-size: 12px;
    color: var(--text-muted);
    border: 1px solid var(--border-color);
    border-top: none;
    background: var(--bg-body);
    border-radius: 0 0 8px 8px;
    margin-top: -1px;
}

/* Kapak Görseli Önizleme Kartı */
.cover-preview-box {
    width: 100%;
    aspect-ratio: 16 / 10;
    border-radius: 6px;
    border: 1px dashed var(--border-color);
    background: var(--bg-body);
    overflow: hidden;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}
.cover-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.cover-placeholder {
    text-align: center;
    color: var(--text-muted);
    padding: 16px;
}

/* Durum Seçici Pill'ler */
.status-pill-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.status-pill-opt {
    position: relative;
}
.status-pill-opt input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.status-pill-opt label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    background: var(--bg-body);
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}
.status-pill-opt input[type="radio"]:checked + label {
    background: var(--bg-surface);
    color: var(--text-main);
    border-color: #0284c7;
    box-shadow: 0 0 0 1px #0284c7;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.status-dot.dot-published { background: #10b981; }
.status-dot.dot-draft { background: #f59e0b; }

/* Slug Giriş Grubu */
.slug-input-wrap {
    display: flex;
    align-items: stretch;
}
.slug-input-prefix {
    display: inline-flex;
    align-items: center;
    padding: 0 10px;
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    border-right: none;
    border-radius: 6px 0 0 6px;
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--text-muted);
    white-space: nowrap;
}
.slug-input-field {
    border-radius: 0 6px 6px 0 !important;
    font-family: var(--font-mono);
    font-size: 13px;
}
</style>

<form method="post" id="blogPostForm" action="">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <?php editor_bar([
        'back_href'  => $is_page ? url('admin/pages.php' . ($is_new ? '' : '?page=' . rawurlencode('item-' . $id))) : url('admin/blog.php'),
        'back_label' => $is_page ? __a('Sayfalar', 'Pages') : __a('Blog', 'Blog'),
        'status'     => $is_new ? null : $post_status,
        'form'       => 'blogPostForm',
        'preview'    => $is_new ? '' : ($is_404 ? url($admin_lang . '/404/') : item_url($post, $admin_lang)),
        'title'      => $is_new ? ($is_page ? __a('Yeni sayfa', 'New page') : __a('Yeni yazı', 'New article')) : $page_title_val,
        'sub'        => $is_404 ? __a('Bulunamayan bir adres açıldığında gösterilen sayfa. Başlığı ve metni her dilde ayrı yazın; taslağa alırsanız varsayılan metin görünür.', 'Shown when an address is not found. Write the title and text in each language; if you set it to draft, the default text is shown.') : (($is_new && $is_page) ? __a('Başlığı yazın, içeriği aşağıdaki editörde oluşturun. Sayfa kendi adresinde, sade bir metin sayfası olarak görünür.', 'Write a title and build the content in the editor below. The page appears at its own address as a simple text page.') : ($is_new ? __a('Editoryal yazınızı, saha notunuzu ya da haberinizi birden çok dilde yayınlayın.', 'Publish your essay, field note or news in several languages.') : '')),
    ]); ?>

    <?php
    $lb_preview = [];
    if (!$is_new) { foreach (lang_bar_languages() as $l) { $lb_preview[$l['code']] = item_url($post, $l['code']); } }
    lang_bar([
        'scope'   => '#blogPostForm',
        'click'   => '#tabBtn_{lang}',
        'hide'    => '.blog-lang-tabs',
        'preview' => $lb_preview,
    ]);
    ?>
    <!-- ── DİL SEKMELERİ: gizli; ortak dil çubuğu bunları kullanır ── -->
    <div class="blog-lang-tabs">
        <?php foreach ($site_languages as $idx => $sl):
            $flag = $sl['flag'] ?? (($sl['code'] === 'tr') ? '🇹🇷' : (($sl['code'] === 'en') ? '🇬🇧' : (($sl['code'] === 'hy') ? '🇦🇲' : '🌐')));
        ?>
            <button type="button" class="blog-lang-tab <?= $idx === 0 ? 'active' : '' ?>" onclick="switchLangTab('<?= e($sl['code']) ?>')" id="tabBtn_<?= e($sl['code']) ?>">
                <span><?= $flag ?></span>
                <span><?= e($sl['name_native'] ?: strtoupper($sl['code'])) ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- ── ANA İKİ SÜTUNLU DÜZEN ── -->
    <div class="blog-editor-layout">

        <!-- SOL KOLON: DİL BAZLI İÇERİK ALANLARI -->
        <div>
            <?php foreach ($site_languages as $idx => $sl):
                $lc = $sl['code'];
                $is_first = ($idx === 0);

                $f_title = $post['title_' . $lc] ?? ($lc === 'tr' ? ($post['title_tr'] ?? '') : ($post['title_en'] ?? ''));
                $f_slug  = $post['slug_' . $lc] ?? ($lc === 'tr' ? ($post['slug_tr'] ?? '') : ($post['slug_en'] ?? ''));
                $f_desc  = $post['description_' . $lc] ?? ($lc === 'tr' ? ($post['description_tr'] ?? '') : ($post['description_en'] ?? ''));
                $f_loc   = $post['location_' . $lc] ?? ($lc === 'tr' ? ($post['location_tr'] ?? '') : ($post['location_en'] ?? ''));
                $f_cont  = $post['content_' . $lc] ?? ($lc === 'tr' ? ($post['content_tr'] ?? '') : ($post['content_en'] ?? ''));

                // Eğer içerik JSON formatındaysa (eski stüdyodan gelmişse), HTML olarak ayrıştır
                if (!empty($f_cont) && strpos(trim($f_cont), '{') === 0) {
                    $json_data = json_decode(trim($f_cont), true);
                    if (is_array($json_data) && (isset($json_data['intro']) || isset($json_data['blocks']))) {
                        $f_cont = render_structured_blocks($json_data);
                    }
                }
            ?>
            <div class="blog-tab-pane" id="pane_<?= e($lc) ?>" style="<?= $is_first ? '' : 'display:none;' ?>">
                
                <!-- Başlık ve Slug Kartı -->
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-body">
                        <!-- Başlık -->
                        <div class="form-group" style="margin-bottom:16px;">
                            <label class="form-label" style="font-size:13px; font-weight:700;">
                                <?= __a('Yazı Başlığı', 'Article Title') ?>
                                <span style="color:var(--danger-text);">*</span>
                            </label>
                            <input type="text"
                                   name="title_<?= e($lc) ?>"
                                   id="input_title_<?= e($lc) ?>"
                                   class="form-control"
                                   style="font-size:16px; font-weight:600; padding:12px 14px;"
                                   value="<?= e($f_title) ?>"
                                   placeholder="<?= $lc === 'tr' ? 'Yazının etkileyici başlığını girin...' : 'Enter article title...' ?>"
                                   oninput="handleTitleInput('<?= e($lc) ?>', this.value)"
                                   <?= $is_first ? 'required' : '' ?>>
                        </div>

                        <!-- Slug / Kalıcı Bağlantı -->
                        <div class="form-group" style="margin-bottom:16px;"<?= $is_404 ? ' hidden' : '' ?>>
                            <label class="form-label" style="font-size:12px; font-weight:600;">
                                <?= __a('Sayfa adresi', 'Page address') ?>
                            </label>
                            <div class="slug-input-wrap">
                                <span class="slug-input-prefix"><?= e(BASE_URL) ?>/<?= e($lc) ?>/</span>
                                <input type="text"
                                       name="slug_<?= e($lc) ?>"
                                       id="input_slug_<?= e($lc) ?>"
                                       class="form-control slug-input-field"
                                       value="<?= e($f_slug) ?>"
                                       placeholder="yazi-basligi"
                                       oninput="this.dataset.userEdited = '1'">
                            </div>
                            <small class="form-hint" style="font-size:12px; color:var(--text-muted); margin-top:4px; display:block;">
                                <?= __a('Boş bırakılırsa başlığa göre otomatik üretilir. Sadece küçük harf, rakam ve tire kullanın.', 'Auto-generated from title if left empty. Use lowercase letters, numbers, and dashes.') ?>
                            </small>
                        </div>

                        <!-- Kısa Özet (Excerpt) -->
                        <div class="form-group" style="margin-bottom:16px;"<?= $is_404 ? ' hidden' : '' ?>>
                            <label class="form-label" style="font-size:12px; font-weight:600;">
                                <?= __a('Kısa Özet & Giriş Notu (Excerpt)', 'Short Excerpt & Lead Note') ?>
                            </label>
                            <textarea name="description_<?= e($lc) ?>"
                                      id="input_desc_<?= e($lc) ?>"
                                      class="form-control"
                                      rows="2"
                                      style="font-size:13px; line-height:1.6;"
                                      placeholder="<?= $lc === 'tr' ? 'Blog listelerinde, arama motorlarında ve sosyal paylaşımlarda görünecek kısa giriş özeti...' : 'Short summary for blog index, search engines, and social cards...' ?>"><?= e($f_desc) ?></textarea>
                        </div>

                        <!-- Kategori / Etiket / Konum -->
                        <div class="form-group" style="margin-bottom:0;"<?= $is_page ? ' hidden' : '' ?>>
                            <label class="form-label" style="font-size:12px; font-weight:600;">
                                <?= __a('Kategori / Konu Etiketi veya Saha Lokasyonu', 'Category / Topic Tag or Field Location') ?>
                            </label>
                            <input type="text"
                                   name="location_<?= e($lc) ?>"
                                   id="input_loc_<?= e($lc) ?>"
                                   class="form-control"
                                   value="<?= e($f_loc) ?>"
                                   placeholder="<?= $lc === 'tr' ? 'Örn: Saha Günlüğü, Belgesel & Röportaj, İstanbul...' : 'e.g. Field Journal, Documentary & Interview, Istanbul...' ?>">
                            <small class="form-hint" style="font-size:12px; color:var(--text-muted); margin-top:4px; display:block;">
                                <?= __a('Yazı kartının üzerinde ve başlığın yanında küçük etiket rozeti olarak görünür.', 'Displayed as a tag badge above the card and alongside article title.') ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Zengin Yazı Editörü Kartı -->
                <div class="card">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <h3 class="card-title" style="font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            <span><?= __a('Yazı Metni & Editoryal İçerik', 'Article Body & Editorial Content') ?></span>
                        </h3>
                        <span style="font-size:12px; color:var(--text-muted);"><?= strtoupper($lc) ?></span>
                    </div>
                    <div class="card-body" style="padding:0;">

                        <!-- Araç Çubuğu (Toolbar) -->
                        <div class="rt-toolbar" id="toolbar_<?= e($lc) ?>">
                            <!-- Kalın -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execCmd('<?= e($lc) ?>', 'bold')" title="<?= __a('Kalın (Ctrl+B)', 'Bold') ?>">
                                <strong>B</strong>
                            </button>
                            <!-- İtalik -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execCmd('<?= e($lc) ?>', 'italic')" title="<?= __a('İtalik (Ctrl+I)', 'Italic') ?>">
                                <em>I</em>
                            </button>
                            <!-- Altı Çizili -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execCmd('<?= e($lc) ?>', 'underline')" title="<?= __a('Altı Çizili', 'Underline') ?>">
                                <u>U</u>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Başlık 2 (H2) -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execFormat('<?= e($lc) ?>', 'h2')" title="<?= __a('Bölüm Başlığı (H2)', 'Heading 2') ?>" style="font-size:12px; font-weight:700;">
                                H2
                            </button>
                            <!-- Başlık 3 (H3) -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execFormat('<?= e($lc) ?>', 'h3')" title="<?= __a('Alt Başlık (H3)', 'Heading 3') ?>" style="font-size:12px; font-weight:700;">
                                H3
                            </button>
                            <!-- Paragraf (Normal Metin) -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execFormat('<?= e($lc) ?>', 'p')" title="<?= __a('Normal Paragraf', 'Paragraph') ?>" style="font-size:12px;">
                                ¶
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Alıntı (Blockquote) -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execFormat('<?= e($lc) ?>', 'blockquote')" title="<?= __a('Blok Alıntı', 'Blockquote') ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg>
                            </button>

                            <!-- Madde İşaretli Liste -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execCmd('<?= e($lc) ?>', 'insertUnorderedList')" title="<?= __a('Madde İşaretli Liste', 'Bullet List') ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </button>
                            <!-- Numaralı Liste -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execCmd('<?= e($lc) ?>', 'insertOrderedList')" title="<?= __a('Numaralı Liste', 'Numbered List') ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
                            </button>
                            <!-- Yatay Çizgi -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execCmd('<?= e($lc) ?>', 'insertHorizontalRule')" title="<?= __a('Yatay Ayraç Çizgi', 'Horizontal Rule') ?>">
                                ―
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Bağlantı / Link -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="insertLink('<?= e($lc) ?>')" title="<?= __a('Bağlantı / Link Ekle', 'Insert Link') ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            </button>
                            <!-- Bağlantıyı Kaldır -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execCmd('<?= e($lc) ?>', 'unlink')" title="<?= __a('Bağlantıyı Kaldır', 'Remove Link') ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.16 11.75-1.72 1.71a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Görsel Ekle (Medya Kütüphanesinden) -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="insertBlogImage('<?= e($lc) ?>')" title="<?= __a('Medya Kütüphanesinden Fotoğraf Ekle', 'Insert Image from Media Library') ?>" style="display:inline-flex; align-items:center; gap:5px; width:auto; padding:0 8px; color:#0284c7; font-weight:600;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <span><?= __a('Görsel Ekle', 'Add Photo') ?></span>
                            </button>

                            <!-- YouTube Video Ekle -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="insertYoutubeVideo('<?= e($lc) ?>')" title="<?= __a('YouTube Video Ekle', 'Insert YouTube Video') ?>" style="display:inline-flex; align-items:center; gap:5px; width:auto; padding:0 8px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><polygon points="10 15 15 12 10 9 10 15"/></svg>
                                <span><?= __a('Video', 'Video') ?></span>
                            </button>

                            <div class="rt-sep"></div>

                            <!-- Biçimlendirmeyi Temizle -->
                            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execCmd('<?= e($lc) ?>', 'removeFormat')" title="<?= __a('Biçimlendirmeyi Temizle', 'Clear Formatting') ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/></svg>
                            </button>

                            <!-- HTML Kaynak Kodu Modu -->
                            <button type="button" class="rt-btn" onclick="toggleHtmlSource('<?= e($lc) ?>')" id="htmlToggleBtn_<?= e($lc) ?>" style="margin-left:auto; width:auto; padding:0 10px; font-size:12px; display:inline-flex; align-items:center; gap:5px;" title="<?= __a('HTML Kaynak Kodu Görünümü', 'HTML Source View') ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                <span id="htmlToggleLabel_<?= e($lc) ?>"><?= __a('Kaynak Kodu', 'HTML Source') ?></span>
                            </button>
                        </div>

                        <!-- Görsel Zengin Editör Alanı -->
                        <div class="blog-rich-editable"
                             id="editor_<?= e($lc) ?>"
                             contenteditable="true"
                             data-placeholder="<?= $lc === 'tr' ? 'Makalenizi, editoryal düşüncelerinizi, saha notlarınızı ve fotoğraflarınızı buraya yazmaya başlayın...' : 'Start writing your editorial article, field notes, and visual stories here...' ?>"
                             oninput="handleEditorChange('<?= e($lc) ?>')"><!--noi18n--><?= $f_cont ?><!--/noi18n--></div>

                        <!-- İsteğe Bağlı HTML Kaynak Alanı (Varsayılan olarak gizli) -->
                        <textarea name="content_<?= e($lc) ?>"
                                  id="raw_textarea_<?= e($lc) ?>"
                                  class="form-control"
                                  style="display:none; min-height:480px; font-family:var(--font-mono); font-size:12px; line-height:1.6; border-radius:0 0 8px 8px; border:1px solid var(--border-color); border-top:none;"
                                  oninput="handleRawChange('<?= e($lc) ?>')"><?= e($f_cont) ?></textarea>

                        <!-- Alt Bilgi Çubuğu (Kelime Sayısı & Tahmini Okuma Süresi) -->
                        <div class="blog-editor-footer-bar">
                            <span id="stats_<?= e($lc) ?>">0 kelime • ~1 dk okuma</span>
                            <span style="font-size:12px; color:var(--text-muted);"><?= __a('Görseller ve videolar direkt metin içine gömülebilir', 'Photos and videos can be embedded directly in content') ?></span>
                        </div>

                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <!-- SAĞ KOLON: YAYINLAMA & KAPAK AYARLARI -->
        <div>
            <!-- Yayın Ayarları Kartı -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h3 class="card-title" style="font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                        <?= __a('Yayın Durumu & Tarih', 'Publishing & Date') ?>
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Durum (Published / Draft) -->
                    <div class="form-group" style="margin-bottom:16px;">
                        <label class="form-label" style="font-size:12px; font-weight:600;"><?= __a('Görünürlük Durumu', 'Visibility Status') ?></label>
                        <div class="status-pill-group">
                            <div class="status-pill-opt">
                                <input type="radio" name="status" id="status_published" value="published" <?= $post_status === 'published' ? 'checked' : '' ?>>
                                <label for="status_published">
                                    <span class="status-dot dot-published"></span>
                                    <span><?= __a('Yayında', 'Published') ?></span>
                                </label>
                            </div>
                            <div class="status-pill-opt">
                                <input type="radio" name="status" id="status_unlisted" value="unlisted" <?= $post_status === 'unlisted' ? 'checked' : '' ?>>
                                <label for="status_unlisted">
                                    <span class="status-dot" style="background:#f59e0b;"></span>
                                    <span><?= __a('Gizli Link', 'Unlisted') ?></span>
                                </label>
                            </div>
                            <div class="status-pill-opt">
                                <input type="radio" name="status" id="status_draft" value="draft" <?= $post_status === 'draft' ? 'checked' : '' ?>>
                                <label for="status_draft">
                                    <span class="status-dot dot-draft"></span>
                                    <span><?= __a('Taslak', 'Draft') ?></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Yayın Tarihi -->
                    <div class="form-group" style="margin-bottom:16px;"<?= $is_page ? ' hidden' : '' ?>>
                        <label class="form-label" style="font-size:12px; font-weight:600;"><?= __a('Yayın Tarihi', 'Publish Date') ?></label>
                        <input type="date" name="date" class="form-control" value="<?= e($post_date) ?>" required>
                    </div>

                    <!-- Sıralama Ağırlığı -->
                    <div class="form-group" style="margin-bottom:20px;"<?= $is_page ? ' hidden' : '' ?>>
                        <label class="form-label" style="font-size:12px; font-weight:600;"><?= __a('Sıra', 'Order') ?></label>
                        <input type="number" name="weight" class="form-control" value="<?= e($post_weight) ?>" placeholder="0">
                        <small class="form-hint" style="font-size:12px; color:var(--text-muted); margin-top:4px; display:block;">
                            <?= __a('Küçük sayılar en önce listelenir. Varsayılan: 0', 'Lower numbers appear first. Default: 0') ?>
                        </small>
                    </div>

                </div>
            </div>

            <?php if ($is_page && !$is_404):
                $al = ($admin_lang ?? 'tr') === 'tr' ? 'tr' : 'en';
                $cur_pv = $id ? get_setting('page_view@item-' . $id, '') : '';
                $cur_tpl = $cur_pv !== '' ? $cur_pv . ':' . get_setting(PAGE_VIEW_FIELDS[$cur_pv] . '@item-' . $id, '') : '';
            ?>
            <!-- Sayfa Şablonu Kartı -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h3 class="card-title" style="font-size:14px;"><?= __a('Sayfa şablonu', 'Page template') ?></h3></div>
                <div class="card-body">
                    <select name="page_template" class="form-control" aria-label="<?= e(__a('Sayfa şablonu', 'Page template')) ?>">
                        <option value=""><?= __a('Sade metin sayfası', 'Simple text page') ?></option>
                        <?php foreach (page_template_groups() as $pv => $grp): ?>
                        <optgroup label="<?= e($grp[$al]) ?>">
                            <?php foreach ($grp['choices'] as $ck => $cl): ?>
                            <option value="<?= e($pv . ':' . $ck) ?>"<?= $cur_tpl === $pv . ':' . $ck ? ' selected' : '' ?>><?= e($cl[$al]) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint" style="font-size:12px; color:var(--text-muted); margin-top:6px; display:block;">
                        <?= __a('Temadaki Hakkımda, İletişim veya proje sayfası düzenlerinden birini seçin. Kapak görseli portre ya da sayfa fotoğrafı olarak kullanılır; renk ve diğer ayarlar Temalar › Özelleştir\'deki o sayfanınkini izler.', 'Pick one of the theme\'s About, Contact or project page layouts. The cover image is used as the portrait or page photo; colours and other options follow that page in Themes › Customize.') ?>
                    </small>
                </div>
            </div>
            <?php endif; ?>

            <!-- Kapak Görseli Kartı -->
            <div class="card" style="margin-bottom:20px;"<?= $is_404 ? ' hidden' : '' ?>>
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 class="card-title" style="font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                        <?= __a('Öne Çıkan Kapak Fotoğrafı', 'Featured Cover Photo') ?>
                    </h3>
                    <?php if (!empty($post_cover)): ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeCoverImage()" style="font-size:12px; padding:2px 8px; color:var(--danger-text);" id="btnRemoveCover">
                            <?= __a('Kaldır', 'Remove') ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeCoverImage()" style="font-size:12px; padding:2px 8px; color:var(--danger-text); display:none;" id="btnRemoveCover">
                            <?= __a('Kaldır', 'Remove') ?>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <input type="hidden" name="cover_image" id="input_cover_image" value="<?= e($post_cover) ?>">
                    <input type="hidden" name="cover_focal" id="input_cover_focal" value="<?= e($post_focal) ?>">
                    
                    <?php
                        $focal_parts = explode(' ', $post_focal);
                        $focal_x = $focal_parts[0] ?? '50%';
                        $focal_y = $focal_parts[1] ?? '50%';
                    ?>
                    <div class="cover-preview-box" id="coverPreviewBox" onclick="handleCoverBoxClick(event)" style="position:relative; cursor:crosshair; overflow:hidden;" title="<?= __a('Kapak Görselini Değiştir veya Odak Noktası Belirle', 'Change Cover Photo or Set Focal Point') ?>">
                        <?php if (!empty($post_cover)): ?>
                            <img src="<?= e(url(ltrim($post_cover, '/'))) ?>" alt="Kapak" id="coverPreviewImg" class="cover-preview-img" style="object-position:<?= e($post_focal) ?>;">
                            <div id="cover_focal_pin" class="focal-crosshair-pin" style="position:absolute; left:<?= e($focal_x) ?>; top:<?= e($focal_y) ?>; width:20px; height:20px; border-radius:50%; border:2px solid #fff; background:rgba(37,99,235,0.85); box-shadow:0 0 0 2px rgba(0,0,0,0.45); transform:translate(-50%, -50%); pointer-events:none; z-index:4;"></div>
                            <div class="cover-placeholder" id="coverPlaceholder" style="display:none;">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="margin-bottom:6px;"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <div style="font-size:12px; font-weight:600;"><?= __a('Kapak Fotoğrafı Seç', 'Select Cover Photo') ?></div>
                                <div style="font-size:12px; opacity:0.8;"><?= __a('Medya Kütüphanesinden', 'From Media Library') ?></div>
                            </div>
                        <?php else: ?>
                            <img src="" alt="Kapak" id="coverPreviewImg" class="cover-preview-img" style="display:none;">
                            <div id="cover_focal_pin" class="focal-crosshair-pin" style="position:absolute; left:50%; top:50%; width:20px; height:20px; border-radius:50%; border:2px solid #fff; background:rgba(37,99,235,0.85); box-shadow:0 0 0 2px rgba(0,0,0,0.45); transform:translate(-50%, -50%); pointer-events:none; z-index:4; display:none;"></div>
                            <div class="cover-placeholder" id="coverPlaceholder">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="margin-bottom:6px;"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <div style="font-size:12px; font-weight:600;"><?= __a('Kapak Fotoğrafı Seç', 'Select Cover Photo') ?></div>
                                <div style="font-size:12px; opacity:0.8;"><?= __a('Medya Kütüphanesinden', 'From Media Library') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:5px; margin-bottom:10px; font-size:12px; color:var(--text-muted);">
                        <span><?= __a('Odak noktası (resme tıklayın):', 'Focal point (click the image):') ?> <strong id="cover_focal_label" style="font-family:monospace;"><?= e($post_focal) ?></strong></span>
                        <button type="button" class="btn btn-sm" onclick="resetCoverFocal(event)" style="font-size:12px; padding:1px 6px; background:transparent; border:1px solid var(--border-color); border-radius:6px; cursor:pointer; color:var(--text-muted);">
                            <?= __a('Sıfırla', 'Reset') ?>
                        </button>
                    </div>

                    <button type="button" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center;" onclick="chooseCoverImage()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        <span><?= __a('Medya Kütüphanesini Aç', 'Open Media Library') ?></span>
                    </button>
                    <small class="form-hint" style="font-size:12px; color:var(--text-muted); margin-top:8px; display:block;">
                        <?= __a('Blog listelerinde ve yazı manşetinde öne çıkan ana görseldir.', 'Main visual displayed on article lists and header.') ?>
                    </small>
                </div>
            </div>

            <?php if (!$is_new && !$is_404): ?>
                <!-- Silme Butonu Kartı -->
                <div class="card" style="border-color:rgba(239, 68, 68, 0.25);">
                    <div class="card-body" style="padding:16px;">
                        <a href="<?= $is_page ? 'item-delete.php?id=' . (int)$post['id'] . '&amp;return=pages&amp;csrf_token=' . urlencode(csrf_token()) : 'blog.php?delete=' . (int)$post['id'] . '&amp;csrf_token=' . urlencode(csrf_token()) ?>"
                           data-confirm="<?= e($is_page ? __a('Bu sayfa kalıcı olarak silinecek. Bu işlem geri alınamaz.', 'This page will be deleted permanently. This cannot be undone.') : __a('Bu yazı kalıcı olarak silinecek. Bu işlem geri alınamaz.', 'This article will be deleted permanently. This cannot be undone.')) ?>"
                           class="btn btn-danger btn-sm"
                           style="width:100%; justify-content:center; color:var(--danger-text); background:transparent; border-color:rgba(239, 68, 68, 0.4);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            <span><?= $is_page ? __a('Bu sayfayı sil', 'Delete this page') : __a('Bu Yazıyı Sil', 'Delete This Article') ?></span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>
</form>

<script>
// ── DİL SEKMELERİ GEÇİŞİ ──
var currentActiveLang = '<?= e($site_languages[0]['code']) ?>';
var activeLanguages = <?= json_encode(array_column($site_languages, 'code')) ?>;
var htmlMode = {};

function switchLangTab(lang) {
    currentActiveLang = lang;
    document.querySelectorAll('.blog-lang-tab').forEach(function(b) {
        b.classList.remove('active');
    });
    var btn = document.getElementById('tabBtn_' + lang);
    if (btn) btn.classList.add('active');

    document.querySelectorAll('.blog-tab-pane').forEach(function(p) {
        p.style.display = 'none';
    });
    var targetPane = document.getElementById('pane_' + lang);
    if (targetPane) targetPane.style.display = 'block';
}

// ── OTOMATİK SLUG OLUŞTURMA ──
function slugifyText(text) {
    if (window.erSlugify) return window.erSlugify(text); // ortak harf tablosu (Ermenice, Kiril, Yunan…)
    var trMap = {
        'ç':'c', 'Ç':'c', 'ğ':'g', 'Ğ':'g', 'ı':'i', 'İ':'i',
        'ö':'o', 'Ö':'o', 'ş':'s', 'Ş':'s', 'ü':'u', 'Ü':'u'
    };
    return text.split('').map(function(c) {
        return trMap[c] || c;
    }).join('')
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/[\s-]+/g, '-');
}

function handleTitleInput(lang, val) {
    var slugInput = document.getElementById('input_slug_' + lang);
    if (!slugInput) return;
    // Eğer kullanıcı slug'ı kendisi elle yazmadıysa başlıktan türet
    if (!slugInput.dataset.userEdited || slugInput.value.trim() === '') {
        slugInput.value = slugifyText(val);
    }
}

// ── ZENGİN EDİTÖR KOMUTLARI ──
function execCmd(lang, cmd, arg) {
    if (htmlMode[lang]) return;
    var el = document.getElementById('editor_' + lang);
    if (el) el.focus();
    document.execCommand(cmd, false, arg || null);
    handleEditorChange(lang);
}

function execFormat(lang, tag) {
    if (htmlMode[lang]) return;
    var el = document.getElementById('editor_' + lang);
    if (el) el.focus();
    document.execCommand('formatBlock', false, '<' + tag + '>');
    handleEditorChange(lang);
}

function insertLink(lang) {
    if (htmlMode[lang]) return;
    var el = document.getElementById('editor_' + lang);
    if (el) el.focus();
    var isEn = (window.ADMIN_LANG === 'en');
    adminPromptLink().then(function (url) {
        if (!url) return;
        document.execCommand('createLink', false, url);
        handleEditorChange(lang);
    });
}

function insertBlogImage(lang) {
    if (htmlMode[lang]) return;
    var el = document.getElementById('editor_' + lang);
    if (!el) return;

    openMediaModalForCallback(function(filePath, altText) {
        el.focus();
        var isEn = (window.ADMIN_LANG === 'en');
        var cleanPath = filePath.replace(/^\/+/, '');
        var fullUrl = '<?= e($base_path) ?>/' + cleanPath;
        var alt = altText || (isEn ? 'Photo' : 'Fotoğraf');

        // Şık, duyarlı ve monografi uyumlu editoryal görsel figürü
        var figHtml = '<figure class="blog-figure" style="margin:2.5rem 0; text-align:center;">' +
                      '<img src="' + escapeHtml(fullUrl) + '" alt="' + escapeHtml(alt) + '" style="max-width:100%; height:auto; border-radius:6px; display:block; margin:0 auto;" />' +
                      '<figcaption style="font-size:12px; color:#888; margin-top:8px;">' + escapeHtml(alt) + '</figcaption>' +
                      '</figure><p><br></p>';

        document.execCommand('insertHTML', false, figHtml);
        handleEditorChange(lang);
    });
}

function insertYoutubeVideo(lang) {
    if (htmlMode[lang]) return;
    var el = document.getElementById('editor_' + lang);
    if (!el) return;
    var isEn = (window.ADMIN_LANG === 'en');
    var sel = window.getSelection(), range = sel && sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
    adminPrompt(isEn ? 'YouTube link or video ID' : 'YouTube bağlantısı ya da video kimliği', '').then(function (raw) {
        if (!raw) return;
        el.focus();
        if (range) { sel.removeAllRanges(); sel.addRange(range); }
        insertBlogVideoNow(el, lang, raw);
    });
}

function insertBlogVideoNow(el, lang, raw) {
    var match = raw.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
    var videoId = match ? match[1] : raw.trim();
    if (!videoId) return;

    var videoHtml = '<div class="video-container" style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; max-width:100%; margin:2.5rem 0; border-radius:6px; background:#000;">' +
                    '<iframe src="https://www.youtube-nocookie.com/embed/' + escapeHtml(videoId) + '" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allowfullscreen loading="lazy"></iframe>' +
                    '</div><p><br></p>';

    el.focus();
    document.execCommand('insertHTML', false, videoHtml);
    handleEditorChange(lang);
}

function toggleHtmlSource(lang) {
    var editor = document.getElementById('editor_' + lang);
    var rawArea = document.getElementById('raw_textarea_' + lang);
    var btnLabel = document.getElementById('htmlToggleLabel_' + lang);
    var btn = document.getElementById('htmlToggleBtn_' + lang);
    var isEn = (window.ADMIN_LANG === 'en');

    if (!htmlMode[lang]) {
        // Zengin Mod -> HTML Modu
        rawArea.value = editor.innerHTML.trim();
        editor.style.display = 'none';
        rawArea.style.display = 'block';
        rawArea.focus();
        btnLabel.textContent = isEn ? 'Visual Editor' : 'Görsel Editör';
        btn.classList.add('active');
        htmlMode[lang] = true;
    } else {
        // HTML Modu -> Zengin Mod
        editor.innerHTML = rawArea.value;
        rawArea.style.display = 'none';
        editor.style.display = 'block';
        editor.focus();
        btnLabel.textContent = isEn ? 'HTML Source' : 'Kaynak Kodu';
        btn.classList.remove('active');
        htmlMode[lang] = false;
        updateWordStats(lang);
    }
}

// ── İÇERİK SENKRONİZASYONU & İSTATİSTİKLER ──
function handleEditorChange(lang) {
    var editor = document.getElementById('editor_' + lang);
    var rawArea = document.getElementById('raw_textarea_' + lang);
    if (editor && rawArea) {
        rawArea.value = editor.innerHTML;
    }
    updateWordStats(lang);
}

function handleRawChange(lang) {
    var editor = document.getElementById('editor_' + lang);
    var rawArea = document.getElementById('raw_textarea_' + lang);
    if (editor && rawArea) {
        editor.innerHTML = rawArea.value;
    }
    updateWordStats(lang);
}

function updateWordStats(lang) {
    var editor = document.getElementById('editor_' + lang);
    var statsEl = document.getElementById('stats_' + lang);
    if (!editor || !statsEl) return;

    var text = editor.innerText || editor.textContent || '';
    var clean = text.trim();
    var words = clean ? clean.split(/\s+/).length : 0;
    var readMins = Math.max(1, Math.ceil(words / 180));
    var isEn = (window.ADMIN_LANG === 'en');

    if (isEn) {
        statsEl.textContent = words + ' words • ~' + readMins + ' min read';
    } else {
        statsEl.textContent = words + ' kelime • ~' + readMins + ' dk okuma';
    }
}

function handleCoverBoxClick(e) {
    var img = document.getElementById('coverPreviewImg');
    if (!img || img.style.display === 'none' || !img.src) {
        chooseCoverImage();
        return;
    }
    var box = document.getElementById('coverPreviewBox');
    var rect = box.getBoundingClientRect();
    var x = Math.round(Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100)));
    var y = Math.round(Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100)));
    var focalVal = x + '% ' + y + '%';
    
    var inp = document.getElementById('input_cover_focal');
    if (inp) inp.value = focalVal;
    var pin = document.getElementById('cover_focal_pin');
    if (pin) {
        pin.style.display = 'block';
        pin.style.left = x + '%';
        pin.style.top = y + '%';
    }
    var lbl = document.getElementById('cover_focal_label');
    if (lbl) lbl.innerText = focalVal;
    img.style.objectPosition = focalVal;
}

function resetCoverFocal(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
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
    var img = document.getElementById('coverPreviewImg');
    if (img) img.style.objectPosition = focalVal;
}

// ── KAPAK FOTOĞRAFI YÖNETİMİ ──
function chooseCoverImage() {
    openMediaModalForCallback(function(filePath, altText) {
        document.getElementById('input_cover_image').value = filePath;
        var cleanPath = filePath.replace(/^\/+/, '');
        var fullUrl = '<?= e($base_path) ?>/' + cleanPath;
        var img = document.getElementById('coverPreviewImg');
        var ph = document.getElementById('coverPlaceholder');
        var removeBtn = document.getElementById('btnRemoveCover');

        img.src = fullUrl;
        img.style.display = 'block';
        if (ph) ph.style.display = 'none';
        if (removeBtn) removeBtn.style.display = 'inline-flex';
    });
}

function removeCoverImage() {
    document.getElementById('input_cover_image').value = '';
    var img = document.getElementById('coverPreviewImg');
    var ph = document.getElementById('coverPlaceholder');
    var removeBtn = document.getElementById('btnRemoveCover');

    img.src = '';
    img.style.display = 'none';
    if (ph) ph.style.display = 'block';
    if (removeBtn) removeBtn.style.display = 'none';
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

// Zorunlu alan gizli bir dil sekmesindeyse tarayıcı formu sessizce durdurur: o sekmeyi aç
document.getElementById('blogPostForm').addEventListener('invalid', function(e) {
    var pane = e.target.closest('.blog-tab-pane');
    if (pane && pane.style.display === 'none') switchLangTab(pane.id.replace('pane_', ''));
}, true);

// Form gönderilirken tüm dillerdeki zengin editör içeriğini textarea'lara aktar
document.getElementById('blogPostForm').addEventListener('submit', function() {
    activeLanguages.forEach(function(lc) {
        if (!htmlMode[lc]) {
            var editor = document.getElementById('editor_' + lc);
            var rawArea = document.getElementById('raw_textarea_' + lc);
            if (editor && rawArea) {
                rawArea.value = editor.innerHTML;
            }
        }
    });
});

// Sayfa yüklendiğinde istatistikleri hesapla
document.addEventListener('DOMContentLoaded', function() {
    activeLanguages.forEach(function(lc) {
        updateWordStats(lc);
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
