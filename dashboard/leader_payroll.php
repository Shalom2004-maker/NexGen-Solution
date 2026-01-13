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
        audit_log('csrf', 'Invalid CSRF token on leader_payroll', $_SESSION['uid'] ?? null);
        die('Invalid request');
    }

    if (!is_numeric($_POST["month"]) || $_POST["month"] < 1 || $_POST["month"] > 12) {
        die("Invalid month");
    }

    $stmt = $conn->prepare("INSERT INTO payroll_inputs(employee_id,month,year,overtime_hours,bonus,deductions,submitted_by) 
                          VALUES(?,?,?,?,?,?,?)");
    $stmt->bind_param("iiidddi", $_POST["emp"], $_POST["month"], $_POST["year"], $_POST["ot"], $_POST["bonus"], $_POST["ded"], $_SESSION["uid"]);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        audit_log('payroll_submit', "Payroll input added for emp {$_POST['emp']}", $_SESSION['uid'] ?? null);
    } else {
        audit_log('payroll_failed', "Failed to add payroll input for emp {$_POST['emp']}", $_SESSION['uid'] ?? null);
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
                <a href="/dashboard/employee.php">Dashboard</a><br>
                <a href="/dashboard/tasks.php">Tasks</a><br>
                <a href="/dashboard/leave.php">Leave</a><br>
                <a href="../public/logout.php">Logout</a>
            </div>
            <div class="col-md-9">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input name="emp" class="form-control" placeholder="Employee ID">
                    <input name="month" class="form-control mt-2">
                    <input name="year" class="form-control mt-2">
                    <input name="ot" class="form-control mt-2" placeholder="Overtime Hours">
                    <input name="bonus" class="form-control mt-2">
                    <input name="ded" class="form-control mt-2">
                    <button class="btn btn-primary mt-3">Submit</button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>
<?php
include "../includes/footer.php"; ?>