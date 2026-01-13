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
    if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on inquiries_delete', $uid);
    die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
if ($action === 'delete') {
    $inquiryId = isset($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : 0;

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

    // Delete inquiry
    $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
    $stmt->bind_param('i', $inquiryId);
    if ($stmt->execute()) {
        if (function_exists('audit_log')) {
            audit_log('inquiry_delete', "Deleted inquiry ID: {$inquiryId}", $uid);
        }
    } else {
        http_response_code(500);
        die('Failed to delete inquiry.');
    }
    $stmt->close();
}

header('Location: inquiries_view.php');
exit();

