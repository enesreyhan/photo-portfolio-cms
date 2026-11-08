<?php
/**
 * admin/legal.php
 * KVKK Aydınlatma Metni ve Açık Rıza Metni Yönetimi (Görsel Zengin Editör / WYSIWYG)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/legal_defaults.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

// ── Form İşlemleri ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Güvenlik doğrulaması başarısız (CSRF). Sayfayı yenileyip tekrar deneyin.');
        header('Location: legal.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'legal') {
        // Sitedeki her dil: kvkk_aydinlatma_metni_{dil}, kvkk_acik_riza_metni_{dil}
        save_settings_l(['kvkk_aydinlatma_metni', 'kvkk_acik_riza_metni'], $_POST);

        set_flash('success', 'KVKK Aydınlatma Metni ve Açık Rıza Metni başarıyla kaydedildi.');
        
        $active_doc = in_array($_POST['active_doc'] ?? '', ['aydinlatma', 'acik_riza']) ? $_POST['active_doc'] : 'aydinlatma';
        $active_lang = in_array($_POST['active_lang'] ?? '', array_column(get_active_languages(), 'code'), true) ? $_POST['active_lang'] : 'tr';
        header("Location: legal.php?doc={$active_doc}&lang={$active_lang}");
        exit;
    } elseif ($action === 'reset_legal') {
        set_setting('kvkk_aydinlatma_metni_tr', get_default_kvkk_aydinlatma_tr());
        set_setting('kvkk_aydinlatma_metni_en', get_default_kvkk_aydinlatma_en());
        set_setting('kvkk_acik_riza_metni_tr', get_default_kvkk_acik_riza_tr());
        set_setting('kvkk_acik_riza_metni_en', get_default_kvkk_acik_riza_en());

        set_flash('success', 'Yasal metinler 6698 sayılı KVKK standart şablonuna başarıyla sıfırlandı.');
        header('Location: legal.php');
        exit;
    }
}

// Mevcut İçerikleri Yükle — Türkçe/İngilizcede hazır metin, diğer dillerde yalnızca kayıtlı çeviri
// (boşsa sitede yedek dildeki metin görünür; düzenleyicide boş gelir ki çevrilmediği belli olsun)
$legal_langs = array_column(get_active_languages(), 'code');
$legal_content = [];
foreach ($legal_langs as $c) {
    foreach (['aydinlatma' => 'ayd', 'acik_riza' => 'riz'] as $type => $px) {
        $legal_content[$px][$c] = in_array($c, ['tr', 'en'], true)
            ? get_legal_content($type, $c)
            : (string)get_setting("kvkk_{$type}_metni_{$c}", '');
    }
}

$initial_doc = in_array($_GET['doc'] ?? '', ['aydinlatma', 'acik_riza']) ? $_GET['doc'] : 'aydinlatma';
$initial_lang = in_array($_GET['lang'] ?? '', $legal_langs, true) ? $_GET['lang'] : (in_array($admin_lang ?? 'tr', $legal_langs, true) ? ($admin_lang ?? 'tr') : 'tr');

$admin_title = __a('Hukuki Metinler', 'Legal Texts');
$breadcrumb_root = __a('bc_system', 'Sistem & Ayarlar');
$breadcrumb_page = __a('legal_page_breadcrumb', 'Yasal Metinler');

require __DIR__ . '/includes/header.php';
?>

<style>
/* Zengin Editör ve Sayfa Yönetimi Stilleri */
.legal-editor-wrap {
    margin-bottom: 28px;
}
.legal-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

