<?php
include "../includes/auth.php";
// Only Project Leaders (and Admin) can manage projects
allow(["ProjectLeader", "Admin"]);
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
            $_SESSION['flash_error'] = 'Project name is required';
            $_SESSION['flash_modal'] = 'create';
        } else {
            $stmt = $conn->prepare("INSERT INTO projects (project_name, description, leader_id, start_date, end_date) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssiss', $name, $desc, $leader, $start, $end);
            if ($stmt->execute()) {
                if (function_exists('audit_log')) audit_log('project_create', "Project {$name} created", $uid);
            } else {
                $_SESSION['flash_error'] = 'Failed to create project';
                $_SESSION['flash_modal'] = 'create';
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
            $_SESSION['flash_error'] = 'Project name is required';
        } else {
            $u = $conn->prepare("UPDATE projects SET project_name = ?, description = ?, leader_id = ?, start_date = ?, end_date = ? WHERE id = ?");
            $u->bind_param('ssissi', $name, $desc, $leader, $start, $end, $id);
            if ($u->execute()) {
                if (function_exists('audit_log')) audit_log('project_update', "Project {$id} updated", $uid);
            } else {
                $_SESSION['flash_error'] = 'Failed to update project';
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
            $_SESSION['flash_error'] = 'Failed to delete project';
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
$projectCount = $res ? (int)$res->num_rows : 0;

// fetch users for leader select
$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");

// flash messages
$error = $_SESSION['flash_error'] ?? null;
$flashModal = $_SESSION['flash_modal'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_modal']);
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
                <div class="page-header d-flex flex-wrap align
                -items-center justify-content-between gap-2">
                    <div>
                        <h3>Projects Management</h3>
                        <p><?= $projectCount ?> total projects in the workspace</p>
                    </div>
                    <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createProjectModal"
                        type="button">
                        <i class="bi bi-plus-circle"></i>&nbsp; Create New Project
                    </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($error)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                <div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="createProjectLabel">Create New Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form method="post">
                                <div class="modal-body">
                                    <input type="hidden" name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="create">

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label for="project_name_create" class="form-label">Project Name *</label>
                                            <input type="text" id="project_name_create" name="project_name"
                                                class="form-control" placeholder="Enter project name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="leader_id_create" class="form-label">Project Leader</label>
                                            <select id="leader_id_create" name="leader_id" class="form-select">
                                                <option value="">Select Leader (optional)</option>
                                                <?php
                                                $users->data_seek(0);
                                                while ($u = $users->fetch_assoc()) : ?>
                                                <option value="<?= $u['id'] ?>">
                                                    <?= htmlspecialchars($u['full_name']) ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label for="start_date_create" class="form-label">Start Date</label>
                                            <input type="date" id="start_date_create" name="start_date"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="end_date_create" class="form-label">End Date</label>
                                            <input type="date" id="end_date_create" name="end_date"
                                                class="form-control">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description_create" class="form-label">Description</label>
                                        <textarea id="description_create" name="description" class="form-control"
                                            rows="4" placeholder="Enter project description"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn-primary-custom">Create Project</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($edit && in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                <div class="form-container">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h5 class="mb-0">Edit Project</h5>
                        <a href="projects.php" class="btn btn-outline-secondary btn-sm">Cancel Edit</a>
                    </div>
                    <form method="post" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id']) ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="project_name" class="form-label">Project Name *</label>
                                <input type="text" id="project_name" name="project_name" class="form-control"
                                    placeholder="Enter project name" required
                                    value="<?= htmlspecialchars($edit['project_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="leader_id" class="form-label">Project Leader</label>
                                <select id="leader_id" name="leader_id" class="form-select">
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
                            <button type="submit" class="btn-primary-custom">Update Project</button>
                            <a href="projects.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-toolbar">
                        <div>
                            <h6 class="mb-0">Projects List</h6>
                            <small class="text-muted">Manage active and upcoming initiatives</small>
                        </div>
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input id="projectSearch" type="text" class="form-control"
                                placeholder="Search by name, leader, or date">
                        </div>
                    </div>
                    <table class="table align-middle mb-0">
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
                            <?php if ($projectCount === 0) : ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No projects available yet.</td>
                            </tr>
                            <?php else : ?>
                            <?php while ($p = $res->fetch_assoc()) : ?>
                            <tr data-project-row="1">
                                <td><?= htmlspecialchars($p['id']) ?></td>
                                <td><?= htmlspecialchars($p['project_name']) ?></td>
                                <td><?= htmlspecialchars(substr($p['description'], 0, 50)) ?><?= strlen($p['description']) > 50 ? '...' : '' ?>
                                </td>
                                <td><?= $p['leader_name'] ? htmlspecialchars($p['leader_name']) : '-' ?></td>
                                <td><?= htmlspecialchars($p['start_date'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($p['end_date'] ?? '-') ?></td>
                                <td>
                                    <?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?>
                                    <div class="d-flex gap-2">
                                        <a class="bi bi-pen btn btn-outline-primary"
                                            href="projects.php?edit_id=<?= $p['id'] ?>"></a>
                                        <form method="post" style="display: inline"
                                            onsubmit="return confirm('Delete this project?')">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="bi bi-trash btn btn-outline-danger"></button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <tr id="projectNoResultsRow" class="d-none">
                                <td colspan="7" class="text-center text-muted py-4">No matching projects found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const nexgenSidebar = document.getElementById('nexgenSidebar');
        const projectSearch = document.getElementById('projectSearch');
        const projectRows = document.querySelectorAll('tr[data-project-row="1"]');
        const noResultsRow = document.getElementById('projectNoResultsRow');

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

        if (projectSearch) {
            projectSearch.addEventListener('input', function() {
                const query = projectSearch.value.toLowerCase().trim();
                let visibleCount = 0;
                projectRows.forEach(row => {
                    const match = row.textContent.toLowerCase().includes(query);
                    row.classList.toggle('d-none', !match);
                    if (match) visibleCount += 1;
                });
                if (noResultsRow) {
                    noResultsRow.classList.toggle('d-none', visibleCount !== 0 || query === '');
                }
            });
        }

        const openCreateModal = <?= $flashModal === 'create' ? 'true' : 'false' ?>;
        if (openCreateModal) {
            const modalEl = document.getElementById('createProjectModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        }
    });
    </script>
</body>

</html>


