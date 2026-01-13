<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";
include "../includes/logger.php";

$uid = $_SESSION["uid"];
// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
// process only on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        audit_log('csrf', 'Invalid CSRF token on leave', $_SESSION['uid'] ?? null);
        die('Invalid request');
    }

    // ensure the user has an employee record
    $empQ = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
    $empQ->bind_param('i', $uid);
    $empQ->execute();
    $empR = $empQ->get_result()->fetch_assoc();
    $empQ->close();

    if (empty($empR)) {
        $error = "No employee record found for current user.";
    } else {
        $employee_id = (int)$empR['id'];
        $stmt = $conn->prepare("INSERT INTO leave_requests(employee_id,start_date,end_date,leave_type,reason) VALUES(?,?,?,?,?)");
        $stmt->bind_param("issss", $employee_id, $_POST["start"], $_POST["end"], $_POST["type"], $_POST["reason"]);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            audit_log('leave_request', "Leave request submitted by user {$uid}", $_SESSION['uid'] ?? null);
            // Redirect to view after successful submission
            header('Location: leave_view.php');
            exit();
        } else {
            audit_log('leave_request_failed', "Failed leave request by user {$uid}", $_SESSION['uid'] ?? null);
            $error = "Failed to submit leave request.";
        }
        $stmt->close();
    }
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        audit_log('leave_request', "Leave request submitted by user {$uid}", $_SESSION['uid'] ?? null);
        // Redirect to view after successful submission
        header('Location: leave_view.php');
        exit();
    } else {
        audit_log('leave_request_failed', "Failed leave request by user {$uid}", $_SESSION['uid'] ?? null);
        $error = "Failed to submit leave request.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Leave - Employee Dashboard</title>

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
            <div class="col-md-9 mb-2 p-4 ms-auto" style="margin-left:25vw;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Leave Request</h3>
                    <a href="leave_view.php" class="btn btn-secondary">View Leave Requests</a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <div class="card p-4 shadow-sm mt-5"
                    style="width: 70%; height: 95vh; background-color: white; margin-left: auto; margin-right: auto; margin-bottom: 2rem;">
                    <form method="POST" action="leave.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <div class="mb-3">
                            <label for="start" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start" name="start" required>
                        </div>
                        <div class="mb-3">
                            <label for="end" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end" name="end" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Leave Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select Leave Type</option>
                                <option value="sick">Sick Leave</option>
                                <option value="annual">Annual Leave</option>
                                <option value="unpaid">Unpaid Leave</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Leave Request</button>
                    </form>
                </div>
            </div>
        </div>

        <body>

</html>