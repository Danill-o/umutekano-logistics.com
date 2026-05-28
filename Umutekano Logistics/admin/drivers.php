<?php
require_once '../includes/header.php';
requireRole('admin');
global $conn;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['add'])) {
        $name  = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass) >= 6) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (full_name,email,phone,password,role) VALUES (?,?,?,?,'driver')");
            $stmt->bind_param('ssss', $name, $email, $phone, $hash);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                $ins = $conn->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                $ins->bind_param('i', $new_id);
                $ins->execute();
                $msg = '<div class="alert alert-success">✅ Driver added successfully.</div>';
            } else {
                $msg = '<div class="alert alert-danger">❌ Email already exists.</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">❌ Please fill all required fields (password min 6 chars).</div>';
        }
    } elseif (isset($_POST['toggle'])) {
        $id   = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE users SET status=IF(status='active','inactive','active') WHERE id=? AND role='driver'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
}

$drivers = $conn->query("SELECT id,full_name,email,phone,status,created_at FROM users WHERE role='driver' ORDER BY created_at DESC");
?>
<h2 class="page-title">🧑✈️ Drivers</h2>
<p class="page-note">Admin responsibilities: create driver profiles, keep driver status current, and ensure only active drivers are assigned deliveries.</p>
<?= $msg ?>
<div class="page-grid">
<div class="card">
    <div class="card-header"><h3>All Drivers</h3></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
        <tbody>
        <?php $has = false; while ($d = $drivers->fetch_assoc()): $has = true; ?>
        <tr>
            <td><?= htmlspecialchars($d['full_name']) ?></td>
            <td><?= htmlspecialchars($d['email']) ?></td>
            <td><?= htmlspecialchars($d['phone'] ?? '—') ?></td>
            <td><span class="badge badge-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
            <td><?= date('d M Y', strtotime($d['created_at'])) ?></td>
            <td>
                <form method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <button name="toggle" class="btn btn-warning btn-sm"><?= $d['status']==='active'?'Deactivate':'Activate' ?></button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="6"><div class="empty-state"><div class="icon">🧑✈️</div><p>No drivers yet</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<div class="form-card">
    <h3 style="margin-bottom:16px;color:#0d2b55">Add Driver</h3>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6"></div>
        <button name="add" class="btn btn-primary">Add Driver</button>
    </form>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
