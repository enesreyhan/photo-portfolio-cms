<?php
/**
 * includes/views/partials/extra_templates.php
 * Ana sayfa ve portfolyo için ek şablonlar (ortak). home.php ve monograph_cards.php çağırır.
 *
 *  sidebar_list    : Kenar Listesi Sunumu  — solda proje listesi, sağda tek büyük fotoğraf
 *  year_archive    : Yıllara Göre Arşiv    — projeler yıl başlıkları altında
 *  contact_sheet   : Kontak Baskı          — film kontak baskısı gibi numaralı kareler
 *  photo_statement : Fotoğraf + Bildiri    — bir yanda tek fotoğraf, öbür yanda büyük metin
 *  thumb_grid      : Küçük Resim Izgarası  — bütün çalışmalar küçük kareler halinde (sütun ve kırpma ayarlı)
 *  book_spreads    : Fotokitap Açılımları  — çalışmalar kitap sayfası gibi ikişerli açılımlarda
 *  mosaic_wall     : Mozaik Duvar          — farklı boyutlarda kapaklardan boşluksuz duvar
 *  zigzag_stream   : Zikzak Akış           — fotoğraflar alt alta, bir sola bir sağa
 *  split_screens   : Bölünmüş Ekran        — her çalışma bir ekran: fotoğraf + büyük başlık (bleed / framed)
 *  light_table     : Işık Masası          — büyük fotoğraf, sağda dikey küçük resim şeridi
 *  triptych        : Üç Panel              — ekran üç dikey panele bölünür, ortada başlık
 *  section_banners : Bölüm Afişleri        — ekran genişliğinde afişler (ana sayfada liste sayfaları)
 *  staggered_gallery: Kademeli Galeri     — kırpılmamış fotoğraflar farklı genişlik ve kaydırmalarla, bol boşluklu
 *  split_grid      : Bir Büyük İki Küçük   — üçlü bloklar; büyük fotoğraf bir solda bir sağda
 *  featured_grid   : Öne Çıkan + Izgara    — ilk çalışma büyük, ardından küçük kareler
 *  photo_wall      : Fotoğraf Duvarı       — kırpılmadan, doğal oranlarıyla sütunlarda (isteğe bağlı üstte tanıtım yazısı)
 *
 * Her slayt dizisi: title, desc, loc, year, target_url, full_img, avif_srcset, webp_srcset, fallback, thumb
 */

