<?php
/**
 * admin/stats.php
 * Google Analytics 4 (GA4) Ziyaretçi & Portfolyo İstatistikleri
 * 
 * Göstergeler:
 * 1. Genel Performans: Toplam Aktif Kullanıcı (activeUsers)
 * 2. İlgi & Derinlik: Ortalama Etkileşim Süresi (averageSessionDuration)
 * 3. Edinim: Trafik Kaynağı / Mecra (sessionSource, sessionMedium) - Pasta Grafik
 * 4. İçerik Popülerliği: En Çok Gezilen Portfolyolar (screenPageViews, pagePath) - İlk 10 Liste
 * 5. Dönüşüm / Aksiyon: İletişim Formu, PDF İndirme vb. (conversions, eventName) - Çubuk Grafik
 * 6. Coğrafi Analiz: Ülke & Şehir Dağılımı (country, city, activeUsers) - Liste & Dünya Haritası
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/media_metadata.php';
require_once __DIR__ . '/../includes/ga_api.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();

// ── AJAX Test veya Ayar Kaydetme İşlemleri ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // AJAX Test İsteği
    if ($action === 'test_connection') {
        header('Content-Type: application/json; charset=utf-8');
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'CSRF güvenlik doğrulaması başarısız.']);
            exit;
        }

        $propId = trim($_POST['property_id'] ?? '');
        $jsonRaw = trim($_POST['service_account_json'] ?? '');

        if (empty($propId)) {
            echo json_encode(['success' => false, 'message' => 'Lütfen GA4 Property ID girin.']);
            exit;
        }
        $sa = json_decode($jsonRaw, true);
        if (!is_array($sa) || empty($sa['client_email']) || empty($sa['private_key'])) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz Service Account JSON formatı.']);
            exit;
        }

        $testRes = test_ga_connection($propId, $sa);
        echo json_encode($testRes);
        exit;
    }

    // Form Üzerinden Ayarları Kaydetme
    if ($action === 'save_ga_config') {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'CSRF güvenlik hatası.');
            header('Location: stats.php');
            exit;
        }

        $propId = trim($_POST['property_id'] ?? '');
        $jsonRaw = trim($_POST['service_account_json'] ?? '');

        // Dosya yüklemesi varsa dosya içeriğini al
        if (!empty($_FILES['service_account_file']['tmp_name'])) {
            $uploadedJson = @file_get_contents($_FILES['service_account_file']['tmp_name']);
            if (!empty($uploadedJson)) {
                $jsonRaw = trim($uploadedJson);
            }
        }

        set_setting('ga_property_id', $propId);
        if (!empty($jsonRaw)) {
            set_setting('ga_service_account_json', $jsonRaw);
        }

        // Önbellekleri temizle
        $pdo->query("DELETE FROM settings WHERE key_name LIKE 'ga_cache_%'");

        set_flash('success', 'Google Analytics API yapılandırması başarıyla kaydedildi.');
        header('Location: stats.php');
        exit;
    }
}

// ── Tarih Aralığı ve Veri Çekimi ──
$range = $_GET['range'] ?? '30d';
if (!in_array($range, ['7d', '30d', '90d', '365d'])) {
    $range = '30d';
}

$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
if ($force_refresh) {
    $pdo->prepare("DELETE FROM settings WHERE key_name = ?")->execute(['ga_cache_' . $range]);
}

$data = get_analytics_dashboard_data($range, $force_refresh);
$isLive = !empty($data['is_live']);

$admin_title = __a('menu_stats');
$breadcrumb_root = __a('bc_system');
$breadcrumb_page = __a('menu_stats');

require __DIR__ . '/includes/header.php';
?>

<!-- Leaflet CSS & JS (Harita Görünümü İçin) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Chart.js (Grafik Motoru) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
/* ── İstatistikler Sayfası Özel Stilleri ── */
.stats-container,
#configModal {
    --border-color: var(--border-card, #eaecf0);
    --bg-body: var(--bg-surface-2, #f8fafc);
}

.stats-container {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

/* Üst Kontrol Çubuğu */
.stats-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    padding-bottom: 4px;
}
.stats-header-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.stats-page-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-main);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.02em;
    line-height: 1.25;
}
.stats-page-title svg {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
    display: block;
    color: var(--accent-blue);
}
.stats-page-desc {
    font-size: 13px;
    color: var(--text-sub);
}
.stats-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.stats-header-actions .btn svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    display: block;
}

/* Tarih Seçici Hapları */
.stats-date-pills {
    display: flex;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 3px;
    gap: 3px;
}
.stats-date-pill {
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.15s ease;
}
.stats-date-pill:hover {
    color: var(--text-main);
    background: var(--bg-hover);
}
.stats-date-pill.active {
    color: var(--accent-blue);
    background: var(--accent-blue-subtle);
    font-weight: 600;
}

