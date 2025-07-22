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
            WHERE m.truck_id = ?
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

// Get upcoming maintenance reminders
function getMaintenanceReminders($conn) {
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
            $reminders[] = $row;
        }
    } else {
        // Log error for debugging
        error_log("Reminders query error: " . $conn->error);
    }
    
    return $reminders;
}

// Function to update truck status based on maintenance status
function updateTruckStatusFromMaintenance($conn, $truckId, $status) {
    $newStatus = 'Good'; // Default
    
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
    
case 'edit':
    $data = json_decode(file_get_contents("php://input"));
    
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

    // Get the truck ID first
    $getTruck = $conn->prepare("SELECT t.truck_id FROM maintenance m 
                               JOIN truck_table t ON m.truck_id = t.truck_id 
                               WHERE m.maintenance_id = ?");
    $getTruck->bind_param("i", $id);
    $getTruck->execute();
    $truck = $getTruck->get_result()->fetch_assoc();
    
    // Then soft delete the maintenance record
    $stmt = $conn->prepare("UPDATE maintenance SET is_deleted = 1, delete_reason = ?, last_modified_by = ?, last_modified_at = NOW() WHERE maintenance_id = ?");
    $stmt->bind_param("ssi", 
        $deleteReason,
        $_SESSION['username'],
        $id
    );
    
    if ($stmt->execute()) {
        // Update truck status to In Terminal if maintenance was deleted
        if ($truck) {
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'In Terminal' WHERE truck_id = ?");
            $updateTruck->bind_param("i", $truck['truck_id']);
            $updateTruck->execute();
        }
        
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