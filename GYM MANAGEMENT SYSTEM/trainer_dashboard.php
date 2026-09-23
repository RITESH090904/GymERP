<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['trainer_id'])) {
    header("Location: index.php");
    exit();
}

$trainerId = (int) $_SESSION['trainer_id'];
$trainerName = $_SESSION['trainer'] ?? 'Trainer';
$message = '';
$activeSection = $_GET['section'] ?? 'dashboard';

$batchStmt = $conn->prepare(
    "SELECT b.id, b.name, b.schedule, b.capacity,
            (SELECT COUNT(*) FROM members m WHERE m.batch_id = b.id) AS total_members
     FROM batches b
     WHERE b.trainer_id = ?
     LIMIT 1"
);
$batchStmt->bind_param("i", $trainerId);
$batchStmt->execute();
$batchStmt->store_result();
$batchStmt->bind_result($batchId, $batchName, $batchSchedule, $batchCapacity, $batchMembersCount);
$batchStmt->fetch();
$batchStmt->close();

if (isset($_POST['mark_batch_attendance']) && $batchId) {
    $attendanceDate = $_POST['attendance_date'];
    $memberIds = $_POST['member_id'] ?? [];
    $statuses = $_POST['status'] ?? [];

    $attendanceStmt = $conn->prepare(
        "INSERT INTO attendance (member_id, date, status, marked_by_trainer_id)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by_trainer_id = VALUES(marked_by_trainer_id)"
    );

    foreach ($memberIds as $index => $memberId) {
        $memberId = (int) $memberId;
        $status = $statuses[$index] ?? 'Present';
        $attendanceStmt->bind_param("issi", $memberId, $attendanceDate, $status, $trainerId);
        $attendanceStmt->execute();
    }
    $attendanceStmt->close();

    $message = 'Batch attendance saved.';
}

$members = [];
if ($batchId) {
    $memberResult = mysqli_query(
        $conn,
        "SELECT m.id, m.name, m.phone, m.plan,
                DATE_ADD(m.purchase_date, INTERVAL COALESCE(p.duration, 0) DAY) AS expiry_date
         FROM members m
         LEFT JOIN plans p ON p.name = m.plan
         WHERE m.batch_id = {$batchId}
         ORDER BY m.name ASC"
    );

    while ($row = mysqli_fetch_assoc($memberResult)) {
        $members[] = $row;
    }
}

