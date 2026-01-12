<?php
// Start the session
session_start();
 
// Unset all session variables to clear the login state
$_SESSION = array();
 
// Destroy the session completely
session_destroy();
 
// Redirect the user back to the student login page
header("location: login.html");
exit;
?>