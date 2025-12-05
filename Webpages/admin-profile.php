<?php
    include "db.php";
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $id = $_SESSION['user_id'];

    // fetch admin details from both tables
    $sql = "
        SELECT u.user_id, u.email, u.full_name, a.job_position
        FROM user u
        LEFT JOIN admin a ON u.user_id = a.user_id
        WHERE u.user_id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Admin not found.");
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Profile</title>
     <link rel="stylesheet" href="profile.css">
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

    <div class="subheader"></div>

    <div class="box"> 
        <img src="images/profile-icon.png" alt="Profile Picture" class="box-img">
        <div class="box-text">
            <div class="text-group">
                <h3><?php echo htmlspecialchars($row['full_name']); ?></h3>
                <p class="email-text"><?php echo htmlspecialchars($row['email']); ?></p>
                <p class="job-text"><?php echo htmlspecialchars($row['job_position'] ?? 'Administrator'); ?></p>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
</body>
</html>