<?php
include 'includes/db.php';
include 'includes/header.php';

// INSERT ATTENDANCE
if(isset($_POST['mark'])){
    $member_id = $_POST['member_id'];
    $date = $_POST['date'];

    mysqli_query($conn, "INSERT INTO attendance (member_id, date) 
    VALUES ('$member_id', '$date')");

    header("Location: attendance.php");
    exit();
}

$today = date("Y-m-d");

// TODAY QUERY
$today_query = "SELECT attendance.*, members.name 
                FROM attendance 
                JOIN members ON members.id = attendance.member_id 
                WHERE attendance.date = '$today'";

$today_res = mysqli_query($conn, $today_query);

// FILTER QUERY
$filter_res = null;
if(isset($_GET['filter_date']) && $_GET['filter_date'] != ""){
    $fdate = $_GET['filter_date'];

    $filter_query = "SELECT attendance.*, members.name 
                     FROM attendance 
                     JOIN members ON members.id = attendance.member_id 
                     WHERE attendance.date = '$fdate'";

    $filter_res = mysqli_query($conn, $filter_query);
}
?>

<h2>Attendance</h2><br>

<!-- MARK ATTENDANCE -->
<form method="POST">
    <select name="member_id" required>
        <option value="">Select Member</option>
        <?php
        $members = mysqli_query($conn, "SELECT * FROM members");
        while($m = mysqli_fetch_assoc($members)){
            echo "<option value='{$m['id']}'>{$m['name']}</option>";
        }
        ?>
    </select>

    <input type="date" name="date" required>
    <button name="mark">Mark Attendance</button>
</form>

<br>

<!-- ✅ TODAY ATTENDANCE -->
<h3>Today's Attendance (<?= $today ?>)</h3>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Member Name</th>
    <th>Date</th>
</tr>

<?php while($row = mysqli_fetch_assoc($today_res)){ ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['name'] ?></td>
    <td><?= $row['date'] ?></td>
</tr>
<?php } ?>

</table>

<br><br>

<!-- ✅ FILTER SECTION -->
<h3>Filter Attendance</h3>

<form method="GET">
    <input type="date" name="filter_date" required>
    <button>Show</button>
</form>

<br>

<?php if($filter_res){ ?>

<h3>Filtered Result</h3>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Member Name</th>
    <th>Date</th>
</tr>

<?php while($row = mysqli_fetch_assoc($filter_res)){ ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['name'] ?></td>
    <td><?= $row['date'] ?></td>
</tr>
<?php }
 ?>

</table>


<?php } ?>

<?php include 'includes/footer.php'; ?>