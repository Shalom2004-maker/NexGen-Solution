<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
include "../includes/header.php";

if(isset($_GET["approve"])){
    $id = intval($_GET["approve"]);
    $uid = intval($_SESSION["uid"]);
    
    $stmt = $conn->prepare("UPDATE leave_requests SET status='leader_approved', leader_id=? WHERE id=?");
    $stmt->bind_param("ii", $uid, $id);
    $stmt->execute();
    $stmt->close();
}

$res = $conn->query("SELECT * FROM leave_requests WHERE status='pending'");
?>

<!DOCTYPE html>
<html>

<body class="container">
    <h3>Team Leave Requests</h3>
    <table class="table">
        <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Dates</th>
            <th>Action</th>
        </tr>
        <?php while($row=$res->fetch_assoc()){ ?>
        <tr>
            <td><?=$row["id"]?></td>
            <td><?=$row["employee_id"]?></td>
            <td><?=$row["start_date"]?> to <?=$row["end_date"]?></td>
            <td><a href="?approve=<?=$row["id"]?>" class="btn btn-success">Approve</a></td>
        </tr>
        <?php } ?>
    </table>
</body>

</html>
<?php
include "../includes/footer.php"; ?>