<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'connexion.php';
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    if (empty($username) || empty($email) || empty($password)) {
        die("Please fill all fields.");
    }

    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $stmt->execute([
        ':username' => $username,
        ':email' => $email
    ]);

    if ($stmt->rowCount() > 0) {
        die("Username or email already exists.");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashed_password
    ]);

    // Redirect to login or success page
    header("Location: login.php");
    exit;
}
?>
<?php
require_once 'connexion.php'; // assuming $conn is created here
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>sign up - GLOBAL NOVELS</title>
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        body {
            padding-top: 100px;
        }

        footer {
            position: absolute;
            bottom: 0%;
            width: 100%;
        }

        .transition {
            transition: all 0.3s ease;
            border-width: 2px;
        }

        .transition:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .btn-primary:hover {
            background-color: #0b5ed7 !important;
            border-color: #0b5ed7 !important;
        }

        .text-gradient {
            background: linear-gradient(90deg, #ff7e5f, #feb47b);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h1.display-4 {
            font-size: 2.5rem;
            letter-spacing: 1.5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        main {
            height: 800px;
            margin-top: -12px;
        }
    </style>
</head>

<body>
<?php include "header.php"; ?>

    <main class="bg-dark">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <h2 class="mb-4 text-center">Create an Account</h2>
                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required />
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required />
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required />
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Re-enter your password" required />
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                    </form>
                    
                    <p class="text-center mt-3" style="color: azure;">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="bg-dark text-white py-4 fixed-bottom">
        <div class="container d-flex justify-content-between align-items-center">
            <p class="mb-0">&copy; 2025 MyWebNovel. All rights reserved.</p>
            <nav>
                <ul class="nav">
                    <li class="nav-item">
                        <a href="about.html" class="nav-link text-white">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a href="about.html" class="nav-link text-white">Terms of Service</a>
                    </li>
                    <li class="nav-item">
                        <a href="about.html" class="nav-link text-white">Privacy Policy</a>
                    </li>
                </ul>
            </nav>
        </div>
    </footer>

    <script src="/js/bootstrap.bundle.min.js"></script>
</body>

</html>