<?php
  include "db.php";
  session_start();
  
  if (!isset($_SESSION['user_id'])) 
  {
      header("Location: login.php");
      exit();
  }

  if($_SERVER["REQUEST_METHOD"] == "POST")
  {
    $id = $_SESSION['user_id'];
    $oldpass = $_POST['current_password'] ?? '';
    $newpass = $_POST['new_password'] ?? '';
    $confirmpass = $_POST['confirm_newpassword'] ?? '';

    //query old password to compare
    $oldpass_sql = "SELECT user_password FROM user WHERE user_id = $id";
    $result = $conn->query($oldpass_sql);
    $opass = $result->fetch_assoc();

    $message = "";

    // verify hashed password 
    if (!password_verify($oldpass, $opass['user_password']))
    {
        $message = "<p style='color:red;'>Current password is incorrect. Please try again.</p>";
    }

    elseif ($newpass !== $confirmpass)
    {
        $message = "<p style='color:red;'>New password and confirmation do not match. Please try again.</p>";
    }
    
    else
    {
        // store new password as hash
        $newHash = password_hash($confirmpass, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET user_password = '$newHash' WHERE user_id = $id";
        mysqli_query($conn, $sql);
        $message = "<p style='color:green;'>Password changed successfully!</p>";  
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="login-signup.css">
</head>
    <body>
        <div class="signup"> 
            <div class="signup-leftside">
                <h2>Change User Password</h2>
                <form method="POST" action="change-password.php">
                    <label for="current_password">Current Password </label>
                    <input type="password" id="current_password" name="current_password" required>

                    <label for="new_password">New Password </label>
                    <input type="password" id="new_password" name="new_password" required>

                    <label for="confirm_newpassword">Confirm New Password </label>
                    <input type="password" id="confirm_newpassword" name="confirm_newpassword" required>

                    <button type="submit" class="top-btn">Change Password</button>
                </form>
         
                <form method="POST" action="user-profile.php">
                    <button type="submit" class="bottom-btn">Back</button>
                </form>
                    <?php 
                        if (!empty($message))       
                            echo $message 
                    ?>
            </div>

            <div class="signup-rightside">
                <img src="images/taftlab-logo.png" alt="TAFT LAB Logo">
                <h2>Every Lasallian's Gateway to<br>DLSU Computer Labs.</h2>
                <p>Book your workspace today — at DLSU.</p>
            </div>


        </div>
    </body>
</html>