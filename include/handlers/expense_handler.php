<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require 'dbhandler.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

$currentUser = $_SESSION['username'] ?? 'System';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? '';

error_log("Expense Handler - Action: $action, Data: " . json_encode($data));

try {
    switch ($action) {
        case 'add_expense':
            $tripId = $data['trip_id'] ?? null;
            $driverId = $data['driver_id'] ?? null;
            $truckId = $data['truck_id'] ?? null;
            $expenseType = $data['expense_type'] ?? null;
            $amount = $data['amount'] ?? null;
            
            if (!$tripId || !$driverId || !$expenseType || !$amount) {
                throw new Exception("Missing required fields. Trip ID: $tripId, Driver ID: $driverId, Type: $expenseType, Amount: $amount");
            }
            
            // Validate amount is positive
            if ($amount <= 0) {
                throw new Exception("Amount must be greater than 0");
            }
            
            // Get truck_id from trip if not provided
            if (!$truckId) {
                $getTruckStmt = $conn->prepare("
                    SELECT t.truck_id 
                    FROM assign a 
                    JOIN truck_table t ON a.plate_no = t.plate_no 
                    WHERE a.trip_id = ?
                ");
                if ($getTruckStmt === false) {
                    throw new Exception("Failed to prepare truck query: " . $conn->error);
                }
                $getTruckStmt->bind_param("i", $tripId);
                $getTruckStmt->execute();
                $truckResult = $getTruckStmt->get_result();
                if ($truckResult->num_rows > 0) {
                    $truckId = $truckResult->fetch_assoc()['truck_id'];
                }
                $getTruckStmt->close();
            }
            
            // Check if created_at column exists
            $columnsQuery = $conn->query("SHOW COLUMNS FROM expenses LIKE 'created_at'");
            $hasCreatedAt = $columnsQuery && $columnsQuery->num_rows > 0;
            
            if ($hasCreatedAt) {
                $stmt = $conn->prepare("INSERT INTO expenses 
                    (trip_id, driver_id, truck_id, expense_type, amount, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
                
                if ($stmt === false) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }
                
                $stmt->bind_param("iiisds", $tripId, $driverId, $truckId, $expenseType, $amount, $currentUser);
            } else {
                $stmt = $conn->prepare("INSERT INTO expenses 
                    (trip_id, driver_id, truck_id, expense_type, amount, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($stmt === false) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }
                
                $stmt->bind_param("iiisds", $tripId, $driverId, $truckId, $expenseType, $amount, $currentUser);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute insert: " . $stmt->error);
            }
            
            $newExpenseId = $conn->insert_id;
            $stmt->close();
            
            error_log("Expense added successfully. ID: $newExpenseId");
            echo json_encode(['success' => true, 'expense_id' => $newExpenseId]);
            break;

        case 'get_expenses_by_trip':
            $tripId = $data['trip_id'] ?? null;
            
            if (!$tripId) {
                throw new Exception("Trip ID required");
            }
            
            // Check if created_at column exists
            $columnsQuery = $conn->query("SHOW COLUMNS FROM expenses LIKE 'created_at'");
            $hasCreatedAt = $columnsQuery && $columnsQuery->num_rows > 0;
            
            if ($hasCreatedAt) {
                $sql = "
                    SELECT e.*, d.name as driver_name, t.plate_no, a.destination,
                           DATE_FORMAT(e.created_at, '%m/%d/%y') as formatted_date,
                           a.cash_adv
                    FROM expenses e
                    LEFT JOIN drivers_table d ON e.driver_id = d.driver_id
                    LEFT JOIN truck_table t ON e.truck_id = t.truck_id
                    LEFT JOIN assign a ON e.trip_id = a.trip_id
                    WHERE e.trip_id = ?
                    ORDER BY e.created_at DESC
                ";
            } else {
                $sql = "
                    SELECT e.*, d.name as driver_name, t.plate_no, a.destination,
                           DATE_FORMAT(NOW(), '%m/%d/%y') as formatted_date,
                           a.cash_adv
                    FROM expenses e
                    LEFT JOIN drivers_table d ON e.driver_id = d.driver_id
                    LEFT JOIN truck_table t ON e.truck_id = t.truck_id
                    LEFT JOIN assign a ON e.trip_id = a.trip_id
                    WHERE e.trip_id = ?
                    ORDER BY e.expense_id DESC
                ";
            }
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare expenses query: " . $conn->error);
            }
            
            $stmt->bind_param("i", $tripId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute expenses query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $expenses = [];
            $totalExpenses = 0;
            $cashAdvanceFromDB = 0;
            
            while ($row = $result->fetch_assoc()) {
                $expenses[] = $row;
                $totalExpenses += floatval($row['amount']);
                // Get cash advance from the first row (should be same for all expenses in the trip)
                if ($row['cash_adv'] && $cashAdvanceFromDB == 0) {
                    $cashAdvanceFromDB = floatval($row['cash_adv']);
                }
            }
            
            $stmt->close();
            
            error_log("Trip expenses query result - Trip ID: $tripId, Expenses: " . count($expenses) . ", Total: $totalExpenses, Cash Advance: $cashAdvanceFromDB");
            
            echo json_encode([
                'success' => true, 
                'expenses' => $expenses,
                'total_expenses' => $totalExpenses,
                'cash_advance' => $cashAdvanceFromDB,
                'remaining_balance' => $cashAdvanceFromDB - $totalExpenses
            ]);
            break;

        case 'get_expenses_by_driver':
            $driverId = $data['driver_id'] ?? null;
            $driverName = $data['driver_name'] ?? null;
            
            if (!$driverId && !$driverName) {
                throw new Exception("Driver ID or name required");
            }
            
            $whereClause = $driverId ? "e.driver_id = ?" : "d.name = ?";
            $param = $driverId ? $driverId : $driverName;
            $type = $driverId ? "i" : "s";
            
            // Check if created_at column exists
            $columnsQuery = $conn->query("SHOW COLUMNS FROM expenses LIKE 'created_at'");
            $hasCreatedAt = $columnsQuery && $columnsQuery->num_rows > 0;
            
            if ($hasCreatedAt) {
                $sql = "
                    SELECT e.*, d.name as driver_name, t.plate_no, a.destination,
                           DATE_FORMAT(e.created_at, '%m/%d/%y') as formatted_date,
                           a.cash_adv
                    FROM expenses e
                    LEFT JOIN drivers_table d ON e.driver_id = d.driver_id
                    LEFT JOIN truck_table t ON e.truck_id = t.truck_id
                    LEFT JOIN assign a ON e.trip_id = a.trip_id
                    WHERE $whereClause
                    ORDER BY e.created_at DESC
                ";
            } else {
                $sql = "
                    SELECT e.*, d.name as driver_name, t.plate_no, a.destination,
                           DATE_FORMAT(NOW(), '%m/%d/%y') as formatted_date,
                           a.cash_adv
                    FROM expenses e
                    LEFT JOIN drivers_table d ON e.driver_id = d.driver_id
                    LEFT JOIN truck_table t ON e.truck_id = t.truck_id
                    LEFT JOIN assign a ON e.trip_id = a.trip_id
                    WHERE $whereClause
                    ORDER BY e.expense_id DESC
                ";
            }
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare driver expenses query: " . $conn->error);
            }
            
            $stmt->bind_param($type, $param);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute driver expenses query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $expenses = [];
            $totalExpenses = 0;
            $cashAdvanceFromDB = 0;
            
            while ($row = $result->fetch_assoc()) {
                $expenses[] = $row;
                $totalExpenses += floatval($row['amount']);
                // Get cash advance from the first row (all should be same for a driver)
                if ($row['cash_adv'] && $cashAdvanceFromDB == 0) {
                    $cashAdvanceFromDB = floatval($row['cash_adv']);
                }
            }
            
            $stmt->close();
            
            // Calculate remaining balance
            $remainingBalance = $cashAdvanceFromDB - $totalExpenses;
            
            error_log("Driver expenses query result - Expenses: " . count($expenses) . ", Total: $totalExpenses, Cash Advance: $cashAdvanceFromDB, Remaining: $remainingBalance");
            
            echo json_encode([
                'success' => true, 
                'expenses' => $expenses,
                'total_expenses' => $totalExpenses,
                'cash_advance' => $cashAdvanceFromDB,
                'remaining_balance' => $remainingBalance
            ]);
            break;

        case 'update_expense':
            $expenseId = $data['expense_id'] ?? null;
            $expenseType = $data['expense_type'] ?? null;
            $amount = $data['amount'] ?? null;
            
            if (!$expenseId || !$expenseType || !$amount) {
                throw new Exception("Missing required fields");
            }

            if ($amount <= 0) {
                throw new Exception("Amount must be greater than 0");
            }

            $columnsQuery = $conn->query("SHOW COLUMNS FROM expenses LIKE 'last_modified_at'");
            $hasLastModifiedAt = $columnsQuery && $columnsQuery->num_rows > 0;
            
            if ($hasLastModifiedAt) {
                $stmt = $conn->prepare("UPDATE expenses SET 
                    expense_type = ?, 
                    amount = ?, 
                    last_modified_by = ?, 
                    last_modified_at = NOW()
                    WHERE expense_id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE expenses SET 
                    expense_type = ?, 
                    amount = ?, 
                    last_modified_by = ?
                    WHERE expense_id = ?");
            }
                
            if ($stmt === false) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }
            
            $stmt->bind_param("sdsi", $expenseType, $amount, $currentUser, $expenseId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute update: " . $stmt->error);
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
            break;

        case 'delete_expense':
            $expenseId = $data['expense_id'] ?? null;
            
            if (!$expenseId) {
                throw new Exception("Expense ID required");
            }
            
            $stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ?");
            if ($stmt === false) {
                throw new Exception("Failed to prepare delete statement: " . $conn->error);
            }
            
            $stmt->bind_param("i", $expenseId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute delete: " . $stmt->error);
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
            break;

        case 'get_expense_summary':
            $driverId = $data['driver_id'] ?? null;
            $tripId = $data['trip_id'] ?? null;
            
            if (!$driverId && !$tripId) {
                throw new Exception("Driver ID or Trip ID required");
            }
            
            $whereClause = "";
            $params = [];
            $types = "";
            
            if ($tripId) {
                $whereClause = "WHERE e.trip_id = ?";
                $params = [$tripId];
                $types = "i";
            } elseif ($driverId) {
                $whereClause = "WHERE e.driver_id = ?";
                $params = [$driverId];
                $types = "i";
            }
            
            $sql = "
                SELECT 
                    COUNT(*) as total_count,
                    SUM(e.amount) as total_amount,
                    AVG(e.amount) as avg_amount,
                    MAX(e.amount) as max_amount,
                    MIN(e.amount) as min_amount,
                    e.expense_type,
                    COUNT(e.expense_type) as type_count,
                    SUM(e.amount) as type_total
                FROM expenses e
                $whereClause
                GROUP BY e.expense_type
                ORDER BY type_total DESC
            ";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare summary query: " . $conn->error);
            }
            
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute summary query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $summary = [];
            $grandTotal = 0;
            
            while ($row = $result->fetch_assoc()) {
                $summary[] = $row;
                $grandTotal += $row['type_total'];
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'summary' => $summary,
                'grand_total' => $grandTotal
            ]);
            break;

        default:
            throw new Exception("Invalid action: $action");
    }
} catch (Exception $e) {
    error_log("Expense handler error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log("Fatal error in expense handler: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>