<?php
include "../includes/auth.php";
allow("HR");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);

// Handle inquiry submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $error = 'Invalid request';
        audit_log('csrf', 'Invalid CSRF token on inquiry create', $uid);
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message) || empty($category) || empty($subject)) {
            $error = 'Please fill in all required fields';
        } else {
            $stmt = $conn->prepare("INSERT INTO inquiries (name, email, company, message, status, category) VALUES (?, ?, ?, ?, 'new', ?)");
            if ($stmt) {
                $stmt->bind_param('sssss', $name, $email, $company, $message, $category);
                if ($stmt->execute()) {
                    $success = 'Inquiry submitted successfully!';
                    audit_log('inquiry_create', "New inquiry created by $name - Subject: $subject", $uid);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                    $_POST = [];
                } else {
                    $error = 'Failed to submit inquiry';
                    audit_log('inquiry_create_failed', "Failed inquiry submission", $uid);
                }
                $stmt->close();
            }
        }
    }
}

// Fetch inquiry statistics
$open_count = 0;
$replied_count = 0;
$resolved_count = 0;

// Count inquiries by status
$stats_query = "SELECT status, COUNT(*) as count FROM inquiries GROUP BY status";
$stats_result = $conn->query($stats_query);
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

// Build inquiry list query
$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? 'all');

$where = '1=1';
$params = [];
$types = '';

