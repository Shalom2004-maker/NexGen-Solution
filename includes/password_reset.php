<?php

if (!function_exists('app_password_reset_otp_ttl_seconds')) {
    function app_password_reset_otp_ttl_seconds()
    {
        return 180;
    }
}

if (!function_exists('app_password_reset_max_regenerations')) {
    function app_password_reset_max_regenerations()
    {
        return 3;
    }
}

if (!function_exists('app_password_reset_max_requests')) {
    function app_password_reset_max_requests()
    {
        return app_password_reset_max_regenerations() + 1;
    }
}

if (!function_exists('app_password_reset_lockout_seconds')) {
    function app_password_reset_lockout_seconds()
    {
        return 86400;
    }
}

if (!function_exists('app_password_reset_parse_datetime')) {
    function app_password_reset_parse_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : $timestamp;
    }
}

if (!function_exists('app_password_reset_seconds_left')) {
    function app_password_reset_seconds_left($value, $now = null)
    {
        $timestamp = app_password_reset_parse_datetime($value);
        if ($timestamp === null) {
            return 0;
        }

        $now = $now === null ? time() : (int) $now;
        return max(0, $timestamp - $now);
    }
}

if (!function_exists('app_password_reset_format_seconds')) {
    function app_password_reset_format_seconds($seconds)
    {
        $seconds = max(0, (int) $seconds);
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
}

if (!function_exists('app_password_reset_effective_request_count')) {
    function app_password_reset_effective_request_count($user, $now = null)
    {
        $now = $now === null ? time() : (int) $now;
        $requestCount = max(0, (int) ($user['password_reset_request_count'] ?? 0));

        if ($requestCount === 0) {
            return 0;
        }

        $lockedUntil = app_password_reset_parse_datetime($user['password_reset_locked_until'] ?? null);
        if ($lockedUntil !== null && $lockedUntil <= $now) {
            return 0;
        }

        $lastRequestedAt = app_password_reset_parse_datetime($user['password_reset_last_requested_at'] ?? null);
        if ($lastRequestedAt !== null && ($now - $lastRequestedAt) >= app_password_reset_lockout_seconds()) {
            return 0;
        }

        return $requestCount;
    }
}

if (!function_exists('app_password_reset_ensure_schema')) {
    function app_password_reset_ensure_schema($conn)
    {
        static $schemaReady = null;

        if ($schemaReady !== null) {
            return $schemaReady;
        }

        $schemaReady = false;
        $columns = [];
        $result = $conn->query("SHOW COLUMNS FROM `users`");

        if (!$result) {
            return false;
        }

        while ($row = $result->fetch_assoc()) {
            $columns[(string) ($row['Field'] ?? '')] = true;
        }
        $result->close();

        $definitions = [
            'password_reset_request_count' => "ALTER TABLE `users` ADD COLUMN `password_reset_request_count` tinyint(3) unsigned NOT NULL DEFAULT 0 AFTER `password_reset_expires_at`",
            'password_reset_locked_until' => "ALTER TABLE `users` ADD COLUMN `password_reset_locked_until` datetime DEFAULT NULL AFTER `password_reset_request_count`",
            'password_reset_last_requested_at' => "ALTER TABLE `users` ADD COLUMN `password_reset_last_requested_at` datetime DEFAULT NULL AFTER `password_reset_locked_until`",
        ];

        foreach ($definitions as $column => $query) {
            if (isset($columns[$column])) {
                continue;
            }

            if (!$conn->query($query)) {
                if (function_exists('audit_log')) {
                    audit_log('password_reset_schema_update_failed', 'Failed to add column ' . $column . ' to users table.');
                }

                return false;
            }
        }

        $schemaReady = true;
        return true;
    }
}

if (!function_exists('app_password_reset_fetch_user_state')) {
    function app_password_reset_fetch_user_state($conn, $email)
    {
        $email = trim((string) $email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        if (!app_password_reset_ensure_schema($conn)) {
            return null;
        }

        $stmt = $conn->prepare(
            "SELECT id, full_name, password_reset_token, password_reset_expires_at, password_reset_request_count,
                    password_reset_locked_until, password_reset_last_requested_at
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return null;
        }

        $now = time();
        $effectiveRequestCount = app_password_reset_effective_request_count($user, $now);
        $otpExpiresIn = app_password_reset_seconds_left($user['password_reset_expires_at'] ?? null, $now);
        $lockoutExpiresIn = app_password_reset_seconds_left($user['password_reset_locked_until'] ?? null, $now);

        $user['password_reset_request_count'] = $effectiveRequestCount;
        $user['otp_expires_in'] = $otpExpiresIn;
        $user['lockout_expires_in'] = $lockoutExpiresIn;
        $user['remaining_regenerations'] = max(0, app_password_reset_max_regenerations() - max(0, $effectiveRequestCount - 1));
        $user['has_active_otp'] = trim((string) ($user['password_reset_token'] ?? '')) !== '' && $otpExpiresIn > 0;
        $user['is_locked'] = $lockoutExpiresIn > 0;

        return $user;
    }
}

if (!function_exists('app_password_reset_issue_otp')) {
    function app_password_reset_issue_otp($conn, $user)
    {
        $now = time();
        $lockedUntilTimestamp = app_password_reset_parse_datetime($user['password_reset_locked_until'] ?? null);

        if ($lockedUntilTimestamp !== null && $lockedUntilTimestamp > $now) {
            return [
                'status' => 'locked',
                'lockout_seconds' => $lockedUntilTimestamp - $now,
                'locked_until' => date('Y-m-d H:i:s', $lockedUntilTimestamp),
            ];
        }

        $requestCount = app_password_reset_effective_request_count($user, $now) + 1;
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAtTimestamp = $now + app_password_reset_otp_ttl_seconds();
        $expiresAt = date('Y-m-d H:i:s', $expiresAtTimestamp);
        $lastRequestedAt = date('Y-m-d H:i:s', $now);
        $lockedUntil = null;
        $lockedUntilValue = '';
        $lockoutSeconds = 0;

        if ($requestCount >= app_password_reset_max_requests()) {
            $lockoutSeconds = app_password_reset_lockout_seconds();
            $lockedUntil = date('Y-m-d H:i:s', $now + $lockoutSeconds);
            $lockedUntilValue = $lockedUntil;
        }

        $stmt = $conn->prepare(
            "UPDATE users
             SET password_reset_token = ?,
                 password_reset_expires_at = ?,
                 password_reset_request_count = ?,
                 password_reset_locked_until = NULLIF(?, ''),
                 password_reset_last_requested_at = ?
             WHERE id = ?"
        );

        if (!$stmt) {
            return ['status' => 'store_failed'];
        }

        $stmt->bind_param(
            'ssissi',
            $otpCode,
            $expiresAt,
            $requestCount,
            $lockedUntilValue,
            $lastRequestedAt,
            $user['id']
        );

        $stored = $stmt->execute();
        $stmt->close();

        if (!$stored) {
            return ['status' => 'store_failed'];
        }

        return [
            'status' => 'issued',
            'otp' => $otpCode,
            'expires_at' => $expiresAt,
            'expires_in' => app_password_reset_otp_ttl_seconds(),
            'request_count' => $requestCount,
            'remaining_regenerations' => max(0, app_password_reset_max_regenerations() - max(0, $requestCount - 1)),
            'lockout_seconds' => $lockoutSeconds,
            'locked_until' => $lockedUntil,
        ];
    }
}
