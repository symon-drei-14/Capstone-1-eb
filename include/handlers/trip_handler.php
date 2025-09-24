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
date_default_timezone_set('Asia/Manila');
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

// Helper functions from trip_operations.php
function getOrCreateClientId($conn, $clientName) {
    $stmt = $conn->prepare("SELECT client_id FROM clients WHERE name = ?");
    $stmt->bind_param("s", $clientName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['client_id'];
    } else {
        $insertStmt = $conn->prepare("INSERT INTO clients (name) VALUES (?)");
        $insertStmt->bind_param("s", $clientName);
        $insertStmt->execute();
        return $conn->insert_id;
    }
}

function getHelperId($conn, $helperName) {
    if (empty($helperName)) return null;
    
    $stmt = $conn->prepare("SELECT helper_id FROM helpers WHERE name = ?");
    $stmt->bind_param("s", $helperName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['helper_id'];
    }
    return null;
}

function getDispatcherId($conn, $dispatcherName) {
    if (empty($dispatcherName)) return null;
    
    $stmt = $conn->prepare("SELECT dispatcher_id FROM dispatchers WHERE name = ?");
    $stmt->bind_param("s", $dispatcherName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['dispatcher_id'];
    }
    return null;
}

function getOrCreateDestinationId($conn, $destinationName) {
    $stmt = $conn->prepare("SELECT destination_id FROM destinations WHERE name = ?");
    $stmt->bind_param("s", $destinationName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['destination_id'];
    } else {
        $insertStmt = $conn->prepare("INSERT INTO destinations (name) VALUES (?)");
        $insertStmt->bind_param("s", $destinationName);
        $insertStmt->execute();
        return $conn->insert_id;
    }
}

function getOrCreateShippingLineId($conn, $shippingLineName) {
    $stmt = $conn->prepare("SELECT shipping_line_id FROM shipping_lines WHERE name = ?");
    $stmt->bind_param("s", $shippingLineName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['shipping_line_id'];
    } else {
        $insertStmt = $conn->prepare("INSERT INTO shipping_lines (name) VALUES (?)");
        $insertStmt->bind_param("s", $shippingLineName);
        $insertStmt->execute();
        return $conn->insert_id;
    }
}

function getConsigneeId($conn, $consigneeName) {
    if (empty($consigneeName)) return null;
    
    $stmt = $conn->prepare("SELECT consignee_id FROM consignees WHERE name = ?");
    $stmt->bind_param("s", $consigneeName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['consignee_id'];
    }
    return null;
}

function getTruckIdByPlateNo($conn, $plateNo) {
    $stmt = $conn->prepare("SELECT truck_id FROM truck_table WHERE plate_no = ?");
    $stmt->bind_param("s", $plateNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['truck_id'];
    }
    return null;
}

function getDriverIdByName($conn, $driverName) {
    $stmt = $conn->prepare("SELECT driver_id FROM drivers_table WHERE name = ?");
    $stmt->bind_param("s", $driverName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['driver_id'];
    }
    return null;
}

function insertTripExpenses($conn, $tripId, $cashAdvance) {
    if ($cashAdvance > 0) {
        $stmt = $conn->prepare("INSERT INTO trip_expenses (trip_id, cash_advance) VALUES (?, ?)");
        $stmt->bind_param("id", $tripId, $cashAdvance);
        return $stmt->execute();
    }
    return true;
}

function deleteTripChecklist($conn, $tripId) {
    $stmt = $conn->prepare("DELETE FROM driver_checklist WHERE trip_id = ?");
    $stmt->bind_param("i", $tripId);
    return $stmt->execute();
}

function updateTripExpenses($conn, $tripId, $cashAdvance) {
    $checkStmt = $conn->prepare("SELECT expense_id FROM trip_expenses WHERE trip_id = ?");
    $checkStmt->bind_param("i", $tripId);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    
    if ($exists) {
        $stmt = $conn->prepare("UPDATE trip_expenses SET cash_advance = ? WHERE trip_id = ?");
        $stmt->bind_param("di", $cashAdvance, $tripId);
    } else {
        if ($cashAdvance > 0) {
            $stmt = $conn->prepare("INSERT INTO trip_expenses (trip_id, cash_advance) VALUES (?, ?)");
            $stmt->bind_param("id", $tripId, $cashAdvance);
        } else {
            return true; // No need to insert 0 cash advance
        }
    }
    
    return $stmt->execute();
}

