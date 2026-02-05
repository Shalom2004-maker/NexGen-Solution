<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "HR", "Admin"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if (!isset($_GET['id'])) {
    header('Location: leave_view.php');
    exit();
}

$leaveId = (int)$_GET['id'];
$uid = (int)($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? '';

// Fetch leave request with employee info
$stmt = $conn->prepare("SELECT l.*, e.user_id, u.full_name AS employee_name 
                        FROM leave_requests l 
                        JOIN employees e ON l.employee_id = e.id 
                        JOIN users u ON e.user_id = u.id 
                        WHERE l.id = ?");
$stmt->bind_param('i', $leaveId);
$stmt->execute();
$leave = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$leave) {
    header('Location: leave_view.php');
    exit();
}

// Permission check: Employee can only edit their own pending requests
if ($role === 'Employee') {
    if ($leave['user_id'] != $uid || $leave['status'] !== 'pending') {
        http_response_code(403);
        die('You can only edit your own pending leave requests.');
    }
}

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View/Edit Leave Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">
    <style>
    * {
        box-sizing: border-box;
        font-family: "Sora", sans-serif;
    }

    body {
        background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
        color: #1f2937;
        min-height: 100vh;
    }

    .dashboard-shell {
        background: radial-gradient(1200px 400px at 20% -10%, rgba(30, 64, 175, 0.12), transparent 60%),
            radial-gradient(800px 300px at 90% 10%, rgba(14, 116, 144, 0.12), transparent 60%);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
    }

    .card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.35);
    }

    .form-control,
    .form-select {
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 12px;
        padding: 0.75rem;
    }

    .form-control:focus,
    .form-select:focus {
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

    .btn-secondary {
        border-radius: 999px;
    }
    </style>
</head>

<body class="container py-4">
    <div class="dashboard-shell">
        <h3 class="mb-2">View/Edit Leave Request</h3>
        <a href="leave_view.php" class="btn btn-secondary mb-3">Back to Leave Requests</a>

        <div class="card">
            <div class="card-body">
                <form method="post" action="leave_update.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="leave_id" value="<?= htmlspecialchars($leave['id']) ?>">

                    <?php if ($role !== 'Employee'): ?>
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($leave['employee_name']) ?>"
                            readonly>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input name="start_date" type="date" class="form-control"
                            value="<?= htmlspecialchars($leave['start_date']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input name="end_date" type="date" class="form-control"
                            value="<?= htmlspecialchars($leave['end_date']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-control" required>
                            <option value="sick" <?= ($leave['leave_type'] === 'sick') ? 'selected' : '' ?>>Sick
                            </option>
                            <option value="annual" <?= ($leave['leave_type'] === 'annual') ? 'selected' : '' ?>>Annual
                            </option>
                            <option value="unpaid" <?= ($leave['leave_type'] === 'unpaid') ? 'selected' : '' ?>>Unpaid
                            </option>
                            <option value="personal" <?= ($leave['leave_type'] === 'personal') ? 'selected' : '' ?>>
                                Personal
                            </option>
                            <option value="vacation" <?= ($leave['leave_type'] === 'vacation') ? 'selected' : '' ?>>
                                Vacation
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="4"
                            required><?= htmlspecialchars($leave['reason']) ?></textarea>
                    </div>

                    <?php if (in_array($role, ['ProjectLeader', 'HR', 'Admin'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="pending" <?= ($leave['status'] === 'pending') ? 'selected' : '' ?>>Pending
                            </option>
                            <option value="leader_approved"
                                <?= ($leave['status'] === 'leader_approved') ? 'selected' : '' ?>>Leader Approved
                            </option>
                            <option value="hr_approved" <?= ($leave['status'] === 'hr_approved') ? 'selected' : '' ?>>HR
                                Approved</option>
                            <option value="rejected" <?= ($leave['status'] === 'rejected') ? 'selected' : '' ?>>Rejected
                            </option>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($leave['status']) ?>"
                            readonly>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Applied At</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($leave['applied_at']) ?>"
                            readonly>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Update Leave Request</button>
                        <a href="leave_view.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
