<?php
/**
 * includes/views/page.php
 * Proje, Seri ve Tekil Sayfa Detay Görünümü
 * Monografi Kitap Mizanpajı (Asimetrik Sergi Açılışı + Canlı Kadraj Sayacı + Kontak Baskı İndeksi)
 */

$is_tr = ($lang ?? 'en') === 'tr';
$item_type = $item['type'] ?? 'project';
$is_project = ($item_type === 'project');
$is_series = ($item_type === 'series');
$is_single = ($item_type === 'single');

// Zemin Teması (dark, white, paper)
$inner_surface = get_setting('inner_default_surface', 'dark');
$hp_surface = get_setting('hp_default_surface', 'dark');
if ($inner_surface === 'white' || $inner_surface === 'paper' || $hp_surface === 'white') {
    $surface_classes = 'light gallery-white';
} else {
    $surface_classes = 'dark';
}

// HTML Class (Projeler, Seriler, Tekil ve ileride eklenecek tüm yeni portfolyo kategorileri için ortak monografi mimarisi)
if ($is_project) {
    $html_class = $surface_classes . ' project-split-page mono-inner-page';
} elseif ($is_series) {
    $html_class = $surface_classes . ' project-split-page series-page mono-inner-page';
} elseif ($is_single) {
    $html_class = $surface_classes . ' project-split-page series-page single-inner-page mono-inner-page';
} else {
    $html_class = $surface_classes . ' project-split-page series-page mono-inner-page category-' . preg_replace('/[^a-z0-9_-]/i', '', $item_type);
}

$page_title = item_field($item, 'title', $lang);
$page_desc = item_field($item, 'description', $lang);
$raw_content = item_field($item, 'content', $lang);

// Open Graph görseli
$og_image = '';
if (!empty($item['cover_image'])) {
    $og_image = url($item['cover_image']);
}

// Karşılıklı dil rotaları
$alternate_en_url = item_url($item, 'en');
$alternate_tr_url = item_url($item, 'tr');

require __DIR__ . '/layout_header.php';

