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
        if(!empty($_POST['lab_id']) && !empty($_POST['pageRequester']))
        {
            $labID = $_POST['lab_id'] ?? '';
            //get first the building to be sent in the url for reference
            $sqlLab = "SELECT building_id, room_code FROM laboratory WHERE lab_id = '$labID'";
            $result = $conn->query($sqlLab);
            $row = $result->fetch_assoc();
            $buildingID = $row['building_id'];
            $roomcode = $row['room_code'];
            
            $sqlBD = "SELECT building_code FROM building b 
                       JOIN laboratory l ON b.building_id = l.building_id 
                       WHERE lab_id = '$labID'";
            $result = $conn->query($sqlBD);
            $row = $result->fetch_assoc();
            $buildingCode = $row['building_code'];

            //check first if there are reservations
            $checkReservations = "SELECT COUNT(*) AS c
                                  FROM reservation WHERE lab_id = ?
                                  AND status = 'Active'";
            $stmt = $conn->prepare($checkReservations);
            $stmt->bind_param("i", $labID);
            $stmt->execute();
            $resCount = $stmt->get_result()->fetch_assoc()['c'];
            $stmt->close();

            //also check if there is an existing class
            $checkClasses = "SELECT COUNT(*) AS c FROM existing_class WHERE lab_id = ?";
            $stmt_class = $conn->prepare($checkClasses);
            $stmt_class->bind_param("i", $labID);
            $stmt_class->execute();
            $classCount = $stmt_class->get_result()->fetch_assoc()['c'];
            $stmt_class->close();

            if ($classCount > 0) {
                header("Location: rm-management.php?type={$buildingCode}&room_code={$roomcode}&message=has_classes");
                exit();
            }
            
            //if reservation check query has result or the existing class check do not delete
            if($resCount > 0)
            {
                header("Location: rm-management.php?type={$buildingCode}&room_code={$roomcode}&message=has_reservations");
                exit();
            }
            //proceed with the delete if safe (no reservations)
            $pageRequester = $_POST['pageRequester'] ?? 'rm-management.php';
            $sql_delete = "DELETE FROM laboratory WHERE lab_id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $labID);
            if($stmt_delete->execute())
            {
                header("Location: {$pageRequester}?type=$buildingCode&message=deleted");
                exit();
            }
            else
            {
                echo "Error deleting record: ". $conn->error;
            }
        }
        else
        {
            echo "Missing lab ID";
        }
        
    }
 

    $conn->close();
?>
