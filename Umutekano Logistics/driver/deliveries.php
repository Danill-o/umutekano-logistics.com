<?php
require_once '../includes/header.php';
requireRole('driver');
global $conn, $user;
$uid = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    csrf_verify();
    $did    = (int)$_POST['delivery_id'];
    $valid  = ['assigned', 'picked_up', 'in_transit', 'delivered', 'failed'];
    $status = in_array($_POST['status'] ?? '', $valid) ? $_POST['status'] : '';
    $notes  = trim($_POST['notes'] ?? '');

    if ($status) {
        $time_sql = '';
        if ($status === 'picked_up') $time_sql = ', picked_up_at=NOW()';
        if ($status === 'delivered') $time_sql = ', delivered_at=NOW()';

        $stmt = $conn->prepare("UPDATE deliveries SET status=?, notes=? $time_sql WHERE id=? AND driver_id=?");
        $stmt->bind_param('ssii', $status, $notes, $did, $uid);
        $stmt->execute();

        if ($status === 'delivered') {
            $r = $conn->prepare("SELECT shipment_id, vehicle_id FROM deliveries WHERE id=?");
            $r->bind_param('i', $did); $r->execute();
            $row = $r->get_result()->fetch_assoc();

            $u1 = $conn->prepare("UPDATE shipments SET status='delivered' WHERE id=?");
            $u1->bind_param('i', $row['shipment_id']); $u1->execute();

            $u2 = $conn->prepare("UPDATE vehicles SET status='available' WHERE id=?");
            $u2->bind_param('i', $row['vehicle_id']); $u2->execute();

            $s = $conn->prepare("SELECT tracking_code,customer_id FROM shipments WHERE id=?");
            $s->bind_param('i', $row['shipment_id']); $s->execute();
            $ship = $s->get_result()->fetch_assoc();
            notify($ship['customer_id'], 'Package Delivered! ✅', "Your shipment {$ship['tracking_code']} has been delivered successfully.", 'success', 'customer/track.php?code=' . $ship['tracking_code']);

        } elseif ($status === 'in_transit') {
            $r = $conn->prepare("SELECT shipment_id FROM deliveries WHERE id=?");
            $r->bind_param('i', $did); $r->execute();
            $row = $r->get_result()->fetch_assoc();
            $s = $conn->prepare("SELECT tracking_code,customer_id FROM shipments WHERE id=?");
            $s->bind_param('i', $row['shipment_id']); $s->execute();
            $ship = $s->get_result()->fetch_assoc();
            notify($ship['customer_id'], 'Shipment In Transit 🚛', "Your shipment {$ship['tracking_code']} is now on the way!", 'info', 'customer/track.php?code=' . $ship['tracking_code']);
        }
    }
}

$stmt = $conn->prepare("SELECT d.*, s.tracking_code, s.pickup_address, s.delivery_address,
    s.receiver_name, s.receiver_phone, v.plate_number, v.model
    FROM deliveries d
    JOIN shipments s ON d.shipment_id=s.id
    JOIN vehicles v ON d.vehicle_id=v.id
    WHERE d.driver_id=? ORDER BY d.assigned_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$deliveries = $stmt->get_result();
?>
<h2 class="page-title">🗺️ My Deliveries</h2>
<div class="card">
    <div class="card-header"><h3>All Assigned Deliveries</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Receiver</th><th>Pickup</th><th>Destination</th><th>Vehicle</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php $has = false; while ($d = $deliveries->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($d['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars($d['receiver_name']) ?><br><small style="color:#999"><?= htmlspecialchars($d['receiver_phone']) ?></small></td>
            <td><?= htmlspecialchars($d['pickup_address']) ?></td>
            <td><?= htmlspecialchars($d['delivery_address']) ?></td>
            <td><?= htmlspecialchars($d['plate_number']) ?><br><small style="color:#999"><?= htmlspecialchars($d['model']) ?></small></td>
            <td><span class="badge badge-<?= $d['status'] ?>"><?= ucfirst(str_replace('_',' ',$d['status'])) ?></span></td>
            <td>
                <?php if (!in_array($d['status'], ['delivered','failed'])): ?>
                <form method="post" style="display:flex;flex-direction:column;gap:5px;min-width:160px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="delivery_id" value="<?= $d['id'] ?>">
                    <select name="status" style="padding:4px 8px;border:1px solid #dee2e6;border-radius:4px;font-size:.82rem">
                        <option value="assigned"   <?= $d['status']==='assigned'   ?'selected':'' ?>>Assigned</option>
                        <option value="picked_up"  <?= $d['status']==='picked_up'  ?'selected':'' ?>>Picked Up</option>
                        <option value="in_transit" <?= $d['status']==='in_transit' ?'selected':'' ?>>In Transit</option>
                        <option value="delivered">Delivered</option>
                        <option value="failed">Failed</option>
                    </select>
                    <input type="text" name="notes" placeholder="Notes (optional)" style="padding:4px 8px;border:1px solid #dee2e6;border-radius:4px;font-size:.82rem">
                    <button name="update" class="btn btn-success btn-sm">Update</button>
                </form>
                <?php else: ?>
                <span style="color:#999;font-size:.8rem">
                    <?= $d['delivered_at'] ? date('d M Y', strtotime($d['delivered_at'])) : ucfirst($d['status']) ?>
                </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="7"><div class="empty-state"><div class="icon">🗺️</div><p>No deliveries assigned yet</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
