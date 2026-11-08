<?php
/**
 * admin/page-names.php
 * Tüm Sayfaların (Portfolyo Genel Başlığı & Kategorileri, Blog, Hakkımda, İletişim, Saha Rotaları, Yasal Sayfalar)
 * İsimlerini ve Kalıcı Bağlantılarını (URL Slug) Özelleştirme Yöneticisi
 * Üstten sekmeli çok dilli arayüz ve otomatik menü senkronizasyonu
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$site_languages = get_active_languages();

// Kategorileri oku
$all_categories = [];
try {
    $all_categories = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

$static_pages_meta = [
    'home' => [
        'icon'         => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>',
        'label_tr'     => 'Ana Sayfa (Seçki & Karşılama)',
        'label_en'     => 'Homepage (Showcase & Hero)',
        'def_title_tr' => 'Ana Sayfa',
        'def_title_en' => 'Home',
        'def_slug_tr'  => '',
        'def_slug_en'  => '',
        'is_home'      => true,
    ],
    'blog' => [
        'icon'         => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
        'label_tr'     => 'Blog / Yazılar / Haberler',
        'label_en'     => 'Blog / Articles / News',
        'def_title_tr' => 'Blog',
        'def_title_en' => 'Blog',
        'def_slug_tr'  => 'blog',
        'def_slug_en'  => 'blog',
    ],
    'about' => [
        'icon'         => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>',
        'label_tr'     => 'Hakkımda Sayfası',
        'label_en'     => 'About Page',
        'def_title_tr' => 'Hakkımda',
        'def_title_en' => 'About Me',
        'def_slug_tr'  => 'hakkimda',
        'def_slug_en'  => 'about',
    ],
    'contact' => [
        'icon'         => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'label_tr'     => 'İletişim Sayfası',
        'label_en'     => 'Contact Page',
        'def_title_tr' => 'İletişim',
        'def_title_en' => 'Contact',
        'def_slug_tr'  => 'iletisim',
        'def_slug_en'  => 'contact',
    ],
    'privacy_notice' => [
        'icon'         => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 12h6"/><path d="M9 16h6"/><path d="M9 8h6"/></svg>',
        'label_tr'     => 'KVKK Aydınlatma Metni',
        'label_en'     => 'Privacy Notice',
        'def_title_tr' => 'Aydınlatma Metni',
        'def_title_en' => 'Privacy Notice',
        'def_slug_tr'  => 'aydinlatma-metni',
        'def_slug_en'  => 'privacy-notice',
    ],
    'explicit_consent' => [
        'icon'         => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'label_tr'     => 'Açık Rıza Metni',
        'label_en'     => 'Explicit Consent',
        'def_title_tr' => 'Açık Rıza Metni',
        'def_title_en' => 'Explicit Consent',
        'def_slug_tr'  => 'acik-riza-metni',
        'def_slug_en'  => 'explicit-consent',
    ],
];
if (field_map_enabled()) {
    $static_pages_meta['field_work'] = [
        'icon'         => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 7 8 11.7z"/></svg>',
        'label_tr'     => 'Saha Rotaları (Harita)',
        'label_en'     => 'Field Routes (Map)',
        'def_title_tr' => 'Saha Rotaları',
        'def_title_en' => 'Field Routes',
        'def_slug_tr'  => 'saha-rotalari',
        'def_slug_en'  => 'field-work',
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', __a('CSRF güvenlik doğrulaması başarısız oldu.', 'CSRF security verification failed.'));
        header('Location: page-names.php');
        exit;
    }

    // 0. Portfolyo Genel Başlığını Güncelle
    foreach ($site_languages as $l_row) {
        $lc = $l_row['code'];
        $def_port = ($lc === 'tr') ? 'Portfolyo' : 'Portfolio';
        $p_val = trim($_POST['portfolio_title_' . $lc] ?? '');
        set_setting('portfolio_title_' . $lc, $p_val !== '' ? $p_val : $def_port);
    }

    // 1. Portfolyo Kategorilerini (categories tablosu + menus tablosu + settings) Güncelle
    foreach ($all_categories as $cat) {
        $cid = (int)$cat['id'];
        $ckey = $cat['key_slug'];
        $old_slug_tr = $cat['slug_tr'];
        $old_slug_en = $cat['slug_en'];

        $name_tr = trim($_POST['cat_name_tr_' . $cid] ?? $cat['name_tr']);
        $name_en = trim($_POST['cat_name_en_' . $cid] ?? $cat['name_en']);
        $slug_tr = slugify(trim($_POST['cat_slug_tr_' . $cid] ?? $cat['slug_tr']));
        $slug_en = slugify(trim($_POST['cat_slug_en_' . $cid] ?? $cat['slug_en']));

        if ($name_tr === '') $name_tr = $cat['name_tr'];
        if ($name_en === '') $name_en = $cat['name_en'];
        if ($slug_tr === '') $slug_tr = $cat['slug_tr'];
        if ($slug_en === '') $slug_en = $cat['slug_en'];

        try {
            $st = $pdo->prepare("UPDATE `categories` SET `name_tr` = ?, `name_en` = ?, `slug_tr` = ?, `slug_en` = ? WHERE `id` = ?");
            $st->execute([$name_tr, $name_en, $slug_tr, $slug_en, $cid]);
        } catch (\Throwable $e) {}

        // Ek diller
        foreach ($site_languages as $l_row) {
            $lc = $l_row['code'];
            $l_name = trim($_POST['cat_name_' . $lc . '_' . $cid] ?? '');
            $l_slug = slugify(trim($_POST['cat_slug_' . $lc . '_' . $cid] ?? ''));
            if ($l_name !== '') set_setting($ckey . '_title_' . $lc, $l_name);
            if ($l_slug !== '') set_setting('static_slug_' . $ckey . '_' . $lc, $l_slug);
            if (!in_array($lc, ['tr', 'en'], true)) {
                try {
                    $st_l = $pdo->prepare("UPDATE `categories` SET `name_{$lc}` = ?, `slug_{$lc}` = ? WHERE `id` = ?");
                    $st_l->execute([$l_name ?: $name_en, $l_slug ?: $slug_en, $cid]);
                } catch (\Throwable $e) {}
            }
        }

        // Üst menüde eşleşen bağlantıyı da otomatik güncelle
        try {
            // menus.url_* kanonik biçimi 'tr/slug/'; eski kayıtlarda baştaki '/' olabilir
            $old_url_tr = 'tr/' . trim($old_slug_tr, '/') . '/';
            $old_url_en = 'en/' . trim($old_slug_en, '/') . '/';
            $new_url_tr = 'tr/' . trim($slug_tr, '/') . '/';
            $new_url_en = 'en/' . trim($slug_en, '/') . '/';
            $st_m = $pdo->prepare("UPDATE `menus` SET `title_tr` = ?, `title_en` = ?, `url_tr` = ?, `url_en` = ? WHERE `url_tr` IN (?, ?) OR `url_en` IN (?, ?)");
            $st_m->execute([$name_tr, $name_en, $new_url_tr, $new_url_en, $old_url_tr, '/' . $old_url_tr, $old_url_en, '/' . $old_url_en]);
        } catch (\Throwable $e) {}
    }

    // 2. Sabit Sayfaları Güncelle
    foreach ($static_pages_meta as $pkey => $pmeta) {
        $old_slug_tr = get_static_slug($pkey, 'tr');
        $old_slug_en = get_static_slug($pkey, 'en');

        foreach ($site_languages as $l_row) {
            $lc = $l_row['code'];
            $def_t = ($lc === 'tr') ? $pmeta['def_title_tr'] : $pmeta['def_title_en'];
            $def_s = ($lc === 'tr') ? $pmeta['def_slug_tr'] : $pmeta['def_slug_en'];

            $val_t = trim($_POST['page_title_' . $pkey . '_' . $lc] ?? '');
            $val_s = ($pkey === 'home') ? '' : slugify(trim($_POST['page_slug_' . $pkey . '_' . $lc] ?? ''));

            set_setting($pkey . '_title_' . $lc, $val_t !== '' ? $val_t : $def_t);
            if ($pkey !== 'home') {
                set_setting('static_slug_' . $pkey . '_' . $lc, $val_s !== '' ? $val_s : $def_s);
            }
        }

        $new_title_tr = get_setting($pkey . '_title_tr', $pmeta['def_title_tr']);
        $new_title_en = get_setting($pkey . '_title_en', $pmeta['def_title_en']);
        $new_slug_tr  = get_setting('static_slug_' . $pkey . '_tr', $pmeta['def_slug_tr']);
        $new_slug_en  = get_setting('static_slug_' . $pkey . '_en', $pmeta['def_slug_en']);

        // Hakkımda sayfası ise items tablosundaki aboutme kaydının başlığını da senkronize et
        if ($pkey === 'about') {
            try {
                $st_ab = $pdo->prepare("UPDATE `items` SET `title_tr` = ?, `title_en` = ? WHERE `slug` = 'aboutme' AND `type` = 'page'");
                $st_ab->execute([$new_title_tr, $new_title_en]);
            } catch (\Throwable $e) {}
        }

        // Üst menüde eşleşen bağlantıyı da otomatik güncelle
        if ($pkey !== 'home') {
            try {
                $old_url_tr = 'tr/' . trim($old_slug_tr, '/') . '/';
                $old_url_en = 'en/' . trim($old_slug_en, '/') . '/';
                $new_url_tr = 'tr/' . trim($new_slug_tr, '/') . '/';
                $new_url_en = 'en/' . trim($new_slug_en, '/') . '/';
                $st_m = $pdo->prepare("UPDATE `menus` SET `title_tr` = ?, `title_en` = ?, `url_tr` = ?, `url_en` = ? WHERE `url_tr` IN (?, ?) OR `url_en` IN (?, ?)");
                $st_m->execute([$new_title_tr, $new_title_en, $new_url_tr, $new_url_en, $old_url_tr, '/' . $old_url_tr, $old_url_en, '/' . $old_url_en]);
            } catch (\Throwable $e) {}
        }
    }

    set_flash('success', __a('Tüm sayfa isimleri, URL adresleri ve üst menü bağlantıları başarıyla güncellendi.', 'All page names, URL slugs, and top menu links have been updated.'));
    header('Location: page-names.php');
    exit;
}

// Güncel kategorileri yeniden yükle
try {
    $all_categories = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

$admin_title     = __a('URL Yönetimi', 'URL Management');
$breadcrumb_root = __a('bc_system', 'Sistem');
$breadcrumb_page = __a('URL Yönetimi', 'URL Management');

require __DIR__ . '/includes/header.php';
?>

<?php require_once __DIR__ . '/includes/settings_tabs.php'; render_settings_header_and_tabs('url'); ?>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><?= e(__a('URL Yönetimi', 'URL Management')) ?></h2>
            <p class="card-sub"><?= __a('Portfolyo, Blog, Hakkımda, İletişim ve diğer tüm sayfaların görünen isimlerini ve URL adreslerini dilediğiniz gibi değiştirebilirsiniz. Değiştirdiğiniz ana isimler yönetim panelindeki şablonlar ve menülerle otomatik senkronize olur.', 'Customize page names and permalinks. Changes automatically sync across panel templates, sidebars, and public navigation.') ?></p>
        </div>
        <button type="submit" form="pageNamesForm" class="btn btn-primary" style="padding:10px 22px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span><?= e(__a('Kaydet', 'Save')) ?></span>
        </button>
    </div>
</div>

<!-- DİL SEÇİM SEKMELERİ (HAKKIMDA SAYFASI GİBİ TABLI & STANDART) -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header" style="flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted);"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <span style="font-size:13px; font-weight:700; color:var(--text-main);"><?= __a('Düzenlenecek Dil Seçimi:', 'Select Language to Edit:') ?></span>
        </div>
        <div class="admin-tabs" id="pnLangTabs" style="border:none; margin:0; flex-wrap:wrap;">
            <?php 
            $preferred_lang = in_array($admin_lang, array_column($site_languages, 'code'), true) ? $admin_lang : ($admin_lang === 'tr' ? 'tr' : ($site_languages[0]['code'] ?? 'en'));
            foreach ($site_languages as $idx_l => $sl):
                $lc = $sl['code'];
                $is_act = ($lc === $preferred_lang);
            ?>
            <button type="button" class="tab-btn <?= $is_act ? 'active' : '' ?>" onclick="switchPageNamesTab('<?= e($lc) ?>', this)">
                <?= e($sl['flag'] ?? '🌐') ?> <?= e($sl['name_native'] ?: strtoupper($lc)) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php lang_bar(['scope' => '#pageNamesForm', 'panes' => ['.pn-lang-pane', '#pn_pane_{lang}'], 'hide' => '#pnLangTabs']); ?>
<form method="POST" action="page-names.php" id="pageNamesForm">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <?php foreach ($site_languages as $idx_l => $sl):
        $lc = $sl['code'];
        $is_act = ($lc === $preferred_lang);
        $port_main_val = get_setting('portfolio_title_' . $lc, ($lc === 'tr' ? 'Portfolyo' : 'Portfolio'));
    ?>
    <div class="pn-lang-pane" id="pn_pane_<?= e($lc) ?>" style="<?= $is_act ? '' : 'display:none;' ?>">
        
        <!-- 1. PORTFOLYO GENEL ADI & KATEGORİLERİ -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div>
                    <h3 class="card-title"><?= __a('1. Portfolyo Ana Başlığı & Kategorileri', '1. Portfolio Main Name & Categories') ?> (<?= e(strtoupper($lc)) ?>)</h3>
                    <p style="margin:2px 0 0; font-size:12px; color:var(--text-muted);"><?= __a('Portfolyo genel başlığını değiştirdiğinizde sol menüdeki şablon isimleri de ("... Ana Sayfa", "... İç Sayfalar") otomatik güncellenir.', 'Changing the Portfolio main name will automatically update all templates and panel sidebar links.') ?></p>
                </div>
                <a href="<?= url('admin/portfolio.php') ?>" class="btn btn-secondary btn-sm"><?= __a('Portfolyo Yönetimi →', 'Portfolio Manager →') ?></a>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:260px; padding-left:24px;"><?= __a('Bölüm / Kategori', 'Section / Category') ?></th>
                            <th style="width:40%;"><?= __a('Görünen İsim (Menü & Sayfa Başlığı)', 'Display Name (Menu & Title)') ?></th>
                            <th style="width:40%; padding-right:24px;"><?= __a('Sayfa adresi', 'Page address') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Portfolyo Genel Başlığı Satırı -->
                        <tr style="background:var(--bg-surface-2);">
                            <td style="padding-left:24px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-sub);"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg>
                                    <div>
                                        <div style="font-weight:700; font-size:14px; color:var(--text-main);"><?= __a('Paneldeki Portfolyo Menü Adı', 'Portfolio Name in Admin Menu') ?></div>
                                        <span style="font-family:var(--font-mono); font-size:12px; color:var(--text-muted);"><?= __a('Yalnızca yönetim panelinin sol menüsünde görünür', 'Shown only in the admin sidebar') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="portfolio_title_<?= e($lc) ?>" class="form-control" value="<?= e($port_main_val) ?>" placeholder="<?= $lc === 'tr' ? 'Portfolyo' : 'Portfolio' ?>" style="font-weight:600;">
                            </td>
                            <td style="padding-right:24px;">
                                <span style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono);"><?= __a('Sitede görünmez', 'Not shown on the site') ?></span>
                            </td>
                        </tr>

                        <!-- Kategoriler -->
                        <?php foreach ($all_categories as $cat):
                            $cid = (int)$cat['id'];
                            $ckey = $cat['key_slug'];
                            $c_name = $cat['name_' . $lc] ?? get_setting($ckey . '_title_' . $lc, ($lc === 'tr' ? $cat['name_tr'] : $cat['name_en']));
                            $c_slug = $cat['slug_' . $lc] ?? get_setting('static_slug_' . $ckey . '_' . $lc, ($lc === 'tr' ? $cat['slug_tr'] : $cat['slug_en']));
                        ?>
                        <tr>
                            <td style="padding-left:24px;">
                                <div style="font-weight:600; font-size:14px; color:var(--text-main);">
                                    <?= e($cat['name_tr'] ?: $cat['name_en']) ?>
                                </div>
                                <span style="font-family:var(--font-mono); font-size:12px; color:var(--text-muted);">kategori: <?= e($ckey) ?></span>
                            </td>
                            <td>
                                <input type="text" name="cat_name_<?= e($lc) ?>_<?= $cid ?>" class="form-control" value="<?= e($c_name) ?>" required>
                            </td>
                            <td style="padding-right:24px;">
                                <div style="display:flex; align-items:center; gap:4px;">
                                    <span style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono);">/<?= e($lc) ?>/</span>
                                    <input type="text" name="cat_slug_<?= e($lc) ?>_<?= $cid ?>" class="form-control font-mono" value="<?= e($c_slug) ?>" required>
                                    <span style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono);">/</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. SABİT SAYFALAR -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <h3 class="card-title"><?= __a('2. Sabit Sayfalar (Blog, Hakkımda, İletişim vb.)', '2. Static Pages (Blog, About, Contact etc.)') ?> (<?= e(strtoupper($lc)) ?>)</h3>
                <p style="margin:2px 0 0; font-size:12px; color:var(--text-muted);"><?= __a('Örn: Blog sayfasını "Yazılar", "Haberler", "Günlük" veya "Journal" olarak değiştirebilirsiniz.', 'Rename pages to anything you like. The top menu updates automatically.') ?></p>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:260px; padding-left:24px;"><?= __a('Sayfa Türü', 'Page Type') ?></th>
                            <th style="width:40%;"><?= __a('Görünen İsim (Menü & Sayfa Başlığı)', 'Display Name (Menu & Title)') ?></th>
                            <th style="width:40%; padding-right:24px;"><?= __a('Sayfa adresi', 'Page address') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($static_pages_meta as $pkey => $pmeta):
                            $def_t = ($lc === 'tr') ? $pmeta['def_title_tr'] : $pmeta['def_title_en'];
                            $def_s = ($lc === 'tr') ? $pmeta['def_slug_tr'] : $pmeta['def_slug_en'];
                            $cur_t = get_setting($pkey . '_title_' . $lc, $def_t);
                            $cur_s = get_setting('static_slug_' . $pkey . '_' . $lc, $def_s);
                            $is_home_row = !empty($pmeta['is_home']);
                        ?>
                        <tr>
                            <td style="padding-left:24px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="color:var(--text-muted); display:inline-flex;"><?= $pmeta['icon'] ?></span>
                                    <div>
                                        <div style="font-weight:600; font-size:14px; color:var(--text-main);">
                                            <?= e($admin_lang === 'en' ? $pmeta['label_en'] : ($admin_lang === 'tr' ? $pmeta['label_tr'] : ui_translate($pmeta['label_en'], $admin_lang))) ?>
                                        </div>
                                        <span style="font-family:var(--font-mono); font-size:12px; color:var(--text-muted);"><?= __a('sayfa', 'page') ?>: <?= e($pkey) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="page_title_<?= e($pkey) ?>_<?= e($lc) ?>" class="form-control" value="<?= e($cur_t) ?>" placeholder="<?= e($def_t) ?>">
                            </td>
                            <td style="padding-right:24px;">
                                <?php if ($is_home_row): ?>
                                    <span style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono);">/<?= e($lc) ?>/ (<?= __a('Ana Sayfa Kök Dizini', 'Homepage Root') ?>)</span>
                                <?php else: ?>
                                    <div style="display:flex; align-items:center; gap:4px;">
                                        <span style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono);">/<?= e($lc) ?>/</span>
                                        <input type="text" name="page_slug_<?= e($pkey) ?>_<?= e($lc) ?>" class="form-control font-mono" value="<?= e($cur_s) ?>" placeholder="<?= e($def_s) ?>">
                                        <span style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono);">/</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <?php endforeach; ?>

    <div style="display:flex; justify-content:flex-end; margin-bottom:40px;">
        <button type="submit" class="btn btn-primary" style="padding:11px 28px; font-size:14px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span><?= e(__a('Kaydet', 'Save')) ?></span>
        </button>
    </div>
</form>

<script>
function switchPageNamesTab(lang, btn) {
    document.querySelectorAll('.pn-lang-pane').forEach(function(p) { p.style.display = 'none'; });
    var target = document.getElementById('pn_pane_' + lang);
    if (target) target.style.display = 'block';
    if (btn && btn.parentElement) {
        btn.parentElement.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
    }
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
