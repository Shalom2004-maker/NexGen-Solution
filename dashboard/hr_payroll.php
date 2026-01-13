<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
include "../includes/header.php";

if (isset($_GET["approve"])) {
    $id = intval($_GET["approve"]);
    $uid = intval($_SESSION["uid"]);

    $updateStmt = $conn->prepare("UPDATE payroll_inputs SET status='approved' WHERE id=?");
    $updateStmt->bind_param("i", $id);
    $updateStmt->execute();
    $updateStmt->close();

    $selectStmt = $conn->prepare("SELECT p.*, e.salary_base FROM payroll_inputs p 
                        JOIN employees e ON p.employee_id=e.id WHERE p.id=?");
    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();
    $data = $selectStmt->get_result()->fetch_assoc();
    $selectStmt->close();

    if ($data) {
        $net = $data["salary_base"] + ($data["overtime_hours"] * 5) + $data["bonus"] - $data["deductions"];

        $stmt = $conn->prepare("INSERT INTO salary_slips(employee_id,month,year,base_salary,overtime_pay,bonus,deductions,net_salary,generated_by) VALUES(?,?,?,?,?,?,?,?,?)");
        // Types: i=employee_id, i=month, i=year, d=base_salary, d=overtime_pay, d=bonus, d=deductions, d=net_salary, i=generated_by
        $employee_id = (int)$data["employee_id"];
        $month = (int)$data["month"];
        $year = (int)$data["year"];
        $base_salary = (float)$data["salary_base"];
        $overtime_pay = (float)($data["overtime_hours"] * 5);
        $bonus = (float)$data["bonus"];
        $deductions = (float)$data["deductions"];
        $net_salary = (float)$net;

        $stmt->bind_param("iiidddddi", $employee_id, $month, $year, $base_salary, $overtime_pay, $bonus, $deductions, $net_salary, $uid);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            echo "Failed";
        }
        $stmt->close();
    }
}

$res = $conn->query("SELECT * FROM payroll_inputs WHERE status='pending'");
?>

<!DOCTYPE html>
<html>

<body class="container">
    <h3>Payroll Approval</h3>
    <table class="table">
        <?php while ($r = $res->fetch_assoc()) { ?>
            <tr>
                <td><?= $r["employee_id"] ?></td>
                <td><?= $r["month"] ?> / <?= $r["year"] ?></td>
                <td><a href="?approve=<?= $r["id"] ?>" class="btn btn-success">Approve</a></td>
            </tr>
        <?php } ?>
    </table>
</body>

</html>
<?php
include "../includes/footer.php"; ?>