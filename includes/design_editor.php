<?php
/**
 * includes/design_editor.php
 * Tasarım düzenleyici (Temalar › Özelleştir): bölümler, alan tanımları ve yayınlanmamış taslak.
 *
 * - design_editor_sections(): düzenleyicinin sol listesindeki bölümler. Her bölüm; temanın kendi
 *   seçenekleri (site_template_options() 'group') ve/veya buradaki alanlardan (fields) oluşur.
 *   Alanlar Format'taki gibi alt başlıklarda toplanır: options (Seçenekler), colors (Renkler), type (Yazı tipi)…
 * - Taslak `design_draft` ayarında tutulur: {"theme": id, "opts": {...}, "values": {ayar: değer}}.
 *   Önizleme çerezi 'draft' iken yönetici siteyi taslakla görür (style_presets.php → style_preview_overrides()).
 * - Yayınla: taslağın yazacağı ayarlar kaydedilir, taslak silinir (admin/templates.php → design_publish).
 */

require_once __DIR__ . '/site_templates.php';

const DESIGN_DRAFT_KEY = 'design_draft';

/** Yazı tipi seçenekleri: sitenin yüklediği aileler (fonts.css, main.css) ve sistem yazı tipleri */
function design_font_choices(): array {
    return [
        'display' => ['tr' => 'Zed Display (başlık)', 'en' => 'Zed Display', 'stack' => "'ZedDisplayFtl', 'Space Grotesk', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"],
        'sans'    => ['tr' => 'Zed Text (metin)', 'en' => 'Zed Text', 'stack' => "'ZedTextFtl', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif"],
        'grotesk' => ['tr' => 'Space Grotesk', 'en' => 'Space Grotesk', 'stack' => "'Space Grotesk', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"],
        'serif'   => ['tr' => 'Tırnaklı (Georgia)', 'en' => 'Serif (Georgia)', 'stack' => "Georgia, 'Baskerville', 'Times New Roman', serif"],
        'mono'    => ['tr' => 'Daktilo (JetBrains Mono)', 'en' => 'Monospace (JetBrains Mono)', 'stack' => "'Jetbrains Mono', ui-monospace, Menlo, Consolas, monospace"],
        'system'  => ['tr' => 'Sistem yazı tipi', 'en' => 'System font', 'stack' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif"],
    ];
}

function design_font_stack(string $key, string $fallback = 'display'): string {
    $c = design_font_choices();
    return ($c[$key] ?? $c[$fallback])['stack'];
}

/**
 * Alan tanımları.
 * type   : text | toggle | choice | slider | color | font | image
 * sub    : bölüm içindeki alt başlık (design_editor_subs())
 * show_if: ['ayar' => [değerler], …] — hepsi sağlanınca gösterilir (ekranda canlı değerlendirilir)
 */
function design_editor_fields(): array {
    static $all = null;
    if ($all === null) {
        $all = design_i18n_fields(design_editor_fields_base()) + design_listing_fields('hp', 'home') + design_listing_fields('pf', 'lists') + design_page_fields();
    }
    return $all;
}

/**
 * Türkçe/İngilizce çiftli yazı alanlarına sitedeki diğer diller de eklenir (ör. header_cta_text_hy),
 * İngilizce alanın hemen ardına. Boş bırakılan dilde sitede yedek dildeki yazı görünür (setting_l).
 */
function design_i18n_fields(array $fields): array {
    $extra = [];
    foreach (get_active_languages() as $l) {
        if (!in_array($l['code'], ['tr', 'en'], true)) $extra[] = $l;
    }
    if (!$extra) return $fields;
    $out = [];
    foreach ($fields as $k => $f) {
        $out[$k] = $f;
        if (substr($k, -3) === '_en' && isset($fields[substr($k, 0, -3) . '_tr']) && ($f['type'] ?? '') === 'text') {
            $base = substr($k, 0, -3);
            foreach ($extra as $l) {
                $c = (string)$l['code'];
                $nf = $f;
                $nf['default'] = '';
                $nf['tr'] = preg_replace('/\(İngilizce\)/u', '(' . $l['name_native'] . ')', $f['tr']);
                $nf['en'] = preg_replace('/\(English\)/u', '(' . $l['name_native'] . ')', $f['en']);
                $nf['help_tr'] = sprintf('Boş bırakılırsa sitede %s yazı görünür.', strtoupper((string)($l['fallback_lang'] ?: 'en')));
                $nf['help_en'] = sprintf('If left empty, the %s text is shown on the site.', strtoupper((string)($l['fallback_lang'] ?: 'en')));
                $out[$base . '_' . $c] = $nf;
            }
        }
    }
    return $out;
}

/** Genel alanlar: renkler, yazı tipleri, logo, menü, alt bilgi */
function design_editor_fields_base(): array {
    $logo_types = site_template_options()['logo_type']['choices'] + [
        'full_text' => ['tr' => 'Tam logo + yazı', 'en' => 'Full logo + text'],
        'none'      => ['tr' => 'Hiçbiri', 'en' => 'None'],
    ];
    $text_logo  = ['header_logo_type' => ['text_only', 'symbol_text', 'full_text']];
    return [
        // ── RENKLER › TEMEL ──
        'site_surface_custom' => [
            'section' => 'color', 'sub' => 'basic', 'type' => 'color', 'default' => '', 'light_only' => true, 'auto_tr' => 'Seçili ton', 'auto_en' => 'Selected tone',
            'tr' => 'Özel zemin rengi', 'en' => 'Custom background colour',
            'help_tr' => 'Beyaz, kâğıt ya da gri yerine kendi açık tonunuz. Koyu tonlar kabul edilmez; koyu zemin için “Siyah”ı seçin.', 'help_en' => 'Your own light tone instead of white, paper or gray. Dark tones are not accepted; choose “Black” for a dark site.',
            'show_if' => ['surface' => ['white', 'paper', 'gray']],
        ],
        'site_text_color' => [
            'section' => 'color', 'sub' => 'basic', 'type' => 'color', 'default' => '',
            'tr' => 'Yazı rengi', 'en' => 'Text colour',
            'help_tr' => 'Başlık, paragraf ve listelerin rengi. Fotoğrafların üzerindeki yazılar değişmez. Ziyaretçi açık/koyu düğmesiyle zemini değiştirirse otomatik renge döner.', 'help_en' => 'Colour of headings, paragraphs and lists. Text over photographs is unchanged. Returns to automatic if a visitor switches light/dark.',
        ],
        'site_accent_color' => [
            'section' => 'color', 'sub' => 'basic', 'type' => 'color', 'default' => '', 'auto_tr' => 'Sarı (varsayılan)', 'auto_en' => 'Yellow (default)',
            'tr' => 'Vurgu rengi', 'en' => 'Accent colour',
            'help_tr' => 'Menü düğmesi, bağlantı çizgileri, etiketler ve seçili öğelerde kullanılan renk.', 'help_en' => 'Used for the menu button, link underlines, labels and selected items.',
        ],
        // ── YAZI TİPLERİ › TEMEL ──
        'site_heading_font' => [
            'section' => 'type', 'sub' => 'basic', 'type' => 'font', 'default' => 'display',
            'tr' => 'Başlık yazı tipi', 'en' => 'Heading font',
            'help_tr' => 'Proje adları, sayfa başlıkları ve büyük yazılar.', 'help_en' => 'Project names, page titles and large text.',
        ],
        'site_body_font' => [
            'section' => 'type', 'sub' => 'basic', 'type' => 'font', 'default' => 'sans',
            'tr' => 'Metin yazı tipi', 'en' => 'Body font',
            'help_tr' => 'Paragraflar, açıklamalar ve menü dışındaki metinler.', 'help_en' => 'Paragraphs, descriptions and other running text.',
        ],
        'site_font_scale' => [
            'section' => 'type', 'sub' => 'basic', 'type' => 'slider', 'min' => 85, 'max' => 120, 'step' => 5, 'unit' => '%', 'default' => '100',
            'tr' => 'Temel yazı boyutu', 'en' => 'Base font size',
            'help_tr' => 'Sitedeki bütün yazıları aynı oranda büyütür ya da küçültür.', 'help_en' => 'Scales all text on the site proportionally.',
        ],
        // ── DÜĞMELER VE FORMLAR (layout_header.php › er-design-tokens; "theme" = temanın kendi görünümü) ──
        'site_button_shape' => [
            'section' => 'buttons', 'sub' => 'basic', 'type' => 'choice', 'default' => 'theme',
            'tr' => 'Düğme köşeleri', 'en' => 'Button corners',
            'help_tr' => '404, iletişim formu, parolalı sayfa ve Hakkımda düğmeleri.', 'help_en' => '404, contact form, password page and About buttons.',
            'choices' => design_choices(['theme' => ['Tema', 'Theme'], 'square' => ['Köşeli', 'Square'], 'rounded' => ['Hafif yuvarlak', 'Rounded'], 'pill' => ['Hap', 'Pill']]),
        ],
        'site_button_style' => [
            'section' => 'buttons', 'sub' => 'basic', 'type' => 'choice', 'default' => 'theme',
            'tr' => 'Düğme görünümü', 'en' => 'Button style',
            'choices' => design_choices(['theme' => ['Tema', 'Theme'], 'filled' => ['Dolu (vurgu rengi)', 'Filled (accent)'], 'outline' => ['Çerçeveli', 'Outline']]),
        ],
        'site_button_case' => [
            'section' => 'buttons', 'sub' => 'basic', 'type' => 'choice', 'default' => 'theme',
            'tr' => 'Düğme yazısı', 'en' => 'Button text',
            'choices' => design_choices(['theme' => ['Tema', 'Theme'], 'normal' => ['Normal', 'Normal'], 'uppercase' => ['BÜYÜK HARF', 'UPPERCASE']]),
        ],
        'site_form_style' => [
            'section' => 'buttons', 'sub' => 'basic', 'type' => 'choice', 'default' => 'theme',
            'tr' => 'Form alanları', 'en' => 'Form fields',
            'help_tr' => 'İletişim formu ve parola alanının görünümü. Köşeler düğmelerle aynı olur.', 'help_en' => 'How the contact form and password field look. Corners match the buttons.',
            'choices' => design_choices(['theme' => ['Tema', 'Theme'], 'boxed' => ['Kutu', 'Box'], 'underline' => ['Alt çizgi', 'Underline']]),
        ],
        // ── SİTE ADI VE LOGO ──
        'header_logo_type' => [
            'section' => 'logo', 'sub' => 'options', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'logo_type', 'default' => 'symbol',
            'tr' => 'Sol üstte ne görünsün', 'en' => 'What appears top left',
            'help_tr' => 'Yazı (isminiz), yüklediğiniz logo görseli ya da ikisi birlikte.', 'help_en' => 'Text (your name), your uploaded logo image, or both.',
            'choices' => $logo_types,
        ],
        'header_logo_text' => [
            'section' => 'logo', 'sub' => 'options', 'type' => 'text', 'default' => mb_strtoupper(site_owner_name()), 'max' => 80,
            'tr' => 'Logo yazısı', 'en' => 'Logo text',
            'help_tr' => 'Sol üstte görünen isim.', 'help_en' => 'The name shown top left.',
            'show_if' => $text_logo,
        ],
        'header_logo_show_subtext' => [
            'section' => 'logo', 'sub' => 'options', 'type' => 'toggle', 'default' => '0',
            'tr' => 'Altında alt başlık', 'en' => 'Subtitle underneath',
            'help_tr' => 'Logo yazısının altında küçük bir satır (ör. “Fotoğrafçı”).', 'help_en' => 'A small line under the logo text (e.g. “Documentary Photographer”).',
            'show_if' => $text_logo,
        ],
        'header_logo_subtext_tr' => [
            'section' => 'logo', 'sub' => 'options', 'type' => 'text', 'default' => 'BELGESEL FOTOĞRAFÇI', 'max' => 80,
            'tr' => 'Alt başlık (Türkçe)', 'en' => 'Subtitle (Turkish)',
            'show_if' => $text_logo + ['header_logo_show_subtext' => ['1']],
        ],
        'header_logo_subtext_en' => [
            'section' => 'logo', 'sub' => 'options', 'type' => 'text', 'default' => 'DOCUMENTARY PHOTOGRAPHER', 'max' => 80,
            'tr' => 'Alt başlık (İngilizce)', 'en' => 'Subtitle (English)',
            'show_if' => $text_logo + ['header_logo_show_subtext' => ['1']],
        ],
        'header_logo_size' => [
            'section' => 'logo', 'sub' => 'image', 'type' => 'slider', 'min' => 24, 'max' => 96, 'step' => 2, 'unit' => 'px', 'default' => '52',
            'tr' => 'Logo görseli boyutu', 'en' => 'Logo image size',
            'help_tr' => 'Simge ya da tam logo görselinin yüksekliği.', 'help_en' => 'Height of the symbol or full logo image.',
            'show_if' => ['header_logo_type' => ['symbol', 'symbol_text', 'full', 'full_text']],
        ],
        'brand_symbol_dark' => [
            'section' => 'logo', 'sub' => 'image', 'type' => 'image', 'default' => '', 'bg' => '#111111', 'fallback' => 'assets/brand/symbol-dark.svg',
            'tr' => 'Simge · koyu zeminde', 'en' => 'Symbol · on dark backgrounds',
            'show_if' => ['header_logo_type' => ['symbol', 'symbol_text']],
        ],
        'brand_symbol_light' => [
            'section' => 'logo', 'sub' => 'image', 'type' => 'image', 'default' => '', 'bg' => '#f4f4f2', 'fallback' => 'assets/brand/symbol-light.svg',
            'tr' => 'Simge · açık zeminde', 'en' => 'Symbol · on light backgrounds',
            'show_if' => ['header_logo_type' => ['symbol', 'symbol_text']],
        ],
        'brand_logo_dark' => [
            'section' => 'logo', 'sub' => 'image', 'type' => 'image', 'default' => '', 'bg' => '#111111', 'fallback' => 'assets/brand/symbol-dark.svg',
            'tr' => 'Tam logo · koyu zeminde', 'en' => 'Full logo · on dark backgrounds',
            'show_if' => ['header_logo_type' => ['full', 'full_text']],
        ],
        'brand_logo_light' => [
            'section' => 'logo', 'sub' => 'image', 'type' => 'image', 'default' => '', 'bg' => '#f4f4f2', 'fallback' => 'assets/brand/symbol-light.svg',
            'tr' => 'Tam logo · açık zeminde', 'en' => 'Full logo · on light backgrounds',
            'show_if' => ['header_logo_type' => ['full', 'full_text']],
        ],
        'header_logo_text_color' => [
            'section' => 'logo', 'sub' => 'colors', 'type' => 'color', 'default' => '',
            'tr' => 'Logo yazısı rengi', 'en' => 'Logo text colour',
            'help_tr' => '“Otomatik” zemine uyar: koyu zeminde açık, açık zeminde koyu.', 'help_en' => '“Automatic” follows the background: light on dark, dark on light.',
            'show_if' => $text_logo,
        ],
        'header_logo_subtext_color' => [
            'section' => 'logo', 'sub' => 'colors', 'type' => 'color', 'default' => '',
            'tr' => 'Alt başlık rengi', 'en' => 'Subtitle colour',
            'help_tr' => '“Otomatik” logo yazısıyla aynı rengin biraz soluğudur.', 'help_en' => '“Automatic” is the logo text colour, slightly faded.',
            'show_if' => $text_logo + ['header_logo_show_subtext' => ['1']],
        ],
        'header_logo_font' => [
            'section' => 'logo', 'sub' => 'type', 'type' => 'font', 'default' => '', 'inherit_tr' => 'Başlık yazı tipiyle aynı', 'inherit_en' => 'Same as heading font',
            'tr' => 'Logo yazı tipi', 'en' => 'Logo font',
            'show_if' => $text_logo,
        ],
        'header_logo_text_size' => [
            'section' => 'logo', 'sub' => 'type', 'type' => 'slider', 'min' => 12, 'max' => 44, 'step' => 1, 'unit' => 'px', 'default' => '19',
            'tr' => 'Logo yazısı boyutu', 'en' => 'Logo text size',
            'show_if' => $text_logo,
        ],
        'header_logo_text_weight' => [
            'section' => 'logo', 'sub' => 'type', 'type' => 'choice', 'default' => '800',
            'tr' => 'Kalınlık', 'en' => 'Weight',
            'choices' => ['400' => ['tr' => 'İnce', 'en' => 'Light'], '600' => ['tr' => 'Orta', 'en' => 'Medium'], '800' => ['tr' => 'Kalın', 'en' => 'Bold']],
            'show_if' => $text_logo,
        ],
        'header_logo_letter_spacing' => [
            'section' => 'logo', 'sub' => 'type', 'type' => 'choice', 'default' => 'normal',
            'tr' => 'Harf aralığı', 'en' => 'Letter spacing',
            'choices' => ['tight' => ['tr' => 'Dar', 'en' => 'Tight'], 'normal' => ['tr' => 'Normal', 'en' => 'Normal'], 'wide' => ['tr' => 'Geniş', 'en' => 'Wide']],
            'show_if' => $text_logo,
        ],
        'header_logo_subtext_size' => [
            'section' => 'logo', 'sub' => 'type', 'type' => 'choice', 'default' => 'small',
            'tr' => 'Alt başlık boyutu', 'en' => 'Subtitle size',
            'choices' => ['small' => ['tr' => 'Küçük', 'en' => 'Small'], 'normal' => ['tr' => 'Normal', 'en' => 'Normal']],
            'show_if' => $text_logo + ['header_logo_show_subtext' => ['1']],
        ],
        'header_logo_mobile_scale' => [
            'section' => 'logo', 'sub' => 'type', 'type' => 'slider', 'min' => 60, 'max' => 100, 'step' => 5, 'unit' => '%', 'default' => '100',
            'tr' => 'Telefonda boyut', 'en' => 'Size on phones',
            'help_tr' => 'Telefonda logo ve yazı bu oranda gösterilir.', 'help_en' => 'Logo and text are shown at this scale on phones.',
        ],
        'brand_favicon' => [
            'section' => 'logo', 'sub' => 'icon', 'type' => 'image', 'default' => '', 'bg' => '#f4f4f2', 'fallback' => 'assets/brand/favicon.png',
            'tr' => 'Tarayıcı sekmesindeki simge', 'en' => 'Browser tab icon',
            'help_tr' => 'Tarayıcı sekmesinde ve yer imlerinde görünür. Kare bir PNG ya da ICO önerilir.', 'help_en' => 'Shown in browser tabs and bookmarks. A square PNG or ICO is best.',
        ],
        // ── MENÜ › SEÇENEKLER ── (Menü düzeni: temanın 'menu_style' seçeneği)
        'header_home_position' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'choice', 'default' => 'top',
            'tr' => 'Ana sayfada menü çubuğu', 'en' => 'Menu bar on the homepage',
            'help_tr' => 'Altta: isim ve menü ana sayfada ekranın altında durur, fotoğraf tüm alanı kaplar. İç sayfalarda menü yine üsttedir. Sol kenar menüsünde ve ortalı menülerde etkisizdir.', 'help_en' => 'Bottom: the name and menu sit at the bottom of the homepage so the photo fills the screen. Inner pages keep the menu at the top. Has no effect with the sidebar or centred menus.',
            'choices' => ['top' => ['tr' => 'Üstte', 'en' => 'Top'], 'bottom' => ['tr' => 'Altta', 'en' => 'Bottom']],
        ],
        'header_container_width' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'choice', 'default' => 'full',
            'tr' => 'Menü genişliği', 'en' => 'Menu width',
            'help_tr' => 'Tam: ekranın iki kenarına yaslanır. Ortalı ve Dar: içerikle aynı genişlikte ortalanır.', 'help_en' => 'Full: edge to edge. Contained and Narrow: centred to the content width.',
            'choices' => ['full' => ['tr' => 'Tam', 'en' => 'Full'], 'contained' => ['tr' => 'Ortalı', 'en' => 'Contained'], 'narrow' => ['tr' => 'Dar', 'en' => 'Narrow']],
        ],
        'header_pos_inner' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'choice', 'default' => 'absolute',
            'tr' => 'Sayfa kaydırılınca', 'en' => 'When the page scrolls',
            'help_tr' => 'İç sayfalarda menünün davranışı. “Geri gelince”: aşağı inerken gizlenir, yukarı kaydırınca görünür.', 'help_en' => 'How the menu behaves on inner pages. “On scroll up”: hides going down, reappears going up.',
            'choices' => ['absolute' => ['tr' => 'Sayfayla kayar', 'en' => 'Scrolls away'], 'fixed' => ['tr' => 'Sabit kalır', 'en' => 'Stays fixed'], 'smart_sticky' => ['tr' => 'Geri gelince', 'en' => 'On scroll up']],
        ],
        'header_scroll_effect' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'choice', 'default' => 'none',
            'tr' => 'Kaydırınca menü zemini', 'en' => 'Menu background on scroll',
            'choices' => ['none' => ['tr' => 'Değişmez', 'en' => 'None'], 'glass_on_scroll' => ['tr' => 'Buzlu cam', 'en' => 'Frosted'], 'solid_on_scroll' => ['tr' => 'Düz renk', 'en' => 'Solid']],
            'show_if' => ['header_pos_inner' => ['fixed', 'smart_sticky']],
        ],
        'header_pos_cover' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'choice', 'default' => 'fixed',
            'tr' => 'Tam ekran fotoğraflı sayfalarda menü', 'en' => 'Menu on full-screen photo pages',
            'help_tr' => 'Ana sayfa ve bölüm kapakları gibi fotoğrafın ekranı kapladığı sayfalarda.', 'help_en' => 'On pages where the photo fills the screen, such as the homepage and section covers.',
            'choices' => ['fixed' => ['tr' => 'Hep üstte kalsın', 'en' => 'Stays on top'], 'absolute' => ['tr' => 'Kaydırınca gitsin', 'en' => 'Scrolls away']],
        ],
        'header_scroll_shrink' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'toggle', 'default' => '0',
            'tr' => 'Kaydırınca küçülsün', 'en' => 'Shrink on scroll',
            'show_if' => ['header_pos_inner' => ['fixed', 'smart_sticky']],
        ],
        'header_nav_hover_style' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'choice', 'default' => 'underline',
            'tr' => 'Bağlantının üzerine gelince', 'en' => 'Link hover effect',
            'choices' => ['underline' => ['tr' => 'Alt çizgi', 'en' => 'Underline'], 'accent' => ['tr' => 'Vurgu rengi', 'en' => 'Accent'], 'pill' => ['tr' => 'Kapsül', 'en' => 'Pill'], 'opacity' => ['tr' => 'Soluklaşma', 'en' => 'Fade']],
        ],
        'header_nav_gap' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'slider', 'min' => 8, 'max' => 56, 'step' => 2, 'unit' => 'px', 'default' => '24',
            'tr' => 'Menü öğeleri arası', 'en' => 'Space between items',
        ],
        'header_padding_y' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'slider', 'min' => 10, 'max' => 64, 'step' => 2, 'unit' => 'px', 'default' => '34',
            'tr' => 'Üst ve alt boşluk', 'en' => 'Top and bottom padding',
        ],
        'header_padding_x' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'slider', 'min' => 14, 'max' => 96, 'step' => 2, 'unit' => 'px', 'default' => '44',
            'tr' => 'Yan boşluk', 'en' => 'Side padding',
        ],
        'header_bar_show_lang' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Dil seçici (TR / EN)', 'en' => 'Language switcher (TR / EN)',
        ],
        'header_bar_show_surface' => [
            'section' => 'menu', 'sub' => 'options', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Açık / koyu düğmesi', 'en' => 'Light / dark button',
            'help_tr' => 'Ziyaretçinin zemini açık ya da koyu yapabilmesi için.', 'help_en' => 'Lets visitors switch the background between light and dark.',
        ],
        // ── MENÜ › HAMBURGER MENÜ ──
        'header_hamburger_style' => [
            'section' => 'menu', 'sub' => 'drawer', 'type' => 'choice', 'default' => 'yellow_circle',
            'tr' => 'Menü düğmesi', 'en' => 'Menu button',
            'help_tr' => 'Hamburger menüde ve telefonda görünen ☰ düğmesi.', 'help_en' => 'The ☰ button shown for the hamburger menu and on phones.',
            'choices' => ['yellow_circle' => ['tr' => 'Renkli daire', 'en' => 'Colour circle'], 'dark_circle' => ['tr' => 'Koyu daire', 'en' => 'Dark circle'], 'glass_circle' => ['tr' => 'Buzlu daire', 'en' => 'Frosted circle'], 'minimal_lines' => ['tr' => 'Yalnızca çizgiler', 'en' => 'Lines only'], 'pill_label' => ['tr' => '“Menü” yazılı', 'en' => '“Menu” label']],
        ],
        'header_drawer_style' => [
            'section' => 'menu', 'sub' => 'drawer', 'type' => 'choice', 'default' => 'card_top_right',
            'tr' => 'Menü nasıl açılsın', 'en' => 'How the menu opens',
            'choices' => ['card_top_right' => ['tr' => 'Köşede kart', 'en' => 'Corner card'], 'drawer_right' => ['tr' => 'Yandan çekmece', 'en' => 'Side drawer'], 'fullscreen' => ['tr' => 'Tam ekran', 'en' => 'Fullscreen']],
        ],
        'header_drawer_font_size' => [
            'section' => 'menu', 'sub' => 'drawer', 'type' => 'choice', 'default' => 'normal',
            'tr' => 'Açılan menüde yazı boyutu', 'en' => 'Text size in the open menu',
            'choices' => ['compact' => ['tr' => 'Küçük', 'en' => 'Small'], 'normal' => ['tr' => 'Orta', 'en' => 'Medium'], 'large' => ['tr' => 'Büyük', 'en' => 'Large'], 'monumental' => ['tr' => 'Çok büyük', 'en' => 'Huge']],
        ],
        'header_drawer_show_lang' => [
            'section' => 'menu', 'sub' => 'drawer', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Açılan menüde dil seçici', 'en' => 'Language switcher in the open menu',
        ],
        'header_drawer_show_surface' => [
            'section' => 'menu', 'sub' => 'drawer', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Açılan menüde açık / koyu düğmesi', 'en' => 'Light / dark button in the open menu',
        ],
        'header_drawer_show_socials' => [
            'section' => 'menu', 'sub' => 'drawer', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Açılan menüde sosyal medya', 'en' => 'Social links in the open menu',
        ],
        // ── MENÜ › SOSYAL MEDYA ──
        'header_bar_show_socials' => [
            'section' => 'menu', 'sub' => 'social', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Menüde sosyal medya simgeleri', 'en' => 'Social icons in the menu',
            'help_tr' => 'Hesap adresleri Site Ayarları › Sosyal medya bölümünden girilir.', 'help_en' => 'Account addresses are entered in Site Settings › Social media.',
        ],
        'header_show_instagram' => ['section' => 'menu', 'sub' => 'social', 'type' => 'toggle', 'default' => '1', 'tr' => 'Instagram', 'en' => 'Instagram', 'show_if' => ['header_bar_show_socials' => ['1']]],
        'header_show_twitter'   => ['section' => 'menu', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'X (Twitter)', 'en' => 'X (Twitter)', 'show_if' => ['header_bar_show_socials' => ['1']]],
        'header_show_facebook'  => ['section' => 'menu', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'Facebook', 'en' => 'Facebook', 'show_if' => ['header_bar_show_socials' => ['1']]],
        'header_show_youtube'   => ['section' => 'menu', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'YouTube', 'en' => 'YouTube', 'show_if' => ['header_bar_show_socials' => ['1']]],
        'header_show_linkedin'  => ['section' => 'menu', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'LinkedIn', 'en' => 'LinkedIn', 'show_if' => ['header_bar_show_socials' => ['1']]],
        'header_show_email'     => ['section' => 'menu', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'E-posta', 'en' => 'Email', 'show_if' => ['header_bar_show_socials' => ['1']]],
        // ── MENÜ › DÜĞME ──
        'header_cta_enabled' => [
            'section' => 'menu', 'sub' => 'button', 'type' => 'toggle', 'default' => '0',
            'tr' => 'Menüde öne çıkan düğme', 'en' => 'Highlighted button in the menu',
            'help_tr' => 'Örneğin “İletişim” ya da “Randevu al”; menünün sağında ayrı bir düğme olarak görünür.', 'help_en' => 'For example “Contact” or “Book now”, shown as a separate button at the right of the menu.',
        ],
        'header_cta_text_tr' => ['section' => 'menu', 'sub' => 'button', 'type' => 'text', 'default' => 'İletişim', 'max' => 40, 'tr' => 'Düğme yazısı (Türkçe)', 'en' => 'Button text (Turkish)', 'show_if' => ['header_cta_enabled' => ['1']]],
        'header_cta_text_en' => ['section' => 'menu', 'sub' => 'button', 'type' => 'text', 'default' => 'Contact', 'max' => 40, 'tr' => 'Düğme yazısı (İngilizce)', 'en' => 'Button text (English)', 'show_if' => ['header_cta_enabled' => ['1']]],
        'header_cta_url' => [
            'section' => 'menu', 'sub' => 'button', 'type' => 'url', 'default' => '', 'max' => 300,
            'tr' => 'Düğme bağlantısı', 'en' => 'Button link',
            'help_tr' => 'Tam adres (https://…), sitenizde bir sayfa (/tr/iletisim/) ya da mailto:adres@örnek.com', 'help_en' => 'A full address (https://…), a page on your site (/en/contact/) or mailto:name@example.com',
            'show_if' => ['header_cta_enabled' => ['1']],
        ],
        // ── MENÜ › RENKLER ──
        'header_bg_style' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'choice', 'default' => 'transparent',
            'tr' => 'Menü zemini', 'en' => 'Menu background',
            'choices' => ['transparent' => ['tr' => 'Şeffaf', 'en' => 'Transparent'], 'gradient' => ['tr' => 'Geçişli', 'en' => 'Gradient'], 'glass' => ['tr' => 'Buzlu cam', 'en' => 'Frosted'], 'solid_dark' => ['tr' => 'Koyu', 'en' => 'Dark'], 'solid_light' => ['tr' => 'Açık', 'en' => 'Light'], 'custom' => ['tr' => 'Özel renk', 'en' => 'Custom']],
        ],
        'header_bg_color' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'color', 'default' => '#0c0e12', 'no_auto' => true,
            'tr' => 'Menü zemin rengi', 'en' => 'Menu background colour',
            'show_if' => ['header_bg_style' => ['custom']],
        ],
        'header_bg_opacity' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'slider', 'min' => 0, 'max' => 100, 'step' => 5, 'unit' => '%', 'default' => '85',
            'tr' => 'Zemin doluluğu', 'en' => 'Background opacity',
            'show_if' => ['header_bg_style' => ['custom', 'glass']],
        ],
        'header_blur_px' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'slider', 'min' => 0, 'max' => 30, 'step' => 1, 'unit' => 'px', 'default' => '14',
            'tr' => 'Arka plan bulanıklığı', 'en' => 'Background blur',
            'show_if' => ['header_bg_style' => ['custom', 'glass']],
        ],
        'header_border_bottom' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'toggle', 'default' => '0',
            'tr' => 'Menünün altında ince çizgi', 'en' => 'Thin line under the menu',
        ],
        'header_adaptive_luminance' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Fotoğrafa göre açık / koyu', 'en' => 'Adapt to the photo',
            'help_tr' => 'Menü yazısı, altındaki fotoğraf açık renkliyse koyulaşır, koyuysa açılır.', 'help_en' => 'Menu text turns dark over light photos and light over dark ones.',
        ],
        'header_accent_color' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'color', 'default' => '#fdd000', 'no_auto' => true,
            'tr' => 'Menü düğmesi rengi', 'en' => 'Menu button colour',
            'help_tr' => 'Renkler › Vurgu rengi değiştirilince bu da birlikte değişir; burada ayrıca başka bir renk seçebilirsiniz.', 'help_en' => 'Follows Colours › Accent unless you pick a different colour here.',
        ],
        'header_drawer_bg' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'color', 'default' => '#fdd000', 'no_auto' => true,
            'tr' => 'Açılan menü zemini', 'en' => 'Open menu background',
        ],
        'header_drawer_text' => [
            'section' => 'menu', 'sub' => 'colors', 'type' => 'color', 'default' => '#0f0f0e', 'no_auto' => true,
            'tr' => 'Açılan menü yazısı', 'en' => 'Open menu text',
        ],
        // ── MENÜ › YAZI TİPİ ──
        'header_nav_font_family' => [
            'section' => 'menu', 'sub' => 'type', 'type' => 'font', 'default' => 'sans',
            'tr' => 'Menü yazı tipi', 'en' => 'Menu font',
        ],
        'header_nav_font_size' => [
            'section' => 'menu', 'sub' => 'type', 'type' => 'slider', 'min' => 10, 'max' => 24, 'step' => 1, 'unit' => 'px', 'default' => '14',
            'tr' => 'Menü yazı boyutu', 'en' => 'Menu text size',
        ],
        'header_nav_font_weight' => [
            'section' => 'menu', 'sub' => 'type', 'type' => 'choice', 'default' => '600',
            'tr' => 'Kalınlık', 'en' => 'Weight',
            'choices' => ['400' => ['tr' => 'Normal', 'en' => 'Regular'], '500' => ['tr' => 'Orta', 'en' => 'Medium'], '600' => ['tr' => 'Yarı kalın', 'en' => 'Semibold'], '700' => ['tr' => 'Kalın', 'en' => 'Bold']],
        ],
        'header_nav_transform' => [
            'section' => 'menu', 'sub' => 'type', 'type' => 'choice', 'default' => 'none',
            'tr' => 'Büyük / küçük harf', 'en' => 'Letter case',
            'choices' => ['none' => ['tr' => 'Olduğu gibi', 'en' => 'As typed'], 'uppercase' => ['tr' => 'BÜYÜK', 'en' => 'UPPER'], 'lowercase' => ['tr' => 'küçük', 'en' => 'lower'], 'capitalize' => ['tr' => 'Baş Harf', 'en' => 'Title']],
        ],
        'header_nav_letter_spacing' => [
            'section' => 'menu', 'sub' => 'type', 'type' => 'choice', 'default' => 'normal',
            'tr' => 'Harf aralığı', 'en' => 'Letter spacing',
            'choices' => ['tight' => ['tr' => 'Dar', 'en' => 'Tight'], 'normal' => ['tr' => 'Normal', 'en' => 'Normal'], 'wide' => ['tr' => 'Geniş', 'en' => 'Wide'], 'ultra_wide' => ['tr' => 'Çok geniş', 'en' => 'Extra wide']],
        ],
        // ── ALT BİLGİ › SEÇENEKLER ── (Göster/gizle: temanın 'footer' seçeneği)
        'footer_layout' => [
            'section' => 'footer', 'sub' => 'options', 'type' => 'choice', 'default' => 'centered_minimal',
            'tr' => 'Alt bilgi düzeni', 'en' => 'Footer layout',
            'help_tr' => 'İsim ve kısa tanıtım yalnızca “Künye” ve “Sütunlu” düzenlerinde görünür.', 'help_en' => 'The name and short description appear only in the “Colophon” and “Columns” layouts.',
            'choices' => ['centered_minimal' => ['tr' => 'Ortalı, sade', 'en' => 'Centred'], 'editorial_colophon' => ['tr' => 'Künye', 'en' => 'Colophon'], 'multi_column' => ['tr' => 'Sütunlu', 'en' => 'Columns'], 'split_bar' => ['tr' => 'İki yana', 'en' => 'Split bar']],
            'show_if' => ['footer' => ['1']],
        ],
        'footer_show_on_homepage' => [
            'section' => 'footer', 'sub' => 'options', 'type' => 'toggle', 'default' => '0',
            'tr' => 'Ana sayfada da göster', 'en' => 'Also on the homepage',
            'show_if' => ['footer' => ['1']],
        ],
        'footer_show_on_covers' => [
            'section' => 'footer', 'sub' => 'options', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Proje listelerinde göster', 'en' => 'On project lists',
            'help_tr' => 'Projeler, Seriler gibi liste sayfalarının altında.', 'help_en' => 'At the bottom of list pages such as Projects and Series.',
            'show_if' => ['footer' => ['1']],
        ],
        'footer_show_back_to_top' => [
            'section' => 'footer', 'sub' => 'options', 'type' => 'toggle', 'default' => '1',
            'tr' => '“Başa dön” düğmesi', 'en' => '“Back to top” button',
            'show_if' => ['footer' => ['1']],
        ],
        'footer_show_inline_top' => [
            'section' => 'footer', 'sub' => 'options', 'type' => 'toggle', 'default' => '0',
            'tr' => 'Alt bilgide “↑ Başa dön” bağlantısı', 'en' => '“↑ Back to top” link in the footer',
            'show_if' => ['footer' => ['1']],
        ],
        'footer_show_main_menu' => [
            'section' => 'footer', 'sub' => 'options', 'type' => 'toggle', 'default' => '0',
            'tr' => 'Menü bağlantılarını göster', 'en' => 'Show menu links',
            'help_tr' => 'Yalnızca alt bilgide görünecek ek bağlantılar Menü Bağlantıları sayfasından eklenir.', 'help_en' => 'Extra links shown only in the footer are added on the Menu Links page.',
            'show_if' => ['footer' => ['1']],
        ],
        'footer_padding_y' => [
            'section' => 'footer', 'sub' => 'options', 'type' => 'slider', 'min' => 16, 'max' => 96, 'step' => 4, 'unit' => 'px', 'default' => '36',
            'tr' => 'Üst ve alt boşluk', 'en' => 'Top and bottom padding',
            'show_if' => ['footer' => ['1']],
        ],
        // ── ALT BİLGİ › YAZILAR ──
        'footer_brand_title_tr' => ['section' => 'footer', 'sub' => 'texts', 'type' => 'text', 'default' => site_owner_name(), 'max' => 80, 'tr' => 'İsim (Türkçe)', 'en' => 'Name (Turkish)', 'show_if' => ['footer' => ['1'], 'footer_layout' => ['editorial_colophon', 'multi_column']]],
        'footer_brand_title_en' => ['section' => 'footer', 'sub' => 'texts', 'type' => 'text', 'default' => site_owner_name(), 'max' => 80, 'tr' => 'İsim (İngilizce)', 'en' => 'Name (English)', 'show_if' => ['footer' => ['1'], 'footer_layout' => ['editorial_colophon', 'multi_column']]],
        'footer_tagline_tr' => ['section' => 'footer', 'sub' => 'texts', 'type' => 'text', 'default' => '', 'max' => 200, 'tr' => 'Kısa tanıtım (Türkçe)', 'en' => 'Short description (Turkish)', 'show_if' => ['footer' => ['1'], 'footer_layout' => ['editorial_colophon', 'multi_column']]],
        'footer_tagline_en' => ['section' => 'footer', 'sub' => 'texts', 'type' => 'text', 'default' => '', 'max' => 200, 'tr' => 'Kısa tanıtım (İngilizce)', 'en' => 'Short description (English)', 'show_if' => ['footer' => ['1'], 'footer_layout' => ['editorial_colophon', 'multi_column']]],
        'footer_copyright_tr' => [
            'section' => 'footer', 'sub' => 'texts', 'type' => 'text', 'default' => '© {year} {site_title}. Tüm hakları saklıdır.', 'max' => 200,
            'tr' => 'Telif yazısı (Türkçe)', 'en' => 'Copyright line (Turkish)',
            'help_tr' => '{year} yerine yıl, {site_title} yerine site adı yazılır.', 'help_en' => '{year} becomes the year and {site_title} the site name.',
            'show_if' => ['footer' => ['1']],
        ],
        'footer_copyright_en' => ['section' => 'footer', 'sub' => 'texts', 'type' => 'text', 'default' => '© {year} {site_title}. All rights reserved.', 'max' => 200, 'tr' => 'Telif yazısı (İngilizce)', 'en' => 'Copyright line (English)', 'show_if' => ['footer' => ['1']]],
        // ── ALT BİLGİ › SOSYAL MEDYA ──
        'footer_socials_enabled' => [
            'section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '1',
            'tr' => 'Sosyal medya simgeleri', 'en' => 'Social icons',
            'help_tr' => 'Hesap adresleri Site Ayarları › Sosyal medya bölümünden girilir.', 'help_en' => 'Account addresses are entered in Site Settings › Social media.',
            'show_if' => ['footer' => ['1']],
        ],
        'footer_social_style' => [
            'section' => 'footer', 'sub' => 'social', 'type' => 'choice', 'default' => 'minimal',
            'tr' => 'Simge stili', 'en' => 'Icon style',
            'choices' => ['minimal' => ['tr' => 'Sade', 'en' => 'Plain'], 'circle_border' => ['tr' => 'Çerçeveli', 'en' => 'Outlined'], 'circle_glass' => ['tr' => 'Buzlu', 'en' => 'Frosted'], 'with_label' => ['tr' => 'Yazılı', 'en' => 'Labelled']],
            'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']],
        ],
        'footer_social_size' => ['section' => 'footer', 'sub' => 'social', 'type' => 'slider', 'min' => 16, 'max' => 32, 'step' => 1, 'unit' => 'px', 'default' => '24', 'tr' => 'Simge boyutu', 'en' => 'Icon size', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_social_gap' => ['section' => 'footer', 'sub' => 'social', 'type' => 'slider', 'min' => 10, 'max' => 36, 'step' => 1, 'unit' => 'px', 'default' => '20', 'tr' => 'Simgeler arası', 'en' => 'Space between icons', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_show_instagram' => ['section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '1', 'tr' => 'Instagram', 'en' => 'Instagram', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_show_twitter'   => ['section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '1', 'tr' => 'X (Twitter)', 'en' => 'X (Twitter)', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_show_facebook'  => ['section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'Facebook', 'en' => 'Facebook', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_show_youtube'   => ['section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '1', 'tr' => 'YouTube', 'en' => 'YouTube', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_show_linkedin'  => ['section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'LinkedIn', 'en' => 'LinkedIn', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_show_behance'   => ['section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'Behance', 'en' => 'Behance', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_show_email'     => ['section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '0', 'tr' => 'E-posta', 'en' => 'Email', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        'footer_show_rss'       => ['section' => 'footer', 'sub' => 'social', 'type' => 'toggle', 'default' => '1', 'tr' => 'RSS', 'en' => 'RSS', 'show_if' => ['footer' => ['1'], 'footer_socials_enabled' => ['1']]],
        // ── ALT BİLGİ › RENKLER ──
        'footer_bg_mode' => [
            'section' => 'footer', 'sub' => 'colors', 'type' => 'choice', 'default' => 'theme_auto',
            'tr' => 'Alt bilgi zemini', 'en' => 'Footer background',
            'choices' => ['theme_auto' => ['tr' => 'Sayfayla aynı', 'en' => 'Same as page'], 'dark' => ['tr' => 'Koyu', 'en' => 'Dark'], 'light' => ['tr' => 'Açık', 'en' => 'Light'], 'custom' => ['tr' => 'Özel renk', 'en' => 'Custom']],
            'show_if' => ['footer' => ['1']],
        ],
        'footer_custom_bg' => ['section' => 'footer', 'sub' => 'colors', 'type' => 'color', 'default' => '#0a0b0e', 'no_auto' => true, 'tr' => 'Alt bilgi zemin rengi', 'en' => 'Footer background colour', 'show_if' => ['footer' => ['1'], 'footer_bg_mode' => ['custom']]],
        'footer_custom_text' => ['section' => 'footer', 'sub' => 'colors', 'type' => 'color', 'default' => '#e2e8f0', 'no_auto' => true, 'tr' => 'Alt bilgi yazı rengi', 'en' => 'Footer text colour', 'show_if' => ['footer' => ['1'], 'footer_bg_mode' => ['custom']]],
        'footer_border_top' => ['section' => 'footer', 'sub' => 'colors', 'type' => 'toggle', 'default' => '1', 'tr' => 'Üstünde ince çizgi', 'en' => 'Thin line above', 'show_if' => ['footer' => ['1']]],
    ];
}

// ── SAYFA TÜRLERİ (Temalar › Özelleştir › Sayfalar) ─────────────────────────

/** Ana sayfa ve proje listelerinde kullanılabilen düzenler (includes/views/home.php, monograph_cards.php) */
function design_listing_layouts(): array {
    return [
        'cinema_fullbleed'    => ['Tam ekran slayt', 'Fullscreen slideshow'],
        'white_cube_gallery'  => ['Beyaz küp galeri (kırpmadan)', 'White cube gallery (uncropped)'],
        'monograph_split'     => ['Monografi: yarım fotoğraf', 'Monograph split'],
        'museum_passepartout' => ['Müze paspartusu', 'Museum passe-partout'],
        'editorial_magazine'  => ['Dergi manşeti', 'Magazine cover'],
        'filmstrip_stage'     => ['Sahne ve film şeridi', 'Stage and filmstrip'],
        'vertical_chapters'   => ['Dikey bölümler', 'Vertical chapters'],
        'story_stream'        => ['Dikey hikâye akışı', 'Story stream'],
        'horizontal_panorama' => ['Yatay şerit', 'Horizontal strip'],
        'curatorial_grid'     => ['Kapak ızgarası', 'Cover grid'],
        'thumb_grid'          => ['Küçük resim ızgarası', 'Thumbnail grid'],
        'mosaic_wall'         => ['Mozaik duvar', 'Mosaic wall'],
        'photo_wall'          => ['Fotoğraf duvarı (kırpmadan)', 'Photo wall (uncropped)'],
        'staggered_gallery'   => ['Kademeli galeri', 'Staggered gallery'],
        'split_grid'          => ['Bir büyük iki küçük', 'One large, two small'],
        'featured_grid'       => ['Öne çıkan + küçük resimler', 'Featured + thumbnails'],
        'zigzag_stream'       => ['Zikzak akış', 'Zigzag stream'],
        'split_screens'       => ['Bölünmüş ekran', 'Split screens'],
        'triptych'            => ['Üç panel', 'Three panels'],
        'section_banners'     => ['Bölüm afişleri', 'Section banners'],
        'light_table'         => ['Işık masası', 'Light table'],

        'framed_masonry'      => ['Çerçeveli kutu duvarı', 'Framed masonry'],
        'minimal_index'       => ['Yazı indeksi', 'Text index'],
        'year_archive'        => ['Yıllara göre arşiv', 'Archive by year'],
        'sidebar_list'        => ['Kenar listesi', 'Sidebar list'],
        'contact_sheet'       => ['Kontak baskı', 'Contact sheet'],
        'photo_statement'     => ['Fotoğraf ve bildiri', 'Photo and statement'],
        'sectioned_showcase'  => ['Bölümlü vitrin', 'Sectioned showcase'],
        'book_spreads'        => ['Fotokitap açılımları', 'Book spreads'],
        'free_collage'        => ['Serbest kolaj', 'Free collage'],
        'project_map'         => ['Proje haritası', 'Project map'],
    ];
}

/** Seçenek dizisi: [değer => [tr, en]] → alan biçimi */
function design_choices(array $map): array {
    $out = [];
    foreach ($map as $v => $l) $out[(string)$v] = ['tr' => $l[0], 'en' => $l[1]];
    return $out;
}

/** Ana sayfa (hp) ve proje listeleri (pf) için ortak alanlar */
function design_listing_fields(string $p, string $section): array {
    $slides  = ['cinema_fullbleed', 'white_cube_gallery', 'monograph_split', 'museum_passepartout', 'editorial_magazine', 'filmstrip_stage'];
    $grids   = ['curatorial_grid', 'framed_masonry', 'thumb_grid', 'mosaic_wall'];
    $shaded  = array_merge($slides, ['vertical_chapters', 'story_stream', 'curatorial_grid']);
    $tplKey  = $p . '_template';
    $if      = fn(array $tpls, array $extra = []) => [$tplKey => $tpls] + $extra;
    $sizes   = design_choices(['compact' => ['Küçük', 'Small'], 'normal' => ['Orta', 'Medium'], 'large' => ['Büyük', 'Large'], 'monumental' => ['Çok büyük', 'Huge']]);
    $f = [];
    $f[$tplKey] = [
        'section' => $section, 'sub' => 'options', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'listing', 'default' => $p === 'hp' ? 'cinema_fullbleed' : 'vertical_chapters',
        'tr' => 'Düzen', 'en' => 'Layout',
        'help_tr' => 'Tema bu sayfa için bir düzen seçer; buradan başka bir düzene geçebilirsiniz. Aşağıdaki ayarlar seçilen düzene göre değişir.', 'help_en' => 'The theme picks a layout for this page; you can switch to another here. The settings below change with the layout.',
        'choices' => design_choices(design_listing_layouts()),
    ];
    $f[$p . '_image_fit'] = [
        'section' => $section, 'sub' => 'options', 'type' => 'choice', 'default' => 'cover',
        'tr' => 'Fotoğraf yerleşimi', 'en' => 'Photo fit',
        'choices' => design_choices(['cover' => ['Ekranı doldur', 'Fill screen'], 'contain' => ['Kırpmadan sığdır', 'Fit uncropped'], 'framed' => ['Çerçeveli', 'Framed']]),
        'show_if' => $if($slides),
    ];
    $f[$p . '_grid_columns'] = [
        'section' => $section, 'sub' => 'options', 'type' => 'choice', 'default' => '2',
        'tr' => 'Sütun sayısı', 'en' => 'Columns',
        'choices' => design_choices(['2' => ['2 (büyük kartlar)', '2 (large cards)'], '3' => ['3', '3']]),
        'show_if' => $if(['curatorial_grid']),
    ];
    $f[$p . '_hover_zoom'] = ['section' => $section, 'sub' => 'options', 'type' => 'toggle', 'default' => '1', 'tr' => 'Üzerine gelince yakınlaşsın', 'en' => 'Zoom on hover', 'show_if' => $if($grids)];
    $f[$p . '_panorama_style'] = [
        'section' => $section, 'sub' => 'options', 'type' => 'choice', 'default' => 'cards',
        'tr' => 'Şeridin görünümü', 'en' => 'Strip style',
        'help_tr' => 'Kartlar: eşit kutular, başlık fotoğrafın üzerinde. Kırpmadan: fotoğraflar doğal oranında, başlık altında. Tam yükseklik: ekran boyu, aralıksız.', 'help_en' => 'Cards: equal boxes with the title over the photo. Uncropped: natural proportions with the title below. Full height: screen-tall with no gaps.',
        'choices' => design_choices(['cards' => ['Kartlar', 'Cards'], 'natural' => ['Kırpmadan', 'Uncropped'], 'fullheight' => ['Tam yükseklik', 'Full height']]),
        'show_if' => $if(['horizontal_panorama']),
    ];
    if ($p === 'hp') {
        $f['tpl_wall_statement'] = [
            'section' => $section, 'sub' => 'options', 'type' => 'toggle', 'default' => '0',
            'tr' => 'Üstte tanıtım yazısı', 'en' => 'Intro text above',
            'help_tr' => 'Site Ayarları’ndaki site açıklaması fotoğrafların üstünde büyük puntoyla gösterilir. **kelime** yazarsanız o kelime kalın görünür.', 'help_en' => 'The site description from Site Settings is shown in large type above the photos. Write **word** to make a word bold.',
            'show_if' => $if(['photo_wall']),
        ];
        $f['tpl_split_style'] = [
            'section' => $section, 'sub' => 'options', 'type' => 'choice', 'default' => 'bleed',
            'tr' => 'Bölünmüş ekran görünümü', 'en' => 'Split screen style',
            'help_tr' => 'Kenara taşan: fotoğraf ekranın yarısını boydan boya doldurur. Çerçeveli: fotoğraf renkli bir zeminde, dergi sayfası gibi. Proje listeleri de aynı görünümü kullanır.', 'help_en' => 'Edge to edge: the photo fills half the screen. Framed: the photo sits on a tinted panel, like a magazine page. Project lists use the same style.',
            'choices' => design_choices(['bleed' => ['Kenara taşan', 'Edge to edge'], 'framed' => ['Çerçeveli', 'Framed']]),
            'show_if' => $if(['split_screens']),
        ];
        $f['tpl_banner_source'] = [
            'section' => $section, 'sub' => 'options', 'type' => 'choice', 'default' => 'categories',
            'tr' => 'Afişlerde ne gösterilsin', 'en' => 'Banners show',
            'help_tr' => 'Bölümler: menüdeki her bölüm bir afiş olur, kapağı o bölümün ilk projesinden gelir. Projeler: ana sayfa seçkisindeki projeler.', 'help_en' => 'Sections: each menu section becomes a banner, its cover taken from the section’s first project. Projects: the projects in the homepage selection.',
            'choices' => design_choices(['categories' => ['Bölümler', 'Sections'], 'projects' => ['Projeler', 'Projects']]),
            'show_if' => $if(['section_banners']),
        ];
    }
    $f[$p . '_split_photo_width'] = [
        'section' => $section, 'sub' => 'options', 'type' => 'slider', 'min' => 45, 'max' => 75, 'step' => 5, 'unit' => '%', 'default' => '60',
        'tr' => 'Fotoğrafın genişliği', 'en' => 'Photo width',
        'show_if' => $if(['monograph_split']),
    ];
    // Slayt
    $f[$p . '_autoplay'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => $p === 'hp' ? '1' : '0', 'tr' => 'Kendiliğinden geçsin', 'en' => 'Autoplay', 'show_if' => $if($slides)];
    $f[$p . '_interval_sec'] = [
        'section' => $section, 'sub' => 'slides', 'type' => 'choice', 'default' => '6',
        'tr' => 'Her fotoğrafta bekleme', 'en' => 'Time per photo',
        'choices' => design_choices(['3' => ['3 saniye', '3 seconds'], '4' => ['4 saniye', '4 seconds'], '5' => ['5 saniye', '5 seconds'], '6' => ['6 saniye', '6 seconds'], '8' => ['8 saniye', '8 seconds'], '10' => ['10 saniye', '10 seconds'], '12' => ['12 saniye', '12 seconds']]),
        'show_if' => $if($slides, [$p . '_autoplay' => ['1']]),
    ];
    $f[$p . '_pause_on_hover'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '0', 'tr' => 'Fare üzerindeyken dursun', 'en' => 'Pause on hover', 'show_if' => $if($slides, [$p . '_autoplay' => ['1']])];
    $f[$p . '_transition_effect'] = [
        'section' => $section, 'sub' => 'slides', 'type' => 'choice', 'default' => 'fade',
        'tr' => 'Geçiş', 'en' => 'Transition',
        'choices' => design_choices(['fade' => ['Solarak', 'Fade'], 'zoom_fade' => ['Yakınlaşarak', 'Zoom'], 'slide_horizontal' => ['Kayarak', 'Slide'], 'instant' => ['Anında', 'Instant']]),
        'show_if' => $if($slides),
    ];
    $f[$p . '_slide_order'] = [
        'section' => $section, 'sub' => 'slides', 'type' => 'choice', 'default' => 'sequential',
        'tr' => 'Sıra', 'en' => 'Order',
        'choices' => design_choices(['sequential' => ['Sırayla', 'In order'], 'random_start' => ['Rastgele başla', 'Random start'], 'shuffle' => ['Karışık', 'Shuffle']]),
        'show_if' => $if($slides),
    ];
    $f[$p . '_show_arrows'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '0', 'tr' => 'İleri / geri okları', 'en' => 'Arrows', 'show_if' => $if($slides)];
    $f[$p . '_arrows_style'] = [
        'section' => $section, 'sub' => 'slides', 'type' => 'choice', 'default' => 'minimal_glass',
        'tr' => 'Ok stili', 'en' => 'Arrow style',
        'choices' => design_choices(['minimal_glass' => ['Buzlu', 'Frosted'], 'side_floating' => ['Kenarlarda', 'At the sides'], 'bottom_bar' => ['Alt çubukta', 'Bottom bar']]),
        'show_if' => $if($slides, [$p . '_show_arrows' => ['1']]),
    ];
    $f[$p . '_show_dots'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '0', 'tr' => 'Noktalar', 'en' => 'Dots', 'show_if' => $if($slides)];
    $f[$p . '_show_counter'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '0', 'tr' => 'Sayaç (1 / 12)', 'en' => 'Counter (1 / 12)', 'show_if' => $if($slides)];
    $f[$p . '_counter_position'] = [
        'section' => $section, 'sub' => 'slides', 'type' => 'choice', 'default' => 'bottom_right',
        'tr' => 'Sayacın yeri', 'en' => 'Counter position',
        'choices' => design_choices(['bottom_right' => ['Sağ alt', 'Bottom right'], 'bottom_left' => ['Sol alt', 'Bottom left'], 'top_right' => ['Sağ üst', 'Top right']]),
        'show_if' => $if($slides, [$p . '_show_counter' => ['1']]),
    ];
    $f[$p . '_show_progress_bar'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '0', 'tr' => 'İlerleme çizgisi', 'en' => 'Progress bar', 'show_if' => $if($slides)];
    $f[$p . '_show_filmstrip'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '0', 'tr' => 'Altta küçük resim şeridi', 'en' => 'Thumbnail strip below', 'show_if' => $if($slides)];
    $f[$p . '_click_to_advance'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => $p === 'hp' ? '1' : '0', 'tr' => 'Fotoğrafa tıklayınca ilerlesin', 'en' => 'Click photo to advance', 'show_if' => $if($slides)];
    $f[$p . '_keyboard_nav'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '1', 'tr' => 'Klavye oklarıyla gezinme', 'en' => 'Keyboard arrows', 'show_if' => $if($slides)];
    $f[$p . '_swipe_nav'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '1', 'tr' => 'Telefonda kaydırarak gezinme', 'en' => 'Swipe on phones', 'show_if' => $if($slides)];
    if ($p === 'pf') {
        $f['pf_show_right_tracker'] = ['section' => $section, 'sub' => 'slides', 'type' => 'toggle', 'default' => '1', 'tr' => 'Sağda ilerleme göstergesi', 'en' => 'Progress tracker on the right', 'show_if' => $if(['vertical_chapters'])];
    }
    // Yazılar
    $f[$p . '_show_title'] = ['section' => $section, 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Başlık', 'en' => 'Title'];
    $f[$p . '_show_location_year'] = ['section' => $section, 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Yer ve yıl', 'en' => 'Place and year'];
    $f[$p . '_show_description'] = ['section' => $section, 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Kısa açıklama', 'en' => 'Short description'];
    $f[$p . '_content_position'] = [
        'section' => $section, 'sub' => 'texts', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'content_position', 'default' => 'bottom_left',
        'tr' => 'Yazının yeri', 'en' => 'Text position',
        'choices' => design_choices(['bottom_left' => ['Sol altta', 'Bottom left'], 'bottom_center' => ['Altta ortada', 'Bottom centre'], 'center_overlay' => ['Tam ortada', 'Centre'], 'top_left' => ['Sol üstte', 'Top left'], 'bottom_split' => ['Altta iki yana', 'Bottom split']]),
        'show_if' => $if($slides),
    ];
    if ($p === 'hp') {
        $f['hp_show_explore_btn'] = ['section' => $section, 'sub' => 'texts', 'type' => 'toggle', 'default' => '0', 'tr' => '“İncele” düğmesi', 'en' => '“Explore” button', 'help_tr' => 'Slaytın bağlı olduğu projeye giden düğme.', 'help_en' => 'A button linking to the slide’s project.', 'show_if' => $if(array_merge($slides, ['story_stream']))];
        $f['hp_intro_enabled'] = ['section' => $section, 'sub' => 'options', 'type' => 'toggle', 'default' => '0', 'tr' => 'Açılış perdesi', 'en' => 'Intro curtain', 'help_tr' => 'Site ilk açılınca ilk fotoğrafla kısa bir giriş gösterilir.', 'help_en' => 'A short intro with the first photo when the site first opens.'];
    }
    // Renkler
    $f[$p . '_overlay_style'] = [
        'section' => $section, 'sub' => 'colors', 'type' => 'choice', 'default' => 'bottom_gradient',
        'tr' => 'Fotoğraf üstü karartma', 'en' => 'Image shading',
        'help_tr' => 'Fotoğrafın üzerindeki yazının okunması için hafif karartma.', 'help_en' => 'A light shade so text over the photo stays readable.',
        'choices' => design_choices(['bottom_gradient' => ['Alttan', 'From bottom'], 'full_vignette' => ['Kenarlardan', 'Vignette'], 'soft_scrim' => ['Hafif', 'Soft'], 'none' => ['Yok', 'None']]),
        'show_if' => $if($shaded),
    ];
    $f[$p . '_overlay_opacity'] = [
        'section' => $section, 'sub' => 'colors', 'type' => 'slider', 'min' => 0, 'max' => 95, 'step' => 5, 'unit' => '%', 'default' => '75',
        'tr' => 'Karartma yoğunluğu', 'en' => 'Shade strength',
        'show_if' => $if($shaded, [$p . '_overlay_style' => ['bottom_gradient', 'full_vignette', 'soft_scrim']]),
    ];
    // Yazı tipi
    $f[$p . '_title_size'] = ['section' => $section, 'sub' => 'type', 'type' => 'choice', 'default' => $p === 'hp' ? 'normal' : 'large', 'tr' => 'Başlık boyutu', 'en' => 'Title size', 'choices' => $sizes];
    return $f;
}

/** Proje sayfası, Hakkımda, İletişim ve Blog alanları */
function design_page_fields(): array {
    $sizes  = design_choices(['compact' => ['Küçük', 'Small'], 'normal' => ['Orta', 'Medium'], 'large' => ['Büyük', 'Large'], 'monumental' => ['Çok büyük', 'Huge']]);
    $widths = design_choices(['narrow' => ['Dar', 'Narrow'], 'standard' => ['Standart', 'Standard'], 'wide' => ['Geniş', 'Wide'], 'full' => ['Tam', 'Full']]);
    $frames = design_choices(['none' => ['Çerçevesiz', 'None'], 'subtle_border' => ['İnce çizgi', 'Thin line'], 'passepartout' => ['Paspartu', 'Passe-partout']]);
    $spread = ['spread_right', 'spread_left', 'sticky_split'];
    $f = [];
    // ── PROJE SAYFASI ──
    $f['inner_opening_layout'] = [
        'section' => 'project', 'sub' => 'options', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'inner_opening', 'default' => 'spread_right',
        'tr' => 'Açılış düzeni', 'en' => 'Opening layout',
        'help_tr' => 'Proje sayfasının en üstü: başlık, metin ve ilk fotoğrafın yerleşimi.', 'help_en' => 'The top of a project page: how the title, text and first photo are arranged.',
        'choices' => design_choices(['spread_right' => ['Sağda fotoğraf', 'Photo on the right'], 'spread_left' => ['Solda fotoğraf', 'Photo on the left'], 'title_photo_text' => ['Başlık, fotoğraf, metin', 'Title, photo, text'], 'editorial_stacked' => ['Alt alta (dergi)', 'Stacked (magazine)'], 'sticky_split' => ['Sabit ikili', 'Sticky split'], 'cinema_hero' => ['Tam ekran kapak', 'Fullscreen cover'], 'centered_museum' => ['Ortalı (müze)', 'Centred (museum)'], 'photo_first' => ['Önce fotoğraf', 'Photo first'], 'framed_box' => ['Çerçeveli kutu', 'Framed box'], 'white_cube' => ['Beyaz küp', 'White cube'], 'story_header' => ['Foto-deneme', 'Photo essay']]),
    ];
    $f['inner_stream_layout'] = [
        'section' => 'project', 'sub' => 'options', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'inner_stream', 'default' => 'editorial_single',
        'tr' => 'Fotoğrafların dizilişi', 'en' => 'Photo sequence layout',
        'choices' => design_choices(['editorial_single' => ['Tek tek, büyük', 'One by one, large'], 'captioned_sequence' => ['Altyazılı dizi', 'Captioned sequence'], 'grid_2col' => ['2 sütun', '2 columns'], 'grid_3col' => ['3 sütun', '3 columns'], 'alternating_rhythm' => ['Bir sağa bir sola', 'Alternating'], 'magazine_offset' => ['Dergi kaydırmalı', 'Magazine offset'], 'horizontal_panorama' => ['Yatay şerit', 'Horizontal strip'], 'framed_masonry' => ['Kutu duvarı', 'Masonry'], 'white_cube_gallery' => ['Beyaz küp', 'White cube'], 'story_stream' => ['Hikâye akışı', 'Story stream'], 'photobook_spread' => ['Fotokitap açılımı', 'Book spread'], 'essay_rhythm' => ['Bir geniş, üç küçük', 'One wide, three small']]),
    ];
    $f['inner_spread_visual_width'] = ['section' => 'project', 'sub' => 'options', 'type' => 'slider', 'min' => 40, 'max' => 65, 'step' => 5, 'unit' => '%', 'default' => '55', 'tr' => 'Açılış fotoğrafının genişliği', 'en' => 'Opening photo width', 'show_if' => ['inner_opening_layout' => $spread]];
    $f['inner_container_width'] = ['section' => 'project', 'sub' => 'options', 'type' => 'choice', 'default' => 'standard', 'tr' => 'Sayfa genişliği', 'en' => 'Page width', 'choices' => $widths];
    $f['inner_photo_gap'] = ['section' => 'project', 'sub' => 'options', 'type' => 'choice', 'default' => 'normal', 'tr' => 'Fotoğraflar arası boşluk', 'en' => 'Space between photos', 'choices' => design_choices(['compact' => ['Sıkı', 'Tight'], 'normal' => ['Dengeli', 'Balanced'], 'spacious' => ['Ferah', 'Airy']])];
    $f['inner_photo_frame_style'] = ['section' => 'project', 'sub' => 'options', 'type' => 'choice', 'default' => 'none', 'tr' => 'Fotoğraf çerçevesi', 'en' => 'Photo frame', 'choices' => $frames];
    $f['inner_default_view'] = [
        'section' => 'project', 'sub' => 'options', 'type' => 'choice', 'default' => 'stream',
        'tr' => 'İlk açılışta görünüm', 'en' => 'Default view',
        'choices' => design_choices(['stream' => ['Sergi akışı', 'Exhibition stream'], 'grid' => ['Kontak baskı', 'Contact sheet']]),
    ];
    $f['inner_contact_sheet_cols'] = ['section' => 'project', 'sub' => 'options', 'type' => 'choice', 'default' => '4', 'tr' => 'Kontak baskıda sütun', 'en' => 'Contact sheet columns', 'choices' => design_choices(['3' => ['3', '3'], '4' => ['4', '4'], '5' => ['5', '5'], '6' => ['6', '6']])];
    $f['inner_header_border'] = ['section' => 'project', 'sub' => 'options', 'type' => 'toggle', 'default' => '1', 'tr' => 'Başlığın altında çizgi', 'en' => 'Line under the title'];
    // Yazılar
    $f['inner_text_wrap_mode'] = ['section' => 'project', 'sub' => 'texts', 'type' => 'choice', 'default' => 'wrap', 'tr' => 'Proje metni', 'en' => 'Project text', 'choices' => design_choices(['wrap' => ['Fotoğrafın yanında akar', 'Flows beside the photo'], 'columns' => ['Ayrı sütunda', 'Separate column']])];
    $f['inner_intro_columns'] = ['section' => 'project', 'sub' => 'texts', 'type' => 'choice', 'default' => '1', 'tr' => 'Giriş metni sütunu', 'en' => 'Intro columns', 'choices' => design_choices(['1' => ['Tek sütun', 'One'], '2' => ['İki sütun', 'Two']])];
    $f['inner_intro_dropcap'] = ['section' => 'project', 'sub' => 'texts', 'type' => 'toggle', 'default' => '0', 'tr' => 'Büyük ilk harf', 'en' => 'Drop cap'];
    $f['inner_caption_style'] = [
        'section' => 'project', 'sub' => 'texts', 'type' => 'choice', 'default' => 'below_left',
        'tr' => 'Fotoğraf altyazısı', 'en' => 'Photo caption',
        'choices' => design_choices(['below_left' => ['Altta solda', 'Below, left'], 'below_center' => ['Altta ortada', 'Below, centred'], 'minimal_muted' => ['Küçük not', 'Small note'], 'hover_overlay' => ['Üzerine gelince', 'On hover']]),
    ];
    $f['inner_show_location_year'] = ['section' => 'project', 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Yer ve yıl', 'en' => 'Place and year'];
    $f['inner_show_frame_badges'] = ['section' => 'project', 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Fotoğraf numarası (01, 02…)', 'en' => 'Photo number (01, 02…)'];
    $f['inner_badge_position'] = [
        'section' => 'project', 'sub' => 'texts', 'type' => 'choice', 'default' => 'bottom_right',
        'tr' => 'Numaranın yeri', 'en' => 'Number position',
        'choices' => design_choices(['bottom_right' => ['Sağ alt', 'Bottom right'], 'bottom_left' => ['Sol alt', 'Bottom left'], 'top_left_overlay' => ['Fotoğrafın üstünde', 'Over the photo']]),
        'show_if' => ['inner_show_frame_badges' => ['1']],
    ];
    // Gezinme
    $f['inner_show_prev_next'] = ['section' => 'project', 'sub' => 'nav', 'type' => 'toggle', 'default' => '1', 'tr' => 'Önceki / sonraki proje', 'en' => 'Previous / next project'];
    $f['inner_prev_next_style'] = [
        'section' => 'project', 'sub' => 'nav', 'type' => 'choice', 'default' => 'image_cards',
        'tr' => 'Önceki / sonraki görünümü', 'en' => 'Previous / next style',
        'choices' => design_choices(['image_cards' => ['Görselli kart', 'Image cards'], 'minimal_text' => ['Yalnızca yazı', 'Text only'], 'compact_bar' => ['İnce şerit', 'Compact bar']]),
        'show_if' => ['inner_show_prev_next' => ['1']],
    ];
    $f['inner_show_back_category'] = ['section' => 'project', 'sub' => 'nav', 'type' => 'toggle', 'default' => '1', 'tr' => 'Listeye dönüş bağlantısı', 'en' => 'Back to list link'];
    $f['inner_show_view_switcher'] = ['section' => 'project', 'sub' => 'nav', 'type' => 'toggle', 'default' => '1', 'tr' => 'Akış / kontak baskı düğmesi', 'en' => 'Stream / contact sheet switch'];
    $f['inner_show_surface_switcher'] = ['section' => 'project', 'sub' => 'nav', 'type' => 'toggle', 'default' => '1', 'tr' => 'Açık / koyu düğmesi', 'en' => 'Light / dark button'];
    $f['inner_show_right_tracker'] = ['section' => 'project', 'sub' => 'nav', 'type' => 'toggle', 'default' => '1', 'tr' => 'Sağda ilerleme göstergesi', 'en' => 'Progress tracker on the right'];
    $f['inner_show_keyboard_hints'] = ['section' => 'project', 'sub' => 'nav', 'type' => 'toggle', 'default' => '1', 'tr' => 'Klavye ipuçları', 'en' => 'Keyboard hints'];
    $f['inner_show_share_bar'] = ['section' => 'project', 'sub' => 'nav', 'type' => 'toggle', 'default' => '1', 'tr' => 'Paylaşım düğmeleri', 'en' => 'Share buttons'];
    $f['inner_title_size'] = ['section' => 'project', 'sub' => 'type', 'type' => 'choice', 'default' => 'normal', 'tr' => 'Başlık boyutu', 'en' => 'Title size', 'choices' => $sizes];

    // ── HAKKIMDA ──
    $f['about_template'] = [
        'section' => 'about', 'sub' => 'options', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'about_template', 'default' => 'monograph_classic',
        'tr' => 'Düzen', 'en' => 'Layout',
        'choices' => design_choices(['monograph_classic' => ['Klasik monografi', 'Classic monograph'], 'cinema_hero' => ['Tam ekran kapak', 'Fullscreen cover'], 'sticky_split' => ['Sabit ikili', 'Sticky split'], 'magazine_profile' => ['Dergi profili', 'Magazine profile'], 'museum_curatorial' => ['Müze künyesi', 'Museum label'], 'minimalist_typography' => ['Yalnızca yazı', 'Typography only'], 'modular_bento' => ['Kutular', 'Modular boxes'], 'press_dossier' => ['Basın dosyası', 'Press dossier'], 'format_portrait_title' => ['Portre yanında başlık', 'Title beside portrait'], 'format_landscape' => ['Geniş fotoğraf, ortalı metin', 'Wide photo, centred text'], 'format_centered' => ['Ortalı tek sütun', 'Centred column'], 'format_labels' => ['Solda başlıklar', 'Headings on the left']]),
    ];
    $f['about_container_width'] = ['section' => 'about', 'sub' => 'options', 'type' => 'choice', 'default' => 'standard', 'tr' => 'Sayfa genişliği', 'en' => 'Page width', 'choices' => $widths];
    $f['about_portrait_position'] = [
        'section' => 'about', 'sub' => 'options', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'about_portrait', 'default' => 'right',
        'tr' => 'Portre fotoğrafı', 'en' => 'Portrait photo',
        'choices' => design_choices(['right' => ['Sağda', 'Right'], 'left' => ['Solda', 'Left'], 'top_hero' => ['Üstte, geniş', 'Top, wide'], 'floating' => ['Metnin içinde', 'Within the text'], 'none' => ['Gösterme', 'Hide']]),
    ];
    $f['about_portrait_width'] = ['section' => 'about', 'sub' => 'options', 'type' => 'slider', 'min' => 30, 'max' => 55, 'step' => 5, 'unit' => '%', 'default' => '40', 'tr' => 'Portre genişliği', 'en' => 'Portrait width', 'show_if' => ['about_portrait_position' => ['right', 'left', 'floating']]];
    $f['about_portrait_frame'] = ['section' => 'about', 'sub' => 'options', 'type' => 'choice', 'default' => 'none', 'tr' => 'Portre çerçevesi', 'en' => 'Portrait frame', 'choices' => $frames, 'show_if' => ['about_portrait_position' => ['right', 'left', 'top_hero', 'floating']]];
    $f['about_narrative_columns'] = ['section' => 'about', 'sub' => 'texts', 'type' => 'choice', 'default' => '1', 'tr' => 'Metin sütunu', 'en' => 'Text columns', 'choices' => design_choices(['1' => ['Tek sütun', 'One'], '2' => ['İki sütun', 'Two']])];
    $f['about_intro_dropcap'] = ['section' => 'about', 'sub' => 'texts', 'type' => 'toggle', 'default' => '0', 'tr' => 'Büyük ilk harf', 'en' => 'Drop cap'];
    $f['about_show_location_role'] = ['section' => 'about', 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Şehir ve unvan', 'en' => 'City and role'];
    $f['about_show_chronology'] = ['section' => 'about', 'sub' => 'chrono', 'type' => 'toggle', 'default' => '1', 'tr' => 'Sergi ve yayın listesi', 'en' => 'Exhibitions and publications'];
    $f['about_chrono_layout'] = [
        'section' => 'about', 'sub' => 'chrono', 'type' => 'choice', 'default' => 'timeline_list',
        'tr' => 'Liste görünümü', 'en' => 'List style',
        'choices' => design_choices(['timeline_list' => ['Zaman çizelgesi', 'Timeline'], 'table_grid' => ['Tablo', 'Table'], 'compact_cards' => ['Kartlar', 'Cards'], 'two_columns' => ['İki sütun', 'Two columns']]),
        'show_if' => ['about_show_chronology' => ['1']],
    ];
    $f['about_chrono_group_by'] = ['section' => 'about', 'sub' => 'chrono', 'type' => 'choice', 'default' => 'flat', 'tr' => 'Gruplama', 'en' => 'Grouping', 'choices' => design_choices(['flat' => ['Tek liste', 'Single list'], 'by_category' => ['Türe göre', 'By type']]), 'show_if' => ['about_show_chronology' => ['1']]];
    $f['about_chrono_badge_style'] = ['section' => 'about', 'sub' => 'chrono', 'type' => 'choice', 'default' => 'pill', 'tr' => 'Tür etiketi', 'en' => 'Type label', 'choices' => design_choices(['pill' => ['Kapsül', 'Pill'], 'minimal' => ['Sade', 'Plain'], 'bordered' => ['Çerçeveli', 'Outlined']]), 'show_if' => ['about_show_chronology' => ['1']]];
    $f['about_show_contact_banner'] = ['section' => 'about', 'sub' => 'contact', 'type' => 'toggle', 'default' => '1', 'tr' => 'İletişim alanı', 'en' => 'Contact block'];
    $f['about_contact_style'] = [
        'section' => 'about', 'sub' => 'contact', 'type' => 'choice', 'default' => 'banner_boxed',
        'tr' => 'İletişim alanının görünümü', 'en' => 'Contact block style',
        'choices' => design_choices(['banner_boxed' => ['Kutu', 'Box'], 'minimal_strip' => ['İnce şerit', 'Strip'], 'floating_badge' => ['Köşe rozeti', 'Corner badge'], 'compact_buttons' => ['Düğmeler', 'Buttons']]),
        'show_if' => ['about_show_contact_banner' => ['1']],
    ];
    $f['about_show_email_button'] = ['section' => 'about', 'sub' => 'contact', 'type' => 'toggle', 'default' => '1', 'tr' => 'E-posta düğmesi', 'en' => 'Email button', 'show_if' => ['about_show_contact_banner' => ['1']]];
    $f['about_show_social_links'] = ['section' => 'about', 'sub' => 'contact', 'type' => 'toggle', 'default' => '1', 'tr' => 'Sosyal medya bağlantıları', 'en' => 'Social links', 'show_if' => ['about_show_contact_banner' => ['1']]];
    if (field_map_enabled()) $f['about_show_field_map_link'] = ['section' => 'about', 'sub' => 'contact', 'type' => 'toggle', 'default' => '1', 'tr' => 'Saha haritası bağlantısı', 'en' => 'Field map link'];
    $f['about_title_size'] = ['section' => 'about', 'sub' => 'type', 'type' => 'choice', 'default' => 'normal', 'tr' => 'Başlık boyutu', 'en' => 'Title size', 'choices' => $sizes];

    // ── İLETİŞİM ──
    $f['contact_page_enabled'] = ['section' => 'contact', 'sub' => 'options', 'type' => 'toggle', 'default' => '0', 'tr' => 'İletişim sayfası', 'en' => 'Contact page', 'help_tr' => 'Kapalıyken iletişim bilgileri Hakkımda sayfasında gösterilir.', 'help_en' => 'When off, contact details are shown on the About page.'];
    $f['contact_page_layout'] = [
        'section' => 'contact', 'sub' => 'options', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'contact_layout', 'default' => 'split_editorial',
        'tr' => 'Düzen', 'en' => 'Layout',
        'choices' => design_choices(['split_editorial' => ['İki sütun', 'Two columns'], 'centered_minimal' => ['Ortalı, sade', 'Centred'], 'dossier_grid' => ['Kutular', 'Boxes'], 'format_info_form' => ['Bilgiler ve form', 'Details and form'], 'format_image_form' => ['Fotoğraf ve form', 'Photo and form'], 'format_hero_form' => ['Üstte geniş fotoğraf', 'Wide photo on top'], 'format_quote_form' => ['Alıntı ve form', 'Quote and form']]),
        'show_if' => ['contact_page_enabled' => ['1']],
    ];
    $f['contact_page_image'] = ['section' => 'contact', 'sub' => 'options', 'type' => 'image', 'default' => '', 'bg' => '#f4f4f2', 'fallback' => 'assets/brand/portrait-placeholder.svg', 'tr' => 'Sayfa fotoğrafı', 'en' => 'Page photo', 'show_if' => ['contact_page_enabled' => ['1'], 'contact_page_layout' => ['format_image_form', 'format_hero_form']]];
    $f['contact_page_show_socials'] = ['section' => 'contact', 'sub' => 'options', 'type' => 'toggle', 'default' => '1', 'tr' => 'Sosyal medya bağlantıları', 'en' => 'Social links', 'show_if' => ['contact_page_enabled' => ['1']]];
    $f['contact_page_show_map_link'] = ['section' => 'contact', 'sub' => 'options', 'type' => 'toggle', 'default' => '1', 'tr' => 'Saha haritası bağlantısı', 'en' => 'Field map link', 'show_if' => ['contact_page_enabled' => ['1']]];
    $f['contact_phone'] = ['section' => 'contact', 'sub' => 'texts', 'type' => 'text', 'default' => '', 'max' => 40, 'tr' => 'Telefon', 'en' => 'Phone', 'help_tr' => 'Boş bırakılırsa telefon gösterilmez.', 'help_en' => 'Leave empty to hide the phone number.', 'show_if' => ['contact_page_enabled' => ['1']]];

    // ── BLOG ──
    $f['blog_enabled'] = ['section' => 'blog', 'sub' => 'options', 'type' => 'toggle', 'default' => '0', 'tr' => 'Blog', 'en' => 'Blog'];
    $f['blog_template'] = [
        'section' => 'blog', 'sub' => 'options', 'type' => 'choice', 'ui' => 'visual', 'pics' => 'blog_template', 'default' => 'editorial_grid',
        'tr' => 'Yazı listesi düzeni', 'en' => 'Post list layout',
        'choices' => design_choices(['editorial_grid' => ['Izgara', 'Grid'], 'magazine_featured' => ['Dergi manşeti', 'Magazine'], 'minimal_journal' => ['Sade günlük', 'Journal'], 'chronological_stream' => ['Kronolojik akış', 'Chronological'], 'split_showcase' => ['İkili vitrin', 'Split showcase'], 'bento_news' => ['Kutular', 'Boxes']]),
        'show_if' => ['blog_enabled' => ['1']],
    ];
    $f['blog_grid_cols'] = ['section' => 'blog', 'sub' => 'options', 'type' => 'choice', 'default' => '3', 'tr' => 'Sütun sayısı', 'en' => 'Columns', 'choices' => design_choices(['2' => ['2', '2'], '3' => ['3', '3']]), 'show_if' => ['blog_enabled' => ['1'], 'blog_template' => ['editorial_grid']]];
    $f['blog_show_cover'] = ['section' => 'blog', 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Kapak görseli', 'en' => 'Cover image', 'show_if' => ['blog_enabled' => ['1']]];
    $f['blog_card_aspect'] = ['section' => 'blog', 'sub' => 'texts', 'type' => 'choice', 'default' => '16/10', 'tr' => 'Kapak oranı', 'en' => 'Cover ratio', 'choices' => design_choices(['16/10' => ['16:10', '16:10'], '3/2' => ['3:2', '3:2'], '4/3' => ['4:3', '4:3'], '1/1' => ['Kare', 'Square']]), 'show_if' => ['blog_enabled' => ['1'], 'blog_show_cover' => ['1']]];
    $f['blog_show_date'] = ['section' => 'blog', 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Tarih', 'en' => 'Date', 'show_if' => ['blog_enabled' => ['1']]];
    $f['blog_show_location'] = ['section' => 'blog', 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Yer', 'en' => 'Place', 'show_if' => ['blog_enabled' => ['1']]];
    $f['blog_show_excerpt'] = ['section' => 'blog', 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Özet', 'en' => 'Excerpt', 'show_if' => ['blog_enabled' => ['1']]];
    $f['blog_show_reading_time'] = ['section' => 'blog', 'sub' => 'texts', 'type' => 'toggle', 'default' => '1', 'tr' => 'Okuma süresi', 'en' => 'Reading time', 'show_if' => ['blog_enabled' => ['1']]];
    return $f;
}

/** Bölüm içindeki alt başlıklar (sıra = ekrandaki sıra) */
function design_editor_subs(): array {
    return [
        'basic'   => ['tr' => 'Temel', 'en' => 'Basic'],
        'options' => ['tr' => 'Seçenekler', 'en' => 'Options'],
        'slides'  => ['tr' => 'Slayt', 'en' => 'Slideshow'],
        'image'   => ['tr' => 'Logo görselleri', 'en' => 'Logo images'],
        'texts'   => ['tr' => 'Yazılar', 'en' => 'Text'],
        'nav'     => ['tr' => 'Gezinme', 'en' => 'Navigation'],
        'chrono'  => ['tr' => 'Sergi ve yayın listesi', 'en' => 'Exhibitions and publications'],
        'contact' => ['tr' => 'İletişim', 'en' => 'Contact'],
        'drawer'  => ['tr' => 'Hamburger menü', 'en' => 'Hamburger menu'],
        'social'  => ['tr' => 'Sosyal medya', 'en' => 'Social media'],
        'button'  => ['tr' => 'Öne çıkan düğme', 'en' => 'Highlighted button'],
        'colors'  => ['tr' => 'Renkler', 'en' => 'Colours'],
        'type'    => ['tr' => 'Yazı tipi', 'en' => 'Typography'],
        'icon'    => ['tr' => 'Site simgesi', 'en' => 'Site icon'],
    ];
}

/** Düzenleyicinin bölümleri (sıra = ekrandaki sıra). İçerik: tema seçenekleri (group) + alanlar (section). */
function design_editor_sections(): array {
    return [
        'color'   => ['part' => 'general', 'tr' => 'Renkler', 'en' => 'Colours', 'desc_tr' => 'Zemin, yazı ve vurgu rengi; tüm renkler tek yerde', 'desc_en' => 'Background, text and accent; every colour in one place', 'collect' => 'color'],
        'type'    => ['part' => 'general', 'tr' => 'Yazı tipleri', 'en' => 'Typography', 'desc_tr' => 'Başlık ve metin yazı tipi, yazı boyutu', 'desc_en' => 'Heading and body fonts, text size', 'collect' => 'type'],
        'buttons' => ['part' => 'general', 'tr' => 'Düğmeler ve formlar', 'en' => 'Buttons and forms', 'desc_tr' => 'Düğme köşeleri, görünümü ve form alanları', 'desc_en' => 'Button corners, style and form fields'],
        'logo'    => ['part' => 'general', 'tr' => 'Site adı ve logo', 'en' => 'Site title and logo', 'desc_tr' => 'İsim, logo görseli, yazı tipi ve site simgesi', 'desc_en' => 'Name, logo image, font and site icon'],
        'menu'    => ['part' => 'general', 'tr' => 'Menü', 'en' => 'Menu', 'desc_tr' => 'Menünün yeri, görünüşü ve hamburger menü', 'desc_en' => 'Menu position, look and hamburger menu'],
        'footer'  => ['part' => 'general', 'tr' => 'Alt bilgi', 'en' => 'Footer', 'desc_tr' => 'Sayfa altındaki bilgi şeridi', 'desc_en' => 'The strip at the bottom of pages'],
        'home'    => ['part' => 'pages', 'tr' => 'Ana sayfa', 'en' => 'Homepage', 'desc_tr' => 'Düzen, slayt ve fotoğraf üstü yazılar', 'desc_en' => 'Layout, slideshow and captions'],
        'lists'   => ['part' => 'pages', 'tr' => 'Proje listeleri', 'en' => 'Project lists', 'desc_tr' => 'Projeler, Seriler gibi liste sayfaları', 'desc_en' => 'List pages such as Projects and Series'],
        'project' => ['part' => 'pages', 'tr' => 'Proje sayfası', 'en' => 'Project page', 'desc_tr' => 'Açılış, fotoğraf dizisi, altyazı ve gezinme', 'desc_en' => 'Opening, photo sequence, captions and navigation'],
        'about'   => ['part' => 'pages', 'tr' => 'Hakkımda', 'en' => 'About', 'desc_tr' => 'Düzen, portre, sergi listesi ve iletişim', 'desc_en' => 'Layout, portrait, exhibitions and contact'],
        'contact' => ['part' => 'pages', 'tr' => 'İletişim', 'en' => 'Contact', 'desc_tr' => 'İletişim sayfası ve menüdeki yeri', 'desc_en' => 'Contact page and its menu link'],
        'blog'    => ['part' => 'pages', 'tr' => 'Blog', 'en' => 'Blog', 'desc_tr' => 'Blog açık/kapalı ve yazı listesi düzeni', 'desc_en' => 'Blog on/off and post list layout'],
    ];
}

/** Rengin göreli parlaklığı (0 siyah … 1 beyaz) */
function design_color_luminance(string $hex): float {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return 0.0;
    $c = array_map(function ($i) use ($hex) {
        $v = hexdec(substr($hex, $i, 2)) / 255;
        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }, [0, 2, 4]);
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

/** Vurgu renginin üzerinde okunacak yazı rengi */
function design_on_color(string $hex): string {
    return design_color_luminance($hex) > 0.4 ? '#111111' : '#ffffff';
}

/** Bir alanın geçerli hâle getirilmiş değeri (geçersizse null) */
/** Kategoriye özel ayar yapılabilen bölümler: alan anahtarı "alan@kategori" olarak saklanır (get_setting kapsamı) */
const DESIGN_SCOPED_SECTIONS = ['lists', 'project'];

/** Kategoriler: key_slug => ad (düzenleyicideki sayfa seçici) */
function design_scope_categories(): array {
    static $cats = null;
    if ($cats === null) {
        $cats = [];
        try {
            foreach (DB::getConnection()->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC) as $c) $cats[(string)$c['key_slug']] = $c;
        } catch (\Throwable $e) {}
    }
    return $cats;
}

/** Tek tek tasarlanabilen projeler: id => kayıt (yalnızca bir listeye ait olanlar) */
function design_scope_items(): array {
    static $items = null;
    if ($items === null) {
        $items = [];
        try {
            foreach (DB::getConnection()->query("SELECT * FROM `items` WHERE `type` IN (SELECT `key_slug` FROM `categories`) ORDER BY `weight` ASC, `date` DESC, `id` DESC")->fetchAll(PDO::FETCH_ASSOC) as $it) $items[(int)$it['id']] = $it;
        } catch (\Throwable $e) {}
    }
    return $items;
}

/** "alan@kategori" ya da proje sayfası için "alan@item-12" → [alan, kapsam]; geçersizse null */
function design_scoped_key(string $key): ?array {
    if (!str_contains($key, '@')) return null;
    [$base, $cat] = explode('@', $key, 2);
    $f = design_editor_fields()[$base] ?? null;
    if (!$f || !in_array($f['section'], DESIGN_SCOPED_SECTIONS, true)) return null;
    if (isset(design_scope_categories()[$cat])) return [$base, $cat];
    // Sayfaya özel: yalnızca Proje sayfası bölümü (index.php setting_item_scope önce bunu okur)
    if ($f['section'] === 'project' && preg_match('/^item-(\d+)$/', $cat, $m) && isset(design_scope_items()[(int)$m[1]])) return [$base, $cat];
    return null;
}

function design_field_sanitize(string $key, $value): ?string {
    if ($sc = design_scoped_key($key)) {
        // Boş değer: bu kategori ortak ayarı kullanır
        return (!is_array($value) && trim((string)$value) === '') ? '' : design_field_sanitize($sc[0], $value);
    }
    $f = design_editor_fields()[$key] ?? null;
    if (!$f || is_array($value)) return null;
    $v = trim((string)$value);
    switch ($f['type']) {
        case 'toggle':
            return $v === '1' ? '1' : '0';
        case 'choice':
            return isset($f['choices'][$v]) ? $v : null;
        case 'font':
            if ($v === '' && isset($f['inherit_tr'])) return '';
            return isset(design_font_choices()[$v]) ? $v : null;
        case 'slider':
            if (!is_numeric($v)) return null;
            return (string)(int)round(max($f['min'], min($f['max'], (float)$v)));
        case 'color':
            if ($v === '') return empty($f['no_auto']) ? '' : null;
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $v)) return null;
            // Açık zemin tonu: koyu bir renk açık zemin kurallarıyla okunaksız olur
            if (!empty($f['light_only']) && design_color_luminance($v) < 0.6) return null;
            return strtolower($v);
        case 'text':
            $v = (string)preg_replace('/[\x00-\x1F\x7F]/u', '', $v);
            return mb_substr($v, 0, (int)($f['max'] ?? 200));
        case 'url':
            if ($v === '') return '';
            if (preg_match('#^(https?://[^\s<>"]+|/[^\s<>"]*|mailto:[^\s<>"]+|tel:\+?[0-9 ()-]+)$#i', $v)) return mb_substr($v, 0, (int)($f['max'] ?? 300));
            return null;
        case 'image':
            if ($v === '') return '';
            // Yalnızca sitenin kendi içindeki görsel yolları (ör. uploads/branding/…, assets/brand/symbol-dark.svg); dış adres ve '..' yok
            if (str_contains($v, '..') || !preg_match('#^[A-Za-z0-9_][A-Za-z0-9_./-]*\.(svg|png|webp|jpe?g|ico|gif)$#i', $v)) return null;
            return $v;
    }
    return null;
}

/** Alanın sitede kayıtlı değeri (yoksa varsayılan) */
function design_field_value(string $key): string {
    if (design_scoped_key($key)) return design_field_sanitize($key, (string)get_setting($key, '')) ?? '';
    $f = design_editor_fields()[$key] ?? null;
    if (!$f) return '';
    if ($key === 'header_nav_transform' && get_setting($key, '') === '') {
        // Önceden yalnızca “büyük harf” aç/kapa vardı
        return get_setting('header_nav_uppercase', '0') === '1' ? 'uppercase' : 'none';
    }
    if ($key === 'header_logo_text_size' && get_setting($key, '') === '') {
        // Önceden logo yazısı boyutu logo görseli boyutundan türetilirdi
        return (string)max(13, min(28, (int)round((int)get_setting('header_logo_size', '52') * 0.36)));
    }
    $v = (string)get_setting($key, (string)$f['default']);
    return design_field_sanitize($key, $v) ?? (string)$f['default'];
}

/** Bir temaya geçilince alanın alacağı değer: temanın ayarlarında varsa o, yoksa sitedeki */
function design_field_value_for(string $tid, string $key): string {
    if ($tid !== site_template_active()) {
        $set = site_templates()[$tid]['settings'] ?? [];
        if (array_key_exists($key, $set)) {
            return design_field_sanitize($key, $set[$key]) ?? design_field_value($key);
        }
    }
    return design_field_value($key);
}

// ── TASLAK ───────────────────────────────────────────────────────────────

function design_draft_get(): ?array {
    $raw = (string)get_setting(DESIGN_DRAFT_KEY, '');
    if ($raw === '') return null;
    $d = json_decode($raw, true);
    if (!is_array($d) || !isset(site_templates()[$d['theme'] ?? ''])) return null;
    return ['theme' => (string)$d['theme'], 'opts' => (array)($d['opts'] ?? []), 'values' => (array)($d['values'] ?? []), 'at' => (string)($d['at'] ?? '')];
}

/** Gelen taslağı doğrular: yalnızca temanın kendi seçenekleri ve tanımlı alanlar, geçerli değerlerle */
function design_draft_clean(string $tid, array $opts, array $values): array {
    $clean_opts = [];
    foreach (site_templates()[$tid]['options'] ?? [] as $opt) {
        if (!array_key_exists($opt, $opts) || is_array($opts[$opt])) continue;
        $val = (string)$opts[$opt];
        if (site_template_option_writes($opt, $val)) $clean_opts[$opt] = $val;
    }
    $clean_vals = [];
    foreach ($values as $k => $v) {
        $s = design_field_sanitize((string)$k, $v);
        if ($s !== null) $clean_vals[(string)$k] = $s;
    }
    return ['theme' => $tid, 'opts' => $clean_opts, 'values' => $clean_vals];
}

function design_draft_save(array $draft): void {
    $draft['at'] = date('d.m.Y H:i');
    set_setting(DESIGN_DRAFT_KEY, json_encode($draft, JSON_UNESCAPED_UNICODE));
}

function design_draft_clear(): void {
    DB::getConnection()->prepare("DELETE FROM `settings` WHERE `key_name` = ?")->execute([DESIGN_DRAFT_KEY]);
    if (isset($GLOBALS['site_settings_cache']) && is_array($GLOBALS['site_settings_cache'])) {
        unset($GLOBALS['site_settings_cache'][DESIGN_DRAFT_KEY]);
    }
}

/**
 * Taslağın yazacağı ayarlar.
 * Başka bir temaya geçiliyorsa temanın tüm ayarları da yazılır; etkin temada yalnızca seçenekler
 * ve alanlar (etkin temada temel ayarları yeniden yazmak, düzenleyicide tek tek seçilmiş değerleri ezerdi).
 */
function design_draft_writes(array $draft): array {
    $tid       = $draft['theme'];
    $switching = $tid !== site_template_active();
    $theme_set = site_templates()[$tid]['settings'] ?? [];
    $out = $switching ? ($theme_set + [SITE_TEMPLATE_KEY => $tid]) : [];
    // Yalnızca değişenler yazılır: etkin temada, sitedeki değerinden farklı olmayan bir seçenek
    // ya da alan hiç yazılmaz (ör. zemin seçeneği, sayfa sayfa ayrı seçilmiş zeminleri ezmesin).
    foreach ($draft['opts'] as $opt => $val) {
        if (!$switching && (string)$val === site_template_option_value((string)$opt)) continue;
        foreach (site_template_option_writes((string)$opt, (string)$val) as $k => $v) $out[$k] = $v;
    }
    foreach ($draft['values'] as $k => $v) {
        if ((string)$v === design_field_value((string)$k) && !($switching && array_key_exists($k, $theme_set))) continue;
        $out[$k] = $v;
        // Görsel yüklendiyse o görsel türü de açılsın
        if ($v !== '' && in_array($k, ['brand_symbol_dark', 'brand_symbol_light'], true)) $out['brand_symbol_enabled'] = '1';
        if ($v !== '' && in_array($k, ['brand_logo_dark', 'brand_logo_light'], true)) $out['brand_logo_enabled'] = '1';
        if ($v !== '' && $k === 'brand_favicon') $out['brand_favicon_enabled'] = '1';
    }
    // Vurgu rengi değişince menünün sarı düğmesi ve açılır menüsü de değişsin —
    // yalnızca menü için ayrıca başka bir renk seçilmemişse (hâlâ eski vurguyla aynıysa)
    if (isset($out['site_accent_color'])) {
        $old_acc = design_field_value('site_accent_color') ?: '#fdd000';
        $new_acc = $out['site_accent_color'] ?: '#fdd000';
        $explicit = $out; // bu taslakta menü için ayrıca seçilmiş renkler korunur
        foreach (['header_accent_color', 'header_drawer_bg'] as $hk) {
            if (isset($explicit[$hk])) continue;
            if (strtolower((string)get_setting($hk, '#fdd000')) === strtolower($old_acc)) $out[$hk] = $new_acc;
        }
        if (isset($out['header_drawer_bg']) && !isset($explicit['header_drawer_bg']) && !isset($explicit['header_drawer_text'])) {
            $out['header_drawer_text'] = design_on_color($new_acc) === '#111111' ? '#0f0f0e' : '#ffffff';
        }
    }
    return $out;
}

/** Önizleme ('draft' çerezi) için get_setting() üzerine yazılacaklar */
function design_draft_overrides(): array {
    $d = design_draft_get();
    return $d ? design_draft_writes($d) : [];
}

/** Düzenleyici alanlarının yazdığı tüm ayarlar (geri alma yedeği için) */
function design_editor_tracked_keys(): array {
    return array_merge(array_keys(design_editor_fields()), ['brand_symbol_enabled', 'brand_logo_enabled', 'brand_favicon_enabled', 'header_accent_color', 'header_drawer_bg', 'header_drawer_text']);
}

// ── GÖRSEL YÜKLEME ───────────────────────────────────────────────────────

/** Logo / simge / site simgesi yükler; başarıda 'uploads/branding/…' yolu, hatada ['error' => mesaj] */
function design_upload_brand_file(array $file, string $prefix) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        return ['error' => __a('Dosya yüklenemedi.', 'The file could not be uploaded.')];
    }
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        return ['error' => __a('Dosya en fazla 5 MB olabilir.', 'The file can be at most 5 MB.')];
    }
    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['svg', 'png', 'webp', 'jpg', 'jpeg', 'ico', 'gif'], true)) {
        return ['error' => __a('Desteklenmeyen biçim. SVG, PNG, WEBP, JPG, GIF ya da ICO yükleyin.', 'Unsupported format. Upload SVG, PNG, WEBP, JPG, GIF or ICO.')];
    }
    if ($ext === 'svg') {
        $svg = (string)@file_get_contents($file['tmp_name']);
        if ($svg === '' || preg_match('/<script|<foreignObject|\bon[a-z]+\s*=|javascript:|<iframe|<embed|<object/i', $svg)) {
            return ['error' => __a('Güvenlik nedeniyle bu SVG dosyası kabul edilmedi.', 'This SVG file was rejected for security reasons.')];
        }
    } elseif ($ext !== 'ico' && @getimagesize($file['tmp_name']) === false) {
        return ['error' => __a('Dosya geçerli bir görsel değil.', 'The file is not a valid image.')];
    }
    $dir = dirname(__DIR__) . '/uploads/branding';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = preg_replace('/[^a-z0-9-]/', '', $prefix) . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return ['error' => __a('Dosya kaydedilemedi.', 'The file could not be saved.')];
    }
    return 'uploads/branding/' . $name;
}

/**
 * Bağımsız sayfanın şablonu (Sayfalar › boş sayfa): sade metin ya da Hakkımda / İletişim / Proje düzenlerinden biri.
 * "page_view@item-<id>" görünümü, alt anahtar ("about_template@item-<id>" gibi) düzeni tutar; get_setting sayfa kapsamını kendisi okur.
 */
const PAGE_VIEW_FIELDS = ['about' => 'about_template', 'contact' => 'contact_page_layout', 'story' => 'inner_stream_layout'];

function page_template_groups(): array {
    $f = design_editor_fields();
    $labels = ['about' => ['Hakkımda düzenleri', 'About layouts'], 'contact' => ['İletişim düzenleri (formlu)', 'Contact layouts (with form)'], 'story' => ['Foto-deneme (proje sayfası düzenleri)', 'Photo essay (project page layouts)']];
    $out = [];
    foreach (PAGE_VIEW_FIELDS as $view => $key) $out[$view] = ['tr' => $labels[$view][0], 'en' => $labels[$view][1], 'choices' => $f[$key]['choices'] ?? []];
    return $out;
}
