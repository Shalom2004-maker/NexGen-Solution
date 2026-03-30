<?php
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

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $serviceName = trim($_POST['service_name'] ?? '');
    $serviceTier = trim($_POST['service_tier'] ?? '');
    $hourlyRate = $_POST['hourly_rate'] ?? null;
    $categoryId = $_POST['category_id'] ?? null;

    if ($serviceName === '') {
        header("Location: services_view.php?error=name_required");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO services (ServiceName, ServiceTier, HourlyRate, CategoryID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssdi', $serviceName, $serviceTier, $hourlyRate, $categoryId);
    if ($stmt->execute()) {
        audit_log('service_create', "Service {$serviceName} created", $uid);
        header("Location: services_view.php?success=created");
    } else {
        header("Location: services_view.php?error=create_failed");
    }
    $stmt->close();
} elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $serviceName = trim($_POST['service_name'] ?? '');
    $serviceTier = trim($_POST['service_tier'] ?? '');
    $hourlyRate = $_POST['hourly_rate'] ?? null;
    $categoryId = $_POST['category_id'] ?? null;

    if ($id <= 0 || $serviceName === '') {
        header("Location: services_view.php?error=invalid_data");
        exit;
    }

    $stmt = $conn->prepare("UPDATE services SET ServiceName = ?, ServiceTier = ?, HourlyRate = ?, CategoryID = ? WHERE ServiceID = ?");
    $stmt->bind_param('ssdii', $serviceName, $serviceTier, $hourlyRate, $categoryId, $id);
    if ($stmt->execute()) {
        audit_log('service_update', "Service {$serviceName} updated", $uid);
        header("Location: services_view.php?success=updated");
    } else {
        header("Location: services_edit.php?id={$id}&error=update_failed");
    }
    $stmt->close();
} else {
    header("Location: services_view.php");
}

exit;
