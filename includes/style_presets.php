<?php
/**
 * includes/style_presets.php
 * Tek tıkla stil paketlerinin ayar değerleri ve önizleme yardımcıları.
 * Panel (admin/templates.php) ve sitenin yönetici önizlemesi ortak kullanır.
 */

const STYLE_PREVIEW_COOKIE = 'er_style_preview';
const STYLE_HISTORY_KEY    = 'style_pkg_history';
const STYLE_HISTORY_MAX    = 3;

require_once __DIR__ . '/site_templates.php';
require_once __DIR__ . '/design_editor.php';

/** Tasarım düzenleyicisinin yayınlanmamış taslağını gösteren önizleme anahtarı */
const STYLE_PREVIEW_DRAFT = 'draft';

/** Ana şablonun önizleme / uygulama anahtarı */
function style_template_key(string $id): string {
    return 'tpl:' . $id;
}

function style_preset_settings(): array {
    static $all = null;
    if ($all !== null) return $all;
    $all = style_legacy_preset_settings();
    // Ana şablonlar (includes/site_templates.php): uygulanınca etkin şablon da kaydedilir
    foreach (site_templates() as $id => $t) {
        $all[style_template_key($id)] = $t['settings'] + [SITE_TEMPLATE_KEY => $id];
    }
    return $all;
}

