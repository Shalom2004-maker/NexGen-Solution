<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
include "../includes/header.php";
require_once __DIR__ . "/../includes/logger.php";

if (!isset($_GET['id'])) {
    header('Location: admin_user_view.php');
    exit();
}

$userId = (int)$_GET['id'];
$uid = (int)($_SESSION['uid'] ?? 0);

// Fetch user data
$stmt = $conn->prepare("SELECT id, full_name, email, role_id, status FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: admin_user_view.php');
    exit();
}

// Fetch roles
$roles = $conn->query("SELECT * FROM roles ORDER BY role_name");

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
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">
    <h3>Edit User</h3>
    <a href="admin_user_view.php" class="btn btn-secondary mb-3">← Back to Users</a>

    <div class="card">
        <div class="card-body">
            <form method="post" action="admin_user_update.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input name="name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">New Password (leave blank to keep current)</label>
                    <input name="pass" type="password" class="form-control" placeholder="Enter new password">
                    <small class="form-text text-muted">Minimum 6 characters</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-control" required>
                        <?php while ($r = $roles->fetch_assoc()) : ?>
                            <option value="<?= $r['id'] ?>" <?= ($user['role_id'] == $r['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['role_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?= ($user['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="disabled" <?= ($user['status'] === 'disabled') ? 'selected' : '' ?>>Disabled</option>
                    </select>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="admin_user_view.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>

</html>

