<?php
/**
 * admin/settings.php
 * Site Genel Ayarları, Tüm Şablon & Modül Kutucukları, SEO, Sosyal Medya, İletişim, Blog ve Güvenlik Merkezi
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

// SQLite Veritabanı Yedeği İndirme İsteği
if (isset($_GET['download']) && $_GET['download'] === 'sqlite') {
    $db_file = dirname(__DIR__) . '/site.db';
    if (file_exists($db_file)) {
        header('Content-Type: application/x-sqlite3');
        header('Content-Disposition: attachment; filename="' . (preg_replace('/[^a-z0-9]+/', '_', strtolower(preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'site'))) ?: 'site') . '_' . date('Y-m-d_His') . '.db"');
        header('Content-Length: ' . filesize($db_file));
        readfile($db_file);
        exit;
    }
}

$pdo = DB::getConnection();
$site_languages = get_active_languages();

function handle_settings_brand_upload(string $fileInput, string $prefix): ?string {
    if (empty($_FILES[$fileInput]) || ($_FILES[$fileInput]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    // Tek doğrulama yolu: tasarım düzenleyicisinin sıkı SVG / görsel denetimi
    require_once __DIR__ . '/../includes/design_editor.php';
    $res = design_upload_brand_file($_FILES[$fileInput], $prefix);
    if (is_array($res)) {
        set_flash('error', $res['error']);
        return null;
    }
    return $res;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'CSRF güvenlik hatası.']);
            exit;
        }
        set_flash('error', 'CSRF güvenlik hatası.');
        header('Location: settings.php');
        exit;
    }

    $action = $_POST['action'] ?? 'general';

    if ($action === 'save_branding') {
        $quickBrandAction = $_POST['brand_quick_action'] ?? '';
        if ($quickBrandAction !== '') {
            if ($quickBrandAction === 'remove_symbol') {
                set_setting('brand_symbol_enabled', '0');
                $curType = get_setting('header_logo_type', 'symbol');
                if ($curType === 'symbol' || $curType === 'symbol_text') {
                    set_setting('header_logo_type', 'text_only');
                }
                set_flash('success', __a('Amblem (Sembol) kaldırıldı ve devre dışı bırakıldı.', 'Emblem (Symbol) removed and disabled.'));
            } elseif ($quickBrandAction === 'reset_symbol') {
                set_setting('brand_symbol_enabled', '1');
                set_setting('brand_symbol_dark', 'assets/brand/symbol-dark.svg');
                set_setting('brand_symbol_light', 'assets/brand/symbol-light.svg');
                set_flash('success', __a('Amblem (Sembol) varsayılan sembol dosyalarına döndürüldü.', 'Emblem (Symbol) restored to default files.'));
            } elseif ($quickBrandAction === 'remove_logo') {
                set_setting('brand_logo_enabled', '0');
                $curType = get_setting('header_logo_type', 'symbol');
                if ($curType === 'full' || $curType === 'full_text') {
                    set_setting('header_logo_type', 'text_only');
                }
                set_flash('success', __a('Tam Logo kaldırıldı ve devre dışı bırakıldı.', 'Full Logo removed and disabled.'));
            } elseif ($quickBrandAction === 'reset_logo') {
                set_setting('brand_logo_enabled', '1');
                set_setting('brand_logo_dark', 'assets/brand/symbol-dark.svg');
                set_setting('brand_logo_light', 'assets/brand/symbol-light.svg');
                set_flash('success', __a('Tam Logo varsayılan SVG dosyalarına döndürüldü.', 'Full Logo restored to default SVG files.'));
            } elseif ($quickBrandAction === 'remove_favicon') {
                set_setting('brand_favicon_enabled', '0');
                set_flash('success', __a('Site ikonu (Favicon) kaldırıldı.', 'Site icon (Favicon) removed.'));
            } elseif ($quickBrandAction === 'reset_favicon') {
                set_setting('brand_favicon_enabled', '1');
                set_setting('brand_favicon', 'assets/brand/favicon.png');
                set_flash('success', __a('Site ikonu (Favicon) varsayılan ikona döndürüldü.', 'Site icon (Favicon) restored to default.'));
            } elseif ($quickBrandAction === 'remove_og_image') {
                set_setting('default_og_image', '');
                set_flash('success', __a('Özel sosyal medya paylaşım görseli kaldırıldı.', 'Custom social share image removed.'));
            } elseif ($quickBrandAction === 'set_logo_none') {
                set_setting('header_logo_type', 'none');
                set_flash('success', __a('Üst menü "Tamamen Logosuz & Amblemsiz Kullanım" moduna alındı.', 'Header switched to "No Logo / No Emblem" mode.'));
            }
            header('Location: settings.php#marka-logo-favicon');
            exit;
        }

        // Görünüm ve aktiflik ayarları
        if (isset($_POST['header_logo_type'])) {
            set_setting('header_logo_type', trim($_POST['header_logo_type']));
        }
        if (isset($_POST['header_logo_size'])) {
            set_setting('header_logo_size', (string)max(24, min(96, (int)$_POST['header_logo_size'])));
        }
        if (isset($_POST['header_logo_text'])) {
            set_setting('header_logo_text', trim($_POST['header_logo_text']));
        }

        set_setting('brand_symbol_enabled', !empty($_POST['brand_symbol_enabled']) ? '1' : '0');
        set_setting('brand_logo_enabled', !empty($_POST['brand_logo_enabled']) ? '1' : '0');
        set_setting('brand_favicon_enabled', !empty($_POST['brand_favicon_enabled']) ? '1' : '0');

        if (isset($_POST['brand_symbol_dark'])) set_setting('brand_symbol_dark', trim($_POST['brand_symbol_dark']));
        if (isset($_POST['brand_symbol_light'])) set_setting('brand_symbol_light', trim($_POST['brand_symbol_light']));
        if (isset($_POST['brand_logo_dark'])) set_setting('brand_logo_dark', trim($_POST['brand_logo_dark']));
        if (isset($_POST['brand_logo_light'])) set_setting('brand_logo_light', trim($_POST['brand_logo_light']));
        if (isset($_POST['brand_favicon'])) set_setting('brand_favicon', trim($_POST['brand_favicon']));
        if (isset($_POST['default_og_image'])) set_setting('default_og_image', trim($_POST['default_og_image']));

        // Dosya yüklemeleri
        $upSymDark = handle_settings_brand_upload('file_symbol_dark', 'symbol-dark');
        if ($upSymDark !== null) {
            set_setting('brand_symbol_dark', $upSymDark);
            set_setting('brand_symbol_enabled', '1');
        }
        $upSymLight = handle_settings_brand_upload('file_symbol_light', 'symbol-light');
        if ($upSymLight !== null) {
            set_setting('brand_symbol_light', $upSymLight);
            set_setting('brand_symbol_enabled', '1');
        }
        $upLogoDark = handle_settings_brand_upload('file_logo_dark', 'logo-dark');
        if ($upLogoDark !== null) {
            set_setting('brand_logo_dark', $upLogoDark);
            set_setting('brand_logo_enabled', '1');
        }
        $upLogoLight = handle_settings_brand_upload('file_logo_light', 'logo-light');
        if ($upLogoLight !== null) {
            set_setting('brand_logo_light', $upLogoLight);
            set_setting('brand_logo_enabled', '1');
        }
        $upFavicon = handle_settings_brand_upload('file_favicon', 'favicon');
        if ($upFavicon !== null) {
            set_setting('brand_favicon', $upFavicon);
            set_setting('brand_favicon_enabled', '1');
        }
        $upOgImage = handle_settings_brand_upload('file_og_image', 'og-share');
        if ($upOgImage !== null) {
            set_setting('default_og_image', $upOgImage);
        }

        set_flash('success', __a('Site logosu, amblem ve favicon ayarları başarıyla kaydedildi.', 'Site logo, emblem, and favicon settings saved successfully.'));
        header('Location: settings.php#marka-logo-favicon');
        exit;
    } elseif ($action === 'general' && ($_POST['section'] ?? '') === 'integrations') {
        // Entegrasyonlar sekmesi: yalnızca Google anahtarları
        set_setting('google_analytics', trim($_POST['google_analytics'] ?? ''));
        // GA4 Mülk ID ve Service Account anahtarı İstatistikler › Bağlantı Ayarları'nda yönetilir
        set_setting('google_maps_api_key', trim($_POST['google_maps_api_key'] ?? ''));
        set_flash('success', __a('Entegrasyon ayarları kaydedildi.', 'Integration settings saved.'));
        header('Location: settings.php#entegrasyonlar');
        exit;
    } elseif ($action === 'general') {
        // Genel sekmesi: site kimliği, iletişim e-postası ve sosyal medya
        foreach ($site_languages as $sl) {
            $lc = $sl['code'];
            if (isset($_POST['site_title_' . $lc])) {
                set_setting('site_title_' . $lc, trim($_POST['site_title_' . $lc]));
            }
            if (isset($_POST['site_desc_' . $lc])) {
                set_setting('site_desc_' . $lc, trim($_POST['site_desc_' . $lc]));
            }
        }
        // Sosyal medya hesap adresleri (görünürlük Üst Menü / Alt Bilgi sayfalarında seçilir)
        foreach (['instagram_url', 'x_url', 'facebook_url', 'youtube_url', 'linkedin_url', 'behance_url'] as $sk) {
            if (isset($_POST[$sk])) {
                set_setting($sk, trim((string)$_POST[$sk]));
            }
        }
        set_setting('contact_email', trim($_POST['contact_email'] ?? ''));
        if (isset($_POST['site_owner_name'])) set_setting('site_owner_name', trim((string)$_POST['site_owner_name']));
        if (isset($_POST['contact_phone'])) set_setting('contact_phone', trim((string)$_POST['contact_phone']));

        set_flash('success', __a('Site ayarları başarıyla kaydedildi.', 'Site settings saved successfully.'));
        header('Location: settings.php#genel');
        exit;
    } elseif ($action === 'save_footer_links') {
        // Alt bilgideki ek bağlantılar (eskiden Menü Linkleri sayfasındaydı)
        $custom_links = [];
        $fl_codes = array_column(get_active_languages(), 'code');
        $urls = $_POST['custom_link_url'] ?? [];
        if (is_array($urls)) {
            foreach ($urls as $idx => $u) {
                $u = trim((string)$u);
                if (preg_match('#^\s*(javascript|data|vbscript):#i', $u)) continue;
                $row = [];
                foreach ($fl_codes as $c) {
                    $col = $_POST['custom_link_' . $c] ?? [];
                    $row['title_' . $c] = trim((string)(is_array($col) ? ($col[$idx] ?? '') : ''));
                }
                $first = (string)current(array_filter($row, 'strlen'));
                if ($u === '' || $first === '') continue;
                // Türkçe ve İngilizce boşsa ilk yazılan başlık kullanılır (eski kayıtlarla uyum)
                foreach (['tr', 'en'] as $c) { if (($row['title_' . $c] ?? '') === '') $row['title_' . $c] = $first; }
                $row['url'] = $u;
                $custom_links[] = $row;
            }
        }
        set_setting('footer_custom_links_json', json_encode($custom_links, JSON_UNESCAPED_UNICODE));
        set_flash('success', __a('Alt bilgi bağlantıları kaydedildi.', 'Footer links saved.'));
        header('Location: settings.php#alt-bilgi-baglantilari');
        exit;
    } elseif ($action === 'toggle_module') {
        $mod_key = $_POST['module_key'] ?? '';
        $allowed_keys = [
            'blog_enabled',
            'contact_page_enabled',
            'legal_pages_enabled',
            'image_protection_enabled',
            'maintenance_mode',
            'inner_enable_lightbox',
            'lightbox_show_counter',
            'lightbox_show_slideshow',
            'lightbox_show_zoom',
            'lightbox_show_fullscreen',
            'lightbox_show_exif',
            'cookie_consent_enabled',
        ];
        if (in_array($mod_key, $allowed_keys, true)) {
            $enabled = (!empty($_POST['enabled']) && $_POST['enabled'] !== '0' && $_POST['enabled'] !== 'false') ? '1' : '0';
            set_setting($mod_key, $enabled);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'enabled' => ($enabled === '1'),
            ]);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false]);
        exit;
    } elseif ($action === 'save_lightbox_and_maintenance') {
        $lb_keys = [
            'inner_enable_lightbox',
            'lightbox_show_counter',
            'lightbox_show_slideshow',
            'lightbox_show_zoom',
            'lightbox_show_fullscreen',
            'lightbox_show_exif',
        ];
        // Lightbox anahtarları yalnızca kendi formundan gelirse yazılır (bakım formu bunları sıfırlamasın)
        if (isset($_POST['lightbox_form'])) {
            foreach ($lb_keys as $lk) {
                set_setting($lk, !empty($_POST[$lk]) ? '1' : '0');
            }
        }
        if (isset($_POST['image_protection_enabled_form'])) {
            set_setting('image_protection_enabled', !empty($_POST['image_protection_enabled']) ? '1' : '0');
        }
        if (isset($_POST['maintenance_mode_form'])) {
            set_setting('maintenance_mode', !empty($_POST['maintenance_mode']) ? '1' : '0');
        }
        save_settings_l(['maintenance_title', 'maintenance_text'], $_POST); // sitedeki her dil

        set_flash('success', __a('Ayarlar kaydedildi.', 'Settings saved.'));
        header('Location: settings.php#' . (isset($_POST['lightbox_form']) ? 'sayfalar' : 'bakim-modu'));
        exit;
    } elseif ($action === 'save_cookie_consent') {
        require_once __DIR__ . '/../includes/cookie_consent.php';
        set_setting('cookie_consent_enabled', !empty($_POST['cookie_consent_enabled']) ? '1' : '0');
        set_setting('cookie_banner_position', ($_POST['cookie_banner_position'] ?? 'box') === 'bar' ? 'bar' : 'box');

        $cc_keys = array_keys(cookie_consent_fields());
        foreach ($site_languages as $sl) {
            $lc = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$sl['code']));
            if ($lc === '') continue;
            foreach ($cc_keys as $ck) {
                $field = 'cookie_banner_' . $ck . '_' . $lc;
                if (isset($_POST[$field])) {
                    set_setting($field, mb_substr(trim((string)$_POST[$field]), 0, 2000, 'UTF-8'));
                }
            }
            $pf = 'cookie_banner_policy_url_' . $lc;
            if (isset($_POST[$pf])) {
                $pu = trim((string)$_POST[$pf]);
                // Yalnızca http(s) veya site içi yol kabul edilir (javascript: vb. engellenir)
                if ($pu !== '' && !preg_match('#^(https?://|/)?[A-Za-z0-9._~:/?\#\[\]@!$&()*+,;=%-]+$#', $pu) || preg_match('#^[a-z][a-z0-9+.-]*:#i', $pu) && !preg_match('#^https?://#i', $pu)) {
                    $pu = '';
                }
                set_setting($pf, $pu);
            }
        }
        set_flash('success', __a('Çerez onayı ayarları kaydedildi.', 'Cookie consent settings saved.'));
        header('Location: settings.php#cerez-onayi');
        exit;
    } elseif ($action === 'save_announcement') {
        set_setting('announce_enabled', !empty($_POST['announce_enabled']) ? '1' : '0');
        foreach ($site_languages as $sl) {
            $lc = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$sl['code']));
            if ($lc !== '' && isset($_POST['announce_text_' . $lc])) {
                set_setting('announce_text_' . $lc, mb_substr(trim((string)$_POST['announce_text_' . $lc]), 0, 300, 'UTF-8'));
            }
        }
        $al = trim((string)($_POST['announce_link'] ?? ''));
        if ($al !== '' && !preg_match('#^(https?://|/)#i', $al)) { $al = ''; }
        set_setting('announce_link', $al);
        $au = trim((string)($_POST['announce_until'] ?? ''));
        set_setting('announce_until', preg_match('/^\d{4}-\d{2}-\d{2}$/', $au) ? $au : '');
        set_flash('success', __a('Duyuru bandı kaydedildi.', 'Announcement bar saved.'));
        header('Location: settings.php#duyuru-bandi');
        exit;
    } elseif ($action === 'reset_cookie_consent') {
        set_setting('cookie_consent_version', (string)(max(1, (int)get_setting('cookie_consent_version', '1')) + 1));
        set_flash('success', __a('Tüm ziyaretçilerden çerez onayı yeniden istenecek.', 'All visitors will be asked for cookie consent again.'));
        header('Location: settings.php#cerez-onayi');
        exit;
    } elseif ($action === 'toggle_legal_pages') {
        $enabled = (!empty($_POST['enabled']) && $_POST['enabled'] !== '0' && $_POST['enabled'] !== 'false') ? '1' : '0';
        set_setting('legal_pages_enabled', $enabled);

        if (!empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'enabled' => ($enabled === '1'),
                'message' => ($enabled === '1') ? 'Hukuki metin sayfaları sitede aktif edildi.' : 'Hukuki metin sayfaları sitede gizlendi.'
            ]);
            exit;
        }

        set_flash('success', ($enabled === '1') ? 'Hukuki metin sayfaları sitede aktif edildi.' : 'Hukuki metin sayfaları sitede gizlendi.');
        header('Location: settings.php#gizlilik');
        exit;
    } elseif ($action === 'legal') {
        set_setting('kvkk_aydinlatma_metni_tr', trim($_POST['kvkk_aydinlatma_metni_tr'] ?? ''));
        set_setting('kvkk_aydinlatma_metni_en', trim($_POST['kvkk_aydinlatma_metni_en'] ?? ''));
        set_setting('kvkk_acik_riza_metni_tr', trim($_POST['kvkk_acik_riza_metni_tr'] ?? ''));
        set_setting('kvkk_acik_riza_metni_en', trim($_POST['kvkk_acik_riza_metni_en'] ?? ''));
        set_flash('success', 'KVKK Aydınlatma ve Açık Rıza metinleri başarıyla güncellendi.');
    } elseif ($action === 'reset_legal') {
        set_setting('kvkk_aydinlatma_metni_tr', get_default_kvkk_aydinlatma_tr());
        set_setting('kvkk_aydinlatma_metni_en', get_default_kvkk_aydinlatma_en());
        set_setting('kvkk_acik_riza_metni_tr', get_default_kvkk_acik_riza_tr());
        set_setting('kvkk_acik_riza_metni_en', get_default_kvkk_acik_riza_en());
        set_flash('success', 'Yasal metinler Türkiye KVKK mevzuatına uygun standart şablona sıfırlandı.');
    } elseif ($action === 'password') {
        $user_id = $_SESSION['admin_user_id'];
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($current_pass, $u['password_hash'])) {
            set_flash('error', 'Mevcut parolanız hatalı.');
        } elseif (mb_strlen($new_pass) < 10) {
            set_flash('error', 'Yeni parola en az 10 karakter olmalıdır.');
        } elseif ($new_pass !== $confirm_pass) {
            set_flash('error', 'Yeni parolalar birbiriyle eşleşmiyor.');
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $up = $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?");
            $up->execute([$new_hash, $user_id]);
            // Bu oturum yeni parolaya bağlanır; eski parolayla açılmış diğer oturumlar is_logged_in() içinde düşer
            session_regenerate_id(true);
            $_SESSION['admin_pw_sig'] = admin_pw_sig($new_hash);
            set_flash('success', 'Parolanız başarıyla güncellendi.');
        }
    }

    $return_hash = [
        'password'    => '#hesap',
        'legal'       => '#gizlilik',
        'reset_legal' => '#gizlilik',
    ][$action] ?? '';
    header('Location: settings.php' . $return_hash);
    exit;
}

$admin_title     = __a('menu_settings', 'Site Ayarları');
$breadcrumb_root = __a('bc_system', 'Sistem');
$breadcrumb_page = __a('menu_settings', 'Site Ayarları');

$blog_enabled             = (get_setting('blog_enabled', '0') === '1');
$contact_enabled          = (get_setting('contact_page_enabled', '0') === '1');
$legal_pages_enabled      = (get_setting('legal_pages_enabled', '1') === '1');
$image_protection_enabled = (get_setting('image_protection_enabled', '1') === '1');
$maintenance_mode         = (get_setting('maintenance_mode', '0') === '1');

$lb_enabled    = (get_setting('inner_enable_lightbox', '1') === '1');
$lb_counter    = (get_setting('lightbox_show_counter', '1') === '1');
$lb_slideshow  = (get_setting('lightbox_show_slideshow', '1') === '1');
$lb_zoom       = (get_setting('lightbox_show_zoom', '1') === '1');
$lb_fullscreen = (get_setting('lightbox_show_fullscreen', '1') === '1');
$lb_exif       = (get_setting('lightbox_show_exif', '1') === '1');

// Dinamik sayfa isimleri
$homepage_nav_title    = get_setting('homepage_title_' . $admin_lang) ?: __a('menu_homepage', 'Ana Sayfa');
$portfolio_nav_title   = get_setting('portfolio_title_' . $admin_lang) ?: __a('menu_portfolio', 'Portfolyo');
$portfolio_home_title  = $portfolio_nav_title . ' ' . __a('menu_homepage', 'Ana Sayfa');
$portfolio_inner_title = $portfolio_nav_title . ' ' . __a('inner_pages', 'İç Sayfalar');
$blog_nav_title        = get_setting('blog_title_' . $admin_lang) ?: __a('menu_blog', 'Blog / Yazılar');
$about_nav_title       = get_setting('about_title_' . $admin_lang) ?: __a('menu_about', 'Hakkımda');
$contact_nav_title     = get_setting('contact_title_' . $admin_lang) ?: __a('menu_contact_page', 'İletişim');

require __DIR__ . '/includes/header.php';
?>

<style>
/* ── Modül Açma / Kapama Anahtarı (Toggle Switch) ── */
.legal-switch-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    background: var(--bg-surface, #f8fafc);
    padding: 4px 10px 4px 6px;
    border-radius: 999px;
    border: 1px solid var(--border-color, #e2e8f0);
    transition: all 0.2s ease;
}
.legal-switch-wrap:hover {
    border-color: var(--primary, #2563eb);
}
.legal-switch-wrap input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}
.legal-switch-slider {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
    background-color: #cbd5e1;
    border-radius: 999px;
    transition: all 0.2s ease-in-out;
}
.legal-switch-slider::before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    top: 3px;
    background-color: #ffffff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    transition: all 0.2s ease-in-out;
}
.legal-switch-wrap input:checked + .legal-switch-slider {
    background-color: #16a34a;
}
.legal-switch-wrap input:checked + .legal-switch-slider::before {
    transform: translateX(16px);
}
.legal-badge-pill {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.03em;
    padding: 2px 7px;
    border-radius: 6px;
    transition: all 0.2s ease;
}
.badge-state-on {
    background: var(--success-soft);
    color: var(--success-text);
}
.badge-state-off {
    background: var(--bg-canvas-subtle);
    color: var(--text-muted);
}
[data-theme="dark"] .legal-switch-slider {
    background-color: #383838;
}
[data-theme="dark"] .badge-state-on {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}
[data-theme="dark"] .badge-state-off {
    background: var(--bg-surface-2);
    color: var(--text-muted);
}

