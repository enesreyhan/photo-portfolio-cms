<?php
/**
 * includes/views/legal.php
 * KVKK Aydınlatma Metni Sayfa Görünümü
 */

$is_tr = ($lang ?? 'en') === 'tr';
$legal_type = $legal_type ?? 'aydinlatma';

$page_title = site_t('Aydınlatma Metni', 'Privacy Notice');
$page_desc = str_replace('{owner}', site_owner_name(), site_t('6698 sayılı KVKK uyarınca veri sorumlusu {owner} tarafından hazırlanan kişisel verilerin korunması ve işlenmesi aydınlatma metni.', 'Clarification notice by data controller {owner} pursuant to Law No. 6698 (KVKK).'));
$raw_content = get_legal_content('aydinlatma', $lang);
$en_path = 'privacy-notice';
$tr_path = 'tr/aydinlatma-metni';

$html_class = 'dark aboutme-page editorial-page legal-view-page';
require __DIR__ . '/layout_header.php';
?>

<?php render_site_navigation($lang, $en_path, $tr_path, $instagram_url); ?>

<div class="left-content"></div>

<div class="content">
    <main>
        <article class="legal-article">
            <!-- Metin Başlığı ve Künye -->
            <div class="legal-header-meta">
                <span class="legal-badge">
                    <?= site_t('6698 Sayılı KVKK Kapsamında', 'Pursuant to Law No. 6698 (KVKK)') ?>
                </span>
                <h1 class="page-header legal-title"><?= e($page_title) ?></h1>
                <div class="legal-sub-info">
                    <span><?= e(str_replace('{owner}', site_owner_name(), site_t('Veri Sorumlusu: {owner}', 'Data Controller: {owner}'))) ?></span>
                    <span class="legal-dot">·</span>
                    <span><?= site_t('Son Güncelleme', 'Last Updated') ?>: <?= date('d.m.Y') ?></span>
                </div>
            </div>

            <!-- İçerik Gövdesi (Zengin HTML veya Markdown Çözümleme) -->
            <section class="body legal-body">
                <?php 
                $has_html = (strpos($raw_content, '<p') !== false || strpos($raw_content, '<h') !== false || strpos($raw_content, '<div') !== false || strpos($raw_content, '<ul') !== false);
                echo $has_html ? sanitize_html($raw_content) : render_markdown($raw_content);
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
