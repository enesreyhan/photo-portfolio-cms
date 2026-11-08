<?php
/**
 * admin/api-translate.php
 * Çok Dilli İçerik Editörleri ve Merkezi Çeviri Stüdyosu için Otomatik Çeviri API Uç Noktası
 * HTML etiketlerini (<p>, <strong>, <em> vb.) koruyarak cümle ve başlıkları hedef dile çevirir.
 * Google Translate (dict-chrome-ex/gtx) ve MyMemory servislerini koruma kalkanıyla çalıştırır.
 * Kota ve hata uyarılarını (MYMEMORY WARNING vb.) asla alanlara doldurmaz; güvenle filtreler.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
@set_time_limit(120);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$raw_input = file_get_contents('php://input');
$payload = json_decode($raw_input, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$csrf = $payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Geçersiz güvenlik anahtarı (CSRF).']);
    exit;
}

$source_lang = strtolower(trim((string)($payload['source_lang'] ?? 'tr')));
$target_lang = strtolower(trim((string)($payload['target_lang'] ?? 'en')));
$texts = $payload['texts'] ?? [];

if (!is_array($texts)) {
    $texts = [$texts];
}

if ($target_lang === '' || $source_lang === $target_lang) {
    echo json_encode([
        'ok' => true,
        'source_lang' => $source_lang,
        'target_lang' => $target_lang,
        'translations' => array_values($texts),
        'results' => array_map(function($t, $i) {
            return [
                'index' => $i,
                'success' => true,
                'service' => 'none',
                'translation' => $t,
            ];
        }, array_values($texts), array_keys($texts))
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Metnin MyMemory veya başka bir çeviri API'si tarafından dönülen kota/hata uyarısı olup olmadığını denetler.
 */
function is_translation_warning_or_error(string $text): bool {
    $trimmed = trim($text);
    if ($trimmed === '') return false;
    $lower = strtolower($trimmed);
    
    if (str_contains($lower, 'mymemory')) return true;
    if (str_contains($lower, 'used all available free translations')) return true;
    if (str_contains($lower, 'translated.net/doc/usagelimits')) return true;
    if (str_contains($lower, 'next available in')) return true;
    if (str_contains($lower, 'quota finished') || str_contains($lower, 'quota exceeded')) return true;
    if (str_contains($lower, 'rate limit') || str_contains($lower, 'too many requests')) return true;
    if (str_starts_with($lower, 'warning:') && (str_contains($lower, 'limit') || str_contains($lower, 'available') || str_contains($lower, 'free') || str_contains($lower, 'translate'))) return true;
    
    return false;
}

// Global uyarı listesi
$g_translation_warnings = [];

/**
 * Tekil bir düz metni Google Translate / MyMemory servisleri üzerinden çevirir.
 * Kesinlikle uyarı metni veya hata yazısı döndürmez; servis başarısız olursa boş string döner.
 */
function translate_plain_segment(string $text, string $sl, string $tl, ?string &$used_service = null): string {
    global $g_translation_warnings;
    static $segment_cache = [];
    $trimmed = trim($text);
    if ($trimmed === '') {
        $used_service = 'empty';
        return $text;
    }

    $cache_key = $sl . '|' . $tl . '|' . $trimmed;
    if (isset($segment_cache[$cache_key])) {
        $used_service = 'cache';
        return $segment_cache[$cache_key];
    }

    // 1. Birincil Servis: Google Translate
    // 'dict-chrome-ex' istemcisi bot engelini aşarak en yüksek başarı oranını sağlar.
    $google_clients = ['dict-chrome-ex', 'gtx'];
    foreach ($google_clients as $client) {
        $url = 'https://translate.googleapis.com/translate_a/single?client=' . rawurlencode($client)
             . '&sl=' . rawurlencode($sl ?: 'auto')
             . '&tl=' . rawurlencode($tl)
             . '&dt=t&q=' . rawurlencode($trimmed);

        $res_body = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            ]);
            $res_body = (string)curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code !== 200) {
                $res_body = '';
            }
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 8,
                    'header'  => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36\r\n",
                ],
            ]);
            $res_body = (string)@file_get_contents($url, false, $ctx);
        }

        if ($res_body !== '' && !str_contains($res_body, '<html') && !str_contains($res_body, 'We\'re sorry')) {
            $decoded = json_decode($res_body, true);
            if (is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
                $out = '';
                foreach ($decoded[0] as $part) {
                    if (isset($part[0]) && is_string($part[0])) {
                        $out .= $part[0];
                    }
                }
                $out = trim($out);
                if ($out !== '' && !is_translation_warning_or_error($out)) {
                    $used_service = 'Google Translate';
                    return $segment_cache[$cache_key] = $out;
                }
            }
        }
    }

    // 2. Yedek Servis: MyMemory Translation API
    // &de parametresi ile kota limitini genişletir, fakat kota uyarısı dönerse ASLA kabul etmez.
    $email = get_setting('contact_email'); // MyMemory: e-posta verilirse günlük kota yükselir
    $mm_url = 'https://api.mymemory.translated.net/get?q=' . rawurlencode($trimmed)
            . '&langpair=' . rawurlencode(($sl ?: 'tr') . '|' . $tl)
            . ($email !== '' ? '&de=' . rawurlencode($email) : '');

    $mm_body = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($mm_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        ]);
        $mm_body = (string)curl_exec($ch);
    }
    if ($mm_body !== '') {
        $mm_json = json_decode($mm_body, true);
        $mm_status = (int)($mm_json['responseStatus'] ?? 0);
        $quotaFinished = !empty($mm_json['quotaFinished']);
        $raw_translated = (string)($mm_json['responseData']['translatedText'] ?? '');

        if ($quotaFinished || $mm_status !== 200 || is_translation_warning_or_error($raw_translated)) {
            $g_translation_warnings[] = 'MyMemory günlük çeviri limitine ulaşıldı (Alan hata yazısıyla doldurulmadı).';
            // KESİNLİKLE uyarı metnini döndürme!
        } elseif ($raw_translated !== '') {
            $candidate = html_entity_decode($raw_translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!is_translation_warning_or_error($candidate)) {
                $used_service = 'MyMemory';
                return $segment_cache[$cache_key] = $candidate;
            }
        }
    }

    // Servisler yanıt vermezse veya kota aşılmışsa:
    // ASLA hata mesajı dönme; başarısız olduğunu belirtmek için boş string dön
    $used_service = 'failed';
    return '';
}

