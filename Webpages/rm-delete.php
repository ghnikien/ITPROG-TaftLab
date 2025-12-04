<?php
    include "db.php";

    if($_SERVER["REQUEST_METHOD"] == "POST")
    {
        if(!empty($_POST['lab_id']) && !empty($_POST['pageRequester']))
        {
            $labID = $_POST['lab_id'] ?? '';
            //get first the building to be sent in the url for reference
            $sqlLab = "SELECT building_id FROM laboratory WHERE lab_id = '$labID'";
            $result = $conn->query($sqlLab);
            $row = $result->fetch_assoc();
            $buildingID = $row['building_id'];
            
            $sqlBD = "SELECT building_code FROM building b 
                       JOIN laboratory l ON b.building_id = l.building_id 
                       WHERE lab_id = '$labID'";
            $result = $conn->query($sqlBD);
            $row = $result->fetch_assoc();
            $buildingCode = $row['building_code'];

            //proceed with the delete
            $pageRequester = $_POST['pageRequester'] ?? 'rm-management.php';
            $sql = "DELETE FROM laboratory WHERE lab_id = '$labID'";
            

            if(mysqli_query($conn, $sql))
            {   
                header("Location: {$pageRequester}?type=$buildingCode&message=deleted");
                exit();
            }
            else
            {
                echo "Error deleting record: ". mysqli_error($conn);
            }
        }
        else
        {
            echo "Missing lab ID";
        }
        
    }
 

    $conn->close();
?>
