<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
include "../includes/header.php";
require_once __DIR__ . "/../includes/logger.php";

$error = '';
$success = '';

// ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

if ($_POST) {
    // CSRF check and populate variables
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $error = 'Invalid request (CSRF).';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $pass = $_POST['pass'] ?? '';
        $role = isset($_POST['role']) ? (int)$_POST['role'] : 0;

        // Basic validation
        if ($name === '' || strlen($name) < 2) {
            $error = 'Enter a valid name.';
        } elseif (!$email) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($role <= 0) {
            $error = 'Select a valid role.';
        }

        // Check role exists
        if (empty($error)) {
            $rstmt = $conn->prepare("SELECT id FROM roles WHERE id = ?");
            $rstmt->bind_param('i', $role);
            $rstmt->execute();
            $rres = $rstmt->get_result();
            if ($rres->num_rows !== 1) {
                $error = 'Selected role does not exist.';
            }
            $rstmt->close();
        }

        // Check duplicate email
        if (empty($error)) {
            $cstmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $cstmt->bind_param('s', $email);
            $cstmt->execute();
            $cres = $cstmt->get_result();
            if ($cres->num_rows > 0) {
                $error = 'A user with that email already exists.';
            }
            $cstmt->close();
        }

        // Insert user
        if (empty($error)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users(full_name,email,password_hash,role_id) VALUES(?,?,?,?)");
            $stmt->bind_param("sssi", $name, $email, $hash, $role);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                if (function_exists('audit_log')) {
                    audit_log('create_user', "Created user {$email}", $_SESSION['uid'] ?? null);
                }
                // Redirect to view after successful creation
                header('Location: admin_user_view.php');
                exit();
            } else {
                $error = 'Failed to create user.';
                if (function_exists('audit_log')) {
                    audit_log('create_user_failed', "Failed to create user {$email}", $_SESSION['uid'] ?? null);
                }
            }
            $stmt->close();
        }
    }
}


$roles = $conn->query("SELECT * FROM roles");
?>

<!DOCTYPE html>
<html>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3 bg-light p-3 rounded">
                <h5>Navigation</h5>
                <a href="admin_user.php" class="d-block mb-2">Create User</a>
                <a href="admin_user_view.php" class="d-block mb-2">View Users</a>
                <a href="tasks.php" class="d-block mb-2">Tasks</a>
                <a href="projects.php" class="d-block mb-2">Projects</a>
                <hr>
                <a href="../public/logout.php" class="d-block text-danger">Logout</a>
            </div>
            <div class="col-md-7 offset-md-1">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Create User</h3>
                    <a href="admin_user_view.php" class="btn btn-secondary">View All Users</a>
                </div>
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
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
        </div>
    </div>
</body>

</html>
<?php include "../includes/footer.php"; ?>