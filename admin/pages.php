<?php
/**
 * admin/pages.php
 * Sayfalar: menü ağacı, sayfa ayarları ve canlı önizleme (Format "Pages" ekranı gibi).
 *
 * - Solda menüdeki ve menüde olmayan sayfalar; liste sayfaları (kategoriler) klasör gibi açılıp
 *   içindeki projeleri gösterir. Menü sırası ve klasör içi sıra sürükle-bırakla değişir (api-reorder.php).
 * - Proje ayarları: başlık, görünürlük, parola, adres (benzersizlik + eski adres yönlendirmesi), kısa açıklama (SEO).
 * - Liste sayfası ayarları: ad, açıklama, menüde görünürlük (menus ile eşzamanlı).
 * - Fotoğraflar ve uzun içerik item-edit.php'de; tasarım Temalar › Özelleştir'de düzenlenir.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/lang_bar.php';
require_once __DIR__ . '/../includes/design_editor.php';

require_login();

$admin_title     = __a('Sayfalar', 'Pages');
$breadcrumb_root = __a('İçerik', 'Content');
$breadcrumb_page = __a('Sayfalar', 'Pages');

$pdo = DB::getConnection();

// Sitede etkin diller (sıra: Diller sayfasındaki sıra). Ekstra dillerin sütunları yoksa eklenir.
$pg_langs = array_column(get_active_languages(), 'code');
foreach ($pg_langs as $pgc) ensure_language_columns($pgc);

/** JSON yanıt + çıkış */
function pages_json(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Sabit sayfalar: anahtar → [ad tr, ad en, düzenleme sayfası, tasarım bölümü, açık mı] */
function pages_static_defs(): array {
    $defs = [
        'about'            => ['Hakkımda', 'About', 'about.php', 'about', true],
        'contact'          => ['İletişim', 'Contact', 'contact-content.php', 'contact', get_setting('contact_page_enabled', '0') === '1'],
        'blog'             => ['Blog', 'Blog', 'blog.php', 'blog', get_setting('blog_enabled', '0') === '1'],
        'field_work'       => ['Saha rotaları', 'Field work', 'about.php', '', true],
        'privacy_notice'   => ['Aydınlatma metni', 'Privacy notice', 'legal.php', '', true],
        'explicit_consent' => ['Açık rıza metni', 'Explicit consent', 'legal.php', '', true],
    ];
    if (!field_map_enabled()) unset($defs['field_work']); // siteye özel modül
    return $defs;
}

/**
 * Bir adres başka bir sayfa tarafından kullanılıyor mu? Kullanılıyorsa o sayfanın adı.
 * Proje adresleri, liste sayfaları ve sabit sayfalarla aynı /dil/adres/ alanını paylaşır;
 * site bir projeyi her iki dildeki adresiyle de bulduğu için iki dil birlikte denetlenir.
 */
function pages_slug_owner(PDO $pdo, string $slug, int $except_item): ?string {
    $codes = array_column(get_active_languages(), 'code');
    foreach (pages_static_defs() as $k => $d) {
        foreach ($codes as $l) {
            if (get_static_slug($k, $l) === $slug) return __a($d[0], $d[1]);
        }
    }
    $cat_cols = implode(' OR ', array_map(fn($c) => "`slug_{$c}` = ?", $codes));
    $st = $pdo->prepare("SELECT * FROM `categories` WHERE {$cat_cols} LIMIT 1");
    $st->execute(array_fill(0, count($codes), $slug));
    if ($c = $st->fetch(PDO::FETCH_ASSOC)) return get_category_localized_name($c, $GLOBALS['admin_lang'] ?? 'tr');
    $item_cols = implode(' OR ', array_map(fn($c) => "`slug_{$c}` = ?", $codes));
    $st = $pdo->prepare("SELECT * FROM `items` WHERE `id` <> ? AND (`slug` = ? OR {$item_cols}) LIMIT 1");
    $st->execute(array_merge([$except_item, $slug], array_fill(0, count($codes), $slug)));
    if ($i = $st->fetch(PDO::FETCH_ASSOC)) return item_field($i, 'title', $GLOBALS['admin_lang'] ?? 'tr');
    return null;
}

/** Sayfa başına seçilebilen düzenler (Temalar › Proje sayfası alanları); "alan@item-<id>" olarak saklanır */
const PAGES_ITEM_TPL_FIELDS = ['inner_opening_layout', 'inner_stream_layout'];

/** Ayarlar panelinin proje alanları (bütün diller) */
function pages_item_fields(array $it): array {
    $f = ['status' => (string)($it['status'] ?: 'published'), 'access_password' => '', 'has_password' => ($it['access_password'] ?? '') !== ''];
    foreach (PAGES_ITEM_TPL_FIELDS as $tk) $f[$tk] = get_setting($tk . '@item-' . (int)$it['id'], '');
    $f['og_image'] = get_setting('item_og_image@item-' . (int)$it['id'], '');
    foreach (array_column(get_active_languages(), 'code') as $c) {
        $f['title_' . $c] = (string)($it['title_' . $c] ?? '');
        $f['slug_' . $c] = (string)($it['slug_' . $c] ?? '');
        $f['description_' . $c] = (string)($it['description_' . $c] ?? '');
        $f['seo_title_' . $c] = get_setting('item_seo_title_' . $c . '@item-' . (int)$it['id'], '');
        $f['url_' . $c] = item_url($it, $c);
    }
    return $f;
}

/** Ayarlar panelinin liste sayfası alanları (bütün diller) */
function pages_cat_fields(array $c): array {
    $f = [];
    foreach (array_column(get_active_languages(), 'code') as $l) {
        $f['name_' . $l] = (string)($c['name_' . $l] ?? '');
        $f['description_' . $l] = (string)($c['description_' . $l] ?? '');
        $f['slug_' . $l] = (string)(($c['slug_' . $l] ?? '') ?: $c['slug_tr']);
    }
    return $f;
}

// ── PROJE AYARLARI: KAYDET ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'pages_item_save') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) pages_json(['ok' => false, 'error' => __a('Güvenlik doğrulaması başarısız.', 'Security check failed.')], 400);
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM `items` WHERE `id` = ? LIMIT 1");
    $st->execute([$id]);
    $item = $st->fetch(PDO::FETCH_ASSOC);
    if (!$item) pages_json(['ok' => false, 'error' => __a('Sayfa bulunamadı.', 'Page not found.')], 404);

    $titles = $slugs = $descs = [];
    foreach ($pg_langs as $c) {
        $titles[$c] = mb_substr(trim((string)($_POST['title_' . $c] ?? '')), 0, 255);
        $descs[$c]  = mb_substr(trim((string)($_POST['description_' . $c] ?? '')), 0, 1000);
    }
    if (!array_filter($titles, 'strlen')) pages_json(['ok' => false, 'field' => 'title_' . $pg_langs[0], 'error' => __a('Başlık boş olamaz.', 'The title cannot be empty.')], 422);
    $status = in_array($_POST['status'] ?? '', ['published', 'unlisted', 'draft'], true) ? $_POST['status'] : 'published';
    $password = item_password_for_save(
        mb_substr(trim((string)($_POST['access_password'] ?? '')), 0, 100),
        (string)($item['access_password'] ?? ''),
        !empty($_POST['remove_access_password'])
    );
    $first_title = (string)current(array_filter($titles, 'strlen'));
    foreach ($pg_langs as $c) {
        // Yazılan adres, yoksa o dildeki başlık. Latin dışı alfabeler slugify ile okunur harflere çevrilir.
        $slugs[$c] = slugify((string)($_POST['slug_' . $c] ?? '')) ?: slugify($titles[$c]);
        // TR ve EN adresi her zaman olur (site ilk bu ikisine bakar); diğer diller boşsa yedek dilin adresi kullanılır
        if ($slugs[$c] === '' && in_array($c, ['tr', 'en'], true)) $slugs[$c] = slugify($first_title);
    }
    foreach ($slugs as $c => $sv) {
        if ($sv === '') {
            if (in_array($c, ['tr', 'en'], true)) pages_json(['ok' => false, 'field' => 'slug_' . $c, 'error' => __a('Geçerli bir adres yazın.', 'Enter a valid address.')], 422);
            continue;
        }
        if ($owner = pages_slug_owner($pdo, $sv, $id)) {
            pages_json(['ok' => false, 'field' => 'slug_' . $c, 'error' => sprintf(__a('“/%s/” adresini “%s” kullanıyor. Başka bir adres yazın.', '“/%s/” is already used by “%s”. Choose another address.'), $sv, $owner)], 422);
        }
    }

    // Adres değiştiyse eski adres yönlendirmeye eklenir (index.php aliases → 301)
    $new_all = array_values(array_filter($slugs, 'strlen'));
    $aliases = array_filter(array_map('trim', explode(',', (string)($item['aliases'] ?? ''))));
    foreach ($slugs as $l => $new) {
        $old = (string)($item['slug_' . $l] ?? '');
        if ($old !== '' && $old !== $new && !in_array($old, $new_all, true)) {
            foreach (['/' . $old . '/', '/' . $l . '/' . $old . '/'] as $al) {
                if (!in_array($al, $aliases, true)) $aliases[] = $al;
            }
        }
    }
    // Yeni adres eski bir yönlendirmede duruyorsa oradan çıkar
    $taken = [];
    foreach ($slugs as $l => $sv) { if ($sv !== '') { $taken[] = $sv; $taken[] = $l . '/' . $sv; } }
    $aliases = array_values(array_filter($aliases, fn($al) => !in_array(trim($al, '/'), $taken, true)));

    $set = ['`status` = ?', '`access_password` = ?', '`slug` = ?', '`aliases` = ?', '`updated_at` = ?'];
    $vals = [$status, $password, ($slugs['en'] ?? '') ?: (($slugs['tr'] ?? '') ?: (string)current($new_all)), implode(',', $aliases), date('Y-m-d H:i:s')];
    foreach ($pg_langs as $c) {
        array_push($set, "`title_{$c}` = ?", "`slug_{$c}` = ?", "`description_{$c}` = ?");
        array_push($vals, $titles[$c], $slugs[$c], $descs[$c]);
    }
    $tpl_vals = [];
    foreach (PAGES_ITEM_TPL_FIELDS as $tk) {
        $v = trim((string)($_POST[$tk] ?? ''));
        if ($v !== '' && !isset(design_editor_fields()[$tk]['choices'][$v])) pages_json(['ok' => false, 'field' => $tk, 'error' => __a('Geçersiz şablon seçimi.', 'Invalid template choice.')], 422);
        $tpl_vals[$tk] = $v;
    }
    $og = trim((string)($_POST['og_image'] ?? ''));
    // Yalnızca sitenin kendi içindeki görsel yolları (medya kütüphanesi); dış adres ve '..' yok
    if ($og !== '' && (str_contains($og, '..') || !preg_match('#^/?[A-Za-z0-9_][A-Za-z0-9_./ -]*\.(jpe?g|png|webp|avif|gif)$#i', $og))) {
        pages_json(['ok' => false, 'field' => 'og_image', 'error' => __a('Paylaşım görseli geçersiz.', 'Invalid sharing image.')], 422);
    }
    $vals[] = $id;
    $pdo->prepare("UPDATE `items` SET " . implode(', ', $set) . " WHERE `id` = ?")->execute($vals);
    foreach ($tpl_vals as $tk => $v) set_setting($tk . '@item-' . $id, $v);
    set_setting('item_og_image@item-' . $id, ltrim($og, '/'));
    foreach ($pg_langs as $c) set_setting('item_seo_title_' . $c . '@item-' . $id, mb_substr(trim((string)preg_replace('/[\x00-\x1F\x7F]/u', '', (string)($_POST['seo_title_' . $c] ?? ''))), 0, 120));
    $st->execute([$id]);
    $fresh = $st->fetch(PDO::FETCH_ASSOC);
    // Bağımsız sayfanın adresi değiştiyse menüdeki bağlantısı da yeni adrese geçer
    if ($item['type'] === 'page') {
        foreach (['tr', 'en'] as $c) {
            $old = (string)($item['slug_' . $c] ?: $item['slug']); $new = (string)($fresh['slug_' . $c] ?: $fresh['slug']);
            if ($old !== '' && $old !== $new) $pdo->prepare("UPDATE `menus` SET `url_{$c}` = ? WHERE TRIM(`url_{$c}`, '/') = ?")->execute([$c . '/' . $new . '/', $c . '/' . $old]);
        }
    }
    $urls = [];
    foreach ($pg_langs as $c) $urls[$c] = item_url($fresh, $c);
    $out_slugs = [];
    foreach ($pg_langs as $c) $out_slugs[$c] = (string)($fresh['slug_' . $c] ?? '');
    pages_json(['ok' => true, 'urls' => $urls, 'slugs' => $out_slugs, 'aliases' => count($aliases), 'locked' => $password !== '']);
}

