<?php
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $room_code = $data['room_code'];
    $start_time = $data['start_time'];
    $end_time = $data['end_time'];
    $status = $data['status']; // "Available", "Class Ongoing", or "Unavailable"
    
    $admin_user_id = 1; 
    $date = date('Y-m-d'); 

    // Find lab_id
    $sql_lab = "SELECT lab_id FROM laboratory WHERE room_code = '$room_code'";
    $result_lab = $conn->query($sql_lab);
    
    if($result_lab->num_rows > 0) {
        $lab_id = $result_lab->fetch_assoc()['lab_id'];

        // 1. Always DELETE existing record for this slot first (to avoid duplicates/conflicts)
        $delete_sql = "DELETE FROM reservation 
                       WHERE lab_id = '$lab_id' 
                       AND date_reserved = '$date' 
                       AND reserve_startTime = '$start_time'";
        $conn->query($delete_sql);

        // 2. If status is NOT "Available", INSERT the new status
        if ($status !== "Available") {
            $insert_sql = "INSERT INTO reservation (user_id, lab_id, date_reserved, reserve_startTime, reserve_endTime, status) 
                           VALUES ('$admin_user_id', '$lab_id', '$date', '$start_time', '$end_time', '$status')";
            
            if ($conn->query($insert_sql) === TRUE) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => $conn->error]);
            }
        } else {
            // If status is "Available", we just deleted the record, so we are done.
            echo json_encode(["success" => true]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Room not found"]);
    }
}
?>