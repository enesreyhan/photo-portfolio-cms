<?php
/**
 * admin/categories.php
 * Portfolyo Kategorileri Yönetimi
 * Kullanıcı yeni kategoriler ekleyebilir, sitenin üst menüsünde gösterilip gösterilmeyeceğini belirleyebilir.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$site_languages = get_all_languages();

// POST İşlemleri (Ekleme, Düzenleme, Silme, Menüde Gösterimi Değiştirme)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrf)) {
        flash('error', 'Güvenlik doğrulaması başarısız (CSRF).');
        redirect('admin/categories.php');
    }

    if ($action === 'create') {
        $name_tr = trim($_POST['name_tr'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '');
        $key_slug = slugify($_POST['key_slug'] ?? $name_en ?: $name_tr);
        $slug_tr = slugify($_POST['slug_tr'] ?? $name_tr);
        $slug_en = slugify($_POST['slug_en'] ?? $name_en ?: $name_tr);
        $show_in_menu = !empty($_POST['show_in_menu']) ? 1 : 0;

        if (empty($name_tr) || empty($key_slug)) {
            flash('error', 'Kategori adı ve sistem kodu zorunludur.');
        } else {
            // Sistem kodu çakışma kontrolü
            $exists = $pdo->prepare("SELECT COUNT(*) FROM `categories` WHERE `key_slug` = ?");
            $exists->execute([$key_slug]);
            if ($exists->fetchColumn() > 0) {
                flash('error', 'Bu sistem koduna (' . htmlspecialchars($key_slug) . ') sahip bir kategori zaten mevcut.');
            } else {
                $maxWeight = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) FROM `categories`")->fetchColumn();
                $weight = $maxWeight + 10;

                $menu_id = null;
                if ($show_in_menu) {
                    // menus tablosuna ekle
                    $maxMenuWeight = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) FROM `menus`")->fetchColumn();
                    $m_stmt = $pdo->prepare("
                        INSERT INTO `menus` (`title_tr`, `title_en`, `url_tr`, `url_en`, `weight`, `is_active`, `separator_before`, `is_system`)
                        VALUES (?, ?, ?, ?, ?, 1, 0, 0)
                    ");
                    $m_stmt->execute([
                        $name_tr,
                        $name_en ?: $name_tr,
                        'tr/' . $slug_tr . '/',
                        'en/' . $slug_en . '/',
                        $maxMenuWeight + 10
                    ]);
                    $menu_id = (int)$pdo->lastInsertId();
                }

                $stmt = $pdo->prepare("
                    INSERT INTO `categories` (`key_slug`, `name_tr`, `name_en`, `slug_tr`, `slug_en`, `show_in_menu`, `menu_id`, `weight`, `created_at`, `updated_at`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([
                    $key_slug,
                    $name_tr,
                    $name_en ?: $name_tr,
                    $slug_tr,
                    $slug_en ?: $slug_tr,
                    $show_in_menu,
                    $menu_id,
                    $weight
                ]);
                $new_cat_id = (int)$pdo->lastInsertId();

                foreach ($site_languages as $sl) {
                    $lc = $sl['code'];
                    if (in_array($lc, ['tr', 'en'], true)) continue;
                    ensure_language_columns($lc);
                    $l_name = trim($_POST['name_' . $lc] ?? '');
                    $l_slug = slugify($_POST['slug_' . $lc] ?? ($l_name ?: $slug_en));
                    try {
                        $pdo->prepare("UPDATE `categories` SET `name_{$lc}` = ?, `slug_{$lc}` = ? WHERE `id` = ?")->execute([$l_name, $l_slug, $new_cat_id]);
                        if ($menu_id && $l_name !== '') {
                            $pdo->prepare("UPDATE `menus` SET `title_{$lc}` = ?, `url_{$lc}` = ? WHERE `id` = ?")->execute([$l_name, $lc . '/' . $l_slug . '/', $menu_id]);
                        }
                    } catch (\Throwable $e) {}
                }

                flash('success', 'Yeni kategori başarıyla eklendi.');
            }
        }
        redirect('admin/categories.php');
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name_tr = trim($_POST['name_tr'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '');
        $slug_tr = slugify($_POST['slug_tr'] ?? $name_tr);
        $slug_en = slugify($_POST['slug_en'] ?? $name_en ?: $name_tr);
        $new_key_slug = slugify($_POST['key_slug'] ?? '');
        $show_in_menu = !empty($_POST['show_in_menu']) ? 1 : 0;

        if ($id > 0 && !empty($name_tr)) {
            // Mevcut kategori kaydını al
            $cat_stmt = $pdo->prepare("SELECT * FROM `categories` WHERE `id` = ?");
            $cat_stmt->execute([$id]);
            $current_cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);

            if ($current_cat) {
                $old_key_slug = $current_cat['key_slug'];
                $final_key_slug = $new_key_slug ?: $old_key_slug;

                // Sistem kodu değiştirildiyse çakışma kontrolü yap
                if ($final_key_slug !== $old_key_slug) {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM `categories` WHERE `key_slug` = ? AND `id` != ?");
                    $chk->execute([$final_key_slug, $id]);
                    if ($chk->fetchColumn() > 0) {
                        flash('error', 'Bu sistem koduna (' . htmlspecialchars($final_key_slug) . ') sahip başka bir kategori zaten mevcut.');
                        redirect('admin/categories.php');
                    }
                }

                $menu_id = $current_cat['menu_id'];

                if ($show_in_menu) {
                    if ($menu_id) {
                        // Var olan menüyü güncelle ve aktif yap
                        $pdo->prepare("
                            UPDATE `menus` 
                            SET `title_tr` = ?, `title_en` = ?, `url_tr` = ?, `url_en` = ?, `is_active` = 1, `updated_at` = CURRENT_TIMESTAMP
                            WHERE `id` = ?
                        ")->execute([
                            $name_tr,
                            $name_en ?: $name_tr,
                            'tr/' . $slug_tr . '/',
                            'en/' . $slug_en . '/',
                            $menu_id
                        ]);
                    } else {
                        // Yeni menü kaydı oluştur
                        $maxMenuWeight = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) FROM `menus`")->fetchColumn();
                        $m_stmt = $pdo->prepare("
                            INSERT INTO `menus` (`title_tr`, `title_en`, `url_tr`, `url_en`, `weight`, `is_active`, `separator_before`, `is_system`)
                            VALUES (?, ?, ?, ?, ?, 1, 0, 0)
                        ");
                        $m_stmt->execute([
                            $name_tr,
                            $name_en ?: $name_tr,
                            'tr/' . $slug_tr . '/',
                            'en/' . $slug_en . '/',
                            $maxMenuWeight + 10
                        ]);
                        $menu_id = (int)$pdo->lastInsertId();
                    }
                } else {
                    if ($menu_id) {
                        // Menüden pasif yap
                        $pdo->prepare("UPDATE `menus` SET `is_active` = 0 WHERE `id` = ?")->execute([$menu_id]);
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE `categories`
                    SET `key_slug` = ?, `name_tr` = ?, `name_en` = ?, `slug_tr` = ?, `slug_en` = ?, `show_in_menu` = ?, `menu_id` = ?, `updated_at` = CURRENT_TIMESTAMP
                    WHERE `id` = ?
                ");
                $stmt->execute([
                    $final_key_slug,
                    $name_tr,
                    $name_en ?: $name_tr,
                    $slug_tr,
                    $slug_en ?: $slug_tr,
                    $show_in_menu,
                    $menu_id,
                    $id
                ]);

                foreach ($site_languages as $sl) {
                    $lc = $sl['code'];
                    if (in_array($lc, ['tr', 'en'], true)) continue;
                    ensure_language_columns($lc);
                    $l_name = trim($_POST['name_' . $lc] ?? '');
                    $l_slug = slugify($_POST['slug_' . $lc] ?? ($l_name ?: $slug_en));
                    try {
                        $pdo->prepare("UPDATE `categories` SET `name_{$lc}` = ?, `slug_{$lc}` = ? WHERE `id` = ?")->execute([$l_name, $l_slug, $id]);
                        if ($menu_id && $l_name !== '') {
                            $pdo->prepare("UPDATE `menus` SET `title_{$lc}` = ?, `url_{$lc}` = ? WHERE `id` = ?")->execute([$l_name, $lc . '/' . $l_slug . '/', $menu_id]);
                        }
                    } catch (\Throwable $e) {}
                }

                // Sistem kodu değiştiyse bağlı çalışmaları ve medya kayıtlarını yeni koda taşı
                if ($final_key_slug !== $old_key_slug) {
                    $pdo->prepare("UPDATE `items` SET `type` = ? WHERE `type` = ?")->execute([$final_key_slug, $old_key_slug]);
                    try {
                        $pdo->prepare("UPDATE `media` SET `item_type` = ? WHERE `item_type` = ?")->execute([$final_key_slug, $old_key_slug]);
                    } catch (\Throwable $e) {}
                }

                flash('success', 'Kategori bilgileri güncellendi.');
            }
        } else {
            flash('error', 'Lütfen kategori adını eksiksiz doldurun.');
        }
        redirect('admin/categories.php');
    }

    if ($action === 'toggle_menu') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $cat_stmt = $pdo->prepare("SELECT * FROM `categories` WHERE `id` = ?");
            $cat_stmt->execute([$id]);
            $cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);

            if ($cat) {
                $new_state = $cat['show_in_menu'] ? 0 : 1;
                $menu_id = $cat['menu_id'];

                if ($new_state) {
                    if ($menu_id) {
                        $pdo->prepare("UPDATE `menus` SET `is_active` = 1 WHERE `id` = ?")->execute([$menu_id]);
                    } else {
                        $maxMenuWeight = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) FROM `menus`")->fetchColumn();
                        $m_stmt = $pdo->prepare("
                            INSERT INTO `menus` (`title_tr`, `title_en`, `url_tr`, `url_en`, `weight`, `is_active`, `separator_before`, `is_system`)
                            VALUES (?, ?, ?, ?, ?, 1, 0, 0)
                        ");
                        $m_stmt->execute([
                            $cat['name_tr'],
                            $cat['name_en'] ?: $cat['name_tr'],
                            'tr/' . $cat['slug_tr'] . '/',
                            'en/' . $cat['slug_en'] . '/',
                            $maxMenuWeight + 10
                        ]);
                        $menu_id = (int)$pdo->lastInsertId();
                    }
                } else {
                    if ($menu_id) {
                        $pdo->prepare("UPDATE `menus` SET `is_active` = 0 WHERE `id` = ?")->execute([$menu_id]);
                    }
                }

                $pdo->prepare("UPDATE `categories` SET `show_in_menu` = ?, `menu_id` = ? WHERE `id` = ?")->execute([$new_state, $menu_id, $id]);
                flash('success', 'Menü görünürlüğü güncellendi.');
            }
        }
        redirect('admin/categories.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $cat_stmt = $pdo->prepare("SELECT * FROM `categories` WHERE `id` = ?");
            $cat_stmt->execute([$id]);
            $cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);

            if ($cat) {
                if (!empty($cat['menu_id'])) {
                    $pdo->prepare("DELETE FROM `menus` WHERE `id` = ?")->execute([$cat['menu_id']]);
                } else {
                    $u_tr = 'tr/' . $cat['slug_tr'] . '/';
                    $u_en = 'en/' . $cat['slug_en'] . '/';
                    $pdo->prepare("DELETE FROM `menus` WHERE `url_tr` = ? OR `url_en` = ?")->execute([$u_tr, $u_en]);
                }
                $pdo->prepare("DELETE FROM `categories` WHERE `id` = ?")->execute([$id]);
                flash('success', '“' . $cat['name_tr'] . '” kategorisi silindi.');
            }
        }
        redirect('admin/categories.php');
    }
}

// Kategorileri Çek
$categories = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);

// Kategori başına çalışma sayısını hesapla
$item_counts = [];
$counts_query = $pdo->query("SELECT `type`, COUNT(*) as cnt FROM `items` GROUP BY `type`")->fetchAll(PDO::FETCH_ASSOC);
foreach ($counts_query as $cq) {
    $item_counts[$cq['type']] = (int)$cq['cnt'];
}

$admin_title = __a('Portfolyo Kategorileri', 'Portfolio Categories');
$breadcrumb_root = __a('bc_system');
$breadcrumb_page = __a('menu_categories');

require __DIR__ . '/includes/header.php';
?>

<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('Portfolyo Kategorileri', 'Portfolio Categories')) ?></h1>
        <p class="page-header-sub"><?= e(__a('categories_page_sub')) ?></p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" onclick="openCatModal('add')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span><?= e(__a('cat_add_btn')) ?></span>
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header" style="padding:16px 20px;">
        <h3 class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <span><?= e(__a('cat_registered')) ?> (<?= count($categories) ?>)</span>
        </h3>
        <span class="badge badge-neutral" style="font-weight: 500;"><?= e(__a('cat_drag_hint')) ?></span>
    </div>

    <div class="table-responsive">
        <table class="admin-table" data-reorder="true" data-target="categories" data-m="categories">
            <thead>
                <tr>
                    <th style="width: 56px; text-align: center;"><?= e(__a('cat_th_order')) ?></th>
                    <th><?= e(__a('cat_th_name_tr')) ?></th>
                    <th><?= e(__a('cat_th_name_en')) ?></th>
                    <th><?= e(__a('cat_th_type')) ?></th>
                    <th><?= e(__a('cat_th_slug')) ?></th>
                    <th style="width: 100px; text-align: center;"><?= e(__a('cat_th_count')) ?></th>
                    <th style="width: 160px; text-align: center;"><?= e(__a('cat_th_menu')) ?></th>
                    <th style="width: 120px; text-align: right;"><?= e(__a('actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-sub);">
                        <?= e(__a('Henüz kayıtlı kategori bulunmuyor.')) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($categories as $idx => $cat): 
                    $count = $item_counts[$cat['key_slug']] ?? 0;
                    $del_confirm = sprintf(__a('“%s” liste sayfası ve menüdeki bağlantısı silinsin mi?', 'Delete the “%s” list page and its menu link?'), $cat['name_tr'])
                        . ($count > 0 ? ' ' . sprintf(__a('İçindeki %d çalışma silinmez.', 'Its %d works are not deleted.'), $count) : '');
                ?>
                <tr data-id="<?= $cat['id'] ?>">
                    <td class="drag-handle-cell drag-handle" title="<?= e(__a('reorder_drag')) ?>">
                        <div style="display:inline-flex; align-items:center; gap:5px;">
                            <svg class="drag-grip-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                            <span class="weight-label" style="font-family:var(--font-mono); font-size:12px; color:var(--text-sub); font-weight:700;"><?= $idx + 1 ?></span>
                        </div>
                    </td>
                    <td>
                        <strong style="font-size: 14px; color: var(--text-main);"><?= e(get_category_localized_name($cat, $admin_lang)) ?></strong>
                    </td>
                    <td>
                        <span style="font-size: 13px; color: var(--text-muted);"><?= e($cat['name_en'] ?: $cat['name_tr']) ?></span>
                    </td>
                    <td>
                        <code style="font-size: 12px; background: var(--bg-canvas-subtle); color: var(--accent-blue); padding: 3px 6px; border-radius: 6px; border: 1px solid var(--border-subtle); font-family:var(--font-mono);"><?= e($cat['key_slug']) ?></code>
                    </td>
                    <td>
                        <div style="font-size: 12px; font-family:var(--font-mono); color:var(--text-muted);">
                            <span>TR: /tr/<?= e($cat['slug_tr']) ?>/</span><br>
                            <span>EN: /en/<?= e($cat['slug_en']) ?>/</span>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <a href="portfolio.php?cat=<?= urlencode($cat['key_slug']) ?>" style="font-family:var(--font-mono); font-size:12px; font-weight:600; text-decoration:none; color:var(--success-text); background:rgba(5,150,105,0.1); padding:3px 8px; border-radius:10px;">
                            <?= $count ?> <?= e(__a('cat_items_unit')) ?>
                        </a>
                    </td>
                    <td style="text-align: center;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="toggle_menu">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="badge <?= $cat['show_in_menu'] ? 'badge-success' : 'badge-neutral' ?>" style="cursor: pointer; border: 1px solid transparent;">
                                <?= $cat['show_in_menu'] ? e(__a('cat_menu_active')) : e(__a('cat_menu_inactive')) ?>
                            </button>
                        </form>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick='openCatModal("edit", <?= json_encode($cat) ?>)'><?= e(__a('edit')) ?></button>
                            <details class="row-menu">
                                <summary class="btn btn-secondary btn-sm" aria-label="<?= e(__a('actions')) ?>">⋯</summary>
                                <div class="row-menu-list">
                                    <form method="POST" data-confirm="<?= e($del_confirm) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="is-danger"><?= e(__a('delete')) ?></button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Kategori Ekleme / Düzenleme Modal ── -->
<div id="catModal" class="modal-backdrop" style="display: none;">
    <div class="modal-box" style="max-width: 520px;">
        <div class="modal-header">
            <h3 id="catModalTitle" style="margin: 0; font-size: 16px; font-weight: 600;"><?= e(__a('cat_modal_add')) ?></h3>
            <button type="button" class="modal-close" onclick="closeCatModal()">&times;</button>
        </div>
        <form method="POST" id="catModalForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" id="cat_action" value="create">
            <input type="hidden" name="id" id="cat_id" value="">

            <div class="modal-body" style="padding: 20px;">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600;"><?= e(__a('cat_modal_name_tr')) ?></label>
                    <input type="text" name="name_tr" id="cat_name_tr" class="form-control" required placeholder="<?= e(__a('Örn: Kitaplar', 'e.g. Kitaplar')) ?>" oninput="autoFillSlugs(this.value)">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <label class="form-label" style="font-weight: 600; margin:0;"><?= e(__a('cat_modal_name_en')) ?></label>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="autoTranslateCategoryModal()" style="font-size:12px; padding:3px 8px;"><?= e(__a('cat_modal_auto_translate')) ?></button>
                    </div>
                    <input type="text" name="name_en" id="cat_name_en" class="form-control" placeholder="<?= e(__a('Örn: Books', 'e.g. Books')) ?>" oninput="autoFillEnSlugs(this.value)">
                </div>

                <?php foreach ($site_languages as $sl):
                    $lc = $sl['code'];
                    if (in_array($lc, ['tr', 'en'], true)) continue;
                ?>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:600; font-size:12px;"><?= e($sl['flag'] ?? '🌐') ?> <?= e(get_localized_language_name($sl['code'], $admin_lang)) ?></label>
                        <input type="text" name="name_<?= e($lc) ?>" id="cat_name_<?= e($lc) ?>" class="form-control" placeholder="<?= e($sl['name_native']) ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:600; font-size:12px;">URL (<?= e(strtoupper($lc)) ?>)</label>
                        <input type="text" name="slug_<?= e($lc) ?>" id="cat_slug_<?= e($lc) ?>" class="form-control font-mono" style="font-size:12px;" placeholder="slug">
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="form-group" id="group_key_slug" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600;"><?= e(__a('cat_modal_key_slug')) ?> *</label>
                    <input type="text" name="key_slug" id="cat_key_slug" class="form-control" placeholder="books">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;"><?= __a('Kalıcı URL (TR)', 'Permanent URL (TR)') ?></label>
                        <div style="display:flex; align-items:center; background:var(--bg-canvas-subtle); border:1px solid var(--border-subtle); border-radius:6px; padding:0 8px;">
                            <span style="font-size:12px; color:var(--text-sub); font-family:var(--font-mono);">/tr/</span>
                            <input type="text" name="slug_tr" id="cat_slug_tr" class="form-control" style="border:none; background:transparent; padding:7px 4px; font-size:12px; font-family:var(--font-mono);" placeholder="kitaplar">
                            <span style="font-size:12px; color:var(--text-sub); font-family:var(--font-mono);">/</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;"><?= __a('Kalıcı URL (EN)', 'Permanent URL (EN)') ?></label>
                        <div style="display:flex; align-items:center; background:var(--bg-canvas-subtle); border:1px solid var(--border-subtle); border-radius:6px; padding:0 8px;">
                            <span style="font-size:12px; color:var(--text-sub); font-family:var(--font-mono);">/en/</span>
                            <input type="text" name="slug_en" id="cat_slug_en" class="form-control" style="border:none; background:transparent; padding:7px 4px; font-size:12px; font-family:var(--font-mono);" placeholder="books">
                            <span style="font-size:12px; color:var(--text-sub); font-family:var(--font-mono);">/</span>
                        </div>
                    </div>
                </div>

                <div style="background: var(--bg-canvas-subtle); border: 1px solid var(--border-subtle); border-radius: 6px; padding: 12px 14px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="show_in_menu" id="cat_show_in_menu" value="1" checked style="width: 17px; height: 17px; margin-top: 2px;">
                        <div>
                            <strong style="font-size: 13px; color: var(--text-main); display: block;"><?= e(__a('cat_modal_show_menu')) ?></strong>
                        </div>
                    </label>
                </div>
            </div>

            <div class="modal-footer" style="padding: 12px 20px; background: var(--bg-surface-2); border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="closeCatModal()"><?= e(__a('cancel')) ?></button>
                <button type="submit" class="btn btn-primary" id="catSubmitBtn"><?= e(__a('save')) ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function cleanCatSlug(val) {
    return (val || '')
        .replace(/İ/g, 'i').replace(/I/g, 'i').replace(/ı/g, 'i')
        .replace(/Ğ/g, 'g').replace(/ğ/g, 'g')
        .replace(/Ü/g, 'u').replace(/ü/g, 'u')
        .replace(/Ş/g, 's').replace(/ş/g, 's')
        .replace(/Ö/g, 'o').replace(/ö/g, 'o')
        .replace(/Ç/g, 'c').replace(/ç/g, 'c')
        .toLowerCase()
        .replace(/\u0307/g, '')
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function autoFillSlugs(val) {
    if (document.getElementById('cat_action').value !== 'create') return;
    var s = cleanCatSlug(val);
    var enVal = document.getElementById('cat_name_en') ? document.getElementById('cat_name_en').value.trim() : '';
    
    var keyEl = document.getElementById('cat_key_slug');
    var trEl = document.getElementById('cat_slug_tr');
    var enEl = document.getElementById('cat_slug_en');
    
    if (trEl && !trEl.dataset.touched) trEl.value = s;
    if (!enVal) {
        if (keyEl && !keyEl.dataset.touched) keyEl.value = s;
        if (enEl && !enEl.dataset.touched) enEl.value = s;
    }
}

function autoFillEnSlugs(val) {
    if (document.getElementById('cat_action').value !== 'create') return;
    var s = cleanCatSlug(val);
    if (!s) return;
    var keyEl = document.getElementById('cat_key_slug');
    var enEl = document.getElementById('cat_slug_en');
    if (keyEl && !keyEl.dataset.touched) keyEl.value = s;
    if (enEl && !enEl.dataset.touched) enEl.value = s;
}

var CAT_EXTRA_LANGS = <?= json_encode(array_values(array_diff(array_column($site_languages, 'code'), ['tr', 'en']))) ?>;

function autoTranslateCategoryModal() {
    var srcEn = document.getElementById('cat_name_en') ? document.getElementById('cat_name_en').value.trim() : '';
    var srcTr = document.getElementById('cat_name_tr') ? document.getElementById('cat_name_tr').value.trim() : '';
    if (!srcEn && !srcTr) { alert(<?= json_encode(__a('Önce İngilizce veya Türkçe kategori adını girin.', 'First enter the category name in English or Turkish.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>); return; }

    function translateFromEn(enText) {
        CAT_EXTRA_LANGS.forEach(function(tl) {
            fetch('<?= url("admin/api-translate.php") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: '<?= csrf_token() ?>',
                    source_lang: 'en',
                    target_lang: tl,
                    texts: [enText]
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                var tr0 = d && d.ok && d.translations && d.translations[0];
                var isWarn = tr0 && (tr0.toLowerCase().indexOf('mymemory warning') !== -1 || tr0.toLowerCase().indexOf('doc/usagelimits') !== -1);
                if (tr0 && !isWarn) {
                    var nEl = document.getElementById('cat_name_' + tl);
                    if (nEl) nEl.value = tr0;
                    var sEl = document.getElementById('cat_slug_' + tl);
                    if (sEl && !sEl.value) sEl.value = cleanCatSlug(tr0);
                }
            });
        });
    }

    if (srcEn) {
        translateFromEn(srcEn);
        if (!srcTr) {
            fetch('<?= url("admin/api-translate.php") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: '<?= csrf_token() ?>',
                    source_lang: 'en',
                    target_lang: 'tr',
                    texts: [srcEn]
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                var tr0 = d && d.ok && d.translations && d.translations[0];
                if (tr0) document.getElementById('cat_name_tr').value = tr0;
            });
        }
    } else if (srcTr) {
        fetch('<?= url("admin/api-translate.php") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: '<?= csrf_token() ?>',
                source_lang: 'tr',
                target_lang: 'en',
                texts: [srcTr]
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var trEn = d && d.ok && d.translations && d.translations[0];
            if (trEn) {
                var nEn = document.getElementById('cat_name_en');
                if (nEn) nEn.value = trEn;
                autoFillEnSlugs(trEn);
                translateFromEn(trEn);
            }
        });
    }
}

function openCatModal(mode, cat) {
    var modal = document.getElementById('catModal');
    var title = document.getElementById('catModalTitle');
    var action = document.getElementById('cat_action');
    var idInput = document.getElementById('cat_id');
    var keyGroup = document.getElementById('group_key_slug');
    var keySlug = document.getElementById('cat_key_slug');
    var nameTr = document.getElementById('cat_name_tr');
    var nameEn = document.getElementById('cat_name_en');
    var slugTr = document.getElementById('cat_slug_tr');
    var slugEn = document.getElementById('cat_slug_en');
    var showMenu = document.getElementById('cat_show_in_menu');

    if (mode === 'edit' && cat) {
        title.textContent = <?= json_encode(__a('cat_modal_edit')) ?> + ': ' + (cat['name_' + window.ADMIN_LANG] || cat.name_tr || cat.name_en);
        action.value = 'edit';
        idInput.value = cat.id;
        nameTr.value = cat.name_tr || '';
        nameEn.value = cat.name_en || '';
        if (keySlug) {
            keySlug.value = cat.key_slug || '';
            keySlug.dataset.touched = '1';
        }
        slugTr.value = cat.slug_tr || '';
        slugEn.value = cat.slug_en || '';
        CAT_EXTRA_LANGS.forEach(function(lc) {
            var nEl = document.getElementById('cat_name_' + lc);
            var sEl = document.getElementById('cat_slug_' + lc);
            if (nEl) nEl.value = cat['name_' + lc] || '';
            if (sEl) sEl.value = cat['slug_' + lc] || '';
        });
        showMenu.checked = (cat.show_in_menu == 1);
        if (keyGroup) keyGroup.style.display = 'block';
    } else {
        title.textContent = <?= json_encode(__a('cat_modal_add')) ?>;
        action.value = 'create';
        idInput.value = '';
        nameTr.value = '';
        nameEn.value = '';
        CAT_EXTRA_LANGS.forEach(function(lc) {
            var nEl = document.getElementById('cat_name_' + lc);
            var sEl = document.getElementById('cat_slug_' + lc);
            if (nEl) nEl.value = '';
            if (sEl) sEl.value = '';
        });
        if (keySlug) {
            keySlug.value = '';
            delete keySlug.dataset.touched;
        }
        slugTr.value = '';
        delete slugTr.dataset.touched;
        slugEn.value = '';
        delete slugEn.dataset.touched;
        showMenu.checked = true;
        if (keyGroup) keyGroup.style.display = 'block';
    }

    modal.style.display = 'flex';
}

function closeCatModal() {
    document.getElementById('catModal').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCatModal();
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
