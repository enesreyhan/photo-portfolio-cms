<?php
/**
 * admin/includes/extra_templates_admin.php
 * Ek şablonların (Kenar Listesi, Yıllara Göre Arşiv, Kontak Baskı, Fotoğraf + Bildiri) panel parçaları.
 * Ana Sayfa ve Portfolyo Ana Sayfa tasarım sayfaları ortak kullanır.
 */

/** Şablon seçim kartları (radio). $field: hp_template | pf_template */
function extra_templates_radio_cards(string $field, string $current, string $onchange): void {
    $cards = [
        'sidebar_list'    => [__a('13. Kenar Listesi Sunumu', '13. Sidebar List'), __a('Solda numaralı proje listesi, sağda kırpılmadan tek büyük fotoğraf. Listede üzerine gelince fotoğraf değişir. En iyi: az sayıda güçlü proje.', 'Numbered project list on the left, one large uncropped photo on the right; hovering the list switches the photo. Best for a few strong projects.')],
        'year_archive'    => [__a('14. Yıllara Göre Arşiv', '14. Archive by Year'), __a('Projeler yıl başlıkları altında; her satırda küçük kapak, başlık ve yer. En iyi: uzun yıllara yayılan çok projeli arşiv.', 'Projects grouped under year headings with thumbnail, title and place. Best for a long, multi-project archive.')],
        'contact_sheet'   => [__a('15. Kontak Baskı', '15. Contact Sheet'), __a('Tüm işler film kontak baskısı gibi küçük, numaralı karelerle siyah zeminde. Üzerine gelince büyür. En iyi: çok sayıda iş.', 'All works as small numbered frames on black, like a film contact sheet; frames enlarge on hover. Best for many works.')],
        'photo_statement' => [__a('16. Fotoğraf + Bildiri', '16. Photo + Statement'), __a('Bir yanda tek büyük fotoğraf, öbür yanda büyük puntolu kısa metin ve proje listesi. Metin Site Ayarları › Genel’deki site açıklamasından gelir.', 'One large photo on one side, a short statement in large type and the project list on the other. The text comes from the site description in Site Settings › General.')],
        'project_map'        => [__a('17. Proje Haritası', '17. Project Map'), __a('Dünya haritasında her proje çekildiği yerde bir nokta; tıklayınca kapak ve bağlantı açılır. Yanında proje listesi. Yer, projelerin “Konum” alanından otomatik bulunur.', 'Each project as a point on a world map where it was shot; clicking shows the cover and link. Project list alongside. Places are found from each project’s Location field.')],
        'sectioned_showcase' => [__a('18. Bölümlü Vitrin', '18. Sectioned Showcase'), __a('Tam ekran kapak, ardından Seçili İşler kartları, son blog yazıları (blog açıksa), kısa tanıtım metni ve Hakkımda / İletişim bağlantıları. Tek sayfada her şey.', 'Full-screen cover, then Selected Work cards, latest blog posts (if blog is on), a short intro and About / Contact links. Everything on one page.')],
        'thumb_grid'         => [__a('20. Küçük Resim Izgarası', '20. Thumbnail Grid'), __a('Bütün çalışmalar küçük kareler halinde yan yana, alt alta; başlık üzerine gelince çıkar. Sol menüyle birlikte en iyi görünür. Sütun sayısı ve kırpma Şablonlar sayfasından seçilir.', 'All work as small thumbnails side by side; titles on hover. Looks best with the sidebar menu. Columns and cropping are chosen on the Templates page.')],
        'book_spreads'       => [__a('21. Fotokitap Açılımları', '21. Photobook Spreads'), __a('Çalışmalar kitap sayfası gibi ikişerli açılımlarda, krem kâğıt üzerinde; ilk sayfa site adı ve açıklaması. Telefonda sayfa sayfa.', 'Work laid out as two-page book spreads on warm paper; the first page shows the site name and description. Page by page on phones.')],
        'mosaic_wall'        => [__a('22. Mozaik Duvar', '22. Mosaic Wall'), __a('Farklı boyutlarda kapaklar boşluksuz bir duvar oluşturur; başlık üzerine gelince çıkar. Sütun sayısı Şablonlar sayfasından seçilir.', 'Covers of varying sizes form a dense wall; titles on hover. Columns are chosen on the Templates page.')],
        'photo_wall'         => [__a('23. Fotoğraf Duvarı', '23. Photo Wall'), __a('Fotoğraflar kırpılmadan, doğal oranlarıyla sütunlar hâlinde dizilir; başlık fotoğrafın altında. Sütun sayısı Temalar › Özelleştir › Ana sayfa’dan seçilir. En iyi: dikey ve yatay fotoğrafların karışık olduğu seriler.', 'Photos stacked uncropped in columns at their natural proportions, titles below. Set the column count in Themes › Customise › Homepage. Best for: series that mix portrait and landscape photos.')],
        'staggered_gallery'  => [__a('24. Kademeli Galeri', '24. Staggered Gallery'), __a('Fotoğraflar kırpılmadan, farklı genişlik ve kaydırmalarla, bol boşluklu bir ritimde. En iyi: sakin, galeri duvarı hissi isteyen seçkiler.', 'Uncropped photos in varying widths and offsets with generous space. Best for: calm selections that should feel like a gallery wall.')],
        'split_grid'         => [__a('25. Bir Büyük İki Küçük', '25. One Large, Two Small'), __a('Üçlü bloklar: bir büyük, iki küçük fotoğraf; büyük olan bir blokta solda, sonrakinde sağda. En iyi: güçlü kapak fotoğrafları olan projeler.', 'Blocks of three: one large and two small photos, the large one alternating sides. Best for: projects with strong cover photos.')],
        'featured_grid'      => [__a('26. Öne Çıkan + Izgara', '26. Featured + Grid'), __a('İlk çalışma büyük ve kırpılmadan, ardından küçük kare resimler. En iyi: öne çıkarmak istediğiniz son bir iş varken.', 'The first work large and uncropped, then small square thumbnails. Best for: when you want to lead with one recent work.')],
        'zigzag_stream'      => [__a('27. Zikzak Akış', '27. Zigzag Stream'), __a('Fotoğraflar kırpılmadan, bir sola bir sağa yaslanarak aşağı doğru akar; başlık fotoğrafın altında. En iyi: tek tek bakılması gereken güçlü kareler.', 'Uncropped photos flow down the page, alternating left and right, titles below. Best for: strong single frames worth a long look.')],
        'split_screens'      => [__a('28. Bölünmüş Ekran', '28. Split Screens'), __a('Her proje bir ekran: bir yarıda fotoğraf, öbür yarıda başlık ve kısa açıklama; taraflar sırayla değişir. Kenara taşan ya da çerçeveli (dergi) görünüm seçilebilir.', 'Each project fills a screen: photo on one half, title and short text on the other, sides alternating. Choose edge-to-edge or framed (magazine) style.')],
        'triptych'           => [__a('29. Üç Panel', '29. Three Panels'), __a('Ekran üç dikey panele bölünür; her panel bir proje kapağı ve ortasında adı. Telefonda alt alta. En iyi: üç-altı ana projesi olan portfolyolar.', 'The screen splits into three tall panels, each a project cover with its name centred. Stacked on phones. Best for: portfolios with three to six main projects.')],
        'section_banners'    => [__a('30. Bölüm Afişleri', '30. Section Banners'), __a('Menüdeki her bölüm geniş bir afiş: kapak fotoğrafı, büyük başlık ve ok. Ana sayfada bölümleri, proje listelerinde projeleri gösterir.', 'Each menu section becomes a wide banner with a cover photo, large title and an arrow. Shows sections on the homepage and projects on project lists.')],
        'light_table'        => [__a('31. Işık Masası', '31. Light Table'), __a('Solda tek büyük fotoğraf, sağda dikey küçük resim şeridi; küçük resme tıklayınca büyük fotoğraf değişir, ok tuşlarıyla da gezilir. Telefonda şerit altta. En iyi: koyu zeminde fotoğrafları tek tek göstermek.', 'One large photo with a vertical thumbnail strip on the right; click a thumbnail (or use the arrow keys) to change the photo. On phones the strip moves below. Best for: showing photos one at a time on a dark background.')],
        'free_collage'       => [__a('19. Serbest Kolaj', '19. Free Collage'), __a('Fotoğraflar farklı boyut ve konumlarda, ızgarasız serbest yerleşir; başlık üzerine gelince çıkar. Telefonda tek sütun. Sanatsal ve kişisel işler için.', 'Photos placed freely in varied sizes and positions without a grid; titles appear on hover. Single column on phones. For artistic and personal work.')],
    ];
    foreach ($cards as $val => [$title, $desc]): ?>
                    <label class="option-radio-card">
                        <div class="option-radio-head">
                            <input type="radio" name="<?= e($field) ?>" value="<?= e($val) ?>" <?= $current === $val ? 'checked' : '' ?> onchange="<?= e($onchange) ?>">
                            <span><?= e($title) ?></span>
                            <span class="badge badge-info" style="font-size:9px; padding:1px 4px;"><?= e(__a('Yeni', 'New')) ?></span>
                        </div>
                        <div class="option-radio-sub"><?= e($desc) ?></div>
                    </label>
<?php endforeach;
}

