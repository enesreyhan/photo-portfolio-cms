<?php
/**
 * admin/portfolio.php
 * Birleşik Portfolyo Yönetim Listesi (Projeler, Seriler, Tekil Fotoğraflar)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();

// Aktif Kategori Filtresi (?cat=all | project | series | single vb.)
$active_cat = trim($_GET['cat'] ?? 'all');

// Kayıtlı Kategorileri Çek
$categories = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);

// Kategori bazlı sayaçları hesapla
$cat_counts = [];
$total_count = 0;
$count_rows = $pdo->query("SELECT `type`, COUNT(*) as cnt FROM `items` WHERE `type` NOT IN ('page', 'blog') GROUP BY `type`")->fetchAll(PDO::FETCH_ASSOC);
foreach ($count_rows as $r) {
    $cat_counts[$r['type']] = (int)$r['cnt'];
    $total_count += (int)$r['cnt'];
}

// İçerikleri Çek (Aktif kategoriye göre veya tümü)
if ($active_cat !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM `items` WHERE `type` = ? ORDER BY `weight` ASC, `date` DESC");
    $stmt->execute([$active_cat]);
} else {
    $stmt = $pdo->query("SELECT * FROM `items` WHERE `type` NOT IN ('page', 'blog') ORDER BY `weight` ASC, `date` DESC");
}
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategori Başlık ve Renk Haritası
$cat_meta = [];
foreach ($categories as $c) {
    $cat_meta[$c['key_slug']] = $c;
}

$admin_title = __a('menu_portfolio');
$breadcrumb_root = __a('bc_portfolio');
$breadcrumb_page = ($active_cat !== 'all' && isset($cat_meta[$active_cat])) ? get_category_localized_name($cat_meta[$active_cat], $admin_lang) : __a('all_works', 'Tüm Çalışmalar');

require __DIR__ . '/includes/header.php';
?>

<!-- ── Sayfa Başlık Bandı ── -->
<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('menu_portfolio')) ?></h1>
        <p class="page-header-sub"><?= e(__a('portfolio_page_sub')) ?></p>
    </div>
    <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <a href="item-edit.php<?= $active_cat !== 'all' ? '?type=' . urlencode($active_cat) : '' ?>" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span><?= e(__a('menu_add_work')) ?></span>
        </a>
    </div>
</div>

<!-- ── Üst Kategori Sekmeleri / Filtre Çubuğu ── -->
<div class="portfolio-filter-bar">
    <div class="portfolio-tabs">
        <a href="portfolio.php?cat=all" class="portfolio-tab-btn <?= $active_cat === 'all' ? 'active' : '' ?>">
            <span><?= e(__a('all_works', 'Tümü')) ?></span>
            <span class="tab-badge"><?= $total_count ?></span>
        </a>
        <?php foreach ($categories as $cat): 
            $c_key = $cat['key_slug'];
            $c_cnt = $cat_counts[$c_key] ?? 0;
            $c_name = get_category_localized_name($cat, $admin_lang);
        ?>
        <a href="portfolio.php?cat=<?= urlencode($c_key) ?>" class="portfolio-tab-btn <?= $active_cat === $c_key ? 'active' : '' ?>">
            <span><?= e($c_name) ?></span>
            <span class="tab-badge"><?= $c_cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
.portfolio-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    gap: 12px;
    flex-wrap: wrap;
}

#portfolioTable tbody tr[data-id] { cursor: pointer; }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg>
            <span><?= e(__a('registered_works', 'Kayıtlı Çalışmalar')) ?> (<?= count($items) ?>)</span>
        </h2>
        <div style="display:flex; align-items:center; gap:8px;">
            <div class="sidebar-search" style="padding:5px 10px; width:220px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" placeholder="<?= e(__a('search_works', 'Çalışmalarda ara...')) ?>" style="font-size:12px;" onkeyup="filterPortfolioTable(this.value)">
            </div>
            <?php lang_missing_filter('#portfolioTable tbody tr[data-id]'); ?>
            <span style="font-size:12px; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                <span><?= e(__a('drag_to_reorder', 'Sıralamak için satırları sürükleyin')) ?></span>
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table" id="portfolioTable" data-reorder="true" data-m="portfolio">
            <thead>
                <tr>
                    <th style="width:52px; text-align:center;"><?= e(__a('order')) ?></th>
                    <th style="width:58px;"><?= e(__a('cover_image', 'Kapak')) ?></th>
                    <th><?= e(__a('title')) ?></th>
                    <th style="width:110px;"><?= e(__a('category')) ?></th>
                    <th style="width:85px;"><?= e(__a('showcase_photos_count', 'Fotoğraf')) ?></th>
                    <th style="width:90px;"><?= e(__a('status')) ?></th>
                    <th style="text-align:right; width:130px;"><?= e(__a('actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:var(--text-sub);">
                            <?= e(__a('search_empty', 'Bu kategoride henüz çalışma bulunmuyor.')) ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($items as $idx => $it):
                    $thumb_src = get_thumbnail_url($it['cover_image'], 320);
                    $edit_url = 'item-edit.php?id=' . $it['id'];
                    $t = $it['type'];
                    $cat_info = $cat_meta[$t] ?? ['key_slug' => $t, 'name_tr' => ucfirst($t), 'name_en' => ucfirst($t)];
                    $badge_class = 'badge-neutral';
                    if ($t === 'project') $badge_class = 'badge-info';
                    elseif ($t === 'series') $badge_class = 'badge-violet';
                    elseif ($t === 'single') $badge_class = 'badge-success';
                    $cat_title = get_category_localized_name($cat_info, $admin_lang);
                ?>
                <tr data-id="<?= $it['id'] ?>" data-missing="<?= e(implode(' ', lang_missing($it, ['title', 'description', 'location', 'content']))) ?>">
                    <td class="drag-handle-cell drag-handle" title="<?= e(__a('reorder_drag')) ?>">
                        <div style="display:inline-flex; align-items:center; gap:5px;">
                            <svg class="drag-grip-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                            <span class="weight-label" style="font-family:var(--font-mono); font-weight:700; color:var(--text-muted); font-size:12px;"><?= $idx + 1 ?></span>
                        </div>
                    </td>
                    <td style="width:68px;">
                        <a href="<?= e($edit_url) ?>" style="display:block; width:56px; height:38px; border-radius:6px; overflow:hidden; background:var(--bg-canvas-subtle); border:1px solid var(--border-subtle); flex-shrink:0;">
                            <img src="<?= e($thumb_src) ?>" width="56" height="38" loading="lazy" decoding="async" alt="" style="width:56px !important; height:38px !important; max-width:56px !important; max-height:38px !important; object-fit:cover; display:block;" />
                        </a>
                    </td>
                    <td>
                        <a href="<?= e($edit_url) ?>" style="color:var(--text-main); font-weight:600; text-decoration:none; display:block; font-size:14px;">
                            <?= e(item_field($it, 'title', $admin_lang) ?: ($admin_lang === 'tr' ? ($it['title_tr'] ?: $it['title_en']) : ($it['title_en'] ?: $it['title_tr']))) ?>
                        </a>
                        <div style="font-size:12px; color:var(--text-sub); margin-top:2px; font-family:var(--font-mono);">
                            <?= e($it['slug_' . $admin_lang] ?? ($it['slug_en'] ?: $it['slug'])) ?>
                        </div>
                        <?= lang_status_chips($it, ['title', 'description', 'location', 'content']) ?>
                    </td>
                    <td>
                        <span class="badge <?= $badge_class ?>">
                            <?= e($cat_title) ?>
                        </span>
                    </td>
                    <td style="font-family:var(--font-mono); font-size:12px;"><?= $it['photo_count'] ?></td>
                    <td>
                        <span class="badge <?= $it['status'] === 'published' ? 'badge-success' : 'badge-neutral' ?>">
                            <?= $it['status'] === 'published' ? e(__a('status_published')) : e(__a('status_draft')) ?>
                        </span>
                    </td>
                    <td style="text-align:right; white-space:nowrap;">
                        <a href="<?= e($edit_url) ?>" class="btn btn-secondary btn-sm" style="padding:5px 10px;"><?= e(__a('edit')) ?></a>
                        <details class="row-menu">
                            <summary class="btn btn-secondary btn-sm" aria-label="<?= e(__a('actions')) ?>">⋯</summary>
                            <div class="row-menu-list">
                                <a href="<?= e(item_url($it, $admin_lang)) ?>" target="_blank"><?= e(__a('topbar_view_site')) ?> (<?= strtoupper($admin_lang) ?>)</a>
                                <a href="item-delete.php?id=<?= $it['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="<?= e(sprintf(__a('“%s” silinsin mi? Sayfası ve metinleri silinir; fotoğraf dosyaları medya kütüphanesinde kalır.', 'Delete “%s”? Its page and texts are deleted; the photo files stay in the media library.'), item_field($it, 'title', $admin_lang))) ?>" class="is-danger"><?= e(__a('delete')) ?></a>
                            </div>
                        </details>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterPortfolioTable(query) {
    var q = (query || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#portfolioTable tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
    });
}
// Satırın boş bir yerine tıklamak düzenlemeyi açar; link, buton, menü ve sürükleme tutamacı hariç.
document.querySelector('#portfolioTable tbody').addEventListener('click', function(e) {
    var row = e.target.closest('tr[data-id]');
    if (!row || e.target.closest('a, button, details, .drag-handle, input')) return;
    location.href = 'item-edit.php?id=' + row.getAttribute('data-id');
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
