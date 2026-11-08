<?php
/**
 * includes/site_templates.php
 * Ana şablon kayıt dosyası: tek tıkla uygulanan 10 ana şablon ve uygulandıktan sonra
 * gösterilecek şablona özel seçenekler burada tanımlanır.
 *
 * - site_templates(): şablonlar (ad, açıklama, değiştirdiği ayarlar, gösterilecek seçenekler)
 * - site_template_options(): seçenek tanımları (etiket, tür, seçimler, hangi ayara yazdığı)
 * - Etkin şablon `site_template` ayarında tutulur.
 *
 * Önizleme / uygula / geri al altyapısı includes/style_presets.php içindedir;
 * şablonlar orada "tpl:<id>" anahtarıyla yer alır.
 */

const SITE_TEMPLATE_KEY     = 'site_template';
const SITE_TEMPLATE_DEFAULT = 'top_slideshow';

/** Tüm zemin ayarları (açık / koyu / kâğıt seçeneği bunların hepsine yazar) */
function site_template_surface_keys(): array {
    return ['hp_default_surface', 'pf_default_surface', 'inner_default_surface', 'about_default_surface', 'blog_default_surface', 'contact_page_surface'];
}

/**
 * Zemin seçimi → yazılacak ayarlar.
 * dark / white / paper sayfa zeminlerine doğrudan yazılır; gri açık zemin ('white') + gri ton olarak saklanır.
 * `site_surface_tone` açık moddaki zemin rengini belirler (layout_header.php → html.er-tone-*).
 */
const SITE_SURFACE_TONE_KEY = 'site_surface_tone';

function site_template_surface(string $surface): array {
    $page_value = $surface === 'gray' ? 'white' : $surface;
    $tone       = in_array($surface, ['paper', 'gray'], true) ? $surface : '';
    // Zemin seçilince özel zemin rengi temizlenir; yoksa eski özel renk seçilen tonu ezer
    return array_fill_keys(site_template_surface_keys(), $page_value) + [SITE_SURFACE_TONE_KEY => $tone, 'site_surface_custom' => ''];
}

/**
 * Ana şablonlar (sıra = paneldeki sıra).
 * settings: şablon uygulanınca yazılan ayarlar (yerleşim + önerilen zemin).
 * options : şablon seçildikten sonra gösterilecek seçenekler (site_template_options() anahtarları).
 * status  : 'ready' = tam çalışır; 'partial' = sonraki adımlarda tamamlanacak parçası var.
 */
