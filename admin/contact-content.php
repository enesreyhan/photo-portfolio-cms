<?php
/**
 * admin/contact-content.php
 * İletişim sayfasının metinleri (her dil için). Düzen ve görünüm: templates.php?design=contact
 * Not: contact-page.php tarayıcılarda 301 ile önbelleğe alındığı için ayrı dosya.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$cc_bases = ['contact_page_kicker', 'contact_page_heading', 'contact_page_desc', 'contact_page_location', 'contact_page_note'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', __a('Güvenlik doğrulaması başarısız (CSRF). Sayfayı yenileyip tekrar deneyin.', 'Security check failed (CSRF). Reload the page and try again.'));
        header('Location: contact-content.php');
        exit;
    }
    save_settings_l($cc_bases, $_POST);
    if (isset($_POST['contact_email'])) set_setting('contact_email', trim($_POST['contact_email']));
    set_flash('success', __a('İletişim sayfası metinleri kaydedildi.', 'Contact page texts saved.'));
    header('Location: contact-content.php');
    exit;
}

// Varsayılanlar includes/views/contact.php ile aynı olmalı
$cc_defaults = [
    'contact_page_kicker'   => ['EDİTORYAL İŞ BİRLİĞİ & TEMSİL', 'EDITORIAL INQUIRIES & COMMISSIONS'],
    'contact_page_heading'  => ['Saha Görevleri, Sergi & İletişim', 'Assignments, Exhibitions & Contact'],
    'contact_page_desc'     => ['Ulusal ve uluslararası editoryal saha görevlerine, uzun soluklu belgesel araştırmalara, baskı/lisanslama taleplerine ve yayın projelerine açıktır.', 'Available for national and international editorial assignments, long-term documentary research, print/licensing inquiries, and publishing projects.'],
    'contact_page_location' => ['', ''],
    'contact_page_note'     => ['Mesajlarınıza genellikle 24–48 saat içerisinde dönüş yapılmaktadır.', 'Inquiries are typically answered within 24–48 hours.'],
];
$cc_labels = [
    'contact_page_kicker'   => __a('Üst etiket', 'Kicker'),
    'contact_page_heading'  => __a('Başlık', 'Heading'),
    'contact_page_desc'     => __a('Açıklama', 'Description'),
    'contact_page_location' => __a('Konum', 'Location'),
    'contact_page_note'     => __a('Yanıt notu', 'Response note'),
];

$admin_title     = __a('İletişim Sayfası', 'Contact Page');
$breadcrumb_page = __a('İletişim Sayfası', 'Contact Page');

require __DIR__ . '/includes/header.php';

$cc_langs = lang_bar_languages();
$cc_init  = lang_bar_initial();
$cc_preview = [];
foreach ($cc_langs as $l) { $cc_preview[$l['code']] = url($l['code'] . '/' . get_static_slug('contact', $l['code']) . '/'); }
?>

<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('İletişim Sayfası', 'Contact Page')) ?></h1>
        <p class="page-header-sub"><?= e(__a('Ziyaretçinin iletişim sayfasında okuduğu metinler. Düzen ve görünüm Tasarım bölümünde.', 'The texts visitors read on the contact page. Layout and look are in Design.')) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="templates.php?design=contact" class="btn btn-secondary"><?= e(__a('Tasarımı düzenle', 'Edit design')) ?></a>
        <button type="submit" form="contactContentForm" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?= e(__a('Kaydet', 'Save')) ?></span>
        </button>
    </div>
</div>

<?php if (get_setting('contact_page_enabled', '0') !== '1'): ?>
<div class="alert alert-warning" style="margin-bottom:16px;"><?= e(__a('İletişim sayfası şu an kapalı; metinler kaydedilir ama sitede görünmez.', 'The contact page is currently off; texts are saved but not shown on the site.')) ?></div>
<?php endif; ?>

<form method="POST" action="contact-content.php" id="contactContentForm" class="save-form">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="card" style="margin-bottom:20px;">
        <div class="card-body">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="cc_email"><?= e(__a('E-posta', 'Email')) ?></label>
                <input type="email" id="cc_email" name="contact_email" class="form-control" value="<?= e(get_setting('contact_email', '')) ?>">
                <p class="form-hint"><?= e(__a('Sayfada gösterilir; formdan gelen mesajların bildirimi de bu adrese gider. Telefon Tasarım › İletişim\'de.', 'Shown on the page; form notifications also go here. Phone is in Design › Contact.')) ?></p>
            </div>
        </div>
    </div>

    <div class="card" id="contactLangCard">
        <?php lang_bar(['scope' => '#contactLangCard', 'panes' => ['.cc-lang-pane', '#cc_pane_{lang}'], 'preview' => $cc_preview]); ?>
        <div class="card-body">
            <?php foreach ($cc_langs as $l):
                $lc = $l['code'];
                $tr = ($lc === 'tr' || ($lc !== 'en' && $l['fallback'] === 'tr'));
            ?>
            <div class="cc-lang-pane" id="cc_pane_<?= e($lc) ?>" lang="<?= e($lc) ?>" dir="<?= e($l['dir']) ?>"<?= $lc === $cc_init ? '' : ' style="display:none;"' ?>>
                <?php foreach ($cc_bases as $b):
                    $k = $b . '_' . $lc;
                    $v = get_setting($k, $cc_defaults[$b][$tr ? 0 : 1]);
                ?>
                <div class="form-group">
                    <label class="form-label" for="<?= e($k) ?>"><?= e($cc_labels[$b]) ?></label>
                    <?php if ($b === 'contact_page_desc'): ?>
                    <textarea id="<?= e($k) ?>" name="<?= e($k) ?>" class="form-control" rows="4"><?= e($v) ?></textarea>
                    <?php else: ?>
                    <input type="text" id="<?= e($k) ?>" name="<?= e($k) ?>" class="form-control" value="<?= e($v) ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
