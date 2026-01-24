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

        $sql = "SELECT users.id, users.full_name, users.password_hash, users.status, roles.role_name 
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

                        if ($user["role_name"] === "Admin") {
                            header("Location: ../dashboard/admin_user.php");
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
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Osward", sans-serif;
    }

    html,
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        width: 100%;
        max-width: 450px;
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .login-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        width: 30vw;
    }

    .card-header {
        background-color: #f8f9fa;
        padding: 1cqb 1rem;
        text-align: center;
        height: 200px;
        border-bottom: 1px solid #e0e0e0;
    }

    .card-header img {
        width: 120px;
        height: 90px;
        margin-bottom: 1rem;
        border-radius: 8px;
    }

    .card-header h4 {
        font-weight: bold;
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 1.8rem;
    }

    .card-header p {
        color: lightslategray;
        margin: 0;
        font-size: 0.95rem;
    }

    .card-body {
        padding: 2rem 1.5rem;
    }

    .alert {
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control {
        border: 1px solid #d4d4d4;
        height: 7vh;
        border-radius: 6px;
        font-size: 0.95rem;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .input-group {
        margin-bottom: 1.5rem;
    }

    .input-group-text {
        background-color: #f8f9fa;
        border: 1px solid #d4d4d4;
        color: lightslategray;
    }

    .btn-login {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: bold;
        padding: 0.7rem;
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
        margin-top: 1rem;
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
            padding: 1.5rem 1rem;
        }

        .card-header img {
            width: 60px;
            height: 60px;
        }

        .card-header h4 {
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1.5rem 1rem;
        }

        .form-control,
        .input-group {
            margin-bottom: 1rem;
        }

        .btn-login {
            padding: 0.65rem;
            font-size: 0.95rem;
        }
    }

    @media (max-width: 480px) {
        .card-header h4 {
            font-size: 1.3rem;
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

                <form method="POST" action="">
                    <label for="emailInput" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="emailInput" name="email"
                            placeholder="Enter your email" required>
                    </div>

                    <label for="passwordInput" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="passwordInput" name="password"
                            placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>

                    <a href="index.php" class="home-link">
                        <i class="bi bi-arrow-left"></i> Back to Home
                    </a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>