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
body,
div.container-fluid {
    background-color: #ececece8;
    padding-top: 0;
    right: 0;
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

<body class="bg-light">
    <div class="container-fluid">
        <div class="row d-flex">
            <?php include 'admin_siderbar.php'; ?>
            <div class="col-md-9 mb-2 p-4 ms-auto" style="margin-left: 25vw;">
                <h3 style="padding-left: 2.3rem;">HR Dashboard</h3>
                <p style="margin-top: .7rem; padding-left: 2.3rem; color: lightslategray"> Manage personnel, approvals,
                    and payroll.</p>
                <div class="d-md-flex justify-content-md-end me-4">
                    <a class="btn btn-outline-primary bi bi-plus-circle btn-sm" href=" admin_create_employee.php">
                        &nbsp;
                        Add
                        Employee</a>
                </div>
                <div class="row d-flex gap-4 justify-content-center pt-4">
                    <div class="col-md-2 bg-light rounded text-start shadow">
                        <h6>Total Employees <span class="bi bi-people-fill"
                                style="margin-left: 1rem; color: #337ccfe2;"></span></h6>
                        <?php
                        include "../includes/db.php";
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM employees");
                        if ($stmt) {
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $count = (int)($count ?? 0);
                            echo "<h4 style=\"margin-left: .5rem;\"><b>{$count}</b></h4>";
                            if ($count > 0) {
                                echo "<p style=\"margin-top: .7rem; font-size: 14px; margin-left: .5rem;\">Active staff members</p>";
                            } else {
                                echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">No employees yet</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">DB error</p>";
                        }
                        ?>
                    </div>
                    <div class="col-md-2 bg-light rounded text-start shadow">
                        <h6>Completed Tasks <span class="bi bi-ui-checks"
                                style="margin-left: .5rem; color: #00a938f3;"></span></h6>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = ?");
                        if ($stmt) {
                            $status = 'done';
                            $stmt->bind_param('is', $id, $status);
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $count = (int)($count ?? 0);
                            echo "<h4 style=\"margin-left: .5rem;\"><b>{$count}</b></h4>";
                            if ($count >= 1) {
                                echo "<p style=\"margin-top: .7rem;  font-size: 14px;  margin-left: .5rem;\">Requests needing approval</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">DB error</p>";
                        }
                        ?>
                    </div>
                    <div class="col-md-2 bg-light rounded text-start shadow">
                        <h6>Pending Leaves<span class="bi bi-suitcase-lg-fill"
                                style="margin-left: .7rem; color: #bc00e6f3;"></span>
                        </h6>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = ?");
                        if ($stmt) {
                            $stmt->bind_param('i', $id);
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $count = (int)($count ?? 0);
                            echo "<h4  style=\"margin-left: .5rem;\"><b>{$count}</b></h4>";
                            if ($count >= 1) {
                                echo "<p style=\"margin-top: .7rem;  font-size: 14px;  margin-left: .5rem;\">Submitted Leave requests</p>";
                            } else {
                                echo "<p style=\"margin-top: .7rem;  font-size: 14px;  margin-left: .5rem;\">No leave requests yet</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">DB error</p>";
                        }
                        ?>
                    </div>
                    <div class="col-md-2 bg-light rounded text-start shadow">
                        <h6>Latest Salaries <span class="bi bi-coin" style="margin-left: 1.3rem; color: orange;"></span>
                        </h6>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) AS Latest_Salary FROM salary_slips;");
                        if ($stmt) {
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $count = (int)($count ?? 0);
                            echo "<h4  style=\"margin-left: .5rem;\"><b>$" . "{$count}</b></h4>";
                            if ($count >= 1) {
                                echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">Salary credited</p>";
                            } else {
                                echo "<p style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">No salary data</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p  style=\"margin-top: .7rem; font-size: 14px;  margin-left: .5rem;\">DB error</p>";
                        }
                        ?>
                    </div>
                    <div class="col-md-9 mb-2 p-4 d-flex gap-3 justify-content-center pt-4">
                        <div class="col-md-8 rounded bg-light p-3 shadow border">
                            <h4 class="mt-2 ml-2">Recent Tasks</h4>
                            <?php
                                $stmt = $conn->prepare("SELECT title, status, created_at FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 5");
                                if ($stmt) {
                                    $stmt->bind_param('i', $id);
                                    $stmt->execute();
                                    $stmt->bind_result($title, $status, $created_at);
                                    echo "<ul class=\"list-group list-group-flush shadow rounded mt-5\">";
                                    while ($stmt->fetch()) {
                                        $status_badge = '';
                                        if ($status === 'in_progress') {
                                            $status_badge = '<span class="badge bg-warning text-dark shadow">In Progress</span>';
                                        } elseif ($status === 'done') {
                                            $status_badge = '<span class="badge bg-success shadow">Completed</span>';
                                        } else {
                                            $status_badge = '<span class="badge bg-secondary shadow">To Do</span>';
                                        }
                                        echo "<li class=\"list-group-item d-flex justify-content-between align-items-center\">
                                                <div>
                                                    <strong>" . htmlspecialchars($title) . "</strong><br>
                                                    <small>Due: " . htmlspecialchars($created_at ?? 'Not set') . "</small>
                                                </div>
                                                <div>" . $status_badge . "</div>
                                              </li>";
                                    }
                                    echo "</ul>";
                                    $stmt->close();
                                } else {
                                    echo "<p>DB error</p>";
                                }
                                ?>
                        </div>
                        <div class="col-md-8 rounded bg-light p-3 shadow border">
                            <h4 class="mt-2 ml-2">My Leave Requests</h4>
                            <?php 
                            $stmt = $conn->prepare("SELECT reason, start_date, end_date, status FROM leave_requests WHERE employee_id = ? ORDER BY applied_at DESC LIMIT 5");
                            if ($stmt) {
                                $stmt->bind_param('i', $id);
                                $stmt->execute();
                                $stmt->bind_result($reason, $start_date, $end_date, $status);
                                echo "<ul class=\"list-group list-group-flush shadow rounded mt-5\">";
                                while ($stmt->fetch()) {
                                    $status_badge = '';
                                    if ($status === 'pending') {
                                        $status_badge = '<span class="badge bg-warning text-dark shadow">Pending</span>';
                                    } elseif ($status === 'approved') {
                                        $status_badge = '<span class="badge bg-success shadow">Approved</span>';
                                    } else {
                                        $status_badge = '<span class="badge bg-danger shadow">Rejected</span>';
                                    }
                                    echo "<li class=\"list-group-item d-flex justify-content-between align-items-center\">
                                            <div>
                                                <strong>" . htmlspecialchars($reason) . "</strong><br>
                                                <small>" . htmlspecialchars($start_date ?? 'Not set') . " to " . htmlspecialchars($end_date ?? 'Not set') . "</small>
                                            </div>
                                            <div>" . $status_badge . "</div>
                                          </li>";
                                }
                                echo "</ul>";
                                $stmt->close();
                            } else {
                                echo "<p>DB error</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>