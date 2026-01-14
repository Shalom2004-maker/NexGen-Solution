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

<link
    href="https://fonts.googleapis.com/css2?family=Architects+Daughter&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Fira+Code:wght@300..700&family=Geist+Mono:wght@100..900&family=Geist:wght@100..900&family=IBM+Plex+Mono:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=IBM+Plex+Sans:ital,wght@0,100..700;1,100..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Lora:ital,wght@0,400..700;1,400..700&family=Merriweather:ital,opsz,wght@0,18..144,300..900;1,18..144,300..900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Outfit:wght@100..900&family=Oxanium:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto+Mono:ital,wght@0,100..700;1,100..700&family=Roboto:ital,wght@0,100..900;1,100..900&family=Source+Code+Pro:ital,wght@0,200..900;1,200..900&family=Source+Serif+4:ital,opsz,wght@0,8..60,200..900;1,8..60,200..900&family=Space+Grotesk:wght@300..700&family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
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
    border-color: lightslategray;
}

h3,
h4 {
    font-weight: bold;
}

a.d-block,
h5 {
    text-decoration: none;
    color: lightslategray;
    padding-top: .5rem;
    text-indent: 1.5rem;
    padding-bottom: .5rem;
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

h5 {
    font-size: 17px;
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
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 bg-light p-3 border-end">
                <div class="d-block position-fixed border-bottom bg-light" style="width: 23.4vw;">
                    <h3 style="margin-top: .5rem; padding-left: 1.5rem;">NexGen Solution</h3>
                    <p style="margin-top: .5rem; padding-left: 1.5rem;">Employee Management</p>
                </div>

                <div class="d-block position-relative"
                    style="margin-top: 7rem; margin-bottom: 8.5rem; overflow-y: auto; height: calc(100vh - 17rem);">
                    <h5>Employee</h5>
                    <a href="employee.php" class="d-block mb-2 bi bi-columns-gap"> &nbsp;&nbsp; Dashboard</a>
                    <a href="tasks.php" class="d-block mb-2 bi bi-suitcase-lg"> &nbsp;&nbsp; My Tasks</a>
                    <a href="leave.php" class="d-block mb-2 bi bi-file-text"> &nbsp;&nbsp; Request Leave</a>
                    <a href="salary.php" class="d-block mb-2 bi bi-coin"> &nbsp;&nbsp; My Salary</a>
                    <h5>Project Leader</h5>
                    <a href="leader.php" class="d-block mb-2 bi bi-columns-gap"> &nbsp;&nbsp; Overview</a>
                    <a href="tasks.php" class="d-block mb-2 bi bi-suitcase-lg"> &nbsp;&nbsp; Tasks Assignment</a>
                    <a href="leave_view.php" class="d-block mb-2 bi bi-file-text"> &nbsp;&nbsp; Leave Review</a>
                    <h5>HR</h5>
                    <a href="hr.php" class="d-block mb-2 bi bi-people"> &nbsp;&nbsp; Employees</a>
                    <a href="leave_view.php" class="d-block mb-2 bi bi-file-text"> &nbsp;&nbsp; Leave Approvals</a>
                    <a href="leader_payroll.php" class="d-block mb-2 bi bi-currency-dollar"> &nbsp;&nbsp; Process
                        Payroll</a>
                    <a href="inquiries_view.php" class="d-block mb-2 bi bi-person-circle"> &nbsp;&nbsp; Inquiries</a>

                    <h5>Admin</h5>
                    <a href="admin_user.php" class="d-block mb-2 bi bi-people"> &nbsp;&nbsp; System Users</a>

                </div>
                <div class="d-block position-fixed bg-light border-top" style="bottom: 0; width: 23.4vw;">
                    <div class=" d-flex justify-content-center align-items-center mt-3">
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
                            class="btn btn-outline-danger mb-3 w-75 text-align-start bi bi-box-arrow-right mt-3">&nbsp;
                            &nbsp; Logout
                        </a>

                    </center>
                </div>
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