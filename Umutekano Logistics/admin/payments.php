<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['mark_paid'])) {
        $id   = (int)$_POST['id'];
        $stmt = $conn->prepare("SELECT customer_id, amount FROM payments WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $upd = $conn->prepare("UPDATE payments SET status='paid', paid_at=NOW() WHERE id=?");
        $upd->bind_param('i', $id);
        $upd->execute();
        if ($row) notify($row['customer_id'], 'Payment Confirmed ✅', 'Your payment of RWF ' . number_format($row['amount']) . ' has been confirmed by admin.', 'success', 'customer/payments.php');
        $msg = '<div class="alert alert-success">✅ Payment marked as paid.</div>';

    } elseif (isset($_POST['add'])) {
        $sid    = (int)$_POST['shipment_id'];
        $cid    = (int)$_POST['customer_id'];
        $amount = (float)$_POST['amount'];
        $valid  = ['cash','mtn_momo','airtel_money','bank_transfer'];
        $method = in_array($_POST['method'] ?? '', $valid) ? $_POST['method'] : 'cash';
        if ($sid && $cid && $amount > 0) {
            $stmt = $conn->prepare("INSERT INTO payments (shipment_id,customer_id,amount,method) VALUES (?,?,?,?)");
            $stmt->bind_param('iiis', $sid, $cid, $amount, $method);
            if ($stmt->execute()) {
                notify($cid, 'Payment Invoice Created', 'A payment of RWF ' . number_format($amount) . ' has been created for your shipment.', 'warning', 'customer/payments.php');
                $msg = '<div class="alert alert-success">✅ Payment record added.</div>';
            }
        }

    } elseif (isset($_POST['refund'])) {
        $id   = (int)$_POST['id'];
        $stmt = $conn->prepare("SELECT customer_id, amount FROM payments WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $upd = $conn->prepare("UPDATE payments SET status='refunded' WHERE id=?");
        $upd->bind_param('i', $id);
        $upd->execute();
        if ($row) notify($row['customer_id'], 'Payment Refunded', 'Your payment of RWF ' . number_format($row['amount']) . ' has been refunded.', 'info', 'customer/payments.php');
        $msg = '<div class="alert alert-info">↩ Payment marked as refunded.</div>';
    }
}

$valid_filters = ['pending','processing','paid','failed','refunded'];
$filter = in_array($_GET['status'] ?? '', $valid_filters) ? $_GET['status'] : '';
if ($filter) {
    $stmt = $conn->prepare("SELECT p.*,s.tracking_code,u.full_name,u.phone FROM payments p
        JOIN shipments s ON p.shipment_id=s.id JOIN users u ON p.customer_id=u.id
        WHERE p.status=? ORDER BY p.created_at DESC");
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $payments = $stmt->get_result();
} else {
    $payments = $conn->query("SELECT p.*,s.tracking_code,u.full_name,u.phone FROM payments p
        JOIN shipments s ON p.shipment_id=s.id JOIN users u ON p.customer_id=u.id ORDER BY p.created_at DESC");
}

$shipments        = $conn->query("SELECT s.id,s.tracking_code,s.customer_id,u.full_name FROM shipments s JOIN users u ON s.customer_id=u.id WHERE s.id NOT IN (SELECT shipment_id FROM payments)");
$total_paid       = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetch_row()[0];
$total_processing = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='processing'")->fetch_row()[0];
$total_pending    = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='pending'")->fetch_row()[0];
?>

<h2 class="page-title">💳 Payments</h2>
<?= $msg ?>

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card green"><div class="num">RWF <?= number_format($total_paid) ?></div><div class="label">Collected</div></div>
    <div class="stat-card orange"><div class="num">RWF <?= number_format($total_processing) ?></div><div class="label">Processing (MoMo)</div></div>
    <div class="stat-card"><div class="num">RWF <?= number_format($total_pending) ?></div><div class="label">Pending</div></div>
</div>

<div class="filter-bar">
    <?php foreach ([''=>'All','pending'=>'Pending','processing'=>'Processing','paid'=>'Paid','failed'=>'Failed','refunded'=>'Refunded'] as $v=>$l): ?>
    <a href="?status=<?= $v ?>" class="btn btn-sm <?= $filter===$v?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
<div class="card">
    <div class="card-header"><h3>Payment Records</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Customer</th><th>Amount</th><th>Method</th><th>Phone</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php $has = false; while ($p = $payments->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($p['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars($p['full_name']) ?></td>
            <td>RWF <?= number_format($p['amount']) ?></td>
            <td style="font-size:.78rem">
                <?php $icons = ['cash'=>'💵 Cash','mtn_momo'=>'📱 MTN','airtel_money'=>'📲 Airtel','bank_transfer'=>'🏦 Bank'];
                echo $icons[$p['method']] ?? ucfirst($p['method']); ?>
            </td>
            <td style="font-size:.78rem"><?= htmlspecialchars($p['phone_number'] ?: '—') ?></td>
            <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
            <td style="font-size:.78rem"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
            <td>
                <?php if (in_array($p['status'], ['pending','processing'])): ?>
                <form method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button name="mark_paid" class="btn btn-success btn-sm">✓ Paid</button>
                </form>
                <?php endif; ?>
                <?php if ($p['status'] === 'paid'): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Refund this payment?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button name="refund" class="btn btn-outline btn-sm">↩ Refund</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="8"><div class="empty-state"><div class="icon">💳</div><p>No records</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="form-card">
    <h3 style="margin-bottom:16px;color:var(--blue)">Add Payment Record</h3>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Shipment</label>
            <select name="shipment_id" id="shipSel" required onchange="setCustomer(this)">
                <option value="">-- Select Shipment --</option>
                <?php while ($s = $shipments->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>" data-cid="<?= $s['customer_id'] ?>"><?= htmlspecialchars($s['tracking_code']) ?> – <?= htmlspecialchars($s['full_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <input type="hidden" name="customer_id" id="custId">
        <div class="form-group"><label>Amount (RWF)</label><input type="number" name="amount" step="0.01" required min="1"></div>
        <div class="form-group">
            <label>Payment Method</label>
            <select name="method">
                <option value="cash">💵 Cash</option>
                <option value="mtn_momo">📱 MTN MoMo</option>
                <option value="airtel_money">📲 Airtel Money</option>
                <option value="bank_transfer">🏦 Bank Transfer</option>
            </select>
        </div>
        <button name="add" class="btn btn-primary btn-block">Add Payment</button>
    </form>
</div>
</div>

<script>
function setCustomer(sel) {
    document.getElementById('custId').value = sel.options[sel.selectedIndex].dataset.cid;
}
</script>
<?php require_once '../includes/footer.php'; ?>
