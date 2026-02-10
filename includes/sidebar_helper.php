<?php
// Helper to include the correct sidebar based on the logged-in role.
if (!function_exists('render_sidebar')) {
    function render_sidebar(?string $role = null): void
    {
        $role = $role ?? ($_SESSION['role'] ?? '');
        $role_lc = strtolower(trim((string)$role));
        $dashboardDir = dirname(__DIR__) . '/dashboard';

        switch ($role_lc) {
            case 'admin':
                include $dashboardDir . '/admin_siderbar.php';
                break;
            case 'projectleader':
                include $dashboardDir . '/leader_sidebar.php';
                break;
            case 'hr':
                include $dashboardDir . '/hr_sidebar.php';
                break;
            case 'employee':
                include $dashboardDir . '/employee_sidebar.php';
                break;
            default:
                include $dashboardDir . '/employee_sidebar.php';
                break;
        }
    }
}

if (!function_exists('sidebar_avatar_url')) {
    function sidebar_avatar_url(): string
    {
        $path = (string)($_SESSION['profile_photo'] ?? '');
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\\/\\//i', $path)) {
            return $path;
        }
        return '../' . ltrim($path, '/');
    }
}

if (!function_exists('resolve_avatar_url')) {
    function resolve_avatar_url(?string $dbPath = null): string
    {
        $path = $dbPath ?? '';
        if ($path === '') {
            $path = (string)($_SESSION['profile_photo'] ?? '');
        }
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\\/\\//i', $path)) {
            return $path;
        }
        return '../' . ltrim($path, '/');
    }
}
?>
