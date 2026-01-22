<?php
include "../includes/auth.php";
allow("ProjectLeader");
include "../includes/db.php";
include "../includes/logger.php";
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

    $stmt = $conn->prepare("INSERT INTO tasks(project_id,assigned_to,created_by,title,description,deadline) 
                          VALUES(?,?,?,?,?,?)");
    $stmt->bind_param("iiisss", $_POST["project"], $_POST["user"], $_SESSION["uid"], $_POST["title"], $_POST["desc"], $_POST["deadline"]);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        audit_log('task_create', "Task created for project {$_POST['project']}", $_SESSION['uid'] ?? null);
    } else {
        audit_log('task_create_failed', "Failed to create task for project {$_POST['project']}", $_SESSION['uid'] ?? null);
        echo "Failed";
    }
    $stmt->close();
}
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
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Oswald", sans-serif;
        }

        html,
        body {
            background-color: #ececece8;
            min-height: 100vh;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            background-color: #f5f5f5d2;
            padding: 2rem;
            overflow-y: auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h3 {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: lightslategray;
            margin: 0;
        }

        .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
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
                padding: 1.5rem;
                padding-top: 3.5rem;
            }

            .form-container {
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
                padding-top: 3rem;
            }

            .page-header h3 {
                font-size: 1.25rem;
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
            <?php include "admin_siderbar.php"; ?>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h3>Create Task</h3>
                    <p>Assign new tasks to team members</p>
                </div>
            </div>

            <div class="form-container">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="mb-3">
                        <label for="project" class="form-label">Project ID *</label>
                        <input type="number" id="project" name="project" class="form-control"
                            placeholder="Enter project ID" required>
                    </div>

                    <div class="mb-3">
                        <label for="user" class="form-label">Employee User ID *</label>
                        <input type="number" id="user" name="user" class="form-control"
                            placeholder="User ID to assign to" required>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Task Title *</label>
                        <input type="text" id="title" name="title" class="form-control"
                            placeholder="Enter task title" required>
                    </div>

                    <div class="mb-3">
                        <label for="desc" class="form-label">Description</label>
                        <textarea id="desc" name="desc" class="form-control" rows="4"
                            placeholder="Task description"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-success w-100">Create Task</button>
                </form>
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