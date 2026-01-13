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

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=lock" />

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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
        min-height: 100vh;
    }

    .login-box {
        height: 90vh;
        margin-top: 2rem;
    }

    h4 {
        font-weight: bold;
        font-family: "Osward", sans-serif;
        font-size: 1.5rem;
    }

    .card-header img {
        margin-bottom: 0.5rem;
    }

    button.btn {
        background-color: #337ccfe2;
        color: white;
        font-weight: bold;
    }

    button.btn:hover {
        background-color: #337ccfe2;
        color: white;
        font-weight: bold;
    }

    a.nav-link {
        text-decoration: none;
        color: #337ccfe2;
    }

    a.nav-link:hover {
        text-decoration: none;
        color: #337ccfe2;
    }
    </style>
</head>

<body>
    <div class="container login-box">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg">
                    <div class="card-header bg-light text-dark text-center">
                        <span style="vertical-align: middle; font-size: 40px; color: lightslategray;">
                            <img src="/assets/nexgenlogo.png" alt="" srcset="">
                        </span>
                        <h4>NexGen Solution</h4>
                        <p>Sign in to your account to continue</p>
                    </div>
                    <div class="card-body">

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <label for="email">Email: </label>
                            <div class="input-group mb-3">
                                <span class="input-group-text" id="inputGroupPrepend">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960"
                                        width="20px" fill="lightslategray">
                                        <path
                                            d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480v58q0 59-40.5 100.5T740-280q-35 0-66-15t-52-43q-29 29-65.5 43.5T480-280q-83 0-141.5-58.5T280-480q0-83 58.5-141.5T480-680q83 0 141.5 58.5T680-480v58q0 26 17 44t43 18q26 0 43-18t17-44v-58q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93h200v80H480Zm0-280q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z" />
                                    </svg>
                                </span>
                                <input type="text" class="form-control" id="validationCustomUsername" name="email"
                                    placeholder="Email" aria-describedby="inputGroupPrepend" required>
                            </div>

                            <label>Password</label>
                            <div class="input-group mb-3">

                                <span class="input-group-text" id="inputGroupPrepend"><svg
                                        xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960"
                                        width="20px" fill="lightslategray">
                                        <path
                                            d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm0-80h480v-400H240v400Zm240-120q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80ZM240-160v-400 400Z" />
                                    </svg></span>
                                <input type="password" class="form-control" id="validationCustomUsername"
                                    name="password" placeholder="Password" aria-describedby="inputGroupPrepend"
                                    required>
                            </div>

                            <button class="btn w-100 mb-3 mt-3">Login</button>
                            <br>
                            <a class="nav-link text-center mb-3" href="index.php">Back to Home</a>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>