// ── LİSTE SAYFASI AYARLARI: KAYDET ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'pages_cat_save') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) pages_json(['ok' => false, 'error' => __a('Güvenlik doğrulaması başarısız.', 'Security check failed.')], 400);
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM `categories` WHERE `id` = ? LIMIT 1");
    $st->execute([$id]);
    $cat = $st->fetch(PDO::FETCH_ASSOC);
    if (!$cat) pages_json(['ok' => false, 'error' => __a('Sayfa bulunamadı.', 'Page not found.')], 404);
    $names = $descs = [];
    foreach ($pg_langs as $c) {
        $names[$c] = mb_substr(trim((string)($_POST['name_' . $c] ?? '')), 0, 128);
        $descs[$c] = mb_substr(trim((string)($_POST['description_' . $c] ?? '')), 0, 1000);
    }
    // Ad ana dillerde (TR, EN) zorunlu; diğer dillerde boş kalırsa yedek dildeki ad görünür
    foreach (array_intersect(['tr', 'en'], $pg_langs) as $c) {
        if ($names[$c] === '') pages_json(['ok' => false, 'field' => 'name_' . $c, 'error' => sprintf(__a('Adı %s olarak da yazın.', 'Enter the name in %s too.'), strtoupper($c))], 422);
    }
    $set = ['`updated_at` = ?']; $vals = [date('Y-m-d H:i:s')];
    foreach ($pg_langs as $c) { array_push($set, "`name_{$c}` = ?", "`description_{$c}` = ?"); array_push($vals, $names[$c], $descs[$c]); }
    $vals[] = $id;
    $pdo->prepare("UPDATE `categories` SET " . implode(', ', $set) . " WHERE `id` = ?")->execute($vals);
    // Menüdeki bağlantının adı da aynı kalsın (categories.php ile aynı davranış)
    if (!empty($cat['menu_id'])) {
        $mset = ['`updated_at` = CURRENT_TIMESTAMP']; $mvals = [];
        foreach ($pg_langs as $c) { $mset[] = "`title_{$c}` = ?"; $mvals[] = $names[$c]; }
        $mvals[] = (int)$cat['menu_id'];
        $pdo->prepare("UPDATE `menus` SET " . implode(', ', $mset) . " WHERE `id` = ?")->execute($mvals);
    }
    pages_json(['ok' => true]);
}

// ── MENÜDE GÖSTER / GİZLE (menü satırı; bağlı liste sayfası da eşzamanlı) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'pages_menu_toggle') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) pages_json(['ok' => false], 400);
    $mid = (int)($_POST['menu_id'] ?? 0);
    $pdo->prepare("UPDATE `menus` SET `is_active` = CASE WHEN `is_active` = 1 THEN 0 ELSE 1 END, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?")->execute([$mid]);
    $pdo->prepare("UPDATE `categories` SET `show_in_menu` = (SELECT `is_active` FROM `menus` WHERE `id` = ?) WHERE `menu_id` = ?")->execute([$mid, $mid]);
    pages_json(['ok' => true]);
}

// ── LİSTE SAYFASINI MENÜYE EKLE (menü satırı yoksa oluşturulur; categories.php ile aynı biçim) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'pages_cat_menu_add') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) pages_json(['ok' => false], 400);
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM `categories` WHERE `id` = ? LIMIT 1");
    $st->execute([$id]);
    $cat = $st->fetch(PDO::FETCH_ASSOC);
    if (!$cat) pages_json(['ok' => false], 404);
    if (!empty($cat['menu_id'])) {
        $pdo->prepare("UPDATE `menus` SET `is_active` = 1 WHERE `id` = ?")->execute([(int)$cat['menu_id']]);
    } else {
        $w = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) + 10 FROM `menus`")->fetchColumn();
        $pdo->prepare("INSERT INTO `menus` (`title_tr`, `title_en`, `url_tr`, `url_en`, `weight`, `is_active`, `separator_before`, `is_system`) VALUES (?, ?, ?, ?, ?, 1, 0, 0)")
            ->execute([$cat['name_tr'], $cat['name_en'], 'tr/' . $cat['slug_tr'] . '/', 'en/' . $cat['slug_en'] . '/', $w]);
        $pdo->prepare("UPDATE `categories` SET `menu_id` = ? WHERE `id` = ?")->execute([(int)$pdo->lastInsertId(), $id]);
    }
    $pdo->prepare("UPDATE `categories` SET `show_in_menu` = 1 WHERE `id` = ?")->execute([$id]);
    pages_json(['ok' => true]);
}

// ── BAĞIMSIZ SAYFAYI MENÜYE EKLE (sayfanın adresine giden bir menü satırı) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'pages_page_menu_add') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) pages_json(['ok' => false], 400);
    $st = $pdo->prepare("SELECT * FROM `items` WHERE `id` = ? AND `type` = 'page' LIMIT 1");
    $st->execute([(int)($_POST['id'] ?? 0)]);
    $it = $st->fetch(PDO::FETCH_ASSOC);
    if (!$it) pages_json(['ok' => false], 404);
    $w = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) + 10 FROM `menus`")->fetchColumn();
    $pdo->prepare("INSERT INTO `menus` (`title_tr`, `title_en`, `url_tr`, `url_en`, `weight`, `is_active`, `separator_before`, `is_separator`, `is_system`) VALUES (?, ?, ?, ?, ?, 1, 0, 0, 0)")
        ->execute([$it['title_tr'] ?: $it['title_en'], $it['title_en'] ?: $it['title_tr'], 'tr/' . ($it['slug_tr'] ?: $it['slug']) . '/', 'en/' . ($it['slug_en'] ?: $it['slug']) . '/', $w]);
    pages_json(['ok' => true]);
}

// ── MENÜ BAĞLANTISI: EKLE / KAYDET (eskiden menus.php) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'pages_link_save') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) pages_json(['ok' => false, 'error' => __a('Güvenlik doğrulaması başarısız.', 'Security check failed.')], 400);
    $mid = (int)($_POST['menu_id'] ?? 0);
    $titles = $urls = [];
    foreach ($pg_langs as $c) {
        $titles[$c] = mb_substr(trim((string)($_POST['title_' . $c] ?? '')), 0, 255);
        $urls[$c]   = mb_substr(trim((string)($_POST['url_' . $c] ?? '')), 0, 500);
    }
    $first_title = (string)current(array_filter($titles, 'strlen'));
    $first_url   = (string)current(array_filter($urls, 'strlen'));
    if ($first_title === '') pages_json(['ok' => false, 'field' => 'title_' . $pg_langs[0], 'error' => __a('Bağlantının adını yazın.', 'Enter a name for the link.')], 422);
    if ($first_url === '') pages_json(['ok' => false, 'field' => 'url_' . $pg_langs[0], 'error' => __a('Bağlantının adresini yazın.', 'Enter the link address.')], 422);
    foreach ($urls as $c => $u) {
        // Yalnızca site içi yol, http(s), mailto ve tel; javascript: gibi şemalar engellenir
        if ($u !== '' && (preg_match('#^\s*(javascript|data|vbscript):#i', $u) || !preg_match('#^(https?://|mailto:|tel:|/|[a-z0-9])#i', $u))) {
            pages_json(['ok' => false, 'field' => 'url_' . $c, 'error' => __a('Adres /tr/sayfa/ ya da https://… biçiminde olmalı.', 'The address must look like /en/page/ or https://…')], 422);
        }
    }
    // TR ve EN sütunları NOT NULL; boşsa ilk yazılan değer kullanılır
    foreach (['tr', 'en'] as $c) {
        if (($titles[$c] ?? '') === '') $titles[$c] = $first_title;
        if (($urls[$c] ?? '') === '') $urls[$c] = $first_url;
    }
    $set = []; $vals = [];
    foreach ($titles as $c => $t) { $set[] = "`title_{$c}` = ?"; $vals[] = $t; $set[] = "`url_{$c}` = ?"; $vals[] = $urls[$c]; }
    if ($mid > 0) {
        $st = $pdo->prepare("SELECT `is_separator` FROM `menus` WHERE `id` = ? LIMIT 1");
        $st->execute([$mid]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['is_separator'] === 1) pages_json(['ok' => false, 'error' => __a('Bağlantı bulunamadı.', 'Link not found.')], 404);
    } else {
        $w = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) + 10 FROM `menus`")->fetchColumn();
        $pdo->prepare("INSERT INTO `menus` (`title_tr`, `title_en`, `url_tr`, `url_en`, `weight`, `is_active`, `separator_before`, `is_separator`, `is_system`) VALUES (?, ?, ?, ?, ?, 1, 0, 0, 0)")
            ->execute([$titles['tr'], $titles['en'], $urls['tr'], $urls['en'], $w]);
        $mid = (int)$pdo->lastInsertId();
    }
    $vals[] = $mid;
    $pdo->prepare("UPDATE `menus` SET " . implode(', ', $set) . ", `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?")->execute($vals);
    pages_json(['ok' => true, 'menu_id' => $mid]);
}

// ── MENÜYE AYRAÇ EKLE ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'pages_separator_add') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) pages_json(['ok' => false], 400);
    $w = (int)$pdo->query("SELECT COALESCE(MAX(`weight`), 0) + 10 FROM `menus`")->fetchColumn();
    $pdo->prepare("INSERT INTO `menus` (`title_tr`, `title_en`, `url_tr`, `url_en`, `weight`, `is_active`, `separator_before`, `is_separator`, `is_system`) VALUES ('| Dikey Ayraç', '| Separator', '', '', ?, 1, 0, 1, 0)")->execute([$w]);
    pages_json(['ok' => true, 'menu_id' => (int)$pdo->lastInsertId()]);
}

// ── MENÜ BAĞLANTISINI / AYRACI SİL (liste ve sabit sayfaların satırı silinmez; onlar menüden çıkarılır) ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'pages_menu_delete') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) pages_json(['ok' => false], 400);
    $mid = (int)($_POST['menu_id'] ?? 0);
    $st = $pdo->prepare("SELECT COUNT(*) FROM `categories` WHERE `menu_id` = ?");
    $st->execute([$mid]);
    if ((int)$st->fetchColumn() > 0) pages_json(['ok' => false, 'error' => __a('Liste sayfaları buradan silinmez; menüden çıkarabilirsiniz.', 'List pages cannot be deleted here; remove them from the menu instead.')], 422);
    $pdo->prepare("DELETE FROM `menus` WHERE `id` = ? AND `is_system` = 0")->execute([$mid]);
    pages_json(['ok' => true]);
}