function site_templates(): array {
    $common = ['surface', 'menu_style', 'home_source', 'footer']; // menü büyük harfi: Menü › Yazı tipi // logo ayarları: includes/design_editor.php (Site adı ve logo)

    $themes = [
        'sidebar_grid' => [
            'tr' => 'Sol Menü + Küçük Resim Izgarası',
            'en' => 'Sidebar + Thumbnail Grid',
            'desc_tr' => 'Solda sabit isim ve menü; sağda bütün çalışmalar küçük kareler halinde yan yana, alt alta.',
            'desc_en' => 'Fixed name and menu on the left; all work as small thumbnails side by side.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'vertical_left', 'header_layout_style' => 'vertical_left', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '1', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'thumb_grid', 'homepage_template' => 'thumb_grid', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'thumb_grid', 'portfolio_home_template' => 'thumb_grid',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'captioned_sequence',
            ] + site_template_surface('white'),
            'options' => array_merge($common, ['grid_columns', 'thumb_crop']),
        ],
        'sidebar_single' => [
            'tr' => 'Sol Menü + Tek Büyük Fotoğraf',
            'en' => 'Sidebar + Single Image',
            'desc_tr' => 'Solda isim ve menü; sağda beyaz zeminde kırpılmadan tek büyük fotoğraf.',
            'desc_en' => 'Name and menu on the left; one large uncropped image on a plain background.',
            'family' => 'single',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'vertical_left', 'header_layout_style' => 'vertical_left', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '1', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'white_cube_gallery', 'homepage_template' => 'white_cube_gallery', 'tpl_home_source' => 'slides', 'hp_image_fit' => 'contain', 'pf_image_fit' => 'contain',
                'pf_template' => 'white_cube_gallery', 'portfolio_home_template' => 'white_cube_gallery',
                'hp_overlay_style' => 'none', 'hp_overlay_opacity' => '0',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'white_cube_gallery',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'top_slideshow' => [
            'tr' => 'Üst Menü + Tam Ekran Slayt',
            'en' => 'Top Menu + Fullscreen Slideshow',
            'desc_tr' => 'Menü fotoğrafın üzerinde; her ekranda tek tam ekran fotoğraf, altta başlık.',
            'desc_en' => 'Menu over the image; one fullscreen photo per screen with its title.',
            'family' => 'single',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal', 'header_layout_style' => 'classic_split', 'hp_header_nav_style' => 'inherit', 'tpl_home_source' => 'slides', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'hp_template' => 'cinema_fullbleed', 'homepage_template' => 'cinema_fullbleed',
                'pf_template' => 'vertical_chapters', 'portfolio_home_template' => 'vertical_chapters',
                'hp_overlay_style' => 'bottom_gradient', 'hp_overlay_opacity' => '75',
                'inner_opening_layout' => 'spread_right', 'inner_stream_layout' => 'editorial_single',
            ] + site_template_surface('dark'),
            'options' => $common,
        ],
        'tiled_grid' => [
            'tr' => 'Döşeme Izgara',
            'en' => 'Tiled Grid',
            'desc_tr' => 'Üst menünün altında proje kapakları eşit kutularda; başlık üzerine gelince belirir.',
            'desc_en' => 'Project covers in equal tiles under a top menu; titles on hover.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal', 'header_layout_style' => 'classic_split', 'hp_header_nav_style' => 'inherit', 'tpl_home_source' => 'all', 'hp_grid_columns' => '3', 'pf_grid_columns' => '3', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'hp_template' => 'curatorial_grid', 'homepage_template' => 'curatorial_grid',
                'pf_template' => 'curatorial_grid', 'portfolio_home_template' => 'curatorial_grid',
                'inner_opening_layout' => 'title_photo_text', 'inner_stream_layout' => 'editorial_single',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'horizontal_strip' => [
            'tr' => 'Yatay Şerit',
            'en' => 'Horizontal Strip',
            'desc_tr' => 'Fotoğraflar aynı yükseklikte, kırpılmadan yan yana; sayfa yatay kaydırılır.',
            'desc_en' => 'Uncropped images at equal height in a sideways-scrolling row.',
            'family' => 'horizontal',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal_left', 'header_layout_style' => 'horizontal_left', 'hp_header_nav_style' => 'inherit', 'tpl_home_source' => 'slides', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'hp_template' => 'horizontal_panorama', 'homepage_template' => 'horizontal_panorama',
                'pf_template' => 'horizontal_panorama', 'portfolio_home_template' => 'horizontal_panorama',
                'hp_panorama_style' => 'cards', 'pf_panorama_style' => 'cards',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'horizontal_panorama',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'vertical_stream' => [
            'tr' => 'Dikey Akış',
            'en' => 'Vertical Stream',
            'desc_tr' => 'Büyük fotoğraflar alt alta akar; aralarında başlık ve kısa metin.',
            'desc_en' => 'Large images flowing down the page with titles and short text between them.',
            'family' => 'story',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal', 'header_layout_style' => 'classic_split', 'hp_header_nav_style' => 'inherit', 'tpl_home_source' => 'slides', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'hp_template' => 'story_stream', 'homepage_template' => 'story_stream',
                'pf_template' => 'story_stream', 'portfolio_home_template' => 'story_stream',
                'inner_opening_layout' => 'title_photo_text', 'inner_stream_layout' => 'story_stream',
            ] + site_template_surface('dark'),
            'options' => $common,
        ],
        'text_index' => [
            'tr' => 'Yalnızca Yazı İndeksi',
            'en' => 'Text Index',
            'desc_tr' => 'Ana sayfada büyük isim ve proje listesi; üzerine gelince küçük önizleme belirir.',
            'desc_en' => 'A large name and a project list; a small preview appears on hover.',
            'family' => 'story',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal_left', 'header_layout_style' => 'horizontal_left', 'hp_header_nav_style' => 'inherit', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'hp_template' => 'minimal_index', 'homepage_template' => 'minimal_index',
                'pf_template' => 'minimal_index', 'portfolio_home_template' => 'minimal_index',
                'inner_opening_layout' => 'title_photo_text', 'inner_stream_layout' => 'editorial_single',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'mosaic_wall' => [
            'tr' => 'Mozaik Duvar',
            'en' => 'Mosaic Wall',
            'desc_tr' => 'Farklı boyutlarda kapaklar boşluksuz bir duvar oluşturur; üzerine gelince başlık çıkar.',
            'desc_en' => 'Covers of varying sizes form a dense wall; titles on hover.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal', 'header_layout_style' => 'classic_split', 'hp_header_nav_style' => 'inherit', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'hp_template' => 'mosaic_wall', 'homepage_template' => 'mosaic_wall',
                'pf_template' => 'mosaic_wall', 'portfolio_home_template' => 'mosaic_wall',
                'inner_opening_layout' => 'spread_right', 'inner_stream_layout' => 'framed_masonry',
            ] + site_template_surface('dark'),
            'options' => array_merge($common, ['grid_columns']),
        ],
        'archive' => [
            'tr' => 'Arşiv (Yıllara Göre)',
            'en' => 'Archive by Year',
            'desc_tr' => 'Projeler yıl başlıkları altında; her satırda küçük kapak, başlık ve yer.',
            'desc_en' => 'Projects grouped under year headings with a small cover, title and place.',
            'family' => 'story',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal', 'header_layout_style' => 'classic_split', 'hp_header_nav_style' => 'inherit', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'hp_template' => 'year_archive', 'homepage_template' => 'year_archive',
                'pf_template' => 'year_archive', 'portfolio_home_template' => 'year_archive',
                'inner_opening_layout' => 'title_photo_text', 'inner_stream_layout' => 'captioned_sequence',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'photobook' => [
            'tr' => 'Fotokitap',
            'en' => 'Photobook',
            'desc_tr' => 'Krem kâğıt zemin; projeler kitap sayfası gibi ikişerli açılımlarda.',
            'desc_en' => 'Warm paper background; projects laid out as two-page book spreads.',
            'family' => 'story',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal_left', 'header_layout_style' => 'horizontal_left', 'hp_header_nav_style' => 'inherit', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'hp_template' => 'book_spreads', 'homepage_template' => 'book_spreads',
                'pf_template' => 'book_spreads', 'portfolio_home_template' => 'book_spreads',
                'inner_opening_layout' => 'centered_museum', 'inner_stream_layout' => 'photobook_spread',
            ] + site_template_surface('paper'),
            'options' => $common,
        ],
        // ── Format seçkisinden uyarlanan temalar · 1. aşama: mevcut düzenlerin birleşimleri ──
        'top_square_grid' => [
            'tr' => 'Üst Menü + Kare Izgara',
            'en' => 'Top Menu + Square Grid',
            'desc_tr' => 'Solda isim, sağda menü; altında bütün çalışmalar eşit kare küçük resimlerle ortalanmış bir ızgarada.',
            'desc_en' => 'Name on the left, menu on the right; all work below in a centred grid of equal square thumbnails.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal', 'header_layout_style' => 'classic_split', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0',
                'hp_template' => 'thumb_grid', 'homepage_template' => 'thumb_grid', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'thumb_grid', 'portfolio_home_template' => 'thumb_grid', 'tpl_grid_columns' => '4', 'tpl_thumb_crop' => 'square',
                'header_scroll_effect' => 'solid_on_scroll',
                'inner_opening_layout' => 'title_photo_text', 'inner_stream_layout' => 'captioned_sequence',
            ] + site_template_surface('white'),
            'options' => array_merge($common, ['grid_columns', 'thumb_crop']),
        ],
        'sidebar_strip_captioned' => [
            'tr' => 'Sol Menü + Yatay Galeri',
            'en' => 'Sidebar + Horizontal Gallery',
            'desc_tr' => 'Solda dikey menü; büyük fotoğraflar yan yana yatay kayar, altlarında proje adları.',
            'desc_en' => 'Vertical menu on the left; large photos scroll sideways with project names below.',
            'family' => 'horizontal',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'vertical_left', 'header_layout_style' => 'vertical_left', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '1', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'horizontal_panorama', 'homepage_template' => 'horizontal_panorama', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'horizontal_panorama', 'portfolio_home_template' => 'horizontal_panorama',
                'hp_show_title' => '1', 'pf_show_title' => '1', 'hp_show_location_year' => '0', 'pf_show_location_year' => '0', 'hp_show_description' => '0', 'pf_show_description' => '0',
                'hp_panorama_style' => 'natural', 'pf_panorama_style' => 'natural',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'horizontal_panorama',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'sidebar_strip_dark' => [
            'tr' => 'Koyu Sol Menü + Yatay Şerit',
            'en' => 'Dark Sidebar + Horizontal Strip',
            'desc_tr' => 'Koyu zemin; solda dikey menü, sağda ekran yüksekliğinde fotoğraflar yatay kayar.',
            'desc_en' => 'Dark background; vertical menu on the left, full-height photos scrolling sideways.',
            'family' => 'horizontal',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'vertical_left', 'header_layout_style' => 'vertical_left', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '1', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'horizontal_panorama', 'homepage_template' => 'horizontal_panorama', 'tpl_home_source' => 'slides', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'horizontal_panorama', 'portfolio_home_template' => 'horizontal_panorama',
                'hp_show_title' => '0', 'pf_show_title' => '1', 'hp_show_location_year' => '0', 'pf_show_location_year' => '0', 'hp_show_description' => '0', 'pf_show_description' => '0',
                'hp_panorama_style' => 'fullheight', 'pf_panorama_style' => 'fullheight',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'horizontal_panorama',
            ] + site_template_surface('dark'),
            'options' => $common,
        ],
        'sidebar_strip' => [
            'tr' => 'Sol Menü + Yatay Şerit',
            'en' => 'Sidebar + Horizontal Strip',
            'desc_tr' => 'Açık zemin; solda dikey menü, sağda fotoğraflar yazısız, yalın bir şerit hâlinde yatay kayar.',
            'desc_en' => 'Light background; vertical menu on the left, photos in a plain sideways-scrolling strip.',
            'family' => 'horizontal',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'vertical_left', 'header_layout_style' => 'vertical_left', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'horizontal_panorama', 'homepage_template' => 'horizontal_panorama', 'tpl_home_source' => 'slides', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'horizontal_panorama', 'portfolio_home_template' => 'horizontal_panorama',
                'hp_show_title' => '0', 'pf_show_title' => '0', 'hp_show_location_year' => '0', 'pf_show_location_year' => '0', 'hp_show_description' => '0', 'pf_show_description' => '0',
                'hp_panorama_style' => 'natural', 'pf_panorama_style' => 'natural',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'horizontal_panorama',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'sidebar_wall' => [
            'tr' => 'Sol Menü + Duvar',
            'en' => 'Sidebar + Wall',
            'desc_tr' => 'Solda dikey menü; sağda farklı boylardaki fotoğraflar kırpılmadan sütunlar hâlinde dizilir.',
            'desc_en' => 'Vertical menu on the left; photos of varying heights stacked uncropped in columns.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'vertical_left', 'header_layout_style' => 'vertical_left', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '1', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'photo_wall', 'homepage_template' => 'photo_wall', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'photo_wall', 'portfolio_home_template' => 'photo_wall', 'tpl_wall_columns' => '3', 'tpl_wall_statement' => '0',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'framed_masonry',
            ] + site_template_surface('white'),
            'options' => array_merge($common, ['wall_columns']),
        ],
        // ── 2. aşama: yeni ızgara düzenleri ──
        'staggered' => [
            'tr' => 'Kademeli Galeri',
            'en' => 'Staggered Gallery',
            'desc_tr' => 'Ortada isim ve menü; fotoğraflar kırpılmadan, farklı genişlik ve kaydırmalarla, bol boşluklu bir ritimde.',
            'desc_en' => 'Name and menu centred; uncropped photos in varying widths and offsets with generous space.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'stacked_center', 'header_layout_style' => 'classic_center', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'staggered_gallery', 'homepage_template' => 'staggered_gallery', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'staggered_gallery', 'portfolio_home_template' => 'staggered_gallery',
                'hp_show_title' => '1', 'pf_show_title' => '1', 'hp_show_location_year' => '1', 'pf_show_location_year' => '1',
                'header_scroll_effect' => 'solid_on_scroll',
                'inner_opening_layout' => 'title_photo_text', 'inner_stream_layout' => 'editorial_single',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'one_two' => [
            'tr' => 'Bir Büyük İki Küçük',
            'en' => 'One Large, Two Small',
            'desc_tr' => 'Üstte isim ve “Menü” düğmesi; üçlü bloklarda bir büyük, iki küçük fotoğraf, büyük olan bir solda bir sağda.',
            'desc_en' => 'Name and a “Menu” button on top; blocks of one large and two small photos, the large one alternating sides.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'hamburger', 'header_layout_style' => 'hamburger', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'split_grid', 'homepage_template' => 'split_grid', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'split_grid', 'portfolio_home_template' => 'split_grid',
                'header_hamburger_style' => 'pill_label', 'hp_show_title' => '1', 'pf_show_title' => '1',
                'header_scroll_effect' => 'solid_on_scroll',
                'inner_opening_layout' => 'spread_left', 'inner_stream_layout' => 'grid_2col',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'sidebar_featured' => [
            'tr' => 'Sol Menü + Öne Çıkan',
            'en' => 'Sidebar + Featured',
            'desc_tr' => 'Solda dikey menü; en son çalışma büyük ve kırpılmadan, altında diğerleri küçük kareler hâlinde.',
            'desc_en' => 'Vertical menu on the left; the latest work large and uncropped, the rest as small squares below.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'vertical_left', 'header_layout_style' => 'vertical_left', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '1', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'featured_grid', 'homepage_template' => 'featured_grid', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'featured_grid', 'portfolio_home_template' => 'featured_grid',
                'tpl_grid_columns' => '4', 'hp_show_title' => '1', 'pf_show_title' => '1',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'captioned_sequence',
            ] + site_template_surface('white'),
            'options' => array_merge($common, ['grid_columns']),
        ],
        'statement_wall' => [
            'tr' => 'Bildiri + Duvar',
            'en' => 'Statement + Wall',
            'desc_tr' => 'Üstte isim ve “Menü” düğmesi; büyük puntolu bir tanıtım cümlesi, altında fotoğraflar kırpılmadan iki sütunda.',
            'desc_en' => 'Name and a “Menu” button on top; a large intro sentence, then uncropped photos in two columns.',
            'family' => 'grid',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'hamburger', 'header_layout_style' => 'hamburger', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'photo_wall', 'homepage_template' => 'photo_wall', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'photo_wall', 'portfolio_home_template' => 'photo_wall',
                'header_hamburger_style' => 'pill_label', 'tpl_wall_columns' => '2', 'tpl_wall_statement' => '1', 'hp_show_title' => '0', 'pf_show_title' => '1',
                'header_scroll_effect' => 'solid_on_scroll',
                'inner_opening_layout' => 'title_photo_text', 'inner_stream_layout' => 'framed_masonry',
            ] + site_template_surface('white'),
            'options' => array_merge($common, ['wall_columns']),
        ],
        'zigzag' => [
            'tr' => 'Zikzak Akış',
            'en' => 'Zigzag Stream',
            'desc_tr' => 'Üstte isim ve çizgi menü düğmesi; fotoğraflar kırpılmadan, bir sola bir sağa yaslanarak aşağı akar.',
            'desc_en' => 'Name and a lines menu button on top; uncropped photos flow down the page, alternating left and right.',
            'family' => 'single',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'hamburger', 'header_layout_style' => 'hamburger', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'zigzag_stream', 'homepage_template' => 'zigzag_stream', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'zigzag_stream', 'portfolio_home_template' => 'zigzag_stream',
                'header_hamburger_style' => 'minimal_lines', 'hp_show_title' => '1', 'pf_show_title' => '1', 'hp_show_location_year' => '1', 'pf_show_location_year' => '1', 'header_scroll_effect' => 'solid_on_scroll',
                'inner_opening_layout' => 'white_cube', 'inner_stream_layout' => 'editorial_single',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'split_screen' => [
            'tr' => 'Bölünmüş Ekran',
            'en' => 'Split Screen',
            'desc_tr' => 'Her proje bir ekran: bir yarıda kenara taşan fotoğraf, öbür yarıda büyük başlık ve kısa açıklama.',
            'desc_en' => 'Each project fills a screen: an edge-to-edge photo on one half, a large title and short text on the other.',
            'family' => 'story',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'hamburger', 'header_layout_style' => 'hamburger', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'split_screens', 'homepage_template' => 'split_screens', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'split_screens', 'portfolio_home_template' => 'split_screens',
                'header_hamburger_style' => 'pill_label', 'tpl_split_style' => 'bleed', 'hp_show_title' => '1', 'pf_show_title' => '1', 'hp_show_description' => '1', 'pf_show_description' => '1', 'header_scroll_effect' => 'solid_on_scroll',
                'inner_opening_layout' => 'spread_left', 'inner_stream_layout' => 'editorial_single',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'magazine_stories' => [
            'tr' => 'Dergi Hikâyeleri',
            'en' => 'Magazine Stories',
            'desc_tr' => 'Kâğıt zeminde dergi sayfaları: renkli panelde çerçeveli fotoğraf, yanında ince puntolu başlık ve açıklama.',
            'desc_en' => 'Magazine pages on paper: a framed photo on a tinted panel, a light title and text beside it.',
            'family' => 'story',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'hamburger', 'header_layout_style' => 'hamburger', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'split_screens', 'homepage_template' => 'split_screens', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'split_screens', 'portfolio_home_template' => 'split_screens',
                'header_hamburger_style' => 'minimal_lines', 'tpl_split_style' => 'framed', 'hp_show_title' => '1', 'pf_show_title' => '1', 'hp_show_description' => '1', 'pf_show_description' => '1', 'header_scroll_effect' => 'solid_on_scroll',
                'inner_opening_layout' => 'title_photo_text', 'inner_stream_layout' => 'editorial_single',
            ] + site_template_surface('paper'),
            'options' => $common,
        ],
        'three_panels' => [
            'tr' => 'Üç Panel',
            'en' => 'Three Panels',
            'desc_tr' => 'Koyu zeminde ekran üç dikey panele bölünür; her panel bir proje kapağı, ortasında adı.',
            'desc_en' => 'On a dark background the screen splits into three tall panels, each a project cover with its name centred.',
            'family' => 'single',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'hamburger', 'header_layout_style' => 'hamburger', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'triptych', 'homepage_template' => 'triptych', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'triptych', 'portfolio_home_template' => 'triptych',
                'header_hamburger_style' => 'minimal_lines', 'hp_show_title' => '1', 'pf_show_title' => '1',
                'inner_opening_layout' => 'cinema', 'inner_stream_layout' => 'captioned_sequence',
            ] + site_template_surface('dark'),
            'options' => $common,
        ],
        'section_banners' => [
            'tr' => 'Bölüm Afişleri',
            'en' => 'Section Banners',
            'desc_tr' => 'Üstte isim ve menü düğmesi; menüdeki her bölüm kapak fotoğraflı geniş bir afiş, büyük başlık ve okla.',
            'desc_en' => 'Name and a menu button on top; each menu section is a wide banner with a cover photo, large title and an arrow.',
            'family' => 'story',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'hamburger', 'header_layout_style' => 'hamburger', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'section_banners', 'homepage_template' => 'section_banners', 'tpl_home_source' => 'all', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'section_banners', 'portfolio_home_template' => 'section_banners',
                'header_hamburger_style' => 'pill_label', 'tpl_banner_source' => 'categories', 'hp_show_title' => '1', 'pf_show_title' => '1', 'header_scroll_effect' => 'solid_on_scroll',
                'inner_opening_layout' => 'spread_left', 'inner_stream_layout' => 'grid_2col',
            ] + site_template_surface('white'),
            'options' => $common,
        ],
        'light_table' => [
            'tr' => 'Işık Masası',
            'en' => 'Light Table',
            'desc_tr' => 'Koyu zeminde tek büyük fotoğraf, sağda dikey küçük resim şeridi; küçük resme tıklayınca fotoğraf değişir.',
            'desc_en' => 'One large photo on a dark background with a vertical thumbnail strip on the right; click a thumbnail to change the photo.',
            'family' => 'single',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal', 'header_layout_style' => 'classic_split', 'hp_header_nav_style' => 'inherit',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'light_table', 'homepage_template' => 'light_table', 'tpl_home_source' => 'all', 'hp_image_fit' => 'contain', 'pf_image_fit' => 'contain',
                'pf_template' => 'light_table', 'portfolio_home_template' => 'light_table',
                'hp_show_title' => '1', 'pf_show_title' => '1', 'hp_show_location_year' => '1', 'pf_show_location_year' => '1',
                'inner_opening_layout' => 'cinema', 'inner_stream_layout' => 'white_cube_gallery',
            ] + site_template_surface('dark'),
            'options' => $common,
        ],
        'bottom_bar' => [
            'tr' => 'Alt Çubuklu Tam Ekran',
            'en' => 'Fullscreen + Bottom Bar',
            'desc_tr' => 'Fotoğraf ekranın tamamını kaplar; isim ve menü ana sayfada altta ince bir çubukta durur.',
            'desc_en' => 'The photo fills the whole screen; on the homepage the name and menu sit in a thin bar at the bottom.',
            'family' => 'single',
            'status' => 'ready',
            'settings' => [
                'header_style' => 'horizontal', 'header_layout_style' => 'classic_split', 'hp_header_nav_style' => 'inherit', 'header_home_position' => 'bottom',
                'header_logo_type' => 'text_only', 'header_logo_show_subtext' => '0', 'header_logo_subtext_size' => 'small',
                'hp_template' => 'cinema_fullbleed', 'homepage_template' => 'cinema_fullbleed', 'tpl_home_source' => 'slides', 'hp_image_fit' => 'cover', 'pf_image_fit' => 'cover',
                'pf_template' => 'vertical_chapters', 'portfolio_home_template' => 'vertical_chapters',
                'hp_overlay_style' => 'none', 'hp_show_title' => '0', 'hp_show_description' => '0', 'hp_show_location_year' => '0',
                'inner_opening_layout' => 'cinema', 'inner_stream_layout' => 'editorial_single',
            ] + site_template_surface('dark'),
            'options' => $common,
        ],
    ];
    // Yalnız bazı temaların değiştirdiği ayarlar: diğer temalara geçince varsayılana dönsün
    $resets = ['header_home_position' => 'top', 'header_scroll_effect' => 'none'];
    // Logo/metin seçimi site kimliğidir; tema değişince kullanıcının yüklediği logo kaybolmasın
    foreach ($themes as &$t) { $t['settings'] += $resets; unset($t['settings']['header_logo_type']); }
    unset($t);
    return $themes;
}

