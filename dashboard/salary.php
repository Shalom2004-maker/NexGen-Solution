<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";

$uid = intval($_SESSION["uid"]);

$stmt = $conn->prepare("SELECT s.* FROM salary_slips s 
                   JOIN employees e ON s.employee_id=e.id 
                   WHERE e.user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Salary - NexGen Solution</title>

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
            <div class="col-md-9 ms-auto p-4" style="margin-left:25vw;">
                <h3>My Salary Slips</h3>
                <p style="margin-top: .7rem;">View your monthly payment history and salary slips.
                </p>
                <hr>
                <div class="col-md-9">
                    <div class="table-responsive rounded shadow">
                        <div class="col-md-12 mt-5 border rounded shadow d-flex 
                            justify-content-center align-items-center p-3">
                            <table class="table table-striped table-hover border">
                                <?php
                                // Check for errors or empty result
                                if ($res === false) {
                                    echo "<tr><td colspan=\"3\">Query error: " . htmlspecialchars($stmt->error) . "</td></tr>";
                                } elseif ($res->num_rows === 0) {
                                    echo "<tr><td colspan=\"3\">No salary slips found.</td></tr>";
                                } else {
                                    echo "<thead><tr><th>ID</th><th>Pay Date</th><th>Amount</th></tr></thead><tbody>";
                                    while ($row = $res->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                        // salary_slips columns: month, year, base_salary, overtime_pay, bonus, deductions, net_salary, created_at
                                        $month = htmlspecialchars($row['month'] ?? '');
                                        $year = htmlspecialchars($row['year'] ?? '');
                                        echo "<td>" . ($month !== '' ? ($month . '/' . $year) : htmlspecialchars($row['created_at'] ?? '')) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['net_salary'] ?? $row['base_salary'] ?? '') . "</td>";
                                        echo "</tr>";
                                    }
                                    echo "</tbody>";
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>