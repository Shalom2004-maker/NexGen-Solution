<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

if (!function_exists('app_env_value')) {
    function app_env_value($key, $default = '')
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

if (!function_exists('app_config_bool')) {
    function app_config_bool($value, $default = false)
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }
}

if (!function_exists('app_request_host')) {
    function app_request_host()
    {
        return strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost')));
    }
}

if (!function_exists('app_request_looks_local')) {
    function app_request_looks_local()
    {
        $host = app_request_host();

        if ($host === '') {
            return true;
        }

        $host = preg_replace('/:\d+$/', '', $host);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || substr($host, -6) === '.local'
            || substr($host, -5) === '.test';
    }
}

if (!function_exists('app_mail_config')) {
    function app_mail_config()
    {
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $config = [
            'app_name' => app_env_value('APP_NAME', 'NexGen Solution'),
            'app_url' => app_env_value('APP_URL', ''),
            'mail_host' => app_env_value('MAIL_HOST', ''),
            'mail_port' => app_env_value('MAIL_PORT', 587),
            'mail_username' => app_env_value('MAIL_USERNAME', ''),
            'mail_password' => app_env_value('MAIL_PASSWORD', ''),
            'mail_encryption' => app_env_value('MAIL_ENCRYPTION', 'tls'),
            'mail_auth' => app_env_value('MAIL_AUTH', ''),
            'mail_from_address' => app_env_value('MAIL_FROM_ADDRESS', ''),
            'mail_from_name' => app_env_value('MAIL_FROM_NAME', ''),
            'mail_reply_to_address' => app_env_value('MAIL_REPLY_TO_ADDRESS', ''),
            'mail_reply_to_name' => app_env_value('MAIL_REPLY_TO_NAME', ''),
            'mail_timeout' => app_env_value('MAIL_TIMEOUT', 30),
            'mail_debug' => app_env_value('MAIL_DEBUG', 0),
            'mail_allow_self_signed' => app_env_value('MAIL_ALLOW_SELF_SIGNED', ''),
            'show_dev_reset_link' => app_env_value('SHOW_DEV_RESET_LINK', ''),
        ];

        foreach ([__DIR__ . '/mail_config.local.php', __DIR__ . '/mail_config.php'] as $path) {
            if (!is_file($path)) {
                continue;
            }

            $fileConfig = require $path;
            if (is_array($fileConfig)) {
                $config = array_replace($config, $fileConfig);
            }
        }

        $config['app_name'] = trim((string) ($config['app_name'] ?: 'NexGen Solution'));
        $config['app_url'] = trim((string) ($config['app_url'] ?? ''));
        $config['mail_host'] = trim((string) ($config['mail_host'] ?? ''));
        $config['mail_port'] = max(1, (int) ($config['mail_port'] ?? 587));
        $config['mail_username'] = trim((string) ($config['mail_username'] ?? ''));
        $config['mail_password'] = (string) ($config['mail_password'] ?? '');
        $config['mail_encryption'] = strtolower(trim((string) ($config['mail_encryption'] ?? 'tls')));
        $config['mail_from_address'] = trim((string) ($config['mail_from_address'] ?: $config['mail_username']));
        $config['mail_from_name'] = trim((string) ($config['mail_from_name'] ?: $config['app_name']));
        $config['mail_reply_to_address'] = trim((string) ($config['mail_reply_to_address'] ?? ''));
        $config['mail_reply_to_name'] = trim((string) ($config['mail_reply_to_name'] ?: $config['mail_from_name']));
        $config['mail_timeout'] = max(5, (int) ($config['mail_timeout'] ?? 30));
        $config['mail_debug'] = max(0, min(4, (int) ($config['mail_debug'] ?? 0)));
        $config['mail_auth'] = app_config_bool($config['mail_auth'], $config['mail_username'] !== '');
        $config['mail_allow_self_signed'] = app_config_bool($config['mail_allow_self_signed'], false);
        $config['show_dev_reset_link'] = app_config_bool($config['show_dev_reset_link'], app_request_looks_local());

        if (!in_array($config['mail_encryption'], ['', 'ssl', 'tls', 'starttls'], true)) {
            $config['mail_encryption'] = 'tls';
        }

        return $config;
    }
}

if (!function_exists('app_mail_is_configured')) {
    function app_mail_is_configured()
    {
        $config = app_mail_config();

        if ($config['mail_host'] === '' || $config['mail_from_address'] === '') {
            return false;
        }

        if ($config['mail_auth'] && ($config['mail_username'] === '' || $config['mail_password'] === '')) {
            return false;
        }

        return true;
    }
}

if (!function_exists('app_should_show_dev_reset_link')) {
    function app_should_show_dev_reset_link()
    {
        return (bool) app_mail_config()['show_dev_reset_link'];
    }
}

if (!function_exists('app_public_base_url')) {
    function app_public_base_url()
    {
        $config = app_mail_config();
        if ($config['app_url'] !== '') {
            return rtrim($config['app_url'], '/');
        }

        $scheme = 'http';

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]);
        } elseif (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            $scheme = 'https';
        } elseif ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            $scheme = 'https';
        }

        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');

        return rtrim($scheme . '://' . $host . $basePath, '/');
    }
}

