<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
include "../includes/logger.php";
// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
// process only on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        audit_log('csrf', 'Invalid CSRF token on leader_payroll', $_SESSION['uid'] ?? null);
        die('Invalid request');
    }

    if (!is_numeric($_POST["month"]) || $_POST["month"] < 1 || $_POST["month"] > 12) {
        die("Invalid month");
    }

    $stmt = $conn->prepare("INSERT INTO payroll_inputs(employee_id,month,year,overtime_hours,bonus,deductions,submitted_by) 
                          VALUES(?,?,?,?,?,?,?)");
    $stmt->bind_param("iiidddi", $_POST["emp"], $_POST["month"], $_POST["year"], $_POST["ot"], $_POST["bonus"], $_POST["ded"], $_SESSION["uid"]);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        audit_log('payroll_submit', "Payroll input added for emp {$_POST['emp']}", $_SESSION['uid'] ?? null);
    } else {
        audit_log('payroll_failed', "Failed to add payroll input for emp {$_POST['emp']}", $_SESSION['uid'] ?? null);
        echo "Failed";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

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
                        <h3>Payroll Input</h3>
                        <p>Submit payroll information for team members</p>
                    </div>
                </div>

                <div class="form-container mx-auto p-4">
                    <h5 class="mb-4">Add Payroll Information</h5>
                    <form method="POST" class="p-4">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="mb-3">
                            <label for="emp" class="form-label">Employee ID *</label>
                            <input type="number" id="emp" name="emp" class="form-control"
                                placeholder="Enter employee ID" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="month" class="form-label">Month *</label>
                                <input type="number" id="month" name="month" class="form-control" placeholder="1-12"
                                    min="1" max="12" required>
                            </div>
                            <div class="col-md-6">
                                <label for="year" class="form-label">Year *</label>
                                <input type="number" id="year" name="year" class="form-control" placeholder="YYYY"
                                    required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="ot" class="form-label">Overtime Hours</label>
                            <input type="number" id="ot" name="ot" class="form-control" step="0.5" placeholder="Hours">
                        </div>

                        <div class="mb-3">
                            <label for="bonus" class="form-label">Bonus</label>
                            <input type="number" id="bonus" name="bonus" class="form-control" step="0.01"
                                placeholder="Amount">
                        </div>

                        <div class="mb-3">
                            <label for="ded" class="form-label">Deductions</label>
                            <input type="number" id="ded" name="ded" class="form-control" step="0.01"
                                placeholder="Amount">
                        </div>

                        <center>
                            <button type="submit" class="btn btn-primary w-50 mt-3">Submit Payroll</button>
                        </center>
                    </form>
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