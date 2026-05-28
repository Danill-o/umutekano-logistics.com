<?php
require_once '../includes/header.php';
requireRole('driver');
global $conn, $user;
$uid = $user['id'];

$filter = sanitize($conn, $_GET['status'] ?? '');
$where  = $filter ? "AND d.status='$filter'" : '';

$deliveries = $conn->query("SELECT d.*,s.tracking_code,s.pickup_address,s.delivery_address,
    s.receiver_name,s.receiver_phone,s.weight_kg,v.plate_number
    FROM deliveries d JOIN shipments s ON d.shipment_id=s.id JOIN vehicles v ON d.vehicle_id=v.id
    WHERE d.driver_id=$uid $where ORDER BY d.assigned_at DESC");

$total_delivered = $conn->query("SELECT COUNT(*) FROM deliveries WHERE driver_id=$uid AND status='delivered'")->fetch_row()[0];
$this_month      = $conn->query("SELECT COUNT(*) FROM deliveries WHERE driver_id=$uid AND status='delivered' AND MONTH(delivered_at)=MONTH(NOW())")->fetch_row()[0];
?>

<h2 class="page-title">📋 Delivery History</h2>

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card green"><div class="num"><?= $total_delivered ?></div><div class="label">Total Delivered</div></div>
    <div class="stat-card orange"><div class="num"><?= $this_month ?></div><div class="label">This Month</div></div>
</div>

<div class="filter-bar">
    <?php foreach ([''=>'All','assigned'=>'Assigned','picked_up'=>'Picked Up','in_transit'=>'In Transit','delivered'=>'Delivered','failed'=>'Failed'] as $v=>$l): ?>
    <a href="?status=<?= $v ?>" class="btn btn-sm <?= $filter===$v?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Receiver</th><th>Pickup</th><th>Destination</th><th>Weight</th><th>Vehicle</th><th>Status</th><th>Assigned</th><th>Delivered</th></tr></thead>
        <tbody>
        <?php $has = false; while ($d = $deliveries->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($d['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars($d['receiver_name']) ?><br><small class="text-muted"><?= $d['receiver_phone'] ?></small></td>
            <td><?= htmlspecialchars(substr($d['pickup_address'],0,25)) ?>...</td>
            <td><?= htmlspecialchars(substr($d['delivery_address'],0,25)) ?>...</td>
            <td><?= $d['weight_kg'] ?> kg</td>
            <td><?= $d['plate_number'] ?></td>
            <td><span class="badge badge-<?= $d['status'] ?>"><?= ucfirst(str_replace('_',' ',$d['status'])) ?></span></td>
            <td><?= date('d M Y', strtotime($d['assigned_at'])) ?></td>
            <td><?= $d['delivered_at'] ? date('d M Y', strtotime($d['delivered_at'])) : '—' ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="9"><div class="empty-state"><div class="icon">📋</div><p>No deliveries found</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
