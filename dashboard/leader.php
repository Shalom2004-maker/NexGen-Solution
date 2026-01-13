<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
include "../includes/header.php";
?>

<!DOCTYPE html>
<html>

<head>
    <title>Project Leader Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">

        <h3>Welcome, Project Leader</h3>

        <div class="row mt-4">
            <div class="col-md-4">
                <a href="leader_tasks.php" class="btn btn-primary w-100 p-3">Manage Tasks</a>
            </div>

            <div class="col-md-4">
                <a href="leader_leave.php" class="btn btn-warning w-100 p-3">Approve Leave</a>
            </div>

            <div class="col-md-4">
                <a href="leader_payroll.php" class="btn btn-success w-100 p-3">Submit Payroll</a>
            </div>
        </div>

    </div>

</body>

</html>