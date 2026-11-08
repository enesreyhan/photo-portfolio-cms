<?php
/**
 * admin/translations.php
 * Merkezi Çeviri Stüdyosu (Yan Yana Toplu Çeviri Matrisi)
 * Tüm dillerdeki (TR | EN | HY | KU | FR | DE vb.) portfolyo başlıklarını, açıklamalarını,
 * kategorileri, menüleri, ana sayfa seçkisini ve site SEO metinlerini tek tabloda yan yana düzenleme
 * ve tek tıkla otomatik çevirme merkezi.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$all_languages = get_all_languages(true);
foreach ($all_languages as $al) {
    ensure_language_columns($al['code']);
}

$section = $_GET['section'] ?? ($_POST['section'] ?? 'items');
$valid_sections = ['items', 'categories', 'menus', 'slides', 'settings'];
if (!in_array($section, $valid_sections, true)) {
    $section = 'items';
}

// ── TOPLU KAYDETME İŞLEMİ (POST) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        flash('error', 'Güvenlik doğrulaması başarısız (CSRF).');
        redirect('admin/translations.php?section=' . urlencode($section));
    }

    $rows = $_POST['rows'] ?? [];
    $saved_count = 0;

    if ($section === 'items' && is_array($rows)) {
        foreach ($rows as $id => $lang_fields) {
            $id = (int)$id;
            if ($id <= 0 || !is_array($lang_fields)) continue;
            $sets = [];
            $vals = [];
            foreach ($all_languages as $l) {
                $lc = $l['code'];
                if (isset($lang_fields['title_' . $lc])) {
                    $sets[] = "`title_{$lc}` = ?";
                    $vals[] = trim((string)$lang_fields['title_' . $lc]);
                }
                if (isset($lang_fields['slug_' . $lc])) {
                    $raw_s = trim((string)$lang_fields['slug_' . $lc]);
                    $sets[] = "`slug_{$lc}` = ?";
                    $vals[] = $raw_s !== '' ? slugify($raw_s) : '';
                }
                if (isset($lang_fields['description_' . $lc])) {
                    $sets[] = "`description_{$lc}` = ?";
                    $vals[] = trim((string)$lang_fields['description_' . $lc]);
                }
                if (isset($lang_fields['location_' . $lc])) {
                    $sets[] = "`location_{$lc}` = ?";
                    $vals[] = trim((string)$lang_fields['location_' . $lc]);
                }
            }
            if (!empty($sets)) {
                $vals[] = $id;
                $sql = "UPDATE `items` SET " . implode(', ', $sets) . " WHERE `id` = ?";
                try {
                    $pdo->prepare($sql)->execute($vals);
                    $saved_count++;
                } catch (\Throwable $e) {}
            }
        }
    } elseif ($section === 'categories' && is_array($rows)) {
        foreach ($rows as $id => $lang_fields) {
            $id = (int)$id;
            if ($id <= 0 || !is_array($lang_fields)) continue;
            $sets = [];
            $vals = [];
            foreach ($all_languages as $l) {
                $lc = $l['code'];
                if (isset($lang_fields['name_' . $lc])) {
                    $sets[] = "`name_{$lc}` = ?";
                    $vals[] = trim((string)$lang_fields['name_' . $lc]);
                }
                if (isset($lang_fields['slug_' . $lc])) {
                    $raw_s = trim((string)$lang_fields['slug_' . $lc]);
                    $sets[] = "`slug_{$lc}` = ?";
                    $vals[] = $raw_s !== '' ? slugify($raw_s) : '';
                }
            }
            if (!empty($sets)) {
                $vals[] = $id;
                $sql = "UPDATE `categories` SET " . implode(', ', $sets) . " WHERE `id` = ?";
                try {
                    $pdo->prepare($sql)->execute($vals);
                    $saved_count++;
                } catch (\Throwable $e) {}
            }
        }
    } elseif ($section === 'menus' && is_array($rows)) {
        foreach ($rows as $id => $lang_fields) {
            $id = (int)$id;
            if ($id <= 0 || !is_array($lang_fields)) continue;
            $sets = [];
            $vals = [];
            foreach ($all_languages as $l) {
                $lc = $l['code'];
                if (isset($lang_fields['title_' . $lc])) {
                    $sets[] = "`title_{$lc}` = ?";
                    $vals[] = trim((string)$lang_fields['title_' . $lc]);
                }
                if (isset($lang_fields['url_' . $lc])) {
                    $sets[] = "`url_{$lc}` = ?";
                    $vals[] = trim((string)$lang_fields['url_' . $lc]);
                }
            }
            if (!empty($sets)) {
                $vals[] = $id;
                $sql = "UPDATE `menus` SET " . implode(', ', $sets) . " WHERE `id` = ?";
                try {
                    $pdo->prepare($sql)->execute($vals);
                    $saved_count++;
                } catch (\Throwable $e) {}
            }
        }
    } elseif ($section === 'slides' && is_array($rows)) {
        foreach ($rows as $id => $lang_fields) {
            $id = (int)$id;
            if ($id <= 0 || !is_array($lang_fields)) continue;
            $sets = [];
            $vals = [];
            foreach ($all_languages as $l) {
                $lc = $l['code'];
                if (isset($lang_fields['title_' . $lc])) {
                    $sets[] = "`title_{$lc}` = ?";
                    $vals[] = trim((string)$lang_fields['title_' . $lc]);
                }
                if (isset($lang_fields['description_' . $lc])) {
                    $sets[] = "`description_{$lc}` = ?";
                    $vals[] = trim((string)$lang_fields['description_' . $lc]);
                }
            }
            if (!empty($sets)) {
                $vals[] = $id;
                $sql = "UPDATE `homepage_slides` SET " . implode(', ', $sets) . " WHERE `id` = ?";
                try {
                    $pdo->prepare($sql)->execute($vals);
                    $saved_count++;
                } catch (\Throwable $e) {}
            }
        }
    } elseif ($section === 'settings' && is_array($rows)) {
        foreach ($rows as $base_key => $lang_vals) {
            if (!is_array($lang_vals)) continue;
            foreach ($all_languages as $l) {
                $lc = $l['code'];
                if (isset($lang_vals[$lc])) {
                    set_setting($base_key . '_' . $lc, trim((string)$lang_vals[$lc]));
                    $saved_count++;
                }
            }
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'saved_count' => $saved_count]);
        exit;
    }

    flash('success', 'Merkezi Çeviri Stüdyosu değişiklikleri başarıyla kaydedildi (' . $saved_count . ' kayıt güncellendi).');
    redirect('admin/translations.php?section=' . urlencode($section) . (isset($_GET['target']) ? '&target=' . urlencode((string)$_GET['target']) : ''));
}

// ── İLGİLİ BÖLÜMÜN VERİLERİNİ ÇEK ──
$data_rows = [];
if ($section === 'items') {
    $data_rows = $pdo->query("SELECT * FROM `items` WHERE `type` != 'page' ORDER BY `type` ASC, `weight` ASC, `id` DESC")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($section === 'categories') {
    $data_rows = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($section === 'menus') {
    $data_rows = $pdo->query("SELECT * FROM `menus` WHERE `is_separator` = 0 ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($section === 'slides') {
    $data_rows = $pdo->query("SELECT * FROM `homepage_slides` ORDER BY `weight` ASC, `id` DESC")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($section === 'settings') {
    $data_rows = [
        ['key' => 'site_title',          'label' => 'Site Ana Başlığı (SEO Title)',        'default_tr' => site_owner_name(), 'default_en' => site_owner_name()],
        ['key' => 'site_desc',           'label' => 'Site Meta Açıklaması (SEO Desc)',     'default_tr' => '', 'default_en' => ''],
        ['key' => 'header_logo_subtext', 'label' => 'Üst Logo Alt Başlığı (Subtext)',      'default_tr' => '', 'default_en' => ''],
        ['key' => 'header_cta_text',     'label' => 'Üst Menü İletişim (CTA) Butonu',      'default_tr' => 'İletişim', 'default_en' => 'Contact'],
        ['key' => 'footer_brand_title',  'label' => 'Alt Bilgi (Footer) Marka Başlığı',    'default_tr' => site_owner_name(), 'default_en' => site_owner_name()],
        ['key' => 'footer_tagline',      'label' => 'Alt Bilgi (Footer) Slogan / Tagline', 'default_tr' => '', 'default_en' => ''],
    ];
}

// ── GÖRÜNÜM: tek hedef dil (kaynak solda, çevrilecek dil sağda) ya da hepsi yan yana ──
$tr_codes = array_column($all_languages, 'code');
$tr_main  = in_array($admin_lang, $tr_codes, true) ? $admin_lang : ($tr_codes[0] ?? 'tr');
$target   = (string)($_GET['target'] ?? '');
if ($target !== 'all' && !in_array($target, $tr_codes, true)) {
    $others = array_values(array_diff($tr_codes, [$tr_main]));
    $target = $others[0] ?? 'all';
}
$tr_by_code = [];
foreach ($all_languages as $l) $tr_by_code[$l['code']] = $l;
$source = '';
if ($target === 'all') {
    $view_langs = $all_languages;
} else {
    $fb = (string)($tr_by_code[$target]['fallback_lang'] ?? '');
    $source = ($fb !== '' && $fb !== $target && isset($tr_by_code[$fb])) ? $fb : ($target !== $tr_main ? $tr_main : (string)(array_values(array_diff($tr_codes, [$target]))[0] ?? $target));
    $view_langs = $source !== $target ? [$tr_by_code[$source], $tr_by_code[$target]] : [$tr_by_code[$target]];
}
// Satır, hedef dilde eksik mi? (kaynakta dolu olup hedefte boş bir çevrilebilir alan)
$tr_fields = ['items' => ['title', 'description', 'location'], 'categories' => ['name'], 'menus' => ['title'], 'slides' => ['title', 'description']][$section] ?? [];
$row_missing = function ($row) use ($section, $tr_fields, $source, $target): bool {
    if ($target === 'all' || $source === '') return false;
    if ($section === 'settings') {
        return trim((string)get_setting($row['key'] . '_' . $source, '')) !== '' && trim((string)get_setting($row['key'] . '_' . $target, '')) === '';
    }
    foreach ($tr_fields as $f) {
        if (trim((string)($row[$f . '_' . $source] ?? '')) !== '' && trim((string)($row[$f . '_' . $target] ?? '')) === '') return true;
    }
    return false;
};
// Eksikler üstte (sıra kendi içinde korunur)
$missing_count = 0;
if ($target !== 'all') {
    $with = $without = [];
    foreach ($data_rows as $r) { if ($row_missing($r)) { $with[] = $r; } else { $without[] = $r; } }
    $missing_count = count($with);
    $data_rows = array_merge($with, $without);
}

$admin_title = __a('Çeviri Tablosu', 'Translation Table');
$breadcrumb_root = __a('bc_system', 'Sistem & Ayarlar');
$breadcrumb_page = __a('translation_studio_title', 'Merkezi Çeviri Stüdyosu');

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Modern Web Guidance: responsive-table pattern */
.table-wrapper {
    container-type: inline-size;
    overflow-x: auto;
    border-radius: 10px;
    border: 1px solid var(--border-subtle);
    background: var(--bg-surface);
}
.matrix-table {
    inline-size: 100%;
    border-collapse: collapse;
    min-width: 780px;
}
.matrix-table thead {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--bg-surface-2);
}
.matrix-table th,
.matrix-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border-subtle);
    vertical-align: top;
    text-align: left;
}
.matrix-table tbody th {
    position: sticky;
    inset-inline-start: 0;
    z-index: 1;
    background: var(--bg-surface);
    border-right: 1px solid var(--border-subtle);
    min-width: 210px;
    max-width: 250px;
}
.matrix-table tbody tr:hover td,
.matrix-table tbody tr:hover th {
    background: var(--bg-hover);
}
.matrix-field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.matrix-field-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.matrix-input {
    width: 100%;
    padding: 7px 10px;
    font-size: 13px;
    border: 1px solid var(--border-light);
    border-radius: 6px;
    background: var(--bg-surface);
    color: var(--text-main);
    box-sizing: border-box;
    transition: border-color 0.15s;
}
.matrix-input:focus {
    border-color: var(--accent-blue);
    outline: none;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}
