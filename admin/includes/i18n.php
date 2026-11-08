<?php
/**
 * admin/includes/i18n.php
 * Yönetim Paneli Çok Dilli Arayüz (i18n) Sistemi
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/helpers.php';
if (file_exists(__DIR__ . '/admin_i18n_dict.php')) {
    require_once __DIR__ . '/admin_i18n_dict.php';
}

// ── Çok Dilli Çeviri Sözlüğü ──
$admin_translations = [
    'tr' => [
        // ── Eksik Ortak Anahtarlar ──
        'templates_hub_title' => 'Temalar',
        'contact_email' => 'Birincil İletişim E-Postası',
        'size' => 'Boyut',
        'menu_templates_hub' => 'Temalar',
        'sayfa' => 'sayfa',
        'status_hidden' => 'Gizli',
        'delete_confirm' => 'Silmek istediğinizden emin misiniz?',
        'auto_translate' => 'Çevir',
        'btn_cancel' => 'Vazgeç',
        'btn_save' => 'Kaydet',
        'footer_brand_title_tr_label' => 'Footer Marka / Fotoğrafçı Başlığı (Türkçe)',
        'footer_brand_title_en_label' => 'Footer Marka / Fotoğrafçı Başlığı (İngilizce)',
        'footer_tagline_tr_label' => 'Alt Slogan / Kısa Biyografi (Türkçe)',
        'footer_tagline_en_label' => 'Alt Slogan / Kısa Biyografi (İngilizce)',
        'footer_copyright_tr_label' => 'Telif Hakkı (Copyright) Yazısı (Türkçe)',
        'footer_copyright_en_label' => 'Telif Hakkı (Copyright) Yazısı (İngilizce)',
        'footer_subnote_tr_label' => 'Ekstra Künye / Lisans Notu (Türkçe - Opsiyonel)',
        'footer_subnote_en_label' => 'Ekstra Künye / Lisans Notu (İngilizce - Opsiyonel)',
        'legal_page_breadcrumb' => 'Yasal Metinler',
        'legal_page_sub' => 'Sitede yayınlanan Aydınlatma Metni ve Açık Rıza Metnini zengin metin editörüyle düzenleyin.',
        'back_to_settings' => 'Site Ayarlarına Dön',
        'btn_save_changes' => 'Değişiklikleri Kaydet',
        'homepage_tpl_btn_title' => 'Ana Sayfa Tasarım & Şablon Ayarlarını Yönet',
        'homepage_tpl_btn' => 'Şablon & Tasarım Ayarları',
        'about_tpl_btn_title' => 'Hakkımda Sayfası Tasarım & Şablon Ayarlarını Yönet',
        'about_tpl_btn' => 'Şablon & Tasarım Ayarları',
        'btn_view_live' => 'Sitede İncele',
        'header_subtext_tr_label' => 'Alt Unvan (Türkçe)',
        'header_subtext_en_label' => 'Alt Unvan (İngilizce)',
        // Sidebar Grupları
        'nav_group_main'      => 'ANA YÖNETİM',
        'nav_group_portfolio' => 'PORTFOLYO',
        'nav_group_pages'     => 'SAYFALAR',
        'nav_group_media'     => 'MEDYA & ARAÇLAR',
        'nav_group_templates' => 'ŞABLONLAR',
        'nav_group_system'    => 'AYARLAR & ARAÇLAR',

        // Menü Linkleri
        'menu_dashboard'      => 'Genel Bakış',
        'menu_portfolio'      => 'Portfolyo',
        'menu_add_work'       => 'Yeni Çalışma Ekle',
        'menu_projects'       => 'Projeler',
        'menu_series'         => 'Seriler',
        'menu_single'         => 'Tekil Fotoğraflar',
        'menu_homepage'       => 'Ana Sayfa',
        'menu_about'          => 'Hakkımda',
        'menu_blog'           => 'Blog / Yazılar',
        'menu_contact_page'   => 'İletişim',
        'menu_page_names'     => 'Sayfa İsimleri & URL',
        'menu_messages'       => 'Gelen Mesajlar',
        'menu_media'          => 'Medya Kütüphanesi',
        'menu_converter'      => 'Dönüştürücü',
        'menu_menus'          => 'Menüler',
        'menu_categories'     => 'Kategoriler',
        'menu_languages'      => 'Diller & Çeviri',
        'menu_header'         => 'Üst Menü',
        'menu_homepage_settings' => 'Ana Sayfa',
        'menu_portfolio_home_settings' => 'Portfolyo Ana Sayfa',
        'menu_inner_pages'    => 'Portfolyo İç Sayfalar',
        'menu_blog_settings'  => 'Blog / Yazılar',
        'menu_about_settings' => 'Hakkımda',
        'menu_footer'         => 'Alt Bilgi',
        'menu_settings'       => 'Site Ayarları',
        'menu_stats'          => 'İstatistikler',
        'homepage_settings_title' => 'Ana Sayfa Şablon & Tasarım Ayarları',
        'homepage_settings_sub'   => 'Ana sayfa fotoğraf vitrini, tam ekran slayt, monografi split, müze paspartu, film şeridi ve ızgara düzenlerini canlı önizleme ile yönetin.',

        // Arama & Üst Bar & Tema
        'search_placeholder'  => 'Arama yapın... (⌘K)',
        'search_empty'        => 'ile eşleşen sonuç bulunamadı.',
        'topbar_view_site'    => 'Siteyi Görüntüle',
        'topbar_collapse'     => 'Sol Menüyü Daralt (Ctrl+[)',
        'topbar_expand'       => 'Sol Menüyü Genişlet (Ctrl+[)',
        'topbar_btn_collapse' => 'Daralt',
        'topbar_btn_expand'   => 'Genişlet',
        'logout_title'        => 'Güvenli Çıkış',
        'switch_lang_title'   => 'Yönetim Paneli Dilini Değiştir',
        'theme_toggle_title'  => 'Karanlık / Aydınlık Tema (Ctrl+Shift+D)',
        'theme_dark'          => 'Karanlık Mod',
        'theme_light'         => 'Aydınlık Mod',

        // Breadcrumbs Kökleri
        'bc_overview'         => 'Genel Bakış',
        'bc_portfolio'        => 'Portfolyo',
        'bc_pages'            => 'Sayfalar',
        'bc_media'            => 'Medya & Araçlar',
        'bc_templates'        => 'Şablonlar',
        'bc_system'           => 'Ayarlar & Araçlar',
        'bc_control_panel'    => 'Kontrol Paneli',

        // Genel Eylemler & Durumlar
        'save'                => 'Kaydet',
        'save_changes'        => 'Değişiklikleri Kaydet',
        'save_all_changes'    => 'Tüm Değişiklikleri Kaydet',
        'saving'              => 'Kaydediliyor...',
        'cancel'              => 'Vazgeç',
        'delete'              => 'Sil',
        'edit'                => 'Düzenle',
        'add_new'             => 'Yeni Ekle',
        'remove'              => 'Kaldır',
        'actions'             => 'İşlemler',
        'status'              => 'Durum',
        'order'               => 'Sıra',
        'reorder_drag'        => 'Sıralamak için sürükleyin',
        'status_published'    => 'Yayında',
        'status_draft'        => 'Taslak',
        'status_active'       => 'Aktif',
        'status_inactive'     => 'Pasif',
        'status_live_active'  => 'Yayında (Aktif)',
        'status_site_hidden'  => 'Sitede Gizli',
        'is_default'          => 'Varsayılan',
        'default_opening_lang'=> 'Varsayılan Açılış Dili',
        'set_as_default'      => 'Varsayılan Yap',
        'current_panel_lang'  => 'Şu Anki Panel Dili',
        'use_panel_in_this_lang' => 'Paneli Bu Dilde Kullan',
        'installed_badge'     => 'Ekli ✓',
        'add_btn'             => '+ Ekle',
        'edit_urls'           => 'Düzenle / URL\'ler',
        'remove_lang'         => 'Çıkar (Sil)',
        'total_content'       => 'içerik',
        'reorder_success'     => 'Sıralama başarıyla kaydedildi.',
        'copied_clipboard'    => 'Panoya kopyalandı.',

        // Tablo Sütunları & Ortak Alanlar
        'title'               => 'Başlık',
        'description'         => 'Açıklama',
        'location'            => 'Konum',
        'year'                => 'Yıl',
        'cover_image'         => 'Kapak Fotoğrafı',
        'image'               => 'Fotoğraf',
        'category'            => 'Kategori',
        'type'                => 'Tür',
        'date'                => 'Tarih',
        'preview'             => 'Önizleme',
        'slug'                => 'URL Slug',
        'url'                 => 'Hedef URL',
        'direction'           => 'Yön',
        'fallback_lang'       => 'Yedek Dil',
        'completeness'        => 'Çeviri Doluluğu',
        'story_canvas'        => 'Hikaye Tuvalini Aç →',

        // Ana Sayfa Seçkisi
        'homepage_showcase_title' => 'Ana Sayfa Fotoğraf Seçkisi',
        'homepage_showcase_sub'   => 'Ana sayfada ziyaretçilere rastgele ve her tıklamada dönüşümlü olarak sergilenecek küratöryel fotoğraf seçkisini yönetin.',
        'view_homepage'           => 'Ana Sayfayı Gör',
        'import_from_portfolio'   => 'Portfolyodan İçe Aktar',
        'add_new_photo'           => 'Yeni Fotoğraf Ekle',
        'total_showcase'          => 'Toplam Seçki',
        'active_showcase'         => 'Aktif Yayında',
        'showcase_photos_count'   => 'Vitrin Fotoğrafları',

        // Dil Bölümü & Matris
        'languages_page_title'    => 'Dil Bölümü & Çok Dilli Ayarlar',
        'languages_page_sub'      => 'Sitede aktif olacak dilleri yönetin, yeni dil ekleyin, varsayılan dili ve panel arayüz dilini yapılandırın.',
        'open_translation_studio' => 'Merkezi Çeviri Stüdyosunu Aç →',
        'installed_languages_title' => 'Sitede Ekli ve Yayındaki Diller',
        'preset_languages_title'  => 'Hızlı Dil Ekleme & Hazır Dil Kütüphanesi',
        'preset_languages_sub'      => 'Tek tıkla hazır dil şablonunu siteye ekleyin, yayınlayın ve tüm arayüzü bu dille kullanın.',
        'admin_lang_behavior_sub'   => 'Yönetim paneli varsayılan dilini, site açılış dilini ve dil değiştirici görünümünü yapılandırın.',
        'dir_ltr'                   => 'LTR (Soldan Sağa)',
        'dir_rtl'                   => 'RTL (Sağdan Sola)',
        'edit_lang_modal_title'     => 'Dil Ayarlarını & URL Yapısını Düzenle',
        'lang_name_native_label'    => 'Kendi Dilindeki Adı (Native)',
        'lang_name_display_label'   => 'Arayüzdeki Dil Adı',
        'flag_icon_label'           => 'Bayrak / Simge',
        'locale_code'               => 'Yerel Kod (Locale)',
        'static_page_slugs'         => 'Sabit Sayfa URL Uzantıları (Slugs)',
        'ui_trans_opt'              => 'Yönetim Paneli & Site Arayüz Çeviri Özelleştirmeleri (Opsiyonel)',
        'default_trans_hint'        => 'Varsayılan çeviri kullanılır',
        'lang_can_be_admin'         => 'Bu dil Yönetim Paneli arayüz dili olarak kullanılabilir',
        'custom_lang_btn'         => 'Listede Olmayan Özel Dil Ekle →',
        'admin_lang_behavior_title' => 'Yönetim Paneli Dili & Site Dil Davranışı Ayarları',
        'admin_panel_lang_label'  => 'Yönetim Paneli Varsayılan Dili',
        'auto_detect_browser_lang'=> 'Ziyaretçiyi Tarayıcı Diline Göre Otomatik Yönlendir',
        'show_lang_switcher_header' => 'Site Üst Menüsünde Dil Seçici Gösterilsin',
        'lang_switcher_format'    => 'Dil Seçici Görünüm Formatı',
        'hide_switcher_single'    => 'Sitede tek aktif dil kaldığında seçiciyi gizle',

        // Merkezi Çeviri Stüdyosu
        'translation_studio_title' => 'Merkezi Çeviri Stüdyosu (Toplu Matris)',
        'translation_studio_sub'   => 'Sitedeki tüm dilleri tek ekranda yan yana karşılaştırın, boş alanları tek tıkla otomatik çevirin ve topluca kaydedin.',
        'back_to_languages'        => '← Dil Yönetimine Dön',
        'translate_all_empty_in_matrix' => 'Tablodaki Tüm Boş Alanları Otomatik Çevir',
        'translate_column_empty'   => 'Boşları Çevir',
        'auto_translated_saved'    => '✓ Çevrildi & Kaydedildi',
        'tab_portfolio_works'      => 'Portfolyo Çalışmaları',
        'tab_categories'           => 'Kategoriler',
        'tab_menus'                => 'Üst Menü Navigasyonu',
        'tab_slides'               => 'Ana Sayfa Seçkisi',
        'tab_settings'             => 'Site & SEO Metinleri',
        'record_item'              => 'Kayıt / Öğe',

        // Ayarlar & Özelleştiriciler
        'settings_page_title'       => 'Site Ayarları & Güvenlik',
        'settings_page_sub'         => 'Genel SEO meta verileri, sosyal medya kanalları ve yönetici hesap güvenliği.',
        'settings_nav_languages'    => 'Dil Bölümü & Çok Dilli Ayarlar',
        'settings_nav_homepage_settings' => 'Ana Sayfa',
        'settings_nav_portfolio_home_settings' => 'Portfolyo Ana Sayfa',
        'settings_nav_header'       => 'Üst Menü',
        'settings_nav_inner_pages'  => 'Portfolyo',
        'settings_nav_blog_settings'=> 'Blog / Yazılar',
        'settings_nav_about_settings' => 'Hakkımda',
        'settings_nav_footer'       => 'Alt Bilgi',
        'settings_customizers_title'=> 'Site Ayarları Alt Bölümleri & Özelleştiriciler',
        'settings_customizers_sub'  => 'Dil yönetimi, üst menü, portfolyo hikaye/galeri dizilimi ve alt bilgi tasarımlarını yönetin.',
        'settings_card_lang_title'  => 'Dil Yönetimi & Panel Dili',
        'settings_card_lang_badge'  => 'Yeni',
        'settings_card_lang_desc'   => 'Sitedeki dilleri ekleyin/çıkarın, yeni dil tanımlayın ve yönetim paneli dilini belirleyin.',
        'settings_card_homepage_title' => 'Ana Sayfa & Vitrin',
        'settings_card_homepage_desc'  => 'Tam Ekran Slayt, Monografi Split, Müze Paspartu, Film Şeridi, Dikey Bölüm ve Izgara tasarımları.',
        'settings_card_header_desc' => 'Hamburger, yatay bar, sabit buzlu cam, logo ve çekmece stilleri.',
        'settings_card_inner_desc'  => 'Monografi sağ/sol açılış, önce yazı sonra fotoğraf, sabit split, dergi ve galeri düzenleri.',
        'settings_card_about_title' => 'Hakkımda & Mizanpaj',
        'settings_card_about_desc'  => 'Klasik Monografi, Sinematik Portre Hero, Minimalist Split, Dergi Profili ve Bento Grid tasarımları.',
        'settings_card_footer_desc' => 'Telif metni, sosyal medya ikonları, sütun yapıları ve özel alt linkler.',
        'settings_general_seo_title'=> 'Genel & SEO Ayarları',
        'settings_site_title_tr'    => 'Site Başlığı (Türkçe)',
        'settings_site_title_en'    => 'Site Başlığı (İngilizce)',
        'settings_site_desc_tr'     => 'Site Açıklaması / Meta Description (Türkçe)',
        'settings_site_desc_en'     => 'Site Açıklaması / Meta Description (İngilizce)',
        'settings_author_name'      => 'Fotoğrafçı / Yazar Adı (Author Meta)',
        'settings_maps_api'         => 'Google Harita / Maps API Anahtarı',
        'settings_maps_api_desc'    => 'Google Cloud Console üzerinden edindiğiniz Maps JavaScript API anahtarını buraya ekleyebilirsiniz. Boş bırakıldığında Medya Kütüphanesindeki dünya haritası ücretsiz OpenStreetMap altyapısıyla çalışır.',
        'settings_save_btn'         => 'Ayarları Kaydet',
        'settings_lang_summary_title' => 'Dil Bölümü (Site & Panel Dilleri)',
        'settings_manage_langs_btn' => 'Dilleri Yönet →',
        'settings_lang_summary_desc'=> 'Sitedeki mevcut dilleri ekleyip çıkartabilir, yeni dil tanımlayabilir, varsayılan açılış dilini ve yönetim panelini hangi dilde kullanacağınızı ayarlayabilirsiniz.',
        'settings_password_title'   => 'Yönetici Parolasını Değiştir',
        'settings_curr_pass'        => 'Mevcut Parola',
        'settings_new_pass'         => 'Yeni Parola',
        'settings_new_pass_confirm' => 'Yeni Parola (Tekrar)',
        'settings_update_pass_btn'  => 'Parolayı Güncelle',
        'settings_backup_title'     => 'Veritabanı Yedeği & İndir',
        'settings_backup_desc'      => 'Sitenin tüm içerik, portfolyo, kategori ve ayarlarını içeren SQLite veritabanı yedeğini tek tıkla bilgisayarınıza indirin.',
        'settings_download_backup_btn' => 'Veritabanı Yedeğini İndir (.db)',
        'settings_legal_title'      => 'Hukuki Metinler & KVKK / Aydınlatma',
        'settings_legal_desc'       => 'Sitede yayınlanacak Çerez Politikası, KVKK / Gizlilik Bildirimi ve Kullanım Şartları sayfalarını yönetin.',
        'settings_manage_legal_btn' => 'Hukuki Sayfaları Düzenle →',

        // Kategoriler
        'categories_page_title'     => 'Portfolyo Kategorileri',
        'categories_page_sub'       => 'İstediğiniz kategoriyi ekleyin, düzenleyin, sıralayın veya silin. Kategorilerinizi tek tıkla sitenin üst menüsüne bağlayabilirsiniz.',
        'cat_add_btn'               => 'Yeni Kategori Ekle',
        'cat_registered'            => 'Kayıtlı Kategoriler',
        'cat_drag_hint'             => 'Sıralamak için satırları sürükleyip bırakın',
        'cat_th_order'              => 'Sıra',
        'cat_th_name_tr'            => 'Kategori Adı (TR)',
        'cat_th_name_en'            => 'Kategori Adı (EN)',
        'cat_th_type'               => 'İç kod',
        'cat_th_slug'               => 'Kalıcı URL Yolu',
        'cat_th_count'              => 'İçerik',
        'cat_th_menu'               => 'Sitenin Üst Menüsü',
        'cat_menu_active'           => '● Menüde Açık',
        'cat_menu_inactive'         => '○ Menüde Kapalı',
        'cat_items_unit'            => 'adet',
        'cat_modal_add'             => 'Yeni Kategori Ekle',
        'cat_modal_edit'            => 'Kategoriyi Düzenle',
        'cat_modal_name_tr'         => 'Kategori Adı (Türkçe) *',
        'cat_modal_name_en'         => 'Kategori Adı (İngilizce)',
        'cat_modal_auto_translate'  => 'Diğer Dillere Çevir',
        'cat_modal_key_slug'        => 'Sistem Kodu (Tavsiye: Değiştirmeyin)',
        'cat_modal_show_menu'       => 'Sitenin Üst Menüsüne Bağlantı Olarak Ekle',

        // Menüler
        'menus_page_title'          => 'Üst Menü & Navigasyon Yönetimi',
        'menus_page_sub'            => 'Sitedeki ana menü öğelerini sıralayın, yeni menü bağlantısı veya dikey ayraç ekleyin.',
        'menus_header_design'       => 'Üst Menü Tasarım Ayarları',
        'menus_social_links'        => 'Sosyal Medya Linkleri',
        'menus_live_preview'        => 'Web Sitesi Canlı Menü Görünümü',
        'menus_live_preview_hint'   => '(Aktif olan öğeler ve menüye eklediğiniz ikonlar sitede bu düzende görünür)',
        'menus_no_active'           => 'Aktif menü öğesi bulunmuyor.',
        'menu_add_link_btn'         => 'Yeni Menü Ekle',
        'menu_add_sep_btn'          => '+ Ayraç Ekle',
        'menu_preview_title'        => 'Canlı Üst Menü Önizlemesi',
        'menu_reorder_title'        => 'Menü Sıralaması (Sürükle & Bırak)',
        'menu_reorder_sub'          => 'Fare ile tutarak yukarı / aşağı taşıyın',
        'menu_th_title'             => 'Menü Öğesi / Başlık',
        'menu_th_url'               => 'Bağlantı (URL)',
        'menu_th_type'              => 'Tür',
        'menu_separator_label'      => '| Dikey Ayraç',
        'menu_separator_desc'       => 'Menü öğeleri arasındaki görsel dikey çizgi',
        'menu_type_link'            => 'Bağlantı',
        'menu_type_separator'       => 'Ayraç',
        'menu_sep_before'           => '| Öncesi Ayraç',
        'menus_modal_add'           => 'Yeni Menü Ekle',
        'menus_modal_edit'          => 'Menü Öğesini Düzenle',
        'menus_modal_edit_sep'      => 'Dikey Ayracı Düzenle',
        'menus_item_type'           => 'Menü Öğesi Türü',
        'menus_type_page_link'      => 'Sayfa / Bağlantı',
        'menus_title_tr'            => 'Türkçe Başlık *',
        'menus_title_en'            => 'İngilizce Başlık',
        'menus_url_tr'              => 'Türkçe URL *',
        'menus_url_en'              => 'İngilizce URL',
        'menus_url_hint'            => 'Site içi veya harici link',
        'menus_sep_notice_title'    => '| Dikey Ayraç Öğesi',
        'menus_sep_notice_desc'     => 'Bu öğe menüde link değil, diğer linkler arasında dikey bir ayraç çizgisi olarak görünür. Kaydettikten sonra fare ile listede dilediğiniz sıraya taşıyabilirsiniz.',
        'menus_sep_before_opt'      => 'Öncesine dikey ayraç çizgisi koy (|)',
        'menus_active_opt'          => 'Menüde hemen aktif olarak görünsün',
        'menus_btn_add'             => 'Menüye Ekle',
        'menus_btn_update'          => 'Değişiklikleri Güncelle',
        'menus_social_modal_title'  => 'Sosyal Medya Bağlantıları',
        'menus_social_modal_desc'   => 'Sosyal medya profil bağlantılarınızı ve üst menüde gösterilip gösterilmeyeceğini buradan belirleyin.',
        'menus_show_in_header'      => 'Üst Menüde Göster',
        'registered_works'          => 'Kayıtlı Çalışmalar',
        'search_works'              => 'Çalışmalarda ara...',
        'drag_to_reorder'           => 'Sıralamak için satırları sürükleyin',
        'all_works'                 => 'Tüm Çalışmalar',
        'category'                  => 'Kategori',

        // Modallar & Ortak Medya Seçici
        'slide_modal_add'           => 'Yeni Seçki Fotoğrafı Ekle',
        'slide_modal_edit'          => 'Seçki Fotoğrafını Düzenle',
        'slide_modal_import'        => 'Portfolyodan Ana Sayfaya Aktar',
        'slide_choose_media'        => 'Medyadan Seç',
        'slide_image_url'           => 'Fotoğraf Dosyası (URL / Yol)',
        'slide_title_tr'            => 'Başlık (Türkçe)',
        'slide_title_en'            => 'Başlık (İngilizce)',
        'slide_desc_tr'             => 'Açıklama (Türkçe)',
        'slide_desc_en'             => 'Açıklama (İngilizce)',
        'slide_year'                => 'Yıl',
        'slide_location'            => 'Konum / Lokasyon',
        'slide_link'                => 'Tıklama Bağlantısı (İsteğe Bağlı)',
        'slide_save_btn'            => 'Seçkiyi Kaydet',
        'media_modal_title'         => 'Medya Kütüphanesinden Görsel Seç',
        'media_modal_folder'        => 'Klasör:',
        'media_modal_all_folders'   => 'Tüm Klasörler (Tüm Kütüphane)',
        'media_modal_sort'          => 'Sırala:',
        'media_modal_search_ph'     => 'Dosya adı veya tarih ara...',
        'media_modal_upload_btn'    => 'Görsel Yükle',
        'media_modal_cancel'        => 'Vazgeç / İptal',
        'media_modal_scanning'      => 'Görseller taranıyor...',
        'sort_date_desc'            => 'Çekim Tarihi (Yeniden Eskiye)',
        'sort_date_asc'             => 'Çekim Tarihi (Eskiden Yeniye)',
        'sort_newest'               => 'Yükleme Tarihi (Son Eklenenler)',
        'sort_oldest'               => 'Yükleme Tarihi (İlk Eklenenler)',
        'sort_name_asc'             => 'Dosya Adı (A → Z)',
        'sort_name_desc'            => 'Dosya Adı (Z → A)',
        'sort_size'                 => 'Boyut (Büyükten Küçüğe)',

        // Hakkımda, Medya, Portfolyo
        'about_page_title'          => 'Hakkımda & Biyografi Yönetimi',
        'about_page_sub'            => 'Biyografi metinleri, editoryal portre, iletişim bilgileri ve sergi kronolojisi.',
        'media_page_title'          => 'Medya Kütüphanesi',
        'media_page_sub'            => 'Belgesel fotoğraf arşivi, dünya coğrafi haritası ve EXIF çekim verileri.',
        'portfolio_page_sub'        => 'Tüm portfolyo kategorilerinizi ve çalışmalarınızı tek merkezden yönetin ve sıralayın.',
        'portfolio_add_new_work'    => '+ Yeni Çalışma Ekle',
        'portfolio_all_categories'  => 'Tüm Kategoriler',
        'header_settings_title'     => 'Üst Menü Tasarım & Davranış Ayarları',
        'header_settings_sub'       => 'Üst menünüzü ister hamburger menü, ister yatay menü, ister fotoğraf üstünde sabit veya transparan yatay bar olarak özelleştirin.',
        'inner_pages_title'         => 'Portfolyo (Detay Mizanpajı) Tasarım Ayarları',
        'inner_pages_sub'           => 'Projeler, Seriler ve Tekil çalışma sayfalarının açılış mizanpajını ve galeri akışını özelleştirin.',
        'about_settings_title'      => 'Hakkımda Tasarım & Mizanpaj Ayarları',
        'about_settings_sub'        => 'Biyografi, portre mizanpajı, iletişim alanları ve sergi kronolojisi için hazır düzenleri seçin veya özelleştirin.',
        'footer_settings_title'     => 'Alt Bilgi Tasarım & İçerik Ayarları',
        'footer_settings_sub'       => 'Web sitenizin alt bilgisindeki yazıları, telif metnini, sosyal medya ikonlarını ve bağlantıları yönetin.',

        // Words & Units
        'Gün'                       => 'Gün',
        'Yıl'                       => 'Yıl',
        'Yenile'                    => 'Yenile',
        'Ziyaretçi Analizi'         => 'Ziyaretçi Analizi',
        'Hukuki sayfaları ve alt bilgi (footer) bağlantılarını sitede aç / kapat' => 'Hukuki sayfaları ve alt bilgi (footer) bağlantılarını sitede aç / kapat',
        'Yasal Metinleri Düzenle →' => 'Yasal Metinleri Düzenle →',
        '1. Aydınlatma Metnini Düzenle' => '1. Aydınlatma Metnini Düzenle',
        '2. Açık Rıza Metnini Düzenle'  => '2. Açık Rıza Metnini Düzenle',
        'Aydınlatma Metnini Yeni Sekmede Görüntüle' => 'Aydınlatma Metnini Yeni Sekmede Görüntüle',
        'Privacy Notice Yeni Sekmede Görüntüle'     => 'Privacy Notice Yeni Sekmede Görüntüle',
        'Açık Rıza Metnini Yeni Sekmede Görüntüle'  => 'Açık Rıza Metnini Yeni Sekmede Görüntüle',
        'Explicit Consent Yeni Sekmede Görüntüle'   => 'Explicit Consent Yeni Sekmede Görüntüle',

        // Settings (KVKK)
        'legal_settings_title'      => 'KVKK & Hukuki Metinler',
        'legal_switch_title'        => 'Hukuki sayfaları ve alt bilgi (footer) bağlantılarını sitede aç / kapat',
        'legal_status_active'       => 'Yayında (Aktif)',
        'legal_status_hidden'       => 'Gizli (Kapalı)',
        'legal_desc_active'         => '6698 Sayılı KVKK uyarınca sitede yasal sayfalar ve alt bilgi (footer) bağlantıları şu an açık.',
        'legal_desc_hidden'         => 'Yasal sayfalar ve alt bilgi (footer) bağlantıları şu anda kapalıdır (ziyaretçilere gizlenmiştir).',
        'legal_edit_btn'            => 'Yasal Metinleri Düzenle →',
        'legal_settings_body_desc'  => 'Aydınlatma Metni (Privacy Notice) ve Açık Rıza Metni (Explicit Consent) içeriklerini sayfa düzenini bozmadan, zengin görsel metin editörü (WYSIWYG) aracılığıyla ayrı bir yönetim sayfasında rahatça düzenleyebilirsiniz.',
        'legal_edit_aydinlatma'     => '1. Aydınlatma Metnini Düzenle',
        'legal_edit_acik_riza'      => '2. Açık Rıza Metnini Düzenle',
        'legal_view_aydinlatma_tr'  => 'Aydınlatma Metnini Yeni Sekmede Görüntüle',
        'legal_view_aydinlatma_en'  => 'Privacy Notice Yeni Sekmede Görüntüle',
        'legal_view_acik_riza_tr'   => 'Açık Rıza Metnini Yeni Sekmede Görüntüle',
        'legal_view_acik_riza_en'   => 'Explicit Consent Yeni Sekmede Görüntüle',

        // Languages & Behavior
        'admin_panel_lang_hint'     => 'Yönetim panelinin hangi dilde görüneceğini belirler.',
        'admin_show_lang_switcher_label' => 'Yönetim paneli üst çubuğunda hızlı dil değiştirici gösterilsin',
        'default_site_lang_label'   => 'Varsayılan Açılış Dili (Kök Dizin /)',
        'default_site_lang_hint'    => 'Ziyaretçiler doğrudan {site} adresine girdiğinde yönlendirilecek ana dil.',
        'auto_detect_browser_lang_label' => 'İlk ziyarette ziyaretçinin tarayıcı dilini (Accept-Language) otomatik algıla',
        'lang_switcher_format_label'=> 'Site Menüsü Dil Seçici Görünüm Formatı',
        'fmt_code'                  => 'Kısa Kod (EN / TR / HY)',
        'fmt_flag_code'             => 'Bayrak + Kısa Kod (🇬🇧 EN / 🇹🇷 TR)',
        'fmt_native'                => 'Dillerin Kendi Orijinal Adı (English / Türkçe / Հայերեն)',
        'hide_switcher_single_label'=> 'Sitede tek aktif dil kaldığında seçiciyi menüden gizle',
        'seo_hreflang_label'        => 'Google SEO için hreflang alternatif dil etiketlerini otomatik üret',
        'disabled_lang_behavior_label' => 'Siteden kaldırılan veya pasife alınan dil URL\'lerine tıklandığında ne yapılsın?',
        'dis_behavior_redirect'     => 'Varsayılan Dile Akıllı Yönlendirme Yap (302 Redirect - Tavsiye Edilen)',
        'dis_behavior_404'          => '404 Sayfa Bulunamadı Göster',
        'disabled_lang_hint'        => 'Örneğin Türkçe dilini siteden çıkarttığınızda eski /tr/... linklerine tıklayan ziyaretçilerin kırık link görmemesi için varsayılan dile yönlendirilmesini sağlar.',
        'save_behavior_settings_btn'=> 'Dil & Davranış Ayarlarını Kaydet',
        'status_inactive_note'      => 'Şu anda pasif - seçildiğinde aktif olur',

        // Menus
        'menus_preview_en_title'    => 'İngilizce Menü Önizlemesi',
        'menus_preview_tr_title'    => 'Türkçe Menü Önizlemesi',

        // Convert Images
        'convert_page_title'        => 'Toplu AVIF ve WebP Görsel Dönüştürücü',
        'convert_card_title'        => 'Görsel Optimizasyonu & Dönüşüm Durumu',
        'convert_card_desc'         => 'Sitenizdeki tüm fotoğraflar modern web standartlarına uygun olarak otomatik olarak hem AVIF hem de WebP formatlarına dönüştürülür. Ziyaretçinin tarayıcısı en uygun formatı seçerek en yüksek kalitede ve en küçük dosya boyutuyla sayfaları anında açar.',
        'convert_stat_orig'         => 'Orijinal Fotoğraflar',
        'convert_stat_orig_sub'     => 'content/ klasöründe',
        'convert_stat_avif'         => 'Üretilen AVIF Varyantları',
        'convert_stat_webp'         => 'Üretilen WebP Varyantları',
        'convert_progress_converting' => 'Dönüştürülüyor...',
        'convert_btn_generate'      => 'Eksik AVIF ve WebP Görsellerini Üret',
        'convert_js_processing'     => 'İşlem yapılıyor, lütfen bekleyin...',
        'convert_js_photos_processed' => 'fotoğraf işlendi...',
        'convert_js_all_done'       => 'Tüm fotoğraflar başarıyla AVIF ve WebP formatında hazırlandı!',
        'convert_js_rescan'         => 'Yeniden Tara ve Güncelle',
        'convert_js_err'            => 'Dönüştürme sırasında bir hata oluştu.',
        'convert_js_net_err'        => 'Sunucu ile iletişim hatası.',

        // Stats
        'stats_page_desc'           => 'Google Analytics 4 API üzerinden portfolyo etkileşimi, coğrafi dağılım ve dönüşüm metrikleri.',
        'stats_btn_config'          => 'API Yapılandırması',
        'stats_status_live'         => '● CANLI GA4 VERİSİ',
        'stats_status_live_desc_prefix' => 'Google Analytics 4 API bağlantısı aktif',
        'stats_status_demo'         => 'DEMO ÖNİZLEME',
        'stats_status_demo_desc'    => 'Google Analytics API anahtarı bekleniyor — Tasarıma uygun gerçekçi portfolyo verileri gösteriliyor.',
        'stats_last_sync'           => 'Son Eşitleme',
        'stats_btn_connect_google'  => 'Google\'a Bağlan →',
        'stats_btn_refresh'         => 'Yenile',
        'stats_refresh_title'       => 'Google Analytics\'ten güncel verileri çek',
        'stats_unit_day'            => 'Gün',
        'stats_unit_year'           => 'Yıl',
        'stats_kpi_active_users'    => 'Aktif Kullanıcı',
        'stats_new_visitors'        => 'yeni ziyaretçi',
        'stats_kpi_avg_duration'    => 'Ort. Etkileşim Süresi',
    ],

    'en' => [
        // ── Missing Shared Keys ──
        'templates_hub_title' => 'Themes',
        'contact_email' => 'Primary Contact Email',
        'size' => 'Size',
        'menu_templates_hub' => 'Themes',
        'sayfa' => 'pages',
        'status_hidden' => 'Hidden',
        'delete_confirm' => 'Are you sure you want to delete?',
        'auto_translate' => 'Translate',
        'btn_cancel' => 'Cancel',
        'btn_save' => 'Save',
        'footer_brand_title_tr_label' => 'Footer Brand / Photographer Title (Turkish)',
        'footer_brand_title_en_label' => 'Footer Brand / Photographer Title (English)',
        'footer_tagline_tr_label' => 'Subtitle / Short Bio (Turkish)',
        'footer_tagline_en_label' => 'Subtitle / Short Bio (English)',
        'footer_copyright_tr_label' => 'Copyright Text (Turkish)',
        'footer_copyright_en_label' => 'Copyright Text (English)',
        'footer_subnote_tr_label' => 'Extra Colophon / License Notice (Turkish - Optional)',
        'footer_subnote_en_label' => 'Extra Colophon / License Notice (English - Optional)',
        'legal_page_breadcrumb' => 'Legal Texts',
        'legal_page_sub' => 'Edit the Privacy Notice and Explicit Consent texts published on the site with the rich text editor.',
        'back_to_settings' => 'Back to Site Settings',
        'btn_save_changes' => 'Save Changes',
        'homepage_tpl_btn_title' => 'Manage Homepage Design & Template Settings',
        'homepage_tpl_btn' => 'Template & Design Settings',
        'about_tpl_btn_title' => 'Manage About Page Design & Template Settings',
        'about_tpl_btn' => 'Template & Design Settings',
        'btn_view_live' => 'View on Site',
        'header_subtext_tr_label' => 'Subtitle / Tagline (Turkish)',
        'header_subtext_en_label' => 'Subtitle / Tagline (English)',
        'dir_rtl' => 'RTL (Right to Left)',
        'dir_ltr' => 'LTR (Left to Right)',
        'preset_languages_sub' => 'Add a ready-made language template to the site with one click, publish it, and use the whole interface in that language.',
        'admin_lang_behavior_sub' => 'Configure the default admin panel language, the site opening language, and the language switcher appearance.',
        'locale_code' => 'Locale Code',
        // Sidebar Groups
        'nav_group_main'      => 'MAIN MANAGEMENT',
        'nav_group_portfolio' => 'PORTFOLIO',
        'nav_group_pages'     => 'PAGES',
        'nav_group_media'     => 'MEDIA & TOOLS',
        'nav_group_templates' => 'TEMPLATES',
        'nav_group_system'    => 'SETTINGS & TOOLS',

        // Menu Links
        'menu_dashboard'      => 'Dashboard',
        'menu_portfolio'      => 'Portfolio',
        'menu_add_work'       => 'Add New Work',
        'menu_projects'       => 'Projects',
        'menu_series'         => 'Series',
        'menu_single'         => 'Single Photos',
        'menu_homepage'       => 'Homepage',
        'menu_about'          => 'About Me',
        'menu_blog'           => 'Blog / Articles',
        'menu_contact_page'   => 'Contact',
        'menu_page_names'     => 'Page Names & URLs',
        'menu_messages'       => 'Messages',
        'menu_media'          => 'Media Library',
        'menu_converter'      => 'Converter',
        'menu_menus'          => 'Menus',
        'menu_categories'     => 'Categories',
        'menu_languages'      => 'Languages & Translate',
        'menu_header'         => 'Top Menu',
        'menu_homepage_settings' => 'Homepage',
        'menu_portfolio_home_settings' => 'Portfolio Homepage',
        'menu_inner_pages'    => 'Portfolio Inner Pages',
        'menu_blog_settings'  => 'Blog / Articles',
        'menu_about_settings' => 'About Me',
        'menu_footer'         => 'Footer',
        'menu_settings'       => 'Site Settings',
        'menu_stats'          => 'Analytics',
        'homepage_settings_title' => 'Homepage Layout & Template Settings',
        'homepage_settings_sub'   => 'Manage homepage showcase, full-bleed slider, monograph split, museum passe-partout, filmstrip, and portfolio grid with live preview.',

        // Search & Topbar & Theme
        'search_placeholder'  => 'Search anything... (⌘K)',
        'search_empty'        => 'no results found for',
        'topbar_view_site'    => 'View Site',
        'topbar_collapse'     => 'Collapse Sidebar (Ctrl+[)',
        'topbar_expand'       => 'Expand Sidebar (Ctrl+[)',
        'topbar_btn_collapse' => 'Collapse',
        'topbar_btn_expand'   => 'Expand',
        'logout_title'        => 'Log Out',
        'switch_lang_title'   => 'Switch Admin Panel Language',
        'theme_toggle_title'  => 'Dark / Light Theme (Ctrl+Shift+D)',
        'theme_dark'          => 'Dark Mode',
        'theme_light'         => 'Light Mode',

        // Breadcrumbs Roots
        'bc_overview'         => 'Overview',
        'bc_portfolio'        => 'Portfolio',
        'bc_pages'            => 'Pages',
        'bc_media'            => 'Media & Tools',
        'bc_templates'        => 'Templates',
        'bc_system'           => 'Settings & Tools',
        'bc_control_panel'    => 'Dashboard',

        // Common Actions & Statuses
        'save'                => 'Save',
        'save_changes'        => 'Save Changes',
        'save_all_changes'    => 'Save All Changes',
        'saving'              => 'Saving...',
        'cancel'              => 'Cancel',
        'delete'              => 'Delete',
        'edit'                => 'Edit',
        'add_new'             => 'Add New',
        'remove'              => 'Remove',
        'actions'             => 'Actions',
        'status'              => 'Status',
        'order'               => 'Order',
        'reorder_drag'        => 'Drag to reorder',
        'status_published'    => 'Published',
        'status_draft'        => 'Draft',
        'status_active'       => 'Active',
        'status_inactive'     => 'Inactive',
        'status_live_active'  => 'Live (Active)',
        'status_site_hidden'  => 'Hidden on Site',
        'is_default'          => 'Default',
        'default_opening_lang'=> 'Default Homepage Language',
        'set_as_default'      => 'Set as Default',
        'current_panel_lang'  => 'Current Panel Language',
        'use_panel_in_this_lang' => 'Use Panel in This Language',
        'installed_badge'     => 'Installed ✓',
        'add_btn'             => '+ Add',
        'edit_urls'           => 'Edit / URLs',
        'remove_lang'         => 'Remove',
        'total_content'       => 'items',
        'reorder_success'     => 'Order saved successfully.',
        'copied_clipboard'    => 'Copied to clipboard.',

        // Table Columns & Common Fields
        'title'               => 'Title',
        'description'         => 'Description',
        'location'            => 'Location',
        'year'                => 'Year',
        'cover_image'         => 'Cover Image',
        'image'               => 'Photo',
        'category'            => 'Category',
        'type'                => 'Type',
        'date'                => 'Date',
        'preview'             => 'Preview',
        'slug'                => 'URL Slug',
        'url'                 => 'Target URL',
        'direction'           => 'Direction',
        'fallback_lang'       => 'Fallback Language',
        'completeness'        => 'Translation Completeness',
        'story_canvas'        => 'Open Story Canvas →',

        // Homepage Showcase
        'homepage_showcase_title' => 'Homepage Photo Showcase',
        'homepage_showcase_sub'   => 'Manage the curated photo showcase displayed randomly and rotated on click on the homepage.',
        'view_homepage'           => 'View Homepage',
        'import_from_portfolio'   => 'Import from Portfolio',
        'add_new_photo'           => 'Add New Photo',
        'total_showcase'          => 'Total Showcase',
        'active_showcase'         => 'Active in Showcase',
        'showcase_photos_count'   => 'Showcase Photos',

        // Languages & Matrix
        'languages_page_title'    => 'Language Section & Multilingual Settings',
        'languages_page_sub'      => 'Manage active languages, add new languages, configure the default site language and admin panel language.',
        'open_translation_studio' => 'Open Translation Studio →',
        'installed_languages_title' => 'Installed and Active Languages',
        'preset_languages_title'  => 'Quick Add & Ready Language Library',
        'custom_lang_btn'         => 'Add Custom Language →',
        'admin_lang_behavior_title' => 'Admin Panel Language & Site Language Behavior',
        'admin_panel_lang_label'  => 'Admin Panel Default Language',
        'auto_detect_browser_lang'=> 'Auto-detect visitor browser language',
        'show_lang_switcher_header' => 'Show language switcher in header',
        'lang_switcher_format'    => 'Language switcher format',
        'hide_switcher_single'    => 'Hide switcher if only one language is active',

        // Central Translation Studio
        'translation_studio_title' => 'Central Translation Studio (Bulk Matrix)',
        'translation_studio_sub'   => 'Compare all languages side by side on a single screen, auto-translate empty fields with one click, and save in bulk.',
        'back_to_languages'        => '← Back to Language Management',
        'translate_all_empty_in_matrix' => 'Auto-Translate All Empty Fields',
        'translate_column_empty'   => 'Translate Empty',
        'auto_translated_saved'    => '✓ Translated & Saved',
        'tab_portfolio_works'      => 'Portfolio Works',
        'tab_categories'           => 'Categories',
        'tab_menus'                => 'Top Menu Navigation',
        'tab_slides'               => 'Homepage Showcase',
        'tab_settings'             => 'Site & SEO Texts',
        'record_item'              => 'Record / Item',

        // Settings & Customizers
        'settings_page_title'       => 'Site Settings & Security',
        'settings_page_sub'         => 'General SEO metadata, social media links, and admin account security.',
        'settings_nav_languages'    => 'Languages & Multilingual Settings',
        'settings_nav_homepage_settings' => 'Homepage Templates',
        'settings_nav_portfolio_home_settings' => 'Portfolio Homepage',
        'settings_nav_header'       => 'Header (Top Menu)',
        'settings_nav_inner_pages'  => 'Inner Pages Design',
        'settings_nav_blog_settings'=> 'Blog / Articles',
        'settings_nav_about_settings' => 'About Me Templates',
        'settings_nav_footer'       => 'Footer (Bottom Bar)',
        'settings_customizers_title'=> 'Subsections & Customizers',
        'settings_customizers_sub'  => 'Manage language options, header, inner page story/gallery layouts, and footer styles.',
        'settings_card_lang_title'  => 'Language Management & Panel',
        'settings_card_lang_badge'  => 'New',
        'settings_card_lang_desc'   => 'Add or remove site languages, define new languages, and configure the admin panel language.',
        'settings_card_homepage_title' => 'Homepage Templates & Showcase',
        'settings_card_homepage_desc'  => 'Full-Bleed Slide, Monograph Split, Museum Passe-partout, Filmstrip, Vertical Chapters, and Grid.',
        'settings_card_header_desc' => 'Hamburger, horizontal bar, sticky frosted glass, logo and drawer styles.',
        'settings_card_inner_desc'  => 'Monograph right/left layout, text-first, fixed split, magazine and gallery layouts.',
        'settings_card_about_title' => 'About Me Templates & Layout',
        'settings_card_about_desc'  => 'Classic Monograph, Cinematic Hero, Sticky Split, Magazine Profile, and Bento Grid templates.',
        'settings_card_footer_desc' => 'Copyright text, social media icons, column structures, and custom bottom links.',
        'settings_general_seo_title'=> 'General & SEO Settings',
        'settings_site_title_tr'    => 'Site Title (Turkish)',
        'settings_site_title_en'    => 'Site Title (English)',
        'settings_site_desc_tr'     => 'Site Description / Meta (Turkish)',
        'settings_site_desc_en'     => 'Site Description / Meta (English)',
        'settings_author_name'      => 'Photographer / Author Name',
        'settings_maps_api'         => 'Google Maps API Key',
        'settings_maps_api_desc'    => 'Add your Maps JavaScript API key here. If left empty, the world map uses OpenStreetMap.',
        'settings_save_btn'         => 'Save Settings',
        'settings_lang_summary_title' => 'Language Summary (Site & Panel)',
        'settings_manage_langs_btn' => 'Manage Languages →',
        'settings_lang_summary_desc'=> 'Add or remove site languages, define new languages, configure the default site language and admin panel language.',
        'settings_password_title'   => 'Change Admin Password',
        'settings_curr_pass'        => 'Current Password',
        'settings_new_pass'         => 'New Password',
        'settings_new_pass_confirm' => 'Confirm New Password',
        'settings_update_pass_btn'  => 'Update Password',
        'settings_backup_title'     => 'Database Backup & Download',
        'settings_backup_desc'      => 'Download a complete SQLite database backup containing all works, categories, and settings with one click.',
        'settings_download_backup_btn' => 'Download Backup (.db)',
        'settings_legal_title'      => 'Legal Texts & Privacy / Terms',
        'settings_legal_desc'       => 'Manage Cookie Policy, Privacy Notice, and Terms of Use pages displayed on your website.',
        'settings_manage_legal_btn' => 'Edit Legal Pages →',

        // Categories
        'categories_page_title'     => 'Portfolio Categories',
        'categories_page_sub'       => 'Add, edit, reorder, or delete categories. Link categories to the top menu with a single click.',
        'cat_add_btn'               => 'Add New Category',
        'cat_registered'            => 'Registered Categories',
        'cat_drag_hint'             => 'Drag and drop rows to reorder',
        'cat_th_order'              => 'Order',
        'cat_th_name_tr'            => 'Category Name (TR)',
        'cat_th_name_en'            => 'Category Name (EN)',
        'cat_th_type'               => 'Internal code',
        'cat_th_slug'               => 'Permanent URL Path',
        'cat_th_count'              => 'Items',
        'cat_th_menu'               => 'Top Menu Visibility',
        'cat_menu_active'           => '● Shown in Menu',
        'cat_menu_inactive'         => '○ Hidden in Menu',
        'cat_items_unit'            => 'items',
        'cat_modal_add'             => 'Add New Category',
        'cat_modal_edit'            => 'Edit Category',
        'cat_modal_name_tr'         => 'Category Name (Turkish) *',
        'cat_modal_name_en'         => 'Category Name (English)',
        'cat_modal_auto_translate'  => 'Auto-Translate to Other Languages',
        'cat_modal_key_slug'        => 'System Code (Type)',
        'cat_modal_show_menu'       => 'Add as Link to Header Menu',

        // Menus
        'menus_page_title'          => 'Header Menu & Navigation Management',
        'menus_page_sub'            => 'Reorder main header menu items, add new links or vertical separators.',
        'menus_header_design'       => 'Header Design Settings',
        'menus_social_links'        => 'Social Media Links',
        'menus_live_preview'        => 'Live Header Menu Preview',
        'menus_live_preview_hint'   => '(Active items and added icons will appear on the site in this layout)',
        'menus_no_active'           => 'No active menu items found.',
        'menu_add_link_btn'         => '+ Add Menu Item',
        'menu_add_sep_btn'          => '+ Add Separator',
        'menu_preview_title'        => 'Live Header Menu Preview',
        'menu_reorder_title'        => 'Menu Order (Drag & Drop)',
        'menu_reorder_sub'          => 'Drag up/down to change order',
        'menu_th_title'             => 'Menu Item / Title',
        'menu_th_url'               => 'Link (URL)',
        'menu_th_type'              => 'Type',
        'menu_separator_label'      => '| Vertical Separator',
        'menu_separator_desc'       => 'Visual vertical divider between menu items',
        'menu_type_link'            => 'Link',
        'menu_type_separator'       => 'Separator',
        'menu_sep_before'           => '| Leading Separator',
        'menus_modal_add'           => 'Add New Menu Item',
        'menus_modal_edit'          => 'Edit Menu Item',
        'menus_modal_edit_sep'      => 'Edit Vertical Divider',
        'menus_item_type'           => 'Menu Item Type',
        'menus_type_page_link'      => 'Page / Link',
        'menus_title_tr'            => 'Turkish Title *',
        'menus_title_en'            => 'English Title',
        'menus_url_tr'              => 'Turkish URL *',
        'menus_url_en'              => 'English URL',
        'menus_url_hint'            => 'Internal or external link',
        'menus_sep_notice_title'    => '| Vertical Divider Item',
        'menus_sep_notice_desc'     => 'This item is not a link; it appears as a vertical divider line between menu items. After saving, you can drag and drop it into any position.',
        'menus_sep_before_opt'      => 'Place vertical divider line before (|)',
        'menus_active_opt'          => 'Immediately active in menu',
        'menus_btn_add'             => 'Add to Menu',
        'menus_btn_update'          => 'Update Changes',
        'menus_social_modal_title'  => 'Social Media Links',
        'menus_social_modal_desc'   => 'Manage your social media profile URLs and choose whether to display them in the top menu.',
        'menus_show_in_header'      => 'Show in Top Menu',
        'registered_works'          => 'Registered Works',
        'search_works'              => 'Search works...',
        'drag_to_reorder'           => 'Drag rows to reorder',
        'all_works'                 => 'All Works',
        'category'                  => 'Category',

        // Modals & Image Picker
        'slide_modal_add'           => 'Add New Showcase Photo',
        'slide_modal_edit'          => 'Edit Showcase Photo',
        'slide_modal_import'        => 'Import from Portfolio to Homepage',
        'slide_choose_media'        => 'Choose from Media',
        'slide_image_url'           => 'Photo File (URL / Path)',
        'slide_title_tr'            => 'Title (Turkish)',
        'slide_title_en'            => 'Title (English)',
        'slide_desc_tr'             => 'Description (Turkish)',
        'slide_desc_en'             => 'Description (English)',
        'slide_year'                => 'Year',
        'slide_location'            => 'Location',
        'slide_link'                => 'Click Link (Optional)',
        'slide_save_btn'            => 'Save Showcase Photo',
        'media_modal_title'         => 'Select Image from Media Library',
        'media_modal_folder'        => 'Folder:',
        'media_modal_all_folders'   => 'All Folders (All Library)',
        'media_modal_sort'          => 'Sort:',
        'media_modal_search_ph'     => 'Search file name or date...',
        'media_modal_upload_btn'    => 'Upload Image',
        'media_modal_cancel'        => 'Cancel',
        'media_modal_scanning'      => 'Scanning images...',
        'sort_date_desc'            => 'Shot Date (Newest First)',
        'sort_date_asc'             => 'Shot Date (Oldest First)',
        'sort_newest'               => 'Upload Date (Newest First)',
        'sort_oldest'               => 'Upload Date (Oldest First)',
        'sort_name_asc'             => 'File Name (A → Z)',
        'sort_name_desc'            => 'File Name (Z → A)',
        'sort_size'                 => 'File Size (Largest First)',

        // About, Media, Portfolio
        'about_page_title'          => 'About Me & Biography Management',
        'about_page_sub'            => 'Biography texts, editorial portrait, contact information, and exhibition timeline.',
        'about_settings_title'      => 'About Me Layout & Design Settings',
        'about_settings_sub'        => 'Select preset templates or fine-tune layout for biography, portrait, contact cards, and exhibition chronology.',
        'media_page_title'          => 'Media Library',
        'media_page_sub'            => 'Documentary photo archive, geographic world map, and EXIF camera data.',
        'portfolio_page_sub'        => 'Manage and reorder all portfolio categories and works from a single dashboard.',
        'portfolio_add_new_work'    => '+ Add New Work',
        'portfolio_all_categories'  => 'All Categories',
        'header_settings_title'     => 'Header Design & Behavior Settings',
        'header_settings_sub'       => 'Customize your header with hamburger menu, horizontal bar, sticky frosted glass, or transparent bar.',
        'inner_pages_title'         => 'Inner Pages (Detail Layout) Design Settings',
        'inner_pages_sub'           => 'Customize opening layouts (text left, photo right, fixed split, etc.) and gallery flow for Projects and Series.',
        'footer_settings_title'     => 'Footer Design & Content Settings',
        'footer_settings_sub'       => 'Manage footer copyright text, social media icons, columns, and custom bottom links.',

        // Words & Units
        'Gün'                       => 'Days',
        'Yıl'                       => 'Year',
        'Yenile'                    => 'Refresh',
        'Ziyaretçi Analizi'         => 'Visitor Analytics',
        'Hukuki sayfaları ve alt bilgi (footer) bağlantılarını sitede aç / kapat' => 'Toggle legal pages and footer links on the website',
        'Yasal Metinleri Düzenle →' => 'Edit Legal Notices →',
        '1. Aydınlatma Metnini Düzenle' => '1. Edit Privacy Notice',
        '2. Açık Rıza Metnini Düzenle'  => '2. Edit Explicit Consent',
        'Aydınlatma Metnini Yeni Sekmede Görüntüle' => 'View Privacy Notice (TR) in New Tab',
        'Privacy Notice Yeni Sekmede Görüntüle'     => 'View Privacy Notice (EN) in New Tab',
        'Açık Rıza Metnini Yeni Sekmede Görüntüle'  => 'View Explicit Consent (TR) in New Tab',
        'Explicit Consent Yeni Sekmede Görüntüle'   => 'View Explicit Consent (EN) in New Tab',

        // Settings (KVKK)
        'legal_settings_title'      => 'GDPR / KVKK & Legal Notices',
        'legal_switch_title'        => 'Toggle legal pages and footer links on the website',
        'legal_status_active'       => 'Live (Active)',
        'legal_status_hidden'       => 'Hidden (Disabled)',
        'legal_desc_active'         => 'Legal pages and footer links are currently live on the site in compliance with regulations.',
        'legal_desc_hidden'         => 'Legal pages and footer links are currently disabled (hidden from visitors).',
        'legal_edit_btn'            => 'Edit Legal Notices →',
        'legal_settings_body_desc'  => 'You can edit Privacy Notice and Explicit Consent texts conveniently on a dedicated page using the rich text editor (WYSIWYG) without breaking layout.',
        'legal_edit_aydinlatma'     => '1. Edit Privacy Notice',
        'legal_edit_acik_riza'      => '2. Edit Explicit Consent',
        'legal_view_aydinlatma_tr'  => 'View Privacy Notice (TR) in New Tab',
        'legal_view_aydinlatma_en'  => 'View Privacy Notice (EN) in New Tab',
        'legal_view_acik_riza_tr'   => 'View Explicit Consent (TR) in New Tab',
        'legal_view_acik_riza_en'   => 'View Explicit Consent (EN) in New Tab',

        // Languages & Behavior
        'admin_panel_lang_label'    => 'Admin Panel Interface Language',
        'admin_panel_lang_hint'     => 'Sets the language of the admin panel.',
        'admin_show_lang_switcher_label' => 'Show quick language switcher in the topbar',
        'default_site_lang_label'   => 'Default Site Root Language (Root /)',
        'default_site_lang_hint'    => 'The primary language visitors are directed to when opening {site} directly.',
        'auto_detect_browser_lang_label' => 'Auto-detect visitor browser language (Accept-Language) on first visit',
        'lang_switcher_format_label'=> 'Menu Language Switcher Display Format',
        'fmt_code'                  => 'Short ISO Code (EN / TR / HY)',
        'fmt_flag_code'             => 'Flag + Short Code (🇬🇧 EN / 🇹🇷 TR)',
        'fmt_native'                => 'Native Language Name (English / Türkçe / Հայերեն)',
        'hide_switcher_single_label'=> 'Hide language switcher when only one language is active',
        'seo_hreflang_label'        => 'Automatically generate hreflang alternate tags for SEO',
        'disabled_lang_behavior_label' => 'What happens when accessing removed / inactive language URLs?',
        'dis_behavior_redirect'     => 'Smart Redirect to Default Language (302 Redirect - Recommended)',
        'dis_behavior_404'          => 'Show 404 Page Not Found',
        'disabled_lang_hint'        => 'Ensures that visitors clicking old language links do not encounter broken pages by redirecting to the default language.',
        'save_behavior_settings_btn'=> 'Save Language & Behavior Settings',
        'status_inactive_note'      => 'Currently inactive - will become active when selected',

        // Menus
        'menus_preview_en_title'    => 'English Menu Preview',
        'menus_preview_tr_title'    => 'Turkish Menu Preview',

        // Convert Images
        'convert_page_title'        => 'Bulk AVIF and WebP Image Converter',
        'convert_card_title'        => 'Image Optimization & Conversion Status',
        'convert_card_desc'         => 'All photos on your site are automatically converted to modern web formats (both AVIF and WebP). The visitor\'s browser loads the most optimal format instantly with superior quality and minimal file size.',
        'convert_stat_orig'         => 'Original Photos',
        'convert_stat_orig_sub'     => 'in content/ directory',
        'convert_stat_avif'         => 'Generated AVIF Variants',
        'convert_stat_webp'         => 'Generated WebP Variants',
        'convert_progress_converting' => 'Converting...',
        'convert_btn_generate'      => 'Generate Missing AVIF and WebP Images',
        'convert_js_processing'     => 'Processing, please wait...',
        'convert_js_photos_processed' => 'photos processed...',
        'convert_js_all_done'       => 'All photos are successfully prepared as AVIF and WebP!',
        'convert_js_rescan'         => 'Rescan and Update',
        'convert_js_err'            => 'An error occurred during conversion.',
        'convert_js_net_err'        => 'Server communication error.',

        // Stats
        'stats_page_desc'           => 'Portfolio engagement, geographic distribution, and conversion metrics via Google Analytics 4 API.',
        'stats_btn_config'          => 'API Configuration',
        'stats_status_live'         => '● LIVE GA4 DATA',
        'stats_status_live_desc_prefix' => 'Google Analytics 4 API connection is active',
        'stats_status_demo'         => 'DEMO PREVIEW',
        'stats_status_demo_desc'    => 'Awaiting Google Analytics API key — Showing realistic portfolio preview data.',
        'stats_last_sync'           => 'Last Synced',
        'stats_btn_connect_google'  => 'Connect Google →',
        'stats_btn_refresh'         => 'Refresh',
        'stats_refresh_title'       => 'Fetch fresh data from Google Analytics',
        'stats_unit_day'            => 'Days',
        'stats_unit_year'           => 'Year',
        'stats_kpi_active_users'    => 'Active Users',
        'stats_new_visitors'        => 'new visitors',
        'stats_kpi_avg_duration'    => 'Avg. Engagement Time',
    ],

];

/**
 * Harici hazır dil sözlük dosyasını yükler (admin/includes/i18n_langs/{lang}.php)
 */
