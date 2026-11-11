<?php
/**
 * scripts/i18n_check.php
 * Dil paketlerinin bütünlüğünü denetler (CLI):
 *   1. Şablonlara gömülü, __a()/site_t() üzerinden geçmeyen arayüz metinleri
 *   2. Koddaki __a('TR','EN') / site_t('TR','EN') çağrılarından ve veri tablolarındaki
 *      (…_tr / …_en) çiftlerden herhangi bir dilde çevirisi olmayanlar
 *   3. Çeviride kaybolan yer tutucular (%s, %d, {site_title} …)
 *
 * Bir satırı bilerek dışarıda bırakmak için satıra "i18n-ignore" yorumu ekleyin.
 * Kullanım:  php scripts/i18n_check.php [dil]      (ör. php scripts/i18n_check.php hy)
 * Sorun yoksa 0, varsa 1 ile çıkar.
 */

if (PHP_SAPI !== 'cli') { exit(1); }
$root = dirname(__DIR__);
$only = $argv[1] ?? null;

$catalogs = [];
foreach (glob("$root/includes/i18n_ui/*.php") as $f) {
    $code = basename($f, '.php');
    if (in_array($code, ['tr', 'en'], true) || ($only && $code !== $only)) continue;
    $catalogs[$code] = include $f;
}

$files = array_merge(glob("$root/admin/*.php"), glob("$root/admin/includes/*.php"), glob("$root/includes/*.php"), glob("$root/includes/views/*.php"));
$files = array_values(array_filter($files, fn($f) => !preg_match('#i18n|admin_i18n_dict|setup_i18n|legal_defaults#', $f)));
$rel = fn($f) => substr($f, strlen($root) + 1);
$lit = fn($t) => eval('return ' . $t . ';');
$isStr = fn($t) => is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING;

$sources = [];   // İngilizce metin => ilk görüldüğü yer
$keys = [];      // __a('anahtar_adi', …) çağrıları
$hardcoded = []; // gömülü metin => dosya

foreach ($files as $f) {
    $toks = array_values(array_filter(token_get_all(file_get_contents($f)), fn($t) => !(is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))));
    $n = count($toks);
    $inScript = false;
    for ($i = 0; $i < $n; $i++) {
        $t = $toks[$i];
        // __a('TR', 'EN') ve site_t('TR', 'EN')
        if (is_array($t) && (($t[0] === T_STRING && in_array($t[1], ['__a', 'site_t'], true)) || ($t[0] === T_VARIABLE && $t[1] === '$L')) && ($toks[$i + 1] ?? null) === '('
            && $isStr($toks[$i + 2] ?? null) && ($toks[$i + 3] ?? null) === ',' && $isStr($toks[$i + 4] ?? null)) {
            $key = $lit($toks[$i + 2][1]);
            if (preg_match('/^[a-z0-9_]+$/', $key)) $keys[$key] ??= $rel($f) . ':' . $t[2];   // anahtar adı: panel dil dosyasında aranır
            else $sources[$lit($toks[$i + 4][1])] ??= $rel($f) . ':' . $t[2];
        }
        // veri tabloları: 'x_tr' => '…', 'x_en' => '…'  (ve 'tr' / 'en')
        if ($isStr($t) && preg_match("/^'(\w*?)en'$/", $t[1], $m) && is_array($toks[$i + 1] ?? null) && $toks[$i + 1][0] === T_DOUBLE_ARROW && $isStr($toks[$i + 2] ?? null)) {
            for ($j = max(0, $i - 40); $j < $i; $j++) {
                if ($isStr($toks[$j]) && $toks[$j][1] === "'" . $m[1] . "tr'") {
                    $en = $lit($toks[$i + 2][1]);
                    if (preg_match('/\p{L}{3}/u', $en) && !preg_match('#^(https?:|/|[a-z0-9_\-]+$)#', $en)) $sources[$en] ??= $rel($f) . ':' . $t[2];
                    break;
                }
            }
        }
        // şablon içindeki gömülü metin (PHP dışı HTML)
        if (is_array($t) && $t[0] === T_INLINE_HTML) {
            $raw = preg_replace('/^.*i18n-ignore.*$/m', '', $t[1]);   // satırda "i18n-ignore" varsa atla (örnek önizleme içeriği vb.)
            $html = preg_replace('#<script\b.*?</script>|<style\b.*?</style>|<!--.*?-->|<(textarea|pre|code)\b.*?</\1>#is', '', $raw);
            if (preg_match_all('/>([^<>]+)</u', $html, $mm)) foreach ($mm[1] as $txt) {
                $txt = trim($txt);
                if (preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $txt) && !preg_match('/[{};$=]/', $txt)) $hardcoded[$txt] = $rel($f);
            }
            // JS: <script> blokları PHP etiketleriyle bölünebilir; parçalar arasında "script içindeyiz" durumu taşınır
            $js = '';
            foreach (preg_split('#(<script\b[^>]*>|</script>)#i', $raw, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
                if (preg_match('#^<script\b#i', $part)) $inScript = true;
                elseif (strcasecmp($part, '</script>') === 0) $inScript = false;
                elseif ($inScript) $js .= "\n" . preg_replace('#^\s*//.*$#m', '', $part);
            }
            if (preg_match_all("/(['\"])((?:(?!\\1)[^\\n\\\\]|\\\\.)*?[çğıöşüÇĞİÖŞÜ](?:(?!\\1)[^\\n\\\\]|\\\\.)*?)\\1/u", $js, $jm)) foreach ($jm[2] as $txt) {
                if (mb_strlen($txt) > 1 && !preg_match('#^\)?\.replace\(|^/|\.test\(#', $txt)) $hardcoded[$txt] = $rel($f) . ' (js)';
            }
            // iki dilli kısayol: EN ? 'English' : 'Türkçe'  (Türkçe harf içermeyen metinleri de yakalar)
            if (preg_match_all("/\\b(?:EN|isEn|ADMIN_LANG === 'en')\\)?\\s*\\?\\s*'[^'\\n]*'\\s*:\\s*'([^'\\n]+)'/", $js, $tm)) foreach ($tm[1] as $txt) $hardcoded[$txt] = $rel($f) . ' (js ternary)';
            if (preg_match_all('/\b(?:placeholder|title|aria-label|alt)="([^"<]*[çğıöşüÇĞİÖŞÜ][^"<]*)"/u', $raw, $am)) foreach ($am[1] as $txt) $hardcoded[$txt] = $rel($f) . ' (attr)';
        }
    }
}

