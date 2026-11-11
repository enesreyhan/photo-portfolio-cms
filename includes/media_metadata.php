<?php
/**
 * includes/media_metadata.php
 * Belgesel Fotoğraf İsimlendirme, EXIF ve Harita Koordinat Kütüphanesi
 * 
 * İsteğe bağlı dosya adı kuralı (eşleşmeyen dosyalar sorunsuz çalışır):
 * Örnek: AB_20170323_TRIST_P-XYZ_01.jpg
 * - Eser sahibi kısaltması · Tarih (YYYYMMDD) · Ülke (2 harf) + şehir (3 harf)
 * - Kategori: P (proje) / S (seri, tekil) · Proje/seri kodu · Sıra numarası
 */

require_once __DIR__ . '/helpers.php';

const MEDIA_COUNTRIES = [
    // Mevcut belgesel ülkeleri
    'TR' => ['name' => 'Türkiye',       'flag' => '🇹🇷', 'en' => 'Turkey'],
    'AL' => ['name' => 'Arnavutluk',     'flag' => '🇦🇱', 'en' => 'Albania'],
    'JO' => ['name' => 'Ürdün',          'flag' => '🇯🇴', 'en' => 'Jordan'],
    'PH' => ['name' => 'Filipinler',     'flag' => '🇵🇭', 'en' => 'Philippines'],
    'BD' => ['name' => 'Bangladeş',      'flag' => '🇧🇩', 'en' => 'Bangladesh'],
    'NP' => ['name' => 'Nepal',          'flag' => '🇳🇵', 'en' => 'Nepal'],
    'ET' => ['name' => 'Etiyopya',       'flag' => '🇪🇹', 'en' => 'Ethiopia'],
    'TN' => ['name' => 'Tunus',          'flag' => '🇹🇳', 'en' => 'Tunisia'],
    'NG' => ['name' => 'Nijer',          'flag' => '🇳🇪', 'en' => 'Niger'],
    'BF' => ['name' => 'Burkina Faso',   'flag' => '🇧🇫', 'en' => 'Burkina Faso'],
    'PK' => ['name' => 'Pakistan',       'flag' => '🇵🇰', 'en' => 'Pakistan'],
    'TZ' => ['name' => 'Tanzanya',       'flag' => '🇹🇿', 'en' => 'Tanzania'],
    'SL' => ['name' => 'Sierra Leone',   'flag' => '🇸🇱', 'en' => 'Sierra Leone'],
    'AF' => ['name' => 'Afganistan',     'flag' => '🇦🇫', 'en' => 'Afghanistan'],

    // Orta Doğu & Kafkaslar & Orta Asya
    'AZ' => ['name' => 'Azerbaycan',     'flag' => '🇦🇿', 'en' => 'Azerbaijan'],
    'IR' => ['name' => 'İran',           'flag' => '🇮🇷', 'en' => 'Iran'],
    'IQ' => ['name' => 'Irak',           'flag' => '🇮🇶', 'en' => 'Iraq'],
    'SY' => ['name' => 'Suriye',         'flag' => '🇸🇾', 'en' => 'Syria'],
    'LB' => ['name' => 'Lübnan',         'flag' => '🇱🇧', 'en' => 'Lebanon'],
    'PS' => ['name' => 'Filistin',       'flag' => '🇵🇸', 'en' => 'Palestine'],
    'SA' => ['name' => 'Suudi Arabistan','flag' => '🇸🇦', 'en' => 'Saudi Arabia'],
    'AE' => ['name' => 'Birleşik Arap Emirlikleri', 'flag' => '🇦🇪', 'en' => 'United Arab Emirates'],
    'QA' => ['name' => 'Katar',          'flag' => '🇶🇦', 'en' => 'Qatar'],
    'KW' => ['name' => 'Kuveyt',         'flag' => '🇰🇼', 'en' => 'Kuwait'],
    'YE' => ['name' => 'Yemen',          'flag' => '🇾🇪', 'en' => 'Yemen'],
    'OM' => ['name' => 'Umman',          'flag' => '🇴🇲', 'en' => 'Oman'],
    'EG' => ['name' => 'Mısır',          'flag' => '🇪🇬', 'en' => 'Egypt'],
    'GE' => ['name' => 'Gürcistan',      'flag' => '🇬🇪', 'en' => 'Georgia'],
    'AM' => ['name' => 'Ermenistan',     'flag' => '🇦🇲', 'en' => 'Armenia'],
    'UZ' => ['name' => 'Özbekistan',     'flag' => '🇺🇿', 'en' => 'Uzbekistan'],
    'KZ' => ['name' => 'Kazakistan',     'flag' => '🇰🇿', 'en' => 'Kazakhstan'],
    'KG' => ['name' => 'Kırgızistan',    'flag' => '🇰🇬', 'en' => 'Kyrgyzstan'],
    'TM' => ['name' => 'Türkmenistan',   'flag' => '🇹🇲', 'en' => 'Turkmenistan'],
    'TJ' => ['name' => 'Tacikistan',     'flag' => '🇹🇯', 'en' => 'Tajikistan'],

    // Balkanlar & Doğu Avrupa
    'BA' => ['name' => 'Bosna-Hersek',   'flag' => '🇧🇦', 'en' => 'Bosnia and Herzegovina'],
    'XK' => ['name' => 'Kosova',         'flag' => '🇽🇰', 'en' => 'Kosovo'],
    'ME' => ['name' => 'Karadağ',        'flag' => '🇲🇪', 'en' => 'Montenegro'],
    'MK' => ['name' => 'Kuzey Makedonya','flag' => '🇲🇰', 'en' => 'North Macedonia'],
    'RS' => ['name' => 'Sırbistan',      'flag' => '🇷🇸', 'en' => 'Serbia'],
    'GR' => ['name' => 'Yunanistan',     'flag' => '🇬🇷', 'en' => 'Greece'],
    'BG' => ['name' => 'Bulgaristan',    'flag' => '🇧🇬', 'en' => 'Bulgaria'],
    'RO' => ['name' => 'Romanya',        'flag' => '🇷🇴', 'en' => 'Romania'],
    'UA' => ['name' => 'Ukrayna',        'flag' => '🇺🇦', 'en' => 'Ukraine'],
    'RU' => ['name' => 'Rusya',          'flag' => '🇷🇺', 'en' => 'Russia'],

    // Afrika
    'MA' => ['name' => 'Fas',            'flag' => '🇲🇦', 'en' => 'Morocco'],
    'DZ' => ['name' => 'Cezayir',        'flag' => '🇩🇿', 'en' => 'Algeria'],
    'SD' => ['name' => 'Sudan',          'flag' => '🇸🇩', 'en' => 'Sudan'],
    'SO' => ['name' => 'Somali',         'flag' => '🇸🇴', 'en' => 'Somalia'],
    'TD' => ['name' => 'Çad',            'flag' => '🇹🇩', 'en' => 'Chad'],
    'ML' => ['name' => 'Mali',           'flag' => '🇲🇱', 'en' => 'Mali'],
    'MR' => ['name' => 'Moritanya',      'flag' => '🇲🇷', 'en' => 'Mauritania'],
    'SN' => ['name' => 'Senegal',        'flag' => '🇸🇳', 'en' => 'Senegal'],
    'GH' => ['name' => 'Gana',           'flag' => '🇬🇭', 'en' => 'Ghana'],
    'KE' => ['name' => 'Kenya',          'flag' => '🇰🇪', 'en' => 'Kenya'],
    'UG' => ['name' => 'Uganda',         'flag' => '🇺🇬', 'en' => 'Uganda'],
    'RW' => ['name' => 'Ruanda',         'flag' => '🇷🇼', 'en' => 'Rwanda'],
    'ZA' => ['name' => 'Güney Afrika',   'flag' => '🇿🇦', 'en' => 'South Africa'],

    // Asya & Pasifik
    'IN' => ['name' => 'Hindistan',      'flag' => '🇮🇳', 'en' => 'India'],
    'ID' => ['name' => 'Endonezya',      'flag' => '🇮🇩', 'en' => 'Indonesia'],
    'MY' => ['name' => 'Malezya',        'flag' => '🇲🇾', 'en' => 'Malaysia'],
    'TH' => ['name' => 'Tayland',        'flag' => '🇹🇭', 'en' => 'Thailand'],
    'VN' => ['name' => 'Vietnam',        'flag' => '🇻🇳', 'en' => 'Vietnam'],
    'JP' => ['name' => 'Japonya',        'flag' => '🇯🇵', 'en' => 'Japan'],
    'CN' => ['name' => 'Çin',            'flag' => '🇨🇳', 'en' => 'China'],
    'KR' => ['name' => 'Güney Kore',     'flag' => '🇰🇷', 'en' => 'South Korea'],

    // Avrupa & Amerika
    'DE' => ['name' => 'Almanya',        'flag' => '🇩🇪', 'en' => 'Germany'],
    'FR' => ['name' => 'Fransa',         'flag' => '🇫🇷', 'en' => 'France'],
    'IT' => ['name' => 'İtalya',         'flag' => '🇮🇹', 'en' => 'Italy'],
    'ES' => ['name' => 'İspanya',        'flag' => '🇪🇸', 'en' => 'Spain'],
    'GB' => ['name' => 'Birleşik Krallık','flag' => '🇬🇧', 'en' => 'United Kingdom'],
    'NL' => ['name' => 'Hollanda',       'flag' => '🇳🇱', 'en' => 'Netherlands'],
    'BE' => ['name' => 'Belçika',        'flag' => '🇧🇪', 'en' => 'Belgium'],
    'AT' => ['name' => 'Avusturya',      'flag' => '🇦🇹', 'en' => 'Austria'],
    'CH' => ['name' => 'İsviçre',        'flag' => '🇨🇭', 'en' => 'Switzerland'],
    'PL' => ['name' => 'Polonya',        'flag' => '🇵🇱', 'en' => 'Poland'],
    'SE' => ['name' => 'İsveç',          'flag' => '🇸🇪', 'en' => 'Sweden'],
    'NO' => ['name' => 'Norveç',         'flag' => '🇳🇴', 'en' => 'Norway'],
    'DK' => ['name' => 'Danimarka',      'flag' => '🇩🇰', 'en' => 'Denmark'],
    'FI' => ['name' => 'Finlandiya',     'flag' => '🇫🇮', 'en' => 'Finland'],
    'PT' => ['name' => 'Portekiz',       'flag' => '🇵🇹', 'en' => 'Portugal'],
    'US' => ['name' => 'Amerika Birleşik Devletleri', 'flag' => '🇺🇸', 'en' => 'United States'],
    'CA' => ['name' => 'Kanada',         'flag' => '🇨🇦', 'en' => 'Canada'],
    'BR' => ['name' => 'Brezilya',       'flag' => '🇧🇷', 'en' => 'Brazil'],
    'AR' => ['name' => 'Arjantin',       'flag' => '🇦🇷', 'en' => 'Argentina'],
    'AU' => ['name' => 'Avustralya',     'flag' => '🇦🇺', 'en' => 'Australia'],
];

