<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['add'])) {
        $plate = trim($_POST['plate_number'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $cap   = (float)($_POST['capacity_kg'] ?? 0);
        if ($plate) {
            $stmt = $conn->prepare("INSERT INTO vehicles (plate_number,model,capacity_kg) VALUES (?,?,?)");
            $stmt->bind_param('ssd', $plate, $model, $cap);
            $msg = $stmt->execute()
                ? '<div class="alert alert-success">✅ Vehicle added.</div>'
                : '<div class="alert alert-danger">❌ Plate number already exists.</div>';
        }
    } elseif (isset($_POST['set_status'])) {
        $id    = (int)$_POST['id'];
        $valid = ['available', 'on_delivery', 'maintenance'];
        $st    = in_array($_POST['status'] ?? '', $valid) ? $_POST['status'] : 'available';
        $stmt  = $conn->prepare("UPDATE vehicles SET status=? WHERE id=?");
        $stmt->bind_param('si', $st, $id);
        $stmt->execute();
    }
}

$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY created_at DESC");
?>
<h2 class="page-title">🚛 Vehicles</h2>
<?= $msg ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
<div class="card">
    <div class="card-header"><h3>Fleet</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Plate</th><th>Model</th><th>Capacity (kg)</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php $has = false; while ($v = $vehicles->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($v['plate_number']) ?></strong></td>
            <td><?= htmlspecialchars($v['model'] ?? '—') ?></td>
            <td><?= number_format($v['capacity_kg']) ?></td>
            <td><span class="badge badge-<?= $v['status'] ?>"><?= ucfirst(str_replace('_',' ',$v['status'])) ?></span></td>
            <td>
                <form method="post" style="display:flex;gap:6px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                    <select name="status" style="padding:4px 8px;border:1px solid #dee2e6;border-radius:4px;font-size:.82rem">
                        <option value="available"   <?= $v['status']==='available'   ?'selected':'' ?>>Available</option>
                        <option value="on_delivery" <?= $v['status']==='on_delivery' ?'selected':'' ?>>On Delivery</option>
                        <option value="maintenance" <?= $v['status']==='maintenance' ?'selected':'' ?>>Maintenance</option>
                    </select>
                    <button name="set_status" class="btn btn-primary btn-sm">Set</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="5"><div class="empty-state"><div class="icon">🚛</div><p>No vehicles yet</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<div class="form-card">
    <h3 style="margin-bottom:16px;color:#0d2b55">Add Vehicle</h3>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Plate Number</label><input type="text" name="plate_number" required placeholder="RAA 000A"></div>
        <div class="form-group"><label>Model</label><input type="text" name="model" placeholder="Toyota Hilux"></div>
        <div class="form-group"><label>Capacity (kg)</label><input type="number" name="capacity_kg" step="0.01" placeholder="1000"></div>
        <button name="add" class="btn btn-primary">Add Vehicle</button>
    </form>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
