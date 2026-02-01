<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";
include "../includes/logger.php";

// Get employee ID
$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;

// Initialize form variables
$error = '';
$success = '';

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        audit_log('csrf', 'Invalid CSRF token on leave', $_SESSION['uid'] ?? null);
        $error = 'Invalid request';
    } else {
        // Ensure the user has an employee record
        $empQ = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
        $empQ->bind_param('i', $uid);
        $empQ->execute();
        $empR = $empQ->get_result()->fetch_assoc();
        $empQ->close();

        if (empty($empR)) {
            $error = "No employee record found for current user.";
        } else {
            $employee_id = (int)$empR['id'];
            $stmt = $conn->prepare("INSERT INTO leave_requests(employee_id,start_date,end_date,leave_type,reason) VALUES(?,?,?,?,?)");
            $stmt->bind_param("issss", $employee_id, $_POST["start"], $_POST["end"], $_POST["type"], $_POST["reason"]);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                audit_log('leave_request', "Leave request submitted by user {$uid}", $_SESSION['uid'] ?? null);
                $success = 'Leave request submitted successfully!';
                // Regenerate CSRF token for security
                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                // Clear form data
                $_POST = [];
            } else {
                audit_log('leave_request_failed', "Failed leave request by user {$uid}", $_SESSION['uid'] ?? null);
                $error = "Failed to submit leave request.";
            }
            $stmt->close();
        }
    }
}

// Fetch leave statistics
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$days_approved = 0;

// Get employee_id from user_id
$emp_stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
if ($emp_stmt) {
    $emp_stmt->bind_param('i', $uid);
    $emp_stmt->execute();
    $emp_result = $emp_stmt->get_result();
    $emp_row = $emp_result->fetch_assoc();
    $employee_id = $emp_row['id'] ?? 0;
    $emp_stmt->close();
} else {
    $employee_id = 0;
}

if ($employee_id > 0) {
    // Pending Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ? AND status = 'pending'");
    if ($stmt) {
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $pending_count = (int)($row['count'] ?? 0);
        $stmt->close();
    }

    // Approved Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ? AND (status = 'hr_approved' OR status = 'leader_approved')");
    if ($stmt) {
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $approved_count = (int)($row['count'] ?? 0);
        $stmt->close();
    }

    // Rejected Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ? AND status = 'rejected'");
    if ($stmt) {
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $rejected_count = (int)($row['count'] ?? 0);
        $stmt->close();
    }

    // Days Approved
    $stmt = $conn->prepare("SELECT SUM(DATEDIFF(end_date, start_date)) as total_days FROM leave_requests WHERE employee_id = ? AND (status = 'hr_approved' OR status = 'leader_approved')");
    if ($stmt) {
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $days_approved = (int)($row['total_days'] ?? 0);
        $stmt->close();
    }
}

