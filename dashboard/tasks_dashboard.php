<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "Admin"]);
include "../includes/db.php";
include "../includes/logger.php";

// Get user ID
$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
$role = $_SESSION['role'] ?? '';
$role_lc = strtolower(trim((string)$role));
$is_admin = $role_lc === 'admin';

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
    } elseif (!in_array($role, ['ProjectLeader', 'Admin'], true)) {
        http_response_code(403);
        $error = 'You do not have permission to create tasks.';
    } else {
        $project_id = trim($_POST['project'] ?? '');
        $project_id = $project_id !== '' ? (string)((int)$project_id) : '';
        $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : $uid;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');

        if ($title === '') {
            $error = 'Task title is required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks (project_id, assigned_to, created_by, title, description, status, deadline) VALUES (NULLIF(?, ''), ?, ?, ?, ?, 'todo', NULLIF(?, ''))");
            $stmt->bind_param("siisss", $project_id, $assigned_to, $uid, $title, $description, $deadline);
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
$global_completion_rate = 0.0;
$team_progress_rows = [];

$can_view_all = in_array($role, ['ProjectLeader', 'Admin'], true);
$scope_where = $can_view_all ? '' : 'WHERE assigned_to = ?';
$scope_types = $can_view_all ? '' : 'i';
$scope_params = $can_view_all ? [] : [$uid];

$count_query_base = "SELECT COUNT(*) as count FROM tasks";

// All tasks count
$stmt = $conn->prepare($count_query_base . " $scope_where");
if ($stmt) {
    if (!$can_view_all) {
        $stmt->bind_param($scope_types, ...$scope_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $all_tasks_count = (int)($row['count'] ?? 0);
    $stmt->close();
}

// Pending (todo) count
$stmt = $conn->prepare($count_query_base . " $scope_where" . ($scope_where ? " AND" : " WHERE") . " status = 'todo'");
if ($stmt) {
    if (!$can_view_all) {
        $stmt->bind_param($scope_types, ...$scope_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $pending_count = (int)($row['count'] ?? 0);
    $stmt->close();
}

// In Progress count
$stmt = $conn->prepare($count_query_base . " $scope_where" . ($scope_where ? " AND" : " WHERE") . " status = 'in_progress'");
if ($stmt) {
    if (!$can_view_all) {
        $stmt->bind_param($scope_types, ...$scope_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $in_progress_count = (int)($row['count'] ?? 0);
    $stmt->close();
}

// Completed count
$stmt = $conn->prepare($count_query_base . " $scope_where" . ($scope_where ? " AND" : " WHERE") . " status = 'done'");
if ($stmt) {
    if (!$can_view_all) {
        $stmt->bind_param($scope_types, ...$scope_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $completed_count = (int)($row['count'] ?? 0);
    $stmt->close();
}

if ($all_tasks_count > 0) {
    $global_completion_rate = round(($completed_count / $all_tasks_count) * 100, 1);
}

if ($can_view_all) {
    $progress_sql = "SELECT u.id, u.full_name,
                            COUNT(t.id) AS all_tasks,
                            SUM(CASE WHEN t.status = 'todo' THEN 1 ELSE 0 END) AS pending_tasks,
                            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_tasks,
                            SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) AS completed_tasks
                     FROM tasks t
                     JOIN users u ON u.id = t.assigned_to
                     GROUP BY u.id, u.full_name
                     ORDER BY completed_tasks DESC, all_tasks DESC, u.full_name ASC";
    $progress_result = $conn->query($progress_sql);
    if ($progress_result) {
        while ($row = $progress_result->fetch_assoc()) {
            $user_total = (int)($row['all_tasks'] ?? 0);
            $user_done = (int)($row['completed_tasks'] ?? 0);
            $row['completion_rate'] = $user_total > 0 ? round(($user_done / $user_total) * 100, 1) : 0.0;
            $team_progress_rows[] = $row;
        }
    }
}

// Search
$search = trim($_GET['q'] ?? '');

// Get filter status from URL
$filterStatus = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filters = ['all', 'todo', 'in_progress', 'done'];
$filterStatus = in_array($filterStatus, $filters) ? $filterStatus : 'all';

// Redirect target for status updates (preserve search/filter)
$redirect_url = 'tasks_dashboard.php';
$redirect_params = [];
if ($search !== '') {
    $redirect_params[] = 'q=' . urlencode($search);
}
if ($filterStatus !== 'all') {
    $redirect_params[] = 'filter=' . urlencode($filterStatus);
}
if ($redirect_params) {
    $redirect_url .= '?' . implode('&', $redirect_params);
}

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

    <!-- Local Bootstrap CSS Link -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="/js/bootstrap.bundle.min.js"></script>

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
                        <h2>Tasks</h2>
                        <p><?= $can_view_all ? 'Monitor all assigned tasks and team progress' : 'Manage and track your assigned tasks' ?>
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="button" class="btn-primary-custom">
                            <a href="tasks_view.php" class="text-white text-decoration-none">
                                <i class=" bi bi-eye"></i> &nbsp; View Tasks
                            </a>
                        </button>
                        <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Metrics Cards -->
            <div class="row mt-4">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card mb-3">
                        <i class="bi bi-list-task metric-icon"></i>
                        <div class="metric-label">All Tasks</div>
                        <div class="metric-value"><?= $all_tasks_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card mb-3">
                        <i class="bi bi-clock-history metric-icon"></i>
                        <div class="metric-label">Pending</div>
                        <div class="metric-value"><?= $pending_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card mb-3">
                        <i class="bi bi-pie-chart metric-icon"></i>
                        <div class="metric-label">In Progress</div>
                        <div class="metric-value"><?= $in_progress_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card mb-3">
                        <i class="bi bi-check2-circle metric-icon"></i>
                        <div class="metric-label">Completed</div>
                        <div class="metric-value"><?= $completed_count ?></div>
                    </div>
                </div>
            </div>

            <?php if ($can_view_all) : ?>
            <div class="section-title mt-4 mb-3 mx-2">
                <span>Team Progress</span>
                <span class="text-muted" style="font-size:0.85rem;">
                    &nbsp; Global completion rate: <?= number_format($global_completion_rate, 1) ?>%
                </span>
            </div>
            <div class="team-progress-wrap">
                <div class="table-responsive" style=" overflow-y: scroll; height: 300px;">
                    <table class=" table table-hover team-progress-table mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>All</th>
                                <th>Pending</th>
                                <th>In Progress</th>
                                <th>Completed</th>
                                <th class=" team-progress-rate">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($team_progress_rows)) : ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No assigned tasks found.</td>
                            </tr>
                            <?php else : ?>
                            <?php foreach ($team_progress_rows as $progress_row) : ?>
                            <?php $rate = (float)($progress_row['completion_rate'] ?? 0.0); ?>
                            <tr>
                                <td><?= htmlspecialchars($progress_row['full_name'] ?? 'Unknown User') ?></td>
                                <td><?= (int)($progress_row['all_tasks'] ?? 0) ?></td>
                                <td><?= (int)($progress_row['pending_tasks'] ?? 0) ?></td>
                                <td><?= (int)($progress_row['in_progress_tasks'] ?? 0) ?></td>
                                <td><?= (int)($progress_row['completed_tasks'] ?? 0) ?></td>
                                <td class="team-progress-rate">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar"
                                            style="width: <?= max(0, min(100, $rate)) ?>%;"></div>
                                    </div>
                                    <small class="text-muted"><?= number_format($rate, 1) ?>%</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Search & Filter Buttons -->
            <div class="col-lg-12 col-md-6 col-12 bg-light-subtle p-3 border shadow rounded mb-3 mt-4">
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
            <div class="filter-buttons mb-3 mt-3">
                <?php
                $baseUrl = 'tasks_dashboard.php';
                $queryBase = $search !== '' ? 'q=' . urlencode($search) . '&' : '';
                ?>
                <a class="filter-btn text-decoration-none <?= $filterStatus === 'all' ? 'active' : '' ?>"
                    href="<?= $baseUrl . '?' . $queryBase . 'filter=all' ?>">All</a>
                <a class="filter-btn text-decoration-none <?= $filterStatus === 'todo' ? 'active' : '' ?>"
                    href="<?= $baseUrl . '?' . $queryBase . 'filter=todo' ?>">Pending</a>
                <a class="filter-btn text-decoration-none <?= $filterStatus === 'in_progress' ? 'active' : '' ?>"
                    href="<?= $baseUrl . '?' . $queryBase . 'filter=in_progress' ?>">In Progress</a>
                <a class="filter-btn text-decoration-none <?= $filterStatus === 'done' ? 'active' : '' ?>"
                    href="<?= $baseUrl . '?' . $queryBase . 'filter=done' ?>">Completed</a>
            </div>

            <!-- Tasks List -->
            <div class="section-title">
                <span class="mx-2 mb-3 mt-3"><?= $can_view_all ? 'All Tasks' : 'My Tasks' ?></span>
            </div>

            <?php
            if ($uid > 0) {
                // Build query based on filter and search
                $where = $can_view_all ? 'WHERE 1=1' : 'WHERE t.assigned_to = ?';
                $params = $can_view_all ? [] : [$uid];
                $types = $can_view_all ? '' : 'i';

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

                $query = "SELECT t.id, t.assigned_to, t.title, t.description, t.status, t.deadline, t.created_at,
                                 p.project_name, u.full_name AS assigned_name
                          FROM tasks t
                          LEFT JOIN projects p ON t.project_id = p.id
                          LEFT JOIN users u ON u.id = t.assigned_to
                          $where
                          ORDER BY t.created_at DESC";
                $stmt = $conn->prepare($query);
                if ($stmt) {
                    if ($types !== '') {
                        $stmt->bind_param($types, ...$params);
                    }
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
                        $status_display = match ($status) {
                            'todo' => 'Pending',
                            'in_progress' => 'In Progress',
                            'done' => 'Completed',
                            default => ucfirst(str_replace('_', ' ', (string)$task['status']))
                        };
                        $deadline_display = $task['deadline'] ? date('M d, Y', strtotime($task['deadline'])) : 'No deadline';
                        $project_display = $task['project_name'] ?: 'Unassigned';
                        $assigned_display = trim((string)($task['assigned_name'] ?? '')) ?: 'Unassigned';
                        $assigned_to = (int)($task['assigned_to'] ?? 0);
                        $can_update_status = $is_admin || $assigned_to === $uid;
                        if ($is_admin) {
                            $status_options = [
                                'todo' => 'Pending',
                                'in_progress' => 'In Progress',
                                'done' => 'Completed'
                            ];
                        } elseif ($status === 'todo') {
                            $status_options = [
                                'todo' => 'Pending',
                                'in_progress' => 'In Progress'
                            ];
                        } elseif ($status === 'in_progress') {
                            $status_options = [
                                'in_progress' => 'In Progress',
                                'done' => 'Completed'
                            ];
                        } else {
                            $status_options = [
                                'done' => 'Completed'
                            ];
                        }
            ?>
            <div class="task-card p-3 mb-3">
                <div class="task-icon-box">
                    <i class="bi bi-list-check"></i>
                </div>
                <div class="task-content">
                    <div class="task-header">
                        <div class="task-title">
                            <div class="col-lg-7 col-md-6 col-12">
                                <div class="d-flex justify-content-start mb-2">
                                    <b><?= htmlspecialchars($task['title']) ?></b>
                                    <span class="task-status mx-5 <?= $status_badge_class ?>">
                                        <?= $status_display ?>
                                    </span>
                                </div>
                                <div class="task-description">
                                    <?= htmlspecialchars($task['description']) ?>
                                </div>
                                <div class="task-meta mt-2 mb-2">
                                    <span class="task-meta-item">
                                        <i class="bi bi-calendar2"></i> &nbsp;
                                        Deadline: <?= htmlspecialchars($deadline_display) ?>
                                    </span>
                                    <span class="task-meta-item mx-5">
                                        <i class="bi bi-clock"></i> &nbsp;
                                        Created: <?= date('M d, Y', strtotime($task['created_at'])) ?>
                                    </span>
                                    <?php if ($can_view_all) : ?>
                                    <span class="task-meta-item">
                                        <i class="bi bi-person"></i> &nbsp;
                                        Assigned: <?= htmlspecialchars($assigned_display) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="task-badges mt-2 d-flex gap-2 justify-content-end">
                                    <span class="task-project">
                                        <?= htmlspecialchars($project_display) ?>
                                    </span>
                                    <?php if ($can_update_status) : ?>
                                    <form method="post" action="tasks_update.php"
                                        class="d-flex align-items-center gap-2 ms-auto">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="set_status">
                                        <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                        <input type="hidden" name="redirect"
                                            value="<?= htmlspecialchars($redirect_url) ?>">
                                        <select name="status" class="form-select form-select-sm" style="width:auto;">
                                            <?php foreach ($status_options as $option_value => $option_label) : ?>
                                            <option value="<?= htmlspecialchars($option_value) ?>"
                                                <?= $status === $option_value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($option_label) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary"
                                            <?= count($status_options) === 1 ? 'disabled' : '' ?>>Update</button>
                                    </form>
                                    <?php else : ?>
                                    <span class="readonly-note ms-auto">Read only</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
        const taskCreateModal = document.getElementById('taskCreateModal');

        if (taskCreateModal && taskCreateModal.parentElement !== document.body) {
            document.body.appendChild(taskCreateModal);
        }

        const closeSidebar = function() {
            if (nexgenSidebar) {
                nexgenSidebar.classList.remove('show');
            }

            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('show');
            }
        };

        const cleanupModalArtifacts = function() {
            if (document.querySelector('.modal.show')) {
                return;
            }

            document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                backdrop.remove();
            });

            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        };

        if (sidebarToggleBtn && nexgenSidebar) {
            sidebarToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpening = !nexgenSidebar.classList.contains('show');
                nexgenSidebar.classList.toggle('show', isOpening);
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('show', isOpening);
                }
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        if (nexgenSidebar) {
            document.querySelectorAll('.nexgen-sidebar-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeSidebar();
                    }
                });
            });
        }

        document.addEventListener('show.bs.modal', closeSidebar);
        document.addEventListener('hidden.bs.modal', cleanupModalArtifacts);
    });
    </script>
</body>

</html>