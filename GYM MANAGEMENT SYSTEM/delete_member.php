<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$paymentStmt = mysqli_prepare($conn, "DELETE FROM user_plans WHERE user_name IN (SELECT name FROM members WHERE id = ?)");
mysqli_stmt_bind_param($paymentStmt, "i", $id);
@mysqli_stmt_execute($paymentStmt);

$stmt = mysqli_prepare($conn, "DELETE FROM members WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

header("Location: members.php");
exit();
?>
