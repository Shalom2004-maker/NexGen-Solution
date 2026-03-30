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

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = $_POST['category_id'] ?? null;
    $dateCreated = $_POST['date_created'] ?? '';
    $isActive = (int)($_POST['is_active'] ?? 1);

    if ($title === '' || $dateCreated === '') {
        header("Location: solutions_view.php?error=required_fields");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO solutions (Title, Description, CategoryID, DateCreated, IsActive) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssisi', $title, $description, $categoryId, $dateCreated, $isActive);
    if ($stmt->execute()) {
        audit_log('solution_create', "Solution {$title} created", $uid);
        header("Location: solutions_view.php?success=created");
    } else {
        header("Location: solutions_view.php?error=create_failed");
    }
    $stmt->close();
} elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = $_POST['category_id'] ?? null;
    $dateCreated = $_POST['date_created'] ?? '';
    $isActive = (int)($_POST['is_active'] ?? 1);

    if ($id <= 0 || $title === '' || $dateCreated === '') {
        header("Location: solutions_view.php?error=invalid_data");
        exit;
    }

    $stmt = $conn->prepare("UPDATE solutions SET Title = ?, Description = ?, CategoryID = ?, DateCreated = ?, IsActive = ? WHERE SolutionID = ?");
    $stmt->bind_param('ssisii', $title, $description, $categoryId, $dateCreated, $isActive, $id);
    if ($stmt->execute()) {
        audit_log('solution_update', "Solution {$title} updated", $uid);
        header("Location: solutions_view.php?success=updated");
    } else {
        header("Location: solutions_edit.php?id={$id}&error=update_failed");
    }
    $stmt->close();
} else {
    header("Location: solutions_view.php");
}

exit;