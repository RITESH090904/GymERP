<?php 
session_start();

if(isset($_POST['login'])){
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    if($user == 'admin' && $pass == 'admin'){
        $_SESSION['admin'] = 'admin';
        header("Location: dashboard.php");
        exit();
    } else {
        $err = "Invalid login!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>GymERP Login</title>

<style>
/* 🌈 Animated Background */
body {
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Arial;
    background: linear-gradient(-45deg, #667eea, #a1b733, #a85c1e, #2575fc);
    background-size: 400% 400%;
    animation: bgMove 10s ease infinite;
}

@keyframes bgMove {
    0% { background-position: 0% }
    50% { background-position: 100% }
    100% { background-position: 0% }
}

/* 🔥 Glass Login Box */
.login {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(12px);
    padding: 30px;
    border-radius: 15px;
    width: 300px;
    text-align: center;
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
}

/* 📝 Inputs */
.login input {
    width: 90%;
    padding: 10px;
    margin: 10px 0;
    border: none;
    border-radius: 8px;
    outline: none;
    transition: 0.3s;
}

.login input:focus {
    box-shadow: 0 0 10px #00c6ff;
}

/* 🔘 Button */
.login button {
    width: 100%;
    padding: 10px;
    border: none;
    background: #00c6ff;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

.login button:hover {
    background: #0072ff;
    transform: scale(1.05);
}

/* ❌ Error */
.err {
    color: #ff4d4d;
    margin-top: 10px;
}
</style>
</head>

<body>

<div class="login">
    <h2 style="color:white;">GymERP Login</h2>

    <form method="POST">
        <input name="user" placeholder="Username" required>
        <input name="pass" type="password" placeholder="Password" required>
        <button name="login">Login</button>
    </form>

    <?php if(isset($err)) echo "<p class='err'>$err</p>"; ?>
</div>

</body>
</html>
