<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle'])) {
    csrf_verify();
    $id   = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE users SET status=IF(status='active','inactive','active') WHERE id=? AND role='customer'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

$customers = $conn->query("SELECT u.*, COUNT(s.id) AS total_shipments
    FROM users u LEFT JOIN shipments s ON u.id=s.customer_id
    WHERE u.role='customer' GROUP BY u.id ORDER BY u.created_at DESC");
?>
<h2 class="page-title">👥 Customers</h2>
<div class="card">
    <div class="card-header"><h3>All Customers</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Shipments</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
        <tbody>
        <?php $has = false; while ($c = $customers->fetch_assoc()): $has = true; ?>
        <tr>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
            <td><?= $c['total_shipments'] ?></td>
            <td><span class="badge badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
            <td>
                <form method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button name="toggle" class="btn btn-warning btn-sm"><?= $c['status']==='active'?'Deactivate':'Activate' ?></button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="7"><div class="empty-state"><div class="icon">👥</div><p>No customers yet</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
