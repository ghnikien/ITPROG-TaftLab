<?php
    $connection = mysqli_connect("localhost:3307", "root", "") or die("Connection failed: " . mysqli_connect_error());
    $use = mysqli_select_db($connection, "dbreservationmp");

    $hasError = false;

    if($_SERVER["REQUEST_METHOD"] == "POST")
    {
        $middle_name = $_POST['middle_name'];

        if(!empty($_POST["first_name"]) && !empty($_POST["last_name"]))
            $fullName = $_POST['last_name'] . ", " . $_POST['first_name']. " " . $middle_name;
        else
            $hasError = true;

        if(!empty($_POST["email_address"]))
            $email = $_POST['email_address'];
        else
            $hasError = true;

        if(!empty($_POST["password"]))
            $password = $_POST['password'];
        else
            $hasError = true;

        if(!empty($_POST["student_type"]))
            $student_type = $_POST['student_type'];
        else
            $hasError = true;

        if(!empty($_POST["department"]))
            $department = $_POST['department'];
        else
            $hasError = true;

        if(!$hasError)
        {
            $insertUser = "INSERT INTO user (user_type, email, password, full_name) 
                           VALUES ('Student', '$email', '$password', '$fullName')";

            mysqli_query($connection, $insertUser);
            $user_id = mysqli_insert_id($connection); //gets the last inserted primary key in table

            $insertStudent = "INSERT INTO student (user_id, student_type)
                              VALUES ('$user_id', '$student_type')";
            mysqli_query($connection, $insertStudent);
            header("Location:readtest.php?message=user added");
            $hasError = false;
            exit();            
        }
    }
     mysqli_close($connection);
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
</head>
<body>
    <h2> Create an Account (Student) </h2>
    <form method = "post" action = "createAccount.php">
        <label for = "first_name"> First Name </label><br>
        <input type = "text" id = "first_name" name = "first_name" required><br><br>

        <label for = "middle_name"> Middle Name </label><br> 
        <input type = "text" id = "middle_name" name = "middle_name"><br><br>

        <label for = "last_name"> Last Name </label><br> 
        <input type = "text" id = "last_name" name = "last_name" required><br><br>
        
        <label for = "student_type"> Student type </label><br> 
        <select id = "student_type" name = "student_type" required>
            <option value = "">-- Select Student Type -- </option>
            <option value = "SHS">SHS</option>
            <option value = "UG">UG</option>
            <option value = "GD">GD</option>
        </select> <br> <br>
        <label for = "email_address"> Email Address </label><br> 

        <input type = "text" id = "email_address" name = "email_address" required><br><br>

        <label for = "password"> Password </label><br> 
        <input type = "password" id = "password" name = "password" required><br><br>



        <label for = "department"> Department </label><br> 
        <select id = "department" name = "department" required>
            <option value = "">-- Select Deartment -- </option>
            <option value = "CCS">CCS</option>
            <option value = "COS">COS</option>
            <option value = "CLA">CLA</option>
            <option value = "BAGCED">BAGCED</option>
            <option value = "COL">COL</option>
            <option value = "GCOE">GCOE</option>
            <option value = "RVRCOB">RVRCOB</option>
            <option value = "SOE">SOE</option>
            <option value = "Integrated School">Senior High and Integrated School</option>
        </select> <br> <br>
        <input type = "submit" value = "Submit">
        
    </form>
        
</body>
</html>