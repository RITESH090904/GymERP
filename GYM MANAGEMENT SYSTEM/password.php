 <h3>Change Password</h3>

    <form method="POST">
        <input type="password" name="old" placeholder="Old Password" required>
        <input type="password" name="new" placeholder="New Password" required>
        <button name="change">Change</button>
    </form>

    <?php
    if(isset($_POST['change'])){
        $old = $_POST['old'];
        $new = password_hash($_POST['new'], PASSWORD_DEFAULT);

        $res = $conn->query("SELECT * FROM users WHERE name='$userName'");
        $u = $res->fetch_assoc();

        if(password_verify($old, $u['password'])){
            $conn->query("UPDATE users SET password='$new' WHERE id=".$u['id']);
            echo "<p style='color:green;'>Password Updated!</p>";
        } else {
            echo "<p style='color:red;'>Wrong Old Password!</p>";
        }
    }
    ?>
