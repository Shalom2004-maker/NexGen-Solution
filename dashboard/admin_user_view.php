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
$role = $_SESSION['role'] ?? '';

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
    $like = "%{$q}%";
    $where = 'WHERE (u.full_name LIKE ? OR u.email LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Count total
$countSql = "SELECT COUNT(*) as c FROM users u " . $where;
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

// Fetch users with roles
$sql = "SELECT u.id, u.full_name, u.email, u.status, u.created_at, r.role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id " . $where . " 
        ORDER BY u.created_at ASC LIMIT ? OFFSET ?";
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
<?php include __DIR__ . '/../includes/admin_top.php'; ?>
<div class="page-header">
    <h3><i class="bi bi-people-fill"></i> Users Management</h3>
    <a href="admin_user.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Create New User
    </a>
</div>

<!-- Search Form -->
<form method="get" class="search-form mb-4">
    <div class="input-group">
        <span class="input-group-text">
            <i class="bi bi-search"></i>
        </span>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control"
            placeholder="Search by name or email">
        <button class="btn btn-outline-secondary" type="submit">Search</button>
        <?php if ($q): ?>
            <a href="admin_user_view.php" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<!-- Users Table -->
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $res->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role_name'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= htmlspecialchars($user['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="admin_user_edit.php?id=<?= urlencode($user['id']) ?>"
                                    class="btn btn-outline-primary btn-sm" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form method="post" action="admin_user_delete.php"
                                    onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    <input type="hidden" name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php
$pages = max(1, ceil($total / $limit));
$baseUrl = 'admin_user_view.php?q=' . urlencode($q);
?>
<?php if ($pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $baseUrl ?>&page=1">First</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page - 1 ?>">Previous</a>
                </li>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $pages; $p++) : ?>
                <?php if ($p === 1 || $p === $pages || abs($p - $page) <= 1) : ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl ?>&page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php elseif ($p === 2 || $p === $pages - 1) : ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page + 1 ?>">Next</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $pages ?>">Last</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../includes/admin_bottom.php'; ?>