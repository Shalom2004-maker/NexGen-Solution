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
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// prepare user list for select
$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");

// build base where and params (use aliases when joining users)
$where = '';
$params = [];
$types = '';
if (!in_array($role, ['ProjectLeader', 'Admin'], true)) {
    $where = 'WHERE t.assigned_to = ?';
    $params[] = $uid;
    $types .= 'i';
}
if ($q !== '') {
    $like = "%{$q}%";
    if ($where === '') {
        $where = 'WHERE (t.title LIKE ? OR t.description LIKE ?)';
    } else {
        $where .= ' AND (t.title LIKE ? OR t.description LIKE ?)';
    }
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// count (from tasks aliased)
$countSql = "SELECT COUNT(*) as c FROM tasks t " . $where;
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

// fetch with limit/offset and join user names for assigned_to / created_by
$sql = "SELECT t.id, t.project_id, p.project_name, t.assigned_to, t.created_by, t.title, t.description, t.status, t.deadline,
t.created_at, au.full_name AS assigned_name, cu.full_name AS created_name FROM tasks t LEFT JOIN users au ON
t.assigned_to = au.id LEFT JOIN users cu ON t.created_by = cu.id LEFT JOIN projects p ON t.project_id = p.id " . $where . " ORDER BY t.created_at DESC LIMIT ?
OFFSET ?";
$stmt = $conn->prepare($sql);
// bind params
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
    <title>Task View NexGen Solution</title>

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
        min-height: 100vh;
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
                <h5>Employee</h5><a href="employee.php" class="d-block mb-2 bi bi-columns-gap">&nbsp;
                    &nbsp;
                    Dashboard</a><a href="tasks.php" class="d-block mb-2 bi bi-suitcase-lg">&nbsp;
                    &nbsp;
                    My Tasks</a><a href="leave.php" class="d-block mb-2 bi bi-file-text">&nbsp;
                    &nbsp;
                    Request Leave</a><a href="salary.php" class="d-block mb-2 bi bi-coin">&nbsp;
                    &nbsp;
                    My Salary</a>
                <hr>
                <div class="d-flex justify-content-center align-items-center mt-4"><span
                        style="width: 50px; height: 50px; background-color: #337ccfe2; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; color: white; font-weight: bold;"><?= substr($_SESSION['name'] ?? 'User', 0, 1) ?></span>&nbsp;
                    &nbsp;
                    &nbsp;
                    &nbsp;
                    <span class="me-3"><b><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></b><br>
                        <font style="font-size: 13px; color: lightslategray;">
                            <?= htmlspecialchars($_SESSION['role'] ?? '') ?></font>
                    </span>
                </div>
                <center>
                    <a href="../public/login.php" type="submit"
                        class="btn btn-outline-danger w-75 text-align-start bi bi-box-arrow-right mt-3">&nbsp;

                        Logout </a>
                </center>
            </div>
            <div class="col-md-9 ms-auto p-4" style="margin-left:25vw;">
                <h3>View Tasks</h3>
                <a href="employee.php" style="margin-top: .5rem; margin-left: 47.7rem;" class="btn btn-secondary">Back
                    Dashboard</a>
                <hr>
                <div class="col-md-12 mt-5 border rounded shadow d-flex justify-content-center align-items-center p-3">
                    <table class="table table-light-striped table-hover mt-3">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Project_id</th>
                                <th>Assigned_to</th>
                                <th>Created_by</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Created_at</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody><?php while ($t = $res->fetch_assoc()) : ?><tr>
                                <td><?= htmlspecialchars($t['id']) ?></td>
                                <td><?php if (!empty($t['project_name'])) {
                                    echo htmlspecialchars($t['project_name']) . ' (' . htmlspecialchars($t['project_id']) . ')';
                                } else {
                                    echo htmlspecialchars($t['project_id']);
                                }

                                ?></td>
                                <td><?php if (!empty($t['assigned_name'])) {
                                    echo htmlspecialchars($t['assigned_name']) . ' (' . htmlspecialchars($t['assigned_to']) . ')';
                                } else {
                                    echo htmlspecialchars($t['assigned_to']);
                                }

                                ?></td>
                                <td><?php if (!empty($t['created_name'])) {
                                    echo htmlspecialchars($t['created_name']) . ' (' . htmlspecialchars($t['created_by']) . ')';
                                } else {
                                    echo htmlspecialchars($t['created_by']);
                                }

                                ?></td>
                                <td><?= htmlspecialchars($t['title']) ?></td>
                                <td><?= htmlspecialchars($t['description']) ?></td>
                                <td><?= htmlspecialchars($t['status']) ?></td>
                                <td><?= htmlspecialchars($t['deadline']) ?></td>
                                <td><?= htmlspecialchars($t['created_at']) ?></td>
                                <td><a href="tasks_edit.php?id=<?= $t['id'] ?>"
                                        class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" action="tasks_update.php" style="display:inline"><input
                                            type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input
                                            type="hidden" name="action" value="toggle_status"><input type="hidden"
                                            name="task_id" value="<?= htmlspecialchars($t['id']) ?>"><button
                                            class="btn btn-sm btn-<?= $t['status'] === 'done' ? 'warning' : 'success' ?>"><?= $t['status'] === 'done' ? 'Undo' : 'Mark Done' ?></button>
                                    </form>
                                    <?php if (in_array($role, ['ProjectLeader', 'Admin'], true) || $t['created_by'] == $uid) : ?>
                                    <form method="post" action="task_delete.php" style="display:inline"
                                        onsubmit="return confirm('Delete task?')"><input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input
                                            type="hidden" name="action" value="delete"><input type="hidden"
                                            name="task_id" value="<?= htmlspecialchars($t['id']) ?>"><button
                                            class="btn btn-sm btn-danger">Delete</button></form><?php endif;
                                                                                                ?>
                                </td>
                            </tr><?php endwhile;
                                ?></tbody>
                    </table><?php $pages = max(1, ceil($total / $limit));
                                $baseUrl = 'tasks_view.php?q=' . urlencode($q);
                                ?><?php if ($pages > 1): ?><nav aria-label="Page navigation">
                        <ul class="pagination"><?php for ($p = 1; $p <= $pages; $p++) : ?><li
                                class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link"
                                    href="<?= $baseUrl ?>&page=<?= $p ?>"><?= $p ?></a></li><?php endfor;
                                                                                            ?></ul>
                    </nav><?php endif;
                        ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>