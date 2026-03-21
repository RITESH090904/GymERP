<?php include 'includes/db.php'; include 'includes/header.php';
$id=$_GET['id'];
if(isset($_POST['save'])){
$n=$_POST['name'];$p=$_POST['phone'];$pl=$_POST['plan'];
mysqli_query($conn,"UPDATE members SET name='$n',phone='$p',plan='$pl' WHERE id=$id");
header("Location:members.php");
}
$r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM members WHERE id=$id"));
?>
<h2>Edit Member</h2>
<form method="POST">
<input name="name" value="<?=$r['name']?>" required>
<input name="phone" value="<?=$r['phone']?>" required>
<select name="plan" required>
    <option value="monthly" <?= $r['plan'] == 'monthly' ? 'selected' : '' ?>>Monthly</option>
    <option value="quarterly" <?= $r['plan'] == 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
    <option value="biannually" <?= $r['plan'] == 'biannually' ? 'selected' : '' ?>>Biannually</option>
    <option value="yearly" <?= $r['plan'] == 'yearly' ? 'selected' : '' ?>>Yearly</option>
</select>
<button name="save">Update</button>
</form>
<?php include 'includes/footer.php'; ?>