/* Ayar Merkezi Kutucukları */
.settings-hub-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(255px, 1fr));
    gap: 14px;
}
.customizer-nav-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    background: var(--bg-surface);
    transition: all 0.16s ease;
}
.customizer-nav-card:hover {
    border-color: var(--primary, #2563eb);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.07);
}
.customizer-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    color: var(--text-main);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Dil Sekmeleri (Site Kimliği & SEO İçin) */
.seo-lang-tabs {
    display: flex;
    gap: 6px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 16px;
    overflow-x: auto;
}
.seo-lang-tab {
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.seo-lang-tab.active {
    color: var(--text-main);
    border-bottom-color: #0284c7;
}
</style>

<?php
/**
 * Ayar satırı anahtarı (anında kaydedilir). $on_label/$off_label rozet metinleridir.
 */
function st_switch(string $key, bool $on, string $badge_id, string $on_label, string $off_label, string $input_id = '', string $extra_js = ''): string {
    $id_attr = $input_id !== '' ? ' id="' . e($input_id) . '"' : '';
    return '<label class="legal-switch-wrap">'
        . '<input type="checkbox"' . $id_attr . ($on ? ' checked' : '')
        . ' onchange="toggleSiteModule(\'' . e($key) . '\', this.checked, \'' . e($badge_id) . '\', null);' . $extra_js . '">'
        . '<span class="legal-switch-slider"></span>'
        . '<span id="' . e($badge_id) . '" class="legal-badge-pill ' . ($on ? 'badge-state-on' : 'badge-state-off') . '"'
        . ' data-on="' . e($on_label) . '" data-off="' . e($off_label) . '">' . e($on ? $on_label : $off_label) . '</span>'
        . '</label>';
}
$L_live   = __a('Yayında', 'Live');
$L_hidden = __a('Gizli', 'Hidden');
$L_on     = __a('Açık', 'On');
$L_off    = __a('Kapalı', 'Off');

require_once __DIR__ . '/includes/settings_tabs.php';
?>

<?php render_settings_header_and_tabs(); ?>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ── 1. GENEL ── -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section class="st-panel" id="genel" data-tab="genel" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e(__a('Site Kimliği & İletişim', 'Site Identity & Contact')) ?></h2>
                <p class="card-sub"><?= e(__a('Arama motorlarında ve tarayıcı sekmesinde görünen site adı, açıklama ve sosyal medya hesaplarınız.', 'Site name and description shown in search engines and browser tabs, plus your social accounts.')) ?></p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="settings.php#genel" data-autosave id="seoIdentityFields">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="general">
                <input type="hidden" name="section" value="identity">

                <?php lang_bar(['scope' => '#seoIdentityFields', 'click' => '#seoTabBtn_{lang}', 'hide' => '.seo-lang-tabs']); ?>
                <div class="seo-lang-tabs">
                    <?php foreach ($site_languages as $idx => $sl):
                        $lc = $sl['code'];
                        $flag = ($lc === 'tr') ? '🇹🇷' : (($lc === 'en') ? '🇬🇧' : (in_array($lc, ['hy', 'hyw'], true) ? '🇦🇲' : '🌐'));
                    ?>
                        <button type="button" class="seo-lang-tab <?= $idx === 0 ? 'active' : '' ?>" id="seoTabBtn_<?= e($lc) ?>" onclick="switchSeoLang('<?= e($lc) ?>')">
                            <span><?= $flag ?></span>
                            <span><?= e($sl['name_native']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($site_languages as $idx => $sl):
                    $lc = $sl['code'];
                ?>
                <div class="seo-lang-pane" id="seoPane_<?= e($lc) ?>" style="<?= $idx === 0 ? '' : 'display:none;' ?>">
                    <div class="form-group">
                        <label class="form-label"><?= e(__a('Site Başlığı', 'Site Title')) ?> (<?= strtoupper($lc) ?>)</label>
                        <input type="text" name="site_title_<?= e($lc) ?>" class="form-control" value="<?= e(get_setting('site_title_' . $lc, get_setting('site_title_en', site_owner_name()))) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(__a('Site Açıklaması (Google’da görünen kısa metin)', 'Site Description (shown in Google results)')) ?> (<?= strtoupper($lc) ?>)</label>
                        <textarea name="site_desc_<?= e($lc) ?>" class="form-control" rows="2"><?= e(get_setting('site_desc_' . $lc)) ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="form-group" style="margin-top:16px;">
                    <label class="form-label"><?= e(__a('Site Sahibi (Ad Soyad / Stüdyo)', 'Site Owner (Name / Studio)')) ?></label>
                    <input type="text" name="site_owner_name" class="form-control" maxlength="120" value="<?= e(get_setting('site_owner_name')) ?>" placeholder="<?= e(site_owner_name()) ?>">
                    <p class="form-hint"><?= e(__a('Telif uyarısı, Google yapısal verisi, yasal metin künyesi, RSS yazarı ve panel başlığında kullanılır.', 'Used in the copyright notice, Google structured data, legal notice, feed author and admin header.')) ?></p>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label class="form-label"><?= e(__a('İletişim E-Postası', 'Contact Email')) ?></label>
                    <input type="email" name="contact_email" class="form-control" value="<?= e(get_setting('contact_email', '')) ?>">
                    <p class="form-hint"><?= e(__a('İletişim formundan gelen mesajların bildirimi bu adrese gönderilir.', 'Contact form notifications are sent to this address.')) ?></p>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label class="form-label"><?= e(__a('Telefon', 'Phone')) ?></label>
                    <input type="tel" name="contact_phone" class="form-control" maxlength="40" value="<?= e(get_setting('contact_phone')) ?>">
                    <p class="form-hint"><?= e(__a('Boş bırakılırsa İletişim sayfasında görünmez.', 'Hidden on the Contact page when empty.')) ?></p>
                </div>

                <!-- ── SOSYAL MEDYA HESAPLARI (tek yer) ── -->
                <div id="sosyal-medya" style="margin-top:22px;">
                    <h3 class="card-title" style="font-size:14px;"><?= e(__a('Sosyal Medya Hesapları', 'Social Media Accounts')) ?></h3>
                    <p class="form-hint" style="margin-bottom:12px;"><?= e(__a('Adresleri burada girin. İkonların üst menüde veya alt bilgide görünüp görünmeyeceğini Temalar › Özelleştir › Menü / Alt Bilgi bölümlerinden seçersiniz.', 'Enter the addresses here. Choose whether icons appear in the header or footer under Themes › Customise › Menu / Footer.')) ?></p>
                    <div class="form-grid-2">
                        <?php
                        $social_fields = [
                            'instagram_url' => ['Instagram', 'https://instagram.com/...'],
                            'x_url'         => ['X (Twitter)', 'https://x.com/...'],
                            'youtube_url'   => ['YouTube', 'https://youtube.com/@...'],
                            'facebook_url'  => ['Facebook', 'https://facebook.com/...'],
                            'linkedin_url'  => ['LinkedIn', 'https://linkedin.com/in/...'],
                            'behance_url'   => ['Behance / Vimeo', 'https://behance.net/...'],
                        ];
                        foreach ($social_fields as $sf_key => [$sf_label, $sf_ph]): ?>
                        <div class="form-group">
                            <label class="form-label"><?= e($sf_label) ?></label>
                            <input type="text" name="<?= e($sf_key) ?>" class="form-control" placeholder="<?= e($sf_ph) ?>" value="<?= e(get_setting($sf_key)) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="setting-row-desc" style="margin-top:2px;"><?= e(__a('Görünürlük:', 'Visibility:')) ?> <a href="templates.php?design=menu"><?= e(__a('Üst Menü', 'Header')) ?></a> · <a href="templates.php?design=footer"><?= e(__a('Alt Bilgi', 'Footer')) ?></a></div>
                </div>

                <div class="settings-save-bar">
                    <button type="submit" class="btn btn-primary"><?= e(__a('Kaydet', 'Save')) ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alt bilgideki ek bağlantılar (eskiden Menü Linkleri sayfasındaydı) -->
    <?php
    $custom_links = json_decode(get_setting('footer_custom_links_json', '[]') ?: '[]', true);
    if (!is_array($custom_links)) $custom_links = [];
    $fl_row = function (array $cl = []): string {
        $h = '<div class="custom-link-row" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">';
        foreach (lang_bar_languages() as $fl) {
            $h .= '<input type="text" name="custom_link_' . e($fl['code']) . '[]" class="form-control" style="flex:1 1 150px;" dir="' . e($fl['dir']) . '" placeholder="' . e($fl['flag'] . ' ' . __a('Başlık', 'Title')) . '" aria-label="' . e(__a('Başlık', 'Title') . ' (' . $fl['name'] . ')') . '" value="' . e($cl['title_' . $fl['code']] ?? '') . '">';
        }
        $h .= '<input type="text" style="flex:1.4 1 200px;" name="custom_link_url[]" class="form-control" placeholder="' . e(__a('Adres (ör. /tr/harita/ veya https://…)', 'Address (e.g. /en/map/ or https://…)')) . '" aria-label="' . e(__a('Adres', 'Address')) . '" value="' . e($cl['url'] ?? '') . '">';
        $h .= '<button type="button" class="btn btn-secondary btn-sm" onclick="var f = this.form; this.parentElement.remove(); if (f) f.dispatchEvent(new Event(\'change\'));" aria-label="' . e(__a('Bağlantıyı kaldır', 'Remove link')) . '">✕</button></div>';
        return $h;
    };
    ?>
    <div class="card" id="alt-bilgi-baglantilari">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e(__a('Alt bilgideki ek bağlantılar', 'Extra footer links')) ?></h2>
                <p class="card-sub"><?= e(__a('Yalnızca sayfanın en altındaki bilgi şeridinde görünen bağlantılar (ör. künye, basın dosyası). Üst menü Sayfalar ekranından, alt bilginin görünüşü Temalar › Özelleştir › Alt Bilgi bölümünden düzenlenir.', 'Links shown only in the footer strip (e.g. imprint, press kit). The main menu is edited on the Pages screen; the footer design under Themes › Customise › Footer.')) ?></p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="settings.php#alt-bilgi-baglantilari" data-autosave>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_footer_links">
                <div id="customFooterLinksContainer" style="display:flex; flex-direction:column; gap:10px; margin-bottom:12px;">
                    <?php foreach ($custom_links as $cl) echo $fl_row($cl); ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('customFooterLinksContainer').insertAdjacentHTML('beforeend', document.getElementById('flRowTpl').innerHTML)"><?= e(__a('Bağlantı ekle', 'Add link')) ?></button>
                <div class="settings-save-bar">
                    <button type="submit" class="btn btn-primary"><?= e(__a('Kaydet', 'Save')) ?></button>
                </div>
            </form>
            <template id="flRowTpl"><?= $fl_row() ?></template>
        </div>
    </div>

    <!-- Duyuru Bandı -->
    <div class="card" id="duyuru-bandi">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e(__a('Duyuru Bandı', 'Announcement Bar')) ?></h2>
                <p class="card-sub"><?= e(__a('Sergi, kitap ya da atölye gibi kısa bir duyuruyu sitenin altında ince bir bantta gösterir. Ziyaretçi kapatabilir; bitiş tarihinden sonra kendiliğinden kalkar.', 'Shows a short notice (exhibition, book, workshop) in a slim bar at the bottom of the site. Visitors can close it; it disappears after the end date.')) ?></p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="settings.php#duyuru-bandi" data-autosave>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_announcement">
                <label style="display:flex; align-items:center; gap:10px; margin-bottom:14px; font-weight:600; cursor:pointer;">
                    <input type="checkbox" name="announce_enabled" value="1" <?= get_setting('announce_enabled', '0') === '1' ? 'checked' : '' ?> style="width:16px; height:16px;">
                    <span><?= e(__a('Duyuru bandını göster', 'Show the announcement bar')) ?></span>
                </label>
                <div class="form-grid-2">
                    <?php foreach ($site_languages as $sl): $lc = $sl['code']; ?>
                    <div class="form-group">
                        <label class="form-label"><?= e(__a('Duyuru metni', 'Announcement text')) ?> (<?= e(strtoupper($lc)) ?>)</label>
                        <input type="text" name="announce_text_<?= e($lc) ?>" class="form-control" maxlength="300" value="<?= e(get_setting('announce_text_' . $lc)) ?>" placeholder="<?php $eg = __a('Örn:', 'e.g.'); /* önek panel dilinde, örnek alanın dilinde */ echo e($eg . (preg_match('/：$/u', $eg) ? '' : ' ') . site_t('Yeni sergi: Surlar ve Suretler · 12 Aralık, İstanbul', 'New exhibition: Walls and Faces · 12 December, Istanbul', $lc)); ?>">
                    </div>
                    <?php endforeach; ?>
                    <div class="form-group">
                        <label class="form-label"><?= e(__a('Bağlantı (isteğe bağlı)', 'Link (optional)')) ?></label>
                        <input type="text" name="announce_link" class="form-control" value="<?= e(get_setting('announce_link')) ?>" placeholder="/tr/... ya da https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(__a('Bitiş tarihi (isteğe bağlı)', 'End date (optional)')) ?></label>
                        <input type="date" name="announce_until" class="form-control" value="<?= e(get_setting('announce_until')) ?>">
                        <p class="form-hint"><?= e(__a('Bu tarihten sonra bant kendiliğinden gizlenir.', 'After this date the bar hides automatically.')) ?></p>
                    </div>
                </div>
                <div class="settings-save-bar">
                    <button type="submit" class="btn btn-primary"><?= e(__a('Kaydet', 'Save')) ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bakım Modu -->
    <div class="card" id="bakim-modu">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e(__a('Bakım Modu', 'Maintenance Mode')) ?></h2>
                <p class="card-sub"><?= e(__a('Açıkken ziyaretçiler siteyi değil, aşağıdaki bilgilendirme ekranını görür. Siz yönetici olarak siteyi normal görmeye devam edersiniz.', 'When on, visitors see the notice below instead of the site. As an admin you still see the site normally.')) ?></p>
            </div>
            <?= st_switch('maintenance_mode', $maintenance_mode, 'modBadgeMaint', $L_on, $L_off, 'maintenanceToggle') ?>
        </div>
        <div class="card-body">
            <form method="POST" action="settings.php#bakim-modu" data-autosave id="maintenanceFields">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_lightbox_and_maintenance">
                <?php lang_bar(['scope' => '#maintenanceFields']); ?>
                <?php lb_panes(function ($c, $l) {
                    $dt = ['tr' => 'Yeni Sergi & Seçki Hazırlanıyor', 'en' => 'New Exhibition in Preparation'][$c] ?? '';
                    $dx = ['tr' => 'Görsel arşivimiz ve yeni belgesel fotoğraf projelerimiz güncelleniyor. Çok yakında yeniden yayındayız.', 'en' => 'Our visual archive and new documentary photography projects are currently being updated. We will be back online shortly.'][$c] ?? '';
                ?>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="mt_t_<?= e($c) ?>"><?= e(__a('Başlık', 'Title')) ?><?= lb_tag($l) ?></label>
                        <input type="text" id="mt_t_<?= e($c) ?>" name="maintenance_title_<?= e($c) ?>" class="form-control" value="<?= e(get_setting('maintenance_title_' . $c, $dt)) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="mt_x_<?= e($c) ?>"><?= e(__a('Açıklama', 'Message')) ?><?= lb_tag($l) ?></label>
                        <textarea id="mt_x_<?= e($c) ?>" name="maintenance_text_<?= e($c) ?>" class="form-control" rows="2"><?= e(get_setting('maintenance_text_' . $c, $dx)) ?></textarea>
                    </div>
                </div>
                <?php }); ?>
                <div class="settings-save-bar">
                    <button type="submit" class="btn btn-secondary"><?= e(__a('Kaydet', 'Save')) ?></button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ── 2. LOGO & FAVICON ── -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section class="st-panel" id="marka" data-tab="marka" role="tabpanel">
<?php
$b_logo_type       = get_setting('header_logo_type', 'symbol');
$b_logo_size       = get_setting('header_logo_size', '52');
$b_logo_text       = get_setting('header_logo_text', mb_strtoupper(site_owner_name()));
$b_symbol_enabled  = get_setting('brand_symbol_enabled', '1');
$b_symbol_dark     = get_setting('brand_symbol_dark', 'assets/brand/symbol-dark.svg');
$b_symbol_light    = get_setting('brand_symbol_light', 'assets/brand/symbol-light.svg');
$b_logo_enabled    = get_setting('brand_logo_enabled', '1');
$b_logo_dark       = get_setting('brand_logo_dark', 'assets/brand/symbol-dark.svg');
$b_logo_light      = get_setting('brand_logo_light', 'assets/brand/symbol-light.svg');
$b_favicon_enabled = get_setting('brand_favicon_enabled', '1');
$b_favicon         = get_setting('brand_favicon', 'assets/brand/favicon.png');
$b_og_image        = get_setting('default_og_image', '');
?>
<div class="card" id="marka-logo-favicon">
    <form method="POST" action="settings.php#marka-logo-favicon" enctype="multipart/form-data" id="brandingSettingsForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save_branding">
        <input type="hidden" name="brand_quick_action" id="settingsBrandQuickAction" value="">

        <div class="card-header" style="padding: 16px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 class="card-title" style="font-size: 16px; margin:0; display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent-blue);"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span><?= e(__a('Logo, Amblem & Favicon Dosyaları', 'Logo, Emblem & Favicon Files')) ?></span>
                </h2>
                <span style="font-size: 12px; color: var(--text-muted);"><?= e(__a('Sitenizin amblemini, tam logosunu, tarayıcı sekme ikonunu (favicon) ve sosyal medya paylaşım kapağını buradan yükleyebilir, değiştirebilir veya tek tıkla kaldırabilirsiniz.', 'Upload, change, or remove your site emblem, full logo, browser tab favicon, and social share image from one place.')) ?></span>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span><?= e(__a('Kaydet', 'Save')) ?></span>
                </button>
            </div>
        </div>

        <div class="card-body" style="padding: 20px; display:flex; flex-direction:column; gap:20px;">
            <div class="settings-hint-card" style="margin-bottom:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/></svg>
                <span><?= e(__a('Burada logo dosyalarını yüklersiniz. Logonun üst menüde nasıl görüneceğini (amblem, tam logo, sadece isim, boyut) seçmek için:', 'Upload logo files here. To choose how the logo appears in the header (emblem, full logo, name only, size):')) ?> <a href="templates.php?design=logo"><?= e(__a('Üst Menü › Logo', 'Header › Logo')) ?> →</a></span>
            </div>

            <!-- 3 Sütunlu Kartlar: 1. Amblem, 2. Tam Logo, 3. Favicon & Sosyal Paylaşım -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:18px;">

                <!-- KART 1: AMBLEM (SEMBOL) -->
                <div style="border:1px solid var(--border-card); border-radius:10px; background:var(--bg-surface); display:flex; flex-direction:column; justify-content:space-between; overflow:hidden;">
                    <div>
                        <div style="padding:12px 16px; border-bottom:1px solid var(--border-subtle); background:var(--bg-surface-2); display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div>
                                <div style="font-weight:700; font-size:14px; color:var(--text-main);"><?= e(__a('1. Amblem (Sembol)', '1. Emblem (Symbol)')) ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= e(__a('Kare veya ikonik sembol (SVG, PNG, WEBP)', 'Square/iconic symbol (SVG, PNG, WEBP)')) ?></div>
                            </div>
                            <label class="legal-switch-wrap">
                                <input type="checkbox" name="brand_symbol_enabled" value="1" <?= $b_symbol_enabled === '1' ? 'checked' : '' ?>>
                                <span class="legal-switch-slider"></span>
                                <span class="legal-badge-pill <?= $b_symbol_enabled === '1' ? 'badge-state-on' : 'badge-state-off' ?>">
                                    <?= $b_symbol_enabled === '1' ? e(__a('Aktif', 'On')) : e(__a('Kapalı', 'Off')) ?>
                                </span>
                            </label>
                        </div>

                        <div style="padding:14px 16px; display:flex; flex-direction:column; gap:12px;">
                            <!-- Koyu Zemin Amblemi -->
                            <div style="background:#0f172a; color:#fff; border-radius:6px; padding:10px 12px; display:flex; align-items:center; gap:12px;">
                                <div style="width:48px; height:48px; border-radius:6px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.14); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <?php if ($b_symbol_enabled === '1' && !empty($b_symbol_dark)): ?>
                                        <img src="<?= e(url($b_symbol_dark)) ?>" alt="Amblem (Koyu)" style="max-width:34px; max-height:34px; object-fit:contain;">
                                    <?php else: ?>
                                        <span style="font-size:12px; opacity:0.5;"><?= e(__a('Yok', 'None')) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12px; font-weight:700; margin-bottom:4px;"><?= e(__a('Koyu Zemin Amblemi (Açık Renk)', 'Dark Surface Emblem (Light)')) ?></div>
                                    <input type="file" name="file_symbol_dark" accept=".svg,.png,.webp,.jpg,.jpeg,.ico" class="form-control" style="font-size:12px; padding:3px 6px; background:#1e293b; color:#fff; border-color:#334155;">
                                    <input type="text" name="brand_symbol_dark" value="<?= e($b_symbol_dark) ?>" class="form-control" style="font-size:12px; padding:3px 6px; margin-top:4px; background:#1e293b; color:#cbd5e1; border-color:#334155;" placeholder="assets/brand/symbol-dark.svg">
                                </div>
                            </div>

                            <!-- Açık Zemin Amblemi -->
                            <div style="background:#f4f2ed; color:#111; border:1px solid var(--border-light); border-radius:6px; padding:10px 12px; display:flex; align-items:center; gap:12px;">
                                <div style="width:48px; height:48px; border-radius:6px; background:#ffffff; border:1px solid rgba(0,0,0,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <?php if ($b_symbol_enabled === '1' && !empty($b_symbol_light)): ?>
                                        <img src="<?= e(url($b_symbol_light)) ?>" alt="<?= e(__a('Amblem (Açık)', 'Symbol (Light)')) ?>" style="max-width:34px; max-height:34px; object-fit:contain;">
                                    <?php else: ?>
                                        <span style="font-size:12px; opacity:0.5;"><?= e(__a('Yok', 'None')) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12px; font-weight:700; margin-bottom:4px;"><?= e(__a('Açık Zemin Amblemi (Koyu Renk)', 'Light Surface Emblem (Dark)')) ?></div>
                                    <input type="file" name="file_symbol_light" accept=".svg,.png,.webp,.jpg,.jpeg,.ico" class="form-control" style="font-size:12px; padding:3px 6px;">
                                    <input type="text" name="brand_symbol_light" value="<?= e($b_symbol_light) ?>" class="form-control" style="font-size:12px; padding:3px 6px; margin-top:4px;" placeholder="assets/brand/symbol-light.svg">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="padding:10px 16px; border-top:1px solid var(--border-subtle); background:var(--bg-surface-2); display:flex; gap:8px;">
                        <button type="button" class="btn btn-secondary btn-sm" style="flex:1; justify-content:center; font-size:12px;" onclick="triggerSettingsBrandAction('reset_symbol')"><?= e(__a('Varsayılana Dön', 'Reset Default')) ?></button>
                        <button type="button" class="btn btn-danger btn-sm" style="flex:1; justify-content:center; font-size:12px;" onclick="triggerSettingsBrandAction('remove_symbol')"><?= e(__a('Amblemi Kaldır', 'Remove Emblem')) ?></button>
                    </div>
                </div>

                <!-- KART 2: TAM LOGO -->
                <div style="border:1px solid var(--border-card); border-radius:10px; background:var(--bg-surface); display:flex; flex-direction:column; justify-content:space-between; overflow:hidden;">
                    <div>
                        <div style="padding:12px 16px; border-bottom:1px solid var(--border-subtle); background:var(--bg-surface-2); display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div>
                                <div style="font-weight:700; font-size:14px; color:var(--text-main);"><?= e(__a('2. Site Logosu (Tam Logo)', '2. Site Logo (Full Logo)')) ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= e(__a('Yatay veya tam marka logosu (SVG, PNG, WEBP)', 'Horizontal or full brand logo (SVG, PNG, WEBP)')) ?></div>
                            </div>
                            <label class="legal-switch-wrap">
                                <input type="checkbox" name="brand_logo_enabled" value="1" <?= $b_logo_enabled === '1' ? 'checked' : '' ?>>
                                <span class="legal-switch-slider"></span>
                                <span class="legal-badge-pill <?= $b_logo_enabled === '1' ? 'badge-state-on' : 'badge-state-off' ?>">
                                    <?= $b_logo_enabled === '1' ? e(__a('Aktif', 'On')) : e(__a('Kapalı', 'Off')) ?>
                                </span>
                            </label>
                        </div>

                        <div style="padding:14px 16px; display:flex; flex-direction:column; gap:12px;">
                            <!-- Koyu Zemin Tam Logo -->
                            <div style="background:#0f172a; color:#fff; border-radius:6px; padding:10px 12px; display:flex; align-items:center; gap:12px;">
                                <div style="width:68px; height:48px; border-radius:6px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.14); display:flex; align-items:center; justify-content:center; flex-shrink:0; padding:4px;">
                                    <?php if ($b_logo_enabled === '1' && !empty($b_logo_dark)): ?>
                                        <img src="<?= e(url($b_logo_dark)) ?>" alt="Logo (Koyu)" style="max-width:58px; max-height:36px; object-fit:contain;">
                                    <?php else: ?>
                                        <span style="font-size:12px; opacity:0.5;"><?= e(__a('Yok', 'None')) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12px; font-weight:700; margin-bottom:4px;"><?= e(__a('Koyu Zemin Logosu (Açık Renk)', 'Dark Surface Logo (Light)')) ?></div>
                                    <input type="file" name="file_logo_dark" accept=".svg,.png,.webp,.jpg,.jpeg" class="form-control" style="font-size:12px; padding:3px 6px; background:#1e293b; color:#fff; border-color:#334155;">
                                    <input type="text" name="brand_logo_dark" value="<?= e($b_logo_dark) ?>" class="form-control" style="font-size:12px; padding:3px 6px; margin-top:4px; background:#1e293b; color:#cbd5e1; border-color:#334155;" placeholder="assets/brand/symbol-dark.svg">
                                </div>
                            </div>

                            <!-- Açık Zemin Tam Logo -->
                            <div style="background:#f4f2ed; color:#111; border:1px solid var(--border-light); border-radius:6px; padding:10px 12px; display:flex; align-items:center; gap:12px;">
                                <div style="width:68px; height:48px; border-radius:6px; background:#ffffff; border:1px solid rgba(0,0,0,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0; padding:4px;">
                                    <?php if ($b_logo_enabled === '1' && !empty($b_logo_light)): ?>
                                        <img src="<?= e(url($b_logo_light)) ?>" alt="<?= e(__a('Logo (Açık)', 'Logo (Light)')) ?>" style="max-width:58px; max-height:36px; object-fit:contain;">
                                    <?php else: ?>
                                        <span style="font-size:12px; opacity:0.5;"><?= e(__a('Yok', 'None')) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12px; font-weight:700; margin-bottom:4px;"><?= e(__a('Açık Zemin Logosu (Koyu Renk)', 'Light Surface Logo (Dark)')) ?></div>
                                    <input type="file" name="file_logo_light" accept=".svg,.png,.webp,.jpg,.jpeg" class="form-control" style="font-size:12px; padding:3px 6px;">
                                    <input type="text" name="brand_logo_light" value="<?= e($b_logo_light) ?>" class="form-control" style="font-size:12px; padding:3px 6px; margin-top:4px;" placeholder="assets/brand/symbol-light.svg">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="padding:10px 16px; border-top:1px solid var(--border-subtle); background:var(--bg-surface-2); display:flex; gap:8px;">
                        <button type="button" class="btn btn-secondary btn-sm" style="flex:1; justify-content:center; font-size:12px;" onclick="triggerSettingsBrandAction('reset_logo')"><?= e(__a('Varsayılana Dön', 'Reset Default')) ?></button>
                        <button type="button" class="btn btn-danger btn-sm" style="flex:1; justify-content:center; font-size:12px;" onclick="triggerSettingsBrandAction('remove_logo')"><?= e(__a('Logoyu Kaldır', 'Remove Logo')) ?></button>
                    </div>
                </div>

                <!-- KART 3: FAVICON & PAYLAŞIM GÖRSELİ -->
                <div style="border:1px solid var(--border-card); border-radius:10px; background:var(--bg-surface); display:flex; flex-direction:column; justify-content:space-between; overflow:hidden;">
                    <div>
                        <div style="padding:12px 16px; border-bottom:1px solid var(--border-subtle); background:var(--bg-surface-2); display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div>
                                <div style="font-weight:700; font-size:14px; color:var(--text-main);"><?= e(__a('3. Favicon & Sosyal Paylaşım İkonu', '3. Favicon & Social Share Image')) ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= e(__a('Sekme ikonu ve WhatsApp/X önizleme görseli', 'Browser tab icon & social preview image')) ?></div>
                            </div>
                            <label class="legal-switch-wrap">
                                <input type="checkbox" name="brand_favicon_enabled" value="1" <?= $b_favicon_enabled === '1' ? 'checked' : '' ?>>
                                <span class="legal-switch-slider"></span>
                                <span class="legal-badge-pill <?= $b_favicon_enabled === '1' ? 'badge-state-on' : 'badge-state-off' ?>">
                                    <?= $b_favicon_enabled === '1' ? e(__a('Aktif', 'On')) : e(__a('Kapalı', 'Off')) ?>
                                </span>
                            </label>
                        </div>

                        <div style="padding:14px 16px; display:flex; flex-direction:column; gap:12px;">
                            <!-- Favicon Kutu -->
                            <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:6px; padding:10px 12px; display:flex; align-items:center; gap:12px;">
                                <div style="width:48px; height:48px; border-radius:6px; background:var(--bg-surface); border:1px solid var(--border-light); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <?php if ($b_favicon_enabled === '1' && !empty($b_favicon)): ?>
                                        <img src="<?= e(url($b_favicon)) ?>" alt="Favicon" style="width:26px; height:26px; object-fit:contain;">
                                    <?php else: ?>
                                        <span style="font-size:12px; color:var(--text-muted);"><?= e(__a('Yok', 'None')) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12px; font-weight:700; margin-bottom:4px; color:var(--text-main);"><?= e(__a('Tarayıcı Sekme İkonu (Favicon)', 'Browser Tab Icon (Favicon)')) ?></div>
                                    <input type="file" name="file_favicon" accept=".png,.svg,.ico,.webp,.jpg,.jpeg" class="form-control" style="font-size:12px; padding:3px 6px;">
                                    <input type="text" name="brand_favicon" value="<?= e($b_favicon) ?>" class="form-control" style="font-size:12px; padding:3px 6px; margin-top:4px;" placeholder="assets/brand/favicon.png">
                                </div>
                            </div>

                            <!-- Varsayılan Sosyal Paylaşım Görseli (OG Image) -->
                            <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:6px; padding:10px 12px; display:flex; align-items:center; gap:12px;">
                                <div style="width:48px; height:48px; border-radius:6px; background:var(--bg-surface); border:1px solid var(--border-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden;">
                                    <?php if (!empty($b_og_image)): ?>
                                        <img src="<?= e(url($b_og_image)) ?>" alt="OG Share" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <span style="font-size:12px; color:var(--text-muted);">OG</span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12px; font-weight:700; margin-bottom:4px; color:var(--text-main);"><?= e(__a('Sosyal Medya Paylaşım Kapağı (OG Image)', 'Social Share Preview Image (OG)')) ?></div>
                                    <input type="file" name="file_og_image" accept=".jpg,.jpeg,.png,.webp" class="form-control" style="font-size:12px; padding:3px 6px;">
                                    <input type="text" name="default_og_image" value="<?= e($b_og_image) ?>" class="form-control" style="font-size:12px; padding:3px 6px; margin-top:4px;" placeholder="uploads/share.jpg">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="padding:10px 16px; border-top:1px solid var(--border-subtle); background:var(--bg-surface-2); display:flex; gap:8px;">
                        <button type="button" class="btn btn-secondary btn-sm" style="flex:1; justify-content:center; font-size:12px;" onclick="triggerSettingsBrandAction('reset_favicon')"><?= e(__a('Varsayılana Dön', 'Reset Default')) ?></button>
                        <button type="button" class="btn btn-danger btn-sm" style="flex:1; justify-content:center; font-size:12px;" onclick="triggerSettingsBrandAction('remove_favicon')"><?= e(__a('Favicon\'u Kaldır', 'Remove Favicon')) ?></button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ── 3. SAYFALAR & ÖZELLİKLER ── -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section class="st-panel" id="sayfalar" data-tab="sayfalar" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e(__a('Hangi Sayfalar Yayında?', 'Which Pages Are Live?')) ?></h2>
                <p class="card-sub"><?= e(__a('Kapalı sayfalar menüden kalkar ve ziyaretçilere gösterilmez. Anahtarlar anında kaydedilir.', 'Hidden pages are removed from the menu and not shown to visitors. Switches save instantly.')) ?></p>
            </div>
        </div>
        <div class="setting-rows">
            <div class="setting-row">
                <div class="setting-row-text">
                    <div class="setting-row-title"><?= e($blog_nav_title) ?></div>
                    <div class="setting-row-desc"><?= e(__a('Yazılar, saha notları ve haberler', 'Articles, field notes and news')) ?> · <a href="blog.php"><?= e(__a('Yazıları yönet', 'Manage posts')) ?></a> · <a href="templates.php?design=blog"><?= e(__a('Görünümü düzenle', 'Edit design')) ?></a></div>
                </div>
                <?= st_switch('blog_enabled', $blog_enabled, 'modBadgeBlog', $L_live, $L_hidden) ?>
            </div>
            <div class="setting-row">
                <div class="setting-row-text">
                    <div class="setting-row-title"><?= e($contact_nav_title) ?></div>
                    <div class="setting-row-desc"><?= e(__a('İletişim formu ve iletişim bilgileri', 'Contact form and details')) ?> · <a href="messages.php"><?= e(__a('Gelen mesajlar', 'Messages')) ?></a> · <a href="contact-content.php"><?= e(__a('Metinleri düzenle', 'Edit texts')) ?></a> · <a href="templates.php?design=contact"><?= e(__a('Görünümü düzenle', 'Edit design')) ?></a></div>
                </div>
                <?= st_switch('contact_page_enabled', $contact_enabled, 'modBadgeContact', $L_live, $L_hidden) ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e(__a('Fotoğraf Görüntüleme & Koruma', 'Photo Viewing & Protection')) ?></h2>
                <p class="card-sub"><?= e(__a('Fotoğraflara tıklandığında açılan tam ekran görüntüleyici ve kopyalama koruması. Anahtarlar anında kaydedilir.', 'The fullscreen viewer that opens when a photo is clicked, and copy protection. Switches save instantly.')) ?></p>
            </div>
        </div>
        <div class="setting-rows">
            <div class="setting-row">
                <div class="setting-row-text">
                    <div class="setting-row-title"><?= e(__a('Sağ Tık & Sürükleme Koruması', 'Right-Click & Drag Protection')) ?></div>
                    <div class="setting-row-desc"><?= e(__a('Fotoğrafların sağ tıkla kaydedilmesini ve sürüklenmesini engeller, telif (©) uyarısı gösterir.', 'Blocks right-click saving and dragging of photos and shows a copyright (©) notice.')) ?></div>
                </div>
                <?= st_switch('image_protection_enabled', $image_protection_enabled, 'modBadgeImgProt', $L_on, $L_off, 'imageProtToggle') ?>
            </div>
            <div class="setting-row">
                <div class="setting-row-text">
                    <div class="setting-row-title"><?= e(__a('Tam Ekran Görüntüleyici (Lightbox)', 'Fullscreen Viewer (Lightbox)')) ?></div>
                    <div class="setting-row-desc"><?= e(__a('Kapalıyken fotoğraflara tıklamak büyük görünüm açmaz.', 'When off, clicking a photo does not open a large view.')) ?></div>
                </div>
                <?= st_switch('inner_enable_lightbox', $lb_enabled, 'lbMasterBadge', $L_on, $L_off) ?>
            </div>
            <div class="setting-row setting-row-stack">
                <div class="setting-row-text">
                    <div class="setting-row-title"><?= e(__a('Görüntüleyicide Gösterilecek Düğmeler', 'Buttons Shown in the Viewer')) ?></div>
                </div>
                <div class="check-grid">
                    <?php
                    $lb_checks = [
                        'lightbox_show_counter'    => [$lb_counter,    __a('Fotoğraf sayacı (01 / 18)', 'Photo counter (01 / 18)')],
                        'lightbox_show_slideshow'  => [$lb_slideshow,  __a('Slayt gösterisi (oynat / durdur)', 'Slideshow (play / pause)')],
                        'lightbox_show_zoom'       => [$lb_zoom,       __a('Yakınlaştırma', 'Zoom')],
                        'lightbox_show_fullscreen' => [$lb_fullscreen, __a('Tam ekran düğmesi', 'Fullscreen button')],
                        'lightbox_show_exif'       => [$lb_exif,       __a('Kamera & EXIF bilgisi', 'Camera & EXIF info')],
                    ];
                    foreach ($lb_checks as $lk => [$lv, $ll]): ?>
                    <label class="check-chip">
                        <input type="checkbox" <?= $lv ? 'checked' : '' ?> onchange="toggleSiteModule('<?= e($lk) ?>', this.checked, null, null)">
                        <span><?= e($ll) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-hint-card">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
        <span><?= e(__a('Sayfaların düzenini, renklerini, üst menü ve alt bilgi tasarımını değiştirmek için:', 'To change page layouts, colors, header and footer design:')) ?> <a href="templates.php"><?= e(__a('Temalar', 'Themes')) ?> →</a></span>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ── 4. GİZLİLİK & KVKK ── -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section class="st-panel" id="gizlilik" data-tab="gizlilik" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e(__a('Yasal Metinler', 'Legal Pages')) ?></h2>
                <p class="card-sub"><?= e(__a('KVKK Aydınlatma Metni ve Açık Rıza Metni sayfaları. Açıkken alt bilgide bağlantıları görünür.', 'Privacy Notice and Explicit Consent pages. When on, links appear in the footer.')) ?></p>
            </div>
            <?= st_switch('legal_pages_enabled', $legal_pages_enabled, 'legalStatusBadge', $L_live, $L_hidden, 'legalPagesToggle') ?>
        </div>
        <div class="setting-rows">
            <div class="setting-row">
                <div class="setting-row-text">
                    <div class="setting-row-title"><?= e(__a('Aydınlatma Metni', 'Privacy Notice')) ?></div>
                </div>
                <a href="legal.php?doc=aydinlatma&lang=tr" class="btn btn-secondary btn-sm"><?= e(__a('Metni Düzenle', 'Edit Text')) ?> →</a>
            </div>
            <div class="setting-row">
                <div class="setting-row-text">
                    <div class="setting-row-title"><?= e(__a('Açık Rıza Metni', 'Explicit Consent')) ?></div>
                </div>
                <a href="legal.php?doc=acik_riza&lang=tr" class="btn btn-secondary btn-sm"><?= e(__a('Metni Düzenle', 'Edit Text')) ?> →</a>
            </div>
        </div>
    </div>

<?php
require_once __DIR__ . '/../includes/cookie_consent.php';
$cc_enabled  = cookie_consent_enabled();
$cc_position = get_setting('cookie_banner_position', 'box');
$cc_version  = cookie_consent_version();
$cc_ga_id    = trim(get_setting('google_analytics', ''));
$cc_fields   = cookie_consent_fields();
$cc_labels   = [
    'title'          => __a('Başlık', 'Title'),
    'message'        => __a('Bilgilendirme Metni', 'Message'),
    'accept'         => __a('"Kabul Et" Butonu', '"Accept" Button'),
    'reject'         => __a('"Reddet" Butonu', '"Reject" Button'),
    'customize'      => __a('"Tercihleri Yönet" Butonu', '"Manage Preferences" Button'),
    'save'           => __a('"Tercihleri Kaydet" Butonu', '"Save Preferences" Button'),
    'necessary'      => __a('Zorunlu Çerezler Başlığı', 'Necessary Cookies Title'),
    'necessary_desc' => __a('Zorunlu Çerezler Açıklaması', 'Necessary Cookies Description'),
    'always_on'      => __a('"Her zaman aktif" Etiketi', '"Always active" Label'),
    'analytics'      => __a('Analitik Çerezler Başlığı', 'Analytics Cookies Title'),
    'analytics_desc' => __a('Analitik Çerezler Açıklaması', 'Analytics Cookies Description'),
    'policy'         => __a('Politika Bağlantı Metni', 'Policy Link Text'),
];
?>
<div class="card" id="cerez-onayi">
    <form method="POST" action="settings.php#cerez-onayi" data-autosave>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save_cookie_consent">

        <div class="card-header" style="padding: 14px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 class="card-title" style="font-size: 14px; margin:0; display:flex; align-items:center; gap:8px;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--warning-text);"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><circle cx="8.5" cy="8.5" r="1"/><circle cx="16" cy="15.5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="11" cy="17" r="1"/><circle cx="7" cy="14" r="1"/></svg>
                    <span><?= e(__a('Çerez Onayı (KVKK / GDPR)', 'Cookie Consent (KVKK / GDPR)')) ?></span>
                </h2>
                <span style="font-size: 12px; color: var(--text-muted);"><?= e(__a('Site ilk açıldığında ziyaretçiye çerez tercihini soran bildirimi gösterir. Tüm aktif dillerde çalışır.', 'Shows a banner asking visitors for their cookie choice on first visit. Works in all active languages.')) ?></span>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= e(__a('Kaydet', 'Save')) ?></span>
            </button>
        </div>

        <div class="card-body" style="padding: 18px 20px; display:flex; flex-direction:column; gap:18px;">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:16px;">
                <div style="padding:16px; border:1px solid var(--border-color); border-radius:10px; background:var(--bg-body); display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                    <div>
                        <div style="font-weight:700; font-size:14px; color:var(--text-main);"><?= e(__a('Çerez Onay Bildirimi', 'Cookie Consent Banner')) ?></div>
                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px;"><?= e(__a('Açıkken Google Analytics yalnızca ziyaretçi onay verirse yüklenir.', 'When on, Google Analytics loads only after the visitor consents.')) ?></div>
                    </div>
                    <label class="legal-switch-wrap">
                        <input type="checkbox" name="cookie_consent_enabled" value="1" id="cookieConsentToggle" <?= $cc_enabled ? 'checked' : '' ?> onchange="toggleSiteModule('cookie_consent_enabled', this.checked, 'modBadgeCookie', null); syncCookieToggles(this.checked);">
                        <span class="legal-switch-slider"></span>
                        <span id="modBadgeCookie" class="legal-badge-pill <?= $cc_enabled ? 'badge-state-on' : 'badge-state-off' ?>">
                            <?= $cc_enabled ? e(__a('Aktif', 'Active')) : e(__a('Pasif', 'Off')) ?>
                        </span>
                    </label>
                </div>

                <div style="padding:16px; border:1px solid var(--border-color); border-radius:10px; background:var(--bg-body);">
                    <label class="form-label" for="cookieBannerPosition" style="font-size:13px;"><?= e(__a('Görünüm', 'Layout')) ?></label>
                    <select name="cookie_banner_position" id="cookieBannerPosition" class="form-control" style="font-size:13px;">
                        <option value="box" <?= $cc_position !== 'bar' ? 'selected' : '' ?>><?= e(__a('Sol alt köşede kutu', 'Box in bottom-left corner')) ?></option>
                        <option value="bar" <?= $cc_position === 'bar' ? 'selected' : '' ?>><?= e(__a('Sayfa altında tam genişlik şerit', 'Full-width bar at the bottom')) ?></option>
                    </select>
                </div>
            </div>

            <div style="padding:12px 14px; border-radius:10px; background:var(--bg-body); border:1px dashed var(--border-color); font-size:12px; color:var(--text-muted); line-height:1.6;">
                <strong style="color:var(--text-main);"><?= e(__a('Nasıl çalışır?', 'How it works')) ?></strong><br>
                <?= e(__a('• "Kabul Et" ve "Reddet" eşit görünür; analitik tercih kutusu varsayılan olarak kapalıdır (KVKK Çerez Rehberi).', '• "Accept" and "Reject" have equal weight; the analytics switch is off by default (KVKK Cookie Guide / GDPR).')) ?><br>
                <?= e(__a('• Ziyaretçinin kararı 180 gün saklanır. Footer\'daki "Çerez Tercihleri" bağlantısından istediği zaman değiştirebilir.', '• The visitor\'s choice is kept for 180 days and can be changed anytime via the "Cookie Preferences" footer link.')) ?><br>
                <?= e(__a('• Google Analytics: ', '• Google Analytics: ')) ?><code><?= $cc_ga_id !== '' ? e($cc_ga_id) : e(__a('tanımlı değil', 'not set')) ?></code>
                · <?= e(__a('Onay sürümü: ', 'Consent version: ')) ?><code>v<?= (int)$cc_version ?></code>
                <br><?= e(__a('• Boş bıraktığınız metin alanlarında her dil için hazır çeviri kullanılır.', '• Empty text fields use the built-in translation for each language.')) ?>
            </div>

            <?php foreach ($site_languages as $idx => $sl):
                $lc = $sl['code'];
                $defaults = [];
                foreach ($cc_fields as $ck => [$dtr, $den]) {
                    $defaults[$ck] = site_t($dtr, $den, $lc);
                }
            ?>
            <details <?= $idx === 0 ? 'open' : '' ?> style="border:1px solid var(--border-color); border-radius:10px; background:var(--bg-body);">
                <summary style="padding:12px 16px; cursor:pointer; font-weight:700; font-size:14px; color:var(--text-main);">
                    <?= e(($sl['flag'] ?? '🌐') . ' ' . $sl['name_native']) ?> <span style="font-weight:500; color:var(--text-muted); font-size:12px;">(<?= e(strtoupper($lc)) ?>) — <?= e(__a('Bildirim Metinleri', 'Banner Texts')) ?></span>
                </summary>
                <div style="padding:4px 16px 16px; display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
                    <?php foreach ($cc_fields as $ck => $_unused):
                        $fname = 'cookie_banner_' . $ck . '_' . $lc;
                        $is_long = in_array($ck, ['message', 'necessary_desc', 'analytics_desc'], true);
                    ?>
                    <div style="<?= $ck === 'message' ? 'grid-column:1 / -1;' : '' ?>">
                        <label class="form-label" style="font-size:12px;"><?= e($cc_labels[$ck] ?? $ck) ?></label>
                        <?php if ($is_long): ?>
                            <textarea name="<?= e($fname) ?>" class="form-control" rows="<?= $ck === 'message' ? 3 : 2 ?>" style="font-size:13px;" placeholder="<?= e($defaults[$ck]) ?>"><?= e(get_setting($fname, '')) ?></textarea>
                        <?php else: ?>
                            <input type="text" name="<?= e($fname) ?>" class="form-control" style="font-size:13px;" placeholder="<?= e($defaults[$ck]) ?>" value="<?= e(get_setting($fname, '')) ?>">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div style="grid-column:1 / -1;">
                        <label class="form-label" style="font-size:12px;"><?= e(__a('Politika Bağlantısı (boşsa Aydınlatma Metni sayfası)', 'Policy Link (defaults to Privacy Notice page)')) ?></label>
                        <input type="text" name="cookie_banner_policy_url_<?= e($lc) ?>" class="form-control" style="font-size:13px;" placeholder="<?= e(cookie_consent_policy_url($lc) ?: '/' . $lc . '/...') ?>" value="<?= e(get_setting('cookie_banner_policy_url_' . $lc, '')) ?>">
                    </div>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </form>
    <div style="padding: 0 20px 18px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <span style="font-size:12px; color:var(--text-muted);"><?= e(__a('Metinlerde ya da çerez kullanımında önemli bir değişiklik yaptıysanız, daha önce karar vermiş ziyaretçilere bildirimi tekrar gösterebilirsiniz.', 'If you changed the texts or how cookies are used, you can show the banner again to visitors who already decided.')) ?></span>
        <form method="POST" action="settings.php#cerez-onayi" data-confirm="<?= e(__a('Bütün ziyaretçilere çerez bildirimi yeniden gösterilsin mi?', 'Show the cookie banner to all visitors again?')) ?>" data-confirm-ok="<?= e(__a('Yeniden göster', 'Show again')) ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="reset_cookie_consent">
            <button type="submit" class="btn btn-secondary btn-sm"><?= e(__a('Herkesten Yeniden Onay İste', 'Ask Everyone Again')) ?></button>
        </form>
    </div>
</div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ── 5. ENTEGRASYONLAR ── -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section class="st-panel" id="entegrasyonlar" data-tab="entegrasyonlar" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e(__a('Google Hizmetleri', 'Google Services')) ?></h2>
                <p class="card-sub"><?= e(__a('Ziyaretçi istatistikleri ve harita için kullanılan anahtarlar. Emin değilseniz değiştirmeyin.', 'Keys used for visitor statistics and maps. Leave them as they are if unsure.')) ?></p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="settings.php#entegrasyonlar" data-autosave>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="general">
                <input type="hidden" name="section" value="integrations">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label"><?= e(__a('Google Analytics Ölçüm Kimliği', 'Google Analytics Measurement ID')) ?></label>
                        <input type="text" name="google_analytics" class="form-control" value="<?= e(get_setting('google_analytics', '')) ?>" placeholder="G-XXXXXXXXXX">
                        <p class="form-hint"><?= e(__a('Siteye ziyaretçi takibi ekler. Çerez onayı açıksa yalnızca onay verenlerde çalışır.', 'Adds visitor tracking. If cookie consent is on, it only runs for visitors who accept.')) ?></p>
                    </div>
                </div>
                <div class="setting-row" style="padding:12px 14px; margin:0 0 16px; border:1px solid var(--border-subtle); border-radius:10px;">
                    <div class="setting-row-text">
                        <div class="setting-row-title"><?= e(__a('İstatistikler Bağlantısı (GA4 Mülk ID & Service Account)', 'Statistics Connection (GA4 Property ID & Service Account)')) ?></div>
                        <div class="setting-row-desc"><?= e(__a('Paneldeki İstatistikler sayfasının Google’dan veri çekmesi için. Bağlantı testi ve JSON dosyası yükleme ile birlikte orada ayarlanır.', 'Lets the Statistics page fetch data from Google. Set up there, with a connection test and JSON file upload.')) ?></div>
                    </div>
                    <a href="stats.php#baglanti" class="btn btn-secondary btn-sm"><?= e(__a('Bağlantı Ayarları', 'Connection Settings')) ?> →</a>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(__a('Google Haritalar API Anahtarı', 'Google Maps API Key')) ?></label>
                    <input type="text" name="google_maps_api_key" class="form-control" placeholder="AIzaSy..." value="<?= e(get_setting('google_maps_api_key')) ?>">
                    <p class="form-hint"><?= e(__a('İletişim sayfasındaki harita için.', 'For the map on the contact page.')) ?></p>
                </div>
                <div class="settings-save-bar">
                    <button type="submit" class="btn btn-primary"><?= e(__a('Kaydet', 'Save')) ?></button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ── 6. HESAP & YEDEK ── -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section class="st-panel" id="hesap" data-tab="hesap" role="tabpanel">
    <div class="settings-two-col">
        <!-- Parola Değiştirme & Güvenlik -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= e(__a('settings_password_title', 'Yönetici Parolası & Güvenlik')) ?></h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="password">

                    <div class="form-group">
                        <label class="form-label"><?= e(__a('settings_curr_pass', 'Mevcut Parola')) ?></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div class="form-group">
                            <label class="form-label"><?= e(__a('settings_new_pass', 'Yeni Parola')) ?></label>
                            <input type="password" name="new_password" class="form-control" required minlength="10">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= e(__a('settings_new_pass_confirm', 'Yeni Parola (Tekrar)')) ?></label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="10">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary" style="margin-top:6px;"><?= e(__a('settings_update_pass_btn', 'Parolayı Güncelle')) ?></button>
                </form>
            </div>
        </div>
        <!-- Veritabanı Yedekleme & Görsel Araçları -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= e(__a('settings_backup_title', 'Veritabanı Yedekleme & Medya Optimizasyonu')) ?></h2>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:12px;">
                <div style="background:var(--bg-body); border:1px solid var(--border-color); border-radius:10px; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <div>
                        <div style="font-weight:700; font-size:13px; color:var(--text-main);">site.db (SQLite)</div>
                        <div style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono); margin-top:2px;">
                            <?php 
                            $db_path = dirname(__DIR__) . '/site.db';
                            $db_size = file_exists($db_path) ? number_format(filesize($db_path) / 1024, 1) . ' KB' : 'Bulunamadı';
                            ?>
                            <?= e(__a('size', 'Boyut')) ?>: <?= $db_size ?>
                        </div>
                    </div>
                    <a href="settings.php?download=sqlite" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        <span><?= e(__a('settings_download_backup_btn', 'Yedeği İndir')) ?></span>
                    </a>
                </div>

                <div style="background:var(--bg-body); border:1px solid var(--border-color); border-radius:10px; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <div>
                        <div style="font-weight:700; font-size:13px; color:var(--text-main);"><?= e(__a('Görsel Dönüştürücü (AVIF / WebP)', 'Image Converter (AVIF / WebP)')) ?></div>
                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                            <?= e(__a('Yüklenen fotoğrafların yeni nesil formatlarını ve küçük boyutlarını üretir.', 'Generates next-gen AVIF/WebP formats and responsive thumbnails.')) ?>
                        </div>
                    </div>
                    <a href="convert-images.php" class="btn btn-secondary btn-sm">
                        <?= e(__a('Dönüştürücüyü Aç →', 'Open Converter →')) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="settings-hint-card">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
        <span><?= e(__a('Panel dili ve site dilleri:', 'Panel language and site languages:')) ?> <a href="languages.php"><?= e(__a('Diller & Çeviri', 'Languages & Translation')) ?> →</a></span>
    </div>
