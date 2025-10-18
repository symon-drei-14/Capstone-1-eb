<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
date_default_timezone_set('Asia/Manila');
require 'dbhandler.php';

function safePrepare($conn, $sql, $context = '') {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("[$context] SQL prepare failed: " . $conn->error . " | Query: $sql");
    }
    return $stmt;
}

function updateTripSummary($conn, $tripId) {
    try {
        $stmt = safePrepare($conn, "
            SELECT 
                t.trip_id,
                d.name as driver,
                (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_budget,
                COALESCE(expense_totals.total_expenses, 0) as total_expenses,
                ((COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) - COALESCE(expense_totals.total_expenses, 0)) as balance
            FROM trips t
            LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
            LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
            LEFT JOIN (
                SELECT trip_id, SUM(amount) as total_expenses
                FROM driver_expenses 
                GROUP BY trip_id
            ) expense_totals ON t.trip_id = expense_totals.trip_id
            WHERE t.trip_id = ?
        ", "updateTripSummary");

        $stmt->bind_param("i", $tripId);
        if (!$stmt->execute()) {
            throw new Exception("[updateTripSummary] Execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        if ($data) {
            $updateStmt = safePrepare($conn, "
                INSERT INTO trip_summary_expenses 
                (trip_id, driver, total_budget, total_expenses, balance)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    driver = VALUES(driver),
                    total_budget = VALUES(total_budget),
                    total_expenses = VALUES(total_expenses),
                    balance = VALUES(balance)
            ", "updateTripSummary - insert");

            $updateStmt->bind_param(
                "isddd", 
                $data['trip_id'],
                $data['driver'],
                $data['total_budget'],
                $data['total_expenses'],
                $data['balance']
            );
            
            if (!$updateStmt->execute()) {
                throw new Exception("[updateTripSummary] Insert/Update failed: " . $updateStmt->error);
            }
            $updateStmt->close();
            
            error_log("✅ Updated trip summary for trip_id: $tripId");
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("❌ Error updating trip summary for trip $tripId: " . $e->getMessage());
        return false;
    }
}

function ensureExpenseTableStructure($conn) {
    try {
        $result = $conn->query("SHOW COLUMNS FROM driver_expenses LIKE 'receipt_image'");
        if ($result->num_rows == 0) {
            $alterSql = "ALTER TABLE driver_expenses ADD COLUMN receipt_image VARCHAR(500) NULL AFTER amount";
            if (!$conn->query($alterSql)) {
                error_log("Warning: Could not add receipt_image column: " . $conn->error);
            } else {
                error_log("✅ Added receipt_image column to driver_expenses table");
            }
        }
    } catch (Exception $e) {
        error_log("❌ Error checking table structure: " . $e->getMessage());
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

ensureExpenseTableStructure($conn);

$currentUser = $_SESSION['username'] ?? 'System';
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? '';

error_log("Expense Handler - Action: $action, Data: " . json_encode($data));

try {
    switch ($action) {
        case 'add_expense':
            $tripId = intval($data['trip_id'] ?? 0);
            $driverId = intval($data['driver_id'] ?? 0);
            $expenseType = $data['expense_type'] ?? null;
            $amount = floatval($data['amount'] ?? 0);
            $receiptImage = $data['receipt_image'] ?? null;

            if ($tripId <= 0 || $driverId <= 0 || !$expenseType || $amount <= 0) {
                throw new Exception("Missing or invalid required fields.");
            }

            $tableCheck = $conn->query("SHOW TABLES LIKE 'expense_types'");
            if ($tableCheck->num_rows == 0) {
                $create = "
                    CREATE TABLE IF NOT EXISTS expense_types (
                        type_id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL UNIQUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ";
                if (!$conn->query($create)) {
                    throw new Exception("Failed to create expense_types: " . $conn->error);
                }
                $defaultTypes = ['Gas', 'Toll Gate', 'Maintenance', 'Food', 'Parking', 'Other'];
                $insertDefault = safePrepare($conn, "INSERT IGNORE INTO expense_types (name) VALUES (?)", "add_expense - insert defaults");
                foreach ($defaultTypes as $t) {
                    $insertDefault->bind_param("s", $t);
                    $insertDefault->execute();
                }
                $insertDefault->close();
            }

            $typeStmt = safePrepare($conn, "SELECT type_id FROM expense_types WHERE name = ?", "add_expense - select type");
            $typeStmt->bind_param("s", $expenseType);
            $typeStmt->execute();
            $typeResult = $typeStmt->get_result();
            if ($typeResult && $typeResult->num_rows > 0) {
                $expenseTypeId = $typeResult->fetch_assoc()['type_id'];
            } else {
                $createType = safePrepare($conn, "INSERT INTO expense_types (name) VALUES (?)", "add_expense - create type");
                $createType->bind_param("s", $expenseType);
                if (!$createType->execute()) {
                    throw new Exception("Failed creating expense type: " . $createType->error);
                }
                $expenseTypeId = $conn->insert_id;
                $createType->close();
            }
            $typeStmt->close();

            $expenseTableCheck = $conn->query("SHOW TABLES LIKE 'driver_expenses'");
            if ($expenseTableCheck->num_rows == 0) {
                $createExpenseTable = "
                    CREATE TABLE IF NOT EXISTS driver_expenses (
                        expense_id INT AUTO_INCREMENT PRIMARY KEY,
                        trip_id INT NOT NULL,
                        driver_id INT NOT NULL,
                        expense_type_id INT NOT NULL,
                        amount DECIMAL(10,2) NOT NULL,
                        receipt_image VARCHAR(500) NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        created_by VARCHAR(255) DEFAULT 'System',
                        FOREIGN KEY (expense_type_id) REFERENCES expense_types(type_id)
                    )
                ";
                if (!$conn->query($createExpenseTable)) {
                    throw new Exception("Failed to create driver_expenses: " . $conn->error);
                }
            }

            $currentTime = date('Y-m-d H:i:s');
            $insert = safePrepare($conn, "
                INSERT INTO driver_expenses (trip_id, driver_id, expense_type_id, amount, receipt_image, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", "add_expense - insert");
            $insert->bind_param("iiidsss", $tripId, $driverId, $expenseTypeId, $amount, $receiptImage, $currentUser, $currentTime);
            if (!$insert->execute()) {
                throw new Exception("Insert expense failed: " . $insert->error);
            }
            $newExpenseId = $conn->insert_id;
            $insert->close();

            updateTripSummary($conn, $tripId);

            echo json_encode(['success' => true, 'expense_id' => $newExpenseId]);
            break;

        case 'get_expenses_by_trip':
            $tripId = intval($data['trip_id'] ?? 0);
            if ($tripId <= 0) throw new Exception("Trip ID required");

            $statusStmt = safePrepare($conn, "SELECT status FROM trips WHERE trip_id = ? LIMIT 1", "get_expenses_by_trip - status");
            $statusStmt->bind_param("i", $tripId);
            $statusStmt->execute();
            $status = $statusStmt->get_result()->fetch_assoc();
            $statusStmt->close();

            $isEnRoute = $status && strtolower($status['status']) === 'en route';

            $summaryStmt = safePrepare($conn, "
                SELECT (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_budget,
                       COALESCE(SUM(de.amount), 0) as total_expenses
                FROM trip_expenses te
                LEFT JOIN driver_expenses de ON te.trip_id = de.trip_id
                WHERE te.trip_id = ?
                GROUP BY te.trip_id, te.cash_advance, te.additional_cash_advance
            ", "get_expenses_by_trip - summary");
            $summaryStmt->bind_param("i", $tripId);
            $summaryStmt->execute();
            $summary = $summaryStmt->get_result()->fetch_assoc();
            $summaryStmt->close();

            $totalBudget = $summary ? floatval($summary['total_budget']) : 0;
            $totalExpenses = $summary ? floatval($summary['total_expenses']) : 0;
            $remainingBalance = $isEnRoute ? ($totalBudget - $totalExpenses) : null;

            $listStmt = safePrepare($conn, "
                SELECT de.*, et.name as expense_type, d.name as driver_name,
                       DATE_FORMAT(de.created_at, '%m/%d/%y') as formatted_date
                FROM driver_expenses de
                LEFT JOIN expense_types et ON de.expense_type_id = et.type_id
                LEFT JOIN drivers_table d ON de.driver_id = d.driver_id
                WHERE de.trip_id = ?
                ORDER BY de.expense_id DESC
            ", "get_expenses_by_trip - list");
            $listStmt->bind_param("i", $tripId);
            $listStmt->execute();
            $result = $listStmt->get_result();
            $expenses = [];
            while ($row = $result->fetch_assoc()) {
                $expenses[] = $row;
            }
            $listStmt->close();

            echo json_encode([
                'success' => true,
                'expenses' => $expenses,
                'total_budget' => $isEnRoute ? $totalBudget : null,
                'total_expenses' => $totalExpenses,
                'remaining_balance' => $remainingBalance,
                'is_en_route' => $isEnRoute
            ]);
            break;

        case 'get_trip_summary':
            $tripId = intval($data['trip_id'] ?? 0);
            if ($tripId <= 0) throw new Exception("Trip ID required");

            $statusStmt = safePrepare($conn, "SELECT status FROM trips WHERE trip_id = ? LIMIT 1", "get_trip_summary - status");
            $statusStmt->bind_param("i", $tripId);
            $statusStmt->execute();
            $status = $statusStmt->get_result()->fetch_assoc();
            $statusStmt->close();

            $isEnRoute = $status && strtolower($status['status']) === 'en route';

            $stmt = safePrepare($conn, "
                SELECT te.trip_id, d.name as driver,
                       (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_budget,
                       COALESCE(SUM(de.amount), 0) as total_expenses
                FROM trip_expenses te
                LEFT JOIN trips t ON te.trip_id = t.trip_id
                LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
                LEFT JOIN driver_expenses de ON te.trip_id = de.trip_id
                WHERE te.trip_id = ?
                GROUP BY te.trip_id, te.cash_advance, te.additional_cash_advance, d.name
            ", "get_trip_summary - summary");
            $stmt->bind_param("i", $tripId);
            $stmt->execute();
            $summary = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$summary) {
                throw new Exception("Trip summary not found for trip_id: $tripId");
            }

            $totalBudget = floatval($summary['total_budget']);
            $totalExpenses = floatval($summary['total_expenses']);
            $balance = $isEnRoute ? ($totalBudget - $totalExpenses) : null;

            echo json_encode([
                'success' => true,
                'summary' => [
                    'trip_id' => $summary['trip_id'],
                    'driver' => $summary['driver'],
                    'total_budget' => $isEnRoute ? $totalBudget : null,
                    'total_expenses' => $totalExpenses,
                    'remaining_balance' => $balance,
                    'is_en_route' => $isEnRoute
                ]
            ]);
            break;

        case 'get_all_trips_summary':
            $stmt = safePrepare($conn, "
                SELECT te.trip_id, d.name as driver,
                       (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_budget,
                       COALESCE(SUM(de.amount), 0) as total_expenses,
                       t.status
                FROM trip_expenses te
                LEFT JOIN trips t ON te.trip_id = t.trip_id
                LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
                LEFT JOIN driver_expenses de ON te.trip_id = de.trip_id
                GROUP BY te.trip_id, te.cash_advance, te.additional_cash_advance, d.name, t.status
                ORDER BY te.trip_id DESC
            ", "get_all_trips_summary");
            $stmt->execute();
            $result = $stmt->get_result();
            $summaries = [];
            while ($row = $result->fetch_assoc()) {
                $isEnRoute = $row['status'] && strtolower($row['status']) === 'en route';
                $summaries[] = [
                    'trip_id' => $row['trip_id'],
                    'driver' => $row['driver'],
                    'total_budget' => $isEnRoute ? floatval($row['total_budget']) : null,
                    'total_expenses' => floatval($row['total_expenses']),
                    'remaining_balance' => $isEnRoute ? (floatval($row['total_budget']) - floatval($row['total_expenses'])) : null,
                    'is_en_route' => $isEnRoute,
                    'status' => $row['status']
                ];
            }
            $stmt->close();
            echo json_encode(['success' => true, 'summaries' => $summaries]);
            break;

        case 'get_expense_types':
            $stmt = safePrepare($conn, "SELECT type_id, name FROM expense_types WHERE name IN ('Toll', 'Gas', 'Repair', 'Food')", "get_expense_types");
            $stmt->execute();
            $result = $stmt->get_result();
            $types = [];
            while ($row = $result->fetch_assoc()) $types[] = $row;
            $stmt->close();
            echo json_encode(['success' => true, 'expense_types' => $types]);
            break;

            case 'update_expense':
    $expenseId = intval($data['expense_id'] ?? 0);
    $tripId = intval($data['trip_id'] ?? 0);
    $expenseType = trim($data['expense_type'] ?? '');
    $amount = floatval($data['amount'] ?? 0);
    $receiptImage = $data['receipt_image'] ?? null;

    if ($expenseId <= 0 || $tripId <= 0 || empty($expenseType) || $amount <= 0) {
        throw new Exception("Missing required fields for update (ID, Trip ID, Type, Amount).");
    }

    // Find or create the expense type ID
    $typeStmt = safePrepare($conn, "SELECT type_id FROM expense_types WHERE name = ?", "update_expense - select type");
    $typeStmt->bind_param("s", $expenseType);
    $typeStmt->execute();
    $typeResult = $typeStmt->get_result();
    if ($typeResult->num_rows > 0) {
        $expenseTypeId = $typeResult->fetch_assoc()['type_id'];
    } else {
        $createType = safePrepare($conn, "INSERT INTO expense_types (name) VALUES (?)", "update_expense - create type");
        $createType->bind_param("s", $expenseType);
        if (!$createType->execute()) {
            throw new Exception("Failed to create new expense type: " . $createType->error);
        }
        $expenseTypeId = $conn->insert_id;
        $createType->close();
    }
    $typeStmt->close();

    // Before updating, get the old image path to delete the file later if it changes
    $oldImageStmt = safePrepare($conn, "SELECT receipt_image FROM driver_expenses WHERE expense_id = ?", "update_expense - get old image");
    $oldImageStmt->bind_param("i", $expenseId);
    $oldImageStmt->execute();
    $oldImagePath = $oldImageStmt->get_result()->fetch_assoc()['receipt_image'] ?? null;
    $oldImageStmt->close();

    // Update the record in the database
    $updateStmt = safePrepare($conn, "
        UPDATE driver_expenses 
        SET expense_type_id = ?, amount = ?, receipt_image = ?
        WHERE expense_id = ?
    ", "update_expense - update");
    $updateStmt->bind_param("idsi", $expenseTypeId, $amount, $receiptImage, $expenseId);

    if (!$updateStmt->execute()) {
        throw new Exception("Database update failed: " . $updateStmt->error);
    }
    $updateStmt->close();

    // If the image was changed, and an old one existed, delete the old file
    if ($oldImagePath && $oldImagePath !== $receiptImage) {
        $fullOldPath = realpath(__DIR__ . '/../') . '/' . $oldImagePath;
        if (file_exists($fullOldPath)) {
            unlink($fullOldPath);
        }
    }

    // IMPORTANT: Recalculate totals for the trip
    updateTripSummary($conn, $tripId);

    echo json_encode(['success' => true, 'message' => 'Expense updated successfully.']);
    break;

        case 'delete_expense':
            $expenseId = intval($data['expense_id'] ?? 0);
            if ($expenseId <= 0) throw new Exception("Expense ID required");

            $getTrip = safePrepare($conn, "SELECT trip_id, receipt_image FROM driver_expenses WHERE expense_id = ?", "delete_expense - get trip");
            $getTrip->bind_param("i", $expenseId);
            $getTrip->execute();
            $tripData = $getTrip->get_result()->fetch_assoc();
            $getTrip->close();
            if (!$tripData) throw new Exception("Expense not found");
            $tripId = $tripData['trip_id'];
            $receiptImage = $tripData['receipt_image'];

            $delete = safePrepare($conn, "DELETE FROM driver_expenses WHERE expense_id = ?", "delete_expense - delete");
            $delete->bind_param("i", $expenseId);
            if (!$delete->execute()) throw new Exception("Delete failed: " . $delete->error);
            $delete->close();

            if ($receiptImage && file_exists("../" . $receiptImage)) {
                if (!unlink("../" . $receiptImage)) {
                    error_log("Warning: Could not delete receipt image file: " . $receiptImage);
                }
            }

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
    if (isset($conn) && $conn) $conn->close();
}
?>