/** Canlı önizleme çizimleri: extraTplPreview(template, ctx) → HTML ya da null */
function extra_templates_preview_js(): void { ?>
<script>
window.EXTRA_TEMPLATE_NAMES = {
    sidebar_list: <?= json_encode(__a('13. Kenar Listesi Sunumu', '13. Sidebar List')) ?>,
    year_archive: <?= json_encode(__a('14. Yıllara Göre Arşiv', '14. Archive by Year')) ?>,
    contact_sheet: <?= json_encode(__a('15. Kontak Baskı', '15. Contact Sheet')) ?>,
    photo_statement: <?= json_encode(__a('16. Fotoğraf + Bildiri', '16. Photo + Statement')) ?>,
    project_map: <?= json_encode(__a('17. Proje Haritası', '17. Project Map')) ?>,
    sectioned_showcase: <?= json_encode(__a('18. Bölümlü Vitrin', '18. Sectioned Showcase')) ?>,
    free_collage: <?= json_encode(__a('19. Serbest Kolaj', '19. Free Collage')) ?>
};
window.extraTplPreview = function (template, c) {
    var S = c.slides || [], esc = function (t) { return String(t == null ? '' : t).replace(/[&<>"]/g, function (ch) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[ch]; }); };
    var wrap = function (inner) { return '<div style="position:relative; min-height:360px; padding:56px 24px 24px 24px; background:' + c.bg + '; color:' + c.text + ';">' + c.header + inner + '</div>'; };
    var mono = 'font-family:monospace; font-size:9.5px; letter-spacing:0.1em; color:' + c.muted + ';';
    if (template === 'sidebar_list') {
        var cur = S[c.idx] || S[0] || {};
        var rows = S.map(function (s, i) {
            var on = (i === c.idx);
            return '<div onmouseenter="selectStageSlide(' + i + ')" style="cursor:pointer; display:flex; gap:8px; padding:6px 0; border-bottom:1px solid ' + c.border + '; font-size:12px; color:' + (on ? c.text : c.muted) + ';"><span style="' + mono + (on ? 'color:#fdd000;' : '') + '">0' + (i + 1) + '</span>' + esc(s.title) + '</div>';
        }).join('');
        return wrap('<div style="display:grid; grid-template-columns:30% 1fr; gap:18px;"><div><div style="' + mono + ' margin-bottom:6px;">SEÇİLİ İŞLER</div>' + rows + '</div>' +
            '<div><img src="' + esc(cur.img) + '" style="width:100%; height:230px; object-fit:contain; object-position:left top; display:block;">' +
            (c.showTitle ? '<div style="font-size:12px; font-weight:700; margin-top:6px;">' + esc(cur.title) + (c.showLocYear ? ' <span style="' + mono + '">' + esc(cur.loc) + ' · ' + esc(cur.year) + '</span>' : '') + '</div>' : '') + '</div></div>');
    }
    if (template === 'year_archive') {
        var groups = {};
        S.forEach(function (s) { var m = String(s.year || '').match(/(19|20)\d{2}/g); var y = m ? Math.max.apply(null, m.map(Number)) : 0; (groups[y] = groups[y] || []).push(s); });
        var years = Object.keys(groups).map(Number).sort(function (a, b) { return b - a; });
        var html = years.map(function (y) {
            return '<div style="display:grid; grid-template-columns:70px 1fr; gap:12px; padding:10px 0; border-top:1px solid ' + c.border + ';"><div style="font-size:22px; font-weight:300;">' + (y || '—') + '</div><div>' +
                groups[y].map(function (s) { return '<div style="display:flex; align-items:center; gap:10px; padding:5px 0;"><img src="' + esc(s.img) + '" style="width:54px; height:36px; object-fit:cover;"><strong style="font-size:12px;">' + esc(s.title) + '</strong>' + (c.showLocYear ? '<span style="' + mono + ' margin-left:auto;">' + esc(s.loc) + '</span>' : '') + '</div>'; }).join('') + '</div></div>';
        }).join('');
        return wrap(html);
    }
    if (template === 'contact_sheet') {
        var frames = S.concat(S).slice(0, 8).map(function (s, i) {
            return '<div style="padding:4px;"><img src="' + esc(s.img) + '" style="width:100%; aspect-ratio:3/2; object-fit:cover; display:block;"><div style="font-family:monospace; font-size:8.5px; color:#d9a441; margin-top:3px;">' + String(i + 1).padStart(2, '0') + 'A <span style="color:#cfcab8;">' + esc(s.title).slice(0, 18) + '</span></div></div>';
        }).join('');
        return wrap('<div style="background:#0b0b0b; padding:12px 8px; display:grid; grid-template-columns:repeat(4, 1fr);">' + frames + '</div>');
    }
    if (template === 'photo_statement') {
        var lead = S[0] || {};
        var list = S.map(function (s, i) { return '<div style="display:flex; gap:8px; padding:5px 0; border-bottom:1px solid ' + c.border + '; font-size:11.5px;"><span style="' + mono + '">0' + (i + 1) + '</span>' + esc(s.title) + '</div>'; }).join('');
        return '<div style="position:relative; min-height:360px; display:grid; grid-template-columns:1fr 1fr; background:' + c.bg + '; color:' + c.text + ';">' + c.header +
            '<div style="background:url(' + esc(lead.img) + ') center/cover;"></div>' +
            '<div style="padding:60px 22px 20px; display:flex; flex-direction:column; justify-content:center; gap:16px;"><div style="font-size:20px; font-weight:300; line-height:1.2;">' + esc(c.statement || '') + '</div><div>' + list + '</div></div></div>';
    }
    if (template === 'project_map') {
        var dots = S.map(function (s, i) { var x = 18 + (i * 37) % 64, y = 30 + (i * 23) % 45; return '<span title="' + esc(s.title) + '" style="position:absolute; left:' + x + '%; top:' + y + '%; width:10px; height:10px; border-radius:50%; background:#fdd000; box-shadow:0 0 0 4px rgba(253,208,0,.25);"></span>'; }).join('');
        var list = S.map(function (s, i) { return '<div style="display:flex; gap:8px; padding:5px 0; border-bottom:1px solid ' + c.border + '; font-size:11.5px;"><span style="' + mono + '">0' + (i + 1) + '</span>' + esc(s.title) + '</div>'; }).join('');
        return '<div style="position:relative; min-height:360px; display:grid; grid-template-columns:1fr 32%; background:' + c.bg + '; color:' + c.text + ';">' + c.header +
            '<div style="position:relative; background:' + (c.bg === '#060709' ? '#16181c' : '#e7e5df') + ' radial-gradient(circle at 30% 40%, rgba(128,128,128,.25) 0 18%, transparent 19%), radial-gradient(circle at 65% 55%, rgba(128,128,128,.22) 0 22%, transparent 23%);">' + dots + '</div>' +
            '<div style="padding:56px 14px 14px;"><div style="' + mono + ' margin-bottom:6px;">PROJELER</div>' + list + '</div></div>';
    }
    if (template === 'sectioned_showcase') {
        var hero = S[0] || {};
        var cards = S.slice(1, 4).map(function (s) { return '<div><img src="' + esc(s.img) + '" style="width:100%; aspect-ratio:4/3; object-fit:cover; display:block;"><div style="font-size:11px; font-weight:700; margin-top:4px;">' + esc(s.title) + '</div></div>'; }).join('');
        return '<div style="position:relative; background:' + c.bg + '; color:' + c.text + ';">' + c.header +
            '<div style="height:200px; background:url(' + esc(hero.img) + ') center/cover; position:relative;"><div style="position:absolute; left:18px; bottom:14px; color:#fff; font-size:18px; font-weight:700; text-shadow:0 2px 8px rgba(0,0,0,.6);">' + esc(hero.title) + '</div></div>' +
            '<div style="padding:16px 18px;"><div style="' + mono + ' border-top:1px solid ' + c.border + '; padding-top:8px; margin-bottom:8px;">SEÇİLİ İŞLER</div><div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;">' + cards + '</div>' +
            '<div style="' + mono + ' border-top:1px solid ' + c.border + '; padding-top:8px; margin:14px 0 6px;">SON YAZILAR · HAKKIMDA · İLETİŞİM</div></div></div>';
    }
    if (template === 'thumb_grid') {
        var tiles = S.concat(S, S).slice(0, 12).map(function (s) { return '<img src="' + esc(s.img) + '" style="width:100%; aspect-ratio:1/1; object-fit:cover; display:block;">'; }).join('');
        return wrap('<div style="padding:10px; display:grid; grid-template-columns:repeat(4, 1fr); gap:6px;">' + tiles + '</div>');
    }
    if (template === 'book_spreads') {
        var pg = function (s) { return '<div style="display:flex; align-items:center; justify-content:center; padding:14px; box-shadow:inset 0 0 18px rgba(40,32,18,.18);"><img src="' + esc(s.img) + '" style="max-width:100%; max-height:120px; display:block;"></div>'; };
        return wrap('<div style="padding:16px; background:#f1ede4;"><div style="display:grid; grid-template-columns:1fr 1fr; background:#fbf8f1; box-shadow:0 8px 24px rgba(40,32,18,.18); min-height:170px;"><div style="padding:18px; display:flex; flex-direction:column; justify-content:flex-end; font-size:18px; font-weight:300; color:#1b1a17;">' + esc(c.statement || '') + '</div>' + pg(S[0] || {}) + '</div></div>');
    }
    if (template === 'mosaic_wall') {
        var cells = S.concat(S, S).slice(0, 9).map(function (s, i) { var sp = i === 0 ? 'grid-column:span 2; grid-row:span 2;' : (i === 5 ? 'grid-column:span 2;' : ''); return '<div style="' + sp + ' background:url(' + esc(s.img) + ') center/cover; min-height:60px;"></div>'; }).join('');
        return wrap('<div style="padding:8px; display:grid; grid-template-columns:repeat(4, 1fr); grid-auto-rows:60px; grid-auto-flow:dense; gap:3px;">' + cells + '</div>');
    }
    if (template === 'staggered_gallery') {
        var pos = [[2, 0, 44], [56, 14, 30], [14, 46, 32], [52, 52, 42]];
        return wrap('<div style="position:relative; height:180px; margin:10px;">' + pos.map(function (p, i) { return '<img src="' + esc(S[i % S.length].img) + '" alt="" style="position:absolute; left:' + p[0] + '%; top:' + p[1] + '%; width:' + p[2] + '%; height:auto; aspect-ratio:4/3; object-fit:cover;">'; }).join('') + '</div>');
    }
    if (template === 'split_grid') {
        var img = function (i, st) { return '<img src="' + esc(S[i % S.length].img) + '" alt="" style="width:100%; height:100%; object-fit:cover; display:block; ' + st + '">'; };
        return wrap('<div style="padding:8px; display:grid; grid-template-columns:2fr 1fr; grid-template-rows:62px 62px; gap:5px;">' + img(0, 'grid-row:span 2;') + img(1, '') + img(2, '') + '</div><div style="padding:0 8px 8px; display:grid; grid-template-columns:1fr 2fr; grid-template-rows:40px 40px; gap:5px;">' + img(3, '') + img(4, 'grid-column:2; grid-row:1 / span 2;') + img(5, 'grid-column:1; grid-row:2;') + '</div>');
    }
    if (template === 'featured_grid') {
        var tiles = S.concat(S).slice(1, 9).map(function (s) { return '<img src="' + esc(s.img) + '" alt="" style="width:100%; aspect-ratio:1/1; object-fit:cover; display:block;">'; }).join('');
        return wrap('<div style="padding:10px;"><img src="' + esc(S[0].img) + '" alt="" style="width:100%; height:96px; object-fit:cover; display:block; margin-bottom:6px;"><div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:5px;">' + tiles + '</div></div>');
    }
    if (template === 'photo_wall') {
        var hs = [78, 52, 96, 64, 88, 58];
        var col = function (o) { return '<div style="flex:1; display:flex; flex-direction:column; gap:6px;">' + [0, 1].map(function (k) { var s = S[(o + k * 3) % S.length]; return '<img src="' + esc(s.img) + '" alt="" style="width:100%; height:' + hs[(o + k * 3) % 6] + 'px; object-fit:cover; display:block;">'; }).join('') + '</div>'; };
        return wrap('<div style="padding:10px; display:flex; gap:6px; align-items:flex-start;">' + col(0) + col(1) + col(2) + '</div>');
    }
    if (template === 'zigzag_stream') {
        return wrap('<div style="padding:10px; display:flex; flex-direction:column; gap:10px;">' + [0, 1, 2].map(function (i) { return '<img src="' + esc(S[i % S.length].img) + '" alt="" style="width:58%; height:52px; object-fit:cover; display:block; align-self:' + (i % 2 ? 'flex-end' : 'flex-start') + ';">'; }).join('') + '</div>');
    }
    if (template === 'split_screens') {
        var row = function (i) { var im = '<div style="background:url(' + esc(S[i % S.length].img) + ') center/cover;"></div>', tx = '<div style="display:flex; flex-direction:column; justify-content:center; gap:5px; padding:10px;"><div style="height:7px; width:70%; background:currentColor; opacity:.7;"></div><div style="height:4px; width:50%; background:currentColor; opacity:.3;"></div></div>'; return '<div style="display:grid; grid-template-columns:1fr 1fr; height:88px;">' + (i % 2 ? tx + im : im + tx) + '</div>'; };
        return wrap('<div>' + row(0) + row(1) + '</div>');
    }
    if (template === 'triptych') {
        return wrap('<div style="display:grid; grid-template-columns:repeat(3, 1fr); height:180px;">' + [0, 1, 2].map(function (i) { return '<div style="position:relative; background:url(' + esc(S[i % S.length].img) + ') center/cover;"><div style="position:absolute; inset:0; background:rgba(0,0,0,.3); display:flex; align-items:center; justify-content:center;"><div style="height:5px; width:50%; background:#fff;"></div></div></div>'; }).join('') + '</div>');
    }
    if (template === 'section_banners') {
        return wrap('<div style="display:flex; flex-direction:column;">' + [0, 1, 2].map(function (i) { return '<div style="position:relative; height:60px; background:url(' + esc(S[i % S.length].img) + ') center/cover;"><div style="position:absolute; inset:0; background:rgba(0,0,0,.35); display:flex; align-items:center; justify-content:center; gap:6px;"><div style="height:7px; width:36%; background:#fff;"></div><div style="width:12px; height:12px; border:2px solid #fff; border-radius:50%;"></div></div></div>'; }).join('') + '</div>');
    }
    if (template === 'light_table') {
        var th = S.concat(S).slice(1, 6).map(function (s, i) { return '<img src="' + esc(s.img) + '" alt="" style="width:100%; aspect-ratio:3/2; object-fit:cover; display:block; opacity:' + (i ? '.45' : '1') + ';">'; }).join('');
        return wrap('<div style="padding:10px; display:grid; grid-template-columns:1fr 44px; gap:8px; height:170px;"><div style="display:flex; align-items:center; justify-content:center;"><img src="' + esc(S[0].img) + '" alt="" style="max-width:100%; max-height:150px; object-fit:contain; display:block;"></div><div style="display:flex; flex-direction:column; gap:4px; overflow:hidden;">' + th + '</div></div>');
    }
    if (template === 'free_collage') {
        var ws = [34, 22, 28, 40], xs = [4, 52, 26, 58];
        var items = S.map(function (s, i) { return '<div style="width:' + ws[i % 4] + '%; margin-left:' + Math.min(xs[i % 4], 100 - ws[i % 4]) + '%; margin-top:' + (i ? 14 : 0) + 'px;"><img src="' + esc(s.img) + '" style="width:100%; display:block;"></div>'; }).join('');
        return wrap('<div>' + items + '</div>');
    }
    return null;
};
</script>
<?php }
