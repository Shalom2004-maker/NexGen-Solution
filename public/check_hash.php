<?php
// Temporary local utility: verify a plaintext password against a bcrypt hash.
// Remove this file after debugging.
require_once __DIR__ . '/../includes/logger.php';

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plain = $_POST['plain'] ?? '';
    $hash = $_POST['hash'] ?? '';
    $ok = false;
    if ($plain !== '' && $hash !== '') {
        $ok = password_verify($plain, $hash);
        $result = $ok ? 'MATCH' : 'NO MATCH';
        if (function_exists('audit_log')) {
            audit_log('hash_check', "check plain(len=" . strlen($plain) . ") result={$result}", null);
        }
    } else {
        $result = 'Provide both plain and hash.';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Check Password Hash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">
    <div class="container">
        <h4>Verify Password Hash</h4>
        <p class="text-muted">Local debug: paste plaintext and bcrypt hash to verify.</p>
        <form method="post">
            <div class="mb-2">
                <label class="form-label">Plaintext password</label>
                <input name="plain" class="form-control" autocomplete="off">
            </div>
            <div class="mb-2">
                <label class="form-label">Hash</label>
                <textarea name="hash" class="form-control" rows="2"></textarea>
            </div>
            <button class="btn btn-primary">Check</button>
        </form>

        <?php if ($result !== null): ?>
        <div class="alert <?= ($result === 'MATCH') ? 'alert-success' : 'alert-danger' ?> mt-3">
            Result: <?= htmlspecialchars($result) ?>
        </div>
        <?php endif; ?>
    </div>
</body>

</html>