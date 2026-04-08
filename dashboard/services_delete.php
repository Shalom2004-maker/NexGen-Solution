n<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

$uid = (int)($_SESSION['uid'] ?? 0);

// Check CSRF
$postedToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
    header("Location: services_view.php?error=invalid_token");
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: services_view.php?error=invalid_id");
    exit;
}

// Check if service is used in support
$checkStmt = $conn->prepare("SELECT COUNT(*) as c FROM support WHERE ServiceID = ?");
$checkStmt->bind_param('i', $id);
$checkStmt->execute();
$supportCount = $checkStmt->get_result()->fetch_assoc()['c'];
$checkStmt->close();

if ($supportCount > 0) {
    header("Location: services_view.php?error=cannot_delete_in_use");
    exit;
}

$stmt = $conn->prepare("DELETE FROM services WHERE ServiceID = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    audit_log('service_delete', "Service ID {$id} deleted", $uid);
    header("Location: services_view.php?success=deleted");
} else {
    header("Location: services_view.php?error=delete_failed");
}
$stmt->close();

exit;