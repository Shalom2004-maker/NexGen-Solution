<?php
include "../includes/auth.php";
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if (!isset($_GET['id'])) {
    header('Location: tasks_view.php');
    exit();
}

$tid = (int)$_GET['id'];
$uid = (int)($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? '';

$q = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$q->bind_param('i', $tid);
$q->execute();
$task = $q->get_result()->fetch_assoc();
$q->close();
if (!$task) {
    header('Location: tasks_view.php');
    exit();
}

// permission to edit: ProjectLeader/Admin or creator
if (!in_array($role, ['ProjectLeader', 'Admin'], true) && (int)$task['created_by'] !== $uid) {
    http_response_code(403);
    die('Forbidden');
}

$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Task</title>
    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    <!-- Local Bootstrap CSS Link -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="/js/bootstrap.bundle.min.js"></script>

</head>

<body class="container py-4">
    <div class="main-wrapper">
        <div id="sidebarContainer">
            <?php include "admin_siderbar.php"; ?>
        </div>
        <h3>Edit Task</h3>
        <form method="post" action="tasks_update.php" class="row g-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id']) ?>">

            <div class="col-md-4">
                <label>Project</label>
                <select name="project" class="form-control">
                    <option value="">(none)</option>
                    <?php while ($p = $projects->fetch_assoc()) : ?>
                    <option value="<?= $p['id'] ?>" <?= ($task['project_id'] == $p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['project_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label>Assignee</label>
                <select name="assigned_to" class="form-control">
                    <option value="">(unassigned)</option>
                    <?php while ($u = $users->fetch_assoc()) : ?>
                    <option value="<?= $u['id'] ?>" <?= ($task['assigned_to'] == $u['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label>Title</label>
                <input name="title" class="form-control" required value="<?= htmlspecialchars($task['title']) ?>">
            </div>

            <div class="col-12">
                <label>Description</label>
                <textarea name="description"
                    class="form-control"><?= htmlspecialchars($task['description']) ?></textarea>
            </div>

            <div class="col-md-4">
                <label>Deadline</label>
                <input type="date" name="deadline" class="form-control"
                    value="<?= htmlspecialchars($task['deadline']) ?>">
            </div>

            <div class="col-12 text-end">
                <button class="btn btn-primary">Save</button>
                <a href="tasks_view.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>