<?php
include 'includes/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conn->prepare(
    "SELECT t.id, t.user_id, t.name, t.specialty, t.phone, t.salary, u.email
     FROM trainers t
     LEFT JOIN users u ON u.id = t.user_id
     WHERE t.id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($trainerId, $trainerUserId, $trainerName, $trainerSpecialty, $trainerPhone, $trainerSalary, $trainerEmail);
$stmt->fetch();

if (!$trainerId) {
    header("Location: trainers.php");
    exit();
}

$message = '';

if (isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $spec = trim($_POST['specialty']);
    $phone = trim($_POST['phone']);
    $salary = (float) $_POST['salary'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($trainerUserId) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $trainerUserId);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = 'That email is already used by another account.';
        } else {
            if ($password !== '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userStmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, email = ?, password = ? WHERE id = ?");
                $userStmt->bind_param("ssssi", $name, $phone, $email, $hashedPassword, $trainerUserId);
            } else {
                $userStmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?");
                $userStmt->bind_param("sssi", $name, $phone, $email, $trainerUserId);
            }
            $userStmt->execute();
        }
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = 'That email is already used by another account.';
        } elseif ($password === '') {
            $message = 'Set a password to create the trainer login.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $createUser = $conn->prepare("INSERT INTO users(name, phone, email, password, role) VALUES (?, ?, ?, ?, 'trainer')");
            $createUser->bind_param("ssss", $name, $phone, $email, $hashedPassword);
            $createUser->execute();
            $trainerUserId = $conn->insert_id;
        }
    }

    if ($message === '') {
        $update = mysqli_prepare($conn, "UPDATE trainers SET user_id = ?, name = ?, specialty = ?, phone = ?, salary = ? WHERE id = ?");
        mysqli_stmt_bind_param($update, "isssdi", $trainerUserId, $name, $spec, $phone, $salary, $id);
        mysqli_stmt_execute($update);
        header("Location: trainers.php");
        exit();
    }
}
?>

<h2>Edit Trainer</h2>

<?php if ($message !== '') { ?>
<p style="text-align:center;color:#ff8080;"><?= htmlspecialchars($message) ?></p>
<?php } ?>

<form method="POST">
    <input type="text" name="name" value="<?= htmlspecialchars($trainerName) ?>" required>

    <select name="specialty" required>
        <option value="Weight Training" <?= ($trainerSpecialty === "Weight Training") ? "selected" : "" ?>>Weight Training</option>
        <option value="Cardio" <?= ($trainerSpecialty === "Cardio") ? "selected" : "" ?>>Cardio</option>
        <option value="Yoga" <?= ($trainerSpecialty === "Yoga") ? "selected" : "" ?>>Yoga</option>
        <option value="CrossFit" <?= ($trainerSpecialty === "CrossFit") ? "selected" : "" ?>>CrossFit</option>
        <option value="Zumba" <?= ($trainerSpecialty === "Zumba") ? "selected" : "" ?>>Zumba</option>
        <option value="Pilates" <?= ($trainerSpecialty === "Pilates") ? "selected" : "" ?>>Pilates</option>
        <option value="personal" <?= ($trainerSpecialty === "personal") ? "selected" : "" ?>>Personal Training</option>
    </select>

    <input type="text" name="phone" value="<?= htmlspecialchars($trainerPhone) ?>" pattern="[0-9]{10}" required>
    <input type="number" name="salary" value="<?= htmlspecialchars($trainerSalary) ?>" required>
    <input type="email" name="email" value="<?= htmlspecialchars($trainerEmail ?? '') ?>" placeholder="Trainer Login Email" required>
    <input type="password" name="password" placeholder="New Password (leave blank to keep current)">

    <button name="update">Update Trainer</button>
</form>

<?php include 'includes/footer.php'; ?>
