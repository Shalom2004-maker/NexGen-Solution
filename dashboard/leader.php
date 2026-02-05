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

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Sora", sans-serif;
    }

    html,
    body {
        background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
        color: #1f2937;
        min-height: 100vh;
    }

    .main-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        background-color: transparent;
        padding-top: 2rem;
        padding-left: 18rem;
        padding-right: 2.5rem;
        padding-bottom: 2rem;
        width: 75%;
        overflow-y: auto;
    }

    .dashboard-shell {
        position: relative;
        background: radial-gradient(1200px 400px at 20% -10%, rgba(30, 64, 175, 0.12), transparent 60%),
            radial-gradient(800px 300px at 90% 10%, rgba(14, 116, 144, 0.12), transparent 60%);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
    }

    .page-header {
        margin-bottom: 1.5rem;
    }

    .page-header h3 {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
        letter-spacing: -0.02em;
    }

    .page-header p {
        color: #5b6777;
        margin: 0;
    }

    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .action-card {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 1.8rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        text-align: left;
        text-decoration: none;
        color: #0f172a;
        transition: all 0.2s ease;
    }

    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
        border-color: rgba(37, 99, 235, 0.35);
        text-decoration: none;
        color: #0f172a;
    }

    .action-card i {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 1.35rem;
        margin-bottom: 1rem;
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
    }

    .action-card h5 {
        margin: 0 0 0.35rem;
        font-weight: 700;
        font-size: 1.05rem;
    }

    .action-card small {
        color: #64748b;
    }

    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1040;
        background-color: #337ccfe2;
        color: white;
        border: none;
        padding: 0.5rem 0.75rem;
        border-radius: 5px;
        cursor: pointer;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1040;
    }

    .sidebar-overlay.show {
        display: block;
    }

    @media (max-width: 768px) {
        .main-wrapper {
            flex-direction: column;
        }

        .sidebar-toggle {
            display: block;
        }

        .main-content {
            padding: 1.25rem;
            padding-top: 3.5rem;
            width: 100%;
        }

        .dashboard-shell {
            padding: 1rem;
        }

        .action-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
        }

        .page-header h3 {
            font-size: 1.35rem;
        }

        .action-card {
            padding: 1.5rem;
        }

        .action-card i {
            font-size: 1.25rem;
        }

        .action-card h5 {
            font-size: 1rem;
        }
    }
    </style>
</head>

<body>
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
