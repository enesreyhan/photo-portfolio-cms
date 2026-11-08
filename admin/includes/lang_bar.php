<?php
/**
 * admin/includes/lang_bar.php
 * Ortak içerik dili çubuğu — bütün düzenleyicilerde aynı yerde, aynı davranış.
 *
 *  - Dil düğmeleri: bayrak, adı ve doluluk (✓ ya da 3/5)
 *  - Seçilen dil sayfadaki bütün yazı alanlarını o dile geçirir
 *  - Kaynak metin: ana dil dışındaki bir dili yazarken her alanın altında kaynak dildeki metin gri görünür
 *  - Çeviri menüsü: boş alanları kaynaktan kopyala / otomatik çevir
 *  - Önizle: seçili dildeki sayfayı açar
 *
 * Alanlar adlarından bulunur: name="title_hy", name="rows[3][title_hy]", name="x[hy]" ya da
 * id="editable_hy" olan contenteditable. Gizli (type=hidden) alanlar ve slug alanları sayılmaz.
 *
 * Sayfa kendi dil sekmelerini kullanıyorsa çubuk onları (gizleyip) tıklar:
 *   'click' => '#tabBtn_{lang}'            — sayfanın kendi düğmesi
 *   'panes' => ['.pn-lang-pane', '#pn_pane_{lang}'] — paneller göster/gizle
 *   (hiçbiri verilmezse [data-lb-pane="xx"] öğeleri gösterilip gizlenir)
 *   'hide'  => '.blog-lang-tabs, ...'      — sayfanın eski dil arayüzü (gizlenir)
 *   'copy_fn' / 'translate_fn' => sayfanın kendi kopyala/çevir işlevi (yoksa genel olanı çalışır)
 *   'preview' => ['tr' => 'https://…', 'en' => …]
 *   'scope'   => '#form'  — alanların aranacağı kapsayıcı (varsayılan: .admin-content)
 *   'initial' => 'tr'
 */

