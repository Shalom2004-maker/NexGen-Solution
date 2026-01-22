<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";
include "../includes/logger.php";

$uid = $_SESSION["uid"] ?? 0;
$error = '';
$success = '';

// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// process only on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        audit_log('csrf', 'Invalid CSRF token on leave', $_SESSION['uid'] ?? null);
        $error = 'Invalid request';
    } else {
        // ensure the user has an employee record
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
                header('Location: leave_view.php');
                exit();
            } else {
                audit_log('leave_request_failed', "Failed leave request by user {$uid}", $_SESSION['uid'] ?? null);
                $error = "Failed to submit leave request.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave - Employee Dashboard</title>

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
            font-family: "Oswald", sans-serif;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h3 {
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .page-header p {
            color: lightslategray;
            margin: 0;
        }

        .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
        }

        .form-container .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-container .form-control,
        .form-container .form-select {
            border: 1px solid #d4d4d4;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .form-container .form-control:focus,
        .form-container .form-select:focus {
            border-color: #337ccfe2;
            box-shadow: 0 0 0 0.2rem rgba(51, 124, 207, 0.25);
        }

        .alert {
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .btn-submit {
            background-color: #337ccfe2;
            color: white;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #2563a8;
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
                padding-top: 3.5rem;
            }

            .form-container {
                max-width: 100%;
                padding: 1.5rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 1.5rem;
            }

            .page-header h3 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
                padding-top: 3rem;
            }

            .form-container {
                padding: 1rem;
            }

            .page-header h3 {
                font-size: 1.25rem;
            }

            .form-container .form-label {
                font-size: 0.9rem;
            }

            .btn-submit {
                padding: 0.6rem 1.5rem;
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
                <div>
                    <h3>Request Leave</h3>
                    <p>Submit a new leave request</p>
                </div>
                <a href="leave_view.php" class="btn btn-outline-secondary">View My Requests</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> Leave request submitted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="mb-3">
                        <label for="startDate" class="form-label">Start Date *</label>
                        <input type="date" class="form-control" id="startDate" name="start" required
                            value="<?= htmlspecialchars($_POST['start'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="endDate" class="form-label">End Date *</label>
                        <input type="date" class="form-control" id="endDate" name="end" required
                            value="<?= htmlspecialchars($_POST['end'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="leaveType" class="form-label">Leave Type *</label>
                        <select class="form-select" id="leaveType" name="type" required>
                            <option value="">Select Leave Type</option>
                            <option value="sick" <?= (isset($_POST['type']) && $_POST['type'] === 'sick') ? 'selected' : '' ?>>Sick Leave</option>
                            <option value="annual" <?= (isset($_POST['type']) && $_POST['type'] === 'annual') ? 'selected' : '' ?>>Annual Leave</option>
                            <option value="unpaid" <?= (isset($_POST['type']) && $_POST['type'] === 'unpaid') ? 'selected' : '' ?>>Unpaid Leave</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason *</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" required
                            placeholder="Enter the reason for your leave request"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit mb-3">Submit Request</button>
                    <a href="employee.php" class="btn btn-outline-secondary w-100">Cancel</a>
                </form>
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