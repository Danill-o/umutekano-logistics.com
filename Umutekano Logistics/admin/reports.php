<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

// Validate date inputs — only accept YYYY-MM-DD format
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to']   ?? '') ? $_GET['to']   : date('Y-m-d');

$s1 = $conn->prepare("SELECT COUNT(*) FROM shipments WHERE DATE(created_at) BETWEEN ? AND ?");
$s1->bind_param('ss', $from, $to);
$s1->execute();
$total_shipments = $s1->get_result()->fetch_row()[0];

$s2 = $conn->prepare("SELECT COUNT(*) FROM shipments WHERE status='delivered' AND DATE(created_at) BETWEEN ? AND ?");
$s2->bind_param('ss', $from, $to);
$s2->execute();
$delivered = $s2->get_result()->fetch_row()[0];

$s3 = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND DATE(created_at) BETWEEN ? AND ?");
$s3->bind_param('ss', $from, $to);
$s3->execute();
$revenue = $s3->get_result()->fetch_row()[0];

$s4 = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='pending' AND DATE(created_at) BETWEEN ? AND ?");
$s4->bind_param('ss', $from, $to);
$s4->execute();
$pending_pay = $s4->get_result()->fetch_row()[0];

$s5 = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM shipments WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
$s5->bind_param('ss', $from, $to);
$s5->execute();
$by_status = $s5->get_result();

$top_drivers = $conn->query("SELECT u.full_name, COUNT(d.id) AS deliveries FROM deliveries d JOIN users u ON d.driver_id=u.id WHERE d.status='delivered' GROUP BY d.driver_id ORDER BY deliveries DESC LIMIT 5");

// Monthly revenue chart (last 6 months)
$monthly = $conn->query("SELECT DATE_FORMAT(created_at,'%b') AS month, COALESCE(SUM(amount),0) AS total
    FROM payments WHERE status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY created_at");
$chart_labels = $chart_data = [];
while ($m = $monthly->fetch_assoc()) {
    $chart_labels[] = $m['month'];
    $chart_data[]   = (float)$m['total'];
}
?>
<h2 class="page-title">📈 Reports & Analytics</h2>

<form method="get" style="display:flex;gap:12px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
    <div class="form-group" style="margin:0"><label>From</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></div>
    <div class="form-group" style="margin:0"><label>To</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></div>
    <button class="btn btn-primary">Filter</button>
</form>

<div class="stats-grid">
    <div class="stat-card"><div class="num"><?= $total_shipments ?></div><div class="label">Total Shipments</div></div>
    <div class="stat-card green"><div class="num"><?= $delivered ?></div><div class="label">Delivered</div></div>
    <div class="stat-card orange"><div class="num">RWF <?= number_format($revenue) ?></div><div class="label">Revenue Collected</div></div>
    <div class="stat-card gray"><div class="num">RWF <?= number_format($pending_pay) ?></div><div class="label">Pending Payments</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
<div class="card">
    <div class="card-header"><h3>Shipments by Status</h3></div>
    <table>
        <thead><tr><th>Status</th><th>Count</th></tr></thead>
        <tbody>
        <?php while ($r = $by_status->fetch_assoc()): ?>
        <tr>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
            <td><?= $r['cnt'] ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<div class="card">
    <div class="card-header"><h3>Top Drivers</h3></div>
    <table>
        <thead><tr><th>Driver</th><th>Deliveries</th></tr></thead>
        <tbody>
        <?php while ($r = $top_drivers->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><strong><?= $r['deliveries'] ?></strong></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</div>

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
