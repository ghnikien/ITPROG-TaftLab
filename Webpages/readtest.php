<?php
    $connection = mysqli_connect("localhost:3307", "root", "") or die("Connection failed: " . mysqli_connect_error());
    $use = mysqli_select_db($connection, "dbreservationmp");
    
    $sql = "SELECT * FROM user";
    $sql2 = "SELECT * FROM student";
    $result = $connection->query($sql);
    $result2 = $connection->query($sql2);

    while($row = mysqli_fetch_array($result))
    {
        echo "user_id: ". $row['user_id']. "<br>";
        echo "user_type: ". $row['user_type']. "<br>";
        echo "email: ". $row['email']. "\n". "<br>";
        echo "password: ". $row['password']. "<br>";
        echo "full_name: ". $row['full_name']. "<br>";
    }

    echo "<br><br>";

    while($row2 = mysqli_fetch_array($result2))
    {
        echo "user_id: ". $row2['user_id']. "<br>";
        echo "student_type: ". $row2['student_type']. "<br>";
    }

    $connection->close();
?>

