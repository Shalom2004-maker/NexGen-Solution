<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

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
        if ($action === 'approve') {
            $id = (int)($_POST['leave_id'] ?? 0);
            $stmt = $conn->prepare("UPDATE leave_requests SET status='leader_approved', leader_id=? WHERE id=? AND status='pending'");
            $stmt->bind_param("ii", $uid, $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $success = 'Leave request approved.';
                audit_log('leader_approve', "Leader {$uid} approved leave {$id}", $uid);
            } else {
                $error = 'Unable to approve leave request. It may already be processed.';
            }
            $stmt->close();
        }
    }

    header('Location: leader_leave.php' . ($success !== '' ? '?ok=1' : ($error !== '' ? '?err=1' : '')));
    exit();
}

if (isset($_GET['ok'])) {
    $success = 'Leave request approved.';
}
if (isset($_GET['err'])) {
    $error = 'Unable to approve leave request. It may already be processed.';
}

// Fetch pending leave requests with employee name
$sql = "SELECT l.*, e.user_id, u.full_name AS employee_name
        FROM leave_requests l
        JOIN employees e ON l.employee_id = e.id
        JOIN users u ON e.user_id = u.id
        WHERE l.status = 'pending'
        ORDER BY l.start_date DESC";
$res = $conn->query($sql);
$queryError = $res === false ? $conn->error : '';
?>

<?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>

<div class="main-content" style="margin-left: 19rem;">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div class="mt-3">
            <h3><i class="bi bi-file-text"></i> Team Leave Requests</h3>
            <p class="text-muted">Pending requests awaiting your approval</p>
        </div>
    </div>

    <div class="table-responsive border rounded shadow col-lg-11 col-md-9 col-12 p-2">
        <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <table class="table table-striped">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Employee</th>
                    <th>Dates</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($queryError): ?>
                <tr>
                    <td colspan="5" class="text-danger">Unable to load leave requests.</td>
                </tr>
                <?php elseif ($res->num_rows === 0): ?>
                <tr>
                    <td colspan="5" class="text-muted">No pending leave requests.</td>
                </tr>
                <?php else: ?>
                <?php while ($row = $res->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['employee_name']) ?></td>
                    <td><?= htmlspecialchars($row['start_date']) ?> to <?= htmlspecialchars($row['end_date']) ?></td>
                    <td><?= htmlspecialchars(substr($row['reason'] ?? '', 0, 80)) ?></td>
                    <td class="d-flex gap-3">
                        <form method="post" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="leave_id" value="<?= (int)$row['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <a href="leave_view.php?id=<?= urlencode($row['id']) ?>"
                            class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>

</html>
