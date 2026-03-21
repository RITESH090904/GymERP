<?php include 'includes/db.php'; include 'includes/header.php';
$r=mysqli_query($conn,"SELECT * FROM members");
?>
<h2>Members</h2><br>
<a href="add_member.php" class="btn">+ Add Member</a><br><br>
<table><tr><th>ID</th><th>Name</th><th>Phone</th><th>Plan</th><th>Action</th></tr>
<?php while($row=mysqli_fetch_assoc($r)){ ?>
<tr>
<td><?= $row['id']?></td>
<td><?= $row['name']?></td>
<td><?= $row['phone']?></td>
<td><?= $row['plan']?></td>
<td>
<a href="edit_member.php?id=<?=$row['id']?>">Edit</a>
<a href="delete_member.php?id=<?=$row['id']?>">Delete</a>
</td>
</tr>
<?php } ?>
</table>
<?php include 'includes/footer.php'; ?>