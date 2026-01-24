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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Osward", sans-serif;
    }

    html,
    body {
        background-color: #ececece8;
        min-height: 100vh;
    }

    .main-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        background-color: #f5f5f5d2;
        padding: 2rem;
        overflow-y: auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-header h3 {
        font-weight: bold;
        color: #333;
        margin: 0;
    }

    .search-form {
        width: 100%;
        max-width: 500px;
    }

    .table-container {
        background-color: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow-x: auto;
    }

    .table {
        margin: 0;
    }

    .table thead {
        background-color: #337ccfe2;
        color: white;
    }

    .table tbody tr {
        border-bottom: 1px solid #d4d4d4;
    }

    .table tbody tr:hover {
        background-color: #f9f9f9;
    }

    .badge {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .action-buttons form {
        display: inline;
        margin: 0;
    }

    .action-buttons button {
        padding: 0.4rem 0.6rem;
        font-size: 0.85rem;
    }

    .pagination {
        margin-top: 2rem;
        justify-content: center;
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
        padding: 0.5rem 0.75rem;
        border-radius: 5px;
        cursor: pointer;
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
            padding: 1.5rem;
            padding-top: 3.5rem;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .page-header h3 {
            width: 100%;
        }

        .search-form {
            max-width: 100%;
        }

        .table-container {
            padding: 1rem;
        }

        .table {
            font-size: 0.9rem;
        }

        .table th,
        .table td {
            padding: 0.5rem !important;
        }

        .action-buttons {
            gap: 0.25rem;
        }

        .action-buttons button {
            padding: 0.3rem 0.5rem;
            font-size: 0.75rem;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
        }

        .table-container {
            padding: 0.75rem;
        }

        .table {
            font-size: 0.8rem;
        }

        .table th,
        .table td {
            padding: 0.25rem !important;
        }

        .page-header h3 {
            font-size: 1.25rem;
        }

        .action-buttons {
            flex-direction: column;
            gap: 0.25rem;
        }

        .action-buttons button {
            width: 100%;
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
            <?php include "admin_siderbar.php"; ?>
        </div>

        <div class="main-content">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const nexgenSidebar = document.getElementById('nexgenSidebar');

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            if (nexgenSidebar) {
                nexgenSidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            }
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            if (nexgenSidebar) {
                nexgenSidebar.classList.remove('show');
            }
            sidebarOverlay.classList.remove('show');
        });
    }
    </script>
</body>

</html>