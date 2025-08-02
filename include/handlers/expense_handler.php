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
           
            // Get truck_id from assign table using trip_id
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
                    $truckData = $truckResult->fetch_assoc();
                    $truckId = $truckData['truck_id'];
                    error_log("Found truck_id: $truckId for trip_id: $tripId");
                } else {
                    error_log("No truck found for trip_id: $tripId, setting truck_id to NULL");
                    $truckId = null;
                }
                $getTruckStmt->close();
            }
           
            // Insert expense
            $stmt = $conn->prepare("INSERT INTO expenses
                (trip_id, driver_id, truck_id, expense_type, amount, created_by)
                VALUES (?, ?, ?, ?, ?, ?)");
           
            if ($stmt === false) {
                throw new Exception("Failed to prepare insert statement: " . $conn->error);
            }
           
            $stmt->bind_param("iiisds", $tripId, $driverId, $truckId, $expenseType, $amount, $currentUser);
           
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute insert: " . $stmt->error);
            }
           
            $newExpenseId = $conn->insert_id;
            $stmt->close();
           
            error_log("Expense added successfully. ID: $newExpenseId, Trip: $tripId, Truck: $truckId");
            echo json_encode(['success' => true, 'expense_id' => $newExpenseId]);
            break;


        case 'get_expenses_by_trip':
            $tripId = $data['trip_id'] ?? null;
           
            if (!$tripId) {
                throw new Exception("Trip ID required");
            }
           
            error_log("Fetching expenses for trip_id: $tripId");
           
            // Get trip summary directly from your view
            $summaryStmt = $conn->prepare("
                SELECT total_budget, total_expenses, balance
                FROM trip_expense_summary
                WHERE trip_id = ?
            ");
            if ($summaryStmt === false) {
                throw new Exception("Failed to prepare summary query: " . $conn->error);
            }
           
            $summaryStmt->bind_param("i", $tripId);
            if (!$summaryStmt->execute()) {
                throw new Exception("Failed to execute summary query: " . $summaryStmt->error);
            }
           
            $summaryResult = $summaryStmt->get_result();
            $summary = $summaryResult->fetch_assoc();
            $summaryStmt->close();
           
            if ($summary) {
                $totalBudget = floatval($summary['total_budget']);
                $totalExpenses = floatval($summary['total_expenses']);
                $remainingBalance = floatval($summary['balance']);
               
                error_log("From view - Budget: $totalBudget, Expenses: $totalExpenses, Balance: $remainingBalance");
            } else {
                error_log("No data found in trip_expense_summary for trip_id: $tripId");
                $totalBudget = 0;
                $totalExpenses = 0;
                $remainingBalance = 0;
            }
           
            // Get detailed expenses list
            $sql = "
                SELECT e.*, d.name as driver_name, t.plate_no, a.destination,
                       DATE_FORMAT(NOW(), '%m/%d/%y') as formatted_date
                FROM expenses e
                LEFT JOIN drivers_table d ON e.driver_id = d.driver_id
                LEFT JOIN truck_table t ON e.truck_id = t.truck_id
                LEFT JOIN assign a ON e.trip_id = a.trip_id
                WHERE e.trip_id = ?
                ORDER BY e.expense_id DESC
            ";
           
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
            while ($row = $result->fetch_assoc()) {
                $expenses[] = $row;
            }
           
            $stmt->close();
           
            error_log("Final result - Trip ID: $tripId, Expenses: " . count($expenses) . ", Total Budget: $totalBudget, Total Expenses: $totalExpenses, Balance: $remainingBalance");
           
            echo json_encode([
                'success' => true,
                'expenses' => $expenses,
                'total_budget' => $totalBudget,
                'total_expenses' => $totalExpenses,
                'remaining_balance' => $remainingBalance
            ]);
            break;


        case 'get_trip_summary':
            $tripId = $data['trip_id'] ?? null;
           
            if (!$tripId) {
                throw new Exception("Trip ID required");
            }
           
            // Get summary directly from your view
            $stmt = $conn->prepare("
                SELECT trip_id, driver, total_budget, total_expenses, balance
                FROM trip_expense_summary
                WHERE trip_id = ?
            ");
            if ($stmt === false) {
                throw new Exception("Failed to prepare summary query: " . $conn->error);
            }
           
            $stmt->bind_param("i", $tripId);
           
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute summary query: " . $stmt->error);
            }
           
            $result = $stmt->get_result();
            $summary = $result->fetch_assoc();
            $stmt->close();
           
            if (!$summary) {
                throw new Exception("Trip summary not found for trip_id: $tripId");
            }
           
            echo json_encode([
                'success' => true,
                'summary' => [
                    'trip_id' => $summary['trip_id'],
                    'driver' => $summary['driver'],
                    'total_budget' => floatval($summary['total_budget']),
                    'total_expenses' => floatval($summary['total_expenses']),
                    'remaining_balance' => floatval($summary['balance'])
                ]
            ]);
            break;


        case 'get_all_trips_summary':
            // Get all trip summaries from your view
            $stmt = $conn->prepare("
                SELECT trip_id, driver, total_budget, total_expenses, balance
                FROM trip_expense_summary
                ORDER BY trip_id DESC
            ");
            if ($stmt === false) {
                throw new Exception("Failed to prepare all summaries query: " . $conn->error);
            }
           
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute all summaries query: " . $stmt->error);
            }
           
            $result = $stmt->get_result();
           
            $summaries = [];
            while ($row = $result->fetch_assoc()) {
                $summaries[] = [
                    'trip_id' => $row['trip_id'],
                    'driver' => $row['driver'],
                    'total_budget' => floatval($row['total_budget']),
                    'total_expenses' => floatval($row['total_expenses']),
                    'remaining_balance' => floatval($row['balance'])
                ];
            }
           
            $stmt->close();
           
            echo json_encode([
                'success' => true,
                'summaries' => $summaries
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