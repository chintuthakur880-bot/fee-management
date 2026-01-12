<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session completely
session_destroy();

// Redirect the user back to the admin login page
header("location: admin_login.html");
exit;
?>

