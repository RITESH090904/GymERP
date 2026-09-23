<?php
include 'includes/db.php';
include 'includes/header.php';

$m = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM members"))[0];
$t = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM trainers"))[0];
$b = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM batches"))[0];
$p = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price), 0) FROM user_plans"))[0];
$s = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(salary), 0) FROM trainers"))[0];
$a = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(member_id) FROM attendance WHERE date = CURDATE()"))[0];
?>
<title>Admin Dashboard</title>
<h1 style="text-align: center; color:white;">Home</h1>
<div class="cards">
<div class="card">Members<br><b><?= (int) $m ?></b></div>
<div class="card">Trainers<br><b><?= (int) $t ?></b></div>
<div class="card">Batches<br><b><?= (int) $b ?></b></div>
<div class="card">Revenue<br><b>Rs <?= number_format((float) $p, 2) ?></b></div>
<div class="card">Total Salaries<br><b>Rs <?= number_format((float) $s, 2) ?></b></div>
<div class="card">Today's Attendance<br><b><?= (int) $a ?></b></div>
</div>
<?php include 'includes/footer.php'; ?>
