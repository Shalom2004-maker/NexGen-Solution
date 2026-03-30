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
    header("Location: support_view.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM support WHERE ID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$support = $result->fetch_assoc();
$stmt->close();

if (!$support) {
    header("Location: support_view.php");
    exit;
}

$solutionOptions = [];
$solStmt = $conn->query("SELECT SolutionID, Title FROM solutions ORDER BY Title");
while ($sol = $solStmt->fetch_assoc()) {
    $solutionOptions[$sol['SolutionID']] = $sol['Title'];
}
$solStmt->close();

$serviceOptions = [];
$serStmt = $conn->query("SELECT ServiceID, ServiceName FROM services ORDER BY ServiceName");
while ($ser = $serStmt->fetch_assoc()) {
    $serviceOptions[$ser['ServiceID']] = $ser['ServiceName'];
}
$serStmt->close();

$statusOptions = ['Open', 'In Progress', 'Resolved', 'Closed'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Support Record</title>
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
                    <h3>Edit Support Record</h3>
                    <a href="support_view.php" class="btn btn-secondary">Back to Support</a>
                </div>
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="support_update.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $support['ID'] ?>">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject *</label>
                                <input type="text" id="subject" name="subject" class="form-control" value="<?= htmlspecialchars($support['Subject']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <?php foreach ($statusOptions as $status): ?>
                                        <option value="<?= $status ?>" <?= $support['Status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <input type="number" id="priority" name="priority" class="form-control" min="1" max="5" value="<?= htmlspecialchars($support['Priority']) ?>">
                            </div>
                            <div class="mb-3">
                                <label for="solution_id" class="form-label">Solution</label>
                                <select id="solution_id" name="solution_id" class="form-select">
                                    <option value="">Select solution</option>
                                    <?php foreach ($solutionOptions as $solId => $solTitle): ?>
                                        <option value="<?= $solId ?>" <?= $support['SolutionID'] == $solId ? 'selected' : '' ?>><?= htmlspecialchars($solTitle) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="service_id" class="form-label">Service</label>
                                <select id="service_id" name="service_id" class="form-select">
                                    <option value="">Select service</option>
                                    <?php foreach ($serviceOptions as $serId => $serName): ?>
                                        <option value="<?= $serId ?>" <?= $support['ServiceID'] == $serId ? 'selected' : '' ?>><?= htmlspecialchars($serName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Support Record</button>
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