<?php
/**
 * admin/media.php
 * Gelişmiş Klasör Tabanlı Medya Kütüphanesi, Coğrafi Harita & EXIF Analizi
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/media_metadata.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$base_dir = dirname(__DIR__);

// Silme işlemi
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    $del_id = (int)$_POST['delete'];
    $stmt = $pdo->prepare("SELECT * FROM `media` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$del_id]);
    $m = $stmt->fetch();
    if ($m) {
        @unlink($base_dir . '/' . $m['file_path']);
        if ($m['avif_path']) @unlink($base_dir . '/' . $m['avif_path']);
        if ($m['webp_path']) @unlink($base_dir . '/' . $m['webp_path']);
        $thumb = preg_replace('/(\.[a-z0-9]+)$/i', '_thumb.webp', $base_dir . '/' . $m['file_path']);
        @unlink($thumb);

        // processed_images klasöründeki türevleri de temizle
        $stem = pathinfo($m['filename'], PATHINFO_FILENAME);
        $proc_matches = glob($base_dir . '/processed_images/' . $stem . '*');
        if ($proc_matches) {
            foreach ($proc_matches as $pm) {
                @unlink($pm);
            }
        }

        $pdo->prepare("DELETE FROM `media` WHERE `id` = ?")->execute([$del_id]);
        set_flash('success', 'Görsel başarıyla silindi.');
    }
    $redir_folder = urlencode($_POST['folder'] ?? '');
    header('Location: media.php' . ($redir_folder ? '?folder=' . $redir_folder : ''));
    exit;
}

$google_maps_api_key = get_setting('google_maps_api_key', '');

$admin_title = __a('menu_media');
$breadcrumb_root = __a('bc_system');
$breadcrumb_page = __a('menu_media');

require __DIR__ . '/includes/header.php';
?>

<!-- Leaflet Harita Kütüphanesi -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
/* ── Yerel Değişken Eşleştirmeleri ── */
.media-top-tabs,
.media-filter-bar,
.media-container,
#tabMapContainer,
#imageDrawer {
    --border-color: var(--border-subtle);
    --bg-card: var(--bg-surface);
    --bg-body: var(--bg-canvas-subtle);
}

/* ── Gelişmiş Filtre Çubuğu & Belirgin Arama ── */
.media-filter-bar {
    background: var(--bg-surface);
    border: 1px solid var(--border-card);
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    box-shadow: var(--card-shadow);
}
.search-prominent-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    background: var(--bg-surface);
    border: 1.5px solid var(--border-light);
    border-radius: 10px;
    padding: 0 14px;
    height: 46px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
}
.search-prominent-wrap:hover {
    border-color: #94a3b8;
}
.search-prominent-wrap:focus-within {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
    background: var(--bg-surface);
}
.search-prominent-icon {
    color: var(--accent-blue);
    flex-shrink: 0;
    margin-right: 10px;
    display: flex;
    align-items: center;
}
.filter-search-input {
    width: 100%;
    height: 100%;
    border: none;
    background: transparent;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-main);
    font-family: inherit;
    outline: none;
}
.filter-search-input::placeholder {
    color: var(--text-sub);
    font-weight: 400;
}
.filter-search-input::-webkit-search-cancel-button {
    display: none;
}
.search-clear-btn {
    display: none;
    align-items: center;
    justify-content: center;
    background: var(--bg-surface-2);
    border: 1px solid var(--border-subtle);
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 8px;
    flex-shrink: 0;
    transition: all 0.15s ease;
}
.search-clear-btn:hover {
    background: var(--bg-hover);
    color: var(--text-main);
}
.search-kbd-hint {
    font-family: var(--font-mono);
    font-size: 12px;
    font-weight: 600;
    color: var(--text-sub);
    background: var(--bg-surface-2);
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    padding: 2px 8px;
    margin-left: 8px;
    flex-shrink: 0;
    user-select: none;
}
.filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.filter-select {
    height: 36px;
    padding: 6px 10px;
    font-size: 13px;
    border-radius: 6px;
    border: 1px solid var(--border-light);
    background: var(--bg-surface);
    color: var(--text-main);
    cursor: pointer;
    max-width: 175px;
    transition: border-color 0.15s;
}
.filter-select:hover {
    border-color: #94a3b8;
}
.filter-select:focus {
    outline: none;
    border-color: #2563eb;
}
.exif-toggle-btn {
    height: 36px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid var(--border-light);
    background: var(--bg-surface);
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s;
}
.exif-toggle-btn:hover {
    background: var(--bg-hover);
    color: var(--text-main);
    border-color: #94a3b8;
}
.exif-toggle-btn.active {
    background: var(--info-soft);
    color: var(--accent-blue);
    border-color: var(--info-border);
}
.exif-filters-drawer {
    display: none;
    padding-top: 10px;
    border-top: 1px dashed var(--border-color);
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
.exif-filters-drawer.open {
    display: flex;
}
.active-filters-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
.filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    background: var(--info-soft);
    color: var(--accent-blue);
    border: 1px solid var(--info-border);
    border-radius: 10px;
    font-size: 12px;
    font-weight: 500;
}
.filter-chip-remove {
    cursor: pointer;
    font-weight: 700;
    opacity: 0.7;
}
.filter-chip-remove:hover {
    opacity: 1;
}

/* ── Medya Kütüphanesi Izgara Düzeni ── */
.media-container {
    display: grid;
    grid-template-columns: 270px 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .media-container {
        grid-template-columns: 1fr;
    }
}

/* Sol Klasör Gezgini (Açılır-Kapanır Ana Dizin Ağacı) */
.folder-tree {
    background: var(--bg-surface);
    border: 1px solid var(--border-card);
    border-radius: 10px;
    overflow: hidden;
    position: sticky;
    top: 24px;
    box-shadow: var(--card-shadow);
}
.folder-tree-header {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bg-surface-2);
    gap: 6px;
}
.folder-tree-header h3 {
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 0;
}
.folder-group {
    border-top: 1px solid var(--border-subtle);
}
.folder-group:first-of-type {
    border-top: none;
}
.folder-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted);
    padding: 9px 12px;
    margin: 2px 6px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    cursor: pointer;
    user-select: none;
    list-style: none;
    transition: background 0.15s ease, color 0.15s ease;
}
.folder-section-title::-webkit-details-marker {
    display: none;
}
.folder-section-title:hover {
    background: var(--bg-hover);
    color: var(--text-main);
}
.folder-section-left {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
    flex: 1;
}
.folder-section-right {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.folder-chevron {
    width: 13px;
    height: 13px;
    color: var(--text-sub);
    transition: transform 0.2s ease, color 0.2s ease;
    flex-shrink: 0;
}
.folder-group[open] > .folder-section-title .folder-chevron {
    transform: rotate(90deg);
    color: var(--text-main);
}
.folder-group[open] > .folder-section-title {
    color: var(--text-main);
}
.folder-cat-badge {
    font-size: 12px;
    font-weight: 600;
    background: var(--bg-canvas-subtle);
    color: var(--text-muted);
    padding: 1px 6px;
    border-radius: 999px;
    font-variant-numeric: tabular-nums;
}
.folder-list {
    list-style: none;
    padding: 2px 6px 8px 6px;
    margin: 0 0 0 14px;
    border-left: 1.5px solid var(--border-subtle);
}
.folder-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 7px 10px;
    border-radius: 6px;
    font-size: 12px;
    color: var(--text-muted);
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    user-select: none;
    min-width: 0;
}
.folder-item:hover {
    background: var(--bg-hover);
    color: var(--text-main);
}
.folder-item.active {
    background: var(--info-soft);
    color: var(--accent-blue);
    font-weight: 600;
}
.folder-item.active svg {
    color: var(--accent-blue);
}
.folder-item.active .folder-badge {
    background: var(--info-soft);
    color: var(--accent-blue);
}
.folder-item-name {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.folder-item-name svg {
    flex-shrink: 0 !important;
    width: 13px !important;
    height: 13px !important;
}
.folder-item-name span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
    flex: 1;
}
.folder-badge {
    font-size: 12px;
    background: var(--bg-body);
    padding: 2px 6px;
    border-radius: 6px;
    color: var(--text-sub);
    font-variant-numeric: tabular-nums;
    flex-shrink: 0 !important;
}