/* Durum Bildirim Şeridi */
.stats-status-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 13px;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    gap: 12px;
    flex-wrap: wrap;
}
.stats-status-banner.is-live {
    border-color: rgba(16, 185, 129, 0.3);
    background: rgba(16, 185, 129, 0.05);
}
.stats-status-banner.is-demo {
    border-color: rgba(245, 158, 11, 0.3);
    background: rgba(245, 158, 11, 0.05);
}
.status-pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.status-pill-badge.live {
    background: rgba(16, 185, 129, 0.2);
    color: var(--success-text);
}
.status-pill-badge.demo {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning-text);
}

/* 4'lü KPI Kart Grid'i */
.stats-container .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 0;
}
.stats-container .kpi-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-card);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: stretch !important;
    gap: 0 !important;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, border-color 0.15s ease;
}
.stats-container .kpi-card:hover {
    border-color: #94a3b8;
    transform: translateY(-2px);
}
.kpi-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    width: 100%;
    margin-bottom: 12px;
}
.kpi-card-title {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-sub);
    line-height: 1.35;
}
.kpi-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--accent-blue-subtle);
    color: var(--accent-blue);
    flex-shrink: 0;
}
.kpi-card-icon svg {
    width: 18px;
    height: 18px;
    display: block;
    flex-shrink: 0;
}
.kpi-card-val {
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--text-main);
    line-height: 1.1;
    margin-bottom: 8px;
}
.kpi-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    font-size: 12px;
    color: var(--text-muted);
}
.kpi-trend-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 700;
    color: var(--success-text);
}

/* İki Kolonlu Panel Izgarası */
.stats-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    width: 100%;
    min-width: 0;
}
@media (max-width: 1024px) {
    .stats-two-col {
        grid-template-columns: 1fr;
    }
}

/* Panel Kart Yapısı */
.stats-panel-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.stats-panel-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: rgba(255, 255, 255, 0.01);
}
.stats-panel-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-main);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    line-height: 1.3;
}
.stats-panel-title svg {
    width: 16px;
    height: 16px;
    display: block;
    flex-shrink: 0;
    color: var(--accent-blue);
}
.stats-panel-body {
    padding: 20px;
    flex: 1;
}

/* Tablo Stilleri */
.stats-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.stats-table th {
    text-align: left;
    padding: 8px 10px;
    color: var(--text-sub);
    font-weight: 600;
    border-bottom: 1px solid var(--border-color);
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.05em;
}
.stats-table td {
    padding: 10px;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-main);
}
.stats-table tr:last-child td {
    border-bottom: none;
}
.stats-table tr:hover td {
    background: var(--bg-hover);
}

/* Yüzde / Progress Bar */
.stats-progress-wrap {
    background: var(--bg-body);
    height: 6px;
    border-radius: 6px;
    overflow: hidden;
    min-width: 60px;
}
.stats-progress-fill {
    height: 100%;
    background: var(--primary);
    border-radius: 6px;
}

