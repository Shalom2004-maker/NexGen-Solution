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
                if (function_exists('audit_log')) {
                    audit_log('password_reset_request', "Password reset requested for {$email}", $user['id']);
                }
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
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Orbitron:wght@500;700&display=swap"
        rel="stylesheet">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">
    <script src="../js/jquery.js"></script>
    <script src="../js/validate.js"></script>
    <script src="../js/future-ui.js" defer></script>
</head>

<body class="future-page future-forgot" data-theme="nebula">
    <div class="future-grid" aria-hidden="true"></div>
    <div class="future-orb future-orb-a" aria-hidden="true"></div>
    <div class="future-orb future-orb-b" aria-hidden="true"></div>
    <div class="future-orb future-orb-c" aria-hidden="true"></div>

    <div class="theme-float">
        <div class="theme-switcher neo-panel" role="group" aria-label="Theme switcher">
            <span class="theme-switcher-label">Theme</span>
            <button class="theme-chip pressable is-active" type="button" data-theme-choice="nebula"
                aria-pressed="true">Nebula</button>
            <button class="theme-chip pressable" type="button" data-theme-choice="ember"
                aria-pressed="false">Ember</button>
            <button class="theme-chip pressable" type="button" data-theme-choice="aurora"
                aria-pressed="false">Aurora</button>
        </div>
    </div>

    <div class="auth-wrap">
        <div class="auth-card tilt-surface" data-tilt="6">
            <div class="auth-header">
                <h4>Forgot Password</h4>
                <p class="mb-0">Request a password reset link</p>
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
                <div class="alert alert-info dev-link-box">
                    <strong>Reset link (dev):</strong>
                    <a class="dev-link" href="<?= htmlspecialchars($resetLink) ?>">Reset your password</a>
                </div>
                <?php endif; ?>

                <form method="post" action="forgot_password.php">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <label for="emailInput" class="form-label">Email Address</label>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="text" class="form-control" data-validation="required email" id="emailInput"
                                name="email" placeholder="Enter your email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <span id="email_error" class="text-danger"></span>
                    </div>
                    <button type="submit" class="btn-action pressable" data-tilt="8">Generate Reset Link</button>
                </form>

                <a href="login.php" class="home-link">
                    <i class="bi bi-arrow-left"></i> Back To Login
                </a>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>