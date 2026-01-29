<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CSS -->
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background-color: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        text-align: left;
    }

    .stat-card h6 {
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }

    .stat-card h4 {
        color: #337ccfe2;
        margin-bottom: 0.5rem;
    }

    .stat-card p {
        color: lightslategray;
        font-size: 0.85rem;
        margin: 0;
    }

    .stat-icon {
        float: right;
        font-size: 1.5rem;
        color: #337ccfe2;
        margin-top: -2.5rem;
    }

    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .card-container {
        background-color: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card-container h4 {
        font-weight: bold;
        color: #333;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
    }

    .list-group-item {
        border: 1px solid #d4d4d4;
        margin-bottom: 0.5rem;
        border-radius: 5px;
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

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .content-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .card-container {
            padding: 1rem;
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

        .stat-card {
            padding: 1rem;
        }

        .stat-card h6 {
            font-size: 0.85rem;
        }

        .card-container {
            padding: 0.75rem;
        }

        .card-container h4 {
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
                <h3><i class="bi bi-speedometer2"></i> Employee Dashboard</h3>
                <p>Welcome back, Employee. Here's what's happening today.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h6>Pending Tasks <i class="bi bi-stopwatch-fill stat-icon"></i></h6>
                    <?php
                    $id = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 1;
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress' AND assigned_to = ?");
                    if ($stmt) {
                        $stmt->bind_param('i', $id);
                        $stmt->execute();
                        $stmt->bind_result($count);
                        $stmt->fetch();
                        $count = (int)($count ?? 0);
                        echo "<h4>{$count}</h4>";
                        echo "<p>" . ($count == 0 ? "Tasks waiting for action" : "Tasks pending") . "</p>";
                        $stmt->close();
                    } else {
                        echo "<p>DB error</p>";
                    }
                    ?>
                </div>

                <div class="stat-card">
                    <h6>Completed Tasks <i class="bi bi-ui-checks stat-icon"></i></h6>
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'done'");
                    if ($stmt) {
                        $stmt->bind_param('i', $id);
                        $stmt->execute();
                        $stmt->bind_result($count);
                        $stmt->fetch();
                        $count = (int)($count ?? 0);
                        echo "<h4>{$count}</h4>";
                        echo "<p>" . ($count >= 1 ? "Great job!" : "No tasks completed yet") . "</p>";
                        $stmt->close();
                    } else {
                        echo "<p>DB error</p>";
                    }
                    ?>
                </div>

                <div class="stat-card">
                    <h6>Leave Requests <i class="bi bi-suitcase-lg-fill stat-icon"></i></h6>
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = ?");
                    if ($stmt) {
                        $stmt->bind_param('i', $id);
                        $stmt->execute();
                        $stmt->bind_result($count);
                        $stmt->fetch();
                        $count = (int)($count ?? 0);
                        echo "<h4>{$count}</h4>";
                        echo "<p>" . ($count >= 1 ? "Submitted leave requests" : "No leave requests yet") . "</p>";
                        $stmt->close();
                    } else {
                        echo "<p>DB error</p>";
                    }
                    ?>
                </div>

                <div class="stat-card">
                    <h6>Latest Salaries <i class="bi bi-coin stat-icon"></i></h6>
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM salary_slips");
                    if ($stmt) {
                        $stmt->execute();
                        $stmt->bind_result($count);
                        $stmt->fetch();
                        $count = (int)($count ?? 0);
                        echo "<h4>$" . number_format($count) . "</h4>";
                        echo "<p>" . ($count >= 1 ? "Salary credited" : "No salary data") . "</p>";
                        $stmt->close();
                    } else {
                        echo "<p>DB error</p>";
                    }
                    ?>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <div class="card-container">
                    <h4><i class="bi bi-list-check"></i> Recent Tasks</h4>
                    <?php
                    $stmt = $conn->prepare("SELECT title, status, created_at FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 5");
                    if ($stmt) {
                        $stmt->bind_param('i', $id);
                        $stmt->execute();
                        $stmt->bind_result($title, $status, $created_at);
                        $has_tasks = false;
                        echo '<div class="list-group">';
                        while ($stmt->fetch()) {
                            $has_tasks = true;
                            $status_badge = '';
                            if ($status === 'in_progress') {
                                $status_badge = '<span class="badge bg-warning text-dark">In Progress</span>';
                            } elseif ($status === 'done') {
                                $status_badge = '<span class="badge bg-success">Completed</span>';
                            } else {
                                $status_badge = '<span class="badge bg-secondary">To Do</span>';
                            }
                            echo '<div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>' . htmlspecialchars($title) . '</strong><br>
                                        <small>Due: ' . htmlspecialchars($created_at ?? 'Not set') . '</small>
                                    </div>
                                    <div>' . $status_badge . '</div>
                                  </div>';
                        }
                        if (!$has_tasks) {
                            echo '<p class="text-muted">No tasks assigned yet.</p>';
                        }
                        echo '</div>';
                        $stmt->close();
                    } else {
                        echo '<p>DB error</p>';
                    }
                    ?>
                </div>

                <div class="card-container">
                    <h4><i class="bi bi-calendar-check"></i> My Leave Requests</h4>
                    <?php
                    $stmt = $conn->prepare("SELECT reason, start_date, end_date, status FROM leave_requests WHERE employee_id = ? ORDER BY applied_at DESC LIMIT 5");
                    if ($stmt) {
                        $stmt->bind_param('i', $id);
                        $stmt->execute();
                        $stmt->bind_result($reason, $start_date, $end_date, $status);
                        $has_leaves = false;
                        echo '<div class="list-group">';
                        while ($stmt->fetch()) {
                            $has_leaves = true;
                            $status_badge = '';
                            if ($status === 'pending') {
                                $status_badge = '<span class="badge bg-warning text-dark">Pending</span>';
                            } elseif ($status === 'approved') {
                                $status_badge = '<span class="badge bg-success">Approved</span>';
                            } else {
                                $status_badge = '<span class="badge bg-danger">Rejected</span>';
                            }
                            echo '<div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>' . htmlspecialchars($reason) . '</strong><br>
                                        <small>' . htmlspecialchars($start_date ?? 'Not set') . ' to ' . htmlspecialchars($end_date ?? 'Not set') . '</small>
                                    </div>
                                    <div>' . $status_badge . '</div>
                                  </div>';
                        }
                        if (!$has_leaves) {
                            echo '<p class="text-muted">No leave requests submitted yet.</p>';
                        }
                        echo '</div>';
                        $stmt->close();
                    } else {
                        echo '<p>DB error</p>';
                    }
                    ?>
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