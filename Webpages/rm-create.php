<?php
    include "db.php";
    session_start();
    if (!isset($_SESSION['user_id'])) 
    {
        header("Location: login.php");
        exit();
    }

    if($_SERVER["REQUEST_METHOD"] == "GET")
    {
      if(isset($_GET['type']) && $_GET['type'] == 'create-LS') //get from rm-management.php
        $code = "LS";
      elseif(isset($_GET['type']) && $_GET['type'] == "create-GK")
        $code = "GK";
      elseif(isset($_GET['type']) && $_GET['type'] == "create-Y")
        $code = "Y";
      elseif(isset($_GET['type']) && $_GET['type'] == "create-AG")
        $code = "AG";
      elseif(isset($_GET['type']) && $_GET['type'] == "create-V")
        $code = "V";

        $getBuilding = "SELECT * FROM building WHERE building_code = '$code'";
        $resultBuilding = $conn->query($getBuilding);
        $row = $resultBuilding->fetch_assoc();   
    }

    $hasError = false;

      if($_SERVER["REQUEST_METHOD"] == "POST")
      {
        $b_code = $_POST['b_code'] ?? '';
        $room_no = $_POST['room_no'] ?? '';
        $capacity = $_POST['capacity'] ?? '';
        $status = $_POST['status'] ?? '';

          if(empty($b_code) || empty($room_no) || empty($capacity) || empty($status))
              $hasError = true;

          if(!$hasError)
          {
              $full_roomcode = $b_code . $room_no;

             //get id of building dynamically using building code
              $getBuilding = "SELECT b.building_id FROM building b
                              WHERE b.building_code = '$b_code'";
              $result = $conn->query($getBuilding);
              $row = $result->fetch_assoc();
              $buildingID = $row['building_id'];
              
              //insert to laboratory table using that 
              $insertLab = "INSERT INTO laboratory(building_id, room_code, capacity, status)
                              VALUES ('$buildingID', '$full_roomcode', '$capacity', '$status')";

              mysqli_query($conn, $insertLab);


              header("Location:rm-management.php?type=$b_code&message=added&room_code=$full_roomcode");
              $hasError = false;
              exit();
          }
      }
      $conn->close();
  ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LS-Create</title>
      <link rel="stylesheet" href="homepage-carousel.css">
    <link rel="stylesheet" href="rm-create.css">
</head>
<body>
      <header> 
        <div class="logo">
            <img src="images/taftlab-logo.png" alt="TaftLab Logo"/>
        </div>

    <div class="header-right">
      <nav>
        <ul> 
          <li><a href="admin-profile">Profile</a></li>
          <li><a href="logout.php">Logout</a></li>
        </ul>
      </nav>
      <div class="profile-icon">
        <img src="images/profile-icon.png" alt="Profile Icon"/>
      </div>
    </div>
  </header>

  <div class="admin-subheader">
    <h2 class=>Room Management - Create Page</h2>
  </div>

  <h2 class="cardHeader"><?php echo "Building: " . $row['building_name'] ?></h2>

  <form method="POST" action="rm-create.php">
    <div class="form-group">
      <label for="b_code">Building Code:</label>
      <input type="text" name="b_code" value="<?php echo $code;?>" readonly> <br>
    </div>

     <div class="form-group">
      <label for="room_no">Room Number:</label>
      <input type="text" inputmode="numeric" name="room_no" required> <br>
    </div>

    <div class="form-group">
      <label for="capacity">Max Capacity:</label>
      <input type="text" name="capacity"> <br>
    </div>


    <div class="form-group">
      <label for="status">Status:</label>
      <select name="status" required>
          <option value ="active">Active</option>
      </select>
    </div>

   <input type="submit" value="Submit">

  </form>
</body>
</html>
