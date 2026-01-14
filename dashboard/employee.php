<?php
include "../includes/auth.php";
allow("Employee");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>

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
    }

    h3,
    h4 {
        font-weight: bold;
    }

    a.d-block,
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

    .col-md-2 {
        width: 15vw;
        border: 1px solid #d4d4d4;
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

    button {
        margin-top: 1.5rem;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 bg-light p-3 position-fixed border-end">
                <div class="d-block border-bottom position-fixed bg-light" style="width: 23.4vw;">
                    <h3 style="margin-top: .5rem; padding-left: 1.5rem;">NexGen Solution</h3>
                    <p style="margin-top: .5rem; padding-left: 1.5rem; color: lightslategray;">Employee Management</p>
                </div>

                <div class="d-block position-relative"
                    style="width: 23.4vw; margin-top: 7rem; overflow-y: auto; height: calc(100vh - 17rem);">
                    <h5>Employee</h5>
                    <a href="employee.php" class="d-block mb-2 bi bi-columns-gap"> &nbsp;&nbsp; Dashboard</a>
                    <a href="tasks.php" class="d-block mb-2 bi bi-suitcase-lg"> &nbsp;&nbsp; My Tasks</a>
                    <a href="leave.php" class="d-block mb-2 bi bi-file-text"> &nbsp;&nbsp; Request Leave</a>
                    <a href="salary.php" class="d-block mb-2 bi bi-coin"> &nbsp;&nbsp; My Salary</a>
                </div>

                <div class="d-block position-fixed bg-light border-top" style="width: 23.4vw;">
                    <div class="d-flex border-top justify-content-center align-items-center">
                        <span
                            style="width: 50px; height: 50px; background-color: #337ccfe2; border-radius: 50%; margin-top: 1.5rem;
                        display: flex; justify-content: center; align-items: center; font-size: 24px; color: white; font-weight: bold;">
                            <?= substr($_SESSION['name'] ?? 'User', 0, 1) ?>
                        </span> &nbsp;&nbsp; &nbsp;&nbsp;
                        <span class="me-3"
                            style="margin-top: 1.5rem;"><b><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></b><br>
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
            <div class="col-md-9 mb-2 p-4 ms-auto" style="margin-left: 25vw;">
                <h3 style="padding-left: 2.3rem;">Employee Dashboard</h3>
                <p style="margin-top: .7rem; padding-left: 2.3rem; color: lightslategray"> Welcome back, Employee.
                    Here's what's
                    happening
                    today.</p>
                <div class="row d-flex gap-4 justify-content-center pt-4">
                    <div class="col-md-2 bg-light rounded text-start shadow">
                        <h6>Pending Tasks <span class="bi bi-stopwatch-fill"
                                style="margin-left: 1.5rem; color: #337ccfe2;"></span></h6>
                        <?php
                        include "../includes/db.php";
                        $id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 1;
                        $role = isset($_SESSION['role']) ? $_SESSION['role'] : $role;
                        $stmt = $conn->prepare("SELECT COUNT(status) FROM tasks WHERE status = 'in_progress' AND assigned_to = ?");
                        if ($stmt) {
                            $stmt->bind_param('i', $id);
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $count = (int)($count ?? 0);
                            echo "<h4 style=\"margin-left: .5rem;\"><b>{$count}</b></h4>";
                            if ($count == 0) {
                                echo "<p style=\"margin-top: .7rem; font-size: 14px; margin-left: .5rem;\">Tasks waiting for action</p>";
                            } else {
                                echo "<p style=\"margin-top: .7rem;  font-size: 14px;  margin-left: .5rem;\">Tasks pending</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">DB error</p>";
                        }
                        ?>
                    </div>
                    <div class="col-md-2 bg-light rounded text-start shadow">
                        <h6>Completed Tasks <span class="bi bi-ui-checks"
                                style="margin-left: .5rem; color: #00a938f3;"></span></h6>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = ?");
                        if ($stmt) {
                            $status = 'done';
                            $stmt->bind_param('is', $id, $status);
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $count = (int)($count ?? 0);
                            echo "<h4 style=\"margin-left: .5rem;\"><b>{$count}</b></h4>";
                            if ($count >= 1) {
                                echo "<p style=\"margin-top: .7rem;  font-size: 14px;  margin-left: .5rem;\">Great job!</p>";
                            } else {
                                echo "<p style=\"margin-top: .7rem;  font-size: 14px;  margin-left: .5rem;\">No tasks completed yet</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">DB error</p>";
                        }
                        ?>
                    </div>
                    <div class="col-md-2 bg-light rounded text-start shadow">
                        <h6>Leave Requests <span class="bi bi-suitcase-lg-fill"
                                style="margin-left: .7rem; color: #bc00e6f3;"></span>
                        </h6>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = ?");
                        if ($stmt) {
                            $stmt->bind_param('i', $id);
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $count = (int)($count ?? 0);
                            echo "<h4  style=\"margin-left: .5rem;\"><b>{$count}</b></h4>";
                            if ($count >= 1) {
                                echo "<p style=\"margin-top: .7rem;  font-size: 14px;  margin-left: .5rem;\">Submitted Leave requests</p>";
                            } else {
                                echo "<p style=\"margin-top: .7rem;  font-size: 14px;  margin-left: .5rem;\">No leave requests yet</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">DB error</p>";
                        }
                        ?>
                    </div>
                    <div class="col-md-2 bg-light rounded text-start shadow">
                        <h6>Latest Salaries <span class="bi bi-coin" style="margin-left: 1.3rem; color: orange;"></span>
                        </h6>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) AS Latest_Salary FROM salary_slips;");
                        if ($stmt) {
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $count = (int)($count ?? 0);
                            echo "<h4  style=\"margin-left: .5rem;\"><b>$" . "{$count}</b></h4>";
                            if ($count >= 1) {
                                echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">Salary credited</p>";
                            } else {
                                echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">No salary data</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p  style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">DB error</p>";
                        }
                        ?>
                    </div>
                    <div class="col-md-9 mb-2 p-4 d-flex gap-3 justify-content-center pt-4">
                        <div class="col-md-8 rounded bg-light p-3 shadow border">
                            <h4 class="mt-2 ml-2">Recent Tasks</h4>
                            <?php
                                $stmt = $conn->prepare("SELECT title, status, created_at FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 5");
                                if ($stmt) {
                                    $stmt->bind_param('i', $id);
                                    $stmt->execute();
                                    $stmt->bind_result($title, $status, $created_at);
                                    echo "<ul class=\"list-group list-group-flush shadow rounded mt-5\">";
                                    while ($stmt->fetch()) {
                                        $status_badge = '';
                                        if ($status === 'in_progress') {
                                            $status_badge = '<span class="badge bg-warning text-dark shadow">In Progress</span>';
                                        } elseif ($status === 'done') {
                                            $status_badge = '<span class="badge bg-success shadow">Completed</span>';
                                        } else {
                                            $status_badge = '<span class="badge bg-secondary shadow">To Do</span>';
                                        }
                                        echo "<li class=\"list-group-item d-flex justify-content-between align-items-center\">
                                                <div>
                                                    <strong>" . htmlspecialchars($title) . "</strong><br>
                                                    <small>Due: " . htmlspecialchars($created_at ?? 'Not set') . "</small>
                                                </div>
                                                <div>" . $status_badge . "</div>
                                              </li>";
                                    }
                                    echo "</ul>";
                                    $stmt->close();
                                } else {
                                    echo "<p>DB error</p>";
                                }
                                ?>
                        </div>
                        <div class="col-md-8 rounded bg-light p-3 shadow border">
                            <h4 class="mt-2 ml-2">My Leave Requests</h4>
                            <?php 
                            $stmt = $conn->prepare("SELECT reason, start_date, end_date, status FROM leave_requests WHERE employee_id = ? ORDER BY applied_at DESC LIMIT 5");
                            if ($stmt) {
                                $stmt->bind_param('i', $id);
                                $stmt->execute();
                                $stmt->bind_result($reason, $start_date, $end_date, $status);
                                echo "<ul class=\"list-group list-group-flush shadow rounded mt-5\">";
                                while ($stmt->fetch()) {
                                    $status_badge = '';
                                    if ($status === 'pending') {
                                        $status_badge = '<span class="badge bg-warning text-dark shadow">Pending</span>';
                                    } elseif ($status === 'approved') {
                                        $status_badge = '<span class="badge bg-success shadow">Approved</span>';
                                    } else {
                                        $status_badge = '<span class="badge bg-danger shadow">Rejected</span>';
                                    }
                                    echo "<li class=\"list-group-item d-flex justify-content-between align-items-center\">
                                            <div>
                                                <strong>" . htmlspecialchars($reason) . "</strong><br>
                                                <small>" . htmlspecialchars($start_date ?? 'Not set') . " to " . htmlspecialchars($end_date ?? 'Not set') . "</small>
                                            </div>
                                            <div>" . $status_badge . "</div>
                                          </li>";
                                }
                                echo "</ul>";
                                $stmt->close();
                            } else {
                                echo "<p>DB error</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>