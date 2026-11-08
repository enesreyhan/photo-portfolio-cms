<?php
/**
 * includes/views/about.php
 * Hakkımda / About Me Sayfa Görünümü
 * Çoklu Şablon Mimarisi: Klasik Monografi, Sinematik Hero, Sabit Split, Editoryal Dergi,
 * Müze & Küratöryel Sergi, Minimalist Tipografi, Bento Grid ve Basın Kiti.
 */

$is_tr = ($lang ?? 'en') === 'tr';
// Şablonu Hakkımda düzeni olan bağımsız sayfa: kendi başlığı, metni ve kapak görseli kullanılır
// Asıl Hakkımda kaydı (slug 'aboutme') da 'page' türündedir; bağımsız sayfa moduna girmemeli (çift portre)
$is_custom_page = ($item['type'] ?? '') === 'page' && ($item['slug'] ?? '') !== 'aboutme';
$page_title = $is_custom_page ? item_field($item, 'title', $lang) : get_page_custom_title('about', $lang, item_field($item, 'title', $lang));
$page_desc = item_field($item, 'description', $lang);
$raw_content = item_field($item, 'content', $lang);

// ── Hakkımda Şablon & Tasarım Ayarları (CMS Settings) ──
$about_template             = get_setting('about_template', 'monograph_classic');
$about_portrait_position    = get_setting('about_portrait_position', 'right');
$about_portrait_width       = max(30, min(55, (int)get_setting('about_portrait_width', '40')));
$about_portrait_frame       = get_setting('about_portrait_frame', 'none');
$about_title_size           = get_setting('about_title_size', 'normal');
$about_narrative_columns    = get_setting('about_narrative_columns', '1');
$about_intro_dropcap        = (get_setting('about_intro_dropcap', '0') === '1');
$about_show_location_role   = (get_setting('about_show_location_role', '1') === '1');

$about_show_contact_banner  = (get_setting('about_show_contact_banner', '1') === '1');
$about_contact_style        = get_setting('about_contact_style', 'banner_boxed');
$about_show_field_map_link  = field_map_enabled() && (get_setting('about_show_field_map_link', '1') === '1');
$about_show_email_button    = (get_setting('about_show_email_button', '1') === '1');
$about_show_social_links    = (get_setting('about_show_social_links', '1') === '1');

$about_show_chronology      = (get_setting('about_show_chronology', '1') === '1');
$about_chrono_layout        = get_setting('about_chrono_layout', 'timeline_list');
$about_chrono_badge_style   = get_setting('about_chrono_badge_style', 'pill');
$about_chrono_group_by      = get_setting('about_chrono_group_by', 'flat');

$about_default_surface      = get_setting('about_default_surface', 'dark');
$about_container_width      = get_setting('about_container_width', 'standard');

// Yapılandırılmış Verileri Ayrıştır
$parsed_about = parse_about_content($raw_content);
$narrative = $parsed_about['narrative'] ?? '';
$portrait = $parsed_about['portrait'] ?? [];
$contact = $parsed_about['contact'] ?? [];
$chronology = $parsed_about['chronology'] ?? [];

$portrait_img  = !empty($portrait['img']) ? $portrait['img'] : '/assets/brand/portrait-placeholder.svg';
$portrait_name = !empty($portrait['name']) ? $portrait['name'] : site_owner_name();
$portrait_role = !empty($portrait['role']) ? $portrait['role'] : '';

$contact_kicker = !empty($contact['kicker']) ? $contact['kicker'] : (site_t('EDİTORYAL İŞ BİRLİĞİ & TEMSİL', 'EDITORIAL INQUIRIES & COMMISSIONS'));
$contact_title  = !empty($contact['title']) ? $contact['title'] : (site_t('Saha Görevleri & İletişim', 'Assignments & Contact'));
$contact_desc   = !empty($contact['desc']) ? $contact['desc'] : (site_t('Ulusal ve uluslararası editoryal saha görevlerine, uzun soluklu belgesel araştırmalara ve yayın projelerine açıktır.', 'Available for national and international editorial assignments, long-term documentary research and publishing projects.'));
$contact_email  = get_setting('contact_email', '') ?: ($contact['email'] ?? ''); // tek kaynak: Site Ayarları
$contact_loc    = !empty($contact['loc']) ? $contact['loc'] : '';