/**
 * Seçenek tanımları.
 * type: 'choice' (radyo düğmeleri) | 'toggle' (aç/kapat)
 * key : doğrudan yazılan ayar. Ya da `writes`: seçilen değere göre birden çok ayar.
 */
function site_template_options(): array {
    $surfaces = [];
    foreach (['dark' => ['Siyah', 'Black', '#0b0b0c'], 'white' => ['Beyaz', 'White', '#ffffff'], 'paper' => ['Kâğıt', 'Paper', '#f4f1ea'], 'gray' => ['Gri', 'Gray', '#e6e5e2']] as $v => $l) {
        $surfaces[$v] = ['tr' => $l[0], 'en' => $l[1], 'swatch' => $l[2], 'writes' => site_template_surface($v)];
    }
    $menu_writes = function (string $style): array {
        $layout = ['horizontal' => 'classic_split', 'horizontal_center' => 'classic_center', 'stacked_center' => 'classic_center', 'horizontal_left' => 'horizontal_left', 'hamburger' => 'hamburger', 'vertical_left' => 'vertical_left'][$style];
        return ['header_style' => $style, 'header_layout_style' => $layout, 'hp_header_nav_style' => 'inherit'];
    };
    return [
        'surface' => [
            'group' => 'color', 'tr' => 'Zemin rengi', 'en' => 'Background', 'type' => 'choice', 'ui' => 'swatch',
            'choices' => $surfaces,
        ],
        'logo_type' => [
            'group' => 'logo', 'tr' => 'Sol üstte ne görünsün', 'en' => 'Site identity', 'type' => 'choice', 'ui' => 'visual', 'key' => 'header_logo_type',
            'choices' => [
                'text_only'   => ['tr' => 'Yalnızca yazı', 'en' => 'Text only'],
                'symbol_text' => ['tr' => 'Simge + yazı', 'en' => 'Symbol + text'],
                'full'        => ['tr' => 'Tam logo', 'en' => 'Full logo'],
                'symbol'      => ['tr' => 'Yalnızca simge', 'en' => 'Symbol only'],
            ],
        ],
        'show_subtitle' => [
            'group' => 'logo', 'tr' => 'İsmin altında alt başlık', 'en' => 'Subtitle under the name', 'type' => 'toggle', 'key' => 'header_logo_show_subtext',
        ],
        'subtitle_size' => [
            'group' => 'logo', 'tr' => 'Alt başlık boyutu', 'en' => 'Subtitle size', 'type' => 'choice', 'key' => 'header_logo_subtext_size', 'default' => 'small',
            'choices' => [
                'small'  => ['tr' => 'Küçük', 'en' => 'Small'],
                'normal' => ['tr' => 'Normal', 'en' => 'Normal'],
            ],
        ],
        'menu_style' => [
            'group' => 'menu', 'tr' => 'Menü düzeni', 'en' => 'Menu layout', 'type' => 'choice', 'ui' => 'visual', 'key' => 'header_style', 'default' => 'hamburger',
            'choices' => [
                'horizontal'        => ['tr' => 'Üstte, sağda', 'en' => 'Top, right', 'writes' => $menu_writes('horizontal')],
                'horizontal_center' => ['tr' => 'Üstte, ortada', 'en' => 'Top, centred', 'writes' => $menu_writes('horizontal_center')],
                'stacked_center'    => ['tr' => 'Ortada, ismin altında', 'en' => 'Centred under the name', 'writes' => $menu_writes('stacked_center')],
                'horizontal_left'   => ['tr' => 'Üstte, solda', 'en' => 'Top, left', 'writes' => $menu_writes('horizontal_left')],
                'hamburger'         => ['tr' => 'Hamburger ☰', 'en' => 'Hamburger ☰', 'writes' => $menu_writes('hamburger')],
                'vertical_left'     => ['tr' => 'Sol kenarda', 'en' => 'Left sidebar', 'writes' => $menu_writes('vertical_left')],
            ],
        ],
        'nav_uppercase' => [
            'group' => 'menu', 'tr' => 'Menü büyük harf', 'en' => 'Uppercase menu', 'type' => 'toggle', 'key' => 'header_nav_uppercase',
        ],
        'footer' => [
            'group' => 'footer', 'tr' => 'Alt bilgi (footer)', 'en' => 'Footer', 'type' => 'toggle', 'key' => 'footer_enabled', 'default' => '1',
        ],
        'grid_columns' => [
            'group' => 'home', 'tr' => 'Izgarada sütun sayısı', 'en' => 'Grid columns', 'type' => 'choice', 'key' => 'tpl_grid_columns', 'default' => '4',
            'choices' => [
                '3' => ['tr' => '3', 'en' => '3'], '4' => ['tr' => '4', 'en' => '4'],
                '5' => ['tr' => '5', 'en' => '5'], '6' => ['tr' => '6', 'en' => '6'],
            ],
        ],
        'wall_columns' => [
            'group' => 'home', 'tr' => 'Duvarda sütun sayısı', 'en' => 'Wall columns', 'type' => 'choice', 'key' => 'tpl_wall_columns', 'default' => '3',
            'choices' => [
                '2' => ['tr' => '2', 'en' => '2'], '3' => ['tr' => '3', 'en' => '3'],
                '4' => ['tr' => '4', 'en' => '4'], '5' => ['tr' => '5', 'en' => '5'],
            ],
        ],
        'card_columns' => [
            'group' => 'home', 'tr' => 'Sütun sayısı', 'en' => 'Columns', 'type' => 'choice', 'read' => 'hp_grid_columns', 'default' => '3',
            'choices' => [
                '2' => ['tr' => '2 (büyük kartlar)', 'en' => '2 (large cards)', 'writes' => ['hp_grid_columns' => '2', 'pf_grid_columns' => '2']],
                '3' => ['tr' => '3', 'en' => '3', 'writes' => ['hp_grid_columns' => '3', 'pf_grid_columns' => '3']],
            ],
        ],
        'thumb_crop' => [
            'group' => 'home', 'tr' => 'Küçük resimler', 'en' => 'Thumbnails', 'type' => 'choice', 'key' => 'tpl_thumb_crop', 'default' => 'square',
            'choices' => [
                'square'   => ['tr' => 'Kare kırp', 'en' => 'Square crop'],
                'original' => ['tr' => 'Orijinal oran', 'en' => 'Original ratio'],
            ],
        ],
        'home_source' => [
            'group' => 'home', 'tr' => 'Ana sayfada ne görünsün', 'en' => 'Homepage shows', 'type' => 'choice', 'key' => 'tpl_home_source', 'default' => 'slides',
            'choices' => [
                'all'    => ['tr' => 'Bütün çalışmalar', 'en' => 'All work'],
                'slides' => ['tr' => 'Ana sayfa seçkisi', 'en' => 'Homepage selection'],
            ],
        ],
        'slide_autoplay' => [
            'group' => 'home', 'tr' => 'Slaytlar kendiliğinden geçsin', 'en' => 'Autoplay slides', 'type' => 'toggle', 'key' => 'hp_autoplay', 'default' => '1',
        ],
        'slide_caption' => [
            'group' => 'home', 'tr' => 'Fotoğraf başlığını göster', 'en' => 'Show photo title', 'type' => 'toggle', 'key' => 'hp_show_title', 'default' => '1',
        ],
        'slide_overlay' => [
            'group' => 'home', 'tr' => 'Fotoğraf üstü karartma', 'en' => 'Image shading', 'type' => 'choice', 'key' => 'hp_overlay_style', 'default' => 'bottom_gradient',
            'choices' => [
                'bottom_gradient' => ['tr' => 'Alttan geçişli', 'en' => 'Bottom gradient'],
                'soft_scrim'      => ['tr' => 'Hafif', 'en' => 'Soft'],
                'none'            => ['tr' => 'Yok', 'en' => 'None'],
            ],
        ],
    ];
}