/* Harita ve Liste Sekmeleri */
.geo-tab-btns {
    display: flex;
    background: var(--bg-body);
    padding: 3px;
    border-radius: 6px;
    gap: 3px;
}
.geo-tab-btn {
    border: none;
    background: transparent;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.geo-tab-btn.active {
    background: var(--bg-surface);
    color: var(--text-main);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
#geoMapContainer {
    width: 100%;
    height: 360px;
    border-radius: 6px;
    overflow: hidden;
}

/* Modal Stilleri */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 20px;
}
.modal-overlay.open {
    display: flex;
}
.modal-dialog {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
}
.modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 8px;
}
.modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<div class="stats-container">

    <!-- ── ÜST KONTROL & FİLTRE ÇUBUĞU ── -->
    <div class="stats-header-bar">
        <div class="stats-header-left">
            <h1 class="stats-page-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                <span><?= e(__a('İstatistikler', 'Statistics')) ?></span>
            </h1>
            <div class="stats-page-desc">
                <?= e(__a('Google Analytics 4 API üzerinden portfolyo etkileşimi, coğrafi dağılım ve dönüşüm metrikleri.', 'Portfolio engagement, geographic distribution, and conversion metrics via Google Analytics 4 API.')) ?>
            </div>
        </div>

        <div class="stats-header-actions">
            <!-- Tarih Seçici -->
            <div class="stats-date-pills">
                <a href="stats.php?range=7d" class="stats-date-pill <?= $range === '7d' ? 'active' : '' ?>">7 <?= e(__a('Gün', 'Days')) ?></a>
                <a href="stats.php?range=30d" class="stats-date-pill <?= $range === '30d' ? 'active' : '' ?>">30 <?= e(__a('Gün', 'Days')) ?></a>
                <a href="stats.php?range=90d" class="stats-date-pill <?= $range === '90d' ? 'active' : '' ?>">90 <?= e(__a('Gün', 'Days')) ?></a>
                <a href="stats.php?range=365d" class="stats-date-pill <?= $range === '365d' ? 'active' : '' ?>">1 <?= e(__a('Yıl', 'Year')) ?></a>
            </div>

            <!-- Yenile Butonu -->
            <a href="stats.php?range=<?= $range ?>&refresh=1" class="btn btn-secondary btn-sm" title="<?= e(__a('Google Analytics\'ten güncel verileri çek', 'Fetch fresh data from Google Analytics')) ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                <span><?= e(__a('Yenile', 'Refresh')) ?></span>
            </a>

            <!-- API Ayar Modalı Aç -->
            <button type="button" class="btn btn-primary btn-sm" onclick="openConfigModal()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span><?= e(__a('API Yapılandırması', 'API Configuration')) ?></span>
            </button>
        </div>
    </div>

    <!-- ── DURUM BİLGİLENDİRME ŞERİDİ ── -->
    <div class="stats-status-banner <?= $isLive ? 'is-live' : 'is-demo' ?>">
        <div style="display:flex; align-items:center; gap:10px;">
            <?php if ($isLive): ?>
                <span class="status-pill-badge live"><?= e(__a('stats_status_live')) ?></span>
                <span><?= e(__a('stats_status_live_desc_prefix')) ?> (Property ID: <strong><?= e($data['property_id']) ?></strong>).</span>
            <?php else: ?>
                <span class="status-pill-badge demo"><?= e(__a('stats_status_demo')) ?></span>
                <span><?= e(__a('stats_status_demo_desc')) ?></span>
            <?php endif; ?>
        </div>
        <div style="display:flex; align-items:center; gap:12px; font-size:12px; color:var(--text-sub);">
            <span><?= e(__a('Son Senkron', 'Last Synced')) ?>: <strong><?= e($data['cached_at_fmt']) ?></strong></span>
            <?php if (!$isLive): ?>
                <button type="button" class="btn btn-secondary btn-sm" style="padding:3px 8px; font-size:12px;" onclick="openConfigModal()"><?= e(__a('Google\'a Bağlan →', 'Connect Google →')) ?></button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($data['api_error'])): ?>
        <div class="alert alert-danger" style="margin:0; font-size:12px;">
            <strong><?= e(__a('Google Analytics API Hatası:', 'Google Analytics API Error:')) ?></strong> <?= e($data['api_error']) ?>
        </div>
    <?php endif; ?>

    <!-- ── GENEL PERFORMANS: BÜYÜK KPI KARTLARI ── -->
    <div class="kpi-grid">
        <!-- 1. Toplam Aktif Kullanıcı (activeUsers) -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-card-title"><?= e(__a('stats_kpi_active_users', 'Aktif Kullanıcı')) ?></span>
                <div class="kpi-card-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>
            <div class="kpi-card-val"><?= number_format($data['active_users'], 0, ',', '.') ?></div>
            <div class="kpi-card-footer">
                <span><?= number_format($data['new_users'], 0, ',', '.') ?> <?= e(__a('stats_new_visitors', 'yeni ziyaretçi')) ?></span>
            </div>
        </div>

        <!-- 2. Ortalama Etkileşim Süresi (averageSessionDuration) -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-card-title"><?= e(__a('stats_kpi_avg_duration', 'Ort. Etkileşim Süresi')) ?></span>
                <div class="kpi-card-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="kpi-card-val"><?= e($data['avg_duration_fmt']) ?></div>
            <div class="kpi-card-footer">
                <span style="color:var(--success-text); font-weight:600;"><?= e(__a('Yüksek İlgi & Derinlik', 'High Depth & Engagement')) ?></span>
                <span><?= e(__a('Fotoğraf okuma süresi', 'Photo reading time')) ?></span>
            </div>
        </div>

        <!-- 3. Sayfa & Fotoğraf Görüntüleme (screenPageViews) -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-card-title"><?= e(__a('Görüntüleme Sayısı', 'Page Views')) ?></span>
                <div class="kpi-card-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
            </div>
            <div class="kpi-card-val"><?= number_format($data['pageviews'], 0, ',', '.') ?></div>
            <div class="kpi-card-footer">
                <span><?= e(__a('Portfolyo & Hikayeler', 'Portfolio & Stories')) ?></span>
                <span><?= e(__a('Kullanıcı başına', 'Per user')) ?> ~<?= round($data['pageviews'] / max(1, $data['active_users']), 1) ?> <?= e(__a('sayfa', 'pages')) ?></span>
            </div>
        </div>

        <!-- 4. Toplam Oturum (sessions) -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-card-title"><?= e(__a('Toplam Oturum', 'Total Sessions')) ?></span>
                <div class="kpi-card-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                </div>
            </div>
            <div class="kpi-card-val"><?= number_format($data['sessions'], 0, ',', '.') ?></div>
            <div class="kpi-card-footer">
                <span><?= e(__a('Hemen Çıkma', 'Bounce Rate')) ?>: <strong>%<?= $data['bounce_rate'] ?></strong></span>
                <span><?= e(__a('Tekil ziyaretler', 'Unique visits')) ?></span>
            </div>
        </div>
    </div>

    <!-- ── BELGESEL & EDİTORYAL KALİBRE ANALİZİ (CİHAZ VE HİKAYE DERİNLİĞİ) ── -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
        <!-- Masaüstü (Editoryal / Küratör) -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-card-title"><?= e(__a('Masaüstü (Editoryal / Jüri)', 'Desktop (Editorial / Jury)')) ?></span>
                <div class="kpi-card-icon" style="background:rgba(245, 158, 11, 0.14); color:var(--warning-text);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                </div>
            </div>
            <div style="display:flex; align-items:baseline; gap:8px;">
                <span class="kpi-card-val" style="font-size:26px;">%<?= $data['devices']['desktop']['pct'] ?? 0 ?></span>
                <span style="font-size:12px; color:var(--text-sub); font-family:var(--font-mono);">(<?= number_format($data['devices']['desktop']['users'] ?? 0) ?> <?= e(__a('Ziyaretçi', 'Visitors')) ?>)</span>
            </div>
            <div class="kpi-card-footer" style="margin-top:4px;">
                <span style="color:var(--success-text); font-weight:600;"><?= e(__a('Ort.', 'Avg.')) ?> <?= e($data['devices']['desktop']['duration_fmt'] ?? '—') ?></span>
                <span title="<?= e(__a('Küratör, jüri ve editörler büyük ekranlardan inceler', 'Curators, jury, and editors review on large displays')) ?>"><?= e(__a('Büyük Ekran İncelemesi', 'Large Display Review')) ?></span>
            </div>
        </div>

        <!-- Mobil (Sosyal Medya / Hızlı Keşif) -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-card-title"><?= e(__a('Mobil (Sosyal Medya Keşfi)', 'Mobile (Social Discovery)')) ?></span>
                <div class="kpi-card-icon" style="background:rgba(56, 189, 248, 0.12); color:#38bdf8;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/></svg>
                </div>
            </div>
            <div style="display:flex; align-items:baseline; gap:8px;">
                <span class="kpi-card-val" style="font-size:26px;">%<?= $data['devices']['mobile']['pct'] ?? 0 ?></span>
                <span style="font-size:12px; color:var(--text-sub); font-family:var(--font-mono);">(<?= number_format($data['devices']['mobile']['users'] ?? 0) ?> <?= e(__a('Ziyaretçi', 'Visitors')) ?>)</span>
            </div>
            <div class="kpi-card-footer" style="margin-top:4px;">
                <span style="color:#38bdf8; font-weight:600;"><?= e(__a('Ort.', 'Avg.')) ?> <?= e($data['devices']['mobile']['duration_fmt'] ?? '—') ?></span>
                <span><?= e(__a('Instagram & X Girişleri', 'Instagram & X Entrances')) ?></span>
            </div>
        </div>

        <!-- Görsel Odaklanma & Hikaye Tamamlama -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-card-title"><?= e(__a('Hikaye & Kare Etkileşimi', 'Story & Frame Engagement')) ?></span>
                <div class="kpi-card-icon" style="background:rgba(16, 185, 129, 0.12); color:var(--success-text);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/><line x1="11" x2="11" y1="8" y2="14"/><line x1="8" x2="14" y1="11" y2="11"/></svg>
                </div>
            </div>
            <div style="display:flex; align-items:baseline; gap:8px;">
                <span class="kpi-card-val" style="font-size:26px;"><?php $ev_by = array_column($data['events'] ?? [], 'count', 'event'); // olayları adıyla eşle (ilk satır page_view olabilir) ?><?= number_format((int)($ev_by['photo_zoom'] ?? 0)) ?></span>
                <span style="font-size:12px; color:var(--text-sub);"><?= e(__a('Kare Büyütme', 'Frame Zoom-In')) ?></span>
            </div>
            <div class="kpi-card-footer" style="margin-top:4px;">
                <span style="color:var(--success-text); font-weight:600;"><?= number_format((int)($ev_by['story_complete'] ?? 0)) ?> <?= e(__a('Hikaye Tamamlama', 'Story Completion')) ?></span>
                <span><?= e(__a('%80+ Okunan Seriler', '80%+ Read Series')) ?></span>
            </div>
        </div>
    </div>

    <!-- ── ZAMAN ÇİZELGESİ TRENDİ (GÜNLÜK AKIŞ) ── -->
    <div class="stats-panel-card">
        <div class="stats-panel-header">
            <span class="stats-panel-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <span><?= e(__a('Ziyaretçi Akışı & Günlük Etkileşim Trendi', 'Visitor Traffic & Daily Engagement Trend')) ?></span>
            </span>
            <span style="font-size:12px; color:var(--text-sub);"><?= e(['7d' => __a('Son 7 Gün', 'Last 7 Days'), '90d' => __a('Son 90 Gün', 'Last 90 Days'), '365d' => __a('Son 1 Yıl', 'Last Year')][$range] ?? __a('Son 30 Gün', 'Last 30 Days')) ?></span>
        </div>
        <div class="stats-panel-body" style="height: 240px; position:relative;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- ── 1. İKİ KOLONLU PANEL: EDİNİM & DÖNÜŞÜM ── -->
    <div class="stats-two-col">

        <!-- Edinim (Kanal): Trafik Kaynağı / Mecra (sessionSource, sessionMedium) -> Pasta Grafik -->
        <div class="stats-panel-card">
            <div class="stats-panel-header">
                <span class="stats-panel-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                    <span><?= e(__a('Edinim: Trafik Kaynağı & Mecra', 'Acquisition: Traffic Source & Medium')) ?></span>
                </span>
                <span style="font-size:12px; color:var(--text-sub);">sessionSource / sessionMedium</span>
            </div>
            <div class="stats-panel-body" style="display:flex; flex-direction:column; gap:16px;">
                <div style="height: 200px; position:relative;">
                    <canvas id="sourcePieChart"></canvas>
                </div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php 
                    $totalSourceSess = array_sum(array_column($data['sources'], 'sessions')) ?: 1;
                    foreach ($data['sources'] as $src): 
                        $pct = round(($src['sessions'] / $totalSourceSess) * 100, 1);
                    ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:12px;">
                            <span style="color:var(--text-main); font-weight:500;"><?= e($src['label']) ?></span>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="stats-progress-wrap" style="width:70px;">
                                    <div class="stats-progress-fill" style="width: <?= $pct ?>%;"></div>
                                </div>
                                <span style="font-family:var(--font-mono); font-size:12px; color:var(--text-sub); width:50px; text-align:right;">%<?= $pct ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Dönüşüm / Aksiyon: İletişim Formu / PDF İndirme (conversions / eventName) -> Çubuk Grafik -->
        <div class="stats-panel-card">
            <div class="stats-panel-header">
                <span class="stats-panel-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
                    <span><?= e(__a('Dönüşüm & Aksiyonlar (Events)', 'Conversions & Actions (Events)')) ?></span>
                </span>
                <span style="font-size:12px; color:var(--text-sub);">conversions / eventName</span>
            </div>
            <div class="stats-panel-body" style="height: 320px; position:relative;">
                <canvas id="eventsBarChart"></canvas>
            </div>
        </div>

    </div>

    <!-- ── 2. İKİ KOLONLU PANEL: İÇERİK POPÜLERLİĞİ & COĞRAFİ ANALİZ ── -->
    <div class="stats-two-col">

        <!-- İçerik Popülerliği: En Çok Gezilen Portfolyolar (screenPageViews / pagePath) -> İlk 10 Liste -->
        <div class="stats-panel-card">
            <div class="stats-panel-header">
                <span class="stats-panel-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span><?= e(__a('En Çok Gezilen Portfolyolar (İlk 10)', 'Most Viewed Portfolios (Top 10)')) ?></span>
                </span>
                <span style="font-size:12px; color:var(--text-sub);">screenPageViews</span>
            </div>
            <div class="stats-panel-body" style="padding:0; overflow-x:auto;">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <th><?= e(__a('Portfolyo / Sayfa', 'Portfolio / Page')) ?></th>
                            <th style="text-align:right;"><?= e(__a('Görüntüleme', 'Views')) ?></th>
                            <th style="text-align:right;"><?= e(__a('Tekil', 'Unique')) ?></th>
                            <th style="width:90px; text-align:right;"><?= e(__a('Pay', 'Share')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $maxViews = !empty($data['pages']) ? max(array_column($data['pages'], 'views')) : 1;
                        foreach ($data['pages'] as $idx => $p): 
                            $ratio = round(($p['views'] / $maxViews) * 100, 1);
                        ?>
                            <tr>
                                <td style="font-family:var(--font-mono); color:var(--text-sub);"><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight:600; color:var(--text-main);"><?= e($p['title']) ?></div>
                                    <div style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono);"><?= e($p['path']) ?></div>
                                </td>
                                <td style="text-align:right; font-weight:700; font-family:var(--font-mono);"><?= number_format($p['views'], 0, ',', '.') ?></td>
                                <td style="text-align:right; font-family:var(--font-mono); color:var(--text-sub);"><?= number_format($p['users'], 0, ',', '.') ?></td>
                                <td style="text-align:right;">
                                    <div class="stats-progress-wrap" style="width:100%;">
                                        <div class="stats-progress-fill" style="width: <?= $ratio ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Coğrafi Analiz: Ülke & Şehir Dağılımı (country, city, activeUsers) -> Liste & Dünya Haritası -->
        <div class="stats-panel-card">
            <div class="stats-panel-header">
                <span class="stats-panel-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                    <span><?= e(__a('Coğrafi Analiz (Ülke & Şehir)', 'Geographic Analysis (Country & City)')) ?></span>
                </span>
                <div class="geo-tab-btns">
                    <button type="button" class="geo-tab-btn active" id="btnTabGeoList" onclick="switchGeoTab('list')"><?= e(__a('Sıralı Liste', 'Ranked List')) ?></button>
                    <button type="button" class="geo-tab-btn" id="btnTabGeoMap" onclick="switchGeoTab('map')"><?= e(__a('Dünya Haritası', 'World Map')) ?></button>
                </div>
            </div>
            <div class="stats-panel-body" style="padding:0;">
                <!-- Sekme 1: Sıralı Liste Tablosu -->
                <div id="geoListContainer" style="display:block; max-height:360px; overflow-y:auto;">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th><?= e(__a('Ülke & Şehir', 'Country & City')) ?></th>
                                <th style="text-align:right;"><?= e(__a('Aktif Kullanıcı', 'Active Users')) ?></th>
                                <th style="width:110px; text-align:right;"><?= e(__a('Dağılım', 'Distribution')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maxGeoUsers = !empty($data['geo']) ? max(array_column($data['geo'], 'users')) : 1;
                            foreach ($data['geo'] as $g): 
                                $geoRatio = round(($g['users'] / $maxGeoUsers) * 100, 1);
                            ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <span style="width:26px; height:26px; border-radius:6px; background:var(--bg-body); display:inline-flex; align-items:center; justify-content:center; font-size:16px; line-height:1; flex-shrink:0;"><?= e($g['flag']) ?></span>
                                            <div>
                                                <div style="font-weight:600; color:var(--text-main);"><?= e($g['city']) ?></div>
                                                <div style="font-size:12px; color:var(--text-sub);"><?= e($g['country']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align:right; font-weight:700; font-family:var(--font-mono);">
                                        <?= number_format($g['users'], 0, ',', '.') ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div class="stats-progress-wrap" style="width:100%;">
                                            <div class="stats-progress-fill" style="width: <?= $geoRatio ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sekme 2: Dünya Haritası Görünümü -->
                <div id="geoMapWrapper" style="display:none; padding:12px;">
                    <div id="geoMapContainer"></div>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ── GOOGLE ANALYTICS API YAPILANDIRMA MODALI ── -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="configModal">
    <div class="modal-dialog">
        <form method="POST" action="stats.php" enctype="multipart/form-data" id="gaConfigForm">
            <input type="hidden" name="action" value="save_ga_config">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="modal-header">
                <span class="modal-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span><?= e(__a('Google Analytics 4 API Yapılandırması', 'Google Analytics 4 API Configuration')) ?></span>
                </span>
                <button type="button" class="btn-close" onclick="closeConfigModal()" style="background:none; border:none; color:var(--text-sub); font-size:18px; cursor:pointer;">&times;</button>
            </div>

            <div class="modal-body">
                <!-- GA4 Property ID -->
                <div>
                    <label class="form-label" style="display:block; font-weight:600; margin-bottom:6px; font-size:13px;">
                        <?= e(__a('GA4 Property (Mülk) ID', 'GA4 Property ID')) ?> <span style="color:var(--danger); font-weight:700;">*</span>
                    </label>
                    <input type="text" name="property_id" id="inputPropertyId" class="form-control" value="<?= e(get_ga_property_id()) ?>" placeholder="<?= e(__a('Örn: 123456789', 'e.g. 123456789')) ?>" required style="width:100%; font-family:var(--font-mono);">
                    <div style="font-size:12px; color:var(--text-sub); margin-top:4px;">
                        <?= e(__a('Google Analytics panelinizdeki Mülk Kimliğidir (Örn: <code>123456789</code>).', 'The Property ID from your Google Analytics dashboard (e.g. <code>123456789</code>).')) ?>
                    </div>
                </div>

                <!-- Google Service Account JSON -->
                <div>
                    <label class="form-label" style="display:block; font-weight:600; margin-bottom:6px; font-size:13px;">
                        <?= e(__a('Google Cloud Service Account JSON Anahtarı', 'Google Cloud Service Account JSON Key')) ?>
                    </label>
                    <textarea name="service_account_json" id="inputSaJson" class="form-control" rows="6" placeholder='{"type": "service_account", "project_id": "...", "private_key": "...", ...}' style="width:100%; font-family:var(--font-mono); font-size:12px;"></textarea>
                    
                    <div style="margin-top:8px;">
                        <span style="font-size:12px; color:var(--text-sub);"><?= e(__a('veya JSON dosyasını seçin: ', 'or select JSON file: ')) ?></span>
                        <input type="file" name="service_account_file" id="inputSaFile" accept=".json" style="font-size:12px; color:var(--text-main);">
                    </div>
                </div>

                <!-- Test Sonucu Bildirim Kutusu -->
                <div id="testResultBox" style="display:none; padding:10px 14px; border-radius:6px; font-size:12px;"></div>

                <!-- Adım Adım Entegrasyon Rehberi -->
                <div style="background:var(--bg-body); padding:14px; border-radius:6px; border:1px solid var(--border-color); font-size:12px; line-height:1.5;">
                    <strong style="color:var(--text-main); display:block; margin-bottom:6px;"><?= e(__a('Canlı Veriyi Aktif Etme Adımları:', 'Steps to Enable Live Data:')) ?></strong>
                    <ol style="margin:0; padding-left:18px; color:var(--text-sub);">
                        <li><?= e(__a('Google Cloud Console\'a gidin ve Google Analytics Data API servisini etkinleştirin.', 'Go to Google Cloud Console and enable the Google Analytics Data API service.')) ?></li>
                        <li><?= e(__a('Service Accounts sekmesinden bir hesap oluşturup JSON Anahtarını indirin.', 'Create a service account under Service Accounts and download the JSON key.')) ?></li>
                        <li><?= e(__a('Google Analytics panelinde Mülk Erişimi Yönetimi\'ne girerek bu e-postayı Görüntüleyici olarak ekleyin.', 'In Google Analytics Property Access Management, add this email as a Viewer.')) ?></li>
                        <li><?= e(__a('İndirdiğiniz JSON dosyasının içeriğini yukarıya yapıştırıp kaydedin.', 'Paste the JSON key content into the box above and save.')) ?></li>
                    </ol>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="runConnectionTest()" id="btnTestConn">
                    <?= e(__a('Bağlantıyı Test Et', 'Test Connection')) ?>
                </button>
                <button type="submit" class="btn btn-primary">
                    <?= e(__a('Kaydet', 'Save')) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── PHP Verilerinin JS Tarafına Aktarımı ──
var trendData = <?= json_encode($data['trend'] ?? []) ?>;
var sourcesData = <?= json_encode($data['sources'] ?? []) ?>;
var eventsData = <?= json_encode($data['events'] ?? []) ?>;
var geoData = <?= json_encode($data['geo'] ?? []) ?>;

var leafletGeoMap = null;

document.addEventListener('DOMContentLoaded', function() {
    initTrendChart();
    initSourcePieChart();
    initEventsBarChart();
});

// ── 1. Ziyaretçi Akışı & Trend Grafiği (Line Chart) ──
function initTrendChart() {
    var ctx = document.getElementById('trendChart');
    if (!ctx) return;

    var labels = trendData.map(function(d) { return d.date; });
    var users = trendData.map(function(d) { return d.users; });
    var pageviews = trendData.map(function(d) { return d.pageviews; });

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: <?= json_encode(__a('Aktif Kullanıcılar', 'Active Users')) ?>,
                    data: users,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#f59e0b'
                },
                {
                    label: <?= json_encode(__a('Sayfa Görüntülemeleri', 'Page Views')) ?>,
                    data: pageviews,
                    borderColor: '#38bdf8',
                    backgroundColor: 'rgba(56, 189, 248, 0.05)',
                    tension: 0.35,
                    fill: true,
                    borderWidth: 1.8,
                    borderDash: [4, 4],
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#38bdf8'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#94a3b8',
                        font: { size: 11, family: "'Space Grotesk', sans-serif" },
                        boxWidth: 12
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1,
                    padding: 10
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.04)' },
                    ticks: { color: '#64748b', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.04)' },
                    ticks: { color: '#64748b', font: { size: 10 } }
                }
            }
        }
    });
}

