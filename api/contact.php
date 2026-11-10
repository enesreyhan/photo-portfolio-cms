<?php
/**
 * api/contact.php
 * Web Sitesi İletişim Formu İşleyicisi
 * - Ziyaretçi mesajlarını güvenle veritabanına (`messages` tablosuna) kaydeder
 * - Çok Katmanlı Anti-Bot, Anti-Spam ve Flood Koruması:
 *     1. Çift Honeypot (Tuzak Giriş Alanları)
 *     2. İnsan Hızı Zaman Aşımı Kontrolü (_ft < 2.5s ve > 24s bot engelleme)
 *     3. Link & BBCode Spam Filtresi (3+ URL veya [url= bbcode içeren bot mesajları)
 *     4. Katı IP Hız Sınırı (10 dakikada en fazla 5, günde en fazla 15 mesaj)
 *     5. Girdi Boyut Kapsülü (DB şişmesini önleyen karakter sınırları)
 *     6. Sessiz Bot Eleme (Silent Drop - bota başarılı izlenimi verip DB'ye yazmama)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Yalnızca POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
    exit;
}

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1') ||
           (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

// Yalnızca site içi yollara geri dön (açık yönlendirme / phishing koruması)
$return_url = url('tr/hakkimda/');
$req_redirect = (string)($_POST['redirect_to'] ?? '');
$req_redirect = preg_replace('/#.*$/', '', $req_redirect);
if ($req_redirect !== ''
    && $req_redirect[0] === '/'
    && !preg_match('#^/[/\\\\]#', $req_redirect)          // //evil.com veya /\evil.com değil
    && !preg_match('/[\x00-\x1F\x7F]/', $req_redirect)) {   // kontrol karakteri yok
    $req_redirect = preg_replace('/([?&])(msg_success|msg_error)=[^&]*/', '$1', $req_redirect);
    $req_redirect = rtrim(str_replace(['?&', '&&'], ['?', '&'], $req_redirect), '?&');
    $return_url = $req_redirect;
}
$lang = trim($_POST['lang'] ?? 'tr');

function respond(bool $success, string $message, int $status_code = 200, array $extra = []) {
    global $is_ajax, $return_url;
    if ($is_ajax) {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
        exit;
    } else {
        $sep = str_contains($return_url, '?') ? '&' : '?';
        $flag = $success ? 'msg_success=1' : ('msg_error=' . urlencode($message));
        header('Location: ' . $return_url . $sep . $flag . '#iletisim');
        exit;
    }
}

// Başarılı sahte mesaj (Bot yakalandığında kullanılır)
$fake_success_msg = ($lang === 'tr')
    ? 'Mesajınız başarıyla iletildi. En kısa sürede sizinle iletişime geçeceğim.'
    : 'Your message has been received. I will get back to you as soon as possible.';

// ────────────────────────────────────────────────────────
// 1. ANTİ-BOT TUZAĞI: Honeypot Alanları
// Normal insanlar CSS ile görünmeyen bu alanları görmez ve boş bırakır.
// Botlar ise HTML içindeki tüm input'ları otomatik doldurur.
// ────────────────────────────────────────────────────────
if (!empty($_POST['contact_company_hp']) || !empty($_POST['website_hp']) || !empty($_POST['address_val_hp'])) {
    // Bot yakalandı: Hata döndürme ki bot tekrar denemesin, sessizce yut
    respond(true, $fake_success_msg);
}

// ────────────────────────────────────────────────────────
// 2. ANTİ-BOT TUZAĞI: İnsan Hızı Zaman Kontrolü
// Bir insanın ad, e-posta ve mesaj yazıp göndermesi en az 2.5 saniye sürer.
// Botlar sayfayı açar açmaz 0.1 saniyede formu post eder.
// ────────────────────────────────────────────────────────
$load_time = isset($_POST['_ft']) ? (int)$_POST['_ft'] : 0;
$time_diff = time() - $load_time;
if ($load_time <= 0 || $time_diff < 2 || $time_diff > 86400) {
    // Zaman damgası olmayan, 2 saniyeden hızlı veya 24 saatten eski form gönderimleri bot kabul edilir
    respond(true, $fake_success_msg);
}

