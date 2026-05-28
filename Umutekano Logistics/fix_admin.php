<?php
/**
 * One-time admin password reset utility.
 * DELETE THIS FILE after use.
 */
require_once 'includes/config.php';

// Only allow from localhost
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Access denied.');
}

$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE email='admin@umutekano.com' AND role='admin'");
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $done = $stmt->affected_rows > 0;
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Fix Admin</title>
<style>body{font-family:sans-serif;padding:40px;max-width:500px;margin:auto}</style>
<script defer src="<?= BASE_URL ?>assets/js/app.js"></script>
</head><body>
<?php if ($done): ?>
    <h2 style="color:green">✅ Admin password reset to <code>admin123</code></h2>
    <p><strong>⚠️ Delete this file immediately!</strong></p>
    <a href="login.php" style="background:#0d2b55;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px">→ Go to Login</a>
<?php else: ?>
    <h2>Reset Admin Password</h2>
    <p>This will set the admin password to <strong>admin123</strong>.</p>
    <form method="post">
        <button name="confirm" style="background:#c0392b;color:#fff;padding:10px 20px;border:none;border-radius:5px;cursor:pointer">
            Reset Password
        </button>
        <a href="login.php" style="margin-left:12px">Cancel</a>
    </form>
<?php endif; ?>
</body></html>