/* Doküman Ana Sekmeleri (1. Aydınlatma vs 2. Açık Rıza) */
.doc-tabs-bar {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid var(--border-subtle);
    padding-bottom: 14px;
    margin-bottom: 20px;
}
.doc-tab-btn {
    padding: 8px 18px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    background: transparent;
    color: var(--text-muted);
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.doc-tab-btn:hover {
    color: var(--text-main);
    background: var(--bg-hover);
}
.doc-tab-btn.active {
    background: var(--primary);
    color: #ffffff;
    border-color: var(--primary);
}

/* Dil Alt Sekmeleri */
.lang-sub-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}
.lang-pills {
    display: flex;
    background: var(--bg-canvas-subtle);
    padding: 3px;
    border-radius: 6px;
    border: 1px solid var(--border-subtle);
}
.lang-pill-btn {
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: transparent;
    color: var(--text-sub);
    transition: all 0.12s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.lang-pill-btn:hover {
    color: var(--text-main);
}
.lang-pill-btn.active {
    background: var(--bg-surface);
    color: var(--text-main);
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

/* Zengin Editör Araç Çubuğu (WYSIWYG) */
.rich-editor-box {
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    background: var(--bg-surface);
    overflow: hidden;
    transition: all 0.15s ease;
}
.rich-editor-box:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}
.rich-toolbar {
    background: var(--bg-canvas-subtle);
    border-bottom: 1px solid var(--border-subtle);
    padding: 7px 10px;
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
    padding: 20px 24px;
    min-height: 480px;
    max-height: 720px;
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
.rich-editable h1,
.rich-editable h2,
.rich-editable h3,
.rich-editable h4 {
    font-family: inherit;
    font-weight: 700;
    color: var(--text-main);
    margin: 22px 0 10px 0;
}
.rich-editable h1 { font-size: 20px; }
.rich-editable h2 { font-size: 16px; }
.rich-editable h3 { font-size: 16px; }
.rich-editable h4 { font-size: 14px; }
.rich-editable ul,
.rich-editable ol {
    margin: 0 0 14px 24px;
    padding: 0;
}
.rich-editable li {
    margin-bottom: 5px;
}
.rich-editable a {
    color: var(--primary, #0284c7);
    text-decoration: underline;
}
.rich-editable hr {
    border: none;
    border-top: 1px solid var(--border-subtle);
    margin: 20px 0;
}
.rich-editable strong {
    font-weight: 700;
}
.rich-editable:empty::before {
    content: attr(data-placeholder);
    color: var(--text-sub);
    pointer-events: none;
    display: block;
}

/* Ham HTML Textarea */
.raw-html-area {
    display: none;
    width: 100%;
    min-height: 480px;
    padding: 16px 20px;
    font-family: var(--font-mono);
    font-size: 13px;
    line-height: 1.6;
    border: none;
    outline: none;
    background: var(--bg-surface-2);
    color: var(--text-main);
    resize: vertical;
    box-sizing: border-box;
}
[data-theme="dark"] .raw-html-area {
    background: var(--bg-canvas-subtle);
    color: var(--text-main);
}
</style>

<!-- ── Sayfa Başlık Bandı ── -->
<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('Hukuki Metinler', 'Legal Texts')) ?></h1>
        <p class="page-header-sub"><?= e(__a('legal_page_sub', 'Sitede yayınlanan Aydınlatma Metni ve Açık Rıza Metnini zengin metin editörüyle düzenleyin.')) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="settings.php#gizlilik" class="btn btn-secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            <span><?= e(__a('back_to_settings', 'Site Ayarlarına Dön')) ?></span>
        </a>
        <button type="button" class="btn btn-primary" onclick="submitLegalForm()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?= e(__a('Kaydet', 'Save')) ?></span>
        </button>
    </div>
</div>

<?php
// Belge sekmelerinin adı seçili içerik dilinde (Sayfa Adları ayarı, yoksa o dilin çevirisi)
$legal_doc_labels = ['ayd' => [], 'riz' => []];
foreach ($legal_langs as $c) {
    $legal_doc_labels['ayd'][$c] = get_page_custom_title('privacy_notice', $c, site_t('Aydınlatma Metni', 'Privacy Notice', $c));
    $legal_doc_labels['riz'][$c] = get_page_custom_title('explicit_consent', $c, site_t('Açık Rıza Metni', 'Explicit Consent', $c));
}
?>
<script>
document.addEventListener('er:langbar', function (e) {
    var L = <?= json_encode($legal_doc_labels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>, lang = e.detail && e.detail.lang;
    document.querySelectorAll('[data-doc-label]').forEach(function (el) {
        var t = L[el.dataset.docLabel] && L[el.dataset.docLabel][lang];
        if (t) el.textContent = el.textContent.split('. ')[0] + '. ' + t;
    });
});
</script>
<div class="legal-editor-wrap">
    <div class="legal-card">
        
        <!-- 1. Doküman Seçimi (Aydınlatma vs Açık Rıza) -->
        <div class="doc-tabs-bar">
            <button type="button" class="doc-tab-btn <?= $initial_doc === 'aydinlatma' ? 'active' : '' ?>" id="docTab_aydinlatma" onclick="switchDoc('aydinlatma')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h6"/></svg>
                <span data-doc-label="ayd">1. <?= e($legal_doc_labels['ayd'][$initial_lang] ?? '') ?></span>
            </button>
            <button type="button" class="doc-tab-btn <?= $initial_doc === 'acik_riza' ? 'active' : '' ?>" id="docTab_acik_riza" onclick="switchDoc('acik_riza')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span data-doc-label="riz">2. <?= e($legal_doc_labels['riz'][$initial_lang] ?? '') ?></span>
            </button>
        </div>

        <!-- 2. Form Gövdesi -->
        <form method="POST" action="legal.php" id="legalForm" class="save-form">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="legal">
            <input type="hidden" name="active_doc" id="activeDocInput" value="<?= e($initial_doc) ?>">
            <input type="hidden" name="active_lang" id="activeLangInput" value="<?= e($initial_lang) ?>">

            <!-- Gizli Girdi Alanları (Gönderim esnasında senkronize edilir) -->
            <?php foreach ($legal_langs as $c): ?>
            <input type="hidden" name="kvkk_aydinlatma_metni_<?= e($c) ?>" id="hidden_ayd_<?= e($c) ?>">
            <input type="hidden" name="kvkk_acik_riza_metni_<?= e($c) ?>" id="hidden_riz_<?= e($c) ?>">
            <?php endforeach; ?>
            <?php lang_bar(['scope' => '#legalForm', 'initial' => $initial_lang]); ?>

            <!-- ── PANEL 1: AYDINLATMA METNİ ── -->
            <div id="panel_aydinlatma" style="<?= $initial_doc === 'aydinlatma' ? 'display:block;' : 'display:none;' ?>">
                
                <?php lb_panes(function ($c, $l) use ($legal_content, $initial_lang) { ?>
                    <div class="lang-sub-bar" style="justify-content:flex-end;">
                        <a href="<?= e(url($c . '/' . get_static_slug('privacy_notice', $c) . '/')) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:5px;">
                            <span><?= __a('Önizle', 'Preview') ?>: /<?= e($c) ?>/<?= e(get_static_slug('privacy_notice', $c)) ?>/ ↗</span>
                        </a>
                    </div>
                    <?php render_rich_editor_component('ayd_' . $c, $legal_content['ayd'][$c], $c === 'tr' ? 'Aydınlatma metninizi buraya yazın veya biçimlendirin...' : ($c === 'en' ? 'Write or format your privacy notice here...' : sprintf(__a('%s metni henüz yok. Boş kalırsa sitede %s metin görünür.', 'No %s text yet. If left empty, the %s text is shown on the site.'), $l['name'], strtoupper($l['fallback'])))); ?>
                <?php }); ?>
            </div>

            <!-- ── PANEL 2: AÇIK RIZA METNİ ── -->
            <div id="panel_acik_riza" style="<?= $initial_doc === 'acik_riza' ? 'display:block;' : 'display:none;' ?>">
                
                <?php lb_panes(function ($c, $l) use ($legal_content, $initial_lang) { ?>
                    <div class="lang-sub-bar" style="justify-content:flex-end;">
                        <a href="<?= e(url($c . '/' . get_static_slug('explicit_consent', $c) . '/')) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:5px;">
                            <span><?= __a('Önizle', 'Preview') ?>: /<?= e($c) ?>/<?= e(get_static_slug('explicit_consent', $c)) ?>/ ↗</span>
                        </a>
                    </div>
                    <?php render_rich_editor_component('riz_' . $c, $legal_content['riz'][$c], $c === 'tr' ? 'Açık rıza metninizi buraya yazın veya biçimlendirin...' : ($c === 'en' ? 'Write or format your explicit consent statement here...' : sprintf(__a('%s metni henüz yok. Boş kalırsa sitede %s metin görünür.', 'No %s text yet. If left empty, the %s text is shown on the site.'), $l['name'], strtoupper($l['fallback'])))); ?>
                <?php }); ?>
            </div>

            <!-- Alt Butonlar -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:18px; border-top:1px solid var(--border-subtle); flex-wrap:wrap; gap:12px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="confirmResetLegal()" style="color:var(--danger-text); border-color:var(--danger-border);">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                    <span>Varsayılan Yasal Şablona Sıfırla</span>
                </button>
                <div style="display:flex; gap:10px;">
                    <a href="settings.php#gizlilik" class="btn btn-secondary">Vazgeç</a>
                    <button type="submit" class="btn btn-primary" style="min-width:140px;">
                        <span><?= e(__a('Kaydet', 'Save')) ?></span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Sıfırlama Formu -->
        <form method="POST" action="legal.php" id="resetLegalForm" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="reset_legal">
        </form>

    </div>
</div>

<?php
/**
 * Zengin Metin Editörü Bileşeni Çıktısı
 */
function render_rich_editor_component($key, $initial_content, $placeholder) {
    ?>
    <div class="rich-editor-box">
        <!-- Araç Çubuğu (Toolbar) -->
        <div class="rich-toolbar">
            <!-- Format Dropdown (P, H2, H3, H4) -->
            <select class="rt-select" onchange="execRichBlock('<?= $key ?>', this.value); this.value='';" title="Başlık ve Paragraf Formatı">
                <option value="">Biçim...</option>
                <option value="p">Normal Paragraf</option>
                <option value="h2">Ana Başlık (H2)</option>
                <option value="h3">Alt Başlık (H3)</option>
                <option value="h4">Küçük Başlık (H4)</option>
            </select>

            <div class="rt-sep"></div>

            <!-- Kalın, İtalik, Altı Çizili, Üstü Çizili -->
            <button type="button" class="rt-btn rt-bold" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'bold')" title="Kalın (Ctrl+B)"><strong>B</strong></button>
            <button type="button" class="rt-btn rt-italic" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'italic')" title="İtalik (Ctrl+I)"><em>I</em></button>
            <button type="button" class="rt-btn rt-underline" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'underline')" title="Altı Çizili (Ctrl+U)"><u>U</u></button>
            <button type="button" class="rt-btn rt-strike" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'strikeThrough')" title="Üstü Çizili"><s>S</s></button>

            <div class="rt-sep"></div>

            <!-- Hizalamalar -->
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'justifyLeft')" title="Sola Yasla">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
            </button>
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'justifyCenter')" title="Ortala">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            </button>
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'justifyRight')" title="Sağa Yasla">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
            </button>
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'justifyFull')" title="İki Yana Yasla">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
            </button>

            <div class="rt-sep"></div>

            <!-- Listeler & Çizgi -->
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'insertUnorderedList')" title="Madde İmleri Listesi">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1.5" fill="currentColor"/><circle cx="3.5" cy="12" r="1.5" fill="currentColor"/><circle cx="3.5" cy="18" r="1.5" fill="currentColor"/></svg>
            </button>
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'insertOrderedList')" title="Numaralı Liste">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/></svg>
            </button>
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'insertHorizontalRule')" title="Ayırıcı Yatay Çizgi">―</button>

            <div class="rt-sep"></div>

            <!-- Link Ekle / Kaldır -->
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichLink('<?= $key ?>')" title="Bağlantı / Link Ekle">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </button>
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'unlink')" title="Bağlantıyı Kaldır">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.16 11.75-1.72 1.71a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
            </button>

            <div class="rt-sep"></div>

            <!-- Biçimlendirmeyi Temizle -->
            <button type="button" class="rt-btn" onmousedown="event.preventDefault();" onclick="execRichCmd('<?= $key ?>', 'removeFormat')" title="Biçimlendirmeyi Temizle">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/></svg>
            </button>

            <!-- HTML Kaynak Kod Modu Geçişi -->
            <button type="button" class="rt-btn" onclick="toggleHtmlMode('<?= $key ?>')" style="margin-left:auto; font-size:12px; background:var(--bg-canvas-subtle); border-color:var(--border-subtle); color:var(--text-main); display:inline-flex; align-items:center; gap:5px;" title="HTML Kaynak Kodu ile Zengin Editör Arasında Geçiş Yap">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                <span id="toggleText_<?= $key ?>">Kaynak Kodu</span>
            </button>
        </div>

        <!-- Görsel İçerik Alanı -->
        <div class="rich-editable" id="editable_<?= $key ?>" contenteditable="true" data-placeholder="<?= e($placeholder) ?>"><!--noi18n--><?= $initial_content ?><!--/noi18n--></div>

        <!-- Ham HTML Kaynak Alanı -->
        <textarea id="raw_textarea_<?= $key ?>" class="raw-html-area"></textarea>
    </div>
    <?php
}
?>

