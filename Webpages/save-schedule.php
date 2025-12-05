<?php
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $room_code = $data['room_code'];
    $start_time = $data['start_time'];
    $end_time = $data['end_time'];
    $status = $data['status']; 
    $subject = isset($data['subject']) ? $data['subject'] : ""; // Get the subject
    $selected_date = $data['date']; // Get the specific date selected
    
    $admin_user_id = 1; 

    // Find lab_id
    $sql_lab = "SELECT lab_id FROM laboratory WHERE room_code = '$room_code'";
    $result_lab = $conn->query($sql_lab);
    
    if($result_lab->num_rows > 0) {
        $lab_id = $result_lab->fetch_assoc()['lab_id'];

        // 1. DELETE existing record for this slot/date
        $delete_sql = "DELETE FROM reservation 
                       WHERE lab_id = '$lab_id' 
                       AND date_reserved = '$selected_date' 
                       AND reserve_startTime = '$start_time'";
        $conn->query($delete_sql);

        // 2. INSERT new record (if not "Available")
        if ($status !== "Available") {
            // Include 'subject' in the INSERT
            $insert_sql = "INSERT INTO reservation (user_id, lab_id, date_reserved, reserve_startTime, reserve_endTime, status, subject) 
                           VALUES ('$admin_user_id', '$lab_id', '$selected_date', '$start_time', '$end_time', '$status', '$subject')";
            
            if ($conn->query($insert_sql) === TRUE) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => $conn->error]);
            }
        } else {
            echo json_encode(["success" => true]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Room not found"]);
    }
}
?>