// ────────────────────────────────────────────────────────
// 3. Girdi Temizleme, Doğrulama ve Boyut Sınırlandırma
// Veritabanını şişirmemek için karakter sınırları uygulanır.
// ────────────────────────────────────────────────────────
$name    = mb_substr(trim(strip_tags($_POST['name'] ?? '')), 0, 100, 'UTF-8');
$email   = mb_substr(trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL)), 0, 120, 'UTF-8');
$subject = mb_substr(trim(strip_tags($_POST['subject'] ?? '')), 0, 200, 'UTF-8');
$message = mb_substr(trim(strip_tags($_POST['message'] ?? '')), 0, 3000, 'UTF-8');

if (mb_strlen($name, 'UTF-8') < 2) {
    $err = ($lang === 'tr') ? 'Lütfen geçerli bir isim giriniz.' : 'Please enter a valid name.';
    respond(false, $err, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = ($lang === 'tr') ? 'Lütfen geçerli bir e-posta adresi giriniz.' : 'Please enter a valid email address.';
    respond(false, $err, 400);
}

if (mb_strlen($message, 'UTF-8') < 5) {
    $err = ($lang === 'tr') ? 'Lütfen iletmek istediğiniz mesajınızı yazınız (en az 5 karakter).' : 'Please enter your message (at least 5 characters).';
    respond(false, $err, 400);
}

// ────────────────────────────────────────────────────────
// 4. ANTİ-SPAM: Aşırı Link ve Reklam/Bahis Botu Taraması
// Spam botlarının %95'i mesaj içerisine çok sayıda link veya bbcode [url] gömer.
// Normal ziyaretçi en fazla 1-2 portfolyo linki paylaşır.
// ────────────────────────────────────────────────────────
$combined_text = $subject . ' ' . $message;
$link_count = preg_match_all('#https?://#i', $combined_text, $m_links);
$has_bbcode = preg_match('#\[(url|link|href)#i', $combined_text);

if ($link_count > 2 || $has_bbcode) {
    // Spam bot link yaymaya çalışıyor -> Sessizce yut, veritabanına yazma
    respond(true, $fake_success_msg);
}

// ────────────────────────────────────────────────────────
// 5. FLOOD & DOS KORUMASI: IP Başına Katı Hız Sınırı
// - 10 dakikada en fazla 5 mesaj
// - 24 saatte en fazla 15 mesaj
// ────────────────────────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

try {
    $pdo = DB::getConnection();

    // Tablonun varlığını ve indekslerini garantiye al
    $table_check_sql = (DB_DRIVER === 'sqlite')
        ? "CREATE TABLE IF NOT EXISTS `messages` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(191) NOT NULL,
            `subject` VARCHAR(255) NULL,
            `message` TEXT NOT NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_agent` TEXT NULL,
            `is_read` INTEGER NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_messages_ip ON `messages`(`ip_address`);
        CREATE INDEX IF NOT EXISTS idx_messages_created ON `messages`(`created_at`);"
        : "CREATE TABLE IF NOT EXISTS `messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(150) NOT NULL,
            `email` varchar(191) NOT NULL,
            `subject` varchar(255) DEFAULT NULL,
            `message` text NOT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `is_read` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_is_read` (`is_read`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_ip_address` (`ip_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($table_check_sql);

    // 10 dakikalık kontrol
    if (DB_DRIVER === 'sqlite') {
        $flood_stmt = $pdo->prepare("SELECT COUNT(*) FROM `messages` WHERE `ip_address` = ? AND `created_at` > datetime('now', '-10 minutes')");
    } else {
        $flood_stmt = $pdo->prepare("SELECT COUNT(*) FROM `messages` WHERE `ip_address` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    }
    $flood_stmt->execute([$ip]);
    if ((int)$flood_stmt->fetchColumn() >= 5) {
        $err = ($lang === 'tr')
            ? 'Kısa süre içinde çok fazla mesaj gönderdiniz. Lütfen birkaç dakika sonra tekrar deneyin.'
            : 'Too many messages sent in a short time. Please wait a few minutes before trying again.';
        respond(false, $err, 429);
    }

    // 24 saatlik günlük IP sınırı (Botun aralıklarla spam yapmasını engeller)
    if (DB_DRIVER === 'sqlite') {
        $day_stmt = $pdo->prepare("SELECT COUNT(*) FROM `messages` WHERE `ip_address` = ? AND `created_at` > datetime('now', '-1 day')");
    } else {
        $day_stmt = $pdo->prepare("SELECT COUNT(*) FROM `messages` WHERE `ip_address` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }
    $day_stmt->execute([$ip]);
    if ((int)$day_stmt->fetchColumn() >= 15) {
        $err = ($lang === 'tr')
            ? 'Günlük mesaj gönderme sınırına ulaştınız. Lütfen daha sonra tekrar deneyin.'
            : 'Daily message limit reached. Please try again later.';
        respond(false, $err, 429);
    }

    // ────────────────────────────────────────────────────────
    // 6. Mesajı Veritabanına Kaydet
    // (Yalnızca tüm filtreleri geçen gerçek insan mesajları kaydedilir)
    // ────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO `messages` (`name`, `email`, `subject`, `message`, `ip_address`, `user_agent`, `is_read`, `created_at`)
        VALUES (?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$name, $email, $subject, $message, $ip, $user_agent]);
    $new_message_id = (int)$pdo->lastInsertId();

} catch (\Throwable $e) {
    error_log("Contact Form DB Error: " . $e->getMessage());
    $err = ($lang === 'tr') ? 'Mesaj kaydedilirken teknik bir hata oluştu.' : 'A technical error occurred while saving your message.';
    respond(false, $err, 500);
}

// 7. E-posta Bildirimi (mesaj veritabanına kaydedildikten sonra; gönderim hatası ziyaretçiyi etkilemez)
// Alıcı: `contact_notify_email` ayarı, yoksa `contact_email`. Lokal geliştirmede gönderilmez.
$notify_to = trim(get_setting('contact_notify_email', '') ?: get_setting('contact_email', ''));
if (empty($is_local) && filter_var($notify_to, FILTER_VALIDATE_EMAIL) && function_exists('mail')) {
    $one_line = fn(string $s): string => trim(preg_replace('/[\r\n\t]+/', ' ', $s));
    $mail_host = preg_replace('/^www\./i', '', (string)parse_url(BASE_URL, PHP_URL_HOST)) ?: 'localhost';

    $mail_subject = 'Yeni iletişim mesajı: ' . ($subject !== '' ? $one_line($subject) : $one_line($name));
    $mail_body = "Siteden yeni bir mesaj geldi.\n\n"
        . "Ad: " . $one_line($name) . "\n"
        . "E-posta: " . $email . "\n"
        . "Konu: " . ($subject !== '' ? $one_line($subject) : '-') . "\n"
        . "Tarih: " . date('d.m.Y H:i') . "\n"
        . "IP: " . $ip . "\n\n"
        . "Mesaj:\n" . $message . "\n\n"
        . "Admin panelinde görüntüle: " . url('admin/messages.php') . "\n";

    $mail_headers = implode("\r\n", [
        'From: ' . mb_encode_mimeheader(site_owner_name(), 'UTF-8') . ' <no-reply@' . $mail_host . '>',
        'Reply-To: ' . $email, // FILTER_VALIDATE_EMAIL ile doğrulandı, satır sonu içeremez
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ]);

    try {
        if (!@mail($notify_to, mb_encode_mimeheader($mail_subject, 'UTF-8'), $mail_body, $mail_headers)) {
            error_log('Contact Form: bildirim e-postası gönderilemedi (mesaj id ' . $new_message_id . ')');
        }
    } catch (\Throwable $e) {
        error_log('Contact Form Mail Error: ' . $e->getMessage());
    }
}

// 8. Başarılı Yanıt Döndür
$success_msg = ($lang === 'tr')
    ? 'Mesajınız başarıyla iletildi. En kısa sürede sizinle iletişime geçeceğim.'
    : 'Your message has been received. I will get back to you as soon as possible.';

respond(true, $success_msg, 200, ['id' => $new_message_id]);
