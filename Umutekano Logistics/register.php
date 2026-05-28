<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . $_SESSION['user_role'] . '/dashboard.php');
    exit;
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name    = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $role    = in_array($_POST['role'] ?? '', ['customer', 'driver']) ? $_POST['role'] : 'customer';
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($name) < 2) {
        $error = 'Full name is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (full_name,email,phone,password,role) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss', $name, $email, $phone, $hash, $role);
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $ins = $conn->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
            $ins->bind_param('i', $new_id);
            $ins->execute();
            notify($new_id, 'Welcome to Umutekano Logistics! 🎉',
                "Hi $name, your account has been created. Start by creating your first shipment.",
                'success', $role . '/dashboard.php');
            $success = 'Account created successfully! <a href="login.php" style="font-weight:700">Login now →</a>';
        } else {
            $error = 'Email already registered. <a href="login.php">Login instead?</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register – <?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<script defer src="<?= BASE_URL ?>assets/js/app.js"></script>
</head>
<body>
<div class="auth-wrap animate-on-scroll in-view">
    <div class="auth-box animated-pop" style="max-width:480px">
        <div class="auth-logo animate-on-scroll in-view">
            <div class="logo-icon">🛡️</div>
            <h1>Umutekano <span>Logistics</span></h1>
            <p>Create your account</p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger">❌ <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if (!$success): ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="John Doe" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="+250 780 000 000" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Register as</label>
                <select name="role">
                    <option value="customer" <?= ($_POST['role'] ?? '') === 'customer' ? 'selected' : '' ?>>👤 Customer</option>
                    <option value="driver"   <?= ($_POST['role'] ?? '') === 'driver'   ? 'selected' : '' ?>>🧑✈️ Driver</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Create Account →</button>
        </form>
        <?php endif; ?>
        <div class="auth-divider">or</div>
        <p style="text-align:center;font-size:.85rem;color:#666">
            Already have an account? <a href="login.php" style="color:var(--blue);font-weight:600">Sign in</a>
        </p>
    </div>
</div>
</body>
</html>
