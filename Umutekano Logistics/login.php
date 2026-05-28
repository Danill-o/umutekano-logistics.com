<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . $_SESSION['user_role'] . '/dashboard.php');
    exit;
}

$mode = $_GET['mode'] ?? 'customer';
$adminMode = $mode === 'admin';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Simple rate-limit: max 10 attempts per 15 min
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_time']     = $_SESSION['login_time'] ?? time();
    if (time() - $_SESSION['login_time'] > 900) {
        $_SESSION['login_attempts'] = 1;
        $_SESSION['login_time']     = time();
    }
    if ($_SESSION['login_attempts'] > 10) {
        $error = 'Too many login attempts. Please wait 15 minutes.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id,full_name,phone,avatar,password,role,status FROM users WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

        if ($u && $u['status'] === 'active' && password_verify($password, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']        = $u['id'];
            $_SESSION['user_name']      = $u['full_name'];
            $_SESSION['user_role']      = $u['role'];
            $_SESSION['user_phone']     = $u['phone'];
            $_SESSION['user_avatar']    = $u['avatar'];
            unset($_SESSION['login_attempts'], $_SESSION['login_time']);
            header('Location: ' . BASE_URL . $u['role'] . '/dashboard.php');
            exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login – <?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<script defer src="<?= BASE_URL ?>assets/js/app.js"></script>
</head>
<body>
<div class="auth-wrap animate-on-scroll in-view">
    <div class="auth-box animated-pop">
        <div class="auth-logo animate-on-scroll in-view">
            <div class="logo-icon">🛡️</div>
            <h1>Umutekano <span>Logistics</span></h1>
            <p>Sign in to your account</p>
        </div>
        <div class="auth-tabs">
            <a href="login.php?mode=customer" class="auth-tab<?= $adminMode ? '' : ' active' ?>">Customer</a>
            <a href="login.php?mode=admin" class="auth-tab<?= $adminMode ? ' active' : '' ?>">Admin</a>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="<?= $adminMode ? 'admin@umutekano.com' : 'you@example.com' ?>"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-row">
                <a href="fix_admin.php" class="link-secondary">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary">Sign In →</button>
        </form>
        <div class="auth-divider">or</div>
        <p style="text-align:center;font-size:.85rem;color:#666">
            No account? <a href="register.php" style="color:var(--blue);font-weight:600">Register here</a>
        </p>
    </div>
</div>
</body>
</html>
