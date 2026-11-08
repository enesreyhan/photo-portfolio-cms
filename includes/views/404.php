<?php
/**
 * includes/views/404.php
 * 404 Sayfa Bulunamadı
 */

http_response_code(404);

$is_tr = ($lang ?? 'en') === 'tr';
// Panelden düzenlenen 404 sayfası (Sayfalar › 404 sayfası; type 'page', slug '404'); yoksa/taslaksa varsayılan metin
$nf_item = null;
try {
    $nf_item = DB::getConnection()->query("SELECT * FROM `items` WHERE `type` = 'page' AND `slug` = '404' AND `status` = 'published' LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (\Throwable $e) {}
$nf_title = ($nf_item ? item_field($nf_item, 'title', $lang ?? 'en') : '') ?: site_t('Kadraj Dışı', 'Out of Frame');
$nf_body  = ($nf_item ? (string)item_field($nf_item, 'content', $lang ?? 'en') : '') ?: not_found_default_html($lang ?? 'en');
if (!preg_match('/<(p|h[1-6]|div|ul|ol|figure|img|blockquote|iframe|br)\b/i', $nf_body)) $nf_body = render_markdown($nf_body);
$page_title = $nf_title;
$html_class = 'dark not-found-page editorial-page';

require __DIR__ . '/layout_header.php';
?>
<?php render_site_navigation($lang, $en_path, $tr_path, $instagram_url); ?>

<div class="left-content"></div>

<div class="content">

    <main class="not-found">
        <div class="not-found-inner">
            <p class="not-found-code">404</p>
            <h1 class="not-found-title"><?= e($nf_title) ?></h1>
            <div class="not-found-text"><?= $nf_body ?></div>
            <a class="not-found-btn" href="<?= e(url(($is_tr ? 'tr/' : ''))) ?>">
                <?= site_t('Ana sayfaya dön', 'Go back home') ?>
            </a>
        </div>
    </main>

    <?php require __DIR__ . '/layout_footer.php'; ?>
</div>

<div class="right-content"></div>
</body>
</html>
