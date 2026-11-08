<?php
/**
 * includes/views/contact.php
 * Bağımsız İletişim Sayfası Görünümü
 * Düzenler: split_editorial, centered_minimal, dossier_grid, format_info_form, format_image_form, format_hero_form, format_quote_form
 */

$is_tr = ($lang ?? 'en') === 'tr';
$page_title = get_page_custom_title('contact', $lang, site_t('İletişim', 'Contact'));

$contact_layout   = get_setting('contact_page_layout', 'split_editorial');
$contact_surface  = get_setting('contact_page_surface', 'dark');
$contact_email    = get_setting('contact_email', '');
$contact_phone    = get_setting('contact_phone', '');
$show_socials     = (get_setting('contact_page_show_socials', '1') === '1');
$show_map_link    = field_map_enabled() && (get_setting('contact_page_show_map_link', '1') === '1');
$contact_image    = trim((string)get_setting('contact_page_image', ''));
if ($contact_image === '') { // görsel seçilmemişse Hakkımda portresi, o da yoksa nötr yer tutucu
    try { $contact_image = (string)DB::getConnection()->query("SELECT `cover_image` FROM `items` WHERE `slug` = 'aboutme' AND `type` = 'page' LIMIT 1")->fetchColumn(); } catch (\Throwable $e) {}
    $contact_image = ltrim($contact_image, '/') ?: 'assets/brand/portrait-placeholder.svg';
}

$kicker  = get_setting('contact_page_kicker_' . $lang, site_t('EDİTORYAL İŞ BİRLİĞİ & TEMSİL', 'EDITORIAL INQUIRIES & COMMISSIONS'));
$heading = get_setting('contact_page_heading_' . $lang, site_t('Saha Görevleri, Sergi & İletişim', 'Assignments, Exhibitions & Contact'));
$desc    = get_setting('contact_page_desc_' . $lang, site_t('Ulusal ve uluslararası editoryal saha görevlerine, uzun soluklu belgesel araştırmalara, baskı/lisanslama taleplerine ve yayın projelerine açıktır.', 'Available for national and international editorial assignments, long-term documentary research, print/licensing inquiries, and publishing projects.'));
$loc     = get_setting('contact_page_location_' . $lang, '');
if ($loc === '') { // ayrı konum girilmemişse Hakkımda sayfasındaki konum
    try { $loc = (string)(parse_about_content((string)DB::getConnection()->query("SELECT `content_" . preg_replace('/[^a-z]/', '', $lang) . "` FROM `items` WHERE `slug` = 'aboutme' AND `type` = 'page' LIMIT 1")->fetchColumn())['contact']['loc'] ?? ''); } catch (\Throwable $e) {}
}
$note    = get_setting('contact_page_note_' . $lang, site_t('Mesajlarınıza genellikle 24–48 saat içerisinde dönüş yapılmaktadır.', 'Inquiries are typically answered within 24–48 hours.'));

// Şablonu İletişim düzeni olan bağımsız sayfa: başlık, açıklama, metin ve kapak sayfanın kendisinden
$is_custom_page = !empty($item) && ($item['type'] ?? '') === 'page';
$page_body = '';
if ($is_custom_page) {
    $kicker  = '';
    $heading = item_field($item, 'title', $lang);
    $page_title = $heading;
    $desc    = item_field($item, 'description', $lang);
    $raw     = (string)item_field($item, 'content', $lang);
    $page_body = preg_match('/<(p|h[1-6]|div|ul|ol|figure|img|blockquote|iframe)\b/i', $raw) ? sanitize_html($raw) : render_markdown($raw);
    if (!empty($item['cover_image'])) $contact_image = $item['cover_image'];
}
$page_desc = $desc;

$social_links = [];
if ($show_socials) {
    $ig = get_setting('instagram_url', '');
    $tw = get_setting('x_url', '');
    $yt = get_setting('youtube_url', '');
    $fb = get_setting('facebook_url', '');
    if (!empty($ig)) $social_links['Instagram'] = $ig;
    if (!empty($tw)) $social_links['X'] = $tw;
    if (!empty($yt)) $social_links['YouTube'] = $yt;
    if (!empty($fb)) $social_links['Facebook'] = $fb;
}

