<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
// use shared admin layout
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
<?php include __DIR__ . '/admin_siderbar.php'; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap');

* {
    box-sizing: border-box;
    font-family: "Sora", sans-serif;
}

body {
    background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
    color: #1f2937;
}

.main-content {
    margin-left: 16rem;
    padding: 2rem 2.5rem 2rem 2rem;
    min-height: 100vh;
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
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.page-header h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.page-header p {
    color: #5b6777;
    margin: 0;
}

.card-panel {
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 16px;
}

.card-body {
    padding: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #475569;
}

.form-control,
select.form-control {
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

.btn-secondary,
.btn-outline-secondary {
    border-radius: 999px;
}

@media (max-width: 991.98px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
    }

    .dashboard-shell {
        padding: 1rem;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="main-content">
    <div class="dashboard-shell">
    <div class="page-header">
        <div>
            <h3>Edit User</h3>
            <p class="text-muted">Modify user details and permissions</p>
        </div>
        <a href="admin_user_view.php" class="btn btn-outline-secondary">Back to Users</a>
    </div>

    <div class="card-panel">
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
                    <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                        required>
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

</div>
</div>
</div>
</body>

</html>
