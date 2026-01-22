<?php
include "../includes/auth.php";
// allow Employees, Project Leaders and Admins to view/manage tasks
allow(["Employee", "ProjectLeader", "Admin"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

// CSRF token removed: form submissions no longer use per-session CSRF tokens

// session_start();
$uid = (int)($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? '';

$q = trim($_GET['q'] ?? '');

// prepare user list and project list for selects
$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");

// Handle POST actions: only create here; other actions handled by separate endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST;

    $action = $posted['action'] ?? '';
    if ($action === 'create') {
        // only ProjectLeader or Admin can create tasks
        if (!in_array($role, ['ProjectLeader', 'Admin'], true)) {
            http_response_code(403);
            die('Forbidden');
        }
        $project = isset($posted['project']) ? (int)$posted['project'] : null;
        $assigned_to = isset($posted['assigned_to']) ? (int)$posted['assigned_to'] : null;
        $title = trim($posted['title'] ?? '');
        $desc = trim($posted['description'] ?? '');
        $deadline = !empty($posted['deadline']) ? $posted['deadline'] : null;

        if ($title === '') {
            $error = 'Title is required';
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks (project_id, assigned_to, created_by, title, description, status, deadline) VALUES (?,?,?,?,?, 'todo', ?)");
            $stmt->bind_param('iiisss', $project, $assigned_to, $uid, $title, $desc, $deadline);
            if ($stmt->execute()) {
                if (function_exists('audit_log')) audit_log('task_create', "Task {$title} created", $uid);
            }
            $stmt->close();
        }

        // after create, redirect to tasks_view.php
        header('Location: tasks_view.php');
        exit();
    }
    // other actions are handled by dedicated endpoints
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - NexGen Solution</title>
    <!-- Google Fonts Link -->
    <link rel=" preconnect" href="https://fonts.googleapis.com">
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h3 {
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .page-header p {
            color: lightslategray;
            margin: 0;
        }

        .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 100%;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #d4d4d4;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #337ccfe2;
            box-shadow: 0 0 0 0.2rem rgba(51, 124, 207, 0.25);
        }

        .alert {
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .btn-submit {
            background-color: #337ccfe2;
            color: white;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #2563a8;
            color: white;
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

        .tasks-table {
            width: 100%;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .tasks-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .tasks-table th {
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #d4d4d4;
            font-weight: 600;
        }

        .tasks-table td {
            padding: 1rem;
            border-bottom: 1px solid #d4d4d4;
        }

        .tasks-table tr:hover {
            background-color: #f9f9f9;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-todo {
            background-color: #ffc107;
            color: #000;
        }

        .status-in-progress {
            background-color: #17a2b8;
            color: white;
        }

        .status-done {
            background-color: #28a745;
            color: white;
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

            .form-container {
                padding: 1.5rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 1.5rem;
            }

            .page-header h3 {
                font-size: 1.5rem;
            }

            .tasks-table {
                overflow-x: auto;
            }

            .tasks-table table {
                min-width: 500px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
                padding-top: 3rem;
            }

            .form-container {
                padding: 1rem;
            }

            .page-header h3 {
                font-size: 1.25rem;
            }

            .form-label {
                font-size: 0.9rem;
            }

            .btn-submit {
                padding: 0.6rem 1.5rem;
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
                    <h3>My Tasks</h3>
                    <p>Manage your tasks and assignments</p>
                </div>
                <a href="tasks_view.php" class="btn btn-outline-secondary">View All Tasks</a>
            </div>

            <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                <div class="form-container mb-4">
                    <h5 class="mb-3">Create New Task</h5>
                    <form method="post" class="row gy-2">
                        <input type="hidden" name="action" value="create">

                        <div class="col-md-4">
                            <label class="form-label">Project (Optional)</label>
                            <select name="project" class="form-select">
                                <option value="">Select Project</option>
                                <?php while ($p = $projects->fetch_assoc()) : ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['project_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Select Assignee</option>
                                <?php
                                $users->data_seek(0);
                                while ($u = $users->fetch_assoc()) :
                                ?>
                                    <option value="<?= $u['id'] ?>">
                                        <?= htmlspecialchars($u['full_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Task Title *</label>
                            <input name="title" class="form-control" placeholder="Enter task title" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Enter task description"></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Create Task
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <h5 class="mb-3">Search Tasks</h5>
                <form method="get" action="tasks_view.php" class="row g-2">
                    <div class="col-md-8">
                        <input name="q" value="<?= htmlspecialchars($q) ?>" class="form-control"
                            placeholder="Search by title or description...">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
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