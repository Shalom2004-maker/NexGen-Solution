<?php
session_start();
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$error = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = 'Invalid request.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user) {
                $rawToken = bin2hex(random_bytes(24));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                $u = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?");
                $u->bind_param('ssi', $tokenHash, $expiresAt, $user['id']);
                $u->execute();
                $u->close();

                $resetLink = 'reset_password.php?token=' . $rawToken;
                if (function_exists('audit_log')) audit_log('password_reset_request', "Password reset requested for {$email}", $user['id']);
            }

            $success = 'If the email exists, a reset link has been generated.';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - NexGen Solution</title>

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
            <h4>Forgot Password</h4>
            <p class="text-muted mb-0">Request a password reset link</p>
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

            <?php if ($resetLink): ?>
            <div class="alert alert-info">
                <strong>Reset link (dev):</strong>
                <a href="<?= htmlspecialchars($resetLink) ?>">Reset your password</a>
            </div>
            <?php endif; ?>

            <form method="post" action="forgot_password.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <label for="emailInput" class="form-label">Email Address</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="emailInput" name="email"
                        placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-action">Generate Reset Link</button>
            </form>

            <a href="login.php" class="home-link"><i class="bi bi-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>

</html>