/** Etkin ana şablon (kayıtlı değilse varsayılan) */
function site_template_active(): string {
    $id = (string)get_setting(SITE_TEMPLATE_KEY, SITE_TEMPLATE_DEFAULT);
    return isset(site_templates()[$id]) ? $id : SITE_TEMPLATE_DEFAULT;
}

/**
 * Bir seçeneğin değeri, verilen ayar okuyucusuna göre.
 * $get(string $key, string $default): string
 */
function site_template_option_value_from(string $opt, callable $get): string {
    $def = site_template_options()[$opt] ?? null;
    if (!$def) return '';
    if ($opt === 'surface') {
        $tone = (string)$get(SITE_SURFACE_TONE_KEY, '');
        $page = (string)$get('hp_default_surface', 'dark');
        if ($tone === 'gray') return 'gray';
        if ($tone === 'paper' || $page === 'paper') return 'paper';
        return $page === 'white' ? 'white' : 'dark';
    }
    $key = $def['key'] ?? ($def['read'] ?? '');
    $val = $key === '' ? '' : (string)$get($key, $def['default'] ?? ($def['type'] === 'toggle' ? '0' : ''));
    // Kayıtlı değer seçeneklerde yoksa (ör. panelin gelişmiş kısmında seçilmiş başka bir menü) varsayılanı göster
    if ($def['type'] === 'choice' && !isset($def['choices'][$val])) {
        $val = (string)($def['default'] ?? array_key_first($def['choices']));
    }
    return $val;
}

