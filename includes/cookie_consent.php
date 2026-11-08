<?php
/**
 * includes/cookie_consent.php
 * KVKK / GDPR uyumlu çerez onay bildirimi (Cookie Consent)
 *
 * - Yönetim panelinden açılıp kapatılabilir (settings: cookie_consent_enabled).
 * - Açıkken Google Analytics ve benzeri analitik betikler ziyaretçi "Kabul Et" demeden YÜKLENMEZ.
 * - "Kabul Et" ve "Reddet" eşit ağırlıkta sunulur, analitik tercih kutusu varsayılan olarak işaretsizdir
 *   (KVKK Kurulu Çerez Uygulamaları Hakkında Rehber & GDPR/ePrivacy ile uyumlu).
 * - Ziyaretçi kararı birinci taraf "er_consent" çerezinde saklanır (180 gün).
 *   Değer biçimi: "{sürüm}.{analitik 0|1}" örn. "1.1"
 * - Yönetici "Herkesten yeniden onay iste" dediğinde sürüm artar ve eski kararlar geçersiz olur.
 * - Metinler her aktif dil için panelden özelleştirilebilir; boş bırakılan alanlar
 *   hazır çeviri kataloğundan (includes/i18n_ui/{dil}.php) gelir.
 */

const COOKIE_CONSENT_NAME = 'er_consent';
const COOKIE_CONSENT_DAYS = 180;

function cookie_consent_enabled(): bool {
    return get_setting('cookie_consent_enabled', '0') === '1';
}

function cookie_consent_version(): int {
    return max(1, (int)get_setting('cookie_consent_version', '1'));
}

/**
 * Ziyaretçinin mevcut kararı: null (henüz karar yok / sürüm eskimiş) veya ['analytics' => bool]
 */
function cookie_consent_state(): ?array {
    $raw = (string)($_COOKIE[COOKIE_CONSENT_NAME] ?? '');
    if (!preg_match('/^(\d+)\.([01])$/', $raw, $m)) {
        return null;
    }
    if ((int)$m[1] !== cookie_consent_version()) {
        return null;
    }
    return ['analytics' => $m[2] === '1'];
}

/**
 * Analitik betikler sunucu tarafında doğrudan basılabilir mi?
 * Onay modülü kapalıysa her zaman evet; açıksa yalnızca güncel sürümde analitiğe onay verilmişse.
 */
function cookie_consent_allows_analytics(): bool {
    if (!cookie_consent_enabled()) {
        return true;
    }
    $state = cookie_consent_state();
    return $state !== null && $state['analytics'];
}

/**
 * Banner metin alanları: anahtar => [Türkçe varsayılan, İngilizce varsayılan (katalog anahtarı)]
 */
function cookie_consent_fields(): array {
    return [
        'title'        => ['Çerez Tercihleri', 'Cookie Preferences'],
        'message'      => [
            'Sitemizin çalışması için zorunlu çerezler kullanıyoruz. Ziyaretçi istatistiklerini ölçmek için analitik çerezleri ise yalnızca onay vermeniz halinde kullanırız. Tercihinizi dilediğiniz zaman değiştirebilirsiniz.',
            'We use strictly necessary cookies to run this site. Analytics cookies, which help us measure visitor statistics, are used only with your consent. You can change your choice at any time.',
        ],
        'accept'       => ['Kabul Et', 'Accept All'],
        'reject'       => ['Reddet', 'Reject'],
        'customize'    => ['Tercihleri Yönet', 'Manage Preferences'],
        'save'         => ['Tercihleri Kaydet', 'Save Preferences'],
        'necessary'    => ['Zorunlu Çerezler', 'Strictly Necessary Cookies'],
        'necessary_desc' => [
            'Sitenin temel işlevleri (tema tercihi, güvenlik, oturum) için gereklidir ve kapatılamaz.',
            'Required for core site functions (theme choice, security, session) and cannot be turned off.',
        ],
        'always_on'    => ['Her zaman aktif', 'Always active'],
        'analytics'    => ['Analitik Çerezler', 'Analytics Cookies'],
        'analytics_desc' => [
            'Google Analytics aracılığıyla ziyaret sayısı ve en çok ilgi gören çalışmalar gibi anonim istatistikler toplanır.',
            'Collects anonymous statistics such as visit counts and most-viewed works via Google Analytics.',
        ],
        'policy'       => ['Aydınlatma Metni', 'Privacy Notice'],
    ];
}

