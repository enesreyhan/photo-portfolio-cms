<?php
/**
 * admin/blog.php
 * Blog / Yazılar Listesi & İçerik Yönetimi
 * Tüm ayarlar ve şablon seçimi Sistem Ayarları / Şablonlar altındaki templates.php?design=blog sayfasından yönetilir.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();
$site_languages = get_active_languages();

// Silme işlemi (POST delete=ID)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete']) && is_numeric($_POST['delete'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $del_id = (int)$_POST['delete'];
        $st = $pdo->prepare("DELETE FROM `items` WHERE `id` = ? AND `type` = 'blog'");
        $st->execute([$del_id]);
        set_flash('success', __a('Yazı silindi.', 'Article deleted.'));
    } else {
        set_flash('error', __a('CSRF doğrulaması başarısız.', 'CSRF verification failed.'));
    }
    header('Location: blog.php');
    exit;
}

$blog_enabled  = get_setting('blog_enabled', '0');
$slug_active   = get_setting('static_slug_blog_' . $admin_lang, ($admin_lang === 'tr' ? get_setting('static_slug_blog_tr', 'blog') : get_setting('static_slug_blog_en', 'blog')));
$custom_blog_title = get_setting('blog_title_' . $admin_lang);
if (empty($custom_blog_title)) {
    $custom_blog_title = ($admin_lang === 'tr') ? get_setting('blog_title_tr', 'Blog') : (get_setting('blog_title_en', '') ?: __a('menu_blog', 'Blog'));
}
$active_title  = $custom_blog_title ?: __a('menu_blog', 'Blog');

// Blog yazılarını çek
$stmt_posts = $pdo->query("SELECT * FROM `items` WHERE `type` = 'blog' ORDER BY `weight` ASC, `date` DESC, `id` DESC");
$blog_posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

$admin_title     = $active_title ?: __a('Blog / Yazılar', 'Blog / Articles');
$breadcrumb_root = __a('Sayfalar', 'Pages');
$breadcrumb_page = $active_title ?: __a('Blog / Yazılar', 'Blog / Articles');

require __DIR__ . '/includes/header.php';
?>

<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title">
            <?= e($active_title) ?>
            <span style="font-size:13px; font-weight:500; color:var(--text-muted); margin-left:6px;">(<?= count($blog_posts) ?> <?= __a('Yazı', 'Articles') ?>)</span>
        </h1>
        <p class="page-header-sub"><?= __a('Blog yazılarınızı, saha notlarınızı ve haberlerinizi bu ekrandan yönetebilir, yeni yazı ekleyebilirsiniz.', 'Manage your blog posts, field journals, and news articles.') ?></p>
    </div>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <?php if ($blog_enabled === '1'): ?>
            <a href="<?= url($admin_lang . '/' . trim($slug_active, '/') . '/') ?>" target="_blank" class="btn btn-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                <span><?= __a('Canlı Sayfayı Gör', 'View Live Page') ?></span>
            </a>
        <?php endif; ?>
        <a href="<?= url('admin/blog-edit.php') ?>" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span><?= __a('Yeni Yazı Ekle', 'Add New Article') ?></span>
        </a>
    </div>
</div>

<!-- ── BLOG YAZILARI LİSTESİ ── -->
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h3 class="card-title"><?= __a('Yazılar & Makaleler', 'Articles & Posts') ?> (<?= count($blog_posts) ?>)</h3>
        <a href="<?= url('admin/blog-edit.php') ?>" class="btn btn-primary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span><?= __a('Yeni Yazı Ekle', 'Add New Article') ?></span>
        </a>
    </div>

    <?php if (empty($blog_posts)): ?>
        <div class="card-body" style="text-align:center; padding:48px 20px;">
            <div style="width:48px; height:48px; border-radius:10px; background:var(--bg-body); border:1px solid var(--border-color); display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px; color:var(--text-muted);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <h4 style="margin:0 0 6px; font-size:16px; font-weight:700;"><?= __a('Henüz yazı eklenmemiş', 'No articles added yet') ?></h4>
            <p style="margin:0 0 18px; font-size:13px; color:var(--text-muted); max-width:440px; margin-left:auto; margin-right:auto;">
                <?= __a('İlk blog yazınızı, haberinizi veya saha günlüğünüzü görsel hikaye editörüyle hemen oluşturabilirsiniz.', 'Create your first blog post, news item, or field journal entry using the visual story editor.') ?>
            </p>
            <a href="<?= url('admin/blog-edit.php') ?>" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span><?= __a('İlk Yazıyı Oluştur', 'Create First Article') ?></span>
            </a>
        </div>
    <?php else: ?>
        <div style="display:flex; justify-content:flex-end; padding:10px 16px 0;"><?php lang_missing_filter('tr.blog-row'); ?></div>
        <div class="table-responsive">
            <table class="admin-table" data-m="blog">
                <thead>
                    <tr>
                        <th style="width:72px; padding-left:20px;"><?= __a('Kapak', 'Cover') ?></th>
                        <th><?= __a('Başlık & Özet', 'Title & Excerpt') ?></th>
                        <th style="width:130px;"><?= __a('Tarih', 'Date') ?></th>
                        <th style="width:110px;"><?= __a('Durum', 'Status') ?></th>
                        <th style="width:190px; text-align:right; padding-right:20px;"><?= __a('İşlemler', 'Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blog_posts as $post):
                        $p_title = item_field($post, 'title', $admin_lang) ?: ($admin_lang === 'tr' ? ($post['title_tr'] ?: $post['title_en']) : ($post['title_en'] ?: $post['title_tr']));
                        $p_desc  = item_field($post, 'description', $admin_lang) ?: ($admin_lang === 'tr' ? ($post['description_tr'] ?: $post['description_en']) : ($post['description_en'] ?: $post['description_tr']));
                        $thumb   = !empty($post['cover_image']) ? get_thumbnail_url($post['cover_image'], 320) : '';
                    ?>
                    <tr class="blog-row" data-missing="<?= e(implode(' ', lang_missing($post, ['title', 'description', 'content']))) ?>">
                        <td style="padding-left:20px;">
                            <?php if ($thumb !== ''): ?>
                                <img src="<?= e($thumb) ?>" alt="" style="width:56px; height:40px; object-fit:cover; border-radius:6px; border:1px solid var(--border-color);">
                            <?php else: ?>
                                <div style="width:56px; height:40px; border-radius:6px; background:var(--bg-body); border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:12px;">—</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= url('admin/blog-edit.php?id=' . (int)$post['id']) ?>" style="font-weight:700; color:var(--text-main); text-decoration:none; font-size:14px;">
                                <?= e($p_title ?: __a('(Başlıksız Yazı)', '(Untitled Post)')) ?>
                            </a>
                            <?= lang_status_chips($post, ['title', 'description', 'content']) ?>
                            <?php if ($p_desc !== ''): ?>
                                <div style="font-size:12px; color:var(--text-muted); margin-top:2px; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;">
                                    <?= e($p_desc) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-family:var(--font-mono); font-size:12px; color:var(--text-muted);">
                            <?= e(!empty($post['date']) ? substr($post['date'], 0, 10) : ($post['year'] ?: '—')) ?>
                        </td>
                        <td>
                            <?php if (($post['status'] ?? 'draft') === 'published'): ?>
                                <span class="badge badge-published"><?= __a('Yayında', 'Published') ?></span>
                            <?php else: ?>
                                <span class="badge badge-draft"><?= __a('Taslak', 'Draft') ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right; padding-right:20px;">
                            <div style="display:inline-flex; gap:6px;">
                                <a href="<?= url('admin/blog-edit.php?id=' . (int)$post['id']) ?>" class="btn btn-secondary btn-sm">
                                    <?= __a('Düzenle', 'Edit') ?>
                                </a>
                                <details class="row-menu">
                                    <summary class="btn btn-secondary btn-sm" aria-label="<?= e(__a('İşlemler', 'Actions')) ?>">⋯</summary>
                                    <div class="row-menu-list">
                                        <a href="<?= e(item_url($post, $admin_lang)) ?>" target="_blank"><?= __a('Sitede gör', 'View on site') ?> ↗</a>
                                        <a href="blog.php?delete=<?= (int)$post['id'] ?>" data-post data-confirm="<?= e(sprintf(__a('“%s” kalıcı olarak silinecek. Bu işlem geri alınamaz.', '“%s” will be deleted permanently. This cannot be undone.'), item_field($post, 'title', $admin_lang))) ?>" class="is-danger"><?= __a('Sil', 'Delete') ?></a>
                                    </div>
                                </details>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
