<?php
/**
 * includes/geo_gazetteer.php
 * Yer adından (ör. "Morogoro, Tanzanya", "Kathmandu, Nepal", "Afganistan") yaklaşık koordinat bulur.
 * Önce şehir (sitenin şehir listeleri), bulunamazsa ülke merkezi kullanılır. Proje Haritası şablonu kullanır.
 */

function geo_fold(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, ['ı' => 'i', 'i̇' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c', 'â' => 'a', 'î' => 'i', 'û' => 'u', 'é' => 'e', 'è' => 'e', 'ã' => 'a', 'á' => 'a', 'í' => 'i', 'ó' => 'o', 'ñ' => 'n']);
    return trim(preg_replace('/[^a-z0-9 ]+/', ' ', $s));
}

/** Ülke merkezleri (Türkçe ve İngilizce adlar) */
function geo_countries(): array {
    static $c = null;
    if ($c !== null) return $c;
    $rows = [
        [39.0, 35.2, 'turkiye', 'turkey'], [33.9, 67.7, 'afganistan', 'afghanistan'], [-6.4, 34.9, 'tanzanya', 'tanzania'],
        [12.2, -1.6, 'burkina faso'], [28.4, 84.1, 'nepal'], [8.5, -11.8, 'sierra leone'], [33.9, 9.5, 'tunus', 'tunisia'],
        [17.6, 8.1, 'nijer', 'niger'], [9.1, 40.5, 'etiyopya', 'ethiopia'], [12.9, 121.8, 'filipinler', 'philippines'],
        [23.7, 90.4, 'banglades', 'bangladesh'], [30.4, 69.3, 'pakistan'], [41.2, 20.2, 'arnavutluk', 'albania'],
        [30.6, 36.2, 'urdun', 'jordan'], [34.8, 39.0, 'suriye', 'syria'], [33.2, 43.7, 'irak', 'iraq'], [32.4, 53.7, 'iran'],
        [33.9, 35.9, 'lubnan', 'lebanon'], [31.0, 34.9, 'israil', 'israel'], [31.9, 35.2, 'filistin', 'palestine', 'gaza'],
        [26.8, 30.8, 'misir', 'egypt'], [26.3, 17.2, 'libya'], [28.0, 1.7, 'cezayir', 'algeria'], [31.8, -7.1, 'fas', 'morocco'],
        [15.6, 32.5, 'sudan'], [7.0, 30.0, 'guney sudan', 'south sudan'], [5.2, 46.2, 'somali', 'somalia'], [0.0, 37.9, 'kenya'],
        [1.4, 32.3, 'uganda'], [-1.9, 29.9, 'ruanda', 'rwanda'], [-4.0, 21.8, 'kongo', 'congo', 'dr congo'], [9.1, 8.7, 'nijerya', 'nigeria'],
        [7.9, -1.0, 'gana', 'ghana'], [14.5, -14.5, 'senegal'], [17.6, -4.0, 'mali'], [15.5, 18.7, 'cad', 'chad'],
        [-18.7, 35.5, 'mozambik', 'mozambique'], [-30.6, 22.9, 'guney afrika', 'south africa'], [-19.0, 29.2, 'zimbabve', 'zimbabwe'],
        [15.6, 48.5, 'yemen'], [23.9, 45.1, 'suudi arabistan', 'saudi arabia'], [21.5, 55.9, 'umman', 'oman'], [23.4, 53.8, 'birlesik arap emirlikleri', 'united arab emirates', 'uae'],
        [20.6, 79.0, 'hindistan', 'india'], [7.9, 80.8, 'sri lanka'], [21.9, 95.9, 'myanmar', 'burma'], [15.9, 100.9, 'tayland', 'thailand'],
        [14.1, 108.3, 'vietnam'], [12.6, 104.9, 'kambocya', 'cambodia'], [-0.8, 113.9, 'endonezya', 'indonesia'], [4.2, 101.9, 'malezya', 'malaysia'],
        [35.9, 104.2, 'cin', 'china'], [36.2, 138.3, 'japonya', 'japan'], [35.9, 127.8, 'guney kore', 'south korea'], [46.9, 103.8, 'mogolistan', 'mongolia'],
        [48.0, 66.9, 'kazakistan', 'kazakhstan'], [41.4, 64.6, 'ozbekistan', 'uzbekistan'], [38.9, 71.3, 'tacikistan', 'tajikistan'], [41.2, 74.8, 'kirgizistan', 'kyrgyzstan'],
        [40.1, 47.6, 'azerbaycan', 'azerbaijan'], [40.1, 45.0, 'ermenistan', 'armenia'], [42.3, 43.4, 'gurcistan', 'georgia'],
        [48.4, 31.2, 'ukrayna', 'ukraine'], [61.5, 105.3, 'rusya', 'russia'], [53.7, 27.9, 'belarus'], [47.4, 28.4, 'moldova'],
        [45.9, 25.0, 'romanya', 'romania'], [42.7, 25.5, 'bulgaristan', 'bulgaria'], [39.1, 21.8, 'yunanistan', 'greece'], [35.1, 33.4, 'kibris', 'cyprus'],
        [44.0, 21.0, 'sirbistan', 'serbia'], [43.9, 17.7, 'bosna hersek', 'bosnia', 'bosnia and herzegovina'], [45.1, 15.2, 'hirvatistan', 'croatia'],
        [42.6, 20.9, 'kosova', 'kosovo'], [41.6, 21.7, 'kuzey makedonya', 'north macedonia'], [42.7, 19.4, 'karadag', 'montenegro'],
        [41.9, 12.6, 'italya', 'italy'], [46.2, 2.2, 'fransa', 'france'], [40.5, -3.7, 'ispanya', 'spain'], [39.4, -8.2, 'portekiz', 'portugal'],
        [51.2, 10.5, 'almanya', 'germany'], [52.1, 5.3, 'hollanda', 'netherlands'], [50.5, 4.5, 'belcika', 'belgium'], [46.8, 8.2, 'isvicre', 'switzerland'],
        [47.5, 14.6, 'avusturya', 'austria'], [51.9, 19.1, 'polonya', 'poland'], [49.8, 15.5, 'cekya', 'czechia', 'czech republic'], [47.2, 19.5, 'macaristan', 'hungary'],
        [55.4, -3.4, 'birlesik krallik', 'ingiltere', 'united kingdom', 'england'], [53.4, -8.2, 'irlanda', 'ireland'],
        [60.1, 18.6, 'isvec', 'sweden'], [60.5, 8.5, 'norvec', 'norway'], [56.3, 9.5, 'danimarka', 'denmark'], [61.9, 25.7, 'finlandiya', 'finland'], [64.9, -19.0, 'izlanda', 'iceland'],
        [37.1, -95.7, 'abd', 'amerika birlesik devletleri', 'united states', 'usa'], [56.1, -106.3, 'kanada', 'canada'], [23.6, -102.6, 'meksika', 'mexico'],
        [15.8, -90.2, 'guatemala'], [18.97, -72.3, 'haiti'], [21.5, -77.8, 'kuba', 'cuba'], [4.6, -74.3, 'kolombiya', 'colombia'], [6.4, -66.6, 'venezuela'],
        [-9.2, -75.0, 'peru'], [-16.3, -63.6, 'bolivya', 'bolivia'], [-14.2, -51.9, 'brezilya', 'brazil'], [-38.4, -63.6, 'arjantin', 'argentina'], [-35.7, -71.5, 'sili', 'chile'],
        [-25.3, 133.8, 'avustralya', 'australia'], [-40.9, 174.9, 'yeni zelanda', 'new zealand'],
    ];
    $c = [];
    foreach ($rows as $r) {
        foreach (array_slice($r, 2) as $name) {
            $c[$name] = [$r[0], $r[1]];
        }
    }
    return $c;
}

/** Şehirler: sitenin şehir listesi (MEDIA_CITIES) + bazı büyük şehirler */
function geo_cities(): array {
    static $c = null;
    if ($c !== null) return $c;
    $c = [];
    if (!defined('MEDIA_CITIES') && is_file(__DIR__ . '/media_metadata.php')) {
        require_once __DIR__ . '/media_metadata.php';
    }
    if (defined('MEDIA_CITIES')) {
        foreach (MEDIA_CITIES as $m) {
            foreach ([$m['name'] ?? '', $m['en'] ?? ''] as $n) {
                if ($n !== '' && isset($m['lat'], $m['lng'])) $c[geo_fold($n)] = [(float)$m['lat'], (float)$m['lng']];
            }
        }
    }
    $extra = [
        'istanbul' => [41.0082, 28.9784], 'ankara' => [39.9334, 32.8597], 'izmir' => [38.4237, 27.1428], 'kathmandu' => [27.7172, 85.3240], 'katmandu' => [27.7172, 85.3240],
        'kabul' => [34.5553, 69.2075], 'kabil' => [34.5553, 69.2075], 'freetown' => [8.4844, -13.2344], 'morogoro' => [-6.8278, 37.6591], 'dar es salaam' => [-6.7924, 39.2083],
        'kaya' => [13.0917, -1.0844], 'ouagadougou' => [12.3714, -1.5197], 'beirut' => [33.8938, 35.5018], 'beyrut' => [33.8938, 35.5018], 'damascus' => [33.5138, 36.2765],
        'baghdad' => [33.3152, 44.3661], 'bagdat' => [33.3152, 44.3661], 'kyiv' => [50.4501, 30.5234], 'kiev' => [50.4501, 30.5234], 'cairo' => [30.0444, 31.2357], 'kahire' => [30.0444, 31.2357],
        'london' => [51.5074, -0.1278], 'londra' => [51.5074, -0.1278], 'paris' => [48.8566, 2.3522], 'berlin' => [52.52, 13.405], 'new york' => [40.7128, -74.006],
        'rize' => [41.0201, 40.5234], 'bolu' => [40.7358, 31.6061], 'hatay' => [36.2023, 36.1606], 'antakya' => [36.2023, 36.1606], 'bursa' => [40.1885, 29.061],
    ];
    foreach ($extra as $k => $v) {
        if (!isset($c[$k])) $c[$k] = $v;
    }
    return $c;
}

/**
 * Yer metninden koordinat noktaları: [['lat'=>..,'lng'=>..], ...]
 * Önce şehir aranır; bulunamazsa metinde geçen her ülke için bir nokta.
 */
function geo_resolve_points(string $location): array {
    $location = trim($location);
    if ($location === '') return [];
    $parts  = preg_split('/[,\/&;]| ve | and /u', $location);
    $tokens = array_values(array_filter(array_map('geo_fold', $parts), function ($t) { return $t !== ''; }));
    $cities = geo_cities();
    foreach ($tokens as $t) {
        if (isset($cities[$t])) return [['lat' => $cities[$t][0], 'lng' => $cities[$t][1]]];
    }
    foreach ($tokens as $t) {
        foreach ($cities as $name => $ll) {
            if (strlen($name) >= 4 && preg_match('/\b' . preg_quote($name, '/') . '\b/', $t)) return [['lat' => $ll[0], 'lng' => $ll[1]]];
        }
    }
    $countries = geo_countries();
    $pts = [];
    foreach ($tokens as $t) {
        if (isset($countries[$t])) { $pts[] = ['lat' => $countries[$t][0], 'lng' => $countries[$t][1]]; continue; }
        foreach ($countries as $name => $ll) {
            if (strlen($name) >= 4 && preg_match('/\b' . preg_quote($name, '/') . '\b/', $t)) { $pts[] = ['lat' => $ll[0], 'lng' => $ll[1]]; break; }
        }
    }
    return array_values(array_unique($pts, SORT_REGULAR));
}