function admin_load_lang_dict(string $code): array {
    global $admin_translations;
    if (isset($admin_translations[$code]) && is_array($admin_translations[$code]) && count($admin_translations[$code]) >= 300) {
        return $admin_translations[$code];
    }
    $file = __DIR__ . '/i18n_langs/' . $code . '.php';
    if (file_exists($file)) {
        $dict = require $file;
        if (is_array($dict)) {
            $admin_translations[$code] = $dict;
            return $dict;
        }
    }
    return $admin_translations['en'] ?? $admin_translations['tr'] ?? [];
}

// Panel dilini belirle:
// 1. URL'de ?admin_lang=XX geldiyse güncelle ve kaydet
// 2. settings tablosundaki admin_panel_lang ayarı
// 3. Session'daki admin_lang
// 4. Varsayılan 'tr'
$all_lang_rows = get_all_languages();
$admin_enabled_langs = [];
foreach ($all_lang_rows as $lr) {
    if (!empty($lr['is_admin_enabled']) && !empty($lr['is_active'])) {
        $admin_enabled_langs[$lr['code']] = $lr;
    }
    if (!empty($lr['ui_strings']) && is_array($lr['ui_strings'])) {
        $c_code = $lr['code'];
        if (!isset($admin_translations[$c_code])) {
            admin_load_lang_dict($c_code);
            if (!isset($admin_translations[$c_code])) {
                $admin_translations[$c_code] = $admin_translations['en'] ?? [];
            }
        }
        $admin_translations[$c_code] = array_merge($admin_translations[$c_code], $lr['ui_strings']);
    }
}
if (empty($admin_enabled_langs)) {
    $admin_enabled_langs['tr'] = ['code' => 'tr', 'name_native' => 'Türkçe', 'flag' => '🇹🇷', 'is_active' => 1];
    $admin_enabled_langs['en'] = ['code' => 'en', 'name_native' => 'English', 'flag' => '🇬🇧', 'is_active' => 1];
}

