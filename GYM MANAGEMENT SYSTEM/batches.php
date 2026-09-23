<?php
include 'includes/db.php';
include 'includes/header.php';

$message = '';

if (isset($_POST['create_batch'])) {
    $name = trim($_POST['name']);
    $schedule = trim($_POST['schedule']);
    $capacity = max(1, (int) ($_POST['capacity'] ?? 10));
    $trainerId = !empty($_POST['trainer_id']) ? (int) $_POST['trainer_id'] : null;

    $check = mysqli_prepare($conn, "SELECT id FROM batches WHERE name = ?");
    mysqli_stmt_bind_param($check, "s", $name);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if ($check->num_rows > 0) {
        $message = 'Batch name already exists.';
        mysqli_stmt_close($check);
    } else {
        mysqli_stmt_close($check);
        if ($trainerId) {
            $stmt = mysqli_prepare($conn, "INSERT INTO batches (name, trainer_id, capacity, schedule) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "siis", $name, $trainerId, $capacity, $schedule);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO batches (name, trainer_id, capacity, schedule) VALUES (?, NULL, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sis", $name, $capacity, $schedule);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        $message = 'Batch created successfully.';
    }
}

$trainers = mysqli_query($conn, "SELECT t.id, t.name, u.email FROM trainers t LEFT JOIN users u ON u.id = t.user_id ORDER BY t.name ASC");
$trainerOptions = [];
while ($trainer = mysqli_fetch_assoc($trainers)) {
    $trainerOptions[] = $trainer;
}

$batchRows = mysqli_query(
    $conn,
    "SELECT b.id, b.name, b.capacity, b.schedule, b.trainer_id, t.name AS trainer_name,
            COUNT(m.id) AS total_members
     FROM batches b
     LEFT JOIN trainers t ON t.id = b.trainer_id
     LEFT JOIN members m ON m.batch_id = b.id
     GROUP BY b.id, b.name, b.capacity, b.schedule, b.trainer_id, t.name
     ORDER BY b.name ASC"
);

$batchMemberGroups = [];
$memberRows = mysqli_query(
    $conn,
    "SELECT b.id AS batch_id, b.name AS batch_name, b.schedule, t.name AS trainer_name,
            m.name AS member_name, m.phone, m.plan,
            DATE_ADD(m.purchase_date, INTERVAL COALESCE(p.duration, 0) DAY) AS expiry_date
     FROM batches b
     LEFT JOIN trainers t ON t.id = b.trainer_id
     LEFT JOIN members m ON m.batch_id = b.id
     LEFT JOIN plans p ON p.name = m.plan
     ORDER BY b.name ASC, m.name ASC"
);
while ($row = mysqli_fetch_assoc($memberRows)) {
    $batchKey = (int) $row['batch_id'];
    if (!isset($batchMemberGroups[$batchKey])) {
        $batchMemberGroups[$batchKey] = [
            'batch_name' => $row['batch_name'],
            'schedule' => $row['schedule'],
            'trainer_name' => $row['trainer_name'],
            'members' => [],
        ];
    }

    if (!empty($row['member_name'])) {
        $batchMemberGroups[$batchKey]['members'][] = $row;
    }
}
?>
<title>Batch Management</title>

<h2>Batches</h2>

<?php if ($message !== '') { ?>
<p style="text-align:center;color:#9fffa9;"><?= htmlspecialchars($message) ?></p>
<?php } ?>

<div class="cards" style="margin-bottom:24px;">
    <div class="card" style="min-width:320px;">
        <h3>Create Batch</h3>
        <form method="POST">
            <input type="text" name="name" placeholder="Batch Name" required>
            <input type="text" name="schedule" placeholder="Schedule (e.g. 6 AM - 7 AM)" required>
            <input type="number" name="capacity" min="1" value="10" required>
            <select name="trainer_id">
                <option value="">Assign Trainer Later</option>
                <?php foreach ($trainerOptions as $trainer) { ?>
                    <option value="<?= (int) $trainer['id'] ?>">
                        <?= htmlspecialchars($trainer['name']) ?><?= !empty($trainer['email']) ? ' (' . htmlspecialchars($trainer['email']) . ')' : '' ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit" name="create_batch">Create Batch</button>
        </form>
        <p style="margin-top:12px;">Admin can set batch size, schedule, and trainer.</p>
    </div>
</div>

<table>
<tr>
    <th>ID</th>
    <th>Batch</th>
    <th>Schedule</th>
    <th>Trainer</th>
    <th>Members</th>
    <th>Capacity</th>
    <th>Admin Update</th>
</tr>
<?php while ($batch = mysqli_fetch_assoc($batchRows)) { ?>
<tr>
    <td><?= (int) $batch['id'] ?></td>
    <td><?= htmlspecialchars($batch['name']) ?></td>
    <td><?= htmlspecialchars($batch['schedule']) ?></td>
    <td><?= htmlspecialchars($batch['trainer_name'] ?: 'Not Assigned') ?></td>
    <td><?= (int) $batch['total_members'] ?></td>
    <td><?= (int) $batch['capacity'] ?></td>
    <td>
        <a class="btn" href="edit_batch.php?id=<?= (int) $batch['id'] ?>">Update</a>
    </td>
</tr>
<?php } ?>
</table>

<h2 style="margin-top:32px;">Batch Wise Members</h2>

<?php foreach ($batchMemberGroups as $group) { ?>
    <div class="card" style="margin-top:20px;">
        <h3><?= htmlspecialchars($group['batch_name']) ?></h3>
        <p>Schedule: <?= htmlspecialchars($group['schedule'] ?: 'Not Set') ?></p>
        <p>Trainer: <?= htmlspecialchars($group['trainer_name'] ?: 'Not Assigned') ?></p>

        <table style="margin-top:16px;">
            <tr>
                <th>Member</th>
                <th>Phone</th>
                <th>Plan</th>
                <th>Expiry Date</th>
            </tr>
            <?php if (count($group['members']) === 0) { ?>
                <tr><td colspan="4">No members in this batch yet.</td></tr>
            <?php } ?>
            <?php foreach ($group['members'] as $member) { ?>
                <tr>
                    <td><?= htmlspecialchars($member['member_name']) ?></td>
                    <td><?= htmlspecialchars($member['phone']) ?></td>
                    <td><?= htmlspecialchars($member['plan']) ?></td>
                    <td><?= htmlspecialchars($member['expiry_date'] ?: 'N/A') ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
<?php } ?>

<?php include 'includes/footer.php'; ?>