// ── VERİ: menü, liste sayfaları, projeler, sabit sayfalar ──
$lang      = in_array($admin_lang, $pg_langs, true) ? $admin_lang : ($pg_langs[0] ?? 'tr');
$menus     = $pdo->query("SELECT * FROM `menus` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
$cats      = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
// 'page' türü bağımsız sayfalardır; 'aboutme' kaydı Hakkımda'nın içeriğidir ve sabit sayfa olarak listelenir
$all_items = $pdo->query("SELECT * FROM `items` WHERE `type` <> 'blog' AND NOT (`type` = 'page' AND `slug` IN ('aboutme', '404')) ORDER BY `weight` ASC, `date` DESC, `id` DESC")->fetchAll(PDO::FETCH_ASSOC);
$items_by_type = [];
foreach ($all_items as $it) $items_by_type[$it['type']][] = $it;
// Menüdeki bağlantı bir bağımsız sayfayı gösteriyorsa satır o sayfa olarak listelenir
$page_by_url = [];
foreach ($items_by_type['page'] ?? [] as $it) $page_by_url['tr/' . ($it['slug_tr'] ?: $it['slug']) . '/'] = $it;
$used_pages = [];
$cat_keys = array_column($cats, 'key_slug');
$cat_by_menu = [];
foreach ($cats as $c) if (!empty($c['menu_id'])) $cat_by_menu[(int)$c['menu_id']] = $c;
$statics = pages_static_defs();
$static_by_url = [];
foreach ($statics as $k => $d) $static_by_url['tr/' . get_static_slug($k, 'tr') . '/'] = $k;

$nodes = [];   // anahtar → düğüm (ekran ve ayarlar için)

$item_node = function (array $it) use (&$nodes, $lang): string {
    $key = 'item-' . $it['id'];
    $cover = '';
    if (!empty($it['cover_image'])) {
        $full = resolve_image_file_path(ltrim((string)$it['cover_image'], '/'));
        $small = $full ? resize_and_convert_image($full, 800, 'webp', 80) : null;
        $cover = url($small ?: ltrim((string)$it['cover_image'], '/'));
    }
    $nodes[$key] = [
        'kind' => 'item', 'id' => (int)$it['id'],
        'label' => item_field($it, 'title', $lang) ?: ('#' . $it['id']),
        'status' => $it['status'] ?: 'published', 'locked' => ($it['access_password'] ?? '') !== '',
        'preview' => item_url($it, $lang),
        // Bağımsız sayfa yazı editöründe (blog-edit.php) açılır; proje şablonu ve tasarım bölümü yok
        'edit' => $it['type'] === 'page' ? 'blog-edit.php?id=' . (int)$it['id'] : 'item-edit.php?id=' . (int)$it['id'] . '&return=pages',
        'design' => $it['type'] === 'page' ? '' : 'project', 'scope' => 'item-' . (int)$it['id'], 'cover' => $cover, 'standalone' => $it['type'] === 'page',
        'f' => pages_item_fields($it), 'missing' => lang_missing($it, ['title', 'description', 'location', 'content']),
    ];
    return $key;
};
$cat_node = function (array $c, int $mid, bool $active) use (&$nodes, $lang, $item_node, $items_by_type): string {
    $key = 'cat-' . $c['id'];
    $nodes[$key] = [
        'kind' => 'category', 'id' => (int)$c['id'], 'menuId' => $mid, 'active' => $active,
        'label' => get_category_localized_name($c, $lang), 'children' => array_map($item_node, $items_by_type[$c['key_slug']] ?? []),
        'preview' => url($lang . '/' . ($c['slug_' . $lang] ?: $c['slug_tr']) . '/'), 'edit' => 'categories.php', 'design' => 'lists', 'scope' => (string)$c['key_slug'],
        'f' => pages_cat_fields($c), 'missing' => lang_missing($c, ['name', 'description']),
    ];
    return $key;
};

$tree_menu = [];
$tree_off  = [];
$used_cats = [];
$used_statics = [];
foreach ($menus as $m) {
    $mid = (int)$m['id'];
    $active = (int)$m['is_active'] === 1;
    if ((int)($m['is_separator'] ?? 0) === 1) {
        $nodes['menu-' . $mid] = ['kind' => 'separator', 'menuId' => $mid, 'active' => $active, 'label' => __a('Ayraç', 'Divider')];
        if ($active) $tree_menu[] = 'menu-' . $mid;
        continue;
    }
    $url_tr = ltrim((string)$m['url_tr'], '/');
    if (isset($cat_by_menu[$mid])) {
        $used_cats[(int)$cat_by_menu[$mid]['id']] = true;
        $key = $cat_node($cat_by_menu[$mid], $mid, $active);
    } elseif (isset($static_by_url[$url_tr])) {
        $sk = $static_by_url[$url_tr];
        $used_statics[$sk] = true;
        $d = $statics[$sk];
        $key = 'static-' . $sk;
        $nodes[$key] = ['kind' => 'static', 'menuId' => $mid, 'active' => $active, 'label' => __a($d[0], $d[1]), 'preview' => url($lang . '/' . get_static_slug($sk, $lang) . '/'), 'edit' => $d[2], 'design' => $d[3]];
    } elseif (isset($page_by_url[$url_tr]) && !isset($used_pages[(int)$page_by_url[$url_tr]['id']])) {
        $used_pages[(int)$page_by_url[$url_tr]['id']] = true;
        $key = $item_node($page_by_url[$url_tr]);
        $nodes[$key]['menuId'] = $mid;
        $nodes[$key]['active'] = $active;
    } else {
        $key = 'menu-' . $mid;
        $is_external = (bool)preg_match('#^(https?:|mailto:|tel:)#i', $url_tr);
        $lf = [];
        foreach ($pg_langs as $c) { $lf['title_' . $c] = (string)($m['title_' . $c] ?? ''); $lf['url_' . $c] = (string)($m['url_' . $c] ?? ''); }
        $nodes[$key] = ['kind' => 'link', 'menuId' => $mid, 'active' => $active, 'label' => __a($m['title_tr'], $m['title_en']) ?: $url_tr, 'preview' => $is_external ? '' : url(ltrim((string)($lang === 'en' ? $m['url_en'] : $m['url_tr']), '/')), 'external' => $is_external ? $m['url_tr'] : '', 'f' => $lf];
    }
    if ($active) $tree_menu[] = $key; else $tree_off[] = $key;
}
// Menü satırı olmayan liste sayfaları
foreach ($cats as $c) {
    if (isset($used_cats[(int)$c['id']])) continue;
    $tree_off[] = $cat_node($c, 0, false);
}
// Menüde olmayan sabit sayfalar (yalnızca açık olanlar)
foreach ($statics as $sk => $d) {
    if (isset($used_statics[$sk]) || !$d[4]) continue;
    $key = 'static-' . $sk;
    $nodes[$key] = ['kind' => 'static', 'menuId' => 0, 'active' => false, 'label' => __a($d[0], $d[1]), 'preview' => url($lang . '/' . get_static_slug($sk, $lang) . '/'), 'edit' => $d[2], 'design' => $d[3]];
    $tree_off[] = $key;
}
// 404 sayfası: menüye girmez; yoksa ilk düzenlemede varsayılan metinle oluşturulur (blog-edit.php?system=404)
$nodes['static-404'] = ['kind' => 'static', 'menuId' => 0, 'active' => false, 'label' => __a('404 sayfası', '404 page'), 'preview' => url($lang . '/404/'), 'edit' => 'blog-edit.php?system=404', 'design' => ''];
$tree_off[] = 'static-404';
// Hiçbir liste sayfasına bağlı olmayan sayfalar
$other = [];
foreach ($items_by_type as $type => $list) {
    if (in_array($type, $cat_keys, true)) continue;
    foreach ($list as $it) {
        if (isset($used_pages[(int)$it['id']])) continue;
        $k = $item_node($it);
        // Menüde olmayan bağımsız sayfa: "Menüde değil" grubunda, menüye eklenebilir
        if ($type === 'page') $tree_off[] = $k; else $other[] = $k;
    }
}
// Ana sayfa: ağacın en üstünde; seçki homepage.php'de, görünüm Temalar › Ana Sayfa'da düzenlenir
$nodes['static-home'] = ['kind' => 'static', 'menuId' => 0, 'active' => true, 'label' => __a('Ana Sayfa', 'Homepage'), 'preview' => url($lang . '/'), 'edit' => 'homepage.php', 'design' => 'home'];
$first_key = $tree_menu[0] ?? ($tree_off[0] ?? '');

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/image_modal.php';

/** Bir ağaç satırı */
function pages_row(string $key, array $nodes, bool $sortable): string {
    $n = $nodes[$key];
    $icon = [
        'category'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
        'item'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="1"/><circle cx="9" cy="10" r="2"/><path d="M21 17l-5-5-9 8"/></svg>',
        'static'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><line x1="9" y1="12" x2="16" y2="12"/><line x1="9" y1="16" x2="16" y2="16"/></svg>',
        'link'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>',
        'separator' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><line x1="12" y1="4" x2="12" y2="20"/></svg>',
    ][$n['kind']];
    $badge = '';
    if ($n['kind'] === 'item' && $n['status'] !== 'published') {
        $badge = '<span class="pg-badge pg-badge-' . e($n['status']) . '">' . e($n['status'] === 'draft' ? __a('Taslak', 'Draft') : __a('Gizli bağlantı', 'Unlisted')) . '</span>';
    }
    if ($n['kind'] === 'item' && !empty($n['locked'])) $badge .= '<span class="pg-badge" title="' . e(__a('Parolalı', 'Password protected')) . '">🔒</span>';
    if (!empty($n['missing'])) {
        foreach ($n['missing'] as $mc) $badge .= '<span class="pg-badge pg-badge-lang" title="' . e(sprintf(__a('%s çevirisi eksik', '%s translation missing'), strtoupper($mc))) . '">' . e(strtoupper($mc)) . '</span>';
    }
    if ($n['kind'] === 'category') $badge .= '<span class="pg-count">' . count($n['children']) . '</span>';
    $handle = $sortable ? '<span class="pg-handle" aria-hidden="true" title="' . e(__a('Sürükleyerek sırala', 'Drag to reorder')) . '">⋮⋮</span>' : '<span class="pg-handle-space"></span>';
    $chev = $n['kind'] === 'category' && $n['children'] ? '<button type="button" class="pg-chev" data-pg-toggle aria-expanded="false" aria-label="' . e(__a('Aç / kapat', 'Expand / collapse')) . '">›</button>' : '<span class="pg-chev-space"></span>';
    $html = '<li class="pg-node pg-kind-' . e($n['kind']) . '" data-key="' . e($key) . '"' . ($sortable ? ' draggable="true"' : '') . '>';
    $html .= '<div class="pg-row">' . $handle . $chev . '<button type="button" class="pg-select" data-pg-select="' . e($key) . '">' . $icon . '<span class="pg-label">' . e($n['label']) . '</span>' . $badge . '</button>';
    $html .= '<span class="pg-actions">';
    if (in_array($n['kind'], ['item', 'category', 'link'], true)) $html .= '<button type="button" class="pg-act" data-pg-settings="' . e($key) . '">' . e(__a('Ayarlar', 'Settings')) . '</button>';
    $html .= '<button type="button" class="pg-act pg-more" data-pg-more="' . e($key) . '" aria-haspopup="menu" aria-label="' . e(__a('Diğer işlemler', 'More actions')) . '">⋯</button></span>';
    $html .= '</div>';
    if ($n['kind'] === 'category' && $n['children']) {
        $html .= '<ul class="pg-children" hidden data-sort="items">';
        foreach ($n['children'] as $ck) $html .= pages_row($ck, $nodes, true);
        $html .= '</ul>';
    }
    return $html . '</li>';
}

/** "Tüm sayfalar" tablosunun satırları (admin-table): menü bağlantıları ve ayraçlar sayfa olmadığı için yok */
function pages_table_rows(array $keys, array $nodes, string $parent = ''): string {
    $html = '';
    foreach ($keys as $key) {
        $n = $nodes[$key] ?? null;
        if (!$n || in_array($n['kind'], ['link', 'separator'], true)) continue;
        if ($n['kind'] === 'category')   [$type, $tcls] = [__a('Liste sayfası', 'List page'), 'badge-info'];
        elseif ($n['kind'] === 'static') [$type, $tcls] = [__a('Sabit sayfa', 'Fixed page'), 'badge-neutral'];
        elseif (!empty($n['standalone'])) [$type, $tcls] = [__a('Bağımsız sayfa', 'Standalone page'), 'badge-violet'];
        elseif ($parent !== '')          [$type, $tcls] = [$parent, 'badge-neutral'];
        else                             [$type, $tcls] = [__a('Listeye bağlı değil', 'Not in a list'), 'badge-warning'];
        $status = $n['status'] ?? 'published';
        [$st_label, $st_cls] = [
            'published' => [__a('Yayında', 'Published'), 'badge-success'],
            'unlisted'  => [__a('Gizli bağlantı', 'Unlisted'), 'badge-info'],
            'draft'     => [__a('Taslak', 'Draft'), 'badge-warning'],
        ][$status] ?? [$status, 'badge-neutral'];
        $in_menu = !empty($n['menuId']) && !empty($n['active']);
        $menu_cell = ($key === 'static-home' || $parent !== '') ? '<span class="pg-t-muted">—</span>'
            : ($in_menu ? '<span class="badge badge-success">' . e(__a('Menüde', 'In menu')) . '</span>' : '<span class="badge badge-neutral">' . e(__a('Menüde değil', 'Not in menu')) . '</span>');
        $path = preg_replace('#^https?://[^/]+#', '', (string)($n['preview'] ?? ''));
        $thumb = !empty($n['cover'])
            ? '<img src="' . e($n['cover']) . '" width="56" height="38" loading="lazy" decoding="async" alt="">'
            : '<span aria-hidden="true">' . ($n['kind'] === 'category' ? '▤' : '¶') . '</span>';
        $chips = '';
        foreach ($n['missing'] ?? [] as $mc) $chips .= '<span class="badge badge-warning pg-t-chip" title="' . e(sprintf(__a('%s çevirisi eksik', '%s translation missing'), strtoupper($mc))) . '">' . e(strtoupper($mc)) . '</span>';
        if (!empty($n['locked'])) $chips .= '<span class="badge badge-neutral pg-t-chip" title="' . e(__a('Parolalı', 'Password protected')) . '">🔒</span>';

        $html .= '<tr data-key="' . e($key) . '"' . ($parent !== '' ? ' class="is-child"' : '') . '>';
        $html .= '<td class="pg-t-thumb"><span class="pg-t-cover">' . $thumb . '</span></td>';
        $html .= '<td class="pg-t-name"><button type="button" class="pg-t-open" data-pg-open="' . e($key) . '">' . e($n['label']) . '</button>' . $chips
              . ($path !== '' ? '<a class="pg-t-path" href="' . e($n['preview']) . '" target="_blank" rel="noopener">' . e($path) . ' ↗</a>' : '') . '</td>';
        $html .= '<td><span class="badge ' . $tcls . '">' . e($type) . '</span></td>';
        $html .= '<td><span class="badge ' . $st_cls . '">' . e($st_label) . '</span></td>';
        $html .= '<td>' . $menu_cell . '</td>';
        $html .= '<td class="pg-t-act"><div style="display:inline-flex; gap:6px;">'
              . (!empty($n['edit']) ? '<a class="btn btn-secondary btn-sm" href="' . e($n['edit']) . '">' . e(__a('Düzenle', 'Edit')) . '</a>' : '')
              . '<button type="button" class="btn btn-secondary btn-sm" data-pg-more="' . e($key) . '" aria-haspopup="menu" aria-label="' . e(__a('Diğer işlemler', 'More actions')) . '">⋯</button></div></td>';
        $html .= '</tr>';
        if ($n['kind'] === 'category') $html .= pages_table_rows($n['children'], $nodes, $n['label']);
    }
    return $html;
}
?>
<style>
/* ── "Tüm sayfalar" (panelin .portfolio-tabs, .card ve .admin-table bileşenleri üzerine küçük ekler) ── */
.pg-view[hidden] { display:none !important; }
.pg-sr { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
.pg-t-thumb { width:68px; }
.pg-t-cover { display:flex; align-items:center; justify-content:center; width:56px; height:38px; border-radius:6px; overflow:hidden; background:var(--bg-canvas-subtle); border:1px solid var(--border-subtle); color:var(--text-muted); font-size:15px; }
.pg-t-cover img { width:56px; height:38px; object-fit:cover; display:block; }
.pg-t-open { all:unset; cursor:pointer; font-weight:600; font-size:14px; color:var(--text-main); }
.pg-t-open:hover { color:var(--accent-blue); }
.pg-t-open:focus-visible { outline:2px solid var(--accent-blue); outline-offset:2px; border-radius:2px; }
.pg-t-chip { margin-left:6px; padding:1px 6px; font-size:11px; vertical-align:1px; }
.pg-t-path { display:block; margin-top:2px; font-size:12px; font-family:var(--font-mono); color:var(--text-sub); text-decoration:none; }
.pg-t-path:hover { color:var(--accent-blue); }
.pg-t-muted { color:var(--text-muted); }
.pg-t-act { text-align:right; white-space:nowrap; }
.admin-table tr.is-child .pg-t-thumb { padding-left:36px; width:104px; }
.admin-table tr.is-child td { background:var(--bg-surface-2); }
.admin-table tr.is-child:hover td { background:var(--bg-hover); }
/* ── Sayfalar ekranı ── */
.pg-langs { display:flex; gap:4px; flex-wrap:wrap; margin:0 0 14px; padding:3px; border:1px solid var(--border-subtle); border-radius:10px; background:var(--bg-canvas-subtle, transparent); }
.pg-lang { position:relative; flex:1 1 auto; padding:6px 10px; border:0; border-radius:6px; background:transparent; color:var(--text-sub); font:600 12px/1.2 var(--font-mono, monospace); cursor:pointer; }
.pg-lang.is-on { background:var(--bg-surface); color:var(--text-main); box-shadow:0 1px 3px rgba(15,23,42,.12); }
.pg-lang:focus-visible { outline:2px solid #2563eb; outline-offset:1px; }
.pg-lang-dot { position:absolute; top:5px; right:6px; width:6px; height:6px; border-radius:50%; background:#f59e0b; }
.pg-lpane[hidden] { display:none !important; }
.pg-badge-lang { font-family:var(--font-mono, monospace); background:var(--warning-soft) !important; color:var(--warning-text) !important; }
[data-theme="dark"] .pg-badge-lang { background:rgba(245,158,11,.18) !important; color:#fcd34d !important; }
.pg-wrap { display:flex; height:calc(100vh - 120px); min-height:560px; border:1px solid var(--border-card); border-radius:10px; overflow:hidden; background:var(--bg-surface); }
.pg-side { width:380px; flex-shrink:0; border-right:1px solid var(--border-card); display:flex; flex-direction:column; min-height:0; }
.pg-side-top { padding:14px 16px; border-bottom:1px solid var(--border-card); display:flex; gap:8px; align-items:center; }
.pg-side-top[hidden] { display:none; }
.pg-side-top .btn { flex:1; justify-content:center; }
.pg-scroll { flex:1; overflow-y:auto; padding:6px 0 24px; }
.pg-scroll[hidden] { display:none; }
.pg-part { margin:16px 16px 6px; font:600 12px/1 var(--font-mono, monospace); letter-spacing:.09em; text-transform:uppercase; color:var(--text-muted); }
.pg-tree, .pg-children { list-style:none; margin:0; padding:0; }
.pg-tree { margin:0 10px; border:1px solid var(--border-card); border-radius:10px; overflow:hidden; }
.pg-children { border-top:1px solid var(--border-card); background:var(--bg-surface-2, rgba(0,0,0,.02)); }
.pg-children[hidden] { display:none; }
.pg-children .pg-row { padding-left:30px; }
.pg-node + .pg-node { border-top:1px solid var(--border-card); }
.pg-row { display:flex; align-items:center; gap:4px; padding:0 8px; min-height:44px; }
.pg-row:hover, .pg-node.is-selected > .pg-row { background:var(--bg-surface-2, rgba(37,99,235,.06)); }
.pg-node.is-selected > .pg-row { box-shadow:inset 3px 0 0 var(--accent-blue, #2563eb); }
.pg-handle { cursor:grab; color:var(--text-muted); font-size:12px; letter-spacing:-2px; width:14px; text-align:center; opacity:.5; user-select:none; flex-shrink:0; }
.pg-row:hover .pg-handle { opacity:1; }
.pg-handle-space, .pg-chev-space { width:14px; flex-shrink:0; }
.pg-chev { all:unset; cursor:pointer; width:18px; height:24px; display:inline-flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:16px; transition:transform .15s; flex-shrink:0; }
.pg-chev[aria-expanded="true"] { transform:rotate(90deg); }
.pg-chev:focus-visible, .pg-select:focus-visible, .pg-act:focus-visible { outline:2px solid var(--accent-blue); outline-offset:1px; }
.pg-select { all:unset; cursor:pointer; flex:1; min-width:0; display:flex; align-items:center; gap:8px; padding:10px 2px; font-size:14px; color:var(--text-main); }
.pg-select svg { flex-shrink:0; color:var(--text-muted); }
.pg-label { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pg-kind-separator .pg-label { color:var(--text-muted); font-style:italic; }
.pg-badge { flex-shrink:0; font-size:12px; font-weight:700; padding:1px 7px; border-radius:999px; background:var(--bg-surface-2, #eee); color:var(--text-muted); }
.pg-badge-draft { background:rgba(245,158,11,.15); color:var(--warning-text); }
.pg-badge-unlisted { background:rgba(37,99,235,.12); color:var(--accent-blue); }
.pg-count { flex-shrink:0; font-size:12px; color:var(--text-muted); }
.pg-actions { display:flex; gap:4px; opacity:0; transition:opacity .12s; }
.pg-row:hover .pg-actions, .pg-row:focus-within .pg-actions, .pg-node.is-selected > .pg-row .pg-actions { opacity:1; }
.pg-act { all:unset; cursor:pointer; font-size:12px; font-weight:600; padding:4px 8px; border:1px solid var(--border-card); border-radius:6px; background:var(--bg-surface); color:var(--text-main); }
.pg-more { font-size:14px; line-height:1; padding:3px 7px; }
.pg-node.is-dragging { opacity:.4; }
.pg-node.drop-before > .pg-row { box-shadow:inset 0 2px 0 var(--accent-blue, #2563eb); }
.pg-node.drop-after > .pg-row { box-shadow:inset 0 -2px 0 var(--accent-blue, #2563eb); }
.pg-empty { margin:6px 16px; font-size:13px; color:var(--text-muted); }
.pg-menu { position:fixed; z-index:10050; min-width:220px; padding:6px; background:var(--bg-surface); border:1px solid var(--border-card); border-radius:10px; box-shadow:0 12px 32px rgba(0,0,0,.16); }
.pg-menu[hidden] { display:none; }
.pg-menu a, .pg-menu button { all:unset; box-sizing:border-box; display:block; width:100%; padding:8px 10px; border-radius:6px; font-size:13px; color:var(--text-main); cursor:pointer; }
.pg-menu a:hover, .pg-menu button:hover, .pg-menu a:focus-visible, .pg-menu button:focus-visible { background:var(--bg-surface-2, rgba(0,0,0,.05)); outline:none; }
.pg-menu .is-danger { color:var(--danger-text); }
.pg-menu hr { border:0; border-top:1px solid var(--border-card); margin:5px 2px; }
/* Ayarlar paneli */
.pg-settings { flex:1; overflow-y:auto; padding:18px 18px 0; }
.pg-settings[hidden] { display:none; }
.pg-back { all:unset; cursor:pointer; font-size:13px; font-weight:600; color:var(--text-muted); }
.pg-back:hover { color:var(--text-main); }
.pg-back:focus-visible { outline:2px solid var(--accent-blue); }
.pg-settings h2 { margin:10px 0 2px; font-size:20px; color:var(--text-main); }
.pg-settings .pg-sub { margin:0 0 10px; font-size:13px; color:var(--text-muted); }
.pg-field { padding:12px 0; border-bottom:1px solid var(--border-card); }
.pg-field label, .pg-field .pg-flabel { display:block; font-size:13px; font-weight:600; color:var(--text-main); margin:0 0 6px; }
.pg-field small { display:block; margin-top:5px; font-size:12px; line-height:1.45; color:var(--text-muted); }
.pg-in { width:100%; box-sizing:border-box; padding:8px 10px; border:1px solid var(--border-card); border-radius:6px; background:var(--bg-surface); color:var(--text-main); font:inherit; font-size:14px; }
textarea.pg-in { min-height:74px; resize:vertical; }
.pg-in:focus { outline:2px solid var(--accent-blue); outline-offset:0; border-color:transparent; }
.pg-in.is-invalid, .pg-url.is-invalid { border-color:#dc2626; }
.pg-two { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.pg-url { display:flex; align-items:center; border:1px solid var(--border-card); border-radius:6px; overflow:hidden; background:var(--bg-surface); }
.pg-url span { padding:8px 0 8px 10px; font-size:13px; color:var(--text-muted); white-space:nowrap; font-family:var(--font-mono, monospace); }
.pg-url span.pg-url-end { padding:8px 10px 8px 0; }
.pg-url input { border:0; padding-left:2px; font-family:var(--font-mono, monospace); font-size:13px; }
.pg-url input:focus { outline:none; }
.pg-url:focus-within { outline:2px solid var(--accent-blue); }
.pg-serp { margin-top:8px; padding:12px 14px; border:1px solid var(--border-card); border-radius:10px; background:#fff; font-family:Arial, sans-serif; }
.pg-serp-url { font-size:12px; color:#202124; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pg-serp-title { font-size:16px; line-height:1.3; color:#1a0dab; margin-bottom:3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pg-serp-desc { font-size:13px; line-height:1.45; color:#4d5156; }
.pg-serp-desc.is-empty { color:var(--warning-text); font-style:italic; }
.pg-cover { display:flex; gap:12px; align-items:center; }
.pg-cover img { width:96px; height:64px; object-fit:cover; border-radius:6px; border:1px solid var(--border-card); background:#eee; }
.pg-seg { display:flex; gap:4px; background:var(--bg-surface-2, var(--bg-body)); border:1px solid var(--border-card); border-radius:10px; padding:3px; }
.pg-seg label { position:relative; flex:1; margin:0; cursor:pointer; }
.pg-seg input { position:absolute; opacity:0; pointer-events:none; }
.pg-seg span { display:block; text-align:center; padding:7px 6px; border-radius:6px; font-size:12px; font-weight:600; color:var(--text-muted); }
.pg-seg input:checked + span { background:var(--bg-surface); color:var(--text-main); box-shadow:0 1px 3px rgba(0,0,0,.12); }
.pg-seg input:focus-visible + span { outline:2px solid var(--accent-blue); }
.pg-save { position:sticky; bottom:0; margin:12px -18px 0; padding:12px 18px; background:var(--bg-surface); border-top:1px solid var(--border-card); display:flex; gap:10px; align-items:center; }
.pg-msg { flex:1; font-size:13px; }
.pg-msg.is-ok { color:var(--success-text); } .pg-msg.is-err { color:var(--danger-text); }
/* Önizleme */
.pg-stage { flex:1; min-width:0; display:flex; flex-direction:column; background:var(--bg-body, #f3f3f1); }
.pg-stage-top { display:flex; align-items:center; gap:10px; padding:10px 14px; border-bottom:1px solid var(--border-card); background:var(--bg-surface); }
.pg-stage-title { flex:1; min-width:0; font-size:13px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:var(--font-mono, monospace); }
.pg-dev { display:inline-flex; border:1px solid var(--border-card); border-radius:6px; padding:2px; gap:2px; }
.pg-dev button { all:unset; cursor:pointer; padding:5px 10px; border-radius:6px; color:var(--text-muted); font-size:12px; font-weight:600; }
.pg-dev button[aria-pressed="true"] { background:var(--bg-surface-2, rgba(37,99,235,.08)); color:var(--accent-blue, #2563eb); }
.pg-dev button:focus-visible { outline:2px solid var(--accent-blue); }
.pg-frame-wrap { flex:1; display:flex; justify-content:center; padding:14px; min-height:0; }
.pg-frame-wrap iframe { width:100%; height:100%; border:1px solid var(--border-card); background:#fff; transition:max-width .25s; }
.pg-frame-wrap iframe[hidden] { display:none; }
.pg-wrap.is-phone .pg-frame-wrap iframe { max-width:390px; border-radius:10px; }
.pg-noprev { margin:auto; font-size:13px; color:var(--text-muted); text-align:center; max-width:280px; }
@media (max-width: 960px) {
    .pg-wrap { flex-direction:column; height:auto; }
    .pg-side { width:auto; border-right:0; border-bottom:1px solid var(--border-card); }
    .pg-stage { height:70vh; }
    .pg-actions { opacity:1; }
}
@media (prefers-reduced-motion: reduce) { .pg-chev, .pg-frame-wrap iframe, .pg-actions { transition:none; } }
</style>

<?php $pg_rows = pages_table_rows(array_merge(['static-home'], $tree_menu, $tree_off, $other), $nodes); $pg_count = substr_count($pg_rows, '<tr '); ?>
<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('Sayfalar', 'Pages')) ?></h1>
        <p class="page-header-sub"><?= e(__a('Sitenizdeki bütün sayfalar tek yerde. “Menü ve önizleme” sekmesinde menü sırasını sürükleyerek değiştirebilir, sayfaları önizleyebilirsiniz.', 'Every page on your site in one place. In “Menu and preview” you can drag to change the menu order and preview pages.')) ?></p>
    </div>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" id="pgCreateBtn" aria-haspopup="menu" aria-controls="pgCreateMenu">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span><?= e(__a('Yeni', 'New')) ?></span>
        </button>
    </div>
</div>

<div class="portfolio-tabs" role="tablist" aria-label="<?= e(__a('Görünüm', 'View')) ?>">
    <button type="button" role="tab" class="portfolio-tab-btn active" id="pgTabList" data-pg-tab="list" aria-controls="pgViewList" aria-selected="true"><?= e(__a('Tüm sayfalar', 'All pages')) ?> <span class="tab-badge"><?= $pg_count ?></span></button>
    <button type="button" role="tab" class="portfolio-tab-btn" id="pgTabTree" data-pg-tab="tree" aria-controls="pgWrap" aria-selected="false"><?= e(__a('Menü ve önizleme', 'Menu and preview')) ?></button>
</div>

<div class="pg-view card" id="pgViewList" role="tabpanel" aria-labelledby="pgTabList">
    <div class="card-header" style="padding:16px 20px;">
        <h3 class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><line x1="9" y1="12" x2="16" y2="12"/><line x1="9" y1="16" x2="16" y2="16"/></svg>
            <span><?= e(__a('Tüm sayfalar', 'All pages')) ?> (<?= $pg_count ?>)</span>
        </h3>
        <span class="badge badge-neutral" style="font-weight:500;"><?= e(__a('Sayfa adına tıklayınca önizlemesi açılır', 'Click a page name to preview it')) ?></span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th><span class="pg-sr"><?= e(__a('Kapak', 'Cover')) ?></span></th>
                <th><?= e(__a('Sayfa', 'Page')) ?></th>
                <th style="width:170px;"><?= e(__a('Tür', 'Type')) ?></th>
                <th style="width:120px;"><?= e(__a('Durum', 'Status')) ?></th>
                <th style="width:130px;"><?= e(__a('Menü', 'Menu')) ?></th>
                <th style="width:140px; text-align:right;"><?= e(__a('İşlemler', 'Actions')) ?></th>
            </tr></thead>
            <tbody><?= $pg_rows ?></tbody>
        </table>
    </div>
</div>

<div class="pg-view pg-wrap" id="pgWrap" role="tabpanel" aria-labelledby="pgTabTree" hidden>
    <aside class="pg-side">
        <div class="pg-scroll" id="pgTreePane">
            <ul class="pg-tree">
                <?= pages_row('static-home', $nodes, false) ?>
            </ul>
            <p class="pg-part"><?= e(__a('Menüde', 'In menu')) ?></p>
            <?php if ($tree_menu): ?>
            <ul class="pg-tree" id="pgMenuTree" data-sort="menus">
                <?php foreach ($tree_menu as $k) echo pages_row($k, $nodes, true); ?>
            </ul>
            <?php else: ?><p class="pg-empty"><?= e(__a('Menüde sayfa yok.', 'No pages in the menu.')) ?></p><?php endif; ?>

            <p class="pg-part"><?= e(__a('Menüde değil', 'Not in menu')) ?></p>
            <?php if ($tree_off): ?>
            <ul class="pg-tree">
                <?php foreach ($tree_off as $k) echo pages_row($k, $nodes, false); ?>
            </ul>
            <?php else: ?><p class="pg-empty"><?= e(__a('Bütün sayfalar menüde.', 'Every page is in the menu.')) ?></p><?php endif; ?>

            <?php if ($other): ?>
            <p class="pg-part"><?= e(__a('Diğer sayfalar', 'Other pages')) ?></p>
            <ul class="pg-tree">
                <?php foreach ($other as $k) echo pages_row($k, $nodes, false); ?>
            </ul>
            <?php endif; ?>
        </div>
        <div class="pg-settings" id="pgSettings" hidden></div>
    </aside>
    <section class="pg-stage" aria-label="<?= e(__a('Önizleme', 'Preview')) ?>">
        <div class="pg-stage-top">
            <span class="pg-stage-title" id="pgStageTitle"></span>
            <div class="pg-dev" role="group" aria-label="<?= e(__a('Önizleme boyutu', 'Preview size')) ?>">
                <button type="button" data-pg-dev="desktop" aria-pressed="true"><?= e(__a('Masaüstü', 'Desktop')) ?></button>
                <button type="button" data-pg-dev="phone" aria-pressed="false"><?= e(__a('Telefon', 'Phone')) ?></button>
            </div>
            <a class="btn btn-secondary btn-sm" id="pgOpen" href="#" target="_blank" rel="noopener"><?= e(__a('Sitede aç', 'Open on site')) ?> ↗</a>
        </div>
        <div class="pg-frame-wrap" id="pgFrameWrap">
            <iframe id="pgFrame" title="<?= e(__a('Sayfa önizlemesi', 'Page preview')) ?>" src="about:blank"></iframe>
        </div>
    </section>
</div>

<div class="pg-menu" id="pgMenu" role="menu" hidden></div>
<div class="pg-menu" id="pgCreateMenu" role="menu" hidden>
    <?php foreach ($cats as $c): ?>
        <a role="menuitem" href="item-edit.php?type=<?= e(rawurlencode($c['key_slug'])) ?>&amp;return=pages"><?= e(sprintf(__a('Yeni sayfa: %s', 'New page in %s'), __a($c['name_tr'], $c['name_en']))) ?></a>
    <?php endforeach; ?>
    <a role="menuitem" href="blog-edit.php?type=page"><?= e(__a('Boş sayfa (listeye bağlı değil)', 'Blank page (not in a list)')) ?></a>
    <hr>
    <a role="menuitem" href="categories.php"><?= e(__a('Yeni liste sayfası (ör. Projeler, Seriler)', 'New list page (e.g. Projects, Series)')) ?></a>
    <button type="button" role="menuitem" data-pg-new-link><?= e(__a('Menüye bağlantı ekle', 'Add a menu link')) ?></button>
    <button type="button" role="menuitem" data-pg-new-sep><?= e(__a('Menüye ayraç ekle', 'Add a menu divider')) ?></button>
    <a role="menuitem" href="blog-edit.php"><?= e(__a('Yeni blog yazısı', 'New blog post')) ?></a>
</div>

<script>
(function () {
    var NODES = <?= json_encode($nodes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var CSRF = <?= json_encode(csrf_token()) ?>;
    var FIRST = <?= json_encode($first_key) ?>;
    var LANG = <?= json_encode($lang) ?>;
    var LANGS = <?= json_encode(lang_bar_languages(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var paneLang = LANG;
    var SITE_HOST = <?= json_encode(preg_replace('#^https?://#', '', rtrim(url(''), '/'))) ?>;
    var TPL = <?= json_encode(array_map(fn($tk) => [
        'key' => $tk, 'label' => __a(design_editor_fields()[$tk]['tr'], design_editor_fields()[$tk]['en']),
        'choices' => array_map(fn($v, $c) => [$v, __a($c['tr'], $c['en'])], array_keys(design_editor_fields()[$tk]['choices']), design_editor_fields()[$tk]['choices']),
    ], PAGES_ITEM_TPL_FIELDS), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var BASE = <?= json_encode(rtrim(url(''), '/') . '/') ?>;
    var T = <?= json_encode([
        'seoTitle' => __a('SEO başlığı', 'SEO title'), 'seoTitleHelp' => __a('Tarayıcı sekmesinde ve arama sonuçlarında görünen başlık. Boş bırakılırsa “Başlık · Site adı” kullanılır.', 'The title shown in the browser tab and search results. If empty, “Title · Site name” is used.'),
        'ogTitle' => __a('Paylaşım görseli', 'Sharing image'), 'ogHelp' => __a('Bağlantı sosyal medyada paylaşıldığında görünen görsel. Boş bırakılırsa kapak görseli, o da yoksa sitenin genel paylaşım görseli kullanılır.', 'The image shown when the link is shared on social media. If empty, the cover image is used, otherwise the site’s default sharing image.'),
        'ogChoose' => __a('Medyadan seç', 'Choose from media'), 'ogRemove' => __a('Kaldır', 'Remove'), 'ogNone' => __a('Kapak görseli kullanılıyor', 'Using the cover image'),
        'tplTitle' => __a('Sayfa şablonu', 'Page template'), 'tplInherit' => __a('Ortak ayar (Temalar › Proje sayfası)', 'Shared setting (Themes › Project page)'),
        'tplHelp' => __a('Yalnızca bu sayfayı değiştirir. Boş bırakılırsa listenin ya da sitenin ayarı kullanılır.', 'Changes this page only. If left empty, the list’s or the site’s setting is used.'),
        'settings' => __a('Ayarlar', 'Settings'), 'back' => __a('Sayfalar', 'Pages'), 'save' => __a('Kaydet', 'Save'), 'saving' => __a('Kaydediliyor…', 'Saving…'),
        'saved' => __a('Kaydedildi.', 'Saved.'), 'error' => __a('Kaydedilemedi. Bağlantınızı kontrol edin.', 'Could not save. Check your connection.'),
        'editContent' => __a('Fotoğrafları ve içeriği düzenle', 'Edit photos and content'), 'editPage' => __a('Sayfayı düzenle', 'Edit page'), 'editList' => __a('Liste sayfalarını yönet', 'Manage list pages'), 'editLink' => __a('Menü bağlantısını düzenle', 'Edit menu link'),
        'design' => __a('Tasarımı düzenle', 'Edit design'), 'openSite' => __a('Sitede aç', 'Open on site'), 'menuHide' => __a('Menüden çıkar', 'Remove from menu'), 'menuShow' => __a('Menüye ekle', 'Add to menu'),
        'delete' => __a('Kalıcı olarak sil', 'Delete permanently'), 'deleteConfirm' => __a('“%s” kalıcı olarak silinsin mi? Bu geri alınamaz.', 'Delete “%s” permanently? This cannot be undone.'),
        'title' => __a('Başlık', 'Title'), 'name' => __a('Ad', 'Name'), 'langs' => __a('Dil', 'Language'), 'fallbackSlug' => __a('Boş bırakılırsa %s adresi kullanılır.', 'If left empty, the %s address is used.'),
        'visibility' => __a('Görünürlük', 'Visibility'), 'published' => __a('Yayında', 'Published'), 'unlisted' => __a('Gizli bağlantı', 'Unlisted'), 'draft' => __a('Taslak', 'Draft'),
        'visHelp' => __a('Gizli bağlantı: listelerde görünmez, bağlantıyı bilen açar. Taslak: yalnızca siz görürsünüz.', 'Unlisted: hidden from lists, open to anyone with the link. Draft: only you can see it.'),
        'password' => __a('Parola', 'Password'), 'passwordHelp' => __a('Boş bırakılırsa sayfa parolasız açılır.', 'Leave empty for no password.'),
        'passwordSet' => __a('Parola ayarlı — değiştirmek için yenisini yazın', 'Password set — type a new one to change it'),
        'passwordSetHelp' => __a('Parola güvenli biçimde (hash) saklanır, sonradan görüntülenemez.', 'The password is stored hashed and cannot be shown again.'),
        'removePassword' => __a('Parolayı kaldır', 'Remove password'),
        'address' => __a('Adres', 'Address'), 'addressHelp' => __a('Adresi değiştirirseniz eski adres kendiliğinden yeni adrese yönlendirilir; paylaşılmış bağlantılar kırılmaz.', 'If you change the address, the old one redirects to the new one automatically, so shared links keep working.'),
        'listAddressHelp' => __a('Liste sayfalarının adresi URL Yönetimi’nden değiştirilir; menü bağlantıları da birlikte güncellenir.', 'List page addresses are changed in URL Management, which also updates the menu links.'),
        'urlManage' => __a('URL Yönetimi', 'URL Management'),
        'desc' => __a('Kısa açıklama', 'Short description'), 'descHelp' => __a('Sayfada kısa açıklama olarak ve arama sonuçlarında görünür. 160 karakterin altında tutun.', 'Shown as the short description and in search results. Keep it under 160 characters.'),
        'listDescHelp' => __a('Liste sayfasının üstünde gösterilen kısa giriş yazısı.', 'A short intro shown at the top of the list page.'),
        'serp' => __a('Arama sonucu önizlemesi', 'Search result preview'), 'serpEmpty' => __a('Açıklama yok: arama motorları sitenin genel açıklamasını gösterir.', 'No description: search engines will show the site’s general description.'),
        'chars' => __a('karakter', 'characters'), 'cover' => __a('Kapak görseli', 'Cover image'), 'coverHelp' => __a('Listelerde bu sayfayı temsil eden görsel. Fotoğrafları düzenlerken değiştirilir.', 'The image that represents this page in lists. Change it while editing photos.'),
        'noCover' => __a('Kapak görseli yok', 'No cover image'), 'menuOn' => __a('Menüde görünüyor', 'Shown in the menu'), 'menuOff' => __a('Menüde görünmüyor', 'Not shown in the menu'),
        'noPreview' => __a('Bu bir dış bağlantı; önizleme yok.', 'This is an external link; there is no preview.'), 'reorderErr' => __a('Sıralama kaydedilemedi.', 'Could not save the order.'),
        'unsaved' => __a('Kaydedilmemiş değişiklikler kaybolacak. Devam edilsin mi?', 'Unsaved changes will be lost. Continue?'),
        'newLink' => __a('Yeni menü bağlantısı', 'New menu link'), 'linkUrl' => __a('Adres', 'Address'),
        'linkUrlHelp' => __a('Site içi bir sayfa için /tr/harita/ gibi, başka bir site için https://… ile başlayan tam adres.', 'For a page on this site use e.g. /en/map/; for another site, a full address starting with https://…'),
        'menuDelete' => __a('Menüden sil', 'Delete from menu'), 'menuDeleteConfirm' => __a('“%s” menüden silinsin mi?', 'Delete “%s” from the menu?'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    var wrap = document.getElementById('pgWrap'), frame = document.getElementById('pgFrame'), treePane = document.getElementById('pgTreePane'), settings = document.getElementById('pgSettings');
    var menu = document.getElementById('pgMenu'), createMenu = document.getElementById('pgCreateMenu');
    var state = { selected: null, dirty: false };

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function post(data, url) {
        var fd = new FormData();
        fd.append('csrf_token', CSRF);
        Object.keys(data).forEach(function (k) {
            if (Array.isArray(data[k])) data[k].forEach(function (v) { fd.append(k + '[]', v); });
            else fd.append(k, data[k]);
        });
        return fetch(url || 'pages.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }

    // Paylaşım görseli alanını günceller (ayarlar paneli)
    function setOg(path) {
        var inp = document.getElementById('pgOgInput'); if (!inp) return;
        inp.value = path; state.dirty = true;
        var img = document.getElementById('pgOgImg'), none = document.getElementById('pgOgNone'), rm = settings.querySelector('[data-pg-og-remove]');
        img.hidden = !path; if (path) img.src = BASE + path;
        none.hidden = !!path; rm.hidden = !path;
    }

    // ── Sekmeler: tablo (varsayılan) ve menü ağacı + önizleme ──
    function showTab(name) {
        document.querySelectorAll('[data-pg-tab]').forEach(function (b) { var on = b.dataset.pgTab === name; b.setAttribute('aria-selected', on ? 'true' : 'false'); b.classList.toggle('active', on); });
        document.getElementById('pgViewList').hidden = name !== 'list';
        wrap.hidden = name !== 'tree';
        // Önizleme yalnızca ağaç açıldığında yüklenir
        if (name === 'tree' && !state.selected) select(FIRST);
        try { history.replaceState(null, '', name === 'tree' ? ('pages.php?view=tree' + (state.selected ? '&page=' + encodeURIComponent(state.selected) : '')) : 'pages.php'); } catch (x) {}
    }

    // ── Seçim ve önizleme ──
    function select(key) {
        var n = NODES[key];
        if (!n) return;
        state.selected = key;
        document.querySelectorAll('.pg-node.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
        var li = document.querySelector('.pg-node[data-key="' + key + '"]');
        if (li) {
            li.classList.add('is-selected');
            // Kapalı bir klasörün içindeyse klasörü aç
            var parent = li.parentNode.closest && li.parentNode.closest('.pg-node');
            if (parent && li.parentNode.hidden) { li.parentNode.hidden = false; var ch = parent.querySelector(':scope > .pg-row [data-pg-toggle]'); if (ch) ch.setAttribute('aria-expanded', 'true'); }
        }
        var url = n.preview || '';
        document.getElementById('pgStageTitle').textContent = url ? url.replace(/^https?:\/\/[^/]+/, '') : (n.external || '');
        var open = document.getElementById('pgOpen');
        open.href = url || n.external || '#';
        open.hidden = !(url || n.external);
        var fw = document.getElementById('pgFrameWrap'), msg = fw.querySelector('.pg-noprev');
        if (url) {
            if (msg) msg.remove();
            frame.hidden = false;
            frame.src = url;
        } else {
            frame.hidden = true;
            frame.src = 'about:blank';
            if (!msg) { msg = document.createElement('p'); msg.className = 'pg-noprev'; fw.appendChild(msg); }
            msg.textContent = (n.kind === 'separator' || !n.menuId) ? '' : T.noPreview;
        }
    }

    // ── ⋯ menüsü ──
    function closeMenus() { menu.hidden = true; createMenu.hidden = true; }
    function place(m, btn, alignLeft) {
        m.hidden = false;
        var r = btn.getBoundingClientRect(), w = m.offsetWidth, h = m.offsetHeight;
        var left = alignLeft ? r.left : Math.min(r.right - w, window.innerWidth - w - 8), top = r.bottom + 4;
        if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 4);
        m.style.left = Math.max(8, left) + 'px';
        m.style.top = top + 'px';
        var f = m.querySelector('a,button'); if (f) f.focus();
    }
    function openMenu(btn, key) {
        var n = NODES[key], items = [];
        if (n.kind === 'item') items.push('<a role="menuitem" href="' + esc(n.edit) + '">' + esc(n.standalone ? T.editPage : T.editContent) + '</a>');
        else if (n.kind === 'category') items.push('<a role="menuitem" href="' + esc(n.edit) + '">' + esc(T.editList) + '</a>');
        else if (n.kind === 'static') items.push('<a role="menuitem" href="' + esc(n.edit) + '">' + esc(T.editPage) + '</a>');
        if (n.kind === 'item' || n.kind === 'category' || n.kind === 'link') items.push('<button type="button" role="menuitem" data-pg-settings="' + esc(key) + '">' + esc(T.settings) + '</button>');
        if (n.design) items.push('<a role="menuitem" href="templates.php?design=' + esc(n.design) + (n.preview ? '&view=' + encodeURIComponent(n.preview) : '') + (n.scope ? '&cat=' + encodeURIComponent(n.scope) : '') + '">' + esc(T.design) + '</a>');
        if (n.preview || n.external) items.push('<a role="menuitem" href="' + esc(n.preview || n.external) + '" target="_blank" rel="noopener">' + esc(T.openSite) + ' ↗</a>');
        if (n.menuId) items.push('<hr><button type="button" role="menuitem" data-pg-menu-toggle="' + n.menuId + '">' + esc(n.active ? T.menuHide : T.menuShow) + '</button>');
        else if (n.kind === 'category') items.push('<hr><button type="button" role="menuitem" data-pg-cat-menu="' + n.id + '">' + esc(T.menuShow) + '</button>');
        else if (n.standalone) items.push('<hr><button type="button" role="menuitem" data-pg-page-menu="' + n.id + '">' + esc(T.menuShow) + '</button>');
        if (n.kind === 'item') items.push('<hr><button type="button" role="menuitem" class="is-danger" data-pg-delete="' + esc(key) + '">' + esc(T.delete) + '</button>');
        if (n.kind === 'link' || n.kind === 'separator') items.push('<hr><button type="button" role="menuitem" class="is-danger" data-pg-menu-delete="' + esc(key) + '">' + esc(T.menuDelete) + '</button>');
        menu.innerHTML = items.join('');
        menu.dataset.for = key;
        place(menu, btn, false);
    }

    // ── Ayarlar paneli ──
    function seg(name, cur, opts) {
        return '<div class="pg-seg" role="radiogroup" aria-label="' + esc(T.visibility) + '">' + opts.map(function (o) { return '<label><input type="radio" name="' + name + '" value="' + o[0] + '"' + (o[0] === cur ? ' checked' : '') + '><span>' + esc(o[1]) + '</span></label>'; }).join('') + '</div>';
    }
    // Dil sekmeleri: panelde aynı anda tek dilin alanları görünür
    function langTabs(f, fields) {
        if (LANGS.length < 2) return '';
        return '<div class="pg-langs" role="tablist" aria-label="' + esc(T.langs) + '">' + LANGS.map(function (l) {
            var miss = fields.some(function (k) { return !f[k + '_' + l.code] && LANGS.some(function (o) { return f[k + '_' + o.code]; }); });
            return '<button type="button" role="tab" class="pg-lang' + (l.code === paneLang ? ' is-on' : '') + '" data-pg-lang="' + esc(l.code) + '" aria-selected="' + (l.code === paneLang) + '">' + esc(l.flag + ' ' + l.code.toUpperCase()) + (miss ? '<i class="pg-lang-dot" aria-hidden="true"></i>' : '') + '</button>';
        }).join('') + '</div>';
    }
    function langPane(code, inner) {
        return '<div class="pg-lpane" data-pg-pane="' + esc(code) + '"' + (code === paneLang ? '' : ' hidden') + '>' + inner + '</div>';
    }
    function fallbackName(code) {
        var l = LANGS.filter(function (x) { return x.code === code; })[0], fb = l && l.fallback;
        var f = LANGS.filter(function (x) { return x.code === fb; })[0];
        return f ? f.name : 'English';
    }
    function showSettings(key) {
        var n = NODES[key], f = n.f, html = '';
        select(key);
        html += '<button type="button" class="pg-back" data-pg-back>← ' + esc(T.back) + '</button>';
        html += '<h2>' + esc(n.label) + '</h2><p class="pg-sub">' + esc(T.settings) + '</p><form id="pgForm" novalidate>';
        if (n.kind === 'item') {
            html += langTabs(f, ['title', 'description']);
            html += LANGS.map(function (l) {
                var c = l.code, opt = (c !== 'tr' && c !== 'en');
                return langPane(c,
                    '<div class="pg-field"><label for="pgTitle_' + c + '">' + esc(T.title) + '</label><input class="pg-in" id="pgTitle_' + c + '" name="title_' + c + '" dir="' + esc(l.dir) + '" value="' + esc(f['title_' + c]) + '"></div>'
                    + '<div class="pg-field"><label for="pgSeo_' + c + '">' + esc(T.seoTitle) + '</label><input class="pg-in" id="pgSeo_' + c + '" name="seo_title_' + c + '" dir="' + esc(l.dir) + '" maxlength="120" value="' + esc(f['seo_title_' + c] || '') + '"><small>' + esc(T.seoTitleHelp) + '</small></div>'
                    + '<div class="pg-field"><label for="pgSlug_' + c + '">' + esc(T.address) + '</label><div class="pg-url"><span>/' + esc(c) + '/</span><input class="pg-in" id="pgSlug_' + c + '" name="slug_' + c + '" value="' + esc(f['slug_' + c]) + '" spellcheck="false"><span class="pg-url-end">/</span></div>'
                    + '<small>' + esc(T.addressHelp) + (opt ? ' ' + esc(T.fallbackSlug.replace('%s', fallbackName(c))) : '') + '</small></div>'
                    + '<div class="pg-field"><label for="pgDesc_' + c + '">' + esc(T.desc) + '</label><textarea class="pg-in" id="pgDesc_' + c + '" name="description_' + c + '" dir="' + esc(l.dir) + '" maxlength="1000">' + esc(f['description_' + c]) + '</textarea><small>' + esc(T.descHelp) + ' <span class="pg-chars" data-for="pgDesc_' + c + '"></span></small></div>');
            }).join('');
            html += '<p class="pg-flabel" style="margin-top:4px">' + esc(T.serp) + '</p><div class="pg-serp" aria-live="polite"><div class="pg-serp-url" id="pgSerpUrl"></div><div class="pg-serp-title" id="pgSerpTitle"></div><div class="pg-serp-desc" id="pgSerpDesc"></div></div>';
            html += '<div class="pg-field" style="margin-top:16px"><span class="pg-flabel">' + esc(T.visibility) + '</span>' + seg('status', f.status, [['published', T.published], ['unlisted', T.unlisted], ['draft', T.draft]]) + '<small>' + esc(T.visHelp) + '</small></div>';
            html += '<div class="pg-field"><label for="pgPass">' + esc(T.password) + '</label><input class="pg-in" id="pgPass" type="password" name="access_password" autocomplete="new-password" value=""' + (n.locked ? ' placeholder="' + esc(T.passwordSet) + '"' : '') + '>'
                + (n.locked ? '<label style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;cursor:pointer"><input type="checkbox" name="remove_access_password" value="1"> ' + esc(T.removePassword) + '</label>' : '')
                + '<small>' + esc(n.locked ? T.passwordSetHelp : T.passwordHelp) + '</small></div>';
            if (!n.standalone) html += '<p class="pg-flabel" style="margin-top:16px">' + esc(T.tplTitle) + '</p>' + TPL.map(function (d) {
                return '<div class="pg-field"><label for="pgTpl_' + d.key + '">' + esc(d.label) + '</label><select class="pg-in" id="pgTpl_' + d.key + '" name="' + d.key + '">'
                    + '<option value="">' + esc(T.tplInherit) + '</option>'
                    + d.choices.map(function (c) { return '<option value="' + esc(c[0]) + '"' + (f[d.key] === c[0] ? ' selected' : '') + '>' + esc(c[1]) + '</option>'; }).join('')
                    + '</select></div>';
            }).join('') + '<small class="pg-sub" style="display:block;margin:-6px 0 14px">' + esc(T.tplHelp) + '</small>';
            html += '<div class="pg-field"><span class="pg-flabel">' + esc(T.ogTitle) + '</span><div class="pg-cover"><img id="pgOgImg" alt=""' + (f.og_image ? ' src="' + esc(BASE + f.og_image.replace(/^\/+/, '')) + '"' : ' hidden') + '><span class="pg-sub" id="pgOgNone" style="margin:0"' + (f.og_image ? ' hidden' : '') + '>' + esc(T.ogNone) + '</span>'
                + '<input type="hidden" name="og_image" id="pgOgInput" value="' + esc(f.og_image || '') + '"><button type="button" class="btn btn-secondary btn-sm" data-pg-og-choose>' + esc(T.ogChoose) + '</button><button type="button" class="btn btn-secondary btn-sm" data-pg-og-remove' + (f.og_image ? '' : ' hidden') + '>' + esc(T.ogRemove) + '</button></div><small>' + esc(T.ogHelp) + '</small></div>';
            html += '<div class="pg-field"><span class="pg-flabel">' + esc(T.cover) + '</span><div class="pg-cover">' + (n.cover ? '<img src="' + esc(n.cover) + '" alt="">' : '<span class="pg-sub" style="margin:0">' + esc(T.noCover) + '</span>') + '<a class="btn btn-secondary btn-sm" href="' + esc(n.edit) + '">' + esc(n.standalone ? T.editPage : T.editContent) + '</a></div><small>' + esc(T.coverHelp) + '</small></div>';
        } else if (n.kind === 'link') {
            html += langTabs(f, ['title', 'url']);
            html += LANGS.map(function (l) {
                var c = l.code;
                return langPane(c,
                    '<div class="pg-field"><label for="pgLinkTitle_' + c + '">' + esc(T.name) + '</label><input class="pg-in" id="pgLinkTitle_' + c + '" name="title_' + c + '" dir="' + esc(l.dir) + '" value="' + esc(f['title_' + c]) + '"></div>'
                    + '<div class="pg-field"><label for="pgLinkUrl_' + c + '">' + esc(T.linkUrl) + '</label><input class="pg-in" id="pgLinkUrl_' + c + '" name="url_' + c + '" value="' + esc(f['url_' + c]) + '" spellcheck="false" placeholder="/' + esc(c) + '/…"><small>' + esc(T.linkUrlHelp) + '</small></div>');
            }).join('');
        } else {
            html += langTabs(f, ['name', 'description']);
            html += LANGS.map(function (l) {
                var c = l.code;
                return langPane(c,
                    '<div class="pg-field"><label for="pgName_' + c + '">' + esc(T.name) + '</label><input class="pg-in" id="pgName_' + c + '" name="name_' + c + '" dir="' + esc(l.dir) + '" value="' + esc(f['name_' + c]) + '"></div>'
                    + '<div class="pg-field"><span class="pg-flabel">' + esc(T.address) + '</span><div style="font-size:13px;font-family:var(--font-mono, monospace);color:var(--text-main)">/' + esc(c) + '/' + esc(f['slug_' + c]) + '/</div><small>' + esc(T.listAddressHelp) + ' <a href="page-names.php">' + esc(T.urlManage) + ' ›</a></small></div>'
                    + '<div class="pg-field"><label for="pgDesc_' + c + '">' + esc(T.desc) + '</label><textarea class="pg-in" id="pgDesc_' + c + '" name="description_' + c + '" dir="' + esc(l.dir) + '" maxlength="1000">' + esc(f['description_' + c]) + '</textarea><small>' + esc(T.listDescHelp) + '</small></div>');
            }).join('');
            html += '<div class="pg-field"><span class="pg-flabel">' + esc(n.active ? T.menuOn : T.menuOff) + '</span>'
                + (n.menuId ? '<button type="button" class="btn btn-secondary btn-sm" data-pg-menu-toggle="' + n.menuId + '">' + esc(n.active ? T.menuHide : T.menuShow) + '</button>' : '<button type="button" class="btn btn-secondary btn-sm" data-pg-cat-menu="' + n.id + '">' + esc(T.menuShow) + '</button>') + '</div>';
        }
        html += '<div class="pg-save"><span class="pg-msg" id="pgMsg" role="status"></span><button type="submit" class="btn btn-primary">' + esc(T.save) + '</button></div></form>';
        settings.innerHTML = html;
        treePane.hidden = true;
        settings.hidden = false;
        state.dirty = false;
        settings.scrollTop = 0;
        updateSerp();
        var first = settings.querySelector('input,textarea'); if (first) first.focus();
    }
    function closeSettings(force) {
        if (!force && state.dirty) {
            adminConfirm(T.unsaved, { ok: LANG === 'en' ? 'Discard changes' : <?= json_encode(__a('Değişiklikleri at', 'Discard changes'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?> }).then(function (ok) { if (ok) closeSettings(true); });
            return;
        }
        settings.hidden = true;
        settings.innerHTML = '';
        treePane.hidden = false;
        state.dirty = false;
        var sel = state.selected && document.querySelector('.pg-node[data-key="' + state.selected + '"] .pg-select');
        if (sel) sel.focus();
    }
    function updateSerp() {
        var form = document.getElementById('pgForm'); if (!form) return;
        settings.querySelectorAll('.pg-chars').forEach(function (c) { var t = document.getElementById(c.getAttribute('data-for')); if (t) c.textContent = '(' + t.value.length + ' ' + T.chars + ')'; });
        var tEl = form.elements['title_' + paneLang]; if (!tEl || !document.getElementById('pgSerpUrl')) return;
        var seoEl = form.elements['seo_title_' + paneLang];
        var title = (seoEl && seoEl.value) || tEl.value || LANGS.map(function (l) { return form.elements['title_' + l.code] ? form.elements['title_' + l.code].value : ''; }).filter(Boolean)[0] || '';
        var slug = form.elements['slug_' + paneLang].value;
        var desc = form.elements['description_' + paneLang].value;
        document.getElementById('pgSerpUrl').textContent = SITE_HOST + ' › ' + paneLang + ' › ' + slug;
        document.getElementById('pgSerpTitle').textContent = title;
        var d = document.getElementById('pgSerpDesc');
        d.textContent = desc ? (desc.length > 160 ? desc.slice(0, 157) + '…' : desc) : T.serpEmpty;
        d.classList.toggle('is-empty', !desc);
    }
    settings.addEventListener('click', function (e) {
        var b = e.target.closest('[data-pg-lang]'); if (!b) return;
        paneLang = b.getAttribute('data-pg-lang');
        settings.querySelectorAll('[data-pg-lang]').forEach(function (x) { var on = x === b; x.classList.toggle('is-on', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
        settings.querySelectorAll('[data-pg-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-pg-pane') !== paneLang; });
        // Önizleme de seçilen dildeki sayfayı göstersin
        var sn = NODES[state.selected], u = sn && sn.f && sn.f['url_' + paneLang];
        if (u) { frame.src = u; document.getElementById('pgStageTitle').textContent = u.replace(/^https?:\/\/[^/]+/, ''); document.getElementById('pgOpen').href = u; }
        updateSerp();
    });
    settings.addEventListener('input', function (e) {
        state.dirty = true;
        e.target.classList.remove('is-invalid');
        var u = e.target.closest('.pg-url'); if (u) u.classList.remove('is-invalid');
        updateSerp();
    });
    settings.addEventListener('change', function () { state.dirty = true; });
    settings.addEventListener('submit', function (e) {
        e.preventDefault();
        var form = e.target, key = state.selected, n = NODES[key], msg = document.getElementById('pgMsg');
        var data = n.kind === 'link' ? { action: 'pages_link_save', menu_id: n.menuId } : { action: n.kind === 'item' ? 'pages_item_save' : 'pages_cat_save', id: n.id };
        Array.prototype.forEach.call(form.elements, function (el) { if (!el.name || ((el.type === 'radio' || el.type === 'checkbox') && !el.checked)) return; data[el.name] = el.value; });
        msg.className = 'pg-msg'; msg.textContent = T.saving;
        post(data).then(function (res) {
            if (!res.ok) {
                msg.className = 'pg-msg is-err'; msg.textContent = res.error || T.error;
                var bad = res.field && form.elements[res.field];
                var badLang = res.field && res.field.split('_').pop(), tab = badLang && settings.querySelector('[data-pg-lang="' + badLang + '"]');
                if (tab && badLang !== paneLang) tab.click();
                if (bad) { bad.classList.add('is-invalid'); var u = bad.closest('.pg-url'); if (u) u.classList.add('is-invalid'); bad.focus(); }
                return;
            }
            state.dirty = false;
            // Menü bağlantısının adı ve yeri ağaçta değişebilir; sayfa yeniden yüklenip bağlantı seçili açılır
            if (n.kind === 'link') { location.href = 'pages.php?page=' + encodeURIComponent('menu-' + res.menu_id); return; }
            msg.className = 'pg-msg is-ok'; msg.textContent = T.saved;
            // Ekrandaki ad, rozet ve önizleme güncellensin
            Object.keys(data).forEach(function (k) { if (n.f && k in n.f) n.f[k] = data[k]; });
            var labelOf = function (prefix) { return n.f[prefix + LANG] || LANGS.map(function (l) { return n.f[prefix + l.code]; }).filter(Boolean)[0] || ''; };
            if (n.kind === 'item') {
                n.status = n.f.status; n.locked = !!res.locked; n.f.has_password = n.locked; n.f.access_password = '';
                n.label = labelOf('title_');
                n.preview = (res.urls && res.urls[LANG]) || n.preview;
                if (res.slugs) Object.keys(res.slugs).forEach(function (c) { if (form.elements['slug_' + c]) { form.elements['slug_' + c].value = res.slugs[c]; n.f['slug_' + c] = res.slugs[c]; } });
            } else {
                n.label = labelOf('name_');
            }
            var row = document.querySelector('.pg-node[data-key="' + key + '"] > .pg-row .pg-select');
            if (row) {
                row.querySelector('.pg-label').textContent = n.label;
                if (n.kind === 'item') {
                    row.querySelectorAll('.pg-badge').forEach(function (b) { b.remove(); });
                    if (n.status !== 'published') row.insertAdjacentHTML('beforeend', '<span class="pg-badge pg-badge-' + n.status + '">' + esc(n.status === 'draft' ? T.draft : T.unlisted) + '</span>');
                    if (n.locked) row.insertAdjacentHTML('beforeend', '<span class="pg-badge">🔒</span>');
                }
            }
            settings.querySelector('h2').textContent = n.label;
            updateSerp();
            select(key);
        }).catch(function () { msg.className = 'pg-msg is-err'; msg.textContent = T.error; });
    });

    // ── Tıklamalar ──
    document.addEventListener('click', function (e) {
        var t;
        if ((t = e.target.closest('#pgCreateBtn'))) { e.stopPropagation(); if (createMenu.hidden) { menu.hidden = true; place(createMenu, t, true); } else closeMenus(); return; }
        if (!e.target.closest('.pg-menu') && !e.target.closest('[data-pg-more]')) closeMenus();
        if ((t = e.target.closest('[data-pg-more]'))) { e.stopPropagation(); if (!menu.hidden && menu.dataset.for === t.dataset.pgMore) { closeMenus(); return; } openMenu(t, t.dataset.pgMore); return; }
        if ((t = e.target.closest('[data-pg-og-choose]'))) {
            openMediaModalForCallback(function (path) { setOg(String(path || '').replace(/^\/+/, '')); });
            return;
        }
        if ((t = e.target.closest('[data-pg-og-remove]'))) { setOg(''); return; }
        if ((t = e.target.closest('[data-pg-tab]'))) { showTab(t.dataset.pgTab); return; }
        if ((t = e.target.closest('[data-pg-open]'))) { select(t.dataset.pgOpen); showTab('tree'); return; }
        if ((t = e.target.closest('[data-pg-settings]'))) { closeMenus(); showTab('tree'); showSettings(t.dataset.pgSettings); return; }
        if ((t = e.target.closest('[data-pg-back]'))) { closeSettings(false); return; }
        if ((t = e.target.closest('[data-pg-toggle]'))) {
            var ul = t.closest('.pg-node').querySelector(':scope > .pg-children'), open = t.getAttribute('aria-expanded') !== 'true';
            t.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (ul) ul.hidden = !open;
            return;
        }
        if ((t = e.target.closest('[data-pg-select]'))) { select(t.dataset.pgSelect); return; }
        if ((t = e.target.closest('[data-pg-dev]'))) {
            wrap.classList.toggle('is-phone', t.dataset.pgDev === 'phone');
            document.querySelectorAll('[data-pg-dev]').forEach(function (b) { b.setAttribute('aria-pressed', b === t ? 'true' : 'false'); });
            return;
        }
        if ((t = e.target.closest('[data-pg-menu-toggle]'))) {
            closeMenus();
            var tk = t.closest('#pgMenu') ? menu.dataset.for : state.selected;
            post({ action: 'pages_menu_toggle', menu_id: t.dataset.pgMenuToggle }).then(function (r) { if (r.ok) location.href = 'pages.php?page=' + encodeURIComponent(tk || ''); });
            return;
        }
        if ((t = e.target.closest('[data-pg-cat-menu]'))) {
            closeMenus();
            var ck = t.closest('#pgMenu') ? menu.dataset.for : state.selected;
            post({ action: 'pages_cat_menu_add', id: t.dataset.pgCatMenu }).then(function (r) { if (r.ok) location.href = 'pages.php?page=' + encodeURIComponent(ck || ''); });
            return;
        }
        if ((t = e.target.closest('[data-pg-page-menu]'))) {
            closeMenus();
            var pk = menu.dataset.for || state.selected;
            post({ action: 'pages_page_menu_add', id: t.dataset.pgPageMenu }).then(function (r) { if (r.ok) location.href = 'pages.php?view=tree&page=' + encodeURIComponent(pk || ''); else adminToast(T.error, 'error'); });
            return;
        }
        if ((t = e.target.closest('[data-pg-new-link]'))) {
            closeMenus();
            NODES.__newlink = { kind: 'link', menuId: 0, label: T.newLink, f: {} };
            showSettings('__newlink');
            return;
        }
        if ((t = e.target.closest('[data-pg-new-sep]'))) {
            closeMenus();
            post({ action: 'pages_separator_add' }).then(function (r) { if (r.ok) location.href = 'pages.php?page=' + encodeURIComponent('menu-' + r.menu_id); else alert(T.error); }).catch(function () { alert(T.error); });
            return;
        }
        if ((t = e.target.closest('[data-pg-menu-delete]'))) {
            var dn = NODES[t.dataset.pgMenuDelete];
            closeMenus();
            if (!dn) return;
            adminConfirm(T.menuDeleteConfirm.replace('%s', dn.label)).then(function (ok) {
                if (!ok) return;
                post({ action: 'pages_menu_delete', menu_id: dn.menuId }).then(function (r) { if (r.ok) location.href = 'pages.php'; else adminToast(r.error || T.error, 'error'); }).catch(function () { adminToast(T.error, 'error'); });
            });
            return;
        }
        if ((t = e.target.closest('[data-pg-delete]'))) {
            var n = NODES[t.dataset.pgDelete];
            closeMenus();
            adminConfirm(T.deleteConfirm.replace('%s', n.label)).then(function (ok) {
                if (ok) window.adminPost('item-delete.php?id=' + n.id + '&return=pages');
            });
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || (document.getElementById('adminDialog') || {}).open) return;
        if (!menu.hidden || !createMenu.hidden) { closeMenus(); return; }
        if (!settings.hidden) closeSettings(false);
    });
    window.addEventListener('beforeunload', function (e) { if (state.dirty) { e.preventDefault(); e.returnValue = ''; } });

    // ── Sürükle-bırak sıralama (menü satırları ve liste içindeki sayfalar) ──
    var drag = null;
    function clearDrop() { document.querySelectorAll('.drop-before,.drop-after').forEach(function (x) { x.classList.remove('drop-before', 'drop-after'); }); }
    document.addEventListener('dragstart', function (e) {
        var li = e.target.closest && e.target.closest('.pg-node[draggable="true"]');
        if (!li) return;
        e.stopPropagation();
        drag = li;
        li.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', li.dataset.key); } catch (x) {}
    });
    document.addEventListener('dragover', function (e) {
        if (!drag) return;
        var li = e.target.closest && e.target.closest('.pg-node');
        while (li && li.parentNode !== drag.parentNode) li = li.parentNode.closest('.pg-node');
        if (!li || li === drag) return;
        e.preventDefault();
        var r = li.querySelector(':scope > .pg-row').getBoundingClientRect(), after = (e.clientY - r.top) > r.height / 2;
        clearDrop();
        li.classList.add(after ? 'drop-after' : 'drop-before');
    });
    document.addEventListener('drop', function (e) {
        if (!drag) return;
        var li = document.querySelector('.drop-before, .drop-after');
        if (!li || li.parentNode !== drag.parentNode) return;
        e.preventDefault();
        li.parentNode.insertBefore(drag, li.classList.contains('drop-after') ? li.nextSibling : li);
        clearDrop();
        saveOrder(drag.parentNode);
    });
    document.addEventListener('dragend', function () {
        if (drag) drag.classList.remove('is-dragging');
        drag = null;
        clearDrop();
    });
    function saveOrder(ul) {
        var target = ul.dataset.sort, ids = [];
        Array.prototype.forEach.call(ul.children, function (li) {
            var n = NODES[li.dataset.key];
            if (n) ids.push(target === 'menus' ? n.menuId : n.id);
        });
        post({ target: target, ids: ids }, 'api-reorder.php').then(function (r) { if (!r.success) alert(T.reorderErr); }).catch(function () { alert(T.reorderErr); });
    }

    // ?page= (düzenleyiciden dönüş) ya da ?view=tree ağacı açar; yoksa tablo görünür
    var qs = new URLSearchParams(location.search), q = qs.get('page');
    if (q && NODES[q]) select(q);
    showTab(q || qs.get('view') === 'tree' ? 'tree' : 'list');
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