$saved_admin_lang = get_setting('admin_panel_lang', 'tr');
if (isset($_GET['admin_lang'])) {
    $req_admin_lang = strtolower(trim((string)$_GET['admin_lang']));
    if (isset($admin_enabled_langs[$req_admin_lang]) || isset($admin_translations[$req_admin_lang]) || file_exists(__DIR__ . '/i18n_langs/' . $req_admin_lang . '.php')) {
        $_SESSION['admin_lang'] = $req_admin_lang;
        try {
            set_setting('admin_panel_lang', $req_admin_lang);
        } catch (\Throwable $e) {}
        $saved_admin_lang = $req_admin_lang;
    }
}

$admin_lang = $_SESSION['admin_lang'] ?? $saved_admin_lang ?? 'tr';
if (!isset($admin_enabled_langs[$admin_lang]) && !isset($admin_translations[$admin_lang]) && !file_exists(__DIR__ . '/i18n_langs/' . $admin_lang . '.php')) {
    $admin_lang = array_key_first($admin_enabled_langs) ?: 'tr';
}
$_SESSION['admin_lang'] = $admin_lang;

// Aktif dil TR veya EN değilse, hazır sözlüğünü otomatik yükle ve standart başlıkları senkronize et
if ($admin_lang !== 'tr' && $admin_lang !== 'en') {
    admin_load_lang_dict($admin_lang);
    if (function_exists('sync_standard_categories_and_menus')) {
        sync_standard_categories_and_menus($admin_lang);
    }
}

