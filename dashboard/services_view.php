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
    $where = 'WHERE s.ServiceName LIKE ? OR s.ServiceTier LIKE ? OR c.CategoryName LIKE ?';
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types = 'sss';
}

// Count total
$countSql = "SELECT COUNT(*) as c FROM services s LEFT JOIN categories c ON s.CategoryID = c.CategoryID " . $where;
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

// Fetch services
$sql = "SELECT s.*, c.CategoryName FROM services s LEFT JOIN categories c ON s.CategoryID = c.CategoryID " . $where . " ORDER BY s.ServiceID DESC LIMIT ? OFFSET ?";
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
    <title>Services Management</title>

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
                    <h3>Services Management</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="bi bi-plus-circle"></i> Add Service
                    </button>
                </div>

                <!-- Search -->
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Search services..." value="<?= htmlspecialchars($q) ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if ($q): ?><a href="services_view.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive rounded shadow border">
                    <table class="table table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Service Name</th>
                                <th>Tier</th>
                                <th>Hourly Rate</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($service = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($service['ServiceID']) ?></td>
                                    <td><?= htmlspecialchars($service['ServiceName']) ?></td>
                                    <td><?= htmlspecialchars($service['ServiceTier'] ?? '') ?></td>
                                    <td>$<?= htmlspecialchars($service['HourlyRate'] ?? '0.00') ?></td>
                                    <td><?= htmlspecialchars($service['CategoryName'] ?? 'N/A') ?></td>
                                    <td>
                                        <a href="services_edit.php?id=<?= $service['ServiceID'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pen"></i> Edit
                                        </a>
                                        <form method="post" action="services_delete.php" style="display: inline" onsubmit="return confirm('Are you sure you want to delete this service?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="id" value="<?= $service['ServiceID'] ?>">
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
                    <nav aria-label="Services pagination">
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

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="services_update.php">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label for="service_name" class="form-label">Service Name *</label>
                            <input type="text" id="service_name" name="service_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="service_tier" class="form-label">Service Tier</label>
                            <input type="text" id="service_tier" name="service_tier" class="form-control" placeholder="Basic, Standard, Premium">
                        </div>
                        <div class="mb-3">
                            <label for="hourly_rate" class="form-label">Hourly Rate</label>
                            <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" min="0" step="0.01">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Service</button>
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