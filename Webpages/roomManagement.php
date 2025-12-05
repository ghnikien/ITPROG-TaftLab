<?php
  include "db.php";

  $LS = "SELECT COUNT(*) AS total, GROUP_CONCAT(room_code SEPARATOR ', ') as room_list 
         FROM laboratory 
         WHERE room_code 
         LIKE '%LS%'
         ORDER BY room_code ASC";
  $result = $conn->query($LS);
  $rowLS = $result->fetch_assoc();

  $GK = "SELECT COUNT(*) AS total, GROUP_CONCAT(room_code SEPARATOR ', ') as room_list 
  FROM laboratory 
  WHERE room_code 
  LIKE '%GK%'
  ORDER BY room_code ASC";
  $result = $conn->query($GK);
  $rowGK = $result->fetch_assoc();

  $A = "SELECT COUNT(*) AS total, GROUP_CONCAT(room_code SEPARATOR ', ') as room_list 
  FROM laboratory 
  WHERE room_code 
  LIKE '%AG%'
  ORDER BY room_code ASC";
  $result = $conn->query($A);
  $rowA = $result->fetch_assoc();

  $Y = "SELECT COUNT(*) AS total, GROUP_CONCAT(room_code SEPARATOR ', ') as room_list 
  FROM laboratory 
  WHERE room_code 
  LIKE '%Y%'
  ORDER BY room_code ASC";
  $result = $conn->query($Y);
  $rowY = $result->fetch_assoc();

  $V = "SELECT COUNT(*) AS total, GROUP_CONCAT(room_code SEPARATOR ', ') as room_list 
  FROM laboratory 
  WHERE room_code 
  LIKE '%V%'
  ORDER BY room_code ASC";
  $result = $conn->query($V);
  $rowV = $result->fetch_assoc();
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
           <li><a href="admin-homepage.php">Back to Dashboard</a></li>
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
      <div class="text-group">
        <h3>St. La Salle Hall</h3>
        <p class="total-classroom"> <?php echo $rowLS['total'] . " classrooms total"; ?> </p>
        <p class="classroom-list"> <?php echo $rowLS['room_list'] ? $rowLS['room_list'] : "No rooms added yet"; ?> </p>
      </div>
        <a href="rm-management.php?type=LS" class="admin-btn">Select</a>
    </div>
  </div>

  <div class="box">
    <img src="images/GK_304B_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
      <div class="text-group">
        <h3>Gokongwei Hall</h3>
        <p class="total-classroom">  <?php echo $rowGK['total'] . " classrooms total"; ?> </p>
        <p class="classroom-list"> <?php echo $rowGK['room_list'] ? $rowGK['room_list'] : "No rooms added yet"; ?> </p>
      </div>
        <a href="rm-management.php?type=GK" class="admin-btn">Select</a>
    </div>
  </div>

  <div class="box">
    <img src="images/AG_1904_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
      <div class="text-group">
        <h3>Br. Andrew Gonzales Hall</h3>
         <p class="total-classroom">  <?php echo $rowA['total'] . " classrooms total"; ?> </p>
         <p class="classroom-list"> <?php echo $rowA['room_list'] ? $rowA['room_list'] : "No rooms added yet"; ?> </p>
      </div>
        <a href="rm-management.php?type=AG" class="admin-btn">Select</a>
    </div>
  </div>

  <div class="box">
    <img src="images/V_103_indoor_3.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
      <div class="text-group">
        <h3>Velasco Hall</h3>
         <p class="total-classroom">  <?php echo $rowV['total'] . " classrooms total"; ?> </p>
         <p class="classroom-list"> <?php echo $rowV['room_list'] ? $rowV['room_list'] : "No rooms added yet"; ?> </p>
      </div>
        <a href="rm-management.php?type=V" class="admin-btn">Select</a>
    </div>
  </div>

  <div class="box">
    <img src="images/Y_602_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
      <div class="text-group">
        <h3>Don Enrique Yuchengco Hall</h3>
         <p class="total-classroom">  <?php echo $rowY['room_list'] ? $rowY['room_list'] : "No rooms added yet"; ?> </p>
         <p class="classroom-list"> <?php echo $rowY['total'] . " classrooms total"; ?> </p>
      </div>
        <a href="rm-management.php?type=Y" class="admin-btn">Select</a>
    </div>
  </div>
</body>
</html>