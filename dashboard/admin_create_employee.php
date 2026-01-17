<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <div class="col-md-7 offset-md-1">
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
            <select name="role" class="form-control mt-2" required>
                <?php while ($r = $roles->fetch_assoc()) { ?>
                <option value="<?= $r["id"] ?>" <?= (isset($role) && $role == $r["id"]) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r["role_name"]) ?></option>
                <?php } ?>
            </select>
            <button type="submit" class="btn btn-primary mt-3">Create</button>
        </form>
    </div>
</body>

</html>