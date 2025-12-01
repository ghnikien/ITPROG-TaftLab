<?php
// Database credentials
$servername = "localhost:3307"; // Change this to your MySQL server hostname
$username = "root"; // Change this to your MySQL username
$password = ""; // Change this to your MySQL password
$database = "dbreservationmp"; // Change this to your MySQL database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    echo "Connection failed";
    die("Connection failed: " . $conn->connect_error);
}

// echo "Connected successfully";
?>