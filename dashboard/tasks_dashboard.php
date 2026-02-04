<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";
include "../includes/logger.php";

// Get user ID
$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;

// Initialize form variables
$error = '';
$success = '';

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// Process form submission (Create Task)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        audit_log('csrf', 'Invalid CSRF token on tasks_dashboard', $_SESSION['uid'] ?? null);
        $error = 'Invalid request';
    } else {
        $project_id = isset($_POST['project']) && $_POST['project'] !== '' ? (int)$_POST['project'] : null;
        $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : $uid;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

        if ($title === '') {
            $error = 'Task title is required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks (project_id, assigned_to, created_by, title, description, status, deadline) VALUES (?,?,?,?,?, 'todo', ?)");
            $stmt->bind_param("iiisss", $project_id, $assigned_to, $uid, $title, $description, $deadline);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                audit_log('task_create', "Task created by user {$uid}", $_SESSION['uid'] ?? null);
                $success = 'Task created successfully!';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                $_POST = [];
            } else {
                audit_log('task_create_failed', "Failed to create task by user {$uid}", $_SESSION['uid'] ?? null);
                $error = "Failed to create task.";
            }
            $stmt->close();
        }
    }
}

// Fetch task statistics
$all_tasks_count = 0;
$pending_count = 0;
$in_progress_count = 0;
$completed_count = 0;

// All tasks count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ?");
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $all_tasks_count = (int)($row['count'] ?? 0);
    $stmt->close();
}

// Pending (todo) count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ? AND status = 'todo'");
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $pending_count = (int)($row['count'] ?? 0);
    $stmt->close();
}

// In Progress count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ? AND status = 'in_progress'");
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $in_progress_count = (int)($row['count'] ?? 0);
    $stmt->close();
}

// Completed count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ? AND status = 'done'");
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $completed_count = (int)($row['count'] ?? 0);
    $stmt->close();
}

// Search
$search = trim($_GET['q'] ?? '');

// Get filter status from URL
$filterStatus = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filters = ['all', 'todo', 'in_progress', 'done'];
$filterStatus = in_array($filterStatus, $filters) ? $filterStatus : 'all';

