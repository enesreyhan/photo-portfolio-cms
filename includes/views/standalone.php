<?php
/**
 * includes/views/standalone.php
 * Bağımsız sayfa (type 'page'): WordPress sayfası gibi başlık, isteğe bağlı kapak görseli ve zengin metin.
 * Proje şablonu (page.php) kullanılmaz; tipografi hukuki metin sayfasıyla ortaktır (custom.css › .legal-*).
 */

$page_title  = item_field($item, 'title', $lang);
$page_desc   = item_field($item, 'description', $lang);
$raw_content = (string)item_field($item, 'content', $lang);
$og_image    = !empty($item['cover_image']) ? url($item['cover_image']) : '';

$html_class = 'dark aboutme-page editorial-page legal-view-page standalone-page';
require __DIR__ . '/layout_header.php';
?>

<?php render_site_navigation($lang, $en_path, $tr_path, $instagram_url); ?>

<div class="left-content"></div>

<div class="content">
    <main>
        <article class="legal-article standalone-article">
            <h1 class="page-header legal-title"><?= e($page_title) ?></h1>
            <?php
            // Blog yazısı: tarih ve yer, blog listesindeki “Tarih” / “Yer” ayarlarına uyar
            $meta = [];
            if (($item['type'] ?? '') === 'blog') {
                if (get_setting('blog_show_date', '1') === '1' && !empty($item['date'])) $meta[] = substr($item['date'], 0, 10);
                $loc = (string)item_field($item, 'location', $lang);
                if (get_setting('blog_show_location', '1') === '1' && $loc !== '') $meta[] = $loc;
            }
            if ($meta): ?>
                <p class="standalone-meta"><?= implode(' &bull; ', array_map('e', $meta)) ?></p>
            <?php endif; ?>
            <?php if (!empty($item['cover_image'])): ?>
                <figure class="standalone-cover"><?= get_responsive_image_html($item['cover_image'], $page_title, '', true, $item['cover_focal'] ?? '50% 50%') ?></figure>
            <?php endif; ?>
            <section class="body legal-body standalone-body">
                <?php
                // Editör HTML kaydeder; eski/elle yazılmış düz metin Markdown olarak çözülür
                echo preg_match('/<(p|h[1-6]|div|ul|ol|figure|img|blockquote|iframe)\b/i', $raw_content) ? sanitize_html($raw_content) : render_markdown($raw_content);
                ?>
            </section>
        </article>
    </main>

    <?php
    $render_closing_html = false;
    require __DIR__ . '/layout_footer.php';
    ?>
</div>

<div class="right-content"></div>
</body>
</html>
