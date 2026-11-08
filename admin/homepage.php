<?php
/**
 * admin/homepage.php
 * Ana Sayfa Seçkisi (Home Showcase) Yönetimi
 * Kullanıcı ana sayfada rastgele ve tıklamayla dönecek fotoğraf seçkisini yönetebilir:
 * Medya kütüphanesinden veya portfolyodan fotoğraf ekleyebilir, sıralayabilir, düzenleyebilir veya silebilir.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$site_languages = get_active_languages();

// POST İşlemleri (Ekleme, Düzenleme, Silme, Durum Değiştirme, Portfolyodan Aktarma)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrf)) {
        flash('error', 'Güvenlik doğrulaması başarısız (CSRF).');
        redirect('admin/homepage.php');
    }

    $now = date('Y-m-d H:i:s');

    if ($action === 'create') {
        $image_path     = trim($_POST['image_path'] ?? '');
        $title_tr       = trim($_POST['title_tr'] ?? '');
        $title_en       = trim($_POST['title_en'] ?? '');
        $description_tr = trim($_POST['description_tr'] ?? '');
        $description_en = trim($_POST['description_en'] ?? '');
        $location       = trim($_POST['location'] ?? '');
        $year           = trim($_POST['year'] ?? '');
        $link_url       = trim($_POST['link_url'] ?? '');
        $is_active      = !empty($_POST['is_active']) ? 1 : 0;

        if (empty($image_path)) {
            flash('error', 'Lütfen bir fotoğraf seçin veya dosya yolu girin.');
        } else {
            $maxWeight = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) FROM `homepage_slides`")->fetchColumn();
            $weight = $maxWeight + 10;

            $stmt = $pdo->prepare("
                INSERT INTO `homepage_slides` (
                    `image_path`, `title_tr`, `title_en`, `description_tr`, `description_en`,
                    `location`, `year`, `link_url`, `weight`, `is_active`, `created_at`, `updated_at`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $image_path,
                $title_tr,
                $title_en ?: $title_tr,
                $description_tr,
                $description_en ?: $description_tr,
                $location,
                $year,
                $link_url,
                $weight,
                $is_active,
                $now,
                $now
            ]);
            $new_slide_id = (int)$pdo->lastInsertId();
            foreach ($site_languages as $sl) {
                $lc = $sl['code'];
                if (in_array($lc, ['tr', 'en'], true)) continue;
                ensure_language_columns($lc);
                $l_t = trim($_POST['title_' . $lc] ?? '');
                $l_d = trim($_POST['description_' . $lc] ?? '');
                try {
                    $pdo->prepare("UPDATE `homepage_slides` SET `title_{$lc}` = ?, `description_{$lc}` = ? WHERE `id` = ?")->execute([$l_t, $l_d, $new_slide_id]);
                } catch (\Throwable $e) {}
            }
            flash('success', 'Ana sayfa seçkisine yeni fotoğraf eklendi.');
        }
        redirect('admin/homepage.php');
    }

    if ($action === 'edit') {
        $id             = (int)($_POST['id'] ?? 0);
        $image_path     = trim($_POST['image_path'] ?? '');
        $title_tr       = trim($_POST['title_tr'] ?? '');
        $title_en       = trim($_POST['title_en'] ?? '');
        $description_tr = trim($_POST['description_tr'] ?? '');
        $description_en = trim($_POST['description_en'] ?? '');
        $location       = trim($_POST['location'] ?? '');
        $year           = trim($_POST['year'] ?? '');
        $link_url       = trim($_POST['link_url'] ?? '');
        $weight         = (int)($_POST['weight'] ?? 0);
        $is_active      = !empty($_POST['is_active']) ? 1 : 0;

        if ($id > 0 && !empty($image_path)) {
            $stmt = $pdo->prepare("
                UPDATE `homepage_slides`
                SET `image_path` = ?, `title_tr` = ?, `title_en` = ?, `description_tr` = ?, `description_en` = ?,
                    `location` = ?, `year` = ?, `link_url` = ?, `weight` = ?, `is_active` = ?, `updated_at` = ?
                WHERE `id` = ?
            ");
            $stmt->execute([
                $image_path,
                $title_tr,
                $title_en ?: $title_tr,
                $description_tr,
                $description_en ?: $description_tr,
                $location,
                $year,
                $link_url,
                $weight,
                $is_active,
                $now,
                $id
            ]);
            foreach ($site_languages as $sl) {
                $lc = $sl['code'];
                if (in_array($lc, ['tr', 'en'], true)) continue;
                ensure_language_columns($lc);
                $l_t = trim($_POST['title_' . $lc] ?? '');
                $l_d = trim($_POST['description_' . $lc] ?? '');
                try {
                    $pdo->prepare("UPDATE `homepage_slides` SET `title_{$lc}` = ?, `description_{$lc}` = ? WHERE `id` = ?")->execute([$l_t, $l_d, $id]);
                } catch (\Throwable $e) {}
            }
            flash('success', 'Seçki fotoğrafı başarıyla güncellendi.');
        } else {
            flash('error', 'Lütfen fotoğraf dosya yolunu boş bırakmayın.');
        }
        redirect('admin/homepage.php');
    }

    if ($action === 'import_portfolio') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM `items` WHERE `id` = ? LIMIT 1");
            $stmt->execute([$item_id]);
            $it = $stmt->fetch();

            if ($it && !empty($it['cover_image'])) {
                $maxWeight = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) FROM `homepage_slides`")->fetchColumn();
                $weight = $maxWeight + 10;
                
                $link = ($it['type'] === 'project') ? ('projects/' . ($it['slug_en'] ?: $it['slug']) . '/') : (($it['slug_en'] ?: $it['slug']) . '/');

                $ins = $pdo->prepare("
                    INSERT INTO `homepage_slides` (
                        `item_id`, `image_path`, `title_tr`, `title_en`, `description_tr`, `description_en`,
                        `location`, `year`, `link_url`, `weight`, `is_active`, `created_at`, `updated_at`
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
                ");
                $ins->execute([
                    $it['id'],
                    $it['cover_image'],
                    $it['title_tr'],
                    $it['title_en'],
                    $it['description_tr'],
                    $it['description_en'],
                    $it['location'],
                    $it['year'],
                    $link,
                    $weight,
                    $now,
                    $now
                ]);
                $new_slide_id = (int)$pdo->lastInsertId();

                foreach ($site_languages as $sl) {
                    $lc = $sl['code'];
                    if (in_array($lc, ['tr', 'en'], true)) continue;
                    $l_t = $it['title_' . $lc] ?? '';
                    $l_d = $it['description_' . $lc] ?? '';
                    if ($l_t !== '' || $l_d !== '') {
                        try {
                            $pdo->prepare("UPDATE `homepage_slides` SET `title_{$lc}` = ?, `description_{$lc}` = ? WHERE `id` = ?")->execute([$l_t, $l_d, $new_slide_id]);
                        } catch (\Throwable $e) {}
                    }
                }

                $succ_title = item_field($it, 'title', $admin_lang) ?: ($admin_lang === 'tr' ? $it['title_tr'] : ($it['title_en'] ?: $it['title_tr']));
                flash('success', '“' . $succ_title . '” ' . __a('portfolyodan ana sayfa seçkisine aktarıldı.', 'imported from portfolio to homepage showcase.'));
            } else {
                flash('error', __a('Seçilen öğenin kapak fotoğrafı bulunamadı.', 'Cover image not found for the selected work.'));
            }
        }
        redirect('admin/homepage.php');
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE `homepage_slides` SET `is_active` = CASE WHEN `is_active` = 1 THEN 0 ELSE 1 END, `updated_at` = ? WHERE `id` = ?")->execute([$now, $id]);
            flash('success', __a('Fotoğraf görünürlüğü güncellendi.', 'Photo visibility updated.'));
        }
        redirect('admin/homepage.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM `homepage_slides` WHERE `id` = ?");
            $stmt->execute([$id]);
            flash('success', __a('Fotoğraf ana sayfa seçkisinden kaldırıldı.', 'Photo removed from homepage showcase.'));
        }
        redirect('admin/homepage.php');
    }
}

// Tüm Seçki Fotoğraflarını Sırasıyla Çek
$slides = $pdo->query("SELECT * FROM `homepage_slides` ORDER BY `weight` ASC, `id` DESC")->fetchAll(PDO::FETCH_ASSOC);

// Portfolyo Öğelerini Çek (Hızlı ID haritası ve içe aktarma modalı için)
$portfolio_items = $pdo->query("SELECT * FROM `items` WHERE `status` = 'published' AND `type` != 'page' ORDER BY `type` ASC, `weight` ASC")->fetchAll(PDO::FETCH_ASSOC);
$items_by_id = [];
$items_by_slug = [];
foreach ($portfolio_items as $pit) {
    $items_by_id[$pit['id']] = $pit;
    if (!empty($pit['slug'])) $items_by_slug[$pit['slug']] = $pit;
    if (!empty($pit['slug_en'])) $items_by_slug[$pit['slug_en']] = $pit;
    if (!empty($pit['slug_tr'])) $items_by_slug[$pit['slug_tr']] = $pit;
}

$cat_label_map = [];
try {
    foreach ($pdo->query("SELECT * FROM `categories`")->fetchAll(PDO::FETCH_ASSOC) as $cr) {
        $cat_label_map[$cr['key_slug']] = get_category_localized_name($cr, $admin_lang);
    }
} catch (\Throwable $e) {}

$total_slides = count($slides);
$active_slides = count(array_filter($slides, fn($s) => !empty($s['is_active'])));

$admin_title = __a('menu_homepage');
$breadcrumb_root = __a('bc_pages');
$breadcrumb_page = __a('menu_homepage');

require __DIR__ . '/includes/header.php';
?>

<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('homepage_showcase_title')) ?></h1>
        <p class="page-header-sub">
            <?= e(__a('homepage_showcase_sub')) ?>
        </p>
    </div>
    <div class="page-header-actions" style="display:flex; flex-wrap:wrap; gap:10px;">
        <a href="<?= url($admin_lang . '/') ?>" target="_blank" class="btn btn-secondary" title="<?= e(__a('view_homepage')) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
            <span><?= e(__a('view_homepage')) ?></span>
        </a>
        <button type="button" class="btn btn-secondary" onclick="openPortfolioImportModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
            <span><?= e(__a('import_from_portfolio')) ?></span>
        </button>
        <button type="button" class="btn btn-primary" onclick="openSlideModal('add')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span><?= e(__a('add_new_photo')) ?></span>
        </button>
    </div>
</div>

<!-- KPI / Özet Kartları (Stravia Standart) -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
        </div>
        <div>
            <div class="kpi-meta-label"><?= e(__a('total_showcase')) ?></div>
            <div class="kpi-value-num"><?= $total_slides ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted);"><?= e(__a('showcase_photos_count')) ?></span></div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon-box" style="background:rgba(16,185,129,0.12); color:var(--success-text);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div>
            <div class="kpi-meta-label"><?= e(__a('active_showcase')) ?></div>
            <div class="kpi-value-num" style="color:var(--success-text);"><?= $active_slides ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted);"><?= e(__a('status_published')) ?></span></div>
        </div>
    </div>

</div>

<!-- Tablo / Liste Görünümü -->
<div class="card" style="padding: 0; overflow: hidden; margin-bottom: 30px;">
    <div class="card-header">
        <div class="card-title">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
            <span><?= e(__a('homepage_showcase_title')) ?> (<?= e(__a('reorder_drag')) ?>)</span>
        </div>
        <div style="font-size:12px; color:var(--text-sub); font-family:var(--font-mono);">
            auto-sync
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table" data-reorder="true" data-target="homepage_slides" data-m="homepage">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;"><?= e(__a('order')) ?></th>
                    <th style="width: 120px;"><?= e(__a('image')) ?></th>
                    <th><?= e(__a('title')) ?> & <?= e(__a('description')) ?></th>
                    <th style="width: 90px; text-align: center;"><?= e(__a('status')) ?></th>
                    <th style="width: 110px; text-align: right;"><?= e(__a('actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($slides)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-sub); padding: 48px 20px;">
                            <div style="font-size: 16px; font-weight:600; color:var(--text-main); margin-bottom:6px;">Henüz ana sayfa seçkisi oluşturulmadı</div>
                            <p style="font-size:13px; color:var(--text-sub); margin-bottom:16px;">Portfolyodaki projelerden seçebilir veya medya kütüphanesinden yeni fotoğraf ekleyebilirsiniz.</p>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openPortfolioImportModal()">Portfolyodan İçe Aktar</button>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($slides as $idx => $s): 
                        $thumb_url = get_thumbnail_url($s['image_path'], 320);

                        // Bağlı portfolyo çalışmasını belirle
                        $matched_item = null;
                        if (!empty($s['item_id']) && isset($items_by_id[$s['item_id']])) {
                            $matched_item = $items_by_id[$s['item_id']];
                        } else {
                            $clean_url = trim(trim($s['link_url'] ?? ''), '/');
                            $parts = explode('/', $clean_url);
                            $last_slug = end($parts);
                            if (!empty($last_slug) && isset($items_by_slug[$last_slug])) {
                                $matched_item = $items_by_slug[$last_slug];
                            } else {
                                foreach ($portfolio_items as $pit) {
                                    if (!empty($pit['cover_image']) && $pit['cover_image'] === $s['image_path']) {
                                        $matched_item = $pit;
                                        break;
                                    }
                                }
                            }
                        }

                        $target_item_id = $matched_item['id'] ?? ($s['item_id'] ?? null);
                        $edit_url = $target_item_id ? ('item-edit.php?id=' . (int)$target_item_id . '&return=homepage') : ('homepage-slide-edit.php?id=' . (int)$s['id']);

                        $slide_title = '';
                        if ($matched_item) {
                            $slide_title = item_field($matched_item, 'title', $admin_lang) ?: ($admin_lang === 'tr' ? ($matched_item['title_tr'] ?: $matched_item['title_en']) : ($matched_item['title_en'] ?: $matched_item['title_tr']));
                        }
                        if (!$slide_title) {
                            $slide_title = item_field($s, 'title', $admin_lang) ?: ($admin_lang === 'tr' ? ($s['title_tr'] ?: $s['title_en']) : ($s['title_en'] ?: $s['title_tr']));
                        }
                        $slide_title = $slide_title ?: __a('(Başlıksız Kare)', '(Untitled Slide)');

                        $slide_desc = '';
                        if ($matched_item) {
                            $slide_desc = item_field($matched_item, 'description', $admin_lang) ?: ($admin_lang === 'tr' ? ($matched_item['description_tr'] ?: $matched_item['description_en']) : ($matched_item['description_en'] ?: $matched_item['description_tr']));
                        }
                        if (!$slide_desc) {
                            $slide_desc = item_field($s, 'description', $admin_lang) ?: ($admin_lang === 'tr' ? ($s['description_tr'] ?: $s['description_en']) : ($s['description_en'] ?: $s['description_tr']));
                        }
                    ?>
                        <tr data-id="<?= $s['id'] ?>">
                            <td class="drag-handle-cell drag-handle" title="Sıralamak için sürükleyin">
                                <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
                                    <svg class="drag-grip-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                                    <span class="weight-label" style="font-family:var(--font-mono); font-size:12px; color:var(--text-sub); font-weight:600;"><?= $idx + 1 ?></span>
                                </div>
                            </td>
                            <td style="width:96px;">
                                <a href="<?= e($edit_url) ?>" class="drag-handle" title="<?= e(__a('edit')) ?>" style="width:76px; height:50px; border-radius:6px; overflow:hidden; border:1px solid var(--border-color); background:#000; position:relative; box-shadow:0 2px 6px rgba(0,0,0,0.08); display:block; flex-shrink:0;">
                                    <img src="<?= e($thumb_url) ?>" width="76" height="50" loading="lazy" decoding="async" alt="<?= e($slide_title) ?>" style="width:76px !important; height:50px !important; max-width:76px !important; max-height:50px !important; object-fit:cover; display:block;" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'76\' height=\'50\' fill=\'%23222\'><rect width=\'76\' height=\'50\'/><text x=\'50%25\' y=\'50%25\' fill=\'%23666\' font-size=\'10\' text-anchor=\'middle\' dy=\'.3em\'>Görsel Yok</text></svg>'">
                                </a>
                            </td>
                            <td>
                                <a href="<?= e($edit_url) ?>" style="font-weight:600; font-size:14px; color:var(--text-main); text-decoration:none; display:inline-block; margin-bottom:3px;">
                                    <?= e($slide_title) ?>
                                </a>
                                <?php if ($slide_desc !== ''): ?>
                                    <div style="font-size:12px; color:var(--text-muted); line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                        <?= e($slide_desc) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-neutral' ?>" style="cursor: pointer; border: 1px solid transparent;" title="<?= e(__a('status')) ?>">
                                        <?= $s['is_active'] ? ('● ' . e(__a('status_published'))) : ('○ ' . e(__a('status_site_hidden'))) ?>
                                    </button>
                                </form>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <a href="<?= e($edit_url) ?>" class="btn btn-secondary btn-sm"><?= e(__a('edit')) ?></a>
                                    <details class="row-menu">
                                        <summary class="btn btn-secondary btn-sm" aria-label="<?= e(__a('actions')) ?>">⋯</summary>
                                        <div class="row-menu-list">
                                            <form method="POST" data-confirm="<?= e(__a('Bu fotoğraf ana sayfa seçkisinden kaldırılsın mı? Fotoğrafın kendisi medya kütüphanesinde kalır.', 'Remove this photo from the homepage selection? The photo itself stays in the media library.')) ?>" data-confirm-ok="<?= e(__a('Seçkiden kaldır', 'Remove from selection')) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="is-danger"><?= e(__a('Seçkiden kaldır', 'Remove from selection')) ?></button>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ── MODAL 1: SEÇKİ FOTOĞRAFI EKLE / DÜZENLE ── -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div id="slideModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:var(--bg-surface); border-radius:10px; width:92%; max-width:680px; max-height:90vh; display:flex; flex-direction:column; box-shadow:var(--card-shadow); overflow:hidden; border:1px solid var(--border-subtle); animation:modalFadeIn 0.18s ease-out;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; border-bottom:1px solid var(--border-subtle); background:var(--bg-surface-2);">
            <h3 id="modalTitle" style="font-size:16px; font-weight:700; color:var(--text-main); margin:0;"><?= e(__a('slide_modal_add')) ?></h3>
            <button type="button" onclick="closeSlideModal()" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:22px; line-height:1;">&times;</button>
        </div>
        
        <form method="POST" id="slideForm" style="padding:24px; overflow-y:auto;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId" value="">

            <!-- 1. Fotoğraf Seçici -->
            <div style="margin-bottom:18px;">
                <label class="form-label" style="font-weight:600;"><?= e(__a('slide_image_url')) ?> *</label>
                <div style="display:flex; gap:8px; margin-bottom:10px;">
                    <input type="text" name="image_path" id="input_image_path" class="form-control" placeholder="Örn: uploads/photo.jpg" required oninput="updateImagePreview(this.value)">
                    <button type="button" class="btn btn-secondary" onclick="openMediaModalForInput('input_image_path')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        <span><?= e(__a('slide_choose_media')) ?></span>
                    </button>
                </div>

                <!-- Canlı Önizleme -->
                <div id="image_preview_container" style="display:none; width:100%; height:160px; border-radius:6px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--bg-canvas-subtle); position:relative;">
                    <img id="input_image_path_preview" src="" style="width:100%; height:100%; object-fit:cover;" />
                </div>
            </div>

            <!-- 2. Başlıklar (TR & EN) -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div>
                    <label class="form-label"><?= e(__a('slide_title_tr')) ?></label>
                    <input type="text" name="title_tr" id="f_title_tr" class="form-control" placeholder="Örn: Kuzeyden Kaçış">
                </div>
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <label class="form-label" style="margin:0;"><?= e(__a('slide_title_en')) ?></label>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="autoTranslateSlideModal()" style="font-size:12px; padding:2px 7px;"><?= e(__a('cat_modal_auto_translate')) ?></button>
                    </div>
                    <input type="text" name="title_en" id="f_title_en" class="form-control" placeholder="Örn: Escape to the North">
                </div>
            </div>

            <!-- 3. Konum & Yıl -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div>
                    <label class="form-label"><?= e(__a('slide_location')) ?></label>
                    <input type="text" name="location" id="f_location" class="form-control" placeholder="Örn: Burkina Faso">
                </div>
                <div>
                    <label class="form-label"><?= e(__a('slide_year')) ?></label>
                    <input type="text" name="year" id="f_year" class="form-control" placeholder="Örn: 2024">
                </div>
            </div>

            <!-- 4. Açıklama / Metin (TR & EN) -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div>
                    <label class="form-label"><?= e(__a('slide_desc_tr')) ?></label>
                    <textarea name="description_tr" id="f_description_tr" rows="3" class="form-control" placeholder="..."></textarea>
                </div>
                <div>
                    <label class="form-label"><?= e(__a('slide_desc_en')) ?></label>
                    <textarea name="description_en" id="f_description_en" rows="3" class="form-control" placeholder="..."></textarea>
                </div>
            </div>

            <?php foreach ($site_languages as $sl):
                $lc = $sl['code'];
                if (in_array($lc, ['tr', 'en'], true)) continue;
            ?>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; background:var(--bg-canvas-subtle); padding:10px; border-radius:6px; border:1px solid var(--border-subtle);">
                <div>
                    <label class="form-label" style="font-size:12px;"><?= e($sl['flag'] ?? '🌐') ?> <?= e(get_localized_language_name($sl['code'], $admin_lang)) ?> <?= e(__a('title')) ?></label>
                    <input type="text" name="title_<?= e($lc) ?>" id="f_title_<?= e($lc) ?>" class="form-control" placeholder="<?= e($sl['name_native']) ?>">
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;"><?= e($sl['flag'] ?? '🌐') ?> <?= e(get_localized_language_name($sl['code'], $admin_lang)) ?> <?= e(__a('description')) ?></label>
                    <input type="text" name="description_<?= e($lc) ?>" id="f_description_<?= e($lc) ?>" class="form-control" placeholder="<?= e($sl['name_native']) ?>">
                </div>
            </div>
            <?php endforeach; ?>

            <!-- 5. Tıklama Bağlantısı (Opsiyonel) & Sıralama -->
            <div style="display:grid; grid-template-columns:2fr 1fr; gap:14px; margin-bottom:16px;">
                <div>
                    <label class="form-label"><?= e(__a('slide_link')) ?></label>
                    <input type="text" name="link_url" id="f_link_url" class="form-control" placeholder="Örn: projects/through-the-fire/">
                </div>
                <div>
                    <label class="form-label"><?= e(__a('order')) ?></label>
                    <input type="number" name="weight" id="f_weight" class="form-control" value="0">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:500;">
                    <input type="checkbox" name="is_active" id="f_is_active" value="1" checked style="width:16px; height:16px;">
                    <span><?= e(__a('status_active')) ?> (<?= e(__a('active_showcase')) ?>)</span>
                </label>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--border-subtle); padding-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="closeSlideModal()"><?= e(__a('cancel')) ?></button>
                <button type="submit" class="btn btn-primary" id="btnSubmit"><?= e(__a('save')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ── MODAL 2: PORTFOLYODAN İÇE AKTAR ── -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div id="portfolioImportModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:var(--bg-surface); border-radius:10px; width:92%; max-width:600px; max-height:85vh; display:flex; flex-direction:column; box-shadow:var(--card-shadow); overflow:hidden; border:1px solid var(--border-subtle); animation:modalFadeIn 0.18s ease-out;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; border-bottom:1px solid var(--border-subtle); background:var(--bg-surface-2);">
            <h3 style="font-size:16px; font-weight:700; color:var(--text-main); margin:0;"><?= e(__a('slide_modal_import')) ?></h3>
            <button type="button" onclick="closePortfolioImportModal()" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:22px; line-height:1;">&times;</button>
        </div>

        <form method="POST" style="padding:24px;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="import_portfolio">

            <div style="margin-bottom:20px;">
                <label class="form-label" style="font-weight:600;"><?= e(__a('category')) ?> / <?= e(__a('record_item')) ?></label>
                <select name="item_id" class="form-control" required style="font-size:14px; padding:10px 12px;">
                    <option value="">-- <?= e(__a('portfolio_all_categories')) ?> --</option>
                    <?php 
                    $curr_type = '';
                    foreach ($portfolio_items as $item): 
                        if ($item['type'] !== $curr_type) {
                            if ($curr_type !== '') echo '</optgroup>';
                            $curr_type = $item['type'];
                            $type_label = $cat_label_map[$curr_type] ?? get_category_localized_name($curr_type, $admin_lang);
                            echo '<optgroup label="' . htmlspecialchars($type_label) . '">';
                        }
                        $p_title = item_field($item, 'title', $admin_lang) ?: ($admin_lang === 'tr' ? ($item['title_tr'] ?: $item['title_en']) : ($item['title_en'] ?: $item['title_tr']));
                        $p_loc   = item_field($item, 'location', $admin_lang) ?: ($item['location'] ?: '—');
                    ?>
                        <option value="<?= $item['id'] ?>">
                            <?= e($p_title) ?> (<?= e($p_loc) ?> • <?= e($item['year'] ?: '—') ?>)
                        </option>
                    <?php endforeach; ?>
                    <?php if ($curr_type !== '') echo '</optgroup>'; ?>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--border-subtle); padding-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="closePortfolioImportModal()"><?= e(__a('cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= e(__a('import_from_portfolio')) ?></button>
            </div>
        </form>
    </div>
</div>

<?php 
// Medya Modalını Dahil Et (Böylece "Medyadan Seç" doğrudan çalışır)
require_once __DIR__ . '/includes/image_modal.php'; 
?>

<script>
function updateImagePreview(path) {
    var container = document.getElementById('image_preview_container');
    var img = document.getElementById('input_image_path_preview');
    if (!path || path.trim() === '') {
        container.style.display = 'none';
        return;
    }
    var cleanPath = path.trim().replace(/^\/+/, '');
    if (!cleanPath.startsWith('content/') && !cleanPath.startsWith('uploads/') && !cleanPath.startsWith('images/') && !cleanPath.startsWith('http')) {
        cleanPath = 'content/' + cleanPath;
    }
    img.src = cleanPath.startsWith('http') ? cleanPath : ('<?= url("") ?>' + cleanPath);
    container.style.display = 'block';
}

var SLIDE_EXTRA_LANGS = <?= json_encode(array_values(array_diff(array_column($site_languages, 'code'), ['tr', 'en']))) ?>;

function autoTranslateSlideModal() {
    var srcTitleEn = document.getElementById('f_title_en') ? document.getElementById('f_title_en').value.trim() : '';
    var srcDescEn = document.getElementById('f_description_en') ? document.getElementById('f_description_en').value.trim() : '';
    var srcTitleTr = document.getElementById('f_title_tr') ? document.getElementById('f_title_tr').value.trim() : '';
    var srcDescTr = document.getElementById('f_description_tr') ? document.getElementById('f_description_tr').value.trim() : '';

    if (!srcTitleEn && !srcTitleTr && !srcDescEn && !srcDescTr) {
        alert('Önce İngilizce veya Türkçe başlık/açıklama girin.');
        return;
    }

    function translateFromEn(titleEn, descEn) {
        SLIDE_EXTRA_LANGS.forEach(function(tl) {
            fetch('<?= url("admin/api-translate.php") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: '<?= csrf_token() ?>',
                    source_lang: 'en',
                    target_lang: tl,
                    texts: [titleEn, descEn]
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d && d.ok && d.translations) {
                    var isW = function(s) { return s && (s.toLowerCase().indexOf('mymemory warning') !== -1 || s.toLowerCase().indexOf('doc/usagelimits') !== -1); };
                    var tEl = document.getElementById('f_title_' + tl);
                    var dEl = document.getElementById('f_description_' + tl);
                    if (tEl && d.translations[0] && !isW(d.translations[0])) tEl.value = d.translations[0];
                    if (dEl && d.translations[1] && !isW(d.translations[1])) dEl.value = d.translations[1];
                }
            });
        });
    }

    if (srcTitleEn || srcDescEn) {
        translateFromEn(srcTitleEn || srcTitleTr, srcDescEn || srcDescTr);
    } else {
        fetch('<?= url("admin/api-translate.php") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: '<?= csrf_token() ?>',
                source_lang: 'tr',
                target_lang: 'en',
                texts: [srcTitleTr, srcDescTr]
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d && d.ok && d.translations) {
                var enT = d.translations[0] || '';
                var enD = d.translations[1] || '';
                if (enT) document.getElementById('f_title_en').value = enT;
                if (enD) document.getElementById('f_description_en').value = enD;
                translateFromEn(enT, enD);
            }
        });
    }
}

var ALL_SLIDES = <?= json_encode(array_column($slides, null, 'id'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function openSlideModal(mode, dataOrId) {
    var modal = document.getElementById('slideModal');
    var title = document.getElementById('modalTitle');
    var action = document.getElementById('formAction');
    var btn = document.getElementById('btnSubmit');

    var data = (typeof dataOrId === 'number' || typeof dataOrId === 'string') ? (ALL_SLIDES[dataOrId] || null) : dataOrId;

    if (mode === 'edit' && data) {
        title.innerText = <?= json_encode(__a('slide_modal_edit')) ?>;
        action.value = 'edit';
        btn.innerText = <?= json_encode(__a('save_changes')) ?>;

        document.getElementById('formId').value = data.id || '';
        document.getElementById('input_image_path').value = data.image_path || '';
        document.getElementById('f_title_tr').value = data.title_tr || '';
        document.getElementById('f_title_en').value = data.title_en || '';
        document.getElementById('f_location').value = data.location || '';
        document.getElementById('f_year').value = data.year || '';
        document.getElementById('f_description_tr').value = data.description_tr || '';
        document.getElementById('f_description_en').value = data.description_en || '';
        SLIDE_EXTRA_LANGS.forEach(function(lc) {
            var tEl = document.getElementById('f_title_' + lc);
            var dEl = document.getElementById('f_description_' + lc);
            if (tEl) tEl.value = data['title_' + lc] || '';
            if (dEl) dEl.value = data['description_' + lc] || '';
        });
        document.getElementById('f_link_url').value = data.link_url || '';
        document.getElementById('f_weight').value = data.weight || 0;
        document.getElementById('f_is_active').checked = (parseInt(data.is_active, 10) === 1);

        updateImagePreview(data.image_path);
    } else {
        title.innerText = <?= json_encode(__a('slide_modal_add')) ?>;
        action.value = 'create';
        btn.innerText = <?= json_encode(__a('slide_save_btn')) ?>;

        document.getElementById('slideForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('f_is_active').checked = true;
        updateImagePreview('');
    }

    modal.style.display = 'flex';
}

function closeSlideModal() {
    document.getElementById('slideModal').style.display = 'none';
}

function openPortfolioImportModal() {
    document.getElementById('portfolioImportModal').style.display = 'flex';
}

function closePortfolioImportModal() {
    document.getElementById('portfolioImportModal').style.display = 'none';
}

// Modal dışına tıklandığında kapatma
window.addEventListener('click', function(e) {
    var m1 = document.getElementById('slideModal');
    var m2 = document.getElementById('portfolioImportModal');
    if (e.target === m1) closeSlideModal();
    if (e.target === m2) closePortfolioImportModal();
});
</script>

<style>
@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.97) translateY(-8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