/**
 * Herhangi bir 2 harfli ISO ülke kodunu Unicode bayrak emojisine dönüştürür.
 * Yeni bir ülke kodu eklendiğinde otomatik bayrak oluşturur.
 */
function country_code_to_flag(string $code): string {
    $code = strtoupper(trim($code));
    if (strlen($code) !== 2 || !ctype_alpha($code)) {
        return '';
    }
    $first = 0x1F1E6 + ord($code[0]) - ord('A');
    $second = 0x1F1E6 + ord($code[1]) - ord('A');
    if (function_exists('mb_chr')) {
        return mb_chr($first, 'UTF-8') . mb_chr($second, 'UTF-8');
    }
    $b1 = chr(0xF0) . chr(0x9F) . chr(0x87) . chr(0xA6 + ord($code[0]) - ord('A'));
    $b2 = chr(0xF0) . chr(0x9F) . chr(0x87) . chr(0xA6 + ord($code[1]) - ord('A'));
    return $b1 . $b2;
}

/**
 * Ülke kodundan ad ve bayrak bilgisini getirir.
 * Listede olmasa dahi bayrağını ve adını otomatik türetir.
 */
function get_country_info(string $code): array {
    $code = strtoupper(trim($code));
    $flag = country_code_to_flag($code);
    if (isset(MEDIA_COUNTRIES[$code])) {
        return [
            'name' => MEDIA_COUNTRIES[$code]['name'],
            'flag' => MEDIA_COUNTRIES[$code]['flag'] ?: $flag,
            'en'   => MEDIA_COUNTRIES[$code]['en'] ?? ''
        ];
    }
    return [
        'name' => $code,
        'flag' => $flag,
        'en'   => $code
    ];
}