$todayAttendance = [];
if ($batchId) {
    $attendanceResult = mysqli_query(
        $conn,
        "SELECT m.name, a.date, a.status
         FROM attendance a
         JOIN members m ON m.id = a.member_id
         WHERE m.batch_id = {$batchId} AND a.date = CURDATE()
         ORDER BY m.name ASC"
    );

    while ($row = mysqli_fetch_assoc($attendanceResult)) {
        $todayAttendance[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Trainer Dashboard</title>
<style>
*{
    box-sizing:border-box;
    text-decoration:none;
}
body{
    margin:0;
    font-family:Arial;
    display:flex;
    background:
    linear-gradient(rgba(7,18,32,0.82), rgba(6,14,26,0.78)),
    url('img/gym4.jpg') no-repeat center center;
    background-size:cover;
    background-attachment:fixed;
}

.sidebar{
    width:220px;
    height:100vh;
    padding:20px;
    position:fixed;
    color:white;
    background:rgba(0,0,0,0.45);
    border-right:1px solid rgba(255,255,255,0.1);
}

.sidebar h2{
    text-align:center;
    margin-bottom:24px;
}

.sidebar a{
    display:block;
    color:white;
    margin:10px 0;
    text-decoration:none;
    padding:10px;
    border-radius:8px;
    cursor:pointer;
}

.sidebar a:hover{
    background:rgba(255,255,255,0.2);
}

.main{
    margin-left:220px;
    padding:30px;
    width:100%;
}

.cards{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:20px;
}

.card{
    background:rgba(255,255,255,0.1);
    backdrop-filter:blur(12px);
    padding:20px;
    border-radius:12px;
    flex:1;
    min-width:220px;
    color:white;
}

table{
    width:100%;
    border-collapse:collapse;
    background:rgba(255,255,255,0.08);
    color:white;
}

th, td{
    padding:12px;
    border:1px solid rgba(255,255,255,0.15);
}

input, select, button{
    padding:10px;
    margin:8px;
    border-radius:6px;
    border:none;
}

button{
    background:linear-gradient(45deg,#00c6ff,#0072ff);
    color:white;
    cursor:pointer;
}

.section{
    display:none;
}

h2, h3, h4{
    color:white;
}
</style>
</head>
<body>
<div class="sidebar">
    <h2>GymERP</h2>
    <a href="trainer_dashboard.php?section=dashboard">Home</a>
    <a href="trainer_dashboard.php?section=attendance">Mark Attendance</a>
    <a href="trainer_dashboard.php?section=members">My Batch Members</a>
    <a href="trainer_settings.php">Settings</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">
    <?php if ($message !== '') { ?>
        <p style="color:#9fffa9;"><?= htmlspecialchars($message) ?></p>
    <?php } ?>

    <div id="dashboard" class="section" style="display:<?= $activeSection === 'dashboard' ? 'block' : 'none' ?>;">
        <h2>Trainer Dashboard</h2>
        <?php if (!$batchId) { ?>
            <div class="card">
                <h3>No Batch Assigned</h3>
                <p>An admin needs to assign you to a batch before you can mark attendance.</p>
            </div>
        <?php } else { ?>
            <div class="cards">
                <div class="card">
                    <h4>Assigned Batch</h4>
                    <p><?= htmlspecialchars($batchName) ?></p>
                </div>
                <div class="card">
                    <h4>Schedule</h4>
                    <p><?= htmlspecialchars($batchSchedule) ?></p>
                </div>
                <div class="card">
                    <h4>Members</h4>
                    <p><?= (int) $batchMembersCount ?>/<?= (int) $batchCapacity ?></p>
                </div>
            </div>

            <h3>Today's Attendance</h3>
            <table>
                <tr><th>Member</th><th>Date</th><th>Status</th></tr>
                <?php if (count($todayAttendance) === 0) { ?>
                    <tr><td colspan="3">No attendance marked today.</td></tr>
                <?php } ?>
                <?php foreach ($todayAttendance as $row) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>

    <div id="attendance" class="section" style="display:<?= $activeSection === 'attendance' ? 'block' : 'none' ?>;">
        <h2>Mark Batch Attendance</h2>
        <?php if (!$batchId) { ?>
            <div class="card">
                <p>No assigned batch found.</p>
            </div>
        <?php } else { ?>
            <form method="POST">
                <input type="date" name="attendance_date" value="<?= date('Y-m-d') ?>" required>
                <table>
                    <tr>
                        <th>Member</th>
                        <th>Plan</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($members as $member) { ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($member['name']) ?>
                                <input type="hidden" name="member_id[]" value="<?= (int) $member['id'] ?>">
                            </td>
                            <td><?= htmlspecialchars($member['plan']) ?></td>
                            <td><?= htmlspecialchars($member['expiry_date'] ?: 'N/A') ?></td>
                            <td>
                                <select name="status[]">
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                </select>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
                <button type="submit" name="mark_batch_attendance">Save Attendance</button>
            </form>
        <?php } ?>
    </div>

    <div id="members" class="section" style="display:<?= $activeSection === 'members' ? 'block' : 'none' ?>;">
        <h2>My Batch Members</h2>
        <?php if (!$batchId) { ?>
            <div class="card">
                <p>No assigned batch found.</p>
            </div>
        <?php } else { ?>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Plan</th>
                    <th>Expiry Date</th>
                </tr>
                <?php foreach ($members as $member) { ?>
                    <tr>
                        <td><?= htmlspecialchars($member['name']) ?></td>
                        <td><?= htmlspecialchars($member['phone']) ?></td>
                        <td><?= htmlspecialchars($member['plan']) ?></td>
                        <td><?= htmlspecialchars($member['expiry_date'] ?: 'N/A') ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
</div>

</body>
</html>
