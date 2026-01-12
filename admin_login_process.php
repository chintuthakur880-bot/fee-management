<?php
session_start();
// --- IMPORTANT: COPY YOUR LIVE DATABASE CREDENTIALS HERE ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee'; 
// ---------------------------------------------------------
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    header("Location: admin_login.html?error=" . urlencode("Database error."));
    exit();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header("Location: admin_login.html?error=" . urlencode("All fields required."));
    exit();
}

$sql = "SELECT id, password FROM admins WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    if (password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: admin_dashboard.html");
        exit();
    }
}
header("Location: admin_login.html?error=" . urlencode("Invalid username or password."));
exit();
?>

