CREATE DATABASE gym_db;
USE gym_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    phone VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role VARCHAR(20) DEFAULT 'user'
);

CREATE TABLE trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100),
    specialty VARCHAR(100),
    phone VARCHAR(15),
    salary DECIMAL(10,2)
) ENGINE=InnoDB;

CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    trainer_id INT NULL,
    capacity INT NOT NULL DEFAULT 10,
    schedule VARCHAR(100) NULL
) ENGINE=InnoDB;

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(15),
    purchase_date DATE,
    plan VARCHAR(50),
    batch_id INT NULL
) ENGINE=InnoDB;

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    date DATE,
    status VARCHAR(20) DEFAULT 'Present',
    marked_by_trainer_id INT NULL,
    UNIQUE(member_id, date),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    price INT,
    duration INT
);

CREATE TABLE user_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100),
    plan_name VARCHAR(100),
    price INT,
    payment_mode VARCHAR(20) NOT NULL DEFAULT 'full',
    installment_count INT NOT NULL DEFAULT 1,
    installment_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    installments_paid INT NOT NULL DEFAULT 1,
    last_payment_date DATE NULL,
    purchase_date DATE,
    expiry_date DATE NULL
);

INSERT INTO plans (name, price, duration) VALUES
('Monthly Plan', 2000, 30),
('Quarterly Plan', 5000, 90),
('Bi-Annual Plan', 10000, 180),
('Annual Plan', 18000, 365);