const MEDIA_CITIES = [
    // Türkiye
    'IST' => ['name' => 'İstanbul',     'en' => 'Istanbul',      'country' => 'TR', 'lat' => 41.0082,  'lng' => 28.9784],
    'TKD' => ['name' => 'Tekirdağ',     'en' => 'Tekirdag',      'country' => 'TR', 'lat' => 40.9781,  'lng' => 27.5117],
    'BOL' => ['name' => 'Bolu',         'en' => 'Bolu',          'country' => 'TR', 'lat' => 40.7358,  'lng' => 31.6061],
    'IZM' => ['name' => 'İzmir',        'en' => 'Izmir',         'country' => 'TR', 'lat' => 38.4237,  'lng' => 27.1428],
    'RZE' => ['name' => 'Rize',         'en' => 'Rize',          'country' => 'TR', 'lat' => 41.0201,  'lng' => 40.5234],
    'HTY' => ['name' => 'Hatay',        'en' => 'Hatay',         'country' => 'TR', 'lat' => 36.2023,  'lng' => 36.1606],
    'CNK' => ['name' => 'Çanakkale',    'en' => 'Canakkale',     'country' => 'TR', 'lat' => 40.1553,  'lng' => 26.4142],
    'BRS' => ['name' => 'Bursa',        'en' => 'Bursa',         'country' => 'TR', 'lat' => 40.1885,  'lng' => 29.0610],
    'ADA' => ['name' => 'Adana',        'en' => 'Adana',         'country' => 'TR', 'lat' => 37.0000,  'lng' => 35.3213],

    // Afganistan
    'KBL' => ['name' => 'Kabil',        'en' => 'Kabul',         'country' => 'AF', 'lat' => 34.5553,  'lng' => 69.2075],
    'JLL' => ['name' => 'Jalalabad',    'en' => 'Jalalabad',     'country' => 'AF', 'lat' => 34.4289,  'lng' => 70.4533],
    'MES' => ['name' => 'Mezar-ı Şerif','en' => 'Mazar-i-Sharif','country' => 'AF', 'lat' => 36.7061,  'lng' => 67.1108],
    'BHL' => ['name' => 'Belh',         'en' => 'Balkh',         'country' => 'AF', 'lat' => 36.7564,  'lng' => 66.8972],

    // Arnavutluk
    'SHK' => ['name' => 'İşkodra',      'en' => 'Shkoder',       'country' => 'AL', 'lat' => 42.0693,  'lng' => 19.5033],
    'LSH' => ['name' => 'Lushnja',      'en' => 'Lushnje',       'country' => 'AL', 'lat' => 40.9419,  'lng' => 19.7050],
    'TRN' => ['name' => 'Tiran',        'en' => 'Tirana',        'country' => 'AL', 'lat' => 41.3275,  'lng' => 19.8187],

    // Ürdün
    'MFR' => ['name' => 'El-Mafraq',    'en' => 'Al-Mafraq',     'country' => 'JO', 'lat' => 32.3429,  'lng' => 36.2081],

    // Filipinler
    'MNL' => ['name' => 'Manila',       'en' => 'Manila',        'country' => 'PH', 'lat' => 14.5995,  'lng' => 120.9842],
    'CTB' => ['name' => 'Cotobato',     'en' => 'Cotabato',      'country' => 'PH', 'lat' => 7.2236,   'lng' => 124.2464],

    // Bangladeş
    'DHK' => ['name' => 'Dakka',        'en' => 'Dhaka',         'country' => 'BD', 'lat' => 23.8103,  'lng' => 90.4125],
    'RNG' => ['name' => 'Rangpur',      'en' => 'Rangpur',       'country' => 'BD', 'lat' => 25.7439,  'lng' => 89.2752],

    // Nepal
    'KTH' => ['name' => 'Katmandu',     'en' => 'Kathmandu',     'country' => 'NP', 'lat' => 27.7172,  'lng' => 85.3240],
    'BDH' => ['name' => 'Budhanagar',   'en' => 'Budhanagar',    'country' => 'NP', 'lat' => 26.4525,  'lng' => 87.2718],

    // Etiyopya
    'ADD' => ['name' => 'Addis Ababa',  'en' => 'Addis Ababa',   'country' => 'ET', 'lat' => 9.0300,   'lng' => 38.7400],

    // Tunus
    'TUN' => ['name' => 'Tunus',        'en' => 'Tunis',         'country' => 'TN', 'lat' => 36.8065,  'lng' => 10.1815],

    // Nijer
    'NIA' => ['name' => 'Niamey',       'en' => 'Niamey',        'country' => 'NG', 'lat' => 13.5116,  'lng' => 2.1254],
    'TSA' => ['name' => 'Tessaoua',     'en' => 'Tessaoua',      'country' => 'NG', 'lat' => 13.7574,  'lng' => 7.9874],

    // Burkina Faso
    'OUA' => ['name' => 'Ouagadougou',  'en' => 'Ouagadougou',   'country' => 'BF', 'lat' => 12.3714,  'lng' => -1.5197],
    'KAY' => ['name' => 'Kaya',         'en' => 'Kaya',          'country' => 'BF', 'lat' => 13.0917,  'lng' => -1.0844],

    // Pakistan
    'GIG' => ['name' => 'Gilgit',       'en' => 'Gilgit',        'country' => 'PK', 'lat' => 35.9221,  'lng' => 74.3087],

    // Tanzanya
    'MRG' => ['name' => 'Morogoro',     'en' => 'Morogoro',      'country' => 'TZ', 'lat' => -6.8278,  'lng' => 37.6591],
    'DRS' => ['name' => 'Darüsselam',   'en' => 'Dar es Salaam', 'country' => 'TZ', 'lat' => -6.7924,  'lng' => 39.2083],

    // Sierra Leone
    'FRT' => ['name' => 'Freetown',     'en' => 'Freetown',      'country' => 'SL', 'lat' => 8.4844,   'lng' => -13.2344],
];

