<?php
session_start();

// Redirect if user or admin is already logged in
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
} elseif (isset($_SESSION["admin"])) {
    header("Location: admin_dashboard.php");
    exit();
}

require_once "dbconnection.php"; // Database connection

// Display logout message if redirected from logout.php
$logoutMessage = "";
if (isset($_GET['message']) && $_GET['message'] == 'logged_out') {
    $logoutMessage = "You have successfully logged out.";
}

// Handle form submissions
$errorMessage = "";
$successMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // User login
    if (isset($_POST["login"])) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user"] = $user["id"];
            header("Location: index.php");
            exit();
        } else {
            $errorMessage = "Email or password is incorrect.";
        }
    }

    // Admin login
    elseif (isset($_POST["admin_login"])) {
        $sql = "SELECT * FROM admins WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);
        if ($admin && password_verify($password, $admin["password"])) {
            $_SESSION["admin"] = $admin["id"];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $errorMessage = "Email or password is incorrect.";
        }
    }

    // User Registration
    elseif (isset($_POST["register"])) {
        $fullname = $_POST["fullname"];
        $password_repeat = $_POST["repeat_password"];

        if ($password !== $password_repeat) {
            $errorMessage = "Passwords do not match.";
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $fullname, $email, $password_hashed);
            if (mysqli_stmt_execute($stmt)) {
                $successMessage = "Registration successful! You can now log in.";
            } else {
                $errorMessage = "Error during registration. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include Bootstrap CSS -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
    body {
        background: url("images/banner_1.jpg") no-repeat center center fixed;
        background-size: cover;
    }
    .card {
        margin-top: 50px;
        opacity: 0.95;
    }
    .form-control:focus {
        box-shadow: none;
    }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php if ($logoutMessage): ?>
                <div class="alert alert-success text-center mt-3"><?php echo $logoutMessage; ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger text-center mt-3"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            <?php if ($successMessage): ?>
                <div class="alert alert-success text-center mt-3"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            <div class="card shadow">
                <div class="card-body">
                    <!-- User Login Form -->
                    <form action="" method="POST" id="userLoginForm">
                        <h2 class="text-center mb-4">User Login</h2>
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Email" required/>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Password" required/>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                        <p class="text-center mt-3">
                            Don't have an account? <a href="#" id="switchToRegister">Register</a>
                        </p>
                        <p class="text-center">
                            Admin? <a href="#" id="switchToAdmin">Login as Admin</a>
                        </p>
                    </form>

                    <!-- Admin Login Form -->
                    <form action="" method="POST" id="adminLoginForm" style="display:none;">
                        <h2 class="text-center mb-4">Admin Login</h2>
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Email" required/>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Password" required/>
                        </div>
                        <button type="submit" name="admin_login" class="btn btn-primary w-100">Login as Admin</button>
                        <p class="text-center mt-3">
                            Go back to <a href="#" id="switchToUserLogin">User Login</a>
                        </p>
                    </form>

                    <!-- Register Form -->
                    <form action="" method="POST" id="registerForm" style="display:none;">
                        <h2 class="text-center mb-4">Register</h2>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="fullname" placeholder="Full Name" required/>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Email" required/>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Password" required/>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="repeat_password" placeholder="Repeat Password" required/>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                        <p class="text-center mt-3">
                            Already have an account? <a href="#" id="switchToUserLoginFromRegister">User Login</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle between forms
document.getElementById('switchToRegister').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('userLoginForm').style.display = 'none';
    document.getElementById('registerForm').style.display = 'block';
});
document.getElementById('switchToAdmin').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('userLoginForm').style.display = 'none';
    document.getElementById('adminLoginForm').style.display = 'block';
});
document.getElementById('switchToUserLogin').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('adminLoginForm').style.display = 'none';
    document.getElementById('userLoginForm').style.display = 'block';
});
document.getElementById('switchToUserLoginFromRegister').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('registerForm').style.display = 'none';
    document.getElementById('userLoginForm').style.display = 'block';
});
</script>
</body>
</html>