<?php
require_once '../includes/header.php';
requireRole('customer');
global $conn, $user;
$uid = $user['id'];

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['initiate_payment'])) {
        $pid      = (int)$_POST['payment_id'];
        $valid    = ['mtn_momo', 'airtel_money'];
        $provider = in_array($_POST['provider'] ?? '', $valid) ? $_POST['provider'] : '';
        $phone    = trim($_POST['phone'] ?? '');

        $stmt = $conn->prepare("SELECT * FROM payments WHERE id=? AND customer_id=? AND status='pending'");
        $stmt->bind_param('ii', $pid, $uid);
        $stmt->execute();
        $pay = $stmt->get_result()->fetch_assoc();

        if ($pay && $provider && $phone) {
            $result = initiateMobileMoney($pid, $provider, $phone, $pay['amount']);
            if ($result['success']) {
                notify($uid, 'Payment Initiated', "Payment of RWF " . number_format($pay['amount']) . " sent to $phone. Enter your PIN to confirm.", 'info', 'customer/payments.php');
                $msg = '<div class="alert alert-info">📱 ' . htmlspecialchars($result['message']) . ' <strong>Ref: ' . htmlspecialchars($result['reference']) . '</strong></div>';
            }
        }

    } elseif (isset($_POST['confirm_payment'])) {
        $pid = (int)$_POST['payment_id'];
        $ref = trim($_POST['reference'] ?? '');

        $stmt = $conn->prepare("SELECT * FROM payments WHERE id=? AND customer_id=? AND status='processing'");
        $stmt->bind_param('ii', $pid, $uid);
        $stmt->execute();
        $pay = $stmt->get_result()->fetch_assoc();

        if ($pay && $ref) {
            $result = confirmMobilePayment($pid, $ref);
            if ($result['success']) {
                notify($uid, 'Payment Successful! ✅', "Your payment of RWF " . number_format($pay['amount']) . " was confirmed. Ref: $ref", 'success', 'customer/payments.php');
                $admin = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch_row();
                if ($admin) notify($admin[0], 'Payment Received', "Customer payment of RWF " . number_format($pay['amount']) . " confirmed. Ref: $ref", 'success', 'admin/payments.php');
                $msg = '<div class="alert alert-success">✅ ' . htmlspecialchars($result['message']) . '</div>';
            }
        }
    }
}

