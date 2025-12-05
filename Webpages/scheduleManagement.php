<?php
include "db.php"; 

// Query to get buildings and their rooms
$sql = "SELECT 
            b.building_id, 
            b.building_name, 
            COUNT(l.lab_id) as room_count, 
            GROUP_CONCAT(l.room_code SEPARATOR ', ') as room_list 
        FROM building b 
        LEFT JOIN laboratory l ON b.building_id = l.building_id 
        GROUP BY b.building_id
        ORDER BY b.building_id ASC";
$result = $conn->query($sql);

function getBuildingImage($name) {
    if (stripos($name, 'Salle') !== false) return 'images/LS_229_indoor_1.jpg';
    if (stripos($name, 'Gokongwei') !== false) return 'images/GK_304B_indoor_1.jpg';
    if (stripos($name, 'Andrew') !== false) return 'images/AG_1904_indoor_1.jpg';
    if (stripos($name, 'Yuchengco') !== false) return 'images/Y_602_indoor_1.jpg';
    if (stripos($name, 'Velasco') !== false) return 'images/V_103_indoor_3.jpg';
    return 'images/taftlab-logo.png';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management</title>
    
    <link rel="stylesheet" href="scheduleManagement.css">
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
                    <li><a href="#">Profile</a></li>
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
        <h1>Schedule Management</h1>
    </div>

    <div class="schedule-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="schedule-card">
                    <div class="card-image">
                        <img src="<?php echo getBuildingImage($row['building_name']); ?>" alt="Building">
                    </div>
                    <div class="card-details">
                        <h3><?php echo htmlspecialchars($row['building_name']); ?></h3>
                        <p class="count"><?php echo $row['room_count']; ?> classrooms total</p>
                        <p class="rooms">
                            <?php echo $row['room_list'] ? $row['room_list'] : "No rooms added yet"; ?>
                        </p>
                    </div>
                    <a href="edit-schedule.php?id=<?php echo $row['building_id']; ?>" class="edit-btn">Edit</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center;">No buildings found in the database.</p>
        <?php endif; ?>
    </div>

</body>
</html>