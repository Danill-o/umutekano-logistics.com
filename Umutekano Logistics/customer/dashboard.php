<?php
require_once '../includes/header.php';
requireRole('customer');
global $conn, $user;
$uid = $user['id'];

$total     = $conn->query("SELECT COUNT(*) FROM shipments WHERE customer_id=$uid")->fetch_row()[0];
$delivered = $conn->query("SELECT COUNT(*) FROM shipments WHERE customer_id=$uid AND status='delivered'")->fetch_row()[0];
$pending   = $conn->query("SELECT COUNT(*) FROM shipments WHERE customer_id=$uid AND status='pending'")->fetch_row()[0];
$in_transit= $conn->query("SELECT COUNT(*) FROM shipments WHERE customer_id=$uid AND status='in_transit'")->fetch_row()[0];
$paid      = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE customer_id=$uid AND status='paid'")->fetch_row()[0];
$unpaid    = $conn->query("SELECT COUNT(*) FROM payments WHERE customer_id=$uid AND status IN ('pending','processing')")->fetch_row()[0];

$recent = $conn->query("SELECT s.tracking_code,s.delivery_address,s.status,s.created_at,
    (SELECT status FROM payments WHERE shipment_id=s.id LIMIT 1) AS pay_status
    FROM shipments s WHERE customer_id=$uid ORDER BY s.created_at DESC LIMIT 6");
?>

<div class="dashboard-topbar">
    <div>
        <h2>Customer Dashboard</h2>
        <p>Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>. Manage your shipments, payments and tracking from one place.</p>
    </div>
    <div class="dashboard-actions">
        <a href="new_shipment.php" class="btn btn-primary">New Shipment</a>
        <a href="payments.php" class="btn btn-outline">Payment History</a>
    </div>
</div>

<?php if ($unpaid > 0): ?>
<div class="alert alert-warning">
    ⚠️ You have <strong><?= $unpaid ?></strong> unpaid invoice<?= $unpaid > 1 ? 's' : '' ?>.
    <a href="payments.php" style="color:inherit;font-weight:700;margin-left:8px">Pay Now →</a>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card"><div class="num"><?= $total ?></div><div class="label">Total Shipments</div></div>
    <div class="stat-card green"><div class="num"><?= $delivered ?></div><div class="label">Delivered</div></div>
    <div class="stat-card orange"><div class="num"><?= $in_transit ?></div><div class="label">In Transit</div></div>
    <div class="stat-card"><div class="num"><?= $pending ?></div><div class="label">Pending</div></div>
    <div class="stat-card green"><div class="num">RWF <?= number_format($paid) ?></div><div class="label">Total Paid</div></div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>⚡ Quick Actions</h3></div>
    <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="new_shipment.php" class="btn btn-primary">➕ New Shipment</a>
        <a href="track.php" class="btn btn-success">📍 Track Package</a>
        <a href="payments.php" class="btn btn-warning">💳 Pay Invoice</a>
        <a href="my_shipments.php" class="btn btn-outline">📦 All Shipments</a>
        <a href="<?= BASE_URL ?>profile.php" class="btn btn-outline">⚙️ My Profile</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Shipments</h3>
        <a href="new_shipment.php" class="btn btn-primary btn-sm">+ New Shipment</a>
    </div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Destination</th><th>Status</th><th>Payment</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php $has = false; while ($s = $recent->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($s['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars(substr($s['delivery_address'],0,30)) ?>...</td>
            <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst(str_replace('_',' ',$s['status'])) ?></span></td>
            <td>
                <?php if ($s['pay_status']): ?>
                <span class="badge badge-<?= $s['pay_status'] ?>"><?= ucfirst($s['pay_status']) ?></span>
                <?php else: ?>
                <span class="text-muted text-small">—</span>
                <?php endif; ?>
            </td>
            <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            <td><a href="track.php?code=<?= $s['tracking_code'] ?>" class="btn btn-sm btn-primary">Track</a></td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="icon">📦</div><p>No shipments yet. <a href="new_shipment.php">Create your first shipment</a></p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