@container (width < 600px) {
    .matrix-table {
        min-width: 100%;
    }
    .matrix-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
    }
    .matrix-table tr {
        display: block;
        margin-block-end: 1rem;
        border: 1px solid var(--border-subtle);
        border-radius: 10px;
    }
    .matrix-table tbody th {
        position: static;
        display: block;
        max-width: none;
        background: var(--bg-surface-2);
        border-right: none;
    }
    .matrix-table td {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .matrix-table td::before {
        content: attr(data-label);
        font-weight: bold;
        font-size: 12px;
        color: var(--text-main);
    }
}
</style>

<?php require_once __DIR__ . '/includes/settings_tabs.php'; render_settings_header_and_tabs('diller'); ?>
<nav class="subnav-tabs" aria-label="<?= e(__a('Diller bölümü', 'Languages section')) ?>" style="margin-bottom:18px;">
    <a href="languages.php" class="subnav-tab"><?= e(__a('Diller & Panel Dili', 'Languages & Panel Language')) ?></a>
    <a href="translations.php" class="subnav-tab is-active"><?= e(__a('Çeviri Tablosu', 'Translation Table')) ?></a>
</nav>
<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
    <p class="page-header-sub" style="margin:0; max-width:70ch;"><?= e(__a('translation_studio_sub')) ?></p>
    <div class="page-header-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" id="btnTranslateAllEmpty" class="btn btn-secondary" onclick="translateAllEmptyInMatrix()" style="background:var(--accent-blue-subtle); color:var(--accent-blue); border-color:rgba(37,99,235,0.25); font-weight:600;">
            <?= e(__a('translate_all_empty_in_matrix')) ?>
        </button>
        <button type="submit" form="matrixForm" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span><?= e(__a('Kaydet', 'Save')) ?></span>
        </button>
    </div>