/* Izgara & Kartlar */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 16px;
}
.media-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    position: relative;
}
.media-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    border-color: #94a3b8;
}
.media-thumb-wrap {
    width: 100%;
    aspect-ratio: 3/2;
    background: #0f172a;
    overflow: hidden;
    position: relative;
    cursor: pointer;
}
.media-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.25s ease;
}
.media-card:hover .media-thumb-img {
    transform: scale(1.04);
}
.media-badges {
    position: absolute;
    top: 6px;
    left: 6px;
    display: flex;
    gap: 4px;
    z-index: 2;
}
.media-badge-pill {
    font-size: 12px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 6px;
    letter-spacing: 0.04em;
    backdrop-filter: blur(6px);
}
.badge-loc {
    background: rgba(0, 0, 0, 0.75);
    color: #ffffff;
}
.badge-cat-p {
    background: rgba(37, 99, 235, 0.9);
    color: #ffffff;
}
.badge-cat-s {
    background: rgba(59, 130, 246, 0.9);
    color: #fff;
}
.badge-cat-single {
    background: rgba(16, 185, 129, 0.9);
    color: #fff;
}
.media-info {
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}
.media-name {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.media-meta-line {
    font-size: 12px;
    color: var(--text-sub);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.media-exif-pill {
    font-size: 12px;
    color: var(--text-sub);
    font-family: 'JetBrains Mono', monospace;
    background: var(--bg-card);
    padding: 2px 6px;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.media-actions {
    display: flex;
    gap: 6px;
    margin-top: 6px;
    padding-top: 8px;
    border-top: 1px solid var(--border-color);
}

/* ── Harita Görünümü ── */
#tabMapContainer {
    display: none;
    grid-template-columns: 1fr 320px;
    gap: 20px;
    align-items: stretch;
}
@media (max-width: 1024px) {
    #tabMapContainer {
        grid-template-columns: 1fr;
    }
}
#worldMap {
    height: 680px;
    width: 100%;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    background: #1e293b;
}
.map-sidebar-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 680px;
    overflow-y: auto;
}
.map-stat-box {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.map-stat-num {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 12px;
    text-align: center;
}
.map-stat-num h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary);
    margin: 0;
}
.map-stat-num p {
    font-size: 12px;
    color: var(--text-sub);
    margin: 4px 0 0 0;
}
.map-loc-item {
    padding: 8px 12px;
    border-radius: 6px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 12px;
}
.map-loc-item:hover {
    border-color: #2563eb;
    background: var(--info-soft);
    transform: translateX(2px);
}

/* Harita Markerları */
.custom-photo-pin {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    color: #ffffff;
    font-size: 12px;
    font-weight: 800;
    border-radius: 50%;
    border: 2px solid #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease;
}
.custom-photo-pin:hover {
    transform: scale(1.15);
    background: #2563eb;
}

/* ── Sağ Slide-out Inspector Drawer (Detay Çekmecesi) ── */
#imageDrawer {
    position: fixed;
    top: 0;
    right: -460px;
    width: 440px;
    height: 100vh;
    background: var(--bg-surface);
    border-left: 1px solid var(--border-color);
    box-shadow: -12px 0 40px rgba(0,0,0,0.5);
    z-index: 1050;
    transition: right 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}
