<?php
  include "db.php";

  $LSClassrooms = 0;
  $GSClassrooms = 0;
  $AGClassrooms = 0;
  $YClassrooms = 0;
  $VClassrooms = 0;

  $LS = "SELECT COUNT(*) AS total FROM laboratory WHERE room_code LIKE '%LS%'";
  $result = $conn->query($LS);
  $value = $result->fetch_assoc();
  $LSClassrooms = $value['total'];

  $GK = "SELECT COUNT(*) AS total FROM laboratory WHERE room_code LIKE '%GK%'";
  $result = $conn->query($GK);
  $value = $result->fetch_assoc();
  $GKClassrooms = $value['total'];

  $A = "SELECT COUNT(*) AS total FROM laboratory WHERE room_code LIKE '%A%'";
  $result = $conn->query($A);
  $value = $result->fetch_assoc();
  $AGClassrooms = $value['total'];

  $Y = "SELECT COUNT(*) AS total FROM laboratory WHERE room_code LIKE '%Y%'";
  $result = $conn->query($Y);
  $value = $result->fetch_assoc();
  $YClassrooms = $value['total'];

  $V = "SELECT COUNT(*) AS total FROM laboratory WHERE room_code LIKE '%V%'";
  $result = $conn->query($V);
  $value = $result->fetch_assoc();
  $VClassrooms = $value['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management</title>
    <link rel="stylesheet" href="homepage-carousel.css">
    <link rel="stylesheet" href="roomManagement.css">
</head>
<body>
    <header> 
        <div class="logo">
            <img src="images/taftlab-logo.png" alt="TaftLab Logo"/>
        </div>

    <div class="header-right">
      <nav>
        <ul> 
          <li><a href="#">History</a></li>
          <li><a href="#">Profile</a></li>
          <li><a href="login.php">Logout</a></li>
        </ul>
      </nav>
      <div class="profile-icon">
        <img src="images/profile-icon.png" alt="Profile Icon"/>
      </div>
    </div>
  </header>

  <div class="admin-subheader">
    <h2 class=>Room Management</h2>
  </div>

  <div class="box">
    <img src="images/LS_229_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
        <h3>St. La Salle Hall</h3>
        <p> <?php echo "Classrooms: " . $LSClassrooms; ?> </p>
        <a href="rm-management.php?type=LS" class="admin-btn">Select</a>
    </div>
  </div>

  <div class="box">
    <img src="images/GK_304B_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
        <h3>Gokongwei Hall</h3>
        <p> <?php echo "Classrooms: " . $GKClassrooms; ?> </p>
        <a href="rm-management.php?type=GK" class="admin-btn">Select</a>
    </div>
  </div>

  <div class="box">
    <img src="images/AG_1904_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
        <h3>Br. Andrew Gonzales Hall</h3>
        <p> <?php echo "Classrooms: " . $AGClassrooms; ?> </p>
        <a href="rm-management.php?type=AG" class="admin-btn">Select</a>
    </div>
  </div>

  <div class="box">
    <img src="images/V_103_indoor_3.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
        <h3>Velasco Hall</h3>
        <p> <?php echo "Classrooms: " . $VClassrooms; ?> </p>
        <a href="rm-management.php?type=V" class="admin-btn">Select</a>
    </div>
  </div>

  <div class="box">
    <img src="images/Y_602_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
        <h3>Don Enrique Yuchengco Hall</h3>
        <p> <?php echo "Classrooms: " . $YClassrooms; ?> </p>
        <a href="rm-management.php?type=Y" class="admin-btn">Select</a>
    </div>
  </div>
    
</body>
</html>