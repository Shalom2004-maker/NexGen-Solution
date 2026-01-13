<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inquiries_view.php');
    exit();
}

$uid = (int)($_SESSION['uid'] ?? 0);

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on inquiries_update', $uid);
    die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
if ($action === 'update') {
    $inquiryId = isset($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $company = trim($_POST['company'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $status = $_POST['status'] ?? 'new';

    // Basic validation
    if ($name === '') {
        http_response_code(400);
        die('Name is required.');
    }
    if (!$email) {
        http_response_code(400);
        die('Valid email is required.');
    }
    if ($message === '') {
        http_response_code(400);
        die('Message is required.');
    }
    if (!in_array($status, ['new', 'replied', 'closed'])) {
        http_response_code(400);
        die('Invalid status.');
    }

    // Check if inquiry exists
    $checkStmt = $conn->prepare("SELECT id FROM inquiries WHERE id = ?");
    $checkStmt->bind_param('i', $inquiryId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows !== 1) {
        $checkStmt->close();
        http_response_code(404);
        die('Inquiry not found.');
    }
    $checkStmt->close();

    // Update inquiry
    $stmt = $conn->prepare("UPDATE inquiries SET name = ?, email = ?, company = ?, message = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $email, $company, $message, $status, $inquiryId);

    if ($stmt->execute()) {
        if (function_exists('audit_log')) {
            audit_log('inquiry_update', "Updated inquiry ID: {$inquiryId}", $uid);
        }
    } else {
        http_response_code(500);
        die('Failed to update inquiry.');
    }
    $stmt->close();
}

header('Location: inquiries_view.php');
exit();

