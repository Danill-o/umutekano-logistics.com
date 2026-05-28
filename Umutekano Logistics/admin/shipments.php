<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    csrf_verify();
    $id     = (int)$_POST['id'];
    $valid  = ['pending', 'processing', 'in_transit', 'delivered', 'cancelled'];
    $status = in_array($_POST['status'] ?? '', $valid) ? $_POST['status'] : 'pending';
    $stmt   = $conn->prepare("UPDATE shipments SET status=? WHERE id=?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
}

$filter = in_array($_GET['status'] ?? '', ['pending','processing','in_transit','delivered','cancelled']) ? $_GET['status'] : '';
if ($filter) {
    $stmt = $conn->prepare("SELECT s.*,u.full_name FROM shipments s JOIN users u ON s.customer_id=u.id WHERE s.status=? ORDER BY s.created_at DESC");
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $shipments = $stmt->get_result();
} else {
    $shipments = $conn->query("SELECT s.*,u.full_name FROM shipments s JOIN users u ON s.customer_id=u.id ORDER BY s.created_at DESC");
}
?>
<h2 class="page-title">📦 Shipments</h2>

<div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach ([''=>'All','pending'=>'Pending','processing'=>'Processing','in_transit'=>'In Transit','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $s=>$l): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s?'btn-primary':'btn-warning' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header"><h3>Shipments List</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Customer</th><th>Receiver</th><th>Destination</th><th>Weight</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php $has = false; while ($s = $shipments->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($s['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars($s['full_name']) ?></td>
            <td><?= htmlspecialchars($s['receiver_name']) ?></td>
            <td><?= htmlspecialchars($s['delivery_address']) ?></td>
            <td><?= $s['weight_kg'] ?> kg</td>
            <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst(str_replace('_',' ',$s['status'])) ?></span></td>
            <td>
                <form method="post" style="display:flex;gap:6px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <select name="status" style="padding:4px 8px;border:1px solid #dee2e6;border-radius:4px;font-size:.82rem">
                        <?php foreach (['pending','processing','in_transit','delivered','cancelled'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $s['status']===$opt?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$opt)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button name="update_status" class="btn btn-primary btn-sm">Save</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="7"><div class="empty-state"><div class="icon">📦</div><p>No shipments found</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