// Kategori ve etiketler (categories tablosundan dinamik olarak çekilir)
$dyn_cat = null;
try {
    $cat_st = $pdo->prepare("SELECT * FROM `categories` WHERE `key_slug` = ? LIMIT 1");
    $cat_st->execute([$item_type]);
    $dyn_cat = $cat_st->fetch(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

if ($dyn_cat) {
    $category_name = item_field($dyn_cat, 'name', $lang);
    $cat_slug = !empty($dyn_cat['slug_' . $lang]) ? $dyn_cat['slug_' . $lang] : ($is_tr ? ($dyn_cat['slug_tr'] ?: $dyn_cat['slug_en']) : ($dyn_cat['slug_en'] ?: $dyn_cat['slug_tr']));
    $category_url = url($lang . '/' . $cat_slug . '/');
} elseif ($item_type === 'blog') {
    $category_name = get_page_custom_title('blog', $lang, 'Blog');
    $category_url = url($lang . '/' . get_static_slug('blog', $lang) . '/');
} elseif ($is_project) {
    $category_name = get_page_custom_title('projects', $lang, site_t('Projeler', 'Projects'));
    $category_url = url($lang . '/' . get_static_slug('projects', $lang) . '/');
} elseif ($is_series) {
    $category_name = get_page_custom_title('series', $lang, site_t('Seriler', 'Series'));
    $category_url = url($lang . '/' . get_static_slug('series', $lang) . '/');
} elseif ($is_single) {
    $category_name = get_page_custom_title('single', $lang, site_t('Tekil', 'Single'));
    $category_url = url($lang . '/' . get_static_slug('single', $lang) . '/');
} else {
    $category_name = ucfirst($item_type);
    $category_url = url($lang . '/' . $item_type . '/');
}

if ($item_type === 'blog') {
    $prev_label = site_t('ÖNCEKİ YAZI', 'PREVIOUS ARTICLE');
    $next_label = site_t('SIRADAKİ YAZI', 'NEXT ARTICLE');
} elseif ($is_project) {
    $prev_label = site_t('ÖNCEKİ PROJE', 'PREVIOUS PROJECT');
    $next_label = site_t('SIRADAKİ PROJE', 'NEXT PROJECT');
} elseif ($is_series) {
    $prev_label = site_t('ÖNCEKİ SERİ', 'PREVIOUS SERIES');
    $next_label = site_t('SIRADAKİ SERİ', 'NEXT SERIES');
} elseif ($is_single) {
    $prev_label = site_t('ÖNCEKİ TEKİL', 'PREVIOUS SINGLE');
    $next_label = site_t('SIRADAKİ TEKİL', 'NEXT SINGLE');
} else {
    $prev_label = site_t('ÖNCEKİ ÇALIŞMA', 'PREVIOUS WORK');
    $next_label = site_t('SIRADAKİ ÇALIŞMA', 'NEXT WORK');
}

$lead_intro_html = '';
$is_structured = false;
$parsed_json = null;
if (!empty($raw_content) && strpos(trim($raw_content), '{') === 0) {
    $parsed_json = json_decode(trim($raw_content), true);
    if (is_array($parsed_json) && (isset($parsed_json['intro']) || isset($parsed_json['blocks']))) {
        $is_structured = true;
        if (!empty($parsed_json['intro'])) {
            $lead_intro_html = format_editorial_rich_text(trim($parsed_json['intro']));
        }
    }
}

if ($is_structured) {
    $blocks_copy = $parsed_json;
    unset($blocks_copy['intro']);
    $rendered_markdown = render_structured_blocks($blocks_copy);
} else {
    $rendered_markdown = render_item_content($raw_content);
}

$item_loc = item_field($item, 'location', $lang);
$item_year = !empty($item['year']) ? $item['year'] : (!empty($item['date']) ? date('Y', strtotime($item['date'])) : '');
$fallback_cover_html = !empty($item['cover_image']) ? get_responsive_image_html($item['cover_image'], $page_title, '', true, $item['cover_focal'] ?? '50% 50%') : '';

// ── İç Sayfa Tasarım & Mizanpaj Ayarları (CMS Customizer) ──
$inner_opening_layout       = get_setting('inner_opening_layout', 'spread_right');
$inner_spread_visual_width  = max(40, min(65, (int)get_setting('inner_spread_visual_width', '55')));
$inner_text_wrap_mode       = get_setting('inner_text_wrap_mode', 'wrap');
$inner_title_size           = get_setting('inner_title_size', 'normal');
$inner_intro_columns        = get_setting('inner_intro_columns', '1');
$inner_intro_dropcap        = (get_setting('inner_intro_dropcap', '0') === '1');
$inner_header_border        = (get_setting('inner_header_border', '1') === '1');

$inner_default_view         = get_setting('inner_default_view', 'stream');
$inner_stream_layout        = get_setting('inner_stream_layout', 'editorial_single');
$inner_container_width      = get_setting('inner_container_width', 'standard');
$inner_photo_gap            = get_setting('inner_photo_gap', 'normal');
$inner_photo_frame_style    = get_setting('inner_photo_frame_style', 'none');
$inner_contact_sheet_cols   = max(3, min(6, (int)get_setting('inner_contact_sheet_cols', '4')));
$inner_caption_style        = get_setting('inner_caption_style', 'below_left');

$inner_show_view_switcher   = (get_setting('inner_show_view_switcher', '1') === '1');
$inner_show_surface_switcher= (get_setting('inner_show_surface_switcher', '1') === '1');
$inner_show_frame_badges    = (get_setting('inner_show_frame_badges', '1') === '1');
$inner_badge_position       = get_setting('inner_badge_position', 'bottom_right');
$inner_show_right_tracker   = (get_setting('inner_show_right_tracker', '1') === '1');
$inner_show_back_category   = (get_setting('inner_show_back_category', '1') === '1') && ($item['type'] ?? '') !== 'page'; // bağımsız sayfanın listesi yok
$inner_show_location_year   = (get_setting('inner_show_location_year', '1') === '1');
$inner_enable_lightbox      = (get_setting('inner_enable_lightbox', '1') === '1');

$inner_show_share_bar       = (get_setting('inner_show_share_bar', '1') === '1');
$inner_show_keyboard_hints  = (get_setting('inner_show_keyboard_hints', '1') === '1');
$inner_show_prev_next       = (get_setting('inner_show_prev_next', '1') === '1') && ($item['type'] ?? '') !== 'page';
$inner_prev_next_style      = get_setting('inner_prev_next_style', 'image_cards');

$wrapper_classes = [
    'project-split-wrapper',
    'mono-inner-wrapper',
    'inner-opening-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_opening_layout),
    'inner-wrapmode-' . ($inner_text_wrap_mode === 'columns' ? 'columns' : 'wrap'),
    'inner-title-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_title_size),
    'inner-intro-cols-' . ($inner_intro_columns === '2' ? '2' : '1'),
    'inner-stream-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_stream_layout),
    'inner-width-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_container_width),
    'inner-gap-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_photo_gap),
    'inner-frame-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_photo_frame_style),
    'inner-caption-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_caption_style),
    'inner-badge-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_badge_position),
    'inner-prevnext-' . preg_replace('/[^a-z0-9_-]/i', '', $inner_prev_next_style),
];
if ($inner_intro_dropcap) $wrapper_classes[] = 'inner-has-dropcap';
if (!$inner_header_border) $wrapper_classes[] = 'inner-no-header-border';
if (!$inner_show_frame_badges) $wrapper_classes[] = 'inner-hide-badges';
if ($inner_default_view === 'grid') $wrapper_classes[] = 'is-contact-sheet';

$wrapper_style_vars = sprintf(
    '--inner-visual-width:%d%%; --inner-cs-cols:%d;',
    $inner_spread_visual_width,
    $inner_contact_sheet_cols
);
?>

<?php render_site_navigation($lang, $en_path, $tr_path, $instagram_url); ?>

<!-- ── SAĞ SABİT MONOGRAFİ KADRAJ İZLEYİCİ (KAPAKLARDAKİ TRACKER İLE AYNI DİL) ── -->
<?php if ($inner_show_right_tracker): ?>
<aside class="mono-frame-tracker" id="mono-frame-tracker" aria-label="<?= site_t('Sergi İlerlemesi', 'Exhibition Progress') ?>">
    <div class="mono-frame-counter">
        <span class="mono-frame-current" id="mono-frame-current">01</span>
        <span class="mono-frame-sep">/</span>
        <span class="mono-frame-total" id="mono-frame-total">--</span>
    </div>
    <div class="mono-frame-progress-track">
        <div class="mono-frame-progress-bar" id="mono-frame-progress-bar"></div>
    </div>
    <button type="button" class="mono-frame-grid-btn <?= $inner_default_view === 'grid' ? 'active' : '' ?>" id="mono-tracker-grid-btn" title="<?= site_t('Kontak Baskı / İndeks Görünümü (G)', 'Contact Sheet / Index View (G)') ?>" aria-label="Toggle Contact Sheet">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    </button>
</aside>
<?php endif; ?>

<div class="<?= e(implode(' ', $wrapper_classes)) ?>" id="mono-inner-wrapper" role="main" style="<?= e($wrapper_style_vars) ?>">
    <section class="project-split-dark" id="story-start">
        <div class="project-dark-inner">

            <div class="mono-inner-main-flow" id="mono-inner-main-flow">
                <!-- ── 1. İÇ SAYFA AÇILIŞ FORMASI (8 FARKLI MİZANPAJ MODU DESTEKLİ) ── -->
                <header class="mono-exhibition-spread" id="mono-exhibition-spread">
                    <!-- Açılış Fotoğrafı / Videosu Slotu -->
                    <div class="mono-spread-visual" id="project-lead-visual-slot" data-fallback-cover="<?= e($fallback_cover_html) ?>"></div>

                    <div class="mono-spread-editorial" id="mono-spread-editorial">
                        <div class="mono-spread-head-block" id="mono-spread-head-block">
                            <?php if ($inner_show_back_category || $inner_show_location_year): ?>
                            <div class="mono-spread-top-meta">
                                <?php if ($inner_show_back_category): ?>
                                <a href="<?= e($category_url) ?>" class="mono-back-category">
                                    <span>←</span> <?= e($category_name) ?>
                                </a>
                                <?php endif; ?>
                                <?php
                                if ($inner_show_location_year):
                                    $meta_parts = [];
                                    if (!empty($item_loc)) $meta_parts[] = e($item_loc);
                                    if (!empty($item_year)) $meta_parts[] = e($item_year);
                                    if (!empty($meta_parts)):
                                ?>
                                    <span class="mono-chapter-location"><?= implode(' • ', $meta_parts) ?></span>
                                <?php
                                    endif;
                                endif;
                                ?>
                            </div>
                            <?php endif; ?>

                            <h1 class="mono-story-title"><?= e($page_title) ?></h1>
                        </div>

                        <?php if ($inner_show_view_switcher || $inner_show_surface_switcher): ?>
                        <!-- Görünüm & Zemin Değiştirici: (Sergi Akışı / Kontak Baskı) + (Karanlık Oda / Kitap Kağıdı) -->
                        <div class="mono-view-bar" id="mono-view-bar">
                            <?php if ($inner_show_view_switcher): ?>
                            <div class="mono-view-switcher" role="tablist" aria-label="<?= site_t('Görünüm Modu', 'View Mode') ?>">
                                <button type="button" class="mono-view-btn <?= $inner_default_view !== 'grid' ? 'active' : '' ?>" data-view="stream" id="btn-view-stream">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="16" rx="1"/></svg>
                                    <span><?= site_t('Sergi Akışı', 'Exhibition') ?></span>
                                </button>
                                <button type="button" class="mono-view-btn <?= $inner_default_view === 'grid' ? 'active' : '' ?>" data-view="grid" id="btn-view-grid">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                    <span><?= site_t('Kontak Baskı', 'Contact Sheet') ?></span>
                                    <span class="mono-view-count" id="project-photo-count"></span>
                                </button>
                            </div>
                            <?php endif; ?>

                            <?php if ($inner_show_surface_switcher): ?>
                            <div class="mono-view-switcher mono-surface-switcher" role="group" aria-label="<?= site_t('Tema Modu', 'Theme Mode') ?>">
                                <button type="button" class="mono-view-btn active" data-surface-btn="dark" onclick="window.setSurfaceTheme && window.setSurfaceTheme('dark')" title="<?= site_t('Karanlık Mod', 'Dark Mode') ?>" aria-label="<?= site_t('Karanlık Mod', 'Dark Mode') ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                                </button>
                                <button type="button" class="mono-view-btn" data-surface-btn="white" onclick="window.setSurfaceTheme && window.setSurfaceTheme('white')" title="<?= site_t('Saf Beyaz Mod', 'Pure White Mode') ?>" aria-label="<?= site_t('Saf Beyaz Mod', 'Pure White Mode') ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Küratöryel Giriş Metni -->
                        <div id="project-intro-slot" class="project-lead-narrative"><?= $lead_intro_html ?></div>
                    </div>
                </header>

                <!-- ── 2. SERGİ & KONTAK BASKI GALERİ AKIŞI ── -->
                <div id="project-gallery-slot" class="project-gallery-content"></div>
            </div>

            <div id="project-raw-stream" style="display:none;">
                <?= $rendered_markdown ?>
            </div>

            <!-- ── 3. KÜNYE & PAYLAŞIM ÇUBUĞU (COLOPHON BAR - HİKAYE SONU) ── -->
            <?php if ($inner_show_share_bar || $inner_show_keyboard_hints): ?>
            <div class="project-actions-row project-colophon-bar">
                <?php if ($inner_show_share_bar): ?>
                <div class="project-share-wrap">
                    <span class="colophon-share-label"><?= site_t('PAYLAŞ', 'SHARE') ?></span>
                    <div class="share-buttons">
                        <a class="share-btn share-x"
                           href="https://x.com/intent/tweet?url=<?= urlencode($canonical_url) ?>&text=<?= urlencode($page_title) ?>"
                           target="_blank" rel="noopener noreferrer" aria-label="Share on X">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.264 5.632 5.9-5.632Zm-1.161 17.52h1.833L7.084 4.126H5.117Z"/></svg>
                        </a>
                        <a class="share-btn share-facebook"
                           href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonical_url) ?>"
                           target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a class="share-btn share-whatsapp"
                           href="https://wa.me/?text=<?= urlencode($page_title . ' ' . $canonical_url) ?>"
                           target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                        </a>
                        <button class="share-btn share-copy" onclick="navigator.clipboard.writeText('<?= e($canonical_url) ?>').then(() => { this.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'15\' height=\'15\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><polyline points=\'20 6 9 17 4 12\'></polyline></svg>'; setTimeout(() => { this.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'15\' height=\'15\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><rect x=\'9\' y=\'9\' width=\'13\' height=\'13\' rx=\'2\'/><path d=\'M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1\'/></svg>'; }, 2000) })" aria-label="Copy link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($inner_show_keyboard_hints): ?>
                <div class="curator-keyboard-hint" id="curator-keyboard-hint" aria-label="<?= e(site_t('Küratör Gezinme Kısayolları', 'Curator Navigation Shortcuts')) ?>">
                    <div class="curator-hint-content">
                        <span class="curator-hint-key"><kbd>←</kbd><kbd>→</kbd> <?= site_t('Gezin', 'Navigate') ?></span>
                        <span class="curator-hint-sep">•</span>
                        <button type="button" class="curator-hint-action-btn" onclick="window.toggleContactSheet && window.toggleContactSheet()" title="<?= site_t('Kontak Baskı Görünümü', 'Contact Sheet View') ?>">
                            <kbd>G</kbd> <?= site_t('İndeks', 'Index') ?>
                        </button>
                        <span class="curator-hint-sep">•</span>
                        <button type="button" class="curator-hint-action-btn" onclick="window.toggleSurfaceTheme && window.toggleSurfaceTheme()" title="<?= site_t('Karanlık / Aydınlık Tema', 'Dark / Light Theme') ?>">
                            <kbd>T</kbd> <?= site_t('Tema', 'Theme') ?>
                        </button>
                        <span class="curator-hint-sep">•</span>
                        <button type="button" class="curator-hint-action-btn" onclick="window.print()" title="<?= site_t('PDF Basın Kiti İndir', 'Download PDF Press Kit') ?>">
                            <kbd>P</kbd> <?= site_t('Basın Kiti', 'Press Kit') ?>
                        </button>
                        <span class="curator-hint-sep">•</span>
                        <button type="button" class="curator-hint-action-btn" onclick="document.fullscreenElement ? document.exitFullscreen().catch(function(){}) : document.documentElement.requestFullscreen().catch(function(){})" title="<?= site_t('Tam Ekran', 'Fullscreen') ?>">
                            <kbd>F</kbd> <?= site_t('Tam Ekran', 'Fullscreen') ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Sayfa Navigasyonu (Önceki / Sonraki Hikaye) -->
            <?php if ($inner_show_prev_next && (!empty($prev_item) || !empty($next_item))): ?>
                <nav class="project-story-nav" aria-label="<?= e(site_t('Gezinme', 'Navigation')) ?>">
                    <?php if (!empty($prev_item)): 
                        $p_title = item_field($prev_item, 'title', $lang);
                        $p_url = item_url($prev_item, $lang);
                        $p_thumb = $prev_item['cover_image'] ? ltrim($prev_item['cover_image'], '/') : '';
                        $p_full = resolve_image_file_path($p_thumb);
                        $p_thumb_url = $p_full ? (resize_and_convert_image($p_full, 480, 'avif', 80) ?: url($prev_item['cover_image'])) : url($prev_item['cover_image']);
                    ?>
                        <a class="story-nav-card story-nav-prev" href="<?= e($p_url) ?>">
                            <div class="story-nav-media">
                                <?php if ($prev_item['cover_image']): ?>
                                    <img class="story-nav-thumb" src="<?= e(url($p_thumb_url)) ?>" alt="<?= e($p_title) ?>" loading="lazy" />
                                <?php endif; ?>
                                <div class="story-nav-scrim"></div>
                            </div>
                            <div class="story-nav-content">
                                <span class="story-nav-eyebrow">
                                    <span class="story-nav-arrow">←</span>
                                    <span><?= e($prev_label) ?></span>
                                </span>
                                <h4 class="story-nav-title"><?= e($p_title) ?></h4>
                                <?php 
                                    $p_loc = item_field($prev_item, 'location', $lang);
                                    if (!empty($prev_item['year']) || !empty($p_loc)): 
                                ?>
                                    <div class="story-nav-meta">
                                        <?php if (!empty($prev_item['year'])): ?><span><?= e($prev_item['year']) ?></span><?php endif; ?>
                                        <?php if (!empty($prev_item['year']) && !empty($p_loc)): ?><span class="story-nav-sep">•</span><?php endif; ?>
                                        <?php if (!empty($p_loc)): ?><span><?= e($p_loc) ?></span><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php else: ?>
                        <div class="story-nav-empty"></div>
                    <?php endif; ?>

                    <?php if (!empty($next_item)): 
                        $n_title = item_field($next_item, 'title', $lang);
                        $n_url = item_url($next_item, $lang);
                        $n_thumb = $next_item['cover_image'] ? ltrim($next_item['cover_image'], '/') : '';
                        $n_full = resolve_image_file_path($n_thumb);
                        $n_thumb_url = $n_full ? (resize_and_convert_image($n_full, 480, 'avif', 80) ?: url($next_item['cover_image'])) : url($next_item['cover_image']);
                    ?>
                        <a class="story-nav-card story-nav-next" href="<?= e($n_url) ?>">
                            <div class="story-nav-media">
                                <?php if ($next_item['cover_image']): ?>
                                    <img class="story-nav-thumb" src="<?= e(url($n_thumb_url)) ?>" alt="<?= e($n_title) ?>" loading="lazy" />
                                <?php endif; ?>
                                <div class="story-nav-scrim"></div>
                            </div>
                            <div class="story-nav-content">
                                <span class="story-nav-eyebrow">
                                    <span><?= e($next_label) ?></span>
                                    <span class="story-nav-arrow">→</span>
                                </span>
                                <h4 class="story-nav-title"><?= e($n_title) ?></h4>
                                <?php 
                                    $n_loc = item_field($next_item, 'location', $lang);
                                    if (!empty($next_item['year']) || !empty($n_loc)): 
                                ?>
                                    <div class="story-nav-meta">
                                        <?php if (!empty($next_item['year'])): ?><span><?= e($next_item['year']) ?></span><?php endif; ?>
                                        <?php if (!empty($next_item['year']) && !empty($n_loc)): ?><span class="story-nav-sep">•</span><?php endif; ?>
                                        <?php if (!empty($n_loc)): ?><span><?= e($n_loc) ?></span><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

            <!-- Alt Footer -->
            <?php 
            $render_closing_html = false;
            require __DIR__ . '/layout_footer.php'; 
            ?>
        </div>
    </section>
</div>

<!-- ── SCRIPT: Monografi Forma Açılışı, Kadraj Numaralandırma & Kontak Baskı Modu ── -->
<script>
(function() {
    var raw = document.getElementById("project-raw-stream");
    var introSlot = document.getElementById("project-intro-slot");
    var leadVisualSlot = document.getElementById("project-lead-visual-slot");
    var spreadHeader = document.getElementById("mono-exhibition-spread");
    var editorialBox = document.getElementById("mono-spread-editorial");
    var headBlock = document.getElementById("mono-spread-head-block");
    var gallerySlot = document.getElementById("project-gallery-slot");
    var wrapper = document.getElementById("mono-inner-wrapper");
    var openingLayout = <?= json_encode($inner_opening_layout) ?>;
    var enableLightbox = <?= json_encode($inner_enable_lightbox) ?>;
    if (!raw || !introSlot || !gallerySlot) return;

    var nodes = Array.from(raw.childNodes);
    var hitPhoto = (introSlot.children.length > 0);

    nodes.forEach(function(node) {
        if (node.nodeType === 3) {
            var txt = node.textContent.trim();
            if (txt.length > 0 && !hitPhoto) {
                var p = document.createElement("p");
                p.textContent = txt;
                introSlot.appendChild(p);
            }
            return;
        }

        if (node.nodeType !== 1) return;
        var el = node;

        var isMedia = el.classList.contains("photo-grid") ||
                      el.classList.contains("photo-row") ||
                      el.classList.contains("photo-figure") ||
                      el.classList.contains("video-container") ||
                      el.classList.contains("project-chapter") ||
                      el.classList.contains("chapter-dark") ||
                      el.classList.contains("chapter-white") ||
                      el.classList.contains("project-white-band") ||
                      el.classList.contains("project-closing-lead") ||
                      el.querySelector("figure.photo-figure") ||
                      el.querySelector("img.photo-figure-img") ||
                      el.querySelector("img") ||
                      el.querySelector("iframe") ||
                      el.tagName === "IFRAME" ||
                      el.querySelector("video") ||
                      el.tagName === "VIDEO" ||
                      el.tagName === "H2" ||
                      el.tagName === "H3";

        if (!hitPhoto && !isMedia) {
            introSlot.appendChild(el);
        } else {
            hitPhoto = true;
            gallerySlot.appendChild(el);
        }
    });

    raw.remove();

    // Giriş metni içinde sonda kalmış bölüm başlığı (H2/H3) veya video varsa galeri akışının başına taşı
    var introChildren = Array.from(introSlot.children);
    var toMove = [];
    introChildren.forEach(function(ch) {
        if (ch.tagName === "H2" || ch.tagName === "H3" || ch.tagName === "VIDEO" || ch.classList.contains("video-container") || ch.querySelector("video")) {
            toMove.push(ch);
        }
    });
    for (var i = toMove.length - 1; i >= 0; i--) {
        gallerySlot.insertBefore(toMove[i], gallerySlot.firstElementChild);
    }

    var hasIntro = (introSlot.textContent.trim().length > 0 || introSlot.children.length > 0);
    if (!hasIntro) {
        introSlot.style.display = "none";
    }

    var usesLeadVisual = ["spread_right", "spread_left", "title_photo_text", "cinema_hero", "photo_first", "white_cube"].indexOf(openingLayout) !== -1;
    var forceLeadEvenWithoutIntro = ["title_photo_text", "cinema_hero", "photo_first", "white_cube"].indexOf(openingLayout) !== -1;

    function populateLeadVisualSlot() {
        if (!leadVisualSlot) return false;
        var firstEl = gallerySlot.firstElementChild;
        if (firstEl && (firstEl.classList.contains("photo-figure") || firstEl.classList.contains("video-container"))) {
            if (firstEl.classList.contains("photo-figure")) {
                var cloneFirst = firstEl.cloneNode(true);
                cloneFirst.classList.add("contact-sheet-only-lead");
                var cloneLink = cloneFirst.querySelector("a.glightbox");
                var realLink = firstEl.querySelector("a.glightbox");
                if (cloneLink && realLink) {
                    cloneLink.classList.remove("glightbox");
                    cloneLink.removeAttribute("data-gallery");
                    cloneLink.classList.add("cs-proxy-lightbox");
                    cloneLink.addEventListener("click", function(ev) {
                        ev.preventDefault();
                        realLink.click();
                    });
                }
                leadVisualSlot.appendChild(firstEl);
                var nextEl = gallerySlot.firstElementChild;
                if (nextEl && (nextEl.tagName === "BLOCKQUOTE" || nextEl.tagName === "P")) {
                    leadVisualSlot.appendChild(nextEl);
                }
                gallerySlot.insertBefore(cloneFirst, gallerySlot.firstElementChild);
            } else {
                leadVisualSlot.appendChild(firstEl);
            }
            return true;
        } else {
            var fbHtml = leadVisualSlot.getAttribute("data-fallback-cover");
            if (fbHtml) {
                leadVisualSlot.innerHTML = fbHtml;
                var fbLink = leadVisualSlot.querySelector("a.glightbox");
                if (fbLink) {
                    var fbHref = fbLink.getAttribute("href");
                    var existingMatch = gallerySlot.querySelector('a.glightbox[href="' + fbHref + '"]');
                    if (existingMatch) {
                        fbLink.classList.remove("glightbox");
                        fbLink.removeAttribute("data-gallery");
                        fbLink.classList.add("cs-proxy-lightbox");
                        fbLink.addEventListener("click", function(ev) {
                            ev.preventDefault();
                            existingMatch.click();
                        });
                    }
                }
                return true;
            }
        }
        return false;
    }

    if (!usesLeadVisual) {
        // Önce Başlık & Yazı (editorial_stacked), Sabit Split (sticky_split) ve Müze (centered_museum) düzenlerinde
        // tüm fotoğraflar galeri akışında kalır.
        if (leadVisualSlot) leadVisualSlot.style.display = "none";
        if (!gallerySlot.querySelector("figure.photo-figure, .video-container, video, iframe") && leadVisualSlot) {
            var fbCoverOnly = leadVisualSlot.getAttribute("data-fallback-cover");
            if (fbCoverOnly) {
                gallerySlot.innerHTML = fbCoverOnly;
            }
        }
    } else if (!hasIntro && !forceLeadEvenWithoutIntro) {
        if (spreadHeader) spreadHeader.classList.add("is-compact-header");
        if (!gallerySlot.querySelector("figure.photo-figure, .video-container, video, iframe") && leadVisualSlot) {
            var fbCompact = leadVisualSlot.getAttribute("data-fallback-cover");
            if (fbCompact) {
                gallerySlot.innerHTML = fbCompact;
            }
        }
    } else if (leadVisualSlot && spreadHeader) {
        var hasPopulated = populateLeadVisualSlot();
        if (!hasPopulated) {
            leadVisualSlot.style.display = "none";
            spreadHeader.classList.add("is-compact-header");
        } else {
            if (openingLayout === "title_photo_text" && editorialBox) {
                // Başlık -> Görünüm Barı -> Açılış Fotoğrafı -> Giriş Metni
                editorialBox.insertBefore(leadVisualSlot, introSlot);
            } else if (openingLayout === "cinema_hero" && headBlock) {
                // Sinematik Hero: Fotoğraf üstünde Başlık & Künye, altında Görünüm Barı & Giriş Metni
                var heroStage = document.createElement("div");
                heroStage.className = "mono-cinema-hero-stage";
                var heroOverlay = document.createElement("div");
                heroOverlay.className = "mono-cinema-hero-overlay";
                spreadHeader.insertBefore(heroStage, spreadHeader.firstElementChild);
                heroStage.appendChild(leadVisualSlot);
                heroOverlay.appendChild(headBlock);
                heroStage.appendChild(heroOverlay);
            }
        }
    }

    if (!enableLightbox && wrapper) {
        Array.from(wrapper.querySelectorAll("a.glightbox, a.cs-proxy-lightbox")).forEach(function(link) {
            link.classList.remove("glightbox", "cs-proxy-lightbox");
            link.removeAttribute("data-gallery");
            link.style.cursor = "default";
            link.addEventListener("click", function(ev) { ev.preventDefault(); });
        });
    }

    // Tüm fotoğrafları numaralandır (01, 02, 03...) ve sağdaki canlı sayacı bağla
    var streamFigures = Array.from(document.querySelectorAll(".mono-spread-visual figure.photo-figure, .project-gallery-content figure.photo-figure:not(.contact-sheet-only-lead)"));
    var totalPhotos = streamFigures.length;
    var padNum = function(n) { return n < 10 ? "0" + n : "" + n; };

    streamFigures.forEach(function(fig, idx) {
        var numStr = padNum(idx + 1);
        fig.setAttribute("data-frame-num", numStr);
        fig.id = "frame-" + (idx + 1);

        var badge = document.createElement("span");
        badge.className = "mono-frame-badge";
        badge.textContent = numStr;
        fig.appendChild(badge);
    });

    var csLead = document.querySelector("figure.contact-sheet-only-lead");
    if (csLead) {
        csLead.setAttribute("data-frame-num", "01");
        var csBadge = document.createElement("span");
        csBadge.className = "mono-frame-badge";
        csBadge.textContent = "01";
        csLead.appendChild(csBadge);
    }

    var allFigures = streamFigures;

    var countEl = document.getElementById("project-photo-count");
    if (countEl && totalPhotos > 0) {
        countEl.textContent = totalPhotos;
    }

    var totalEl = document.getElementById("mono-frame-total");
    var currentEl = document.getElementById("mono-frame-current");
    var progressBar = document.getElementById("mono-frame-progress-bar");
    var tracker = document.getElementById("mono-frame-tracker");

    if (totalEl && totalPhotos > 0) {
        totalEl.textContent = padNum(totalPhotos);
    } else if (tracker && totalPhotos === 0) {
        tracker.style.display = "none";
    }

    // Scroll sırasında aktif fotoğrafı ve ilerleme çubuğunu güncelle
    if (allFigures.length > 0 && currentEl) {
        var fObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var n = entry.target.getAttribute("data-frame-num");
                    if (n) currentEl.textContent = n;
                }
            });
        }, { rootMargin: "-35% 0px -45% 0px", threshold: 0 });

        allFigures.forEach(function(fig) { fObserver.observe(fig); });

        window.addEventListener("scroll", function() {
            var docH = document.documentElement.scrollHeight - window.innerHeight;
            var pct = docH > 0 ? Math.min(100, Math.max(0, (window.scrollY / docH) * 100)) : 0;
            if (progressBar) progressBar.style.height = pct + "%";
        }, { passive: true });
    }

    // Sergi Akışı <-> Kontak Baskı (Contact Sheet) Geçişi
    var btnStream = document.getElementById("btn-view-stream");
    var btnGrid = document.getElementById("btn-view-grid");
    var btnTrackerGrid = document.getElementById("mono-tracker-grid-btn");

    window.setGalleryViewMode = function(mode) {
        if (!wrapper) return;
        var isGrid = (mode === "grid");
        wrapper.classList.toggle("is-contact-sheet", isGrid);
        if (btnStream) btnStream.classList.toggle("active", !isGrid);
        if (btnGrid) btnGrid.classList.toggle("active", isGrid);
        if (btnTrackerGrid) btnTrackerGrid.classList.toggle("active", isGrid);
    };

    window.toggleContactSheet = function() {
        if (!wrapper) return;
        var isGrid = wrapper.classList.contains("is-contact-sheet");
        window.setGalleryViewMode(isGrid ? "stream" : "grid");
    };

    if (btnStream) btnStream.addEventListener("click", function() { window.setGalleryViewMode("stream"); });
    if (btnGrid) btnGrid.addEventListener("click", function() { window.setGalleryViewMode("grid"); });
    if (btnTrackerGrid) btnTrackerGrid.addEventListener("click", function() { window.toggleContactSheet(); });

    // Yatay Panorama Akışı (inner-stream-horizontal_panorama) Dikey Tekerlek Çevrim Desteği
    if (wrapper.classList.contains("inner-stream-horizontal_panorama")) {
        var gSlot = document.getElementById("project-gallery-slot");
        if (gSlot) {
            gSlot.addEventListener("wheel", function(e) {
                if (wrapper.classList.contains("is-contact-sheet")) return;
                if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                    e.preventDefault();
                    gSlot.scrollLeft += e.deltaY;
                }
            }, { passive: false });
        }
    }
})();
</script>

