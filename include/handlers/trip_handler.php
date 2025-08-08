<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require 'dbhandler.php';

// Check database connection first
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

try {
    switch ($action) {
        case 'add':
            $driverId = $data['driver_id'] ?? null;
            $driverName = $data['driver'] ?? null;
            
            if (empty($driverId) && !empty($driverName)) {
                $getDriverId = $conn->prepare("SELECT driver_id FROM drivers_table WHERE name = ? LIMIT 1");
                if ($getDriverId === false) {
                    throw new Exception("Failed to prepare driver query: " . $conn->error);
                }
                $getDriverId->bind_param("s", $driverName);
                $getDriverId->execute();
                $driverResult = $getDriverId->get_result();
                if ($driverResult->num_rows > 0) {
                    $driverId = $driverResult->fetch_assoc()['driver_id'];
                }
                $getDriverId->close();
            }
            
            // Check if created_at column exists
            $columnsQuery = $conn->query("SHOW COLUMNS FROM assign LIKE 'created_at'");
            $hasCreatedAt = $columnsQuery && $columnsQuery->num_rows > 0;
            
            if ($hasCreatedAt) {
                $stmt = $conn->prepare("INSERT INTO assign 
                    (plate_no, date, driver, driver_id, helper, dispatcher, container_no, client, 
                    destination, shippine_line, consignee, size, cash_adv, status,
                    last_modified_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                if ($stmt === false) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }
                
                $stmt->bind_param("sssssssssssssss",
                    $data['plateNo'],
                    $data['date'],
                    $driverName,
                    $driverId, 
                    $data['helper'],
                    $data['dispatcher'],
                    $data['containerNo'],
                    $data['client'],
                    $data['destination'],
                    $data['shippingLine'],
                    $data['consignee'],
                    $data['size'],
                    $data['cashAdvance'],
                    $data['status'],
                    $currentUser  
                );
            } else {
                $stmt = $conn->prepare("INSERT INTO assign 
                    (plate_no, date, driver, driver_id, helper, dispatcher, container_no, client, 
                    destination, shippine_line, consignee, size, cash_adv, status,
                    last_modified_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt === false) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }
                
                $stmt->bind_param("sssssssssssssss",
                    $data['plateNo'],
                    $data['date'],
                    $driverName,
                    $driverId, 
                    $data['helper'],
                    $data['dispatcher'],
                    $data['containerNo'],
                    $data['client'],
                    $data['destination'],
                    $data['shippingLine'],
                    $data['consignee'],
                    $data['size'],
                    $data['cashAdvance'],
                    $data['status'],
                    $currentUser  
                );
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute insert: " . $stmt->error);
            }
            
            $newTripId = $conn->insert_id;

            if ($data['status'] === 'En Route') {
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Enroute' WHERE plate_no = ?");
                if ($updateTruck === false) {
                    throw new Exception("Failed to prepare truck update: " . $conn->error);
                }
                $updateTruck->bind_param("s", $data['plateNo']);
                $updateTruck->execute();
                $updateTruck->close();
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'trip_id' => $newTripId]);
            break;

        case 'edit':
            $getCurrent = $conn->prepare("SELECT status, plate_no FROM assign WHERE trip_id = ?");
            if ($getCurrent === false) {
                throw new Exception("Failed to prepare current select: " . $conn->error);
            }
            $getCurrent->bind_param("i", $data['id']);
            $getCurrent->execute();
            $current = $getCurrent->get_result()->fetch_assoc();
            $getCurrent->close();

            $driverId = $data['driver_id'] ?? null;
            $driverName = $data['driver'] ?? null;
            
            if (empty($driverId) && !empty($driverName)) {
                $getDriverId = $conn->prepare("SELECT driver_id FROM drivers_table WHERE name = ? LIMIT 1");
                if ($getDriverId === false) {
                    throw new Exception("Failed to prepare driver query: " . $conn->error);
                }
                $getDriverId->bind_param("s", $driverName);
                $getDriverId->execute();
                $driverResult = $getDriverId->get_result();
                if ($driverResult->num_rows > 0) {
                    $driverId = $driverResult->fetch_assoc()['driver_id'];
                }
                $getDriverId->close();
            }
            
            $editReasons = isset($data['editReasons']) ? json_encode($data['editReasons']) : null;
            
            // Check if last_modified_at column exists
            $columnsQuery = $conn->query("SHOW COLUMNS FROM assign LIKE 'last_modified_at'");
            $hasLastModifiedAt = $columnsQuery && $columnsQuery->num_rows > 0;
            
            if ($hasLastModifiedAt) {
                $stmt = $conn->prepare("UPDATE assign SET 
                    plate_no=?, date=?, driver=?, driver_id=?, helper=?, dispatcher=?, container_no=?, client=?, 
                    destination=?, shippine_line=?, consignee=?, size=?, cash_adv=?, status=?,
                    edit_reasons=?, last_modified_by=?, last_modified_at=NOW()
                    WHERE trip_id=?");
            } else {
                $stmt = $conn->prepare("UPDATE assign SET 
                    plate_no=?, date=?, driver=?, driver_id=?, helper=?, dispatcher=?, container_no=?, client=?, 
                    destination=?, shippine_line=?, consignee=?, size=?, cash_adv=?, status=?,
                    edit_reasons=?, last_modified_by=?
                    WHERE trip_id=?");
            }
                
            if ($stmt === false) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }
            
            $stmt->bind_param("ssssssssssssssssi",
                $data['plateNo'],
                $data['date'],
                $driverName,
                $driverId,
                $data['helper'],
                $data['dispatcher'],
                $data['containerNo'],
                $data['client'],
                $data['destination'],
                $data['shippingLine'],
                $data['consignee'],
                $data['size'],
                $data['cashAdvance'],
                $data['status'],
                $editReasons,
                $currentUser,  
                $data['id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute update: " . $stmt->error);
            }
            
            $stmt->close();
            
            if ($current && $current['status'] !== $data['status']) {
                $newTruckStatus = 'In Terminal'; 
                
                if ($data['status'] === 'En Route') {
                    $newTruckStatus = 'Enroute';
                } elseif ($data['status'] === 'Pending') {
                    $newTruckStatus = 'Pending';
                }
                
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE plate_no = ?");
                if ($updateTruck === false) {
                    throw new Exception("Failed to prepare truck update: " . $conn->error);
                }
                $updateTruck->bind_param("ss", $newTruckStatus, $data['plateNo']);
                $updateTruck->execute();
                $updateTruck->close();
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $getPlate = $conn->prepare("SELECT plate_no, status FROM assign WHERE trip_id = ?");
            if ($getPlate === false) {
                throw new Exception("Failed to prepare plate select: " . $conn->error);
            }
            $getPlate->bind_param("i", $data['id']);
            $getPlate->execute();
            $trip = $getPlate->get_result()->fetch_assoc();
            $getPlate->close();
            
            $stmt = $conn->prepare("DELETE FROM assign WHERE trip_id=?");
            if ($stmt === false) {
                throw new Exception("Failed to prepare delete statement: " . $conn->error);
            }
            $stmt->bind_param("i", $data['id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute delete: " . $stmt->error);
            }
            
            $stmt->close();
            
            if ($trip && $trip['status'] === 'En Route') {
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'In Terminal' WHERE plate_no = ?");
                if ($updateTruck === false) {
                    throw new Exception("Failed to prepare truck update: " . $conn->error);
                }
                $updateTruck->bind_param("s", $trip['plate_no']);
                $updateTruck->execute();
                $updateTruck->close();
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'get_drivers':
            $stmt = $conn->prepare("SELECT driver_id, name, email FROM drivers_table ORDER BY name");
            if ($stmt === false) {
                throw new Exception("Failed to prepare drivers query: " . $conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $drivers = [];
            while ($row = $result->fetch_assoc()) {
                $drivers[] = $row;
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'drivers' => $drivers]);
            break;

        case 'get_trips_with_drivers':
            $whereClause = "";
            $params = [];
            $types = "";
            
            // Filter by driver if specified
            if (isset($data['driver_id'])) {
                $whereClause = "WHERE a.driver_id = ?";
                $params = [$data['driver_id']];
                $types = "i";
            } elseif (isset($data['driver_name'])) {
                $whereClause = "WHERE a.driver = ?";
                $params = [$data['driver_name']];
                $types = "s";
            }
            
            // Check if created_at column exists
            $columnsQuery = $conn->query("SHOW COLUMNS FROM assign LIKE 'created_at'");
            $hasCreatedAt = $columnsQuery && $columnsQuery->num_rows > 0;
            
            if ($hasCreatedAt) {
                $sql = "
                    SELECT a.*, d.name as driver_name, d.email as driver_email,
                           DATE_FORMAT(a.date, '%Y-%m-%d') as formatted_date,
                           DATE_FORMAT(a.created_at, '%Y-%m-%d %H:%i:%s') as created_timestamp
                    FROM assign a 
                    LEFT JOIN drivers_table d ON a.driver_id = d.driver_id 
                    $whereClause
                    ORDER BY a.date DESC, a.created_at DESC
                ";
            } else {
                $sql = "
                    SELECT a.*, d.name as driver_name, d.email as driver_email,
                           DATE_FORMAT(a.date, '%Y-%m-%d') as formatted_date,
                           NULL as created_timestamp
                    FROM assign a 
                    LEFT JOIN drivers_table d ON a.driver_id = d.driver_id 
                    $whereClause
                    ORDER BY a.date DESC, a.trip_id DESC
                ";
            }
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare trips query: " . $conn->error);
            }
            
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute trips query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $trips = [];
            while ($row = $result->fetch_assoc()) {
                $trips[] = $row;
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'trips' => $trips]);
            break;

        case 'get_driver_current_trip':
            $driverId = $data['driver_id'] ?? null;
            $driverName = $data['driver_name'] ?? null;
            
            if (!$driverId && !$driverName) {
                throw new Exception("Driver ID or name required");
            }
            
            $whereClause = $driverId ? "a.driver_id = ?" : "a.driver = ?";
            $param = $driverId ? $driverId : $driverName;
            $type = $driverId ? "i" : "s";
            
            $stmt = $conn->prepare("
                SELECT a.*, d.name as driver_name, d.email as driver_email,
                       DATE_FORMAT(a.date, '%Y-%m-%d') as formatted_date
                FROM assign a 
                LEFT JOIN drivers_table d ON a.driver_id = d.driver_id 
                WHERE $whereClause AND a.status IN ('En Route', 'Pending')
                ORDER BY 
                    CASE WHEN a.status = 'En Route' THEN 1 ELSE 2 END,
                    a.date ASC
                LIMIT 1
            ");
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare current trip query: " . $conn->error);
            }
            
            $stmt->bind_param($type, $param);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute current trip query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $trip = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'trip' => $trip,
                'has_active_trip' => $trip !== null
            ]);
            break;

        case 'get_driver_scheduled_trips':
            $driverId = $data['driver_id'] ?? null;
            $driverName = $data['driver_name'] ?? null;
            
            if (!$driverId && !$driverName) {
                throw new Exception("Driver ID or name required");
            }
            
            $whereClause = $driverId ? "a.driver_id = ?" : "a.driver = ?";
            $param = $driverId ? $driverId : $driverName;
            $type = $driverId ? "i" : "s";
            
            $stmt = $conn->prepare("
                SELECT a.*, d.name as driver_name, d.email as driver_email,
                       DATE_FORMAT(a.date, '%Y-%m-%d') as formatted_date
                FROM assign a 
                LEFT JOIN drivers_table d ON a.driver_id = d.driver_id 
                WHERE $whereClause AND a.status = 'Pending'
                ORDER BY a.date ASC
            ");
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare scheduled trips query: " . $conn->error);
            }
            
            $stmt->bind_param($type, $param);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute scheduled trips query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $trips = [];
            while ($row = $result->fetch_assoc()) {
                $trips[] = $row;
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'trips' => $trips]);
            break;

        case 'update_trip_status':
            // Simplified status update for mobile app
            $tripId = $data['trip_id'] ?? null;
            $newStatus = $data['status'] ?? null;
            
            if (!$tripId || !$newStatus) {
                throw new Exception("Trip ID and status required");
            }
            
            // Get current trip info
            $getCurrent = $conn->prepare("SELECT plate_no, status FROM assign WHERE trip_id = ?");
            if ($getCurrent === false) {
                throw new Exception("Failed to prepare current status query: " . $conn->error);
            }
            $getCurrent->bind_param("i", $tripId);
            $getCurrent->execute();
            $current = $getCurrent->get_result()->fetch_assoc();
            $getCurrent->close();
            
            if (!$current) {
                throw new Exception("Trip not found");
            }
            
            // Check if last_modified_at column exists
            $columnsQuery = $conn->query("SHOW COLUMNS FROM assign LIKE 'last_modified_at'");
            $hasLastModifiedAt = $columnsQuery && $columnsQuery->num_rows > 0;
            
            // Update trip status
            if ($hasLastModifiedAt) {
                $stmt = $conn->prepare("UPDATE assign SET 
                    status = ?, 
                    last_modified_by = ?, 
                    last_modified_at = NOW()
                    WHERE trip_id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE assign SET 
                    status = ?, 
                    last_modified_by = ?
                    WHERE trip_id = ?");
            }
                
            if ($stmt === false) {
                throw new Exception("Failed to prepare status update: " . $conn->error);
            }
            
            $stmt->bind_param("ssi", $newStatus, $currentUser, $tripId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute status update: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Update truck status accordingly
            $newTruckStatus = 'In Terminal';
            if ($newStatus === 'En Route') {
                $newTruckStatus = 'Enroute';
            } elseif ($newStatus === 'Pending') {
                $newTruckStatus = 'Pending';
            }
            
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE plate_no = ?");
            if ($updateTruck === false) {
                throw new Exception("Failed to prepare truck status update: " . $conn->error);
            }
            $updateTruck->bind_param("ss", $newTruckStatus, $current['plate_no']);
            $updateTruck->execute();
            $updateTruck->close();
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            break;

            case 'save_checklist':
    // Check if checklist already exists for this trip
    $checkStmt = $conn->prepare("SELECT id FROM driver_checklist WHERE trip_id = ?");
    $checkStmt->bind_param("i", $data['trip_id']);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
    if ($exists) {
        // Update existing checklist
        $stmt = $conn->prepare("UPDATE driver_checklist SET 
            no_fatigue = ?,
            no_drugs = ?,
            no_distractions = ?,
            no_illness = ?,
            fit_to_work = ?,
            alcohol_test = ?,
            hours_sleep = ?
            WHERE trip_id = ?");
        $stmt->bind_param("iiiiiddi", 
            $data['no_fatigue'],
            $data['no_drugs'],
            $data['no_distractions'],
            $data['no_illness'],
            $data['fit_to_work'],
            $data['alcohol_test'],
            $data['hours_sleep'],
            $data['trip_id']
        );
    } else {
        // Insert new checklist
        $stmt = $conn->prepare("INSERT INTO driver_checklist (
            trip_id,
            no_fatigue,
            no_drugs,
            no_distractions,
            no_illness,
            fit_to_work,
            alcohol_test,
            hours_sleep
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiidd", 
            $data['trip_id'],
            $data['no_fatigue'],
            $data['no_drugs'],
            $data['no_distractions'],
            $data['no_illness'],
            $data['fit_to_work'],
            $data['alcohol_test'],
            $data['hours_sleep']
        );
    }
    
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;



        case 'fix_missing_driver_ids':
            $stmt = $conn->prepare("
                UPDATE assign a 
                SET driver_id = (
                    SELECT d.driver_id 
                    FROM drivers_table d 
                    WHERE d.name = a.driver 
                    LIMIT 1
                ) 
                WHERE a.driver_id IS NULL AND a.driver IS NOT NULL
            ");
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare driver ID fix: " . $conn->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute driver ID fix: " . $stmt->error);
            }
            
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            echo json_encode(['success' => true, 'updated_records' => $affectedRows]);
            break;

        default:
            throw new Exception("Invalid action: $action");
    }
} catch (Exception $e) {
    error_log("Trip handler error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log("Fatal error in trip handler: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
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