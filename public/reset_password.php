<?php
session_start();
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";
require_once __DIR__ . "/../includes/password_reset.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$error = '';
$success = '';
$email = trim($_GET['email'] ?? ($_POST['email'] ?? ($_SESSION['password_reset_email'] ?? '')));
$otp = trim($_POST['otp'] ?? '');
$otpCountdownSeconds = 0;
$lockoutSeconds = 0;
$statusInfo = '';
$passwordResetSchemaReady = app_password_reset_ensure_schema($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $error = 'Invalid request.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $otp = trim($_POST['otp'] ?? '');
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            $error = 'Please enter the 6-digit reset code.';
        } elseif ($new === '' || $confirm === '') {
            $error = 'Please complete all fields.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!$passwordResetSchemaReady) {
            $error = 'Password reset setup is incomplete. Please update the users table and try again.';
        } else {
            $userId = 0;
            $resetUser = app_password_reset_fetch_user_state($conn, $email);

            if ($resetUser) {
                $storedOtp = trim((string) ($resetUser['password_reset_token'] ?? ''));
                $otpCountdownSeconds = (int) ($resetUser['otp_expires_in'] ?? 0);
                $lockoutSeconds = (int) ($resetUser['lockout_expires_in'] ?? 0);

                if ($storedOtp === '' || $otpCountdownSeconds <= 0 || !hash_equals($storedOtp, $otp)) {
                    $error = 'This reset code is invalid or has expired.';
                } else {
                    $userId = (int) ($resetUser['id'] ?? 0);
                }
            } else {
                $error = 'This reset code is invalid or has expired.';
            }

            if ($error === '') {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $u = $conn->prepare(
                    "UPDATE users
                     SET password_hash = ?,
                         password = '',
                         password_reset_token = NULL,
                         password_reset_expires_at = NULL,
                         password_reset_request_count = 0,
                         password_reset_locked_until = NULL,
                         password_reset_last_requested_at = NULL
                     WHERE id = ?"
                );
                if ($u) {
                    $u->bind_param('si', $newHash, $userId);
                    if ($u->execute()) {
                        $success = 'Your password has been reset. You can now log in.';
                        if (function_exists('audit_log')) {
                            audit_log('password_reset', "Password reset for user {$userId}", $userId);
                        }
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                        unset($_SESSION['password_reset_email']);
                        $otp = '';
                        $otpCountdownSeconds = 0;
                        $lockoutSeconds = 0;
                    } else {
                        $error = 'Failed to reset password.';
                    }
                    $u->close();
                } else {
                    $error = 'Failed to reset password.';
                }
            }
        }
    }
}

$activeResetState = null;
if ($passwordResetSchemaReady && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $activeResetState = app_password_reset_fetch_user_state($conn, $email);

    if ($activeResetState && !$success) {
        if ($otpCountdownSeconds === 0 && !empty($activeResetState['has_active_otp'])) {
            $otpCountdownSeconds = (int) ($activeResetState['otp_expires_in'] ?? 0);
        }

        if ($lockoutSeconds === 0 && !empty($activeResetState['is_locked'])) {
            $lockoutSeconds = (int) ($activeResetState['lockout_expires_in'] ?? 0);
        }

        if (!empty($activeResetState['has_active_otp'])) {
            $statusInfo = 'Your current OTP is valid for 3 minutes from the most recent request.';
        } elseif (trim((string) ($activeResetState['password_reset_token'] ?? '')) !== '') {
            $statusInfo = 'Your most recent OTP has expired. Return to the forgot-password page to generate a new one.';
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
    <script src="../js/password-reset-timer.js" defer></script>
</head>

<body class="future-page future-forgot" data-theme="dark">
    <div class="future-grid" aria-hidden="true"></div>
    <div class="future-orb future-orb-a" aria-hidden="true"></div>
    <div class="future-orb future-orb-b" aria-hidden="true"></div>
    <div class="future-orb future-orb-c" aria-hidden="true"></div>

    <div class="theme-switcher mx-2 ms-2 mt-2 me-2 position-absolute end-0 top-0" role="group"
        aria-label="Theme toggle">
        <button class="theme-chip pressable" type="button" data-theme-toggle data-icon-light="bi-sun-fill"
            data-icon-dark="bi-moon-fill" aria-label="Toggle theme" aria-pressed="true">
            <i class="bi bi-moon-fill" aria-hidden="true"></i>
        </button>
    </div>

    <div class="auth-wrap">
        <div class="auth-card tilt-surface" data-tilt="6">
            <div class="auth-header">
                <h4>Reset Password</h4>
                <p class="text-muted mb-0">Enter the 6-digit code from your email and set a new password</p>
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

                <?php if ($statusInfo && !$success): ?>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle"></i> <?= htmlspecialchars($statusInfo) ?>
                </div>
                <?php endif; ?>

                <?php if ($otpCountdownSeconds > 0 && !$success): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-clock-history"></i>
                    <span data-reset-countdown="<?= (int) $otpCountdownSeconds ?>"
                        data-prefix="Current OTP expires in "
                        data-expired-text="The current OTP has expired. Request a new one to continue."></span>
                </div>
                <?php endif; ?>

                <?php if ($lockoutSeconds > 0 && !$success): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-shield-lock"></i>
                    <span data-reset-countdown="<?= (int) $lockoutSeconds ?>"
                        data-prefix="OTP regeneration becomes available in "
                        data-expired-text="You can request a new OTP again now."></span>
                </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="post" action="reset_password.php">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <label class="form-label">Email Address</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="text" class="form-control" id="email" name="email" data-validation="required email"
                            value="<?= htmlspecialchars($email) ?>" placeholder="Enter your email" required>
                    </div>
                    <div id="email_error" class="text-danger validation-error"></div>
                    <label class="form-label">Reset Code</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="text" class="form-control" id="otp" name="otp"
                            data-validation="required numeric min-length max-length" data-min-length="6"
                            data-max-length="6" minlength="6" maxlength="6" inputmode="numeric"
                            autocomplete="one-time-code" value="<?= htmlspecialchars($otp) ?>"
                            placeholder="Enter the 6-digit code" required>
                    </div>
                    <div id="otp_error" class="text-danger validation-error"></div>
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
                    <button type="submit" class="btn-action pressable" data-tilt="8">Verify Code And Update
                        Password</button>
                </form>
                <?php endif; ?>

                <a href="forgot_password.php" class="home-link"><i class="bi bi-arrow-repeat"></i> Request a new
                    code</a>
                <a href="login.php" class="home-link"><i class="bi bi-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/validate.js"></script>
</body>

</html>