function get_admin_lang(): string {
    global $admin_lang;
    return $admin_lang ?: 'tr';
}

function get_admin_enabled_languages(): array {
    global $admin_enabled_langs;
    if (empty($admin_enabled_langs)) {
        return [
            'tr' => ['code' => 'tr', 'name_native' => 'Türkçe', 'flag' => '🇹🇷', 'is_active' => 1],
            'en' => ['code' => 'en', 'name_native' => 'English', 'flag' => '🇬🇧', 'is_active' => 1],
        ];
    }
    return $admin_enabled_langs;
}

/**
 * Yönetici Paneli Akıllı Çeviri Fonksiyonu (__a)
 * Türkçe ve İngilizce (ve tüm hazır diller) ayrımını kusursuz yapar:
 * - Türkçe panelde daima Türkçe metin gösterir, asla İngilizce fallback basmaz.
 * - İngilizce panelde daima İngilizce metin gösterir.
 * - Diğer tüm hazır dillerde (pt, ko, hi, ur, ms, fr, de, es, it, ru, zh, ja, ar, el, hy)
 *   o dilin tam sözlüğünden kusursuz çeviriyi basar.
 */
function __a(string $key, ?string $fallback = null): string {
    global $admin_translations, $admin_lang, $admin_ui_dictionary_tr_to_en;
    $curr = $admin_lang ?: 'tr';

    // ── 1. TÜRKÇE PANEL SEÇİLİ İSE ──
    if ($curr === 'tr') {
        if (isset($admin_translations['tr'][$key]) && $admin_translations['tr'][$key] !== '') {
            return $admin_translations['tr'][$key];
        }
        $cat_tr = function_exists('ui_catalog') ? ui_catalog('tr') : [];
        if (!empty($cat_tr)) {
            if (isset($cat_tr[$key]) && $cat_tr[$key] !== '') {
                return $cat_tr[$key];
            }
            if ($fallback !== null && isset($cat_tr[$fallback]) && $cat_tr[$fallback] !== '') {
                return $cat_tr[$fallback];
            }
        }
        if (!preg_match('/^[a-z0-9_]+$/', $key)) {
            return $key;
        }
        if ($fallback !== null && $fallback !== '') {
            if (isset($admin_ui_dictionary_tr_to_en[$fallback]) || preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $fallback)) {
                return $fallback;
            }
        }
        return $key;
    }

    // ── 2. İNGİLİZCE PANEL SEÇİLİ İSE ──
    if ($curr === 'en') {
        if (isset($admin_translations['en'][$key]) && $admin_translations['en'][$key] !== '') {
            return $admin_translations['en'][$key];
        }
        $cat_en = function_exists('ui_catalog') ? ui_catalog('en') : [];
        if (!empty($cat_en)) {
            if (isset($cat_en[$key]) && $cat_en[$key] !== '') {
                return $cat_en[$key];
            }
            if ($fallback !== null && isset($cat_en[$fallback]) && $cat_en[$fallback] !== '') {
                return $cat_en[$fallback];
            }
        }
        if ($fallback !== null && $fallback !== '') {
            if (!preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $fallback) && !isset($admin_ui_dictionary_tr_to_en[$fallback])) {
                return $fallback;
            }
        }
        if (!empty($admin_ui_dictionary_tr_to_en)) {
            if (isset($admin_ui_dictionary_tr_to_en[$key])) {
                return $admin_ui_dictionary_tr_to_en[$key];
            }
            if ($fallback !== null && isset($admin_ui_dictionary_tr_to_en[$fallback])) {
                return $admin_ui_dictionary_tr_to_en[$fallback];
            }
        }
        if (isset($admin_translations['tr'])) {
            $found_k = array_search($key, $admin_translations['tr'], true);
            if ($found_k !== false && isset($admin_translations['en'][$found_k]) && $admin_translations['en'][$found_k] !== '') {
                return $admin_translations['en'][$found_k];
            }
        }
        if ($fallback !== null && $fallback !== '') {
            return $fallback;
        }
        return $key;
    }

    // ── 3. DİĞER DİLLER (pt, ko, hi, ur, ms, fr, de, es, it, ru, zh, ja, ar, el, hy vb.) ──
    if (!isset($admin_translations[$curr])) {
        admin_load_lang_dict($curr);
    }

    // a) Tanımlı anahtar kodu hedef dilde varsa
    if (isset($admin_translations[$curr][$key]) && $admin_translations[$curr][$key] !== '') {
        return $admin_translations[$curr][$key];
    }

    // b) Eğer $key doğrudan Türkçe metin ise, TR sözlüğündeki anahtarını bulup hedef dildeki karşılığını ver
    if (isset($admin_translations['tr'])) {
        $found_k = array_search($key, $admin_translations['tr'], true);
        if ($found_k !== false && isset($admin_translations[$curr][$found_k]) && $admin_translations[$curr][$found_k] !== '') {
            return $admin_translations[$curr][$found_k];
        }
    }

    // c) Eğer $fallback İngilizce ise ve EN sözlüğünde varsa, hedef dildeki karşılığını ver
    if ($fallback !== null && $fallback !== '' && isset($admin_translations['en'])) {
        $found_k = array_search($fallback, $admin_translations['en'], true);
        if ($found_k !== false && isset($admin_translations[$curr][$found_k]) && $admin_translations[$curr][$found_k] !== '') {
            return $admin_translations[$curr][$found_k];
        }
    }

    // d) UI sözlüğündeki uzun metin eşleşmesi
    if (!empty($admin_ui_dictionary_tr_to_en) && isset($admin_ui_dictionary_tr_to_en[$key])) {
        $en_val = $admin_ui_dictionary_tr_to_en[$key];
        $found_k = array_search($en_val, $admin_translations['en'] ?? [], true);
        if ($found_k !== false && !empty($admin_translations[$curr][$found_k])) {
            return $admin_translations[$curr][$found_k];
        }
    }

    // d2) Ortak hazır dil kataloğu (includes/i18n_ui/{lang}.php): İngilizce kaynak metin → hedef dil çevirisi
    $cat = ui_catalog($curr);
    if (!empty($cat)) {
        $cands = [];
        if (isset($admin_translations['en'][$key])) {
            $cands[] = $admin_translations['en'][$key];
        }
        if ($fallback !== null && $fallback !== '') {
            $cands[] = $fallback;
        }
        if (!empty($admin_ui_dictionary_tr_to_en)) {
            if (isset($admin_ui_dictionary_tr_to_en[$key])) {
                $cands[] = $admin_ui_dictionary_tr_to_en[$key];
            }
            if ($fallback !== null && isset($admin_ui_dictionary_tr_to_en[$fallback])) {
                $cands[] = $admin_ui_dictionary_tr_to_en[$fallback];
            }
        }
        if (isset($admin_translations['tr'])) {
            $found_tr_k = array_search($key, $admin_translations['tr'], true);
            if ($found_tr_k !== false && isset($admin_translations['en'][$found_tr_k])) {
                $cands[] = $admin_translations['en'][$found_tr_k];
            }
        }
        $cands[] = $key;
        foreach ($cands as $cand) {
            if (isset($cat[$cand]) && $cat[$cand] !== '') {
                return $cat[$cand];
            }
        }
    }

    // e) Hedef dilde yoksa İngilizce çeviri fallback
    if (isset($admin_translations['en'][$key]) && $admin_translations['en'][$key] !== '') {
        return $admin_translations['en'][$key];
    }
    if ($fallback !== null && $fallback !== '' && !preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $fallback)) {
        return $fallback;
    }
    if (isset($admin_translations['tr'][$key])) {
        return $admin_translations['tr'][$key];
    }

    return $fallback ?? $key;
}

