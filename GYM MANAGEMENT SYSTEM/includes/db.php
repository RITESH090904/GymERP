<?php
$conn = mysqli_connect("localhost", "root", "", "gym_db", 3306);

if (!$conn) {
    die("DB Error: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

function gymTableExists($conn, $tableName)
{
    $safeTable = mysqli_real_escape_string($conn, $tableName);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$safeTable}'");
    return $result && mysqli_num_rows($result) > 0;
}

function gymColumnExists($conn, $tableName, $columnName)
{
    if (!gymTableExists($conn, $tableName)) {
        return false;
    }

    $safeTable = mysqli_real_escape_string($conn, $tableName);
    $safeColumn = mysqli_real_escape_string($conn, $columnName);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $result && mysqli_num_rows($result) > 0;
}

function gymEnsureSchema($conn)
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        trainer_id INT NULL,
        capacity INT NOT NULL DEFAULT 10,
        schedule VARCHAR(100) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (!gymColumnExists($conn, 'trainers', 'user_id')) {
        mysqli_query($conn, "ALTER TABLE trainers ADD COLUMN user_id INT NULL AFTER id");
    }

    if (!gymColumnExists($conn, 'members', 'batch_id')) {
        mysqli_query($conn, "ALTER TABLE members ADD COLUMN batch_id INT NULL AFTER plan");
    }

    if (!gymColumnExists($conn, 'user_plans', 'payment_mode')) {
        mysqli_query($conn, "ALTER TABLE user_plans ADD COLUMN payment_mode VARCHAR(20) NOT NULL DEFAULT 'full' AFTER price");
    }

    if (!gymColumnExists($conn, 'user_plans', 'installment_count')) {
        mysqli_query($conn, "ALTER TABLE user_plans ADD COLUMN installment_count INT NOT NULL DEFAULT 1 AFTER payment_mode");
    }

    if (!gymColumnExists($conn, 'user_plans', 'installment_amount')) {
        mysqli_query($conn, "ALTER TABLE user_plans ADD COLUMN installment_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER installment_count");
    }

    if (!gymColumnExists($conn, 'user_plans', 'expiry_date')) {
        mysqli_query($conn, "ALTER TABLE user_plans ADD COLUMN expiry_date DATE NULL AFTER purchase_date");
    }

    if (!gymColumnExists($conn, 'user_plans', 'installments_paid')) {
        mysqli_query($conn, "ALTER TABLE user_plans ADD COLUMN installments_paid INT NOT NULL DEFAULT 1 AFTER installment_amount");
    }

    if (!gymColumnExists($conn, 'user_plans', 'last_payment_date')) {
        mysqli_query($conn, "ALTER TABLE user_plans ADD COLUMN last_payment_date DATE NULL AFTER installments_paid");
    }

    if (!gymColumnExists($conn, 'attendance', 'marked_by_trainer_id')) {
        mysqli_query($conn, "ALTER TABLE attendance ADD COLUMN marked_by_trainer_id INT NULL AFTER status");
    }
}

gymEnsureSchema($conn);
?>
