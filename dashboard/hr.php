<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
include "../includes/header.php";
?>

<!DOCTYPE html>
<html>

<head>
    <title>HR Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">

        <h3>HR Dashboard</h3>

        <div class="row mt-4">

            <div class="col-md-3">
                <a href="hr_leave.php" class="btn btn-warning w-100 p-3">Leave Approvals</a>
            </div>

            <div class="col-md-3">
                <a href="hr_payroll.php" class="btn btn-success w-100 p-3">Payroll</a>
            </div>

            <div class="col-md-3">
                <a href="hr_inquiries.php" class="btn btn-primary w-100 p-3">Inquiries</a>
            </div>

            <div class="col-md-3">
                <a href="admin_user.php" class="btn btn-dark w-100 p-3">Employees</a>
            </div>

        </div>

    </div>

</body>

</html>