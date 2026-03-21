<?php include 'includes/db.php'; include 'includes/header.php';
if(isset($_POST['save'])){
$n=$_POST['name'];$p=$_POST['phone'];$pl=$_POST['plan'];
mysqli_query($conn,"INSERT INTO members(name,phone,plan) VALUES('$n','$p','$pl')");
header("Location:members.php");
}
?>
<h2>Add Member</h2>
<form method="POST">
<input name="name" placeholder="Name" required>
<input name="phone" placeholder="Phone" required>
<select id="plan" name="plan">
    <option value="">Select Plan</option>
  <option value="monthly">Monthly</option>
  <option value="quarterly">Quarterly</option>
  <option value="biannually">Biannually</option>
  <option value="yearly">Yearly</option>
</select>

<button name="save">Save</button>
</form>
<?php include 'includes/footer.php'; ?>