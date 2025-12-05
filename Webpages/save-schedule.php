<?php
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $room_code = $data['room_code'];
    $start_time = $data['start_time'];
    $end_time = $data['end_time'];
    $status = $data['status']; 
    $subject = isset($data['subject']) ? $data['subject'] : ""; 
    $selected_date = $data['date']; 
    
    // Convert Date (2023-10-25) to Day ('Mon', 'Tue')
    $day_of_week = date('D', strtotime($selected_date)); 

    $admin_user_id = 1; 

    // Find lab_id
    $sql_lab = "SELECT lab_id FROM laboratory WHERE room_code = '$room_code'";
    $result_lab = $conn->query($sql_lab);
    
    if($result_lab->num_rows > 0) {
        $lab_id = $result_lab->fetch_assoc()['lab_id'];
        
        $conn->query("DELETE FROM reservation WHERE lab_id = '$lab_id' AND date_reserved = '$selected_date' AND reserve_startTime = '$start_time'");

        $delete_class = "DELETE cs FROM class_schedule cs
                         JOIN existing_class ec ON cs.class_id = ec.class_id
                         WHERE ec.lab_id = '$lab_id' 
                         AND cs.class_day = '$day_of_week' 
                         AND cs.start_time = '$start_time'";
        $conn->query($delete_class);

        if ($status == "Unavailable") {
            
            $stmt = $conn->prepare("INSERT INTO reservation (user_id, lab_id, date_reserved, reserve_startTime, reserve_endTime, status) VALUES (?, ?, ?, ?, ?, 'Unavailable')");
            $stmt->bind_param("iisss", $admin_user_id, $lab_id, $selected_date, $start_time, $end_time);
            $stmt->execute();
            echo json_encode(["success" => true]);

        } elseif ($status == "Class Ongoing") {
            
            $section = "ADMIN"; 
            
            $check_class = "SELECT class_id FROM existing_class WHERE course_code = '$subject' AND section = '$section' AND lab_id = '$lab_id'";
            $res_check = $conn->query($check_class);

            if ($res_check->num_rows > 0) {
                // Class exists, get ID
                $class_id = $res_check->fetch_assoc()['class_id'];
            } else {
                // Class doesn't exist, Create it
                $stmt = $conn->prepare("INSERT INTO existing_class (course_code, section, lab_id) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $subject, $section, $lab_id);
                $stmt->execute();
                $class_id = $conn->insert_id;
            }

            // B. Add the Schedule for this specific Day
            $stmt = $conn->prepare("INSERT INTO class_schedule (class_id, class_day, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $class_id, $day_of_week, $start_time, $end_time);
            
            if ($stmt->execute()) {
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