<?php
include 'includes/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$memberStmt = $conn->prepare("SELECT id, name, email, phone, purchase_date, plan, batch_id FROM members WHERE id = ? LIMIT 1");
$memberStmt->bind_param("i", $id);
$memberStmt->execute();
$memberStmt->store_result();
$memberStmt->bind_result($memberId, $memberName, $memberEmail, $memberPhone, $memberPurchaseDate, $memberPlan, $memberBatchId);
$memberStmt->fetch();

if (!$memberId) {
    header("Location: members.php");
    exit();
}

$message = '';

if (isset($_POST['save'])) {
    $n = trim($_POST['name']);
    $p = trim($_POST['phone']);
    $pl = trim($_POST['plan']);
    $batchId = (int) $_POST['batch_id'];
    $batchId = $batchId > 0 ? $batchId : null;

    if ($batchId) {
        $batchStmt = $conn->prepare("SELECT capacity, (SELECT COUNT(*) FROM members WHERE batch_id = ? AND id != ?) AS total_members FROM batches WHERE id = ? LIMIT 1");
        $batchStmt->bind_param("iii", $batchId, $id, $batchId);
        $batchStmt->execute();
        $batchStmt->store_result();
        $batchStmt->bind_result($batchCapacity, $batchCount);
        $batchStmt->fetch();

        if ($batchCount >= $batchCapacity) {
            $message = 'Selected batch is already full.';
        }
    }

    if ($message === '') {
        $updateStmt = $conn->prepare("UPDATE members SET name = ?, phone = ?, plan = ?, batch_id = ? WHERE id = ?");
        $updateStmt->bind_param("sssii", $n, $p, $pl, $batchId, $id);
        $updateStmt->execute();

        header("Location: members.php");
        exit();
    }
}

$plans = mysqli_query($conn, "SELECT name FROM plans ORDER BY price ASC");
$batches = mysqli_query(
    $conn,
    "SELECT b.id, b.name, b.capacity,
            (SELECT COUNT(*) FROM members m WHERE m.batch_id = b.id AND m.id != {$id}) AS total_members
     FROM batches b
     ORDER BY b.name ASC"
);
?>
<h2>Edit Member</h2>

<?php if ($message !== '') { ?>
<p style="text-align:center;color:#ff8080;"><?= htmlspecialchars($message) ?></p>
<?php } ?>

<form method="POST">
<input name="name" value="<?= htmlspecialchars($memberName) ?>" required>
<input name="phone" value="<?= htmlspecialchars($memberPhone) ?>" pattern="[0-9]{10}" required>
<select name="plan" required>
    <?php while ($plan = mysqli_fetch_assoc($plans)) { ?>
        <option value="<?= htmlspecialchars($plan['name']) ?>" <?= $memberPlan === $plan['name'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($plan['name']) ?>
        </option>
    <?php } ?>
</select>
<select name="batch_id" required>
    <option value="">Select Batch</option>
    <?php while ($batch = mysqli_fetch_assoc($batches)) { ?>
        <option value="<?= (int) $batch['id'] ?>" <?= (int) $memberBatchId === (int) $batch['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($batch['name']) ?> (<?= (int) $batch['total_members'] ?>/<?= (int) $batch['capacity'] ?>)
        </option>
    <?php } ?>
</select>
<button name="save">Update</button>
</form>

<?php include 'includes/footer.php'; ?>