try {
    switch ($action) {
        case 'add':
            $conn->begin_transaction();
            
            try {
                // Get foreign key IDs
                $truckId = getTruckIdByPlateNo($conn, $data['plateNo']);
                $driverId = getDriverIdByName($conn, $data['driver']);
                $clientId = getOrCreateClientId($conn, $data['client']);
                $helperId = getHelperId($conn, $data['helper']);
                $dispatcherId = getDispatcherId($conn, $data['dispatcher']);
                $destinationId = getOrCreateDestinationId($conn, $data['destination']);
                $shippingLineId = getOrCreateShippingLineId($conn, $data['shippingLine']);
                $consigneeId = getConsigneeId($conn, $data['consignee']);
                
                if (!$truckId) {
                    throw new Exception("Truck with plate number {$data['plateNo']} not found");
                }
                if (!$driverId) {
                    throw new Exception("Driver {$data['driver']} not found");
                }
                
                // Insert trip
                $stmt = $conn->prepare("INSERT INTO trips 
                    (truck_id, driver_id, helper_id, dispatcher_id, client_id, 
                    destination_id, shipping_line_id, consignee_id, container_no, 
                    trip_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiiiiiisss",
                    $truckId,
                    $driverId,
                    $helperId,
                    $dispatcherId,
                    $clientId,
                    $destinationId,
                    $shippingLineId,
                    $consigneeId,
                    $data['containerNo'],
                    $data['date'],
                    $data['status']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert trip: " . $stmt->error);
                }
                
                $tripId = $conn->insert_id;

                // Insert cash advance if provided
                $cashAdvance = floatval($data['cashAdvance'] ?? 0);
                if (!insertTripExpenses($conn, $tripId, $cashAdvance)) {
                    throw new Exception("Failed to insert trip expenses");
                }

                // Insert audit log
                $auditStmt = $conn->prepare("INSERT INTO audit_logs_trips (trip_id, modified_by, edit_reason) VALUES (?, ?, 'Trip created')");
                $auditStmt->bind_param("is", $tripId, $currentUser);
                if (!$auditStmt->execute()) {
                    throw new Exception("Failed to insert audit log: " . $auditStmt->error);
                }

                // Update truck status
                if ($data['status'] === 'En Route') {
                    $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Enroute' WHERE truck_id = ?");
                    $updateTruck->bind_param("i", $truckId);
                    $updateTruck->execute();
                }

                $conn->commit();
                echo json_encode(['success' => true, 'trip_id' => $tripId]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        case 'edit':
    $conn->begin_transaction();
    
    try {
        // Get current trip info
        $getCurrent = $conn->prepare("SELECT status, truck_id, driver_id FROM trips WHERE trip_id = ?");
        $getCurrent->bind_param("i", $data['id']);
        $getCurrent->execute();
        $current = $getCurrent->get_result()->fetch_assoc();
        
        if (!$current) {
            throw new Exception("Trip not found");
        }
        
        // Get current driver ID before update
        $currentDriverId = $current['driver_id'];
        
        // Get foreign key IDs
        $truckId = getTruckIdByPlateNo($conn, $data['plateNo']);
        $driverId = getDriverIdByName($conn, $data['driver']);
        $clientId = getOrCreateClientId($conn, $data['client']);
        $helperId = getHelperId($conn, $data['helper']);
        $dispatcherId = getDispatcherId($conn, $data['dispatcher']);
        $destinationId = getOrCreateDestinationId($conn, $data['destination']);
        $shippingLineId = getOrCreateShippingLineId($conn, $data['shippingLine']);
        $consigneeId = getConsigneeId($conn, $data['consignee']);

        // Check if driver is being changed
        if ($currentDriverId != $driverId) {
            // Delete the existing checklist when driver changes
            if (!deleteTripChecklist($conn, $data['id'])) {
                throw new Exception("Failed to reset checklist for new driver");
            }
        }

        if (!$truckId) {
            throw new Exception("Truck with plate number {$data['plateNo']} not found");
        }
        if (!$driverId) {
            throw new Exception("Driver {$data['driver']} not found");
        }

        // Update trip
        $stmt = $conn->prepare("UPDATE trips SET 
            truck_id=?, driver_id=?, helper_id=?, dispatcher_id=?, client_id=?, 
            destination_id=?, shipping_line_id=?, consignee_id=?, container_no=?, 
            trip_date=?, status=?
            WHERE trip_id=?");
        $stmt->bind_param("iiiiiiiisssi",
            $truckId,
            $driverId, 
            $helperId,
            $dispatcherId,
            $clientId,
            $destinationId,
            $shippingLineId,
            $consigneeId,
            $data['containerNo'],
            $data['date'],
            $data['status'],
            $data['id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update trip: " . $stmt->error);
        }
        
        // Update trip expenses
        $cashAdvance = floatval($data['cashAdvance'] ?? 0);
        updateTripExpenses($conn, $data['id'], $cashAdvance);
        
        // Update audit log
        $editReasons = isset($data['editReasons']) ? json_encode($data['editReasons']) : null;
        $auditStmt = $conn->prepare("UPDATE audit_logs_trips SET modified_by=?, modified_at=NOW(), edit_reason=? WHERE trip_id=? AND is_deleted=0");
        $auditStmt->bind_param("ssi", $currentUser, $editReasons, $data['id']);
        $auditStmt->execute();
        
        // Update truck status if status changed
        if ($current['status'] !== $data['status']) {
            $newTruckStatus = 'In Terminal';
            if ($data['status'] === 'En Route') {
                $newTruckStatus = 'Enroute';
            } elseif ($data['status'] === 'Pending') {
                $newTruckStatus = 'Pending';
            }
            
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE truck_id = ?");
            if ($updateTruck === false) {
                throw new Exception("Failed to prepare truck status update: " . $conn->error);
            }
            $updateTruck->bind_param("si", $newTruckStatus, $truckId);
            $updateTruck->execute();
            $updateTruck->close();
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    break;

        case 'delete':
            // Mark trip as deleted in audit log (soft delete)
            $stmt = $conn->prepare("UPDATE audit_logs_trips SET 
                is_deleted = 1,
                delete_reason = ?,
                modified_by = ?,
                modified_at = NOW()
                WHERE trip_id = ? AND is_deleted = 0");
            $stmt->bind_param("ssi", 
                $data['reason'] ?? 'Deleted via mobile',
                $currentUser,
                $data['id']
            );
            $stmt->execute();
            
            // Get trip details for truck status update
            $getTrip = $conn->prepare("
                SELECT t.status, tr.truck_id 
                FROM trips t 
                JOIN truck_table tr ON t.truck_id = tr.truck_id 
                WHERE t.trip_id = ?
            ");
            $getTrip->bind_param("i", $data['id']);
            $getTrip->execute();
            $trip = $getTrip->get_result()->fetch_assoc();
            
            if ($trip) {
                $newTruckStatus = 'In Terminal';
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE truck_id = ?");
                $updateTruck->bind_param("si", $newTruckStatus, $trip['truck_id']);
                $updateTruck->execute();
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
        $whereClause = "WHERE t.driver_id = ?";
        $params = [$data['driver_id']];
        $types = "i";
    } elseif (isset($data['driver_name'])) {
        $whereClause = "WHERE d.name = ?";
        $params = [$data['driver_name']];
        $types = "s";
    }
    
    // Add filter for non-deleted trips
    $whereClause .= ($whereClause ? " AND " : "WHERE ") . "NOT EXISTS (
        SELECT 1 FROM audit_logs_trips al2 
        WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
    )";
    
    // The query is updated to join with the audit log to get the latest modification details.
    $sql = "
        SELECT 
            t.trip_id,
            t.container_no,
            t.trip_date,
            t.status,
            t.created_at,
            tr.plate_no,
            tr.capacity as truck_capacity,
            d.name as driver,
            d.driver_id,
            d.name as driver_name,
            d.email as driver_email,
            h.name as helper,
            disp.name as dispatcher,
            c.name as client,
            dest.name as destination,
            sl.name as shipping_line,
            cons.name as consignee,
            COALESCE(te.cash_advance, 0) as cash_advance,
            COALESCE(te.additional_cash_advance, 0) as additional_cash_advance,
            COALESCE(te.diesel, 0) as diesel,
            (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_cash_advance,
            COALESCE(te.cash_advance, 0) as cash_adv,
            DATE_FORMAT(t.trip_date, '%Y-%m-%d') as formatted_date,
            t.trip_date as date,
            DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as created_timestamp,
            al.modified_at AS last_modified_at,
            al.edit_reason
        FROM trips t
        LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
        LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
        LEFT JOIN helpers h ON t.helper_id = h.helper_id
        LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
        LEFT JOIN clients c ON t.client_id = c.client_id
        LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
        LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
        LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
        LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
        LEFT JOIN (
            SELECT trip_id, MAX(modified_at) as max_modified_at
            FROM audit_logs_trips
            WHERE is_deleted = 0
            GROUP BY trip_id
        ) max_al ON t.trip_id = max_al.trip_id
        LEFT JOIN audit_logs_trips al ON al.trip_id = max_al.trip_id AND al.modified_at = max_al.max_modified_at
        $whereClause
        ORDER BY t.trip_date DESC, t.created_at DESC
    ";
    
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
        // Map to old column names for compatibility
        $row['size'] = $row['truck_capacity'];
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
    
    $whereClause = $driverId ? "t.driver_id = ?" : "d.name = ?";
    $param = $driverId ? $driverId : $driverName;
    $type = $driverId ? "i" : "s";
    
    $stmt = $conn->prepare("
        SELECT 
            t.trip_id,
            t.container_no,
            t.trip_date,
            t.status,
            tr.plate_no,
            tr.capacity as size,
            tr.capacity as truck_capacity,
            d.name as driver,
            d.driver_id,
            d.name as driver_name,
            d.email as driver_email,
            h.name as helper,
            disp.name as dispatcher,
            c.name as client,
            dest.name as destination,
            sl.name as shipping_line,
            cons.name as consignee,
            COALESCE(te.cash_advance, 0) as cash_advance,
            COALESCE(te.additional_cash_advance, 0) as additional_cash_advance,
            COALESCE(te.diesel, 0) as diesel,
            (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_cash_advance,
            COALESCE(te.cash_advance, 0) as cash_adv,
            DATE_FORMAT(t.trip_date, '%Y-%m-%d') as formatted_date,
            t.trip_date as date
        FROM trips t 
        LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
        LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
        LEFT JOIN helpers h ON t.helper_id = h.helper_id
        LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
        LEFT JOIN clients c ON t.client_id = c.client_id
        LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
        LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
        LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
        LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
        WHERE $whereClause 
        AND t.status IN ('En Route', 'Pending')
        AND NOT EXISTS (
            SELECT 1 FROM audit_logs_trips al2 
            WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
        )
        ORDER BY 
            CASE WHEN t.status = 'En Route' THEN 1 ELSE 2 END,
            t.trip_date ASC
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
    
    $whereClause = $driverId ? "t.driver_id = ?" : "d.name = ?";
    $param = $driverId ? $driverId : $driverName;
    $type = $driverId ? "i" : "s";
    
    $stmt = $conn->prepare("
        SELECT 
            t.trip_id,
            t.container_no,
            t.trip_date,
            t.status,
            tr.plate_no,
            tr.capacity as size,
            tr.capacity as truck_capacity,
            d.name as driver,
            d.driver_id,
            d.name as driver_name,
            d.email as driver_email,
            h.name as helper,
            disp.name as dispatcher,
            c.name as client,
            dest.name as destination,
            sl.name as shipping_line,
            cons.name as consignee,
            COALESCE(te.cash_advance, 0) as cash_advance,
            COALESCE(te.additional_cash_advance, 0) as additional_cash_advance,
            COALESCE(te.diesel, 0) as diesel,
            (COALESCE(te.cash_advance, 0) + COALESCE(te.additional_cash_advance, 0)) as total_cash_advance,
            COALESCE(te.cash_advance, 0) as cash_adv,
            DATE_FORMAT(t.trip_date, '%Y-%m-%d') as formatted_date,
            t.trip_date as date
        FROM trips t 
        LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
        LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
        LEFT JOIN helpers h ON t.helper_id = h.helper_id
        LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
        LEFT JOIN clients c ON t.client_id = c.client_id
        LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
        LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
        LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
        LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
        WHERE $whereClause 
        AND t.status = 'Pending'
        AND NOT EXISTS (
            SELECT 1 FROM audit_logs_trips al2 
            WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
        )
        ORDER BY t.trip_date ASC
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

    case 'get_driver_history':
    $driverId = $data['driver_id'] ?? null;
    $driverName = $data['driver_name'] ?? null;
    
    if (!$driverId && !$driverName) {
        throw new Exception("Driver ID or name required");
    }
    
    $whereClause = $driverId ? "t.driver_id = ?" : "d.name = ?";
    $param = $driverId ? $driverId : $driverName;
    $type = $driverId ? "i" : "s";
    
    $stmt = $conn->prepare("
        SELECT 
            t.trip_id,
            t.container_no,
            t.trip_date,
            t.status,
            t.created_at,
            tr.plate_no,
            tr.capacity as truck_capacity,
            d.name as driver,
            d.driver_id,
            d.name as driver_name,
            dest.name as destination,
            c.name as client,
            sl.name as shipping_line,
            cons.name as consignee,
            DATE_FORMAT(t.trip_date, '%Y-%m-%d') as formatted_date,
            DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as created_timestamp,
            -- Get trip start and end times from route data if available
            (SELECT MIN(timestamp) FROM trip_routes WHERE trip_id = t.trip_id) as trip_start_time,
            (SELECT MAX(timestamp) FROM trip_routes WHERE trip_id = t.trip_id) as trip_end_time
        FROM trips t
        LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
        LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
        LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
        LEFT JOIN clients c ON t.client_id = c.client_id
        LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
        LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
        WHERE $whereClause 
        AND t.status = 'Completed'
        AND NOT EXISTS (
            SELECT 1 FROM audit_logs_trips al2 
            WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
        )
        ORDER BY t.trip_date DESC, t.created_at DESC
        LIMIT 50
    ");
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare driver history query: " . $conn->error);
    }
    
    $stmt->bind_param($type, $param);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute driver history query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'trips' => $trips]);
    break;

case 'get_trip_route':
    $tripId = $data['trip_id'] ?? null;
    
    if (!$tripId) {
        throw new Exception("Trip ID required");
    }
    
    $stmt = $conn->prepare("
        SELECT 
            latitude,
            longitude,
            timestamp,
            speed,
            heading
        FROM trip_routes 
        WHERE trip_id = ? 
        ORDER BY timestamp ASC
    ");
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare trip route query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $tripId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute trip route query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $coordinates = [];
    while ($row = $result->fetch_assoc()) {
        $coordinates[] = [
            'latitude' => floatval($row['latitude']),
            'longitude' => floatval($row['longitude']),
            'timestamp' => $row['timestamp'],
            'speed' => $row['speed'] ? floatval($row['speed']) : null,
            'heading' => $row['heading'] ? floatval($row['heading']) : null
        ];
    }
    
    $stmt->close();
    
    if (empty($coordinates)) {
        echo json_encode(['success' => false, 'message' => 'No route data found for this trip']);
    } else {
        echo json_encode([
            'success' => true, 
            'route' => [
                'trip_id' => $tripId,
                'coordinates' => $coordinates,
                'total_points' => count($coordinates)
            ]
        ]);
    }
    break;

case 'store_route_point':
    $tripId = $data['trip_id'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    $speed = $data['speed'] ?? null;
    $heading = $data['heading'] ?? null;
    
    if (!$tripId || !$latitude || !$longitude) {
        throw new Exception("Trip ID, latitude, and longitude are required");
    }
    
    $stmt = $conn->prepare("
        INSERT INTO trip_routes (trip_id, latitude, longitude, timestamp, speed, heading)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare route storage query: " . $conn->error);
    }
    
    $stmt->bind_param("iddsdd", $tripId, $latitude, $longitude, $timestamp, $speed, $heading);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute route storage query: " . $stmt->error);
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Route point stored successfully']);
    break;

case 'store_route_bulk':
    $tripId = $data['trip_id'] ?? null;
    $routePoints = $data['route_points'] ?? [];
    
    if (!$tripId || empty($routePoints)) {
        throw new Exception("Trip ID and route points are required");
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO trip_routes (trip_id, latitude, longitude, timestamp, speed, heading)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare bulk route storage query: " . $conn->error);
        }
        
        foreach ($routePoints as $point) {
            $latitude = $point['latitude'] ?? null;
            $longitude = $point['longitude'] ?? null;
            $timestamp = $point['timestamp'] ?? date('Y-m-d H:i:s');
            $speed = $point['speed'] ?? null;
            $heading = $point['heading'] ?? null;
            
            if (!$latitude || !$longitude) {
                continue;
            }
            
            $stmt->bind_param("iddsdd", $tripId, $latitude, $longitude, $timestamp, $speed, $heading);
            $stmt->execute();
        }
        
        $conn->commit();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Route data stored successfully']);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    break;

case 'get_trip_statistics':
    $tripId = $data['trip_id'] ?? null;
    
    if (!$tripId) {
        throw new Exception("Trip ID required");
    }
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_points,
            MIN(timestamp) as start_time,
            MAX(timestamp) as end_time,
            AVG(CASE WHEN speed > 0 THEN speed END) as avg_speed,
            MAX(speed) as max_speed,
            -- Calculate total distance (approximate)
            SUM(
                6371000 * acos(
                    cos(radians(LAG(latitude) OVER (ORDER BY timestamp))) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(LAG(longitude) OVER (ORDER BY timestamp))) + 
                    sin(radians(LAG(latitude) OVER (ORDER BY timestamp))) * 
                    sin(radians(latitude))
                )
            ) as total_distance_meters
        FROM (
            SELECT latitude, longitude, timestamp, speed
            FROM trip_routes 
            WHERE trip_id = ?
            ORDER BY timestamp ASC
        ) as route_data
    ");
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare trip statistics query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $tripId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute trip statistics query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    $stmt->close();

    if ($stats['start_time'] && $stats['end_time']) {
        $start = new DateTime($stats['start_time']);
        $end = new DateTime($stats['end_time']);
        $duration = $end->diff($start);
        $stats['duration_hours'] = $duration->h + ($duration->days * 24);
        $stats['duration_minutes'] = $duration->i;
        $stats['duration_formatted'] = $duration->format('%h hours %i minutes');
    }

    if ($stats['total_distance_meters']) {
        $stats['total_distance_km'] = round($stats['total_distance_meters'] / 1000, 2);
    }
    
    echo json_encode(['success' => true, 'statistics' => $stats]);
    break;

        case 'update_trip_status':
            $tripId = $data['trip_id'] ?? null;
            $newStatus = $data['status'] ?? null;
            
            if (!$tripId || !$newStatus) {
                throw new Exception("Trip ID and status required");
            }

            $getCurrent = $conn->prepare("SELECT t.status, tr.truck_id FROM trips t JOIN truck_table tr ON t.truck_id = tr.truck_id WHERE t.trip_id = ?");
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

            $stmt = $conn->prepare("UPDATE trips SET status = ? WHERE trip_id = ?");
            if ($stmt === false) {
                throw new Exception("Failed to prepare status update: " . $conn->error);
            }
            
            $stmt->bind_param("si", $newStatus, $tripId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute status update: " . $stmt->error);
            }
            
            $stmt->close();

            $auditStmt = $conn->prepare("UPDATE audit_logs_trips SET modified_by=?, modified_at=NOW(), edit_reason=? WHERE trip_id=? AND is_deleted=0");
            $auditStmt->bind_param("ssi", $currentUser, "Status updated to $newStatus", $tripId);
            $auditStmt->execute();

            $newTruckStatus = 'In Terminal';
            if ($newStatus === 'En Route') {
                $newTruckStatus = 'Enroute';
            } elseif ($newStatus === 'Pending') {
                $newTruckStatus = 'Pending';
            }
            
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE truck_id = ?");
            if ($updateTruck === false) {
                throw new Exception("Failed to prepare truck status update: " . $conn->error);
            }
            $updateTruck->bind_param("si", $newTruckStatus, $current['truck_id']);
            $updateTruck->execute();
            $updateTruck->close();
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            break;

    
 case 'save_checklist':
            // Get the ID of the driver submitting the checklist
            $submittingDriverId = $data['driver_id'] ?? null;
        
            if (!$submittingDriverId) {
                throw new Exception("Submitting driver's ID is missing from the request.");
            }
        
            // Check if the trip exists and get its details for validation
            $tripCheck = $conn->prepare("SELECT driver_id, trip_date FROM trips WHERE trip_id = ?");
            $tripCheck->bind_param("i", $data['trip_id']);
            $tripCheck->execute();
            $tripResult = $tripCheck->get_result();
        
            if ($tripResult->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Trip not found']);
                $tripCheck->close();
                break;
            }
        
            $trip = $tripResult->fetch_assoc();
            $tripCheck->close();
            $assignedDriverId = $trip['driver_id'];
        
            // Here's the bypass logic. If the submitting driver is not the one currently
            // assigned to the trip, it means they are a replacement and should be allowed to submit anytime.
            $isReassignedDriver = ($submittingDriverId != $assignedDriverId);
        
            // Only enforce the time window check if it's the original driver.
            if (!$isReassignedDriver) {
                $tripDateTime = new DateTime($trip['trip_date']);
                $now = new DateTime();
                
                $threeHoursBefore = (clone $tripDateTime)->modify('-3 hours');
                $oneHourBefore = (clone $tripDateTime)->modify('-1 hour');
        
                if ($now < $threeHoursBefore) {
                    $formattedTime = $threeHoursBefore->format('g:i A');
                    echo json_encode([
                        'success' => false,
                        'message' => "Checklist will be available starting at {$formattedTime} (3 hours before the scheduled trip)."
                    ]);
                    break;
                }
        
                if ($now > $oneHourBefore) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Checklist submission closed 1 hour before the scheduled trip time.'
                    ]);
                    break;
                }
            }
        
            // Check if a checklist for this trip already exists
            $checkStmt = $conn->prepare("SELECT id FROM driver_checklist WHERE trip_id = ?");
            $checkStmt->bind_param("i", $data['trip_id']);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->num_rows > 0;
            $checkStmt->close();
        
            if ($exists) {
                // If it exists, we update it.
                $stmt = $conn->prepare("UPDATE driver_checklist SET 
                    no_fatigue = ?, no_drugs = ?, no_distractions = ?, no_illness = ?,
                    fit_to_work = ?, alcohol_test = ?, hours_sleep = ?, submitted_at = NOW()
                    WHERE trip_id = ?");
                $stmt->bind_param("iiiiiddi", 
                    $data['no_fatigue'], $data['no_drugs'], $data['no_distractions'], $data['no_illness'],
                    $data['fit_to_work'], $data['alcohol_test'], $data['hours_sleep'], $data['trip_id']
                );
            } else {
                // Otherwise, we create a new one.
                $stmt = $conn->prepare("INSERT INTO driver_checklist (
                    trip_id, no_fatigue, no_drugs, no_distractions, no_illness,
                    fit_to_work, alcohol_test, hours_sleep, submitted_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiiiiidd", 
                    $data['trip_id'], $data['no_fatigue'], $data['no_drugs'], $data['no_distractions'],
                    $data['no_illness'], $data['fit_to_work'], $data['alcohol_test'], $data['hours_sleep']
                );
            }
        
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Checklist submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save checklist']);
            }
            $stmt->close();
            break;



        case 'get_checklist':
    $tripId = $data['trip_id'] ?? null;
    
    if (!$tripId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Trip ID is required']);
        break;
    }
    
    try {
        $stmt = $conn->prepare("SELECT *, DATE_FORMAT(submitted_at, '%Y-%m-%d %H:%i:%s') as formatted_submitted_at FROM driver_checklist WHERE trip_id = ?");
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $tripId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $checklist = $result->fetch_assoc();
            echo json_encode([
                'success' => true, 
                'checklist' => $checklist,
                'message' => 'Checklist found'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No checklist found for this trip'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching checklist: ' . $e->getMessage()
        ]);
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
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