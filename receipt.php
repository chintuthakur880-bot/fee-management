<?php
session_start();
// Security check: Must be logged in to view a receipt.
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
    exit("Error connecting to the database. Please check credentials in receipt.php"); 
}

// Get the specific receipt ID from the URL (e.g., receipt.php?id=5)
$receipt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($receipt_id <= 0) {
    header("Location: home.html"); 
    exit;
}

// Fetch the main receipt details from the database.
// CRUCIAL SECURITY CHECK: We also verify that the receipt belongs to the currently logged-in student.
$stmt = $conn->prepare(
    "SELECT r.transaction_id, r.payment_date, r.total_paid, s.name, s.student_id_card 
     FROM receipts r 
     JOIN students s ON r.student_id = s.id 
     WHERE r.id = ? AND r.student_id = ?"
);
$stmt->bind_param("ii", $receipt_id, $_SESSION['student_id']);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

// If no receipt is found (or it belongs to another student), stop the script.
if (!$receipt) {
    echo "Receipt not found or you do not have permission to view it.";
    exit;
}

// Fetch all the individual items that were paid in this specific transaction
$items = [];
$stmt_items = $conn->prepare("SELECT component_name, amount FROM receipt_items WHERE receipt_id = ?");
$stmt_items->bind_param("i", $receipt_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
while($row = $result_items->fetch_assoc()) {
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?php echo htmlspecialchars($receipt['transaction_id']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; }
        @media print {
            body * { visibility: hidden; }
            #receipt-container, #receipt-container * { visibility: visible; }
            #receipt-container { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4 sm:p-6 lg:p-8 max-w-2xl">
        <div id="receipt-container" class="bg-white rounded-lg shadow-lg p-8">
            <header class="flex justify-between items-start border-b pb-6 mb-6">
                <div>
                    <img src="https://bitswgl.ac.in/assets/img/logos/logo.png" alt="BITS Logo" class="h-16 mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Payment Receipt</h1>
                </div>
                <div class="text-right">
                    <p class="text-gray-600">Transaction ID</p>
                    <p class="font-mono font-semibold"><?php echo htmlspecialchars($receipt['transaction_id']); ?></p>
                    <p class="text-gray-600 mt-2">Date</p>
                    <p class="font-semibold"><?php echo date('d M, Y', strtotime($receipt['payment_date'])); ?></p>
                </div>
            </header>
            
            <section class="mb-8">
                <h2 class="text-lg font-semibold text-gray-700 mb-2">Billed To:</h2>
                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($receipt['name']); ?></p>
                <p class="text-gray-600">Student ID: <?php echo htmlspecialchars($receipt['student_id_card']); ?></p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-700 mb-2">Payment Details:</h2>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($item['component_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">₹<?php echo number_format($item['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-6 py-4 text-right font-bold text-gray-800">Total Paid This Transaction</td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 text-lg">₹<?php echo number_format($receipt['total_paid'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
            
            <footer class="text-center text-gray-500 text-sm mt-8">
                <p>This is an official receipt. Thank you for your payment.</p>
            </footer>
        </div>

        <div class="text-center mt-6 no-print">
            <button onclick="window.print()" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-indigo-700">Print Receipt</button>
            <a href="home.html" class="ml-4 text-indigo-600 hover:text-indigo-800">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

