<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
include "../includes/header.php";
require_once __DIR__ . "/../includes/logger.php";

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);

// Search & pagination
$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build search query
$where = '';
$params = [];
$types = '';
if ($q !== '') {
    $like = "%{$q}%";
    $where = 'WHERE (name LIKE ? OR email LIKE ? OR company LIKE ? OR message LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}
if ($statusFilter !== '') {
    if ($where === '') {
        $where = 'WHERE status = ?';
    } else {
        $where .= ' AND status = ?';
    }
    $params[] = $statusFilter;
    $types .= 's';
}

// Count total
$countSql = "SELECT COUNT(*) as c FROM inquiries " . $where;
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

// Fetch inquiries
$sql = "SELECT * FROM inquiries " . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
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
    <title>Inquiries Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Website Inquiries</h3>
        <a href="hr_inquiries.php" class="btn btn-secondary">Back to HR Dashboard</a>
    </div>

    <!-- Search and Filter -->
    <form method="get" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <input name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Search inquiries">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="new" <?= $statusFilter === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="replied" <?= $statusFilter === 'replied' ? 'selected' : '' ?>>Replied</option>
                    <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary">Search</button>
            </div>
            <div class="col-md-3">
                <?php if ($q || $statusFilter): ?>
                    <a href="inquiries_view.php" class="btn btn-link">Reset</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($inquiry = $res->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($inquiry['id']) ?></td>
                        <td><?= htmlspecialchars($inquiry['name']) ?></td>
                        <td><?= htmlspecialchars($inquiry['email']) ?></td>
                        <td><?= htmlspecialchars($inquiry['company']) ?></td>
                        <td><?= htmlspecialchars(substr($inquiry['message'], 0, 50)) ?><?= strlen($inquiry['message']) > 50 ? '...' : '' ?></td>
                        <td>
                            <span class="badge bg-<?= $inquiry['status'] === 'new' ? 'primary' : ($inquiry['status'] === 'replied' ? 'warning' : 'secondary') ?>">
                                <?= htmlspecialchars($inquiry['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($inquiry['created_at']) ?></td>
                        <td>
                            <a href="inquiries_edit.php?id=<?= $inquiry['id'] ?>" class="btn btn-sm btn-outline-primary">View/Edit</a>
                            <form method="post" action="inquiries_delete.php" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this inquiry?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php
    $pages = max(1, ceil($total / $limit));
    $baseUrl = 'inquiries_view.php?q=' . urlencode($q) . '&status=' . urlencode($statusFilter);
    ?>
    <?php if ($pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($p = 1; $p <= $pages; $p++) : ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl ?>&page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <?php include "../includes/footer.php"; ?>
</body>

</html>

