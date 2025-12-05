<?php
    include "db.php";
    session_start();
    if (!isset($_SESSION['id'])) 
    {
        header("Location: login.php");
        exit();
    }

    $id = $_SESSION['id'];

    $sql = "SELECT * FROM user WHERE user_id = $id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Profile</title>
     <link rel="stylesheet" href="admin-profile.css">
     <link rel="stylesheet" href="homepage-carousel.css">
</head>
<body>
    <header>
         <div class="logo">
            <img src="images/taftlab-logo.png" alt="TaftLab Logo">
        </div>

    <div class="header-right">
      <nav>
        <ul> 
            <li><a href="admin-homepage.php">Back to Homepage</a></li>
        </ul>
      </nav>
    </div>
  </header>

        
    </header>

    <div class="admin-subheader"> </div>

    <div class="box"> 
        <img src="images/profile-icon.png" alt="Lab Picture" class="box-img">
        <div class="box-text">
            <div class="text-group">
                <h3><?php echo $row['full_name'];?></h3>
                <p class="email-text"> <?php echo $row['email']; ?></p>
            </div>
            <a href="logout.php" class="admin-btn">Logout</a>
        </div>
    </div>
    
</body>
</html>