$ph = function (string $s): string { preg_match_all('/%\d*\$?[sd]|\{[a-z_]+\}/', $s, $m); sort($m[0]); return implode(' ', $m[0]); };
$missing = []; $broken = [];
foreach ($catalogs as $code => $cat) {
    foreach ($sources as $en => $where) {
        if (!isset($cat[$en]) && !isset($cat[trim($en)])) $missing[$code][] = "$en  ($where)";
        elseif (isset($cat[$en]) && $ph($en) !== $ph((string)$cat[$en])) $broken[$code][] = "$en  =>  {$cat[$en]}";
    }
    $panel = is_file("$root/admin/includes/i18n_langs/$code.php") ? include "$root/admin/includes/i18n_langs/$code.php" : [];
    foreach ($keys as $key => $where) if (!isset($panel[$key]) && !isset($cat[$key])) $missing[$code][] = "$key (anahtar)  ($where)";
}

$problems = 0;
echo "Kaynak metin: " . count($sources) . ", dil: " . implode(', ', array_keys($catalogs)) . "\n\n";
if ($hardcoded) {
    $problems += count($hardcoded);
    echo "Gömülü metin (" . count($hardcoded) . ") — __a('TR', 'EN') ile sarın:\n";
    foreach ($hardcoded as $txt => $where) echo "  - " . mb_substr($txt, 0, 90) . "   [$where]\n";
    echo "\n";
}
foreach ($missing as $code => $list) { $problems += count($list); echo "[$code] çevirisi eksik (" . count($list) . "):\n  - " . implode("\n  - ", array_slice($list, 0, getenv("ALL") ? 9999 : 30)) . (count($list) > 30 ? "\n  …" : '') . "\n\n"; }
foreach ($broken as $code => $list) { $problems += count($list); echo "[$code] yer tutucu bozuk (" . count($list) . "):\n  - " . implode("\n  - ", $list) . "\n\n"; }
echo $problems ? "Toplam sorun: $problems\n" : "Sorun yok: tüm metinler dil paketlerinde.\n";
exit($problems ? 1 : 0);