$field_map_url = url($lang . '/' . get_static_slug('field_work', $lang) . '/');
$about_url     = url($lang . '/' . get_static_slug('about', $lang) . '/');

if ($contact_surface === 'white' || $contact_surface === 'paper' || get_setting('hp_default_surface') === 'white') {
    $html_class = 'contact-page aboutme-page editorial-page about-surface-white gallery-white light';
} else {
    $html_class = 'dark contact-page aboutme-page editorial-page about-surface-dark';
}

if (!isset($site_title)) {
    $site_title = get_setting('site_title_' . $lang, get_setting('site_title_en', site_owner_name()));
}

require __DIR__ . '/layout_header.php';
?>
<body class="bg-stone-50 text-stone-800 dark:bg-stone-950 dark:text-stone-200 transition-colors duration-300 ease-in-out">

<?php if ($is_custom_page) render_site_navigation($lang, $en_path, $tr_path, $instagram_url); else render_site_navigation($lang, 'contact', $url_tr ?? null, $url_en ?? null); ?>

<div class="left-content"></div>

<div class="center-content">
    <main class="main contact-page-main contact-layout-<?= e($contact_layout) ?>">
        <div class="contact-page-container">
            <?php if ($contact_layout === 'format_hero_form'): ?>
            <figure class="contact-page-figure"><img src="<?= e(url($contact_image)) ?>" alt="<?= e($heading ?: $page_title) ?>" loading="eager"></figure>
            <?php endif; ?>

            <!-- ÜST BAŞLIK BLOĞU -->
            <header class="contact-page-hero">
                <?php if (!empty($kicker)): ?>
                    <span class="contact-page-kicker"><?= e($kicker) ?></span>
                <?php endif; ?>
                <h1 class="contact-page-title"><?= e($heading ?: $page_title) ?></h1>
                <?php if (!empty($desc)): ?>
                    <p class="contact-page-lead"><?= e($desc) ?></p>
                <?php endif; ?>
                <?php if (trim(strip_tags($page_body, '<img>')) !== ''): ?>
                    <div class="contact-page-lead contact-page-text"><?= $page_body ?></div>
                <?php endif; ?>
            </header>
            <?php if ($contact_layout === 'format_quote_form' && !empty($note)): ?>
            <blockquote class="contact-page-quote"><?= e($note) ?></blockquote>
            <?php endif; ?>

            <?php if ($contact_layout === 'dossier_grid'): ?>
            <!-- BASIN & AJANS DOSYASI KARTLARI -->
            <section class="contact-dossier-cards">
                <div class="contact-dossier-card">
                    <span class="dossier-label"><?= site_t('DOĞRUDAN E-POSTA', 'DIRECT EMAIL') ?></span>
                    <a href="mailto:<?= e($contact_email) ?>" class="dossier-value dossier-link"><?= e($contact_email) ?></a>
                </div>
                <div class="contact-dossier-card">
                    <span class="dossier-label"><?= site_t('MERKEZ / KONUM', 'BASE / LOCATION') ?></span>
                    <span class="dossier-value"><?= e($loc) ?></span>
                </div>
                <?php if (!empty($contact_phone)): ?>
                <div class="contact-dossier-card">
                    <span class="dossier-label"><?= site_t('TELEFON / TEMSİL', 'PHONE / AGENCY') ?></span>
                    <span class="dossier-value"><?= e($contact_phone) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($show_map_link): ?>
                <div class="contact-dossier-card">
                    <span class="dossier-label"><?= site_t('SAHA HARİTASI', 'FIELD ARCHIVE') ?></span>
                    <a href="<?= e($field_map_url) ?>" class="dossier-value dossier-link"><?= e(get_page_custom_title('field_work', $lang, site_t('Saha Rotaları', 'Field Routes'))) ?> &rarr;</a>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <div class="contact-page-body">
                <?php if ($contact_layout === 'format_image_form'): ?>
                <figure class="contact-page-figure"><img src="<?= e(url($contact_image)) ?>" alt="<?= e($heading ?: $page_title) ?>" loading="lazy"></figure>
                <?php endif; ?>
                <?php if (in_array($contact_layout, ['split_editorial', 'format_info_form'], true)): ?>
                <!-- SOL SÜTUN: EDİTORYAL İLETİŞİM BİLGİLERİ -->
                <aside class="contact-page-sidebar">
                    <div class="contact-info-block">
                        <span class="contact-info-label"><?= site_t('E-POSTA', 'EMAIL') ?></span>
                        <a href="mailto:<?= e($contact_email) ?>" class="contact-info-email">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span><?= e($contact_email) ?></span>
                        </a>
                    </div>

                    <?php if (!empty($contact_phone)): ?>
                    <div class="contact-info-block">
                        <span class="contact-info-label"><?= site_t('TELEFON / TEMSİLCİLİK', 'PHONE / REPRESENTATION') ?></span>
                        <div class="contact-info-text"><?= e($contact_phone) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($loc)): ?>
                    <div class="contact-info-block">
                        <span class="contact-info-label"><?= site_t('KONUM', 'LOCATION') ?></span>
                        <div class="contact-info-text"><?= e($loc) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($social_links)): ?>
                    <div class="contact-info-block">
                        <span class="contact-info-label"><?= site_t('SOSYAL AĞLAR', 'SOCIAL NETWORKS') ?></span>
                        <div class="contact-social-pills">
                            <?php foreach ($social_links as $s_name => $s_url): ?>
                                <a href="<?= e($s_url) ?>" target="_blank" rel="noopener noreferrer" class="contact-social-pill">
                                    <span><?= e($s_name) ?></span>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="contact-info-links">
                        <a href="<?= e($about_url) ?>" class="contact-aux-link">
                            <span><?= e(get_page_custom_title('about', $lang, site_t('Biyografi & Sergi Kronolojisi', 'Biography & Exhibition Chronology'))) ?></span>
                            <span>&rarr;</span>
                        </a>
                        <?php if ($show_map_link): ?>
                        <a href="<?= e($field_map_url) ?>" class="contact-aux-link">
                            <span><?= e(get_page_custom_title('field_work', $lang, site_t('Saha Rotaları Haritası', 'Field Routes Map'))) ?></span>
                            <span>&rarr;</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </aside>
                <?php endif; ?>

                <!-- İLETİŞİM FORMU KARTI -->
                <section class="contact-page-form-card">
                    <div class="contact-form-header" style="margin-bottom:1.5rem;">
                        <span class="contact-form-kicker"><?= site_t('DOĞRUDAN MESAJ FORMU', 'DIRECT INQUIRY FORM') ?></span>
                        <h2 class="contact-form-title" style="margin-top:4px;"><?= site_t('Mesaj Gönderin', 'Send a Message') ?></h2>
                        <?php if (!empty($note)): ?>
                            <p class="contact-form-subtitle"><?= e($note) ?></p>
                        <?php endif; ?>
                    </div>

                    <div id="contactFormAlert" class="contact-form-alert" style="display: <?= !empty($_GET['msg_success']) || !empty($_GET['msg_error']) ? 'block' : 'none' ?>;">
                        <?php if (!empty($_GET['msg_success'])): ?>
                            <div class="alert-box alert-success">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <span><?= site_t('Mesajınız başarıyla iletildi. En kısa sürede sizinle iletişime geçeceğim.', 'Your message has been sent successfully. I will get back to you shortly.') ?></span>
                            </div>
                        <?php elseif (!empty($_GET['msg_error'])): ?>
                            <div class="alert-box alert-error">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= e($_GET['msg_error']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="<?= url('api/contact.php') ?>" method="POST" id="siteContactForm" class="site-contact-form">
                        <input type="hidden" name="lang" value="<?= e($lang) ?>">
                        <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                        <input type="hidden" name="_ft" value="<?= time() ?>">

                        <div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                            <input type="text" name="contact_company_hp" tabindex="-1" autocomplete="off">
                            <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="form-grid">
                            <div class="form-col">
                                <label class="form-field-label" for="contact_name">
                                    <?= site_t('Adınız Soyadınız', 'Your Name') ?> <span class="required-star">*</span>
                                </label>
                                <input type="text" id="contact_name" name="name" class="contact-input" required placeholder="<?= site_t('Adınız ve soyadınız', 'Your full name') ?>">
                            </div>

                            <div class="form-col">
                                <label class="form-field-label" for="contact_email">
                                    <?= site_t('E-posta Adresiniz', 'Your Email') ?> <span class="required-star">*</span>
                                </label>
                                <input type="email" id="contact_email" name="email" class="contact-input" required placeholder="<?= site_t('ornek@alanadi.com', 'you@example.com') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="form-field-label" for="contact_subject">
                                <?= site_t('Konu (İsteğe Bağlı)', 'Subject (Optional)') ?>
                            </label>
                            <input type="text" id="contact_subject" name="subject" class="contact-input" placeholder="<?= site_t('Örn: Editoryal çekim / sergi / lisanslama talebi', 'e.g. Editorial commission / exhibition / licensing inquiry') ?>">
                        </div>

                        <div class="form-row">
                            <label class="form-field-label" for="contact_message">
                                <?= site_t('Mesajınız', 'Your Message') ?> <span class="required-star">*</span>
                            </label>
                            <textarea id="contact_message" name="message" class="contact-textarea" rows="5" required placeholder="<?= site_t('Mesajınızı buraya yazınız...', 'Please enter your message here...') ?>"></textarea>
                        </div>

                        <div class="form-submit-row" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <?php if ($contact_layout === 'centered_minimal'): ?>
                                <a href="mailto:<?= e($contact_email) ?>" style="font-family:var(--font-mono, monospace); font-size:0.82rem; opacity:0.75; text-decoration:none;">
                                    <?= e($contact_email) ?> &bull; <?= e($loc) ?>
                                </a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <button type="submit" id="contactSubmitBtn" class="contact-submit-btn">
                                <span class="btn-text"><?= site_t('Mesajı Gönder', 'Send Message') ?></span>
                                <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            </button>
                        </div>
                    </form>
                </section>
            </div>

        </div>
    </main>

    <script>
    (function() {
        var form = document.getElementById('siteContactForm');
        if (!form) return;

        var alertBox = document.getElementById('contactFormAlert');
        var submitBtn = document.getElementById('contactSubmitBtn');
        var btnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
        var originalText = btnText ? btnText.textContent : '';

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (submitBtn) submitBtn.disabled = true;
            if (btnText) btnText.textContent = <?= json_encode(site_t('Gönderiliyor...', 'Sending...')) ?>;

            var formData = new FormData(form);
            formData.append('is_ajax', '1');

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { status: res.status, ok: res.ok, data: data };
                }).catch(function() {
                    return { status: res.status, ok: res.ok, data: {} };
                });
            })
            .then(function(result) {
                if (submitBtn) submitBtn.disabled = false;
                if (btnText) btnText.textContent = originalText;

                if (result.ok && result.data.success) {
                    alertBox.innerHTML = '<div class="alert-box alert-success">' +
                        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>' +
                        '<span>' + (result.data.message || <?= json_encode(site_t('Mesajınız başarıyla iletildi.', 'Your message has been sent.')) ?>) + '</span>' +
                    '</div>';
                    alertBox.style.display = 'block';
                    form.reset();
                } else {
                    var errMsg = (result.data && result.data.message) || (result.data && result.data.error) || <?= json_encode(site_t('Bir hata oluştu, lütfen tekrar deneyin.', 'An error occurred, please try again.')) ?>;
                    alertBox.innerHTML = '<div class="alert-box alert-error">' +
                        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
                        '<span>' + errMsg + '</span>' +
                    '</div>';
                    alertBox.style.display = 'block';
                }
            })
            .catch(function() {
                if (submitBtn) submitBtn.disabled = false;
                if (btnText) btnText.textContent = originalText;
                alertBox.innerHTML = '<div class="alert-box alert-error">' +
                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
                    '<span><?= site_t('Bağlantı hatası oluştu. Lütfen doğrudan e-posta ile iletişime geçiniz.', 'Network error. Please contact directly via email.') ?></span>' +
                '</div>';
                alertBox.style.display = 'block';
            });
        });
    })();
    </script>

    <?php
    $render_closing_html = false;
    require __DIR__ . '/layout_footer.php';
    ?>
</div>

<div class="right-content"></div>
</body>
</html>