$stmt = $conn->prepare("SELECT p.*,s.tracking_code,s.delivery_address FROM payments p
    JOIN shipments s ON p.shipment_id=s.id WHERE p.customer_id=? ORDER BY p.created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$payments = $stmt->get_result();

$s1 = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE customer_id=? AND status='paid'");
$s1->bind_param('i', $uid); $s1->execute();
$total_paid = $s1->get_result()->fetch_row()[0];

$s2 = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE customer_id=? AND status IN ('pending','processing')");
$s2->bind_param('i', $uid); $s2->execute();
$total_pending = $s2->get_result()->fetch_row()[0];

$s3 = $conn->prepare("SELECT COUNT(*) FROM payments WHERE customer_id=?");
$s3->bind_param('i', $uid); $s3->execute();
$total_count = $s3->get_result()->fetch_row()[0];

$s4 = $conn->prepare("SELECT p.*,s.tracking_code FROM payments p JOIN shipments s ON p.shipment_id=s.id
    WHERE p.customer_id=? AND p.status IN ('pending','processing') ORDER BY p.created_at DESC");
$s4->bind_param('i', $uid); $s4->execute();
$pending_pays_arr = $s4->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h2 class="page-title">💳 My Payments</h2>
<?= $msg ?>

<div class="stats-grid">
    <div class="stat-card green"><div class="num">RWF <?= number_format($total_paid) ?></div><div class="label">Total Paid</div></div>
    <div class="stat-card orange"><div class="num">RWF <?= number_format($total_pending) ?></div><div class="label">Outstanding</div></div>
    <div class="stat-card"><div class="num"><?= $total_count ?></div><div class="label">Total Transactions</div></div>
</div>

<?php if (count($pending_pays_arr) > 0): ?>
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>⚠️ Payments Due</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Destination</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($pending_pays_arr as $pp): ?>
        <tr>
            <td><strong><?= htmlspecialchars($pp['tracking_code']) ?></strong></td>
            <td><?= htmlspecialchars($pp['delivery_address']) ?></td>
            <td><strong>RWF <?= number_format($pp['amount']) ?></strong></td>
            <td><span class="badge badge-<?= $pp['status'] ?>"><?= ucfirst($pp['status']) ?></span></td>
            <td>
                <?php if ($pp['status'] === 'pending'): ?>
                <button class="btn btn-success btn-sm" onclick="openPayModal(<?= $pp['id'] ?>, '<?= htmlspecialchars($pp['tracking_code']) ?>', <?= $pp['amount'] ?>)">
                    📱 Pay Now
                </button>
                <?php elseif ($pp['status'] === 'processing'): ?>
                <button class="btn btn-warning btn-sm" onclick="openConfirmModal(<?= $pp['id'] ?>, '<?= htmlspecialchars($pp['reference']) ?>')">
                    ✅ Confirm PIN
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Payment History</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tracking</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php $has = false; while ($p = $payments->fetch_assoc()): $has = true; ?>
        <tr>
            <td><?= htmlspecialchars($p['tracking_code']) ?></td>
            <td>RWF <?= number_format($p['amount']) ?></td>
            <td><?php $ml = ['cash'=>'💵 Cash','mtn_momo'=>'📱 MTN MoMo','airtel_money'=>'📱 Airtel Money','bank_transfer'=>'🏦 Bank Transfer']; echo $ml[$p['method']] ?? ucfirst($p['method']); ?></td>
            <td><span style="font-size:.78rem;color:var(--gray)"><?= htmlspecialchars($p['reference'] ?: '—') ?></span></td>
            <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
            <td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="6"><div class="empty-state"><div class="icon">💳</div><p>No payment records yet</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- MOBILE MONEY PAYMENT MODAL -->
<div class="modal-overlay" id="payModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📱 Mobile Money Payment</h3>
            <button class="modal-close" onclick="closePayModal()">×</button>
        </div>
        <div class="pay-step active" id="step1">
            <div class="amount-display">
                <div class="label">Amount to Pay</div>
                <div class="amount" id="modalAmount">RWF 0</div>
                <div class="label" id="modalTracking"></div>
            </div>
            <p style="font-size:.85rem;color:var(--gray);margin-bottom:12px;text-align:center">Select your mobile money provider:</p>
            <div class="payment-methods">
                <div class="pay-method-btn" onclick="selectMethod('mtn_momo', this)">
                    <div class="pm-logo">📱</div>
                    <div class="pm-name" style="color:#f5a623;font-size:1rem">MTN MoMo</div>
                    <div style="font-size:.72rem;color:var(--gray)">MTN Mobile Money</div>
                </div>
                <div class="pay-method-btn" onclick="selectMethod('airtel_money', this)">
                    <div class="pm-logo">📲</div>
                    <div class="pm-name" style="color:#e40000;font-size:1rem">Airtel Money</div>
                    <div style="font-size:.72rem;color:var(--gray)">Airtel Money</div>
                </div>
            </div>
            <button class="btn btn-primary btn-block" onclick="goStep2()" id="nextBtn" disabled>Continue →</button>
        </div>
        <div class="pay-step" id="step2">
            <div class="amount-display">
                <div class="label">Amount</div>
                <div class="amount" id="modalAmount2">RWF 0</div>
            </div>
            <div id="methodLabel" style="text-align:center;font-weight:600;margin-bottom:16px;color:var(--blue)"></div>
            <div class="form-group">
                <label>Mobile Money Phone Number</label>
                <input type="tel" id="momoPhone" placeholder="e.g. 0781234567" style="font-size:1.1rem;text-align:center">
                <div class="form-hint">Enter the phone number registered with your mobile money account</div>
            </div>
            <div style="display:flex;gap:8px">
                <button class="btn btn-outline" onclick="goStep1()">← Back</button>
                <button class="btn btn-success" style="flex:1" onclick="submitPayment()">📤 Send Payment Request</button>
            </div>
        </div>
        <div class="pay-step" id="step3">
            <div class="processing-anim">
                <div class="spinner"></div>
                <p style="font-weight:600;color:var(--blue)">Sending payment request...</p>
                <p style="color:var(--gray);font-size:.85rem;margin-top:8px">Please wait while we contact your mobile money provider</p>
            </div>
        </div>
    </div>
</div>

<!-- CONFIRM PIN MODAL -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✅ Confirm Payment</h3>
            <button class="modal-close" onclick="document.getElementById('confirmModal').classList.remove('open')">×</button>
        </div>
        <div class="alert alert-info">
            A payment request was sent to your phone. Enter your mobile money PIN on your phone, then click <strong>Confirm</strong> below.
        </div>
        <div style="background:var(--light);border-radius:8px;padding:14px;margin-bottom:16px;text-align:center">
            <div style="font-size:.8rem;color:var(--gray)">Transaction Reference</div>
            <div style="font-weight:700;font-size:1rem;color:var(--blue)" id="confirmRef"></div>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_id" id="confirmPayId">
            <input type="hidden" name="reference"  id="confirmRefInput">
            <button name="confirm_payment" class="btn btn-success btn-block btn-lg">✅ I've Entered My PIN – Confirm</button>
        </form>
    </div>
</div>

<form method="post" id="payForm">
    <?= csrf_field() ?>
    <input type="hidden" name="initiate_payment" value="1">
    <input type="hidden" name="payment_id" id="formPayId">
    <input type="hidden" name="provider"   id="formProvider">
    <input type="hidden" name="phone"      id="formPhone">
</form>

<script>
let selectedMethod = '', currentPayId = 0, currentAmount = 0, currentTracking = '';

function openPayModal(payId, tracking, amount) {
    currentPayId = payId; currentAmount = amount; currentTracking = tracking;
    document.getElementById('modalAmount').textContent  = 'RWF ' + amount.toLocaleString();
    document.getElementById('modalAmount2').textContent = 'RWF ' + amount.toLocaleString();
    document.getElementById('modalTracking').textContent = 'Shipment: ' + tracking;
    document.getElementById('payModal').classList.add('open');
    goStep1();
}
function closePayModal() { document.getElementById('payModal').classList.remove('open'); selectedMethod = ''; }
function selectMethod(method, el) {
    selectedMethod = method;
    document.querySelectorAll('.pay-method-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('nextBtn').disabled = false;
    document.getElementById('methodLabel').textContent = method === 'mtn_momo' ? '📱 MTN Mobile Money' : '📲 Airtel Money';
}
function goStep1() { document.querySelectorAll('.pay-step').forEach(s => s.classList.remove('active')); document.getElementById('step1').classList.add('active'); }
function goStep2() {
    if (!selectedMethod) return;
    document.querySelectorAll('.pay-step').forEach(s => s.classList.remove('active'));
    document.getElementById('step2').classList.add('active');
    document.getElementById('momoPhone').focus();
}
function submitPayment() {
    const phone = document.getElementById('momoPhone').value.trim();
    if (!phone || phone.length < 9) { alert('Please enter a valid phone number'); return; }
    document.querySelectorAll('.pay-step').forEach(s => s.classList.remove('active'));
    document.getElementById('step3').classList.add('active');
    document.getElementById('formPayId').value    = currentPayId;
    document.getElementById('formProvider').value = selectedMethod;
    document.getElementById('formPhone').value    = phone;
    setTimeout(() => document.getElementById('payForm').submit(), 1800);
}
function openConfirmModal(payId, ref) {
    document.getElementById('confirmPayId').value    = payId;
    document.getElementById('confirmRefInput').value = ref;
    document.getElementById('confirmRef').textContent = ref;
    document.getElementById('confirmModal').classList.add('open');
}
</script>

<?php require_once '../includes/footer.php'; ?>
