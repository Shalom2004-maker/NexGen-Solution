<?php
include "../includes/auth.php";
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
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build search query based on role
$where = '';
$params = [];
$types = '';

// Employees see only their own leave requests
if ($role === 'Employee') {
    // Get employee_id from user_id
    $empStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
    $empStmt->bind_param('i', $uid);
    $empStmt->execute();
    $empResult = $empStmt->get_result();
    if ($empRow = $empResult->fetch_assoc()) {
        $where = 'WHERE l.employee_id = ?';
        $params[] = $empRow['id'];
        $types .= 'i';
    }
    $empStmt->close();
}

if ($statusFilter !== '' && $where === '') {
    $where = 'WHERE l.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
} elseif ($statusFilter !== '') {
    $where .= ' AND l.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

// Count total
$countSql = "SELECT COUNT(*) as c FROM leave_requests l " . $where;
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

// Fetch leave requests with employee names
$sql = "SELECT l.*, e.user_id, u.full_name AS employee_name 
        FROM leave_requests l 
        JOIN employees e ON l.employee_id = e.id 
        JOIN users u ON e.user_id = u.id " . $where . " 
        ORDER BY l.applied_at DESC LIMIT ? OFFSET ?";
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
    <title>Leave Management</title>

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

    .col-md-3 {
        min-height: 100vh;
        background-color: #ececece8;
        color: black;
        box-shadow: inset 0 0 10px #aaaaaa;
    }

    h3,
    h4 {
        font-weight: bold;
    }

    a.d-block,
    h5 {
        text-decoration: none;
        color: lightslategray;
        padding-top: .7rem;
        text-indent: 1.5rem;
        padding-bottom: .7rem;
    }

    a:hover {
        color: white;
        background-color: #337ccfe2;
        border-radius: 5px;
    }

    .col-md-9 {
        background-color: #f5f5f5d2;
        min-height: 110vh;
    }

    .col-md-2 {
        width: 15vw;
        border: 1px solid #d4d4d4;
    }

    h6 {
        padding-top: .5rem;
        margin-left: .5rem;
    }

    p {
        color: lightslategray;
    }

    button {
        margin-top: 1.5rem;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 bg-light p-3 position-fixed">
                <h3 style="margin-top: .5rem; padding-left: 1.5rem;">NexGen Solution</h3>
                <p style="margin-top: .5rem; padding-left: 1.5rem;">Employee Management</p>
                <hr>
                <h5>Employee</h5>
                <a href="employee.php" class="d-block mb-2 bi bi-columns-gap"> &nbsp;&nbsp; Dashboard</a>
                <a href="tasks.php" class="d-block mb-2 bi bi-suitcase-lg"> &nbsp;&nbsp; My Tasks</a>
                <a href="leave.php" class="d-block mb-2 bi bi-file-text"> &nbsp;&nbsp; Request Leave</a>
                <a href="salary.php" class="d-block mb-2 bi bi-coin"> &nbsp;&nbsp; My Salary</a>
                <hr>

                <div class="d-flex justify-content-center align-items-center mt-4">
                    <span
                        style="width: 50px; height: 50px; background-color: #337ccfe2; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; color: white; font-weight: bold;">
                        <?= substr($_SESSION['name'] ?? 'User', 0, 1) ?>
                    </span> &nbsp;&nbsp; &nbsp;&nbsp;
                    <span class="me-3"><b><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></b><br>
                        <font style="font-size: 13px; color: lightslategray;">
                            <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
                        </font>
                    </span>
                </div>
                <center>
                    <a href="../public/logout.php" type="submit"
                        class="btn btn-outline-danger w-75 text-align-start bi bi-box-arrow-right mt-3">&nbsp;
                        &nbsp; Logout
                    </a>

                </center>
            </div>
        </div>
        <div class="col-md-9 ms-auto p-4" style="margin-left:25vw;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><?= $role === 'Employee' ? 'Leave Requests' : 'Leave Requests' ?></h3>
                <?php if ($role === 'Employee'): ?>
                <a href="leave.php" class="btn btn-primary">Apply for Leave</a>
                <?php endif; ?>
            </div>

            <!-- Filter -->
            <form method="GET" class="mb-3 w-100">
                <div class="d-flex gap-4">
                    <div class="col-md-4">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending
                            </option>
                            <option value="leader_approved"
                                <?= $statusFilter === 'leader_approved' ? 'selected' : '' ?>>
                                Leader
                                Approved</option>
                            <option value="hr_approved" <?= $statusFilter === 'hr_approved' ? 'selected' : '' ?>>HR
                                Approved
                            </option>
                            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-secondary">Filter</button>
                    </div>
                    <div class="col-md-4">
                        <?php if ($statusFilter): ?>
                        <a href="leave_view.php" class="btn btn-link">Reset</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <div class="table-responsive rounded shadow">
                <div class="col-md-12 border rounded d-flex justify-content-center align-items-center p-3">

                    <table class="table table-striped table-hover border">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <?php if ($role !== 'Employee'): ?>
                                <th>Employee</th>
                                <?php endif; ?>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Leave Type</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Applied At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($leave = $res->fetch_assoc()) : ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['id']) ?></td>
                                <?php if ($role !== 'Employee'): ?>
                                <td><?= htmlspecialchars($leave['employee_name']) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($leave['start_date']) ?></td>
                                <td><?= htmlspecialchars($leave['end_date']) ?></td>
                                <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                                <td><?= htmlspecialchars(substr($leave['reason'], 0, 30)) ?><?= strlen($leave['reason']) > 30 ? '...' : '' ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?=
                                                                $leave['status'] === 'hr_approved' ? 'success' : ($leave['status'] === 'leader_approved' ? 'warning' : ($leave['status'] === 'rejected' ? 'danger' : 'secondary'))
                                                                ?>">
                                        <?= htmlspecialchars($leave['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($leave['applied_at']) ?></td>
                                <td>
                                    <a href="leave_edit.php?id=<?= $leave['id'] ?>"
                                        class="btn btn-sm btn-outline-primary">View/Edit</a>
                                    <?php if ($role === 'Employee' && $leave['status'] === 'pending'): ?>
                                    <form method="post" action="leave_delete.php" style="display:inline"
                                        onsubmit="return confirm('Are you sure you want to cancel this leave request?')">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                        <button class="btn btn-sm btn-danger">Cancel</button>
                                    </form>
                                    <?php endif; ?>
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
            $baseUrl = 'leave_view.php?status=' . urlencode($statusFilter);
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