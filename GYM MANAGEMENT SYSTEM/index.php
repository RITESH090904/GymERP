<?php
session_start();
$conn = new mysqli("localhost", "root", "", "gym_db");
$conn->set_charset("utf8mb4");

$err = "";
$msg = "";
$admin_key = "ADMIN123";
$trainer_key="TRAIN123";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $specialty = trim($_POST['specialty'] ?? '');
    $salary = 0.00;

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $msg = "Email already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users(name, phone, email, password, role) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $phone, $email, $password, $role);

        if ($stmt->execute()) {
            $newUserId = $conn->insert_id;

            if ($role === 'trainer') {
                $trainerStmt = $conn->prepare("INSERT INTO trainers(user_id, name, specialty, phone, salary) VALUES(?, ?, ?, ?, ?)");
                $trainerStmt->bind_param("isssd", $newUserId, $name, $specialty, $phone, $salary);
                $trainerStmt->execute();
            }

            $msg = "Registration successful.";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];
    $role = $_POST['role'];
    $secret = $_POST['secret'] ?? "";

    $stmt = $conn->prepare("SELECT id, name, phone, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $userName, $userPhone, $userEmail, $userPassword, $userRole);
        $stmt->fetch();

        if (password_verify($pass, $userPassword)) {
            if ($role === "admin") {
                if ($userRole !== "admin") {
                    $err = "You are not registered as admin.";
                } elseif ($secret !== $admin_key) {
                    $err = "Invalid admin secret key.";
                } else {
                    $_SESSION['admin'] = $userName;
                    $_SESSION['admin_id'] = $userId;
                    header("Location: dashboard.php");
                    exit();
                }
            } elseif ($role === "trainer") {
                if ($userRole !== "trainer") {
                    $err = "You are not registered as trainer.";
                } elseif ($secret !== $admin_key) {
                    $err = "Invalid admin secret key.";
                } else {
                    $trainerStmt = $conn->prepare("SELECT id FROM trainers WHERE user_id = ? LIMIT 1");
                    $trainerStmt->bind_param("i", $userId);
                    $trainerStmt->execute();
                    $trainerStmt->store_result();

                    if ($trainerStmt->num_rows === 0) {
                        $err = "Trainer profile not found.";
                    } else {
                        $trainerStmt->bind_result($trainerId);
                        $trainerStmt->fetch();
                        $_SESSION['trainer'] = $userName;
                        $_SESSION['trainer_id'] = $trainerId;
                        $_SESSION['trainer_user_id'] = $userId;
                        header("Location: trainer_dashboard.php");
                        exit();
                    }
                }
            } else {
                $_SESSION['user'] = $userName;
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $userEmail;
                header("Location: user_dashboard.php");
                exit();
            }
        } else {
            $err = "Wrong password.";
        }
    } else {
        $err = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>GymERP Login</title>

<style>
body {
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Arial, sans-serif;
    background:
        linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
        url('img/gym4.jpg') no-repeat center center;
    background-size: cover;
}

.login {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(12px);
    padding: 30px;
    border-radius: 15px;
    width: 320px;
    text-align: center;
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
}

.login input, .login select {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: none;
    border-radius: 8px;
    box-sizing: border-box;
}

.login button {
    width: 100%;
    padding: 10px;
    border: none;
    background: #00c6ff;
    color: white;
    border-radius: 8px;
    cursor: pointer;
}

.login button:hover {
    background: #0072ff;
    transform: scale(1.05);
}

.err { color: #ff8080; }
.msg { color: #9fffa9; }

.toggle {
    color: white;
    cursor: pointer;
    font-size: 14px;
}
</style>

<script>
function toggleForm() {
    const login = document.getElementById("loginForm");
    const reg = document.getElementById("regForm");

    if (login.style.display === "none") {
        login.style.display = "block";
        reg.style.display = "none";
    } else {
        login.style.display = "none";
        reg.style.display = "block";
    }
}

function toggleSecret(role) {
    const secret = document.getElementById("secretField");

    if (role === "admin"){
        secret.style.display = "block";
        secret.required = true;
    } else {
        secret.style.display = "none";
        secret.required = false;
        secret.value = "";
        
    }
}

function toggleSecret(role) {
    const secret = document.getElementById("secretField");

    if (role === "Trainer"){
        secret.style.display = "block";
        secret.required = true;
    } else {
        secret.style.display = "none";
        secret.required = false;
        secret.value = "";
        
    }
}

function toggleRegisterFields(role) {
    const trainerFields = document.getElementById("trainerRegisterFields");
    const specialty = document.getElementById("trainerSpecialty");

    if (role === "trainer") {
        trainerFields.style.display = "block";
        specialty.required = true;
    } else {
        trainerFields.style.display = "none";
        specialty.required = false;
        specialty.value = "";
    }
}
</script>

</head>

<body>

<div class="login">
    <form method="POST" id="loginForm">
        <h2 style="color:white;">Login</h2>

        <input name="email" type="email" placeholder="Email" required>
        <input name="pass" type="password" placeholder="Password" required>

        <label style="color:white;">Login As</label>
        <select name="role" onchange="toggleSecret(this.value)">
            <option value="user">User</option>
            <option value="trainer">Trainer</option>
            <option value="admin">Admin</option>
        </select>

        <input name="secret" id="secretField" placeholder="Admin Secret Key" style="display:none;">

        <button name="login">Login</button>

        <p class="toggle" onclick="toggleForm()">New user? Register</p>
    </form>

    <form method="POST" id="regForm" style="display:none;">
        <h2 style="color:white;">Register</h2>

        <input name="name" placeholder="Name" required>
        <input type="tel" name="phone" placeholder="Phone Number" pattern="[0-9]{10}" required>
        <input name="email" type="email" placeholder="Email" required>
        <input name="pass" type="password" placeholder="Password" required>

        <label style="color:white;">Register As</label>
        <select name="role" onchange="toggleRegisterFields(this.value)">
            <option value="user">User</option>
            <option value="trainer">Trainer</option>
            <option value="admin">Admin</option>
        </select>

        <div id="trainerRegisterFields" style="display:none;">
            <select name="specialty" id="trainerSpecialty">
                <option value="">Select Specialty</option>
                <option value="Weight Training">Weight Training</option>
                <option value="Cardio">Cardio</option>
                <option value="Yoga">Yoga</option>
                <option value="CrossFit">CrossFit</option>
                <option value="Zumba">Zumba</option>
                <option value="Pilates">Pilates</option>
                <option value="personal">Personal Training</option>
            </select>
        </div>

        <button name="register">Register</button>

        <p class="toggle" onclick="toggleForm()">Already have account? Login</p>
    </form>

    <?php if ($err) echo "<p class='err'>" . htmlspecialchars($err) . "</p>"; ?>
    <?php if ($msg) echo "<p class='msg'>" . htmlspecialchars($msg) . "</p>"; ?>
</div>

</body>
</html>
