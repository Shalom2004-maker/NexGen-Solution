<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";
include "../includes/header.php";

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
</head>

<body>

    <div class="container mt-4">
        <h3>My Profile</h3>

        <table class="table table-bordered">
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
</body>

</html>
<?php
include "../includes/footer.php";?>