</div>

<!-- Bölüm Seçici Sekmeler -->
<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px;">
    <?php
    $sec_tabs = [
        'items'      => ['label' => __a('tab_portfolio_works', 'Portfolyo Çalışmaları'), 'icon' => '📸'],
        'categories' => ['label' => __a('tab_categories', 'Kategoriler'),           'icon' => '🏷️'],
        'menus'      => ['label' => __a('tab_menus', 'Üst Menü Navigasyonu'),  'icon' => '🧭'],
        'slides'     => ['label' => __a('tab_slides', 'Ana Sayfa Seçkisi'),     'icon' => '🖼️'],
        'settings'   => ['label' => __a('tab_settings', 'Site & SEO Metinleri'),  'icon' => '🌐'],
    ];
    foreach ($sec_tabs as $sk => $st):
        $is_act = ($section === $sk);
    ?>
        <a href="<?= e(url('admin/translations.php?section=' . $sk . (isset($_GET['target']) ? '&target=' . rawurlencode((string)$_GET['target']) : ''))) ?>"
           class="btn <?= $is_act ? 'btn-primary' : 'btn-secondary' ?>"
           style="font-size:13px; padding:8px 14px;">
            <span><?= e($st['label']) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="tr-view">
    <label for="trTarget"><?= e(__a('Çevrilecek dil', 'Translate into')) ?></label>
    <select id="trTarget" onchange="location.href='?section=<?= e(rawurlencode($section)) ?>&target=' + encodeURIComponent(this.value)">
        <?php foreach ($all_languages as $l): ?>
        <option value="<?= e($l['code']) ?>"<?= $target === $l['code'] ? ' selected' : '' ?>><?= e(($l['flag'] ?: '🌐') . ' ' . $l['name_native']) ?></option>
        <?php endforeach; ?>
        <option value="all"<?= $target === 'all' ? ' selected' : '' ?>><?= e(__a('Bütün diller yan yana', 'All languages side by side')) ?></option>
    </select>
    <?php if ($target !== 'all' && $source !== '' && $source !== $target): ?>
    <span class="tr-src"><?= e(__a('Kaynak', 'Source')) ?>: <?= e(($tr_by_code[$source]['flag'] ?: '🌐') . ' ' . $tr_by_code[$source]['name_native']) ?> <small>(<?= e(__a('salt okunur', 'read-only')) ?>)</small></span>
    <span class="tr-count<?= $missing_count ? '' : ' is-done' ?>"><?= $missing_count ? e(sprintf(__a('%d kayıtta eksik çeviri', '%d records missing a translation'), $missing_count)) : e(__a('Eksik çeviri yok', 'Nothing missing')) ?></span>
    <label class="tr-only"><input type="checkbox" id="trOnlyMissing"<?= $missing_count ? '' : ' disabled' ?>> <?= e(__a('Yalnızca eksikleri göster', 'Show only missing')) ?></label>
    <?php endif; ?>