// Projects for create form
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");
$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

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

    <!-- CSS -->
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Sora", sans-serif;
    }

    html,
    body {
        background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
        color: #1f2937;
        min-height: 100vh;
    }

    .main-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        background-color: transparent;
        padding-top: 2rem;
        padding-left: 18rem;
        padding-right: 2.5rem;
        padding-bottom: 2rem;
        overflow-x: hidden;
        width: 75%;
    }

    .dashboard-shell {
        position: relative;
        background: radial-gradient(1200px 400px at 20% -10%, rgba(30, 64, 175, 0.12), transparent 60%),
            radial-gradient(800px 300px at 90% 10%, rgba(14, 116, 144, 0.12), transparent 60%);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .page-header h2 {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .page-header p {
        color: #5b6777;
        font-size: 0.95rem;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    /* Metric Cards */
    .metric-card {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1.4rem;
        margin-bottom: 1.5rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .metric-card:hover {
        transform: translateY(-3px);
        border-color: rgba(37, 99, 235, 0.4);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 70px;
        height: 70px;
        background: rgba(37, 99, 235, 0.08);
        border-radius: 20px;
        transform: translate(18px, -20px);
    }

    .metric-icon {
        font-size: 1.6rem;
        color: #1d4ed8;
        margin-bottom: 0.6rem;
    }

    .metric-label {
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 600;
        margin-bottom: 0.4rem;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    /* Filter Buttons */
    .filter-buttons {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .filter-btn {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.4);
        padding: 0.5rem 1.1rem;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        color: #475569;
        transition: all 0.12s ease;
        cursor: pointer;
        border-radius: 999px;
    }

    .filter-btn.active {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        border-color: transparent;
        color: white;
    }

    .filter-btn:hover {
        border-color: rgba(37, 99, 235, 0.6);
        color: #1d4ed8;
    }

    /* Section Title */
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 1.25rem;
        color: #0f172a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-title a {
        font-size: 0.85rem;
        color: #1d4ed8;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .section-title a:hover {
        color: #0f172a;
    }

    /* Task Cards */
    .task-card {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.15s ease;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .task-card:hover {
        border-color: rgba(37, 99, 235, 0.4);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        transform: translateX(2px);
    }

    .task-icon-box {
        width: 50px;
        height: 50px;
        background: rgba(37, 99, 235, 0.12);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .task-icon-box i {
        font-size: 1.5rem;
        color: #1d4ed8;
    }

    .task-content {
        flex: 1;
    }

    .task-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .task-title {
        font-weight: 600;
        color: #0f172a;
    }

    .task-description {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .task-meta {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 0.75rem;
    }

    .task-meta-item {
        display: inline-block;
        margin-right: 1.5rem;
    }

    .task-badges {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .task-project {
        display: inline-block;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #1d4ed8;
        color: white;
    }

    .task-status {
        display: inline-block;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .task-status.todo {
        background-color: #fbbf24;
        color: white;
    }

    .task-status.in_progress {
        background-color: #3b82f6;
        color: white;
    }

    .task-status.done {
        background-color: #10b981;
        color: white;
    }

    /* Empty State */
    .empty-state {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 3rem 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .empty-state i {
        font-size: 3rem;
        color: #cbd5f5;
        margin-bottom: 1rem;
        display: block;
    }

    .empty-state p {
        color: #64748b;
        font-size: 0.95rem;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        border: none;
        color: white;
        padding: 0.6rem 1.4rem;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.12s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
    }

    .btn-primary-custom:hover {
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(29, 78, 216, 0.3);
    }

    /* Modal Styles */
    .modal-content {
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.4);
        box-shadow: 0 30px 50px rgba(15, 23, 42, 0.2);
    }

    .modal-header {
        border-bottom: 1px solid rgba(148, 163, 184, 0.3);
        background: linear-gradient(135deg, rgba(29, 78, 216, 0.1), rgba(14, 116, 144, 0.08));
    }

    .modal-title {
        font-weight: 700;
        color: #0f172a;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .form-control,
    .form-select {
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 12px;
        padding: 0.75rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #1d4ed8;
        box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.15);
    }

    .form-label {
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.5rem;
    }

    .modal-footer {
        border-top: 1px solid rgba(148, 163, 184, 0.3);
        padding: 1rem;
    }

    .btn-primary {
        background-color: #1d4ed8;
        border-color: #1d4ed8;
    }

    .btn-primary:hover {
        background-color: #1e40af;
        border-color: #1e40af;
    }

    .btn-outline-secondary {
        color: #6c757d;
        border-color: #6c757d;
    }

    .btn-outline-secondary:hover {
        background-color: #6c757d;
        border-color: #6c757d;
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

    @media (max-width: 768px) {
        .main-wrapper {
            flex-direction: column;
        }

        .sidebar-toggle {
            display: block;
        }

        .main-content {
            padding: 1.25rem;
            width: 100%;
            padding-top: 3.5rem;
            padding-left: 1.25rem;
        }

        .dashboard-shell {
            padding: 1rem;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .page-header h2 {
            font-size: 1.6rem;
        }

        .metric-value {
            font-size: 1.6rem;
        }

        .task-card {
            flex-direction: column;
            gap: 0.75rem;
        }

        .task-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-buttons {
            flex-direction: column;
        }

        .filter-btn {
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
        }

        .page-header h2 {
            font-size: 1.35rem;
        }

        .metric-card {
            padding: 1rem;
        }

        .metric-value {
            font-size: 1.5rem;
        }

        .metric-label {
            font-size: 0.75rem;
        }

        .empty-state {
            padding: 2rem 1rem;
        }

        .empty-state i {
            font-size: 2rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn-primary-custom {
            width: 100%;
            text-align: center;
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
            <?php include "employee_sidebar.php"; ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="page-header">
                    <div>
                        <h2>Tasks</h2>
                        <p>Manage and track your team's tasks</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="button" class="btn-primary-custom">
                            <a href="tasks_view.php" class="text-white text-decoration-none">
                                <i class=" bi bi-eye"></i> &nbsp; View Tasks
                            </a>
                        </button>
                        <button type="button" class="btn-primary-custom" data-bs-toggle="modal"
                            data-bs-target="#taskCreateModal">
                            <i class="bi bi-plus-circle"></i> &nbsp; Create Task
                        </button>

                        <!-- Create Task Modal -->
                        <div class="modal fade" id="taskCreateModal" tabindex="-1"
                            aria-labelledby="taskCreateModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header border-bottom">
                                        <h1 class="modal-title fs-5" id="taskCreateModalLabel">
                                            <i class="bi bi-plus-circle"></i> &nbsp; Create New Task
                                        </h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if (!empty($error)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($success)): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                        <?php endif; ?>

                                        <form method="POST" action="">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                            <div class="mb-3">
                                                <label for="project" class="form-label">Project (Optional)</label>
                                                <select class="form-select" id="project" name="project">
                                                    <option value="">Select Project</option>
                                                    <?php if ($projects): ?>
                                                    <?php while ($p = $projects->fetch_assoc()): ?>
                                                    <option value="<?= $p['id'] ?>"
                                                        <?= (isset($_POST['project']) && (int)$_POST['project'] === (int)$p['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($p['project_name']) ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="assigned_to" class="form-label">Assign To</label>
                                                <select class="form-select" id="assigned_to" name="assigned_to">
                                                    <option value="">Select Assignee</option>
                                                    <?php if ($users): ?>
                                                    <?php while ($u = $users->fetch_assoc()): ?>
                                                    <option value="<?= $u['id'] ?>"
                                                        <?= (isset($_POST['assigned_to']) && (int)$_POST['assigned_to'] === (int)$u['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($u['full_name']) ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="title" class="form-label">Task Title <span
                                                        style="color: #ef4444;">*</span></label>
                                                <input type="text" class="form-control" id="title" name="title" required
                                                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                                    placeholder="Enter task title">
                                            </div>

                                            <div class="mb-3">
                                                <label for="deadline" class="form-label">Deadline</label>
                                                <input type="date" class="form-control" id="deadline" name="deadline"
                                                    value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control" id="description" name="description"
                                                    rows="4"
                                                    placeholder="Enter task description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                            </div>

                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-outline-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn-primary-custom">
                                                    <i class="bi bi-check2-circle"></i> &nbsp; Create Task
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metrics Cards -->
            <div class="row mt-4">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card">
                        <i class="bi bi-list-task metric-icon"></i>
                        <div class="metric-label">All Tasks</div>
                        <div class="metric-value"><?= $all_tasks_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card">
                        <i class="bi bi-clock-history metric-icon"></i>
                        <div class="metric-label">Pending</div>
                        <div class="metric-value"><?= $pending_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card">
                        <i class="bi bi-pie-chart metric-icon"></i>
                        <div class="metric-label">In Progress</div>
                        <div class="metric-value"><?= $in_progress_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card">
                        <i class="bi bi-check2-circle metric-icon"></i>
                        <div class="metric-label">Completed</div>
                        <div class="metric-value"><?= $completed_count ?></div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter Buttons -->
            <div class="col-lg-12 col-md-6 col-12 bg-light-subtle p-3 border shadow rounded mb-3">
                <form method="get" class="mb-0">
                    <div class="row g-2 d-flex">
                        <div class="col-lg-6 col-md-6 col-12" style="height: 8vh;">
                            <div class="input-group">
                                <span class="input-group-text" style="height: 8vh;">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                    class="form-control" placeholder="Search tasks..." style="height: 8vh;">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 d-flex gap-2">
                            <button class="btn btn-outline-secondary" type="submit">Search</button>
                            <?php if ($search): ?>
                            <a href="tasks_dashboard.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-buttons mb-3">
                <?php
                $baseUrl = 'tasks_dashboard.php';
                $queryBase = $search !== '' ? 'q=' . urlencode($search) . '&' : '';
                ?>
                <a class="filter-btn <?= $filterStatus === 'all' ? 'active' : '' ?>"
                    href="<?= $baseUrl . '?' . $queryBase . 'filter=all' ?>">All</a>
                <a class="filter-btn <?= $filterStatus === 'todo' ? 'active' : '' ?>"
                    href="<?= $baseUrl . '?' . $queryBase . 'filter=todo' ?>">Pending</a>
                <a class="filter-btn <?= $filterStatus === 'in_progress' ? 'active' : '' ?>"
                    href="<?= $baseUrl . '?' . $queryBase . 'filter=in_progress' ?>">In Progress</a>
                <a class="filter-btn <?= $filterStatus === 'done' ? 'active' : '' ?>"
                    href="<?= $baseUrl . '?' . $queryBase . 'filter=done' ?>">Completed</a>
            </div>

            <!-- Tasks List -->
            <div class="section-title">
                <span>My Tasks</span>
            </div>

            <?php
            if ($uid > 0) {
                // Build query based on filter and search
                $where = 'WHERE t.assigned_to = ?';
                $params = [$uid];
                $types = 'i';

                if ($filterStatus === 'todo') {
                    $where .= " AND t.status = 'todo'";
                } elseif ($filterStatus === 'in_progress') {
                    $where .= " AND t.status = 'in_progress'";
                } elseif ($filterStatus === 'done') {
                    $where .= " AND t.status = 'done'";
                }

                if ($search !== '') {
                    $where .= " AND (t.title LIKE ? OR t.description LIKE ?)";
                    $like = "%{$search}%";
                    $params[] = $like;
                    $params[] = $like;
                    $types .= 'ss';
                }

                $query = "SELECT t.id, t.title, t.description, t.status, t.deadline, t.created_at, p.project_name
                          FROM tasks t
                          LEFT JOIN projects p ON t.project_id = p.id
                          $where
                          ORDER BY t.created_at DESC";
                $stmt = $conn->prepare($query);
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $has_tasks = false;

                    while ($task = $result->fetch_assoc()) {
                        $has_tasks = true;
                        $status = strtolower($task['status']);
                        $status_badge_class = match ($status) {
                            'todo' => 'todo',
                            'in_progress' => 'in_progress',
                            'done' => 'done',
                            default => 'todo'
                        };
                        $status_display = ucfirst(str_replace('_', ' ', $task['status']));
                        $deadline_display = $task['deadline'] ? date('M d, Y', strtotime($task['deadline'])) : 'No deadline';
                        $project_display = $task['project_name'] ?: 'Unassigned';
            ?>
            <div class="task-card">
                <div class="task-icon-box">
                    <i class="bi bi-list-check"></i>
                </div>
                <div class="task-content">
                    <div class="task-header">
                        <div class="task-title">
                            <?= htmlspecialchars($task['title']) ?>
                        </div>
                        <span class="task-status <?= $status_badge_class ?>">
                            <?= $status_display ?>
                        </span>
                    </div>
                    <div class="task-description">
                        <?= htmlspecialchars($task['description']) ?>
                    </div>
                    <div class="task-meta">
                        <span class="task-meta-item">
                            <i class="bi bi-calendar2"></i>
                            Deadline: <?= htmlspecialchars($deadline_display) ?>
                        </span>
                        <span class="task-meta-item">
                            <i class="bi bi-clock"></i>
                            Created: <?= date('M d, Y', strtotime($task['created_at'])) ?>
                        </span>
                    </div>
                    <div class="task-badges">
                        <span class="task-project">
                            <?= htmlspecialchars($project_display) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php
                    }

                    if (!$has_tasks) {
                    ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No tasks found</p>
                <p style="font-size: 0.85rem; color: #bbb; margin-top: 0.5rem;">
                    Create a task to get started
                </p>
            </div>
            <?php
                    }
                    $stmt->close();
                } else {
                    echo '<p>Database error</p>';
                }
            } else {
                ?>
            <div class="empty-state">
                <i class="bi bi-exclamation-circle"></i>
                <p>Unable to load tasks</p>
            </div>
            <?php
            }
            ?>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function filterRequests(filter) {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Filter logic can be enhanced with AJAX to reload requests
        console.log('Filter: ' + filter);
    }

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
