<?php
/**
 * admin/api-reorder.php
 * Proje, Seri ve Tekil Fotoğrafların Ağırlık / Sıralama Güncelleme API'si
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek metodu.']);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'CSRF güvenlik hatası.']);
    exit;
}

$ids = $_POST['ids'] ?? [];
if (!is_array($ids) || empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'Sıralama verisi alınamadı.']);
    exit;
}

$targetParam = $_POST['target'] ?? 'items';
$valid_targets = ['items', 'menus', 'homepage_slides', 'categories'];
$target = in_array($targetParam, $valid_targets) ? $targetParam : 'items';

$pdo = DB::getConnection();

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE `{$target}` SET `weight` = ? WHERE `id` = ?");
    
    foreach ($ids as $index => $id) {
        $weight = ((int)$index + 1) * 10;
        $stmt->execute([$weight, (int)$id]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Sıralama başarıyla güncellendi.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
exit;
