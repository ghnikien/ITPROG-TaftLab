<?php
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $room_code = $data['room_code'];
    $start_time = $data['start_time'];
    $end_time = $data['end_time'];
    $status = $data['status']; 
    $subject = isset($data['subject']) ? $data['subject'] : ""; 
    $section = isset($data['section']) ? $data['section'] : "ADMIN"; 
    $selected_date = $data['date']; 
    $day_of_week = date('D', strtotime($selected_date)); 
    $admin_user_id = 1; 

    $sql_lab = "SELECT lab_id FROM laboratory WHERE room_code = '$room_code'";
    $result_lab = $conn->query($sql_lab);
    
    if($result_lab->num_rows > 0) {
        $lab_id = $result_lab->fetch_assoc()['lab_id'];

        // VALIDATION: CHECK FOR EXISTING STUDENT RESERVATIONS 
        // We block the Admin if students are already in the 'reservation' table
        if ($status !== "Available") {
            $check_res = "SELECT COUNT(*) as c FROM reservation 
                          WHERE lab_id = '$lab_id' 
                          AND date_reserved = '$selected_date' 
                          AND reserve_startTime = '$start_time'
                          AND status = 'Active'";
            $res_count = $conn->query($check_res)->fetch_assoc()['c'];

            if ($res_count > 0) {
                echo json_encode(["success" => false, "error" => "Cannot change status. There are $res_count active student reservations."]);
                exit; 
            }
        }
        
        // A. Delete from RESTRICTED_SLOTS (Red blocks)
        $conn->query("DELETE FROM restricted_slots WHERE lab_id = '$lab_id' AND restricted_date = '$selected_date' AND start_time = '$start_time'");

        // B. Delete from CLASS SCHEDULE (Orange classes)
        $delete_class = "DELETE cs FROM class_schedule cs
                         JOIN existing_class ec ON cs.class_id = ec.class_id
                         WHERE ec.lab_id = '$lab_id' 
                         AND cs.class_day = '$day_of_week' 
                         AND cs.start_time = '$start_time'";
        $conn->query($delete_class);


        // STEP 2: INSERT NEW RECORD 
        if ($status == "Unavailable") {
            // CASE 1: Red -> Insert into RESTRICTED_SLOTS
            $stmt = $conn->prepare("INSERT INTO restricted_slots (lab_id, restricted_date, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $lab_id, $selected_date, $start_time, $end_time);
            
            if($stmt->execute()) echo json_encode(["success" => true]);
            else echo json_encode(["success" => false, "error" => $conn->error]);

        } elseif ($status == "Class Ongoing") {
            // CASE 2: Orange -> Insert into EXISTING_CLASS + CLASS_SCHEDULE
            
            // Check if Class exists
            $check_class = "SELECT class_id FROM existing_class WHERE course_code = '$subject' AND section = '$section' AND lab_id = '$lab_id'";
            $res_check = $conn->query($check_class);

            if ($res_check->num_rows > 0) {
                $class_id = $res_check->fetch_assoc()['class_id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO existing_class (course_code, section, lab_id) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $subject, $section, $lab_id);
                $stmt->execute();
                $class_id = $conn->insert_id;
            }

            // Add Schedule
            $stmt = $conn->prepare("INSERT INTO class_schedule (class_id, class_day, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $class_id, $day_of_week, $start_time, $end_time);
            
            if ($stmt->execute()) echo json_encode(["success" => true]);
            else echo json_encode(["success" => false, "error" => $conn->error]);

        } else {
            // CASE 3: Available -> We already cleaned up, so we are done.
            echo json_encode(["success" => true]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Room not found"]);
    }
}
?>