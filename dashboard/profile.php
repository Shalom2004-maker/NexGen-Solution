<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";

$uid = intval($_SESSION["uid"]);

$stmt = $conn->prepare("SELECT users.full_name, users.email, employees.job_title, employees.department, employees.hire_date, 
employees.salary_base FROM users JOIN employees ON users.id = employees.user_id WHERE users.id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$q = $stmt->get_result();

$data = $q->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">
    <style>
    * {
        box-sizing: border-box;
        font-family: "Sora", sans-serif;
    }

    body {
        background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
        color: #1f2937;
    }

    .dashboard-shell {
        background: radial-gradient(1200px 400px at 20% -10%, rgba(30, 64, 175, 0.12), transparent 60%),
            radial-gradient(800px 300px at 90% 10%, rgba(14, 116, 144, 0.12), transparent 60%);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
    }

    .table-responsive {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        overflow: hidden;
        background: #ffffff;
    }

    .table th {
        background-color: #f8fafc;
        color: #334155;
        font-weight: 600;
    }
    </style>
</head>

<body>

    <div class="container mt-4">
        <div class="dashboard-shell">
            <h3 class="mb-3">My Profile</h3>
            <div class="table-responsive">
                <table class="table table-bordered m-0">
                    <tr>
                        <th>Name</th>
                        <td><?= $data["full_name"] ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?= $data["email"] ?></td>
                    </tr>
                    <tr>
                        <th>Job Title</th>
                        <td><?= $data["job_title"] ?></td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td><?= $data["department"] ?></td>
                    </tr>
                    <tr>
                        <th>Hire Date</th>
                        <td><?= $data["hire_date"] ?></td>
                    </tr>
                    <tr>
                        <th>Base Salary</th>
                        <td><?= $data["salary_base"] ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>