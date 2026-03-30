<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

$uid = (int)($_SESSION['uid'] ?? 0);

// Check CSRF
$postedToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
    header("Location: categories_view.php?error=invalid_token");
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: categories_view.php?error=invalid_id");
    exit;
}

// Check if category is used in services or solutions
$checkStmt = $conn->prepare("SELECT COUNT(*) as c FROM services WHERE CategoryID = ?");
$checkStmt->bind_param('i', $id);
$checkStmt->execute();
$servicesCount = $checkStmt->get_result()->fetch_assoc()['c'];
$checkStmt->close();

$checkStmt = $conn->prepare("SELECT COUNT(*) as c FROM solutions WHERE CategoryID = ?");
$checkStmt->bind_param('i', $id);
$checkStmt->execute();
$solutionsCount = $checkStmt->get_result()->fetch_assoc()['c'];
$checkStmt->close();

if ($servicesCount > 0 || $solutionsCount > 0) {
    header("Location: categories_view.php?error=cannot_delete_in_use");
    exit;
}

$stmt = $conn->prepare("DELETE FROM categories WHERE CategoryID = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    audit_log('category_delete', "Category ID {$id} deleted", $uid);
    header("Location: categories_view.php?success=deleted");
} else {
    header("Location: categories_view.php?error=delete_failed");
}
$stmt->close();

exit;
