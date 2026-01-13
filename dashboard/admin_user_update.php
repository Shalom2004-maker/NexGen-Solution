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
    if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on admin_user_update', $uid);
    die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
if ($action === 'update') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $pass = $_POST['pass'] ?? '';
    $role = isset($_POST['role']) ? (int)$_POST['role'] : 0;
    $status = $_POST['status'] ?? 'active';

    // Basic validation
    if ($name === '' || strlen($name) < 2) {
        http_response_code(400);
        die('Enter a valid name.');
    }
    if (!$email) {
        http_response_code(400);
        die('Enter a valid email address.');
    }
    if ($role <= 0) {
        http_response_code(400);
        die('Select a valid role.');
    }
    if (!in_array($status, ['active', 'disabled'])) {
        http_response_code(400);
        die('Invalid status.');
    }

    // Check if user exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->bind_param('i', $userId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows !== 1) {
        $checkStmt->close();
        http_response_code(404);
        die('User not found.');
    }
    $checkStmt->close();

    // Check duplicate email (excluding current user)
    $emailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailStmt->bind_param('si', $email, $userId);
    $emailStmt->execute();
    if ($emailStmt->get_result()->num_rows > 0) {
        $emailStmt->close();
        http_response_code(400);
        die('A user with that email already exists.');
    }
    $emailStmt->close();

    // Check role exists
    $roleStmt = $conn->prepare("SELECT id FROM roles WHERE id = ?");
    $roleStmt->bind_param('i', $role);
    $roleStmt->execute();
    if ($roleStmt->get_result()->num_rows !== 1) {
        $roleStmt->close();
        http_response_code(400);
        die('Selected role does not exist.');
    }
    $roleStmt->close();

    // Update user
    if (!empty($pass) && strlen($pass) >= 6) {
        // Update with password
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password_hash = ?, role_id = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssisi", $name, $email, $hash, $role, $status, $userId);
    } else {
        // Update without password
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role_id = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $name, $email, $role, $status, $userId);
    }

    if ($stmt->execute()) {
        if (function_exists('audit_log')) {
            audit_log('user_update', "Updated user {$email} (ID: {$userId})", $uid);
        }
    } else {
        http_response_code(500);
        die('Failed to update user.');
    }
    $stmt->close();
}

header('Location: admin_user_view.php');
exit();

