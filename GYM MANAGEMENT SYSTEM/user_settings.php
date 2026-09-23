<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$message = '';
$error = '';

$profileStmt = $conn->prepare("SELECT name, phone, email FROM users WHERE id = ? LIMIT 1");
$profileStmt->bind_param("i", $userId);
$profileStmt->execute();
$profileStmt->store_result();
$profileStmt->bind_result($userName, $userPhone, $userEmail);
$profileStmt->fetch();
$profileStmt->close();

if (isset($_POST['save_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $check->bind_param("si", $email, $userId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = 'That email is already in use.';
    } else {
        $userStmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?");
        $userStmt->bind_param("sssi", $name, $phone, $email, $userId);
        $userStmt->execute();
        $userStmt->close();

        $memberStmt = $conn->prepare("UPDATE members SET name = ?, phone = ?, email = ? WHERE email = ?");
        $memberStmt->bind_param("ssss", $name, $phone, $email, $userEmail);
        $memberStmt->execute();
        $memberStmt->close();

        $_SESSION['user'] = $name;
        $_SESSION['user_email'] = $email;
        $userName = $name;
        $userPhone = $phone;
        $userEmail = $email;
        $message = 'Profile updated successfully.';
    }
    $check->close();
}

if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];

    $passwordStmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $passwordStmt->bind_param("i", $userId);
    $passwordStmt->execute();
    $passwordStmt->store_result();
    $passwordStmt->bind_result($passwordHash);
    $passwordStmt->fetch();
    $passwordStmt->close();

    if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
        $error = 'Current password is incorrect.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updatePassword = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updatePassword->bind_param("si", $newHash, $userId);
        $updatePassword->execute();
        $updatePassword->close();
        $message = 'Password changed successfully.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>User Settings</title>
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
    linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)),
    url('img/gym2.jpg') no-repeat center center;
    background-size:cover;
    background-attachment:fixed;
}
.sidebar{
    width:220px;
    height:100vh;
    padding:20px;
    position:fixed;
    color:white;
    background:rgba(0, 0, 0, 0.4);
    border-right: 1px solid rgba(255,255,255,0.1);
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
}
.sidebar a:hover{ background:rgba(255,255,255,0.2); }
.main{ margin-left:220px; padding:30px; width:100%; }
.cards{ display:flex; gap:20px; flex-wrap:wrap; }
.card{
    background:rgba(255,255,255,0.1);
    backdrop-filter:blur(12px);
    padding:20px;
    border-radius:12px;
    flex:1;
    min-width:320px;
    color:white;
}
input, button{
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
</style>
</head>
<body>
<div class="sidebar">
    <h2>GymERP</h2>
    <a href="user_dashboard.php?section=dashboard">Home</a>
    <a href="user_dashboard.php?section=plans">Plans</a>
    <a href="user_dashboard.php?section=attendance">Attendance</a>
    <a href="user_dashboard.php?section=payments">Payment History</a>
    <a href="user_dashboard.php?section=change_batch">Change Batch</a>
    <a href="user_settings.php">Settings</a>
    <a href="logout.php">Logout</a>
</div>
<div class="main">
    <h2 style="color:white;">User Settings</h2>
    <?php if ($message !== '') { ?><p style="color:#9fffa9;"><?= htmlspecialchars($message) ?></p><?php } ?>
    <?php if ($error !== '') { ?><p style="color:#ff8080;"><?= htmlspecialchars($error) ?></p><?php } ?>

    <div class="cards">
        <div class="card">
            <h3>Personal Details</h3>
            <form method="POST">
                <input type="text" name="name" value="<?= htmlspecialchars($userName) ?>" required>
                <input type="text" name="phone" value="<?= htmlspecialchars($userPhone) ?>" pattern="[0-9]{10}" required>
                <input type="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" required>
                <button type="submit" name="save_profile">Save Profile</button>
            </form>
        </div>

        <div class="card">
            <h3>Credentials</h3>
            <form method="POST">
                <input type="password" name="current_password" placeholder="Current Password" required>
                <input type="password" name="new_password" placeholder="New Password" required>
                <button type="submit" name="change_password">Change Password</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
