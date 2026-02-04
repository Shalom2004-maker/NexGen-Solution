<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    * {
        box-sizing: border-box;
        font-family: "Sora", sans-serif;
    }

    body {
        background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
        color: #1f2937;
        min-height: 100vh;
        padding: 2rem 1rem;
    }

    .dashboard-shell {
        max-width: 900px;
        margin: 0 auto;
        background: radial-gradient(1200px 400px at 20% -10%, rgba(30, 64, 175, 0.12), transparent 60%),
            radial-gradient(800px 300px at 90% 10%, rgba(14, 116, 144, 0.12), transparent 60%);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
    }

    .card-panel {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1.5rem;
    }

    .form-control,
    .form-select {
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 12px;
        padding: 0.75rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #1d4ed8;
        box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.15);
    }

    .btn-primary {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        border: none;
        border-radius: 999px;
        font-weight: 600;
        padding: 0.6rem 1.2rem;
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
    }

    .btn-secondary {
        border-radius: 999px;
    }
    </style>
</head>

<body>
    <div class="dashboard-shell">
        <div class="card-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Create User</h3>
                <a href="admin_user_view.php" class="btn btn-secondary">View All Users</a>
            </div>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input name="name" class="form-control" placeholder="Enter Name"
                    value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
                <input name="email" type="email" class="form-control mt-2" placeholder="Enter Email"
                    value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                <input name="pass" type="password" class="form-control mt-2" placeholder="Put Password" required>
                <select name="role" class="form-select mt-2" required>
                    <?php while ($r = $roles->fetch_assoc()) { ?>
                    <option value="<?= $r["id"] ?>" <?= (isset($role) && $role == $r["id"]) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r["role_name"]) ?></option>
                    <?php } ?>
                </select>
                <button type="submit" class="btn btn-primary mt-3">Create</button>
            </form>
        </div>
    </div>
</body>

</html>
