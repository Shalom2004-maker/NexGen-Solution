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

// Modal create user
$create_error = '';
$create_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $create_error = 'Invalid request (CSRF).';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $pass = $_POST['pass'] ?? '';
        $role_id = isset($_POST['role']) ? (int)$_POST['role'] : 0;

        if ($name === '' || strlen($name) < 2) {
            $create_error = 'Enter a valid name.';
        } elseif (!$email) {
            $create_error = 'Enter a valid email address.';
        } elseif (strlen($pass) < 6) {
            $create_error = 'Password must be at least 6 characters.';
        } elseif ($role_id <= 0) {
            $create_error = 'Select a valid role.';
        }

        if (empty($create_error)) {
            $rstmt = $conn->prepare("SELECT id FROM roles WHERE id = ?");
            $rstmt->bind_param('i', $role_id);
            $rstmt->execute();
            $rres = $rstmt->get_result();
            if ($rres->num_rows !== 1) {
                $create_error = 'Selected role does not exist.';
            }
            $rstmt->close();
        }

        if (empty($create_error)) {
            $cstmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $cstmt->bind_param('s', $email);
            $cstmt->execute();
            $cres = $cstmt->get_result();
            if ($cres->num_rows > 0) {
                $create_error = 'A user with that email already exists.';
            }
            $cstmt->close();
        }

        if (empty($create_error)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users(full_name,email,password_hash,role_id) VALUES(?,?,?,?)");
            $stmt->bind_param("sssi", $name, $email, $hash, $role_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                if (function_exists('audit_log')) {
                    audit_log('create_user', "Created user {$email}", $_SESSION['uid'] ?? null);
                }
                $create_success = 'User created successfully!';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                $_POST = [];
            } else {
                $create_error = 'Failed to create user.';
                if (function_exists('audit_log')) {
                    audit_log('create_user_failed', "Failed to create user {$email}", $_SESSION['uid'] ?? null);
                }
            }
            $stmt->close();
        }
    }
}

// Roles for modal
$roles = $conn->query("SELECT * FROM roles ORDER BY role_name");

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
<?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<button class="sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleSidebar()" title="Toggle Sidebar">
    <i class="bi bi-list"></i>
</button>
<div class="main-content">
    <div class="dashboard-shell">
        <div class="page-header d-flex justify-content-between align-items-center end-0 mb-5">
            <div>
                <h3>Users Management</h3>
            </div>
            <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus"></i> &nbsp; Create New User
            </button>
        </div>

        <?php if (!empty($create_error)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($create_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php elseif (!empty($create_success)) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($create_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="get" class="search-form mb-5">
            <div class="row g-2">
                <div class="col-12 col-md-8">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control"
                            placeholder="Search by name or email">
                    </div>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2 align-items-center">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                    <?php if ($q): ?>
                    <a href="admin_user_view.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Users Table -->
        <div class="table-responsive rounded shadow">
            <table class="table table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th class="d-none d-sm-table-cell">Role</th>
                        <th>Status</th>
                        <th class="d-none d-md-table-cell">Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $res->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td class="d-none d-sm-table-cell"><?= htmlspecialchars($user['role_name'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= htmlspecialchars($user['status']) ?>
                            </span>
                        </td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($user['created_at']) ?></td>
                        <td>
                            <div class="action-buttons d-flex gap-2 flex-wrap">
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

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="create_user" value="1">

                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" required
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Enter full name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Enter email address">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="pass" required minlength="6"
                            placeholder="Enter password (min. 6 characters)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select a role</option>
                            <?php if ($roles): ?>
                            <?php while ($r = $roles->fetch_assoc()) : ?>
                            <option value="<?= htmlspecialchars($r['id']) ?>"
                                <?= (isset($_POST['role']) && (int)$_POST['role'] === (int)$r['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['role_name']) ?>
                            </option>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Create User</button>
                </div>
            </form>
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

    const showCreateModal = <?= !empty($create_error) ? 'true' : 'false' ?>;
    if (showCreateModal) {
        const createModal = document.getElementById('createUserModal');
        if (createModal && window.bootstrap) {
            const modalInstance = new bootstrap.Modal(createModal);
            modalInstance.show();
        }
    }
});
</script>
</body>

</html>
