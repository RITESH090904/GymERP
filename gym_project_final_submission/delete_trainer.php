<?php
include 'includes/db.php';

// GET ID
$id = $_GET['id'];

// DELETE QUERY
mysqli_query($conn, "DELETE FROM trainers WHERE id=$id");

// REDIRECT BACK
header("Location: trainers.php");
exit();
?>