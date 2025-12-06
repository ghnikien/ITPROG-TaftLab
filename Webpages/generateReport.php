<?php

include "db.php";

session_start();
if (!isset($_SESSION['user_id'])) {
	header("Location: login.php");
	exit();
}

// Helper function - XML Escape
function xmlEscape($str) {
	return htmlspecialchars($str, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

// Read search filters 
$timeScope		= $_GET['time_scope'] ?? 'ALL';
$fromDate		= $_GET['from_date'] ?? '';
$toDate			= $_GET['to_date'] ?? '';
$buildingSel	= $_GET['building_id'] ?? 'ALL';
$downloadXml	= isset($_GET['download']) && $_GET['download'] == 1;

$startDate	= null;
$endDate	= null;

// Date Range Logic

// If timeScope that isn't 'ALL' has an empty fromDate
if ($timeScope !== 'ALL' && empty($fromDate)) {
    // Assign a impossible date range so SQL returns nothing, rough solution but it works xd
    $startDate = '9999-12-31';
    $endDate = '9999-12-31';
}

if (!empty($fromDate)) {

	try {
		$from = new DateTime($fromDate);

		switch ($timeScope) {

			case 'DAY':
				$startDate = $endDate = $from->format('Y-m-d');
				break;

			case 'WEEK':
				$wkStart = clone $from; 
				$wkStart->modify('monday this week');

				$wkEnd = clone $wkStart; 
				$wkEnd->modify('sunday this week');

				$startDate = $wkStart->format('Y-m-d');
				$endDate   = $wkEnd->format('Y-m-d');
				break;

			case 'MONTH':
				$startDate = $from->format('Y-m-01');
				$endDate   = $from->format('Y-m-t');
				break;

			case 'YEAR':
				$startDate = $from->format('Y-01-01');
				$endDate   = $from->format('Y-12-31');
				break;
		}
	}
	catch (Exception $e) {
		$startDate = $endDate = null;
		$timeScope = 'ALL';
	}

}

// Build the SQL WHERE Clause
$where = [];

if ($startDate !== null && $endDate !== null) {	// Date from-to filter
	$startEsc	= $conn->real_escape_string($startDate);
	$endEsc		= $conn->real_escape_string($endDate);
	$where[]	= "r.date_reserved BETWEEN '{$startEsc}' AND '{$endEsc}'";
}

$buildingCode = null;
$buildingName = null;

if ($buildingSel !== 'ALL') {	// Building filter (if buildingSel is NOT set at 'ALL')
	$bid = (int)$buildingSel;
	$where[] = "b.building_id = {$bid}";
}

// Main SQL Query for fetching reservation, user, lab, and building details
$sql = "
	SELECT 
		r.reservation_id,
		r.date_reserved,
		r.reserve_startTime,
		r.reserve_endTime,
		r.status,
		u.user_id,
		u.full_name,
		u.user_type,
		l.lab_id,
		l.room_code,
		b.building_id,
		b.building_code,
		b.building_name
	FROM reservation r
	INNER JOIN user u ON r.user_id = u.user_id
	INNER JOIN laboratory l ON r.lab_id = l.lab_id
	INNER JOIN building b ON l.building_id = b.building_id
";

if (!empty($where)) {
	$sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY r.date_reserved, r.reserve_startTime";

$result = $conn->query($sql);
$reservations = [];
$buildingCounts = [];
$roomCounts = [];
$userCounts = [];

$totalReservations = 0;

while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
    $totalReservations++;

    // Count per building
    $bCode = $row['building_code'];
    if (!isset($buildingCounts[$bCode])) {
        $buildingCounts[$bCode] = 0;
    }
    $buildingCounts[$bCode]++;

    // Count per room
    $room = $row['room_code'];
    if (!isset($roomCounts[$room])) {
        $roomCounts[$room] = 0;
    }
    $roomCounts[$room]++;

    // Count per user
    $user = $row['full_name'];
    if (!isset($userCounts[$user])) {
        $userCounts[$user] = 0;
    }
    $userCounts[$user]++;
}

// Determine top performing building (only if $buildingSel == 'ALL')
$topBuilding = null;
$topBuildingCount = 0;
if ($buildingSel == 'ALL' && !empty($buildingCounts)) {
    $topBuildingCount = max($buildingCounts);
    $topBuilding = array_keys($buildingCounts, $topBuildingCount);
}

// Determine top performing room(s)
if (!empty($roomCounts)) {
    $topRoomCount = max($roomCounts);
    $topRooms = array_keys($roomCounts, $topRoomCount);
} else {
    $topRoomCount = 0;
    $topRooms = [];
}

// Determine top performing student(s)
if (!empty($userCounts)) {
    $topStudentCount = max($userCounts);
    $topStudents = array_keys($userCounts, $topStudentCount);
} else {
    $topStudentCount = 0;
    $topStudents = [];
}

// Assign building name for XML generation
$buildingName = "";

if ($buildingSel != 'ALL') {
    $stmt = $conn->prepare("SELECT building_name FROM building WHERE building_id = ?");
    $stmt->bind_param("i", $buildingSel);
    $stmt->execute();
    $stmt->bind_result($buildingName);
    $stmt->fetch();
    $stmt->close();
}

// Fetch building list ($buildingList) for its dropdown menu
$buildingList = [];
$bQuery = $conn->query("SELECT * FROM building ORDER BY building_code");
while ($row = $bQuery->fetch_assoc()) $buildingList[] = $row;

// XML Generation "title" conditions
$currentLabel = date('Y-m-d_His');
$title = "Reservation Summary Report";

// CASE 1: ALL time, ALL buildings
if ($timeScope == 'ALL' && $buildingSel == 'ALL') {
    $title = "Reservation Summary Report";
}

// CASE 2: ALL time, specific building
else if ($timeScope == 'ALL' && $buildingSel != 'ALL') {
    $title = "Reservation Summary Report - " . $buildingName;
}

// CASE 3: Specific time, ALL buildings
else if ($timeScope != 'ALL' && $buildingSel == 'ALL') {
    $title = "Reservation Summary Report - " . $currentLabel;
}

// CASE 4: Specific time, specific building
else if ($timeScope != 'ALL' && $buildingSel != 'ALL') {
    $title = "Reservation Summary Report - " . $buildingName . " - " . $currentLabel;
}

// XML Generation
if ($downloadXml) {
    header("Content-Type: application/xml; charset=UTF-8");
    header("Content-Disposition: attachment; filename=summary_report_" . date('Y-m-d_His') . ".xml");

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<!DOCTYPE reservationSummary SYSTEM \"summaryReport.dtd\">\n";

	echo "<reservationSummary generatedAt=\"" . xmlEscape(date('c')) . "\" timeScope=\"$timeScope\">\n";
	echo "  <title>" . xmlEscape($title) . "</title>\n";

    echo "  <filters>\n";
    echo "    <dateRange from=\"" . xmlEscape($startDate) . "\" to=\"" . xmlEscape($endDate) . "\" />\n";

    echo "    <buildingFilter scope=\"" . ($buildingSel == 'ALL' ? "ALL" : "SPECIFIC") . "\"";
    if ($buildingSel !== 'ALL') {
        echo " buildingId=\"" . xmlEscape($buildingSel) . "\"";
        echo " buildingCode=\"" . xmlEscape($buildingCode) . "\"";
        echo " buildingName=\"" . xmlEscape($buildingName) . "\"";
    }
    echo " />\n";
    echo "  </filters>\n";

	echo "  <summary>\n";

	echo "      <totalReservations>"
		. xmlEscape($totalReservations)
		. "</totalReservations>\n";

	// Top building(s)
	echo "      <topBuildings>\n";
	if ($buildingSel == 'ALL') {
		foreach ($topBuilding as $b) {
			echo "          <building code=\"" . xmlEscape($b) .
				"\" count=\"" . xmlEscape($topBuildingCount) . "\" />\n";
		}
	}
	echo "      </topBuildings>\n";

	// Top room(s)
	echo "      <topRooms>\n";
	foreach ($topRooms as $room) {
		echo "          <room name=\"" . xmlEscape($room) .
			"\" count=\"" . xmlEscape($topRoomCount) . "\" />\n";
	}
	echo "      </topRooms>\n";

	// Top student(s)
	echo "      <topStudents>\n";
	foreach ($topStudents as $student) {
		echo "          <student name=\"" . xmlEscape($student) .
			"\" count=\"" . xmlEscape($topStudentCount) . "\" />\n";
	}
	echo "      </topStudents>\n";

	echo "  </summary>\n";

    echo "  <reservations>\n";
    foreach ($reservations as $r) {
        echo "    <reservation id=\"{$r['reservation_id']}\" dateReserved=\"{$r['date_reserved']}\" startTime=\"{$r['reserve_startTime']}\" endTime=\"{$r['reserve_endTime']}\" status=\"{$r['status']}\">\n";
        echo "      <user id=\"{$r['user_id']}\" fullName=\"" . xmlEscape($r['full_name']) . "\" userType=\"{$r['user_type']}\" />\n";
        echo "      <lab id=\"{$r['lab_id']}\" roomCode=\"{$r['room_code']}\" buildingId=\"{$r['building_id']}\" buildingCode=\"{$r['building_code']}\" buildingName=\"" . xmlEscape($r['building_name']) . "\" />\n";
        echo "    </reservation>\n";
    }
    echo "  </reservations>\n";

    echo "</reservationSummary>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Generate Summary Report</title>
	<link rel="stylesheet" href="admin-homepage.css">
	<link rel="stylesheet" href="generateReport.css">
</head>

<body>

	<header>
		<div class="logo">
			<img src="images/taftlab-logo.png" alt="Taft Lab Logo">
		</div>
		<div class="header-right">
			<nav>
				<ul>
					<li><a href="admin-homepage.php">Back to Dashboard</a></li>
					<li><a href="admin-profile.php">Profile</a></li>
					<li><a href="login.php">Logout</a></li>
				</ul>
			</nav>
			<div class="profile-section">
				<div class="profile-icon">
					<img src="images/profile-icon.png" alt="User">
				</div>
			</div>
		</div>
	</header>

	<div class="title-bar">
		<h1>Generate Summary Report</h1>
	</div>

	<div class="page-container">
		<form method="GET">
			<div class="report-content">

				<!-- Time Filter Box -->
				<div class="filters-box">
					<h3>Filters</h3>
					<label><input type="radio" name="time_scope" value="ALL" <?= $timeScope=='ALL'?'checked':'' ?>> All</label><br>
					<label><input type="radio" name="time_scope" value="DAY" <?= $timeScope=='DAY'?'checked':'' ?>> Daily</label><br>
					<label><input type="radio" name="time_scope" value="WEEK" <?= $timeScope=='WEEK'?'checked':'' ?>> Weekly</label><br>
					<label><input type="radio" name="time_scope" value="MONTH" <?= $timeScope=='MONTH'?'checked':'' ?>> Monthly</label><br>
					<label><input type="radio" name="time_scope" value="YEAR" <?= $timeScope=='YEAR'?'checked':'' ?>> Yearly</label>
				</div>

				<!-- Date Range Filter Box -->
				<div class="range-box">
					<h3>Range</h3>

					<label>From:</label>
					<input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>">

					<label>To:</label>
					<input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>">

					<label>Building:</label>
					<select name="building_id" size="6">
						<option value="ALL" <?= $buildingSel=='ALL'?'selected':'' ?>>- All Buildings -</option>
						<?php foreach ($buildingList as $b): ?>
							<option value="<?= $b['building_id'] ?>" <?= $buildingSel==$b['building_id']?'selected':'' ?>>
								<?= htmlspecialchars($b['building_code'] . " - " . $b['building_name']) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

			</div>

			<div class="buttons-row">
				<button type="submit" class="admin-btn">Preview</button>
				<button type="submit" name="download" value="1" class="admin-btn">Generate XML</button>
				<a href="admin-homepage.php" class="admin-btn">Back</a>
			</div>

		</form>

	</div>

	<!-- Preview Table Output -->

	<?php if (!empty($reservations)): ?>
	
	<!-- [Total reservations] -->

	<div class="summary-stats">

		<h2>Summary</h2>
		
		<h4>Title of the report: <?= $title ?></h4>

		<h4>Total Reservations: <?= $totalReservations ?></h4>

		<?php if ($buildingSel == 'ALL'): ?>
			<h4>Top Performing Building(s):
				<?php foreach ($topBuilding as $b): ?>
					<?= htmlspecialchars($b) ?> [Count = <?= $topBuildingCount ?>]
				<?php endforeach; ?>
			</h4>
		<?php endif; ?>

		<h4>Top Performing Room(s):
			<?php foreach ($topRooms as $r): ?>
				<?= htmlspecialchars($r) ?> [Count = <?= $topRoomCount ?>]
			<?php endforeach; ?>
		</h4>

		<h4>Student(s) with Most Reservations:
			<?php foreach ($topStudents as $u): ?>
				<?= htmlspecialchars($u) ?> [Count = <?= $topStudentCount ?>]
			<?php endforeach; ?>
		</h4>
	</div>
	
	<!-- [Table of user reservation data]-->
	
	<table class="report-table">
		<tr>
			<th>Date</th>
			<th>Time</th>
			<th>User</th>
			<th>User Type</th>
			<th>Building</th>
			<th>Room</th>
			<th>Status</th>
		</tr>

		<?php foreach ($reservations as $r): ?>
		<tr>
			<td><?= $r['date_reserved'] ?></td>
			<td><?= substr($r['reserve_startTime'],0,5) ?> - <?= substr($r['reserve_endTime'],0,5) ?></td>
			<td><?= htmlspecialchars($r['full_name']) ?></td>
			<td><?= $r['user_type'] ?></td>
			<td><?= $r['building_code'] ?></td>
			<td><?= $r['room_code'] ?></td>
			<td><?= $r['status'] ?></td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php else: ?>
	<p class="no-data">No reservations found for the selected filters.</p>
	<?php endif; ?>

</body>
</html>