<?php
/**
 * robots.php — /robots.txt (Sitemap adresi sitenin kendi alan adından üretilir)
 */
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nAllow: /\nDisallow: /admin/\nSitemap: " . BASE_URL . "/sitemap.xml\n";
