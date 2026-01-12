<?php
session_start();
$servername = "sql301.infinityfree.com";
$username = "if0_39853943";
$password = "bits4321";
$database = "if0_39853943_student";

$con = new mysqli($servername, $username, $password, $database);

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (!isset($_SESSION['uname']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$result = $con->query("SELECT * FROM fee");
?>

<h2>Welcome Admin: <?php echo $_SESSION['uname']; ?></h2>

<table border="1" cellpadding="6">
    <tr>
        <th>Student</th><th>Library Fee</th><th>University Fee</th>
        <th>Tuition Fee</th><th>Hostal Fee</th><th>Total</th><th>Receipt</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['uname']; ?></td>
            <td><?php echo $row['LibraryFee']; ?></td>
            <td><?php echo $row['UniversityFee']; ?></td>
            <td><?php echo $row['TuitionFee']; ?></td>
            <td><?php echo $row['HostalFee']; ?></td>
            <td><?php echo $row['TotalFee']; ?></td>
            <td><a href="receipt.php?uname=<?php echo $row['uname']; ?>">Generate</a></td>
        </tr>
    <?php endwhile; ?>
</table>
