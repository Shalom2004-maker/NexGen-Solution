<?php
// admin_top.php - shared header and opening layout for admin/dashboard pages
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Shared admin styles (trimmed version of admin_dashboard.css) */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Inter", sans-serif;
        }

        html,
        body {
            background-color: #ececece8;
            color: #333;
            min-height: 100vh;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        #sidebarContainer {
            width: 18rem;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .main-content {
            flex: 1;
            margin-left: 18rem;
            padding: 1.7rem 2rem;
            background-color: #f5f5f5d2;
        }

        .page-header {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .card-panel {
            background: #fff;
            border: 1px solid #d4d4d4;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1040;
        }

        .nexgen-sidebar {
            width: 18rem;
        }

        @media (max-width:768px) {
            #sidebarContainer {
                position: fixed;
                transform: translateX(-100%);
            }

            #sidebarContainer.show {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding-top: 3.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="btn btn-primary sidebar-toggle" id="sidebarToggleBtn" type="button"><i class="bi bi-list"></i></button>

    <div class="main-wrapper">
        <div id="sidebarContainer" class="nexgen-sidebar">
            <?php include __DIR__ . '/../dashboard/admin_siderbar.php'; ?>
        </div>

        <div class="main-content">
            <!-- page content starts -->