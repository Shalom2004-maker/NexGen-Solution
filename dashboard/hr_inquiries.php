<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";

if (isset($_GET["reply"])) {
    $id = intval($_GET["reply"]);
    $stmt = $conn->prepare("UPDATE inquiries SET status='replied' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: hr_inquiries.php');
    exit();
}

if (isset($_GET["close"])) {
    $id = intval($_GET["close"]);
    $stmt = $conn->prepare("UPDATE inquiries SET status='closed' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: hr_inquiries.php');
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Inquiries - NexGen Solution</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>

<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button">
        <i class="bi bi-list"></i>
    </button>

    <div class="main-wrapper">
        <div id="sidebarContainer">
            <?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <div>
                        <h3 class="mb-1">Website Inquiries</h3>
                        <p class="text-muted mb-0">Track and respond to incoming inquiries</p>
                    </div>
                    <a href="inquiries_dashboard.php" class="btn btn-primary">
                        <i class="bi bi-eye"></i>&nbsp; View Inquiries</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped m-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                    <a href="hr_inquiries.php?reply=<?= $i["id"] ?>" class="btn btn-sm btn-primary">Mark
                                        Replied</a>
                                    <a href="hr_inquiries.php?close=<?= $i["id"] ?>"
                                        class="btn btn-sm btn-danger">Close</a>
                                </td>
                            </tr>
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const nexgenSidebar = document.getElementById('nexgenSidebar');

        if (sidebarToggleBtn && nexgenSidebar) {
            sidebarToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                nexgenSidebar.classList.toggle('show');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('show');
                }
            });
        }

        if (sidebarOverlay && nexgenSidebar) {
            sidebarOverlay.addEventListener('click', function() {
                nexgenSidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        if (nexgenSidebar) {
            document.querySelectorAll('.nexgen-sidebar-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        nexgenSidebar.classList.remove('show');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('show');
                        }
                    }
                });
            });
        }
    });
    </script>
</body>

</html>

