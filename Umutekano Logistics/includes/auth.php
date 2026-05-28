<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'     => (int)$_SESSION['user_id'],
        'name'   => $_SESSION['user_name'],
        'role'   => $_SESSION['user_role'],
        'phone'  => $_SESSION['user_phone']  ?? '',
        'avatar' => $_SESSION['user_avatar'] ?? '',
    ];
}

function refreshSession(): void {
    global $conn;
    $id   = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT full_name,phone,avatar FROM users WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $_SESSION['user_name']   = $row['full_name'];
        $_SESSION['user_phone']  = $row['phone'];
        $_SESSION['user_avatar'] = $row['avatar'];
    }
}

function generateTrackingCode(): string {
    return 'UL-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

function sanitize($conn, $val): string {
    return $conn->real_escape_string(trim((string)$val));
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
