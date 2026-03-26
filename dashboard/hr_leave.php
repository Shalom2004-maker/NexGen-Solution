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
        $id = (int)($_POST['leave_id'] ?? 0);

        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE leave_requests SET status='hr_approved', hr_id=? WHERE id=? AND status='leader_approved'");
            $stmt->bind_param("ii", $uid, $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $success = 'Leave request approved.';
            } else {
                $error = 'Leave request must be leader-approved before HR approval.';
            }
            $stmt->close();
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE leave_requests SET status='rejected', hr_id=? WHERE id=? AND status='leader_approved'");
            $stmt->bind_param("ii", $uid, $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $success = 'Leave request rejected.';
            } else {
                $error = 'Leave request must be leader-approved before HR rejection.';
            }
            $stmt->close();
        }
    }

    header('Location: hr_leave.php' . ($success !== '' ? '?ok=1' : ($error !== '' ? '?err=1' : '')));
    exit();
}

if (isset($_GET['ok'])) {
    $success = 'Action completed.';
}
if (isset($_GET['err'])) {
    $error = 'Unable to process this leave request.';
}

$res = $conn->query("SELECT leave_requests.id, leave_requests.employee_id, users.full_name, leave_requests.start_date, leave_requests.end_date, leave_requests.status
                     FROM leave_requests
                     INNER JOIN employees ON leave_requests.employee_id = employees.id
                     INNER JOIN users ON employees.user_id = users.id
                     ORDER BY leave_requests.start_date DESC");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Leave Approval - NexGen Solution</title>

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
                        <h3 class="mb-1">HR Leave Approval</h3>
                        <p class="text-muted mb-0">Review and approve leader-approved leave requests</p>
                    </div>
                </div>
                <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table m-0">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $res->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $row["employee_id"] ?></td>
                                <td><?= $row["full_name"] ?></td>
                                <td><?= $row["start_date"] ?> to <?= $row["end_date"] ?></td>
                                <td><?= htmlspecialchars($row["status"]) ?></td>
                                <td>
                                    <?php if ($row["status"] === "leader_approved"): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="leave_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                    </form>
                                    <form method="post" class="d-inline ms-1">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="leave_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>Waiting on Leader</button>
                                    <?php endif; ?>
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


