<?php
// --- IMPORTANT: COPY YOUR LIVE DATABASE CREDENTIALS HERE ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee'; 
// ---------------------------------------------------------

// Establish a connection to the database
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    // In a real application, you'd log this error instead of displaying it
    die("Database connection failed: " . $conn->connect_error);
}

// Get data from the registration form and trim any extra whitespace
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$student_id_card = trim($_POST['student_id_card'] ?? '');
$course = trim($_POST['course'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// --- Step 1: Server-side Validation ---
if (empty($full_name) || empty($email) || empty($student_id_card) || empty($course) || empty($password)) {
    die("Error: All fields are required. Please go back and fill them in.");
}
if ($password !== $confirm_password) {
    die("Error: The passwords you entered do not match. Please go back and try again.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: The email format is invalid. Please go back and enter a valid email address.");
}

// --- Step 2: Check for Duplicate Entries ---
// Use a prepared statement to prevent SQL injection
$sql_check = "SELECT id FROM students WHERE email = ? OR student_id_card = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ss", $email, $student_id_card);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    die("Error: A student with this email or Student ID already exists in the system.");
}
$stmt_check->close();

// --- Step 3: Securely Hash the Password ---
// password_hash() creates a strong, secure hash. Never store plain text passwords.
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// --- Step 4: Use a Transaction for Safe Inserts ---
// A transaction ensures that all database operations succeed or none of them do.
// This prevents partial data from being created if one step fails.
$conn->begin_transaction();

try {
    // Insert the new student into the `students` table
    $sql_student = "INSERT INTO students (name, email, student_id_card, password, course, admission_date) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt_student = $conn->prepare($sql_student);
    $stmt_student->bind_param("sssss", $full_name, $email, $student_id_card, $hashed_password, $course);
    $stmt_student->execute();
    $new_student_id = $conn->insert_id; // Get the ID of the student we just created
    $stmt_student->close();
    
    // Create an initial fee record for the new student
    $total_fee = 95000.00; // You can set a default fee for all new students
    $sql_fees = "INSERT INTO fees (student_id, total_fee, amount_paid) VALUES (?, ?, 0.00)";
    $stmt_fees = $conn->prepare($sql_fees);
    $stmt_fees->bind_param("id", $new_student_id, $total_fee);
    $stmt_fees->execute();
    $new_fee_id = $conn->insert_id; // Get the ID of the new fee record
    $stmt_fees->close();

    // Create a default fee breakdown for the new student
    $tuition_fee = 75000.00;
    $other_fee = 20000.00;
    $sql_breakdown1 = "INSERT INTO fee_breakdown (fee_id, component_name, amount) VALUES (?, 'Tuition Fee', ?)";
    $stmt_b1 = $conn->prepare($sql_breakdown1);
    $stmt_b1->bind_param("id", $new_fee_id, $tuition_fee);
    $stmt_b1->execute();
    $stmt_b1->close();

    $sql_breakdown2 = "INSERT INTO fee_breakdown (fee_id, component_name, amount) VALUES (?, 'Development Fee', ?)";
    $stmt_b2 = $conn->prepare($sql_breakdown2);
    $stmt_b2->bind_param("id", $new_fee_id, $other_fee);
    $stmt_b2->execute();
    $stmt_b2->close();

    // If all database operations were successful, commit the changes
    $conn->commit();

    // Redirect the user to the login page with a success message
    header("Location: login.html?success=1");
    exit();

} catch (mysqli_sql_exception $exception) {
    // If any operation failed, roll back all changes to keep the database consistent
    $conn->rollback();
    die("Registration failed due to a system error. Please try again later. Error: " . $exception->getMessage());
} finally {
    // Always close the database connection
    $conn->close();
}
?>

