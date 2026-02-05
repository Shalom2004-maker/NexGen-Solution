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
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Inter", sans-serif;
    }

    html,
    body {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .auth-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        width: min(520px, 90vw);
    }

    .auth-header {
        background-color: #f8f9fa;
        padding: 1rem;
        text-align: center;
        border-bottom: 1px solid #e0e0e0;
    }

    .auth-body {
        padding: 1.6rem;
    }

    .btn-action {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        color: white;
        font-weight: bold;
        padding: 0.55rem;
        border: none;
        border-radius: 6px;
        width: 100%;
    }

    .home-link {
        display: block;
        text-align: center;
        color: #667eea;
        text-decoration: none;
        margin-top: .6rem;
        font-weight: 500;
    }
    </style>
</head>

<body>
    <div class="auth-card">
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
                    <input type="password" class="form-control" name="new_password" required>
                </div>
                <label class="form-label">Confirm Password</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-action">Update Password</button>
            </form>
            <?php endif; ?>

            <a href="login.php" class="home-link"><i class="bi bi-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>

</html>
