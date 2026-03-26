<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION["uid"] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        $error = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['payroll_id'] ?? 0);

        if ($action === 'approve' && $id > 0) {
            try {
                $conn->begin_transaction();

                $selectStmt = $conn->prepare("SELECT p.*, e.salary_base
                                              FROM payroll_inputs p
                                              JOIN employees e ON p.employee_id = e.id
                                              WHERE p.id = ? AND p.status = 'pending'
                                              LIMIT 1");
                $selectStmt->bind_param("i", $id);
                $selectStmt->execute();
                $data = $selectStmt->get_result()->fetch_assoc();
                $selectStmt->close();

                if (!$data) {
                    throw new Exception('Payroll input not found or already processed.');
                }

                $employee_id = (int)$data["employee_id"];
                $month = (int)$data["month"];
                $year = (int)$data["year"];
                $base_salary = (float)$data["salary_base"];
                $overtime_hours = (float)($data["overtime_hours"] ?? 0);
                $bonus = (float)($data["bonus"] ?? 0);
                $deductions = (float)($data["deductions"] ?? 0);
                $overtime_pay = $overtime_hours * 5;
                $net_salary = $base_salary + $overtime_pay + $bonus - $deductions;

                $existsStmt = $conn->prepare("SELECT id FROM salary_slips WHERE employee_id = ? AND month = ? AND year = ? LIMIT 1");
                $existsStmt->bind_param("iii", $employee_id, $month, $year);
                $existsStmt->execute();
                $existingSlip = $existsStmt->get_result()->fetch_assoc();
                $existsStmt->close();

                if (!$existingSlip) {
                    $insertStmt = $conn->prepare("INSERT INTO salary_slips(employee_id,month,year,base_salary,overtime_pay,bonus,deductions,net_salary,generated_by) VALUES(?,?,?,?,?,?,?,?,?)");
                    $insertStmt->bind_param("iiidddddi", $employee_id, $month, $year, $base_salary, $overtime_pay, $bonus, $deductions, $net_salary, $uid);
                    if (!$insertStmt->execute() || $insertStmt->affected_rows < 1) {
                        $insertStmt->close();
                        throw new Exception('Failed to generate salary slip.');
                    }
                    $insertStmt->close();
                }

                $updateStmt = $conn->prepare("UPDATE payroll_inputs SET status='approved' WHERE id=? AND status='pending'");
                $updateStmt->bind_param("i", $id);
                $updateStmt->execute();
                $updateStmt->close();

                $conn->commit();
                $success = $existingSlip ? 'Payroll approved. Existing salary slip was reused.' : 'Payroll approved and salary slip generated.';
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    header('Location: hr_payroll.php' . ($success !== '' ? '?ok=1' : ($error !== '' ? '?err=1' : '')));
    exit();
}

if (isset($_GET['ok'])) {
    $success = 'Payroll request processed successfully.';
}
if (isset($_GET['err'])) {
    $error = 'Unable to process this payroll request.';
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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h3 class="mb-1">Payroll Approval</h3>
                        <p class="text-muted mb-0">Approve payroll inputs for employee salary slips</p>
                    </div>
                </div>
                <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
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
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="payroll_id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                    </form>
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

