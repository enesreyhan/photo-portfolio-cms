<?php
/**
 * Yönetici parolasını terminalden sıfırlar (kullanıcı yoksa oluşturur).
 * Kullanım: php scripts/reset-password.php <kullanici_adi>
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../includes/db.php';

$username = $argv[1] ?? '';
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Kullanım: php scripts/reset-password.php <kullanici_adi>\n");
    exit(1);
}

echo "Yeni parola (en az 10 karakter): ";
system('stty -echo 2>/dev/null');
$password = rtrim((string)fgets(STDIN), "\r\n");
system('stty echo 2>/dev/null');
echo "\n";
if (mb_strlen($password) < 10) {
    fwrite(STDERR, "Parola en az 10 karakter olmalı.\n");
    exit(1);
}

$pdo  = DB::getConnection();
$hash = password_hash($password, PASSWORD_DEFAULT);
$st   = $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `username` = ?");
$st->execute([$hash, $username]);
if ($st->rowCount() === 0) {
    $pdo->prepare("INSERT INTO `users` (`username`, `password_hash`, `email`, `created_at`) VALUES (?, ?, '', ?)")
        ->execute([$username, $hash, date('Y-m-d H:i:s')]);
    echo "Yeni yönetici oluşturuldu: {$username}\n";
} else {
    echo "Parola güncellendi: {$username}\n";
}
// Kilitlenmiş giriş denemelerini temizle
try { $pdo->exec("DELETE FROM `login_attempts`"); } catch (\Throwable $e) {}
