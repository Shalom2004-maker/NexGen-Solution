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
$role_lc = strtolower(trim((string)$role));
$is_admin = $role_lc === 'admin';

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
    if (!$is_admin && $assigned !== $uid) {
        http_response_code(403);
        die('Forbidden');
    }
    // Move forward in workflow: todo -> in_progress -> done
    if ($current === 'todo') {
        $newStatus = 'in_progress';
    } elseif ($current === 'in_progress') {
        $newStatus = 'done';
    } else {
        $newStatus = 'done';
    }
    if ($newStatus !== $current) {
        $u = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $u->bind_param('si', $newStatus, $taskId);
        if ($u->execute()) {
            if (function_exists('audit_log')) audit_log('task_status', "Task {$taskId} set to {$newStatus}", $uid);
        }
        $u->close();
    }
} elseif ($action === 'set_status') {
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $requested = trim($_POST['status'] ?? '');
    $allowed = ['todo', 'in_progress', 'done'];
    if (!in_array($requested, $allowed, true)) {
        http_response_code(400);
        die('Invalid status');
    }
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
    $current = (string)($r['status'] ?? '');
    if (!$is_admin && $assigned !== $uid) {
        http_response_code(403);
        die('Forbidden');
    }
    if (!$is_admin) {
        $allowedTransitions = [
            'todo' => ['todo', 'in_progress'],
            'in_progress' => ['in_progress', 'done'],
            'done' => ['done'],
        ];
        $valid = $allowedTransitions[$current] ?? [$current];
        if (!in_array($requested, $valid, true)) {
            http_response_code(400);
            die('Invalid status transition');
        }
    }
    if ($requested !== $current) {
        $u = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $u->bind_param('si', $requested, $taskId);
        if ($u->execute()) {
            if (function_exists('audit_log')) audit_log('task_status', "Task {$taskId} set to {$requested}", $uid);
        }
        $u->close();
    }
} elseif ($action === 'update') {
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $project = trim($_POST['project'] ?? '');
    $project = $project !== '' ? (string)((int)$project) : '';
    $assigned_to = trim($_POST['assigned_to'] ?? '');
    $assigned_to = $assigned_to !== '' ? (string)((int)$assigned_to) : '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');

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

    $u = $conn->prepare("UPDATE tasks SET project_id = NULLIF(?, ''), assigned_to = NULLIF(?, ''), title = ?, description = ?, deadline = NULLIF(?, '') WHERE id = ?");
    $u->bind_param('sssssi', $project, $assigned_to, $title, $description, $deadline, $taskId);
    if ($u->execute()) {
        if (function_exists('audit_log')) audit_log('task_update', "Task {$taskId} updated", $uid);
    }
    $u->close();
}

$redirect = $_POST['redirect'] ?? '';
if ($redirect === '') {
    $redirect = $_SERVER['HTTP_REFERER'] ?? '';
}
if (!preg_match('/^tasks_(dashboard|view)\\.php(\\?.*)?$/', $redirect)) {
    $redirect = 'tasks_view.php';
}
header('Location: ' . $redirect);
exit();
