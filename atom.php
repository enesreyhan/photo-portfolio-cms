<?php
/**
 * atom.php
 * Dinamik Atom 1.0 Beslemesi
 */

header('Content-Type: application/atom+xml; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = DB::getConnection();
$stmt = $pdo->query("SELECT * FROM `items` WHERE `status` = 'published' AND `type` != 'page' AND COALESCE(`access_password`, '') = '' ORDER BY `date` DESC LIMIT 25");
$items = $stmt->fetchAll();

$site_title = get_setting('site_title_en', site_owner_name());
$site_desc = get_setting('site_desc_en', '');
$updated = !empty($items[0]['date']) ? date('c', strtotime($items[0]['date'])) : date('c');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title><?= e($site_title) ?></title>
    <subtitle><?= e($site_desc) ?></subtitle>
    <link href="<?= e(url('atom.xml')) ?>" rel="self" />
    <link href="<?= e(url('/')) ?>" />
    <updated><?= $updated ?></updated>
    <id><?= e(url('/')) ?></id>
    <author>
        <name><?= e(site_owner_name()) ?></name>
        <?php if (get_setting('contact_email') !== ''): ?><email><?= e(get_setting('contact_email')) ?></email><?php endif; ?>
    </author>

    <?php foreach ($items as $item): 
        $item_url = item_url($item, 'en');
        $item_date = !empty($item['date']) ? date('c', strtotime($item['date'])) : date('c');
    ?>
    <entry>
        <title><?= e($item['title_en']) ?></title>
        <link href="<?= e($item_url) ?>" />
        <id><?= e($item_url) ?></id>
        <updated><?= $item_date ?></updated>
        <summary><?= e($item['description_en']) ?></summary>
    </entry>
    <?php endforeach; ?>
</feed>
