<?php
/**
 * admin/dashboard.php
 * Genel Bakış: sitenin durumu tek ekranda. Yalnızca okur; değişiklikler her ayarın kendi sayfasında yapılır.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/../includes/cookie_consent.php';

require_login();

$pdo = DB::getConnection();

// ── Sayılar ──
$q = function (string $sql, array $args = []) use ($pdo) {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($args);
        return (int)$st->fetchColumn();
    } catch (\Throwable $e) {
        return 0;
    }
};
$n_unread    = $q("SELECT COUNT(*) FROM `messages` WHERE `is_read` = 0");
$n_works     = $q("SELECT COUNT(*) FROM `items` WHERE `type` NOT IN ('page', 'blog') AND `status` = 'published'");
$n_works_all = $q("SELECT COUNT(*) FROM `items` WHERE `type` NOT IN ('page', 'blog')");
$n_media     = $q("SELECT COUNT(*) FROM `media`");
$n_slides    = $q("SELECT COUNT(*) FROM `homepage_slides` WHERE `is_active` = 1");

// ── Son mesajlar (SQLite'ta saat UTC'dir) ──
$recent_messages = [];
try {
    $recent_messages = $pdo->query("SELECT `id`, `name`, `subject`, `message`, `is_read`, `created_at` FROM `messages` ORDER BY `created_at` DESC, `id` DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}
$fmt_msg_time = function (string $raw): string {
    try {
        $dt = new DateTime($raw, new DateTimeZone(DB_DRIVER === 'sqlite' ? 'UTC' : date_default_timezone_get()));
        $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return $dt->format('d.m.Y H:i');
    } catch (\Throwable $e) {
        return $raw;
    }
};

// ── Son düzenlenen çalışmalar ──
$recent_items = [];
try {
    $recent_items = $pdo->query("SELECT `id`, `type`, `title_tr`, `title_en`, `status`, `cover_image`, `updated_at` FROM `items` WHERE `type` NOT IN ('page', 'blog') ORDER BY `updated_at` DESC, `id` DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// ── Ziyaretçi özeti (İstatistikler sayfasının önbelleğinden; burada API çağrısı yapılmaz) ──
$ga = null;
$ga_raw = get_setting('ga_cache_30d', '');
if ($ga_raw !== '') {
    $tmp = json_decode($ga_raw, true);
    if (is_array($tmp) && isset($tmp['active_users'])) {
        $ga = $tmp;
    }
}

// ── Site durumu ──
$maintenance_on = (get_setting('maintenance_mode', '0') === '1');
$ga_on          = (trim((string)get_setting('google_analytics', '')) !== '');
$status_rows = [
    [
        'label' => __a('Bakım Modu', 'Maintenance Mode'),
        'on'    => $maintenance_on,
        'on_t'  => __a('Açık — ziyaretçiler siteyi göremiyor', 'On — visitors cannot see the site'),
        'off_t' => __a('Kapalı', 'Off'),
        'warn_when_on' => true,
        'href'  => 'settings.php#bakim-modu',
    ],
    [
        'label' => __a('Ana Sayfa Seçkisi', 'Homepage Showcase'),
        'on'    => $n_slides > 0,
        'on_t'  => sprintf(__a('%d fotoğraf', '%d photos'), $n_slides),
        'off_t' => __a('Boş', 'Empty'),
        'warn_when_off' => true,
        'href'  => 'homepage.php',
    ],
    [
        'label' => get_setting('blog_title_' . $admin_lang) ?: __a('Blog', 'Blog'),
        'on'    => (get_setting('blog_enabled', '0') === '1'),
        'on_t'  => __a('Yayında', 'Live'),
        'off_t' => __a('Gizli', 'Hidden'),
        'href'  => 'settings.php#sayfalar',
    ],
    [
        'label' => get_setting('contact_title_' . $admin_lang) ?: __a('İletişim', 'Contact'),
        'on'    => (get_setting('contact_page_enabled', '0') === '1'),
        'on_t'  => __a('Yayında', 'Live'),
        'off_t' => __a('Gizli', 'Hidden'),
        'href'  => 'settings.php#sayfalar',
    ],
    [
        'label' => __a('Yasal Metinler (KVKK)', 'Legal Pages (KVKK)'),
        'on'    => (get_setting('legal_pages_enabled', '1') === '1'),
        'on_t'  => __a('Yayında', 'Live'),
        'off_t' => __a('Gizli', 'Hidden'),
        'href'  => 'settings.php#gizlilik',
    ],
    [
        'label' => __a('Çerez Onayı', 'Cookie Consent'),
        'on'    => cookie_consent_enabled(),
        'on_t'  => __a('Açık', 'On'),
        'off_t' => __a('Kapalı', 'Off'),
        'warn_when_off' => $ga_on,
        'href'  => 'settings.php#cerez-onayi',
    ],
];

// ── Dikkat gerektirenler ──
$attention = [];
if ($maintenance_on) {
    $attention[] = [__a('Bakım modu açık: ziyaretçiler siteyi değil bakım ekranını görüyor.', 'Maintenance mode is on: visitors see the maintenance screen instead of the site.'), 'settings.php#bakim-modu', __a('Bakım Modu', 'Maintenance')];
}
if ($n_unread > 0) {
    $attention[] = [sprintf(__a('%d okunmamış mesajınız var.', 'You have %d unread messages.'), $n_unread), 'messages.php', __a('Mesajları Aç', 'Open Messages')];
}
if (!cookie_consent_enabled() && $ga_on) {
    $attention[] = [__a('Google Analytics açık ama çerez onayı kapalı. KVKK gereği ziyaretçiden onay alınması önerilir.', 'Google Analytics is on but cookie consent is off. Asking visitors for consent is recommended.'), 'settings.php#cerez-onayi', __a('Çerez Onayı', 'Cookie Consent')];
}

$type_labels = [
    'project'  => __a('Proje', 'Project'),
    'series'   => __a('Seri', 'Series'),
    'single'   => __a('Tekil', 'Single'),
    'selected' => __a('Seçki', 'Selected'),
];

$admin_title     = __a('Genel Bakış', 'Overview');
$breadcrumb_page = __a('Genel Bakış', 'Overview');

require __DIR__ . '/includes/header.php';
?>

<style>
.dash-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap: 24px; align-items: start; }
.dash-col { display: flex; flex-direction: column; gap: 24px; min-width: 0; }
.dash-col > .card { margin-bottom: 0; }
@media (max-width: 960px) { .dash-grid { grid-template-columns: 1fr; } }
.dash-attention { display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; }
.dash-attention-item {
    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    padding: 11px 14px; border-radius: 10px;
    border: 1px solid var(--warning, #f59e0b);
    background: rgba(245, 158, 11, 0.08);
    font-size: 13px; color: var(--text-main);
}
a.kpi-card { text-decoration: none; color: inherit; }
a.kpi-card.is-alert { border-color: var(--accent-blue); box-shadow: 0 0 0 1px var(--accent-blue); }
.kpi-sub { font-size: 12px; font-weight: 500; color: var(--text-muted); font-family: var(--font-body); }
.dash-list { display: flex; flex-direction: column; }
.dash-list-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 20px; border-top: 1px solid var(--border-subtle);
    text-decoration: none; color: inherit;
}
.dash-list-item:first-child { border-top: none; }
.dash-list-item:hover { background: var(--bg-hover); }
.dash-list-main { min-width: 0; flex: 1; }
.dash-list-title { font-size: 14px; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-list-sub { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-list-meta { font-size: 12px; color: var(--text-sub); font-family: var(--font-mono); flex-shrink: 0; }
.dash-unread-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent-blue); flex-shrink: 0; }
.dash-read-dot { width: 8px; height: 8px; flex-shrink: 0; }
.dash-empty { padding: 22px 20px; font-size: 13px; color: var(--text-muted); }
.dash-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.dash-works { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; padding: 16px 20px 20px; }
.dash-work { text-decoration: none; color: inherit; min-width: 0; }
.dash-work-img { position: relative; aspect-ratio: 4 / 3; border-radius: 8px; overflow: hidden; background: var(--bg-surface-2); }
.dash-work-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .25s ease; }
.dash-work:hover .dash-work-img img { transform: scale(1.04); }
.dash-work-badge { position: absolute; top: 8px; left: 8px; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: rgba(17, 24, 39, 0.78); color: #fff; }
.dash-work .dash-list-title { margin-top: 8px; }
.status-pill { font-size: 12px; font-weight: 700; padding: 3px 9px; border-radius: 6px; white-space: nowrap; }
.status-pill.is-on { background: var(--success-soft); color: var(--success-text); }
.status-pill.is-off { background: var(--bg-surface-2); color: var(--text-muted); }
.status-pill.is-warn { background: rgba(245, 158, 11, 0.15); color: var(--warning-text); }
[data-theme="dark"] .status-pill.is-on { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
[data-theme="dark"] .status-pill.is-warn { color: #fbbf24; }
</style>

<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('Genel Bakış', 'Overview')) ?></h1>
        <p class="page-header-sub"><?= e(__a('Sitenizin durumu tek bakışta. Bir şeyi değiştirmek için ilgili satıra tıklayın.', 'Your site at a glance. Click a row to change something.')) ?></p>
    </div>
    <div class="dash-actions">
        <a href="item-edit.php" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            <span><?= e(__a('Yeni Çalışma', 'New Work')) ?></span>
        </a>
        <a href="blog-edit.php" class="btn btn-secondary"><?= e(__a('Yeni Yazı', 'New Post')) ?></a>
        <a href="media.php" class="btn btn-secondary"><?= e(__a('Fotoğraf Yükle', 'Upload Photos')) ?></a>
    </div>
</div>

<?php if ($attention): ?>
<div class="dash-attention">
    <?php foreach ($attention as [$msg, $href, $btn]): ?>
    <div class="dash-attention-item">
        <span><?= e($msg) ?></span>
        <a href="<?= e($href) ?>" class="btn btn-secondary btn-sm"><?= e($btn) ?> →</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="kpi-grid">
    <a href="stats.php" class="kpi-card">
        <div class="kpi-icon-box" style="background:rgba(245,158,11,0.12); color:var(--warning-text);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></div>
        <div>
            <div class="kpi-meta-label"><?= e(__a('Ziyaretçi · Son 30 Gün', 'Visitors · Last 30 Days')) ?></div>
            <?php if ($ga): ?>
                <div class="kpi-value-num"><?= (int)$ga['active_users'] ?> <span class="kpi-sub"><?= (int)($ga['pageviews'] ?? 0) ?> <?= e(__a('görüntüleme', 'views')) ?></span></div>
            <?php else: ?>
                <div class="kpi-value-num">—</div>
            <?php endif; ?>
        </div>
    </a>
    <a href="portfolio.php" class="kpi-card">
        <div class="kpi-icon-box" style="background:rgba(16,185,129,0.12); color:var(--success-text);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M17 3v18"/><path d="M3 12h18"/></svg></div>
        <div>
            <div class="kpi-meta-label"><?= e(__a('Yayındaki Çalışma', 'Published Works')) ?></div>
            <div class="kpi-value-num"><?= $n_works ?> <span class="kpi-sub">/ <?= $n_works_all ?></span></div>
        </div>
    </a>
    <a href="media.php" class="kpi-card">
        <div class="kpi-icon-box" style="background:rgba(168,85,247,0.12); color:#a855f7;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></div>
        <div>
            <div class="kpi-meta-label"><?= e(__a('Medya', 'Media')) ?></div>
            <div class="kpi-value-num"><?= $n_media ?> <span class="kpi-sub"><?= e(__a('fotoğraf', 'photos')) ?></span></div>
        </div>
    </a>
    <a href="messages.php" class="kpi-card<?= $n_unread > 0 ? ' is-alert' : '' ?>">
        <div class="kpi-icon-box"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></div>
        <div>
            <div class="kpi-meta-label"><?= e(__a('Okunmamış Mesaj', 'Unread Messages')) ?></div>
            <div class="kpi-value-num"><?= $n_unread ?></div>
        </div>
    </a>
</div>

<div class="dash-grid">
    <div class="dash-col">
        <!-- Son düzenlenen çalışmalar -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= e(__a('Son Düzenlenen Çalışmalar', 'Recently Edited Works')) ?></h2>
                <a href="portfolio.php" class="btn btn-secondary btn-sm"><?= e(__a('Portfolyo', 'Portfolio')) ?> →</a>
            </div>
            <?php if (!$recent_items): ?>
                <div class="dash-empty"><?= e(__a('Henüz çalışma yok.', 'No works yet.')) ?> <a href="item-edit.php" style="color:var(--accent-blue);"><?= e(__a('İlk çalışmanızı ekleyin', 'Add your first work')) ?> →</a></div>
            <?php else: ?>
            <div class="dash-works">
                <?php foreach ($recent_items as $it):
                    $it_title = $admin_lang === 'en' ? ($it['title_en'] ?: $it['title_tr']) : ($it['title_tr'] ?: $it['title_en']);
                    $it_date  = $it['updated_at'] ? date('d.m.Y', strtotime($it['updated_at'])) : '';
                ?>
                <a href="item-edit.php?id=<?= (int)$it['id'] ?>" class="dash-work">
                    <div class="dash-work-img">
                        <?php if (!empty($it['cover_image'])): ?>
                            <img src="<?= e(url(ltrim($it['cover_image'], '/'))) ?>" alt="" loading="lazy" onerror="this.style.visibility='hidden'">
                        <?php endif; ?>
                        <?php if ($it['status'] !== 'published'): ?>
                            <span class="dash-work-badge"><?= e(__a('Taslak', 'Draft')) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="dash-list-title"><?= e($it_title) ?></div>
                    <div class="dash-list-sub"><?= e($type_labels[$it['type']] ?? $it['type']) ?> · <?= e($it_date) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Son mesajlar -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= e(__a('Son Mesajlar', 'Recent Messages')) ?></h2>
                <a href="messages.php" class="btn btn-secondary btn-sm"><?= e(__a('Tümü', 'All')) ?> →</a>
            </div>
            <div class="dash-list">
                <?php if (!$recent_messages): ?>
                    <div class="dash-empty"><?= e(__a('Henüz mesaj yok.', 'No messages yet.')) ?></div>
                <?php endif; ?>
                <?php foreach ($recent_messages as $m): ?>
                <a href="messages.php" class="dash-list-item">
                    <span class="<?= (int)$m['is_read'] === 0 ? 'dash-unread-dot' : 'dash-read-dot' ?>"></span>
                    <div class="dash-list-main">
                        <div class="dash-list-title"><?= e($m['name']) ?><?= $m['subject'] ? ' · ' . e($m['subject']) : '' ?></div>
                        <div class="dash-list-sub"><?= e(mb_substr(trim((string)$m['message']), 0, 90)) ?></div>
                    </div>
                    <span class="dash-list-meta"><?= e($fmt_msg_time((string)$m['created_at'])) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="dash-col">
        <!-- Site durumu -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= e(__a('Site Durumu', 'Site Status')) ?></h2>
                <a href="settings.php" class="btn btn-secondary btn-sm"><?= e(__a('Ayarlar', 'Settings')) ?> →</a>
            </div>
            <div class="dash-list">
                <?php foreach ($status_rows as $row):
                    $warn = ($row['on'] && !empty($row['warn_when_on'])) || (!$row['on'] && !empty($row['warn_when_off']));
                    $cls  = $warn ? 'is-warn' : ($row['on'] ? 'is-on' : 'is-off');
                ?>
                <a href="<?= e($row['href']) ?>" class="dash-list-item">
                    <div class="dash-list-main"><div class="dash-list-title"><?= e($row['label']) ?></div></div>
                    <span class="status-pill <?= $cls ?>"><?= e($row['on'] ? $row['on_t'] : $row['off_t']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($ga && !empty($ga['cached_at_fmt'])): ?>
<p class="form-hint" style="margin-top:16px;"><?= e(__a('Ziyaretçi sayısı son güncelleme:', 'Visitor count last updated:')) ?> <?= e($ga['cached_at_fmt']) ?> · <a href="stats.php" style="color:var(--accent-blue);"><?= e(__a('İstatistikler', 'Statistics')) ?></a></p>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
