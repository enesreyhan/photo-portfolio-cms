<?php
/**
 * includes/views/blog_index.php
 * Blog / Yazılar / Haberler Ana Sayfa Görünümü
 * 6 Şablon: editorial_grid, magazine_featured, minimal_journal, chronological_stream, split_showcase, bento_news
 */

$is_tr = ($lang ?? 'en') === 'tr';
$page_title = get_page_custom_title('blog', $lang, 'Blog');

$blog_template        = get_setting('blog_template', 'editorial_grid');
$blog_surface         = get_setting('blog_default_surface', 'dark');
$blog_grid_cols       = max(2, min(3, (int)get_setting('blog_grid_cols', '3')));
$blog_card_aspect     = get_setting('blog_card_aspect', '16/10');
$blog_show_cover      = (get_setting('blog_show_cover', '1') === '1');
$blog_show_date       = (get_setting('blog_show_date', '1') === '1');
$blog_show_reading    = (get_setting('blog_show_reading_time', '1') === '1');
$blog_show_location   = (get_setting('blog_show_location', '1') === '1');
$blog_show_excerpt    = (get_setting('blog_show_excerpt', '1') === '1');

$blog_kicker   = get_setting('blog_kicker_' . $lang, site_t('SAHA NOTLARI & EDİTORYAL YAZILAR', 'FIELD NOTES & EDITORIAL ESSAYS'));
$blog_subtitle = get_setting('blog_subtitle_' . $lang, site_t('Belgesel fotoğrafçılık, görsel hikaye anlatıcılığı, saha günceleri ve güncel duyurular.', 'Essays on documentary photography, visual storytelling, field journals, and news.'));

$page_desc = $blog_subtitle;

if ($blog_surface === 'white' || $blog_surface === 'paper' || get_setting('hp_default_surface') === 'white') {
    $html_class = 'blog-page aboutme-page editorial-page about-surface-white gallery-white light';
} else {
    $html_class = 'dark blog-page aboutme-page editorial-page about-surface-dark';
}

if (!isset($site_title)) {
    $site_title = get_setting('site_title_' . $lang, get_setting('site_title_en', site_owner_name()));
}

// Tahmini okuma süresi hesaplayıcı
if (!function_exists('estimate_blog_reading_time')) {
    function estimate_blog_reading_time(array $post, string $lang): string {
        $raw = (string)item_field($post, 'content', $lang) . ' ' . (string)item_field($post, 'description', $lang);
        $clean = strip_tags($raw);
        $words = max(1, str_word_count($clean));
        $mins = max(1, (int)ceil($words / 180));
        return $mins . ' ' . ui_text('minutes_read', $lang, 'min read', 'dk okuma');
    }
}

require __DIR__ . '/layout_header.php';
?>
<body class="bg-stone-50 text-stone-800 dark:bg-stone-950 dark:text-stone-200 transition-colors duration-300 ease-in-out">

<?php render_site_navigation($lang, 'blog', $url_tr ?? null, $url_en ?? null); ?>

<div class="left-content"></div>

