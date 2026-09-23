<?php
include 'includes/db.php';
include 'includes/header.php';
?>
<title>Payment Management</title>
<h2>User Plan Purchases</h2>

<table>
<tr>
<th>ID</th>
<th>User</th>
<th>Plan</th>
<th>Total Amount</th>
<th>Mode</th>
<th>Installments</th>
<th>Paid</th>
<th>Remaining</th>
<th>EMI Amount</th>
<th>Purchase Date</th>
<th>Last Payment</th>
<th>Expiry Date</th>
</tr>

<?php
$r = mysqli_query($conn, "SELECT * FROM user_plans ORDER BY id ASC");

while ($row = mysqli_fetch_assoc($r)) {
?>
<tr>
<td><?= (int) $row['id'] ?></td>
<td><?= htmlspecialchars($row['user_name']) ?></td>
<td><?= htmlspecialchars($row['plan_name']) ?></td>
<td>Rs <?= number_format((float) $row['price'], 2) ?></td>
<td><?= htmlspecialchars(strtoupper($row['payment_mode'] ?? 'full')) ?></td>
<td><?= (int) ($row['installment_count'] ?? 1) ?></td>
<td><?= (int) ($row['installments_paid'] ?? 1) ?></td>
<td><?= max(0, (int) ($row['installment_count'] ?? 1) - (int) ($row['installments_paid'] ?? 1)) ?></td>
<td>Rs <?= number_format((float) ($row['installment_amount'] ?? $row['price']), 2) ?></td>
<td><?= htmlspecialchars($row['purchase_date']) ?></td>
<td><?= htmlspecialchars($row['last_payment_date'] ?? $row['purchase_date']) ?></td>
<td><?= htmlspecialchars($row['expiry_date'] ?? 'N/A') ?></td>
</tr>
<?php } ?>

</table>

<?php include 'includes/footer.php'; ?>
