<?php
/**
 * includes/auth.php
 * Oturum Yönetimi, Kimlik Doğrulama ve Güvenlik
 */

require_once __DIR__ . '/db.php';

function init_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $is_https,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function is_logged_in(): bool {
    init_session();
    if (empty($_SESSION['admin_user_id'])) return false;
    // Oturumdaki kullanıcı silinmiş ya da veritabanı yeniden kurulmuşsa oturum geçersizdir
    static $verified = null;
    if ($verified === null) {
        try {
            $st = DB::getConnection()->prepare("SELECT 1 FROM `users` WHERE `id` = ? LIMIT 1");
            $st->execute([(int)$_SESSION['admin_user_id']]);
            $verified = (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            $verified = false;
        }
        if (!$verified) unset($_SESSION['admin_user_id'], $_SESSION['admin_username'], $_SESSION['admin_email']);
    }
    return $verified;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['admin_user_id'] ?? 0,
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'email'    => $_SESSION['admin_email'] ?? ''
    ];
}

function attempt_login(string $username, string $password): bool {
    init_session();
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_email'] = $user['email'] ?? '';
        return true;
    }
    return false;
}

function admin_logout(): void {
    init_session();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function csrf_token(): string {
    init_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool {
    init_session();
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash(string $type, string $message): void {
    init_session();
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
    init_session();
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $flashes;
}

/* ══════════════════════════════════════════════════════════════
 * Giriş Deneme Sınırı (kaba kuvvet koruması)
 * Aynı IP'den LOGIN_MAX_ATTEMPTS hatalı denemeden sonra LOGIN_LOCK_MINUTES dakika kilit.
 * ══════════════════════════════════════════════════════════════ */
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 15;

function login_client_ip(): string {
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function login_attempts_table(): void {
    static $ready = false;
    if ($ready) return;
    $pdo = DB::getConnection();
    $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (`ip` VARCHAR(45) NOT NULL, `attempted_at` INT NOT NULL)");
    $ready = true;
}

/** Kilitliyse kalan dakika, değilse 0 döner. */
function login_lock_remaining_minutes(): int {
    try {
        login_attempts_table();
        $pdo = DB::getConnection();
        $since = time() - LOGIN_LOCK_MINUTES * 60;
        $pdo->prepare("DELETE FROM `login_attempts` WHERE `attempted_at` < ?")->execute([$since]);
        $st = $pdo->prepare("SELECT COUNT(*), MIN(`attempted_at`) FROM `login_attempts` WHERE `ip` = ? AND `attempted_at` >= ?");
        $st->execute([login_client_ip(), $since]);
        [$count, $first] = $st->fetch(PDO::FETCH_NUM);
        if ((int)$count < LOGIN_MAX_ATTEMPTS) return 0;
        $unlock = (int)$first + LOGIN_LOCK_MINUTES * 60;
        return max(1, (int)ceil(($unlock - time()) / 60));
    } catch (\Throwable $e) {
        error_log('login throttle: ' . $e->getMessage());
        return 0; // tablo sorunu girişi tamamen kilitlemesin
    }
}

function login_record_failure(): void {
    try {
        login_attempts_table();
        DB::getConnection()->prepare("INSERT INTO `login_attempts` (`ip`, `attempted_at`) VALUES (?, ?)")->execute([login_client_ip(), time()]);
    } catch (\Throwable $e) {
        error_log('login throttle: ' . $e->getMessage());
    }
}

function login_clear_failures(): void {
    try {
        login_attempts_table();
        DB::getConnection()->prepare("DELETE FROM `login_attempts` WHERE `ip` = ?")->execute([login_client_ip()]);
    } catch (\Throwable $e) {}
}

