<?php
    include "db.php"; 

    $hasError = false;
    $errorMessage = '';

    if($_SERVER["REQUEST_METHOD"] == "POST")
    {
        $first_name   = $_POST['first_name']   ?? '';
        $middle_name  = $_POST['middle_name']  ?? ''; 
        $last_name    = $_POST['last_name']    ?? '';
        $email        = $_POST['email_address'] ?? '';
        $password     = $_POST['password']     ?? '';
        $student_type = $_POST['student_type'] ?? '';
        $department   = $_POST['department']   ?? '';

        // Only validate if form was actually submitted
        if(!empty($_POST)) {
            // Validate first name and last name
            if(empty($first_name) || empty($last_name)) {
                $hasError = true;
                $errorMessage = "First name and last name are required.";
            }
            
            // Validate email, password, student type, and department
            if(empty($email) || empty($password) || empty($student_type) || empty($department)) {
                $hasError = true;
                $errorMessage = "All fields are required.";
            }

            // Vallidate email ends with @dlsu.edu.ph
            if(!$hasError && !str_ends_with($email, '@dlsu.edu.ph')) {
                $hasError = true;
                $errorMessage = "Email must be a valid DLSU email address (@dlsu.edu.ph).";
            }

            if(!$hasError)
            {
                // Check if email already exists
                $checkEmail = "SELECT email FROM user WHERE email = ?";
                $stmtCheck = $conn->prepare($checkEmail);
                $stmtCheck->bind_param("s", $email);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                
                if($resultCheck->num_rows > 0) {
                    $hasError = true;
                    $errorMessage = "Email already registered.";
                } else {
                    // Create full name
                    $fullName = $last_name . ", " . $first_name;
                    if(!empty($middle_name)) {
                        $fullName .= " " . $middle_name;
                    }

                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $user_type = 'Student'; // Set BEFORE binding

                    // Insert into user table
                    $insertUser = "INSERT INTO user (user_type, email, user_password, full_name) 
                                   VALUES (?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($insertUser);
                    $stmt->bind_param("ssss", $user_type, $email, $hashed_password, $fullName);
                    
                    if($stmt->execute()) {
                        $user_id = $stmt->insert_id;

                        // Insert into student table
                        $insertStudent = "INSERT INTO student (user_id, student_type, department)
                                          VALUES (?, ?, ?)";

                        $stmt2 = $conn->prepare($insertStudent);
                        $stmt2->bind_param("iss", $user_id, $student_type, $department);
                        
                        if($stmt2->execute()) {
                            // Redirect on success
                            header("Location: login.php");
                            exit();
                        } else {
                            $hasError = true;
                            $errorMessage = "Error creating student record: " . $stmt2->error;
                        }
                        $stmt2->close();
                    } else {
                        $hasError = true;
                        $errorMessage = "Error creating account: " . $stmt->error;
                    }
                    $stmt->close();
                }
                $stmtCheck->close();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="login-signup.css">
</head>
<body>
    <div class="signup">
        <div class="signup-leftside">
            <h2>Sign Up to TaftLab</h2>

            <?php if($hasError && $_SERVER["REQUEST_METHOD"] == "POST"): ?>
                <div style="color: red; margin-bottom: 15px; padding: 10px; background-color: #ffe6e6; border-radius: 5px;">
                    <p><?= htmlspecialchars($errorMessage); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="createAccount.php">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>

                <label for="middle_name">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name">

                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>

                <label for="email_address">Email Address</label>
                <input type="text" id="email_address" name="email_address" placeholder="user@dlsu.edu.ph" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <label for="student_type">Student Type</label>
                <select id="student_type" name="student_type" required>
                    <option value="" disabled selected>Select here</option>
                    <option value="SHS">SHS</option>
                    <option value="UG">UG</option>
                    <option value="GD">GD</option>
                </select>

                <label for="department">College/School</label>
                <select id="department" name="department" required>
                    <option value="" disabled selected>Select here</option>
                    <option value="CCS">CCS</option>
                    <option value="COS">COS</option>
                    <option value="CLA">CLA</option>
                    <option value="BAGCED">BAGCED</option>
                    <option value="COL">COL</option>
                    <option value="GCOE">GCOE</option>
                    <option value="RVRCOB">RVRCOB</option>
                    <option value="SOE">SOE</option>
                    <option value="Integrated School">Integrated School</option>
                </select>

                <button type="submit" class="top-btn">Create Account</button>
            </form>

            <form method="POST" action="login.php">
                <button type="submit" class="bottom-btn">Back</button>
            </form>
        </div>

        <div class="signup-rightside">
            <img src="images/taftlab-logo.png" alt="TAFT LAB Logo">
            <h2>Every Lasallian's Gateway to<br>DLSU Computer Labs.</h2>
            <p>Book your workspace today — at DLSU.</p>
        </div>
    </div>

    <!-- dependent dropdowns via JS -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const studentType = document.getElementById("student_type");
        const department = document.getElementById("department");

        // Store all original department options
        const allDepartments = Array.from(department.options);

        studentType.addEventListener("change", function() {
            const selectedType = this.value;

            // reset department options to all
            department.innerHTML = "";

            // default "Select here" option
            const defaultOption = document.createElement("option");
            defaultOption.textContent = "Select here";
            defaultOption.value = "";
            defaultOption.disabled = true;
            defaultOption.selected = true;
            department.appendChild(defaultOption);

            // apply filtering
            // if SHS, show only "Integrated School"
            if (selectedType === "SHS") {
                allDepartments.forEach(opt => {
                    if (opt.value === "Integrated School") {
                        department.appendChild(opt.cloneNode(true));
                    }
                });
            } 
            
            // if UG or GD, show all except "Integrated School"
            else if (selectedType === "UG" || selectedType === "GD") {
                allDepartments.forEach(opt => {
                    if (opt.value && opt.value !== "Integrated School") {
                        department.appendChild(opt.cloneNode(true));
                    }
                });
            }
        });
    });
    </script>
</body>
</html>