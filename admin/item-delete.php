<?php
/**
 * admin/item-delete.php
 * İçerik Silme İşlemi
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Güvenlik doğrulaması geçersiz.');
    header('Location: portfolio.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("SELECT type FROM `items` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$id]);
    $type = $stmt->fetchColumn() ?: 'all';

    $del = $pdo->prepare("DELETE FROM `items` WHERE `id` = ?");
    $del->execute([$id]);
    set_flash('success', 'İçerik başarıyla silindi.');

    if (($_POST['return'] ?? '') === 'pages') {
        header('Location: pages.php');
        exit;
    }
    header('Location: portfolio.php?cat=' . urlencode($type));
    exit;
}

header('Location: portfolio.php');
exit;
