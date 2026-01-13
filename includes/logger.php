<?php
// Simple audit logger
function get_client_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function audit_log($action, $message, $userId = null)
{
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/audit.log';
    $time = date('Y-m-d H:i:s');
    $ip = get_client_ip();
    $uid = $userId ?? ($_SESSION['uid'] ?? 'guest');
    $line = "[$time] ip=$ip user=$uid action=$action msg=" . str_replace(["\n", "\r"], [' ', ' '], $message) . "\n";
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}
