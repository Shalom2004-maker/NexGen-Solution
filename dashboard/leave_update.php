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
    if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on leave_update', $uid);
    die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
if ($action === 'update') {
    $leaveId = isset($_POST['leave_id']) ? (int)$_POST['leave_id'] : 0;
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $leaveType = $_POST['leave_type'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $status = $_POST['status'] ?? 'pending';

    // Basic validation
    if (empty($startDate) || empty($endDate)) {
        http_response_code(400);
        die('Start and end dates are required.');
    }
    if (!in_array($leaveType, ['sick', 'annual', 'unpaid', 'personal', 'vacation'])) {
        http_response_code(400);
        die('Invalid leave type.');
    }
    if (strtotime($startDate) === false || strtotime($endDate) === false || strtotime($endDate) < strtotime($startDate)) {
        http_response_code(400);
        die('End date must be on or after the start date.');
    }
    if (empty($reason)) {
        http_response_code(400);
        die('Reason is required.');
    }

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

    // Permission check: Employee can only update their own pending requests
    if ($role === 'Employee') {
        if ($leave['user_id'] != $uid || $leave['status'] !== 'pending') {
            http_response_code(403);
            die('You can only edit your own pending leave requests.');
        }
        // Employees cannot change status
        $status = $leave['status'];
    } elseif ($role === 'ProjectLeader') {
        $allowedTransitions = [
            'pending' => ['pending', 'leader_approved', 'rejected'],
            'leader_approved' => ['leader_approved', 'rejected'],
            'rejected' => ['rejected'],
        ];
        if (!in_array($status, ['pending', 'leader_approved', 'rejected'], true)) {
            http_response_code(400);
            die('Project leaders cannot set HR approval.');
        }
        if ($leave['status'] === 'hr_approved') {
            http_response_code(403);
            die('HR-approved leave requests can only be changed by Admin.');
        }
        $validStatuses = $allowedTransitions[$leave['status']] ?? [];
        if (!in_array($status, $validStatuses, true)) {
            http_response_code(400);
            die('Invalid leader leave transition.');
        }
    } elseif ($role === 'HR') {
        $allowedTransitions = [
            'pending' => ['pending'],
            'leader_approved' => ['leader_approved', 'hr_approved', 'rejected'],
            'hr_approved' => ['hr_approved'],
            'rejected' => ['rejected'],
        ];
        if (!in_array($status, ['pending', 'leader_approved', 'hr_approved', 'rejected'], true)) {
            http_response_code(400);
            die('Invalid status.');
        }
        $validStatuses = $allowedTransitions[$leave['status']] ?? [];
        if (!in_array($status, $validStatuses, true)) {
            http_response_code(400);
            die('HR can only finalize leader-approved leave requests.');
        }
    } elseif ($role === 'Admin') {
        if (!in_array($status, ['pending', 'leader_approved', 'hr_approved', 'rejected'], true)) {
            http_response_code(400);
            die('Invalid status.');
        }
    } else {
        http_response_code(403);
        die('You do not have permission to update leave requests.');
    }

    $leaderId = (int)($leave['leader_id'] ?? 0);
    $hrId = (int)($leave['hr_id'] ?? 0);

    if ($role === 'ProjectLeader' && in_array($status, ['leader_approved', 'rejected'], true)) {
        $leaderId = $uid;
    }
    if ($role === 'HR' && in_array($status, ['hr_approved', 'rejected'], true)) {
        $hrId = $uid;
    }

    // Update leave request
    $stmt = $conn->prepare("UPDATE leave_requests SET start_date = ?, end_date = ?, leave_type = ?, reason = ?, status = ?, leader_id = NULLIF(?, 0), hr_id = NULLIF(?, 0) WHERE id = ?");
    $stmt->bind_param("sssssiii", $startDate, $endDate, $leaveType, $reason, $status, $leaderId, $hrId, $leaveId);

    if ($stmt->execute()) {
        if (function_exists('audit_log')) {
            audit_log('leave_update', "Updated leave request ID: {$leaveId}", $uid);
        }
    } else {
        http_response_code(500);
        die('Failed to update leave request.');
    }
    $stmt->close();
}

header('Location: leave_view.php');
exit();
