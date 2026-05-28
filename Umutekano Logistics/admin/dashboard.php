<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

$stats = [];
foreach ([
    'customers'  => "SELECT COUNT(*) FROM users WHERE role='customer'",
    'drivers'    => "SELECT COUNT(*) FROM users WHERE role='driver'",
    'vehicles'   => "SELECT COUNT(*) FROM vehicles",
    'shipments'  => "SELECT COUNT(*) FROM shipments",
    'delivered'  => "SELECT COUNT(*) FROM shipments WHERE status='delivered'",
    'pending'    => "SELECT COUNT(*) FROM shipments WHERE status='pending'",
    'in_transit' => "SELECT COUNT(*) FROM shipments WHERE status='in_transit'",
    'revenue'    => "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'",
    'unpaid'     => "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status IN ('pending','processing')",
] as $k => $q) {
    $stats[$k] = $conn->query($q)->fetch_row()[0];
}

$recent_shipments = $conn->query("SELECT s.tracking_code,u.full_name,s.delivery_address,s.status,s.created_at
    FROM shipments s JOIN users u ON s.customer_id=u.id ORDER BY s.created_at DESC LIMIT 6");

$recent_payments = $conn->query("SELECT p.amount,p.method,p.status,p.created_at,u.full_name,s.tracking_code
    FROM payments p JOIN users u ON p.customer_id=u.id JOIN shipments s ON p.shipment_id=s.id
    ORDER BY p.created_at DESC LIMIT 5");

// Monthly revenue for mini chart (last 6 months)
$monthly = $conn->query("SELECT DATE_FORMAT(created_at,'%b') AS month, COALESCE(SUM(amount),0) AS total
    FROM payments WHERE status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY created_at");
$chart_labels = $chart_data = [];
while ($m = $monthly->fetch_assoc()) {
    $chart_labels[] = $m['month'];
    $chart_data[]   = (float)$m['total'];
}
?>

<div class="dashboard-topbar">
    <div>
        <h2>Transport</h2>
        <p>Date · Time: <?= date('d M Y h:i A') ?> · Updated analytics for your fleet and shipments.</p>
    </div>
    <div class="dashboard-actions">
        <a href="reports.php" class="btn btn-outline">Filter</a>
        <a href="reports.php" class="btn btn-success">Export</a>
        <a href="shipments.php" class="btn btn-primary">Create New</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="num"><?= $stats['customers'] ?></div><div class="label">Customers</div></div>
    <div class="stat-card green"><div class="num"><?= $stats['drivers'] ?></div><div class="label">Drivers</div></div>
    <div class="stat-card orange"><div class="num"><?= $stats['vehicles'] ?></div><div class="label">Vehicles</div></div>
    <div class="stat-card"><div class="num"><?= $stats['shipments'] ?></div><div class="label">Total Shipments</div></div>
    <div class="stat-card green"><div class="num"><?= $stats['delivered'] ?></div><div class="label">Delivered</div></div>
    <div class="stat-card orange"><div class="num"><?= $stats['in_transit'] ?></div><div class="label">In Transit</div></div>
    <div class="stat-card"><div class="num"><?= $stats['pending'] ?></div><div class="label">Pending</div></div>
    <div class="stat-card green"><div class="num">RWF <?= number_format($stats['revenue']) ?></div><div class="label">Revenue Collected</div></div>
    <div class="stat-card red"><div class="num">RWF <?= number_format($stats['unpaid']) ?></div><div class="label">Outstanding</div></div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>⚡ Quick Actions</h3></div>
    <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="shipments.php" class="btn btn-primary">📦 View Shipments</a>
        <a href="deliveries.php" class="btn btn-success">🗺️ Assign Delivery</a>
        <a href="drivers.php" class="btn btn-warning">🧑✈️ Add Driver</a>
        <a href="vehicles.php" class="btn btn-outline">🚛 Add Vehicle</a>
        <a href="payments.php" class="btn btn-outline">💳 Manage Payments</a>
        <a href="reports.php" class="btn btn-outline">📈 View Reports</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

<!-- Recent Shipments -->
<div class="card">
    <div class="card-header"><h3>Recent Shipments</h3><a href="shipments.php" class="btn btn-primary btn-sm">View All</a></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Customer</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php while ($r = $recent_shipments->fetch_assoc()): ?>
        <tr>
            <td><strong><?= htmlspecialchars($r['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
            <td><?= date('d M', strtotime($r['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Recent Payments -->
<div class="card">
    <div class="card-header"><h3>Recent Payments</h3><a href="payments.php" class="btn btn-primary btn-sm">View All</a></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($p = $recent_payments->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($p['full_name']) ?></td>
            <td>RWF <?= number_format($p['amount']) ?></td>
            <td style="font-size:.78rem"><?= str_replace('_',' ',ucfirst($p['method'])) ?></td>
            <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

</div>

<!-- Revenue Chart -->
<?php if (count($chart_data) > 0): ?>
<div class="card" style="margin-top:24px">
    <div class="card-header"><h3>📈 Revenue – Last 6 Months</h3></div>
    <div class="card-body">
        <canvas id="revenueChart" height="80"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Revenue (RWF)',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(13,43,85,.7)',
            borderRadius: 6,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'RWF ' + v.toLocaleString() } } }
    }
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