// ── 2. Edinim Trafik Kaynağı / Mecra (Doughnut Chart) ──
function initSourcePieChart() {
    var ctx = document.getElementById('sourcePieChart');
    if (!ctx) return;

    var labels = sourcesData.map(function(s) { return s.label; });
    var data = sourcesData.map(function(s) { return s.sessions; });
    var colors = ['#f59e0b', '#38bdf8', '#a855f7', '#ec4899', '#10b981', '#64748b', '#94a3b8'];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, data.length),
                borderColor: '#1e293b',
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1,
                    callbacks: {
                        label: function(item) {
                            return ' ' + item.label + ': ' + item.parsed + <?= json_encode(__a(' oturum', ' sessions')) ?>;
                        }
                    }
                }
            }
        }
    });
}

// ── 3. Dönüşüm & Aksiyonlar (Bar Chart) ──
function initEventsBarChart() {
    var ctx = document.getElementById('eventsBarChart');
    if (!ctx) return;

    var labels = eventsData.map(function(e) { return e.label; });
    var data = eventsData.map(function(e) { return e.count; });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: <?= json_encode(__a('Aksiyon Adedi', 'Action Count')) ?>,
                data: data,
                backgroundColor: 'rgba(245, 158, 11, 0.75)',
                hoverBackgroundColor: '#f59e0b',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y', // Yatay Çubuk
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.04)' },
                    ticks: { color: '#64748b', font: { size: 10 } }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: '#cbd5e1', font: { size: 11 } }
                }
            }
        }
    });
}

