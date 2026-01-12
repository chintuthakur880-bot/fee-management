<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); echo json_encode(['error' => 'Auth required.']); exit;
}
header('Content-Type: application/json');

// --- IMPORTANT: COPY YOUR LIVE DATABASE CREDENTIALS HERE ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee';
// ------------------------------------------------------------------
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) { exit(); }

$student_id = $_SESSION['student_id'];
$receipts = [];

// Fetch all receipts for the logged-in student, newest first
$sql = "SELECT id, transaction_id, payment_date, total_paid 
        FROM receipts 
        WHERE student_id = ? 
        ORDER BY payment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $receipts[] = $row;
}
echo json_encode($receipts);

$stmt->close();
$conn->close();
?>
