<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

// Get all maintenance records with pagination
function getMaintenanceRecords($conn, $page = 1, $rowsPerPage = 5, $statusFilter = 'all') {
    $offset = ($page - 1) * $rowsPerPage;
    
    $sql = "SELECT 
            m.maintenance_id,
            m.truck_id,
            m.maintenance_type,
            m.licence_plate,
            m.date_mtnce,
            m.remarks,
            m.status,
            m.supplier,
            m.cost,
            m.last_modified_by,
            m.last_modified_at,
            m.edit_reasons,
            m.is_deleted,
            m.delete_reason
            FROM maintenance m
            LEFT JOIN truck_table t ON m.truck_id = t.truck_id";
    
    $params = [];
    $types = '';
    
    // Add status filter
    if ($statusFilter === 'Deleted') {
        $sql .= " WHERE m.is_deleted = 1";
    } elseif ($statusFilter !== 'all') {
        $sql .= " WHERE m.status = ? AND m.is_deleted = 0";
        $params[] = $statusFilter;
        $types .= "s";
    } else {
        $sql .= " WHERE m.is_deleted = 0";
    }
    
    $sql .= " ORDER BY m.maintenance_id DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $rowsPerPage;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    // Get total count for pagination with the same filter
    $countSql = "SELECT COUNT(*) as total FROM maintenance m";
    $countParams = [];
    $countTypes = '';
    
    if ($statusFilter === 'Deleted') {
        $countSql .= " WHERE m.is_deleted = 1";
    } elseif ($statusFilter !== 'all') {
        $countSql .= " WHERE m.status = ? AND m.is_deleted = 0";
        $countParams[] = $statusFilter;
        $countTypes .= "s";
    } else {
        $countSql .= " WHERE m.is_deleted = 0";
    }
    
    $countStmt = $conn->prepare($countSql);
    
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRows = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $rowsPerPage);
    
    return [
        "records" => $records,
        "totalPages" => $totalPages,
        "currentPage" => $page
    ];
}



// Get maintenance history for a specific truck
function getMaintenanceHistory($conn, $truckId) {
    $sql = "SELECT m.maintenance_id, m.date_mtnce, m.remarks, m.status, m.supplier, m.cost, 
            m.last_modified_by, m.last_modified_at, t.plate_no as licence_plate
            FROM maintenance m
            LEFT JOIN truck_table t ON m.truck_id = t.truck_id
           WHERE m.truck_id = ? AND t.is_deleted = 0
            ORDER BY m.date_mtnce DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $truckId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    return $history;
}




function getMaintenanceReminders($conn) {
    // First get all maintenance records that are due soon or overdue
    $sql = "SELECT m.maintenance_id, m.truck_id, t.plate_no as licence_plate, m.date_mtnce, 
            m.remarks, m.status, m.supplier, m.cost, m.last_modified_by, m.last_modified_at,
            DATEDIFF(m.date_mtnce, CURDATE()) as days_remaining
            FROM maintenance m
            LEFT JOIN truck_table t ON m.truck_id = t.truck_id
            WHERE m.status != 'Completed' 
            AND (DATEDIFF(m.date_mtnce, CURDATE()) <= 7)
            ORDER BY days_remaining ASC";
    
    $result = $conn->query($sql);
    
    $reminders = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Check if this record is overdue (days_remaining < 0)
            if ($row['days_remaining'] < 0 && $row['status'] != 'Overdue') {
                // Update the status to Overdue
                $updateStmt = $conn->prepare("UPDATE maintenance SET status = 'Overdue' WHERE maintenance_id = ?");
                $updateStmt->bind_param("i", $row['maintenance_id']);
                $updateStmt->execute();
                
                // Update the truck status
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Overdue' WHERE truck_id = ?");
                $updateTruck->bind_param("i", $row['truck_id']);
                $updateTruck->execute();
                
                // Update the status in our result
                $row['status'] = 'Overdue';
            }
            $reminders[] = $row;
        }
    } else {
        error_log("Reminders query error: " . $conn->error);
    }
    
    return $reminders;
}

