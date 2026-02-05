<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";

$uid = intval($_SESSION["uid"]);

$stmt = $conn->prepare("SELECT users.full_name, users.email, users.profile_photo, employees.job_title, employees.department, employees.hire_date, 
employees.salary_base FROM users JOIN employees ON users.id = employees.user_id WHERE users.id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$q = $stmt->get_result();

$data = $q->fetch_assoc();
$photoPath = $data['profile_photo'] ?? '';
$photoUrl = '';
if ($photoPath) {
    if (preg_match('/^https?:\\/\\//i', $photoPath)) {
        $photoUrl = $photoPath;
    } else {
        $photoUrl = '../' . ltrim($photoPath, '/');
    }
}
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

    .profile-avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 2.2rem;
        overflow: hidden;
        border: 2px solid rgba(29, 78, 216, 0.25);
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    </style>
</head>

<body>

    <div class="container mt-4">
        <div class="dashboard-shell">
            <div class="d-flex justify-content-between align-items-center end-2 mb-3">
                <div>
                    <h3 class="mb-3">My Profile</h3>
                </div>
                <a href="employee.php">
                    <button class="btn btn-outline-primary">Back</button>
                </a>
            </div>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="profile-avatar">
                    <?php if ($photoUrl): ?>
                    <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Profile photo">
                    <?php else: ?>
                    <?= htmlspecialchars(substr($data['full_name'] ?? 'U', 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h5 class="mb-0"><?= htmlspecialchars($data['full_name'] ?? '') ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($data['email'] ?? '') ?></small>
                </div>
            </div>
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
