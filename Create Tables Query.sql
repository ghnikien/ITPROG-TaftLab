CREATE TABLE user(
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_type VARCHAR(20) NOT NULL,
    email VARCHAR(80) UNIQUE,
    password VARCHAR(50),
    full_name VARCHAR(80)
 );

CREATE TABLE student (
    user_id INT PRIMARY KEY,
    student_type VARCHAR(25) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id)
 );
 
 CREATE TABLE admin (
    user_id INT PRIMARY KEY,
    job_position VARCHAR(30),
    department VARCHAR(30),
    FOREIGN KEY (user_id) REFERENCES user(user_id)
 );

CREATE TABLE building(
    building_id INT AUTO_INCREMENT PRIMARY KEY,
    building_code VARCHAR(10),
    building_name VARCHAR(45) NOT NULL
);

CREATE TABLE laboratory(
    lab_id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    room_code VARCHAR(20) NOT NULL,
    capacity INT, 
    status VARCHAR(20),
    FOREIGN KEY (building_id) REFERENCES building(building_id)
);


CREATE TABLE reservation (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, 
    lab_id INT NOT NULL, 
    date_reserved DATE NOT NULL,
    reserve_startTime TIME NOT NULL,
    reserve_endTime TIME NOT NULL,
    status ENUM ('Active', 'Cancelled', 'Completed'),
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (lab_id) REFERENCES laboratory(lab_id)
 );
 
 CREATE TABLE existing_class(
     class_schedule_id INT AUTO_INCREMENT PRIMARY KEY,
     course_code VARCHAR(20),
     section VARCHAR(8),
     lab_id INT NOT NULL,
     day VARCHAR(20),
     start_time TIME,
     end_time TIME,
     FOREIGN KEY (lab_id) REFERENCES laboratory(lab_id)
 );