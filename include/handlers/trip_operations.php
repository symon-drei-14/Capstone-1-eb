<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

$currentUser = $_SESSION['username'] ?? 'System';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? '';

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

function checkDriverAvailability($conn, $driverId, $tripDate, $excludeTripId = null) {
    $query = "SELECT t.trip_date, dest.name as destination, t.status 
              FROM trips t
              JOIN drivers_table d ON t.driver_id = d.driver_id
              LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
              LEFT JOIN audit_logs_trips alt ON t.trip_id = alt.trip_id
              WHERE t.driver_id = ? 
              AND t.status IN ('Pending', 'En Route')
              AND (alt.is_deleted IS NULL OR alt.is_deleted = 0)
              AND DATE(t.trip_date) BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)";
    
    if ($excludeTripId) {
        $query .= " AND t.trip_id != ?";
    }
    
    $stmt = $conn->prepare($query);
    
    if ($excludeTripId) {
        $stmt->bind_param("issi", $driverId, $tripDate, $tripDate, $excludeTripId);
    } else {
        $stmt->bind_param("iss", $driverId, $tripDate, $tripDate);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conflictingTrips = [];
    while ($row = $result->fetch_assoc()) {
        $conflictingTrips[] = $row;
    }
    
    return $conflictingTrips;
}

function validateTripDate($tripDate, $isEdit = false) {
    // If this is an edit operation, skip the 3-day validation
    if ($isEdit) {
        return ['valid' => true];
    }
    
    $today = new DateTime();
    $tripDateTime = new DateTime($tripDate);
    $interval = $today->diff($tripDateTime);
    
    // Check if trip date is at least 3 days from today (only for new trips)
    if ($interval->days < 3 && $tripDateTime > $today) {
        $earliestDate = (new DateTime())->modify('+3 days')->format('Y-m-d');
        return [
            'valid' => false,
            'message' => "Trips must be scheduled at least 3 days in advance. The earliest available date is $earliestDate."
        ];
    }
    
    return ['valid' => true];
}

