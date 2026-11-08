<?php
/**
 * admin/languages.php
 * Site Ayarları -> Dil Bölümü & Çok Dilli Mimari Yönetimi
 * - Mevcut tüm dilleri aktif/pasif yapma, silme (çıkartma) ve geri ekleme
 * - Yeni dil ekleme (Hazır Dil Kütüphanesi veya Özel ISO Kodu ile)
 * - Yönetim Paneli dilini seçme ve arayüz çevirilerini özelleştirme
 * - Sabit sayfa URL (slug) eşleştirmeleri, Yedek Dil (Fallback) ve SEO hreflang ayarları
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$presets = get_preset_language_catalog();

// POST İşlemleri
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $is_ajax = !empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'CSRF güvenlik hatası.']);
            exit;
        }
        set_flash('error', 'CSRF güvenlik hatası.');
        header('Location: languages.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $now = date('Y-m-d H:i:s');

    // 1. Dili Sitede Aktif / Pasif Yap (Ekle / Çıkar Toggle)
    if ($action === 'toggle_active') {
        $lang_id = (int)($_POST['id'] ?? 0);
        $target_state = (!empty($_POST['is_active']) && $_POST['is_active'] !== '0' && $_POST['is_active'] !== 'false') ? 1 : 0;

        $st = $pdo->prepare("SELECT * FROM `languages` WHERE `id` = ? LIMIT 1");
        $st->execute([$lang_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Dil bulunamadı.']);
                exit;
            }
            set_flash('error', 'Dil bulunamadı.');
            header('Location: languages.php');
            exit;
        }

        if ($target_state === 0) {
            $active_cnt = (int)$pdo->query("SELECT COUNT(*) FROM `languages` WHERE `is_active` = 1")->fetchColumn();
            if ($active_cnt <= 1 && (int)$row['is_active'] === 1) {
                $msg = 'Sitede en az 1 aktif dil bulunmalıdır. Bu dili kapatmadan önce başka bir dili aktif edin.';
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                set_flash('error', $msg);
                header('Location: languages.php');
                exit;
            }
        }

        $up = $pdo->prepare("UPDATE `languages` SET `is_active` = ?, `updated_at` = ? WHERE `id` = ?");
        $up->execute([$target_state, $now, $lang_id]);

        // Eğer pasife alınan dil varsayılan dil ise, aktif olan ilk dili varsayılan yap
        if ($target_state === 0 && (int)$row['is_default'] === 1) {
            $pdo->exec("UPDATE `languages` SET `is_default` = 0");
            $first_active = $pdo->query("SELECT `id`, `code` FROM `languages` WHERE `is_active` = 1 ORDER BY `weight` ASC, `id` ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($first_active) {
                $pdo->prepare("UPDATE `languages` SET `is_default` = 1 WHERE `id` = ?")->execute([$first_active['id']]);
                set_setting('default_site_lang', $first_active['code']);
            }
        }

        if ($target_state === 1) {
            ensure_language_columns($row['code']);
        }

        $msg = $target_state === 1
            ? "{$row['name_tr']} ({$row['code']}) dili sitede aktif edildi."
            : "{$row['name_tr']} ({$row['code']}) dili siteden gizlendi (pasife alındı).";

        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'is_active' => $target_state, 'message' => $msg]);
            exit;
        }
        set_flash('success', $msg);
        header('Location: languages.php');
        exit;
    }

    // 2. Varsayılan Site Dili Yap
    if ($action === 'set_default') {
        $lang_id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM `languages` WHERE `id` = ? LIMIT 1");
        $st->execute([$lang_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $pdo->exec("UPDATE `languages` SET `is_default` = 0");
            $up = $pdo->prepare("UPDATE `languages` SET `is_default` = 1, `is_active` = 1, `updated_at` = ? WHERE `id` = ?");
            $up->execute([$now, $lang_id]);
            set_setting('default_site_lang', $row['code']);
            ensure_language_columns($row['code']);
            set_flash('success', "{$row['name_native']} ({$row['code']}) sitenin varsayılan açılış dili olarak ayarlandı.");
        }
        header('Location: languages.php');
        exit;
    }

    // 3. Yönetim Paneli Dilini Değiştir
    if ($action === 'set_admin_lang') {
        $code = strtolower(trim($_POST['code'] ?? 'tr'));
        $st = $pdo->prepare("SELECT * FROM `languages` WHERE `code` = ? LIMIT 1");
        $st->execute([$code]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $pdo->prepare("UPDATE `languages` SET `is_admin_enabled` = 1 WHERE `id` = ?")->execute([$row['id']]);
        }
        set_setting('admin_panel_lang', $code);
        $_SESSION['admin_lang'] = $code;
        set_flash('success', 'Yönetim paneli dili "' . strtoupper($code) . '" olarak güncellendi.');
        header('Location: languages.php?admin_lang=' . urlencode($code));
        exit;
    }

    // 4. Hazır Dil Şablonundan Tek Tıkla Ekle
    if ($action === 'add_preset') {
        $code = strtolower(trim($_POST['preset_code'] ?? ''));
        if (!isset($presets[$code])) {
            set_flash('error', 'Geçersiz hazır dil şablonu.');
            header('Location: languages.php');
            exit;
        }
        $p = $presets[$code];
        $exists = $pdo->prepare("SELECT `id` FROM `languages` WHERE `code` = ? LIMIT 1");
        $exists->execute([$code]);
        if ($exists->fetchColumn()) {
            // Zaten varsa aktif et
            $pdo->prepare("UPDATE `languages` SET `is_active` = 1, `updated_at` = ? WHERE `code` = ?")->execute([$now, $code]);
            ensure_language_columns($code);
            set_flash('success', "{$p['name_tr']} ({$code}) dili zaten mevcuttu ve sitede aktif edildi.");
            header('Location: languages.php');
            exit;
        }

        $max_w = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) FROM `languages`")->fetchColumn();
        $ins = $pdo->prepare("
            INSERT INTO `languages` (
                `code`, `name_native`, `name_tr`, `flag`, `direction`, `locale`,
                `fallback_lang`, `is_active`, `is_default`, `is_admin_enabled`,
                `url_slugs_json`, `weight`, `created_at`, `updated_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, 1, ?, ?, ?, ?)
        ");
        $ins->execute([
            $p['code'], $p['name_native'], $p['name_tr'], $p['flag'],
            $p['direction'], $p['locale'], $p['fallback_lang'],
            json_encode($p['url_slugs'], JSON_UNESCAPED_UNICODE),
            $max_w + 1, $now, $now
        ]);
        ensure_language_columns($code);
        set_flash('success', "{$p['flag']} {$p['name_native']} ({$p['name_tr']}) dili siteye başarıyla eklendi ve aktif edildi.");
        header('Location: languages.php');
        exit;
    }

    // 5. Özel Dil Ekleme Kaldırıldı (Yalnızca Hazır Dil Kütüphanesi Desteklenir)
    if ($action === 'add_language') {
        set_flash('error', 'Sisteme yalnızca Hazır Dil Kütüphanesindeki onaylı diller eklenebilir.');
        header('Location: languages.php');
        exit;
    }

    // 6. Mevcut Dili Düzenle (Modal)
    if ($action === 'edit_language') {
        $lang_id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM `languages` WHERE `id` = ? LIMIT 1");
        $st->execute([$lang_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            set_flash('error', 'Düzenlenecek dil bulunamadı.');
            header('Location: languages.php');
            exit;
        }

        $name_native = trim($_POST['name_native'] ?? $row['name_native']);
        $name_tr = trim($_POST['name_tr'] ?? $row['name_tr']);
        $flag = trim($_POST['flag'] ?? $row['flag']) ?: '🌐';
        $direction = in_array($_POST['direction'] ?? 'ltr', ['ltr', 'rtl'], true) ? $_POST['direction'] : 'ltr';
        $locale = trim($_POST['locale'] ?? '');
        $fallback_lang = strtolower(trim($_POST['fallback_lang'] ?? 'en')) ?: 'en';
        $is_admin_enabled = !empty($_POST['is_admin_enabled']) ? 1 : 0;

        // URL uzantıları tek yerden (Ayarlar › URL Yönetimi) yönetilir; burada mevcut değer korunur
        $url_slugs = !empty($row['url_slugs_json']) ? (json_decode($row['url_slugs_json'], true) ?: []) : [];

        // Özel arayüz çevirileri (UI Strings)
        $existing_ui = !empty($row['ui_strings_json']) ? (json_decode($row['ui_strings_json'], true) ?: []) : [];
        if (isset($_POST['ui_strings']) && is_array($_POST['ui_strings'])) {
            foreach ($_POST['ui_strings'] as $uk => $uv) {
                $uv = trim((string)$uv);
                if ($uv !== '') {
                    $existing_ui[$uk] = $uv;
                } else {
                    unset($existing_ui[$uk]);
                }
            }
        }

        $up = $pdo->prepare("
            UPDATE `languages` SET
                `name_native` = ?, `name_tr` = ?, `flag` = ?, `direction` = ?, `locale` = ?,
                `fallback_lang` = ?, `is_admin_enabled` = ?, `url_slugs_json` = ?, `ui_strings_json` = ?,
                `updated_at` = ?
            WHERE `id` = ?
        ");
        $up->execute([
            $name_native, $name_tr, $flag, $direction, $locale,
            $fallback_lang, $is_admin_enabled,
            json_encode($url_slugs, JSON_UNESCAPED_UNICODE),
            json_encode($existing_ui, JSON_UNESCAPED_UNICODE),
            $now, $lang_id
        ]);

        set_flash('success', "{$flag} {$name_native} ({$row['code']}) dil yapılandırması güncellendi.");
        header('Location: languages.php');
        exit;
    }

    // 7. Dili Sistemden Çıkar / Sil
    if ($action === 'delete_language') {
        $lang_id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM `languages` WHERE `id` = ? LIMIT 1");
        $st->execute([$lang_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            set_flash('error', 'Silinecek dil bulunamadı.');
            header('Location: languages.php');
            exit;
        }

        $total_cnt = (int)$pdo->query("SELECT COUNT(*) FROM `languages`")->fetchColumn();
        if ($total_cnt <= 1) {
            set_flash('error', 'Sistemde en az 1 dil kalmalıdır. Son dili silemezsiniz.');
            header('Location: languages.php');
            exit;
        }

        $pdo->prepare("DELETE FROM `languages` WHERE `id` = ?")->execute([$lang_id]);

        // Eğer hiç aktif dil kalmadıysa ilk dili aktif ve varsayılan yap
        $active_cnt = (int)$pdo->query("SELECT COUNT(*) FROM `languages` WHERE `is_active` = 1")->fetchColumn();
        if ($active_cnt === 0) {
            $first_any = $pdo->query("SELECT `id` FROM `languages` ORDER BY `weight` ASC, `id` ASC LIMIT 1")->fetchColumn();
            if ($first_any) {
                $pdo->prepare("UPDATE `languages` SET `is_active` = 1 WHERE `id` = ?")->execute([$first_any]);
            }
        }

        // Eğer silinen dil varsayılan dil ise yeni varsayılan belirle
        if ((int)$row['is_default'] === 1) {
            $first_active = $pdo->query("SELECT `id`, `code` FROM `languages` WHERE `is_active` = 1 ORDER BY `weight` ASC, `id` ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($first_active) {
                $pdo->prepare("UPDATE `languages` SET `is_default` = 1 WHERE `id` = ?")->execute([$first_active['id']]);
                set_setting('default_site_lang', $first_active['code']);
            }
        }

        // Eğer silinen dil aktif panel dili ise paneli kalan dile geçir
        if (get_setting('admin_panel_lang', 'tr') === $row['code']) {
            $rem_lang = $pdo->query("SELECT `code` FROM `languages` ORDER BY `is_default` DESC, `weight` ASC LIMIT 1")->fetchColumn() ?: 'tr';
            set_setting('admin_panel_lang', $rem_lang);
            $_SESSION['admin_lang'] = $rem_lang;
        }

        set_flash('success', "{$row['name_tr']} ({$row['code']}) dili siteden çıkartıldı. İsterseniz aşağıdan tek tıkla tekrar ekleyebilirsiniz.");
        header('Location: languages.php');
        exit;
    }

    // 8. Sürükle-Bırak Sıralama Kaydet
    if ($action === 'reorder_languages') {
        $order = $_POST['order'] ?? [];
        if (is_string($order)) {
            $order = json_decode($order, true) ?: [];
        }
        if (is_array($order)) {
            $up = $pdo->prepare("UPDATE `languages` SET `weight` = ? WHERE `id` = ?");
            foreach ($order as $idx => $lid) {
                $up->execute([$idx + 1, (int)$lid]);
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }

    // 9. Genel Dil & Panel Davranış Ayarlarını Kaydet
    if ($action === 'save_behavior_settings') {
        $def_site_lang = strtolower(trim($_POST['default_site_lang'] ?? 'en'));
        $adm_panel_lang = strtolower(trim($_POST['admin_panel_lang'] ?? 'tr'));

        // Varsayılan site dilini güncelle
        $st = $pdo->prepare("SELECT `id` FROM `languages` WHERE `code` = ? LIMIT 1");
        $st->execute([$def_site_lang]);
        $def_id = $st->fetchColumn();
        if ($def_id) {
            $pdo->exec("UPDATE `languages` SET `is_default` = 0");
            $pdo->prepare("UPDATE `languages` SET `is_default` = 1, `is_active` = 1 WHERE `id` = ?")->execute([$def_id]);
            set_setting('default_site_lang', $def_site_lang);
        }

        // Yönetim paneli dilini güncelle
        set_setting('admin_panel_lang', $adm_panel_lang);
        $_SESSION['admin_lang'] = $adm_panel_lang;

        set_setting('auto_detect_browser_lang', !empty($_POST['auto_detect_browser_lang']) ? '1' : '0');
        set_setting('hide_switcher_if_single_lang', !empty($_POST['hide_switcher_if_single_lang']) ? '1' : '0');
        set_setting('seo_hreflang_enabled', !empty($_POST['seo_hreflang_enabled']) ? '1' : '0');

        $disabled_beh = in_array($_POST['disabled_lang_behavior'] ?? '', ['redirect_default', '404'], true)
            ? $_POST['disabled_lang_behavior']
            : 'redirect_default';
        set_setting('disabled_lang_behavior', $disabled_beh);

        $sw_format = in_array($_POST['lang_switcher_format'] ?? '', ['code', 'flag_code', 'native'], true)
            ? $_POST['lang_switcher_format']
            : 'code';
        set_setting('lang_switcher_format', $sw_format);

        set_flash('success', 'Dil davranışı ve yönetim paneli dil ayarları başarıyla kaydedildi.');
        header('Location: languages.php?admin_lang=' . urlencode($adm_panel_lang));
        exit;
    }
}

$languages = get_all_languages(true);
$active_languages = get_active_languages(true);
$existing_codes = array_column($languages, 'code');
$default_site_lang = get_default_language();
$current_admin_lang = get_admin_lang();

// İçerik Çeviri Doluluk İstatistiklerini Hesapla (Portfolyo + Kategori + Menü + Slayt + SEO Ayarları)
foreach ($languages as $l_ens) {
    ensure_language_columns($l_ens['code']);
}

$total_items  = (int)$pdo->query("SELECT COUNT(*) FROM `items` WHERE `type` != 'page'")->fetchColumn();
$total_cats   = (int)$pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
$total_menus  = (int)$pdo->query("SELECT COUNT(*) FROM `menus` WHERE `is_separator` = 0")->fetchColumn();
$total_slides = (int)$pdo->query("SELECT COUNT(*) FROM `homepage_slides`")->fetchColumn();
$seo_keys     = ['site_title', 'site_desc', 'header_logo_subtext', 'header_cta_text', 'footer_brand_title', 'footer_tagline'];
$total_seo    = count($seo_keys);
$grand_total  = $total_items + $total_cats + $total_menus + $total_slides + $total_seo;

$lang_completeness = [];
foreach ($languages as $l) {
    $lc = $l['code'];
    $tr_items = 0;
    $tr_cats = 0;
    $tr_menus = 0;
    $tr_slides = 0;
    $tr_seo = 0;

    try {
        $tr_items = (int)$pdo->query("SELECT COUNT(*) FROM `items` WHERE `type` != 'page' AND `title_{$lc}` IS NOT NULL AND TRIM(`title_{$lc}`) != ''")->fetchColumn();
    } catch (\Throwable $e) {}

    try {
        $tr_cats = (int)$pdo->query("SELECT COUNT(*) FROM `categories` WHERE `name_{$lc}` IS NOT NULL AND TRIM(`name_{$lc}`) != ''")->fetchColumn();
    } catch (\Throwable $e) {}

    try {
        $tr_menus = (int)$pdo->query("SELECT COUNT(*) FROM `menus` WHERE `is_separator` = 0 AND `title_{$lc}` IS NOT NULL AND TRIM(`title_{$lc}`) != ''")->fetchColumn();
    } catch (\Throwable $e) {}

    try {
        $tr_slides = (int)$pdo->query("SELECT COUNT(*) FROM `homepage_slides` WHERE `title_{$lc}` IS NOT NULL AND TRIM(`title_{$lc}`) != ''")->fetchColumn();
    } catch (\Throwable $e) {}

    foreach ($seo_keys as $sk) {
        $val = trim((string)get_setting($sk . '_' . $lc, ''));
        if ($val !== '' || in_array($lc, ['tr', 'en'], true)) {
            $tr_seo++;
        }
    }

    $translated_sum = $tr_items + $tr_cats + $tr_menus + $tr_slides + $tr_seo;
    $pct = $grand_total > 0 ? (int)round(($translated_sum / $grand_total) * 100) : 100;

    $lang_completeness[$lc] = [
        'translated' => $translated_sum,
        'total'      => $grand_total,
        'percent'    => $pct,
        'breakdown'  => "Portfolyo: {$tr_items}/{$total_items} · Kategori: {$tr_cats}/{$total_cats} · Menü: {$tr_menus}/{$total_menus} · Slayt: {$tr_slides}/{$total_slides} · SEO: {$tr_seo}/{$total_seo}",
        'breakdown_items' => [
            ['label' => 'Portfolyo', 'count' => "{$tr_items}/{$total_items}"],
            ['label' => 'Kategori', 'count' => "{$tr_cats}/{$total_cats}"],
            ['label' => 'Menü', 'count' => "{$tr_menus}/{$total_menus}"],
            ['label' => 'Slayt', 'count' => "{$tr_slides}/{$total_slides}"],
            ['label' => 'SEO', 'count' => "{$tr_seo}/{$total_seo}"],
        ],
    ];
}

$admin_title = __a('Diller', 'Languages');
$breadcrumb_root = __a('bc_system', 'Sistem');
$breadcrumb_page = __a('menu_languages', 'Languages & Translate');

require __DIR__ . '/includes/header.php';
?>

<style>
.lang-switch-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}
.lang-switch-toggle input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.lang-switch-track {
    position: relative;
    width: 38px;
    height: 21px;
    background: #cbd5e1;
    border-radius: 999px;
    transition: background 0.2s ease;
}
[data-theme="dark"] .lang-switch-track {
    background: #383838;
}
.lang-switch-track::after {
    content: "";
    position: absolute;
    top: 3px;
    left: 3px;
    width: 15px;
    height: 15px;
    background: #ffffff;
    border-radius: 50%;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    transition: transform 0.2s ease;
}
.lang-switch-toggle input:checked + .lang-switch-track {
    background: #10b981;
}
.lang-switch-toggle input:checked + .lang-switch-track::after {
    transform: translateX(17px);
}

.preset-lang-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(195px, 1fr));
    gap: 10px;
}
.preset-lang-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 13px;
    background: var(--bg-surface-2);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    transition: all 0.15s ease;
}
.preset-lang-card:hover {
    border-color: var(--border-light);
    background: var(--bg-hover);
}
.preset-lang-card.is-installed {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.3);
}

/* Native <dialog> Modal Styling (Modern Web Guidance light-dismiss-a-dialog) */
dialog.lang-edit-dialog {
    margin: auto;
    width: 680px;
    max-width: calc(100vw - 32px);
    max-height: calc(100vh - 48px);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    background: var(--bg-surface);
    color: var(--text-main);
    padding: 0;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
    overflow: hidden;
}
dialog.lang-edit-dialog::backdrop {
    background-color: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(4px);
}
.dialog-inner-scroll {
    max-height: calc(100vh - 170px);
    overflow-y: auto;
    padding: 22px 24px;
}
.lang-table-card .table-responsive {
    overflow-x: visible;
}
@media (max-width: 900px) {
    .lang-table-card .table-responsive {
        overflow-x: auto;
    }
}
.admin-languages-table {
    width: 100%;
    table-layout: auto;
}
.admin-languages-table th,
.admin-languages-table td {
    padding: 10px 12px;
}
</style>