// Function to update truck status based on maintenance status
function updateTruckStatusFromMaintenance($conn, $truckId, $status) {
    $newStatus = 'In Terminal'; // Default
    
    if ($status === 'In Progress') {
        $newStatus = 'In Repair';
    } elseif ($status === 'Overdue') {
        $newStatus = 'Overdue';
    } elseif ($status === 'Pending' || $status === 'Completed') {
        // Check if there are any active trips
        $tripQuery = $conn->prepare("SELECT a.status FROM assign a
                                   JOIN truck_table t ON a.plate_no = t.plate_no
                                   WHERE t.truck_id = ?
                                   ORDER BY a.date DESC LIMIT 1");
        $tripQuery->bind_param("i", $truckId);
        $tripQuery->execute();
        $tripResult = $tripQuery->get_result();
        
        if ($tripResult->num_rows === 0 || $tripResult->fetch_assoc()['status'] !== 'En Route') {
            $newStatus = 'In Terminal';
        }
    }
    
    $updateStmt = $conn->prepare("UPDATE truck_table SET status = ? WHERE truck_id = ?");
    $updateStmt->bind_param("si", $newStatus, $truckId);
    $updateStmt->execute();
}

// Process the request based on action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
case 'getRecords':
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $data = getMaintenanceRecords($conn, $page, 5, $statusFilter);
    echo json_encode($data);
    break;

    case 'getHistory':
        $truckId = isset($_GET['truckId']) ? intval($_GET['truckId']) : 0;
        $history = getMaintenanceHistory($conn, $truckId);
        echo json_encode(["history" => $history]);
        break;
        
    case 'getReminders':
        $reminders = getMaintenanceReminders($conn);
        echo json_encode(["reminders" => $reminders]);
        break;



        
  case 'add':
    $data = json_decode(file_get_contents("php://input"));
    
    // First check if the truck exists and isn't deleted
    $truckCheck = $conn->prepare("SELECT truck_id FROM truck_table WHERE truck_id = ? AND is_deleted = 0");
    $truckCheck->bind_param("i", $data->truckId);
    $truckCheck->execute();
    $truckResult = $truckCheck->get_result();
    
    if ($truckResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Truck not found or has been deleted"]);
        exit;
    }
    
    if (!isset($data->truckId, $data->date, $data->remarks, $data->status, $data->maintenanceType)) {
        $missing = [];
        if (!isset($data->truckId)) $missing[] = 'truckId';
        if (!isset($data->date)) $missing[] = 'date';
        if (!isset($data->remarks)) $missing[] = 'remarks';
        if (!isset($data->status)) $missing[] = 'status';
        if (!isset($data->maintenanceType)) $missing[] = 'maintenanceType';
        
        echo json_encode(["success" => false, "message" => "Incomplete data. Missing: " . implode(', ', $missing)]);
        exit;
    }

    // Validate maintenance type
    $validTypes = ['preventive', 'emergency'];
    if (!in_array($data->maintenanceType, $validTypes)) {
        echo json_encode(["success" => false, "message" => "Invalid maintenance type"]);
        exit;
    }

    // Check for preventive maintenance frequency if not emergency
    if ($data->maintenanceType === 'preventive') {
        $checkStmt = $conn->prepare("SELECT date_mtnce FROM maintenance 
                                    WHERE truck_id = ? AND status = 'Completed' 
                                    AND maintenance_type = 'preventive'
                                    ORDER BY date_mtnce DESC LIMIT 1");
        $checkStmt->bind_param("i", $data->truckId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $lastMaintenance = $checkResult->fetch_assoc()['date_mtnce'];
            $lastDate = new DateTime($lastMaintenance);
            $currentDate = new DateTime($data->date);
            $interval = $lastDate->diff($currentDate);
            
            // Check if less than 6 months since last preventive maintenance
            if ($interval->m < 6 && $interval->y == 0) {
                echo json_encode([
                    "success" => false, 
                    "message" => "Preventive maintenance can only be scheduled every 6 months. Last maintenance was on " . 
                                date('Y-m-d', strtotime($lastMaintenance)) . 
                                ". Please mark as emergency repair if needed."
                ]);
                exit;
            }
        }
    }

    $username = $_SESSION['username']; 
    
    $licensePlate = isset($data->licensePlate) ? $data->licensePlate : '';
    $supplier = isset($data->supplier) ? $data->supplier : '';
    $cost = isset($data->cost) ? $data->cost : 0;
    
    $stmt = $conn->prepare("INSERT INTO maintenance (truck_id, licence_plate, date_mtnce, remarks, status, supplier, cost, last_modified_by, maintenance_type) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssdss", 
        $data->truckId,
        $licensePlate,
        $data->date, 
        $data->remarks,
        $data->status,
        $supplier,
        $cost,
        $username,
        $data->maintenanceType
    );
    
    if ($stmt->execute()) {
        // Update truck status based on maintenance status
        updateTruckStatusFromMaintenance($conn, $data->truckId, $data->status);
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }
    $stmt->close();
    break;

    case 'fullDelete':
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM maintenance WHERE maintenance_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }
    break;

    
case 'edit':
    $data = json_decode(file_get_contents("php://input"));
    
    // Check if truck exists and isn't deleted
    $truckCheck = $conn->prepare("SELECT truck_id FROM truck_table WHERE truck_id = ? AND is_deleted = 0");
    $truckCheck->bind_param("i", $data->truckId);
    $truckCheck->execute();
    $truckResult = $truckCheck->get_result();
    
    if ($truckResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Cannot edit - truck has been deleted"]);
        exit;
    }
    
    if (!isset($data->truckId, $data->date, $data->remarks, $data->status, $data->maintenanceType)) {
        $missing = [];
        if (!isset($data->truckId)) $missing[] = 'truckId';
        if (!isset($data->date)) $missing[] = 'date';
        if (!isset($data->remarks)) $missing[] = 'remarks';
        if (!isset($data->status)) $missing[] = 'status';
        if (!isset($data->maintenanceType)) $missing[] = 'maintenanceType';
        
        echo json_encode(["success" => false, "message" => "Incomplete data. Missing: " . implode(', ', $missing)]);
        exit;
    }

    // Validate maintenance type
    $validTypes = ['preventive', 'emergency'];
    if (!in_array($data->maintenanceType, $validTypes)) {
        echo json_encode(["success" => false, "message" => "Invalid maintenance type"]);
        exit;
    }

    $username = $_SESSION['username']; 
    
    $licensePlate = isset($data->licensePlate) ? $data->licensePlate : '';
    $supplier = isset($data->supplier) ? $data->supplier : '';
    $cost = isset($data->cost) ? $data->cost : 0;
    $editReasons = isset($data->editReasons) ? json_encode($data->editReasons) : null;
    
    $stmt = $conn->prepare("UPDATE maintenance SET truck_id = ?, licence_plate = ?, date_mtnce = ?, remarks = ?, 
                           status = ?, supplier = ?, cost = ?, last_modified_by = ?, maintenance_type = ?, edit_reasons = ?
                           WHERE maintenance_id = ?");
    $stmt->bind_param("isssssdsssi", 
        $data->truckId,
        $licensePlate,
        $data->date, 
        $data->remarks,
        $data->status,
        $supplier,
        $cost,
        $username,
        $data->maintenanceType,
        $editReasons,
        $data->maintenanceId
    );
    
    if ($stmt->execute()) {
        // Update truck status based on maintenance status
        updateTruckStatusFromMaintenance($conn, $data->truckId, $data->status);
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }
    $stmt->close();
    break;
    
 case 'delete':
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $deleteReason = isset($_GET['reason']) ? $_GET['reason'] : '';
    
    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit;
    }

    // Get the truck ID and maintenance status first
    $getMaintenance = $conn->prepare("SELECT m.truck_id, m.status, t.plate_no 
                                    FROM maintenance m
                                    JOIN truck_table t ON m.truck_id = t.truck_id
                                    WHERE m.maintenance_id = ?");
    $getMaintenance->bind_param("i", $id);
    $getMaintenance->execute();
    $maintenance = $getMaintenance->get_result()->fetch_assoc();
    
    // Then soft delete the maintenance record
    $stmt = $conn->prepare("UPDATE maintenance SET is_deleted = 1, delete_reason = ?, 
                          last_modified_by = ?, last_modified_at = NOW() 
                          WHERE maintenance_id = ?");
    $stmt->bind_param("ssi", 
        $deleteReason,
        $_SESSION['username'],
        $id
    );
    
    if ($stmt->execute()) {
        // Update truck status based on other active maintenance records
        $checkActiveMaintenance = $conn->prepare("SELECT status FROM maintenance 
                                                WHERE truck_id = ? AND is_deleted = 0
                                                ORDER BY date_mtnce DESC LIMIT 1");
        $checkActiveMaintenance->bind_param("i", $maintenance['truck_id']);
        $checkActiveMaintenance->execute();
        $activeMaintenance = $checkActiveMaintenance->get_result()->fetch_assoc();
        
        $newTruckStatus = 'In Terminal'; // Default
        
        if ($activeMaintenance) {
            // If there are other active maintenance records, use their status
            if ($activeMaintenance['status'] === 'In Progress') {
                $newTruckStatus = 'In Repair';
            } elseif ($activeMaintenance['status'] === 'Overdue') {
                $newTruckStatus = 'Overdue';
            }
        }
        
        // Also check trip status
        $checkTrip = $conn->prepare("SELECT status FROM assign 
                                   WHERE plate_no = ? 
                                   ORDER BY date DESC LIMIT 1");
        $checkTrip->bind_param("s", $maintenance['plate_no']);
        $checkTrip->execute();
        $tripStatus = $checkTrip->get_result()->fetch_assoc();
        
        if ($tripStatus && $tripStatus['status'] === 'Enroute') {
            $newTruckStatus = 'Enroute';
        }
        
        $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE truck_id = ?");
        $updateTruck->bind_param("si", $newTruckStatus, $maintenance['truck_id']);
        $updateTruck->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    break;

case 'restore':
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit;
    }

    // First get the maintenance record to restore
    $getMaintenance = $conn->prepare("SELECT truck_id, status FROM maintenance WHERE maintenance_id = ?");
    $getMaintenance->bind_param("i", $id);
    $getMaintenance->execute();
    $maintenance = $getMaintenance->get_result()->fetch_assoc();
    
    if (!$maintenance) {
        echo json_encode(["success" => false, "message" => "Maintenance record not found"]);
        exit;
    }

    // Restore the maintenance record
    $stmt = $conn->prepare("UPDATE maintenance SET is_deleted = 0, delete_reason = NULL WHERE maintenance_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // If the maintenance status was "In Progress", set truck back to "In Repair"
        if ($maintenance['status'] === 'In Progress') {
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'In Repair' WHERE truck_id = ?");
            $updateTruck->bind_param("i", $maintenance['truck_id']);
            $updateTruck->execute();
        }
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }
    break;
        
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}

$conn->close();
?>