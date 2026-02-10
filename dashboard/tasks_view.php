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

// Search & pagination
$q = trim($_GET['q'] ?? '');
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
        overflow-y: auto;
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
        margin-bottom: 2rem;
    }

    .page-header h3 {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: #5b6777;
        margin: 0;
    }

    .table-container {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        overflow-x: auto;
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-container th {
        background-color: #f8fafc;
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #d4d4d4;
        font-weight: 600;
        font-size: 0.95rem;
        color: #334155;
    }

    .table-container td {
        padding: 0.75rem;
        border-bottom: 1px solid #d4d4d4;
        font-size: 0.9rem;
    }

    .table-container tr:hover {
        background-color: #f9f9f9;
    }

    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
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

    .pagination {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
        justify-content: center;
    }

    .pagination a,
    .pagination span {
        padding: 0.5rem 0.75rem;
        border: 1px solid #d4d4d4;
        border-radius: 999px;
        text-decoration: none;
        color: #1d4ed8;
    }

    .pagination a:hover {
        background-color: #1d4ed8;
        color: white;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .action-buttons a {
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.3s ease;
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

    .btn-edit {
        background-color: #17a2b8;
        color: white;
    }

    .btn-delete {
        background-color: #dc3545;
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
            padding-top: 3.5rem;
            width: 100%;
        }

        .dashboard-shell {
            padding: 1rem;
        }

        .table-container {
            padding: 1rem;
        }

        .table-container table {
            font-size: 0.9rem;
        }

        .table-container th,
        .table-container td {
            padding: 0.5rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .action-buttons a {
            width: 100%;
            text-align: center;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
            width: 100%;

        }

        .page-header h3 {
            font-size: 1.35rem;
        }

        .table-container {
            padding: 0.75rem;
            font-size: 0.85rem;
        }

        .table-container th,
        .table-container td {
            padding: 0.4rem;
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
            <?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="page-header d-flex justify-content-between align-items-center end-0">
                    <div>
                        <h3>View All Tasks</h3>
                        <p>Manage and track all tasks</p>
                    </div>

                    <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                    <button type="button" class="btn-primary-custom">
                        <a href="leader_tasks.php" class="text-white text-decoration-none">
                            <i class=" bi bi-plus-circle"></i> &nbsp; Create Tasks
                        </a>
                    </button>
                    <?php endif; ?>

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
                                        $statusLabel = ucfirst(str_replace('_', ' ', $t['status']));
                                        ?>
                                    <span
                                        class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                </td>
                                <td><?= $t['deadline'] ? date('M d, Y', strtotime($t['deadline'])) : 'No deadline' ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ((int)$t['assigned_to'] === $uid || in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                                        <form method="post" action="tasks_update.php" style="display: inline">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="task_id"
                                                value="<?= htmlspecialchars($t['id']) ?>">
                                            <button type="submit" class="btn btn-outline-success"
                                                title="Toggle status">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if (in_array($role, ['ProjectLeader', 'Admin'], true) || $t['created_by'] == $uid): ?>
                                        <a href="tasks_edit.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-pen"></i>
                                        </a>
                                        <form method="post" action="task_delete.php" style="display: inline"
                                            onsubmit="return confirm('Delete this task?')">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="task_id"
                                                value="<?= htmlspecialchars($t['id']) ?>">
                                            <button type="submit" class="btn btn-outline-danger"
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
                        <a href="?q=<?= urlencode($q) ?>&page=<?= $p ?>" <?= $active ?>>
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