// ── 4. Coğrafi Analiz Sekme Değişimi & Harita (Leaflet) ──
function switchGeoTab(tab) {
    var btnList = document.getElementById('btnTabGeoList');
    var btnMap  = document.getElementById('btnTabGeoMap');
    var contList = document.getElementById('geoListContainer');
    var wrapMap  = document.getElementById('geoMapWrapper');

    if (tab === 'list') {
        btnList.classList.add('active');
        btnMap.classList.remove('active');
        contList.style.display = 'block';
        wrapMap.style.display = 'none';
    } else {
        btnMap.classList.add('active');
        btnList.classList.remove('active');
        contList.style.display = 'none';
        wrapMap.style.display = 'block';

        setTimeout(initOrUpdateGeoMap, 150);
    }
}

function initOrUpdateGeoMap() {
    var mapEl = document.getElementById('geoMapContainer');
    if (!mapEl) return;

    if (!leafletGeoMap) {
        leafletGeoMap = L.map('geoMapContainer', {
            center: [28.0, 25.0],
            zoom: 2,
            minZoom: 1,
            maxZoom: 12
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(leafletGeoMap);
    } else {
        leafletGeoMap.invalidateSize();
    }

    var bounds = [];
    geoData.forEach(function(g) {
        if (g.lat && g.lng) {
            var size = Math.min(Math.max(Math.round(Math.sqrt(g.users) * 5) + 16, 20), 40);
            var pinHtml = '<div style="background:#f59e0b; color:#000; font-weight:800; border-radius:50%; width:' + size + 'px; height:' + size + 'px; display:flex; align-items:center; justify-content:center; font-size:12px; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,0.4);">' + g.users + '</div>';

            var icon = L.divIcon({
                html: pinHtml,
                className: '',
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2]
            });

            var marker = L.marker([g.lat, g.lng], { icon: icon }).addTo(leafletGeoMap);
            marker.bindPopup(
                '<div style="font-family:\'Space Grotesk\', sans-serif; font-size:12px;">' +
                    '<h4 style="margin:0 0 4px 0; font-size:13px; font-weight:700;">' + g.flag + ' ' + g.city + ', ' + g.country + '</h4>' +
                    '<div style="color:var(--warning-text); font-weight:700;">' + g.users + <?= json_encode(__a(' Aktif Ziyaretçi', ' Active Visitors')) ?> + '</div>' +
                '</div>'
            );
            bounds.push([g.lat, g.lng]);
        }
    });

    if (bounds.length > 0) {
        leafletGeoMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 6 });
    }
}

