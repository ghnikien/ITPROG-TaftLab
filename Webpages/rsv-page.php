<?php
session_start();
include "db.php"; 

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// building id from POST request, GET parameter and default to 1 as fallback options
$building_id = 1;

if (isset($_POST['building_id'])) {
    $building_id = intval($_POST['building_id']);
} else if (isset($_GET['redirect_building'])) {
    $building_id = intval($_GET['redirect_building']);
}

// fetch building details; ensure a singular building is fetched
$sql_building = "
    SELECT building_code, building_name
    FROM building 
    WHERE building_id = ?
    LIMIT 1
";

// prepare and execute
$stmt_building = $conn->prepare($sql_building);
$stmt_building->bind_param("i", $building_id);
$stmt_building->execute();
$result_building = $stmt_building->get_result();

$building = ($result_building && $result_building->num_rows > 0)
            ? $result_building->fetch_assoc()
            : null;

if (!$building) {
    die("Building not found.");
}

// DLSU timeslots
$slots = [
    "07:30AM - 09:00AM" => ["07:30:00", "09:00:00"],
    "09:15AM - 10:45AM" => ["09:15:00", "10:45:00"],
    "11:00AM - 12:30PM" => ["11:00:00", "12:30:00"],
    "12:45PM - 02:15PM" => ["12:45:00", "14:15:00"],
    "02:30PM - 04:00PM" => ["14:30:00", "16:00:00"],
    "04:15PM - 05:45PM" => ["16:15:00", "17:45:00"],
    "06:00PM - 07:30PM" => ["18:00:00", "19:30:00"]
];

// obtain today's date and weekday
date_default_timezone_set('America/Chicago');
$today_date = date('Y-m-d');

// check if today is Sunday and if yes, block reservation access and redirect
$php_weekday = date('D');
if ($php_weekday === 'Sun') {
    die("Reservations are not available on Sundays.");
}

// map date('D') to our weekday format
$weekday_map = ['Mon'=>'Mon','Tue'=>'Tue','Wed'=>'Wed','Thu'=>'Thu','Fri'=>'Fri','Sat'=>'Sat','Sun'=>'Sun'];
$weekday = $weekday_map[$php_weekday] ?? $php_weekday;

// fetch laboratories in the building
$sql_labs = "
    SELECT lab_id, room_code, capacity, status
    FROM laboratory
    WHERE building_id = ?
    ORDER BY room_code ASC
";
// prepare and execute
$stmt_labs = $conn->prepare($sql_labs);
$stmt_labs->bind_param("i", $building_id);
$stmt_labs->execute();
$result_labs = $stmt_labs->get_result();
$labs = ($result_labs) ? $result_labs->fetch_all(MYSQLI_ASSOC) : [];

// helper funciton to get reservation count for a lab at a specific date and time
// the completed status is included to prevent exploitation by cancelling reservations to free up slots
function getReservationCount($conn, $lab_id, $date, $start, $end) {
    $sql = "
        SELECT COUNT(*) AS c
        FROM reservation
        WHERE lab_id = ?
        AND date_reserved = ?
        AND reserve_startTime = ?
        AND reserve_endTime = ?
        AND status IN ('Active', 'Completed')
    "; // counts only active and completed reservations since cancelled ones free up slots
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $lab_id, $date, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res ? intval($res->fetch_assoc()['c']) : 0);
}

// helper function to check if user has already reserved the same slot
function hasUserReservedSlot($conn, $user_id, $lab_id, $date, $start, $end) {
    $sql = "
        SELECT reservation_id
        FROM reservation
        WHERE user_id = ?
        AND lab_id = ?
        AND date_reserved = ?
        AND reserve_startTime = ?
        AND reserve_endTime = ?
        AND status = 'Active'
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $user_id, $lab_id, $date, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res && $res->num_rows > 0);
}

// to foster a 3 reservations per day limit, get count of user's reservations for today
// takes into account only active and completed (previous)  reservations made by the user
// the completed status is included to prevent exploitation by cancelling reservations to free up slots
function getUserReservationCountForDay($conn, $user_id, $date) {
    $sql = "
        SELECT COUNT(*) AS c
        FROM reservation
        WHERE user_id = ?
        AND date_reserved = ?
        AND status IN ('Active', 'Completed')
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res ? intval($res->fetch_assoc()['c']) : 0);
}

