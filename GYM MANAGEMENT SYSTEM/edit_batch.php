<?php
include 'includes/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$batchStmt = mysqli_prepare(
    $conn,
    "SELECT b.id, b.name, b.schedule, b.capacity, b.trainer_id,
            (SELECT COUNT(*) FROM members WHERE batch_id = b.id) AS total_members
     FROM batches b
     WHERE b.id = ?"
);
mysqli_stmt_bind_param($batchStmt, "i", $id);
mysqli_stmt_execute($batchStmt);
mysqli_stmt_bind_result($batchStmt, $batchId, $batchName, $batchSchedule, $batchCapacity, $batchTrainerId, $batchMemberCount);
mysqli_stmt_fetch($batchStmt);
mysqli_stmt_close($batchStmt);

if (!$batchId) {
    header("Location: batches.php");
    exit();
}

$message = '';

if (isset($_POST['update_batch'])) {
    $name = trim($_POST['name']);
    $schedule = trim($_POST['schedule']);
    $capacity = max(1, (int) ($_POST['capacity'] ?? 10));
    $trainerId = !empty($_POST['trainer_id']) ? (int) $_POST['trainer_id'] : null;

    if ($capacity < (int) $batchMemberCount) {
        $message = 'Capacity cannot be less than current batch members.';
    } else {
        $nameCheck = mysqli_prepare($conn, "SELECT id FROM batches WHERE name = ? AND id != ?");
        mysqli_stmt_bind_param($nameCheck, "si", $name, $batchId);
        mysqli_stmt_execute($nameCheck);
        mysqli_stmt_store_result($nameCheck);

        if ($nameCheck->num_rows > 0) {
            $message = 'Another batch already uses that name.';
            mysqli_stmt_close($nameCheck);
        } else {
            mysqli_stmt_close($nameCheck);
            if ($trainerId) {
                $stmt = mysqli_prepare($conn, "UPDATE batches SET name = ?, schedule = ?, capacity = ?, trainer_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssiii", $name, $schedule, $capacity, $trainerId, $batchId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE batches SET name = ?, schedule = ?, capacity = ?, trainer_id = NULL WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssii", $name, $schedule, $capacity, $batchId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            header("Location: batches.php");
            exit();
        }
    }
}

$trainerRows = mysqli_query($conn, "SELECT t.id, t.name FROM trainers t ORDER BY t.name ASC");
?>
<title>Edit Batch</title>

<h2>Update Batch</h2>

<?php if ($message !== '') { ?>
<p style="text-align:center;color:#ff8080;"><?= htmlspecialchars($message) ?></p>
<?php } ?>

<form method="POST">
    <input type="text" name="name" value="<?= htmlspecialchars($batchName) ?>" required>
    <input type="text" name="schedule" value="<?= htmlspecialchars($batchSchedule) ?>" required>
    <input type="number" name="capacity" min="<?= max(1, (int) $batchMemberCount) ?>" value="<?= (int) $batchCapacity ?>" required>
    <select name="trainer_id">
        <option value="">No Trainer</option>
        <?php while ($trainer = mysqli_fetch_assoc($trainerRows)) { ?>
            <option value="<?= (int) $trainer['id'] ?>" <?= (int) $batchTrainerId === (int) $trainer['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($trainer['name']) ?>
            </option>
        <?php } ?>
    </select>
    <button type="submit" name="update_batch">Save Changes</button>
    <a class="btn" href="batches.php">Back</a>
</form>

<?php include 'includes/footer.php'; ?>