$chrono_title   = !empty($chronology['title']) ? $chronology['title'] : (site_t('Sergi, Yayın ve Programlar', 'Exhibitions, Publications & Media'));
$chrono_sub     = !empty($chronology['sub']) ? $chronology['sub'] : (site_t('Seçilmiş Monografiler, Katılımlar ve Basın Arşivi', 'Selected Monographs, Features and Press Archive'));
$chrono_items   = !empty($chronology['items']) && is_array($chronology['items']) ? $chronology['items'] : [];

$field_map_url  = url($lang . '/' . get_static_slug('field_work', $lang) . '/');

if ($is_custom_page) {
    $narrative = preg_match('/<(p|h[1-6]|div|ul|ol|figure|img|blockquote|iframe)\b/i', $raw_content) ? sanitize_html($raw_content) : render_markdown($raw_content);
    $chrono_items = [];
    $about_show_location_role = false;
    if (empty($item['cover_image'])) $about_portrait_position = 'none';
    $portrait_img  = (string)($item['cover_image'] ?? '') ?: $portrait_img;
    $portrait_name = $page_title;
    $portrait_role = $page_desc;
}

// Sosyal medya bağlantıları
$social_links = [];
$ig = get_setting('instagram_url', '');
$tw = get_setting('x_url', '');
$yt = get_setting('youtube_url', '');
$fb = get_setting('facebook_url', '');
if (!empty($ig)) $social_links['Instagram'] = $ig;
if (!empty($tw)) $social_links['X'] = $tw;
if (!empty($yt)) $social_links['YouTube'] = $yt;
if (!empty($fb)) $social_links['Facebook'] = $fb;

// Zemin Teması ve HTML Sınıfı (Karanlık Oda, Saf Beyaz ve Kitap Kağıdı)
if ($about_default_surface === 'white') {
    $html_class = 'aboutme-page editorial-page about-surface-white gallery-white light';
} elseif ($about_default_surface === 'paper') {
    $html_class = 'aboutme-page editorial-page about-surface-paper gallery-paper light';
} else {
    $html_class = 'dark aboutme-page editorial-page about-surface-dark';
}

if (!isset($site_title)) {
    $site_title = get_setting('site_title_' . $lang, get_setting('site_title_en', site_owner_name()));
}

require __DIR__ . '/layout_header.php';

$container_classes = [
    'about-container',
    'about-tpl-' . preg_replace('/[^a-z0-9_-]/i', '', $about_template),
    'about-width-' . preg_replace('/[^a-z0-9_-]/i', '', $about_container_width),
    'about-title-' . preg_replace('/[^a-z0-9_-]/i', '', $about_title_size),
    'about-portrait-pos-' . preg_replace('/[^a-z0-9_-]/i', '', $about_portrait_position),
    'about-frame-' . preg_replace('/[^a-z0-9_-]/i', '', $about_portrait_frame),
    'about-cols-' . ($about_narrative_columns === '2' ? '2' : '1'),
    'about-contact-' . preg_replace('/[^a-z0-9_-]/i', '', $about_contact_style),
    'about-chrono-' . preg_replace('/[^a-z0-9_-]/i', '', $about_chrono_layout),
];
if ($about_intro_dropcap) $container_classes[] = 'about-has-dropcap';

$container_style = sprintf('--about-portrait-width: %d%%;', $about_portrait_width);
?>

<?php render_site_navigation($lang, $en_path, $tr_path, $instagram_url); ?>

<div class="left-content"></div>

