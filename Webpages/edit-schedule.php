<?php
include "db.php";

$building_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_date = date('Y-m-d'); 

// Fetch Building Name
$sql_building = "SELECT building_name FROM building WHERE building_id = $building_id";
$result_building = $conn->query($sql_building);
$building_name = ($result_building->num_rows > 0) ? $result_building->fetch_assoc()['building_name'] : "Unknown Building";

// Fetch Rooms
$sql_rooms = "SELECT room_code, capacity FROM laboratory WHERE building_id = $building_id ORDER BY room_code";
$result_rooms = $conn->query($sql_rooms);

// Fetch Existing Reservations
$booked_slots = [];
$sql_reservations = "SELECT l.room_code, r.reserve_startTime, r.status 
                     FROM reservation r 
                     JOIN laboratory l ON r.lab_id = l.lab_id 
                     WHERE l.building_id = $building_id 
                     AND r.date_reserved = '$current_date'";
$result_res = $conn->query($sql_reservations);

if ($result_res) {
    while($row = $result_res->fetch_assoc()) {
        // Store the specific status ("Class Ongoing", "Unavailable")
        $booked_slots[$row['room_code']][$row['reserve_startTime']] = $row['status'];
    }
}

$time_slots = [
    ["07:30:00", "09:00:00"], ["09:15:00", "10:45:00"], ["11:00:00", "12:30:00"],
    ["12:45:00", "14:15:00"], ["14:30:00", "16:00:00"], ["16:15:00", "17:45:00"],
    ["18:00:00", "19:30:00"], ["19:45:00", "21:15:00"]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule - <?php echo $building_name; ?></title>
    <link rel="stylesheet" href="scheduleManagement.css">
    
    <style>
        .edit-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .building-title { font-size: 2rem; font-weight: 800; margin-bottom: 20px; }
        
        /* Table Styles */
        .schedule-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .schedule-table th, .schedule-table td { border: 1px solid #ccc; padding: 8px; text-align: center; font-size: 12px; }
        .schedule-table th { background-color: #f2f2f2; font-weight: 700; }
        .schedule-table td:first-child { font-weight: 800; background-color: #eee; width: 80px; }

        /* Dynamic Status Colors */
        .status-available { background-color: #28a745; color: white; cursor: pointer; } /* Green */
        .status-ongoing { background-color: #fd7e14; color: white; cursor: pointer; } /* Orange */
        .status-unavailable { background-color: #dc3545; color: white; cursor: pointer; } /* Red */
        
        /* EDIT FORM STYLES (Hidden by default) */
        #editFormSection { display: none; border: 2px solid #006937; padding: 30px; border-radius: 10px; text-align: center; max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group select { width: 100%; padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc; }
        
        .btn-confirm { background-color: #006937; color: white; padding: 10px 30px; border: none; border-radius: 20px; cursor: pointer; font-weight: bold; font-size: 16px; margin-right: 10px; }
        .btn-cancel { background-color: #888; color: white; padding: 10px 30px; border: none; border-radius: 20px; cursor: pointer; font-weight: bold; font-size: 16px; }
        
        .back-dashboard { display: inline-block; margin-top: 20px; color: #555; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <header>
        <div class="logo"><img src="images/taftlab-logo.png" alt="Taft Lab Logo"></div>
        <div class="header-right">
            <nav><ul><li><a href="scheduleManagement.php">Back to Dashboard</a></li></ul></nav>
        </div>
    </header>

    <div class="title-bar"><h1>Schedule Management</h1></div>

    <div class="edit-container">
        <h2 class="building-title"><?php echo $building_name; ?></h2>
        
        <div id="tableView">
            <p style="margin-bottom:15px; font-weight:600; color:#555;">Click a timeslot to update its status.</p>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <?php foreach ($time_slots as $slot) echo "<th>" . substr($slot[0], 0, 5) . "<br>to<br>" . substr($slot[1], 0, 5) . "</th>"; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_rooms->num_rows > 0) {
                        while($row = $result_rooms->fetch_assoc()) {
                            $room = $row['room_code'];
                            echo "<tr><td>" . $room . "</td>";
                            foreach($time_slots as $slot) {
                                $start = $slot[0]; $end = $slot[1];
                                
                                // Determine Status
                                $status = "Available"; // Default
                                if (isset($booked_slots[$room][$start])) {
                                    $status = $booked_slots[$room][$start]; // "Class Ongoing" or "Unavailable"
                                }

                                // Determine Color Class
                                $class = "status-available";
                                if ($status == "Class Ongoing") $class = "status-ongoing";
                                if ($status == "Unavailable") $class = "status-unavailable";

                                echo "<td class='$class' 
                                          onclick=\"openEditForm('$room', '$start', '$end', '$status')\">
                                          $status
                                      </td>";
                            }
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
            <a href="scheduleManagement.php" class="back-dashboard">Back to Dashboard</a>
        </div>

        <div id="editFormSection">
            <h3 style="margin-bottom: 20px;">Edit Schedule</h3>
            
            <div class="form-group">
                <label>Room:</label>
                <input type="text" id="editRoom" disabled style="width:100%; padding:10px; background:#eee; border:1px solid #ccc;">
            </div>

            <div class="form-group">
                <label>Timeslot:</label>
                <input type="text" id="editTime" disabled style="width:100%; padding:10px; background:#eee; border:1px solid #ccc;">
            </div>

            <div class="form-group">
                <label>Status:</label>
                <select id="editStatus">
                    <option value="Available">Available (Green)</option>
                    <option value="Class Ongoing">Class Ongoing (Orange)</option>
                    <option value="Unavailable">Unavailable (Red)</option>
                </select>
            </div>

            <div style="margin-top: 30px;">
                <button class="btn-confirm" onclick="confirmUpdate()">Confirm</button>
                <button class="btn-cancel" onclick="closeEditForm()">Cancel</button>
            </div>
        </div>

    </div>

    <script>
    // Global variables to store current selection
    let selectedRoom = "";
    let selectedStart = "";
    let selectedEnd = "";

    function openEditForm(room, start, end, currentStatus) {
        // 1. Save data to globals
        selectedRoom = room;
        selectedStart = start;
        selectedEnd = end;

        // 2. Populate the form
        document.getElementById('editRoom').value = room;
        document.getElementById('editTime').value = start.substring(0,5) + " - " + end.substring(0,5);
        document.getElementById('editStatus').value = currentStatus;

        // 3. Switch Views (Hide Table, Show Form)
        document.getElementById('tableView').style.display = 'none';
        document.getElementById('editFormSection').style.display = 'block';
    }

    function closeEditForm() {
        // Switch Views Back (Hide Form, Show Table)
        document.getElementById('editFormSection').style.display = 'none';
        document.getElementById('tableView').style.display = 'block';
    }

    function confirmUpdate() {
        let newStatus = document.getElementById('editStatus').value;

        // Send to Backend
        fetch('save-schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                room_code: selectedRoom,
                start_time: selectedStart,
                end_time: selectedEnd,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success! Reload the page to show updated table
                location.reload(); 
            } else {
                alert("Error saving: " + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    </script>

</body>
</html>