function checkDriverEnRouteTrips($conn, $driverId, $excludeTripId = null) {
    $query = "SELECT COUNT(*) as count 
              FROM trips t
              WHERE t.driver_id = ? 
              AND t.status = 'En Route'
              AND NOT EXISTS (
                  SELECT 1 FROM audit_logs_trips alt 
                  WHERE alt.trip_id = t.trip_id AND alt.is_deleted = 1
              )";
    
    if ($excludeTripId) {
        $query .= " AND t.trip_id != ?";
    }
    
    $stmt = $conn->prepare($query);
    
    if ($excludeTripId) {
        $stmt->bind_param("ii", $driverId, $excludeTripId);
    } else {
        $stmt->bind_param("i", $driverId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    return $count > 0;
}

function insertTripExpenses($conn, $tripId, $cashAdvance, $additionalCashAdvance = 0, $diesel = 0) {
    $stmt = $conn->prepare("INSERT INTO trip_expenses (trip_id, cash_advance, additional_cash_advance, diesel) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iddd", $tripId, $cashAdvance, $additionalCashAdvance, $diesel);
    return $stmt->execute();
}

function updateTripExpenses($conn, $tripId, $cashAdvance, $additionalCashAdvance = 0, $diesel = 0) {
    $checkStmt = $conn->prepare("SELECT expense_id FROM trip_expenses WHERE trip_id = ?");
    $checkStmt->bind_param("i", $tripId);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    
    if ($exists) {
        $stmt = $conn->prepare("UPDATE trip_expenses SET cash_advance = ?, additional_cash_advance = ?, diesel = ? WHERE trip_id = ?");
        $stmt->bind_param("dddi", $cashAdvance, $additionalCashAdvance, $diesel, $tripId);
    } else {
        $stmt = $conn->prepare("INSERT INTO trip_expenses (trip_id, cash_advance, additional_cash_advance, diesel) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iddd", $tripId, $cashAdvance, $additionalCashAdvance, $diesel);
    }
    
    return $stmt->execute();
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

function getOrCreatePortId($conn, $portName) {
    if (empty($portName)) return null;
    
    $stmt = $conn->prepare("SELECT port_id FROM ports WHERE name = ?");
    $stmt->bind_param("s", $portName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['port_id'];
    } else {
        $insertStmt = $conn->prepare("INSERT INTO ports (name) VALUES (?)");
        $insertStmt->bind_param("s", $portName);
        $insertStmt->execute();
        return $conn->insert_id;
    }
}

function deleteTripChecklist($conn, $tripId) {
    $stmt = $conn->prepare("DELETE FROM driver_checklist WHERE trip_id = ?");
    $stmt->bind_param("i", $tripId);
    return $stmt->execute();
}

function checkMaintenanceConflict($conn, $truckId, $tripDate) {
    $stmt = $conn->prepare("
   SELECT m.date_mtnce, m.remarks, mt.type_name 
FROM maintenance_table m
LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.maintenance_type_id
LEFT JOIN audit_logs_maintenance alm ON m.maintenance_id = alm.maintenance_id
WHERE m.truck_id = ? 
AND (alm.is_deleted = 0 OR alm.is_deleted IS NULL)
AND m.status != 'Completed'
AND (
    -- Exact date match
    m.date_mtnce = ?
    OR
    -- Within one week before maintenance
    DATEDIFF(m.date_mtnce, ?) BETWEEN 0 AND 7
    OR
    -- Or if trip spans multiple days that might conflict
    ? BETWEEN DATE_SUB(m.date_mtnce, INTERVAL 7 DAY) AND m.date_mtnce
        )
    ");
    
    if (!$stmt) {
        return ['hasConflict' => false]; // On error, assume no conflict
    }
    
    $stmt->bind_param("isss", $truckId, $tripDate, $tripDate, $tripDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $maintenance = $result->fetch_assoc();
        return [
            'hasConflict' => true,
            'maintenanceDate' => $maintenance['date_mtnce'],
            'maintenanceType' => $maintenance['type_name'],
            'remarks' => $maintenance['remarks']
        ];
    }
    
    return ['hasConflict' => false];
}

try {
    switch ($action) {
        case 'add':
            $conn->begin_transaction();
            
            try {
                // Validate trip date is at least 3 days in advance
                $dateValidation = validateTripDate($data['date']);
                if (!$dateValidation['valid']) {
                    throw new Exception($dateValidation['message']);
                }
                
                // Check for maintenance conflicts first
                $truckId = getTruckIdByPlateNo($conn, $data['plateNo']);
                
                if (!$truckId) {
                    throw new Exception("Truck with plate number {$data['plateNo']} not found");
                }
                
                // Check if the trip date conflicts with maintenance
                $maintenanceCheck = checkMaintenanceConflict($conn, $truckId, $data['date']);
                
                if ($maintenanceCheck['hasConflict']) {
                    throw new Exception("Cannot schedule trip: Truck has maintenance scheduled on " . 
                                       $maintenanceCheck['maintenanceDate'] . ". " .
                                       "Trips cannot be scheduled within one week of maintenance.");
                }

                $driverId = getDriverIdByName($conn, $data['driver']);
                $conflictingTrips = checkDriverAvailability($conn, $driverId, $data['date']);
                
                if (!empty($conflictingTrips)) {
                    $conflictDetails = "";
                    foreach ($conflictingTrips as $trip) {
                        $tripDate = date('M j, Y', strtotime($trip['trip_date']));
                        $conflictDetails .= "• {$tripDate} - {$trip['destination']} ({$trip['status']})\n";
                    }
                    
                    throw new Exception("Driver {$data['driver']} has conflicting trips within 3 days of the selected date:\n\n" . 
                                       $conflictDetails . "\nPlease choose a different date or driver.");
                }
                
                $clientId = getOrCreateClientId($conn, $data['client']);
                $helperId = getHelperId($conn, $data['helper']);
                $dispatcherId = getDispatcherId($conn, $data['dispatcher']);
                $destinationId = getOrCreateDestinationId($conn, $data['destination']);
                $shippingLineId = getOrCreateShippingLineId($conn, $data['shippingLine']);
                $consigneeId = getConsigneeId($conn, $data['consignee']);
                $driverId = getDriverIdByName($conn, $data['driver']);
                
                if (!$truckId) {
                    throw new Exception("Truck with plate number {$data['plateNo']} not found");
                }
                if (!$driverId) {
                    throw new Exception("Driver {$data['driver']} not found");
                }
                
                $portId = getOrCreatePortId($conn, $data['port']);
                
                $stmt = $conn->prepare("INSERT INTO trips 
                    (truck_id, driver_id, helper_id, dispatcher_id, client_id, port_id,
                    destination_id, shipping_line_id, consignee_id, container_no, 
                    trip_date, status, fcl_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("iiiiiiiisssss",
                    $truckId,
                    $driverId,
                    $helperId,
                    $dispatcherId,
                    $clientId,
                    $portId,
                    $destinationId,
                    $shippingLineId,
                    $consigneeId,
                    $data['containerNo'],
                    $data['date'],
                    $data['status'],
                    $data['fcl_status']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert trip: " . $stmt->error);
                }
                
                $tripId = $conn->insert_id;

                $cashAdvance = floatval($data['cashAdvance'] ?? 0);
                $additionalCashAdvance = floatval($data['additionalCashAdvance'] ?? 0);
                $diesel = floatval($data['diesel'] ?? 0);
                
                if ($cashAdvance > 0 || $additionalCashAdvance > 0 || $diesel > 0) {
                    if (!insertTripExpenses($conn, $tripId, $cashAdvance, $additionalCashAdvance, $diesel)) {
                        throw new Exception("Failed to insert trip expenses");
                    }
                }

                $auditStmt = $conn->prepare("INSERT INTO audit_logs_trips (trip_id, modified_by, edit_reason) VALUES (?, ?, 'Trip created')");
                $auditStmt->bind_param("is", $tripId, $currentUser);
                if (!$auditStmt->execute()) {
                    throw new Exception("Failed to insert audit log: " . $auditStmt->error);
                }

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
        // Validate trip date is at least 3 days in advance for new dates
        if (isset($data['date'])) {
            
            $dateValidation = validateTripDate($data['date'], true);
            if (!$dateValidation['valid']) {
                throw new Exception($dateValidation['message']);
            }
        }
                
        $getCurrent = $conn->prepare("SELECT status, truck_id, driver_id FROM trips WHERE trip_id = ?");
        $getCurrent->bind_param("i", $data['id']);
        $getCurrent->execute();
        $current = $getCurrent->get_result()->fetch_assoc();
        
        if (!$current) {
            throw new Exception("Trip not found");
        }

        // Get current driver ID before update
        $currentDriverId = $current['driver_id'];
        
        $driverId = getDriverIdByName($conn, $data['driver']);

        // Check if driver is being changed
        if ($currentDriverId != $driverId) {
            // Delete the existing checklist when driver changes
            if (!deleteTripChecklist($conn, $data['id'])) {
                throw new Exception("Failed to reset checklist for new driver");
            }
        }

        if ($data['status'] === 'En Route') {
            // Check if driver already has an En Route trip (excluding current trip)
            $hasEnRouteTrip = checkDriverEnRouteTrips($conn, $driverId, $data['id']);
            
            if ($hasEnRouteTrip) {
                throw new Exception("Cannot set status to 'En Route': Driver {$data['driver']} already has an active trip with En Route status.");
            }
        }
        
        $conflictingTrips = checkDriverAvailability($conn, $driverId, $data['date'], $data['id']);
        
        if (!empty($conflictingTrips)) {
            $conflictDetails = "";
            foreach ($conflictingTrips as $trip) {
                $tripDate = date('M j, Y', strtotime($trip['trip_date']));
                $conflictDetails .= "• {$tripDate} - {$trip['destination']} ({$trip['status']})\n";
            }
            
            throw new Exception("Driver {$data['driver']} has conflicting trips within 3 days of the selected date:\n\n" . 
                               $conflictDetails . "\nPlease choose a different date or driver.");
        }
        
        $truckId = getTruckIdByPlateNo($conn, $data['plateNo']);
        $clientId = getOrCreateClientId($conn, $data['client']);
        $helperId = getHelperId($conn, $data['helper']);
        $dispatcherId = getDispatcherId($conn, $data['dispatcher']);
        $destinationId = getOrCreateDestinationId($conn, $data['destination']);
        $shippingLineId = getOrCreateShippingLineId($conn, $data['shippingLine']);
        $consigneeId = getConsigneeId($conn, $data['consignee']);
        $driverId = getDriverIdByName($conn, $data['driver']);

        $portId = getOrCreatePortId($conn, $data['port']);

        $stmt = $conn->prepare("UPDATE trips SET 
            truck_id=?, driver_id=?, helper_id=?, dispatcher_id=?, client_id=?, port_id=?,
            destination_id=?, shipping_line_id=?, consignee_id=?, container_no=?, 
            trip_date=?, status=?, fcl_status=?  
            WHERE trip_id=?");
        
        $stmt->bind_param("iiiiiiiisssssi",
            $truckId,
            $driverId, 
            $helperId,
            $dispatcherId,
            $clientId,
            $portId,
            $destinationId,
            $shippingLineId,
            $consigneeId,
            $data['containerNo'],
            $data['date'],
            $data['status'],
            $data['fclStatus'],
            $data['id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update trip: " . $stmt->error);
        }
        
        // Update trip expenses with all three fields
        $cashAdvance = floatval($data['cashAdvance'] ?? 0);
        $additionalCashAdvance = floatval($data['additionalCashAdvance'] ?? 0);
        $diesel = floatval($data['diesel'] ?? 0);
        
        if (!updateTripExpenses($conn, $data['id'], $cashAdvance, $additionalCashAdvance, $diesel)) {
            throw new Exception("Failed to update trip expenses");
        }
        
        // Update audit log
        $editReasons = isset($data['editReasons']) ? json_encode($data['editReasons']) : null;
        $auditStmt = $conn->prepare("UPDATE audit_logs_trips SET modified_by=?, modified_at=NOW(), edit_reason=? WHERE trip_id=? AND is_deleted=0");
        $auditStmt->bind_param("ssi", $currentUser, $editReasons, $data['id']);
        
        if (!$auditStmt->execute()) {
            throw new Exception("Failed to update audit log: " . $auditStmt->error);
        }
        
        // Update truck status logic
        if ($current['status'] !== $data['status']) {
            $newTruckStatus = 'In Terminal';
            if ($data['status'] === 'En Route') {
                $newTruckStatus = 'Enroute';
            }
            
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE truck_id = ?");
            $updateTruck->bind_param("si", $newTruckStatus, $truckId);
            
            if (!$updateTruck->execute()) {
                throw new Exception("Failed to update truck status: " . $updateTruck->error);
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    break;

        case 'delete':
    // First check if the trip is in 'En Route' status
    $checkStatusStmt = $conn->prepare("SELECT status FROM trips WHERE trip_id = ?");
    $checkStatusStmt->bind_param("i", $data['id']);
    $checkStatusStmt->execute();
    $statusResult = $checkStatusStmt->get_result();
    
    if ($statusResult->num_rows > 0) {
        $tripStatus = $statusResult->fetch_assoc()['status'];
        
        if ($tripStatus === 'En Route') {
            throw new Exception("Cannot delete a trip that is currently 'En Route'. Please complete or cancel the trip first.");
        }
    }
    
    // Mark trip as deleted in audit log
    $stmt = $conn->prepare("UPDATE audit_logs_trips SET 
        is_deleted = 1,
        delete_reason = ?,
        modified_by = ?,
        modified_at = NOW()
        WHERE trip_id = ? AND is_deleted = 0");
    $stmt->bind_param("ssi", 
        $data['reason'],
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

        case 'get_active_trips':
            $statusFilter = $data['statusFilter'] ?? 'all';
            $sortOrder = $data['sortOrder'] ?? 'desc';
            $page = $data['page'] ?? 1;
            $perPage = $data['perPage'] ?? 10;
            
            $query = "SELECT 
            t.trip_id,
            t.container_no,
            t.trip_date,
            t.status,
            t.fcl_status,
            t.created_at,
            tr.plate_no, 
            tr.capacity as truck_capacity,
            d.name as driver,
            d.driver_id,
            h.name as helper,
            disp.name as dispatcher,
            c.name as client,
            p.name as port,  
            dest.name as destination,
            sl.name as shipping_line,
            cons.name as consignee,
            al.modified_by as last_modified_by,
            al.modified_at as last_modified_at,
            al.edit_reason,
            COALESCE(te.cash_advance, 0) as cash_advance,
            COALESCE(te.additional_cash_advance, 0) as additional_cash_advance,
            COALESCE(te.diesel, 0) as diesel
          FROM trips t
          LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
          LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
          LEFT JOIN helpers h ON t.helper_id = h.helper_id
          LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
          LEFT JOIN clients c ON t.client_id = c.client_id
          LEFT JOIN ports p ON t.port_id = p.port_id  
          LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
          LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
          LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
          LEFT JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 0
          LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
          WHERE NOT EXISTS (
              SELECT 1 FROM audit_logs_trips al2 
              WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
          )";
            
            if ($statusFilter !== 'all') {
                $query .= " AND t.status = ?";
            }
            
            $query .= " ORDER BY t.trip_date " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT ? OFFSET ?";
            
            if ($statusFilter !== 'all') {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sii", $statusFilter, $perPage, $offset);
            } else {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $perPage, $offset);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $trips = [];
            while ($row = $result->fetch_assoc()) {
                $trips[] = $row;
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM trips t
                          WHERE NOT EXISTS (
                              SELECT 1 FROM audit_logs_trips al2 
                              WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
                          )";
            
            if ($statusFilter !== 'all') {
                $countQuery .= " AND t.status = ?";
                $countStmt = $conn->prepare($countQuery);
                $countStmt->bind_param("s", $statusFilter);
            } else {
                $countStmt = $conn->prepare($countQuery);
            }
            
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true, 
                'trips' => $trips,
                'total' => $total,
                'perPage' => $perPage,
                'currentPage' => $page
            ]);
            break;

        case 'get_deleted_trips':
            $statusFilter = $data['statusFilter'] ?? 'all';
            $sortOrder = $data['sortOrder'] ?? 'desc';
            $page = $data['page'] ?? 1;
            $perPage = $data['perPage'] ?? 10;
            
            $query = "SELECT 
            t.trip_id,
            t.container_no,
            t.trip_date,
            t.status,
            t.fcl_status,
            t.created_at,
            tr.plate_no, 
            tr.capacity as truck_capacity,
            d.name as driver,
            h.name as helper,
            disp.name as dispatcher,
            c.name as client,
            p.name as port,  
            dest.name as destination,
            sl.name as shipping_line,
            cons.name as consignee,
            al.modified_by as last_modified_by,
            al.modified_at as last_modified_at,
            al.delete_reason,
            1 as is_deleted,
            COALESCE(te.cash_advance, 0) as cash_advance,
            COALESCE(te.additional_cash_advance, 0) as additional_cash_advance,
            COALESCE(te.diesel, 0) as diesel
          FROM trips t
          LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
          LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
          LEFT JOIN helpers h ON t.helper_id = h.helper_id
          LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
          LEFT JOIN clients c ON t.client_id = c.client_id
          LEFT JOIN ports p ON t.port_id = p.port_id  
          LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
          LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
          LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
          LEFT JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 1
          LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
          WHERE EXISTS (
              SELECT 1 FROM audit_logs_trips al2 
              WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
          )";
            
            if ($statusFilter !== 'all') {
                $query .= " AND t.status = ?";
            }
            
            $query .= " ORDER BY t.trip_date " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT ? OFFSET ?";
            
            if ($statusFilter !== 'all') {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sii", $statusFilter, $perPage, $offset);
            } else {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $perPage, $offset);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $trips = [];
            while ($row = $result->fetch_assoc()) {
                $trips[] = $row;
            }
            
            // Get total count for deleted trips
            $countQuery = "SELECT COUNT(*) as total FROM trips t
                          WHERE EXISTS (
                              SELECT 1 FROM audit_logs_trips al2 
                              WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
                          )";
            
            if ($statusFilter !== 'all') {
                $countQuery .= " AND t.status = ?";
                $countStmt = $conn->prepare($countQuery);
                $countStmt->bind_param("s", $statusFilter);
            } else {
                $countStmt = $conn->prepare($countQuery);
            }
            
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true, 
                'trips' => $trips,
                'total' => $total,
                'perPage' => $perPage,
                'currentPage' => $page
            ]);
            break;

        case 'restore':
            // Restore trip by marking as not deleted
            $stmt = $conn->prepare("UPDATE audit_logs_trips SET 
                is_deleted = 0,
                delete_reason = NULL,
                modified_by = ?,
                modified_at = NOW()
                WHERE trip_id = ? AND is_deleted = 1");
            $stmt->bind_param("si", $currentUser, $data['id']);
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
            
            // Update truck status if needed
            if ($trip && $trip['status'] === 'En Route') {
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Enroute' WHERE truck_id = ?");
                $updateTruck->bind_param("i", $trip['truck_id']);
                $updateTruck->execute();
            }
            
            // Get updated stats
            require 'triplogstats.php';
            $stats = getTripStatistics($conn);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;

        case 'full_delete':
    // First check if the trip is in 'En Route' status
    $checkStatusStmt = $conn->prepare("SELECT status FROM trips WHERE trip_id = ?");
    $checkStatusStmt->bind_param("i", $data['id']);
    $checkStatusStmt->execute();
    $statusResult = $checkStatusStmt->get_result();
    
    if ($statusResult->num_rows > 0) {
        $tripStatus = $statusResult->fetch_assoc()['status'];
        
        if ($tripStatus === 'En Route') {
            throw new Exception("Cannot permanently delete a trip that is currently 'En Route'. Please complete or cancel the trip first.");
        }
    }
    
    // First mark as deleted in audit log (if not already)
    $checkStmt = $conn->prepare("SELECT is_deleted FROM audit_logs_trips WHERE trip_id = ? AND is_deleted = 1");
    $checkStmt->bind_param("i", $data['id']);
    $checkStmt->execute();
    $isDeleted = $checkStmt->get_result()->num_rows > 0;
    
    if (!$isDeleted) {
        throw new Exception("Trip must be soft-deleted first");
    }
    
    // Permanently delete the trip and related records
    $conn->begin_transaction();
    
    try {
        // Delete trip expenses first (if any)
        $deleteExpenses = $conn->prepare("DELETE FROM trip_expenses WHERE trip_id = ?");
        $deleteExpenses->bind_param("i", $data['id']);
        $deleteExpenses->execute();
        
        // Delete audit logs (foreign key constraint)
        $deleteAudit = $conn->prepare("DELETE FROM audit_logs_trips WHERE trip_id = ?");
        $deleteAudit->bind_param("i", $data['id']);
        $deleteAudit->execute();
        
        // Delete the trip
        $deleteTrip = $conn->prepare("DELETE FROM trips WHERE trip_id = ?");
        $deleteTrip->bind_param("i", $data['id']);
        $deleteTrip->execute();
        
        $conn->commit();
        
        // Get updated stats
        require 'triplogstats.php';
        $stats = getTripStatistics($conn);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'message' => 'Trip permanently deleted'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    break;

            case 'get_ports':
    $stmt = $conn->prepare("SELECT port_id, name FROM ports ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ports = [];
    while ($row = $result->fetch_assoc()) {
        $ports[] = $row;
    }
    
    echo json_encode(['success' => true, 'ports' => $ports]);
    break;


      case 'get_expenses':
    $tripId = $data['tripId'] ?? 0;
    
    // First get the cash advance and diesel amounts
    $stmt = $conn->prepare("SELECT cash_advance, additional_cash_advance, diesel FROM trip_expenses WHERE trip_id = ?");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $expenseResult = $stmt->get_result();
    $tripExpenses = $expenseResult->fetch_assoc();
    $stmt->close();
    
    // Then get the driver expenses
    $stmt = $conn->prepare("
        SELECT 
            'Cash Advance' as expense_type,
            CONCAT('₱', FORMAT(cash_advance, 2)) as amount,
            created_at
        FROM trip_expenses 
        WHERE trip_id = ? AND cash_advance > 0
        
        UNION ALL
        
        SELECT 
            'Additional Cash Advance' as expense_type,
            CONCAT('₱', FORMAT(additional_cash_advance, 2)) as amount,
            created_at
        FROM trip_expenses 
        WHERE trip_id = ? AND additional_cash_advance > 0
        
        UNION ALL
        
        SELECT 
            'Diesel' as expense_type,
            CONCAT('₱', FORMAT(diesel, 2)) as amount,
            created_at
        FROM trip_expenses 
        WHERE trip_id = ? AND diesel > 0
        
        UNION ALL
        
        SELECT 
            et.name as expense_type,
            CONCAT('₱', FORMAT(de.amount, 2)) as amount,
            de.created_at
        FROM driver_expenses de
        INNER JOIN expense_types et ON de.expense_type_id = et.type_id
        WHERE de.trip_id = ?
        
        ORDER BY created_at DESC
    ");
    
    $stmt->bind_param("iiii", $tripId, $tripId, $tripId, $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'expenses' => $expenses,
        'cashAdvance' => $tripExpenses['cash_advance'] ?? 0,
        'additionalCashAdvance' => $tripExpenses['additional_cash_advance'] ?? 0,
        'diesel' => $tripExpenses['diesel'] ?? 0
    ]);
    break;

          
case 'get_trips_today':
    $statusFilter = $data['statusFilter'] ?? 'all';
    $sortOrder = $data['sortOrder'] ?? 'desc';
    $page = $data['page'] ?? 1;
    $perPage = $data['perPage'] ?? 10;
    
    // Get today's date
    $today = date('Y-m-d');
    
    $query = "SELECT 
        t.trip_id,
        t.container_no,
        t.trip_date,
        t.status,
        t.fcl_status,
        t.created_at,
        tr.plate_no, 
        tr.capacity as truck_capacity,
        d.name as driver,
        d.driver_id,
        h.name as helper,
        disp.name as dispatcher,
        c.name as client,
        p.name as port,  
        dest.name as destination,
        sl.name as shipping_line,
        cons.name as consignee,
        al.modified_by as last_modified_by,
        al.modified_at as last_modified_at,
        al.edit_reason,
        COALESCE(te.cash_advance, 0) as cash_advance,
        COALESCE(te.additional_cash_advance, 0) as additional_cash_advance,
        COALESCE(te.diesel, 0) as diesel
      FROM trips t
      LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
      LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
      LEFT JOIN helpers h ON t.helper_id = h.helper_id
      LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
      LEFT JOIN clients c ON t.client_id = c.client_id
      LEFT JOIN ports p ON t.port_id = p.port_id  
      LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
      LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
      LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
      LEFT JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 0
      LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
      WHERE NOT EXISTS (
          SELECT 1 FROM audit_logs_trips al2 
          WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
      )
      AND DATE(t.trip_date) = ?";
    
    if ($statusFilter !== 'all') {
        $query .= " AND t.status = ?";
    }
    
    $query .= " ORDER BY t.trip_date " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
    $offset = ($page - 1) * $perPage;
    $query .= " LIMIT ? OFFSET ?";
    
    if ($statusFilter !== 'all') {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $today, $statusFilter, $perPage, $offset);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $today, $perPage, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM trips t
                  WHERE NOT EXISTS (
                      SELECT 1 FROM audit_logs_trips al2 
                      WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
                  )
                  AND DATE(t.trip_date) = ?";
    
    if ($statusFilter !== 'all') {
        $countQuery .= " AND t.status = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("ss", $today, $statusFilter);
    } else {
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("s", $today);
    }
    
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true, 
        'trips' => $trips,
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $page
    ]);
    break;

    case 'get_trip_by_id':
    $tripId = $data['id'] ?? 0;
    
    $query = "SELECT 
        t.trip_id as id,
        t.container_no as containerNo,
        t.trip_date as date,
        t.status,
        t.fcl_status,
        tr.plate_no as plateNo, 
        tr.capacity as truck_capacity,
        d.name as driver,
        d.driver_id,
        h.name as helper,
        disp.name as dispatcher,
        c.name as client,
        p.name as port,  
        dest.name as destination,
        sl.name as shippingLine,
        cons.name as consignee,
        al.modified_by as modifiedby,
        al.modified_at as modifiedat,
        al.edit_reason as edit_reasons,
        COALESCE(te.cash_advance, 0) as cashAdvance,
        COALESCE(te.additional_cash_advance, 0) as additionalCashAdvance,
        COALESCE(te.diesel, 0) as diesel
      FROM trips t
      LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
      LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
      LEFT JOIN helpers h ON t.helper_id = h.helper_id
      LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
      LEFT JOIN clients c ON t.client_id = c.client_id
      LEFT JOIN ports p ON t.port_id = p.port_id  
      LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
      LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
      LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
      LEFT JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 0
      LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
      WHERE t.trip_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $trip = $result->fetch_assoc();
        echo json_encode(['success' => true, 'trip' => $trip]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Trip not found']);
    }
    break;

//eto pa babaguhin ko ehe
case 'get_helpers':
    $stmt = $conn->prepare("SELECT helper_id, name FROM helpers ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $helpers = [];
    while ($row = $result->fetch_assoc()) {
        $helpers[] = $row;
    }
    
    echo json_encode(['success' => true, 'helpers' => $helpers]);
    break;



   case 'get_clients':
    $stmt = $conn->prepare("SELECT client_id, name FROM clients WHERE is_deleted = 0 ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();

    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }

    echo json_encode(['success' => true, 'clients' => $clients]);
    break;

case 'get_destinations':
    $stmt = $conn->prepare("SELECT destination_id, name FROM destinations WHERE is_deleted = 0 ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();

    $destinations = [];
    while ($row = $result->fetch_assoc()) {
        $destinations[] = $row;
    }

    echo json_encode(['success' => true, 'destinations' => $destinations]);
    break;

case 'get_shipping_lines':
    $stmt = $conn->prepare("SELECT shipping_line_id, name FROM shipping_lines WHERE is_deleted = 0 ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();

    $shipping_lines = [];
    while ($row = $result->fetch_assoc()) {
        $shipping_lines[] = $row;
    }

    echo json_encode(['success' => true, 'shipping_lines' => $shipping_lines]);
    break;

case 'get_dispatchers':
    $stmt = $conn->prepare("SELECT dispatcher_id, name FROM dispatchers WHERE is_deleted = 0 ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dispatchers = [];
    while ($row = $result->fetch_assoc()) {
        $dispatchers[] = $row;
    }
    
    echo json_encode(['success' => true, 'dispatchers' => $dispatchers]);
    break;

case 'get_consignees':
    $stmt = $conn->prepare("SELECT consignee_id, name FROM consignees WHERE is_deleted = 0 ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $consignees = [];
    while ($row = $result->fetch_assoc()) {
        $consignees[] = $row;
    }
    
    echo json_encode(['success' => true, 'consignees' => $consignees]);
    break;

        case 'get_checklist':
    $tripId = $data['trip_id'] ?? null;
    $driverId = $data['driver_id'] ?? null;
    
    if (!$tripId) {
        echo json_encode(['success' => false, 'message' => 'Trip ID is required']);
        break;
    }
    
    // If no driver_id is provided, get it from the trip
    if (!$driverId) {
        $getDriverStmt = $conn->prepare("SELECT driver_id FROM trips WHERE trip_id = ?");
        $getDriverStmt->bind_param("i", $tripId);
        $getDriverStmt->execute();
        $driverResult = $getDriverStmt->get_result();
        
        if ($driverResult->num_rows > 0) {
            $driverId = $driverResult->fetch_assoc()['driver_id'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Trip not found']);
            break;
        }
        $getDriverStmt->close();
    }
    
    // Now query checklist with both trip_id and driver_id validation
    $stmt = $conn->prepare("
        SELECT dc.*, DATE_FORMAT(dc.submitted_at, '%Y-%m-%d %H:%i:%s') as formatted_submitted_at 
        FROM driver_checklist dc
        INNER JOIN trips t ON dc.trip_id = t.trip_id
        WHERE dc.trip_id = ? AND t.driver_id = ?
    ");
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare checklist query: ' . $conn->error]);
        break;
    }
    
    $stmt->bind_param("ii", $tripId, $driverId);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to execute checklist query: ' . $stmt->error]);
        break;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $checklist = $result->fetch_assoc();
        echo json_encode(['success' => true, 'checklist' => $checklist]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No checklist data found']);
    }
    $stmt->close();
    break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>