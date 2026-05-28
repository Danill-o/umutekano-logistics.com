<?php
require_once '../includes/header.php';
requireRole('driver');
global $conn, $user;
$uid = $user['id'];

$total     = $conn->query("SELECT COUNT(*) FROM deliveries WHERE driver_id=$uid")->fetch_row()[0];
$delivered = $conn->query("SELECT COUNT(*) FROM deliveries WHERE driver_id=$uid AND status='delivered'")->fetch_row()[0];
$active    = $conn->query("SELECT COUNT(*) FROM deliveries WHERE driver_id=$uid AND status IN ('assigned','picked_up','in_transit')")->fetch_row()[0];
$failed    = $conn->query("SELECT COUNT(*) FROM deliveries WHERE driver_id=$uid AND status='failed'")->fetch_row()[0];

// Active delivery detail
$active_delivery = $conn->query("SELECT d.*,s.tracking_code,s.pickup_address,s.delivery_address,
    s.receiver_name,s.receiver_phone,v.plate_number,v.model
    FROM deliveries d JOIN shipments s ON d.shipment_id=s.id JOIN vehicles v ON d.vehicle_id=v.id
    WHERE d.driver_id=$uid AND d.status IN ('assigned','picked_up','in_transit')
    ORDER BY d.assigned_at DESC LIMIT 1")->fetch_assoc();

$recent = $conn->query("SELECT d.status,d.assigned_at,d.delivered_at,s.tracking_code,s.delivery_address,v.plate_number
    FROM deliveries d JOIN shipments s ON d.shipment_id=s.id JOIN vehicles v ON d.vehicle_id=v.id
    WHERE d.driver_id=$uid ORDER BY d.assigned_at DESC LIMIT 5");

// Success rate
$rate = $total > 0 ? round(($delivered / $total) * 100) : 0;
?>

<div class="dashboard-topbar">
    <div>
        <h2>Driver Dashboard</h2>
        <p>Track your current assignments, performance, and delivery progress in real time.</p>
    </div>
    <div class="dashboard-actions">
        <a href="deliveries.php" class="btn btn-primary">My Deliveries</a>
        <a href="history.php" class="btn btn-outline">History</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="num"><?= $total ?></div><div class="label">Total Assigned</div></div>
    <div class="stat-card green"><div class="num"><?= $delivered ?></div><div class="label">Delivered</div></div>
    <div class="stat-card orange"><div class="num"><?= $active ?></div><div class="label">Active</div></div>
    <div class="stat-card red"><div class="num"><?= $failed ?></div><div class="label">Failed</div></div>
    <div class="stat-card green"><div class="num"><?= $rate ?>%</div><div class="label">Success Rate</div></div>
</div>

<!-- Active Delivery Card -->
<?php if ($active_delivery): ?>
<div class="card" style="border-left:4px solid var(--orange);margin-bottom:24px">
    <div class="card-header">
        <h3>🚛 Active Delivery</h3>
        <span class="badge badge-<?= $active_delivery['status'] ?>"><?= ucfirst(str_replace('_',' ',$active_delivery['status'])) ?></span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
                <div class="text-muted text-small">Tracking</div>
                <div class="fw-bold"><?= $active_delivery['tracking_code'] ?></div>
            </div>
            <div>
                <div class="text-muted text-small">Vehicle</div>
                <div class="fw-bold"><?= $active_delivery['plate_number'] ?> – <?= htmlspecialchars($active_delivery['model']) ?></div>
            </div>
            <div>
                <div class="text-muted text-small">Pickup</div>
                <div><?= htmlspecialchars($active_delivery['pickup_address']) ?></div>
            </div>
            <div>
                <div class="text-muted text-small">Deliver To</div>
                <div><?= htmlspecialchars($active_delivery['delivery_address']) ?></div>
            </div>
            <div>
                <div class="text-muted text-small">Receiver</div>
                <div><?= htmlspecialchars($active_delivery['receiver_name']) ?> – <?= $active_delivery['receiver_phone'] ?></div>
            </div>
        </div>
        <div style="margin-top:16px">
            <a href="deliveries.php" class="btn btn-success">📍 Update Status</a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">No active delivery right now. Check <a href="deliveries.php">My Deliveries</a> for new assignments.</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>⚡ Quick Actions</h3></div>
    <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="deliveries.php" class="btn btn-primary">🗺️ My Deliveries</a>
        <a href="history.php" class="btn btn-outline">📋 Delivery History</a>
        <a href="<?= BASE_URL ?>profile.php" class="btn btn-outline">⚙️ My Profile</a>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Recent Deliveries</h3><a href="deliveries.php" class="btn btn-primary btn-sm">View All</a></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Destination</th><th>Vehicle</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php $has = false; while ($d = $recent->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($d['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars(substr($d['delivery_address'],0,30)) ?>...</td>
            <td><?= $d['plate_number'] ?></td>
            <td><span class="badge badge-<?= $d['status'] ?>"><?= ucfirst(str_replace('_',' ',$d['status'])) ?></span></td>
            <td><?= date('d M Y', strtotime($d['assigned_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="5"><div class="empty-state"><div class="icon">🗺️</div><p>No deliveries assigned yet</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
