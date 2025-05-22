<?php
session_start();
require_once 'connexion.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (!empty($username) && !empty($password)) {
        $ban_stmt = $conn->prepare('SELECT reason FROM banned_users WHERE username = ?');
        $ban_stmt->execute([$username]);
        $ban = $ban_stmt->fetch(PDO::FETCH_ASSOC);
        if ($ban) {
            $ban_reason = $ban['reason'] ?: 'Violation of site rules';
            $error = 'Your account has been banned. Reason: ' . htmlspecialchars($ban_reason) . '<br>Contact: <a href="mailto:global0novels@gmail.com">global0novels@gmail.com</a>';
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $id = $user['id'];
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit;
                } elseif ($user['role'] === 'author') {
                    header("Location: author_dashboard.php");
                    exit;
                } else {
                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = 'Incorrect username or password.';
            }
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GLOBAL NOVELS</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" >
    <link rel="stylesheet" href="login.css">
    <style>

        footer {
            position: absolute;
            bottom: 0%;
            width: 100%;
        }

        body {
            padding-top: 70px;
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

        @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');

        .main-container {
            min-height: 85vh;
            min-width: 450px;
            font-family: 'calibri';
        }

        .centered-flex {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            width: 400px;
            height: 480px;
            display: grid;
            position: relative;
        }

        .icon {
            position: absolute;
            width: 85px;
            font-size: 50px;
            display: grid;
            height: 85px;
            place-content: center;
            border: 1px solid #2a2a2a;
            z-index: 1;
            justify-self: center;
            border-radius: 50%;
            background: #0e0e0e;
        }

        .fa {
            color: #a2a2a2;
        }

        form {
            flex-direction: column;
            padding: 25px 25px 10px;
            height: 440px;
            border-radius: 30px;
            background: rgba(19, 19, 19, 0.736);
            border: 1px solid rgba(255, 255, 255, 0.097);
            position: absolute;
            width: 100%;
            bottom: 0;
        }

        .title {
            position: relative;
            margin: 40px 0;
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        .msg {
            color: #fa2929;
            position: absolute;
            top: 25%;
        }

        .field {
            display: flex;
            position: relative;
            width: 100%;
        }

        .field .fa {
            position: absolute;
            font-size: 14px;
            right: 10px;
            bottom: 10px;
        }

        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px rgb(14 14 14) inset;
        }

        form input {
            display: block;
            outline: none;
            width: 100%;
            border: none;
            font-size: 16px;
            color: #d2d2d2;
            margin: 25px 0 5px;
            caret-color: #cccccc;
            background: transparent;
            padding: 10px 25px 3px 0;
            border-bottom: 1px solid #404040;
        }

        .action {
            justify-content: space-between;
            width: 100%;
            font-size: 14px;
        }

        .action label {
            cursor: pointer;
            color: #7d7d7d;
        }

        .action input {
            width: auto;
            margin: 0 8px 0 0;
            cursor: pointer;
        }

        a {
            text-decoration: none;
            color: #9b9b9b;
        }

        .btn-container {
            padding: 20px;
            transition: .2s linear;
        }

        #login-btn {
            padding: 5px 20px;
            border: none;
            background: rgb(25, 62, 97);
            color: white;
            font-weight: 600;
            font-size: 16px;
            border-radius: 15px;
            transition: .3s;
            margin: 25px 0;
        }

        #login-btn:hover {
            cursor: pointer;
        }

        .signup {
            color: rgb(70, 70, 70);
            margin-top: 10px;
        }

        .shift-left {
            transform: translateX(-120%);
        }

        .shift-right {
            transform: translateX(120%);
        }

        .shift-top {
            transform: translateY(-150%);
        }

        .shift-bottom {
            transform: translateY(150%);
        }

        .no-shift {
            transform: translate(0%, 0%);
        }
    </style>
</head>
<body>
    <?php if (isset($error)): ?>
        <div class="container mt-4">
            <div class="alert alert-danger text-center" role="alert">
                <?php echo $error; ?>
            </div>
        </div>
    <?php endif; ?>

    <main class="bg-dark">
        <div class="main-container centered-flex">
            <div class="form-container">
                <div class="icon fa fa-user"></div>
                <form class="centered-flex" action="login.php" method="POST">
                    <div class="title">LOGIN</div>
                    <div class="msg"></div>
                    <div class="field">
                        <input type="text" name="username" placeholder="Username" id="uname" style="color: #d2d2d2;">
                        <span class="fa fa-user"></span>
                    </div>
                    <div class="field">
                        <input type="password" name="password" placeholder="Password" id="pass">
                        <span class="fa fa-lock"></span>
                    </div>
                    
                    <div class="btn-container">
                        <input type="submit" id="login-btn" value="Login">
                    </div>
                    <div class="signup">Don't have an Account? <a href="register.php">Sign up</a></div>
                </form>
            </div>
        </div>
    </main>
    <footer class="bg-dark text-white py-4 fixed-bottom">
        <div class="container d-flex justify-content-between align-items-center">
            <p class="mb-0">&copy; 2025 MyWebNovel. All rights reserved.</p>
            <nav>
                <ul class="nav">
                    <li class="nav-item"><a href="about.html" class="nav-link text-white">About Us</a></li>
                    <li class="nav-item"><a href="about.html" class="nav-link text-white">Terms of Service</a></li>
                    <li class="nav-item"><a href="about.html" class="nav-link text-white">Privacy Policy</a></li>
                </ul>
            </nav>
        </div>
    </footer>
    
    <script src="/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>
