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
        overflow-x: hidden;
        width: 75%;
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
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .page-header h2 {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .page-header p {
        color: #5b6777;
        font-size: 0.95rem;
        margin: 0;
    }

    /* Metric Cards */
    .metric-card {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1.4rem;
        margin-bottom: 1.5rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .metric-card:hover {
        transform: translateY(-3px);
        border-color: rgba(37, 99, 235, 0.4);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 70px;
        height: 70px;
        background: rgba(37, 99, 235, 0.08);
        border-radius: 20px;
        transform: translate(18px, -20px);
    }

    .metric-icon {
        font-size: 1.6rem;
        color: #1d4ed8;
        margin-bottom: 0.6rem;
    }

    .metric-label {
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 600;
        margin-bottom: 0.4rem;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .metric-change {
        font-size: 0.85rem;
        color: #16a34a;
    }

    .metric-change.negative {
        color: #ef4444;
    }

    /* Section Title */
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-top: 1rem;
        margin-bottom: 1.4rem;
        color: #0f172a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-title a {
        font-size: 0.85rem;
        color: #1d4ed8;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .section-title a:hover {
        color: #0f172a;
    }

    /* Recent Tasks */
    .task-list {
        background: #ffffff;
        border: 1px solid #d4d4d4;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 2rem;
    }

    .task-item {
        padding: 0.9rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: #fff;
        border-radius: 12px;
        margin-bottom: 0.9rem;
        transition: all 0.12s ease;
    }

    .task-item:last-child {
        margin-bottom: 0;
    }

    .task-item:hover {
        background: #f8fafc;
        transform: translateX(2px);
    }

    .task-title {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .task-meta {
        color: #64748b;
        font-size: 0.85rem;
    }

    .task-priority {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 0.5rem;
        text-transform: uppercase;
    }

    .task-priority.high {
        background-color: #f87171;
        color: white;
    }

    .task-priority.medium {
        background-color: #fbbf24;
        color: white;
    }

    .task-priority.low {
        background-color: #60a5fa;
        color: white;
    }

    /* Leave Requests */
    .leave-card {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .leave-card:last-child {
        margin-bottom: 0;
    }

    .leave-requestor {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .leave-dates {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }

    .leave-type {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #1d4ed8;
        color: white;
    }

    .leave-status {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .leave-status.pending {
        background-color: #fbbf24;
        color: white;
    }

    .leave-status.approved {
        background-color: #4ade80;
        color: white;
    }

    /* Project Progress */
    .project-item {
        margin-bottom: 1rem;
    }

    .project-name {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
    }

    .progress {
        height: 8px;
        background-color: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }

    .progress-bar {
        background: linear-gradient(90deg, #1d4ed8, #0ea5a4);
        height: 100%;
        border-radius: 999px;
        transition: width 1s ease;
    }

    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .action-btn {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1.2rem;
        text-align: center;
        transition: all 0.12s ease;
        text-decoration: none;
        color: #0f172a;
        cursor: pointer;
    }

    .action-btn:hover {
        background: #f8fafc;
        border-color: rgba(37, 99, 235, 0.4);
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        color: #0f172a;
    }

    .action-icon {
        font-size: 1.6rem;
        color: #1d4ed8;
        margin-bottom: 0.5rem;
        display: block;
    }

    .action-label {
        font-size: 0.9rem;
        font-weight: 600;
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
        padding: 0.6rem 0.8rem;
        border-radius: 12px;
        cursor: pointer;
        font-size: 1.25rem;
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
            width: 100%;
            padding-top: 3.5rem;
            padding-left: 1.25rem;
        }

        .dashboard-shell {
            padding: 1rem;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .page-header h2 {
            font-size: 1.6rem;
        }

        .metric-value {
            font-size: 1.6rem;
        }

        .quick-actions {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.75rem;
        }

        .action-icon {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
        }

        .page-header h2 {
            font-size: 1.35rem;
        }

        .metric-card {
            padding: 1rem;
        }

        .metric-value {
            font-size: 1.5rem;
        }

        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
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
                    <div>
                        <h2>Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></h2>
                        <p>Here's what's happening in your workspace today.</p>
                    </div>
                </div>

                <!-- Metrics Cards -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="metric-card">
                            <i class="bi bi-people metric-icon"></i>
                            <div class="metric-label">Total Employees</div>
                            <div class="metric-value"><?= $total_employees ?></div>
                            <div class="metric-change"><i class="bi bi-arrow-up"></i> 12% vs last month</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="metric-card">
                            <i class="bi bi-clipboard-check metric-icon"></i>
                            <div class="metric-label">Active Tasks</div>
                            <div class="metric-value"><?= $active_tasks ?></div>
                            <div class="metric-change"><i class="bi bi-arrow-up"></i> 8% vs last month</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="metric-card">
                            <i class="bi bi-calendar-event metric-icon"></i>
                            <div class="metric-label">Pending Leaves</div>
                            <div class="metric-value"><?= $pending_leaves ?></div>
                            <div class="metric-change"><i class="bi bi-arrow-down"></i> 3% vs last month</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="metric-card">
                            <i class="bi bi-graph-up metric-icon"></i>
                            <div class="metric-label">Performance</div>
                            <div class="metric-value"><?= $performance_rating ?>%</div>
                            <div class="metric-change"><i class="bi bi-arrow-up"></i> 3% vs last month</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="section-title">
                    <span>Quick Actions</span>
                </div>
                <div class="quick-actions">
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
                <div class="row">
                    <div class="col-lg-7 col-md-12 col-12">
                        <div class="task-list">
                            <div class="section-title">
                                <span>Recent Tasks</span>
                                <a href="tasks_dashboard.php">View All &nbsp; ↗</a>
                            </div>

                            <?php
                        if ($recent_tasks && $recent_tasks->num_rows > 0):
                            while ($task = $recent_tasks->fetch_assoc()):
                                $priority_class = strtolower($task['priority'] ?? 'medium');
                        ?>
                            <div class="task-item">
                                <div class="task-title"><?= htmlspecialchars($task['title'] ?? 'Untitled Task') ?></div>
                                <div class="task-meta"><i class="bi bi-calendar3"></i> Today</div>
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
                        <div class="section-title">
                            <span>Leave Requests</span>
                            <a href="leave_view.php">View All Requests</a>
                        </div>

                        <?php
                    if ($leave_requests && $leave_requests->num_rows > 0):
                        while ($leave = $leave_requests->fetch_assoc()):
                    ?>
                        <div class="leave-card">
                            <div class="leave-requestor"><?= htmlspecialchars($leave['full_name']) ?></div>
                            <div class="leave-dates">
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
                        <div class="leave-card">
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
                                        $status_badge = '<span class="badge bg-warning text-dark">Pending</span>';
                                    } elseif ($status === 'approved') {
                                        $status_badge = '<span class="badge bg-success">Approved</span>';
                                    } else {
                                        $status_badge = '<span class="badge bg-danger">Rejected</span>';
                                    }
                                    echo '<div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>' . htmlspecialchars($reason) . '</strong><br>
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
                <div class="section-title">
                    <span>Project Progress</span>
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