// Get filter status from URL
$filterStatus = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filters = ['all', 'pending', 'approved', 'rejected'];
$filterStatus = in_array($filterStatus, $filters) ? $filterStatus : 'all';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

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

    .main-content {
        flex: 1;
        background-color: #f5f5f5d2;
        padding-top: 1.7rem;
        padding-left: 18rem;
        padding-right: 2rem;
        padding-bottom: 2rem;
        overflow-x: hidden;
        width: 75%;
    }

    .page-header {
        margin-bottom: 2rem;
    }

    .page-header h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #333;
    }

    .page-header p {
        color: lightslategray;
        font-size: 0.9rem;
    }

    /* Metric Cards */
    .metric-card {
        background: #ffffff;
        border: 1px solid #d4d4d4;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.15s ease;
        position: relative;
        overflow: hidden;
    }

    .metric-card:hover {
        transform: translateY(-3px);
        border-color: #337ccfe2;
        box-shadow: 0 6px 20px rgba(51, 124, 207, 0.08);
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 60px;
        height: 60px;
        background: rgba(51, 124, 207, 0.06);
        border-radius: 50%;
        transform: translate(18px, -18px);
    }

    .metric-icon {
        font-size: 1.8rem;
        color: #337ccfe2;
        margin-bottom: 0.5rem;
    }

    .metric-label {
        color: #666;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #337ccfe2;
        margin-bottom: 0.25rem;
    }

    /* Filter Buttons */
    .filter-buttons {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .filter-btn {
        background: #ffffff;
        padding: 0.5rem 1.25rem;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        color: #666;
        transition: all 0.12s ease;
        cursor: pointer;
    }

    .filter-btn.active {
        background: #337ccfe2;
        border-color: #337ccfe2;
        color: white;
    }

    .filter-btn:hover {
        border-color: #337ccfe2;
        color: #337ccfe2;
    }

    /* Section Title */
    .section-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-title a {
        font-size: 0.85rem;
        color: #337ccfe2;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .section-title a:hover {
        color: #2563a8;
    }

    /* Leave Request Cards */
    .leave-request-card {
        background: #ffffff;
        border: 1px solid #d4d4d4;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.15s ease;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .leave-request-card:hover {
        border-color: #337ccfe2;
        box-shadow: 0 2px 8px rgba(51, 124, 207, 0.06);
        transform: translateX(2px);
    }

    .leave-icon-box {
        width: 50px;
        height: 50px;
        background: rgba(51, 124, 207, 0.1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .leave-icon-box i {
        font-size: 1.5rem;
        color: #337ccfe2;
    }

    .leave-request-content {
        flex: 1;
    }

    .leave-request-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .leave-requestor {
        font-weight: 600;
        color: #333;
    }

    .leave-reason {
        color: #777;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .leave-dates {
        color: #666;
        font-size: 0.85rem;
        margin-bottom: 0.75rem;
    }

    .leave-date-range {
        display: inline-block;
        margin-right: 1.5rem;
    }

    .leave-days-count {
        display: inline-block;
        color: #337ccfe2;
        font-weight: 600;
    }

    .leave-meta {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .leave-type {
        display: inline-block;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #337ccfe2;
        color: white;
    }

    .leave-status {
        display: inline-block;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .leave-status.pending {
        background-color: #fbbf24;
        color: white;
    }

    .leave-status.approved {
        background-color: #4ade80;
        color: white;
    }

    .leave-status.rejected {
        background-color: #ef4444;
        color: white;
    }

    .leave-status.leader_approved {
        background-color: #60a5fa;
        color: white;
    }

    .leave-status.hr_approved {
        background-color: #10b981;
        color: white;
    }

    /* Empty State */
    .empty-state {
        background: #ffffff;
        border: 1px solid #d4d4d4;
        border-radius: 8px;
        padding: 3rem 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .empty-state i {
        font-size: 3rem;
        color: #ccc;
        margin-bottom: 1rem;
        display: block;
    }

    .empty-state p {
        color: #999;
        font-size: 0.95rem;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .btn-primary-custom {
        background: #337ccfe2;
        border: none;
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.12s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary-custom:hover {
        background: #2563a8;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(51, 124, 207, 0.2);
    }

    /* Modal Styles */
    .modal-content {
        border: 1px solid #d4d4d4;
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }

    .modal-title {
        font-weight: 700;
        color: #333;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .form-control,
    .form-select {
        border: 1px solid #d4d4d4;
        border-radius: 6px;
        padding: 0.75rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #337ccfe2;
        box-shadow: 0 0 0 0.2rem rgba(51, 124, 207, 0.25);
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 1rem;
    }

    .btn-primary {
        background-color: #337ccfe2;
        border-color: #337ccfe2;
    }

    .btn-primary:hover {
        background-color: #2563a8;
        border-color: #2563a8;
    }

    .btn-outline-secondary {
        color: #6c757d;
        border-color: #6c757d;
    }

    .btn-outline-secondary:hover {
        background-color: #6c757d;
        border-color: #6c757d;
        color: white;
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
            padding: 1.5rem;
            width: 100%;
            padding-top: 3.5rem;
            padding-left: 1.5rem;
        }

        .page-header h2 {
            font-size: 1.5rem;
        }

        .metric-value {
            font-size: 1.6rem;
        }

        .leave-request-card {
            flex-direction: column;
            gap: 0.75rem;
        }

        .leave-request-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-buttons {
            flex-direction: column;
        }

        .filter-btn {
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
        }

        .page-header h2 {
            font-size: 1.25rem;
        }

        .metric-card {
            padding: 1rem;
        }

        .metric-value {
            font-size: 1.5rem;
        }

        .metric-label {
            font-size: 0.75rem;
        }

        .empty-state {
            padding: 2rem 1rem;
        }

        .empty-state i {
            font-size: 2rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn-primary-custom {
            width: 100%;
            text-align: center;
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
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-calendar-check"></i> Leave Requests</h2>
                    <p>View and submit leave requests</p>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="button" class="btn-primary-custom" data-bs-toggle="modal"
                        data-bs-target="#leaveRequestModal">
                        <i class="bi bi-plus-circle"></i> &nbsp; Request Leave
                    </button>

                    <!-- Leave Request Modal -->
                    <div class="modal fade" id="leaveRequestModal" tabindex="-1"
                        aria-labelledby="leaveRequestModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-bottom">
                                    <h1 class="modal-title fs-5" id="leaveRequestModalLabel">
                                        <i class="bi bi-calendar-plus"></i> &nbsp; Request Leave
                                    </h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                        <div class="mb-3">
                                            <label for="startDate" class="form-label">Start Date <span
                                                    style="color: #ef4444;">*</span></label>
                                            <input type="date" class="form-control" id="startDate" name="start" required
                                                value="<?= htmlspecialchars($_POST['start'] ?? '') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="endDate" class="form-label">End Date <span
                                                    style="color: #ef4444;">*</span></label>
                                            <input type="date" class="form-control" id="endDate" name="end" required
                                                value="<?= htmlspecialchars($_POST['end'] ?? '') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="leaveType" class="form-label">Leave Type <span
                                                    style="color: #ef4444;">*</span></label>
                                            <select class="form-select" id="leaveType" name="type" required>
                                                <option value="">Select Leave Type</option>
                                                <option value="sick"
                                                    <?= (isset($_POST['type']) && $_POST['type'] === 'sick') ? 'selected' : '' ?>>
                                                    Sick Leave</option>
                                                <option value="annual"
                                                    <?= (isset($_POST['type']) && $_POST['type'] === 'annual') ? 'selected' : '' ?>>
                                                    Annual Leave</option>
                                                <option value="unpaid"
                                                    <?= (isset($_POST['type']) && $_POST['type'] === 'unpaid') ? 'selected' : '' ?>>
                                                    Unpaid Leave</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="reason" class="form-label">Reason <span
                                                    style="color: #ef4444;">*</span></label>
                                            <textarea class="form-control" id="reason" name="reason" rows="4" required
                                                placeholder="Enter the reason for your leave request">
                                                <?= htmlspecialchars($_POST['reason'] ?? '') ?>
                                            </textarea>
                                        </div>

                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-outline-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-send"></i> &nbsp; Submit Request
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metrics Cards -->
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card">
                        <i class="bi bi-clock-history metric-icon"></i>
                        <div class="metric-label">Pending Requests</div>
                        <div class="metric-value"><?= $pending_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card">
                        <i class="bi bi-check2-circle metric-icon"></i>
                        <div class="metric-label">Approved</div>
                        <div class="metric-value"><?= $approved_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card">
                        <i class="bi bi-x-circle metric-icon"></i>
                        <div class="metric-label">Rejected</div>
                        <div class="metric-value"><?= $rejected_count ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="metric-card">
                        <i class="bi bi-calendar metric-icon"></i>
                        <div class="metric-label">Days Approved</div>
                        <div class="metric-value"><?= $days_approved ?></div>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="col-lg-12 col-md-6 col-12 bg-light-subtle p-3 border rounded mb-3">
                <div class="filter-buttons">
                    <a href="?filter=all"
                        class="filter-btn border rounded <?= $filterStatus === 'all' ? 'active' : '' ?>">
                        All Requests
                    </a>
                    <a href="?filter=pending"
                        class="filter-btn border rounded <?= $filterStatus === 'pending' ? 'active' : '' ?>">
                        Pending
                    </a>
                    <a href="?filter=approved"
                        class="filter-btn border rounded <?= $filterStatus === 'approved' ? 'active' : '' ?>">
                        Approved
                    </a>
                    <a href="?filter=rejected"
                        class="filter-btn border rounded <?= $filterStatus === 'rejected' ? 'active' : '' ?>">
                        Rejected
                    </a>
                </div>
            </div>

            <!-- Leave Requests List -->
            <div class="section-title">
                <span>My Leave Requests</span>
            </div>

            <?php
            if ($employee_id > 0) {
                // Build query based on filter
                $where = 'WHERE employee_id = ?';
                $params = [$employee_id];
                $types = 'i';

                if ($filterStatus === 'pending') {
                    $where .= " AND status = 'pending'";
                } elseif ($filterStatus === 'approved') {
                    $where .= " AND (status = 'approved' OR status = 'hr_approved' OR status = 'leader_approved')";
                } elseif ($filterStatus === 'rejected') {
                    $where .= " AND status = 'rejected'";
                }

                // Fetch leave requests for the employee based on filter
                $query = "SELECT id, leave_type, reason, start_date, end_date, status FROM leave_requests " . $where . " ORDER BY applied_at DESC";
                $stmt = $conn->prepare($query);
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $has_requests = false;

                    while ($leave = $result->fetch_assoc()) {
                        $has_requests = true;
                        $start_date = new DateTime($leave['start_date']);
                        $end_date = new DateTime($leave['end_date']);
                        $days_diff = $end_date->diff($start_date)->days + 1;
                        $status = strtolower($leave['status']);
                        $status_badge_class = match ($status) {
                            'pending' => 'pending',
                            'rejected' => 'rejected',
                            'leader_approved' => 'leader_approved',
                            'hr_approved' => 'hr_approved',
                            default => 'pending'
                        };
                        $status_display = ucfirst(str_replace('_', ' ', $leave['status']));
            ?>
            <div class="leave-request-card">
                <div class="leave-icon-box">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div class="leave-request-content">
                    <div class="leave-request-header">
                        <div class="leave-requestor">
                            <?= htmlspecialchars($_SESSION['name'] ?? 'Employee') ?>
                        </div>
                        <span class="leave-status <?= $status_badge_class ?>">
                            <?= $status_display ?>
                        </span>
                    </div>
                    <div class="leave-reason">
                        <?= htmlspecialchars($leave['reason']) ?>
                    </div>
                    <div class="leave-dates">
                        <span class="leave-date-range">
                            <i class="bi bi-calendar2"></i>
                            <?= htmlspecialchars($leave['start_date']) ?> - <?= htmlspecialchars($leave['end_date']) ?>
                        </span>
                        <span class="leave-days-count">
                            <?= $days_diff ?> days
                        </span>
                    </div>
                    <div class="leave-meta">
                        <span class="leave-type">
                            <?= htmlspecialchars($leave['leave_type']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php
                    }

                    if (!$has_requests) {
                    ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No leave requests yet</p>
                <p style="font-size: 0.85rem; color: #bbb; margin-top: 0.5rem;">
                    Click "Request Leave" to submit your first leave request
                </p>
            </div>
            <?php
                    }
                    $stmt->close();
                } else {
                    echo '<p>Database error</p>';
                }
            } else {
                ?>
            <div class="empty-state">
                <i class="bi bi-exclamation-circle"></i>
                <p>Unable to load leave requests</p>
            </div>
            <?php
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function filterRequests(filter) {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Filter logic can be enhanced with AJAX to reload requests
        console.log('Filter: ' + filter);
    }

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