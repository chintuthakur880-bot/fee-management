<?php
// Start the session to check who is logged in
session_start();

// Redirect to login if no one is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

header('Content-Type: application/json');

// --- IMPORTANT: MAKE SURE THESE ARE YOUR LIVE DATABASE CREDENTIALS ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee'; 
// ------------------------------------------------------------------

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// --- THIS IS THE KEY CHANGE ---
// Get the student ID from the secure session
$student_id = $_SESSION['student_id'];

// Prepare the final response structure
$response = [
    'student_details' => null,
    'fee_breakdown' => [],
    'total_fee' => 0
];

// --- Query 1: Get Student Details for the logged-in student ---
$sql_student = "SELECT id, name, email, student_id_card, course, admission_date FROM students WHERE id = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();

if ($result_student->num_rows > 0) {
    $response['student_details'] = $result_student->fetch_assoc();
} else {
    // This case should ideally not happen if the user is logged in
    echo json_encode(['error' => 'Could not find details for the logged-in student.']);
    $conn->close();
    exit();
}
$stmt_student->close();

// --- Query 2: Get Fee Breakdown for the logged-in student ---
$sql_fees = "SELECT 
                fb.component_name, 
                fb.amount,
                f.total_fee
            FROM fees f
            JOIN fee_breakdown fb ON f.id = fb.fee_id
            WHERE f.student_id = ?";

$stmt_fees = $conn->prepare($sql_fees);
$stmt_fees->bind_param("i", $student_id);
$stmt_fees->execute();
$result_fees = $stmt_fees->get_result();

if ($result_fees->num_rows > 0) {
    while ($row = $result_fees->fetch_assoc()) {
        $response['fee_breakdown'][] = [
            'component_name' => $row['component_name'],
            'amount' => (float)$row['amount']
        ];
        // This will be the same for all rows, so we can just keep setting it
        $response['total_fee'] = (float)$row['total_fee'];
    }
}

// Return the combined, dynamic response
echo json_encode($response);

// Clean up
$stmt_fees->close();
$conn->close();
?>

