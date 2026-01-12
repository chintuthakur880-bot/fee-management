<?php
// Start the session to store user login state
session_start();

// --- IMPORTANT: COPY YOUR WORKING CREDENTIALS FROM DB_TEST.PHP HERE ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee';      // e.g., 'epiz_33853943_fee'
// ------------------------------------------------------------------

// --- Establish Connection ---
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    // In a real app, you'd log this error, not show it to the user
    header("Location: login.html?error=" . urlencode("Database connection error."));
    exit();
}

// --- Get Data from POST Request ---
$student_id_card = $_POST['student_id_card'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($student_id_card) || empty($password)) {
    header("Location: login.html?error=" . urlencode("Student ID and password are required."));
    exit();
}

// --- Prepare and Execute SQL Query ---
// We select the hashed password and the student's internal ID
$sql = "SELECT id, password FROM students WHERE student_id_card = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id_card);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();
    
    // --- Verify the Password ---
    // password_verify() securely checks the provided password against the stored hash
    if (password_verify($password, $student['password'])) {
        // Password is correct!
        
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store student information in the session
        $_SESSION['loggedin'] = true;
        $_SESSION['student_id'] = $student['id']; // Use the internal numeric ID

        // Redirect to the main fee portal page
        header("Location: home.html");
        exit();
    }
}

// --- If we reach here, login failed ---
header("Location: login.html?error=" . urlencode("Invalid Student ID or password."));
exit();

?>