if (!function_exists('app_public_url')) {
    function app_public_url($path = '')
    {
        $path = trim((string) $path);

        if ($path !== '' && preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $baseUrl = app_public_base_url();
        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('app_password_reset_otp_email_html')) {
    function app_password_reset_otp_email_html($recipientName, $otpCode, $resetPageUrl = '')
    {
        $appName = htmlspecialchars((string) app_mail_config()['app_name'], ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars(trim((string) $recipientName) ?: 'there', ENT_QUOTES, 'UTF-8');
        $safeOtp = htmlspecialchars(trim((string) $otpCode), ENT_QUOTES, 'UTF-8');
        $safeResetUrl = htmlspecialchars(trim((string) $resetPageUrl), ENT_QUOTES, 'UTF-8');
        $resetUrlBlock = $safeResetUrl !== ''
            ? '<p style="margin:20px 0 0;font-size:14px;line-height:1.7;">Reset your password here: <a href="' . $safeResetUrl . '">' . $safeResetUrl . '</a></p>'
            : '';

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="margin:0;padding:24px;background:#f5f7fb;color:#1f2937;font-family:Arial,sans-serif;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e5e7eb;">
        <p style="margin:0 0 16px;font-size:14px;letter-spacing:0.08em;text-transform:uppercase;color:#2563eb;">' . $appName . '</p>
        <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;">Your password reset code</h1>
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hi ' . $safeName . ',</p>
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">We received a request to reset your password. Enter this 6-digit code on the reset page. It expires in 1 hour.</p>
        <div style="margin:24px 0;padding:18px 20px;border-radius:14px;background:#eff6ff;border:1px solid #bfdbfe;text-align:center;">
            <div style="font-size:13px;letter-spacing:0.12em;text-transform:uppercase;color:#2563eb;margin-bottom:8px;">Reset Code</div>
            <div style="font-size:34px;font-weight:700;letter-spacing:0.22em;color:#0f172a;">' . $safeOtp . '</div>
        </div>
        ' . $resetUrlBlock . '
        <p style="margin:0;font-size:14px;line-height:1.7;color:#6b7280;">If you did not request a password reset, you can safely ignore this email.</p>
    </div>
</body>
</html>';
    }
}

if (!function_exists('app_password_reset_otp_email_text')) {
    function app_password_reset_otp_email_text($recipientName, $otpCode, $resetPageUrl = '')
    {
        $displayName = trim((string) $recipientName) ?: 'there';
        $appName = (string) app_mail_config()['app_name'];
        $resetPageUrl = trim((string) $resetPageUrl);

        return "Hello {$displayName},\n\n"
            . "We received a request to reset your {$appName} password.\n"
            . "Enter this 6-digit code on the reset page. This code expires in 1 hour.\n\n"
            . "Reset code: {$otpCode}\n\n"
            . ($resetPageUrl !== '' ? "Reset page: {$resetPageUrl}\n\n" : '')
            . "If you did not request a password reset, you can safely ignore this email.\n";
    }
}

if (!function_exists('send_password_reset_otp_email')) {
    function send_password_reset_otp_email($toEmail, $toName, $otpCode, $resetPageUrl = '')
    {
        $toEmail = trim((string) $toEmail);
        $toName = trim((string) $toName);
        $otpCode = trim((string) $otpCode);
        $resetPageUrl = trim((string) $resetPageUrl);

        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'error' => 'Recipient email address is invalid.'];
        }

        if (!preg_match('/^\d{6}$/', $otpCode)) {
            return ['sent' => false, 'error' => 'OTP code must be exactly 6 digits.'];
        }

        if (!app_mail_is_configured()) {
            return [
                'sent' => false,
                'error' => 'SMTP mail settings are incomplete. Configure MAIL_HOST, MAIL_PORT, MAIL_FROM_ADDRESS, and credentials before sending email.',
            ];
        }

        $config = app_mail_config();
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $config['mail_host'];
            $mail->Port = $config['mail_port'];
            $mail->SMTPAuth = (bool) $config['mail_auth'];
            $mail->Username = $config['mail_username'];
            $mail->Password = $config['mail_password'];
            $mail->Timeout = $config['mail_timeout'];
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->SMTPDebug = $config['mail_debug'];

            if ($config['mail_debug'] > 0) {
                $mail->Debugoutput = static function ($message) {
                    error_log('[mailer] ' . trim((string) $message));
                };
            }

            if ($config['mail_encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (in_array($config['mail_encryption'], ['tls', 'starttls'], true)) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            if ($config['mail_allow_self_signed']) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            $mail->setFrom($config['mail_from_address'], $config['mail_from_name']);
            $mail->addAddress($toEmail, $toName);

            if ($config['mail_reply_to_address'] !== '') {
                $mail->addReplyTo($config['mail_reply_to_address'], $config['mail_reply_to_name']);
            }

            $mail->Subject = 'Your ' . $config['app_name'] . ' password reset code';
            $mail->Body = app_password_reset_otp_email_html($toName, $otpCode, $resetPageUrl);
            $mail->AltBody = app_password_reset_otp_email_text($toName, $otpCode, $resetPageUrl);
            $mail->send();

            return ['sent' => true, 'error' => ''];
        } catch (PHPMailerException $e) {
            return ['sent' => false, 'error' => trim((string) $e->getMessage())];
        } catch (Throwable $e) {
            return ['sent' => false, 'error' => trim((string) $e->getMessage())];
        }
    }
}
