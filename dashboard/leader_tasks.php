<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
include "../includes/logger.php";
// current user
$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
$projects = [];
$project_scope = 'owned';

if ($uid > 0) {
    $scope_stmt = $conn->prepare("SELECT id FROM projects WHERE leader_id = ? LIMIT 1");
    if ($scope_stmt) {
        $scope_stmt->bind_param('i', $uid);
        $scope_stmt->execute();
        $scope_stmt->store_result();
        $project_scope = $scope_stmt->num_rows > 0 ? 'owned' : 'all';
        $scope_stmt->close();
    }
}

// form feedback
$error = '';
$success = '';
// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
// process only on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        audit_log('csrf', 'Invalid CSRF token on leader_tasks', $_SESSION['uid'] ?? null);
        die('Invalid request');
    }

    $project_id = isset($_POST['project']) ? (int)$_POST['project'] : 0;
    $assigned_to = isset($_POST['user']) ? (int)$_POST['user'] : 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['desc'] ?? '');
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    if ($project_id <= 0 || $assigned_to <= 0 || $title === '') {
        $error = 'Please select a project, an employee, and enter a task title.';
    } else {
        // ensure project belongs to this leader
        if ($project_scope === 'all') {
            $pstmt = $conn->prepare("SELECT id FROM projects WHERE id = ? LIMIT 1");
            $pstmt->bind_param('i', $project_id);
        } else {
            $pstmt = $conn->prepare("SELECT id FROM projects WHERE id = ? AND leader_id = ? LIMIT 1");
            $pstmt->bind_param('ii', $project_id, $uid);
        }
        $pstmt->execute();
        $pstmt->store_result();
        $valid_project = $pstmt->num_rows > 0;
        $pstmt->close();

        // ensure user is an employee
        $ustmt = $conn->prepare("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND r.role_name = 'Employee' LIMIT 1");
        $ustmt->bind_param('i', $assigned_to);
        $ustmt->execute();
        $ustmt->store_result();
        $valid_user = $ustmt->num_rows > 0;
        $ustmt->close();

        if (!$valid_project) {
            $error = 'Invalid project selection.';
        } elseif (!$valid_user) {
            $error = 'Invalid employee selection.';
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks(project_id,assigned_to,created_by,title,description,deadline) 
                                  VALUES(?,?,?,?,?,?)");
            $stmt->bind_param("iiisss", $project_id, $assigned_to, $uid, $title, $description, $deadline);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                audit_log('task_create', "Task created for project {$project_id}", $_SESSION['uid'] ?? null);
                $success = 'Task created successfully.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                $_POST = [];
            } else {
                audit_log('task_create_failed', "Failed to create task for project {$project_id}", $_SESSION['uid'] ?? null);
                $error = 'Failed to create task.';
            }
            $stmt->close();
        }
    }
}

// Projects for leader select (prefer leader-owned projects, then fall back to available projects from schema data)
if ($uid > 0) {
    if ($project_scope === 'owned') {
        $pstmt = $conn->prepare("SELECT id, project_name FROM projects WHERE leader_id = ? ORDER BY project_name");
        $pstmt->bind_param('i', $uid);
        $pstmt->execute();
        $project_result = $pstmt->get_result();
        while ($project_result && ($project_row = $project_result->fetch_assoc())) {
            $projects[] = $project_row;
        }
        $pstmt->close();
    } else {
        $fallback_result = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");
        if ($fallback_result) {
            while ($project_row = $fallback_result->fetch_assoc()) {
                $projects[] = $project_row;
            }
        }
    }
}

// Employees for assignee select
$users = $conn->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Employee' ORDER BY u.full_name");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CSS -->
</head>

<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button">
        <i class="bi bi-list"></i>
    </button>

    <div class="main-wrapper">
        <div id="sidebarContainer">
            <?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="page-header">
                    <div>
                        <h3>Create Tasks</h3>
                        <p>Assign new tasks to team members</p>
                    </div>
                    <a href="leader.php" class="btn btn-sm btn-outline-primary">Back</a>
                </div>

                <div class="form-container mx-auto p-3">
                    <form method="post" class="p-4">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="project" class="form-label">Project ID *</label>
                            <select id="project" name="project" class="form-select" required>
                                <option value="">Select project</option>
                                <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"
                                    <?= (isset($_POST['project']) && (int)$_POST['project'] === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['project_name']) ?> (ID: <?= (int)$p['id'] ?>)
                                </option>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <option value="" disabled>No projects available</option>
                                <?php endif; ?>
                            </select>
                            <?php if ($project_scope === 'all' && !empty($projects)): ?>
                            <small class="text-muted d-block mt-2">
                                No projects are currently assigned to this leader account, so available projects are
                                shown from the database.
                            </small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="user" class="form-label">Employee User ID *</label>
                            <select id="user" name="user" class="form-control" required>
                                <option value="">Select employee</option>
                                <?php if ($users): ?>
                                <?php while ($u = $users->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>"
                                    <?= (isset($_POST['user']) && (int)$_POST['user'] === (int)$u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['full_name']) ?> (ID: <?= (int)$u['id'] ?>)
                                </option>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Task Title *</label>
                            <input type="text" id="title" name="title" class="form-control"
                                placeholder="Enter task title" required
                                value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="desc" class="form-label">Description</label>
                            <textarea id="desc" name="desc" class="form-control" rows="4"
                                placeholder="Task description"><?= htmlspecialchars($_POST['desc'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="deadline" class="form-label">Deadline</label>
                            <input type="date" id="deadline" name="deadline" class="form-control"
                                value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
                        </div>

                        <center>
                            <button type="submit" class="btn btn-success w-50 mt-3">Create Task</button>
                        </center>
                    </form>
                </div>
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