<!-- ── Üst Başlık Bandı & Site Ayarları Alt Gezintisi ── -->
<?php require_once __DIR__ . '/includes/settings_tabs.php'; render_settings_header_and_tabs('diller'); ?>
<nav class="subnav-tabs" aria-label="<?= e(__a('Diller bölümü', 'Languages section')) ?>" style="margin-bottom:18px;">
    <a href="languages.php" class="subnav-tab is-active"><?= e(__a('Diller & Panel Dili', 'Languages & Panel Language')) ?></a>
    <a href="translations.php" class="subnav-tab"><?= e(__a('Çeviri Tablosu', 'Translation Table')) ?></a>
</nav>

<!-- ── 1. BÖLÜM: SİTEDEKİ MEVCUT DİLLER TABLOSU (EKLE / ÇIKAR / SIRALA / DÜZENLE) ── -->
<div class="card lang-table-card">
    <div class="card-header">
        <div>
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <span><?= e(__a('installed_languages_title')) ?></span>
            </h2>
            <p style="font-size:12px; color:var(--text-muted); margin-top:3px;">
                <?= e(__a('reorder_drag')) ?>
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <span class="badge badge-success"><?= count($active_languages) ?> <?= e(__a('status_active')) ?></span>
            <span class="badge badge-neutral"><?= count($languages) ?> <?= e(__a('total_showcase')) ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table admin-languages-table" data-m="languages">
            <thead>
                <tr>
                    <th style="width:38px; text-align:center; padding:10px 6px;"><?= e(__a('order')) ?></th>
                    <th><?= e(__a('title', 'DİL & BAYRAK')) ?></th>
                    <th style="white-space:nowrap;"><?= e(__a('direction', 'KOD / YÖN')) ?></th>
                    <th style="white-space:nowrap;"><?= e(__a('status', 'SİTEDE DURUMU')) ?></th>
                    <th style="white-space:nowrap;"><?= e(__a('default_opening_lang', 'VARSAYILAN DİL')) ?></th>
                    <th style="white-space:nowrap;"><?= e(__a('current_panel_lang', 'YÖNETİM PANELİ DİLİ')) ?></th>
                    <th style="text-align:right; white-space:nowrap;"><?= e(__a('actions', 'İŞLEMLER')) ?></th>
                </tr>
            </thead>
            <tbody id="languagesSortableBody">
                <?php foreach ($languages as $l):
                    $lid = (int)$l['id'];
                    $lcode = $l['code'];
                    $is_act = !empty($l['is_active']);
                    $is_def = ($lcode === $default_site_lang);
                    $is_adm_curr = ($lcode === $current_admin_lang);
                    $comp = $lang_completeness[$lcode] ?? ['translated' => 0, 'total' => $total_items, 'percent' => 0];
                ?>
                <tr data-id="<?= $lid ?>">
                    <td class="drag-handle-cell" title="Sıralamak için sürükleyin">
                        <svg class="drag-grip-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:22px; line-height:1;"><?= e($l['flag'] ?: '🌐') ?></span>
                            <div>
                                <div style="font-weight:700; color:var(--text-main); font-size:14px;">
                                    <?= e($l['name_native']) ?>
                                    <?php 
                                    $loc_name = get_localized_language_name($lcode, $admin_lang);
                                    if ($loc_name !== '' && $loc_name !== $l['name_native']): ?>
                                        <span style="font-weight:400; color:var(--text-muted); font-size:13px;">(<?= e($loc_name) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:12px; color:var(--text-sub); font-family:var(--font-mono);">
                                    URL: <a href="<?= e(url($lcode . '/')) ?>" target="_blank" style="color:var(--text-muted); text-decoration:underline;">/<?= e($lcode) ?>/</a>
                                    · <?= e(__a('fallback_lang', 'Yedek Dil')) ?>: <strong><?= e(strtoupper($l['fallback_lang'] ?: 'en')) ?></strong>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-neutral" style="font-size:12px; font-weight:700;"><?= e(strtoupper($lcode)) ?></span>
                        <span class="badge <?= ($l['direction'] ?? 'ltr') === 'rtl' ? 'badge-warning' : 'badge-neutral' ?>" style="font-size:12px;">
                            <?= ($l['direction'] ?? 'ltr') === 'rtl' ? e(__a('dir_rtl', 'RTL (Sağdan Sola)')) : 'LTR' ?>
                        </span>
                    </td>
                    <td>
                        <label class="lang-switch-toggle" title="<?= e(__a('status')) ?>">
                            <input type="checkbox" <?= $is_act ? 'checked' : '' ?> onchange="toggleSiteLanguage(<?= $lid ?>, this)">
                            <span class="lang-switch-track"></span>
                            <span class="badge <?= $is_act ? 'badge-success' : 'badge-neutral' ?>" id="lang-status-badge-<?= $lid ?>">
                                <?= $is_act ? e(__a('status_live_active')) : e(__a('status_site_hidden')) ?>
                            </span>
                        </label>
                    </td>
                    <td>
                        <?php if ($is_def): ?>
                            <span class="badge" style="background:var(--primary); color:#fdd000; border:1px solid var(--border-subtle); padding:4px 10px;">
                                ★ <?= e(__a('default_opening_lang')) ?>
                            </span>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="set_default">
                                <input type="hidden" name="id" value="<?= $lid ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:12px; padding:4px 10px;">
                                    <?= e(__a('set_as_default')) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_adm_curr): ?>
                            <span class="badge badge-webp" style="padding:4px 10px;">
                                ✓ <?= e(__a('current_panel_lang')) ?>
                            </span>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="set_admin_lang">
                                <input type="hidden" name="code" value="<?= e($lcode) ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:12px; padding:4px 10px;">
                                    <?= e(__a('use_panel_in_this_lang')) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right; white-space:nowrap;">
                        <?php
                            $l_edit_data = $l;
                            $l_edit_data['completeness'] = $comp;
                        ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick='openEditLangDialog(<?= json_encode($l_edit_data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                            <?= e(__a('edit_urls')) ?>
                        </button>
                        <?php if (count($languages) > 1): ?>
                        <?php $del_lang_name = get_localized_language_name($lcode, $admin_lang); ?>
                        <details class="row-menu">
                            <summary class="btn btn-secondary btn-sm" aria-label="<?= e(__a('actions')) ?>">⋯</summary>
                            <div class="row-menu-list">
                                <form method="POST" data-confirm="<?= e(sprintf(__a('%s (%s) siteden kaldırılsın mı? Ziyaretçiler bu dili artık seçemez.', 'Remove %s (%s) from the site? Visitors can no longer choose this language.'), $del_lang_name, strtoupper($lcode))) ?>" data-confirm-ok="<?= e(__a('remove_lang')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_language">
                                    <input type="hidden" name="id" value="<?= $lid ?>">
                                    <button type="submit" class="is-danger"><?= e(__a('remove_lang')) ?></button>
                                </form>
                            </div>
                        </details>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── 2. BÖLÜM: TEK TIKLA HAZIR DİL EKLE / GERİ YÜKLE KÜTÜPHANESİ ── -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                <span><?= e(__a('preset_languages_title')) ?></span>
            </h2>
            <p style="font-size:12px; color:var(--text-muted); margin-top:3px;">
                <?= e(__a('preset_languages_sub', 'Tek tıkla hazır dil şablonunu siteye ekleyin, yayınlayın ve tüm arayüzü bu dille kullanın.')) ?>
            </p>
        </div>
    </div>
    <div class="card-body">
        <div class="preset-lang-grid">
            <?php foreach ($presets as $pcode => $pinfo):
                $already_in = in_array($pcode, $existing_codes, true);
                $p_loc_name = get_localized_language_name($pcode, $admin_lang);
            ?>
            <div class="preset-lang-card <?= $already_in ? 'is-installed' : '' ?>">
                <div style="display:flex; align-items:center; gap:9px; min-width:0;">
                    <span style="font-size:20px;"><?= e($pinfo['flag']) ?></span>
                    <div style="min-width:0;">
                        <div style="font-weight:700; font-size:13px; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <?= e($pinfo['name_native']) ?>
                        </div>
                        <div style="font-size:12px; color:var(--text-muted);">
                            <?= e($p_loc_name) ?> · <code style="font-size:12px;"><?= e(strtoupper($pcode)) ?></code>
                            <?= $pinfo['direction'] === 'rtl' ? '· RTL' : '' ?>
                        </div>
                    </div>
                </div>
                <?php if ($already_in): ?>
                    <span class="badge badge-success" style="font-size:12px;"><?= e(__a('installed_badge')) ?></span>
                <?php else: ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="add_preset">
                        <input type="hidden" name="preset_code" value="<?= e($pcode) ?>">
                        <button type="submit" class="btn btn-primary btn-sm" style="padding:5px 10px; font-size:12px;">
                            <?= e(__a('add_btn')) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── 3. BÖLÜM: PANEL DİLİ, VARSAYILAN DİL & ÇOK DİLLİ DAVRANIŞ AYARLARI ── -->
