<?php
// start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["uid"])) {
    header("Location: ../public/login.php");
    exit();
}

if (!isset($_SESSION["role"])) {
    die("Blocked");
}

// allow accepts a role string or an array of allowed roles
// Normalizes role names (trim + lowercase) and treats Admin as a superuser
function allow($role)
{
    $current = trim((string)($_SESSION["role"] ?? ''));
    $current_lc = strtolower($current);

    // Admin bypasses all role checks
    if ($current_lc === 'admin') {
        return;
    }

    if (is_array($role)) {
        $normalized = array_map(function ($r) {
            return strtolower(trim((string)$r));
        }, $role);
        if (!in_array($current_lc, $normalized, true)) {
            http_response_code(403);
            die("Access denied");
        }
    } else {
        if ($current_lc !== strtolower(trim((string)$role))) {
            http_response_code(403);
            die("Access denied");
        }
    }
}