function style_legacy_preset_settings(): array {
    return [
        'cinematic_dark' => [
            'homepage_template'       => 'cinema_fullbleed',
            'hp_template'             => 'cinema_fullbleed',
            'hp_default_surface'      => 'dark',
            'hp_overlay_style'        => 'bottom_gradient',
            'hp_overlay_opacity'      => '75',
            'inner_opening_layout'    => 'spread_right',
            'inner_default_surface'   => 'dark',
            'portfolio_home_template' => 'curatorial_grid',
            'pf_template'             => 'curatorial_grid',
            'pf_default_surface'      => 'dark',
            'about_default_surface'   => 'dark',
            'blog_default_surface'    => 'dark',
            'header_style'            => 'horizontal',
            'header_layout_style'     => 'classic_split',
            'hp_header_nav_style'     => 'inherit',
            'inner_stream_layout'     => 'editorial_single',
        ],
        'pure_white_gallery' => [
            'homepage_template'       => 'white_cube_gallery',
            'hp_template'             => 'white_cube_gallery',
            'hp_default_surface'      => 'white',
            'hp_overlay_style'        => 'none',
            'hp_overlay_opacity'      => '0',
            'hp_show_location_year'   => '1',
            'hp_show_title'           => '1',
            'hp_show_description'     => '1',
            'hp_show_counter'         => '0',
            'hp_show_arrows'          => '0',
            'hp_show_dots'            => '0',
            'inner_opening_layout'    => 'white_cube',
            'inner_default_surface'   => 'white',
            'portfolio_home_template' => 'curatorial_grid',
            'pf_template'             => 'curatorial_grid',
            'pf_default_surface'      => 'white',
            'about_default_surface'   => 'white',
            'blog_default_surface'    => 'white',
            'contact_page_surface'    => 'white',
            'header_style'            => 'horizontal_left',
            'header_layout_style'     => 'horizontal_left',
            'hp_header_nav_style'     => 'horizontal_left',
            'header_logo_type'        => 'symbol_text',
            'header_nav_uppercase'    => '1',
            'header_bg_style'         => 'transparent',
            'inner_stream_layout'     => 'white_cube_gallery',
        ],
        'editorial_magazine' => [
            'homepage_template'       => 'editorial_magazine',
            'hp_template'             => 'editorial_magazine',
            'hp_default_surface'      => 'dark',
            'hp_overlay_style'        => 'soft_scrim',
            'hp_overlay_opacity'      => '60',
            'inner_opening_layout'    => 'title_photo_text',
            'inner_default_surface'   => 'dark',
            'portfolio_home_template' => 'minimal_index',
            'pf_template'             => 'minimal_index',
            'pf_default_surface'      => 'dark',
            'about_default_surface'   => 'dark',
            'blog_default_surface'    => 'dark',
            'header_style'            => 'horizontal',
            'header_layout_style'     => 'classic_split',
            'hp_header_nav_style'     => 'inherit',
            'inner_stream_layout'     => 'editorial_single',
        ],
        'museum_paper' => [
            'homepage_template'       => 'museum_passepartout',
            'hp_template'             => 'museum_passepartout',
            'hp_default_surface'      => 'paper',
            'hp_overlay_style'        => 'none',
            'hp_overlay_opacity'      => '0',
            'inner_opening_layout'    => 'centered_museum',
            'inner_default_surface'   => 'paper',
            'portfolio_home_template' => 'minimal_index',
            'pf_template'             => 'minimal_index',
            'pf_default_surface'      => 'paper',
            'about_default_surface'   => 'paper',
            'blog_default_surface'    => 'paper',
            'header_style'            => 'horizontal',
            'header_layout_style'     => 'classic_split',
            'hp_header_nav_style'     => 'inherit',
            'inner_stream_layout'     => 'editorial_single',
        ],
        'vertical_monograph' => [
            'homepage_template'       => 'vertical_chapters',
            'hp_template'             => 'vertical_chapters',
            'hp_default_surface'      => 'dark',
            'hp_overlay_style'        => 'bottom_gradient',
            'hp_overlay_opacity'      => '70',
            'inner_opening_layout'    => 'sticky_split',
            'inner_default_surface'   => 'dark',
            'portfolio_home_template' => 'framed_masonry',
            'pf_template'             => 'framed_masonry',
            'pf_default_surface'      => 'dark',
            'about_default_surface'   => 'dark',
            'blog_default_surface'    => 'dark',
            'header_style'            => 'vertical_left',
            'header_layout_style'     => 'vertical_left',
            'hp_header_nav_style'     => 'inherit',
            'inner_stream_layout'     => 'editorial_single',
        ],
        // ── Yeni görünümler ──
        'sidebar_list_look' => [
            'homepage_template'       => 'sidebar_list',
            'hp_template'             => 'sidebar_list',
            'hp_default_surface'      => 'white',
            'portfolio_home_template' => 'sidebar_list',
            'pf_template'             => 'sidebar_list',
            'pf_default_surface'      => 'white',
            'inner_opening_layout'    => 'white_cube',
            'inner_stream_layout'     => 'captioned_sequence',
            'inner_default_surface'   => 'white',
            'about_default_surface'   => 'white',
            'blog_default_surface'    => 'white',
            'header_style'            => 'horizontal_left',
            'header_layout_style'     => 'horizontal_left',
            'hp_header_nav_style'     => 'inherit',
        ],
        'map_atlas' => [
            'homepage_template'       => 'project_map',
            'hp_template'             => 'project_map',
            'hp_default_surface'      => 'dark',
            'portfolio_home_template' => 'year_archive',
            'pf_template'             => 'year_archive',
            'pf_default_surface'      => 'dark',
            'inner_opening_layout'    => 'spread_right',
            'inner_stream_layout'     => 'captioned_sequence',
            'inner_default_surface'   => 'dark',
            'about_default_surface'   => 'dark',
            'blog_default_surface'    => 'dark',
            'header_style'            => 'horizontal',
            'header_layout_style'     => 'classic_split',
            'hp_header_nav_style'     => 'inherit',
        ],
        'archive_look' => [
            'homepage_template'       => 'year_archive',
            'hp_template'             => 'year_archive',
            'hp_default_surface'      => 'white',
            'portfolio_home_template' => 'contact_sheet',
            'pf_template'             => 'contact_sheet',
            'pf_default_surface'      => 'white',
            'inner_opening_layout'    => 'title_photo_text',
            'inner_stream_layout'     => 'grid_2col',
            'inner_default_surface'   => 'white',
            'about_default_surface'   => 'white',
            'blog_default_surface'    => 'white',
            'header_style'            => 'horizontal',
            'header_layout_style'     => 'classic_split',
            'hp_header_nav_style'     => 'inherit',
        ],
        'photobook_look' => [
            'homepage_template'       => 'photo_statement',
            'hp_template'             => 'photo_statement',
            'hp_default_surface'      => 'paper',
            'portfolio_home_template' => 'free_collage',
            'pf_template'             => 'free_collage',
            'pf_default_surface'      => 'paper',
            'inner_opening_layout'    => 'centered_museum',
            'inner_stream_layout'     => 'photobook_spread',
            'inner_default_surface'   => 'paper',
            'about_default_surface'   => 'paper',
            'blog_default_surface'    => 'paper',
            'header_style'            => 'horizontal_left',
            'header_layout_style'     => 'horizontal_left',
            'hp_header_nav_style'     => 'inherit',
        ],
        'agency_showcase' => [
            'homepage_template'       => 'sectioned_showcase',
            'hp_template'             => 'sectioned_showcase',
            'hp_default_surface'      => 'dark',
            'portfolio_home_template' => 'curatorial_grid',
            'pf_template'             => 'curatorial_grid',
            'pf_default_surface'      => 'dark',
            'inner_opening_layout'    => 'cinema_hero',
            'inner_stream_layout'     => 'editorial_single',
            'inner_default_surface'   => 'dark',
            'about_default_surface'   => 'dark',
            'blog_default_surface'    => 'dark',
            'header_style'            => 'horizontal',
            'header_layout_style'     => 'classic_split',
            'header_bg_style'         => 'transparent',
            'hp_header_nav_style'     => 'inherit',
        ],
    ];
}

