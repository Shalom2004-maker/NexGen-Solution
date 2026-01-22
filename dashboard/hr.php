<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Osward", sans-serif;
        }

        html,
        body {
            background-color: #ececece8;
            min-height: 100vh;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            background-color: #f5f5f5d2;
            padding: 2rem;
            overflow-y: auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h3 {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: lightslategray;
            margin: 0;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: white;
        }

        .action-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
        }

        .action-card h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .action-card.warning {
            background-color: #ffc107;
        }

        .action-card.success {
            background-color: #28a745;
        }

        .action-card.info {
            background-color: #007bff;
        }

        .action-card.danger {
            background-color: #dc3545;
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
                padding: 1.5rem;
                padding-top: 3.5rem;
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
                font-size: 1.25rem;
            }

            .action-card {
                padding: 1.5rem;
            }

            .action-card i {
                font-size: 1.5rem;
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
            <?php include "admin_siderbar.php"; ?>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h3><i class="bi bi-people-fill"></i> HR Dashboard</h3>
                <p>Manage personnel, approvals, and payroll operations</p>
            </div>

            <div class="action-grid">
                <a href="leave_view.php" class="action-card warning">
                    <i class="bi bi-calendar-check"></i>
                    <h5>Leave Approvals</h5>
                    <small>Review and approve employee leave requests</small>
                </a>

                <a href="hr_payroll.php" class="action-card success">
                    <i class="bi bi-currency-dollar"></i>
                    <h5>Payroll</h5>
                    <small>Manage salary and compensation</small>
                </a>

                <a href="inquiries_view.php" class="action-card info">
                    <i class="bi bi-chat-left-text"></i>
                    <h5>Inquiries</h5>
                    <small>View public contact inquiries</small>
                </a>

                <a href="admin_user_view.php" class="action-card danger">
                    <i class="bi bi-shield-lock"></i>
                    <h5>System Users</h5>
                    <small>Manage system user accounts</small>
                </a>
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