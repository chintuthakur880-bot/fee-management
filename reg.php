<?php
$email=$_POST['email'];
$pass1=$_POST['password'];
$uname=$_POST['username'];
$usertype=$_POST['usertype'];
$servername="sql301.infinityfree.com";
$username="if0_39853943";
$password="bits4321";
$database="if0_39853943_student";
$con=new mysqli($servername,$username,$password,$database);
$sql="insert into student22(email,pass1,uname,usertype)values('$email','$pass1','$uname','$usertype')";
$res=$con->query($sql);
if($res)
header("location:login.html");
else
echo("not reg")
?>