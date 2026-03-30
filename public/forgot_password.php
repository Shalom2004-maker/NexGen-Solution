<?php
session_start();
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";
require_once __DIR__ . "/../includes/mailer.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$error = '';
$success = '';
$otpPreview = '';
$mailInfo = '';
$resetPageUrl = app_public_url('reset_password.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = 'Invalid request.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($user) {
                    $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $tokenHash = hash('sha256', $otpCode);
                    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
                    $tokenSaved = false;

                    $u = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?");
                    if ($u) {
                        $u->bind_param('ssi', $tokenHash, $expiresAt, $user['id']);
                        $tokenSaved = $u->execute();
                        $u->close();
                    }

                    if ($tokenSaved) {
                        $_SESSION['password_reset_email'] = $email;
                        $resetPageUrl = app_public_url('reset_password.php?email=' . urlencode($email));

                        $mailResult = send_password_reset_otp_email(
                            $email,
                            (string) ($user['full_name'] ?? ''),
                            $otpCode,
                            $resetPageUrl
                        );

                        if (!empty($mailResult['sent'])) {
                            if (function_exists('audit_log')) {
                                audit_log('password_reset_mail_sent', "Password reset OTP email sent to {$email}", $user['id']);
                            }
                        } else {
                            $mailError = trim((string) ($mailResult['error'] ?? ''));
                            if (function_exists('audit_log')) {
                                audit_log(
                                    'password_reset_mail_failed',
                                    "Password reset OTP email failed for {$email}: " . ($mailError !== '' ? $mailError : 'Unknown mail error'),
                                    $user['id']
                                );
                            }

                            if (app_should_show_dev_reset_link()) {
                                $mailInfo = 'Email delivery failed on this environment'
                                    . ($mailError !== '' ? ' (' . $mailError . ')' : '')
                                    . ', so the development OTP is shown below.';
                                $otpPreview = $otpCode;
                            }
                        }
                    } elseif (function_exists('audit_log')) {
                        audit_log('password_reset_token_store_failed', "Failed to store password reset OTP for {$email}", $user['id']);
                    }

                    if (function_exists('audit_log')) {
                        audit_log('password_reset_request', "Password reset requested for {$email}", $user['id']);
                    }
                }
            } elseif (function_exists('audit_log')) {
                audit_log('password_reset_lookup_failed', "Failed to prepare password reset lookup for {$email}");
            }

            $success = 'If the email exists, a 6-digit reset code has been prepared.';
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
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@400;600;700&display=swap"
        rel="stylesheet">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/colors.css" rel="stylesheet">
    <link href="../css/theme.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">
    <script src="../js/jquery.js"></script>
    <script src="../js/validate.js"></script>
    <script src="../js/future-ui.js" defer></script>
</head>

<body class="future-page future-forgot" data-theme="dark">
    <div class="future-grid" aria-hidden="true"></div>
    <div class="future-orb future-orb-a" aria-hidden="true"></div>
    <div class="future-orb future-orb-b" aria-hidden="true"></div>
    <div class="future-orb future-orb-c" aria-hidden="true"></div>

    <div class="theme-switcher" role="group" aria-label="Theme toggle">
        <button class="theme-chip pressable" type="button" data-theme-toggle data-icon-light="bi-sun-fill"
            data-icon-dark="bi-moon-fill" aria-label="Toggle theme" aria-pressed="true">
            <i class="bi bi-moon-fill" aria-hidden="true"></i>
        </button>
    </div>

    <div class="auth-wrap">
        <div class="auth-card tilt-surface" data-tilt="6">
            <div class="auth-header">
                <h4>Forgot Password</h4>
                <p class="mb-0">Request a password reset code by email</p>
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

                <?php if ($mailInfo): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle"></i> <?= htmlspecialchars($mailInfo) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($otpPreview): ?>
                <div class="alert alert-info dev-link-box">
                    <strong>Reset OTP (dev):</strong>
                    <span class="dev-link"><?= htmlspecialchars($otpPreview) ?></span>
                    <div class="mt-2">
                        <a class="dev-link" href="<?= htmlspecialchars($resetPageUrl) ?>">Open reset page</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-secondary">
                    <a class="dev-link" href="<?= htmlspecialchars($resetPageUrl) ?>">Continue to the reset page</a>
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
                    <button type="submit" class="btn-action pressable" data-tilt="8">Send Reset Code</button>
                </form>

                <a href="reset_password.php" class="home-link">
                    <i class="bi bi-shield-lock"></i> I already have a reset code
                </a>

                <a href="login.php" class="home-link">
                    <i class="bi bi-arrow-left"></i> Back To Login
                </a>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>
