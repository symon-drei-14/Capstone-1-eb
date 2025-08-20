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
                COALESCE(a.cash_adv, 0) as total_budget,
                COALESCE(expense_totals.total_expenses, 0) as total_expenses,
                (COALESCE(a.cash_adv, 0) - COALESCE(expense_totals.total_expenses, 0)) as balance
            FROM assign a
            LEFT JOIN drivers_table d ON a.driver_id = d.driver_id
            LEFT JOIN (
                SELECT 
                    trip_id,
                    SUM(amount) as total_expenses
                FROM expenses 
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
            $truckId = $data['truck_id'] ?? null;
            $expenseType = $data['expense_type'] ?? null;
            $amount = $data['amount'] ?? null;

            error_log("ADD EXPENSE - Received data: " . json_encode($data));
            error_log("ADD EXPENSE - Trip ID: $tripId (type: " . gettype($tripId) . ")");
            error_log("ADD EXPENSE - Driver ID: $driverId (type: " . gettype($driverId) . ")");
            error_log("ADD EXPENSE - Truck ID: $truckId (type: " . gettype($truckId) . ")");
            
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

            if (!$truckId || intval($truckId) <= 0) {
                error_log("Looking up truck_id for trip_id: $tripId");
                
                $getTruckStmt = $conn->prepare("
                    SELECT DISTINCT t.truck_id, t.plate_no
                    FROM assign a
                    JOIN truck_table t ON a.plate_no = t.plate_no
                    WHERE a.trip_id = ?
                    LIMIT 1
                ");
                if ($getTruckStmt === false) {
                    throw new Exception("Failed to prepare truck query: " . $conn->error);
                }
                $getTruckStmt->bind_param("i", $tripId);
                $getTruckStmt->execute();
                $truckResult = $getTruckStmt->get_result();
                if ($truckResult->num_rows > 0) {
                    $truckData = $truckResult->fetch_assoc();
                    $truckId = intval($truckData['truck_id']);
                    error_log("Found truck_id: $truckId (plate: {$truckData['plate_no']}) for trip_id: $tripId");
                } else {
                    error_log("No truck found in assign table for trip_id: $tripId");

                    $altTruckStmt = $conn->prepare("
                        SELECT truck_id FROM trips WHERE trip_id = ?
                    ");
                    if ($altTruckStmt) {
                        $altTruckStmt->bind_param("i", $tripId);
                        $altTruckStmt->execute();
                        $altResult = $altTruckStmt->get_result();
                        if ($altResult->num_rows > 0) {
                            $altData = $altResult->fetch_assoc();
                            $truckId = intval($altData['truck_id']);
                            error_log("Found truck_id: $truckId from trips table for trip_id: $tripId");
                        } else {
                            error_log("No truck found in trips table either for trip_id: $tripId");
                            $truckId = null;
                        }
                        $altTruckStmt->close();
                    } else {
                        $truckId = null;
                    }
                }
                $getTruckStmt->close();
            } else {
                $truckId = intval($truckId);
                error_log("Using provided truck_id: $truckId");
            }
           
            error_log("Final values - Trip ID: $tripId, Driver ID: $driverId, Truck ID: " . ($truckId ?: 'NULL') . ", Type: $expenseType, Amount: $amount");

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
            
            // Update the trip summary table
            updateTripSummary($conn, $tripId);
           
            error_log("✅ Expense added successfully. ID: $newExpenseId, Trip: $tripId, Driver: $driverId, Truck: " . ($truckId ?: 'NULL'));
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

            // Get data from physical table
            $summaryStmt = $conn->prepare("
                SELECT total_budget, total_expenses, balance
                FROM trip_summary_expenses
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
                $remainingBalance = $isEnRoute ? floatval($summary['balance']) : null;
               
                error_log("From table - Budget: $totalBudget, Expenses: $totalExpenses, Balance: " . ($remainingBalance !== null ? $remainingBalance : 'N/A (not en route)'));
            } else {
                error_log("No data found in trip_summary_expenses for trip_id: $tripId - updating summary");
                updateTripSummary($conn, $tripId);
                
                // Try again after update
                $summaryStmt = $conn->prepare("
                    SELECT total_budget, total_expenses, balance
                    FROM trip_summary_expenses
                    WHERE trip_id = ?
                ");
                $summaryStmt->bind_param("i", $tripId);
                $summaryStmt->execute();
                $summaryResult = $summaryStmt->get_result();
                $summary = $summaryResult->fetch_assoc();
                $summaryStmt->close();
                
                if ($summary) {
                    $totalBudget = floatval($summary['total_budget']);
                    $totalExpenses = floatval($summary['total_expenses']);
                    $remainingBalance = $isEnRoute ? floatval($summary['balance']) : null;
                } else {
                    $totalBudget = 0;
                    $totalExpenses = 0;
                    $remainingBalance = null;
                }
            }

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

            // Get from physical table
            $stmt = $conn->prepare("
                SELECT trip_id, driver, total_budget, total_expenses, balance
                FROM trip_summary_expenses
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
                updateTripSummary($conn, $tripId);
                
                // Try again after update
                $stmt = $conn->prepare("
                    SELECT trip_id, driver, total_budget, total_expenses, balance
                    FROM trip_summary_expenses
                    WHERE trip_id = ?
                ");
                $stmt->bind_param("i", $tripId);
                $stmt->execute();
                $result = $stmt->get_result();
                $summary = $result->fetch_assoc();
                $stmt->close();
                
                if (!$summary) {
                    throw new Exception("Trip summary not found for trip_id: $tripId");
                }
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
            // Get from physical table
            $stmt = $conn->prepare("
                SELECT tes.trip_id, tes.driver, tes.total_budget, tes.total_expenses, tes.balance,
                       a.status
                FROM trip_summary_expenses tes
                LEFT JOIN assign a ON tes.trip_id = a.trip_id
                ORDER BY tes.trip_id DESC
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

        case 'delete_expense':
            // Add this case if you need to delete expenses
            $expenseId = $data['expense_id'] ?? null;
            
            if (!$expenseId) {
                throw new Exception("Expense ID required");
            }
            
            // Get trip_id before deleting
            $getTripStmt = $conn->prepare("SELECT trip_id FROM expenses WHERE expense_id = ?");
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
            $deleteStmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ?");
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