<?php include 'includes/db.php'; include 'includes/header.php';
if(isset($_POST['save'])){
$m=$_POST['member'];$a=$_POST['amount'];
mysqli_query($conn,"INSERT INTO payments(member_id,amount) VALUES('$m','$a')");
}
$r=mysqli_query($conn,"SELECT payments.*,members.name FROM payments JOIN members ON members.id=payments.member_id");
?>
<h2>Payments</h2>
<form method="POST">
<input name="member" placeholder="Member ID" required>
<input name="amount" placeholder="Amount" required>
<button name="save">Add Payment</button>
</form>
<table><tr><th>ID</th><th>Member</th><th>Amount</th></tr>
<?php while($row=mysqli_fetch_assoc($r)){ ?>
<tr><td><?=$row['id']?></td><td><?=$row['name']?></td><td><?=$row['amount']?></td></tr>
<?php } ?>
</table>
<?php include 'includes/footer.php'; ?>