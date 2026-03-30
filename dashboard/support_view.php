<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
$types = '';

if ($q !== '') {
    $where = 'WHERE s.Subject LIKE ? OR sol.Title LIKE ? OR ser.ServiceName LIKE ?';
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types = 'sss';
}

if ($statusFilter !== '') {
    $where .= ($where === '' ? 'WHERE' : ' AND') . ' s.Status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$countSql = "SELECT COUNT(*) as c FROM support s LEFT JOIN solutions sol ON s.SolutionID = sol.SolutionID LEFT JOIN services ser ON s.ServiceID = ser.ServiceID " . $where;
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

$sql = "SELECT s.*, sol.Title as SolutionTitle, ser.ServiceName FROM support s LEFT JOIN solutions sol ON s.SolutionID = sol.SolutionID LEFT JOIN services ser ON s.ServiceID = ser.ServiceID " . $where . " ORDER BY s.CreatedAt DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($params) {
    $bindTypes = $types . 'ii';
    $stmt->bind_param($bindTypes, ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$res = $stmt->get_result();

$solutionOptions = [];
$solStmt = $conn->query("SELECT SolutionID, Title FROM solutions ORDER BY Title");
while ($sol = $solStmt->fetch_assoc()) {
    $solutionOptions[$sol['SolutionID']] = $sol['Title'];
}
$solStmt->close();

$serviceOptions = [];
$serStmt = $conn->query("SELECT ServiceID, ServiceName FROM services ORDER BY ServiceName");
while ($ser = $serStmt->fetch_assoc()) {
    $serviceOptions[$ser['ServiceID']] = $ser['ServiceName'];
}
$serStmt->close();

$statusOptions = ['Open', 'In Progress', 'Resolved', 'Closed'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="/js/bootstrap.bundle.min.js"></script>
</head>

<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div><button class="sidebar-toggle" id="sidebarToggleBtn" type="button"><i class="bi bi-list"></i></button>
    <div class="main-wrapper">
        <div id="sidebarContainer"><?php include "../includes/sidebar_helper.php";
                                    render_sidebar(); ?></div>
        <div class="main-content">
            <div class="dashboard-shell">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Support Management</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupportModal">
                        <i class="bi bi-plus-circle"></i> Add Support Record
                    </button>
                </div>
                <form method="GET" class="mb-3">
                    <div class="d-flex gap-2">
                        <input type="text" name="q" class="form-control" placeholder="Search support..." value="<?= htmlspecialchars($q) ?>">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-secondary" type="submit">Filter</button>
                        <?php if ($q || $statusFilter): ?><a href="support_view.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive rounded shadow border">
                    <table class="table table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Solution</th>
                                <th>Service</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($support = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($support['ID']) ?></td>
                                    <td><?= htmlspecialchars($support['Subject']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= strtolower(str_replace(' ', '', $support['Status'])) === 'resolved' ? 'success' : (strtolower($support['Status']) === 'inprogress' ? 'warning' : 'primary') ?>">
                                            <?= htmlspecialchars($support['Status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($support['Priority']) ?></td>
                                    <td><?= htmlspecialchars($support['SolutionTitle'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($support['ServiceName'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($support['CreatedAt']) ?></td>
                                    <td>
                                        <a href="support_edit.php?id=<?= $support['ID'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pen"></i> Edit
                                        </a>
                                        <form method="post" action="support_delete.php" style="display: inline" onsubmit="return confirm('Are you sure you want to delete this support record?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="id" value="<?= $support['ID'] ?>">
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php $pages = max(1, ceil($total / $limit)); ?>
                <?php if ($pages > 1): ?>
                    <nav aria-label="Support pagination">
                        <ul class="pagination justify-content-center mt-3">
                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addSupportModal" tabindex="-1" aria-labelledby="addSupportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSupportModalLabel">Add Support Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="support_update.php">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" id="subject" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= $status ?>" <?= $status === 'Open' ? 'selected' : '' ?>><?= $status ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <input type="number" id="priority" name="priority" class="form-control" min="1" max="5" value="3">
                        </div>
                        <div class="mb-3">
                            <label for="solution_id" class="form-label">Solution</label>
                            <select id="solution_id" name="solution_id" class="form-select">
                                <option value="">Select solution</option>
                                <?php foreach ($solutionOptions as $id => $title): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($title) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="service_id" class="form-label">Service</label>
                            <select id="service_id" name="service_id" class="form-select">
                                <option value="">Select service</option>
                                <?php foreach ($serviceOptions as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Support Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
        });
    </script>
</body>

</html>