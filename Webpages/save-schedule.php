<?php
include "db.php";

// Get the raw JSON data (this will now be a LIST of changes)
$input_data = json_decode(file_get_contents("php://input"), true);

if ($input_data) {
    $success_count = 0;
    $error_count = 0;
    $admin_user_id = 1; // Dummy Admin ID
    $date = date('Y-m-d'); // Today's date

    // Loop through each change sent by the "Proceed" button
    foreach ($input_data as $change) {
        $room_code = $change['room_code'];
        $start_time = $change['start_time'];
        $end_time = $change['end_time'];
        $status = $change['status'];

        // Find lab_id
        $sql_lab = "SELECT lab_id FROM laboratory WHERE room_code = '$room_code'";
        $result_lab = $conn->query($sql_lab);

        if ($result_lab->num_rows > 0) {
            $lab_id = $result_lab->fetch_assoc()['lab_id'];

            if ($status == "Occupied") {
                // Insert if not exists (IGNORE skips errors if it already exists)
                $sql = "INSERT IGNORE INTO reservation (user_id, lab_id, date_reserved, reserve_startTime, reserve_endTime, status) 
                        VALUES ('$admin_user_id', '$lab_id', '$date', '$start_time', '$end_time', 'Active')";
            } else {
                // Delete to make it vacant
                $sql = "DELETE FROM reservation 
                        WHERE lab_id = '$lab_id' 
                        AND date_reserved = '$date' 
                        AND reserve_startTime = '$start_time'";
            }

            if ($conn->query($sql) === TRUE) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }

    echo json_encode(["success" => true, "processed" => $success_count, "errors" => $error_count]);
} else {
    echo json_encode(["success" => false, "error" => "No data received"]);
}
?>