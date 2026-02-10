<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

// Approve action (kept simple using GET for compatibility)
if (isset($_GET["approve"])) {
    $id = intval($_GET["approve"]);
    $uid = intval($_SESSION["uid"]);

    $stmt = $conn->prepare("UPDATE leave_requests SET status='leader_approved', leader_id=? WHERE id=?");
    $stmt->bind_param("ii", $uid, $id);
    $stmt->execute();
    $stmt->close();
    audit_log('leader_approve', "Leader {$uid} approved leave {$id}", $uid);
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
                        <a href="?approve=<?= urlencode($row['id']) ?>" class="btn btn-sm btn-success">Approve</a>
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
