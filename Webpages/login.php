<?php
session_start();
include "db.php"; 

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check admin credentials first
    if ($username === "taftlab_admin@dlsu.edu.ph" && $password === "admin123") {
        $_SESSION['user_id'] = 901; // Admin ID
        $_SESSION['email'] = $username;
        $_SESSION['is_admin'] = true;
        header("Location: admin-homepage.php");
        exit(); 
    }
    
    // Check regular user credentials
    $sql_email = "SELECT * FROM user WHERE email = ?";
    $stmt = $conn->prepare($sql_email);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if($password === $user['user_password']) {
            // Store user info in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'] ?? '';
            $_SESSION['last_name'] = $user['last_name'] ?? '';
            $_SESSION['is_admin'] = false;
            
            header("Location: homepage.php");
            exit();
        }
        else {
            $error = "Invalid password";
        }
    }
    else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
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

            <?php if (!empty($error)): ?>
                <p style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

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