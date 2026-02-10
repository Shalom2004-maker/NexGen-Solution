<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "HR", "Admin"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";
include "../includes/sidebar_helper.php";

$uid = (int)($_SESSION['uid'] ?? 0);

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        if (function_exists('audit_log')) audit_log('csrf_fail', 'CSRF token mismatch on settings', $uid);
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($name === '' || $email === '') {
                $error = 'Name and email are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                $check->bind_param('si', $email, $uid);
                $check->execute();
                $exists = $check->get_result()->fetch_assoc();
                $check->close();

                if ($exists) {
                    $error = 'That email is already in use.';
                } else {
                    $u = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    $u->bind_param('ssi', $name, $email, $uid);
                    if ($u->execute()) {
                        $_SESSION['name'] = $name;
                        $success = 'Profile updated successfully.';
                        if (function_exists('audit_log')) audit_log('profile_update', "Profile updated for user {$uid}", $uid);
                    } else {
                        $error = 'Failed to update profile.';
                    }
                    $u->close();
                }
            }
        } elseif ($action === 'update_photo') {
            if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Please choose a valid image file.';
            } else {
                $file = $_FILES['profile_photo'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                if ($file['size'] > $maxSize) {
                    $error = 'Image must be less than 2MB.';
                } else {
                    $allowedExt = ['jpg', 'jpeg', 'png'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt, true)) {
                        $error = 'Only JPG and PNG images are allowed.';
                    } else {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
                        if ($finfo) finfo_close($finfo);
                        $allowedMime = ['image/jpeg', 'image/png'];
                        if ($mime && !in_array($mime, $allowedMime, true)) {
                            $error = 'Invalid image file type.';
                        } else {
                            $uploadDir = realpath(__DIR__ . '/../assets');
                            $avatarDir = $uploadDir ? $uploadDir . DIRECTORY_SEPARATOR . 'avatars' : '';

                            if ($avatarDir && !is_dir($avatarDir)) {
                                mkdir($avatarDir, 0755, true);
                            }

                            if (!$avatarDir || !is_dir($avatarDir)) {
                                $error = 'Upload directory is not available.';
                            } else {
                                $filename = 'user_' . $uid . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                                $destination = $avatarDir . DIRECTORY_SEPARATOR . $filename;

                                if (move_uploaded_file($file['tmp_name'], $destination)) {
                                    $relativePath = 'assets/avatars/' . $filename;
                                    $u = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                                    $u->bind_param('si', $relativePath, $uid);
                                    if ($u->execute()) {
                                        $success = 'Profile photo updated.';
                                        $_SESSION['profile_photo'] = $relativePath;
                                        if (function_exists('audit_log')) audit_log('profile_photo', "Profile photo updated for user {$uid}", $uid);
                                    } else {
                                        $error = 'Failed to save profile photo.';
                                    }
                                    $u->close();
                                } else {
                                    $error = 'Failed to upload image.';
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($action === 'remove_photo') {
            $u = $conn->prepare("UPDATE users SET profile_photo = NULL WHERE id = ?");
            $u->bind_param('i', $uid);
            if ($u->execute()) {
                $success = 'Profile photo removed.';
                $_SESSION['profile_photo'] = null;
                if (function_exists('audit_log')) audit_log('profile_photo_remove', "Profile photo removed for user {$uid}", $uid);
            } else {
                $error = 'Failed to remove profile photo.';
            }
            $u->close();
        } elseif ($action === 'change_password') {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if ($current === '' || $new === '' || $confirm === '') {
                $error = 'All password fields are required.';
            } elseif ($new !== $confirm) {
                $error = 'New password and confirmation do not match.';
            } elseif (strlen($new) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else {
                $q = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                $q->bind_param('i', $uid);
                $q->execute();
                $user = $q->get_result()->fetch_assoc();
                $q->close();

                $hash = $user['password_hash'] ?? '';
                if (!$hash || !password_verify($current, $hash)) {
                    $error = 'Current password is incorrect.';
                } else {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $u = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $u->bind_param('si', $newHash, $uid);
                    if ($u->execute()) {
                        $success = 'Password changed successfully.';
                        if (function_exists('audit_log')) audit_log('password_change', "Password changed for user {$uid}", $uid);
                    } else {
                        $error = 'Failed to change password.';
                    }
                    $u->close();
                }
            }
        }

        // rotate token to prevent resubmission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
}

// Fetch user profile
$stmt = $conn->prepare("SELECT full_name, email, profile_photo FROM users WHERE id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$photoUrl = resolve_avatar_url($user['profile_photo'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

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
        overflow-x: hidden;
        width: 75%;
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

    .settings-card {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        margin-bottom: 1.5rem;
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

    .avatar-wrap {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .avatar {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 2rem;
        overflow: hidden;
        border: 2px solid rgba(29, 78, 216, 0.25);
    }

    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        border: none;
        color: white;
        padding: 0.6rem 1.4rem;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.12s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
    }

    .btn-primary-custom:hover {
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(29, 78, 216, 0.3);
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
            width: 100%;
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
                        <h3>Settings</h3>
                        <p>Manage your account settings and security</p>
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
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="settings-card">
                    <h5 class="mb-3">Profile Photo</h5>
                    <div class="avatar-wrap">
                        <div class="avatar">
                            <?php if ($photoUrl): ?>
                            <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Profile photo">
                            <?php else: ?>
                            <?= htmlspecialchars(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
                            <?php endif; ?>
                        </div>

                        <form method="post" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update_photo">
                            <input class="form-control" type="file" name="profile_photo" accept="image/*" required>
                            <button type="submit" class="btn btn-primary-custom">Upload Photo</button>
                        </form>

                        <?php if ($photoUrl): ?>
                        <form method="post" class="ms-0">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="remove_photo">
                            <button type="submit" class="btn btn-outline-danger">Remove</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="settings-card">
                    <h5 class="mb-3">Account Information</h5>
                    <form method="post">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" required
                                    value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                        </div>
                    </form>
                </div>

                <div class="settings-card">
                    <h5 class="mb-3">Change Password</h5>
                    <form method="post">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary-custom">Update Password</button>
                        </div>
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
