<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
require_once __DIR__ . "/../includes/chart_generator.php";

$chartGen = new ChartGenerator($conn);
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

    <!-- CanvasJS for Charts -->
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

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

                <!-- Project Leader Analytics Charts -->
                <div class="row mt-4">
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Task Status Overview</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $taskData = $chartGen->getTaskStatusChart();
                                $chartGen->renderChart('leaderTaskChart', $taskData, 'Tasks by Status', 'doughnut');
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Project Progress</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get project progress data
                                $projectQuery = "SELECT p.project_name, COUNT(t.id) as total_tasks,
                                                SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as completed_tasks
                                                FROM projects p
                                                LEFT JOIN tasks t ON p.id = t.project_id
                                                GROUP BY p.id, p.project_name
                                                HAVING total_tasks > 0
                                                ORDER BY p.id";

                                $projectResult = $conn->query($projectQuery);
                                $projectData = array();

                                while($row = $projectResult->fetch_assoc()){
                                    $progress = $row['total_tasks'] > 0 ? round(($row['completed_tasks'] / $row['total_tasks']) * 100) : 0;
                                    $projectData[] = array(
                                        "label" => substr($row['project_name'], 0, 20) . (strlen($row['project_name']) > 20 ? '...' : ''),
                                        "y" => (int)$progress
                                    );
                                }

                                $chartGen->renderChart('leaderProjectChart', $projectData, 'Project Completion %', 'column');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Team Task Distribution</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get tasks assigned to team members
                                $teamQuery = "SELECT u.full_name, COUNT(t.id) as task_count
                                             FROM users u
                                             LEFT JOIN tasks t ON u.id = t.assigned_to
                                             WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'Employee')
                                             GROUP BY u.id, u.full_name
                                             ORDER BY task_count DESC";

                                $teamResult = $conn->query($teamQuery);
                                $teamData = array();

                                while($row = $teamResult->fetch_assoc()){
                                    $teamData[] = array(
                                        "label" => $row['full_name'],
                                        "y" => (int)$row['task_count']
                                    );
                                }

                                $chartGen->renderChart('leaderTeamChart', $teamData, 'Tasks per Team Member', 'bar');
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Leave Requests Overview</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $leaveData = $chartGen->getLeaveStatusChart();
                                $chartGen->renderChart('leaderLeaveChart', $leaveData, 'Leave Requests Status', 'pie');
                                ?>
                            </div>
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