</div>
<style>
.tr-view { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:0 0 14px; padding:10px 12px; border:1px solid var(--border-subtle); border-radius:10px; background:var(--bg-surface); font-size:13px; }
.tr-view label { font-weight:600; color:var(--text-main); }
.tr-view select { padding:6px 10px; border:1px solid var(--border-subtle); border-radius:6px; background:var(--bg-surface); color:var(--text-main); font:inherit; }
.tr-src { color:var(--text-sub); }
.tr-src small { color:var(--text-muted); }
.tr-count { padding:3px 9px; border-radius:999px; background:var(--warning-soft); color:var(--warning-text); font-weight:600; font-size:12px; }
.tr-count.is-done { background:var(--success-soft); color:#166534; }
.tr-only { font-weight:500 !important; display:inline-flex; align-items:center; gap:6px; margin-left:auto; cursor:pointer; }
.matrix-input.is-source { background:var(--bg-canvas-subtle, rgba(148,163,184,.08)); color:var(--text-sub); border-style:dashed; }
.matrix-table tr[data-missing="1"] > th { box-shadow: inset 3px 0 0 #f59e0b; }
.matrix-table.only-missing tbody tr:not([data-missing="1"]) { display:none; }
[data-theme="dark"] .tr-count { background:rgba(245,158,11,.18); color:#fcd34d; }
[data-theme="dark"] .tr-count.is-done { background:rgba(34,197,94,.18); color:#86efac; }
</style>

<form method="POST" id="matrixForm">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="section" value="<?= e($section) ?>">

    <div tabindex="0" role="region" aria-labelledby="matrixCaption" class="table-wrapper">
        <table class="matrix-table">
            <caption id="matrixCaption" style="position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0);">
                <?= e($sec_tabs[$section]['label'] ?? __a('translation_studio_title')) ?>
            </caption>
            <thead>
                <tr>
                    <th scope="col" style="min-width:210px;"><?= e(__a('record_item')) ?></th>
                    <?php foreach ($view_langs as $l):
                        $lc = $l['code'];
                    ?>
                    <th scope="col" style="min-width:270px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="font-size:16px;"><?= e($l['flag'] ?: '🌐') ?></span>
                                <strong><?= e($l['name_native']) ?></strong>
                                <?php 
                                $loc_hdr = get_localized_language_name($lc, $admin_lang);
                                if ($loc_hdr !== '' && $loc_hdr !== $l['name_native']): ?>
                                    <span style="font-size:12px; font-weight:400; color:var(--text-muted);">(<?= e($loc_hdr) ?>)</span>
                                <?php endif; ?>
                                <code style="font-size:12px; color:var(--text-muted);">(<?= e(strtoupper($lc)) ?>)</code>
                            </div>
                            <?php if ($source !== '' ? $lc !== $source : $lc !== 'tr'): ?>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="translateColumnEmpty('<?= e($lc) ?>', this)" title="Translate empty fields" style="font-size:12px; padding:3px 7px; background:var(--accent-blue-subtle); color:var(--accent-blue); border-color:rgba(37,99,235,0.25);">
                                <?= e(__a('translate_column_empty')) ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($section === 'items'): ?>
                    <?php foreach ($data_rows as $row):
                        $rid = (int)$row['id'];
                        $thumb = !empty($row['cover_image']) ? get_cover_image_url($row['cover_image']) : '';
                    ?>
                    <tr data-row-id="<?= $rid ?>"<?= $row_missing($row) ? ' data-missing="1"' : '' ?>>
                        <th scope="row">
                            <div style="display:flex; gap:10px; align-items:flex-start;">
                                <?php if ($thumb): ?>
                                    <img src="<?= e($thumb) ?>" alt="" style="width:44px; height:44px; object-fit:cover; border-radius:6px; flex-shrink:0; border:1px solid var(--border-subtle);">
                                <?php endif; ?>
                                <div style="min-width:0;">
                                    <div style="font-weight:700; font-size:13px; color:var(--text-main); margin-bottom:2px; word-break:break-word;">
                                        <?= e($row['title_tr'] ?: ($row['title_en'] ?: $row['slug'])) ?>
                                    </div>
                                    <div style="font-size:12px; color:var(--text-muted); margin-bottom:6px;">
                                        <?= e(__a('type')) ?>: <code><?= e($row['type']) ?></code>
                                    </div>
                                    <?php $target_edit = (($row['type'] ?? '') === 'blog') ? 'blog-edit.php?id=' : 'item-edit.php?id='; ?>
                                    <a href="<?= e(url('admin/' . $target_edit . $rid)) ?>" class="btn btn-secondary btn-sm" style="font-size:12px; padding:3px 8px;">
                                        <?= e((($row['type'] ?? '') === 'blog') ? __a('Yazıyı Düzenle →', 'Edit Article →') : __a('story_canvas', 'Hikaye Tuvalini Aç →')) ?>
                                    </a>
                                </div>
                            </div>
                        </th>
                        <?php foreach ($view_langs as $l):
                            $lc = $l['code'];
                            $label_str = ($l['flag'] ?: '🌐') . ' ' . $l['name_native'] . ' (' . get_localized_language_name($lc, $admin_lang) . ')';
                        ?>
                        <td data-label="<?= e($label_str) ?>" data-lang="<?= e($lc) ?>">
                            <div class="matrix-field-group">
                                <div>
                                    <span class="matrix-field-label"><?= e(__a('title')) ?></span>
                                    <input type="text" class="matrix-input" data-field="title" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][title_<?= e($lc) ?>]" value="<?= e($row['title_' . $lc] ?? '') ?>" placeholder="<?= e(strtoupper($lc)) ?> <?= e(__a('title')) ?>...">
                                </div>
                                <div>
                                    <span class="matrix-field-label">URL Slug (/<?= e($lc) ?>/...)</span>
                                    <input type="text" class="matrix-input font-mono" style="font-size:12px;" data-field="slug" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][slug_<?= e($lc) ?>]" value="<?= e($row['slug_' . $lc] ?? '') ?>" placeholder="<?= e($row['slug_en'] ?: $row['slug']) ?>">
                                </div>
                                <div>
                                    <span class="matrix-field-label"><?= e(__a('description')) ?></span>
                                    <textarea class="matrix-input" rows="2" data-field="description" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][description_<?= e($lc) ?>]" placeholder="<?= e(strtoupper($lc)) ?> <?= e(__a('description')) ?>..."><?= e($row['description_' . $lc] ?? '') ?></textarea>
                                </div>
                                <div>
                                    <span class="matrix-field-label"><?= e(__a('location')) ?></span>
                                    <input type="text" class="matrix-input" data-field="location" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][location_<?= e($lc) ?>]" value="<?= e($row['location_' . $lc] ?? '') ?>" placeholder="<?= e($row['location'] ?? '') ?>">
                                </div>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>

                <?php elseif ($section === 'categories'): ?>
                    <?php foreach ($data_rows as $row):
                        $rid = (int)$row['id'];
                    ?>
                    <tr data-row-id="<?= $rid ?>"<?= $row_missing($row) ? ' data-missing="1"' : '' ?>>
                        <th scope="row">
                            <div style="font-weight:700; font-size:13px; color:var(--text-main);">
                                <?= e($row['name_tr'] ?: $row['name_en']) ?>
                            </div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">
                                <?= e(__a('cat_th_type', 'Sistem Kodu')) ?>: <code><?= e($row['key_slug']) ?></code>
                            </div>
                        </th>
                        <?php foreach ($view_langs as $l):
                            $lc = $l['code'];
                            $label_str = ($l['flag'] ?: '🌐') . ' ' . $l['name_native'] . ' (' . get_localized_language_name($lc, $admin_lang) . ')';
                        ?>
                        <td data-label="<?= e($label_str) ?>" data-lang="<?= e($lc) ?>">
                            <div class="matrix-field-group">
                                <div>
                                    <span class="matrix-field-label"><?= e(__a('cat_th_name_tr', 'Kategori Adı')) ?></span>
                                    <input type="text" class="matrix-input" data-field="name" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][name_<?= e($lc) ?>]" value="<?= e($row['name_' . $lc] ?? '') ?>" placeholder="<?= e(strtoupper($lc)) ?>">
                                </div>
                                <div>
                                    <span class="matrix-field-label">URL Slug (/<?= e($lc) ?>/...)</span>
                                    <input type="text" class="matrix-input font-mono" style="font-size:12px;" data-field="slug" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][slug_<?= e($lc) ?>]" value="<?= e($row['slug_' . $lc] ?? '') ?>" placeholder="<?= e($row['slug_en'] ?: $row['key_slug']) ?>">
                                </div>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>

                <?php elseif ($section === 'menus'): ?>
                    <?php foreach ($data_rows as $row):
                        $rid = (int)$row['id'];
                    ?>
                    <tr data-row-id="<?= $rid ?>"<?= $row_missing($row) ? ' data-missing="1"' : '' ?>>
                        <th scope="row">
                            <div style="font-weight:700; font-size:13px; color:var(--text-main);">
                                <?= e($row['title_tr'] ?: $row['title_en']) ?>
                            </div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">
                                <?= e(__a('status')) ?>: <?= !empty($row['is_active']) ? e(__a('status_active')) : e(__a('status_inactive')) ?>
                            </div>
                        </th>
                        <?php foreach ($view_langs as $l):
                            $lc = $l['code'];
                            $label_str = ($l['flag'] ?: '🌐') . ' ' . $l['name_native'] . ' (' . get_localized_language_name($lc, $admin_lang) . ')';
                        ?>
                        <td data-label="<?= e($label_str) ?>" data-lang="<?= e($lc) ?>">
                            <div class="matrix-field-group">
                                <div>
                                    <span class="matrix-field-label"><?= e(__a('menu_th_title', 'Menü Başlığı')) ?></span>
                                    <input type="text" class="matrix-input" data-field="title" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][title_<?= e($lc) ?>]" value="<?= e($row['title_' . $lc] ?? '') ?>" placeholder="<?= e(strtoupper($lc)) ?>">
                                </div>
                                <div>
                                    <span class="matrix-field-label"><?= e(__a('url', 'Hedef URL')) ?></span>
                                    <input type="text" class="matrix-input font-mono" style="font-size:12px;" data-field="url" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][url_<?= e($lc) ?>]" value="<?= e($row['url_' . $lc] ?? '') ?>" placeholder="<?= e(__a('Boşsa otomatik çözülür', 'Auto-resolved if empty')) ?>">
                                </div>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>

                <?php elseif ($section === 'slides'): ?>
                    <?php foreach ($data_rows as $row):
                        $rid = (int)$row['id'];
                        $thumb = !empty($row['image_path']) ? get_cover_image_url($row['image_path']) : '';
                    ?>
                    <tr data-row-id="<?= $rid ?>"<?= $row_missing($row) ? ' data-missing="1"' : '' ?>>
                        <th scope="row">
                            <div style="display:flex; gap:10px; align-items:center;">
                                <?php if ($thumb): ?>
                                    <img src="<?= e($thumb) ?>" alt="" style="width:52px; height:36px; object-fit:cover; border-radius:6px; flex-shrink:0;">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight:700; font-size:13px; color:var(--text-main);">
                                        <?= e($row['title_tr'] ?: ($row['title_en'] ?: 'Slayt #' . $rid)) ?>
                                    </div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= e($row['location'] ?? '') ?></div>
                                </div>
                            </div>
                        </th>
                        <?php foreach ($view_langs as $l):
                            $lc = $l['code'];
                            $label_str = ($l['flag'] ?: '🌐') . ' ' . $l['name_native'] . ' (' . get_localized_language_name($lc, $admin_lang) . ')';
                        ?>
                        <td data-label="<?= e($label_str) ?>" data-lang="<?= e($lc) ?>">
                            <div class="matrix-field-group">
                                <div>
                                    <span class="matrix-field-label">Slayt Başlığı</span>
                                    <input type="text" class="matrix-input" data-field="title" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][title_<?= e($lc) ?>]" value="<?= e($row['title_' . $lc] ?? '') ?>">
                                </div>
                                <div>
                                    <span class="matrix-field-label">Slayt Açıklaması</span>
                                    <textarea class="matrix-input" rows="2" data-field="description" data-lang="<?= e($lc) ?>" name="rows[<?= $rid ?>][description_<?= e($lc) ?>]"><?= e($row['description_' . $lc] ?? '') ?></textarea>
                                </div>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>

                <?php elseif ($section === 'settings'): ?>
                    <?php foreach ($data_rows as $srow):
                        $bkey = $srow['key'];
                    ?>
                    <tr data-row-id="<?= e($bkey) ?>"<?= $row_missing($srow) ? ' data-missing="1"' : '' ?>>
                        <th scope="row">
                            <div style="font-weight:700; font-size:13px; color:var(--text-main);">
                                <?= e($srow['label']) ?>
                            </div>
                            <code style="font-size:12px; color:var(--text-muted);"><?= e($bkey) ?>_*</code>
                        </th>
                        <?php foreach ($view_langs as $l):
                            $lc = $l['code'];
                            $def_val = ($lc === 'tr') ? ($srow['default_tr'] ?? '') : (($lc === 'en') ? ($srow['default_en'] ?? '') : '');
                            $curr_val = get_setting($bkey . '_' . $lc, $def_val);
                            $label_str = ($l['flag'] ?: '🌐') . ' ' . $l['name_native'] . ' (' . get_localized_language_name($lc, $admin_lang) . ')';
                        ?>
                        <td data-label="<?= e($label_str) ?>" data-lang="<?= e($lc) ?>">
                            <input type="text" class="matrix-input" data-field="setting" data-lang="<?= e($lc) ?>" name="rows[<?= e($bkey) ?>][<?= e($lc) ?>]" value="<?= e($curr_val) ?>" placeholder="<?= e(strtoupper($lc)) ?> metni...">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<script>