if (!function_exists('extra_template_keys')) {

function extra_template_keys(): array {
    return ['sidebar_list', 'year_archive', 'contact_sheet', 'photo_statement', 'project_map', 'sectioned_showcase', 'free_collage', 'thumb_grid', 'book_spreads', 'mosaic_wall', 'photo_wall', 'staggered_gallery', 'split_grid', 'featured_grid', 'zigzag_stream', 'split_screens', 'triptych', 'section_banners', 'light_table'];
}

/** <picture> çıktısı */
function xt_picture(array $s, string $class, string $sizes, bool $eager = false): void {
    if (empty($s['full_img'])) return; ?>
    <picture>
        <?php if (!empty($s['avif_srcset'])): ?><source type="image/avif" srcset="<?= e($s['avif_srcset']) ?>" sizes="<?= e($sizes) ?>"><?php endif; ?>
        <?php if (!empty($s['webp_srcset'])): ?><source type="image/webp" srcset="<?= e($s['webp_srcset']) ?>" sizes="<?= e($sizes) ?>"><?php endif; ?>
        <img class="<?= e($class) ?>" src="<?= e($s['fallback']) ?>" alt="<?= e($s['title']) ?>" draggable="false" decoding="async" <?= $eager ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
    </picture>
<?php }

/** "2017–2024" gibi değerlerden sıralama için son yılı çıkarır */
function xt_sort_year(string $year): int {
    if (preg_match_all('/(?:19|20)\d{2}/', $year, $m) && !empty($m[0])) {
        return (int)max($m[0]);
    }
    return 0;
}

/**
 * @param array $o container_class, container_style, show_title, show_desc, show_locyear, lang, kicker, statement
 */
function render_extra_template(string $tpl, array $slides, array $o): void {
    $lang      = $o['lang'] ?? 'tr';
    $show_t    = $o['show_title'] ?? true;
    $show_d    = $o['show_desc'] ?? true;
    $show_ly   = $o['show_locyear'] ?? true;
    $kicker    = $o['kicker'] ?? '';
    $cls       = trim(($o['container_class'] ?? '') . ' xt-root xt-' . $tpl);
    $style     = $o['container_style'] ?? '';
    $locyear   = function (array $s): string {
        return trim($s['loc'] . (($s['loc'] !== '' && $s['year'] !== '') ? ' · ' : '') . $s['year']);
    };
    ?>
<div class="<?= e($cls) ?>" id="hp-container" style="<?= e($style) ?>">
<?php
    // ─────────────────────────────────────────────────────────────
    if ($tpl === 'sidebar_list'): ?>
    <div class="xt-sb">
        <nav class="xt-sb-list" aria-label="<?= e($kicker) ?>">
            <?php if ($kicker !== ''): ?><div class="xt-kicker"><?= e($kicker) ?></div><?php endif; ?>
            <ol>
                <?php foreach ($slides as $i => $s): ?>
                <li>
                    <button type="button" class="xt-sb-item<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>">
                        <span class="xt-num"><?= sprintf('%02d', $i + 1) ?></span>
                        <span class="xt-sb-name"><?= e($s['title']) ?></span>
                    </button>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <div class="xt-sb-stage">
            <?php foreach ($slides as $i => $s): ?>
            <figure class="xt-sb-figure<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>">
                <?php if ($s['target_url']): ?><a href="<?= e($s['target_url']) ?>" class="xt-sb-link" aria-label="<?= e($s['title']) ?>"><?php endif; ?>
                    <?php xt_picture($s, 'xt-sb-img', '(max-width: 900px) 100vw, 70vw', $i === 0); ?>
                <?php if ($s['target_url']): ?></a><?php endif; ?>
                <figcaption class="xt-sb-cap">
                    <?php if ($show_t && $s['title'] !== ''): ?><strong><?= e($s['title']) ?></strong><?php endif; ?>
                    <?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-meta"><?= e($locyear($s)) ?></span><?php endif; ?>
                    <?php if ($show_d && $s['desc'] !== ''): ?><span class="xt-desc"><?= e($s['desc']) ?></span><?php endif; ?>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    (function () {
        var root = document.currentScript.parentNode;
        var items = root.querySelectorAll('.xt-sb-item'), figs = root.querySelectorAll('.xt-sb-figure');
        var cur = 0;
        function show(i) {
            if (!figs.length) return;
            cur = (i + figs.length) % figs.length;
            items.forEach(function (b, k) { b.classList.toggle('is-active', k === cur); });
            figs.forEach(function (f, k) { f.classList.toggle('is-active', k === cur); });
        }
        items.forEach(function (b) {
            b.addEventListener('mouseenter', function () { show(+b.dataset.idx); });
            b.addEventListener('focus', function () { show(+b.dataset.idx); });
            b.addEventListener('click', function () {
                var f = figs[+b.dataset.idx], a = f && f.querySelector('a');
                if (cur === +b.dataset.idx && a) { location.href = a.href; } else { show(+b.dataset.idx); }
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.target.closest && e.target.closest('input, textarea')) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowRight') { show(cur + 1); }
            if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') { show(cur - 1); }
        });
    })();
    </script>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'year_archive'):
        $groups = [];
        foreach ($slides as $s) {
            $y = xt_sort_year((string)$s['year']);
            $groups[$y][] = $s;
        }
        krsort($groups); ?>
    <div class="xt-arc">
        <?php if ($kicker !== ''): ?><div class="xt-kicker"><?= e($kicker) ?></div><?php endif; ?>
        <?php foreach ($groups as $y => $rows): ?>
        <section class="xt-arc-year">
            <h2 class="xt-arc-y"><?= $y > 0 ? e((string)$y) : e(site_t('Diğer', 'Other', $lang)) ?></h2>
            <div class="xt-arc-rows">
                <?php foreach ($rows as $s):
                    $tag = $s['target_url'] ? 'a' : 'div'; ?>
                <<?= $tag ?> class="xt-arc-row"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
                    <span class="xt-arc-thumb"><?php if (!empty($s['thumb'])): ?><img src="<?= e($s['thumb']) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?></span>
                    <span class="xt-arc-main">
                        <span class="xt-arc-title"><?= e($s['title']) ?></span>
                        <?php if ($show_d && $s['desc'] !== ''): ?><span class="xt-desc"><?= e($s['desc']) ?></span><?php endif; ?>
                    </span>
                    <?php if ($show_ly && $s['loc'] !== ''): ?><span class="xt-meta"><?= e($s['loc']) ?></span><?php endif; ?>
                    <?php if ($s['target_url']): ?><span class="xt-arrow" aria-hidden="true">→</span><?php endif; ?>
                </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'contact_sheet'): ?>
    <div class="xt-cs">
        <?php if ($kicker !== ''): ?><div class="xt-kicker"><?= e($kicker) ?></div><?php endif; ?>
        <div class="xt-cs-sheet">
            <?php foreach ($slides as $i => $s):
                $tag = $s['target_url'] ? 'a' : 'div'; ?>
            <<?= $tag ?> class="xt-cs-frame"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
                <span class="xt-cs-img"><?php if (!empty($s['thumb'])): ?><img src="<?= e($s['thumb']) ?>" alt="<?= e($s['title']) ?>" loading="<?= $i < 8 ? 'eager' : 'lazy' ?>" decoding="async"><?php endif; ?></span>
                <span class="xt-cs-label">
                    <span class="xt-num"><?= sprintf('%02dA', $i + 1) ?></span>
                    <?php if ($show_t && $s['title'] !== ''): ?><span class="xt-cs-name"><?= e($s['title']) ?></span><?php endif; ?>
                </span>
            </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'thumb_grid'):
        // Sütun sayısı ve kırpma: Şablonlar › Sol Menü + Küçük Resim Izgarası seçenekleri
        $cols = max(2, min(6, (int)get_setting('tpl_grid_columns', '4')));
        $crop = get_setting('tpl_thumb_crop', 'square') === 'original' ? 'original' : 'square'; ?>
    <div class="xt-tg xt-tg-<?= e($crop) ?>" style="--xt-cols: <?= $cols ?>;">
        <?php foreach ($slides as $i => $s):
            $tag = $s['target_url'] ? 'a' : 'div';
            $src = $s['thumb'] ?: $s['fallback']; // 800 px: küçük kareler için yeterli ?>
        <<?= $tag ?> class="xt-tg-item"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
            <span class="xt-tg-img"><?php if (!empty($src)): ?><img src="<?= e($src) ?>" alt="<?= e($s['title']) ?>" loading="<?= $i < 12 ? 'eager' : 'lazy' ?>" decoding="async"><?php endif; ?></span>
            <?php if ($show_t && $s['title'] !== ''): ?>
            <span class="xt-tg-cap">
                <span class="xt-tg-title"><?= e($s['title']) ?></span>
                <?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-tg-meta"><?= e($locyear($s)) ?></span><?php endif; ?>
            </span>
            <?php endif; ?>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'zigzag_stream'):
        // Zikzak akış: fotoğraflar alt alta, kırpılmadan; bir sola bir sağa kaydırılmış. ?>
    <div class="xt-zz">
        <?php foreach ($slides as $i => $s):
            $tag = $s['target_url'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="xt-zz-item xt-zz-<?= $i % 2 ? 'r' : 'l' ?>"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
            <?php xt_picture($s, 'xt-zz-img', '(max-width: 767px) 90vw, 58vw', $i < 2); ?>
            <?php if ($show_t && $s['title'] !== ''): ?>
            <span class="xt-zz-cap"><span class="xt-zz-title"><?= e($s['title']) ?></span><?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-zz-meta"><?= e($locyear($s)) ?></span><?php endif; ?></span>
            <?php endif; ?>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'split_screens'):
        // Bölünmüş ekran: her çalışma bir ekran; bir yanda fotoğraf, öbür yanda büyük başlık.
        // tpl_split_style: bleed (fotoğraf yarım ekranı doldurur) | framed (renkli zeminde çerçeveli fotoğraf, dergi gibi)
        $sstyle = get_setting('tpl_split_style', 'bleed') === 'framed' ? 'framed' : 'bleed';
        $more   = $lang === 'tr' ? 'Görüntüle' : 'View'; ?>
    <div class="xt-ss xt-ss-<?= $sstyle ?>">
        <?php foreach ($slides as $i => $s):
            $tag = $s['target_url'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="xt-ss-item"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
            <span class="xt-ss-media"><?php xt_picture($s, 'xt-ss-img', '(max-width: 767px) 100vw, 50vw', $i < 2); ?></span>
            <span class="xt-ss-text">
                <?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-ss-meta"><?= e($locyear($s)) ?></span><?php endif; ?>
                <span class="xt-ss-title"><?= e($s['title']) ?></span>
                <?php if ($show_d && $s['desc'] !== ''): ?><span class="xt-ss-desc"><?= e($s['desc']) ?></span><?php endif; ?>
                <?php if ($s['target_url']): ?><span class="xt-ss-more"><?= e($more) ?> →</span><?php endif; ?>
            </span>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'light_table'): ?>
    <div class="xt-lt">
        <div class="xt-lt-stage">
            <?php foreach ($slides as $i => $s): ?>
            <figure class="xt-lt-figure<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>"<?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
                <?php if ($s['target_url']): ?><a href="<?= e($s['target_url']) ?>" class="xt-lt-link" aria-label="<?= e($s['title']) ?>"<?= $i === 0 ? '' : ' tabindex="-1"' ?>><?php endif; ?>
                    <?php xt_picture($s, 'xt-lt-img', '(max-width: 767px) 100vw, 78vw', $i === 0); ?>
                <?php if ($s['target_url']): ?></a><?php endif; ?>
                <?php if (($show_t && $s['title'] !== '') || ($show_ly && $locyear($s) !== '')): ?>
                <figcaption class="xt-lt-cap">
                    <?php if ($show_t && $s['title'] !== ''): ?><span class="xt-lt-title"><?= e($s['title']) ?></span><?php endif; ?>
                    <?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-lt-meta"><?= e($locyear($s)) ?></span><?php endif; ?>
                </figcaption>
                <?php endif; ?>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php if (count($slides) > 1): ?>
        <div class="xt-lt-strip" role="tablist" aria-label="<?= e($lang === 'en' ? 'Photos' : 'Fotoğraflar') ?>">
            <?php foreach ($slides as $i => $s): $src = $s['thumb'] ?: $s['fallback']; ?>
            <button type="button" class="xt-lt-thumb<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="<?= e($s['title'] !== '' ? $s['title'] : (string)($i + 1)) ?>">
                <?php if ($src): ?><img src="<?= e($src) ?>" alt="" loading="<?= $i < 8 ? 'eager' : 'lazy' ?>" decoding="async" draggable="false"><?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <script>
    (function () {
        var root = document.currentScript.parentNode;
        var thumbs = root.querySelectorAll('.xt-lt-thumb'), figs = root.querySelectorAll('.xt-lt-figure');
        var cur = 0;
        function show(i, scroll) {
            if (!figs.length) return;
            cur = (i + figs.length) % figs.length;
            figs.forEach(function (f, k) {
                var on = k === cur, a = f.querySelector('a');
                f.classList.toggle('is-active', on);
                if (on) { f.removeAttribute('aria-hidden'); var im = f.querySelector('img'); if (im) im.loading = 'eager'; } else { f.setAttribute('aria-hidden', 'true'); }
                if (a) { if (on) a.removeAttribute('tabindex'); else a.setAttribute('tabindex', '-1'); }
            });
            thumbs.forEach(function (b, k) { b.classList.toggle('is-active', k === cur); b.setAttribute('aria-selected', k === cur ? 'true' : 'false'); });
            if (scroll && thumbs[cur]) thumbs[cur].scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        }
        // Sonraki fotoğrafı önceden yükle
        if (figs[1]) { var n = figs[1].querySelector('img'); if (n) n.loading = 'eager'; }
        thumbs.forEach(function (b) {
            b.addEventListener('click', function () { show(+b.dataset.idx, false); });
        });
        // Telefonda sağa/sola kaydırma
        var stage = root.querySelector('.xt-lt-stage'), sx = null, sy = 0;
        if (stage) {
            stage.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
            stage.addEventListener('touchend', function (e) {
                if (sx === null) return;
                var dx = e.changedTouches[0].clientX - sx, dy = e.changedTouches[0].clientY - sy; sx = null;
                if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) show(cur + (dx < 0 ? 1 : -1), true);
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.target.closest && e.target.closest('input, textarea, [contenteditable]')) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowRight') { e.preventDefault(); show(cur + 1, true); }
            if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') { e.preventDefault(); show(cur - 1, true); }
        });
    })();
    </script>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'triptych'):
        // Üç panel: ekran üç dikey panele bölünür; her panelde bir çalışma ve ortasında başlığı. ?>
    <div class="xt-tp">
        <?php foreach ($slides as $i => $s):
            $tag = $s['target_url'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="xt-tp-item"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
            <?php xt_picture($s, 'xt-tp-img', '(max-width: 767px) 100vw, 34vw', $i < 3); ?>
            <span class="xt-tp-cap"><span class="xt-tp-title"><?= e($s['title']) ?></span><?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-tp-meta"><?= e($locyear($s)) ?></span><?php endif; ?></span>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'section_banners'):
        // Bölüm afişleri: ekran genişliğinde afişler, ortada büyük başlık ve ok.
        // Ana sayfada (tpl_banner_source = categories) liste sayfalarını (Projeler, Seriler…) gösterir; listelerde çalışmaları.
        $bands = [];
        if (($o['context'] ?? '') === 'home' && get_setting('tpl_banner_source', 'categories') === 'categories') {
            try {
                $pdo  = DB::getConnection();
                $cats = $pdo->query("SELECT * FROM `categories` WHERE `show_in_menu` = 1 ORDER BY `weight` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
                $cov  = $pdo->prepare("SELECT `cover_image` FROM `items` WHERE `type` = ? AND `status` = 'published' AND `cover_image` IS NOT NULL AND `cover_image` <> '' ORDER BY `weight` ASC, `date` DESC, `id` DESC LIMIT 1");
                foreach ($cats as $c) {
                    $cov->execute([$c['key_slug']]);
                    $img = (string)$cov->fetchColumn();
                    if ($img === '') continue;
                    $full = resolve_image_file_path(ltrim($img, '/'));
                    $md   = $full ? resize_and_convert_image($full, 1600, 'webp', 82) : null;
                    $lg   = $full ? resize_and_convert_image($full, 2560, 'webp', 80) : null;
                    $slug = ($c['slug_' . $lang] ?? '') ?: $c['slug_tr'];
                    $bands[] = [
                        'title' => get_category_localized_name($c, $lang), 'loc' => '', 'year' => '', 'desc' => '',
                        'target_url' => url($lang . '/' . $slug . '/'), 'full_img' => $full,
                        'avif_srcset' => '', 'webp_srcset' => ($md ? url($md) . ' 1600w' : '') . ($lg ? ', ' . url($lg) . ' 2560w' : ''),
                        'fallback' => $md ? url($md) : url(ltrim($img, '/')),
                    ];
                }
            } catch (\Throwable $e) { $bands = []; }
        }
        if (!$bands) $bands = $slides; ?>
    <div class="xt-sb2">
        <?php foreach ($bands as $i => $s):
            $tag = $s['target_url'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="xt-sb2-item"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
            <?php xt_picture($s, 'xt-sb2-img', '100vw', $i < 2); ?>
            <span class="xt-sb2-cap">
                <span class="xt-sb2-title"><?= e($s['title']) ?></span>
                <?php if ($s['target_url']): ?><span class="xt-sb2-arrow" aria-hidden="true">→</span><?php endif; ?>
            </span>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'staggered_gallery'):
        // Kademeli galeri: kırpılmamış fotoğraflar 12 sütunlu ızgarada farklı genişlik ve kaydırmalarla;
        // 6'lık bir ritim tekrar eder (bol boşluk, sakin). Telefonda tek sütun, hafif sağa-sola kaydırma.
        $rhythm = [
            ['c' => '1 / span 6',  'm' => '0'],
            ['c' => '8 / span 4',  'm' => 'clamp(40px, 14vh, 160px)'],
            ['c' => '3 / span 4',  'm' => '0'],
            ['c' => '7 / span 6',  'm' => 'clamp(24px, 8vh, 90px)'],
            ['c' => '2 / span 5',  'm' => '0'],
            ['c' => '8 / span 4',  'm' => 'clamp(30px, 10vh, 120px)'],
        ]; ?>
    <div class="xt-sg">
        <?php foreach ($slides as $i => $s):
            $r = $rhythm[$i % count($rhythm)];
            $tag = $s['target_url'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="xt-sg-item xt-sg-<?= $i % 2 ? 'r' : 'l' ?>" style="grid-column: <?= $r['c'] ?>; margin-top: <?= $r['m'] ?>;"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
            <?php xt_picture($s, 'xt-sg-img', '(max-width: 767px) 92vw, 50vw', $i < 3); ?>
            <?php if ($show_t && $s['title'] !== ''): ?>
            <span class="xt-sg-cap"><span class="xt-sg-title"><?= e($s['title']) ?></span><?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-sg-meta"><?= e($locyear($s)) ?></span><?php endif; ?></span>
            <?php endif; ?>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'split_grid'):
        // Bir büyük iki küçük: üçlü bloklar; büyük fotoğraf bir blokta solda, sonrakinde sağda. Fotoğraflar kutuyu doldurur.
        $blocks = array_chunk(array_keys($slides), 3); ?>
    <div class="xt-sp">
        <?php foreach ($blocks as $bi => $idxs): ?>
        <div class="xt-sp-block xt-sp-<?= $bi % 2 ? 'rev' : 'fwd' ?> xt-sp-n<?= count($idxs) ?>">
            <?php foreach ($idxs as $k => $i):
                $s = $slides[$i];
                $tag = $s['target_url'] ? 'a' : 'div'; ?>
            <<?= $tag ?> class="xt-sp-item xt-sp-<?= $k === 0 ? 'big' : 'small' ?>"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
                <?php xt_picture($s, 'xt-sp-img', $k === 0 ? '(max-width: 767px) 100vw, 64vw' : '(max-width: 767px) 50vw, 32vw', $bi === 0); ?>
                <?php if ($show_t && $s['title'] !== ''): ?>
                <span class="xt-sp-cap"><span class="xt-sp-title"><?= e($s['title']) ?></span><?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-sp-meta"><?= e($locyear($s)) ?></span><?php endif; ?></span>
                <?php endif; ?>
            </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'featured_grid'):
        // Öne çıkan + ızgara: ilk çalışma büyük ve kırpılmadan, ardından küçük kare resimler.
        $cols  = max(3, min(6, (int)get_setting('tpl_grid_columns', '4')));
        $first = $slides[0] ?? null;
        $rest  = array_slice($slides, 1, null, true); ?>
    <div class="xt-fg">
        <?php if ($first): $tag = $first['target_url'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="xt-fg-hero"<?= $first['target_url'] ? ' href="' . e($first['target_url']) . '"' : '' ?>>
            <?php xt_picture($first, 'xt-fg-hero-img', '(max-width: 767px) 100vw, 75vw', true); ?>
            <?php if ($show_t && $first['title'] !== ''): ?>
            <span class="xt-fg-hero-cap"><span class="xt-fg-title"><?= e($first['title']) ?></span><?php if ($show_ly && $locyear($first) !== ''): ?><span class="xt-fg-meta"><?= e($locyear($first)) ?></span><?php endif; ?></span>
            <?php endif; ?>
        </<?= $tag ?>>
        <?php endif; ?>
        <div class="xt-fg-grid" style="--xt-cols: <?= $cols ?>;">
            <?php foreach ($rest as $i => $s):
                $tag = $s['target_url'] ? 'a' : 'div';
                $src = $s['thumb'] ?: $s['fallback']; ?>
            <<?= $tag ?> class="xt-fg-item"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
                <span class="xt-fg-img"><?php if (!empty($src)): ?><img src="<?= e($src) ?>" alt="<?= e($s['title']) ?>" loading="lazy" decoding="async"><?php endif; ?></span>
                <?php if ($show_t && $s['title'] !== ''): ?><span class="xt-fg-title-sm"><?= e($s['title']) ?></span><?php endif; ?>
            </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'photo_wall'):
        // Fotoğraf duvarı: kırpılmadan, doğal oranlarıyla sütunlarda. Her fotoğraf en kısa sütuna eklenir;
        // `order` asıl sırayı korur (telefonda sütunlar çözülür, sıra bozulmaz). Statement: üstte tanıtım yazısı.
        $cols    = max(2, min(5, (int)get_setting('tpl_wall_columns', '3')));
        $columns = array_fill(0, $cols, []);
        $heights = array_fill(0, $cols, 0.0);
        foreach ($slides as $i => $s) {
            $ratio = 1.25;
            if (!empty($s['full_img']) && is_file($s['full_img'])) {
                $dim = @getimagesize($s['full_img']);
                if ($dim && $dim[0] > 0) $ratio = $dim[1] / $dim[0];
            }
            $c = array_keys($heights, min($heights))[0];
            $columns[$c][] = $i;
            $heights[$c] += $ratio + ($show_t ? 0.12 : 0);
        }
        $statement = get_setting('tpl_wall_statement', '0') === '1' ? trim((string)($o['statement'] ?? '')) : ''; ?>
    <div class="xt-pw-wrap">
        <?php if ($statement !== ''): ?><div class="xt-pw-statement"><?= preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', nl2br(e($statement))) ?></div><?php endif; ?>
        <div class="xt-pw" style="--xt-cols: <?= $cols ?>;">
            <?php foreach ($columns as $col): ?>
            <div class="xt-pw-col">
                <?php foreach ($col as $i):
                    $s = $slides[$i];
                    $tag = $s['target_url'] ? 'a' : 'div'; ?>
                <<?= $tag ?> class="xt-pw-item" style="order: <?= (int)$i ?>;"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
                    <?php xt_picture($s, 'xt-pw-img', '(max-width: 767px) 100vw, ' . (int)round(100 / $cols) . 'vw', $i < 6); ?>
                    <?php if ($show_t && $s['title'] !== ''): ?>
                    <span class="xt-pw-cap">
                        <span class="xt-pw-title"><?= e($s['title']) ?></span>
                        <?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-pw-meta"><?= e($locyear($s)) ?></span><?php endif; ?>
                    </span>
                    <?php endif; ?>
                </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'mosaic_wall'):
        // Her 10 kapakta bir düzen: 1. büyük kare (2×2), 6. geniş (2×1), 8. uzun (1×2); boşluklar "dense" ile dolar
        $cols = max(3, min(6, (int)get_setting('tpl_grid_columns', '4')));
        $span = [0 => 'big', 5 => 'wide', 7 => 'tall']; ?>
    <div class="xt-tg xt-mw" style="--xt-cols: <?= $cols ?>;">
        <?php foreach ($slides as $i => $s):
            $tag = $s['target_url'] ? 'a' : 'div';
            $sz  = $span[$i % 10] ?? 'one'; ?>
        <<?= $tag ?> class="xt-tg-item xt-mw-<?= $sz ?>"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
            <?php xt_picture($s, 'xt-mw-img', $sz === 'big' || $sz === 'wide' ? '(max-width: 767px) 100vw, 50vw' : '(max-width: 767px) 50vw, 25vw', $i < 6); ?>
            <?php if ($show_t && $s['title'] !== ''): ?>
            <span class="xt-tg-cap">
                <span class="xt-tg-title"><?= e($s['title']) ?></span>
                <?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-tg-meta"><?= e($locyear($s)) ?></span><?php endif; ?>
            </span>
            <?php endif; ?>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'book_spreads'):
        // İlk açılımın sol sayfası başlık sayfası (site adı + site açıklaması), sonra çalışmalar ikişer ikişer
        $statement = trim((string)($o['statement'] ?? ''));
        $pages = array_merge([null], $slides);
        if (count($pages) % 2) $pages[] = false; // son açılımın boş sağ sayfası
        $site_name = trim((string)get_setting('header_logo_text', get_setting('site_title', ''))); ?>
    <div class="xt-bk">
        <?php foreach (array_chunk($pages, 2) as $spread): ?>
        <div class="xt-bk-spread">
            <?php foreach ($spread as $pg):
                if ($pg === null): ?>
            <div class="xt-bk-page xt-bk-title">
                <?php if ($kicker !== ''): ?><div class="xt-kicker"><?= e($kicker) ?></div><?php endif; ?>
                <?php if ($site_name !== ''): ?><h2><?= e($site_name) ?></h2><?php endif; ?>
                <?php if ($statement !== ''): ?><p><?= e($statement) ?></p><?php endif; ?>
            </div>
                <?php elseif ($pg === false): ?>
            <div class="xt-bk-page" aria-hidden="true"></div>
                <?php else:
                    $tag = $pg['target_url'] ? 'a' : 'div';
                    $num = (int)array_search($pg, $slides, true) + 1; ?>
            <<?= $tag ?> class="xt-bk-page"<?= $pg['target_url'] ? ' href="' . e($pg['target_url']) . '"' : '' ?>>
                <span class="xt-bk-figure"><?php xt_picture($pg, 'xt-bk-img', '(max-width: 767px) 92vw, 40vw', $num <= 2); ?></span>
                <?php if ($show_t && $pg['title'] !== ''): ?>
                <span class="xt-bk-cap"><span><?= e($pg['title']) ?></span><?php if ($show_ly && $locyear($pg) !== ''): ?><span class="xt-meta"><?= e($pg['year'] !== '' ? $pg['year'] : $pg['loc']) ?></span><?php endif; ?></span>
                <?php endif; ?>
                <span class="xt-bk-folio"><?= sprintf('%02d', $num) ?></span>
            </<?= $tag ?>>
                <?php endif;
            endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'photo_statement'):
        $lead = $slides[0] ?? null;
        $statement = trim((string)($o['statement'] ?? '')); ?>
    <div class="xt-st">
        <div class="xt-st-photo">
            <?php if ($lead): ?>
                <?php if ($lead['target_url']): ?><a href="<?= e($lead['target_url']) ?>" aria-label="<?= e($lead['title']) ?>"><?php endif; ?>
                    <?php xt_picture($lead, 'xt-st-img', '(max-width: 900px) 100vw, 50vw', true); ?>
                <?php if ($lead['target_url']): ?></a><?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="xt-st-text">
            <?php if ($statement !== ''): ?><p class="xt-st-quote"><?= e($statement) ?></p><?php endif; ?>
            <?php if ($slides): ?>
            <ol class="xt-st-list">
                <?php foreach ($slides as $i => $s): ?>
                <li>
                    <?php if ($s['target_url']): ?><a href="<?= e($s['target_url']) ?>"><?php endif; ?>
                        <span class="xt-num"><?= sprintf('%02d', $i + 1) ?></span>
                        <span><?= e($s['title']) ?></span>
                        <?php if ($show_ly && $s['year'] !== ''): ?><span class="xt-meta"><?= e($s['year']) ?></span><?php endif; ?>
                    <?php if ($s['target_url']): ?></a><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </div>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'project_map'):
        require_once __DIR__ . '/../../geo_gazetteer.php';
        $pins = []; $unplaced = [];
        foreach ($slides as $i => $s) {
            $pts = geo_resolve_points((string)($s['loc_raw'] ?? $s['loc']));
            if (!$pts) { $unplaced[] = $s; continue; }
            foreach ($pts as $pt) {
                $pins[] = ['lat' => $pt['lat'], 'lng' => $pt['lng'], 'title' => $s['title'], 'meta' => $locyear($s), 'url' => $s['target_url'], 'img' => $s['thumb'] ?? ''];
            }
        }
        $light = !empty($o['light']); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <div class="xt-map">
        <div class="xt-map-canvas" id="xt-map-canvas" aria-label="<?= e(site_t('Proje haritası', 'Project map', $lang)) ?>"></div>
        <div class="xt-map-list">
            <?php if ($kicker !== ''): ?><div class="xt-kicker"><?= e($kicker) ?></div><?php endif; ?>
            <?php foreach ($slides as $i => $s): $tag = $s['target_url'] ? 'a' : 'div'; ?>
            <<?= $tag ?> class="xt-map-row"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?> data-title="<?= e($s['title']) ?>">
                <span class="xt-num"><?= sprintf('%02d', $i + 1) ?></span>
                <span class="xt-map-name"><?= e($s['title']) ?></span>
                <?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-meta"><?= e($locyear($s)) ?></span><?php endif; ?>
            </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
    (function () {
        var pins = <?= json_encode($pins, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        var el = document.getElementById('xt-map-canvas');
        if (!el || typeof L === 'undefined') return;
        var map = L.map(el, { scrollWheelZoom: false, worldCopyJump: true, zoomControl: false, attributionControl: true });
        L.control.zoom({ position: 'bottomleft' }).addTo(map);
        var tone = <?= json_encode($light ? 'Light_Gray' : 'Dark_Gray') ?>;
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_' + tone + '_Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 16, attribution: 'Esri' }).addTo(map);
        var esc = function (t) { return String(t || '').replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); };
        var bounds = [];
        pins.forEach(function (p) {
            var m = L.circleMarker([p.lat, p.lng], { radius: 7, color: '#fdd000', weight: 2, fillColor: '#fdd000', fillOpacity: 0.85 }).addTo(map);
            var html = (p.img ? '<img src="' + esc(p.img) + '" alt="" style="width:180px;height:110px;object-fit:cover;display:block;margin-bottom:6px;">' : '') +
                '<strong>' + esc(p.title) + '</strong>' + (p.meta ? '<br><span style="opacity:.7;font-size:12px;">' + esc(p.meta) + '</span>' : '') +
                (p.url ? '<br><a href="' + esc(p.url) + '" style="font-weight:600;">' + <?= json_encode(site_t('Projeyi aç →', 'Open project →', $lang)) ?> + '</a>' : '');
            m.bindPopup(html);
            bounds.push([p.lat, p.lng]);
        });
        if (bounds.length > 1) { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 6 }); }
        else if (bounds.length === 1) { map.setView(bounds[0], 5); }
        else { map.setView([25, 20], 2); }
    })();
    </script>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'sectioned_showcase'):
        $hero = $slides[0] ?? null;
        $selected = array_slice($slides, $hero ? 1 : 0, 6);
        $posts = [];
        if (get_setting('blog_enabled', '0') === '1') {
            try {
                $posts = DB::getConnection()->query("SELECT * FROM `items` WHERE `type` = 'blog' AND `status` = 'published' ORDER BY COALESCE(NULLIF(`date`, ''), `created_at`) DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) { $posts = []; }
        }
        $about_url   = function_exists('get_static_slug') ? url($lang . '/' . get_static_slug('about', $lang) . '/') : '';
        $contact_url = (get_setting('contact_page_enabled', '0') === '1' && function_exists('get_static_slug')) ? url($lang . '/' . get_static_slug('contact', $lang) . '/') : '';
        $statement   = trim((string)($o['statement'] ?? '')); ?>
    <div class="xt-sec">
        <?php if ($hero): ?>
        <section class="xt-sec-hero">
            <?php xt_picture($hero, 'xt-sec-hero-img', '100vw', true); ?>
            <div class="xt-sec-hero-text">
                <?php if ($show_ly && $locyear($hero) !== ''): ?><div class="xt-meta"><?= e($locyear($hero)) ?></div><?php endif; ?>
                <?php if ($show_t && $hero['title'] !== ''): ?><h1 class="xt-sec-hero-title"><?= e($hero['title']) ?></h1><?php endif; ?>
                <?php if ($hero['target_url']): ?><a class="xt-sec-btn" href="<?= e($hero['target_url']) ?>"><?= e(site_t('Projeyi incele', 'View project', $lang)) ?> →</a><?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($selected): ?>
        <section class="xt-sec-block">
            <div class="xt-sec-head"><h2><?= e($kicker !== '' ? $kicker : site_t('Seçili İşler', 'Selected Work', $lang)) ?></h2></div>
            <div class="xt-sec-grid">
                <?php foreach ($selected as $s): $tag = $s['target_url'] ? 'a' : 'div'; ?>
                <<?= $tag ?> class="xt-sec-card"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?>>
                    <span class="xt-sec-thumb"><?php if (!empty($s['thumb'])): ?><img src="<?= e($s['thumb']) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?></span>
                    <span class="xt-sec-card-title"><?= e($s['title']) ?></span>
                    <?php if ($show_ly && $locyear($s) !== ''): ?><span class="xt-meta"><?= e($locyear($s)) ?></span><?php endif; ?>
                </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($posts): ?>
        <section class="xt-sec-block">
            <div class="xt-sec-head"><h2><?= e(site_t('Son Yazılar', 'Latest Writing', $lang)) ?></h2></div>
            <div class="xt-sec-posts">
                <?php foreach ($posts as $p):
                    $p_url = function_exists('item_url') ? item_url($p, $lang) : '';
                    $p_title = function_exists('item_field') ? item_field($p, 'title', $lang) : ($p['title_' . $lang] ?? $p['title_en']); ?>
                <a class="xt-sec-post" href="<?= e($p_url) ?>">
                    <span class="xt-meta"><?= e((string)($p['date'] ?: substr((string)$p['created_at'], 0, 10))) ?></span>
                    <span class="xt-sec-post-title"><?= e($p_title) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($statement !== '' || $about_url !== '' || $contact_url !== ''): ?>
        <section class="xt-sec-block xt-sec-about">
            <?php if ($statement !== ''): ?><p class="xt-sec-about-text"><?= e($statement) ?></p><?php endif; ?>
            <div class="xt-sec-links">
                <?php if ($about_url !== ''): ?><a class="xt-sec-btn" href="<?= e($about_url) ?>"><?= e(site_t('Hakkımda', 'About', $lang)) ?> →</a><?php endif; ?>
                <?php if ($contact_url !== ''): ?><a class="xt-sec-btn" href="<?= e($contact_url) ?>"><?= e(site_t('İletişim', 'Contact', $lang)) ?> →</a><?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
<?php
    // ─────────────────────────────────────────────────────────────
    elseif ($tpl === 'free_collage'):
        // Sabit (her yüklemede aynı) serbest yerleşim: genişlik, yatay kayma ve dikey boşluk karede karede değişir
        $widths  = [34, 22, 28, 40, 24, 30, 26, 36];
        $offsets = [4, 52, 26, 58, 10, 44, 66, 18];
        $gaps    = [0, -6, 8, -4, 4, -8, 6, 2]; ?>
    <div class="xt-col">
        <?php if ($kicker !== ''): ?><div class="xt-kicker"><?= e($kicker) ?></div><?php endif; ?>
        <?php foreach ($slides as $i => $s):
            $w = $widths[$i % count($widths)]; $x = min($offsets[$i % count($offsets)], 100 - $w); $g = $gaps[$i % count($gaps)];
            $tag = $s['target_url'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="xt-col-item"<?= $s['target_url'] ? ' href="' . e($s['target_url']) . '"' : '' ?> style="--w:<?= $w ?>%; --x:<?= $x ?>%; --g:<?= $g ?>vh;">
            <?php xt_picture($s, 'xt-col-img', '(max-width: 900px) 100vw, 40vw', $i < 2); ?>
            <span class="xt-col-cap">
                <span class="xt-num"><?= sprintf('%02d', $i + 1) ?></span>
                <?php if ($show_t && $s['title'] !== ''): ?><span><?= e($s['title']) ?></span><?php endif; ?>
            </span>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
<?php
}

}
