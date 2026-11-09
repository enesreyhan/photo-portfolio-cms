<?php
/**
 * includes/views/home.php
 * Ana Sayfa Görünümü (Küratöryel Fotoğraf Seçkisi & Çoklu Şablon Mimarisi)
 * Şablonlar:
 * 1. cinema_fullbleed    : Tam Ekran Sinematik Slayt (Varsayılan)
 * 2. monograph_split     : Monografi Split (Yazı Sol, Fotoğraf Sağ)
 * 3. museum_passepartout : Müze & Sergi Salonu (Paspartu Çerçeve)
 * 4. editorial_magazine  : Editoryal Dergi Kapağı (Manşet & Künye)
 * 5. filmstrip_stage     : Film Şeridi & Sahne (Alt Thumbnail Bar)
 * 6. vertical_chapters   : Dikey Hikaye Bölümleri (Kaydırmalı Tam Ekran)
 * 7. curatorial_grid     : Küratöryel Portfolyo Izgarası (Vitrin Kartları)
 * 8. minimal_index       : Minimalist Tipografik İndeks (Etkileşimli Liste)
 */

$is_tr = ($lang ?? 'en') === 'tr';

// ── Ana Sayfa Şablon & Tasarım Ayarları (CMS Settings) ──
$hp_template             = get_setting('hp_template', 'cinema_fullbleed');
$hp_content_position     = get_setting('hp_content_position', 'bottom_left');
$hp_title_size           = get_setting('hp_title_size', 'normal');
$hp_image_fit            = get_setting('hp_image_fit', 'cover');
$hp_split_photo_width    = max(45, min(75, (int)get_setting('hp_split_photo_width', '60')));
$hp_grid_columns         = in_array(get_setting('hp_grid_columns', '2'), ['2', '3'], true) ? (int)get_setting('hp_grid_columns', '2') : 2;
$hp_show_title           = (get_setting('hp_show_title', '1') === '1');
$hp_show_description     = (get_setting('hp_show_description', '1') === '1');
$hp_show_location_year   = (get_setting('hp_show_location_year', '1') === '1');
$hp_show_explore_btn     = (get_setting('hp_show_explore_btn', '0') === '1');

$hp_autoplay             = (get_setting('hp_autoplay', '1') === '1');
$hp_interval_sec         = max(2, min(30, (int)get_setting('hp_interval_sec', '6')));
$hp_transition_effect    = get_setting('hp_transition_effect', 'fade');
$hp_slide_order          = get_setting('hp_slide_order', 'sequential');
$hp_click_to_advance     = (get_setting('hp_click_to_advance', '1') === '1');
$hp_keyboard_nav         = (get_setting('hp_keyboard_nav', '1') === '1');
$hp_swipe_nav            = (get_setting('hp_swipe_nav', '1') === '1');
$hp_pause_on_hover       = (get_setting('hp_pause_on_hover', '0') === '1');

$hp_show_counter         = (get_setting('hp_show_counter', '0') === '1');
$hp_counter_position     = get_setting('hp_counter_position', 'bottom_right');
$hp_show_arrows          = (get_setting('hp_show_arrows', '0') === '1');
$hp_arrows_style         = get_setting('hp_arrows_style', 'minimal_glass');
$hp_show_dots            = (get_setting('hp_show_dots', '0') === '1');
$hp_show_progress_bar    = (get_setting('hp_show_progress_bar', '0') === '1');
$hp_show_filmstrip       = (get_setting('hp_show_filmstrip', '0') === '1') || ($hp_template === 'filmstrip_stage');

$hp_default_surface      = get_setting('hp_default_surface', 'dark');
$hp_overlay_style        = get_setting('hp_overlay_style', 'bottom_gradient');
$hp_overlay_opacity      = max(0, min(95, (int)get_setting('hp_overlay_opacity', '75')));
$hp_hover_zoom           = (get_setting('hp_hover_zoom', '1') === '1');
$hp_enable_surface_toggle= (get_setting('hp_enable_surface_toggle', '0') === '1');

// Slayt sıralaması: shuffle modunda sunucu tarafında karıştır
if ($hp_slide_order === 'shuffle' && count($slides) > 1) {
    shuffle($slides);
}

$is_scrollable_hp = in_array($hp_template, ['vertical_chapters', 'curatorial_grid', 'framed_masonry', 'story_stream', 'sidebar_list', 'year_archive', 'contact_sheet', 'photo_statement', 'project_map', 'sectioned_showcase', 'free_collage', 'thumb_grid', 'book_spreads', 'mosaic_wall', 'photo_wall', 'staggered_gallery', 'split_grid', 'featured_grid', 'zigzag_stream', 'split_screens', 'triptych', 'section_banners', 'light_table'], true);
$is_below_fold_mode = in_array($hp_template, ['vertical_chapters', 'curatorial_grid', 'framed_masonry', 'story_stream', 'sidebar_list', 'year_archive', 'contact_sheet', 'photo_statement', 'project_map', 'sectioned_showcase', 'free_collage', 'thumb_grid', 'book_spreads', 'mosaic_wall', 'photo_wall', 'staggered_gallery', 'split_grid', 'featured_grid', 'zigzag_stream', 'split_screens', 'triptych', 'section_banners', 'light_table'], true);

$html_classes = ['hp-page'];
if (!$is_scrollable_hp) {
    $html_classes[] = 'mono-page';
} else {
    $html_classes[] = 'hp-scrollable-page';
}
if ($hp_default_surface === 'white' || $hp_default_surface === 'paper' || $hp_template === 'white_cube_gallery') {
    $html_classes[] = 'light gallery-white hp-surface-white';
} else {
    $html_classes[] = 'dark hp-surface-dark';
}
$html_class = implode(' ', $html_classes);

$GLOBALS['is_homepage'] = true;
require __DIR__ . '/layout_header.php';

