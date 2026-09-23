<?php
include 'includes/db.php';
include 'includes/header.php';

$today = date("Y-m-d");

$query = "SELECT attendance.member_id, attendance.date, attendance.status, members.name 
          FROM attendance 
          JOIN members ON members.id = attendance.member_id 
          WHERE attendance.date = '$today'";

$res = mysqli_query($conn, $query);
?>
<title>Admin Dashboard  </title>

<h2>Attendance</h2>

<a href="add_attendance.php" class="btn"> Mark Attendance</a>
<a href="filter_attendance.php" class="btn">Filter Attendance</a>

<br><br>

<h3>Today's Attendance (<?= $today ?>)</h3>

<div class="table-container">  <!-- ADD THIS -->

<table>
<tr>
    <th>Member ID</th>
    <th>Member Name</th>
    <th>Date</th>
    <th>Status</th>
</tr>

<?php if(mysqli_num_rows($res) > 0){ ?>
    
    <?php while($row = mysqli_fetch_assoc($res)){ ?>
    <tr>
        <td><?= $row['member_id'] ?></td>
        <td><?= $row['name'] ?></td>
        <td><?= $row['date'] ?></td>
        <td class="<?= $row['status']=='Present' ? 'status-present' : 'status-absent' ?>">
            <?= $row['status'] ?>
        </td>
    </tr>
    <?php } ?>

<?php } else { ?>

    <tr>
        <td colspan="4" style="text-align:center;">No attendance marked today</td>
    </tr>

<?php } ?>

</table>

</div> <!-- CLOSE table-container -->

<?php include 'includes/footer.php'; ?>
