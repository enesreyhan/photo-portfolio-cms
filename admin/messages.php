<?php
/**
 * admin/messages.php
 * Gelen İletişim Mesajları Yönetimi
 * Web sitesi iletişim formundan gelen mesajları listeleme, okuma, yanıtlama ve silme.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

require_login();

$pdo = DB::getConnection();

// Tablonun varlığını garantiye al
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
    );"
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
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$pdo->exec($table_check_sql);

// AJAX veya Form POST İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrf)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF Hatası']);
            exit;
        }
        flash('error', 'Güvenlik doğrulaması başarısız oldu.');
        redirect('admin/messages.php');
    }

    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE `messages` SET `is_read` = 1 WHERE `id` = ?")->execute([$id]);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => true]);
            exit;
        }
        flash('success', 'Mesaj okundu olarak işaretlendi.');
        redirect('admin/messages.php');
    }

    if ($action === 'mark_unread') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE `messages` SET `is_read` = 0 WHERE `id` = ?")->execute([$id]);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => true]);
            exit;
        }
        flash('success', 'Mesaj okunmadı olarak işaretlendi.');
        redirect('admin/messages.php');
    }

    if ($action === 'mark_all_read') {
        $pdo->exec("UPDATE `messages` SET `is_read` = 1 WHERE `is_read` = 0");
        flash('success', 'Tüm mesajlar okundu olarak işaretlendi.');
        redirect('admin/messages.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM `messages` WHERE `id` = ?")->execute([$id]);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => true]);
            exit;
        }
        flash('success', 'Mesaj silindi.');
        redirect('admin/messages.php');
    }

    if ($action === 'bulk_action') {
        $bulk_type = $_POST['bulk_type'] ?? '';
        $ids = $_POST['selected_ids'] ?? [];
        if (is_array($ids) && !empty($ids)) {
            $int_ids = array_map('intval', $ids);
            $in_clause = implode(',', $int_ids);
            if ($bulk_type === 'delete') {
                $pdo->exec("DELETE FROM `messages` WHERE `id` IN ($in_clause)");
                flash('success', count($int_ids) . ' adet mesaj silindi.');
            } elseif ($bulk_type === 'mark_read') {
                $pdo->exec("UPDATE `messages` SET `is_read` = 1 WHERE `id` IN ($in_clause)");
                flash('success', count($int_ids) . ' adet mesaj okundu olarak işaretlendi.');
            } elseif ($bulk_type === 'mark_unread') {
                $pdo->exec("UPDATE `messages` SET `is_read` = 0 WHERE `id` IN ($in_clause)");
                flash('success', count($int_ids) . ' adet mesaj okunmadı olarak işaretlendi.');
            }
        }
        redirect('admin/messages.php');
    }
}

// Filtre ve Arama Parametreleri
$filter = $_GET['filter'] ?? 'all'; // all, unread, read
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($filter === 'unread') {
    $where[] = "`is_read` = 0";
} elseif ($filter === 'read') {
    $where[] = "`is_read` = 1";
}

if ($search !== '') {
    $where[] = "(`name` LIKE ? OR `email` LIKE ? OR `subject` LIKE ? OR `message` LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

// Sayaçlar
$count_all = (int)$pdo->query("SELECT COUNT(*) FROM `messages`")->fetchColumn();
$count_unread = (int)$pdo->query("SELECT COUNT(*) FROM `messages` WHERE `is_read` = 0")->fetchColumn();
$count_read = (int)$pdo->query("SELECT COUNT(*) FROM `messages` WHERE `is_read` = 1")->fetchColumn();

// Sayfalama (Pagination)
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$total_rows_stmt = $pdo->prepare("SELECT COUNT(*) FROM `messages` $where_sql");
$total_rows_stmt->execute($params);
$total_rows = (int)$total_rows_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT * FROM `messages` $where_sql ORDER BY `created_at` DESC, `id` DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Otomatik açılacak mesaj ID'si (Örn: mailden tıklandıysa)
$auto_open_id = (int)($_GET['id'] ?? 0);

$admin_title = __a('menu_messages', 'Gelen Mesajlar');
require __DIR__ . '/includes/header.php';
?>

<div class="page-header-wrap" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="page-header-title" style="display:flex; align-items:center; gap:10px;">
            <span><?= e(__a('menu_messages', 'Gelen Mesajlar')) ?></span>
            <?php if ($count_unread > 0): ?>
                <span class="badge" style="background:#ef4444; color:#fff; font-size:12px; font-weight:700; padding:2px 8px; border-radius:10px;"><?= $count_unread ?> <?= e(__a('Yeni', 'New')) ?></span>
            <?php endif; ?>
        </h1>
        <p class="page-header-sub"><?= e(__a('Web sitesi iletişim formundan iletilen doğrudan mesajlar ve iş birliği talepleri.', 'Direct messages and collaboration requests submitted from the website contact form.')) ?></p>
    </div>
    <div class="page-header-actions" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <?php if ($count_unread > 0): ?>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-secondary" title="<?= e(__a('Tüm okunmamış mesajları okundu say', 'Mark all unread messages as read')) ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span><?= e(__a('Tümünü Okundu İşaretle', 'Mark All as Read')) ?></span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Filtre Sekmeleri ve Arama Çubuğu -->
<div class="card" style="margin-bottom:20px; padding:14px 18px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <!-- Filtre Sekmeleri -->
        <div style="display:flex; align-items:center; gap:8px;">
            <a href="?filter=all<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
               class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:13px; padding:6px 14px;">
                <?= e(__a('Tümü', 'All')) ?> (<?= $count_all ?>)
            </a>
            <a href="?filter=unread<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
               class="btn <?= $filter === 'unread' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:13px; padding:6px 14px;">
                <?= e(__a('Okunmamış', 'Unread')) ?> (<?= $count_unread ?>)
            </a>
            <a href="?filter=read<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
               class="btn <?= $filter === 'read' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:13px; padding:6px 14px;">
                <?= e(__a('Okunmuş', 'Read')) ?> (<?= $count_read ?>)
            </a>
        </div>

        <!-- Arama Formu -->
        <form method="GET" style="display:flex; align-items:center; gap:8px; margin:0;">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <div style="position:relative; width:260px;">
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="<?= e(__a('İsim, e-posta veya mesaj ara...', 'Search by name, email, or message...')) ?>" style="padding-left:32px; font-size:13px; height:34px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:10px; top:10px; opacity:0.5;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
            <button type="submit" class="btn btn-secondary" style="height:34px; padding:0 12px; font-size:12px;"><?= e(__a('Ara', 'Search')) ?></button>
            <?php if ($search !== ''): ?>
                <a href="?filter=<?= e($filter) ?>" class="btn btn-secondary" style="height:34px; padding:0 10px; font-size:12px; color:var(--danger-text);" title="<?= e(__a('Aramayı Temizle', 'Clear Search')) ?>">&times;</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Mesaj Listesi Kartı -->
<form method="POST" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="bulk_action">
    <input type="hidden" name="bulk_type" id="bulk_type" value="">

    <div class="card">
        <!-- Toplu İşlem Butonları Barı (Checkbox seçilince belirir) -->
        <div id="bulkBar" style="display:none; padding:10px 20px; background:rgba(253, 208, 0, 0.12); border-bottom:1px solid rgba(253, 208, 0, 0.3); align-items:center; justify-content:space-between;">
            <span style="font-size:13px; font-weight:600;"><span id="selectedCount">0</span> <?= e(__a('mesaj seçildi', 'messages selected')) ?></span>
            <div style="display:flex; gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="submitBulk('mark_read')"><?= e(__a('Okundu İşaretle', 'Mark as Read')) ?></button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="submitBulk('mark_unread')"><?= e(__a('Okunmadı İşaretle', 'Mark as Unread')) ?></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="adminConfirm(<?= e(json_encode(__a('Seçili mesajlar kalıcı olarak silinecek. Bu işlem geri alınamaz.', 'The selected messages will be deleted permanently. This cannot be undone.'))) ?>).then(function (ok) { if (ok) submitBulk('delete'); });"><?= e(__a('Sil', 'Delete')) ?></button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="admin-table" data-m="messages">
                <thead>
                    <tr>
                        <th style="width:36px; text-align:center;">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="cursor:pointer;">
                        </th>
                        <th style="width:40px; text-align:center;"></th>
                        <th style="width:190px;"><?= e(__a('Gönderen', 'Sender')) ?></th>
                        <th style="width:200px;"><?= e(__a('Konu', 'Subject')) ?></th>
                        <th><?= e(__a('Mesaj Özeti', 'Message Summary')) ?></th>
                        <th style="width:140px;"><?= e(__a('Tarih', 'Date')) ?></th>
                        <th style="width:130px; text-align:right;"><?= e(__a('İşlemler', 'Actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:50px 20px; color:var(--text-sub);">
                                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px; display:block; opacity:0.4;"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <strong style="font-size:16px; display:block; margin-bottom:4px;"><?= e(__a('Gelen mesaj bulunamadı', 'No incoming messages found')) ?></strong>
                                <span style="font-size:13px; opacity:0.75;">
                                    <?= $search !== '' ? e(__a('Arama kriterlerinize uygun mesaj bulunamadı.', 'No messages found matching your search criteria.')) : e(__a('Ziyaretçiler iletişim formunu doldurduğunda mesajlar burada listelenecektir.', 'Messages submitted through your contact form will appear here.')) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($messages as $m): 
                        $is_unread = (int)$m['is_read'] === 0;
                        $m_name = $m['name'] ?: __a('İsimsiz', 'Anonymous');
                        $m_email = $m['email'];
                        $m_subject = $m['subject'] ?: __a('Konu Belirtilmedi', 'Subject Not Specified');
                        $m_preview = mb_substr(strip_tags($m['message']), 0, 75, 'UTF-8');
                        if (mb_strlen($m['message'], 'UTF-8') > 75) $m_preview .= '...';
                        // SQLite CURRENT_TIMESTAMP değeri UTC'dir; panelde İstanbul saatiyle göster
                        try {
                            $m_dt = new DateTime((string)$m['created_at'], new DateTimeZone(DB_DRIVER === 'sqlite' ? 'UTC' : date_default_timezone_get()));
                            $m_dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                            $m_date = $m_dt->format('d.m.Y H:i');
                        } catch (\Throwable $e) {
                            $m_date = (string)$m['created_at'];
                        }
                    ?>
                        <tr id="row-<?= $m['id'] ?>" class="<?= $is_unread ? 'row-unread' : '' ?>" style="<?= $is_unread ? 'background:rgba(253, 208, 0, 0.05); font-weight:600;' : '' ?>">
                            <td style="text-align:center;">
                                <input type="checkbox" name="selected_ids[]" value="<?= $m['id'] ?>" class="msg-chk" onchange="updateBulkBar()" style="cursor:pointer;">
                            </td>
                            <td style="text-align:center; padding:0 4px;">
                                <?php if ($is_unread): ?>
                                    <span class="status-dot" style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ef4444;" title="<?= e(__a('Okunmamış Yeni Mesaj', 'Unread New Message')) ?>"></span>
                                <?php else: ?>
                                    <span class="status-dot" style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--border-subtle); opacity:0.5;" title="<?= e(__a('Okundu', 'Read')) ?>"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; flex-direction:column;">
                                    <span style="color:var(--text-main); font-size:14px;"><?= e($m_name) ?></span>
                                    <a href="mailto:<?= e($m_email) ?>" style="font-size:12px; color:var(--text-muted); font-weight:normal; text-decoration:none;" onclick="event.stopPropagation();">
                                        <?= e($m_email) ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:13px; color:var(--text-main);"><?= e($m_subject) ?></span>
                            </td>
                            <td>
                                <div style="cursor:pointer; color:var(--text-sub); font-size:13px; font-weight:normal; max-width:420px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" onclick="openMsgModal(<?= (int)$m['id'] ?>)">
                                    <?= e($m_preview) ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono); white-space:nowrap;"><?= $m_date ?></span>
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openMsgModal(<?= (int)$m['id'] ?>)" title="<?= e(__a('Detayı Oku', 'Read Details')) ?>" style="padding:4px 8px; font-size:12px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <span><?= e(__a('Oku', 'Read')) ?></span>
                                </button>
                                <details class="row-menu" onclick="event.stopPropagation();">
                                    <summary class="btn btn-secondary btn-sm" aria-label="<?= e(__a('İşlemler', 'Actions')) ?>">⋯</summary>
                                    <div class="row-menu-list">
                                        <a href="mailto:<?= e($m_email) ?>?subject=Re:%20<?= urlencode($m_subject) ?>"><?= e(__a('E-posta ile yanıtla', 'Reply by email')) ?></a>
                                        <button type="button" onclick="toggleReadStatus(<?= (int)$m['id'] ?>, <?= $is_unread ? 1 : 0 ?>)"><?= $is_unread ? e(__a('Okundu işaretle', 'Mark as read')) : e(__a('Okunmadı işaretle', 'Mark as unread')) ?></button>
                                        <hr>
                                        <button type="button" class="is-danger" onclick="deleteMessage(<?= (int)$m['id'] ?>)"><?= e(__a('Sil', 'Delete')) ?></button>
                                    </div>
                                </details>
                            </td>
                        </tr>

                        <!-- Gizli Mesaj Verisi (Modal İçin) -->
                        <script>
                            window.MSG_DATA = window.MSG_DATA || {};
                            window.MSG_DATA[<?= (int)$m['id'] ?>] = <?= json_encode([
                                'id'         => (int)$m['id'],
                                'name'       => $m['name'],
                                'email'      => $m['email'],
                                'subject'    => $m['subject'],
                                'message'    => $m['message'],
                                'ip'         => $m['ip_address'],
                                'ua'         => $m['user_agent'],
                                'is_read'    => (int)$m['is_read'],
                                'created_at' => $m_date
                            ], JSON_UNESCAPED_UNICODE) ?>;
                        </script>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Sayfalama (Pagination) -->
        <?php if ($total_pages > 1): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-top:1px solid var(--border-subtle); font-size:13px;">
                <span style="color:var(--text-muted);">Toplam <?= $total_rows ?> mesaj, Sayfa <?= $page ?> / <?= $total_pages ?></span>
                <div style="display:flex; gap:6px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&filter=<?= e($filter) ?>&q=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">&larr; Önceki</a>
                    <?php endif; ?>
                    <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                        <a href="?page=<?= $p ?>&filter=<?= e($filter) ?>&q=<?= urlencode($search) ?>" class="btn <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&filter=<?= e($filter) ?>&q=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">Sonraki &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- ── Mesaj Detay Modalı ── -->
<div id="msgModal" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div class="modal-box" style="background:var(--bg-canvas); border:1px solid var(--border-subtle); border-radius:10px; width:100%; max-width:680px; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 20px 40px rgba(0,0,0,0.4); overflow:hidden;">
        
        <!-- Modal Başlık -->
        <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid var(--border-subtle);">
            <div>
                <h3 id="modalMsgSubject" style="margin:0; font-size:16px; font-weight:700; color:var(--text-main);"><?= e(__a('Mesaj Detayı', 'Message Details')) ?></h3>
                <span id="modalMsgDate" style="font-size:12px; color:var(--text-muted); font-family:var(--font-mono);"></span>
            </div>
            <button type="button" class="modal-close" onclick="closeMsgModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--text-sub); line-height:1;">&times;</button>
        </div>

        <!-- Modal Gövdesi -->
        <div class="modal-body" style="padding:24px; overflow-y:auto; flex:1;">
            <!-- Gönderen Bilgi Kutusu -->
            <div style="background:var(--bg-canvas-subtle); border:1px solid var(--border-subtle); border-radius:6px; padding:14px 18px; margin-bottom:20px;">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; font-size:13px;">
                    <div>
                        <span style="color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:0.05em; display:block; margin-bottom:2px;"><?= e(__a('Gönderen:', 'Sender:')) ?></span>
                        <strong id="modalMsgName" style="color:var(--text-main); font-size:14px;"></strong>
                    </div>
                    <div>
                        <span style="color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:0.05em; display:block; margin-bottom:2px;"><?= e(__a('E-posta Adresi:', 'Email Address:')) ?></span>
                        <a id="modalMsgEmail" href="#" style="color:#fdd000; font-weight:600; text-decoration:none;"></a>
                    </div>
                    <div>
                        <span style="color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:0.05em; display:block; margin-bottom:2px;"><?= e(__a('IP Adresi:', 'IP Address:')) ?></span>
                        <span id="modalMsgIp" style="font-family:var(--font-mono); font-size:12px; color:var(--text-sub);"></span>
                    </div>
                </div>
            </div>

            <!-- Mesaj Metni -->
            <div>
                <span style="color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:0.05em; display:block; margin-bottom:8px; font-weight:600;"><?= e(__a('Mesaj İçeriği:', 'Message Content:')) ?></span>
                <div id="modalMsgBody" style="background:var(--bg-canvas-subtle); border:1px solid var(--border-subtle); border-left:3px solid #fdd000; border-radius:6px; padding:18px; font-size:14px; line-height:1.65; color:var(--text-main); white-space:pre-wrap; word-break:break-word;"></div>
            </div>
        </div>

        <!-- Modal Alt Butonlar -->
        <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; border-top:1px solid var(--border-subtle); background:var(--bg-canvas-subtle);">
            <div>
                <button type="button" id="modalDeleteBtn" class="btn btn-danger btn-sm"><?= e(__a('Bu Mesajı Sil', 'Delete this Message')) ?></button>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeMsgModal()"><?= e(__a('Kapat', 'Close')) ?></button>
                <a id="modalReplyBtn" href="#" class="btn btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                    <span><?= e(__a('E-posta ile Yanıtla', 'Reply via Email')) ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
var currentOpenMsgId = null;
var I18N_MSG = {
    untitled: <?= json_encode(__a('Konu Belirtilmedi', 'Subject Not Specified')) ?>,
    anonymous: <?= json_encode(__a('İsimsiz', 'Anonymous')) ?>,
    unknown: <?= json_encode(__a('Bilinmiyor', 'Unknown')) ?>,
    reply_subject: <?= json_encode(__a('İletişim Mesajınız', 'Your Contact Message')) ?>,
    confirm_del_single: <?= json_encode(__a('Bu mesajı silmek istediğinize emin misiniz?', 'Are you sure you want to delete this message?')) ?>
};

function toggleSelectAll(master) {
    var checkboxes = document.querySelectorAll('.msg-chk');
    checkboxes.forEach(function(cb) { cb.checked = master.checked; });
    updateBulkBar();
}

function updateBulkBar() {
    var checked = document.querySelectorAll('.msg-chk:checked');
    var bar = document.getElementById('bulkBar');
    var countSpan = document.getElementById('selectedCount');
    if (checked.length > 0) {
        bar.style.display = 'flex';
        countSpan.textContent = checked.length;
    } else {
        bar.style.display = 'none';
        var selectAll = document.getElementById('selectAll');
        if (selectAll) selectAll.checked = false;
    }
}

function submitBulk(type) {
    document.getElementById('bulk_type').value = type;
    document.getElementById('bulkForm').submit();
}

function openMsgModal(id) {
    var data = window.MSG_DATA && window.MSG_DATA[id];
    if (!data) return;
    currentOpenMsgId = id;

    document.getElementById('modalMsgSubject').textContent = data.subject || I18N_MSG.untitled;
    document.getElementById('modalMsgDate').textContent = data.created_at;
    document.getElementById('modalMsgName').textContent = data.name || I18N_MSG.anonymous;
    
    var emailLink = document.getElementById('modalMsgEmail');
    emailLink.textContent = data.email;
    emailLink.href = 'mailto:' + encodeURIComponent(data.email) + '?subject=Re: ' + encodeURIComponent(data.subject || I18N_MSG.reply_subject);

    document.getElementById('modalMsgIp').textContent = data.ip || I18N_MSG.unknown;
    document.getElementById('modalMsgBody').textContent = data.message;

    var replyBtn = document.getElementById('modalReplyBtn');
    replyBtn.href = 'mailto:' + encodeURIComponent(data.email) + '?subject=Re: ' + encodeURIComponent(data.subject || I18N_MSG.reply_subject);

    var delBtn = document.getElementById('modalDeleteBtn');
    delBtn.onclick = function() { deleteMessage(id); };

    var modal = document.getElementById('msgModal');
    modal.style.display = 'flex';

    // Eğer okunmamışsa arka planda okundu yap
    if (data.is_read === 0) {
        fetch('messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=mark_read&id=' + id + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN)
        }).then(function() {
            data.is_read = 1;
            var row = document.getElementById('row-' + id);
            if (row) {
                row.style.background = '';
                row.style.fontWeight = 'normal';
                var dot = row.querySelector('.status-dot');
                if (dot) {
                    dot.style.background = 'var(--border-subtle)';
                    dot.style.opacity = '0.5';
                    dot.title = 'Okundu';
                }
            }
        });
    }
}

function closeMsgModal() {
    var modal = document.getElementById('msgModal');
    modal.style.display = 'none';
    currentOpenMsgId = null;
}

// Modal dışına tıklandığında veya ESC basıldığında kapat
window.addEventListener('click', function(e) {
    var modal = document.getElementById('msgModal');
    if (e.target === modal) {
        closeMsgModal();
    }
});
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMsgModal();
});

function toggleReadStatus(id, currentStatus) {
    var newAction = (currentStatus === 1) ? 'mark_unread' : 'mark_read';
    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">' +
                     '<input type="hidden" name="action" value="' + newAction + '">' +
                     '<input type="hidden" name="id" value="' + id + '">';
    document.body.appendChild(form);
    form.submit();
}

function deleteMessage(id) {
    adminConfirm(window.ADMIN_LANG === 'en' ? 'This message will be deleted permanently. This cannot be undone.' : 'Bu mesaj kalıcı olarak silinecek. Bu işlem geri alınamaz.').then(function (ok) { if (ok) deleteMessageNow(id); });
}
function deleteMessageNow(id) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">' +
                     '<input type="hidden" name="action" value="delete">' +
                     '<input type="hidden" name="id" value="' + id + '">';
    document.body.appendChild(form);
    form.submit();
}

// Otomatik açılması istenen mesaj varsa
<?php if ($auto_open_id > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    openMsgModal(<?= $auto_open_id ?>);
});
<?php endif; ?>
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
