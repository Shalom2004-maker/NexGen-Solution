<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<!-- Google Fonts Link -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">

<!-- Bootstrap CSS Link -->
<link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<!-- CSS -->
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Osward", sans-serif;
}

html,
body {
    background-color: #ececece8;
}

.col-md-3 {
    min-height: 100vh;
    background-color: #ececece8;
    color: black;
    box-shadow: inset 0 0 10px #aaaaaa;
}

h3,
h4 {
    font-weight: bold;
}

a.d-block,
h5 {
    text-decoration: none;
    color: lightslategray;
    padding-top: .7rem;
    text-indent: 1.5rem;
    padding-bottom: .7rem;
}

a:hover {
    color: white;
    background-color: #337ccfe2;
    border-radius: 5px;
}

.col-md-9 {
    background-color: #f5f5f5d2;
}

.col-md-2 {
    width: 15vw;
    border: 1px solid #d4d4d4;
}

h6 {
    padding-top: .5rem;
    margin-left: .5rem;
}

p {
    color: lightslategray;
}

button {
    margin-top: 1.5rem;
}
</style>
</head>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3 bg-light p-3 position-fixed">
                <h3 style="margin-top: .5rem; padding-left: 1.5rem;">NexGen Solution</h3>
                <p style="margin-top: .5rem; padding-left: 1.5rem;">Employee Management</p>
                <hr>
                <h5>Employee</h5>
                <a href="employee.php" class="d-block mb-2 bi bi-columns-gap"> &nbsp;&nbsp; Dashboard</a>
                <a href="tasks.php" class="d-block mb-2 bi bi-suitcase-lg"> &nbsp;&nbsp; My Tasks</a>
                <a href="leave.php" class="d-block mb-2 bi bi-file-text"> &nbsp;&nbsp; Request Leave</a>
                <a href="salary.php" class="d-block mb-2 bi bi-coin"> &nbsp;&nbsp; My Salary</a>
                <hr>

                <div class="d-flex justify-content-center align-items-center mt-4">
                    <span
                        style="width: 50px; height: 50px; background-color: #337ccfe2; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; color: white; font-weight: bold;">
                        <?= substr($_SESSION['name'] ?? 'User', 0, 1) ?>
                    </span> &nbsp;&nbsp; &nbsp;&nbsp;
                    <span class="me-3"><b><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></b><br>
                        <font style="font-size: 13px; color: lightslategray;">
                            <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
                        </font>
                    </span>
                </div>
                <center>
                    <a href="../public/logout.php" type="submit"
                        class="btn btn-outline-danger w-75 text-align-start bi bi-box-arrow-right mt-3">&nbsp;
                        &nbsp; Logout
                    </a>

                </center>
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