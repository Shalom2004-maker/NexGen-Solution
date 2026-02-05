<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "Admin"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tasks_view.php');
    exit();
}

$uid = (int)($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? '';

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on task_delete', $uid);
    die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
if ($action === 'delete') {
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    // fetch created_by
    $q = $conn->prepare("SELECT created_by FROM tasks WHERE id = ?");
    $q->bind_param('i', $taskId);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();
    if (!$r) {
        http_response_code(404);
        die('Not found');
    }
    $creator = (int)$r['created_by'];
    if (!in_array($role, ['ProjectLeader', 'Admin'], true) && $creator !== $uid) {
        http_response_code(403);
        die('Forbidden');
    }
    $d = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $d->bind_param('i', $taskId);
    if ($d->execute()) {
        if (function_exists('audit_log')) audit_log('task_delete', "Task {$taskId} deleted", $uid);
    }
    $d->close();
}

header('Location: tasks_view.php');
exit();