/**
 * Zengin metin (HTML) veya düz metni yapısını bozmadan çevirir
 */
function translate_preserving_markup(string $input, string $sl, string $tl, ?string &$used_service = null): string {
    if (trim($input) === '') {
        $used_service = 'empty';
        return '';
    }

    // HTML etiketi içermiyorsa doğrudan çevir
    if (!preg_match('/<[a-z][\s\S]*>/i', $input)) {
        return translate_plain_segment($input, $sl, $tl, $used_service);
    }

    // HTML etiketlerini koruyarak yalnızca metin düğümlerini çevir
    $parts = preg_split('/(<[^>]+>)/u', $input, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) {
        return translate_plain_segment(strip_tags($input), $sl, $tl, $used_service);
    }

    $result = '';
    $has_success = false;
    $services_used = [];

    foreach ($parts as $part) {
        if ($part === '') continue;
        if ($part[0] === '<' && substr($part, -1) === '>') {
            $result .= $part;
        } else {
            $decoded_text = html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (trim($decoded_text) === '') {
                $result .= $part;
            } else {
                $lead_space = preg_match('/^\s+/u', $decoded_text, $m1) ? $m1[0] : '';
                $trail_space = preg_match('/\s+$/u', $decoded_text, $m2) ? $m2[0] : '';
                $seg_service = '';
                $translated = translate_plain_segment(trim($decoded_text), $sl, $tl, $seg_service);
                if ($translated !== '' && !is_translation_warning_or_error($translated)) {
                    $has_success = true;
                    $services_used[] = $seg_service;
                    $result .= $lead_space . htmlspecialchars($translated, ENT_NOQUOTES, 'UTF-8') . $trail_space;
                } else {
                    // Çeviri başarısız olursa orijinal metni koru
                    $result .= $lead_space . htmlspecialchars($decoded_text, ENT_NOQUOTES, 'UTF-8') . $trail_space;
                }
            }
        }
    }

    $used_service = $has_success ? implode(', ', array_unique($services_used)) : 'failed';
    return $result;
}

$translations = [];
$results = [];

foreach ($texts as $idx => $txt) {
    $str = (string)$txt;
    if (trim($str) === '') {
        $translations[$idx] = '';
        $results[$idx] = [
            'index'       => $idx,
            'success'     => true,
            'service'     => 'empty',
            'translation' => '',
            'error'       => null,
        ];
        continue;
    }

    $used_service = '';
    $translated = translate_preserving_markup($str, $source_lang, $target_lang, $used_service);

    // Ek güvenlik filtresi: Eğer translated içinde uyarı/hata metni kalmışsa temizle
    if (is_translation_warning_or_error($translated)) {
        $translated = '';
        $used_service = 'failed';
    }

    $is_success = ($translated !== '' && $used_service !== 'failed');
    $translations[$idx] = $translated;
    $results[$idx] = [
        'index'       => $idx,
        'success'     => $is_success,
        'service'     => $used_service ?: 'unknown',
        'translation' => $translated,
        'error'       => $is_success ? null : 'Çeviri servisi yanıt vermedi veya günlük kota ulaşıldı.',
    ];
}

echo json_encode([
    'ok'           => true,
    'source_lang'  => $source_lang,
    'target_lang'  => $target_lang,
    'translations' => $translations,
    'results'      => $results,
    'warnings'     => array_values(array_unique($g_translation_warnings)),
], JSON_UNESCAPED_UNICODE);

