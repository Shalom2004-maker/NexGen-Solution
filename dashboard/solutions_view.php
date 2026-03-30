<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);

// Search & pagination
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build search query
$where = '';
$params = [];
$types = '';

if ($q !== '') {
    $where = 'WHERE s.Title LIKE ? OR s.Description LIKE ? OR c.CategoryName LIKE ?';
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types = 'sss';
}

// Count total
$countSql = "SELECT COUNT(*) as c FROM solutions s LEFT JOIN categories c ON s.CategoryID = c.CategoryID " . $where;
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

// Fetch solutions
$sql = "SELECT s.*, c.CategoryName FROM solutions s LEFT JOIN categories c ON s.CategoryID = c.CategoryID " . $where . " ORDER BY s.SolutionID DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($params) {
    $bindTypes = $types . 'ii';
    $stmt->bind_param($bindTypes, ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$res = $stmt->get_result();

// Get categories for select
$categoryOptions = [];
$catStmt = $conn->query("SELECT CategoryID, CategoryName FROM categories ORDER BY CategoryName");
while ($cat = $catStmt->fetch_assoc()) {
    $categoryOptions[$cat['CategoryID']] = $cat['CategoryName'];
}
$catStmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solutions Management</title>

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
</head>

<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div><button class="sidebar-toggle" id="sidebarToggleBtn"
        type="button"><i class="bi bi-list"></i></button>
    <div class="main-wrapper">
        <div id="sidebarContainer"><?php include "../includes/sidebar_helper.php";
                                    render_sidebar(); ?></div>
        <div class="main-content">

            <div class="dashboard-shell">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Solutions Management</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSolutionModal">
                        <i class="bi bi-plus-circle"></i> Add Solution
                    </button>
                </div>

                <!-- Search -->
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Search solutions..." value="<?= htmlspecialchars($q) ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if ($q): ?><a href="solutions_view.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive rounded shadow border">
                    <table class="table table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Date Created</th>
                                <th>Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($solution = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($solution['SolutionID']) ?></td>
                                    <td><?= htmlspecialchars($solution['Title']) ?></td>
                                    <td><?= htmlspecialchars(substr($solution['Description'] ?? '', 0, 50)) ?><?= strlen($solution['Description'] ?? '') > 50 ? '...' : '' ?></td>
                                    <td><?= htmlspecialchars($solution['CategoryName'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($solution['DateCreated']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $solution['IsActive'] ? 'success' : 'secondary' ?>">
                                            <?= $solution['IsActive'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="solutions_edit.php?id=<?= $solution['SolutionID'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pen"></i> Edit
                                        </a>
                                        <form method="post" action="solutions_delete.php" style="display: inline" onsubmit="return confirm('Are you sure you want to delete this solution?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="id" value="<?= $solution['SolutionID'] ?>">
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php $pages = max(1, ceil($total / $limit)); ?>
                <?php if ($pages > 1): ?>
                    <nav aria-label="Solutions pagination">
                        <ul class="pagination justify-content-center mt-3">
                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Solution Modal -->
    <div class="modal fade" id="addSolutionModal" tabindex="-1" aria-labelledby="addSolutionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSolutionModalLabel">Add New Solution</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="solutions_update.php">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select id="category_id" name="category_id" class="form-select">
                                <option value="">Select category</option>
                                <?php foreach ($categoryOptions as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="date_created" class="form-label">Date Created *</label>
                            <input type="date" id="date_created" name="date_created" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="is_active" class="form-label">Visibility</label>
                            <select id="is_active" name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Solution</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle
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
        });
    </script>
</body>

</html>