<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
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
    <title>Inquiries Management - NexGen Solution</title>

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
            font-family: "Oswald", sans-serif;
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
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h3 {
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .page-header p {
            color: lightslategray;
            margin: 0;
        }

        .filter-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th {
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #d4d4d4;
            font-weight: 600;
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
                padding: 1.5rem;
                padding-top: 3.5rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
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
                font-size: 1.25rem;
            }

            .table-container th,
            .table-container td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            .filter-container {
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
            <?php include "admin_siderbar.php"; ?>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h3>Website Inquiries</h3>
                    <p>Manage and respond to customer inquiries</p>
                </div>
            </div>

            <div class="filter-container">
                <form method="get" class="row g-2">
                    <div class="col-md-4">
                        <input name="q" value="<?= htmlspecialchars($q) ?>" class="form-control"
                            placeholder="Search by name, email, or company">
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
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <?php if ($q || $statusFilter): ?>
                        <div class="col-md-2">
                            <a href="inquiries_view.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res->num_rows === 0): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem; color: #999;">No inquiries found</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($inquiry = $res->fetch_assoc()) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($inquiry['id']) ?></td>
                                    <td><?= htmlspecialchars($inquiry['name']) ?></td>
                                    <td><?= htmlspecialchars($inquiry['email']) ?></td>
                                    <td><?= htmlspecialchars($inquiry['company']) ?></td>
                                    <td><?= htmlspecialchars(substr($inquiry['message'], 0, 40)) ?><?= strlen($inquiry['message']) > 40 ? '...' : '' ?></td>
                                    <td>
                                        <?php
                                        $statusBadge = $inquiry['status'] === 'new' ? 'primary' : ($inquiry['status'] === 'replied' ? 'warning' : 'secondary');
                                        ?>
                                        <span class="badge bg-<?= $statusBadge ?>">
                                            <?= htmlspecialchars($inquiry['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($inquiry['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="inquiries_edit.php?id=<?= $inquiry['id'] ?>" class="btn btn-sm btn-outline-primary">View/Edit</a>
                                            <form method="post" action="inquiries_delete.php" style="display:inline"
                                                onsubmit="return confirm('Delete this inquiry?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total > $limit): ?>
                <nav class="mt-4" aria-label="Page navigation">
                    <ul class="pagination">
                        <?php
                        $pages = ceil($total / $limit);
                        $baseUrl = 'inquiries_view.php?q=' . urlencode($q) . '&status=' . urlencode($statusFilter);
                        ?>
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
        });
    </script>
</body>

</html>