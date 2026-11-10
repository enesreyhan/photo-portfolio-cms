<?php
/**
 * includes/views/layout_footer.php
 * Sitenin Alt Bilgi Alanı (Footer & Global Scripts)
 */

$is_tr = ($lang ?? 'en') === 'tr';
$site_title = setting_l('site_title', null, site_owner_name());

$footer_enabled          = (int)get_setting('footer_enabled', 1);
$footer_layout           = get_setting('footer_layout', 'centered_minimal'); // centered_minimal | editorial_colophon | multi_column | split_bar
$footer_show_back_to_top = (int)get_setting('footer_show_back_to_top', 1);
$footer_show_inline_top  = (int)get_setting('footer_show_inline_top', 0);

$f_brand_title = setting_l('footer_brand_title', null, site_owner_name());
$f_tagline     = setting_l('footer_tagline', null, '');
$f_copy_raw    = setting_l('footer_copyright', null, site_t('© {year} {site_title}. Tüm hakları saklıdır.', '© {year} {site_title}. All rights reserved.')
);
$f_copy_text   = str_replace(['{year}', '{site_title}'], [date('Y'), $site_title], $f_copy_raw);
$f_subnote     = trim((string)setting_l('footer_subnote', null, ''));

$footer_socials_enabled = (int)get_setting('footer_socials_enabled', 1);
$footer_social_style    = get_setting('footer_social_style', 'minimal'); // minimal | circle_border | circle_glass | with_label
$footer_social_size     = max(14, min(36, (int)get_setting('footer_social_size', 24)));
$footer_social_gap      = max(8, min(48, (int)get_setting('footer_social_gap', 20)));

$instagram_url = get_setting('instagram_url', '');
$youtube_url   = get_setting('youtube_url', '');
$x_url         = get_setting('x_url', '');
$facebook_url  = get_setting('facebook_url', '');
$linkedin_url  = get_setting('linkedin_url', '');
$behance_url   = get_setting('behance_url', '');
$contact_email = get_setting('contact_email', '');

$footer_show_instagram = (int)get_setting('footer_show_instagram', 1);
$footer_show_twitter   = (int)get_setting('footer_show_twitter', 1);
$footer_show_facebook  = (int)get_setting('footer_show_facebook', 0);
$footer_show_youtube   = (int)get_setting('footer_show_youtube', 1);
$footer_show_linkedin  = (int)get_setting('footer_show_linkedin', 0);
$footer_show_behance   = (int)get_setting('footer_show_behance', 0);
$footer_show_email     = (int)get_setting('footer_show_email', 0);
$footer_show_rss       = (int)get_setting('footer_show_rss', 1);

$footer_show_main_menu = (int)get_setting('footer_show_main_menu', 0);
$footer_custom_links   = json_decode(get_setting('footer_custom_links_json', '[]') ?: '[]', true);
if (!is_array($footer_custom_links)) $footer_custom_links = [];

$footer_bg_mode     = get_setting('footer_bg_mode', 'theme_auto'); // theme_auto | dark | light | custom
$footer_custom_bg   = get_setting('footer_custom_bg', '#0a0b0e');
$footer_custom_text = get_setting('footer_custom_text', '#e2e8f0');
$footer_border_top  = (int)get_setting('footer_border_top', 1);
$footer_padding_y   = max(12, min(96, (int)get_setting('footer_padding_y', 36)));

// Footer Menü Linklerini Hazırla
$footer_nav_items = [];
if ($footer_show_main_menu) {
    try {
        $pdo_f = DB::getConnection();
        $db_f_menus = $pdo_f->query("SELECT * FROM `menus` WHERE `is_active` = 1 AND `is_separator` = 0 ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($db_f_menus as $fm) {
            $fm_name = get_menu_localized_title($fm, $lang);
            $fm_raw  = item_field($fm, 'url', $lang);
            $fm_url  = (str_starts_with($fm_raw, 'http://') || str_starts_with($fm_raw, 'https://')) ? $fm_raw : url($fm_raw);
            if ($fm_name) {
                $footer_nav_items[] = ['name' => $fm_name, 'url' => $fm_url];
            }
        }
    } catch (\Throwable $e) {}
}
foreach ($footer_custom_links as $cl) {
    $cl_name = '';
    foreach (lang_chain($GLOBALS['lang'] ?? ($is_tr ? 'tr' : 'en')) as $clc) { if (trim((string)($cl['title_' . $clc] ?? '')) !== '') { $cl_name = (string)$cl['title_' . $clc]; break; } }
    $cl_raw  = trim((string)($cl['url'] ?? ''));
    if ($cl_name !== '' && $cl_raw !== '') {
        $cl_url = (str_starts_with($cl_raw, 'http://') || str_starts_with($cl_raw, 'https://') || str_starts_with($cl_raw, 'mailto:')) ? $cl_raw : url($cl_raw);
        $footer_nav_items[] = ['name' => $cl_name, 'url' => $cl_url];
    }
}

