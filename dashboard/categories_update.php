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

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $categoryName = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($categoryName === '') {
        header("Location: categories_view.php?error=name_required");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO categories (CategoryName, Description) VALUES (?, ?)");
    $stmt->bind_param('ss', $categoryName, $description);
    if ($stmt->execute()) {
        audit_log('category_create', "Category {$categoryName} created", $uid);
        header("Location: categories_view.php?success=created");
    } else {
        header("Location: categories_view.php?error=create_failed");
    }
    $stmt->close();
} elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $categoryName = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0 || $categoryName === '') {
        header("Location: categories_view.php?error=invalid_data");
        exit;
    }

    $stmt = $conn->prepare("UPDATE categories SET CategoryName = ?, Description = ? WHERE CategoryID = ?");
    $stmt->bind_param('ssi', $categoryName, $description, $id);
    if ($stmt->execute()) {
        audit_log('category_update', "Category {$categoryName} updated", $uid);
        header("Location: categories_view.php?success=updated");
    } else {
        header("Location: categories_edit.php?id={$id}&error=update_failed");
    }
    $stmt->close();
} else {
    header("Location: categories_view.php");
}

exit;
