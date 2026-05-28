<?php
require_once '../includes/header.php';
requireRole('customer');
global $conn, $user;
$uid = $user['id'];

$shipment = null;
$delivery = null;
$code = sanitize($conn, $_GET['code'] ?? $_GET['tracking_code'] ?? '');

if ($code) {
    $stmt = $conn->prepare("SELECT * FROM shipments WHERE tracking_code=? AND customer_id=?");
    $stmt->bind_param('si', $code, $uid);
    $stmt->execute();
    $shipment = $stmt->get_result()->fetch_assoc();

    if ($shipment) {
        $sid = $shipment['id'];
        $delivery = $conn->query("SELECT d.*,u.full_name AS driver_name,v.plate_number FROM deliveries d JOIN users u ON d.driver_id=u.id JOIN vehicles v ON d.vehicle_id=v.id WHERE d.shipment_id=$sid ORDER BY d.assigned_at DESC LIMIT 1")->fetch_assoc();
    }
}

$steps = ['pending'=>1,'processing'=>2,'in_transit'=>3,'delivered'=>4,'cancelled'=>4];
$current_step = $shipment ? ($steps[$shipment['status']] ?? 1) : 0;
?>
<h2 class="page-title">📍 Track Package</h2>

<div class="form-card" style="max-width:500px;margin-bottom:24px">
    <form method="get" style="display:flex;gap:10px">
        <input type="text" name="code" value="<?= htmlspecialchars($code) ?>" placeholder="Enter tracking code e.g. UL-XXXXXXXX" style="flex:1;padding:9px 12px;border:1px solid #dee2e6;border-radius:5px">
        <button class="btn btn-primary">Track</button>
    </form>
</div>

<?php if ($code && !$shipment): ?>
<div class="alert alert-danger">Tracking code not found or does not belong to your account.</div>
<?php elseif ($shipment): ?>

<div class="card" style="max-width:700px">
    <div class="card-header"><h3>Tracking: <?= $shipment['tracking_code'] ?></h3></div>
    <div style="padding:24px">

        <?php if ($shipment['status'] !== 'cancelled'): ?>
        <div class="track-steps">
            <?php
            $labels = ['Pending','Processing','In Transit','Delivered'];
            foreach ($labels as $i => $label):
                $done = $current_step > $i;
            ?>
            <div class="track-step <?= $done ? 'done' : '' ?>">
                <div class="dot"><?= $done ? '✓' : ($i+1) ?></div>
                <div class="step-label"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-danger">This shipment has been cancelled.</div>
        <?php endif; ?>

        <table style="margin-top:16px">
            <tr><td style="padding:6px 12px;color:#666;width:160px">Receiver</td><td><?= htmlspecialchars($shipment['receiver_name']) ?> – <?= $shipment['receiver_phone'] ?></td></tr>
            <tr><td style="padding:6px 12px;color:#666">Pickup</td><td><?= htmlspecialchars($shipment['pickup_address']) ?></td></tr>
            <tr><td style="padding:6px 12px;color:#666">Destination</td><td><?= htmlspecialchars($shipment['delivery_address']) ?></td></tr>
            <tr><td style="padding:6px 12px;color:#666">Weight</td><td><?= $shipment['weight_kg'] ?> kg</td></tr>
            <tr><td style="padding:6px 12px;color:#666">Status</td><td><span class="badge badge-<?= $shipment['status'] ?>"><?= ucfirst(str_replace('_',' ',$shipment['status'])) ?></span></td></tr>
            <?php if ($delivery): ?>
            <tr><td style="padding:6px 12px;color:#666">Driver</td><td><?= htmlspecialchars($delivery['driver_name']) ?></td></tr>
            <tr><td style="padding:6px 12px;color:#666">Vehicle</td><td><?= $delivery['plate_number'] ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
