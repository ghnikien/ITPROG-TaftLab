<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Homepage</title>
    <link rel="stylesheet" href="homepage-carousel.css">
    <link rel="stylesheet" href="admin-homepage.css">
</head>
<body>
    <header> 
        <div class="logo">
            <img src="images/taftlab-logo.png" alt="TaftLab Logo"/>
        </div>

    <div class="header-right">
      <nav>
        <ul> 
          <li><a href="#">History</a></li>
          <li><a href="#">Profile</a></li>
          <li><a href="login.php">Logout</a></li>
        </ul>
      </nav>
      <div class="profile-icon">
        <img src="images/profile-icon.png" alt="Profile Icon"/>
      </div>
    </div>
  </header>

  <div class="admin-subheader">
    <h2 class=>Home Page - Admin</h2>
  </div>

  <div class="box">
    <img src="images/Y_602_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
        <h3> Room Management </h3>
        <p> Add, edit, or remove classrooms and manage their details to keep information up to date. </p>
        <a href="roomManagement-main.php" class="admin-btn">Manage Rooms</a>
    </div>
  </div>

  <div class="box">
    <img src="images/Y_602_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
        <h3> Schedule Management </h3>
        <p> Create and modify classroom schedules, assign time slots, and mark rooms as unavailable when needed. </p>
        <a href="#" class="admin-btn">Manage Schedules</a>
    </div>
  </div>

  <div class="box">
    <img src="images/Y_602_indoor_1.jpg" alt="Lab Picture" class="box-img">
    <div class="box-text">
        <h3> Generate Report Summary </h3>
        <p> Create and modify classroom schedules, assign time slots, and mark rooms as unavailable when needed. </p>
        <a href="#" class="admin-btn">Generate Reports</a>
    </div>
  </div>
    
</body>
</html>