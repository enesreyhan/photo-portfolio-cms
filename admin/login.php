<?php
/**
 * admin/login.php
 * Yönetici Giriş Ekranı
 * Stravia Modern Dashboard Mizanpajı (Açık Tema)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

init_session();

if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}

if (is_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$error = '';
// İlk kurulum: hiç yönetici yoksa giriş formu yerine ilk yönetici hesabı oluşturulur
$pdo = DB::getConnection();
$needs_setup = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn() === 0;
// Kurulum iki adımlı: 1) dil seçimi (?setup_lang=xx)  2) o dilde hesap formu; panel de o dilde açılır
$setup_lang = '';
$setup_langs = [];
if ($needs_setup) {
    require_once __DIR__ . '/../includes/setup_i18n.php';
    $setup_langs = setup_languages();
    $cand = strtolower((string)($_POST['setup_lang'] ?? $_GET['setup_lang'] ?? ''));
    if (isset($setup_langs[$cand])) $setup_lang = $cand;
}
$T = fn(string $k): string => setup_t($k, $setup_lang ?: 'en');
if ($needs_setup && $setup_lang !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email    = trim($_POST['email'] ?? '');
    $owner    = trim($_POST['owner_name'] ?? '');
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = $T('err_csrf');
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = $T('err_user');
    } elseif (mb_strlen($password) < 10) {
        $error = $T('err_pw');
    } elseif ($password !== ($_POST['password_confirm'] ?? '')) {
        $error = $T('err_match');
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $T('err_email');
    } elseif ((int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn() !== 0) {
        $error = $T('err_exists');
        $needs_setup = false;
    } else {
        $pdo->prepare("INSERT INTO `users` (`username`, `password_hash`, `email`, `created_at`) VALUES (?, ?, ?, ?)")
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $email, date('Y-m-d H:i:s')]);
        if ($owner !== '') set_setting('site_owner_name', $owner);
        if ($email !== '' && get_setting('contact_email') === '') set_setting('contact_email', $email);
        set_setting('admin_panel_lang', $setup_lang);
        // Kurulum dili sitede de varsa varsayılan site dili olur
        $st = $pdo->prepare("SELECT COUNT(*) FROM `languages` WHERE `code` = ?");
        $st->execute([$setup_lang]);
        if ((int)$st->fetchColumn() > 0) {
            $pdo->prepare("UPDATE `languages` SET `is_default` = CASE WHEN `code` = ? THEN 1 ELSE 0 END")->execute([$setup_lang]);
        }
        attempt_login($username, $password);
        $_SESSION['admin_lang'] = $setup_lang;
        header('Location: ' . url('admin/dashboard.php'));
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Güvenlik doğrulaması zaman aşımına uğradı, lütfen tekrar deneyin.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $lock_min = login_lock_remaining_minutes();
        if ($lock_min > 0) {
            $error = 'Çok fazla hatalı deneme yapıldı. Lütfen ' . $lock_min . ' dakika sonra tekrar deneyin.';
        } elseif (attempt_login($username, $password)) {
            login_clear_failures();
            header('Location: ' . url('admin/dashboard.php'));
            exit;
        } else {
            login_record_failure();
            usleep(800000); // her hatalı denemeyi yavaşlat
            $error = 'Kullanıcı adı veya parola hatalı.';
            if (login_lock_remaining_minutes() > 0) {
                $error = 'Çok fazla hatalı deneme yapıldı. Lütfen ' . LOGIN_LOCK_MINUTES . ' dakika sonra tekrar deneyin.';
            }
        }
    }
}
// Karanlık / Aydınlık Tema Tercihi
$admin_theme = $_COOKIE['admin_theme'] ?? 'light';
if (!in_array($admin_theme, ['dark', 'light'], true)) {
    $admin_theme = 'light';
}
?>
<!DOCTYPE html>
<?php $page_lang = $needs_setup ? ($setup_lang ?: 'en') : 'tr'; ?>
<html lang="<?= e($page_lang) ?>" dir="<?= e(($setup_langs[$setup_lang]['direction'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr') ?>" data-theme="<?= e($admin_theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $needs_setup ? e($T('title')) : 'Giriş Yap - ' . e(site_owner_name()) . ' Yönetim Paneli' ?></title>
    <link rel="icon" type="image/png" href="<?= url('assets/brand/favicon.png') ?>" />
    
    <!-- Tema Titremesini Önleyen Erken Yükleme Betiği -->
    <script>
    (function() {
        try {
            var t = localStorage.getItem('admin_theme');
            if (!t) {
                var m = document.cookie.match(/admin_theme=([^;]+)/);
                t = m ? m[1] : null;
            }
            if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                t = 'dark';
            }
            if (t === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        } catch (e) {}
    })();
    </script>

    <!-- Tipografi ve Fontlar -->
    <link rel="stylesheet" href="<?= url('fonts.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            color-scheme: light;
            --font-body: "ZedTextFtl", "Space Grotesk", 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-header: "ZedDisplayFtl", "Space Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            
            /* Stravia Modern Panel Açık Tema Paleti */
            --bg-canvas: #f4f5f6;
            --bg-card: #ffffff;
            --border-card: #eaecf0;
            --border-light: #e2e8f0;
            --border-subtle: #f1f5f9;
            
            --text-main: #0f172a;
            --text-muted: #5c6479;
            --text-sub: #94a3b8;
            
            --primary: #0f172a;
            --primary-hover: #1e293b;
            --primary-text: #ffffff;
            
            --accent-blue: #2563eb;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #b91c1c;
        }

        [data-theme="dark"] {
            color-scheme: dark;
            --bg-canvas: #121212;
            --bg-card: #1e1e1e;
            --border-card: #282828;
            --border-light: #383838;
            --border-subtle: #222222;

            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --text-sub: #6b7280;

            --primary: #ffffff;
            --primary-hover: #e5e7eb;
            --primary-text: #121212;

            --accent-blue: #3b82f6;
            --danger-bg: rgba(239, 68, 68, 0.15);
            --danger-border: rgba(239, 68, 68, 0.35);
            --danger-text: #fca5a5;
        }

        html[data-theme="dark"] .theme-icon-sun { display: block; }
        html[data-theme="dark"] .theme-icon-moon { display: none; }
        html:not([data-theme="dark"]) .theme-icon-sun { display: none; }
        html:not([data-theme="dark"]) .theme-icon-moon { display: block; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        ::selection {
            background-color: #dbeafe !important;
            color: #1e40af !important;
        }
        ::-moz-selection {
            background-color: #dbeafe !important;
            color: #1e40af !important;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-canvas);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .login-wrap {
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 10px;
            width: 100%;
            padding: 36px 32px 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03), 0 10px 25px -5px rgba(15, 23, 42, 0.05);
        }

        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .brand-symbol {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #ffffff;
            border: 1px solid var(--border-card);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .brand-symbol img {
            width: 30px;
            height: 30px;
            object-fit: contain;
            display: block;
        }

        .login-title {
            font-family: var(--font-header);
            font-size: 21px;
            font-weight: 700;
            text-align: center;
            color: var(--text-main);
            letter-spacing: -0.025em;
            margin-bottom: 6px;
        }

        .login-sub {
            font-size: 13px;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 6px;
            color: var(--text-main);
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s, background-color 0.15s;
        }

        [data-theme="dark"] .form-control {
            background: #181818;
            border-color: #383838;
            color: #f3f4f6;
        }

        [data-theme="dark"] .brand-symbol {
            background: #181818;
            border-color: #282828;
        }

        /* Tema Değiştirme Düğmesi */
        .login-theme-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            color: var(--text-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .login-theme-btn:hover {
            color: var(--text-main);
            border-color: var(--border-light);
        }

        .theme-icon-sun { display: none; }
        .theme-icon-moon { display: block; }
        [data-theme="dark"] .theme-icon-sun { display: block; color: #fbbf24; }
        [data-theme="dark"] .theme-icon-moon { display: none; }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .form-control::placeholder {
            color: var(--text-sub);
        }

        .btn-submit {
            width: 100%;
            padding: 11px 16px;
            background: var(--primary);
            color: var(--primary-text);
            border: 1px solid var(--primary);
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .setup-lang-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px; margin-top:18px; }
        .setup-lang-btn { display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid var(--border, rgba(0,0,0,.12)); border-radius:10px; text-decoration:none; color:inherit; font-size:14px; }
        .setup-lang-btn:hover, .setup-lang-btn:focus-visible { border-color:currentColor; }
        .error-msg {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
            padding: 11px 14px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-msg svg {
            flex-shrink: 0;
        }

        .login-footer {
            text-align: center;
            padding-top: 4px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s ease;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .back-link:hover {
            color: var(--text-main);
        }
    </style>
</head>
<body>

<button type="button" class="login-theme-btn" onclick="toggleLoginTheme()" title="Karanlık / Aydınlık Tema (Ctrl+Shift+D)" aria-label="Tema"><!-- i18n-ignore: login page has no admin i18n loaded -->
    <svg class="theme-icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
    <svg class="theme-icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
</button>

<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <div class="brand-symbol">
                <img src="<?= url('assets/brand/favicon.png') ?>" alt="<?= e(site_owner_name()) ?>" />
            </div>
        </div>
        <?php if ($needs_setup && $setup_lang === ''): ?>
        <h1 class="login-title"><?= e($T('choose')) ?></h1>
        <p class="login-sub">Choose the setup language · Kurulum dilini seçin</p><!-- i18n-ignore: bilingual on purpose -->
        <div class="setup-lang-grid">
            <?php foreach ($setup_langs as $lc => $ld): ?>
            <a class="setup-lang-btn" href="?setup_lang=<?= e($lc) ?>" lang="<?= e($lc) ?>"><span><?= e($ld['flag'] ?? '') ?></span><span><?= e($ld['name_native']) ?></span></a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <h1 class="login-title"><?= $needs_setup ? e($T('welcome')) : 'Yönetim Paneli' ?></h1>
        <p class="login-sub"><?= $needs_setup ? e($T('sub')) : e(site_owner_name()) . ' içerik yönetim sistemi' ?></p>

        <?php if ($error): ?>
            <div class="error-msg">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <?php if ($needs_setup): ?><input type="hidden" name="setup_lang" value="<?= e($setup_lang) ?>"><?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="username"><?= $needs_setup ? e($T('username')) : 'Kullanıcı Adı' ?></label>
                <input type="text" id="username" name="username" class="form-control" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
            </div>

            <?php if ($needs_setup): ?>
            <div class="form-group">
                <label class="form-label" for="owner_name"><?= e($T('owner')) ?></label>
                <input type="text" id="owner_name" name="owner_name" class="form-control" maxlength="120" value="<?= e($_POST['owner_name'] ?? '') ?>" placeholder="<?= e($T('owner_ph')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="email"><?= e($T('email')) ?></label>
                <input type="email" id="email" name="email" class="form-control" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="password"><?= $needs_setup ? e($T('password')) : 'Parola' ?></label>
                <input type="password" id="password" name="password" class="form-control" required <?= $needs_setup ? 'minlength="10" autocomplete="new-password"' : 'autocomplete="current-password"' ?>>
            </div>

            <?php if ($needs_setup): ?>
            <div class="form-group">
                <label class="form-label" for="password_confirm"><?= e($T('password2')) ?></label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="10" autocomplete="new-password">
            </div>
            <?php endif; ?>

            <button type="submit" class="btn-submit">
                <span><?= $needs_setup ? e($T('submit')) : 'Giriş Yap' ?></span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
        </form>
        <?php if ($needs_setup): ?>
        <p style="text-align:center; margin-top:14px; font-size:13px;"><a href="login.php" class="back-link" style="justify-content:center;"><?= e($T('change_lang')) ?></a></p>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="login-footer">
        <a href="<?= url('/') ?>" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span><?= $needs_setup ? e($T('back')) : 'Web Sitesine Dön' ?></span>
        </a>
    </div>
</div>

<script>
function toggleLoginTheme() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var nextTheme = isDark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', nextTheme);
    try {
        localStorage.setItem('admin_theme', nextTheme);
        document.cookie = 'admin_theme=' + nextTheme + '; path=/; max-age=31536000; SameSite=Lax';
    } catch (e) {}
}

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'D' || e.key === 'd')) {
        e.preventDefault();
        toggleLoginTheme();
    }
});
</script>

</body>
</html>
