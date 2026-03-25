<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "Admin"]);
include "../includes/db.php";
include "../includes/logger.php";

// Get employee ID
$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
$role = $_SESSION['role'] ?? '';

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

$can_view_all = in_array($role, ['ProjectLeader', 'Admin'], true);

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

$scope_where = $can_view_all ? '' : 'WHERE employee_id = ?';
$scope_types = $can_view_all ? '' : 'i';
$scope_params = $can_view_all ? [] : [$employee_id];

if ($can_view_all || $employee_id > 0) {
    // Pending Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests $scope_where" . ($scope_where ? " AND" : " WHERE") . " status = 'pending'");
    if ($stmt) {
        if (!$can_view_all) {
            $stmt->bind_param($scope_types, ...$scope_params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $pending_count = (int)($row['count'] ?? 0);
        $stmt->close();
    }

    // Approved Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests $scope_where" . ($scope_where ? " AND" : " WHERE") . " (status = 'hr_approved' OR status = 'leader_approved')");
    if ($stmt) {
        if (!$can_view_all) {
            $stmt->bind_param($scope_types, ...$scope_params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $approved_count = (int)($row['count'] ?? 0);
        $stmt->close();
    }

    // Rejected Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests $scope_where" . ($scope_where ? " AND" : " WHERE") . " status = 'rejected'");
    if ($stmt) {
        if (!$can_view_all) {
            $stmt->bind_param($scope_types, ...$scope_params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $rejected_count = (int)($row['count'] ?? 0);
        $stmt->close();
    }

    // Days Approved
    $stmt = $conn->prepare("SELECT SUM(DATEDIFF(end_date, start_date)) as total_days FROM leave_requests $scope_where" . ($scope_where ? " AND" : " WHERE") . " (status = 'hr_approved' OR status = 'leader_approved')");
    if ($stmt) {
        if (!$can_view_all) {
            $stmt->bind_param($scope_types, ...$scope_params);
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

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
                        <h2>Leave Requests</h2>
                        <p>View and submit leave requests</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <?php if ($can_view_all) : ?>
                        <a href="leave_view.php" class="btn-primary-custom text-decoration-none">
                            <i class="bi bi-eye"></i> &nbsp; View All Leaves
                        </a>
                        <?php endif; ?>
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
                                                <input type="date" class="form-control" id="startDate" name="start"
                                                    required value="<?= htmlspecialchars($_POST['start'] ?? '') ?>">
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
                                                    <option value="personal"
                                                        <?= (isset($_POST['type']) && $_POST['type'] === 'personal') ? 'selected' : '' ?>>
                                                        Personal Leave</option>
                                                    <option value="vacation"
                                                        <?= (isset($_POST['type']) && $_POST['type'] === 'vacation') ? 'selected' : '' ?>>
                                                        Vacation Leave</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="reason" class="form-label">Reason <span
                                                        style="color: #ef4444;">*</span></label>
                                                <textarea class="form-control" id="reason" name="reason" rows="4"
                                                    required placeholder="Enter the reason for your leave request">
                                                <?= htmlspecialchars($_POST['reason'] ?? '') ?>
                                            </textarea>
                                            </div>

                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-outline-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn-primary-custom">
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
            </div>

            <!-- Metrics Cards -->
            <div class="row mt-4 mb-4">
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
            <div class="table-container filter-panel mb-3">
                <div class="filter-buttons">
                    <a href="?filter=all"
                        class="filter-btn text-decoration-none <?= $filterStatus === 'all' ? 'active' : '' ?>">
                        All Requests
                    </a>
                    <a href="?filter=pending"
                        class="filter-btn text-decoration-none <?= $filterStatus === 'pending' ? 'active' : '' ?>">
                        Pending
                    </a>
                    <a href="?filter=approved"
                        class="filter-btn text-decoration-none <?= $filterStatus === 'approved' ? 'active' : '' ?>">
                        Approved
                    </a>
                    <a href="?filter=rejected"
                        class="filter-btn text-decoration-none <?= $filterStatus === 'rejected' ? 'active' : '' ?>">
                        Rejected
                    </a>
                </div>
            </div>

            <!-- Leave Requests List -->
            <div class="section-title mt-4 mb-2">
                <span class="fw-bold"><?= $can_view_all ? 'Leave Requests' : 'My Leave Requests' ?></span>
                <a href="leave_view.php" class="section-link text-decoration-none">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <?php
            if ($can_view_all || $employee_id > 0) {
                // Build query based on filter
                $where = $can_view_all ? 'WHERE 1=1' : 'WHERE lr.employee_id = ?';
                $params = $can_view_all ? [] : [$employee_id];
                $types = $can_view_all ? '' : 'i';

                if ($filterStatus === 'pending') {
                    $where .= " AND lr.status = 'pending'";
                } elseif ($filterStatus === 'approved') {
                    $where .= " AND (lr.status = 'hr_approved' OR lr.status = 'leader_approved')";
                } elseif ($filterStatus === 'rejected') {
                    $where .= " AND lr.status = 'rejected'";
                }

                // Fetch leave requests for the employee based on filter
                $query = "SELECT lr.id, lr.leave_type, lr.reason, lr.start_date, lr.end_date, lr.status, u.full_name as requestor_name
                          FROM leave_requests lr
                          LEFT JOIN employees e ON lr.employee_id = e.id
                          LEFT JOIN users u ON e.user_id = u.id
                          $where
                          ORDER BY lr.applied_at DESC";
                $stmt = $conn->prepare($query);
                if ($stmt) {
                    if ($types !== '') {
                        $stmt->bind_param($types, ...$params);
                    }
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
                            <?= htmlspecialchars($leave['requestor_name'] ?: ($_SESSION['name'] ?? 'Employee')) ?>
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
    </div>

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
        const leaveRequestModal = document.getElementById('leaveRequestModal');

        if (leaveRequestModal && leaveRequestModal.parentElement !== document.body) {
            document.body.appendChild(leaveRequestModal);
        }

        const closeSidebar = function() {
            if (nexgenSidebar) {
                nexgenSidebar.classList.remove('show');
            }

            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('show');
            }
        };

        const cleanupModalArtifacts = function() {
            if (document.querySelector('.modal.show')) {
                return;
            }

            document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                backdrop.remove();
            });

            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        };

        if (sidebarToggleBtn && nexgenSidebar) {
            sidebarToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpening = !nexgenSidebar.classList.contains('show');
                nexgenSidebar.classList.toggle('show', isOpening);
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('show', isOpening);
                }
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        if (nexgenSidebar) {
            document.querySelectorAll('.nexgen-sidebar-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeSidebar();
                    }
                });
            });
        }

        document.addEventListener('show.bs.modal', closeSidebar);
        document.addEventListener('hidden.bs.modal', cleanupModalArtifacts);
    });
    </script>
</body>

</html>

