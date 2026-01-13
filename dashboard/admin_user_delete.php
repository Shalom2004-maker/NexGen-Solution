<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_user_view.php');
    exit();
}

$uid = (int)($_SESSION['uid'] ?? 0);

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on admin_user_delete', $uid);
    die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
if ($action === 'delete') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    // Prevent deleting yourself
    if ($userId === $uid) {
        http_response_code(400);
        die('You cannot delete your own account.');
    }

    // Check if user exists
    $checkStmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $checkStmt->bind_param('i', $userId);
    $checkStmt->execute();
    $user = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$user) {
        http_response_code(404);
        die('User not found.');
    }

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        if (function_exists('audit_log')) {
            audit_log('user_delete', "Deleted user {$user['email']} (ID: {$userId})", $uid);
        }
    } else {
        http_response_code(500);
        die('Failed to delete user.');
    }
    $stmt->close();
}

header('Location: admin_user_view.php');
exit();