var TR_SOURCE = <?= json_encode($source) ?>;
(function () {
    if (TR_SOURCE) document.querySelectorAll('.matrix-input[data-lang="' + TR_SOURCE + '"]').forEach(function (el) { el.readOnly = true; el.classList.add('is-source'); el.tabIndex = -1; });
    var cb = document.getElementById('trOnlyMissing');
    if (cb) cb.addEventListener('change', function () { document.querySelector('.matrix-table').classList.toggle('only-missing', cb.checked); });
})();
var isMatrixDirty = false;
var isAutoSaving = false;

document.addEventListener('input', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('matrix-input')) {
        isMatrixDirty = true;
    }
});

function saveMatrixAjax() {
    var form = document.getElementById('matrixForm');
    if (!form) return Promise.resolve(false);
    isAutoSaving = true;
    var fd = new FormData(form);
    return fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        isAutoSaving = false;
        if (res && res.ok) {
            isMatrixDirty = false;
            return true;
        }
        return false;
    })
    .catch(function() {
        isAutoSaving = false;
        return false;
    });
}

// Sekme değiştirirken veya "Dil Yönetimine Dön"e tıklarken kaydedilmemiş değişiklik varsa otomatik kaydet
document.addEventListener('click', function(e) {
    var link = e.target.closest ? e.target.closest('a[href]') : null;
    if (!link || !isMatrixDirty || isAutoSaving) return;
    var href = link.getAttribute('href');
    if (!href || href.indexOf('javascript:') === 0 || link.target === '_blank') return;
    if (href.indexOf('translations.php') !== -1 || href.indexOf('languages.php') !== -1) {
        e.preventDefault();
        link.style.opacity = '0.6';
        saveMatrixAjax().then(function() {
            window.location.href = href;
        });
    }
});

