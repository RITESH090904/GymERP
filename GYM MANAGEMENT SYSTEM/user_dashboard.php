<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$userName = $_SESSION['user'];
$userEmail = $_SESSION['user_email'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
$activeSection = $_GET['section'] ?? 'dashboard';

if (isset($_POST['buy'])) {
    $plan = trim($_POST['plan']);
    $batchId = (int) $_POST['batch_id'];
    $paymentMode = $_POST['payment_mode'];
    $date = date("Y-m-d");

    $planStmt = $conn->prepare("SELECT duration, price FROM plans WHERE name = ? LIMIT 1");
    $planStmt->bind_param("s", $plan);
    $planStmt->execute();
    $planStmt->store_result();
    $planStmt->bind_result($planDuration, $planPrice);
    $planStmt->fetch();

    if (!$planDuration) {
        header("Location: user_dashboard.php?error=invalid_plan");
        exit();
    }

    $batchStmt = $conn->prepare("SELECT capacity, (SELECT COUNT(*) FROM members WHERE batch_id = ?) AS total_members FROM batches WHERE id = ? LIMIT 1");
    $batchStmt->bind_param("ii", $batchId, $batchId);
    $batchStmt->execute();
    $batchStmt->store_result();
    $batchStmt->bind_result($batchCapacity, $batchCount);
    $batchStmt->fetch();

    if (!$batchCapacity) {
        header("Location: user_dashboard.php?error=invalid_batch");
        exit();
    }

    $memberStmt = $conn->prepare("SELECT id, batch_id FROM members WHERE email = ? LIMIT 1");
    $memberStmt->bind_param("s", $userEmail);
    $memberStmt->execute();
    $memberStmt->store_result();
    $memberStmt->bind_result($existingMemberId, $existingBatchId);
    $memberStmt->fetch();

    if ($batchCount >= $batchCapacity && (!$existingMemberId || (int) $existingBatchId !== $batchId)) {
        header("Location: user_dashboard.php?error=batch_full");
        exit();
    }

    $installmentCount = 1;
    if ($paymentMode === 'emi_2') {
        $installmentCount = 2;
    } elseif ($paymentMode === 'emi_3') {
        $installmentCount = 3;
    } elseif ($paymentMode === 'emi_6') {
        $installmentCount = 6;
    }

    $installmentAmount = round(((float) $planPrice) / $installmentCount, 2);
    $expiryDate = date("Y-m-d", strtotime("+" . (int) $planDuration . " days", strtotime($date)));

    $userStmt = $conn->prepare("SELECT name, phone, email FROM users WHERE id = ? LIMIT 1");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userStmt->store_result();
    $userStmt->bind_result($dbUserName, $dbUserPhone, $dbUserEmail);
    $userStmt->fetch();

    $paymentLabel = $installmentCount > 1 ? 'emi' : 'full';

    $paymentStmt = $conn->prepare(
        "INSERT INTO user_plans(user_name, plan_name, price, payment_mode, installment_count, installment_amount, installments_paid, last_payment_date, purchase_date, expiry_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $installmentsPaid = 1;
    $paymentStmt->bind_param("ssisidisss", $dbUserName, $plan, $planPrice, $paymentLabel, $installmentCount, $installmentAmount, $installmentsPaid, $date, $date, $expiryDate);
    $paymentStmt->execute();

    if ($existingMemberId) {
        $update = $conn->prepare("UPDATE members SET name = ?, phone = ?, plan = ?, purchase_date = ?, batch_id = ? WHERE id = ?");
        $update->bind_param("ssssii", $dbUserName, $dbUserPhone, $plan, $date, $batchId, $existingMemberId);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO members(name, phone, email, purchase_date, plan, batch_id) VALUES(?, ?, ?, ?, ?, ?)");
        $insert->bind_param("sssssi", $dbUserName, $dbUserPhone, $dbUserEmail, $date, $plan, $batchId);
        $insert->execute();
    }

    header("Location: user_dashboard.php?success=1");
    exit();
}

if (isset($_POST['change_batch'])) {
    $newBatchId = (int) $_POST['new_batch_id'];

    $memberStmt = $conn->prepare("SELECT id, batch_id, purchase_date, plan FROM members WHERE email = ? LIMIT 1");
    $memberStmt->bind_param("s", $userEmail);
    $memberStmt->execute();
    $memberStmt->store_result();
    $memberStmt->bind_result($memberId, $currentBatchId, $purchaseDate, $currentPlanName);
    $memberStmt->fetch();

    if (!$memberId || empty($purchaseDate) || empty($currentPlanName)) {
        header("Location: user_dashboard.php?error=no_membership");
        exit();
    }

    $validityStmt = $conn->prepare("SELECT duration FROM plans WHERE name = ? LIMIT 1");
    $validityStmt->bind_param("s", $currentPlanName);
    $validityStmt->execute();
    $validityStmt->store_result();
    $validityStmt->bind_result($currentPlanDuration);
    $validityStmt->fetch();

    $expiryDate = $currentPlanDuration ? date("Y-m-d", strtotime("+" . (int) $currentPlanDuration . " days", strtotime($purchaseDate))) : null;
    if (empty($expiryDate) || strtotime($expiryDate) < strtotime(date("Y-m-d"))) {
        header("Location: user_dashboard.php?error=membership_expired");
        exit();
    }

    $batchStmt = $conn->prepare("SELECT capacity, (SELECT COUNT(*) FROM members WHERE batch_id = ?) AS total_members FROM batches WHERE id = ? LIMIT 1");
    $batchStmt->bind_param("ii", $newBatchId, $newBatchId);
    $batchStmt->execute();
    $batchStmt->store_result();
    $batchStmt->bind_result($batchCapacity, $batchCount);
    $batchStmt->fetch();

    if (!$batchCapacity) {
        header("Location: user_dashboard.php?error=invalid_batch");
        exit();
    }

    if ($batchCount >= $batchCapacity && (int) $currentBatchId !== $newBatchId) {
        header("Location: user_dashboard.php?error=batch_full");
        exit();
    }

    $updateBatch = $conn->prepare("UPDATE members SET batch_id = ? WHERE id = ?");
    $updateBatch->bind_param("ii", $newBatchId, $memberId);
    $updateBatch->execute();

    header("Location: user_dashboard.php?batch_changed=1");
    exit();
}

if (isset($_POST['pay_installment'])) {
    $paymentId = (int) $_POST['payment_id'];
    $today = date("Y-m-d");

    $paymentLookup = $conn->prepare(
        "SELECT id, installment_count, installments_paid
         FROM user_plans
         WHERE id = ? AND user_name = ?
         LIMIT 1"
    );
    $paymentLookup->bind_param("is", $paymentId, $userName);
    $paymentLookup->execute();
    $paymentLookup->store_result();
    $paymentLookup->bind_result($foundPaymentId, $installmentCount, $installmentsPaid);
    $paymentLookup->fetch();
    $paymentLookup->close();

    if ($foundPaymentId && $installmentsPaid < $installmentCount) {
        $nextPaid = $installmentsPaid + 1;
        $payStmt = $conn->prepare("UPDATE user_plans SET installments_paid = ?, last_payment_date = ? WHERE id = ?");
        $payStmt->bind_param("isi", $nextPaid, $today, $paymentId);
        $payStmt->execute();
        $payStmt->close();
        header("Location: user_dashboard.php?installment_paid=1");
        exit();
    }

    header("Location: user_dashboard.php?error=no_installment_due");
    exit();
}

if (isset($_POST['change'])) {
    $old = $_POST['old'];
    $new = password_hash($_POST['new'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($dbPasswordUserId, $dbPasswordHash);
    $stmt->fetch();

    if ($dbPasswordUserId && password_verify($old, $dbPasswordHash)) {
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new, $dbPasswordUserId);
        $update->execute();
        echo "<script>alert('Password updated');</script>";
    } else {
        echo "<script>alert('Wrong old password');</script>";
    }
}

$currentMembership = mysqli_query(
    $conn,
    "SELECT m.id AS member_id, m.batch_id, m.plan, m.purchase_date, b.name AS batch_name, b.schedule, t.name AS trainer_name,
            DATE_ADD(m.purchase_date, INTERVAL COALESCE(p.duration, 0) DAY) AS expiry_date
     FROM members m
     LEFT JOIN batches b ON b.id = m.batch_id
     LEFT JOIN trainers t ON t.id = b.trainer_id
     LEFT JOIN plans p ON p.name = m.plan
     WHERE m.email = '" . mysqli_real_escape_string($conn, $userEmail) . "'
     ORDER BY m.id DESC
     LIMIT 1"
);
$membershipData = $currentMembership ? mysqli_fetch_assoc($currentMembership) : null;
?>

<!DOCTYPE html>
<html>
<head>
<title>User Dashboard</title>

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
    transition:0.3s;
    cursor:pointer;
}

.sidebar a:hover{
    background:rgba(255,255,255,0.2);
    transform:translateX(5px);
}

.main{
    margin-left:220px;
    padding:30px;
    width:100%;
}

.cards{
    display:flex;
    gap:20px;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.card{
    background:rgba(255,255,255,0.1);
    backdrop-filter:blur(12px);
    padding:20px;
    border-radius:12px;
    flex:1;
    min-width:220px;
    color:white;
    box-shadow:0 5px 15px rgba(0,0,0,0.3);
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(10px);
    color:white;
    border-radius:10px;
    overflow:hidden;
}

th{
    background:rgba(255,255,255,0.2);
    padding:12px;
}

td{
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}

tr:hover{
    background:rgba(255,255,255,0.1);
}

button{
    padding:10px 15px;
    background:linear-gradient(45deg,#00c6ff,#0072ff);
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

button:hover{
    transform:scale(1.05);
}

h2,h3{
    text-align:center;
    color:white;
}

input, select{
    padding:10px;
    margin:10px;
    border-radius:6px;
    border:none;
}

.section{
    display:none;
}
</style>

</head>

<body>

<?php
if (isset($_GET['success'])) {
    echo "<script>
        alert('Payment successful.');
        window.history.replaceState(null, null, 'user_dashboard.php');
    </script>";
}

if (isset($_GET['batch_changed'])) {
    echo "<script>
        alert('Batch changed successfully.');
        window.history.replaceState(null, null, 'user_dashboard.php');
    </script>";
}

if (isset($_GET['installment_paid'])) {
    echo "<script>
        alert('Installment paid successfully.');
        window.history.replaceState(null, null, 'user_dashboard.php');
    </script>";
}

if (isset($_GET['error'])) {
    $errorMessage = 'Something went wrong.';
    if ($_GET['error'] === 'invalid_plan') {
        $errorMessage = 'Selected plan is not available.';
    } elseif ($_GET['error'] === 'invalid_batch') {
        $errorMessage = 'Selected batch is not available.';
    } elseif ($_GET['error'] === 'batch_full') {
        $errorMessage = 'Selected batch is already full.';
    } elseif ($_GET['error'] === 'no_membership') {
        $errorMessage = 'Buy a plan first before changing batch.';
    } elseif ($_GET['error'] === 'membership_expired') {
        $errorMessage = 'Your membership has expired. Renew your plan before changing batch.';
    } elseif ($_GET['error'] === 'no_installment_due') {
        $errorMessage = 'No installment payment is pending for this plan.';
    }

    echo "<script>
        alert(" . json_encode($errorMessage) . ");
        window.history.replaceState(null, null, 'user_dashboard.php');
    </script>";
}
?>

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

<div id="dashboard" class="section" style="display:<?= $activeSection === 'dashboard' ? 'block' : 'none' ?>;">
<h2>Welcome <?= htmlspecialchars($userName) ?></h2>
<div class="cards">
<div class="card">
    <h4>Current Plan</h4>
    <p><?= htmlspecialchars($membershipData['plan'] ?? 'No Active Plan') ?></p>
</div>
<div class="card">
    <h4>Selected Batch</h4>
    <p><?= htmlspecialchars($membershipData['batch_name'] ?? 'Not Selected') ?></p>
</div>
<div class="card">
    <h4>Trainer</h4>
    <p><?= htmlspecialchars($membershipData['trainer_name'] ?? 'Not Assigned') ?></p>
</div>
<div class="card">
    <h4>Plan Expiry Date</h4>
    <p><?= htmlspecialchars($membershipData['expiry_date'] ?? 'No Active Plan') ?></p>
</div>
<div class="card">
    <h4>Batch Schedule</h4>
    <p><?= htmlspecialchars($membershipData['schedule'] ?? 'Not Assigned') ?></p>
</div>
</div>
</div>

<div id="plans" class="section" style="display:<?= $activeSection === 'plans' ? 'block' : 'none' ?>;">
<h3>Buy Plans And Select Batch</h3>
<div class="cards">
<?php
$plans = mysqli_query($conn, "SELECT * FROM plans ORDER BY price ASC");
while ($p = mysqli_fetch_assoc($plans)) {
    $planNameJs = htmlspecialchars(json_encode($p['name']), ENT_QUOTES, 'UTF-8');
    echo "<div class='card'>
<h4>" . htmlspecialchars($p['name']) . "</h4>
<p>Rs " . (int) $p['price'] . "</p>
<p>" . (int) $p['duration'] . " Days</p>
<button type=\"button\" onclick=\"openPayment(" . $planNameJs . "," . (int) $p['price'] . "," . (int) $p['duration'] . ")\">
    Buy Now
</button>
</div>";
}
?>
</div>
</div>

<div id="attendance" class="section" style="display:<?= $activeSection === 'attendance' ? 'block' : 'none' ?>;">
<h3>Attendance</h3>
<table>
<tr><th>Date</th><th>Status</th></tr>
<?php
$res = $conn->prepare("SELECT a.date, a.status FROM attendance a JOIN members m ON a.member_id = m.id WHERE m.email = ? ORDER BY a.date DESC");
$res->bind_param("s", $userEmail);
$res->execute();
$res->bind_result($attendanceDate, $attendanceStatus);
$hasAttendance = false;
while ($res->fetch()) {
    $hasAttendance = true;
    echo "<tr><td>" . htmlspecialchars($attendanceDate) . "</td><td>" . htmlspecialchars($attendanceStatus) . "</td></tr>";
}
if (!$hasAttendance) {
    echo "<tr><td colspan='2'>No attendance records found.</td></tr>";
}
?>
</table>
</div>

<div id="payments" class="section" style="display:<?= $activeSection === 'payments' ? 'block' : 'none' ?>;">
<h3>Payment History</h3>
<table>
<tr><th>Plan</th><th>Total Amount</th><th>Mode</th><th>Installments</th><th>Paid</th><th>Remaining</th><th>EMI Amount</th><th>Last Payment</th><th>Purchase Date</th><th>Expiry Date</th><th>Action</th></tr>
<?php
$payments = $conn->prepare(
    "SELECT id, plan_name, price, payment_mode, installment_count, installments_paid, installment_amount, last_payment_date, purchase_date, expiry_date
     FROM user_plans
     WHERE user_name = ?
     ORDER BY purchase_date DESC, id DESC"
);
$payments->bind_param("s", $userName);
$payments->execute();
$payments->bind_result($paymentId, $paymentPlanName, $paymentPrice, $paymentMode, $paymentInstallments, $paymentInstallmentsPaid, $paymentInstallmentAmount, $lastPaymentDate, $paymentDate, $paymentExpiryDate);
$hasPayments = false;
while ($payments->fetch()) {
    $hasPayments = true;
    $remainingInstallments = max(0, (int) $paymentInstallments - (int) $paymentInstallmentsPaid);
    echo "<tr>
<td>" . htmlspecialchars($paymentPlanName) . "</td>
<td>Rs " . (int) $paymentPrice . "</td>
<td>" . htmlspecialchars(strtoupper($paymentMode)) . "</td>
<td>" . (int) $paymentInstallments . "</td>
<td>" . (int) $paymentInstallmentsPaid . "</td>
<td>" . $remainingInstallments . "</td>
<td>Rs " . number_format((float) $paymentInstallmentAmount, 2) . "</td>
<td>" . htmlspecialchars($lastPaymentDate ?: $paymentDate) . "</td>
<td>" . htmlspecialchars($paymentDate) . "</td>
<td>" . htmlspecialchars($paymentExpiryDate) . "</td>
<td>";
    if ($remainingInstallments > 0 && strtolower($paymentMode) === 'emi') {
        echo "<form method='POST' style='margin:0;'>
<input type='hidden' name='payment_id' value='" . (int) $paymentId . "'>
<button type='submit' name='pay_installment'>Pay Next Installment</button>
</form>";
    } else {
        echo "Completed";
    }
    echo "</td>
</tr>";
}
if (!$hasPayments) {
    echo "<tr><td colspan='11'>No payment history found.</td></tr>";
}
?>
</table>
</div>

<div id="change_batch" class="section" style="display:<?= $activeSection === 'change_batch' ? 'block' : 'none' ?>;">
<h3>Change Batch According To Your Time</h3>
<div class="card" style="max-width:760px;">
    <p>Current Batch: <?= htmlspecialchars($membershipData['batch_name'] ?? 'Not Selected') ?></p>
    <p>Current Schedule: <?= htmlspecialchars($membershipData['schedule'] ?? 'Not Assigned') ?></p>
    <form method="POST">
        <select name="new_batch_id" required>
            <option value="">Select New Batch</option>
            <?php
            $availableBatches = mysqli_query(
                $conn,
                "SELECT b.id, b.name, b.capacity, b.schedule, t.name AS trainer_name,
                        COUNT(m.id) AS total_members
                 FROM batches b
                 LEFT JOIN trainers t ON t.id = b.trainer_id
                 LEFT JOIN members m ON m.batch_id = b.id
                 GROUP BY b.id, b.name, b.capacity, b.schedule, t.name
                 HAVING COUNT(m.id) < b.capacity OR b.id = " . (int) ($membershipData['batch_id'] ?? 0) . "
                 ORDER BY b.name ASC"
            );
            while ($batch = mysqli_fetch_assoc($availableBatches)) {
                echo '<option value="' . (int) $batch['id'] . '"' . ((int) ($membershipData['batch_id'] ?? 0) === (int) $batch['id'] ? ' selected' : '') . '>' .
                    htmlspecialchars($batch['name']) .
                    ' - ' . htmlspecialchars($batch['schedule']) .
                    ' - Trainer: ' . htmlspecialchars($batch['trainer_name'] ?: 'Not Assigned') .
                    ' (' . (int) $batch['total_members'] . '/' . (int) $batch['capacity'] . ')' .
                    '</option>';
            }
            ?>
        </select>
        <button type="submit" name="change_batch">Update Batch</button>
    </form>
    <p>You can change batch while your membership is active.</p>
</div>
</div>

</div>

<script>
function updateInstallmentPreview() {
    const mode = document.getElementById("paymentMode").value;
    const total = Number(document.getElementById("hiddenPrice").value || 0);
    let count = 1;

    if (mode === "emi_2") count = 2;
    if (mode === "emi_3") count = 3;
    if (mode === "emi_6") count = 6;

    const perInstallment = (total / count).toFixed(2);
    document.getElementById("installmentPreview").innerText =
        count === 1
            ? "Full payment: Rs " + total
            : "EMI: " + count + " installments of Rs " + perInstallment;
}

function openPayment(name, price, duration) {
    document.getElementById("paymentModal").style.display = "block";
    document.getElementById("planName").innerText = "Plan: " + name;
    document.getElementById("planPrice").innerText = "Total Price: Rs " + price;
    document.getElementById("planDuration").innerText = "Duration: " + duration + " days";
    document.getElementById("hiddenPlan").value = name;
    document.getElementById("hiddenPrice").value = price;
    document.getElementById("paymentMode").value = "full";
    updateInstallmentPreview();
}

function closeModal() {
    document.getElementById("paymentModal").style.display = "none";
}
</script>

<div id="paymentModal" style="
display:none;
position:fixed;
top:0; left:0;
width:100%; height:100%;
background:rgba(0,0,0,0.7);
z-index:9999;
">

    <div style="
    background:white;
    padding:20px;
    width:360px;
    margin:100px auto;
    border-radius:10px;
    text-align:center;
    ">

        <h3 id="planName" style="color:#111;"></h3>
        <p id="planPrice" style="color:#111;"></p>
        <p id="planDuration" style="color:#111;"></p>

        <form method="POST">
            <input type="hidden" name="plan" id="hiddenPlan">
            <input type="hidden" id="hiddenPrice">

            <select name="batch_id" required>
                <option value="">Select Batch</option>
                <?php
                $planBatches = mysqli_query(
                    $conn,
                    "SELECT b.id, b.name, b.capacity, b.schedule, t.name AS trainer_name,
                            COUNT(m.id) AS total_members
                     FROM batches b
                     LEFT JOIN trainers t ON t.id = b.trainer_id
                     LEFT JOIN members m ON m.batch_id = b.id
                     GROUP BY b.id, b.name, b.capacity, b.schedule, t.name
                     HAVING COUNT(m.id) < b.capacity OR b.id = " . (int) ($membershipData['batch_id'] ?? 0) . "
                     ORDER BY b.name ASC"
                );
                while ($batch = mysqli_fetch_assoc($planBatches)) {
                    echo '<option value="' . (int) $batch['id'] . '">' .
                        htmlspecialchars($batch['name']) .
                        ' - ' . htmlspecialchars($batch['schedule']) .
                        ' - Trainer: ' . htmlspecialchars($batch['trainer_name'] ?: 'Not Assigned') .
                        ' (' . (int) $batch['total_members'] . '/' . (int) $batch['capacity'] . ')' .
                        '</option>';
                }
                ?>
            </select>

            <select name="payment_mode" id="paymentMode" onchange="updateInstallmentPreview()" required>
                <option value="full">Full Payment</option>
                <option value="emi_2">2 Installments</option>
                <option value="emi_3">3 Installments</option>
                <option value="emi_6">6 Installments</option>
            </select>

            <p id="installmentPreview" style="color:#111;"></p>

            <button type="submit" name="buy">Confirm Payment</button>
        </form>

        <br>
        <button type="button" onclick="closeModal()">Cancel</button>
    </div>
</div>

</body>
</html>