/** Bir seçeneğin sitede şu an kayıtlı değeri */
function site_template_option_value(string $opt): string {
    return site_template_option_value_from($opt, fn(string $k, string $d): string => (string)get_setting($k, $d));
}

/** Bir temaya geçilince seçeneğin alacağı değer: etkin temada kayıtlı değer, değilse temanın kendi değeri */
function site_template_option_value_for(string $tid, string $opt): string {
    if ($tid === site_template_active()) return site_template_option_value($opt);
    $set = site_templates()[$tid]['settings'] ?? [];
    return site_template_option_value_from($opt, fn(string $k, string $d): string => array_key_exists($k, $set) ? (string)$set[$k] : (string)get_setting($k, $d));
}

/** Özelleştirme ekranındaki bölümler (sıra = ekrandaki sıra) */
function site_template_option_groups(): array {
    return [
        'color'  => ['tr' => 'Renkler', 'en' => 'Colours', 'desc_tr' => 'Sitenin zemin rengi', 'desc_en' => 'Site background colour'],
        'logo'   => ['tr' => 'Site adı ve logo', 'en' => 'Site title and logo', 'desc_tr' => 'Sol üstte ne görüneceği', 'desc_en' => 'What appears top left'],
        'menu'   => ['tr' => 'Menü', 'en' => 'Menu', 'desc_tr' => 'Menünün yeri ve görünüşü', 'desc_en' => 'Where the menu sits and how it looks'],
        'home'   => ['tr' => 'Ana sayfa', 'en' => 'Homepage', 'desc_tr' => 'Ana sayfada ne ve nasıl görüneceği', 'desc_en' => 'What the homepage shows and how'],
        'footer' => ['tr' => 'Alt bilgi', 'en' => 'Footer', 'desc_tr' => 'Sayfa altındaki bilgi şeridi', 'desc_en' => 'The strip at the bottom of pages'],
    ];
}

