<?php
/**
 * admin/includes/settings_tabs.php
 * Site Ayarları başlığı + sekme çubuğu. settings.php ve page-names.php (URL Yönetimi) ortak kullanır,
 * böylece URL Yönetimi Site Ayarları'nın bir sekmesi olarak görünür.
 *
 * @param string $active  settings.php içindeyse '' (aktif sekmeyi JS seçer), URL Yönetimi'nde 'url', Diller'de 'diller'
 */
function render_settings_header_and_tabs(string $active = ''): void {
    $on_settings = ($active === '');
    $tabs = [
        'genel'          => [__a('Genel', 'General'), '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'],
        'marka'          => [__a('Logo', 'Logo'), '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>'],
        'sayfalar'       => [__a('Sayfalar & Özellikler', 'Pages & Features'), '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>'],
        'url'            => [__a('URL Yönetimi', 'URL Management'), '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'],
        'diller'         => [__a('Diller', 'Languages'), '<path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/>'],
        'gizlilik'       => [__a('Gizlilik', 'Privacy'), '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
        'entegrasyonlar' => [__a('Entegrasyonlar', 'Integrations'), '<path d="M9 2v6"/><path d="M15 2v6"/><path d="M6 8h12v4a6 6 0 0 1-12 0z"/><path d="M12 18v4"/>'],
        'hesap'          => [__a('Hesap & Yedek', 'Account & Backup'), '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/>'],
    ];
    ?>
<div class="page-header-wrap">
    <div>
        <h1 class="page-header-title"><?= e(__a('Site Ayarları', 'Site Settings')) ?></h1>
        <p class="page-header-sub"><?= e(__a('Sitenin geneline ait ayarlar. Sayfa tasarımları için “Temalar”, içerik için sol menüdeki İçerik bölümünü kullanın.', 'Site-wide settings. Use “Themes” for page layouts and the Content section in the sidebar for content.')) ?></p>
    </div>
</div>

<nav class="st-tabs" role="tablist" aria-label="<?= e(__a('Ayar bölümleri', 'Settings sections')) ?>">
    <?php foreach ($tabs as $tab_id => $tab):
        if ($tab_id === 'url' || $tab_id === 'diller') {
            // Ayrı sayfalar (page-names.php, languages.php) — her zaman gerçek bağlantı
            $href = $tab_id === 'url' ? 'page-names.php' : 'languages.php';
            $attrs = '';
            $is_active = ($active === $tab_id);
        } elseif ($on_settings) {
            $href = '#' . $tab_id;
            $attrs = ' data-tab="' . e($tab_id) . '"';
            $is_active = false; // JS seçer
        } else {
            $href = 'settings.php#' . $tab_id;
            $attrs = '';
            $is_active = false;
        }
    ?>
    <a href="<?= e($href) ?>" class="st-tab<?= $is_active ? ' is-active' : '' ?>" role="tab"<?= $attrs ?> aria-selected="<?= $is_active ? 'true' : 'false' ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $tab[1] ?></svg>
        <span><?= e($tab[0]) ?></span>
    </a>
    <?php endforeach; ?>
</nav>
    <?php
}
