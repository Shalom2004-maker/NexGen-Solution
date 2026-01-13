<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
include "../includes/header.php";


if(isset($_GET["approve"])){
    $id = intval($_GET["approve"]);
    $uid = intval($_SESSION["uid"]);
    
    $stmt = $conn->prepare("UPDATE leave_requests SET status='hr_approved', hr_id=? WHERE id=?");
    $stmt->bind_param("ii", $uid, $id);
    $stmt->execute();
    $stmt->close();
}

$res=$conn->query("SELECT * FROM leave_requests WHERE status='leader_approved'");
?>

<!DOCTYPE html>
<html>

<body class="container">
    <h3>HR Leave Approval</h3>
    <table class="table">
        <?php while($row=$res->fetch_assoc()){ ?>
        <tr>
            <td><?=$row["employee_id"]?></td>
            <td><?=$row["start_date"]?> to <?=$row["end_date"]?></td>
            <td><a href="?approve=<?=$row["id"]?>" class="btn btn-primary">Approve</a></td>
        </tr>
        <?php } ?>
    </table>
</body>

</html>
<?php include "../includes/footer.php"; ?>