<?php
include "db.php";

$building_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// DAY SELECTION LOGIC 
// If user selected a day, use it. Otherwise, default to "Monday".
$selected_day = isset($_GET['day']) ? $_GET['day'] : "Monday";

// Calculate the Date for the selected day (Upcoming)
// This ensures we are editing a real date in the database
$target_date = date('Y-m-d', strtotime("next $selected_day"));

// Fetch Building Name
$sql_building = "SELECT building_name FROM building WHERE building_id = $building_id";
$result_building = $conn->query($sql_building);
$building_name = ($result_building->num_rows > 0) ? $result_building->fetch_assoc()['building_name'] : "Unknown Building";

// Fetch Rooms AND Capacity
$sql_rooms = "SELECT room_code, capacity FROM laboratory WHERE building_id = $building_id ORDER BY room_code";
$result_rooms = $conn->query($sql_rooms);

// Fetch Existing Reservations for the TARGET DATE
$booked_slots = [];
$sql_reservations = "SELECT l.room_code, r.reserve_startTime, r.status, r.subject 
                     FROM reservation r 
                     JOIN laboratory l ON r.lab_id = l.lab_id 
                     WHERE l.building_id = $building_id 
                     AND r.date_reserved = '$target_date'";
$result_res = $conn->query($sql_reservations);

if ($result_res) {
    while($row = $result_res->fetch_assoc()) {
        $booked_slots[$row['room_code']][$row['reserve_startTime']] = [
            'status' => $row['status'],
            'subject' => $row['subject']
        ];
    }
}

$time_slots = [
    ["07:30:00", "09:00:00"], ["09:15:00", "10:45:00"], ["11:00:00", "12:30:00"],
    ["12:45:00", "14:15:00"], ["14:30:00", "16:00:00"], ["16:15:00", "17:45:00"],
    ["18:00:00", "19:30:00"], ["19:45:00", "21:15:00"]
];

