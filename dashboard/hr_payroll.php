<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";

if (isset($_GET["approve"])) {
    $id = intval($_GET["approve"]);
    $uid = intval($_SESSION["uid"]);

    $updateStmt = $conn->prepare("UPDATE payroll_inputs SET status='approved' WHERE id=?");
    $updateStmt->bind_param("i", $id);
    $updateStmt->execute();
    $updateStmt->close();

    $selectStmt = $conn->prepare("SELECT p.*, e.salary_base FROM payroll_inputs p 
                        JOIN employees e ON p.employee_id=e.id WHERE p.id=?");
    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();
    $data = $selectStmt->get_result()->fetch_assoc();
    $selectStmt->close();

    if ($data) {
        $net = $data["salary_base"] + ($data["overtime_hours"] * 5) + $data["bonus"] - $data["deductions"];

        $stmt = $conn->prepare("INSERT INTO salary_slips(employee_id,month,year,base_salary,overtime_pay,bonus,deductions,net_salary,generated_by) VALUES(?,?,?,?,?,?,?,?,?)");
        // Types: i=employee_id, i=month, i=year, d=base_salary, d=overtime_pay, d=bonus, d=deductions, d=net_salary, i=generated_by
        $employee_id = (int)$data["employee_id"];
        $month = (int)$data["month"];
        $year = (int)$data["year"];
        $base_salary = (float)$data["salary_base"];
        $overtime_pay = (float)($data["overtime_hours"] * 5);
        $bonus = (float)$data["bonus"];
        $deductions = (float)$data["deductions"];
        $net_salary = (float)$net;

        $stmt->bind_param("iiidddddi", $employee_id, $month, $year, $base_salary, $overtime_pay, $bonus, $deductions, $net_salary, $uid);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            echo "Failed";
        }
        $stmt->close();
    }
}

$res = $conn->query("SELECT * FROM payroll_inputs WHERE status='pending'");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Approval - NexGen Solution</title>

    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>
    * {
        box-sizing: border-box;
        font-family: "Sora", sans-serif;
        margin: 0;
        padding: 0;
    }

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
        padding-top: 2rem;
        padding-left: 18rem;
        padding-right: 2.5rem;
        padding-bottom: 2rem;
    }

    .dashboard-shell {
        background: radial-gradient(1200px 400px at 20% -10%, rgba(30, 64, 175, 0.12), transparent 60%),
            radial-gradient(800px 300px at 90% 10%, rgba(14, 116, 144, 0.12), transparent 60%);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
    }

    .table-responsive {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .table thead th {
        background-color: #f8fafc;
        color: #334155;
        font-weight: 600;
    }

    .btn-success {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        border: none;
        border-radius: 999px;
        font-weight: 600;
        padding: 0.5rem 1rem;
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.2);
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
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
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
            <?php include "hr_sidebar.php"; ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h3 class="mb-1">Payroll Approval</h3>
                        <p class="text-muted mb-0">Approve payroll inputs for employee salary slips</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped m-0">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Period</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $res->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $r["employee_id"] ?></td>
                                <td><?= $r["month"] ?> / <?= $r["year"] ?></td>
                                <td><a href="?approve=<?= $r["id"] ?>" class="btn btn-success btn-sm">Approve</a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
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
