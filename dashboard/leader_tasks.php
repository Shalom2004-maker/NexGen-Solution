<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
include "../includes/logger.php";
// current user
$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;

// form feedback
$error = '';
$success = '';
// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
// process only on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        audit_log('csrf', 'Invalid CSRF token on leader_tasks', $_SESSION['uid'] ?? null);
        die('Invalid request');
    }

    $project_id = isset($_POST['project']) ? (int)$_POST['project'] : 0;
    $assigned_to = isset($_POST['user']) ? (int)$_POST['user'] : 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['desc'] ?? '');
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    if ($project_id <= 0 || $assigned_to <= 0 || $title === '') {
        $error = 'Please select a project, an employee, and enter a task title.';
    } else {
        // ensure project belongs to this leader
        $pstmt = $conn->prepare("SELECT id FROM projects WHERE id = ? AND leader_id = ? LIMIT 1");
        $pstmt->bind_param('ii', $project_id, $uid);
        $pstmt->execute();
        $pstmt->store_result();
        $valid_project = $pstmt->num_rows > 0;
        $pstmt->close();

        // ensure user is an employee
        $ustmt = $conn->prepare("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND r.role_name = 'Employee' LIMIT 1");
        $ustmt->bind_param('i', $assigned_to);
        $ustmt->execute();
        $ustmt->store_result();
        $valid_user = $ustmt->num_rows > 0;
        $ustmt->close();

        if (!$valid_project) {
            $error = 'Invalid project selection.';
        } elseif (!$valid_user) {
            $error = 'Invalid employee selection.';
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks(project_id,assigned_to,created_by,title,description,deadline) 
                                  VALUES(?,?,?,?,?,?)");
            $stmt->bind_param("iiisss", $project_id, $assigned_to, $uid, $title, $description, $deadline);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                audit_log('task_create', "Task created for project {$project_id}", $_SESSION['uid'] ?? null);
                $success = 'Task created successfully.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                $_POST = [];
            } else {
                audit_log('task_create_failed', "Failed to create task for project {$project_id}", $_SESSION['uid'] ?? null);
                $error = 'Failed to create task.';
            }
            $stmt->close();
        }
    }
}

// Projects for leader select (only projects owned by this leader)
$projects = null;
if ($uid > 0) {
    $pstmt = $conn->prepare("SELECT id, project_name FROM projects WHERE leader_id = ? ORDER BY project_name");
    $pstmt->bind_param('i', $uid);
    $pstmt->execute();
    $projects = $pstmt->get_result();
    $pstmt->close();
}

// Employees for assignee select
$users = $conn->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Employee' ORDER BY u.full_name");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Sora", sans-serif;
    }

    html,
    body {
        background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
        color: #1f2937;
        min-height: 100vh;
    }

    .main-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        background-color: transparent;
        padding-top: 2rem;
        padding-left: 18rem;
        padding-right: 2.5rem;
        padding-bottom: 2rem;
        width: 75%;
        overflow-y: auto;
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
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
        letter-spacing: -0.02em;
    }

    .page-header p {
        color: #5b6777;
        margin: 0;
    }

    .form-container {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        max-width: 640px;
    }

    .form-label {
        font-weight: 600;
        color: #475569;
    }

    .form-control {
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 12px;
        padding: 0.75rem;
    }

    .form-control:focus {
        border-color: #1d4ed8;
        box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.15);
    }

    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1040;
        background-color: #337ccfe2;
        color: white;
        border: none;
        padding: 0.6rem 0.8rem;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1.25rem;
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

    @media (max-width: 768px) {
        .main-wrapper {
            flex-direction: column;
        }

        .sidebar-toggle {
            display: block;
        }

        .main-content {
            padding: 1.25rem;
            padding-top: 3.5rem;
            width: auto;
        }

        .dashboard-shell {
            padding: 1rem;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .form-container {
            max-width: 100%;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
            width: 100%;
        }

        .page-header h3 {
            font-size: 1.35rem;
        }

        .form-container {
            padding: 1rem;
        }
    }
    </style>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button">
        <i class="bi bi-list"></i>
    </button>

    <div class="main-wrapper">
        <div id="sidebarContainer">
            <?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="page-header">
                    <div>
                        <h3>Create Tasks</h3>
                        <p>Assign new tasks to team members</p>
                    </div>
                    <a href="leader.php" class="btn btn-sm btn-outline-primary">Back</a>
                </div>

                <div class="form-container mx-auto">
                    <form method="post">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="project" class="form-label">Project ID *</label>
                            <select id="project" name="project" class="form-control" required>
                                <option value="">Select project</option>
                                <?php if ($projects): ?>
                                <?php while ($p = $projects->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>"
                                    <?= (isset($_POST['project']) && (int)$_POST['project'] === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['project_name']) ?> (ID: <?= (int)$p['id'] ?>)
                                </option>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="user" class="form-label">Employee User ID *</label>
                            <select id="user" name="user" class="form-control" required>
                                <option value="">Select employee</option>
                                <?php if ($users): ?>
                                <?php while ($u = $users->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>"
                                    <?= (isset($_POST['user']) && (int)$_POST['user'] === (int)$u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['full_name']) ?> (ID: <?= (int)$u['id'] ?>)
                                </option>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Task Title *</label>
                            <input type="text" id="title" name="title" class="form-control"
                                placeholder="Enter task title" required
                                value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="desc" class="form-label">Description</label>
                            <textarea id="desc" name="desc" class="form-control" rows="4"
                                placeholder="Task description"><?= htmlspecialchars($_POST['desc'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="deadline" class="form-label">Deadline</label>
                            <input type="date" id="deadline" name="deadline" class="form-control"
                                value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-success w-100">Create Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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