// check for class conflicts just in case
function hasClassConflict($conn, $lab_id, $weekday, $start, $end) {
    $sql = "
        SELECT cs.class_schedule_id
        FROM class_schedule cs
        JOIN existing_class ec ON cs.class_id = ec.class_id
        WHERE ec.lab_id = ?
        AND cs.class_day = ?
        AND NOT (cs.end_time <= ? OR cs.start_time >= ?)
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $lab_id, $weekday, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res && $res->num_rows > 0);
}

// helper function to check for restricted slots
function hasRestriction($conn, $lab_id, $date, $start, $end) {
    $sql = "
        SELECT restricted_slot_id
        FROM restricted_slots
        WHERE lab_id = ?
        AND restricted_date = ?
        AND NOT (end_time <= ? OR start_time >= ?)
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $lab_id, $date, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res && $res->num_rows > 0);
}

// helper function to convert time string to DateTime instantiation
function timeToDateTime($date, $timeStr){
    return new DateTime($date.' '.$timeStr);
}

// get user's reservation count for today
$user_id = $_SESSION['user_id'];
$userDayReservations = getUserReservationCountForDay($conn, $user_id, $today_date);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Page</title>
    <link rel="stylesheet" href="reservation.css">
</head>

<body>

<!-- nav bar-->
<header> 
    <div class="logo">
        <img src="images/taftlab-logo.png" alt="TaftLab Logo"/>
    </div>

    <div class="header-right">
      <nav>
        <ul> 
          <li><a href="rsv-history.php">My Reservations</a></li>
          <li><a href="#">Profile</a></li>
          <li><a href="logout.php">Logout</a></li>
        </ul>
      </nav>
      <div class="profile-icon">
        <img src="images/profile-icon.png" alt="Profile Icon"/>
      </div>
    </div>
</header>