/**
 * Bir dil için banner metinleri (panelde girilen özel metin > hazır çeviri).
 */
function cookie_consent_strings(string $lang): array {
    $out = [];
    foreach (cookie_consent_fields() as $key => [$tr, $en]) {
        $custom = trim(get_setting('cookie_banner_' . $key . '_' . $lang, ''));
        $out[$key] = $custom !== '' ? $custom : site_t($tr, $en, $lang);
    }
    return $out;
}

/**
 * Aydınlatma / çerez politikası bağlantısı (panelde özel URL girilebilir).
 */
function cookie_consent_policy_url(string $lang): string {
    $custom = trim(get_setting('cookie_banner_policy_url_' . $lang, ''));
    if ($custom !== '') {
        return preg_match('#^(https?:)?//#i', $custom) ? $custom : url(ltrim($custom, '/'));
    }
    if (get_setting('legal_pages_enabled', '1') !== '1') {
        return '';
    }
    return url($lang . '/' . get_static_slug('privacy_notice', $lang) . '/');
}

/**
 * <head> içine basılır: Google Analytics (onaya bağlı), banner stilleri ve betiği.
 */
function render_cookie_consent_head(string $lang, string $ga_id): void {
    $enabled = cookie_consent_enabled();
    $ga_id = trim($ga_id);
    $ga_safe = preg_match('/^[A-Za-z0-9-]+$/', $ga_id) ? $ga_id : '';

    // 1) Onay modülü kapalı ya da analitiğe zaten onay verilmiş: GA'yı doğrudan yükle
    if ($ga_safe !== '' && cookie_consent_allows_analytics()) {
        echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($ga_safe) . '"></script>' . "\n";
        echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config'," . json_encode($ga_safe) . ");</script>\n";
    }

    if (!$enabled) {
        return;
    }

    $strings = cookie_consent_strings($lang);
    $config = [
        'name'     => COOKIE_CONSENT_NAME,
        'version'  => cookie_consent_version(),
        'days'     => COOKIE_CONSENT_DAYS,
        'ga'       => $ga_safe,
        'position' => get_setting('cookie_banner_position', 'box') === 'bar' ? 'bar' : 'box',
        'policy'   => cookie_consent_policy_url($lang),
        'decided'  => cookie_consent_state() !== null,
        'analytics'=> cookie_consent_allows_analytics(),
        't'        => $strings,
    ];
    $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>
    <style id="er-cc-style">
        .er-cc{position:fixed;z-index:2147483000;font-family:var(--text-font,system-ui,sans-serif);color:var(--text-0,#212121);background:var(--bg-0,#fff);border:1px solid var(--bg-2,#e5e5e5);box-shadow:0 12px 40px rgba(0,0,0,.18);line-height:1.5;font-size:14px;box-sizing:border-box}
        .er-cc *{box-sizing:border-box}
        .er-cc[hidden]{display:none!important}
        .er-cc--box{left:20px;bottom:20px;width:min(420px,calc(100vw - 32px));border-radius:12px;padding:20px}
        .er-cc--bar{left:0;right:0;bottom:0;border-radius:0;border-width:1px 0 0;padding:18px max(16px,calc((100vw - 1100px)/2))}
        .er-cc--bar .er-cc-main{display:flex;gap:24px;align-items:center;flex-wrap:wrap}
        .er-cc--bar .er-cc-text{flex:1 1 420px}
        .er-cc-title{display:block;font-family:var(--header-font,inherit);font-size:16px;font-weight:700;margin:0 0 6px}
        .er-cc-msg{margin:0;color:var(--text-1,#555)}
        .er-cc-msg a{color:inherit;text-decoration:underline;text-underline-offset:2px;border:0}
        .er-cc-msg a:hover{color:var(--primary-color,#fdd000)}
        .er-cc-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
        .er-cc--bar .er-cc-actions{margin-top:0}
        .er-cc-btn{appearance:none;font:inherit;font-size:13px;font-weight:700;padding:10px 16px;border-radius:8px;cursor:pointer;border:1px solid var(--text-0,#212121);background:transparent;color:var(--text-0,#212121);flex:1 1 auto;min-height:40px}
        .er-cc-btn--main{background:var(--text-0,#212121);color:var(--bg-0,#fff)}
        .er-cc-btn--link{border-color:transparent;text-decoration:underline;text-underline-offset:3px;flex:0 0 auto;padding-left:6px;padding-right:6px}
        .er-cc-btn:hover{border-color:var(--primary-color,#fdd000)}
        .er-cc-btn--main:hover{background:var(--primary-color,#fdd000);color:#111}
        .er-cc-btn:focus-visible,.er-cc-switch input:focus-visible+span{outline:2px solid var(--primary-color,#fdd000);outline-offset:2px}
        .er-cc-prefs{margin-top:14px;border-top:1px solid var(--bg-2,#e5e5e5);padding-top:6px}
        .er-cc-row{display:flex;gap:14px;align-items:flex-start;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--bg-2,#e5e5e5)}
        .er-cc-row strong{display:block;font-size:13.5px}
        .er-cc-row p{margin:2px 0 0;font-size:12.5px;color:var(--text-1,#555)}
        .er-cc-always{font-size:12px;font-weight:700;white-space:nowrap;color:var(--text-1,#555);padding-top:2px}
        .er-cc-switch{position:relative;display:inline-flex;flex:0 0 auto;margin-top:2px}
        .er-cc-switch input{position:absolute;opacity:0;width:100%;height:100%;margin:0;cursor:pointer}
        .er-cc-switch span{width:40px;height:22px;border-radius:22px;background:var(--bg-2,#ddd);position:relative;transition:background .2s}
        .er-cc-switch span::after{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);transition:transform .2s}
        .er-cc-switch input:checked+span{background:var(--primary-color,#fdd000)}
        .er-cc-switch input:checked+span::after{transform:translateX(18px)}
        @media (max-width:600px){.er-cc--box{left:10px;right:10px;bottom:10px;width:auto;padding:16px}.er-cc-btn{flex:1 1 100%}.er-cc-btn--link{flex:1 1 100%}}
        @media (prefers-reduced-motion:reduce){.er-cc-switch span,.er-cc-switch span::after{transition:none}}
    </style>
    <script>
    (function () {
        var C = <?= $json ?>;
        var T = C.t;

        function setCookie(analytics) {
            var v = C.version + '.' + (analytics ? '1' : '0');
            var secure = location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = C.name + '=' + v + '; Max-Age=' + (C.days * 86400) + '; Path=/; SameSite=Lax' + secure;
        }

        function loadAnalytics() {
            if (!C.ga || window.__erGaLoaded || typeof window.gtag === 'function') return;
            window.__erGaLoaded = true;
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { dataLayer.push(arguments); };
            gtag('js', new Date());
            gtag('config', C.ga);
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(C.ga);
            document.head.appendChild(s);
        }

        // Onay geri çekildiğinde Google Analytics çerezlerini temizle (_ga, _ga_XXXX, _gid, _gat)
        function clearAnalyticsCookies() {
            var host = location.hostname, parts = host.split('.'), domains = ['', host];
            for (var i = 1; i < parts.length - 1; i++) domains.push('.' + parts.slice(i).join('.'));
            document.cookie.split(';').forEach(function (c) {
                var n = c.split('=')[0].trim();
                if (/^(_ga|_gid|_gat)/.test(n)) {
                    domains.forEach(function (d) {
                        document.cookie = n + '=; Max-Age=0; Path=/' + (d ? '; Domain=' + d : '');
                    });
                }
            });
        }

        function decide(analytics) {
            var had = C.analytics;
            setCookie(analytics);
            C.decided = true;
            C.analytics = analytics;
            if (analytics) {
                loadAnalytics();
            } else {
                clearAnalyticsCookies();
                if (had || window.__erGaLoaded) { location.reload(); return; }
            }
            hide();
        }

        var root, prefs, toggle, lastFocus;

        function el(tag, attrs, text) {
            var n = document.createElement(tag);
            for (var k in (attrs || {})) n.setAttribute(k, attrs[k]);
            if (text != null) n.textContent = text;
            return n;
        }

        function build() {
            root = el('div', { 'class': 'er-cc er-cc--' + C.position, role: 'dialog', 'aria-live': 'polite', 'aria-labelledby': 'er-cc-title', 'aria-describedby': 'er-cc-msg' });
            root.hidden = true;
            var main = el('div', { 'class': 'er-cc-main' });
            var text = el('div', { 'class': 'er-cc-text' });
            text.appendChild(el('span', { 'class': 'er-cc-title', id: 'er-cc-title' }, T.title));
            var msg = el('p', { 'class': 'er-cc-msg', id: 'er-cc-msg' }, T.message + ' ');
            if (C.policy) msg.appendChild(el('a', { href: C.policy }, T.policy));
            text.appendChild(msg);
            main.appendChild(text);

            var actions = el('div', { 'class': 'er-cc-actions' });
            var bReject = el('button', { type: 'button', 'class': 'er-cc-btn' }, T.reject);
            var bAccept = el('button', { type: 'button', 'class': 'er-cc-btn er-cc-btn--main' }, T.accept);
            var bCustom = el('button', { type: 'button', 'class': 'er-cc-btn er-cc-btn--link', 'aria-expanded': 'false', 'aria-controls': 'er-cc-prefs' }, T.customize);
            bReject.addEventListener('click', function () { decide(false); });
            bAccept.addEventListener('click', function () { decide(true); });
            bCustom.addEventListener('click', function () {
                var open = prefs.hidden;
                prefs.hidden = !open;
                bCustom.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) toggle.focus();
            });
            actions.appendChild(bReject);
            actions.appendChild(bAccept);
            actions.appendChild(bCustom);
            main.appendChild(actions);
            root.appendChild(main);

            prefs = el('div', { 'class': 'er-cc-prefs', id: 'er-cc-prefs' });
            prefs.hidden = true;
            var r1 = el('div', { 'class': 'er-cc-row' });
            var r1t = el('div');
            r1t.appendChild(el('strong', null, T.necessary));
            r1t.appendChild(el('p', null, T.necessary_desc));
            r1.appendChild(r1t);
            r1.appendChild(el('span', { 'class': 'er-cc-always' }, T.always_on));
            prefs.appendChild(r1);

            var r2 = el('div', { 'class': 'er-cc-row' });
            var r2t = el('div');
            r2t.appendChild(el('strong', { id: 'er-cc-an-label' }, T.analytics));
            r2t.appendChild(el('p', null, T.analytics_desc));
            r2.appendChild(r2t);
            var sw = el('label', { 'class': 'er-cc-switch' });
            toggle = el('input', { type: 'checkbox', role: 'switch', 'aria-labelledby': 'er-cc-an-label' });
            sw.appendChild(toggle);
            sw.appendChild(el('span', { 'aria-hidden': 'true' }));
            r2.appendChild(sw);
            prefs.appendChild(r2);

            var pa = el('div', { 'class': 'er-cc-actions' });
            var bSave = el('button', { type: 'button', 'class': 'er-cc-btn er-cc-btn--main' }, T.save);
            bSave.addEventListener('click', function () { decide(!!toggle.checked); });
            pa.appendChild(bSave);
            prefs.appendChild(pa);
            root.appendChild(prefs);

            root.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && C.decided) hide();
            });
            document.body.appendChild(root);
        }

        function show(openPrefs) {
            if (!root) build();
            toggle.checked = !!(C.decided && C.analytics); // varsayılan: işaretsiz
            prefs.hidden = !openPrefs;
            root.querySelector('.er-cc-btn--link').setAttribute('aria-expanded', openPrefs ? 'true' : 'false');
            lastFocus = document.activeElement;
            root.hidden = false;
            var f = openPrefs ? toggle : root.querySelector('.er-cc-btn');
            if (f && (openPrefs || C.decided)) f.focus();
        }

        function hide() {
            if (!root) return;
            root.hidden = true;
            if (lastFocus && lastFocus.focus && document.contains(lastFocus)) lastFocus.focus();
        }

        // Footer'daki "Çerez Tercihleri" bağlantısı ya da herhangi bir [data-cookie-settings] öğesi
        window.erCookieSettings = function () { show(true); };
        document.addEventListener('click', function (e) {
            var t = e.target.closest && e.target.closest('[data-cookie-settings]');
            if (t) { e.preventDefault(); show(true); }
        });

        function init() { if (!C.decided) show(false); }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
    </script>
    <?php
}
