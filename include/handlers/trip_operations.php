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

require_once 'NotificationService.php';
$notificationService = new NotificationService($conn);

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
    // The query now checks for trips within a 2-hour window (before and after)
    $query = "SELECT t.trip_date, dest.name as destination, t.status 
              FROM trips t
              JOIN drivers_table d ON t.driver_id = d.driver_id
              LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
              LEFT JOIN audit_logs_trips alt ON t.trip_id = alt.trip_id
              WHERE t.driver_id = ? 
              AND t.status IN ('Pending', 'En Route')
              AND (alt.is_deleted IS NULL OR alt.is_deleted = 0)
              AND t.trip_date BETWEEN TIMESTAMPADD(HOUR, -2, ?) AND TIMESTAMPADD(HOUR, 2, ?)";
    
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
    // If this is an edit operation, skip the time validation
    if ($isEdit) {
        return ['valid' => true];
    }
    
    $now = new DateTime();
    $tripDateTime = new DateTime($tripDate);
    
    // Calculate the total minutes difference
    $interval = $now->diff($tripDateTime);
    $minutesDifference = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    
    // If the trip date is in the past (indicated by the 'invert' property of the interval), it's invalid
    if ($interval->invert) {
         return [
            'valid' => false,
            'message' => "Cannot schedule a trip in the past."
        ];
    }

    // Check if the trip date is less than 2 hours (120 minutes) from now for new trips
    if ($minutesDifference < 120) {
        $earliestTime = (new DateTime())->modify('+2 hours')->format('M j, Y h:i A');
        return [
            'valid' => false,
            'message' => "New trips must be scheduled at least 2 hours in advance. The earliest you can book is around {$earliestTime}."
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

function insertTripExpenses($conn, $tripId, $cashAdvance, $additionalCashAdvance = 0) {
    $stmt = $conn->prepare("INSERT INTO trip_expenses (trip_id, cash_advance, additional_cash_advance) VALUES (?, ?, ?)");
    $stmt->bind_param("idd", $tripId, $cashAdvance, $additionalCashAdvance);
    return $stmt->execute();
}

function updateTripExpenses($conn, $tripId, $cashAdvance, $additionalCashAdvance = 0) {
    $checkStmt = $conn->prepare("SELECT expense_id FROM trip_expenses WHERE trip_id = ?");
    $checkStmt->bind_param("i", $tripId);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;

    if ($exists) {
        $stmt = $conn->prepare("UPDATE trip_expenses SET cash_advance = ?, additional_cash_advance = ? WHERE trip_id = ?");
        $stmt->bind_param("ddi", $cashAdvance, $additionalCashAdvance, $tripId);
    } else {
        $stmt = $conn->prepare("INSERT INTO trip_expenses (trip_id, cash_advance, additional_cash_advance) VALUES (?, ?, ?)");
        $stmt->bind_param("idd", $tripId, $cashAdvance, $additionalCashAdvance);
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


function formatDateTimeForTable($datetimeString) {
    if (empty($datetimeString)) return 'N/A';
    try {
        $date = new DateTime($datetimeString);
        return '<span class="date">' . $date->format('M j, Y') . '</span><br> <span class="time">' . $date->format('h:i A') . '</span>';
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

// New helper function to render a single row's HTML
function renderTripRowHtml($trip, $showDeleted, $searchTerm) {
    $highlight = function($text, $search) {
        if (empty($search) || !isset($text)) return htmlspecialchars($text ?? '');
        $text = htmlspecialchars($text);
        $escapedSearch = preg_quote($search, '/');
        if (empty($escapedSearch)) return $text;
        return preg_replace('/(' . $escapedSearch . ')/i', '<span class="highlight">$1</span>', $text);
    };

    $statusCell = '';
    $actionCell = '';

    if ($showDeleted) {
        $statusCell = '<td data-label="Status"><span class="status cancelled">Deleted</span></td>';
        $actionCell = '
           <td data-label="Actions" class="actions">
                <div class="dropdown">
                    <button class="dropdown-btn" data-tooltip="Actions"><i class="fas fa-ellipsis-v"></i></button>
                    <div class="dropdown-content">
                        <button class="dropdown-item restore" data-id="' . $trip['trip_id'] . '"><i class="fas fa-trash-restore"></i> Restore</button>
                        <button class="dropdown-item full-delete" data-id="' . $trip['trip_id'] . '"><i class="fa-solid fa-ban"></i> Permanent Delete</button>
                    </div>
                </div>
            </td>';
    } else {
        $statusClass = strtolower(str_replace(' ', '', $trip['status'] ?? ''));
        $statusCell = '<td data-label="Status"><span class="status ' . $statusClass . '">' . htmlspecialchars($trip['status'] ?? '') . '</span></td>';
        
        $historyButton = (!empty($trip['edit_reason']) && $trip['edit_reason'] !== 'null' && $trip['edit_reason'] !== '[]') ? 
            '<button class="dropdown-item view-reasons" data-id="' . $trip['trip_id'] . '"><i class="fas fa-history"></i> View History</button>' : '';

        $actionCell = '
         <td data-label="Actions" class="actions">
            <div class="dropdown">
                <button class="dropdown-btn" data-tooltip="Actions"><i class="fas fa-ellipsis-v"></i></button>
                <div class="dropdown-content">
                    <button class="dropdown-item edit" data-id="' . $trip['trip_id'] . '"><i class="fas fa-edit"></i> Edit</button>
                    <button class="dropdown-item view-expenses" data-id="' . $trip['trip_id'] . '"><i class="fas fa-money-bill-wave"></i> View Expenses</button>
                    <button class="dropdown-item view-checklist" data-id="' . $trip['trip_id'] . '" data-driver-id="' . ($trip['driver_id'] ?? '') . '"><i class="fas fa-clipboard-check"></i> Driver Checklist</button>
                   <a href="trip_report.php?id=' . $trip['trip_id'] . '" target="_blank" class="dropdown-item Full-report"><i class="fas fa-file-alt"></i> Generate Report</a>'
                    . $historyButton .
                    '<button class="dropdown-item delete" data-id="' . $trip['trip_id'] . '"><i class="fas fa-trash-alt"></i> Delete</button>
                     <button class="dropdown-item cancel-trip" onclick="cancelTrip(' . $trip['trip_id'] . ')"><i class="fas fa-ban"></i> Cancel Trip</button>
                </div>
            </div>
        </td>';
    }
    
    $formattedDate = formatDateTimeForTable($trip['trip_date']);
    $lastModifiedDate = $trip['last_modified_at'] ?? $trip['created_at'];
    $formattedModifiedDate = formatDateTimeForTable($lastModifiedDate);
    $lastModifiedBy = !empty($trip['last_modified_by']) ? '<br> <strong>' . $highlight($trip['last_modified_by'], $searchTerm) . '</strong>' : '';
    $clientPort = $highlight($trip['client'] ?? 'N/A', $searchTerm) . (!empty($trip['port']) ? ' - ' . $highlight($trip['port'], $searchTerm) : '');

    $row = '
        <tr data-trip-id="' . $trip['trip_id'] . '" class="' . ($showDeleted ? 'deleted-row' : '') . '">
            <td data-label="Plate Number">' . $highlight($trip['plate_no'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Trip Date">' . $formattedDate . '</td>
            <td data-label="Driver">' . $highlight($trip['driver'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Helper">' . $highlight($trip['helper'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Dispatcher">' . $highlight($trip['dispatcher'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Container No.">' . $highlight($trip['container_no'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Client - Port">' . $clientPort . '</td>
            <td data-label="Destination">' . $highlight($trip['destination'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Shipping Line">' . $highlight($trip['shipping_line'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Consignee">' . $highlight($trip['consignee'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Container Size">' . $highlight(!empty($trip['truck_capacity']) ? $trip['truck_capacity'] . 'ft' : 'N/A', $searchTerm) . '</td>
            <td data-label="FCL">' . $highlight($trip['fcl_status'] ?? 'N/A', $searchTerm) . '</td>
            <td data-label="Cash Advance">₱' . number_format(floatval($trip['cash_advance'] ?? 0), 2) . '</td>
            <td data-label="Additional Cash">₱' . number_format(floatval($trip['additional_cash_advance'] ?? 0), 2) . '</td>
            
            ' . $statusCell . '
            <td data-label="Last Modified">' . $formattedModifiedDate . $lastModifiedBy . '</td>
            ' . $actionCell . '
        </tr>';

    return $row;
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
    WHERE m.truck_id = ? 
    AND m.status != 'Completed'
    AND NOT EXISTS (
        SELECT 1 
        FROM audit_logs_maintenance al 
        WHERE al.maintenance_id = m.maintenance_id 
          AND al.is_deleted = 1
          AND al.modified_at = (
              SELECT MAX(al2.modified_at)
              FROM audit_logs_maintenance al2
              WHERE al2.maintenance_id = m.maintenance_id
          )
    )
    AND ? >= DATE_SUB(m.date_mtnce, INTERVAL 7 DAY)
    ORDER BY m.date_mtnce ASC
    LIMIT 1
    ");
    
    if (!$stmt) {
        return ['hasConflict' => false]; 
    }
    
    $stmt->bind_param("is", $truckId, $tripDate);
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
        // $dateValidation = validateTripDate($data['date']);
        // if (!$dateValidation['valid']) {
        //     throw new Exception($dateValidation['message']);
        // }
        
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

        // $driverId = getDriverIdByName($conn, $data['driver']);
        // $conflictingTrips = checkDriverAvailability($conn, $driverId, $data['date']);
        
        // if (!empty($conflictingTrips)) {
        //     $conflictDetails = "";
        //     foreach ($conflictingTrips as $trip) {
        //         $tripDate = date('M j, Y h:i A', strtotime($trip['trip_date']));
        //         $conflictDetails .= "• {$tripDate} - {$trip['destination']} ({$trip['status']})\n";
        //     }
            
        //     throw new Exception("Driver {$data['driver']} has a conflicting trip within 2 hours of the selected time:\n\n" . 
        //                         $conflictDetails . "\nPlease choose a different time or driver.");
        // }
        
        $clientId = getOrCreateClientId($conn, $data['client']);
        $helperId = getHelperId($conn, $data['helper']);
        $dispatcherId = getDispatcherId($conn, $data['dispatcher']);
        $destinationId = getOrCreateDestinationId($conn, $data['destination']);
        $shippingLineId = getOrCreateShippingLineId($conn, $data['shippingLine']);
        $consigneeId = getConsigneeId($conn, $data['consignee']);
        $driverId = (string)getDriverIdByName($conn, $data['driver']);
        
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

if ($cashAdvance > 0 || $additionalCashAdvance > 0) {
    if (!insertTripExpenses($conn, $tripId, $cashAdvance, $additionalCashAdvance)) {
        throw new Exception("Failed to insert trip expenses");
    }
}

        $auditStmt = $conn->prepare("INSERT INTO audit_logs_trips (trip_id, modified_by, modified_at, edit_reason) VALUES (?, ?, ?, 'Trip created')");
        $currentTime = date('Y-m-d H:i:s');
        $auditStmt->bind_param("iss", $tripId, $currentUser, $currentTime);
        if (!$auditStmt->execute()) {
            throw new Exception("Failed to insert audit log: " . $auditStmt->error);
        }

         $updateDriverQueueStmt = $conn->prepare("UPDATE drivers_table SET last_assigned_at = ? WHERE driver_id = ?");
        $updateDriverQueueStmt->bind_param("ss", $currentTime, $driverId);
        if (!$updateDriverQueueStmt->execute()) {
            
            throw new Exception("Failed to update driver queue position: " . $updateDriverQueueStmt->error);
        }

         $updateLastAssignedStmt = $conn->prepare("UPDATE drivers_table SET last_assigned_at = ? WHERE driver_id = ?");
        $updateLastAssignedStmt->bind_param("ss", $currentTime, $driverId);
        if (!$updateLastAssignedStmt->execute()) {
            
            error_log("Failed to update driver's last_assigned_at timestamp for driver_id: " . $driverId);
        }

        
       

        if ($data['status'] === 'En Route') {
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Enroute' WHERE truck_id = ?");
            $updateTruck->bind_param("i", $truckId);
            $updateTruck->execute();
        }

            if ($tripId && $driverId) {
            $getTripData = $conn->prepare("
                SELECT 
                    t.trip_id,
                    t.container_no,
                    t.trip_date,
                    dest.name as destination,
                    c.name as client,
                    DATE_FORMAT(t.trip_date, '%M %d, %Y at %h:%i %p') as formatted_date,
                    tr.plate_no,
                    p.name as port,
                    sl.name as shipping_line
                FROM trips t
                LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
                LEFT JOIN clients c ON t.client_id = c.client_id
                LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
                LEFT JOIN ports p ON t.port_id = p.port_id
                LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
                WHERE t.trip_id = ?
            ");
            $getTripData->bind_param("i", $tripId);
            $getTripData->execute();
            $tripData = $getTripData->get_result()->fetch_assoc();
            $getTripData->close();
            
            if ($tripData) {
                $notificationResult = $notificationService->sendTripAssignedNotification($driverId, $tripData);

                if ($notificationResult) {
                    error_log("Notification sent successfully for new trip ID: $tripId to driver ID: $driverId");
                } else {
                    error_log("Failed to send notification for new trip ID: $tripId to driver ID: $driverId");
                }

                error_log("=== NOTIFICATION DEBUG ===");
                error_log("Trip ID: " . ($tripId ?? 'null'));
                error_log("Driver ID: " . $driverId . " (type: " . gettype($driverId) . ")");
                error_log("Trip data exists: " . ($tripData ? 'YES' : 'NO'));
                if ($tripData) {
                    error_log("Destination: " . ($tripData['destination'] ?? 'null'));
                }
                error_log("Notification result: " . ($notificationResult ? 'SUCCESS' : 'FAILED'));
                error_log("=========================");
            }
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

        $currentDriverId = $current['driver_id'];
        
        $driverId = (string)getDriverIdByName($conn, $data['driver']);

        if ($currentDriverId && $currentDriverId != $driverId) {
            $penaltyStmt = $conn->prepare(
                "UPDATE drivers_table SET checked_in_at = NULL WHERE driver_id = ?"
            );
            $penaltyStmt->bind_param("i", $currentDriverId);
            $penaltyStmt->execute();
            $penaltyStmt->close();

            if (!deleteTripChecklist($conn, $data['id'])) {
                throw new Exception("Failed to reset checklist for new driver");
            }
        }
        
        if ($data['status'] === 'En Route') {
            $hasEnRouteTrip = checkDriverEnRouteTrips($conn, $driverId, $data['id']);
            
            if ($hasEnRouteTrip) {
                throw new Exception("Cannot set status to 'En Route': Driver {$data['driver']} already has an active trip with En Route status.");
            }
        }
        
        $truckId = getTruckIdByPlateNo($conn, $data['plateNo']);
        $clientId = getOrCreateClientId($conn, $data['client']);
        $helperId = getHelperId($conn, $data['helper']);
        $dispatcherId = getDispatcherId($conn, $data['dispatcher']);
        $destinationId = getOrCreateDestinationId($conn, $data['destination']);
        $shippingLineId = getOrCreateShippingLineId($conn, $data['shippingLine']);
        $consigneeId = getConsigneeId($conn, $data['consignee']);
        $portId = getOrCreatePortId($conn, $data['port']);

        $stmt = $conn->prepare("UPDATE trips SET 
            truck_id=?, driver_id=?, helper_id=?, dispatcher_id=?, client_id=?, port_id=?,
            destination_id=?, shipping_line_id=?, consignee_id=?, container_no=?, 
            trip_date=?, status=?, fcl_status=?  
            WHERE trip_id=?");
        
        $stmt->bind_param("isiiiiiisssssi",
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
        
       $cashAdvance = floatval($data['cashAdvance'] ?? 0);
        $additionalCashAdvance = floatval($data['additionalCashAdvance'] ?? 0);

        if (!updateTripExpenses($conn, $data['id'], $cashAdvance, $additionalCashAdvance)) {
            throw new Exception("Failed to update trip expenses");
        }
        
        $editReasons = isset($data['editReasons']) ? json_encode($data['editReasons']) : null;
        $auditStmt = $conn->prepare("UPDATE audit_logs_trips SET modified_by=?, modified_at=?, edit_reason=? WHERE trip_id=? AND is_deleted=0 ");
        $currentTime = date('Y-m-d H:i:s');
        $auditStmt->bind_param("sssi", $currentUser, $currentTime, $editReasons, $data['id']);
        
        if (!$auditStmt->execute()) {
            throw new Exception("Failed to update audit log: " . $auditStmt->error);
        }
        
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

            $getTripData = $conn->prepare("
            SELECT 
                t.trip_id,
                t.container_no,
                t.trip_date,
                t.status,
                dest.name as destination,
                c.name as client,
                DATE_FORMAT(t.trip_date, '%M %d, %Y at %h:%i %p') as formatted_date,
                tr.plate_no,
                p.name as port,
                sl.name as shipping_line,
                d_old.name as old_driver_name,
                d_new.name as new_driver_name
            FROM trips t
            LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
            LEFT JOIN clients c ON t.client_id = c.client_id
            LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
            LEFT JOIN ports p ON t.port_id = p.port_id
            LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
            LEFT JOIN drivers_table d_old ON d_old.driver_id = ?
            LEFT JOIN drivers_table d_new ON d_new.driver_id = ?
            WHERE t.trip_id = ?
        ");
        $getTripData->bind_param("isi", $currentDriverId, $driverId, $data['id']);
        $getTripData->execute();
        $tripData = $getTripData->get_result()->fetch_assoc();
        $getTripData->close();
        
        if ($tripData) {
            if ($currentDriverId != $driverId) {
                $cancelResult = $notificationService->sendTripCancelledNotification($currentDriverId, $tripData);

                $assignResult = $notificationService->sendTripAssignedNotification($driverId, $tripData);
                
                error_log("Trip reassigned - Cancelled notification to driver ID $currentDriverId: " . ($cancelResult ? 'success' : 'failed'));
                error_log("Trip reassigned - Assignment notification to driver ID $driverId: " . ($assignResult ? 'success' : 'failed'));
                
            } else {
                $updateResult = $notificationService->sendTripUpdatedNotification($driverId, $tripData);
                error_log("Trip updated notification to driver ID $driverId: " . ($updateResult ? 'success' : 'failed'));
            }

            error_log("=== NOTIFICATION DEBUG ===");
            error_log("Trip ID: " . $data['id']);
            error_log("Driver ID: " . $driverId . " (type: " . gettype($driverId) . ")");
            error_log("Trip data exists: " . ($tripData ? 'YES' : 'NO'));
            if ($tripData) {
                error_log("Destination: " . ($tripData['destination'] ?? 'null'));
            }
            error_log("Current Driver ID: " . $currentDriverId);
            error_log("Driver changed: " . ($currentDriverId != $driverId ? 'YES' : 'NO'));
            error_log("=========================");
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    break;

    case 'send_test_notification':
    $driverId = $data['driver_id'] ?? null;
    $message = $data['message'] ?? 'Test notification from Mansar Trucking';
    
    if (!$driverId) {
        throw new Exception("Driver ID is required");
    }

    $checkDriver = $conn->prepare("SELECT name FROM drivers_table WHERE driver_id = ?");
    $checkDriver->bind_param("i", $driverId);
    $checkDriver->execute();
    $driverResult = $checkDriver->get_result();
    
    if ($driverResult->num_rows === 0) {
        throw new Exception("Driver not found");
    }
    
    $driverName = $driverResult->fetch_assoc()['name'];
    $checkDriver->close();

    $result = $notificationService->sendTestNotification($driverId, $message);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => "Test notification sent successfully to {$driverName}",
            'driver_name' => $driverName
        ]);
    } else {
        throw new Exception("Failed to send test notification to {$driverName}");
    }
    break;

    case 'delete':
    $tripId = $data['id'] ?? 0;
    if ($tripId <= 0) {
        throw new Exception("Invalid Trip ID provided.");
    }

    $conn->begin_transaction();
    try {

        $getTripStmt = $conn->prepare("SELECT status, truck_id FROM trips WHERE trip_id = ?");
        $getTripStmt->bind_param("i", $tripId);
        $getTripStmt->execute();
        $trip = $getTripStmt->get_result()->fetch_assoc();
        $getTripStmt->close();

        if (!$trip) {
            throw new Exception("Trip not found.");
        }


        if ($trip['status'] === 'En Route') {
            throw new Exception("Cannot delete a trip that is currently 'En Route'. Please complete or cancel the trip first.");
        }

       $auditStmt = $conn->prepare(
            "UPDATE audit_logs_trips SET 
                is_deleted = 1,
                delete_reason = ?,
                modified_by = ?,
                modified_at = ?
            WHERE trip_id = ? AND is_deleted = 0
            ORDER BY log_id DESC LIMIT 1" 
        );
        $currentTime = date('Y-m-d H:i:s');
        $auditStmt->bind_param("sssi", $data['reason'], $currentUser, $currentTime, $tripId);
        $auditStmt->execute();
        $auditStmt->close();
        
        if ($trip['truck_id']) {
            $updateTruckStmt = $conn->prepare("UPDATE truck_table SET status = 'In Terminal' WHERE truck_id = ?");
            $updateTruckStmt->bind_param("i", $trip['truck_id']);
            $updateTruckStmt->execute();
            $updateTruckStmt->close();
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    break;


    case 'get_active_trips':
            $statusFilter = $data['statusFilter'] ?? 'all';
            $sortOrder = $data['sortOrder'] ?? 'desc';
            $page = $data['page'] ?? 1;
            $perPage = $data['perPage'] ?? 10;
            $dateFrom = $data['dateFrom'] ?? '';
            $dateTo = $data['dateTo'] ?? '';
            $searchTerm = $data['searchTerm'] ?? ''; 

            $query = "SELECT 
    t.trip_id, t.container_no, t.trip_date, t.status, t.fcl_status, t.created_at,
    tr.plate_no, tr.capacity as truck_capacity, d.name as driver, h.name as helper,
    disp.name as dispatcher, c.name as client, p.name as port, dest.name as destination,
    sl.name as shipping_line, cons.name as consignee, al.modified_by as last_modified_by,
    al.modified_at as last_modified_at, al.edit_reason,
    COALESCE(te.cash_advance, 0) as cash_advance,
    COALESCE(te.additional_cash_advance, 0) as additional_cash_advance
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
            
            $params = [];
            $types = "";
            
            if ($statusFilter !== 'all') {
                $query .= " AND t.status = ?";
                $params[] = $statusFilter;
                $types .= "s";
            }
            

            if (!empty($searchTerm)) {
                $searchQuery = " AND (
                    tr.plate_no LIKE ? OR
                    d.name LIKE ? OR
                    h.name LIKE ? OR
                    disp.name LIKE ? OR
                    c.name LIKE ? OR
                    p.name LIKE ? OR
                    dest.name LIKE ? OR
                    sl.name LIKE ? OR
                    cons.name LIKE ? OR
                    t.container_no LIKE ?
                )";
                $query .= $searchQuery;
                $searchParam = "%{$searchTerm}%";
                for ($i = 0; $i < 10; $i++) { 
                    $params[] = $searchParam;
                    $types .= "s";
                }
            }


            if (!empty($dateFrom) && !empty($dateTo)) {
                $query .= " AND DATE(t.trip_date) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
                $types .= "ss";
            } elseif (!empty($dateFrom)) {
                $query .= " AND DATE(t.trip_date) >= ?";
                $params[] = $dateFrom;
                $types .= "s";
            } elseif (!empty($dateTo)) {
                $query .= " AND DATE(t.trip_date) <= ?";
                $params[] = $dateTo;
                $types .= "s";
            }
            
            $query .= " ORDER BY t.trip_date " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $trips = [];
            while ($row = $result->fetch_assoc()) {
                $trips[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total 
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
                          WHERE NOT EXISTS (
                              SELECT 1 FROM audit_logs_trips al2 
                              WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
                          )";
            
            $countParams = [];
            $countTypes = "";
            
            if ($statusFilter !== 'all') {
                $countQuery .= " AND t.status = ?";
                $countParams[] = $statusFilter;
                $countTypes .= "s";
            }
            
            if (!empty($searchTerm)) {
                $countQuery .= $searchQuery;
                $searchParam = "%{$searchTerm}%";
                for ($i = 0; $i < 10; $i++) {
                    $countParams[] = $searchParam;
                    $countTypes .= "s";
                }
            }

            if (!empty($dateFrom) && !empty($dateTo)) {
                $countQuery .= " AND DATE(t.trip_date) BETWEEN ? AND ?";
                $countParams[] = $dateFrom;
                $countParams[] = $dateTo;
                $countTypes .= "ss";
            } elseif (!empty($dateFrom)) {
                $countQuery .= " AND DATE(t.trip_date) >= ?";
                $countParams[] = $dateFrom;
                $countTypes .= "s";
            } elseif (!empty($dateTo)) {
                $countQuery .= " AND DATE(t.trip_date) <= ?";
                $countParams[] = $dateTo;
                $countTypes .= "s";
            }
            
            $countStmt = $conn->prepare($countQuery);
            
            if (!empty($countParams)) {
                $countStmt->bind_param($countTypes, ...$countParams);
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

            case 'debug_notifications':
    $driverId = $data['driver_id'] ?? null;
    
    if (!$driverId) {
        echo json_encode(['success' => false, 'message' => 'Driver ID required']);
        break;
    }

    $driverCheck = $conn->prepare("SELECT driver_id, name FROM drivers_table WHERE driver_id = ?");
    $driverCheck->bind_param("s", $driverId);
    $driverCheck->execute();
    $driverResult = $driverCheck->get_result();
    $driverExists = $driverResult->num_rows > 0;
    $driverInfo = $driverExists ? $driverResult->fetch_assoc() : null;
    $driverCheck->close();

    $tokenCheck = $conn->prepare("SELECT fcm_token, device_type, is_active, created_at FROM fcm_tokens WHERE driver_id = ?");
    $tokenCheck->bind_param("s", $driverId);
    $tokenCheck->execute();
    $tokenResult = $tokenCheck->get_result();
    $tokens = [];
    while ($row = $tokenResult->fetch_assoc()) {
        $tokens[] = $row;
    }
    $tokenCheck->close();

    $notifCheck = $conn->prepare("SELECT notification_id, title, body, type, is_read, created_at FROM notifications WHERE driver_id = ? ORDER BY created_at DESC LIMIT 10");
    $notifCheck->bind_param("s", $driverId);
    $notifCheck->execute();
    $notifResult = $notifCheck->get_result();
    $notifications = [];
    while ($row = $notifResult->fetch_assoc()) {
        $notifications[] = $row;
    }
    $notifCheck->close();
    
    echo json_encode([
        'success' => true,
        'debug_info' => [
            'driver_id' => $driverId,
            'driver_exists' => $driverExists,
            'driver_info' => $driverInfo,
            'fcm_tokens' => $tokens,
            'notifications' => $notifications,
            'token_count' => count($tokens),
            'active_tokens' => array_filter($tokens, function($t) { return $t['is_active'] == 1; }),
            'notification_count' => count($notifications)
        ]
    ]);
    break;


case 'fetchNextRow':
        $page = $data['page'] ?? 1;
        $perPage = $data['perPage'] ?? 10;
        $statusFilter = $data['statusFilter'] ?? 'all';
        $sortOrder = $data['sortOrder'] ?? 'desc';
        $dateFrom = $data['dateFrom'] ?? '';
        $dateTo = $data['dateTo'] ?? '';
        $searchTerm = $data['searchTerm'] ?? '';

        $offset = ($page * $perPage) - 1;

        $isDeletedView = ($statusFilter === 'deleted');
        $isTodayView = ($statusFilter === 'today');
        
        $baseQuery = "SELECT 
        t.trip_id, t.driver_id, t.container_no, t.trip_date, t.status, t.fcl_status, t.created_at,
        tr.plate_no, tr.capacity as truck_capacity, d.name as driver, h.name as helper,
        disp.name as dispatcher, c.name as client, p.name as port, dest.name as destination,
        sl.name as shipping_line, cons.name as consignee, al.modified_by as last_modified_by,
        al.modified_at as last_modified_at, al.edit_reason, al.delete_reason,
        COALESCE(te.cash_advance, 0) as cash_advance,
        COALESCE(te.additional_cash_advance, 0) as additional_cash_advance
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
      LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id";
        
        $params = [];
        $types = "";

        if ($isDeletedView) {
            $baseQuery .= " JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 1 WHERE 1=1";
        } else {
            $baseQuery .= " LEFT JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 0 
                           WHERE NOT EXISTS (SELECT 1 FROM audit_logs_trips al2 WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1)";
        }

        if (!$isDeletedView && $statusFilter !== 'all' && !$isTodayView) {
            $baseQuery .= " AND t.status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }

        if ($isTodayView) {
            $baseQuery .= " AND DATE(t.trip_date) = CURDATE()";
        }
        
        if (!empty($searchTerm)) {
            $baseQuery .= " AND (tr.plate_no LIKE ? OR d.name LIKE ? OR h.name LIKE ? OR disp.name LIKE ? OR c.name LIKE ? OR p.name LIKE ? OR dest.name LIKE ? OR sl.name LIKE ? OR cons.name LIKE ? OR t.container_no LIKE ?)";
            $searchParam = "%{$searchTerm}%";
            for ($i = 0; $i < 10; $i++) {
                $params[] = $searchParam;
                $types .= "s";
            }
        }

        if (!empty($dateFrom) && !empty($dateTo)) {
            $baseQuery .= " AND DATE(t.trip_date) BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
            $types .= "ss";
        } elseif (!empty($dateFrom)) {
            $baseQuery .= " AND DATE(t.trip_date) >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        } elseif (!empty($dateTo)) {
            $baseQuery .= " AND DATE(t.trip_date) <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }

        $baseQuery .= " ORDER BY t.trip_date " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
        $baseQuery .= " LIMIT 1 OFFSET ?";
        $params[] = $offset;
        $types .= "i";
        
        $stmt = $conn->prepare($baseQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($trip = $result->fetch_assoc()) {
            $rowHtml = renderTripRowHtml($trip, $isDeletedView, $searchTerm);
            echo json_encode(['success' => true, 'rowHtml' => $rowHtml]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No more rows']);
        }
        break;




case 'register_fcm_token':
    $driverId = null;
    $fcmToken = null;
    $deviceType = 'android';

    if (!empty($data)) {
        $driverId = $data['driver_id'] ?? null;
        $fcmToken = $data['fcm_token'] ?? null;
        $deviceType = $data['device_type'] ?? 'android';
    }

    if (empty($driverId) || empty($fcmToken)) {
        $driverId = $_POST['driver_id'] ?? null;
        $fcmToken = $_POST['fcm_token'] ?? null;
        $deviceType = $_POST['device_type'] ?? 'android';
    }

    error_log("=== FCM Registration Debug ===");
    error_log("Driver ID: " . ($driverId ?? 'null'));
    error_log("FCM Token: " . ($fcmToken ? substr($fcmToken, 0, 50) . '...' : 'null'));
    error_log("Device Type: " . $deviceType);
    
    if (empty($driverId) || empty($fcmToken)) {
        echo json_encode([
            'success' => false,
            'message' => 'Driver ID and FCM token are required',
            'received_driver_id' => $driverId,
            'received_token_length' => $fcmToken ? strlen($fcmToken) : 0
        ]);
        break;
    }

    $checkDriver = $conn->prepare("SELECT driver_id, name FROM drivers_table WHERE driver_id = ?");
    $checkDriver->bind_param("s", $driverId);
    $checkDriver->execute();
    $driverResult = $checkDriver->get_result();
    
    if ($driverResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Driver not found with ID: ' . $driverId
        ]);
        break;
    }
    
    $driverInfo = $driverResult->fetch_assoc();
    $checkDriver->close();
    
    try {
        $deactivateStmt = $conn->prepare("UPDATE fcm_tokens SET is_active = 0 WHERE driver_id = ?");
        $deactivateStmt->bind_param("s", $driverId);
        $deactivateResult = $deactivateStmt->execute();
        error_log("Deactivate existing tokens result: " . ($deactivateResult ? 'success' : 'failed'));
        $deactivateStmt->close();

        $currentTime = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("
            INSERT INTO fcm_tokens (driver_id, fcm_token, device_type, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, 1, ?, ?)
            ON DUPLICATE KEY UPDATE 
                is_active = 1, 
                device_type = VALUES(device_type),
                updated_at = VALUES(updated_at)
        ");
        
        $stmt->bind_param("sssss", $driverId, $fcmToken, $deviceType, $currentTime, $currentTime);
        $result = $stmt->execute();
        
        if ($result) {
            error_log("FCM token registered successfully for driver: $driverId");
            echo json_encode([
                'success' => true,
                'message' => 'FCM token registered successfully',
                'driver_name' => $driverInfo['name'],
                'driver_id' => $driverId,
                'token_preview' => substr($fcmToken, 0, 20) . '...'
            ]);
        } else {
            error_log("Failed to register FCM token: " . $stmt->error);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to register FCM token: ' . $stmt->error
            ]);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Exception in FCM token registration: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    break;

case 'get_notifications':
    $driverId = $data['driver_id'] ?? $_POST['driver_id'] ?? '';
    $limit = $data['limit'] ?? $_POST['limit'] ?? 50;
    $offset = $data['offset'] ?? $_POST['offset'] ?? 0;
    
    if (!$driverId) {
        echo json_encode(['success' => false, 'message' => 'Driver ID is required']);
        break;
    }
    
    $notifications = $notificationService->getDriverNotifications($driverId, $limit, $offset);
    $unreadCount = $notificationService->getUnreadCount($driverId);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'total_count' => count($notifications),
        'driver_id' => $driverId
    ]);
    break;


    case 'mark_notification_read':
    $notificationId = $data['notification_id'] ?? null;
    $driverId = $data['driver_id'] ?? null;
    
    if (!$notificationId || !$driverId) {
        throw new Exception("Notification ID and Driver ID are required");
    }
    
    $result = $notificationService->markAsRead($notificationId, $driverId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        throw new Exception("Failed to mark notification as read");
    }
    break;

    case 'get_unread_count':
    $driverId = $data['driver_id'] ?? null;
    
    if (!$driverId) {
        throw new Exception("Driver ID is required");
    }
    
    $unreadCount = $notificationService->getUnreadCount($driverId);
    
    echo json_encode([
        'success' => true, 
        'unread_count' => $unreadCount
    ]);
    break;

    case 'get_deleted_trips':
    $statusFilter = $data['statusFilter'] ?? 'all';
    $sortOrder = $data['sortOrder'] ?? 'desc';
    $page = $data['page'] ?? 1;
    $perPage = $data['perPage'] ?? 10;
    $dateFrom = $data['dateFrom'] ?? '';
    $dateTo = $data['dateTo'] ?? '';
    
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
    COALESCE(te.additional_cash_advance, 0) as additional_cash_advance
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
    
    $params = [];
    $types = "";
    
    if ($statusFilter !== 'all') {
        $query .= " AND t.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    if (!empty($dateFrom) && !empty($dateTo)) {
        $query .= " AND DATE(t.trip_date) BETWEEN ? AND ?";
        $params[] = $dateFrom;
        $params[] = $dateTo;
        $types .= "ss";
    } elseif (!empty($dateFrom)) {
        $query .= " AND DATE(t.trip_date) >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    } elseif (!empty($dateTo)) {
        $query .= " AND DATE(t.trip_date) <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }
    
    $query .= " ORDER BY t.trip_date " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
    $offset = ($page - 1) * $perPage;
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    
    $countQuery = "SELECT COUNT(*) as total FROM trips t
                  WHERE EXISTS (
                      SELECT 1 FROM audit_logs_trips al2 
                      WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
                  )";
    
    $countParams = [];
    $countTypes = "";
    
    if ($statusFilter !== 'all') {
        $countQuery .= " AND t.status = ?";
        $countParams[] = $statusFilter;
        $countTypes .= "s";
    }

    if (!empty($dateFrom) && !empty($dateTo)) {
        $countQuery .= " AND DATE(t.trip_date) BETWEEN ? AND ?";
        $countParams[] = $dateFrom;
        $countParams[] = $dateTo;
        $countTypes .= "ss";
    } elseif (!empty($dateFrom)) {
        $countQuery .= " AND DATE(t.trip_date) >= ?";
        $countParams[] = $dateFrom;
        $countTypes .= "s";
    } elseif (!empty($dateTo)) {
        $countQuery .= " AND DATE(t.trip_date) <= ?";
        $countParams[] = $dateTo;
        $countTypes .= "s";
    }
    
    $countStmt = $conn->prepare($countQuery);
    
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
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
    // First get the trip details before restoring
    $getTripDetails = $conn->prepare("
        SELECT t.trip_date, t.driver_id, d.name as driver_name
        FROM trips t
        JOIN drivers_table d ON t.driver_id = d.driver_id
        WHERE t.trip_id = ?
    ");
    $getTripDetails->bind_param("i", $data['id']);
    $getTripDetails->execute();
    $tripDetails = $getTripDetails->get_result()->fetch_assoc();
    
    if (!$tripDetails) {
        throw new Exception("Trip not found");
    }
    
    // Check if restoring would create a conflict with existing active trips
   $conflictingTrips = checkDriverAvailability($conn, $tripDetails['driver_id'], $tripDetails['trip_date'], $data['id']);
 
if (!empty($conflictingTrips)) {
    $conflictDetails = "";
    foreach ($conflictingTrips as $trip) {
        $tripDate = date('M j, Y h:i A', strtotime($trip['trip_date']));
        $conflictDetails .= "• {$tripDate} - {$trip['destination']} ({$trip['status']})\n";
    }
    
    // This error message is now updated to mention 2 hours
    throw new Exception("Cannot restore trip: Driver {$tripDetails['driver_name']} has a conflicting trip within 2 hours of this trip's date:\n\n" . 
                        $conflictDetails . "\nPlease resolve the conflict before restoring.");
}
    
    // Restore trip by marking as not deleted
 $stmt = $conn->prepare("UPDATE audit_logs_trips SET 
        is_deleted = 0,
        delete_reason = NULL,
        modified_by = ?,
        modified_at = ?
        WHERE trip_id = ? AND is_deleted = 1");
    $currentTime = date('Y-m-d H:i:s');
    $stmt->bind_param("ssi", $currentUser, $currentTime, $data['id']);
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
        // !! NEW CODE !! Delete from driver_expenses
        $deleteDriverExpenses = $conn->prepare("DELETE FROM driver_expenses WHERE trip_id = ?");
        $deleteDriverExpenses->bind_param("i", $data['id']);
        $deleteDriverExpenses->execute();
        $deleteDriverExpenses->close();

        // Delete trip expenses (if any)
        $deleteExpenses = $conn->prepare("DELETE FROM trip_expenses WHERE trip_id = ?");
        $deleteExpenses->bind_param("i", $data['id']);
        $deleteExpenses->execute();
        $deleteExpenses->close();
        
        // Delete audit logs
        $deleteAudit = $conn->prepare("DELETE FROM audit_logs_trips WHERE trip_id = ?");
        $deleteAudit->bind_param("i", $data['id']);
        $deleteAudit->execute();
        $deleteAudit->close();

        // Delete the trip itself (LAST)
        $deleteTrip = $conn->prepare("DELETE FROM trips WHERE trip_id = ?");
        $deleteTrip->bind_param("i", $data['id']);
        $deleteTrip->execute();
        $deleteTrip->close();
        
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

    $stmt = $conn->prepare("SELECT cash_advance, additional_cash_advance FROM trip_expenses WHERE trip_id = ?");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $expenseResult = $stmt->get_result();
    $tripExpenses = $expenseResult->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT
            et.name as expense_type,
            CONCAT('₱', FORMAT(de.amount, 2)) as amount,
            de.created_at as submitted_time,
            de.receipt_image
        FROM driver_expenses de
        INNER JOIN expense_types et ON de.expense_type_id = et.type_id
        WHERE de.trip_id = ?
        ORDER BY de.created_at DESC
    ");

    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();

    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['submitted_time'])) {
            $date = new DateTime($row['submitted_time']);
            $row['submitted_time'] = $date->format('Y-m-d\TH:i:s');
        }
        $expenses[] = $row;
    }

    echo json_encode([
        'success' => true,
        'expenses' => $expenses,
        'cashAdvance' => $tripExpenses['cash_advance'] ?? 0,
        'additionalCashAdvance' => $tripExpenses['additional_cash_advance'] ?? 0
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
    COALESCE(te.additional_cash_advance, 0) as additional_cash_advance
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
    COALESCE(te.additional_cash_advance, 0) as additionalCashAdvance
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

   case 'get_next_driver':
    $capacity = $data['capacity'] ?? '';
    if (empty($capacity)) {
        throw new Exception("Capacity is required to find the next driver.");
    }

    $currentTime = date('Y-m-d H:i:s');
    $checkInCutoff = (new DateTime())->modify('-16 hours')->format('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        SELECT 
            d.driver_id,
            d.name,
            t.plate_no,
            t.capacity
        FROM drivers_table d
        JOIN truck_table t ON d.assigned_truck_id = t.truck_id
        WHERE t.capacity = ?
          -- Rule 1: Must be checked in
          AND d.checked_in_at IS NOT NULL
          -- Rule 2: Check-in must be valid (within the last 16 hours)
          AND d.checked_in_at >= ?
          -- Rule 3: Must not be currently penalized
          AND (d.penalty_until IS NULL OR d.penalty_until < ?)
          -- Rule 4: Truck must be available for a trip
          AND t.is_deleted = 0
          -- A truck can be 'Enroute' and the driver still be available for a *future* trip.
          -- The time conflict check will handle any overlaps.
          AND t.status NOT IN ('In Repair', 'Overdue')
        -- Rule 5: Order by last assignment time (NULLs first), then by check-in time.
        -- This creates a fair, circular queue.
        ORDER BY d.last_assigned_at ASC, d.checked_in_at ASC
        LIMIT 1
    ");
    $stmt->bind_param("sss", $capacity, $checkInCutoff, $currentTime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $driver = $result->fetch_assoc();
        echo json_encode(['success' => true, 'driver' => $driver]);
    } else {
        echo json_encode(['success' => false, 'message' => "No checked-in drivers available in the queue for {$capacity}ft capacity."]);
    }
    break;

      case 'reassign_trip_on_failure':
    $conn->begin_transaction();
    
    try {
        $tripId = $data['trip_id'] ?? null;
        $originalDriverId = $data['original_driver_id'] ?? null;
        $reason = $data['reason'] ?? 'Unknown failure';

        if (!$tripId || !$originalDriverId) {
            throw new Exception("Trip ID and Original Driver ID are required.");
        }

        // 1. Get the required truck capacity and trip details
        $tripStmt = $conn->prepare("
            SELECT tr.capacity, t.truck_id, t.trip_date
            FROM trips t
            JOIN truck_table tr ON t.truck_id = tr.truck_id
            WHERE t.trip_id = ?
        ");
        $tripStmt->bind_param("i", $tripId);
        $tripStmt->execute();
        $tripDetails = $tripStmt->get_result()->fetch_assoc();
        
        if (!$tripDetails) {
            throw new Exception("Trip not found.");
        }
        $capacity = $tripDetails['capacity'];
        $originalTruckId = $tripDetails['truck_id'];
        $tripDate = $tripDetails['trip_date'];

        // Always apply the 16-hour penalty to the original driver
        $penaltyTime = (new DateTime())->modify('+16 hours')->format('Y-m-d H:i:s');
        $penaltyStmt = $conn->prepare("
            UPDATE drivers_table 
            SET penalty_until = ? 
            WHERE driver_id = ?
        ");
        $penaltyStmt->bind_param("si", $penaltyTime, $originalDriverId);
        $penaltyStmt->execute();
        $penaltyStmt->close();

        // 2. Define the base logic for finding the next available driver (based on queue and availability)
        $currentTime = date('Y-m-d H:i:s');
        $checkInCutoff = (new DateTime())->modify('-16 hours')->format('Y-m-d H:i:s');

        $findNextDriverQuery = "
            SELECT d.driver_id, d.name, t.truck_id, t.plate_no, t.capacity
            FROM drivers_table d
            JOIN truck_table t ON d.assigned_truck_id = t.truck_id
            WHERE %s -- Capacity filter placeholder
              AND d.driver_id != ? -- Can't be the penalized driver
              AND d.checked_in_at IS NOT NULL
              AND d.checked_in_at >= ?
              AND (d.penalty_until IS NULL OR d.penalty_until < ?)
              AND t.is_deleted = 0
              AND t.status NOT IN ('In Repair', 'Overdue')
            ORDER BY d.last_assigned_at ASC, d.checked_in_at ASC
            LIMIT 1
        ";
        
        $newDriver = null;
        $fallbackDetails = null;

        // --- PHASE 1: Try to find a direct replacement (Same Capacity) ---
        $capacityFilter1 = "t.capacity = ?";
        $query1 = sprintf($findNextDriverQuery, $capacityFilter1);
        $stmt1 = $conn->prepare($query1);
        $stmt1->bind_param("siss", $capacity, $originalDriverId, $checkInCutoff, $currentTime);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $newDriver = $result1->fetch_assoc();
        $stmt1->close();
        
        // --- PHASE 2: Fallback Logic ---
        if (!$newDriver) {
            $fallbackDetails = null;

            // Only a 20ft trip can fall back to a 40ft truck.
            // A 40ft trip cannot be handled by a 20ft truck.
            if ($capacity == '20') {
                $capacityFilter = "t.capacity = '40'";
                $fallbackCapacity = '40';
                
                $query2 = sprintf($findNextDriverQuery, $capacityFilter);
                $stmt2 = $conn->prepare($query2);
                $stmt2->bind_param("iss", $originalDriverId, $checkInCutoff, $currentTime); 
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $newDriver = $result2->fetch_assoc();
                $stmt2->close();
                
                if ($newDriver) {
                    $fallbackDetails = $fallbackCapacity;
                }
            }
        }

        // --- PHASE 3: Reassign or Cancel ---
        if ($newDriver) {
            $newDriverId = $newDriver['driver_id'];
            $newDriverName = $newDriver['name'];
            $newTruckId = $newDriver['truck_id'];
            $newTruckPlate = $newDriver['plate_no'];
            $newTruckCapacity = $newDriver['capacity'];

            $reassignStmt = $conn->prepare("UPDATE trips SET driver_id = ?, truck_id = ? WHERE trip_id = ?");
            $reassignStmt->bind_param("iii", $newDriverId, $newTruckId, $tripId);
            $reassignStmt->execute();
            $reassignStmt->close();
            
            $reasonDetail = ($reason === 'failed_checklist') ? "failed checklist" : "missed deadline";
            
            if ($fallbackDetails) {
                 $reasonText = "Trip reassigned to {$newTruckCapacity}ft driver {$newDriverName} ({$newTruckPlate}) because no {$capacity}ft driver was available after original driver {$reasonDetail}. Original driver penalized.";
            } else {
                 $reasonText = "Trip reassigned to {$newDriverName} ({$newTruckPlate}) after original driver {$reasonDetail}. Original driver penalized.";
            }

            $auditReason = json_encode([$reasonText]);
            $auditStmt = $conn->prepare("
                UPDATE audit_logs_trips 
                SET modified_by = ?, modified_at = ?, edit_reason = ? 
                WHERE trip_id = ? AND is_deleted = 0
            ");
            
            $auditStmt->bind_param("sssi", $currentUser, $currentTime, $auditReason, $tripId);
            $auditStmt->execute();
            $auditStmt->close();

            $updateLastAssignedStmt = $conn->prepare("UPDATE drivers_table SET last_assigned_at = ? WHERE driver_id = ?");
            $updateLastAssignedStmt->bind_param("si", $currentTime, $newDriverId);
            $updateLastAssignedStmt->execute();
            $updateLastAssignedStmt->close();
            
            $conn->commit();
            echo json_encode([
                'success' => true, 
                'message' => "Trip (ID: {$tripId}) successfully reassigned to {$newDriverName} with {$newTruckCapacity}ft truck.",
                'new_driver_id' => $newDriverId
            ]);

        } else {
            $cancelStmt = $conn->prepare("UPDATE trips SET status = 'Cancelled' WHERE trip_id = ?");
            $cancelStmt->bind_param("i", $tripId);
            $cancelStmt->execute();
            $cancelStmt->close();

            $updateTruckStmt = $conn->prepare("UPDATE truck_table SET status = 'In Terminal' WHERE truck_id = ?");
            $updateTruckStmt->bind_param("i", $originalTruckId);
            $updateTruckStmt->execute();
            $updateTruckStmt->close();


            $reasonText = ($reason === 'failed_checklist') ? 'Trip cancelled: original driver failed checklist and no replacement was found.' : 'Trip cancelled: original driver missed deadline and no replacement was found.';
        $auditReason = json_encode([$reasonText . " Original driver penalized."]);
            $auditStmt = $conn->prepare("
                UPDATE audit_logs_trips 
                SET modified_by = ?, modified_at = ?, edit_reason = ? 
                WHERE trip_id = ? AND is_deleted = 0
            ");
            
            $auditStmt->bind_param("sssi", $currentUser, $currentTime, $auditReason, $tripId);
            $auditStmt->execute();
            $auditStmt->close();
            
            $conn->commit();
            echo json_encode([
                'success' => false, 
                'message' => 'No available drivers for reassignment. The trip has been cancelled and original driver penalized.'
            ]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500); 
        throw $e;
    }
    break;


case 'cancel_trip':

    $tripId = $data['id'] ?? 0;
    if ($tripId <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid Trip ID."]);
        exit;
    }

    $username = $_SESSION['username'] ?? 'System';
    $editReason = json_encode(["Trip status changed to Cancelled by user."]); 

    $conn->begin_transaction();
    try {
        $getTruckStmt = $conn->prepare("SELECT truck_id FROM trips WHERE trip_id = ?");
        $getTruckStmt->bind_param("i", $tripId);
        $getTruckStmt->execute();
        $tripData = $getTruckStmt->get_result()->fetch_assoc();
        $truckId = $tripData['truck_id'] ?? null;
        $getTruckStmt->close();

        $stmt = $conn->prepare("UPDATE trips SET status = 'Cancelled' WHERE trip_id = ?");
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
        $stmt->close();

          $auditStmt = $conn->prepare(
            "UPDATE audit_logs_trips 
             SET modified_by = ?, modified_at = ?, edit_reason = ? 
             WHERE trip_id = ? AND is_deleted = 0 
             ORDER BY log_id DESC LIMIT 1"
        );
        $currentTime = date('Y-m-d H:i:s');
        $auditStmt->bind_param("sssi", $username, $currentTime, $editReason, $tripId);
        $auditStmt->execute();
        $auditStmt->close();

        if ($truckId) {
            $updateTruckStmt = $conn->prepare("UPDATE truck_table SET status = 'In Terminal' WHERE truck_id = ?");
            $updateTruckStmt->bind_param("i", $truckId);
            $updateTruckStmt->execute();
            $updateTruckStmt->close();
        }

        $conn->commit();
        echo json_encode(["success" => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
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