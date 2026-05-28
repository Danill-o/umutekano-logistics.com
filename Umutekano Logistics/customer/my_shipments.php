<?php
require_once '../includes/header.php';
requireRole('customer');
global $conn, $user;
$uid = $user['id'];

$shipments = $conn->query("SELECT * FROM shipments WHERE customer_id=$uid ORDER BY created_at DESC");
?>
<h2 class="page-title">📦 My Shipments</h2>
<div class="card">
    <div class="card-header"><h3>All Shipments</h3> <a href="new_shipment.php" class="btn btn-primary btn-sm">+ New</a></div>
    <table>
        <thead><tr><th>Tracking</th><th>Receiver</th><th>Destination</th><th>Weight</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php while ($s = $shipments->fetch_assoc()): ?>
        <tr>
            <td><strong><?= $s['tracking_code'] ?></strong></td>
            <td><?= htmlspecialchars($s['receiver_name']) ?></td>
            <td><?= htmlspecialchars($s['delivery_address']) ?></td>
            <td><?= $s['weight_kg'] ?> kg</td>
            <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst(str_replace('_',' ',$s['status'])) ?></span></td>
            <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            <td><a href="track.php?code=<?= $s['tracking_code'] ?>" class="btn btn-sm btn-primary">Track</a></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php require_once '../includes/footer.php'; ?>
