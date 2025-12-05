<?php
    include "db.php";

    if($_SERVER["REQUEST_METHOD"] == "GET")
    {
      $bcode = $_GET['building_code'] ?? '';
      $getBuilding = "SELECT * FROM building WHERE building_code = '$bcode'";
      $resultBuilding = $conn->query($getBuilding);
      $bd = $resultBuilding->fetch_assoc();  
      
      $labID = $_GET['lab_id'];
      $sql = "SELECT * FROM laboratory WHERE lab_id = $labID";
      $result = $conn->query($sql);
      $row = mysqli_fetch_array($result);
      $pageRequester = $_GET['pageRequester'] ?? 'rm-management.php' ;
    }
   
    $hasError = false;
    if($_SERVER["REQUEST_METHOD"] == "POST")
    {
        $labID = $_POST['lab_id'];
        $b_code = $_POST['b_code'] ?? '';
        $room_no = $_POST['room_no'];
        $capacity = $_POST['capacity'] ?? '';
        $status = $_POST['status'] ?? '';
        $pageRequester = $_POST['pageRequester'] ?? 'rm-management.php';

        if(empty($b_code) || empty($room_no) || empty($capacity) || empty($status))
            $hasError = true;


        if(!$hasError)
        {           
          $full_roomcode =$b_code . $room_no;
            $updateLab = "UPDATE laboratory SET room_code = '$full_roomcode',
                                                capacity  = '$capacity',
                                                status    = '$status'
                          WHERE lab_id = $labID";

            mysqli_query($conn, $updateLab);
            header("Location: {$pageRequester}?type=$b_code&message=updated&room_code=$full_roomcode");
            $hasError = false;
            exit();
        }
        mysqli_close($conn);
    }
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
    <h2 class=>Room Management - Update Page</h2>
  </div>

    <h2 class="cardHeader"><?php echo $bd['building_name'];?></h2>
    <h2 class="cardHeader"><?php echo "Room: " . $row['room_code'];?></h2>

  <form method="POST" action="rm-update.php">
    <input type="hidden" name="pageRequester" value="<?php echo $pageRequester; ?>">
    <input type="hidden" name="lab_id" value="<?php echo $row['lab_id'];?>">
    <div class="form-group">
      <label for="b_code">Building Code:</label>
      <input type="text" name="b_code" value = "<?php echo $bd['building_code'];?>" readonly> <br>
    </div>

     <div class="form-group">
      <label for="room_no">Room Number:</label>
      <input type="text" inputmode="numeric" name="room_no" value="<?php echo filter_var($row['room_code'], FILTER_SANITIZE_NUMBER_INT);?>" required> <br> <!--extract the number only -->
    </div>

    <div class="form-group">
      <label for="capacity">Max Capacity:</label>
      <input type="text" name="capacity" value = "<?php echo $row['capacity'];?>"> <br>
    </div>

    <div class="form-group">
      <label for="status">Status:</label>
      <select name="status" required>
          <option value ="">-- Select Status --</option>
          <option value ="Active" <?php if($row['status'] == 'Active') echo 'selected';?>>Active</option>
          <option value ="Maintenance" <?php if($row['status'] == 'Maintenance') echo 'selected';?>>Maintenance</option>
          <option value ="Closed" <?php if($row['status'] == 'Closed') echo 'selected';?>>Closed</option>
      </select>
    </div>

   <input type="submit" value="Submit">

  </form>
</body>
</html>