<!-- sub-header containing the policies-->
<main class="container">
    <section class="guidelines">
        <h2>Reservation Guidelines</h2>
        <ol>
            <li>You can only book a slot for <strong>today</strong>.</li>
            <li>Service hours follow DLSU ITS policies: 07:30AM to 07:30PM.</li>
            <li>Click only the green-colored slots.</li>
            <li>After selecting, review details and confirm.</li>
            <li>You may cancel before the start time.</li>
            <li>You can reserve up to 3 times per day.</li>
            <li>You cannot reserve the same slot more than once.</li>
        </ol>
    </section>

    <!-- reservation timetable -->
    <section class="timetable">
        <h2><?= htmlspecialchars($building['building_name']) ?> — <?= date('F j, Y') ?></h2>
        <p style="color: #666; margin-bottom: 10px;">Reservations today: <strong><?= $userDayReservations ?>/3</strong></p>

        <!-- table is "wrapped" in the sense that it can be scrolled horizontally -->
        <div class="table-wrap">
            <table class="rsv-table" id="rsvTable">
                <thead>
                    <tr>
                        <!-- room and timeslot header colored in mint -->
                        <th class="mint-header">ROOM</th>
                        <?php foreach($slots as $label => $times): ?>
                            <th class="mint-header"><?= htmlspecialchars($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <!-- table body containing rooms and their respective slot statuses -->
                <tbody>
                <?php foreach ($labs as $lab): ?>
                    <tr>
                        <td class="roomcol"><?= htmlspecialchars($lab['room_code']); ?></td>

                        <?php foreach ($slots as $label => $times): 
                            $sstart = $times[0];
                            $send   = $times[1];

                            $slotEndDT = timeToDateTime($today_date,$send);
                            $now = new DateTime();

                            $cellClass = '';
                            $cellText  = '';
                            $isUserReserved = hasUserReservedSlot($conn, $user_id, $lab['lab_id'], $today_date, $sstart, $send);

                            if ($now > $slotEndDT) {
                                $cellClass = 'past';
                                $cellText = 'Past';
                            }

                            // if the user has already reserved this slot and the time is not past
                            else if ($isUserReserved && $now <= $slotEndDT) {
                                $cellClass = 'user-reserved';
                                $cellText = 'Your Slot';
                            }

                            // if not past, check for restrictions, class conflicts, and availability
                            else if (hasRestriction($conn, $lab['lab_id'], $today_date, $sstart, $send)) {
                                $cellClass = 'restricted';
                                $cellText = 'Restricted';
                            }
                            else if (hasClassConflict($conn, $lab['lab_id'], $weekday, $sstart, $send)) {
                                $cellClass = 'class';
                                $cellText = 'Class Ongoing';
                            }

                            // check capacity if deemed available
                            else {
                                $count = getReservationCount($conn, $lab['lab_id'], $today_date, $sstart, $send);
                                $capacity = intval($lab['capacity']);

                                if ($count >= $capacity) {
                                    $cellClass = 'full';
                                    $cellText = "$count/$capacity";
                                } else {
                                    $cellClass = 'available';
                                    $cellText = "$count/$capacity";
                                }
                            }

                            // then, prepare data attributes for the cell and format accordingly
                            $data_attrs = sprintf(
                                'data-labid="%d" data-room="%s" data-start="%s" data-end="%s" data-date="%s" data-count="%d" data-cap="%d" data-userreserved="%s"',
                                $lab['lab_id'],
                                htmlspecialchars($lab['room_code']),
                                $sstart,
                                $send,
                                $today_date,
                                $count ?? 0,
                                $capacity ?? intval($lab['capacity']),
                                $isUserReserved ? 'true' : 'false'
                            );
                        ?>

                        <td class="cell <?= $cellClass ?>" <?= $data_attrs ?>>
                            <div class="cell-inner"><?= htmlspecialchars($cellText) ?></div>
                        </td>

                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- legend and back button -->
        <div class="legend">
            <span class="legend-box available"></span> Available
            <span class="legend-box full"></span> Full
            <span class="legend-box class"></span> Class Ongoing
            <span class="legend-box restricted"></span> Restricted
            <span class="legend-box past"></span> Past
        </div>

        <div class="backwrap">
            <a href="homepage.php" class="btn back">Back</a>
        </div>
    </section>
</main>

<!-- modal -->
<!-- this modal is shown when a user clicks an available slot -->
<!-- for confirmation before proceeding to rsv-confirmation.php -->
<div id="modalBackdrop" class="modal-backdrop">
    <div class="modal" id="reserveModal">
        <h3 id="modalTitle">Reserve Slot</h3>
        <p id="modalMsg">Confirm reservation</p>

        <!-- reservation confirmation form -->
        <form id="reserveForm" method="post" action="rsv-confirmation.php">
            <input type="hidden" name="lab_id" id="f_lab_id">
            <input type="hidden" name="reserve_date" id="f_date">
            <input type="hidden" name="reserve_startTime" id="f_start">
            <input type="hidden" name="reserve_endTime" id="f_end">
            <input type="hidden" name="building_id" id="f_building_id" value="<?= $building_id ?>">

            <div class="form-row">
                <label>Room</label>
                <div id="f_room" class="readonly"></div>
            </div>

            <div class="form-row">
                <label>Time</label>
                <div id="f_time" class="readonly"></div>
            </div>

            <div class="form-row">
                <label>Slots</label>
                <div id="f_slots" class="readonly"></div>
            </div>

            <!-- modal action buttons, confirming directs the page to confirmation php file-->
            <div class="modal-actions">
                <button type="button" id="cancelBtn" class="btn">Cancel</button>
                <button type="submit" id="confirmBtn" class="btn primary">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const modalBackdrop = document.getElementById('modalBackdrop');
    const cancelBtn = document.getElementById('cancelBtn');
    const userDayReservations = <?= $userDayReservations ?>;

    function showModal(){ 
        modalBackdrop.style.display='flex'; 
    }
    
    function hideModal(){ 
        modalBackdrop.style.display='none'; 
    }

    cancelBtn.addEventListener('click', hideModal);
    modalBackdrop.addEventListener('click', e => { if(e.target===modalBackdrop) hideModal(); });

    document.querySelectorAll('.rsv-table .cell').forEach(td=>{
        td.addEventListener('click', function(){
            const isUserReserved = td.dataset.userreserved === 'true';
            
            // Check if user already reserved this slot
            if (isUserReserved) {
                alert("You have already reserved this slot.");
                return;
            }

            // Check if user has reached daily limit
            if (userDayReservations >= 3) {
                alert("You have reached the maximum of 3 reservations per day.");
                return;
            }

            if(!td.classList.contains('available')){
                alert("This slot cannot be reserved.");
                return;
            }

            const labid = td.dataset.labid;
            const room = td.dataset.room;
            const start = td.dataset.start;
            const end = td.dataset.end;
            const date = td.dataset.date;
            const count = td.dataset.count;
            const cap = td.dataset.cap;

            document.getElementById('f_lab_id').value = labid;
            document.getElementById('f_date').value = date;
            document.getElementById('f_start').value = start;
            document.getElementById('f_end').value = end;

            document.getElementById('f_room').textContent = room;
            document.getElementById('f_time').textContent = start + " - " + end;
            document.getElementById('f_slots').textContent = (cap - count) + " slots available";

            showModal();
        });
    });
});
</script>

</body>
</html>