<?php
session_start();
include "db.php";

// Check if user is logged in; session check
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

// get user ID from session
$user_id = intval($_SESSION['user_id']);
// current date/time for status updates
date_default_timezone_set('Asia/Manila');
// current date and time
$nowDate = date("Y-m-d");
$nowTime = date("H:i:s");

// update past active reservations to completed
// dynamic update based on current date/time wherein when the reservation's endTime has passed
$updateCompleted = "
    UPDATE reservation
    SET status='Completed'
    WHERE user_id = $user_id
      AND status='Active'
      AND (
           date_reserved < '$nowDate'
           OR (date_reserved = '$nowDate' AND reserve_endTime < '$nowTime')
      )
";
$conn->query($updateCompleted);


// cancel reservations upon user request invoked in the modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === "cancel") {
    $res_id = intval($_POST['reservation_id']);

    $sql = "UPDATE reservation SET status='Cancelled' WHERE reservation_id=$res_id AND user_id=$user_id";
    $conn->query($sql);

    echo json_encode(["success" => true]);
    exit;
}


// fetch reservation details for rescheduling that was invoked in the modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === "fetch") {
    $res_id = intval($_POST['reservation_id']);
    $q = "
        SELECT r.*, 
               b.building_name, b.building_code,
               l.room_code
        FROM reservation r 
        JOIN laboratory l ON r.lab_id = l.lab_id
        JOIN building b ON l.building_id = b.building_id
        WHERE r.reservation_id = $res_id
          AND r.user_id = $user_id
    ";
    $result = $conn->query($q);
    $data = $result->fetch_assoc();

    // timeslots list
    $timeSlots = [
        ["07:30:00","09:00:00"],
        ["09:15:00","10:45:00"],
        ["11:00:00","12:30:00"],
        ["12:45:00","14:15:00"],
        ["14:30:00","16:00:00"],
        ["16:15:00","17:45:00"],
        ["18:00:00","19:30:00"]
    ];

    // temporary array to hold available slots
    $available = [];

    foreach ($timeSlots as $slot) {
        $s = $slot[0];
        $e = $slot[1];

        // skip slots already finished today
        if ($data['date_reserved'] == $nowDate && $e < $nowTime) 
            continue;

        // check conflict
        $check = "
            SELECT * FROM reservation
            WHERE lab_id = {$data['lab_id']}
              AND date_reserved = '{$data['date_reserved']}'
              AND status='Active'
              AND (
                    ('$s' BETWEEN reserve_startTime AND reserve_endTime)
                 OR ('$e' BETWEEN reserve_startTime AND reserve_endTime)
              )
              AND reservation_id != $res_id
        ";
        $conflict = $conn->query($check);

        if ($conflict->num_rows == 0) {
            $available[] = [
                "start" => $s,
                "end"   => $e,
                "readable" => date("h:i A", strtotime($s)) . " - " . date("h:i A", strtotime($e))
            ];
        }
    }

    echo json_encode([
        "success" => true,
        "details" => $data,
        "available" => $available
    ]);
    exit;
}


// peerform rescheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === "reschedule") {
    $res_id = intval($_POST['reservation_id']);
    $new_start = $_POST['new_start'];
    $new_end = $_POST['new_end'];

    // update reservation
    $sql = "
        UPDATE reservation 
        SET reserve_startTime = '$new_start',
            reserve_endTime = '$new_end'
        WHERE reservation_id = $res_id
          AND user_id = $user_id
    ";

    $conn->query($sql);

    echo json_encode(["success" => true]);
    exit;
}


// fetch all reservations of the user
// ordered by status and date and then time
$sql = "
    SELECT r.*, 
           b.building_name, b.building_code,
           l.room_code
    FROM reservation r
    JOIN laboratory l ON r.lab_id = l.lab_id
    JOIN building b ON l.building_id = b.building_id
    WHERE r.user_id = $user_id
    ORDER BY 
        CASE 
            WHEN r.status = 'Active' THEN 1
            WHEN r.status = 'Completed' THEN 2
            WHEN r.status = 'Cancelled' THEN 3
            ELSE 4
        END,
        r.date_reserved DESC,
        r.reserve_startTime DESC
";

$result = $conn->query($sql);

