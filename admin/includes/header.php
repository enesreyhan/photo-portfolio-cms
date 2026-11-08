<?php
/**
 * admin/includes/header.php
 * Yönetim Paneli Üst Bilgi ve Kenar Çubuğu (Sidebar)
 * Stravia Modern Dashboard Mizanpajı
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/lang_bar.php'; // ortak içerik dili çubuğu: lang_bar([...])
require_once __DIR__ . '/editor_bar.php'; // ortak düzenleyici üst çubuğu: editor_bar([...])

require_login();

if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// noi18n işaretleri kullanıcı içeriğini çeviri filtresinden korur; her dilde çıktıdan silinir ki editöre/DB'ye sızmasın
ob_start(function ($buf) use ($admin_lang) {
    if (($admin_lang ?? 'tr') !== 'tr') $buf = admin_translate_ui_buffer($buf);
    return str_replace(['<!--noi18n-->', '<!--/noi18n-->'], '', $buf);
});

$user = current_user();
$current_script = basename($_SERVER['PHP_SELF']);
$flashes = get_flashes();

// İstatistik sayaçları
$pdo = DB::getConnection();
$c_portfolio = (int)$pdo->query("SELECT COUNT(*) FROM `items` WHERE `type` NOT IN ('page', 'blog')")->fetchColumn();
$c_blog = 0;
try {
    $c_blog = (int)$pdo->query("SELECT COUNT(*) FROM `items` WHERE `type` = 'blog'")->fetchColumn();
} catch (\Throwable $e) {}
$c_media = (int)$pdo->query("SELECT COUNT(*) FROM `media`")->fetchColumn();
$c_homepage_slides = 0;
try {
    $c_homepage_slides = (int)$pdo->query("SELECT COUNT(*) FROM `homepage_slides` WHERE `is_active` = 1")->fetchColumn();
} catch (\Throwable $e) {}
$c_unread_messages = 0;
try {
    $c_unread_messages = (int)$pdo->query("SELECT COUNT(*) FROM `messages` WHERE `is_read` = 0")->fetchColumn();
} catch (\Throwable $e) {}

// Ekmek Kırıntısı (Breadcrumbs): kök her zaman sol menüdeki grupla aynıdır
$nav_group_map = [
    'content'  => ['pages.php', 'homepage.php', 'index.php', 'homepage-slide-edit.php', 'portfolio.php', 'projects.php', 'series.php', 'single.php', 'item-edit.php', 'blog.php', 'blog-edit.php', 'about.php', 'media.php', 'convert-images.php', 'categories.php'],
    'visitors' => ['messages.php', 'stats.php'],
    'design'   => ['templates.php'],
    'settings' => ['settings.php', 'legal.php', 'backup.php', 'languages.php', 'translations.php', 'page-names.php'],
];
$nav_group_map = ['overview' => ['dashboard.php']] + $nav_group_map;
$nav_group_labels = [
    'overview' => __a('Panel', 'Panel'),
    'content'  => __a('İçerik', 'Content'),
    'visitors' => __a('Ziyaretçiler', 'Visitors'),
    'design'   => __a('Tasarım', 'Design'),
    'settings' => __a('Ayarlar', 'Settings'),
];
foreach ($nav_group_map as $ng_id => $ng_scripts) {
    if (in_array($current_script, $ng_scripts, true)) {
        $breadcrumb_root = $nav_group_labels[$ng_id];
        break;
    }
}
if (!isset($breadcrumb_root)) {
    $breadcrumb_root = $nav_group_labels['content'];
}

$breadcrumb_page = $admin_title ?? __a('bc_control_panel');

// Karanlık / Aydınlık Tema Tercihi
$admin_theme = $_COOKIE['admin_theme'] ?? 'light';
if (!in_array($admin_theme, ['dark', 'light'], true)) {
    $admin_theme = 'light';
}

$admin_lang_info = get_language_info($admin_lang);
$admin_dir = $admin_lang_info['direction'] ?? (in_array($admin_lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr');
?>
<!DOCTYPE html>
<html lang="<?= e($admin_lang) ?>" dir="<?= e($admin_dir) ?>" data-theme="<?= e($admin_theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($admin_title ?? __a('bc_control_panel')) ?> - <?= e(site_owner_name()) ?></title>
    
    <?php
    $adm_fav_enabled = (int)get_setting('brand_favicon_enabled', 1);
    $adm_fav_path    = trim((string)get_setting('brand_favicon', 'assets/brand/favicon.png'));
    $adm_fav_url     = ($adm_fav_enabled && $adm_fav_path !== '') ? url($adm_fav_path) : url('assets/brand/favicon.png');
    ?>
    <?php if ($adm_fav_enabled): ?>
    <link rel="icon" href="<?= e($adm_fav_url) ?>" />
    <?php else: ?>
    <link rel="icon" href="data:," />
    <?php endif; ?>
    <meta name="csrf-token" content="<?= csrf_token() ?>">

    <!-- Tema Titremesini Önleyen Erken Yükleme Betiği (Zero-Flicker Inline Script) -->
    <script>
    (function() {
        try {
            var t = localStorage.getItem('admin_theme');
            if (!t) {
                var m = document.cookie.match(/admin_theme=([^;]+)/);
                t = m ? m[1] : null;
            }
            if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                t = 'dark';
            }
            if (t === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        } catch (e) {}
    })();
    </script>
    <!-- Sitede kullanılan orijinal fontlar (ZedDisplay, ZedText, Space Grotesk, JetBrains Mono) -->
    <link rel="stylesheet" href="<?= url('fonts.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    
    <link rel="stylesheet" href="<?= url('admin/assets/admin.css') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/admin.css') ?>">

    <script>
    (function() {
        var isPhone = window.matchMedia && window.matchMedia('(max-width: 900px)').matches;
        if (!isPhone && localStorage.getItem('admin_sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    })();
    window.ADMIN_LANG = <?= json_encode($admin_lang) ?>;

    function toggleNavGroup(groupId) {
        var group = document.querySelector('.nav-group[data-group-id="' + groupId + '"]');
        if (!group) return;
        var isCollapsed = group.classList.toggle('is-collapsed');
        var btn = group.querySelector('.nav-group-toggle');
        if (btn) btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');

        var saved = [];
        try {
            saved = JSON.parse(localStorage.getItem('admin_collapsed_nav_groups') || '[]');
        } catch (e) { saved = []; }

        if (isCollapsed) {
            if (saved.indexOf(groupId) === -1) saved.push(groupId);
        } else {
            saved = saved.filter(function(id) { return id !== groupId; });
        }

        try {
            localStorage.setItem('admin_collapsed_nav_groups', JSON.stringify(saved));
        } catch (e) {}
    }

    function initNavGroups() {
        var saved = [];
        try {
            saved = JSON.parse(localStorage.getItem('admin_collapsed_nav_groups') || '[]');
        } catch (e) { saved = []; }

        document.querySelectorAll('.nav-group').forEach(function(group) {
            var id = group.getAttribute('data-group-id');
            var hasActive = group.querySelector('.nav-item.active');
            var isCollapsed = (saved.indexOf(id) !== -1) && !hasActive;
            if (isCollapsed) {
                group.classList.add('is-collapsed');
                var btn = group.querySelector('.nav-group-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            } else {
                group.classList.remove('is-collapsed');
                var btn = group.querySelector('.nav-group-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            }
        });
    }
    </script>
<?php lang_slugify_js(); ?>
</head>
<body>
<script>
if (document.documentElement.classList.contains('sidebar-collapsed')) {
    document.body.classList.add('sidebar-collapsed');
}
</script>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ── STRAVIA TARZI SOL KENAR ÇUBUĞU (ADMIN SIDEBAR) ── -->
<!-- ══════════════════════════════════════════════════════════════ -->
<aside class="admin-sidebar">
    
    <!-- 1. Marka, Logo ve Sol Menü Kapat/Aç Butonu -->
    <div class="sidebar-header">
        <a href="<?= url('admin/dashboard.php') ?>" class="sidebar-brand" title="<?= e(site_owner_name()) ?>">
            <span class="brand-symbol">
                <img src="<?= e($adm_fav_url) ?>" alt="<?= e(site_owner_name()) ?>">
            </span>
            <span class="sidebar-brand-text"><?= e(site_owner_name()) ?></span>
        </a>
        <div style="display:flex; align-items:center; gap:6px;">
            <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" onclick="toggleAdminSidebar()" title="<?= e(__a('topbar_collapse')) ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                    <path d="M9 3v18"/>
                    <path d="m14 9-3 3 3 3"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- 2. Hızlı Arama Kutusu (Search anything ⌘ K) -->
    <div class="sidebar-search-wrap">
        <div class="sidebar-search" onclick="document.getElementById('adminQuickSearchInput').focus()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" id="adminQuickSearchInput" placeholder="<?= e(__a('search_placeholder')) ?>" autocomplete="off">
            <kbd class="kbd-pill">⌘ K</kbd>
        </div>
        <!-- Evrensel Anlık Arama Sonuç Dropdown'ı -->
        <div id="adminSearchResultsDropdown" class="admin-search-dropdown"></div>
    </div>

    <!-- 3. Navigasyon Grupları (Açılır / Kapanır) -->
    <?php
    $portfolio_nav_title = get_setting('portfolio_title_' . $admin_lang, __a('menu_portfolio', 'Portfolyo'));

    $homepage_nav_title = get_setting('homepage_title_' . $admin_lang, __a('menu_homepage', 'Ana Sayfa'));
    $about_nav_title    = get_setting('about_title_' . $admin_lang, __a('menu_about', 'Hakkımda'));
    $blog_nav_title     = get_setting('blog_title_' . $admin_lang, __a('menu_blog', 'Blog / Yazılar'));
    ?>
    <?php
    $template_scripts = ['templates.php'];
    ?>
    <?php $c_active_langs = count(get_supported_languages()); ?>
    <div class="sidebar-nav">
        <a href="<?= url('admin/dashboard.php') ?>" class="nav-item nav-item-top <?= $current_script === 'dashboard.php' ? 'active' : '' ?>" title="<?= e(__a('Genel Bakış', 'Overview')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            <span><?= e(__a('Genel Bakış', 'Overview')) ?></span>
        </a>
        <!-- İÇERİK: sitede yayınlanan her şey -->
        <div class="nav-group" data-group-id="content">
            <button type="button" class="nav-group-toggle" onclick="toggleNavGroup('content')" aria-expanded="true">
                <span class="nav-label"><?= e(__a('İÇERİK', 'CONTENT')) ?></span>
                <svg class="nav-group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="nav-group-items">
                <a href="<?= url('admin/pages.php') ?>" class="nav-item <?= in_array($current_script, ['pages.php', 'homepage.php', 'index.php', 'homepage-slide-edit.php', 'about.php']) ? 'active' : '' ?>" title="<?= e(__a('Sayfalar', 'Pages')) ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    <span><?= e(__a('Sayfalar', 'Pages')) ?></span>
                </a>
                <a href="<?= url('admin/portfolio.php') ?>" class="nav-item <?= in_array($current_script, ['portfolio.php', 'projects.php', 'series.php', 'single.php', 'item-edit.php', 'categories.php']) ? 'active' : '' ?>" title="<?= e($portfolio_nav_title) ?> (<?= $c_portfolio ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg>
                    <span><?= e($portfolio_nav_title) ?></span>
                    <span class="nav-counter"><?= $c_portfolio ?></span>
                </a>
                <a href="<?= url('admin/blog.php') ?>" class="nav-item <?= in_array($current_script, ['blog.php', 'blog-edit.php']) ? 'active' : '' ?>" title="<?= e($blog_nav_title) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                    <span><?= e($blog_nav_title) ?></span>
                    <span class="nav-counter"><?= $c_blog ?></span>
                </a>
                <a href="<?= url('admin/media.php') ?>" class="nav-item <?= in_array($current_script, ['media.php', 'convert-images.php']) ? 'active' : '' ?>" title="<?= e(__a('menu_media')) ?> (<?= $c_media ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    <span><?= e(__a('menu_media')) ?></span>
                    <span class="nav-counter"><?= $c_media ?></span>
                </a>
            </div>
        </div>
        <!-- ZİYARETÇİLER: gelen mesajlar ve istatistikler -->
        <div class="nav-group" data-group-id="visitors">
            <button type="button" class="nav-group-toggle" onclick="toggleNavGroup('visitors')" aria-expanded="true">
                <span class="nav-label"><?= e(__a('ZİYARETÇİLER', 'VISITORS')) ?></span>
                <svg class="nav-group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="nav-group-items">
                <a href="<?= url('admin/messages.php') ?>" class="nav-item <?= $current_script === 'messages.php' ? 'active' : '' ?>" title="<?= e(__a('menu_messages', 'Gelen Mesajlar')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <span><?= e(__a('menu_messages', 'Gelen Mesajlar')) ?></span>
                    <?php if ($c_unread_messages > 0): ?>
                        <span class="nav-counter" style="background:#ef4444; color:#ffffff; font-weight:700;"><?= $c_unread_messages ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('admin/stats.php') ?>" class="nav-item <?= $current_script === 'stats.php' ? 'active' : '' ?>" title="<?= e(__a('menu_stats')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    <span><?= e(__a('menu_stats')) ?></span>
                </a>
            </div>
        </div>
        <!-- TASARIM: sayfaların nasıl göründüğü -->
        <div class="nav-group" data-group-id="design">
            <button type="button" class="nav-group-toggle" onclick="toggleNavGroup('design')" aria-expanded="true">
                <span class="nav-label"><?= e(__a('TASARIM', 'DESIGN')) ?></span>
                <svg class="nav-group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="nav-group-items">
                <a href="<?= url('admin/templates.php') ?>" class="nav-item <?= in_array($current_script, $template_scripts) ? 'active' : '' ?>" title="<?= e(__a('Temalar', 'Themes')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                    <span><?= e(__a('Temalar', 'Themes')) ?></span>
                    
                </a>
            </div>
        </div>
        <!-- AYARLAR: sitenin geneline ait ayarlar -->
        <div class="nav-group" data-group-id="settings">
            <button type="button" class="nav-group-toggle" onclick="toggleNavGroup('settings')" aria-expanded="true">
                <span class="nav-label"><?= e(__a('AYARLAR', 'SETTINGS')) ?></span>
                <svg class="nav-group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="nav-group-items">
                <a href="<?= url('admin/settings.php') ?>" class="nav-item <?= in_array($current_script, ['settings.php', 'legal.php', 'backup.php', 'page-names.php', 'languages.php', 'translations.php']) ? 'active' : '' ?>" title="<?= e(__a('menu_settings')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0 2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span><?= e(__a('menu_settings')) ?></span>
                </a>
            </div>
        </div>
    </div>
    <script>initNavGroups();</script>

    <!-- 4. Kullanıcı Profil Kartı (Shuvo Huq Tarzı) -->
    <div class="sidebar-user-card-wrap">
        <div class="sidebar-user-card">
            <a href="<?= url('admin/settings.php') ?>" class="user-avatar-wrap" title="<?= e(__a('menu_settings')) ?>" style="text-decoration:none; display:flex;">
                <?php // Avatar: Hakkımda sayfasının portresi; yoksa baş harf
                $adm_avatar = '';
                try { $adm_avatar = (string)DB::getConnection()->query("SELECT `cover_image` FROM `items` WHERE `slug` = 'aboutme' AND `type` = 'page' LIMIT 1")->fetchColumn(); } catch (\Throwable $e) {} ?>
                <?php if ($adm_avatar !== ''): ?>
                <img src="<?= e(url(ltrim($adm_avatar, '/'))) ?>" alt="<?= e(site_owner_name()) ?>" class="user-avatar-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <?php endif; ?>
                <div class="user-avatar-fallback"<?= $adm_avatar === '' ? ' style="display:flex;"' : '' ?>><?= e(mb_strtoupper(mb_substr(site_owner_name(), 0, 1))) ?></div>
            </a>
            <a href="<?= url('admin/settings.php') ?>" class="user-meta" title="<?= e(__a('menu_settings')) ?>" style="text-decoration:none; color:inherit; min-width:0;">
                <span class="user-display-name"><?= e(site_owner_name()) ?></span>
                <span class="user-role-label"><?= e(($user['email'] ?? '') ?: get_setting('contact_email') ?: ($user['username'] ?? '')) ?></span>
            </a>
            <?php $admin_avail_langs = get_admin_enabled_languages();
            if (count($admin_avail_langs) > 1): ?>
            <details class="ui-lang-menu">
                <summary class="user-logout-btn ui-lang-btn" title="<?= e(__a('Panel dili', 'Panel language')) ?>" aria-label="<?= e(__a('Panel dili', 'Panel language')) ?>">
                    <span class="ui-lang-code"><?= e(strtoupper($admin_lang)) ?></span>
                </summary>
                <div class="ui-lang-list" role="menu">
                    <div class="ui-lang-head"><?= e(__a('Panel dili', 'Panel language')) ?></div>
                    <p class="ui-lang-note"><?= e(__a('Yalnızca panelin menü ve düğmelerini değiştirir. İçeriğin dili her düzenleyicinin üstündeki çubuktan seçilir.', 'Changes only the panel’s menus and buttons. Content language is chosen from the bar at the top of each editor.')) ?></p>
                    <?php foreach ($admin_avail_langs as $al_code => $al_info): ?>
                    <a role="menuitem" href="<?= e(get_admin_lang_switch_url($al_code)) ?>" class="ui-lang-item<?= $admin_lang === $al_code ? ' is-current' : '' ?>"<?= $admin_lang === $al_code ? ' aria-current="true"' : '' ?>>
                        <span><?= e($al_info['flag'] ?? '') ?></span><span><?= e($al_info['name_native'] ?? strtoupper($al_code)) ?></span><?= $admin_lang === $al_code ? '<span class="ui-lang-check">✓</span>' : '' ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endif; ?>
            <form method="post" action="<?= url('admin/logout.php') ?>" style="margin:0;display:flex;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="user-logout-btn" title="<?= e(__a('logout_title')) ?>" aria-label="<?= e(__a('logout_title')) ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>
<div class="mobile-nav-backdrop" onclick="closeMobileNav()" aria-hidden="true"></div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ── ANA ÇALIŞMA ALANI & ÜST ÇUBUK (MAIN CONTENT AREA) ── -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div class="admin-main">
    <header class="admin-topbar">
        <div class="topbar-left">
            <!-- Sidebar Aç/Kapa Butonu -->
            <button type="button" id="sidebarToggleBtn" class="topbar-icon-btn" onclick="toggleAdminSidebar()" title="<?= e(__a('topbar_collapse')) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                    <path d="M9 3v18"/>
                </svg>
            </button>

            <!-- Ekmek Kırıntısı (⊞ Overview / Dashboard) -->
            <nav class="admin-breadcrumbs">
                <span class="breadcrumb-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                </span>
                <span class="breadcrumb-root"><?= e(__a($breadcrumb_root)) ?></span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?= e(__a($breadcrumb_page)) ?></span>
            </nav>
        </div>

        <div class="topbar-right">
            <!-- Tema Değiştir (Aydınlık / Karanlık) Butonu - Sağ Üst (View Site'ın Solunda) -->
            <button type="button" id="adminThemeToggleBtn" class="topbar-icon-btn theme-toggle-btn" onclick="toggleAdminTheme()" title="<?= e(__a('theme_toggle_title', 'Karanlık / Aydınlık Tema (Ctrl+Shift+D)')) ?>" aria-label="<?= e(__a('theme_toggle_title', 'Tema Değiştir')) ?>">
                <svg class="theme-icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                <svg class="theme-icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            </button>

            <a href="<?= url('/') ?>" target="_blank" class="topbar-link-btn" title="<?= e(__a('topbar_view_site')) ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                <span><?= e(__a('topbar_view_site')) ?></span>
            </a>
            <a href="<?= url('admin/settings.php') ?>" class="topbar-icon-btn" title="<?= e(__a('menu_settings')) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </a>
        </div>
    </header>

    <main class="admin-content">
        <?php
        // ── Bölüm içi alt sekmeler (tüm panelde aynı görünüm) ──
        $subnav = null;
        if (in_array($current_script, ['portfolio.php', 'categories.php'], true)) {
            $subnav = [
                'portfolio.php'  => __a('Çalışmalar', 'Works'),
                'categories.php' => __a('Kategoriler', 'Categories'),
            ];
        } elseif (in_array($current_script, ['media.php', 'convert-images.php'], true)) {
            $subnav = [
                'media.php'          => __a('Medya Kütüphanesi', 'Media Library'),
                'convert-images.php' => __a('Görsel Dönüştürücü (AVIF / WebP)', 'Image Converter (AVIF / WebP)'),
            ];
        } elseif ($current_script === 'legal.php') {
            $subnav = ['settings.php#gizlilik' => '← ' . __a('Gizlilik & KVKK Ayarları', 'Privacy & KVKK Settings')];
        }
        ?>
        <?php if ($subnav): ?>
        <nav class="subnav-tabs" aria-label="<?= e(__a('Bölüm sayfaları', 'Section pages')) ?>">
            <?php foreach ($subnav as $sn_file => $sn_label): ?>
                <?php if ($sn_file === '|'): ?>
                    <span class="subnav-tabs-sep"></span>
                <?php elseif ($sn_file === '#label'): ?>
                    <span class="subnav-tabs-label"><?= e($sn_label) ?></span>
                <?php else: ?>
                    <a href="<?= url('admin/' . $sn_file) ?>" class="subnav-tab <?= $current_script === $sn_file ? 'is-active' : '' ?>"><?= e($sn_label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <?php foreach ($flashes as $flash): ?>
            <div class="flash-alert flash-<?= e($flash['type']) ?>">
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endforeach; ?>