/**
 * Ülke ve şehir kodundan şehir meta bilgisini (ad, koordinat) getirir.
 * Etiyopya ET-ADA -> ADD (Addis Ababa) gibi çakışmaları güvenle çözer.
 */
function get_city_info(string $city_code, string $country_code = ''): array {
    $ct = strtoupper(trim($city_code));
    $cc = strtoupper(trim($country_code));

    // Özel durum: Etiyopya ADA -> ADD (Addis Ababa)
    $lookup_key = ($cc === 'ET' && $ct === 'ADA') ? 'ADD' : $ct;

    if (isset(MEDIA_CITIES[$lookup_key])) {
        $info = MEDIA_CITIES[$lookup_key];
        if ($cc === '' || empty($info['country']) || $info['country'] === $cc) {
            return $info;
        }
    }

    if ($cc !== '') {
        foreach (MEDIA_CITIES as $k => $c) {
            if ($k === $ct && isset($c['country']) && $c['country'] === $cc) {
                return $c;
            }
        }
    }

    if (isset(MEDIA_CITIES[$ct])) {
        return MEDIA_CITIES[$ct];
    }

    return [
        'name'    => $ct,
        'en'      => $ct,
        'country' => $cc,
        'lat'     => null,
        'lng'     => null
    ];
}

