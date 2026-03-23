<?php
session_start();
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$error = '';
$success = '';
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$tokenHash = $token !== '' ? hash('sha256', $token) : '';
$valid = false;
$userId = 0;

if ($tokenHash !== '') {
    $stmt = $conn->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires_at IS NOT NULL AND password_reset_expires_at > NOW() LIMIT 1");
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $valid = true;
        $userId = (int)$row['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $error = 'Invalid request.';
    } elseif (!$valid) {
        $error = 'This reset link is invalid or has expired.';
    } else {
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new === '' || $confirm === '') {
            $error = 'Please complete all fields.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $u = $conn->prepare("UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = ?");
            $u->bind_param('si', $newHash, $userId);
            if ($u->execute()) {
                $success = 'Your password has been reset. You can now log in.';
                if (function_exists('audit_log')) audit_log('password_reset', "Password reset for user {$userId}", $userId);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                $valid = false;
            } else {
                $error = 'Failed to reset password.';
            }
            $u->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - NexGen Solution</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@400;600;700&display=swap"
        rel="stylesheet">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/colors.css" rel="stylesheet">
    <link href="../css/theme.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">
    <script src="../js/future-ui.js" defer></script>
</head>

<body class="future-page future-forgot" data-theme="dark">
    <div class="future-grid" aria-hidden="true"></div>
    <div class="future-orb future-orb-a" aria-hidden="true"></div>
    <div class="future-orb future-orb-b" aria-hidden="true"></div>
    <div class="future-orb future-orb-c" aria-hidden="true"></div>

    <div class="theme-float">
        <div class="theme-switcher neo-panel" role="group" aria-label="Theme switcher">
            <span class="theme-switcher-label">Theme</span>
            <button class="theme-chip pressable is-active" type="button" data-theme-choice="dark"
                aria-pressed="true">Dark</button>
            <button class="theme-chip pressable" type="button" data-theme-choice="light"
                aria-pressed="false">Light</button>
        </div>
    </div>

    <div class="auth-wrap">
        <div class="auth-card tilt-surface" data-tilt="6">
        <div class="auth-header">
            <h4>Reset Password</h4>
            <p class="text-muted mb-0">Set a new password</p>
        </div>
        <div class="auth-body">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($valid && !$success): ?>
            <form method="post" action="reset_password.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <label class="form-label">New Password</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="new_password" name="new_password"
                        data-validation="required min-length" data-min-length="8" required>
                </div>
                <div id="new_password_error" class="text-danger validation-error"></div>
                <label class="form-label">Confirm Password</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" name="confirm_password"
                        data-validation="required confirm-password" data-confirm-password="new_password" required>
                </div>
                <div id="confirm_password_error" class="text-danger validation-error"></div>
                <button type="submit" class="btn-action pressable" data-tilt="8">Update Password</button>
            </form>
            <?php endif; ?>

            <a href="login.php" class="home-link"><i class="bi bi-arrow-left"></i> Back to Login</a>
        </div>
    </div>
    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/validate.js"></script>
</body>

</html>
