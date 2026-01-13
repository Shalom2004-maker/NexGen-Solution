<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
include "../includes/header.php";
include "../includes/logger.php";
// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
// process only on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        audit_log('csrf', 'Invalid CSRF token on leader_tasks', $_SESSION['uid'] ?? null);
        die('Invalid request');
    }

    $stmt = $conn->prepare("INSERT INTO tasks(project_id,assigned_to,created_by,title,description,deadline) 
                          VALUES(?,?,?,?,?,?)");
    $stmt->bind_param("iiisss", $_POST["project"], $_POST["user"], $_SESSION["uid"], $_POST["title"], $_POST["desc"], $_POST["deadline"]);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        audit_log('task_create', "Task created for project {$_POST['project']}", $_SESSION['uid'] ?? null);
    } else {
        audit_log('task_create_failed', "Failed to create task for project {$_POST['project']}", $_SESSION['uid'] ?? null);
        echo "Failed";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3 bg-light p-3">
                <a href="dashboard/employee.php">Dashboard</a><br>
                <a href="dashboard/tasks.php">Tasks</a><br>
                <a href="dashboard/leave.php">Leave</a><br>
                <a href="../public/logout.php">Logout</a>
            </div>
            <div class="col-md-9">
                <h3>Create Task</h3>
                <form method="post">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input name="project" class="form-control" placeholder="Project ID">
                    <input name="user" class="form-control mt-2" placeholder="Employee User ID">
                    <input name="title" class="form-control mt-2">
                    <textarea name="desc" class="form-control mt-2"></textarea>
                    <input type="date" name="deadline" class="form-control mt-2">
                    <button class="btn btn-success mt-2">Create</button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>
<?php
include "../includes/footer.php"; ?>