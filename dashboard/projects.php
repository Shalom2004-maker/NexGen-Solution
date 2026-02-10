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
        margin-bottom: 1.5rem;
    }

    .page-header h3 {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
        letter-spacing: -0.02em;
    }

    .page-header p {
        color: #5b6777;
        margin: 0;
    }

    /* Action Button */
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

    .form-container {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        margin-bottom: 2rem;
    }

    .table-container {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        overflow-x: auto;
    }

    .table-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .table-toolbar .search-box {
        position: relative;
        max-width: 320px;
        width: 100%;
    }

    .table-toolbar .search-box input {
        padding-left: 2.25rem;
    }

    .table-toolbar .search-box i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        pointer-events: none;
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
        color: #334155;
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
            padding: 1.25rem;
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
            font-size: 1.35rem;
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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