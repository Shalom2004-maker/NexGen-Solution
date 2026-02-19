<?php

include "../includes/auth.php";
allow(["Employee", "HR", "Admin"]);
include "../includes/db.php";

$role = $_SESSION['role'] ?? '';

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
        background: transparent;
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
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .page-header h3 {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .page-header p {
        color: #5b6777;
        margin: 0;
        font-size: 0.95rem;
    }

    .header-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        color: #fff;
        border: none;
        padding: 0.7rem 1.2rem;
        border-radius: 999px;
        font-weight: 600;
        letter-spacing: 0.01em;
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
    }

    .btn-primary-custom:hover {
        color: #fff;
        transform: translateY(-1px);
    }

    .btn-outline-custom {
        border: 1px solid rgba(15, 23, 42, 0.2);
        color: #0f172a;
        padding: 0.7rem 1.2rem;
        border-radius: 999px;
        font-weight: 600;
        background: #fff;
    }

    .alert-custom {
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.4);
        background: rgba(255, 255, 255, 0.9);
        padding: 0.9rem 1.1rem;
        margin-bottom: 1rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1.4rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
        min-height: 150px;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
        border-color: rgba(37, 99, 235, 0.45);
    }

    .stat-badge {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .stat-card h6 {
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
    }

    .stat-card h4 {
        color: #0f172a;
        margin-bottom: 0.5rem;
        font-size: 2rem;
        font-weight: 700;
    }

    .stat-card p {
        color: #6b7280;
        font-size: 0.85rem;
        margin: 0;
    }

    .quick-actions {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1.2rem 1.4rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        align-items: center;
        justify-content: space-between;
    }

    .quick-actions h5 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
        color: #0f172a;
    }

    .quick-actions .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .action-link {
        border: 1px solid rgba(30, 41, 59, 0.15);
        padding: 0.55rem 0.95rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #0f172a;
        background: #f8fafc;
        text-decoration: none;
    }

    .action-link:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .card-container {
        background-color: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1.4rem;
        transition: all 0.2s ease;
    }

    .card-container:hover {
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .card-container h4 {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1.2rem;
        font-size: 1.1rem;
    }

    .list-group {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .list-group-item {
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 12px;
        padding: 0.9rem;
        background: #fff;
        transition: all 0.12s ease;
    }

    .list-group-item:hover {
        background: #f8fafc;
        transform: translateX(2px);
    }

    .list-group-item strong {
        color: #0f172a;
        display: block;
        margin-bottom: 0.25rem;
    }

    .list-group-item small {
        color: #6b7280;
        font-size: 0.85rem;
    }

    .badge {
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        border-radius: 20px;
        font-size: 0.72rem;
    }

    .bg-warning {
        background-color: #fbbf24 !important;
    }

    .bg-success {
        background-color: #22c55e !important;
    }

    .bg-secondary {
        background-color: #64748b !important;
    }

    .bg-danger {
        background-color: #ef4444 !important;
    }

    .text-dark {
        color: #0f172a !important;
    }

    .text-muted {
        color: #94a3b8 !important;
    }

    .modal-content {
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.4);
        box-shadow: 0 30px 50px rgba(15, 23, 42, 0.2);
    }

    .modal-header {
        border-bottom: 1px solid rgba(148, 163, 184, 0.3);
        background: linear-gradient(135deg, rgba(29, 78, 216, 0.1), rgba(14, 116, 144, 0.08));
    }

    .modal-title {
        font-weight: 700;
        color: #0f172a;
    }

    .modal-body label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.35rem;
    }

    .modal-body .form-control,
    .modal-body .form-select {
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.45);
        padding: 0.65rem 0.8rem;
    }

    .modal-footer {
        border-top: 1px solid rgba(148, 163, 184, 0.3);
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
        border-radius: 5px;
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
            padding-top: 3.5rem;
            width: 100%;
            padding-left: 1.25rem;
        }

        .dashboard-shell {
            padding: 1rem;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .page-header h3 {
            font-size: 1.6rem;
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

        .quick-actions {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
            width: 100%;
        }

        .page-header h3 {
            font-size: 1.35rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-card h6 {
            font-size: 0.75rem;
        }

        .stat-card h4 {
            font-size: 1.5rem;
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
            <?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>
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
                            <i class="bi bi-person-plus"></i> Create Employee
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
                <div class="quick-actions">
                    <h5>Quick Actions</h5>
                    <div class="action-buttons">
                        <a class="action-link" href="tasks_dashboard.php"><i class="bi bi-list-check"></i> Tasks</a>
                        <a class="action-link" href="leave_dashboard.php"><i class="bi bi-calendar2-check"></i>
                            Leave</a>
                        <a class="action-link" href="salary_dashboard.php"><i class="bi bi-coin"></i> Salary</a>
                    </div>
                </div>
                <?php elseif ($role === 'HR') : ?>
                <div class="quick-actions">
                    <h5>Quick Actions</h5>
                    <div class="action-buttons">
                        <a class="action-link" href="employee.php"><i class="bi bi-people"></i> Employees</a>
                        <a class="action-link" href="leave_view.php"><i class="bi bi-calendar2-check"></i> Leave</a>
                        <a class="action-link" href="inquiries_dashboard.php"><i class="bi bi-inbox"></i> Inquiries</a>
                        <a class="action-link" href="hr_payroll.php"><i class="bi bi-coin"></i> Payroll</a>
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
                        $id = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 1;
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE status IN ('todo', 'in_progress') AND assigned_to = ?");
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
                        <div class="stat-badge"><i class="bi bi-ui-checks"></i></div>
                        <h6>Completed Tasks</h6>
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
                        <div class="stat-badge"><i class="bi bi-suitcase-lg-fill"></i></div>
                        <h6>Leave Requests</h6>
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
                        <div class="stat-badge"><i class="bi bi-coin"></i></div>
                        <h6>Salary Slips</h6>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM salary_slips");
                        if ($stmt) {
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
                        ?>
                    </div>

                </div>
                <?php endif; ?>

                <?php if (in_array($role, ['HR', 'Admin'], true)) : ?>
                <div class="content-grid">
                    <div class="card-container">
                        <h4><i class="bi bi-people"></i>&nbsp; Newest Employees</h4>
                        <?php
                        $stmt = $conn->prepare("SELECT u.full_name, u.email, e.department, e.job_title, e.hire_date FROM employees e JOIN users u ON e.user_id = u.id ORDER BY e.id DESC LIMIT 12");
                        if ($stmt) {
                            $stmt->execute();
                            $stmt->bind_result($emp_name, $emp_email, $emp_department, $emp_job, $emp_hire);
                            $has_employees = false;
                            echo '<div class="list-group">';
                            while ($stmt->fetch()) {
                                $has_employees = true;
                                $emp_hire_str = ($emp_hire instanceof \DateTime) ? $emp_hire->format('Y-m-d') : (string)($emp_hire ?? 'Not set');
                                echo '<div class="list-group-item">
                                        <strong>' . htmlspecialchars($emp_name) . '</strong>
                                        <small>' . htmlspecialchars($emp_job ?? 'Not set') . ' - ' . htmlspecialchars($emp_department ?? 'Not set') . '</small><br>
                                        <small>' . htmlspecialchars($emp_email ?? 'Not set') . ' - Hired: ' . htmlspecialchars($emp_hire_str) . '</small>
                                      </div>';
                            }
                            if (!$has_employees) {
                                echo '<p class="text-muted">No employee records yet.</p>';
                            }
                            echo '</div>';
                            $stmt->close();
                        } else {
                            echo '<p>DB error</p>';
                        }
                        ?>
                    </div>
                </div>
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
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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
                                <input type="password" name="password" class="form-control" required minlength="6"
                                    placeholder="Minimum 6 characters">
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
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
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