// Her slayt için duyarlı görsel verilerini hazırla
$prepared_slides = [];
foreach ($slides as $idx => $s) {
    $title = item_field($s, 'title', $lang);
    $desc  = item_field($s, 'description', $lang);
    $loc   = !empty($s['location_' . $lang]) ? $s['location_' . $lang] : ($s['location'] ?? '');
    $year  = $s['year'] ?? '';

    $raw_link = trim($s['link_url'] ?? '');
    $target_url = resolve_slide_url($raw_link, $lang);

    $img_path = $s['image_path'] ? ltrim($s['image_path'], '/') : '';
    $full_img = resolve_image_file_path($img_path);

    $sm_avif = $full_img ? resize_and_convert_image($full_img, 800, 'avif', 80) : null;
    $md_avif = $full_img ? resize_and_convert_image($full_img, 1600, 'avif', 80) : null;
    $lg_avif = $full_img ? resize_and_convert_image($full_img, 2560, 'avif', 75) : null;

    $sm_webp = $full_img ? resize_and_convert_image($full_img, 800, 'webp', 82) : null;
    $md_webp = $full_img ? resize_and_convert_image($full_img, 1600, 'webp', 84) : null;
    $lg_webp = $full_img ? resize_and_convert_image($full_img, 2560, 'webp', 80) : null;

    $fallback_img_url = $md_webp ? url($md_webp) : ($md_avif ? url($md_avif) : url($img_path));
    $thumb_img_url    = $sm_webp ? url($sm_webp) : ($sm_avif ? url($sm_avif) : $fallback_img_url);

    $srcset_parts = [];
    if ($sm_avif) $srcset_parts[] = url($sm_avif) . ' 800w';
    if ($md_avif) $srcset_parts[] = url($md_avif) . ' 1600w';
    if ($lg_avif) $srcset_parts[] = url($lg_avif) . ' 2560w';
    $avif_srcset_str = implode(', ', $srcset_parts);

    $webp_srcset_str = '';
    if ($md_webp) {
        $w_parts = [];
        if ($sm_webp) $w_parts[] = url($sm_webp) . ' 800w';
        if ($md_webp) $w_parts[] = url($md_webp) . ' 1600w';
        if ($lg_webp) $w_parts[] = url($lg_webp) . ' 2560w';
        $webp_srcset_str = implode(', ', $w_parts);
    }

    $prepared_slides[] = [
        'idx'        => $idx,
        'title'      => $title,
        'desc'       => $desc,
        'loc'        => $loc,
        'year'       => $year,
        'target_url' => $target_url,
        'full_img'   => $full_img,
        'avif_srcset'=> $avif_srcset_str,
        'webp_srcset'=> $webp_srcset_str,
        'fallback'   => $fallback_img_url,
        'thumb'      => $thumb_img_url,
    ];
}

$total_slides_count = count($prepared_slides);
$initial_active_idx = 0;
if ($hp_slide_order === 'random_start' && $total_slides_count > 1 && !$is_scrollable_hp) {
    $initial_active_idx = random_int(0, $total_slides_count - 1);
}

$container_classes = [
    'mono-container',
    'hp-mono-container',
    'hp-tpl-' . preg_replace('/[^a-z0-9_-]/i', '', $hp_template),
    'hp-pos-' . preg_replace('/[^a-z0-9_-]/i', '', $hp_content_position),
    'hp-title-' . preg_replace('/[^a-z0-9_-]/i', '', $hp_title_size),
    'hp-fit-' . preg_replace('/[^a-z0-9_-]/i', '', $hp_image_fit),
    'hp-trans-' . preg_replace('/[^a-z0-9_-]/i', '', $hp_transition_effect),
    'hp-overlay-' . preg_replace('/[^a-z0-9_-]/i', '', $hp_overlay_style),
    'hp-counter-pos-' . preg_replace('/[^a-z0-9_-]/i', '', $hp_counter_position),
    'hp-arrows-' . preg_replace('/[^a-z0-9_-]/i', '', $hp_arrows_style),
];
// Yatay şerit görünümü (Temalar › Özelleştir): kartlar | kırpmadan | tam yükseklik
$hp_pano_style = in_array(get_setting('hp_panorama_style', 'cards'), ['cards', 'natural', 'fullheight'], true) ? get_setting('hp_panorama_style', 'cards') : 'cards';
$container_classes[] = 'hp-pano-' . $hp_pano_style;
if ($hp_hover_zoom) $container_classes[] = 'hp-has-hover-zoom';
if ($hp_show_filmstrip) $container_classes[] = 'hp-has-filmstrip';

$overlay_alpha = round($hp_overlay_opacity / 100, 2);
$container_style = sprintf(
    '--hp-split-photo-width: %d%%; --hp-overlay-alpha: %s; --hp-grid-cols: %d; --hp-interval-ms: %dms;',
    $hp_split_photo_width,
    $overlay_alpha,
    $hp_grid_columns,
    $hp_interval_sec * 1000
);
?>

<!-- ── NAV: fixed over full-bleed chapter ── -->
<?php render_site_navigation($lang, $en_path, $tr_path, $instagram_url); ?>

<?php if (get_setting('hp_intro_enabled', '0') === '1' && !empty($prepared_slides[0]['fallback'])):
    $intro = $prepared_slides[0]; ?>
<!-- ── Açılış perdesi (oturum başına bir kez) ── -->
<div id="xt-intro" class="xt-intro" role="dialog" aria-label="<?= e(get_setting('site_title_' . $lang, 'Portfolio')) ?>" hidden>
    <picture>
        <?php if ($intro['avif_srcset']): ?><source type="image/avif" srcset="<?= e($intro['avif_srcset']) ?>" sizes="100vw"><?php endif; ?>
        <?php if ($intro['webp_srcset']): ?><source type="image/webp" srcset="<?= e($intro['webp_srcset']) ?>" sizes="100vw"><?php endif; ?>
        <img src="<?= e($intro['fallback']) ?>" alt="" fetchpriority="high">
    </picture>
    <div class="xt-intro-text">
        <div class="xt-intro-name"><?= e(get_setting('header_logo_text', '') ?: preg_replace('/\s*[|–-].*$/u', '', get_setting('site_title_' . $lang, ''))) ?></div>
        <button type="button" class="xt-intro-enter"><?= e(site_t('Siteye Gir', 'Enter', $lang)) ?> <span aria-hidden="true">↓</span></button>
    </div>
