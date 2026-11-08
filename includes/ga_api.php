<?php
/**
 * includes/ga_api.php
 * Google Analytics 4 (GA4) Data API Entegrasyonu & Raporlama Motoru
 * 
 * Portfolyo için:
 * - Genel Performans: Toplam Aktif Kullanıcı (activeUsers)
 * - İlgi & Derinlik: Ortalama Etkileşim Süresi (averageSessionDuration)
 * - Edinim: Trafik Kaynağı / Mecra (sessionSource, sessionMedium)
 * - İçerik Popülerliği: En Çok Gezilen Portfolyolar (screenPageViews, pagePath)
 * - Dönüşüm / Aksiyon: İletişim Formu, PDF İndirme vb. (conversions, eventName)
 * - Coğrafi Analiz: Ülke & Şehir Dağılımı (country, city, activeUsers)
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/media_metadata.php';

const GA_DEFAULT_PROPERTY_ID = '';

/**
 * GA4 Property ID'sini getirir
 */
function get_ga_property_id(): string {
    $prop = trim(get_setting('ga_property_id', ''));
    return !empty($prop) ? $prop : GA_DEFAULT_PROPERTY_ID;
}

/**
 * Google Service Account JSON yapılandırmasını getirir
 */
function get_ga_service_account(): ?array {
    $json_raw = get_setting('ga_service_account_json', '');
    if (empty($json_raw)) {
        return null;
    }
    $decoded = json_decode($json_raw, true);
    if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
        return null;
    }
    return $decoded;
}

/**
 * GA4 Canlı API bağlantısı yapılandırılmış mı?
 */
function is_ga_configured(): bool {
    return get_ga_service_account() !== null;
}

/**
 * Google Service Account için RS256 JWT Token oluşturur
 */
function get_ga_jwt(array $sa): string {
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claim = [
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now
    ];

    $b64Header = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $b64Claim  = rtrim(strtr(base64_encode(json_encode($claim)), '+/', '-_'), '=');
    $dataToSign = $b64Header . '.' . $b64Claim;

    $signature = '';
    $privateKey = openssl_pkey_get_private($sa['private_key']);
    if (!$privateKey) {
        throw new Exception('Geçersiz Private Key: Service Account JSON içeriğindeki private_key doğrulanamadı.');
    }

    $ok = openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    if (!$ok) {
        throw new Exception('JWT imzalama başarısız oldu.');
    }

    $b64Sig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    return $dataToSign . '.' . $b64Sig;
}

/**
 * Google OAuth2 token endpoint'inden geçici access_token alır
 */
function get_ga_access_token(array $sa): string {
    static $cached_tokens = [];
    $email = $sa['client_email'] ?? 'default';

    if (isset($cached_tokens[$email]) && $cached_tokens[$email]['expires_at'] > time() + 300) {
        return $cached_tokens[$email]['token'];
    }

    $jwt = get_ga_jwt($sa);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $json = json_decode($res, true);
    if ($httpCode !== 200 || empty($json['access_token'])) {
        $err = $json['error_description'] ?? $json['error'] ?? 'Google OAuth2 Token alınamadı.';
        throw new Exception($err);
    }

    $token = $json['access_token'];
    $expires_in = (int)($json['expires_in'] ?? 3600);
    $cached_tokens[$email] = [
        'token'      => $token,
        'expires_at' => time() + $expires_in
    ];

    return $token;
}

/**
 * GA4 Data API runReport sorgusu yapar
 */
function ga4_run_report(string $propertyId, string $accessToken, array $body): array {
    $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $json = json_decode($res, true);
    if ($httpCode !== 200) {
        $msg = $json['error']['message'] ?? 'Google Analytics API sorgu hatası (HTTP ' . $httpCode . ')';
        throw new Exception($msg);
    }

    return $json ?? [];
}

/**
 * Google Analytics bağlantısını test eder
 */
