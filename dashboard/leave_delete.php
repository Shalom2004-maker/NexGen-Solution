<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "HR", "Admin"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: leave_view.php');
    exit();
}

$uid = (int)($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? '';

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on leave_delete', $uid);
    die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
if ($action === 'delete') {
    $leaveId = isset($_POST['leave_id']) ? (int)$_POST['leave_id'] : 0;

    // Check if leave request exists and get employee info
    $checkStmt = $conn->prepare("SELECT l.*, e.user_id 
                                  FROM leave_requests l 
                                  JOIN employees e ON l.employee_id = e.id 
                                  WHERE l.id = ?");
    $checkStmt->bind_param('i', $leaveId);
    $checkStmt->execute();
    $leave = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$leave) {
        http_response_code(404);
        die('Leave request not found.');
    }

    // Permission check: Only employees can delete their own pending requests, or Admin/HR can delete any
    if ($role === 'Employee') {
        if ($leave['user_id'] != $uid || $leave['status'] !== 'pending') {
            http_response_code(403);
            die('You can only cancel your own pending leave requests.');
        }
    } elseif (!in_array($role, ['Admin', 'HR'])) {
        http_response_code(403);
        die('You do not have permission to delete leave requests.');
    }

    // Delete leave request
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
    $stmt->bind_param('i', $leaveId);
    if ($stmt->execute()) {
        if (function_exists('audit_log')) {
            audit_log('leave_delete', "Deleted leave request ID: {$leaveId}", $uid);
        }
    } else {
        http_response_code(500);
        die('Failed to delete leave request.');
    }
    $stmt->close();
}

header('Location: leave_view.php');
exit();

