<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: solutions_view.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM solutions WHERE SolutionID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$solution = $result->fetch_assoc();
$stmt->close();

if (!$solution) {
    header("Location: solutions_view.php");
    exit;
}

$categoryOptions = [];
$catStmt = $conn->query("SELECT CategoryID, CategoryName FROM categories ORDER BY CategoryName");
while ($cat = $catStmt->fetch_assoc()) {
    $categoryOptions[$cat['CategoryID']] = $cat['CategoryName'];
}
$catStmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="/js/bootstrap.bundle.min.js"></script>
</head>

<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div><button class="sidebar-toggle" id="sidebarToggleBtn" type="button"><i class="bi bi-list"></i></button>
    <div class="main-wrapper">
        <div id="sidebarContainer"><?php include "../includes/sidebar_helper.php";
                                    render_sidebar(); ?></div>
        <div class="main-content">
            <div class="dashboard-shell">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Edit Solution</h3>
                    <a href="solutions_view.php" class="btn btn-secondary">Back to Solutions</a>
                </div>
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="solutions_update.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $solution['SolutionID'] ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($solution['Title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($solution['Description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="">Select category</option>
                                    <?php foreach ($categoryOptions as $catId => $catName): ?>
                                        <option value="<?= $catId ?>" <?= $solution['CategoryID'] == $catId ? 'selected' : '' ?>><?= htmlspecialchars($catName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="date_created" class="form-label">Date Created *</label>
                                <input type="date" id="date_created" name="date_created" class="form-control" value="<?= htmlspecialchars($solution['DateCreated']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="is_active" class="form-label">Visibility</label>
                                <select id="is_active" name="is_active" class="form-select">
                                    <option value="1" <?= $solution['IsActive'] ? 'selected' : '' ?>>Active</option>
                                    <option value="0" <?= !$solution['IsActive'] ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Solution</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
        });
    </script>
</body>

</html>