</div>
<script>
(function () {
    var el = document.getElementById('xt-intro');
    if (!el) return;
    try { if (sessionStorage.getItem('xt_intro_seen') === '1') { el.remove(); return; } } catch (e) {}
    el.hidden = false;
    document.documentElement.classList.add('xt-intro-open');
    var closed = false;
    function close() {
        if (closed) return; closed = true;
        try { sessionStorage.setItem('xt_intro_seen', '1'); } catch (e) {}
        el.classList.add('is-leaving');
        document.documentElement.classList.remove('xt-intro-open');
        setTimeout(function () { el.remove(); }, 650);
    }
    el.addEventListener('click', close);
    window.addEventListener('wheel', close, { once: true, passive: true });
    window.addEventListener('touchmove', close, { once: true, passive: true });
    document.addEventListener('keydown', function (e) { if (['Enter', ' ', 'Escape', 'ArrowDown'].indexOf(e.key) !== -1) close(); });
})();
</script>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ŞABLON 7: KÜRATÖRYEL PORTFOLYO IZGARASI (curatorial_grid) -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php if ($hp_template === 'curatorial_grid'): ?>
<div class="<?= e(implode(' ', $container_classes)) ?>" id="hp-container" style="<?= e($container_style) ?>">
    <div class="hp-curatorial-grid-wrap">
        <?php foreach ($prepared_slides as $idx => $s): ?>
            <article class="hp-grid-card">
                <?php if ($s['target_url']): ?>
                    <a href="<?= e($s['target_url']) ?>" class="hp-grid-card-link" title="<?= e($s['title']) ?>">
                <?php else: ?>
                    <div class="hp-grid-card-link">
                <?php endif; ?>
                    <div class="hp-grid-card-media">
                        <?php if ($s['full_img']): ?>
                            <picture>
                                <?php if ($s['avif_srcset']): ?>
                                    <source type="image/avif" srcset="<?= e($s['avif_srcset']) ?>" sizes="(max-width: 768px) 100vw, 50vw">
                                <?php endif; ?>
                                <?php if ($s['webp_srcset']): ?>
                                    <source type="image/webp" srcset="<?= e($s['webp_srcset']) ?>" sizes="(max-width: 768px) 100vw, 50vw">
                                <?php endif; ?>
                                <img class="hp-grid-card-img"
                                     src="<?= e($s['fallback']) ?>"
                                     alt="<?= e($s['title']) ?>"
                                     draggable="false"
                                     <?php if ($idx === 0): ?>fetchpriority="high"<?php else: ?>loading="lazy"<?php endif; ?>
                                     decoding="async" />
                            </picture>
                        <?php endif; ?>
                        <div class="hp-grid-card-overlay"></div>
                    </div>
                    <div class="hp-grid-card-body">
                        <?php if ($hp_show_location_year && ($s['loc'] !== '' || $s['year'] !== '')): ?>
                            <div class="mono-chapter-meta">
                                <span class="mono-chapter-location">
                                    <?= e($s['loc']) ?><?= ($s['loc'] !== '' && $s['year'] !== '') ? ' • ' : '' ?><?= e($s['year']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($hp_show_title && $s['title'] !== ''): ?>
                            <h2 class="hp-grid-card-title"><?= e($s['title']) ?></h2>
                        <?php endif; ?>
                        <?php if ($hp_show_description && $s['desc'] !== ''): ?>
                            <p class="hp-grid-card-desc"><?= e($s['desc']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php if ($s['target_url']): ?>
                    </a>
                <?php else: ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ŞABLON 8: MİNİMALİST TİPOGRAFİK İNDEKS (minimal_index) -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php elseif ($hp_template === 'minimal_index'): ?>
<div class="<?= e(implode(' ', $container_classes)) ?>" id="hp-container" style="<?= e($container_style) ?>">
    <div class="hp-minimal-index-layout">
        <div class="hp-minimal-index-list" role="tablist">
            <div class="hp-minimal-index-kicker">
                <?= site_t('SEÇİLMİŞ KÜRATÖRYEL DİZİN', 'SELECTED CURATORIAL INDEX') ?> (<?= sprintf('%02d', $total_slides_count) ?>)
            </div>
            <?php foreach ($prepared_slides as $idx => $s): ?>
                <div class="hp-minimal-index-row <?= $idx === $initial_active_idx ? 'active' : '' ?>"
                     data-idx="<?= $idx ?>"
                     onmouseenter="if(window.hpGoToSlide) window.hpGoToSlide(<?= $idx ?>);"
                     onclick="<?= $s['target_url'] ? 'window.location.href=' . e(json_encode($s['target_url'])) : 'if(window.hpGoToSlide) window.hpGoToSlide(' . $idx . ')' ?>;">
                    <div class="hp-minimal-index-left">
                        <span class="hp-minimal-index-num"><?= sprintf('%02d', $idx + 1) ?></span>
                        <?php if ($s['target_url']): ?>
                            <a href="<?= e($s['target_url']) ?>" class="hp-minimal-index-title" onclick="event.stopPropagation()"><?= e($s['title'] ?: '—') ?></a>
                        <?php else: ?>
                            <span class="hp-minimal-index-title"><?= e($s['title'] ?: '—') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($hp_show_location_year && ($s['loc'] !== '' || $s['year'] !== '')): ?>
                        <span class="hp-minimal-index-meta">
                            <?= e($s['loc']) ?><?= ($s['loc'] !== '' && $s['year'] !== '') ? ' • ' : '' ?><?= e($s['year']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="hp-minimal-index-stage">
            <?php foreach ($prepared_slides as $idx => $s): ?>
                <section class="hp-chapter-slide hp-index-preview-slide <?= $idx === $initial_active_idx ? 'active' : '' ?>" data-idx="<?= $idx ?>" id="slide-<?= $idx ?>">
                    <?php if ($s['target_url']): ?>
                        <a href="<?= e($s['target_url']) ?>" class="hp-index-preview-link" title="<?= e($s['title']) ?>">
                    <?php else: ?>
                        <div class="hp-index-preview-link">
                    <?php endif; ?>
                        <?php if ($s['full_img']): ?>
                            <picture>
                                <?php if ($s['avif_srcset']): ?>
                                    <source type="image/avif" srcset="<?= e($s['avif_srcset']) ?>" sizes="60vw">
                                <?php endif; ?>
                                <?php if ($s['webp_srcset']): ?>
                                    <source type="image/webp" srcset="<?= e($s['webp_srcset']) ?>" sizes="60vw">
                                <?php endif; ?>
                                <img class="mono-chapter-img"
                                     src="<?= e($s['fallback']) ?>"
                                     alt="<?= e($s['title']) ?>"
                                     draggable="false"
                                     <?php if ($idx === $initial_active_idx): ?>fetchpriority="high"<?php else: ?>fetchpriority="low" loading="lazy"<?php endif; ?>
                                     decoding="async" />
                            </picture>
                        <?php endif; ?>
                    <?php if ($s['target_url']): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($hp_show_description && $s['desc'] !== ''): ?>
                        <div class="hp-index-preview-caption">
                            <p class="mono-chapter-desc"><?= e($s['desc']) ?></p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ŞABLON 9: YATAY PANORAMA & SONSUZ AKIŞ (horizontal_panorama) -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php elseif ($hp_template === 'horizontal_panorama'): ?>
<div class="<?= e(implode(' ', $container_classes)) ?>" id="hp-container" style="<?= e($container_style) ?>">
    <div class="hp-horizontal-panorama-stage" id="hpPanoramaStage" tabindex="0" role="region" aria-label="<?= site_t('Yatay Fotoğraf Panoraması', 'Horizontal Photography Panorama') ?>">
        <div class="hp-horizontal-panorama-track" id="hpPanoramaTrack">
            <?php foreach ($prepared_slides as $idx => $s): ?>
                <article class="hp-panorama-card <?= $idx === 0 ? 'active' : '' ?>" data-idx="<?= $idx ?>">
                    <?php if ($s['target_url']): ?>
                        <a href="<?= e($s['target_url']) ?>" class="hp-panorama-card-link" title="<?= e($s['title']) ?>">
                    <?php else: ?>
                        <div class="hp-panorama-card-link">
                    <?php endif; ?>
                        <div class="hp-panorama-media">
                            <?php if ($s['full_img']): ?>
                                <picture>
                                    <?php if ($s['avif_srcset']): ?>
                                        <source type="image/avif" srcset="<?= e($s['avif_srcset']) ?>" sizes="(max-height: 1080px) 150vw, 100vw">
                                    <?php endif; ?>
                                    <?php if ($s['webp_srcset']): ?>
                                        <source type="image/webp" srcset="<?= e($s['webp_srcset']) ?>" sizes="(max-height: 1080px) 150vw, 100vw">
                                    <?php endif; ?>
                                    <img class="hp-panorama-img"
                                         src="<?= e($s['fallback']) ?>"
                                         alt="<?= e($s['title']) ?>"
                                         draggable="false"
                                         <?php if ($idx < 3): ?>fetchpriority="high"<?php else: ?>loading="lazy"<?php endif; ?>
                                         decoding="async" />
                                </picture>
                            <?php endif; ?>
                            <div class="hp-panorama-overlay"></div>
                        </div>

                        <div class="hp-panorama-caption">
                            <?php if ($hp_show_location_year && ($s['loc'] !== '' || $s['year'] !== '')): ?>
                                <div class="mono-chapter-meta">
                                    <span class="mono-chapter-location">
                                        <?= e($s['loc']) ?><?= ($s['loc'] !== '' && $s['year'] !== '') ? ' • ' : '' ?><?= e($s['year']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if ($hp_show_title && $s['title'] !== ''): ?>
                                <h2 class="hp-panorama-title"><?= e($s['title']) ?></h2>
                            <?php endif; ?>
                            <?php if ($hp_show_description && $s['desc'] !== ''): ?>
                                <p class="hp-panorama-desc"><?= e($s['desc']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php if ($s['target_url']): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Panorama Navigasyon Okları -->
        <?php if ($hp_show_arrows && $total_slides_count > 1): ?>
        <div class="hp-panorama-nav-dock">
            <button type="button" class="hp-panorama-btn hp-panorama-prev" id="hpPanoramaPrev" aria-label="<?= site_t('Önceki Fotoğraf', 'Previous Photo') ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button type="button" class="hp-panorama-btn hp-panorama-next" id="hpPanoramaNext" aria-label="<?= site_t('Sonraki Fotoğraf', 'Next Photo') ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
        <?php endif; ?>

        <!-- Panorama İlerleme Çubuğu ve Sayaç -->
        <div class="hp-panorama-footer-bar">
            <?php if ($hp_show_progress_bar): ?>
                <div class="hp-panorama-progress-track">
                    <div class="hp-panorama-progress-thumb" id="hpPanoramaProgressThumb"></div>
                </div>
            <?php endif; ?>
            <?php if ($hp_show_counter): ?>
                <div class="hp-panorama-counter" id="hpPanoramaCounter">
                    <span id="hpPanoramaCur">01</span> / <span><?= sprintf('%02d', $total_slides_count) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ŞABLON 10: ÇERÇEVELİ KUTU DUVARI (framed_masonry) -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php elseif ($hp_template === 'framed_masonry'): ?>
<div class="<?= e(implode(' ', $container_classes)) ?>" id="hp-container" style="<?= e($container_style) ?>">
    <div class="hp-framed-box-wrapper">
        <div class="hp-framed-box-header">
            <div class="hp-framed-box-kicker">
                <?= site_t('SEÇİLMİŞ ÇALIŞMALAR & SERİLER', 'SELECTED WORKS & ARCHIVES') ?>
            </div>
            <div class="hp-framed-box-count">
                <?= sprintf('%02d', $total_slides_count) ?> <?= site_t('KADRAJ', 'FRAMES') ?>
            </div>
        </div>

        <div class="hp-framed-masonry-grid hp-masonry-cols-<?= (int)$hp_grid_columns ?>">
            <?php foreach ($prepared_slides as $idx => $s): ?>
                <article class="hp-framed-card">
                    <?php if ($s['target_url']): ?>
                        <a href="<?= e($s['target_url']) ?>" class="hp-framed-card-link" title="<?= e($s['title']) ?>">
                    <?php else: ?>
                        <div class="hp-framed-card-link">
                    <?php endif; ?>
                        <div class="hp-framed-card-media">
                            <?php if ($s['full_img']): ?>
                                <picture>
                                    <?php if ($s['avif_srcset']): ?>
                                        <source type="image/avif" srcset="<?= e($s['avif_srcset']) ?>" sizes="(max-width: 768px) 100vw, 50vw">
                                    <?php endif; ?>
                                    <?php if ($s['webp_srcset']): ?>
                                        <source type="image/webp" srcset="<?= e($s['webp_srcset']) ?>" sizes="(max-width: 768px) 100vw, 50vw">
                                    <?php endif; ?>
                                    <img class="hp-framed-card-img"
                                         src="<?= e($s['fallback']) ?>"
                                         alt="<?= e($s['title']) ?>"
                                         draggable="false"
                                         <?php if ($idx < 4): ?>fetchpriority="high"<?php else: ?>loading="lazy"<?php endif; ?>
                                         decoding="async" />
                                </picture>
                            <?php endif; ?>
                            <div class="hp-framed-card-overlay"></div>
                        </div>

                        <div class="hp-framed-card-info">
                            <?php if ($hp_show_location_year && ($s['loc'] !== '' || $s['year'] !== '')): ?>
                                <div class="hp-framed-meta">
                                    <?= e($s['loc']) ?><?= ($s['loc'] !== '' && $s['year'] !== '') ? ' • ' : '' ?><?= e($s['year']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($hp_show_title && $s['title'] !== ''): ?>
                                <h2 class="hp-framed-title"><?= e($s['title']) ?></h2>
                            <?php endif; ?>
                            <?php if ($hp_show_description && $s['desc'] !== ''): ?>
                                <p class="hp-framed-desc"><?= e($s['desc']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php if ($s['target_url']): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ŞABLON 12: EDİTORYAL HİKAYE AKIŞI (story_stream) -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php elseif ($hp_template === 'story_stream'): ?>
<div class="<?= e(implode(' ', $container_classes)) ?>" id="hp-container" style="<?= e($container_style) ?>">
    <div class="hp-story-stream-wrap">
        <?php foreach ($prepared_slides as $idx => $s): ?>
            <article class="hp-story-stream-item">
                <div class="hp-story-stream-media">
                    <?php if ($s['target_url']): ?>
                        <a href="<?= e($s['target_url']) ?>" class="hp-story-media-link" title="<?= e($s['title']) ?>">
                    <?php else: ?>
                        <div class="hp-story-media-link">
                    <?php endif; ?>
                        <?php if ($s['full_img']): ?>
                            <picture>
                                <?php if ($s['avif_srcset']): ?>
                                    <source type="image/avif" srcset="<?= e($s['avif_srcset']) ?>" sizes="100vw">
                                <?php endif; ?>
                                <?php if ($s['webp_srcset']): ?>
                                    <source type="image/webp" srcset="<?= e($s['webp_srcset']) ?>" sizes="100vw">
                                <?php endif; ?>
                                <img class="hp-story-stream-img"
                                     src="<?= e($s['fallback']) ?>"
                                     alt="<?= e($s['title']) ?>"
                                     draggable="false"
                                     <?php if ($idx === 0): ?>fetchpriority="high"<?php else: ?>loading="lazy"<?php endif; ?>
                                     decoding="async" />
                            </picture>
                        <?php endif; ?>
                    <?php if ($s['target_url']): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="hp-story-stream-details">
                    <div class="hp-story-stream-header">
                        <?php if ($hp_show_location_year && ($s['loc'] !== '' || $s['year'] !== '')): ?>
                            <div class="hp-story-stream-kicker">
                                <?= e($s['loc']) ?><?= ($s['loc'] !== '' && $s['year'] !== '') ? ' • ' : '' ?><?= e($s['year']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($hp_show_title && $s['title'] !== ''): ?>
                            <h2 class="hp-story-stream-title">
                                <?php if ($s['target_url']): ?>
                                    <a href="<?= e($s['target_url']) ?>"><?= e($s['title']) ?></a>
                                <?php else: ?>
                                    <?= e($s['title']) ?>
                                <?php endif; ?>
                            </h2>
                        <?php endif; ?>
                    </div>

                    <div class="hp-story-stream-narrative">
                        <?php if ($hp_show_description && $s['desc'] !== ''): ?>
                            <p class="hp-story-stream-desc"><?= e($s['desc']) ?></p>
                        <?php endif; ?>
                        <?php if ($s['target_url'] && $hp_show_explore_btn): ?>
                            <a href="<?= e($s['target_url']) ?>" class="hp-story-stream-btn">
                                <span><?= site_t('HİKAYEYİ İNCELE', 'EXPLORE STORY') ?></span>
                                <span class="hp-btn-arrow">&rarr;</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- EK ŞABLONLAR: Kenar Listesi · Yıllara Göre Arşiv · Kontak Baskı · Fotoğraf + Bildiri -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php elseif (in_array($hp_template, ['sidebar_list', 'year_archive', 'contact_sheet', 'photo_statement', 'project_map', 'sectioned_showcase', 'free_collage', 'thumb_grid', 'book_spreads', 'mosaic_wall', 'photo_wall', 'staggered_gallery', 'split_grid', 'featured_grid', 'zigzag_stream', 'split_screens', 'triptych', 'section_banners', 'light_table'], true)):
    require_once __DIR__ . '/partials/extra_templates.php';
    render_extra_template($hp_template, $prepared_slides, [
        'context'         => 'home',
        'container_class' => implode(' ', $container_classes),
        'container_style' => $container_style,
        'show_title'      => $hp_show_title,
        'show_desc'       => $hp_show_description,
        'show_locyear'    => $hp_show_location_year,
        'lang'            => $lang,
        'kicker'          => site_t('Seçili İşler', 'Selected Work', $lang),
        'statement'       => get_setting('site_desc_' . $lang, ''),
        'light'           => in_array($hp_default_surface, ['white', 'paper'], true),
    ]);
?>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- ŞABLON 1, 2, 3, 4, 5, 6, 11: SLAYT & SAHNE ŞABLONLARI (white_cube_gallery dahil) -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php else: ?>
<div class="<?= e(implode(' ', $container_classes)) ?>" id="hp-container" style="<?= e($container_style) ?>">
    <?php foreach ($prepared_slides as $idx => $s):
        $is_initial = ($idx === $initial_active_idx);
        $next_slide_idx = ($total_slides_count > 0) ? (($idx + 1) % $total_slides_count) : 0;
        $next_slide_title = $prepared_slides[$next_slide_idx]['title'] ?? '';
    ?>
        <section class="mono-chapter hp-chapter-slide <?= $is_initial ? 'active' : '' ?>" data-idx="<?= $idx ?>" id="slide-<?= $idx ?>">
            <!-- Görsel / Sahne Alanı -->
            <div class="hp-slide-visual-area">
                <?php if ($s['target_url']): ?>
                    <a href="<?= e($s['target_url']) ?>" class="mono-chapter-bg-link" aria-label="<?= e($s['title'] ?: 'Photograph') ?>" title="<?= e($s['title']) ?>">
                <?php else: ?>
                    <div class="mono-chapter-bg-link" role="button" tabindex="0" title="<?= site_t('Sonraki fotoğraf için tıklayın', 'Click to see next photo') ?>" aria-label="<?= e($s['title'] ?: 'Photograph') ?>">
                <?php endif; ?>
                    <?php if ($s['full_img']): ?>
                        <picture>
                            <?php if ($s['avif_srcset']): ?>
                                <source type="image/avif" srcset="<?= e($s['avif_srcset']) ?>" sizes="100vw">
                            <?php endif; ?>
                            <?php if ($s['webp_srcset']): ?>
                                <source type="image/webp" srcset="<?= e($s['webp_srcset']) ?>" sizes="100vw">
                            <?php endif; ?>
                            <img class="mono-chapter-img"
                                 src="<?= e($s['fallback']) ?>"
                                 alt="<?= e($s['title']) ?>"
                                 draggable="false"
                                 <?php if ($is_initial): ?>
                                     fetchpriority="high"
                                 <?php elseif ($is_below_fold_mode): ?>
                                     loading="lazy"
                                 <?php else: ?>
                                     fetchpriority="low" loading="lazy"
                                 <?php endif; ?>
                                 decoding="async" />
                        </picture>
                    <?php endif; ?>
                <?php if ($s['target_url']): ?>
                    </a>
                <?php else: ?>
                    </div>
                <?php endif; ?>

                <?php
                $has_slide_editorial = ($hp_show_location_year && ($s['loc'] !== '' || $s['year'] !== ''))
                                    || ($hp_show_title && $s['title'] !== '')
                                    || ($hp_show_description && $s['desc'] !== '')
                                    || ($hp_show_explore_btn && !empty($s['target_url']));
                ?>

                <!-- Cinematic dark gradients overlay (only when overlay is enabled and there is text to read) -->
                <?php if ($has_slide_editorial && $hp_overlay_style !== 'none'): ?>
                    <div class="mono-chapter-overlay"></div>
                <?php endif; ?>
            </div>

            <!-- Editorial Content (only rendered if title, kicker or description is enabled) -->
            <?php if ($has_slide_editorial): ?>
            <div class="mono-chapter-content hp-editorial-box">
                <div class="hp-editorial-primary">
                    <?php if ($hp_show_location_year && ($s['loc'] !== '' || $s['year'] !== '')): ?>
                    <div class="mono-chapter-meta">
                        <span class="mono-chapter-location">
                            <?= e($s['loc']) ?>
                            <?php if ($s['loc'] !== '' && $s['year'] !== ''): ?> • <?php endif; ?>
                            <?= e($s['year']) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ($hp_show_title && $s['title'] !== ''): ?>
                    <h2 class="mono-chapter-title">
                        <?php if ($s['target_url']): ?>
                            <a href="<?= e($s['target_url']) ?>" onclick="event.stopPropagation()"><?= e($s['title']) ?></a>
                        <?php else: ?>
                            <span><?= e($s['title']) ?></span>
                        <?php endif; ?>
                    </h2>
                    <?php endif; ?>
                </div>

                <?php if ($hp_show_description && $s['desc'] !== ''): ?>
                <div class="hp-editorial-secondary">
                    <p class="mono-chapter-desc">
                        <?= e($s['desc']) ?>
                    </p>
                </div>
                <?php endif; ?>

                <?php if ($hp_show_explore_btn && $s['target_url']): ?>
                <div class="hp-editorial-action">
                    <a href="<?= e($s['target_url']) ?>" class="hp-editorial-explore-btn" onclick="event.stopPropagation()">
                        <span><?= site_t('Projeyi İncele', 'Explore Project') ?></span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <!-- ── KADRAJ SAYACI (01 / 12) ── -->
    <?php if ($hp_show_counter && $hp_template !== 'filmstrip_stage' && $total_slides_count > 0): ?>
    <div class="hp-frame-counter" id="hpFrameCounter" aria-live="polite">
        <span id="hpCurrentFrameNum"><?= sprintf('%02d', $initial_active_idx + 1) ?></span>
        <span class="hp-counter-sep">/</span>
        <span><?= sprintf('%02d', $total_slides_count) ?></span>
    </div>
    <?php endif; ?>

    <!-- ── ÖNCEKİ / SONRAKİ GEZİNME OKLARI ── -->
    <?php if ($hp_show_arrows && $total_slides_count > 1): ?>
    <div class="hp-nav-arrows" role="group" aria-label="<?= site_t('Slayt Kontrolleri', 'Slide Controls') ?>">
        <button type="button" class="hp-arrow-btn hp-arrow-prev" onclick="if(window.hpUserStep) window.hpUserStep(-1); event.stopPropagation();" aria-label="<?= site_t('Önceki Fotoğraf', 'Previous Photo') ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
            <?php if ($hp_arrows_style === 'bottom_bar'): ?><span><?= site_t('ÖNCEKİ', 'PREV') ?></span><?php endif; ?>
        </button>
        <button type="button" class="hp-arrow-btn hp-arrow-next" onclick="if(window.hpUserStep) window.hpUserStep(1); event.stopPropagation();" aria-label="<?= site_t('Sonraki Fotoğraf', 'Next Photo') ?>">
            <?php if ($hp_arrows_style === 'bottom_bar'): ?><span><?= site_t('SONRAKİ', 'NEXT') ?></span><?php endif; ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
    <?php endif; ?>

    <!-- ── SLAYT NOKTA / ÇİZGİ GÖSTERGELERİ (PAGINATION DOTS) ── -->
    <?php if (($hp_show_dots || $hp_template === 'vertical_chapters') && $total_slides_count > 1): ?>
    <div class="hp-pagination-dots" id="hpPaginationDots" role="tablist">
        <?php foreach ($prepared_slides as $idx => $s): ?>
            <button type="button"
                    class="hp-dot-btn <?= $idx === $initial_active_idx ? 'active' : '' ?>"
                    data-idx="<?= $idx ?>"
                    onclick="if(window.hpGoToSlide) window.hpGoToSlide(<?= $idx ?>); event.stopPropagation();"
                    aria-label="<?= e(($s['title'] ?: 'Slide') . ' (' . ($idx + 1) . ')') ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── ALT FİLM ŞERİDİ (THUMBNAIL FILMSTRIP DOCK) ── -->
    <?php if ($hp_show_filmstrip && $total_slides_count > 1): ?>
    <div class="hp-filmstrip-dock" id="hpFilmstripDock">
        <?php foreach ($prepared_slides as $idx => $s): ?>
            <button type="button"
                    class="hp-filmstrip-thumb <?= $idx === $initial_active_idx ? 'active' : '' ?>"
                    data-idx="<?= $idx ?>"
                    onclick="if(window.hpGoToSlide) window.hpGoToSlide(<?= $idx ?>); event.stopPropagation();"
                    title="<?= e($s['title']) ?>">
                <img src="<?= e($s['thumb']) ?>" alt="<?= e($s['title']) ?>" loading="lazy" decoding="async" />
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── OTOMATİK GEÇİŞ SÜRE ÇUBUĞU (PROGRESS BAR) ── -->
    <?php if ($hp_show_progress_bar && $hp_autoplay && $total_slides_count > 1): ?>
    <div class="hp-progress-bar-track">
        <div class="hp-progress-bar-fill" id="hpProgressBarFill"></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (get_setting('footer_show_on_homepage', '0') === '1' && get_setting('footer_enabled', '1') === '1'): ?>
<div style="<?= $is_scrollable_hp ? 'position:relative; z-index:50;' : 'position:fixed; bottom:0; left:0; right:0; z-index:950; background:linear-gradient(0deg, rgba(0,0,0,0.75) 0%, transparent 100%); padding: 0 24px;' ?>">
    <?php 
    $render_closing_html = false;
    require __DIR__ . '/layout_footer.php'; 
    ?>
</div>
<?php endif; ?>

<!-- ── SCRIPT: Çoklu Şablon Geçiş, Döngü & Etkileşim ── -->
<script>
(function() {
    var HP_TEMPLATE = <?= json_encode($hp_template) ?>;

    // ── 1. DİKEY AKIŞ ŞABLONLARI (curatorial_grid, framed_masonry, story_stream) ──
    if (['curatorial_grid', 'framed_masonry', 'story_stream'].indexOf(HP_TEMPLATE) !== -1) {
        return;
    }

    // ── 2. YATAY PANORAMA & SONSUZ AKIŞ (horizontal_panorama) ──
    if (HP_TEMPLATE === 'horizontal_panorama') {
        var pTrack = document.getElementById('hpPanoramaTrack');
        var pPrevBtn = document.getElementById('hpPanoramaPrev');
        var pNextBtn = document.getElementById('hpPanoramaNext');
        var pProgressThumb = document.getElementById('hpPanoramaProgressThumb');
        var pCurEl = document.getElementById('hpPanoramaCur');
        var pCards = Array.from(document.querySelectorAll('.hp-panorama-card'));
        if (!pTrack || !pCards.length) return;

        function updatePanoramaState() {
            var scrollLeft = pTrack.scrollLeft;
            var maxScroll = pTrack.scrollWidth - pTrack.clientWidth;
            var progressPct = maxScroll > 0 ? (scrollLeft / maxScroll) * 100 : 0;
            if (pProgressThumb) {
                pProgressThumb.style.width = Math.min(100, Math.max(5, progressPct)) + '%';
            }

            // Aktif kartı bul
            var trackCenter = scrollLeft + (pTrack.clientWidth / 2);
            var bestIdx = 0;
            var minDiff = Infinity;
            pCards.forEach(function(card, idx) {
                var cardCenter = card.offsetLeft + (card.offsetWidth / 2);
                var diff = Math.abs(cardCenter - trackCenter);
                if (diff < minDiff) {
                    minDiff = diff;
                    bestIdx = idx;
                }
            });
            pCards.forEach(function(c, i) {
                c.classList.toggle('active', i === bestIdx);
            });
            if (pCurEl) {
                pCurEl.textContent = String(bestIdx + 1).padStart(2, '0');
            }
        }

        pTrack.addEventListener('scroll', function() {
            window.requestAnimationFrame(updatePanoramaState);
        }, { passive: true });

        // Fare tekerleği dikey hareketi yatay harekete dönüştürür (Smooth Mouse Wheel)
        pTrack.addEventListener('wheel', function(e) {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                e.preventDefault();
                pTrack.scrollLeft += e.deltaY;
            }
        }, { passive: false });

        // Ok butonları
        function scrollByCard(direction) {
            var cardWidth = pCards[0] ? pCards[0].offsetWidth + 18 : 450;
            pTrack.scrollBy({ left: direction * cardWidth, behavior: 'smooth' });
        }
        if (pPrevBtn) pPrevBtn.addEventListener('click', function() { scrollByCard(-1); });
        if (pNextBtn) pNextBtn.addEventListener('click', function() { scrollByCard(1); });

        // Klavye ok tuşları
        window.addEventListener('keydown', function(e) {
            var tag = (e.target && e.target.tagName) ? e.target.tagName.toUpperCase() : '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
            if (e.key === 'ArrowRight' || e.code === 'Space') {
                e.preventDefault();
                scrollByCard(1);
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                scrollByCard(-1);
            }
        });

        // Mouse Drag to Scroll
        var isDown = false;
        var startX, scrollLeftPos;
        pTrack.addEventListener('mousedown', function(e) {
            if (e.target.closest('a') || e.target.closest('button')) return;
            isDown = true;
            pTrack.classList.add('is-dragging');
            startX = e.pageX - pTrack.offsetLeft;
            scrollLeftPos = pTrack.scrollLeft;
        });
        window.addEventListener('mouseup', function() {
            isDown = false;
            if (pTrack) pTrack.classList.remove('is-dragging');
        });
        pTrack.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            var x = e.pageX - pTrack.offsetLeft;
            var walk = (x - startX) * 1.5;
            pTrack.scrollLeft = scrollLeftPos - walk;
        });

        updatePanoramaState();
        return;
    }

    // ── 3. SLAYT VE BÖLÜM TABANLI SAHNE ŞABLONLARI ──
    var slides = Array.from(document.querySelectorAll('.hp-chapter-slide'));
    if (!slides.length) return;

    var total = slides.length;
    var currentIdx = <?= (int)$initial_active_idx ?>;
    var AUTOPLAY_ENABLED = <?= json_encode($hp_autoplay && $hp_template !== 'vertical_chapters') ?>;
    var SLIDE_INTERVAL_MS = <?= (int)($hp_interval_sec * 1000) ?>;
    var CLICK_TO_ADVANCE = <?= json_encode($hp_click_to_advance && !in_array($hp_template, ['vertical_chapters', 'minimal_index'], true)) ?>;
    var KEYBOARD_NAV = <?= json_encode($hp_keyboard_nav) ?>;
    var SWIPE_NAV = <?= json_encode($hp_swipe_nav && $hp_template !== 'vertical_chapters') ?>;
    var PAUSE_ON_HOVER = <?= json_encode($hp_pause_on_hover) ?>;
    var autoTimer = null;
    var isHoveredPaused = false;

    var foundActive = slides.findIndex(function(s) { return s.classList.contains('active'); });
    if (foundActive !== -1) {
        currentIdx = foundActive;
    } else {
        slides[0].classList.add('active');
        currentIdx = 0;
    }

    function preloadSlide(idx) {
        if (idx < 0 || idx >= total) return;
        var img = slides[idx].querySelector('img');
        if (img && img.loading !== 'eager') {
            img.loading = 'eager';
        }
    }

    if (total > 1) {
        preloadSlide((currentIdx + 1) % total);
    }

    function syncIndicators(idx) {
        var counterEl = document.getElementById('hpCurrentFrameNum');
        if (counterEl) {
            counterEl.textContent = String(idx + 1).padStart(2, '0');
        }
        document.querySelectorAll('.hp-dot-btn, .hp-filmstrip-thumb, .hp-minimal-index-row').forEach(function(el) {
            var elIdx = parseInt(el.getAttribute('data-idx') || '-1', 10);
            el.classList.toggle('active', elIdx === idx);
        });
        var pBar = document.getElementById('hpProgressBarFill');
        if (pBar) {
            pBar.style.transition = 'none';
            pBar.style.width = '0%';
            void pBar.offsetWidth;
            if (AUTOPLAY_ENABLED && !isHoveredPaused) {
                pBar.style.transition = 'width ' + SLIDE_INTERVAL_MS + 'ms linear';
                pBar.style.width = '100%';
            }
        }
    }

    function goToSlide(targetIdx) {
        if (total <= 1) return;
        if (targetIdx < 0) targetIdx = total - 1;
        if (targetIdx >= total) targetIdx = 0;

        if (HP_TEMPLATE === 'vertical_chapters') {
            var targetSection = slides[targetIdx];
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth' });
            }
            currentIdx = targetIdx;
            syncIndicators(currentIdx);
            return;
        }

        if (targetIdx === currentIdx) return;

        var prevSlide = slides[currentIdx];
        var nextSlide = slides[targetIdx];

        if (prevSlide) {
            prevSlide.classList.remove('active');
        }
        if (nextSlide) {
            nextSlide.classList.add('active');
            var img = nextSlide.querySelector('img');
            if (img) img.loading = 'eager';
        }

        currentIdx = targetIdx;
        syncIndicators(currentIdx);
        preloadSlide((currentIdx + 1) % total);

        if (typeof window.checkHeaderLuminance === 'function') {
            setTimeout(window.checkHeaderLuminance, 50);
        }
    }

    function advanceSlide(step) {
        if (step === undefined) step = 1;
        var next = (currentIdx + step + total) % total;
        goToSlide(next);
    }

    function startAutoPlay() {
        stopAutoPlay();
        if (AUTOPLAY_ENABLED && total > 1 && !document.hidden && !isHoveredPaused) {
            syncIndicators(currentIdx);
            autoTimer = setInterval(function() {
                advanceSlide(1);
            }, SLIDE_INTERVAL_MS);
        }
    }

    function stopAutoPlay() {
        if (autoTimer) {
            clearInterval(autoTimer);
            autoTimer = null;
        }
    }

    function userInteract(step) {
        advanceSlide(step);
        startAutoPlay();
    }

    window.hpGoToSlide = function(idx) {
        goToSlide(idx);
        startAutoPlay();
    };
    window.hpUserStep = function(step) {
        userInteract(step);
    };

    startAutoPlay();

    if (PAUSE_ON_HOVER) {
        document.querySelectorAll('.hp-editorial-box, .hp-nav-arrows, .hp-filmstrip-dock, .hp-minimal-index-list').forEach(function(el) {
            el.addEventListener('mouseenter', function() {
                isHoveredPaused = true;
                stopAutoPlay();
            });
            el.addEventListener('mouseleave', function() {
                isHoveredPaused = false;
                startAutoPlay();
            });
        });
    }

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoPlay();
        } else {
            startAutoPlay();
        }
    });

    // Dikey Hikaye Bölümleri (vertical_chapters) için scroll takibi
    if (HP_TEMPLATE === 'vertical_chapters' && 'IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var idx = parseInt(entry.target.getAttribute('data-idx') || '0', 10);
                    currentIdx = idx;
                    syncIndicators(currentIdx);
                }
            });
        }, { threshold: 0.55 });
        slides.forEach(function(s) { obs.observe(s); });
    }

    // 1. Ekrana / Fotoğrafa tıklayınca sonraki fotoğrafa geç
    var container = document.getElementById('hp-container');
    if (container && CLICK_TO_ADVANCE) {
        container.addEventListener('click', function(e) {
            if (e.target.closest('a') || e.target.closest('button') || e.target.closest('#hidokmeh-menu-drawer') || e.target.closest('.om-header-bar') || e.target.closest('.hp-filmstrip-dock') || e.target.closest('.hp-pagination-dots')) {
                return;
            }
            userInteract(1);
        });
    }

    // 2. Menüdeki Logo'ya tıklayınca sayfa yenilenmek yerine akıcı şekilde bir sonraki fotoğrafa geçsin
    var logoLink = document.querySelector('.site-logo-link');
    if (logoLink) {
        logoLink.addEventListener('click', function(e) {
            e.preventDefault();
            userInteract(1);
        });
    }

    // 3. Klavye Tuşları
    if (KEYBOARD_NAV) {
        window.addEventListener('keydown', function(e) {
            var tag = (e.target && e.target.tagName) ? e.target.tagName.toUpperCase() : '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

            if (e.code === 'Space' || e.key === 'ArrowRight' || (HP_TEMPLATE === 'vertical_chapters' && e.key === 'ArrowDown')) {
                e.preventDefault();
                userInteract(1);
            } else if (e.key === 'ArrowLeft' || (HP_TEMPLATE === 'vertical_chapters' && e.key === 'ArrowUp')) {
                e.preventDefault();
                userInteract(-1);
            }
        });
    }

    // 4. Mobil Kaydırma (Swipe Touch Desteği)
    if (SWIPE_NAV) {
        var touchStartX = 0;
        var touchStartY = 0;
        var isSwiping = false;

        window.addEventListener('touchstart', function(e) {
            if (e.touches && e.touches[0]) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                isSwiping = false;
            }
        }, { passive: true });

        window.addEventListener('touchmove', function(e) {
            if (e.touches && e.touches[0]) {
                var diffX = Math.abs(e.touches[0].clientX - touchStartX);
                var diffY = Math.abs(e.touches[0].clientY - touchStartY);
                if (diffX > 20 || diffY > 20) {
                    isSwiping = true;
                }
            }
        }, { passive: true });

        window.addEventListener('touchend', function(e) {
            if (e.changedTouches && e.changedTouches[0]) {
                var touchEndX = e.changedTouches[0].clientX;
                var diff = touchStartX - touchEndX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        userInteract(1);
                    } else {
                        userInteract(-1);
                    }
                }
            }
        }, { passive: true });

        window.addEventListener('click', function(e) {
            if (isSwiping) {
                e.preventDefault();
                e.stopPropagation();
                isSwiping = false;
            }
        }, true);
    }
})();
</script>
</body>
</html>
