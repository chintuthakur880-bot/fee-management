<?php
session_start();
// --- Authentication Check ---
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    http_response_code(401); 
    echo json_encode(['error' => 'Authentication required.']); 
    exit;
}
header('Content-Type: application/json');

// --- YOUR DATABASE CREDENTIALS ---
$dbHost = 'sql301.infinityfree.com';
$dbUser = 'if0_39853943';
$dbPass = 'bits4321';
$dbName = 'if0_39853943_fee'; 
// ---------------------------------------------------------

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_all_students':
        // --- THIS QUERY IS THE ONLY PART THAT HAS BEEN UPDATED ---
        // It now also selects the 'course' from the students table.
        $sql = "SELECT s.id as student_id, s.name, s.student_id_card, s.course, f.total_fee, f.amount_paid, f.last_updated 
                FROM students s 
                LEFT JOIN fees f ON s.id = f.student_id 
                ORDER BY s.name ASC";
        $result = $conn->query($sql);
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        echo json_encode($students);
        break;

    // --- All other features below are unchanged and will continue to work correctly ---
    case 'get_student_details':
        $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        if ($student_id <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID.']); exit; }

        $details = ['student_name' => '', 'breakdown' => []];
        $stmt_name = $conn->prepare("SELECT name FROM students WHERE id = ?");
        $stmt_name->bind_param("i", $student_id);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        if($row_name = $result_name->fetch_assoc()) { $details['student_name'] = $row_name['name']; }

        $stmt_breakdown = $conn->prepare("SELECT fb.component_name, fb.amount FROM fee_breakdown fb JOIN fees f ON fb.fee_id = f.id WHERE f.student_id = ?");
        $stmt_breakdown->bind_param("i", $student_id);
        $stmt_breakdown->execute();
        $result_breakdown = $stmt_breakdown->get_result();
        while($row_b = $result_breakdown->fetch_assoc()) { $details['breakdown'][] = $row_b; }
        
        echo json_encode($details);
        break;

    case 'get_dashboard_summary':
        $sql = "SELECT COUNT(s.id) as total_students, SUM(f.amount_paid) as total_paid, SUM(f.total_fee) as total_allocated FROM students s LEFT JOIN fees f ON s.id = f.student_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $summary = [
            'total_students' => (int)($row['total_students'] ?? 0),
            'total_paid' => (float)($row['total_paid'] ?? 0),
            'total_due' => (float)($row['total_allocated'] ?? 0) - (float)($row['total_paid'] ?? 0)
        ];
        echo json_encode($summary);
        break;
    
    case 'add_bulk_fee':
        $data = json_decode(file_get_contents('php://input'), true);
        $component = $data['component'] ?? null;
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
        if (empty($component) || $amount <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid data.']); exit; }
        
        $conn->begin_transaction();
        try {
            $stmt_update_total = $conn->prepare("UPDATE fees SET total_fee = total_fee + ?");
            $stmt_update_total->bind_param("d", $amount);
            $stmt_update_total->execute();
            
            $result_ids = $conn->query("SELECT id FROM fees");
            $stmt_insert = $conn->prepare("INSERT INTO fee_breakdown (fee_id, component_name, amount) VALUES (?, ?, ?)");
            while ($row = $result_ids->fetch_assoc()) {
                $stmt_insert->bind_param("isd", $row['id'], $component, $amount);
                $stmt_insert->execute();
            }
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error during bulk update.']);
        }
        break;

    case 'update_fee_details':
        $data = json_decode(file_get_contents('php://input'), true);
        $student_id = $data['student_id'] ?? 0;
        $amount_paid = $data['amount_paid'] ?? 0;
        $components = $data['components'] ?? [];
        
        if ($student_id <= 0 || !is_array($components)) { echo json_encode(['success' => false, 'message' => 'Invalid data.']); exit; }

        $conn->begin_transaction();
        try {
            $stmt_fee_id = $conn->prepare("SELECT id FROM fees WHERE student_id = ?");
            $stmt_fee_id->bind_param("i", $student_id);
            $stmt_fee_id->execute();
            $fee_id = $stmt_fee_id->get_result()->fetch_assoc()['id'];
            if (!$fee_id) { throw new Exception("Fee record not found."); }

            $stmt_delete = $conn->prepare("DELETE FROM fee_breakdown WHERE fee_id = ?");
            $stmt_delete->bind_param("i", $fee_id);
            $stmt_delete->execute();

            $new_total_fee = 0;
            $stmt_insert = $conn->prepare("INSERT INTO fee_breakdown (fee_id, component_name, amount) VALUES (?, ?, ?)");
            foreach ($components as $component) {
                if (!empty($component['name']) && is_numeric($component['amount'])) {
                    $name = trim($component['name']);
                    $amount = (float)$component['amount'];
                    $stmt_insert->bind_param("isd", $fee_id, $name, $amount);
                    $stmt_insert->execute();
                    $new_total_fee += $amount;
                }
            }

            $stmt_update_fees = $conn->prepare("UPDATE fees SET total_fee = ?, amount_paid = ? WHERE id = ?");
            $stmt_update_fees->bind_param("ddi", $new_total_fee, $amount_paid, $fee_id);
            $stmt_update_fees->execute();
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Database transaction failed.']);
        }
        break;

    case 'delete_student':
        $data = json_decode(file_get_contents('php://input'), true);
        $student_id = $data['student_id'] ?? 0;
        if ($student_id > 0) {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            if ($stmt->execute()) { echo json_encode(['success' => true]); } 
            else { echo json_encode(['success' => false, 'message' => 'Delete failed.']); }
        } else { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
        break;
}
$conn->close();
?>

