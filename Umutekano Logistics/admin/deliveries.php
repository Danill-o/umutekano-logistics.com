<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    csrf_verify();
    $shipment_id = (int)$_POST['shipment_id'];
    $driver_id   = (int)$_POST['driver_id'];
    $vehicle_id  = (int)$_POST['vehicle_id'];

    if ($shipment_id && $driver_id && $vehicle_id) {
        $stmt = $conn->prepare("INSERT INTO deliveries (shipment_id,driver_id,vehicle_id) VALUES (?,?,?)");
        $stmt->bind_param('iii', $shipment_id, $driver_id, $vehicle_id);
        if ($stmt->execute()) {
            $upd1 = $conn->prepare("UPDATE shipments SET status='processing' WHERE id=?");
            $upd1->bind_param('i', $shipment_id);
            $upd1->execute();

            $upd2 = $conn->prepare("UPDATE vehicles SET status='on_delivery' WHERE id=?");
            $upd2->bind_param('i', $vehicle_id);
            $upd2->execute();

            $ship = $conn->prepare("SELECT tracking_code,customer_id FROM shipments WHERE id=?");
            $ship->bind_param('i', $shipment_id);
            $ship->execute();
            $s = $ship->get_result()->fetch_assoc();

            notify($driver_id, 'New Delivery Assigned 🚛', "You have been assigned delivery for shipment {$s['tracking_code']}.", 'info', 'driver/deliveries.php');
            notify($s['customer_id'], 'Shipment Being Processed 📦', "Your shipment {$s['tracking_code']} has been assigned to a driver.", 'success', 'customer/track.php?code=' . $s['tracking_code']);
            $msg = '<div class="alert alert-success">✅ Delivery assigned and driver notified.</div>';
        }
    }
}

$deliveries = $conn->query("SELECT d.*,s.tracking_code,s.delivery_address,u.full_name AS driver_name,v.plate_number
    FROM deliveries d
    JOIN shipments s ON d.shipment_id=s.id
    JOIN users u ON d.driver_id=u.id
    JOIN vehicles v ON d.vehicle_id=v.id
    ORDER BY d.assigned_at DESC");

$pending_shipments = $conn->query("SELECT id,tracking_code,delivery_address FROM shipments WHERE status='pending'");
$drivers           = $conn->query("SELECT id,full_name FROM users WHERE role='driver' AND status='active'");
$vehicles          = $conn->query("SELECT id,plate_number,model FROM vehicles WHERE status='available'");
?>
<h2 class="page-title">🗺️ Deliveries</h2>
<p class="page-note">Admin responsibilities: assign pending shipments, select drivers and vehicles, and monitor delivery progress.</p>
<?= $msg ?>

<div class="page-grid">
<div class="card">
    <div class="card-header"><h3>Assigned Deliveries</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Driver</th><th>Vehicle</th><th>Destination</th><th>Status</th><th>Assigned</th></tr></thead>
        <tbody>
        <?php $has = false; while ($d = $deliveries->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($d['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars($d['driver_name']) ?></td>
            <td><?= htmlspecialchars($d['plate_number']) ?></td>
            <td><?= htmlspecialchars($d['delivery_address']) ?></td>
            <td><span class="badge badge-<?= $d['status'] ?>"><?= ucfirst(str_replace('_',' ',$d['status'])) ?></span></td>
            <td><?= date('d M Y', strtotime($d['assigned_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="6"><div class="empty-state"><div class="icon">🗺️</div><p>No deliveries yet</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<div class="form-card">
    <h3 style="margin-bottom:16px;color:#0d2b55">Assign Delivery</h3>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Pending Shipment</label>
            <select name="shipment_id" required>
                <option value="">-- Select Shipment --</option>
                <?php while ($s = $pending_shipments->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['tracking_code']) ?> – <?= htmlspecialchars(substr($s['delivery_address'],0,30)) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Driver</label>
            <select name="driver_id" required>
                <option value="">-- Select Driver --</option>
                <?php while ($d = $drivers->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Vehicle</label>
            <select name="vehicle_id" required>
                <option value="">-- Select Vehicle --</option>
                <?php while ($v = $vehicles->fetch_assoc()): ?>
                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['plate_number']) ?> (<?= htmlspecialchars($v['model']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <button name="assign" class="btn btn-success">Assign Delivery</button>
    </form>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
