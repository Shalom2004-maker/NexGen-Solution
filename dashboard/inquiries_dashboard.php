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

<?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<button class="sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleSidebar()" title="Toggle Sidebar">
    <i class="bi bi-list"></i>
</button>

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
    transition: margin .2s ease;
    background: transparent;
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

.sidebar-toggle-btn {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1050;
    background-color: #1d4ed8;
    color: white;
    border: none;
    padding: 0.6rem 0.8rem;
    border-radius: 12px;
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

.page-header h2 {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.page-header p {
    color: #5b6777;
    font-size: 0.95rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.stat-card {
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 16px;
    padding: 1.6rem;
    transition: all 0.2s ease;
}

.stat-card:hover {
    border-color: rgba(37, 99, 235, 0.4);
    box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
    transform: translateY(-2px);
}

.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
    font-size: 1.3rem;
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0.4rem 0 0;
}

.stat-label {
    color: #64748b;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-weight: 600;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.4);
    border-radius: 999px;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    font-weight: 600;
}

.filter-btn:hover {
    border-color: rgba(37, 99, 235, 0.6);
    color: #1d4ed8;
}

.filter-btn.active {
    background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
    color: white;
    border-color: transparent;
}

.btn-primary {
    background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
    border: none;
    border-radius: 999px;
    font-weight: 600;
    padding: 0.6rem 1.2rem;
    box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
}

.btn-primary:hover {
    box-shadow: 0 12px 24px rgba(29, 78, 216, 0.3);
}

.table-responsive {
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.table th {
    background-color: #f8fafc;
    border-top: none;
    font-weight: 600;
    color: #334155;
}

.table td {
    vertical-align: middle;
}

.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
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
    color: #64748b;
}

.empty-icon {
    font-size: 3rem;
    color: #cbd5f5;
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
        font-size: 1.2rem;
    }
}

/* Modal Styling */
.modal-content {
    border-radius: 18px;
    border: 1px solid rgba(148, 163, 184, 0.4);
    box-shadow: 0 30px 50px rgba(15, 23, 42, 0.2);
    background: #ffffff;
}

.modal-header {
    border-bottom: 1px solid rgba(148, 163, 184, 0.3);
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(29, 78, 216, 0.1), rgba(14, 116, 144, 0.08));
}

.modal-title {
    color: #0f172a;
    font-weight: 700;
    font-size: 1.25rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-label {
    color: #475569;
    font-weight: 600;
    margin-bottom: 0.65rem;
    display: block;
    font-size: 0.9rem;
}

.modal-input,
.modal-select,
.modal-textarea {
    background-color: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.45);
    color: #0f172a;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.modal-input::placeholder,
.modal-select::placeholder,
.modal-textarea::placeholder {
    color: #94a3b8;
}

.modal-input:focus,
.modal-select:focus,
.modal-textarea:focus {
    border-color: #1d4ed8;
    color: #0f172a;
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
    outline: none;
}

.modal-form-group {
    margin-bottom: 1.25rem;
}

.modal-form-group:last-child {
    margin-bottom: 0;
}

.modal-footer {
    border-top: 1px solid rgba(148, 163, 184, 0.3);
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
    border: 1px solid rgba(148, 163, 184, 0.6);
    color: #475569;
}

.modal-btn-cancel:hover {
    background-color: #e2e8f0;
    border-color: rgba(148, 163, 184, 0.6);
    color: #0f172a;
}

.modal-btn-submit {
    background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
    border: none;
    color: white;
}

.modal-btn-submit:hover {
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.25);
}
</style>

<div class="main-content">
    <div class="dashboard-shell">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2>Inquiries</h2>
                <p>Submit and track your inquiries</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inquiryModal">
                    <i class="bi bi-plus"></i> New Inquiry
                </button>
            </div>
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
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                    class="form-control" placeholder="Search inquiries...">
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
                <tbody class="bg-light">
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
                        <td class="d-flex gap-2 mb-0 mt-2" style="height: 9vh">
                            <a href="inquiries_edit.php?id=<?= urlencode($row['id']) ?>"
                                class="btn btn-outline-primary">
                                <i class="bi bi-pen"></i>
                            </a>
                            <a href="inquiries_delete.php?id=<?= urlencode($row['id']) ?>"
                                class="btn btn-outline-danger">
                                <i class="bi bi-trash"></i>
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

<script src="../js/bootstrap.bundle.min.js"></script>

</body>

</html>