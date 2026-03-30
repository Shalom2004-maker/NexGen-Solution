<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

$uid = (int)($_SESSION['uid'] ?? 0);

$postedToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
    header("Location: solutions_view.php?error=invalid_token");
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: solutions_view.php?error=invalid_id");
    exit;
}

$checkStmt = $conn->prepare("SELECT COUNT(*) as c FROM support WHERE SolutionID = ?");
$checkStmt->bind_param('i', $id);
$checkStmt->execute();
$supportCount = $checkStmt->get_result()->fetch_assoc()['c'];
$checkStmt->close();

if ($supportCount > 0) {
    header("Location: solutions_view.php?error=cannot_delete_in_use");
    exit;
}

$stmt = $conn->prepare("DELETE FROM solutions WHERE SolutionID = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    audit_log('solution_delete', "Solution ID {$id} deleted", $uid);
    header("Location: solutions_view.php?success=deleted");
} else {
    header("Location: solutions_view.php?error=delete_failed");
}
$stmt->close();

exit;
