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
        margin-bottom: 1.5rem;
    }

    .page-header h3 {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
        letter-spacing: -0.02em;
    }

    .page-header p {
        color: #5b6777;
        margin: 0;
    }

    .form-container {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        max-width: 640px;
    }

    .form-label {
        font-weight: 600;
        color: #475569;
    }

    .form-control {
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 12px;
        padding: 0.75rem;
    }

    .form-control:focus {
        border-color: #1d4ed8;
        box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.15);
    }

    .btn-primary {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        border: none;
        border-radius: 999px;
        font-weight: 600;
        padding: 0.6rem 1.2rem;
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
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
        }

        .dashboard-shell {
            padding: 1rem;
        }

        .form-container {
            max-width: 100%;
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

        .form-container {
            padding: 1rem;
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
            <?php include "leader_sidebar.php"; ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
            <div class="page-header">
                <div>
                    <h3>Payroll Input</h3>
                    <p>Submit payroll information for team members</p>
                </div>
            </div>

            <div class="form-container">
                <h5 class="mb-4">Add Payroll Information</h5>
                <form method="post">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="mb-3">
                        <label for="emp" class="form-label">Employee ID *</label>
                        <input type="number" id="emp" name="emp" class="form-control" placeholder="Enter employee ID"
                            required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="month" class="form-label">Month *</label>
                            <input type="number" id="month" name="month" class="form-control" placeholder="1-12" min="1"
                                max="12" required>
                        </div>
                        <div class="col-md-6">
                            <label for="year" class="form-label">Year *</label>
                            <input type="number" id="year" name="year" class="form-control" placeholder="YYYY" required>
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
                        <input type="number" id="ded" name="ded" class="form-control" step="0.01" placeholder="Amount">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Submit Payroll</button>
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