#imageDrawer.open {
    right: 0;
}
.drawer-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bg-card);
}
.drawer-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    flex: 1;
}
.drawer-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-sub);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.exif-metrics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.exif-metric-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 8px 12px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.exif-metric-card .label {
    font-size: 12px;
    color: var(--text-sub);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.exif-metric-card .val {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-main);
    font-family: 'JetBrains Mono', monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.variant-row {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-size: 12px;
}
.variant-badge {
    font-size: 12px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 6px;
}
.badge-orig { background: #3b82f6; color: #fff; }
.badge-avif { background: #10b981; color: #fff; }
.badge-webp { background: #2563eb; color: #fff; }
</style>

<!-- ── Sayfa Başlık Bandı ── -->
<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('media_page_title')) ?></h1>
        <p class="page-header-sub"><?= e(__a('media_page_sub')) ?></p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" onclick="document.getElementById('mainFileInput').click()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
            <span><?= e(__a('media_modal_upload_btn', '+ Yeni Fotoğraf Yükle')) ?></span>
        </button>
    </div>
</div>

<!-- ── Görünüm Sekmeleri (Izgara & Harita) ── -->
<div class="media-top-tabs"<?= field_map_enabled() ? '' : ' style="display:none"' ?>><?php // Harita sekmesi siteye özel dosya adı kuralına dayanır ?>
    <button type="button" class="media-tab-btn active" id="btnTabGrid" onclick="switchView('grid')">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
        <span><?= __a('Fotoğraf Izgarası', 'Photo Grid') ?></span>
        <span class="media-tab-badge" id="tabBadgePhotos">0</span>
    </button>
    <button type="button" class="media-tab-btn" id="btnTabMap" onclick="switchView('map')">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        <span><?= __a('Dünya Haritası', 'World Map') ?></span>
        <span class="media-tab-badge" id="tabBadgeCities"><?= __a('0 Şehir', '0 Cities') ?></span>
    </button>
</div>

<!-- ── GELİŞMİŞ FİLTRELEME & BELİRGİN ARAMA ÇUBUĞU ── -->
<search class="media-filter-bar">
    <!-- Belirgin Arama Satırı -->
    <div class="search-prominent-wrap" onclick="document.getElementById('mediaSearchInput').focus()">
        <span class="search-prominent-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </span>
        <input type="search" id="mediaSearchInput" placeholder="<?= __a('Fotoğraf adı, şehir, ülke, proje kodu veya kamera modeli ile arşivde ara...', 'Search archive by photo name, city, country, project code or camera model...') ?>" class="filter-search-input" oninput="handleSearch()" aria-label="<?= e(__a('Medya kütüphanesinde ara', 'Search media library')) ?>">
        <button type="button" id="btnSearchClear" class="search-clear-btn" onclick="event.stopPropagation(); clearSearchInput()"><?= __a('Temizle', 'Clear') ?> &times;</button>
        <kbd class="search-kbd-hint" title="<?= __a('Hızlı arama için / tuşuna basın', 'Press / for quick search') ?>">/</kbd>
    </div>

    <button type="button" class="btn btn-secondary btn-sm media-filter-toggle" aria-expanded="false" aria-controls="mediaFilterRow" onclick="var b=this.closest('.media-filter-bar'); var o=b.classList.toggle('is-open'); this.setAttribute('aria-expanded', o)"><?= __a('Filtrele', 'Filter') ?></button>
    <div class="filter-row" id="mediaFilterRow">
        <!-- Ülke Seçici -->
        <select id="filterCountry" class="filter-select" onchange="handleFilterChange('country')">
            <option value="all"><?= __a('Tüm Ülkeler', 'All Countries') ?></option>
        </select>

        <!-- Şehir Seçici -->
        <select id="filterCity" class="filter-select" onchange="handleFilterChange('city')">
            <option value="all"><?= __a('Tüm Şehirler', 'All Cities') ?></option>
        </select>

        <!-- Tür / Kategori -->
        <select id="filterType" class="filter-select" onchange="handleFilterChange('type')">
            <option value="all"><?= __a('Tüm Kategoriler', 'All Categories') ?></option>
            <?php
            $all_cats_stmt = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC");
            if ($all_cats_stmt) {
                foreach ($all_cats_stmt->fetchAll(PDO::FETCH_ASSOC) as $cc) {
                    $cat_display = get_category_localized_name($cc, $admin_lang);
                    echo '<option value="' . e($cc['key_slug']) . '">' . e($cat_display) . '</option>';
                }
            }
            ?>
        </select>

        <!-- Proje / Seri Adı -->
        <select id="filterProject" class="filter-select" onchange="handleFilterChange('project')">
            <option value="all"><?= __a('Tüm Projeler & Seriler', 'All Projects & Series') ?></option>
        </select>

        <!-- Yıl -->
        <select id="filterYear" class="filter-select" style="max-width:110px;" onchange="handleFilterChange('year')">
            <option value="all"><?= __a('Tüm Yıllar', 'All Years') ?></option>
        </select>

        <!-- Sıralama -->
        <select id="sortSelect" class="filter-select" style="max-width:140px;" onchange="handleFilterChange('sort')">
            <option value="newest"><?= __a('En Yeni Yüklenen', 'Newest Uploads') ?></option>
            <option value="oldest"><?= __a('En Eski Yüklenen', 'Oldest Uploads') ?></option>
            <option value="shot_date_desc"><?= __a('Çekim Tarihi (Yeni → Eski)', 'Shot Date (Newest First)') ?></option>
            <option value="shot_date_asc"><?= __a('Çekim Tarihi (Eski → Yeni)', 'Shot Date (Oldest First)') ?></option>
            <option value="name"><?= __a('İsme Göre (A-Z)', 'By Name (A-Z)') ?></option>
            <option value="size"><?= __a('Boyuta Göre', 'By Size') ?></option>
        </select>

        <!-- EXIF Filtre Butonu -->
        <button type="button" class="exif-toggle-btn" id="btnExifToggle" onclick="toggleExifDrawer()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            <span><?= __a('EXIF Filtreleri', 'EXIF Filters') ?></span>
            <span id="exifFilterCount" style="display:none; background:var(--primary); color:#fff; border-radius:10px; padding:1px 6px; font-size:12px; font-weight:700;">0</span>
        </button>

        <!-- Filtreleri Temizle -->
        <button type="button" id="btnClearFilters" class="btn btn-secondary btn-sm" onclick="clearAllFilters()" style="display:none; height:36px; padding:6px 12px;">
            <span><?= __a('Temizle', 'Clear') ?></span>
        </button>
    </div>

    <!-- ── EXIF Detay Filtreleri (Açılır Çekmece) ── -->
    <div class="exif-filters-drawer" id="exifDrawer">
        <!-- Kamera -->
        <select id="filterCamera" class="filter-select" onchange="handleFilterChange('camera')">
            <option value="all"><?= __a('Tüm Kameralar', 'All Cameras') ?></option>
        </select>

        <!-- Diyafram -->
        <select id="filterAperture" class="filter-select" onchange="handleFilterChange('aperture')">
            <option value="all"><?= __a('Tüm Diyaframlar', 'All Apertures') ?></option>
            <option value="f1.4-f2.0"><?= __a('ƒ/1.4 - ƒ/2.0 (Açık)', 'ƒ/1.4 - ƒ/2.0 (Wide Open)') ?></option>
            <option value="f2.8">ƒ/2.8</option>
            <option value="f4.0">ƒ/4.0</option>
            <option value="f5.6">ƒ/5.6</option>
            <option value="f8.0">ƒ/8.0</option>
            <option value="f11+"><?= __a('ƒ/11 ve üzeri (Kapalı)', 'ƒ/11 and above (Closed)') ?></option>
        </select>

        <!-- Enstantane -->
        <select id="filterShutter" class="filter-select" onchange="handleFilterChange('shutter')">
            <option value="all"><?= __a('Tüm Enstantaneler', 'All Shutter Speeds') ?></option>
            <option value="fast"><?= __a('1/1000s ve Hızlı', '1/1000s and Faster') ?></option>
            <option value="mid-fast">1/250s - 1/800s</option>
            <option value="mid">1/60s - 1/200s</option>
            <option value="slow"><?= __a('1/50s ve Yavaş', '1/50s and Slower') ?></option>
        </select>

        <!-- ISO -->
        <select id="filterIso" class="filter-select" onchange="handleFilterChange('iso')">
            <option value="all"><?= __a('Tüm ISO Değerleri', 'All ISO Values') ?></option>
            <option value="100"><?= __a('ISO 100 ve altı', 'ISO 100 and below') ?></option>
            <option value="200">ISO 200</option>
            <option value="400">ISO 400</option>
            <option value="800">ISO 800</option>
            <option value="1600">ISO 1600</option>
            <option value="3200+"><?= __a('ISO 3200 ve üzeri', 'ISO 3200 and above') ?></option>
        </select>

        <span style="font-size:12px; color:var(--text-sub); margin-left:auto;">
            <?= __a('Diyafram, enstantane, ISO ve kamera modeline göre anlık arşiv taraması.', 'Instant archive search by aperture, shutter speed, ISO and camera model.') ?>
        </span>
    </div>

    <!-- Aktif Filtre Rozetleri -->
    <div class="active-filters-chips" id="activeFilterChips" style="display:none;"></div>
</search>

<!-- ── 1. GÖRÜNÜM: FOTOĞRAF IZGARASI (GRID VIEW) ── -->
<div id="tabGridContainer" class="media-container">
    <!-- Sol Klasör Gezgini -->
    <div class="folder-tree">
        <div class="folder-tree-header">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                <span><?= __a('Klasörler', 'Folders') ?></span>
            </h3>
            <div style="display:flex; align-items:center; gap:4px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllFolderGroups()" title="<?= __a('Tüm Ana Dizinleri Aç / Kapat', 'Expand / Collapse All Folders') ?>" style="padding:3px 7px; font-size:12px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="promptNewFolder()" title="<?= __a('Yeni Klasör Oluştur', 'Create New Folder') ?>" style="padding:3px 8px; font-size:12px;">
                    <?= __a('+ Yeni', '+ New') ?>
                </button>
            </div>
        </div>

        <div style="padding:6px 6px 4px 6px;">
            <div class="folder-item active" id="folder_all" onclick="selectFolder('all')">
                <span class="folder-item-name">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    <span><?= __a('Tüm Görseller', 'All Images') ?></span>
                </span>
                <span class="folder-badge" id="badge_total">...</span>
            </div>
        </div>

        <!-- Dinamik Kategoriler (Veritabanındaki categories tablosuna göre) -->
        <div id="custom_cat_folders"></div>

        <!-- Diğer / Yüklenenler (Açılır-Kapanır Ana Dizin) -->
        <details class="folder-group" id="group_other" data-group-key="other" open ontoggle="saveFolderGroupState('other', this.open)">
            <summary class="folder-section-title">
                <span class="folder-section-left">
                    <svg class="folder-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    <span><?= __a('Diğer Klasörler', 'Other Folders') ?></span>
                </span>
                <span class="folder-section-right">
                    <span class="folder-cat-badge" id="badge_cat_other">0</span>
                </span>
            </summary>
            <ul class="folder-list" id="list_other"></ul>
        </details>
            <ul class="folder-list" id="list_other"></ul>
        </details>
    </div>

    <!-- Sağ Galeri & Yükleme -->
    <div>
        <!-- Sürükle-Bırak Yükleme Alanı -->
        <div id="dropZone" style="border:2px dashed var(--border-light); border-radius:10px; padding:20px; text-align:center; background:rgba(255,255,255,0.01); cursor:pointer; margin-bottom:20px; transition:all 0.15s;">
            <div style="font-size:13px; font-weight:600; color:var(--text-main); display:flex; align-items:center; justify-content:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                <span id="dropZoneTitle"><?= __a('Seçili Klasöre Fotoğraf Yükleyin (Sürükle & Bırak)', 'Upload Photos to Selected Folder (Drag & Drop)') ?></span>
            </div>
            <div style="font-size:12px; color:var(--text-sub); margin-top:4px;">
                <?= __a('Yüklenen fotoğraflar otomatik olarak çözümlenir, EXIF bilgileri kaydedilir ve', 'Uploaded photos are automatically analyzed, EXIF data saved, and converted to') ?> <strong>AVIF & WebP</strong> <?= __a('formatlarına dönüştürülür.', 'formats.') ?>
            </div>
        </div>

        <!-- Yükleme İlerleme Durumu -->
        <div id="uploadProgressBox" style="display:none; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:10px; padding:16px; margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; font-weight:600; margin-bottom:8px;">
                <span id="uploadProgressText"><?= __a('Görseller yükleniyor ve dönüştürülüyor...', 'Photos uploading and converting...') ?></span>
                <span id="uploadProgressCount">0 / 0</span>
            </div>
            <div style="background:var(--bg-body); border-radius:6px; height:8px; overflow:hidden;">
                <div id="uploadProgressBar" style="width:0%; height:100%; background:var(--primary); transition:width 0.2s;"></div>
            </div>
        </div>

        <input type="file" id="mainFileInput" multiple accept="image/*" style="display:none;" onchange="uploadFiles(this.files)">

        <!-- Durum ve Sonuç Özeti -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; font-size:12px; color:var(--text-sub);">
            <div id="folderSubtitle"><?= __a('Fotoğraflar listeleniyor...', 'Listing photos...') ?></div>
            <div id="filterResultCount"></div>
        </div>

        <!-- Medya Izgarası -->
        <div id="mediaGrid" class="media-grid">
            <div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:var(--text-sub);">
                <?= __a('Yükleniyor...', 'Loading...') ?>
            </div>
        </div>
        <div id="mediaLoadMore" hidden style="text-align:center; margin:20px 0;">
            <button type="button" class="btn btn-secondary" onclick="mediaLimit += 600; loadMedia();"><?= e(__a('Daha fazla göster', 'Show more')) ?></button>
        </div>
    </div>
</div>

<!-- ── 2. GÖRÜNÜM: DÜNYA HARİTASI (WORLD MAP) ── -->
<div id="tabMapContainer">
    <div id="worldMap"></div>

    <div class="map-sidebar-card">
        <div>
            <h3 style="font-size:16px; font-weight:700; margin:0 0 4px 0;"><?= __a('Coğrafi Çekim Haritası', 'Geographic Photo Map') ?></h3>
            <p style="font-size:12px; color:var(--text-sub); margin:0;"><?= __a('Dünya genelinde belgelenen lokasyonlar ve fotoğraf sayıları.', 'Documented locations worldwide and photo counts.') ?></p>
        </div>

        <div class="map-stat-box">
            <div class="map-stat-num">
                <h4 id="mapStatCountries">0</h4>
                <p><?= __a('Ülke', 'Countries') ?></p>
            </div>
            <div class="map-stat-num">
                <h4 id="mapStatCities">0</h4>
                <p><?= __a('Şehir / Konum', 'City / Location') ?></p>
            </div>
        </div>

        <div>
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-sub); margin-bottom:8px;">
                <?= __a('Lokasyon Listesi', 'Location List') ?>
            </div>
            <div id="mapCitiesList" style="display:flex; flex-direction:column; gap:6px; max-height:420px; overflow-y:auto;">
                <!-- Dinamik lokasyonlar -->
            </div>
        </div>
    </div>
</div>

<!-- ── SAĞ ÇEKMECE / DETAY GÖRÜNTÜLEYİCİ (INSPECTOR) ── -->
<div id="imageDrawer">
    <div class="drawer-header">
        <div>
            <h4 style="font-size:13px; font-weight:600; margin:0;" id="drawerTitle"><?= __a('Fotoğraf Detayları', 'Photo Details') ?></h4>
            <span style="font-size:12px; color:var(--text-sub);" id="drawerDate"></span>
        </div>
        <button type="button" onclick="closeDrawer()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:22px; line-height:1;">&times;</button>
    </div>

    <div class="drawer-body">
        <!-- Büyük Önizleme -->
        <div style="width:100%; height:230px; background:#0f172a; border:1px solid var(--border-color); border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center; position:relative;">
            <img id="drawerImage" src="" style="max-width:100%; max-height:100%; object-fit:contain;" />
            <div id="drawerBadgeOverlay" style="position:absolute; bottom:8px; left:8px; display:flex; gap:6px;"></div>
        </div>

        <!-- 1. BÖLÜM: Fotoğraf Künyesi (İsimlendirme Standardı) -->
        <div>
            <div class="drawer-section-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span><?= __a('Fotoğraf Künyesi & İsimlendirme', 'Photo Colophon & Naming') ?></span>
            </div>
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:6px; padding:12px; display:flex; flex-direction:column; gap:8px;">
                <div style="display:flex; justify-content:space-between; font-size:12px;">
                    <span style="color:var(--text-sub);"><?= __a('Eser Sahibi:', 'Author:') ?></span>
                    <strong id="drawerAuthor"><?= e(site_owner_name()) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:12px;">
                    <span style="color:var(--text-sub);"><?= __a('Konum:', 'Location:') ?></span>
                    <strong id="drawerLocation">-</strong>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:12px;">
                    <span style="color:var(--text-sub);"><?= __a('Proje / Seri:', 'Project / Series:') ?></span>
                    <strong id="drawerProject" style="color:var(--primary);">-</strong>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:12px;">
                    <span style="color:var(--text-sub);"><?= __a('Dosya Adı:', 'Filename:') ?></span>
                    <span id="drawerFilename" style="font-family:'JetBrains Mono', monospace; font-size:12px;">-</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:12px;">
                    <span style="color:var(--text-sub);"><?= __a('Klasör Yolu:', 'Folder Path:') ?></span>
                    <span id="drawerPath" style="font-family:'JetBrains Mono', monospace; font-size:12px; max-width:240px; overflow:hidden; text-overflow:ellipsis;">-</span>
                </div>
            </div>
        </div>

        <!-- 2. BÖLÜM: EXIF Çekim Verileri -->
        <div>
            <div class="drawer-section-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <span><?= __a('EXIF Çekim Verileri', 'EXIF Shot Data') ?></span>
            </div>
            <div class="exif-metrics-grid">
                <div class="exif-metric-card">
                    <span class="label"><?= __a('Kamera', 'Camera') ?></span>
                    <span class="val" id="drawerCamera">-</span>
                </div>
                <div class="exif-metric-card">
                    <span class="label"><?= __a('Lens', 'Lens') ?></span>
                    <span class="val" id="drawerLens">-</span>
                </div>
                <div class="exif-metric-card">
                    <span class="label"><?= __a('Diyafram', 'Aperture') ?></span>
                    <span class="val" id="drawerAperture" style="color:var(--primary);">-</span>
                </div>
                <div class="exif-metric-card">
                    <span class="label"><?= __a('Enstantane', 'Shutter Speed') ?></span>
                    <span class="val" id="drawerShutter">-</span>
                </div>
                <div class="exif-metric-card">
                    <span class="label"><?= __a('ISO Hassasiyeti', 'ISO Sensitivity') ?></span>
                    <span class="val" id="drawerIso">-</span>
                </div>
                <div class="exif-metric-card">
                    <span class="label"><?= __a('Odak Uzaklığı', 'Focal Length') ?></span>
                    <span class="val" id="drawerFocal">-</span>
                </div>
                <div class="exif-metric-card">
                    <span class="label"><?= __a('Çözünürlük', 'Resolution') ?></span>
                    <span class="val" id="drawerDims">-</span>
                </div>
                <div class="exif-metric-card">
                    <span class="label"><?= __a('Orijinal Boyut', 'Original Size') ?></span>
                    <span class="val" id="drawerSize">-</span>
                </div>
            </div>
        </div>

        <!-- 3. BÖLÜM: Dönüştürülmüş Formatlar (AVIF / WebP Linkleri) -->
        <div>
            <div class="drawer-section-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                <span><?= __a('Optimize Edilmiş Formatlar & Web Bağlantıları', 'Optimized Formats & Web Links') ?></span>
            </div>
            <div id="drawerVariantsList" style="display:flex; flex-direction:column; gap:8px;">
                <div style="font-size:12px; color:var(--text-sub);"><?= __a('Yükleniyor...', 'Loading...') ?></div>
            </div>
        </div>

        <!-- Alt Silme & Kapatma Butonları -->
        <div style="margin-top:auto; padding-top:16px; border-top:1px solid var(--border-color); display:flex; gap:10px;">
            <a id="drawerDeleteLink" href="#" data-post data-confirm="<?= e(__a('Bu görsel kalıcı olarak silinecek. Kullanıldığı sayfalarda görünmez olur ve bu işlem geri alınamaz.', 'This image will be deleted permanently. It disappears from any page that uses it, and this cannot be undone.')) ?>" class="btn btn-danger btn-sm" style="flex:1; justify-content:center;">
                <?= __a('Görseli Sil', 'Delete Image') ?>
            </a>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeDrawer()" style="flex:1; justify-content:center;"><?= __a('Kapat', 'Close') ?></button>
        </div>
    </div>
</div>

<script>
window.MEDIA_I18N = {
    cities: <?= json_encode(__a(' Şehir', ' Cities')) ?>,
    listing_total: <?= json_encode(__a('Toplam %d fotoğraf listeleniyor.', 'Listing total %d photos.')) ?>,
    photos_in_folder: <?= json_encode(__a('"%s" klasöründe %d fotoğraf bulunuyor.', '%d photos in folder "%s".')) ?>,
    scanning_variants: <?= json_encode(__a('Varyantlar taranıyor...', 'Scanning variants...')) ?>,
    shot_prefix: <?= json_encode(__a('Çekim: ', 'Shot: ')) ?>,
    unknown: <?= json_encode(__a('Bilinmiyor', 'Unknown')) ?>,
    single_photo: <?= json_encode(__a('Tekil Fotoğraf', 'Single Photo')) ?>,
    fixed_lens: <?= json_encode(__a('Sabit Lens', 'Fixed Lens')) ?>,
    no_variants: <?= json_encode(__a('Optimize varyant bulunamadı.', 'No optimized variants found.')) ?>,
    open_btn: <?= json_encode(__a('Aç ↗', 'Open ↗')) ?>,
    no_folders_yet: <?= json_encode(__a('Henüz klasör yok', 'No folders yet')) ?>
};
var activeFolder = '<?= e($_GET['folder'] ?? 'all') ?>';
var activeView = 'grid'; // 'grid' veya 'map'
var googleMapsApiKey = <?= json_encode($google_maps_api_key) ?>;
var allImages = [];
var currentFilteredImages = [];
var mapLocations = [];
var searchTimer = null;
var leafletMap = null;
var mapMarkers = [];

document.addEventListener('DOMContentLoaded', function() {
    loadMedia();
    setupDropZone();
});

// ── Görünüm Değiştirici (Izgara vs Harita) ──
function switchView(view) {
    activeView = view;
    var btnGrid = document.getElementById('btnTabGrid');
    var btnMap  = document.getElementById('btnTabMap');
    var gridCont = document.getElementById('tabGridContainer');
    var mapCont  = document.getElementById('tabMapContainer');

    if (view === 'grid') {
        btnGrid.classList.add('active');
        btnMap.classList.remove('active');
        gridCont.style.display = 'grid';
        mapCont.style.display = 'none';
    } else {
        btnMap.classList.add('active');
        btnGrid.classList.remove('active');
        gridCont.style.display = 'none';
        mapCont.style.display = 'grid';

        setTimeout(initOrUpdateMap, 100);
    }
}

// ── API'den Medya Verilerini Yükleme ──
var mediaLimit = 600;
function loadMedia() {
    var sort     = document.getElementById('sortSelect').value;
    var q        = document.getElementById('mediaSearchInput').value;
    var country  = document.getElementById('filterCountry').value;
    var city     = document.getElementById('filterCity').value;
    var type     = document.getElementById('filterType').value;
    var project  = document.getElementById('filterProject').value;
    var year     = document.getElementById('filterYear').value;
    var camera   = document.getElementById('filterCamera').value;
    var aperture = document.getElementById('filterAperture').value;
    var shutter  = document.getElementById('filterShutter').value;
    var iso      = document.getElementById('filterIso').value;

    var params = new URLSearchParams();
    params.append('admin_lang', window.ADMIN_LANG || '<?= e($admin_lang) ?>');
    if (activeFolder && activeFolder !== 'all') params.append('folder', activeFolder);
    if (sort) params.append('sort', sort);
    if (q) params.append('q', q);
    if (country && country !== 'all') params.append('country', country);
    if (city && city !== 'all') params.append('city', city);
    if (type && type !== 'all') params.append('type', type);
    if (project && project !== 'all') params.append('project', project);
    if (year && year !== 'all') params.append('year', year);
    if (camera && camera !== 'all') params.append('camera', camera);
    if (aperture && aperture !== 'all') params.append('aperture', aperture);
    if (shutter && shutter !== 'all') params.append('shutter', shutter);
    if (iso && iso !== 'all') params.append('iso', iso);
    params.append('limit', mediaLimit);

    var url = '<?= url("admin/api-media.php") ?>?' + params.toString();

    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                renderFolderSidebar(data.categories, data.total_count);
                populateFilterDropdowns(data.filter_options);
                renderMediaGrid(data.images);
                document.getElementById('mediaLoadMore').hidden = !data.has_more;
                mapLocations = data.map_locations || [];
                updateStatsBadges(data.total_count, data.filtered_count, mapLocations.length);
                updateActiveFilterChips();

                if (activeView === 'map') {
                    initOrUpdateMap();
                }
            }
        })
        .catch(function(err) {
            document.getElementById('mediaGrid').innerHTML = '<div style="color:var(--danger); grid-column:1/-1; text-align:center;">' + <?= json_encode(__a('Görseller yüklenirken hata oluştu.', 'An error occurred while loading images.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '</div>';
        });
}

function updateStatsBadges(total, filtered, cityCount) {
    document.getElementById('badge_total').innerText = total;
    document.getElementById('tabBadgePhotos').innerText = filtered + ' / ' + total;
    document.getElementById('tabBadgeCities').innerText = cityCount + (window.MEDIA_I18N.cities || ' Cities');

    var sub = document.getElementById('folderSubtitle');
    if (activeFolder === 'all') {
        sub.innerText = (window.MEDIA_I18N.listing_total || <?= json_encode(__a('Toplam %d fotoğraf listeleniyor.', 'Listing %d photos in total.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>).replace('%d', filtered);
    } else {
        sub.innerText = (window.MEDIA_I18N.photos_in_folder || '"%s" ' + <?= json_encode(__a('klasöründe %d fotoğraf bulunuyor.', 'folder contains %d photos.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '').replace('%d', filtered).replace('%s', activeFolder);
    }
}

function countryCodeToFlag(code) {
    if (!code || typeof code !== 'string') return '';
    var c = code.trim().toUpperCase();
    if (c.length !== 2 || !/^[A-Z]{2}$/.test(c)) return '';
    var first = 0x1F1E6 + (c.charCodeAt(0) - 65);
    var second = 0x1F1E6 + (c.charCodeAt(1) - 65);
    return String.fromCodePoint(first, second);
}

// ── Filtre Dropdown Seçeneklerini Doldurma ──
var dropdownsPopulated = false;
function populateFilterDropdowns(opts) {
    if (dropdownsPopulated) return; // Sadece ilk yüklemede doldur, seçimler sıfırlanmasın
    dropdownsPopulated = true;

    // Ülkeler (Sadece Ülke Bayrak Emojisi)
    var selC = document.getElementById('filterCountry');
    if (opts.countries) {
        opts.countries.forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.code;
            var flag = c.flag || countryCodeToFlag(c.code);
            opt.innerText = (flag ? flag + ' ' : '') + c.name + ' (' + c.count + ')';
            selC.appendChild(opt);
        });
    }

    // Şehirler (Ülke Bayrağı İle Birlikte)
    var selCity = document.getElementById('filterCity');
    if (opts.cities) {
        opts.cities.forEach(function(ct) {
            var opt = document.createElement('option');
            opt.value = ct.code;
            var flag = ct.country_flag || countryCodeToFlag(ct.country_code);
            opt.innerText = (flag ? flag + ' ' : '') + ct.name + ' (' + ct.count + ')';
            selCity.appendChild(opt);
        });
    }

    // Projeler & Seriler
    var selP = document.getElementById('filterProject');
    if (opts.projects) {
        opts.projects.forEach(function(p) {
            var opt = document.createElement('option');
            opt.value = p.code;
            opt.innerText = p.title + ' (' + p.count + ')';
            selP.appendChild(opt);
        });
    }

    // Yıllar
    var selY = document.getElementById('filterYear');
    if (opts.years) {
        opts.years.forEach(function(y) {
            var opt = document.createElement('option');
            opt.value = y.shot_year;
            opt.innerText = y.shot_year + ' (' + y.cnt + ')';
            selY.appendChild(opt);
        });
    }

    // Kameralar
    var selCam = document.getElementById('filterCamera');
    if (opts.cameras) {
        opts.cameras.forEach(function(cam) {
            var opt = document.createElement('option');
            opt.value = cam.camera;
            opt.innerText = cam.camera + ' (' + cam.cnt + ')';
            selCam.appendChild(opt);
        });
    }
}

// ── Filtre Değişikliği Yönetimi ──
function handleFilterChange(trigger) {
    loadMedia();
}

function handleSearch() {
    var input = document.getElementById('mediaSearchInput');
    var clearBtn = document.getElementById('btnSearchClear');
    if (clearBtn && input) {
        clearBtn.style.display = input.value.trim().length > 0 ? 'inline-flex' : 'none';
    }
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadMedia, 250);
}

function clearSearchInput() {
    var input = document.getElementById('mediaSearchInput');
    var clearBtn = document.getElementById('btnSearchClear');
    if (input) {
        input.value = '';
        input.focus();
    }
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }
    loadMedia();
}

// "/" tuşu ile medya arama kutusuna hızlı odaklanma
document.addEventListener('keydown', function(e) {
    if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        var tag = (document.activeElement && document.activeElement.tagName) ? document.activeElement.tagName.toUpperCase() : '';
        if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT' && !(document.activeElement && document.activeElement.isContentEditable)) {
            var searchInput = document.getElementById('mediaSearchInput');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
    }
});

function toggleExifDrawer() {
    var drawer = document.getElementById('exifDrawer');
    var btn = document.getElementById('btnExifToggle');
    drawer.classList.toggle('open');
    btn.classList.toggle('active');
}

function clearAllFilters() {
    document.getElementById('mediaSearchInput').value = '';
    var clearBtn = document.getElementById('btnSearchClear');
    if (clearBtn) clearBtn.style.display = 'none';
    document.getElementById('filterCountry').value = 'all';
    document.getElementById('filterCity').value = 'all';
    document.getElementById('filterType').value = 'all';
    document.getElementById('filterProject').value = 'all';
    document.getElementById('filterYear').value = 'all';
    document.getElementById('filterCamera').value = 'all';
    document.getElementById('filterAperture').value = 'all';
    document.getElementById('filterShutter').value = 'all';
    document.getElementById('filterIso').value = 'all';
    loadMedia();
}

function updateActiveFilterChips() {
    var chips = [];
    var qVal = (document.getElementById('mediaSearchInput').value || '').trim();
    if (qVal !== '') chips.push({ label: 'Arama: "' + qVal + '"', key: 'mediaSearchInput' });

    var cCountry = document.getElementById('filterCountry');
    if (cCountry.value !== 'all') chips.push({ label: '' + <?= json_encode(__a('Ülke:', 'Country:'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + ' ' + cCountry.options[cCountry.selectedIndex].text.split('(')[0], key: 'filterCountry' });

    var cCity = document.getElementById('filterCity');
    if (cCity.value !== 'all') chips.push({ label: '' + <?= json_encode(__a('Şehir:', 'City:'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + ' ' + cCity.options[cCity.selectedIndex].text.split('(')[0], key: 'filterCity' });

    var cType = document.getElementById('filterType');
    if (cType.value !== 'all') chips.push({ label: '' + <?= json_encode(__a('Tür:', 'Type:'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + ' ' + cType.options[cType.selectedIndex].text, key: 'filterType' });

    var cProject = document.getElementById('filterProject');
    if (cProject.value !== 'all') chips.push({ label: 'Proje/Seri: ' + cProject.options[cProject.selectedIndex].text.split('(')[0], key: 'filterProject' });

    var cYear = document.getElementById('filterYear');
    if (cYear.value !== 'all') chips.push({ label: '' + <?= json_encode(__a('Yıl:', 'Year:'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + ' ' + cYear.value, key: 'filterYear' });

    var cCam = document.getElementById('filterCamera');
    if (cCam.value !== 'all') chips.push({ label: 'Kamera: ' + cCam.value, key: 'filterCamera' });

    var cAp = document.getElementById('filterAperture');
    if (cAp.value !== 'all') chips.push({ label: 'Diyafram: ' + cAp.options[cAp.selectedIndex].text, key: 'filterAperture' });

    var cSh = document.getElementById('filterShutter');
    if (cSh.value !== 'all') chips.push({ label: 'Enstantane: ' + cSh.options[cSh.selectedIndex].text, key: 'filterShutter' });

    var cIso = document.getElementById('filterIso');
    if (cIso.value !== 'all') chips.push({ label: 'ISO: ' + cIso.options[cIso.selectedIndex].text, key: 'filterIso' });

    var cont = document.getElementById('activeFilterChips');
    var btnClear = document.getElementById('btnClearFilters');
    var badgeCount = document.getElementById('exifFilterCount');

    // EXIF filtresi sayısı
    var exifCount = (cCam.value !== 'all' ? 1 : 0) + (cAp.value !== 'all' ? 1 : 0) + (cSh.value !== 'all' ? 1 : 0) + (cIso.value !== 'all' ? 1 : 0);
    if (exifCount > 0) {
        badgeCount.innerText = exifCount;
        badgeCount.style.display = 'inline-block';
    } else {
        badgeCount.style.display = 'none';
    }

    if (chips.length > 0) {
        cont.style.display = 'flex';
        btnClear.style.display = 'inline-flex';
        cont.innerHTML = chips.map(function(ch) {
            return '<span class="filter-chip">' +
                '<span>' + ch.label + '</span>' +
                '<span class="filter-chip-remove" onclick="removeSingleFilter(\'' + ch.key + '\')">&times;</span>' +
            '</span>';
        }).join('');
    } else {
        cont.style.display = 'none';
        btnClear.style.display = 'none';
    }
}

function removeSingleFilter(key) {
    if (key === 'mediaSearchInput') {
        clearSearchInput();
        return;
    }
    document.getElementById(key).value = 'all';
    loadMedia();
}

// ── Sol Klasör Ağacı (Açılır-Kapanır Ana Dizinler) ──
function getFolderGroupState() {
    try {
        var raw = localStorage.getItem('media_folder_groups_state');
        return raw ? JSON.parse(raw) : {};
    } catch (e) {
        return {};
    }
}

function saveFolderGroupState(key, isOpen) {
    try {
        var st = getFolderGroupState();
        st[key] = !!isOpen;
        localStorage.setItem('media_folder_groups_state', JSON.stringify(st));
    } catch (e) {}
}

function toggleAllFolderGroups() {
    var groups = Array.from(document.querySelectorAll('details.folder-group'));
    if (!groups.length) return;
    var anyOpen = groups.some(function(g) { return g.open; });
    var targetOpen = !anyOpen;
    groups.forEach(function(g) {
        g.open = targetOpen;
        var k = g.getAttribute('data-group-key');
        if (k) saveFolderGroupState(k, targetOpen);
    });
}

function sumCategoryPhotos(folders) {
    if (!folders || !folders.length) return 0;
    return folders.reduce(function(acc, f) { return acc + (parseInt(f.count, 10) || 0); }, 0);
}

function renderFolderSidebar(cats, total) {
    var otherFolders = (cats.other && cats.other.folders) ? cats.other.folders : [];
    renderCatList('list_other', otherFolders);

    var bOth = document.getElementById('badge_cat_other');
    if (bOth) bOth.innerText = sumCategoryPhotos(otherFolders);

    var customWrap = document.getElementById('custom_cat_folders');
    if (customWrap) {
        var customHtml = '';
        Object.keys(cats).forEach(function(k) {
            if (k !== 'other' && cats[k]) {
                var listId = 'list_custom_' + k;
                var catTotal = sumCategoryPhotos(cats[k].folders || []);
                customHtml += '<details class="folder-group" id="group_' + k + '" data-group-key="' + k + '" open ontoggle="saveFolderGroupState(\'' + k + '\', this.open)">' +
                    '<summary class="folder-section-title">' +
                        '<span class="folder-section-left">' +
                            '<svg class="folder-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>' +
                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>' +
                            '<span>' + (cats[k].label || k) + '</span>' +
                        '</span>' +
                        '<span class="folder-section-right">' +
                            '<span class="folder-cat-badge">' + catTotal + '</span>' +
                        '</span>' +
                    '</summary>' +
                    '<ul class="folder-list" id="' + listId + '"></ul>' +
                '</details>';
            }
        });
        customWrap.innerHTML = customHtml;
        Object.keys(cats).forEach(function(k) {
            if (k !== 'other' && cats[k]) {
                renderCatList('list_custom_' + k, cats[k].folders || []);
            }
        });
    }

    // Kayıtlı açık/kapalı durumlarını geri yükle
    var savedStates = getFolderGroupState();
    document.querySelectorAll('details.folder-group').forEach(function(g) {
        var key = g.getAttribute('data-group-key');
        if (key && Object.prototype.hasOwnProperty.call(savedStates, key)) {
            g.open = !!savedStates[key];
        }
    });

    document.querySelectorAll('.folder-item').forEach(function(el) { el.classList.remove('active'); });
    if (activeFolder === 'all') {
        document.getElementById('folder_all').classList.add('active');
    } else {
        var el = document.querySelector('[data-folder="' + activeFolder + '"]');
        if (el) {
            el.classList.add('active');
            var parentDetails = el.closest('details.folder-group');
            if (parentDetails && !parentDetails.open) {
                parentDetails.open = true;
            }
        }
    }
}

function renderCatList(containerId, folders) {
    var ul = document.getElementById(containerId);
    if (!folders || !folders.length) {
        ul.innerHTML = '<li style="font-size:12px; color:var(--text-sub); padding:4px 10px;">' + (window.MEDIA_I18N.no_folders_yet) + '</li>';
        return;
    }
    ul.innerHTML = folders.map(function(f) {
        var isActive = (activeFolder === f.path) ? ' active' : '';
        return '<li class="folder-item' + isActive + '" data-folder="' + f.path + '" onclick="selectFolder(\'' + f.path + '\')">' +
            '<span class="folder-item-name">' +
                '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:0.6; flex-shrink:0;"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>' +
                '<span title="' + f.path + '" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0;">' + f.name + '</span>' +
            '</span>' +
            '<span class="folder-badge" style="flex-shrink:0;">' + f.count + '</span>' +
        '</li>';
    }).join('');
}

function selectFolder(folder) {
    activeFolder = folder;
    var dropTitle = document.getElementById('dropZoneTitle');
    if (folder === 'all') {
        dropTitle.innerText = <?= json_encode(__a('Genel Klasöre Fotoğraf Yükleyin (Sürükle & Bırak)', 'Upload Photos to the General Folder (Drag & Drop)'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    } else {
        dropTitle.innerText = '"' + folder + '" ' + <?= json_encode(__a('Klasörüne Yükleyin', 'Upload to Folder'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '';
    }
    loadMedia();
}

// ── Medya Izgarasını Oluşturma ──
function renderMediaGrid(images) {
    currentFilteredImages = images;
    var grid = document.getElementById('mediaGrid');

    if (!images.length) {
        grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:var(--text-sub);">' +
            '<p style="font-size:16px; font-weight:600;">' + <?= json_encode(__a('Seçilen filtrelere uygun görsel bulunamadı.', 'No images match the selected filters.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '</p>' +
            '<p style="font-size:12px; margin-top:6px;">' + <?= json_encode(__a('Filtreleri sıfırlayarak tüm arşivi görüntüleyebilirsiniz.', 'Reset the filters to view the whole archive.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '</p>' +
            '<button type="button" class="btn btn-secondary btn-sm" onclick="clearAllFilters()" style="margin-top:10px;">Filtreleri Temizle</button>' +
        '</div>';
        return;
    }

    grid.innerHTML = images.map(function(img) {
        var catClass = (img.item_type === 'project') ? 'badge-cat-p' : ((img.item_type === 'series') ? 'badge-cat-s' : 'badge-cat-single');
        var exifSnippet = [];
        if (img.aperture) exifSnippet.push(img.aperture);
        if (img.shutter) exifSnippet.push(img.shutter);
        if (img.iso) exifSnippet.push('ISO ' + img.iso);

        return '<div class="media-card">' +
            '<div class="media-thumb-wrap" onclick="openDrawer(' + img.id + ')">' +
                '<img class="media-thumb-img" src="' + img.thumb_url + '" alt="' + img.original_name + '" loading="lazy" />' +
                '<div class="media-badges">' +
                    (img.country_flag ? '<span class="media-badge-pill badge-loc">' + img.country_flag + ' ' + (img.city_name || img.country_name) + '</span>' : '') +
                    '<span class="media-badge-pill ' + catClass + '">' + img.type_label + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="media-info">' +
                '<div class="media-name" title="' + img.original_name + '">' + img.original_name + '</div>' +
                '<div class="media-meta-line">' +
                    '<span>' + (img.shot_date_fmt || img.shot_year || '') + '</span>' +
                    '<span>' + img.file_size_fmt + '</span>' +
                '</div>' +
                (exifSnippet.length > 0 ? 
                    '<div class="media-meta-line" style="margin-top:2px;">' +
                        '<span class="media-exif-pill">' + exifSnippet.join(' • ') + '</span>' +
                    '</div>' : '') +
                '<div class="media-actions">' +
                    '<button type="button" class="btn btn-secondary btn-sm" style="flex:1; justify-content:center; padding:4px 8px; font-size:12px;" onclick="openDrawer(' + img.id + ')">' +
                        (<?= json_encode(__a('Detaylar & EXIF', 'Details & EXIF'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) +
                    '</button>' +
                    '<a href="' + img.url + '" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="padding:4px 8px; font-size:12px;" title="' + escapeHtml(<?= json_encode(__a('Görseli Yeni Sekmede Aç', 'Open image in new tab'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) + '">' +
                        <?= json_encode(__a('Aç ↗', 'Open ↗'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> +
                    '</a>' +
                '</div>' +
            '</div>' +
        '</div>';
    }).join('');
}

// ── DÜNYA HARİTASI İŞLEMLERİ (LEAFLET.JS) ──
function initOrUpdateMap() {
    var mapEl = document.getElementById('worldMap');
    if (!mapEl) return;

    if (!leafletMap) {
        // Haritayı başlat (merkez: Akdeniz / Orta Doğu enlemi)
        leafletMap = L.map('worldMap', {
            center: [28.0, 30.0],
            zoom: 3,
            minZoom: 2,
            maxZoom: 18
        });

        // Ücretsiz ve anahtarsız OpenStreetMap katmanı
        var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> | ' + <?= json_encode(e(site_owner_name()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
            maxZoom: 19
        });

        if (googleMapsApiKey && googleMapsApiKey.trim() !== '') {
            var googleRoads = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&key=' + encodeURIComponent(googleMapsApiKey.trim()), {
                attribution: '&copy; Google Maps',
                maxZoom: 20
            });
            var googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}&key=' + encodeURIComponent(googleMapsApiKey.trim()), {
                attribution: '&copy; Google Maps',
                maxZoom: 20
            });

            googleRoads.addTo(leafletMap);

            L.control.layers({
                "Google Maps": googleRoads,
                "Google Uydu": googleHybrid,
                "OpenStreetMap": osmLayer
            }, null, { position: 'topright' }).addTo(leafletMap);
        } else {
            osmLayer.addTo(leafletMap);
        }
    } else {
        leafletMap.invalidateSize();
    }

    // Önceki markerları temizle
    mapMarkers.forEach(function(m) { leafletMap.removeLayer(m); });
    mapMarkers = [];

    var bounds = [];
    var countriesSet = new Set();

    // Lokasyon listesi sidebar'ı
    var listEl = document.getElementById('mapCitiesList');
    listEl.innerHTML = '';

    mapLocations.forEach(function(loc) {
        countriesSet.add(loc.country_code);

        // Marker boyutu ve stili (fotoğraf sayısına göre)
        var size = Math.min(Math.max(Math.round(Math.sqrt(loc.count) * 6) + 18, 24), 48);
        var pinHtml = '<div class="custom-photo-pin" style="width:' + size + 'px; height:' + size + 'px;">' + loc.count + '</div>';

        var customIcon = L.divIcon({
            html: pinHtml,
            className: '',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2]
        });

        var marker = L.marker([loc.lat, loc.lng], { icon: customIcon }).addTo(leafletMap);

        // Popup İçeriği
        var projectsList = loc.projects.length ? loc.projects.join(', ') : <?= json_encode(__a('Belgesel Fotoğraflar', 'Documentary Photographs'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var popupContent = 
            '<div style="font-family:\'Space Grotesk\', sans-serif; min-width:210px; max-width:260px;">' +
                (loc.sample_thumb ? '<div style="width:100%; height:110px; border-radius:6px; overflow:hidden; margin-bottom:8px; background:#000;"><img src="' + loc.sample_thumb + '" style="width:100%; height:100%; object-fit:cover;" /></div>' : '') +
                '<h4 style="margin:0 0 4px 0; font-size:14px; font-weight:700;">' + (loc.country_flag ? loc.country_flag + ' ' : '') + loc.city_name + ', ' + loc.country_name + '</h4>' +
                '<div style="font-size:12px; color:var(--accent-blue); font-weight:700; margin-bottom:6px;">' + loc.count + ' ' + <?= json_encode(__a('Fotoğraf', 'Photos'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '</div>' +
                '<div style="font-size:12px; color:var(--text-muted); line-height:1.4; margin-bottom:10px;"><strong>Projeler:</strong> ' + projectsList + '</div>' +
                '<button type="button" class="btn btn-primary btn-sm" style="width:100%; justify-content:center; font-size:12px;" onclick="filterByMapCity(\'' + loc.city_code + '\')">' +
                    <?= json_encode(__a('Bu Konumdaki Fotoğrafları Gör →', 'See Photos from This Location →'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> +
                '</button>' +
            '</div>';

        marker.bindPopup(popupContent);
        mapMarkers.push(marker);
        bounds.push([loc.lat, loc.lng]);

        // Sidebar listesine ekle
        var item = document.createElement('div');
        item.className = 'map-loc-item';
        item.innerHTML = 
            '<div>' +
                '<strong>' + (loc.country_flag ? loc.country_flag + ' ' : '') + loc.city_name + '</strong>' +
                '<div style="font-size:12px; color:var(--text-sub);">' + loc.country_name + '</div>' +
            '</div>' +
            '<span class="folder-badge">' + loc.count + '</span>';
        item.onclick = function() {
            leafletMap.flyTo([loc.lat, loc.lng], 9, { duration: 1 });
            marker.openPopup();
        };
        listEl.appendChild(item);
    });

    document.getElementById('mapStatCountries').innerText = countriesSet.size;
    document.getElementById('mapStatCities').innerText = mapLocations.length;

    if (bounds.length > 0) {
        leafletMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 8 });
    }
}

function filterByMapCity(cityCode) {
    document.getElementById('filterCity').value = cityCode;
    switchView('grid');
    loadMedia();
}

// ── SAĞ ÇEKMECE (INSPECTOR) DETAYLARI ──
function openDrawer(imgId) {
    var drawer = document.getElementById('imageDrawer');
    drawer.classList.add('open');

    // Drawer içeriğini API'den taze detay ve varyantlarla çek
    var url = '<?= url("admin/api-media.php") ?>?action=details&id=' + imgId + '&admin_lang=' + encodeURIComponent(window.ADMIN_LANG || 'tr');

    document.getElementById('drawerVariantsList').innerHTML = '<div style="font-size:12px; color:var(--text-sub);">' + (window.MEDIA_I18N.scanning_variants) + '</div>';

    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var img = data.image;
                var parsed = data.parsed;
                var exif = data.exif;
                var variants = data.variants || [];

                document.getElementById('drawerTitle').innerText = img.original_name || img.filename;
                document.getElementById('drawerDate').innerText = parsed.date ? ((window.MEDIA_I18N.shot_prefix || '' + <?= json_encode(__a('Çekim:', 'Shot:'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + ' ') + parsed.date) : '';
                document.getElementById('drawerImage').src = '<?= url("") ?>' + img.file_path;

                // Rozetler
                var badgesHtml = '';
                if (parsed.country_flag) badgesHtml += '<span class="media-badge-pill badge-loc">' + parsed.country_flag + ' ' + (parsed.city_name || parsed.country_name) + '</span>';
                if (parsed.badge) badgesHtml += '<span class="media-badge-pill badge-cat-p">' + parsed.badge + '</span>';
                document.getElementById('drawerBadgeOverlay').innerHTML = badgesHtml;

                // Künye
                document.getElementById('drawerAuthor').innerText = parsed.author_name || <?= json_encode(site_owner_name(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
                document.getElementById('drawerLocation').innerText = (parsed.country_flag ? parsed.country_flag + ' ' : '') + (parsed.city_name ? parsed.city_name + ', ' : '') + (parsed.country_name || (window.MEDIA_I18N.unknown || 'Bilinmiyor'));
                document.getElementById('drawerProject').innerText = parsed.project_title || parsed.type_label || (window.MEDIA_I18N.single_photo || <?= json_encode(__a('Tekil Fotoğraf', 'Single Photo'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                document.getElementById('drawerFilename').innerText = img.filename;
                document.getElementById('drawerPath').innerText = img.file_path;

                // EXIF
                document.getElementById('drawerCamera').innerText = exif.camera_clean || exif.camera || (window.MEDIA_I18N.unknown || 'Bilinmiyor');
                document.getElementById('drawerLens').innerText = exif.lens || (window.MEDIA_I18N.fixed_lens || 'Sabit Lens');
                document.getElementById('drawerAperture').innerText = exif.aperture || '-';
                document.getElementById('drawerShutter').innerText = exif.shutter || '-';
                document.getElementById('drawerIso').innerText = exif.iso ? ('ISO ' + exif.iso) : '-';
                document.getElementById('drawerFocal').innerText = exif.focal_length || '-';
                document.getElementById('drawerDims').innerText = (img.width && img.height) ? (img.width + ' × ' + img.height + ' px') : (window.MEDIA_I18N.unknown || 'Bilinmiyor');
                document.getElementById('drawerSize').innerText = roundFileSize(img.file_size);

                // Varyantlar (WebP & AVIF Linkleri)
                renderDrawerVariants(variants);

                // Silme butonu
                var delLink = document.getElementById('drawerDeleteLink');
                delLink.href = 'media.php?delete=' + img.id + '&folder=' + encodeURIComponent(activeFolder);
                if (!delLink.dataset.baseConfirm) delLink.dataset.baseConfirm = delLink.getAttribute('data-confirm') || '';
                var usedIn = data.used_in || [];
                delLink.setAttribute('data-confirm', delLink.dataset.baseConfirm + (usedIn.length
                    ? '\n\n' + (window.ADMIN_LANG === 'en' ? 'Used in: ' : '' + <?= json_encode(__a('Kullanıldığı yerler:', 'Used in:'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + ' ') + usedIn.join(', ')
                    : ''));
            }
        });
}

function closeDrawer() {
    var drawer = document.getElementById('imageDrawer');
    if (drawer) {
        drawer.classList.remove('open');
    }
}

// Bilgi kartı açıkken kart dışına tıklandığında veya Escape basıldığında otomatik kapat
document.addEventListener('click', function(e) {
    var drawer = document.getElementById('imageDrawer');
    if (!drawer || !drawer.classList.contains('open')) return;
    if (drawer.contains(e.target)) return;
    if (e.target.closest && (e.target.closest('.media-thumb-wrap') || e.target.closest('.media-card') || e.target.closest('[onclick*="openDrawer"]'))) {
        return;
    }
    closeDrawer();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
        closeDrawer();
    }
});

function renderDrawerVariants(variants) {
    var cont = document.getElementById('drawerVariantsList');
    if (!variants || !variants.length) {
        cont.innerHTML = '<div style="font-size:12px; color:var(--text-sub);">' + (window.MEDIA_I18N.no_variants) + '</div>';
        return;
    }

    cont.innerHTML = variants.map(function(v) {
        var badgeClass = (v.format === 'AVIF') ? 'badge-avif' : ((v.format === 'WEBP') ? 'badge-webp' : 'badge-orig');
        var label = v.label || '';
        return '<div class="variant-row">' +
            '<div style="display:flex; align-items:center; gap:8px; overflow:hidden;">' +
                '<span class="variant-badge ' + badgeClass + '">' + v.format + '</span>' +
                '<div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' +
                    '<div style="font-weight:600; font-size:12px;">' + escapeHtml(label) + '</div>' +
                    '<div style="font-size:12px; color:var(--text-sub);">' + escapeHtml(v.size_fmt) + '</div>' +
                '</div>' +
            '</div>' +
            '<div style="display:flex; gap:6px; flex-shrink:0;">' +
                '<a href="' + v.url + '" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="padding:4px 10px; font-size:12px;">' + (window.MEDIA_I18N.open_btn) + '</a>' +
            '</div>' +
        '</div>';
    }).join('');
}

function roundFileSize(bytes) {
    if (!bytes) return '0 KB';
    if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    }
    return Math.round(bytes / 1024) + ' KB';
}

// ── SÜRÜKLE-BIRAK YÜKLEYİCİ ──
function setupDropZone() {
    var dz = document.getElementById('dropZone');
    dz.addEventListener('dragover', function(e) {
        e.preventDefault();
        dz.style.borderColor = '#2563eb';
        dz.style.background = 'rgba(37, 99, 235, 0.05)';
    });
    dz.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dz.style.borderColor = 'var(--border-light)';
        dz.style.background = 'rgba(255,255,255,0.01)';
    });
    dz.addEventListener('drop', function(e) {
        e.preventDefault();
        dz.style.borderColor = 'var(--border-light)';
        dz.style.background = 'rgba(255,255,255,0.01)';
        uploadFiles(e.dataTransfer.files);
    });
    dz.addEventListener('click', function() {
        document.getElementById('mainFileInput').click();
    });
}

function uploadFiles(files) {
    if (!files || !files.length) return;

    var total = files.length;
    var completed = 0;

    var progressBox = document.getElementById('uploadProgressBox');
    var progressBar = document.getElementById('uploadProgressBar');
    var progressText = document.getElementById('uploadProgressText');
    var progressCount = document.getElementById('uploadProgressCount');

    progressBox.style.display = 'block';
    progressBar.style.width = '0%';
    progressCount.innerText = '0 / ' + total;

    var queue = Array.from(files);
    var failed = [];

    function processNext() {
        if (!queue.length && failed.length) {
            progressBox.style.display = 'none';
            loadMedia();
            alert(((window.ADMIN_LANG === 'en') ? (failed.length + ' of ' + total + ' uploads failed:\n') : (total + ' dosyadan ' + failed.length + ' ' + <?= json_encode(__a('tanesi yüklenemedi:
', 'could not be uploaded:'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '')) + failed.join('\n'));
            return;
        }
        if (!queue.length) {
            progressText.innerText = (window.ADMIN_LANG === 'en') ? 'All photos successfully uploaded, EXIF parsed and converted!' : <?= json_encode(__a('Tüm fotoğraflar başarıyla yüklendi, EXIF verileri çözümlendi ve dönüştürüldü!', 'All photos were uploaded, EXIF data parsed and converted successfully!'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            setTimeout(function() {
                progressBox.style.display = 'none';
                loadMedia();
            }, 1200);
            return;
        }

        var file = queue.shift();
        progressText.innerText = (window.ADMIN_LANG === 'en') ? ('Converting "' + file.name + '" (AVIF & WebP)...') : ('"' + file.name + '" ' + <?= json_encode(__a('dönüştürülüyor (AVIF & WebP)...', 'converting (AVIF & WebP)...'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '');

        var formData = new FormData();
        formData.append('image', file);
        formData.append('csrf_token', '<?= csrf_token() ?>');
        if (activeFolder && activeFolder !== 'all') {
            formData.append('folder', activeFolder);
        }

        fetch('<?= url("admin/upload.php") ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            completed++;
            if (!data || !data.success) failed.push(file.name + ': ' + ((data && data.error) || '?'));
            var pct = Math.round((completed / total) * 100);
            progressBar.style.width = pct + '%';
            progressCount.innerText = completed + ' / ' + total;
            processNext();
        })
        .catch(function(err) {
            completed++;
            failed.push(file.name + ': ' + (err && err.message ? err.message : 'network'));
            processNext();
        });
    }

    processNext();
}

function promptNewFolder() {
    var parent = (activeFolder && activeFolder !== 'all') ? activeFolder : 'uploads';
    adminPrompt(window.ADMIN_LANG === 'en' ? 'New folder name (e.g. bosphorus-series)' : <?= json_encode(__a('Yeni klasörün adı (ör. bosphorus-series)', 'Name of the new folder (e.g. bosphorus-series)'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, '').then(function (name) {
        name = (name || '').trim();
        if (name) createFolderNow(parent, name);
    });
}

function createFolderNow(parent, name) {
    var formData = new FormData();
    formData.append('action', 'create_folder');
    formData.append('parent', parent);
    formData.append('name', name);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('<?= url("admin/api-media.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            activeFolder = data.folder;
            loadMedia();
        } else {
            alert('Hata: ' + (data.error || <?= json_encode(__a('Klasör oluşturulamadı.', 'The folder could not be created.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>));
        }
    });
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
