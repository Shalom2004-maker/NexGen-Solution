<?php
session_start();
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";
require_once __DIR__ . "/../includes/mailer.php";
require_once __DIR__ . "/../includes/password_reset.php";

function app_public_url($path)
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/public/' . $path;
}

function send_password_reset_otp_email($to, $fullName, $otpCode, $resetPageUrl)
{
    $subject = 'Password Reset Code - NexGen Solution';
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #007bff; color: white; padding: 10px; text-align: center; }
            .content { padding: 20px; }
            .otp { font-size: 24px; font-weight: bold; color: #007bff; text-align: center; margin: 20px 0; }
            .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Password Reset Request</h2>
            </div>
            <div class='content'>
                <p>Hi {$fullName},</p>
                <p>You requested a password reset for your NexGen Solution account.</p>
                <p>Your 6-digit reset code is:</p>
                <div class='otp'>{$otpCode}</div>
                <p>This code will expire in 3 minutes.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
                <p><a href='{$resetPageUrl}' class='button'>Reset Password</a></p>
            </div>
        </div>
    </body>
    </html>
    ";

    $result = sendEmail($to, $subject, $body);
    if ($result === true) {
        return ['sent' => true];
    } else {
        return ['sent' => false, 'error' => $result];
    }
}

function app_should_show_dev_reset_link()
{
    // Show dev link if running on localhost or if a specific environment variable is set
    return ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === 0 || getenv('SHOW_DEV_RESET_LINK') === 'true');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$error = '';
$success = '';
$otpPreview = '';
$mailInfo = '';
$countdownSeconds = 0;
$policyInfo = '';
$lockoutSeconds = 0;
$resetPageUrl = app_public_url('reset_password.php');
$passwordResetSchemaReady = app_password_reset_ensure_schema($conn);

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
                    if (!$passwordResetSchemaReady) {
                        $error = 'Password reset setup is incomplete. Please update the users table and try again.';
                        if (function_exists('audit_log')) {
                            audit_log('password_reset_schema_missing', "Password reset schema is missing for {$email}", $user['id']);
                        }
                    } else {
                        $resetUser = app_password_reset_fetch_user_state($conn, $email);
                        $issueResult = $resetUser ? app_password_reset_issue_otp($conn, $resetUser) : ['status' => 'store_failed'];

                        if (($issueResult['status'] ?? '') === 'locked') {
                            $lockoutSeconds = (int) ($issueResult['lockout_seconds'] ?? 0);
                            $error = 'You have used all available OTP regeneration attempts. Please try again in '
                                . app_password_reset_format_seconds($lockoutSeconds) . '.';
                        } elseif (($issueResult['status'] ?? '') === 'issued') {
                            $otpCode = (string) ($issueResult['otp'] ?? '');
                            $countdownSeconds = (int) ($issueResult['expires_in'] ?? 0);
                            $lockoutSeconds = (int) ($issueResult['lockout_seconds'] ?? 0);
                            $remainingRegenerations = (int) ($issueResult['remaining_regenerations'] ?? 0);

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

                            if ($remainingRegenerations > 0) {
                                $policyInfo = 'This OTP expires in 3 minutes. You can regenerate it '
                                    . $remainingRegenerations
                                    . ' more time' . ($remainingRegenerations === 1 ? '' : 's')
                                    . ' before the 24-hour cooldown starts.';
                            } else {
                                $policyInfo = 'This OTP expires in 3 minutes. You have used the last available OTP request, so the next one can be requested after the 24-hour cooldown.';
                            }
                        } else {
                            $error = 'Unable to prepare a password reset OTP right now.';
                            if (function_exists('audit_log')) {
                                audit_log('password_reset_token_store_failed', "Failed to store password reset OTP for {$email}", $user['id']);
                            }
                        }
                    }

                    if (function_exists('audit_log')) {
                        audit_log('password_reset_request', "Password reset requested for {$email}", $user['id']);
                    }
                }
            } elseif (function_exists('audit_log')) {
                audit_log('password_reset_lookup_failed', "Failed to prepare password reset lookup for {$email}");
            }

            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

            if ($error === '') {
                $success = 'If the email exists, a 6-digit reset code has been prepared.';
            }
        }
    }
}

$activeEmail = trim($_POST['email'] ?? ($_SESSION['password_reset_email'] ?? ''));
$activeResetState = null;

if ($passwordResetSchemaReady && $activeEmail !== '' && filter_var($activeEmail, FILTER_VALIDATE_EMAIL)) {
    $activeResetState = app_password_reset_fetch_user_state($conn, $activeEmail);

    if ($activeResetState) {
        if ($countdownSeconds === 0 && !empty($activeResetState['has_active_otp'])) {
            $countdownSeconds = (int) ($activeResetState['otp_expires_in'] ?? 0);
        }

        if ($lockoutSeconds === 0 && !empty($activeResetState['is_locked'])) {
            $lockoutSeconds = (int) ($activeResetState['lockout_expires_in'] ?? 0);
        }

        if ($policyInfo === '' && !empty($activeResetState['has_active_otp'])) {
            $remainingRegenerations = (int) ($activeResetState['remaining_regenerations'] ?? 0);

            if ($remainingRegenerations > 0) {
                $policyInfo = 'This OTP expires in 3 minutes. You can regenerate it '
                    . $remainingRegenerations
                    . ' more time' . ($remainingRegenerations === 1 ? '' : 's')
                    . ' before the 24-hour cooldown starts.';
            } else {
                $policyInfo = 'This OTP expires in 3 minutes. You have used the last available OTP request, so the next one can be requested after the 24-hour cooldown.';
            }
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
    <script src="../js/password-reset-timer.js" defer></script>
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

                <?php if ($countdownSeconds > 0): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-clock-history"></i>
                    <span data-reset-countdown="<?= (int) $countdownSeconds ?>" data-prefix="Current OTP expires in "
                        data-expired-text="The current OTP has expired. Request a new one if you still need access."></span>
                </div>
                <?php endif; ?>

                <?php if ($policyInfo): ?>
                <div class="alert alert-secondary" role="alert">
                    <i class="bi bi-arrow-repeat"></i> <?= htmlspecialchars($policyInfo) ?>
                </div>
                <?php endif; ?>

                <?php if ($lockoutSeconds > 0): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-shield-lock"></i>
                    <span data-reset-countdown="<?= (int) $lockoutSeconds ?>"
                        data-prefix="OTP regeneration becomes available in "
                        data-expired-text="You can request a new OTP again now."></span>
                </div>
                <?php endif; ?>

                <?php if ($otpPreview): ?>
                <div class="alert alert-info dev-link-box d-flex flex-column" role="alert">
                    <div>
                        <strong>Reset OTP (dev):</strong>
                        <span class="dev-link"><?= htmlspecialchars($otpPreview) ?></span>
                    </div>
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
                                value="<?= htmlspecialchars($activeEmail) ?>">
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