<div class="card" id="panel-lang-section">
    <div class="card-header">
        <div>
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                <span><?= e(__a('admin_lang_behavior_title')) ?></span>
            </h2>
            <p style="font-size:12px; color:var(--text-muted); margin-top:3px;">
                <?= e(__a('admin_lang_behavior_sub', 'Yönetim paneli varsayılan dilini, site açılış dilini ve dil değiştirici görünümünü yapılandırın.')) ?>
            </p>
        </div>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save_behavior_settings">

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
                <!-- Yönetim Paneli Dili -->
                <div class="form-group" style="background:var(--bg-surface-2); padding:16px; border-radius:10px; border:1px solid var(--border-subtle);">
                    <label class="form-label"><?= e(__a('admin_panel_lang_label', 'Yönetim Paneli Arayüz Dili (Admin Panel)')) ?></label>
                    <select name="admin_panel_lang" class="form-control">
                        <?php foreach ($languages as $l): ?>
                            <option value="<?= e($l['code']) ?>" <?= $current_admin_lang === $l['code'] ? 'selected' : '' ?>>
                                <?= e($l['flag']) ?> <?= e($l['name_native']) ?> (<?= e(get_localized_language_name($l['code'], $admin_lang)) ?> - <?= e(strtoupper($l['code'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display:block; margin-top:6px; font-size:12px; color:var(--text-sub); line-height:1.4;">
                        <?= e(__a('admin_panel_lang_hint', 'Yönetim panelinin hangi dilde görüneceğini belirler.')) ?>
                    </small>
                </div>

                <!-- Varsayılan Site Açılış Dili -->
                <div class="form-group" style="background:var(--bg-surface-2); padding:16px; border-radius:10px; border:1px solid var(--border-subtle);">
                    <label class="form-label"><?= e(__a('default_site_lang_label', 'Sitenin Varsayılan Açılış Dili (Root /)')) ?></label>
                    <select name="default_site_lang" class="form-control">
                        <?php foreach ($languages as $l): ?>
                            <option value="<?= e($l['code']) ?>" <?= $default_site_lang === $l['code'] ? 'selected' : '' ?>>
                                <?= e($l['flag']) ?> <?= e($l['name_native']) ?> (<?= e(get_localized_language_name($l['code'], $admin_lang)) ?>) (/<?= e($l['code']) ?>/) <?= empty($l['is_active']) ? ('[' . e(__a('status_inactive_note', 'Şu an pasif - seçilirse aktif olur')) . ']') : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display:block; margin-top:6px; font-size:12px; color:var(--text-sub); line-height:1.4;">
                        <?= e(str_replace('{site}', preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? ''), __a('default_site_lang_hint', 'Ziyaretçiler doğrudan {site} adresine girdiğinde yönlendirilecek ana dil.'))) ?>
                    </small>
                    <label style="display:inline-flex; align-items:center; gap:8px; margin-top:10px; font-size:13px; cursor:pointer; font-weight:500;">
                        <input type="checkbox" name="auto_detect_browser_lang" value="1" <?= get_setting('auto_detect_browser_lang', '1') === '1' ? 'checked' : '' ?>>
                        <span><?= e(__a('auto_detect_browser_lang_label', 'İlk ziyarette ziyaretçinin tarayıcı dilini (Accept-Language) otomatik algıla')) ?></span>
                    </label>
                </div>

                <!-- Dil Seçici & SEO Ayarları -->
                <div class="form-group" style="background:var(--bg-surface-2); padding:16px; border-radius:10px; border:1px solid var(--border-subtle);">
                    <label class="form-label"><?= e(__a('lang_switcher_format_label', 'Menüde Dil Seçici Görünüm Biçimi')) ?></label>
                    <?php $curr_sw_fmt = get_setting('lang_switcher_format', 'code'); ?>
                    <select name="lang_switcher_format" class="form-control">
                        <option value="code" <?= $curr_sw_fmt === 'code' ? 'selected' : '' ?>><?= e(__a('fmt_code', 'Kısa ISO Kodu (EN / TR / HY)')) ?></option>
                        <option value="flag_code" <?= $curr_sw_fmt === 'flag_code' ? 'selected' : '' ?>><?= e(__a('fmt_flag_code', 'Bayrak + Kısa Kod (🇬🇧 EN / 🇹🇷 TR)')) ?></option>
                        <option value="native" <?= $curr_sw_fmt === 'native' ? 'selected' : '' ?>><?= e(__a('fmt_native', 'Tam Dil Adı (English / Türkçe / Հայերեն)')) ?></option>
                    </select>

                    <div style="display:flex; flex-direction:column; gap:6px; margin-top:10px;">
                        <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; font-weight:500;">
                            <input type="checkbox" name="hide_switcher_if_single_lang" value="1" <?= get_setting('hide_switcher_if_single_lang', '1') === '1' ? 'checked' : '' ?>>
                            <span><?= e(__a('hide_switcher_single_label', 'Sitede tek dil aktifken dil değiştiriciyi otomatik gizle')) ?></span>
                        </label>
                        <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; font-weight:500;">
                            <input type="checkbox" name="seo_hreflang_enabled" value="1" <?= get_setting('seo_hreflang_enabled', '1') === '1' ? 'checked' : '' ?>>
                            <span><?= e(__a('seo_hreflang_label', 'Google SEO için hreflang alternatif dil etiketlerini otomatik üret')) ?></span>
                        </label>
                    </div>
                </div>

                <!-- Pasif / Çıkartılan Dil URL Davranışı -->
                <div class="form-group" style="background:var(--bg-surface-2); padding:16px; border-radius:10px; border:1px solid var(--border-subtle);">
                    <label class="form-label"><?= e(__a('disabled_lang_behavior_label', 'Kaldırılan / Pasif Dil URL\'lerine Girildiğinde Ne Olsun?')) ?></label>
                    <?php $curr_dis_beh = get_setting('disabled_lang_behavior', 'redirect_default'); ?>
                    <select name="disabled_lang_behavior" class="form-control">
                        <option value="redirect_default" <?= $curr_dis_beh === 'redirect_default' ? 'selected' : '' ?>><?= e(__a('dis_behavior_redirect', 'Varsayılan Dile Akıllı Yönlendir (302 Redirect - Önerilen)')) ?></option>
                        <option value="404" <?= $curr_dis_beh === '404' ? 'selected' : '' ?>><?= e(__a('dis_behavior_404', '404 Sayfa Bulunamadı Göster')) ?></option>
                    </select>
                    <small style="display:block; margin-top:6px; font-size:12px; color:var(--text-sub); line-height:1.4;">
                        <?= e(__a('disabled_lang_hint', 'Örneğin Türkçe dilini siteden çıkarttığınızda eski /tr/... linklerine tıklayan ziyaretçilerin kırık link görmemesi için varsayılan dile yönlendirilmesini sağlar.')) ?>
                    </small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                <?= e(__a('Kaydet', 'Save')) ?>
            </button>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ── MODAL 1: DİL & PANEL ÇEVİRİLERİNİ DÜZENLE (<dialog closedby="any">) ── -->
<!-- ══════════════════════════════════════════════════════════════ -->
<dialog id="editLangDialog" class="lang-edit-dialog" closedby="any" aria-labelledby="editLangDialogTitle">
    <div class="card-header" style="border-bottom:1px solid var(--border-subtle);">
        <h3 class="card-title" id="editLangDialogTitle">Dil Ayarlarını & URL Yapısını Düzenle</h3>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editLangDialog').close()">✕ Kapat</button>
    </div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="edit_language">
        <input type="hidden" name="id" id="edit_lang_id" value="">
        <div class="dialog-inner-scroll">
            <!-- ── Çeviri Doluluğu Bilgi & İlerleme Kartı (Popup Üst Kısmı) ── -->
            <div id="edit_comp_box" style="margin-bottom:16px; padding:14px 16px; background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:10px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; gap:10px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent-blue);"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span style="font-weight:700; font-size:13px; color:var(--text-main);"><?= e(__a('completeness', 'Çeviri Doluluğu')) ?></span>
                        <span id="edit_comp_percent_badge" class="badge badge-success" style="font-size:12px; font-weight:700; font-family:var(--font-mono);">%0</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="font-size:12px; color:var(--text-muted);">
                            <strong id="edit_comp_counts" style="color:var(--text-main); font-family:var(--font-mono);">0 / 0</strong> <?= e(__a('total_content', 'içerik çevrildi')) ?>
                        </span>
                        <a id="edit_comp_studio_link" href="translations.php" class="btn btn-secondary btn-sm" style="font-size:12px; padding:3px 9px; color:var(--accent-blue); background:var(--accent-blue-subtle); border-color:rgba(37,99,235,0.25); text-decoration:none;">
                            <?= e(__a('open_translation_studio', 'Çeviri Stüdyosu')) ?>
                        </a>
                    </div>
                </div>

                <div style="width:100%; height:7px; background:var(--border-subtle); border-radius:999px; overflow:hidden; margin-bottom:10px;">
                    <div id="edit_comp_progress_bar" style="width:0%; height:100%; background:#10b981; border-radius:999px; transition:width 0.25s ease;"></div>
                </div>

                <div id="edit_comp_breakdown_items" style="display:flex; flex-wrap:wrap; gap:6px 10px; font-size:12px; color:var(--text-sub);">
                    <!-- JS ile doldurulacak -->
                </div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label class="form-label"><?= e(__a('Kendi Dilindeki Adı (Native)', 'Native Language Name')) ?></label>
                    <input type="text" name="name_native" id="edit_name_native" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(__a('Arayüzdeki Dil Adı', 'Display Language Name')) ?></label>
                    <input type="text" name="name_tr" id="edit_name_tr" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(__a('Bayrak / Simge', 'Flag / Symbol')) ?></label>
                    <input type="text" name="flag" id="edit_flag" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(__a('direction', 'Yazım Yönü')) ?></label>
                    <select name="direction" id="edit_direction" class="form-control">
                        <option value="ltr"><?= e(__a('dir_ltr', 'LTR (Soldan Sağa)')) ?></option>
                        <option value="rtl"><?= e(__a('dir_rtl', 'RTL (Sağdan Sola)')) ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(__a('fallback_lang', 'Yedek İçerik Dili (Fallback)')) ?></label>
                    <select name="fallback_lang" id="edit_fallback_lang" class="form-control">
                        <option value="en"><?= e(get_localized_language_name('en', $admin_lang)) ?> (EN)</option>
                        <option value="tr"><?= e(get_localized_language_name('tr', $admin_lang)) ?> (TR)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(__a('locale_code', 'Yerel Kod (Locale)')) ?></label>
                    <input type="text" name="locale" id="edit_locale" class="form-control" placeholder="tr_TR, en_US">
                </div>
            </div>

            <p class="form-hint" style="margin:12px 0;"><?= e(__a('Bu dildeki sayfa adları ve URL uzantıları', 'Page names and URL slugs for this language are managed in')) ?> <a href="page-names.php"><?= e(__a('Ayarlar › URL Yönetimi', 'Settings › URL Management')) ?></a><?= e(__a(' ekranından yönetilir.', '.')) ?></p>

            <div style="margin:12px 0; padding:14px; background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:10px;">
                <div style="font-weight:700; font-size:13px; margin-bottom:8px;"><?= e(__a('Yönetim Paneli & Site Arayüz Çeviri Özelleştirmeleri (Opsiyonel)', 'Admin Panel & Interface Translations (Optional)')) ?></div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label class="form-label" style="font-size:12px;"><?= e(__a('menu_homepage', 'Ana Sayfa')) ?></label>
                        <input type="text" name="ui_strings[menu_homepage]" id="edit_ui_menu_homepage" class="form-control" placeholder="<?= e(__a('Varsayılan çeviri kullanılır', 'Default translation is used')) ?>">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:12px;"><?= e(__a('menu_portfolio', 'Portfolyo')) ?></label>
                        <input type="text" name="ui_strings[menu_portfolio]" id="edit_ui_menu_portfolio" class="form-control" placeholder="<?= e(__a('Varsayılan çeviri kullanılır', 'Default translation is used')) ?>">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:12px;"><?= e(__a('menu_settings', 'Site Ayarları')) ?></label>
                        <input type="text" name="ui_strings[menu_settings]" id="edit_ui_menu_settings" class="form-control" placeholder="<?= e(__a('Varsayılan çeviri kullanılır', 'Default translation is used')) ?>">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:12px;"><?= e(__a('topbar_view_site', 'Siteyi Görüntüle')) ?></label>
                        <input type="text" name="ui_strings[topbar_view_site]" id="edit_ui_topbar_view_site" class="form-control" placeholder="<?= e(__a('Varsayılan çeviri kullanılır', 'Default translation is used')) ?>">
                    </div>
                </div>
            </div>

            <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                <input type="checkbox" name="is_admin_enabled" id="edit_is_admin_enabled" value="1">
                <span><?= e(__a('Bu dil Yönetim Paneli arayüz dili olarak kullanılabilir', 'This language can be used as admin interface language')) ?></span>
            </label>
        </div>
        <div class="card-header" style="border-top:1px solid var(--border-subtle); border-bottom:none; justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('editLangDialog').close()"><?= e(__a('cancel')) ?></button>
            <button type="submit" class="btn btn-primary"><?= e(__a('Kaydet', 'Save')) ?></button>
        </div>
    </form>
</dialog>

<script src="<?= url('js/Sortable.min.js') ?>"></script>
<script>
// 1. Sitede Aktif / Pasif Anlık AJAX Değiştirici
function toggleSiteLanguage(id, checkboxEl) {
    var isChecked = checkboxEl.checked;
    var badge = document.getElementById('lang-status-badge-' + id);

    var fd = new FormData();
    fd.append('csrf_token', '<?= csrf_token() ?>');
    fd.append('action', 'toggle_active');
    fd.append('ajax', '1');
    fd.append('id', id);
    fd.append('is_active', isChecked ? '1' : '0');

    fetch('languages.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data || !data.success) {
            alert((data && data.message) ? data.message : 'İşlem başarısız oldu.');
            checkboxEl.checked = !isChecked;
            return;
        }
        if (badge) {
            badge.className = 'badge ' + (isChecked ? 'badge-success' : 'badge-neutral');
            badge.textContent = isChecked ? 'Yayında (Aktif)' : 'Sitede Gizli';
        }
        if (window.adminToast) window.adminToast();
    })
    .catch(function() {
        alert('Bağlantı hatası oluştu.');
        checkboxEl.checked = !isChecked;
    });
}

// 2. Düzenleme Modalını Aç
function openEditLangDialog(lang) {
    document.getElementById('edit_lang_id').value = lang.id;
    document.getElementById('editLangDialogTitle').textContent = (lang.flag || '🌐') + ' ' + lang.name_native + ' (' + lang.code.toUpperCase() + ') Dilini Düzenle';
    document.getElementById('edit_name_native').value = lang.name_native || '';

    // Çeviri Doluluğu Verilerini Doldur
    var comp = lang.completeness || { translated: 0, total: 0, percent: 0, breakdown: '', breakdown_items: [] };
    var pct = parseInt(comp.percent, 10) || 0;

    var pctBadge = document.getElementById('edit_comp_percent_badge');
    var pBar = document.getElementById('edit_comp_progress_bar');
    var counts = document.getElementById('edit_comp_counts');
    var bItems = document.getElementById('edit_comp_breakdown_items');
    var studioLink = document.getElementById('edit_comp_studio_link');

    if (pctBadge) {
        pctBadge.textContent = '%' + pct;
        pctBadge.className = 'badge ' + (pct >= 80 ? 'badge-success' : (pct >= 30 ? 'badge-warning' : 'badge-neutral'));
    }
    if (pBar) {
        pBar.style.width = pct + '%';
        pBar.style.background = pct >= 80 ? '#10b981' : (pct >= 30 ? '#f59e0b' : '#94a3b8');
    }
    if (counts) {
        counts.textContent = (comp.translated || 0) + ' / ' + (comp.total || 0);
    }
    if (studioLink) {
        studioLink.href = 'translations.php?target=' + encodeURIComponent(lang.code);
    }
    if (bItems) {
        if (comp.breakdown_items && comp.breakdown_items.length) {
            bItems.innerHTML = comp.breakdown_items.map(function(item) {
                return '<span style="display:inline-flex; align-items:center; gap:4px; background:var(--bg-surface); padding:3px 8px; border-radius:6px; border:1px solid var(--border-subtle);">' +
                       '<span style="color:var(--text-muted);">' + item.label + ':</span> <strong style="font-family:var(--font-mono); color:var(--text-main);">' + item.count + '</strong></span>';
            }).join('');
        } else if (comp.breakdown) {
            bItems.textContent = comp.breakdown;
        } else {
            bItems.textContent = '';
        }
    }
    document.getElementById('edit_name_tr').value = lang.name_tr || '';
    document.getElementById('edit_flag').value = lang.flag || '🌐';
    document.getElementById('edit_direction').value = lang.direction || 'ltr';
    document.getElementById('edit_fallback_lang').value = lang.fallback_lang || 'en';
    document.getElementById('edit_locale').value = lang.locale || '';
    document.getElementById('edit_is_admin_enabled').checked = (parseInt(lang.is_admin_enabled, 10) === 1);

    var ui = lang.ui_strings || {};
    document.getElementById('edit_ui_menu_homepage').value = ui.menu_homepage || '';
    document.getElementById('edit_ui_menu_portfolio').value = ui.menu_portfolio || '';
    document.getElementById('edit_ui_menu_settings').value = ui.menu_settings || '';
    document.getElementById('edit_ui_topbar_view_site').value = ui.topbar_view_site || '';

    document.getElementById('editLangDialog').showModal();
}

// 3. Sürükle-Bırak Dil Sıralaması
document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.getElementById('languagesSortableBody');
    if (tbody && typeof Sortable !== 'undefined') {
        Sortable.create(tbody, {
            handle: '.drag-handle-cell',
            animation: 150,
            onEnd: function() {
                var ids = [];
                tbody.querySelectorAll('tr[data-id]').forEach(function(tr) {
                    ids.push(tr.getAttribute('data-id'));
                });
                var fd = new FormData();
                fd.append('csrf_token', '<?= csrf_token() ?>');
                fd.append('action', 'reorder_languages');
                fd.append('order', JSON.stringify(ids));
                fetch('languages.php', { method: 'POST', body: fd });
            }
        });
    }

    // Fallback for browsers without <dialog closedby> support (Safari)
    if (!('closedBy' in HTMLDialogElement.prototype)) {
        document.querySelectorAll('dialog.lang-edit-dialog').forEach(function(dialog) {
            dialog.addEventListener('click', function(event) {
                if (event.target !== dialog) return;
                var rect = dialog.getBoundingClientRect();
                var isDialogContent = (
                    rect.top <= event.clientY &&
                    event.clientY <= rect.top + rect.height &&
                    rect.left <= event.clientX &&
                    event.clientX <= rect.left + rect.width
                );
                if (isDialogContent) return;
                dialog.close();
            });
        });
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