/**
 * Önizleme / yayınlama için: temanın ayarları + seçilen seçeneklerin yazdıkları.
 * Yalnızca o temaya ait, geçerli değerli seçenekler dikkate alınır.
 */
function site_template_settings_with_options(string $tid, array $opts): array {
    $t = site_templates()[$tid] ?? null;
    if (!$t) return [];
    $out = $t['settings'] + [SITE_TEMPLATE_KEY => $tid];
    foreach ($t['options'] as $opt) {
        if (!array_key_exists($opt, $opts)) continue;
        foreach (site_template_option_writes($opt, (string)$opts[$opt]) as $k => $v) {
            $out[$k] = $v;
        }
    }
    return $out;
}

/** Bir seçenek değerinin yazacağı ayarlar (geçersiz değerde boş dizi) */
function site_template_option_writes(string $opt, string $value): array {
    $def = site_template_options()[$opt] ?? null;
    if (!$def) return [];
    if ($def['type'] === 'toggle') {
        return [$def['key'] => $value === '1' ? '1' : '0'];
    }
    if (!isset($def['choices'][$value])) return [];
    if (isset($def['choices'][$value]['writes'])) return $def['choices'][$value]['writes'];
    return [$def['key'] => $value];
}

/**
 * Tema bilgi penceresinde gösterilen özet: [etiket_tr, etiket_en, değer_tr, değer_en]
 */
