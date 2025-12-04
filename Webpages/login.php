<?php
    include "db.php"; 

    $error = '';
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username === "taftlab_admin@dlsu.edu.ph" && $password === "admin123") {
            header("Location: admin-homepage.php");
            exit(); 
        } else {
            $error = "Invalid username or password";
        }
        
        $sql_email = "SELECT * FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql_email);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        
        if($result->num_rows === 1) //check if email address exists
        {
            $user = $result->fetch_assoc(); //fetch_assoc gets actual row data related to the user with the specified email

            if($password === $user['user_password']) //if password is the same as the value of the user's password record
            {
                header("Location: homepage.php");
                exit();
            }
            else
            {
                $error = "Invalid password";
            }
        }
        else
        {
            $error = "Email not found.";
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
                <label for="email">Email Address</label>
                <input type="text" id="email" name="email" placeholder="Enter your DLSU email here" required>
                
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