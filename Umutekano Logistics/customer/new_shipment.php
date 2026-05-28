<?php
require_once '../includes/header.php';
requireRole('customer');
global $conn;
$uid = $user['id'];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $code     = generateTrackingCode();
    $receiver = trim($_POST['receiver_name'] ?? '');
    $rphone   = trim($_POST['receiver_phone'] ?? '');
    $pickup   = trim($_POST['pickup_address'] ?? '');
    $delivery = trim($_POST['delivery_address'] ?? '');
    $weight   = (float)($_POST['weight_kg'] ?? 0);
    $desc     = trim($_POST['description'] ?? '');

    if ($receiver && $rphone && $pickup && $delivery) {
        $stmt = $conn->prepare("INSERT INTO shipments (tracking_code,customer_id,receiver_name,receiver_phone,pickup_address,delivery_address,weight_kg,description) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sissssds', $code, $uid, $receiver, $rphone, $pickup, $delivery, $weight, $desc);
        if ($stmt->execute()) {
            notify($uid, 'Shipment Created 📦', "Your shipment $code has been submitted and is pending processing.", 'success', 'customer/my_shipments.php');
            $msg = "<div class='alert alert-success'>✅ Shipment created! Tracking code: <strong>$code</strong></div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>❌ Please fill all required fields.</div>";
    }
}
?>
<h2 class="page-title">➕ New Shipment</h2>
<?= $msg ?>
<div class="form-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Receiver Name</label><input type="text" name="receiver_name" required></div>
            <div class="form-group"><label>Receiver Phone</label><input type="text" name="receiver_phone" required></div>
        </div>
        <div class="form-group"><label>Pickup Address</label><input type="text" name="pickup_address" required></div>
        <div class="form-group"><label>Delivery Address</label><input type="text" name="delivery_address" required></div>
        <div class="form-row">
            <div class="form-group"><label>Weight (kg)</label><input type="number" name="weight_kg" step="0.01" min="0.1"></div>
            <div class="form-group"><label>Description</label><input type="text" name="description" placeholder="e.g. Electronics, Clothes"></div>
        </div>
        <button type="submit" class="btn btn-primary">Submit Shipment</button>
    </form>
</div>
<?php require_once '../includes/footer.php'; ?>
