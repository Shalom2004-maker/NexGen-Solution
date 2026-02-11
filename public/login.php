<?php
session_start();
include "../includes/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if ($email === "" || $password === "") {
        $error = "Email and password required";
    } else {

        $sql = "SELECT users.id, users.full_name, users.password_hash, users.status, users.profile_photo, roles.role_name 
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

                // Check if account is active
                if (isset($user["status"]) && $user["status"] !== "active") {
                    $error = "Account is disabled. Please contact administrator.";
                } else {
                    // Trim password hash to remove any newline characters
                    $password_hash = trim($user["password_hash"]);

                    if (password_verify($password, $password_hash)) {

                        $_SESSION["uid"] = $user["id"];
                        $_SESSION["name"]    = $user["full_name"];
                        $_SESSION["role"]    = $user["role_name"];
                        $_SESSION["profile_photo"] = $user["profile_photo"] ?? null;

                        if ($user["role_name"] === "Admin") {
                            header("Location: ../dashboard/admin_dashboard.php");
                        } elseif ($user["role_name"] === "HR") {
                            header("Location: ../dashboard/hr.php");
                        } elseif ($user["role_name"] === "ProjectLeader") {
                            header("Location: ../dashboard/leader.php");
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
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    <!-- Local Bootstrap CSS Link -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.js"></script>
    <script src="../js/jquery.js"></script>
    <script src="../js/validate.js"></script>

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Inter", sans-serif;

    }

    html,
    body {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        padding-top: .7rem;
        padding-bottom: .7rem;
    }

    .login-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .card-header {
        background-color: #f8f9fa;
        padding: .7rem;
        text-align: center;
        border-bottom: 1px solid #e0e0e0;
    }

    .card-header img {
        max-width: 120px;
        width: 40%;
        height: auto;
        margin-bottom: 0.3rem;
        border-radius: 8px;
        object-fit: contain;
    }

    .card-header h4 {
        font-weight: bold;
        color: #333;
        margin-bottom: 0.3rem;
        font-size: 1.5rem;
    }

    .card-header p {
        color: lightslategray;
        margin: 0;
        font-size: 0.90rem;
    }

    .card-body {
        padding: 2rem 1.5rem;
        width: 40vw;
    }

    .alert {
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.3rem;
        font-size: 0.95rem;
    }

    .form-control {
        border: 1px solid #d4d4d4;
        height: 40px;
        border-radius: 6px;
        font-size: 0.95rem;
        padding: 0.5rem 0.7rem;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .form-field {
        margin-bottom: 1rem;
    }

    .form-field .input-group {
        margin-bottom: 0;
    }

    .validation-error {
        display: none;
        margin-top: 0.4rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #dc3545;
        line-height: 1.2;
    }

    .input-group .input-group-text {
        transition: all 0.2s ease;
    }

    .input-group:focus-within .input-group-text {
        color: #1d4ed8;
        border-color: #667eea;
        background-color: #eef3ff;
    }

    .input-group.has-error .input-group-text {
        color: #dc3545;
        border-color: #dc3545;
        background-color: #fff1f1;
    }

    .form-control.input-error {
        border-color: #dc3545 !important;
        background-color: #fff8f8;
    }

    .form-control.input-error:focus {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.18) !important;
    }

    .input-group-text {
        background-color: #f3f3f3;
        border: 1px solid #d4d4d4;
        color: lightslategray;
    }

    .btn-login {
        background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
        color: white;
        font-weight: bold;
        padding: 0.5rem;
        border: none;
        border-radius: 6px;
        width: 100%;
        margin: 1rem 0;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .home-link {
        display: block;
        text-align: center;
        color: #667eea;
        text-decoration: none;
        margin-top: .5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .home-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    @media (max-width: 576px) {
        .login-container {
            padding: 0.5rem;
        }

        .card-header {
            padding: 1rem 0.75rem;
            width: 100%;
        }

        .card-header img {
            width: 28%;
            height: auto;
        }

        .card-header h4 {
            font-size: 1.25rem;
        }

        .card-body {
            padding: 1rem 0.75rem;
            width: 100%;
        }

        .form-control,
        .input-group {
            margin-bottom: 0.9rem;
        }

        .btn-login {
            padding: 0.65rem;
            font-size: 0.95rem;
        }
    }

    @media (max-width: 480px) {

        .card-header,
        .card-body {
            width: 100%;
        }

        .card-header h4 {
            font-size: 1rem;
        }

        .card-header p {
            font-size: 0.85rem;
        }

        .form-label {
            font-size: 0.9rem;
        }

        .form-control,
        .input-group {
            font-size: 0.9rem;
        }
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <img src="../assets/logos/nexgen-brand-logo.png" alt=" NexGen Solution Logo">
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

                <div class="col-lg-7 col-md-10 col-12 w-100">
                    <form method="POST" id="login-form" action="#" onsubmit="return validateForm()" class="w-100">
                        <div class="form-field">
                            <label for="emailInput" class="form-label">Email Address</label>
                            <div class="input-group">
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
                            <label for="passwordInput" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="passwordInput" name="password"
                                    data-validation="required min-length max-length" data-min-length="7"
                                    data-max-length="128" placeholder="Enter your password">
                            </div>
                            <div id="password_error" class="text-danger validation-error"></div>
                        </div>

                        <button type="submit" class="btn-login">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
