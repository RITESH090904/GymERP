<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$trainerLookup = $conn->prepare("SELECT user_id FROM trainers WHERE id = ? LIMIT 1");
$trainerLookup->bind_param("i", $id);
$trainerLookup->execute();
$trainerLookup->store_result();
$trainerLookup->bind_result($trainerUserId);
$trainerLookup->fetch();

$batchStmt = mysqli_prepare($conn, "UPDATE batches SET trainer_id = NULL WHERE trainer_id = ?");
mysqli_stmt_bind_param($batchStmt, "i", $id);
mysqli_stmt_execute($batchStmt);

$stmt = mysqli_prepare($conn, "DELETE FROM trainers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

if (!empty($trainerUserId)) {
    $userStmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, "i", $trainerUserId);
    mysqli_stmt_execute($userStmt);
}

header("Location: trainers.php");
exit();
?>
