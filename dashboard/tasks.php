<?php
include "../includes/auth.php";
// allow Employees, Project Leaders and Admins to view/manage tasks
allow(["Employee", "ProjectLeader", "Admin"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

// CSRF token removed: form submissions no longer use per-session CSRF tokens

// session_start();
$uid = (int)($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? '';

$q = trim($_GET['q'] ?? '');

// prepare user list and project list for selects
$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");

// Handle POST actions: only create here; other actions handled by separate endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST;

    $action = $posted['action'] ?? '';
    if ($action === 'create') {
        // only ProjectLeader or Admin can create tasks
        if (!in_array($role, ['ProjectLeader', 'Admin'], true)) {
            http_response_code(403);
            die('Forbidden');
        }
        $project = isset($posted['project']) ? (int)$posted['project'] : null;
        $assigned_to = isset($posted['assigned_to']) ? (int)$posted['assigned_to'] : null;
        $title = trim($posted['title'] ?? '');
        $desc = trim($posted['description'] ?? '');
        $deadline = !empty($posted['deadline']) ? $posted['deadline'] : null;

        if ($title === '') {
            $error = 'Title is required';
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks (project_id, assigned_to, created_by, title, description, status, deadline) VALUES (?,?,?,?,?, 'todo', ?)");
            $stmt->bind_param('iiisss', $project, $assigned_to, $uid, $title, $desc, $deadline);
            if ($stmt->execute()) {
                if (function_exists('audit_log')) audit_log('task_create', "Task {$title} created", $uid);
            }
            $stmt->close();
        }

        // after create, redirect to tasks_view.php
        header('Location: tasks_view.php');
        exit();
    }
    // other actions are handled by dedicated endpoints
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - NexGen Solution</title>
    <!-- Google Fonts Link -->
    <link rel=" preconnect" href="https://fonts.googleapis.com">
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

<body class="container-fluid">
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
        <div class="col-md-9 ms-auto p-4" style="margin-left:25vw;">
            <h3 class="mb-3">My Tasks</h3><?php if (in_array($role, ['ProjectLeader', 'Admin'], true)) : ?><div
                    class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Create Task</h5>
                        <form method="get" action="tasks_view.php" class="row g-2 mb-3">
                            <div class="col-md-4"><input name="q" value="<?= htmlspecialchars($q) ?>" class="form-control"
                                    placeholder="Search title or description"></div>
                            <div class="col-md-2"><button class="btn btn-outline-secondary">Search</button><a
                                    href="tasks.php" class="btn btn-link">Reset</a></div>
                        </form>
                        <form method="post" class="row gy-2"><input type="hidden" name="action" value="create">
                            <div class="col-md-3"><select name="project" class="form-control">
                                    <option value="">Project (optional)</option>
                                    <?php while ($p = $projects->fetch_assoc()) : ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['project_name']) ?>(<?= $p['id'] ?>)
                                        </option><?php endwhile;
                                                    ?>
                                </select></div>
                            <div class="col-md-2"><select name="assigned_to" class="form-control">
                                    <option value="">Assignee</option><?php while ($u = $users->fetch_assoc()) : ?><option
                                            value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?>(<?= $u['id'] ?>)
                                        </option>
                                    <?php endwhile;
                                    ?>
                                </select></div>
                            <div class="col-md-4"><input name="title" class="form-control" placeholder="Title" required>
                            </div>
                            <div class="col-md-4"><input type="date" name="deadline" class="form-control"></div>
                            <div class="col-12"><textarea name="description" class="form-control"
                                    placeholder="Description"></textarea></div>
                            <div class="col-12 text-end"><button class="btn btn-primary">Create</button></div>
                        </form>
                    </div>
                </div><?php endif;
                        ?><div class="alert alert-info"><a href="tasks_view.php" class="btn btn-primary">View All Tasks</a>
            </div>
        </div>
    </div>
</body>

</html>