if ($search !== '') {
    $like = "%{$search}%";
    $where .= " AND (name LIKE ? OR email LIKE ? OR company LIKE ? OR message LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}

if ($category !== '' && $category !== 'all') {
    $where .= " AND category = ?";
    $params[] = $category;
    $types .= 's';
}

if ($status_filter !== 'all') {
    $where .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total
$count_sql = "SELECT COUNT(*) as total FROM inquiries WHERE {$where}";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Fetch inquiries
$sql = "SELECT id, name, email, company, category, message, status, created_at FROM inquiries WHERE {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?";
$inquiry_params = array_merge($params, [$limit, $offset]);
$inquiry_types = $types . 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($inquiry_types, ...$inquiry_params);
$stmt->execute();
$inquiries_result = $stmt->get_result();
$stmt->close();

$pages = max(1, ceil($total / $limit));
?>

<?php include __DIR__ . '/admin_siderbar.php'; ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<button class="sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleSidebar()" title="Toggle Sidebar">
    <i class="bi bi-list"></i>
</button>

<style>
.main-content {
    margin-left: 16rem;
    padding: 2rem;
    transition: margin .2s ease;
}

@media (max-width: 991.98px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
    }
}

.sidebar-toggle-btn {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1050;
    background-color: #337ccfe2;
    color: white;
    border: none;
    padding: 0.6rem 0.8rem;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .sidebar-toggle-btn {
        display: block;
    }

    .main-content {
        margin-left: 0;
        padding: 1rem;
    }
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

.stat-card {
    background: linear-gradient(135deg, rgba(51, 124, 207, 0.1) 0%, rgba(51, 124, 207, 0.05) 100%);
    border: 1px solid rgba(51, 124, 207, 0.2);
    border-radius: 12px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: linear-gradient(135deg, rgba(51, 124, 207, 0.15) 0%, rgba(51, 124, 207, 0.1) 100%);
    box-shadow: 0 4px 12px rgba(51, 124, 207, 0.15);
}

.stat-icon {
    font-size: 2.5rem;
    color: #337ccfe2;
    opacity: 0.8;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #337ccfe2;
    margin: 0.5rem 0;
}

.stat-label {
    color: #6c757d;
    font-size: 0.95rem;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    background: transparent;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.filter-btn:hover {
    border-color: #337ccfe2;
    color: #337ccfe2;
}

.filter-btn.active {
    background-color: #337ccfe2;
    color: white;
    border-color: #337ccfe2;
}

.table-responsive {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
}

.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-new {
    background-color: #e7f3ff;
    color: #0056b3;
}

.status-replied {
    background-color: #fff3cd;
    color: #856404;
}

.status-closed {
    background-color: #d4edda;
    color: #155724;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-icon {
    font-size: 3rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

@media (max-width: 576px) {
    .stat-card {
        padding: 1.5rem;
    }

    .filter-btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }

    .stat-number {
        font-size: 2rem;
    }

    .stat-icon {
        font-size: 2rem;
    }
}

/* Modal Styling */
.modal-content {
    background-color: #1a2332;
    border: 1px solid rgba(51, 124, 207, 0.3);
    border-radius: 12px;
}

.modal-header {
    border-bottom: 1px solid rgba(51, 124, 207, 0.2);
    padding: 1.5rem;
}

.modal-title {
    color: white;
    font-weight: 600;
    font-size: 1.25rem;
}

.btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.7;
}

.btn-close:hover {
    opacity: 1;
}

.modal-body {
    padding: 1.5rem;
}

.modal-label {
    color: #e0e0e0;
    font-weight: 500;
    margin-bottom: 0.75rem;
    display: block;
    font-size: 0.95rem;
}

.modal-input,
.modal-select,
.modal-textarea {
    background-color: #0f1419;
    border: 1px solid rgba(51, 124, 207, 0.4);
    color: #e0e0e0;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.modal-input::placeholder,
.modal-select::placeholder,
.modal-textarea::placeholder {
    color: #8a8a8a;
}

.modal-input:focus,
.modal-select:focus,
.modal-textarea:focus {
    background-color: #0f1419;
    border-color: #337ccfe2;
    color: #e0e0e0;
    box-shadow: 0 0 0 3px rgba(51, 124, 207, 0.1);
    outline: none;
}

.modal-form-group {
    margin-bottom: 1.25rem;
}

.modal-form-group:last-child {
    margin-bottom: 0;
}

.modal-footer {
    border-top: 1px solid rgba(51, 124, 207, 0.2);
    padding: 1.5rem;
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

.modal-btn {
    padding: 0.65rem 1.5rem;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.modal-btn-cancel {
    background-color: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #e0e0e0;
}

.modal-btn-cancel:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
}

.modal-btn-submit {
    background: linear-gradient(135deg, #337ccfe2 0%, #1e5fa8 100%);
    border: none;
    color: white;
}

.modal-btn-submit:hover {
    background: linear-gradient(135deg, #1e5fa8 0%, #0f3a6e 100%);
    box-shadow: 0 4px 12px rgba(51, 124, 207, 0.3);
}
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-chat-dots"></i> Inquiries</h2>
            <p class="text-muted">Submit and track your inquiries</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inquiryModal">
            <i class="bi bi-plus"></i> New Inquiry
        </button>
    </div>

    <!-- Metric Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Open Inquiries</div>
                        <div class="stat-number"><?= $open_count ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">In Progress</div>
                        <div class="stat-number"><?= $replied_count ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Resolved</div>
                        <div class="stat-number"><?= $resolved_count ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
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
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control"
                                placeholder="Search inquiries...">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex gap-2 align-items-center">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if ($search): ?>
                        <a href="inquiries_dashboard.php" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Category Filter Buttons -->
                <div class="d-flex flex-wrap gap-2">
                    <a href="inquiries_dashboard.php"
                        class="filter-btn <?= empty($category) || $category === 'all' ? 'active' : '' ?>">All</a>
                    <a href="?category=HR&q=<?= urlencode($search) ?>"
                        class="filter-btn <?= $category === 'HR' ? 'active' : '' ?>">HR</a>
                    <a href="?category=IT%20Support&q=<?= urlencode($search) ?>"
                        class="filter-btn <?= $category === 'IT Support' ? 'active' : '' ?>">IT Support</a>
                    <a href="?category=Payroll&q=<?= urlencode($search) ?>"
                        class="filter-btn <?= $category === 'Payroll' ? 'active' : '' ?>">Payroll</a>
                    <a href="?category=General&q=<?= urlencode($search) ?>"
                        class="filter-btn <?= $category === 'General' ? 'active' : '' ?>">General</a>
                    <a href="?category=Complaint&q=<?= urlencode($search) ?>"
                        class="filter-btn <?= $category === 'Complaint' ? 'active' : '' ?>">Complaint</a>
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
                    <th>Name</th>
                    <th>Email</th>
                    <th class="d-none d-md-table-cell">Category</th>
                    <th class="d-none d-md-table-cell">Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($inquiries_result->num_rows > 0): ?>
                <?php while ($row = $inquiries_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td class="d-none d-md-table-cell">
                        <span class="badge bg-light text-dark"><?= htmlspecialchars($row['category']) ?></span>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                            <?= ucfirst(htmlspecialchars($row['status'])) ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="inquiries_edit.php?id=<?= urlencode($row['id']) ?>"
                            class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-square"></i> View
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <p class="mb-0">No inquiries found.</p>
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
                    href="?page=1&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">First</a>
            </li>
            <li class="page-item">
                <a class="page-link"
                    href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">Previous</a>
            </li>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <?php if ($p === 1 || $p === $pages || abs($p - $page) <= 1): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link"
                    href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>"><?= $p ?></a>
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
                    href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">Next</a>
            </li>
            <li class="page-item">
                <a class="page-link"
                    href="?page=<?= $pages ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">Last</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- New Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inquiryModalLabel">Submit New Inquiry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if (isset($success) && !empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
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

</body>

</html>