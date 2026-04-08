<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";
require_once __DIR__ . "/../includes/inquiry_helpers.php";

ensure_inquiry_reply_support($conn);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $posted_token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $error = 'Invalid request.';
        audit_log('csrf', 'Invalid CSRF token on inquiries dashboard', $uid);
    } elseif ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '' || $category === '' || $subject === '') {
            $error = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("INSERT INTO inquiries (name, email, company, message, status, category) VALUES (?, ?, ?, ?, 'new', ?)");
            if ($stmt) {
                $stmt->bind_param('sssss', $name, $email, $company, $message, $category);
                if ($stmt->execute()) {
                    $success = 'Inquiry submitted successfully.';
                    audit_log('inquiry_create', "New inquiry created by $name - Subject: $subject", $uid);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                    $_POST = [];
                } else {
                    $error = 'Failed to submit inquiry.';
                    audit_log('inquiry_create_failed', 'Failed inquiry submission', $uid);
                }
                $stmt->close();
            } else {
                $error = 'Unable to prepare the inquiry request.';
            }
        }
    } elseif ($action === 'reply') {
        $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
        $replyMessage = trim($_POST['reply_message'] ?? '');

        if ($inquiryId <= 0) {
            $error = 'Invalid inquiry selected.';
        } elseif ($replyMessage === '') {
            $error = 'Please write a reply before sending.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, status FROM inquiries WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $inquiryId);
                $stmt->execute();
                $inquiry = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $inquiry = null;
            }

            if (!$inquiry) {
                $error = 'Inquiry not found.';
            } else {
                $replyAuthor = trim((string)($_SESSION['name'] ?? $_SESSION['role'] ?? 'NexGen Solution Team'));
                $emailStatus = true;

                if (filter_var($inquiry['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $emailStatus = send_inquiry_reply_email(
                        (string)$inquiry['email'],
                        (string)($inquiry['name'] ?? ''),
                        $replyMessage,
                        $replyAuthor
                    );
                }

                $nextStatus = ($inquiry['status'] ?? '') === 'closed' ? 'closed' : 'replied';
                $updateStmt = $conn->prepare("
                    UPDATE inquiries
                    SET status = ?, reply_message = ?, replied_at = NOW(), replied_by = ?
                    WHERE id = ?
                ");

                if ($updateStmt) {
                    $updateStmt->bind_param('ssii', $nextStatus, $replyMessage, $uid, $inquiryId);
                    if ($updateStmt->execute()) {
                        audit_log('inquiry_reply', "Reply saved for inquiry ID: {$inquiryId}", $uid);

                        if ($emailStatus === true) {
                            $success = 'Reply saved, inquiry marked as replied, and email sent.';
                        } else {
                            $success = 'Reply saved and inquiry marked as replied. Email delivery could not be confirmed.';
                            audit_log('inquiry_reply_mail_failed', "Inquiry ID {$inquiryId} reply mail issue: {$emailStatus}", $uid);
                        }

                        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                        $_POST = [];
                    } else {
                        $error = 'Failed to save the reply.';
                    }
                    $updateStmt->close();
                } else {
                    $error = 'Unable to prepare the reply action.';
                }
            }
        }
    }
}

$open_count = 0;
$replied_count = 0;
$resolved_count = 0;

$stats_result = $conn->query("SELECT status, COUNT(*) AS count FROM inquiries GROUP BY status");
if ($stats_result) {
    while ($row = $stats_result->fetch_assoc()) {
        if ($row['status'] === 'new') {
            $open_count = (int)$row['count'];
        } elseif ($row['status'] === 'replied') {
            $replied_count = (int)$row['count'];
        } elseif ($row['status'] === 'closed') {
            $resolved_count = (int)$row['count'];
        }
    }
}

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? 'all');

$where = '1=1';
$params = [];
$types = '';

if ($search !== '') {
    $like = "%{$search}%";
    $where .= " AND (i.name LIKE ? OR i.email LIKE ? OR i.company LIKE ? OR i.message LIKE ? OR i.reply_message LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
    $types .= 'sssss';
}

if ($category !== '' && $category !== 'all') {
    $where .= " AND i.category = ?";
    $params[] = $category;
    $types .= 's';
}

