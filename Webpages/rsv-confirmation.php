<?php
session_start();
include "db.php"; 

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// when back button is clicked, return to rsv-page.php with building_id for easy access to the previous page
if (isset($_POST['back_btn'])) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 1;
    ?>
    <!DOCTYPE html>
    <html>
    <body>
        <form id="backForm" method="post" action="rsv-page.php">
            <input type="hidden" name="building_id" value="<?= $building_id ?>">
        </form>
        <script>document.getElementById('backForm').submit();</script>
    </body>
    </html>
    <?php
    exit;
}

// validate reservation data fetched from the clicked slot. 
if (
    !isset($_POST['lab_id']) ||
    !isset($_POST['reserve_date']) ||
    !isset($_POST['reserve_startTime']) ||
    !isset($_POST['reserve_endTime'])
) {
    die("Invalid access. No reservation data received.");
}

// assign POST data to variables
$lab_id = intval($_POST['lab_id']);
$reserve_date = $_POST['reserve_date'];
$reserve_start = $_POST['reserve_startTime'];
$reserve_end = $_POST['reserve_endTime'];
$building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 1;

// fetch laboratory, building, and student from the selected lab id
$sql_res = "
    SELECT l.room_code, l.capacity, b.building_name, b.building_id
    FROM laboratory l
    JOIN building b ON l.building_id = b.building_id
    WHERE l.lab_id = $lab_id
";

$res = $conn->query($sql_res);
if (!$res || $res->num_rows === 0) {
    die("Invalid laboratory.");
}

// laboratory details
$lab = $res->fetch_assoc();
$building_id = $lab['building_id'];

// fetch user details in line with with student information 
$user_id = $_SESSION['user_id'];
$sql = "
    SELECT u.user_id, u.email, u.user_password, u.full_name, 
           s.student_type, s.department
    FROM user u
    LEFT JOIN student s ON u.user_id = s.user_id
    WHERE u.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();

// left div submission of verification (email and password)
$error = '';
if (isset($_POST['submit_cred'])) {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email === "" || $password === "") {
        $error = "Please fill out both fields.";
    } 
    
    else if ($email !== $user['email']) {
        $error = "Email does not match the logged-in account.";
    } 
    
    else if (!password_verify($password, $user['user_password'])) {
        $error = "Incorrect password.";
    } 
    
    else {
        // after validation, insert reservation into database
        $sql_insert = "
            INSERT INTO reservation (user_id, lab_id, date_reserved, reserve_startTime, reserve_endTime, status)
            VALUES (?, ?, ?, ?, ?, 'Active')
        ";
        
        // prepare and bind
        // bind parameters as fallback in case of failed direct insertion
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iisss", $user_id, $lab_id, $reserve_date, $reserve_start, $reserve_end);

        if ($stmt_insert->execute()) {
            // Show success message then redirect
            echo "<script>
                alert('Reservation successful!');
                window.location.href = 'rsv-page.php?redirect_building=" . intval($building_id) . "';
            </script>";
            exit;
        } else {
            $error = "Error while saving reservation: " . $stmt_insert->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Confirmation</title>
    <link rel="stylesheet" href="reservation.css">
</head>
<body>

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

<div class="sub-header">
    Confirmation of Reservation
</div>

<main class="container">
    <div class="hstry-confirm-wrapper">
        <div class="hstry-left-box">
            <h3>To proceed, confirm your student account.</h3>

            <form method="post" action="">
                <input type="hidden" name="lab_id" value="<?= $lab_id ?>">
                <input type="hidden" name="reserve_date" value="<?= $reserve_date ?>">
                <input type="hidden" name="reserve_startTime" value="<?= $reserve_start ?>">
                <input type="hidden" name="reserve_endTime" value="<?= $reserve_end ?>">
                <input type="hidden" name="building_id" value="<?= $building_id ?>">

                <label>Email Address</label>
                <input type="text" name="email" class="hstry-input" placeholder="Enter your DLSU email">

                <label>Password</label>
                <input type="password" name="password" class="hstry-input" placeholder="Enter your password">

                <div class="button-group">
                    <button type="submit" name="submit_cred" class="hstry-btn-submit">Confirm</button>
                    <button type="submit" name="back_btn" class="hstry-btn-back">Back</button>
                </div>

                <?php if ($error != ''): ?>
                    <div class="hstry-error"><?= $error ?></div>
                <?php endif; ?>
            </form>
        </div>

        <div class="hstry-divider"></div>
        <div class="hstry-right-box">
            <div class="hstry-details-header">RESERVATION DETAILS</div>

            <div class="hstry-details-row"><span>Building:</span> <?= htmlspecialchars($lab['building_name']) ?></div>
            <div class="hstry-details-row"><span>Room:</span> <?= htmlspecialchars($lab['room_code']) ?></div>
            <div class="hstry-details-row"><span>Date:</span> <?= htmlspecialchars(date("l, F j, Y", strtotime($reserve_date))) ?></div>
            <div class="hstry-details-row"><span>Start Time:</span> <?= htmlspecialchars(date("h:i A", strtotime($reserve_start))) ?></div>
            <div class="hstry-details-row"><span>End Time:</span> <?= htmlspecialchars(date("h:i A", strtotime($reserve_end))) ?></div>


            <div class="hstry-details-row"><span>Student:</span> 
                <?= htmlspecialchars($user['full_name'] ?? 'N/A') ?>
            </div>

            <div class="hstry-details-row"><span>Department:</span> 
                <?= htmlspecialchars($user['department'] ?? 'N/A') ?>
            </div>
        </div>
                    
    </div>

</main>

</body>
</html>
