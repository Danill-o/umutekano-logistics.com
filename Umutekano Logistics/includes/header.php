<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

global $conn;
$user         = currentUser();
$role         = $user['role'];
$uid          = $user['id'];
$unread       = unread_count($uid);
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Fetch latest 5 notifications
$notifs = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");

function nav_active(string $file, string $dir = ''): string {
    global $current_page, $current_dir;
    return ($current_page === $file && ($dir === '' || $current_dir === $dir)) ? ' active' : '';
}

$notif_icons = ['info'=>'ℹ️','success'=>'✅','warning'=>'⚠️','danger'=>'🚨'];
$initials    = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<script defer src="<?= BASE_URL ?>assets/js/app.js"></script>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div style="display:flex;align-items:center;gap:12px">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <a class="brand" href="<?= BASE_URL ?>">🛡️ Umutekano <span>Logistics</span></a>
    </div>
    <div class="navbar-right">
        <!-- Notification Bell -->
        <div style="position:relative">
            <button class="notif-btn" onclick="toggleNotif()" title="Notifications">
                🔔
                <?php if ($unread > 0): ?>
                <span class="notif-badge"><?= $unread > 9 ? '9+' : $unread ?></span>
                <?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDropdown" style="display:none;" aria-hidden="true">
                <div class="notif-dropdown-header">
                    <span>Notifications <?php if ($unread): ?><span class="badge badge-warning"><?= $unread ?> new</span><?php endif; ?></span>
                    <a href="<?= BASE_URL ?>notifications.php">View all</a>
                </div>
                <?php if ($notifs && $notifs->num_rows > 0): ?>
                    <?php while ($n = $notifs->fetch_assoc()): ?>
                    <a href="<?= BASE_URL ?>notifications.php?read=<?= $n['id'] ?><?= $n['link'] ? '&goto='.urlencode($n['link']) : '' ?>"
                       class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                        <div class="notif-icon <?= $n['type'] ?>"><?= $notif_icons[$n['type']] ?? 'ℹ️' ?></div>
                        <div class="notif-text">
                            <div class="ntitle"><?= htmlspecialchars($n['title']) ?></div>
                            <div class="ntime"><?= timeAgo($n['created_at']) ?></div>
                        </div>
                    </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notif-empty">No notifications yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- User chip -->
        <div class="user-chip">
            <?php if ($user['avatar']): ?>
                <img src="<?= BASE_URL . 'assets/images/' . htmlspecialchars($user['avatar']) ?>" alt="">
            <?php else: ?>
                <div class="avatar-placeholder"><?= $initials ?></div>
            <?php endif; ?>
            <span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
        </div>

        <form method="post" action="<?= BASE_URL ?>logout.php" style="display:inline">
            <button class="btn-logout" type="submit">Logout</button>
        </form>
    </div>
</div>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="layout">
<aside class="sidebar" id="sidebar">
<?php if ($role === 'admin'): ?>
    <div class="section-label">Main</div>
    <a href="<?= BASE_URL ?>admin/dashboard.php"  class="<?= nav_active('dashboard.php','admin') ?>">📊 Dashboard</a>
    <div class="section-label">Management</div>
    <a href="<?= BASE_URL ?>admin/shipments.php"  class="<?= nav_active('shipments.php','admin') ?>">📦 Shipments</a>
    <a href="<?= BASE_URL ?>admin/deliveries.php" class="<?= nav_active('deliveries.php','admin') ?>">🗺️ Deliveries</a>
    <a href="<?= BASE_URL ?>admin/drivers.php"    class="<?= nav_active('drivers.php','admin') ?>">🧑‍✈️ Drivers</a>
    <a href="<?= BASE_URL ?>admin/vehicles.php"   class="<?= nav_active('vehicles.php','admin') ?>">🚛 Vehicles</a>
    <a href="<?= BASE_URL ?>admin/customers.php"  class="<?= nav_active('customers.php','admin') ?>">👥 Customers</a>
    <a href="<?= BASE_URL ?>admin/warehouses.php" class="<?= nav_active('warehouses.php','admin') ?>">🏭 Warehouses</a>
    <div class="section-label">Finance</div>
    <a href="<?= BASE_URL ?>admin/payments.php"   class="<?= nav_active('payments.php','admin') ?>">💳 Payments</a>
    <div class="section-label">Analytics</div>
    <a href="<?= BASE_URL ?>admin/reports.php"    class="<?= nav_active('reports.php','admin') ?>">📈 Reports</a>
<?php elseif ($role === 'customer'): ?>
    <div class="section-label">My Account</div>
    <a href="<?= BASE_URL ?>customer/dashboard.php"    class="<?= nav_active('dashboard.php','customer') ?>">🏠 Dashboard</a>
    <div class="section-label">Shipments</div>
    <a href="<?= BASE_URL ?>customer/new_shipment.php" class="<?= nav_active('new_shipment.php','customer') ?>">➕ New Shipment</a>
    <a href="<?= BASE_URL ?>customer/my_shipments.php" class="<?= nav_active('my_shipments.php','customer') ?>">📦 My Shipments</a>
    <a href="<?= BASE_URL ?>customer/track.php"        class="<?= nav_active('track.php','customer') ?>">📍 Track Package</a>
    <div class="section-label">Finance</div>
    <a href="<?= BASE_URL ?>customer/payments.php"     class="<?= nav_active('payments.php','customer') ?>">💳 Payments</a>
<?php elseif ($role === 'driver'): ?>
    <div class="section-label">My Work</div>
    <a href="<?= BASE_URL ?>driver/dashboard.php"   class="<?= nav_active('dashboard.php','driver') ?>">🏠 Dashboard</a>
    <a href="<?= BASE_URL ?>driver/deliveries.php"  class="<?= nav_active('deliveries.php','driver') ?>">🗺️ My Deliveries</a>
    <a href="<?= BASE_URL ?>driver/history.php"     class="<?= nav_active('history.php','driver') ?>">📋 History</a>
<?php endif; ?>
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>profile.php">⚙️ My Profile</a>
    </div>
</aside>

<main class="main">

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}
function toggleNotif() {
    document.getElementById('notifDropdown').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const dd = document.getElementById('notifDropdown');
    if (dd && !dd.contains(e.target) && !e.target.closest('.notif-btn')) {
        dd.classList.remove('open');
    }
});
</script>
