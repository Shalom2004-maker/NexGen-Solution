<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
include "../includes/header.php";

if (isset($_GET["reply"])) {
    $id = intval($_GET["reply"]);
    $stmt = $conn->prepare("UPDATE inquiries SET status='replied' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: inquiries_view.php');
    exit();
}

if (isset($_GET["close"])) {
    $id = intval($_GET["close"]);
    $stmt = $conn->prepare("UPDATE inquiries SET status='closed' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: inquiries_view.php');
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Website Inquiries</h3>
            <a href="inquiries_view.php" class="btn btn-primary">View All Inquiries</a>
        </div>

        <table class="table table-bordered table-striped">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Company</th>
                <th>Message</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php
            $query = $conn->query("SELECT * FROM inquiries ORDER BY id DESC");

            if (!$query) {
                die("Database query failed: " . $conn->error);
            } else {
                while ($i = $query->fetch_assoc()) { ?>
            <tr>
                <td><?= $i["name"] ?></td>
                <td><?= $i["email"] ?></td>
                <td><?= $i["company"] ?></td>
                <td><?= $i["message"] ?></td>
                <td><?= $i["status"] ?></td>
                <td>
                    <a href="?reply=<?= $i["id"] ?>" class="btn btn-sm btn-primary">Mark Replied</a>
                    <a href="?close=<?= $i["id"] ?>" class="btn btn-sm btn-danger">Close</a>
                </td>
            </tr>
            <?php }
            } ?>

        </table>
    </div>
</body>

</html>
<?php include "../includes/footer.php"; ?>