<div class="center-content">
    <main class="main blog-index-main blog-tpl-<?= e($blog_template) ?> blog-cols-<?= (int)$blog_grid_cols ?>">
        <div class="blog-index-container">

            <!-- ÜST BAŞLIK BLOĞU -->
            <header class="blog-index-hero">
                <?php if (!empty($blog_kicker)): ?>
                    <span class="blog-index-kicker"><?= e($blog_kicker) ?></span>
                <?php endif; ?>
                <h1 class="blog-index-title"><?= e($page_title) ?></h1>
                <?php if (!empty($blog_subtitle)): ?>
                    <p class="blog-index-subtitle"><?= e($blog_subtitle) ?></p>
                <?php endif; ?>
            </header>

            <?php if (empty($blog_items)): ?>
                <div class="blog-empty-state">
                    <p><?= site_t('Henüz yayınlanmış bir yazı bulunmuyor.', 'No articles have been published yet.') ?></p>
                </div>
            <?php else: ?>

                <?php
                $render_items = $blog_items;
                $featured_item = null;
                if ($blog_template === 'magazine_featured' && count($render_items) > 0) {
                    $featured_item = array_shift($render_items);
                }
                ?>

                <?php if ($featured_item):
                    $f_title = item_field($featured_item, 'title', $lang);
                    $f_desc  = item_field($featured_item, 'description', $lang);
                    $f_loc   = item_field($featured_item, 'location', $lang);
                    $f_url   = item_url($featured_item, $lang);
                    $f_img   = !empty($featured_item['cover_image']) ? get_thumbnail_url($featured_item['cover_image'], 1600) : '';
                    $f_date  = !empty($featured_item['date']) ? substr($featured_item['date'], 0, 10) : ($featured_item['year'] ?? '');
                    $f_read  = estimate_blog_reading_time($featured_item, $lang);
                ?>
                <!-- DERGİ MANŞET ÖNE ÇIKAN YAZI -->
                <article class="blog-featured-hero">
                    <?php if ($blog_show_cover && $f_img !== ''): ?>
                    <a href="<?= e($f_url) ?>" class="blog-featured-media">
                        <img src="<?= e($f_img) ?>" alt="<?= e($f_title) ?>" loading="eager">
                    </a>
                    <?php endif; ?>
                    <div class="blog-featured-body">
                        <div class="blog-card-meta">
                            <span class="blog-featured-badge"><?= site_t('ÖNE ÇIKAN YAZI', 'FEATURED ARTICLE') ?></span>
                            <?php if ($blog_show_date && $f_date !== ''): ?>
                                <span><?= e($f_date) ?></span>
                            <?php endif; ?>
                            <?php if ($blog_show_location && $f_loc !== ''): ?>
                                <span>&bull; <?= e($f_loc) ?></span>
                            <?php endif; ?>
                            <?php if ($blog_show_reading): ?>
                                <span>&bull; <?= e($f_read) ?></span>
                            <?php endif; ?>
                        </div>
                        <h2 class="blog-featured-title">
                            <a href="<?= e($f_url) ?>"><?= e($f_title) ?></a>
                        </h2>
                        <?php if ($blog_show_excerpt && $f_desc !== ''): ?>
                            <p class="blog-featured-excerpt"><?= e($f_desc) ?></p>
                        <?php endif; ?>
                        <a href="<?= e($f_url) ?>" class="blog-read-link">
                            <span><?= site_t('Yazıyı Oku', 'Read Article') ?></span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </article>
                <?php endif; ?>

                <?php if (!empty($render_items)): ?>
                <section class="blog-posts-collection" style="--blog-aspect: <?= e($blog_card_aspect) ?>;">
                    <?php foreach ($render_items as $idx => $post):
                        $p_title = item_field($post, 'title', $lang);
                        $p_desc  = item_field($post, 'description', $lang);
                        $p_loc   = item_field($post, 'location', $lang);
                        $p_url   = item_url($post, $lang);
                        $p_img   = !empty($post['cover_image']) ? get_thumbnail_url($post['cover_image'], 960) : '';
                        $p_date  = !empty($post['date']) ? substr($post['date'], 0, 10) : ($post['year'] ?? '');
                        $p_read  = estimate_blog_reading_time($post, $lang);
                    ?>
                    <article class="blog-post-card <?= ($blog_template === 'bento_news' && $idx === 0) ? 'bento-wide' : '' ?>">
                        <?php if ($blog_show_cover && $p_img !== '' && $blog_template !== 'minimal_journal'): ?>
                            <a href="<?= e($p_url) ?>" class="blog-card-media">
                                <img src="<?= e($p_img) ?>" alt="<?= e($p_title) ?>" loading="lazy">
                            </a>
                        <?php endif; ?>

                        <div class="blog-card-content">
                            <div class="blog-card-meta">
                                <?php if ($blog_show_date && $p_date !== ''): ?>
                                    <span><?= e($p_date) ?></span>
                                <?php endif; ?>
                                <?php if ($blog_show_location && $p_loc !== ''): ?>
                                    <span><?= ($blog_show_date && $p_date !== '') ? '&bull; ' : '' ?><?= e($p_loc) ?></span>
                                <?php endif; ?>
                                <?php if ($blog_show_reading): ?>
                                    <span><?= (($blog_show_date && $p_date !== '') || ($blog_show_location && $p_loc !== '')) ? '&bull; ' : '' ?><?= e($p_read) ?></span>
                                <?php endif; ?>
                            </div>

                            <h2 class="blog-card-title">
                                <a href="<?= e($p_url) ?>"><?= e($p_title) ?></a>
                            </h2>

                            <?php if ($blog_show_excerpt && $p_desc !== ''): ?>
                                <p class="blog-card-excerpt"><?= e($p_desc) ?></p>
                            <?php endif; ?>

                            <a href="<?= e($p_url) ?>" class="blog-read-link">
                                <span><?= site_t('Devamını Oku', 'Read More') ?></span>
                                <span>&rarr;</span>
                            </a>
                        </div>

                        <?php if ($blog_show_cover && $p_img !== '' && $blog_template === 'minimal_journal'): ?>
                            <a href="<?= e($p_url) ?>" class="blog-journal-thumb">
                                <img src="<?= e($p_img) ?>" alt="<?= e($p_title) ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>

    <?php
    $render_closing_html = false;
    require __DIR__ . '/layout_footer.php';
    ?>
</div>

<div class="right-content"></div>
</body>
</html>
