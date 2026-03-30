<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "HR", "Admin"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";
include "../includes/sidebar_helper.php";

$uid = (int)($_SESSION['uid'] ?? 0);
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isAdmin = ($role === 'admin');

$homepageDefaults = [
    'home_hero_eyebrow' => 'Intelligent Workforce Platform',
    'home_hero_title' => 'Manage your team with precision',
    'home_hero_summary' => 'Browse a live catalog of services, solutions, and support coverage powered by your latest published data.',
    'home_services_intro' => 'Browse the current service catalog, grouped by tier and category so visitors can quickly understand what you offer.',
    'home_solutions_intro' => 'Highlighted active solutions are now pulled directly from your latest published entries.',
    'home_support_intro' => 'A live operational summary generated from support activity without exposing private ticket details.',
];

$homepageKeys = array_keys($homepageDefaults);

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$error = '';
$success = '';

if ($isAdmin) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

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
            if (!isset($_FILES['profile_photo'])) {
                $error = 'Please choose an image file.';
            } else {
                $file = $_FILES['profile_photo'];
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
                    if ($uploadError === UPLOAD_ERR_NO_FILE) {
                        $error = 'Please choose an image file.';
                    } elseif (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                        $error = 'Uploaded image is too large.';
                    } else {
                        $error = 'Upload failed. Please try again.';
                    }
                } elseif (!is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
                    $error = 'Invalid upload request.';
                } else {
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
                $q = $conn->prepare("SELECT password_hash, password FROM users WHERE id = ?");
                $q->bind_param('i', $uid);
                $q->execute();
                $user = $q->get_result()->fetch_assoc();
                $q->close();

                $hash = trim((string)($user['password_hash'] ?? ''));
                $legacyPassword = (string)($user['password'] ?? '');
                $isCurrentPasswordValid = false;

                if ($hash !== '') {
                    $passwordInfo = password_get_info($hash);
                    if (!empty($passwordInfo['algo'])) {
                        $isCurrentPasswordValid = password_verify($current, $hash);
                    }
                }

                if (!$isCurrentPasswordValid && $legacyPassword !== '') {
                    $isCurrentPasswordValid = hash_equals($legacyPassword, $current);
                }

                if (!$isCurrentPasswordValid) {
                    $error = 'Current password is incorrect.';
                } else {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $u = $conn->prepare("UPDATE users SET password_hash = ?, password = '' WHERE id = ?");
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
        } elseif ($action === 'update_homepage' && $isAdmin) {
            $fieldValues = [];
            foreach ($homepageKeys as $key) {
                $inputValue = trim((string)($_POST[$key] ?? ''));
                if ($inputValue === '') {
                    $inputValue = $homepageDefaults[$key] ?? '';
                }
                if (strlen($inputValue) > 240) {
                    $error = 'Homepage content fields must be 240 characters or less.';
                    break;
                }
                $fieldValues[$key] = $inputValue;
            }

            if ($error === '') {
                $stmt = $conn->prepare(
                    "INSERT INTO site_settings (setting_key, setting_value)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                );

                if ($stmt) {
                    foreach ($fieldValues as $key => $value) {
                        $stmt->bind_param('ss', $key, $value);
                        if (!$stmt->execute()) {
                            $error = 'Failed to update homepage content.';
                            break;
                        }
                    }
                    $stmt->close();
                } else {
                    $error = 'Failed to prepare homepage update.';
                }

                if ($error === '') {
                    $success = 'Homepage content updated successfully.';
                    if (function_exists('audit_log')) {
                        audit_log('homepage_update', 'Homepage content updated', $uid);
                    }
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

$homepageSettings = $homepageDefaults;
if ($isAdmin) {
    $placeholders = implode(",", array_fill(0, count($homepageKeys), "?"));
    $types = str_repeat("s", count($homepageKeys));
    $settingsQuery = $conn->prepare(
        "SELECT setting_key, setting_value
         FROM site_settings
         WHERE setting_key IN ($placeholders)"
    );
    if ($settingsQuery) {
        $settingsQuery->bind_param($types, ...$homepageKeys);
        $settingsQuery->execute();
        $result = $settingsQuery->get_result();
        while ($row = $result->fetch_assoc()) {
            $key = $row['setting_key'] ?? '';
            if ($key !== '') {
                $homepageSettings[$key] = (string)($row['setting_value'] ?? '');
            }
        }
        $settingsQuery->close();
    }
}
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
    <script src="../js/jquery.js"></script>
    <script src="../js/validate.js"></script>
    <style>
    .profile-avatar-wrapper {
        width: 10rem;
        height: 10rem;
        margin: -4rem auto 1rem;
    }

    .profile-avatar,
    .profile-avatar-fallback {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 4px solid hsl(var(--card));
        box-shadow: var(--shadow-md);
    }

    .profile-avatar {
        object-fit: cover;
        background: hsl(var(--card));
    }

    .profile-avatar-fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, hsl(var(--primary) / 0.55), hsl(var(--secondary) / 0.55));
        color: hsl(var(--primary-foreground));
        font-size: 2.5rem;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .avatar-wrap {
            flex-direction: column;
            gap: 3;
        }

        .profile-avatar-wrapper {
            width: 8.5rem;
            height: 8.5rem;
            margin-top: -3.4rem;
        }

        .avatar-wrap {
            display: inline-block;
        }

        .btn-outline-danger {
            margin-top: 1rem;
        }
    }
    </style>
</head>

<body class="future-page future-dashboard" data-theme="dark">
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

                <div class="settings-card mb-4">
                    <h5 class="mb-3">Profile Photo</h5>
                    <div class="avatar-wrap d-flex justify-content-center">
                        <div class="profile-avatar-wrapper mt-2">
                            <?php if ($photoUrl): ?>
                            <img src="<?= htmlspecialchars($photoUrl) ?>" class="profile-avatar" alt="Profile photo">
                            <?php else: ?>
                            <div class="profile-avatar-fallback">
                                <?= htmlspecialchars(substr($_SESSION['name'] ?? 'U', 0, 1)) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="">
                            <form method="post" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">
                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="update_photo">
                                <input class="form-control" type="file" name="profile_photo"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-validation="required file"
                                    data-filesize="2097152" required>
                                <div id="profile_photo_error" class="text-danger validation-error w-100"></div>
                                <button type="submit" class="btn btn-primary-custom">Upload Photo</button>
                            </form>
                        </div>

                        <?php if ($photoUrl): ?>
                        <form method="post" class="ms-1">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="remove_photo">
                            <button type="submit" class="btn btn-outline-danger">Remove</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="settings-card mb-4">
                    <h5 class="mb-3">Account Information</h5>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control"
                                    data-validation="required" required>
                                <div id="current_password_error" class="text-danger validation-error"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" id="new_password_settings" name="new_password"
                                    class="form-control" data-validation="required min-length" data-min-length="8"
                                    required>
                                <div id="new_password_error" class="text-danger validation-error"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control"
                                    data-validation="required confirm-password"
                                    data-confirm-password="new_password_settings" required>
                                <div id="confirm_password_error" class="text-danger validation-error"></div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary-custom" name="update_password">Update
                                Password</button>
                        </div>
                    </form>
                </div>

                <?php if ($isAdmin): ?>
                <div class="settings-card mt-4">
                    <h5 class="mb-2">Homepage Content</h5>
                    <p class="text-muted mb-3">Update the public homepage copy without touching code.</p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update_homepage">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Hero Eyebrow</label>
                                <input type="text" name="home_hero_eyebrow" class="form-control" maxlength="240"
                                    value="<?= htmlspecialchars($homepageSettings['home_hero_eyebrow'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Hero Title</label>
                                <input type="text" name="home_hero_title" class="form-control" maxlength="240"
                                    value="<?= htmlspecialchars($homepageSettings['home_hero_title'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Hero Summary</label>
                                <textarea name="home_hero_summary" class="form-control" rows="3"
                                    maxlength="240"><?= htmlspecialchars($homepageSettings['home_hero_summary'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Services Intro</label>
                                <textarea name="home_services_intro" class="form-control" rows="2"
                                    maxlength="240"><?= htmlspecialchars($homepageSettings['home_services_intro'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Solutions Intro</label>
                                <textarea name="home_solutions_intro" class="form-control" rows="2"
                                    maxlength="240"><?= htmlspecialchars($homepageSettings['home_solutions_intro'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Support Intro</label>
                                <textarea name="home_support_intro" class="form-control" rows="2"
                                    maxlength="240"><?= htmlspecialchars($homepageSettings['home_support_intro'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary-custom">Save Homepage Content</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
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