/**
 * Yönetim Paneli Sayfa Çıktısı Otomatik Çevirici
 * Sadece admin_lang !== 'tr' olduğunda devreye girer.
 * Script, style, textarea, pre ve code gibi kullanıcı/kod alanlarını koruyarak
 * statik HTML UI metinlerini ve etiketlerini hedef dile çevirir.
 * İki aşamalı (Two-Pass) mimari kullanır:
 * 1. Çok kelimeli kalıplar ve uzun ifadeler (strtr ile)
 * 2. Tekil kelimeler (Unicode kelime sınırı (?<!\p{L})...(?!\p{L}) ile ek/kök bozulmalarını önler)
 */
function admin_translate_ui_buffer(string $buffer): string {
    global $admin_lang, $admin_translations, $admin_ui_dictionary_tr_to_en;
    $curr = $admin_lang ?: 'tr';
    if ($curr === 'tr') {
        return $buffer;
    }

    static $cache = [];
    if (!isset($cache[$curr])) {
        if ($curr !== 'en' && !isset($admin_translations[$curr])) {
            admin_load_lang_dict($curr);
        }

        $raw = [];

        // 1. Tüm 17 Hazır Dilin Orijinal ve Hedef Adları
        $all_codes = ['tr', 'en', 'pt', 'ko', 'hi', 'ur', 'ms', 'fr', 'de', 'es', 'it', 'ru', 'zh', 'ja', 'ar', 'el', 'hy'];
        foreach ($all_codes as $code) {
            $tr_name = function_exists('get_localized_language_name') ? get_localized_language_name($code, 'tr') : '';
            $tgt_name = function_exists('get_localized_language_name') ? get_localized_language_name($code, $curr) : '';
            if ($tr_name && $tgt_name && $tr_name !== $tgt_name) {
                $raw[$tr_name] = $tgt_name;
                $raw['(' . $tr_name . ')'] = '(' . $tgt_name . ')';
                $raw['(' . $tr_name . ' - Varsayılan)'] = '(' . $tgt_name . ' - ' . ($curr === 'en' ? 'Default' : $tgt_name) . ')';
            }
        }

        // 2. Çok Sık Kullanılan Türkçe Çekimler ve Birleşik Terimler
        $inflections = [
            'en' => [
                'Türkçe' => 'Turkish',
                'Türkiye' => 'Turkey',
                'Yönetim Paneli' => 'Control Panel',
                'Yönetimi' => 'Management',
                'Yönetim' => 'Management',
                'Yöneticiler' => 'Administrators',
                'Günlük' => 'Daily',
                'Güncelleme' => 'Update',
                'Güncellemeler' => 'Updates',
                'Güncelle' => 'Update',
                'Sıralama' => 'Order',
                'Sıradaki' => 'Next',
                'Sıradaki Çalışma' => 'Next Work',
                'Önceki Çalışma' => 'Previous Work',
                'Önceki' => 'Previous',
                'Fotoğraflar' => 'Photos',
                'Fotoğrafları' => 'Photos',
                'Fotoğrafçı' => 'Photographer',
                'Fotoğrafçılık' => 'Photography',
                'Kategoriler' => 'Categories',
                'Kategorisi' => 'Category',
                'Kategoride' => 'in Category',
                'Durumu' => 'Status',
                'Durumlar' => 'Statuses',
                'Diller' => 'Languages',
                'Dilleri' => 'Languages',
                'Konumu' => 'Location',
                'Konumlandırma' => 'Positioning',
                'Tarihi' => 'Date',
                'Tarihler' => 'Dates',
                'Ayraçlar' => 'Separators',
                'Giriş Yap' => 'Log In',
                'Çıkış Yap' => 'Log Out',
                'İngilizce Başlık' => 'English Title',
                'Türkçe Başlık' => 'Turkish Title',
                'İngilizce Açıklama' => 'English Description',
                'Türkçe Açıklama' => 'Turkish Description',
                'Türkçe Metin' => 'Turkish Text',
                'İngilizce Metin' => 'English Text',
            ],
            'pt' => [
                'Türkçe' => 'Turco',
                'Fotoğraflar' => 'Fotografias',
                'Fotoğrafçı' => 'Fotógrafo',
                'Fotoğrafçılık' => 'Fotografia',
                'Kategoriler' => 'Categorias',
                'Diller' => 'Idiomas',
                'Yönetim Paneli' => 'Painel de Controle',
                'Yönetim' => 'Gestão',
                'Güncelleme' => 'Atualização',
                'Sıradaki' => 'Próximo',
                'Önceki' => 'Anterior',
                'Durumu' => 'Estado',
            ],
            'ko' => [
                'Türkçe' => '튀르키예어',
                'Fotoğraflar' => '사진들',
                'Fotoğrafçı' => '사진작가',
                'Fotoğrafçılık' => '사진 예술',
                'Kategoriler' => '카테고리',
                'Diller' => '언어',
                'Yönetim Paneli' => '관리자 패널',
                'Yönetim' => '관리',
                'Güncelleme' => '업데이트',
                'Sıradaki' => '다음',
                'Önceki' => '이전',
                'Durumu' => '상태',
            ],
            'fr' => [
                'Türkçe' => 'Turc',
                'Fotoğraflar' => 'Photos',
                'Fotoğrafçı' => 'Photographe',
                'Kategoriler' => 'Catégories',
                'Diller' => 'Langues',
                'Yönetim Paneli' => 'Tableau de bord',
                'Güncelleme' => 'Mise à jour',
                'Sıradaki' => 'Suivant',
                'Önceki' => 'Précédent',
            ],
            'de' => [
                'Türkçe' => 'Türkisch',
                'Fotoğraflar' => 'Fotos',
                'Fotoğrafçı' => 'Fotograf',
                'Kategoriler' => 'Kategorien',
                'Diller' => 'Sprachen',
                'Yönetim Paneli' => 'Dashboard',
                'Güncelleme' => 'Aktualisierung',
                'Sıradaki' => 'Weiter',
                'Önceki' => 'Zurück',
            ],
            'es' => [
                'Türkçe' => 'Turco',
                'Fotoğraflar' => 'Fotos',
                'Fotoğrafçı' => 'Fotógrafo',
                'Kategoriler' => 'Categorías',
                'Diller' => 'Idiomas',
                'Yönetim Paneli' => 'Panel de Control',
                'Güncelleme' => 'Actualización',
                'Sıradaki' => 'Siguiente',
                'Önceki' => 'Anterior',
            ],
            'it' => [
                'Türkçe' => 'Turco',
                'Fotoğraflar' => 'Foto',
                'Fotoğrafçı' => 'Fotografo',
                'Kategoriler' => 'Categorie',
                'Diller' => 'Lingue',
                'Yönetim Paneli' => 'Pannello di controllo',
                'Güncelleme' => 'Aggiornamento',
                'Sıradaki' => 'Successivo',
                'Önceki' => 'Precedente',
            ],
            'ru' => [
                'Türkçe' => 'Турецкий',
                'Fotoğraflar' => 'Фотографии',
                'Fotoğrafçı' => 'Фотограф',
                'Kategoriler' => 'Категории',
                'Diller' => 'Языки',
                'Yönetim Paneli' => 'Панель управления',
                'Güncelleme' => 'Обновление',
                'Sıradaki' => 'Следующий',
                'Önceki' => 'Предыдущий',
            ],
            'zh' => [
                'Türkçe' => '土耳其语',
                'Fotoğraflar' => '照片',
                'Fotoğrafçı' => '摄影师',
                'Kategoriler' => '分类',
                'Diller' => '语言',
                'Yönetim Paneli' => '管理面板',
                'Güncelleme' => '更新',
                'Sıradaki' => '下一个',
                'Önceki' => '上一个',
            ],
            'ja' => [
                'Türkçe' => 'トルコ語',
                'Fotoğraflar' => '写真',
                'Fotoğrafçı' => '写真家',
                'Kategoriler' => 'カテゴリー',
                'Diller' => '言語',
                'Yönetim Paneli' => 'ダッシュボード',
                'Güncelleme' => '更新',
                'Sıradaki' => '次へ',
                'Önceki' => '前へ',
            ],
            'ar' => [
                'Türkçe' => 'التركية',
                'Fotoğraflar' => 'الصور',
                'Fotoğrafçı' => 'المصور',
                'Kategoriler' => 'التصنيفات',
                'Diller' => 'اللغات',
                'Yönetim Paneli' => 'لوحة التحكم',
                'Güncelleme' => 'تحديث',
                'Sıradaki' => 'التالي',
                'Önceki' => 'السابق',
            ],
            'hi' => [
                'Türkçe' => 'तुर्की',
                'Fotoğraflar' => 'तस्वीरें',
                'Fotoğrafçı' => 'फोटोग्राफर',
                'Kategoriler' => 'श्रेणियाँ',
                'Diller' => 'भाषाएँ',
                'Yönetim Paneli' => 'नियंत्रण कक्ष',
                'Güncelleme' => 'अपडेट',
                'Sıradaki' => 'अगला',
                'Önceki' => 'पिछला',
            ],
            'ur' => [
                'Türkçe' => 'ترکی',
                'Fotoğraflar' => 'تصاویر',
                'Fotoğrafçı' => 'فوٹوگرافر',
                'Kategoriler' => 'اقسام',
                'Diller' => 'زبانیں',
                'Yönetim Paneli' => 'کنٹرول پینل',
                'Güncelleme' => 'اپ ڈیٹ',
                'Sıradaki' => 'اگلا',
                'Önceki' => 'پچھلا',
            ],
            'ms' => [
                'Türkçe' => 'Turki',
                'Fotoğraflar' => 'Foto',
                'Fotoğrafçı' => 'Jurugambar',
                'Kategoriler' => 'Kategori',
                'Diller' => 'Bahasa',
                'Yönetim Paneli' => 'Panel Kawalan',
                'Güncelleme' => 'Kemas Kini',
                'Sıradaki' => 'Seterusnya',
                'Önceki' => 'Sebelumnya',
            ],
            'el' => [
                'Türkçe' => 'Τουρκικά',
                'Fotoğraflar' => 'Φωτογραφίες',
                'Fotoğrafçı' => 'Φωτογράφος',
                'Kategoriler' => 'Κατηγορίες',
                'Diller' => 'Γλώσσες',
                'Yönetim Paneli' => 'Πίνακας Ελέγχου',
                'Güncelleme' => 'Ενημέρωση',
                'Sıradaki' => 'Επόμενο',
                'Önceki' => 'Προηγούμενο',
            ],
            'hy' => [
                'Türkçe' => 'Թուրքերեն',
                'Fotoğraflar' => 'Լուսանկարներ',
                'Fotoğrafçı' => 'Լուսանկարիչ',
                'Kategoriler' => 'Կատեգորիաներ',
                'Diller' => 'Լեզուներ',
                'Yönetim Paneli' => 'Կառավարման վահանակ',
                'Güncelleme' => 'Թարմացում',
                'Sıradaki' => 'Հաջորդը',
                'Önceki' => 'Նախորդը',
            ]
        ];

        if (isset($inflections[$curr])) {
            foreach ($inflections[$curr] as $k => $v) {
                $raw[$k] = $v;
            }
        }

        // 3. UI Sözlüğü ve Ana Çeviri Dizileri
        if ($curr === 'en') {
            if (!empty($admin_ui_dictionary_tr_to_en)) {
                foreach ($admin_ui_dictionary_tr_to_en as $k => $v) {
                    if (!isset($raw[$k])) {
                        $raw[$k] = $v;
                    }
                }
            }
            if (isset($admin_translations['tr']) && isset($admin_translations['en'])) {
                foreach ($admin_translations['tr'] as $k => $tr_text) {
                    $en_text = $admin_translations['en'][$k] ?? '';
                    if ($en_text !== '' && $tr_text !== '' && $en_text !== $tr_text && !isset($raw[$tr_text])) {
                        $raw[$tr_text] = $en_text;
                    }
                }
            }
        } else {
            if (isset($admin_translations['tr']) && isset($admin_translations[$curr])) {
                foreach ($admin_translations['tr'] as $k => $tr_text) {
                    $tgt_text = $admin_translations[$curr][$k] ?? '';
                    if ($tgt_text !== '' && $tr_text !== '' && $tgt_text !== $tr_text && !isset($raw[$tr_text])) {
                        if (mb_stripos($tgt_text, $tr_text) !== false) {
                            continue;
                        }
                        $raw[$tr_text] = $tgt_text;
                    }
                }
            }
            if (isset($admin_translations['en']) && isset($admin_translations[$curr])) {
                foreach ($admin_translations['en'] as $k => $en_text) {
                    $tgt_text = $admin_translations[$curr][$k] ?? '';
                    if ($tgt_text !== '' && $en_text !== '' && $tgt_text !== $en_text && !isset($raw[$en_text])) {
                        if (mb_stripos($tgt_text, $en_text) !== false) {
                            continue;
                        }
                        $raw[$en_text] = $tgt_text;
                    }
                }
            }
            if (!empty($admin_ui_dictionary_tr_to_en) && isset($admin_translations['en'])) {
                foreach ($admin_ui_dictionary_tr_to_en as $tr_str => $en_str) {
                    if (!isset($raw[$tr_str])) {
                        $found_k = array_search($en_str, $admin_translations['en'], true);
                        if ($found_k !== false && !empty($admin_translations[$curr][$found_k])) {
                            $mapped_val = $admin_translations[$curr][$found_k];
                            if (mb_stripos($mapped_val, $tr_str) === false) {
                                $raw[$tr_str] = $mapped_val;
                            }
                        }
                    }
                    if (!isset($raw[$en_str])) {
                        $found_k = array_search($en_str, $admin_translations['en'], true);
                        if ($found_k !== false && !empty($admin_translations[$curr][$found_k])) {
                            $mapped_val = $admin_translations[$curr][$found_k];
                            if (mb_stripos($mapped_val, $en_str) === false) {
                                $raw[$en_str] = $mapped_val;
                            }
                        }
                    }
                }
            }
            if (function_exists('ui_catalog')) {
                $cat = ui_catalog($curr);
                foreach ($cat as $en_key => $tgt_val) {
                    if ($en_key !== '' && $tgt_val !== '' && !isset($raw[$en_key])) {
                        $raw[$en_key] = $tgt_val;
                    }
                }
            }
            if (function_exists('get_standard_category_matrix')) {
                $cat_matrix = get_standard_category_matrix();
                foreach ($cat_matrix as $slug => $by_lang) {
                    $tgt_cname = $by_lang[$curr]['name'] ?? '';
                    if ($tgt_cname !== '') {
                        foreach ($by_lang as $l => $cdata) {
                            if (!empty($cdata['name']) && $cdata['name'] !== $tgt_cname && !isset($raw[$cdata['name']])) {
                                $raw[$cdata['name']] = $tgt_cname;
                            }
                        }
                    }
                }
            }
            if (function_exists('get_standard_menu_matrix')) {
                $menu_matrix = get_standard_menu_matrix();
                foreach ($menu_matrix as $slug => $by_lang) {
                    $tgt_mtitle = $by_lang[$curr]['title'] ?? '';
                    if ($tgt_mtitle !== '') {
                        foreach ($by_lang as $l => $mdata) {
                            if (!empty($mdata['title']) && $mdata['title'] !== $tgt_mtitle && !isset($raw[$mdata['title']])) {
                                $raw[$mdata['title']] = $tgt_mtitle;
                            }
                        }
                    }
                }
            }
        }

        // 4. İki Aşamalı Bölümleme (Phrases vs Single Words)
        $phrase_map = [];
        $word_map = [];
        foreach ($raw as $key => $val) {
            $k = trim($key);
            if ($k === '' || $val === '') continue;
            // Boşluk veya başlangıç noktalama/sembol içerenler doğrudan kalıp olarak strtr'ye
            if (preg_match('/\s/u', $k) || preg_match('/^[\p{P}\+—]/u', $k)) {
                $phrase_map[$k] = $val;
            } else {
                // Tekil kelimeler kelime sınırıyla eşleştirilmek üzere word_map'e
                $word_map[$k] = $val;
            }
        }

        uksort($phrase_map, function($a, $b) {
            return mb_strlen($b) <=> mb_strlen($a);
        });
        uksort($word_map, function($a, $b) {
            return mb_strlen($b) <=> mb_strlen($a);
        });

        $escaped = array_map(function($k) { return preg_quote($k, '/'); }, array_keys($word_map));
        $word_regex = !empty($escaped) ? '/(?<!\p{L})(' . implode('|', $escaped) . ')(?!\p{L})/u' : null;

        $cache[$curr] = [$phrase_map, $word_map, $word_regex];
    }

    list($phrase_map, $word_map, $word_regex) = $cache[$curr];
    if (empty($phrase_map) && empty($word_map)) {
        return $buffer;
    }

    $translate_fn = function(string $text) use ($phrase_map, $word_map, $word_regex): string {
        if ($text === '') return '';
        if (!empty($phrase_map)) {
            $text = strtr($text, $phrase_map);
        }
        if ($word_regex !== null) {
            $text = preg_replace_callback($word_regex, function($m) use ($word_map) {
                return $word_map[$m[1]] ?? $m[1];
            }, $text);
        }
        return $text;
    };

    // Korunacak bloklar: script, style, textarea, pre, code
    $parts = preg_split('/(<!--noi18n-->.*?<!--\/noi18n-->|<(?:script|style|textarea|pre|code)[^>]*>.*?<\/(?:script|style|textarea|pre|code)>)/is', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
    $out = '';
    foreach ($parts as $part) {
        if (preg_match('/^(?:<!--noi18n-->|<(?:script|style|textarea|pre|code))/i', $part)) {
            $out .= $part;
        } else {
            // 1. Etiketler arası metin düğümleri (>...<)
            $part = preg_replace_callback('/>([^<]+)</s', function($m) use ($translate_fn) {
                return '>' . $translate_fn($m[1]) . '<';
            }, $part);

            // 2. Başlık ve yer tutucu öznitelikleri (placeholder, title, aria-label)
            $part = preg_replace_callback('/(placeholder|title|aria-label)="([^"]+)"/i', function($m) use ($translate_fn) {
                return $m[1] . '="' . $translate_fn($m[2]) . '"';
            }, $part);

            // 3. Buton değerleri (<input type="submit|button" value="...">)
            $part = preg_replace_callback('/(<input\s+[^>]*type="(?:submit|button)"[^>]*value=")([^"]+)(")/i', function($m) use ($translate_fn) {
                return $m[1] . $translate_fn($m[2]) . $m[3];
            }, $part);

            $out .= $part;
        }
    }
    return $out;
}

/**
 * Dil Değiştirme Bağlantısı Üretir (Mevcut query parametrelerini korur)
 */
function get_admin_lang_switch_url(string $target_lang): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/admin/';
    $parsed = parse_url($uri);
    $path = $parsed['path'] ?? '/admin/';
    $query = [];
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $query);
    }
    $query['admin_lang'] = $target_lang;
    return $path . '?' . http_build_query($query);
}
