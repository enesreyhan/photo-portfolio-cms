<?php
/**
 * admin/api-search.php
 * Yönetim Paneli Evrensel Canlı Arama (Global Instant Search API)
 * Tüm projeleri, serileri, tekil fotoğrafları, jurnal yazılarını ve menüleri arar.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q, 'UTF-8') < 1) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$pdo = DB::getConnection();
$likeQ = '%' . $q . '%';

$typeMap = [
    'page' => 'Sayfa'
];

$catRows = $pdo->query("SELECT `key_slug`, `name_tr`, `name_en` FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($catRows as $cr) {
    $typeMap[$cr['key_slug']] = $cr['name_tr'] ?: $cr['name_en'];
}

$results = [];

// 1. Statik Menü / Sayfa Başlıkları Araması
$systemPages = [
    ['title' => 'Ana Sayfa Seçkisi', 'sub' => 'Ana Sayfa Fotoğraf ve Hikaye Seçkisi Yönetimi', 'url' => url('admin/homepage.php'), 'badge' => 'Sayfa'],
    ['title' => 'Portfolyo', 'sub' => 'Tüm Portfolyo Çalışmaları ve Kategori Filtreleri', 'url' => url('admin/portfolio.php'), 'badge' => 'Menü'],
    ['title' => 'Kategoriler', 'sub' => 'Portfolyo Kategorilerini Ekle, Düzenle veya Sil', 'url' => url('admin/categories.php'), 'badge' => 'Ayar'],
];
foreach ($catRows as $cr) {
    $cLabel = $cr['name_tr'] ?: $cr['name_en'];
    $systemPages[] = [
        'title' => $cLabel,
        'sub'   => $cLabel . ' Kategorisi Çalışmaları',
        'url'   => url('admin/portfolio.php?cat=' . urlencode($cr['key_slug'])),
        'badge' => 'Kategori'
    ];
}
$systemPages = array_merge($systemPages, [
    ['title' => 'Blog / Yazılar Yönetimi', 'sub' => 'Makaleler, Saha Günlükleri ve Haberler Listesi', 'url' => url('admin/blog.php'), 'badge' => 'Menü'],
    ['title' => 'Yeni Blog Yazısı Ekle', 'sub' => 'Editoryal Blog ve Makale Yazım Stüdyosu', 'url' => url('admin/blog-edit.php'), 'badge' => 'Yazı'],
    ['title' => 'Gelen Mesajlar', 'sub' => 'İletişim Formundan Gelen Mesajlar', 'url' => url('admin/messages.php'), 'badge' => 'Menü'],
    ['title' => 'Hakkımda (Biyografi)', 'sub' => 'Biyografi, Kronoloji ve Saha Görevleri', 'url' => url('admin/about.php'), 'badge' => 'Menü'],
    ['title' => 'Medya Kütüphanesi', 'sub' => 'Görsel Arşivi ve Dosyalar', 'url' => url('admin/media.php'), 'badge' => 'Menü'],
    ['title' => 'Görsel Dönüştürücü', 'sub' => 'Toplu AVIF & WebP Optimizasyonu', 'url' => url('admin/convert-images.php'), 'badge' => 'Araçlar'],
    ['title' => 'Temalar', 'sub' => 'Tema seçimi ve Özelleştir: renkler, yazı tipleri, logo, menü, alt bilgi ve sayfalar', 'url' => url('admin/templates.php'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › Ana sayfa', 'sub' => 'Ana sayfa düzeni, slayt, yazılar ve renkler', 'url' => url('admin/templates.php?design=home'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › Proje listeleri', 'sub' => 'Portfolyo liste sayfalarının düzeni', 'url' => url('admin/templates.php?design=lists'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › Proje sayfası', 'sub' => 'Proje, seri ve tekil sayfa düzeni', 'url' => url('admin/templates.php?design=project'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › Blog', 'sub' => 'Blog sayfasının düzeni', 'url' => url('admin/templates.php?design=blog'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › İletişim', 'sub' => 'İletişim sayfasının düzeni ve form', 'url' => url('admin/templates.php?design=contact'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › Hakkımda', 'sub' => 'Hakkımda sayfasının düzeni', 'url' => url('admin/templates.php?design=about'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › Site adı ve logo', 'sub' => 'Sol üstte isim, logo ve simge', 'url' => url('admin/templates.php?design=logo'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › Menü', 'sub' => 'Menünün yeri, görünüşü ve hamburger menü', 'url' => url('admin/templates.php?design=menu'), 'badge' => 'Tasarım'],
    ['title' => 'Tasarım › Alt bilgi', 'sub' => 'Telif yazısı, sosyal medya simgeleri ve düzen', 'url' => url('admin/templates.php?design=footer'), 'badge' => 'Tasarım'],
    ['title' => 'Alt bilgideki ek bağlantılar', 'sub' => 'Yalnızca sayfa altında görünen bağlantılar', 'url' => url('admin/settings.php#alt-bilgi-baglantilari'), 'badge' => 'Ayar'],
    ['title' => 'URL Yönetimi (Site Ayarları)', 'sub' => 'Tüm sabit sayfaların ve kategorilerin başlık ve kalıcı adresleri', 'url' => url('admin/page-names.php'), 'badge' => 'Ayar'],
    ['title' => 'Diller & Çeviri Sistemi', 'sub' => 'Aktif site dilleri, varsayılan dil ve çeviri matrisi', 'url' => url('admin/languages.php'), 'badge' => 'Ayar'],
    ['title' => 'Site Ayarları', 'sub' => 'Genel Yapılandırma, İletişim, Blog, Sosyal Medya, SEO ve Güvenlik', 'url' => url('admin/settings.php'), 'badge' => 'Ayar'],
    ['title' => 'Menü (Sayfalar)', 'sub' => 'Üst menü sırası, bağlantılar ve ayraçlar', 'url' => url('admin/pages.php'), 'badge' => 'Ayar'],
    ['title' => 'İstatistikler (Google Analytics)', 'sub' => 'Ziyaretçi & Portfolyo Raporları', 'url' => url('admin/stats.php'), 'badge' => 'Rapor'],
]);

foreach ($systemPages as $page) {
    if (mb_stripos($page['title'], $q) !== false || mb_stripos($page['sub'], $q) !== false) {
        $results[] = [
            'type'     => 'menu',
            'title'    => $page['title'],
            'sub'      => $page['sub'],
            'url'      => $page['url'],
            'badge'    => $page['badge'],
            'thumb'    => null,
            'external' => !empty($page['external'])
        ];
    }
}

// 2. Veritabanındaki Tüm İçerikler (Projeler, Seriler, Tekiller, Jurnal)
$stmt = $pdo->prepare("
    SELECT id, type, slug, slug_en, slug_tr, title_tr, title_en, description_tr, description_en, cover_image, location, year, date, status
    FROM `items`
    WHERE `title_tr` LIKE :q 
       OR `title_en` LIKE :q 
       OR `slug` LIKE :q 
       OR `slug_en` LIKE :q 
       OR `slug_tr` LIKE :q 
       OR `description_tr` LIKE :q 
       OR `description_en` LIKE :q 
       OR `location` LIKE :q 
       OR `year` LIKE :q
    ORDER BY `updated_at` DESC
    LIMIT 20
");
$stmt->execute([':q' => $likeQ]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    $title = $item['title_tr'] ?: $item['title_en'] ?: $item['slug'];
    $subParts = [];
    if (!empty($item['year'])) $subParts[] = $item['year'];
    if (!empty($item['location'])) $subParts[] = $item['location'];
    $subParts[] = 'EN: /' . ($item['slug_en'] ?: $item['slug']) . '/';
    $subParts[] = 'TR: /tr/' . ($item['slug_tr'] ?: $item['slug']) . '/';
    if ($item['status'] === 'draft') $subParts[] = 'Taslak';

    $thumbUrl = null;
    if (!empty($item['cover_image'])) {
        $clean = ltrim($item['cover_image'], '/');
        $thumbUrl = str_starts_with($clean, 'http') ? $clean : url($clean);
    }

    $results[] = [
        'type'     => 'item',
        'itemType' => $item['type'],
        'title'    => $title,
        'sub'      => implode(' • ', $subParts),
        'url'      => ($item['type'] === 'blog') ? url('admin/blog-edit.php?id=' . $item['id']) : url('admin/item-edit.php?id=' . $item['id']),
        'badge'    => $typeMap[$item['type']] ?? ucfirst($item['type']),
        'thumb'    => $thumbUrl,
        'status'   => $item['status']
    ];
}

echo json_encode([
    'success' => true,
    'query'   => $q,
    'count'   => count($results),
    'results' => $results
]);
