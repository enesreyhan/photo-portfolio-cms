<?php
/**
 * rss.php
 * Dinamik RSS 2.0 Beslemesi
 */

header('Content-Type: application/rss+xml; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = DB::getConnection();
$stmt = $pdo->query("SELECT * FROM `items` WHERE `status` = 'published' AND `type` != 'page' AND COALESCE(`access_password`, '') = '' ORDER BY `date` DESC LIMIT 25");
$items = $stmt->fetchAll();

$site_title = get_setting('site_title_en', site_owner_name());
$site_desc = get_setting('site_desc_en', '');
$build_date = date('r');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?= e($site_title) ?></title>
        <link><?= e(url('/')) ?></link>
        <description><?= e($site_desc) ?></description>
        <language>en</language>
        <lastBuildDate><?= $build_date ?></lastBuildDate>
        <atom:link href="<?= e(url('rss.xml')) ?>" rel="self" type="application/rss+xml" />

        <?php foreach ($items as $item): 
            $item_url = item_url($item, 'en');
            $pub_date = !empty($item['date']) ? date('r', strtotime($item['date'])) : date('r');
        ?>
        <item>
            <title><?= e($item['title_en']) ?></title>
            <link><?= e($item_url) ?></link>
            <guid isPermaLink="true"><?= e($item_url) ?></guid>
            <pubDate><?= $pub_date ?></pubDate>
            <description><?= e($item['description_en']) ?></description>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>
