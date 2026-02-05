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
    if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on tasks_update', $uid);
    die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
if ($action === 'toggle_status') {
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $q = $conn->prepare("SELECT assigned_to, status FROM tasks WHERE id = ?");
    $q->bind_param('i', $taskId);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();
    if (!$r) {
        http_response_code(404);
        die('Not found');
    }
    $assigned = (int)$r['assigned_to'];
    $current = $r['status'];
    if (!in_array($role, ['ProjectLeader', 'Admin'], true) && $assigned !== $uid) {
        http_response_code(403);
        die('Forbidden');
    }
    // Toggle between 'done' and 'todo' to match schema values
    $newStatus = ($current === 'done') ? 'todo' : 'done';
    $u = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $u->bind_param('si', $newStatus, $taskId);
    if ($u->execute()) {
        if (function_exists('audit_log')) audit_log('task_status', "Task {$taskId} set to {$newStatus}", $uid);
    }
    $u->close();
} elseif ($action === 'update') {
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $project = isset($_POST['project']) && $_POST['project'] !== '' ? (int)$_POST['project'] : null;
    $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    if ($title === '') {
        http_response_code(400);
        die('Title is required');
    }

    // check existence and permission
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

    $u = $conn->prepare("UPDATE tasks SET project_id = ?, assigned_to = ?, title = ?, description = ?, deadline = ? WHERE id = ?");
    $u->bind_param('iisssi', $project, $assigned_to, $title, $description, $deadline, $taskId);
    if ($u->execute()) {
        if (function_exists('audit_log')) audit_log('task_update', "Task {$taskId} updated", $uid);
    }
    $u->close();
}

header('Location: tasks_view.php');
exit();
