<?php
include "../includes/auth.php";
// allow Employees, Project Leaders and Admins to view/manage projects
allow(["Employee", "ProjectLeader", "Admin"]);
include "../includes/db.php";
include "../includes/header.php";
require_once __DIR__ . "/../includes/logger.php";

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? '';

// Handle POST actions: create, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST;
    $token = $posted['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on projects', $uid);
        die('Invalid CSRF token');
    }

    $action = $posted['action'] ?? '';

    // Only ProjectLeader/Admin can create/update/delete
    if (!in_array($role, ['ProjectLeader', 'Admin'], true)) {
        http_response_code(403);
        die('Forbidden');
    }

    if ($action === 'create') {
        $name = trim($posted['project_name'] ?? '');
        $desc = trim($posted['description'] ?? '');
        $leader = isset($posted['leader_id']) && $posted['leader_id'] !== '' ? (int)$posted['leader_id'] : null;
        $start = !empty($posted['start_date']) ? $posted['start_date'] : null;
        $end = !empty($posted['end_date']) ? $posted['end_date'] : null;

        if ($name === '') {
            $error = 'Project name is required';
        } else {
            $stmt = $conn->prepare("INSERT INTO projects (project_name, description, leader_id, start_date, end_date) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssiss', $name, $desc, $leader, $start, $end);
            if ($stmt->execute()) {
                if (function_exists('audit_log')) audit_log('project_create', "Project {$name} created", $uid);
            } else {
                $error = 'Failed to create project';
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $id = isset($posted['id']) ? (int)$posted['id'] : 0;
        $name = trim($posted['project_name'] ?? '');
        $desc = trim($posted['description'] ?? '');
        $leader = isset($posted['leader_id']) && $posted['leader_id'] !== '' ? (int)$posted['leader_id'] : null;
        $start = !empty($posted['start_date']) ? $posted['start_date'] : null;
        $end = !empty($posted['end_date']) ? $posted['end_date'] : null;

        if ($name === '') {
            $error = 'Project name is required';
        } else {
            $u = $conn->prepare("UPDATE projects SET project_name = ?, description = ?, leader_id = ?, start_date = ?, end_date = ? WHERE id = ?");
            $u->bind_param('ssissi', $name, $desc, $leader, $start, $end, $id);
            if ($u->execute()) {
                if (function_exists('audit_log')) audit_log('project_update', "Project {$id} updated", $uid);
            } else {
                $error = 'Failed to update project';
            }
            $u->close();
        }
    } elseif ($action === 'delete') {
        $id = isset($posted['id']) ? (int)$posted['id'] : 0;
        $d = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $d->bind_param('i', $id);
        if ($d->execute()) {
            if (function_exists('audit_log')) audit_log('project_delete', "Project {$id} deleted", $uid);
        } else {
            $error = 'Failed to delete project';
        }
        $d->close();
    }

    // redirect back to avoid resubmission
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// optional edit load
$edit = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $q = $conn->prepare("SELECT id, project_name, description, leader_id, start_date, end_date FROM projects WHERE id = ?");
    $q->bind_param('i', $eid);
    $q->execute();
    $edit = $q->get_result()->fetch_assoc();
    $q->close();
}

// list projects with leader name
$res = $conn->query("SELECT p.id, p.project_name, p.description, p.leader_id, p.start_date, p.end_date, u.full_name AS leader_name FROM projects p LEFT JOIN users u ON p.leader_id = u.id ORDER BY p.id DESC");

// fetch users for leader select
$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">
    <h3>Projects</h3>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?= $edit ? 'Edit Project' : 'Create Project' ?></h5>
                <form method="post" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <?php if ($edit): ?><input type="hidden" name="action" value="update"><input type="hidden" name="id"
                            value="<?= htmlspecialchars($edit['id']) ?>"><?php else: ?><input type="hidden" name="action"
                            value="create"><?php endif; ?>
                    <div class="col-md-6">
                        <input name="project_name" class="form-control" placeholder="Project Name" required
                            value="<?= htmlspecialchars($edit['project_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <select name="leader_id" class="form-control">
                            <option value="">Leader (optional)</option>
                            <?php while ($u = $users->fetch_assoc()) : ?>
                                <option value="<?= $u['id'] ?>"
                                    <?= (isset($edit['leader_id']) && $edit['leader_id'] == $u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['full_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="start_date" class="form-control"
                            value="<?= htmlspecialchars($edit['start_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="end_date" class="form-control"
                            value="<?= htmlspecialchars($edit['end_date'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <textarea name="description" class="form-control"
                            placeholder="Description"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-primary"><?= $edit ? 'Update' : 'Create' ?></button>
                        <?php if ($edit): ?><a href="projects.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>id</th>
                <th>project_name</th>
                <th>description</th>
                <th>leader</th>
                <th>start_date</th>
                <th>end_date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($p = $res->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($p['id']) ?></td>
                    <td><?= htmlspecialchars($p['project_name']) ?></td>
                    <td><?= htmlspecialchars($p['description']) ?></td>
                    <td><?= $p['leader_name'] ? htmlspecialchars($p['leader_name']) . ' (' . htmlspecialchars($p['leader_id']) . ')' : htmlspecialchars($p['leader_id']) ?>
                    </td>
                    <td><?= htmlspecialchars($p['start_date']) ?></td>
                    <td><?= htmlspecialchars($p['end_date']) ?></td>
                    <td>
                        <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                            <a class="btn btn-sm btn-outline-secondary" href="projects.php?edit_id=<?= $p['id'] ?>">Edit</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete project?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php include "../includes/footer.php"; ?>
</body>

</html>