<div class="content">
    <main>
        <article class="<?= e(implode(' ', $container_classes)) ?>" id="about-article-root" style="<?= e($container_style) ?>">

            <!-- ── BAŞLIK & KÜNYE BÖLÜMÜ (Müze, Dergi ve Klasik Modlar) ── -->
            <?php if ($about_template !== 'cinema_hero'): ?>
            <header class="about-mono-header">
                <?php
                // Künye: Hakkımda'daki konum + unvanın ilk kısmı (ör. "İstanbul, Türkiye • Belgesel Fotoğrafçı"); CSS büyük harfe çevirir
                $about_meta_line = implode(' • ', array_filter([trim((string)$contact_loc), trim(explode('•', (string)$portrait_role)[0])]));
                if ($about_show_location_role && $about_meta_line !== ''): ?>
                <div class="mono-chapter-meta">
                    <span class="mono-chapter-location">
                        <?= e($about_meta_line) ?>
                    </span>
                </div>
                <?php endif; ?>
                <h1 class="mono-chapter-title about-main-title"><?= e($page_title) ?></h1>
            </header>
            <?php endif; ?>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- ŞABLON 2: SİNEMATİK PORTRE HERO -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <?php if ($about_template === 'cinema_hero'): ?>
                <div class="about-cinema-hero-wrap">
                    <img src="<?= e(url($portrait_img)) ?>" alt="<?= e($portrait_name) ?>" class="about-cinema-hero-img" />
                    <div class="about-cinema-hero-overlay">
                        <?php if ($about_show_location_role): ?>
                        <div class="mono-chapter-meta" style="margin-bottom:6px;">
                            <span class="mono-chapter-location" style="color:rgba(255,255,255,0.78);">
                                <?= site_t('İSTANBUL, TÜRKİYE • BELGESEL FOTOĞRAFÇI', 'ISTANBUL, TURKEY • DOCUMENTARY PHOTOGRAPHER') ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <h1 class="mono-chapter-title about-main-title" style="color:#ffffff; margin:0; line-height:1.05;"><?= e($page_title) ?></h1>
                    </div>
                </div>

                <div class="about-narrative">
                    <?= $narrative ?>
                </div>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- ŞABLON 7: MODERN BENTO GRID (MODÜLER KARTLAR) -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <?php elseif ($about_template === 'modular_bento'): ?>
                <div class="about-bento-grid">
                    <!-- 1. Biyografi Kartı -->
                    <div class="bento-card bento-card-bio">
                        <div class="about-narrative">
                            <?= $narrative ?>
                        </div>
                    </div>

                    <!-- 2. Portre Kartı -->
                    <?php if ($about_portrait_position !== 'none'): ?>
                    <div class="bento-card bento-card-portrait">
                        <figure class="about-portrait-card" style="height:100%; margin:0; border:none; box-shadow:none; border-radius:0;">
                            <img src="<?= e(url($portrait_img)) ?>" alt="<?= e($portrait_name) ?>" class="about-portrait-img" style="height:100%; object-fit:cover;" />
                            <figcaption class="about-portrait-caption">
                                <span class="caption-name"><?= e($portrait_name) ?></span>
                                <span class="caption-role"><?= e($portrait_role) ?></span>
                            </figcaption>
                        </figure>
                    </div>
                    <?php endif; ?>

                    <!-- 3. İletişim Kartı -->
                    <?php if ($about_show_contact_banner): ?>
                    <div class="bento-card bento-card-contact">
                        <span class="contact-banner-kicker"><?= e($contact_kicker) ?></span>
                        <h3 class="contact-banner-title" style="margin-bottom:0.4rem;"><?= e($contact_title) ?></h3>
                        <p class="contact-banner-desc" style="margin-bottom:1rem;"><?= e($contact_desc) ?></p>
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <button type="button" class="about-contact-btn" onclick="openContactModal()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span><?= site_t('İletişim Formu', 'Contact Form') ?></span>
                            </button>
                            <?php if ($about_show_email_button): ?>
                            <a href="mailto:<?= e($contact_email) ?>" class="about-email-btn">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                                <span><?= e($contact_email) ?></span>
                            </a>
                            <?php endif; ?>
                            <span class="contact-location"><?= e($contact_loc) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 4. Saha Haritası Kartı -->
                    <?php if ($about_show_field_map_link): ?>
                    <div class="bento-card bento-card-map">
                        <span style="font-family:var(--font-mono, monospace); font-size:0.74rem; font-weight:700; color:var(--er-accent, #fdd000); letter-spacing:0.1em; text-transform:uppercase;">COĞRAFİ ARŞİV</span>
                        <h4 style="font-size:1.15rem; font-weight:700; margin:0.3rem 0 0.5rem 0;"><?= site_t('Saha Rotaları ve Belgesel Haritası', 'Field Work & Documentary Map') ?></h4>
                        <p style="font-size:0.88rem; opacity:0.75; line-height:1.5; margin-bottom:1rem;"><?= site_t('Belgesel çalışmalarının coğrafi rotaları ve koordinat istasyonları.', 'Geographic routes and coordinate stations of documentary field projects.') ?></p>
                        <a href="<?= e($field_map_url) ?>" class="about-field-link">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                            <span><?= site_t('Haritayı Keşfet →', 'Explore Field Map →') ?></span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- DİĞER TÜM ŞABLONLAR (Monografi, Split, Dergi, Müze, Minimalist, Basın Kiti) -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <?php else: ?>

                <!-- Basın Kiti Başlık Çubuğu -->
                <?php if ($about_template === 'press_dossier'): ?>
                <div class="press-kit-badge-bar" style="display:flex; justify-content:space-between; align-items:center; padding:10px 16px; background:rgba(128,128,128,0.1); border-radius:6px; margin-bottom:2rem; font-family:var(--font-mono, monospace); font-size:0.8rem;">
                    <span><strong><?= site_t('RESMİ BASIN KİTİ & BİYOGRAFİ DOSYASI', 'OFFICIAL PRESS KIT & DOSSIER') ?></strong></span>
                    <button type="button" onclick="window.print()" class="press-kit-print-btn">
                        <span><?= site_t('PDF / Yazdır 🖨', 'Print / PDF 🖨') ?></span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="about-main-grid">
                    <div class="about-narrative">
                        <?= $narrative ?>
                    </div>

                    <?php if ($about_portrait_position !== 'none'): ?>
                    <aside class="about-aside">
                        <figure class="about-portrait-card">
                            <img src="<?= e(url($portrait_img)) ?>" alt="<?= e($portrait_name) ?>" class="about-portrait-img" />
                            <figcaption class="about-portrait-caption">
                                <span class="caption-name"><?= e($portrait_name) ?></span>
                                <span class="caption-role"><?= e($portrait_role) ?></span>
                            </figcaption>
                        </figure>

                        <!-- Minimalist veya Split modda yan iletişim hapları -->
                        <?php if ($about_template === 'sticky_split' && $about_show_email_button): ?>
                        <div style="margin-top:1rem; display:flex; flex-direction:column; gap:8px;">
                            <a href="mailto:<?= e($contact_email) ?>" class="about-email-btn" style="justify-content:center;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                                <span><?= e($contact_email) ?></span>
                            </a>
                            <span class="contact-location" style="text-align:center;"><?= e($contact_loc) ?></span>
                        </div>
                        <?php endif; ?>
                    </aside>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

            <!-- ── GÖREVLER & İLETİŞİM BANNERI (Bento Grid dışındaki modlar için) ── -->
            <?php if ($about_template !== 'modular_bento' && $about_show_contact_banner && !($about_template === 'sticky_split' && $about_contact_style === 'floating_badge')): ?>
                <?php if ($about_contact_style === 'minimal_strip'): ?>
                    <section class="about-contact-minimal_strip">
                        <div>
                            <span style="font-family:var(--font-mono, monospace); font-size:0.75rem; font-weight:700; color:var(--er-accent, #fdd000); letter-spacing:0.08em; text-transform:uppercase; display:block; margin-bottom:2px;"><?= e($contact_kicker) ?></span>
                            <strong style="font-size:1.05rem;"><?= e($contact_title) ?>:</strong>
                            <span style="font-size:0.92rem; opacity:0.75; margin-left:6px;"><?= e($contact_desc) ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <button type="button" class="about-contact-btn" onclick="openContactModal()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span><?= site_t('İletişim Formu', 'Contact Form') ?></span>
                            </button>
                            <?php if ($about_show_email_button): ?>
                            <a href="mailto:<?= e($contact_email) ?>" class="about-email-btn" style="padding:8px 16px; font-size:0.82rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                                <span><?= e($contact_email) ?></span>
                            </a>
                            <?php endif; ?>
                            <span class="contact-location"><?= e($contact_loc) ?></span>
                        </div>
                    </section>
                <?php elseif ($about_contact_style === 'compact_buttons'): ?>
                    <section class="about-contact-compact_buttons">
                        <button type="button" class="about-contact-btn" onclick="openContactModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span><?= site_t('İletişim Formu', 'Contact Form') ?></span>
                        </button>
                        <?php if ($about_show_email_button): ?>
                        <a href="mailto:<?= e($contact_email) ?>" class="about-email-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                            <span><?= e($contact_email) ?></span>
                        </a>
                        <?php endif; ?>
                        <span class="contact-location"><?= e($contact_loc) ?></span>
                    </section>
                <?php else: ?>
                    <section class="about-contact-banner" id="iletisim">
                        <span id="contact" style="position:relative; top:-90px; display:block; visibility:hidden;"></span>
                        <div class="contact-banner-content">
                            <span class="contact-banner-kicker"><?= e($contact_kicker) ?></span>
                            <h3 class="contact-banner-title"><?= e($contact_title) ?></h3>
                            <p class="contact-banner-desc"><?= e($contact_desc) ?></p>
                        </div>
                        <div class="contact-banner-action">
                            <button type="button" class="about-contact-btn" onclick="openContactModal()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span><?= site_t('İletişim Formu', 'Contact Form') ?></span>
                            </button>
                            <?php if ($about_show_email_button): ?>
                            <a href="mailto:<?= e($contact_email) ?>" class="about-email-btn">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                                <span><?= e($contact_email) ?></span>
                            </a>
                            <?php endif; ?>
                            <span class="contact-location"><?= e($contact_loc) ?></span>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ── İLETİŞİM POPUP / MODAL DİYALOG ── -->
            <div id="contactModalBackdrop" class="contact-modal-backdrop" onclick="if(event.target === this) closeContactModal();">
                <div class="contact-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="contactModalTitle">
                    <button type="button" class="contact-modal-close" onclick="closeContactModal()" aria-label="<?= site_t('Kapat', 'Close') ?>">&times;</button>
                    
                    <div class="contact-form-header">
                        <span class="contact-form-kicker"><?= site_t('EDİTORYAL & İŞ BİRLİĞİ', 'EDITORIAL & INQUIRIES') ?></span>
                        <h3 class="contact-form-title" id="contactModalTitle"><?= site_t('Mesaj Gönderin', 'Send a Message') ?></h3>
                        <p class="contact-form-subtitle">
                            <?= site_t('Editoryal saha görevleri, lisanslama, sergi projeleri veya sorularınız için doğrudan mesaj iletebilirsiniz.', 'For editorial assignments, licensing, exhibitions, commissions, or inquiries, please feel free to reach out.') ?>
                        </p>
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

                        <!-- Honeypot Alanları (İnsanlar görmez, botlar doldurunca elenir) -->
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
                            <input type="text" id="contact_subject" name="subject" class="contact-input" placeholder="<?= site_t('Örn: Editoryal çekim / sergi iş birliği talebi', 'e.g. Editorial commission / exhibition inquiry') ?>">
                        </div>

                        <div class="form-row">
                            <label class="form-field-label" for="contact_message">
                                <?= site_t('Mesajınız', 'Your Message') ?> <span class="required-star">*</span>
                            </label>
                            <textarea id="contact_message" name="message" class="contact-textarea" rows="4" required placeholder="<?= site_t('Mesajınızı buraya yazınız...', 'Please enter your message here...') ?>"></textarea>
                        </div>

                        <div class="form-submit-row">
                            <button type="submit" id="contactSubmitBtn" class="contact-submit-btn">
                                <span class="btn-text"><?= site_t('Mesajı Gönder', 'Send Message') ?></span>
                                <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── KÜRATOERYEL SERGİ & YAYIN KRONOLOJİSİ ── -->
            <?php if ($about_show_chronology && !empty($chrono_items)): ?>
            <section class="about-chronology">
                <div class="chronology-header">
                    <h2 class="chronology-title"><?= e($chrono_title) ?></h2>
                    <?php if (!empty($chrono_sub)): ?>
                        <span class="chronology-sub"><?= e($chrono_sub) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Tablo Izgarası Modu -->
                <?php if ($about_chrono_layout === 'table_grid'): ?>
                    <div style="overflow-x:auto;">
                        <table class="chronology-table" style="border:none !important; border-collapse:collapse !important;">
                            <thead>
                                <tr style="border:none !important; border-bottom:none !important; font-family:var(--font-mono, monospace); font-size:0.76rem; opacity:0.65; text-transform:uppercase;">
                                    <th style="padding:8px 10px; width:80px; border:none !important;"><?= site_t('Yıl', 'Year') ?></th>
                                    <th style="padding:8px 10px; width:110px; border:none !important;"><?= site_t('Tür', 'Type') ?></th>
                                    <th style="padding:8px 10px; border:none !important;"><?= site_t('Başlık', 'Title') ?></th>
                                    <th style="padding:8px 10px; border:none !important;"><?= site_t('Mekan / Yayın', 'Venue / Publisher') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chrono_items as $ci): 
                                    $c_year  = $ci['year'] ?? '';
                                    $c_badge = $ci['badge'] ?? 'badge-exhibition';
                                    $c_blabel = $ci['badge_label'] ?? 'Sergi';
                                    $c_title = $ci['title'] ?? '';
                                    $c_url   = trim($ci['url'] ?? '');
                                    $c_venue = $ci['venue'] ?? '';
                                ?>
                                <tr style="border:none !important;">
                                    <td class="chrono-year" style="padding:10px; border:none !important;"><?= e($c_year) ?></td>
                                    <td style="padding:10px; border:none !important;"><span class="chrono-badge <?= e($c_badge) ?>"><?= e($c_blabel) ?></span></td>
                                    <td style="padding:10px; border:none !important;">
                                        <?php if ($c_url !== ''): ?>
                                            <a href="<?= e($c_url) ?>" target="_blank" rel="noopener noreferrer" class="chrono-link">
                                                <span><?= e($c_title) ?></span>
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                            </a>
                                        <?php else: ?>
                                            <h4 class="chrono-name" style="margin:0;"><?= e($c_title) ?></h4>
                                        <?php endif; ?>
                                    </td>
                                    <td class="chrono-venue" style="padding:10px; border:none !important;"><?= e($c_venue) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <!-- Kompakt Kartlar Modu -->
                <?php elseif ($about_chrono_layout === 'compact_cards'): ?>
                    <div class="chronology-cards-grid">
                        <?php foreach ($chrono_items as $ci): 
                            $c_year  = $ci['year'] ?? '';
                            $c_badge = $ci['badge'] ?? 'badge-exhibition';
                            $c_blabel = $ci['badge_label'] ?? 'Sergi';
                            $c_title = $ci['title'] ?? '';
                            $c_url   = trim($ci['url'] ?? '');
                            $c_venue = $ci['venue'] ?? '';
                        ?>
                        <div class="chrono-card-item">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <span class="chrono-year"><?= e($c_year) ?></span>
                                <span class="chrono-badge <?= e($c_badge) ?>"><?= e($c_blabel) ?></span>
                            </div>
                            <?php if ($c_url !== ''): ?>
                                <a href="<?= e($c_url) ?>" target="_blank" rel="noopener noreferrer" class="chrono-link">
                                    <span><?= e($c_title) ?></span>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                </a>
                            <?php else: ?>
                                <h4 class="chrono-name"><?= e($c_title) ?></h4>
                            <?php endif; ?>
                            <span class="chrono-venue"><?= e($c_venue) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                <!-- Klasik Dikey Zaman Çizelgesi Listesi -->
                <?php else: ?>
                    <div class="chronology-list">
                        <?php foreach ($chrono_items as $ci): 
                            $c_year  = $ci['year'] ?? '';
                            $c_badge = $ci['badge'] ?? 'badge-exhibition';
                            $c_blabel = $ci['badge_label'] ?? 'Sergi';
                            $c_title = $ci['title'] ?? '';
                            $c_url   = trim($ci['url'] ?? '');
                            $c_venue = $ci['venue'] ?? '';
                        ?>
                        <div class="chronology-item">
                            <span class="chrono-year"><?= e($c_year) ?></span>
                            <span class="chrono-badge <?= e($c_badge) ?>"><?= e($c_blabel) ?></span>
                            <div class="chrono-info">
                                <?php if ($c_url !== ''): ?>
                                    <a href="<?= e($c_url) ?>" target="_blank" rel="noopener noreferrer" class="chrono-link">
                                        <span><?= e($c_title) ?></span>
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    </a>
                                <?php else: ?>
                                    <h4 class="chrono-name"><?= e($c_title) ?></h4>
                                <?php endif; ?>
                                <span class="chrono-venue"><?= e($c_venue) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

        </article>
    </main>

    <script>
    // ── İletişim Popup / Modal ve AJAX Form Yönetimi ──
    function openContactModal() {
        var modal = document.getElementById('contactModalBackdrop');
        if (!modal) return;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        setTimeout(function() {
            var firstInput = document.getElementById('contact_name');
            if (firstInput) firstInput.focus();
        }, 150);
    }

    function closeContactModal() {
        var modal = document.getElementById('contactModalBackdrop');
        if (!modal) return;
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    window.openContactModal = openContactModal;
    window.closeContactModal = closeContactModal;

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeContactModal();
    });

    (function() {
        var form = document.getElementById('siteContactForm');
        if (!form) return;

        var alertBox = document.getElementById('contactFormAlert');
        var submitBtn = document.getElementById('contactSubmitBtn');
        var btnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
        var originalText = btnText ? btnText.textContent : '';

        // Sayfa içi #iletisim / #contact bağlantılarına tıklandığında popup aç
        document.querySelectorAll('a[href="#iletisim"], a[href="#contact"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                openContactModal();
            });
        });

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
            .catch(function(err) {
                if (submitBtn) submitBtn.disabled = false;
                if (btnText) btnText.textContent = originalText;
                alertBox.innerHTML = '<div class="alert-box alert-error">' +
                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
                    '<span><?= site_t('Bağlantı hatası oluştu. Lütfen doğrudan e-posta ile iletişime geçiniz.', 'Network error. Please contact directly via email.') ?></span>' +
                '</div>';
                alertBox.style.display = 'block';
            });
        });

        // Sayfa #iletisim veya #contact ile açıldığında veya form yanıtı döndüğünde popup'ı doğrudan aç
        if (window.location.hash === '#iletisim' || window.location.hash === '#contact' || window.location.search.indexOf('msg_success') !== -1 || window.location.search.indexOf('msg_error') !== -1) {
            setTimeout(openContactModal, 300);
        }
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
