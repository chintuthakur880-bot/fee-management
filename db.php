<?php
$servername="sql301.infinityfree.com";
$username="if0_39853943";
$password="bits4321";
$database="if0_39853943_student";
$con=new mysqli($servername,$username,$password,$database);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
