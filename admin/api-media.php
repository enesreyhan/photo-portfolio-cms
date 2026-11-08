<?php
/**
 * admin/api-media.php
 * Gelişmiş Medya Kütüphanesi Filtreleme, Harita ve Varyant API'si
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/media_metadata.php';
require_once __DIR__ . '/includes/i18n.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
    exit;
}

$pdo = DB::getConnection();
$base_dir = dirname(__DIR__);

// ── 1. Tekil Görsel Detay İsteği (Varyantlar ve Canlı EXIF) ──
if (($_GET['action'] ?? '') === 'details' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM `media` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$id]);
    $m = $stmt->fetch();

    if (!$m) {
        echo json_encode(['success' => false, 'error' => 'Görsel bulunamadı.']);
        exit;
    }

    $req_lang = $_GET['admin_lang'] ?? ($_SESSION['admin_lang'] ?? (($_COOKIE['admin_lang'] ?? 'tr') === 'en' ? 'en' : 'tr'));
    $variants = find_photo_variants($base_dir, $m['file_path'], $req_lang);
    $parsed = parse_media_filename($m['filename']);
    $exif = read_photo_exif($base_dir . '/' . $m['file_path']);

    // Silmeden önce uyarmak için: görselin kullanıldığı çalışma/sayfa/slayt/ayarlar (dosya adı kökü ile aranır)
    $used_in = [];
    $stem = pathinfo($m['filename'] ?: $m['file_path'], PATHINFO_FILENAME);
    if (strlen($stem) >= 4) {
        foreach ($pdo->query("SELECT * FROM `items`")->fetchAll(PDO::FETCH_ASSOC) as $it) {
            foreach ($it as $v) {
                if (is_string($v) && strpos($v, $stem) !== false) { $used_in[] = ($it['title_tr'] ?: $it['title_en'] ?: $it['slug']) . ' (' . $it['type'] . ')'; break; }
            }
        }
        $st = $pdo->prepare("SELECT COUNT(*) FROM `homepage_slides` WHERE `image_path` LIKE ?");
        $st->execute(['%' . $stem . '%']);
        if ((int)$st->fetchColumn() > 0) $used_in[] = __a('Ana sayfa slaytı', 'Homepage slide');
        $st = $pdo->prepare("SELECT COUNT(*) FROM `settings` WHERE `key_value` LIKE ?");
        $st->execute(['%' . $stem . '%']);
        if ((int)$st->fetchColumn() > 0) $used_in[] = __a('Site ayarları / tasarım', 'Site settings / design');
    }

    echo json_encode([
        'success'   => true,
        'used_in'   => $used_in,
        'image'     => $m,
        'parsed'    => $parsed,
        'exif'      => $exif,
        'variants'  => $variants
    ]);
    exit;
}

// ── 2. Yeni Klasör Oluşturma İsteği ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'create_folder') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'CSRF güvenlik hatası.']);
        exit;
    }

    $parent = trim($_POST['parent'] ?? 'uploads');
    $folder_name = slugify($_POST['name'] ?? '');

    if ($folder_name === '') {
        echo json_encode(['success' => false, 'error' => 'Geçersiz klasör adı.']);
        exit;
    }

    $parent = str_replace(['..', "\0"], '', $parent);
    $parent = trim($parent, '/\\');
    if ($parent === '') $parent = 'uploads';

    $full_target = $base_dir . '/' . $parent . '/' . $folder_name;
    if (is_dir($full_target)) {
        echo json_encode(['success' => false, 'error' => 'Bu klasör zaten mevcut.']);
        exit;
    }

    if (@mkdir($full_target, 0755, true)) {
        $rel_folder = $parent . '/' . $folder_name;
        echo json_encode(['success' => true, 'folder' => $rel_folder]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Klasör oluşturulamadı (İzin hatası).']);
    }
    exit;
}

// ── 3. Klasör Listesi ve Kategorileri ──
$req_lang = !empty($_GET['admin_lang']) ? preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['admin_lang'])) : ($admin_lang ?? 'tr');
$admin_lang = $req_lang;
$GLOBALS['admin_lang'] = $req_lang;

$folders_stmt = $pdo->query("
    SELECT folder, COUNT(*) as photo_count 
    FROM `media` 
    WHERE `folder` != '' 
    GROUP BY `folder` 
    ORDER BY `folder` ASC
");
$raw_folders = $folders_stmt->fetchAll();
// Henüz fotoğraf olmayan (yeni oluşturulmuş) klasörler DB'de yok; diskten ekle
$known_folders = array_column($raw_folders, 'folder');
$disk_dirs = array_merge(glob($base_dir . '/uploads/*', GLOB_ONLYDIR) ?: [], glob($base_dir . '/uploads/*/*', GLOB_ONLYDIR) ?: [], glob($base_dir . '/content/*/*', GLOB_ONLYDIR) ?: []);
$sel_dir = trim(str_replace(['..', "\0"], '', $_GET['folder'] ?? ''), '/');
if ($sel_dir !== '' && is_dir($base_dir . '/' . $sel_dir)) $disk_dirs[] = $base_dir . '/' . $sel_dir;
foreach ($disk_dirs as $d) {
    $rel = ltrim(substr($d, strlen($base_dir)), '/');
    if (!in_array($rel, $known_folders, true)) { $raw_folders[] = ['folder' => $rel, 'photo_count' => 0]; $known_folders[] = $rel; }
}

$categories = [];
$custom_cat_rows = $pdo->query("SELECT * FROM `categories` ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
$custom_cat_map = [];
foreach ($custom_cat_rows as $cr) {
    $ck = $cr['key_slug'];
    $label = get_category_localized_name($cr, $req_lang);
    $categories[$ck] = ['label' => $label, 'folders' => []];
    $custom_cat_map[$ck] = $label;
}
$categories['other'] = ['label' => __a('Diğer Klasörler', 'Other Folders'), 'folders' => []];

foreach ($raw_folders as $f) {
    $folder_path = $f['folder'];
    $cnt = (int)$f['photo_count'];
    $basename = basename($folder_path);

    $matched_custom = false;
    foreach ($custom_cat_map as $ck => $clabel) {
        $prefixes = ['content/' . $ck];
        if ($ck === 'project') {
            $prefixes[] = 'content/projects';
        }
        foreach ($prefixes as $pref) {
            if (strpos($folder_path, $pref) === 0) {
                $categories[$ck]['folders'][] = ['path' => $folder_path, 'name' => $basename, 'count' => $cnt];
                $matched_custom = true;
                break 2;
            }
        }
    }
    if (!$matched_custom) {
        $categories['other']['folders'][] = ['path' => $folder_path, 'name' => $basename, 'count' => $cnt];
    }
}

// ── 4. Filtre Parametreleri ──
$selected_folder = trim($_GET['folder'] ?? '');
$search_q        = trim($_GET['q'] ?? '');
$filter_country  = trim($_GET['country'] ?? '');
$filter_city     = trim($_GET['city'] ?? '');
$filter_type     = trim($_GET['type'] ?? '');
$filter_project  = trim($_GET['project'] ?? '');
$filter_year     = trim($_GET['year'] ?? '');
$filter_camera   = trim($_GET['camera'] ?? '');
$filter_aperture = trim($_GET['aperture'] ?? '');
$filter_shutter  = trim($_GET['shutter'] ?? '');
$filter_iso      = trim($_GET['iso'] ?? '');
$sort            = trim($_GET['sort'] ?? 'newest');

$where = ["1=1"];
$params = [];

// Klasör filtresi
if ($selected_folder !== '' && $selected_folder !== 'all') {
    $where[] = "`folder` = ?";
    $params[] = $selected_folder;
}

// Arama filtresi
if ($search_q !== '') {
    $where[] = "(`filename` LIKE ? OR `original_name` LIKE ? OR `camera` LIKE ? OR `city_code` LIKE ? OR `country_code` LIKE ? OR `project_code` LIKE ?)";
    $q_like = '%' . $search_q . '%';
    $params = array_merge($params, [$q_like, $q_like, $q_like, $q_like, $q_like, $q_like]);
}

// Ülke filtresi
if ($filter_country !== '' && $filter_country !== 'all') {
    $where[] = "`country_code` = ?";
    $params[] = strtoupper($filter_country);
}

// Şehir filtresi
if ($filter_city !== '' && $filter_city !== 'all') {
    $where[] = "`city_code` = ?";
    $params[] = strtoupper($filter_city);
}

// Kategori / Tür filtresi (project, series, single)
if ($filter_type !== '' && $filter_type !== 'all') {
    $where[] = "`item_type` = ?";
    $params[] = strtolower($filter_type);
}

// Proje / Seri Kodu filtresi
if ($filter_project !== '' && $filter_project !== 'all') {
    $where[] = "`project_code` = ?";
    $params[] = strtoupper($filter_project);
}

// Yıl filtresi
if ($filter_year !== '' && $filter_year !== 'all') {
    $where[] = "`shot_year` = ?";
    $params[] = $filter_year;
}

// Kamera filtresi
if ($filter_camera !== '' && $filter_camera !== 'all') {
    $where[] = "`camera` LIKE ?";
    $params[] = '%' . $filter_camera . '%';
}

// Diyafram filtresi
if ($filter_aperture !== '' && $filter_aperture !== 'all') {
    if ($filter_aperture === 'f1.4-f2.0') {
        $where[] = "(`aperture_num` >= 1.2 AND `aperture_num` <= 2.0)";
    } elseif ($filter_aperture === 'f2.8') {
        $where[] = "(`aperture_num` >= 2.5 AND `aperture_num` <= 3.2)";
    } elseif ($filter_aperture === 'f4.0') {
        $where[] = "(`aperture_num` >= 3.5 AND `aperture_num` <= 4.5)";
    } elseif ($filter_aperture === 'f5.6') {
        $where[] = "(`aperture_num` >= 5.0 AND `aperture_num` <= 6.3)";
    } elseif ($filter_aperture === 'f8.0') {
        $where[] = "(`aperture_num` >= 7.1 AND `aperture_num` <= 9.0)";
    } elseif ($filter_aperture === 'f11+') {
        $where[] = "`aperture_num` >= 10.0";
    }
}

// Enstantane filtresi
if ($filter_shutter !== '' && $filter_shutter !== 'all') {
    if ($filter_shutter === 'fast') { // 1/1000s ve daha hızlı
        $where[] = "(`shutter_num` > 0 AND `shutter_num` <= 0.001)";
    } elseif ($filter_shutter === 'mid-fast') { // 1/250s - 1/800s
        $where[] = "(`shutter_num` > 0.001 AND `shutter_num` <= 0.004)";
    } elseif ($filter_shutter === 'mid') { // 1/60s - 1/200s
        $where[] = "(`shutter_num` > 0.004 AND `shutter_num` <= 0.017)";
    } elseif ($filter_shutter === 'slow') { // 1/50s ve daha yavaş
        $where[] = "`shutter_num` > 0.017";
    }
}

// ISO filtresi
if ($filter_iso !== '' && $filter_iso !== 'all') {
    if ($filter_iso === '100') {
        $where[] = "(`iso` > 0 AND `iso` <= 160)";
    } elseif ($filter_iso === '200') {
        $where[] = "(`iso` >= 180 AND `iso` <= 320)";
    } elseif ($filter_iso === '400') {
        $where[] = "(`iso` >= 350 AND `iso` <= 640)";
    } elseif ($filter_iso === '800') {
        $where[] = "(`iso` >= 700 AND `iso` <= 1250)";
    } elseif ($filter_iso === '1600') {
        $where[] = "(`iso` >= 1400 AND `iso` <= 2500)";
    } elseif ($filter_iso === '3200+') {
        $where[] = "`iso` >= 3000";
    }
}

// Sıralama
$order_by = "`id` DESC";
if ($sort === 'oldest') {
    $order_by = "`id` ASC";
} elseif ($sort === 'name') {
    $order_by = "`filename` ASC";
} elseif ($sort === 'name_desc') {
    $order_by = "`filename` DESC";
} elseif ($sort === 'size') {
    $order_by = "`file_size` DESC";
} elseif ($sort === 'shot_date_desc') {
    $order_by = "CASE WHEN `shot_date` IS NULL OR `shot_date` = '' THEN 1 ELSE 0 END, `shot_date` DESC, `id` DESC";
} elseif ($sort === 'shot_date_asc') {
    $order_by = "CASE WHEN `shot_date` IS NULL OR `shot_date` = '' THEN 1 ELSE 0 END, `shot_date` ASC, `id` ASC";
}

// Sayfalama: istemci "Daha fazla göster" ile limiti artırır
$limit = max(100, min(20000, (int)($_GET['limit'] ?? 600)));
$sql = "SELECT * FROM `media` WHERE " . implode(' AND ', $where) . " ORDER BY " . $order_by . " LIMIT " . $limit;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM `media` WHERE " . implode(' AND ', $where));
$cnt_stmt->execute($params);
$filtered_total = (int)$cnt_stmt->fetchColumn();

// ── 5. Filtre Dropdown Seçenekleri ve İstatistikler ──
$agg_countries = $pdo->query("
    SELECT country_code, COUNT(*) as cnt 
    FROM `media` 
    WHERE country_code != '' 
    GROUP BY country_code 
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

$country_options = [];
foreach ($agg_countries as $ac) {
    $cc = $ac['country_code'];
    $info = get_country_info($cc);
    $country_options[] = [
        'code'  => $cc,
        'name'  => $info['name'],
        'flag'  => $info['flag'],
        'count' => (int)$ac['cnt']
    ];
}

$agg_cities = $pdo->query("
    SELECT city_code, country_code, COUNT(*) as cnt 
    FROM `media` 
    WHERE city_code != '' 
    GROUP BY city_code 
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

$city_options = [];
foreach ($agg_cities as $act) {
    $code = $act['city_code'];
    $cc = $act['country_code'];
    $lookup_key = ($cc === 'ET' && $code === 'ADA') ? 'ADD' : $code;
    $info = MEDIA_CITIES[$lookup_key] ?? MEDIA_CITIES[$code] ?? ['name' => $code, 'lat' => null, 'lng' => null];
    $c_info = get_country_info($cc);
    $city_options[] = [
        'code'         => $code,
        'name'         => $info['name'],
        'country_code' => $cc,
        'country_name' => $c_info['name'],
        'country_flag' => $c_info['flag'],
        'lat'          => $info['lat'] ?? null,
        'lng'          => $info['lng'] ?? null,
        'count'        => (int)$act['cnt']
    ];
}

$agg_years = $pdo->query("
    SELECT shot_year, COUNT(*) as cnt 
    FROM `media` 
    WHERE shot_year != '' 
    GROUP BY shot_year 
    ORDER BY shot_year DESC
")->fetchAll(PDO::FETCH_ASSOC);

$agg_cameras = $pdo->query("
    SELECT camera, COUNT(*) as cnt 
    FROM `media` 
    WHERE camera != '' 
    GROUP BY camera 
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

$agg_types = $pdo->query("
    SELECT item_type, COUNT(*) as cnt 
    FROM `media` 
    WHERE item_type != '' 
    GROUP BY item_type 
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

$agg_projects = $pdo->query("
    SELECT project_code, item_type, COUNT(*) as cnt 
    FROM `media` 
    WHERE project_code != '' 
    GROUP BY project_code 
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

$project_options = [];
foreach ($agg_projects as $ap) {
    $pcode = $ap['project_code'];
    $title = MEDIA_PROJECTS[$pcode] ?? MEDIA_SERIES[$pcode] ?? $pcode;
    $project_options[] = [
        'code'  => $pcode,
        'type'  => $ap['item_type'],
        'title' => $title,
        'count' => (int)$ap['cnt']
    ];
}

// ── 6. Harita Lokasyonları (Map Locations) ──
$map_locations = [];
foreach ($city_options as $ct) {
    if (!empty($ct['lat']) && !empty($ct['lng'])) {
        // Bu şehirdeki projeleri topla
        $pr_stmt = $pdo->prepare("
            SELECT DISTINCT project_code, item_type 
            FROM `media` 
            WHERE `city_code` = ? AND `project_code` != ''
        ");
        $pr_stmt->execute([$ct['code']]);
        $city_projects = [];
        while ($pr = $pr_stmt->fetch(PDO::FETCH_ASSOC)) {
            $p_code = $pr['project_code'];
            $p_title = MEDIA_PROJECTS[$p_code] ?? MEDIA_SERIES[$p_code] ?? $p_code;
            $city_projects[] = $p_title;
        }

        // Şehirdeki tekil fotoğraf var mı?
        $single_check = $pdo->prepare("SELECT COUNT(*) FROM `media` WHERE `city_code` = ? AND `item_type` = 'single'");
        $single_check->execute([$ct['code']]);
        if ((int)$single_check->fetchColumn() > 0) {
            $city_projects[] = 'Tekil Fotoğraflar (Single)';
        }

        // Son görsel önizlemesi
        $thumb_stmt = $pdo->prepare("SELECT file_path FROM `media` WHERE `city_code` = ? ORDER BY `id` DESC LIMIT 1");
        $thumb_stmt->execute([$ct['code']]);
        $sample_fp = $thumb_stmt->fetchColumn();
        $sample_thumb = $sample_fp ? url($sample_fp) : '';

        $map_locations[] = [
            'city_code'    => $ct['code'],
            'city_name'    => $ct['name'],
            'country_code' => $ct['country_code'],
            'country_name' => $ct['country_name'],
            'country_flag' => $ct['country_flag'],
            'lat'          => (float)$ct['lat'],
            'lng'          => (float)$ct['lng'],
            'count'        => $ct['count'],
            'projects'     => $city_projects,
            'sample_thumb' => $sample_thumb
        ];
    }
}

// ── 7. Görselleri Hazırla ──
$images = [];
foreach ($rows as $m) {
    // Thumbnail yolu (Hızlı yükleme için öncelikle processed_images 600w WebP varyantını kullan)
    $thumb_url = url($m['file_path']);
    $fn_clean = pathinfo($m['filename'], PATHINFO_FILENAME);
    $processed_600 = $base_dir . '/processed_images/' . $fn_clean . '_600w.webp';
    if (file_exists($processed_600)) {
        $thumb_url = url('processed_images/' . $fn_clean . '_600w.webp');
    } elseif (!empty($m['webp_path']) && file_exists($base_dir . '/' . $m['webp_path'])) {
        $thumb_url = url($m['webp_path']);
    } else {
        $thumb_local = preg_replace('/(\.[a-z0-9]+)$/i', '_thumb.webp', $base_dir . '/' . $m['file_path']);
        if (file_exists($thumb_local)) {
            $thumb_url = url(str_replace($base_dir . '/', '', $thumb_local));
        }
    }

    $c_code = $m['country_code'];
    $c_info = get_country_info($c_code);

    $city_c = $m['city_code'];
    $lookup_key = ($c_code === 'ET' && $city_c === 'ADA') ? 'ADD' : $city_c;
    $city_info = MEDIA_CITIES[$lookup_key] ?? MEDIA_CITIES[$city_c] ?? ['name' => $city_c];

    $p_code = $m['project_code'];
    $p_title = MEDIA_PROJECTS[$p_code] ?? MEDIA_SERIES[$p_code] ?? ($m['item_type'] === 'single' ? get_category_localized_name('single', $admin_lang) : $p_code);

    $type_labels = array_merge([
        'project' => get_category_localized_name('project', $admin_lang),
        'series'  => get_category_localized_name('series', $admin_lang),
        'single'  => get_category_localized_name('single', $admin_lang),
        'other'   => __a('other', 'Diğer')
    ], $custom_cat_map);

    // Tarih formatı (Tüm diller için format_date helper)
    $shot_date_fmt = !empty($m['shot_date']) ? format_date($m['shot_date'], $req_lang) : '';

    $images[] = [
        'id'            => (int)$m['id'],
        'filename'      => $m['filename'],
        'original_name' => $m['original_name'] ?: $m['filename'],
        'file_path'     => $m['file_path'],
        'folder'        => $m['folder'],
        'url'           => url($m['file_path']),
        'thumb_url'     => $thumb_url,
        'width'         => (int)$m['width'],
        'height'        => (int)$m['height'],
        'file_size'     => (int)$m['file_size'],
        'file_size_fmt' => round($m['file_size'] / 1024) . ' KB',
        'created_at'    => $m['created_at'],

        // Metadata
        'country_code'  => $c_code,
        'country_name'  => $c_info['name'],
        'country_flag'  => $c_info['flag'],
        'city_code'     => $city_c,
        'city_name'     => $city_info['name'],
        'item_type'     => $m['item_type'],
        'type_label'    => $type_labels[$m['item_type']] ?? 'Diğer',
        'project_code'  => $p_code,
        'project_title' => $p_title,
        'shot_date'     => $m['shot_date'],
        'shot_date_fmt' => $shot_date_fmt,
        'shot_year'     => $m['shot_year'],

        // EXIF
        'camera'        => $m['camera'],
        'lens'          => $m['lens'],
        'aperture'      => $m['aperture'],
        'shutter'       => $m['shutter'],
        'iso'           => (int)$m['iso'],
        'focal_length'  => $m['focal_length'],
    ];
}

$total_all = (int)$pdo->query("SELECT COUNT(*) FROM `media`")->fetchColumn();

echo json_encode([
    'success'         => true,
    'total_count'     => $total_all,
    'filtered_count'  => $filtered_total,
    'has_more'        => $filtered_total > count($rows),
    'selected_folder' => $selected_folder,
    'categories'      => $categories,
    'filter_options'  => [
        'countries' => $country_options,
        'cities'    => $city_options,
        'years'     => $agg_years,
        'cameras'   => $agg_cameras,
        'types'     => $agg_types,
        'projects'  => $project_options,
    ],
    'map_locations'   => $map_locations,
    'images'          => $images
]);