function site_template_info(string $tid): array {
    $L = ['logo' => ['Logo', 'Logo'], 'menu' => ['Menü', 'Menu'], 'home' => ['Ana sayfa', 'Homepage'], 'list' => ['Çalışma listeleri', 'Work lists'], 'project' => ['Proje sayfası', 'Project page'], 'best' => ['En uygun', 'Best for']];
    $map = [
        'sidebar_grid' => [
            'logo' => ['Sol üstte isim (yazı), altında küçük alt başlık', 'Name top left (text) with a small subtitle'],
            'menu' => ['Solda, dikey ve sabit', 'Left, vertical and fixed'],
            'home' => ['Bütün çalışmalar küçük kare resimler hâlinde', 'All work as small square thumbnails'],
            'list' => ['Aynı küçük resim ızgarası', 'The same thumbnail grid'],
            'project' => ['Açılış fotoğrafı, ardından altyazılı fotoğraf dizisi', 'Opening photo, then a captioned sequence'],
            'best' => ['Çok sayıda işi tek bakışta göstermek', 'Showing a lot of work at a glance'],
        ],
        'sidebar_single' => [
            'logo' => ['Sol üstte isim (yazı), altında küçük alt başlık', 'Name top left (text) with a small subtitle'],
            'menu' => ['Solda, dikey ve sabit', 'Left, vertical and fixed'],
            'home' => ['Seçki fotoğrafları tek tek, kırpılmadan büyük', 'Selected photos one at a time, large and uncropped'],
            'list' => ['Tek büyük fotoğraf, ileri ve geri', 'One large photo with next and previous'],
            'project' => ['Galeri duvarı gibi sade, kırpılmamış fotoğraflar', 'Plain, uncropped photos like a gallery wall'],
            'best' => ['Her fotoğrafın tek başına görülmesi', 'Letting each photograph stand alone'],
        ],
        'top_slideshow' => [
            'logo' => ['Sol üstte, fotoğrafın üzerinde', 'Top left, over the photo'],
            'menu' => ['Üstte, sağda; fotoğrafın üzerinde', 'Top right, over the photo'],
            'home' => ['Tam ekran slayt, altta fotoğrafın başlığı', 'Fullscreen slideshow with the photo title'],
            'list' => ['Her proje bir tam ekran bölüm', 'Each project as a fullscreen chapter'],
            'project' => ['Büyük açılış, ardından tek tek fotoğraflar', 'Large opening, then photos one by one'],
            'best' => ['Güçlü tek kareleri sinematik göstermek', 'Showing strong single frames cinematically'],
        ],
        'tiled_grid' => [
            'logo' => ['Sol üstte isim', 'Name top left'],
            'menu' => ['Üstte, sağda', 'Top right'],
            'home' => ['Proje kapakları eşit kutularda, başlık üzerine gelince', 'Project covers in equal tiles, titles on hover'],
            'list' => ['Aynı kapak ızgarası', 'The same cover grid'],
            'project' => ['Başlık, açılış fotoğrafı ve metin; ardından fotoğraflar', 'Title, opening photo and text, then photos'],
            'best' => ['Proje odaklı, düzenli bir vitrin', 'A tidy, project-led showcase'],
        ],
        'horizontal_strip' => [
            'logo' => ['Sol üstte isim', 'Name top left'],
            'menu' => ['Üstte, ismin yanında solda', 'Top left, next to the name'],
            'home' => ['Fotoğraflar aynı yükseklikte yan yana, yatay kayar', 'Photos side by side at equal height, scrolling sideways'],
            'list' => ['Aynı yatay şerit', 'The same horizontal strip'],
            'project' => ['Fotoğraflar yatay şerit hâlinde', 'Photos in a horizontal strip'],
            'best' => ['Diziyi kitap sayfası gibi çevirtmek', 'Leafing through a series like a book'],
        ],
        'vertical_stream' => [
            'logo' => ['Sol üstte isim', 'Name top left'],
            'menu' => ['Üstte, sağda', 'Top right'],
            'home' => ['Büyük fotoğraflar alt alta, aralarında başlık ve kısa metin', 'Large photos down the page with titles and short text'],
            'list' => ['Aynı dikey akış', 'The same vertical stream'],
            'project' => ['Başlık ve metin, ardından alt alta fotoğraflar', 'Title and text, then photos one below another'],
            'best' => ['Hikâye anlatan belgesel işler', 'Documentary work that tells a story'],
        ],
        'text_index' => [
            'logo' => ['Sol üstte isim', 'Name top left'],
            'menu' => ['Üstte, ismin yanında solda', 'Top left, next to the name'],
            'home' => ['Proje adlarından oluşan liste, üzerine gelince küçük önizleme', 'A list of project names with a small preview on hover'],
            'list' => ['Aynı yazı listesi', 'The same text list'],
            'project' => ['Başlık, açılış fotoğrafı ve metin; ardından fotoğraflar', 'Title, opening photo and text, then photos'],
            'best' => ['Sade, metin ağırlıklı bir giriş', 'A quiet, text-led entrance'],
        ],
        'mosaic_wall' => [
            'logo' => ['Sol üstte isim', 'Name top left'],
            'menu' => ['Üstte, sağda', 'Top right'],
            'home' => ['Farklı boyutlarda kapaklar boşluksuz bir duvar oluşturur', 'Covers of varying sizes form a dense wall'],
            'list' => ['Aynı mozaik duvar', 'The same mosaic wall'],
            'project' => ['Büyük açılış, ardından taşma ızgarada fotoğraflar', 'Large opening, then photos in a masonry grid'],
            'best' => ['Enerjik, çok renkli seçkiler', 'Energetic, varied selections'],
        ],
        'archive' => [
            'logo' => ['Sol üstte isim', 'Name top left'],
            'menu' => ['Üstte, sağda', 'Top right'],
            'home' => ['Projeler yıl başlıkları altında, küçük kapak ve başlıkla', 'Projects under year headings with a small cover and title'],
            'list' => ['Aynı yıllara göre arşiv', 'The same year archive'],
            'project' => ['Açılış fotoğrafı, ardından altyazılı fotoğraf dizisi', 'Opening photo, then a captioned sequence'],
            'best' => ['Uzun yıllara yayılan bir arşiv', 'An archive spanning many years'],
        ],
        'photobook' => [
            'logo' => ['Sol üstte isim', 'Name top left'],
            'menu' => ['Üstte, ismin yanında solda', 'Top left, next to the name'],
            'home' => ['Projeler kitap sayfası gibi ikişerli açılımlarda', 'Projects as two-page book spreads'],
            'list' => ['Aynı kitap açılımları', 'The same book spreads'],
            'project' => ['Ortalanmış açılış, fotoğraflar kitap sayfası düzeninde', 'Centred opening, photos laid out like book pages'],
            'best' => ['Basılı kitap hissi veren seriler', 'Series that should feel like a printed book'],
        ],
        'top_square_grid' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Üstte, sağda', 'Top right'],
            'home' => ['Bütün çalışmalar eşit kare küçük resimlerle ortalı bir ızgarada', 'All work as equal square thumbnails in a centred grid'],
            'list' => ['Aynı kare ızgara', 'The same square grid'],
            'project' => ['Başlık, fotoğraf ve metin; ardından altyazılı fotoğraf dizisi', 'Title, photo and text, then a captioned sequence'],
            'best' => ['Renkli, düzenli seriler ve çok sayıda iş', 'Colourful, orderly series and lots of work'],
        ],
        'sidebar_strip_captioned' => [
            'logo' => ['Sol üstte isim, altında küçük alt başlık', 'Name top left with a small subtitle'],
            'menu' => ['Solda, dikey ve sabit', 'Left, vertical and fixed'],
            'home' => ['Büyük fotoğraflar yan yana yatay kayar, altlarında proje adı', 'Large photos scroll sideways with the project name below'],
            'list' => ['Aynı yatay galeri', 'The same horizontal gallery'],
            'project' => ['Fotoğraflar yatay şerit hâlinde', 'Photos in a horizontal strip'],
            'best' => ['Dergi ve moda gibi başlıklı seçkiler', 'Titled selections such as editorial and fashion'],
        ],
        'sidebar_strip_dark' => [
            'logo' => ['Sol üstte isim, altında küçük alt başlık', 'Name top left with a small subtitle'],
            'menu' => ['Solda, dikey ve sabit; koyu zeminde', 'Left, vertical and fixed, on dark'],
            'home' => ['Ekran yüksekliğinde fotoğraflar yatay kayar', 'Full-height photos scroll sideways'],
            'list' => ['Aynı yatay şerit, altında proje adı', 'The same strip with project names'],
            'project' => ['Fotoğraflar yatay şerit hâlinde', 'Photos in a horizontal strip'],
            'best' => ['Manzara ve gece fotoğrafı gibi atmosferik işler', 'Atmospheric work such as landscape and night'],
        ],
        'sidebar_strip' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Solda, dikey ve sabit', 'Left, vertical and fixed'],
            'home' => ['Fotoğraflar yazısız, yalın bir şerit hâlinde yatay kayar', 'Photos in a plain, text-free sideways strip'],
            'list' => ['Aynı yatay şerit', 'The same horizontal strip'],
            'project' => ['Fotoğraflar yatay şerit hâlinde', 'Photos in a horizontal strip'],
            'best' => ['Sade, nefes alan bir sunum', 'A quiet, airy presentation'],
        ],
        'sidebar_wall' => [
            'logo' => ['Sol üstte isim, altında küçük alt başlık', 'Name top left with a small subtitle'],
            'menu' => ['Solda, dikey ve sabit', 'Left, vertical and fixed'],
            'home' => ['Farklı boylarda fotoğraflar kırpılmadan sütunlar hâlinde', 'Photos of varying heights in uncropped columns'],
            'list' => ['Aynı fotoğraf duvarı', 'The same photo wall'],
            'project' => ['Galeri duvarı gibi sade açılış, ardından fotoğraf duvarı', 'A plain gallery-wall opening, then a photo wall'],
            'best' => ['Dikey ve yatay fotoğrafların karışık olduğu seriler', 'Series mixing portrait and landscape photos'],
        ],
        'staggered' => [
            'logo' => ['Ortada isim', 'Name centred'],
            'menu' => ['Üstte, ortada, ismin altında', 'Top centre, under the name'],
            'home' => ['Fotoğraflar kırpılmadan, farklı genişlik ve kaydırmalarla', 'Uncropped photos in varying widths and offsets'],
            'list' => ['Aynı kademeli galeri', 'The same staggered gallery'],
            'project' => ['Başlık, fotoğraf ve metin; ardından tek tek fotoğraflar', 'Title, photo and text, then photos one by one'],
            'best' => ['Sakin, galeri duvarı hissi isteyen seçkiler', 'Calm selections that should feel like a gallery wall'],
        ],
        'one_two' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Sağ üstte “Menü” düğmesi; tıklayınca açılır', 'A “Menu” button top right that opens the menu'],
            'home' => ['Üçlü bloklar: bir büyük, iki küçük fotoğraf', 'Blocks of one large and two small photos'],
            'list' => ['Aynı bloklar', 'The same blocks'],
            'project' => ['Solda fotoğraf, sağda metin; ardından iki sütun', 'Photo left, text right, then two columns'],
            'best' => ['Güçlü kapak fotoğrafları olan projeler', 'Projects with strong cover photos'],
        ],
        'sidebar_featured' => [
            'logo' => ['Sol üstte isim, altında küçük alt başlık', 'Name top left with a small subtitle'],
            'menu' => ['Solda, dikey ve sabit', 'Left, vertical and fixed'],
            'home' => ['En son çalışma büyük, altında diğerleri küçük kareler', 'The latest work large, the rest as small squares'],
            'list' => ['Aynı düzen', 'The same layout'],
            'project' => ['Galeri duvarı gibi sade açılış, ardından altyazılı dizi', 'A plain gallery-wall opening, then a captioned sequence'],
            'best' => ['Öne çıkarmak istediğiniz son bir iş varken', 'Leading with one recent work'],
        ],
        'statement_wall' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Sağ üstte “Menü” düğmesi', 'A “Menu” button top right'],
            'home' => ['Büyük puntolu tanıtım cümlesi, altında iki sütun fotoğraf', 'A large intro sentence above two columns of photos'],
            'list' => ['Fotoğraf duvarı, başlıklarla', 'A photo wall with titles'],
            'project' => ['Başlık, fotoğraf ve metin; ardından fotoğraf duvarı', 'Title, photo and text, then a photo wall'],
            'best' => ['Kim olduğunu tek cümleyle anlatmak isteyenler', 'Saying who you are in one sentence'],
        ],
        'zigzag' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Sağ üstte çizgi düğmesi; tıklayınca açılır', 'A lines button top right that opens the menu'],
            'home' => ['Kırpılmamış fotoğraflar, bir solda bir sağda', 'Uncropped photos, alternating left and right'],
            'list' => ['Aynı zikzak akış', 'The same zigzag stream'],
            'project' => ['Galeri duvarı gibi sade açılış, ardından tek tek fotoğraflar', 'A plain gallery-wall opening, then photos one by one'],
            'best' => ['Tek tek bakılması gereken güçlü kareler', 'Strong single frames worth a long look'],
        ],
        'split_screen' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Sağ üstte “Menü” düğmesi', 'A “Menu” button top right'],
            'home' => ['Her proje bir ekran: yarısı fotoğraf, yarısı yazı', 'One screen per project: half photo, half text'],
            'list' => ['Aynı bölünmüş ekranlar', 'The same split screens'],
            'project' => ['Solda fotoğraf, sağda metin; ardından tek tek fotoğraflar', 'Photo left, text right, then photos one by one'],
            'best' => ['Her projesini birkaç cümleyle anlatmak isteyenler', 'Introducing each project in a few sentences'],
        ],
        'magazine_stories' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Sağ üstte çizgi düğmesi', 'A lines button top right'],
            'home' => ['Dergi sayfaları: çerçeveli fotoğraf ve ince başlık', 'Magazine pages: a framed photo and a light title'],
            'list' => ['Aynı dergi sayfaları', 'The same magazine pages'],
            'project' => ['Başlık, fotoğraf ve metin; ardından tek tek fotoğraflar', 'Title, photo and text, then photos one by one'],
            'best' => ['Belgesel ve hikâye anlatan seriler', 'Documentary and story-driven series'],
        ],
        'three_panels' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Sağ üstte çizgi düğmesi', 'A lines button top right'],
            'home' => ['Ekranı bölen üç dikey proje paneli', 'Three tall project panels across the screen'],
            'list' => ['Aynı paneller, üçerli sıralar', 'The same panels in rows of three'],
            'project' => ['Tam ekran açılış, ardından altyazılı dizi', 'A full-screen opening, then a captioned sequence'],
            'best' => ['Üç-altı ana projesi olan portfolyolar', 'Portfolios with three to six main projects'],
        ],
        'section_banners' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Sağ üstte “Menü” düğmesi', 'A “Menu” button top right'],
            'home' => ['Menüdeki her bölüm için geniş bir afiş', 'A wide banner for each menu section'],
            'list' => ['Projeler afiş afiş, alt alta', 'Projects as banners, one below another'],
            'project' => ['Solda fotoğraf, sağda metin; ardından iki sütun', 'Photo left, text right, then two columns'],
            'best' => ['İşlerini birkaç ana bölüme ayıranlar', 'Work organised into a few main sections'],
        ],
        'light_table' => [
            'logo' => ['Sol üstte isim (yazı)', 'Name top left (text)'],
            'menu' => ['Üstte, sağda', 'Top right'],
            'home' => ['Tek büyük fotoğraf, sağda küçük resim şeridi', 'One large photo with a thumbnail strip on the right'],
            'list' => ['Aynı ışık masası, projelerin kapaklarıyla', 'The same light table with project covers'],
            'project' => ['Tam ekran açılış, ardından galeri duvarı', 'A full-screen opening, then a gallery wall'],
            'best' => ['Koyu zeminde fotoğrafları tek tek göstermek', 'Showing photos one at a time on a dark background'],
        ],
        'bottom_bar' => [
            'logo' => ['Ana sayfada sol altta isim', 'Name bottom left on the homepage'],
            'menu' => ['Ana sayfada altta, sağda; iç sayfalarda üstte', 'Bottom right on the homepage, top on inner pages'],
            'home' => ['Yazısız tam ekran slayt', 'A fullscreen slideshow without captions'],
            'list' => ['Her proje bir tam ekran bölüm', 'Each project as a fullscreen chapter'],
            'project' => ['Tam ekran açılış, ardından tek tek fotoğraflar', 'A full-screen opening, then photos one by one'],
            'best' => ['Fotoğrafın ekranı kesintisiz doldurması', 'Letting the photo fill the screen uninterrupted'],
        ],
    ];
    $out = [];
    foreach ($map[$tid] ?? [] as $k => $v) {
        $out[] = [$L[$k][0], $L[$k][1], $v[0], $v[1]];
    }
    return $out;
}
