<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
?>
<link rel="stylesheet" href="css/style.css">
<div class="sidebar">
<h2>GymERP</h2>
<a href="dashboard.php" >Home</a>
<a href="members.php">Members</a>
<a href="trainers.php">Trainers</a>
<a href="batches.php">Batches</a>
<a href="payments.php">Payments</a>
<a href="attendance.php">Attendance</a>
<a href="admin_settings.php">Settings</a>
<a href="logout.php">Logout</a>
</div>
<div class="main">