// Proje/seri kodu → başlık eşlemesi siteye özeldir: includes/private/media_codes.php (varsa)
if (is_file(__DIR__ . '/private/media_codes.php')) require_once __DIR__ . '/private/media_codes.php';
if (!defined('MEDIA_PROJECTS')) define('MEDIA_PROJECTS', []);
if (!defined('MEDIA_SERIES')) define('MEDIA_SERIES', []);

/**
 * Dosya adını belgesel isimlendirme kuralına göre parse eder
 */
function parse_media_filename(string $filename): array {
    $clean_fn = basename($filename);

    $res = [
        'is_matched'    => false,
        'author'        => 'ER',
        'author_name'   => site_owner_name(),
        'date'          => '',
        'year'          => '',
        'month'         => '',
        'day'           => '',
        'country_code'  => '',
        'country_name'  => '',
        'country_flag'  => '',
        'city_code'     => '',
        'city_name'     => '',
        'lat'           => null,
        'lng'           => null,
        'item_type'     => 'other',
        'type_label'    => 'Diğer',
        'project_code'  => '',
        'project_title' => '',
        'number'        => '',
        'badge'         => ''
    ];

    // Regex: AB_20170323_TRIST_P-XYZ_01.jpg ya da AB_20260316_SLFRT_S_01.jpg ya da AB_20130309_TRIST_S06.jpg
    if (preg_match('/^([A-Za-z]+)_(\d{4})(\d{2})(\d{2})_([A-Za-z]{2})([A-Za-z]{3})_([PSps])(?:[-_]?([A-Za-z0-9]+))?(?:[-_]?(\d+))?\./', $clean_fn, $m)) {
        $res['is_matched'] = true;
        $res['author'] = strtoupper($m[1]);

        $res['year']  = $m[2];
        $res['month'] = $m[3];
        $res['day']   = $m[4];
        $res['date']  = "{$m[2]}-{$m[3]}-{$m[4]}";

        $cc = strtoupper($m[5]);
        $city = strtoupper($m[6]);
        $res['country_code'] = $cc;
        $res['city_code']    = $city;

        $cInfo = get_country_info($cc);
        $res['country_name'] = $cInfo['name'];
        $res['country_flag'] = $cInfo['flag'];

        // Şehir eşleme
        $city_lookup_key = $city;
        // Özel durum: ET-ADA -> Addis Ababa
        if ($cc === 'ET' && $city === 'ADA') {
            $city_lookup_key = 'ADD';
        }

        if (isset(MEDIA_CITIES[$city_lookup_key])) {
            $res['city_name'] = MEDIA_CITIES[$city_lookup_key]['name'];
            $res['lat']       = MEDIA_CITIES[$city_lookup_key]['lat'];
            $res['lng']       = MEDIA_CITIES[$city_lookup_key]['lng'];
        } elseif (isset(MEDIA_CITIES[$city])) {
            $res['city_name'] = MEDIA_CITIES[$city]['name'];
            $res['lat']       = MEDIA_CITIES[$city]['lat'];
            $res['lng']       = MEDIA_CITIES[$city]['lng'];
        } else {
            $res['city_name'] = $city;
        }

        $cat = strtoupper($m[7]);
        $pcode = !empty($m[8]) ? strtoupper($m[8]) : '';
        $num = !empty($m[9]) ? $m[9] : '';

        if ($cat === 'P') {
            $res['item_type'] = 'project';
            $res['type_label'] = 'Proje';
            $res['project_code'] = $pcode;
            $res['project_title'] = MEDIA_PROJECTS[$pcode] ?? $pcode;
            $res['number'] = $num;
            $res['badge'] = 'PROJE: ' . ($res['project_title'] ?: $pcode);
        } elseif ($cat === 'S') {
            if ($pcode && isset(MEDIA_SERIES[$pcode])) {
                $res['item_type'] = 'series';
                $res['type_label'] = 'Seri';
                $res['project_code'] = $pcode;
                $res['project_title'] = MEDIA_SERIES[$pcode];
                $res['number'] = $num;
                $res['badge'] = 'SERİ: ' . $res['project_title'];
            } else {
                $res['item_type'] = 'single';
                $res['type_label'] = 'Tekil';
                $res['project_code'] = '';
                $res['project_title'] = 'Tekil Fotoğraf';
                $res['number'] = $num ?: $pcode;
                $res['badge'] = 'TEKİL #' . $res['number'];
            }
        }
    }

    return $res;
}

