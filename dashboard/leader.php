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

    <link href="../css/colors.css" rel="stylesheet">
    <link href="../css/theme.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">

    <style>
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .action-card {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 1rem;
        min-height: 180px;
        padding: 1.35rem;
        border-radius: 1rem;
        border: 1px solid hsl(var(--border) / 0.72);
        background: hsl(var(--card));
        box-shadow: var(--shadow-sm);
        color: var(--text);
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .action-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: var(--text);
    }

    .action-card-icon {
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: hsl(var(--primary) / 0.14);
        color: var(--accent-color);
    }

    .action-card h5 {
        margin: 0;
        font-weight: 700;
    }

    .action-card small {
        color: var(--muted-text);
        line-height: 1.5;
    }
    </style>
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
                <div class="page-header mb-4">
                    <h3>Welcome, Project Leader</h3>
                    <p>Manage your team tasks, approvals, and payroll submissions</p>
                </div>

                <div class="action-grid">
                    <a href="leader_tasks.php" class="action-card">
                        <div class="action-card-icon">
                            <i class="bi bi-list-task"></i>
                        </div>
                        <div>
                            <h5>Manage Tasks</h5>
                            <small>Assign work, follow progress, and keep the team aligned.</small>
                        </div>
                    </a>

                    <a href="leader_leave.php" class="action-card">
                        <div class="action-card-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <h5>Review Leave</h5>
                            <small>Check pending requests and send leader recommendations forward.</small>
                        </div>
                    </a>

                    <a href="leader_payroll.php" class="action-card">
                        <div class="action-card-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <h5>Submit Payroll</h5>
                            <small>Send overtime, bonus, and deduction inputs for HR processing.</small>
                        </div>
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


