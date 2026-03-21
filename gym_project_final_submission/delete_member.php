<?php
include 'includes/db.php';

$id = $_GET['id'];

// DELETE ATTENDANCE RECORDS
mysqli_query($conn, "DELETE FROM attendance WHERE member_id=$id");

// DELETE MEMBER
mysqli_query($conn, "DELETE FROM members WHERE id=$id");

header("Location: members.php");
exit();
?>