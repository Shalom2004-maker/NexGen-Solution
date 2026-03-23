<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

// Fetch dashboard metrics
$total_employees = 0;
$active_tasks = 0;
$pending_leaves = 0;
$performance_rating = 0;

// Total Employees
$emp_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id IN (SELECT id FROM roles WHERE role_name = 'Employee')");
if ($emp_result) {
    $emp_row = $emp_result->fetch_assoc();
    $total_employees = $emp_row['count'];
}

// Active Tasks
$task_result = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE status IN ('todo', 'in_progress')");
if ($task_result) {
    $task_row = $task_result->fetch_assoc();
    $active_tasks = $task_row['count'];
}

// Pending Leaves
$leave_result = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'Pending'");
if ($leave_result) {
    $leave_row = $leave_result->fetch_assoc();
    $pending_leaves = $leave_row['count'];
}

// Performance (Random for now - you can replace with actual calculation)
$performance_rating = 94;

// Recent Tasks (limit to 4)
$recent_tasks = $conn->query("SELECT * FROM tasks ORDER BY created_at DESC LIMIT 4");

// Leave Requests (limit to 3)
// join on employee_id to get the requestor's name
$leave_requests = $conn->query("SELECT l.*, u.full_name FROM leave_requests l JOIN users u ON l.employee_id = u.id ORDER BY l.applied_at DESC LIMIT 3");

