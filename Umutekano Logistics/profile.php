<?php
require_once 'includes/header.php';
global $conn, $user;
$uid = $user['id'];
$msg = '';

$stmt = $conn->prepare("SELECT u.*, p.address, p.city, p.country, p.bio, p.license_no
    FROM users u LEFT JOIN user_profiles p ON u.id=p.user_id WHERE u.id=?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['update_profile'])) {
        $name    = trim($_POST['full_name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? 'Rwanda');
        $bio     = trim($_POST['bio'] ?? '');
        $license = trim($_POST['license_no'] ?? '');

        $s1 = $conn->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?");
        $s1->bind_param('ssi', $name, $phone, $uid);
        $s1->execute();

        $exists = $conn->prepare("SELECT id FROM user_profiles WHERE user_id=?");
        $exists->bind_param('i', $uid);
        $exists->execute();
        if ($exists->get_result()->num_rows) {
            $s2 = $conn->prepare("UPDATE user_profiles SET address=?,city=?,country=?,bio=?,license_no=? WHERE user_id=?");
            $s2->bind_param('sssssi', $address, $city, $country, $bio, $license, $uid);
        } else {
            $s2 = $conn->prepare("INSERT INTO user_profiles (user_id,address,city,country,bio,license_no) VALUES (?,?,?,?,?,?)");
            $s2->bind_param('isssss', $uid, $address, $city, $country, $bio, $license);
        }
        $s2->execute();

        if (!empty($_FILES['avatar']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed) && $_FILES['avatar']['size'] < 2097152) {
                $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], ROOT_PATH . 'assets/images/' . $filename)) {
                    $sa = $conn->prepare("UPDATE users SET avatar=? WHERE id=?");
                    $sa->bind_param('si', $filename, $uid);
                    $sa->execute();
                }
            }
        }

        refreshSession();
        $msg = '<div class="alert alert-success">✅ Profile updated successfully.</div>';
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

    } elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $sh = $conn->prepare("SELECT password FROM users WHERE id=?");
        $sh->bind_param('i', $uid);
        $sh->execute();
        $hash = $sh->get_result()->fetch_row()[0];

        if (!password_verify($current, $hash)) {
            $msg = '<div class="alert alert-danger">❌ Current password is incorrect.</div>';
        } elseif ($new !== $confirm) {
            $msg = '<div class="alert alert-danger">❌ New passwords do not match.</div>';
        } elseif (strlen($new) < 6) {
            $msg = '<div class="alert alert-danger">❌ Password must be at least 6 characters.</div>';
        } else {
            $newhash = password_hash($new, PASSWORD_BCRYPT);
            $sp = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $sp->bind_param('si', $newhash, $uid);
            $sp->execute();
            $msg = '<div class="alert alert-success">✅ Password changed successfully.</div>';
        }
    }
}

$initials = strtoupper(substr($u['full_name'], 0, 2));
?>

<h2 class="page-title">⚙️ My Profile</h2>
<?= $msg ?>

<div class="profile-header">
    <?php if ($u['avatar']): ?>
        <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($u['avatar']) ?>" class="profile-avatar" alt="">
    <?php else: ?>
        <div class="profile-avatar"><?= $initials ?></div>
    <?php endif; ?>
    <div class="profile-info">
        <h2><?= htmlspecialchars($u['full_name']) ?></h2>
        <p><?= htmlspecialchars($u['email']) ?> &middot; <?= htmlspecialchars($u['phone'] ?? '') ?></p>
        <span class="profile-badge"><?= ucfirst($u['role']) ?></span>
        <?php if ($u['city']): ?><span class="profile-badge">📍 <?= htmlspecialchars($u['city']) ?></span><?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

<div class="form-card">
    <h3 style="margin-bottom:20px;color:var(--blue)">Edit Profile</h3>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>City</label><input type="text" name="city" value="<?= htmlspecialchars($u['city'] ?? '') ?>"></div>
            <div class="form-group"><label>Country</label><input type="text" name="country" value="<?= htmlspecialchars($u['country'] ?? 'Rwanda') ?>"></div>
        </div>
        <div class="form-group"><label>Address</label><input type="text" name="address" value="<?= htmlspecialchars($u['address'] ?? '') ?>"></div>
        <div class="form-group"><label>Bio</label><textarea name="bio"><?= htmlspecialchars($u['bio'] ?? '') ?></textarea></div>
        <?php if ($u['role'] === 'driver'): ?>
        <div class="form-group"><label>Driver License No.</label><input type="text" name="license_no" value="<?= htmlspecialchars($u['license_no'] ?? '') ?>"></div>
        <?php endif; ?>
        <div class="form-group">
            <label>Profile Photo <span class="text-muted">(max 2MB)</span></label>
            <input type="file" name="avatar" accept="image/*">
        </div>
        <button name="update_profile" class="btn btn-primary">💾 Save Changes</button>
    </form>
</div>

<div class="form-card">
    <h3 style="margin-bottom:20px;color:var(--blue)">Change Password</h3>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="new_password" required minlength="6"></div>
        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
        <button name="change_password" class="btn btn-warning">🔒 Change Password</button>
    </form>

    <hr class="divider">
    <h3 style="margin-bottom:12px;color:var(--blue)">Account Info</h3>
    <table style="font-size:.88rem">
        <tr><td style="padding:6px 0;color:var(--gray);width:120px">Email</td><td><?= htmlspecialchars($u['email']) ?></td></tr>
        <tr><td style="padding:6px 0;color:var(--gray)">Role</td><td><?= ucfirst($u['role']) ?></td></tr>
        <tr><td style="padding:6px 0;color:var(--gray)">Status</td><td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td></tr>
        <tr><td style="padding:6px 0;color:var(--gray)">Member since</td><td><?= date('d M Y', strtotime($u['created_at'])) ?></td></tr>
    </table>
</div>

</div>

<?php require_once 'includes/footer.php'; ?>
