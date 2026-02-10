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

<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap');

* {
    box-sizing: border-box;
    font-family: "Sora", sans-serif;
}

body {
    background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
    color: #1f2937;
}

.main-content {
    margin-left: 16rem;
    padding: 2rem 2.5rem 2rem 2rem;
    transition: margin .2s ease;
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
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
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

.page-header h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.btn-outline-primary {
    border-radius: 999px;
}

.btn-outline-secondary {
    border-radius: 999px;
}

.modal-content {
    border-radius: 18px;
    border: 1px solid rgba(148, 163, 184, 0.4);
    box-shadow: 0 30px 50px rgba(15, 23, 42, 0.2);
}

.modal-header {
    border-bottom: 1px solid rgba(148, 163, 184, 0.3);
    background: linear-gradient(135deg, rgba(29, 78, 216, 0.1), rgba(14, 116, 144, 0.08));
}

.modal-title {
    font-weight: 700;
    color: #0f172a;
}

.modal-body label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.35rem;
}

.modal-body .form-control,
.modal-body .form-select {
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.45);
    padding: 0.65rem 0.8rem;
}

.modal-footer {
    border-top: 1px solid rgba(148, 163, 184, 0.3);
}

.table-responsive {
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.table thead th {
    background-color: #f8fafc;
    color: #334155;
    border-top: none;
    font-weight: 600;
}

.table-responsive table th,
.table-responsive table td {
    vertical-align: middle;
}

.badge {
    border-radius: 999px;
    padding: 0.35rem 0.7rem;
    font-weight: 600;
}

.action-buttons .btn {
    margin-right: .40rem;
}

/* Sidebar overlay for mobile */
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

@media (max-width: 991.98px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
    }

    .dashboard-shell {
        padding: 1rem;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-form .input-group {
        flex-wrap: wrap;
    }
}

@media (max-width: 575.98px) {
    .action-buttons {
        gap: .25rem;
    }
}

@media (max-width: 768px) {
    .nexgen-sidebar {
        position: fixed !important;
        left: -100% !important;
        top: 0 !important;
        width: 70% !important;
        max-width: 300px !important;
        height: 100vh !important;
        z-index: 1050 !important;
        transition: left 0.3s ease !important;
    }

    .nexgen-sidebar.show {
        left: 0 !important;
    }
}
</style>

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