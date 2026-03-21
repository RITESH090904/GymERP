<?php
include 'includes/db.php';
include 'includes/header.php';

// ADD TRAINER
if(isset($_POST['add'])){
    $name = $_POST['name'];
    $specialty = $_POST['specialty'];
    $phone = $_POST['phone'];
    $salary = $_POST['salary'];

    mysqli_query($conn, "INSERT INTO trainers (name, specialty, phone, salary) 
    VALUES ('$name', '$specialty', '$phone', '$salary')");

    header("Location: trainers.php");
    exit();
}
?>

<h2>Add Trainer</h2>

<form method="POST">

    <!-- NAME -->
    <input type="text" name="name" placeholder="Enter Name" required>

    <!-- SPECIALTY -->
    <select name="specialty" required>
        <option value="">Select Specialty</option>
        <option value="Weight Training">Weight Training</option>
        <option value="Cardio">Cardio</option>
        <option value="Yoga">Yoga</option>
        <option value="CrossFit">CrossFit</option>
        <option value="Zumba">Zumba</option>
    </select>

    <!-- PHONE -->
    <input type="text" name="phone" placeholder="Enter Phone" required>

    <!-- SALARY -->
    <input type="number" name="salary" placeholder="Enter Salary" required>

    <!-- BUTTON -->
    <button type="submit" name="add">Add Trainer</button>

</form>

<?php include 'includes/footer.php'; ?>