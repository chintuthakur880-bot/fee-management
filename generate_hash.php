<?php
// This script generates a secure password hash.
// We will use this to update the database.

// Set the password you want to use
$passwordToHash = 'password123';

// Generate the hash using PHP's secure default algorithm
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);

// Display the result
echo "<h1>Password Hash Generator</h1>";
echo "<p><strong>Password to hash:</strong> " . htmlspecialchars($passwordToHash) . "</p>";
echo "<hr>";
echo "<p><strong>New Secure Hash:</strong></p>";
echo "<p style='font-family: monospace; font-size: 1.2em; background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>" . htmlspecialchars($hashedPassword) . "</p>";
echo "<hr>";
echo "<h3>Action Required:</h3>";
echo "<ol>";
echo "<li>Copy the new secure hash above.</li>";
echo "<li>Go to your phpMyAdmin and open the 'students' table.</li>";
echo "<li>Find the row for 'Rohan Sharma' (ID: CS2025001) and click 'Edit'.</li>";
echo "<li>Paste this new hash into the 'password' field and click 'Go' to save.</li>";
echo "<li>Once saved, try logging in again. It will work.</li>";
echo "</ol>";

?>
