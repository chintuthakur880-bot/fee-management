<?php
session_start();
// Security check: If not logged in, redirect.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html"); 
    exit;
}

// --- IMPORTANT: COPY YOUR LIVE DATABASE CREDENTIALS HERE ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee'; 
// ------------------------------------------------------------------

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) { 
    header("Location: home.html?payment=error"); 
    exit(); 
}

$student_id = $_SESSION['student_id'];
$amount_to_pay = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$ids_to_update_str = $_POST['ids'] ?? '';

if ($student_id > 0 && $amount_to_pay > 0 && !empty($ids_to_update_str)) {
    
    $conn->begin_transaction();
    
    try {
        // Step 1 & 2: Update fees and component status (this part is correct)
        $stmt1 = $conn->prepare("UPDATE fees SET amount_paid = amount_paid + ? WHERE student_id = ?");
        $stmt1->bind_param("di", $amount_to_pay, $student_id);
        $stmt1->execute();

        $ids_to_update = explode(',', $ids_to_update_str);
        $placeholders = implode(',', array_fill(0, count($ids_to_update), '?'));
        $stmt2 = $conn->prepare("UPDATE fee_breakdown SET status = 'paid' WHERE id IN ($placeholders)");
        $stmt2->bind_param(str_repeat('i', count($ids_to_update)), ...$ids_to_update);
        $stmt2->execute();

        // --- STEP 3: SAVE PERMANENT RECEIPT TO DATABASE ---
        $transaction_id = 'TXN' . time() . $student_id;
        $payment_date = date('Y-m-d H:i:s');
        
        $stmt_receipt = $conn->prepare("INSERT INTO receipts (student_id, transaction_id, payment_date, total_paid) VALUES (?, ?, ?, ?)");
        $stmt_receipt->bind_param("issd", $student_id, $transaction_id, $payment_date, $amount_to_pay);
        $stmt_receipt->execute();
        $receipt_id = $conn->insert_id; // Get the ID of the new receipt

        $stmt_items = $conn->prepare("SELECT component_name, amount FROM fee_breakdown WHERE id IN ($placeholders)");
        $stmt_items->bind_param(str_repeat('i', count($ids_to_update)), ...$ids_to_update);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        
        $stmt_insert_item = $conn->prepare("INSERT INTO receipt_items (receipt_id, component_name, amount) VALUES (?, ?, ?)");
        while($item = $result_items->fetch_assoc()) {
            $stmt_insert_item->bind_param("isd", $receipt_id, $item['component_name'], $item['amount']);
            $stmt_insert_item->execute();
        }

        $conn->commit();
        // --- STEP 4: REDIRECT TO THE PERMANENT RECEIPT PAGE ---
        header("Location: receipt.php?id=" . $receipt_id);
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
    }
}

// If anything fails, redirect with an error
header("Location: home.html?payment=error");
exit();
?>