<script>
var currentDoc = '<?= e($initial_doc) ?>';
var currentLang = '<?= e($initial_lang) ?>';
var LEGAL_LANGS = <?= json_encode($legal_langs) ?>;
var htmlMode = {};
LEGAL_LANGS.forEach(function (c) { htmlMode['ayd_' + c] = false; htmlMode['riz_' + c] = false; });
// Ortak dil çubuğunda dil değişince kaydettikten sonra aynı dile dönülsün
document.addEventListener('er:langbar', function (e) { currentLang = e.detail.lang; var i = document.getElementById('activeLangInput'); if (i) i.value = e.detail.lang; });

// Doküman Geçişi (Aydınlatma vs Açık Rıza)
function switchDoc(doc) {
    currentDoc = doc;
    document.getElementById('activeDocInput').value = doc;

    document.querySelectorAll('.doc-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    var activeBtn = document.getElementById('docTab_' + doc);
    if (activeBtn) activeBtn.classList.add('active');

    document.getElementById('panel_aydinlatma').style.display = (doc === 'aydinlatma') ? 'block' : 'none';
    document.getElementById('panel_acik_riza').style.display = (doc === 'acik_riza') ? 'block' : 'none';
}

// Dil Geçişi (TR vs EN)
function switchLang(doc, lang) {
    currentLang = lang;
    document.getElementById('activeLangInput').value = lang;

    var prefix = (doc === 'aydinlatma') ? 'ayd' : 'riz';

    // Buton aktiflik durumu
    var btnTr = document.getElementById('langBtn_' + prefix + '_tr');
    var btnEn = document.getElementById('langBtn_' + prefix + '_en');
    if (btnTr && btnEn) {
        if (lang === 'tr') {
            btnTr.classList.add('active');
            btnEn.classList.remove('active');
        } else {
            btnEn.classList.add('active');
            btnTr.classList.remove('active');
        }
    }

    // Editör göster/gizle
    var wrapTr = document.getElementById('editorWrap_' + prefix + '_tr');
    var wrapEn = document.getElementById('editorWrap_' + prefix + '_en');
    if (wrapTr && wrapEn) {
        wrapTr.style.display = (lang === 'tr') ? 'block' : 'none';
        wrapEn.style.display = (lang === 'en') ? 'block' : 'none';
    }

    // Önizleme linki değiştir
    var previewTr = document.getElementById('previewLink_' + prefix + '_tr');
    var previewEn = document.getElementById('previewLink_' + prefix + '_en');
    if (previewTr && previewEn) {
        previewTr.style.display = (lang === 'tr') ? 'inline-flex' : 'none';
        previewEn.style.display = (lang === 'en') ? 'inline-flex' : 'none';
    }
}

// ── Zengin Editör Komutları ──
function execRichCmd(key, cmd, val) {
    if (htmlMode[key]) return;
    var el = document.getElementById('editable_' + key);
    if (!el) return;
    el.focus();
    document.execCommand(cmd, false, val || null);
}

function execRichBlock(key, tag) {
    if (!tag || htmlMode[key]) return;
    var el = document.getElementById('editable_' + key);
    if (!el) return;
    el.focus();
    document.execCommand('formatBlock', false, '<' + tag + '>');
}

function execRichLink(key) {
    if (htmlMode[key]) return;
    var el = document.getElementById('editable_' + key);
    if (!el) return;
    el.focus();

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

function toggleHtmlMode(key) {
    var editable = document.getElementById('editable_' + key);
    var textarea = document.getElementById('raw_textarea_' + key);
    var btnText = document.getElementById('toggleText_' + key);

    if (!htmlMode[key]) {
        // Zengin Moddan HTML Moduna geçiş
        textarea.value = editable.innerHTML.trim();
        editable.style.display = 'none';
        textarea.style.display = 'block';
        btnText.textContent = 'Görsel Editör';
        htmlMode[key] = true;
    } else {
        // HTML Modundan Zengin Moda dönüş
        editable.innerHTML = textarea.value;
        textarea.style.display = 'none';
        editable.style.display = 'block';
        btnText.textContent = 'Kaynak Kodu';
        htmlMode[key] = false;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Form Gönderme Hazırlığı
function submitLegalForm() {
    var keys = [];
    LEGAL_LANGS.forEach(function (c) { keys.push('ayd_' + c, 'riz_' + c); });
    keys.forEach(function(k) {
        var content = '';
        if (htmlMode[k]) {
            content = document.getElementById('raw_textarea_' + k).value;
        } else {
            content = document.getElementById('editable_' + k).innerHTML;
        }
        document.getElementById('hidden_' + k).value = content;
    });

    document.getElementById('legalForm').submit();
}

function confirmResetLegal() {
    adminConfirm('Yasal metinlerde yaptığınız bütün düzenlemeler silinir ve metinler 6698 sayılı KVKK standart şablonuna döner.', { title: 'Yasal metinler sıfırlansın mı?', ok: 'Şablona sıfırla' }).then(function (ok) {
        if (ok) document.getElementById('resetLegalForm').submit();
    });
}

// Klavye Kısayolu (Ctrl+S / Cmd+S)
window.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        submitLegalForm();
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
