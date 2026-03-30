<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

$uid = (int)($_SESSION['uid'] ?? 0);

$postedToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
    header("Location: support_view.php?error=invalid_token");
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $subject = trim($_POST['subject'] ?? '');
    $status = trim($_POST['status'] ?? 'Open');
    $priority = (int)($_POST['priority'] ?? 3);
    $solutionId = $_POST['solution_id'] ?? null;
    $serviceId = $_POST['service_id'] ?? null;

    if ($subject === '') {
        header("Location: support_view.php?error=subject_required");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO support (Subject, Status, Priority, SolutionID, ServiceID) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssiii', $subject, $status, $priority, $solutionId, $serviceId);
    if ($stmt->execute()) {
        audit_log('support_create', "Support record {$subject} created", $uid);
        header("Location: support_view.php?success=created");
    } else {
        header("Location: support_view.php?error=create_failed");
    }
    $stmt->close();
} elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $status = trim($_POST['status'] ?? 'Open');
    $priority = (int)($_POST['priority'] ?? 3);
    $solutionId = $_POST['solution_id'] ?? null;
    $serviceId = $_POST['service_id'] ?? null;

    if ($id <= 0 || $subject === '') {
        header("Location: support_view.php?error=invalid_data");
        exit;
    }

    $stmt = $conn->prepare("UPDATE support SET Subject = ?, Status = ?, Priority = ?, SolutionID = ?, ServiceID = ? WHERE ID = ?");
    $stmt->bind_param('ssiiii', $subject, $status, $priority, $solutionId, $serviceId, $id);
    if ($stmt->execute()) {
        audit_log('support_update', "Support record {$subject} updated", $uid);
        header("Location: support_view.php?success=updated");
    } else {
        header("Location: support_edit.php?id={$id}&error=update_failed");
    }
    $stmt->close();
} else {
    header("Location: support_view.php");
}

exit;
