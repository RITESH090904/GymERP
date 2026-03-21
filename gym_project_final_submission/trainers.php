<?php
include 'includes/db.php';
include 'includes/header.php';

// FETCH TRAINERS
$res = mysqli_query($conn, "SELECT * FROM trainers");
?>

<h2>Trainers</h2><br>

<a class="btn" href="add_trainer.php">+ Add Trainer</a>

<br><br>

<table border="1" cellpadding="10" cellspacing="0" width="100%">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Specialty</th>
    <th>Phone</th>
    <th>Salary</th>
    <th>Actions</th>
</tr>

<?php while($row = mysqli_fetch_assoc($res)){ ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['name'] ?></td>
    <td><?= $row['specialty'] ?></td>
    <td><?= $row['phone'] ?></td>
    <td><?= $row['salary'] ?></td>

    <!-- ✅ ACTION COLUMN -->
    <td style="white-space: nowrap; text-align:center;">
        <a href="edit_trainer.php?id=<?= $row['id'] ?>">Edit</a>
        |
        <a href="delete_trainer.php?id=<?= $row['id'] ?>" 
           onclick="return confirm('Are you sure you want to delete this trainer?')">
           Delete
        </a>
    </td>
</tr>
<?php } ?>

</table>

<?php include 'includes/footer.php'; ?>