if ($status_filter !== 'all') {
    $where .= " AND i.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) AS total FROM inquiries i WHERE {$where}";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$count_stmt->close();

$sql = "
    SELECT
        i.id,
        i.name,
        i.email,
        i.company,
        i.category,
        i.message,
        i.status,
        i.created_at,
        i.reply_message,
        i.replied_at,
        COALESCE(u.full_name, '') AS replied_by_name
    FROM inquiries i
    LEFT JOIN users u ON u.id = i.replied_by
    WHERE {$where}
    ORDER BY COALESCE(i.replied_at, i.created_at) DESC, i.created_at DESC
    LIMIT ? OFFSET ?
";
$inquiry_params = array_merge($params, [$limit, $offset]);
$inquiry_types = $types . 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($inquiry_types, ...$inquiry_params);
$stmt->execute();
$inquiries_result = $stmt->get_result();
$inquiries = [];
if ($inquiries_result instanceof mysqli_result) {
    while ($inquiryRow = $inquiries_result->fetch_assoc()) {
        $inquiries[] = $inquiryRow;
    }
    $inquiries_result->free();
}
$stmt->close();

$pages = max(1, (int)ceil($total / $limit));
?>

<?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<button class="sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleSidebar()" title="Toggle Sidebar">
    <i class="bi bi-list"></i>
</button>
<div class="main-content">
    <div class="dashboard-shell">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2>Inquiries</h2>
                <p>Submit, track, and reply to incoming inquiries</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inquiryModal">
                    <i class="bi bi-plus"></i> New Inquiry
                </button>
            </div>
        </div>

        <?php if ($error !== ''): ?>
        <div class="alert alert-danger mb-4" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
        <div class="alert alert-success mb-4" role="alert">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <!-- Metric Cards -->
        <div class="row mb-4 g-3">
            <div class="col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-start">
                        <div class="stat-icon mx-1">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div class="mx-4">
                            <div class="stat-label">Open Inquiries</div>
                            <div class="stat-number fw-bold fs-4"><?= $open_count ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-start">
                        <div class="stat-icon mx-1">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="mx-4">
                            <div class="stat-label">In Progress</div>
                            <div class="stat-number fw-bold fs-4"><?= $replied_count ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-start">
                        <div class="stat-icon mx-1">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="mx-4">
                            <div class="stat-label">Resolved</div>
                            <div class="stat-number fw-bold fs-4"><?= $resolved_count ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="mb-0">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-lg-5 d-flex align-items-center justify-content-center">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                    class="form-control" placeholder="Search inquiries...">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <select name="status" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All statuses</option>
                                <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>New</option>
                                <option value="replied" <?= $status_filter === 'replied' ? 'selected' : '' ?>>Replied</option>
                                <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4 d-flex gap-2 justify-content-start align-items-center">
                            <button class="btn btn-outline-secondary" type="submit">Search</button>
                            <?php if ($search || ($category !== '' && $category !== 'all') || $status_filter !== 'all'): ?>
                            <a href="inquiries_dashboard.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Category Filter Buttons -->
                    <div class="d-flex flex-wrap gap-2">
                        <a href="inquiries_dashboard.php"
                            class="filter-btn text-decoration-none <?= empty($category) || $category === 'all' ? 'active' : '' ?>">All</a>
                        <a href="?category=HR&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"
                            class="filter-btn text-decoration-none <?= $category === 'HR' ? 'active' : '' ?>">HR</a>
                        <a href="?category=IT%20Support&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"
                            class="filter-btn text-decoration-none <?= $category === 'IT Support' ? 'active' : '' ?>">IT
                            Support</a>
                        <a href="?category=Payroll&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"
                            class="filter-btn text-decoration-none <?= $category === 'Payroll' ? 'active' : '' ?>">Payroll</a>
                        <a href="?category=General&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"
                            class="filter-btn text-decoration-none <?= $category === 'General' ? 'active' : '' ?>">General</a>
                        <a href="?category=Complaint&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"
                            class="filter-btn text-decoration-none <?= $category === 'Complaint' ? 'active' : '' ?>">Complaint</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inquiries Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contact</th>
                        <th class="d-none d-md-table-cell">Category</th>
                        <th class="d-none d-md-table-cell">Status</th>
                        <th class="d-none d-lg-table-cell">Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-light">
                    <?php if (!empty($inquiries)): ?>
                    <?php foreach ($inquiries as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($row['email']) ?></div>
                            <?php if (!empty($row['company'])): ?>
                            <div class="small text-muted"><?= htmlspecialchars($row['company']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <span
                                class="badge bg-info opacity-75 text-dark"><?= htmlspecialchars($row['category'] ?: 'General') ?></span>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                                <?= ucfirst(htmlspecialchars($row['status'])) ?>
                            </span>
                            <?php if (!empty($row['replied_at'])): ?>
                            <div class="small text-muted mt-1">
                                Replied <?= date('M d, Y g:i A', strtotime($row['replied_at'])) ?>
                            </div>
                            <?php if (!empty($row['replied_by_name'])): ?>
                            <div class="small text-muted">by <?= htmlspecialchars($row['replied_by_name']) ?></div>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="small text-muted mt-1">Awaiting response</div>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <div class="small fw-semibold">Received <?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                            <div class="small text-muted mt-1"><?= htmlspecialchars(inquiry_preview_text($row['message'], 95)) ?></div>
                            <?php if (!empty($row['reply_message'])): ?>
                            <div class="small text-success mt-2">Reply: <?= htmlspecialchars(inquiry_preview_text($row['reply_message'], 95)) ?></div>
                            <?php else: ?>
                            <div class="small text-muted mt-2">No saved reply yet</div>
                            <?php endif; ?>
                        </td>
                        <td class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                            <button type="button" class="btn btn-outline-success"
                                data-bs-toggle="modal"
                                data-bs-target="#replyModal<?= (int)$row['id'] ?>">
                                <i class="bi bi-reply"></i>
                            </button>
                            <a href="inquiries_edit.php?id=<?= urlencode($row['id']) ?>"
                                class="btn btn-outline-primary">
                                <i class="bi bi-pen"></i>
                            </a>
                            <form method="post" action="inquiries_delete.php" class="d-inline"
                                onsubmit="return confirm('Delete this inquiry?')">
                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="inquiry_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state p-3 text-align-center">
                                <center>
                                    <div class=" empty-icon">
                                        <i class="bi bi-chat-dots"></i>
                                    </div>
                                    <p class="mb-2">No inquiries found.</p>
                                </center>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link"
                        href="?page=1&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status_filter) ?>">First</a>
                </li>
                <li class="page-item">
                    <a class="page-link"
                        href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status_filter) ?>">Previous</a>
                </li>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === 1 || $p === $pages || abs($p - $page) <= 1): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link"
                        href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status_filter) ?>"><?= $p ?></a>
                </li>
                <?php elseif ($p === 2 || $p === $pages - 1): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
                <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                <li class="page-item">
                    <a class="page-link"
                        href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status_filter) ?>">Next</a>
                </li>
                <li class="page-item">
                    <a class="page-link"
                        href="?page=<?= $pages ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status_filter) ?>">Last</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($inquiries as $row): ?>
