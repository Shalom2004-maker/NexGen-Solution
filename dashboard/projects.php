<?php
include "../includes/auth.php";
// allow Employees, Project Leaders and Admins to view/manage projects
allow(["Employee", "ProjectLeader", "Admin"]);
include "../includes/db.php";
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Oswald", sans-serif;
        }

        html,
        body {
            background-color: #ececece8;
            min-height: 100vh;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            background-color: #f5f5f5d2;
            padding: 2rem;
            overflow-y: auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h3 {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: lightslategray;
            margin: 0;
        }

        .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th {
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #d4d4d4;
            font-weight: 600;
        }

        .table-container td {
            padding: 0.75rem;
            border-bottom: 1px solid #d4d4d4;
        }

        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1040;
            background-color: #337ccfe2;
            color: white;
            border: none;
            padding: 0.6rem 0.8rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.25rem;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }

        .sidebar-overlay.show {
            display: block;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }

            .sidebar-toggle {
                display: block;
            }

            .main-content {
                padding: 1.5rem;
                padding-top: 3.5rem;
            }

            .table-container {
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
                padding-top: 3rem;
            }

            .page-header h3 {
                font-size: 1.25rem;
            }

            .table-container th,
            .table-container td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            .form-container {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button">
        <i class="bi bi-list"></i>
    </button>

    <div class="main-wrapper">
        <div id="sidebarContainer">
            <?php include "admin_siderbar.php"; ?>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h3>Projects Management</h3>
                    <p>Create and manage project details</p>
                </div>
            </div>

            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                <div class="form-container">
                    <h5><?= $edit ? 'Edit Project' : 'Create New Project' ?></h5>
                    <form method="post" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <?php if ($edit): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id']) ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="project_name" class="form-label">Project Name *</label>
                                <input type="text" id="project_name" name="project_name" class="form-control"
                                    placeholder="Enter project name" required
                                    value="<?= htmlspecialchars($edit['project_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="leader_id" class="form-label">Project Leader</label>
                                <select id="leader_id" name="leader_id" class="form-control">
                                    <option value="">Select Leader (optional)</option>
                                    <?php
                                    $users->data_seek(0);
                                    while ($u = $users->fetch_assoc()) : ?>
                                        <option value="<?= $u['id'] ?>"
                                            <?= (isset($edit['leader_id']) && $edit['leader_id'] == $u['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['full_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control"
                                    value="<?= htmlspecialchars($edit['start_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control"
                                    value="<?= htmlspecialchars($edit['end_date'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4"
                                placeholder="Enter project description"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?= $edit ? 'Update Project' : 'Create Project' ?>
                            </button>
                            <?php if ($edit): ?>
                                <a href="projects.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project Name</th>
                            <th>Description</th>
                            <th>Leader</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $res->fetch_assoc()) : ?>
                            <tr>
                                <td><?= htmlspecialchars($p['id']) ?></td>
                                <td><?= htmlspecialchars($p['project_name']) ?></td>
                                <td><?= htmlspecialchars(substr($p['description'], 0, 50)) ?><?= strlen($p['description']) > 50 ? '...' : '' ?></td>
                                <td><?= $p['leader_name'] ? htmlspecialchars($p['leader_name']) : '-' ?></td>
                                <td><?= htmlspecialchars($p['start_date'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($p['end_date'] ?? '-') ?></td>
                                <td>
                                    <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                                        <div class="d-flex gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="projects.php?edit_id=<?= $p['id'] ?>">Edit</a>
                                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this project?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const nexgenSidebar = document.getElementById('nexgenSidebar');

            if (sidebarToggleBtn && nexgenSidebar) {
                sidebarToggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    nexgenSidebar.classList.toggle('show');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.toggle('show');
                    }
                });
            }

            if (sidebarOverlay && nexgenSidebar) {
                sidebarOverlay.addEventListener('click', function() {
                    nexgenSidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            }

            if (nexgenSidebar) {
                document.querySelectorAll('.nexgen-sidebar-menu a').forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            nexgenSidebar.classList.remove('show');
                            if (sidebarOverlay) {
                                sidebarOverlay.classList.remove('show');
                            }
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>