<?php
// --- ADD THESE TWO LINES FOR DEBUGGING ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// -----------------------------------------

// --- IMPORTANT: REPLACE WITH YOUR LIVE INFINITYFREE CREDENTIALS ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee';      // <-- Found under "MySQL DB Name" in cPanel
// ------------------------------------------------------------------

// Create connection
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check connection
if ($conn->connect_error) {
    echo "<h1 style='color: red;'>Database Connection FAILED!</h1>";
    // This will show you the exact reason it failed
    echo "<p><strong>Error:</strong> " . $conn->connect_error . "</p>";
    echo "<p>Please double-check every credential in the db_test.php file. Copy and paste them directly from your InfinityFree control panel to avoid typos.</p>";
} else {
    echo "<h1 style='color: green;'>Database Connection SUCCESSFUL!</h1>";
    echo "<p>This proves your credentials are correct.</p>";
    echo "<p><strong>ACTION:</strong> Now, you must copy these exact same credentials into the top of the other four PHP files:</p>";
    echo "<ul>";
    echo "<li>login_process.php</li>";
    echo "<li>get_fee_details.php</li>";
    echo "<li>get_account_details.php</li>";
    echo "<li>process_payment.php</li>";
    echo "</ul>";
}

$conn->close();
?>

