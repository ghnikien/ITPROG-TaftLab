CREATE TABLE user(
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('Student', 'Admin') NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL
);

CREATE TABLE student (
    user_id INT PRIMARY KEY,
    student_type ENUM('SHS', 'UG', 'GD') NOT NULL,
    department ENUM('CCS', 'COS', 'CLA', 'BAGCED', 'COL', 'GCOE', 'RVRCOB', 'SOE', 'Integrated School') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

CREATE TABLE admin (
    user_id INT PRIMARY KEY,
    job_position VARCHAR(30) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

CREATE TABLE building(
    building_id INT AUTO_INCREMENT PRIMARY KEY,
    building_code VARCHAR(10) NOT NULL,
    building_name VARCHAR(45) NOT NULL
);

CREATE TABLE laboratory(
    lab_id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    room_code VARCHAR(10) NOT NULL,
    capacity INT NOT NULL, 
    status ENUM('Active', 'Maintenance', 'Closed') NOT NULL DEFAULT 'Active',
    FOREIGN KEY (building_id) REFERENCES building(building_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE(building_id, room_code)
);

CREATE TABLE reservation (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, 
    lab_id INT NOT NULL, 
    date_reserved DATE NOT NULL,
    reserve_startTime TIME NOT NULL,
    reserve_endTime TIME NOT NULL,
    status ENUM ('Active', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE RESTRICT ON UPDATE CASCADE, 
    FOREIGN KEY (lab_id) REFERENCES laboratory(lab_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE(user_id, lab_id, date_reserved, reserve_startTime, reserve_endTime)
);

CREATE TABLE existing_class(
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL,
    section VARCHAR(10) NOT NULL,
    lab_id INT NOT NULL,
    FOREIGN KEY (lab_id) REFERENCES laboratory(lab_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE(course_code, section)
);

CREATE TABLE class_schedule(
    class_schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    class_day ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (class_id) REFERENCES existing_class(class_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE(class_id, class_day, start_time, end_time)
);

CREATE TABLE restricted_slots(
    restricted_slot_id INT AUTO_INCREMENT PRIMARY KEY,
    lab_id INT NOT NULL,
    restricted_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (lab_id) REFERENCES laboratory(lab_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE(lab_id, restricted_date, start_time, end_time)
);

-- INSERT Buildings
INSERT INTO building (building_id, building_code, building_name)
VALUES(101, 'GK', 'Gokongwei Hall');

INSERT INTO building (building_id, building_code, building_name)
VALUES(102, 'LS', 'St. La Salle Hall');

INSERT INTO building (building_id, building_code, building_name)
VALUES(103, 'AG', 'Br. Andrew Gonzales Hall');

INSERT INTO building (building_id, building_code, building_name)
VALUES(104, 'V', 'Velasco Hall');

INSERT INTO building (building_id, building_code, building_name)
VALUES(105, 'Y', 'Don Enrique Yuchengco Hall');

-- INSERT Admin User
INSERT INTO user(user_id, user_type, email, user_password, full_name)
VALUES(901, 'Admin', 'taftlab_admin@dlsu.edu.ph', 'admin123', 'John Doe');

INSERT INTO admin(user_id, job_position)
VALUES(901, 'IT Administrator');

-- GK Labs (Building 101)
INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G210', 30, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G211', 30, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G302A', 24, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G302B', 24, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G304A', 24, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G306A', 24, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G304B', 45, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G306B', 24, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G404A', 30, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(101, 'G404B', 30, 'Active');

-- LS Labs (Building 102)
INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(102, 'L212', 18, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(102, 'L229', 48, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(102, 'L320', 39, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(102, 'L335', 44, 'Active');

-- Andrew Gonzales Labs (Building 103)
INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(103, 'AG1706', 45, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(103, 'AG1904', 45, 'Active');

-- Velasco Labs (Building 104)
INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(104, 'V103', 29, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(104, 'V205', 24, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(104, 'V206', 24, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(104, 'V208A', 22, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(104, 'V208B', 22, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(104, 'V301', 35, 'Active');

INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(104, 'V10', 28, 'Active');

-- Yuchengco Labs (Building 105)
INSERT INTO laboratory(building_id, room_code, capacity, status)
VALUES(105, 'Y602', 28, 'Active');