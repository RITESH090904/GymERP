
CREATE DATABASE gym_db;
USE gym_db;

-- MEMBERS TABLE
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(15),
    join_date DATE,
    plan VARCHAR(50)
);

-- TRAINERS TABLE
CREATE TABLE trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    specialty VARCHAR(100),
    phone VARCHAR(15),
    salary DECIMAL(10,2)
);

-- PAYMENTS TABLE
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    amount DECIMAL(10,2),
    payment_date DATE,
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- ATTENDANCE TABLE
CREATE TABLE attendance (
    member_id INT,
    date DATE,
    (member_id, date),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);


INSERT INTO members (name, email, phone, join_date, plan)
VALUES 
('Ritesh', 'ritesh@gmail.com', '9876543210', '2025-01-01', 'Monthly');

INSERT INTO trainers (name, specialty, phone, salary)
VALUES 
('John', 'Weight Training', '9123456780', 25000);

INSERT INTO payments (member_id, amount, payment_date)
VALUES 
(1, 1000, '2025-02-01');

INSERT INTO attendance (member_id, date, status)
VALUES 
(1, CURDATE(), 'Present');