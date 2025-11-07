<?php
    $error = '';
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username === "taftlab_admin@dlsu.edu.ph" && $password === "admin123") {
            header("Location: createAccount.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAFT LAB | Login</title>
    <link rel="stylesheet" href="login-signup.css">
</head>

<body>
    <div class="login">

        <div class="login-leftside">
            <img src="images/taftlab-logo.png" alt="TAFT LAB Logo" class="login-logo">

            <form method="POST" action="login.php">
                <label for="username">Email Address</label>
                <input type="text" id="username" name="username" placeholder="Enter your DLSU email here" required>
                
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password here" required>
                
                <button type="submit" class="top-btn">Log In</button>
            </form>

            <form method="POST" action="createAccount.php">
                <button type="submit" class="bottom-btn">Sign Up</button>
            </form>
        </div>

        <div class="login-rightside">
            <div class="hex-design"></div>
        </div>
    </div>
</body>
</html>