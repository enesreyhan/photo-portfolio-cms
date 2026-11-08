<?php
/**
 * admin/includes/editor_bar.php
 * Bütün düzenleyicilerin (çalışma, blog yazısı, Hakkımda) ortak üst çubuğu ve başlığı.
 * Üst çubuk: geri · durum rozeti | [ek düğmeler] · Sitede gör · Kaydet
 * Stiller: assets/admin.css (.item-action-bar …)
 *
 * @param array $o back_href, back_label, status ('published'|'draft'|'unlisted'|null), form (Kaydet'in gönderdiği form id),
 *                 preview (sitede gör adresi, isteğe bağlı), extra (ek düğmelerin HTML'i), title, sub
 */
function editor_bar(array $o): void {
    $status = $o['status'] ?? null;
    $labels = ['published' => __a('Yayında', 'Published'), 'draft' => __a('Taslak', 'Draft'), 'unlisted' => __a('Gizli bağlantı', 'Unlisted')];
    $status_label = is_string($status) ? ($labels[$status] ?? null) : null;
    ?>
<div class="item-action-bar">
    <div class="item-action-bar-left">
        <a href="<?= e($o['back_href']) ?>" class="action-bar-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            <span><?= e($o['back_label']) ?></span>
        </a>
        <?php if ($status_label): ?>
        <div class="action-bar-divider"></div>
        <span class="status-indicator-pill <?= $status === 'published' ? 'published' : 'draft' ?>"><span class="status-dot"></span><span><?= e($status_label) ?></span></span>
        <?php endif; ?>
    </div>
    <div class="item-action-bar-right">
        <?= $o['extra'] ?? '' ?>
        <?php if (!empty($o['preview'])): ?>
        <a href="<?= e($o['preview']) ?>" target="_blank" rel="noopener" class="btn btn-secondary"><?= e(__a('Sitede gör', 'View on site')) ?> ↗</a>
        <?php endif; ?>
        <button type="submit" form="<?= e($o['form']) ?>" class="btn btn-primary" title="<?= e(__a('Kaydet (Ctrl+S)', 'Save (Ctrl+S)')) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span><?= e(__a('Kaydet', 'Save')) ?></span>
        </button>
    </div>
</div>
<div class="page-header-wrap editor-head">
    <div>
        <h1 class="page-header-title"><?= e($o['title'] ?? '') ?></h1>
        <?php if (!empty($o['sub'])): ?><p class="page-header-sub"><?= e($o['sub']) ?></p><?php endif; ?>
    </div>
</div>
    <?php
}
