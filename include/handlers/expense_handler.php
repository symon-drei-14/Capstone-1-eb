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

/**
 * Update summary for a specific trip
 */
function updateTripSummary($conn, $tripId) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                a.trip_id,
                d.name as driver,
                (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_budget,
                COALESCE(expense_totals.total_expenses, 0) as total_expenses,
                ((COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) - COALESCE(expense_totals.total_expenses, 0)) as balance
            FROM assign a
            LEFT JOIN drivers_table d ON a.driver_id = d.driver_id
            LEFT JOIN trip_expenses te ON a.trip_id = te.trip_id
            LEFT JOIN (
                SELECT 
                    trip_id,
                    SUM(amount) as total_expenses
                FROM driver_expenses 
                WHERE trip_id = ?
                GROUP BY trip_id
            ) expense_totals ON a.trip_id = expense_totals.trip_id
            WHERE a.trip_id = ?
        ");
        
        $stmt->bind_param("ii", $tripId, $tripId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        if ($data) {
            $updateStmt = $conn->prepare("
                INSERT INTO trip_summary_expenses 
                (trip_id, driver, total_budget, total_expenses, balance)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    driver = VALUES(driver),
                    total_budget = VALUES(total_budget),
                    total_expenses = VALUES(total_expenses),
                    balance = VALUES(balance)
            ");
            
            $updateStmt->bind_param("isddd", 
                $data['trip_id'],
                $data['driver'],
                $data['total_budget'],
                $data['total_expenses'],
                $data['balance']
            );
            
            $updateStmt->execute();
            $updateStmt->close();
            
            error_log("Updated trip summary for trip_id: $tripId");
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error updating trip summary for trip $tripId: " . $e->getMessage());
        return false;
    }
}

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
            $expenseType = $data['expense_type'] ?? null;
            $amount = $data['amount'] ?? null;

            error_log("ADD EXPENSE - Received data: " . json_encode($data));
            error_log("ADD EXPENSE - Trip ID: $tripId (type: " . gettype($tripId) . ")");
            error_log("ADD EXPENSE - Driver ID: $driverId (type: " . gettype($driverId) . ")");
            
            if (!$tripId || !$driverId || !$expenseType || !$amount) {
                throw new Exception("Missing required fields. Trip ID: $tripId, Driver ID: $driverId, Type: $expenseType, Amount: $amount");
            }

            $tripId = intval($tripId);
            if ($tripId <= 0) {
                throw new Exception("Invalid trip ID: must be a positive integer, received: " . $data['trip_id']);
            }

            $driverId = intval($driverId);
            if ($driverId <= 0) {
                throw new Exception("Invalid driver ID: must be a positive integer, received: " . $data['driver_id']);
            }

            if ($amount <= 0) {
                throw new Exception("Amount must be greater than 0");
            }

            // First, check or create expense type
            $expenseTypeId = null;
            $typeCheckStmt = $conn->prepare("SELECT type_id FROM expense_types WHERE name = ?");
            if ($typeCheckStmt === false) {
                throw new Exception("Failed to prepare type check query: " . $conn->error);
            }
            
            $typeCheckStmt->bind_param("s", $expenseType);
            $typeCheckStmt->execute();
            $typeResult = $typeCheckStmt->get_result();
            
            if ($typeResult->num_rows > 0) {
                $typeData = $typeResult->fetch_assoc();
                $expenseTypeId = $typeData['type_id'];
                error_log("Found existing expense type: $expenseType (ID: $expenseTypeId)");
            } else {
                // Create new expense type
                $typeCheckStmt->close();
                $createTypeStmt = $conn->prepare("INSERT INTO expense_types (name) VALUES (?)");
                if ($createTypeStmt === false) {
                    throw new Exception("Failed to prepare type creation query: " . $conn->error);
                }
                
                $createTypeStmt->bind_param("s", $expenseType);
                if (!$createTypeStmt->execute()) {
                    throw new Exception("Failed to create expense type: " . $createTypeStmt->error);
                }
                
                $expenseTypeId = $conn->insert_id;
                $createTypeStmt->close();
                error_log("Created new expense type: $expenseType (ID: $expenseTypeId)");
            }
            
            if (!$expenseTypeId) {
                $typeCheckStmt->close();
                throw new Exception("Failed to get or create expense type ID");
            }
            $typeCheckStmt->close();

            // Insert into driver_expenses table
            error_log("Final values - Trip ID: $tripId, Driver ID: $driverId, Type ID: $expenseTypeId, Amount: $amount");

            $stmt = $conn->prepare("INSERT INTO driver_expenses
                (trip_id, driver_id, expense_type_id, amount, created_by)
                VALUES (?, ?, ?, ?, ?)");
           
            if ($stmt === false) {
                throw new Exception("Failed to prepare insert statement: " . $conn->error);
            }
           
            $stmt->bind_param("iiids", $tripId, $driverId, $expenseTypeId, $amount, $currentUser);
           
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute insert: " . $stmt->error);
            }
           
            $newExpenseId = $conn->insert_id;
            $stmt->close();
            
            // Update the trip summary table
            updateTripSummary($conn, $tripId);
           
            error_log("✅ Expense added successfully. ID: $newExpenseId, Trip: $tripId, Driver: $driverId, Type: $expenseTypeId");
            echo json_encode(['success' => true, 'expense_id' => $newExpenseId]);
            break;

        case 'get_expenses_by_trip':
            $tripId = $data['trip_id'] ?? null;
           
            if (!$tripId) {
                throw new Exception("Trip ID required");
            }
           
            error_log("Fetching expenses for trip_id: $tripId");

            $statusStmt = $conn->prepare("
                SELECT status FROM assign WHERE trip_id = ? LIMIT 1
            ");
            if ($statusStmt === false) {
                throw new Exception("Failed to prepare status query: " . $conn->error);
            }
            
            $statusStmt->bind_param("i", $tripId);
            if (!$statusStmt->execute()) {
                throw new Exception("Failed to execute status query: " . $statusStmt->error);
            }
            
            $statusResult = $statusStmt->get_result();
            $statusData = $statusResult->fetch_assoc();
            $statusStmt->close();
            
            $isEnRoute = $statusData && strtolower($statusData['status']) === 'en route';
            error_log("Trip status check - Trip ID: $tripId, Status: " . ($statusData['status'] ?? 'not found') . ", Is En Route: " . ($isEnRoute ? 'true' : 'false'));

            // Get budget from trip_expenses and total expenses from driver_expenses
            $summaryStmt = $conn->prepare("
                SELECT 
                    (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_budget,
                    COALESCE(SUM(de.amount), 0) as total_expenses,
                    ((COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) - COALESCE(SUM(de.amount), 0)) as balance
                FROM trip_expenses te
                LEFT JOIN driver_expenses de ON te.trip_id = de.trip_id
                WHERE te.trip_id = ?
                GROUP BY te.trip_id, te.cash_advance, te.additional_cash_advance
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
               
                error_log("From query - Budget: $totalBudget, Expenses: $totalExpenses, Balance: " . ($remainingBalance !== null ? $remainingBalance : 'N/A (not en route)'));
            } else {
                // If no trip_expenses record, check if trip exists and create one
                $checkTripStmt = $conn->prepare("
                    SELECT a.trip_id, 
                           COALESCE(a.cash_adv, 0) as cash_advance,
                           COALESCE(a.additional_cash_advance, 0) as additional_cash_advance
                    FROM assign a 
                    WHERE a.trip_id = ?
                ");
                $checkTripStmt->bind_param("i", $tripId);
                $checkTripStmt->execute();
                $checkResult = $checkTripStmt->get_result();
                $tripData = $checkResult->fetch_assoc();
                $checkTripStmt->close();
                
                if ($tripData) {
                    // Create trip_expenses record
                    $createTripStmt = $conn->prepare("
                        INSERT INTO trip_expenses (trip_id, cash_advance, additional_cash_advance) 
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            cash_advance = VALUES(cash_advance),
                            additional_cash_advance = VALUES(additional_cash_advance)
                    ");
                    $additionalCashAdv = floatval($tripData['additional_cash_advance'] ?? 0);
                    $createTripStmt->bind_param("idd", $tripId, $tripData['cash_advance'], $additionalCashAdv);
                    $createTripStmt->execute();
                    $createTripStmt->close();
                    
                    $totalBudget = floatval($tripData['cash_advance']) + $additionalCashAdv;
                    $totalExpenses = 0;
                    $remainingBalance = $isEnRoute ? $totalBudget : null;
                } else {
                    $totalBudget = 0;
                    $totalExpenses = 0;
                    $remainingBalance = null;
                }
            }

            // Get detailed expenses with expense type names
            $sql = "
                SELECT de.*, et.name as expense_type, d.name as driver_name, a.destination,
                       DATE_FORMAT(de.created_at, '%m/%d/%y') as formatted_date
                FROM driver_expenses de
                LEFT JOIN expense_types et ON de.expense_type_id = et.type_id
                LEFT JOIN drivers_table d ON de.driver_id = d.driver_id
                LEFT JOIN assign a ON de.trip_id = a.trip_id
                WHERE de.trip_id = ?
                ORDER BY de.expense_id DESC
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
           
            error_log("Final result - Trip ID: $tripId, Expenses: " . count($expenses) . ", Total Budget: $totalBudget, Total Expenses: $totalExpenses, Balance: " . ($remainingBalance !== null ? $remainingBalance : 'N/A'));
           
            echo json_encode([
                'success' => true,
                'expenses' => $expenses,
                'total_budget' => $totalBudget,
                'total_expenses' => $totalExpenses,
                'remaining_balance' => $remainingBalance,
                'is_en_route' => $isEnRoute
            ]);
            break;

        case 'get_trip_summary':
            $tripId = $data['trip_id'] ?? null;
           
            if (!$tripId) {
                throw new Exception("Trip ID required");
            }

            $statusStmt = $conn->prepare("
                SELECT status FROM assign WHERE trip_id = ? LIMIT 1
            ");
            if ($statusStmt === false) {
                throw new Exception("Failed to prepare status query: " . $conn->error);
            }
            
            $statusStmt->bind_param("i", $tripId);
            if (!$statusStmt->execute()) {
                throw new Exception("Failed to execute status query: " . $statusStmt->error);
            }
            
            $statusResult = $statusStmt->get_result();
            $statusData = $statusResult->fetch_assoc();
            $statusStmt->close();
            
            $isEnRoute = $statusData && strtolower($statusData['status']) === 'en route';

            // Get summary from new table structure
            $stmt = $conn->prepare("
                SELECT 
                    te.trip_id,
                    d.name as driver,
                    (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_budget,
                    COALESCE(SUM(de.amount), 0) as total_expenses,
                    ((COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) - COALESCE(SUM(de.amount), 0)) as balance
                FROM trip_expenses te
                LEFT JOIN assign a ON te.trip_id = a.trip_id
                LEFT JOIN drivers_table d ON a.driver_id = d.driver_id
                LEFT JOIN driver_expenses de ON te.trip_id = de.trip_id
                WHERE te.trip_id = ?
                GROUP BY te.trip_id, te.cash_advance, te.additional_cash_advance, d.name
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
                    'remaining_balance' => $isEnRoute ? floatval($summary['balance']) : null,
                    'is_en_route' => $isEnRoute
                ]
            ]);
            break;

        case 'get_all_trips_summary':
            // Get from new table structure
            $stmt = $conn->prepare("
                SELECT 
                    te.trip_id,
                    d.name as driver,
                    (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_budget,
                    COALESCE(SUM(de.amount), 0) as total_expenses,
                    ((COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) - COALESCE(SUM(de.amount), 0)) as balance,
                    a.status
                FROM trip_expenses te
                LEFT JOIN assign a ON te.trip_id = a.trip_id
                LEFT JOIN drivers_table d ON a.driver_id = d.driver_id
                LEFT JOIN driver_expenses de ON te.trip_id = de.trip_id
                GROUP BY te.trip_id, te.cash_advance, te.additional_cash_advance, d.name, a.status
                ORDER BY te.trip_id DESC
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
                $isEnRoute = $row['status'] && strtolower($row['status']) === 'en route';
                
                $summaries[] = [
                    'trip_id' => $row['trip_id'],
                    'driver' => $row['driver'],
                    'total_budget' => floatval($row['total_budget']),
                    'total_expenses' => floatval($row['total_expenses']),
                    'remaining_balance' => $isEnRoute ? floatval($row['balance']) : null,
                    'is_en_route' => $isEnRoute,
                    'status' => $row['status']
                ];
            }
           
            $stmt->close();
           
            echo json_encode([
                'success' => true,
                'summaries' => $summaries
            ]);
            break;

        case 'get_expense_types':
            $stmt = $conn->prepare("SELECT type_id, name FROM expense_types ORDER BY name");
            if ($stmt === false) {
                throw new Exception("Failed to prepare expense types query: " . $conn->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute expense types query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $expenseTypes = [];
            
            while ($row = $result->fetch_assoc()) {
                $expenseTypes[] = $row;
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'expense_types' => $expenseTypes
            ]);
            break;

        case 'delete_expense':
            $expenseId = $data['expense_id'] ?? null;
            
            if (!$expenseId) {
                throw new Exception("Expense ID required");
            }
            
            // Get trip_id before deleting
            $getTripStmt = $conn->prepare("SELECT trip_id FROM driver_expenses WHERE expense_id = ?");
            $getTripStmt->bind_param("i", $expenseId);
            $getTripStmt->execute();
            $result = $getTripStmt->get_result();
            $expenseData = $result->fetch_assoc();
            $getTripStmt->close();
            
            if (!$expenseData) {
                throw new Exception("Expense not found");
            }
            
            $tripId = $expenseData['trip_id'];
            
            // Delete the expense
            $deleteStmt = $conn->prepare("DELETE FROM driver_expenses WHERE expense_id = ?");
            $deleteStmt->bind_param("i", $expenseId);
            
            if (!$deleteStmt->execute()) {
                throw new Exception("Failed to delete expense: " . $deleteStmt->error);
            }
            
            $deleteStmt->close();
            
            // Update the trip summary
            updateTripSummary($conn, $tripId);
            
            echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
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