/**
 * Görsel dosyasından EXIF bilgilerini detaylıca okur
 */
function read_photo_exif(string $full_path): array {
    $res = [
        'has_exif'        => false,
        'camera'          => '',
        'camera_clean'    => '',
        'lens'            => '',
        'aperture'        => '',
        'aperture_num'    => null,
        'shutter'         => '',
        'shutter_num'     => null,
        'iso'             => null,
        'focal_length'    => '',
        'shot_datetime'   => '',
        'orientation'     => 1,
    ];

    if (!file_exists($full_path) || !function_exists('exif_read_data')) {
        return $res;
    }

    $ex = @exif_read_data($full_path);
    if (!$ex) {
        return $res;
    }

    $res['has_exif'] = true;

    // Kamera Modeli
    $model = trim($ex['Model'] ?? '');
    $make  = trim($ex['Make'] ?? '');
    if ($model !== '') {
        $res['camera'] = $model;
        $clean = $model;
        // Standartlaştırma
        $clean = str_replace(['NIKON CORPORATION', 'NIKON'], 'Nikon', $clean);
        $clean = str_replace('Z 6_2', 'Z 6 II', $clean);
        $clean = str_replace('Z 7_2', 'Z 7 II', $clean);
        if ($clean === 'X100T') $clean = 'Fujifilm X100T';
        $res['camera_clean'] = trim($clean);
    }

    // Lens
    if (!empty($ex['LensModel'])) {
        $res['lens'] = trim((string)$ex['LensModel']);
    } elseif (!empty($ex['UndefinedTag:0xA434'])) {
        $res['lens'] = trim((string)$ex['UndefinedTag:0xA434']);
    }

    // Diyafram (Aperture / FNumber)
    if (!empty($ex['COMPUTED']['ApertureFNumber'])) {
        $ap_clean = ltrim(trim($ex['COMPUTED']['ApertureFNumber']), 'fF/ ');
        $val = round((float)$ap_clean, 1);
        $res['aperture_num'] = $val;
        $res['aperture'] = 'ƒ/' . (floor($val) == $val ? (int)$val : $val);
    } elseif (!empty($ex['FNumber'])) {
        $fnum_raw = $ex['FNumber'];
        if (is_string($fnum_raw) && strpos($fnum_raw, '/') !== false) {
            $parts = explode('/', $fnum_raw);
            $val = (float)$parts[0] / max((float)($parts[1] ?? 1), 1);
        } else {
            $val = (float)$fnum_raw;
        }
        $val = round($val, 1);
        $res['aperture_num'] = $val;
        $res['aperture'] = 'ƒ/' . (floor($val) == $val ? (int)$val : $val);
    }

    // Enstantane / Shutter Speed (ExposureTime)
    $exp_raw = $ex['ExposureTime'] ?? null;
    if ($exp_raw) {
        if (is_string($exp_raw) && strpos($exp_raw, '/') !== false) {
            $parts = explode('/', $exp_raw);
            $val = (float)$parts[0] / max((float)($parts[1] ?? 1), 1);
            $res['shutter'] = $exp_raw . 's';
            $res['shutter_num'] = $val;
        } else {
            $val = (float)$exp_raw;
            $res['shutter_num'] = $val;
            if ($val > 0 && $val < 1) {
                $inv = round(1 / $val);
                $res['shutter'] = '1/' . $inv . 's';
            } else {
                $res['shutter'] = round($val, 1) . 's';
            }
        }
    }

    // ISO
    if (!empty($ex['ISOSpeedRatings'])) {
        $res['iso'] = (int)$ex['ISOSpeedRatings'];
    }

    // Odak Uzaklığı (Focal Length)
    $focal_raw = $ex['FocalLength'] ?? null;
    if ($focal_raw) {
        if (is_string($focal_raw) && strpos($focal_raw, '/') !== false) {
            $parts = explode('/', $focal_raw);
            $val = round((float)$parts[0] / max((float)($parts[1] ?? 1), 1));
        } else {
            $val = round((float)$focal_raw);
        }
        $res['focal_length'] = $val . ' mm';
    }

    // Çekim Zamanı
    if (!empty($ex['DateTimeOriginal'])) {
        $res['shot_datetime'] = (string)$ex['DateTimeOriginal'];
    }

    return $res;
}

