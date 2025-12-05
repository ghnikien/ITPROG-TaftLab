<?php
  include "db.php";

  $totalClassrooms = 0;

  if($_SERVER["REQUEST_METHOD"] == "GET")
  {
    $code = $_GET['type'] ?? '';

    $getBuilding = "SELECT * FROM building WHERE building_code = '$code'";
    $resultBuilding = $conn->query($getBuilding);
    $row = $resultBuilding->fetch_assoc(); 


    $sql = "SELECT * FROM laboratory WHERE room_code LIKE '%$code%' ORDER BY room_code ASC";
    $result = $conn->query($sql);
    $totalClassroom = "SELECT COUNT(*) AS total FROM laboratory WHERE room_code LIKE '%$code%'";
    $result2 = $conn->query($totalClassroom);
    $value = $result2->fetch_assoc();
    $totalClassrooms = $value['total'];
    
    
  }

  $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LS Management</title>
    <link rel="stylesheet" href="homepage-carousel.css">
    <link rel="stylesheet" href="rm-management.css">
</head>
<body>
    <header> 
        <div class="logo">
            <img src="images/taftlab-logo.png" alt="TaftLab Logo"/>
        </div>

    <div class="header-right">
      <nav>
        <ul> 
          <li><a href="roomManagement.php">Hompage</a></li>
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
    <h2>Room Management</h2>
  </div>

  <div class="header-row">
    <div class="header-left">
        <h1 class="header"><?php echo $row['building_name'] ?? 'Unknown Building'; ?></h1>
        <p class="sub-header-text"><?php echo "Total Classrooms: " . $totalClassrooms; ?></p>
    </div>
    <a href="rm-create.php?type=create-<?php echo $code;?>" class="create">+ Add Classroom</a> 
  </div>

  <table>
    <tr class="table-header">
      <th> Room </th>
      <th> Capacity </th>
      <th> Status </th>
      <th> Action </th>
    </tr>

    <?php while($labs = mysqli_fetch_array($result)): ?>
      <tr>
        <td> <?php echo $labs['room_code'] ?> </td>
        <td> <?php echo $labs['capacity'] ?> </td>
        <td> <?php echo $labs['status'] ?> </td>
        <td> 
          <form method="GET" action="rm-update.php" style="display:inline">
            <input type="hidden" name="building_code" value="<?php echo $row['building_code']; ?>">
            <input type="hidden" name="lab_id" value="<?php echo $labs['lab_id']; ?>">
            <input type="hidden" name="pageRequester" value="rm-management.php">
            <input class="update" type="submit" value="Edit">
          </form>

          <form method="POST" action="rm-delete.php" style="display:inline">
            <input type="hidden" name="lab_id" value="<?php echo $labs['lab_id']; ?>">
            <input type="hidden" name="pageRequester" value="rm-management.php">
            <input class="delete" type="submit" value="Delete" onclick="return confirm('Are you sure you want to delete this laboratory?');">
          </form>
        </td>
      </tr>
    <?php endwhile;?>
    
  </table>
  <?php
    if(isset($_GET['message']) && $_GET['message'] == 'added' && isset($_GET['room_code']))
    {
      $roomcode = $_GET['room_code'];
      echo "<p class='success'>$roomcode has been added Successfully!</p>";
    }
    elseif(isset($_GET['message']) && $_GET['message'] == 'updated' && isset($_GET['room_code']))
    {
      $roomcode = $_GET['room_code'];
      echo "<p class='success'>Room $roomcode has been updated Successfully!</p>";
    }
    elseif(isset($_GET['message']) && $_GET['message'] == 'deleted' && isset($_GET['room_code']))
    {
      echo "<p class='deleted'>Lab has been deleted!</p>";
    }
    else
    {
      //do nothing so does not show any message
    }
  ?>

</body>
</html>
