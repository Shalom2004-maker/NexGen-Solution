<?php

include "../includes/auth.php";
allow(["Employee", "HR", "Admin"]);
include "../includes/db.php";

$role = $_SESSION['role'] ?? '';
$currentUserId = (int)($_SESSION['uid'] ?? 0);
$currentEmployeeId = 0;

if ($role === 'Employee' && $currentUserId > 0) {
    $employee_lookup = $conn->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
    if ($employee_lookup) {
        $employee_lookup->bind_param('i', $currentUserId);
        $employee_lookup->execute();
        $employee_lookup->bind_result($currentEmployeeId);
        $employee_lookup->fetch();
        $currentEmployeeId = (int)$currentEmployeeId;
        $employee_lookup->close();
    }
}

$create_error = '';
$create_success = '';

// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_employee'])) {
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $create_error = 'Invalid request (CSRF).';
    } elseif (!in_array($role, ['HR', 'Admin'], true)) {
        http_response_code(403);
        $create_error = 'You do not have permission to create employees.';
    } else {
        $name = trim($_POST['full_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $job_title = trim($_POST['job_title'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $hire_date = $_POST['hire_date'] ?? null;
        $salary_base = isset($_POST['salary_base']) ? (float)$_POST['salary_base'] : 0.0;
        $status = ($_POST['status'] ?? 'active') === 'resigned' ? 'resigned' : 'active';

        if ($name === '' || strlen($name) < 2) {
            $create_error = 'Enter a valid full name.';
        } elseif (!$email) {
            $create_error = 'Enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $create_error = 'Password must be at least 6 characters.';
        } elseif ($job_title === '') {
            $create_error = 'Job title is required.';
        } elseif ($department === '') {
            $create_error = 'Department is required.';
        } elseif ($hire_date === '' || $hire_date === null) {
            $create_error = 'Hire date is required.';
        } elseif ($salary_base < 0) {
            $create_error = 'Salary must be 0 or higher.';
        }

        if (empty($create_error)) {
            $role_stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'Employee' LIMIT 1");
            if ($role_stmt && $role_stmt->execute()) {
                $role_result = $role_stmt->get_result();
                $role_row = $role_result->fetch_assoc();
                $role_id = (int)($role_row['id'] ?? 0);
                $role_stmt->close();
            } else {
                $role_id = 0;
            }

            if ($role_id <= 0) {
                $create_error = 'Employee role not found. Please contact admin.';
            }
        }

        if (empty($create_error)) {
            $dup_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            if ($dup_stmt) {
                $dup_stmt->bind_param('s', $email);
                $dup_stmt->execute();
                $dup_stmt->store_result();
                if ($dup_stmt->num_rows > 0) {
                    $create_error = 'A user with that email already exists.';
                }
                $dup_stmt->close();
            }
        }

        if (empty($create_error)) {
            $conn->begin_transaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $u_stmt = $conn->prepare("INSERT INTO users(full_name, email, password_hash, role_id) VALUES(?,?,?,?)");
                if (!$u_stmt) {
                    throw new \Exception('Failed to create user.');
                }
                $u_stmt->bind_param("sssi", $name, $email, $hash, $role_id);
                if (!$u_stmt->execute()) {
                    throw new \Exception('Failed to create user.');
                }
                $user_id = (int)$u_stmt->insert_id;
                $u_stmt->close();

                $e_stmt = $conn->prepare("INSERT INTO employees(user_id, job_title, department, hire_date, salary_base, status) VALUES(?,?,?,?,?,?)");
                if (!$e_stmt) {
                    throw new \Exception('Failed to create employee record.');
                }
                $e_stmt->bind_param("isssds", $user_id, $job_title, $department, $hire_date, $salary_base, $status);
                if (!$e_stmt->execute()) {
                    throw new \Exception('Failed to create employee record.');
                }
                $e_stmt->close();

                $conn->commit();
                $create_success = 'Employee created successfully.';
            } catch (\Exception $ex) {
                $conn->rollback();
                $create_error = $ex->getMessage();
            }
        }
    }
}
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
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    <!-- Local Bootstrap CSS Link -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/colors.css" rel="stylesheet">
    <link href="../css/theme.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">
    <script src="../js/bootstrap.bundle.min.js"></script>

    <style>
    .avatar-initial {
        width: 4rem;
        height: 4rem;
        border-radius: 50%;
        background-color: #0d6efd;
        color: #fff;
        font-weight: 600;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        text-transform: uppercase;
        font-size: 1.3rem;
    }
    </style>

    <!-- CSS -->
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
                <div class="page-header">
                    <div>
                        <?php if ($role === 'Employee') : ?>
                        <h3>Employee Dashboard</h3>
                        <p>Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Employee') ?>. Here's your live
                            workspace overview.</p>
                        <?php else : ?>
                        <h3>Employee Records</h3>
                        <p>Manage employee profiles and onboarding details.</p>
                        <?php endif; ?>
                    </div>
                    <div class="header-actions">
                        <?php if (in_array($role, ['HR', 'Admin'], true)) : ?>
                        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createEmployeeModal">
                            <i class="bi bi-person-plus"></i> &nbsp; Create Employee
                        </button>
                        <?php endif; ?>
                        <?php if ($role === 'Employee') : ?>
                        <a class="btn btn-outline-custom" href="profile.php">
                            <i class="bi bi-person-circle"></i> My Profile
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($create_error)) : ?>
                <div class="alert alert-danger alert-custom"><?= htmlspecialchars($create_error) ?></div>
                <?php elseif (!empty($create_success)) : ?>
                <div class="alert alert-success alert-custom"><?= htmlspecialchars($create_success) ?></div>
                <?php endif; ?>

                <?php if ($role === 'Employee') : ?>
                <div class="quick-actions mb-4">
                    <h5>Quick Actions</h5>
                    <div class="action-buttons">
                        <a class="action-link d-flex" href="tasks_dashboard.php"><i class="bi bi-list-check"></i>
                            &nbsp; Tasks</a>
                        <a class="action-link d-flex" href="leave_dashboard.php"><i class="bi bi-calendar2-check"></i>
                            &nbsp;
                            Leave</a>
                        <a class="action-link d-flex" href="salary_dashboard.php"><i class="bi bi-coin"></i> &nbsp;
                            Salary</a>
                    </div>
                </div>
                <?php elseif ($role === 'HR') : ?>
                <div class="quick-actions mt-4 mb-4">
                    <h5>Quick Actions</h5>
                    <div class="action-buttons mt-3 gap-3">
                        <a class="action-link" href="employee.php"><i class="bi bi-people"></i> &nbsp; Employees</a>
                        <a class="action-link" href="leave_view.php"><i class="bi bi-calendar2-check"></i> &nbsp;
                            Leave</a>
                        <a class="action-link" href="inquiries_dashboard.php"><i class="bi bi-inbox"></i> &nbsp;
                            Inquiries</a>
                        <a class="action-link" href="hr_payroll.php"><i class="bi bi-coin"></i> &nbsp; Payroll</a>
                    </div>
                </div>
                <?php elseif ($role === 'Admin') : ?>
                <div class="quick-actions">
                    <h5>Quick Actions</h5>
                    <div class="action-buttons">
                        <a class="action-link" href="admin_dashboard.php"><i class="bi bi-columns-gap"></i>
                            Dashboard</a>
                        <a class="action-link" href="admin_user_view.php"><i class="bi bi-people"></i> Users</a>
                        <a class="action-link" href="inquiries_dashboard.php"><i class="bi bi-inbox"></i> Inquiries</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <?php if ($role === 'Employee') : ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-badge"><i class="bi bi-stopwatch-fill"></i></div>
                        <h6>Pending Tasks</h6>
                        <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE status IN ('todo', 'in_progress') AND assigned_to = ?");
                            if ($stmt) {
                                $stmt->bind_param('i', $currentUserId);
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
                        <div class="stat-badge"><i class="bi bi-ui-checks"></i></div>
                        <h6>Completed Tasks</h6>
                        <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'done'");
                            if ($stmt) {
                                $stmt->bind_param('i', $currentUserId);
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
                        <div class="stat-badge"><i class="bi bi-suitcase-lg-fill"></i></div>
                        <h6>Leave Requests</h6>
                        <?php
                            if ($currentEmployeeId > 0) {
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = ?");
                                if ($stmt) {
                                    $stmt->bind_param('i', $currentEmployeeId);
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
                            } else {
                                echo "<h4>0</h4>";
                                echo "<p>Employee profile not linked yet</p>";
                            }
                            ?>
                    </div>

                    <div class="stat-card">
                        <div class="stat-badge"><i class="bi bi-coin"></i></div>
                        <h6>Salary Slips</h6>
                        <?php
                            if ($currentEmployeeId > 0) {
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM salary_slips WHERE employee_id = ?");
                                if ($stmt) {
                                    $stmt->bind_param('i', $currentEmployeeId);
                                    $stmt->execute();
                                    $stmt->bind_result($count);
                                    $stmt->fetch();
                                    $count = (int)($count ?? 0);
                                    echo "<h4>" . number_format($count) . "</h4>";
                                    echo "<p>" . ($count >= 1 ? "Records available" : "No salary data") . "</p>";
                                    $stmt->close();
                                } else {
                                    echo "<p>DB error</p>";
                                }
                            } else {
                                echo "<h4>0</h4>";
                                echo "<p>Employee profile not linked yet</p>";
                            }
                            ?>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <div class="card-container">
                        <h4 class="d-flex"><i class="bi bi-list-check"></i> &nbsp; My Recent Tasks</h4>
                        <?php
                            $stmt = $conn->prepare("SELECT title, status, created_at FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 5");
                            if ($stmt) {
                                $stmt->bind_param('i', $currentUserId);
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
                        <h4 class="d-flex"><i class="bi bi-calendar-check"></i> &nbsp; My Leave Requests</h4>
                        <?php
                            if ($currentEmployeeId > 0) {
                                $stmt = $conn->prepare("SELECT reason, start_date, end_date, status FROM leave_requests WHERE employee_id = ? ORDER BY applied_at DESC LIMIT 5");
                                if ($stmt) {
                                    $stmt->bind_param('i', $currentEmployeeId);
                                    $stmt->execute();
                                    $stmt->bind_result($reason, $start_date, $end_date, $status);
                                    $has_leaves = false;
                                    echo '<div class="list-group">';
                                    while ($stmt->fetch()) {
                                        $has_leaves = true;
                                        $status_badge = '';
                                        if ($status === 'pending') {
                                            $status_badge = '<span class="badge bg-warning text-dark">Pending</span>';
                                        } elseif (in_array($status, ['leader_approved', 'hr_approved'], true)) {
                                            $status_badge = '<span class="badge bg-success">' . htmlspecialchars(ucwords(str_replace('_', ' ', $status))) . '</span>';
                                        } else {
                                            $status_badge = '<span class="badge bg-danger">Rejected</span>';
                                        }
                                        $start_date_str = ($start_date instanceof \DateTime) ? $start_date->format('Y-m-d') : (string)($start_date ?? 'Not set');
                                        $end_date_str = ($end_date instanceof \DateTime) ? $end_date->format('Y-m-d') : (string)($end_date ?? 'Not set');
                                        echo '<div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>' . htmlspecialchars($reason) . '</strong><br>
                                                <small>' . htmlspecialchars($start_date_str) . ' to ' . htmlspecialchars($end_date_str) . '</small>
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
                            } else {
                                echo '<p class="text-muted">Your employee profile is not linked to a leave record yet.</p>';
                            }
                            ?>
                    </div>

                </div>
                <?php endif; ?>

                <?php if (in_array($role, ['HR', 'Admin'], true)) : ?>
                <div class="content-grid">
                    <div class="card-container">
                        <h4><i class="bi bi-people"></i> &nbsp; Newest Employees</h4>
                        <?php
                            $stmt = $conn->prepare("SELECT u.full_name, u.email, u.profile_photo, e.department, e.job_title, e.hire_date FROM employees e JOIN users u ON e.user_id = u.id ORDER BY e.id DESC LIMIT 12");
                            if ($stmt) {
                                $stmt->execute();
                                $stmt->bind_result($emp_name, $emp_email, $emp_photo, $emp_department, $emp_job, $emp_hire);
                                $has_employees = false;
                                echo '<div class="row gx-3 gy-3">';
                                while ($stmt->fetch()) {
                                    $has_employees = true;
                                    $emp_hire_str = htmlspecialchars($emp_hire ?? 'Not set');
                                    $avatar_html = '';
                                    $resolved_photo = function_exists('resolve_avatar_url') ? resolve_avatar_url($emp_photo) : '';
                                    if ($resolved_photo !== '') {
                                        $avatar_html = '<img src="' . htmlspecialchars($resolved_photo) . '" class="rounded-circle" width="40" height="40">';
                                    } else {
                                        $initial = strtoupper(substr(trim($emp_name ?? ''), 0, 1));
                                        if ($initial === '') {
                                            $initial = '?';
                                        }
                                        $avatar_html = '<span class="avatar-initial">' . htmlspecialchars($initial) . '</span>';
                                    }

                                    echo '<div class="col-lg-4 col-md-6 col-12 gap-3">';
                                    echo '  <div class="card h-100 shadow-sm border-0">';
                                    echo '    <div class="card-body py-3">';
                                    echo '      <div class="d-flex align-items-center mb-3">';
                                    echo '        <div class="me-3">' . $avatar_html . '</div>';
                                    echo '        <div>'; 
                                    echo '          <h6 class="mb-1">' . htmlspecialchars($emp_name ?? 'Unknown') . '</h6>';
                                    echo '          <span class="text-muted small">' . htmlspecialchars($emp_job ?? 'Not set') . '</span>';
                                    echo '        </div>';
                                    echo '      </div>';
                                    echo '      <div class="small">';
                                    echo '        <div><strong>Department:</strong> ' . htmlspecialchars($emp_department ?? 'Not set') . '</div>';
                                    echo '        <div><strong>Email:</strong> ' . htmlspecialchars($emp_email ?? 'Not set') . '</div>';
                                    echo '        <div><strong>Hired:</strong> ' . $emp_hire_str . '</div>';
                                    echo '      </div>';
                                    echo '    </div>';
                                    echo '  </div>';
                                    echo '</div>';
                                }
                                if (!$has_employees) {
                                    echo '<div class="col-12"><p class="text-muted">No employee records yet.</p></div>';
                                }
                                echo '</div>';
                                $stmt->close();
                            } else {
                                echo '<p>DB error</p>';
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (in_array($role, ['HR', 'Admin'], true)) : ?>
            <!-- Create Employee Modal -->
            <div class="modal fade" id="createEmployeeModal" tabindex="-1" aria-labelledby="createEmployeeModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createEmployeeModalLabel">Create New Employee</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="create_employee" value="1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="full_name" class="form-control" required
                                            placeholder="e.g. Jane Doe">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" required
                                            placeholder="e.g. jane@company.com">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Password *</label>
                                        <input type="password" name="password" class="form-control" required
                                            minlength="6" placeholder="Minimum 6 characters">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Job Title *</label>
                                        <input type="text" name="job_title" class="form-control" required
                                            placeholder="e.g. UI Designer">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Department *</label>
                                        <input type="text" name="department" class="form-control" required
                                            placeholder="e.g. Design">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Hire Date *</label>
                                        <input type="date" name="hire_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Base Salary *</label>
                                        <input type="number" name="salary_base" class="form-control" required min="0"
                                            step="0.01" placeholder="e.g. 55000">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" selected>Active</option>
                                            <option value="resigned">Resigned</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn-primary-custom">Create Employee</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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

                const showCreateModal = <?= !empty($create_error) ? 'true' : 'false' ?>;
                if (showCreateModal) {
                    const createModal = document.getElementById('createEmployeeModal');
                    if (createModal && window.bootstrap) {
                        const modalInstance = new bootstrap.Modal(createModal);
                        modalInstance.show();
                    }
                }
            });
            </script>
</body>

</html>
