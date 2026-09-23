<?php
include 'includes/db.php';
include 'includes/header.php';

if (isset($_POST['mark'])) {
    $member_id = (int) $_POST['member_id'];
    $date = $_POST['date'];
    $status = $_POST['status'];

    $check = mysqli_prepare($conn, "SELECT id FROM attendance WHERE member_id = ? AND date = ?");
    mysqli_stmt_bind_param($check, "is", $member_id, $date);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        echo "<p style='color:red;'>Attendance already marked!</p>";
    } else {
        $insert = mysqli_prepare($conn, "INSERT INTO attendance (member_id, date, status) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insert, "iss", $member_id, $date, $status);
        mysqli_stmt_execute($insert);
        echo "<p style='color:green;'>Attendance marked successfully!</p>";
    }
}
?>

<h2>Mark Attendance</h2>

<div class="content-box">
<form method="POST">

<select name="member_id" required>
    <option value="">Select Member</option>
    <?php
    $members = mysqli_query($conn, "SELECT * FROM members ORDER BY name ASC");
    while ($m = mysqli_fetch_assoc($members)) {
        echo "<option value='" . (int) $m['id'] . "'>" . htmlspecialchars($m['name']) . "</option>";
    }
    ?>
</select>

<br><br>

<input type="date" name="date" value="<?= date('Y-m-d') ?>" required>

<br><br>

<select name="status" required>
    <option value="Present">Present</option>
    <option value="Absent">Absent</option>
</select>

<br><br>

<button name="mark">Submit</button>
<a href="attendance.php" class="btn">Back</a>

</form>
</div>

<?php include 'includes/footer.php'; ?>
