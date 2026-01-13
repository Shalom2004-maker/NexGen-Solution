<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #343a40;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container mt-5 pt-5 py-4">
        <nav class="navbar bg-dark border-bottom border-body w-100" data-bs-theme="dark">
            <span class="navbar-brand">Spycray Portal</span>
            <div class="ms-auto">
                <span class="text-light me-3"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?> (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</span>
                <a href="../public/logout.php" class="btn btn-light">Logout</a>
            </div>
        </nav>
        <?php
        // Optional diagnostics banner: set environment variable DEBUG_SHOW_SESSION=1 to enable
        if (getenv('DEBUG_SHOW_SESSION') === '1') {
            $uid = htmlspecialchars($_SESSION['uid'] ?? '');
            $role = htmlspecialchars($_SESSION['role'] ?? '');
            echo "<div class=\"alert alert-warning mt-2\">DEBUG: uid={$uid} role={$role}</div>";
        }
        ?>
    </div>
</body>

</html>