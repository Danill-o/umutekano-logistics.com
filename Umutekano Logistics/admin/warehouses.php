<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    csrf_verify();
    $name     = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    if ($name && $location) {
        $stmt = $conn->prepare("INSERT INTO warehouses (name,location,capacity) VALUES (?,?,?)");
        $stmt->bind_param('ssi', $name, $location, $capacity);
        $msg = $stmt->execute()
            ? '<div class="alert alert-success">✅ Warehouse added.</div>'
            : '<div class="alert alert-danger">❌ Error adding warehouse.</div>';
    }
}

$warehouses = $conn->query("SELECT w.*, COUNT(s.id) AS shipment_count FROM warehouses w LEFT JOIN shipments s ON w.id=s.warehouse_id GROUP BY w.id ORDER BY w.created_at DESC");
?>
<h2 class="page-title">🏭 Warehouses</h2>
<?= $msg ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
<div class="card">
    <div class="card-header"><h3>All Warehouses</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Location</th><th>Capacity</th><th>Shipments</th></tr></thead>
        <tbody>
        <?php $has = false; while ($w = $warehouses->fetch_assoc()): $has = true; ?>
        <tr>
            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
            <td><?= htmlspecialchars($w['location']) ?></td>
            <td><?= number_format($w['capacity']) ?> units</td>
            <td><?= $w['shipment_count'] ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="4"><div class="empty-state"><div class="icon">🏭</div><p>No warehouses yet</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<div class="form-card">
    <h3 style="margin-bottom:16px;color:#0d2b55">Add Warehouse</h3>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Name</label><input type="text" name="name" required placeholder="Kigali Central Warehouse"></div>
        <div class="form-group"><label>Location</label><input type="text" name="location" required placeholder="KG 123 St, Kigali"></div>
        <div class="form-group"><label>Capacity (units)</label><input type="number" name="capacity" placeholder="500"></div>
        <button name="add" class="btn btn-primary">Add Warehouse</button>
    </form>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
