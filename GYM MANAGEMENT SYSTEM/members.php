<?php
include 'includes/db.php';
include 'includes/header.php';

$r = mysqli_query(
    $conn,
    "SELECT m.id, m.name, m.phone, m.plan, m.purchase_date, b.name AS batch_name, p.duration,
            DATE_ADD(m.purchase_date, INTERVAL COALESCE(p.duration, 0) DAY) AS expiry_date
     FROM members m
     LEFT JOIN batches b ON b.id = m.batch_id
     LEFT JOIN plans p ON p.name = m.plan
     ORDER BY m.id ASC"
);
?>
<title>Member Management</title>

<h2>Members</h2><br>

<table border="1" cellpadding="10" cellspacing="0">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Phone</th>
    <th>Plan</th>
    <th>Batch</th>
    <th>Purchase Date</th>
    <th>Expiry Date</th>
    <th>Action</th>
</tr>

<?php while ($row = mysqli_fetch_assoc($r)) { ?>
<tr>
    <td><?= (int) $row['id'] ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['phone']) ?></td>
    <td><?= htmlspecialchars($row['plan']) ?></td>
    <td><?= htmlspecialchars($row['batch_name'] ?: 'Not Assigned') ?></td>
    <td><?= !empty($row['purchase_date']) ? htmlspecialchars($row['purchase_date']) : 'N/A' ?></td>
    <td><?= !empty($row['expiry_date']) ? htmlspecialchars($row['expiry_date']) : 'N/A' ?></td>
    <td>
        <a href="edit_member.php?id=<?= (int) $row['id'] ?>">Edit</a> |
        <a href="delete_member.php?id=<?= (int) $row['id'] ?>" onclick="return confirm('Delete this member?')">Delete</a>
    </td>
</tr>
<?php } ?>

</table>

<?php include 'includes/footer.php'; ?>