// Project Progress (sample projects)
$projects = [
    ['name' => 'Website Redesign', 'progress' => 85],
    ['name' => 'Mobile App v2', 'progress' => 62],
    ['name' => 'API Integration', 'progress' => 45]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    <!-- Local Bootstrap CSS Link -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="/js/bootstrap.bundle.min.js"></script>


    <!-- CSS -->
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
                    <div>
                        <h2>Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></h2>
                        <p>Here's what's happening in your workspace today.</p>
                    </div>
                </div>

                <!-- Metrics Cards -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <div class="metric-card">
                            <i class="bi bi-people metric-icon"></i>
                            <div class="metric-label">Total Employees</div>
                            <div class="metric-value"><?= $total_employees ?></div>
                            <div class="metric-change"><i class="bi bi-arrow-up"></i> 12% vs last month</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <div class="metric-card">
                            <i class="bi bi-clipboard-check metric-icon"></i>
                            <div class="metric-label">Active Tasks</div>
                            <div class="metric-value"><?= $active_tasks ?></div>
                            <div class="metric-change"><i class="bi bi-arrow-up"></i> 8% vs last month</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <div class="metric-card">
                            <i class="bi bi-calendar-event metric-icon"></i>
                            <div class="metric-label">Pending Leaves</div>
                            <div class="metric-value"><?= $pending_leaves ?></div>
                            <div class="metric-change"><i class="bi bi-arrow-down"></i> 3% vs last month</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <div class="metric-card">
                            <i class="bi bi-graph-up metric-icon"></i>
                            <div class="metric-label">Performance</div>
                            <div class="metric-value"><?= $performance_rating ?>%</div>
                            <div class="metric-change"><i class="bi bi-arrow-up"></i> 3% vs last month</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="section-title mt-4 mb-3">
                    <span>Quick Actions</span>
                </div>
                <div class="quick-actions mb-4 d-flex justify-content-evenly gap-3">
                    <a href="tasks_dashboard.php" class="action-btn">
                        <i class="bi bi-plus-circle action-icon"></i>
                        <span class="action-label">New Task</span>
                    </a>
                    <a href="leave_dashboard.php" class="action-btn">
                        <i class="bi bi-file-earmark action-icon"></i>
                        <span class="action-label">Request Leave</span>
                    </a>
                    <a href="inquiries_dashboard.php" class="action-btn">
                        <i class="bi bi-chat-left action-icon"></i>
                        <span class="action-label">Submit Inquiry</span>
                    </a>
                    <a href="projects.php" class="action-btn">
                        <i class="bi bi-bar-chart action-icon"></i>
                        <span class="action-label">View Projects</span>
                    </a>
                    <a href="admin_user_view.php" class="action-btn">
                        <i class="bi bi-people action-icon"></i>
                        <span class="action-label">View Users</span>
                    </a>
                </div>

                <!-- Recent Tasks and Leave Requests -->
                <div class="row mt-4 mb-4">
                    <div class="col-lg-7 col-md-12 col-12 mb-3">
                        <div class="task-list">
                            <div class="section-title d-flex justify-content-between align-items-center">
                                <span>Recent Tasks</span>
                                <a class="text-decoration-none" href="tasks_dashboard.php">View All&nbsp; ↗</a>
                            </div>

                            <?php
                        if ($recent_tasks && $recent_tasks->num_rows > 0):
                            while ($task = $recent_tasks->fetch_assoc()):
                                $priority_class = strtolower($task['priority'] ?? 'medium');
                        ?>
                            <div class="task-item">
                                <div class="task-title mb-2"><?= htmlspecialchars($task['title'] ?? 'Untitled Task') ?>
                                </div>
                                <div class="task-meta mb-2"><i class="bi bi-calendar3"></i> Today</div>
                                <span class="task-priority <?= $priority_class ?>">
                                    <?= htmlspecialchars($task['priority'] ?? 'Medium') ?>
                                </span>
                            </div>
                            <?php
                            endwhile;
                        else:
                            ?>
                            <div class="task-item">
                                <div class="task-title">No recent tasks</div>
                                <div class="task-meta">Tasks will appear here as they are created</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="section-title d-flex justify-content-between align-items-center mb-3">
                            <span>Leave Requests</span>
                            <a class="text-decoration-none" href="leave_view.php">View All Requests</a>
                        </div>

                        <?php
                    if ($leave_requests && $leave_requests->num_rows > 0):
                        while ($leave = $leave_requests->fetch_assoc()):
                    ?>
                        <div class="leave-card">
                            <div class="leave-requestor mb-2"><?= htmlspecialchars($leave['full_name']) ?></div>
                            <div class="leave-dates mb-2">
                                <i class="bi bi-calendar"></i>
                                <?= htmlspecialchars($leave['start_date'] ?? 'N/A') ?> -
                                <?= htmlspecialchars($leave['end_date'] ?? 'N/A') ?>
                            </div>
                            <span class="leave-type"><?= htmlspecialchars($leave['leave_type'] ?? 'Annual') ?></span>
                            <span class="leave-status <?= strtolower($leave['status'] ?? 'pending') ?>">
                                <?= htmlspecialchars($leave['status'] ?? 'Pending') ?>
                            </span>
                        </div>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <div class="leave-card mb-3">
                            <div class="leave-requestor">Leave Requests</div>
                            <h4><i class="bi bi-calendar-check"></i> My Leave Requests</h4>
                            <?php
                            $stmt = $conn->prepare("SELECT reason, start_date, end_date, status FROM leave_requests WHERE employee_id = ? ORDER BY applied_at ASC LIMIT 7");
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
                                        $status_badge = '<span class="badge bg-warning text-dark ml-2">Pending</span>';
                                    } elseif ($status === 'hr_approved' || $status === 'leader_approved') {
                                        $status_badge = '<span class="badge bg-success ml-2">Approved</span>';
                                    } else {
                                        $status_badge = '<span class="badge bg-danger ml-2">Rejected</span>';
                                    }
                                    echo '<div class="p-2 mb-3 list-group-item d-flex justify-content-between align-items-center" style="width: max-content;">
                                    <div>
                                        <small>' . htmlspecialchars($reason) . '</small><br>
                                        <small>' . htmlspecialchars(is_object($start_date) ? $start_date->format('Y-m-d') : ($start_date ?? 'Not set')) . ' to ' . htmlspecialchars(is_object($end_date) ? $end_date->format('Y-m-d') : ($end_date ?? 'Not set')) . '</small>
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
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Progress -->
                <div class="section-title mb-3">
                    <span class="mb-3">Project Progress</span>
                </div>
                <div class="row">
                    <div class="col-lg-7">
                        <?php foreach ($projects as $project): ?>
                        <div class="project-item">
                            <div class="project-name">
                                <span><?= htmlspecialchars($project['name']) ?></span>
                                <span><?= $project['progress'] ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?= $project['progress'] ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
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