<?php
session_start();
include "../includes/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST["email"]);
    $password = (string)($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Email and password required";
    } else {

        $sql = "SELECT users.id, users.full_name, users.password_hash, users.password, users.status, users.profile_photo, roles.role_name 
                FROM users 
                JOIN roles ON users.role_id = roles.id 
                WHERE users.email = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (isset($user["status"]) && $user["status"] !== "active") {
                    $error = "Account is disabled. Please contact administrator.";
                } else {
                    $stored_hash = trim((string) ($user["password_hash"] ?? ""));
                    $stored_password = (string) ($user["password"] ?? "");
                    $is_valid_password = false;
                    $hash_is_valid = false;
                    $needs_rehash = false;
                    $legacy_upgrade_required = false;
                    $clear_plaintext_password = $stored_password !== "";

                    if ($stored_hash !== "") {
                        $password_info = password_get_info($stored_hash);
                        if (!empty($password_info["algo"])) {
                            $hash_is_valid = true;
                            $is_valid_password = password_verify($password, $stored_hash);
                            if ($is_valid_password) {
                                $needs_rehash = password_needs_rehash($stored_hash, PASSWORD_DEFAULT);
                            }
                        }
                    }

                    if (!$hash_is_valid && $stored_password !== "") {
                        $legacy_upgrade_required = hash_equals($stored_password, $password);
                        $is_valid_password = $legacy_upgrade_required;
                    }

                    if ($is_valid_password) {
                        if ($legacy_upgrade_required || $needs_rehash || $clear_plaintext_password) {
                            $new_hash = $hash_is_valid && !$needs_rehash && !$legacy_upgrade_required
                                ? $stored_hash
                                : password_hash($password, PASSWORD_DEFAULT);
                            $upgrade_stmt = $conn->prepare("UPDATE users SET password_hash = ?, password = '' WHERE id = ?");
                            if (!$upgrade_stmt) {
                                $error = "Unable to upgrade account security. Please contact administrator.";
                            } else {
                                $user_id = (int)$user["id"];
                                $upgrade_stmt->bind_param("si", $new_hash, $user_id);
                                if (!$upgrade_stmt->execute()) {
                                    $error = "Unable to upgrade account security. Please contact administrator.";
                                }
                                $upgrade_stmt->close();
                            }
                        }
                    }

                    if ($is_valid_password && $error === "") {
                        session_regenerate_id(true);

                        $_SESSION["uid"] = $user["id"];
                        $_SESSION["name"]    = $user["full_name"];
                        $_SESSION["role"]    = $user["role_name"];
                        $_SESSION["profile_photo"] = $user["profile_photo"] ?? null;

                        if ($user["role_name"] === "HR") {
                            header("Location: ../dashboard/hr.php");
                        } elseif ($user["role_name"] === "Admin") {
                            header("Location: ../dashboard/admin_dashboard.php");
                        } elseif ($user["role_name"] === "ProjectLeader") {
                            header("Location: ../dashboard/leader.php");
                        } elseif ($user["role_name"] === "Guest") {
                            header("Location: index.php");
                        } else {
                            header("Location: ../dashboard/employee.php");
                        }
                        $stmt->close();
                        exit();
                    } else {
                        $error = "Wrong password";
                    }
                }
            } else {
                $error = "Account not found";
            }
            $stmt->close();
        } else {
            $error = "Database error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Solution Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@400;600;700&display=swap"
        rel="stylesheet">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/colors.css" rel="stylesheet">
    <link href="../css/theme.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">

    <script src="../js/jquery.js"></script>
    <script src="../js/validate.js"></script>
    <script src="../js/future-ui.js" defer></script>
</head>

<body class="future-page future-login" data-theme="dark">
    <div class="future-grid" aria-hidden="true"></div>
    <div class="future-orb future-orb-a" aria-hidden="true"></div>
    <div class="future-orb future-orb-b" aria-hidden="true"></div>
    <div class="future-orb future-orb-c" aria-hidden="true"></div>

    <div class="theme-switcher" role="group" aria-label="Theme toggle">
        <button class="theme-chip pressable" type="button" data-theme-toggle data-icon-light="bi-sun-fill"
            data-icon-dark="bi-moon-fill" aria-label="Toggle theme" aria-pressed="true">
            <i class="bi bi-moon-fill" aria-hidden="true"></i>
        </button>
    </div>

    <div class="login-container">
        <div class="login-card tilt-surface" data-tilt="6">
            <div class="card-header">
                <img src="../assets/logos/nexgen-brand-logo.png" alt="NexGen Solution Logo">
                <h4>NexGen Solution</h4>
                <p>Sign in to your account</p>
            </div>

            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form method="POST" id="login-form" action="#" onsubmit="return validateForm()" class="w-100">
                    <div class="form-field">
                        <label for="emailInput" class="form-label mb-2">Email Address</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="text" class="form-control" id="emailInput" name="email"
                                data-validation="required email" placeholder="Enter your email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div id="email_error" class="text-danger validation-error"></div>
                    </div>

                    <div class="form-field">
                        <label for="passwordInput" class="form-label mb-2">Password</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="passwordInput" name="password"
                                data-validation="required min-length max-length" data-min-length="7"
                                data-max-length="128" placeholder="Enter your password">
                        </div>
                        <div id="password_error" class="text-danger validation-error"></div>
                    </div>

                    <button type="submit" class="btn-login pressable mt-3" data-tilt="8">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>

                    <a href="forgot_password.php" class="home-link">
                        <i class="bi bi-question-circle"></i> Forgot password?
                    </a>

                    <a href="index.php" class="home-link">
                        <i class="bi bi-arrow-left"></i> Back to Home
                    </a>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>
