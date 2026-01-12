<?php
session_start();

// Security check: If not logged in, stop right here.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); 
    echo json_encode(['error' => 'Authentication required.']); 
    exit;
}
header('Content-Type: application/json');

// --- YOUR LIVE DATABASE CREDENTIALS ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee';
// ------------------------------------------------------------------

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed in get_fee_details.php']);
    exit();
}

$student_id = $_SESSION['student_id'];
$response = [];

// --- Query 1: Get the main summary details ---
$sql_main = "SELECT s.id, s.name AS student_name, f.id as fee_id, f.total_fee, f.amount_paid 
             FROM students s 
             JOIN fees f ON s.id = f.student_id 
             WHERE s.id = ?";
$stmt_main = $conn->prepare($sql_main);
$stmt_main->bind_param("i", $student_id);
$stmt_main->execute();
$result_main = $stmt_main->get_result();

if ($row_main = $result_main->fetch_assoc()) {
    $response['student_name'] = $row_main['student_name'];
    $response['total_fee']    = (float)$row_main['total_fee'];
    $response['amount_paid']  = (float)$row_main['amount_paid'];
    $response['amount_due']   = (float)$row_main['total_fee'] - (float)$row_main['amount_paid'];
    
    // --- Query 2: Get the detailed fee breakdown of UNPAID fees ---
    $fee_id = $row_main['fee_id'];
    $response['breakdown'] = []; 
    $sql_breakdown = "SELECT id, component_name, amount FROM fee_breakdown WHERE fee_id = ? AND status = 'unpaid'";
    $stmt_breakdown = $conn->prepare($sql_breakdown);
    $stmt_breakdown->bind_param("i", $fee_id);
    $stmt_breakdown->execute();
    $result_breakdown = $stmt_breakdown->get_result();

    while($row_b = $result_breakdown->fetch_assoc()) {
        $response['breakdown'][] = $row_b;
    }
    $stmt_breakdown->close();

    echo json_encode($response);
} else {
    echo json_encode(['error' => 'No fee details found for this user.']);
}

$stmt_main->close();
$conn->close();
?>