/** Sitedeki önizleme çubuğu için paket adları (TR / EN) */
function style_preset_titles(): array {
    $titles = style_legacy_preset_titles();
    foreach (site_templates() as $id => $t) {
        $titles[style_template_key($id)] = ['tr' => $t['tr'], 'en' => $t['en']];
    }
    return $titles;
}

function style_legacy_preset_titles(): array {
    return [
        'cinematic_dark' => ['tr' => 'Belgesel & Sinematik Siyah', 'en' => 'Cinematic Dark'],
        'pure_white_gallery' => ['tr' => 'Saf Beyaz Galeri & Orijinal Kadraj', 'en' => 'Pure White Cube Gallery'],
        'editorial_magazine' => ['tr' => 'Editoryal Dergi & Foto-Röportaj', 'en' => 'Editorial Magazine & Essay'],
        'museum_paper' => ['tr' => 'Müze Paspartu & Sıcak Kitap Kağıdı', 'en' => 'Museum Paper & Passepartout'],
        'vertical_monograph' => ['tr' => 'Çağdaş Dikey Monografi', 'en' => 'Contemporary Vertical Monograph'],
        'sidebar_list_look' => ['tr' => 'Kenar Listesi', 'en' => 'Sidebar List'],
        'map_atlas' => ['tr' => 'Harita Atlası', 'en' => 'Map Atlas'],
        'archive_look' => ['tr' => 'Arşiv', 'en' => 'Archive'],
        'photobook_look' => ['tr' => 'Fotokitap', 'en' => 'Photobook'],
        'agency_showcase' => ['tr' => 'Ajans Vitrini', 'en' => 'Agency Showcase'],
    ];
}

/** Paketlerin değiştirdiği tüm ayar anahtarları (yedek ve geri alma için) */
function style_preset_tracked_keys(): array {
    $keys = [];
    foreach (style_preset_settings() as $set) {
        foreach ($set as $k => $_) {
            $keys[$k] = true;
        }
    }
    // Ana şablon seçeneklerinin yazdığı ayarlar da yedeklenir: geri alınca şablondan sonra
    // değiştirilen seçenekler de (alt bilgi, sütun sayısı vb.) önceki hâline döner
    foreach (site_template_options() as $def) {
        if (!empty($def['key'])) $keys[$def['key']] = true;
        foreach ($def['choices'] ?? [] as $c) {
            foreach ($c['writes'] ?? [] as $k => $_) $keys[$k] = true;
        }
    }
    // Tasarım düzenleyicisinin alanları (logo, yazı tipi, renkler…)
    foreach (design_editor_tracked_keys() as $k) $keys[$k] = true;
    return array_keys($keys);
}

/** Yönetici önizlemesi açıksa paket anahtarını döndürür (yalnızca site sayfalarında, yalnızca giriş yapmış yöneticiye). */
function style_preview_key(): ?string {
    static $resolved = false, $key = null;
    if ($resolved) return $key;
    $resolved = true;
    $cookie = $_COOKIE[STYLE_PREVIEW_COOKIE] ?? '';
    if ($cookie === '' || ($cookie !== STYLE_PREVIEW_DRAFT && !isset(style_preset_settings()[$cookie]))) return null;
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($script, '/admin/') !== false || strpos($script, '/api/') !== false) return null;
    if (!function_exists('is_logged_in')) {
        require_once __DIR__ . '/auth.php';
    }
    if (!is_logged_in()) return null;
    $key = $cookie;
    if (!headers_sent()) {
        header('Cache-Control: no-store, private');
    }
    return $key;
}

/** Önizleme açıksa get_setting() değerlerinin üzerine yazılacak ayarlar */
function style_preview_overrides(): array {
    $k = style_preview_key();
    if (!$k) return [];
    // Tasarım düzenleyicisi: sunucuda saklanan yayınlanmamış taslak (includes/design_editor.php)
    if ($k === STYLE_PREVIEW_DRAFT) return design_draft_overrides();
    return style_preset_settings()[$k];
}
