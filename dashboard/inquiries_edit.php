<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if (!isset($_GET['id'])) {
    header('Location: inquiries_view.php');
    exit();
}

$inquiryId = (int)$_GET['id'];
$uid = (int)($_SESSION['uid'] ?? 0);

// Fetch inquiry data
$stmt = $conn->prepare("SELECT * FROM inquiries WHERE id = ?");
$stmt->bind_param('i', $inquiryId);
$stmt->execute();
$inquiry = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$inquiry) {
    header('Location: inquiries_view.php');
    exit();
}

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
?>
<?php include __DIR__ . '/admin_siderbar.php'; ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content container py-4">
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>View/Edit Inquiry</h3>
            <p class="text-muted">Modify inquiry details and track status</p>
        </div>
        <a href="inquiries_view.php" class="btn btn-outline-secondary">← Back to Inquiries</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" action="inquiries_update.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="inquiry_id" value="<?= htmlspecialchars($inquiry['id']) ?>">

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="<?= htmlspecialchars($inquiry['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control"
                        value="<?= htmlspecialchars($inquiry['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Company</label>
                    <input name="company" class="form-control" value="<?= htmlspecialchars($inquiry['company']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="5"
                        required><?= htmlspecialchars($inquiry['message']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="new" <?= ($inquiry['status'] === 'new') ? 'selected' : '' ?>>New</option>
                        <option value="replied" <?= ($inquiry['status'] === 'replied') ? 'selected' : '' ?>>Replied
                        </option>
                        <option value="closed" <?= ($inquiry['status'] === 'closed') ? 'selected' : '' ?>>Closed
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Created At</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($inquiry['created_at']) ?>"
                        readonly>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update Inquiry</button>
                    <a href="inquiries_view.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    </body>

    </html>