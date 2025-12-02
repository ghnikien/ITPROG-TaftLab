<?php
include "db.php";

$building_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_date = date('Y-m-d'); 

// Fetch Building Name
$sql_building = "SELECT building_name FROM building WHERE building_id = $building_id";
$result_building = $conn->query($sql_building);
$building_name = ($result_building->num_rows > 0) ? $result_building->fetch_assoc()['building_name'] : "Unknown Building";

// Fetch Rooms
$sql_rooms = "SELECT room_code FROM laboratory WHERE building_id = $building_id ORDER BY room_code";
$result_rooms = $conn->query($sql_rooms);

// Fetch Existing Reservations (So the page loads correctly)
$booked_slots = [];
$sql_reservations = "SELECT l.room_code, r.reserve_startTime 
                     FROM reservation r 
                     JOIN laboratory l ON r.lab_id = l.lab_id 
                     WHERE l.building_id = $building_id 
                     AND r.date_reserved = '$current_date' 
                     AND r.status = 'Active'";
$result_res = $conn->query($sql_reservations);

if ($result_res) {
    while($row = $result_res->fetch_assoc()) {
        $booked_slots[$row['room_code']][$row['reserve_startTime']] = true;
    }
}

$time_slots = [
    ["07:30:00", "09:00:00"],
    ["09:15:00", "10:45:00"],
    ["11:00:00", "12:30:00"],
    ["12:45:00", "14:15:00"],
    ["14:30:00", "16:00:00"],
    ["16:15:00", "17:45:00"]
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
        .schedule-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .schedule-table th, .schedule-table td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        .schedule-table th { background-color: #f2f2f2; font-weight: 700; }
        .schedule-table td:first-child { font-weight: 800; background-color: #eee; width: 100px; }

        /* Status Colors */
        .status-vacant { background-color: #28a745; color: white; cursor: pointer; transition: 0.2s; }
        .status-occupied { background-color: #dc3545; color: white; cursor: pointer; transition: 0.2s; }
        .status-vacant:hover, .status-occupied:hover { opacity: 0.8; transform: scale(1.05); }

        /* Button Container */
        .action-buttons {
            display: flex;
            justify-content: flex-end; /* Puts Proceed button on the right */
            gap: 15px;
            margin-top: 20px;
        }

        .back-btn { 
            background-color: #888; color: white; padding: 12px 25px; 
            text-decoration: none; border-radius: 5px; font-weight: bold; border: none; font-size: 14px;
        }
        
        .proceed-btn { 
            background-color: #006937; color: white; padding: 12px 25px; 
            text-decoration: none; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; font-size: 14px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .proceed-btn:hover { background-color: #004d29; }
        
        .unsaved-warning { color: #dc3545; font-weight: bold; margin-right: 15px; display: none; }
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
        <p style="margin-bottom:15px; font-weight:600; color:#555;">Click cells to toggle status. Click "Proceed to Update" to save.</p>

        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Room</th>
                    <?php foreach ($time_slots as $slot) echo "<th>" . substr($slot[0], 0, 5) . " - " . substr($slot[1], 0, 5) . "</th>"; ?>
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
                            $is_booked = isset($booked_slots[$room][$start]);
                            $status_text = $is_booked ? "Occupied" : "Vacant";
                            $class = $is_booked ? "status-occupied" : "status-vacant";

                            // IMPORTANT: We added 'data-changed="false"' to track changes
                            echo "<td class='$class' 
                                      data-room='$room' 
                                      data-start='$start' 
                                      data-end='$end' 
                                      data-status='$status_text' 
                                      data-changed='false'
                                      onclick='toggleSlot(this)'>
                                      $status_text
                                  </td>";
                        }
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>

        <div class="action-buttons">
            <span class="unsaved-warning" id="warningMsg">You have unsaved changes!</span>
            <a href="scheduleManagement.php" class="back-btn">Back</a>
            <button class="proceed-btn" onclick="saveAllChanges()">Proceed to Update</button>
        </div>
    </div>

    <script>
    // 1. Toggle Visuals Only
    function toggleSlot(cell) {
        let currentStatus = cell.getAttribute('data-status');
        let newStatus = (currentStatus === "Vacant") ? "Occupied" : "Vacant";

        // Update Text and Color
        cell.innerText = newStatus;
        cell.className = (newStatus === "Occupied") ? "status-occupied" : "status-vacant";
        
        // Update Data Attributes
        cell.setAttribute('data-status', newStatus);
        
        // Mark as Changed
        // If it was already marked changed, we keep it changed. 
        // Logic: We just track that *something* happened to this cell.
        cell.setAttribute('data-changed', 'true');

        // Show warning text
        document.getElementById('warningMsg').style.display = "inline";
    }

    // 2. Collect and Send Data
    function saveAllChanges() {
        let cells = document.querySelectorAll('td[data-changed="true"]');
        let changes = [];

        if (cells.length === 0) {
            alert("No changes to save.");
            return;
        }

        cells.forEach(cell => {
            changes.push({
                room_code: cell.getAttribute('data-room'),
                start_time: cell.getAttribute('data-start'),
                end_time: cell.getAttribute('data-end'),
                status: cell.getAttribute('data-status')
            });
        });

        // Send to Backend
        fetch('save-schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(changes)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Successfully updated " + data.processed + " slots!");
                location.reload(); // Refresh page to show fresh data
            } else {
                alert("Error saving: " + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    </script>

</body>
</html>