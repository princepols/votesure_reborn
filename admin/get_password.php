<?php
// get_password.php
require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$admin_id = (int) $_GET['id'] ?? 0;

// Get current admin info
$current_stmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
$current_stmt->execute([$_SESSION['admin_id']]);
$current_admin = $current_stmt->fetch(PDO::FETCH_ASSOC);
$is_default_admin = ($current_admin['username'] === 'admin');

// Get target admin info
$target_stmt = $pdo->prepare("SELECT id, username, plain_password FROM admins WHERE id = ?");
$target_stmt->execute([$admin_id]);
$target_admin = $target_stmt->fetch(PDO::FETCH_ASSOC);

if (!$target_admin) {
    echo json_encode(['success' => false, 'error' => 'Admin not found']);
    exit;
}

// Check permissions: default admin can see all, others can only see their own
if ($is_default_admin || $admin_id == $_SESSION['admin_id']) {
    echo json_encode([
        'success' => true,
        'password' => $target_admin['plain_password'] ?? 'Not set',
        'username' => $target_admin['username']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
}