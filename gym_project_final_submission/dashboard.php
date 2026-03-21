<?php include 'includes/db.php'; include 'includes/header.php';
$m=mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM members"))[0];
$t=mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM trainers"))[0];
$p=mysqli_fetch_row(mysqli_query($conn,"SELECT SUM(amount) FROM payments"))[0];
$s=mysqli_fetch_row(mysqli_query($conn,"SELECT SUM(salary) FROM trainers"))[0];
$a=mysqli_fetch_row(mysqli_query($conn,"SELECT count(member_id) FROM attendance WHERE date=CURDATE()"))[0];
?>
<h1>Dashboard</h1>
<div class="cards">
<div class="card">Members<br><b><?= $m ?></b></div>
<div class="card">Trainers<br><b><?= $t ?></b></div>
<div class="card">Revenue<br><b>₹<?= $p ?></b></div>
<div class="card">Total Salaries<br><b>₹<?= $s ?></b></div>
<div class="card">Today's Attendance<br><b><?= $a ?></b></div>
</div>
<?php include 'includes/footer.php'; ?>