<div class="modal fade" id="replyModal<?= (int)$row['id'] ?>" tabindex="-1"
    aria-labelledby="replyModalLabel<?= (int)$row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replyModalLabel<?= (int)$row['id'] ?>">
                    <?= !empty($row['reply_message']) ? 'Update Inquiry Reply' : 'Reply To Inquiry' ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="inquiry_id" value="<?= (int)$row['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sender</label>
                            <input type="text" class="form-control"
                                value="<?= htmlspecialchars(($row['name'] ?? '') . ' <' . ($row['email'] ?? '') . '>') ?>"
                                readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Status</label>
                            <input type="text" class="form-control"
                                value="<?= htmlspecialchars(ucfirst((string)($row['status'] ?? 'new'))) ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Original Message</label>
                            <textarea class="form-control" rows="5" readonly><?= htmlspecialchars($row['message'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reply Message *</label>
                            <textarea name="reply_message" class="form-control" rows="6"
                                placeholder="Write the response that should be saved and emailed to the enquirer."
                                required><?= htmlspecialchars($row['reply_message'] ?? '') ?></textarea>
                            <div class="form-text">
                                Saving this form records the reply, updates the inquiry as replied, and attempts to
                                send the message to the inquiry email address.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer mt-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- New Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inquiryModalLabel">Submit New Inquiry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="modal-form-group">
                        <label class="modal-label">Category *</label>
                        <select name="category" class="modal-select form-control" required>
                            <option value="">Select category</option>
                            <option value="HR" <?= ($_POST['category'] ?? '') === 'HR' ? 'selected' : '' ?>>HR</option>
                            <option value="IT Support"
                                <?= ($_POST['category'] ?? '') === 'IT Support' ? 'selected' : '' ?>>IT Support</option>
                            <option value="Payroll" <?= ($_POST['category'] ?? '') === 'Payroll' ? 'selected' : '' ?>>
                                Payroll</option>
                            <option value="General" <?= ($_POST['category'] ?? '') === 'General' ? 'selected' : '' ?>>
                                General</option>
                            <option value="Complaint"
                                <?= ($_POST['category'] ?? '') === 'Complaint' ? 'selected' : '' ?>>Complaint</option>
                        </select>
                    </div>

                    <div class="modal-form-group">
                        <label class="modal-label">Name *</label>
                        <input type="text" name="name" class="modal-input form-control" placeholder="Your name"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="modal-form-group">
                        <label class="modal-label">Email *</label>
                        <input type="email" name="email" class="modal-input form-control"
                            placeholder="your.email@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required>
                    </div>

                    <div class="modal-form-group">
                        <label class="modal-label">Company</label>
                        <input type="text" name="company" class="modal-input form-control"
                            placeholder="Company name (optional)"
                            value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                    </div>

                    <div class="modal-form-group">
                        <label class="modal-label">Subject *</label>
                        <input type="text" name="subject" class="modal-input form-control"
                            placeholder="Brief subject of your inquiry"
                            value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
                    </div>

                    <div class="modal-form-group">
                        <label class="modal-label">Message *</label>
                        <textarea name="message" class="modal-textarea form-control" rows="5"
                            placeholder="Describe your inquiry in detail..."
                            required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn modal-btn modal-btn-cancel"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn modal-btn modal-btn-submit">Submit Inquiry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('nexgenSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
    if (overlay) {
        overlay.classList.toggle('show');
    }
}

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

<script src="../js/bootstrap.bundle.min.js"></script>

</body>

</html>