function slugifyMatrix(text) {
    if (window.erSlugify) return window.erSlugify(text); // ortak harf tablosu (Ermenice, Kiril, Yunan…)
    var trMap = {'ı':'i','İ':'i','I':'i','ş':'s','Ş':'s','ğ':'g','Ğ':'g','ü':'u','Ü':'u','ö':'o','Ö':'o','ç':'c','Ç':'c'};
    return String(text || '')
        .replace(/['’`´]/g, '')
        .toLowerCase()
        .replace(/[ıİIşŞğĞüÜöÖçÇ]/g, function(m) { return trMap[m] || m; })
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function translateColumnEmpty(targetLang, btnEl) {
    var rows = document.querySelectorAll('.matrix-table tbody tr');
    var tasks = [];

    rows.forEach(function(tr) {
        var translatableFields = ['title', 'description', 'location', 'name', 'setting'];
        translatableFields.forEach(function(field) {
            var dst = tr.querySelector('.matrix-input[data-field="' + field + '"][data-lang="' + targetLang + '"]');
            if (!dst || dst.value.trim() !== '') return;

            if (TR_SOURCE) {
                var srcEl = tr.querySelector('.matrix-input[data-field="' + field + '"][data-lang="' + TR_SOURCE + '"]');
                if (srcEl && srcEl.value.trim()) tasks.push({ srcLang: TR_SOURCE, text: srcEl.value.trim(), apply: function (translated) {
                    dst.value = translated;
                    if (field === 'title' || field === 'name') {
                        var slugInp = tr.querySelector('.matrix-input[data-field="slug"][data-lang="' + targetLang + '"]');
                        if (slugInp && !slugInp.value.trim()) slugInp.value = slugifyMatrix(translated);
                    }
                } });
                return;
            }
            var srcTr = tr.querySelector('.matrix-input[data-field="' + field + '"][data-lang="tr"]');
            var srcEn = tr.querySelector('.matrix-input[data-field="' + field + '"][data-lang="en"]');
            var srcVal = '';
            var srcLang = 'en';

            if (targetLang === 'en') {
                srcVal = (srcTr && srcTr.value.trim() !== '') ? srcTr.value.trim() : '';
                srcLang = 'tr';
            } else {
                // Diğer tüm diller için İngilizceyi daima birincil ana kaynak olarak kullan
                if (srcEn && srcEn.value.trim() !== '') {
                    srcVal = srcEn.value.trim();
                    srcLang = 'en';
                } else if (srcTr && srcTr.value.trim() !== '') {
                    srcVal = srcTr.value.trim();
                    srcLang = 'tr';
                }
            }

            if (srcVal) {
                tasks.push({
                    srcLang: srcLang,
                    text: srcVal,
                    apply: function(translated) {
                        dst.value = translated;
                        if (field === 'title' || field === 'name') {
                            var slugInp = tr.querySelector('.matrix-input[data-field="slug"][data-lang="' + targetLang + '"]');
                            if (slugInp && !slugInp.value.trim()) {
                                var s = slugifyMatrix(translated);
                                if (!s && srcEn && srcEn.value.trim()) s = slugifyMatrix(srcEn.value.trim());
                                slugInp.value = s;
                            }
                        }
                    }
                });
            }
        });
    });

    if (!tasks.length) {
        alert(targetLang.toUpperCase() + ' sütununda çevrilecek boş alan bulunamadı.');
        return;
    }

    var origText = btnEl ? btnEl.innerHTML : '';
    if (btnEl) {
        btnEl.disabled = true;
        btnEl.innerHTML = '⏳ ' + tasks.length + ' alan...';
    }

    fetch('<?= url("admin/api-translate.php") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?= csrf_token() ?>',
            source_lang: tasks[0].srcLang || 'tr',
            target_lang: targetLang,
            texts: tasks.map(function(t) { return t.text; })
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (btnEl) {
            btnEl.disabled = false;
            btnEl.innerHTML = origText;
        }
        if (data && data.ok && Array.isArray(data.translations)) {
            data.translations.forEach(function(trTxt, idx) {
                if (tasks[idx] && trTxt && !isTranslationWarningText(trTxt)) tasks[idx].apply(trTxt);
            });
            isMatrixDirty = true;
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.innerHTML = 'Kaydediliyor...';
            }
            saveMatrixAjax().then(function(saved) {
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.innerHTML = saved ? '✓ Çevrildi & Kaydedildi' : origText;
                    setTimeout(function() { btnEl.innerHTML = origText; }, 2500);
                }
            });
        }
    })
    .catch(function() {
        if (btnEl) {
            btnEl.disabled = false;
            btnEl.innerHTML = origText;
        }
    });
}

function isTranslationWarningText(str) {
    if (!str) return false;
    var lower = String(str).toLowerCase().trim();
    return lower.indexOf('mymemory warning') !== -1 ||
           lower.indexOf('used all available free translations') !== -1 ||
           lower.indexOf('translated.net/doc/usagelimits') !== -1 ||
           lower.indexOf('next available in') !== -1 ||
           lower.indexOf('quota finished') !== -1 ||
           lower.indexOf('too many requests') !== -1 ||
           (lower.indexOf('warning:') === 0 && (lower.indexOf('limit') !== -1 || lower.indexOf('free') !== -1 || lower.indexOf('available') !== -1));
}

function translateAllEmptyInMatrix() {
    var colBtns = document.querySelectorAll('.matrix-table thead button[onclick^="translateColumnEmpty"]');
    colBtns.forEach(function(btn) {
        btn.click();
    });
}

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('matrixForm').submit();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
