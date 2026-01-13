<?php
// Recursively run `php -l` on all .php files under the current working directory.
// Usage: from project root run `php scripts/check_php.php`

$start = getcwd();
$exclude = [
    DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR,
];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($start));
$total = 0;
$failed = [];

foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'php') continue;
    $skip = false;
    foreach ($exclude as $ex) {
        if (strpos($path, $ex) !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;
    $total++;
    $cmd = 'php -l ' . escapeshellarg($path) . ' 2>&1';
    exec($cmd, $out, $ret);
    if ($ret !== 0) {
        $failed[$path] = implode("\n", $out);
        echo "[FAIL] $path\n";
        echo implode("\n", $out) . "\n\n";
    } else {
        echo "[OK]   $path\n";
    }
}

echo "\nChecked: $total PHP files. Failures: " . count($failed) . "\n";
if (count($failed) > 0) {
    echo "Run again to see details above.\n";
    exit(1);
}
exit(0);
