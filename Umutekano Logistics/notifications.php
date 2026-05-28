<?php
require_once 'includes/header.php';
global $conn, $user;
$uid = $user['id'];

if (isset($_GET['read'])) {
    $nid  = (int)$_GET['read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $nid, $uid);
    $stmt->execute();
    if (!empty($_GET['goto'])) {
        header('Location: ' . BASE_URL . ltrim(urldecode($_GET['goto']), '/'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['mark_all'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
    } elseif (isset($_POST['delete_all'])) {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id=?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
    }
}

$notifs = $conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC");
$notifs->bind_param('i', $uid);
$notifs->execute();
$result = $notifs->get_result();
$icons  = ['info' => 'ℹ️', 'success' => '✅', 'warning' => '⚠️', 'danger' => '🚨'];
?>
<div class="flex-between" style="margin-bottom:20px">
    <h2 class="page-title" style="margin:0">🔔 Notifications</h2>
    <div style="display:flex;gap:8px">
        <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <button name="mark_all" class="btn btn-outline btn-sm">✓ Mark all read</button>
        </form>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete all notifications?')">
            <?= csrf_field() ?>
            <button name="delete_all" class="btn btn-danger btn-sm">🗑 Clear all</button>
        </form>
    </div>
</div>

<div class="card">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($n = $result->fetch_assoc()): ?>
        <a href="notifications.php?read=<?= $n['id'] ?><?= $n['link'] ? '&goto=' . urlencode($n['link']) : '' ?>"
           class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>" style="display:flex;padding:14px 20px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit">
            <div class="notif-icon <?= $n['type'] ?>" style="margin-right:12px"><?= $icons[$n['type']] ?? 'ℹ️' ?></div>
            <div style="flex:1">
                <div style="font-weight:<?= !$n['is_read'] ? '600' : '400' ?>;font-size:.9rem"><?= htmlspecialchars($n['title']) ?></div>
                <div style="color:#555;font-size:.85rem;margin-top:3px"><?= htmlspecialchars($n['message']) ?></div>
                <div style="color:var(--gray);font-size:.75rem;margin-top:4px"><?= timeAgo($n['created_at']) ?> &middot; <?= date('d M Y H:i', strtotime($n['created_at'])) ?></div>
            </div>
            <?php if (!$n['is_read']): ?>
            <div style="width:8px;height:8px;border-radius:50%;background:var(--orange);margin-top:6px;flex-shrink:0"></div>
            <?php endif; ?>
        </a>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><div class="icon">🔔</div><p>No notifications yet</p></div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
