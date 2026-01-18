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
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    }

    h3,
    h4 {
        font-weight: bold;
    }

    h5 {
        text-decoration: none;
        color: lightslategray;
        padding-top: .5rem;
        text-indent: 1.5rem;
        padding-bottom: .5rem;
    }

    a:hover {
        color: white;
        background-color: #337ccfe2;
        border-radius: 5px;
    }

    .col-md-9 {
        background-color: #f5f5f5d2;
    }


    h6 {
        padding-top: .5rem;
        margin-left: .5rem;
    }

    h5 {
        font-size: 17px;
    }

    h5 p {
        color: lightslategray;
    }

    form {
        width: 100%;
        padding: 10px 20px;
    }

    div.col-md-4 {
        display: flex;
        gap: 1rem;
    }

    input[type="text"] button {
        margin-top: 0;
    }

    .search-buttons {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    </style>
</head>

<body>
    <div class="container-fluid d-flex p-0">
        <?php include "admin_siderbar.php"; ?>
        <div class="col-md-9 mb-2 p-4 ms-auto" style="margin-left: 25vw;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Users Management</h3>
                <a href="admin_user.php" class="btn btn-outline-primary btn-sm bi bi-plus-circle"> &nbsp; Create New
                    User</a>
            </div>

            <!-- Search -->
            <div class="d-flex justify-content-between align-items-center">
                <form method="get" class="mb-3 w-75">
                    <div class="input-group mb-3">
                        <span type="submit" class="input-group-text btn btn-outline-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-search" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1
                                     1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                            </svg>
                        </span>
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control"
                            placeholder="Search by name or email"> &nbsp;&nbsp;
                        <span style="margin-top: -1.5rem">
                            <button class="btn btn-outline-secondary">Search</button>
                        </span>
                        <?php if ($q): ?>

                        <a href=" admin_user_view.php" class="btn btn-link">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-responsive border bg-light rounded shadow">
                <table class="table table-light border">
                    <thead class="table-primary">
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
                                <form method="post" action="admin_user_delete.php" style="margin-top: -1.3rem"
                                    onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    <input type="hidden" name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button class="btn btn-outline-primary"> <a
                                            href=" admin_user_edit.php?id=<?= $user['id'] ?>"
                                            class="bi bi-pencil-square"></a>
                                    </button> &nbsp;&nbsp;
                                    <button class="btn btn-outline-danger bi bi-trash3-fill"></button>
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
            $baseUrl = 'admin_user_view.php?q=' . urlencode($q);
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
        </div>
    </div>
</body>

</html>