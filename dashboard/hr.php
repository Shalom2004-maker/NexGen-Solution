<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
require_once __DIR__ . "/../includes/chart_generator.php";

$chartGen = new ChartGenerator($conn);
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
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CanvasJS for Charts -->
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</head>

<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button">
        <i class="bi bi-list"></i>
    </button>

    <div class="main-wrapper">
        <div id="sidebarContainer">
            <?php include "../includes/sidebar_helper.php";
            render_sidebar(); ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="page-header mb-4">
                    <h3>HR Dashboard</h3>
                    <p>Manage personnel, approvals, and payroll operations</p>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-md-6 col-12 p-2 d-flex flex-column justify-content-center">
                        <a href="leave_view.php" class="action-card warning text-decoration-none">
                            <div class="mb-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <h5>Leave Approvals</h5>
                                <small>Review and approve employee leave requests</small>
                            </div>
                        </a>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 p-2 d-flex flex-column justify-content-center">
                        <a href="hr_payroll.php" class="action-card success text-decoration-none">
                            <div class="mb-3">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div>
                                <h5>Payroll</h5>
                                <small>Manage salary and compensation</small>
                            </div>
                        </a>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 p-2 d-flex flex-column justify-content-center">
                        <a href="inquiries_dashboard.php" class="action-card info text-decoration-none">
                            <div class="mb-3">
                                <i class="bi bi-chat-left-text"></i>
                            </div>
                            <div>
                                <h5>Inquiries</h5>
                                <small>View public contact inquiries</small>
                            </div>
                        </a>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 p-2 d-flex flex-column justify-content-center">
                        <a href="employee.php" class="action-card danger text-decoration-none">
                            <div class="mb-3">
                                <i class="bi bi-people"></i>
                            </div>
                            <div>
                                <h5>Employee Records</h5>
                                <small>Create and maintain employee profiles</small>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- HR Analytics Charts -->
                <div class="row mt-4">
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Leave Requests Status</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $leaveData = $chartGen->getLeaveStatusChart();
                                $chartGen->renderChart('hrLeaveChart', $leaveData, 'Leave Requests by Status', 'doughnut');
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Employee Distribution</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $deptData = $chartGen->getEmployeeDepartmentChart();
                                $chartGen->renderChart('hrDeptChart', $deptData, 'Employees by Department', 'pie');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Monthly Leave Trends</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $monthlyData = $chartGen->getMonthlyLeaveChart();
                                $chartGen->renderChart('hrMonthlyChart', $monthlyData, 'Leave Requests (Last 6 Months)', 'line');
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Leave Types Distribution</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $leaveTypeData = $chartGen->getLeaveTypesChart();
                                $chartGen->renderChart('hrLeaveTypeChart', $leaveTypeData, 'Leave Requests by Type', 'pie');
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