$in_mono_colophon = !empty($inside_mono_colophon);
?>
<?php if ($footer_enabled): ?>
<style>
html body footer.site-footer.om-footer-custom {
    padding-top: <?= $footer_padding_y ?>px !important;
    padding-bottom: <?= max(16, round($footer_padding_y * 0.65)) ?>px !important;
    border-top: <?= $footer_border_top ? '1px solid var(--border, rgba(128,128,128,0.18))' : 'none' ?> !important;
    <?php if ($footer_bg_mode === 'dark'): ?>
    background: #0a0b0e !important;
    color: #f8fafc !important;
    padding-left: 28px !important;
    padding-right: 28px !important;
    border-radius: 8px !important;
    <?php elseif ($footer_bg_mode === 'light'): ?>
    background: #f4f2ed !important;
    color: #141413 !important;
    padding-left: 28px !important;
    padding-right: 28px !important;
    border-radius: 8px !important;
    <?php elseif ($footer_bg_mode === 'custom'): ?>
    background: <?= e($footer_custom_bg) ?> !important;
    color: <?= e($footer_custom_text) ?> !important;
    padding-left: 28px !important;
    padding-right: 28px !important;
    border-radius: 8px !important;
    <?php endif; ?>
}
<?php if ($footer_bg_mode !== 'theme_auto'): ?>
html body footer.site-footer.om-footer-custom .footer-copy,
html body footer.site-footer.om-footer-custom a.footer-social-link,
html body footer.site-footer.om-footer-custom a.footer-legal-link,
html body footer.site-footer.om-footer-custom a.footer-custom-nav-link {
    color: inherit !important;
}
<?php endif; ?>
.site-footer .footer-socials {
    gap: <?= $footer_social_gap ?>px !important;
    flex-wrap: wrap !important;
}
.site-footer .footer-social-icon {
    width: <?= $footer_social_size ?>px !important;
    height: <?= $footer_social_size ?>px !important;
    min-width: <?= $footer_social_size ?>px !important;
    min-height: <?= $footer_social_size ?>px !important;
}
<?php if ($footer_social_style === 'circle_border'): ?>
.site-footer a.footer-social-link {
    width: <?= $footer_social_size + 16 ?>px !important;
    height: <?= $footer_social_size + 16 ?>px !important;
    border: 1px solid rgba(128,128,128,0.32) !important;
    border-radius: 50% !important;
}
<?php elseif ($footer_social_style === 'circle_glass'): ?>
.site-footer a.footer-social-link {
    width: <?= $footer_social_size + 16 ?>px !important;
    height: <?= $footer_social_size + 16 ?>px !important;
    background: rgba(128,128,128,0.12) !important;
    border-radius: 50% !important;
}
<?php elseif ($footer_social_style === 'with_label'): ?>
.site-footer a.footer-social-link {
    width: auto !important;
    height: auto !important;
    padding: 5px 10px !important;
    gap: 7px !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
}
<?php endif; ?>
.footer-custom-nav {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 18px;
    margin: 0.4rem 0 1.1rem 0;
}
a.footer-custom-nav-link {
    color: inherit !important;
    opacity: 0.78;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none !important;
    border: none !important;
    transition: opacity 0.2s ease;
}
a.footer-custom-nav-link:hover {
    opacity: 1;
}
.footer-multi-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr;
    gap: 32px;
    text-align: left;
    max-width: 1120px;
    margin: 0 auto 24px auto;
    padding-bottom: 22px;
    border-bottom: 1px solid rgba(128,128,128,0.16);
}
.footer-split-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    max-width: 1120px;
    margin: 0 auto;
    text-align: left;
}
.footer-subnote {
    font-size: 0.78rem;
    opacity: 0.55;
    margin-top: 6px;
    line-height: 1.45;
}
@media (max-width: 768px) {
    .footer-multi-grid {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 20px;
    }
    .footer-multi-grid .footer-socials,
    .footer-split-row .footer-socials {
        justify-content: center !important;
    }
    .footer-split-row {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<footer class="site-footer om-footer-custom om-footer-<?= e($footer_layout) ?>">
    <?php
    // Sosyal Medya İkonları HTML Bloğu
    ob_start();
    if ($footer_socials_enabled): ?>
    <div class="footer-socials" style="<?= ($footer_layout === 'multi_column' || $footer_layout === 'split_bar') ? 'justify-content:flex-start; margin-bottom:0;' : '' ?>">
        <?php if ($footer_show_instagram && $instagram_url): ?>
            <a href="<?= e($instagram_url) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-link" aria-label="Instagram">
                <svg class="footer-social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" width="<?= $footer_social_size ?>" height="<?= $footer_social_size ?>" aria-hidden="true"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
                <?php if ($footer_social_style === 'with_label'): ?><span>Instagram</span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($footer_show_twitter && $x_url): ?>
            <a href="<?= e($x_url) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-link" aria-label="X (Twitter)">
                <svg class="footer-social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor" width="<?= $footer_social_size ?>" height="<?= $footer_social_size ?>" aria-hidden="true"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
                <?php if ($footer_social_style === 'with_label'): ?><span>X</span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($footer_show_facebook && $facebook_url): ?>
            <a href="<?= e($facebook_url) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-link" aria-label="Facebook">
                <svg class="footer-social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor" width="<?= $footer_social_size ?>" height="<?= $footer_social_size ?>" aria-hidden="true"><path d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256z"/></svg>
                <?php if ($footer_social_style === 'with_label'): ?><span>Facebook</span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($footer_show_youtube && $youtube_url): ?>
            <a href="<?= e($youtube_url) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-link" aria-label="YouTube">
                <svg class="footer-social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor" width="<?= $footer_social_size ?>" height="<?= $footer_social_size ?>" aria-hidden="true"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/></svg>
                <?php if ($footer_social_style === 'with_label'): ?><span>YouTube</span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($footer_show_linkedin && $linkedin_url): ?>
            <a href="<?= e($linkedin_url) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-link" aria-label="LinkedIn">
                <svg class="footer-social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" width="<?= $footer_social_size ?>" height="<?= $footer_social_size ?>" aria-hidden="true"><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>
                <?php if ($footer_social_style === 'with_label'): ?><span>LinkedIn</span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($footer_show_behance && $behance_url): ?>
            <a href="<?= e($behance_url) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-link" aria-label="Behance">
                <svg class="footer-social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor" width="<?= $footer_social_size ?>" height="<?= $footer_social_size ?>" aria-hidden="true"><path d="M232 237.2c31.8-15.2 48.4-38.2 48.4-74 0-70.6-52.6-87.8-113.3-87.8H0v354.4h171.8c64.4 0 124.9-30.9 124.9-102.9 0-44.5-21.1-77.4-64.7-89.7zM77.9 135.9H151c28.1 0 53.4 7.9 53.4 40.5 0 30.1-19.7 42.2-47.5 42.2h-79v-82.7zm83.3 233.7H77.9V272h84.9c34.3 0 56 14.3 56 50.6 0 35.8-25.9 47-57.6 47zm358.5-240.7H376V94h143.7v34.9zM576 305.2c0-75.9-44.4-139.2-124.9-139.2-78.2 0-131.3 58.8-131.3 135.8 0 79.9 50.3 134.7 131.3 134.7 61.3 0 101-27.6 120.1-86.3H509c-6.7 21.9-34.3 33.5-55.7 33.5-41.3 0-63-24.2-63-65.3h185.1c.3-4.2.6-8.7.6-13.2zM390.4 274c2.3-33.7 24.7-54.8 58.5-54.8 35.4 0 53.2 20.8 56.2 54.8H390.4z"/></svg>
                <?php if ($footer_social_style === 'with_label'): ?><span>Behance</span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($footer_show_email && $contact_email): ?>
            <a href="mailto:<?= e($contact_email) ?>" class="footer-social-link" aria-label="Email">
                <svg class="footer-social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="<?= $footer_social_size ?>" height="<?= $footer_social_size ?>" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <?php if ($footer_social_style === 'with_label'): ?><span>E-Posta</span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($footer_show_rss): ?>
            <a href="<?= e(url('atom.xml')) ?>" class="footer-social-link" aria-label="RSS Feed">
                <svg class="footer-social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" width="<?= $footer_social_size ?>" height="<?= $footer_social_size ?>" aria-hidden="true"><path d="M0 64C0 46.3 14.3 32 32 32c229.8 0 416 186.2 416 416c0 17.7-14.3 32-32 32s-32-14.3-32-32C384 253.6 226.4 96 32 96C14.3 96 0 81.7 0 64zM128 416c0 35.3-28.7 64-64 64s-64-28.7-64-64s28.7-64 64-64s64 28.7 64 64zM32 160c159.1 0 288 128.9 288 288c0 17.7-14.3 32-32 32s-32-14.3-32-32c0-123.7-100.3-224-224-224c-17.7 0-32-14.3-32-32s14.3-32 32-32z"/></svg>
                <?php if ($footer_social_style === 'with_label'): ?><span>RSS</span><?php endif; ?>
            </a>
        <?php endif; ?>
    </div>
    <?php endif;
    $socials_block = ob_get_clean();

    // Yasal Linkler Bloğu
    ob_start();
    $show_legal_links = (get_setting('legal_pages_enabled', '1') == '1');
    $show_cookie_link = function_exists('cookie_consent_enabled') && cookie_consent_enabled();
    if ($show_legal_links || $show_cookie_link): ?>
    <div class="footer-legal" style="<?= ($footer_layout === 'split_bar') ? 'justify-content:flex-start; margin:4px 0 0 0;' : '' ?>">
        <?php if ($show_legal_links): ?>
        <a href="<?= url(($is_tr ? 'tr/aydinlatma-metni/' : 'en/privacy-notice/')) ?>" class="footer-legal-link"><?= site_t('Aydınlatma Metni', 'Privacy Notice') ?></a>
        <span class="footer-legal-sep">·</span>
        <a href="<?= url(($is_tr ? 'tr/acik-riza-metni/' : 'en/explicit-consent/')) ?>" class="footer-legal-link"><?= site_t('Açık Rıza Metni', 'Explicit Consent') ?></a>
        <?php endif; ?>
        <?php if ($show_cookie_link): ?>
        <?php if ($show_legal_links): ?><span class="footer-legal-sep">·</span><?php endif; ?>
        <a href="#" class="footer-legal-link" data-cookie-settings role="button"><?= e(cookie_consent_strings($lang)['title']) ?></a>
        <?php endif; ?>
    </div>
    <?php endif;
    $legal_block = ob_get_clean();
    ?>

    <?php if ($footer_layout === 'multi_column'): ?>
        <div class="footer-multi-grid">
            <div>
                <?php if ($f_brand_title): ?>
                    <h4 style="font-family:var(--header-font, sans-serif); font-size:1.25rem; font-weight:700; margin:0 0 6px 0;"><?= e($f_brand_title) ?></h4>
                <?php endif; ?>
                <?php if ($f_tagline): ?>
                    <p style="font-size:0.88rem; opacity:0.68; margin:0; line-height:1.5;"><?= e($f_tagline) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <?php if (!empty($footer_nav_items)): ?>
                    <div style="font-family:var(--font-mono, monospace); font-size:0.7rem; text-transform:uppercase; letter-spacing:0.1em; opacity:0.5; margin-bottom:10px;"><?= site_t('GEZİNTİ', 'NAVIGATION') ?></div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <?php foreach ($footer_nav_items as $fni): ?>
                            <a href="<?= e($fni['url']) ?>" class="footer-custom-nav-link"><?= e($fni['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-family:var(--font-mono, monospace); font-size:0.7rem; text-transform:uppercase; letter-spacing:0.1em; opacity:0.5; margin-bottom:10px;"><?= site_t('BAĞLANTI & SOSYAL', 'CONNECT') ?></div>
                <?= $socials_block ?>
            </div>
        </div>
        <div class="footer-split-row">
            <div>
                <p class="footer-copy" style="margin:0;"><?= e($f_copy_text) ?></p>
                <?php if ($f_subnote !== ''): ?>
                    <p class="footer-subnote"><?= e($f_subnote) ?></p>
                <?php endif; ?>
            </div>
            <div><?= $legal_block ?></div>
        </div>

    <?php elseif ($footer_layout === 'split_bar'): ?>
        <div class="footer-split-row">
            <div>
                <?php if (!empty($footer_nav_items)): ?>
                    <div class="footer-custom-nav" style="justify-content:flex-start; margin-bottom:6px;">
                        <?php foreach ($footer_nav_items as $fni): ?>
                            <a href="<?= e($fni['url']) ?>" class="footer-custom-nav-link"><?= e($fni['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p class="footer-copy" style="margin:0;"><?= e($f_copy_text) ?></p>
                <?= $legal_block ?>
                <?php if ($f_subnote !== ''): ?>
                    <p class="footer-subnote"><?= e($f_subnote) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <?= $socials_block ?>
            </div>
        </div>

    <?php else: /* centered_minimal veya editorial_colophon */ ?>
        <?php if ($footer_layout === 'editorial_colophon' && !$in_mono_colophon): ?>
            <?php if ($f_brand_title): ?>
                <h3 style="font-family:var(--header-font, sans-serif); font-size:1.55rem; font-weight:700; margin:0 0 6px 0;"><?= e($f_brand_title) ?></h3>
            <?php endif; ?>
            <?php if ($f_tagline): ?>
                <p style="font-size:0.92rem; opacity:0.65; margin:0 0 18px 0;"><?= e($f_tagline) ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($footer_show_inline_top && !$in_mono_colophon): ?>
            <div style="margin-bottom:16px;">
                <a href="#" onclick="window.scrollTo({top:0,behavior:'smooth'}); return false;" class="footer-nav-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
                    <span><?= site_t('Başa Dön', 'Back to Top') ?></span>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($footer_nav_items)): ?>
            <nav class="footer-custom-nav" aria-label="Footer Navigation">
                <?php foreach ($footer_nav_items as $fni): ?>
                    <a href="<?= e($fni['url']) ?>" class="footer-custom-nav-link"><?= e($fni['name']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?= $socials_block ?>
        <?= $legal_block ?>

        <p class="footer-copy"><?= e($f_copy_text) ?></p>
        <?php if ($f_subnote !== ''): ?>
            <p class="footer-subnote"><?= e($f_subnote) ?></p>
        <?php endif; ?>
    <?php endif; ?>
</footer>
<?php endif; ?>

<?php if ($footer_show_back_to_top): ?>
<button id="scroll-to-top" aria-label="Scroll to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
</button>
<?php endif; ?>
<script>
// Alttaki duyuru bandı / çerez barı sağ alt köşeyi kaplıyorsa yüksekliğini --er-bottom-offset'e yaz
(function () {
    var root = document.documentElement, queued = false;
    function measure() {
        queued = false;
        var off = 0;
        document.querySelectorAll('.xt-announce, .er-cc').forEach(function (el) {
            if (el.hidden) return;
            var r = el.getBoundingClientRect();
            if (r.height && r.right > window.innerWidth - 100) off = Math.max(off, window.innerHeight - r.top);
        });
        root.style.setProperty('--er-bottom-offset', Math.round(off) + 'px');
    }
    function schedule() { if (!queued) { queued = true; setTimeout(measure, 50); } }
    new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true, attributeFilter: ['hidden'] });
    window.addEventListener('resize', schedule);
    schedule();
})();
</script>

<?php
$enable_lightbox     = get_setting('inner_enable_lightbox', '1') === '1';
$lb_show_counter     = get_setting('lightbox_show_counter', '1') === '1';
$lb_show_slideshow   = get_setting('lightbox_show_slideshow', '1') === '1';
$lb_show_zoom        = get_setting('lightbox_show_zoom', '1') === '1';
$lb_show_fullscreen  = get_setting('lightbox_show_fullscreen', '1') === '1';
$lb_show_exif        = get_setting('lightbox_show_exif', '1') === '1';
?>
<?php if ($enable_lightbox): ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.3.0/css/glightbox.min.css" />
<style>
/* GLightbox Belgesel Toolbar & Gelişmiş Kontroller (B4) */
.gcustom-toolbar {
    position: fixed;
    top: 18px;
    left: 20px;
    right: 70px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    pointer-events: none;
    z-index: 9999999;
}
.gcustom-toolbar > * {
    pointer-events: auto;
}
.gcustom-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.gcustom-right {
    display: flex;
    align-items: center;
    gap: 8px;
}
.gcustom-pill {
    background: rgba(15, 23, 42, 0.78);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #f8fafc;
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.04em;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.35);
    user-select: none;
}
.gcustom-exif-pill {
    font-family: var(--font-mono, monospace);
    font-size: 11px;
    color: #cbd5e1;
    max-width: 480px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.gcustom-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.78);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #f8fafc;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.18s ease;
    box-shadow: 0 4px 16px rgba(0,0,0,0.35);
    padding: 0;
}
.gcustom-btn:hover {
    background: rgba(30, 41, 59, 0.95);
    border-color: rgba(255, 255, 255, 0.4);
    transform: scale(1.06);
    color: #ffffff;
}
.gcustom-btn.active {
    background: #2563eb;
    border-color: #60a5fa;
    color: #ffffff;
}
@media (max-width: 768px) {
    .gcustom-toolbar {
        top: 12px;
        left: 12px;
        right: 60px;
    }
    .gcustom-exif-pill {
        display: none !important;
    }
    .gcustom-btn {
        width: 32px;
        height: 32px;
    }
}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.3.0/js/glightbox.min.js"></script>
<?php endif; ?>
<script>
    // Scroll to top button visibility
    (function() {
        var btn = document.getElementById('scroll-to-top');
        if (btn) {
            window.addEventListener('scroll', function() {
                btn.classList.toggle('visible', window.scrollY > 300);
            });
        }
    })();

    // GLightbox Modal Setup & Gelişmiş Kontroller (B4)
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($enable_lightbox): ?>
        if (typeof GLightbox !== 'undefined') {
            var myLightbox = GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: true,
                zoomable: true,
                draggable: true,
                autoplayVideos: false,
                descPosition: 'bottom'
            });
            window.erLightbox = myLightbox;

            // Localized button names, and keyboard focus moves into the dialog
            myLightbox.on('open', function() {
                var c = document.querySelector('.glightbox-container');
                if (!c) return;
                c.setAttribute('aria-modal', 'true');
                var names = { '.gclose': <?= json_encode(site_t('Kapat', 'Close')) ?>, '.gprev': <?= json_encode(site_t('Önceki fotoğraf', 'Previous photo')) ?>, '.gnext': <?= json_encode(site_t('Sonraki fotoğraf', 'Next photo')) ?> };
                Object.keys(names).forEach(function(sel) {
                    var b = c.querySelector(sel);
                    if (b) b.setAttribute('aria-label', names[sel]);
                });
                var x = c.querySelector('.gclose');
                if (x) x.focus({ preventScroll: true });
            });

            var lbCfg = {
                counter: <?= json_encode($lb_show_counter) ?>,
                slideshow: <?= json_encode($lb_show_slideshow) ?>,
                zoom: <?= json_encode($lb_show_zoom) ?>,
                fullscreen: <?= json_encode($lb_show_fullscreen) ?>,
                exif: <?= json_encode($lb_show_exif) ?>
            };

            var slideshowTimer = null;
            var isPlaying = false;

            function stopSlideshow() {
                if (slideshowTimer) {
                    clearInterval(slideshowTimer);
                    slideshowTimer = null;
                }
                isPlaying = false;
                var btn = document.getElementById('gcustomSlideshowBtn');
                if (btn) {
                    btn.classList.remove('active');
                    btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>';
                }
            }

            function startSlideshow() {
                stopSlideshow();
                isPlaying = true;
                var btn = document.getElementById('gcustomSlideshowBtn');
                if (btn) {
                    btn.classList.add('active');
                    btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>';
                }
                slideshowTimer = setInterval(function() {
                    if (myLightbox) myLightbox.next();
                }, 3500);
            }

            function updateLightboxToolbar() {
                var container = document.querySelector('.glightbox-container');
                if (!container) return;

                var toolbar = container.querySelector('.gcustom-toolbar');
                if (!toolbar) {
                    toolbar = document.createElement('div');
                    toolbar.className = 'gcustom-toolbar';
                    var leftHtml = '<div class="gcustom-left">';
                    if (lbCfg.counter) {
                        leftHtml += '<div class="gcustom-pill gcustom-counter" id="gcustomCounter">01 / 01</div>';
                    }
                    if (lbCfg.exif) {
                        leftHtml += '<div class="gcustom-pill gcustom-exif-pill" id="gcustomExif" style="display:none;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="flex-shrink:0;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg><span id="gcustomExifText"></span></div>';
                    }
                    leftHtml += '</div>';

                    var rightHtml = '<div class="gcustom-right">';
                    if (lbCfg.slideshow) {
                        rightHtml += '<button type="button" class="gcustom-btn" id="gcustomSlideshowBtn" title="<?= site_t('Otomatik Slayt Gösterisi', 'Autoplay Slideshow') ?>" aria-label="<?= site_t('Otomatik Slayt Gösterisi', 'Autoplay Slideshow') ?>"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></button>';
                    }
                    if (lbCfg.zoom) {
                        rightHtml += '<button type="button" class="gcustom-btn" id="gcustomZoomBtn" title="<?= site_t('Yakınlaştır / Uzaklaştır', 'Zoom In / Out') ?>" aria-label="<?= site_t('Yakınlaştır / Uzaklaştır', 'Zoom In / Out') ?>"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg></button>';
                    }
                    if (lbCfg.fullscreen) {
                        rightHtml += '<button type="button" class="gcustom-btn" id="gcustomFullscreenBtn" title="<?= site_t('Tam Ekran', 'Fullscreen') ?>" aria-label="<?= site_t('Tam Ekran', 'Fullscreen') ?>"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg></button>';
                    }
                    rightHtml += '</div>';

                    toolbar.innerHTML = leftHtml + rightHtml;
                    container.appendChild(toolbar);

                    var ssBtn = toolbar.querySelector('#gcustomSlideshowBtn');
                    if (ssBtn) {
                        ssBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            if (isPlaying) stopSlideshow(); else startSlideshow();
                        });
                    }
                    var zmBtn = toolbar.querySelector('#gcustomZoomBtn');
                    if (zmBtn) {
                        zmBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            var curImg = container.querySelector('.gslide.current .gslide-image img');
                            if (curImg) curImg.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
                        });
                    }
                    var fsBtn = toolbar.querySelector('#gcustomFullscreenBtn');
                    if (fsBtn) {
                        fsBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            if (!document.fullscreenElement) {
                                (container.requestFullscreen || container.webkitRequestFullscreen || document.documentElement.requestFullscreen).call(container);
                            } else {
                                if (document.exitFullscreen) document.exitFullscreen();
                            }
                        });
                    }
                }

                var total = myLightbox.elements ? myLightbox.elements.length : 1;
                var current = (myLightbox.index !== undefined ? myLightbox.index : 0) + 1;
                var counterEl = toolbar.querySelector('#gcustomCounter');
                if (counterEl) {
                    counterEl.textContent = String(current).padStart(2, '0') + ' / ' + String(total).padStart(2, '0');
                }

                var exifEl = toolbar.querySelector('#gcustomExif');
                var exifTextEl = toolbar.querySelector('#gcustomExifText');
                if (exifEl && exifTextEl && myLightbox.elements && myLightbox.elements[myLightbox.index]) {
                    var elNode = myLightbox.elements[myLightbox.index].node;
                    var exifData = elNode ? elNode.getAttribute('data-exif') : '';
                    if (exifData) {
                        exifTextEl.textContent = exifData;
                        exifEl.style.display = 'inline-flex';
                    } else {
                        exifEl.style.display = 'none';
                    }
                }
            }

            myLightbox.on('open', function() {
                updateLightboxToolbar();
                if (typeof gtag === 'function') {
                    gtag('event', 'photo_zoom', {
                        'event_category': 'Engagement',
                        'event_label': window.location.pathname
                    });
                }
            });

            myLightbox.on('slide_after_change', function() {
                updateLightboxToolbar();
            });

            myLightbox.on('close', function() {
                stopSlideshow();
                if (document.fullscreenElement && document.exitFullscreen) {
                    try { document.exitFullscreen(); } catch(e) {}
                }
            });

            // + Büyüteç İkonuyla Tıklayınca Kesin Büyütme / Küçültme ve Sürükleme Desteği
            var isZoomDragging = false;
            var dragMoved = false;
            var startX = 0, startY = 0, transX = 0, transY = 0;

            document.addEventListener('mousedown', function(e) {
                var img = e.target.closest('.glightbox-container .gslide.zoomed .gslide-image img');
                if (!img) return;
                isZoomDragging = true;
                dragMoved = false;
                startX = e.clientX - transX;
                startY = e.clientY - transY;
                img.classList.add('dragging');
                e.preventDefault();
            });

            document.addEventListener('mousemove', function(e) {
                if (!isZoomDragging) return;
                var img = document.querySelector('.glightbox-container .gslide.current.zoomed .gslide-image img');
                if (!img) return;
                var dx = e.clientX - startX;
                var dy = e.clientY - startY;
                if (Math.abs(dx - transX) > 4 || Math.abs(dy - transY) > 4) {
                    dragMoved = true;
                }
                transX = dx;
                transY = dy;
                img.style.transform = 'translate3d(' + transX + 'px, ' + transY + 'px, 0)';
            });

            document.addEventListener('mouseup', function() {
                if (!isZoomDragging) return;
                isZoomDragging = false;
                var img = document.querySelector('.glightbox-container .gslide.current .gslide-image img');
                if (img) img.classList.remove('dragging');
            });

            document.addEventListener('click', function(e) {
                var img = e.target.closest('.glightbox-container .gslide-image img');
                if (!img) return;
                if (dragMoved) {
                    dragMoved = false;
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    return;
                }
                var slide = img.closest('.gslide');
                if (!slide) return;
                e.stopImmediatePropagation();
                e.preventDefault();
                var willZoom = !slide.classList.contains('zoomed');
                if (willZoom) {
                    slide.classList.add('zoomed');
                    transX = 0;
                    transY = 0;
                    var targetW = Math.max(window.innerWidth * 1.65, (img.naturalWidth || 1800) * 1.35, 1920);
                    img.style.maxWidth = 'none';
                    img.style.maxHeight = 'none';
                    img.style.width = Math.round(targetW) + 'px';
                    img.style.height = 'auto';
                    img.style.transform = 'translate3d(0px, 0px, 0)';
                } else {
                    slide.classList.remove('zoomed');
                    transX = 0;
                    transY = 0;
                    img.style.width = '';
                    img.style.height = '';
                    img.style.maxWidth = '';
                    img.style.maxHeight = '';
                    img.style.transform = '';
                }
            }, true);

            myLightbox.on('slide_before_change', function() {
                transX = 0;
                transY = 0;
                document.querySelectorAll('.glightbox-container .gslide.zoomed').forEach(function(s) {
                    s.classList.remove('zoomed');
                    var im = s.querySelector('.gslide-image img');
                    if (im) {
                        im.style.width = '';
                        im.style.height = '';
                        im.style.maxWidth = '';
                        im.style.maxHeight = '';
                        im.style.transform = '';
                    }
                });
            });
        }
        <?php endif; ?>

        // Hikaye Tamamlama Takibi (%80 scroll derinliği)
        var storyCompleted = false;
        window.addEventListener('scroll', function() {
            if (!storyCompleted && (window.innerHeight + window.scrollY) >= (document.body.offsetHeight * 0.8)) {
                storyCompleted = true;
                if (typeof gtag === 'function') {
                    gtag('event', 'story_complete', {
                        'event_category': 'Engagement',
                        'event_label': window.location.pathname
                    });
                }
            }
        }, { passive: true });

        // İletişim / E-posta Tıklaması Takibi
        document.querySelectorAll('a[href^="mailto:"]').forEach(function(a) {
            a.addEventListener('click', function() {
                if (typeof gtag === 'function') {
                    gtag('event', 'contact_click', {
                        'event_category': 'Conversion',
                        'event_label': this.href
                    });
                }
            });
        });
    });

    // Language active button pill styling
    (function() {
        var isDark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('.lang-toggle-option[data-active="true"]').forEach(function(el) {
            el.style.setProperty('background-color', isDark ? '#fff' : '#111', 'important');
            el.style.setProperty('color', isDark ? '#111' : '#fff', 'important');
            el.style.setProperty('box-shadow', '0 1px 2px rgba(0,0,0,0.2)', 'important');
        });
    })();

    // Görsel Koruma & Telif Güvenliği (Right-Click & Drag Protection with Editorial Toast) (A1)
    <?php if (get_setting('image_protection_enabled', '1') === '1'): ?>
    (function() {
        var toastTimer = null;

        function showCopyrightToast() {
            var existing = document.getElementById('editorial-copyright-toast');
            if (existing) existing.remove();
            if (toastTimer) clearTimeout(toastTimer);

            var toast = document.createElement('div');
            toast.id = 'editorial-copyright-toast';
            toast.className = 'editorial-copyright-toast';
            toast.setAttribute('role', 'alert');

            var noticeText = <?= json_encode(str_replace('{owner}', e(site_owner_name()), site_t('© {owner} — Tüm hakları saklıdır. Editoryal lisanslama, yüksek çözünürlüklü basın ve sergi hakları için lütfen iletişime geçiniz.', '© {owner} — All rights reserved. For editorial licensing, high-res press wire access, and exhibition rights, please contact.')), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

            function escapeToastText(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }
            var licenseEmail = <?= json_encode((string)$contact_email, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            var contactText = <?= json_encode(site_t('Lisans Talebi İlet', 'Inquire Licensing'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

            toast.innerHTML = 
                '<div class="toast-inner">' +
                    '<span class="toast-icon">©</span>' +
                    '<div class="toast-body">' +
                        '<p class="toast-text">' + noticeText + '</p>' +
                        (licenseEmail ? '<div class="toast-actions">' +
                            '<a href="mailto:' + encodeURIComponent(licenseEmail).replace('%40', '@') + '?subject=' + encodeURIComponent(<?= json_encode(site_t('Editoryal Görsel Lisanslama Talebi', 'Editorial Image Licensing Inquiry'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>) + '" class="toast-action-btn">' +
                                '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>' +
                                '<span>' + contactText + ' (' + escapeToastText(licenseEmail) + ')</span>' +
                            '</a>' +
                        '</div>' : '') +
                    '</div>' +
                    '<button type="button" class="toast-close-btn" aria-label="Close" onclick="this.closest(\'#editorial-copyright-toast\').remove()">✕</button>' +
                '</div>';

            document.body.appendChild(toast);

            requestAnimationFrame(function() {
                toast.classList.add('visible');
            });

            if (typeof gtag === 'function') {
                gtag('event', 'copyright_notice_shown', {
                    'event_category': 'Security',
                    'event_label': window.location.pathname
                });
            }

            toastTimer = setTimeout(function() {
                if (toast && toast.parentNode) {
                    toast.classList.remove('visible');
                    setTimeout(function() { if (toast && toast.parentNode) toast.remove(); }, 300);
                }
            }, 4000);
        }

        document.addEventListener('contextmenu', function(e) {
            var target = e.target;
            if (target && (target.tagName === 'IMG' || target.closest('figure.photo-figure') || target.closest('.glightbox') || target.closest('.card-cover') || target.classList.contains('location-story-thumb'))) {
                e.preventDefault();
                showCopyrightToast();
            }
        });

        document.addEventListener('dragstart', function(e) {
            var target = e.target;
            if (target && target.tagName === 'IMG') {
                e.preventDefault();
                showCopyrightToast();
            }
        });
    })();
    <?php endif; ?>
</script>
<?php if (!isset($render_closing_html) || $render_closing_html !== false): ?>
</body>
</html>
<?php endif; ?>
