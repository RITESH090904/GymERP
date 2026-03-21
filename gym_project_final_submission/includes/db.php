<?php
$conn = mysqli_connect("localhost","root","","gym_db",3306);
if(!$conn){ die("DB Error: ".mysqli_connect_error()); }
?>