/**
 * Görsel için üretilmiş tüm AVIF & WebP optimize varyantlarını tespit eder
 */
function find_photo_variants(string $base_dir, string $file_path, string $lang = 'tr'): array {
    $variants = [];
    $fn = basename($file_path);
    $stem = pathinfo($fn, PATHINFO_FILENAME);
    $is_en = ($lang === 'en');
    // admin panelinden çağrılınca panel dilinin paketini kullan
    $L = fn(string $tr, string $en): string => function_exists('__a') ? __a($tr, $en) : ($is_en ? $en : $tr);

    // Orijinal dosya
    $orig_local = $base_dir . '/' . $file_path;
    if (file_exists($orig_local)) {
        $variants[] = [
            'type'      => 'original',
            'format'    => strtoupper(pathinfo($file_path, PATHINFO_EXTENSION)),
            'label'     => $L('Orijinal Master', 'Original Master'),
            'path'      => $file_path,
            'url'       => url($file_path),
            'size'      => filesize($orig_local),
            'size_fmt'  => round(filesize($orig_local) / 1024) . ' KB',
            'is_master' => true
        ];
    }

    // processed_images/ klasöründeki türevler
    $proc_dir = $base_dir . '/processed_images';
    if (is_dir($proc_dir)) {
        $matches = glob($proc_dir . '/' . $stem . '*');
        if ($matches) {
            $proc_variants = [];
            foreach ($matches as $m) {
                $ext = strtolower(pathinfo($m, PATHINFO_EXTENSION));
                if (!in_array($ext, ['webp', 'avif', 'jpg', 'jpeg', 'png'])) continue;

                $mb_name = basename($m);
                $rel = 'processed_images/' . $mb_name;
                $sz = filesize($m);
                $w = 0;

                // 1. Önce dosya adından genişlik ara (örn: _1800w.webp veya _800w.avif)
                if (preg_match('/_(\d+)w\.' . preg_quote($ext, '/') . '$/i', $mb_name, $wm)) {
                    $w = (int)$wm[1];
                }

                // 2. Dosya adında yoksa (örn: Zola hashli AVIF .c039369b81af4c3b.avif), getimagesize ile genişliği al
                if (!$w) {
                    $dims = @getimagesize($m);
                    if ($dims && !empty($dims[0])) {
                        $w = (int)$dims[0];
                    }
                }

                $fmt_upper = ($ext === 'webp') ? 'WebP' : strtoupper($ext);

                if ($w > 0) {
                    $tier = '';
                    if ($w >= 1400) {
                        $tier = ' ' . $L('Geniş', 'Wide');
                    } elseif ($w >= 800) {
                        $tier = ' ' . $L('Orta', 'Medium');
                    } else {
                        $tier = ' ' . $L('Küçük', 'Small');
                    }
                    $label = $fmt_upper . ' (' . $w . 'px' . $tier . ')';
                } else {
                    $label = ($ext === 'webp') ? $L('WebP Dönüştürülmüş', 'WebP Converted') : ($fmt_upper . ' ' . $L('Optimize', 'Optimized'));
                }

                $proc_variants[] = [
                    'type'      => $ext,
                    'format'    => strtoupper($ext),
                    'label'     => $label,
                    'path'      => $rel,
                    'url'       => url($rel),
                    'size'      => $sz,
                    'size_fmt'  => round($sz / 1024) . ' KB',
                    'width'     => $w,
                    'is_master' => false
                ];
            }

            // Varyantları formata (AVIF önce, sonra WebP) ve genişliğe (büyükten küçüğe) göre sırala
            usort($proc_variants, function($a, $b) {
                if ($a['format'] !== $b['format']) {
                    return ($a['format'] === 'AVIF') ? -1 : 1;
                }
                return ($b['width'] ?? 0) <=> ($a['width'] ?? 0);
            });

            foreach ($proc_variants as $pv) {
                $variants[] = $pv;
            }
        }
    }

    // Klasör içindeki thumbnail (_thumb.webp)
    $thumb_local = preg_replace('/(\.[a-z0-9]+)$/i', '_thumb.webp', $orig_local);
    if (file_exists($thumb_local)) {
        $thumb_rel = str_replace($base_dir . '/', '', $thumb_local);
        $variants[] = [
            'type'      => 'thumb',
            'format'    => 'WEBP',
            'label'     => 'Panel Önizleme (Küçük Resim)',
            'path'      => $thumb_rel,
            'url'       => url($thumb_rel),
            'size'      => filesize($thumb_local),
            'size_fmt'  => round(filesize($thumb_local) / 1024) . ' KB',
            'is_master' => false
        ];
    }

    return $variants;
}
