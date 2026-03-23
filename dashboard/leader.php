<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Leader Dashboard</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <div class="page-header">
                <h3>Welcome, Project Leader</h3>
                <p>Manage your team tasks, approvals, and payroll submissions</p>
            </div>

            <div class="action-grid">
                <a href="leader_tasks.php" class="action-card primary">
                    <i class="bi bi-list-task"></i>
                    <h5>Manage Tasks</h5>
                    <small>Assign and track team tasks</small>
                </a>

                <a href="leader_leave.php" class="action-card warning">
                    <i class="bi bi-calendar-check"></i>
                    <h5>Approve Leave</h5>
                    <small>Review team leave requests</small>
                </a>

                <a href="leader_payroll.php" class="action-card success">
                    <i class="bi bi-currency-dollar"></i>
                    <h5>Submit Payroll</h5>
                    <small>Submit team payroll information</small>
                </a>
            </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const nexgenSidebar = document.getElementById('nexgenSidebar');

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            if (nexgenSidebar) {
                nexgenSidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            }
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            if (nexgenSidebar) {
                nexgenSidebar.classList.remove('show');
            }
            sidebarOverlay.classList.remove('show');
        });
    }
    </script>
</body>

</html>