</section>

<script>
function triggerSettingsBrandAction(act) {
    var inp = document.getElementById('settingsBrandQuickAction');
    var frm = document.getElementById('brandingSettingsForm');
    if (inp && frm) {
        inp.value = act;
        frm.submit();
    }
}

function switchSeoLang(lang) {
    document.querySelectorAll('.seo-lang-tab').forEach(function(b) { b.classList.remove('active'); });
    var btn = document.getElementById('seoTabBtn_' + lang);
    if (btn) btn.classList.add('active');

    document.querySelectorAll('.seo-lang-pane').forEach(function(p) { p.style.display = 'none'; });
    var pane = document.getElementById('seoPane_' + lang);
    if (pane) pane.style.display = 'block';
}

function toggleSiteModule(moduleKey, isChecked, badgeId, cardBadgeId) {
    var badge = document.getElementById(badgeId);
    var cardBadge = cardBadgeId ? document.getElementById(cardBadgeId) : null;
    var isEn = (window.ADMIN_LANG === 'en');

    if (badge) {
        badge.className = 'legal-badge-pill ' + (isChecked ? 'badge-state-on' : 'badge-state-off');
        var onTxt  = badge.getAttribute('data-on')  || (<?= json_encode(__a('Açık', 'On'), JSON_UNESCAPED_UNICODE) ?>);
        var offTxt = badge.getAttribute('data-off') || (<?= json_encode(__a('Kapalı', 'Off'), JSON_UNESCAPED_UNICODE) ?>);
        badge.innerText = isChecked ? onTxt : offTxt;
    }
    if (cardBadge) {
        cardBadge.className = 'badge ' + (isChecked ? 'badge-published' : 'badge-draft');
        cardBadge.innerText = isChecked ? (<?= json_encode(__a('Açık', 'Active'), JSON_UNESCAPED_UNICODE) ?>) : (<?= json_encode(__a('Kapalı', 'Hidden'), JSON_UNESCAPED_UNICODE) ?>);
    }

    var formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('action', 'toggle_module');
    formData.append('ajax', '1');
    formData.append('module_key', moduleKey);
    formData.append('enabled', isChecked ? '1' : '0');

    fetch('settings.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (!data || !data.success) {
            alert(<?= json_encode(__a('Ayar kaydedilirken bir hata oluştu.', 'Failed to save setting.'), JSON_UNESCAPED_UNICODE) ?>);
            return;
        }
        if (window.adminToast) window.adminToast();
    })
    .catch(function() {
        alert(<?= json_encode(__a('Bağlantı hatası oluştu.', 'Connection error.'), JSON_UNESCAPED_UNICODE) ?>);
    });
}

