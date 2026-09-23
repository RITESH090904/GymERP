<?php
include 'includes/db.php';
include 'includes/header.php';

$res = mysqli_query(
    $conn,
    "SELECT t.*, u.email,
            (SELECT b.name FROM batches b WHERE b.trainer_id = t.id LIMIT 1) AS batch_name
     FROM trainers t
     LEFT JOIN users u ON u.id = t.user_id
     ORDER BY t.id ASC"
);
?>
<title>Trainer Management</title>

<h2>Trainers</h2><br>

<a class="btn" href="add_trainer.php">Add Trainer</a>

<br><br>

<table border="1" cellpadding="10" cellspacing="0" width="100%">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Specialty</th>
    <th>Phone</th>
    <th>Salary</th>
    <th>Assigned Batch</th>
    <th>Actions</th>
</tr>

<?php while ($row = mysqli_fetch_assoc($res)) { ?>
<tr>
    <td><?= (int) $row['id'] ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['email'] ?: 'No Login') ?></td>
    <td><?= htmlspecialchars($row['specialty']) ?></td>
    <td><?= htmlspecialchars($row['phone']) ?></td>
    <td>Rs <?= number_format((float) $row['salary'], 2) ?></td>
    <td><?= htmlspecialchars($row['batch_name'] ?: 'Not Assigned') ?></td>
    <td style="white-space: nowrap; text-align:center;">
        <a href="edit_trainer.php?id=<?= (int) $row['id'] ?>">Edit</a>
        |
        <a href="delete_trainer.php?id=<?= (int) $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this trainer?')">
           Delete
        </a>
    </td>
</tr>
<?php } ?>

</table>

<?php include 'includes/footer.php'; ?>