// helper function for building images based on building code
function buildingImg($code){
    switch($code){
        case "AG": return "images/AG_1904_indoor_1.jpg";
        case "GK": return "images/GK_304B_indoor_1.jpg";
        case "LS": return "images/LS_229_indoor_1.jpg";
        case "V":  return "images/V_103_indoor_3.jpg";
        case "Y":  return "images/Y_602_indoor_1.jpg";
        default: return "images/default-lab.jpg";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Reservations</title>
    <link rel="stylesheet" href="rsv-history.css">
    <style>
        /* modal inline design */
        #reschedModal {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.6); justify-content:center; align-items:center;
        }
        .modal-content {
            background:white; padding:25px; width:420px; border-radius:10px;
        }
    </style>
</head>

<body>

<!-- header -->
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

<div class="title-bar"><h1>My Reservations</h1></div>

<!-- view 1: the reservation list with filter; the default view -->
<div id="reservationListView">

    <!-- filter row -->
    <div class="filter-row">
        <select id="filterSelect">
            <option value="All">All</option>
            <option value="Active">Active</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>
        <button onclick="applyFilter()">Filter</button>
    </div>

    <!-- reservation cards  -->
    <div id="cardContainer">
    <?php while($row = $result->fetch_assoc()): ?>
        <?php
            $img = buildingImg($row['building_code']);
            $date = date("F j, Y", strtotime($row['date_reserved']));
        ?>
        
        <!-- single reservation card with pertinent reservation details-->
        <div class="reservation-card" data-status="<?= $row['status'] ?>">
            <img src="<?= $img ?>" class="card-image">

            <!-- reservation info -->
            <div class="card-info">
                <h2><?= $row['building_name'] ?></h2>
                <h3><?= $row['room_code'] ?></h3>
                <p>
                    <?= $date ?><br>
                    <?= date("h:i A", strtotime($row['reserve_startTime'])) ?>
                    -
                    <?= date("h:i A", strtotime($row['reserve_endTime'])) ?>
                </p>

                <!-- action buttons based on status -->
                <!-- only active reservations can be rescheduled or cancelled -->
                <?php if ($row['status'] == "Active"): ?>
                    <button class="btn-green" onclick="openModal(<?= $row['reservation_id'] ?>)">Resched</button>
                    <button class="btn-red" onclick="cancelRes(<?= $row['reservation_id'] ?>)">Cancel</button>
                <?php elseif ($row['status'] == "Completed"): ?>
                    <p class="status-completed">COMPLETED</p>
                <?php else: ?>
                    <p class="status-cancelled">CANCELLED</p>
                <?php endif; ?>
            </div>
        </div>

    <?php endwhile; ?>

    </div>

    <!-- back button to homepage -->
    <button class="back-btn" onclick="window.location.href='homepage.php'">Back</button>
</div>

<!-- view 2: reschedule portal modal -->
<div id="reschedModal">
    <div class="modal-content">
        <h2>Reschedule Reservation</h2>
        <!-- reservation details -->
        <div id="modalReservationDetails"></div>

        <!-- timeslot dropdown -->
        <!-- users are only allowed to select from timeslots starting now till the end of the day that are not conflicting with other reservations -->
        <label>Choose New Timeslot</label>
        <select id="slotDropdown"></select>

        <div class="modal-actions">
            <div class="modal-actions-inner">
                <button class="modal-btn" onclick="closeModal()">Back</button>
                <button class="modal-btn" onclick="submitReschedule()">Confirm</button>
            </div>
        </div>


    </div>
</div>


<script>
// global variable to track current reservation being rescheduled
let currentResID = 0;

// helper function to filter reservation cards
function applyFilter(){
    let filter = document.getElementById("filterSelect").value;
    document.querySelectorAll(".reservation-card").forEach(c => {
        if (filter === "All" || c.dataset.status === filter)
            c.style.display = "flex";
        else
            c.style.display = "none";
    });
}

// cancel reservation based on invoked reservation ID
function cancelRes(id){
    if (!confirm("Cancel this reservation?")) 
        return;

    let form = new FormData();
    form.append("action","cancel");
    form.append("reservation_id",id);

    // in the same page, send POST request to cancel reservation and reload page after
    fetch("rsv-history.php", { method:"POST", body:form })
    .then(r=>r.json())
    .then(() => location.reload());
}

// helper function to open reschedule modal
function openModal(id){
    currentResID = id;

    let form = new FormData();
    form.append("action","fetch");
    form.append("reservation_id",id);

    // in the same page, send POST request to fetch reservation details and available timeslots
    fetch("rsv-history.php", { method:"POST", body:form })
    .then(r=>r.json())
    .then(data => {

        // populate modal with fetched data
        let d = data.details;

        document.getElementById("modalReservationDetails").innerHTML =
        `<p>
            <b>${d.building_name}</b><br>
            ${d.room_code}<br>
            ${d.date_reserved}<br>
            ${formatTime(d.reserve_startTime)} - ${formatTime(d.reserve_endTime)}
        </p>`;

        // populate timeslot dropdown
        let dd = document.getElementById("slotDropdown");
        dd.innerHTML = "";

        data.available.forEach(slot => {
            let op = document.createElement("option");
            op.value = slot.start + "|" + slot.end;
            op.textContent = slot.readable;
            dd.appendChild(op);
        });

        document.getElementById("reschedModal").style.display = "flex";
    });
}

// close reschedule modal when back button is clicked
function closeModal(){
    document.getElementById("reschedModal").style.display = "none";
}

// submit invoked reschedule request
function submitReschedule(){

    // get selected timeslot and parse start/end times
    let val = document.getElementById("slotDropdown").value.split("|");
    let s = val[0];
    let e = val[1];

    // send to backend
    let form = new FormData();
    form.append("action","reschedule");
    form.append("reservation_id", currentResID);
    form.append("new_start", s);
    form.append("new_end", e);

    // in the same page, send POST request to reschedule reservation and reload page after
    fetch("rsv-history.php", { method:"POST", body:form })
    .then(r=>r.json())
    .then(() => location.reload());
}


// helper function to format time strings
function formatTime(t){
    let d = new Date("2020-01-01 "+t);
    return d.toLocaleTimeString([], {hour:"2-digit", minute:"2-digit"});
}

</script>

</body>
</html>