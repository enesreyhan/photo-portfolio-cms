<?php
/**
 * admin/convert-images.php
 * Web Tabanlı Toplu AVIF ve WebP Görsel Dönüştürücü
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$base_dir = dirname(__DIR__);
$content_dir = $base_dir . '/content';
$proc_dir = $base_dir . '/processed_images';

// AJAX batch işleme isteği
if (isset($_GET['action']) && $_GET['action'] === 'batch_convert') {
    header('Content-Type: application/json; charset=utf-8');
    
    $offset = (int)($_GET['offset'] ?? 0);
    $limit = 10;

    $iterator = is_dir($content_dir) ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($content_dir)) : [];
    $images = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
            $images[] = $file->getPathname();
        }
    }

    $total = count($images);
    $batch = array_slice($images, $offset, $limit);

    $widths = [600, 920, 1800];
    $converted_count = 0;

    foreach ($batch as $img_path) {
        $fn = pathinfo($img_path, PATHINFO_FILENAME);
        foreach ($widths as $w) {
            resize_and_convert_image($img_path, $w, 'webp', 82);
            resize_and_convert_image($img_path, $w, 'avif', 78);
        }
        $converted_count++;
    }

    $next_offset = $offset + count($batch);
    $done = ($next_offset >= $total);

    echo json_encode([
        'success'   => true,
        'offset'    => $next_offset,
        'total'     => $total,
        'done'      => $done,
        'percent'   => $total > 0 ? round(($next_offset / $total) * 100) : 100
    ]);
    exit;
}

// İstatistikler
$source_count = 0;
if (is_dir($content_dir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($content_dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
            $source_count++;
        }
    }
}

$proc_avif_count = 0;
$proc_webp_count = 0;
if (is_dir($proc_dir)) {
    $files = scandir($proc_dir);
    foreach ($files as $f) {
        if (substr($f, -5) === '.avif') $proc_avif_count++;
        if (substr($f, -5) === '.webp') $proc_webp_count++;
    }
}

$admin_title = __a('Görsel Dönüştürücü', 'Image Converter');
require __DIR__ . '/includes/header.php';
?>

<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('Görsel Dönüştürücü', 'Image Converter')) ?></h1>
        <p class="page-header-sub"><?= e(__a('Yüklenen fotoğrafların hızlı açılan AVIF ve WebP sürümlerini ve küçük boyutlarını üretir.', 'Generates fast-loading AVIF and WebP versions and smaller sizes of uploaded photos.')) ?></p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= e(__a('convert_card_title', 'Görsel Optimizasyon ve Dönüştürme Durumu')) ?></h2>
    </div>
    <div class="card-body">
        <p style="color:var(--text-muted); margin-bottom:24px; max-width:700px; line-height:1.6;">
            <?= e(__a('convert_card_desc', 'Sitenizdeki tüm fotoğraflar modern web standartlarına uygun olarak otomatik olarak hem AVIF hem de WebP formatlarına dönüştürülür. Ziyaretçinin tarayıcısı en uygun formatı seçerek en yüksek kalitede ve en küçük dosya boyutuyla sayfaları anında açar.')) ?>
        </p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:32px;">
            <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:10px; padding:20px;">
                <div style="font-size:12px; color:var(--text-sub); text-transform:uppercase; font-weight:600;"><?= e(__a('convert_stat_orig', 'Orijinal Fotoğraflar')) ?></div>
                <div style="font-size:28px; font-weight:700; color:var(--text-main); margin-top:8px;"><?= $source_count ?></div>
                <div style="font-size:12px; color:var(--text-sub); margin-top:4px;"><?= e(__a('convert_stat_orig_sub', 'content/ klasöründe')) ?></div>
            </div>

            <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:10px; padding:20px;">
                <div style="font-size:12px; color:var(--text-sub); text-transform:uppercase; font-weight:600;"><?= e(__a('convert_stat_avif', 'Hazır AVIF Varyantları')) ?></div>
                <div style="font-size:28px; font-weight:700; color:#34d399; margin-top:8px;"><?= $proc_avif_count ?></div>
                <div style="font-size:12px; color:var(--text-sub); margin-top:4px;">600w, 920w, 1800w, 2560w</div>
            </div>

            <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:10px; padding:20px;">
                <div style="font-size:12px; color:var(--text-sub); text-transform:uppercase; font-weight:600;"><?= e(__a('convert_stat_webp', 'Hazır WebP Varyantları')) ?></div>
                <div style="font-size:28px; font-weight:700; color:#60a5fa; margin-top:8px;"><?= $proc_webp_count ?></div>
                <div style="font-size:12px; color:var(--text-sub); margin-top:4px;">600w, 920w, 1800w</div>
            </div>
        </div>

        <div id="progressContainer" style="display:none; margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; font-weight:600; margin-bottom:8px;">
                <span id="progressText"><?= e(__a('convert_progress_converting', 'Dönüştürülüyor...')) ?></span>
                <span id="progressPercent">0%</span>
            </div>
            <div style="background:var(--bg-canvas-subtle); border:1px solid var(--border-subtle); border-radius:6px; height:12px; overflow:hidden;">
                <div id="progressBar" style="background:linear-gradient(90deg, #10b981, #3b82f6); width:0%; height:100%; transition:width 0.2s ease;"></div>
            </div>
        </div>

        <button type="button" id="startConvertBtn" class="btn btn-primary" onclick="startBatchConversion()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            <span><?= e(__a('convert_btn_generate', 'Eksik AVIF ve WebP Görsellerini Oluştur')) ?></span>
        </button>
    </div>
</div>

<script>
var totalImages = <?= $source_count ?>;
var isRunning = false;

function startBatchConversion() {
    if (isRunning) return;
    isRunning = true;

    var btn = document.getElementById('startConvertBtn');
    btn.disabled = true;
    btn.innerHTML = '<span>' + <?= json_encode(__a('convert_js_processing', 'İşleniyor, lütfen bekleyin...')) ?> + '</span>';

    document.getElementById('progressContainer').style.display = 'block';
    processBatch(0);
}

function processBatch(offset) {
    fetch('convert-images.php?action=batch_convert&offset=' + offset)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var pct = data.percent;
                document.getElementById('progressBar').style.width = pct + '%';
                document.getElementById('progressPercent').textContent = pct + '%';
                document.getElementById('progressText').textContent = data.offset + ' / ' + data.total + ' ' + <?= json_encode(__a('convert_js_photos_processed', 'fotoğraf işlendi...')) ?>;

                if (!data.done) {
                    processBatch(data.offset);
                } else {
                    document.getElementById('progressText').textContent = <?= json_encode(__a('convert_js_all_done', 'Tüm fotoğraflar başarıyla AVIF ve WebP olarak hazırlandı!')) ?>;
                    var btn = document.getElementById('startConvertBtn');
                    btn.disabled = false;
                    btn.innerHTML = '<span>' + <?= json_encode(__a('convert_js_rescan', 'Yeniden Tara ve Güncelle')) ?> + '</span>';
                    isRunning = false;
                }
            } else {
                alert(<?= json_encode(__a('convert_js_err', 'Dönüştürme sırasında bir hata oluştu.')) ?>);
                isRunning = false;
            }
        })
        .catch(function() {
            alert(<?= json_encode(__a('convert_js_net_err', 'Sunucu iletişim hatası.')) ?>);
            isRunning = false;
        });
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