$days_of_week = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
$subjects_list = ["IT-PROG", "CCINFOM", "CCAPDEV", "ITNET01", "ITNET02", "ITDBADM", "ITISMOB", "CCPROG1", "CCPROG2", "CCPROG3", "ITCMSY2"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule - <?php echo $building_name; ?></title>
    <link rel="stylesheet" href="scheduleManagement.css">
    
    <style>
        .edit-container { max-width: 1400px; margin: 40px auto; padding: 20px; } 
        .building-title { font-size: 2rem; font-weight: 800; margin-bottom: 20px; }
        
        /* Day Selector */
        .day-selector { margin-bottom: 20px; }
        .day-selector select { padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc; font-weight: bold; }
        
        /* Table Styles */
        .schedule-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .schedule-table th, .schedule-table td { border: 1px solid #ccc; padding: 5px; text-align: center; font-size: 11px; height: 50px; }
        .schedule-table th { background-color: #f2f2f2; font-weight: 700; }
        .schedule-table td:first-child { font-weight: 800; background-color: #eee; width: 60px; font-size: 13px; }

        /* Dynamic Status Colors & Text */
        .status-available { background-color: #28a745; color: white; cursor: pointer; } 
        .status-ongoing { background-color: #fd7e14; color: white; cursor: pointer; } 
        .status-unavailable { background-color: #dc3545; color: white; cursor: pointer; } 

        /* EDIT FORM */
        #editFormSection { display: none; border: 2px solid #006937; padding: 30px; border-radius: 10px; text-align: center; max-width: 500px; margin: 0 auto; background: white; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group select, .form-group input { width: 100%; padding: 10px; font-size: 14px; border-radius: 5px; border: 1px solid #ccc; }
        
        .btn-confirm { background-color: #006937; color: white; padding: 10px 30px; border: none; border-radius: 20px; cursor: pointer; font-weight: bold; }
        .btn-cancel { background-color: #888; color: white; padding: 10px 30px; border: none; border-radius: 20px; cursor: pointer; font-weight: bold; }
        
        #subjectGroup { display: none; } 
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
        
        <div class="day-selector">
            <label for="daySelect" style="font-weight:bold; margin-right:10px;">Select Day:</label>
            <select id="daySelect" onchange="changeDay(this.value)">
                <?php foreach($days_of_week as $day): ?>
                    <option value="<?php echo $day; ?>" <?php if($selected_day == $day) echo 'selected'; ?>>
                        <?php echo $day; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span style="margin-left: 15px; font-size: 14px; color: #555;">(Editing schedule for: <?php echo $target_date; ?>)</span>
        </div>

        <div id="tableView">
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
                            $capacity = $row['capacity'];
                            
                            echo "<tr><td>" . $room . "</td>";
                            
                            foreach($time_slots as $slot) {
                                $start = $slot[0]; $end = $slot[1];
                                
                                // Default State: Available
                                $status = "Available";
                                $subject = "";
                                $displayText = "0 / " . $capacity; 
                                $class = "status-available";

                                // Check DB
                                if (isset($booked_slots[$room][$start])) {
                                    $status = $booked_slots[$room][$start]['status'];
                                    $subject = $booked_slots[$room][$start]['subject'];
                                }

                                // Apply Logic based on Status
                                if ($status == "Unavailable") {
                                    $class = "status-unavailable";
                                    $displayText = $capacity . " / " . $capacity; // Max Capacity
                                } elseif ($status == "Class Ongoing") {
                                    $class = "status-ongoing";
                                    $displayText = "Class Ongoing<br>(" . $subject . ")";
                                }

                                echo "<td class='$class' 
                                          onclick=\"openEditForm('$room', '$capacity', '$start', '$end', '$status', '$subject')\">
                                          $displayText
                                      </td>";
                            }
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
            <a href="scheduleManagement.php" style="color: #555; text-decoration: none; font-weight: bold;">Back to Dashboard</a>
        </div>

        <div id="editFormSection">
            <h3 style="margin-bottom: 20px;">Edit Schedule</h3>
            
            <div class="form-group">
                <label>Day:</label>
                <input type="text" value="<?php echo $selected_day; ?>" disabled style="background:#eee;">
            </div>

            <div class="form-group">
                <label>Room:</label>
                <input type="text" id="editRoom" disabled style="background:#eee;">
            </div>

            <div class="form-group">
                <label>Timeslot:</label>
                <input type="text" id="editTime" disabled style="background:#eee;">
            </div>

            <div class="form-group">
                <label>Status:</label>
                <select id="editStatus" onchange="toggleSubjectField()">
                    <option value="Available">Available (Green)</option>
                    <option value="Class Ongoing">Class Ongoing (Orange)</option>
                    <option value="Unavailable">Unavailable (Red)</option>
                </select>
            </div>

            <div class="form-group" id="subjectGroup">
                <label>Subject:</label>
                <select id="editSubject">
                    <?php foreach($subjects_list as $sub): ?>
                        <option value="<?php echo $sub; ?>"><?php echo $sub; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-top: 30px;">
                <button class="btn-confirm" onclick="confirmUpdate()">Confirm</button>
                <button class="btn-cancel" onclick="closeEditForm()">Cancel</button>
            </div>
        </div>

    </div>

    <script>
    // Global State
    let selectedRoom = "";
    let selectedStart = "";
    let selectedEnd = "";
    let targetDate = "<?php echo $target_date; ?>"; // Pass PHP date to JS

    function changeDay(day) {
        // Reload page with new day parameter
        window.location.href = "edit-schedule.php?id=<?php echo $building_id; ?>&day=" + day;
    }

    function openEditForm(room, capacity, start, end, currentStatus, currentSubject) {
        selectedRoom = room;
        selectedStart = start;
        selectedEnd = end;

        // Populate Form
        document.getElementById('editRoom').value = room + " (Max: " + capacity + ")";
        document.getElementById('editTime').value = start.substring(0,5) + " - " + end.substring(0,5);
        document.getElementById('editStatus').value = currentStatus;
        
        // Handle Subject Field
        if (currentSubject) {
            document.getElementById('editSubject').value = currentSubject;
        }
        toggleSubjectField(); // Show/Hide subject based on status

        // Switch Views
        document.getElementById('tableView').style.display = 'none';
        document.getElementById('editFormSection').style.display = 'block';
    }

    function closeEditForm() {
        document.getElementById('editFormSection').style.display = 'none';
        document.getElementById('tableView').style.display = 'block';
    }

    function toggleSubjectField() {
        let status = document.getElementById('editStatus').value;
        let subjectGroup = document.getElementById('subjectGroup');
        
        if (status === "Class Ongoing") {
            subjectGroup.style.display = "block";
        } else {
            subjectGroup.style.display = "none";
        }
    }

    function confirmUpdate() {
        let newStatus = document.getElementById('editStatus').value;
        let newSubject = document.getElementById('editSubject').value;

        // If status is not "Class Ongoing", clear the subject so we don't save it
        if (newStatus !== "Class Ongoing") {
            newSubject = "";
        }

        fetch('save-schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                room_code: selectedRoom,
                start_time: selectedStart,
                end_time: selectedEnd,
                status: newStatus,
                subject: newSubject,
                date: targetDate
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
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