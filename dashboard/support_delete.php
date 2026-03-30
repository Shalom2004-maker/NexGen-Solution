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

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: support_view.php?error=invalid_id");
    exit;
}

$stmt = $conn->prepare("DELETE FROM support WHERE ID = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    audit_log('support_delete', "Support record ID {$id} deleted", $uid);
    header("Location: support_view.php?success=deleted");
} else {
    header("Location: support_view.php?error=delete_failed");
}
$stmt->close();

exit;
