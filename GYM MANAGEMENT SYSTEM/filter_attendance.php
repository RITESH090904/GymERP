<?php
include 'includes/db.php';
include 'includes/header.php';

$filter_res = null;
if (isset($_GET['filter_date']) && $_GET['filter_date'] !== "") {
    $fdate = $_GET['filter_date'];

    $filter_query = mysqli_prepare(
        $conn,
        "SELECT attendance.member_id, attendance.date, attendance.status, members.name
         FROM attendance
         JOIN members ON members.id = attendance.member_id
         WHERE attendance.date = ?"
    );
    mysqli_stmt_bind_param($filter_query, "s", $fdate);
    mysqli_stmt_execute($filter_query);
    mysqli_stmt_bind_result($filter_query, $filterMemberId, $filterDate, $filterStatus, $filterName);
    $filter_res = $filter_query;
}
?>
<h2>Attendance Overview</h2>

<br>

<div class="content-box">
<h3>Filter Attendance</h3>

<form method="GET">
<input type="date" name="filter_date" required>
<button>Search</button>
<a href="attendance.php" class="btn">Back</a>
</form>
</div>

<br>

<?php if ($filter_res) { ?>

<div class="content-box">

<h3>Filtered Result</h3>

<table>
<tr>
<th>ID</th>
<th>Name</th>
<th>Date</th>
<th>Status</th>
</tr>

<?php while (mysqli_stmt_fetch($filter_res)) { ?>
<tr>
<td><?= (int) $filterMemberId ?></td>
<td><?= htmlspecialchars($filterName) ?></td>
<td><?= htmlspecialchars($filterDate) ?></td>
<td class="<?= $filterStatus === 'Present' ? 'status-present' : 'status-absent' ?>">
<?= htmlspecialchars($filterStatus) ?>
</td>
</tr>
<?php } ?>

</table>

</div>

<?php } ?>

<?php include 'includes/footer.php'; ?>
