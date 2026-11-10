<?php
/**
 * sitemap.php
 * Dinamik XML Site Haritası (Sitemap) — Google Görsel Şeması (Image Sitemap) & Çok Dilli Alternatifler (E11)
 */

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = DB::getConnection();
$active_langs = get_supported_languages();

// Yalnızca yayındaki ve gizli olmayan öğeler (unlisted ve parola korumalılar hariç)
$stmt = $pdo->query("SELECT * FROM `items` WHERE `status` = 'published' AND COALESCE(`access_password`, '') = '' AND NOT (`type` = 'page' AND `slug` IN ('aboutme', '404')) ORDER BY `weight` ASC, `date` DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <!-- Ana Sayfalar (Aktif Diller) -->
    <?php foreach ($active_langs as $lc): ?>
    <url>
        <loc><?= e(url($lc . '/')) ?></loc>
        <?php foreach ($active_langs as $alt_lc): ?>
        <xhtml:link rel="alternate" hreflang="<?= e($alt_lc) ?>" href="<?= e(url($alt_lc . '/')) ?>" />
        <?php endforeach; ?>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <?php endforeach; ?>

    <!-- Bölüm İndeksleri (Kategoriler) -->
    <?php
    $cat_rows = [];
    try {
        $cat_rows = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
    foreach ($cat_rows as $cr):
        foreach ($active_langs as $lc):
            $c_slug = !empty($cr['slug_' . $lc]) ? $cr['slug_' . $lc] : (($lc === 'tr') ? ($cr['slug_tr'] ?: $cr['slug_en']) : ($cr['slug_en'] ?: $cr['slug_tr']));
            if (!empty($c_slug)):
    ?>
    <url>
        <loc><?= e(url($lc . '/' . $c_slug . '/')) ?></loc>
        <?php foreach ($active_langs as $alt_lc):
            $alt_slug = !empty($cr['slug_' . $alt_lc]) ? $cr['slug_' . $alt_lc] : (($alt_lc === 'tr') ? ($cr['slug_tr'] ?: $cr['slug_en']) : ($cr['slug_en'] ?: $cr['slug_tr']));
            if (!empty($alt_slug)):
        ?>
        <xhtml:link rel="alternate" hreflang="<?= e($alt_lc) ?>" href="<?= e(url($alt_lc . '/' . $alt_slug . '/')) ?>" />
        <?php endif; endforeach; ?>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php
            endif;
        endforeach;
    endforeach;
    ?>

    <!-- Statik Sayfalar -->
    <?php foreach ($active_langs as $lc): ?>
    <url>
        <loc><?= e(url($lc . '/' . get_static_slug('about', $lc) . '/')) ?></loc>
        <?php foreach ($active_langs as $alt_lc): ?>
        <xhtml:link rel="alternate" hreflang="<?= e($alt_lc) ?>" href="<?= e(url($alt_lc . '/' . get_static_slug('about', $alt_lc) . '/')) ?>" />
        <?php endforeach; ?>
        <priority>0.7</priority>
    </url>
    <?php if (field_map_enabled()): ?>
    <url>
        <loc><?= e(url($lc . '/' . get_static_slug('field_work', $lc) . '/')) ?></loc>
        <?php foreach ($active_langs as $alt_lc): ?>
        <xhtml:link rel="alternate" hreflang="<?= e($alt_lc) ?>" href="<?= e(url($alt_lc . '/' . get_static_slug('field_work', $alt_lc) . '/')) ?>" />
        <?php endforeach; ?>
        <priority>0.7</priority>
    </url>
    <?php endif; ?>
    <?php if (get_setting('legal_pages_enabled', '1') == '1'): ?>
    <url>
        <loc><?= e(url($lc . '/' . get_static_slug('privacy_notice', $lc) . '/')) ?></loc>
        <?php foreach ($active_langs as $alt_lc): ?>
        <xhtml:link rel="alternate" hreflang="<?= e($alt_lc) ?>" href="<?= e(url($alt_lc . '/' . get_static_slug('privacy_notice', $alt_lc) . '/')) ?>" />
        <?php endforeach; ?>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= e(url($lc . '/' . get_static_slug('explicit_consent', $lc) . '/')) ?></loc>
        <?php foreach ($active_langs as $alt_lc): ?>
        <xhtml:link rel="alternate" hreflang="<?= e($alt_lc) ?>" href="<?= e(url($alt_lc . '/' . get_static_slug('explicit_consent', $alt_lc) . '/')) ?>" />
        <?php endforeach; ?>
        <priority>0.3</priority>
    </url>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- Tüm Yayınlanan Çalışmalar & Google Görseller (Image Sitemap) -->
    <?php foreach ($items as $item): 
        $type = $item['type'];
        $lastmod = !empty($item['updated_at']) ? date('Y-m-d', strtotime($item['updated_at'])) : (!empty($item['date']) ? date('Y-m-d', strtotime($item['date'])) : date('Y-m-d'));

        foreach ($active_langs as $lc):
            $loc_url = item_url($item, $lc);
            $item_title = item_field($item, 'title', $lc);
            $item_desc = item_field($item, 'description', $lc);

            // Görselleri derle (Kapak + blok fotoğrafları)
            $images = [];
            if (!empty($item['cover_image'])) {
                $images[] = [
                    'loc'     => get_cover_image_url($item['cover_image']),
                    'title'   => $item_title,
                    'caption' => $item_desc ?: $item_title
                ];
            }

            $raw_json = $item['content_' . $lc] ?? ($item['content_tr'] ?? '');
            if (!empty($raw_json) && str_starts_with(trim($raw_json), '{')) {
                $parsed = json_decode($raw_json, true);
                if (!empty($parsed['blocks']) && is_array($parsed['blocks'])) {
                    foreach ($parsed['blocks'] as $b) {
                        if (($b['type'] ?? '') === 'photo' && !empty($b['path'])) {
                            $b_img_url = get_cover_image_url($b['path']);
                            $b_alt = !empty($b['alt']) ? $b['alt'] : (!empty($b['alt_' . $lc]) ? $b['alt_' . $lc] : $item_title);
                            $images[] = [
                                'loc'     => $b_img_url,
                                'title'   => $b_alt ?: $item_title,
                                'caption' => $b_alt ?: $item_title
                            ];
                        }
                    }
                }
            }
    ?>
    <url>
        <loc><?= e($loc_url) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <?php foreach ($active_langs as $alt_lc): ?>
        <xhtml:link rel="alternate" hreflang="<?= e($alt_lc) ?>" href="<?= e(item_url($item, $alt_lc)) ?>" />
        <?php endforeach; ?>
        <?php foreach ($images as $img): ?>
        <image:image>
            <image:loc><?= e($img['loc']) ?></image:loc>
            <?php if (!empty($img['title'])): ?>
            <image:title><?= e($img['title']) ?></image:title>
            <?php endif; ?>
            <?php if (!empty($img['caption'])): ?>
            <image:caption><?= e($img['caption']) ?></image:caption>
            <?php endif; ?>
        </image:image>
        <?php endforeach; ?>
        <priority>0.9</priority>
    </url>
    <?php
        endforeach;
    endforeach; ?>
</urlset>
