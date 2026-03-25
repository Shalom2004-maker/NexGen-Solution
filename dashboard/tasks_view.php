<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "Admin"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? '';
$role_lc = strtolower(trim((string)$role));
$is_admin = $role_lc === 'admin';

// Search & pagination
$q = trim($_GET['q'] ?? '');
$filter = trim($_GET['filter'] ?? 'all');
$allowedFilters = ['all', 'todo', 'in_progress', 'done'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// prepare user list for select
$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");

// build base where and params (use aliases when joining users)
$where = '';
$params = [];
$types = '';
if (!in_array($role, ['ProjectLeader', 'Admin'], true)) {
    $where = 'WHERE t.assigned_to = ?';
    $params[] = $uid;
    $types .= 'i';
}
if ($filter !== 'all') {
    if ($where === '') {
        $where = 'WHERE t.status = ?';
    } else {
        $where .= ' AND t.status = ?';
    }
    $params[] = $filter;
    $types .= 's';
}
if ($q !== '') {
    $like = "%{$q}%";
    if ($where === '') {
        $where = 'WHERE (t.title LIKE ? OR t.description LIKE ?)';
    } else {
        $where .= ' AND (t.title LIKE ? OR t.description LIKE ?)';
    }
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// count (from tasks aliased)
$countSql = "SELECT COUNT(*) as c FROM tasks t " . $where;
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

// fetch with limit/offset and join user names for assigned_to / created_by
$sql = "SELECT t.id, t.project_id, p.project_name, t.assigned_to, t.created_by, t.title, t.description, t.status, t.deadline,
t.created_at, au.full_name AS assigned_name, cu.full_name AS created_name FROM tasks t LEFT JOIN users au ON
t.assigned_to = au.id LEFT JOIN users cu ON t.created_by = cu.id LEFT JOIN projects p ON t.project_id = p.id " . $where . " ORDER BY t.created_at DESC LIMIT ?
OFFSET ?";
$stmt = $conn->prepare($sql);
// bind params
if ($params) {
    $bindTypes = $types . 'ii';
    $stmt->bind_param($bindTypes, ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task View NexGen Solution</title>

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
                <div class="page-header d-flex justify-content-between align-items-center end-0">
                    <div>
                        <h3><?= in_array($role, ['ProjectLeader', 'Admin'], true) ? 'All Tasks' : 'My Tasks' ?></h3>
                        <p><?= in_array($role, ['ProjectLeader', 'Admin'], true) ? 'Monitor all assigned tasks' : 'Manage and track your assigned tasks' ?>
                        </p>
                    </div>

                    <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                    <button type="button" class="btn-primary-custom">
                        <a href="leader_tasks.php" class="text-white text-decoration-none">
                            <i class=" bi bi-plus-circle"></i> &nbsp; Create Tasks
                        </a>
                    </button>
                    <?php endif; ?>

                </div>

                <?php
                $filterBase = [];
                if ($q !== '') {
                    $filterBase['q'] = $q;
                }
                ?>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>"
                        href="?<?= htmlspecialchars(http_build_query(array_merge($filterBase, ['filter' => 'all', 'page' => 1]))) ?>">All</a>
                    <a class="btn btn-sm <?= $filter === 'todo' ? 'btn-primary' : 'btn-outline-secondary' ?>"
                        href="?<?= htmlspecialchars(http_build_query(array_merge($filterBase, ['filter' => 'todo', 'page' => 1]))) ?>">Pending</a>
                    <a class="btn btn-sm <?= $filter === 'in_progress' ? 'btn-primary' : 'btn-outline-secondary' ?>"
                        href="?<?= htmlspecialchars(http_build_query(array_merge($filterBase, ['filter' => 'in_progress', 'page' => 1]))) ?>">In
                        Progress</a>
                    <a class="btn btn-sm <?= $filter === 'done' ? 'btn-primary' : 'btn-outline-secondary' ?>"
                        href="?<?= htmlspecialchars(http_build_query(array_merge($filterBase, ['filter' => 'done', 'page' => 1]))) ?>">Completed</a>
                </div>

                <div class="table-responsive rounded shadow">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Project</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No tasks found</td>
                            </tr>
                            <?php else: ?>
                            <?php while ($t = $res->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['title']) ?></strong><br><small
                                        class="text-muted"><?= htmlspecialchars($t['description']) ?></small></td>
                                <td><?= htmlspecialchars($t['project_name'] ?? 'Unassigned') ?></td>
                                <td><?= htmlspecialchars($t['assigned_name'] ?? 'Unassigned') ?></td>
                                <td>
                                    <?php
                                        $statusClass = $t['status'] === 'done' ? 'status-done' : ($t['status'] === 'in_progress' ? 'status-in-progress' : 'status-todo');
                                        $statusLabel = match ($t['status']) {
                                            'todo' => 'Pending',
                                            'in_progress' => 'In Progress',
                                            'done' => 'Completed',
                                            default => ucfirst(str_replace('_', ' ', (string)$t['status']))
                                        };
                                        ?>
                                    <span
                                        class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                </td>
                                <td><?= $t['deadline'] ? date('M d, Y', strtotime($t['deadline'])) : 'No deadline' ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ((int)$t['assigned_to'] === $uid || $is_admin) : ?>
                                        <form method="post" action="tasks_update.php" style="display: inline">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="task_id"
                                                value="<?= htmlspecialchars($t['id']) ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm"
                                                title="Advance status">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if (in_array($role, ['ProjectLeader', 'Admin'], true) || $t['created_by'] == $uid): ?>
                                        <a href="tasks_edit.php?id=<?= $t['id'] ?>"
                                            class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pen"></i>
                                        </a>
                                        <form method="post" action="task_delete.php" style="display: inline"
                                            onsubmit="return confirm('Delete this task?')">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="task_id"
                                                value="<?= htmlspecialchars($t['id']) ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                style="cursor:pointer;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($total > $limit): ?>
                    <div class="pagination">
                        <?php
                        $pages = ceil($total / $limit);
                        for ($p = 1; $p <= $pages; $p++):
                            $active = $p === $page ? 'style="background-color:#337ccfe2;color:white;"' : '';
                        ?>
                        <a href="?q=<?= urlencode($q) ?>&filter=<?= urlencode($filter) ?>&page=<?= $p ?>"
                            <?= $active ?>>
                            <?= $p ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
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