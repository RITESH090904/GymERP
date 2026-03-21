<?php
include 'includes/db.php';
include 'includes/header.php';

// GET ID
$id = $_GET['id'];

// FETCH EXISTING DATA
$res = mysqli_query($conn, "SELECT * FROM trainers WHERE id=$id");
$row = mysqli_fetch_assoc($res);

// UPDATE DATA
if(isset($_POST['update'])){
    $name = $_POST['name'];
    $spec = $_POST['specialty']; // ✅ FIXED
    $phone = $_POST['phone'];
    $salary = $_POST['salary'];

    mysqli_query($conn, "UPDATE trainers 
    SET name='$name', specialty='$spec', phone='$phone', salary='$salary' 
    WHERE id=$id");

    header("Location: trainers.php");
    exit();
}
?>

<h2>Edit Trainer</h2>

<form method="POST">

    <!-- NAME -->
    <input type="text" name="name" value="<?= $row['name'] ?>" required>

    <!-- SPECIALTY -->
    <select name="specialty" required>
        <option value="Weight Training" <?= ($row['specialty']=="Weight Training")?"selected":"" ?>>Weight Training</option>
        <option value="Cardio" <?= ($row['specialty']=="Cardio")?"selected":"" ?>>Cardio</option>
        <option value="Yoga" <?= ($row['specialty']=="Yoga")?"selected":"" ?>>Yoga</option>
        <option value="CrossFit" <?= ($row['specialty']=="CrossFit")?"selected":"" ?>>CrossFit</option>
        <option value="Zumba" <?= ($row['specialty']=="Zumba")?"selected":"" ?>>Zumba</option>
    </select>

    <!-- PHONE -->
    <input type="text" name="phone" value="<?= $row['phone'] ?>" placeholder="Phone" required>

    <!-- SALARY -->
    <input type="number" name="salary" value="<?= $row['salary'] ?>" placeholder="Salary" required>

    <button name="update">Update Trainer</button>
</form>

<?php include 'includes/footer.php'; ?>