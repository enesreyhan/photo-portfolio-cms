<?php
/**
 * admin/homepage-slide-edit.php
 * Ana Sayfa Seçki Görseli / Slaytı Tam Sayfa Düzenleyici
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$site_languages = get_active_languages();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('admin/homepage.php');
}

$stmt = $pdo->prepare("SELECT * FROM `homepage_slides` WHERE `id` = ?");
$stmt->execute([$id]);
$slide = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slide) {
    flash('error', __a('Kayıt bulunamadı.', 'Record not found.'));
    redirect('admin/homepage.php');
}

// Bağlı portfolyo çalışması var mı?
$matched_item = null;
if (!empty($slide['item_id'])) {
    $st_i = $pdo->prepare("SELECT * FROM `items` WHERE `id` = ? LIMIT 1");
    $st_i->execute([$slide['item_id']]);
    $matched_item = $st_i->fetch(PDO::FETCH_ASSOC);
}

// POST Güncelleme İşlemi
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız (CSRF).');
        redirect('admin/homepage-slide-edit.php?id=' . $id);
    }

    $image_path     = trim($_POST['image_path'] ?? '');
    $title_tr       = trim($_POST['title_tr'] ?? '');
    $title_en       = trim($_POST['title_en'] ?? '');
    $description_tr = trim($_POST['description_tr'] ?? '');
    $description_en = trim($_POST['description_en'] ?? '');
    $location       = trim($_POST['location'] ?? '');
    $year           = trim($_POST['year'] ?? '');
    $link_url       = trim($_POST['link_url'] ?? '');
    $is_active      = !empty($_POST['is_active']) ? 1 : 0;
    $now            = date('Y-m-d H:i:s');

    if (empty($image_path)) {
        flash('error', __a('Lütfen bir görsel seçin.', 'Please select an image.'));
    } else {
        $stmt_up = $pdo->prepare("
            UPDATE `homepage_slides`
            SET `image_path` = ?, `title_tr` = ?, `title_en` = ?, `description_tr` = ?, `description_en` = ?,
                `location` = ?, `year` = ?, `link_url` = ?, `is_active` = ?, `updated_at` = ?
            WHERE `id` = ?
        ");
        $stmt_up->execute([
            $image_path,
            $title_tr,
            $title_en ?: $title_tr,
            $description_tr,
            $description_en ?: $description_tr,
            $location,
            $year,
            $link_url,
            $is_active,
            $now,
            $id
        ]);

        // Ek diller
        foreach ($site_languages as $sl) {
            $lc = $sl['code'];
            if (in_array($lc, ['tr', 'en'], true)) continue;
            $l_t = trim($_POST['title_' . $lc] ?? '');
            $l_d = trim($_POST['description_' . $lc] ?? '');
            try {
                $pdo->prepare("UPDATE `homepage_slides` SET `title_{$lc}` = ?, `description_{$lc}` = ? WHERE `id` = ?")->execute([$l_t, $l_d, $id]);
            } catch (\Throwable $e) {}
        }

        flash('success', __a('Ana sayfa seçki görseli güncellendi.', 'Showcase slide updated successfully.'));
        redirect('admin/homepage.php');
    }
}

$active_title = item_field($slide, 'title', $admin_lang) ?: ($admin_lang === 'tr' ? ($slide['title_tr'] ?: $slide['title_en']) : ($slide['title_en'] ?: $slide['title_tr']));
$active_title = $active_title ?: __a('Seçkiyi Düzenle', 'Edit Showcase Slide');

$admin_title     = $active_title;
$breadcrumb_root = __a('menu_homepage', 'Ana Sayfa');
$breadcrumb_page = $active_title;

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/image_modal.php';
?>

<div class="page-header-wrap" style="margin-bottom: 24px;">
    <div>
        <h1 class="page-header-title"><?= e($active_title) ?></h1>
        <p class="page-header-sub"><?= e(__a('Ana sayfa vitrininde dönecek fotoğrafın bilgilerini ve yönlendirme bağlantısını düzenleyin.', 'Edit slide details, focal point, and destination link for homepage showcase.')) ?></p>
    </div>
    <div class="page-header-actions" style="display:flex; gap:10px; align-items:center;">
        <?php if ($matched_item): ?>
            <a href="item-edit.php?id=<?= (int)$matched_item['id'] ?>&return=homepage" class="btn btn-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                <span><?= e(__a('Portfolyo Çalışmasına Git →', 'Go to Portfolio Work →')) ?></span>
            </a>
        <?php endif; ?>
        <a href="homepage.php" class="btn btn-secondary"><?= e(__a('cancel')) ?></a>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('slideForm').submit();">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span><?= e(__a('btn_save_changes')) ?></span>
        </button>
    </div>
</div>

<?php if ($matched_item): ?>
<div style="background:rgba(37,99,235,0.06); border:1px solid rgba(37,99,235,0.22); border-radius:10px; padding:12px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
    <div style="display:flex; align-items:center; gap:12px;">
        <span style="font-size:22px;">📁</span>
        <div>
            <strong style="color:var(--text-main); font-size:14px;"><?= e(__a('Bu görsel bir portfolyo çalışmasına bağlıdır:', 'This slide is linked to a portfolio work:')) ?> <?= e(item_field($matched_item, 'title', $admin_lang) ?: ($matched_item['title_en'] ?: $matched_item['title_tr'])) ?></strong>
            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;"><?= e(__a('Galeri fotoğrafları, editoryal metinler ve tüm portfolyo detayları için portfolyo düzenleyicisini kullanabilirsiniz.', 'Use the full portfolio editor to manage gallery photos, markdown narrative, and project metadata.')) ?></div>
        </div>
    </div>
    <a href="item-edit.php?id=<?= (int)$matched_item['id'] ?>&return=homepage" class="btn btn-primary btn-sm">
        <?= e(__a('Portfolyo Düzenleyiciyi Aç →', 'Open Portfolio Editor →')) ?>
    </a>
</div>
<?php endif; ?>

<form method="POST" id="slideForm" style="display:grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: start;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <!-- Sol Sütun: Fotoğraf Önizleme ve Seçim -->
    <div class="card" style="padding: 20px;">
        <h3 class="card-title" style="margin-bottom: 14px;"><?= e(__a('image')) ?></h3>
        
        <div style="position:relative; width:100%; aspect-ratio:3/2; background:#000; border-radius:6px; overflow:hidden; border:1px solid var(--border-color); margin-bottom:14px; box-shadow:var(--card-shadow);">
            <img id="img_preview" src="<?= e(get_thumbnail_url($slide['image_path'], 640)) ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label"><?= e(__a('image_path')) ?></label>
            <div style="display:flex; gap:8px;">
                <input type="text" name="image_path" id="f_image_path" class="form-control" value="<?= e($slide['image_path']) ?>" required style="font-family:var(--font-mono); font-size:12px;">
                <button type="button" class="btn btn-secondary" onclick="openMediaModalForCallback(onImageSelected)" style="flex-shrink:0;">
                    <?= e(__a('select')) ?>
                </button>
            </div>
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label"><?= e(__a('location')) ?></label>
            <input type="text" name="location" class="form-control" value="<?= e($slide['location'] ?? '') ?>" placeholder="Örn: Morogoro, Tanzanya">
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label"><?= e(__a('year')) ?></label>
            <input type="text" name="year" class="form-control" value="<?= e($slide['year'] ?? '') ?>" placeholder="Örn: 2025">
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label"><?= e(__a('Tıklama Bağlantısı (URL)', 'Click Link (URL)')) ?></label>
            <input type="text" name="link_url" class="form-control" value="<?= e($slide['link_url'] ?? '') ?>" placeholder="Örn: projects/albino-families-tanzania/">
        </div>

        <div style="padding-top:10px; border-top:1px solid var(--border-subtle);">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:14px; font-weight:600; color:var(--text-main);">
                <input type="checkbox" name="is_active" value="1" <?= !empty($slide['is_active']) ? 'checked' : '' ?> style="width:16px; height:16px;">
                <span><?= e(__a('Ana Sayfada Yayında Göster', 'Publish on Homepage')) ?></span>
            </label>
        </div>
    </div>

    <!-- Sağ Sütun: Başlık & Açıklama (Çok Dilli) -->
    <div class="card" style="padding: 24px;">
        <h3 class="card-title" style="margin-bottom: 18px;"><?= e(__a('title')) ?> & <?= e(__a('description')) ?></h3>

        <?php lang_bar(['scope' => '#slideLangFields']); ?>
        <div id="slideLangFields">
        <?php lb_panes(function ($lc, $l) use ($slide) { ?>
            <div class="form-group" style="margin-bottom:18px;">
                <label class="form-label" style="font-weight:600;" for="f_title_<?= e($lc) ?>"><?= e(__a('Başlık', 'Title')) ?><?= lb_tag($l) ?></label>
                <input type="text" name="title_<?= e($lc) ?>" id="f_title_<?= e($lc) ?>" class="form-control" value="<?= e($slide['title_' . $lc] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:6px;">
                <label class="form-label" style="font-weight:600;" for="f_desc_<?= e($lc) ?>"><?= e(__a('Açıklama', 'Description')) ?><?= lb_tag($l) ?></label>
                <textarea name="description_<?= e($lc) ?>" id="f_desc_<?= e($lc) ?>" class="form-control" rows="3"><?= e($slide['description_' . $lc] ?? '') ?></textarea>
            </div>
        <?php }); ?>
        </div>
    </div>
</form>

<script>
function onImageSelected(path) {
    if (!path) return;
    document.getElementById('f_image_path').value = path;
    var preview = document.getElementById('img_preview');
    if (preview) {
        preview.src = '../' + path.replace(/^\/+/, '');
    }
}

</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
