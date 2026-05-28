<?php
define('DB_HOST',   'localhost');
define('DB_USER',   'root');
define('DB_PASS',   '');
define('DB_NAME',   'umutekano_logistics');
define('SITE_NAME', 'Umutekano Logistics');

// ── BASE_URL ──────────────────────────────────────────────────────────────────
$baseProtocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host         = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName   = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir    = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$rootDir      = preg_replace('#/(admin|customer|driver)(/.*)?$#', '', $scriptDir);
if ($rootDir === '') {
    $rootDir = '/';
}
define('BASE_URL',  $baseProtocol . '://' . $host . ($rootDir === '/' ? '' : $rootDir) . '/');
define('ROOT_PATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// ── Mobile Money simulation ───────────────────────────────────────────────────
define('MTN_MOMO_ENABLED',     true);
define('AIRTEL_MONEY_ENABLED', true);

// ── Database connection ───────────────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:red">
        <h2>Database Connection Failed</h2>
        <p>' . htmlspecialchars($conn->connect_error) . '</p>
        <p>Make sure XAMPP MySQL is running and the database <strong>' . DB_NAME . '</strong> exists.</p>
    </div>');
}
$conn->set_charset('utf8mb4');

// ── CSRF helpers ──────────────────────────────────────────────────────────────
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px;color:red"><h2>403 – Invalid request token.</h2><p><a href="javascript:history.back()">Go back</a></p></div>');
    }
}

// ── Notification helpers ──────────────────────────────────────────────────────
function notify(int $user_id, string $title, string $message, string $type = 'info', string $link = ''): void {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id,title,message,type,link) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $user_id, $title, $message, $type, $link);
    $stmt->execute();
}

function unread_count(int $user_id): int {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

// ── Mobile Money simulation ───────────────────────────────────────────────────
function initiateMobileMoney(int $payment_id, string $provider, string $phone, float $amount): array {
    global $conn;
    $ref  = strtoupper($provider[0]) . 'M-' . date('YmdHis') . '-' . rand(1000, 9999);
    $stmt = $conn->prepare("INSERT INTO payment_transactions (payment_id,provider,phone,amount,reference,status,response_msg) VALUES (?,?,?,?,?,'pending',?)");
    $msg  = "Payment request sent to $phone";
    $stmt->bind_param('issdss', $payment_id, $provider, $phone, $amount, $ref, $msg);
    $stmt->execute();

    $upd = $conn->prepare("UPDATE payments SET status='processing', phone_number=?, reference=? WHERE id=?");
    $upd->bind_param('ssi', $phone, $ref, $payment_id);
    $upd->execute();

    return ['success' => true, 'reference' => $ref, 'message' => "Payment request sent to $phone. Enter your PIN to confirm."];
}

function confirmMobilePayment(int $payment_id, string $reference): array {
    global $conn;
    $s1 = $conn->prepare("UPDATE payment_transactions SET status='success', response_msg='Payment confirmed' WHERE reference=?");
    $s1->bind_param('s', $reference);
    $s1->execute();

    $s2 = $conn->prepare("UPDATE payments SET status='paid', paid_at=NOW() WHERE id=?");
    $s2->bind_param('i', $payment_id);
    $s2->execute();

    return ['success' => true, 'message' => 'Payment confirmed successfully!'];
}
