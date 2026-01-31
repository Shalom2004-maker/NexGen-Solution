<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

$error = '';
$success = '';

// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

if ($_POST) {
    // CSRF check and populate variables
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $error = 'Invalid request (CSRF).';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $pass = $_POST['pass'] ?? '';
        $role = isset($_POST['role']) ? (int)$_POST['role'] : 0;

        // Basic validation
        if ($name === '' || strlen($name) < 2) {
            $error = 'Enter a valid name.';
        } elseif (!$email) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($role <= 0) {
            $error = 'Select a valid role.';
        }

        // Check role exists
        if (empty($error)) {
            $rstmt = $conn->prepare("SELECT id FROM roles WHERE id = ?");
            $rstmt->bind_param('i', $role);
            $rstmt->execute();
            $rres = $rstmt->get_result();
            if ($rres->num_rows !== 1) {
                $error = 'Selected role does not exist.';
            }
            $rstmt->close();
        }

        // Check duplicate email
        if (empty($error)) {
            $cstmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $cstmt->bind_param('s', $email);
            $cstmt->execute();
            $cres = $cstmt->get_result();
            if ($cres->num_rows > 0) {
                $error = 'A user with that email already exists.';
            }
            $cstmt->close();
        }

        // Insert user
        if (empty($error)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users(full_name,email,password_hash,role_id) VALUES(?,?,?,?)");
            $stmt->bind_param("sssi", $name, $email, $hash, $role);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                if (function_exists('audit_log')) {
                    audit_log('create_user', "Created user {$email}", $_SESSION['uid'] ?? null);
                }
                // Redirect to view after successful creation
                header('Location: admin_user_view.php');
                exit();
            } else {
                $error = 'Failed to create user.';
                if (function_exists('audit_log')) {
                    audit_log('create_user_failed', "Failed to create user {$email}", $_SESSION['uid'] ?? null);
                }
            }
            $stmt->close();
        }
    }
}


$roles = $conn->query("SELECT * FROM roles");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - Admin Dashboard</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&family=Special+Gothic+Expanded+One&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    <!-- Local Bootstrap CSS Link -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="/js/bootstrap.bundle.min.js"></script>


    <!-- CSS -->
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Special Gothic Expanded One", sans-serif;
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
        width: 75%;
        padding-top: 1.7rem;
        padding-left: 18rem;
        padding-right: 2rem;
    }

    .form-container .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .form-container .form-control {
        border: 1px solid #d4d4d4;
        padding: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .form-container .form-control:focus {
        border-color: #337ccfe2;
        box-shadow: 0 0 0 0.2rem rgba(51, 124, 207, 0.25);
    }

    .alert {
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }

    .btn-submit {
        background-color: #337ccfe2;
        color: white;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }

    .btn-submit:hover {
        background-color: #2563a8;
        color: white;
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
            padding: 1.5rem;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-header h3 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
            width: 100%;
        }

        .form-container {
            padding: 1rem;
        }

        .page-header h3 {
            font-size: 1.25rem;
        }

        .form-container .form-label {
            font-size: 0.9rem;
        }

        .btn-submit {
            padding: 0.6rem 1.5rem;
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
                <h3>Create New User</h3>
                <p>Add a new system user account</p>
                <div class="position-relative" style="height: 50px;">
                    <a href="admin_user_view.php" class="btn btn-outline-primary btn-sm btn-lg position-absolute end-0">
                        <i class="bi bi-plus-circle"></i> View Users
                    </a>
                </div>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> User created successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="mb-3">
                        <label for="nameInput" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="nameInput" name="name" required
                            placeholder="Enter full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="emailInput" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="emailInput" name="email" required
                            placeholder="Enter email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="passwordInput" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="passwordInput" name="pass" required
                            placeholder="Enter password (min. 6 characters)">
                    </div>

                    <div class="mb-3">
                        <label for="roleSelect" class="form-label">Role *</label>
                        <select class="form-control" id="roleSelect" name="role" required>
                            <option value="">Select a role</option>
                            <?php while ($role = $roles->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($role['id']) ?>">
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit mb-3">Create User</button>
                    <a href="admin_user_view.php" class="btn btn-outline-secondary w-100">Cancel</a>
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