// ── 5. Modal İşlemleri & AJAX Test ──
function openConfigModal() {
    document.getElementById('configModal').classList.add('open');
}

// Site Ayarları › Entegrasyonlar'dan gelinirse bağlantı penceresini doğrudan aç
if (location.hash === '#baglanti') {
    document.addEventListener('DOMContentLoaded', openConfigModal);
}

function closeConfigModal() {
    document.getElementById('configModal').classList.remove('open');
}

function runConnectionTest() {
    var btn = document.getElementById('btnTestConn');
    var propId = document.getElementById('inputPropertyId').value.trim();
    var saJson = document.getElementById('inputSaJson').value.trim();
    var box = document.getElementById('testResultBox');

    if (!propId) {
        alert(<?= json_encode(__a('Lütfen GA4 Property ID giriniz.', 'Please enter the GA4 Property ID.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
        return;
    }
    if (!saJson) {
        alert(<?= json_encode(__a('Lütfen Service Account JSON metnini yapıştırınız.', 'Please paste the Service Account JSON text.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
        return;
    }

    btn.innerText = 'Test Ediliyor...';
    btn.disabled = true;
    box.style.display = 'none';

    var fd = new FormData();
    fd.append('action', 'test_connection');
    fd.append('csrf_token', '<?= csrf_token() ?>');
    fd.append('property_id', propId);
    fd.append('service_account_json', saJson);

    fetch('stats.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            btn.innerText = <?= json_encode(__a('Bağlantıyı Test Et', 'Test Connection'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            btn.disabled = false;
            box.style.display = 'block';

            if (res.success) {
                box.style.background = 'rgba(16, 185, 129, 0.15)';
                box.style.color = '#10b981';
                box.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                box.innerHTML = '<strong>' + <?= json_encode(__a('Başarılı!', 'Success!'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '</strong> ' + <?= json_encode(__a('Google Analytics 4 API bağlantısı doğrulandı.', 'Google Analytics 4 API connection verified.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + ' (' + res.message + ')';
            } else {
                box.style.background = 'rgba(239, 68, 68, 0.15)';
                box.style.color = '#ef4444';
                box.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                box.innerHTML = '<strong>Hata:</strong> ' + res.message;
            }
        })
        .catch(function(err) {
            btn.innerText = <?= json_encode(__a('Bağlantıyı Test Et', 'Test Connection'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            btn.disabled = false;
            box.style.display = 'block';
            box.style.background = 'rgba(239, 68, 68, 0.15)';
            box.style.color = '#ef4444';
            box.innerHTML = <?= json_encode(__a('Sunucuyla bağlantı hatası oluştu.', 'A server connection error occurred.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        });
}

// Dosya seçildiğinde içeriğini textarea'ya aktarma
var fileInput = document.getElementById('inputSaFile');
if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                document.getElementById('inputSaJson').value = evt.target.result;
            };
            reader.readAsText(file);
        }
    });
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