function test_ga_connection(string $propertyId, array $sa): array {
    try {
        $token = get_ga_access_token($sa);
        $testReport = ga4_run_report($propertyId, $token, [
            'dateRanges' => [['startDate' => '7daysAgo', 'endDate' => 'today']],
            'metrics'    => [['name' => 'activeUsers']]
        ]);

        $activeUsers = (int)($testReport['rows'][0]['metricValues'][0]['value'] ?? 0);
        return [
            'success'      => true,
            'message'      => 'Google Analytics 4 API bağlantısı başarılı!',
            'property_id'  => $propertyId,
            'active_users' => $activeUsers
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Saniyeyi "2dk 15sn" veya "45sn" biçiminde formatlar
 */
function format_ga_duration(float|int $seconds): string {
    $sec = (int)round($seconds);
    if ($sec < 60) {
        return $sec . 'sn';
    }
    $min = floor($sec / 60);
    $remSec = $sec % 60;
    if ($min < 60) {
        return $min . 'dk' . ($remSec > 0 ? ' ' . $remSec . 'sn' : '');
    }
    $hours = floor($min / 60);
    $remMin = $min % 60;
    return $hours . 'sa ' . $remMin . 'dk';
}

/**
 * Tarih aralığı metnini GA4 parametresine çevirir
 */
function get_ga_date_range_params(string $range): array {
    switch ($range) {
        case '7d':
            return ['startDate' => '7daysAgo', 'endDate' => 'yesterday', 'label' => 'Son 7 Gün'];
        case '90d':
            return ['startDate' => '90daysAgo', 'endDate' => 'yesterday', 'label' => 'Son 90 Gün'];
        case '365d':
            return ['startDate' => '365daysAgo', 'endDate' => 'yesterday', 'label' => 'Son 1 Yıl'];
        case '30d':
        default:
            return ['startDate' => '30daysAgo', 'endDate' => 'yesterday', 'label' => 'Son 30 Gün'];
    }
}

/**
 * Bilinen global şehir koordinatları (Leaflet Harita Pinleri için)
 */
function get_global_city_coordinates(string $city, string $countryCode): ?array {
    $c = strtoupper(trim($city));
    $cc = strtoupper(trim($countryCode));

    // Yerel belgesel şehirleri
    if (isset(MEDIA_CITIES[$c])) {
        return ['lat' => MEDIA_CITIES[$c]['lat'], 'lng' => MEDIA_CITIES[$c]['lng']];
    }

    static $global_coords = [
        'ISTANBUL'  => ['lat' => 41.0082, 'lng' => 28.9784],
        'ANKARA'    => ['lat' => 39.9334, 'lng' => 32.8597],
        'IZMIR'     => ['lat' => 38.4237, 'lng' => 27.1428],
        'BURSA'     => ['lat' => 40.1885, 'lng' => 29.0610],
        'ANTALYA'   => ['lat' => 36.8969, 'lng' => 30.7133],
        'NEW YORK'  => ['lat' => 40.7128, 'lng' => -74.0060],
        'LONDON'    => ['lat' => 51.5074, 'lng' => -0.1278],
        'BERLIN'    => ['lat' => 52.5200, 'lng' => 13.4050],
        'PARIS'     => ['lat' => 48.8566, 'lng' => 2.3522],
        'ROME'      => ['lat' => 41.9028, 'lng' => 12.4964],
        'AMSTERDAM' => ['lat' => 52.3676, 'lng' => 4.9041],
        'TIRANA'    => ['lat' => 41.3275, 'lng' => 19.8187],
        'ZURICH'    => ['lat' => 47.3769, 'lng' => 8.5417],
        'TOKYO'     => ['lat' => 35.6762, 'lng' => 139.6503],
        'FREETOWN'  => ['lat' => 8.4844,  'lng' => -13.2344],
        'DUBAI'     => ['lat' => 25.2048, 'lng' => 55.2708],
        'TORONTO'   => ['lat' => 43.6532, 'lng' => -79.3832],
        'SYDNEY'    => ['lat' => -33.8688, 'lng' => 151.2093],
    ];

    if (isset($global_coords[$c])) {
        return $global_coords[$c];
    }

    return null;
}

/**
 * GA4 API verilerini çeker veya akıllı demo verilerini sunar
 */
function get_analytics_dashboard_data(string $range = '30d', bool $force_refresh = false): array {
    $propId = get_ga_property_id();
    $sa = get_ga_service_account();
    $dateParams = get_ga_date_range_params($range);

    // 1. Önbellek Kontrolü (15 dakika)
    $cache_key = 'ga_cache_' . $range;
    if (!$force_refresh) {
        $cached_raw = get_setting($cache_key, '');
        if (!empty($cached_raw)) {
            $cached_data = json_decode($cached_raw, true);
            if (is_array($cached_data) && ($cached_data['cached_at'] ?? 0) > time() - 900) {
                return $cached_data;
            }
        }
    }

    // 2. Canlı Google API Sorgusu
    if ($sa !== null && $propId !== '') {
        try {
            $token = get_ga_access_token($sa);

            // A) KPI & Özet Metrikleri
            $kpiReport = ga4_run_report($propId, $token, [
                'dateRanges' => [['startDate' => $dateParams['startDate'], 'endDate' => $dateParams['endDate']]],
                'metrics'    => [
                    ['name' => 'activeUsers'],
                    ['name' => 'newUsers'],
                    ['name' => 'sessions'],
                    ['name' => 'averageSessionDuration'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'bounceRate']
                ]
            ]);

            $kpiRow = $kpiReport['rows'][0]['metricValues'] ?? [];
            $activeUsers = (int)($kpiRow[0]['value'] ?? 0);
            $newUsers = (int)($kpiRow[1]['value'] ?? 0);
            $sessions = (int)($kpiRow[2]['value'] ?? 0);
            $avgDuration = (float)($kpiRow[3]['value'] ?? 0);
            $pageViews = (int)($kpiRow[4]['value'] ?? 0);
            $bounceRate = round((float)($kpiRow[5]['value'] ?? 0) * 100, 1);

            // B) Coğrafi Analiz (Ülke & Şehir Dağılımı)
            $geoReport = ga4_run_report($propId, $token, [
                'dateRanges' => [['startDate' => $dateParams['startDate'], 'endDate' => $dateParams['endDate']]],
                'dimensions' => [['name' => 'country'], ['name' => 'city']],
                'metrics'    => [['name' => 'activeUsers']],
                'orderBys'   => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]],
                'limit'      => 15
            ]);

            $geoList = [];
            if (!empty($geoReport['rows'])) {
                foreach ($geoReport['rows'] as $r) {
                    $countryName = $r['dimensionValues'][0]['value'] ?? 'Bilinmiyor';
                    $cityName = $r['dimensionValues'][1]['value'] ?? 'Bilinmiyor';
                    $users = (int)($r['metricValues'][0]['value'] ?? 0);

                    // Ülke kodu tespiti & bayrak
                    $cCode = '';
                    foreach (MEDIA_COUNTRIES as $iso => $cData) {
                        if (strcasecmp($cData['name'], $countryName) === 0 || strcasecmp($cData['en'] ?? '', $countryName) === 0) {
                            $cCode = $iso;
                            break;
                        }
                    }
                    $flag = $cCode ? country_code_to_flag($cCode) : '🌐';
                    $coords = get_global_city_coordinates($cityName, $cCode);

                    $geoList[] = [
                        'country' => $countryName,
                        'city'    => $cityName,
                        'flag'    => $flag,
                        'users'   => $users,
                        'lat'     => $coords['lat'] ?? null,
                        'lng'     => $coords['lng'] ?? null,
                    ];
                }
            }

            // C) Edinim / Trafik Kaynağı & Mecra (Pasta Grafik)
            $sourceReport = ga4_run_report($propId, $token, [
                'dateRanges' => [['startDate' => $dateParams['startDate'], 'endDate' => $dateParams['endDate']]],
                'dimensions' => [['name' => 'sessionSource'], ['name' => 'sessionMedium']],
                'metrics'    => [['name' => 'sessions'], ['name' => 'activeUsers']],
                'orderBys'   => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                'limit'      => 7
            ]);

            $sourceList = [];
            if (!empty($sourceReport['rows'])) {
                foreach ($sourceReport['rows'] as $r) {
                    $src = $r['dimensionValues'][0]['value'] ?? '(direct)';
                    $med = $r['dimensionValues'][1]['value'] ?? '(none)';
                    $sess = (int)($r['metricValues'][0]['value'] ?? 0);
                    $users = (int)($r['metricValues'][1]['value'] ?? 0);

                    $label = $src;
                    if ($med !== '(none)' && $med !== '') {
                        $label .= ' / ' . $med;
                    }

                    $sourceList[] = [
                        'label'    => $label,
                        'sessions' => $sess,
                        'users'    => $users
                    ];
                }
            }

            // D) İçerik Popülerliği (En Çok Gezilen Portfolyolar)
            $pagesReport = ga4_run_report($propId, $token, [
                'dateRanges' => [['startDate' => $dateParams['startDate'], 'endDate' => $dateParams['endDate']]],
                'dimensions' => [['name' => 'pagePath'], ['name' => 'pageTitle']],
                'metrics'    => [['name' => 'screenPageViews'], ['name' => 'activeUsers']],
                'orderBys'   => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                'limit'      => 10
            ]);

            $pagesList = [];
            if (!empty($pagesReport['rows'])) {
                foreach ($pagesReport['rows'] as $r) {
                    $path = $r['dimensionValues'][0]['value'] ?? '/';
                    $title = $r['dimensionValues'][1]['value'] ?? $path;
                    $views = (int)($r['metricValues'][0]['value'] ?? 0);
                    $users = (int)($r['metricValues'][1]['value'] ?? 0);

                    $pagesList[] = [
                        'path'  => $path,
                        'title' => clean_portfolio_title($title, $path),
                        'views' => $views,
                        'users' => $users
                    ];
                }
            }

            // E) Dönüşüm / Aksiyon (İletişim Formu, PDF vb.)
            $eventsReport = ga4_run_report($propId, $token, [
                'dateRanges' => [['startDate' => $dateParams['startDate'], 'endDate' => $dateParams['endDate']]],
                'dimensions' => [['name' => 'eventName']],
                'metrics'    => [['name' => 'eventCount'], ['name' => 'conversions']],
                'orderBys'   => [['metric' => ['metricName' => 'eventCount'], 'desc' => true]],
                'limit'      => 8
            ]);

            $eventsList = [];
            if (!empty($eventsReport['rows'])) {
                foreach ($eventsReport['rows'] as $r) {
                    $evName = $r['dimensionValues'][0]['value'] ?? '';
                    $cnt = (int)($r['metricValues'][0]['value'] ?? 0);
                    $conv = (int)($r['metricValues'][1]['value'] ?? 0);

                    $eventsList[] = [
                        'event'       => $evName,
                        'label'       => format_event_label($evName),
                        'count'       => $cnt,
                        'conversions' => $conv
                    ];
                }
            }

            // F) Zaman Çizelgesi Trendi (Günlük Trafik)
            $trendReport = ga4_run_report($propId, $token, [
                'dateRanges' => [['startDate' => $dateParams['startDate'], 'endDate' => $dateParams['endDate']]],
                'dimensions' => [['name' => 'date']],
                'metrics'    => [['name' => 'activeUsers'], ['name' => 'screenPageViews']],
                'orderBys'   => [['dimension' => ['dimensionName' => 'date'], 'desc' => false]],
            ]);

            $trendList = [];
            if (!empty($trendReport['rows'])) {
                foreach ($trendReport['rows'] as $r) {
                    $dRaw = $r['dimensionValues'][0]['value'] ?? '';
                    $users = (int)($r['metricValues'][0]['value'] ?? 0);
                    $views = (int)($r['metricValues'][1]['value'] ?? 0);
                    $trendList[] = [
                        'date'      => format_ga_date_label($dRaw),
                        'users'     => $users,
                        'pageviews' => $views
                    ];
                }
            }

            // G) Cihaz Dağılımı (Masaüstü vs Mobil vs Tablet)
            $devices = [
                'desktop' => ['users' => 0, 'sessions' => 0, 'duration_sec' => 0, 'duration_fmt' => '0sn', 'pct' => 0, 'label' => 'Masaüstü (Editoryal / Jüri / Küratör)'],
                'mobile'  => ['users' => 0, 'sessions' => 0, 'duration_sec' => 0, 'duration_fmt' => '0sn', 'pct' => 0, 'label' => 'Mobil (Sosyal Medya / Hızlı Keşif)'],
                'tablet'  => ['users' => 0, 'sessions' => 0, 'duration_sec' => 0, 'duration_fmt' => '0sn', 'pct' => 0, 'label' => 'Tablet (Portfolyo Sunumu)']
            ];
            try {
                $devReport = ga4_run_report($propId, $token, [
                    'dateRanges' => [['startDate' => $dateParams['startDate'], 'endDate' => $dateParams['endDate']]],
                    'dimensions' => [['name' => 'deviceCategory']],
                    'metrics'    => [['name' => 'activeUsers'], ['name' => 'averageSessionDuration'], ['name' => 'sessions']]
                ]);
                $totalDevUsers = 0;
                if (!empty($devReport['rows'])) {
                    foreach ($devReport['rows'] as $dr) {
                        $cat = strtolower($dr['dimensionValues'][0]['value'] ?? 'desktop');
                        $u = (int)($dr['metricValues'][0]['value'] ?? 0);
                        $dur = (float)($dr['metricValues'][1]['value'] ?? 0);
                        $s = (int)($dr['metricValues'][2]['value'] ?? 0);
                        $totalDevUsers += $u;
                        if (isset($devices[$cat])) {
                            $devices[$cat]['users'] = $u;
                            $devices[$cat]['sessions'] = $s;
                            $devices[$cat]['duration_sec'] = $dur;
                            $devices[$cat]['duration_fmt'] = format_ga_duration($dur);
                        }
                    }
                    if ($totalDevUsers > 0) {
                        foreach ($devices as $k => $dv) {
                            $devices[$k]['pct'] = round(($dv['users'] / $totalDevUsers) * 100, 1);
                        }
                    }
                }
            } catch (\Throwable $e) {}

            $liveData = [
                'is_live'           => true,
                'cached_at'         => time(),
                'cached_at_fmt'     => date('H:i, d.m.Y'),
                'property_id'       => $propId,
                'range'             => $range,
                'range_label'       => $dateParams['label'],
                'active_users'      => $activeUsers,
                'new_users'         => $newUsers,
                'sessions'          => $sessions,
                'avg_duration_sec'  => $avgDuration,
                'avg_duration_fmt'  => format_ga_duration($avgDuration),
                'pageviews'         => $pageViews,
                'bounce_rate'       => $bounceRate,
                'geo'               => $geoList,
                'sources'           => $sourceList,
                'pages'             => $pagesList,
                'events'            => $eventsList,
                'trend'             => $trendList,
                'devices'           => $devices
            ];

            set_setting($cache_key, json_encode($liveData, JSON_UNESCAPED_UNICODE));
            return $liveData;

        } catch (Exception $e) {
            // API hatası durumunda hata mesajını ekleyerek demo veriye geç
            $demo = get_demo_analytics_data($range, $dateParams);
            $demo['api_error'] = $e->getMessage();
            return $demo;
        }
    }

    // 3. Henüz API anahtarı girilmediğinde gösterilecek estetik Demo Veri
    return get_demo_analytics_data($range, $dateParams);
}

/**
 * Sayfa başlıklarını gereksiz site soneklerinden temizler
 */
function clean_portfolio_title(string $title, string $path): string {
    $owner = preg_quote(site_owner_name(), '/');
    $t = preg_replace('/\s*\|\s*' . $owner . '.*$/iu', '', $title);
    $t = preg_replace('/\s*-\s*' . $owner . '.*$/iu', '', $t);
    $t = trim($t);
    if (empty($t) || $t === '/') {
        return ($path === '/' || $path === '') ? 'Portfolyo Ana Sayfası' : $path;
    }
    return $t;
}

/**
 * Event adlarını Türkçe açıklayıcı etikete çevirir
 */
function format_event_label(string $eventName): string {
    $map = [
        'contact_form_submit'   => 'İletişim Formu Gönderimi',
        'portfolio_pdf_download'=> 'PDF Portfolyo İndirme',
        'photo_story_read'      => 'Fotoğraf Hikayesi İnceleme',
        'exif_details_open'     => 'EXIF Bilgisi Görüntüleme',
        'page_view'             => 'Sayfa Ziyareti',
        'first_visit'           => 'İlk Ziyaret',
        'session_start'         => 'Oturum Başlatma',
        'user_engagement'       => 'Kullanıcı Etkileşimi',
        'scroll'                => 'Sayfa Kaydırma (Scroll)',
        'click'                 => 'Dış Bağlantı Tıklaması'
    ];
    return $map[$eventName] ?? ucwords(str_replace('_', ' ', $eventName));
}

/**
 * 20261019 tarihini "19 Eki" formatına çevirir
 */
function format_ga_date_label(string $dRaw): string {
    if (strlen($dRaw) !== 8) return $dRaw;
    $day = substr($dRaw, 6, 2);
    $month = substr($dRaw, 4, 2);
    $months = [
        '01'=>'Oca','02'=>'Şub','03'=>'Mar','04'=>'Nis','05'=>'May','06'=>'Haz',
        '07'=>'Tem','08'=>'Ağu','09'=>'Eyl','10'=>'Eki','11'=>'Kas','12'=>'Ara'
    ];
    return (int)$day . ' ' . ($months[$month] ?? '');
}

/**
 * Estetik ve Gerçekçi Demo Analytics Veri Seti
 */
function get_demo_analytics_data(string $range, array $dateParams): array {
    $multipliers = ['7d' => 0.28, '30d' => 1.0, '90d' => 2.8, '365d' => 11.2];
    $m = $multipliers[$range] ?? 1.0;

    $baseUsers = (int)round(1842 * $m);
    $baseNew = (int)round(1420 * $m);
    $baseSessions = (int)round(2610 * $m);
    $baseViews = (int)round(9450 * $m);
    $avgDuration = 135; // 2dk 15sn

    $geo = [
        ['country' => 'Türkiye', 'city' => 'İstanbul', 'flag' => '🇹🇷', 'users' => (int)round(780 * $m), 'lat' => 41.0082, 'lng' => 28.9784],
        ['country' => 'Türkiye', 'city' => 'İzmir',    'flag' => '🇹🇷', 'users' => (int)round(190 * $m), 'lat' => 38.4237, 'lng' => 27.1428],
        ['country' => 'Almanya', 'city' => 'Berlin',   'flag' => '🇩🇪', 'users' => (int)round(145 * $m), 'lat' => 52.5200, 'lng' => 13.4050],
        ['country' => 'Amerika Birleşik Devletleri', 'city' => 'New York', 'flag' => '🇺🇸', 'users' => (int)round(130 * $m), 'lat' => 40.7128, 'lng' => -74.0060],
        ['country' => 'Birleşik Krallık', 'city' => 'Londra', 'flag' => '🇬🇧', 'users' => (int)round(110 * $m), 'lat' => 51.5074, 'lng' => -0.1278],
        ['country' => 'Fransa',  'city' => 'Paris',    'flag' => '🇫🇷', 'users' => (int)round(95 * $m),  'lat' => 48.8566, 'lng' => 2.3522],
        ['country' => 'Arnavutluk', 'city' => 'Tiran', 'flag' => '🇦🇱', 'users' => (int)round(85 * $m),  'lat' => 41.3275, 'lng' => 19.8187],
        ['country' => 'İtalya',  'city' => 'Roma',     'flag' => '🇮🇹', 'users' => (int)round(72 * $m),  'lat' => 41.9028, 'lng' => 12.4964],
        ['country' => 'Hollanda','city' => 'Amsterdam','flag' => '🇳🇱', 'users' => (int)round(68 * $m),  'lat' => 52.3676, 'lng' => 4.9041],
        ['country' => 'İsviçre', 'city' => 'Zürih',    'flag' => '🇨🇭', 'users' => (int)round(55 * $m),  'lat' => 47.3769, 'lng' => 8.5417],
        ['country' => 'Japonya', 'city' => 'Tokyo',    'flag' => '🇯🇵', 'users' => (int)round(42 * $m),  'lat' => 35.6762, 'lng' => 139.6503],
        ['country' => 'Sierra Leone', 'city' => 'Freetown', 'flag' => '🇸🇱', 'users' => (int)round(35 * $m), 'lat' => 8.4844, 'lng' => -13.2344],
    ];

    $sources = [
        ['label' => 'Direct / (none)',         'sessions' => (int)round(887 * $m), 'users' => (int)round(640 * $m)],
        ['label' => 'google / organic',        'sessions' => (int)round(730 * $m), 'users' => (int)round(530 * $m)],
        ['label' => 'instagram / social',      'sessions' => (int)round(522 * $m), 'users' => (int)round(380 * $m)],
        ['label' => 'behance.net / referral',  'sessions' => (int)round(287 * $m), 'users' => (int)round(195 * $m)],
        ['label' => 'x.com / social',          'sessions' => (int)round(130 * $m), 'users' => (int)round(90 * $m)],
        ['label' => 'linkedin / social',       'sessions' => (int)round(54 * $m),  'users' => (int)round(42 * $m)],
    ];

    // Demo sayfa listesi sitenin kendi içeriklerinden üretilir (GA bağlı değilken örnek görünüm)
    $pages = [['path' => '/', 'title' => site_t('Ana Sayfa', 'Homepage'), 'views' => (int)round(610 * $m), 'users' => (int)round(390 * $m)]];
    try {
        $demo_items = DB::getConnection()->query("SELECT `type`, `slug_en`, `slug`, `title_en`, `title_tr` FROM `items` WHERE `status` = 'published' AND `type` != 'page' ORDER BY `weight` ASC, `id` DESC LIMIT 9")->fetchAll();
        foreach ($demo_items as $i => $it) {
            $f = 0.9 ** ($i + 1);
            $pages[] = ['path' => '/' . $it['type'] . '/' . ($it['slug_en'] ?: $it['slug']), 'title' => $it['title_en'] ?: $it['title_tr'], 'views' => (int)round(2140 * $f * $m), 'users' => (int)round(1240 * $f * $m)];
        }
    } catch (\Throwable $e) {}
    usort($pages, fn($x, $y) => $y['views'] <=> $x['views']);

    $events = [
        ['event' => 'photo_zoom',             'label' => 'Tekil Kare Büyütme (Lightbox)',     'count' => (int)round(640 * $m), 'conversions' => 0],
        ['event' => 'story_complete',         'label' => 'Hikaye Tamamlama (%80+ Okuma)',      'count' => (int)round(412 * $m), 'conversions' => (int)round(412 * $m)],
        ['event' => 'portfolio_pdf_download', 'label' => 'PDF Portfolyo İndirme',              'count' => (int)round(82 * $m),  'conversions' => (int)round(82 * $m)],
        ['event' => 'contact_click',          'label' => 'İletişim & Saha Görevi Niyeti',      'count' => (int)round(46 * $m),  'conversions' => (int)round(46 * $m)],
        ['event' => 'exif_details_open',      'label' => 'EXIF & Çekim Bilgisi İnceleme',      'count' => (int)round(340 * $m), 'conversions' => 0],
    ];

    $devices = [
        'desktop' => [
            'users'        => (int)round(792 * $m),
            'sessions'     => (int)round(1120 * $m),
            'pct'          => 43.0,
            'duration_fmt' => '3dk 42sn',
            'label'        => 'Masaüstü (Editoryal / Jüri / Küratör)'
        ],
        'mobile' => [
            'users'        => (int)round(976 * $m),
            'sessions'     => (int)round(1380 * $m),
            'pct'          => 53.0,
            'duration_fmt' => '1dk 15sn',
            'label'        => 'Mobil (Sosyal Medya / Hızlı Keşif)'
        ],
        'tablet' => [
            'users'        => (int)round(74 * $m),
            'sessions'     => (int)round(110 * $m),
            'pct'          => 4.0,
            'duration_fmt' => '2dk 20sn',
            'label'        => 'Tablet (Portfolyo Sunumu)'
        ]
    ];

    // Günlük Trend (Son 14-30 gün)
    $trend = [];
    $daysCount = ($range === '7d') ? 7 : (($range === '90d') ? 30 : (($range === '365d') ? 30 : 20));
    $now = time();
    for ($i = $daysCount; $i >= 0; $i--) {
        $tTime = $now - ($i * 86400);
        $dLabel = date('j M', $tTime);
        $daySeed = sin($i * 0.7) * 25 + cos($i * 0.4) * 15;
        $dayUsers = max(30, (int)round((75 + $daySeed) * ($m > 1 ? 1.5 : 1)));
        $dayViews = (int)round($dayUsers * 4.2);
        $trend[] = [
            'date'      => $dLabel,
            'users'     => $dayUsers,
            'pageviews' => $dayViews
        ];
    }

    return [
        'is_live'          => false,
        'cached_at'        => time(),
        'cached_at_fmt'    => date('H:i, d.m.Y'),
        'property_id'      => get_ga_property_id(),
        'range'            => $range,
        'range_label'      => $dateParams['label'],
        'active_users'     => $baseUsers,
        'new_users'        => $baseNew,
        'sessions'         => $baseSessions,
        'avg_duration_sec' => $avgDuration,
        'avg_duration_fmt' => '2dk 15sn',
        'pageviews'        => $baseViews,
        'bounce_rate'      => 42.8,
        'geo'              => $geo,
        'sources'          => $sources,
        'pages'            => $pages,
        'events'           => $events,
        'trend'            => $trend,
        'devices'          => $devices
    ];
}