// Çerez kartındaki anahtar ile rozetini senkron tut
function syncCookieToggles(on) {
    var b = document.getElementById('modBadgeCookie');
    if (!b) return;
    var en = (window.ADMIN_LANG === 'en');
    b.className = 'legal-badge-pill ' + (on ? 'badge-state-on' : 'badge-state-off');
    b.innerText = on ? (en ? 'Active' : 'Aktif') : (en ? 'Off' : 'Pasif');
}

// ── Sekmeler: adres çubuğundaki #bölüm ile çalışır (kaydettikten sonra aynı sekmeye döner) ──
(function () {
    var tabs = document.querySelectorAll('.st-tab[data-tab]');
    var panels = document.querySelectorAll('.st-panel');
    if (!tabs.length) return;
    function activate(tabId, target) {
        var found = false;
        panels.forEach(function (p) {
            var on = p.getAttribute('data-tab') === tabId;
            p.classList.toggle('is-active', on);
            if (on) found = true;
        });
        if (!found) return false;
        tabs.forEach(function (t) {
            var on = t.getAttribute('data-tab') === tabId;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        try { sessionStorage.setItem('st_tab', tabId); } catch (e) {}
        // Tarayıcının kendi çapa kaydırmasından sonra çalışsın
        setTimeout(function () {
            if (target && !target.classList.contains('st-panel')) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                window.scrollTo(0, 0);
            }
        }, 40);
        return true;
    }
    function fromHash() {
        var id = decodeURIComponent((location.hash || '').replace('#', ''));
        var el = id ? document.getElementById(id) : null;
        var panel = el ? el.closest('.st-panel') : null;
        if (panel) return activate(panel.getAttribute('data-tab'), el);
        var saved = null;
        try { saved = sessionStorage.getItem('st_tab'); } catch (e) {}
        if (!saved || !activate(saved, null)) activate(tabs[0].getAttribute('data-tab'), null);
    }
    tabs.forEach(function (t) {
        t.addEventListener('click', function (ev) {
            ev.preventDefault();
            var id = t.getAttribute('data-tab');
            history.replaceState(null, '', '#' + id);
            activate(id, null);
        });
    });
    window.addEventListener('hashchange', fromHash);
    fromHash();
})();

// ── Anında kaydetme: data-autosave formlarında her değişiklik arka planda kaydedilir ──
// Sunucu her zamanki gibi kaydedip settings.php'ye yönlendirir; yanıtta hata bildirimi varsa o gösterilir.
(function () {
    var EN = window.ADMIN_LANG === 'en';
    document.querySelectorAll('form[data-autosave]').forEach(function (f) {
        f.setAttribute('data-no-dirty', '');
        f.querySelectorAll('.settings-save-bar').forEach(function (b) { b.hidden = true; });
        var timer = null, last = new URLSearchParams(new FormData(f)).toString(), busy = Promise.resolve();
        function save() {
            clearTimeout(timer);
            var body = new FormData(f), sig = new URLSearchParams(body).toString();
            if (sig === last) return;
            last = sig;
            busy = busy.then(function () {
                return fetch(f.getAttribute('action') || 'settings.php', { method: 'POST', body: body, credentials: 'same-origin' })
                    .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
                    .then(function (html) {
                        var doc = new DOMParser().parseFromString(html, 'text/html'), err = doc.querySelector('.flash-alert.flash-error');
                        if (err) { last = ''; adminToast(err.textContent.trim(), 'error'); } else adminToast(<?= json_encode(__a('Kaydedildi', 'Saved'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                    })
                    .catch(function () { last = ''; adminToast(EN ? 'Could not save. Check your connection.' : <?= json_encode(__a('Kaydedilemedi. Bağlantınızı kontrol edin.', 'Could not save. Check your connection.'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 'error'); });
            });
        }
        f.addEventListener('input', function (e) {
            var t = e.target;
            if (t.type === 'checkbox' || t.type === 'radio' || t.tagName === 'SELECT' || t.type === 'date') return;
            clearTimeout(timer);
            timer = setTimeout(save, 900);
        });
        f.addEventListener('change', save);
        f.addEventListener('submit', function (e) { e.preventDefault(); save(); });
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