<script>
// "Bir geniş, üç küçük" dizilişi: fotoğraflar ikili/üçlü satırlara dağıldığı için CSS sayacı tutmaz; sırayı burada sayarız.
document.querySelectorAll('.inner-stream-essay_rhythm .project-gallery-content figure.photo-figure:not(.contact-sheet-only-lead)').forEach(function (f, i) {
    if (i % 4 === 0) f.classList.add('is-essay-wide');
});
</script>

<script>
(function() {
    var prevUrl =<?= json_encode(!empty($prev_item) ? item_url($prev_item, ($is_tr ? 'tr' : 'en')) : null) ?>;
    var nextUrl = <?= json_encode(!empty($next_item) ? item_url($next_item, ($is_tr ? 'tr' : 'en')) : null) ?>;

    window.addEventListener('keydown', function(e) {
        var tag = (e.target && e.target.tagName) ? e.target.tagName.toUpperCase() : '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (e.target && e.target.isContentEditable)) return;
        if (e.ctrlKey || e.altKey || e.metaKey) return;

        // GLightbox açıkken modalın kendi yön tuşları devrede kalsın
        if (document.querySelector('.glightbox-container')) return;

        if (e.key === 'ArrowLeft' && prevUrl) {
            window.location.href = prevUrl;
        } else if (e.key === 'ArrowRight' && nextUrl) {
            window.location.href = nextUrl;
        } else if (e.key === 'g' || e.key === 'G') {
            e.preventDefault();
            if (typeof window.toggleContactSheet === 'function') window.toggleContactSheet();
        } else if (e.key === 't' || e.key === 'T') {
            e.preventDefault();
            if (typeof window.toggleSurfaceTheme === 'function') window.toggleSurfaceTheme();
        } else if (e.key === 'p' || e.key === 'P') {
            e.preventDefault();
            window.print();
        } else if (e.key === 'f' || e.key === 'F') {
            e.preventDefault();
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(function(){});
            } else {
                document.exitFullscreen().catch(function(){});
            }
        }
    });
})();
</script>
</body>
</html>
