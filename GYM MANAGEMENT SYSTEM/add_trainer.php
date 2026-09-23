<?php
include 'includes/db.php';
include 'includes/header.php';

$message = '';

if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $specialty = trim($_POST['specialty']);
    $phone = trim($_POST['phone']);
    $salary = (float) $_POST['salary'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = 'That email is already used by another account.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userStmt = $conn->prepare("INSERT INTO users(name, phone, email, password, role) VALUES (?, ?, ?, ?, 'trainer')");
        $userStmt->bind_param("ssss", $name, $phone, $email, $hashedPassword);
        $userStmt->execute();
        $userId = $conn->insert_id;

        $trainerStmt = mysqli_prepare($conn, "INSERT INTO trainers (user_id, name, specialty, phone, salary) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($trainerStmt, "isssd", $userId, $name, $specialty, $phone, $salary);
        mysqli_stmt_execute($trainerStmt);

        header("Location: trainers.php");
        exit();
    }
}
?>

<h2>Add Trainer</h2>

<?php if ($message !== '') { ?>
<p style="text-align:center;color:#ff8080;"><?= htmlspecialchars($message) ?></p>
<?php } ?>

<form method="POST">
    <input type="text" name="name" placeholder="Enter Name" required>

    <select name="specialty" required>
        <option value="">Select Specialty</option>
        <option value="Weight Training">Weight Training</option>
        <option value="Cardio">Cardio</option>
        <option value="Yoga">Yoga</option>
        <option value="CrossFit">CrossFit</option>
        <option value="Zumba">Zumba</option>
        <option value="Pilates">Pilates</option>
        <option value="personal">Personal Training</option>
    </select>

    <input type="text" name="phone" placeholder="Enter Phone" pattern="[0-9]{10}" required>
    <input type="number" name="salary" placeholder="Enter Salary" required>
    <input type="email" name="email" placeholder="Trainer Login Email" required>
    <input type="password" name="password" placeholder="Temporary Password" required>

    <button type="submit" name="add">Add Trainer</button>
</form>

<?php include 'includes/footer.php'; ?>