/** window.erSlugify: panelin adres önerileri sunucudaki slugify() ile aynı harf tablosunu kullanır (header.php basar) */
function lang_slugify_js(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    echo "<script>\n";
    ?>
// Adres önerisi: sunucudaki slugify() ile aynı harf tablosu (includes/helpers.php → slug_translit_map)
window.ER_SLUG_MAP = <?= json_encode(slug_translit_map(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
window.erSlugify = function (text) {
    var tr = { 'ı': 'i', 'İ': 'i', 'I': 'i', 'ş': 's', 'Ş': 's', 'ğ': 'g', 'Ğ': 'g', 'ü': 'u', 'Ü': 'u', 'ö': 'o', 'Ö': 'o', 'ç': 'c', 'Ç': 'c' };
    var s = String(text || '').trim().replace(/['’`´]/g, '').replace(/[ıİIşŞğĞüÜöÖçÇ]/g, function (m) { return tr[m]; }).toLowerCase();
    var map = window.ER_SLUG_MAP || {};
    s = s.replace(/ու/g, map['ու'] || 'u');
    s = s.split('').map(function (ch) { return Object.prototype.hasOwnProperty.call(map, ch) ? map[ch] : ch; }).join('');
    if (s.normalize) s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    return s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
};
<?php
    echo "</script>\n";
}

/** Panelde düzenlenebilen içerik dilleri (sitede etkin olanlar, panelde sıralandığı gibi) */
function lang_bar_languages(): array {
    $out = [];
    foreach (get_active_languages() as $l) {
        $code = (string)$l['code'];
        $out[] = [
            'code'     => $code,
            'name'     => (string)($l['name_native'] ?: strtoupper($code)),
            'flag'     => (string)($l['flag'] ?? '🌐'),
            'fallback' => (string)($l['fallback_lang'] ?? 'en'),
            'dir'      => (string)($l['direction'] ?? 'ltr'),
        ];
    }
    return $out;
}

/** Çubuğun açılacağı dil: panel dili etkinse o, değilse ilk dil */
function lang_bar_initial(): string {
    $codes = array_column(lang_bar_languages(), 'code');
    $admin = $GLOBALS['admin_lang'] ?? 'tr';
    if (in_array($admin, $codes, true)) return $admin;
    return in_array('tr', $codes, true) ? 'tr' : ($codes[0] ?? 'tr');
}

function lang_bar(array $o = []): void {
    static $assets = false;
    $langs = lang_bar_languages();
    if (count($langs) < 2 && empty($o['always'])) return; // tek dilde çubuğa gerek yok
    $id  = 'lb_' . substr(md5(json_encode($o) . mt_rand()), 0, 6);
    $cfg = [
        'id'          => $id,
        'langs'       => $langs,
        'initial'     => $o['initial'] ?? lang_bar_initial(),
        'main'        => lang_bar_initial(), // ana dil: panel dili; bu dilde yazarken kaynak metin gösterilmez
        'scope'       => $o['scope'] ?? '.admin-content',
        'click'       => $o['click'] ?? '',
        'panes'       => $o['panes'] ?? null,
        'hide'        => $o['hide'] ?? '',
        'copy_fn'     => $o['copy_fn'] ?? '',
        'translate_fn'=> $o['translate_fn'] ?? '',
        'preview'     => $o['preview'] ?? new stdClass(),
        'ignore'      => $o['ignore'] ?? [],
        'csrf'        => csrf_token(),
        'api'         => url('admin/api-translate.php'),
        't'           => [
            'source'      => __a('Kaynak dili yanında göster', 'Show source language alongside'),
            'translate'   => __a('Çeviri', 'Translate'),
            'copy'        => __a('Boş alanları %s metninden kopyala', 'Copy empty fields from %s'),
            'auto'        => __a('Boş alanları otomatik çevir (%s → %s)', 'Auto-translate empty fields (%s → %s)'),
            'preview'     => __a('Önizle', 'Preview'),
            'working'     => __a('Çevriliyor…', 'Translating…'),
            'done'        => __a('%d alan dolduruldu. Kaydetmeyi unutmayın.', '%d fields filled. Remember to save.'),
            'nothing'     => __a('Doldurulacak boş alan yok.', 'No empty fields to fill.'),
            'failed'      => __a('Çeviri servisine ulaşılamadı. Biraz sonra yeniden deneyin.', 'The translation service could not be reached. Try again shortly.'),
            'same'        => __a('Kaynak dil ile aynı dil seçili.', 'The source language is selected.'),
            'full'        => __a('Bütün alanlar dolu', 'All fields filled'),
            'partial'     => __a('%d / %d alan dolu', '%d of %d fields filled'),
            'editing'     => __a('Düzenlenen dil', 'Editing language'),
            'from'        => __a('Kaynak', 'Source'),
        ],
    ];
    ?>
<div class="lang-bar" id="<?= e($id) ?>" role="toolbar" aria-label="<?= e(__a('İçerik dili', 'Content language')) ?>">
    <span class="lb-label"><?= e(__a('İçerik dili', 'Content language')) ?></span>
    <div class="lb-langs" role="tablist"></div>
    <div class="lb-tools">
        <label class="lb-src-toggle"><input type="checkbox" class="lb-src-cb"> <span><?= e($cfg['t']['source']) ?></span></label>
        <div class="lb-menu">
            <button type="button" class="lb-btn lb-menu-btn" aria-haspopup="true" aria-expanded="false"><?= e($cfg['t']['translate']) ?> ▾</button>
            <div class="lb-menu-list" hidden>
                <button type="button" class="lb-act" data-act="copy"></button>
                <button type="button" class="lb-act" data-act="translate"></button>
            </div>
        </div>
        <a class="lb-btn lb-preview" href="#" target="_blank" rel="noopener" hidden>↗ <?= e($cfg['t']['preview']) ?></a>
    </div>
    <div class="lb-status" aria-live="polite" hidden></div>
</div>
<script>window.ER_LANG_BARS = window.ER_LANG_BARS || []; window.ER_LANG_BARS.push(<?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>);</script>
    <?php
    if ($assets) return;
    $assets = true;
    ?>
<style>
.lang-bar { position: sticky; top: calc(var(--admin-topbar-h, 60px) + 8px); z-index: 40; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 0 0 18px; padding: 8px 10px; background: var(--bg-surface, #fff); border: 1px solid var(--border-subtle, #e5e7eb); border-radius: 10px; box-shadow: 0 4px 14px rgba(15, 23, 42, .05); }
.lang-bar .lb-label { font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--text-muted, #64748b); padding-left: 4px; }
.lang-bar .lb-langs { display: flex; gap: 6px; flex-wrap: wrap; }
.lang-bar .lb-lang { display: inline-flex; align-items: center; gap: 6px; padding: 6px 11px; border: 1px solid var(--border-subtle, #e5e7eb); border-radius: 999px; background: transparent; color: var(--text-main, #0f172a); font: 600 13px/1.2 inherit; cursor: pointer; }
.lang-bar .lb-lang:hover { border-color: var(--text-muted, #94a3b8); }
.lang-bar .lb-lang[aria-selected="true"] { background: var(--text-main, #0f172a); border-color: var(--text-main, #0f172a); color: var(--bg-surface, #fff); }
.lang-bar .lb-lang:focus-visible, .lang-bar .lb-btn:focus-visible, .lang-bar .lb-act:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
.lang-bar .lb-count { font: 600 12px/1 var(--font-mono, monospace); padding: 3px 6px; border-radius: 999px; background: var(--warning-soft); color: var(--warning-text); }
.lang-bar .lb-count.is-full { background: var(--success-soft); color: #166534; }
.lang-bar .lb-count.is-empty { background: var(--danger-soft); color: #991b1b; }
.lang-bar .lb-tools { display: flex; align-items: center; gap: 8px; margin-left: auto; flex-wrap: wrap; }
.lang-bar .lb-src-toggle { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-sub, #475569); cursor: pointer; user-select: none; }
.lang-bar .lb-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 11px; border: 1px solid var(--border-subtle, #e5e7eb); border-radius: 6px; background: var(--bg-surface, #fff); color: var(--text-main, #0f172a); font: 600 13px/1.2 inherit; cursor: pointer; text-decoration: none; }
.lang-bar .lb-btn:hover { border-color: var(--text-muted, #94a3b8); }
.lang-bar .lb-menu { position: relative; }
.lang-bar .lb-menu-list { position: absolute; right: 0; top: calc(100% + 6px); z-index: 50; min-width: 290px; padding: 6px; background: var(--bg-surface, #fff); border: 1px solid var(--border-subtle, #e5e7eb); border-radius: 10px; box-shadow: 0 12px 30px rgba(15, 23, 42, .14); }
.lang-bar .lb-menu-list[hidden] { display: none; }
.lang-bar .lb-act { display: block; width: 100%; padding: 9px 10px; border: 0; border-radius: 6px; background: transparent; color: var(--text-main, #0f172a); font: 500 13px/1.35 inherit; text-align: left; cursor: pointer; }
.lang-bar .lb-act:hover:not(:disabled) { background: var(--bg-hover, #f1f5f9); }
.lang-bar .lb-act:disabled { opacity: .45; cursor: default; }
.lang-bar .lb-status { flex-basis: 100%; font-size: 13px; color: var(--text-sub, #475569); padding: 2px 4px 0; }
.lb-ref { margin: 5px 0 0; padding: 6px 9px; border-left: 2px solid var(--border-subtle, #cbd5e1); font-size: 13px; line-height: 1.45; color: var(--text-muted, #64748b); background: var(--bg-hover, rgba(148, 163, 184, .08)); border-radius: 0 6px 6px 0; white-space: pre-line; overflow-wrap: anywhere; }
.lb-ref b { font-weight: 600; color: var(--text-sub, #475569); margin-right: 4px; }
.lb-ref[hidden] { display: none !important; }
.lb-hidden-ui { display: none !important; }
.lang-bar [hidden] { display: none !important; }
.lb-pane[hidden] { display: none !important; }
.lb-tag { display: inline-block; margin-left: 6px; padding: 1px 6px; border-radius: 999px; font: 600 12px/1.5 var(--font-mono, monospace); color: var(--text-muted, #64748b); background: var(--bg-hover, rgba(148,163,184,.12)); vertical-align: middle; }
[data-theme="dark"] .lang-bar .lb-count { background: rgba(245, 158, 11, .18); color: #fcd34d; }
[data-theme="dark"] .lang-bar .lb-count.is-full { background: rgba(34, 197, 94, .18); color: #86efac; }
[data-theme="dark"] .lang-bar .lb-count.is-empty { background: rgba(239, 68, 68, .18); color: #fca5a5; }
@media (max-width: 760px) { .lang-bar { position: static; } .lang-bar .lb-tools { margin-left: 0; } .lang-bar .lb-label { flex-basis: 100%; } }
</style>
<script>
(function () {
    function init(cfg) {
        var bar = document.getElementById(cfg.id);
        if (!bar || bar.dataset.ready) return;
        bar.dataset.ready = '1';
        var codes = cfg.langs.map(function (l) { return l.code; });
        var byCode = {}; cfg.langs.forEach(function (l) { byCode[l.code] = l; });
        var scope = document.querySelector(cfg.scope) || document;
        var current = codes.indexOf(cfg.initial) > -1 ? cfg.initial : codes[0];
        var T = cfg.t, refs = [];
        var SRC_KEY = 'er_lb_show_source';
        var showSrc = true;
        try { showSrc = localStorage.getItem(SRC_KEY) !== '0'; } catch (e) {}
        bar.querySelector('.lb-src-cb').checked = showSrc;

        if (cfg.hide) document.querySelectorAll(cfg.hide).forEach(function (el) { el.classList.add('lb-hidden-ui'); });

        // ── Alanları bul: { taban: { dil: öğe } } ──
        var codeAlt = codes.map(function (c) { return c.replace(/[^a-z0-9_]/g, ''); }).join('|');
        var reName = new RegExp('^(.*?)(?:_(' + codeAlt + ')|\\[(' + codeAlt + ')\\])(\\])?$');
        var reId = new RegExp('^(.*?)_(' + codeAlt + ')$');
        function baseIgnored(base) {
            if (/(^|\[|_)slug$/.test(base) || /(^|\[)slug(_|$)/.test(base)) return true;
            if (/csrf|_json$|password/.test(base)) return true;
            return cfg.ignore.some(function (p) { return base.indexOf(p) === 0; });
        }
        function collect() {
            var map = {};
            scope.querySelectorAll('input[name], textarea[name], select[name]').forEach(function (el) {
                if (el.type === 'hidden' || el.type === 'checkbox' || el.type === 'radio' || el.type === 'file' || el.tagName === 'SELECT') return;
                if (el.closest('.lang-bar')) return;
                var m = el.name.match(reName); if (!m) return;
                var lang = m[2] || m[3], base = m[1] + (m[4] ? ']' : '');
                if (baseIgnored(base)) return;
                (map[base] = map[base] || {})[lang] = el;
            });
            scope.querySelectorAll('[contenteditable="true"][id]').forEach(function (el) {
                var m = el.id.match(reId); if (!m) return;
                var base = '#' + m[1];
                if (baseIgnored(base)) return;
                (map[base] = map[base] || {})[m[2]] = el;
            });
            return map;
        }
        function val(el) {
            if (!el) return '';
            var v = el.isContentEditable ? el.innerText : el.value;
            return (v || '').replace(/ /g, ' ').trim();
        }
        function rawVal(el) { return el.isContentEditable ? el.innerHTML : el.value; }
        function setVal(el, v) {
            if (el.isContentEditable) el.innerHTML = v; else el.value = v;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
        function plain(s) { var d = document.createElement('div'); d.innerHTML = s; return (d.textContent || '').replace(/\s+\n/g, '\n').trim(); }

        // Kaynak dil: dilin yedek dili (sitede etkinse), değilse dolu olan ilk diğer dil
        function sourceFor(lang) {
            var fb = (byCode[lang] || {}).fallback;
            if (fb && fb !== lang && byCode[fb]) return fb;
            for (var i = 0; i < codes.length; i++) if (codes[i] !== lang) return codes[i];
            return lang;
        }

        // ── Dil düğmeleri ──
        var wrap = bar.querySelector('.lb-langs');
        wrap.innerHTML = cfg.langs.map(function (l) {
            return '<button type="button" role="tab" class="lb-lang" data-lang="' + l.code + '" aria-selected="false">' +
                '<span aria-hidden="true">' + l.flag + '</span><span>' + l.name.replace(/</g, '&lt;') + '</span><span class="lb-count" hidden></span></button>';
        }).join('');

        function updateCounts() {
            var map = collect(), bases = Object.keys(map);
            var relevant = bases.filter(function (b) { return codes.some(function (c) { return val(map[b][c]); }); });
            cfg.langs.forEach(function (l) {
                var btn = wrap.querySelector('[data-lang="' + l.code + '"]'), badge = btn.querySelector('.lb-count');
                var present = relevant.filter(function (b) { return map[b][l.code]; });
                if (!present.length) { badge.hidden = true; btn.title = ''; return; }
                var filled = present.filter(function (b) { return val(map[b][l.code]); }).length;
                badge.hidden = false;
                badge.classList.toggle('is-full', filled === present.length);
                badge.classList.toggle('is-empty', filled === 0);
                badge.textContent = filled === present.length ? '✓' : (filled + '/' + present.length);
                btn.title = filled === present.length ? T.full : T.partial.replace('%d', filled).replace('%d', present.length);
            });
        }

        // ── Kaynak metin kutuları ──
        function anchorFor(el) {
            if (el.isContentEditable) {
                var box = el.closest('.rich-editor-wrap, .rich-editor, .editor-wrap, .narrative-editor');
                return box || el;
            }
            return el;
        }
        function renderRefs() {
            refs.forEach(function (r) { r.remove(); }); refs = [];
            if (!showSrc) return;
            var src = sourceFor(current);
            if (src === current || current === cfg.main) return;
            var map = collect(), sl = byCode[src];
            Object.keys(map).forEach(function (b) {
                var tgt = map[b][current], s = map[b][src];
                if (!tgt || !s) return;
                var text = s.isContentEditable || s.tagName === 'TEXTAREA' ? plain(rawVal(s)) : val(s);
                if (!text) return;
                if (text.length > 420) text = text.slice(0, 420) + '…';
                var ref = document.createElement('div');
                ref.className = 'lb-ref';
                ref.setAttribute('dir', sl.dir || 'ltr');
                var lab = document.createElement('b'); lab.textContent = sl.flag + ' ' + sl.name + ':';
                ref.appendChild(lab); ref.appendChild(document.createTextNode(text));
                var a = anchorFor(tgt);
                a.parentNode.insertBefore(ref, a.nextSibling);
                refs.push(ref);
            });
        }

        // ── Dil değiştir ──
        function applyPanes(lang) {
            if (cfg.click) {
                var target = document.querySelector(cfg.click.replace(/\{lang\}/g, lang));
                if (target) target.click();
            } else if (cfg.panes) {
                document.querySelectorAll(cfg.panes[0]).forEach(function (p) { p.style.display = 'none'; });
                var p = document.querySelector(cfg.panes[1].replace(/\{lang\}/g, lang));
                if (p) p.style.display = '';
            } else {
                scope.querySelectorAll('[data-lb-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-lb-pane') !== lang; });
            }
        }
        function select(lang, silent) {
            current = lang;
            wrap.querySelectorAll('.lb-lang').forEach(function (b) { b.setAttribute('aria-selected', b.getAttribute('data-lang') === lang ? 'true' : 'false'); });
            if (!silent) applyPanes(lang);
            var src = sourceFor(lang), sn = byCode[src] ? byCode[src].name : src, tn = byCode[lang].name;
            var cp = bar.querySelector('[data-act="copy"]'), tr = bar.querySelector('[data-act="translate"]');
            cp.textContent = T.copy.replace('%s', sn); tr.textContent = T.auto.replace('%s', sn).replace('%s', tn);
            cp.disabled = tr.disabled = (src === lang);
            var pv = bar.querySelector('.lb-preview'), url = cfg.preview && cfg.preview[lang];
            pv.hidden = !url; if (url) pv.href = url;
            bar.setAttribute('data-current', lang);
            document.dispatchEvent(new CustomEvent('er:langbar', { detail: { lang: lang, source: src } }));
            setTimeout(function () { renderRefs(); updateCounts(); }, 30);
        }
        wrap.addEventListener('click', function (e) { var b = e.target.closest('.lb-lang'); if (b) select(b.getAttribute('data-lang')); });
        wrap.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            var i = codes.indexOf(current) + (e.key === 'ArrowRight' ? 1 : -1);
            i = (i + codes.length) % codes.length; select(codes[i]);
            wrap.querySelector('[data-lang="' + codes[i] + '"]').focus();
        });

        bar.querySelector('.lb-src-cb').addEventListener('change', function () {
            showSrc = this.checked;
            try { localStorage.setItem(SRC_KEY, showSrc ? '1' : '0'); } catch (e) {}
            renderRefs();
        });

        // ── Çeviri menüsü ──
        var mBtn = bar.querySelector('.lb-menu-btn'), mList = bar.querySelector('.lb-menu-list');
        function closeMenu() { mList.hidden = true; mBtn.setAttribute('aria-expanded', 'false'); }
        mBtn.addEventListener('click', function () { var open = mList.hidden; mList.hidden = !open; mBtn.setAttribute('aria-expanded', open ? 'true' : 'false'); });
        document.addEventListener('click', function (e) { if (!e.target.closest('#' + cfg.id + ' .lb-menu')) closeMenu(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });

        var status = bar.querySelector('.lb-status');
        function say(msg) { status.hidden = !msg; status.textContent = msg || ''; }

        function emptyPairs() {
            var src = sourceFor(current), map = collect(), pairs = [];
            Object.keys(map).forEach(function (b) {
                var s = map[b][src], t = map[b][current];
                if (s && t && val(s) && !val(t)) pairs.push([s, t]);
            });
            return pairs;
        }
        function genericCopy() {
            var pairs = emptyPairs();
            pairs.forEach(function (p) { setVal(p[1], rawVal(p[0])); });
            say(pairs.length ? T.done.replace('%d', pairs.length) : T.nothing);
        }
        function genericTranslate() {
            var src = sourceFor(current), pairs = emptyPairs();
            if (!pairs.length) { say(T.nothing); return; }
            say(T.working);
            fetch(cfg.api, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrf },
                body: JSON.stringify({ csrf_token: cfg.csrf, source_lang: src, target_lang: current, texts: pairs.map(function (p) { return rawVal(p[0]); }) })
            }).then(function (r) { return r.json(); }).then(function (res) {
                var tr = (res && res.translations) || [], n = 0;
                pairs.forEach(function (p, i) { if (tr[i] && String(tr[i]).trim()) { setVal(p[1], tr[i]); n++; } });
                say(n ? T.done.replace('%d', n) : T.failed);
                renderRefs(); updateCounts();
            }).catch(function () { say(T.failed); });
        }
        bar.querySelectorAll('.lb-act').forEach(function (b) {
            b.addEventListener('click', function () {
                closeMenu();
                var act = b.getAttribute('data-act'), fn = act === 'copy' ? cfg.copy_fn : cfg.translate_fn;
                if (fn && typeof window[fn] === 'function') { window[fn](); setTimeout(function () { renderRefs(); updateCounts(); }, 400); return; }
                if (act === 'copy') genericCopy(); else genericTranslate();
                renderRefs(); updateCounts();
            });
        });

        // Yazdıkça sayılar güncellensin (kaynak alan değişince kutular da)
        var t;
        scope.addEventListener('input', function (e) {
            if (e.target.closest && e.target.closest('.lang-bar')) return;
            clearTimeout(t); t = setTimeout(function () { updateCounts(); if (showSrc) renderRefs(); }, 400);
        });

        // Sayfanın kendi sekmesine tıklanırsa çubuk da o dile geçsin
        document.addEventListener('er:pagelang', function (e) { if (e.detail && byCode[e.detail]) select(e.detail, true); });

        select(current, false);
        bar.erSelect = select;
    }
    function boot() { (window.ER_LANG_BARS || []).forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
</script>
    <?php
}
/**
 * Her dil için bir panel basar: <div data-lb-pane="xx">…</div> — çubuk bunları gösterip gizler.
 * $fn($code, $lang_info) panelin içeriğini basar.
 */
function lb_panes(callable $fn): void {
    $init = lang_bar_initial();
    foreach (lang_bar_languages() as $l) {
        echo '<div class="lb-pane" data-lb-pane="' . e($l['code']) . '" dir="' . e($l['dir']) . '"' . ($l['code'] === $init ? '' : ' hidden') . '>';
        $fn($l['code'], $l);
        echo '</div>';
    }
}

/** Alan etiketinin yanına küçük dil işareti: "Başlık · 🇦🇲 HY" */
function lb_tag(array $l): string {
    return '<span class="lb-tag">' . e($l['flag']) . ' ' . e(strtoupper($l['code'])) . '</span>';
}

/**
 * Bir kaydın dillere göre doluluğu.
 * $fields: ['title', 'description', …] — kayıtta {alan}_{dil} sütunları aranır.
 * Bir alan hiçbir dilde dolu değilse sayılmaz (ör. açıklaması olmayan bir proje).
 * Dönüş: ['tr' => [dolu, toplam], 'hy' => [1, 3], …]
 */
function lang_status(array $row, array $fields): array {
    $langs = array_column(lang_bar_languages(), 'code');
    $filled = static function ($v): bool {
        $v = trim((string)$v);
        return $v !== '' && $v !== '[]' && $v !== '{}' && $v !== 'null';
    };
    $relevant = array_values(array_filter($fields, static function ($f) use ($row, $langs, $filled) {
        foreach ($langs as $c) { if ($filled($row[$f . '_' . $c] ?? '')) return true; }
        return false;
    }));
    $out = [];
    foreach ($langs as $c) {
        $n = 0;
        foreach ($relevant as $f) { if ($filled($row[$f . '_' . $c] ?? '')) $n++; }
        $out[$c] = [$n, count($relevant)];
    }
    return $out;
}

/** Eksik dillerin kodları: ['hy', …] */
function lang_missing(array $row, array $fields): array {
    $miss = [];
    foreach (lang_status($row, $fields) as $c => [$n, $t]) { if ($t > 0 && $n < $t) $miss[] = $c; }
    return $miss;
}

/** Satırda gösterilecek küçük işaretler: eksik diller ("HY 1/3"); her şey tamamsa hiçbir şey */
function lang_status_chips(array $row, array $fields): string {
    if (count(lang_bar_languages()) < 2) return '';
    $html = '';
    foreach (lang_status($row, $fields) as $c => [$n, $t]) {
        if ($t === 0 || $n === $t) continue;
        $title = $n === 0
            ? sprintf(__a('%s çevirisi yok', 'No %s translation'), strtoupper($c))
            : sprintf(__a('%s: %d / %d alan dolu', '%s: %d of %d fields filled'), strtoupper($c), $n, $t);
        $html .= '<span class="lb-chip' . ($n === 0 ? ' is-empty' : '') . '" title="' . e($title) . '">' . e(strtoupper($c)) . ' ' . ($n === 0 ? '—' : $n . '/' . $t) . '</span>';
    }
    return $html === '' ? '' : '<span class="lb-chips">' . $html . '</span>';
}

/**
 * "Çevirisi eksik olanlar" süzgeci. Satırlar data-missing="hy en" taşımalı.
 * $rows_selector: süzülecek satırlar (ör. '#portfolioTable tbody tr[data-id]')
 */
function lang_missing_filter(string $rows_selector): void {
    $langs = lang_bar_languages();
    if (count($langs) < 2) return;
    $id = 'lbf_' . substr(md5($rows_selector), 0, 6);
    ?>
    <label class="lb-filter" for="<?= e($id) ?>">
        <span><?= e(__a('Çeviri', 'Translation')) ?></span>
        <select id="<?= e($id) ?>" data-rows="<?= e($rows_selector) ?>">
            <option value=""><?= e(__a('Hepsi', 'All')) ?></option>
            <?php foreach ($langs as $l): ?>
            <option value="<?= e($l['code']) ?>"><?= e(sprintf(__a('%s eksik olanlar', 'Missing %s'), $l['flag'] . ' ' . $l['name'])) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <script>
    (function () {
        var sel = document.getElementById(<?= json_encode($id) ?>);
        sel.addEventListener('change', function () {
            var v = sel.value;
            document.querySelectorAll(sel.getAttribute('data-rows')).forEach(function (r) {
                var miss = (' ' + (r.getAttribute('data-missing') || '') + ' ');
                r.classList.toggle('lb-filtered-out', !!v && miss.indexOf(' ' + v + ' ') === -1);
            });
        });
    })();
    </script>
    <?php
    static $css = false;
    if ($css) return;
    $css = true;
    ?>
    <style>
    .lb-filter { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-sub, #475569); }
    .lb-filter select { padding: 5px 8px; border: 1px solid var(--border-subtle, #e5e7eb); border-radius: 6px; background: var(--bg-surface, #fff); color: var(--text-main, #0f172a); font: inherit; font-size: 12px; }
    .lb-filtered-out { display: none !important; }
    .lb-chips { display: inline-flex; gap: 4px; flex-wrap: wrap; vertical-align: middle; }
    .lb-chip { display: inline-block; padding: 1px 6px; border-radius: 999px; font: 600 12px/1.6 var(--font-mono, monospace); background: var(--warning-soft); color: var(--warning-text); }
    .lb-chip.is-empty { background: var(--danger-soft); color: #991b1b; }
    [data-theme="dark"] .lb-chip { background: rgba(245,158,11,.18); color: #fcd34d; }
    [data-theme="dark"] .lb-chip.is-empty { background: rgba(239,68,68,.18); color: #fca5a5; }
    </style>
    <?php
}
